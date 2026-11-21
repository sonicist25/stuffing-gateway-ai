<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Outstanding extends MY_Controller
{
  public function __construct(){
    parent::__construct();
    $this->load->model('api/finance/ap/M_outstanding','m');
  }

  // GET /api/finance/ap/outstanding?vendor_code=EVERGREEN&limit=200
  public function index(){
    $p = [
      'vendor_code' => $this->input->get('vendor_code', true),
      'search'      => $this->input->get('search', true), // bill_no/job
      'limit'       => (int)$this->input->get('limit', true),
      'offset'      => (int)$this->input->get('offset', true),
    ];
    $res = $this->m->list($p);
    return $this->_json(true,'OK',$res);
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