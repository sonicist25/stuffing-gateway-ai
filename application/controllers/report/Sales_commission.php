<?php
class Sales_commission extends CI_Controller
{
	var $title ='Sales Comission';
	public function __construct(){
		parent::__construct();
		$this->fungsi->restrict();
		$this->load->model('report/sales_commission_model');
	}

	function index(){
		$this->fungsi->auth(51,'report Sales Comission');
		$data['title'] = $this->title;
		$data['menu_navigation'] ='navigation';
		$data['main_view'] ='report/template-report';
		$this->load->view('afg_template', $data);
	}
	
	function open_form() {	
		$shipper = $this->sales_commission_model->get_commission_data($_POST)->result();
		$option ['']='';
		if($shipper) {
			foreach($shipper as $row ){
				$option[$row->shipper]=$row->shipper;
			}
		}
		$data['option']=$option;
 		$this->load->view('report/v_sales_commission',$data);
	}
	
	function get_commission_data() {
 		$shipper = $this->sales_commission_model->get_commission_data($_POST)->result();
		$option ['']='';
		if($shipper) {
			foreach($shipper as $row ){
				$option[$row->shipper]=$row->shipper;
			}
		}
		$dropdown = form_dropdown('dropdown_shipper',$option,'','id="dropdown_shipper" style="min-width: 200px"');
 		echo json_encode(array('dropdown'=>$dropdown));
	}
	
	function get_proses() {	
 		$data = $this->sales_commission_model->get_proses($_POST);
 		echo json_encode($data);
	}
	
	function to_pdf(){
		$data = $this->sales_commission_model->get_query()->result_array();
		$this->session->set_flashdata($_POST);
		$this->load->library('FPDF_MC_Table');
		$pdf=new FPDF_MC_Table();	
		$shipper = strtoupper($data[0]['shipper']);
		$tgl_awal = $this->session->flashdata('tgl_awal');
		$tgl_akhir = $this->session->flashdata('tgl_akhir');
		$pdf->setDataHeader(array('report'=>'3','shipper'=>$shipper,'tgl_awal'=>$tgl_awal,'tgl_akhir'=>$tgl_akhir));
		$pdf->AddPage('P','A4');	
		$pdf->AliasNbPages();
		$pdf->SetFont('Arial','',8);
		$pdf->SetWidths(array(10,35,35,15,15,30,20,30));
		$pdf->SetAligns(array('C','L','L','C','C','R','C','R'));
		$i=1;	
		$n=0;
		foreach($data as $row ) {
			$qty = $data[$n]['qty']; if($qty=='') {$qty = $data[$n]['qty_detail'];}
			if($data[$n]['mchgs'] > $qty) { $com = $data[$n]['mchgs'] * $data[$n]['unit_price']; } else { $com = $qty * $data[$n]['unit_price']; }
			$pdf->Row(array(
				$i,
				$data[$n]['kode_ref'],
				$data[$n]['destination'],
				$qty,
				$data[$n]['mchgs'],
				$data[$n]['unit_price'],
				$data[$n]['currency'],
				$com,
			));
			$i++;$n++;
		}
		
		$pdf->Output('SALES_COMMISSION_'. time().'.pdf','I');
		//	//print_r($data);
	}
	
	function to_excel() {
		$data = $this->sales_commission_model->get_query()->result_array();
		$this->session->set_flashdata($_POST);
		$shipper = strtoupper($data[0]['shipper']);
		$tgl_awal = $this->session->flashdata('tgl_awal');
		$tgl_akhir = $this->session->flashdata('tgl_akhir');
		$time = time();
		header("Cache-Control: no-cache, no-store, must-revalidate");  
		header("Content-Type: application/vnd.ms-excel");  
		header("Content-Disposition: attachment; filename=SALES_COMMISSION_$time.xls");		
 		echo link_tag('public/theme/css/report.css', 'stylesheet', 'text/css').
		'<table summary="" width="100%" align="center">
		<tr><td class="space"></td><td class="judul" colspan="8">REKAP SALES COMMISSION</td></tr>
	   <tr><td class="space"></td><td class="title" colspan="8">TANGGAL : '.$tgl_awal.' S/D '.$tgl_akhir.'</td></tr>
	   <tr><td class="space"></td><td class="title" colspan="8">SHIPPER : '. strtoupper($data[0]['shipper']).'</td></tr>
		<tr>
			<td class="space"></td>
			<td align="center" width="30" class="header">NO</td>
			<td align="center" width="120" class="header">JOB NO</td>
			<td align="center" width="170" class="header">DESTINATION</td>
			<td align="center" width="50" class="header">QTY</td>
			<td align="center" width="50" class="header">MCHG</td>
			<td align="center" width="120" class="header">PRICE</td>
			<td align="center" width="70" class="header">CURRENCY</td>
			<td align="center" width="120" class="header">COMMISSION</td>
		</tr>
		<tr><td style="border:none; height: 5px" colspan="8"></td></tr>';
		$i=1;	
		$n=0;
		foreach($data as $row ) {
			$qty = $data[$n]['qty']; if($qty=='') {$qty = $data[$n]['qty_detail'];}
			if($data[$n]['mchgs'] > $qty) { $com = $data[$n]['mchgs'] * $data[$n]['unit_price']; } else { $com = $qty * $data[$n]['unit_price']; }
				echo '<tr>
				<td class="space"></td>
				<td>'.$i.'</td>
				<td>'.$data[$n]['kode_ref'].'</td>
				<td>'.$data[$n]['destination'].'</td>
				<td align="center">'.$qty.'</td>
				<td align="center">'.$data[$n]['mchgs'].'</td>
				<td>'.$data[$n]['unit_price'].'</td>
				<td align="center">'.$data[$n]['currency'].'</td>
				<td>'.$com.'</td>
				</tr>';	
			$i++;$n++;
		}
		
		exit();
		/*
		$this->session->set_flashdata($_POST);
		$data = $this->session->flashdata('rows');
		echo //print_r($data);
		*/
	}
}
