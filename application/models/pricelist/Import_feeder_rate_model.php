<?php
class  Import_feeder_rate_model extends CI_Model
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
	
	function get_feeder_rate(){
		$q = $this->dbx->query("select * from refshippingcompany");
		return $q;
	}
	
	function lastFeeder($code)
	{
		$sql = "SELECT * FROM `a_feeder_rate` where code ='$code'  order by id asc limit 1"; 		
		$query = $this->dbx->query($sql);			
		return $query->row();
		
	}
	
	function pilihFeeder($code,$id)
	{
		$sql = "SELECT * FROM `a_feeder_rate` where code ='$code'  and id = ?"; 		
		$query = $this->dbx->query($sql,array($id));			
		return $query->row();
	}
	
	function nextFeeder($code,$id)
	{
		$sql = "SELECT * FROM `a_feeder_rate` where code ='$code'  and id > ?"; 		
		$query = $this->dbx->query($sql,array($id));	
		if($query->num_rows() > 0 ) {
			return $query->row();
		} else {
			$sql = "SELECT * FROM `a_feeder_rate` where code ='$code'  and id = ?"; 		
			$query = $this->dbx->query($sql,array($id));
			return $query->row();
		}
	}
	
	function prevFeeder($code,$id)
	{
		$sql = "SELECT * FROM `a_feeder_rate` where code ='$code'  and id < ?"; 		
		$query = $this->dbx->query($sql,array($id));	
		if($query->num_rows() > 0 ) {
			return $query->row();
		} else {
			$sql = "SELECT * FROM `a_feeder_rate` where code ='$code'  and id = ?"; 		
			$query = $this->dbx->query($sql,array($id));
			return $query->row();
		}
	}
}


