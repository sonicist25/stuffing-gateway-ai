<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_pph23_reports extends MY_Model
{
  private $company_id = 1;

  private function _valid_date($s, $default = null)
  {
    $s = trim((string)$s);

    if ($s === '') {
      return $default;
    }

    return preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $s) ? $s : $default;
  }

  private function _valid_month($s)
  {
    $s = trim((string)$s);
    return preg_match('/^\d{4}\-\d{2}$/', $s) ? $s : null;
  }

  private function _region_sql($alias)
  {
    if ($this->is_super_admin()) {
      return '';
    }

    $region_id = (string)$this->auth_region();

    if ($region_id === '') {
      return '';
    }

    return ' AND ' . $alias . '.region_id = ' . $this->erp->escape($region_id) . ' ';
  }

  /**
   * List bills where pph23_total > settled.
   * Output includes: pph23_total, pph23_paid, pph23_outstanding.
   */
  public function pph23_outstanding($p)
  {
    $limit  = (int)($p['limit'] ?? 200);

    if ($limit <= 0) {
      $limit = 200;
    }

    $offset = (int)($p['offset'] ?? 0);

    if ($offset < 0) {
      $offset = 0;
    }

    $month = $this->_valid_month($p['month'] ?? '');

    $paidSub = "
      SELECT r.bill_id, COALESCE(SUM(r.allocated_amount),0) AS paid
      FROM pph23_payment_refs r
      INNER JOIN pph23_payments p ON p.pay_id = r.pay_id
      WHERE p.company_id = " . (int)$this->company_id . "
        AND p.status != 'CANCELLED'
        " . $this->_region_sql('p') . "
      GROUP BY r.bill_id
    ";

    $this->erp->select("
      b.bill_id,
      b.bill_no,
      b.posting_date,
      b.source_key AS job_no,
      b.vendor_code,
      b.vendor_name,
      b.total,
      b.paid_total,
      b.pph23_total,
      COALESCE(x.paid,0) AS pph23_paid,
      (COALESCE(b.pph23_total,0) - COALESCE(x.paid,0)) AS pph23_outstanding
    ", false)
    ->from('ap_bills b')
    ->join("($paidSub) x", 'x.bill_id = b.bill_id', 'left', false)
    ->where('b.company_id', $this->company_id)
    ->where("COALESCE(b.status,'') !=", 'CANCELLED')
    ->where('COALESCE(b.pph23_total,0) >', 0.01, false)
    ->where('(COALESCE(b.pph23_total,0) - COALESCE(x.paid,0)) >', 0.01, false);

    $this->apply_region_filter_alias('b', 'region_id');

    if (!empty($p['vendor_code'])) {
      $this->erp->where('b.vendor_code', $p['vendor_code']);
    }

    if ($month) {
      $this->erp->where("DATE_FORMAT(b.posting_date,'%Y-%m') =", $month, false);
    }

    if (!empty($p['search'])) {
      $q = $p['search'];

      $this->erp->group_start()
        ->like('b.bill_no', $q)
        ->or_like('b.source_key', $q)
        ->or_like('b.vendor_name', $q)
      ->group_end();
    }

    $this->erp->order_by('b.posting_date', 'DESC')
      ->order_by('b.bill_id', 'DESC');

    $total = (int)$this->erp->count_all_results('', false);
    $rows  = $this->erp->limit($limit, $offset)->get()->result_array();

    foreach ($rows as &$r) {
      $r['total'] = (float)$r['total'];
      $r['paid_total'] = (float)$r['paid_total'];
      $r['pph23_total'] = (float)$r['pph23_total'];
      $r['pph23_paid'] = (float)$r['pph23_paid'];
      $r['pph23_outstanding'] = (float)$r['pph23_outstanding'];
    }

    unset($r);

    $sum = [
      'pph23_total'       => 0,
      'pph23_paid'        => 0,
      'pph23_outstanding' => 0
    ];

    foreach ($rows as $r) {
      $sum['pph23_total'] += (float)$r['pph23_total'];
      $sum['pph23_paid'] += (float)$r['pph23_paid'];
      $sum['pph23_outstanding'] += (float)$r['pph23_outstanding'];
    }

    return [
      'region_id' => (string)$this->auth_region(),
      'limit'     => $limit,
      'offset'    => $offset,
      'total'     => $total,
      'totals'    => $sum,
      'rows'      => $rows
    ];
  }

  /**
   * Aging / recap per month. Masa pajak = month of ap_bills.posting_date.
   */
  public function pph23_aging($p)
  {
    $date_from = $this->_valid_date($p['date_from'] ?? '', date('Y-01-01'));
    $date_to   = $this->_valid_date($p['date_to'] ?? '', date('Y-m-d'));
    $vendor_code = trim((string)($p['vendor_code'] ?? ''));

    $paidSub = "
      SELECT r.bill_id, COALESCE(SUM(r.allocated_amount),0) AS paid
      FROM pph23_payment_refs r
      INNER JOIN pph23_payments p ON p.pay_id = r.pay_id
      WHERE p.company_id = " . (int)$this->company_id . "
        AND p.status = 'SUBMITTED'
        " . $this->_region_sql('p') . "
      GROUP BY r.bill_id
    ";

    $this->erp->select("
	  DATE_FORMAT(b.posting_date,'%Y-%m') AS masa,
	  SUM(COALESCE(b.pph23_total,0)) AS pph23_total,
	  SUM(COALESCE(x.paid,0)) AS pph23_paid,
	  SUM(COALESCE(b.pph23_total,0) - COALESCE(x.paid,0)) AS pph23_outstanding
	", false)
	->from('ap_bills b')
	->join("($paidSub) x", 'x.bill_id = b.bill_id', 'left', false)
	->where('b.company_id', $this->company_id)
	->where("COALESCE(b.status,'') !=", 'CANCELLED')
	->where('COALESCE(b.pph23_total,0) >', 0.01, false)
	->where('b.posting_date >=', $date_from)
	->where('b.posting_date <=', $date_to);

    $this->apply_region_filter_alias('b', 'region_id');

    if ($vendor_code !== '') {
      $this->erp->where('b.vendor_code', $vendor_code);
    }

    $this->erp->group_by("DATE_FORMAT(b.posting_date,'%Y-%m')", false)
      ->order_by('masa', 'DESC');

    $rows = $this->erp->get()->result_array();

    $grand = [
      'pph23_total'       => 0,
      'pph23_paid'        => 0,
      'pph23_outstanding' => 0
    ];

    foreach ($rows as &$r) {
      $r['pph23_total'] = (float)$r['pph23_total'];
      $r['pph23_paid'] = (float)$r['pph23_paid'];
      $r['pph23_outstanding'] = (float)$r['pph23_outstanding'];

      $grand['pph23_total'] += $r['pph23_total'];
      $grand['pph23_paid'] += $r['pph23_paid'];
      $grand['pph23_outstanding'] += $r['pph23_outstanding'];
    }

    unset($r);

    return [
      'region_id'   => (string)$this->auth_region(),
      'date_from'   => $date_from,
      'date_to'     => $date_to,
      'vendor_code' => $vendor_code ?: null,
      'totals'      => $grand,
      'rows'        => $rows
    ];
  }

  public function pph23_paid_history($p)
  {
    $date_from = $this->_valid_date($p['date_from'] ?? '', date('Y-01-01'));
    $date_to   = $this->_valid_date($p['date_to'] ?? '', date('Y-m-d'));

    $this->erp->select("
        DATE_FORMAT(p.posting_date,'%Y-%m') AS masa_setor,
        COUNT(*) AS voucher_count,
        SUM(p.total_paid) AS total_paid
      ", false)
      ->from('pph23_payments p')
      ->where('p.company_id', $this->company_id)
      ->where('p.status', 'SUBMITTED')
      ->where('p.posting_date >=', $date_from)
      ->where('p.posting_date <=', $date_to);

    $this->apply_region_filter_alias('p', 'region_id');

    $rows = $this->erp->group_by("DATE_FORMAT(p.posting_date,'%Y-%m')", false)
      ->order_by('masa_setor', 'DESC')
      ->get()
      ->result_array();

    $grand = [
      'total_paid'    => 0,
      'voucher_count' => 0
    ];

    foreach ($rows as &$r) {
      $r['voucher_count'] = (int)$r['voucher_count'];
      $r['total_paid'] = (float)$r['total_paid'];
      $grand['voucher_count'] += $r['voucher_count'];
      $grand['total_paid'] += $r['total_paid'];
    }

    unset($r);

    $this->erp->select("
        DATE_FORMAT(p.posting_date,'%Y-%m') AS masa_setor,
        p.cash_account_id,
        SUM(p.total_paid) AS total_paid
      ", false)
      ->from('pph23_payments p')
      ->where('p.company_id', $this->company_id)
      ->where('p.status', 'SUBMITTED')
      ->where('p.posting_date >=', $date_from)
      ->where('p.posting_date <=', $date_to);

    $this->apply_region_filter_alias('p', 'region_id');

    $byCash = $this->erp->group_by("DATE_FORMAT(p.posting_date,'%Y-%m'), p.cash_account_id", false)
      ->order_by('masa_setor', 'DESC')
      ->get()
      ->result_array();

    $cashIds = array_values(array_unique(array_map(function ($x) {
      return (int)$x['cash_account_id'];
    }, $byCash)));

    $cashMap = [];

    if ($cashIds) {
      $this->erp->select('account_id,account_code,account_name')
        ->from('accounts')
        ->where('company_id', $this->company_id)
        ->where_in('account_id', $cashIds);

      $this->apply_region_filter('region_id');

      $acc = $this->erp->get()->result_array();

      foreach ($acc as $a) {
        $cashMap[(int)$a['account_id']] = $a['account_code'] . ' - ' . $a['account_name'];
      }
    }

    foreach ($byCash as &$r) {
      $r['cash_account_id'] = (int)$r['cash_account_id'];
      $r['cash_account_label'] = $cashMap[(int)$r['cash_account_id']] ?? null;
      $r['total_paid'] = (float)$r['total_paid'];
    }

    unset($r);

    return [
      'region_id'       => (string)$this->auth_region(),
      'date_from'       => $date_from,
      'date_to'         => $date_to,
      'totals'          => $grand,
      'rows'            => $rows,
      'by_cash_account' => $byCash
    ];
  }

  public function pph23_control($p)
  {
    $date_from = $this->_valid_date($p['date_from'] ?? '', date('Y-01-01'));
    $date_to   = $this->_valid_date($p['date_to'] ?? '', date('Y-m-d'));

    $this->erp->select("
        DATE_FORMAT(b.posting_date,'%Y-%m') AS masa,
        SUM(COALESCE(b.pph23_total,0)) AS accrued
      ", false)
      ->from('ap_bills b')
      ->where('b.company_id', $this->company_id)
      ->where("COALESCE(b.status,'') !=", 'CANCELLED')
      ->where('COALESCE(b.pph23_total,0) >', 0.01, false)
      ->where('b.posting_date >=', $date_from)
      ->where('b.posting_date <=', $date_to);

    $this->apply_region_filter_alias('b', 'region_id');

    $accRows = $this->erp->group_by("DATE_FORMAT(b.posting_date,'%Y-%m')", false)
      ->get()
      ->result_array();

    $accMap = [];

    foreach ($accRows as $r) {
      $accMap[$r['masa']] = (float)$r['accrued'];
    }

    $this->erp->select("
        DATE_FORMAT(p.posting_date,'%Y-%m') AS masa,
        SUM(COALESCE(p.total_paid,0)) AS paid
      ", false)
      ->from('pph23_payments p')
      ->where('p.company_id', $this->company_id)
      ->where('p.status', 'SUBMITTED')
      ->where('p.posting_date >=', $date_from)
      ->where('p.posting_date <=', $date_to);

    $this->apply_region_filter_alias('p', 'region_id');

    $payRows = $this->erp->group_by("DATE_FORMAT(p.posting_date,'%Y-%m')", false)
      ->get()
      ->result_array();

    $payMap = [];

    foreach ($payRows as $r) {
      $payMap[$r['masa']] = (float)$r['paid'];
    }

    $months = [];
    $cur = date('Y-m-01', strtotime($date_from));
    $end = date('Y-m-01', strtotime($date_to));

    while ($cur <= $end) {
      $months[] = date('Y-m', strtotime($cur));
      $cur = date('Y-m-01', strtotime($cur . ' +1 month'));
    }

    $rows = [];
    $totAcc = 0.0;
    $totPaid = 0.0;
    $run = 0.0;

    foreach ($months as $m) {
      $ac = (float)($accMap[$m] ?? 0);
      $pd = (float)($payMap[$m] ?? 0);
      $diff = $ac - $pd;
      $run += $diff;

      $rows[] = [
        'masa'            => $m,
        'accrued'         => $ac,
        'paid'            => $pd,
        'diff'            => $diff,
        'payable_running' => $run
      ];

      $totAcc += $ac;
      $totPaid += $pd;
    }

    $pph23_account_id = $this->_coa_id('PPH23_PAYABLE', (string)$this->auth_region());
    $gl_balance = null;

    if ($pph23_account_id > 0) {
      $this->erp->select('COALESCE(SUM(debit - credit),0) AS bal', false)
        ->from('gl_entries')
        ->where('company_id', $this->company_id)
        ->where('status', 'POSTED')
        ->where('account_id', $pph23_account_id)
        ->where('posting_date <=', $date_to);

      $this->apply_region_filter('region_id');

      $gl = $this->erp->get()->row_array();
      $gl_balance = (float)($gl['bal'] ?? 0);
    }

    return [
      'region_id' => (string)$this->auth_region(),
      'date_from' => $date_from,
      'date_to'   => $date_to,
      'totals'    => [
        'accrued'              => $totAcc,
        'paid'                 => $totPaid,
        'diff'                 => $totAcc - $totPaid,
        'payable_running_end'  => $run,
        'gl_pph23_payable_balance_asof' => $gl_balance
      ],
      'rows' => $rows
    ];
  }


  private function _coa_config()
  {
    $this->config->load('finance_coa', false, true);
    $cfg = $this->config->item('finance_coa');
    return is_array($cfg) ? $cfg : [];
  }

  private function _coa_code($key)
  {
    $cfg = $this->_coa_config();
    $key = strtoupper(trim((string)$key));
    return trim((string)($cfg['accounts'][$key] ?? ''));
  }

  private function _coa_id($key, $region_id = null)
  {
    $code = $this->_coa_code($key);
    $region_id = trim((string)($region_id !== null ? $region_id : (string)$this->auth_region()));

    if ($code === '' || $region_id === '') {
      return 0;
    }

    $row = $this->erp
      ->select('account_id')
      ->from('accounts')
      ->where('company_id', $this->company_id)
      ->where('region_id', $region_id)
      ->where('account_code', $code)
      ->where('is_active', 1)
      ->where('is_postable', 1)
      ->limit(1)
      ->get()
      ->row_array();

    return $row ? (int)$row['account_id'] : 0;
  }

}
