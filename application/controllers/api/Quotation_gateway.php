<?php
use chriskacerguis\RestServer\RestController;
 
class Quotation_gateway extends RestController{
      
    public function __construct(){
        parent::__construct();
        $this->load->model(array('api/m_api','api/m_instant_booking','m_bill_of_ladding','m_print_out_bl','pricelist/booking_model'));
		$this->dbx = $this->m_instant_booking->dbx;
    }
     
    public function index_get(){
		$booking_code = $this->get('booking_code');
		$this->dbx->where('booking_code',$booking_code);
		$data = $this->dbx->get('trnstuffingplan_booking')->result_array();
		//print_r($this->dbx->last_query());
		//die();
		if(is_array($data) && count($data)>0){
		$id = $data[0]['id'];
		$code = $data[0]['booking_code'];
		}else{
		$id = '';
		$code = '';
		}
		if($id===null){
        $response = $this->m_api->read_product();
		}else{
		$response = $this->m_api->read_product($id,$code);
		//print_r($this->dbx->last_query());
	  //die();
		}
		if($response){
        $this->response($response);
		}else{
		 $this->response(['error'=>true,'message'=>'Data tidak ditemukan'],404);
		}
    }
	
	public function index_post(){
		$shipper = $this->m_api->get_shipper_detail($this->post('user_first_name'));
		//print_r($shipper);
		//die();
        $data = array(
		'type_of_booking' => $this->post('type_of_booking'),
        'booking_code' => $this->post('booking_code'),
        'mode' => $this->post('mode'),
        'movement_type' => $this->post('movement_type'),
        'incoterm' => $this->post('incoterm'),
        'origin_country' => $this->post('origin_country'),
        'origin_city' => $this->post('origin_city'),
		'destination_city' => $this->post('destination_city'),
        'trans_city' => $this->post('trans_city'),
		'insurance'=>$this->post('insurance'),
		'trucking'=>$this->post('trucking'),
		'freight'=>$this->post('freight'),
		'clearance'=>$this->post('clearance'),
		'peb'=>$this->post('peb'),
		'cargo_type' => $this->post('cargo_type'),
		'forwarder_name'=>$this->post('user_first_name'),
        'shipper_id' => $shipper['shipper'],
		'sales'=>$shipper['sales'],
		'number_of_bl' => $this->post('number_of_bl'),
		'status' => $this->post('status'),
		'total' => $this->post('total'),
		'pickup_datetime'=>$this->post('pickup_datetime'),
		'expect_etd' => $this->post('expect_etd'),
		'eta' => $this->post('eta'),
		'warehouse' => $this->post('warehouse'),
		'si_number' => $this->post('si_number'),
		'remark' => $this->post('remark'),
		'closing_date'=>$this->post('closing_date'),
		'pickup_location'=>$this->post('pickup_location'),
		'drop_location'=>$this->post('drop_location'),
		'file_msds'=>$this->post('file_msds'),
		'file_packing_list'=>$this->post('file_packing_list'),
		'file_invoice'=>$this->post('file_invoice'),
		'file_bl'=>$this->post('file_bl'),
		'file_co'=>$this->post('file_co'),
		'file_other'=>$this->post('file_other'),
		'real_dest'=>$this->post('real_dest'),
		'route_type'=>$this->post('route_type'),
		'region_id'=>$shipper['region_id']);
		$data_detail = array(
		'booking_id' => $this->post('booking_code'),
        'shipper_name' => $this->post('shipper_name'),
        'shipper_address' => $this->post('shipper_address'),
        'consignee_name' => $this->post('consignee_name'),
        'consignee' => $this->post('consignee'),
		'notify_name'=>$this->post('notify_name'),
        'notify' => $this->post('notify'),
        'package_type' => json_encode($this->post('package_type')),
        'quantity' => $this->post('quantity'),
        'dimension' => json_encode($this->post('dimension')),
		'volume' => $this->post('volume'),
        'weight' => $this->post('weight'),
        'netto' => $this->post('netto'),
        'container_type' => $this->post('container_type'),
        'shipping_mark' => $this->post('shipping_mark'),
        'marking' => $this->post('marking'),
        'description_print' => $this->post('description_print'),
        'description_of_goods' => $this->post('description_of_goods'),
		'created_at' => date("Ymd"),
		'updated_at' => '',
		'region_id'=>$shipper['region_id']);
        $response = $this->m_api->create_quotation($data,$data_detail);
		
			$to = $this->booking_model->get_shipper_details($shipper['shipper']);
			$sales = $to->sales;
			$to_sales = $this->dbx->get_where('mstadmin', array('user_id' => $sales))->row();
			//print_r($to_sales);
			//die();
			$dataEmail = array(
				'nama' => $to->shipper_name,
				'nama_sales' => $to_sales->fullname,
			);
			$html = $this->load->view('emails/index.php', $dataEmail,TRUE);
			/* if ($shipper['region_id']=='Jakarta'){
			$response_email = send_email_elastic("Request from - ".$dataEmail['nama'], $html, array($to_sales->email,"sales.support1@gateway-id.com","sales.support2@gateway-id.com"), "");
			}else{
			$response_email = send_email_elastic("Request from - ".$dataEmail['nama'], $html, array($to_sales->email), "");
			} */
	
			
        $this->response($response);
    }
	
	public function index_put(){
		$shipper = $this->m_instant_booking->get_shipper_detail($this->put('user_first_name'));
		//print_r($shipper);
		//die();
		if ($shipper !== 'Username does not exist'){
        $data = array(
		'type_of_booking' => $this->put('type_of_booking'),
        'booking_code' => $this->put('booking_code'),
        'mode' => $this->put('mode'),
        'movement_type' => $this->put('movement_type'),
        'incoterm' => $this->put('incoterm'),
        'origin_country' => $this->put('origin_country'),
        'origin_city' => $this->put('origin_city'),
        'destination_country' => $this->put('destination_country'),
		'destination_city' => $this->put('destination_city'),
        'trans_city' => $this->put('origin_city'),
        'insurance' => $this->put('insurance'),
        'trucking' => $this->put('trucking'),
        'freight' => $this->put('freight'),
        'clearance' => $this->put('clearance'),
		'peb'=>$this->put('peb'),
        'cargo_type' => $this->put('cargo_type'),
		'consignee_id' => $this->put('consignee'),
		'notify_id' => $this->put('notify'),
		'number_of_bl' => $this->put('number_of_bl'),
		'status' => $this->put('status'),
		'total' => $this->put('total'),
		'expect_etd' => $this->put('expected_etd'),
		'eta' => $this->put('eta'),
		'warehouse' => $this->put('warehouse'),
		'si_number' => $this->put('si_number'),
		'remark' => $this->put('remark'),
		'closing_date'=>$this->put('closing_date'),
		'pickup_location'=>$this->put('pickup_location'),
		'drop_location'=>$this->put('drop_location'),
		'file_msds'=>$this->put('file_msds'),
		'file_packing_list'=>$this->put('file_packing_list'),
		'file_invoice'=>$this->put('file_invoice'),
		'file_bl'=>$this->put('file_bl'),
		'file_co'=>$this->put('file_co'),
		'file_other'=>$this->put('file_other'),
		'region_id'=>$shipper['region_id']);
		
		$data_detail = array(
		'booking_id' => $this->put('booking_code'),
        'shipper_name' => $this->put('shipper_name'),
        'shipper_address' => $this->put('shipper_address'),
        'consignee_name' => $this->post('consignee_name'),
        'consignee' => $this->post('consignee'),
		'notify_name'=>$this->post('notify_name'),
        'package_type' => json_encode($this->put('package_type')),
        'quantity' => $this->put('quantity'),
        'dimension' => json_encode($this->put('dimension')),
		'volume' => $this->put('volume'),
        'weight' => $this->put('weight'),
        'netto' => $this->put('netto'),
        'container_type' => $this->put('container_type'),
        'shipping_mark' => $this->put('shipping_mark'),
        'marking' => $this->put('marking'),
        'description_print' => $this->put('description_print'),
        'description_of_goods' => $this->put('description_of_goods'),
		'updated_at'=>date("Ymd"));
        $response = $this->m_api->update_quotation($data,$data_detail);
        $this->response($response);
		if ($this->put('status')=='finalize'){
		$filename = "Booking Confirmation-".$this->put('si_number').".pdf";
		$this->cetak_bc_api($data,$data_detail, $filename);
		$booking=$this->booking_model->find($data['booking_code']);
		$to = $this->booking_model->get_shipper_details($booking->shipper_id);
		$dataEmail = array('nama' => $to->shipper_name, 'booking_code' => $data['booking_code']);
		$html = $this->load->view('emails/confirmation.php',$dataEmail,TRUE);
		$response_email = send_email_elastic("Booking Confirmation - ".$data['si_number'], $html, array($to->cp_email), $filename);
		if($response_email['code'] == 500){
				return $this->output->set_status_header(500)
				->set_content_type('application/json')
				->set_output(json_encode($response_email['response']));
			}else{
				echo json_encode($response);	
			}
		}else{
		echo json_encode($response);		
		}
    }else{
		echo json_encode($this->failed_shipper());
	}	
	
    }
	
	public function index_delete(){
        $id = $this->delete('booking_code');
        $response = $this->m_api->delete_quotation($id);
		//print_r($this->dbx->affected_rows());
		//die();
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
	public function failed_shipper(){
    $response='Username does not existing';
    return $response;
	}
} 
?>