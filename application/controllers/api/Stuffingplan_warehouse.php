<?php
use chriskacerguis\RestServer\RestController;
 
class Stuffingplan_warehouse extends RestController{
      
    public function __construct(){
        parent::__construct();
        $this->load->model('api/m_api');
    }
     
    public function index_get(){
		$code = $this->get('code');
		$key = $this->get('filter');
		if($code===null){
			if($key===null){
				$response = $this->m_api->get_stuffing_plan($code,$key);
			}else{
				$response = $this->m_api->get_stuffing_plan($code,$key);
			}
		}else{
		$response = $this->m_api->get_stuffing_plan($code,$key);
		}
        $this->response($response);
    }
  public function index_put(){
	$code = $this->put('code');
        $data = array(
		'air_line' => $this->put('status'));
        $response = $this->m_api->update_stuffing_plan($data,$code);
        $this->response($response);
    }
}
?>