<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_pph23 extends MY_Model
{
  private $company_id = 1;

  public function list($p)
  {
    $this->erp->select("p.pay_id,p.pay_no,p.posting_date,p.cash_account_id,p.total_paid,p.remark,p.status", false)
      ->from('pph23_payments p')
      ->where('p.company_id', $this->company_id);

    $this->apply_region_filter_alias('p', 'region_id');

    if (!empty($p['status'])) {
      $this->erp->where('p.status', strtoupper($p['status']));
    }

    if (!empty($p['search'])) {
      $q = $p['search'];
      $this->erp->group_start()
        ->like('p.pay_no', $q)
        ->or_like('p.remark', $q)
      ->group_end();
    }

    if (!empty($p['date_from'])) {
      $this->erp->where('p.posting_date >=', $p['date_from']);
    }

    if (!empty($p['date_to'])) {
      $this->erp->where('p.posting_date <=', $p['date_to']);
    }

    $this->erp->order_by('p.posting_date', 'DESC')
      ->order_by('p.pay_id', 'DESC');

    $rows = $this->erp->get()->result_array();

    if ($rows) {
      $ids = array_values(array_unique(array_map(function ($x) {
        return (int)$x['cash_account_id'];
      }, $rows)));

      $map = [];

      if ($ids) {
        $this->erp->select('account_id,account_code,account_name')
          ->from('accounts')
          ->where('company_id', $this->company_id)
          ->where_in('account_id', $ids);

        $this->apply_region_filter('region_id');

        $acc = $this->erp->get()->result_array();

        foreach ($acc as $a) {
          $map[(int)$a['account_id']] = $a['account_code'] . ' - ' . $a['account_name'];
        }
      }

      foreach ($rows as &$r) {
        $r['cash_account_label'] = $map[(int)$r['cash_account_id']] ?? null;
        $r['total_paid'] = (float)$r['total_paid'];
      }

      unset($r);
    }

    return $rows;
  }

  public function get($pay_id)
  {
    $pay_id = (int)$pay_id;

    $this->erp->from('pph23_payments')
      ->where('company_id', $this->company_id)
      ->where('pay_id', $pay_id);

    $this->apply_region_filter('region_id');

    $hdr = $this->erp->get()->row_array();

    if (!$hdr) {
      return null;
    }

    $this->erp->select('r.ref_id,r.bill_id,r.bill_no,r.allocated_amount', false)
      ->from('pph23_payment_refs r')
      ->where('r.pay_id', $pay_id);

    if ($this->erp->field_exists('region_id', 'pph23_payment_refs')) {
      $this->apply_region_filter_alias('r', 'region_id');
    }

    $refs = $this->erp->order_by('r.ref_id', 'ASC')->get()->result_array();

    foreach ($refs as &$r) {
      $r['allocated_amount'] = (float)$r['allocated_amount'];
    }

    unset($r);

    $hdr['total_paid'] = (float)$hdr['total_paid'];
    $hdr['refs'] = $refs;

    return $hdr;
  }

  public function create($p)
  {
    $posting_date    = (string)($p['posting_date'] ?? '');
    $cash_account_id = (int)($p['cash_account_id'] ?? 0);
    $remark          = trim((string)($p['remark'] ?? ''));
    $alloc           = $p['allocations'] ?? [];

    $errs = [];

    if (!$posting_date) {
      $errs['posting_date'] = 'required';
    }

    if ($cash_account_id <= 0) {
      $errs['cash_account_id'] = 'required';
    }

    if (!is_array($alloc) || count($alloc) < 1) {
      $errs['allocations'] = 'required';
    }

    if ($errs) {
      return ['ok' => false, 'http' => 422, 'message' => 'Validation error', 'errors' => $errs];
    }

    if ($this->_is_locked($posting_date)) {
      return ['ok' => false, 'http' => 409, 'message' => 'PERIOD_LOCKED'];
    }

    $this->erp->select('account_id,is_postable,is_active')
      ->from('accounts')
      ->where('company_id', $this->company_id)
      ->where('account_id', $cash_account_id);

    $this->apply_region_filter('region_id');

    $acc = $this->erp->get()->row_array();

    if (!$acc || (int)$acc['is_postable'] !== 1 || (int)$acc['is_active'] !== 1) {
      return ['ok' => false, 'http' => 422, 'message' => 'Invalid cash account'];
    }

    $pph23_account_id = $this->_coa_id('PPH23_PAYABLE', (string)$this->auth_region());

    if ($pph23_account_id <= 0) {
      return ['ok' => false, 'http' => 422, 'message' => 'Missing/invalid PPh23 payable account (' . $this->_coa_code('PPH23_PAYABLE') . ')'];
    }

    $total_paid = 0.0;
    $rows = [];

    foreach ($alloc as $i => $a) {
      $bid = (int)($a['bill_id'] ?? 0);
      $amt = (float)($a['amount'] ?? 0);

      if ($bid <= 0) {
        $errs["allocations.$i.bill_id"] = 'required';
      }

      if ($amt <= 0) {
        $errs["allocations.$i.amount"] = 'must be > 0';
      }

      if ($bid > 0 && $amt > 0) {
        $this->erp->select('bill_id,bill_no,source_key,pph23_total,status', false)
          ->from('ap_bills')
          ->where('company_id', $this->company_id)
          ->where('bill_id', $bid);

        $this->apply_region_filter('region_id');

        $bill = $this->erp->get()->row_array();

        if (!$bill) {
          $errs["allocations.$i.bill_id"] = 'bill not found';
        } elseif ($bill['status'] === 'CANCELLED') {
          $errs["allocations.$i.bill_id"] = 'bill cancelled';
        } else {
          $pph_total = (float)($bill['pph23_total'] ?? 0);

          if ($pph_total <= 0.01) {
            $errs["allocations.$i.bill_id"] = 'bill has no pph23_total';
          } else {
            $paid = $this->_pph23_paid_for_bill($bid);
            $out = $pph_total - $paid;

            if ($out <= 0.01) {
              $errs["allocations.$i.bill_id"] = 'pph23 already settled';
            } elseif ($amt - $out > 0.01) {
              $errs["allocations.$i.amount"] = "amount exceeds pph23 outstanding ($out)";
            } else {
              $rows[] = [
                'bill_id'   => $bid,
                'bill_no'   => $bill['bill_no'],
                'job_no'    => trim((string)($bill['source_key'] ?? '')),
                'pph_total' => $pph_total,
                'pph_paid'  => $paid,
                'pph_out'   => $out,
                'amount'    => $amt
              ];

              $total_paid += $amt;
            }
          }
        }
      }
    }

    if ($errs) {
      return ['ok' => false, 'http' => 422, 'message' => 'Validation error', 'errors' => $errs];
    }

    $region_id = (string)$this->auth_region();

    $this->erp->trans_begin();

    $pay_no = $this->_next_series('PPH23', (int)date('Y', strtotime($posting_date)));

    $this->erp->insert('pph23_payments', [
      'company_id'      => $this->company_id,
      'region_id'       => $region_id,
      'pay_no'          => $pay_no,
      'posting_date'    => $posting_date,
      'cash_account_id' => $cash_account_id,
      'total_paid'      => $total_paid,
      'remark'          => $remark ?: null,
      'status'          => 'SUBMITTED',
      'created_by'      => $this->auth_username(),
      'created_at'      => date('Y-m-d H:i:s')
    ]);

    $pay_id = (int)$this->erp->insert_id();

    foreach ($rows as $r) {
      $refData = [
        'pay_id'           => $pay_id,
        'bill_id'          => $r['bill_id'],
        'bill_no'          => $r['bill_no'],
        'allocated_amount' => $r['amount']
      ];

      if ($this->erp->field_exists('region_id', 'pph23_payment_refs')) {
        $refData['region_id'] = $region_id;
      }

      $this->erp->insert('pph23_payment_refs', $refData);
    }

    if ($this->_gl_voucher_posted('WHTPAY', $pay_no)) {
      $this->erp->trans_rollback();
      return ['ok' => false, 'http' => 409, 'message' => 'GL_ALREADY_POSTED', 'data' => ['voucher_type' => 'WHTPAY', 'voucher_no' => $pay_no]];
    }

    $refs = $this->erp->get_where('pph23_payment_refs', ['pay_id' => $pay_id])->result_array();
    $this->_post_gl_pph23($posting_date, $pay_no, $pph23_account_id, $cash_account_id, $total_paid, $refs);

    if ($this->erp->trans_status() === false) {
      $err = $this->erp->error();
      $sql = $this->erp->last_query();
      $this->erp->trans_rollback();

      return [
        'ok'      => false,
        'http'    => 500,
        'message' => 'DB error',
        'data'    => [
          'db_error'   => $err,
          'last_query' => $sql,
        ]
      ];
    }

    $this->erp->trans_commit();

    return ['ok' => true, 'message' => 'PPH23 settlement posted', 'data' => [
      'pay_id'       => $pay_id,
      'pay_no'       => $pay_no,
      'region_id'    => $region_id,
      'posting_date' => $posting_date,
      'total_paid'   => $total_paid
    ]];
  }

  public function cancel($pay_id)
  {
    $pay_id = (int)$pay_id;

    $this->erp->from('pph23_payments')
      ->where('company_id', $this->company_id)
      ->where('pay_id', $pay_id);

    $this->apply_region_filter('region_id');

    $hdr = $this->erp->get()->row_array();

    if (!$hdr) {
      return ['ok' => false, 'http' => 404, 'message' => 'Settlement not found'];
    }

    if (strtoupper($hdr['status']) === 'CANCELLED') {
      return ['ok' => false, 'http' => 409, 'message' => 'Already cancelled'];
    }

    if ($this->_is_locked($hdr['posting_date'])) {
      return ['ok' => false, 'http' => 409, 'message' => 'PERIOD_LOCKED'];
    }

    $pay_no = $hdr['pay_no'];

    $this->erp->trans_begin();

    $this->erp->where('company_id', $this->company_id)
      ->where('voucher_type', 'WHTPAY')
      ->where('voucher_no', $pay_no);

    $this->apply_region_filter('region_id');

    $this->erp->update('gl_entries', ['status' => 'CANCELLED']);

    $this->erp->where('company_id', $this->company_id)
      ->where('pay_id', $pay_id);

    $this->apply_region_filter('region_id');

    $this->erp->update('pph23_payments', [
      'status'       => 'CANCELLED',
      'cancelled_at' => date('Y-m-d H:i:s'),
      'cancelled_by' => $this->auth_username()
    ]);

    if ($this->erp->trans_status() === false) {
      $this->erp->trans_rollback();
      return ['ok' => false, 'http' => 500, 'message' => 'DB error'];
    }

    $this->erp->trans_commit();

    return ['ok' => true, 'message' => 'PPH23 settlement cancelled', 'data' => [
      'pay_id'    => $pay_id,
      'pay_no'    => $pay_no,
      'region_id' => $hdr['region_id'] ?? $this->auth_region()
    ]];
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

  private function _next_series($series_key, $year)
  {
    $row = $this->erp->get_where('number_series', [
      'series_key' => $series_key,
      'company_id' => $this->company_id,
      'year'       => $year
    ])->row_array();

    if (!$row) {
      $prefix = $series_key . '-' . $year . '-';

      $this->erp->insert('number_series', [
        'series_key' => $series_key,
        'company_id' => $this->company_id,
        'year'       => $year,
        'prefix'     => $prefix,
        'counter'    => 0
      ]);

      $row = [
        'prefix'  => $prefix,
        'counter' => 0
      ];
    }

    $new_counter = (int)$row['counter'] + 1;

    $this->erp->where([
      'series_key' => $series_key,
      'company_id' => $this->company_id,
      'year'       => $year
    ])->update('number_series', [
      'counter' => $new_counter
    ]);

    return $row['prefix'] . str_pad((string)$new_counter, 5, '0', STR_PAD_LEFT);
  }

  private function _is_locked($posting_date)
  {
    $this->erp->select('MAX(lock_date) mx', false)
      ->from('accounting_locks')
      ->where('company_id', $this->company_id);

    if ($this->erp->field_exists('region_id', 'accounting_locks')) {
      $this->apply_region_filter('region_id');
    }

    $row = $this->erp->get()->row_array();
    $mx = $row['mx'] ?? null;

    return ($mx && $posting_date <= $mx);
  }

  private function _gl_voucher_posted($voucher_type, $voucher_no)
  {
    $this->erp->select('gl_id')
      ->from('gl_entries')
      ->where('company_id', $this->company_id)
      ->where('voucher_type', $voucher_type)
      ->where('voucher_no', $voucher_no)
      ->where('status', 'POSTED')
      ->limit(1);

    $this->apply_region_filter('region_id');

    return (bool)$this->erp->get()->row_array();
  }

  private function _pph23_paid_for_bill($bill_id)
  {
    $this->erp->select('COALESCE(SUM(r.allocated_amount),0) AS paid', false)
      ->from('pph23_payment_refs r')
      ->join('pph23_payments p', 'p.pay_id = r.pay_id', 'inner')
      ->where('r.bill_id', (int)$bill_id)
      ->where('p.company_id', $this->company_id)
      ->where('p.status', 'SUBMITTED');

    $this->apply_region_filter_alias('p', 'region_id');

    if ($this->erp->field_exists('region_id', 'pph23_payment_refs')) {
      $this->apply_region_filter_alias('r', 'region_id');
    }

    $row = $this->erp->get()->row_array();

    return (float)($row['paid'] ?? 0);
  }

  private function _post_gl_pph23($posting_date, $pay_no, $pph23_account_id, $cash_account_id, $amount, $refs)
  {
    $region_id = (string)$this->auth_region();

    foreach ($refs as $r) {
      $bill_id = (int)($r['bill_id'] ?? 0);
      $amt = (float)($r['allocated_amount'] ?? 0);

      if ($bill_id <= 0 || $amt <= 0) {
        continue;
      }

      $this->erp->select('source_key')
        ->from('ap_bills')
        ->where('company_id', $this->company_id)
        ->where('bill_id', $bill_id);

      $this->apply_region_filter('region_id');

      $bill = $this->erp->get()->row_array();
      $job_no = trim((string)($bill['source_key'] ?? ''));

      $this->erp->insert('gl_entries', [
        'company_id'   => $this->company_id,
        'region_id'    => $region_id,
        'posting_date' => $posting_date,
        'job_no'       => $job_no ?: null,
        'account_id'   => $pph23_account_id,
        'party_id'     => null,
        'debit'        => $amt,
        'credit'       => 0,
        'voucher_type' => 'WHTPAY',
        'voucher_no'   => $pay_no,
        'status'       => 'POSTED',
        'created_at'   => date('Y-m-d H:i:s')
      ]);
    }

    $this->erp->insert('gl_entries', [
      'company_id'   => $this->company_id,
      'region_id'    => $region_id,
      'posting_date' => $posting_date,
      'job_no'       => null,
      'account_id'   => $cash_account_id,
      'party_id'     => null,
      'debit'        => 0,
      'credit'       => $amount,
      'voucher_type' => 'WHTPAY',
      'voucher_no'   => $pay_no,
      'status'       => 'POSTED',
      'created_at'   => date('Y-m-d H:i:s')
    ]);
  }
}
