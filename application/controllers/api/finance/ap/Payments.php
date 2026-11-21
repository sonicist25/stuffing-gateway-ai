<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Payments extends MY_Controller
{
  public function __construct(){
    parent::__construct();
    $this->load->model('api/finance/ap/M_payments','mp');
  }

  public function index(){
    $p = [
      'search' => $this->input->get('search', true),
      'status' => $this->input->get('status', true), // optional
      'date_from' => $this->input->get('date_from', true),
      'date_to' => $this->input->get('date_to', true),
    ];
    $rows = $this->mp->list($p);
    return $this->_json(true,'OK',$rows);
  }

  public function show($pay_id){
    $row = $this->mp->get((int)$pay_id);
    if(!$row) return $this->_json(false,'Not found',null,null,404);
    return $this->_json(true,'OK',$row);
  }

  // POST /api/finance/ap/payments/create
  public function create(){
    $p = json_decode($this->input->raw_input_stream, true) ?: [];
    $res = $this->mp->create($p);
    return $this->_json($res['ok'],$res['message'],$res['data']??null,$res['errors']??null,$res['http']??200);
  }

  // POST /api/finance/ap/payments/cancel/{pay_id}
  public function cancel($pay_id){
    $res = $this->mp->cancel((int)$pay_id);
    return $this->_json($res['ok'],$res['message'],$res['data']??null,$res['errors']??null,$res['http']??200);
  }

  protected function _json($ok,$message,$data=null,$errors=null,$http=200){
    $this->output->set_status_header((int)$http)
      ->set_content_type('application/json')
      ->set_output(json_encode([
        'status'=>(bool)$ok,'message'=>$message,'data'=>$data,'errors'=>$errors,
        'meta'=>['timezone'=>'Asia/Jakarta','server_time'=>date('Y-m-d H:i:s')]
      ]));
  }
}