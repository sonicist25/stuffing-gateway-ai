<?php
class M_rekap_consignee extends CI_Model
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
	
	function get_sales_data() {
		$region_id=$this->session->userdata('region_id');
		$this->dbx->select(array(
			'mstadmin.user_id as user_id ',
			'mstadmin.fullname as user_name ',
		));		
		$this->dbx->join('mstadmin','mstadmin.user_id=refsales.user_id','left');
		$this->dbx->where('mstadmin.region_id',$region_id);
		$query = $this->dbx->get('refsales');
		return $query;
	}
	
	function get_consignee() {
		$region_id=$this->session->userdata('region_id');
		$this->dbx->select(array(
			'consignee_import_code',
			'consignee_import_name ',
			'consignee_import_address',
		));		
		$this->dbx->where('refconsigneeimport.region_id',$region_id);
		$this->dbx->order_by('refconsigneeimport.consignee_import_name','asc');
		$query = $this->dbx->get('refconsigneeimport');
		if($query->num_rows() > 0 ) {
			return $query->result();
		} else {
			return false;
		}
	}
	
	function get_consignee_by_id($data) {		
		$q = $this->dbx->get_where('refconsigneeimport',array('consignee_import_code'=>$data));
		if($q->num_rows()>0) 
		{
			return $q->row()->consignee_import_name;
		}
		else 
		{
			return "";
		}
	}
	
	function get_consignee_data($data) {
		$this->dbx->select(array(
			'consignee_import_name ',
			'consignee_import_address',
		));		
		return $this->dbx->get_where('refconsigneeimport',array('consignee_import_code'=>$data['shipper']))->row();
	}
	
	function get_id_consignee_by_consignee_id($code)
	{
		$q = $this->dbx->get_where("refconsigneeimport",array("consignee_import_code"=>$code));
		if($q->num_rows()>0) 
		{
			return $q->row()->consignee_import_code;
		}
		else 
		{
			return "";
		}
	}
	
	function get_proses($data){
		/*
		sales
		shipper
			selain job 300 shipper = shppr
		
		 		shipper = fowarder; // fwd -> consignee_import
		*/
		
		$region_id=$this->session->userdata('region_id');
		$user_id=$this->session->userdata('user_id');	
		$this->dbx->where('user_id',$user_id);
		$this->dbx->delete('last_query');	
		
		$consignee= ($data['consignee']);	
		$tgl_awal = $this->fungsi->jin_date_sql($data['tgl_awal']);
		$tgl_akhir = $this->fungsi->jin_date_sql($data['tgl_akhir']);
		$this->dbx->select(array(
			'trnstuffingplandetail.kode_ref as job_no',
			'trnstuffingplandetail.shipper_print as shipper',
			'trnstuffingplandetail.shipper as shipper_imp',
			'trnstuffingplandetail.destination as spd_destination',
			'trnstuffingplandetail.bl_meas as meas',
			'trnstuffingplandetail.desc_goods as desc_goods',
			'trnstuffingplandetail.sales as sales',
			'trnstuffingplan.stuffing_date as tgl_job',
			'trnstuffingplan.vessel as vessel',
			'trnstuffingplan.voyage as voyage',
			'trnstuffingplan.destination as sp_destination',
		));		
		$this->dbx->join('trnstuffingplan','trnstuffingplan.kode_ref=trnstuffingplandetail.code','left');
		$this->dbx->where('trnstuffingplandetail.shipper_print',$consignee);
		$this->dbx->where('trnstuffingplan.etd_jkt >=',$tgl_awal);
		$this->dbx->where('trnstuffingplan.etd_jkt <=',$tgl_akhir);
		$this->dbx->where('trnstuffingplan.region_id',$region_id);
		$this->dbx->order_by('tgl_job','asc');
		$query = $this->dbx->get('trnstuffingplandetail');
		$last_query = $this->dbx->last_query();
		
	////print_r($query);

		if($query->num_rows() > 0 ) {
			$this->dbx->insert('last_query',array('user_id'=>$user_id,'query'=>$last_query));
			return array('status'=>$query->num_rows(),'rows'=>$query->result_array());
		} else {
			return array('status'=>$query->num_rows(),'rows'=>$query->result_array());	
		}
	}
	
	function get_query(){
		$data = $this->dbx->get_where('last_query',array('user_id'=>$this->session->userdata('user_id')));	
		if($data->num_rows() > 0 ) {
			$sql = $data->row()->query;
			$query = $this->dbx->query($sql);
			return $query;
		} else {
			return false;	
		}
	}

}


