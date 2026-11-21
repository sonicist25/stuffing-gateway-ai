<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Sales_target extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('reference/Sales_target_model');
    }
	
    public function index() {
        $year      = $this->session->userdata('db');
    $region_id = $this->session->userdata('region_id');

    $master = $this->Sales_target_model->get_master($region_id, $year);
    $monthly = [];
    if ($master) {
        $monthly = $this->Sales_target_model->get_monthly($master->target_id);
    }

    $data = [
        'menu_navigation' => 'navigation',
        'main_view'       => 'reference/sales_target',
        'h1_title'        => '',
        'title'           => 'Manajemen Forecast',
        'master'          => $master,
        'monthly'         => $monthly
    ];

    $this->load->view('afg_template', $data);

    }

    public function save_target() {
        $year       = $this->input->post('year');
        $region_id  = $this->input->post('region_id');
        $target_revenue = preg_replace('/[^0-9]/', '', $this->input->post('target_revenue'));
        $target_profit  = preg_replace('/[^0-9]/', '', $this->input->post('target_profit'));

        $monthly_revenue = $this->input->post('monthly_revenue') ?: [];
        $monthly_profit  = $this->input->post('monthly_profit') ?: [];

        // Bersihkan format Rp jadi angka
        $rev_clean  = array_map(function($v){ return preg_replace('/[^0-9]/','',$v); }, $monthly_revenue);
        $prof_clean = array_map(function($v){ return preg_replace('/[^0-9]/','',$v); }, $monthly_profit);

        // Tentukan distribusi_type otomatis
        $distribution_type = 'AUTO';
        if (count(array_unique($rev_clean)) > 1 || count(array_unique($prof_clean)) > 1) {
            $distribution_type = 'MANUAL';
        }

        // Validasi manual (jika MANUAL)
        if ($distribution_type == 'MANUAL') {
            $total_revenue = array_sum($rev_clean);
            $total_profit  = array_sum($prof_clean);

            if ($total_revenue != $target_revenue || $total_profit != $target_profit) {
                $this->session->set_flashdata('error', 'Distribusi bulanan tidak sesuai dengan target tahunan.');
                redirect('reference/sales_target/input');
                return;
            }
        }

        // Simpan master (insert/update)
        $target_id = $this->Sales_target_model->save_master([
            'region_id'        => $region_id,
            'year'             => $year,
            'target_revenue'   => $target_revenue,
            'target_profit'    => $target_profit,
            'distribution_type'=> $distribution_type
        ]);

        // Simpan bulanan (insert/update)
        $this->Sales_target_model->save_monthly($target_id, $rev_clean, $prof_clean);

        $this->session->set_flashdata('success', 'Sales target berhasil disimpan.');
        redirect('reference/sales_target');
    }
}