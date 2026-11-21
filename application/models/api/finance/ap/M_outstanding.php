<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_outstanding extends MY_Model
{
  private $company_id = 1;

  public function list($p)
  {
    $limit  = (int)($p['limit'] ?? 200); if ($limit<=0) $limit=200;
    $offset = (int)($p['offset'] ?? 0); if ($offset<0) $offset=0;

    $this->erp->select("
      b.bill_id,
      b.bill_no,
      b.posting_date,
      b.source_key AS job_no,
      b.vendor_code,
      b.vendor_name,
      b.total,
      b.paid_total,
      (b.total - b.paid_total) AS outstanding,
      b.status
    ", false)
    ->from('ap_bills b')
    ->where('b.company_id', $this->company_id);

    // only outstanding
    $this->erp->where('(b.total - b.paid_total) >', 0.01, false);

    if (!empty($p['vendor_code'])) $this->erp->where('b.vendor_code', $p['vendor_code']);

    if (!empty($p['search'])) {
      $q = $p['search'];
      $this->erp->group_start()
        ->like('b.bill_no', $q)
        ->or_like('b.source_key', $q)
        ->or_like('b.vendor_name', $q)
      ->group_end();
    }

    $this->erp->order_by('b.posting_date','DESC')->order_by('b.bill_id','DESC');

    $total = (int)$this->erp->count_all_results('', false);

    $rows = $this->erp->limit($limit, $offset)->get()->result_array();
    foreach ($rows as &$r) {
      $r['total'] = (float)$r['total'];
      $r['paid_total'] = (float)$r['paid_total'];
      $r['outstanding'] = (float)$r['outstanding'];
    }
    unset($r);

    return [
      'limit'=>$limit,'offset'=>$offset,'total'=>$total,'rows'=>$rows
    ];
  }
}