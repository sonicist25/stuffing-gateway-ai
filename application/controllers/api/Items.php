<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Items extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('api/M_items');
    }

    // Endpoint untuk Select2
    public function get_select2() {
        $search = $this->input->get('q'); // parameter pencarian dari select2
        $result = $this->M_items->get_all($search);

        $data = [];
        foreach ($result as $row) {
            $data[] = [
                "id"   => $row->typeservice_id,
                "text" => $row->typeservice_code . " - " . $row->typeservice_name,
                "ppn"  => $row->typeservice_ppn
            ];
        }

        echo json_encode($data);
    }
}