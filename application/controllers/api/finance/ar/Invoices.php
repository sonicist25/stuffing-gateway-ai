<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Invoices extends MY_Controller
{
  public function __construct(){
    parent::__construct();
    $this->load->model('api/finance/ar/M_invoices','minv');
  }

  public function index(){
    $p = [
      'status'    => $this->input->get('status', true),
      'search'    => $this->input->get('search', true),
      'date_from' => $this->input->get('date_from', true),
      'date_to'   => $this->input->get('date_to', true),
    ];
    $rows = $this->minv->list($p);
    return $this->_json(true,'OK',$rows);
  }

  public function show($ar_invoice_id)
{
    $row = $this->minv->detail((int)$ar_invoice_id);
    if (!$row) return $this->_json(false, 'Not found', null, null, 404);
    return $this->_json(true, 'OK', $row);
}

  public function _json($ok,$message,$data=null,$errors=null,$http=200){
    $this->output->set_status_header((int)$http)
      ->set_content_type('application/json')
      ->set_output(json_encode([
        'status'=>(bool)$ok,'message'=>$message,'data'=>$data,'errors'=>$errors,
        'meta'=>['timezone'=>'Asia/Jakarta','server_time'=>date('Y-m-d H:i:s')]
      ]));
  }
  
public function payments($ar_invoice_id)
{
    $ar_invoice_id = (int)$ar_invoice_id;
    if ($ar_invoice_id <= 0) {
        return $this->_json(false, 'Invalid ar_invoice_id', null, ['ar_invoice_id' => 'required'], 422);
    }

    $rows = $this->minv->payment_history($ar_invoice_id);
    return $this->_json(true, 'OK', $rows, null, 200);
}
}