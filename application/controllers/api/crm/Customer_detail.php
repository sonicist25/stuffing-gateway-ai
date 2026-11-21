<?php
use chriskacerguis\RestServer\RestController;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Customer_detail extends RestController {

    private $jwt_key = "your-secret-key";

    public function __construct() {
		parent::__construct();

		// Handle CORS preflight
		header('Access-Control-Allow-Origin: *');
		header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
		header('Access-Control-Allow-Headers: Origin, Accept, Content-Type, Authorization, X-Requested-With, X-API-KEY');

		if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
			http_response_code(200);
			exit;
		}

		$this->load->database('master', TRUE);
		$this->load->model('api/crm/m_customer_detail');
	}

    private function get_login_sales_id()
    {
        $authHeader = $this->input->get_request_header('Authorization', TRUE);

        if (!$authHeader || stripos($authHeader, 'Bearer ') !== 0) {
            return [
                'status'  => false,
                'message' => 'Missing or invalid Authorization header',
                'code'    => RestController::HTTP_UNAUTHORIZED
            ];
        }

        $token = trim(substr($authHeader, 7));

        try {
            $decoded = JWT::decode($token, new Key($this->jwt_key, 'HS256'));

            if (empty($decoded->data->user_id)) {
                return [
                    'status'  => false,
                    'message' => 'sales not found in token payload',
                    'code'    => RestController::HTTP_UNAUTHORIZED
                ];
            }

            return [
                'status'   => true,
                'sales_id' => $decoded->data->user_id,
                'decoded'  => $decoded
            ];

        } catch (Exception $e) {
            return [
                'status'  => false,
                'message' => 'Invalid or expired token',
                'error'   => $e->getMessage(),
                'code'    => RestController::HTTP_UNAUTHORIZED
            ];
        }
    }

    public function profile_get($id_shipper = NULL) {
        // Ambil id_shipper dari parameter GET jika tidak ada di function
        if ($id_shipper === NULL) {
            $id_shipper = $this->get('id_shipper');
        }

        if (!$id_shipper) {
            return $this->response([
                'status' => false,
                'message' => 'id_shipper is required'
            ], RestController::HTTP_BAD_REQUEST);
        }

        // Ambil sales_id dari JWT login
        $login = $this->get_login_sales_id();

        if (!$login['status']) {
            return $this->response([
                'status'  => false,
                'message' => $login['message'],
                'error'   => isset($login['error']) ? $login['error'] : null
            ], $login['code']);
        }

        $id_sales = $login['sales_id'];

        // Ambil data dari model
        $shipper_detail = $this->m_customer_detail->get_shipper_detail($id_shipper);

        // Jika tidak ada data shipper
        if (!$shipper_detail) {
            return $this->response([
                'status' => false,
                'message' => 'Shipper not found'
            ], RestController::HTTP_NOT_FOUND);
        }

        $total_shipment  = $this->m_customer_detail->get_total_shipment($id_shipper, $id_sales);
        $open_invoices   = $this->m_customer_detail->get_open_invoices($id_shipper, $id_sales);
        $latest_invoices = $this->m_customer_detail->get_latest_invoices($id_shipper, 8, $id_sales);
        $volume_chart    = $this->m_customer_detail->get_volume_chart($id_shipper, $id_sales);
        $last_bookings   = $this->m_customer_detail->get_last_bookings($id_shipper, 8, $id_sales);
        $spending        = $this->m_customer_detail->get_sum_price_per_month($id_shipper, $id_sales);

        return $this->response([
            'status' => true,
            'data' => [
                'id_shipper'        => $id_shipper,
                'sales_id'          => $id_sales,
                'shipper_detail'    => $shipper_detail,
                'total_shipment'    => $total_shipment,
                'open_invoices'     => $open_invoices,
                'latest_invoices'   => $latest_invoices,
                'volume_chart'      => $volume_chart,
                'last_bookings'     => $last_bookings,
                'spending_bookings' => $spending,
            ]
        ], RestController::HTTP_OK);
    }
}