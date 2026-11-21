<?php
use chriskacerguis\RestServer\RestController;
 
class Update_cargo_status extends RestController{
      
    public function __construct(){
        parent::__construct();
        $this->load->model('api/m_api');
    }

  public function index_put(){
	$code = $this->put('booking_code');
        $data = $this->put('status_cargo');
        $response = $this->m_api->update_stuffing_plan_detail($data,$code);
		//print_r($this->dbx->last_query());
		//die();
        $this->response($response);
    }
}
?>