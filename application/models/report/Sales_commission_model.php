<?php
class Sales_commission_model extends CI_Model
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
		$region_id=$this->session->userdata('region_id');
		$tgl_awal = $this->fungsi->jin_date_sql($data['tgl_awal']);
		$tgl_akhir = $this->fungsi->jin_date_sql($data['tgl_akhir']);
		$this->dbx->select(array(
			'trnstuffingplandetail.shipper_print as shipper',
		));		
		$this->dbx->join('trnstuffingplandetail','trnstuffingplandetail.sp_detail_id=inf_cost_refund_sc.sp_detail_id','left');
		$this->dbx->join('trnstuffingplan','trnstuffingplan.sp_id=trnstuffingplandetail.sp_id','left');		
		$this->dbx->where('trnstuffingplan.stuffing_date >=',$tgl_awal);
		$this->dbx->where('trnstuffingplan.stuffing_date <=',$tgl_akhir);
		$this->dbx->where('trnstuffingplan.region_id',$region_id);
		$query = $this->dbx->get('inf_cost_refund_sc');
		return $query;
	}
	
	function get_proses($data){
		$region_id=$this->session->userdata('region_id');
		$user_id=$this->session->userdata('user_id');
		
		$this->dbx->where('user_id',$user_id);
		$this->dbx->delete('last_query');
		
		$tgl_awal = $this->fungsi->jin_date_sql($data['tgl_awal']);
		$tgl_akhir = $this->fungsi->jin_date_sql($data['tgl_akhir']);
		
		$shipper = $this->dbx->get_where('mstshipper',array('shipper_name'=>$data['shipper']));
		
		if( $shipper->num_rows() > 0 ) {
			$where = $this->dbx->where('trnstuffingplandetail.shipper_print',$data['shipper']);
		} else {
			$where = $this->dbx->where('trnstuffingplandetail.shipper_import',$data['shipper']);
		}
		
		$this->dbx->select(array(
			'inf_cost_refund_sc.kode_ref',
			'inf_cost_refund_sc.mchgs',
			'inf_cost_refund_sc.qty',
			'inf_cost_refund_sc.unit_price',
			'inf_cost_refund_sc.currency',
			'trnstuffingplandetail.shipper_print as shipper',
			'trnstuffingplandetail.destination as destination',
			'trnstuffingplandetail.bl_meas as qty_detail',
		));		
		$this->dbx->join('trnstuffingplandetail','trnstuffingplandetail.kode_ref=inf_cost_refund_sc.kode_ref','left');
		$this->dbx->join('trnstuffingplan','trnstuffingplan.kode_ref=trnstuffingplandetail.code','left');
		$this->dbx->where('trnstuffingplan.stuffing_date >=',$tgl_awal);
		$this->dbx->where('trnstuffingplan.stuffing_date <=',$tgl_akhir);
		$this->dbx->where('trnstuffingplan.region_id',$region_id);	
		$where;	
		$query = $this->dbx->get('inf_cost_refund_sc');
		$last_query = $this->dbx->last_query();
	//	return array('rows'=>$query);
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


