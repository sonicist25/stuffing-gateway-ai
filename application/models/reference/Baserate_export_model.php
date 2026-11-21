<?php
class Baserate_export_model extends CI_Model
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
	
	function getDatarefbaserateexport()
	{
		$q = $this->dbx->query("select * from refbaserateexport");
		return $q;
	}
	
	function insert_baserate_export($wh_code)
	{
		$this->dbx->insert('refbaserateexport', array('baserate_export_code'=>strtoupper($wh_code),));
	}
	
	function update_baserate_export($wh_code, $id)
	{
		$this->dbx->where('baserate_export_id',$id);
		$this->dbx->update('refbaserateexport', array('baserate_export_code'=>strtoupper($wh_code)));
	}
	
	function delete_baserate_export($id)
	{
		$this->dbx->where('baserate_export_id',$id);
		$this->dbx->delete('refbaserateexport');
	}
	
	function getListBaserate()
	{
		$this->dbx->select ('baserate_export_code');
		return $this->dbx->get('refbaserateexport');
	}
	
}	
