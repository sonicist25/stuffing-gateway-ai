<?php
class shipper_import_model extends CI_Model
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
	
	function getDatarefshipperimport()
	{
		$q = $this->dbx->query("select * from refshipperimport");
		return $q;
	}
	
	function insert_shipper_import($wh_code, $wh_name, $wh_address)
	{
		$this->dbx->insert('refshipperimport', array('shipper_import_code'=>strtoupper($wh_code), 'shipper_import_name'=>ucwords($wh_name), 'shipper_import_address'=>$wh_address));
	}
	
	function update_shipper_import($wh_code, $wh_name, $wh_address, $id)
	{
		$this->dbx->where('shipper_import_id',$id);
		$this->dbx->update('refshipperimport', array('shipper_import_code'=>strtoupper($wh_code), 'shipper_import_name'=>ucwords($wh_name), 'shipper_import_address'=>$wh_address));
	}
	
	function delete_shipper_import($id)
	{
		$this->dbx->where('shipper_import_id',$id);
		$this->dbx->delete('refshipperimport');
	}
	
}	
