<?php
use chriskacerguis\RestServer\RestController;
 
class Stuffingplan_data extends RestController{
      
    public function __construct(){
        parent::__construct();
        $this->load->model('api/m_api');
    }
     
    public function index_get(){

        $response = $this->m_api->get_stuffing_plan_data();
        $this->response($response);
    }
}
?>