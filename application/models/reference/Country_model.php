<?php
class Country_model extends CI_Model
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
	
	function getdatacountry()
	{
		$q = $this->dbx->query("select * from mstcountries");
		return $q;
	}
	
	function insert_country($data)
	{
		$this->dbx->insert('mstcountries',$data);
		if($this->dbx->affected_rows() > 0) {
			return '1';
		} else {
			return '0';	
		}
	}
	
	function update_country($data, $id)
	{
		$this->dbx->where('idnya',$id);
		$this->dbx->update('mstcountries',$data);
		if($this->dbx->affected_rows() > 0) {
			return '1';
		} else {
			return '0';	
		}
	}
	
	function delete_country($id)
	{
		$this->dbx->where('idnya',$id);
		$this->dbx->delete('mstcountries');
		if($this->dbx->affected_rows() > 0) {
			return '1';
		} else {
			return '0';	
		}
	}
	
}	