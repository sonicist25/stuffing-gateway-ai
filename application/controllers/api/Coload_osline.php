<?php
use chriskacerguis\RestServer\RestController;
 
class Coload_osline extends RestController{
      
    public function __construct(){
        parent::__construct();
        $this->load->model(array('api/m_instant_booking','m_bill_of_ladding','m_print_out_bl','pricelist/booking_model'));
    }
	
	public function index_post(){
		$shipper = $this->m_instant_booking->get_shipper_detail('OCEAN SKY LINE');
		//print_r($shipper);
		//die();
		if ($shipper !== 'Username does not exist'){
		$portTrans = (!empty($data['trans_city']) && $data['trans_city'] !== "null") ? $data['trans_city'] : '';	
        $data = array(
		'type_of_booking'=>'EXPORT',
   			'booking_code'=>$this->input->post('booking_code'),
   			'mode'=>$this->input->post('mode'),
   			'movement_type'=>$this->input->post('movement_type'),
			'si_number'=>$this->input->post('si_number'),
			'origin_country'=>'INDONESIA',
			'incoterm'=>$this->input->post('incoterm'),
   			'origin_city'=>$this->input->post('origin_city'),
   			'trans_city'=>$portTrans,
   			'destination_city'=>$this->input->post('destination_city'),
			'forwarder_name'=>$shipper['shipper_print'],
			'shipper_id'=>$shipper['shipper'],
			'sales'=>$shipper['sales'],
			'created_by'=>$shipper['shipper_print'],
			'consignee_id'=>$this->input->post('consignee_id'),
			'notify_id'=>$this->input->post('notify_id'),
			'status'=>'finalize',
			'freight'=>$this->input->post('freight'),
			'number_of_bl'=>$this->input->post('number_of_bl'),
			'expect_etd'=>$this->input->post('expect_etd'),
			'eta'=>$this->input->post('eta'),
			'closing_date'=>$this->input->post('closing_doc'),
			'closing_cargo'=>$this->input->post('closing_car'),
			'vessel'=>$this->input->post('vessel'),
			'voyage'=>$this->input->post('voyage'),
			'warehouse'=>$this->input->post('warehouse'),
			'pic_document'=>$this->session->userdata('fullname'),
			'updated_at' =>date("Y-m-d H:i:s"),
			'region_id'=>'Jakarta');
		$data_detail = array(
		'booking_id' => $this->input->post('booking_code'),
			'package_type' => $this->input->post('package_type'),
			'quantity' => $this->input->post('quantity'),
			'volume' => $this->input->post('volume'),
			'weight' => $this->input->post('weight'),
			'netto' => $this->input->post('netto'),
			'shipper_name'=>$shipper['shipper_print'],
			'shipper_address'=>$shipper['shipper_address'],
			'consignee_name'=>$this->input->post('consignee_name'),
			'consignee'=>$this->input->post('consignee'),
			'notify_name'=>$this->input->post('notify_name'),
			'notify'=>$this->input->post('notify'),
			'shipping_mark' => $this->input->post('shipping_mark'),
			'marking' => $this->input->post('marking'),
			'description_print' => $this->input->post('description_print'),
			'description_of_goods' => $this->input->post('desc_of_goods'),
			'cp_name' => $this->input->post('cp_name'),
			'cp_email' => $this->input->post('cp_email'),
			'created_at' =>date("Y-m-d H:i:s"),
			'region_id'=>'Jakarta');
        $response = $this->m_instant_booking->create_quotation($data,$data_detail);
		if($response){
			echo json_encode($response);
		}
        }else{
		echo json_encode($this->failed_shipper());
		}
    }
	
	public function failed_shipper(){
    $response='Username does not exist';
    return $response;
	}
	
} 
?>