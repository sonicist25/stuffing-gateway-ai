<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Accounts extends MY_Controller
{
  public function __construct(){
    parent::__construct();
    $this->load->model('api/finance/cash/M_cash_accounts','m');
  }

  // GET /api/finance/cash/accounts?search=bca
  public function index(){
    $p = [
      'search' => $this->input->get('search', true),
      'limit'  => (int)$this->input->get('limit', true),
    ];
    $rows = $this->m->list($p);
    return $this->_json(true,'OK',$rows);
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