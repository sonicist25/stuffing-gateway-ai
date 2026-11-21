<?php
use chriskacerguis\RestServer\RestController;

class Sales extends RestController {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    public function get_sales_get() {
        // ambil parameter dari GET
        $region_id = $this->get('region_id');
        $user_id   = $this->get('user_id');
        $q         = $this->get('q'); // keyword pencarian

        // join ke mstadmin
        $this->db->join('mstadmin', 'mstadmin.user_id = refsales.user_id', 'left');

        // siapkan filter
        if (!empty($region_id)) {
            $this->db->where('mstadmin.region_id', $region_id);
        }
        if (!empty($user_id)) {
            $this->db->where('refsales.user_id', $user_id);
        }
        if (!empty($q)) {
            // contoh pencarian di beberapa kolom
            $this->db->group_start();
            $this->db->like('refsales.remark', $q);
            $this->db->or_like('mstadmin.username', $q);
            $this->db->group_end();
        }

        // eksekusi query
        $qres = $this->db->get('refsales');
        $result = $qres->result_array();

        // response JSON
        if ($result) {
            $this->response([
                'status' => true,
                'data'   => $result
            ], RestController::HTTP_OK);
        } else {
            $this->response([
                'status'  => false,
                'message' => 'Data sales tidak ditemukan'
            ], RestController::HTTP_NOT_FOUND);
        }
    }
}