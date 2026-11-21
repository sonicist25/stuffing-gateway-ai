<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Pastikan Anda sudah menginstal library chriskacerguis/codeigniter-restserver
use chriskacerguis\RestServer\RestController;

class Pricing extends RestController {

    public function __construct() {
        parent::__construct();
        
        // 1. HEADER CORS HARUS DI LUAR 'IF', AGAR BERLAKU UNTUK 'GET' JUGA
        header("Access-Control-Allow-Origin: *"); // Boleh diganti http://localhost:5173 jika ingin ketat
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization, X-API-KEY, x-api-key");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Credentials: true");

        // 2. TANGKAP PREFLIGHT BROWSER
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            http_response_code(200);
            exit(0);
        }
        
        $this->load->model('pricelist/M_freight');
    }

    /**
     * PATCH: ambil parameter request dari mana pun ia dikirim.
     *
     * $this->post() hanya membaca $_POST, dan PHP cuma mengisi $_POST
     * untuk body application/x-www-form-urlencoded atau multipart.
     * Kalau frontend mengirim JSON (axios/fetch dengan Content-Type
     * application/json), $_POST KOSONG sehingga semua parameter
     * terbaca null. Di sini body JSON ikut dibaca sebagai cadangan,
     * lalu query string sebagai cadangan terakhir.
     */
    private function req_param($key, $default = null) {
        // 1. body form-urlencoded / multipart  -> $_POST
        $v = $this->post($key);

        // 2. body JSON. php://input bisa SUDAH habis dibaca oleh
        //    RestController, jadi pakai cache CodeIgniter lebih dulu.
        if ($v === null || $v === '') {
            $raw = $this->input->raw_input_stream;
            if (empty($raw)) { $raw = file_get_contents('php://input'); }
            if (!empty($raw)) {
                $json = json_decode($raw, true);
                if (is_array($json) && array_key_exists($key, $json)) {
                    $v = $json[$key];
                } else {
                    parse_str($raw, $form);          // body form yg tdk masuk $_POST
                    if (is_array($form) && array_key_exists($key, $form)) {
                        $v = $form[$key];
                    }
                }
            }
        }

        // 3. body yang sudah diparse RestController
        if (($v === null || $v === '') && isset($this->request->body)) {
            $b = $this->request->body;
            if (is_array($b) && array_key_exists($key, $b)) { $v = $b[$key]; }
        }

        // 4. query string (?rate_id=..&port_key=..) - jalan utk POST juga
        if ($v === null || $v === '') { $v = $this->input->get($key); }

        // 5. cadangan terakhir
        if (($v === null || $v === '') && isset($_REQUEST[$key])) { $v = $_REQUEST[$key]; }

        if ($v === 'null' || $v === 'undefined' || $v === '') { $v = null; }
        return ($v === null) ? $default : $v;
    }

    /**
     * Endpoint: GET /freight
     * Fungsi: Mengambil List Utama yang sudah dikelompokkan
     */
    public function index_get() {
        $grouped_rates = $this->M_freight->get_grouped_rates();

        if (!empty($grouped_rates)) {
            $this->response([
                'status' => 'success',
                'data'   => $grouped_rates
            ], RestController::HTTP_OK); 
        } else {
            $this->response([
                'status'  => 'error',
                'message' => 'No freight rates found'
            ], RestController::HTTP_NOT_FOUND); 
        }
    }

    /**
     * Endpoint: POST /freight/details
     * Fungsi: Mengambil Detail berdasarkan rate_id (Support 2 Tab FCL & LCL)
     */
   public function details_post() {
        $rate_id = $this->req_param('rate_id');
        
        if (!$rate_id) {
            $this->response(['status' => 'error', 'message' => 'ID is required'], 400);
            return;
        }

        // 1. Ambil Header
        $header = $this->db->get_where('freight_rates', ['id' => $rate_id])->row();
        
        // 2. Ambil LCL Routes (Gunakan Order By Prioritas agar React bisa memilahnya)
        $this->db->order_by('is_priority', 'DESC');
        $this->db->order_by('price', 'ASC');
        $lcl_routes = $this->db->get_where('freight_rate_lcl', ['freight_rate_id' => $rate_id])->result();
        
        // 3. Ambil Detail Komponen FCL
        $fcl_raw = $this->M_freight->get_fcl_details($rate_id);
        $fcl_data = [];
        $group_names = [1 => 'Freight Components', 2 => 'Inland Moda', 3 => 'Subject To'];
        
        if (!empty($fcl_raw)) {
            foreach ($fcl_raw as $f) {
                $g_name = isset($group_names[$f->group_id]) ? $group_names[$f->group_id] : 'Other';
                $fcl_data[$g_name][] = [
                    'component_name' => $f->component_name,
                    'unit_basis'     => $f->unit_basis,
                    'price_20'       => $f->price_20,
                    'price_40'       => $f->price_40,
                    'price_40hc'     => $f->price_40hc
                ];
            }
        }

        // 4. Ambil Detail Komponen LCL (Tabel Baru)
        $lcl_raw = $this->db->get_where('freight_rate_lcl_details', ['freight_rate_id' => $rate_id])->result();
        $lcl_data = [];
        
        if (!empty($lcl_raw)) {
            foreach ($lcl_raw as $l) {
                // Pastikan key-nya sesuai dengan yang diproses React (service_name, unit_basis, price_wm)
                $g_name = !empty($l->lcl_group) ? $l->lcl_group : 'Charges';
                $lcl_data[$g_name][] = [
                    'service_name' => $l->service_name,
                    'unit_basis'   => $l->unit_basis,
                    'price_wm'     => $l->price_wm
                ];
            }
        }

        $this->response([
            'status'     => 'success', 
            'header'     => $header,
            'lcl_routes' => $lcl_routes, 
            'fcl_data'   => $fcl_data,
            'lcl_data'   => $lcl_data // Data ini sekarang berisi detail LCL
        ], 200); 
    }
	
	// Application/controllers/api/Pricing.php

    /**
     * Endpoint: GET /transhipment
     * Fungsi: Mengambil List Utama Transhipment Rates yang sudah dikelompokkan
     */
    public function transhipment_get() {
        $transhipment_rates = $this->M_freight->get_grouped_transhipment_rates();

        if (!empty($transhipment_rates)) {
            $this->response([
                'status' => 'success',
                'data'   => $transhipment_rates
            ], RestController::HTTP_OK); 
        } else {
            $this->response([
                'status'  => 'error',
                'message' => 'No transhipment rates found'
            ], RestController::HTTP_NOT_FOUND); 
        }
    }

    /**
     * Endpoint: POST /transhipment/details
     * Fungsi: Mengambil Detail Transhipment berdasarkan rate_id
     */
    public function transhipment_details_post() {
        $rate_id  = (int) $this->req_param('rate_id', 0);
        $port_key = $this->req_param('port_key');

        // PATCH: baris VIA SIN adalah baris PERBANDINGAN yang mewakili 3
        // agen sekaligus, jadi tidak membawa rate_id - hanya port_key.
        if (!$rate_id && $port_key) {
            $rate_id = (int) $this->M_freight->get_sin_representative_rate_id($port_key);
        }

        if (!$rate_id) {
            $this->response([
                'status'  => 'error',
                'message' => 'rate_id atau port_key wajib diisi',
                
            ], RestController::HTTP_BAD_REQUEST);
            return;
        }

        // get_transhipment_detail() sudah ikut mengambil kolom header
        // (agent_code, agent_name, validity, validity_date) lewat JOIN FK.
        $detail = $this->M_freight->get_transhipment_detail($rate_id);

        if (!$detail) {
            $this->response([
                'status'  => 'error',
                'message' => 'Transhipment rate details not found'
            ], RestController::HTTP_NOT_FOUND);
            return;
        }

        // PATCH: biaya lokal di pelabuhan tujuan, dikelompokkan
        // agen sumber -> jenis penerima (COLOADER / DIRECT / PERSONAL EFFECTS).
        $local_charges = [];
        $charge_ports  = [];
        foreach ($this->M_freight->get_local_charges_for_destination($detail->destination) as $c) {
            $src = !empty($c->source_agent) ? $c->source_agent : 'AGENT';
            $local_charges[$src][$c->party_type][] = $c;
            if (!empty($c->port) && !in_array($c->port, $charge_ports, TRUE)) {
                $charge_ports[] = $c->port;
            }
        }

        // PATCH: kontak agen di pelabuhan tujuan.
        $agents = $this->M_freight->get_agents_for_destination($detail->destination);

        // PATCH: utk VIA SIN, kirim tarif ketiga agen Singapura supaya
        // frontend bisa menampilkannya berdampingan.
        $sin_agents = [];
        if ($detail->ts_type === 'VIA SIN') {
            $pk = $port_key;
            if (!$pk) {
                $row = $this->db->select('port_key')
                                ->get_where('ts_port_map',
                                    ['source' => 'RATE',
                                     'port_raw' => $detail->destination])->row();
                $pk = $row ? $row->port_key : null;
            }
            if ($pk) $sin_agents = $this->M_freight->get_sin_agent_rows($pk);
        }

        $this->response([
            'status'        => 'success',
            'data'          => $detail,
            'local_charges' => $local_charges,
            'charge_ports'  => $charge_ports,
            'agents'        => $agents,
            'sin_agents'    => $sin_agents
        ], RestController::HTTP_OK);
    }
	
	/**
     * Endpoint: GET /pricing/clearance
     * Fungsi: Mengambil List Tarif Customs Clearance & Info Dokumen
     */
    public function clearance_get() {
        $this->load->model('pricelist/M_clearance');
        $data = $this->M_clearance->get_all_for_api();

        if (!empty($data['tariffs'])) {
            $this->response([
                'status' => 'success',
                'data'   => $data
            ], RestController::HTTP_OK); 
        } else {
            $this->response([
                'status'  => 'error',
                'message' => 'No customs clearance rates found'
            ], RestController::HTTP_NOT_FOUND); 
        }
    }
}