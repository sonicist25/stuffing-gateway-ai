<?php
class C_post_data_acct_per_inv extends CI_Controller
{
	var $title ='Post Data Invoice Per Job';
	public function __construct(){
		parent::__construct();
		$this->fungsi->restrict();
		$this->load->model('finance/M_post_data_acct');
		$this->load->library('Fungsi');

        // Inject koneksi database aktif ke library Fungsi
        $this->fungsi->dbx = $this->M_post_data_acct->dbx;
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
 		$this->load->view('finance/v_post_data_acct_inv');
	}
	
	function get_invoice() {	
 		$jobsDBPlan = $this->M_post_data_acct->get_kode_ref_plan($_POST)->result();
 		$jobsDBDetail = $this->M_post_data_acct->get_kode_ref_detail($_POST)->result();
		$jobs = array();
		foreach($jobsDBPlan as $jobDB){
			array_push($jobs, $jobDB->job_no);
		}
		foreach($jobsDBDetail as $jobDB){
			array_push($jobs, $jobDB->job_no);
		}
 		$invoices = $this->M_post_data_acct->get_invoice_data($jobs, $_POST['query'])->result();

		$data = array();
		foreach($invoices as $row ){
			$data[]=array("id" => $row->number, "text" => $row->number);
		}
		
		echo json_encode($data);

		// $dropdown = form_dropdown('dropdown_invoice',$option,'','id="dropdown_invoice" style="min-width: 200px"');
 		// echo json_encode(array('dropdown'=>$dropdown));
	}

	function proses_post()
	{
		$data['invoice'] = $_POST['invoice_no'];
		$dbx = $this->M_post_data_acct->dbx;
		body_jasaweb_invoice($data,$dbx);
	}
}

/*
function too_excel(){	
		$this->load->library(array('Spreadsheet_Excel_Reader','fungsi'));
		$excel = new Spreadsheet_Excel_Reader('tes.xls');
		echo $excel->dump(true,true);
}
*/

