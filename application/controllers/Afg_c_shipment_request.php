<?php
class Afg_c_shipment_request extends CI_Controller
{
	var $title = 'Shipping';
	public function __construct(){
		parent::__construct();
		$this->fungsi->restrict();
		$this->load->model('afg_m_countries');
		$this->load->model('afg_m_export');
		$this->load->model('afg_m_cities');
		$this->load->model('afg_m_shipment_request');
		$this->load->model('pricelist/booking_model');
		$this->fungsi->dbx = $this->afg_m_shipment_request->dbx;
        $this->dbx = $this->afg_m_shipment_request->dbx;
	}
	
	function index(){
		if ($this->session->userdata('login') == TRUE)
		{
			$this->session->unset_userdata('country_id');
			$data['title'] = $this->title;
			$data['h1_title'] = 'Shipment request';
			$data['table_title'] = 'Select destination country';
			$data['menu_navigation'] = 'navigation';
			$data['main_view'] = 'afg_v_shipment_pilih_country';
			$this->load->view('afg_template1', $data);
		}
		else
		{
			redirect('afg_c_login');
		}
	}
	function open_form()
	{
		$data['id'] = $this->input->post('rowid');
		$data['destination'] = $this->input->post('destination');
		$data['mode'] = $this->input->post('mode');
		$data['trans'] = $this->input->post('trans');
		$data['schedule_id'] = $this->input->post('schedule_id');
		$this->load->view('pricelist/form_shipment_request',$data);
	}
	
	function submitShipmentRequest(){
		$export_id = $this->input->post('export_id');
		$consignee = $this->input->post('consignee');
		$notify_party = $this->input->post('notify_party');
		$container_no = $this->input->post('container_no');
		$shipping_unit = $this->input->post('shipping_unit');
		$description = $this->input->post('description');
		$gross_weight = $this->input->post('gross_weight');
		$netto_weight = $this->input->post('netto_weight');
		$total = $this->input->post('total');
		$freight_payable = $this->input->post('freight_payable');
		$note = $this->input->post('note');
		$mode = $this->input->post('mode');
		if($mode == 'add')
			$this->afg_m_shipment_request->add('admin',$export_id,$consignee,$notify_party,$container_no,$shipping_unit,$description,$gross_weight,$netto_weight,$freight_payable,'Shipper asli : '.$note,'No');
		else if($mode == 'edit')
			$this->afg_m_shipment_request->update($shipment_request_id,$shipper_id,$export_id,$consignee,$notify_party,$container_no,$shipping_unit,$description,$gross_weight,$netto_weight,$freight_payable,$note,$isProcessed);
	}
	
	function listExport(){
		$country_id = $this->session->userdata('country_id');
		$country_name = $this->afg_m_countries->getCountriesById($country_id)->country_name;
		$data['title'] = $this->title;
		$data['h1_title'] = 'Shipping';
		$data['menu_navigation'] = 'navigation';
		$data['main_view'] = 'afg_v_shipment_pilih_export';
		$data['table_title'] = 'Select schedule to '.$country_name;
		$this->load->view('afg_template1', $data);
	}
	
	function jin_date_str($date){
		$exp = explode('-',$date);
		if(count($exp) == 3) 
		{
			$date = $exp[2].'/'.$exp[1].'/'.$exp[0];
		}
		return $date;
	}
	
	function selectCountry($country_id){
		$this->session->set_userdata('pilih_country', TRUE);
		$this->session->set_userdata('country_id', $country_id);
		redirect('afg_c_shipment_request/listExport');
	}
	
	
	function displayShipmentRequestFiltered(){
	 	$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
		$sord = isset($_POST['sord'])?$_POST['sord']:'';
				
		$start = $batas * $hal - $batas;
		$start = ($start<0)?0:$start;			
		$_POST['start'] = $start;
		
		// Diagnosa: buka .../displayShipmentRequestFiltered?debug=1&destination=KOTA (tanpa mengubah data)
		if ($this->input->get('debug') == '1') {
			$params = array_merge(array('rows' => 20, 'start' => 0), $this->input->get(NULL, TRUE));
			$rows   = $this->afg_m_shipment_request->get_all_finalize_order($params, true);
			$this->output->set_content_type('application/json')->set_output(json_encode(array(
				'session_region' => $this->session->userdata('region_id'),
				'session_db'     => $this->session->userdata('db'),
				'parameter'      => $params,
				'jumlah_baris'   => count($rows),
				'sql'            => $this->afg_m_shipment_request->last_list_sql,
				'error'          => $this->afg_m_shipment_request->last_list_error,
				'data'           => $this->afg_m_shipment_request->finalize_diagnostics(),
			), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
			return;
		}

		$q = $this->afg_m_shipment_request->get_all_finalize_order($_POST,false); 
		$query = $this->afg_m_shipment_request->get_all_finalize_order($_POST,true); 
		//print_r($this->dbx->last_query());
		//die();
		$hitung = count($q);	 
		if( $hitung > 0 ) {
		    $total_hal = ceil($hitung/$batas);
		} else {
		    $total_hal = 0;
		}	 
		if ($hal > $total_hal) $hal=$total_hal;	
					
		$data = new stdClass();	 
		$data->page = $hal;
		$data->total = $total_hal;
		$data->records = $hitung;
		
		$i=0;
		foreach($query as $row) {
			$data->rows[$i]['id']=$row->id;
			$data->rows[$i]['cell']=array(
				$row->real_id,             // 0. No Booking (BC)
				$row->house_bl,            // 1. HBL Number
				$row->si_number,           // 2. SI Number
				$row->cargo_type,          // 3. Cargo Type
 				$row->expect_etd,          // 4. ETD
 				$row->forwarder_name,      // 5. Booked by
				$row->shipper_name,        // 6. Shipper
				$row->destination_city,    // 7. Destination
				$row->quantity.' '.$row->package_type, // 8. Party
				$row->description_print,   // 9. Commodities
				// 10. Create By; booking titipan cabang lain ditandai
				(isset($row->owner_region) && strcasecmp((string) $row->owner_region, (string) $this->session->userdata('region_id')) !== 0)
					? 'COLOAD dari ' . $row->owner_region . ' (' . $row->pic_document . ')'
					: $row->pic_document,
				$row->volume,              // 11. Cbm
				$row->weight,              // 12. Weight
				$row->shipping_mark,       // 13. Marking
				$row->incoterm,            // 14. Incoterm
				$row->number_of_bl,        // 15. BL Type
			);
			$i++;
		}
		echo json_encode($data);
	}
	
	
	function displayShipmentRequest($export_id){
		$page = isset($_POST['page'])?$_POST['page']:1; // get the requested page
		$limit = isset($_POST['rows'])?$_POST['rows']:10; // get how many rows we want to have into the grid
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'name'; // get index row - i.e. user click to sort
		$sord = isset($_POST['sord'])?$_POST['sord']:''; // get the direction

        $start = $limit*$page - $limit; // do not put $limit*($page - 1)
		$start = ($start<0)?0:$start;  // make sure that $start is not a negative value

		$where = ""; //if there is no search request sent by jqgrid, $where should be empty
        $searchField = isset($_POST['searchField']) ? $_POST['searchField'] : false;
        $searchOper = isset($_POST['searchOper']) ? $_POST['searchOper']: false;
        $searchString = isset($_POST['searchString']) ? $_POST['searchString'] : false;

        if ($_POST['_search'] == 'true') {
            $ops = array(
            'eq'=>'=', //equal
            'ne'=>'<>',//not equal
            'lt'=>'<', //less than
            'le'=>'<=',//less than or equal
            'gt'=>'>', //greater than
            'ge'=>'>=',//greater than or equal
            'bw'=>'LIKE', //begins with
            'bn'=>'NOT LIKE', //doesn't begin with
            'in'=>'LIKE', //is in
            'ni'=>'NOT LIKE', //is not in
            'ew'=>'LIKE', //ends with
            'en'=>'NOT LIKE', //doesn't end with
            'cn'=>'LIKE', // contains
            'nc'=>'NOT LIKE'  //doesn't contain
            );

            foreach ($ops as $key=>$value){
                if ($searchOper==$key) {
                    $ops = $value;
                }
            }
            if($searchOper == 'eq' ) $searchString = $searchString;
            if($searchOper == 'bw' || $searchOper == 'bn') $searchString .= '%';
            if($searchOper == 'ew' || $searchOper == 'en' ) $searchString = '%'.$searchString;
            if($searchOper == 'cn' || $searchOper == 'nc' || $searchOper == 'in' || $searchOper == 'ni') $searchString = '%'.$searchString.'%';

            $where = "$searchField $ops '$searchString' ";
        }
		$country_id = $this->session->userdata('country_id');
		if(!$sidx) $sidx =1;
        $count=$this->afg_m_shipment_request->getDataShipmentRequest($export_id,$start,$limit,$sidx,$sord,$where)->num_rows();

		if( $count > 0 ) {
			$total_pages = ceil($count/$limit);    //calculating total number of pages
		} else {
			$total_pages = 0;
		}

		if ($page > $total_pages) $page=$total_pages;
   		$query = $this->afg_m_shipment_request->getDataShipmentRequest($export_id,$start,$limit,$sidx,$sord,$where)->result();
   		
		$responce = new stdClass();
		$responce->page = $page;
		$responce->total = $total_pages;
		$responce->records = $count;
		$i=0;
		
		foreach($query as $row) 
		{
			$responce->rows[$i]['shipment_request_id']=$row->shipment_request_id;
			$responce->rows[$i]['cell']=array(
											anchor('#','edit',array('class' => 'editBtn','onclick'=>'return false')).' '.
											anchor('#','hapus',array('class' => 'hapusBtn','onclick'=>'return false')),
											$row->shipment_request_id,
											$row->shipper_id,
											$row->note,
											$row->export_id,
											$row->consignee,
											$row->notify_party,
											$row->container_no,
											$row->shipping_unit,
											$row->description,
											$row->gross_weight,
											$row->netto_weight,
											$row->freight_payable,
											$row->isProcessed
											);
			$i++;
		}
		echo json_encode($responce);
	}
	
	function displayCountrySelect(){
		$page = isset($_POST['page'])?$_POST['page']:1; // get the requested page
		$limit = isset($_POST['rows'])?$_POST['rows']:10; // get how many rows we want to have into the grid
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'name'; // get index row - i.e. user click to sort
		$sord = isset($_POST['sord'])?$_POST['sord']:''; // get the direction
		
        $start = $limit*$page - $limit; // do not put $limit*($page - 1)
		$start = ($start<0)?0:$start;  // make sure that $start is not a negative value

		$where = ""; //if there is no search request sent by jqgrid, $where should be empty
        $searchField = isset($_POST['searchField']) ? $_POST['searchField'] : false;
        $searchOper = isset($_POST['searchOper']) ? $_POST['searchOper']: false;
        $searchString = isset($_POST['searchString']) ? $_POST['searchString'] : false;

        if ($_POST['_search'] == 'true') {
            $ops = array(
            'eq'=>'=', //equal
            'ne'=>'<>',//not equal
            'lt'=>'<', //less than
            'le'=>'<=',//less than or equal
            'gt'=>'>', //greater than
            'ge'=>'>=',//greater than or equal
            'bw'=>'LIKE', //begins with
            'bn'=>'NOT LIKE', //doesn't begin with
            'in'=>'LIKE', //is in
            'ni'=>'NOT LIKE', //is not in
            'ew'=>'LIKE', //ends with
            'en'=>'NOT LIKE', //doesn't end with
            'cn'=>'LIKE', // contains
            'nc'=>'NOT LIKE'  //doesn't contain
            );

            foreach ($ops as $key=>$value){
                if ($searchOper==$key) {
                    $ops = $value;
                }
            }
            if($searchOper == 'eq' ) $searchString = $searchString;
            if($searchOper == 'bw' || $searchOper == 'bn') $searchString .= '%';
            if($searchOper == 'ew' || $searchOper == 'en' ) $searchString = '%'.$searchString;
            if($searchOper == 'cn' || $searchOper == 'nc' || $searchOper == 'in' || $searchOper == 'ni') $searchString = '%'.$searchString.'%';
			if(strpos($searchString,',')>0)
			{
				$searchString1 = substr($searchString, 0, strpos($searchString,','));
				$searchString2 = substr($searchString, strpos($searchString,',')+1, strlen($searchString)-(strpos($searchString,',')+1)); 
			}
			
			if(strpos($searchField,',')>0)
			{
				$searchField1 = 'disloc';//substr($searchField, strpos($searchField,','));
				$searchField2 = 'datebook';//substr($searchField, -strpos($searchField,','));
				$where = "$searchField1 $ops '$searchString1' and $searchField2 = '$searchString2'"; 
			}
			else
			{
				$where = "$searchField $ops '$searchString' ";
			}
        }

		if(!$sidx) $sidx =1;
        $count = $this->afg_m_countries->getDataCountries($start,$limit,$sidx,$sord,$where)->num_rows();

		if( $count > 0 ) {
			$total_pages = ceil($count/$limit);    //calculating total number of pages
		} else {
			$total_pages = 0;
		}

		if ($page > $total_pages) $page=$total_pages;
   		$query = $this->afg_m_countries->getDataCountries($start,$limit,$sidx,$sord,$where)->result();
   		
		$responce = new stdClass();
		$responce->page = $page;
		$responce->total = $total_pages;
		$responce->records = $count;
		$i=0;
		foreach($query as $row) 
		{
			$responce->rows[$i]['country_id']=$row->country_id;
			$responce->rows[$i]['cell']=array(
												$row->country_id,
												$row->country_name,
												//form_submit(array('class' => 'linkExport','action' => 'afg_c_export/selectCountry/'.$row->country_id), 'select')
												anchor('afg_c_shipment_request/selectCountry/'.$row->country_id,'Select',array('class' => 'linkExport'))
												);
			$i++;
		}
		echo json_encode($responce);
	}
	
	function displayExport(){
		
		$page = isset($_POST['page'])?$_POST['page']:1; // get the requested page
		$limit = isset($_POST['rows'])?$_POST['rows']:10; // get how many rows we want to have into the grid
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'name'; // get index row - i.e. user click to sort
		$sord = isset($_POST['sord'])?$_POST['sord']:''; // get the direction

        $start = $limit*$page - $limit; // do not put $limit*($page - 1)
		$start = ($start<0)?0:$start;  // make sure that $start is not a negative value

		$where = ""; //if there is no search request sent by jqgrid, $where should be empty
        $searchField = isset($_POST['searchField']) ? $_POST['searchField'] : false;
        $searchOper = isset($_POST['searchOper']) ? $_POST['searchOper']: false;
        $searchString = isset($_POST['searchString']) ? $_POST['searchString'] : false;

        if ($_POST['_search'] == 'true') {
            $ops = array(
            'eq'=>'=', //equal
            'ne'=>'<>',//not equal
            'lt'=>'<', //less than
            'le'=>'<=',//less than or equal
            'gt'=>'>', //greater than
            'ge'=>'>=',//greater than or equal
            'bw'=>'LIKE', //begins with
            'bn'=>'NOT LIKE', //doesn't begin with
            'in'=>'LIKE', //is in
            'ni'=>'NOT LIKE', //is not in
            'ew'=>'LIKE', //ends with
            'en'=>'NOT LIKE', //doesn't end with
            'cn'=>'LIKE', // contains
            'nc'=>'NOT LIKE'  //doesn't contain
            );

            foreach ($ops as $key=>$value){
                if ($searchOper==$key) {
                    $ops = $value;
                }
            }
            if($searchOper == 'eq' ) $searchString = $searchString;
            if($searchOper == 'bw' || $searchOper == 'bn') $searchString .= '%';
            if($searchOper == 'ew' || $searchOper == 'en' ) $searchString = '%'.$searchString;
            if($searchOper == 'cn' || $searchOper == 'nc' || $searchOper == 'in' || $searchOper == 'ni') $searchString = '%'.$searchString.'%';

            $where = "$searchField $ops '$searchString' ";
        }
		$country_id = $this->session->userdata('country_id');
		if(!$sidx) $sidx =1;
        $count = $this->afg_m_export->getDataExport($country_id,$start,$limit,$sidx,$sord,$where)->num_rows();

		if( $count > 0 ) {
			$total_pages = ceil($count/$limit);    //calculating total number of pages
		} else {
			$total_pages = 0;
		}

		if ($page > $total_pages) $page=$total_pages;
   		$query = $this->afg_m_export->getDataExport($country_id,$start,$limit,$sidx,$sord,$where)->result();

		$responce = new stdClass();
		$responce->page = $page;
		$responce->total = $total_pages;
		$responce->records = $count;
		$i=0;
		
		foreach($query as $row) 
		{
			$responce->rows[$i]['export_id']=$row->export_id;
			$responce->rows[$i]['cell']=array(
											$row->export_id,
											$row->origin_city_id,
											$row->origin_vessel,
											$row->origin_voyage,
											$this->jin_date_str($row->stf_cls),
											$this->jin_date_str($row->origin_etd),
											$row->connecting_city_id,
											$row->connecting_vessel,
											$row->connecting_voyage,
											$this->jin_date_str($row->connecting_etd),
											$this->jin_date_str($row->eta_destination),
											$row->final_destination_country_id,
											anchor('afg_c_shipment_request/shipmentRequest/'.$row->export_id,'Select',array('class' => 'linkExport'))
											);
			$i++;
		}
		echo json_encode($responce);
	}
	
	function hide_shipment_request(){
		$id = $this->input->post('id');
		$status = $this->afg_m_export->hide_shipment_request($id);
		echo json_encode(array('status'=>$status,'mode'=>'hide request'));
	}

	function kirim_ke_detail_trn()
	{
		// =========================================================
		// 1. BACA PAYLOAD
		// Support:
		// - items=<json array> (form/urlencoded)
		// - raw JSON array: [{...}, {...}]
		// - raw JSON object: {"items":[...]}
		// - format lama single POST
		// =========================================================
		$items = null;
		$rawItems = $this->input->post('items', false);

		if ($rawItems !== null && $rawItems !== '') {
			$items = is_string($rawItems) ? json_decode($rawItems, true) : $rawItems;

			if (!is_array($items)) {
				return $this->output
					->set_content_type('application/json')
					->set_status_header(400)
					->set_output(json_encode(array(
						'status'  => 0,
						'message' => 'Format items tidak valid.',
						'json_error' => json_last_error_msg()
					), JSON_UNESCAPED_SLASHES));
			}
		}

		if ($items === null) {
			$rawBody = trim((string)$this->input->raw_input_stream);

			if ($rawBody !== '' && ($rawBody[0] === '[' || $rawBody[0] === '{')) {
				$decoded = json_decode($rawBody, true);

				if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
					return $this->output
						->set_content_type('application/json')
						->set_status_header(400)
						->set_output(json_encode(array(
							'status'  => 0,
							'message' => 'JSON body tidak valid.',
							'json_error' => json_last_error_msg()
						), JSON_UNESCAPED_SLASHES));
				}

				if (isset($decoded['items']) && is_array($decoded['items'])) {
					$items = $decoded['items'];
				} elseif (array_is_list($decoded)) {
					$items = $decoded;
				} else {
					$items = array($decoded);
				}
			}
		}

		if ($items === null) {
			$single = $this->input->post(NULL, false);
			$items = !empty($single) ? array($single) : array();
		}

		if (empty($items)) {
			return $this->output
				->set_content_type('application/json')
				->set_status_header(400)
				->set_output(json_encode(array(
					'status'  => 0,
					'message' => 'Tidak ada booking yang dikirim.'
				), JSON_UNESCAPED_SLASHES));
		}

		if (count($items) > 50) {
			return $this->output
				->set_content_type('application/json')
				->set_status_header(400)
				->set_output(json_encode(array(
					'status'  => 0,
					'message' => 'Maksimal 50 booking dalam satu proses.'
				), JSON_UNESCAPED_SLASHES));
		}

		// =========================================================
		// 2. PROSES PER BOOKING
		// Transaction berada di model, jadi 1 booking gagal tidak
		// membatalkan booking lain dalam batch.
		// =========================================================
		$results            = array();
		$successCount       = 0;
		$failedCount        = 0;
		$alreadyExistsCount = 0;

		foreach ($items as $item) {
			if (!is_array($item)) {
				$failedCount++;
				$results[] = array(
					'status'  => 0,
					'code'    => 'INVALID_ITEM',
					'message' => 'Format item booking tidak valid.'
				);
				continue;
			}

			try {
				$result = $this->afg_m_shipment_request->find_and_insert_booking_order($item);
			} catch (Throwable $e) {
				log_message(
					'error',
					'[SHIPMENT_REQUEST_CONTROLLER] booking=' . ($item['booking_id'] ?? '')
					. ' | message=' . $e->getMessage()
					. ' | file=' . $e->getFile() . ':' . $e->getLine()
				);

				$result = array(
					'status'     => 0,
					'code'       => 'CONTROLLER_EXCEPTION',
					'booking_id' => $item['booking_id'] ?? '',
					'stage'      => 'CONTROLLER',
					'message'    => 'Terjadi kesalahan saat memproses booking.',
					'error'      => $e->getMessage()
				);

				// Detail file/line hanya tampil bila endpoint dipanggil dengan ?debug=1
				if ($this->input->get('debug') == '1') {
					$result['exception_type'] = get_class($e);
					$result['exception_file'] = basename($e->getFile());
					$result['exception_line'] = $e->getLine();
				}
			}

			// =========================================================
			// 3. EMAIL QN - HANYA SETELAH MODEL COMMIT
			// =========================================================
			if (
				isset($result['status'])
				&& (int)$result['status'] === 1
				&& empty($result['already_exists'])
				&& !empty($result['send_email'])
				&& strpos((string)($result['booking_id'] ?? ''), 'QN-') !== false
			) {
				try {
					$emailMeta = $result['email_meta'] ?? array();
					$shipperId = (int)($emailMeta['shipper_id'] ?? 0);

					if ($shipperId > 0) {
						$to = $this->booking_model->get_shipper_details($shipperId);

						if ($to) {
							$sales = $to->sales ?? null;
							$toSales = null;

							if (!empty($sales)) {
								$toSales = $this->dbx
									->get_where('mstadmin', array('user_id' => $sales))
									->row();
							}

							$recipientEmail = trim((string)($to->cp_email ?? ''));

							if ($recipientEmail !== '') {
								$dataEmail = array(
									'nama'              => $to->shipper_name ?? '',
									'consignee'         => $emailMeta['consignee_name'] ?? '',
									'nama_sales'        => $toSales->fullname ?? '',
									'code'              => $result['booking_id'],
									'bl'                => $emailMeta['bl_no'] ?? '',
									'port_of_loading'   => $emailMeta['port_of_loading'] ?? '',
									'port_of_discharge' => $emailMeta['port_of_discharge'] ?? '',
									'etd'               => $emailMeta['etd'] ?? '',
									'eta'               => $emailMeta['eta'] ?? '',
									'pod'               => $emailMeta['pod'] ?? '',
									'pol'               => $emailMeta['pol'] ?? ''
								);

								$html = $this->load->view('emails/noBL.php', $dataEmail, true);
								$responseEmail = send_email_elastic(
									'HBL ' . $result['booking_id'] . ' has been prepared',
									$html,
									array($recipientEmail),
									''
								);

								if (isset($responseEmail['code']) && (int)$responseEmail['code'] === 500) {
									log_message(
										'error',
										'[SHIPMENT_REQUEST_EMAIL] booking=' . $result['booking_id']
										. ' | response=' . json_encode($responseEmail)
									);
									$result['email'] = array(
										'status'  => 0,
										'message' => 'Booking berhasil, tetapi email gagal dikirim.'
									);
								} else {
									$result['email'] = array(
										'status'  => 1,
										'message' => 'Email berhasil dikirim.'
									);
								}
							} else {
								$result['email'] = array(
									'status'  => 0,
									'message' => 'Booking berhasil, tetapi email shipper kosong.'
								);
							}
						} else {
							$result['email'] = array(
								'status'  => 0,
								'message' => 'Booking berhasil, tetapi data shipper untuk email tidak ditemukan.'
							);
						}
					}
				} catch (Throwable $emailException) {
					log_message(
						'error',
						'[SHIPMENT_REQUEST_EMAIL_EXCEPTION] booking=' . ($result['booking_id'] ?? '')
						. ' | message=' . $emailException->getMessage()
					);

					$result['email'] = array(
						'status'  => 0,
						'message' => 'Booking berhasil, tetapi proses email mengalami error.'
					);
				}
			}

			unset($result['email_meta'], $result['send_email']);

			if (isset($result['status']) && (int)$result['status'] === 1) {
				$successCount++;
				if (!empty($result['already_exists'])) {
					$alreadyExistsCount++;
				}
			} else {
				$failedCount++;
			}

			$results[] = $result;
		}

		// =========================================================
		// 4. SUMMARY
		// =========================================================
		if ($failedCount === 0) {
			$overallStatus = 1;
			$message = 'Semua booking berhasil diproses.';
		} elseif ($successCount > 0) {
			$overallStatus = 2;
			$message = 'Sebagian booking berhasil dan sebagian gagal.';
		} else {
			$overallStatus = 0;
			$message = 'Semua booking gagal diproses.';
		}

		return $this->output
			->set_content_type('application/json')
			->set_output(json_encode(array(
				'status'  => $overallStatus,
				'message' => $message,
				'summary' => array(
					'total'          => count($items),
					'success'        => $successCount,
					'failed'         => $failedCount,
					'already_exists' => $alreadyExistsCount
				),
				'results' => $results
			), JSON_UNESCAPED_SLASHES));
	}
}
?>