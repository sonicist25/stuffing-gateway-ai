<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Bank_transfers extends MY_Controller
{
  public function __construct(){
    parent::__construct();
    $this->load->model('api/finance/cash/M_bank_transfers','m');
  }

  // GET /api/finance/cash/bank-transfers
  public function index(){
    $p = [
      'search' => $this->input->get('search', true),
      'date_from' => $this->input->get('date_from', true),
      'date_to' => $this->input->get('date_to', true),
      'status' => $this->input->get('status', true),
    ];
    $rows = $this->m->list($p);
    return $this->_json(true,'OK',$rows);
  }

  // GET /api/finance/cash/bank-transfers/show/{trf_id}
  public function show($trf_id){
    $row = $this->m->get((int)$trf_id);
    if(!$row) return $this->_json(false,'Not found',null,null,404);
    return $this->_json(true,'OK',$row);
  }

  // POST /api/finance/cash/bank-transfers/create
  public function create(){
    $p = json_decode($this->input->raw_input_stream, true) ?: [];
    $res = $this->m->create($p);
    return $this->_json($res['ok'], $res['message'], $res['data'] ?? null, $res['errors'] ?? null, $res['http'] ?? 200);
  }

  // POST /api/finance/cash/bank-transfers/cancel/{trf_id}
  public function cancel($trf_id){
    $res = $this->m->cancel((int)$trf_id);
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
}