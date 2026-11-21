<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Reports extends MY_Controller
{
  public function __construct(){
    parent::__construct();
    $this->load->model('api/finance/ar/M_reports','mr');
  }

  // GET /api/finance/ar/aging?as_of=2026-02-26&status=UNPAID|PARTIAL|ALL&group_by=customer|kepada
  public function aging(){
    $p = [
      'as_of'    => $this->input->get('as_of', true),     // YYYY-MM-DD
      'status'   => $this->input->get('status', true),    // UNPAID|PARTIAL|ALL
      'group_by' => $this->input->get('group_by', true),  // customer|kepada
      'region_id'=> $this->input->get('region_id', true), // optional
    ];
    $res = $this->mr->ar_aging($p);
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