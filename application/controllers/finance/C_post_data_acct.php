<?php
class C_post_data_acct extends CI_Controller
{
	var $title ='Post Data Acct';
	public function __construct(){
		parent::__construct();
		$this->fungsi->restrict();
		$this->load->model('finance/M_post_data_acct');
	}

	function index(){
		$this->fungsi->auth(50,'report Job Sheet');
		$data['title'] = $this->title;
		$data['table_title'] ='INVOICE';
		$data['menu_navigation'] ='navigation';
		$data['main_view'] ='finance/template-finance';
		$this->load->view('afg_template', $data);
	}
	
	function open_form() {	
 		$this->load->view('finance/v_post_data_acct_periode');
	}
		
	function proses_post()
	{
		$bln=$_POST['bulan'];
		$region_id=$this->session->userdata('region_id');
		$this->dbx->select(array(
			'trnstuffingplandetail.kode_ref as job_no',
			'trnstuffingplandetail.region_id as region_id',
		));		
		$this->dbx->where("SUBSTRING(trnstuffingplandetail.kode_ref, 8, 4)=", $bln);
		$this->dbx->where('trnstuffingplandetail.region_id',$region_id);
		$this->dbx->order_by('kode_ref', 'ASC');
		$query = $this->dbx->get('trnstuffingplandetail');
		foreach($query->result() as $res){
			$data['kode_ref'] = $res->job_no;
			body_jasaweb_jobsheet($data);
		}
	}
}
