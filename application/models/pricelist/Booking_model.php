<?php
class Booking_model extends CI_Model
{
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
	 
	function find($kodeBooking){
		return $this->dbx->get_where('trnstuffingplan_booking', array('booking_code' => $kodeBooking))->row();
	}
	function findresult($kodeBooking){
		return $this->dbx->get_where('trnstuffingplan_booking', array('booking_code' => $kodeBooking))->result();
	}
	function cari_data($kodeBooking){
		return $this->dbx->get_where('trnstuffingplan_booking', array('booking_code' => $kodeBooking))->result();
	}
	function cari($id_booking){ 
		return $this->dbx->get_where('trnstuffingplan_booking', array('id' => $id_booking))->result();
	}
	function collect_data_booking($id_booking,$booking_code) {
		$this->dbx->select('sales')->from('trnstuffingplan_booking')->where('booking_code',$booking_code);
		$q = $this->dbx->get();
		if (!empty($q->row()->sales)) {
			$admin = $this->dbx->get_where('mstadmin', array('user_id'=>$q->row()->sales))->row();
			if ($admin) {
				$sales = $admin->fullname;
			} else {
				$sales = ""; // tidak ada user_id yang cocok
			}
		} else {
			$sales = "";
		}

		$q1 = $this->dbx->get_where('trnstuffingplan_booking',array('booking_code'=>$booking_code))->row();
		$q2 = $this->dbx->get_where('trnstuffingplan_booking_detail',array('booking_id'=>$booking_code))->row();

		/*
		 * Schedule existing WAJIB dibaca dari tabel schedule, bukan dari
		 * trnstuffingplan_booking.region_id. region_id pada booking adalah
		 * cabang pemilik booking dan bisa berbeda dengan origin schedule
		 * (contoh: booking Semarang memakai schedule Surabaya).
		 */
		$schedule = null;
		if ($q1 && !empty($q1->schedule_id)) {
			$scheduleId = (int) $q1->schedule_id;
			$routeType  = strtoupper(trim((string) ($q1->route_type ?? '')));

			// Pilih tabel utama berdasarkan route_type, lalu fallback ke tabel lain
			// hanya jika ID tidak ditemukan. Ini aman untuk data legacy.
			if (strpos($routeType, 'VIA') !== false) {
				$scheduleTables = array('tbl_schedules_via', 'tbl_schedules_direct');
			} else {
				$scheduleTables = array('tbl_schedules_direct', 'tbl_schedules_via');
			}

			foreach ($scheduleTables as $scheduleTable) {
				if (!$this->dbx->table_exists($scheduleTable)) {
					continue;
				}

				$rowSchedule = $this->dbx
					->where('id', $scheduleId)
					->get($scheduleTable)
					->row();

				if ($rowSchedule) {
					// Metadata internal untuk debugging / view.
					$rowSchedule->_schedule_table = $scheduleTable;
					$schedule = $rowSchedule;
					break;
				}
			}
		}

		return array(
			"booking"  => $q1,
			"detail"   => $q2,
			"sales"    => $sales,
			"schedule" => $schedule,
			"coloader" => $this->coloader_state($q1),
		);
	}

	/**
	 * Status coloader untuk form edit:
	 *   mode/company/prefix/message dari Coloader_guard, link = BC asal aktif,
	 *   link_source = 'manual' (diisi dari Gateway) | 'api' (dikirim sistem coloader).
	 */
	public function coloader_state($booking)
	{
		$state = array('mode' => '', 'company' => '', 'prefix' => '', 'message' => '', 'link_bc' => '', 'link_company' => '', 'link_source' => '');
		if (!$booking) {
			return $state;
		}
		$info = $this->_coloader_info($booking->shipper_id ?? 0);
		if ($info) {
			$state['mode']    = $info['mode'];
			$state['company'] = $info['company_name'];
			$state['prefix']  = $info['bc_prefix'];
			$state['message'] = $info['message'];
		}
		$link = $this->_active_coload_link($booking->booking_code);
		if ($link) {
			$state['link_bc']      = $link['linked_bc_no'];
			$state['link_company'] = $link['company_name'];
			$state['link_source']  = $this->_is_manual_link($link) ? 'manual' : 'api';
			if ($link['company_code'] === self::BRANCH_COMPANY) {
				$parent = $this->branch_parent_of($booking->booking_code);
				$state['link_source']  = 'branch';
				$state['link_company'] = 'Gateway ' . ($parent && !empty($parent['region_id']) ? $parent['region_id'] : 'cabang lain');
			}
		}
		// BC ini di-coload ke cabang lain: tampilkan BC cabang tujuan
		$child = $this->branch_child_of($booking->booking_code);
		if ($child) {
			$state['child_bc']     = $child['booking_code'];
			$state['child_hbl']    = $child['house_bl'];
			$state['child_region'] = $child['region_id'];
			$state['child_lock']   = (string) $this->_branch_child_lock($child);
		}
		return $state;
	}
function shipper_selected_id($id_booking)
	{
		$q = $this->dbx->get_where('trnstuffingplan_booking',array('id'=>$id_booking));
		if($q->num_rows()>0)
		{
		return $q->row()->shipper_id;
		}
		else 
		{
			return '';
		}
	}
function consignee_selected($id_booking)
   {
  
   	$q = $this->dbx->get_where('trnstuffingplan_booking',array('id'=>$id_booking));
   	if($q->num_rows()>0) 
   	{
   		return $q->row()->consignee_id;
   	}
   }
   
   function notify_selected($id_booking)
   {
  
   	$q = $this->dbx->get_where('trnstuffingplan_booking',array('id'=>$id_booking));
   	if($q->num_rows()>0) 
   	{
   		return $q->row()->notify_id;
   	}
   }
	function insert_data($data){
		$this->dbx->insert("x_finance_booking_detail", $data);
		return array("status"=>$this->dbx->affected_rows());
	}

	function saving_data($data)
	{
		unset($data['myData']["space"]);
		$this->dbx->update("x_finance_booking_detail",
			$data['myData'],
			array('id'=>$data['id'])
		);
		return array("status"=>$this->dbx->affected_rows());
	}

	function manage_data($data){

		if($data['oper']=='add'){
			$this->dbx->insert('x_finance_booking_detail', array(
				'booking_id'=>$data['booking_id'],
				'kondisi'=>'PREPAID',
				'currency'=>'USD',
				'of_emkl'=>$data['of_emkl'],
			));	
		}
		if($data['oper']=='edit'){
			$this->dbx->where('id',$data['id']);
			$this->dbx->update('x_finance_booking_detail', array($data['kolom']=>$data['isi'],'of_emkl'=>$data['of_emkl']));	
		}
		if($data['oper']=='del'){
			/*
			$this->dbx->where('id',$data['id']);
			$this->dbx->delete('a_selling_rate');
			*/
			$id=$data["id"];
			$idx = explode(",",$id);
			for($i=0;$i<=count($idx)-1;$i++) {
				$this->dbx->delete('x_finance_booking_detail',array("id"=>$idx[$i]));
			}	
		}
	}
	
	function get_price($booking_id)
   {
   	$q = $this->dbx->get_where('x_finance_booking_detail',array('booking_id'=>$booking_id));
   	return $q;
   	}

	function update_finance($data){
		if ($data['ddl']=='no'){
		$trans=null;
		}else{
		$trans=$data['ddl2'];
		}
		$this->dbx->where('id',$data['id']);
		$this->dbx->update('trnstuffingplan_booking', array(
		'status'=>$data['status'],
		'trans_city'=>$trans,
		'vessel'=>$data['vessel'],
		'voyage'=>$data['voyage'],
		'expect_etd'=>$data['etd'],
		'closing_date'=>$data['closing_date'],
		'eta'=>$data['eta'],
		'warehouse'=>$data['warehouse'],
		'remark'=>$data['remark'],
		'pic_document'=>$data['pic_document'],
		'valid_at'=>$data['valid_at'],));	
		//print_r ($this->dbx->last_query());
		//die();
		return array("status"=>$this->dbx->affected_rows());
	}

	function get_table_selling($data,$limit){
		
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
		return $this->dbx->get_where('x_finance_booking_detail',array('booking_id'=>$data['booking_id']))->result();
	}
	
	function getDataQuotation($data,$limit){
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
		$this->dbx->where('trnstuffingplan_booking.status','prepared');
		$this->dbx->where('trnstuffingplan_booking.region_id',$this->session->userdata('region_id'));
		$this->dbx->join('trnstuffingplan_booking_detail','trnstuffingplan_booking_detail.booking_id=trnstuffingplan_booking.booking_code','left');
		return $this->dbx->get('trnstuffingplan_booking')->result();
	}	
	function get_data_consignee($data)
   {
   	$q = $this->dbx->get_where('refconsigneenotify',$data);
   	return $q;
   }
     function get_data_notify($data)
   {
   	$q = $this->dbx->get_where('refconsigneenotify',$data);
   	return $q;
   	}
	
function get_data_booking_confirmation($id)
	{
		$this->dbx->select(array(
			'sp_detail_id as spd',
			'trnstuffingplandetail.kode_ref as kdr',
			'trnstuffingplandetail.consignee_add as consignee',
			'trnstuffingplandetail.shipper_print as shipper_print',
			'trnstuffingplandetail.note as note',
			'trnstuffingplandetail.shipper_import as shipper_import',
			'trnstuffingplandetail.notify_add as notify',
			'trnstuffingplandetail.sales as sales',
			'trnstuffingplandetail.hb_l as hb_l',
			'trnstuffingplandetail.weight as weight',
			'trnstuffingplandetail.ship_meas as volume',
			'trnstuffingplandetail.bl_meas as chargeable',
			'trnstuffingplandetail.no_of_pack as package',
			'trnstuffingplandetail.express_release as express_release',
			'trnstuffingplandetail.desc_print as desc_print',
			'trnstuffingplandetail.shipping_mark as shipping_mark',
			'trnstuffingplan.port_loading as pol',
			'trnstuffingplan.destination as destination',
			'trnstuffingplan.type_of_trans as type',
			'trnstuffingplan.closing_date_time as closing',
			'trnstuffingplan.kode_ref as kode_ref',
			'trnstuffingplan.vessel as vessel',
			'trnstuffingplan.voyage as voyage',
			'trnstuffingplan.carrier as carrier',
			'trnstuffingplan.warehouse as warehouse',
			'trnstuffingplan.type_cont_qty as type_cont_qty',
			'trnstuffingplan.type_cont_size as type_cont_size',
			'trnstuffingplan.cont_seal_no as cont_seal_no',
			'trnstuffingplan.kurs as kurs',
			'trnstuffingplan.region_id as region_id',
			'tbl_detail.no_from_shipper as booking_no',
			'tbl_detail.bl_type as bl_type',
			'tbl_detail.feeder_vessel as feeder',
			'refwarehouse.warehouse_address as warehouse_address',
		));
		$this->dbx->from('trnstuffingplandetail');
		$this->dbx->join('trnstuffingplan','trnstuffingplan.sp_id=trnstuffingplandetail.sp_id','left');
		$this->dbx->join('tbl_detail', 'tbl_detail.detail_id = trnstuffingplandetail.sp_detail_id', 'left');
		$this->dbx->join('refwarehouse','refwarehouse.warehouse_code=trnstuffingplan.warehouse','left');
		$this->dbx->where('trnstuffingplandetail.sp_detail_id',$id);
		$this->dbx->where('trnstuffingplan.region_id',$this->session->userdata('region_id'));
		$query = $this->dbx->get();
		if($query->num_rows() > 0 ) {
			return $query->result_array();
		} else {
			return null;	
		}
	}
	function get_data_shipper_location($data)
   {
   	$q = $this->dbx->get_where('shipper_location',$data);
   	return $q;
   	}
	
	function get_shipper_by_id($data)
   {
	$q = $this->dbx->get_where('mstshipper',$data);
   	if($q->num_rows()>0) 
   	{
   		return array('id_shipper'=>$q->row('id_shipper'),'shipper_name'=>$q->row('shipper_name'),'shipper_address'=>$q->row('shipper_address'),'sales'=>$q->row('sales'));
   	}
   }
   
    public function get_exist_id($booking_id) {
    $this->dbx->select('id'); // Ganti 'id' jika nama kolom ID berbeda
    $this->dbx->where('booking_code',$booking_id); // Ganti 'id' jika nama kolom ID berbeda
    $query = $this->dbx->get('trnstuffingplan_booking');

    $result = $query->row();
    $last_id = $result->id ?? 0; // Jika null, dianggap 0
    return $last_id;
   }
function save_booking($data)
{
    $regional = $this->session->userdata("region_id");
    $periode = substr($this->session->userdata("db"), -2);
    $q0 = $this->dbx->get_where('refregion', array('region_name' => $regional))->row()->region_id;
    // 1. Ambil data POD dari request frontend (pod_code / code_pod)
    $q3 = !empty($data['code_pod']) ? $data['code_pod'] : (!empty($data['pod_code']) ? $data['pod_code'] : '');

    // 2. BACKEND FAIL-SAFE: Jika dari frontend kosong, lakukan query ke database
    if (empty($q3) && !empty($data['destination_city'])) {
        $this->dbx->select('code');
        $this->dbx->from('tableName'); // TODO: Ganti 'tableName' dengan nama tabel Anda yang sebenarnya
        $this->dbx->where('name', $data['destination_city']);
        
        // Asumsi tipe data is_active adalah string/enum 'TRUE'. Jika boolean/tinyint, gunakan 1.
        $this->dbx->where('is_active', 'TRUE'); 
        
        $query_pod = $this->dbx->get()->row();

        // Jika datanya ditemukan, timpa $q3 dengan code dari database
        if ($query_pod) {
            $q3 = $query_pod->code;
        }
    }

    // 3. Jika sudah di-query tapi masih kosong juga, gagalkan proses insert
    if (empty($q3)) {
        return array(
            'status' => 0, 
            'message' => 'Error: Kode POD tidak ditemukan di sistem. Pastikan Port of Destination valid dan aktif.'
        );
    }

    $portTrans = (!empty($data['trans_city']) && $data['trans_city'] !== "null") ? $data['trans_city'] : '';

    // Coload antar cabang: validasi sebelum apa pun disimpan
    list($coload, $coloadError, $coloadTarget) = $this->_branch_coload_fields($data, $this->session->userdata('region_id'));
    if ($coloadError !== null) {
        return array('status' => 0, 'message' => $coloadError);
    }
    // Coload ke cabang Gateway lain: pastikan BC cabang tujuan bisa dibuat SEBELUM BC ini disimpan
    $branchAccount = null;
    $adoptChild    = null;
    $adoptBc       = strtoupper(trim((string) ($data['coload_existing_bc'] ?? '')));
    if ($coloadTarget !== null && $adoptBc !== '') {
        // Sambungkan ke BC cabang tujuan yang sudah ada (dibuat sebelum aturan coload)
        list($adoptChild, $adoptError) = $this->_branch_adopt_check($adoptBc, $coloadTarget, $this->session->userdata('region_id'));
        if ($adoptError !== null) {
            return array('status' => 0, 'code' => 'BRANCH_ADOPT_INVALID', 'message' => $adoptError);
        }
    } elseif ($coloadTarget !== null) {
        list($branchAccount, $branchError) = $this->_branch_account($coloadTarget, $this->session->userdata('region_id'));
        if ($branchError !== null) {
            return array('status' => 0, 'code' => 'BRANCH_COLOAD_NOT_READY', 'message' => $branchError);
        }
    }

    // Shipper coloader (Osline/Winfast/HNS/cabang Gateway): tolak BC manual, atau wajib BC asal
    $data['shipper_id'] = $this->_resolve_shipper_id($data);
    $guard = $this->_coloader_guard_new($data);
    if (!empty($guard['error'])) {
        return $guard['error'];
    }

    $booking_code = "GTW-" . strtoupper(uniqid());

    // 1. Insert booking tanpa house_bl dulu
    $bookingData = array(
        'type_of_booking' => 'EXPORT',
        'booking_code' => $booking_code,
        'mode' => $data['mode'],
        'movement_type' => $data['movement_type'],
        'si_number' => $data['si_number'],
        'origin_country' => 'INDONESIA',
        'incoterm' => $data['incoterm'],
        'origin_city' => $data['origin_city'],
        'trans_city' => $portTrans,
		'pol_code'=>$data['pol_code'],
   		'pot_code'=>$data['pot_code'],
   		'pod_code'=>$data['pod_code'],
        'destination_city' => $data['destination_city'],
        'forwarder_name' => $data['shipper_name'],
        'shipper_id' => $data['shipper_id'],
        'sales' => $data['sales'],
        'created_by' => $data['shipper_id'],
        'consignee_id' => $data['consignee_id'],
        'notify_id' => $data['notify_id'],
        'status' => 'finalize',
        'freight' => $data['freight'],
        'number_of_bl' => $data['number_of_bl'],
        'expect_etd' => $data['expect_etd'],
        'eta' => $data['eta'],
        'closing_date' => $data['closing_doc'],
        'closing_cargo' => $data['closing_car'],
        'vessel' => $data['vessel'],
        'voyage' => $data['voyage'],
        'warehouse' => $data['warehouse'],
        'schedule_id' => $data['schedule_id'],
        'route_type' => $data['route_type'],
		'cargo_type'      => $data['cargo_type'],
        'file_other' => isset($data['file_other']) ? $data['file_other'] : null,
        'pic_document' => $this->session->userdata('fullname'),
        'created_at' => date("Y-m-d H:i:s"),
        'region_id' => $this->session->userdata('region_id'),
    );
    $bookingData = array_merge($bookingData, $coload);
    $this->dbx->insert('trnstuffingplan_booking', $bookingData);

    // 2. Ambil ID hasil insert (auto-increment)
    $idbooking = $this->dbx->insert_id();

    // BC asal coloader (mode manual): link dibuat sekarang. Gagal = booking dibatalkan.
    if (!empty($guard['link'])) {
        $linkError = $this->_insert_manual_link($booking_code, $guard['link']);
        if ($linkError !== null) {
            $this->dbx->where('booking_code', $booking_code)->delete('trnstuffingplan_booking');
            return array('status' => 0, 'code' => 'COLOADER_LINK_FAILED', 'message' => $linkError);
        }
    }

	// 1) Siapkan prefix sesuai destinasi (hanya jika $portTrans kosong)
	$destination = strtoupper(trim($data['destination_city'] ?? ''));
	$prefix = null;

	if (empty($portTrans) || $portTrans === 'LOS ANGELES' || $portTrans === 'NEW YORK') {
		if ($destination === 'LOS ANGELES' || $destination === 'HOUSTON' || $destination === 'SEATTLE' || $destination === 'OAKLAND') {
			$prefix = 'LAX'.$periode; // hasil: IDULA50001..IDULA59999
		} elseif ($destination === 'NEW YORK' || $destination === 'CHICAGO' || $destination === 'ATLANTA' || $destination === 'MIAMI' || $destination === 'KANSAS CITY'|| $destination === 'BALTIMORE') {
			$prefix = 'NYK'.$periode; // hasil: IDUNY60001..IDUNY69999
		}
	}


	// 2) Bangun $bl
	if ($prefix !== null) {

		// (Opsional tapi disarankan) gunakan transaksi + FOR UPDATE agar anti race-condition
		$this->dbx->trans_start();

		// Ambil suffix terbesar (4 digit terakhir) untuk prefix tsb
		// Catatan: REGEXP memastikan format tepat: PREFIX + 4 digit
		$sql = "
			SELECT MAX(CAST(RIGHT(house_bl, 5) AS UNSIGNED)) AS max_suffix
			FROM trnstuffingplan_booking
			WHERE house_bl REGEXP '^{$prefix}[0-9]{4}$'
			FOR UPDATE
		";
		$row = $this->dbx->query($sql)->row();
		$next = (int)($row->max_suffix ?? 0) + 1;

		if ($next > 9999) {
			// guard kalau range penuh
			throw new Exception('Range nomor untuk prefix ' . $prefix . ' sudah habis (0001..9999).');
		}

		// Bentuk nomor BL: PREFIX + 4 digit (leading zero)
		$bl = $prefix . str_pad($idbooking, 5, '0', STR_PAD_LEFT);

		// ... lakukan INSERT/UPDATE yang menggunakan $bl di sini jika perlu ...

		$this->dbx->trans_complete();

	} else {
		// fallback ke format lama kamu
		$q5 = str_pad($idbooking, 5, '0', STR_PAD_LEFT);
		$bl = substr($regional, 0, 1) . $q3 . "-" . $q0 . $periode . $q5;
	}


    // 4. Update record dengan house_bl
    $this->dbx->where('id', $idbooking);
    $this->dbx->update('trnstuffingplan_booking', array('house_bl' => $bl));

    // 5. Insert detail
    $detailbooking = array(
        'booking_id' => $booking_code,
        'package_type' => $data['package_type'],
        'quantity' => $data['quantity'],
        'volume' => $data['volume'],
        'weight' => $data['weight'],
        'netto' => $data['netto'],
        'shipper_name' => $data['shipper_name'],
        'shipper_address' => $data['shipper_address'],
        'consignee_name' => $data['consignee_name'],
        'consignee' => $data['consignee'],
        'notify_name' => $data['notify_name'],
        'notify' => $data['notify'],
        'shipping_mark' => $data['shipping_mark'],
        'marking' => $data['marking'],
        'description_print' => $data['description_print'],
        'description_of_goods' => $data['desc_of_goods'],
        'cp_name' => $data['cp_name'],
        'cp_email' => $data['cp_email'],
		'do_number'            => $data['do_number'],
        'wh_arrival'           => $this->_normalize_datetime($data['wh_arrival'] ?? ''),
        'created_at' => date("Y-m-d H:i:s"),
        'region_id' => $this->session->userdata('region_id'),
        'shipper_note' => $data['shipper_note'],
    );
    $this->dbx->insert('trnstuffingplan_booking_detail', $detailbooking);
    $saveStatus = $this->dbx->affected_rows();   // dibaca sekarang: query coload di bawah mengubah affected_rows

    // Coload antar cabang: buat BC di cabang tujuan (shipper = Gateway cabang ini, linked BC = BC ini)
    $branchChild = null;
    if ($adoptChild !== null) {
        $adoptError  = $this->_insert_adopt_link($booking_code, $adoptChild);
        $branchChild = $adoptError === null
            ? array('booking_code' => $adoptChild['booking_code'], 'house_bl' => $adoptChild['house_bl'])
            : $adoptError;
    } elseif ($coloadTarget !== null) {
        $branchChild = $this->_create_branch_child($booking_code, $coloadTarget, $branchAccount);
    }
    if ($coloadTarget !== null) {
        if (is_string($branchChild)) {
            $this->dbx->where('booking_id', $booking_code)->delete('trnstuffingplan_booking_detail');
            if (!empty($guard['link']) && $this->dbx->table_exists('trnbooking_link')) {
                $this->dbx->where('booking_code', $booking_code)->delete('trnbooking_link');
            }
            $this->dbx->where('booking_code', $booking_code)->delete('trnstuffingplan_booking');
            return array('status' => 0, 'code' => 'BRANCH_COLOAD_FAILED', 'message' =>
                'BC tidak disimpan: booking coload di cabang ' . $coloadTarget . ' gagal dibuat. ' . $branchChild);
        }
        // Satu nomor HBL: BC ini memakai HBL cabang coloader
        $sharedHbl = $this->_share_child_hbl($booking_code, $branchChild['booking_code']);
        if ($sharedHbl !== null) {
            $bl = $sharedHbl;
        }
    }
	
	// ========================================================
    // TIMELINE: BOOKING TERJADI SEBELUM MASTER/HOUSE JOB DIBUAT
    // ========================================================
    $this->load->model('Timeline_model');

    $cargo_type_val = strtoupper(trim((string)($data['cargo_type'] ?? 'GENCO')));
    $route_type_val = strtoupper(trim((string)($data['route_type'] ?? 'DIRECT')));
    $booking_type_val = strtoupper(trim((string)($data['type_of_booking'] ?? 'EXPORT')));
    $schedule_source = (strpos($booking_type_val, 'IMPORT') !== FALSE ? 'IMPORT_' : 'EXPORT_').
        (strpos($route_type_val, 'VIA') !== FALSE ? 'VIA' : 'DIRECT');

    $timeline_result = $this->Timeline_model->record_timeline_event(
        'BOOKING',
        $booking_code,
        'BOOKING_RECEIVED',
        date('Y-m-d H:i:s'),
        NULL,
        'Pendaftaran booking masuk (Incoterm: '.($data['incoterm'] ?? '-').
        ', Tipe: '.$cargo_type_val.')',
        NULL,
        NULL,
        $data['origin_city'] ?? NULL,
        array(
            'region_id' => $this->session->userdata('region_id'),
            'schedule_id' => (int)($data['schedule_id'] ?? 0),
            'schedule_type' => $schedule_source,
            'booking_code' => $booking_code
        )
    );

    if (empty($timeline_result['status'])) {
        log_message(
            'error',
            'BOOKING_TIMELINE_FAILED booking_code='.$booking_code.
            ' result='.json_encode($timeline_result)
        );
    }
    // ========================================================

    return array(
		'status'         => $saveStatus,
		'booking_id'     => $idbooking,
		'house_bl'       => $bl,
		'si_number'      => $data['si_number'] ?? null,
		'booking_code'   => $booking_code,
		'vessel'         => $data['vessel'] ?? null,
		'voyage'         => $data['voyage'] ?? null,
		'pol'            => $data['pol_code'] ?? null,
		'pod'            => $data['pod_code'] ?? null,
		'container_type' => $data['container_type'] ?? null,
		'carrier'        => $data['carrier'] ?? null,
		'dg_class' => $data['dg_class'],
		'hs_code'  => $data['hs_code'],
		'linked_bc'      => !empty($guard['link']) ? $guard['link']['bc_no'] : null,
		'coload_booking_code' => $branchChild ? $branchChild['booking_code'] : null,
		'coload_house_bl'     => $branchChild ? $branchChild['house_bl'] : null,
		'coload_region'       => $branchChild ? $coloadTarget : null,
	);
}
   
   function edit_booking($data)
{
    // --- Pastikan kolom baru sudah ada di database yang AKTIF (master / periode) ---
    $missing = $this->_missing_detail_columns();
    if (!empty($missing)) {
        return array(
            'status'  => 0,
            'message' => 'Kolom belum ada di tabel trnstuffingplan_booking_detail pada database "' .
                         $this->dbx->database . '": ' . implode(', ', $missing) .
                         '. Jalankan migration di database tersebut.'
        );
    }

    // --- Tentukan booking_code yang benar sebagai kunci UPDATE ---
    // Sebelumnya kunci = $data['id'] (hidden #id = $_POST['id_booking']).
    // Jika isinya bukan booking_code (mis. id numerik), UPDATE kena 0 baris
    // tapi tetap dilaporkan sukses -> data tidak pernah tersimpan.
    $key = $this->_resolve_booking_key($data);
    if ($key === '') {
        return array('status' => 0, 'message' => 'Booking tidak ditemukan di database "' . $this->dbx->database . '". Data tidak disimpan.');
    }
    $data['id'] = $key;

    // Ambil data booking existing sebagai sumber nilai yang harus dipertahankan saat edit.
    $current = $this->dbx->get_where('trnstuffingplan_booking', array('booking_code' => $key))->row_array();

    // =====================================================
    // PRESERVE SCHEDULE ID
    // =====================================================
    // schedule_id dari form edit bisa kosong / 0 ketika toolbar schedule gagal
    // ter-load. Jangan biarkan kondisi tersebut memutus relasi booking dengan
    // schedule yang sebelumnya sudah valid. Schedule hanya diganti bila frontend
    // benar-benar mengirim ID baru yang > 0.
    $currentScheduleId = isset($current['schedule_id']) ? (int) $current['schedule_id'] : 0;
    $postedScheduleId  = isset($data['schedule_id']) ? (int) $data['schedule_id'] : 0;

    if ($postedScheduleId > 0) {
        $scheduleId = $postedScheduleId;
    } else {
        $scheduleId = $currentScheduleId;

        if ($currentScheduleId > 0) {
            log_message(
                'error',
                'EDIT_BOOKING_PRESERVE_SCHEDULE booking_code=' . $key .
                ' posted_schedule_id=' . ($data['schedule_id'] ?? 'EMPTY') .
                ' preserved_schedule_id=' . $currentScheduleId
            );
        }
    }

    // Coload antar cabang: pemilik booking = region_id booking (bukan cabang tujuan)
    list($coload, $coloadError, $coloadTarget) = $this->_branch_coload_fields($data, $current['region_id'] ?? $this->session->userdata('region_id'), $current);
    if ($coloadError !== null) {
        return array('status' => 0, 'message' => $coloadError);
    }
    if (array_key_exists('stuffing_region', $coload)) {
        $before = trim((string) ($current['stuffing_region'] ?? ''));
        $after  = trim((string) ($coload['stuffing_region'] ?? ''));
        if (strcasecmp($before, $after) !== 0) {
            if (!$this->_is_own_booking($current)) {
                return array('status' => 0, 'code' => 'BOOKING_OTHER_BRANCH', 'message' =>
                    'Booking ini milik cabang ' . $current['region_id'] . ' (titipan coload). ' .
                    'Stuffing Plan / Coloader hanya bisa diubah oleh cabang ' . $current['region_id'] . '.');
            }
            $job = $this->_stuffing_job_of($key, $current);
            if ($job !== null) {
                return array('status' => 0, 'message' =>
                    'Cabang stuffing tidak bisa diubah: booking sudah masuk job stuffing ' . $job .
                    '. Keluarkan dulu dari job tersebut, lalu ubah Stuffing Plan / Coloader.');
            }
        }
    }

    // Shipper coloader: hanya berlaku bila shipper diganti / BC asal dikirim (booking lama tidak diblok)
    $data['shipper_id'] = $this->_resolve_shipper_id($data, $current);
    $guard = $this->_coloader_guard_edit($data, $current);
    if (!empty($guard['error'])) {
        return $guard['error'];
    }

    // Coload antar cabang: BC di cabang tujuan dibuat / diteruskan / dibatalkan setelah BC ini tersimpan.
    // Penolakan (BC tujuan sudah masuk job, akun belum dipetakan) dicek SEKARANG, sebelum apa pun berubah.
    $branchPlan = $this->_branch_coload_plan($key, $current, $coloadTarget, array_key_exists('stuffing_plan', $data),
                                             strtoupper(trim((string) ($data['coload_existing_bc'] ?? ''))));
    if (!empty($branchPlan['error'])) {
        return array('status' => 0, 'code' => $branchPlan['code'], 'message' => $branchPlan['error']);
    }

    // HBL selalu memakai cabang PEMILIK booking (region_id booking), bukan cabang yang
    // sedang mengedit: titipan coload bisa dibuka dari cabang stuffing.
    $regional   = !empty($current['region_id']) ? $current['region_id'] : $this->session->userdata("region_id");
    $periode    = substr($this->session->userdata("db"), -2);
    $next_id    = $this->get_exist_id($data['booking_code']);
    $portTrans  = (!empty($data['trans_city']) && $data['trans_city'] !== "null") ? $data['trans_city'] : '';
    $q0         = $this->dbx->get_where('refregion', array('region_name' => $regional))->row()->region_id;
    // 1. Ambil data POD dari request frontend (pod_code / code_pod)
    $q3 = !empty($data['code_pod']) ? $data['code_pod'] : (!empty($data['pod_code']) ? $data['pod_code'] : '');

    // 2. BACKEND FAIL-SAFE: Jika dari frontend kosong, lakukan query ke database
    if (empty($q3) && !empty($data['destination_city'])) {
        $this->dbx->select('code');
        $this->dbx->from('tableName'); // TODO: Ganti 'tableName' dengan nama tabel Anda yang sebenarnya
        $this->dbx->where('name', $data['destination_city']);
        
        // Asumsi tipe data is_active adalah string/enum 'TRUE'. Jika boolean/tinyint, gunakan 1.
        $this->dbx->where('is_active', 'TRUE'); 
        
        $query_pod = $this->dbx->get()->row();

        // Jika datanya ditemukan, timpa $q3 dengan code dari database
        if ($query_pod) {
            $q3 = $query_pod->code;
        }
    }

    // 3. Jika sudah di-query tapi masih kosong juga, gagalkan proses insert
    if (empty($q3)) {
        return array(
            'status' => 0, 
            'message' => 'Error: Kode POD tidak ditemukan di sistem. Pastikan Port of Destination valid dan aktif.'
        );
    }

    $destination = strtoupper(trim($data['destination_city'] ?? ''));
    $prefix = null;

    if (empty($portTrans) || $portTrans === 'LOS ANGELES' || $portTrans === 'NEW YORK') {
        if ($destination === 'LOS ANGELES' || $destination === 'HOUSTON' || $destination === 'SEATTLE' || $destination === 'OAKLAND') {
            $prefix = 'LAX' . $periode;
        } elseif ($destination === 'NEW YORK' || $destination === 'CHICAGO' || $destination === 'ATLANTA' || $destination === 'MIAMI' || $destination === 'KANSAS CITY' || $destination === 'BALTIMORE') {
            $prefix = 'NYK' . $periode;
        }
    }

    if ($prefix !== null) {
        $this->dbx->trans_start();

        $sql = "
            SELECT MAX(CAST(RIGHT(house_bl, 5) AS UNSIGNED)) AS max_suffix
            FROM trnstuffingplan_booking
            WHERE house_bl REGEXP '^{$prefix}[0-9]{4}$'
            FOR UPDATE
        ";
        $row  = $this->dbx->query($sql)->row();
        $next = (int)($row->max_suffix ?? 0) + 1;

        if ($next > 99999) {
            throw new Exception('Range nomor untuk prefix ' . $prefix . ' sudah habis.');
        }

        $bl = $prefix . str_pad($next_id, 5, '0', STR_PAD_LEFT);

        $this->dbx->trans_complete();
    } else {
        $q5 = str_pad($next_id, 5, '0', STR_PAD_LEFT);
        $bl = substr($regional, 0, 1) . $q3 . "-" . $q0 . $periode . $q5;
    }

    $bookingData = array(
        'type_of_booking' => 'EXPORT',
        'booking_code'    => $data['booking_code'],
        'mode'            => $data['mode'],
        'movement_type'   => $data['movement_type'],
        'origin_country'  => 'INDONESIA',
        'si_number'       => $data['si_number'],
        'incoterm'        => $data['incoterm'],
        'origin_city'     => $data['origin_city'],
        'pol_code'        => $data['pol_code'],
        'pot_code'        => $data['pot_code'],
        'pod_code'        => $data['pod_code'],
        'trans_city'      => $portTrans,
        'destination_city'=> $data['destination_city'],
        'forwarder_name'  => $data['shipper_name'],
        'shipper_id'      => $data['shipper_id'],
        'sales'           => $data['sales'],
        'created_by'      => $data['shipper_id'],
        'consignee_id'    => $data['consignee_id'],
        'notify_id'       => $data['notify_id'],
        'status'          => 'finalize',
        'freight'         => $data['freight'],
        'number_of_bl'    => $data['number_of_bl'],
        'expect_etd'      => $data['expect_etd'],
        'eta'             => $data['eta'],
        'closing_date'    => $data['closing_doc'],
        'closing_cargo'   => $data['closing_car'],
        'vessel'          => $data['vessel'],
        'voyage'          => $data['voyage'],
        'warehouse'       => $data['warehouse'],
        'house_bl'        => $bl,
		'schedule_id'      => $scheduleId,
        'route_type' 	  => $data['route_type'],
		'cargo_type'      => $data['cargo_type'],
        'updated_at'      => date("Y-m-d H:i:s"),
    );
    $bookingData = array_merge($bookingData, $coload);
    if ($this->_has_shared_hbl()) {
        // HBL sendiri dulu; bila masih coload ke cabang lain, HBL coloader dipasang lagi di bawah
        $bookingData['house_bl_shared'] = 0;
    }

    $this->dbx->trans_start();

    $this->dbx->where('booking_code', $data['id']);
    $okHeader = $this->dbx->update('trnstuffingplan_booking', $bookingData);

    $detailbooking = array(
        'package_type'         => $data['package_type'],
        'quantity'             => $data['quantity'],
        'volume'               => $data['volume'],
        'weight'               => $data['weight'],
        'netto'                => $data['netto'],
        'shipper_name'         => $data['shipper_name'],
        'shipper_address'      => $data['shipper_address'],
        'consignee_name'       => $data['consignee_name'],
        'consignee'            => $data['consignee'],
        'notify_name'          => $data['notify_name'],
        'notify'               => $data['notify'],
        'shipping_mark'        => $data['shipping_mark'],
        'description_print'    => $data['description_print'],
        'marking'              => $data['long_mark'],
        'description_of_goods' => $data['long_description'],
        'cp_name'              => $data['cp_name'],
        'cp_email'             => $data['cp_email'],
        'do_number'            => $data['do_number'],
        'wh_arrival'           => $this->_normalize_datetime($data['wh_arrival'] ?? ''),
		'dg_class' => $data['dg_class'],
		'hs_code'  => $data['hs_code'],
        'updated_at'           => date("Y-m-d H:i:s"),
    );

    // Pengawasan BC Hanggar (AJU, PEB, NPE, Jalur, Karantina) - field kosong tidak ditimpa
    $detailbooking = array_merge($detailbooking, $this->_customs_doc_fields($data));

    $detailExists = $this->dbx->where('booking_id', $data['id'])
                              ->count_all_results('trnstuffingplan_booking_detail') > 0;

    if ($detailExists) {
        $this->dbx->where('booking_id', $data['id']);
        $okDetail = $this->dbx->update('trnstuffingplan_booking_detail', $detailbooking);
    } else {
        // Detail belum ada -> buat, supaya data tidak hilang diam-diam
        $detailbooking['booking_id'] = $data['id'];
        $detailbooking['created_at'] = date("Y-m-d H:i:s");
        $detailbooking['region_id']  = $this->session->userdata('region_id');
        $okDetail = $this->dbx->insert('trnstuffingplan_booking_detail', $detailbooking);
    }

    $dbError = $this->dbx->error();

    $this->dbx->trans_complete();

    $status = ($this->dbx->trans_status() && $okHeader && $okDetail) ? 1 : 0;

    if (!$status) {
        return array(
            'status'  => 0,
            'message' => 'Gagal menyimpan ke database "' . $this->dbx->database . '"' .
                         (!empty($dbError['message']) ? ': ' . $dbError['message'] : '.')
        );
    }

    // Coload antar cabang
    $branchResult = !empty($branchPlan['action']) ? $this->_apply_branch_coload_plan($key, $branchPlan) : array();
    if (!empty($branchResult['error'])) {
        return array('status' => 0, 'code' => 'BRANCH_COLOAD_FAILED', 'booking_code' => $key, 'message' =>
            'Booking tersimpan, tetapi ' . $branchResult['error'] . ' Simpan ulang booking ini.');
    }
    // Satu nomor HBL: masih coload ke cabang lain -> pakai HBL cabang coloader
    if (!empty($branchResult['booking_code'])) {
        $sharedHbl = $this->_share_child_hbl($key, $branchResult['booking_code']);
        if ($sharedHbl !== null) {
            $bl = $sharedHbl;
        }
    }
    // BC ini sendiri BC coloader: BC asal yang memakai HBL-nya ikut diperbarui
    $this->_refresh_parent_hbl($key);

    // BC asal coloader (manual): tambah / ganti / putus link sesuai hasil guard
    if (!empty($guard['action'])) {
        $linkError = $this->_apply_manual_link_action($key, $guard);
        if ($linkError !== null) {
            return array('status' => 0, 'code' => 'COLOADER_LINK_FAILED', 'message' =>
                'Booking tersimpan, tetapi BC asal gagal dicatat: ' . $linkError . ' Simpan ulang booking ini.');
        }
    }

    return array(
        'status'         => $status,
        'linked_bc'      => $guard['bc_no'] ?? null,
        'coload_booking_code' => $branchResult['booking_code'] ?? null,
        'coload_region'       => $branchResult['region'] ?? null,
        'coload_note'         => $branchResult['note'] ?? null,
        'booking_code'   => $data['booking_code'] ?? null,
        'house_bl'       => $bl,
        'si_number'      => $data['si_number'] ?? null,
        'vessel'         => $data['vessel'] ?? null,
        'voyage'         => $data['voyage'] ?? null,
        'pol'            => $data['pol_code'] ?? null,
        'pod'            => $data['pod_code'] ?? null,
        'shipper_id'     => $data['shipper_id'] ?? null,
        'schedule_id'    => $scheduleId,

        // optional kalau nanti form Anda punya
        'mbl'            => $data['mbl'] ?? null,
        'carrier'        => $data['carrier'] ?? null,
        'container_no'   => $data['container_no'] ?? ($data['container'] ?? null),
        'container_type' => $data['container_type'] ?? null,
    );
}
   
  /* =====================================================================
   * Coload antar cabang Gateway (diperlakukan seperti sister company)
   *   BC asal (mis. Surabaya, GTW-A) tetap: shipper asli, HBL Surabaya.
   *   BC tujuan (mis. Jakarta, GTW-B) dibuat otomatis: shipper = akun Gateway Surabaya,
   *   HBL Jakarta, consignee/notify/cargo/PEB disalin dari GTW-A,
   *   trnbooking_link(booking_code=GTW-B, linked_company='GW', linked_bc_no=GTW-A).
   *   Perubahan GTW-A diteruskan ke GTW-B selama GTW-B belum masuk job / gudang.
   * ===================================================================== */

  const BRANCH_COMPANY    = 'GW';
  const BRANCH_LINK_ACTOR = 'GATEWAY-BRANCH:';
  // BC cabang tujuan yang SUDAH ada sebelum aturan coload (dibuat manual dengan shipper Gateway <asal>)
  // lalu disambungkan ke BC asal: ticket WMS tetap milik BC tujuan, hanya PEB/NPE yang diteruskan,
  // dan membatalkan coload hanya memutus link (BC tujuan tidak dibatalkan).
  const BRANCH_ADOPT_ACTOR = 'GATEWAY-ADOPT:';

  /** Kolom header yang diteruskan dari BC asal ke BC cabang tujuan */
  private static $BRANCH_SYNC_HEADER = array(
      'type_of_booking', 'mode', 'movement_type', 'si_number', 'origin_country', 'incoterm', 'origin_city',
      'trans_city', 'pol_code', 'pot_code', 'pod_code', 'destination_city', 'consignee_id', 'notify_id',
      'freight', 'number_of_bl', 'expect_etd', 'eta', 'closing_date', 'closing_cargo', 'vessel', 'voyage',
      'warehouse', 'schedule_id', 'route_type', 'cargo_type', 'peb', 'file_other',
  );
  /** Kolom detail yang diteruskan (shipper & kontak TIDAK: di BC tujuan shipper = Gateway cabang asal) */
  private static $BRANCH_SYNC_DETAIL = array(
      'package_type', 'quantity', 'volume', 'weight', 'netto', 'dimension', 'container_type',
      'consignee_name', 'consignee', 'notify_name', 'notify', 'shipping_mark', 'marking',
      'description_print', 'description_of_goods', 'do_number', 'wh_arrival', 'dg_class', 'hs_code',
  );
  private static $BRANCH_SYNC_CUSTOMS = array(
      'aju_number', 'peb_number', 'peb_date', 'npe_number', 'npe_date', 'jalur', 'jalur_date',
      'karantina_status', 'karantina_number', 'karantina_date',
  );

  private function _has_link_table()
  {
      return $this->dbx->table_exists('trnbooking_link');
  }

  /** BC cabang tujuan (link GW aktif ke BC ini), atau null. */
  public function branch_child_of($bookingCode)
  {
      if (!$this->_has_link_table() || trim((string) $bookingCode) === '') {
          return null;
      }
      $row = $this->dbx->select('b.*, l.id AS branch_link_id, l.created_by AS branch_link_by', false)
          ->from('trnbooking_link l')
          ->join('trnstuffingplan_booking b', 'b.booking_code = l.booking_code', 'inner')
          ->where('l.linked_company', self::BRANCH_COMPANY)
          ->where('l.linked_bc_no', $bookingCode)
          ->where('l.status', 'active')
          ->order_by('l.id', 'DESC')->limit(1)->get()->row_array();
      return $row ?: null;
  }

  /** BC cabang tujuan hasil "sambung" (sudah ada sebelum aturan coload)? */
  private function _is_adopted_child(array $child)
  {
      return strpos((string) ($child['branch_link_by'] ?? ''), self::BRANCH_ADOPT_ACTOR) === 0;
  }

  /**
   * Validasi BC cabang tujuan yang sudah ada untuk disambungkan ke BC asal.
   * @return array [booking row|null, error|null]
   */
  private function _branch_adopt_check($code, $targetRegion, $originRegion, $parentCode = null)
  {
      $code = strtoupper(trim((string) $code));
      if (!$this->_has_link_table()) {
          return array(null, 'Tabel trnbooking_link belum ada di database "' . $this->dbx->database . '".');
      }
      $b = $this->dbx->get_where('trnstuffingplan_booking', array('booking_code' => $code))->row_array();
      if (!$b) {
          return array(null, 'BC ' . $code . ' tidak ditemukan di database "' . $this->dbx->database . '".');
      }
      if ($parentCode !== null && $code === $parentCode) {
          return array(null, 'BC tujuan tidak boleh sama dengan BC ini.');
      }
      if (strcasecmp((string) $b['region_id'], (string) $targetRegion) !== 0) {
          return array(null, 'BC ' . $code . ' milik cabang ' . $b['region_id'] . ', bukan ' . $targetRegion . '.');
      }
      if (strtolower((string) $b['status']) === 'cancelled') {
          return array(null, 'BC ' . $code . ' sudah dibatalkan.');
      }
      $linked = $this->dbx->select('linked_bc_no')->where('booking_code', $code)->where('linked_company', self::BRANCH_COMPANY)
                          ->where('status', 'active')->limit(1)->get('trnbooking_link')->row_array();
      if ($linked) {
          return array(null, 'BC ' . $code . ' sudah tersambung ke BC ' . $linked['linked_bc_no'] . '.');
      }
      // Shipper BC tujuan harus akun Gateway cabang (bukan shipper asli)
      $this->load->library('Branch_coload');
      $mapped = $this->branch_coload->shipper_account($targetRegion, $originRegion);
      $ok = ((int) $b['shipper_id'] === $mapped);
      if (!$ok) {
          $info = $this->_coloader_info($b['shipper_id']);
          $ok = $info && $info['mode'] === 'branch';
      }
      if (!$ok) {
          return array(null, 'Shipper BC ' . $code . ' (' . $b['forwarder_name'] . ') bukan akun Gateway cabang, jadi bukan BC titipan ' .
                             $originRegion . '. Periksa lagi nomor BC-nya.');
      }
      return array($b, null);
  }

  /** Sambungkan BC tujuan yang sudah ada ke BC asal. @return string|null error */
  private function _insert_adopt_link($parentCode, array $child)
  {
      $p = $this->dbx->select('house_bl')->get_where('trnstuffingplan_booking', array('booking_code' => $parentCode))->row_array();
      try {
          $ok = $this->dbx->insert('trnbooking_link', array(
              'booking_code'   => $child['booking_code'],
              'linked_company' => self::BRANCH_COMPANY,
              'linked_bc_no'   => $parentCode,
              'linked_hbl'     => $p && (string) $p['house_bl'] !== '' ? $p['house_bl'] : null,
              'link_type'      => 'COLOAD',
              'status'         => 'active',
              'created_by'     => substr(self::BRANCH_ADOPT_ACTOR . (string) $this->session->userdata('fullname'), 0, 100),
              'created_at'     => date('Y-m-d H:i:s'),
          ));
          if (!$ok) {
              return 'Database: ' . ($this->dbx->error()['message'] ?? 'link gagal disimpan');
          }
      } catch (Exception $e) {
          return 'Database: ' . $e->getMessage();
      }
      return null;
  }

  /** Lepas BC tujuan: hasil sambung -> hanya link diputus; selain itu BC tujuan dibatalkan. */
  private function _release_branch_child(array $child, $reason)
  {
      if ($this->_is_adopted_child($child)) {
          $this->dbx->where('id', $child['branch_link_id'])->where('status', 'active')
                    ->update('trnbooking_link', array(
                        'status'        => 'cancelled',
                        'cancelled_by'  => $this->_branch_actor(),
                        'cancelled_at'  => date('Y-m-d H:i:s'),
                        'cancel_reason' => substr($reason . ' (sambungan dilepas, BC tujuan tetap)', 0, 255),
                    ));
          return 'Sambungan ke booking ' . $child['booking_code'] . ' di cabang ' . $child['region_id'] . ' dilepas (booking itu tetap ada).';
      }
      $this->_cancel_branch_child($child, $reason);
      return 'Booking ' . $child['booking_code'] . ' di cabang ' . $child['region_id'] . ' dibatalkan.';
  }

  /** Kolom HBL bersama (gw_104_hbl_coloader.sql) sudah ada? */
  private function _has_shared_hbl()
  {
      static $has = array();
      $k = $this->dbx->database;
      if (!isset($has[$k])) {
          // Kolom ada DAN unique lama di house_bl sudah diganti (migrasi gw_104 selesai di DB ini)
          $has[$k] = in_array('house_bl_shared', $this->dbx->list_fields('trnstuffingplan_booking'), true)
              && $this->dbx->query("SHOW INDEX FROM trnstuffingplan_booking WHERE Key_name = 'unique_house_bl'")->num_rows() === 0;
      }
      return $has[$k];
  }

  /**
   * Satu nomor HBL untuk coload antar cabang: BC asal memakai HBL BC cabang coloader.
   * @return string|null HBL yang dipakai BC asal (null bila kolom gw_104 belum ada)
   */
  private function _share_child_hbl($parentCode, $childCode)
  {
      if (!$this->_has_shared_hbl()) {
          return null;
      }
      $c = $this->dbx->select('house_bl')->get_where('trnstuffingplan_booking', array('booking_code' => $childCode))->row_array();
      if (!$c || trim((string) $c['house_bl']) === '') {
          return null;
      }
      $this->dbx->where('booking_code', $parentCode)
                ->update('trnstuffingplan_booking', array('house_bl' => $c['house_bl'], 'house_bl_shared' => 1));
      return $c['house_bl'];
  }

  /** BC coloader berubah HBL (diedit cabangnya sendiri): BC asal yang memakai HBL-nya ikut. */
  private function _refresh_parent_hbl($childCode)
  {
      $parent = $this->branch_parent_of($childCode);
      if ($parent && $this->_has_shared_hbl()) {
          $row = $this->dbx->select('house_bl_shared')->get_where('trnstuffingplan_booking', array('booking_code' => $parent['booking_code']))->row_array();
          if ($row && (int) $row['house_bl_shared'] === 1) {
              $this->_share_child_hbl($parent['booking_code'], $childCode);
          }
      }
  }

  /** BC asal (cabang lain) untuk BC hasil coload antar cabang, atau null. */
  public function branch_parent_of($bookingCode)
  {
      if (!$this->_has_link_table() || trim((string) $bookingCode) === '') {
          return null;
      }
      $row = $this->dbx->select('l.linked_bc_no AS booking_code, p.region_id, p.house_bl, p.status', false)
          ->from('trnbooking_link l')
          ->join('trnstuffingplan_booking p', 'p.booking_code = l.linked_bc_no', 'left')
          ->where('l.booking_code', $bookingCode)
          ->where('l.linked_company', self::BRANCH_COMPANY)
          ->where('l.status', 'active')
          ->limit(1)->get()->row_array();
      return $row ?: null;
  }

  /** Alasan BC cabang tujuan sudah tidak boleh diubah/dibatalkan dari cabang asal, atau null. */
  private function _branch_child_lock(array $child)
  {
      if (strtolower(trim((string) ($child['status'] ?? ''))) === 'booked') {
          return 'sudah masuk job' . (trim((string) ($child['job_no'] ?? '')) !== '' ? ' ' . trim($child['job_no']) : '');
      }
      $job = $this->_stuffing_job_of($child['booking_code'], $child);
      if ($job !== null) {
          return 'sudah masuk job stuffing ' . $job;
      }
      if (trim((string) ($child['warehouse_status'] ?? '')) !== '') {
          return 'sudah diproses gudang (' . trim($child['warehouse_status']) . ')';
      }
      $rd = (string) ($child['received_date'] ?? '');
      if ($rd !== '' && strpos($rd, '0000-00-00') !== 0) {
          return 'cargo-nya sudah diterima gudang';
      }
      if ((int) ($child['shipped_on_board'] ?? 0) === 1) {
          return 'sudah shipped on board';
      }
      return null;
  }

  /** Akun shipper "Gateway <asal>" di cabang tujuan. @return array [mstshipper row|null, error|null] */
  private function _branch_account($targetRegion, $originRegion)
  {
      $this->load->library('Branch_coload');
      $db = $this->dbx->database;
      if (!$this->_has_link_table() || !$this->dbx->table_exists('mstcoloader_company')
          || !$this->dbx->get_where('mstcoloader_company', array('company_code' => self::BRANCH_COMPANY))->row_array()) {
          return array(null, 'Coload antar cabang membutuhkan tabel trnbooking_link dan coloader GW di database "' . $db .
              '". Jalankan gw_103_coloader_guard.sql (dan 001_booking_link.sql bila trnbooking_link belum ada).');
      }
      $id = $this->branch_coload->shipper_account($targetRegion, $originRegion);
      if ($id <= 0) {
          return array(null, 'Akun shipper "Gateway ' . $originRegion . '" untuk cabang ' . $targetRegion .
              ' belum dipetakan (config/branch_coload.php, branch_shipper_accounts). Hubungi IT.');
      }
      $acc = $this->dbx->get_where('mstshipper', array('id_shipper' => $id))->row_array();
      if (!$acc) {
          return array(null, 'Akun shipper ' . $id . ' (Gateway ' . $originRegion . ' di ' . $targetRegion .
              ') tidak ada di mstshipper database "' . $db . '".');
      }
      return array($acc, null);
  }

  /** HBL dengan aturan yang sama seperti save_booking, untuk region & id tertentu. */
  private function _build_house_bl($id, $regionName, array $header)
  {
      $periode   = substr((string) $this->session->userdata('db'), -2);
      $portTrans = trim((string) ($header['trans_city'] ?? ''));
      $dest      = strtoupper(trim((string) ($header['destination_city'] ?? '')));
      if (empty($portTrans) || $portTrans === 'LOS ANGELES' || $portTrans === 'NEW YORK') {
          if (in_array($dest, array('LOS ANGELES', 'HOUSTON', 'SEATTLE', 'OAKLAND'), true)) {
              return 'LAX' . $periode . str_pad($id, 5, '0', STR_PAD_LEFT);
          }
          if (in_array($dest, array('NEW YORK', 'CHICAGO', 'ATLANTA', 'MIAMI', 'KANSAS CITY', 'BALTIMORE'), true)) {
              return 'NYK' . $periode . str_pad($id, 5, '0', STR_PAD_LEFT);
          }
      }
      $pod = trim((string) ($header['pod_code'] ?? ''));
      if ($pod === '' && $dest !== '') {
          $q = $this->dbx->select('code')->where('name', $header['destination_city'])->limit(1)->get('tableName');
          $r = $q ? $q->row() : null;
          $pod = $r ? $r->code : '';
      }
      $reg  = $this->dbx->get_where('refregion', array('region_name' => $regionName))->row();
      $code = $reg ? $reg->region_id : '';
      return substr($regionName, 0, 1) . $pod . '-' . $code . $periode . str_pad($id, 5, '0', STR_PAD_LEFT);
  }

  private function _branch_actor()
  {
      return substr(self::BRANCH_LINK_ACTOR . (string) $this->session->userdata('fullname'), 0, 100);
  }

  /**
   * Buat BC di cabang tujuan dari BC asal yang SUDAH tersimpan.
   * @return array ['booking_code','house_bl'] | string pesan error
   */
  private function _create_branch_child($parentCode, $targetRegion, array $account)
  {
      $p  = $this->dbx->get_where('trnstuffingplan_booking', array('booking_code' => $parentCode))->row_array();
      $pd = $this->dbx->get_where('trnstuffingplan_booking_detail', array('booking_id' => $parentCode))->row_array();
      if (!$p) {
          return 'BC asal ' . $parentCode . ' tidak ditemukan.';
      }
      $origin = (string) $p['region_id'];
      $now    = date('Y-m-d H:i:s');
      $zero   = '0000-00-00 00:00:00';
      $code   = 'GTW-' . strtoupper(uniqid());

      // Header: salinan BC asal, lalu kolom milik cabang tujuan ditimpa
      $h = $p;
      unset($h['id'], $h['house_bl'], $h['house_bl_key']);   // house_bl_key = kolom generated (gw_104)
      if (array_key_exists('house_bl_shared', $h)) {
          $h['house_bl_shared'] = 0;
      }
      $over = array(
          'booking_code'     => $code,
          'region_id'        => $targetRegion,
          'shipper_id'       => (int) $account['id_shipper'],
          'created_by'       => (int) $account['id_shipper'],
          'forwarder_name'   => $account['shipper_name'],
          // mstshipper.sales bisa berisi "482,90" sedangkan kolom booking angka: ambil angka pertama
          'sales'            => preg_match('/\d+/', (string) ($account['sales'] ?? ''), $sm) ? (int) $sm[0] : ($p['sales'] ?? ''),
          'status'           => 'finalize',
          'stuffing_plan'    => 'Consol',
          'coloader'         => '',
          'stuffing_region'  => null,
          'job_no'           => '',
          'warehouse_status' => '',
          'shipped_on_board' => 0,
          'received_date'    => $zero,
          'received_by'      => '',
          'stuffing_date'    => $zero,
          'gate_date'        => $zero,
          'pic_document'     => substr('COLOAD ' . strtoupper($origin) . ' - ' . (string) $this->session->userdata('fullname'), 0, 100),
          'created_at'       => $now,
          'updated_at'       => $now,
      );
      foreach ($over as $k => $v) {
          if (array_key_exists($k, $h) || in_array($k, array('booking_code', 'region_id', 'shipper_id', 'status'), true)) {
              $h[$k] = $v;
          }
      }

      // Detail: salinan BC asal; shipper = akun Gateway cabang asal, kontak = user cabang asal
      $d = $pd ?: array();
      unset($d['id']);
      $d['booking_id']      = $code;
      $d['region_id']       = $targetRegion;
      $d['shipper_name']    = $account['shipper_name'];
      $d['shipper_address'] = (string) ($account['shipper_address'] ?? '');
      if (array_key_exists('note', $d)) {
          $d['note'] = mb_substr($account['shipper_name'] . "\n" . (string) ($account['shipper_address'] ?? ''), 0, 200);
      }
      $d['cp_name']    = substr((string) $this->session->userdata('fullname'), 0, 30);
      $d['cp_email']   = (string) $this->session->userdata('email');
      $d['created_at'] = $now;
      if (array_key_exists('updated_at', $d)) {
          $d['updated_at'] = $now;
      }
      if (array_key_exists('shipper_note', $d)) {
          $d['shipper_note'] = 'COLOAD dari Gateway ' . $origin . ' - BC ' . $parentCode . ' / HBL ' . (string) $p['house_bl'];
      }

      $this->dbx->trans_begin();
      try {
          if (!$this->dbx->insert('trnstuffingplan_booking', $h)) {
              throw new Exception('header: ' . ($this->dbx->error()['message'] ?? ''));
          }
          $id = (int) $this->dbx->insert_id();
          $hbl = $this->_build_house_bl($id, $targetRegion, $h);
          $this->dbx->where('id', $id)->update('trnstuffingplan_booking', array('house_bl' => $hbl));
          if (!$this->dbx->insert('trnstuffingplan_booking_detail', $d)) {
              throw new Exception('detail: ' . ($this->dbx->error()['message'] ?? ''));
          }
          if (!$this->dbx->insert('trnbooking_link', array(
              'booking_code'   => $code,
              'linked_company' => self::BRANCH_COMPANY,
              'linked_bc_no'   => $parentCode,
              'linked_hbl'     => (string) $p['house_bl'] !== '' ? $p['house_bl'] : null,
              'link_type'      => 'COLOAD',
              'status'         => 'active',
              'created_by'     => $this->_branch_actor(),
              'created_at'     => $now,
          ))) {
              throw new Exception('link: ' . ($this->dbx->error()['message'] ?? ''));
          }
          if ($this->dbx->trans_status() === false) {
              throw new Exception((string) ($this->dbx->error()['message'] ?? 'transaksi gagal'));
          }
          $this->dbx->trans_commit();
      } catch (Exception $e) {
          $this->dbx->trans_rollback();
          log_message('error', 'BRANCH_COLOAD create child of ' . $parentCode . ': ' . $e->getMessage());
          return 'Database: ' . $e->getMessage();
      }
      return array('booking_code' => $code, 'house_bl' => $hbl);
  }

  /** Teruskan data BC asal ke BC cabang tujuan. $customsOnly = hanya PEB/NPE. */
  private function _sync_branch_child($parentCode, array $child, $customsOnly = false)
  {
      $p  = $this->dbx->get_where('trnstuffingplan_booking', array('booking_code' => $parentCode))->row_array();
      $pd = $this->dbx->get_where('trnstuffingplan_booking_detail', array('booking_id' => $parentCode))->row_array() ?: array();
      $cd = $this->dbx->get_where('trnstuffingplan_booking_detail', array('booking_id' => $child['booking_code']))->row_array() ?: array();
      if (!$p) {
          return;
      }
      $now = date('Y-m-d H:i:s');
      $detailCols = $customsOnly ? self::$BRANCH_SYNC_CUSTOMS : array_merge(self::$BRANCH_SYNC_DETAIL, self::$BRANCH_SYNC_CUSTOMS);
      $d = array();
      foreach ($detailCols as $c) {
          if (array_key_exists($c, $pd) && array_key_exists($c, $cd)) $d[$c] = $pd[$c];
      }
      if ($d) {
          if (array_key_exists('updated_at', $cd)) $d['updated_at'] = $now;
          $this->dbx->where('booking_id', $child['booking_code'])->update('trnstuffingplan_booking_detail', $d);
      }
      if ($customsOnly) {
          return;
      }
      $h = array();
      foreach (self::$BRANCH_SYNC_HEADER as $c) {
          if (array_key_exists($c, $p) && array_key_exists($c, $child)) $h[$c] = $p[$c];
      }
      $h['house_bl'] = $this->_build_house_bl((int) $child['id'], $child['region_id'], array_merge($child, $h));
      if (array_key_exists('updated_at', $child)) $h['updated_at'] = $now;
      $this->dbx->where('booking_code', $child['booking_code'])->update('trnstuffingplan_booking', $h);
  }

  /** Batalkan BC cabang tujuan (status cancelled + link diputus), tanpa transaksi sendiri. */
  private function _cancel_branch_child(array $child, $reason)
  {
      $now = date('Y-m-d H:i:s');
      $this->dbx->where('booking_code', $child['booking_code'])
                ->update('trnstuffingplan_booking', array('status' => 'cancelled', 'updated_at' => $now));
      $this->dbx->where('booking_code', $child['booking_code'])->where('status', 'active')
                ->update('trnbooking_link', array(
                    'status'        => 'cancelled',
                    'cancelled_by'  => $this->_branch_actor(),
                    'cancelled_at'  => $now,
                    'cancel_reason' => substr($reason, 0, 255),
                ));
  }

  /**
   * Rencana coload antar cabang untuk edit BC asal (dicek SEBELUM BC asal diubah).
   * @return array kosong | ['error','code'] | ['action' => sync|cancel|create|replace, 'child', 'target', 'account']
   */
  private function _branch_coload_plan($key, array $current, $target, $planPosted, $adoptBc = '')
  {
      $child = $this->branch_child_of($key);
      if (!$child && $target === null) {
          return array();
      }
      $childRegion = $child ? (string) $child['region_id'] : null;
      $desired     = $planPosted ? $target : $childRegion;

      if ($child && $desired !== null && strcasecmp($desired, $childRegion) === 0) {
          return array('action' => 'sync', 'child' => $child);
      }
      if ($child) {
          // BC tujuan hasil sambung: melepas sambungan tidak mengubah BC tujuan, jadi tidak dikunci
          $lock = $this->_is_adopted_child($child) ? null : $this->_branch_child_lock($child);
          if ($lock !== null) {
              return array('code' => 'BRANCH_COLOAD_LOCKED', 'error' =>
                  'Stuffing Plan / Coloader tidak bisa diubah: booking ' . $child['booking_code'] . ' di cabang ' . $childRegion .
                  ' ' . $lock . '. Minta cabang ' . $childRegion . ' mengeluarkannya dulu.');
          }
          if ($desired === null) {
              return array('action' => 'cancel', 'child' => $child);
          }
      }
      $origin = (string) ($current['region_id'] ?? $this->session->userdata('region_id'));
      if ($adoptBc !== '') {
          list($adopt, $err) = $this->_branch_adopt_check($adoptBc, $desired, $origin, $key);
          if ($err !== null) {
              return array('code' => 'BRANCH_ADOPT_INVALID', 'error' => $err);
          }
          return array('action' => $child ? 'replace' : 'create', 'child' => $child, 'target' => $desired, 'adopt' => $adopt);
      }
      list($account, $err) = $this->_branch_account($desired, $origin);
      if ($err !== null) {
          return array('code' => 'BRANCH_COLOAD_NOT_READY', 'error' => $err);
      }
      return array('action' => $child ? 'replace' : 'create', 'child' => $child, 'target' => $desired, 'account' => $account);
  }

  /** Jalankan rencana setelah BC asal tersimpan. @return array ['booking_code','region','note'] | ['error'] */
  private function _apply_branch_coload_plan($key, array $plan)
  {
      switch ($plan['action']) {
          case 'sync':
              if ($this->_is_adopted_child($plan['child'])) {
                  // BC tujuan hasil sambung: datanya tetap milik cabang tujuan, hanya PEB/NPE yang diteruskan
                  $this->_sync_branch_child($key, $plan['child'], true);
                  return array('booking_code' => $plan['child']['booking_code'], 'region' => $plan['child']['region_id']);
              }
              $lock = $this->_branch_child_lock($plan['child']);
              if ($lock !== null) {
                  // Hanya PEB/NPE yang masih diteruskan
                  $this->_sync_branch_child($key, $plan['child'], true);
                  return array('booking_code' => $plan['child']['booking_code'], 'region' => $plan['child']['region_id'],
                      'note' => 'Booking ' . $plan['child']['booking_code'] . ' di cabang ' . $plan['child']['region_id'] . ' ' . $lock .
                                ': perubahan cargo/jadwal TIDAK diteruskan (PEB/NPE tetap diteruskan). Informasikan ke cabang ' .
                                $plan['child']['region_id'] . '.');
              }
              $this->_sync_branch_child($key, $plan['child']);
              return array('booking_code' => $plan['child']['booking_code'], 'region' => $plan['child']['region_id']);

          case 'cancel':
              return array('note' => $this->_release_branch_child($plan['child'],
                  'Stuffing Plan BC ' . $key . ' diubah dari Gateway ' . $this->session->userdata('region_id')));

          case 'replace':
              $this->_release_branch_child($plan['child'], 'Coload BC ' . $key . ' dipindah ke cabang ' . $plan['target']);
              // lanjut buat / sambung yang baru
          case 'create':
              if (!empty($plan['adopt'])) {
                  $err = $this->_insert_adopt_link($key, $plan['adopt']);
                  if ($err !== null) {
                      return array('error' => 'sambungan ke ' . $plan['adopt']['booking_code'] . ' gagal (' . $err . ').');
                  }
                  return array('booking_code' => $plan['adopt']['booking_code'], 'region' => $plan['target']);
              }
              $res = $this->_create_branch_child($key, $plan['target'], $plan['account']);
              if (is_string($res)) {
                  return array('error' => 'booking coload di cabang ' . $plan['target'] . ' gagal dibuat (' . $res . ').');
              }
              return array('booking_code' => $res['booking_code'], 'region' => $plan['target']);
      }
      return array();
  }

  /* =====================================================================
   * Guard shipper coloader (lihat libraries/Coloader_guard.php)
   *   auto   : Osline 4000, Winfast 4170, akun HNS -> BC manual ditolak
   *   branch : akun cabang Gateway                -> BC manual ditolak
   *   manual : akun coloader lain                 -> wajib BC asal, dicatat di trnbooking_link
   * Berlaku untuk BC baru dan saat shipper diganti. Booking lama tidak diblok.
   * ===================================================================== */

  /** Penanda link yang dibuat dari form Gateway (bukan dari API coloader) */
  const MANUAL_LINK_ACTOR = 'GATEWAY-UI:';

  private function _coloader_info($shipperId)
  {
      if ((int) $shipperId <= 0) {
          return null;
      }
      $this->load->library('coloader_guard');
      return $this->coloader_guard->classify($this->dbx, (int) $shipperId);
  }

  /** shipper_id dari hidden field; bila kosong dicari dari nama shipper (harus tepat satu). */
  private function _resolve_shipper_id($data)
  {
      $posted = isset($data['shipper_id']) ? trim((string) $data['shipper_id']) : '';
      if ((int) $posted > 0) {
          return (int) $posted;
      }
      $name = trim((string) ($data['shipper_name'] ?? ''));
      if ($name === '' || !$this->dbx->table_exists('mstshipper')) {
          return $posted;
      }
      $rows = $this->dbx->select('id_shipper, region_id')->where('shipper_name', $name)
                        ->limit(20)->get('mstshipper')->result_array();
      if (count($rows) > 1) {
          $region = (string) $this->session->userdata('region_id');
          $rows = array_values(array_filter($rows, function ($r) use ($region) {
              return strcasecmp((string) $r['region_id'], $region) === 0;
          }));
      }
      return count($rows) === 1 ? (int) $rows[0]['id_shipper'] : $posted;
  }

  private function _coloader_error($code, $message, $info = null)
  {
      $out = array('status' => 0, 'code' => $code, 'message' => $message);
      if ($info) {
          $out['coloader'] = array(
              'mode'         => $info['mode'],
              'company_code' => $info['company_code'],
              'company_name' => $info['company_name'],
              'bc_prefix'    => $info['bc_prefix'],
          );
      }
      return $out;
  }

  /** Booking yang memakai BC asal ini (link aktif), atau null. */
  private function _link_owner($companyCode, $bcNo)
  {
      if (!$this->dbx->table_exists('trnbooking_link')) {
          return null;
      }
      $row = $this->dbx->select('booking_code')
                       ->where('linked_company', $companyCode)
                       ->where('linked_bc_no', $bcNo)
                       ->where('status', 'active')
                       ->limit(1)->get('trnbooking_link')->row_array();
      return $row ? $row['booking_code'] : null;
  }

  /** Validasi BC asal dari form. @return array ['link'=>...] atau ['error'=>...] */
  private function _manual_link_from_post($info, $data, $exceptBooking = null)
  {
      $bc = strtoupper(preg_replace('/\s+/', '', (string) ($data['coloader_bc'] ?? '')));
      $name = $info['company_name'];
      $eg   = $info['bc_prefix'] !== '' ? ' (' . $info['bc_prefix'] . '...)' : '';

      if ($bc === '') {
          return array('error' => $this->_coloader_error('COLOADER_BC_REQUIRED',
              'Shipper ini coloader ' . $name . '. BC asal' . $eg . ' wajib diisi: salin nomor BC yang tertempel di cargo.', $info));
      }
      if (!$this->coloader_guard->bc_matches($info, $bc)) {
          return array('error' => $this->_coloader_error('COLOADER_BC_FORMAT',
              'BC asal "' . $bc . '" tidak sesuai format BC ' . $name . $eg . '. Periksa lagi nomornya.', $info));
      }
      $owner = $this->_link_owner($info['company_code'], $bc);
      if ($owner !== null && $owner !== $exceptBooking) {
          return array('error' => $this->_coloader_error('COLOADER_BC_USED',
              'BC asal ' . $bc . ' sudah dipakai booking ' . $owner . '. Pakai booking tersebut, jangan buat BC baru.', $info));
      }
      return array('link' => array(
          'company_code' => $info['company_code'],
          'company_name' => $name,
          'bc_no'        => $bc,
      ));
  }

  /** BC baru. @return array kosong | ['link'=>...] | ['error'=>...] */
  private function _coloader_guard_new($data)
  {
      $info = $this->_coloader_info($data['shipper_id']);
      if (!$info) {
          return array();
      }
      if ($info['mode'] !== 'manual') {
          return array('error' => $this->_coloader_error('COLOADER_' . strtoupper($info['mode']), $info['message'], $info));
      }
      return $this->_manual_link_from_post($info, $data);
  }

  /**
   * Edit BC. @return array kosong | ['error'=>...] | ['action'=>add|replace|cancel, 'link', 'old', 'bc_no']
   */
  private function _coloader_guard_edit($data, $current)
  {
      $key  = (string) ($current['booking_code'] ?? '');
      $link = $key !== '' ? $this->_active_coload_link($key) : null;

      // Booking dari API coloader (Osline/Winfast/...): dikelola sistem coloader, jangan disentuh
      if ($link && !$this->_is_manual_link($link)) {
          return array();
      }

      $oldId   = (int) ($current['shipper_id'] ?? 0);
      $newId   = (int) $data['shipper_id'];
      $changed = $newId > 0 && $newId !== $oldId;
      $info    = $this->_coloader_info($newId);
      $postBc  = trim((string) ($data['coloader_bc'] ?? ''));

      if (!$info) {
          // Ganti ke shipper biasa: BC asal lama tidak berlaku lagi
          return ($link && $changed) ? array('action' => 'cancel', 'old' => $link) : array();
      }
      if ($info['mode'] !== 'manual') {
          return $changed
              ? array('error' => $this->_coloader_error('COLOADER_' . strtoupper($info['mode']), $info['message'], $info))
              : array();   // booking lama dengan shipper ini tetap boleh diedit
      }

      // mode manual
      if (!$changed) {
          if ($link && !array_key_exists('coloader_bc', $data)) {
              return array('bc_no' => $link['linked_bc_no']);       // pemanggil lama: link dipertahankan
          }
          if (!$link && $postBc === '') {
              return array();                                         // booking lama tanpa BC asal: tidak diblok
          }
      }

      $r = $this->_manual_link_from_post($info, $data, $key);
      if (!empty($r['error'])) {
          return $r;
      }
      $new = $r['link'];
      if ($link && $link['company_code'] === $new['company_code'] && strcasecmp($link['linked_bc_no'], $new['bc_no']) === 0) {
          return array('bc_no' => $new['bc_no']);
      }
      return array('action' => $link ? 'replace' : 'add', 'link' => $new, 'old' => $link, 'bc_no' => $new['bc_no']);
  }

  private function _is_manual_link($link)
  {
      return strpos((string) ($link['created_by'] ?? ''), self::MANUAL_LINK_ACTOR) === 0;
  }

  private function _link_actor()
  {
      return substr(self::MANUAL_LINK_ACTOR . (string) $this->session->userdata('fullname'), 0, 100);
  }

  private function _link_row($bookingCode, $link)
  {
      return array(
          'booking_code'   => $bookingCode,
          'linked_company' => $link['company_code'],
          'linked_bc_no'   => $link['bc_no'],
          'link_type'      => 'COLOAD',
          'status'         => 'active',
          'created_by'     => $this->_link_actor(),
          'created_at'     => date('Y-m-d H:i:s'),
      );
  }

  private function _link_db_message($e)
  {
      $msg = is_string($e) ? $e : $e->getMessage();
      if (stripos($msg, 'Duplicate') !== false) {
          return 'BC asal sudah dipakai booking lain.';
      }
      return $msg;
  }

  /** @return string|null pesan error */
  private function _insert_manual_link($bookingCode, $link)
  {
      if (!$this->dbx->table_exists('trnbooking_link')) {
          return null;   // tabel link belum dipasang: BC asal tidak dicatat
      }
      try {
          if (!$this->dbx->insert('trnbooking_link', $this->_link_row($bookingCode, $link))) {
              $err = $this->dbx->error();
              return $this->_link_db_message((string) ($err['message'] ?? 'insert trnbooking_link gagal'));
          }
      } catch (Exception $e) {
          return $this->_link_db_message($e);
      }
      return null;
  }

  /** @return string|null pesan error */
  private function _apply_manual_link_action($bookingCode, $guard)
  {
      if (!$this->dbx->table_exists('trnbooking_link')) {
          return null;
      }
      $this->dbx->trans_begin();
      try {
          if (!empty($guard['old']['id'])) {
              $this->dbx->where('id', $guard['old']['id'])->where('status', 'active')->update('trnbooking_link', array(
                  'status'        => 'cancelled',
                  'cancelled_by'  => $this->_link_actor(),
                  'cancelled_at'  => date('Y-m-d H:i:s'),
                  'cancel_reason' => $guard['action'] === 'cancel' ? 'Shipper diganti dari Gateway' : 'BC asal diganti dari Gateway',
              ));
          }
          if ($guard['action'] !== 'cancel') {
              $this->dbx->insert('trnbooking_link', $this->_link_row($bookingCode, $guard['link']));
          }
          if ($this->dbx->trans_status() === false) {
              $err = $this->dbx->error();
              throw new Exception((string) ($err['message'] ?? 'trnbooking_link gagal disimpan'));
          }
          $this->dbx->trans_commit();
      } catch (Exception $e) {
          $this->dbx->trans_rollback();
          return $this->_link_db_message($e);
      }
      return null;
  }

  /**
   * Kolom coload dari form (stuffing_plan, coloader, stuffing_region) + cabang tujuan coload.
   * Bila form tidak mengirim stuffing_plan (pemanggil lama), kolom tidak disentuh.
   *
   * Coload ke cabang Gateway lain TIDAK lagi mengisi stuffing_region: cabang tujuan mendapat
   * BC sendiri (lihat _branch_coload_plan). Booking cara lama (stuffing_region sudah terisi)
   * yang tetap coload ke cabang yang sama dibiarkan berjalan dengan cara lama.
   *
   * @return array [array $fields, string|null $error, string|null $targetRegion]
   */
  private function _branch_coload_fields($data, $ownRegion, $current = null)
  {
      if (!array_key_exists('stuffing_plan', $data)) {
          return array(array(), null, null);
      }
      $this->load->library('Branch_coload');
      list($fields, $error) = $this->branch_coload->resolve($data['stuffing_plan'], $data['coloader'] ?? '', $ownRegion);
      if ($error !== null) {
          return array(array(), $error, null);
      }
      $target = isset($fields['target_region']) ? $fields['target_region'] : null;
      unset($fields['target_region']);

      $hasColumn = in_array('stuffing_region', $this->dbx->list_fields('trnstuffingplan_booking'), true);
      $oldRegion = $current ? trim((string) ($current['stuffing_region'] ?? '')) : '';
      if ($hasColumn && $oldRegion !== '' && $target !== null && strcasecmp($oldRegion, $target) === 0) {
          // Cara lama yang masih berjalan: satu booking, di-stuffing cabang tujuan
          $fields['stuffing_region'] = $oldRegion;
          $target = null;
      }
      if (!$hasColumn) {
          unset($fields['stuffing_region']);
      }
      return array($fields, null, $target);
  }

  /** Nomor job stuffing yang sudah memuat booking ini, atau null. */
  private function _stuffing_job_of($booking_code, $row = null)
  {
      $job = trim((string) ($row['job_no'] ?? ''));
      if ($job !== '') {
          return $job;
      }
      if (!$this->dbx->table_exists('trnstuffingplandetail')) {
          return null;
      }
      $fields = $this->dbx->list_fields('trnstuffingplandetail');
      if (!in_array('booking_code', $fields, true)) {
          return null;
      }
      $col = in_array('code', $fields, true) ? 'code' : (in_array('kode_ref', $fields, true) ? 'kode_ref' : 'booking_code');
      $q = $this->dbx->select($col . ' AS job')->where('booking_code', $booking_code)->limit(1)->get('trnstuffingplandetail');
      $d = $q ? $q->row_array() : null;
      return $d ? (string) $d['job'] : null;
  }

  /**
   * Input datetime-local mengirim "Y-m-d\TH:i". Ubah ke "Y-m-d H:i:s".
   * Kosong / tidak valid => NULL (bukan '' yang ditolak MySQL strict mode).
   */
  private function _normalize_datetime($v)
  {
      $v = trim(str_replace('T', ' ', (string)$v));
      if ($v === '' || strpos($v, '0000-00-00') === 0) return null;
      foreach (array('Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d') as $fmt) {
          $d = DateTime::createFromFormat($fmt, $v);
          if ($d && $d->format($fmt) === $v) {
              return $d->format('Y-m-d H:i:s');
          }
      }
      return null;
  }

  /**
   * Cari booking_code yang valid dari POST (#id dulu, lalu booking_code).
   * Return '' jika tidak ada yang cocok.
   */
  private function _resolve_booking_key($data)
  {
      foreach (array($data['id'] ?? '', $data['booking_code'] ?? '') as $cand) {
          $cand = trim((string)$cand);
          if ($cand === '') continue;
          $n = $this->dbx->where('booking_code', $cand)->count_all_results('trnstuffingplan_booking');
          if ($n > 0) return $cand;
      }
      return '';
  }

  /**
   * Kolom yang wajib ada di trnstuffingplan_booking_detail pada DB aktif.
   */
  private function _missing_detail_columns()
  {
      $need = array(
          'do_number', 'wh_arrival', 'dg_class', 'hs_code',
          'aju_number', 'peb_number', 'peb_date', 'npe_number', 'npe_date',
          'jalur', 'jalur_date', 'karantina_status', 'karantina_number', 'karantina_date',
      );
      $have = array_map('strtolower', $this->dbx->list_fields('trnstuffingplan_booking_detail'));
      return array_values(array_diff($need, $have));
  }

  /**
   * Normalisasi field PENGAWASAN BC HANGGAR dari POST
   * (AJU, PEB, NPE, Jalur, Karantina).
   *
   * ATURAN ANTI-KOSONG: field yang dikirim kosong TIDAK dimasukkan ke array,
   * sehingga nilai yang sudah tersimpan di DB tidak akan tertimpa kosong,
   * walaupun validasi di form di-bypass. Koreksi (ganti nilai) tetap bisa.
   */
  private function _customs_doc_fields($data)
  {
      $clean_no = function ($v, $max = 30) {
          $v = strtoupper(trim((string)$v));
          return $v === '' ? null : substr($v, 0, $max);
      };
      $clean_date = function ($v) {
          $v = trim((string)$v);
          if ($v === '') return null;
          $d = DateTime::createFromFormat('Y-m-d', $v);
          return ($d && $d->format('Y-m-d') === $v) ? $v : null;
      };
      $clean_enum = function ($v, $allowed) {
          $v = strtoupper(trim((string)$v));
          return in_array($v, $allowed, true) ? $v : null;
      };

      $fields = array(
          'aju_number'       => $clean_no($data['aju_number'] ?? ''),
          'peb_number'       => $clean_no($data['peb_number'] ?? ''),
          'peb_date'         => $clean_date($data['peb_date'] ?? ''),
          'npe_number'       => $clean_no($data['npe_number'] ?? ''),
          'npe_date'         => $clean_date($data['npe_date'] ?? ''),
          'jalur'            => $clean_enum($data['jalur'] ?? '', array('HIJAU', 'MERAH')),
          'jalur_date'       => $clean_date($data['jalur_date'] ?? ''),
          'karantina_status' => $clean_enum($data['karantina_status'] ?? '', array('TIDAK WAJIB', 'WAJIB')),
          'karantina_number' => $clean_no($data['karantina_number'] ?? '', 50),
          'karantina_date'   => $clean_date($data['karantina_date'] ?? ''),
      );

      // Buang yang kosong/tidak valid => kolom di DB tidak disentuh
      return array_filter($fields, function ($v) {
          return $v !== null;
      });
  }

  /**
   * Update field PENGAWASAN BC HANGGAR saja. Dipakai saat status booking = BOOKED,
   * di mana field lain tidak boleh berubah.
   */
  function update_customs_doc($data)
  {
      $booking_code = trim($data['booking_code'] ?? '');
      if ($booking_code === '') {
          return array('status' => 0, 'message' => 'Booking code kosong.');
      }

      $missing = $this->_missing_detail_columns();
      if (!empty($missing)) {
          return array(
              'status'  => 0,
              'message' => 'Kolom belum ada di database "' . $this->dbx->database . '": ' . implode(', ', $missing) .
                           '. Jalankan migration di database tersebut.'
          );
      }

      $exists = $this->dbx->get_where('trnstuffingplan_booking_detail', array('booking_id' => $booking_code))->num_rows();
      if ($exists == 0) {
          return array('status' => 0, 'message' => 'Detail booking tidak ditemukan.');
      }

      $fields = $this->_customs_doc_fields($data);
      $fields['updated_at'] = date('Y-m-d H:i:s');

      $this->dbx->where('booking_id', $booking_code);
      $ok = $this->dbx->update('trnstuffingplan_booking_detail', $fields);

      if (!$ok) {
          $err = $this->dbx->error();
          return array(
              'status'  => 0,
              'message' => 'Gagal menyimpan PEB/NPE ke database "' . $this->dbx->database . '"' .
                           (!empty($err['message']) ? ': ' . $err['message'] : '.')
          );
      }

      // Coload antar cabang: PEB/NPE ikut ke BC cabang tujuan (boleh walau sudah masuk job)
      $child = $this->branch_child_of($booking_code);
      if ($child) {
          $this->dbx->where('booking_id', $child['booking_code'])->update('trnstuffingplan_booking_detail', $fields);
      }

      return array('status' => 1, 'booking_code' => $booking_code, 'database' => $this->dbx->database,
                   'coload_booking_code' => $child ? $child['booking_code'] : null);
  }

  /** Link coload aktif (trnbooking_link) milik booking ini, atau null. Aman bila tabel belum ada. */
  private function _active_coload_link($booking_code)
  {
      if (!$this->dbx->table_exists('trnbooking_link')) {
          return null;
      }
      $hasCompany = $this->dbx->table_exists('mstcoloader_company');
      $this->dbx->select('l.id, l.linked_company AS company_code, l.linked_bc_no, l.created_by'
          . ($hasCompany ? ', c.company_name' : ''), false)
          ->from('trnbooking_link l');
      if ($hasCompany) {
          $this->dbx->join('mstcoloader_company c', 'c.company_code = l.linked_company', 'left');
      }
      $q = $this->dbx->where('l.booking_code', $booking_code)->where('l.status', 'active')->limit(1)->get();
      $row = $q ? $q->row_array() : null;
      if (!$row) {
          return null;
      }
      if (empty($row['company_name'])) {
          $row['company_name'] = $row['company_code'];
      }
      return $row;
  }

  /**
   * Guard penghapusan booking.
   *
   * Booking TIDAK boleh dihapus jika:
   * - sudah direferensikan oleh trnstuffingplandetail.booking_code; atau
   * - status booking sudah "booked"; atau
   * - book.job_no sudah terisi.
   *
   * Guard ini harus dipanggil SEBELUM delete WMS dan juga dipakai lagi
   * di cancel_booking() sebagai pertahanan terakhir.
   */
  public function get_booking_delete_guard($booking_code)
  {
      $booking_code = trim((string) $booking_code);

      if ($booking_code === '') {
          return array(
              'can_delete'   => false,
              'status'       => 0,
              'booking_code' => '',
              'message'      => 'BOOKING_CODE_EMPTY'
          );
      }

      // 1. Referensi paling kuat: booking sudah menjadi anak stuffing plan.
      $stuffing_job = $this->_stuffing_job_of($booking_code);

      if ($stuffing_job !== null && trim((string) $stuffing_job) !== '') {
          return array(
              'can_delete'     => false,
              'status'         => 0,
              'booking_code'   => $booking_code,
              'job_no'         => trim((string) $stuffing_job),
              'booking_status' => 'booked',
              'message'        => 'BOOKING_ALREADY_IN_STUFFING_PLAN'
          );
      }

      // 2. Secondary guard dari header booking.
      $booking = $this->dbx
          ->select('booking_code, status, job_no, region_id')
          ->where('booking_code', $booking_code)
          ->limit(1)
          ->get('trnstuffingplan_booking')
          ->row_array();

      // Titipan coload dari cabang lain hanya boleh dihapus cabang pemiliknya.
      if ($booking && !$this->_is_own_booking($booking)) {
          return array(
              'can_delete'   => false,
              'status'       => 0,
              'booking_code' => $booking_code,
              'message'      => 'BOOKING_OTHER_BRANCH',
              'user_message' => 'Booking ' . $booking_code . ' milik cabang ' . $booking['region_id'] .
                                ' (titipan coload ke cabang ini), jadi tidak bisa dihapus dari sini. ' .
                                'Minta cabang ' . $booking['region_id'] . ' menghapus atau mengubah Stuffing Plan-nya.',
          );
      }

      if ($booking) {
          $status = strtolower(trim((string) ($booking['status'] ?? '')));
          $job_no = trim((string) ($booking['job_no'] ?? ''));

          if ($status === 'booked' || $job_no !== '') {
              return array(
                  'can_delete'     => false,
                  'status'         => 0,
                  'booking_code'   => $booking_code,
                  'job_no'         => $job_no,
                  'booking_status' => $status,
                  'message'        => 'BOOKING_ALREADY_IN_STUFFING_PLAN'
              );
          }
      }

      // 3. Booking coload dari Osline / Winfast / BPLines (Booking API v2): sumber datanya
      //    di sistem coloader. Hapus dari sana, supaya booking Gateway dibatalkan,
      //    link diputus, dan ticket WMS diurus dari sisi yang sama.
      $link = $this->_active_coload_link($booking_code);
      // Link manual (BC asal diisi dari form Gateway) tidak menahan penghapusan:
      // sistem coloader tidak tahu booking ini, jadi Gateway yang menghapus.
      if ($link !== null && $link['company_code'] === self::BRANCH_COMPANY) {
          $parent = $this->branch_parent_of($booking_code);
          $origin = $parent && !empty($parent['region_id']) ? $parent['region_id'] : 'asal';
          return array(
              'can_delete'     => false,
              'status'         => 0,
              'booking_code'   => $booking_code,
              'linked_company' => $link['company_code'],
              'linked_bc_no'   => $link['linked_bc_no'],
              'message'        => 'BOOKING_LINKED_COLOAD',
              'user_message'   => 'Booking ini titipan coload dari Gateway ' . $origin . ' (BC asal ' . $link['linked_bc_no'] .
                                  '), jadi tidak bisa dihapus dari sini. Minta cabang ' . $origin . ' menghapus BC ' .
                                  $link['linked_bc_no'] . ' atau mengubah Stuffing Plan-nya. ' .
                                  (strpos((string) ($link['created_by'] ?? ''), self::BRANCH_ADOPT_ACTOR) === 0
                                      ? 'Setelah itu sambungan dilepas dan booking ' . $booking_code . ' kembali menjadi booking cabang ini biasa.'
                                      : 'Setelah itu booking ' . $booking_code . ' otomatis dibatalkan dan hilang dari daftar ini.'),
          );
      }
      if ($link !== null && !$this->_is_manual_link($link)) {
          return array(
              'can_delete'     => false,
              'status'         => 0,
              'booking_code'   => $booking_code,
              'linked_company' => $link['company_code'],
              'linked_bc_no'   => $link['linked_bc_no'],
              'message'        => 'BOOKING_LINKED_COLOAD',
              'user_message'   => 'Booking ini titipan coload dari ' . $link['company_name'] .
                                  ' dengan BC asal ' . $link['linked_bc_no'] . ', jadi tidak bisa dihapus dari Gateway. ' .
                                  'Minta tim ' . $link['company_name'] . ' menghapus atau mengubah BC ' . $link['linked_bc_no'] .
                                  ' di sistem mereka. Setelah itu booking ' . $booking_code .
                                  ' otomatis dibatalkan dan hilang dari daftar ini.',
          );
      }

      // 4. BC asal yang di-coload ke cabang lain: BC cabang tujuan ikut dibatalkan saat dihapus,
      //    jadi hanya boleh selama BC tujuan belum masuk job / gudang.
      $child = $this->branch_child_of($booking_code);
      if ($child && !$this->_is_adopted_child($child)) {
          $lock = $this->_branch_child_lock($child);
          if ($lock !== null) {
              return array(
                  'can_delete'   => false,
                  'status'       => 0,
                  'booking_code' => $booking_code,
                  'message'      => 'BOOKING_COLOAD_CHILD_LOCKED',
                  'user_message' => 'Booking ini di-coload ke cabang ' . $child['region_id'] . ' sebagai ' . $child['booking_code'] .
                                    ', dan booking tersebut ' . $lock . '. Minta cabang ' . $child['region_id'] .
                                    ' mengeluarkannya dulu sebelum BC ini dihapus.',
              );
          }
      }

      // Header boleh saja sudah tidak ada (mis. cleanup/retry WMS).
      // Yang penting tidak ada referensi stuffing plan.
      return array(
          'can_delete'     => true,
          'coload_booking_code' => $child ? $child['booking_code'] : null,
          'status'         => 1,
          'booking_code'   => $booking_code,
          'local_exists'   => !empty($booking),
          'booking_status' => $booking ? strtolower(trim((string) ($booking['status'] ?? ''))) : '',
          'job_no'         => $booking ? trim((string) ($booking['job_no'] ?? '')) : '',
          'message'        => 'BOOKING_DELETE_ALLOWED'
      );
  }

  function cancel_booking($booking_code)
  {

	// kita ekstrak nilai teksnya secara otomatis.
	if (is_array($booking_code)) {
		if (isset($booking_code['booking'])) {
			$booking_code = $booking_code['booking'];
		} elseif (isset($booking_code['booking_code'])) {
			$booking_code = $booking_code['booking_code'];
		} else {
			$booking_code = ''; // Kosongkan jika key tidak ditemukan
		}
	}

	// Baru diubah ke string yang bersih
	$booking_code = trim((string) $booking_code);
	// ------------------------------

      if ($booking_code === '') {
          return array(
              'status'  => 0,
              'message' => 'BOOKING_CODE_EMPTY'
          );
      }

      // HARD STOP: jangan pernah menghapus booking yang sudah masuk stuffing plan.
      $guard = $this->get_booking_delete_guard($booking_code);

      if (empty($guard['can_delete'])) {
          return array_merge(
              array(
                  'status'       => 0,
                  'booking_code' => $booking_code
              ),
              $guard
          );
      }

      // Hapus detail lebih dulu untuk menghindari masalah FK bila constraint aktif.
      $this->dbx->trans_begin();

      try {
          // Riwayat link coload yang sudah dibatalkan (FK ke booking ini) ikut dihapus;
          // link yang masih aktif sudah ditolak oleh guard di atas.
          // Coload antar cabang: BC di cabang tujuan dibatalkan (guard sudah memastikan belum masuk job)
          $child = $this->branch_child_of($booking_code);
          if ($child) {
              $this->_release_branch_child($child, 'BC asal ' . $booking_code . ' dihapus oleh Gateway ' . $this->session->userdata('region_id'));
          }

          if ($this->dbx->table_exists('trnbooking_link')) {
              $this->dbx->where('booking_code', $booking_code)
                        ->group_start()
                            ->where('status', 'cancelled')
                            ->or_like('created_by', self::MANUAL_LINK_ACTOR, 'after')
                        ->group_end();
              $this->dbx->delete('trnbooking_link');
          }

          $this->dbx->where('booking_id', $booking_code);
          $this->dbx->delete('trnstuffingplan_booking_detail');
          $detail_deleted = (int) $this->dbx->affected_rows();

          $this->dbx->where('booking_code', $booking_code);
          $this->dbx->delete('trnstuffingplan_booking');
          $header_deleted = (int) $this->dbx->affected_rows();

          $failed   = ($this->dbx->trans_status() === FALSE);
          $db_error = $this->dbx->error();
      } catch (Exception $e) {
          // db_debug / mysqli strict mode melempar exception, bukan return FALSE
          $failed   = true;
          $db_error = array('message' => $e->getMessage());
      }

      if ($failed) {
          $this->dbx->trans_rollback();

          return array(
              'status'       => 0,
              'booking_code' => $booking_code,
              'message'      => 'LOCAL_BOOKING_DELETE_FAILED',
              'error'        => isset($db_error['message']) ? $db_error['message'] : ''
          );
      }

      $this->dbx->trans_commit();

      return array(
          'status'         => 1,
          'booking_code'   => $booking_code,
          'header_deleted' => $header_deleted,
          'detail_deleted' => $detail_deleted,
          'message'        => ($header_deleted > 0 || $detail_deleted > 0)
              ? 'BOOKING_DELETED'
              : 'BOOKING_ALREADY_NOT_FOUND'
      );
  }

  /** Booking ini milik cabang user yang login? (tanpa session region = dianggap milik sendiri) */
  private function _is_own_booking($booking)
  {
      $owner  = trim((string) (is_array($booking) ? ($booking['region_id'] ?? '') : ($booking->region_id ?? '')));
      $region = trim((string) $this->session->userdata('region_id'));
      return $owner === '' || $region === '' || strcasecmp($owner, $region) === 0;
  }

  private function _has_stuffing_region()
  {
      static $has = array();
      $k = $this->dbx->database;
      if (!isset($has[$k])) {
          $has[$k] = in_array('stuffing_region', $this->dbx->list_fields('trnstuffingplan_booking'), true);
      }
      return $has[$k];
  }

  /** Daftar booking: milik cabang ini ATAU titipan coload yang di-stuffing di cabang ini. */
  private function _where_region_or_stuffing($region)
  {
      if ($this->_has_stuffing_region()) {
          $this->dbx->group_start()
                    ->where('book.region_id', $region)
                    ->or_where('book.stuffing_region', $region)
                    ->group_end();
      } else {
          $this->dbx->where('book.region_id', $region);
      }
  }

  /** Cabang pemilik booking (region_id detail ikut di '*', jadi dibedakan) untuk menandai titipan coload. */
  private function _coload_list_columns()
  {
      return 'book.region_id AS owner_region';
  }

  function get_all_booked_order($data,$limit){
    $region = $this->session->userdata('region_id');
    $this->dbx->select('*,
                      book.booking_code as real_id,
                      book.real_dest as destination,
                    ');
    $this->dbx->select($this->_coload_list_columns(), false);
    $this->dbx->from('trnstuffingplan_booking book');
    $this->dbx->join('trnstuffingplan_booking_detail','trnstuffingplan_booking_detail.booking_id=book.booking_code','left');
    
    // GANTI STATUS MENJADI BOOKED DI SINI
    // (BC yang di-coload ke cabang lain masuk job cabangnya SENDIRI, seperti BC biasa)
    $this->dbx->where('book.status','booked');
    
    // Booking milik cabang ini + titipan coload dari cabang lain yang di-stuffing di sini
    $this->_where_region_or_stuffing($region);
    
    if (
    isset($data['_search']) &&
    $data['_search'] === 'true' &&
    !empty($data['searchField'])
	) {

		$searchField  = $data['searchField'];
		$searchString = $data['searchString'] ?? '';
		$searchOper   = $data['searchOper'] ?? 'cn';

		$ops = array(
			'eq' => '=',
			'cn' => 'LIKE',
		);

		$operator = isset($ops[$searchOper])
			? $ops[$searchOper]
			: 'LIKE';

		if ($searchOper === 'cn') {
			$searchString = '%' . $searchString . '%';
		}

		$where = "$searchField $operator " .
				 $this->dbx->escape($searchString);

		$this->dbx->where($where);
	}
    
    if($limit==true) {
        $this->dbx->limit($data['rows'],$data['start']);
    }

    $this->dbx->order_by("
    CASE
        WHEN book.job_no LIKE 'GCL-100%' THEN 1
        WHEN book.job_no LIKE 'GCL-102%' THEN 2
        ELSE 99
    END
	", '', false);

	$this->dbx->order_by('book.job_no', 'DESC');
	$this->dbx->order_by('book.real_dest', 'ASC');
	$this->dbx->order_by('book.expect_etd', 'ASC');;
    
    return $this->dbx->get()->result();
}
   
  function get_all_finalize_order($data,$limit){
		$region = $this->session->userdata('region_id');
		$this->dbx->select('*,
						  book.booking_code as real_id,
						  book.real_dest as destination,
						');
		$this->dbx->select($this->_coload_list_columns(), false);
		$this->dbx->from('trnstuffingplan_booking book');
		$this->dbx->join('trnstuffingplan_booking_detail','trnstuffingplan_booking_detail.booking_id=book.booking_code','left');
		$this->dbx->where('book.status','finalize');
		// Booking milik cabang ini + titipan coload dari cabang lain yang di-stuffing di sini
		$this->_where_region_or_stuffing($region);
		 if (
		isset($data['_search']) &&
		$data['_search'] === 'true' &&
		!empty($data['searchField'])
		) {

			$searchField  = $data['searchField'];
			$searchString = $data['searchString'] ?? '';
			$searchOper   = $data['searchOper'] ?? 'cn';

			$ops = array(
				'eq' => '=',
				'cn' => 'LIKE',
			);

			$operator = isset($ops[$searchOper])
				? $ops[$searchOper]
				: 'LIKE';

			if ($searchOper === 'cn') {
				$searchString = '%' . $searchString . '%';
			}

			$where = "$searchField $operator " .
					 $this->dbx->escape($searchString);

			$this->dbx->where($where);
		}
		
		if($limit==true) {
			$this->dbx->limit($data['rows'],$data['start']);
		}
	
		$this->dbx->order_by('destination','book.expect_etd asc');
		
		return $this->dbx->get()->result();
	}
	function get_warehouse_detail($wh_code){
  	$q = $this->dbx->get_where('foobar',array('warehouse_code'=>$wh_code));
	//print_r($this->dbx->last_query());
	//die();
   	if($q->num_rows()>0) 
   	{
   		return array(
		'warehouse_code'=>$q->row('warehouse_code'),
		'warehouse_name'=>$q->row('warehouse_name'),
		'warehouse_address'=>$q->row('warehouse_address'),
		'npwp'=>$q->row('npwp'),
		'nitku'=>$q->row('nitku'),
		'alamat'=>$q->row('alamat'));
   	} else{
		return array('warehouse_code'=>'','warehouse_name'=>'','warehouse_address'=>'');
	}
	}
	function get_do($booking_code){
  	$q = $this->dbx->get_where('trnstuffingplan_booking',array('booking_code'=>$booking_code))->row('trucking');
	//print_r($q);
	//die();
   	if($q>0) 
   	{
   		return $q;
   	} else {
		return '';
	}
	}
	
	public function get_shipper_details($shipper_id) {
	
    $this->dbx->select('sales, shipper_name');
    $this->dbx->from('mstshipper');
    $this->dbx->where('id_shipper', $shipper_id);
    $query = $this->dbx->get();
	
    // Check if the row exists
    if ($query->num_rows() > 0) {
        return $query->row(); // Return the result as an object
    } else {
        return null; // No matching rows
    }
	}
	
	/**
     * Context booking untuk timeline Master Job.
     * Mengembalikan kode booking, rekap kargo, dan waktu booking pertama.
     */
    public function get_booking_timeline_context(
        $schedule_id,
        $region_id = '',
        $route_type = '',
        $booking_type = 'EXPORT'
    ) {
        $schedule_id = (int)$schedule_id;
        $region_id = trim((string)$region_id);
        $route_type = strtoupper(trim((string)$route_type));
        $booking_type = strtoupper(trim((string)$booking_type));

        $result = array(
            'booking_codes' => array(),
            'GENCO' => 0,
            'DG' => 0,
            'TOTAL' => 0,
            'first_booking_at' => NULL,
            'first_confirmed_by' => 'SYSTEM'
        );

        if ($schedule_id <= 0) {
            return $result;
        }

        $this->dbx->select(
            'booking_code, cargo_type, created_at, pic_document, route_type, type_of_booking'
        );
        $this->dbx->from('trnstuffingplan_booking');
        $this->dbx->where('schedule_id', $schedule_id);

        if ($region_id !== '') {
            $this->dbx->where('region_id', $region_id);
        }

        if ($booking_type !== '' && $this->dbx->field_exists('type_of_booking', 'trnstuffingplan_booking')) {
            $this->dbx->where(
                'UPPER(type_of_booking) LIKE '.$this->dbx->escape('%'.$booking_type.'%'),
                NULL,
                FALSE
            );
        }

        if ($route_type !== '' && $this->dbx->field_exists('route_type', 'trnstuffingplan_booking')) {
            if (strpos($route_type, 'VIA') !== FALSE) {
                $this->dbx->where("UPPER(route_type) LIKE '%VIA%'", NULL, FALSE);
            } else {
                $this->dbx->where("UPPER(COALESCE(route_type, '')) NOT LIKE '%VIA%'", NULL, FALSE);
            }
        }

        if ($this->dbx->field_exists('status', 'trnstuffingplan_booking')) {
            $this->dbx->where("LOWER(COALESCE(status, '')) != 'cancelled'", NULL, FALSE);
        }
        $this->dbx->order_by('created_at', 'ASC');
        $rows = $this->dbx->get()->result();

        foreach ($rows as $row) {
            $booking_code = trim((string)$row->booking_code);
            if ($booking_code !== '') {
                $result['booking_codes'][] = $booking_code;
            }

            $cargo_type = strtoupper(trim((string)$row->cargo_type));
            if ($cargo_type === 'DG') {
                $result['DG']++;
            } else {
                $result['GENCO']++;
            }
            $result['TOTAL']++;

            if ($result['first_booking_at'] === NULL && !empty($row->created_at)) {
                $result['first_booking_at'] = $row->created_at;
                $result['first_confirmed_by'] = !empty($row->pic_document)
                    ? $row->pic_document
                    : 'SYSTEM';
            }
        }

        $result['booking_codes'] = array_values(array_unique($result['booking_codes']));
        return $result;
    }

    /** Backward compatible untuk pemanggil lama. */
    public function get_booking_summary_by_schedule($schedule_id)
    {
        $context = $this->get_booking_timeline_context(
            $schedule_id,
            $this->session->userdata('region_id')
        );

        return array(
            'GENCO' => $context['GENCO'],
            'DG' => $context['DG'],
            'TOTAL' => $context['TOTAL']
        );
    }
}



