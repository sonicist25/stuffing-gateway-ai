<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;
class Customer extends RestController {

    public function __construct() {
        parent::__construct();
		$this->load->model('api/crm/Customer_model');
    }

    /**
     * GET shipper data berdasarkan region_id atau sales
     * Endpoint: GET /shipper?region_id=xxx
     * Endpoint: GET /shipper?sales=xxx
     */
    public function index_get() {
        $sales = $this->get('sales');
        $q     = $this->get('q');
        $region_id    = $this->get('region');
        $page  = (int) $this->get('page') ?: 1;
        $limit = (int) $this->get('limit') ?: 20;
        $offset = ($page - 1) * $limit;
		
		// Filter keyword
			if ($q !== NULL) {
			$result = $this->Customer_model->search_customer($region_id,$sales, $q, $limit, $offset);
			}else{
			$result = $this->Customer_model->get_shipper($region_id, $sales, $limit, $offset);
			}

        if ($result['data']) {
            $this->response([
                'status' => true,
                'page'   => $page,
                'limit'  => $limit,
                'total'  => $result['total'],
                'data'   => $result['data']
            ], RestController::HTTP_OK);
        } else {
            $this->response([
                'status' => false,
                'message' => 'No customers found'
            ], RestController::HTTP_NOT_FOUND);
        }
    }


}