<?php
class Rekap_shipper extends CI_Controller
{
	var $title ='Rekap Shipper';
	public function __construct(){
		parent::__construct();
		$this->fungsi->restrict();
		$this->load->model(array('report/m_rekap_shipper','report/m_rekap_sales'));
		$this->fungsi->dbx = $this->m_rekap_shipper->dbx;
	}

	function index(){
		$this->fungsi->auth(54,$this->title);
		$data['title'] = $this->title;
		$data['menu_navigation'] ='navigation';
		$data['main_view'] ='report/template-report';
		$this->load->view('afg_template', $data);
	}
	
	function open_form() {	
		$sales = $this->m_rekap_shipper->get_sales_data()->result();
		$option ['']='';
		if($sales) {
			foreach($sales as $row ){
				$option[$row->user_id]=$row->user_name;
			}
		}
		$data['option']=$option;
 		$this->load->view('report/v_rekap_shipper',$data);
	}
	
	function get_shipper() {	
		$shipper = $this->m_rekap_shipper->get_shipper($_POST);
		////print_r($this->dbx->last_query());
		//die();
		$option ['']='';
		if($shipper) {
			foreach($shipper as $row ){
				$option[$row->shipper]=$row->shipper_print;
			}
		}
		$dropdown = form_dropdown('dropdown_shipper',$option,'','id="dropdown_shipper" style="min-width: 250px" onchange="get_shipper_data()"');
 		echo json_encode(array('dropdown'=>$dropdown));
	}
	
	function get_shipper_data() {	
		$data = $this->m_rekap_shipper->get_shipper_data($_POST);
 		echo json_encode($data);
	}
	
	function get_proses() {	
 		$data = $this->m_rekap_shipper->get_proses($_POST);
		////print_r($this->dbx->last_query());
		//die();
 		echo json_encode($data);
	}
	
	function to_excel() {	
		$reg_id = $this->session->userdata('region_id');
		$data = $this->m_rekap_shipper->get_query($_GET)->result_array();
		$sales_id = $this->m_rekap_shipper->get_shipper_by_id($data[0]['shipper']);
		$sales = strtoupper($sales_id);
		$tgl_awal = $this->input->get('tgl_awal');
		$tgl_akhir = $this->input->get('tgl_akhir');
		$time = time();
		
		header("Cache-Control: no-cache, no-store, must-revalidate");  
		header("Content-Type: application/vnd.ms-excel");  
		header("Content-Disposition: attachment; filename=REKAP_SHIPPER_$time.xls");
 		echo link_tag('public/theme/css/report.css', 'stylesheet', 'text/css').
		'<table summary="" width="100%" align="center">
		<tr><td class="space"></td><td class="judul" colspan="10">LAPORAN PER SHIPPER</td></tr>
	   <tr><td class="space"></td><td class="title" colspan="10">TANGGAL : '.$tgl_awal.' S/D '.$tgl_akhir.'</td></tr>
	   <tr><td class="space"></td><td class="title" colspan="10">EXPORTIR : '. $data[0]['shipper'] .'</td></tr>
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
		$i=1;	
		$n=0;
		foreach($data as $row ) {
			$code = $data[$n]['job_no'];
			$destination = $data[$n]['spd_destination'];
			if($destination=='') { $destination = $data[$n]['spd_destination']; };
			$des_id = $this->fungsi->get_country_id($destination);
			if(substr($code,4,3)=='300'||substr($code,4,3)=='301'||substr($code,4,3)=='302'||substr($code,4,3)=='400'||substr($code,4,3)=='402'||substr($code,4,3)=='600') 
			{ $shipper = $data[$n]['shipper_imp'];}
			else
			{$shipper = $data[$n]['shipper'];}
			$de_go = substr($data[$n]['desc_goods']??'',0,100);
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
				<td align="center">'.$data[$n]['hb_l'].'</td>
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
		$reg_id = $this->session->userdata('region_id');
		$this->load->library('FPDF_MC_Table');
		$pdf=new FPDF_MC_Table();	
		$sales_id = $this->m_rekap_shipper->get_shipper_by_id($this->input->get('shipper'));
		$sales = strtoupper($sales_id);
		$tgl_awal = $this->input->get('tgl_awal');
		$tgl_akhir = $this->input->get('tgl_akhir');
		$pdf->setDataHeader(array('report'=>'6','sales_id'=>$sales_id,'sales'=>$sales,'tgl_awal'=>$tgl_awal,'tgl_akhir'=>$tgl_akhir));
		$pdf->AddPage('L','Legal');	
		$pdf->AliasNbPages();		
		$pdf->SetFont('Arial','',8);
		$pdf->SetWidths(array(10,35,25,25,35,30,100,30,15,30));
		$pdf->SetAligns(array('C','L','C','C','L','L','L','L','C','R'));
		
		$data = $this->m_rekap_shipper->get_query($_GET)->result_array();
		////print_r($this->dbx->last_query());
		
		$i=1;	
		$n=0;
		foreach($data as $row ) {
			
			$code = $data[$n]['job_no'];
			$destination = $data[$n]['spd_destination'];
			if($destination=='') { $destination = $data[$n]['spd_destination']; };
			$des_id = $this->fungsi->get_country_id($destination);
			$shipper = $data[$n]['shipper'];
			//if(substr($code,0,5)=='GCL-3') { $shipper = $data[$n]['shipper_imp'];}
			$de_go = substr($data[$n]['desc_goods']??'',0,100);
			$desc = str_replace("\n"," ",$de_go);
			$profit = $this->m_rekap_sales->get_profit($code);
			$ttl_profit[] = $profit;
			$ttl_meas [] = $data[$n]['meas'];
			$pdf->Row(array(
				$i,
				$code,
				$this->fungsi->jin_date_str($data[$n]['tgl_job']),
				$data[$n]['hb_l'],
				$data[$n]['vessel'].''.$data[$n]['voyage'],
				$destination,
				$desc,
				strtoupper($shipper),
				$data[$n]['meas'],
				$this->fungsi->pecah(($profit),','),
			));
			$i++;$n++;
		}
      $pdf->Ln(1);
		$pdf->SetFont('Arial','B',10);
		$pdf->Cell(290,7,'TOTAL',1,'','C',false);
		$pdf->Cell(15,7,array_sum($ttl_meas),1,'','C',false);
		$pdf->Cell(30,7,$this->fungsi->pecah(array_sum($ttl_profit)),1,'','R',false);
		$pdf->Output('REKAP_SHIPPER'. time().'.pdf','I');

	}
}
?>