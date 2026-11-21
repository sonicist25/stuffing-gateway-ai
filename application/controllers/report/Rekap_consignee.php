<?php
class Rekap_consignee extends CI_Controller
{
	var $title ='Rekap Consignee';
	public function __construct(){
		parent::__construct();
		$this->fungsi->restrict();
		$this->load->model (array('report/m_rekap_consignee','report/m_rekap_sales'));
	}

	function index(){
		$this->fungsi->auth(53,$this->title);
		$data['title'] = $this->title;
		$data['h1_title'] ='INVOICE TO SHIPPER';
		$data['table_title'] ='INVOICE';
		$data['menu_navigation'] ='navigation';
		$data['main_view'] ='report/template-report';
		$this->load->view('afg_template', $data);
	}
	
	function open_form() {
		$consignee = $this->m_rekap_consignee->get_consignee();
		$option ['']='';
		foreach($consignee as $row ){
				$option[$row->consignee_import_name]=$row->consignee_import_name;
			}
		
		$data['option']=$option;
 		$this->load->view('report/v_rekap_consignee',$data);
	}


	function get_proses() {
 		$data = $this->m_rekap_consignee->get_proses($_POST);
 		echo json_encode($data);
	}
	
	function to_excel() {
		$reg_id = $this->session->userdata('region_id');	
		$sales_id = $this->fungsi->get_user_by_id($this->input->get('sales'));
		$sales = strtoupper($sales_id);
		$tgl_awal = $this->input->get('tgl_awal');
		$tgl_akhir = $this->input->get('tgl_akhir');
		
		header("Cache-Control: no-cache, no-store, must-revalidate");  
		header("Content-Type: application/vnd.ms-excel");  
		header("Content-Disposition: attachment; filename=LAPORAN PER SALESMAN.xls");
 		echo link_tag('public/theme/css/report.css', 'stylesheet', 'text/css').
		'<table summary="" width="100%" align="center">
		<tr><td class="space"></td><td class="judul" colspan="10">LAPORAN PER SALESMAN</td></tr>
	   <tr><td class="space"></td><td class="title" colspan="10">TANGGAL : '.$tgl_awal.' S/D '.$tgl_akhir.'</td></tr>
	   <tr><td class="space"></td><td class="title" colspan="10">SALESMAN : '. $sales.'</td></tr>
		<tr>
			<td class="space"></td>
			<td align="center" width="30" class="header">NO</td>
			<td align="center" width="120" class="header">JOB NO</td>
			<td align="center" width="80" class="header">TGL JOB</td>
			<td align="center" width="100" class="header">BL NO</td>
			<td align="center" width="100" class="header">VESEL</td>
			<td align="center" width="120" class="header">DESTINATION</td>
			<td align="center" width="350" class="header">DESCRIPTION OF GOOD</td>
			<td align="center" width="120" class="header">SHIPPER</td>
			<td align="center" width="80" class="header">MEAS</td>
			<td align="center" width="120" class="header">PROFIT</td>
		</tr>
		<tr><td style="border:none; height: 5px" colspan="11"></td></tr>';
		
		//$data = $this->m_rekap_sales->get_query(array('tgl_awal'=>$tgl_awal,'tgl_akhir'=>$tgl_akhir,'sales'=>$sales_id))->result_array();
		$data = $this->m_rekap_sales->get_query($_GET)->result_array();
		
		$i=1;	
		$n=0;
		foreach($data as $row ) {
			$code = $data[$n]['job_no'];
			$destination = $data[$n]['spd_destination'];
			if($destination=='') { $destination = $data[$n]['spd_destination']; };
			$des_id = $this->fungsi->get_country_id($destination);
			if(substr($code,4,3)=='300'||substr($code,4,3)=='301'||substr($code,4,3)=='302'||substr($code,4,3)=='400'||substr($code,4,3)=='402'||substr($code,4,3)=='600') 
			{ $shipper = $data[$n]['shipper_imp'];}
			else {$shipper = $data[$n]['shipper'];}
			$de_go = substr($data[$n]['desc_goods'],0,100);
			$desc = str_replace("\n"," ",$de_go);
			$profit = $this->m_rekap_sales->get_profit($code);
			$ttl_profit[] = $profit;
			$ttl_meas [] = $data[$n]['meas'];
			echo 
			'<tr>
				<td class="space"></td>
				<td align="center">'.$i.'</td>
				<td>'.$code.'</td>
				<td align="center">'.$this->fungsi->jin_date_str($data[$n]['tgl_job']).'</td>
				<td align="center">'.substr($reg_id, 0,1).$des_id.'-GCL'. substr($code, 11,6).'</td>
				<td>'.$data[$n]['vessel'].''.$data[$n]['voyage'].'</td>
				<td>'.$destination.'</td>
				<td>'.$desc.'</td>
				<td>'. strtoupper($shipper).'</td>
				<td align="center">'.$this->fungsi->pecah4($data[$n]['meas']).'</td>
			<td align="right">'.$this->fungsi->pecah(($profit),',').'</td>
			</tr>';
			$i++;$n++;
		}
		echo '<tr><td style="border:none; height: 5px" colspan="11"></td></tr>
               <tr>
			<td class="space"></td>
			<td align="center" width="1020" class="header" colspan="8">TOTAL</td>
			<td align="center" width="80" class="header">'.array_sum($ttl_meas).'</td>
			<td align="right" width="120" class="header">'.$this->fungsi->pecah(array_sum($ttl_profit)).'</td>
		</tr>';

		echo '</table>';
		exit();
	}
	
	function to_pdf(){
		
 		
		//$this->session->set_flashdata($_POST);
		
		$reg_id = $this->session->userdata('region_id');
		$this->load->library('FPDF_MC_Table');
		$pdf=new FPDF_MC_Table();	
		$tgl_awal = $this->input->get('tgl_awal');
		$tgl_akhir = $this->input->get('tgl_akhir');
		$consignee= $this->input->get('consignee');	
		$pdf->setDataHeader(array('report'=>'13','consignee'=>$consignee,'tgl_awal'=>$tgl_awal,'tgl_akhir'=>$tgl_akhir));
		$pdf->AddPage('L','Legal');	
		$pdf->AliasNbPages();
		$pdf->SetFont('Arial','',8);
		$pdf->SetWidths(array(10,35,25,25,35,30,100,30,15,30));
		$pdf->SetAligns(array('C','L','C','C','L','L','L','L','C','R'));
		
		//$data = $this->m_rekap_sales->get_query(array('tgl_awal'=>$tgl_awal,'tgl_akhir'=>$tgl_akhir,'sales'=>$this->input->get('sales')))->result_array();
		$data = $this->m_rekap_consignee->get_query()->result_array();
		
		$i=1;	
		$n=0;
		foreach($data as $row ) {
			$code = $data[$n]['job_no'];
			$destination = $data[$n]['spd_destination'];
			if($destination=='') { $destination = $data[$n]['spd_destination']; };
			$des_id = $this->fungsi->get_country_id($destination);
			if(substr($code,4,3)=='300'||substr($code,4,3)=='301'||substr($code,4,3)=='302'||substr($code,4,3)=='400'||substr($code,4,3)=='402'||substr($code,4,3)=='600') 
			{ $shipper = $data[$n]['shipper_imp'];}
			else {$shipper = $data[$n]['shipper'];}
			$de_go = substr($data[$n]['desc_goods'],0,100);
			$desc = str_replace("\n"," ",$de_go);
			$profit = $this->m_rekap_sales->get_profit($code);
			$ttl_profit[] = $profit;
			$ttl_meas [] = $data[$n]['meas'];
			$pdf->Row(array(
				$i,
				$code,
				$this->fungsi->jin_date_str($data[$n]['tgl_job']),
				substr($reg_id, 0,1).$des_id.'-GCL'. substr($code, 11,6),
				$data[$n]['vessel'].''.$data[$n]['voyage'],
				$destination,
				$desc,
				strtoupper($shipper),
				$data[$n]['meas'],
				$this->fungsi->pecah($this->m_rekap_sales->get_profit($code),' '),
			));
			$i++;$n++;
		}
		$pdf->Ln(1);
		$pdf->SetFont('Arial','B',10);
		$pdf->Cell(290,7,'TOTAL',1,'','C',false);
		$pdf->Cell(15,7,array_sum($ttl_meas),1,'','C',false);
		$pdf->Cell(30,7,$this->fungsi->pecah(array_sum($ttl_profit)),1,'','R',false);
		$pdf->Output('REKAP_CONSIGNEE_'. time().'.pdf','I');
	//	//print_r($data);
	exit;
	}
}