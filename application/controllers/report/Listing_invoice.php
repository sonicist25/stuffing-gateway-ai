<?php
class Listing_invoice extends CI_Controller
{
	var $title ='Listing Invoice';
	public function __construct(){
		parent::__construct();
		$this->fungsi->restrict();
		$this->load->model('report/m_listing_invoice');
	}

	function index(){
		//$this->fungsi->auth(57,'Analisa Job');
		$data['title'] = $this->title;
		$data['menu_navigation'] ='navigation';
		$data['main_view'] ='report/template-report';
		$this->load->view('afg_template', $data);
	}
	
	function open_form() {	
 		$this->load->view('report/v_listing_inv_view');
	}

	function get_proses() {	
 		$data = $this->m_listing_invoice->get_proses($_POST);
 		echo json_encode($data);
	}
	
	function to_pdf(){
		
 		$data = $this->m_listing_invoice->get_q2(); 		
 		//print_r($data);
 		
 		$this->session->set_flashdata($_POST);
		$this->load->library('FPDF_MC_Table');
		$pdf=new FPDF_MC_Table();	
		
		$periode = $this->session->flashdata('bulan');
		if($periode==true) {
			$bulan = $this->fungsi->bulan2(substr($periode,0,2));
			$tahun = substr($periode,3,4); 
		$pdf->setDataHeader(array('report'=>'inv_total','periode'=>$bulan.' '.$tahun,));
		}else{
			$tahun = $this->session->userdata('db');  
		$pdf->setDataHeader(array('report'=>'inv_total','periode'=>$tahun,));
		}
		$pdf->AddPage('P','A4');	
		$pdf->AliasNbPages();
		$pdf->SetFont('Arial','',8);
		$pdf->SetWidths(array(10,30,17,21,17,13,9,58,20));
		$pdf->SetAligns(array('C','L','L','L','C','L','R','L','C'));
		$i=1;
		$n=0;

		foreach($data as $row ) {
			// Split the string into an array of dates
			$dates = explode(',', $data[$n]['date']??'');

			// Initialize an array to store formatted dates
			$formatted_dates = [];

			// Loop through each date and format it
				foreach ($dates as $date) {
				// Convert the date string to DateTime object
				$date_obj = DateTime::createFromFormat('Ymd', $date);
				if ($date_obj !== false) {
				// Format the date as DD/MM/YYYY
				$formatted_date = $date_obj->format('d/m/Y');
        
				// Store the formatted date
				$formatted_dates[] = $formatted_date;
				} else {
				// Handle invalid date format
				$formatted_dates[] = 'Belum dibuat';
				}
			}
			if(substr($data[$n]['kode_ref'],4,3)=="300" || substr($data[$n]['kode_ref'],4,3)=="301" ||substr($data[$n]['kode_ref'],4,3)=="302" ||substr($data[$n]['kode_ref'],4,3)=="400" ||substr($data[$n]['kode_ref'],4,3)=="600" || substr($data[$n]['kode_ref'],4,3)=="800"){
			$etd = $data[$n]['eta'];
			}else{
			$etd = $data[$n]['etd'];
			}
			$a = implode("\n", $formatted_dates);
			$parts = explode(',', $data[$n]['number']??'');
			$output_string = implode("\n", $parts);
			$paid = explode(',', $data[$n]['status']??'');
			$output_paid = implode("\n", $paid);
			$aging = explode(',', $data[$n]['aging']??'');
			$output_aging = implode("\n", $aging);
			$date_tanggal2 = DateTime::createFromFormat('Ymd', $etd);
			$atd = $date_tanggal2->format('d/m/Y'); 
			$complete = $this->m_listing_invoice->get_billed($data[$n]['kode_ref']);
			if($complete == 'billed'){
				$billed = "Complete";
			}else{
				$billed = "Incomplete";	
			}
			$pdf->Row(array(
				$i,
				$data[$n]['kode_ref'],
				$atd,
				$output_string,
				$a,
				$output_paid,
				$output_aging,
				substr($data[$n]['receive_from']??'',0,30),
				$billed,
				//$this->fungsi->pecah($data[$n]['price'],","),
				//$data[$n]['price'],
			));
			$i++;
			$n++;
		}
		$pdf->Output('LISTING_INVOICE_PERBULAN'. time().'.pdf','I');
	}

	function to_excel() {
		$page = 'TABEL TOTAL INVOICE';
		$data = $this->m_listing_invoice->get_q2();
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
		<tr><td class="space"></td><td class="judul" colspan="11">TABEL TOTAL INVOICE</td></tr>
	   <tr><td class="space"></td><td class="title" colspan="11">Periode : '.$range.'</td></tr>
		<tr>
			<td class="space"></td>
			<td align="center" width="30" class="header">NO</td>
			<td align="center" width="120" class="header">JOB NO #</td>
			<td align="center" width="80" class="header">ETD/ETA</td>
			<td align="center" width="100" class="header">INVOICE NO</td>
			<td align="center" width="80" class="header">INVOICE DATE</td>
			<td align="center" width="50" class="header">PAYMENT</td>
			<td align="center" width="30" class="header">AGING</td>
			<td align="center" width="180" class="header">BILLED TO</td>
			<td align="center" width="70" class="header">STATUS</td>
		</tr>
		<tr><td style="border:none; height: 5px" colspan="11"></td></tr>';
		if($data) {
			$i=1;	
			$n=0;
	
		foreach($data as $row ) {
			// Split the string into an array of dates
			$dates = explode(',', $data[$n]['date']??'');

			// Initialize an array to store formatted dates
			$formatted_dates = [];

			// Loop through each date and format it
				foreach ($dates as $date) {
				// Convert the date string to DateTime object
				$date_obj = DateTime::createFromFormat('Ymd', $date);
				if ($date_obj !== false) {
				// Format the date as DD/MM/YYYY
				$formatted_date = $date_obj->format('d/m/Y');
        
				// Store the formatted date
				$formatted_dates[] = $formatted_date;
				} else {
				// Handle invalid date format
				$formatted_dates[] = 'Belum dibuat';
				}
			}
			if(substr($data[$n]['kode_ref'],4,3)=="300" || substr($data[$n]['kode_ref'],4,3)=="301" ||substr($data[$n]['kode_ref'],4,3)=="302" ||substr($data[$n]['kode_ref'],4,3)=="400" ||substr($data[$n]['kode_ref'],4,3)=="600" || substr($data[$n]['kode_ref'],4,3)=="800"){
			$etd = $data[$n]['eta'];
			}else{
			$etd = $data[$n]['etd'];
			}
			$a = implode("\n", $formatted_dates);
			$parts = explode(',', $data[$n]['number']??'');
			$output_string = implode("\n", $parts);
			$paid = explode(',', $data[$n]['status']??'');
			$output_paid = implode("\n", $paid);
			$aging = explode(',', $data[$n]['aging']??'');
			$output_aging = implode("\n", $aging);
			$date_tanggal2 = DateTime::createFromFormat('Ymd', $etd);
			$atd = $date_tanggal2->format('d/m/Y'); 
			$complete = $this->m_listing_invoice->get_billed($data[$n]['kode_ref']);
			if($complete == 'billed'){
				$billed = "Complete";
			}else{
				$billed = "Incomplete";	
			}				
				echo 
				'<tr>
					<td class="space"></td>
					<td align="center">'.$i.'</td>
					<td align="left">'.$data[$n]['kode_ref'].'</td>
					<td align="left">'.$atd.'</td>
					<td align="left">'.$output_string.'</td>
					<td align="left">'.$a.'</td>
					<td align="left">'.$output_paid.'</td>
					<td align="left">'.$output_aging.'</td>
					<td align="left">'.$data[$n]['receive_from'].'</td>
					<td align="center">'.$billed.'</td>
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
