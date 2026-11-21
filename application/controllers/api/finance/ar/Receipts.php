<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Receipts extends MY_Controller
{
  public function __construct(){
    parent::__construct();
    $this->load->model('api/finance/ar/M_receipts','mrcpt');
  }

  // POST /api/finance/ar/receipts/cancel/{rcpt_id}
  public function cancel($rcpt_id){
    $res = $this->mrcpt->cancel((int)$rcpt_id);
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
  
  public function index()
	{
	  $p = [
		'search' => $this->input->get('search', true),
		'date_from' => $this->input->get('date_from', true),
		'date_to' => $this->input->get('date_to', true),
		'status' => $this->input->get('status', true), // SUBMITTED/CANCELLED (optional)
	  ];
	  $rows = $this->mrcpt->list($p);
	  return $this->_json(true, 'OK', $rows);
	}

public function show($rcpt_id)
	{
	  $row = $this->mrcpt->get((int)$rcpt_id);
	  if (!$row) return $this->_json(false, 'Not found', null, null, 404);
	  return $this->_json(true, 'OK', $row);
	}
public function create()
{
  $p = json_decode($this->input->raw_input_stream, true) ?: [];
  $res = $this->mrcpt->create($p);
  return $this->_json($res['ok'], $res['message'], $res['data'] ?? null, $res['errors'] ?? null, $res['http'] ?? 200);
}
}