<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin_recalc extends MY_Controller
{
  public function __construct(){
    parent::__construct();
    $this->load->model('api/finance/M_admin_recalc','m');
  }

  // POST /api/finance/admin/recalc/ap_bills
  public function ap_bills(){
    $p = json_decode($this->input->raw_input_stream, true) ?: [];
    $res = $this->m->recalc_ap_bills($p);
    return $this->_json($res['ok'], $res['message'], $res['data'] ?? null, $res['errors'] ?? null, $res['http'] ?? 200);
  }

  // POST /api/finance/admin/recalc/ar_invoices
  public function ar_invoices(){
    $p = json_decode($this->input->raw_input_stream, true) ?: [];
    $res = $this->m->recalc_ar_invoices($p);
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