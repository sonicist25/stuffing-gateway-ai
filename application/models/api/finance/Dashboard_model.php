<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard_model extends MY_Model
{
    /*
     * =========================================================
     * DB / AUTH HELPERS
     * =========================================================
     */

    private function db_table_exists($table)
    {
        return $this->db->table_exists($table);
    }

    private function db_column_exists($table, $column)
    {
        return $this->db->field_exists($column, $table);
    }

    private function table_exists($table)
    {
        return $this->erp->table_exists($table);
    }

    private function column_exists($table, $column)
    {
        return $this->erp->field_exists($column, $table);
    }

    private function num($value)
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_string($value)) {
            $value = str_replace([',', ' '], ['', ''], $value);
        }

        return is_numeric($value) ? (float)$value : 0.0;
    }

    private function safe_date($date, $fallback = null)
    {
        if (!$fallback) {
            $fallback = date('Y-m-d');
        }

        if (!$date) {
            return $fallback;
        }

        $ts = strtotime((string)$date);

        return $ts ? date('Y-m-d', $ts) : $fallback;
    }

    /**
     * Region dashboard SELALU berasal dari authenticated session/JWT.
     * Parameter region_id dari request sengaja tidak dijadikan sumber otoritatif.
     */
    private function active_region()
    {
        $region = '';

        if (method_exists($this, 'auth_region')) {
            $region = trim((string)$this->auth_region());
        }

        if ($region === '') {
            $region = trim((string)$this->session->userdata('region_id'));
        }

        if ($region === '') {
            $region = trim((string)$this->session->userdata('region'));
        }

        return $region;
    }

    /**
     * ERP dashboard wajib exact-region dan fail-closed.
     * Tidak ada fallback NULL/kosong dan tidak ada super-admin bypass.
     */
    private function apply_erp_region_filter($table, $region_id = '', $alias = '')
    {
        $region_id = $this->active_region();

        if ($region_id === '' || !$this->column_exists($table, 'region_id')) {
            $this->erp->where('1 = 0', null, false);
            return;
        }

        $col = $alias !== '' ? $alias . '.region_id' : 'region_id';
        $this->erp->where($col, $region_id);
    }

    /**
     * Operational DB hanya dipakai untuk data yang memang belum menjadi dokumen ERP,
     * misalnya uninvoiced selling price. Tetap exact-region dan fail-closed.
     */
    private function apply_db_region_filter($table, $region_id = '', $alias = '')
    {
        $region_id = $this->active_region();

        if ($region_id === '' || !$this->db_column_exists($table, 'region_id')) {
            $this->db->where('1 = 0', null, false);
            return;
        }

        $col = $alias !== '' ? $alias . '.region_id' : 'region_id';
        $this->db->where($col, $region_id);
    }

    /*
     * =========================================================
     * COMMON ERP EXPRESSIONS
     * =========================================================
     */

    private function ar_outstanding_doc_expr($alias = 'ai')
    {
        return "GREATEST(COALESCE({$alias}.grand_total,0) - COALESCE({$alias}.paid_total,0), 0)";
    }

    private function ar_outstanding_idr_expr($alias = 'ai')
    {
        $doc = $this->ar_outstanding_doc_expr($alias);

        return "(
            CASE
                WHEN UPPER(TRIM(COALESCE({$alias}.currency,'IDR'))) IN ('USD','US$','USD.')
                THEN ({$doc}) * COALESCE(NULLIF({$alias}.exchange_rate,0),1)
                ELSE ({$doc})
            END
        )";
    }

    private function ap_outstanding_doc_expr($alias = 'b')
    {
        return "GREATEST(COALESCE({$alias}.total,0) - COALESCE({$alias}.paid_total,0), 0)";
    }

    private function ap_rate_expr($alias = 'b')
    {
        if ($this->column_exists('ap_bills', 'posting_kurs')) {
            return "COALESCE(NULLIF({$alias}.posting_kurs,0),1)";
        }

        if ($this->column_exists('ap_bills', 'exchange_rate')) {
            return "COALESCE(NULLIF({$alias}.exchange_rate,0),1)";
        }

        if ($this->column_exists('ap_bills', 'kurs')) {
            return "COALESCE(NULLIF({$alias}.kurs,0),1)";
        }

        return '1';
    }

    private function ap_outstanding_idr_expr($alias = 'b')
    {
        $doc  = $this->ap_outstanding_doc_expr($alias);
        $rate = $this->ap_rate_expr($alias);

        if (!$this->column_exists('ap_bills', 'currency')) {
            return "({$doc})";
        }

        return "(
            CASE
                WHEN UPPER(TRIM(COALESCE({$alias}.currency,'IDR'))) IN ('USD','US$','USD.')
                THEN ({$doc}) * ({$rate})
                ELSE ({$doc})
            END
        )";
    }

    private function open_status_sql($alias)
    {
        return "UPPER(TRIM(COALESCE({$alias}.status,''))) NOT IN ('PAID','CANCELLED','VOID','REVISION','DRAFT')";
    }

    /*
     * =========================================================
     * GL HELPERS - ERP DB ($this->erp)
     * =========================================================
     */

    private function gl_balance_by_account_filter($company_id, $as_of, $where_sql, $region_id = '')
    {
        if (!$this->table_exists('gl_entries') || !$this->table_exists('accounts')) {
            return 0;
        }

        $this->erp->select(''
            . 'COALESCE(SUM('
            . 'CASE '
            . 'WHEN a.root_type IN ("Asset", "Expense") THEN ge.debit - ge.credit '
            . 'ELSE ge.credit - ge.debit END'
            . '),0) AS balance', false);

        $this->erp->from('gl_entries ge');
        $this->erp->join('accounts a', 'a.account_id = ge.account_id', 'inner');

        if ($this->column_exists('gl_entries', 'company_id')) {
            $this->erp->where('ge.company_id', (int)$company_id);
        }

        if ($this->column_exists('accounts', 'company_id')) {
            $this->erp->where('a.company_id', (int)$company_id);
        }

        $this->apply_erp_region_filter('gl_entries', $region_id, 'ge');
        $this->apply_erp_region_filter('accounts', $region_id, 'a');

        if ($this->column_exists('gl_entries', 'posting_date')) {
            $this->erp->where('ge.posting_date <=', $as_of);
        }

        if ($this->column_exists('gl_entries', 'status')) {
            $this->erp->where('ge.status', 'POSTED');
        }

        if ($this->column_exists('accounts', 'is_postable')) {
            $this->erp->where('a.is_postable', 1);
        }

        if ($this->column_exists('accounts', 'is_active')) {
            $this->erp->where('a.is_active', 1);
        }

        $this->erp->where($where_sql, null, false);

        $row = $this->erp->get()->row();

        return $row ? $this->num($row->balance) : 0;
    }

    private function gl_period_amount($company_id, $date_from, $date_to, $where_sql, $normal_side = 'credit', $region_id = '')
    {
        if (!$this->table_exists('gl_entries') || !$this->table_exists('accounts')) {
            return 0;
        }

        $amount_expr = strtolower($normal_side) === 'debit'
            ? '(ge.debit - ge.credit)'
            : '(ge.credit - ge.debit)';

        $this->erp->select("COALESCE(SUM({$amount_expr}),0) AS total_amount", false);
        $this->erp->from('gl_entries ge');
        $this->erp->join('accounts a', 'a.account_id = ge.account_id', 'inner');

        if ($this->column_exists('gl_entries', 'company_id')) {
            $this->erp->where('ge.company_id', (int)$company_id);
        }

        if ($this->column_exists('accounts', 'company_id')) {
            $this->erp->where('a.company_id', (int)$company_id);
        }

        $this->apply_erp_region_filter('gl_entries', $region_id, 'ge');
        $this->apply_erp_region_filter('accounts', $region_id, 'a');

        $this->erp->where('ge.posting_date >=', $date_from);
        $this->erp->where('ge.posting_date <=', $date_to);

        if ($this->column_exists('gl_entries', 'status')) {
            $this->erp->where('ge.status', 'POSTED');
        }

        if ($this->column_exists('accounts', 'is_postable')) {
            $this->erp->where('a.is_postable', 1);
        }

        if ($this->column_exists('accounts', 'is_active')) {
            $this->erp->where('a.is_active', 1);
        }

        $this->erp->where($where_sql, null, false);

        $row = $this->erp->get()->row();

        return $row ? $this->num($row->total_amount) : 0;
    }

    /*
     * =========================================================
     * SUMMARY - ERP SOURCE OF TRUTH
     * =========================================================
     */

    public function get_summary($p)
    {
        $company_id = (int)($p['company_id'] ?? 1);
        $region_id  = $this->active_region();
        $date_from  = $this->safe_date($p['date_from'] ?? null, date('Y-01-01'));
        $date_to    = $this->safe_date($p['date_to'] ?? null, date('Y-m-d'));

        if ($company_id <= 0) {
            $company_id = 1;
        }

        $cash_bank = $this->gl_balance_by_account_filter(
            $company_id,
            $date_to,
            "(
                a.account_code IN ('1120','1270','1271')
                OR LOWER(a.account_name) LIKE '%cash%'
                OR LOWER(a.account_name) LIKE '%bank%'
                OR LOWER(a.account_name) LIKE '%kas%'
                OR LOWER(a.account_name) LIKE '%petty%'
            )",
            $region_id
        );

        $ar = $this->get_ar_invoices_outstanding_total($company_id, $region_id);
        $ap = $this->get_ap_bills_outstanding_total($company_id, $region_id);

        // Revenue/HPP/Opex sudah diposting ke GL; dashboard tidak lagi membaca x_finance.
        $revenue = $this->gl_period_amount(
            $company_id,
            $date_from,
            $date_to,
            "a.account_code LIKE '41%'",
            'credit',
            $region_id
        );

        $expense = $this->gl_period_amount(
            $company_id,
            $date_from,
            $date_to,
            "a.account_code LIKE '5%'",
            'debit',
            $region_id
        );

        return [
            'period' => [
                'date_from' => $date_from,
                'date_to'   => $date_to,
            ],
            'cash_bank_balance'      => round($cash_bank, 2),
            'accounts_receivable'    => round($ar, 2),
            'accounts_payable'       => round($ap, 2),
            'revenue_this_period'    => round($revenue, 2),
            'expense_this_period'    => round($expense, 2),
            'net_profit_this_period' => round($revenue - $expense, 2),
        ];
    }

    private function get_ar_invoices_outstanding_total($company_id, $region_id = '')
    {
        if (!$this->table_exists('ar_invoices')) {
            return 0;
        }

        $outstanding_doc = $this->ar_outstanding_doc_expr('ai');
        $outstanding_idr = $this->ar_outstanding_idr_expr('ai');

        $this->erp->select("COALESCE(SUM({$outstanding_idr}),0) AS total_amount", false);
        $this->erp->from('ar_invoices ai');
        $this->erp->where('ai.company_id', (int)$company_id);
        $this->apply_erp_region_filter('ar_invoices', $region_id, 'ai');
        $this->erp->where($this->open_status_sql('ai'), null, false);
        $this->erp->where("{$outstanding_doc} > 0.01", null, false);

        $row = $this->erp->get()->row();

        return $row ? $this->num($row->total_amount) : 0;
    }

    private function get_ap_bills_outstanding_total($company_id, $region_id = '')
    {
        if (!$this->table_exists('ap_bills')) {
            return 0;
        }

        $outstanding_doc = $this->ap_outstanding_doc_expr('b');
        $outstanding_idr = $this->ap_outstanding_idr_expr('b');

        $this->erp->select("COALESCE(SUM({$outstanding_idr}),0) AS total_amount", false);
        $this->erp->from('ap_bills b');

        if ($this->column_exists('ap_bills', 'company_id')) {
            $this->erp->where('b.company_id', (int)$company_id);
        }

        $this->apply_erp_region_filter('ap_bills', $region_id, 'b');

        if ($this->column_exists('ap_bills', 'status')) {
            $this->erp->where("UPPER(TRIM(COALESCE(b.status,''))) NOT IN ('PAID','CANCELLED','VOID')", null, false);
        }

        $this->erp->where("{$outstanding_doc} > 0.01", null, false);

        $row = $this->erp->get()->row();

        return $row ? $this->num($row->total_amount) : 0;
    }

    /*
     * =========================================================
     * ATTENTION - ERP SOURCE OF TRUTH
     * =========================================================
     */

    public function get_attention($p)
    {
        $company_id = (int)($p['company_id'] ?? 1);
        $region_id  = $this->active_region();

        if ($company_id <= 0) {
            $company_id = 1;
        }

        return [
            'ap_bills_waiting_payment' => $this->count_ap_bills_waiting_payment($company_id, $region_id),
            'ar_invoices_overdue'      => $this->count_ar_invoices_overdue($company_id, $region_id),
            'cash_advances_open'       => $this->count_cash_advances_open($company_id, $region_id),
            'journal_entries_draft'    => $this->count_journal_entries_draft($company_id, $region_id),
            'sales_order_uninvoiced'   => $region_id !== '' ? $this->count_uninvoiced_orders($company_id, $region_id) : 0,
        ];
    }

    private function count_ap_bills_waiting_payment($company_id, $region_id = '')
    {
        if (!$this->table_exists('ap_bills')) {
            return 0;
        }

        $outstanding = $this->ap_outstanding_doc_expr('b');

        $this->erp->from('ap_bills b');

        if ($this->column_exists('ap_bills', 'company_id')) {
            $this->erp->where('b.company_id', (int)$company_id);
        }

        $this->apply_erp_region_filter('ap_bills', $region_id, 'b');

        if ($this->column_exists('ap_bills', 'status')) {
            $this->erp->where("UPPER(TRIM(COALESCE(b.status,''))) NOT IN ('PAID','CANCELLED','VOID')", null, false);
        }

        $this->erp->where("{$outstanding} > 0.01", null, false);

        return (int)$this->erp->count_all_results();
    }

    private function count_ar_invoices_overdue($company_id, $region_id = '')
    {
        if (!$this->table_exists('ar_invoices')) {
            return 0;
        }

        $today       = date('Y-m-d');
        $outstanding = $this->ar_outstanding_doc_expr('ai');

        $this->erp->from('ar_invoices ai');
        $this->erp->where('ai.company_id', (int)$company_id);
        $this->apply_erp_region_filter('ar_invoices', $region_id, 'ai');
        $this->erp->where($this->open_status_sql('ai'), null, false);
        $this->erp->where("{$outstanding} > 0.01", null, false);

        if ($this->column_exists('ar_invoices', 'due_date')) {
            $this->erp->where('ai.due_date <', $today);
        } else {
            // Schema saat ini tidak memiliki due_date: 0-30 hari dianggap current.
            $this->erp->where(
                'DATEDIFF(' . $this->erp->escape($today) . ', ai.posting_date) > 30',
                null,
                false
            );
        }

        return (int)$this->erp->count_all_results();
    }

    private function count_cash_advances_open($company_id, $region_id = '')
    {
        if (!$this->table_exists('cash_advances')) {
            return 0;
        }

        $this->erp->from('cash_advances');

        if ($this->column_exists('cash_advances', 'company_id')) {
            $this->erp->where('company_id', (int)$company_id);
        }

        $this->apply_erp_region_filter('cash_advances', $region_id);

        if ($this->column_exists('cash_advances', 'status')) {
            $this->erp->where_in('status', ['OPEN', 'SUBMITTED']);
        }

        return (int)$this->erp->count_all_results();
    }

    private function count_journal_entries_draft($company_id, $region_id = '')
    {
        if (!$this->table_exists('journal_entries')) {
            return 0;
        }

        $this->erp->from('journal_entries');

        if ($this->column_exists('journal_entries', 'company_id')) {
            $this->erp->where('company_id', (int)$company_id);
        }

        $this->apply_erp_region_filter('journal_entries', $region_id);

        if ($this->column_exists('journal_entries', 'status')) {
            $this->erp->where('status', 'DRAFT');
        }

        return (int)$this->erp->count_all_results();
    }

    /*
     * =========================================================
     * AR AGING - ERP ar_invoices
     * =========================================================
     */

    public function get_ar_aging($p)
    {
        $company_id = (int)($p['company_id'] ?? 1);
        $region_id  = $this->active_region();
        $asOf       = $this->safe_date($p['as_of'] ?? null);

        if ($company_id <= 0) {
            $company_id = 1;
        }

        if (!$this->table_exists('ar_invoices')) {
            return $this->empty_aging();
        }

        $outstanding_doc = $this->ar_outstanding_doc_expr('ai');
        $outstanding_idr = $this->ar_outstanding_idr_expr('ai');

        $this->erp->select("\n            ai.ar_invoice_id,\n            ai.invoice_no,\n            ai.job_no,\n            ai.posting_date,\n            ai.party_code,\n            ai.party_name,\n            ai.region_id,\n            ai.currency,\n            ai.exchange_rate,\n            ai.grand_total,\n            ai.paid_total,\n            ai.outstanding_total,\n            ai.status,\n            DATEDIFF(" . $this->erp->escape($asOf) . ", ai.posting_date) AS age_days,\n            {$outstanding_doc} AS outstanding_doc,\n            {$outstanding_idr} AS outstanding_idr\n        ", false);

        $this->erp->from('ar_invoices ai');
        $this->erp->where('ai.company_id', $company_id);
        $this->apply_erp_region_filter('ar_invoices', $region_id, 'ai');
        $this->erp->where('ai.posting_date <=', $asOf);

        // Raw SQL sengaja digunakan agar PAID/CANCELLED/VOID menjadi string literal,
        // bukan di-escape CI sebagai nama kolom.
        $this->erp->where($this->open_status_sql('ai'), null, false);
        $this->erp->where("{$outstanding_doc} > 0.01", null, false);

        $rows = $this->erp
            ->order_by('age_days', 'DESC')
            ->get()
            ->result_array();

        $bucket = [
            'current_amount' => 0.0,
            'd_31_60'        => 0.0,
            'd_over_60'      => 0.0,
        ];

        foreach ($rows as $r) {
            $age = max(0, (int)$r['age_days']);
            $amt = (float)$r['outstanding_idr'];

            if ($age <= 30) {
                $bucket['current_amount'] += $amt;
            } elseif ($age <= 60) {
                $bucket['d_31_60'] += $amt;
            } else {
                $bucket['d_over_60'] += $amt;
            }
        }

        return [
            ['bucket' => '0 - 30 Days',  'amount' => round($bucket['current_amount'], 2)],
            ['bucket' => '31 - 60 Days', 'amount' => round($bucket['d_31_60'], 2)],
            ['bucket' => '61+ Days',     'amount' => round($bucket['d_over_60'], 2)],
        ];
    }

    /*
     * =========================================================
     * AP AGING - ERP ap_bills
     * =========================================================
     */

    public function get_ap_aging($p)
    {
        $company_id = (int)($p['company_id'] ?? 1);
        $region_id  = $this->active_region();
        $asOf       = $this->safe_date($p['as_of'] ?? null);

        if ($company_id <= 0) {
            $company_id = 1;
        }

        if (!$this->table_exists('ap_bills')) {
            return $this->empty_aging();
        }

        $outstanding_doc = $this->ap_outstanding_doc_expr('b');
        $outstanding_idr = $this->ap_outstanding_idr_expr('b');

        $select = "\n            b.bill_id,\n            b.bill_no,\n            b.posting_date,\n            DATEDIFF(" . $this->erp->escape($asOf) . ", b.posting_date) AS age_days,\n            b.vendor_type,\n            b.vendor_code,\n            b.vendor_name,\n            b.total,\n            b.paid_total,\n            {$outstanding_doc} AS outstanding_doc,\n            {$outstanding_idr} AS outstanding_idr\n        ";

        if ($this->column_exists('ap_bills', 'currency')) {
            $select .= ', b.currency';
        }
        if ($this->column_exists('ap_bills', 'posting_kurs')) {
            $select .= ', b.posting_kurs';
        }

        $this->erp->select($select, false);
        $this->erp->from('ap_bills b');

        if ($this->column_exists('ap_bills', 'company_id')) {
            $this->erp->where('b.company_id', $company_id);
        }

        $this->apply_erp_region_filter('ap_bills', $region_id, 'b');
        $this->erp->where('b.posting_date <=', $asOf);

        if ($this->column_exists('ap_bills', 'status')) {
            $this->erp->where("UPPER(TRIM(COALESCE(b.status,''))) NOT IN ('PAID','CANCELLED','VOID')", null, false);
        }

        $this->erp->where("{$outstanding_doc} > 0.01", null, false);

        $rows = $this->erp
            ->order_by('age_days', 'DESC')
            ->get()
            ->result_array();

        $bucket = [
            'current_amount' => 0.0,
            'd_31_60'        => 0.0,
            'd_over_60'      => 0.0,
        ];

        foreach ($rows as $r) {
            $age = max(0, (int)$r['age_days']);
            $amt = (float)$r['outstanding_idr'];

            if ($age <= 30) {
                $bucket['current_amount'] += $amt;
            } elseif ($age <= 60) {
                $bucket['d_31_60'] += $amt;
            } else {
                $bucket['d_over_60'] += $amt;
            }
        }

        return [
            ['bucket' => '0 - 30 Days',  'amount' => round($bucket['current_amount'], 2)],
            ['bucket' => '31 - 60 Days', 'amount' => round($bucket['d_31_60'], 2)],
            ['bucket' => '61+ Days',     'amount' => round($bucket['d_over_60'], 2)],
        ];
    }

    private function empty_aging()
    {
        return [
            ['bucket' => '0 - 30 Days',  'amount' => 0],
            ['bucket' => '31 - 60 Days', 'amount' => 0],
            ['bucket' => '61+ Days',     'amount' => 0],
        ];
    }

    /*
     * =========================================================
     * RECENT ACTIVITY - ERP SOURCE OF TRUTH
     * =========================================================
     */

    public function get_recent_activity($p)
    {
        $company_id = (int)($p['company_id'] ?? 1);
        $region_id  = $this->active_region();
        $limit      = (int)($p['limit'] ?? 15);

        if ($company_id <= 0) {
            $company_id = 1;
        }

        if ($limit <= 0) {
            $limit = 15;
        }
        if ($limit > 50) {
            $limit = 50;
        }

        $rows = [];
        $rows = array_merge($rows, $this->recent_from_ar_invoices($company_id, $limit, $region_id));
        $rows = array_merge($rows, $this->recent_from_gl($company_id, $limit, $region_id));
        $rows = array_merge($rows, $this->recent_from_ar_receipts($company_id, $limit, $region_id));
        $rows = array_merge($rows, $this->recent_from_ap_payments($company_id, $limit, $region_id));
        $rows = array_merge($rows, $this->recent_from_ap_bills($company_id, $limit, $region_id));
        $rows = array_merge($rows, $this->recent_from_cash_advances($company_id, $limit, $region_id));

        usort($rows, function ($a, $b) {
            return strtotime((string)$b['activity_date']) - strtotime((string)$a['activity_date']);
        });

        return array_slice($rows, 0, $limit);
    }

    private function recent_from_ar_invoices($company_id, $limit, $region_id = '')
    {
        if (!$this->table_exists('ar_invoices')) {
            return [];
        }

        $date_col = $this->column_exists('ar_invoices', 'updated_at')
            ? 'ai.updated_at'
            : ($this->column_exists('ar_invoices', 'created_at') ? 'ai.created_at' : 'ai.posting_date');

        $this->erp->select("\n            {$date_col} AS activity_date,\n            'AR_INVOICE' AS type,\n            ai.invoice_no AS reference_no,\n            ai.status,\n            CONCAT('AR Invoice: ', ai.invoice_no, ' - ', COALESCE(ai.job_no,'')) AS description\n        ", false);

        $this->erp->from('ar_invoices ai');
        $this->erp->where('ai.company_id', (int)$company_id);
        $this->apply_erp_region_filter('ar_invoices', $region_id, 'ai');
        $this->erp->order_by($date_col, 'DESC');
        $this->erp->limit($limit);

        return $this->erp->get()->result_array();
    }

    private function recent_from_gl($company_id, $limit, $region_id = '')
    {
        if (!$this->table_exists('gl_entries')) {
            return [];
        }

        $date_col = $this->column_exists('gl_entries', 'created_at') ? 'created_at' : 'posting_date';

        $this->erp->select("\n            {$date_col} AS activity_date,\n            voucher_type AS type,\n            voucher_no AS reference_no,\n            status,\n            CONCAT('GL posted: ', voucher_type, ' ', voucher_no) AS description\n        ", false);

        $this->erp->from('gl_entries');

        if ($this->column_exists('gl_entries', 'company_id')) {
            $this->erp->where('company_id', (int)$company_id);
        }

        $this->apply_erp_region_filter('gl_entries', $region_id);

        if ($this->column_exists('gl_entries', 'status')) {
            $this->erp->where('status', 'POSTED');
        }

        $this->erp->group_by(['voucher_type', 'voucher_no']);
        $this->erp->order_by($date_col, 'DESC');
        $this->erp->limit($limit);

        return $this->erp->get()->result_array();
    }

    private function recent_from_ar_receipts($company_id, $limit, $region_id = '')
    {
        if (!$this->table_exists('ar_receipts')) {
            return [];
        }

        $date_col = $this->column_exists('ar_receipts', 'created_at') ? 'created_at' : 'posting_date';
        $no_col = $this->column_exists('ar_receipts', 'receipt_no')
            ? 'receipt_no'
            : ($this->column_exists('ar_receipts', 'rcpt_no') ? 'rcpt_no' : 'rcpt_id');

        $this->erp->select("\n            {$date_col} AS activity_date,\n            'AR_RECEIPT' AS type,\n            {$no_col} AS reference_no,\n            status,\n            CONCAT('Receive payment: ', {$no_col}) AS description\n        ", false);

        $this->erp->from('ar_receipts');

        if ($this->column_exists('ar_receipts', 'company_id')) {
            $this->erp->where('company_id', (int)$company_id);
        }

        $this->apply_erp_region_filter('ar_receipts', $region_id);
        $this->erp->order_by($date_col, 'DESC');
        $this->erp->limit($limit);

        return $this->erp->get()->result_array();
    }

    private function recent_from_ap_payments($company_id, $limit, $region_id = '')
    {
        if (!$this->table_exists('ap_payments')) {
            return [];
        }

        $date_col = $this->column_exists('ap_payments', 'created_at') ? 'created_at' : 'posting_date';
        $no_col = $this->column_exists('ap_payments', 'payment_no')
            ? 'payment_no'
            : ($this->column_exists('ap_payments', 'pay_no') ? 'pay_no' : 'payment_id');

        $this->erp->select("\n            {$date_col} AS activity_date,\n            'AP_PAYMENT' AS type,\n            {$no_col} AS reference_no,\n            status,\n            CONCAT('Vendor payment: ', {$no_col}) AS description\n        ", false);

        $this->erp->from('ap_payments');

        if ($this->column_exists('ap_payments', 'company_id')) {
            $this->erp->where('company_id', (int)$company_id);
        }

        $this->apply_erp_region_filter('ap_payments', $region_id);
        $this->erp->order_by($date_col, 'DESC');
        $this->erp->limit($limit);

        return $this->erp->get()->result_array();
    }

    private function recent_from_ap_bills($company_id, $limit, $region_id = '')
    {
        if (!$this->table_exists('ap_bills')) {
            return [];
        }

        $date_col = $this->column_exists('ap_bills', 'updated_at')
            ? 'updated_at'
            : ($this->column_exists('ap_bills', 'created_at') ? 'created_at' : 'posting_date');

        $this->erp->select("\n            {$date_col} AS activity_date,\n            'AP_BILL' AS type,\n            bill_no AS reference_no,\n            status,\n            CONCAT('AP Bill: ', bill_no, ' - ', COALESCE(vendor_name,'')) AS description\n        ", false);

        $this->erp->from('ap_bills');

        if ($this->column_exists('ap_bills', 'company_id')) {
            $this->erp->where('company_id', (int)$company_id);
        }

        $this->apply_erp_region_filter('ap_bills', $region_id);
        $this->erp->order_by($date_col, 'DESC');
        $this->erp->limit($limit);

        return $this->erp->get()->result_array();
    }

    private function recent_from_cash_advances($company_id, $limit, $region_id = '')
    {
        if (!$this->table_exists('cash_advances')) {
            return [];
        }

        $date_col = $this->column_exists('cash_advances', 'updated_at')
            ? 'updated_at'
            : ($this->column_exists('cash_advances', 'created_at') ? 'created_at' : 'posting_date');

        if ($this->column_exists('cash_advances', 'advance_id')) {
            $id_col = 'advance_id';
        } elseif ($this->column_exists('cash_advances', 'cash_advance_id')) {
            $id_col = 'cash_advance_id';
        } elseif ($this->column_exists('cash_advances', 'id')) {
            $id_col = 'id';
        } else {
            $id_col = 'posting_date';
        }

        $purpose_expr = $this->column_exists('cash_advances', 'purpose') ? "COALESCE(purpose,'')" : "''";
        $status_expr  = $this->column_exists('cash_advances', 'status') ? 'status' : "''";

        $this->erp->select("\n            {$date_col} AS activity_date,\n            'CASH_ADVANCE' AS type,\n            {$id_col} AS reference_no,\n            {$status_expr} AS status,\n            CONCAT('Cash Advance: ', {$purpose_expr}) AS description\n        ", false);

        $this->erp->from('cash_advances');

        if ($this->column_exists('cash_advances', 'company_id')) {
            $this->erp->where('company_id', (int)$company_id);
        }

        $this->apply_erp_region_filter('cash_advances', $region_id);
        $this->erp->order_by($date_col, 'DESC');
        $this->erp->limit($limit);

        return $this->erp->get()->result_array();
    }

    /*
     * =========================================================
     * UNINVOICED REVENUE - ERP REVENUE ORDER SOURCE OF TRUTH
     * =========================================================
     *
     * Operational inf_income_* is no longer queried here.
     * Revenue potential = revenue_order_items.
     * Billing state      = revenue_order_items.billing_status.
     */

    private function revenue_item_amount_idr_expr($alias = 'ri')
    {
        return "(
            CASE
                WHEN UPPER(TRIM(COALESCE({$alias}.currency,'IDR'))) IN ('USD','US$','USD.') THEN
                    CASE
                        WHEN COALESCE({$alias}.amount_idr,0) > 0
                        THEN COALESCE({$alias}.amount_idr,0)
                        ELSE COALESCE({$alias}.amount,0) * COALESCE(NULLIF({$alias}.exchange_rate,0),1)
                    END
                ELSE COALESCE({$alias}.amount,0)
            END
        )";
    }

    private function apply_unbilled_revenue_scope($company_id, $region_id = '', $item_alias = 'ri', $order_alias = 'ro')
    {
        if (!$this->table_exists('revenue_order_items') || !$this->table_exists('revenue_orders')) {
            $this->erp->where('1 = 0', null, false);
            return;
        }

        if ($this->column_exists('revenue_order_items', 'company_id')) {
            $this->erp->where($item_alias . '.company_id', (int)$company_id);
        }
        if ($this->column_exists('revenue_orders', 'company_id')) {
            $this->erp->where($order_alias . '.company_id', (int)$company_id);
        }

        $this->apply_erp_region_filter('revenue_order_items', $region_id, $item_alias);
        $this->apply_erp_region_filter('revenue_orders', $region_id, $order_alias);

        $this->erp->where(
            "UPPER(TRIM(COALESCE({$item_alias}.billing_status,'UNBILLED'))) <> 'BILLED'",
            null,
            false
        );

        if ($this->column_exists('revenue_orders', 'status')) {
            $this->erp->where(
                "UPPER(TRIM(COALESCE({$order_alias}.status,''))) NOT IN ('CANCELLED','VOID')",
                null,
                false
            );
        }
    }

    public function count_uninvoiced_orders($company_id = 1, $region_id = '')
    {
        if (!$this->table_exists('revenue_order_items') || !$this->table_exists('revenue_orders')) {
            return 0;
        }

        $this->erp->select('COUNT(DISTINCT ri.revenue_order_id) AS total_count', false)
            ->from('revenue_order_items ri')
            ->join('revenue_orders ro', 'ro.revenue_order_id = ri.revenue_order_id', 'inner');

        $this->apply_unbilled_revenue_scope($company_id, $region_id, 'ri', 'ro');

        $row = $this->erp->get()->row_array();
        return (int)($row['total_count'] ?? 0);
    }

    public function count_uninvoiced_items($region_id = '', $company_id = 1)
    {
        if (!$this->table_exists('revenue_order_items') || !$this->table_exists('revenue_orders')) {
            return 0;
        }

        $this->erp->from('revenue_order_items ri')
            ->join('revenue_orders ro', 'ro.revenue_order_id = ri.revenue_order_id', 'inner');

        $this->apply_unbilled_revenue_scope($company_id, $region_id, 'ri', 'ro');

        return (int)$this->erp->count_all_results();
    }

    public function get_uninvoiced_dashboard($p = [])
    {
        $company_id = (int)($p['company_id'] ?? 1);
        $region_id  = $this->active_region();
        $date_from  = $this->safe_date($p['date_from'] ?? null, date('Y-01-01'));
        $date_to    = $this->safe_date($p['date_to'] ?? null, date('Y-m-d'));

        if ($company_id <= 0) {
            $company_id = 1;
        }

        if (!$this->table_exists('revenue_order_items') || !$this->table_exists('revenue_orders')) {
            return [
                'total_order_uninvoiced'   => 0,
                'total_count_uninvoiced'   => 0,
                'total_nominal_uninvoiced' => 0,
                'total_unbilled_idr'        => 0,
                'total_unbilled_usd'        => 0,
                'total_equivalent_idr'      => 0,
                'grouped_by_service'        => [],
                'top_5_shippers'            => [],
                'top_5_shippers_invoiced'   => [],
            ];
        }

        $totals = $this->get_uninvoiced_revenue_totals($company_id, $region_id);
        $topShippers = $this->get_top_5_shippers_invoiced(
            $company_id,
            $region_id,
            $date_from,
            $date_to
        );

        return [
            'period'                     => ['date_from' => $date_from, 'date_to' => $date_to],
            'total_order_uninvoiced'     => $this->count_uninvoiced_orders($company_id, $region_id),
            'total_count_uninvoiced'     => (int)($totals['total_count'] ?? 0),
            // Backward-compatible key used by older Dashboard.jsx.
            'total_nominal_uninvoiced'   => round($this->num($totals['total_equivalent_idr'] ?? 0), 2),
            'total_unbilled_idr'          => round($this->num($totals['total_idr'] ?? 0), 2),
            'total_unbilled_usd'          => round($this->num($totals['total_usd'] ?? 0), 4),
            'total_equivalent_idr'        => round($this->num($totals['total_equivalent_idr'] ?? 0), 2),
            'grouped_by_service'          => $this->get_uninvoiced_grouped_by_service($company_id, $region_id),
            // Keep legacy key but change semantics to the agreed target: invoiced shippers from ERP.
            'top_5_shippers'              => $topShippers,
            'top_5_shippers_invoiced'     => $topShippers,
        ];
    }

    private function get_uninvoiced_revenue_totals($company_id, $region_id = '')
    {
        $amountIdr = $this->revenue_item_amount_idr_expr('ri');

        $this->erp->select("\n            COUNT(*) AS total_count,\n            COALESCE(SUM(CASE WHEN UPPER(TRIM(COALESCE(ri.currency,'IDR'))) IN ('USD','US$','USD.') THEN 0 ELSE COALESCE(ri.amount,0) END),0) AS total_idr,\n            COALESCE(SUM(CASE WHEN UPPER(TRIM(COALESCE(ri.currency,'IDR'))) IN ('USD','US$','USD.') THEN COALESCE(ri.amount,0) ELSE 0 END),0) AS total_usd,\n            COALESCE(SUM({$amountIdr}),0) AS total_equivalent_idr\n        ", false)
            ->from('revenue_order_items ri')
            ->join('revenue_orders ro', 'ro.revenue_order_id = ri.revenue_order_id', 'inner');

        $this->apply_unbilled_revenue_scope($company_id, $region_id, 'ri', 'ro');

        $row = $this->erp->get()->row_array();

        return $row ?: [
            'total_count' => 0,
            'total_idr' => 0,
            'total_usd' => 0,
            'total_equivalent_idr' => 0,
        ];
    }

    private function get_uninvoiced_grouped_by_service($company_id, $region_id = '')
    {
        $amountIdr = $this->revenue_item_amount_idr_expr('ri');
        $serviceExpr = "COALESCE(NULLIF(TRIM(ri.item_service),''), NULLIF(TRIM(ri.code_service),''), 'OTHER')";

        $this->erp->select("\n            {$serviceExpr} AS type_of_service,\n            COUNT(*) AS total_count,\n            COALESCE(SUM(CASE WHEN UPPER(TRIM(COALESCE(ri.currency,'IDR'))) IN ('USD','US$','USD.') THEN 0 ELSE COALESCE(ri.amount,0) END),0) AS total_idr,\n            COALESCE(SUM(CASE WHEN UPPER(TRIM(COALESCE(ri.currency,'IDR'))) IN ('USD','US$','USD.') THEN COALESCE(ri.amount,0) ELSE 0 END),0) AS total_usd,\n            COALESCE(SUM({$amountIdr}),0) AS total_nominal\n        ", false)
            ->from('revenue_order_items ri')
            ->join('revenue_orders ro', 'ro.revenue_order_id = ri.revenue_order_id', 'inner');

        $this->apply_unbilled_revenue_scope($company_id, $region_id, 'ri', 'ro');
        $this->erp->group_by($serviceExpr, false);
        $this->erp->order_by('total_nominal', 'DESC');

        return $this->erp->get()->result_array();
    }

    private function invoiced_idr_expr($alias = 'ai')
    {
        if ($this->column_exists('ar_invoices', 'grand_total_idr')) {
            return "(
                CASE
                    WHEN COALESCE({$alias}.grand_total_idr,0) > 0
                    THEN COALESCE({$alias}.grand_total_idr,0)
                    WHEN UPPER(TRIM(COALESCE({$alias}.currency,'IDR'))) IN ('USD','US$','USD.')
                    THEN COALESCE({$alias}.grand_total,0) * COALESCE(NULLIF({$alias}.exchange_rate,0),1)
                    ELSE COALESCE({$alias}.grand_total,0)
                END
            )";
        }

        return "(
            CASE
                WHEN UPPER(TRIM(COALESCE({$alias}.currency,'IDR'))) IN ('USD','US$','USD.')
                THEN COALESCE({$alias}.grand_total,0) * COALESCE(NULLIF({$alias}.exchange_rate,0),1)
                ELSE COALESCE({$alias}.grand_total,0)
            END
        )";
    }

    private function get_top_5_shippers_invoiced($company_id, $region_id, $date_from, $date_to)
    {
        if (!$this->table_exists('ar_invoices')) {
            return [];
        }

        $amountIdr = $this->invoiced_idr_expr('ai');
        $partyCodeExpr = $this->column_exists('ar_invoices', 'party_code')
            ? "COALESCE(NULLIF(TRIM(ai.party_code),''), '-')"
            : "'-'";
        $partyNameExpr = $this->column_exists('ar_invoices', 'party_name')
            ? "COALESCE(NULLIF(TRIM(ai.party_name),''), {$partyCodeExpr})"
            : $partyCodeExpr;

        $this->erp->select("\n            {$partyCodeExpr} AS party_code,\n            {$partyNameExpr} AS shipper_print,\n            COUNT(DISTINCT ai.ar_invoice_id) AS total_count,\n            COALESCE(SUM({$amountIdr}),0) AS total_nominal\n        ", false)
            ->from('ar_invoices ai');

        if ($this->column_exists('ar_invoices', 'company_id')) {
            $this->erp->where('ai.company_id', (int)$company_id);
        }

        $this->apply_erp_region_filter('ar_invoices', $region_id, 'ai');

        if ($this->column_exists('ar_invoices', 'posting_date')) {
            $this->erp->where('ai.posting_date >=', $date_from);
            $this->erp->where('ai.posting_date <=', $date_to);
        }

        if ($this->column_exists('ar_invoices', 'status')) {
            // PAID remains included because this is invoiced revenue ranking.
            $this->erp->where(
                "UPPER(TRIM(COALESCE(ai.status,''))) NOT IN ('DRAFT','CANCELLED','VOID','REVISION')",
                null,
                false
            );
        }

        if ($this->column_exists('ar_invoices', 'party_type')) {
            // Current posting model stores operational `kepada` in party_type.
            // CUSTOMER is accepted for older snapshots where shipper was normalized as customer.
            $this->erp->where(
                "UPPER(TRIM(COALESCE(ai.party_type,''))) IN ('SHIPPER','CUSTOMER')",
                null,
                false
            );
        }

        $this->erp->group_by($partyCodeExpr, false);
        $this->erp->group_by($partyNameExpr, false);
        $this->erp->order_by('total_nominal', 'DESC');
        $this->erp->limit(5);

        return $this->erp->get()->result_array();
    }

    /*
     * =========================================================
     * BANK INDONESIA KURS
     * =========================================================
     */

    public function fetch_kurs_bi($currency = 'USD', $date = null)
    {
        $currency = strtoupper(trim($currency ?: 'USD'));
        $date     = $this->safe_date($date ?: date('Y-m-d'));

        for ($i = 0; $i <= 7; $i++) {
            $try_date = date('Y-m-d', strtotime($date . " -{$i} day"));

            $url = 'https://www.bi.go.id/biwebservice/wskursbi.asmx/getSubKursLokal3'
                . '?mts=' . rawurlencode($currency)
                . '&startdate=' . rawurlencode($try_date)
                . '&enddate=' . rawurlencode($try_date);

            $xml = $this->http_get($url);

            if (!$xml) {
                continue;
            }

            $parsed = $this->parse_kurs_bi_xml($xml, $currency, $try_date);

            if (!empty($parsed)) {
                return [
                    'status' => true,
                    'data'   => $parsed,
                ];
            }
        }

        return [
            'status'  => false,
            'message' => 'Data kurs BI tidak ditemukan walau sudah mundur 7 hari.',
            'data'    => [
                'currency' => $currency,
                'date'     => $date,
            ],
        ];
    }

    private function parse_kurs_bi_xml($xml, $currency, $requested_date)
    {
        $buy = 0;
        $sell = 0;

        if (preg_match('/<beli_subkurslokal[^>]*>([^<]+)<\/beli_subkurslokal>/i', $xml, $m_buy)) {
            $buy = $this->normalize_number($m_buy[1]);
        }

        if (preg_match('/<jual_subkurslokal[^>]*>([^<]+)<\/jual_subkurslokal>/i', $xml, $m_sell)) {
            $sell = $this->normalize_number($m_sell[1]);
        }

        if ($buy <= 0 && $sell <= 0) {
            return [];
        }

        $mid = ($buy > 0 && $sell > 0) ? (($buy + $sell) / 2) : max($buy, $sell);

        return [
            'currency'       => strtoupper($currency),
            'date'           => $requested_date,
            'requested_date' => $requested_date,
            'buy_rate'       => $buy,
            'sell_rate'      => $sell,
            'middle_rate'    => $mid,
            'source'         => 'Bank Indonesia',
            'raw_columns'    => [],
        ];
    }

    private function http_get($url)
    {
        if (!function_exists('curl_init')) {
            return @file_get_contents($url);
        }

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 25,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
        ]);

        $body = curl_exec($ch);
        $err  = curl_error($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($body === false || $code < 200 || $code >= 300) {
            log_message('error', 'BI kurs request failed. HTTP=' . $code . ' ERR=' . $err);
            return null;
        }

        return $body;
    }

    private function normalize_number($value)
    {
        if ($value === null || $value === '') {
            return 0;
        }

        $value = trim((string) $value);
        $value = str_replace([' ', ','], ['', '.'], $value);

        if (substr_count($value, '.') > 1) {
            $last_dot = strrpos($value, '.');
            $value = str_replace('.', '', substr($value, 0, $last_dot)) . '.' . substr($value, $last_dot + 1);
        }

        return is_numeric($value) ? (float) $value : 0;
    }

    public function save_kurs_bi_if_table_exists($kurs)
    {
        if (!$this->table_exists('currency_rates')) {
            return false;
        }

        $data = [
            'currency'    => $kurs['currency'],
            'rate_date'   => date('Y-m-d', strtotime($kurs['date'])),
            'buy_rate'    => $kurs['buy_rate'],
            'sell_rate'   => $kurs['sell_rate'],
            'middle_rate' => $kurs['middle_rate'],
            'source'      => $kurs['source'],
            'updated_at'  => date('Y-m-d H:i:s'),
        ];

        $existing = $this->erp
            ->where('currency', $data['currency'])
            ->where('rate_date', $data['rate_date'])
            ->get('currency_rates')
            ->row();

        if ($existing) {
            $this->erp
                ->where('currency', $data['currency'])
                ->where('rate_date', $data['rate_date'])
                ->update('currency_rates', $data);
        } else {
            if ($this->column_exists('currency_rates', 'created_at')) {
                $data['created_at'] = date('Y-m-d H:i:s');
            }

            $this->erp->insert('currency_rates', $data);
        }

        return $this->erp->affected_rows() >= 0;
    }
}