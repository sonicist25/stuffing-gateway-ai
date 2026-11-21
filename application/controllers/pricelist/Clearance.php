<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Clearance extends CI_Controller {

    public function __construct() {
        parent::__construct();
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
        $data['title'] = 'Customs Clearance Rates';
        $data['main_view'] = 'pricelist/v_clearance';
        $this->load->view('afg_template', $data);
    }

    // Data untuk jqGrid
    public function get_grid_data() {
        $page = $this->input->post('page') ?: 1;
        $limit = $this->input->post('rows') ?: 50;
        $sidx = $this->input->post('sidx') ?: 'service_type';
        $sord = $this->input->post('sord') ?: 'ASC';

        $this->db->start_cache();
        $this->db->from('clearance_tariffs');
        $this->db->stop_cache();

        $count = $this->db->count_all_results();
        $total_pages = ($count > 0) ? ceil($count / $limit) : 0;
        if ($page > $total_pages) $page = $total_pages;
        $start = $limit * $page - $limit;
        if ($start < 0) $start = 0;

        $this->db->order_by($sidx, $sord);
        $this->db->limit($limit, $start);
        $result = $this->db->get()->result();
        $this->db->flush_cache();

        $responce = new stdClass();
        $responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;
        $responce->rows = $result;

        echo json_encode($responce);
    }

    public function get_data_edit() {
        $id = $this->input->post('id');
        echo json_encode($this->M_clearance->get_data_by_id($id));
    }

    public function save() {
        $id = $this->input->post('id');
        $data = [
            'service_type'   => $this->input->post('service_type'),
            'component_name' => $this->input->post('component_name'),
            'price_idr'      => $this->input->post('price_idr'),
            'unit_basis'     => $this->input->post('unit_basis'),
            'is_at_cost'     => $this->input->post('is_at_cost'),
            'remarks'        => $this->input->post('remarks')
        ];

        $this->M_clearance->save_tariff($data, $id);
        echo json_encode(['status' => 'success']);
    }

    public function delete() {
        $id = $this->input->post('id');
        $this->M_clearance->delete_tariff($id);
        echo json_encode(['status' => 'success']);
    }
}