<?php
class M_rekap_sales extends CI_Model
{
  public $dbx;
  public $dbr;

    public function __construct() {
        parent::__construct();

        $tahun = $this->session->userdata('db');

        if ($tahun == '' || !is_numeric($tahun)) {
            $this->dbx = $this->load->database('master', TRUE);
            $this->dbr = $this->load->database('replica', TRUE);
        } else {
            $this->dbx = $this->load->database(switch_db($tahun), TRUE);
            $this->dbr = $this->load->database(switch_db_replica($tahun), TRUE);
        }
    }
	
	function get_sales_data() {
		$region_id=$this->session->userdata('region_id');
		$this->dbr->select(array(
			'mstadmin.user_id as user_id ',
			'mstadmin.fullname as user_name ',
		));		
		$this->dbr->join('mstadmin','mstadmin.user_id=refsales.user_id','left');
		$this->dbr->where('mstadmin.region_id',$region_id);
		$this->dbr->where('mstadmin.user_id !=',0);
		$this->dbr->order_by('mstadmin.fullname','asc');
		$query = $this->dbr->get('refsales');
		return $query;
	}
	
	function get_proses($data){
		$region_id=$this->session->userdata('region_id');
		$user_id=$this->session->userdata('user_id');	
		$this->dbx->where('user_id',$user_id);
		$this->dbx->delete('last_query');		
		$tgl_awal = $this->fungsi->jin_date_sql($data['tgl_awal']);
		$tgl_akhir = $this->fungsi->jin_date_sql($data['tgl_akhir']);

		$this->dbr->select(array(
			'trnstuffingplandetail.kode_ref as job_no',
			'trnstuffingplandetail.shipper_print as shipper',
			'trnstuffingplandetail.shipper_import_code as shipper_imp',
			'trnstuffingplandetail.destination as spd_destination',
			'trnstuffingplandetail.bl_meas as meas',
			'trnstuffingplandetail.desc_goods as desc_goods',
			'trnstuffingplandetail.sales as sales',
			'trnstuffingplan.kode_ref as kode_ref',
			'trnstuffingplan.stuffing_date as tgl_job',
			'trnstuffingplan.tanggal_job as tanggal_job',
			'trnstuffingplan.etd_jkt as etd_jkt',
			'trnstuffingplan.eta as eta',
			'trnstuffingplan.vessel as vessel',
			'trnstuffingplan.voyage as voyage',
			'trnstuffingplan.destination as sp_destination',
			'trnstuffingplan.type_of_trans as type',
			'trnstuffingplan.type_cont_qty as type_cont_qty',
			'trnstuffingplan.type_cont_size as type_cont_size',
		));
				
		$this->dbr->join('trnstuffingplan','trnstuffingplan.sp_id=trnstuffingplandetail.sp_id','left');
		$this->dbr->where('trnstuffingplandetail.sales',$data['sales']);
		$this->dbr->where("`trnstuffingplan.tanggal_job`>='$tgl_awal'");
		$this->dbr->where("`trnstuffingplan.tanggal_job`<='$tgl_akhir'");
		$this->dbr->where('trnstuffingplan.region_id',$region_id);
		$this->dbr->where('trnstuffingplan.kode_ref !=','');
		$this->dbr->where('trnstuffingplan.tanggal_job !=','');	
		$this->dbr->order_by("CAST(SUBSTRING(trnstuffingplandetail.kode_ref, 5, LENGTH(trnstuffingplandetail.kode_ref)-5) AS UNSIGNED)", "asc", false);
		$this->dbr->order_by("RIGHT(trnstuffingplandetail.kode_ref, 1)", "asc", false);
		$query = $this->dbr->get('trnstuffingplandetail');
		
	//print_r ($this->dbx->last_query());

	//	return array('query'=>$last_query,'status'=>$query->num_rows(),'result'=>$query->result_array());
	//	return array('rows'=>$query);

		if($query->num_rows() > 0 ) {
			//$this->dbx->insert('last_query',array('user_id'=>$user_id,'query'=>$last_query));
			return array('status'=>$query->num_rows(),'rows'=>$query->result_array());
		} else {
			return array('status'=>$query->num_rows(),'rows'=>$query->result_array());	
		}
	}
	
	function get_query($data){
		$region_id=$this->session->userdata('region_id');
		$user_id=$this->session->userdata('user_id');		
		$tgl_awal = $this->fungsi->jin_date_sql($data['tgl_awal']);
		$tgl_akhir = $this->fungsi->jin_date_sql($data['tgl_akhir']);

		$this->dbr->select(array(
			'trnstuffingplandetail.kode_ref as job_no',
			'trnstuffingplandetail.shipper_print as shipper',
			'trnstuffingplandetail.destination as spd_destination',
			'trnstuffingplandetail.bl_meas as meas',
			'trnstuffingplandetail.weight as weight',
			'trnstuffingplandetail.desc_goods as desc_goods',
			'trnstuffingplandetail.sales as sales',
			'trnstuffingplandetail.incoterms as incoterms',
			'trnstuffingplan.stuffing_date as tgl_job',
			'trnstuffingplan.tanggal_job as tanggal_job',
			'trnstuffingplan.etd_jkt as etd_jkt',
			'trnstuffingplan.eta as eta',
			'trnstuffingplan.vessel as vessel',
			'trnstuffingplan.voyage as voyage',
			'trnstuffingplan.type_cont_qty as type_cont_qty',
			'trnstuffingplan.type_cont_size as type_cont_size',
			'trnstuffingplan.port_loading as port_loading',
			'trnstuffingplan.destination as sp_destination',
			'trnstuffingplan.type_of_trans as type',
		));
				
		$this->dbr->join('trnstuffingplan','trnstuffingplan.sp_id=trnstuffingplandetail.sp_id','left');
		$this->dbr->where('trnstuffingplandetail.sales',$data['sales']);
		$this->dbr->where("`trnstuffingplan.tanggal_job`>='$tgl_awal'");
		$this->dbr->where("`trnstuffingplan.tanggal_job`<='$tgl_akhir'");
		$this->dbr->where('trnstuffingplan.region_id',$region_id);
		$this->dbr->where('trnstuffingplandetail.sales',$data['sales']);
		$this->dbr->order_by("CAST(SUBSTRING(trnstuffingplandetail.kode_ref, 5, LENGTH(trnstuffingplandetail.kode_ref)-5) AS UNSIGNED)", "asc", false);
		$this->dbr->order_by("RIGHT(trnstuffingplandetail.kode_ref, 1)", "asc", false);
		$query = $this->dbr->get('trnstuffingplandetail');
		return $query;
	}

	function get_income_pdf($kode_ref){
      $region_id=$this->session->userdata('region_id');
	 	$this->dbr->select('*');
	 	$this->dbr->from('inf_income_selling_price');
		$this->dbr->where('kode_ref',$kode_ref);
		$this->dbr->where('region_id',$region_id);
		$query = $this->dbr->get();
		if($query->num_rows() > 0 ) {
			return $query;
		} else {
			return null;	
		}		
	}

	function get_profit($code) {
		$q = $this->dbr->get_where('save_jobsheet',array('kode_ref'=>$code,'region_id'=>$this->session->userdata('region_id')));
		if($q->num_rows() > 0 ) {
			return $q->row()->profit;
		} else {
			return "";
		}
	}
	
	function get_income($code) {
		$q = $this->dbr->get_where('save_jobsheet',array('kode_ref'=>$code,'region_id'=>$this->session->userdata('region_id')));
		if($q->num_rows() > 0 ) {
			return $q->row()->income;
		} else {
			return "";
		}
	}
	function get_cost($code) {
		$q = $this->dbr->get_where('save_jobsheet',array('kode_ref'=>$code,'region_id'=>$this->session->userdata('region_id')));
		if($q->num_rows() > 0 ) {
			return $q->row()->cost;
		} else {
			return "";
		}
	}
	function get_meas($code) {
		$q = $this->dbr->get_where('trnstuffingplandetail',array('kode_ref'=>$code,'region_id'=>$this->session->userdata('region_id')));
		if($q->num_rows() > 0 ) {
			return $q->row()->bl_meas;
		} else {
			return "";
		}
	}
	function get_bl($code) {
		$q = $this->dbr->get_where('trnstuffingplandetail',array('kode_ref'=>$code,'region_id'=>$this->session->userdata('region_id')));
		if($q->num_rows() > 0 ) {
			return $q->row()->hb_l;
		} else {
			return "";
		}
	}
	function save_profit($data)
	{
		$q = $this->dbx->get_where("save_jobsheet",array("kode_ref"=>$data["kode_ref"]));
		if($q->num_rows()>0)
		{
			$this->dbx->update("save_jobsheet",$data,array("kode_ref"=>$data["kode_ref"]));
		}
		else 
		{
			$this->dbx->insert("save_jobsheet",$data);
		}
		//print_r($this->dbx->last_query());
		//die();
	}

function save_profit_rekap ($data)
	{
		$q = $this->dbx->get_where("save_jobsheet",array("kode_ref"=>$data["kode_ref"]));
		if($q->num_rows()>0)
		{
			$this->dbx->update("save_jobsheet",$data,array("kode_ref"=>$data["kode_ref"]));
		}
		else 
		{
			$this->dbx->insert("save_jobsheet",$data);
		}
				
	}
	
	function save_acct ($data)
	{
		$q = $this->dbx->get_where("save_acct",array("job_no"=>$data['job_no']));
		if($q->num_rows()>0)
		{
			$this->dbx->update("save_acct",$data,array("job_no"=>$data['job_no']));
		}
		else 
		{
			$this->dbx->insert("save_acct",$data);
		}
				
	}
}


