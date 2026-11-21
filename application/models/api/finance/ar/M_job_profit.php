<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_job_profit extends MY_Model
{
    public $company_id = 1;

    public function __construct()
    {
        parent::__construct();
        $this->config->load('finance_coa', false, true);
    }

    // =========================================================
    // REGION + CENTRAL COA
    // =========================================================

    private function region_id($fallback = null)
    {
        $region = '';

        if ($fallback !== null && trim((string)$fallback) !== '') {
            $region = trim((string)$fallback);
        }

        if ($region === '' && $this->session->userdata('region_id')) {
            $region = trim((string)$this->session->userdata('region_id'));
        }

        if ($region === '' && $this->session->userdata('region')) {
            $region = trim((string)$this->session->userdata('region'));
        }

        if ($region === '' && method_exists($this, 'auth_region')) {
            $region = trim((string)$this->auth_region());
        }

        return $region;
    }

    private function coa_config()
    {
        $cfg = $this->config->item('finance_coa');
        return is_array($cfg) ? $cfg : [];
    }

    private function coa_code($key)
    {
        $cfg = $this->coa_config();
        $accounts = $cfg['accounts'] ?? [];
        return trim((string)($accounts[$key] ?? ''));
    }

    private function coa_id($key, $region_id)
    {
        $code = $this->coa_code($key);
        $region_id = $this->region_id($region_id);

        if ($code === '' || $region_id === '') {
            return 0;
        }

        $row = $this->erp
            ->select('account_id, account_code, account_name, is_postable, is_active')
            ->from('accounts')
            ->where('company_id', $this->company_id)
            ->where('region_id', $region_id)
            ->where('account_code', $code)
            ->get()
            ->row_array();

        if (!$row) {
            return 0;
        }

        if ((int)$row['is_active'] !== 1 || (int)$row['is_postable'] !== 1) {
            return 0;
        }

        return (int)$row['account_id'];
    }

    private function require_coa(array $keys, $region_id)
    {
        $region_id = $this->region_id($region_id);
        $resolved = [];
        $missing = [];

        foreach ($keys as $key) {
            $code = $this->coa_code($key);
            $id = $this->coa_id($key, $region_id);

            if ($id <= 0) {
                $missing[] = [
                    'key'  => $key,
                    'code' => $code,
                ];
                continue;
            }

            $resolved[$key] = $id;
        }

        if ($missing) {
            return [
                'ok'      => false,
                'http'    => 422,
                'message' => 'Missing/invalid central COA mapping',
                'data'    => [
                    'region_id' => $region_id,
                    'missing'   => $missing,
                ],
            ];
        }

        return [
            'ok'   => true,
            'data' => $resolved,
        ];
    }

    private function revenue_prefixes()
    {
        $cfg = $this->coa_config();
        $prefixes = $cfg['reporting']['REVENUE_PREFIXES'] ?? [];

        if (!is_array($prefixes)) {
            return [];
        }

        $out = [];
        foreach ($prefixes as $prefix) {
            $prefix = trim((string)$prefix);
            if ($prefix !== '') {
                $out[] = $prefix;
            }
        }

        return array_values(array_unique($out));
    }

    private function revenue_condition_sql($accountAlias = 'a')
    {
        $parts = [];

        foreach ($this->revenue_prefixes() as $prefix) {
            $parts[] = $accountAlias . '.account_code LIKE ' . $this->erp->escape($prefix . '%');
        }

        return $parts ? '(' . implode(' OR ', $parts) . ')' : '(1 = 0)';
    }

    // =========================================================
    // GL HELPERS
    // =========================================================

    private function sum_gl($job_no, $account_id, $region_id, $date_from = null, $date_to = null)
    {
        $this->erp
            ->select('COALESCE(SUM(debit),0) AS dr, COALESCE(SUM(credit),0) AS cr', false)
            ->from('gl_entries')
            ->where('company_id', $this->company_id)
            ->where('region_id', $region_id)
            ->where('status', 'POSTED')
            ->where('job_no', $job_no)
            ->where('account_id', (int)$account_id);

        if ($date_from) {
            $this->erp->where('posting_date >=', $date_from);
        }

        if ($date_to) {
            $this->erp->where('posting_date <=', $date_to);
        }

        $r = $this->erp->get()->row_array();
        $dr = (float)($r['dr'] ?? 0);
        $cr = (float)($r['cr'] ?? 0);

        return [
            'debit'   => round($dr, 0),
            'credit'  => round($cr, 0),
            'balance' => round($dr - $cr, 0),
        ];
    }

    private function sum_revenue($job_no, $region_id, $date_from = null, $date_to = null)
    {
        $revenueCondition = $this->revenue_condition_sql('a');

        $this->erp
            ->select('COALESCE(SUM(g.debit),0) AS dr, COALESCE(SUM(g.credit),0) AS cr', false)
            ->from('gl_entries g')
            ->join(
                'accounts a',
                'a.account_id = g.account_id AND a.company_id = g.company_id AND a.region_id = g.region_id',
                'inner'
            )
            ->where('g.company_id', $this->company_id)
            ->where('g.region_id', $region_id)
            ->where('g.status', 'POSTED')
            ->where('g.job_no', $job_no)
            ->where($revenueCondition, null, false);

        if ($date_from) {
            $this->erp->where('g.posting_date >=', $date_from);
        }

        if ($date_to) {
            $this->erp->where('g.posting_date <=', $date_to);
        }

        $r = $this->erp->get()->row_array();
        $dr = (float)($r['dr'] ?? 0);
        $cr = (float)($r['cr'] ?? 0);

        return [
            'debit'   => round($dr, 0),
            'credit'  => round($cr, 0),
            'balance' => round($dr - $cr, 0),
        ];
    }

    private function valid_date($s)
    {
        if (!$s) {
            return null;
        }

        return preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $s) ? $s : null;
    }

    // =========================================================
    // SINGLE JOB PROFIT
    // =========================================================

    public function get_job_profit($p)
    {
        $job_no = trim((string)($p['job_no'] ?? ''));
        $region_id = $this->region_id($p['region_id'] ?? null);

        if ($job_no === '') {
            return ['ok' => false, 'http' => 422, 'message' => 'job_no required'];
        }

        if ($region_id === '') {
            return ['ok' => false, 'http' => 422, 'message' => 'region_id required'];
        }

        $date_from = $this->valid_date($p['date_from'] ?? null);
        $date_to   = $this->valid_date($p['date_to'] ?? null);

        $coa = $this->require_coa(['AR', 'AP', 'PPH23_PAYABLE', 'HPP'], $region_id);
        if (!$coa['ok']) {
            return $coa;
        }

        $accAR    = $coa['data']['AR'];
        $accAP    = $coa['data']['AP'];
        $accPPH23 = $coa['data']['PPH23_PAYABLE'];
        $accHPP   = $coa['data']['HPP'];

        $rev = $this->sum_revenue($job_no, $region_id, $date_from, $date_to);
        $hpp = $this->sum_gl($job_no, $accHPP, $region_id, $date_from, $date_to);
        $ar  = $this->sum_gl($job_no, $accAR, $region_id, $date_from, $date_to);
        $ap  = $this->sum_gl($job_no, $accAP, $region_id, $date_from, $date_to);
        $pph = $this->sum_gl($job_no, $accPPH23, $region_id, $date_from, $date_to);

        $revenue_amount = round($rev['credit'] - $rev['debit'], 0);
        $hpp_amount     = round($hpp['debit'] - $hpp['credit'], 0);
        $gross_profit   = round($revenue_amount - $hpp_amount, 0);

        $this->erp
            ->select('posting_date,voucher_type,voucher_no,account_id,debit,credit')
            ->from('gl_entries')
            ->where('company_id', $this->company_id)
            ->where('region_id', $region_id)
            ->where('status', 'POSTED')
            ->where('job_no', $job_no)
            ->order_by('posting_date', 'ASC')
            ->order_by('voucher_type', 'ASC')
            ->order_by('voucher_no', 'ASC')
            ->limit(50);

        if ($date_from) {
            $this->erp->where('posting_date >=', $date_from);
        }

        if ($date_to) {
            $this->erp->where('posting_date <=', $date_to);
        }

        $rows = $this->erp->get()->result_array();

        return [
            'ok'      => true,
            'http'    => 200,
            'message' => 'OK',
            'data'    => [
                'job_no'     => $job_no,
                'region_id'  => $region_id,
                'date_from'  => $date_from,
                'date_to'    => $date_to,
                'direct_hpp' => true,

                'pnl' => [
                    'revenue'      => $revenue_amount,
                    'hpp'          => $hpp_amount,
                    'cogs'         => $hpp_amount,
                    'gross_profit' => $gross_profit,
                ],

                'balances' => [
                    'ar'    => $ar['balance'],
                    'ap'    => round($ap['credit'] - $ap['debit'], 0),
                    'pph23' => round($pph['credit'] - $pph['debit'], 0),
                ],

                'raw' => [
                    'revenue_gl' => $rev,
                    'hpp_gl'     => $hpp,
                    'ar_gl'      => $ar,
                    'ap_gl'      => $ap,
                    'pph23_gl'   => $pph,
                ],

                'sample_gl_rows' => $rows,
            ],
        ];
    }

    // =========================================================
    // JOB PROFIT LIST
    // =========================================================

    public function list_job_profit($p)
    {
        $region_id = $this->region_id($p['region_id'] ?? null);
        $date_from = $this->valid_date($p['date_from'] ?? null);
        $date_to   = $this->valid_date($p['date_to'] ?? null);

        if ($region_id === '') {
            return ['ok' => false, 'http' => 422, 'message' => 'region_id required'];
        }

        if (!$date_from || !$date_to) {
            return ['ok' => false, 'http' => 422, 'message' => 'date_from and date_to required (YYYY-MM-DD)'];
        }

        $limit  = (int)($p['limit'] ?? 50);
        $offset = (int)($p['offset'] ?? 0);

        if ($limit <= 0 || $limit > 200) {
            $limit = 50;
        }

        if ($offset < 0) {
            $offset = 0;
        }

        $sort = $p['sort'] ?? 'profit_desc';
        $orderBy = 'gross_profit DESC';
        if ($sort === 'profit_asc')  $orderBy = 'gross_profit ASC';
        if ($sort === 'revenue_desc') $orderBy = 'revenue DESC';
        if ($sort === 'cogs_desc')    $orderBy = 'hpp DESC';

        $coa = $this->require_coa(['HPP'], $region_id);
        if (!$coa['ok']) {
            return $coa;
        }

        $accHPP = $coa['data']['HPP'];
        $revCond = $this->revenue_condition_sql('a');

        $sql = "
            SELECT
                g.job_no,
                ROUND(SUM(CASE WHEN {$revCond} THEN (g.credit - g.debit) ELSE 0 END), 0) AS revenue,
                ROUND(SUM(CASE WHEN g.account_id = ? THEN (g.debit - g.credit) ELSE 0 END), 0) AS hpp,
                ROUND(
                    SUM(CASE WHEN {$revCond} THEN (g.credit - g.debit) ELSE 0 END)
                    -
                    SUM(CASE WHEN g.account_id = ? THEN (g.debit - g.credit) ELSE 0 END)
                , 0) AS gross_profit
            FROM gl_entries g
            JOIN accounts a
              ON a.company_id = g.company_id
             AND a.region_id = g.region_id
             AND a.account_id = g.account_id
            WHERE g.company_id = ?
              AND g.region_id = ?
              AND g.status = 'POSTED'
              AND g.job_no IS NOT NULL
              AND g.job_no <> ''
              AND g.posting_date >= ?
              AND g.posting_date <= ?
              AND ({$revCond} OR g.account_id = ?)
            GROUP BY g.job_no
            ORDER BY {$orderBy}
            LIMIT ? OFFSET ?
        ";

        $rows = $this->erp->query($sql, [
            $accHPP,
            $accHPP,
            $this->company_id,
            $region_id,
            $date_from,
            $date_to,
            $accHPP,
            $limit,
            $offset,
        ])->result_array();

        foreach ($rows as &$row) {
            $row['cogs'] = (float)($row['hpp'] ?? 0);
        }
        unset($row);

        $sqlCount = "
            SELECT COUNT(*) AS cnt
            FROM (
                SELECT g.job_no
                FROM gl_entries g
                JOIN accounts a
                  ON a.company_id = g.company_id
                 AND a.region_id = g.region_id
                 AND a.account_id = g.account_id
                WHERE g.company_id = ?
                  AND g.region_id = ?
                  AND g.status = 'POSTED'
                  AND g.job_no IS NOT NULL
                  AND g.job_no <> ''
                  AND g.posting_date >= ?
                  AND g.posting_date <= ?
                  AND ({$revCond} OR g.account_id = ?)
                GROUP BY g.job_no
            ) x
        ";

        $cntRow = $this->erp->query($sqlCount, [
            $this->company_id,
            $region_id,
            $date_from,
            $date_to,
            $accHPP,
        ])->row_array();

        return [
            'ok'      => true,
            'http'    => 200,
            'message' => 'OK',
            'data'    => [
                'region_id'  => $region_id,
                'date_from'  => $date_from,
                'date_to'    => $date_to,
                'direct_hpp' => true,
                'limit'      => $limit,
                'offset'     => $offset,
                'total'      => (int)($cntRow['cnt'] ?? 0),
                'rows'       => $rows,
            ],
        ];
    }

    // =========================================================
    // JOB STATUS
    // =========================================================

    public function get_job_status($p)
    {
        $job_no = trim((string)($p['job_no'] ?? ''));
        $region_id = $this->region_id($p['region_id'] ?? null);

        if ($job_no === '') {
            return ['ok' => false, 'http' => 422, 'message' => 'job_no required'];
        }

        if ($region_id === '') {
            return ['ok' => false, 'http' => 422, 'message' => 'region_id required'];
        }

        $coa = $this->require_coa(['AR', 'AP'], $region_id);
        if (!$coa['ok']) {
            return $coa;
        }

        $ar = $this->sum_gl($job_no, $coa['data']['AR'], $region_id);
        $apRaw = $this->sum_gl($job_no, $coa['data']['AP'], $region_id);
        $rev = $this->sum_revenue($job_no, $region_id);

        $ar_balance = round($ar['balance'], 0);
        $ap_balance = round($apRaw['credit'] - $apRaw['debit'], 0);
        $revenue_amount = round($rev['credit'] - $rev['debit'], 0);

        $tol = 1;
        $has_revenue = ($revenue_amount > 0);
        $is_settled = $has_revenue
            && abs($ar_balance) <= $tol
            && abs($ap_balance) <= $tol;

        if ($is_settled) {
            $status = 'SETTLED';
        } elseif ($has_revenue) {
            $status = 'INVOICED';
        } else {
            $status = 'OPEN';
        }

        $cnt = $this->erp
            ->select('COUNT(*) cnt', false)
            ->from('gl_entries')
            ->where('company_id', $this->company_id)
            ->where('region_id', $region_id)
            ->where('status', 'POSTED')
            ->where('job_no', $job_no)
            ->get()
            ->row_array();

        return [
            'ok'      => true,
            'http'    => 200,
            'message' => 'OK',
            'data'    => [
                'job_no'     => $job_no,
                'region_id'  => $region_id,
                'status'     => $status,
                'direct_hpp' => true,
                'metrics'    => [
                    'revenue'    => $revenue_amount,
                    'ar_balance' => $ar_balance,
                    'ap_balance' => $ap_balance,
                ],
                'flags' => [
                    'has_revenue' => $has_revenue,
                    'is_settled'  => $is_settled,
                ],
                'gl_count' => (int)($cnt['cnt'] ?? 0),
            ],
        ];
    }

    // =========================================================
    // JOB DASHBOARD
    // =========================================================

    public function list_job_dashboard($p)
    {
        $region_id = $this->region_id($p['region_id'] ?? null);
        $date_from = $this->valid_date($p['date_from'] ?? null);
        $date_to   = $this->valid_date($p['date_to'] ?? null);

        if ($region_id === '') {
            return ['ok' => false, 'http' => 422, 'message' => 'region_id required'];
        }

        if (!$date_from || !$date_to) {
            return ['ok' => false, 'http' => 422, 'message' => 'date_from and date_to required (YYYY-MM-DD)'];
        }

        $status = strtoupper(trim((string)($p['status'] ?? 'ALL')));
        if (!in_array($status, ['ALL', 'OPEN', 'INVOICED', 'SETTLED'], true)) {
            $status = 'ALL';
        }

        $q = trim((string)($p['q'] ?? ''));
        $limit  = (int)($p['limit'] ?? 50);
        $offset = (int)($p['offset'] ?? 0);

        if ($limit <= 0 || $limit > 200) {
            $limit = 50;
        }

        if ($offset < 0) {
            $offset = 0;
        }

        $sort = $p['sort'] ?? 'profit_desc';
        $orderBy = 'gross_profit DESC';
        if ($sort === 'profit_asc')  $orderBy = 'gross_profit ASC';
        if ($sort === 'revenue_desc') $orderBy = 'revenue DESC';
        if ($sort === 'cogs_desc')    $orderBy = 'hpp DESC';
        if ($sort === 'recent')       $orderBy = 'last_posting_date DESC';

        $coa = $this->require_coa(['AR', 'AP', 'HPP'], $region_id);
        if (!$coa['ok']) {
            return $coa;
        }

        $accAR  = $coa['data']['AR'];
        $accAP  = $coa['data']['AP'];
        $accHPP = $coa['data']['HPP'];
        $revCond = $this->revenue_condition_sql('a');

        $qWhere = '';
        $qParams = [];
        if ($q !== '') {
            $qWhere = ' AND g.job_no LIKE ? ';
            $qParams[] = $q . '%';
        }

        $having = '';
        if ($status === 'SETTLED') {
            $having = ' HAVING revenue > 0 AND ABS(ar_balance) <= 1 AND ABS(ap_balance) <= 1 ';
        } elseif ($status === 'INVOICED') {
            $having = ' HAVING revenue > 0 AND NOT (ABS(ar_balance) <= 1 AND ABS(ap_balance) <= 1) ';
        } elseif ($status === 'OPEN') {
            $having = ' HAVING revenue <= 0 ';
        }

        $sql = "
            SELECT
                g.job_no,
                ROUND(SUM(CASE WHEN {$revCond} THEN (g.credit - g.debit) ELSE 0 END), 0) AS revenue,
                ROUND(SUM(CASE WHEN g.account_id = ? THEN (g.debit - g.credit) ELSE 0 END), 0) AS hpp,
                ROUND(SUM(CASE WHEN g.account_id = ? THEN (g.debit - g.credit) ELSE 0 END), 0) AS ar_balance,
                ROUND(SUM(CASE WHEN g.account_id = ? THEN (g.credit - g.debit) ELSE 0 END), 0) AS ap_balance,
                ROUND(
                    SUM(CASE WHEN {$revCond} THEN (g.credit - g.debit) ELSE 0 END)
                    -
                    SUM(CASE WHEN g.account_id = ? THEN (g.debit - g.credit) ELSE 0 END)
                , 0) AS gross_profit,
                MAX(g.posting_date) AS last_posting_date,
                CASE
                    WHEN
                        ROUND(SUM(CASE WHEN {$revCond} THEN (g.credit - g.debit) ELSE 0 END), 0) > 0
                        AND ABS(ROUND(SUM(CASE WHEN g.account_id = ? THEN (g.debit - g.credit) ELSE 0 END), 0)) <= 1
                        AND ABS(ROUND(SUM(CASE WHEN g.account_id = ? THEN (g.credit - g.debit) ELSE 0 END), 0)) <= 1
                    THEN 'SETTLED'
                    WHEN ROUND(SUM(CASE WHEN {$revCond} THEN (g.credit - g.debit) ELSE 0 END), 0) > 0
                    THEN 'INVOICED'
                    ELSE 'OPEN'
                END AS status
            FROM gl_entries g
            JOIN accounts a
              ON a.company_id = g.company_id
             AND a.region_id = g.region_id
             AND a.account_id = g.account_id
            WHERE g.company_id = ?
              AND g.region_id = ?
              AND g.status = 'POSTED'
              AND g.job_no IS NOT NULL
              AND g.job_no <> ''
              AND g.posting_date >= ?
              AND g.posting_date <= ?
              {$qWhere}
              AND ({$revCond} OR g.account_id IN (?, ?, ?))
            GROUP BY g.job_no
            {$having}
            ORDER BY {$orderBy}
            LIMIT ? OFFSET ?
        ";

        $params = [
            $accHPP,
            $accAR,
            $accAP,
            $accHPP,
            $accAR,
            $accAP,
            $this->company_id,
            $region_id,
            $date_from,
            $date_to,
        ];
        $params = array_merge($params, $qParams, [$accHPP, $accAR, $accAP, $limit, $offset]);

        $rows = $this->erp->query($sql, $params)->result_array();
        foreach ($rows as &$row) {
            $row['cogs'] = (float)($row['hpp'] ?? 0);
        }
        unset($row);

        $sqlCount = "
            SELECT COUNT(*) AS cnt
            FROM (
                SELECT
                    g.job_no,
                    ROUND(SUM(CASE WHEN {$revCond} THEN (g.credit - g.debit) ELSE 0 END), 0) AS revenue,
                    ROUND(SUM(CASE WHEN g.account_id = ? THEN (g.debit - g.credit) ELSE 0 END), 0) AS hpp,
                    ROUND(SUM(CASE WHEN g.account_id = ? THEN (g.debit - g.credit) ELSE 0 END), 0) AS ar_balance,
                    ROUND(SUM(CASE WHEN g.account_id = ? THEN (g.credit - g.debit) ELSE 0 END), 0) AS ap_balance
                FROM gl_entries g
                JOIN accounts a
                  ON a.company_id = g.company_id
                 AND a.region_id = g.region_id
                 AND a.account_id = g.account_id
                WHERE g.company_id = ?
                  AND g.region_id = ?
                  AND g.status = 'POSTED'
                  AND g.job_no IS NOT NULL
                  AND g.job_no <> ''
                  AND g.posting_date >= ?
                  AND g.posting_date <= ?
                  {$qWhere}
                  AND ({$revCond} OR g.account_id IN (?, ?, ?))
                GROUP BY g.job_no
                {$having}
            ) x
        ";

        $paramsCount = [
            $accHPP,
            $accAR,
            $accAP,
            $this->company_id,
            $region_id,
            $date_from,
            $date_to,
        ];
        $paramsCount = array_merge($paramsCount, $qParams, [$accHPP, $accAR, $accAP]);

        $cntRow = $this->erp->query($sqlCount, $paramsCount)->row_array();

        return [
            'ok'      => true,
            'http'    => 200,
            'message' => 'OK',
            'data'    => [
                'region_id'  => $region_id,
                'date_from'  => $date_from,
                'date_to'    => $date_to,
                'status'     => $status,
                'q'          => $q,
                'direct_hpp' => true,
                'limit'      => $limit,
                'offset'     => $offset,
                'total'      => (int)($cntRow['cnt'] ?? 0),
                'rows'       => $rows,
            ],
        ];
    }
}
