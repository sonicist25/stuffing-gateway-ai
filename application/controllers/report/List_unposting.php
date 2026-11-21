<?php
class List_unposting extends CI_Controller
{
	var $title ='List job belum diposting';
	public function __construct(){
		parent::__construct();
		$this->fungsi->restrict();
		$this->load->model(array('report/m_listing_job_unposted','user_model'));
		$this->fungsi->dbx = $this->m_listing_job_unposted->dbx;
	}

	function index(){
		$this->fungsi->auth(64,'Unposting Job');
		$data['title'] = $this->title;
		$data['menu_navigation'] ='navigation';
		$data['main_view'] ='report/template-report';
		$this->load->view('afg_template', $data);
	}
	
	function open_form() {	
 		$this->load->view('report/v_listing_job_unposted');
	}

	function get_proses() {	
 		$data = $this->m_listing_job_unposted->get_proses($_POST);
 		echo json_encode($data);
	}
	
	function to_pdf(){
		
 		$data = $this->m_listing_job_unposted->get_q2(); 		
 		////print_r($data);
 		
 		$this->session->set_flashdata($_POST);
		$this->load->library('FPDF_MC_Table');
		$pdf=new FPDF_MC_Table();	
		
		$periode = $this->session->flashdata('bulan');
		if($periode==true) {
			$bulan = $this->fungsi->bulan2(substr($periode,0,2));
			$tahun = substr($periode,3,4); 
		$pdf->setDataHeader(array('report'=>'Unposting','periode'=>$bulan.' '.$tahun,));
		}		
		$pdf->AddPage('P','A4');	
		$pdf->AliasNbPages();
		$pdf->SetFont('Arial','',8);
		$pdf->SetWidths(array(10,30,50,55));
		$pdf->SetAligns(array('C','L','L','L'));
		$i=1;
		$n=0;

		foreach($data as $row ) {
			$pdf->Row(array(
				$i,
				$data[$n]['kode_ref'],
				$this->user_model->get_user_by_id($data[$n]['prepared']),
				"JOB SHEET",
			));
			$i++;
			$n++;
		}
		$pdf->Output('LISTING_JOB_'. time().'.pdf','I');
	}

	function to_excel() {
		$page = 'LISTING JOB YG BELUM DIPOSTING';
		$data = $this->m_listing_job_unposted->get_q2();
		//$this->session->set_flashdata(array('periode'=>$this->input->post('bulan')));
		$periode = $this->session->flashdata('bulan');
		if($periode==true) {
			$bulan = $this->fungsi->bulan2(substr($periode,0,2));
			$tahun = substr($periode,3,4); 
			$pdf->setDataHeader(array('report'=>'9','periode'=>$periode.' '.$tahun,));
		}	
		header("Cache-Control: no-cache, no-store, must-revalidate");  
		header("Content-Type: application/vnd.ms-excel");  
		header("Content-Disposition: attachment; filename='$page'.xls");		
 		echo link_tag('public/theme/css/report.css', 'stylesheet', 'text/css').
		'<table summary="" width="100%" align="center">
		<tr><td class="space"></td><td class="judul" colspan="11">LISTING JOB YG BELUM DIPOSTING</td></tr>
	   <tr><td class="space"></td><td class="title" colspan="11">Periode : '.$periode.'</td></tr>
		<tr>
			<td class="space"></td>
			<td align="center" width="30" class="header">NO</td>
			<td align="center" width="120" class="header">JOB NO #</td>
			<td align="center" width="90" class="header">PIC</td>
			<td align="center" width="170" class="header">STATUS</td>
		</tr>
		<tr><td style="border:none; height: 5px" colspan="11"></td></tr>';
		if($data) {
			$i=1;	
			$n=0;
			foreach($data as $row ) {				
				echo 
				'<tr>
					<td class="space"></td>
					<td align="center">'.$i.'</td>
					<td align="left">'.$data[$n]['kode_ref'].'</td>
					<td align="left">'.$this->user_model->get_user_by_id($data[$n]['prepared']).'</td>
					<td align="left">'."JOBSHEET".'</td>
				</tr>';
				$i++;
				$n++;
			}
		}
		echo '</table>';
		exit();
	}
	
	function list_job1(){
		$value = $this->m_listing_job->get_proses(array('bulan'=>'03/2014'));
		echo json_encode($value);
	}
	
	function list_job2(){
		$data = $this->m_listing_job->get_string();
		foreach($data as $i => $val){
			echo $data[$i]. br();
		}
		/*
	//	echo json_encode(array('count'=>count($value),'data'=>$value));
		*/
	}
	
}
