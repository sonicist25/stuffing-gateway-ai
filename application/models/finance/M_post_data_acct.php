<?php
class M_post_data_acct extends CI_Model
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
   
   function get_kode_ref($data) {
		$query = $this->get_kode_ref($data);
		return $query;
	}
	
   function get_kode_ref_data() {
		$bln=$this->input->post('bln');
		$region_id=$this->session->userdata('region_id');
		$this->dbx->select(array(
			'trnstuffingplandetail.kode_ref as job_no',
		));		
		$this->dbx->where("SUBSTRING(trnstuffingplandetail.kode_ref, 8, 4)=", $bln);
		$this->dbx->where('trnstuffingplandetail.region_id',$region_id);
		$this->dbx->order_by('kode_ref', 'ASC');
		$query = $this->dbx->get('trnstuffingplandetail');
		return $query;
	}
	
	function get_kode_ref_plan() {
		$bln=$this->input->post('bln');
		$region_id=$this->session->userdata('region_id');
		$this->dbx->select(array(
			'trnstuffingplan.kode_ref as job_no',
		));		
		$this->dbx->where("SUBSTRING(trnstuffingplan.kode_ref, 8, 4)=", $bln);
		$this->dbx->where('trnstuffingplan.region_id',$region_id);
		$this->dbx->order_by('kode_ref', 'ASC');
		$query = $this->dbx->get('trnstuffingplan');
		return $query;
	}

	function get_kode_ref_detail() {
		$bln=$this->input->post('bln');
		$region_id=$this->session->userdata('region_id');
		$this->dbx->select(array(
			'trnstuffingplandetail.kode_ref as job_no',
		));		
		$this->dbx->where("SUBSTRING(trnstuffingplandetail.kode_ref, 8, 4)=", $bln);
		$this->dbx->where('trnstuffingplandetail.region_id',$region_id);
		$this->dbx->order_by('kode_ref', 'ASC');
		$query = $this->dbx->get('trnstuffingplandetail');
		return $query;
	}


	function get_invoice_data($jobs,$noInv)
	{
		$array = join("','",$jobs);
		// //print_r($array);
		// die();
		if($noInv != ''){
			$query = $this->dbx->query("select x_finance.number from x_finance WHERE x_finance.job_no IN ('".$array."') and x_finance.number like '%".$noInv."%' and (jenis = 'INVOICE' or jenis = 'DEBIT NOTE') and region_id = 'Jakarta' limit 10");
		}else{
			$query = $this->dbx->query("select x_finance.number from x_finance WHERE x_finance.job_no IN ('".$array."') and (jenis = 'INVOICE' or jenis = 'DEBIT NOTE') and region_id = 'Jakarta'");
		}
		// and x_finance.number like %'.$noInv.'%
		// //print_r($this->dbx->last_query());
		// die();
		// $this->dbx->select(array(
		// 	'x_finance.number',
		// ));
		// $this->dbx->where('x_finance.region_id', "Jakarta");
		// $this->dbx->where('x_finance.jenis', "INVOICE");
		// $this->dbx->or_where('x_finance.jenis', "DEBIT NOTE");
		// $this->dbx->where_in('x_finance.job_no', $jobs);
		// $this->dbx->like('job_no', $noInv, 'both');
		// $this->dbx->limit(10);
		// $query = $this->dbx->get('x_finance');
		return $query;
	}

	function proses_kode_ref()
	{
		
	}
}
?>