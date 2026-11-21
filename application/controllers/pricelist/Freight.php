<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Freight extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('pricelist/M_freight');
        $this->load->model('pricelist/M_clearance');
        // Pengecekan Session Anda...
        if (empty($this->session->userdata('region_id'))) { 
            if ($this->input->is_ajax_request()) {
                header('HTTP/1.1 401 Unauthorized');
                echo json_encode(['status' => 'expired', 'message' => 'Session expired']);
                exit;
            } else {
                redirect('');
            }
        }
    }

    public function index() {
        $data['title'] = 'live_rates';
        $data['main_view'] = 'pricelist/freight_list';
        
        // 1. Ambil Data Sea Freight (FCL & LCL)
        $data['grouped_rates'] = $this->M_freight->get_grouped_rates();
        
        // 2. Ambil Data Air Freight
        $data['air_rates'] = $this->M_freight->get_grouped_air_rates();
        
        // 3. Ambil Data Inland / Trucking
        $data['trucking_rates'] = $this->M_freight->get_trucking_rates();

        // 4. Ambil Data Transhipment (Baru)
        $data['transhipment_rates'] = $this->M_freight->get_grouped_transhipment_rates();
		
		$data['clearance_data'] = $this->M_clearance->get_all_for_api();

        $this->load->view('afg_template', $data);
    }

    public function get_details() {
        $rate_id = $this->input->post('rate_id');
        if (!$rate_id) {
            echo json_encode(['status' => 'error', 'message' => 'ID is required']);
            return;
        }

        $header = $this->db->get_where('freight_rates', ['id' => $rate_id])->row();
        if (!$header) {
            echo json_encode(['status' => 'error']); 
            return;
        }

        // 1. Ambil Ringkasan Rute LCL via Active Record biasa
        $lcl_routes = $this->db->order_by('price', 'ASC')->get_where('freight_rate_lcl', ['freight_rate_id' => $rate_id])->result();
        
        // 2. Panggil Rincian FCL dari Model & Kelompokkan berdasarkan Group ID
        $fcl_raw = $this->M_freight->get_fcl_details($rate_id);
        $fcl_data = [];
        $group_names = [1 => 'Freight Components', 2 => 'Inland Moda', 3 => 'Subject To'];
        foreach ($fcl_raw as $f) {
            $g_name = isset($group_names[$f->group_id]) ? $group_names[$f->group_id] : 'Other';
            $fcl_data[$g_name][] = $f;
        }

        // 3. Panggil Rincian LCL dari Model & Kelompokkan berdasarkan Nama Grup Teks
        $lcl_raw = $this->M_freight->get_lcl_details($rate_id);
        $lcl_data = [];
        foreach ($lcl_raw as $l) {
            $g_name = !empty($l->lcl_group) ? $l->lcl_group : 'Charges';
            $lcl_data[$g_name][] = $l;
        }

        echo json_encode([
            'status' => 'success', 
            'header' => $header,
            'lcl_routes' => $lcl_routes, 
            'fcl_data' => $fcl_data,
            'lcl_data' => $lcl_data
        ]);
    }
	
	public function get_transhipment_details() {
        $rate_id  = (int) $this->input->post('rate_id');
        $port_key = $this->input->post('port_key');

        // PATCH: baris perbandingan VIA SIN mewakili 3 agen sekaligus,
        // jadi tombolnya hanya mengirim port_key tanpa rate_id. Ambil
        // baris wakilnya di sini. (Sebelumnya selalu "ID is required".)
        if (!$rate_id && $port_key) {
            $rate_id = (int) $this->M_freight->get_sin_representative_rate_id($port_key);
        }

        if (!$rate_id) {
            $this->output->set_content_type('application/json');
            echo json_encode(['status' => 'error',
                              'message' => 'ID atau port_key wajib diisi']);
            return;
        }

        $detail = $this->M_freight->get_transhipment_detail($rate_id);
        
        if (!$detail) {
            $this->output->set_content_type('application/json');
            echo json_encode(['status' => 'error', 'message' => 'Data not found']); 
            return;
        }

        // PATCH: ambil biaya lokal di pelabuhan tujuan, dikelompokkan
        // per jenis penerima (COLOADER / DIRECT / PERSONAL EFFECTS).
        $local_charges = [];
        $charge_ports  = [];
        // Dikelompokkan 2 tingkat: agen sumber -> jenis penerima. Satu
        // pelabuhan bisa punya tarif lokal dari lebih dari satu agen
        // (mis. CHARTERLINK dan JCONSOL), jadi keduanya ditampilkan
        // berdampingan agar bisa dibandingkan.
        foreach ($this->M_freight->get_local_charges_for_destination($detail->destination) as $c) {
            $src = !empty($c->source_agent) ? $c->source_agent : 'AGENT';
            $local_charges[$src][$c->party_type][] = $c;
            if (!empty($c->port) && !in_array($c->port, $charge_ports, TRUE)) {
                $charge_ports[] = $c->port;
            }
        }

        // PATCH: ambil data agen di pelabuhan tujuan.
        $agents = $this->M_freight->get_agents_for_destination($detail->destination);

        // PATCH: utk VIA SIN, sertakan tarif ketiga agen Singapura supaya
        // staff bisa membandingkan sendiri di dalam modal.
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

        $this->output->set_content_type('application/json');
        echo json_encode([
            'status'        => 'success', 
            'data'          => $detail,
            'local_charges' => $local_charges,
            'charge_ports'  => $charge_ports,
            'agents'        => $agents,
            'sin_agents'    => $sin_agents
        ]);
    }
}