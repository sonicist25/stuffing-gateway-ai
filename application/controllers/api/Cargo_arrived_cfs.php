<?php
use chriskacerguis\RestServer\RestController;
 
class Cargo_arrived_cfs extends RestController{
      
    public function __construct(){
        parent::__construct();
        $this->load->model('api/m_api');
    }

  public function index_put(){
	$params = json_decode(file_get_contents('php://input'), TRUE);
        $response = $this->m_api->update_arrived_cfs($params);
		//print_r($this->dbx->last_query());
		//die();
        $this->response($response);
    }
}
