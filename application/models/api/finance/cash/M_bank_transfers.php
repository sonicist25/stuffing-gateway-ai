<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_bank_transfers extends MY_Model
{
  private $company_id = 1;

  public function list($p){
    $this->erp->select("t.trf_id,t.trf_no,t.posting_date,t.from_account_id,t.to_account_id,t.amount,t.remark,t.status", false)
      ->from('bank_transfers t')
      ->where('t.company_id', $this->company_id);

    if (!empty($p['status'])) $this->erp->where('t.status', strtoupper($p['status']));
    if (!empty($p['search'])) {
      $q = $p['search'];
      $this->erp->group_start()
        ->like('t.trf_no', $q)
        ->or_like('t.remark', $q)
      ->group_end();
    }
    if (!empty($p['date_from'])) $this->erp->where('t.posting_date >=', $p['date_from']);
    if (!empty($p['date_to']))   $this->erp->where('t.posting_date <=', $p['date_to']);

    $this->erp->order_by('t.posting_date','DESC')->order_by('t.trf_id','DESC');
    $rows = $this->erp->get()->result_array();

    // attach account labels
    if ($rows) {
      $ids = [];
      foreach ($rows as $r) {
        $ids[] = (int)$r['from_account_id'];
        $ids[] = (int)$r['to_account_id'];
      }
      $ids = array_values(array_unique(array_filter($ids)));

      $map = [];
      if ($ids) {
        $acc = $this->erp->select('account_id,account_code,account_name')
          ->where('company_id',$this->company_id)
          ->where_in('account_id',$ids)
          ->get('accounts')->result_array();
        foreach($acc as $a){
          $map[(int)$a['account_id']] = $a['account_code'].' - '.$a['account_name'];
        }
      }

      foreach($rows as &$r){
        $r['amount'] = (float)$r['amount'];
        $r['from_account_label'] = $map[(int)$r['from_account_id']] ?? null;
        $r['to_account_label']   = $map[(int)$r['to_account_id']] ?? null;
      }
      unset($r);
    }

    return $rows;
  }

  public function get($trf_id){
    $row = $this->erp->get_where('bank_transfers', [
      'company_id'=>$this->company_id,
      'trf_id'=>$trf_id
    ])->row_array();
    if(!$row) return null;

    $row['amount'] = (float)$row['amount'];
    return $row;
  }

  public function create($p){
    $posting_date    = (string)($p['posting_date'] ?? '');
    $from_account_id = (int)($p['from_account_id'] ?? 0);
    $to_account_id   = (int)($p['to_account_id'] ?? 0);
    $amount          = (float)($p['amount'] ?? 0);
    $remark          = trim((string)($p['remark'] ?? ''));

    $errs = [];
    if(!$posting_date) $errs['posting_date']='required';
    if($from_account_id<=0) $errs['from_account_id']='required';
    if($to_account_id<=0) $errs['to_account_id']='required';
    if($from_account_id>0 && $to_account_id>0 && $from_account_id===$to_account_id) $errs['to_account_id']='must be different';
    if($amount<=0) $errs['amount']='must be > 0';
    if($errs) return ['ok'=>false,'http'=>422,'message'=>'Validation error','errors'=>$errs];

    if ($this->_is_locked($posting_date)) {
      return ['ok'=>false,'http'=>409,'message'=>'PERIOD_LOCKED'];
    }

    // validate accounts postable & active
    $from = $this->_get_cash_account($from_account_id);
    if(!$from) return ['ok'=>false,'http'=>422,'message'=>'Invalid from_account_id'];

    $to = $this->_get_cash_account($to_account_id);
    if(!$to) return ['ok'=>false,'http'=>422,'message'=>'Invalid to_account_id'];

    $this->erp->trans_begin();

    $trf_no = $this->_next_series('BTRF', (int)date('Y', strtotime($posting_date)));

    // header
    $this->erp->insert('bank_transfers', [
      'company_id'=>$this->company_id,
      'trf_no'=>$trf_no,
      'posting_date'=>$posting_date,
      'from_account_id'=>$from_account_id,
      'to_account_id'=>$to_account_id,
      'amount'=>$amount,
      'remark'=>$remark ?: null,
      'status'=>'SUBMITTED',
      'created_by'=>null
    ]);
    $trf_id = (int)$this->erp->insert_id();

    // idempotent guard GL
    if ($this->_gl_voucher_posted('BTRF', $trf_no)) {
      $this->erp->trans_rollback();
      return ['ok'=>false,'http'=>409,'message'=>'GL_ALREADY_POSTED','data'=>['voucher_type'=>'BTRF','voucher_no'=>$trf_no]];
    }

    // post GL (Dr to, Cr from)
    $this->_post_gl_btrf($posting_date, $trf_no, $from_account_id, $to_account_id, $amount);

    if($this->erp->trans_status()===false){
      $err = $this->erp->error();
      $sql = $this->erp->last_query();
      $this->erp->trans_rollback();
      return ['ok'=>false,'http'=>500,'message'=>'DB error','data'=>['db_error'=>$err,'last_query'=>$sql]];
    }
    $this->erp->trans_commit();

    return ['ok'=>true,'message'=>'Bank transfer posted','data'=>[
      'trf_id'=>$trf_id,
      'trf_no'=>$trf_no,
      'posting_date'=>$posting_date,
      'amount'=>$amount
    ]];
  }

  public function cancel($trf_id){
    $hdr = $this->erp->get_where('bank_transfers', [
      'company_id'=>$this->company_id,
      'trf_id'=>$trf_id
    ])->row_array();
    if(!$hdr) return ['ok'=>false,'http'=>404,'message'=>'Transfer not found'];
    if(strtoupper($hdr['status'])==='CANCELLED') return ['ok'=>false,'http'=>409,'message'=>'Already cancelled'];

    if ($this->_is_locked($hdr['posting_date'])) {
      return ['ok'=>false,'http'=>409,'message'=>'PERIOD_LOCKED'];
    }

    $trf_no = $hdr['trf_no'];
	// ===== GUARD: cannot cancel if reconciled in bank statement =====
	$lock = $this->_get_reconciled_lock('BTRF', $trf_no);
	if ($lock) {
	  return [
		'ok'=>false,
		'http'=>409,
		'message'=>'CANNOT_CANCEL_RECONCILED',
		'data'=>['lock'=>$lock]
	  ];
	}
    $this->erp->trans_begin();

    // OPTIONAL guard: kalau GL belum pernah ada, kamu bisa return 409
    // kalau tidak, update CANCELLED tetap aman.
    $this->erp->where([
      'company_id'=>$this->company_id,
      'voucher_type'=>'BTRF',
      'voucher_no'=>$trf_no
    ])->update('gl_entries', ['status'=>'CANCELLED']);

    $this->erp->where(['company_id'=>$this->company_id,'trf_id'=>$trf_id])->update('bank_transfers', [
      'status'=>'CANCELLED',
      'cancelled_at'=>date('Y-m-d H:i:s'),
      'cancelled_by'=>null
    ]);

    if($this->erp->trans_status()===false){
      $err = $this->erp->error();
      $this->erp->trans_rollback();
      return ['ok'=>false,'http'=>500,'message'=>'DB error','data'=>['db_error'=>$err]];
    }
    $this->erp->trans_commit();

    return ['ok'=>true,'message'=>'Transfer cancelled','data'=>['trf_id'=>$trf_id,'trf_no'=>$trf_no]];
  }

  // =========================
  // helpers
  // =========================
  private function _get_cash_account($account_id){
    $row = $this->erp->select('account_id,is_postable,is_active,root_type')
      ->get_where('accounts', [
        'company_id'=>$this->company_id,
        'account_id'=>$account_id
      ])->row_array();
    if(!$row) return null;
    if((int)$row['is_active']!==1 || (int)$row['is_postable']!==1) return null;

    // Optional: enforce Asset only for Cash/Bank
    // if ($row['root_type'] !== 'Asset') return null;

    return $row;
  }

  private function _post_gl_btrf($posting_date, $trf_no, $from_account_id, $to_account_id, $amount){
    // Dr To
    $this->erp->insert('gl_entries', [
      'company_id'=>$this->company_id,
      'posting_date'=>$posting_date,
      'job_no'=>null,
      'account_id'=>$to_account_id,
      'party_id'=>null,
      'debit'=>$amount,
      'credit'=>0,
      'voucher_type'=>'BTRF',
      'voucher_no'=>$trf_no,
      'status'=>'POSTED',
      'created_at'=>date('Y-m-d H:i:s')
    ]);

    // Cr From
    $this->erp->insert('gl_entries', [
      'company_id'=>$this->company_id,
      'posting_date'=>$posting_date,
      'job_no'=>null,
      'account_id'=>$from_account_id,
      'party_id'=>null,
      'debit'=>0,
      'credit'=>$amount,
      'voucher_type'=>'BTRF',
      'voucher_no'=>$trf_no,
      'status'=>'POSTED',
      'created_at'=>date('Y-m-d H:i:s')
    ]);
  }

  private function _gl_voucher_posted($voucher_type, $voucher_no){
    return (bool)$this->erp->select('gl_id')
      ->from('gl_entries')
      ->where('company_id', $this->company_id)
      ->where('voucher_type', $voucher_type)
      ->where('voucher_no', $voucher_no)
      ->where('status', 'POSTED')
      ->limit(1)->get()->row_array();
  }

  private function _is_locked($posting_date){
    $row = $this->erp->select('MAX(lock_date) mx', false)
      ->get_where('accounting_locks',['company_id'=>$this->company_id])->row_array();
    $mx = $row['mx'] ?? null;
    return ($mx && $posting_date <= $mx);
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
  
  private function _is_reconciled_voucher($voucher_type, $voucher_no)
	{
	  $row = $this->erp->select('l.line_id', false)
		->from('bank_statement_lines l')
		->join('bank_statements s', 's.stmt_id = l.stmt_id', 'inner')
		->where('s.company_id', $this->company_id)
		->where('s.status', 'RECONCILED')
		->where_in('l.match_status', ['MATCHED','RECONCILED'])
		->where('l.matched_voucher_type', strtoupper($voucher_type))
		->where('l.matched_voucher_no', $voucher_no)
		->limit(1)->get()->row_array();

	  return $row ? true : false;
	}
	
	private function _get_reconciled_lock($voucher_type, $voucher_no)
{
  $voucher_type = strtoupper(trim((string)$voucher_type));
  $voucher_no   = trim((string)$voucher_no);
  if (!$voucher_type || !$voucher_no) return null;

  return $this->erp->select('s.stmt_id,s.period_month,s.statement_date,l.line_id', false)
    ->from('bank_statement_lines l')
    ->join('bank_statements s', 's.stmt_id = l.stmt_id', 'inner')
    ->where('s.company_id', $this->company_id)
    ->where('s.status', 'RECONCILED')
    ->where_in('l.match_status', ['MATCHED','RECONCILED'])
    ->where('l.matched_voucher_type', $voucher_type)
    ->where('l.matched_voucher_no', $voucher_no)
    ->limit(1)->get()->row_array();
}
}