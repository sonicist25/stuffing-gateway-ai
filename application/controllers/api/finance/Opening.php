<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Opening extends MY_Controller
{
  public function __construct(){
    parent::__construct();
    $this->load->model('api/finance/M_opening','mo');
  }

  public function submit(){
    $p = json_decode($this->input->raw_input_stream, true) ?: [];
    $res = $this->mo->submit($p);
    return $this->_json($res['ok'], $res['message'], $res['data'] ?? null, $res['errors'] ?? null, $res['http'] ?? 200);
  }

  public function get(){
    $p = [
      'voucher_no' => $this->input->get('voucher_no', true),
    ];
    $res = $this->mo->get($p);
    return $this->_json($res['ok'], $res['message'], $res['data'] ?? null, $res['errors'] ?? null, $res['http'] ?? 200);
  }

  public function cancel(){
    $p = json_decode($this->input->raw_input_stream, true) ?: [];
    $res = $this->mo->cancel($p);
    return $this->_json($res['ok'], $res['message'], $res['data'] ?? null, $res['errors'] ?? null, $res['http'] ?? 200);
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