<?php
use chriskacerguis\RestServer\RestController;
 
class Pilih_stuffingplan_warehouse extends RestController{
      
    public function __construct(){
        parent::__construct();
        $this->load->model('api/m_api');
    }
     
    public function index_get(){
		$code = $this->get('code');
		if($code===null){
        $response = $this->m_api->filter_stuffing_plan();
		}else{
		$response = $this->m_api->filter_stuffing_plan($code);
		}
        $this->response($response);
    }
}
?>