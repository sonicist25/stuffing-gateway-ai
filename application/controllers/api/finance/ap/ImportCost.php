<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class ImportCost extends MY_Controller
{
  public function __construct(){
    parent::__construct();
    $this->load->model('api/finance/ap/M_import_cost','mimp');
  }

  // GET /api/finance/ap/import/batches?source=x_glbcost&vendor_code=XXX
  public function batches(){
    $p = [
      'source' => $this->input->get('source', true),
      'vendor_code' => $this->input->get('vendor_code', true),
      'region_id' => $this->input->get('region_id', true),
    ];
    $rows = $this->mimp->batches($p);
    return $this->_json(true,'OK',$rows);
  }

  // GET /api/finance/ap/import/preview?source=x_glbcost&vendor_code=XXX&batch=YYY
  public function preview(){
    $p = [
      'source' => $this->input->get('source', true),
      'vendor_code' => $this->input->get('vendor_code', true),
      'batch' => $this->input->get('batch', true),
      'region_id' => $this->input->get('region_id', true),
    ];
    $res = $this->mimp->preview($p);
    return $this->_json($res['ok'],$res['message'],$res['data']??null,$res['errors']??null,$res['http']??200);
  }

  // POST /api/finance/ap/import/submit
  public function submit(){
    $p = json_decode($this->input->raw_input_stream, true) ?: [];
    $res = $this->mimp->submit($p);
    return $this->_json($res['ok'],$res['message'],$res['data']??null,$res['errors']??null,$res['http']??200);
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