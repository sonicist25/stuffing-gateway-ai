<?php
class DbAlias {
    public function apply() {
        $CI =& get_instance();
        if (!isset($CI->session)) return;

        $tahun = (int) $CI->session->userdata('db');
        if ($tahun < 2000 || $tahun > 2100) $tahun = (int) date('Y');

        // Buat koneksi dinamis sekali di level app
        $dbx = $CI->load->database(switch_db($tahun), TRUE);
        $CI->dbx = $dbx;
        $CI->db  = $dbx; // alias global juga
        log_message('debug', 'Hook set DB: '.$CI->db->database);
    }
}
