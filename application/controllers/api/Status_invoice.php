<?php
use chriskacerguis\RestServer\RestController;
 
class Status_invoice extends RestController{
      
    public function __construct(){
        parent::__construct();
        $this->load->model('api/m_api');
    }
     
  public function index_post(){
	$params = json_decode(file_get_contents('php://input'), TRUE);
	//print_r($params);
	$response = $this->m_api->update_x_finance($params);
    $this->response($response);
  }
}