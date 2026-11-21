<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_receipts extends MY_Model
{
    private $company_id = 1;

    /**
     * Recalculate payment state untuk ERP AR invoice.
     *
     * Relasi baru:
     * ar_receipt_refs.ar_invoice_id => ar_invoices.ar_invoice_id
     */
    private function recalc_invoice_payment_state($ar_invoice_id)
    {
        $ar_invoice_id = (int)$ar_invoice_id;
        if ($ar_invoice_id <= 0) return false;

        $inv = $this->erp->select('ar_invoice_id, invoice_no, grand_total')
            ->from('ar_invoices')
            ->where('company_id', $this->company_id)
            ->where('ar_invoice_id', $ar_invoice_id)
            ->get()
            ->row_array();

        if (!$inv) return false;

        $row = $this->erp->select('COALESCE(SUM(rr.allocated_amount),0) AS paid_total', false)
            ->from('ar_receipt_refs rr')
            ->join('ar_receipts r', 'r.rcpt_id = rr.rcpt_id', 'inner')
            ->where('rr.ar_invoice_id', $ar_invoice_id)
            ->where('r.company_id', $this->company_id)
            ->where('r.status', 'SUBMITTED')
            ->get()
            ->row_array();

        $paidTotal   = (float)($row['paid_total'] ?? 0);
        $grandTotal  = (float)($inv['grand_total'] ?? 0);
        $outstanding = max(0, $grandTotal - $paidTotal);

        $status = ($outstanding <= 0.01)
            ? 'paid'
            : (($paidTotal > 0.01) ? 'partial' : 'unpaid');

        $lastPay = $this->get_last_payment_yyyymmdd($ar_invoice_id, 0);

        $update = [
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->erp->field_exists('paid_total', 'ar_invoices')) {
            $update['paid_total'] = round($paidTotal, 2);
        }

        if ($this->erp->field_exists('outstanding', 'ar_invoices')) {
            $update['outstanding'] = round($outstanding, 2);
        }

        if ($this->erp->field_exists('status', 'ar_invoices')) {
            $update['status'] = $status;
        }

        if ($this->erp->field_exists('tgl_bayar', 'ar_invoices')) {
            $update['tgl_bayar'] = ($paidTotal > 0.01 ? $lastPay : null);
        }

        $this->erp->where('company_id', $this->company_id)
            ->where('ar_invoice_id', $ar_invoice_id)
            ->update('ar_invoices', $update);

        return [
            'ar_invoice_id' => $ar_invoice_id,
            'invoice_no'    => $inv['invoice_no'] ?? null,
            'paid_total'    => $paidTotal,
            'grand_total'   => $grandTotal,
            'outstanding'   => $outstanding,
            'status'        => $status,
        ];
    }

    /**
     * Ambil tanggal bayar terakhir dalam format YYYYMMDD.
     * Exclude receipt tertentu biasanya dipakai saat cancel.
     */
    private function get_last_payment_yyyymmdd($ar_invoice_id, $exclude_rcpt_id = 0)
    {
        $ar_invoice_id = (int)$ar_invoice_id;
        $exclude_rcpt_id = (int)$exclude_rcpt_id;

        $this->erp->select('MAX(r.posting_date) AS last_date', false)
            ->from('ar_receipt_refs rr')
            ->join('ar_receipts r', 'r.rcpt_id = rr.rcpt_id', 'inner')
            ->where('rr.ar_invoice_id', $ar_invoice_id)
            ->where('r.company_id', $this->company_id)
            ->where('r.status', 'SUBMITTED');

        if ($exclude_rcpt_id > 0) {
            $this->erp->where('rr.rcpt_id !=', $exclude_rcpt_id);
        }

        $row = $this->erp->get()->row_array();

        if (!$row || empty($row['last_date'])) return '';

        return date('Ymd', strtotime($row['last_date']));
    }

    /**
     * List receipt history untuk halaman /ar/receipts.
     */
    public function list($p)
{
    $this->erp->select("
        r.rcpt_id,
        r.company_id,
        r.rcpt_no,
        r.posting_date,
        r.currency,
        r.payment_rate,
        r.cash_account_id,
        r.total_paid,
        r.cash_received,
        r.deposit_used,
        r.adjustment_total,
        r.remark,
        r.status,
        r.region_id,
        r.created_at,
        r.cancelled_at,
        CONCAT(a.account_code, ' - ', a.account_name) AS cash_account_label
    ", false)
        ->from('ar_receipts r')
        ->join(
            'accounts a',
            'a.account_id = r.cash_account_id AND a.company_id = r.company_id',
            'left'
        )
        ->where('r.company_id', $this->company_id);

    $this->apply_company_filter_alias('r', 'region_id');

    if (!empty($p['status'])) {
        $status = strtoupper(trim($p['status']));

        if ($status !== 'ALL') {
            $this->erp->where('r.status', $status);
        }
    }

    if (!empty($p['date_from'])) {
        $this->erp->where('r.posting_date >=', $p['date_from']);
    }

    if (!empty($p['date_to'])) {
        $this->erp->where('r.posting_date <=', $p['date_to']);
    }

    if (!empty($p['search'])) {
        $q = trim($p['search']);
        $like = $this->erp->escape('%' . $q . '%');

        $this->erp->group_start()
            ->like('r.rcpt_no', $q)
            ->or_like('r.remark', $q)
            ->or_like('r.currency', $q)
            ->or_where("EXISTS (
                SELECT 1
                FROM ar_receipt_refs rr_s
                LEFT JOIN ar_invoices ai_s
                    ON (
                        ai_s.ar_invoice_id = rr_s.ar_invoice_id
                        OR ai_s.invoice_no = rr_s.invoice_no
                    )
                WHERE rr_s.rcpt_id = r.rcpt_id
                  AND (
                        rr_s.invoice_no LIKE {$like}
                     OR ai_s.invoice_no LIKE {$like}
                     OR ai_s.party_name LIKE {$like}
                     OR ai_s.party_code LIKE {$like}
                     OR ai_s.job_no LIKE {$like}
                  )
            )", null, false)
        ->group_end();
    }

    $this->erp
        ->order_by('r.posting_date', 'DESC')
        ->order_by('r.rcpt_id', 'DESC');

    $rows = $this->erp->get()->result_array();

    if (!$rows) {
        return [];
    }

    $rcptIds = array_values(array_unique(array_map(function ($r) {
        return (int)($r['rcpt_id'] ?? 0);
    }, $rows)));

    $mapCustomers = [];
    $mapInvoices  = [];
    $mapRefs      = [];

    if (!empty($rcptIds)) {
        /*
         * Ambil customer dari ar_invoices, bukan dari ar_receipts.
         * ar_receipts memang tidak punya customer_name.
         *
         * Join utama pakai ar_invoice_id.
         * Fallback pakai invoice_no untuk data lama yang ar_invoice_id kosong.
         */
        $refs = $this->erp->select("
            rr.ref_id,
            rr.rcpt_id,
            rr.ar_invoice_id,
            rr.finance_id,
            rr.source_id,
            rr.invoice_no AS ref_invoice_no,
            rr.currency AS ref_currency,
            rr.payment_rate,
            rr.allocated_amount,

            ai.ar_invoice_id AS ai_ar_invoice_id,
            ai.invoice_no AS ar_invoice_no,
            ai.source_id AS ai_source_id,
            ai.job_no,
            ai.party_code,
            ai.party_name,
            ai.currency AS invoice_currency,
            ai.grand_total,
            ai.paid_total,
            ai.status AS invoice_status
        ", false)
            ->from('ar_receipt_refs rr')
            ->join(
                'ar_invoices ai',
                '(
                    ai.ar_invoice_id = rr.ar_invoice_id
                    OR (
                        (rr.ar_invoice_id IS NULL OR rr.ar_invoice_id = 0)
                        AND ai.invoice_no = rr.invoice_no
                    )
                )',
                'left',
                false
            )
            ->where_in('rr.rcpt_id', $rcptIds)
            ->order_by('rr.rcpt_id', 'ASC')
            ->order_by('rr.ref_id', 'ASC')
            ->get()
            ->result_array();

        foreach ($refs as $x) {
            $rid = (int)($x['rcpt_id'] ?? 0);
            if ($rid <= 0) {
                continue;
            }

            $invoiceNo = trim((string)(
                $x['ar_invoice_no']
                ?? $x['ref_invoice_no']
                ?? ''
            ));

            $partyName = trim((string)($x['party_name'] ?? ''));
            $partyCode = trim((string)($x['party_code'] ?? ''));

            /*
             * Fallback urutan:
             * 1. party_name dari ar_invoices
             * 2. party_code dari ar_invoices
             * 3. invoice_no supaya table tidak kosong
             */
            $customerLabel = $partyName !== ''
                ? $partyName
                : ($partyCode !== '' ? $partyCode : $invoiceNo);

            if ($invoiceNo !== '') {
                if (!isset($mapInvoices[$rid])) {
                    $mapInvoices[$rid] = [];
                }
                $mapInvoices[$rid][$invoiceNo] = $invoiceNo;
            }

            if ($customerLabel !== '') {
                if (!isset($mapCustomers[$rid])) {
                    $mapCustomers[$rid] = [];
                }
                $mapCustomers[$rid][$customerLabel] = $customerLabel;
            }

            if (!isset($mapRefs[$rid])) {
                $mapRefs[$rid] = [];
            }

            $mapRefs[$rid][] = [
                'ref_id'           => (int)($x['ref_id'] ?? 0),
                'rcpt_id'          => $rid,
                'ar_invoice_id'    => (int)($x['ar_invoice_id'] ?? $x['ai_ar_invoice_id'] ?? 0),
                'finance_id'       => (int)($x['finance_id'] ?? 0),
                'source_id'        => $x['source_id'] ?? $x['ai_source_id'] ?? null,
                'invoice_no'       => $invoiceNo,
                'job_no'           => $x['job_no'] ?? null,
                'party_code'       => $partyCode,
                'party_name'       => $partyName,
                'customer_name'    => $customerLabel,
                'currency'         => $x['ref_currency'] ?: ($x['invoice_currency'] ?? null),
                'payment_rate'     => (float)($x['payment_rate'] ?? 1),
                'allocated_amount' => (float)($x['allocated_amount'] ?? 0),
                'grand_total'      => isset($x['grand_total']) ? (float)$x['grand_total'] : 0,
                'paid_total'       => isset($x['paid_total']) ? (float)$x['paid_total'] : 0,
                'invoice_status'   => $x['invoice_status'] ?? null,
            ];
        }

        foreach ($mapCustomers as $rid => $set) {
            $mapCustomers[$rid] = implode(', ', array_values($set));
        }

        foreach ($mapInvoices as $rid => $set) {
            $mapInvoices[$rid] = implode(', ', array_values($set));
        }
    }

    foreach ($rows as &$r) {
        $rid = (int)($r['rcpt_id'] ?? 0);

        $r['customers']     = $mapCustomers[$rid] ?? '-';
        $r['customer_name'] = $mapCustomers[$rid] ?? '-';
        $r['party_name']    = $mapCustomers[$rid] ?? '-';
        $r['invoice_nos']   = $mapInvoices[$rid] ?? '';
        $r['refs']          = $mapRefs[$rid] ?? [];

        $r['payment_rate']     = (float)($r['payment_rate'] ?? 1);
        $r['total_paid']       = (float)($r['total_paid'] ?? 0);
        $r['cash_received']    = (float)($r['cash_received'] ?? 0);
        $r['deposit_used']     = (float)($r['deposit_used'] ?? 0);
        $r['adjustment_total'] = (float)($r['adjustment_total'] ?? 0);

        /*
         * Field khusus summary.
         * Receipt CANCELLED tetap tampil di table dengan nilai aslinya,
         * tapi tidak ikut dihitung summary card.
         */
        $status = strtoupper((string)($r['status'] ?? ''));
        $isActive = in_array($status, ['SUBMITTED', 'POSTED'], true);

        $r['is_active_for_summary'] = $isActive ? 1 : 0;

        $r['summary_total_paid']       = $isActive ? $r['total_paid'] : 0;
        $r['summary_cash_received']    = $isActive ? $r['cash_received'] : 0;
        $r['summary_deposit_used']     = $isActive ? $r['deposit_used'] : 0;
        $r['summary_adjustment_total'] = $isActive ? $r['adjustment_total'] : 0;
    }
    unset($r);

    return $rows;
}
    /**
     * Detail receipt untuk expandable row di AR Receipt History.
     */
    public function get($rcpt_id)
{
    $rcpt_id = (int)$rcpt_id;
    if ($rcpt_id <= 0) {
        return null;
    }

    $hdr = $this->erp->select("
        r.*,
        CONCAT(a.account_code, ' - ', a.account_name) AS cash_account_label
    ", false)
        ->from('ar_receipts r')
        ->join(
            'accounts a',
            'a.account_id = r.cash_account_id AND a.company_id = r.company_id',
            'left'
        )
        ->where('r.company_id', $this->company_id)
        ->where('r.rcpt_id', $rcpt_id)
        ->get()
        ->row_array();

    if (!$hdr) {
        return null;
    }

    $authRegion = $this->auth_region();
    if (!empty($authRegion) && isset($hdr['region_id']) && $hdr['region_id'] !== $authRegion) {
        return null;
    }

    /*
     * Detail refs.
     * Customer diambil dari ar_invoices.party_name.
     * Join fallback invoice_no untuk data lama.
     */
    $refs = $this->erp->select("
        rr.ref_id,
        rr.rcpt_id,
        rr.ar_invoice_id,
        rr.finance_id,
        rr.source_id,
        rr.invoice_no AS ref_invoice_no,
        rr.currency AS ref_currency,
        rr.payment_rate,
        rr.allocated_amount,

        ai.ar_invoice_id AS ai_ar_invoice_id,
        ai.invoice_no AS ar_invoice_no,
        ai.source_id AS ai_source_id,
        ai.party_code,
        ai.party_name,
        ai.job_no,
        ai.posting_date AS invoice_date,
        ai.currency AS invoice_currency,
        ai.exchange_rate,
        ai.grand_total,
        ai.grand_total_idr,
        ai.paid_total,
        ai.outstanding_total,
        ai.status AS invoice_status
    ", false)
        ->from('ar_receipt_refs rr')
        ->join(
            'ar_invoices ai',
            '(
                ai.ar_invoice_id = rr.ar_invoice_id
                OR (
                    (rr.ar_invoice_id IS NULL OR rr.ar_invoice_id = 0)
                    AND ai.invoice_no = rr.invoice_no
                )
            )',
            'left',
            false
        )
        ->where('rr.rcpt_id', $rcpt_id)
        ->order_by('rr.ref_id', 'ASC')
        ->get()
        ->result_array();

    $customerSet = [];
    $invoiceSet  = [];

    foreach ($refs as &$r) {
        $invoiceNo = trim((string)(
            $r['ar_invoice_no']
            ?? $r['ref_invoice_no']
            ?? ''
        ));

        $partyName = trim((string)($r['party_name'] ?? ''));
        $partyCode = trim((string)($r['party_code'] ?? ''));

        $customerLabel = $partyName !== ''
            ? $partyName
            : ($partyCode !== '' ? $partyCode : $invoiceNo);

        if (empty($r['invoice_no'])) {
            $r['invoice_no'] = $invoiceNo;
        }

        $r['ar_invoice_id'] = (int)($r['ar_invoice_id'] ?? $r['ai_ar_invoice_id'] ?? 0);
        $r['finance_id']    = (int)($r['finance_id'] ?? 0);

        $r['source_id'] = $r['source_id'] ?? $r['ai_source_id'] ?? null;

        $r['party_name']    = $partyName;
        $r['party_code']    = $partyCode;
        $r['customer_name'] = $customerLabel;

        $r['currency'] = $r['ref_currency'] ?: ($r['invoice_currency'] ?? $hdr['currency'] ?? 'IDR');

        $r['allocated_amount']  = (float)($r['allocated_amount'] ?? 0);
        $r['payment_rate']      = (float)($r['payment_rate'] ?? 1);
        $r['exchange_rate']     = (float)($r['exchange_rate'] ?? 1);
        $r['grand_total']       = isset($r['grand_total']) ? (float)$r['grand_total'] : 0;
        $r['grand_total_idr']   = isset($r['grand_total_idr']) ? (float)$r['grand_total_idr'] : 0;
        $r['paid_total']        = isset($r['paid_total']) ? (float)$r['paid_total'] : 0;
        $r['outstanding_total'] = isset($r['outstanding_total']) ? (float)$r['outstanding_total'] : 0;

        if ($customerLabel !== '') {
            $customerSet[$customerLabel] = $customerLabel;
        }

        if ($invoiceNo !== '') {
            $invoiceSet[$invoiceNo] = $invoiceNo;
        }
    }
    unset($r);

    $customers  = !empty($customerSet) ? implode(', ', array_values($customerSet)) : '-';
    $invoiceNos = !empty($invoiceSet) ? implode(', ', array_values($invoiceSet)) : '';

    $hdr['payment_rate']     = (float)($hdr['payment_rate'] ?? 1);
    $hdr['total_paid']       = (float)($hdr['total_paid'] ?? 0);
    $hdr['cash_received']    = (float)($hdr['cash_received'] ?? 0);
    $hdr['deposit_used']     = (float)($hdr['deposit_used'] ?? 0);
    $hdr['adjustment_total'] = (float)($hdr['adjustment_total'] ?? 0);

    $hdr['customers']     = $customers;
    $hdr['customer_name'] = $customers;
    $hdr['party_name']    = $customers;
    $hdr['invoice_nos']   = $invoiceNos;

    $hdr['refs']            = $refs;
    $hdr['adjustment_rows'] = $this->_get_receipt_adjustments($rcpt_id);
    $hdr['deposit_rows']    = $this->_get_receipt_deposits($rcpt_id, $hdr['rcpt_no']);

    return $hdr;
}
    /**
     * Cancel receipt.
     */
    public function cancel($rcpt_id)
    {
        $rcpt_id = (int)$rcpt_id;

        $hdr = $this->erp->where('company_id', $this->company_id)
            ->where('region_id', $this->auth_region())
            ->where('rcpt_id', $rcpt_id)
            ->get('ar_receipts')
            ->row_array();

        if (!$hdr) {
            return ['ok' => false, 'http' => 404, 'message' => 'Receipt not found'];
        }

        if (strtoupper((string)$hdr['status']) === 'CANCELLED') {
            return ['ok' => false, 'http' => 409, 'message' => 'Receipt already cancelled'];
        }

        if ($this->_is_locked($hdr['posting_date'])) {
            return ['ok' => false, 'http' => 409, 'message' => 'PERIOD_LOCKED'];
        }

        $refs = $this->erp->get_where('ar_receipt_refs', [
            'rcpt_id' => $rcpt_id
        ])->result_array();

        if (!$refs) {
            return ['ok' => false, 'http' => 409, 'message' => 'Receipt has no refs'];
        }

        $rcpt_no = $hdr['rcpt_no'];

        $lock = $this->_get_reconciled_lock('RCPT', $rcpt_no);
        if ($lock) {
            return [
                'ok'      => false,
                'http'    => 409,
                'message' => 'CANNOT_CANCEL_RECONCILED',
                'data'    => ['lock' => $lock]
            ];
        }

        /**
         * Siapkan payload HNS berdasarkan kondisi SETELAH receipt ini dibatalkan.
         */
        $payload = [];

        foreach ($refs as $ref) {
            $ar_invoice_id = (int)($ref['ar_invoice_id'] ?? 0);
            if ($ar_invoice_id <= 0) continue;

            $inv = $this->erp->select('ar_invoice_id, source_id, invoice_no, grand_total')
                ->from('ar_invoices')
                ->where('company_id', $this->company_id)
                ->where('ar_invoice_id', $ar_invoice_id)
                ->get()
                ->row_array();

            if (!$inv) continue;

            $row = $this->erp->select('COALESCE(SUM(rr.allocated_amount),0) AS paid_total', false)
                ->from('ar_receipt_refs rr')
                ->join('ar_receipts ar', 'ar.rcpt_id = rr.rcpt_id', 'inner')
                ->where('rr.ar_invoice_id', $ar_invoice_id)
                ->where('ar.company_id', $this->company_id)
                ->where('ar.status', 'SUBMITTED')
                ->where('ar.rcpt_id !=', $rcpt_id)
                ->get()
                ->row_array();

            $paidAfter   = (float)($row['paid_total'] ?? 0);
            $grandTotal  = (float)($inv['grand_total'] ?? 0);
            $outstanding = max(0, $grandTotal - $paidAfter);

            $statusHns = ($outstanding <= 0.01)
                ? 'paid'
                : (($paidAfter > 0.01) ? 'partial' : 'unpaid');

            $payload[] = [
                'invoice_no'    => (string)$inv['invoice_no'],
                'status'        => $statusHns,
                'tanggal_bayar' => ($paidAfter > 0.01 ? $this->get_last_payment_yyyymmdd($ar_invoice_id, $rcpt_id) : ''),
                'overdue'       => $paidAfter,
            ];
        }

        $hns = $this->_post_status_invoice_hns($payload);
        if (!$hns['ok']) {
            return [
                'ok'      => false,
                'http'    => 502,
                'message' => 'XLT_UPDATE_FAILED',
                'data'    => ['hns' => $hns]
            ];
        }

        $this->erp->trans_begin();

        /**
         * 1. Cancel GL receipt.
         */
        $this->erp->where([
            'company_id'   => $this->company_id,
            'voucher_type' => 'RCPT',
            'voucher_no'   => $rcpt_no
        ])->update('gl_entries', [
            'status'     => 'CANCELLED',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        /**
         * 2. Restore deposit usage yang dipakai receipt ini.
         */
        $restore = $this->_restore_deposit_usage_for_receipt($rcpt_id, $rcpt_no);
        if (!$restore['ok']) {
            $this->erp->trans_rollback();
            return $restore;
        }

        /**
         * 3. Cancel deposit baru hasil overpayment receipt ini.
         */
        $cancelDep = $this->_cancel_created_deposit_for_receipt($rcpt_no);
        if (!$cancelDep['ok']) {
            $this->erp->trans_rollback();
            return $cancelDep;
        }

        /**
         * 4. Mark receipt cancelled.
         */
        $this->erp->where([
            'rcpt_id'    => $rcpt_id,
            'company_id' => $this->company_id
        ])->update('ar_receipts', [
            'status'       => 'CANCELLED',
            'cancelled_at' => date('Y-m-d H:i:s'),
            'cancelled_by' => null,
        ]);

        /**
         * 5. Recalculate semua invoice terdampak.
         */
        $invoiceIds = [];
        foreach ($refs as $ref) {
            $aid = (int)($ref['ar_invoice_id'] ?? 0);
            if ($aid > 0) $invoiceIds[$aid] = true;
        }

        foreach (array_keys($invoiceIds) as $ar_invoice_id) {
            $this->recalc_invoice_payment_state($ar_invoice_id);
        }

        if ($this->erp->trans_status() === false) {
            $this->erp->trans_rollback();
            return ['ok' => false, 'http' => 500, 'message' => 'DB error'];
        }

        $this->erp->trans_commit();

        return [
            'ok'      => true,
            'message' => 'Receipt cancelled',
            'data'    => [
                'rcpt_id' => $rcpt_id,
                'rcpt_no' => $rcpt_no,
                'hns'     => $hns,
            ]
        ];
    }

    /**
     * Ambil adjustment rows untuk receipt.
     * Support beberapa nama tabel agar aman dengan struktur sebelumnya.
     */
	private function _get_receipt_adjustments($rcpt_id)
{
    $rcpt_id = (int)$rcpt_id;
    if ($rcpt_id <= 0) return [];

    /*
     * Source utama detail adjustment sekarang:
     * ar_receipt_adjustments
     *
     * Jangan ambil dari gl_entries karena gl_entries hanya jurnal angka
     * dan tidak punya kolom remark/remarks.
     */
    if ($this->erp->table_exists('ar_receipt_adjustments')) {
        $rows = $this->erp->select("
                adj.adj_id,
                adj.company_id,
                adj.region_id,
                adj.rcpt_id,
                adj.account_id,
                adj.amount,
                adj.currency,
                adj.payment_rate,
                adj.amount_base,
                adj.remark,
                adj.status,
                adj.created_at,
                a.account_code,
                a.account_name,
                a.root_type
            ", false)
            ->from('ar_receipt_adjustments adj')
            ->join(
                'accounts a',
                'a.account_id = adj.account_id AND a.company_id = adj.company_id',
                'left'
            )
            ->where('adj.company_id', $this->company_id)
            ->where('adj.rcpt_id', $rcpt_id)
            ->where('adj.status', 'ACTIVE')
            ->order_by('adj.adj_id', 'ASC')
            ->get()
            ->result_array();

        foreach ($rows as &$r) {
            $r['adj_id']       = (int)($r['adj_id'] ?? 0);
            $r['rcpt_id']      = (int)($r['rcpt_id'] ?? 0);
            $r['account_id']   = (int)($r['account_id'] ?? 0);
            $r['amount']       = (float)($r['amount'] ?? 0);
            $r['amount_base']  = (float)($r['amount_base'] ?? 0);
            $r['payment_rate'] = (float)($r['payment_rate'] ?? 1);
        }
        unset($r);

        if ($rows) {
            return $rows;
        }
    }

    /*
     * Fallback untuk receipt lama:
     * Kalau header adjustment_total sudah ada,
     * tapi detail belum pernah disimpan ke ar_receipt_adjustments.
     */
    $hdr = $this->erp->select('rcpt_id, rcpt_no, adjustment_total, currency, payment_rate')
        ->from('ar_receipts')
        ->where('company_id', $this->company_id)
        ->where('rcpt_id', $rcpt_id)
        ->get()
        ->row_array();

    if (!$hdr) return [];

    $adjustmentTotal = (float)($hdr['adjustment_total'] ?? 0);

    if ($adjustmentTotal > 0.01) {
        return [
            [
                'adj_id'       => null,
                'company_id'   => $this->company_id,
                'region_id'    => null,
                'rcpt_id'      => $rcpt_id,
                'account_id'   => null,
                'account_code' => null,
                'account_name' => 'Adjustment',
                'root_type'    => null,
                'amount'       => $adjustmentTotal,
                'currency'     => $hdr['currency'] ?? null,
                'payment_rate' => (float)($hdr['payment_rate'] ?? 1),
                'amount_base'  => $adjustmentTotal,
                'remark'       => 'Detail adjustment belum tersimpan. Receipt lama hanya punya adjustment_total.',
                'status'       => 'LEGACY',
                'created_at'   => null,
            ]
        ];
    }

    return [];
}
 
    private function _get_receipt_deposits($rcpt_id, $rcpt_no)
    {
        $rcpt_id = (int)$rcpt_id;
        $rcpt_no = trim((string)$rcpt_no);

        if (!$this->erp->table_exists('customer_deposit_allocations')) {
            return [];
        }

        $this->erp->select("
            da.alloc_id,
            da.deposit_id,
            da.amount_doc,
            da.amount_idr,
            da.currency,
            da.applied_rate,
            da.status,
            da.apply_voucher_type,
            da.apply_voucher_no,
            da.apply_rcpt_id,
            d.party_type,
            d.party_code,
            d.party_name,
            d.source_voucher_type,
            d.source_voucher_no,
            d.balance_doc,
            d.balance_idr
        ", false)
            ->from('customer_deposit_allocations da')
            ->join('customer_deposits d', 'd.deposit_id = da.deposit_id AND d.company_id = da.company_id', 'left')
            ->where('da.company_id', $this->company_id)
            ->group_start()
                ->where('da.apply_rcpt_id', $rcpt_id);

        if ($rcpt_no !== '') {
            $this->erp->or_group_start()
                ->where('da.apply_voucher_type', 'RCPT')
                ->where('da.apply_voucher_no', $rcpt_no)
            ->group_end();
        }

        $this->erp->group_end();

        if ($this->erp->field_exists('status', 'customer_deposit_allocations')) {
            $this->erp->where("(da.status IS NULL OR da.status <> 'CANCELLED')", null, false);
        }

        $rows = $this->erp->order_by('da.alloc_id', 'ASC')
            ->get()
            ->result_array();

        foreach ($rows as &$r) {
            $r['amount_doc']  = (float)($r['amount_doc'] ?? 0);
            $r['amount_idr']  = (float)($r['amount_idr'] ?? 0);
            $r['balance_doc'] = (float)($r['balance_doc'] ?? 0);
            $r['balance_idr'] = (float)($r['balance_idr'] ?? 0);
            $r['amount']      = $r['amount_doc'];
            $r['used_amount'] = $r['amount_doc'];
        }
        unset($r);

        return $rows;
    }

    /**
     * Period lock.
     */
    private function _is_locked($posting_date)
    {
        $row = $this->erp->select('MAX(lock_date) mx', false)
            ->get_where('accounting_locks', ['company_id' => $this->company_id])
            ->row_array();

        $mx = $row['mx'] ?? null;

        return ($mx && $posting_date <= $mx);
    }

    /**
     * Sync status invoice ke HNS / xunlaitong.
     */
    private function _post_status_invoice_hns($payloadArr)
    {
        if (!$payloadArr || !is_array($payloadArr)) {
            return [
                'ok'      => true,
                'skipped' => true,
                'reason'  => 'empty_payload'
            ];
        }

        $url = 'http://localhost/api/status_invoice';

        $headers = [
            'Content-Type: application/json',
            'x-api-key: gateway-fms'
        ];

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_POSTFIELDS     => json_encode($payloadArr),
            CURLOPT_CONNECTTIMEOUT => 7,
            CURLOPT_TIMEOUT        => 25,
        ]);

        $resp = curl_exec($ch);
        $err  = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        $ok = (($err === '' || $err === null) && ($code >= 200 && $code < 300));

        return [
            'ok'             => $ok,
            'http_code'      => $code,
            'error'          => $err ?: null,
            'response'       => $resp ?: null,
            'sent_count'     => count($payloadArr),
            'payload_sample' => array_slice($payloadArr, 0, 3),
        ];
    }

    private function _is_reconciled_voucher($voucher_type, $voucher_no)
    {
        $row = $this->erp->select('l.line_id', false)
            ->from('bank_statement_lines l')
            ->join('bank_statements s', 's.stmt_id = l.stmt_id', 'inner')
            ->where('s.company_id', $this->company_id)
            ->where('s.status', 'RECONCILED')
            ->where_in('l.match_status', ['MATCHED', 'RECONCILED'])
            ->where('l.matched_voucher_type', strtoupper($voucher_type))
            ->where('l.matched_voucher_no', $voucher_no)
            ->limit(1)
            ->get()
            ->row_array();

        return $row ? true : false;
    }

    private function _get_reconciled_lock($voucher_type, $voucher_no)
    {
        $voucher_type = strtoupper(trim((string)$voucher_type));
        $voucher_no   = trim((string)$voucher_no);

        if (!$voucher_type || !$voucher_no) return null;

        return $this->erp->select('s.stmt_id, s.period_month, s.statement_date, l.line_id', false)
            ->from('bank_statement_lines l')
            ->join('bank_statements s', 's.stmt_id = l.stmt_id', 'inner')
            ->where('s.company_id', $this->company_id)
            ->where('s.status', 'RECONCILED')
            ->where_in('l.match_status', ['MATCHED', 'RECONCILED'])
            ->where('l.matched_voucher_type', $voucher_type)
            ->where('l.matched_voucher_no', $voucher_no)
            ->limit(1)
            ->get()
            ->row_array();
    }

    /**
     * Restore customer deposit allocation saat receipt dicancel.
     */
    private function _restore_deposit_usage_for_receipt($rcpt_id, $rcpt_no)
    {
        if (!$this->erp->table_exists('customer_deposit_allocations')) {
            return ['ok' => true, 'skipped' => true, 'reason' => 'customer_deposit_allocations_not_exists'];
        }

        $this->erp->from('customer_deposit_allocations')
            ->where('company_id', $this->company_id)
            ->where('apply_voucher_type', 'RCPT')
            ->where('apply_voucher_no', $rcpt_no)
            ->where('apply_rcpt_id', (int)$rcpt_id)
            ->where('status', 'APPLIED');

        $allocs = $this->erp->get()->result_array();

        foreach ($allocs as $a) {
            $alloc_id   = (int)($a['alloc_id'] ?? 0);
            $deposit_id = (int)($a['deposit_id'] ?? 0);
            $amount_doc = (float)($a['amount_doc'] ?? 0);
            $amount_idr = (float)($a['amount_idr'] ?? 0);

            if ($alloc_id <= 0 || $deposit_id <= 0 || ($amount_doc <= 0 && $amount_idr <= 0)) {
                continue;
            }

            $lot = $this->erp->get_where('customer_deposits', [
                'company_id' => $this->company_id,
                'deposit_id' => $deposit_id,
            ])->row_array();

            if (!$lot) {
                return [
                    'ok'      => false,
                    'http'    => 500,
                    'message' => 'DEPOSIT_LOT_NOT_FOUND',
                    'data'    => [
                        'deposit_id' => $deposit_id,
                        'rcpt_no'    => $rcpt_no,
                        'alloc_id'   => $alloc_id,
                    ]
                ];
            }

            $newUsedDoc    = max(0, (float)$lot['used_doc'] - $amount_doc);
            $newBalanceDoc = (float)$lot['balance_doc'] + $amount_doc;
            $newUsedIdr    = max(0, (float)$lot['used_idr'] - $amount_idr);
            $newBalanceIdr = (float)$lot['balance_idr'] + $amount_idr;

            $newStatus = ($newBalanceDoc > 0.01) ? 'OPEN' : 'USED';

            $this->erp->where([
                'company_id' => $this->company_id,
                'deposit_id' => $deposit_id
            ])->update('customer_deposits', [
                'used_doc'    => round($newUsedDoc, 2),
                'balance_doc' => round($newBalanceDoc, 2),
                'used_idr'    => round($newUsedIdr, 2),
                'balance_idr' => round($newBalanceIdr, 2),
                'status'      => $newStatus,
                'updated_at'  => date('Y-m-d H:i:s'),
            ]);

            $this->erp->where([
                'company_id' => $this->company_id,
                'alloc_id'   => $alloc_id,
            ])->update('customer_deposit_allocations', [
                'status'       => 'CANCELLED',
                'cancelled_at' => date('Y-m-d H:i:s'),
                'cancelled_by' => null,
                'updated_at'   => date('Y-m-d H:i:s'),
            ]);
        }

        return ['ok' => true];
    }

    /**
     * Cancel deposit yang dibuat dari overpayment receipt ini.
     */
    private function _cancel_created_deposit_for_receipt($rcpt_no)
    {
        if (!$this->erp->table_exists('customer_deposits')) {
            return ['ok' => true, 'skipped' => true, 'reason' => 'customer_deposits_not_exists'];
        }

        $deps = $this->erp->from('customer_deposits')
            ->where('company_id', $this->company_id)
            ->where('source_voucher_type', 'RCPT')
            ->where('source_voucher_no', $rcpt_no)
            ->get()
            ->result_array();

        foreach ($deps as $d) {
            $deposit_id = (int)$d['deposit_id'];
            $used_doc   = (float)($d['used_doc'] ?? 0);

            if ($used_doc > 0.01) {
                return [
                    'ok'      => false,
                    'http'    => 409,
                    'message' => 'CANNOT_CANCEL_RECEIPT_DEPOSIT_ALREADY_USED',
                    'data'    => [
                        'rcpt_no'    => $rcpt_no,
                        'deposit_id' => $deposit_id,
                        'used_doc'   => $used_doc
                    ]
                ];
            }

            $this->erp->where([
                'company_id' => $this->company_id,
                'deposit_id' => $deposit_id
            ])->update('customer_deposits', [
                'status'       => 'CANCELLED',
                'balance_doc'  => 0,
                'balance_idr'  => 0,
                'cancelled_at' => date('Y-m-d H:i:s'),
                'cancelled_by' => null,
                'updated_at'   => date('Y-m-d H:i:s'),
            ]);
        }

        return ['ok' => true];
    }
}