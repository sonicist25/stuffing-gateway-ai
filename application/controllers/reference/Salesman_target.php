<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Salesman_target extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('reference/Sales_target_model');
        $this->load->helper('nama_bulan');
        $this->fungsi->auth(20,'Account'); // sesuaikan hak akses
    }

    public function index() {
        // Ambil data salesman target dari model
        $year      = $this->session->userdata('db');
        $region_id = $this->session->userdata('region_id');
        
        $salesmen_targets = $this->Sales_target_model->get_salesmen_targets($year, $region_id);


        $data = [
            'menu_navigation' => 'navigation',
            'main_view'       => 'reference/salesman_target', // file view
            'h1_title'        => 'Target Salesman',
            'title'           => 'Distribusi Target Salesman',
            'salesmen_targets'=> $salesmen_targets
        ];

        $this->load->view('afg_template', $data);
    }
    
}