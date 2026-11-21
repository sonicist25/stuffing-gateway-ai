<?php
class Listing_job extends CI_Controller
{
	var $title ='Listing Job';
	public function __construct(){
		parent::__construct();
		$this->fungsi->restrict();
		$this->load->model('report/m_listing_job');
	}

	function index(){
		//$this->fungsi->auth(57,'Analisa Job');
		$data['title'] = $this->title;
		$data['menu_navigation'] ='navigation';
		$data['main_view'] ='report/template-report';
		$this->load->view('afg_template', $data);
	}
	
	function open_form() {	
 		$this->load->view('report/v_listing_job_view');
	}

	function get_proses() {	
 		$data = $this->m_listing_job->get_proses($_POST);
 		echo json_encode($data);
	}
	
	function to_pdf(){
		
 		$data = $this->m_listing_job->get_q2(); 		
 		////print_r($data);
 		
 		$this->session->set_flashdata($_POST);
		$this->load->library('FPDF_MC_Table');
		$pdf=new FPDF_MC_Table();	
		
		$periode = $this->session->flashdata('bulan');
		if($periode==true) {
			$bulan = $this->fungsi->bulan2(substr($periode,0,2));
			$tahun = substr($periode,3,4); 
		$pdf->setDataHeader(array('report'=>'9','periode'=>$bulan.' '.$tahun,));
		}else{
			$tahun = $this->session->userdata('db');  
		$pdf->setDataHeader(array('report'=>'9','periode'=>$tahun,));
		}
		$pdf->AddPage('P','A4');	
		$pdf->AliasNbPages();
		$pdf->SetFont('Arial','',8);
		$pdf->SetWidths(array(10,30,35,10,70,10,25));
		$pdf->SetAligns(array('C','L','L','C','L','L','R'));
		$i=1;
		$n=0;

		foreach($data as $row ) {
			if($data[$n]['prep_coll']=='PREPAID') {
				$pc = 'P';
			} else {
				$pc = 'C';
			}
			$pdf->Row(array(
				$i,
				$data[$n]['kode_ref'],
				//strtoupper($data[$n]['code_service']),
				strtoupper($data[$n]['item_service']??''),
				$pc,
				strtoupper($data[$n]['receive_from']??''),
				$data[$n]['currency'],
				$this->fungsi->pecah($data[$n]['price'],","),
				//$data[$n]['price'],
			));
			$i++;
			$n++;
		}
		$pdf->Output('LISTING_JOB_'. time().'.pdf','I');
	}

	function to_excel() {
		$page = 'LISTING JOB YG BELUM DI BUAT INVOICE';
		$data = $this->m_listing_job->get_q2();
		//$this->session->set_flashdata(array('periode'=>$this->input->post('bulan')));
		$periode = $this->session->flashdata('bulan');
		if($periode==true) {
			$bulan = $this->fungsi->bulan2(substr($periode,0,2));
			$tahun = substr($periode,3,4); 
			$range = $bulan." ".$tahun;
		}else{
		$tahun = $this->session->userdata('db');  
		$range = $tahun;
		}	
		header("Cache-Control: no-cache, no-store, must-revalidate");  
		header("Content-Type: application/vnd.ms-excel");  
		header("Content-Disposition: attachment; filename='$page'.xls");		
 		echo link_tag('public/theme/css/report.css', 'stylesheet', 'text/css').
		'<table summary="" width="100%" align="center">
		<tr><td class="space"></td><td class="judul" colspan="11">LISTING JOB YG BELUM DI BUAT INVOICE</td></tr>
	   <tr><td class="space"></td><td class="title" colspan="11">Periode : '.$range.'</td></tr>
		<tr>
			<td class="space"></td>
			<td align="center" width="30" class="header">NO</td>
			<td align="center" width="120" class="header">JOB NO #</td>
			<td align="center" width="90" class="header">KODE</td>
			<td align="center" width="170" class="header">NAMA SERVICE</td>
			<td align="center" width="50" class="header">P/C</td>
			<td align="center" width="250" class="header">TAGIHAN KE</td>
			<td align="center" width="50" class="header">MU</td>
			<td align="center" width="120" class="header">JUMLAH</td>
		</tr>
		<tr><td style="border:none; height: 5px" colspan="11"></td></tr>';
		if($data) {
			$i=1;	
			$n=0;
			if($data[$n]['prep_coll']=='PREPAID') {
				$pc = 'P';
			} else {
				$pc = 'C';
			}
			foreach($data as $row ) {				
				echo 
				'<tr>
					<td class="space"></td>
					<td align="center">'.$i.'</td>
					<td align="left">'.$data[$n]['kode_ref'].'</td>
					<td align="left">'.$data[$n]['code_service'].'</td>
					<td align="left">'.$data[$n]['item_service'].'</td>
					<td align="left">'.$pc.'</td>
					<td align="left">'.$data[$n]['receive_from'].'</td>
					<td align="left">'.$data[$n]['currency'].'</td>
					<td align="right">'.$this->fungsi->pecah($data[$n]['price'],",").'</td>
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
