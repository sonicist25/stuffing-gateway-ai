<?php
use chriskacerguis\RestServer\RestController;
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

class Shipment_list extends RestController
{
    private $jwt_key = "your-secret-key";

    public function __construct() {
        parent::__construct();
        $this->load->model('api/crm/M_shipment_list');

        // ambil dari config, atau bisa juga hardcode kalau mau
        //$this->jwt_key = $this->config->item('jwt_key'); 
        // atau:
        // $this->jwt_key = 'rahasia-jwt-kamu-di-sini';
    }

    // Endpoint: GET /api/booking_list
    public function index_get() {
        // Ambil header Authorization: Bearer xxxx.yyyy.zzzz
        $authHeader = $this->input->get_request_header('Authorization', TRUE);
        if (!$authHeader || stripos($authHeader, 'Bearer ') !== 0) {
            return $this->response([
                'status'  => false,
                'message' => 'Missing or invalid Authorization header'
            ], RestController::HTTP_UNAUTHORIZED);
        }

        $token = trim(substr($authHeader, 7));

        try {
            $decoded = JWT::decode($token, new Key($this->jwt_key, 'HS256'));

            if (empty($decoded->data->user_id)) {
                return $this->response([
                    'status'  => false,
                    'message' => 'sales not found in token payload'
                ], RestController::HTTP_UNAUTHORIZED);
            }

            $id_sales = $decoded->data->user_id;
			//print_r($id_sales);
			//die();
			
            $data = $this->M_shipment_list->get_combined_bookings($id_sales);

            if (!empty($data['bookings']) || !empty($data['last_bookings'])) {
                return $this->response([
                    'status' => true,
                    'data'   => $data
                ], RestController::HTTP_OK);
            } else {
                return $this->response([
                    'status'  => false,
                    'message' => 'No bookings found'
                ], RestController::HTTP_NOT_FOUND);
            }
        } catch (Exception $e) {
            return $this->response([
                'status'  => false,
                'message' => 'JWT error: '.$e->getMessage()
            ], RestController::HTTP_UNAUTHORIZED);
        }
    }
}
