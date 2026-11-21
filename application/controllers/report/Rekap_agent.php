<?php
class Rekap_agent extends CI_Controller
{
	var $title ='Rekap Agent';
	public function __construct(){
		parent::__construct();
		$this->fungsi->restrict();
		$this->load->model (array('report/m_rekap_agent','report/m_rekap_sales'));
		$this->fungsi->dbx = $this->m_rekap_sales->dbx;
		$this->dbx = $this->m_rekap_sales->dbx;
	}

	function index(){
		$this->fungsi->auth(53,$this->title);
		$data['title'] = $this->title;
		$data['h1_title'] ='REKAP AGENT';
		$data['table_title'] ='REPORT';
		$data['menu_navigation'] ='navigation';
		$data['main_view'] ='report/template-report';
		$this->load->view('afg_template', $data);
	}
	
	function open_form() {
		$agent = $this->m_rekap_agent->get_agent();
		$option ['']='';
		foreach($agent as $row ){
				$option[$row->agen_penagihan]=$row->agen_penagihan;
			}
		
		$data['option']=$option;
 		$this->load->view('report/v_rekap_agent',$data);
	}


	function get_proses() {
 		$data = $this->m_rekap_agent->get_proses($_POST);
 		echo json_encode($data);
	}
	
	function to_excel() {
		$periode = $this->input->get('bulan');
		$agent = $this->m_rekap_agent->get_id_agent($this->input->get('agent'));
		
		header("Cache-Control: no-cache, no-store, must-revalidate");  
		header("Content-Type: application/vnd.ms-excel");  
		header("Content-Disposition: attachment; filename=LAPORAN PER AGENT.xls");
 		echo link_tag('public/theme/css/report.css', 'stylesheet', 'text/css').
		'<table summary="" width="100%" align="center">
		<tr><td class="space"></td><td class="judul" colspan="10">LAPORAN PER AGENT '. $this->m_rekap_agent->get_id_agent($this->input->get('agent')).'</td></tr>
	   <tr><td class="space"></td><td class="title" colspan="10">PERIODE : '.$this->fungsi->bulan2(substr($periode,0,2)).' '.substr($periode,3,4).'</td></tr>
		<tr>
			<td class="space"></td>
			<td align="center" width="30" class="header">NO</td>
			<td align="center" width="120" class="header">JOB NO #</td>
			<td align="center" width="80" class="header">DESTINATION</td>
			<td align="center" width="80" class="header">VOLUME</td>
			<td align="center" width="100" class="header">NOMINATION</td>
			<td align="center" width="100" class="header">FREEHAND</td>
			<td align="center" width="120" class="header">INCOME IDR</td>
			<td align="center" width="120" class="header">INCOME USD</td>
			<td align="center" width="120" class="header">TOTAL INCOME</td>
			<td align="center" width="120" class="header">COST IDR</td>
			<td align="center" width="120" class="header">COST USD</td>
			<td align="center" width="120" class="header">TOTAL COST</td>
			<td align="center" width="120" class="header">PROFIT/LOSE</td>
		</tr>
		<tr><td style="border:none; height: 5px" colspan="11"></td></tr>';
		
		//$data = $this->m_rekap_sales->get_query(array('tgl_awal'=>$tgl_awal,'tgl_akhir'=>$tgl_akhir,'sales'=>$sales_id))->result_array();
		$data = $this->m_rekap_agent->get_query($_GET)->result_array();
		
		$i=1;	
		$n=0;
		$sum=0;
		foreach($data as $row ) {
			$imp = substr($data[$n]['kode_ref'],4,3);
				if($imp=='300' || $imp=='301' || $imp=='302' ||$imp=='400' || $imp=='402' || $imp=='600') 
				{$destination=$data[$n]['origin'];}
				else
				{$destination=$data[$n]['destination'];}
			$totals = $this->m_rekap_agent->get_sales_type($data[$n]['kode_ref']);
			$total_sales_nominasi = $totals['total_sales_91'];
			$total_sales_freehand = $totals['total_sales_not_91'];
			echo 
			'<tr>
				<td class="space"></td>
				<td align="center">'.$i.'</td>
				<td>'.$data[$n]['kode_ref'].'</td>
				<td>'.$destination.'</td>
				<td>'.$total_sales_nominasi+$total_sales_freehand.'</td>
				<td align ="right">'.$total_sales_nominasi.'</td>
				<td align ="right">'.$total_sales_freehand.'</td>
				<td align ="right">'.$this->fungsi->pecah($this->m_rekap_agent->get_detail($data[$n]['kode_ref'],'income_rp'),',').'</td>
				<td align ="right">'.$this->fungsi->pecah($this->m_rekap_agent->get_detail($data[$n]['kode_ref'],'income_usd'),',').'</td>
				<td align ="right">'.$this->fungsi->pecah($this->m_rekap_agent->get_detail($data[$n]['kode_ref'],'total_income'),',').'</td>
				<td align ="right">'.$this->fungsi->pecah($this->m_rekap_agent->get_detail($data[$n]['kode_ref'],'cost_rp'),',').'</td>
				<td align ="right">'.$this->fungsi->pecah($this->m_rekap_agent->get_detail($data[$n]['kode_ref'],'cost_usd'),',').'</td>
				<td align ="right">'.$this->fungsi->pecah($this->m_rekap_agent->get_detail($data[$n]['kode_ref'],'total_cost'),',').'</td>
				<td align ="right">'.$this->fungsi->pecah($this->m_rekap_agent->get_detail($data[$n]['kode_ref'],'total_profit'),',').'</td>
			</tr>';
			$total_profit = $this->m_rekap_agent->get_detail($data[$n]['kode_ref'],'total_profit');
			$sum+= floatval($total_profit);
			$i++;$n++;
		}
		echo '<tr><td style="border:none; height: 5px" colspan="11"></td></tr>
               <tr>
			<td class="space"></td>
			<td align="center" width="1260" class="header" colspan="12">TOTAL</td>
			<td align="right" width="120" class="header">'.$this->fungsi->pecah($sum).'</td>
		</tr>';

		echo '</table>';
		exit();
	}
	
	function to_pdf(){
 		$data = $this->m_rekap_agent->get_query()->result_array();
 		$this->session->set_flashdata($_GET);
		$this->load->library('FPDF_MC_Table');
		$pdf=new FPDF_MC_Table();		
		$periode = $this->input->get('bulan');
		$agent = $this->m_rekap_agent->get_id_agent($this->input->get('agent'));
		if($periode) {
			$bulan = $this->fungsi->bulan2(substr($periode,0,2));
			$tahun = substr($periode,3,4); 
			$pdf->setDataHeader(array('report'=>'15','periode'=>$bulan.' '.$tahun,'agent'=>$agent));
		}
		$pdf->AddPage('L','A4');	
		$pdf->AliasNbPages();		
		$pdf->SetFont('Arial','',8);
		$pdf->SetWidths(array(8,27,35,15,15,15,25,20,25,25,20,25,24));
		$pdf->SetAligns(array('C','L','L','L','L','C','R','R','R','R','R','R','R'));
		$i=1;	
		$n=0;
		$sum = 0;
		foreach($data as $row ) {
				$imp = substr($data[$n]['kode_ref'],4,3);
				$totals = $this->m_rekap_agent->get_sales_type($data[$n]['kode_ref']);
				$total_sales_nominasi = $totals['total_sales_91'];
				$total_sales_freehand = $totals['total_sales_not_91'];
				if($imp=='300' || $imp=='301' || $imp=='302' ||$imp=='400' ||$imp=='402' || $imp=='600') 
				{$destination=$data[$n]['origin'];}
				else
				{$destination=$data[$n]['destination'];}
				$pdf->Row(array(
				$i,
				$data[$n]['kode_ref'],
				$destination,
				$total_sales_nominasi+$total_sales_freehand,
				$total_sales_nominasi,
				$total_sales_freehand,
				$this->fungsi->pecah($this->m_rekap_agent->get_detail($data[$n]['kode_ref'],'income_rp'),','),
				$this->fungsi->pecah($this->m_rekap_agent->get_detail($data[$n]['kode_ref'],'income_usd'),','),
				$this->fungsi->pecah($this->m_rekap_agent->get_detail($data[$n]['kode_ref'],'total_income'),','),
				$this->fungsi->pecah($this->m_rekap_agent->get_detail($data[$n]['kode_ref'],'cost_rp'),','),
				$this->fungsi->pecah($this->m_rekap_agent->get_detail($data[$n]['kode_ref'],'cost_usd'),','),
				$this->fungsi->pecah($this->m_rekap_agent->get_detail($data[$n]['kode_ref'],'total_cost'),','),
				$this->fungsi->pecah($this->m_rekap_agent->get_detail($data[$n]['kode_ref'],'total_profit'),','),
			));
			$total_profit = $this->m_rekap_agent->get_detail($data[$n]['kode_ref'],'total_profit');
			$sum+= floatval($total_profit);
			//$sumnom+= floatval($total_sales_nominasi);
			//$sumfree+= floatval($total_sales_freehand);
			$i++;$n++;
		}
		$pdf->Ln(1);
		$pdf->SetFont('Arial','B',10);
		$pdf->Cell(44,7,'VOLUME NOMINASI',1,'','C',false);
		$pdf->Cell(49,7,'',1,',','R',false);
		$pdf->Cell(44,7,'VOLUME FREEHAND',1,'','C',false);
		$pdf->Cell(49,7,'',1,',','R',false);
		$pdf->Cell(44,7,'TOTAL PROFIT',1,'','C',false);
		$pdf->Cell(49,7,$this->fungsi->pecah($sum),1,',','R',false);
		$pdf->Output('laporan_agent_'. time().'.pdf','I');
	}
}