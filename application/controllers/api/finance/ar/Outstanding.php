<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Outstanding extends MY_Controller
{
  public function __construct(){
    parent::__construct();
    $this->load->model('api/finance/ar/M_outstanding','m');
  }

  // GET /api/finance/ar/outstanding?customer_type=AGENT&customer_code=FSLKHI&limit=200
  public function index(){
    $p = [
      'customer_type' => $this->input->get('customer_type', true), // optional
      'customer_code' => $this->input->get('customer_code', true), // optional
      'search'        => $this->input->get('search', true),        // invoice/job search
      'limit'         => (int)$this->input->get('limit', true),
      'offset'        => (int)$this->input->get('offset', true),
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