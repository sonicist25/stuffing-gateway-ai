<?php
class M_rekap_jobsheet extends CI_Model
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
	
	function get_proses($data){
		$region_id=$this->session->userdata('region_id');
		$user_id=$this->session->userdata('user_id');	
		$this->dbx->where('user_id',$user_id);
		$this->dbx->delete('last_query');		

		$bulan = substr($data['bulan'],3,4). substr($data['bulan'],0,2);
	
		$this->dbx->select(array(
			
			'trnstuffingplandetail.kode_ref as job_no',
			'trnstuffingplandetail.shipper as shipper',
			'trnstuffingplandetail.shipper as shipper_imp',
			'trnstuffingplandetail.destination as spd_destination',
			'trnstuffingplandetail.bl_meas as meas',
			'trnstuffingplandetail.desc_goods as desc_goods',
			'trnstuffingplandetail.sales as sales',
			'trnstuffingplan.sp_id as id',
			'trnstuffingplan.kode_ref as kode_ref',
			'trnstuffingplan.stuffing_date as tgl_job',
			'trnstuffingplan.vessel as vessel',
			'trnstuffingplan.voyage as voyage',
			'trnstuffingplan.destination as destination',
			'trnstuffingplandetail.shipper_print as shipper',
		));		
		$this->dbx->join('trnstuffingplandetail','trnstuffingplandetail.sp_id=trnstuffingplan.sp_id','left');
		$this->dbx->like('trnstuffingplan.stuffing_date',$bulan);
		$this->dbx->order_by("kode_ref","asc");
		$query = $this->dbx->get_where('trnstuffingplan',array('trnstuffingplan.region_id'=>$region_id));
		$last_query = $this->dbx->last_query();

	//	return array('query'=>$last_query,'status'=>$query->num_rows(),'result'=>$query->result_array());
		
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

	function get_kubikasi($code){
		$this->dbx->like('kode_ref',$code);
		$q = $this->dbx->get('trnstuffingplandetail');	
		if($q->num_rows() > 0 ) {
			foreach($q->result() as $row){
				$kubik[]=$row->bl_meas;
			}
			return array_sum($kubik);
		} else {
			return "";
		}	
	}

	function get_detail($code,$mode){
		$region_id=$this->session->userdata('region_id');
		$q = $this->dbx->get_where('save_jobsheet',array('region_id'=>$region_id,'kode_ref'=>$code));
		if($q->num_rows() > 0 ) {
			foreach($q->result() as $row){
				$income[]=$row->income;
				$cost[]=$row->cost;
				$profit[]=$row->profit;
			}
			if($mode=='income') {
				return array_sum($income);
				//return $code;
			}
			elseif($mode=='cost') {
				return array_sum($cost);
			}
			elseif($mode=='profit') {
				return array_sum($profit);
			}
		} else {
			return "";
		}
	}
}


