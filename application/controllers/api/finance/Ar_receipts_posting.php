<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Ar_receipts_posting extends MY_Controller
{
  public function __construct(){
    parent::__construct();
    $this->load->model('api/finance/M_ar_receipts', 'm');
    header('Content-Type: application/json; charset=utf-8');
  }

  public function post_to_gl(){
    $p = json_decode($this->input->raw_input_stream, true) ?: [];
    $res = $this->m->post_rcpt_to_gl($p);
    $this->output->set_status_header($res['http'] ?? 200)->set_output(json_encode([
      'status'=>(bool)$res['ok'],
      'message'=>$res['message'],
      'data'=>$res['data'] ?? null
    ]));
  }

  public function cancel_gl(){
    $p = json_decode($this->input->raw_input_stream, true) ?: [];
    $res = $this->m->cancel_rcpt_gl($p);
    $this->output->set_status_header($res['http'] ?? 200)->set_output(json_encode([
      'status'=>(bool)$res['ok'],
      'message'=>$res['message'],
      'data'=>$res['data'] ?? null
    ]));
  }
}