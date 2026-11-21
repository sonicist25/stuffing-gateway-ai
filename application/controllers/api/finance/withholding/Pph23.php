<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Pph23 extends MY_Controller
{
  public function __construct(){
    parent::__construct();
    $this->load->model('api/finance/withholding/M_pph23', 'm');
  }

  // GET /api/finance/withholding/pph23?date_from=...&date_to=...
  public function index(){
    $p = [
      'status'    => $this->input->get('status', true),
      'search'    => $this->input->get('search', true),
      'date_from' => $this->input->get('date_from', true),
      'date_to'   => $this->input->get('date_to', true),
    ];
    $rows = $this->m->list($p);
    return $this->_json(true,'OK',$rows);
  }

  // GET /api/finance/withholding/pph23/show/{pay_id}
  public function show($pay_id){
    $row = $this->m->get((int)$pay_id);
    if(!$row) return $this->_json(false,'Not found',null,null,404);
    return $this->_json(true,'OK',$row);
  }

  // POST /api/finance/withholding/pph23/create
  public function create(){
    $p = json_decode($this->input->raw_input_stream, true) ?: [];
    $res = $this->m->create($p);
    return $this->_json($res['ok'], $res['message'], $res['data'] ?? null, $res['errors'] ?? null, $res['http'] ?? 200);
  }

  // POST /api/finance/withholding/pph23/cancel/{pay_id}
  public function cancel($pay_id){
    $res = $this->m->cancel((int)$pay_id);
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