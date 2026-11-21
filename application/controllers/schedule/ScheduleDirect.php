<?php if(!defined('BASEPATH')) exit('No direct script access allowed');
class ScheduleDirect extends CI_Controller
{
	var $title = 'Schedule';
    public function __construct()
    {
        parent::__construct();
        $this->fungsi->restrict();
        $this->fungsi->get_define();
        $this->load->model(array('schedule/scheduleDirect_model','schedule/scheduleDirectImport_model','Timeline_model'));
        $this->fungsi->dbx = $this->scheduleDirect_model->dbx;
		$this->dbx = $this->scheduleDirect_model->dbx;
		$this->config->load('schedule_do', FALSE, TRUE);
   
    }

    public function get_categories()
    {
      $data['categories'] = $this->scheduleDirect_model->get_categories();
      $this->load->view('schedule_direct/categories', $data);
    }

    public function index()
    {
        $this->fungsi->auth(100,'Schedule');
		$data['title'] = 'ScheduleDirect';
		$data['menu_navigation'] = 'navigation';
		$data['main_view'] = 'schedule/schedulesDirect';				
		$this->load->view('afg_template', $data);
    }

    function scheduleDirectListing()
    {
            $now = new \DateTime('now');
            $month_default = $now->format('m');
            $year_default = $this->session->userdata('db');
            $region_id = $this->session->userdata('region_id');
            $month_search = $this->input->post('month');
            $year_search = $this->session->userdata('db');

            if(!empty($month_search) AND !empty($year_search)){
              $month = $month_search;
              $year = $year_search;

            }else{
              $month = $month_default;
              $year = $year_default;
            }


              $sessionArray = array(
                                      'month'=>$month,
                                      'year'=>$year,
                                      );
              $this->session->set_userdata($sessionArray);
              //echo $this->session->userdata('month').$month;exit();
              $data['scheduleDirectRecords'] = $this->scheduleDirect_model->ScheduleDirectByCities($month, $year);
              $data['scheduleDirectRecords_byCity'] = $this->scheduleDirect_model->ScheduleDirectListing_byCity($month, $year);

             //echo $this->dbx->last_query();exit();
			$data['title'] = 'ScheduleDirect';
			$data['menu_navigation'] = 'navigation';
            $data['main_view'] = 'schedule/schedulesDirect';				
			$this->load->view('afg_template', $data);
        
    }
	
	private function _build_container_type_json_from_post()
	{
		$containerType = $this->input->post('container_type');
		$containerRaw  = trim((string) $this->input->post('container_type_raw'));
		$containerQty  = trim((string) $this->input->post('container_qty'));

		$items = [];

		// 1. Kalau dari form lama container_type berupa array.
		if (is_array($containerType)) {
			foreach ($containerType as $ct) {
				$ct = trim((string) $ct);
				if ($ct !== '') {
					$items[] = $this->_normalize_container_label($ct);
				}
			}
		}

		// 2. Kalau container_type single string.
		if (empty($items) && is_string($containerType) && trim($containerType) !== '') {
			$items[] = $this->_normalize_container_label($containerType);
		}

		// 3. Parse raw equipment dari DO.
		// Contoh:
		// 20'DRY ST.-1, 40'DRY HC.-2
		// 1 X H40'
		// 1 x 40' Hi-Cube Container
		// 40Hx1
		// 1 x 20DC
		if (empty($items) && $containerRaw !== '') {
			$parts = preg_split('/\s*,\s*/', $containerRaw);

			foreach ($parts as $part) {
				$part = trim($part);
				if ($part === '') {
					continue;
				}

				$parsed = $this->_parse_container_part($part);

				if (!empty($parsed)) {
					$items[] = $parsed['label'];
				}
			}
		}

		// 4. Fallback dari qty saja.
		if (empty($items) && $containerQty !== '') {
			$items[] = 'UNKNOWN';
		}

		// 5. Jangan pernah return string kosong, karena kolom JSON akan error.
		return json_encode(array_values(array_unique($items)), JSON_UNESCAPED_UNICODE);
	}

	private function _parse_container_part($part)
	{
		$raw = strtoupper(trim((string) $part));
		$raw = str_replace(["’", "`", "´"], "'", $raw);
		$raw = preg_replace('/\s+/', ' ', $raw);

		$qty  = 1;
		$size = '';
		$type = '';
		// Format COSCO: 1 X 40' Hi-Cube Container
		if (preg_match("/\b(\d+)\s*[xX]\s*(20|40)\s*'?\s*(Hi[\-\s]?Cube|High[\-\s]?Cube|HC|HQ|H|Dry|GP|DC)?\s*(?:Container)?\b/i", $raw, $m)) {
			$qty  = (int) $m[1];
			$size = $m[2];
			$type = isset($m[3]) ? $m[3] : '';

			if ($qty <= 0) {
				$qty = 1;
			}

			return [
				'label' => $this->_normalize_container_size_type($size, $type),
				'qty'   => $qty,
			];
		}
		// Format: 20'DRY ST.-1 / 40'DRY HC.-2
		elseif (preg_match("/\b(20|40)\s*'?\s*([A-Z0-9\s\/\.\-]*?)\s*[-xX]\s*(\d+)\b/i", $raw, $m)) {
			$size = $m[1];
			$type = $m[2];
			$qty  = (int) $m[3];
		}
		// Format: 1 X H40' / 1 X 40HC / 1 x 40' Hi-Cube Container
		elseif (preg_match("/\b(\d+)\s*[xX]\s*(H)?\s*(20|40)\s*'?\s*([A-Z0-9\s\/\.\-]*)/i", $raw, $m)) {
			$qty  = (int) $m[1];
			$size = $m[3];
			$type = trim(($m[2] ? 'H ' : '') . ' ' . ($m[4] ?? ''));
		}
		// Format: 40Hx1 / 20DCx1 / 40HC x 2
		elseif (preg_match("/\b(20|40)\s*([A-Z0-9\s\/\.\-]*?)\s*[xX]\s*(\d+)\b/i", $raw, $m)) {
			$size = $m[1];
			$type = $m[2];
			$qty  = (int) $m[3];
		}
		// Format: 20DC / 40HC / 40 H / 40 FT
		elseif (preg_match("/\b(20|40)\s*'?[\s\-]*([A-Z0-9\s\/\.\-]*)/i", $raw, $m)) {
			$size = $m[1];
			$type = $m[2];
			$qty  = 1;
		}

		if ($size === '') {
			return null;
		}

		if ($qty <= 0) {
			$qty = 1;
		}

		return [
			'label' => $this->_normalize_container_size_type($size, $type),
			'qty'   => $qty,
		];
	}
	private function _normalize_container_label($value)
	{
		$value = strtoupper(trim((string) $value));
		$value = str_replace(["’", "`", "´"], "'", $value);
		$value = preg_replace('/\s+/', ' ', $value);

		$parsed = $this->_parse_container_part($value);

		if (!empty($parsed) && !empty($parsed['label'])) {
			// Container type di tbl_schedules_direct wajib tipe saja.
			// Qty disimpan di container_qty / do_extracted_json, jangan ikut di JSON container_type.
			return $parsed['label'];
		}

		// Safety: kalau user/form masih kirim "40 HC x1", bersihkan qty-nya.
		$value = preg_replace('/\s*x\s*\d+\s*$/i', '', $value);
		$value = preg_replace('/\s*-\s*\d+\s*$/i', '', $value);

		return trim($value);
	}


	private function _normalize_container_size_type($size, $type)
	{
		$size = trim((string) $size);
		$type = strtoupper(trim((string) $type));

		$type = str_replace(["'", '.', '-', '_'], ' ', $type);
		$type = preg_replace('/\s+/', ' ', $type);

		// H40 dari Samudera = 40 HC.
		if ($size === '40' && preg_match('/\bH\b/', $type)) {
			return '40 HC';
		}

		// 40HC / 40 H / 40HQ / HIGH CUBE / HI CUBE = 40 HC.
		if ($size === '40' && (
			strpos($type, 'HC') !== false ||
			strpos($type, 'HQ') !== false ||
			strpos($type, 'HIGH CUBE') !== false ||
			strpos($type, 'HI CUBE') !== false ||
			strpos($type, 'HICUBE') !== false ||
			strpos($type, 'H C') !== false
		)) {
			return '40 HC';
		}

		// Default normalisasi sesuai request.
		if ($size === '20') {
			return '20 FT';
		}

		if ($size === '40') {
			return '40 FT';
		}

		return $size . ' FT';
	}
	
	private function _upper_post($name)
	{
		return strtoupper(trim((string) $this->input->post($name)));
	}

    public function createScheduleDirect()
    {
        // Tangkap periode
        $month   = $this->input->post('month');
        $year    = $this->input->post('year');

        // Tangkap field dari form modal terbaru
        $liner_id          = $this->input->post('liner_id'); // <-- Tambahan Form Baru
        $city_id           = $this->_upper_post('city_id');
        $vessel            = $this->input->post('vessel');
        $voy               = $this->input->post('voy');
        $stf_cls           = $this->input->post('stf_cls'); // Closing Date
        $etd_jkt           = $this->input->post('etd_jkt');
        $eta               = $this->input->post('eta');
        $stuffing_date     = $this->input->post('stuffing_date'); // <-- Tambahan Form Baru
        $stuffing_location = $this->input->post('stuffing_location'); 

        // Tangkap field lama (biarkan jika di tabel database Gateway CRM Anda masih membutuhkan ini)
        $carrier           = $this->input->post('carrier');
        $booking_number    = $this->input->post('booking_number');
        $containerType     = $this->input->post('container_type');

        // Mapping data ke array untuk dikirim ke model
        $scheduleDirectInfo = [
			'city_id'           => $this->input->post('city_id'),
			'vessel'            => $this->input->post('vessel'),
			'voy'               => $this->input->post('voy'),
			'stf_cls'           => $this->input->post('stf_cls'),
			'etd_jkt'           => $this->input->post('etd_jkt'),
			'eta'               => $this->input->post('eta'),
			'stuffing_date'     => $this->input->post('stuffing_date'),
			'stuffing_location' => $this->input->post('stuffing_location'),
			'carrier'           => $this->input->post('liner_id'),
			'booking_number'    => $this->input->post('booking_number'),

			// WAJIB valid JSON, jangan ambil langsung dari post.
			'container_type'    => $this->_build_container_type_json_from_post(),

			'region_id'         => $this->session->userdata('region_id'),
		];
		
        // Panggil fungsi model createScheduleDirect
        $result = $this->scheduleDirect_model->createScheduleDirect($scheduleDirectInfo);
        
        if($result > 0)
        {
            $this->session->set_flashdata('success', 'Schedule Direct created successfully');
        }
        else
        {
            $this->session->set_flashdata('error', 'Schedule creation failed'); // Pesan error disesuaikan
        }
        
        // Simpan state bulan & tahun agar UI tetap berada di periode yang sama setelah disubmit
        $sessionArray = array(
            'month_create_direct' => $month,
            'year_create_direct'  => $year,
        );
        $this->session->set_userdata($sessionArray);

        redirect('schedule/ScheduleDirect/scheduleDirectListing');
    }


    public function updateScheduleDirect()
	{
		$name  = $this->input->post('name');
		$value = $this->input->post('value');
		$id    = $this->input->post('pk');

		if ($name === 'container_type') {
			$value = $value ? json_encode($value) : json_encode([]);
		}

		$scheduleDirectInfo = [
			$name       => $value,
			'updatedBy' => $this->session->userdata('fullname'),
			'updatedDtm'=> date('Y-m-d H:i:s')
		];

		// 1. Update table schedule direct
		$result = $this->scheduleDirect_model->updateScheduleDirect($scheduleDirectInfo, $id);

		// 2. Ambil seluruh data schedule direct setelah update
		$schedule = $this->scheduleDirect_model->getScheduleDirectById($id); 

		if ($schedule) {
			// ========================================================
			// 3. Update ke trnstuffingplan_booking (Biarkan seperti semula)
			// ========================================================
			$this->dbx->where('schedule_id', $id);
			$exists_booking = $this->dbx->get('trnstuffingplan_booking')->num_rows() > 0;
			
			if ($exists_booking) {
				$stf_cls       = $schedule->stf_cls ?? null;
				$closingDate   = $stf_cls ? $stf_cls . 'T00:00' : null;

				$bookingUpdate = [
					'expect_etd'    => $schedule->etd_jkt,
					'eta'           => $schedule->eta,
					'closing_date'  => $closingDate,
					'closing_cargo' => $schedule->stuffing_date,
					'warehouse'     => $schedule->stuffing_location,
					'vessel'        => $schedule->vessel . ' ' . $schedule->voy
				];

				$this->dbx->where('schedule_id', $id);
				$this->dbx->update('trnstuffingplan_booking', $bookingUpdate);
			}

			// ========================================================
			// 4. Update ke trnstuffingplan (Ubah format tanggal jadi YYYYMMDD)
			// ========================================================
			$this->dbx->where('schedule_id', $id);
			$exists_sp = $this->dbx->get('trnstuffingplan')->num_rows() > 0;

			if ($exists_sp) {
				
				// Konversi tanggal dengan menghilangkan tanda '-'
				$etd_jkt_clean       = $schedule->etd_jkt ? str_replace('-', '', $schedule->etd_jkt) : null;
				$eta_clean           = $schedule->eta ? str_replace('-', '', $schedule->eta) : null;
				$stuffing_date_clean = $schedule->stuffing_date ? str_replace('-', '', $schedule->stuffing_date) : null;
				$stf_cls_clean       = $schedule->stf_cls ? str_replace('-', '', $schedule->stf_cls) : null;

				$spUpdate = [
					'etd_jkt'           => $etd_jkt_clean,
					'eta'               => $eta_clean,
					'stuffing_date'     => $stuffing_date_clean,
					'closing_date_time' => $stf_cls_clean,
					'vessel'            => $schedule->vessel,
					'voyage'            => $schedule->voy,
					'warehouse'         => $schedule->stuffing_location,
					'carrier'           => $schedule->carrier 
				];

				$this->dbx->where('schedule_id', $id);
				$this->dbx->where('schedule_type !=', 'VIA');
				$this->dbx->update('trnstuffingplan', $spUpdate);
			}
		}
	}

      function deleteScheduleDirect()
      {
        
              $id = $this->input->post('id');
              $scheduleDirectInfo = array('isDeleted'=>1,'updatedBy'=>$this->session->userdata('fullname'), 'updatedDtm'=>date('Y-m-d H:i:s'));
              $result = $this->scheduleDirect_model->deleteScheduleDirect($id, $scheduleDirectInfo);
              if ($result > 0) { echo(json_encode(array('status'=>TRUE))); }
              else { echo(json_encode(array('status'=>FALSE))); }
	  }
      
      function scheduleDirectListingImport()
    {
       
            $now = new \DateTime('now');
            $month_default = $now->format('m');
            $year_default = $this->session->userdata('db');

            $month_search = $this->input->post('month');
            $year_search = $this->session->userdata('db');

            if(!empty($month_search) ){
              $month = $month_search;
              $year = $year_search;

            }else{
              $month = $month_default;
              $year = $year_default;
            }


              $sessionArray = array(
                                      'month'=>$month,
                                      'year'=>$year,
                                      );
              $this->session->set_userdata($sessionArray);
              //echo $this->session->userdata('month').$month;exit();
              $data['scheduleDirectRecordsImport'] = $this->scheduleDirectImport_model->ScheduleDirectImportByCities($month, $year);
              $data['scheduleDirectRecordsImport_byCity'] = $this->scheduleDirectImport_model->ScheduleDirectListingImport_byCity($month, $year);

             //echo $this->dbx->last_query();exit();
            $data['cities'] = $this->scheduleDirectImport_model->getCities();
			$data['title'] = 'ScheduleDirectImport';
			$data['menu_navigation'] = 'navigation';
            $data['main_view'] = 'schedule/schedulesDirectImport';				
			$this->load->view('afg_template', $data);
        
    }

     function createScheduleDirectImport()
     {
        
                 $month = $this->input->post('month');
                 $year = $this->input->post('year');

                 $vessel = $this->input->post('vessel');
                 $voy = $this->input->post('voy');
                 $stf_cls = $this->input->post('stf_cls');
                 $etd_jkt = $this->input->post('etd_jkt');
                 $eta = $this->input->post('eta');
                 $city_id = $this->input->post('city_id');

                 $scheduleDirectInfo = array(
                                     'vessel'=>$vessel,
                                     'voy'=>$voy,
                                     'cut_off'=>$stf_cls,
                                     'etd'=>$etd_jkt,
                                     'eta_jkt'=>$eta,
                                     'city_id'=>$city_id,
                                     'createdBy'=>$this->session->userdata('fullname'),
                                     'createdDtm'=>date('Y-m-d H:i:s'),
                                     'region_id'=>$this->session->userdata('region_id'),
                                     );
                 $result = $this->scheduleDirectImport_model->createScheduleDirectImport($scheduleDirectInfo);
                 if($result > 0)
                 {
                     $this->session->set_flashdata('success', 'Schedule Direct created successfully');
                 }
                 else
                 {
                     $this->session->set_flashdata('error', 'Contact creation failed');
                 }
                 $sessionArray = array(
                                         'month_create_direct'=>$month,
                                         'year_create_direct'=>$year,

                                         );
                  $this->session->set_userdata($sessionArray);

                 redirect('schedule/ScheduleDirect/scheduleDirectListingImport');

     }


      public function updateScheduleDirectImport()
     {
       $name = $this->input->post('name');
       $value = $this->input->post('value');
       $id = $this->input->post('pk');
       $scheduleDirectInfo = array(
                           $name =>$value,
                           'updatedBy'=>$this->session->userdata('fullname'),
                           'updatedDtm'=>date('Y-m-d H:i:s'));
       $result = $this->scheduleDirectImport_model->updateScheduleDirectImport($scheduleDirectInfo, $id);
     }

      function deleteScheduleDirectImport()
      {
        
              $id = $this->input->post('id');
              $scheduleDirectInfo = array('isDeleted'=>1,'updatedBy'=>$this->session->userdata('fullname'), 'updatedDtm'=>date('Y-m-d H:i:s'));
              $result = $this->scheduleDirectImport_model->deleteScheduleDirectImport($id, $scheduleDirectInfo);
              if ($result > 0) { echo(json_encode(array('status'=>TRUE))); }
              else { echo(json_encode(array('status'=>FALSE))); }
	  }

     function pageNotFound()
     {
         $this->global['pageTitle'] = 'CodeInsect : 404 - Page Not Found';

         $this->loadViews("404", $this->global, NULL, NULL);
     }
	 public function extractDo()
	{
		if (empty($_FILES['do_file']['name'])) {
			return $this->_json(array('status' => false, 'message' => 'File DO belum dipilih.'));
		}

		$upload = $this->_saveScheduleDoUpload($_FILES['do_file']);
		if (!$upload['status']) {
			return $this->_json($upload);
		}

		$this->load->library('Schedule_do_extractor');

		// Paksa debug saat troubleshooting. Setelah normal boleh diganti ke get/post debug lagi.
		$debugMode = true;

		$result = $this->schedule_do_extractor->extract(
			$upload['full_path'],
			$upload['original_name'],
			$debugMode
		);

		$rawText = isset($result['raw_text']) ? (string)$result['raw_text'] : '';

		$response = array(
			'status'         => true,
			'message'        => 'OCR selesai. Tetap review manual sebelum save.',
			'file_token'     => $upload['file_token'],
			'raw_text'       => $rawText,
			'fields'         => isset($result['fields']) ? $result['fields'] : array(),
			'missing_fields' => isset($result['missing_fields']) ? $result['missing_fields'] : array(),
			'debug'          => isset($result['debug']) ? $result['debug'] : array(),
		);

		$response['debug']['upload'] = array(
			'full_path'      => $upload['full_path'],
			'original_name'  => $upload['original_name'],
			'file_token'     => $upload['file_token'],
			'php_user'       => function_exists('get_current_user') ? get_current_user() : '',
			'sys_temp_dir'   => sys_get_temp_dir(),
			'temp_writable'  => is_writable(sys_get_temp_dir()) ? 1 : 0,
		);

		if (strlen(trim($rawText)) < 20) {
			$response['status'] = false;
			$response['message'] = 'Extract gagal: raw_text masih kosong. Lihat debug untuk cek pdftotext/pdftoppm/tesseract/permission.';
			$response['warning'] = 'Untuk PDF, server wajib punya poppler-utils: pdftotext dan pdftoppm. Tesseract saja tidak cukup untuk membaca PDF langsung.';
		}

		return $this->_json($response);
	}

	public function getFormData()
	{
		$id = (int) $this->input->get('id');
		if ($id <= 0) {
			return $this->_json(array('status' => false, 'message' => 'Invalid schedule id'));
		}

		$schedule = $this->scheduleDirect_model->getScheduleDirectById($id);
		if (!$schedule) {
			return $this->_json(array('status' => false, 'message' => 'Schedule not found'));
		}

		$bookings = array();
		if (method_exists($this->scheduleDirect_model, 'getBookingsByScheduleId')) {
			$bookings = $this->scheduleDirect_model->getBookingsByScheduleId($id);
		}

		return $this->_json(array(
			'status' => true,
			'data' => array(
				'schedule' => $schedule,
				'bookings' => $bookings,
			)
		));
	}
	
	public function searchDestinations()
	{
		$q = strtoupper(trim((string) $this->input->get('q')));

		$rows = $this->scheduleDirect_model->searchDestinationOptions($q, 30);

		return $this->_json(array(
			'results' => $rows
		));
	}

	public function searchLiners()
	{
		$q = strtoupper(trim((string) $this->input->get('q')));

		$rows = $this->scheduleDirect_model->searchLinerOptions($q, 30);

		return $this->_json(array(
			'results' => $rows
		));
	}

	public function saveForm()
	{
		$id = (int) $this->input->post('id');

		$payload = $this->_buildScheduleDirectPayloadFromForm($id > 0);
		
		if (!$this->scheduleDirect_model->destinationExists($payload['city_id'])) {
			return $this->_json(array(
				'status' => false,
				'message' => 'Port of Destination tidak ada di master. Silakan pilih dari list.'
			));
		}

		if (!empty($payload['carrier']) && !$this->scheduleDirect_model->linerExists($payload['carrier'])) {
			return $this->_json(array(
				'status' => false,
				'message' => 'Carrier / Liner tidak ada di master. Silakan pilih dari list.'
			));
		}

		if (empty($payload['city_id']) || empty($payload['vessel']) || empty($payload['voy']) || empty($payload['etd_jkt']) || empty($payload['eta']) || empty($payload['stf_cls'])) {
			return $this->_json(array(
				'status' => false,
				'message' => 'Field wajib belum lengkap: destination, vessel, voyage, closing, ETD, ETA.'
			));
		}

		$this->dbx->trans_begin();

		if ($id > 0) {
			$payload['updatedBy']  = $this->session->userdata('fullname');
			$payload['updatedDtm'] = date('Y-m-d H:i:s');
			$resultId = $this->scheduleDirect_model->updateScheduleDirectSafe($payload, $id);
			$scheduleId = $id;
		} else {
			$payload['createdBy']  = $this->session->userdata('fullname');
			$payload['createdDtm'] = date('Y-m-d H:i:s');
			$payload['region_id']  = $this->session->userdata('region_id');
			$scheduleId = $this->scheduleDirect_model->createScheduleDirectSafe($payload);
			$resultId = $scheduleId;
		}

		if (!$resultId) {
			$this->dbx->trans_rollback();
			return $this->_json(array('status' => false, 'message' => 'Gagal menyimpan schedule.'));
		}

		$schedule = $this->scheduleDirect_model->getScheduleDirectById($scheduleId);
		if ($schedule) {
			$this->_syncScheduleDirectToBookingAndStuffing($scheduleId, $schedule);
		}

		if ($this->dbx->trans_status() === false) {
			$this->dbx->trans_rollback();
			return $this->_json(array('status' => false, 'message' => 'Database transaction failed.'));
		}

		$this->dbx->trans_commit();
		
		// ========================================================
		// TRIGGER TIMELINE AUDIT: SCHEDULING & DO UPLOAD
		// ========================================================
		$is_new_schedule = ($id == 0); // Jika $id 0, berarti ini proses Create Schedule[cite: 5]
		$do_file_token   = !empty($payload['do_file_token']) ? $payload['do_file_token'] : NULL; //[cite: 5]

		// Panggil fungsi model untuk mendistribusikan log ke semua Master Job terkait
		$this->load->model('Timeline_model'); 
		$this->Timeline_model->sync_schedule_events_to_master(
			$scheduleId, 
			'DIRECT / TRANSSHIPMENT', // Gunakan 'VIA' jika diletakkan di ScheduleVia.php[cite: 6]
			$is_new_schedule, 
			$do_file_token
		);
		// ========================================================

		$bookingCount = 0;
		if (method_exists($this->scheduleDirect_model, 'countBookingsByScheduleId')) {
			$bookingCount = (int) $this->scheduleDirect_model->countBookingsByScheduleId($scheduleId);
		}

		$bcResult = array('sent' => 0, 'skipped' => true);
		if ($id > 0 && $bookingCount > 0 && $this->input->post('send_bc_update') == '1') {
			$bcResult = $this->_sendScheduleUpdateBookingConfirmation($scheduleId);
		}

		$month = $this->input->post('month');
		if (!empty($month)) {
			$this->session->set_userdata(array('month' => str_pad((int)$month, 2, '0', STR_PAD_LEFT)));
		}

		return $this->_json(array(
			'status' => true,
			'message' => ($id > 0 ? 'Schedule berhasil diupdate.' : 'Schedule berhasil dibuat.'),
			'schedule_id' => $scheduleId,
			'booking_count' => $bookingCount,
			'bc_result' => $bcResult,
		));
	}
	
	private function _buildScheduleDirectPayloadFromForm($isUpdate = false)
{
    $cols = array(
        'city_id', 'vessel', 'voy', 'stf_cls', 'etd_jkt', 'eta',
        'stuffing_date', 'stuffing_location', 'booking_number',

        'si_no', 'mbl_no', 'do_date',
        'pol', 'pod', 'final_destination', 'transit_port', 'transit_terminal',
        'terminal', 'pickup_depot', 'full_return_cy', 'empty_pickup_at',
        'open_stack_at', 'cargo_closing_at', 'doc_closing_at', 'vgm_cutoff_at', 'si_cutoff_at',
        'container_qty', 'container_type_raw',
        'commodity', 'gross_weight', 'cargo_type', 'is_dg',
        'dg_details', 'remarks',
        'do_file_token', 'do_raw_text', 'do_extracted_json', 'schedule_kind_hint'
    );

    $payload = array();

    foreach ($cols as $col) {
        $val = $this->input->post($col);

        if ($val === null) {
            continue;
        }

        if (is_array($val)) {
            $val = json_encode($val, JSON_UNESCAPED_UNICODE);
        }

        $payload[$col] = trim((string) $val);
    }

    // form pakai liner_id, table pakai carrier
    $linerId = $this->input->post('liner_id');
    if ($linerId !== null) {
        $payload['carrier'] = strtoupper(trim((string) $linerId));
    }

    // Uppercase field text utama
    $upperCols = array(
        'city_id', 'vessel', 'voy', 'stuffing_location', 'booking_number',
        'si_no', 'mbl_no', 'pol', 'pod', 'final_destination', 'transit_port',
        'transit_terminal', 'terminal', 'pickup_depot', 'full_return_cy',
        'container_type_raw', 'commodity', 'gross_weight', 'cargo_type',
        'dg_details', 'remarks', 'schedule_kind_hint'
    );

    foreach ($upperCols as $col) {
        if (array_key_exists($col, $payload) && $payload[$col] !== '') {
            $payload[$col] = strtoupper(trim((string) $payload[$col]));
        }
    }

    // container_type wajib JSON valid, contoh ["40 HC"]
    $payload['container_type'] = $this->_build_container_type_json_from_post();

    // do_extracted_json kosong bikin CHECK json_valid gagal
    if (array_key_exists('do_extracted_json', $payload)) {
        $json = trim((string) $payload['do_extracted_json']);

        if ($json === '') {
            if ($isUpdate) {
                unset($payload['do_extracted_json']);
            } else {
                $payload['do_extracted_json'] = null;
            }
        } else {
            json_decode($json, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $payload['do_extracted_json'] = json_encode(array(), JSON_UNESCAPED_UNICODE);
            } else {
                $payload['do_extracted_json'] = $json;
            }
        }
    }

    // Optional fields kosong jadi NULL saat create, dan tidak overwrite saat update
    $nullableCols = array(
        'si_no', 'mbl_no', 'do_date',
        'pol', 'pod', 'final_destination', 'transit_port', 'transit_terminal',
        'terminal', 'pickup_depot', 'full_return_cy', 'empty_pickup_at',
        'open_stack_at', 'cargo_closing_at', 'doc_closing_at', 'vgm_cutoff_at', 'si_cutoff_at',
        'container_qty', 'container_type_raw',
        'commodity', 'gross_weight', 'cargo_type',
        'dg_details', 'remarks',
        'do_file_token', 'do_raw_text', 'schedule_kind_hint'
    );

    foreach ($nullableCols as $col) {
        if (array_key_exists($col, $payload) && trim((string) $payload[$col]) === '') {
            if ($isUpdate) {
                unset($payload[$col]);
            } else {
                $payload[$col] = null;
            }
        }
    }

    if (!$isUpdate && empty($payload['region_id'])) {
        $payload['region_id'] = $this->session->userdata('region_id');
    }

    return $payload;
}
	
	private function _toDateOnly($value)
	{
		$value = trim((string) $value);

		if ($value === '' || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
			return '';
		}

		// Kalau sudah YYYY-MM-DD atau YYYY-MM-DD HH:II:SS.
		if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $value, $m)) {
			return $m[1] . '-' . $m[2] . '-' . $m[3];
		}

		// Kalau dari table lama sudah YYYYMMDD.
		if (preg_match('/^(\d{4})(\d{2})(\d{2})$/', $value, $m)) {
			return $m[1] . '-' . $m[2] . '-' . $m[3];
		}

		$ts = strtotime($value);
		if ($ts === false) {
			return '';
		}

		return date('Y-m-d', $ts);
	}

	private function _toCompactDate($value)
	{
		$date = $this->_toDateOnly($value);

		if ($date === '') {
			return '';
		}

		return str_replace('-', '', $date);
	}

	private function _toDateTimeStart($value)
	{
		$date = $this->_toDateOnly($value);

		if ($date === '') {
			return '';
		}

		return $date . ' 00:00:00';
	}


	private function _syncScheduleDirectToBookingAndStuffing($scheduleId, $schedule)
	{
		$scheduleId = (int) $scheduleId;

		$cityId          = strtoupper(trim((string) ($schedule->city_id ?? '')));
		$vessel          = strtoupper(trim((string) ($schedule->vessel ?? '')));
		$voyage          = strtoupper(trim((string) ($schedule->voy ?? '')));
		$warehouse       = strtoupper(trim((string) ($schedule->stuffing_location ?? '')));
		$carrier         = strtoupper(trim((string) ($schedule->carrier ?? '')));
		$bookingNumber   = strtoupper(trim((string) ($schedule->booking_number ?? '')));

		$etdDate         = $this->_toDateOnly($schedule->etd_jkt ?? null);        // 2026-07-12
		$etaDate         = $this->_toDateOnly($schedule->eta ?? null);            // 2026-08-04
		$stuffingDate    = $this->_toDateOnly($schedule->stuffing_date ?? null);  // 2026-07-08
		$closingDate     = $this->_toDateOnly($schedule->stf_cls ?? null);        // 2026-07-08

		$etdCompact      = $this->_toCompactDate($etdDate);       // 20260712
		$etaCompact      = $this->_toCompactDate($etaDate);       // 20260804
		$stuffingCompact = $this->_toCompactDate($stuffingDate);  // 20260708
		$closingCompact  = $this->_toCompactDate($closingDate);   // 20260708

		$stuffingDateTime = $this->_toDateTimeStart($stuffingDate); // 2026-07-08 00:00:00

		$scac = '';
		if ($carrier !== '' && method_exists($this->scheduleDirect_model, 'getScacByCarrierCode')) {
			$scac = strtoupper(trim((string) $this->scheduleDirect_model->getScacByCarrierCode($carrier)));
		}

		/*
		 * 1. Update trnstuffingplan
		 * schedule_type harus DIRECT / TRANSSHIPMENT.
		 * Format tanggal di table ini varchar YYYYMMDD.
		 */
		$spUpdate = array(
			'etd_jkt'      => $etdCompact,
			'eta'          => $etaCompact,
			'vessel'       => $vessel,
			'voyage'       => $voyage,
			'warehouse'    => $warehouse,
			'carrier'      => $carrier,
			'destination'  => $cityId,
			'mother_bl'    => $bookingNumber,
			'stuffing_date'=> $stuffingCompact,
			'updated_by'   => $this->session->userdata('fullname'),
			'updated_at'   => date('Y-m-d H:i:s'),
		);

		if ($scac !== '') {
			$spUpdate['scac'] = $scac;
		}

		if ($closingCompact !== '') {
			$spUpdate['closing_date_time'] = $closingCompact;
		}

		// Jangan overwrite field tanggal dengan kosong.
		foreach ($spUpdate as $key => $val) {
			if ($val === null || $val === '') {
				unset($spUpdate[$key]);
			}
		}

		if (!empty($spUpdate)) {
			$this->dbx->where('schedule_id', $scheduleId);
			$this->dbx->where('schedule_type', 'DIRECT / TRANSSHIPMENT');
			$this->dbx->update('trnstuffingplan', $spUpdate);
			// DEBUGGING: Tulis query ke file log atau tampilkan (pilih salah satu)
    log_message('error', 'QUERY TRNSTUFFINGPLAN: ' . $this->dbx->last_query());
		}

		/*
		 * 2. Update trnstuffingplan_booking
		 * Format expect_etd / eta = YYYY-MM-DD.
		 * Format stuffing_date = YYYY-MM-DD 00:00:00.
		 */
		$bookingUpdate = array(
			'destination_city' => $cityId,
			'expect_etd'      => $etdDate,
			'eta'             => $etaDate,
			'vessel'          => $vessel,
			'voyage'          => $voyage,
			'updated_at'      => date('Y-m-d H:i:s'),
		);

		if ($stuffingDateTime !== '') {
			$bookingUpdate['stuffing_date'] = $stuffingDateTime;
		}

		// Kalau Bapak masih mau sinkron field lama juga, ini boleh tetap:
		if ($warehouse !== '') {
			$bookingUpdate['warehouse'] = $warehouse;
		}

		if ($closingDate !== '') {
			$bookingUpdate['closing_date'] = $closingDate . ' 00:00:00';
		}

		if ($stuffingDateTime !== '') {
			$bookingUpdate['closing_cargo'] = $stuffingDateTime;
		}

		foreach ($bookingUpdate as $key => $val) {
			if ($val === null || $val === '') {
				unset($bookingUpdate[$key]);
			}
		}

		if (!empty($bookingUpdate)) {
			$this->dbx->where('schedule_id', $scheduleId);

			// Di table booking field-nya route_type, bukan schedule_type.
			if ($this->dbx->field_exists('route_type', 'trnstuffingplan_booking')) {
				$this->dbx->where('route_type', 'DIRECT / TRANSSHIPMENT');
			}

			$this->dbx->update('trnstuffingplan_booking', $bookingUpdate);
		}
	}

	private function _sendScheduleUpdateBookingConfirmation($scheduleId)
	{
		/**
		 * TODO: Hubungkan ke fungsi BC existing.
		 * Yang penting trigger ini dipanggil setelah save sukses dan user centang send_bc_update.
		 * Contoh integrasi:
		 *   $this->load->library('Booking_confirmation_service');
		 *   return $this->booking_confirmation_service->sendByScheduleId($scheduleId, true);
		 */
		log_message('error', 'SCHEDULE_DIRECT_BC_UPDATE_REQUEST schedule_id=' . $scheduleId . ' by=' . $this->session->userdata('fullname'));
		return array('sent' => 0, 'skipped' => true, 'reason' => 'adapter_not_connected_yet');
	}

	private function _saveScheduleDoUpload($file)
	{
		$allowedExt = array('pdf', 'png', 'jpg', 'jpeg', 'tif', 'tiff');
		$original = isset($file['name']) ? $file['name'] : '';
		$ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));

		if (!in_array($ext, $allowedExt, true)) {
			return array('status' => false, 'message' => 'Format file tidak didukung. Gunakan PDF/JPG/PNG/TIF.');
		}

		if (!empty($file['error'])) {
			return array('status' => false, 'message' => 'Upload error code: ' . $file['error']);
		}

		$baseDir = FCPATH . 'uploads/schedule_do/';
		if (!is_dir($baseDir)) {
			@mkdir($baseDir, 0775, true);
		}

		$folder = date('Ymd_His') . '_' . mt_rand(1000, 9999);
		$targetDir = $baseDir . $folder . '/';
		if (!is_dir($targetDir)) {
			@mkdir($targetDir, 0775, true);
		}

		$safeName = preg_replace('/[^a-zA-Z0-9_\.\-]/', '_', $original);
		$target = $targetDir . $safeName;

		if (!move_uploaded_file($file['tmp_name'], $target)) {
			return array('status' => false, 'message' => 'Gagal menyimpan file upload DO.');
		}

		return array(
			'status' => true,
			'full_path' => $target,
			'original_name' => $original,
			'file_token' => $folder . '/' . $safeName,
		);
	}

	private function _json($arr)
	{
		$this->output
			->set_content_type('application/json')
			->set_output(json_encode($arr));
	}
 }
 ?>
