<?php
class Afg_c_dropdown extends CI_Controller
{
	var $title = 'Stuffing Plan';
	
	public function __construct(){
		parent::__construct();
		$this->fungsi->restrict();
		$this->load->model(array(
			'afg_m_cities',
			'afg_m_ref',
			'afg_m_income',
			'afg_m_cost',
			'user_model',
			'afg_m_stuffing_plan',			
			'stuffing_ppjk',
			'reference/port_destination_model',
			'reference/agent_company_model',
			'reference/type_service_model',
			'reference/type_container_model',
			'reference/baserate_export_model',
			'reference/stuffing_location_model',
			'reference/shipping_company_model',
			'reference/type_incoterms_model')
		);	
		$this->fungsi->dbx = $this->user_model->dbx;
		$this->dbx = $this->user_model->dbx;		
	}
	
	function index(){
		die();
	}
	
	/* new update */
	
	function update_row_shipper() {
		$shipper = $this->user_model->get_data_shipper_update($_POST['code'],$_POST['kodeRef']);
		echo json_encode($shipper);
	}
	
	function dropdownSalesIdName($id=''){
		$sales = $this->user_model->get_data_sales();
		$data['option_sales']['']=' ';
    	if($sales->num_rows() > 0 ) {
			foreach ($sales->result() as $row)
			{	
				$data['option_sales'][$row->user_id] = $row->fullname;
			}
			echo form_dropdown('dropdownSalesId', $data['option_sales'], $id, 'id="dropdownSalesId" style="width: 100%"');
		}
		else 
		{
			echo form_dropdown('dropdownSalesId', $data['option_sales'], $id, 'id="dropdownSalesId" style="width: 100%"');
		}
	}
	
	/* end update */
	function ref_airport(){
		$airport = $this->user_model->getListAirport();
		if(empty($airport->result_array())){
			$data[]="";	
			}else{
			foreach ($airport->result_array() as $row)
			{	
				$data[]=$row['name'];
			}
			}
			echo json_encode($data);
	}

	function ref_airline(){
		$airline = $this->user_model->getListAirline();
		if(empty($airline->result_array())){
		$data[]="";	
		}else{
		foreach ($airline->result_array() as $row)
		{	
			$data[]=$row['name'];
		}
		}
		echo json_encode($data);
	}
	
	function airline(){
		$airline = $this->user_model->getAirline();
		if(empty($airline->result_array())){
		$data[]="";	
		}else{
		foreach ($airline->result_array() as $row)
		{	
			$data[]=strtoupper($row['Airline']);
		}
		}
		echo json_encode($data);
	}
	
	function dropdownIncoterms(){
		$incoterm = $this->type_incoterms_model->getListincoterms();
  		$data['incoterm']['']=' ';
    	foreach ($incoterm->result() as $row)
		{	
			$data['incoterm'][$row->id] = $row->incoterms_name;
		}
		echo form_dropdown('incoterm', $data['incoterm'], '', 'id="incoterm" style="width: 100%"');
	}
	function dropdown_prepared(){
		$prepared = $this->user_model->dropdown_prepared();
  		$data['prepared']['']=' ';
    	foreach ($prepared->result() as $row)
		{	
			$data['prepared'][$row->user_id] = strtoupper($row->fullname);
		}
		echo json_encode(form_dropdown('prepared', $data['prepared'], '', 'id="prepared" style="width: 100%"'));
	}
	function dropdown_account(){
		$account = $this->user_model->dropdown_account();
		//print_r($this->dbx->last_query());
		//die();
  		if(empty($account)){
		$data[]="";	
		}else{
		foreach ($account as $row)
		{	
			$data[]=array(
                'label' => $row['account_name'],
                'value' => $row['id']);
		}
		}
		echo json_encode($data);
	}
	function get_dropdown_year() {
		$prev_year = $_POST['prev_year'];
		$get_year = $_POST['get_year'];
		
		$thn = array(
			//$get_year - 3 => $get_year - 3,
			//$get_year - 2 => $get_year - 2,
			$get_year - 1 => $get_year - 1,
			$get_year => $get_year,
			//$get_year + 1 => $get_year + 1,
			//$get_year + 2 => $get_year + 2,
			//$get_year + 3 => $get_year + 3,
		);
		$data['option_thn']['']= '';
		foreach ($thn as $key => $value) {
			$data['option_thn'][$key]= $value;
		}	
		echo form_dropdown('combo_year',$data['option_thn'],$get_year,'id="combo_year"');
	}
	
	function dropdown_trucking()
	{
		//$select = $this->stuffing_location_model->trucking_co_selected($id);
		$select = '';
		$trucking = $this->stuffing_location_model->getStuffing_trucking()->result();
		$data['trucking']['']='';
		foreach ($trucking as $row)
		{	
			$data['trucking'][$row->trucking_company_id]=$row->trucking_company_name;
		}		
		echo form_dropdown('trucking_co',$data['trucking'],$select,'id="trucking_co" style="width: 100px"');			
	}
	
	function dropdown_location()
	{
		//$select = $this->stuffing_location_model->trucking_co_selected($id);
		$select = '';
		$location = $this->stuffing_location_model->getStuffing_location()->result();
		$data['location']['']=' ';
		foreach ($location as $row)
		{	
			$data['location'][$row->stuffing_location_id]=$row->stuffing_location_company;
		}		
		echo form_dropdown('location',$data['location'],$select,'id="location" style="max-width: 100px;"');}
	
	function live_search(){
		$data = $this->afg_m_income->live_search($_POST)->result_array();
		echo json_encode(array('hasil'=>$data));
	}
	
	function dropdownPayable(){
		$payable = $this->afg_m_cost->get_data_payable();
		//print_r($this->dbx->last_query());
		//die();
		if(empty($payable)){
		//print_r($this->dbx->last_query());
		//die();	
		$data[]="";	
		}else{
		foreach ($payable as $row)
		{	
			$data[]=$row['payable'];
		}
		}
		echo json_encode($data);
	}
		
	function dropdownReceiveFrom(){
		$receive = $this->afg_m_income->getReceiveFrom()->result();
		$data['option_receive'][' ']=' ';
		foreach ($receive as $row)
		{	
			$data['option_receive'][$row->receive_name]=$row->receive_name;
		}		
		echo form_dropdown('dropdownReceiveFrom',$data['option_receive'],'','id="dropdownReceiveFrom"');
	}
	
	function dropdown_destination(){
		//$destination = $this->port_destination_model->getListDestination()->result();
		$destination = $this->afg_m_cities->getListCitiesDrop()->result();
		$data['option_destination'][' ']=' ';
		foreach ($destination as $row)
		{	
			$data['option_destination'][$row->city_name]=$row->city_name;
		}		
		echo form_dropdown('dropdownDestination',$data['option_destination'],'','id="dropdownDestination"');
	}
	
	function dropdownDestination(){
		$destination = $this->port_destination_model->getListDestination()->result();
		$data['option_destination'][' ']=' ';
		foreach ($destination as $row)
		{	
			$data['option_destination'][$row->portdestination_name]=$row->portdestination_name;
		}		
		echo form_dropdown('dropdownDestination',$data['option_destination'],'','id="dropdownDestination"');
	}
		
	function dropdownAgent(){
		$agent = $this->agent_company_model->getListAgent()->result();
		$data['option_agent'][' ']='';
		foreach ($agent as $row)
		{	
			$data['option_agent'][$row->agent_company_code]=$row->agent_company_code;
		}		
		echo form_dropdown('dropdownAgent',$data['option_agent'],'','id="dropdownAgent"');
	}
	
	function dropdownAgentAjax(){
		$q = $this->input->get('q');
		$this->db->select('agent_company_code');
		$this->db->like('agent_company_code', $q);
		$this->db->where('region_id',$this->session->userdata("region_id"));
		$result = $this->db->get('refagentcompany')->result();

		echo json_encode($result);
	}

	
	function dropdownCities(){
		$cities = $this->afg_m_cities->getListCitiesDrop()->result();
		$data['option_cities'][' ']='';
		foreach ($cities as $row)
		{	
			$data['option_cities'][$row->city_name]=$row->city_name;
		}		
		echo form_dropdown('dropdownCities',$data['option_cities'],'','id="dropdownCities"');
	}
	
	function dropdown_cities(){
		$cities = $this->afg_m_cities->getListCitiesDrop()->result();
		$data['option_cities'][' ']='';
		foreach ($cities as $row)
		{	
			$data['option_cities'][$row->city_name]=$row->city_name;
		}		
		$value = form_dropdown('dropdownCities',$data['option_cities'],'','id="dropdownCities"');
		echo json_encode($value);
	}
	
	function dropdownService(){
		$agent = $this->type_service_model->getListService()->result();
		$data['option_service'][' ']='';
		foreach ($agent as $row)
		{	
			$data['option_service'][$row->typeservice_code]=$row->typeservice_code;
		}		
		echo form_dropdown('codeService',$data['option_service'],'','id="codeService"');
	}
	
	function dropdownItemService(){
		$agent = $this->type_service_model->getListService()->result();
		$data['option_service'][' ']=' ';
		foreach ($agent as $row)
		{	
			$data['option_service'][$row->typeservice_name]=$row->typeservice_name;
		}		
		echo form_dropdown('codeItemService',$data['option_service'],'','id="codeItemService"');
	}
	
	function dropdownContainer(){
		$agent = $this->type_container_model->getListContainer()->result();
		$data['option_container'][' ']='';
		foreach ($agent as $row)
		{	
			$data['option_container'][$row->typecontainer_name]=$row->typecontainer_name;
		}		
		echo form_dropdown('dropdownContainer',$data['option_container'],'','id="dropdownContainer"');
	}
	
	function dropdownShipCo(){
		$agent = $this->shipping_company_model->getListCompany()->result();
		$data['option_company'][' ']='';
		foreach ($agent as $row)
		{	
			$data['option_company'][$row->shipping_company_code]=$row->shipping_company_code;
		}		
		echo form_dropdown('dropdownShippingCompany',$data['option_company'],'','id="dropdownShippingCompany"');
	}
	
	function dropdown_shippco(){
		$searchTerm = $this->input->get('term');
		$carrier = $this->shipping_company_model->getListCompany($searchTerm);
		//print_r($this->dbx->last_query());
		//die();
		if(empty($carrier->result_array())){
		$data[]="";	
		}else{
		foreach ($carrier->result_array() as $row)
		{	
			$data[]=$row['shipping_company_code'];
		}
		}
		echo json_encode($data);
	}
	function dropdown_scac(){
		$searchTerm = $this->input->get('term');
		$carrier = $this->shipping_company_model->getListScac($searchTerm);
		//print_r($this->dbx->last_query());
		//die();
		if(empty($carrier->result_array())){
		$data[]="";	
		}else{
		foreach ($carrier->result_array() as $row)
		{	
			$data[]=$row['scac'];
		}
		}
		echo json_encode($data);
	}
	
	
	function dropdownBaserate(){
		$agent = $this->baserate_export_model->getListBaserate()->result();
		$data['option_baserate'][' ']='';
		foreach ($agent as $row)
		{	
			$data['option_baserate'][$row->baserate_export_code]=$row->baserate_export_code;
		}		
		echo form_dropdown('dropdownBaserate',$data['option_baserate'],'','id="dropdownBaserate"');
	}
	
	function dropdownStuffinglocation(){
		$agent = $this->stuffing_location_model->getStuffing_location()->result();
		$data['option_Stuffinglocation'][' ']='';
		foreach ($agent as $row)
		{	
			$data['option_Stuffinglocation'][$row->stuffing_location_company]=$row->stuffing_location_company;
		}		
		echo form_dropdown('dropdownStuffinglocation',$data['option_Stuffinglocation'],'','id="dropdownStuffinglocation"');
	}
	
	function dropdownWarehouse(){
		$warehouse = $this->afg_m_ref->getListWarehouse()->result();
		$data['option_warehouse'][' ']= ' ';
		foreach ($warehouse as $row)
			{	
				$data['option_warehouse'][$row->warehouse_code]=$row->warehouse_code;
			}
		echo form_dropdown('dropdownWarehouse',$data['option_warehouse'],'','id="dropdownWarehouse" disabled="disabled" style="width: 110px;"');
	}	
	
	function dropdown_warehouse_with_code(){
		$warehouse = $this->afg_m_ref->getListGudang();
		//print_r($this->dbx->last_query());
		//die();
		if(empty($warehouse->result_array())){
		$data[]="";
		}else{
		foreach ($warehouse->result_array() as $row)
		{	
		$data[]=array(
                'label' => $row['warehouse_code'],
                'value' => $row['warehouse_name']);
		}
		}	
		//print_r($data);
		//die();
		echo json_encode($data);
	}

	function dd_kode_ref(){
		$kode = $this->afg_m_stuffing_plan->ambil_kode()->result();
		$data['option_kode']['']=' ';
			foreach ($kode as $row) {	
				$data['option_kode'][$row->kode_ref]=$row->kode_ref;
			}
		echo  form_dropdown('dd_kode',$data['option_kode'],'','id="dd_kode" style="width: 170px;"');
	}

	function dd_type(){
		$type = $this->afg_m_stuffing_plan->ambil_kode2()->result();
			$data['option_type']['']=' ';
			foreach ($type as $row) {	
				$data['option_type'][$row->type_of_trans]=$row->type_of_trans;
			}
		echo  form_dropdown('dd_type',$data['option_type'],'','id="dd_type" style="width: 170px;"');
	}	

	function dd_shipper(){
		$shipper = $this->afg_m_stuffing_plan->ambil_kode()->result();
			$data['option_shipper']['']=' ';
			foreach ($shipper as $row) {	
				$data['option_shipper'][$row->shipper_print]=$row->shipper_print;
			}
		echo  form_dropdown('dd_shipper',$data['option_shipper'],'','id="dd_shipper" style="width: 170px;"');
	}

	function dd_consignee(){
		$consignee = $this->afg_m_stuffing_plan->ambil_kode()->result();
			$data['option_consignee']['']=' ';
			foreach ($consignee as $row) {	
				$data['option_consignee'][$row->consignee]=$row->consignee;
			}
			echo  form_dropdown('dd_consignee',$data['option_consignee'],'','id="dd_consignee" style="width: 170px;"');
	}

	function dd_awb(){
		$awb = $this->afg_m_stuffing_plan->ambil_kode2()->result();
			$data['option_awb']['']=' ';
			foreach ($awb as $row) {	
				$data['option_awb'][$row->mother_bl]=$row->mother_bl;
			}
			echo  form_dropdown('dd_awb',$data['option_awb'],'','id="dd_awb" style="width: 170px;"');
	}

	function dd_hb_l(){
		$hb_l = $this->afg_m_stuffing_plan->ambil_kode()->result();
			$data['option_hb_l']['']=' ';
			foreach ($hb_l as $row) {	
				$data['option_hb_l'][$row->hb_l]=$row->hb_l;
			}
			echo  form_dropdown('dd_hb_l',$data['option_hb_l'],'','id="dd_hb_l" style="width: 80px;"');
	}

	function dd_etd(){
		$etd = $this->afg_m_stuffing_plan->ambil_kode2()->result();
			$data['option_etd']['']=' ';
			foreach ($etd as $row) {	
				$data['option_etd'][$row->etd_jkt]=$row->etd_jkt;
			}
			echo  form_dropdown('dd_etd',$data['option_etd'],'','id="dd_etd" style="width: 120px;"');
	}

	function item_service(){
		$code = $this->input->post('code_service');
		$data = $this->type_service_model->getDataItemService($code);
		echo json_encode(array('item_service'=>$data));
	}
	
	function item_service_nya(){
		$code = $this->input->post('code_service');
		$data = $this->type_service_model->getDataItemService($code);
		echo json_encode($data);
	}
	
	function get_item_service_nya(){
		$data = $this->type_service_model->get_item_service_nya($_POST);
		echo json_encode($data);
	}
	
	function get_last_refno(){
		$data= '123';
		echo json_encode($data);
	}
	
	function dropdownUser(){
		$this->dbx->select('user_id,fullname');
		$sales = $this->dbx->get_where('mstadmin',array('username !='=>'root','region_id'=>$this->session->userdata('region_id')));
  		$data['option_sales']['']=' ';
    	if($sales->num_rows() > 0 ) {
			foreach ($sales->result() as $row)
			{	
				$data['option_sales'][$row->user_id] = $row->fullname;
			}
			echo form_dropdown('dropdownSales', $data['option_sales'], '', 'id="dropdownSales" style="width: 100%"');
		}
		else 
		{
			echo form_dropdown('dropdownSales', $data['option_sales'], '', 'id="dropdownSales" style="width: 100%"');
		}
	}
	
	function dropdownSales(){
		$sales = $this->user_model->get_data_sales();
    	if($sales->num_rows() > 0 ) {
			foreach ($sales->result() as $row)
			{	
				$data['option_sales'][$row->user_id] = $row->fullname;
			}
			echo form_dropdown('dropdownSales', $data['option_sales'], [], 'id="dropdownSales" style="width: 100%"');
		}
		else 
		{
			echo form_dropdown('dropdownSales', $data['option_sales'],'', 'id="dropdownSales" style="width: 100%"');
		}
	}
	
	function dropdownInvoiceAR(){
		$searchTerm = $this->input->get('term');
		$shipperTerm = $this->input->get('code');
		if($searchTerm){
		$inv_ar = $this->user_model->get_data_invoice($searchTerm,$shipperTerm);
		foreach ($inv_ar->result() as $row) {
            $data[] = array('id' => $row->id,'number' => $row->number,'job_no' => $row->job_no,'kepada'=>$row->kepada, 'kepada_kode'=>$row->kepada_kode,'currency' => $row->currency,'grand_total' => $row->grand_total);
        }
		}else{
		$data =[];	
		}
        // Return the result as JSON
        echo json_encode($data);
	}
	
public function dropdownJobAP() {
    // Get the search term from query parameter
    $searchTerm = $this->input->get('q');

    // Initialize the data array
    $data = [];

    // Check if search term is provided
    if ($searchTerm) {
        // Retrieve data based on the search term
        $inv_ar = $this->user_model->get_data_pay($searchTerm);
		//print_r($inv_ar);
        // Iterate through each sub-array in the $inv_ar array
        foreach ($inv_ar as $subArray) {
            // Iterate through each object in the current sub-array
            foreach ($subArray as $object) {
                // Add the object data to the $data array
                if (is_object($object)) {
                    $data[] = array(
                        'kode_ref' => $object->kode_ref,
                        'item_service' => $object->item_service,
                        'payable_to' => $object->payable_to,
                        'status' => $object->status,
                        'price' => $object->price,
                        'currency' => $object->currency,
                        'id' => $object->id,
                        'table_name' => $object->table_name,
                    );
                }
            }
        }
    }

    // Return the result as JSON
    echo json_encode($data);
}
	
	function dropdownSalesId(){
		$sales = $this->user_model->get_data_sales();
  		$data['option_sales']['']=' ';
    	if($sales->num_rows() > 0 ) {
			foreach ($sales->result() as $row)
			{	
				$data['option_sales'][$row->user_id] = $row->user_id;
			}
			echo form_dropdown('dropdownSalesId', $data['option_sales'], '', 'id="dropdownSalesId" style="width: 100%"');
		}
		else 
		{
			echo form_dropdown('dropdownSalesId', $data['option_sales'], '', 'id="dropdownSalesId" style="width: 100%"');
		}
	}
	
	function dropdown_shipper($edit){
		$shipper = $this->user_model->get_data_shipper();
  		$data['option_shipper']['']=' ';
    	if($shipper->num_rows() > 0 ) {
			foreach ($shipper->result() as $row)
			{	
				if($edit=='shipper_code'){
					$data['option_shipper'][$row->shipper_id] = $row->shipper_id;
				} 
				else if($edit=='shipper_id'){
					$data['option_shipper'][$row->shipper_id] = $row->shipper_name;
				}
				else 
				{
					$data['option_shipper'][$row->shipper_name] = $row->shipper_name;
				}
			}
			echo form_dropdown('dropdown_shipper', $data['option_shipper'], '', 'id="dropdown_shipper" style="width: 100%"');
		}
		else 
		{
			echo form_dropdown('dropdown_shipper', $data['option_shipper'], '', 'id="dropdown_shipper" style="width: 100%"');
		}
	}
	
	function dropdown_shipper_print(){
		$shipper = $this->user_model->get_data_shipper_print();
		if(empty($shipper->result_array())){
		$data[]="";	
		}else{
		foreach ($shipper->result_array() as $row)
		{	
			$data[]=$row['shipper_name'];
		}
		}
		echo json_encode($data);
	}
function dropdown_shipper_with_id(){
    $shipper = $this->user_model->get_data_shipper_print();
    $data = array();

    if($shipper->num_rows() > 0){
        $rows = $shipper->result_array();

        // Tandai shipper coloader (Osline/Winfast/HNS/cabang Gateway), lihat libraries/Coloader_guard.php
        $this->load->library('coloader_guard');
        $coloaders = $this->coloader_guard->classify_many($this->dbx, array_column($rows, 'id_shipper'));
        $labels = array('auto' => 'otomatis dari ', 'branch' => 'akun cabang ', 'manual' => 'coloader ');

        foreach ($rows as $row) {	
            $item = array(
                // Kita masukkan Nama ke dalam properti 'id'
                'id'   => $row['shipper_name'], 
                'text' => $row['shipper_name'],
                // Kita simpan ID aslinya di properti tambahan untuk hidden input
                'real_id' => $row['id_shipper'] 
            );
            $c = isset($coloaders[(int) $row['id_shipper']]) ? $coloaders[(int) $row['id_shipper']] : null;
            if ($c) {
                $item['text']             .= ' (' . $labels[$c['mode']] . ($c['mode'] === 'branch' ? 'Gateway' : $c['company_name']) . ')';
                $item['coloader_mode']     = $c['mode'];
                $item['coloader_company']  = $c['company_name'];
                $item['coloader_prefix']   = $c['bc_prefix'];
                $item['coloader_message']  = $c['message'];
            }
            $data[] = $item;
        }
    }
    echo json_encode($data);
}
	function dropdown_consignee_with_id(){
		$consignee = $this->user_model->get_data_consignee();
		if(empty($consignee->result_array())){
		$data[]="";	
		}else{
		foreach ($consignee->result_array() as $row)
		{	
			$data[]=array(
                'label' => $row['consignee_notify_name'],
                'value' => $row['consignee_notify_id']);
		}
		}
		echo json_encode($data);
	}
	function dropdown_location_with_code(){
		$location = $this->afg_m_cities->getListLocation();
		//print_r($this->dbx->last_query());
		//die();
		if(empty($location->result_array())){
		$data[]="";
		}else{
		foreach ($location->result_array() as $row)
		{	
		$data[]=array(
                'label' => $row['city_name'],
                'value' => $row['city_id']);
		}
		}	
		//print_r($data);
		//die();
		echo json_encode($data);
	}
	function dropdown_shipper_import_print(){
		$shipper = $this->user_model->get_data_shipper_import();
		if(empty($shipper->result_array())){
		$data[]="Data not found";	
		}else{
		foreach ($shipper->result_array() as $row)
		{	
			$data[]=$row['consignee_import_name'];
		}
		}
		echo json_encode($data);
	}
	
	function dropdown_shipper_import(){
		$shipper = $this->user_model->get_data_shipper_import();
  		$data['consignee']['']=' ';
    	foreach ($shipper->result() as $row)
		{	
			$data['consignee'][$row->consignee_import_code] = $row->consignee_import_name;
		}
		echo form_dropdown('consignee', $data['consignee'], '', 'id="consignee" style="width: 100%"');
	}
	
	function dropdown_shipper_search($search){
		$shipper = $this->user_model->get_data_shipper();
  		$data['option_sales']['']=' ';
    	if($shipper->num_rows() > 0 ) {
			foreach ($shipper->result() as $row)
			{	
				if($search=='shipper_code'){
					$data['option_shipper'][$row->shipper_id] = $row->shipper_id;
				} else {
					$data['option_shipper'][$row->shipper_name] = $row->shipper_name;
				}
			}
			echo json_encode(form_dropdown('dropdown_shipper', $data['option_shipper'], '', 'id="dropdown_shipper" style="width: 100%"'));
		}
		else 
		{
			echo json_encode(form_dropdown('dropdown_shipper', $data['option_shipper'], '', 'id="dropdown_shipper" style="width: 100%"'));
		}
	}
	
	function dropdown_port(){
		$port = $this->user_model->get_data_port();
		//print_r($this->dbx->last_query());
		if(empty($port->result_array())){
		$data[]="";	
		}else{
		foreach ($port->result_array() as $row)
		{	
			$data[]=$row['city_name'];
		}
		}
		echo json_encode($data);
	}
	
	function dropdown_port_jadul(){
		$port = $this->user_model->get_data_port_jadul();
		if(empty($port->result_array())){
		$data[]="";	
		}else{
		foreach ($port->result_array() as $row)
		{	
			$data[]=$row['city_name'];
		}
		}
		echo json_encode($data);
	}
	
	function dropdown_airport(){
		$airport = $this->user_model->get_data_airport();
		//print_r($this->dbx->last_query());
		if(empty($airport->result_array())){
		$data[]="";	
		}else{
		foreach ($airport->result_array() as $row)
		{	
			$data[]=$row['city_name'];
		}
		}
		echo json_encode($data);
	}
	
	function get_airport_code(){
		$this->dbx->select('iata_code')->where('name', $this->input->get('name'));
		$airport = $this->dbx->get('ref_airport')->row();
		//print_r($this->dbx->last_query());
		if(empty($airport)){
		$data="";	
		}else{
		$data=$airport;
		}
		echo json_encode($data);
	}
	
	public function get_port_code() {
		$name = $_GET['name'];

		$this->dbx->where('name', $name);
		$this->dbx->where('is_active', 'TRUE');
		$query = $this->dbx->get('tableName'); // ganti nama tabelmu
		
		//print_r($this->dbx->last_query());
		//die();

		if ($query->num_rows() > 0) {
			echo json_encode(['code' => $query->row()->code]);
		} else {
			echo json_encode(['code' => null]);
		}
}
	
	function airline_code(){
		$this->dbx->select('IATA')->where('Airline', $this->input->get('name'));
		$airport = $this->dbx->get('airlines')->row();
		//print_r($this->dbx->last_query());
		if(empty($airport)){
		$data="";	
		}else{
		$data=$airport;
		}
		echo json_encode($data);
	}
	
public function get_region_options() {
    $today = date('Y-m-d');

    // Query pertama: dari tbl_schedules_direct
    $this->dbx->distinct()->select('region_id')
        ->where('isDeleted', 0)
        ->where('etd_jkt >=', $today);
    $query1 = $this->dbx->get_compiled_select('tbl_schedules_direct');

    // Query kedua: dari tbl_schedules_via
    $this->dbx->distinct()->select('region_id')
        ->where('isDeleted', 0)
        ->where('etd_jkt >=', $today);
    $query2 = $this->dbx->get_compiled_select('tbl_schedules_via');

    // Gabungkan dengan UNION
    $union_query = $this->dbx->query("$query1 UNION $query2");

    // Ambil hasil sebagai array
    $result = array_column($union_query->result_array(), 'region_id');

    echo json_encode($result);
}

public function dropdown_job_3() {
    $term = $this->input->get('q'); // ambil query dari URL ?q=

    $this->dbx->select('sp_id,code,kode_ref,hb_l');
    $this->dbx->where('region_id', $this->session->userdata('region_id'));

    if (!empty($term)) {
        $this->dbx->like('kode_ref', $term); // search berdasarkan input
		 $this->dbx->like('kode_ref', '-3');
    } else {
        $this->dbx->like('kode_ref', '-3'); // default
    }

    $this->dbx->limit(20); // limit hasil
    $query = $this->dbx->get('trnstuffingplandetail');

    echo json_encode($query->result_array());
}

public function dropdown_job() {
    $term = $this->input->get('q'); // ambil query dari URL ?q=

    $this->dbx->select('sp_id,code,kode_ref,hb_l');
    $this->dbx->where('region_id', $this->session->userdata('region_id'));

    if (!empty($term)) {
        $this->dbx->like('kode_ref', $term); // search berdasarkan input
    } else {
        $this->dbx->like('kode_ref', ''); // default
    }

    $this->dbx->limit(20); // limit hasil
    $query = $this->dbx->get('trnstuffingplandetail');

    echo json_encode($query->result_array());
}


public function dropdown_airline() {
    $this->dbx->select('code,name');
    $query = $this->dbx->get('ref_airline');
    echo json_encode($query->result_array()); // jangan array_column
}

public function dropdown_airport_d() {
    $this->dbx->select('iata_code,name');
    $query = $this->dbx->get('ref_airport');
    echo json_encode($query->result_array()); // jangan array_column
}

public function dropdown_liner() {
    $this->dbx->select('code, scac, name');
    //$this->dbx->where('region_id', $this->session->userdata('region_id'));
    $query = $this->dbx->get('ref_scac_lcl');
    echo json_encode($query->result_array()); // jangan array_column
}

public function dropdown_liner_select2() {
    $q = $this->input->get('q'); // Tangkap input pencarian

    $this->dbx->select('code, scac, name');
    //$this->dbx->where('region_id', $this->session->userdata('region_id'));
    
    if (!empty($q)) {
        $this->dbx->group_start();
        $this->dbx->like('scac', $q);
        $this->dbx->or_like('name', $q);
        $this->dbx->or_like('code', $q);
        $this->dbx->group_end();
    }
    
    $this->dbx->limit(30);
    $query = $this->dbx->get('ref_scac');
    echo json_encode($query->result_array());
}

public function dropdown_liner_schedule() {
    $q = $this->input->post('q'); // Tangkap input pencarian

    $this->dbx->select('code, scac, name');
    //$this->dbx->where('region_id', $this->session->userdata('region_id'));
    
    if (!empty($q)) {
        $this->dbx->group_start();
        $this->dbx->like('scac', $q);
        $this->dbx->or_like('name', $q);
        $this->dbx->or_like('code', $q);
        $this->dbx->group_end();
    }
    
    $this->dbx->limit(30);
    $query = $this->dbx->get('ref_scac');
    echo json_encode($query->result_array());
}

public function dropdown_trucking_vendor() {
    $this->dbx->select('trucking_company_code, trucking_company_name');
    $this->dbx->where('region_id', $this->session->userdata('region_id'));
    $query = $this->dbx->get('reftruckingcompany');
    echo json_encode($query->result_array()); // jangan array_column
}

public function dropdown_coloader() {
    $this->dbx->select('shipping_company_code, shipping_company_name');
    $this->dbx->where('shipping_company_type', 'COLOADER');
    $this->dbx->where('region_id', $this->session->userdata('region_id'));
    $query = $this->dbx->get('refshippingcompany');
    echo json_encode($query->result_array()); // jangan array_column
}

public function dropdown_agent_d() {
    $this->dbx->select('agent_company_code,agent_company_name,agent_company_city');
    $this->dbx->where('region_id', $this->session->userdata('region_id'));
    $query = $this->dbx->get('refagentpenagihan');
    echo json_encode($query->result_array()); // jangan array_column
}
public function dropdown_scac_d() {
    $this->dbx->select('scac,name');
    $query = $this->dbx->get('ref_scac');
    echo json_encode($query->result_array()); // jangan array_column
}

public function dropdown_warehouse_a() {
    // Tangkap parameter 'q' dari AJAX Select2 (karena JS di atas pakai POST, gunakan input->post)
    $q = $this->input->post('q'); 

    $this->dbx->select('warehouse_code, warehouse_name');
    $this->dbx->where('region_id', $this->session->userdata('region_id'));
    
    // Jika ada kata kunci pencarian, filter datanya
    if (!empty($q)) {
        $this->dbx->group_start(); // Gunakan group_start untuk menghindari konflik AND/OR dengan region_id
        $this->dbx->like('warehouse_code', $q);
        $this->dbx->or_like('warehouse_name', $q);
        $this->dbx->group_end();
    }

    // Batasi limit agar query tidak berat jika data banyak
	$this->dbx->order_by('warehouse_name');
    $this->dbx->limit(50); 
    
    // Ganti 'foobar' dengan nama tabel warehouse yang sebenarnya
    $query = $this->dbx->get('foobar'); 
    echo json_encode($query->result_array());
}

public function dropdown_data_city() {
    $q = $this->input->post('q'); 
    $data = $this->afg_m_cities->get_filtered_data($q);
	//print_r($this->dbx->last_query());
    $response = [];
    foreach ($data->result() as $row) {
        $response[] = [
            'id' => $row->name,
            'text' => $row->name,
			'code' => $row->code // Menggabungkan name dan code
        ];
    }

    echo json_encode($response);
}
public function dropdown_warehouse_d() {
    $this->dbx->select('warehouse_code,warehouse_name');
    $this->dbx->where('region_id', $this->session->userdata('region_id'));
    $query = $this->dbx->get('foobar');
    echo json_encode($query->result_array()); // jangan array_column
}
public function dropdown_warehouse_airport() {
    $this->dbx->select('warehouse_code, warehouse_name');
    $query = $this->dbx->get('ref_airfreight_warehouses');
    echo json_encode($query->result_array()); // jangan array_column
}
public function dropdown_warehouse_import() {
    $this->dbx->select('warehouse_code, warehouse_name');
    $this->dbx->where('position', 'Gudang Import');
	$this->dbx->where('region_id', $this->session->userdata('region_id'));
    $query = $this->dbx->get('refwarehouse');
    echo json_encode($query->result_array()); // jangan array_column
}
public function dropdown_cy() {
    $this->dbx->select('kode_cy,nama_cy');
    $query = $this->dbx->get('refcontainer_yard');
    echo json_encode($query->result_array()); // jangan array_column
}

public function dropdown_container_lcl(){
	$this->dbx->select('typecontainer_name');
    $query = $this->dbx->get('reftypecontainer');
    $result = array_column($query->result_array(), 'typecontainer_name');
    echo json_encode($result);
}

public function get_city_options() {
	$region = $this->input->get('region_id');
    $direct = $this->dbx->distinct()->select('city_id')->where('region_id', $region)->where('isDeleted', 0)->where('etd_jkt >=', date('Y-m-d'))->get('tbl_schedules_direct')->result_array();
    $via = $this->dbx->distinct()->select('eta_city_id')->where('region_id', $region)->where('isDeleted', 0)->where('etd_jkt >=', date('Y-m-d'))->get('tbl_schedules_via')->result_array();
		
    $cities = array_unique(array_merge(
        array_column($direct, 'city_id'),
        array_column($via, 'eta_city_id')
    ));
    echo json_encode($cities);
}

public function get_etd_options() {
    $region = $this->input->get('region_id');
    $city   = $this->input->get('city_identifier');

    $etds = [];

    if ($region && $city) {
        $date_limit = date('Y-m-d', strtotime('-10 days'));

        $this->dbx->select('etd_jkt')->distinct();
        $this->dbx->where('region_id', $region);
        $this->dbx->where('city_id', $city);
        $this->dbx->where('isDeleted', 0);
        $this->dbx->where('etd_jkt >=', $date_limit);
        $direct = $this->dbx->get('tbl_schedules_direct')->result_array();

        $this->dbx->select('etd_jkt')->distinct();
        $this->dbx->where('region_id', $region);
        $this->dbx->where('eta_city_id', $city);
        $this->dbx->where('isDeleted', 0);
        $this->dbx->where('etd_jkt >=', $date_limit);
        $via = $this->dbx->get('tbl_schedules_via')->result_array();

        $etds = array_values(array_unique(array_merge(
            array_column($direct, 'etd_jkt'),
            array_column($via, 'etd_jkt')
        )));
    }

    // hanya sekali echo
    echo json_encode($etds);
}



public function search_json() {
    $filters = [
        'region_id'      => $this->input->post('region_id'),
        'city_identifier'=> $this->input->post('city_identifier'),
        'etd_jkt'        => $this->input->post('etd_jkt')
    ];

    $this->load->model('afg_m_cities');
    $results = $this->afg_m_cities->search($filters);

    echo json_encode($results);
}

public function fetch_dropdown_port() {
    $searchTerm = $this->input->get('q');
    $data = $this->afg_m_cities->get_filtered_data($searchTerm);
	//print_r($this->dbx->last_query());
    $response = [];
    foreach ($data->result() as $row) {
        $response[] = [
            'id' => $row->id,
            'text' => $row->name,
			'code' => $row->code // Menggabungkan name dan code
        ];
    }

    echo json_encode($response);
}

public function fetch_dropdown_port_pricing() {
    $searchTerm = $this->input->get('q');
    $data = $this->afg_m_cities->get_filtered_data_pricing($searchTerm);
	//print_r($this->dbx->last_query());
    $response = [];
    foreach ($data->result() as $row) {
        $response[] = [
            'id' => $row->id,
            'text' => $row->text,
			'code' => $row->code // Menggabungkan name dan code
        ];
    }

    echo json_encode($response);
}

public function item_service_auto() {
        $term = $this->input->get('term'); // ambil parameter dari jQuery UI Autocomplete
        $result = $this->type_service_model->search_service($term);

        $data = [];
        foreach ($result as $row) {
            $data[] = [
                'id'    => $row['typeservice_id'],
                'value' => $row['typeservice_name'], // yang ditampilkan di input
                'label' => $row['typeservice_code'] . ' - ' . $row['typeservice_name'], // yang muncul di dropdown
                'ppn'   => $row['typeservice_ppn']
            ];
        }

        echo json_encode($data);
    }
	
	public function fetch_indonesia_destinations() {
        $q = $this->input->get('q');
        
        $this->db->select('d.id, CONCAT(d.name, ", ", r.name, ", ", p.name) as text');
        $this->db->from('reg_districts d');
        $this->db->join('reg_regencies r', 'r.id = d.regency_id');
        $this->db->join('reg_provinces p', 'p.id = r.province_id');
        
        if (!empty($q)) {
            $this->db->group_start();
            $this->db->like('d.name', $q);
            $this->db->or_like('r.name', $q);
            $this->db->group_end();
        }
        
        $this->db->limit(50); // Batasi agar tidak berat
        $query = $this->db->get()->result_array();
        
        echo json_encode($query);
    }
	
	public function trucking_destinations() {
        $q = $this->input->get('q');
        
        $this->db->select('destination_name as id, destination_name as text');
        $this->db->from('trucking_rates');
        
        if (!empty($q)) {
            $this->db->like('destination_name', $q);
        }
        
        $this->db->group_by('destination_name'); // Agar tidak ada nama ganda
        $this->db->order_by('destination_name', 'ASC');
        $this->db->limit(30);
        
        $query = $this->db->get()->result_array();
        echo json_encode($query);
    }

}

?>