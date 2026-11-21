<?php
 
use chriskacerguis\RestServer\RestController;
 
class Manage_invoice extends RestController{
     
    public function __construct(){
        parent::__construct();
        $this->load->model(array('api/m_api','finance/m_inv_to_shipper','afg_m_cities','finance/m_finance_proses','afg_m_job_sheet','user_model'));
    }
     
    public function index_get(){
		$id = $this->get('booking_id');
		if($id===null){
        $response = $this->m_api->read_invoice();
		}else{
		$response = $this->m_api->read_invoice($id);
		}
        $this->response($response);
    }
		
	function cetak_bc_api($data,$data_detail, $filename) 
	{  
		//include the libraries
		require_once("pdf/phpqrcode/qrlib.php");
		
		//create a QR Code and save it as a png image file named test.png
		$si = $data['si_number'];
		QRcode::png($si,"test.png");
		$this->load->library('FPDF_MC_Table');
		$pdf=new FPDF_MC_Table();		
		$pdf->AddPage('P','A4');
		$pdf->SetAutoPageBreak(false);
	
		
			$logo = 'logo.png';
			$pt = 'PT GATEWAY CONTAINER LINE';
			$add = 'JL. OTISTA III RT. 001 RW. 001 NO. 18
						CIPINANG CEMPEDAK JATINEGARA
						JAKARTA TIMUR 13340
						PHONE : ( 62-21 ) 8591-8669 ( HUNTING )
						FAX : ( 62-21 ) 8591-8664';	
		
		
		$booking=$this->booking_model->find($data['booking_code']);
		//print_r($this->dbx->last_query());
		//die();
		$wh=$this->booking_model->get_warehouse_detail($booking->warehouse);
		//print_r($this->dbx->last_query());
		//die();
		$closing_date=$data['closing_date'];
		//print_r($_GET);
		//die();
		$pdf->Image(APPPATH.'third_party/img/BC.jpg',0,0,+200);
		$pdf->SetFont('Arial','',7);
		$pdf->SetXY(50, 40);
		$pdf->write(0,$booking->mode);
		$pdf->SetXY(50, 44.5);
		$pdf->write(0,urldecode($si));
		$pdf->SetXY(50, 49);
		$pdf->write(0,date("Y/m/d"));
		$pdf->SetXY(50, 53.5);
		$pdf->write(0,$closing_date);
		$pdf->SetXY(50, 62.5);
		$pdf->write(0,$booking->expect_etd);
		$pdf->SetXY(20,72);
		$pdf->MultiCell(60,3,strtoupper($data_detail['shipper_name']),0,'L',false);
		$pdf->SetXY(20,75);
		$pdf->MultiCell(60,3,strtoupper($data_detail['shipper_address']),0,'L',false);
		$pdf->SetXY(105,72);
		$pdf->MultiCell(60,3,$data_detail['consignee'],0,'L',false);
		$pdf->SetXY(20,92);
		$pdf->MultiCell(60,3,$data_detail['notify'],0,'L',false);
		$pdf->SetXY(105, 94);
		$pdf->write(0,strtoupper($booking->vessel));
		$pdf->SetXY(148, 94);
		$pdf->write(0,strtoupper($booking->voyage));
		$pdf->SetXY(105, 102);
		$pdf->write(0,$booking->expect_etd);
		$pdf->SetXY(148, 102);
		$pdf->write(0,$booking->eta);
		$pdf->SetXY(105, 111);
		$pdf->write(0,$booking->origin_city);
		$pdf->SetXY(148, 111);
		$pdf->write(0,$booking->destination_city);
		$pdf->SetXY(20, 119);
		$pdf->write(0,$booking->number_of_bl);
		$pdf->SetXY(75, 119);
		$pdf->write(0,$data_detail['quantity']." ".$data_detail['package_type']);
		$pdf->SetXY(105, 119);
		$pdf->write(0,$data_detail['weight']." KGs");
		$pdf->SetXY(135, 119);
		$pdf->write(0,$data_detail['netto']." KGs");
		$pdf->SetXY(162, 119);
		$pdf->write(0,$data_detail['volume']." M3");
		$pdf->SetXY(20, 126);
		$pdf->write(0,$data['freight']);
		$pdf->SetXY(105, 126);
		$pdf->write(0,$data['incoterm']);
		$pdf->SetXY(20, 135);
		$pdf->MultiCell(60,3,$data_detail['marking'],0,'L',false);
		$pdf->SetXY(105, 135);
		$pdf->MultiCell(60,3,$data_detail['description_of_goods'],0,'L',false);
		$pdf->SetXY(20, 187);
		$pdf->write(0,$wh['warehouse_name']);
		$pdf->SetXY(20, 190);
		$pdf->multiCell(100,3,$wh['warehouse_address']);
		$pdf->SetXY(11, 228);
		if($booking->mode=='FCL'|| $booking->mode=='LCL'){
		$pdf->write(0,'SEA');
		}
		$pdf->SetXY(30, 228);
		$pdf->write(0,$booking->vessel);
		$pdf->SetXY(75, 228);
		$pdf->write(0,$booking->origin_city);
		if(!empty($booking->trans_city)){
		$pdf->SetXY(116, 228);
		$pdf->write(0,$booking->trans_city);
		$pdf->SetXY(11, 231);
		if($booking->mode=='FCL'|| $booking->mode=='LCL'){
		$pdf->write(0,'SEA');}
		$pdf->SetXY(30, 231);
		$pdf->write(0,$booking->voyage);
		$pdf->SetXY(75, 231);
		$pdf->write(0,$booking->trans_city);
		$pdf->SetXY(116, 231);
		$pdf->write(0,$booking->origin_city);
		$pdf->SetXY(147, 228);
		$pdf->write(0,$booking->expect_etd);
		$pdf->SetXY(167, 228);
		$pdf->write(0,$booking->eta);
		}else{
		$pdf->SetXY(116, 228);
		$pdf->write(0,$booking->destination_city);
		$pdf->SetXY(147, 228);
		$pdf->write(0,$booking->expect_etd);
		$pdf->SetXY(167, 228);
		$pdf->write(0,$booking->eta);
		}
		$pdf->SetXY(11,277);
		$pdf->write(0,$this->session->userdata('fullname'));
		$pdf->Image("http://gateway-cl.com/pdf/qr_generator.php?code=urldecode($si)", 160, 246, 28, 28, "png");
		$pdfdoc = $pdf->Output($filename,'F');
	}

		public function index_put(){
		$booking_code = $this->put('booking_code');
        $data = array(
        'shipper' => $this->put('shipper'),
		'consignee' => $this->put('consignee'),
		'notify' => $this->put('notify'),
        'agent' => $this->put('agent'),
		'no_pack_qty'=>$this->put('qty'),
		'no_of_pack'=>$this->put('packaging'),
        'marking_nos' => $this->put('shipping_mark'),
        'desc_goods' => $this->put('desc_goods'),
        'cetak_jml_doc_origi' => $this->put('number_of_bl'),
        'express_release' => $this->put('express_release'),
        'shipped_on_board' => $this->put('shipped_on_board'),
        'weight' => $this->put('gross_weight'),
		'netto' => $this->put('nett_weight'),
		'bl_meas' => $this->put('volume'),
		'freight' => $this->put('freight'));
		$data_detail = array(
		'shipper_name'=>$this->put('shipper_name'),
		'shipper_address'=>$this->put('shipper_add'),
		'port_of_receive' => $this->put('port_of_receipt'),
        'port_of_loading' => $this->put('port_of_loading'),
        'port_of_delivery' => $this->put('port_of_delivery'),
		'port_of_discharge' => $this->put('port_of_discharge'),
		'last_vessel'=> $this->put('vessel'),
		'last_snd_vessel'=> $this->put('connecting'),
		'last_count' => $this->put('container'),
		'as_agent' => $this->put('as_agent'),
		'no_from_shipper' => $this->put('si_number'));
        $response = $this->m_api->update_bl($data,$data_detail,$booking_code);
        $this->response($response);
    }
} 
?>