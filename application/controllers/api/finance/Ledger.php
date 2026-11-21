<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Ledger extends MY_Controller
{
  public function __construct(){
    parent::__construct();
    $this->load->model('api/finance/M_ledger','ml');
  }

  // GET /api/finance/ledger/cash?account_id=123&date_from=2026-02-01&date_to=2026-02-29&search=&include_opening=1
  public function cash(){
    $p = [
      'account_id' => (int)$this->input->get('account_id', true),
      'date_from'  => $this->input->get('date_from', true),
      'date_to'    => $this->input->get('date_to', true),
      'search'     => $this->input->get('search', true),
      'include_opening' => (int)$this->input->get('include_opening', true),
    ];
    $res = $this->ml->cash_ledger($p);
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
  
  public function voucher(){
	  $p = [
		'voucher_type' => $this->input->get('voucher_type', true),
		'voucher_no'   => $this->input->get('voucher_no', true),
	  ];
	  $res = $this->ml->voucher_detail($p);
	  return $this->_json($res['ok'], $res['message'], $res['data'] ?? null, $res['errors'] ?? null, $res['http'] ?? 200);
	}
public function general()
{
    $accountId = $this->input->get('account_id', true);

    $p = [
        'account_id' => ($accountId === null || $accountId === '') ? null : (int)$accountId,
        'date_from'  => $this->input->get('date_from', true),
        'date_to'    => $this->input->get('date_to', true),
        'search'     => $this->input->get('search', true),
        'include_opening' => $this->input->get('include_opening', true) !== null
            ? (int)$this->input->get('include_opening', true)
            : 1,
        'movement'   => $this->input->get('movement', true),
    ];

    $res = $this->ml->general_ledger($p);
    return $this->_json(true, 'OK', $res);
}
}