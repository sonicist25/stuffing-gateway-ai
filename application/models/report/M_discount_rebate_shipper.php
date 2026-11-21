<?php
class M_discount_rebate_shipper extends CI_Model
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
	
	function get_shipper_commission($data) {
		$query = $this->get_commission_data($data);
		return $query;
	}
	
	function get_commission_data($data) {
		$user_id = $this->session->userdata('user_id');
		$region_id=$this->session->userdata('region_id');
		$tgl_awal = $this->fungsi->jin_date_sql($data['tgl_awal']);
		$tgl_akhir = $this->fungsi->jin_date_sql($data['tgl_akhir']);
		$this->dbx->select(array(
			'inf_cost_refund_discount.kode_ref',
			'inf_cost_refund_discount.mchgs',
			'inf_cost_refund_discount.qty',
			'inf_cost_refund_discount.unit_price',
			'inf_cost_refund_discount.currency',
			'trnstuffingplandetail.shipper_print as shipper',
		));
				
		$this->dbx->join('trnstuffingplandetail','trnstuffingplandetail.sp_detail_id=inf_cost_refund_discount.sp_detail_id','left');
		$this->dbx->join('trnstuffingplan','trnstuffingplan.sp_id=trnstuffingplandetail.sp_id','left');		
		$this->dbx->where("if(trnstuffingplan.kode_ref like '%-300%' or trnstuffingplan.kode_ref like '%-301%' or trnstuffingplan.kode_ref like '%-302%' or trnstuffingplan.kode_ref like  '%-600%' or trnstuffingplan.kode_ref like  '%-400%',`eta`,`etd_jkt`)>='$tgl_awal'");
		$this->dbx->where("if(trnstuffingplan.kode_ref like '%-300%' or trnstuffingplan.kode_ref like '%-301%' or trnstuffingplan.kode_ref like '%-302%' or trnstuffingplan.kode_ref like  '%-600%' or trnstuffingplan.kode_ref like  '%-400%',`eta`,`etd_jkt`)<='$tgl_akhir'");
		$this->dbx->where('trnstuffingplan.region_id',$region_id);
		$this->dbx->where('trnstuffingplandetail.sales',$user_id);
	    $this->dbx->order_by('trnstuffingplandetail.shipper_print','asc');
		$this->dbx->group_by('trnstuffingplandetail.shipper_print');
		$query = $this->dbx->get('inf_cost_refund_discount');
		return $query;
	}

function get_commission_data_all($data) {
		$region_id=$this->session->userdata('region_id');
		$tgl_awal = $this->fungsi->jin_date_sql($data['tgl_awal']);
		$tgl_akhir = $this->fungsi->jin_date_sql($data['tgl_akhir']);
		$this->dbx->select(array(
			'inf_cost_refund_discount.kode_ref',
			'inf_cost_refund_discount.mchgs',
			'inf_cost_refund_discount.qty',
			'inf_cost_refund_discount.unit_price',
			'inf_cost_refund_discount.currency',
			'trnstuffingplandetail.shipper_print as shipper',
		));
				
		$this->dbx->join('trnstuffingplandetail','trnstuffingplandetail.sp_detail_id=inf_cost_refund_discount.sp_detail_id','left');
		$this->dbx->join('trnstuffingplan','trnstuffingplan.sp_id=trnstuffingplandetail.sp_id','left');		
		$this->dbx->where("if(trnstuffingplan.kode_ref like '%-300%' or trnstuffingplan.kode_ref like '%-301%' or trnstuffingplan.kode_ref like '%-302%' or trnstuffingplan.kode_ref like  '%-600%' or trnstuffingplan.kode_ref like  '%-400%',`eta`,`etd_jkt`)>='$tgl_awal'");
		$this->dbx->where("if(trnstuffingplan.kode_ref like '%-300%' or trnstuffingplan.kode_ref like '%-301%' or trnstuffingplan.kode_ref like '%-302%' or trnstuffingplan.kode_ref like  '%-600%' or trnstuffingplan.kode_ref like  '%-400%',`eta`,`etd_jkt`)<='$tgl_akhir'");
		$this->dbx->where('trnstuffingplan.region_id',$region_id);
	    $this->dbx->order_by('trnstuffingplandetail.shipper_print','asc');
		$this->dbx->group_by('trnstuffingplandetail.shipper_print');
		$query = $this->dbx->get('inf_cost_refund_discount');
		return $query;
	}
	
	function get_proses($data) {
		$tgl_awal = $this->fungsi->jin_date_sql($data['tgl_awal']);
		$tgl_akhir = $this->fungsi->jin_date_sql($data['tgl_akhir']);
		$this->dbx->select(array(
			'inf_cost_refund_discount.kode_ref',
			'inf_cost_refund_discount.mchgs',
			'inf_cost_refund_discount.qty',
			'inf_cost_refund_discount.unit_price',
			'inf_cost_refund_discount.currency',
			'trnstuffingplandetail.shipper_print as shipper',
			//'trnstuffingplandetail.shipper_import as shipper_imp',
			'trnstuffingplandetail.destination as destination',
			'trnstuffingplandetail.bl_meas as qty_detail',
		));
		$this->dbx->join('trnstuffingplandetail','trnstuffingplandetail.kode_ref=inf_cost_refund_discount.kode_ref','left');
		$this->dbx->join('trnstuffingplan','trnstuffingplan.sp_id=trnstuffingplandetail.sp_id','left');
		$this->dbx->where("if(trnstuffingplan.kode_ref like '%-300%' or trnstuffingplan.kode_ref like '%-301%' or trnstuffingplan.kode_ref like '%-302%' or trnstuffingplan.kode_ref like  '%-600%' or trnstuffingplan.kode_ref like  '%-400%',`eta`,`etd_jkt`)>='$tgl_awal'");
		$this->dbx->where("if(trnstuffingplan.kode_ref like '%-300%' or trnstuffingplan.kode_ref like '%-301%' or trnstuffingplan.kode_ref like '%-302%' or trnstuffingplan.kode_ref like  '%-600%' or trnstuffingplan.kode_ref like  '%-400%',`eta`,`etd_jkt`)<='$tgl_akhir'");
		$this->dbx->where('trnstuffingplandetail.shipper_print',$data['shipper']);
		
		$query = $this->dbx->get('inf_cost_refund_discount')->result_array();
		return $query;
		
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


