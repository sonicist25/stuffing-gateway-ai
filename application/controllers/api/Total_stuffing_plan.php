<?php
use chriskacerguis\RestServer\RestController;
 
class Total_stuffing_plan extends RestController{
      
    public function __construct(){
        parent::__construct();
        $this->load->model('api/m_api');
    }
     
    public function index_get(){
		$response = $this->m_api->hitung_stuffing_plan();
        $this->response($response);
    }
}
?>