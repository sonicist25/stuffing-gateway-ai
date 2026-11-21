<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_laporan_shipment extends CI_Model
{
  public $dbx;
  public $dbr;

    public function __construct() {
        parent::__construct();

        $tahun = $this->session->userdata('db');

        if ($tahun == '' || !is_numeric($tahun)) {
            $this->dbx = $this->load->database('master', TRUE);
            $this->dbr = $this->load->database('replica', TRUE);
        } else {
            $this->dbx = $this->load->database(switch_db($tahun), TRUE);
            $this->dbr = $this->load->database(switch_db_replica($tahun), TRUE);
        }
    }
    
    /**
     * FUNGSI UTAMA LAPORAN
     * Menggantikan get_proses(), get_query(), dan semua perulangan N+1.
     * Menggunakan Subquery agar database hanya diakses 1 KALI saja.
     */
    public function get_report_data($bulan = null) {
        $region_id = $this->session->userdata('region_id');
        
        $this->dbr->select(array(
            'a.sp_id as id',
            'a.kode_ref as kode_ref',
            'a.stuffing_date as tgl_job',
            'a.etd_jkt as etd',
            'a.destination as destination',
            'a.port_loading as origin',
            'a.shipment_type_multi as type',
            'a.type_cont_size as type_cont_size',
            'a.kurs as kurs',
            
            // Lakukan SUM langsung dari tabel yang di-JOIN
            "IFNULL(SUM(sj.income_rp), 0) as income_rp",
            "IFNULL(SUM(sj.income_usd), 0) as income_usd",
            "IFNULL(SUM(sj.inv_agent), 0) as inv_agent",
            "IFNULL(SUM(sj.income), 0) as total_income",
            
            "IFNULL(SUM(sj.cost_rp), 0) as cost_rp",
            "IFNULL(SUM(sj.cost_usd), 0) as cost_usd",
            "IFNULL(SUM(sj.cost), 0) as total_cost",
            
            "IFNULL(SUM(sj.profit), 0) as total_profit"
        ));     
        
        $this->dbr->from('trnstuffingplan a');
        
        // JOIN langsung dengan LIKE (Persen HANYA di belakang agar Index aktif)
        $this->dbr->join('save_jobsheet sj', "sj.kode_ref LIKE CONCAT(a.kode_ref, '%')", 'left');
        
        $this->dbr->where('a.region_id', $region_id);
        
        if (!empty($bulan)) {
            // Tambahkan NULL, FALSE agar CodeIgniter mengeksekusi Query murni apa adanya
            $this->dbr->where("SUBSTR(a.kode_ref, 10, 2) = '$bulan'", NULL, FALSE);
        }
        
        // Karena kita pakai SUM(), kita WAJIB melakukan GROUP BY ke tabel utama (a)
        // Ini memastikan data tidak duplikat dan sesuai standar SQL MariaDB
        $this->dbr->group_by(array(
            'a.sp_id', 
            'a.kode_ref', 
            'a.stuffing_date', 
            'a.etd_jkt', 
            'a.destination', 
            'a.port_loading', 
            'a.shipment_type_multi', 
            'a.type_cont_size', 
            'a.kurs'
        ));
        
        $this->dbr->order_by("a.kode_ref", "asc");
        
        return $this->dbr->get()->result_array();
    }

    /**
     * FUNGSI HELPER (OPSIONAL)
     * Hanya gunakan jika ada Controller LAIN yang masih membutuhkan fungsi spesifik.
     * Jika hanya dipakai di laporan ini, fungsi ini boleh dihapus.
     */
    public function get_detail_finance($code, $mode) {
        $region_id = $this->session->userdata('region_id');
        
        // Memastikan mode yang diminta valid untuk mencegah error
        $valid_columns = ['kurs', 'ttl_income_rp', 'ttl_income_usd', 'total_income', 'ttl_cost_rp', 'ttl_cost_usd', 'total_cost', 'total_profit'];
        if (!in_array($mode, $valid_columns)) {
            return 0;
        }

        $this->dbr->select_sum($mode, 'total_value');
        $this->dbr->where('job_no', $code);
        $this->dbr->where('region_id', $region_id);
        $query = $this->dbr->get('save_jobsheet');

        return ($query->num_rows() > 0) ? $query->row()->total_value : 0;
    }
}