<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_import_cost extends MY_Model
{
  private $company_id = 1;

  // SOURCE yang didukung
  private $sources = [
    'inf_cost_tbl_satu',
    'inf_cost_refund_sc',
    'inf_cost_refund_discount',
    'x_glbcost'
  ];

  public function batches($p){
    $source = (string)($p['source'] ?? '');
    $vendor = trim((string)($p['vendor_code'] ?? ''));
    $region = trim((string)($p['region_id'] ?? $this->auth_region()));

    if (!in_array($source, $this->sources, true) || !$vendor) return [];

    // batch key:
    // - inf_cost_* : id_tarik
    // - x_glbcost  : `group`
    if ($source === 'x_glbcost') {
      $this->erp->select("g.`group` AS batch, COUNT(*) AS cnt, SUM(CAST(REPLACE(g.price,',','') AS DECIMAL(18,2))) AS total", false)
        ->from('x_glbcost g')
        ->where('g.payeble_to', $vendor)
        ->where("COALESCE(g.currency,'IDR') = 'IDR'", null, false);

      if ($region) $this->erp->where('g.region_id', $region);

      // skip yg sudah pernah diimport
      $this->erp->where("NOT EXISTS (
        SELECT 1 FROM ap_import_map m
        WHERE m.company_id = {$this->company_id}
          AND m.source_table = 'x_glbcost'
          AND m.source_id = g.id
      )", null, false);

      $this->erp->group_by('g.`group`')
        ->order_by('g.`group`','DESC');

      return $this->erp->get()->result_array();
    }

    // inf_cost_* (3 tabel) pakai id_tarik sebagai batch
    $t = $source;
    $this->erp->select("c.id_tarik AS batch, COUNT(*) AS cnt, SUM(CAST(REPLACE(c.price,',','') AS DECIMAL(18,2))) AS total", false)
      ->from("$t c")
      ->where('c.payable_to', $vendor)
      ->where("COALESCE(c.currency,'IDR') = 'IDR'", null, false);

    if ($region) $this->erp->where('c.region_id', $region);

    $this->erp->where("NOT EXISTS (
      SELECT 1 FROM ap_import_map m
      WHERE m.company_id = {$this->company_id}
        AND m.source_table = ".$this->erp->escape($t)."
        AND m.source_id = c.id
    )", null, false);

    $this->erp->group_by('c.id_tarik')
      ->order_by('c.id_tarik','DESC');

    return $this->erp->get()->result_array();
  }

  public function preview($p){
    $source = (string)($p['source'] ?? '');
    $vendor = trim((string)($p['vendor_code'] ?? ''));
    $batch  = trim((string)($p['batch'] ?? ''));
    $region = trim((string)($p['region_id'] ?? $this->auth_region()));

    $errs = [];
    if (!in_array($source, $this->sources, true)) $errs['source'] = 'invalid';
    if (!$vendor) $errs['vendor_code'] = 'required';
    if ($batch === '') $errs['batch'] = 'required';
    if ($errs) return ['ok'=>false,'http'=>422,'message'=>'Validation error','errors'=>$errs];

    $lines = $this->_fetch_lines($source, $vendor, $batch, $region);
    if (!$lines) return ['ok'=>false,'http'=>404,'message'=>'No lines to import'];

    $total = 0.0;
    foreach ($lines as $l) $total += (float)$l['amount'];

    return ['ok'=>true,'message'=>'OK','data'=>[
      'source'=>$source,
      'vendor_code'=>$vendor,
      'batch'=>$batch,
      'currency'=>'IDR',
      'total'=>$total,
      'lines'=>$lines
    ]];
  }

  public function submit($p){
    $source = (string)($p['source'] ?? '');
    $vendor = trim((string)($p['vendor_code'] ?? ''));
    $batch  = trim((string)($p['batch'] ?? ''));
    $posting_date = (string)($p['posting_date'] ?? date('Y-m-d'));
    $vendor_type  = strtoupper(trim((string)($p['vendor_type'] ?? 'OTHER')));
    $vendor_name  = trim((string)($p['vendor_name'] ?? $vendor));
    $remark       = trim((string)($p['remark'] ?? ''));
    $region       = trim((string)($p['region_id'] ?? $this->auth_region()));

    $errs = [];
    if (!in_array($source, $this->sources, true)) $errs['source'] = 'invalid';
    if (!$vendor) $errs['vendor_code'] = 'required';
    if ($batch === '') $errs['batch'] = 'required';
    if (!$posting_date) $errs['posting_date'] = 'required';
    if (!$vendor_type) $errs['vendor_type'] = 'required';
    if (!$vendor_name) $errs['vendor_name'] = 'required';
    if ($region === '') $errs['region_id'] = 'required';

    $ap_account_id  = $this->_coa_id('AP', $region);
    $hpp_account_id = $this->_coa_id('HPP', $region);

    if ($ap_account_id <= 0) {
      $errs['setup_ap'] = 'Missing/invalid AP account (' . $this->_coa_code('AP') . ')';
    }
    if ($hpp_account_id <= 0) {
      $errs['setup_hpp'] = 'Missing/invalid HPP account (' . $this->_coa_code('HPP') . ')';
    }

    if ($errs) return ['ok'=>false,'http'=>422,'message'=>'Validation error','errors'=>$errs];

    $lines = $this->_fetch_lines($source, $vendor, $batch, $region);
    if (!$lines) return ['ok'=>false,'http'=>404,'message'=>'No lines to import'];

    $total = 0.0;
    foreach ($lines as $l) $total += (float)$l['amount'];

    $this->erp->trans_begin();

    // create bill
    $bill_no = $this->_next_series('BILL', (int)date('Y', strtotime($posting_date)));
    $this->erp->insert('ap_bills', [
      'company_id'=>$this->company_id,
      'region_id'=>$region,
      'bill_no'=>$bill_no,
      'vendor_type'=>$vendor_type,
      'vendor_code'=>$vendor,
      'vendor_name'=>$vendor_name,
      'posting_date'=>$posting_date,
      'currency'=>'IDR',
      'remark'=>$remark ?: ("Import {$source} batch {$batch}"),
      'status'=>'SUBMITTED',
      'total'=>$total,
      'paid_total'=>0,
      'created_by'=>null
    ]);
    $bill_id = (int)$this->erp->insert_id();

    // items + import_map
    $line_no = 1;
    foreach ($lines as $l) {
      // Direct HPP mode: imported operational cost is expensed immediately.
      $this->erp->insert('ap_bill_items', [
        'bill_id'=>$bill_id,
        'line_no'=>$line_no++,
        'expense_type'=>'HPP',
        'account_id'=>$hpp_account_id,
        'job_id'=>$l['job_id'] ?: null,
        'memo'=>$l['memo'],
        'amount'=>$l['amount']
      ]);

      // map imported source row
      $mapData = [
        'company_id'=>$this->company_id,
        'source_table'=>$source,
        'source_id'=>$l['source_id'],
        'bill_id'=>$bill_id
      ];
      if ($this->erp->field_exists('region_id', 'ap_import_map')) {
        $mapData['region_id'] = $region;
      }
      $this->erp->insert('ap_import_map', $mapData);
    }

    // post GL: Dr HPP per item, Cr AP total
    foreach ($lines as $l) {
      $this->erp->insert('gl_entries', [
        'company_id'=>$this->company_id,
        'region_id'=>$region,
        'posting_date'=>$posting_date,
        'account_id'=>$hpp_account_id,
        'party_id'=>null,
        'debit'=>$l['amount'],
        'credit'=>0,
        'voucher_type'=>'BILL',
        'voucher_no'=>$bill_no,
        'status'=>'POSTED',
        'created_at'=>date('Y-m-d H:i:s')
      ]);
    }
    $this->erp->insert('gl_entries', [
      'company_id'=>$this->company_id,
      'region_id'=>$region,
      'posting_date'=>$posting_date,
      'account_id'=>$ap_account_id,
      'party_id'=>null,
      'debit'=>0,
      'credit'=>$total,
      'voucher_type'=>'BILL',
      'voucher_no'=>$bill_no,
      'status'=>'POSTED',
      'created_at'=>date('Y-m-d H:i:s')
    ]);

    if ($this->erp->trans_status()===false) {
      $this->erp->trans_rollback();
      return ['ok'=>false,'http'=>500,'message'=>'DB error'];
    }
    $this->erp->trans_commit();

    return ['ok'=>true,'message'=>'Imported bill created','data'=>[
      'bill_id'=>$bill_id,
      'bill_no'=>$bill_no,
      'total'=>$total,
      'source'=>$source,
      'batch'=>$batch,
      'vendor_code'=>$vendor,
      'region_id'=>$region,
      'posting_mode'=>'DIRECT_HPP'
    ]];
  }

  // -------- internal: fetch lines --------
  private function _fetch_lines($source, $vendor, $batch, $region='')
  {
    if ($source === 'x_glbcost') {
      $this->erp->select("
        g.id AS source_id,
        g.job_no AS job_id,
        CONCAT(COALESCE(g.item_service,''),' ',COALESCE(g.remark,'')) AS memo,
        CAST(REPLACE(g.price,',','') AS DECIMAL(18,2)) AS amount
      ", false)
      ->from('x_glbcost g')
      ->where('g.payeble_to', $vendor)
      ->where('g.`group`', $batch)
      ->where("COALESCE(g.currency,'IDR')='IDR'", null, false);

      if ($region) $this->erp->where('g.region_id', $region);

      $this->erp->where("NOT EXISTS (
        SELECT 1 FROM ap_import_map m
        WHERE m.company_id = {$this->company_id}
          AND m.source_table = 'x_glbcost'
          AND m.source_id = g.id
      )", null, false);

      $rows = $this->erp->get()->result_array();
      return $this->_normalize_lines($rows);
    }

    // inf_cost_* tables: batch=id_tarik, vendor=payable_to
    $t = $source;
    $this->erp->select("
      c.id AS source_id,
      c.kode_ref AS job_id,
      CONCAT(COALESCE(c.item_service,''),' ',COALESCE(c.remark,'')) AS memo,
      CAST(REPLACE(c.price,',','') AS DECIMAL(18,2)) AS amount
    ", false)
    ->from("$t c")
    ->where('c.payable_to', $vendor)
    ->where('c.id_tarik', $batch)
    ->where("COALESCE(c.currency,'IDR')='IDR'", null, false);

    if ($region) $this->erp->where('c.region_id', $region);

    $this->erp->where("NOT EXISTS (
      SELECT 1 FROM ap_import_map m
      WHERE m.company_id = {$this->company_id}
        AND m.source_table = ".$this->erp->escape($t)."
        AND m.source_id = c.id
    )", null, false);

    $rows = $this->erp->get()->result_array();
    return $this->_normalize_lines($rows);
  }

  private function _normalize_lines($rows){
    $out = [];
    foreach ($rows as $r) {
      $amt = (float)$r['amount'];
      if ($amt <= 0) continue;
      $out[] = [
        'source_id' => (int)$r['source_id'],
        'job_id' => $r['job_id'] ?? null,
        'memo' => trim((string)$r['memo']),
        'amount' => $amt
      ];
    }
    return $out;
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

  private function _next_series($series_key, $year){
    $row = $this->erp->get_where('number_series', [
      'series_key'=>$series_key,'company_id'=>$this->company_id,'year'=>$year
    ])->row_array();

    if(!$row){
      $prefix = $series_key.'-'.$year.'-';
      $this->erp->insert('number_series', [
        'series_key'=>$series_key,'company_id'=>$this->company_id,'year'=>$year,'prefix'=>$prefix,'counter'=>0
      ]);
      $row = ['prefix'=>$prefix,'counter'=>0];
    }

    $new_counter = (int)$row['counter'] + 1;
    $this->erp->where(['series_key'=>$series_key,'company_id'=>$this->company_id,'year'=>$year])
      ->update('number_series', ['counter'=>$new_counter]);

    return $row['prefix'] . str_pad((string)$new_counter, 5, '0', STR_PAD_LEFT);
  }
}