<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;

class Pricing_import extends RestController {

    public function __construct() {
        parent::__construct();
        
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization, X-API-KEY, x-api-key");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Credentials: true");

        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            http_response_code(200);
            exit(0);
        }
        
        $this->load->model('pricelist/M_import');
    }

    /**
     * Endpoint: GET /api/pricing_import
     * Fungsi: Mengambil List Utama Import FCL & LCL untuk React FE
     */
    public function index_get() {
        $grouped_rates = $this->M_import->get_grouped_rates();

        if (!empty($grouped_rates['fcl']) || !empty($grouped_rates['lcl'])) {
            $this->response([
                'status' => 'success',
                'data'   => $grouped_rates
            ], RestController::HTTP_OK); 
        } else {
            $this->response([
                'status'  => 'error',
                'message' => 'No import rates found'
            ], RestController::HTTP_NOT_FOUND); 
        }
    }

    /**
     * Endpoint: POST /api/pricing_import/details
     * Fungsi: Mengambil Detail Local Charge untuk Modal PricingImportPage.jsx
     */
    public function details_post() {
        $rate_id = $this->post('rate_id');
        $rate_type = $this->post('rate_type'); // 'fcl' atau 'lcl'
        
        if (!$rate_id || !$rate_type) {
            $this->response(['status' => 'error', 'message' => 'Rate ID and Type are required'], 400);
            return;
        }

        $details = $this->M_import->get_rate_details($rate_type, $rate_id);

        if ($details) {
            // Mapping array agar dapat di-loop dengan sempurna oleh FE component PricingDetailModal
            $fcl_data = [];
            $lcl_data = [];

            // 1. Masukkan Destination Local Charges ke dalam group utama
            if (!empty($details['local_charges'])) {
                if ($rate_type === 'fcl') {
                    $fcl_data['Destination Local Charges'] = $details['local_charges'];
                } else {
                    $lcl_data['Destination Local Charges'] = $details['local_charges'];
                }
            }

            // 2. Loop & sisipkan setiap group dari Origin Extra Charges
            if (!empty($details['origin_charges'])) {
                foreach ($details['origin_charges'] as $group_name => $items) {
                    if ($rate_type === 'fcl') {
                        $fcl_data["Origin Charges - $group_name"] = $items;
                    } else {
                        $lcl_data["Origin Charges - $group_name"] = $items;
                    }
                }
            }

            $this->response([
                'status'      => 'success', 
                'header'      => $details['header'],
                'fcl_data'    => $fcl_data,
                'lcl_data'    => $lcl_data,
                'rebate_info' => $details['rebate_info']
            ], 200); 
        } else {
            $this->response(['status' => 'error', 'message' => 'Details not found'], 404); 
        }
    }
}