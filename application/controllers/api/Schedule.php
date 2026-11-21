<?php
use chriskacerguis\RestServer\RestController;
 
class Schedule extends RestController{
     
    public function __construct(){
        
		header('Access-Control-Allow-Origin: *');
		header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
		header(
			'Access-Control-Allow-Headers: ' .
			'Origin, Content-Type, Accept, Authorization, X-API-KEY'
		);

		// Penting: preflight tidak membawa API key aktual.
		// Hentikan sebelum RestController memvalidasi API key.
		if (
			isset($_SERVER['REQUEST_METHOD']) &&
			strtoupper($_SERVER['REQUEST_METHOD']) === 'OPTIONS'
		) {
			http_response_code(204);
			exit;
		}
		parent::__construct();
        $this->load->model(array('api/m_schedule_api'));
    }
	
	public function index_get(){
	$now=date('Y-m-d');
	$month = date('m');
	$city = $this->get('city');
	$etd_jkt = $this->get('etd');
	if($city || $etd_jkt){
        $response = $this->m_schedule_api->search_schedule($city,$etd_jkt);
		}else{
		$response = $this->m_schedule_api->get_schedule($now,$month);
		}
        $this->response($response);
	}
}
?>