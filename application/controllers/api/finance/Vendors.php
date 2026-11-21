<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Vendors extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        // Load model khusus API Vendor. Alias 'mvendor' agar lebih singkat
        $this->load->model('api/finance/M_vendors', 'mvendor');
    }

    public function get()
    {
        // Menangkap parameter pencarian dari Select2 (biasanya dikirim sebagai 'q')
        $searchTerm = $this->input->get('q');
        $region_id = $this->require_region();
        // Memanggil fungsi dari model untuk mengambil data
        $data = $this->mvendor->get_select2_data($region_id,$searchTerm);

        // Membungkus data ke dalam array 'results' sesuai format standar Select2
        $response = array(
            "results" => $data
        );

        // Memberikan header JSON dan mengembalikan response
        $this->output
            ->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($response));
    }
}