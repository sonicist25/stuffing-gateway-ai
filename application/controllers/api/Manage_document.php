<?php
 
use chriskacerguis\RestServer\RestController;
 
class Manage_document extends RestController{
     
    public function __construct(){
        parent::__construct();
        $this->load->model(array('api/m_api','m_bill_of_ladding','m_print_out_bl','pricelist/booking_model'));
    }
     
    public function index_get(){
		$id = $this->get('booking_id');
		if($id===null){
        $response = $this->m_api->read_bl();
		}else{
		$response = $this->m_api->read_bl($id);
		}
        $this->response($response);
    }
	public function index_post(){
        $data = array(
        'booking_code' => $this->post('booking_code'),
		'freight' => $this->post('freight'),	
		'peb' => $this->post('peb'),
		'number_of_bl' => $this->post('number_of_bl'),
		'status' => 'finalize',
		'total' => $this->post('total'),
		'pickup_datetime'=>$this->post('pickup_datetime'),
		'expect_etd' => $this->post('expect_etd'),
		'eta' => $this->post('eta'),
		'warehouse' => $this->post('warehouse'),
		'si_number' => $this->post('si_number'),
		'remark' => $this->post('remark'),
		'closing_date'=>$this->post('closing_date'),
		'pol_bl'=>$this->post('port_of_loading'),
		'pod_bl'=>$this->post('port_of_delivery'),
		'pickup_location'=>$this->post('pickup_location'),
		'drop_location'=>$this->post('drop_location'),
		'incoterm'=>$this->post('incoterm'));
		
		$data_detail = array(
		'booking_id' => $this->post('booking_code'),
        'shipper_name' => $this->post('shipper_name'),
        'shipper_address' => $this->post('shipper'),
        'consignee_name' => $this->post('consignee_name'),
        'consignee' => $this->post('consignee'),
        'notify_name' => $this->post('notify_name'),
        'notify' => $this->post('notify'),
        'package_type' => $this->post('packaging'),
        'quantity' => $this->post('quantity'),
        'dimension' => $this->post('dimension'),
		'volume' => $this->post('volume'),
        'weight' => $this->post('gross_weight'),
        'netto' => $this->post('nett_weight'),
        'container_type' => $this->post('container_type'),
        'shipping_mark' => $this->post('marking'),
        'marking' => $this->post('shipping_mark'),
        'description_print' => $this->post('description_print'),
        'description_of_goods' => $this->post('desc_goods'),
		'as_agent' => $this->post('as_agent'),
		'created_at' => date("Ymd"),
		'updated_at' => '');
		
        $response = $this->m_api->create_shipping_intruction($data,$data_detail);
		
		$filename = "Booking Confirmation-".$data['si_number'].".pdf";
		$this->cetak_bc_api($data,$data_detail, $filename);
		$booking=$this->booking_model->find($data['booking_code']);
		$to = $this->booking_model->get_shipper_details($booking->shipper_id);
		$dataEmail = array('nama' => $to->shipper_name, 'booking_code' => $data['booking_code']);
		$html = $this->load->view('emails/confirmation.php',$dataEmail,TRUE);
		$response = send_email_elastic("Booking Confirmation - ".$data['si_number'], $html, array($to->cp_email), $filename);
		if($response['code'] == 500){
			return $this->output->set_status_header(500)
			->set_content_type('application/json')
			->set_output(json_encode($response['response']));
		}else{
			unlink(realpath(APPPATH.'../'.$filename));
			echo json_encode($response);	
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