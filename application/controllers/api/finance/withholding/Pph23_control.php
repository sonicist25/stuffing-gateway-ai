<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Pph23_control extends MY_Controller
{
  public function __construct(){
    parent::__construct();
    $this->load->model('api/finance/withholding/M_pph23_reports', 'm');
  }

  // GET /api/finance/withholding/pph23/control?date_from=2026-01-01&date_to=2026-12-31
  public function index(){
    $p = [
      'date_from' => $this->input->get('date_from', true),
      'date_to'   => $this->input->get('date_to', true),
    ];
    $res = $this->m->pph23_control($p);
    return $this->_json(true, 'OK', $res);
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