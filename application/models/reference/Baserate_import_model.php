<?php
class Baserate_import_model extends CI_Model
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
	
	function refairlinecompany()
	{
		$q = $this->dbx->query("select * from refairlinecompany");
		return $q;
	}
	
	function insert_baserate_import($wh_code)
	{
		$this->dbx->insert('refairlinecompany', array('baserate_import_code'=>strtoupper($wh_code),));
	}
	
	function update_baserate_import($wh_code,$id)
	{
		$this->dbx->where('baserate_import_id',$id);
		$this->dbx->update('refairlinecompany', array('baserate_import_code'=>strtoupper($wh_code),));
	}
	
	function delete_baserate_import($id)
	{
		$this->dbx->where('baserate_import_id',$id);
		$this->dbx->delete('refairlinecompany');
	}
	
}	
