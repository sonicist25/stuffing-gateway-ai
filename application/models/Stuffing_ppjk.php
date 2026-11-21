<?php
class Stuffing_ppjk extends CI_Model
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
	
	function getDataImporByKodeRef($data)
	{       

		$region = 'Jakarta';
		$this->dbx->select(
			array(
				'trnstuffingplan.type_of_trans',		
				'trnstuffingplandetail.shipper_print',
				'trnstuffingplandetail.consignee',
				'trnstuffingplan.mother_bl',
				'trnstuffingplandetail.hb_l',
				'trnstuffingplan.etd_jkt',				
			)
		);
		$this->dbx->from('trnstuffingplandetail');
		$this->dbx->join('trnstuffingplan','trnstuffingplan.sp_id=trnstuffingplandetail.sp_id','letf');
		$this->dbx->where('trnstuffingplandetail.kode_ref',$data['kode_ref']);
		$this->dbx->where('trnstuffingplan.region_id',$region);
		$query = $this->dbx->get();
		
		if($query->num_rows() > 0){
			return $query;
		} else {
			return false;
		}
	}
	
	function getDataPpjk()
	{
		$q = $this->dbx->query("select * from ppjk");
		return $q;
	}
	
	function insert_ppjk($data)
	{
		$this->dbx->insert('ppjk', $data);
	}
	
	function update_ppjk($data, $id)
	{
		$this->dbx->where('ppjk_id',$id);
		$this->dbx->update('ppjk', $data);
	}
	
	function delete_ppjk($id)
	{
		$this->dbx->where('ppjk_id',$id);
		$this->dbx->delete('ppjk');
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
