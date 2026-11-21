<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_vendor_deposits extends MY_Model
{
  private $company_id = 1;

  public function list($p = [])
  {
    $this->erp->select("
      d.deposit_id,
      d.company_id,
      d.region_id,
      d.vendor_type,
      d.vendor_code,
      d.vendor_name,
      d.posting_date,
      d.currency,
      d.kurs,
      d.amount,
      d.used_amount,
      d.balance_amount,
      d.source_type,
      d.source_pay_id,
      d.source_pay_no,
      d.source_bill_id,
      d.source_bill_no,
      d.remark,
      d.status,
      d.created_at,
      d.updated_at
    ", false)
    ->from('ap_vendor_deposits d')
    ->where('d.company_id', $this->company_id);

    $this->apply_region_filter_alias('d', 'region_id');

    if (!empty($p['vendor_code'])) {
      $this->erp->where('d.vendor_code', $p['vendor_code']);
    }

    if (!empty($p['vendor_name'])) {
      $this->erp->like('d.vendor_name', $p['vendor_name']);
    }

    if (!empty($p['status'])) {
      $this->erp->where('d.status', strtoupper($p['status']));
    } else {
      // Default list hanya yang masih bisa dipakai
      $this->erp->where('d.status !=', 'CANCELLED');
    }

    if (!empty($p['currency'])) {
      $this->erp->where('d.currency', strtoupper($p['currency']));
    }

    if (!empty($p['date_from'])) {
      $this->erp->where('d.posting_date >=', $p['date_from']);
    }

    if (!empty($p['date_to'])) {
      $this->erp->where('d.posting_date <=', $p['date_to']);
    }

    if (!empty($p['search'])) {
      $q = $p['search'];
      $this->erp->group_start()
        ->like('d.vendor_code', $q)
        ->or_like('d.vendor_name', $q)
        ->or_like('d.source_pay_no', $q)
        ->or_like('d.source_bill_no', $q)
        ->or_like('d.remark', $q)
      ->group_end();
    }

    $rows = $this->erp
      ->order_by('d.posting_date', 'DESC')
      ->order_by('d.deposit_id', 'DESC')
      ->get()
      ->result_array();

    foreach ($rows as &$r) {
      $r['amount'] = (float)$r['amount'];
      $r['used_amount'] = (float)$r['used_amount'];
      $r['balance_amount'] = (float)$r['balance_amount'];
      $r['kurs'] = (float)$r['kurs'];
    }
    unset($r);

    return $rows;
  }

  public function summary($p = [])
  {
    $this->erp->select("
      d.vendor_type,
      d.vendor_code,
      d.vendor_name,
      d.currency,
      COUNT(*) AS deposit_count,
      COALESCE(SUM(d.amount),0) AS total_amount,
      COALESCE(SUM(d.used_amount),0) AS total_used,
      COALESCE(SUM(d.balance_amount),0) AS total_balance
    ", false)
    ->from('ap_vendor_deposits d')
    ->where('d.company_id', $this->company_id)
    ->where('d.status', 'OPEN')
    ->where('d.balance_amount >', 0);

    $this->apply_region_filter_alias('d', 'region_id');

    if (!empty($p['vendor_code'])) {
      $this->erp->where('d.vendor_code', $p['vendor_code']);
    }

    if (!empty($p['search'])) {
      $q = $p['search'];
      $this->erp->group_start()
        ->like('d.vendor_code', $q)
        ->or_like('d.vendor_name', $q)
      ->group_end();
    }

    $rows = $this->erp
      ->group_by('d.vendor_type, d.vendor_code, d.vendor_name, d.currency')
      ->order_by('d.vendor_name', 'ASC')
      ->get()
      ->result_array();

    foreach ($rows as &$r) {
      $r['deposit_count'] = (int)$r['deposit_count'];
      $r['total_amount'] = (float)$r['total_amount'];
      $r['total_used'] = (float)$r['total_used'];
      $r['total_balance'] = (float)$r['total_balance'];
    }
    unset($r);

    return $rows;
  }

  public function history($p = [])
  {
    $useRegionJoin = '';

    if (!$this->is_super_admin()) {
      $region = (string)$this->auth_region();

      if ($region !== '') {
        $useRegionJoin = ' AND u.region_id = ' . $this->erp->escape($region);
      }
    }

    $this->erp->select("
      d.deposit_id,
      d.region_id,
      d.vendor_type,
      d.vendor_code,
      d.vendor_name,
      d.currency,
      d.amount AS deposit_amount,
      d.used_amount,
      d.balance_amount,
      d.source_pay_no,
      d.source_bill_no,
      d.status AS deposit_status,

      u.use_id,
      u.pay_id,
      u.pay_no,
      u.bill_id,
      u.bill_no,
      u.posting_date AS use_posting_date,
      u.amount AS use_amount,
      u.remark AS use_remark,
      u.status AS use_status,
      u.created_at AS use_created_at
    ", false)
    ->from('ap_vendor_deposits d')
    ->join('ap_vendor_deposit_uses u', 'u.deposit_id = d.deposit_id AND u.company_id = d.company_id' . $useRegionJoin, 'left', false)
    ->where('d.company_id', $this->company_id);

    $this->apply_region_filter_alias('d', 'region_id');

    if (!empty($p['vendor_code'])) {
      $this->erp->where('d.vendor_code', $p['vendor_code']);
    }

    if (!empty($p['vendor_name'])) {
      $this->erp->like('d.vendor_name', $p['vendor_name']);
    }

    if (!empty($p['date_from'])) {
      $this->erp->where('d.posting_date >=', $p['date_from']);
    }

    if (!empty($p['date_to'])) {
      $this->erp->where('d.posting_date <=', $p['date_to']);
    }

    if (!empty($p['search'])) {
      $q = $p['search'];
      $this->erp->group_start()
        ->like('d.vendor_code', $q)
        ->or_like('d.vendor_name', $q)
        ->or_like('d.source_pay_no', $q)
        ->or_like('d.source_bill_no', $q)
        ->or_like('u.pay_no', $q)
        ->or_like('u.bill_no', $q)
      ->group_end();
    }

    $rows = $this->erp
      ->order_by('d.deposit_id', 'DESC')
      ->order_by('u.use_id', 'DESC')
      ->get()
      ->result_array();

    foreach ($rows as &$r) {
      $r['deposit_amount'] = (float)$r['deposit_amount'];
      $r['used_amount'] = (float)$r['used_amount'];
      $r['balance_amount'] = (float)$r['balance_amount'];
      $r['use_amount'] = (float)($r['use_amount'] ?? 0);
    }
    unset($r);

    return $rows;
  }

  public function get($deposit_id)
  {
    $deposit_id = (int)$deposit_id;

    $this->erp->from('ap_vendor_deposits')
      ->where('company_id', $this->company_id)
      ->where('deposit_id', $deposit_id);

    $this->apply_region_filter('region_id');

    $hdr = $this->erp->get()->row_array();

    if (!$hdr) return null;

    $this->erp->from('ap_vendor_deposit_uses')
      ->where('company_id', $this->company_id)
      ->where('deposit_id', $deposit_id);

    $this->apply_region_filter('region_id');

    $uses = $this->erp
      ->order_by('use_id', 'DESC')
      ->get()
      ->result_array();

    $hdr['amount'] = (float)$hdr['amount'];
    $hdr['used_amount'] = (float)$hdr['used_amount'];
    $hdr['balance_amount'] = (float)$hdr['balance_amount'];

    foreach ($uses as &$u) {
      $u['amount'] = (float)$u['amount'];
    }
    unset($u);

    $hdr['uses'] = $uses;

    return $hdr;
  }

  public function create_from_payment($data)
  {
    $amount = round((float)($data['amount'] ?? 0), 2);

    if ($amount <= 0) {
      return ['ok' => false, 'message' => 'Deposit amount must be > 0'];
    }

    $vendor_code = trim((string)($data['vendor_code'] ?? ''));
    $vendor_name = trim((string)($data['vendor_name'] ?? ''));

    if ($vendor_code === '' && $vendor_name === '') {
      return ['ok' => false, 'message' => 'Vendor is required for deposit'];
    }

    $region_id = (string)$this->auth_region();

    $row = [
      'company_id'      => $this->company_id,
      'region_id'       => $region_id,
      'vendor_type'     => $data['vendor_type'] ?? null,
      'vendor_code'     => $vendor_code ?: null,
      'vendor_name'     => $vendor_name ?: null,

      'posting_date'    => $data['posting_date'] ?? date('Y-m-d'),
      'currency'        => strtoupper($data['currency'] ?? 'IDR'),
      'kurs'            => (float)($data['kurs'] ?? 1),

      'amount'          => $amount,
      'used_amount'     => 0,
      'balance_amount'  => $amount,

      'source_type'     => $data['source_type'] ?? 'APPAY',
      'source_pay_id'   => $data['source_pay_id'] ?? null,
      'source_pay_no'   => $data['source_pay_no'] ?? null,
      'source_bill_id'  => $data['source_bill_id'] ?? null,
      'source_bill_no'  => $data['source_bill_no'] ?? null,

      'remark'          => $data['remark'] ?? null,
      'status'          => 'OPEN',
      'created_by'      => $data['created_by'] ?? $this->auth_username(),
      'created_at'      => date('Y-m-d H:i:s'),
    ];

    $this->erp->insert('ap_vendor_deposits', $row);
    $deposit_id = (int)$this->erp->insert_id();

    if ($deposit_id <= 0) {
      return [
        'ok' => false,
        'message' => 'Failed to create vendor deposit',
        'error' => $this->erp->error(),
      ];
    }

    return [
      'ok' => true,
      'message' => 'Vendor deposit created',
      'data' => [
        'deposit_id' => $deposit_id,
        'region_id' => $region_id,
        'amount' => $amount,
      ],
    ];
  }

  public function use_deposit($data)
  {
    $deposit_id = (int)($data['deposit_id'] ?? 0);
    $amount = round((float)($data['amount'] ?? 0), 2);

    if ($deposit_id <= 0) {
      return ['ok' => false, 'message' => 'deposit_id is required'];
    }

    if ($amount <= 0) {
      return ['ok' => false, 'message' => 'Use amount must be > 0'];
    }

    $this->erp->where('company_id', $this->company_id)
      ->where('deposit_id', $deposit_id)
      ->where('status', 'OPEN');

    $this->apply_region_filter('region_id');

    $dep = $this->erp->get('ap_vendor_deposits')->row_array();

    if (!$dep) {
      return ['ok' => false, 'message' => 'Deposit not found or not open'];
    }

    $balance = (float)$dep['balance_amount'];

    if ($amount - $balance > 0.01) {
      return ['ok' => false, 'message' => 'Use amount exceeds deposit balance'];
    }

    $region_id = (string)($dep['region_id'] ?? $this->auth_region());

    $useRow = [
      'company_id'   => $this->company_id,
      'region_id'    => $region_id,
      'deposit_id'   => $deposit_id,
      'pay_id'       => $data['pay_id'] ?? null,
      'pay_no'       => $data['pay_no'] ?? null,
      'bill_id'      => $data['bill_id'] ?? null,
      'bill_no'      => $data['bill_no'] ?? null,
      'posting_date' => $data['posting_date'] ?? date('Y-m-d'),
      'amount'       => $amount,
      'remark'       => $data['remark'] ?? null,
      'status'       => 'POSTED',
      'created_by'   => $data['created_by'] ?? $this->auth_username(),
      'created_at'   => date('Y-m-d H:i:s'),
    ];

    $this->erp->insert('ap_vendor_deposit_uses', $useRow);
    $use_id = (int)$this->erp->insert_id();

    if ($use_id <= 0) {
      return [
        'ok' => false,
        'message' => 'Failed to use deposit',
        'error' => $this->erp->error(),
      ];
    }

    $newUsed = (float)$dep['used_amount'] + $amount;
    $newBal = (float)$dep['amount'] - $newUsed;
    if ($newBal < 0.01) $newBal = 0;

    $newStatus = $newBal <= 0.01 ? 'CLOSED' : 'OPEN';

    $this->erp
      ->where('company_id', $this->company_id)
      ->where('deposit_id', $deposit_id);

    $this->apply_region_filter('region_id');

    $this->erp->update('ap_vendor_deposits', [
        'used_amount' => $newUsed,
        'balance_amount' => $newBal,
        'status' => $newStatus,
        'updated_at' => date('Y-m-d H:i:s'),
      ]);

    return [
      'ok' => true,
      'message' => 'Deposit used',
      'data' => [
        'use_id' => $use_id,
        'deposit_id' => $deposit_id,
        'region_id' => $region_id,
        'amount' => $amount,
        'balance_amount' => $newBal,
        'status' => $newStatus,
      ],
    ];
  }

  public function cancel_uses_by_payment($pay_id, $pay_no = null)
  {
    $pay_id = (int)$pay_id;

    if ($pay_id <= 0 && empty($pay_no)) {
      return ['ok' => false, 'message' => 'pay_id/pay_no required'];
    }

    $this->erp->from('ap_vendor_deposit_uses')
      ->where('company_id', $this->company_id)
      ->where('status', 'POSTED');

    $this->apply_region_filter('region_id');

    if ($pay_id > 0) {
      $this->erp->where('pay_id', $pay_id);
    } else {
      $this->erp->where('pay_no', $pay_no);
    }

    $uses = $this->erp->get()->result_array();

    foreach ($uses as $u) {
      $deposit_id = (int)$u['deposit_id'];
      $amount = (float)$u['amount'];

      $this->erp->where('company_id', $this->company_id)
        ->where('deposit_id', $deposit_id);

      $this->apply_region_filter('region_id');

      $dep = $this->erp->get('ap_vendor_deposits')->row_array();

      if (!$dep) continue;

      $newUsed = max(0, (float)$dep['used_amount'] - $amount);
      $newBal = (float)$dep['amount'] - $newUsed;
      $newStatus = $newBal > 0.01 ? 'OPEN' : 'CLOSED';

      $this->erp
        ->where('company_id', $this->company_id)
        ->where('use_id', (int)$u['use_id']);

      $this->apply_region_filter('region_id');

      $this->erp->update('ap_vendor_deposit_uses', [
          'status' => 'CANCELLED',
          'cancelled_at' => date('Y-m-d H:i:s'),
        ]);

      $this->erp
        ->where('company_id', $this->company_id)
        ->where('deposit_id', $deposit_id);

      $this->apply_region_filter('region_id');

      $this->erp->update('ap_vendor_deposits', [
          'used_amount' => $newUsed,
          'balance_amount' => $newBal,
          'status' => $newStatus,
          'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    return ['ok' => true, 'message' => 'Deposit uses cancelled'];
  }

  public function cancel_created_by_payment($pay_id, $pay_no = null)
  {
    $pay_id = (int)$pay_id;

    $this->erp->where('company_id', $this->company_id)
      ->where('status !=', 'CANCELLED');

    $this->apply_region_filter('region_id');

    if ($pay_id > 0) {
      $this->erp->where('source_pay_id', $pay_id);
    } else {
      $this->erp->where('source_pay_no', $pay_no);
    }

    $deps = $this->erp->get('ap_vendor_deposits')->result_array();

    foreach ($deps as $d) {
      if ((float)$d['used_amount'] > 0.01) {
        return [
          'ok' => false,
          'message' => 'Cannot cancel payment: created vendor deposit already used',
          'data' => [
            'deposit_id' => (int)$d['deposit_id'],
            'source_pay_no' => $d['source_pay_no'],
            'used_amount' => (float)$d['used_amount'],
          ],
        ];
      }

      $this->erp
        ->where('company_id', $this->company_id)
        ->where('deposit_id', (int)$d['deposit_id']);

      $this->apply_region_filter('region_id');

      $this->erp->update('ap_vendor_deposits', [
          'status' => 'CANCELLED',
          'balance_amount' => 0,
          'updated_at' => date('Y-m-d H:i:s'),
          'cancelled_at' => date('Y-m-d H:i:s'),
        ]);
    }

    return ['ok' => true, 'message' => 'Created deposits cancelled'];
  }
}
