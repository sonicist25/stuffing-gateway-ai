<?php
class Laporan_job extends CI_Controller
{
	var $title ='Laporan Job';
	public function __construct()
	{
		parent::__construct();
		$this->fungsi->restrict();
		$this->load->model('report/job_sheet_model');
	}

	function index()
	{
		$this->fungsi->auth(59,'Report Laporan Job');
		$data['title'] = $this->title;
		$data['menu_navigation'] ='navigation';
		$data['main_view'] ='report/template-report';
		$this->load->view('afg_template', $data);
	}
	
	function open_form() {	
 		$this->load->view('report/v_laporan_job');
	}


}
