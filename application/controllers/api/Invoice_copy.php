<?php
use chriskacerguis\RestServer\RestController;
 
class Invoice_copy extends RestController{
     
    public function __construct(){
        parent::__construct();
        $this->load->model(array('api/m_invoice_api','finance/m_inv_to_shipper'));
    }
	
	public function index_get(){
	$invoice_id = $this->get('id');
	$response = $this->m_invoice_api->get_data_invoice($invoice_id);
    $this->response($response);
	}
}
?>