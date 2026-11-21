<?php
class  Export_cost_price_trucking_model extends CI_Model
{
	public function __construct(){
    	parent::__construct();
    	$this->load->database('master',false);
		$this->load->database(''.$this->session->userdata('db').'',true);
   }
	
	function get_trucking(){
		$q = $this->dbx->query("select * from reftruckingcompany");
		return $q;
	}
	
	function lastData($kode_agent)
	{
		$sql = "SELECT * FROM `a_cost_agent` where kode_agent ='$kode_agent'  order by id asc limit 1"; 		
		$query = $this->dbx->query($sql);			
		return $query->row();
		
	}
	
	function pilihData($kode_agent,$id)
	{
		$sql = "SELECT * FROM `a_cost_agent` where kode_agent ='$kode_agent'  and id = ?"; 		
		$query = $this->dbx->query($sql,array($id));			
		return $query->row();
	}
	
	function nextData($kode_agent,$id)
	{
		$sql = "SELECT * FROM `a_cost_agent` where kode_agent ='$kode_agent'  and id > ?"; 		
		$query = $this->dbx->query($sql,array($id));	
		if($query->num_rows() > 0 ) {
			return $query->row();
		} else {
			$sql = "SELECT * FROM `a_cost_agent` where kode_agent ='$kode_agent'  and id = ?"; 		
			$query = $this->dbx->query($sql,array($id));
			return $query->row();
		}
	}
	
	function prevData($kode_agent,$id)
	{
		$sql = "SELECT * FROM `a_cost_agent` where kode_agent ='$kode_agent'  and id < ?"; 		
		$query = $this->dbx->query($sql,array($id));	
		if($query->num_rows() > 0 ) {
			return $query->row();
		} else {
			$sql = "SELECT * FROM `a_cost_agent` where kode_agent ='$kode_agent'  and id = ?"; 		
			$query = $this->dbx->query($sql,array($id));
			return $query->row();
		}
	}
}


