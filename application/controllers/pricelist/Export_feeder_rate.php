<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Export_feeder_rate extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('pricelist/M_freight_crud');
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

    // =========================================================
    // 1. FUNGSI INDEX INI YANG SEBELUMNYA HILANG (MEMICU 404)
    // =========================================================
    public function index() {
        $data['title']       = 'Master Freight Pricing';
        $data['main_view']   = 'pricelist/v_freight_crud'; // View yang diload ke div #main
        $data['script']      = 'freight_crud'; // JS yang diload

        // Asumsi nama file template master Anda adalah 'template'
        $this->load->view('afg_template', $data); 
    }

    // =========================================================
    // 2. FUNGSI UNTUK JQGRID (Pastikan ini juga tidak hilang)
    // =========================================================
	  public function get_grid_data() {
		$page  = $this->input->post('page') ? intval($this->input->post('page')) : 1;
		$limit = $this->input->post('rows') ? intval($this->input->post('rows')) : 20;
		$sidx  = $this->input->post('sidx') ? $this->input->post('sidx') : 'id';
		$sord  = $this->input->post('sord') ? $this->input->post('sord') : 'DESC';

		// Ambil filters (jqGrid mengirimkan JSON di post 'filters')
		$filters = $this->input->post('filters');

		// Panggil model dengan parameter filter
		$result = $this->M_freight_crud->get_all_rates($page, $limit, $sidx, $sord, $filters);

		// Kembalikan JSON ke jqGrid
		echo json_encode($result);
	}
    // =========================================================
    // 3. FUNGSI GET_DATA_EDIT (Patch 2 Tab)
    // =========================================================
    // =========================================================
    // 3. FUNGSI GET_DATA_EDIT 
    // =========================================================
    public function get_data_edit() {
        $id = $this->input->post('id');
        $header = $this->db->get_where('freight_rates', ['id' => $id])->row();
        
        if ($header) {
            // Ambil text Port untuk Select2 (Sesuaikan 'tableName' dengan tabel port Anda)
            if ($header->destination_id) {
                $dest = $this->db->get_where('tableName', ['id' => $header->destination_id])->row(); 
                
                // Ambil nama dasarnya dulu
                $header->destination_name = $dest ? $dest->name : ''; 
                
                // Cek apakah kolom country_code dan port_code BENAR-BENAR ADA di tabel
                if ($dest && isset($dest->country_code) && isset($dest->port_code)) {
                    $header->destination_name .= ", " . $dest->country_code . " (" . $dest->port_code . ")";
                }
            }
            
            if ($header->origin_id) {
                $origin = $this->db->get_where('tableName', ['id' => $header->origin_id])->row();
                
                // Ambil nama dasarnya dulu
                $header->origin_name = $origin ? $origin->name : ''; 
                
                // Cek apakah kolom country_code dan port_code BENAR-BENAR ADA di tabel
                if ($origin && isset($origin->country_code) && isset($origin->port_code)) {
                    $header->origin_name .= ", " . $origin->country_code . " (" . $origin->port_code . ")";
                }
            }
        }

        $lcl_routes = $this->db->get_where('freight_rate_lcl', ['freight_rate_id' => $id])->result();
        $fcl_details = $this->db->order_by('sort_order', 'ASC')->get_where('freight_rate_details', ['freight_rate_id' => $id])->result();
        $lcl_details = $this->db->get_where('freight_rate_lcl_details', ['freight_rate_id' => $id])->result();
        
        echo json_encode([
            'header'      => $header, 
            'lcl_routes'  => $lcl_routes, 
            'fcl_details' => $fcl_details,
            'lcl_details' => $lcl_details
        ]);
    }

    // =========================================================
    // 4. FUNGSI SAVE (Patch 2 Tab)
    // =========================================================
    public function save() {
        $id = $this->input->post('id');

       $header_data = array(
            'origin_id'         => $this->input->post('origin_id'),
            'destination_id'    => $this->input->post('destination_id'),
            'liner_name'        => $this->input->post('liner_name'),
            'cargo_type'        => $this->input->post('cargo_type'),
            
            // Validitas FCL
            'fcl_validity_from' => $this->input->post('fcl_validity_from') ?: null,
            'fcl_validity_to'   => $this->input->post('fcl_validity_to') ?: null,
            'fcl_free_time'     => $this->input->post('fcl_free_time'),
            'fcl_transit_time'  => $this->input->post('fcl_transit_time'),
            'fcl_include_rate'  => $this->input->post('fcl_include_rate'),
            'fcl_exclude_rate'  => $this->input->post('fcl_exclude_rate'),
            'fcl_remarks'       => $this->input->post('fcl_remarks'),
            
            // Validitas LCL (Baru)
            'lcl_validity_from' => $this->input->post('lcl_validity_from') ?: null,
            'lcl_validity_to'   => $this->input->post('lcl_validity_to') ?: null,
            'lcl_include_rate'  => $this->input->post('lcl_include_rate'),
            'lcl_exclude_rate'  => $this->input->post('lcl_exclude_rate'),
            'lcl_remarks'       => $this->input->post('lcl_remarks'),
            
            // Audit Trail
            'updated_at'        => date('Y-m-d H:i:s'),
            'updated_by'        => $this->session->userdata('user_id') // Sesuaikan dengan session ID login Anda
        );

        $fcl_components = array(
            'group'        => $this->input->post('fcl_group'),
            'service_name' => $this->input->post('fcl_service_name'),
            'unit'         => $this->input->post('fcl_unit'),
            'price_20'     => $this->input->post('fcl_price_20'),
            'price_40'     => $this->input->post('fcl_price_40'),
            'price_40hc'   => $this->input->post('fcl_price_40hc')
        );

        $lcl_components = array(
            'group'        => $this->input->post('lcl_group'),
            'service_name' => $this->input->post('lcl_service_name'),
            'unit'         => $this->input->post('lcl_unit'),
            'price'        => $this->input->post('lcl_price_wm')
        );

        $lcl_routes   = $this->input->post('lcl');
        $lcl_transit  = $this->input->post('lcl_transit');
        $lcl_priority = $this->input->post('lcl_priority');

        $status = $this->M_freight_crud->save_rate_data($id, $header_data, $fcl_components, $lcl_components, $lcl_routes, $lcl_transit, $lcl_priority);

        if ($status) {
            echo json_encode(['status' => 'success', 'message' => 'Data Pricing Berhasil Disimpan']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal Menyimpan Data ke Database']);
        }
    }
	
	  // Proses Hapus
    public function delete() {
        $id = $this->input->post('id');
        $this->db->where('id', $id);
        $this->db->delete('freight_rates'); // Jika ada ON DELETE CASCADE di DB, otomatis detail terhapus
        echo json_encode(['status' => 'success', 'msg' => 'Data berhasil dihapus']);
    }
}