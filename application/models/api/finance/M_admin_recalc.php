<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_admin_recalc extends MY_Model
{
  private $company_id = 1;

  // POST body optional: { "limit": 5000 }
  public function recalc_ap_bills($p)
  {
    $limit = (int)($p['limit'] ?? 50000);
    if ($limit <= 0) $limit = 50000;

    $this->erp->trans_begin();

    // paid_total = SUM refs where payment status SUBMITTED
    $sql = "
      UPDATE ap_bills b
      LEFT JOIN (
        SELECT r.bill_id, COALESCE(SUM(r.allocated_amount),0) AS paid
        FROM ap_payment_refs r
        INNER JOIN ap_payments p ON p.pay_id = r.pay_id
        WHERE p.company_id = ?
          AND p.status = 'SUBMITTED'
        GROUP BY r.bill_id
      ) x ON x.bill_id = b.bill_id
      SET b.paid_total = COALESCE(x.paid,0)
      WHERE b.company_id = ?
      LIMIT {$limit}
    ";
    $this->erp->query($sql, [$this->company_id, $this->company_id]);

    if ($this->erp->trans_status()===false){
      $err = $this->erp->error();
      $this->erp->trans_rollback();
      return ['ok'=>false,'http'=>500,'message'=>'DB error','data'=>['db_error'=>$err]];
    }
    $this->erp->trans_commit();

    return ['ok'=>true,'message'=>'OK','data'=>['updated_limit'=>$limit]];
  }

  // Recalc x_finance.overdue/status/tgl_bayar from receipts SUBMITTED
  // POST body optional: { "limit": 5000 }
  public function recalc_ar_invoices($p)
  {
    $limit = (int)($p['limit'] ?? 50000);
    if ($limit <= 0) $limit = 50000;

    $this->erp->trans_begin();

    // 1) overdue = SUM allocated_amount from SUBMITTED receipts
    $sql1 = "
      UPDATE x_finance f
      LEFT JOIN (
        SELECT rr.finance_id, COALESCE(SUM(rr.allocated_amount),0) AS paid
        FROM ar_receipt_refs rr
        INNER JOIN ar_receipts r ON r.rcpt_id = rr.rcpt_id
        WHERE r.company_id = ?
          AND r.status = 'SUBMITTED'
        GROUP BY rr.finance_id
      ) x ON x.finance_id = f.id
      SET f.overdue = COALESCE(x.paid,0)
      LIMIT {$limit}
    ";
    $this->erp->query($sql1, [$this->company_id]);

    // 2) status berdasarkan paid vs grand_total
    $sql2 = "
      UPDATE x_finance
      SET status =
        CASE
          WHEN COALESCE(grand_total,0) <= 0 THEN status
          WHEN COALESCE(overdue,0) >= COALESCE(grand_total,0) - 0.01 THEN 'paid'
          WHEN COALESCE(overdue,0) > 0.01 THEN 'partial'
          ELSE 'unpaid'
        END
      LIMIT {$limit}
    ";
    $this->erp->query($sql2);

    // 3) tgl_bayar = MAX(posting_date) dari receipts SUBMITTED untuk finance_id (YYYYMMDD)
    // kalau tidak ada payment -> ''
    $sql3 = "
      UPDATE x_finance f
      LEFT JOIN (
        SELECT rr.finance_id, DATE_FORMAT(MAX(r.posting_date),'%Y%m%d') AS last_pay
        FROM ar_receipt_refs rr
        INNER JOIN ar_receipts r ON r.rcpt_id = rr.rcpt_id
        WHERE r.company_id = ?
          AND r.status = 'SUBMITTED'
        GROUP BY rr.finance_id
      ) t ON t.finance_id = f.id
      SET f.tgl_bayar = COALESCE(t.last_pay,'')
      LIMIT {$limit}
    ";
    $this->erp->query($sql3, [$this->company_id]);

    if ($this->erp->trans_status()===false){
      $err = $this->erp->error();
      $this->erp->trans_rollback();
      return ['ok'=>false,'http'=>500,'message'=>'DB error','data'=>['db_error'=>$err]];
    }
    $this->erp->trans_commit();

    return ['ok'=>true,'message'=>'OK','data'=>['updated_limit'=>$limit]];
  }
}