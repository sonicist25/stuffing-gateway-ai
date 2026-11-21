<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Trucking extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('pricelist/M_trucking');
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
        $data['title']       = 'Master Inland/Trucking Rates';
        $data['main_view']   = 'pricelist/v_trucking'; 
        $data['script']      = 'trucking_crud'; 
        $this->load->view('afg_template', $data); 
    }

    public function get_grid_data() {
        $page    = $this->input->post('page') ?: 1;
        $limit   = $this->input->post('rows') ?: 20;
        $sidx    = $this->input->post('sidx') ?: 'destination_name';
        $sord    = $this->input->post('sord') ?: 'ASC';
        $filters = $this->input->post('filters');

        $data = $this->M_trucking->get_all_trucking($page, $limit, $sidx, $sord, $filters);
        echo json_encode($data);
    }

    public function get_data_edit() {
        $id = $this->input->post('id');
        $data = $this->db->get_where('trucking_rates', ['id' => $id])->row_array();
        echo json_encode($data);
    }

    public function save() {
        $id = $this->input->post('id');
        
        // Langsung tangkap nama destinasi dari input
        $data = array(
            'destination_name' => strtoupper($this->input->post('destination_name')), // Format huruf besar
            'cdd_price'        => floatval(str_replace(['Rp', '.', ','], '', $this->input->post('cdd_price'))),
            'fuso_price'       => floatval(str_replace(['Rp', '.', ','], '', $this->input->post('fuso_price'))),
            'wb_price'         => floatval(str_replace(['Rp', '.', ','], '', $this->input->post('wb_price'))),
            'price_20'         => floatval(str_replace(['Rp', '.', ','], '', $this->input->post('price_20'))),
            'price_40'         => floatval(str_replace(['Rp', '.', ','], '', $this->input->post('price_40'))),
            'updated_at'       => date('Y-m-d H:i:s'),
            'updated_by'       => $this->session->userdata('user_id')
        );

        if ($this->M_trucking->save_rate($id, $data)) {
            echo json_encode(['status' => 'success', 'msg' => 'Data Trucking Berhasil Disimpan']);
        } else {
            echo json_encode(['status' => 'error', 'msg' => 'Gagal Menyimpan Data']);
        }
    }

    public function delete() {
        $id = $this->input->post('id');
        if ($this->M_trucking->delete_rate($id)) {
            echo json_encode(['status' => 'success', 'msg' => 'Data Berhasil Dihapus']);
        }
    }
}