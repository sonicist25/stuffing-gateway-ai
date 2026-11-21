<?php
class M_rekap_agent extends CI_Model
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
	
	function get_id_agent($code)
	{
		$q = $this->dbx->get_where("refagentcompany",array("agent_company_code"=>$code));
		if($q->num_rows()>0) 
		{
			return $q->row()->agent_company_name;
		}
		else 
		{
			return "";
		}
	}
	
	function get_sales_type($code)
	{
		$q = $this->dbx->get_where("trnstuffingplandetail",array("code"=>$code));
		// Check if rows are found
		if ($q->num_rows() > 0) {
		// Initialize totals
		$total_sales_91 = 0;
		$total_sales_not_91 = 0;

		// Loop through each row
			foreach ($q->result() as $row) {
				if ($row->sales == 91 || $row->sales == 90) {
					$total_sales_91 += $row->bl_meas;
					} else {
					$total_sales_not_91 += $row->bl_meas;
					}
				}
		}
		 return array(
        'total_sales_91' => $total_sales_91,
        'total_sales_not_91' => $total_sales_not_91
		);
	}
	
	function get_agent() {
		$region_id=$this->session->userdata('region_id');
		$this->dbx->select(array(
			'agen_penagihan',
		));		
		$this->dbx->where('trnstuffingplan.region_id',$region_id);
		$this->dbx->group_by('trnstuffingplan.agen_penagihan');
		$this->dbx->order_by('trnstuffingplan.agen_penagihan','asc');
		$query = $this->dbx->get('trnstuffingplan');
		if($query->num_rows() > 0 ) {
			return $query->result();
		} else {
			return false;
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
		
		$agent= ($data['agent']);
		if($data['bulan']!==""){		
		$bulan = substr($data['bulan'],0,2);
		}else{
		$bulan = 00;
		}
	
		$this->dbx->select(array(
			'trnstuffingplan.sp_id as id',
			'trnstuffingplan.kode_ref as kode_ref',
			'trnstuffingplan.stuffing_date as tgl_job',
			'trnstuffingplan.etd_jkt as etd',
			'trnstuffingplan.destination as destination',
			'trnstuffingplan.port_loading as origin',
			'trnstuffingplan.shipment_type_multi as type'));		
		$this->dbx->order_by("kode_ref","asc");
		if($bulan!==00){
		$query = $this->dbx->get_where('trnstuffingplan',array('trnstuffingplan.region_id'=>$region_id,'substr(kode_ref,10,2)'=>$bulan,'agen_penagihan'=>$agent));
		}else{
		$query = $this->dbx->get_where('trnstuffingplan',array('trnstuffingplan.region_id'=>$region_id,'agen_penagihan'=>$agent));	
		}	
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
	
	function get_detail($code,$mode){
		$region_id=$this->session->userdata('region_id');
		$this->dbx->like('job_no',$code);
		$this->dbx->like('region_id',$region_id);
		$q = $this->dbx->get('save_acct');
		if($q->num_rows() > 0 ) {
			foreach($q->result() as $row){
				$kurs[]=floatval($row->kurs);
				$income_rp[]=floatval($row->ttl_income_rp);
				$income_usd[]=floatval($row->ttl_income_usd);
				$income[]=floatval($row->total_income);
				$cost_rp[]=floatval($row->ttl_cost_rp);
				$cost_usd[]=floatval($row->ttl_cost_usd);
				$cost[]=floatval($row->total_cost);
				$profit[]=floatval($row->total_profit);
				$meas[]=floatval($row->total_meas);
				
			}
			if($mode=='kurs') {
				return array_sum($kurs);
				//return $code;
			}
			elseif($mode=='income_rp') {
				return array_sum($income_rp);
			}
			elseif($mode=='income_usd') {
				return array_sum($income_usd);
			}
			elseif($mode=='total_income') {
				return array_sum($income);
			}	
			elseif($mode=='cost_rp') {
				return array_sum($cost_rp);
			}
			elseif($mode=='cost_usd') {
				return array_sum($cost_usd);
			}
			elseif($mode=='total_cost') {
				return array_sum($cost);
			}
			elseif($mode=='total_profit') {
				return array_sum($profit);
			}
			elseif($mode=='meas') {
				return array_sum($meas);
			}
		} else {
			return "";
		}
	}
}




