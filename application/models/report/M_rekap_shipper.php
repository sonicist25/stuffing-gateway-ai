<?php
class M_rekap_shipper extends CI_Model
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
		$this->dbx->where('mstadmin.user_id !=',0);
		$this->dbx->order_by('mstadmin.fullname','asc');
		$query = $this->dbx->get('refsales');
		return $query;
	}
	
	function get_shipper($data) {
		$this->dbx->select(array(
			'shipper',
			'shipper_print',
		));		
		$this->dbx->where('sales',$data['sales']);
		$this->dbx->group_by('shipper_print');
		$query = $this->dbx->get('trnstuffingplandetail');
		if($query->num_rows() > 0 ) {
			return $query->result();
		} else {
			return false;
		}
	}
	
	function get_shipper_by_id($data) {		
		$q = $this->dbx->get_where('mstshipper',array('id_shipper'=>$data));
		if($q->num_rows()>0) 
		{
			return $q->row()->shipper_name;
		}
		else 
		{
			return "";
		}
	}
	
	function get_shipper_data($data) {
		$this->dbx->select(array(
			'shipper_name ',
			'shipper_address',
		));		
		return $this->dbx->get_where('mstshipper',array('id_shipper'=>$data['shipper']))->row();
	}
	
	function get_id_shipper_by_shipper_id($code)
	{
		$q = $this->dbx->get_where("mstshipper",array("shipper_id"=>$code,"region_id"=>$this->session->userdata('region_id')));
		if($q->num_rows()>0) 
		{
			return $q->row()->id_shipper;
		}
		else 
		{
			return "";
		}
	}
	
	function get_proses($data){
		$region_id=$this->session->userdata('region_id');
		$shipper_id = $this->get_id_shipper_by_shipper_id($data['shipper']);
		$shipper = $data['shipper'];
		$sales = $data['sales'];
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
			'trnstuffingplan.closing_date_time as tgl_job',
			'trnstuffingplan.vessel as vessel',
			'trnstuffingplan.voyage as voyage',
			'trnstuffingplan.destination as sp_destination',
		));		
		$this->dbx->join('trnstuffingplan','trnstuffingplan.kode_ref=trnstuffingplandetail.code','left');
		$this->dbx->where('trnstuffingplandetail.shipper',$shipper);
		$this->dbx->where('trnstuffingplandetail.sales',$sales);
		$this->dbx->where("`trnstuffingplan.tanggal_job`>='$tgl_awal'");
		$this->dbx->where("`trnstuffingplan.tanggal_job`<='$tgl_akhir'");
		$this->dbx->order_by('tgl_job','asc');
		$query = $this->dbx->get('trnstuffingplandetail');
		if($query->num_rows() > 0 ) {
			return array('status'=>$query->num_rows(),'rows'=>$query->result_array());
		} else {
			return array('status'=>$query->num_rows(),'rows'=>$query->result_array());	
		}

	}
	
	function get_query($data){
		$region_id=$this->session->userdata('region_id');
		$shipper_id = $this->get_id_shipper_by_shipper_id($data['shipper']);
		$sales = $data['sales'];
		$shipper = $data['shipper'];	
		$tgl_awal = $this->fungsi->jin_date_sql($data['tgl_awal']);
		$tgl_akhir = $this->fungsi->jin_date_sql($data['tgl_akhir']);
		$this->dbx->select(array(
			'trnstuffingplandetail.kode_ref as job_no',
			'trnstuffingplandetail.shipper_print as shipper',
			'trnstuffingplandetail.shipper as shipper_imp',
			'trnstuffingplandetail.destination as spd_destination',
			'trnstuffingplandetail.bl_meas as meas',
			'trnstuffingplandetail.hb_l as hb_l',
			'trnstuffingplandetail.desc_goods as desc_goods',
			'trnstuffingplandetail.sales as sales',
			'trnstuffingplan.closing_date_time as tgl_job',
			'trnstuffingplan.vessel as vessel',
			'trnstuffingplan.voyage as voyage',
			'trnstuffingplan.destination as sp_destination',
		));		
		$this->dbx->join('trnstuffingplan','trnstuffingplan.sp_id=trnstuffingplandetail.sp_id','left');
		$this->dbx->where('trnstuffingplandetail.shipper',$shipper);
		$this->dbx->where('trnstuffingplandetail.sales',$sales);
		$this->dbx->where('trnstuffingplan.tanggal_job >=',$tgl_awal);
		$this->dbx->where('trnstuffingplan.tanggal_job <=',$tgl_akhir);
		$this->dbx->where('trnstuffingplan.region_id',$region_id);
		$this->dbx->order_by('tgl_job','asc');
		$query = $this->dbx->get('trnstuffingplandetail');
		return $query;
	}

}


?>