<?php
use chriskacerguis\RestServer\RestController;
 
class Invoice_status extends RestController{
     
    public function __construct(){
        parent::__construct();
        $this->load->model(array('api/m_invoice_api'));
    }
	
	public function index_get(){
	$invoice_id = $this->get('number');
	$response = $this->m_invoice_api->get_all($invoice_id);
    $this->response($response);
	}
}
?>