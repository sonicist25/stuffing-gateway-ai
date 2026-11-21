<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Reports extends MY_Controller
{
  public function __construct(){
    parent::__construct();
    $this->load->model('api/finance/ap/M_reports','mr');
  }

  // GET /api/finance/ap/aging?as_of=2026-02-26&vendor_type=TRUCKING&status=UNPAID|PARTIAL|ALL
  public function aging(){
    $p = [
      'as_of' => $this->input->get('as_of', true),
      'status'=> $this->input->get('status', true),
      'vendor_type'=> $this->input->get('vendor_type', true),
    ];
    $res = $this->mr->ap_aging($p);
    return $this->_json(true,'OK',$res);
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