<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Statements extends MY_Controller
{
  public function __construct(){
    parent::__construct();
    $this->load->model('api/finance/bank/M_bank_recon','m');
  }

  // GET /api/finance/bank/statements
  public function index(){
    $p = [
      'bank_account_id' => $this->input->get('bank_account_id', true),
      'period_month'    => $this->input->get('period_month', true), // YYYY-MM
      'status'          => $this->input->get('status', true),
      'date_from'       => $this->input->get('date_from', true),
      'date_to'         => $this->input->get('date_to', true),
      'search'          => $this->input->get('search', true),
    ];
    $rows = $this->m->list($p);
    return $this->_json(true,'OK',$rows);
  }

  // GET /api/finance/bank/statements/show/{stmt_id}
  public function show($stmt_id){
    $row = $this->m->get((int)$stmt_id);
    if(!$row) return $this->_json(false,'Not found',null,null,404);
    return $this->_json(true,'OK',$row);
  }

  // POST /api/finance/bank/statements/import
  public function import(){
    $p = json_decode($this->input->raw_input_stream, true) ?: [];
    $res = $this->m->import($p);
    return $this->_json($res['ok'], $res['message'], $res['data'] ?? null, $res['errors'] ?? null, $res['http'] ?? 200);
  }

  // POST /api/finance/bank/statements/auto-match/{stmt_id}
  public function auto_match($stmt_id){
    $p = json_decode($this->input->raw_input_stream, true) ?: [];
    $p['stmt_id'] = (int)$stmt_id;
    $res = $this->m->auto_match($p);
    return $this->_json($res['ok'], $res['message'], $res['data'] ?? null, $res['errors'] ?? null, $res['http'] ?? 200);
  }

  // POST /api/finance/bank/statements/reconcile/{stmt_id}
  public function reconcile($stmt_id){
    $p = json_decode($this->input->raw_input_stream, true) ?: [];
    $p['stmt_id'] = (int)$stmt_id;
    $res = $this->m->reconcile($p);
    return $this->_json($res['ok'], $res['message'], $res['data'] ?? null, $res['errors'] ?? null, $res['http'] ?? 200);
  }

  // POST /api/finance/bank/lines/match/{line_id}
  public function match_line($line_id){
    $p = json_decode($this->input->raw_input_stream, true) ?: [];
    $p['line_id'] = (int)$line_id;
    $res = $this->m->manual_match($p);
    return $this->_json($res['ok'], $res['message'], $res['data'] ?? null, $res['errors'] ?? null, $res['http'] ?? 200);
  }

  // POST /api/finance/bank/lines/unmatch/{line_id}
  public function unmatch_line($line_id){
    $p = ['line_id'=>(int)$line_id];
    $res = $this->m->unmatch($p);
    return $this->_json($res['ok'], $res['message'], $res['data'] ?? null, $res['errors'] ?? null, $res['http'] ?? 200);
  }

  // POST /api/finance/bank/lines/ignore/{line_id}
  public function ignore_line($line_id){
    $p = ['line_id'=>(int)$line_id];
    $res = $this->m->ignore($p);
    return $this->_json($res['ok'], $res['message'], $res['data'] ?? null, $res['errors'] ?? null, $res['http'] ?? 200);
  }

  protected function _json($ok,$message,$data=null,$errors=null,$http=200){
    $this->output->set_status_header((int)$http)
      ->set_content_type('application/json')
      ->set_output(json_encode([
        'status'=>(bool)$ok,
        'message'=>$message,
        'data'=>$data,
        'errors'=>$errors,
        'meta'=>['timezone'=>'Asia/Jakarta','server_time'=>date('Y-m-d H:i:s')]
      ]));
  }
  
  // GET /api/finance/bank/lines/suggest/{line_id}?tol=3&limit=10
public function suggest($line_id){
  $p = [
    'line_id' => (int)$line_id,
    'tol'     => (int)$this->input->get('tol', true),
    'limit'   => (int)$this->input->get('limit', true),
  ];
  $res = $this->m->suggest($p);
  return $this->_json($res['ok'], $res['message'], $res['data'] ?? null, $res['errors'] ?? null, $res['http'] ?? 200);
}
}