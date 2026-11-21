<?php
class Trucking_company_model extends CI_Model
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
	
	function getDatareftruckingcompany()
	{
		$this->dbx->select ('*');
		$this->dbx->where('region_id',$this->session->userdata('region_id'));
		$q = $this->dbx->get('reftruckingcompany');
		return $q;
	}
	
	function insert_trucking_company($wh_code, $wh_name, $wh_cp, $wh_address, $wh_remark)
	{
		$this->dbx->insert('reftruckingcompany', array('trucking_company_code'=>strtoupper($wh_code), 'trucking_company_name'=>$wh_name, 'trucking_company_cp'=>$wh_cp, 'trucking_company_address'=>$wh_address, 'trucking_company_remark'=>$wh_remark,'region_id'=>$this->session->userdata('region_id')));
	}
	
	function update_trucking_company($wh_code, $wh_name, $wh_cp, $wh_address, $wh_remark, $id)
	{
		$this->dbx->where('trucking_company_id',$id);
		$this->dbx->update('reftruckingcompany', array('trucking_company_code'=>strtoupper($wh_code), 'trucking_company_name'=>$wh_name, 'trucking_company_cp'=>$wh_cp, 'trucking_company_address'=>$wh_address, 'trucking_company_remark'=>$wh_remark,'region_id'=>$this->session->userdata('region_id')));
	}
	
	function delete_trucking_company($id)
	{
		$this->dbx->where('trucking_company_id',$id);
		$this->dbx->where('region_id',$this->session->userdata('region_id'));
		$this->dbx->delete('reftruckingcompany');
	}
	
	function getListTruckingCompany()
	{
		$this->dbx->select (' trucking_company_code,trucking_company_name',FALSE);
		$this->dbx->where('region_id',$this->session->userdata('region_id'));
		return $this->dbx->get('reftruckingcompany');
	}
	
}	
