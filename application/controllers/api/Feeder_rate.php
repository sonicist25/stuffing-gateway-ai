<?php
use chriskacerguis\RestServer\RestController;

class Feeder_rate extends RestController
{
    public function __construct()
    {
        parent::__construct();

        // =======================================================
        // 1. IZINKAN CORS & HEADERS (SANGAT PENTING UNTUK REACT)
        // =======================================================
        header("Access-Control-Allow-Origin: *"); 
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization, X-API-KEY, x-api-key");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Credentials: true");

        // Tangkap metode OPTIONS (Preflight) dari Browser
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            http_response_code(200);
            exit(0);
        }
        // =======================================================

        $this->load->model('api/M_feeder_rate', 'm_feeder_rate');
    }

    /**
     * GET /api/feeder_rate
     */
    public function index_get()
    {
        // --- BACA API KEY DARI HEADERS ATAU URL PARAMETER ---
        $api_key = $this->input->get_request_header('X-API-KEY', TRUE);
        
        // Fallback: Jika kosong, coba baca dari header huruf kecil atau URL biasa
        if (empty($api_key)) {
            $api_key = $this->input->get_request_header('x-api-key', TRUE) ?: $this->get('X-API-KEY');
        }

        if ($api_key !== 'gateway-fms') {
            return $this->response(
                [
                    'status'  => false,
                    'message' => 'Invalid API KEY',
                ],
                RestController::HTTP_UNAUTHORIZED
            );
        }

        $origin      = $this->get('origin');       // SEA: region_id, AIR: origin_code / nama
        $destination = $this->get('destination');  // SEA: destination, AIR: dest_code / nama
        $valid_on    = $this->get('valid_on');     // Y-m-d
        $service     = $this->get('service');      // lcl|fcl|all
        $mode        = $this->get('mode');         // sea|air|all

        if ($service === NULL || $service === '') {
            $service = 'all';
        }
        if ($mode === NULL || $mode === '') {
            $mode = 'all';
        }

        $sea = ['lcl' => [], 'fcl' => []];
        $air = [];

        // SEA (LCL/FCL)
        if ($mode === 'sea' || $mode === 'all') {
            $sea = $this->m_feeder_rate->get_feeder_rates(
                $origin,
                $destination,
                $service
            );
        }

        // AIRFREIGHT
        if ($mode === 'air' || $mode === 'all') {
            $air = $this->m_feeder_rate->get_air_rates(
                $origin,
                $destination
            ); // Hapus tanda koma ekstra di baris ini
        }

        // bentuk data sesuai mode
        if ($mode === 'sea') {
            $data = $sea;
        } elseif ($mode === 'air') {
            $data = ['air' => $air];
        } else {
            $data = [
                'sea' => $sea,
                'air' => $air,
            ];
        }

        return $this->response(
            [
                'status'  => true,
                'message' => 'OK',
                'data'    => $data,
            ],
            RestController::HTTP_OK
        );
    }
}