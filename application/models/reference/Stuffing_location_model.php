<?php
class Stuffing_location_model extends CI_Model
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
	
	function get_trucking_name($id) 
	{
		$q = $this->dbx->get_where('reftruckingcompany',array('trucking_company_id'=>$id));
		if($q->num_rows()>0) 
		{
			return $q->row()->trucking_company_name;
		}
		else 
		{
			return $id;
		}
	}
	
	function get_location_name($id) 
	{
		$q = $this->dbx->get_where('refstuffinglocation',array('stuffing_location_id'=>$id));
		if($q->num_rows()>0) 
		{
			return $q->row()->stuffing_location_company;
		}
		else 
		{
			return $id;
		}
	}
	
	function shipper_selected($id)
	{
		$q = $this->dbx->get_where('trnstuffingplandetail',array('sp_detail_id'=>$id));
		if($q->num_rows()>0)
		{
			return $q->row()->shipper;		
		}
		else 
		{
			return '';
		}
	}
	
	function shipper_selected_id($id)
	{
		$q = $this->dbx->get_where('trnstuffingplandetail',array('sp_detail_id'=>$id));
		if($q->num_rows()>0)
		{
			$shp_id = $this->dbx->get_where('mstshipper',array('shipper_name'=>$q->row()->shipper_print));
			if($shp_id->num_rows()>0) 
			{
				return $shp_id->row()->id_shipper;
			}
		}
		else 
		{
			return '';
		}
	}
	
	function location_selected($id)
	{
		$q = $this->dbx->get_where('tbl_detail',array('detail_id'=>$id));
		if($q->num_rows()>0)
		{
			return $q->row()->location;		
		}
		else 
		{
			return '';
		}
	}
	
	function trucking_co_selected($id)
	{
		$q = $this->dbx->get_where('tbl_detail',array('detail_id'=>$id));
		if($q->num_rows()>0)
		{
			return $q->row()->trucking_co;		
		}
		else 
		{
			return '';
		}
	}
	
	function getDataStuffing_location()
	{
		$q = $this->dbx->query("select * from foobar");
		return $q;
	}
	
	function insert_Stuffing_location($wh_code,$wh_company, $wh_address)
	{
		$this->dbx->insert('foobar', array('warehouse_code'=>strtoupper($wh_code),'warehouse_name'=>strtoupper($wh_company),'warehouse_address'=>strtoupper($wh_address),'region_id'=>$this->session->userdata('region_id')));
		$status = $this->dbx->affected_rows();
		if ($status == 1){
		$this->dbx->insert('refwarehouse', array('warehouse_code'=>strtoupper($wh_code),'warehouse_name'=>strtoupper($wh_company),'warehouse_address'=>strtoupper($wh_address),'region_id'=>$this->session->userdata('region_id')));
		}		
		return $status;
	}
	
	function update_Stuffing_location($wh_code,$wh_company, $wh_address, $id)
	{
		$this->dbx->where('id',$id);
		$this->dbx->update('foobar', array('warehouse_code'=>strtoupper($wh_code),'warehouse_name'=>strtoupper($wh_company),'warehouse_address'=>strtoupper($wh_address),'region_id'=>$this->session->userdata('region_id')));
		$status = $this->dbx->affected_rows();
		if ($status == 1){
		$this->dbx->update('refwarehouse', array('warehouse_code'=>strtoupper($wh_code),'warehouse_name'=>strtoupper($wh_company),'warehouse_address'=>strtoupper($wh_address),'region_id'=>$this->session->userdata('region_id')));
		}
		return $status;
	}
	
	function delete_Stuffing_location($id)
	{
		$this->dbx->where('id',$id);
		$this->dbx->delete('foobar');
		$status = $this->dbx->affected_rows();
		return $status;
	}
	
	function getStuffing_location()
	{
		$this->dbx->select ('*');
		return $this->dbx->get('refstuffinglocation');
	}
	
	function getStuffing_trucking()
	{
		$this->dbx->select ('*');
		return $this->dbx->get('reftruckingcompany');
	}
	
}	
