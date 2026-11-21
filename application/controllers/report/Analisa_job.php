<?php
class Analisa_job extends CI_Controller
{
	var $title ='Analisa Job';
	public function __construct(){
		parent::__construct();
		$this->fungsi->restrict();
		$this->load->model(array('report/m_analisa_job','report/m_rekap_jobsheet'));
	}

	function index(){
		$this->fungsi->auth(56,$this->title);
		$data['title'] = $this->title;
		$data['menu_navigation'] ='navigation';
		$data['main_view'] ='report/template-report';
		$this->load->view('afg_template', $data);
	}
	
	function open_form() {	
 		$this->load->view('report/v_analisa_job');
	}
	
	function get_proses() {	
 		$data = $this->m_analisa_job->get_proses($_POST);
 		echo json_encode($data);
	}
	
	function to_pdf(){
 		$data = $this->m_analisa_job->get_query()->result_array();
 		$this->session->set_flashdata($_POST);
		$this->load->library('FPDF_MC_Table');
		$pdf=new FPDF_MC_Table();	
			
		$pdf->AliasNbPages();
		$periode = $this->session->flashdata('bulan');
		if($periode==true) {
			$bulan = $this->fungsi->bulan2(substr($periode,0,2));
			$tahun = substr($periode,3,4); 
			$pdf->setDataHeader(array('report'=>'8','periode'=>$bulan.' '.$tahun,));
		}	
		$pdf->Addpage('L','A4');
		$pdf->SetFont('Arial','',8);
		$pdf->SetWidths(array(10,100,30,45,45,45));
		$pdf->SetAligns(array('C','L','C','R','R','R'));
		$i=1;	
		$n=0;

		foreach($data as $row ) {	
			$shipper_name = $this->get_shipper_name($row['shipper']);		
			$pdf->Row(array(
				$i,
				strtoupper($shipper_name),
				$row['jumlah_booking'],
				$this->fungsi->pecah($row['income'],' '),
				$this->fungsi->pecah($row['cost'],' '),
				$this->fungsi->pecah($row['profit'],' '),
			));
			$i++;	
		}
		$pdf->Output('ANALISA_JOB_'. time().'.pdf','I');
	//	//print_r($data);
	}
	
	function to_excel() {	
		$data = $this->m_analisa_job->get_query()->result_array();
 	//	//print_r($data);
	}
	
	function get_shipper_name($shipper){
		$data = $this->m_analisa_job->get_shipper_name($shipper);
		return $data;
	}

}

/*

sortir desc jumlah booking, income, profit

*/