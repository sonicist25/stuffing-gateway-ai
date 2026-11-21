<?php
class C_download_invoice extends CI_Controller{

	var $title ='Invoice to Shipper';
	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('finance/m_inv_to_shipper','afg_m_cities','finance/m_finance_proses','afg_m_job_sheet','user_model'));
		$this->dbx = $this->m_inv_to_shipper->dbx; 
	}

	
	function draft() {
	
		
		//include the libraries
		//require_once("pdf/phpqrcode/qrlib.php");
		
		//create a QR Code and save it as a png image file named test.png
		
		//QRcode::png($this->input->get('kode_ref'),"test.png");
		
  		$no_invoice = $this->input->get('invoice_number');
		$invoice_date = $this->input->get('invoice_date');
		$currency = $this->input->get('currency');
		$kepada = $this->input->get('kepada');
		$kepada_pajak = $this->input->get('kepada_pajak');
		$region = 'Jakarta';
		$numb = !empty($no_invoice) ? substr($no_invoice, 0, 3) : "000";
		$kode_ref = $this->input->get('kode_ref');
		$id = $this->input->get('id');
		$logo='logo.png';
		$company = 'PT. GATEWAY CONTAINER LINE';
		$query = $this->m_inv_to_shipper->get_report_pdf($this->input->get('kode_ref'));
		$data =  $this->m_inv_to_shipper->get_form_inv($this->input->get('id'));
		if (strtoupper($query['shipment_type_multi'])=='COLOAD'){
			if($region=='Jakarta')
			{$shipment='COLOAD';}
			else
			{$shipment='LCL';}
		}else{
		$shipment = strtoupper($query['shipment_type_multi']);
		}
		$this->load->library('FPDF_MC_Table');
		$pdf=new FPDF_MC_Table();		
		$pdf->AliasNbPages();	
		$pdf->setDataHeader(array('report'=>'Invoice Draft'));
		$pdf->AddPage('P','invoice');

		if ($region=='Jakarta'){
			$pdf->SetFont('verdana','',12);
			$pdf->SetXY(130, 10);
			$pdf->write(5,'PT. GATEWAY CONTAINER LINE');
			$pdf->SetFont('verdana','',7);
			$pdf->SetXY(148, 17);
			$pdf->write(0,'JL. OTISTA III RT. 001 RW. 001 NO. 18');
			$pdf->SetXY(153, 20);
			$pdf->write(0,'CIPINANG CEMPEDAK JATINEGARA');
			$pdf->SetXY(168, 23);
			$pdf->write(0,'JAKARTA TIMUR 13340');
			$pdf->SetXY(143, 26);
			$pdf->write(0,'PHONE : ( 62-21 ) 8591-8669 ( HUNTING )');
			$pdf->SetXY(163, 29);
			$pdf->write(0,'FAX : ( 62-21 ) 8591-8664');
			$pdf->SetFont('verdana','',8);
			$pdf->SetXY(161, 32);
			$pdf->write(0,'general@gateway-id.com');
		} 
		else if ($region=='Surabaya')
		{
		$pdf->SetFont('verdana','',12);	
		$pdf->SetXY(10, 13);
		$pdf->Cell(190,4, 'PT. GATEWAY CONTAINER LINE',0,1,'R',false,'');
		$pdf->SetFont('verdana','',8);
		$pdf->Cell(190,4,strtoupper('JL GAYUNGSARI BARAT NO 91B'),0,1,'R',false,'');
		$pdf->Cell(190,4,'GAYUNGAN, KEC. GAYUNGAN',0,1,'R',false,'');
		$pdf->Cell(190,4,'SURABAYA 60235, JAWA TIMUR',0,1,'R',false,'');
		$pdf->Cell(190,4,'PHONE : 031 82522370 / 031 82522405/031 82522827-830',0,1,'R',false,'');
		$pdf->Cell(190,4,'mgtsby@gateway-id.com',0,1,'R',false,'');
		} 
		else if ($region=='Cikarang')
		{
		$pdf->SetFont('verdana','',12);
		$pdf->SetXY(130, 10);
		$pdf->write(5,'PT. GATEWAY CONTAINER LINE');
		$pdf->SetFont('verdana','',7);
		$pdf->SetXY(172, 17);
		$pdf->write(0,'JL. INDUSTRI PASIR');
		$pdf->SetXY(173, 20);
		$pdf->write(0,'GOMBONG NO.114');
		$pdf->SetXY(173.5, 23);
		$pdf->write(0,'CIKARANG UTARA ');
		$pdf->SetXY(166, 26);
		$pdf->write(0,'PHONE :(021)89100449');
		$pdf->SetXY(170, 29);
		$pdf->write(0,'FAX :(021)89100449');
		$pdf->SetFont('verdana','',8);
		$pdf->SetXY(161, 32);
		$pdf->write(0,'lisbeth@gateway-id.com');
		}
		else if ($region=='Semarang')
		{
		$pdf->SetFont('verdana','',12);	
		$pdf->SetXY(10, 13);
		$pdf->Cell(190,4, 'PT. GATEWAY CONTAINER LINE',0,1,'R',false,'');
		$pdf->SetFont('verdana','',8);
		$pdf->Cell(190,4,strtoupper('JL. SEMARANG INDAH NO.D14 / 20A, TAWANGMAS'),0,1,'R',false,'');
		$pdf->Cell(190,4,'KEC. SEMARANG BAR.',0,1,'R',false,'');
		$pdf->Cell(190,4,'KOTA SEMARANG, JAWA TENGAH 50144',0,1,'R',false,'');
		$pdf->Cell(190,4,'PHONE : ( 62-24 ) 76632972',0,1,'R',false,'');
		$pdf->Cell(190,4,'mgtsrg@gateway-id.com',0,1,'R',false,'');
		}
		else if ($region=='Medan')
		{
		$pdf->SetFont('verdana','',12);	
		$pdf->SetXY(10, 13);
		$pdf->Cell(190,4, 'PT. GATEWAY CONTAINER LINE',0,1,'R',false,'');
		$pdf->SetFont('verdana','',8);
		$pdf->Cell(190,4,strtoupper('MANDIRI BUILDING 7TH FLOOR'),0,1,'R',false,'');
		$pdf->Cell(190,4,'JL. IMAM BONJOL NO. 16-D MEDAN 20112',0,1,'R',false,'');
		$pdf->Cell(190,4,'SUMATRA UTARA 20112 - INDONESIA',0,1,'R',false,'');
		$pdf->Cell(190,4,'PHONE : (62-61) 4559876',0,1,'R',false,'');
		$pdf->Cell(190,4,'mgtblw@gateway-id.com',0,1,'R',false,'');
		}
		else if ($region=='Bandung')
		{
		$pdf->setDataHeader(array('report'=>'invoice_bandung'));
		$pdf->SetFont('verdana','',12);	
		$pdf->SetXY(10, 13);
		$pdf->Cell(190,4, 'PT. GATEWAY CONTAINER LINE',0,1,'R',false,'');
		$pdf->SetFont('verdana','',8);
		$pdf->Cell(190,4,strtoupper('METRO TRADE CENTRE, JL SOEKARNO HATTA'),0,1,'R',false,'');
		$pdf->Cell(190,4,'BLOK D NO.21 SEKEJATI, KEC. BUAH BATU',0,1,'R',false,'');
		$pdf->Cell(190,4,'KOTA BANDUNG, JAWA BARAT 40286',0,1,'R',false,'');
		$pdf->Cell(190,4,'PHONE : (022) 7502283',0,1,'R',false,'');
		}
		
		$pdf->Image(APPPATH.'third_party/img/'.$logo,10,13,+55);
		   
		$pdf->SetFont('Arial','B',16);
		if ($numb == 'INR')
		{
			$pdf->SetXY(90, 45);
			$pdf->write(0,' REIMBURSEMENT ');
		}
		else {
			$pdf->SetXY(90, 45);
			$pdf->write(0,' INVOICE ');
		}
			$pdf->SetFont('verdana','',10);
			$pdf->SetXY(150, 45);
			$pdf->MultiCell(50,8,$no_invoice,0,0,false);
		
			$pdf->Line(10,53,200,53);		
			$pdf->Line(120,110,120,53);
		
			$pdf->SetFont('verdana','',8);
		$pdf->SetXY(10, 56);
		$pdf->write(0,'BILL TO :');
		$pdf->SetXY(10, 60);
		$pdf->MultiCell(105, 3, $kepada. "\n" . $kepada_pajak);
		
		$pdf->SetXY(123, 56);
		$pdf->write(0,'REF NUMBER');
		$pdf->SetXY(149, 56);
		$pdf->write(0,':');		
		$pdf->SetXY(151, 56);
		$pdf->write(0,$kode_ref.' / '.$shipment);
		
		$pdf->SetXY(123, 60);
		$pdf->write(0,'DATE');
		$pdf->SetXY(149, 60);
		$pdf->write(0,':');
		$pdf->SetXY(151, 60);
		$pdf->write(0,$invoice_date);
		
		$pdf->SetXY(123, 64);
		$pdf->write(0,'OCEAN FREIGHT');
		$pdf->SetXY(149, 64);
		$pdf->write(0,':');
		$pdf->SetXY(151, 64);
		$pdf->write(0,$query['freight']);
		
		$pdf->SetXY(123, 68);
		$pdf->write(0,'PARTY');
		$pdf->SetXY(149, 68);
		$pdf->write(0,':');
		$pdf->SetXY(151, 68);
		$pdf->write(0, strtoupper($query['no_of_pack'] ?? ''));
		
		$pdf->SetXY(123, 72);
		$pdf->write(0,'GWT');
		$pdf->SetXY(149, 72);
		$pdf->write(0,':');
		$pdf->SetXY(151, 72);
		$pdf->write(0,$query['weight'].' '.'KGS');

		$pdf->SetXY(123, 76);
		$pdf->write(0,'CBM');
		$pdf->SetXY(149, 76);
		$pdf->write(0,':');
		$pdf->SetXY(151, 76);
		$pdf->write(0,$query['bl_meas'].' '.'M3');
		
		$pdf->Line(10,79,200,79);
		
		$pdf->SetXY(10, 83);
		$pdf->write(0,'VESSEL');
		$pdf->SetXY(27, 83);
		$pdf->write(0,':'); 
		$pdf->SetXY(29, 83);
		
		$detail = $this->afg_m_job_sheet->get_detail_job($query['sp_detail_id']);
		$job = substr($kode_ref,4,3);	
		if($job=='300'||$job=='301'||$job=='302'||$job=='400'||$job=='402'||$job=='600')
		{
			$pdf->write(0,$query['vessel'].' '.$query['voyage']);
		} 
		else 
		{
			if($detail->last_vessel != '') 
			{
				$vessel = $detail->last_vessel;
				$vessel_etd = $detail->last_vessel_etd;
				$vessel_eta = $detail->last_vessel_eta;
				$vessel_etd_date = $detail->last_vessel_etd_date;
				$vessel_eta_date = $detail->last_vessel_eta_date;
			}
			else 
			{
				$vessel = $detail->feeder_vessel;
				$vessel_etd = $detail->feeder_vessel_etd;
				$vessel_eta = $detail->feeder_vessel_eta;
				$vessel_etd_date = $detail->feeder_vessel_etd_date;
				$vessel_eta_date = $detail->feeder_vessel_eta_date;
			}
			$pdf->write(0,$vessel);
		}
		
		////print_r(array($job,$query['freight'],$query['vessel']));exit();

		$pdf->SetXY(10, 87);
		$pdf->write(0,'ETD/ETA');
		$pdf->SetXY(27, 87);
		$pdf->write(0,':');
		$pdf->SetXY(29, 87);
		$etd_jkt = $query['etd_jkt'];
		$eta = $query['eta'];
		$pdf->write(0,substr($etd_jkt,6,2).'-'. substr($etd_jkt,4,2).'-'. substr($etd_jkt,0,4).'/'.substr($eta,6,2).'-'. substr($eta,4,2).'-'. substr($eta,0,4));
		
		$pdf->SetXY(10, 91);
		$pdf->write(0,'POL/POD');
		$pdf->SetXY(27, 91);
		$pdf->write(0,':');
		$pdf->SetXY(29, 91);
		$job = substr($kode_ref,4,3);	
		if($job=='300'||$job=='301'||$job=='302'||$job=='400'||$job=='402'||$job=='600')  {
		$pdf->write(0,$query['port_loading'].' / '.$query['port_dest']);}
		else{
		$pdf->write(0,$query['port_loading'].' / '.$query['destination']);}
		
		$pdf->SetXY(10, 95);
		$pdf->write(0,'B/L NO');
		$pdf->SetXY(27, 95);
		$pdf->write(0,':');
		$pdf->SetXY(29, 95);
		$region = $this->session->userdata('region_id');
		$destination = $this->afg_m_cities->get_city_id($query['destination']);

		$pdf->write(0,strtoupper($query['hb_l']));

		$pdf->SetXY(10, 99);
		$pdf->write(0,'MBL NO');
		$pdf->SetXY(27, 99);
		$pdf->write(0,':');
		$pdf->SetXY(29, 99);
		$pdf->write(0,$query['mother_bl']);
		
		$pdf->Line(10,103,200,103);	
		if($job=='300'||$job=='301'||$job=='302'||$job=='400'||$job=='402'||$job=='600')  {
		$pdf->SetXY(123, 85);
		$pdf->write(0,'SUPPLIER');
		$pdf->SetXY(140, 85);
		$pdf->write(0,':');
		$pdf->SetXY(145, 83);
		$pdf->MultiCell(50, 4,$this->input->get('si_shipper'),'0','L');
		} else {
		$pdf->SetXY(123, 83);
		$pdf->write(0,'SI NUMBER');
		$pdf->SetXY(143, 83);
		$pdf->write(0,':');
		$pdf->SetXY(148, 81);
		$pdf->MultiCell(50, 4,$this->input->get('si_shipper'),'0','L');
		}
		$pdf->SetXY(36, 107);
		$pdf->write(0,'DETAIL OF CHARGES');
		$pdf->SetXY(96, 107);
		$pdf->write(0,'QUANTITY');
		$pdf->SetXY(127, 107);
		$pdf->write(0,'UNIT RATE');
		$pdf->SetXY(168, 107);
		$pdf->write(0,'AMOUNT');
		$pdf->Ln(3);
		
		$pdf->Ln();
		
		$pdf->Line(10,110,200,110);
		$pdf->Line(90,110,90,103);
		$pdf->Line(151,110,151,103);
		//$pdf->Line(10,53,10,103);
		//$pdf->Line(200,53,200,103);
		$j=1;
		if($data) {
			foreach ($data->result_array() as $row){
				$amount = $row['price'];
				$pdf->Cell(80, 6,strtoupper($row['item']),0,0,'L');
				$pdf->Cell(30, 6,$row['qty'],0,0,'C');
				$pdf->Cell(10, 6,$row['currency'],0,0,'L');
				$pdf->Cell(21, 6,$this->fungsi->pecah($row['unit_price'],','),0,0,'R');
				$pdf->Cell(13, 6,$row['currency'],0,0,'R');
				$pdf->Cell(36, 6,$this->fungsi->pecah($amount,','),0,0,'R');
				$pdf->Ln();
				$j++;
			}
			if ($j >20)
			{ $pdf->AddPage();}
		} 
		
		
		$jumlah_idr = $this->m_inv_to_shipper->get_ttl_inv($id,'RP.');
		$jumlah_usd = $this->m_inv_to_shipper->get_ttl_inv($id,'USD');
		
		$ttl_usd = '';
		$ttl_idr = '';
		
		if(!empty($jumlah_usd)) 
		$ttl_usd=$this->fungsi->pecah(array_sum($jumlah_usd),',');
		
		if(!empty($jumlah_idr)) 
		//$ttl_idr=round(array_sum($jumlah_idr),-2);
		$ttl_idr=$this->fungsi->pecah(array_sum($jumlah_idr),',');
		
		$pdf->Line(10,235,200,235);
		$pdf->Line(151,245,151,235);
		$pdf->Line(200,245,151,245);
		
		//$pdf->Image("http://localhost/pdf/qr_generator.php?code=$kode_ref", 160, 246, 28, 28, "png");
			
		
		if($ttl_usd!='') {
			$pdf->SetXY(153, 236);
			$pdf->MultiCell(12,4,'USD : ',0,'L',false);
			$pdf->SetXY(160, 236);
			$pdf->MultiCell(38,4, $ttl_usd,0,'R',false);
		}
		if($ttl_idr!='') {
			$pdf->SetXY(153, 240);
			$pdf->MultiCell(12,4,'IDR : ',0,'L',false);
			$pdf->SetXY(160, 240);
			$pdf->MultiCell(38,4,$ttl_idr,0,'R',false);
		}
		
			$pdf->SetFont('verdana','',8);
			$pdf->SetXY(10, 253);
			$pdf->write(0,'BANK ACCOUNT DETAIL :');
			$pdf->SetXY(10, 257);
			$pdf->write(0,'IDR :');
			$pdf->SetXY(20, 257);
			$pdf->write(0,'PT. GATEWAY CONTAINER LINE');
			$pdf->SetXY(20, 261);
			$pdf->write(0,'MAYBANK INDONESIA' );
			$pdf->SetXY(20, 265);
			$pdf->write(0,'A/C NUMBER : 2082409727');
		
		$pdf->Output($no_invoice.'.pdf','I'); 
	}
}

/*
getListGrop => prosses add
*/