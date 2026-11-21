<?php
class M_analisa_job extends CI_Model
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
	
	function get_shipper_name($shipper){
		if($shipper !='') {
			$query = $this->dbx->get_where('mstshipper',array('shipper_id'=>$shipper));
			if($query->num_rows() > 0 ) {
				return $query->row()->shipper_name;
			} else {
				$query2 = $this->dbx->get_where('refconsigneeimport',array('consignee_import_code'=>$shipper));
				if($query2->num_rows() > 0 ) {
					return $query2->row()->consignee_import_name;
				}
			}
		} else {
			return "";
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
	
	function get_proses($data){
		$region_id=$this->session->userdata('region_id');
		$user_id=$this->session->userdata('user_id');	
		$bulan = substr($data['bulan'],3,4). substr($data['bulan'],0,2);
		
		$this->dbx->where('user_id',$user_id);
		$this->dbx->delete('last_query');	
		$this->dbx->where('periode',$bulan);
		$this->dbx->delete('analisa_job');		
		
		$this->dbx->select(array(
			'trnstuffingplan.stuffing_date as stuffing_date',
			'trnstuffingplandetail.shipper as shipper',
			'trnstuffingplandetail.shipper_import_code as shipper_imp',
			'trnstuffingplandetail.shipper_import as shipper_import',
			'trnstuffingplandetail.kode_ref as kode_ref',
		));
				
		$this->dbx->join('trnstuffingplan','trnstuffingplan.kode_ref=trnstuffingplandetail.code','left');
		$this->dbx->like('trnstuffingplan.stuffing_date',$bulan);
		$this->dbx->where('trnstuffingplan.kode_ref !=','');
		$this->dbx->where('trnstuffingplan.region_id',$region_id);			
		$query = $this->dbx->get('trnstuffingplandetail');
		
		if($query->num_rows() > 0 ) {
			foreach($query->result() as $row ) {
				$shipper = $row->shipper; 
				if($shipper=='') { $shipper = $row->shipper_imp; }
				$shippernya = $shipper; 
				$jumlah_booking = $this->get_jumlah_booking($shipper,$row->stuffing_date);
				$income = $this->get_detail($row->kode_ref,'income');
				$cost = $this->get_detail($row->kode_ref,'cost');
				$profit = $this->get_detail($row->kode_ref,'profit');
				$periode = $bulan;
				$this->dbx->insert('analisa_job',array(
					'shipper'=>$shippernya,
					'jumlah_booking'=>$jumlah_booking,
					'income'=>$income,
					'cost'=>$cost,
					'profit'=>$profit,
					'periode'=>$periode,
				));
			}
		}
		$last_query = 'select * from analisa_job where periode = '.$periode.' order by '.$data['sidx'].' desc ';
		$this->dbx->insert('last_query',array('user_id'=>$user_id,'query'=>$last_query));
		return array('status'=>$query->num_rows(),'rows'=>$query->result_array(),'periode'=>$bulan);	
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

	function get_jumlah_booking($shipper,$stuffing_date){
		$region_id=$this->session->userdata('region_id');
		$bulan = substr($stuffing_date, 0, 6);
		$q = $this->dbx->get_where('trnstuffingplandetail',array('shipper'=>$shipper));
		if($q->num_rows() > 0 ) {
			$this->dbx->join('trnstuffingplan','trnstuffingplan.kode_ref=trnstuffingplandetail.code','left');
			$this->dbx->like('trnstuffingplan.stuffing_date',$bulan);
			$this->dbx->where('trnstuffingplan.kode_ref !=','');
			$this->dbx->where('trnstuffingplan.region_id',$region_id);			
			$this->dbx->where('trnstuffingplandetail.shipper',$shipper);			
			$q1 = $this->dbx->get('trnstuffingplandetail');
			return $q->num_rows();
		} else {
			$this->dbx->join('trnstuffingplan','trnstuffingplan.kode_ref=trnstuffingplandetail.code','left');
			$this->dbx->like('trnstuffingplan.stuffing_date',$bulan);
			$this->dbx->where('trnstuffingplan.kode_ref !=','');
			$this->dbx->where('trnstuffingplan.region_id',$region_id);			
			$this->dbx->where('trnstuffingplandetail.shipper_import_code',$shipper);			
			$q1 = $this->dbx->get('trnstuffingplandetail');
			return $q1->num_rows();
		}
	}
	
	function get_detail($code,$mode){
		$this->dbx->like('kode_ref',$code);
		$q = $this->dbx->get('save_jobsheet');
		if($q->num_rows() > 0 ) {
			foreach($q->result() as $row){
				$income[]=$row->income;
				$cost[]=$row->cost;
				$profit[]=$row->profit;
			}
			if($mode=='income') {
				return array_sum($income);
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


