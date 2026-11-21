<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_ledger extends MY_Model
{
    private $company_id = 1;

    /**
     * Semua ledger/jurnal di model ini hanya membaca ERP tables:
     * - gl_entries sebagai source of truth jurnal
     * - accounts untuk label akun
     * - ar_invoices / ar_invoice_items untuk AR Invoice
     * - ar_receipts / ar_receipt_refs untuk Receipt
     * - ap_bills / ap_bill_items untuk AP Bill
     * - ap_payments / ap_payment_refs untuk Vendor Payment
     * - journal_entries bila tersedia untuk JE header
     */

    public function general_ledger($p)
    {
        $db = $this->_erpdb();

        $rawAccount     = trim((string)($p['account_id'] ?? ''));
        $dateFrom       = !empty($p['date_from']) ? $this->_normalize_date($p['date_from']) : date('Y-m-01');
        $dateTo         = !empty($p['date_to'])   ? $this->_normalize_date($p['date_to'])   : date('Y-m-d');
        $search         = trim((string)($p['search'] ?? ''));
        $movement       = strtoupper(trim((string)($p['movement'] ?? 'ALL')));
        $includeOpening = (int)($p['include_opening'] ?? 1);

        $acc       = null;
        $accountId = null;

        if ($rawAccount !== '') {
            $acc = $this->_get_account_by_id_or_code($rawAccount);
            if ($acc) {
                $accountId = (int)$acc['account_id'];
            }
        }

        $opening = 0.0;

        if ($includeOpening && $accountId) {
            $db->reset_query();
            $db->select('COALESCE(SUM(g.debit),0) AS dr, COALESCE(SUM(g.credit),0) AS cr', false)
                ->from('gl_entries g')
                ->where('g.company_id', $this->company_id)
                ->where('g.account_id', $accountId)
                ->where('g.status', 'POSTED')
                ->where('g.posting_date <', $dateFrom);

            $this->_apply_region_filter('gl_entries', 'g');

            $op = $db->get()->row_array();
            $opening = (float)($op['dr'] ?? 0) - (float)($op['cr'] ?? 0);
        }

        $select = [
            'g.gl_id',
            'g.posting_date',
            'g.account_id',
            'a.account_code',
            'a.account_name',
            'g.voucher_type',
            'g.voucher_no',
            'g.debit',
            'g.credit',
            '(g.debit - g.credit) AS delta',
        ];

        if ($this->_field_exists('remarks', 'gl_entries')) {
            $select[] = 'g.remarks';
        } elseif ($this->_field_exists('memo', 'gl_entries')) {
            $select[] = 'g.memo AS remarks';
        } else {
            $select[] = "'' AS remarks";
        }

        $db->reset_query();
        $db->select(implode(",\n", $select), false)
            ->from('gl_entries g')
            ->join('accounts a', 'a.account_id = g.account_id', 'left')
            ->where('g.company_id', $this->company_id)
            ->where('g.status', 'POSTED')
            ->where('g.posting_date >=', $dateFrom)
            ->where('g.posting_date <=', $dateTo);

        $this->_apply_region_filter('gl_entries', 'g');

        if ($accountId) {
            $db->where('g.account_id', $accountId);
        }

        if ($search !== '') {
            $db->group_start()
                ->like('g.voucher_no', $search)
                ->or_like('g.voucher_type', $search)
                ->or_like('a.account_code', $search)
                ->or_like('a.account_name', $search);

            if ($this->_field_exists('remarks', 'gl_entries')) {
                $db->or_like('g.remarks', $search);
            } elseif ($this->_field_exists('memo', 'gl_entries')) {
                $db->or_like('g.memo', $search);
            }

            $db->group_end();
        }

        if ($movement === 'DEBIT') {
            $db->where('g.debit >', 0);
        } elseif ($movement === 'CREDIT') {
            $db->where('g.credit >', 0);
        }

        $rows = $db->order_by('g.posting_date', 'ASC')
            ->order_by('g.gl_id', 'ASC')
            ->get()
            ->result_array();

        $this->_enrich_ledger_rows($rows);

        $bal = $opening;
        foreach ($rows as &$r) {
            $r['debit']  = (float)($r['debit'] ?? 0);
            $r['credit'] = (float)($r['credit'] ?? 0);
            $r['delta']  = (float)($r['delta'] ?? ($r['debit'] - $r['credit']));

            if ($accountId) {
                $bal += $r['delta'];
                $r['balance'] = $bal;
            }
        }
        unset($r);

        return [
            'account' => $accountId ? [
                'account_id' => $accountId,
                'label'      => $acc ? $acc['account_code'] . ' - ' . $acc['account_name'] : '',
                'root_type'  => $acc['root_type'] ?? null,
            ] : null,
            'date_from' => $dateFrom,
            'date_to'   => $dateTo,
            'opening'   => $opening,
            'closing'   => $accountId ? $bal : null,
            'rows'      => $rows,
        ];
    }

    public function cash_ledger($p)
    {
        $db = $this->_erpdb();

        $accId = (int)($p['account_id'] ?? 0);
        if ($accId <= 0) {
            return ['opening' => 0, 'rows' => [], 'account' => null];
        }

        $dateFrom       = !empty($p['date_from']) ? $this->_normalize_date($p['date_from']) : date('Y-m-01');
        $dateTo         = !empty($p['date_to'])   ? $this->_normalize_date($p['date_to'])   : date('Y-m-d');
        $search         = trim((string)($p['search'] ?? ''));
        $movement       = strtoupper(trim((string)($p['movement'] ?? 'ALL')));
        $includeOpening = (int)($p['include_opening'] ?? 1);

        $acc = $this->_get_account_by_id_or_code((string)$accId);

        $opening = 0.0;
        if ($includeOpening) {
            $db->reset_query();
            $db->select('COALESCE(SUM(g.debit),0) AS dr, COALESCE(SUM(g.credit),0) AS cr', false)
                ->from('gl_entries g')
                ->where('g.company_id', $this->company_id)
                ->where('g.account_id', $accId)
                ->where('g.status', 'POSTED')
                ->where('g.posting_date <', $dateFrom);

            $this->_apply_region_filter('gl_entries', 'g');

            $op = $db->get()->row_array();
            $opening = (float)($op['dr'] ?? 0) - (float)($op['cr'] ?? 0);
        }

        $select = [
            'g.gl_id',
            'g.posting_date',
            'g.voucher_type',
            'g.voucher_no',
            'g.debit',
            'g.credit',
            '(g.debit - g.credit) AS delta',
        ];

        if ($this->_field_exists('remarks', 'gl_entries')) {
            $select[] = 'g.remarks';
        } elseif ($this->_field_exists('memo', 'gl_entries')) {
            $select[] = 'g.memo AS remarks';
        } else {
            $select[] = "'' AS remarks";
        }

        $db->reset_query();
        $db->select(implode(",\n", $select), false)
            ->from('gl_entries g')
            ->where('g.company_id', $this->company_id)
            ->where('g.account_id', $accId)
            ->where('g.status', 'POSTED')
            ->where('g.posting_date >=', $dateFrom)
            ->where('g.posting_date <=', $dateTo);

        $this->_apply_region_filter('gl_entries', 'g');

        if ($search !== '') {
            $db->group_start()
                ->like('g.voucher_no', $search)
                ->or_like('g.voucher_type', $search);

            if ($this->_field_exists('remarks', 'gl_entries')) {
                $db->or_like('g.remarks', $search);
            } elseif ($this->_field_exists('memo', 'gl_entries')) {
                $db->or_like('g.memo', $search);
            }

            $db->group_end();
        }

        if ($movement === 'DEBIT') {
            $db->where('g.debit >', 0);
        } elseif ($movement === 'CREDIT') {
            $db->where('g.credit >', 0);
        }

        $db->order_by('g.posting_date', 'ASC');
        if ($this->_field_exists('created_at', 'gl_entries')) {
            $db->order_by('g.created_at', 'ASC');
        }
        $db->order_by('g.gl_id', 'ASC');

        $rows = $db->get()->result_array();

        $this->_enrich_ledger_rows($rows);

        $bal = $opening;
        foreach ($rows as &$r) {
            $r['debit']  = (float)($r['debit'] ?? 0);
            $r['credit'] = (float)($r['credit'] ?? 0);
            $r['delta']  = (float)($r['delta'] ?? ($r['debit'] - $r['credit']));
            $bal += $r['delta'];
            $r['balance'] = $bal;
        }
        unset($r);

        return [
            'account' => $acc ? [
                'account_id' => (int)$acc['account_id'],
                'label'      => $acc['account_code'] . ' - ' . $acc['account_name'],
                'root_type'  => $acc['root_type'] ?? null,
            ] : null,
            'date_from' => $dateFrom,
            'date_to'   => $dateTo,
            'opening'   => $opening,
            'closing'   => $bal,
            'rows'      => $rows,
        ];
    }

    public function voucher_detail($p)
    {
        $db = $this->_erpdb();

        $vt = strtoupper(trim((string)($p['voucher_type'] ?? '')));
        $vn = trim((string)($p['voucher_no'] ?? ''));

        if ($vt === '' || $vn === '') {
            return ['ok' => false, 'http' => 422, 'message' => 'voucher_type & voucher_no required'];
        }

        $select = [
            'g.gl_id',
            'g.posting_date',
            'g.account_id',
            'g.debit',
            'g.credit',
            'g.voucher_type',
            'g.voucher_no',
            'g.status',
        ];

        if ($this->_field_exists('remarks', 'gl_entries')) {
            $select[] = 'g.remarks';
        } elseif ($this->_field_exists('memo', 'gl_entries')) {
            $select[] = 'g.memo AS remarks';
        } else {
            $select[] = "'' AS remarks";
        }

        $db->reset_query();
        $gl = $db->select(implode(",\n", $select), false)
            ->from('gl_entries g')
            ->where('g.company_id', $this->company_id)
            ->where('g.voucher_type', $vt)
            ->where('g.voucher_no', $vn)
            ->where('g.status', 'POSTED')
            ->order_by('g.debit', 'DESC')
            ->order_by('g.gl_id', 'ASC')
            ->get()
            ->result_array();

        $accIds = [];
        foreach ($gl as $x) {
            $accIds[] = (int)($x['account_id'] ?? 0);
        }
        $accIds = array_values(array_unique(array_filter($accIds)));

        $accMap = [];
        if ($accIds) {
            $db->reset_query();
            $accs = $db->select('account_id, account_code, account_name')
                ->from('accounts')
                ->where('company_id', $this->company_id)
                ->where_in('account_id', $accIds)
                ->get()
                ->result_array();

            foreach ($accs as $a) {
                $accMap[(int)$a['account_id']] = $a['account_code'] . ' - ' . $a['account_name'];
            }
        }

        foreach ($gl as &$g) {
            $g['debit']         = (float)($g['debit'] ?? 0);
            $g['credit']        = (float)($g['credit'] ?? 0);
            $g['account_label'] = $accMap[(int)($g['account_id'] ?? 0)] ?? (string)($g['account_id'] ?? '');
        }
        unset($g);

        $doc = null;

        if ($vt === 'BILL') {
            $doc = $this->_voucher_doc_bill($vn);
        } elseif ($vt === 'VPAY') {
            $doc = $this->_voucher_doc_vpay($vn);
        } elseif ($vt === 'RCPT') {
            $doc = $this->_voucher_doc_rcpt($vn);
        } elseif ($vt === 'ARINV') {
            $doc = $this->_voucher_doc_arinv($vn);
        } elseif ($vt === 'JE') {
            $doc = $this->_voucher_doc_je($vn);
        }

        return [
            'ok'      => true,
            'message' => 'OK',
            'data'    => [
                'voucher_type' => $vt,
                'voucher_no'   => $vn,
                'doc'          => $doc,
                'gl'           => $gl,
            ],
        ];
    }

    /* =========================================================
     * Ledger enrichers
     * ========================================================= */

    private function _enrich_ledger_rows(&$rows)
    {
        if (empty($rows) || !is_array($rows)) {
            return;
        }

        $byType = [
            'BILL'  => [],
            'VPAY'  => [],
            'RCPT'  => [],
            'ARINV' => [],
            'JE'    => [],
        ];

        foreach ($rows as $r) {
            $vt = strtoupper(trim((string)($r['voucher_type'] ?? '')));
            $vn = trim((string)($r['voucher_no'] ?? ''));
            if ($vn === '') {
                continue;
            }
            if (!isset($byType[$vt])) {
                $byType[$vt] = [];
            }
            $byType[$vt][] = $vn;
        }

        foreach ($byType as $k => $v) {
            $byType[$k] = array_values(array_unique($v));
        }

        $billMap = $this->_bill_party_map($byType['BILL']);
        $vpayMap = $this->_vpay_party_map($byType['VPAY']);
        $rcptMap = $this->_rcpt_party_map($byType['RCPT']);
        $arinvMap = $this->_arinv_party_map($byType['ARINV']);
        $jeMap = $this->_je_party_map($byType['JE']);

        $billFxMap  = $this->_bill_fx_map($byType['BILL']);
        $arinvFxMap = $this->_arinv_fx_map($byType['ARINV']);

        foreach ($rows as &$r) {
            $vt = strtoupper(trim((string)($r['voucher_type'] ?? '')));
            $vn = trim((string)($r['voucher_no'] ?? ''));

            $party = '';
            if ($vt === 'BILL') {
                $party = $billMap[$vn] ?? '';
            } elseif ($vt === 'VPAY') {
                $party = $vpayMap[$vn] ?? '';
            } elseif ($vt === 'RCPT') {
                $party = $rcptMap[$vn] ?? '';
            } elseif ($vt === 'ARINV') {
                $party = $arinvMap[$vn] ?? '';
            } elseif ($vt === 'JE') {
                $party = $jeMap[$vn] ?? 'Journal Entry';
            }

            $r['party_name'] = $party !== '' ? $party : null;
            $r['voucher_label'] = ($party !== '') ? ($vn . ' • ' . $party) : $vn;

            $remarks = trim((string)($r['remarks'] ?? ''));
            if ($remarks !== '' && $party !== '') {
                $r['description_display'] = $remarks . ' - ' . $party;
            } elseif ($party !== '') {
                $r['description_display'] = $party;
            } else {
                $r['description_display'] = $remarks;
            }

            $r['fx'] = null;
            if ($vt === 'ARINV' && isset($arinvFxMap[$vn])) {
                $r['fx'] = $arinvFxMap[$vn];
            } elseif ($vt === 'BILL' && isset($billFxMap[$vn])) {
                $r['fx'] = $billFxMap[$vn];
            }
        }
        unset($r);
    }

    private function _bill_party_map($billNos)
    {
        $db = $this->_erpdb();
        $out = [];
        $billNos = $this->_clean_list($billNos);

        if (!$billNos || !$this->_table_exists('ap_bills')) {
            return $out;
        }

        $noCol = $this->_first_col('ap_bills', ['bill_no', 'ap_bill_no', 'voucher_no', 'name', 'number']);
        if (!$noCol) {
            return $out;
        }

        $partyExpr = $this->_coalesce_expr('ap_bills', 'b', [
            'vendor_name', 'supplier_name', 'vendor_code', 'supplier_code', 'vendor_id', 'paid_to'
        ], "''");

        $db->reset_query();
        $rows = $db->select("b.$noCol AS voucher_no, $partyExpr AS party_name", false)
            ->from('ap_bills b')
            ->where('b.company_id', $this->company_id)
            ->where_in("b.$noCol", $billNos)
            ->get()
            ->result_array();

        foreach ($rows as $r) {
            $out[(string)$r['voucher_no']] = trim((string)($r['party_name'] ?? ''));
        }

        return $out;
    }

    private function _vpay_party_map($payNos)
    {
        $db = $this->_erpdb();
        $out = [];
        $payNos = $this->_clean_list($payNos);

        if (!$payNos || !$this->_table_exists('ap_payments')) {
            return $out;
        }

        $payNoCol = $this->_first_col('ap_payments', ['pay_no', 'vpay_no', 'payment_no', 'voucher_no', 'name', 'number']);
        $payIdCol = $this->_first_col('ap_payments', ['pay_id', 'payment_id', 'id']);
        if (!$payNoCol || !$payIdCol) {
            return $out;
        }

        $headerPartyExpr = $this->_coalesce_expr('ap_payments', 'p', [
            'vendor_name', 'supplier_name', 'vendor_code', 'supplier_code', 'paid_to'
        ], "''");

        $db->reset_query();
        $pays = $db->select("p.$payIdCol AS pay_id, p.$payNoCol AS pay_no, $headerPartyExpr AS header_party", false)
            ->from('ap_payments p')
            ->where('p.company_id', $this->company_id)
            ->where_in("p.$payNoCol", $payNos)
            ->get()
            ->result_array();

        $payIdByNo = [];
        $payNoById = [];
        foreach ($pays as $p) {
            $pid = (int)$p['pay_id'];
            $no = (string)$p['pay_no'];
            $payIdByNo[$no] = $pid;
            $payNoById[$pid] = $no;
            if (trim((string)$p['header_party']) !== '') {
                $out[$no] = trim((string)$p['header_party']);
            }
        }

        $payIds = array_values(array_unique(array_values($payIdByNo)));
        if (!$payIds || !$this->_table_exists('ap_payment_refs') || !$this->_table_exists('ap_bills')) {
            return $out;
        }

        $refPayIdCol = $this->_first_col('ap_payment_refs', ['pay_id', 'payment_id']);
        $refBillIdCol = $this->_first_col('ap_payment_refs', ['bill_id', 'ap_bill_id']);
        $billIdCol = $this->_first_col('ap_bills', ['bill_id', 'ap_bill_id', 'id']);

        if (!$refPayIdCol || !$refBillIdCol || !$billIdCol) {
            return $out;
        }

        $vendorExpr = $this->_coalesce_expr('ap_bills', 'b', [
            'vendor_name', 'supplier_name', 'vendor_code', 'supplier_code', 'vendor_id'
        ], "''");

        $db->reset_query();
        $rows = $db->select("r.$refPayIdCol AS pay_id, GROUP_CONCAT(DISTINCT $vendorExpr ORDER BY $vendorExpr SEPARATOR ', ') AS vendors", false)
            ->from('ap_payment_refs r')
            ->join('ap_bills b', "b.$billIdCol = r.$refBillIdCol", 'left')
            ->where_in("r.$refPayIdCol", $payIds)
            ->group_by("r.$refPayIdCol")
            ->get()
            ->result_array();

        foreach ($rows as $r) {
            $pid = (int)$r['pay_id'];
            $no = $payNoById[$pid] ?? '';
            if ($no !== '' && trim((string)$r['vendors']) !== '') {
                $out[$no] = trim((string)$r['vendors']);
            }
        }

        return $out;
    }

    private function _rcpt_party_map($rcptNos)
    {
        $db = $this->_erpdb();
        $out = [];
        $rcptNos = $this->_clean_list($rcptNos);

        if (!$rcptNos || !$this->_table_exists('ar_receipts')) {
            return $out;
        }

        $rcptNoCol = $this->_first_col('ar_receipts', ['rcpt_no', 'receipt_no', 'voucher_no', 'name', 'number']);
        $rcptIdCol = $this->_first_col('ar_receipts', ['rcpt_id', 'receipt_id', 'id']);
        if (!$rcptNoCol || !$rcptIdCol) {
            return $out;
        }

        $headerPartyExpr = $this->_coalesce_expr('ar_receipts', 'r', [
            'customer_name', 'payer_name', 'received_from', 'customer_code', 'customer_id'
        ], "''");

        $db->reset_query();
        $rcpts = $db->select("r.$rcptIdCol AS rcpt_id, r.$rcptNoCol AS rcpt_no, $headerPartyExpr AS header_party", false)
            ->from('ar_receipts r')
            ->where('r.company_id', $this->company_id)
            ->where_in("r.$rcptNoCol", $rcptNos)
            ->get()
            ->result_array();

        $rcptNoById = [];
        $rcptIds = [];
        foreach ($rcpts as $r) {
            $rid = (int)$r['rcpt_id'];
            $no = (string)$r['rcpt_no'];
            $rcptNoById[$rid] = $no;
            $rcptIds[] = $rid;
            if (trim((string)$r['header_party']) !== '') {
                $out[$no] = trim((string)$r['header_party']);
            }
        }

        $rcptIds = array_values(array_unique(array_filter($rcptIds)));
        if (!$rcptIds || !$this->_table_exists('ar_receipt_refs') || !$this->_table_exists('ar_invoices')) {
            return $out;
        }

        $refRcptIdCol = $this->_first_col('ar_receipt_refs', ['rcpt_id', 'receipt_id']);
        if (!$refRcptIdCol) {
            return $out;
        }

        $join = $this->_ar_receipt_ref_invoice_join('rr', 'ai');
        if ($join === '') {
            return $out;
        }

        $customerExpr = $this->_ar_invoice_customer_expr('ai');

        $db->reset_query();
        $rows = $db->select("rr.$refRcptIdCol AS rcpt_id, GROUP_CONCAT(DISTINCT $customerExpr ORDER BY $customerExpr SEPARATOR ', ') AS customers", false)
            ->from('ar_receipt_refs rr')
            ->join('ar_invoices ai', $join, 'left')
            ->where_in("rr.$refRcptIdCol", $rcptIds)
            ->group_by("rr.$refRcptIdCol")
            ->get()
            ->result_array();

        foreach ($rows as $r) {
            $rid = (int)$r['rcpt_id'];
            $no = $rcptNoById[$rid] ?? '';
            if ($no !== '' && trim((string)$r['customers']) !== '') {
                $out[$no] = trim((string)$r['customers']);
            }
        }

        return $out;
    }

    private function _arinv_party_map($invoiceNos)
    {
        $db = $this->_erpdb();
        $out = [];
        $invoiceNos = $this->_clean_list($invoiceNos);

        if (!$invoiceNos || !$this->_table_exists('ar_invoices')) {
            return $out;
        }

        $noCol = $this->_first_col('ar_invoices', ['invoice_no', 'ar_invoice_no', 'voucher_no', 'name', 'number']);
        if (!$noCol) {
            return $out;
        }

        $customerExpr = $this->_ar_invoice_customer_expr('ai');

        $db->reset_query();
        $rows = $db->select("ai.$noCol AS invoice_no, $customerExpr AS customer_name", false)
            ->from('ar_invoices ai')
            ->where('ai.company_id', $this->company_id)
            ->where_in("ai.$noCol", $invoiceNos)
            ->get()
            ->result_array();

        foreach ($rows as $r) {
            $out[(string)$r['invoice_no']] = trim((string)($r['customer_name'] ?? ''));
        }

        return $out;
    }

    private function _je_party_map($jeNos)
    {
        $db = $this->_erpdb();
        $out = [];
        $jeNos = $this->_clean_list($jeNos);

        if (!$jeNos || !$this->_table_exists('journal_entries')) {
            return $out;
        }

        $noCol = $this->_first_col('journal_entries', ['je_no', 'journal_no', 'voucher_no', 'name', 'number']);
        if (!$noCol) {
            return $out;
        }

        $descExpr = $this->_coalesce_expr('journal_entries', 'j', ['remarks', 'memo', 'description', 'title'], "'Journal Entry'");

        $db->reset_query();
        $rows = $db->select("j.$noCol AS je_no, $descExpr AS description", false)
            ->from('journal_entries j')
            ->where_in("j.$noCol", $jeNos)
            ->get()
            ->result_array();

        foreach ($rows as $r) {
            $out[(string)$r['je_no']] = trim((string)($r['description'] ?? 'Journal Entry'));
        }

        return $out;
    }

    private function _arinv_fx_map($invoiceNos)
    {
        $db = $this->_erpdb();
        $out = [];
        $invoiceNos = $this->_clean_list($invoiceNos);

        if (!$invoiceNos || !$this->_table_exists('ar_invoices')) {
            return $out;
        }

        $noCol = $this->_first_col('ar_invoices', ['invoice_no', 'ar_invoice_no', 'voucher_no', 'name', 'number']);
        if (!$noCol || !$this->_field_exists('currency', 'ar_invoices')) {
            return $out;
        }

        $amountCol = $this->_first_col('ar_invoices', ['foreign_total', 'grand_total_foreign', 'total_foreign', 'grand_total', 'total', 'net_total']);
        $rateCol   = $this->_first_col('ar_invoices', ['posting_kurs', 'payment_rate', 'exchange_rate', 'kurs', 'rate']);

        if (!$amountCol) {
            return $out;
        }

        $select = "ai.$noCol AS invoice_no, ai.currency, ai.$amountCol AS amount";
        if ($rateCol) {
            $select .= ", ai.$rateCol AS rate";
        } else {
            $select .= ", NULL AS rate";
        }

        $db->reset_query();
        $rows = $db->select($select, false)
            ->from('ar_invoices ai')
            ->where('ai.company_id', $this->company_id)
            ->where_in("ai.$noCol", $invoiceNos)
            ->get()
            ->result_array();

        foreach ($rows as $r) {
            $cur = strtoupper(trim((string)($r['currency'] ?? '')));
            if ($cur !== 'USD') {
                continue;
            }
            $no = (string)$r['invoice_no'];
            $out[$no] = [
                'currency' => 'USD',
                'amount'   => (float)($r['amount'] ?? 0),
                'rate'     => $this->_num_or_null($r['rate'] ?? null),
            ];
        }

        return $out;
    }

    private function _bill_fx_map($billNos)
    {
        $db = $this->_erpdb();
        $out = [];
        $billNos = $this->_clean_list($billNos);

        if (!$billNos || !$this->_table_exists('ap_bills')) {
            return $out;
        }

        $noCol = $this->_first_col('ap_bills', ['bill_no', 'ap_bill_no', 'voucher_no', 'name', 'number']);
        if (!$noCol || !$this->_field_exists('currency', 'ap_bills')) {
            return $out;
        }

        $amountCol = $this->_first_col('ap_bills', ['foreign_total', 'grand_total_foreign', 'total_foreign', 'gross_total', 'grand_total', 'total', 'net_total']);
        $rateCol   = $this->_first_col('ap_bills', ['posting_kurs', 'payment_rate', 'exchange_rate', 'kurs', 'rate']);

        if (!$amountCol) {
            return $out;
        }

        $select = "b.$noCol AS bill_no, b.currency, b.$amountCol AS amount";
        if ($rateCol) {
            $select .= ", b.$rateCol AS rate";
        } else {
            $select .= ", NULL AS rate";
        }

        $db->reset_query();
        $rows = $db->select($select, false)
            ->from('ap_bills b')
            ->where('b.company_id', $this->company_id)
            ->where_in("b.$noCol", $billNos)
            ->get()
            ->result_array();

        foreach ($rows as $r) {
            $cur = strtoupper(trim((string)($r['currency'] ?? '')));
            if ($cur !== 'USD') {
                continue;
            }
            $no = (string)$r['bill_no'];
            $out[$no] = [
                'currency' => 'USD',
                'amount'   => (float)($r['amount'] ?? 0),
                'rate'     => $this->_num_or_null($r['rate'] ?? null),
            ];
        }

        return $out;
    }

    /* =========================================================
     * Voucher document detail
     * ========================================================= */

    private function _voucher_doc_bill($billNo)
    {
        $db = $this->_erpdb();
        if (!$this->_table_exists('ap_bills')) {
            return null;
        }

        $noCol = $this->_first_col('ap_bills', ['bill_no', 'ap_bill_no', 'voucher_no', 'name', 'number']);
        $idCol = $this->_first_col('ap_bills', ['bill_id', 'ap_bill_id', 'id']);
        if (!$noCol || !$idCol) {
            return null;
        }

        $db->reset_query();
        $b = $db->from('ap_bills')
            ->where('company_id', $this->company_id)
            ->where($noCol, $billNo)
            ->get()
            ->row_array();

        if (!$b) {
            return null;
        }

        $billId = (int)$b[$idCol];
        $items = [];

        if ($this->_table_exists('ap_bill_items')) {
            $itemBillIdCol = $this->_first_col('ap_bill_items', ['bill_id', 'ap_bill_id']);
            if ($itemBillIdCol) {
                $db->reset_query();
                $db->from('ap_bill_items')->where($itemBillIdCol, $billId);
                if ($this->_field_exists('line_no', 'ap_bill_items')) {
                    $db->order_by('line_no', 'ASC');
                } elseif ($this->_field_exists('id', 'ap_bill_items')) {
                    $db->order_by('id', 'ASC');
                }
                $items = $db->get()->result_array();
            }
        }

        foreach ($items as &$it) {
            $this->_cast_amount_fields($it);
        }
        unset($it);
        $this->_cast_amount_fields($b);

        return ['type' => 'BILL', 'header' => $b, 'items' => $items];
    }

    private function _voucher_doc_vpay($payNo)
    {
        $db = $this->_erpdb();
        if (!$this->_table_exists('ap_payments')) {
            return null;
        }

        $payNoCol = $this->_first_col('ap_payments', ['pay_no', 'vpay_no', 'payment_no', 'voucher_no', 'name', 'number']);
        $payIdCol = $this->_first_col('ap_payments', ['pay_id', 'payment_id', 'id']);
        if (!$payNoCol || !$payIdCol) {
            return null;
        }

        $db->reset_query();
        $pmt = $db->from('ap_payments')
            ->where('company_id', $this->company_id)
            ->where($payNoCol, $payNo)
            ->get()
            ->row_array();

        if (!$pmt) {
            return null;
        }

        $payId = (int)$pmt[$payIdCol];
        $refs = [];

        if ($this->_table_exists('ap_payment_refs')) {
            $refPayIdCol = $this->_first_col('ap_payment_refs', ['pay_id', 'payment_id']);
            if ($refPayIdCol) {
                $select = 'r.*';
                $join = '';

                if ($this->_table_exists('ap_bills')) {
                    $refBillIdCol = $this->_first_col('ap_payment_refs', ['bill_id', 'ap_bill_id']);
                    $billIdCol = $this->_first_col('ap_bills', ['bill_id', 'ap_bill_id', 'id']);
                    if ($refBillIdCol && $billIdCol) {
                        $select .= ', ' . $this->_coalesce_expr('ap_bills', 'b', ['vendor_name', 'supplier_name', 'vendor_code', 'vendor_id'], "''") . ' AS vendor_name';
                        $join = "b.$billIdCol = r.$refBillIdCol";
                    }
                }

                $db->reset_query();
                $db->select($select, false)->from('ap_payment_refs r')->where("r.$refPayIdCol", $payId);
                if ($join !== '') {
                    $db->join('ap_bills b', $join, 'left');
                }
                if ($this->_field_exists('ref_id', 'ap_payment_refs')) {
                    $db->order_by('r.ref_id', 'ASC');
                }
                $refs = $db->get()->result_array();
            }
        }

        foreach ($refs as &$r) {
            $this->_cast_amount_fields($r);
        }
        unset($r);
        $this->_cast_amount_fields($pmt);

        return ['type' => 'VPAY', 'header' => $pmt, 'refs' => $refs];
    }

    private function _voucher_doc_rcpt($rcptNo)
    {
        $db = $this->_erpdb();
        if (!$this->_table_exists('ar_receipts')) {
            return null;
        }

        $rcptNoCol = $this->_first_col('ar_receipts', ['rcpt_no', 'receipt_no', 'voucher_no', 'name', 'number']);
        $rcptIdCol = $this->_first_col('ar_receipts', ['rcpt_id', 'receipt_id', 'id']);
        if (!$rcptNoCol || !$rcptIdCol) {
            return null;
        }

        $db->reset_query();
        $rcpt = $db->from('ar_receipts')
            ->where('company_id', $this->company_id)
            ->where($rcptNoCol, $rcptNo)
            ->get()
            ->row_array();

        if (!$rcpt) {
            return null;
        }

        $rcptId = (int)$rcpt[$rcptIdCol];
        $refs = [];

        if ($this->_table_exists('ar_receipt_refs')) {
            $refRcptIdCol = $this->_first_col('ar_receipt_refs', ['rcpt_id', 'receipt_id']);
            if ($refRcptIdCol) {
                $select = 'rr.*';
                $join = '';

                if ($this->_table_exists('ar_invoices')) {
                    $join = $this->_ar_receipt_ref_invoice_join('rr', 'ai');
                    if ($join !== '') {
                        $noCol = $this->_first_col('ar_invoices', ['invoice_no', 'ar_invoice_no', 'voucher_no', 'name', 'number']);
                        $customerExpr = $this->_ar_invoice_customer_expr('ai');
                        if ($noCol) {
                            // Do not overwrite rr.invoice_no.
                            $select .= ", ai.$noCol AS ar_invoice_no_resolved";
                        }
                        // Do not alias as customer_name, because rr.customer_name may already exist and be empty.
                        $select .= ", $customerExpr AS invoice_customer_name";
                    }
                }

                $db->reset_query();
                $db->select($select, false)->from('ar_receipt_refs rr')->where("rr.$refRcptIdCol", $rcptId);
                if ($join !== '') {
                    $db->join('ar_invoices ai', $join, 'left');
                }
                if ($this->_field_exists('ref_id', 'ar_receipt_refs')) {
                    $db->order_by('rr.ref_id', 'ASC');
                }
                $refs = $db->get()->result_array();
            }
        }

        foreach ($refs as &$r) {
            $existingCustomer = trim((string)($r['customer_name'] ?? ''));
            $invoiceCustomer  = trim((string)($r['invoice_customer_name'] ?? ''));

            if ($existingCustomer === '' && $invoiceCustomer !== '') {
                $r['customer_name'] = $invoiceCustomer;
            }

            if (!empty($r['ar_invoice_no_resolved']) && empty($r['invoice_no'])) {
                $r['invoice_no'] = $r['ar_invoice_no_resolved'];
            }

            unset($r['invoice_customer_name'], $r['ar_invoice_no_resolved']);
            $this->_cast_amount_fields($r);
        }
        unset($r);

        $set = [];
        foreach ($refs as $r) {
            $nm = trim((string)($r['customer_name'] ?? ''));
            if ($nm !== '') {
                $set[$nm] = true;
            }
        }
        $rcpt['customer_names'] = implode(', ', array_keys($set));
        $this->_cast_amount_fields($rcpt);

        return ['type' => 'RCPT', 'header' => $rcpt, 'refs' => $refs];
    }


    private function _voucher_doc_arinv($invoiceNo)
    {
        $db = $this->_erpdb();
        if (!$this->_table_exists('ar_invoices')) {
            return null;
        }

        $noCol = $this->_first_col('ar_invoices', ['invoice_no', 'ar_invoice_no', 'voucher_no', 'name', 'number']);
        $idCol = $this->_first_col('ar_invoices', ['invoice_id', 'ar_invoice_id', 'id']);
        if (!$noCol || !$idCol) {
            return null;
        }

        $db->reset_query();
        $inv = $db->from('ar_invoices')
            ->where('company_id', $this->company_id)
            ->where($noCol, $invoiceNo)
            ->get()
            ->row_array();

        if (!$inv) {
            return null;
        }

        $invoiceId = (int)$inv[$idCol];
        $items = [];

        if ($this->_table_exists('ar_invoice_items')) {
            $itemInvIdCol = $this->_first_col('ar_invoice_items', ['invoice_id', 'ar_invoice_id']);
            if ($itemInvIdCol) {
                $db->reset_query();
                $db->from('ar_invoice_items')->where($itemInvIdCol, $invoiceId);
                if ($this->_field_exists('line_no', 'ar_invoice_items')) {
                    $db->order_by('line_no', 'ASC');
                } elseif ($this->_field_exists('id', 'ar_invoice_items')) {
                    $db->order_by('id', 'ASC');
                }
                $items = $db->get()->result_array();
            }
        }

        foreach ($items as &$it) {
            $this->_cast_amount_fields($it);
        }
        unset($it);

        $inv['customer_name'] = $this->_extract_customer_from_invoice_row($inv);
        $this->_cast_amount_fields($inv);

        return ['type' => 'ARINV', 'header' => $inv, 'items' => $items];
    }

    private function _voucher_doc_je($jeNo)
    {
        $db = $this->_erpdb();
        if (!$this->_table_exists('journal_entries')) {
            return ['type' => 'JE', 'header' => ['voucher_no' => $jeNo], 'items' => []];
        }

        $noCol = $this->_first_col('journal_entries', ['je_no', 'journal_no', 'voucher_no', 'name', 'number']);
        if (!$noCol) {
            return ['type' => 'JE', 'header' => ['voucher_no' => $jeNo], 'items' => []];
        }

        $db->reset_query();
        $header = $db->from('journal_entries')
            ->where($noCol, $jeNo)
            ->get()
            ->row_array();

        return ['type' => 'JE', 'header' => $header ?: ['voucher_no' => $jeNo], 'items' => []];
    }

    /* =========================================================
     * Generic helpers
     * ========================================================= */

    private function _erpdb()
    {
        return isset($this->erp) ? $this->erp : $this->db;
    }

    private function _get_account_by_id_or_code($raw)
    {
        $db = $this->_erpdb();
        $raw = trim((string)$raw);

        if ($raw === '' || !$this->_table_exists('accounts')) {
            return null;
        }

        $db->reset_query();
        $acc = $db->select('account_id, account_code, account_name, root_type')
            ->from('accounts')
            ->where('company_id', $this->company_id)
            ->where('account_id', (int)$raw)
            ->get()
            ->row_array();

        if ($acc) {
            return $acc;
        }

        $db->reset_query();
        return $db->select('account_id, account_code, account_name, root_type')
            ->from('accounts')
            ->where('company_id', $this->company_id)
            ->where('account_code', $raw)
            ->get()
            ->row_array();
    }

    private function _table_exists($table)
    {
        $db = $this->_erpdb();
        return $db->table_exists($table);
    }

    private function _field_exists($field, $table)
    {
        $db = $this->_erpdb();
        return $db->field_exists($field, $table);
    }

    private function _first_col($table, array $cols)
    {
        foreach ($cols as $col) {
            if ($this->_field_exists($col, $table)) {
                return $col;
            }
        }
        return null;
    }

    private function _clean_list($items)
    {
        if (!is_array($items)) {
            return [];
        }

        $out = [];
        foreach ($items as $x) {
            $x = trim((string)$x);
            if ($x !== '') {
                $out[] = $x;
            }
        }
        return array_values(array_unique($out));
    }

    private function _coalesce_expr($table, $alias, array $cols, $defaultSql = "''")
    {
        $parts = [];
        foreach ($cols as $col) {
            if ($this->_field_exists($col, $table)) {
                // PATCH_VER_20260701_RCPT_PARTY_NAME:
                // Treat empty strings as NULL so COALESCE can continue to party_name/party_code.
                $parts[] = "NULLIF(TRIM(" . $alias . "." . $col . "), '')";
            }
        }

        if (!$parts) {
            return $defaultSql;
        }

        $parts[] = $defaultSql;
        return 'COALESCE(' . implode(', ', $parts) . ')';
    }


    private function _ar_invoice_customer_expr($alias = 'ai')
    {
        return $this->_coalesce_expr('ar_invoices', $alias, [
            // New ERP AR invoice structure stores bill-to/customer here.
            'party_name',
            'party_code',
            // Fallbacks for older/alternate structures.
            'bill_to_name',
            'customer_name',
            'bill_to',
            'customer_code',
            'customer_id',
            'shipper_name',
            'consignee_name',
        ], "''");
    }


    private function _extract_customer_from_invoice_row($row)
    {
        $cols = [
            'party_name',
            'party_code',
            'bill_to_name',
            'customer_name',
            'bill_to',
            'customer_code',
            'customer_id',
            'shipper_name',
            'consignee_name',
        ];

        foreach ($cols as $col) {
            if (isset($row[$col]) && trim((string)$row[$col]) !== '') {
                return trim((string)$row[$col]);
            }
        }

        return '';
    }


    private function _ar_receipt_ref_invoice_join($refAlias, $invoiceAlias)
    {
        $conds = [];

        // New ERP migration relation:
        // ar_receipt_refs.finance_id stores the original operational finance id,
        // ar_invoices.source_id stores the same id as varchar.
        if ($this->_field_exists('finance_id', 'ar_receipt_refs')) {
            if ($this->_field_exists('source_id', 'ar_invoices')) {
                $conds[] = $invoiceAlias . ".source_id = CAST(" . $refAlias . ".finance_id AS CHAR)";
            }
            if ($this->_field_exists('source_ref_id', 'ar_invoices')) {
                $conds[] = $invoiceAlias . ".source_ref_id = CAST(" . $refAlias . ".finance_id AS CHAR)";
            }
            if ($this->_field_exists('finance_id', 'ar_invoices')) {
                $conds[] = $invoiceAlias . ".finance_id = " . $refAlias . ".finance_id";
            }
        }

        $refInvoiceIdCol = $this->_first_col('ar_receipt_refs', ['invoice_id', 'ar_invoice_id']);
        $invIdCol        = $this->_first_col('ar_invoices', ['invoice_id', 'ar_invoice_id', 'id']);

        if ($refInvoiceIdCol && $invIdCol) {
            $conds[] = $invoiceAlias . '.' . $invIdCol . ' = ' . $refAlias . '.' . $refInvoiceIdCol;
        }

        $refInvoiceNoCol = $this->_first_col('ar_receipt_refs', ['invoice_no', 'ar_invoice_no', 'voucher_no']);
        if ($refInvoiceNoCol) {
            foreach (['invoice_no', 'ar_invoice_no', 'voucher_no', 'name', 'number', 'source_ref'] as $invNoCol) {
                if ($this->_field_exists($invNoCol, 'ar_invoices')) {
                    $conds[] = $invoiceAlias . '.' . $invNoCol . ' = ' . $refAlias . '.' . $refInvoiceNoCol;
                }
            }
        }

        $conds = array_values(array_unique($conds));
        if (!$conds) {
            return '';
        }

        return '(' . implode(' OR ', $conds) . ')';
    }


    private function _apply_region_filter($table, $alias = '')
    {
        $db = $this->_erpdb();
        $regionId = $this->_auth_region_value();

        if ($regionId === '' || !$this->_field_exists('region_id', $table)) {
            return;
        }

        $field = $alias !== '' ? ($alias . '.region_id') : 'region_id';
        $db->where($field, $regionId);
    }

    private function _auth_region_value()
    {
        if (method_exists($this, 'auth_region')) {
            return trim((string)$this->auth_region());
        }

        if (isset($this->session)) {
            $region = $this->session->userdata('region_id');
            if ($region !== null && $region !== '') {
                return trim((string)$region);
            }
        }

        return '';
    }

    private function _cast_amount_fields(&$row)
    {
        if (!is_array($row)) {
            return;
        }

        $cols = [
            'debit', 'credit', 'amount', 'allocated_amount', 'total', 'grand_total',
            'gross_total', 'net_total', 'paid_total', 'total_paid', 'cash_received',
            'deposit_used', 'posting_kurs', 'payment_rate', 'exchange_rate', 'kurs', 'rate',
        ];

        foreach ($cols as $col) {
            if (array_key_exists($col, $row) && $row[$col] !== null && $row[$col] !== '') {
                $row[$col] = (float)$row[$col];
            }
        }
    }

    private function _num_or_null($v)
    {
        if ($v === null || $v === '') {
            return null;
        }

        $n = (float)preg_replace('/[^0-9.\-]/', '', (string)$v);
        return $n > 0 ? $n : null;
    }

    private function _normalize_date($date)
    {
        $date = trim((string)$date);

        if ($date === '') {
            return date('Y-m-d');
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $date;
        }

        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $date)) {
            list($d, $m, $y) = explode('/', $date);
            return $y . '-' . $m . '-' . $d;
        }

        $ts = strtotime($date);
        return $ts ? date('Y-m-d', $ts) : date('Y-m-d');
    }
}
