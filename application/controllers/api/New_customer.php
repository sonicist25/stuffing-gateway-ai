<?php
use chriskacerguis\RestServer\RestController;
 
class New_customer extends RestController{
      
    public function __construct(){
        parent::__construct();
        $this->load->model('api/m_api');
    }

  public function index_post(){
	$params = json_decode(file_get_contents('php://input'), TRUE);
	$cek = $this->m_api->cek_customer($params);
  if($cek == 0){
	$response = $this->m_api->insert_customer($params);
    $this->response($response);
    }else{
	$response = $this->m_api->conflict();
    $this->response($response);
	}
  }
  
  public function index_put(){
	$params = json_decode(file_get_contents('php://input'), TRUE);
	//print_r($params);
	$response = $this->m_api->update_customer($params);
    $this->response($response);
    }
   public function index_delete(){
/* 	$params = json_decode(file_get_contents('php://input'), TRUE);
	//print_r($params);
	$response = $this->m_api->delete_customer($params);
	//print_r($this->dbx->last_query());
	//die();
    $this->response($response); */
    }
}
?>