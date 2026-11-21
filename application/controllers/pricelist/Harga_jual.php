<?php
class Harga_jual extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->library('fungsi');
		$this->fungsi->restrict();
		$this->load->model(array('pricelist/harga_jual_model','reference/port_destination_model','reference/agent_company_model'));
	}
	
	function index() 
	{
		$this->fungsi->auth(25,'Harga Jual');
		$data = array( 'menu_navigation' => 'navigation', 
							'main_view' => 'pricelist/v_harga_jual',
							'title' => 'Harga Jual',
							'rowList'=>'',
		);
		$this->load->view('afg_template', $data);
	}
	
	function open_form() 
	{
		$data = '';
		$this->load->view('pricelist/v_harga_jual_form', $data);
	}
	
	function cetak_form() 
	{
		$this->load->library(array('fpdf', 'fungsi'));
		$this->fpdf->FPDF('P','mm','A4');
		$this->fpdf->AddPage();
		$this->fpdf->SetFont('Arial','',8);
		
		$this->fpdf->Output('HARGA JUAL '. date('d-M-Y'),'I');
	}
}

