<?php
class Job_sheet extends CI_Controller
{
	var $title ='Job Sheet';
	public function __construct(){
		parent::__construct();
		$this->fungsi->restrict();
		$this->load->model('report/job_sheet_model');
		$this->fungsi->dbx = $this->job_sheet_model->dbx;
	}

	function index(){
		$this->fungsi->auth(50,'report Job Sheet');
		$data['title'] = $this->title;
		$data['table_title'] ='INVOICE';
		$data['menu_navigation'] ='navigation';
		$data['main_view'] ='report/template-report';
		$this->load->view('afg_template', $data);
	}
	
	function open_form() {	
 		$this->load->view('report/v_job_sheet');
	}
	
	function get_proses() {	
 		$data = $this->job_sheet_model->get_proses($_POST);
 		echo json_encode($data);
	}
	
	function to_excel() {
		$page = 'TOTAL JOB';
		$data = $this->job_sheet_model->get_query();
		$this->session->set_flashdata($_POST);
		$per = (string) $this->session->flashdata('bulan');
		if ($per !== '') {
			$bln = substr($per,4,2);
    		$thn = substr($per,0,4);
    		$periode = $this->fungsi->bulan3($bln).' '.$thn;
		} else {
			$periode = 'ALL PERIOD';
		}
		header("Cache-Control: no-cache, no-store, must-revalidate");  
		header("Content-Type: application/vnd.ms-excel");  
		header("Content-Disposition: attachment; filename=$page.xls");		
 		echo link_tag('public/theme/css/report.css', 'stylesheet', 'text/css').
		'<table summary="" width="100%" align="center">
		<tr><td class="space"></td><td class="judul" colspan="11">TOTAL JOB '.$periode.'</td></tr>
	   <tr><td class="space"></td><td class="title" colspan="11">Periode : '.$periode.'</td></tr>
		<tr>
			<td class="space"></td>
			<td align="center" width="30" class="header">NO</td>
			<td align="center" width="120" class="header">REF NO</td>
			<td align="center" width="170" class="header">SHIPPER</td>
			<td align="center" width="90" class="header">PACKAGING</td>
			<td align="center" width="90" class="header">VOLUME</td>
			<td align="center" width="90" class="header">TYPE CONTAINEER</td>
			<td align="center" width="120" class="header">POL / POD</td>
			<td align="center" width="70" class="header">CARRIER</td>
			<td align="center" width="120" class="header">H BL / M BL</td>
			<td align="center" width="120" class="header">FREIGHT</td>
			<td align="center" width="120" class="header">SCHEDULE DETAIL</td>
			<td align="center" width="120" class="header">SEAL NO / CONT NO</td>
		</tr>
		<tr><td style="border:none; height: 5px" colspan="12"></td></tr>';
		if($data) {
			$i=1;
			foreach($data->result_array() as $row ) {
				$kode_ref = $row['spd_kode_ref'];
				$id = $row['sp_id'];
					if(substr($kode_ref,4,3)=='300'|| substr($kode_ref,4,3)=='301'||substr($kode_ref,4,3)=='302'||substr($kode_ref,4,3)=='400'||substr($kode_ref,4,3)=='402'||substr($kode_ref,4,3)=='600')
				{ $kepada = $row['shipper_import']; 
				$destination = $row['sp_destination'];} 
				else { $kepada = $row['shipper_print'];
				$destination = $row['spd_destination'];
				if($destination=='') { $destination = $row['sp_destination']; }
				}
				$port_loading = $row['port_loading'];
				if($port_loading == '' ) { $port_loading = '<br>'; }			
				$reg_id = substr($this->session->userdata('region_id'),0,1);
				$des_id = $this->fungsi->get_country_id($destination);			
				$blno = $row['hbl'];
				$html = htmlspecialchars("\n");
				$detail_sch = $this->job_sheet_model->get_schedule_detail($row['sp_id']);
    			$item = str_replace('<br>', $html, $detail_sch);
    			$vol = $this->job_sheet_model->get_volume($kode_ref);
    			$party = $this->job_sheet_model->get_party($kode_ref);
				//print_r($this->dbx->last_query());
				//die();
    			$volume = $vol;
				$total_volume [] = $row['meas'];
    			$countener = $row['cont_qty'].' X '.$row['cont_size'];
    			if($row['cont_qty']=='' || $row['cont_size']=='') {
    				$countener = "";
    			}
    					
				echo 
				'<tr>
					<td class="space"></td>
					<td align="center">'.$i.'</td>
					<td>'.$kode_ref.'</td>
					<td>'.$kepada.'</td>
					<td>'.$party.'</td>
					<td>'.$volume.'</td>
					<td>'.$countener.'</td>
					<td>'.$port_loading.' / '.$destination.'</td>
					<td>'.$row['carrier'].'</td>
					<td>'.$blno.' '.$row['mbl'].'</td>
					<td>'.$row['freight'].'</td>
					<td>'.$item.'</td>
					<td>'.$row['csn'].'</td>
				</tr>';
				$i++;
			}
		}
		 echo '<tr><td style="border:none; height: 5px" colspan="11"></td></tr>
            <tr>
			<td class="space"></td>
			<td align="center" width="1100" class="header" colspan="11">TOTAL VOLUME</td>
			<td align="right" width="120" class="header">'.$this->fungsi->pecah(array_sum($total_volume)).' cbm</td>
		</tr>';
		echo '</table>';
		exit();
	}
	
	function to_pdf(){
		$data = $this->job_sheet_model->get_query();
		//print_r($this->dbx->last_query());
		//die();
		$this->session->set_flashdata($_POST);
		$this->load->library('FPDF_MC_Table');
		$pdf=new FPDF_MC_Table();	
		$periode = (string) $this->session->flashdata('bulan');
		if ($periode !== '') {
			$bulan = substr($periode,4,2);
    		$tahun = substr($periode,0,4);
			$periode_header = $bulan.'-'.$tahun;
		} else {
			$periode_header = 'ALL PERIOD';
		}
		$pdf->setDataHeader(array('report'=>'report_job_sheet','bulan'=>$periode_header,));
		$pdf->AddPage('L','Legal');
		$pdf->SetFont('Arial','',8);
		$pdf->SetWidths(array(10,40,25,20,20,20,30,30,25,70,45));
		$pdf->SetAligns(array('C','L','L','L','L','L','L','L','L','L','L'));		
		if($data) {
			$i=1;
			foreach($data->result_array() as $row ) {
				$kode_ref = $row['spd_kode_ref'];
				$id = $row['sp_id'];
				if(substr($kode_ref,4,3)=='300'|| substr($kode_ref,4,3)=='301'||substr($kode_ref,4,3)=='302'||substr($kode_ref,4,3)=='400'||substr($kode_ref,4,3)=='402'||substr($kode_ref,4,3)=='600')
				{ $kepada = $row['shipper_import']; 
				$destination = $row['sp_destination'];} 
				else { $kepada = $row['shipper_print'];
				$destination = $row['spd_destination'];
				if($destination=='') { $destination = $row['sp_destination']; }
				}
				$port_loading = $row['port_loading'];
				if($port_loading == '' ) { $port_loading = '<br>'; }
				
				$reg_id = substr($this->session->userdata('region_id'),0,1);
				$des_id = $this->fungsi->get_country_id($destination);
				
				$blno = $row['hbl'];
				
				$html = htmlspecialchars("\n");
				$detail_sch = $this->job_sheet_model->get_schedule_detail($row['sp_id']);
    			$item = str_replace('<br>', $html, $detail_sch);
    			
    			$vol = trim($this->job_sheet_model->get_volume($kode_ref));
    			$party =$this->job_sheet_model->get_party($kode_ref);
    			$volume = str_replace('<br>', $html, $vol);
    			$total_volume [] = $row['meas'];
    			$countener = $row['cont_qty'].' X '.$row['cont_size'];
    			if($row['cont_qty']=='' || $row['cont_size']=='') {
    				$countener = "";
    			}
    		
				$pdf->Row(array(
					$i,
					$kode_ref,
					$kepada,
					$party,
					$vol,
					$countener,
					$port_loading.' / '.$destination,
					$row['carrier'],
					$blno.' '.$row['mbl'],
					$item,
					$row['csn'],
				));
				$i++;
			}
		}
		$pdf->Ln(1);
		$pdf->SetFont('Arial','B',10);
		$pdf->Cell(290,7,'TOTAL VOLUME',1,'','C',false);
		$pdf->Cell(45,7,array_sum($total_volume).' cbm',1,'','C',false);
		$pdf->Output('JOB_SHEET_'. time().'.pdf','I');
	}
	
	
	function get_schedule($id){
		$data = $this->job_sheet_model->get_schedule($id);
 		return $data;
	}
	
}

/*
function too_excel(){	
		$this->load->library(array('Spreadsheet_Excel_Reader','fungsi'));
		$excel = new Spreadsheet_Excel_Reader('tes.xls');
		echo $excel->dump(true,true);
}
*/
