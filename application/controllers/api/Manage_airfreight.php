<?php
 
use chriskacerguis\RestServer\RestController;
 
class Manage_airfreight extends RestController{
     
    public function __construct(){
        parent::__construct();
        $this->load->model(array('api/m_api'));
    }
     
    public function index_get(){
		$id = $this->get('booking_id');
		if($id===null){
        $response = $this->m_api->read_awb();
		}else{
		$response = $this->m_api->read_awb($id);
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
		'currency'=>$this->post('currency'),
		'value_for_custom'=>$this->post('value_for_custom'),
		'value_for_carrier'=>$this->post('value_for_carrier'));

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
		
        $response = $this->m_api->create_booking_airfreight($data,$data_detail);
        $this->response($response);
		}
		public function index_put(){
		$booking_code = $this->put('booking_code');
		//$kode_ref = $this->put('kode_ref');
		//print_r($booking_code);
		//die;
        $data = array(
            'booking_code'=> $this->put('booking_code'),
            'shipper_name'=> $this->put('shipper_print'),
            'shipper_address'=> $this->put('shipper_add'),
            'consignee'=> $this->put('consignee_name'),
            'consignee_add'=> $this->put('consignee_add'),
            'departure'=> $this->put('departure'),
            'route_to'=> $this->put('route_to'),
            'feeder_vessel'=> $this->put('by_first_carrier'),
            'feeder_vessel_etd'=> $this->put('to_1'),
            'feeder_vessel_eta'=> $this->put('by_1'),
			'snd_vessel_etd'=> $this->put('to_2'),
            'snd_vessel_eta'=> $this->put('by_2'),
            'destination'=> $this->put('destination'),
            'feeder_vessel_etd_date'=> $this->put('flight_date_1'),
            'feeder_vessel_eta_date'=> $this->put('flight_date_2'),
            'handling_information'=> $this->put('handling_information'),
            'sci'=> $this->put('sci'),
            'charge_weight_prep'=> $this->put('charge_weight_prep'),
            'charge_weight_col'=> $this->put('charge_weight_col'),
            'valuation_charge_prep'=> $this->put('valuation_charge_prep'),
            'valuation_charge_col'=> $this->put('valuation_charge_col'),
            'tax_pre'=> $this->put('tax_pre'),
            'tax_col'=> $this->put('tax_col'),
            'other_charge_due_agent_pre'=> $this->put('other_charge_due_agent_pre'),
            'other_charge_due_agent_col'=> $this->put('other_charge_due_agent_col'),
            'other_charge_due_carrier_pre'=> $this->put('other_charge_due_carrier_pre'),
            'other_charge_due_carrier_col'=> $this->put('other_charge_due_carrier_col'),
            'total_prep'=> $this->put('total_prep'),
            'total_col'=> $this->put('col'),
            'currency_conve_rates'=> $this->put('currency_conve_rates'),
            'cc_charges_destination'=> $this->put('cc_charges_destination'),
            'charges_at_des'=> $this->put('charges_at_des'),
            'total_collect'=> $this->put('total_collect'),
            'acc_information_detail'=> $this->put('acc_information_detail'),
            'currency_declaration'=> $this->put('currency_declaration'),
            'chcg'=> $this->put('chcg'),
            'value_for_carriage'=> $this->put('value_for_carriage'),
            'value_for_custom'=> $this->put('value_for_custom'),
            'amount_of_insurance'=> $this->put('amount_of_insurance'),
            'wt_val'=> $this->put('wt_val'),
            'other_prep_col'=> $this->put('other_prep_col'),
            'other_charges_detail'=> $this->put('other_charges_detail'),
            'other_charges_amount'=> $this->put('other_charges_amount'),
            'other_charges_entitlement'=> $this->put('other_charges_entitlement'),
            'shipper_certification'=> $this->put('shipper_certification'),
            'shipper_signatures'=> $this->put('shipper_signatures'),
            'carrier_execution'=> $this->put('carrier_execution'),
			'place'=> $this->put('place'),
            'signature'=> $this->put('signature'),
            'date'=> $this->put('date'));
	$data_detail = array(
            'booking_code'=> $this->put('booking_code'),
            'piece'=> $this->put('piece'),
            'weight'=> $this->put('gross_weight'),
            'rate_class'=> $this->put('rate_class'),
            'item_number'=> $this->put('item_number'),
            'chgs_weight'=> $this->put('charges_weight'),
            'rate_per_charges'=> $this->put('rate_per_charges'),
            'total'=> $this->put('total'),
            'marking_nos'=> $this->put('marking_nos'),
            'desc_goods'=> $this->put('nature_and_quantity'),
            'lenght_1'=> $this->put('lenght_1'),
            'width_1'=> $this->put('width_1'),
            'height_1'=> $this->put('height_1'),
            'pieces_1'=> $this->put('pieces_1'),
            'weight_1'=> $this->put('weight_1'),
			'lenght_2'=> $this->put('lenght_2'),
            'width_2'=> $this->put('width_2'),
            'height_2'=> $this->put('height_2'),
            'pieces_2'=> $this->put('pieces_2'),
            'weight_2'=> $this->put('weight_2'),
			'lenght_3'=> $this->put('lenght_3'),
            'width_3'=> $this->put('width_3'),
            'height_3'=> $this->put('height_3'),
            'pieces_3'=> $this->put('pieces_3'),
            'weight_3'=> $this->put('weight_3'),
			'lenght_4'=> $this->put('lenght_4'),
            'width_4'=> $this->put('width_4'),
            'height_4'=> $this->put('height_4'),
            'pieces_4'=> $this->put('pieces_4'),
            'weight_4'=> $this->put('weight_4'));
        $response = $this->m_api->update_awb($data,$data_detail,$booking_code);
        $this->response($response);
    }
} 
?>