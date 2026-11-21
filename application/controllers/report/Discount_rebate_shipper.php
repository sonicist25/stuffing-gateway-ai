<?php
class discount_rebate_shipper extends CI_Controller
{
	var $title ='Discount Rebate Shipper';
	public function __construct(){
		parent::__construct();
		$this->fungsi->restrict();
		$this->load->model('report/m_discount_rebate_shipper');
		$this->fungsi->dbx = $this->m_discount_rebate_shipper->dbx;		
		$this->dbx = $this->m_discount_rebate_shipper->dbx;
	}

	function index(){
		$this->fungsi->auth(52,$this->title);
		$data['title'] = $this->title;
		$data['menu_navigation'] ='navigation';
		$data['main_view'] ='report/template-report';
		$this->load->view('afg_template', $data);
	}
	
	function open_form() {	
		$level = $this->session->userdata ('level_id');
		$user_id = $this->session->userdata('user_id');
		if ($level == 0 || $level == 1){
		$shipper = $this->m_discount_rebate_shipper->get_commission_data_all($_POST)->result();
		}else{
		$shipper = $this->m_discount_rebate_shipper->get_commission_data($_POST)->result();
		}
		$option ['']='';
		if($shipper) {
			foreach($shipper as $row ){
				$option[$row->shipper]=$row->shipper;
			}
		}
		$data['option']=$option;
 		$this->load->view('report/v_discount_rebate_shipper',$data);
	}
	
	function get_commission_data() {
 		$level = $this->session->userdata ('level_id');
		$user_id = $this->session->userdata('user_id');
		if ($level == 0 || $level == 1){
		$shipper = $this->m_discount_rebate_shipper->get_commission_data_all($_POST)->result();
		//print_r($this->dbx->last_query());
		}else{
		$shipper = $this->m_discount_rebate_shipper->get_commission_data($_POST)->result();
		}
		$option ['']='';
		if($shipper) {
			foreach($shipper as $row ){
				$option[$row->shipper]=$row->shipper;
			}
		}
		$dropdown = form_dropdown('dropdown_shipper',$option,'','id="dropdown_shipper" style="min-width: 250px;max-width: 250px"');
 		//json_encode(array('dropdown'=>$shipper));
 		echo json_encode(array('dropdown'=>$dropdown));
	}
	
	function get_proses() {	
 		$data = $this->m_discount_rebate_shipper->get_proses($_POST);
 		echo json_encode($data);
	}
	
	function to_pdf() {
		
		$data = $this->m_discount_rebate_shipper->get_proses($_GET);
		
		$this->load->library('FPDF_MC_Table');
		$pdf=new FPDF_MC_Table();	
		$shipper = strtoupper($_GET['shipper']);
		$pdf->setDataHeader(array('report'=>'4','shipper'=>$shipper,'tgl_awal'=>$_GET['tgl_awal'],'tgl_akhir'=>$_GET['tgl_akhir']));	
		$pdf->AliasNbPages();
		$pdf->AddPage('P','A4');
		
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
			$i++;
			$n++;
		}
		
		$pdf->Output('Rebat_shipper_'. time().'.pdf','I');
	}
	
	function to_excel() {
		//$data = $this->m_discount_rebate_shipper->get_query()->result_array();
		
		$data = $this->m_discount_rebate_shipper->get_proses($_GET);
		
		$this->session->set_flashdata($_POST);
		$shipper = strtoupper($data[0]['shipper']);
		$tgl_awal = $this->session->flashdata('tgl_awal');
		$tgl_akhir = $this->session->flashdata('tgl_akhir');
		$time = time();
		header("Cache-Control: no-cache, no-store, must-revalidate");  
		header("Content-Type: application/vnd.ms-excel");  
		header("Content-Disposition: attachment; filename=DISCOUNT REBARE SHIPPER $time.xls");		
 		echo link_tag('public/theme/css/report.css', 'stylesheet', 'text/css').
		'<table summary="" width="100%" align="center">
		<tr><td class="space"></td><td class="judul" colspan="8">REKAP DISCOUNT / REBATE SHIPPER</td></tr>
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
	}
	
	function tes() {
		$foo1 = new stdClass();
		$foo2 = new stdClass();
		$foo3 = new stdClass();

		$foo1->one = "1";
		$foo2->two = "2";

		foreach ( $foo1 as $key => $value ) {
  			$foo3->$key = $value;
		}

		foreach ( $foo2 as $key => $value ) {
  			$foo3->$key = $value;
		}

		var_dump($foo3);
	}
}
