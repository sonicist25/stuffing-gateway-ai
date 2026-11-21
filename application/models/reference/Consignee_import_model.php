<?php
class Consignee_import_model extends CI_Model
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
	
	function getDatarefconsigneeimport()
	{	$region = $this->session->userdata("region_id");
		$this->dbx->order_by('consignee_import_id','desc');
		$q = $this->dbx->get_where('refconsigneeimport',array('region_id'=>$this->session->userdata('region_id')));
		return $q;
	}
	
	function insert_consignee_import($wh_code, $wh_name, $wh_address){
		$this->dbx->insert('refconsigneeimport', 
			array(
				'consignee_import_code'=>strtoupper($wh_code), 
				'consignee_import_name'=>strtoupper($wh_name), 
				'consignee_import_address'=>strtoupper($wh_address),
				'region_id'=>$this->session->userdata("region_id"),
		));
	}
	
	function update_consignee_import($wh_code, $wh_name, $wh_address, $id)
	{
		$this->dbx->where('consignee_import_id',$id);
		$this->dbx->update('refconsigneeimport', 
			array(
			'consignee_import_code'=>strtoupper($wh_code), 
			'consignee_import_name'=>strtoupper($wh_name), 
			'consignee_import_address'=>strtoupper($wh_address),
			'region_id'=>$this->session->userdata("region_id"),
		));
	}
	
	function delete_consignee_import($id)
	{
		$this->dbx->where('consignee_import_id',$id);
		$this->dbx->delete('refconsigneeimport');
	}
	
}	
