<?php
class M_listing_invoice extends CI_Model
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
	$this->dbx->select('x_finance.id as id,
		x_finance.job_no as job_no,
		x_finance.jenis as jenis,
		x_finance.group as group,
		x_finance.se_exportir as si_shipper,
		GROUP_CONCAT(x_finance.number) AS number,
		GROUP_CONCAT(x_finance.date) as date,
		GROUP_CONCAT(IF(x_finance.status="paid","",datediff(current_date(),date_format(x_finance.date,"%Y-%m-%d")))) as aging,
		GROUP_CONCAT(IF(x_finance.status="paid",x_finance.status,IF(datediff(current_date(),date_format(x_finance.date,"%Y-%m-%d"))>60,"overdue",x_finance.status))) as status,
		x_finance.region_id as region_id,
		trnstuffingplandetail.kode_ref as kode_ref,
		trnstuffingplandetail.shipper_print as receive_from,
		trnstuffingplandetail.destination as destination,
		trnstuffingplandetail.hb_l as bl,
		trnstuffingplan.etd_jkt as etd,
		trnstuffingplan.eta as eta,
		trnstuffingplan.tanggal_job as eta,
		trnstuffingplan.type_of_trans as type,
');

	 $this->dbx->join('trnstuffingplan','trnstuffingplan.kode_ref=trnstuffingplandetail.code','left');	
	 $this->dbx->join('x_finance','x_finance.job_no=trnstuffingplandetail.kode_ref','left outer');
	 $this->dbx->where(array('trnstuffingplandetail.region_id'=>$this->session->userdata("region_id")));
	 $this->dbx->like('trnstuffingplan.tanggal_job',$data['tahun'].$data['bulan']);
     $this->dbx->group_by("trnstuffingplandetail.kode_ref");
	 $q=$this->dbx->get('trnstuffingplandetail');
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
	
	function get_billed($kode_ref){
		$data = $this->dbx->get_where('inf_income_selling_price',array('kode_ref'=>$kode_ref,'ditarik'=> 0));	
		
		if($data->num_rows() > 0 ) {
			return 'belum';
		} else {
			return 'billed';	
		}
	}
	

}


