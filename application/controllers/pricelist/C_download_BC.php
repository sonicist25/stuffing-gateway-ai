<?php
class C_download_bc extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->library('fungsi');
		$this->fungsi->restrict();
		$this->load->model(array('pricelist/booking_model','user_model','reference/stuffing_location_model','afg_m_detail','reference/consignee_notify_model','afg_m_cities','afg_m_job_sheet','afg_m_ref'));
	}
	
	function cetak_bc() 
	{  
		//include the libraries
		require_once("pdf/phpqrcode/qrlib.php");
		
		//create a QR Code and save it as a png image file named test.png
		$si = $this->input->get('booking_number');
		QRcode::png($si,"test.png");
		$this->load->library('FPDF_MC_Table');
		$pdf=new FPDF_MC_Table();		
		$pdf->AddPage('P','A4');
		$pdf->SetAutoPageBreak(false);
	
		if($this->session->userdata('region_id')=='Osline') {
			$logo = 'osline.png';
			$pt = 'OCEAN SKY LINE';
			$add = 'GCL BUILDING, 1ST FLOOR
				Jl OTISTA III NO. 18
				JAKARTA TIMUR 13340 - INDONESIA
				PHONE (62) 21 21012840';
		} else {
			$logo = 'logo.png';
			$pt = 'PT GATEWAY CONTAINER LINE';
			$add = 'JL. OTISTA III RT. 001 RW. 001 NO. 18
						CIPINANG CEMPEDAK JATINEGARA
						JAKARTA TIMUR 13340
						PHONE : ( 62-21 ) 8591-8669 ( HUNTING )
						FAX : ( 62-21 ) 8591-8664';	
		}
		$wh=$this->booking_model->get_warehouse_detail($_GET['warehouse']);
		$do_number =$this->booking_model->get_do($_GET['booking_number']);
		//print_r($do_number);
		//die();
		$pdf->Image(APPPATH.'third_party/img/BC.jpg',0,0,+200);
		$pdf->SetFont('Arial','',7);
		$pdf->SetXY(50, 40);
		$pdf->write(0,$_GET['type']);
		$pdf->SetXY(50, 44.5);
		$pdf->write(0,$_GET['booking_number']);
		$pdf->SetXY(50, 49);
		$pdf->write(0,date("Y/m/d"));
		$pdf->SetXY(50, 53.5);
		$pdf->write(0, date('Y/m/d', strtotime($_GET['closing_date'])));
		$pdf->SetXY(50, 62.5);
		$pdf->write(0,$do_number);
		$pdf->SetXY(20,72);
		$pdf->MultiCell(60,3,strtoupper($_GET['shipper']),0,'L',false);
		$pdf->SetXY(20,75);
		$pdf->MultiCell(60,3,strtoupper($_GET['note']),0,'L',false);
		$pdf->SetXY(105,72);
		$pdf->MultiCell(60,3,$_GET['con_add'],0,'L',false);
		$pdf->SetXY(20,92);
		$pdf->MultiCell(60,3,$_GET['not'],0,'L',false);
		$pdf->SetXY(105, 94);
		$pdf->write(0,$_GET['vessel']);
		$pdf->SetXY(148, 94);
		$pdf->write(0,$_GET['voyage']);
		$pdf->SetXY(105, 102);
		$pdf->write(0,$_GET['etd']);
		$pdf->SetXY(148, 102);
		$pdf->write(0,$_GET['eta']);
		$pdf->SetXY(105, 111);
		$pdf->write(0,$_GET['pol']);
		$pdf->SetXY(148, 111);
		$pdf->write(0,$_GET['pod']);
		$pdf->SetXY(20, 119);
		$pdf->write(0,$_GET['bl']);
		$pdf->SetXY(75, 119);
		$pdf->write(0,$_GET['qty']." ".$_GET['pack']);
		$pdf->SetXY(105, 119);
		$pdf->write(0,$_GET['weight']." KGs");
		$pdf->SetXY(135, 119);
		$pdf->write(0,$_GET['netto']." KGs");
		$pdf->SetXY(162, 119);
		$pdf->write(0,$_GET['meas']." M3");
		$pdf->SetXY(20, 126);
		$pdf->write(0,$_GET['freight']);
		$pdf->SetXY(105, 126);
		$pdf->write(0,$_GET['incoterm']);
		$pdf->SetXY(20, 135);
		$pdf->MultiCell(60,3,$_GET['long_mark'],0,'L',false);
		$pdf->SetXY(105, 135);
		$pdf->MultiCell(60,3,$_GET['long_description'],0,'L',false);
		$pdf->SetXY(20, 187);
		$pdf->write(0,$wh['warehouse_name']);
		$pdf->SetXY(20, 190);
		$pdf->multiCell(100,3,$wh['warehouse_address']);
		$pdf->SetXY(11, 228);
		if($_GET['type']=='FCL'|| $_GET['type']=='LCL'){
		$pdf->write(0,'SEA');
		}
		$pdf->SetXY(30, 228);
		$pdf->write(0,$_GET['vessel']);
		$pdf->SetXY(75, 228);
		$pdf->write(0,$_GET['pol']);
		$pdf->SetXY(116, 228);
		$pdf->write(0,$_GET['pod']);
		$pdf->SetXY(147, 228);
		$pdf->write(0,$_GET['etd']);
		$pdf->SetXY(167, 228);
		$pdf->write(0,$_GET['eta']);
		}
		$pdf->SetXY(11,277);
		$pdf->write(0,$this->session->userdata('fullname'));
		$pdf->Image("http://gateway-cl.com/pdf/qr_generator.php?code=$si", 160, 246, 28, 28, "png");
		$pdf->Output("BC ".$si." ".date('d-M-Y').'.pdf','I');
	}
}
?>