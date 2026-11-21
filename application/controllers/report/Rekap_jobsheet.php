<?php
class Rekap_jobsheet extends CI_Controller
{
	var $title ='Rekap Jobsheet';
	public function __construct(){
		parent::__construct();
		$this->fungsi->restrict();
		$this->load->model('report/m_rekap_jobsheet');
	}

	function index(){
		$this->fungsi->auth(55,$this->title);
		$data['title'] = $this->title;
		$data['menu_navigation'] ='navigation';
		$data['main_view'] ='report/template-report';
		$this->load->view('afg_template', $data);
	}
	
	function open_form() {	
 		$this->load->view('report/v_rekap_jobsheet');
	}
	
	function get_proses() {	
 		$data = $this->m_rekap_jobsheet->get_proses($_POST);
 		echo json_encode($data);
	}
	
	function to_excel() {	
		$data = $this->m_rekap_jobsheet->get_query()->result_array();
 		$this->session->set_flashdata($_POST);
		$per= $this->session->flashdata('bulan');
		$bln = substr($per,4,2);
    	$thn = substr($per,0,4);
    	$periode = $this->fungsi->bulan3($bln).' '.$thn;
		$time= time();
		header("Cache-Control: no-cache, no-store, must-revalidate");  
		header("Content-Type: application/vnd.ms-excel");  
		header("Content-Disposition: attachment; filename=REKAP_JOBSHEET_$time.xls");		
 		echo link_tag('public/theme/css/report.css', 'stylesheet', 'text/css').
		'<table summary="" width="100%" align="center">
		<tr><td class="space"></td><td class="judul" colspan="10">REKAP JOBSHEET PERIODE :'.$per.'</td></tr>
		<tr>
			<td class="space"></td>
			<td align="center" width="30" class="header">NO</td>
			<td align="center" width="120" class="header">JOB NO #</td>
			<td align="center" width="120" class="header">DESTINATION</td>
			<td align="center" width="280" class="header">CUSTOMER</td>
			<td align="center" width="100" class="header">INCOME</td>
			<td align="center" width="100" class="header">COST</td>
			<td align="center" width="120" class="header">PROFIT / LOSE</td>
		</tr>
		<tr><td style="border:none; height: 5px" colspan="11"></td></tr>';
		$i=1;	
		$n=0;
		foreach($data as $row ) {
		$total_income [] = $this->m_rekap_jobsheet->get_detail($data[$n]['job_no'],'income');
		$total_cost [] = $this->m_rekap_jobsheet->get_detail($data[$n]['job_no'],'cost');
		$total_profit [] = $this->m_rekap_jobsheet->get_detail($data[$n]['job_no'],'profit');
		echo 
			'<tr>
				<td class="space"></td>
				<td align="center">'.$i.'</td>
				<td>'.$data[$n]['job_no'].'</td>
				<td>'.$data[$n]['destination'].'</td>
				<td>'.$data[$n]['shipper'].'</td>
				<td align="right">'.$this->fungsi->pecah($this->m_rekap_jobsheet->get_detail($data[$n]['job_no'],'income'),',').'</td>
				<td align="right">'.$this->fungsi->pecah($this->m_rekap_jobsheet->get_detail($data[$n]['job_no'],'cost'),',').'</td>
				<td align="right">'.$this->fungsi->pecah($this->m_rekap_jobsheet->get_detail($data[$n]['job_no'],'profit'),',').'</td>
			</tr>';
			$i++;$n++;	
		}
		 echo '<tr><td style="border:none; height: 5px" colspan="8"></td></tr>
            <tr>
			<td class="space"></td>
			<td align="center" width="550" class="header" colspan="4">TOTAL JOB</td>
			<td align="right" width="120" class="header">'.$this->fungsi->pecah(array_sum($total_income)).'</td>
			<td align="right" width="120" class="header">'.$this->fungsi->pecah(array_sum($total_cost)).'</td>
			<td align="right" width="120" class="header">'.$this->fungsi->pecah(array_sum($total_profit)).'</td>
		</tr>';
		echo '</table>';
		exit();
	}
	
	function to_pdf(){
 		$data = $this->m_rekap_jobsheet->get_query()->result_array();
 		$this->session->set_flashdata($_POST);
		$this->load->library('FPDF_MC_Table');
		$pdf=new FPDF_MC_Table();		
		
		$periode = $this->session->flashdata('bulan');
		if($periode==true) {
			$bulan = $this->fungsi->bulan2(substr($periode,0,2));
			$tahun = substr($periode,3,4); 
			$pdf->setDataHeader(array('report'=>'7','periode'=>$bulan.' '.$tahun,));
		}
		$pdf->AddPage('P','A4');	
		$pdf->AliasNbPages();		
		$pdf->SetFont('Arial','',8);
		$pdf->SetWidths(array(10,32,68,25,25,25));
		$pdf->SetAligns(array('C','L','L','R','R','R'));
		$i=1;	
		$n=0;
		foreach($data as $row ) {
			$pdf->Row(array(
				$i,
				$data[$n]['job_no'],
				$data[$n]['shipper'],
				$this->fungsi->pecah($this->m_rekap_jobsheet->get_detail($data[$n]['job_no'],'income'),','),
				$this->fungsi->pecah($this->m_rekap_jobsheet->get_detail($data[$n]['job_no'],'cost'),','),
				$this->fungsi->pecah($this->m_rekap_jobsheet->get_detail($data[$n]['job_no'],'profit'),','),
			));
			$i++;$n++;
		}
		
		$pdf->Output('REKAP_JOBSHEET_'. time().'.pdf','I');
	}


















}
