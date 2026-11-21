<?php
class M_kurs_pajak extends CI_Model
{
  public $dbx;

    public function __construct() {
        parent::__construct();

        $tahun = $this->session->userdata('db');

        if ($tahun == '' || !is_numeric($tahun)) {
            $this->dbx = $this->load->database('master', TRUE);
        } else {
            $this->dbx = $this->load->database(switch_db($tahun), TRUE);
        }
    }
	
	function getDataKursPajak(){
		$q = $this->dbx->query("select * from a_kurs_pajak order by id desc");
		return $q;
	}
	
	function add_data($kolom,$isi){
		if($kolom=='mulai_tgl' || $kolom=='smp_dgn' || $kolom=='tgl_kmk') {
			$this->dbx->insert('a_kurs_pajak',array($kolom=>$this->fungsi->jin_date_sql($isi)));
		} else {
			$this->dbx->insert('a_kurs_pajak',array($kolom=>$isi));
		}
		if($this->dbx->affected_rows() > 0) {
			return '1';
		} else {
			return '0';	
		}
	}
	
	function update_data($kolom,$isi,$id){
		if($kolom=='mulai_tgl' || $kolom=='smp_dgn' || $kolom=='tgl_kmk') {
			$this->dbx->where('id',$id);
			$this->dbx->update('a_kurs_pajak',array($kolom=>$this->fungsi->jin_date_sql($isi)));
		} else {
			$this->dbx->where('id',$id);
			$this->dbx->update('a_kurs_pajak',array($kolom=>$isi));
		}		
		if($this->dbx->affected_rows() > 0) {
			return '1';
		} else {
			return '0';	
		}
	}
	
	function delete_data($id){
		$this->dbx->where('id',$id);
		$this->dbx->delete('a_kurs_pajak');
		if($this->dbx->affected_rows() > 0) {
			return '1';
		} else {
			return '0';	
		}
	}
}	
