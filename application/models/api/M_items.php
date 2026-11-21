<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_items extends CI_Model {

    public function __construct() {
        parent::__construct();
    }

    // Ambil semua data untuk select2
    public function get_all($search = null) {
        $this->db->select('typeservice_id, typeservice_code, typeservice_name, typeservice_ppn');
        $this->db->from('reftypeservice');

        // Jika ada pencarian dari select2
        if (!empty($search)) {
            $this->db->like('typeservice_name', $search);
            $this->db->or_like('typeservice_code', $search);
        }

        $query = $this->db->get();
        return $query->result();
    }
}

