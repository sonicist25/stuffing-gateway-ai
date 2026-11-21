<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_reports extends MY_Model
{
  private $company_id = 1;

  public function ap_aging($p){
    $asOf = !empty($p['as_of']) ? $p['as_of'] : date('Y-m-d');
    $status = strtoupper((string)($p['status'] ?? 'ALL'));
    $vendorType = strtoupper(trim((string)($p['vendor_type'] ?? '')));

    $this->erp->select("
      b.bill_id, b.bill_no, b.posting_date,
      DATEDIFF(".$this->erp->escape($asOf).", b.posting_date) AS age_days,
      b.vendor_type, b.vendor_code, b.vendor_name,b.currency,
      b.total, b.paid_total,
      (b.total - b.paid_total) AS outstanding
    ", false)
    ->from('ap_bills b')
    ->where('b.company_id', $this->company_id)
    ->where('b.status !=', 'CANCELLED')
    ->where('(b.total - b.paid_total) > 0', null, false);

    if($vendorType) $this->erp->where('b.vendor_type', $vendorType);

    if($status==='UNPAID')  $this->erp->where('b.paid_total <= 0', null, false);
    if($status==='PARTIAL') $this->erp->where('b.paid_total > 0 AND (b.total - b.paid_total) > 0', null, false);

    $rows = $this->erp->order_by('age_days','DESC')->get()->result_array();

    $summary = [];
    $details = [];

    foreach($rows as $r){
      $age = (int)$r['age_days'];
      $amt = (float)$r['outstanding'];
      $bucket = ($age<=30) ? '0_30' : (($age<=60) ? '31_60' : (($age<=90) ? '61_90' : '90_plus'));

      $key = $r['vendor_name'] ?: '(UNKNOWN)';
      if(!isset($summary[$key])) $summary[$key] = ['0_30'=>0,'31_60'=>0,'61_90'=>0,'90_plus'=>0,'total'=>0];

      $summary[$key][$bucket] += $amt;
      $summary[$key]['total'] += $amt;

      $r['bucket'] = $bucket;
      $r['total'] = (float)$r['total'];
      $r['paid_total'] = (float)$r['paid_total'];
      $r['outstanding'] = $amt;

      $details[] = $r;
    }

    foreach($summary as $k=>&$v){
      foreach($v as $bk=>$val) $v[$bk] = (float)$val;
    }
    unset($v);

    return [
      'as_of'=>$asOf,
      'status'=>$status,
      'vendor_type'=>$vendorType ?: null,
      'summary'=>$summary,
      'details'=>$details
    ];
  }
}