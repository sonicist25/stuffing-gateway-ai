<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Pph23_outstanding extends MY_Controller
{
  public function __construct(){
    parent::__construct();
    $this->load->model('api/finance/withholding/M_pph23_reports', 'm');
  }

  // GET /api/finance/withholding/pph23/outstanding?vendor_code=...&month=2026-03&search=...
  public function index(){
    $p = [
      'vendor_code' => $this->input->get('vendor_code', true),
      'month'       => $this->input->get('month', true),  // YYYY-MM (optional)
      'search'      => $this->input->get('search', true),
      'limit'       => (int)$this->input->get('limit', true),
      'offset'      => (int)$this->input->get('offset', true),
    ];
    $res = $this->m->pph23_outstanding($p);
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