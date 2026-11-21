<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_bank_recon extends MY_Model
{
  private $company_id = 1;

  // -----------------------------
  // LIST + GET
  // -----------------------------
  public function list($p){
    $this->erp->select("s.stmt_id,s.bank_account_id,s.period_month,s.statement_date,s.remark,s.status,s.created_at", false)
      ->from('bank_statements s')
      ->from('bank_statements s')
      ->where('s.company_id',$this->company_id);
	$this->apply_region_filter('region_id');
    if (!empty($p['bank_account_id'])) $this->erp->where('s.bank_account_id', (int)$p['bank_account_id']);
    if (!empty($p['period_month'])) $this->erp->where('s.period_month', $p['period_month']);
    if (!empty($p['status'])) $this->erp->where('s.status', strtoupper($p['status']));
    if (!empty($p['date_from'])) $this->erp->where('s.statement_date >=', $p['date_from']);
    if (!empty($p['date_to']))   $this->erp->where('s.statement_date <=', $p['date_to']);
    if (!empty($p['search'])) {
      $q = $p['search'];
      $this->erp->group_start()->like('s.remark',$q)->or_like('s.period_month',$q)->group_end();
    }

    $this->erp->order_by('s.statement_date','DESC')->order_by('s.stmt_id','DESC');
    $rows = $this->erp->get()->result_array();

    // attach bank account label
    if ($rows) {
      $ids = array_values(array_unique(array_map(fn($r)=>(int)$r['bank_account_id'], $rows)));
      $map = [];
      if ($ids) {
        $acc = $this->erp->select('account_id,account_code,account_name')
          ->where('company_id',$this->company_id)->where_in('account_id',$ids)
          ->get('accounts')->result_array();
        foreach($acc as $a) $map[(int)$a['account_id']] = $a['account_code'].' - '.$a['account_name'];
      }
      foreach($rows as &$r){
        $r['bank_account_label'] = $map[(int)$r['bank_account_id']] ?? null;
      }
      unset($r);
    }

    return $rows;
  }

  public function get($stmt_id){
    $hdr = $this->erp->get_where('bank_statements', [
      'company_id'=>$this->company_id,
      'stmt_id'=>$stmt_id
    ])->row_array();
    if(!$hdr) return null;

    $lines = $this->erp->order_by('line_no','ASC')
      ->get_where('bank_statement_lines', ['stmt_id'=>$stmt_id])
      ->result_array();

    foreach($lines as &$l){
      $l['debit'] = (float)$l['debit'];
      $l['credit'] = (float)$l['credit'];
      $l['balance'] = (float)$l['balance'];
    }
    unset($l);

    $hdr['lines'] = $lines;
    return $hdr;
  }

  // -----------------------------
  // IMPORT
  // -----------------------------
  // body:
  // {
  //   "bank_account_id": 26,
  //   "period_month": "2026-03",
  //   "statement_date": "2026-03-31",
  //   "remark": "BCA March",
  //   "lines": [
  //     {"date":"2026-03-03","description":"RCPT-2026-00012 ...","debit":0,"credit":1000000,"balance":123},
  //     ...
  //   ]
  // }
  public function import($p){
    $bank_account_id = (int)($p['bank_account_id'] ?? 0);
    $period_month    = trim((string)($p['period_month'] ?? ''));
    $statement_date  = trim((string)($p['statement_date'] ?? date('Y-m-d')));
    $remark          = trim((string)($p['remark'] ?? ''));
    $lines           = $p['lines'] ?? [];

    $errs = [];
    if ($bank_account_id<=0) $errs['bank_account_id']='required';
    if (!$this->_is_month($period_month)) $errs['period_month']='required (YYYY-MM)';
    if (!$this->_is_date($statement_date)) $errs['statement_date']='invalid (YYYY-MM-DD)';
    if (!is_array($lines) || count($lines)<1) $errs['lines']='required';
    if ($errs) return ['ok'=>false,'http'=>422,'message'=>'Validation error','errors'=>$errs];

    // prevent duplicate period import for same bank
    $exists = $this->erp->select('stmt_id')->get_where('bank_statements', [
      'company_id'=>$this->company_id,
      'bank_account_id'=>$bank_account_id,
      'period_month'=>$period_month,
      'status !='=>'CANCELLED'
    ])->row_array();
    if($exists){
      return ['ok'=>false,'http'=>409,'message'=>'STATEMENT_EXISTS','data'=>['stmt_id'=>(int)$exists['stmt_id']]];
    }

    $this->erp->trans_begin();

    $this->erp->insert('bank_statements', [
      'company_id'=>$this->company_id,
      'bank_account_id'=>$bank_account_id,
      'period_month'=>$period_month,
      'statement_date'=>$statement_date,
      'remark'=>$remark ?: null,
      'status'=>'DRAFT',
      'created_by'=>null
    ]);
    $stmt_id = (int)$this->erp->insert_id();

    $line_no = 1;
    $inserted = 0;
    foreach($lines as $ln){
      $d = trim((string)($ln['date'] ?? ''));
      $desc = trim((string)($ln['description'] ?? ''));
      $debit = (float)($ln['debit'] ?? 0);
      $credit = (float)($ln['credit'] ?? 0);
      $bal = (float)($ln['balance'] ?? 0);

      if (!$this->_is_date($d)) continue;
      if ($desc === '') continue;
      if ($debit < 0) $debit = 0;
      if ($credit < 0) $credit = 0;

      $this->erp->insert('bank_statement_lines', [
        'stmt_id'=>$stmt_id,
        'line_no'=>$line_no++,
        'trx_date'=>$d,
        'description'=>$desc,
        'debit'=>$debit,
        'credit'=>$credit,
        'balance'=>$bal,
        'match_status'=>'UNMATCHED'
      ]);
      $inserted++;
    }

    if($this->erp->trans_status()===false){
      $err = $this->erp->error();
      $this->erp->trans_rollback();
      return ['ok'=>false,'http'=>500,'message'=>'DB error','data'=>['db_error'=>$err]];
    }

    $this->erp->trans_commit();

    return ['ok'=>true,'message'=>'Imported','data'=>[
      'stmt_id'=>$stmt_id,
      'inserted_lines'=>$inserted
    ]];
  }

  // -----------------------------
  // AUTO MATCH
  // -----------------------------
  // body optional: { "date_tolerance_days": 3, "only_unmatched": 1 }
  public function auto_match($p){
    $stmt_id = (int)($p['stmt_id'] ?? 0);
    if($stmt_id<=0) return ['ok'=>false,'http'=>422,'message'=>'stmt_id required'];

    $hdr = $this->erp->get_where('bank_statements', [
      'company_id'=>$this->company_id,'stmt_id'=>$stmt_id
    ])->row_array();
    if(!$hdr) return ['ok'=>false,'http'=>404,'message'=>'Statement not found'];
    if($hdr['status']==='RECONCILED') return ['ok'=>false,'http'=>409,'message'=>'ALREADY_RECONCILED'];

    $tol = (int)($p['date_tolerance_days'] ?? 3);
    if($tol<0) $tol=0; if($tol>10) $tol=10;
    $only_unmatched = (int)($p['only_unmatched'] ?? 1) ? true : false;

    // get lines
    $this->erp->from('bank_statement_lines')->where('stmt_id',$stmt_id);
    if($only_unmatched) $this->erp->where('match_status','UNMATCHED');
    $lines = $this->erp->order_by('line_no','ASC')->get()->result_array();

    $matched = 0;
    $attempted = 0;

    $this->erp->trans_begin();

    foreach($lines as $ln){
      $attempted++;
      $line_id = (int)$ln['line_id'];
      $desc = (string)$ln['description'];
      $trx_date = (string)$ln['trx_date'];
      $debit = (float)$ln['debit'];
      $credit = (float)$ln['credit'];

      // 1) direct voucher in description (RCPT-/VPAY-/BTRF-)
      $hit = $this->_extract_voucher($desc);
      if($hit){
        [$vt,$vn] = $hit;
        $gl_id = $this->_find_gl_by_voucher_and_bank($vt,$vn,(int)$hdr['bank_account_id']);
        if($gl_id){
          $this->_apply_match($line_id,$vt,$vn,$gl_id,'AUTO');
          $matched++;
          continue;
        }
        // kalau voucher ada tapi tidak ada GL row di bank account tsb, skip
      }

      // 2) fallback by amount + date window, by direction
      $amount = ($credit>0) ? $credit : $debit; // credit=in, debit=out
      if($amount <= 0.01) continue;

      $direction = ($credit>0) ? 'IN' : 'OUT';

      $candidate = $this->_find_best_gl_by_amount_date(
        (int)$hdr['bank_account_id'],
        $trx_date,
        $tol,
        $amount,
        $direction
      );

      if($candidate){
        $this->_apply_match($line_id,$candidate['voucher_type'],$candidate['voucher_no'],(int)$candidate['gl_id'],'AUTO');
        $matched++;
      }
    }

    if($this->erp->trans_status()===false){
      $err=$this->erp->error();
      $this->erp->trans_rollback();
      return ['ok'=>false,'http'=>500,'message'=>'DB error','data'=>['db_error'=>$err]];
    }
    $this->erp->trans_commit();

    return ['ok'=>true,'message'=>'OK','data'=>[
      'stmt_id'=>$stmt_id,
      'attempted'=>$attempted,
      'matched'=>$matched,
      'tolerance_days'=>$tol
    ]];
  }

  // -----------------------------
  // MANUAL MATCH / UNMATCH / IGNORE
  // -----------------------------
  // POST body: { "voucher_type":"RCPT|VPAY|BTRF", "voucher_no":"RCPT-2026-00001" }
  public function manual_match($p){
    $line_id = (int)($p['line_id'] ?? 0);
    $vt = strtoupper(trim((string)($p['voucher_type'] ?? '')));
    $vn = trim((string)($p['voucher_no'] ?? ''));
    if($line_id<=0) return ['ok'=>false,'http'=>422,'message'=>'line_id required'];
    if(!$vt || !$vn) return ['ok'=>false,'http'=>422,'message'=>'voucher_type & voucher_no required'];

    $line = $this->erp->select('l.*, s.bank_account_id, s.status stmt_status', false)
      ->from('bank_statement_lines l')
      ->join('bank_statements s','s.stmt_id=l.stmt_id','inner')
      ->where('l.line_id',$line_id)
      ->where('s.company_id',$this->company_id)
      ->get()->row_array();
    if(!$line) return ['ok'=>false,'http'=>404,'message'=>'Line not found'];
    if($line['stmt_status']==='RECONCILED') return ['ok'=>false,'http'=>409,'message'=>'STATEMENT_RECONCILED'];

    $gl_id = $this->_find_gl_by_voucher_and_bank($vt,$vn,(int)$line['bank_account_id']);
    if(!$gl_id){
      return ['ok'=>false,'http'=>404,'message'=>'GL_NOT_FOUND_FOR_VOUCHER_BANK'];
    }

    $this->erp->trans_begin();
    $this->_apply_match($line_id,$vt,$vn,$gl_id,'MANUAL');

    if($this->erp->trans_status()===false){
      $err=$this->erp->error();
      $this->erp->trans_rollback();
      return ['ok'=>false,'http'=>500,'message'=>'DB error','data'=>['db_error'=>$err]];
    }
    $this->erp->trans_commit();

    return ['ok'=>true,'message'=>'MATCHED','data'=>[
      'line_id'=>$line_id,'voucher_type'=>$vt,'voucher_no'=>$vn,'gl_id'=>$gl_id
    ]];
  }

  public function unmatch($p){
    $line_id = (int)($p['line_id'] ?? 0);
    if($line_id<=0) return ['ok'=>false,'http'=>422,'message'=>'line_id required'];

    $line = $this->erp->select('l.line_id, l.match_status, s.status stmt_status', false)
      ->from('bank_statement_lines l')
      ->join('bank_statements s','s.stmt_id=l.stmt_id','inner')
      ->where('l.line_id',$line_id)
      ->where('s.company_id',$this->company_id)
      ->get()->row_array();
    if(!$line) return ['ok'=>false,'http'=>404,'message'=>'Line not found'];
    if($line['stmt_status']==='RECONCILED') return ['ok'=>false,'http'=>409,'message'=>'STATEMENT_RECONCILED'];

    $this->erp->where('line_id',$line_id)->update('bank_statement_lines', [
      'match_status'=>'UNMATCHED',
      'match_method'=>null,
      'matched_voucher_type'=>null,
      'matched_voucher_no'=>null,
      'matched_gl_id'=>null,
      'matched_at'=>null,
      'matched_by'=>null
    ]);

    if($this->erp->affected_rows()===0){
      // still ok (idempotent)
    }

    return ['ok'=>true,'message'=>'UNMATCHED','data'=>['line_id'=>$line_id]];
  }

  public function ignore($p){
    $line_id = (int)($p['line_id'] ?? 0);
    if($line_id<=0) return ['ok'=>false,'http'=>422,'message'=>'line_id required'];

    $line = $this->erp->select('l.line_id, s.status stmt_status', false)
      ->from('bank_statement_lines l')
      ->join('bank_statements s','s.stmt_id=l.stmt_id','inner')
      ->where('l.line_id',$line_id)
      ->where('s.company_id',$this->company_id)
      ->get()->row_array();
    if(!$line) return ['ok'=>false,'http'=>404,'message'=>'Line not found'];
    if($line['stmt_status']==='RECONCILED') return ['ok'=>false,'http'=>409,'message'=>'STATEMENT_RECONCILED'];

    $this->erp->where('line_id',$line_id)->update('bank_statement_lines', [
      'match_status'=>'IGNORED',
      'match_method'=>null,
      'matched_voucher_type'=>null,
      'matched_voucher_no'=>null,
      'matched_gl_id'=>null,
      'matched_at'=>null,
      'matched_by'=>null
    ]);

    return ['ok'=>true,'message'=>'IGNORED','data'=>['line_id'=>$line_id]];
  }

  // -----------------------------
  // RECONCILE (mark all matched as reconciled + header reconciled)
  // -----------------------------
  public function reconcile($p){
    $stmt_id = (int)($p['stmt_id'] ?? 0);
    if($stmt_id<=0) return ['ok'=>false,'http'=>422,'message'=>'stmt_id required'];

    $hdr = $this->erp->get_where('bank_statements', [
      'company_id'=>$this->company_id,'stmt_id'=>$stmt_id
    ])->row_array();
    if(!$hdr) return ['ok'=>false,'http'=>404,'message'=>'Statement not found'];
    if($hdr['status']==='RECONCILED') return ['ok'=>false,'http'=>409,'message'=>'ALREADY_RECONCILED'];

    $this->erp->trans_begin();

    // lines MATCHED -> RECONCILED
    $this->erp->where('stmt_id',$stmt_id)->where('match_status','MATCHED')
      ->update('bank_statement_lines', ['match_status'=>'RECONCILED']);

    // header -> RECONCILED
    $this->erp->where(['stmt_id'=>$stmt_id,'company_id'=>$this->company_id])
      ->update('bank_statements', ['status'=>'RECONCILED']);

    if($this->erp->trans_status()===false){
      $err=$this->erp->error();
      $this->erp->trans_rollback();
      return ['ok'=>false,'http'=>500,'message'=>'DB error','data'=>['db_error'=>$err]];
    }
    $this->erp->trans_commit();

    return ['ok'=>true,'message'=>'RECONCILED','data'=>['stmt_id'=>$stmt_id]];
  }

  // =============================
  // internal helpers
  // =============================
  private function _is_date($s){
    return (bool)preg_match('/^\d{4}\-\d{2}\-\d{2}$/', (string)$s);
  }
  private function _is_month($s){
    return (bool)preg_match('/^\d{4}\-\d{2}$/', (string)$s);
  }

  // extract voucher like RCPT-2026-00001 / VPAY-2026-00001 / BTRF-2026-00001
  private function _extract_voucher($desc){
    $desc = strtoupper((string)$desc);
    if (preg_match('/\b(RCPT|VPAY|BTRF)\-\d{4}\-\d{5}\b/', $desc, $m)) {
      $voucher = $m[0];
      $parts = explode('-', $voucher);
      $vt = $parts[0];
      return [$vt, $voucher];
    }
    return null;
  }

  // find gl row for voucher on that bank account (cash account)
  private function _find_gl_by_voucher_and_bank($vt,$vn,$bank_account_id){
    $row = $this->erp->select('gl_id', false)
      ->from('gl_entries')
      ->where('company_id',$this->company_id)
      ->where('status','POSTED')
      ->where('voucher_type',$vt)
      ->where('voucher_no',$vn)
      ->where('account_id',(int)$bank_account_id)
      ->order_by('gl_id','ASC')
      ->limit(1)->get()->row_array();
    return $row ? (int)$row['gl_id'] : 0;
  }

  // best candidate by amount+date window and direction
  private function _find_best_gl_by_amount_date($bank_account_id, $trx_date, $tol_days, $amount, $direction){
    $d1 = date('Y-m-d', strtotime($trx_date." -{$tol_days} day"));
    $d2 = date('Y-m-d', strtotime($trx_date." +{$tol_days} day"));

    $this->erp->select("gl_id, posting_date, voucher_type, voucher_no, debit, credit", false)
      ->from('gl_entries')
      ->where('company_id',$this->company_id)
      ->where('status','POSTED')
      ->where('account_id',(int)$bank_account_id)
      ->where('posting_date >=', $d1)
      ->where('posting_date <=', $d2);

    // Direction:
    // bank IN => Dr cash (debit) usually in RCPT
    // bank OUT => Cr cash (credit) usually in VPAY or BTRF from side
    if ($direction === 'IN') {
      $this->erp->where('debit >=', $amount - 0.01);
      $this->erp->where('debit <=', $amount + 0.01);
      $this->erp->where_in('voucher_type', ['RCPT','BTRF']); // BTRF could be incoming to bank
      $this->erp->order_by('ABS(DATEDIFF(posting_date, '.$this->erp->escape($trx_date).'))','ASC', false);
      $this->erp->order_by('gl_id','ASC');
    } else {
      $this->erp->where('credit >=', $amount - 0.01);
      $this->erp->where('credit <=', $amount + 0.01);
      $this->erp->where_in('voucher_type', ['VPAY','BTRF']); // BTRF could be outgoing from bank
      $this->erp->order_by('ABS(DATEDIFF(posting_date, '.$this->erp->escape($trx_date).'))','ASC', false);
      $this->erp->order_by('gl_id','ASC');
    }

    $row = $this->erp->limit(1)->get()->row_array();
    return $row ?: null;
  }

  private function _apply_match($line_id,$vt,$vn,$gl_id,$method){
    // prevent same voucher matched by multiple lines (optional guard)
    // In MVP, we allow but better to block double-match:
    $dup = $this->erp->select('line_id')
      ->from('bank_statement_lines l')
      ->join('bank_statements s','s.stmt_id=l.stmt_id','inner')
      ->where('s.company_id',$this->company_id)
      ->where('l.match_status !=','UNMATCHED')
      ->where('l.matched_voucher_type',$vt)
      ->where('l.matched_voucher_no',$vn)
      ->limit(1)->get()->row_array();
    if($dup){
      return; // skip
    }

    $this->erp->where('line_id',$line_id)->update('bank_statement_lines', [
      'match_status'=>'MATCHED',
      'match_method'=>$method,
      'matched_voucher_type'=>$vt,
      'matched_voucher_no'=>$vn,
      'matched_gl_id'=>$gl_id,
      'matched_at'=>date('Y-m-d H:i:s'),
      'matched_by'=>null
    ]);
  }
  public function suggest($p){
  $line_id = (int)($p['line_id'] ?? 0);
  $tol = (int)($p['tol'] ?? 3);
  $limit = (int)($p['limit'] ?? 10);

  if($line_id<=0) return ['ok'=>false,'http'=>422,'message'=>'line_id required'];
  if($tol<0) $tol=0; if($tol>10) $tol=10;
  if($limit<=0) $limit=10; if($limit>30) $limit=30;

  $line = $this->erp->select('l.*, s.bank_account_id, s.status stmt_status', false)
    ->from('bank_statement_lines l')
    ->join('bank_statements s','s.stmt_id=l.stmt_id','inner')
    ->where('l.line_id',$line_id)
    ->where('s.company_id',$this->company_id)
    ->get()->row_array();

  if(!$line) return ['ok'=>false,'http'=>404,'message'=>'Line not found'];
  if($line['stmt_status']==='RECONCILED') return ['ok'=>false,'http'=>409,'message'=>'STATEMENT_RECONCILED'];

  $trx_date = (string)$line['trx_date'];
  $debit = (float)$line['debit'];
  $credit = (float)$line['credit'];

  $direction = ($credit > 0.01) ? 'IN' : 'OUT';
  $amount = ($credit > 0.01) ? $credit : $debit;
  if($amount <= 0.01) return ['ok'=>true,'message'=>'OK','data'=>['line_id'=>$line_id,'candidates'=>[]]];

  $cands = $this->_suggest_gl_candidates(
    (int)$line['bank_account_id'],
    $trx_date,
    $tol,
    $amount,
    $direction,
    $limit
  );

  return ['ok'=>true,'message'=>'OK','data'=>[
    'line_id'=>$line_id,
    'bank_account_id'=>(int)$line['bank_account_id'],
    'trx_date'=>$trx_date,
    'direction'=>$direction,
    'amount'=>$amount,
    'tolerance_days'=>$tol,
    'candidates'=>$cands
  ]];
}

private function _suggest_gl_candidates($bank_account_id, $trx_date, $tol_days, $amount, $direction, $limit){
  $d1 = date('Y-m-d', strtotime($trx_date." -{$tol_days} day"));
  $d2 = date('Y-m-d', strtotime($trx_date." +{$tol_days} day"));

  $this->erp->select("
      gl.gl_id,
      gl.posting_date,
      gl.voucher_type,
      gl.voucher_no,
      gl.debit,
      gl.credit
    ", false)
    ->from('gl_entries gl')
    ->where('gl.company_id', $this->company_id)
    ->where('gl.status', 'POSTED')
    ->where('gl.account_id', (int)$bank_account_id)
    ->where('gl.posting_date >=', $d1)
    ->where('gl.posting_date <=', $d2);

  if ($direction === 'IN') {
    $this->erp->where('gl.debit >=', $amount - 0.01);
    $this->erp->where('gl.debit <=', $amount + 0.01);
    $this->erp->where_in('gl.voucher_type', ['RCPT','BTRF']);
  } else {
    $this->erp->where('gl.credit >=', $amount - 0.01);
    $this->erp->where('gl.credit <=', $amount + 0.01);
    $this->erp->where_in('gl.voucher_type', ['VPAY','BTRF']);
  }

  // exclude vouchers already matched by other lines (reduce double-match)
  $this->erp->where("NOT EXISTS (
    SELECT 1
    FROM bank_statement_lines bl
    JOIN bank_statements bs ON bs.stmt_id = bl.stmt_id
    WHERE bs.company_id = ".$this->erp->escape($this->company_id)."
      AND bl.match_status IN ('MATCHED','RECONCILED')
      AND bl.matched_voucher_type = gl.voucher_type
      AND bl.matched_voucher_no = gl.voucher_no
  )", null, false);

  $this->erp->order_by('ABS(DATEDIFF(gl.posting_date, '.$this->erp->escape($trx_date).'))','ASC', false);
  $this->erp->order_by('gl.gl_id','ASC');
  $rows = $this->erp->limit($limit)->get()->result_array();

  $out = [];
  foreach($rows as $r){
    $d = (float)$r['debit'];
    $c = (float)$r['credit'];
    $amt = ($direction==='IN') ? $d : $c;

    $out[] = [
      'gl_id' => (int)$r['gl_id'],
      'posting_date' => $r['posting_date'],
      'voucher_type' => $r['voucher_type'],
      'voucher_no' => $r['voucher_no'],
      'amount' => (float)$amt
    ];
  }
  return $out;
}
}