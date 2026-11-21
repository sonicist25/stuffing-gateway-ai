<?php
class C_post_data_inv extends CI_Controller
{
	var $title ='Post Data Invoice';
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
 		$this->load->view('finance/v_post_inv_periode');
	}
		
	function proses_post()
	{
		$jobsDBPlan = $this->M_post_data_acct->get_kode_ref_plan($_POST)->result();
 		$jobsDBDetail = $this->M_post_data_acct->get_kode_ref_detail($_POST)->result();
		$noInv = $this->input->post('bulan');
		$jobs = array();
		foreach($jobsDBPlan as $jobDB){
			array_push($jobs, $jobDB->job_no);
		}
		foreach($jobsDBDetail as $jobDB){
			array_push($jobs, $jobDB->job_no);
		}

 		$invoices = $this->M_post_data_acct->get_invoice_data($jobs, '')->result();
		if($invoices) {
			foreach($invoices as $row ){
				$data['invoice'] = $row->number;
				body_jasaweb_invoice($data);
			}
		}
	}
}
