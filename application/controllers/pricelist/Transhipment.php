<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Transhipment extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('pricelist/M_transhipment');
    }

    public function index() {
        $data['title']       = 'Master Transhipment Rates';
        $data['main_view']   = 'pricelist/v_transhipment'; 
        $data['script']      = 'transhipment_crud'; 
        $this->load->view('afg_template', $data); 
    }

    public function get_grid_data() {
        $page    = $this->input->post('page') ?: 1;
        $limit   = $this->input->post('rows') ?: 50;
        $sidx    = $this->input->post('sidx') ?: 'destination';
        $sord    = $this->input->post('sord') ?: 'ASC';
        $filters = $this->input->post('filters');

        echo json_encode($this->M_transhipment->get_grid_data($page, $limit, $sidx, $sord, $filters));
    }

    public function get_data_edit() {
        $id = $this->input->post('id');
        echo json_encode($this->db->get_where('ts_rates', ['id' => $id])->row_array());
    }

    public function save() {
        $id = $this->input->post('id');
        $data = array(
            'ts_type'          => $this->input->post('ts_type'),
            'destination'      => strtoupper($this->input->post('destination')),
            'country'          => strtoupper($this->input->post('country')),
            'via_port'         => strtoupper($this->input->post('via_port')),
            'freight_wm'       => floatval($this->input->post('freight_wm')),
            'min_cbm'          => $this->input->post('min_cbm'),
            'transit_time_1st' => $this->input->post('transit_time_1st') !== '' ? (int)$this->input->post('transit_time_1st') : NULL,
            'transit_time_2nd' => $this->input->post('transit_time_2nd') !== '' ? (int)$this->input->post('transit_time_2nd') : NULL,
            'frequency'        => $this->input->post('frequency'),
            'fumigation'       => $this->input->post('fumigation'),
            'surcharge_gri'    => $this->input->post('surcharge_gri') !== '' ? floatval($this->input->post('surcharge_gri')) : NULL,
            'surcharge_ddc'    => $this->input->post('surcharge_ddc') !== '' ? floatval($this->input->post('surcharge_ddc')) : NULL,
            'surcharge_ams'    => $this->input->post('surcharge_ams'),
            'dg_class3'        => $this->input->post('dg_class3'),
            'dg_class2'        => $this->input->post('dg_class2'),
            'remarks'          => $this->input->post('remarks'),
            'updated_by'       => $this->session->userdata('user_id')
        );

        if ($this->M_transhipment->save_rate($id, $data)) {
            echo json_encode(['status' => 'success', 'msg' => 'Data Disimpan']);
        } else {
            echo json_encode(['status' => 'error', 'msg' => 'Gagal Simpan']);
        }
    }

    public function delete() {
        $id = $this->input->post('id');
        if ($this->M_transhipment->delete_rate($id)) echo json_encode(['status' => 'success']);
    }
	
	public function get_ports() {
        $q = $this->input->get('q');
        
        // Ganti 'tableName' dengan nama tabel master port Anda jika berbeda
        $this->db->select('name as id, name as text, country_id');
        $this->db->from('tableName'); 
        
        if (!empty($q)) {
            $this->db->like('name', $q);
        }
        
        $this->db->limit(50);
        $query = $this->db->get()->result_array();
        
        echo json_encode($query);
    }
}