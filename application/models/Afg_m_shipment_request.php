<?php class Afg_m_shipment_request extends CI_Model
{	
	var $table='trnshipmentrequest';
	public $last_list_sql = '';
	public $last_list_error = '';
	private $second_db;
  public $dbx;

    public function __construct() {
        parent::__construct();

        $tahun = $this->session->userdata('db');

        if ($tahun == '' || !is_numeric($tahun)) {
            $this->dbx = $this->load->database('master', TRUE);
        } else {
            $this->dbx = $this->load->database(switch_db($tahun), TRUE);
        }
    }
	
	function get_all_request($data,$limit){
		$region = $this->session->userdata('region_id');
		
		$this->dbx->select('*');
		$this->dbx->from('trnshipmentrequest');
		$this->dbx->join('trnexport','trnexport.export_id=trnshipmentrequest.export_id');
		$this->dbx->where('trnexport.region_id',$region);
		$this->dbx->where('trnshipmentrequest.isProcessed','No');
		
		if($data['_search']=='true') {
			$searchField = $data['searchField'];
			$searchString = $data['searchString'];
      	$searchOper  =  $data['searchOper'];
			
			$ops = array(
            	'eq'=>'=', //equal
            	'ne'=>'<>',//not equal
            	'bw'=>'LIKE', //begins with
            	'lt'=>'<', //less than
            	'le'=>'<=',//less than or equal
            	'gt'=>'>', //greater than
            	'ge'=>'>=',//greater than or equal 
            	'bn'=>'NOT LIKE', //doesn't begin with
            	'in'=>'LIKE', //is in
            	'ni'=>'NOT LIKE', //is not in
            	'ew'=>'LIKE', //ends with
            	'en'=>'NOT LIKE', //doesn't end with
            	'cn'=>'LIKE', // contains
            	'nc'=>'NOT LIKE'  //doesn't contain
        	);

      	foreach ($ops as $key=>$value){
 				if($searchOper==$key) {
            	$ops = $value;
          	}
     		} 
            
       	if($searchOper == 'eq' ) $searchString = $searchString;
       	if($searchOper == 'bw' || $searchOper == 'bn') $searchString .= '%';
       	if($searchOper == 'ew' || $searchOper == 'en' ) $searchString = '%'.$searchString;
      	if($searchOper == 'cn' || $searchOper == 'nc' || $searchOper == 'in' || $searchOper == 'ni') $searchString = '%'.$searchString.'%';
				
      	$where = "$searchField $ops '$searchString' ";                     
			$this->dbx->where($where); 				            
		}
		
		if($limit==true) {
			$this->dbx->limit($data['rows'],$data['start']);
		}
	
		$this->dbx->order_by($data['sidx'],$data['sord']);
		
		return $this->dbx->get()->result();
	}
	
	function tampil_data_ShipmentRequestFiltered_semua(){
		$this->dbx->select('*');
		$this->dbx->from('trnshipmentrequest');
		$this->dbx->join('trnexport','trnexport.export_id=trnshipmentrequest.export_id');
		$query = $this->dbx->get();
		return $query;
	}
	
	function tampil_data_ShipmentRequestFiltered($sp_id){
		$this->dbx->select('*');
		$this->dbx->from('trnshipmentrequest');
		$this->dbx->join('trnexport','trnexport.export_id=trnshipmentrequest.export_id');
		$this->dbx->where('trnexport.sp_id',$sp_id);
		$this->dbx->where('trnshipmentrequest.isProcessed','No');
		$query = $this->dbx->get();
		return $query;
	}
	
	function getDataShipmentRequest($export_id,$start,$limit,$sidx,$sord,$where){
		$this->dbx->select('*');
		$this->dbx->limit($limit);
		if($where != NULL)$this->dbx->where($where,NULL,FALSE);
		$this->dbx->join('trnexport','trnexport.export_id=trnshipmentrequest.export_id');
		$this->dbx->where('trnshipmentrequest.export_id',$export_id);
		$query = $this->dbx->get($this->table,$limit,$start);
		return $query;
	}

	function getMaxID(){
		$this->dbx->select_max('shipment_request_id');
		$query = $this->dbx->get($this->table);
		if ( $query->result() == '')
		{
			return 1;
		}
		else
		{
			return $query->row()->shipment_request_id;
		}
	}

	function getDataShipmentRequestFiltered($start,$limit,$sidx,$sord,$where){
		$this->dbx->select('*');
    	$this->dbx->limit($limit);
    	if($where != NULL)$this->dbx->where($where,NULL,FALSE);
		$this->dbx->join('trnexport','trnexport.export_id=trnshipmentrequest.export_id');
    	$query = $this->dbx->get($this->table,$limit,$start);
		return $query;
	}

	function add($shipper_id,$export_id,$consignee,$notify_party,$container_no,$shipping_unit,$description,$gross_weight,$netto_weight,$freight_payable,$note,$isProcessed){
    	$data = array
		(
			'shipper_id'=>$shipper_id,
			'export_id'=>$export_id,
			'consignee'=>$consignee,
			'notify_party'=>$notify_party,
			'container_no'=>$container_no,
			'shipping_unit'=>$shipping_unit,
			'description'=>$description,
			'gross_weight'=>$gross_weight,
			'netto_weight'=>$netto_weight,
			'freight_payable'=>$freight_payable,
			'note'=>$note,
			'isProcessed'=>$isProcessed
    	);
    	$this->dbx->insert($this->table,$data);
		$shipmentRequestID = $this->getMaxID();
		$data = array
		(
			'shipment_request_id'=>$shipmentRequestID,			
			'consignee'=>$consignee,
			'notify_party'=>$notify_party,
			'container_no'=>$container_no,
			'shipping_unit'=>$shipping_unit,
			'description'=>$description,
			'gross_weight'=>$gross_weight,
			'netto_weight'=>$netto_weight,
			'freight_payable'=>$freight_payable,
			'isProcessed'=>$isProcessed
    	);
		$this->dbx->insert('tempshipmentfinal',$data);
	}

	function update($shipment_request_id,$shipper_id,$export_id,$consignee,$notify_party,$container_no,$shipping_unit,$description,$gross_weight,$netto_weight,$freight_payable,$note,$isProcessed){
    	$data = array
		(
			'shipper_id'=>$shipper_id,
			'export_id'=>$export_id,
			'consignee'=>$consignee,
			'notify_party'=>$notify_party,
			'container_no'=>$container_no,
			'shipping_unit'=>$shipping_unit,
			'description'=>$description,
			'gross_weight'=>$gross_weight,
			'netto_weight'=>$netto_weight,
			'freight_payable'=>$freight_payable,
			'note'=>$note,
			'isProcessed'=>$isProcessed
    	);
    	$this->dbx->where('shipment_request_id',$shipment_request_id);
    	$this->dbx->update($this->table,$data);
	}
	
	function manage_request_edit($data,$id){
		$data['lock']=$data['isLock'];
		$data['shipment_request_id']=$data['id'];
		unset($data['id']);
		unset($data['isLock']);
		unset($data['oper']);
		
		$this->dbx->where('shipment_request_id',$id);
    	$this->dbx->update($this->table,$data);
    	if($this->dbx->affected_rows() > 0) {
			return '1';
		} else {
			return '0';	
		}
	}
	
	function manage_request_delete($id){
		$this->dbx->where('shipment_request_id',$id);
    	$this->dbx->delete($this->table);
    	if($this->dbx->affected_rows() > 0) {
			return '1';
		} else {
			return '0';	
		}
	}


	function get_all_finalize_order($data, $limit)
{
    $region = $this->session->userdata('region_id');

    // ============================================================
    // 1. RESOLVE SCHEDULE TERLEBIH DAHULU
    // Jangan dilakukan setelah query booking mulai dibangun.
    // ============================================================
    $scheduleEtd = null;

    if (!empty($data['schedule_id']) && (int)$data['schedule_id'] > 0) {

        $requestedScheduleId = (int)$data['schedule_id'];

        $schedule = $this->dbx
            ->select(
                'tbl_schedules_direct.id,
                 tbl_schedules_direct.etd_jkt,
                 tbl_schedules_direct.city_id'
            )
            ->from('tbl_schedules_direct')
            ->where('tbl_schedules_direct.id', $requestedScheduleId)
            ->limit(1)
            ->get()
            ->row_array();

        if (!empty($schedule) && !empty($schedule['etd_jkt'])) {

            $scheduleEtd = date(
                'Y-m-d',
                strtotime($schedule['etd_jkt'])
            );

        } else {

            log_message(
                'error',
                '[SHIPMENT_REQUEST] Schedule ID '
                . $requestedScheduleId
                . ' tidak ditemukan di tbl_schedules_direct.'
            );
        }
    }

    // ============================================================
    // 2. BARU BANGUN QUERY BOOKING
    // ============================================================
    $hasStuffingRegion = in_array(
        'stuffing_region',
        $this->dbx->list_fields('trnstuffingplan_booking'),
        true
    );

    $this->dbx->select(
        '*, book.booking_code as real_id, book.region_id as owner_region'
        . ($hasStuffingRegion
            ? ', book.stuffing_region as stuffing_region'
            : '')
    );

    $this->dbx->from('trnstuffingplan_booking book');

    $this->dbx->join(
        'trnstuffingplan_booking_detail',
        'trnstuffingplan_booking_detail.booking_id = book.booking_code',
        'left'
    );

    // ============================================================
    // MODE
    // ============================================================
    if (!empty($data['mode'])) {

        if ($data['mode'] == 'FCL') {

            $this->dbx->like('book.mode', 'FCL');

        } elseif ($data['mode'] == 'Air') {

            $this->dbx->like('book.mode', 'AIR-FREIGHT');

        } else {

            $this->dbx->not_like('book.mode', 'FCL');
        }
    }

    // ============================================================
    // STATUS
    // ============================================================
    $this->dbx->where('book.status', 'finalize');
    $this->dbx->where("NOT EXISTS (SELECT 1 FROM trnstuffingplandetail sd WHERE CONVERT(sd.booking_code USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(book.booking_code USING utf8mb4) COLLATE utf8mb4_unicode_ci)", null, false);

    // ============================================================
    // REGION / COLOAD
    // ============================================================
    if ($hasStuffingRegion) {

        $this->dbx
            ->group_start()

                ->group_start()

                    ->where('book.region_id', $region)

                    ->group_start()
                        ->where(
                            'book.stuffing_region IS NULL',
                            null,
                            false
                        )
                        ->or_where('book.stuffing_region', '')
                    ->group_end()

                ->group_end()

                ->or_where('book.stuffing_region', $region)

            ->group_end();

    } else {

        $this->dbx->where('book.region_id', $region);
    }

    // ============================================================
	// SCHEDULE FILTER
	//
	// Prioritas:
	// 1. Booking yang memang memiliki schedule_id yang dipilih
	// 2. Booking schedule lain tetapi ETD sama
	//
	// Destination / transit tetap difilter di blok berikutnya.
	// ============================================================
	if (
		!empty($data['schedule_id'])
		&& (int)$data['schedule_id'] > 0
	) {

		$requestedScheduleId = (int)$data['schedule_id'];

		$this->dbx->group_start();

			// Schedule ID yang memang sama SELALU boleh tampil
			$this->dbx->where(
				'book.schedule_id',
				$requestedScheduleId
			);

			// Schedule berbeda tetapi sailing date sama
			if (!empty($scheduleEtd)) {

				$this->dbx->or_where(
					'book.expect_etd',
					$scheduleEtd
				);
			}

		$this->dbx->group_end();
	}
	// SCHEDULE FILTER
	//
	// Prioritas:
	// 1. Booking yang memang memiliki schedule_id yang dipilih
	// 2. Booking schedule lain tetapi ETD sama
	//
	// Destination / transit tetap difilter di blok berikutnya.
	// ============================================================
	if (
		!empty($data['schedule_id'])
		&& (int)$data['schedule_id'] > 0
	) {

		$requestedScheduleId = (int)$data['schedule_id'];

		$this->dbx->group_start();

			// Schedule ID yang memang sama SELALU boleh tampil
			$this->dbx->where(
				'book.schedule_id',
				$requestedScheduleId
			);

			// Schedule berbeda tetapi sailing date sama
			if (!empty($scheduleEtd)) {

				$this->dbx->or_where(
					'book.expect_etd',
					$scheduleEtd
				);
			}

		$this->dbx->group_end();
	}

    // ============================================================
    // DESTINATION / TRANSIT
    // ============================================================
    if (
        !empty($data['destination'])
        || !empty($data['trans'])
    ) {

        $this->dbx->group_start();

        if (!empty($data['destination'])) {

            $this->dbx->or_where(
                'book.destination_city',
                $data['destination']
            );

            $this->dbx->or_where(
                'book.real_dest',
                $data['destination']
            );
        }

        if (!empty($data['trans'])) {

            $this->dbx->or_where(
                'book.trans_city',
                $data['trans']
            );

            $this->dbx->or_where(
                'book.real_dest',
                $data['trans']
            );
        }

        $this->dbx->group_end();
    }

    // ============================================================
    // jqGrid SEARCH
    // ============================================================
    if (
        isset($data['_search'])
        && $data['_search'] == 'true'
    ) {

        if (!empty($data['filters'])) {

            $filters = json_decode(
                $data['filters'],
                true
            );

            if (
                $filters
                && isset($filters['rules'])
            ) {

                $this->dbx->group_start();

                foreach ($filters['rules'] as $rule) {

                    $field = $rule['field'];
                    $searchData = $rule['data'];

                    if ($field == 'real_id') {
                        $field = 'book.booking_code';
                    }

                    if ($field == 'house_bl') {
                        $field = 'book.house_bl';
                    }

                    if ($field == 'si_number') {
                        $field = 'book.si_number';
                    }

                    if ($field == 'cargo_type') {
                        $field = 'book.cargo_type';
                    }

                    if ($field == 'destination_city') {
                        $field = 'book.destination_city';
                    }

                    if ($field == 'shipper_name') {
                        $field =
                            'trnstuffingplan_booking_detail.shipper_name';
                    }

                    $this->dbx->like(
                        $field,
                        $searchData
                    );
                }

                $this->dbx->group_end();
            }

        } else {

            $searchField =
                isset($data['searchField'])
                    ? $data['searchField']
                    : '';

            $searchString =
                isset($data['searchString'])
                    ? $data['searchString']
                    : '';

            $searchOper =
                isset($data['searchOper'])
                    ? $data['searchOper']
                    : '';

            $ops = array(
                'eq' => '=',
                'cn' => 'LIKE'
            );

            if (isset($ops[$searchOper])) {

                $operator = $ops[$searchOper];

                if ($searchOper == 'cn') {
                    $searchString =
                        '%' . $searchString . '%';
                }

                $where =
                    "$searchField $operator "
                    . $this->dbx->escape($searchString);

                $this->dbx->where(
                    $where,
                    null,
                    false
                );
            }
        }
    }

    // ============================================================
    // LIMIT
    // ============================================================
    if ($limit == true) {

        $this->dbx->limit(
            $data['rows'],
            $data['start']
        );
    }

    $this->dbx->order_by(
        'book.expect_etd',
        'asc'
    );

    $q = $this->dbx->get();

    $this->last_list_sql =
        $this->dbx->last_query();

    $this->last_list_error =
        $q
            ? ''
            : (string)(
                $this->dbx->error()['message']
                ?? ''
            );

    if (!$q) {

        log_message(
            'error',
            'get_all_finalize_order gagal: '
            . $this->last_list_error
            . ' | '
            . $this->last_list_sql
        );

        return array();
    }

    return $q->result();
}
	/** Diagnosa daftar Add to job (dipakai ?debug=1 di controller). */
	function finalize_diagnostics()
	{
		$fields = $this->dbx->list_fields('trnstuffingplan_booking');
		$has    = in_array('stuffing_region', $fields, true);
		$this->dbx->select('region_id, ' . ($has ? "IFNULL(stuffing_region, '(NULL)')" : "'(kolom tidak ada)'") . ' AS stuffing_region, COUNT(*) AS jumlah', false)
		          ->from('trnstuffingplan_booking')
		          ->where('status', 'finalize')
		          ->group_by(array('region_id', 'stuffing_region'));
		$q = $this->dbx->get();
		return array(
			'database'               => $this->dbx->database,
			'kolom_stuffing_region'  => $has,
			'booking_finalize'       => $q ? $q->result_array() : $this->dbx->error(),
		);
	}

	function get_shipper_by_id($data)
   {
   	$q = $this->dbx->get_where('mstshipper',$data);
   	if($q->num_rows()>0) 
   	{
   		return array('id_shipper'=>$q->row('id_shipper'),'sales'=>$q->row('sales'));
   	}
   }

    function find_and_insert_booking_order($data)
    {
        require_once APPPATH . 'libraries/Booking_job_sync.php';
        $requested = trim((string)($data['booking_id'] ?? ''));
        $bc = $requested;
        $region = trim((string)$this->session->userdata('region_id'));
        $spId = (int)($data['sp_id'] ?? 0);
        if ($bc === '' || $region === '' || $spId <= 0) {
            return ['status'=>0,'code'=>'INVALID_INPUT','booking_id'=>$bc,'message'=>'Booking, region atau SP ID tidak valid.'];
        }
        $tx = new Booking_job_sync($this->dbx);
        try {
            $tx->begin();
            $tx->requireInnoDB(['trnstuffingplan','trnstuffingplan_booking','trnstuffingplandetail','tbl_detail','x_tracking_export']);
            $targetPlan = $tx->lockPlan($spId, $region);
            if (trim((string)$targetPlan['kode_ref']) === '' || (!empty($data['code']) && $data['code'] !== $targetPlan['kode_ref'])) {
                throw new DomainException('Master job di browser tidak sesuai dengan stuffing plan. Muat ulang job.');
            }
            // Preserve latest GCL coload child BC resolution (only child in active region).
            if ($this->dbx->table_exists('trnbooking_link')) {
                $child = $tx->check($this->dbx->select('b.booking_code, b.region_id')
                    ->from('trnbooking_link l')
                    ->join('trnstuffingplan_booking b','b.booking_code = l.booking_code','inner')
                    ->where('l.linked_company','GW')->where('l.linked_bc_no',$bc)->where('l.status','active')
                    ->where('b.status !=','cancelled')->where('b.region_id',$region)->order_by('l.id','DESC')->limit(1)->get(), 'RESOLVE_COLOAD')->row_array();
                if ($child && strcasecmp($child['region_id'], $region) === 0) $bc = $child['booking_code'];
            }
            $data['booking_id'] = $bc;
            $master = $tx->lockBooking($bc);
            $owner = trim((string)$master['region_id']);
            $stuffing = trim((string)($master['stuffing_region'] ?? ''));
            if (strcasecmp($stuffing !== '' ? $stuffing : $owner, $region) !== 0) {
                throw new DomainException('Booking tidak boleh di-stuffing oleh region '.$region.'.');
            }
            $existing = $tx->details($bc);
            if ($existing) {
                if (count($existing) !== 1 || (int)$existing[0]['sp_id'] !== $spId || strcasecmp($existing[0]['region_id'], $region) !== 0) {
                    throw new DomainException('Booking sudah berada di job lain atau mempunyai detail ganda. Audit / Move Job diperlukan.');
                }
                // The requested business invariant is based on existing detail. Report missing auxiliary records separately.
                $e = $existing[0];
                $blDetail = $tx->check($this->dbx->get_where('tbl_detail',['detail_id'=>$e['sp_detail_id']]),'CHECK_EXISTING_BL')->row_array();
                $tracking = $tx->check($this->dbx->get_where('x_tracking_export',['booking_code'=>$bc,'no_job'=>$e['kode_ref']]),'CHECK_EXISTING_TRACKING')->row_array();
                $warning = (!$blDetail || !$tracking) ? 'Detail lama sudah ada tetapi BL/tracking belum lengkap. Audit manual diperlukan.' : null;
                $job = $tx->sync($bc, $tx->planSchedule($targetPlan));
                $tx->commit();
                return ['status'=>1,'code'=>'ALREADY_EXISTS','booking_id'=>$bc,'requested_booking_id'=>$requested,'already_exists'=>true,'job_no'=>$job,'sp_detail_id'=>$e['sp_detail_id'],'message'=>'Booking sudah ada. Status dan job_no disinkronkan.','warning'=>$warning];
            }
            if (strtolower(trim((string)$master['status'])) !== 'finalize') throw new DomainException('Status booking bukan finalize: '.$master['status']);
            $booking = $tx->check($this->dbx->select('d.*, b.*')->from('trnstuffingplan_booking_detail d')
                ->join('trnstuffingplan_booking b','b.booking_code = d.booking_id')->where('d.booking_id',$bc)->get(), 'LOAD_BOOKING_DETAIL')->result();
            if (count($booking) !== 1) throw new DomainException('Booking harus mempunyai satu detail sumber. Jumlah ditemukan: '.count($booking));
            if (trim((string)$master['si_number']) === '') throw new DomainException('SI Number booking kosong. Lengkapi sebelum Add to Job.');
            // Suffix from backend under plan lock, not grid row count.
            $data['num'] = $tx->freeSuffix($targetPlan,$region,strtoupper(trim((string)($data['num'] ?? ''))));
            $data['code'] = $targetPlan['kode_ref'];
            $data['kode_ref'] = $data['code'].$data['num'];
            $data['sp_id'] = $spId;
            $bookingUpdate = $tx->planSchedule($targetPlan);
            foreach (['etd'=>'expect_etd','closing'=>'closing_date','eta'=>'eta'] as $key=>$col) {
                $raw = trim((string)($data[$key] ?? ''));
                if ($raw === '' || substr($raw,0,10) === '0000-00-00') continue;
                $ts = strtotime(str_replace('/','-',$raw));
                if ($ts === false) throw new DomainException('Tanggal '.$key.' tidak valid.');
                $bookingUpdate[$col] = date($key === 'closing' ? 'Y-m-d H:i:s' : 'Y-m-d',$ts);
            }
            if (trim((string)($data['vessel'] ?? '')) !== '') $bookingUpdate['vessel'] = trim($data['vessel']);
            // real_dest is managed by existing BEFORE UPDATE trigger.
		// 2. MULAI PERULANGAN
		foreach($booking as $row) {
			$org = $row->origin_city;
			$dst = $row->destination_city;
			$kode_ref = $data['kode_ref'];
			$bl = $row->house_bl;
			
			// --- INSERT TRNSTUFFINGPLANDETAIL ---
			$detailData = array(
				'code'               => $data['code'],
				'kode_ref'           => $kode_ref,
				'incoterms'          => $row->incoterm,
				'destination'        => $row->destination_city,
				'shipper'            => $row->shipper_id,
				'shipper_print'      => $row->forwarder_name,
				'note'               => strtoupper($row->shipper_name) . "\n" . strtoupper($row->shipper_address),
				'consignee'          => strtoupper($row->consignee_name),
				'consignee_add'      => strtoupper($row->consignee_name) . "\n" . strtoupper($row->consignee),
				'notify'             => $row->notify_name,
				'notify_add'         => strtoupper($row->notify),
				'shipping_mark'      => strtoupper($row->shipping_mark),
				'agent_code'         => isset($data['agent']) ? $data['agent'] : (isset($_POST['agent']) ? $_POST['agent'] : ''),
				'freight'            => strtoupper($row->freight),
				'sales'              => $row->sales,
				'marking_nos'        => $row->marking,
				'cetak_jml_doc_origi'=> strcasecmp(trim((string)$row->number_of_bl), '3 Original') === 0 ? 'TRUE' : 'FALSE',
				'express_release'    => strcasecmp(trim((string)$row->number_of_bl), 'Express Release') === 0 ? 'TRUE' : 'FALSE',
				'desc_goods'         => $row->description_of_goods,
				'no_pack_unit'       => $row->package_type,
				'no_pack_qty'        => $row->quantity,
				'no_of_pack'         => $row->quantity . " " . $row->package_type,
				'desc_print'         => strtoupper($row->description_print),
				'bl_meas'            => $row->volume,
				'netto'              => (string)($row->netto ?? ''),
				'wh_meas'            => $row->volume,
				'cost_meas'          => $row->volume,
				'ship_meas'          => $row->volume,
				'weight'             => $row->weight,
				'region_id'          => $region,
				'booking_code'       => $data['booking_id'],
				'sp_id'              => isset($data['sp_id']) ? $data['sp_id'] : (isset($_POST['sp_id']) ? $_POST['sp_id'] : ''),
				'num'                => isset($data['num']) ? $data['num'] : (isset($_POST['num']) ? $_POST['num'] : ''),
				'hb_l'               => $bl,
				'isLock'             => 0,
				'do_number'          => (string)($row->do_number ?? ''),
				'shipped_on_board'   => $row->shipped_on_board == '1' ? 'TRUE' : 'FALSE',
				// --- TAMBAHAN FIELD BARU ---
				'cargo_type'         => $row->cargo_type,
				'dg_class'           => $row->dg_class,
				'hs_code'            => $row->hs_code,
				'created_by'         => $this->session->userdata('fullname'),
				'created_at'         => date('Y-m-d H:i:s'),
				'wh_arrival'         => $row->wh_arrival
			);
			$tx->check($this->dbx->insert('trnstuffingplandetail', $detailData), 'INSERT_STUFFING_DETAIL');
			$qSpDetail = $this->dbx->insert_id();
			if (!$qSpDetail) throw new RuntimeException('ID detail tidak diperoleh.');

			// --- INSERT TBL_DETAIL ---
			$tbldetailData = array(
				'detail_id'              => $qSpDetail,
				'sp_id'                  => isset($data['sp_id']) ? $data['sp_id'] : (isset($_POST['sp_id']) ? $_POST['sp_id'] : ''),
				'shipper_name'           => $row->shipper_name,
				'shipper_address'        => $row->shipper_address,
				'consignee'              => $row->consignee,
				'notify'                 => $row->notify,
				'diff_status_2'          => $row->movement_type,
				'no_from_shipper'        => $row->si_number,
				'feeder_vessel_etd_date' => $this->fungsi->jin_date_str_strip2($row->expect_etd),
				'feeder_vessel_eta_date' => $this->fungsi->jin_date_str_strip2($row->eta),
				'port_of_loading'        => $row->pol_bl ?? $row->origin_city,
				'port_of_discharge'      => $row->pod_bl ?? $row->destination_city,
				'port_of_receive'        => $row->pol_bl ?? $row->origin_city,
				'port_of_delivery'       => $row->pod_bl ?? $row->destination_city,
				'as_agent'               => $row->as_agent,
				'payable'                => $row->payable,
				'booking_code'           => $data['booking_id'],
				'region_id'              => $region
			);
			$tx->check($this->dbx->insert('tbl_detail', $tbldetailData), 'INSERT_TBL_DETAIL');

			// --- 3. INSERT X_TRACKING_EXPORT (BARU) ---
			// Catatan: id_tracking tidak perlu dimasukkan karena tipe datanya bigint Auto Increment.
			// Database akan mengisinya secara otomatis.
			// Format datetime dari $row->wh_arrival ke d/m/Y H:i:s
			$pickupCargo = null;
    
			// Tambahkan filter agar 0000-00-00 ditolak dan tidak lolos ke fungsi DateTime
			if (!empty($row->wh_arrival) && $row->wh_arrival !== '0000-00-00 00:00:00' && $row->wh_arrival !== '0000-00-00') {
				
				$dt = DateTime::createFromFormat('Y-m-d H:i:s', $row->wh_arrival);
				
				if ($dt !== false) {
					$pickupCargo = json_encode([
						"Pickup Cargo" => $dt->format('d/m/Y H:i:s')
					], JSON_UNESCAPED_SLASHES);
				}
			}

			// Masukkan ke array trackingData
			$trackingData = array(
				'no_job'             => $kode_ref,
				'booking_code'       => $data['booking_id'],
				'booking_received'   => $row->created_at,
				'received_date'      => $master['received_date'],
				'received_by'        => $row->received_by,
				'id_shipper'         => $row->shipper_id,
				'id_request'         => (string)$row->si_number,
				'packaging'          => $row->quantity . " " . $row->package_type,
				'cbm'                => $row->volume,
				'id_agent'           => isset($data['agent']) ? $data['agent'] : (isset($_POST['agent']) ? $_POST['agent'] : null),
				'mbl_no'             => isset($data['mbl']) ? $data['mbl'] : null,
				'hbl_no'             => $bl,
				'feeder_name'        => isset($data['vessel']) ? $data['vessel'] : null,
				'etd'                => $data['etd'] ?? $row->expect_etd,
				'eta'                => $data['eta'] ?? $row->eta,
				'shipper'            => $row->forwarder_name,
				'consol_destination' => isset($data['dest']) ? $data['dest'] : '',
				'destination'        => $row->destination_city,
				'container_no'       => isset($data['container']) ? $data['container'] : null,
				'remarks'            => 'NULL',
				'sp_detail_id'       => isset($data['sp_id']) ? $data['sp_id'] : (isset($_POST['sp_id']) ? $_POST['sp_id'] : null),
				'region_id'          => $region,
				'lt_planned_gi_at'   => $row->wh_arrival,
				'pickup_cargo'       => $pickupCargo, // ← tambahan di sini
			);
			$tx->check($this->dbx->insert('x_tracking_export', $trackingData), 'INSERT_TRACKING_EXPORT');

			/* // Bagian Finance Booking di-comment dari kode asli Anda
			// ...
			*/
		}

            $job = $tx->sync($bc, $bookingUpdate);
            $tx->commit();
            return ['status'=>1,'code'=>'BOOKED','booking_id'=>$bc,'requested_booking_id'=>$requested,
                'sp_id'=>$spId,'sp_detail_id'=>$qSpDetail,'job_no'=>$job,'already_exists'=>false,
                'message'=>'Booking berhasil masuk job '.$job.'.','send_email'=>true,
                'email_meta'=>['shipper_id'=>$row->shipper_id,'consignee_name'=>$row->consignee_name,'bl_no'=>$bl,
                    'port_of_loading'=>$tbldetailData['port_of_loading'],'port_of_discharge'=>$tbldetailData['port_of_discharge'],
                    'etd'=>$bookingUpdate['expect_etd'] ?? $row->expect_etd,'eta'=>$bookingUpdate['eta'] ?? $row->eta,
                    'pol'=>$row->origin_city,'pod'=>$row->destination_city]];
        } catch (Throwable $e) {
            log_message('error','[ADD_BOOKING] '.$bc.' stage='.$tx->stage.' error='.$e->getMessage());
            return ['status'=>0,'code'=>$e instanceof DomainException ? 'VALIDATION_ERROR' : 'DATABASE_ERROR',
                'booking_id'=>$bc,'requested_booking_id'=>$requested,'stage'=>$tx->stage,'error'=>$e->getMessage(),
                'message'=>$e instanceof DomainException ? $e->getMessage() : 'Gagal memproses booking. Transaction dibatalkan.'];
        } finally { $tx->finish(); }
    }
}
/* END */
