<?php
class Laporan_per_departement extends CI_Controller
{
	var $title ='Laporan Per Departement';
	public function __construct()
	{
		parent::__construct();
		$this->fungsi->restrict();
		$this->load->model('report/job_sheet_model');
	}

	function index()
	{
		$this->fungsi->auth(58,'Laporan Per Departement');
		$data['title'] = $this->title;
		$data['menu_navigation'] ='navigation';
		$data['main_view'] ='report/template-report';
		$this->load->view('afg_template', $data);
	}
	
	function open_form() {	
 		$this->load->view('report/v_laporan_per_departement');
	}

}
