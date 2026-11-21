<?php
class Afg_c_stuffing_plan extends CI_Controller {
	var $title = 'Stuffing Plan';

	// ---------------------------------------------------------------
	// Konfigurasi Partner API Neo Customs (BC 1.1)
	// Dokumentasi: https://neo-customs.jar-vis.com/docs/partner-api
	// TODO: sebaiknya pindahkan base url & api key ini ke config/production
	// (mis. $this->config->item('customs_api_key')) agar tidak hardcode.
	// ---------------------------------------------------------------
	private $customs_api_base_url = 'https://api-gw.jar-vis.com/jarvis/api/v1/partner/';
	private $customs_api_key      = 'jvs_live_z6ARyA7oXoWBgYTq4z9cjyORx2YKtCUtzIwWd84E';

	// GCL Warehouse Tally Sheet API. Auth URLs intentionally come from env/config
	// because the supplied integration snippet did not include their actual paths.
	private $warehouse_tally_base_url = 'https://gcl-warehouse.com/api/v1/inventory/tallysheet';

	/*
	 * Pakai token yang sama dengan Ticket_warehouse.
	 * Jangan dikirim ke frontend.
	 */
	private $wms_bearer_token = "GCL-APP-9246b8082f66208961668b10f85896f8";

	public function __construct()
	{
		parent::__construct();
		$this->fungsi->restrict();
		$this->fungsi->get_define();
		$this->load->model(array(
			'user_model',
			'afg_m_stuffing_plan',
			'reference/region_model',
			'afg_m_schedule',
			'm_bill_of_ladding',
			'afg_m_ref',
			'reference/agent_company_model',
			'reference/consignee_notify_model',
			'reference/shipping_company_model',
			'reference/stuffing_location_model',
			'afg_m_countries',
			'afg_m_cities','afg_m_shipment_request',
			'reference/port_destination_model',
			'pricelist/selling_rate_model',
			'afg_m_job_sheet',
			'afg_m_detail',
			'report/m_rekap_sales',
			'pricelist/booking_model',
			'afg_m_tracking'
		));
		$this->load->library('FPDF_MC_Table');
		$this->load->library('Fungsi');
		$this->load->library('Access_control');
		// Minimal harus boleh buka menu stuffing plan.
		$this->access_control->require_menu('transaction', 'view');

        // Inject koneksi database aktif ke library Fungsi
        $this->fungsi->dbx = $this->afg_m_stuffing_plan->dbx;
        $this->dbx = $this->afg_m_stuffing_plan->dbx;
	}
	
	function index() {
		$regionName = $this->session->userdata('region_name');
		$this->session->unset_userdata('country_id');
		$data['title'] = $this->title;
		$data['h1_title'] = 'Stuffing Plan';
		$data['table_title'] = 'List Shipment Request';
		$data['menu_navigation'] = 'navigation';
		$data['main_view'] = 'stuffing_plan/afg_v_stuffing_plan';
		$data['authentic_level'] = $this->fungsi->authentic_level(array(0,'1','2','3',4));		
		$data['regionName'] = $regionName;			
		$data['readonly'] = 'disabled="disabled"';						
		$data['level'] = $this->session->userdata('level_id');			
		$data['region_id'] = $this->session->userdata('region_id');			
		$data['region'] = $this->region_model->get_data();
		$data['style'] = 'trn_stuffing_plan';			
		$data['script'] = 'trn_stuffing_plan';
		$data['type_of_trans'] = json_encode($this->fungsi->list_type_of_trans());
		$data['user']=$this->fungsi->get_data_object(REF_USR,array('user_id','fullname'),'fullname',TRUE,FALSE);
		$data['cities'] = $this->fungsi->get_data_object(REF_CTY,array('city_name','city_name'),'city_name',FALSE,FALSE);
		$data['airport'] = $this->fungsi->get_data_object(REF_AIP,array('name','name'),'name',FALSE,FALSE);
		$data['airline'] = $this->fungsi->get_data_object(REF_AIL,array('name','name'),'name',FALSE,FALSE);
		$data['warehouses'] = $this->fungsi->get_data_object(REF_WRH,array('warehouse_code','warehouse_code'),'warehouse_code',FALSE,FALSE);
		$data['carriers'] = $this->fungsi->get_data_object(REF_SHC,array('shipping_company_code','shipping_company_code'),'shipping_company_code',TRUE,FALSE);
		$data['container'] = $this->fungsi->get_data_object(REF_CNT,array('typecontainer_name','typecontainer_name'),'typecontainer_name',FALSE,FALSE);
		//$data['agent'] = $this->fungsi->get_data_object(REF_AGN,array('agent_company_id as id','agent_company_code as code'),'agent_company_code',FALSE,TRUE);
		
		/* perubahan array agent */
		$data['agent'] = $this->afg_m_stuffing_plan->get_agent_dropdown();
		//print_r($this->dbx->last_query());
		//die();
		$data['pelayaran'] = $this->afg_m_stuffing_plan->get_data_pelayaran();
		$data['pelayaran_lcl'] = $this->fungsi->get_data_object(REF_CAL,array('code','code'),'code',FALSE,FALSE);
		//print_r($data['pelayaran']);
		//die();
		
		/* update dropdown agen_penagihan */
		
		$data['agent_penagihan'] = $this->fungsi->get_data_object_agent(REF_AGN,array('agent_company_code','agent_company_code'),'agent_company_code',TRUE,FALSE);
		
		$this->load->view('afg_template', $data);
		
	}
	
	function open_job_form()
	{
		$data = $this->input->post();
		$this->load->view('stuffing_plan/v_job_form',$data);
	}
	
	function edit_job_form()
	{
		$data = $this->input->post();
		if($data['mode']== "LCL"){
		$this->load->view('stuffing_plan/v_job_edit',$data);
		}elseif($data['mode']== "FCL"){
		$this->load->view('stuffing_plan/v_job_edit_fcl',$data);
		}elseif($data['mode']== "COLOAD"){
		$this->load->view('stuffing_plan/v_job_edit_coload',$data);
		}elseif($data['mode']== "AIR FREIGHT"){
		$this->load->view('stuffing_plan/v_job_edit_200',$data);
		}elseif($data['mode']== "SF IMPORT LCL"){
		$this->load->view('stuffing_plan/v_job_edit_300',$data);
		}elseif($data['mode']== "SF IMPORT FCL"){
		$this->load->view('stuffing_plan/v_job_edit_301',$data);
		}elseif($data['mode']== "COLOAD IMPORT"){
		$this->load->view('stuffing_plan/v_job_edit_302',$data);
		}elseif($data['mode']== "CUSTOM IMPORT"){
		$this->load->view('stuffing_plan/v_job_edit_400',$data);
		}elseif($data['mode']== "CUSTOM"){
		$this->load->view('stuffing_plan/v_job_edit_401',$data);
		}elseif($data['mode']== "PROFIT SHARE PPJK"){
		$this->load->view('stuffing_plan/v_job_edit_402',$data);
		}elseif($data['mode']== "MESCELLENEOUS"){
		$this->load->view('stuffing_plan/v_job_edit_500',$data);
		}elseif($data['mode']== "AIR FREIGHT IMPORT"){
		$this->load->view('stuffing_plan/v_job_edit_600',$data);
		}elseif($data['mode']== "TRUCKING ONLY"){
		$this->load->view('stuffing_plan/v_job_edit_700',$data);
		}elseif($data['mode']== "TRUCKING VENDOR"){
		$this->load->view('stuffing_plan/v_job_edit_701',$data);
		}elseif($data['mode']== "TRUCKING CUSTOMER"){
		$this->load->view('stuffing_plan/v_job_edit_702',$data);
		}else{
		$this->load->view('stuffing_plan/v_job_edit',$data);
		}
	}
	
	function update_shipco()
	{
		$data = $this->afg_m_stuffing_plan->update_shipco($_POST);
		echo json_encode($data);
	}
	function update_pelayaran()
	{
		$data = $this->afg_m_stuffing_plan->update_pelayaran($_POST);
		//print_r($this->dbx->last_query()); die();
		echo json_encode($data);
	}
	
	function saving_data()
	{
		// FIX LOCK_JOB: kolom hidden lock_job di grid adalah snapshot lama.
		// lock_job hanya boleh diubah lewat Job Sheet (afg_c_job_sheet), jangan ikut tersimpan di sini.
		if (isset($_POST['myData']) && is_array($_POST['myData'])) {
			unset($_POST['myData']['lock_job']);
		}
		$data = $this->afg_m_stuffing_plan->saving_data($_POST);
	//print_r($this->dbx->last_query()); die();
		
		echo json_encode($data);
	}
	
	function cek()
	{
		$this->load->model('afg_m_cost');
		$payable = $this->afg_m_cost->get_data_payable();
		}
	
	function tambah_countainer()
	{
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
		$sord = isset($_POST['sord'])?$_POST['sord']:'';
				
		$start = $batas * $hal - $batas;
		$start = ($start<0)?0:$start;			
		$_POST['start'] = $start;
		
      $q = $this->afg_m_stuffing_plan->tambah_countainer($_POST,false); 
      $query = $this->afg_m_stuffing_plan->tambah_countainer($_POST,true); 
		
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
				$row->count_size,
				$row->count_no,
				$row->seal_no,
			);
		$i++; 
		}
		echo json_encode($data);	
	}
	
	function tambah_countainer_seal()
	{
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
		$sord = isset($_POST['sord'])?$_POST['sord']:'';
				
		$start = $batas * $hal - $batas;
		$start = ($start<0)?0:$start;			
		$_POST['start'] = $start;
		
      $q = $this->afg_m_stuffing_plan->tambah_countainer_seal($_POST,false); 
      $query = $this->afg_m_stuffing_plan->tambah_countainer_seal($_POST,true); 
		
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
				$row->count_num,
				$row->count_type,
				$row->id_sp,
			);
		$i++;
		}
		echo json_encode($data);	
	}
	
	function manage_table_detail()
	{
		$value = $this->afg_m_stuffing_plan->manage_table_detail($_POST);
		echo json_encode($value);	
	}
	
	function save_data_stuffingplan_detail() 
	{
		$value = $this->afg_m_stuffing_plan->save_data_stuffingplan_detail($_POST);
		echo json_encode($value);
	}
	
	function save_edit_data_stuffing() 
	{
		$value = $this->afg_m_stuffing_plan->save_edit_data_stuffing($_POST);
		echo json_encode($value);
	}
	
	function get_cetak_stuffing() 	
	{
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
		$sord = isset($_POST['sord'])?$_POST['sord']:'';
				
		$start = $batas * $hal - $batas;
		$start = ($start<0)?0:$start;			
		$_POST['start'] = $start;
		
      $q = $this->afg_m_stuffing_plan->get_cetak_stuffing($_POST,false); 
      $query = $this->afg_m_stuffing_plan->get_cetak_stuffing($_POST,true); 
		
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
		    $data->rows[$i]['id']=$row->sp_detail_id;
		    $data->rows[$i]['cell']=array(
				$row->kode_ref.$row->num,
				$row->shipper_print,
				$row->desc_print,
			);
		$i++;
		}
		echo json_encode($data);
		////print_r($query);
	}
	

    /** AJAX untuk panel Cargo Composition (-100 / -300). */
    public function cargo_composition()
    {
        $this->output->set_content_type('application/json', 'utf-8');
        $sp_id = (int) $this->input->post('sp_id');
        $kode_ref = trim((string) $this->input->post('kode_ref'));
        if ($sp_id <= 0 || !preg_match('/^[^-]+-(100|300)/i', $kode_ref)) {
            return $this->output->set_output(json_encode(array(
                'status' => 0,
                'message' => 'Cargo Composition hanya tersedia untuk consol export (-100) dan import (-300).',
            )));
        }

        $result = $this->afg_m_stuffing_plan->get_cargo_composition($sp_id, $kode_ref);
        if ($result === null) {
            $this->output->set_status_header(404);
            return $this->output->set_output(json_encode(array(
                'status' => 0,
                'message' => 'Job tidak ditemukan atau tidak dapat diakses.',
            )));
        }
        return $this->output->set_output(json_encode(array(
            'status' => 1,
            'data' => $result,
        )));
    }

	function get_stuffing_plan_detail()
	{
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'kode_ref';
		$sord = isset($_POST['sord'])?$_POST['sord']:'asc';
		$code = $this->input->post('code');
		$code_fixed = substr($code, 0, 15);
		$q = $this->afg_m_stuffing_plan->get_stuffing_plan_detail($code_fixed);
		//print_r($this->dbx->last_query());
		$hitung = count($q->result());
	 
		if( $hitung > 0 ) {
		    $total_hal = ceil($hitung/$batas);
		} else {
		    $total_hal = 0;
		}
	 
		if ($hal > $total_hal) $hal=$total_hal;
	 
		$start = $batas*$hal - $batas;
		$start = ($start<0)?0:$start;		
		
		$data = new stdClass();	 
		$data->page = $hal;
		$data->total = $total_hal;
		$data->records = $hitung;
		$i=0;		
		foreach($q->result() as $row) {
			
			/*
			$number = strtoupper(chr(97+$i));
			if($i == 26 || $i > 26 ) {
				$ii = $i-26;
				$number = $number = 'Z'. strtoupper(chr(97+$ii));			
			}
			*/
			
		$data->rows[$i]['id']=$row->sp_detail_id;
		$data->rows[$i]['cell']=array(
		    									$row->num,
												$row->wg_ctn,
												$row->shipper_print,
												$row->no_pack_qty,		
												$row->no_pack_unit,
												$row->desc_print,
												$row->weight,
												$row->ship_meas,
												$row->wh_meas,
												$row->wh_agent,
												$row->bl_meas,
												$row->destination,
												$row->doc,
												$row->hbl,
												$row->agent_code,
												$row->freight,
												$row->c_o,
												$row->shipping_mark,
												$row->kode_ref.$row->num,
												$row->shipper,
												$row->lock_job,
												$row->hb_l,
												$row->isLock,
												'',
												$row->incoterms,
														$row->hs_code,
											);
		    $i++;
		}
		echo json_encode($data);
	}
	
	function get_stuffing_plan_detail_export()
	{
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'kode_ref';
		$sord = isset($_POST['sord'])?$_POST['sord']:'asc';
		$code = $this->input->post('code');
		$q = $this->afg_m_stuffing_plan->get_stuffing_plan_detail($code);
		
		$hitung = count($q->result());
	 
		if( $hitung > 0 ) {
		    $total_hal = ceil($hitung/$batas);
		} else {
		    $total_hal = 0;
		}
	 
		if ($hal > $total_hal) $hal=$total_hal;
	 
		$start = $batas*$hal - $batas;
		$start = ($start<0)?0:$start;		
		
		$data = new stdClass();	 
		$data->page = $hal;
		$data->total = $total_hal;
		$data->records = $hitung;
		$i=0;		
		foreach($q->result() as $row) 
		{
			/*
			$number = strtoupper(chr(97+$i));
			if($i == 26 || $i > 26 ) {
				$ii = $i-26;
				$number = $number = 'Z'. strtoupper(chr(97+$ii));			
			}
			*/
			
			
			$trucking_co = $this->afg_m_detail->get_detail($row->sp_detail_id)->trucking_co;
			$location = $this->afg_m_detail->get_detail($row->sp_detail_id)->location;
			$overtime = $this->afg_m_detail->get_detail($row->sp_detail_id)->overtime;
			
		   $data->rows[$i]['id']=$row->sp_detail_id;
		   $data->rows[$i]['cell']=array(
		    									$row->num,
		    									$row->wg_ctn,
												$row->shipper_print,												
												$row->shipper_import,
												$row->no_pack_qty,
												$row->no_pack_unit,
												$row->desc_print,
												$row->weight,
												$row->ship_meas,
												$row->wh_meas,
												$row->wh_agent,
												$row->cost_meas,
												$row->bl_meas,
												strtoupper($row->destination ?? ''),
												$row->doc,
												$row->hb_l,
												$row->status_selling,
												$row->hs_code,
												htmlentities(substr($row->shipping_mark ?? '',0,10)),
												$row->incoterms,
												$row->isN,
												$this->stuffing_location_model->get_trucking_name($trucking_co),
												$row->c_o,
												$overtime,
												$row->freight,
												$row->kode_ref.$row->num,
												htmlentities($row->desc_goods??''),
												$row->shipping_mark,
												$row->notify,
												$row->notify_add,
												$row->consignee,
												$row->consignee_add,
												$row->shipper_import_code,
												$row->shipper_import_add,
												$row->note,
												$row->lock_job,
												$row->shipper,		
												$row->isLock,												
											);
		    $i++;
		}
		echo json_encode($data);
	}
	
	function change_region()
	{
		$this->session->set_userdata('region_id',$_POST['region_id']);
		echo json_encode(array('region_id'=>$_POST['region_id']));
	}
	
	function get_stuffing_plan()
	{
		$level_user = $this->session->userdata('level_id');
		$data = $this->afg_m_stuffing_plan->get_stuffing_plan($_POST,$level_user);
		
		if($data) 
		{
			$data->first_id = $this->afg_m_stuffing_plan->get_id('select_max');
			$data->last_id = $this->afg_m_stuffing_plan->get_id('select_min');
			$data->kode_ref1 = substr($data->kode_ref,4,3);
			$data->kode_ref2 = substr($data->kode_ref,7,2);
			$data->kode_ref3 = substr($data->kode_ref,9,2);

			// Konversi tanggal job (format yyyymmdd)
			$tanggalJob = null;
			if (!empty($data->tanggal_job) && preg_match('/^\d{8}$/', $data->tanggal_job)) {
				$tanggalJob = DateTime::createFromFormat('Ymd', $data->tanggal_job);
				$data->tanggal_job = $this->fungsi->jin_date_str($data->tanggal_job);
			}

			$data->closing_date_time = $this->fungsi->jin_date_str($data->closing_date_time);
			$data->etd_jkt = $this->fungsi->jin_date_str($data->etd_jkt);
			$data->atd = $this->fungsi->jin_date_str($data->atd);
			$data->eta = $this->fungsi->jin_date_str($data->eta);
			$data->ata = $this->fungsi->jin_date_str($data->ata);
			$data->stuffing_date = $this->fungsi->jin_date_str($data->stuffing_date);
			$data->double_container = $this->afg_m_stuffing_plan->get_double_container($data->sp_id);

			// Hitung selisih 3 bulan
			$skipStatusCheck = false;
			if ($tanggalJob instanceof DateTime) {
				$today = new DateTime();
				$diff = $today->diff($tanggalJob);
				// Jika tanggal job lebih dari 3 bulan dari hari ini
				if ($tanggalJob < $today->sub(new DateInterval('P3M'))) {
					$skipStatusCheck = true;
				}
			}

			$jobType = (int)substr(
				(string)$data->kode_ref,
				4,
				3
			);

			$gocometPending =
				isset($data->status_gocomet) &&
				$data->status_gocomet === 'MBL_CHANGED_PENDING';

			$trackingTypes = array(
				100, 101, 102,
				300, 301, 302
			);

			if (
				!$skipStatusCheck &&
				!$gocometPending &&
				in_array($jobType, $trackingTypes, true)
			) {
				if (in_array($jobType, array(100, 101, 102), true)) {
					$perfect =
						$this->afg_m_stuffing_plan
							->check_status($data->kode_ref);

				} else {
					$perfect =
						$this->afg_m_stuffing_plan
							->check_status_import($data->kode_ref);
				}

				if ($perfect === 'perfect') {
					$data->posted = 'Perfect';

					$this->afg_m_stuffing_plan
						->save_status_gocomet(
							$data->kode_ref,
							'Perfect'
						);

				} else {
					$exists =
						$this->afg_m_stuffing_plan
							->check_gocomet_exists(
								$data->kode_ref
							);

					if ($exists) {
						$data->posted =
							$this->cek_status_track(
								$data->kode_ref
							);

					} else {
						$lookupValue = !empty($data->id_gocomet)
							? $data->id_gocomet
							: $data->kode_ref;

						$data->posted =
							$this->afg_m_stuffing_plan
								->get_status_posted(
									$lookupValue
								);
					}
				}

			} else {
				if ($gocometPending) {
					$data->posted = 'MBL_CHANGED_PENDING';

				} else {
					$lookupValue = !empty($data->id_gocomet)
						? $data->id_gocomet
						: $data->kode_ref;

					$data->posted =
						$this->afg_m_stuffing_plan
							->get_status_posted(
								$lookupValue
							);
				}
			}

			echo json_encode($data);
		} 
		else 
		{
			echo json_encode(array('stuffing_date'=>'','closing_date_time'=>'','etd_jkt'=>'','kode_ref'=>''));
		}
	}

	
	function cek_expire()
	{
		$sp_id = $this->input->post('id_sp');
		$status = $this->afg_m_schedule->cek_expire($sp_id);
		echo json_encode(array('status'=>$status,));
	}
	
	function cek_request()
	{
		$sp_id = $this->input->post('id_sp');
		$status = $this->afg_m_schedule->get_data_request($sp_id);
		echo json_encode(array('status'=>$status,));
	}
	
	function cek_export()
	{
		$sp_id = $this->input->post('sp_id');
		$status = $this->fungsi->cek_export($sp_id);
		echo json_encode(array('status'=>$status,));
	}
	
	function system_management_menu()
	{
		$data = array(
			'title' => 'Menu Management',
			'menu_navigation'=>'navigation',
			'main_view'=>'menu_management',
		);			
		$this->load->view('menu_management', $data);
	}
	
	function unlock_request()
	{
		$status = $this->afg_m_stuffing_plan->unlock_request($_POST);
		echo json_encode(array('status'=>$status, 'mode'=>'unlock_request'));
	}

	function batal_proses()
	{
		$id = $this->input->post('id');
		$id_request = $this->input->post('id_request');
		$status = $this->afg_m_stuffing_plan->batal_proses($id,$id_request);
		echo json_encode(array('status'=>$status, 'mode'=>'batal proses'));
	}
	
	function tambahCount()
	{
		$data['sp_id']= $_POST['sp_id'];
		$this->load->view('trucking/tambah_container', $data);
	}
	
	function show_table_container()
	{
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
		$sord = isset($_POST['sord'])?$_POST['sord']:'';
				
		$start = $batas * $hal - $batas;
		$start = ($start<0)?0:$start;			
		$_POST['start'] = $start; 
           
		$query = $this->afg_m_stuffing_plan->show_table_container($_POST['sp_id']);
		
		$hitung = count($query);	 
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
		   	$row->id_sp,
				$row->count_size,
				$row->count_no,
				$row->seal_no,
			);
		$i++;
		}
		echo json_encode($data);
	}
	
	function tambah_container_manage()
	{
		$value = $this->afg_m_stuffing_plan->tambah_container_manage($_POST);
		echo json_encode($value);
	}
	
	function tambah_container_manage_seal()
	{
		$value = $this->afg_m_stuffing_plan->tambah_container_manage_seal($_POST);
		//print_r($_POST);
		//die();
		echo json_encode($value);
	}
	function edit_container_manage_seal()
	{   
		$data["id"] = $this->input->post("id", TRUE);
		$data["jumlah"] = $this->input->post("count_num", TRUE);
		$data["tipe"] = $this->input->post("count_type", TRUE);
		$value = $this->afg_m_stuffing_plan->edit_container_manage_seal($data);
		//print_r($this->dbx->last_query());
		echo json_encode($value);
	}
	function create_schedule()
	{
		$this->load->view('stuffing_plan/create_schedule');
	}
	
	function dropdownAgent()
	{
		$agent = $this->agent_company_model->getListAgent()->result();
		$data['option_agent']['']=' ';
		foreach ($agent as $row)
		{	
			$data['option_agent'][$row->agent_company_code]=$row->agent_company_code;
		}		
		echo form_dropdown('dropdownAgent',$data['option_agent'],'','id="dropdownAgent"');
	}
	
	function dropdownShipper()
	{
		$agent = $this->user_model->getAllUser()->result();
		$data['option_shipper']['']=' ';
		foreach ($agent as $row)
		{	
			$data['option_shipper'][$row->shipper_id]=$row->shipper_id;
		}		
		echo form_dropdown('shipper',$data['option_shipper'],'','id="shipper"');
	}
	
	function spnum()
	{
		$tipe = $this->input->post('tipe');
		$bulan = $this->input->post('bulan');
		$tahun = $this->input->post('tahun');
		$tanggal = $this->input->post('tanggal');
		$date_array = explode("/",$tanggal); // split the array
		$var_day = $date_array[0]; //day seqment
		$var_month = $date_array[1]; //month segment
		$var_year = $date_array[2]; //year segment
		$new_date_format = "$var_year$var_month$var_day";
		if ($tipe == 'LCL')
		{$kode ='100';}
		else if ($tipe == 'FCL')
		{$kode ='101';}
		else if ($tipe == 'COLOAD')
		{$kode ='102';}
		else if ($tipe == 'AIR FREIGHT')
		{$kode ='200';}
		else if ($tipe == 'AIR FREIGHT IMPORT')
		{$kode ='600';}
		else if ($tipe == 'SF IMPORT LCL')
		{$kode ='300';}
		else if ($tipe == 'SF IMPORT FCL')
		{$kode ='301';}
		else if ($tipe == 'COLOAD IMPORT')
		{$kode ='302';}
		else if ($tipe == 'MESCELLENEOUS')
		{$kode ='500';}
		else if ($tipe == 'DOMESTICS')
		{$kode ='900';}
		else if ($tipe == 'FCL DOMESTICS')
		{$kode ='901';}
		else if ($tipe == 'OTHER DOMESTICS')
		{$kode ='902';}
		else if ($tipe == 'PROFIT SHARE PPJK')
		{$kode ='402';}
		else if ($tipe == 'CUSTOM')
		{$kode ='401';}
		else if ($tipe == 'CUSTOM IMPORT')
		{$kode ='400';}
		else if ($tipe == 'TRUCKING ONLY')
		{$kode ='700';}
		else if ($tipe == 'TRUCKING VENDOR')
		{$kode ='701';}
		else if ($tipe == 'TRUCKING CUSTOMER')
		{$kode ='702';}
		else if ($tipe == 'TRUCKING EXTERNAL')
		{$kode ='703';}
		else if ($tipe == 'WAREHOUSE')
		{$kode ='800';}
		$tahunjob=substr($this->session->userdata('db'),-2);
		$hasil= $this->afg_m_stuffing_plan->getNextIDNew($kode,$bulan,$tahun);
		// Ambil nama region dari session
		$region_name = $this->session->userdata('region_id');

		// Panggil model untuk mendapatkan ID-nya
		$reg_id = $this->afg_m_stuffing_plan->get_id_by_name($region_name);
		if($hasil=="0")
			 {$hasil=$reg_id."-$kode$tahunjob$bulan"."0001";}
		else
			{
			$hasil=$hasil+1;
			$hasil=$reg_id."-$kode$tahunjob$bulan". str_pad($hasil, 4, "0", STR_PAD_LEFT);
			}
		$this->save_stuffing_num($hasil,$tipe,$new_date_format);
		
		
		//$id = $this->afg_m_stuffing_plan->getNextID();
		//echo json_encode(array('refno'=>$hasil, 'id'=>$id));
	}
	
	function save_stuffing_num($kode_ref,$type,$tanggal) 
	{
		$data = $this->afg_m_stuffing_plan->save_stuffing_num($kode_ref,$type,$tanggal);
		echo json_encode($data);
		//$id = $this->afg_m_stuffing_plan->getNextID();
		//echo json_encode(array('refno'=>$kode_ref, 'id'=>$id));
	}
	
	function select_shipper_code()
	{
		$shipper_id = $this->input->post('shipper_id');
		$data = $this->user_model->getUserById($shipper_id);
		$nama = $data->shipper_name;
		$alamat = $data->shipper_address;
		echo json_encode(array('nama'=>$nama, 'alamat'=>$alamat));
	}
	
	function select_consignee_code()
	{
		$consignee_id = $this->input->post('consignee_id');
		$data = $this->user_model->get_consignee_id($consignee_id);
		$nama = $data->consignee_notify_name;
		$alamat = $data->consignee_notify_address;
		echo json_encode(array('nama'=>$nama, 'alamat'=>$alamat));
	}
	
	function select_notify_code()
	{
		$notify_id = $this->input->post('notify_id');
		$data = $this->user_model->get_consignee_id($notify_id);
		$nama = $data->consignee_notify_name;
		$alamat = $data->consignee_notify_address;
		echo json_encode(array('nama'=>$nama, 'alamat'=>$alamat));
	}

	function submitStuffingPlan()
	{
		$sp_id = $this->input->post('sp_id');
		$mode = $this->input->post('mode');
		$pesan = $this->input->post('pesan');
			
		$data = array (  
			'sp_id'                     => $sp_id,  
			'kode_ref'                  => $this->input->post('kode_ref'),  
			'tanggal_job'               => $this->fungsi->jin_date_sql($this->input->post('tanggal_job')),  
			'type_of_trans'             => $this->input->post('type_of_trans'),  
			'stuffing_date'             => $this->fungsi->jin_date_sql($this->input->post('stuffing_date')),  
			'warehouse'                 => $this->input->post('warehouse'),  
			'carrier'                   => strtoupper($this->input->post('carrier') ?? ''), // Handle null  
			'scac'                       => strtoupper(trim((string)($this->input->post('scac') ?? ''))),
			'mother_bl'                  => strtoupper(trim((string)($this->input->post('mother_bl') ?? ''))),
			'shipment_type_multi'       => $this->input->post('shipment_type_multi'),  
			'vessel'                    => strtoupper($this->input->post('vessel') ?? ''), // Handle null  
			'voyage'                    => strtoupper($this->input->post('voyage') ?? ''), // Handle null  
			'closing_date_time'         => $this->fungsi->jin_date_sql($this->input->post('closing_date_time')),  
			'closing_time'              => $this->input->post('closing_time'),  
			'etd_jkt'                   => $this->fungsi->jin_date_sql($this->input->post('etd_jkt')),  
			'atd'                       => $this->fungsi->jin_date_sql($this->input->post('atd')),  
			'eta'                       => $this->fungsi->jin_date_sql($this->input->post('eta')),  
			'ata'                       => $this->fungsi->jin_date_sql($this->input->post('ata')),  
			'port_loading'              => $this->input->post('port_loading'),  
			'transhipment_port'         => $this->input->post('transhipment_port'),  
			'destination'               => $this->input->post('destination'),  
			'cont_seal_no'              => strtoupper($this->input->post('cont_seal_no') ?? ''), // Handle null  
			'type_cont_qty'            => strtoupper($this->input->post('type_cont_qty') ?? ''), // Handle null  
			'type_cont_size'            => $this->input->post('type_cont_size'),  
			'agen_penagihan'            => $this->input->post('agen_penagihan'),  
			'isBatal'                   => $this->input->post('isBatal'),  
			'kurs'                      => $this->input->post('kurs'),  
			'kurs_selling'              => $this->input->post('kurs_selling'),  
			'schedule_id'               => $this->input->post('schedule_id') ?? '', // Handle null  
			'schedule_type'             => $this->input->post('route_type') ?? '', // Handle null  
			'region_id'                 => $this->session->userdata('region_id'),  
			'prepared'                  => $this->session->userdata('user_id'),  
		); 
		
		if($mode == 'add')
		{
			$data['oper']='add';
			$value = $this->afg_m_stuffing_plan->manage_stuffing($data);
			
			// manage_stuffing akan mengembalikan ['status' => inserted_id_atau_jumlah_row_terpengaruh]
			// Jika insert sukses, kita ambil sp_id yang baru dibuat untuk proses selanjutnya
			$new_sp_id = ($value['status'] > 0 && $value['status'] !== true) ? $value['status'] : $sp_id;
			
			$value['gocomet'] = array('status' => 'skipped', 'reason' => 'SAVE_FAILED');
			
			if ($value['status'] > 0 && (int)$new_sp_id > 0) {
				// Database sudah tersimpan. Baru setelah itu GoComet boleh dipanggil.
				$value['gocomet'] = $this->sync_gocomet_tracking((int)$new_sp_id, true);
			}
			
			// ========================================================
            // TIMELINE: MASTER JOB BARU DIBUAT
            // Schedule dan booking tidak dicatat ulang di sini karena keduanya
            // sudah terjadi sebelum Master Job. Sidebar menggabungkannya lewat
            // schedule_id dan booking_code.
            // ========================================================
            if ($value['status'] > 0) {
                $this->load->model('Timeline_model');
                $this->Timeline_model->record_timeline_event(
                    'MASTER',
                    $data['kode_ref'],
                    'CONSOLE_CREATED',
                    date('Y-m-d H:i:s'),
                    NULL,
                    'Master Job Console & Stuffing Plan berhasil dibuat',
                    NULL,
                    NULL,
                    NULL,
                    array(
                        'region_id' => $data['region_id'],
                        'schedule_id' => (int)$data['schedule_id'],
                        'schedule_type' => $this->timeline_schedule_source(
                            $data['kode_ref'],
                            $data['schedule_type']
                        )
                    )
                );
            }
            // ========================================================
			echo json_encode($value);
		}
		if($mode == 'copy')
		{
			$data['oper']='add';
			$value = $this->afg_m_stuffing_plan->manage_stuffing($data);
			$value['mode']= 'copy';
			// Copy dapat membawa MBL job lama. Jangan otomatis memakai quota atau
			// menempelkan tracking lama ke job hasil copy.
			$value['gocomet'] = array('status' => 'skipped', 'reason' => 'COPY_REQUIRES_NEW_MBL');
			echo json_encode($value);
		}
		if($mode == 'edit')
		{
			// 1. Ambil data lama sebelum diupdate untuk perbandingan
			$this->dbx->select('cont_seal_no, stuffing_date, atd, mother_bl, scac, id_gocomet, status_gocomet');
			$old_data = $this->dbx->get_where('trnstuffingplan', array(
				'sp_id' => (int)$sp_id,
				'region_id' => $this->session->userdata('region_id')
			))->row();

			if (!$old_data) {
				return $this->output
					->set_status_header(404)
					->set_content_type('application/json')
					->set_output(json_encode(array(
						'status' => 0,
						'mode' => 'edit',
						'message' => 'Stuffing plan tidak ditemukan pada region aktif.'
					)));
			}

			$old_cont          = $old_data ? $old_data->cont_seal_no : '';
			$old_stuffing_date = $old_data ? $old_data->stuffing_date : '';
			$old_atd           = $old_data ? $old_data->atd : '';
			$old_mbl           = strtoupper(trim((string)$old_data->mother_bl));
			$old_scac          = strtoupper(trim((string)$old_data->scac));
			$new_mbl           = strtoupper(trim((string)$data['mother_bl']));
			$new_scac          = strtoupper(trim((string)$data['scac']));
			$tracking_changed  = ($old_mbl !== $new_mbl || $old_scac !== $new_scac);

			// Jika job sudah punya tracking dan identitas tracking berubah, tahan
			// pengecekan status lama sampai update GoComet berhasil.
			if (!empty($old_data->id_gocomet) && $tracking_changed) {
				$data['status_gocomet'] = 'MBL_CHANGED_PENDING';
			}

			// 2. Eksekusi update data
			$status = $this->afg_m_stuffing_plan->update_stuffing($sp_id,$data);
			
			// ========================================================
			// TRIGGER TIMELINE AUDIT: EDIT MASTER JOB
			// ========================================================
			if ($status > 0) {
				$this->load->model('Timeline_model');
				$today = date('Y-m-d');
				$now   = date('Y-m-d H:i:s');
				
				// Fungsi helper untuk mengecek apakah event sudah ada di database timeline
				$check_event = function($kode_ref, $event_code) {
					$this->dbx->where('ref_type', 'MASTER');
					$this->dbx->where('ref_id', $kode_ref);
					$this->dbx->where('event_code', $event_code);
					if ($this->dbx->field_exists('region_id', 'x_timeline_audit')) {
						$this->dbx->where('region_id', $this->session->userdata('region_id'));
					}
					return $this->dbx->get('x_timeline_audit')->num_rows() > 0;
				};

				// A. INPUT NOMOR CONTAINER
				$new_cont = trim($data['cont_seal_no'] ?? '');
				if (!empty($new_cont)) {
				    // Catat jika ada perubahan ATAU jika belum pernah tercatat sama sekali
					if ($new_cont !== $old_cont || !$check_event($data['kode_ref'], 'CONTAINER_ASSIGNED')) {
						$this->Timeline_model->record_timeline_event(
							'MASTER', $data['kode_ref'], 'CONTAINER_ASSIGNED', $now, NULL,
							'Nomor Container / Seal diinput: ' . $new_cont, 
							$old_cont, $new_cont
						);
					}
				}

				// B. PELAKSANAAN STUFFING DI CFS
				$new_stuffing_date = $data['stuffing_date'];
				if (!empty($new_stuffing_date) && $new_stuffing_date !== '0000-00-00' && $new_stuffing_date !== '00000000') {
					if ($new_stuffing_date !== $old_stuffing_date || !$check_event($data['kode_ref'], 'STUFFING_EXECUTED')) {
					    
					    // UBAH DI SINI: Normalisasi format tanggal (Ubah / menjadi -) agar bisa dibaca PHP
					    $parse_stuf = str_replace('/', '-', $new_stuffing_date);
					    $is_future = strtotime($parse_stuf) > strtotime($today);
					    
					    $remarks = $is_future 
					        ? '⚠️ [ESTIMASI/MASA DEPAN] Rencana stuffing dijadwalkan pada: ' . $this->fungsi->jin_date_str($new_stuffing_date)
					        : 'Pelaksanaan stuffing kargo ke dalam kontainer selesai (Tanggal Aktual: ' . $this->fungsi->jin_date_str($new_stuffing_date) . ')';

						$this->Timeline_model->record_timeline_event(
							'MASTER', $data['kode_ref'], 'STUFFING_EXECUTED', $now, NULL,
							$remarks, 
							$old_stuffing_date, $new_stuffing_date
						);
					}
				}

				// C. KAPAL BERANGKAT (ATD / Actual Time of Departure)
				$new_atd = $data['atd'];
				if (!empty($new_atd) && $new_atd !== '0000-00-00' && $new_atd !== '00000000') {
					if ($new_atd !== $old_atd || !$check_event($data['kode_ref'], 'VESSEL_DEPARTED')) {
					    
					    // UBAH DI SINI: Normalisasi format tanggal (Ubah / menjadi -) agar bisa dibaca PHP
					    $parse_atd = str_replace('/', '-', $new_atd);
					    $is_future = strtotime($parse_atd) > strtotime($today);
					    
					    $remarks = $is_future 
					        ? '⚠️ [ESTIMASI/MASA DEPAN] Kapal dijadwalkan berangkat pada: ' . $this->fungsi->jin_date_str($new_atd)
					        : 'Kapal telah berangkat (Actual Time of Departure: ' . $this->fungsi->jin_date_str($new_atd) . ')';

						$this->Timeline_model->record_timeline_event(
							'MASTER', $data['kode_ref'], 'VESSEL_DEPARTED', $now, NULL,
							$remarks, 
							$old_atd, $new_atd
						);
					}
				}
			}
			// ========================================================

			// Save utama sudah selesai. Sinkronisasi tidak lagi berjalan paralel
			// dengan update database.
			$gocomet = $status > 0 ? $this->sync_gocomet_tracking((int)$sp_id, $tracking_changed) : null;

			echo json_encode(array(
				'status' => $status,
				'mode' => 'edit',
				'gocomet' => $gocomet
			));
		}
		if($mode == 'del')
		{
			$data['oper']='del';
			$value = $this->afg_m_stuffing_plan->delete_stuffing($sp_id,$pesan,$data['oper']);
			//print_r($this->dbx->last_query());
			echo json_encode($value);
		}
	}
	
	/**
	 * Cek status respon penerimaan BC 1.1 dari Neo Customs Partner API.
	 * GET /manifests/by-reference/{referenceId}/respons
	 *
	 * referenceId = job number (txtRefno2 / kode_ref)
	 *
	 * Jika data respon SUDAH ADA NILAINYA (array tidak kosong), catat/perbarui
	 * timeline event MANIFEST_UPLOADED dengan keterangan "BC 1.1 sudah Recon."
	 */
	public function cek_respon_bc11() {
		$kode_ref = $this->input->post('kode_ref');

		if (empty($kode_ref)) {
			echo json_encode(['status' => false, 'message' => 'Kode Ref (Master Job) tidak valid.']);
			return;
		}

		try {
			$client = new \GuzzleHttp\Client();
			$response = $client->request(
				'GET',
				$this->customs_api_base_url . '/manifests/by-reference/' . rawurlencode($kode_ref) . '/respons',
				[
					'headers' => [
						'Accept'    => 'application/json',
						'X-API-Key' => $this->customs_api_key,
					],
				]
			);

			$respons = json_decode($response->getBody(), true);
			// Beberapa API membungkus list di dalam key "data", jaga-jaga kedua bentuk didukung.
			$list = is_array($respons) && isset($respons['data']) ? $respons['data'] : (is_array($respons) ? $respons : []);
			$has_data = !empty($list);

			// ---------------------------------------------------------
			// TRIGGER TIMELINE AUDIT: BC 1.1 sudah Recon
			// ---------------------------------------------------------
			if ($has_data) {
				$this->load->model('Timeline_model');
				$this->Timeline_model->record_timeline_event(
					'MASTER',
					$kode_ref,
					'MANIFEST_UPLOADED',
					date('Y-m-d H:i:s'),
					NULL,
					'BC 1.1 sudah Recon.'
				);
			}

			echo json_encode([
				'status'   => true,
				'has_data' => $has_data,
				'respons'  => $list,
			]);

		} catch (\GuzzleHttp\Exception\RequestException $e) {
			$err_message = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : $e->getMessage();
			echo json_encode(['status' => false, 'message' => 'Gagal mengambil data respon dari Bea Cukai: ' . $err_message]);
		} catch (Exception $e) {
			echo json_encode(['status' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
		}
	}

	/**
	 * Menampilkan (stream) dokumen BC 1.1 / AWB dari Neo Customs Partner API.
	 * GET /manifests/by-reference/{referenceId}/awb-pdf
	 */
	public function show_bc11_awb($kode_ref) {
		$this->_stream_customs_pdf(
			'/manifests/by-reference/' . rawurlencode($kode_ref) . '/awb-pdf',
			'BC11_AWB_' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $kode_ref) . '.pdf'
		);
	}

	/**
	 * Menampilkan (stream) dokumen Respon Penerimaan BC 1.1 dari Neo Customs Partner API.
	 * GET /manifests/by-reference/{referenceId}/respons/{idRespon}/pdf
	 */
	public function show_bc11_respon_pdf($kode_ref, $id_respon) {
		$this->_stream_customs_pdf(
			'/manifests/by-reference/' . rawurlencode($kode_ref) . '/respons/' . rawurlencode($id_respon) . '/pdf',
			'BC11_RESPON_' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $kode_ref) . '_' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $id_respon) . '.pdf'
		);
	}

	/**
	 * Helper: ambil PDF dari Neo Customs Partner API lalu alirkan (stream) ke browser
	 * agar bisa langsung ditampilkan (mis. di dalam iframe/tab baru).
	 */
	private function _stream_customs_pdf($endpoint_path, $filename) {
		try {
			$client = new \GuzzleHttp\Client();
			$response = $client->request(
				'GET',
				$this->customs_api_base_url . $endpoint_path,
				[
					'headers' => [
						'Accept'    => 'application/pdf',
						'X-API-Key' => $this->customs_api_key,
					],
				]
			);

			header('Content-Type: application/pdf');
			header('Content-Disposition: inline; filename="' . $filename . '"');
			echo $response->getBody();

		} catch (\GuzzleHttp\Exception\RequestException $e) {
			$status = $e->getResponse() ? $e->getResponse()->getStatusCode() : 500;
			http_response_code($status);
			header('Content-Type: text/plain');
			echo 'Gagal mengambil dokumen dari Bea Cukai: ' . ($e->getResponse() ? $e->getResponse()->getBody()->getContents() : $e->getMessage());
		} catch (Exception $e) {
			http_response_code(500);
			header('Content-Type: text/plain');
			echo 'Terjadi kesalahan: ' . $e->getMessage();
		}
	}
	

	/**
	 * PDF Tally Sheet dari GCL Warehouse untuk row detail yang dipilih.
	 * Browser hanya mengirim sp_detail_id; booking_code dibaca lagi dari DB + region session.
	 */
	/**
	 * PDF Tally Sheet dari GCL Warehouse.
	 *
	 * Endpoint:
	 * GET /api/v1/inventory/tallysheet/{booking_code}/pdf?template=default
	 *
	 * Auth:
	 * Authorization: Bearer <fixed WMS bearer token>
	 */
	public function tally_sheet_pdf($sp_detail_id)
	{
		if (!$this->access_control->can_feature('stuffing_upload_tracking')) {
			return $this->_document_error(
				'Access denied',
				'Anda tidak memiliki hak akses untuk dokumen ini.',
				403
			);
		}

		$row = $this->_document_detail_row($sp_detail_id);

		if (!$row) {
			return $this->_document_error(
				'Tally Sheet',
				'Detail shipment tidak ditemukan / region tidak sesuai.',
				404
			);
		}

		$booking_code = trim((string)$row->booking_code);

		if ($booking_code === '') {
			return $this->_document_error(
				'Tally Sheet',
				'Booking Confirmation Code belum tersedia pada detail ini.',
				422
			);
		}

		/*
		 * Template default.
		 * Tetap support parameter:
		 * ?template=thermal
		 * ?template=new
		 * ?template=no_template
		 * ?template=default
		 */
		$template = strtolower(
			trim((string)$this->input->get('template', true))
		);

		if ($template === '') {
			$template = 'default';
		}

		if (!in_array(
			$template,
			array(
				'thermal',
				'new',
				'no_template',
				'default'
			),
			true
		)) {
			$template = 'default';
		}

		/*
		 * URL BARU:
		 *
		 * https://gcl-warehouse.com/
		 * api/v1/inventory/tallysheet/{booking_code}/pdf
		 */
		$url =
			rtrim($this->warehouse_tally_base_url, '/')
			. '/'
			. rawurlencode($booking_code)
			. '/pdf'
			. '?template='
			. rawurlencode($template);

		/*
		 * Tidak perlu get_token / refresh_token lagi.
		 */
		$result = $this->_warehouse_binary_get(
			$url,
			$this->wms_bearer_token
		);

		/*
		 * Token fixed invalid / expired.
		 */
		if ((int)$result['status'] === 401) {
			return $this->_document_error(
				'Tally Sheet',
				'Bearer token WMS tidak valid / tidak mempunyai akses.',
				401
			);
		}

		if ((int)$result['status'] === 403) {
			return $this->_document_error(
				'Tally Sheet',
				'Bearer token WMS tidak mempunyai permission untuk Tally Sheet.',
				403
			);
		}

		/*
		 * Response selain 200 / bukan PDF.
		 */
		if (
			(int)$result['status'] !== 200 ||
			!$this->_looks_like_pdf(
				$result['body'],
				$result['content_type']
			)
		) {
			$message = $this->_external_error_message(
				$result['body'],
				'Gagal mengambil Tally Sheet dari GCL Warehouse.'
			);

			return $this->_document_error(
				'Tally Sheet',
				$message,
				(int)$result['status'] ?: 502
			);
		}

		$filename =
			'tallysheet_'
			. preg_replace(
				'/[^A-Za-z0-9_.-]/',
				'_',
				$booking_code
			)
			. '.pdf';

		return $this->_stream_pdf_binary(
			$result['body'],
			$filename
		);
	}

	/**
	 * Editor PEB / NPE / PPB untuk row detail yang dipilih (tombol PEB di panel Stuffing Plan).
	 * Mengembalikan FRAGMEN HTML (bukan JSON): widget upload + form PEB/NPE/PPB milik booking_code row ini,
	 * dimuat ke modal #modal_peb. Widget-nya sama dengan di Edit Booking: data masuk ke tabel PEB per booking_code
	 * (multi-PEB), otomatis diteruskan ke gudang (WMS), dan dibaca langsung oleh cetak Stuffing Plan.
	 */
	public function peb_editor($sp_detail_id)
	{
		header('Content-Type: text/html; charset=UTF-8');
		if (!$this->access_control->can_feature('stuffing_upload_tracking')) {
			echo $this->_peb_editor_msg('Anda tidak memiliki hak akses untuk dokumen PEB.');
			return;
		}
		$row = $this->_document_detail_row($sp_detail_id);
		if (!$row) {
			echo $this->_peb_editor_msg('Detail shipment tidak ditemukan / region tidak sesuai.');
			return;
		}
		$code = trim((string) $row->booking_code);
		if ($code === '') {
			echo $this->_peb_editor_msg('Row ini tidak terhubung ke booking (dibuat manual), sehingga PEB/NPE/PPB tidak bisa diisi dari sini. '
				. 'Bila booking-nya ada, isi lewat Edit Booking.');
			return;
		}
		$this->load->model('pricelist/peb_model');
		if (($m = $this->peb_model->schema_message()) !== '') {
			echo $this->_peb_editor_msg($m);
			return;
		}
		if (!$this->peb_model->find_booking($code)) {
			echo $this->_peb_editor_msg('Booking ' . $code . ' tidak ditemukan di database tahun ini.');
			return;
		}

		$h = function ($t) { return htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8'); };
		echo '<div class="pwsp-head">'
			. '<span><b>HB/L</b> ' . $h(trim((string) $row->hb_l) !== '' ? $row->hb_l : '-') . '</span>'
			. '<span><b>Shipper</b> ' . $h(trim((string) $row->shipper_print) !== '' ? $row->shipper_print : '-') . '</span>'
			. '<span><b>Job</b> ' . $h(trim((string) $row->kode_ref) !== '' ? $row->kode_ref : $row->code) . '</span>'
			. '<span><b>Booking</b> ' . $h($code) . '</span></div>';
		$this->load->view('pricelist/partials/peb_widget', array(
			'booking_code' => $code,
			'widget_id'    => 'pebw_sp',
			'title'        => '&#128737; PEB / NPE / PPB',
			'note'         => 'Isi manual, atau upload PDF PEB / NPE / PPB dari CEISA (boleh sekaligus). Otomatis tersimpan, diteruskan ke gudang, dan tercetak di Stuffing Plan.',
		));
	}

	private function _peb_editor_msg($msg)
	{
		return '<div class="pwsp-msg">' . htmlspecialchars((string) $msg, ENT_QUOTES, 'UTF-8') . '</div>';
	}

	/**
	 * Nomor PEB & NPE (satu baris per PEB, urut sama) untuk kolom cetak Stuffing Plan.
	 * PEB belum bernomor (baru AJU) tampil sebagai "AJU xxxxxx"; PEB tanpa NPE -> "-" di kolom NPE.
	 * @return array array(list PEB, list NPE)
	 */
	private function _sp_peb_npe_lines($list)
	{
		$peb = array();
		$npe = array();
		foreach ((array) $list as $p) {
			$no  = trim((string) $p['peb_number']);
			$aju = trim((string) $p['aju_number']);
			if ($no === '' && $aju !== '') { $no = 'AJU ' . substr($aju, -6); }
			if ($no === '') { continue; }
			$peb[] = $no;
			$n = trim((string) $p['npe_number']);
			$npe[] = ($n !== '') ? $n : '-';
		}
		return array($peb, $npe);
	}

	/**
	 * PDF PEB berdasarkan House BL pada row detail yang dipilih.
	 */
	public function peb_pdf($sp_detail_id)
	{
		if (!$this->access_control->can_feature('stuffing_upload_tracking')) {
			return $this->_document_error('Access denied', 'Anda tidak memiliki hak akses untuk dokumen ini.', 403);
		}
		$row = $this->_document_detail_row($sp_detail_id);
		if (!$row) return $this->_document_error('PEB', 'Detail shipment tidak ditemukan / region tidak sesuai.', 404);

		$house_bl = trim((string)$row->hb_l);
		if ($house_bl === '') {
			return $this->_document_error('PEB', 'House BL belum tersedia pada detail ini.', 422);
		}

		$ref_no = trim((string)$row->code);
		if ($ref_no === '') {
			return $this->_document_error('PEB', 'Kode Ref (Master Job) belum tersedia pada detail ini.', 422);
		}

		return $this->_lookup_and_stream_peb($ref_no, $house_bl, (int)$sp_detail_id);
	}

	/**
	 * Lookup + tampilkan PEB lewat endpoint yang sudah diverifikasi berfungsi (via Postman):
	 *   GET /manifests/by-reference/{referenceId}/bls/by-house-bl/{houseBl}/source-pdf
	 *
	 * PENTING: endpoint ini dipakai untuk SEMUA House BL, baik yang mengandung slash
	 * maupun tidak — rawurlencode() sudah menangani slash dengan aman, jadi tidak perlu
	 * (dan tidak boleh) bercabang ke endpoint lama 'peb/by-house-bl/{houseBl}/pdf' yang
	 * sudah tidak dilayani API (itulah penyebab error "PEB tidak ditemukan" sebelumnya).
	 *
	 * Respon sukses (status 200) dari endpoint ini bisa berupa:
	 *  - PDF langsung (Content-Type: application/pdf)  -> House BL tidak ambigu, 1 PEB match.
	 *  - JSON berisi daftar nomor aju                   -> House BL ambigu, dipakai > 1 PEB.
	 */
	private function _lookup_and_stream_peb($ref_no, $house_bl, $sp_detail_id)
	{
		$lookup = $this->_customs_get(
			'/manifests/by-reference/' . rawurlencode($ref_no) . '/bls/by-house-bl/' . rawurlencode($house_bl) . '/source-pdf',
			'application/pdf, application/json'
		);

		if ($lookup['status'] !== 200) {
			return $this->_handle_peb_failure($lookup['status'], $lookup['body'], $house_bl, $sp_detail_id);
		}

		// Kasus tidak ambigu: API langsung mengembalikan PDF, tampilkan langsung.
		if ($this->_looks_like_pdf($lookup['body'], $lookup['content_type'])) {
			$filename = 'PEB_' . preg_replace('/[^A-Za-z0-9_.-]/', '_', $house_bl) . '.pdf';
			return $this->_stream_pdf_binary($lookup['body'], $filename);
		}

		// Kasus ambigu: API mengembalikan JSON berisi daftar nomor aju.
		$ajus = $this->_extract_peb_aju_candidates($lookup['body']);
		if (count($ajus) === 1) {
			return $this->_stream_peb_by_aju($ajus[0], $house_bl, $sp_detail_id);
		}
		if (count($ajus) > 1) {
			return $this->_render_peb_candidates($house_bl, $ajus, $sp_detail_id);
		}

		return $this->_document_error('PEB', 'Data PEB ditemukan tetapi nomor aju tidak dapat dibaca dari response API.', 502);
	}

	/** Ambiguous PEB candidate: user memilih nomor aju dari halaman kandidat. */
	public function peb_pdf_by_aju($sp_detail_id)
	{
		if (!$this->access_control->can_feature('stuffing_upload_tracking')) {
			return $this->_document_error('Access denied', 'Anda tidak memiliki hak akses untuk dokumen ini.', 403);
		}
		$row = $this->_document_detail_row($sp_detail_id);
		if (!$row) return $this->_document_error('PEB', 'Detail shipment tidak ditemukan / region tidak sesuai.', 404);

		$aju = trim((string)$this->input->get('aju', true));
		if ($aju === '') return $this->_document_error('PEB', 'Nomor aju kosong.', 400);

		$house_bl = trim((string)$row->hb_l);
		$ref_no   = trim((string)$row->code);

		$verify = $this->_customs_get(
			'/manifests/by-reference/' . rawurlencode($ref_no) . '/bls/by-house-bl/' . rawurlencode($house_bl) . '/source-pdf',
			'application/pdf, application/json'
		);

		// Kalau ternyata sekarang sudah tidak ambigu (API langsung balas PDF), tampilkan langsung.
		if ($verify['status'] === 200 && $this->_looks_like_pdf($verify['body'], $verify['content_type'])) {
			$filename = 'PEB_' . preg_replace('/[^A-Za-z0-9_.-]/', '_', $house_bl) . '.pdf';
			return $this->_stream_pdf_binary($verify['body'], $filename);
		}

		$allowed_ajus = $verify['status'] === 200 ? $this->_extract_peb_aju_candidates($verify['body']) : array();
		if (!$allowed_ajus || !in_array($aju, $allowed_ajus, true)) {
			return $this->_document_error('PEB', 'Nomor aju tidak cocok dengan House BL pada row detail yang dipilih.', 403);
		}

		return $this->_stream_peb_by_aju($aju, $house_bl, (int)$sp_detail_id);
	}

	private function _stream_peb_by_aju($aju, $house_bl, $sp_detail_id)
	{
		$pdf = $this->_customs_get('peb/' . rawurlencode($aju) . '/pdf', 'application/pdf, application/json');
		if ($pdf['status'] === 200 && $this->_looks_like_pdf($pdf['body'], $pdf['content_type'])) {
			$filename = 'PEB_' . preg_replace('/[^A-Za-z0-9_.-]/', '_', $aju) . '.pdf';
			return $this->_stream_pdf_binary($pdf['body'], $filename);
		}
		return $this->_handle_peb_failure($pdf['status'], $pdf['body'], $house_bl, $sp_detail_id);
	}

	private function _handle_peb_failure($status, $body, $house_bl, $sp_detail_id)
	{
		$status = (int)$status ?: 502;
		$decoded = json_decode((string)$body, true);
		$code = $this->_extract_external_error_code($decoded);

		if ($code === 'PEB_NOT_REGISTERED' || stripos((string)$body, 'PEB_NOT_REGISTERED') !== false) {
			return $this->_document_error(
				'PEB belum terdaftar',
				'PEB untuk House BL ' . $house_bl . ' belum memiliki NOPEN / belum terdaftar di Bea Cukai. Draft tidak ditampilkan.',
				404
			);
		}
		if ($status === 404) {
			return $this->_document_error('PEB tidak ditemukan', $this->_external_error_message($body, 'Data PEB untuk House BL tersebut tidak ditemukan.'), 404);
		}

		if ($status === 409 || $code === 'PEB_AMBIGUOUS') {
			$ajus = $this->_extract_peb_aju_candidates($body);
			if ($ajus) return $this->_render_peb_candidates($house_bl, $ajus, $sp_detail_id);
			return $this->_document_error('PEB Ambiguous', 'House BL dipakai oleh beberapa PEB, tetapi daftar nomor aju tidak dapat dibaca dari response API.', 409);
		}

		return $this->_document_error('PEB', $this->_external_error_message($body, 'Gagal mengambil PDF PEB.'), $status);
	}

	private function _render_peb_candidates($house_bl, array $ajus, $sp_detail_id)
	{
		http_response_code(409);
		header('Content-Type: text/html; charset=UTF-8');
		$safe_hbl = htmlspecialchars($house_bl, ENT_QUOTES, 'UTF-8');
		echo '<!doctype html><html><head><meta charset="utf-8"><title>Pilih PEB</title>';
		echo '<style>body{font-family:Arial,sans-serif;background:#f4f6f9;color:#263238;padding:28px}.box{max-width:720px;margin:auto;background:#fff;border:1px solid #d8dee6;border-radius:8px;padding:24px}.btn{display:block;margin:10px 0;padding:12px 14px;background:#00245E;color:#fff;text-decoration:none;border-radius:5px}.muted{color:#68737d;font-size:13px}</style></head><body><div class="box">';
		echo '<h2>PEB membutuhkan pilihan</h2><p>House BL <b>'.$safe_hbl.'</b> dipakai oleh beberapa PEB. Pilih nomor aju:</p>';
		foreach ($ajus as $aju) {
			$safe_aju = htmlspecialchars($aju, ENT_QUOTES, 'UTF-8');
			$url = base_url('afg_c_stuffing_plan/peb_pdf_by_aju/' . (int)$sp_detail_id) . '?aju=' . rawurlencode($aju);
			echo '<a class="btn" href="'.htmlspecialchars($url, ENT_QUOTES, 'UTF-8').'">'.$safe_aju.'</a>';
		}
		echo '<p class="muted">PDF hanya tersedia untuk PEB yang sudah terdaftar / memiliki NOPEN.</p></div></body></html>';
		exit;
	}

	private function _extract_peb_aju_candidates($body)
	{
		$data = is_string($body) ? json_decode($body, true) : $body;
		if (!is_array($data)) return array();

		$found = array();
		$walk = function ($node) use (&$walk, &$found) {
			if (!is_array($node)) return;
			foreach ($node as $key => $value) {
				$normalized = strtolower(preg_replace('/[^a-z0-9]/i', '', (string)$key));
				if (in_array($normalized, array('nomoraju', 'noaju', 'ajuanumber', 'ajuanumber'), true) && is_scalar($value)) {
					$v = trim((string)$value);
					if ($v !== '') $found[$v] = $v;
				}
				if (is_array($value)) $walk($value);
			}
		};
		$walk($data);
		return array_values($found);
	}

	private function _customs_get($path, $accept)
	{
		try {
			$client = new \GuzzleHttp\Client();
			$response = $client->request('GET', rtrim($this->customs_api_base_url, '/') . '/' . ltrim($path, '/'), array(
				'http_errors' => false,
				'headers' => array(
					'Accept' => $accept,
					'X-API-Key' => $this->customs_api_key,
				),
			));
			return array(
				'status' => (int)$response->getStatusCode(),
				'content_type' => (string)$response->getHeaderLine('Content-Type'),
				'body' => (string)$response->getBody(),
			);
		} catch (Exception $e) {
			return array('status' => 502, 'content_type' => 'text/plain', 'body' => $e->getMessage());
		}
	}

	private function _document_detail_row($sp_detail_id)
	{
		$sp_detail_id = (int)$sp_detail_id;
		if ($sp_detail_id <= 0) return null;
		return $this->dbx
			->select('sp_detail_id,code, kode_ref, booking_code, hb_l, shipper_print')
			->where('sp_detail_id', $sp_detail_id)
			->where('region_id', $this->session->userdata('region_id'))
			->get('trnstuffingplandetail')
			->row();
	}

	private function _warehouse_binary_get($url, $token)
	{
		$ch = curl_init($url);

		curl_setopt_array($ch, array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPGET        => true,

			CURLOPT_CONNECTTIMEOUT => 10,
			CURLOPT_TIMEOUT        => 60,

			CURLOPT_HTTPHEADER => array(
				'Accept: application/pdf',
				'Authorization: Bearer ' . $token,
			),
		));

		$body = curl_exec($ch);

		$status = (int)curl_getinfo(
			$ch,
			CURLINFO_HTTP_CODE
		);

		$content_type = (string)curl_getinfo(
			$ch,
			CURLINFO_CONTENT_TYPE
		);

		$error = curl_error($ch);

		curl_close($ch);

		if ($body === false) {
			$body = '';
		}

		if ($error !== '') {
			log_message(
				'error',
				'GCL Warehouse Tally cURL error: ' . $error
			);

			if ($status === 0) {
				$status = 502;
			}
		}

		return array(
			'status'       => $status,
			'content_type' => $content_type,
			'body'         => $body,
		);
	}

	private function _looks_like_pdf($body, $content_type = '')
	{
		return stripos((string)$content_type, 'application/pdf') !== false || strncmp((string)$body, '%PDF-', 5) === 0;
	}

	private function _stream_pdf_binary($binary, $filename)
	{
		if (ob_get_length()) @ob_end_clean();
		header('Content-Type: application/pdf');
		header('Content-Disposition: inline; filename="' . str_replace('"', '', $filename) . '"');
		header('Content-Length: ' . strlen($binary));
		header('Cache-Control: private, max-age=0, must-revalidate');
		echo $binary;
		exit;
	}

	private function _extract_external_error_code($data)
	{
		if (!is_array($data)) return '';

		// Common top-level shapes. Never cast an array/object to string.
		foreach (array('code', 'error_code', 'errorCode', 'status_code') as $key) {
			if (isset($data[$key]) && is_scalar($data[$key])) {
				$value = strtoupper(trim((string)$data[$key]));
				if ($value !== '') return $value;
			}
		}

		// APIs commonly wrap details in error/data/detail. Search them recursively.
		foreach (array('error', 'data', 'detail', 'errors') as $key) {
			if (isset($data[$key]) && is_array($data[$key])) {
				$value = $this->_extract_external_error_code($data[$key]);
				if ($value !== '') return $value;
			}
		}

		return '';
	}

	private function _external_error_message($body, $fallback)
	{
		$data = json_decode((string)$body, true);
		$message = $this->_extract_external_error_message_value($data);
		if ($message !== '') return $message;

		$text = trim(strip_tags((string)$body));
		return $text !== '' ? substr($text, 0, 700) : $fallback;
	}

	private function _extract_external_error_message_value($data)
	{
		if (!is_array($data)) return '';

		foreach (array('message', 'error_description', 'description', 'reason', 'detail') as $key) {
			if (isset($data[$key]) && is_scalar($data[$key])) {
				$value = trim((string)$data[$key]);
				if ($value !== '') return $value;
			}
		}

		if (isset($data['error']) && is_scalar($data['error'])) {
			$value = trim((string)$data['error']);
			if ($value !== '') return $value;
		}

		foreach (array('error', 'data', 'detail', 'errors') as $key) {
			if (isset($data[$key]) && is_array($data[$key])) {
				$value = $this->_extract_external_error_message_value($data[$key]);
				if ($value !== '') return $value;
			}
		}

		return '';
	}

	private function _document_error($title, $message, $status = 500)
	{
		http_response_code((int)$status);
		header('Content-Type: text/html; charset=UTF-8');
		$title = htmlspecialchars((string)$title, ENT_QUOTES, 'UTF-8');
		$message = htmlspecialchars((string)$message, ENT_QUOTES, 'UTF-8');
		echo '<!doctype html><html><head><meta charset="utf-8"><title>'.$title.'</title>';
		echo '<style>body{font-family:Arial,sans-serif;background:#f4f6f9;padding:30px;color:#263238}.box{max-width:720px;margin:auto;background:#fff;border-left:5px solid #c62828;padding:22px;box-shadow:0 2px 8px rgba(0,0,0,.08)}h2{margin-top:0;color:#00245E}p{line-height:1.55}</style></head><body>';
		echo '<div class="box"><h2>'.$title.'</h2><p>'.$message.'</p></div></body></html>';
		exit;
	}

	function getTabelStuffingPlan()
	{
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
		$sord = isset($_POST['sord'])?$_POST['sord']:'';
				
		$start = $batas * $hal - $batas;
		$start = ($start<0)?0:$start;			
		$_POST['start'] = $start;
		$level_user = $this->session->userdata('level_id');
		$q = $this->afg_m_stuffing_plan->get_all_stuffing($_POST,false,$level_user); 
		$query = $this->afg_m_stuffing_plan->get_all_stuffing($_POST,true,$level_user); 
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
		    $data->rows[$i]['id']=$row->sp_id;
		    $data->rows[$i]['cell']=array(
		   	    $row->sp_id,
				$row->kode_ref,
				$this->fungsi->jin_date_str($row->tanggal_job),
				$this->fungsi->jin_date_str($row->etd_jkt),
				$row->vessel.' '.$row->voyage,
				$row->carrier,
				$row->mother_bl,
				$row->port_loading,
				$row->destination,
				$row->cont_seal_no,
				$this->afg_m_stuffing_plan->get_status_posted($row->kode_ref),
				$this->fungsi->get_user_by_id($row->prepared),
			);
		$i++;
		}
		echo json_encode($data);
	}
	
	/**
	 * Tentukan sumber schedule dari tipe job dan rutenya.
	 * schedule_id dapat sama pada tabel Direct/Via atau Export/Import, jadi nama
	 * sumber wajib ikut dibawa ketika timeline digabungkan.
	 */
	private function timeline_schedule_source($kode_ref, $route_type)
	{
		$job_type = (int)substr(trim((string)$kode_ref), 4, 3);
		$is_import = in_array($job_type, array(300, 301, 302, 400, 402, 600), TRUE);
		$is_via = strpos(strtoupper(trim((string)$route_type)), 'VIA') !== FALSE;

		return ($is_import ? 'IMPORT_' : 'EXPORT_').($is_via ? 'VIA' : 'DIRECT');
	}

	public function get_timeline_data()
    {
        $kode_ref = trim((string)$this->input->post('kode_ref'));
        $region_id = trim((string)$this->session->userdata('region_id'));

        if ($kode_ref === '') {
            echo json_encode(array('status' => FALSE, 'message' => 'Kode Ref kosong'));
            return;
        }

        // Ambil lineage Master Job. Region wajib dipakai agar kode/ID dari
        // cabang lain tidak ikut terbaca.
        $this->dbx->where('kode_ref', $kode_ref);
        if ($region_id !== '' && $this->dbx->field_exists('region_id', 'trnstuffingplan')) {
            $this->dbx->where('region_id', $region_id);
        }
        $master = $this->dbx->get('trnstuffingplan')->row();

        if (!$master) {
            echo json_encode(array(
                'status' => FALSE,
                'has_data' => FALSE,
                'message' => 'Master Job tidak ditemukan pada region aktif.'
            ));
            return;
        }

        $schedule_id = (int)($master->schedule_id ?? 0);
        $route_type = trim((string)($master->schedule_type ?? ''));
        $schedule_source = $this->timeline_schedule_source($kode_ref, $route_type);
        $booking_type = strpos($schedule_source, 'IMPORT_') === 0 ? 'IMPORT' : 'EXPORT';

        $this->load->model('Booking_model');
        $this->load->model('Timeline_model');

        $booking = $this->Booking_model->get_booking_timeline_context(
            $schedule_id,
            $region_id,
            $route_type,
            $booking_type
        );

        // Gabungkan audit dari tiga sumber:
        // MASTER(kode_ref), BOOKING(booking_code), SCHEDULE(schedule_id+tipe).
        $events_db = $this->Timeline_model->get_master_timeline_events(
            $kode_ref,
            $schedule_id,
            $schedule_source,
            $booking['booking_codes'],
            $region_id
        );

        // Ambil kejadian pertama untuk setiap milestone. Perubahan lanjutan tetap
        // berada di audit trail tetapi tidak mengganti tanggal milestone awal.
        $mapped_events = array();
        foreach ($events_db as $event) {
            $event_code = strtoupper(trim((string)$event->event_code));
            if ($event_code !== '' && !isset($mapped_events[$event_code])) {
                $mapped_events[$event_code] = $event;
            }
        }

        // ================================================================
        // FIX: PRE_ALERT_DOWNLOAD bisa mempunyai region_id NULL.
        // M_pre_alert_agent::log_download() versi lama mencatat lokasi pada
        // location_name, tetapi tidak mengisi region_id. Timeline_model versi
        // sekarang memfilter region secara ketat, sehingga event itu hilang.
        //
        // Master Job di atas SUDAH diverifikasi pada region aktif. Query ini
        // hanya menerima event untuk ref_type/ref_id Master tersebut dan
        // membolehkan region aktif atau NULL/kosong dari audit legacy.
        // Ambil DOWNLOAD PERTAMA sebagai waktu milestone, tanpa mengubah log.
        // ================================================================
        if ($this->dbx->table_exists('x_timeline_audit')) {
            $this->dbx->select('id_log, event_code, event_date, confirmed_by, remarks, proof_document');
            $this->dbx->from('x_timeline_audit');
            $this->dbx->where('ref_type', 'MASTER');
            $this->dbx->where('ref_id', $kode_ref);
            $this->dbx->where('event_code', 'PRE_ALERT_DOWNLOAD');

            if ($region_id !== '' && $this->dbx->field_exists('region_id', 'x_timeline_audit')) {
                $this->dbx->group_start();
                $this->dbx->where('region_id', $region_id);
                $this->dbx->or_where('region_id IS NULL', NULL, FALSE);
                $this->dbx->or_where('region_id', '');
                $this->dbx->group_end();
            }

            $this->dbx->order_by('event_date', 'ASC');
            $this->dbx->order_by('id_log', 'ASC');
            $first_pre_alert_download = $this->dbx->limit(1)->get()->row();

            if ($first_pre_alert_download && (
                !isset($mapped_events['PRE_ALERT_DOWNLOAD']) ||
                strcmp(
                    (string)$first_pre_alert_download->event_date,
                    (string)$mapped_events['PRE_ALERT_DOWNLOAD']->event_date
                ) < 0
            )) {
                $mapped_events['PRE_ALERT_DOWNLOAD'] = $first_pre_alert_download;
            }
        }

        // Schedule lama mungkin dibuat sebelum fitur audit ada. Baca langsung
        // dari tabel schedule agar Step 1 tetap aktif dengan tanggal aslinya.
        if (!isset($mapped_events['SCHEDULE_PLANNED']) && $schedule_id > 0) {
            $schedule_event = $this->Timeline_model->get_schedule_milestone(
                $schedule_id,
                $schedule_source,
                $region_id
            );
            if ($schedule_event) {
                $mapped_events['SCHEDULE_PLANNED'] = $schedule_event;
            }
        }

        // Booking lama juga mungkin belum mempunyai audit. Data booking sendiri
        // adalah bukti milestone dan tanggal created_at menjadi tanggal event.
        if (!isset($mapped_events['BOOKING_RECEIVED']) && $booking['TOTAL'] > 0) {
            $mapped_events['BOOKING_RECEIVED'] = (object)array(
                'event_code' => 'BOOKING_RECEIVED',
                'event_date' => $booking['first_booking_at'],
                'confirmed_by' => $booking['first_confirmed_by'],
                'remarks' => 'Booking sudah terdaftar pada schedule ini.'
            );
        }

        // Master lama yang belum mempunyai audit tetap harus menampilkan bahwa
        // job console sudah ada. Jangan memakai waktu request saat ini.
        if (!isset($mapped_events['CONSOLE_CREATED'])) {
            $master_created_at = NULL;
            foreach (array('created_at', 'createdDtm', 'tanggal_job') as $field) {
                if (isset($master->$field) && !empty($master->$field)) {
                    $master_created_at = $master->$field;
                    break;
                }
            }
            $mapped_events['CONSOLE_CREATED'] = (object)array(
                'event_code' => 'CONSOLE_CREATED',
                'event_date' => $master_created_at,
                'confirmed_by' => 'SYSTEM',
                'remarks' => 'Master Job Console & Stuffing Plan sudah dibuat.'
            );
        }

        $framework = array(
            array(
                'code' => 'SCHEDULE_PLANNED',
                'title' => '1. Scheduling',
                'desc' => 'Sistem menyiapkan data schedule kapal dan rute tujuan.'
            ),
            array(
                'code' => 'BOOKING_RECEIVED',
                'title' => '2. Booking masuk & Penentuan Tipe',
                'desc' => 'Tim mengevaluasi klasifikasi GENCO/DG dan booking ke pelayaran.'
            ),
            array(
                'code' => 'CONSOLE_CREATED',
                'title' => '3. Pembuatan Job Console',
                'desc' => 'Job Console dibuat di dalam sistem dan anak job dikonsolidasi.'
            ),
            array(
                'code' => 'DO_RECEIVED',
                'title' => '4. DO Pelayaran Diterima & Upload',
                'desc' => 'Pihak pelayaran merilis Delivery Order (DO).'
            ),
            array(
                'code' => 'CONTAINER_ASSIGNED',
                'title' => '5. Pengambilan & Input Nomor Kontainer',
                'desc' => 'Truk mengambil kontainer kosong di depo dan nomor fisik diinput.'
            ),
            array(
                'code' => 'STUFFING_PLAN_ISSUED',
                'title' => '6. Pengiriman Stuffing Plan ke Gudang',
                'desc' => 'Dokumen Stuffing Plan dicetak dan diserahkan ke gudang CFS.'
            ),
            array(
                'code' => 'STUFFING_EXECUTED',
                'title' => '7. Pelaksanaan Stuffing di Gudang',
                'desc' => 'Cargo dimasukkan ke dalam kontainer sesuai dengan Stuffing Plan.'
            ),
            array(
                'code' => 'MANIFEST_UPLOADED',
                'title' => '8. Transfer Manifest & Recon BC',
                'desc' => 'Menunggu hasil rekonsiliasi (Recon) BC 1.1 dari Bea Cukai.'
            ),
            array(
                'code' => 'VESSEL_DEPARTED',
                'title' => '9. Vessel Departed (ATD)',
                'desc' => 'Kontainer dimuat ke kapal dan berangkat.'
            ),
            array(
                'code' => 'PRE_ALERT_DOWNLOAD',
                'title' => '10. Pembuatan / Download Pre-Alert untuk Agent',
                'desc' => 'Pre-Alert dibuat/diunduh untuk Agent. Konfirmasi penerimaan Agent merupakan proses terpisah.'
            )
        );

        $format_date = function($value) {
            $value = trim((string)$value);
            if ($value === '' || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
                return '';
            }
            $timestamp = strtotime($value);
            return $timestamp !== FALSE ? date('d M Y H:i', $timestamp) : '';
        };

        $timeline_result = array();
        $last_completed_index = -1;
        $last_completed_code = '';

        foreach ($framework as $index => $step) {
            $code = $step['code'];
            $event = $mapped_events[$code] ?? NULL;
            $is_completed = $event !== NULL;
            $remarks = $step['desc'];

            if ($code === 'BOOKING_RECEIVED') {
                $remarks = "GENCO: <b style='color:#FFCA33;'>".(int)$booking['GENCO']."</b> | ".
                    "DG: <b style='color:#ff4d4d;'>".(int)$booking['DG']."</b><br>".
                    "<i>Jika ada muatan DG, MSDS harus disubmit ke pelayaran.</i>";
            } elseif ($event && !empty($event->remarks)) {
                $remarks = $event->remarks;
            }

            if ($is_completed) {
                $last_completed_index = $index;
                $last_completed_code = $code;
            }

            $timeline_result[] = array(
                'step_code' => $code,
                'title' => $step['title'],
                'desc' => $remarks,
                'is_completed' => $is_completed,
                'is_current' => FALSE,
                'date' => $event ? $format_date($event->event_date ?? '') : '',
                'confirmed_by' => $event ? (string)($event->confirmed_by ?? '') : ''
            );
        }

        if ($last_completed_index < count($timeline_result) - 1) {
            $timeline_result[$last_completed_index + 1]['is_current'] = TRUE;
        }

        // Bila current_status belum tersedia pada data lama, gunakan milestone
        // paling akhir agar label Status Shipment tidak kosong.
        $current_status = trim((string)($master->current_status ?? ''));
        if ($current_status === '') {
            $current_status = $last_completed_code;
        }

        echo json_encode(array(
            'status' => TRUE,
            'has_data' => TRUE,
            'current_status' => $current_status,
            'shipment_status' => $current_status,
            'timeline' => $timeline_result,
            'meta' => array(
                'schedule_id' => $schedule_id,
                'schedule_source' => $schedule_source,
                'booking_total' => (int)$booking['TOTAL']
            )
        ));
    }
	
	function getTabelSchedule()
	{
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
		$sord = isset($_POST['sord'])?$_POST['sord']:'';
      
      if(!$sidx) $sidx = 1;
		$q = $this->afg_m_schedule->tampil_data_export_region();
				
		$hitung = count($q);
	 
		if( $hitung > 0 ) {
		    $total_hal = ceil($hitung/$batas);
		} else {
		    $total_hal = 0;
		}
	 
		if ($hal > $total_hal) $hal=$total_hal;
	 
		$start = $batas*$hal - $batas;
		$start = ($start<0)?0:$start;		
		
		$data = new stdClass();	 
		$data->page = $hal;
		$data->total = $total_hal;
		$data->records = $hitung;
		$i=0;
		foreach($q->result() as $row) {
		    $data->rows[$i]['id']=$row->export_id;
		    $data->rows[$i]['cell']=array(
		    									$row->sp_id,
												$this->afg_m_schedule->get_city_by_id($row->origin_city_id),
												$row->origin_vessel,
												$row->origin_voyage,
												$this->fungsi->jin_date_str($row->stf_cls),
												$this->fungsi->jin_date_str($row->origin_etd),
												$this->afg_m_schedule->get_city_by_id($row->connecting_city_id),
												$this->fungsi->jin_date_str($row->eta_destination),
												$this->afg_m_schedule->get_city_by_id($row->final_destination_city_id),
												$row->created,
												);
		    $i++;
		}
		echo json_encode($data);
	}
	
	function get_country()
	{
		$countries = $this->afg_m_countries->getListCountries()->result();
		$data['option_countries'][0]=' -country- ';
		foreach ($countries as $row)
		{	
			$data['option_countries'][$row->country_id]=$row->country_name;
		}
		$countries = form_dropdown('dropdownCountries',$data['option_countries'],'','id="dropdownCountries" style="width: 220px;"');
		echo json_encode(array('country'=>$countries));
	}
	
	function manage_table()
	{
		$data = array(
			'sp_id' 							=>	$this->input->post('sp_id'),
			'kode_ref' 						=>	$this->input->post('kode_ref'),
			'shipment_request_id' 		=>	$this->input->post('id_request'),
			'wg_ctn' 						=>	$this->input->post('wg_ctn'),
			'shipper' 						=> $this->input->post('shipper'),
			'no_pack_qty' 					=>	strtoupper($this->input->post('no_pack_qty')??''),
			'no_pack_unit' 				=>	strtoupper($this->input->post('no_pack_unit')??''),
			'desc_goods' 					=>	strtoupper($this->input->post('desc_goods')??''),
			'weight'							=>	$this->input->post('weight'),
			'ship_meas' 					=>	$this->input->post('ship_meas'),
			'wh_meas'						=>	$this->input->post('wh_meas'),
			'chgs_weight'					=>	$this->input->post('chgs_weight'),
			'cost_meas'						=>	$this->input->post('cost_meas'),
			'wh_agent'						=>	$this->input->post('wh_agent'),
			'bl_meas'						=>	$this->input->post('bl_meas'),
			'destination'					=>	strtoupper($this->input->post('destination')??''),
			'doc'								=>	strtoupper($this->input->post('doc')??''),
			'hb_l'							=>	$this->input->post('hb_l'),
			'agent_code'					=>	$this->input->post('agent_code'),
			'freight'						=>	$this->input->post('freight'),
			'freights_charges'			=>	strtoupper($this->input->post('freights_charges')??''),
			'c_o'								=>	strtoupper($this->input->post('c_o')??''),
			'marking_nos'								=>	$this->input->post('marking_nos'),
			'shipping_mark'				=>	strtoupper($this->input->post('shipping_mark')??''),
			'isN'								=>	$this->input->post('isN'),
			'isOptCost'						=>	$this->input->post('isOptCost'),
			'status_selling'				=>	$this->input->post('status_selling'),
			'isSpotRate'					=>	$this->input->post('isSpotRate'),
			'note'	                  =>	strtoupper($this->input->post('note')??''),
         'shipper_print'         	=>	strtoupper($this->input->post('shipper_print')??''),
			'consignee'						=>	strtoupper($this->input->post('consignee')??''),
			'notify'							=>	strtoupper($this->input->post('notify')??''),
			'express_release'				=>	$this->input->post('express_release'),
			'shipped_on_board'			=>	$this->input->post('shipped_on_board'),
			'net_weight_tidak_dicetak'	=>	$this->input->post('net_weight_tidak_dicetak'),
			'cetak_jml_doc_origi'		=>	$this->input->post('cetak_jml_doc_origi'),
			'num'								=> $this->input->post('num'),
			'isLock'							=> $this->input->post('isLock'),
			'code'							=> $this->input->post('code'),
			'sales'							=> $this->input->post('sales'),
			'shipp_co'						=> $this->input->post('shipp_co'),
			'region_id'						=> $this->input->post('region_id'),
		);
		
		$opr =	$this->input->post('oper');
		$id =	$this->input->post('id');
 		$job_no = $this->input->post('kode');
		
		if($opr=='add') 
		{ 
			$status = $this->afg_m_stuffing_plan->manage_tabel_add($data);
			echo json_encode(array('status'=>$status,'mode'=>'add'));
		}
		else if($opr=='edit')
 		{
			$status = $this->afg_m_stuffing_plan->manage_tabel_edit($id, $data);
			echo json_encode(array('status'=>$status,'mode'=>'edit'));
		}
		else if($opr=='del')
 		{
			$status = $this->afg_m_stuffing_plan->manage_tabel_delete($id,$job_no);
			echo json_encode(array('status'=>$status,'mode'=>'del'));
			//print_r($this->dbx->last_query());
		}
	}
	
	function manage_data_request()
	{
		$opr	=	$this->input->post('oper');
		$id	=	$this->input->post('id');
 		
		if($opr=='edit')
 		{
			$status = $this->afg_m_shipment_request->manage_data_request($_POST,$id);
			echo json_encode(array('status'=>$status,'mode'=>'edit'));
 		}
		else if($opr=='del')
 		{
			$status = $this->booking_model->cancel_booking($id);
			echo json_encode(array('status'=>$status,'mode'=>'delete'));
 		}	
	}
	
	function select_kode_ref()
	{
		$kode = $this->input->post('ref_no');
		$code = $this->afg_m_stuffing_plan->getListKodeRef($kode);
		$status = $code->num_rows();
		if($status > 0 ) {
			$data['option_koderef'][0]=' -  Kode Ref  - ';
			foreach ($code->result() as $row)
			{	
				$data['option_koderef'][$row->sp_id]=$row->kode_ref;
			}		
			$option = 'Select Kode Ref :'. nbs(). form_dropdown('dropdownKodeRef',$data['option_koderef'],'','id="dropdownKodeRef" style="float: right"');
			echo json_encode(array( "status"=>$status,"dropdown" => $option ));
		} else {
			echo json_encode(array( "status"=>$status,"dropdown" => array('','')));
		}
	}
	
	function move_job()
	{
		$sp = $this->input->post('sp');
		$spd = $this->input->post('spd');
		$status = $this->afg_m_stuffing_plan->move_job($sp, $spd);
		echo json_encode(array( "status" => $status));
	}
	
	function check()
	{
		$data = $this->afg_m_stuffing_plan->updateStuffingPlanDetail()->result();
		foreach ($data as $row){
			echo  $row->sp_detail_id.' | '. $row->kode_ref.' | '.$row->num . br();		
		}
	}

	function delete_schedule_expired()
	{
		$sp_id = $this->input->post('sp_id');
		$status = $this->fungsi->delete_schedule_expired();
		echo json_encode(array('status'=>$status,));
	}
	
	function get_last_id()
	{
		$data = $this->afg_m_stuffing_plan->getNextID();
		echo json_encode(array('status'=>$data));
	}
	
	function open_form_detail()
	{
		$mode = $this->input->post('mode');
		$id_detail = $this->input->post('id');
		$this->load->view('trucking/form-input-detail',array('mode'=>$mode,'id'=>$id_detail));
	}
	
	function update_attc()
	{
		$mode = $this->input->post('mode');
		$kode_ref = $this->input->post('kode_ref');
		$value = strtoupper($this->input->post('value'));
		$this->afg_m_stuffing_plan->update_attc($mode,$kode_ref,$value);
	}
	
	public function cetak_stuffing($code)
{
    // Kalau mau debug error
    // ini_set('display_errors', 1);
    // error_reporting(E_ALL);

    $ttl_1 = array();   // jumlah paket
    $ttl_2 = array();   // GW
    $ttl_3 = array();   // NW
    $ttl_4 = array();   // Shipper Meas
    $ttl_5 = array();   // W/House Meas
    $ttl_6 = array();   // B/L Meas

    $header    = $this->afg_m_stuffing_plan->cetak_stuffing_header($code);
    $detail    = $this->afg_m_stuffing_plan->cetak_stuffing_detail($code);
    $instruksi = $this->afg_m_stuffing_plan->get_instruksi($header->sp_id);

    if ( ! $header) {
        header('Content-Type: text/plain; charset=utf-8');
        echo "Header stuffing tidak ditemukan untuk code: ".$code;
        exit;
    }

    // Kalau FPDF_MC_Table belum di-autoload, include di sini:
    // require_once(APPPATH.'third_party/fpdf_mc_table.php');

    if ( ! class_exists('FPDF_MC_Table', false)) { $this->load->library('FPDF_MC_Table'); }
    require_once APPPATH . 'libraries/Sp_pdf_table.php';   // turunan FPDF_MC_Table: sel dengan font sendiri (kolom PEB/NPE)
    $pdf = new Sp_Pdf_Table();
    $pdf->AddPage('L', 'Legal');
    $pdf->AliasNbPages();
    $pdf->SetMargins(8,10);

    // ================= HEADER ATAS =================
    $pdf->SetXY(8, 5);
    $pdf->SetFont('helvetica','',8);

    $to_text = $instruksi ? $instruksi->to : '';
    $pdf->Cell(75, 5, 'TO   : '.$to_text, 0, 0, 'L', false);

    $pdf->SetFont('helvetica','B',14);
    $pdf->Cell(187, 5, 'STUFFING PLAN', 0, 0, 'C', false);

    $pdf->SetFont('helvetica','BI',8);
    $pdf->Cell(75, 5, 'PAGE : '.$pdf->PageNo().'/{nb}', 0, 0, 'R', false);
    $pdf->Ln();

    $pdf->SetFont('helvetica','B',14);
    $pdf->Cell(337, 5, 'PT. GATEWAY CONTAINER LINE', 0, 1, 'C', false);
    $pdf->Ln(5);

    // --- MENGATUR UKURAN KOLOM ---
    // Simpan posisi Y awal untuk QR code agar sejajar dengan Baris 1
    $startY = $pdf->GetY();

    // Setup proporsi lebar untuk 3 kolom teks (Kolom 1, 2, dan 3)
    $wL = 27; // Lebar Label
    $wS = 3;  // Lebar Titik Dua (:)
    $wV = 68; // Lebar Value
    // Total per kolom = 98 mm. (Total 3 kolom teks = 294 mm)

    // ============ BARIS 1 ============
    $pdf->SetFont('helvetica','',9);
    $pdf->Cell($wL, 5, 'OUR REF NO.', 0, 0, 'L', false);
    $pdf->Cell($wS, 5, ':', 0, 0, 'L', false);
    $pdf->SetFont('helvetica','B', 12); // Disesuaikan sedikit tebalnya
    $pdf->Cell($wV, 5, $header->kode_ref, 0, 0, 'L', false);

    $pdf->SetFont('helvetica','',9);
    $pdf->Cell($wL, 5, 'NAME OF VESSEL', 0, 0, 'L', false);
    $pdf->Cell($wS, 5, ':', 0, 0, 'L', false);
    $pdf->Cell($wV, 5, $header->vessel.' '.$header->voyage, 0, 0, 'L', false);

    $pdf->Cell($wL, 5, 'PORT OF LOADING', 0, 0, 'L', false);
    $pdf->Cell($wS, 5, ':', 0, 0, 'L', false);
    $pdf->Cell($wV, 5, $header->port_loading, 0, 1, 'L', false);

    // ============ BARIS 2 ============
    $pdf->Cell($wL, 5, 'WAREHOUSE.', 0, 0, 'L', false);
    $pdf->Cell($wS, 5, ':', 0, 0, 'L', false);
    $pdf->Cell($wV, 5, $header->warehouse, 0, 0, 'L', false);

    $pdf->Cell($wL, 5, 'CLOSING TIME', 0, 0, 'L', false);
    $pdf->Cell($wS, 5, ':', 0, 0, 'L', false);
    $pdf->Cell(20, 5, $this->fungsi->jin_date_str($header->closing_date_time), 0, 0, 'L', false);
    $pdf->Cell($wV - 20, 5, $header->closing_time, 0, 0, 'L', false);

    $pdf->Cell($wL, 5, 'TRANSHIPMENT', 0, 0, 'L', false);
    $pdf->Cell($wS, 5, ':', 0, 0, 'L', false);
    $pdf->Cell($wV, 5, $header->transhipment_port, 0, 1, 'L', false);

    // ============ BARIS 3 ============
    $pdf->Cell($wL, 5, 'STUFFING DATE.', 0, 0, 'L', false);
    $pdf->Cell($wS, 5, ':', 0, 0, 'L', false);
    $pdf->Cell($wV, 5, $this->fungsi->jin_date_str($header->stuffing_date), 0, 0, 'L', false);

    $pdf->Cell($wL, 5, 'ETD JKT', 0, 0, 'L', false);
    $pdf->Cell($wS, 5, ':', 0, 0, 'L', false);
    $pdf->Cell($wV, 5, $this->fungsi->jin_date_str($header->etd_jkt), 0, 0, 'L', false);

    $pdf->Cell($wL, 5, 'DESTINATION', 0, 0, 'L', false);
    $pdf->Cell($wS, 5, ':', 0, 0, 'L', false);
    $pdf->Cell($wV, 5, $header->destination, 0, 1, 'L', false);

    // ============ BARIS 4 ============
    $pdf->Cell($wL, 5, 'CARRIER / MBL No.', 0, 0, 'L', false);
    $pdf->Cell($wS, 5, ':', 0, 0, 'L', false);
    $pdf->Cell($wV, 5, $header->carrier." / ".$header->mother_bl, 0, 0, 'L', false);

    $pdf->Cell($wL, 5, 'TYPE OF CONT', 0, 0, 'L', false);
    $pdf->Cell($wS, 5, ':', 0, 0, 'L', false);
    $pdf->Cell($wV, 5, $header->type_cont_size, 0, 0, 'L', false);

    $pdf->Cell($wL, 5, 'CONT/SEAL NO', 0, 0, 'L', false);
    $pdf->Cell($wS, 5, ':', 0, 0, 'L', false);
    $pdf->Cell($wV, 5, $header->cont_seal_no, 0, 1, 'L', false);


    // ============ KOLOM 4 (QR CODE) ============
    // Posisikan di sumbu X = 308 (setelah 3 kolom teks habis) dan sejajar dengan Baris 1 ($startY).
    // (Pastikan setting "allow_url_fopen = On" pada php.ini server Anda untuk merender gambar dari URL eksternal)
    $qr_data = urlencode($header->kode_ref);
    $qr_url  = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&margin=0&data=' . $qr_data;
    $pdf->Image($qr_url, 308, $startY, 18, 18, 'PNG');
    
    $pdf->Ln(3); // Spasi tambahan ke garis pembatas tabel

    // ================= WARNA DESTINATION =================
    $destination       = $header->destination;
    $destinationColors = get_destination_colors();

    // default bg & text color
    $fill_r = 200; $fill_g = 200; $fill_b = 200;     // abu-abu muda
    $text_r = 0;   $text_g = 0;   $text_b = 0;       // hitam

    if (isset($destinationColors[$destination])) {
        $hex = $destinationColors[$destination];
        $fill_r = hexdec(substr($hex, 1, 2));
        $fill_g = hexdec(substr($hex, 3, 2));
        $fill_b = hexdec(substr($hex, 5, 2));

        list($text_r, $text_g, $text_b) = get_text_color_for_background($hex);
    }

    // ================= KOLOM TABEL (mm; jumlah 337 = lebar halaman Legal landscape setelah margin) =================
    // 3 kolom MEAS (Shipper / W/House / B/L) tetap ada seperti format lama, ditambah NO. PEB & NO. NPE.
    // Supaya semuanya muat, huruf isi tabel 9pt (dulu 10pt) dan kolom PEB/NPE mengecil otomatis sesuai panjang nomornya.
    // PEB/NPE dibaca langsung dari tabel PEB per booking_code (bukan salinan di tabel stuffing plan),
    // jadi PEB/NPE yang baru di-upload otomatis ikut tercetak dan booking dengan banyak PEB tampil semuanya.
    $fs  = 9;      // ukuran huruf isi tabel (pt)
    $col = array(
        'no' => 8, 'shipper' => 46, 'pack' => 21, 'desc' => 42, 'gw' => 19, 'nw' => 19,
        'ship' => 16, 'meas' => 16, 'bl' => 16,
        'peb' => 18, 'npe' => 34, 'dest' => 33, 'mark' => 49,
    );

    // Header tabel (dipakai juga untuk mengulang header di halaman lanjutan)
    $head = function ($p, $y) use ($col, $fill_r, $fill_g, $fill_b, $text_r, $text_g, $text_b) {
        $p->SetDrawColor(0, 0, 0);
        $p->SetFillColor($fill_r, $fill_g, $fill_b);
        $p->SetTextColor($text_r, $text_g, $text_b);
        $one = array(
            'no' => 'No', 'shipper' => 'NAME OF SHIPPER', 'gw' => 'GW', 'nw' => 'NW',
            'peb' => 'NO. PEB', 'npe' => 'NO. NPE', 'dest' => 'DESTINATION', 'mark' => 'SHIPPING MARKS',
        );
        $two = array(
            'pack' => array('NO OF', 'PACKAGES'), 'desc' => array('DISCRIPTION OF', 'GOODS'),
            'ship' => array('SHIPPER', 'MEAS'), 'meas' => array('W/HOUSE', 'MEAS'), 'bl' => array('B/L', 'MEAS'),
        );
        $x = 8;
        foreach ($col as $k => $w) {
            $p->SetFont('helvetica', 'B', in_array($k, array('ship', 'meas', 'bl'), true) ? 8 : 9);
            if (isset($two[$k])) {
                $p->SetXY($x, $y);       $p->MultiCell($w, 5, $two[$k][0], 'TL', 'C', true);
                $p->SetXY($x, $y + 5);   $p->MultiCell($w, 5, $two[$k][1], 'BL', 'C', true);
            } else {
                $p->SetXY($x, $y);       $p->MultiCell($w, 10, $one[$k], ($k === 'mark') ? 'TBLR' : 'TBL', 'C', true);
            }
            $x += $w;
        }
        $p->SetXY(8, $y + 10);
    };

    // Garis sebelum header tabel
    $pdf->SetDrawColor(0,0,0);
    $pdf->Line(8,47,345,47);
    $head($pdf, 48);
    $pdf->sp_new_page = function ($p) use ($head) { $head($p, 10); };   // header diulang bila tabel lanjut ke halaman berikutnya

    // ================= DETAIL =================
    $pdf->SetTextColor(70,70,70);      // teks normal
    $pdf->SetFillColor(255,255,255);   // bg putih
    $pdf->SetFont('helvetica','',10);

    // PEB / NPE per booking_code. Gagal membaca (mis. tabel PEB belum dimigrasi) TIDAK boleh menggagalkan cetak.
    $peb_map = array();
    try {
        $codes = array();
        foreach ($detail as $d) { if (!empty($d['booking_code'])) { $codes[] = $d['booking_code']; } }
        if ($codes) {
            $this->load->model('pricelist/peb_model');
            $peb_map = $this->peb_model->numbers_by_bookings($codes);
        }
    } catch (Throwable $e) {
        log_message('error', 'cetak_stuffing: PEB/NPE tidak terbaca: ' . $e->getMessage());
    }
    $peb_lines = array(); $npe_lines = array(); $all_peb = array(); $all_npe = array();
    foreach ($detail as $i => $row) {
        $bk = isset($row['booking_code']) ? $row['booking_code'] : '';
        list($peb_lines[$i], $npe_lines[$i]) = $this->_sp_peb_npe_lines(isset($peb_map[$bk]) ? $peb_map[$bk] : array());
        $all_peb = array_merge($all_peb, $peb_lines[$i]);
        $all_npe = array_merge($all_npe, $npe_lines[$i]);
    }
    // Ukuran font kolom PEB/NPE: satu ukuran untuk semua baris (maks. sama dengan huruf isi tabel) supaya seragam. Nomor yang
    // terlalu panjang (perlu < 7pt) tidak ikut menentukan ukuran umum; sel itu saja yang dikecilkan (min. 6pt, lalu dibungkus).
    $fs_pn = $fs;
    foreach (array(array($all_peb, $col['peb']), array($all_npe, $col['npe'])) as $g) {
        foreach ($g[0] as $t) {
            $f = $pdf->SpFit(array($t), $g[1], $fs, 6);
            if ($f >= 7 && $f < $fs_pn) { $fs_pn = $f; }
        }
    }
    $fs_peb = array(); $fs_npe = array();
    foreach ($detail as $i => $row) {
        $fs_peb[$i] = min($fs_pn, $pdf->SpFit($peb_lines[$i], $col['peb'], $fs_pn, 6));
        $fs_npe[$i] = min($fs_pn, $pdf->SpFit($npe_lines[$i], $col['npe'], $fs_pn, 6));
    }
    $pdf->SetFont('helvetica','',10);

    foreach ($detail as $i => $row) 
    {
        // weight & netto string → float aman
        $netto     = floatval(str_replace(array(',', ' '), '', $row['netto']));
        $weight    = floatval(str_replace(array(',', ' '), '', $row['weight']));
        $ship_meas = floatval(str_replace(array(',', ' '), '', $row['ship_meas']));
        $wh_meas   = floatval(str_replace(array(',', ' '), '', $row['wh_meas']));
        $bl_meas   = floatval(str_replace(array(',', ' '), '', $row['bl_meas']));

        $pdf->SpRow(array(
            array('t' => $row['num'],                                   'w' => $col['no'],      'a' => 'C', 'fs' => $fs),
            array('t' => $row['shipper_print'],                         'w' => $col['shipper'], 'a' => 'L', 'fs' => $fs),
            array('t' => $row['no_pack_qty'].' '.$row['no_pack_unit'],  'w' => $col['pack'],    'a' => 'C', 'fs' => $fs),
            array('t' => $row['desc_print'],                            'w' => $col['desc'],    'a' => 'L', 'fs' => $fs),
            array('t' => $this->fungsi->pecah3($row['weight'], ","),    'w' => $col['gw'],      'a' => 'C', 'fs' => $fs),   // tetap pakai format lama
            array('t' => $this->fungsi->pecah3($netto, ","),            'w' => $col['nw'],      'a' => 'C', 'fs' => $fs),
            array('t' => $this->fungsi->pecah4($ship_meas, ","),        'w' => $col['ship'],    'a' => 'C', 'fs' => $fs),
            array('t' => $this->fungsi->pecah4($wh_meas, ","),          'w' => $col['meas'],    'a' => 'C', 'fs' => $fs),
            array('t' => $this->fungsi->pecah4($bl_meas, ","),          'w' => $col['bl'],      'a' => 'C', 'fs' => $fs),
            array('t' => $peb_lines[$i], 'w' => $col['peb'], 'a' => 'C', 'fs' => $fs_peb[$i], 'lh' => 4.5),
            array('t' => $npe_lines[$i], 'w' => $col['npe'], 'a' => 'C', 'fs' => $fs_npe[$i], 'lh' => 4.5),
            array('t' => $row['destination'],                           'w' => $col['dest'],    'a' => 'L', 'fs' => $fs),
            array('t' => $row['shipping_mark'],                         'w' => $col['mark'],    'a' => 'L', 'fs' => $fs),
        ));

        $ttl_1[] = $row['no_pack_qty'];
        $ttl_2[] = $weight;
        $ttl_3[] = $netto;
        $ttl_4[] = $ship_meas;
        $ttl_5[] = $wh_meas;
        $ttl_6[] = $bl_meas;
    }

    // ================= TOTAL =================
    $pdf->Ln(1);
    $pdf->SpRow(array(
        array('t' => '',                                            'w' => $col['no'],      'a' => 'C', 'st' => 'B', 'fs' => $fs),
        array('t' => 'TOTAL',                                       'w' => $col['shipper'], 'a' => 'C', 'st' => 'B', 'fs' => $fs),
        array('t' => array_sum($ttl_1),                             'w' => $col['pack'],    'a' => 'C', 'st' => 'B', 'fs' => $fs),
        array('t' => '',                                            'w' => $col['desc'],    'a' => 'L', 'st' => 'B', 'fs' => $fs),
        array('t' => $this->fungsi->pecah3(array_sum($ttl_2), ","), 'w' => $col['gw'],      'a' => 'C', 'st' => 'B', 'fs' => $fs),
        array('t' => $this->fungsi->pecah3(array_sum($ttl_3), ","), 'w' => $col['nw'],      'a' => 'C', 'st' => 'B', 'fs' => $fs),
        array('t' => $this->fungsi->pecah4(array_sum($ttl_4), ","), 'w' => $col['ship'],    'a' => 'C', 'st' => 'B', 'fs' => $fs),
        array('t' => $this->fungsi->pecah4(array_sum($ttl_5), ","), 'w' => $col['meas'],    'a' => 'C', 'st' => 'B', 'fs' => $fs),
        array('t' => $this->fungsi->pecah4(array_sum($ttl_6), ","), 'w' => $col['bl'],      'a' => 'C', 'st' => 'B', 'fs' => $fs),
        array('t' => '',                                            'w' => $col['peb'],     'a' => 'C', 'st' => 'B', 'fs' => $fs),
        array('t' => '',                                            'w' => $col['npe'],     'a' => 'C', 'st' => 'B', 'fs' => $fs),
        array('t' => '',                                            'w' => $col['dest'],    'a' => 'L', 'st' => 'B', 'fs' => $fs),
        array('t' => '',                                            'w' => $col['mark'],    'a' => 'L', 'st' => 'B', 'fs' => $fs),
    ));

    // ================= FOOTER & INSTRUKSI =================
    $pdf->SetFont('helvetica','B',10);
    $pdf->Ln(2);
    $pdf->Cell(337, 0, '', 'T', 1, 'C', false);
    $pdf->Ln(2);

    // 1. Matikan pengulangan header tabel jika ternyata footer harus pindah halaman
    $pdf->sp_new_page = null; 

    // 2. Cek ketersediaan ruang (hindari auto page-break FPDF yang berantakan).
    // Jika posisi Y sudah lebih dari 165 (terlalu dekat dengan batas bawah),
    // kita paksa pindah halaman agar seluruh footer utuh berada di 1 halaman yang sama.
    if ($pdf->GetY() > 165) {
        $pdf->AddPage('L', 'Legal');
    }

    $startY = $pdf->GetY(); // Ambil koordinat Y saat ini yang pasti sudah aman

    // --- 3. BAGIAN KANAN (PLANNER & KODE REF) ---
    $pdf->SetXY(190, $startY);
    $planner_name = $this->fungsi->get_user_by_id($header->prepared);
    $pdf->Cell(155, 5, 'PLANNER : ' . strtoupper($planner_name), 0, 1, 'R', false);

    $pdf->SetXY(190, $startY + 15);
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(155, 10, $header->kode_ref, 0, 1, 'R', false);

    // --- 4. BAGIAN KIRI (INSTRUKSI STUFFING & STUFFING DATE) ---
    $pdf->SetXY(8, $startY);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(170, 5, 'INSTRUKSI STUFFING :', 0, 1, 'L', false);

    $pdf->SetTextColor(70, 70, 70);
    $pdf->SetFont('helvetica', '', 9);
    $instruksi_text = $instruksi ? $instruksi->instruksi_stuffing : '-';
    $pdf->MultiCell(170, 4.5, $instruksi_text, 0, 'L', false);

    $pdf->Ln(2);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(170, 5, 'STUFFING DATE : ' . $this->fungsi->jin_date_str($header->stuffing_date), 0, 1, 'L', false);

    // Pindahkan cursor Y ke batas paling bawah (melewati kotak instruksi / kode ref) 
    // agar tidak error jika ada kode tambahan setelah ini
    $endY = max($pdf->GetY(), $startY + 30);
    $pdf->SetY($endY);

    // ========================================================
    // TRIGGER TIMELINE AUDIT: KIRIM STUFFING PLAN KE CFS
    // ========================================================
        $this->load->model('Timeline_model');
        $this->Timeline_model->record_timeline_event(
            'MASTER',
            $code,                   // $code adalah parameter kode_ref yang diterima fungsi ini
            'STUFFING_PLAN_ISSUED',  // Event Code: Stuffing Plan Diterbitkan
            date('Y-m-d H:i:s'),
            NULL,
            'Dokumen Stuffing Plan dicetak dan diserahkan ke gudang CFS'
        );
        // ========================================================

    $pdf->Output('STUFFING PLAN '.$header->kode_ref.'.pdf','I');
    exit;
}


	function instruksi_stuffing() 
	{
		$data = $this->afg_m_stuffing_plan->instruksi_stuffing($_POST['sp_id']);
		echo json_encode($data);
	}

	function close_form_stuffing() 
	{
		$data = $this->afg_m_stuffing_plan->close_form_stuffing($_POST);
		echo json_encode($data);
	}

public function print_rekap()
{
    ob_start();

    // --- Ambil semua parameter GET ---
    $data = $this->input->get(); // sama dengan $_GET, tapi lewat CI
    $job_no       = $data['job_no'] ?? '';
    $stuffing_date= $data['stuffing_date'] ?? '';
    $agent        = isset($data['agent']) ? str_replace('%20', ' ', $data['agent']) : '';
    $vessel       = isset($data['vessel']) ? str_replace('%20', ' ', $data['vessel']) : '';
    $voyage       = isset($data['voyage']) ? str_replace('%20', ' ', $data['voyage']) : '';
    $cont         = isset($data['cont']) ? str_replace('%20', ' ', $data['cont']) : '';
    $cont_no      = isset($data['cont_no']) ? str_replace('%20', ' ', $data['cont_no']) : '';
    $kurs         = isset($data['kurs']) ? (float)str_replace(',', '', $data['kurs']) : 0.0;
    $destination  = $data['destination'] ?? '';

    // --- Ambil konteks agregat sekali jalan ---
    $ctx       = $this->afg_m_stuffing_plan->agg_rekap_context($job_no);
    $rows      = $ctx['rows'];      // baris detail (hasil get_print_rekap)
    $income    = $ctx['income'];    // per kode_ref: ['iusd','irp','giusd','girp']
    $cost      = $ctx['cost'];      // per kode_ref: ['usd','rp']
    $dn_map    = $ctx['dn'];        // per spd: float USD
    $inv_agent = $ctx['inv_agent']; // per spd: float USD (collect + DN)
    $coloader  = $ctx['coloader'];  // per kode_ref: ['usd','rp']

    // --- Siapkan PDF ---
    $pdf = new FPDF_MC_Table();
    $pdf->AddPage('L','Letter');

    $pdf->SetFont('helvetica','B',8);
    $pdf->SetMargins(5,0);
    $pdf->SetXY(5, 5);
    $pdf->write(0, 'PT. GATEWAY CONTAINER LINE');
    $pdf->SetFont('helvetica','',7);
    $pdf->SetXY(5, 9);
    $pdf->write(0, 'REKAP JOB');
    $pdf->SetXY(5, 13);
    $pdf->write(0, 'STUFFING EXPORT/ETA IMPORT');
    $pdf->SetXY(45, 13);
    $pdf->write(0, ':');
    $pdf->SetXY(50, 13);
    $pdf->write(0, $stuffing_date);
    $pdf->SetXY(5, 17);
    $pdf->write(0, 'JOB NUMBER');
    $pdf->SetXY(45, 17);
    $pdf->write(0, ':');
    $pdf->SetXY(50, 17);
    $pdf->write(0, $job_no);
    $pdf->SetXY(5, 21);
    $pdf->write(0, 'AGENT PENAGIHAN');
    $pdf->SetXY(45, 21);
    $pdf->write(0, ':');
    $pdf->SetXY(50, 21);
    $pdf->write(0, $agent);
    $pdf->SetXY(5, 25);
    $pdf->write(0, 'VESSEL');
    $pdf->SetXY(45, 25);
    $pdf->write(0, ':');
    $pdf->SetXY(50, 25);
    $pdf->write(0, trim($vessel.' '.$voyage));
    $pdf->SetXY(5, 29);
    $pdf->write(0, 'CONTAINER NUMBER');
    $pdf->SetXY(45, 29);
    $pdf->write(0, ':');
    $pdf->SetXY(50, 29);
    $pdf->write(0, $cont);
    $pdf->SetXY(50, 33);
    $pdf->write(0, $cont_no);

    $pdf->AliasNbPages();
    $pdf->setDataHeader(['report' => '11']);

    // Header table
    $pdf->SetFont('helvetica','B',7);
    $pdf->SetFillColor(230,230,230);
    $pdf->SetTextColor(100,100,100);
    $pdf->SetXY(5, 40);
    $pdf->MultiCell(7,8,'No',1,'C',true);
    $pdf->SetXY(12, 40);
    $pdf->MultiCell(23,8,'B/L NO',1,'C',true);
    $pdf->SetXY(35, 40);
    $pdf->MultiCell(45,8,'SHIPPER',1,'C',true);
    $pdf->SetXY(80, 40);
    $pdf->MultiCell(14,8,'MEAS',1,'C',true);
    $pdf->SetXY(94, 40);
    $pdf->MultiCell(14,8,'KURS',1,'C',true);
    $pdf->SetXY(108, 40);
    $pdf->MultiCell(16,4,'INVOICE TO AGENT',1,'C',true);
    $pdf->SetXY(124, 40);
    $pdf->MultiCell(16,4,'CR NOTE TO AGENT',1,'C',true);

    $pdf->SetXY(140, 40);
    $pdf->MultiCell(38,4,'INCOME',1,'C',true);
    $pdf->SetXY(140, 44);
    $pdf->MultiCell(16,4,'USD',1,'C',true);
    $pdf->SetXY(156, 44);
    $pdf->MultiCell(22,4,'IDR',1,'C',true);

    $pdf->SetXY(178, 40);
    $pdf->MultiCell(38,4,'COST',1,'C',true);
    $pdf->SetXY(178, 44);
    $pdf->MultiCell(16,4,'USD',1,'C',true);
    $pdf->SetXY(194, 44);
    $pdf->MultiCell(22,4,'IDR',1,'C',true);

    $pdf->SetXY(216, 40);
    $pdf->MultiCell(38,4,'PROFIT / LOST',1,'C',true);
    $pdf->SetXY(216, 44);
    $pdf->MultiCell(16,4,'USD',1,'C',true);
    $pdf->SetXY(232, 44);
    $pdf->MultiCell(22,4,'IDR',1,'C',true);
    $pdf->SetXY(254, 40);
    $pdf->MultiCell(20,8,'TOTAL',1,'C',true);
    $pdf->Ln(1);

    $pdf->SetFont('helvetica','',7);
    $pdf->SetTextColor(10,10,10);
    $pdf->SetWidths([7,23,45,14,14,16,16,16,22,16,22,16,22,20]);
    $pdf->SetAligns(['C','L','L','R','R','R','R','R','R','R','R','R','R','R']);

    // --- Totaller global ---
    $gn_bl_meas=[]; $gn_ttl_i_usd=[]; $gn_ttl_i_rp=[];
    $gn_ttl_c_usd=[]; $gn_ttl_c_rp=[];
    $gn_ttl_p_usd=[]; $gn_ttl_p_rp=[];
    $gn_ttl_dbt=[];  /* $gn_ttl_crd=[]; (kolom CRNOTE kita tidak hitung di bawah, tetap kosong/0) */
    $gn_total=[];

    // --- Simpan batch rekap (opsional: tetap pakai existing method per-row) ---
    // Kita bungkus transaksi agar 1 commit.
    $this->db->trans_start();

    $i = 1;
    foreach ($rows as $row) {
        $kode_ref = $row->kode_ref;
        $spd      = $row->spd;
        $hb_l     = $row->hb_l;
        $shipper  = substr($row->shipper_print, 0, 25);
        $meas     = $row->bl_meas;
        $kurs_str = $this->fungsi->pecah($kurs,',');
        $sales = $row->sales;

        // fallback destination jika kosong
        $dest = $row->destination ?: $destination;

        // Ambil paket angka dari aggregator (aman jika tidak ada -> 0)
        $inc = isset($income[$kode_ref]) ? $income[$kode_ref] : ['iusd'=>0,'irp'=>0,'giusd'=>0,'girp'=>0];
        $cst = isset($cost[$kode_ref])   ? $cost[$kode_ref]   : ['usd'=>0,'rp'=>0];
        $dnU = isset($dn_map[$spd])      ? (float)$dn_map[$spd] : 0.0;
        $invU= isset($inv_agent[$spd])   ? (float)$inv_agent[$spd] : 0.0;
        $col = isset($coloader[$kode_ref]) ? $coloader[$kode_ref] : ['usd'=>0.0,'rp'=>0.0];

        // Income rekap (sesuai logika lama): selling + global + DN + coloader
        $income_usd      = (float)$inc['iusd'] + (float)$inc['giusd'];
        $income_rp       = (float)$inc['irp']  + (float)$inc['girp'];
        $income_rekap    = (float)$inc['iusd'] + (float)$inc['giusd'] + (float)$dnU + (float)$col['usd'];
        $income_rekap_rp = (float)$inc['irp']  + (float)$inc['girp']  + (float)$col['rp'];

        // Cost rekap (lokal + global sudah di-join jadi $cst['usd'], $cst['rp'])
        $cost_usd = (float)$cst['usd'];
        $cost_rp  = (float)$cst['rp'];

        // Profit (sesuai rumus lama: ttl_income(USD) + globalUSD - ttl_cost(USD) + DN + coloaderUSD | IDR: incomeRP - costRP + coloaderRP)
        $profit_usd = $income_usd - $cost_usd + (float)$dnU + (float)$col['usd']; // (inc iusd+giusd) - cost_usd + dn + col_usd
        $profit_rp  = $income_rp - $cost_rp  + (float)$col['rp'];

        // Formatting kosong bila zero (ikuti perilaku lama)
        $profit_usd_out = ($profit_usd == 0) ? '' : $profit_usd;
        $profit_rp_out  = ($profit_rp  == 0) ? '' : $profit_rp;

        // TOTAL (USD->IDR dikali kurs + IDR)
        $total = (float)$profit_usd * $kurs + (float)$profit_rp;

        // Tulis baris tabel
        $pdf->Row([
            $row->num,
            $hb_l,
            $shipper,
            $this->fungsi->pecah4($meas, ','),
            $kurs_str,
            $this->fungsi->pecah($income_rekap,','),    // INVOICE TO AGENT (USD)
            /* CR NOTE TO AGENT (di header ada, isi pakai cost_usd sebagai di code lama / atau kosong?) */
            $this->fungsi->pecah($cost_usd,','),

            $this->fungsi->pecah($income_rekap,','),     // INCOME USD
            $this->fungsi->pecah($income_rekap_rp,','),  // INCOME RP
            $this->fungsi->pecah($cost_usd,','),         // COST USD
            $this->fungsi->pecah($cost_rp,','),          // COST RP
            $this->fungsi->pecah($profit_usd_out,','),   // PROFIT USD
            $this->fungsi->pecah($profit_rp_out,','),    // PROFIT RP
            $this->fungsi->pecah($total,','),            // TOTAL
        ]);

        // Simpan ke tabel rekap_sales (pakai method yang sudah ada)
        $this->m_rekap_sales->save_profit_rekap([
            "kode_ref"   => $kode_ref,
            "profit"     => $total,
            "income"     => $income_rekap_rp + (float)$income_rekap * $kurs,
            "cost"       => $cost_rp + (float)$cost_usd * $kurs,
            "profit_usd" => ($profit_usd_out === '' ? 0 : $profit_usd),
            "profit_rp"  => ($profit_rp_out  === '' ? 0 : $profit_rp),
            "income_usd" => $income_rekap,
            "income_rp"  => $income_rekap_rp,
            "cost_usd"   => $cost_usd,
            "cost_rp"    => $cost_rp,
            "inv_agent"  => $invU,
            "cost_coloader"=>"", // belum dipakai
			"sales"=>$this->fungsi->get_user_by_id($sales),
            "region_id"  => $this->session->userdata('region_id'),
            "sales_id"  => $sales,
        ]);

        // Akumulator total
        $gn_bl_meas[]    = (float)$meas;
        $gn_ttl_i_usd[]  = (float)$income_rekap;
        $gn_ttl_i_rp[]   = (float)$income_rekap_rp;
        $gn_ttl_c_usd[]  = (float)$cost_usd;
        $gn_ttl_c_rp[]   = (float)$cost_rp;
        $gn_ttl_p_usd[]  = ($profit_usd_out === '' ? 0 : (float)$profit_usd);
        $gn_ttl_p_rp[]   = ($profit_rp_out  === '' ? 0 : (float)$profit_rp);
        $gn_ttl_dbt[]    = (float)$income_rekap; // mengikuti bidang "INVOICE TO AGENT" sebagai DN/Collect ke agent
        $gn_total[]      = (float)$total;

        $i++;
    }

    // --- TOTAL REKAP (susun seperti versi lama) ---
    $ttl_bl_meas      = array_sum($gn_bl_meas);
    $ttl_income_usd   = array_sum($gn_ttl_i_usd);
    $ttl_income_rp    = array_sum($gn_ttl_i_rp);
    $ttl_cost_usd     = array_sum($gn_ttl_c_usd);
    $ttl_cost_rp      = array_sum($gn_ttl_c_rp);
    $ttl_profit_usd   = array_sum($gn_ttl_p_usd);
    $ttl_profit_rp    = array_sum($gn_ttl_p_rp);
    $ttl_dn_agent     = array_sum($gn_ttl_dbt);
    $ttl_total        = array_sum($gn_total);

    // Baris TOTAL
    $pdf->Ln(1);
    $pdf->SetFont('helvetica','B',7);
    $pdf->Cell(75,5,'TOTAL REKAP',1,'','C',true);
    $pdf->Cell(14,5,$this->fungsi->pecah4($ttl_bl_meas,","),1,'','R',true);
    $pdf->Cell(14,5,'',1,'','R',true); // kolom "KURS" total (kosong seperti sebelumnya)
    $pdf->Cell(16,5,$this->fungsi->pecah($ttl_dn_agent,","),1,'','R',true);  // total DN/invoice to agent
    $pdf->Cell(16,5,$this->fungsi->pecah($ttl_cost_usd,","),1,'','R',true);  // di kolom "CR NOTE TO AGENT" (seperti kode lama menaruh cost_usd)
    $pdf->Cell(16,5,$this->fungsi->pecah($ttl_income_usd,","),1,'','R',true);
    $pdf->Cell(22,5,$this->fungsi->pecah($ttl_income_rp,","),1,'','R',true);
    $pdf->Cell(16,5,$this->fungsi->pecah($ttl_cost_usd,","),1,'','R',true);
    $pdf->Cell(22,5,$this->fungsi->pecah($ttl_cost_rp,","),1,'','R',true);
    $pdf->Cell(16,5,$this->fungsi->pecah($ttl_profit_usd,","),1,'','R',true);
    $pdf->Cell(22,5,$this->fungsi->pecah($ttl_profit_rp,","),1,'','R',true);
    $pdf->Cell(20,5,$this->fungsi->pecah($ttl_total,","),1,'','R',true);

    // Simpan total ke m_rekap_sales (save_acct) – ikuti struktur lama
    $this->m_rekap_sales->save_acct([
        "job_no"          => $job_no,
        "destination"     => $destination,
        "total_meas"      => $ttl_bl_meas,
        "kurs"            => $kurs,
        "total_dn"        => $ttl_dn_agent,      // invoice to agent
        "total_cn"        => $ttl_cost_usd,      // mapping ke kolom lama (CR NOTE / cost_usd)
        "ttl_income_usd"  => $ttl_income_usd,
        "ttl_income_rp"   => $ttl_income_rp,
        "total_income"    => $ttl_income_rp + ($ttl_income_usd * $kurs),
        "ttl_cost_usd"    => $ttl_cost_usd,
        "ttl_cost_rp"     => $ttl_cost_rp,
        "total_cost"      => $ttl_cost_rp + ($ttl_cost_usd * $kurs),
        "ttl_profit_usd"  => $ttl_profit_usd,
        "ttl_profit_rp"   => $ttl_profit_rp,
        "total_profit"    => $ttl_total,
        "region_id"       => $this->session->userdata('region_id'),
    ]);

    $this->db->trans_complete();

    // Keluarkan PDF
    $pdf->Output("PRINT-REKAP-".$job_no,'I');
}

	
	function get_total_income()
	{
		$value = $this->afg_m_stuffing_plan->get_total_income('GCL-30014030001C');
		echo json_encode($value);
	}
	
	function get_total_cost()
	{
		$value = $this->afg_m_stuffing_plan->get_total_cost('GCL-30014030001A');
		//print_r($value);
	}
	
	function list_colum()
	{
		$data = new stdClass();
		$data = $this->dbx->list_fields('tbl_detail');
   	foreach($data as $key => $row)
   	{
   		$data->$key = $key;
   	}
   	//print_r($data);
	}
	
	function objectToArray($d) 
	{
		if (is_object($d)) 
		{
			// Gets the properties of the given object
			// with get_object_vars function
			$d = get_object_vars($d);
		}
	
		if (is_array($d)) {
			/*
			* Return array converted to object
			* Using __FUNCTION__ (Magic constant)
			* for recursive call
			*/
			return array_map(__FUNCTION__, $d);
		}
		else 
		{
			// Return array
			return $d;
		}
	}
	
	function arrayToObject($d) {
		if (is_array($d)) {
			/*
			* Return array converted to object
			* Using __FUNCTION__ (Magic constant)
			* for recursive call
			*/
			return (object) array_map(__FUNCTION__, $d);
		}
		else 
		{
			// Return object
			return $d;
		}
	}
	
	function pindah_netto()
	{
		$q = $this->dbx->get('trnstuffingplandetail')->result();
		foreach($q as $row)
		{
			$this->dbx->update('trnstuffingplandetail',array('netto'=>$row->ship_meas),array('sp_detail_id'=>$row->sp_detail_id));
		}
		echo "Row Affected : ".$this->dbx->affected_rows();
	}
	function cetak_bc_depan($kode,$id) 
	{
		$this->load->library('FPDF_MC_Table');
		$pdf=new FPDF_MC_Table();		
		$pdf->AddPage('P','A4');
	
		$pdf->Image(APPPATH.'third_party/img/0001.jpg',0,0,+200);
		$pdf->SetFont('helvetica','',8);
		$data =  $this->booking_model->get_data_booking_confirmation($id);
		////print_r($data);
		//die();
		$pdf->SetXY(55,43.2);
		$pdf->write(0,$data[0]['type']);
		$pdf->SetXY(55,47.5);
		$pdf->write(0,$data[0]['kode_ref']);
		$pdf->SetXY(55,52);
		$pdf->write(0,date("d/m/Y"));
		$pdf->SetXY(55,56.5);
		$pdf->write(0,$this->fungsi->jin_date_str($data[0]['closing']));
		$pdf->SetXY(55,65);
		$pdf->SetFont('helvetica','B',10);
		$pdf->write(0,$data[0]['booking_no']);
		$pdf->SetFont('helvetica','',8);
		$shipper = $data[0]['note'];
		$pdf->SetXY(15,76);
		$pdf->Multicell(115,4,$data[0]['note'],0,'L',FALSE);
		$pdf->SetXY(105,76);
		$pdf->Multicell(115,4,$data[0]['consignee'],0,'L',FALSE);
		$pdf->SetXY(15,106);
		$pdf->Multicell(115,4,$data[0]['notify'],0,'L',FALSE);
		$pdf->SetXY(105,108);
		$pdf->write(0,$data[0]['vessel']);
		$pdf->SetXY(152,108);
		$pdf->write(0,$data[0]['voyage']);
		$pdf->SetXY(105,116);
		$pdf->write(0,$data[0]['desc_print']);
		$pdf->SetXY(105,124);
		$pdf->write(0,$data[0]['shipping_mark']);
		$pdf->SetXY(15,132);
		if($data[0]['express_release']=='TRUE')
		{$bl= 'EXPRESS RELEASE';}else{$bl='3 ORIGINAL';}
		$pdf->write(0,$bl);
		$pdf->SetXY(75,132);
		$pdf->write(0,$data[0]['weight']);
		$pdf->SetXY(105,132);
		$pdf->write(0,$data[0]['volume']);
		$pdf->SetXY(135,132);
		$pdf->write(0,$data[0]['chargeable']);	
		$pdf->SetXY(164,132);
		$pdf->write(0,$data[0]['package']);
		$pdf->SetXY(15,140);
		$pdf->write(0,$data[0]['pol']);
		$pdf->SetXY(105,140);
		$pdf->write(0,$data[0]['destination']);
		$pdf->SetXY(15,156);
		$pdf->write(0,$data[0]['warehouse'].'       '.$data[0]['warehouse_address']);
		$pdf->SetXY(15,170);
		$pdf->write(0,"SEA FREIGHT");
		$pdf->SetXY(45,170);
		$pdf->write(0,$data[0]['feeder']);
		$pdf->Output('BOOKING CONFIRMATION '. date('d-M-Y'),'I');
	}
function proses_post()
	{
		$mbl = $_POST['mbl'];
		$container = $_POST['container'];
		$job_no = $_POST['job_no'];
		$cont = explode("/",$container);
        $cont_no = preg_replace('/\s+/', '', $cont[0]);
		$scac = $_POST['scac'];
		$arr = array("cntr_no" => $cont_no, "bl_no"  => $mbl, "carrier_no" => $scac);
		$kirim = json_encode($arr);
		$client = new \GuzzleHttp\Client();

$response = $client->request('POST', 'https://api.portcast.io/api/v2/eta/bill-of-lading-bookmarks', [
  'body' => $kirim,
  'headers' => [
    'Accept' => 'application/json',
    'Content-Type' => 'application/json',
    'x-api-key' => 'PC25B0ADAAA1412B4615BAC49345E576F7',
  ],
]);

$data['hasil'] = json_decode($response->getBody());
$data['job_no'] = $job_no;
foreach ($data['hasil'] as $i => $r) {
$this->afg_m_stuffing_plan->save_id_bl($r->id,$data['job_no']);
}
	}
	
	
	/**
	 * Endpoint manual tetap tersedia, tetapi tidak lagi menerima MBL/SCAC dari
	 * browser. Semua identitas tracking dibaca ulang dari job berdasarkan sp_id.
	 */
	public function proses_gocomet()
	{
		$sp_id = (int)$this->input->post('sp_id');
		if ($sp_id <= 0) {
			return $this->output
				->set_status_header(422)
				->set_content_type('application/json')
				->set_output(json_encode(array(
					'status' => 'error',
					'reason' => 'INVALID_SP_ID'
				)));
		}

		$result = $this->sync_gocomet_tracking($sp_id, true);
		$http_status = ($result['status'] === 'error') ? 500 : 200;

		return $this->output
			->set_status_header($http_status)
			->set_content_type('application/json')
			->set_output(json_encode($result));
	}

	/** Validasi murah sebelum quota GoComet digunakan. */
	private function validate_gocomet_mbl($mbl, $scac)
	{
		$mbl  = strtoupper(trim((string)$mbl));
		$scac = strtoupper(trim((string)$scac));

		if ($mbl === '') {
			return array('valid' => false, 'message' => 'Mother BL kosong');
		}

		if (in_array($mbl, array('-', 'NA', 'N/A', 'TBA', 'TBC', 'TEST'), true)) {
			return array('valid' => false, 'message' => 'Mother BL masih berupa placeholder');
		}

		if (strlen($mbl) < 6 || strlen($mbl) > 30) {
			return array('valid' => false, 'message' => 'Panjang Mother BL tidak valid');
		}

		if (!preg_match('/^[A-Z0-9\-\/]+$/', $mbl)) {
			return array('valid' => false, 'message' => 'Mother BL mengandung karakter tidak valid');
		}

		if ($scac === '' || $scac === 'AUTO') {
			return array('valid' => false, 'message' => 'SCAC belum valid');
		}

		// Existing business rules dari form lama, sekarang dipaksakan di server.
		$rules = array(
			'REGU' => 'JKS',
			'SIKU' => 'SSL',
			'EGLV' => '08',
			'ESPU' => 'ID',
		);

		if (isset($rules[$scac]) && strpos($mbl, $rules[$scac]) === false) {
			return array(
				'valid' => false,
				'message' => 'Mother BL tidak sesuai dengan SCAC '.$scac
			);
		}

		return array('valid' => true, 'message' => '');
	}

	/** Carrier tertentu memang tidak menggunakan GoComet. */
	private function is_gocomet_carrier_excluded($carrier)
	{
		$carrier = strtoupper(trim((string)$carrier));
		$excluded = array(
			'LPIJKT', 'SUNGGONG', 'CTP', 'GANINDO', 'KERRY',
			'LPI SRG', 'TRESNA', 'MONTER', 'COLLYER',
			'SETIA SAMUDRA', 'BLESSINDO', 'JAMESON', 'SLL'
		);

		foreach ($excluded as $name) {
			if ($carrier !== '' && strpos($carrier, $name) !== false) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Sinkronisasi dilakukan sesudah save database.
	 * - belum ada ID: ADD sekali setelah MBL lolos validasi;
	 * - sudah ada ID + identitas berubah: UPDATE ID yang sama;
	 * - sudah ada ID + tidak berubah: tidak ada request API.
	 */
	private function sync_gocomet_tracking($sp_id, $tracking_identity_changed = false)
	{
		// Cegah dua request Save/double-click membuat ADD tracking bersamaan.
		$lock_name = 'gocomet_sync_'.md5(
			(string)$this->session->userdata('region_id').'|'.(int)$sp_id
		);
		$lock_row = $this->dbx
			->query('SELECT GET_LOCK(?, 5) AS acquired', array($lock_name))
			->row();

		if (!$lock_row || (int)$lock_row->acquired !== 1) {
			return array('status' => 'skipped', 'reason' => 'SYNC_ALREADY_RUNNING');
		}

		try {
		$job = $this->afg_m_stuffing_plan->get_gocomet_job($sp_id);
		if (!$job) {
			return array('status' => 'error', 'reason' => 'JOB_NOT_FOUND');
		}

		$mbl      = strtoupper(trim((string)$job->mother_bl));
		$scac     = strtoupper(trim((string)$job->scac));
		$job_no   = trim((string)$job->kode_ref);
		$track_id = trim((string)$job->id_gocomet);
		$job_type = (int)substr($job_no, 4, 3);
		$mode     = in_array($job_type, array(200, 600), true) ? 'air' : 'ocean';

		// Preserve cakupan auto-tracking lama. Existing tracking tetap boleh
		// di-update jika identitasnya berubah.
		$auto_types = array(100, 101, 300, 301);
		if ($track_id === '' && !in_array($job_type, $auto_types, true)) {
			return array('status' => 'skipped', 'reason' => 'JOB_TYPE_NOT_ELIGIBLE');
		}

		if ($this->is_gocomet_carrier_excluded($job->carrier)) {
			return array('status' => 'skipped', 'reason' => 'CARRIER_EXCLUDED');
		}

		$validation = $this->validate_gocomet_mbl($mbl, $scac);
		if (!$validation['valid']) {
			log_message('error', 'GoComet dilewati untuk sp_id '.$sp_id.': '.$validation['message']);
			return array(
				'status' => 'skipped',
				'reason' => 'INVALID_MBL',
				'message' => $validation['message']
			);
		}

		$pending_update = ((string)$job->status_gocomet === 'MBL_CHANGED_PENDING');
		if ($track_id !== '' && !$tracking_identity_changed && !$pending_update) {
			return array('status' => 'skipped', 'reason' => 'TRACKING_UNCHANGED');
		}

		$token_row = $this->dbx->select('access_token')->get('tokens')->row();
		$token = $token_row ? trim((string)$token_row->access_token) : '';
		if ($token === '') {
			return array('status' => 'error', 'reason' => 'GOCOMET_TOKEN_MISSING');
		}

		$is_update = ($track_id !== '');
		if ($is_update) {
			$url = 'https://tracking.gocomet.com/api/v1/integrations/update-multiple-tracking';
			$raw_body = array(
				'token' => $token,
				'trackings' => array(
					$track_id => array(
						'tracking_number' => $mbl,
						'carrier_code' => $scac,
						'reference_no' => $job_no,
					)
				)
			);
		} else {
			$url = 'https://tracking.gocomet.com/api/v1/integrations/add_tracking_number';
			$raw_body = array(
				'token' => $token,
				'tracking' => array(
					'carrier_code' => $scac,
					'tracking_number' => $mbl,
					'mode' => $mode,
					'reference_no' => $job_no,
				)
			);
		}

		try {
			$client = new \GuzzleHttp\Client();
			$response = $client->request('POST', $url, array(
				'headers' => array('Content-Type' => 'application/json'),
				'json' => $raw_body,
				'connect_timeout' => 10,
				'timeout' => 20,
			));

			$response_data = json_decode($response->getBody()->getContents(), true);
			if (!is_array($response_data)) {
				$response_data = array();
			}

			if (!$is_update) {
				$new_tracking_id = trim((string)($response_data['tracking_id'] ?? ''));

				if ($new_tracking_id === '' && isset($response_data['trackings'])) {
					$trackings = $response_data['trackings'];
					if (is_string($trackings)) {
						$trackings = json_decode($trackings, true);
					}
					if (is_array($trackings) && !empty($trackings)) {
						$keys = array_keys($trackings);
						$new_tracking_id = trim((string)$keys[0]);
					}
				}

				if ($new_tracking_id === '') {
					return array('status' => 'error', 'reason' => 'TRACKING_ID_NOT_RETURNED');
				}

				$this->afg_m_stuffing_plan->save_id_gocomet($new_tracking_id, $sp_id);
				$track_id = $new_tracking_id;
			}

			$this->afg_m_stuffing_plan->save_gocomet_state(
				$sp_id,
				$is_update ? 'Tracking Updated' : 'Tracked'
			);

			return array(
				'status' => 'success',
				'action' => $is_update ? 'updated' : 'added',
				'tracking_id' => $track_id
			);
		} catch (\GuzzleHttp\Exception\RequestException $e) {
			$response_body = $e->getResponse()
				? $e->getResponse()->getBody()->getContents()
				: $e->getMessage();

			// Pada ADD duplicate, GoComet biasanya mengembalikan UUID existing.
			// Simpan UUID tersebut ke sp_id yang benar tanpa membuat tracking baru.
			if (!$is_update && preg_match('/[0-9a-fA-F\-]{36}/', $response_body, $matches)) {
				$this->afg_m_stuffing_plan->save_id_gocomet($matches[0], $sp_id);
				$this->afg_m_stuffing_plan->save_gocomet_state($sp_id, 'Tracked');

				return array(
					'status' => 'success',
					'action' => 'linked_existing',
					'tracking_id' => $matches[0]
				);
			}

			log_message('error', 'GoComet sync gagal untuk sp_id '.$sp_id.': '.$response_body);
			return array(
				'status' => 'error',
				'reason' => 'GOCOMET_REQUEST_FAILED',
				'message' => $e->getMessage()
			);
		} catch (\Throwable $e) {
			log_message('error', 'GoComet sync error untuk sp_id '.$sp_id.': '.$e->getMessage());
			return array(
				'status' => 'error',
				'reason' => 'GOCOMET_SYNC_ERROR',
				'message' => $e->getMessage()
			);
		}
		} finally {
			$this->dbx->query('SELECT RELEASE_LOCK(?)', array($lock_name));
		}
	}
	
function cek_status_track($kode_ref)
{
	$job = $this->dbx->get_where('trnstuffingplan', array(
		'kode_ref' => $kode_ref,
		'region_id' => $this->session->userdata('region_id')
	))->row();

	if (!$job) {
		return 'Untracked';
	}

	// MBL lokal sudah berubah tetapi GoComet belum berhasil di-update.
	// Jangan lookup MBL baru ke tracking lama.
	if ((string)$job->status_gocomet === 'MBL_CHANGED_PENDING') {
		return 'MBL_CHANGED_PENDING';
	}

	$tracking_number = strtoupper(trim((string)$job->mother_bl));
	if ($tracking_number === '') {
		return 'Untracked';
	}

	$token_row = $this->dbx->select('access_token')->get('tokens')->row();
	$token = $token_row ? trim((string)$token_row->access_token) : '';
	if ($token === '') {
		return 'Untracked';
	}

	try {
		$client = new \GuzzleHttp\Client();
		$response = $client->request('GET', 'https://tracking.gocomet.com/api/v1/integrations/live-tracking', array(
			'headers' => array('Accept' => 'application/json'),
			'query' => array(
				'tracking_numbers' => array($tracking_number),
				'token' => $token,
			),
			'connect_timeout' => 10,
			'timeout' => 20,
		));

		$api_response = json_decode($response->getBody(), true);
		if (!empty($api_response['updated_trackings'][0]['status'])) {
			$status = $api_response['updated_trackings'][0]['status'];
			$this->afg_m_stuffing_plan->save_status_gocomet($kode_ref, $status);
			return $status;
		}
	} catch (\Throwable $e) {
		log_message('error', 'Cek status GoComet gagal untuk '.$kode_ref.': '.$e->getMessage());
	}

	return 'Invalid Master BL';

}

public function fetch_dropdown_data() {
    $searchTerm = $this->input->get('q');
    $data = $this->afg_m_cities->get_filtered_data($searchTerm);
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

public function fetch_dropdown_data_air() {
    $searchTerm = $this->input->get('q');
    $data = $this->afg_m_cities->get_filtered_data_air($searchTerm);

    $response = [];
    foreach ($data->result() as $row) {
        $response[] = [
            'id' => $row->name,
            'text' => $row->name
        ];
    }

    echo json_encode($response);
}

public function dropdown_port_jadul(){
		$searchTerm = $this->input->get('q');
		$port = $this->afg_m_cities->getListCitiesDrop($searchTerm);
		if(empty($port->result_array())){
		$data[]="";	
		}else{
		foreach ($port->result() as $row)
		{	
			 $response[] = [
            'id' => $row->city_name,
            'text' => $row->city_name
			];
		}
		echo json_encode($response);
	}
}

function get_data_edit_stuffing_plan()
{
    $data = $this->afg_m_stuffing_plan->get_data_edit_stuffing_plan($_POST);
    $double_container = $this->afg_m_stuffing_plan->get_double_container($_POST['sp_id']);

    if($data) 
    {
        if($double_container){
            $result = [];

            foreach ($double_container as $item) {
                $result[] = [
                    'count_no' => $item->count_no,
                    'seal_no' => $item->seal_no,
                    'count_size'=>$item->count_size
                ];
            }
            $data->container_data = json_encode($result);
        }else{
            if (empty($data->cont_seal_no)) {
                $result[] = [
                    'count_no' => '',
                    'seal_no' => '',
                    'count_size'=>'',
                ];
                $data->container_data = json_encode($result);
            } elseif (strpos($data->cont_seal_no, '/') === false) {
                $result[] = [
                    'count_no' => $data->cont_seal_no,
                    'seal_no' => '',
                    'count_size'=>''
                ];
                $data->container_data = json_encode($result);
            }else{
            $cont_seal_no = $data->cont_seal_no;

            // Hapus semua spasi
            $cleaned = str_replace(' ', '', $cont_seal_no);

            // Pisahkan berdasarkan '/'
            list($container_no, $seal_no) = explode('/', $cleaned);
            $data->container_no = $container_no;
            $data->seal_no = $seal_no;
            $result[] = [
                    'count_no' => $container_no,
                    'seal_no' => $seal_no,
                    'count_size'=>$data->type_cont_size
                ];
                $data->container_data = json_encode($result);
            }
        }
        if (empty($data->closing_date_time)) {
            $formatted_datetime = '';	
        } elseif(empty($data->closing_time)) {
            $datetime = DateTime::createFromFormat("d/m/Y H:i:s", "$data->closing_date_time $data->closing_time");
            $formatted_datetime = $datetime ? $datetime->format("Y-m-d\TH:i") : '';
        }else{
            // Gabungkan dan ubah ke format datetime-local
            $datetime = DateTime::createFromFormat("Ymd H:i:s", "$data->closing_date_time $data->closing_time");
            $formatted_datetime = $datetime ? $datetime->format("Y-m-d\TH:i") : '';
        }

        $data->tanggal_job = $this->fungsi->jin_date_str($data->tanggal_job);
        $data->closing_date_time = !empty($data->closing_date_time) ? date("Y-m-d", strtotime($data->closing_date_time)) : '';
        $data->etd_jkt = !empty($data->etd_jkt) ? date("Y-m-d", strtotime($data->etd_jkt)) : '';
        $data->atd = !empty($data->atd) ? date("Y-m-d", strtotime($data->atd)) : '';
        $data->eta = !empty($data->eta) ? date("Y-m-d", strtotime($data->eta)) : '';
        $data->ata = !empty($data->ata) ? date("Y-m-d", strtotime($data->ata)) : '';
        $data->stuffing_date = !empty($data->stuffing_date) ? date("Y-m-d", strtotime($data->stuffing_date)): '';

        // ==========================================================
        // TAMBAHAN: Tarik instruksi_stuffing khusus mode LCL
        // ==========================================================
        if (isset($_POST['mode']) && $_POST['mode'] == 'LCL') {
            $sp_id = $_POST['sp_id'];
            $query_instruksi = $this->db->query("SELECT instruksi_stuffing FROM x_instruksi_stuffing WHERE id_sp = ?", array($sp_id));
            $row_instruksi = $query_instruksi->row();

            // Masukkan ke object $data
            $data->instruksi_stuffing = ($row_instruksi) ? $row_instruksi->instruksi_stuffing : '';
        } else {
            $data->instruksi_stuffing = ''; // Default kosong jika bukan LCL
        }
        // ==========================================================

        echo json_encode($data);
    } 
    else 
    {
        echo json_encode(array('stuffing_date'=>'','closing_date_time'=>'','etd_jkt'=>'','kode_ref'=>''));
    }
}
	
	public function get_list_agent()
{
		// Tangkap keyword yang dikirim oleh Select2
		$keyword = $this->input->get('keyword');
		
		// Panggil fungsi model (Sesuaikan 'Nama_model' dengan nama file model Anda)
		$data = $this->afg_m_stuffing_plan->get_agent_for_select2($keyword);
		
		// Kembalikan output sebagai JSON
		echo json_encode($data);
	}
	
	public function dismiss_announcement() {
		  $user_id = $this->session->userdata('user_id');

		  // Update preferensi user
		  $this->dbx->where('user_id', $user_id)->update('mstadmin', ['show_announcement' => 0]);

		  // Update session biar tidak muncul lagi
		  $this->session->set_userdata('show_announcement', 0);
		}


}

?>
