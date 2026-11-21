<?php
class Rekap_sales extends CI_Controller
{
	var $title ='Rekap Sales';
	public function __construct(){
		parent::__construct();
		$this->fungsi->restrict();
		$this->load->model('report/m_rekap_sales');
		$this->fungsi->dbx = $this->m_rekap_sales->dbx;
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
        $level = $this->session->userdata ('level_id');
		$sales = $this->m_rekap_sales->get_sales_data()->result();
		$option ['']='';
		if($level == 0 || $level == 1 || $level == 3) {
		foreach($sales as $row ){
			$option[$row->user_id]=$row->user_name;
			}
		}
        else
             {
             $option[]=$this->session->userdata('fullname');
             }
		$data['option']=$option;
 		$this->load->view('report/v_rekap_sales',$data);
	}


	function get_proses() {
 		$data = $this->m_rekap_sales->get_proses($_POST);
		//print_r($this->dbx->last_query());
 		echo json_encode($data);
	}
	
	function to_sales() {
		$reg_id = $this->session->userdata('region_id');	
		$sales_id = $this->fungsi->get_user_by_id($this->input->get('sales'));
		$sales = strtoupper($sales_id);
		$tgl_awal = $this->input->get('tgl_awal');
		$tgl_akhir = $this->input->get('tgl_akhir');
		
		header("Cache-Control: no-cache, no-store, must-revalidate");  
		header("Content-Type: application/vnd.ms-excel");  
		header("Content-Disposition: attachment; filename=LAPORAN PER SALESMAN $sales.xls");
 		echo link_tag('public/theme/css/report.css', 'stylesheet', 'text/css').
		'<table summary="" width="100%" align="center">
		<tr><td class="space"></td><td class="judul" colspan="10">LAPORAN PER SALESMAN</td></tr>
	   <tr><td class="space"></td><td class="title" colspan="10">TANGGAL : '.$tgl_awal.' S/D '.$tgl_akhir.'</td></tr>
	   <tr><td class="space"></td><td class="title" colspan="10">SALESMAN : '. $sales.'</td></tr>
		<tr>
			<td class="space"></td>
			<td align="center" width="30" class="header">NO</td>
			<td align="center" width="120" class="header">JOB NO</td>
			<td align="center" width="180" class="header">SHIPPER</td>
			<td align="center" width="100" class="header">CBM</td>
			<td align="center" width="100" class="header">20</td>
			<td align="center" width="100" class="header">40</td>
			<td align="center" width="100" class="header">HC</td>
			<td align="center" width="100" class="header">AF</td>
			<td align="center" width="100" class="header">TRUCKING</td>
			<td align="center" width="120" class="header">ORIGIN</td>
			<td align="center" width="120" class="header">DESTINATION</td>
			<td align="center" width="120" class="header">TERM</td>
			<td align="center" width="120" class="header">VESSEL</td>
			<td align="center" width="80" class="header">TANGGAL JOB</td>
			<td align="center" width="120" class="header">PROFIT</td>
			<td align="center" width="120" class="header">TYPE</td>
		</tr>
		<tr><td style="border:none; height: 5px" colspan="11"></td></tr>';
		
		$data = $this->m_rekap_sales->get_query($_GET)->result_array();
		
		$i = 1;	
		$ttl_meas = [];
		$ttl_profit = [];

		foreach($data as $row) {
			$code = $row['job_no'];
			$port_loading = $row['port_loading'];
			$destination = $row['spd_destination'];
			$shipper = $row['shipper'];
			
			$profit = (float)$this->m_rekap_sales->get_profit($code);
			$ttl_profit[] = $profit;
			
			$job = substr($code, 4, 3);
			
			// Hitung CBM (Hapus format Text Excel yang error, pakai number_format PHP)
			$meas_val = '';
			if($job != '200' && $job != '600' && $job != '301' && $job != '101' && $job != '700' && $job != '701' && $job != '702') {
				$meas_val = number_format((float)$row['meas'], 4, '.', '');
			}
			$ttl_meas[] = (float)$row['meas'];

			// Hitung Kontainer (Aman dari Null dan mencakup Job 100)
			$cont_size = $row['type_cont_size'] ?? '';
			$cont_qty  = $row['type_cont_qty'] ?? '';
			
			$duapuluh = '';
			$empatpuluh = '';
			$HC = '';
			
			if( $job == '101' || $job == '301') {
				if (strpos($cont_size, '20') !== false) {
					$duapuluh = $cont_qty;
				}
				if (strpos($cont_size, 'HC') !== false) {
					$HC = $cont_qty;
				} else if (strpos($cont_size, '40') !== false) {
					$empatpuluh = $cont_qty;
				}
			}

			// Hitung AF & Trucking
			$weight = '';
			if($job == '200' || $job == '600') {
				$weight = $row['weight'];
			}
			
			$truck = '';
			if($job == '700' || $job == '701' || $job == '702') {
				$truck = $this->fungsi->pecah4($row['meas']);
			}

			echo 
			'<tr>
				<td class="space"></td>
				<td align="center">'.$i.'</td>
				<td>'.$code.'</td>
				<td>'. strtoupper($shipper).'</td>
				<td align="left">'. $meas_val .'</td>
				<td align="center">'. $duapuluh .'</td>
				<td align="center">'. $empatpuluh .'</td>
				<td align="center">'. $HC .'</td>
				<td align="center">'. $weight .'</td>
				<td align="center">'. $truck .'</td>
				<td>'.$port_loading.'</td>
				<td>'.$destination.'</td>
				<td>'.$row['incoterms'].'</td>
				<td>'.$row['vessel'].' '.$row['voyage'].'</td>
				<td align="center">'.$this->fungsi->jin_date_str($row['tanggal_job']).'</td>
				<td align="right">'.$this->fungsi->pecah($profit, ',').'</td>
				<td>'.$row['type'].'</td>
			</tr>';
			$i++;
		}
		
		echo '<tr><td style="border:none; height: 5px" colspan="11"></td></tr>
               <tr>
			<td class="space"></td>
			<td align="center" width="330" class="header" colspan="3">TOTAL</td>
			<td align="left" width="100" class="header">'. number_format(array_sum($ttl_meas), 4, '.', '') .'</td>
			<td align="center" width="1060" class="header" colspan="10"></td>
			<td align="right" width="120" class="header">'.$this->fungsi->pecah(array_sum($ttl_profit)).'</td>
		</tr>';

		echo '</table>';
		exit();
	}
	
	function to_excel() {
		$reg_id = $this->session->userdata('region_id');	
		$sales_id = $this->fungsi->get_user_by_id($this->input->get('sales'));
		$sales = strtoupper($sales_id);
		$tgl_awal = $this->input->get('tgl_awal');
		$tgl_akhir = $this->input->get('tgl_akhir');
		
		header("Cache-Control: no-cache, no-store, must-revalidate");  
		header("Content-Type: application/vnd.ms-excel");  
		header("Content-Disposition: attachment; filename=LAPORAN SALESMAN $sales.xls");
 		echo link_tag('public/theme/css/report.css', 'stylesheet', 'text/css').
		'<table summary="" width="100%" align="center">
		<tr><td class="space"></td><td class="judul" colspan="10">LAPORAN PER SALESMAN</td></tr>
	   <tr><td class="space"></td><td class="title" colspan="10">TANGGAL : '.$tgl_awal.' S/D '.$tgl_akhir.'</td></tr>
	   <tr><td class="space"></td><td class="title" colspan="10">SALESMAN : '. $sales.'</td></tr>
		<tr>
			<td class="space"></td>
			<td align="center" width="30" class="header">NO</td>
			<td align="center" width="350" class="header">SHIPPER</td>
			<td align="center" width="120" class="header">NO JOB</td>
			<td align="center" width="150" class="header">DESTINATION</td>
			<td align="center" width="120" class="header"> MEAS(SF)<br>WEIGHT(AF)</td>
			<td align="center" width="120" class="header">INCOME</td>
			<td align="center" width="120" class="header">COST</td>
			<td align="center" width="120" class="header">PROFIT</td>
			<td align="center" width="120" class="header">PAYMENT</td>
		</tr>
		<tr><td style="border:none; height: 5px" colspan="10"></td></tr>';
		
		//$data = $this->m_rekap_sales->get_query(array('tgl_awal'=>$tgl_awal,'tgl_akhir'=>$tgl_akhir,'sales'=>$sales_id))->result_array();
		$data = $this->m_rekap_sales->get_query($_GET)->result_array();
		
		$i=1;	
		$n=0;
		foreach($data as $row ) {
			$code = $data[$n]['job_no'];
			$destination = $data[$n]['spd_destination'];
			if($destination=='') { $destination = $data[$n]['spd_destination']; };
			$des_id = $this->fungsi->get_country_id($destination);
			$shipper = $data[$n]['shipper'];
			$de_go = substr($data[$n]['desc_goods']??'',0,100);
			$desc = str_replace("\n"," ",$de_go??'');
			$income = $this->m_rekap_sales->get_income($code);
			$ttl_income[] = $income;
			$cost = $this->m_rekap_sales->get_cost($code);
			$ttl_cost[] = $cost;
			$profit = $this->m_rekap_sales->get_profit($code);
			$ttl_profit[] = $profit;
			$ttl_meas [] = $data[$n]['meas'];
			echo 
			'<tr>
				<td class="space"></td>
				<td align="center">'.$i.'</td>
				<td>'.strtoupper($shipper).'</td>
				<td align="center">'.$code.'</td>
				<td align="center">'.$destination.'</td>
				<td align="right">'.$this->fungsi->pecah4($data[$n]['meas']).'</td>
				<td align="right">'.$this->fungsi->pecah(($income),',').'</td>
				<td align="right">'.$this->fungsi->pecah(($cost),',').'</td>
				<td align="right">'. $this->fungsi->pecah(($profit),',').'</td>
				<td align="center"></td>
			</tr>';
			$i++;$n++;
		}
		echo '<tr><td style="border:none; height: 5px" colspan="10"></td></tr>
               <tr>
			<td class="space"></td>
			<td align="center" width="650" class="header" colspan="4">TOTAL</td>
			<td align="right" width="120" class="header">'.array_sum($ttl_meas).'</td>
			<td align="right" width="120" class="header">'.$this->fungsi->pecah(array_sum($ttl_income)).'</td>
			<td align="right" width="120" class="header">'.$this->fungsi->pecah(array_sum($ttl_cost)).'</td>
			<td align="right" width="120" class="header">'.$this->fungsi->pecah(array_sum($ttl_profit)).'</td>
			<td align="right" width="120" class="header"></td>
		</tr>';

		echo '</table>';
		exit();
	}
	
	function to_pdf(){
		
 		
		//$this->session->set_flashdata($_POST);
		
		$reg_id = $this->session->userdata('region_id');
		$this->load->library('FPDF_MC_Table');
		$pdf=new FPDF_MC_Table();	
		$sales_id = $this->fungsi->get_user_by_id($this->input->get('sales'));
		$sales = strtoupper($sales_id);
		$tgl_awal = $this->input->get('tgl_awal');
		$tgl_akhir = $this->input->get('tgl_akhir');
		$pdf->setDataHeader(array('report'=>'5','sales'=>$sales,'tgl_awal'=>$tgl_awal,'tgl_akhir'=>$tgl_akhir));
		$pdf->AddPage('L','Legal');	
		$pdf->AliasNbPages();
		$pdf->SetFont('Arial','',8);
		$pdf->SetWidths(array(10,35,25,25,35,30,100,30,15,30));
		$pdf->SetAligns(array('C','L','C','C','L','L','L','L','C','R'));
		
		
		$data = $this->m_rekap_sales->get_query($_GET)->result_array();
		
		$i=1;	
		$n=0;
		foreach($data as $row ) {
			$code = $data[$n]['job_no'];
			$destination = $data[$n]['spd_destination'];
			if($destination=='') { $destination = $data[$n]['spd_destination']; };
			//$des_id = $this->fungsi->get_country_id($destination);
			$shipper = $data[$n]['shipper'];
			$de_go = substr($data[$n]['desc_goods']??'',0,100);
			$desc = str_replace("\n"," ",$de_go);
			$profit = $this->m_rekap_sales->get_profit($code);
			$meas = $this->m_rekap_sales->get_meas($code);
			$bl = $this->m_rekap_sales->get_bl($code);
			$ttl_profit[] = $profit;
			$ttl_meas [] = $meas;
			$pdf->Row(array(
				$i,
				$code,
				$this->fungsi->jin_date_str($data[$n]['tanggal_job']),
				$bl,
				$data[$n]['vessel'].''.$data[$n]['voyage'],
				$destination,
				$desc,
				strtoupper($shipper??""),
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
		$pdf->Output('REKAP_SALES_'. time().'.pdf','I');
		//print_r($data);
	}
}


