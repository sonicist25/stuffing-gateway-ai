<?php
use chriskacerguis\RestServer\RestController;
 
class Get_document_list extends RestController{
     
    public function __construct(){
        parent::__construct();
        $this->load->model(array('api/m_api'));
    }
     
    public function index_get(){
		$id = $this->get('booking_id');
		$shipper = $this->get('shipper');
		if($id===null){
        $response = $this->m_api->get_bl_number($id,$shipper);
		}else{
		$response = $this->m_api->get_bl_number($id,$shipper);
		}
        $this->response($response);
    }
}
?>