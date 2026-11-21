<?php
use chriskacerguis\RestServer\RestController;
 
class Schedule_import extends RestController{
     
    public function __construct(){
        parent::__construct();
        $this->load->model(array('api/m_schedule_api'));
    }
	
	public function index_get(){
	$now=date('Y-m-d');	
	$month = date('m');
	$city = $this->get('city');
	$eta_jkt = $this->get('eta');
	if($city && $eta_jkt){
        $response = $this->m_schedule_api->search_schedule_import($city,$eta_jkt);
		}else{
		$response = $this->m_schedule_api->get_schedule_import($now,$month);
		}
        $this->response($response);
	}
}
?>