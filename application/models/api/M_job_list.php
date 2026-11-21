<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_job_list extends CI_Model {

    public function get_filtered_jobs() 
    {
        $this->db->select('
            kode_ref as nomor_job, 
            etd_jkt as tanggal_etd, 
            vessel, 
            voyage
        ');
        $this->db->from('trnstuffingplan');
        
        // Filter contain -100 atau -101
        $this->db->group_start();
        $this->db->like('kode_ref', '-100');
        $this->db->or_like('kode_ref', '-101');
        $this->db->or_like('kode_ref', '-300');
        $this->db->group_end();
        
        // Ambil data mentah
        $results = $this->db->get()->result_array();

        // Looping untuk standarisasi format tanggal_etd menjadi YYYY-MM-DD
        foreach ($results as &$row) {
            // Pastikan data tidak kosong dan memiliki panjang persis 8 karakter (YYYYMMDD)
            if (!empty($row['tanggal_etd']) && strlen($row['tanggal_etd']) === 8) {
                // Format dari "20260104" menjadi "2026-01-04"
                $row['tanggal_etd'] = date('Y-m-d', strtotime($row['tanggal_etd']));
            } else if (empty($row['tanggal_etd'])) {
                // Jika datanya kosong di database, ubah menjadi null agar lebih standar di API
                $row['tanggal_etd'] = null; 
            }
        }

        return $results;
    }
}