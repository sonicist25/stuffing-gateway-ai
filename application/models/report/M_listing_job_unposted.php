<?php
class M_listing_job_unposted extends CI_Model
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
		$this->dbx->select(array(
           'inf_income.kode_ref as kode_ref',
		   'SUBSTRING(inf_income.kode_ref, 1,15) as job_number',
           'inf_income.created_by as created_by',
           'inf_income.region_id as region_id',
		   'trnstuffingplan.kode_ref as ref_no',
		   'trnstuffingplan.prepared as prepared',   
      ));
		$this->dbx->join('trnstuffingplan','trnstuffingplan.kode_ref=SUBSTRING(inf_income.kode_ref, 1, 15)');
		$this->dbx->from('inf_income');
        $this->dbx->where(array('inf_income.region_id'=>$this->session->userdata("region_id"),'created_by'=>null,'LENGTH(inf_income.kode_ref)'=>16));
		$this->dbx->like('inf_income.kode_ref',substr($data['tahun'],2).$data['bulan']);
     	$this->dbx->order_by("inf_income.kode_ref","asc");
		$q=$this->dbx->get();
		//print_r($this->dbx->last_query());
		$last_query = $this->dbx->last_query();

		$qr = $this->dbx->get_where("last_query",array("user_id"=>$this->session->userdata('user_id')));
		
		if($qr->num_rows() > 0 ) {
			$this->dbx->update('last_query',array('query'=>$last_query),array("user_id"=>$this->session->userdata('user_id')));
			return array('status'=>$q->num_rows(),'bulan'=>$data['bulan'],'rows'=>$q->result_array());
		} else {
			$this->dbx->insert('last_query',array('user_id'=>$this->session->userdata('user_id'),'query'=>$last_query));
			return array('status'=>$q->num_rows(),'bulan'=>$data['bulan'],'rows'=>$q->result_array());
		}
}
	
	function get_q2(){
		$data = $this->dbx->get_where('last_query',array('user_id'=>$this->session->userdata('user_id')));	
		
		if($data->num_rows() > 0 ) {
			$sql = $data->row()->query;
			$query = $this->dbx->query($sql);
			return $query->result_array();
		} else {
			return false;	
		}
	}

}


