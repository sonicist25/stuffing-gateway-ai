<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class ScheduleVia extends CI_Controller
{
    var $title = 'Schedule Via';
    public $dbx;

    public function __construct()
    {
        parent::__construct();
        $this->fungsi->restrict();
        $this->fungsi->get_define();
        $this->load->model(array('schedule/scheduleVia_model', 'schedule/scheduleViaImport_model','Timeline_model' ));
        $this->fungsi->dbx = $this->scheduleVia_model->dbx;
        $this->dbx = $this->scheduleVia_model->dbx;
        $this->config->load('schedule_do', FALSE, TRUE);
    }

    public function index()
    {
        $this->fungsi->auth(100, 'Schedule');
        $data['title'] = 'ScheduleVia';
        $data['menu_navigation'] = 'navigation';
        $data['main_view'] = 'schedule/schedulesVia';
        $this->load->view('afg_template', $data);
    }

    public function scheduleViaListing()
    {
        $now = new DateTime('now');
        $monthDefault = $now->format('m');
        $yearDefault  = $this->session->userdata('db');

        $monthSearch = $this->input->post('month_via');
        $yearSearch  = $this->session->userdata('db');

        if (!empty($monthSearch) && !empty($yearSearch)) {
            $monthVia = str_pad((int) $monthSearch, 2, '0', STR_PAD_LEFT);
            $yearVia  = $yearSearch;
        } else {
            $monthVia = $monthDefault;
            $yearVia  = $yearDefault;
        }

        $this->session->set_userdata(array(
            'month_via' => $monthVia,
            'year_via'  => $yearVia,
        ));

        $data['scheduleViaRecords'] = $this->scheduleVia_model->ScheduleViaByCities($monthVia, $yearVia);
        $data['scheduleViaRecords_byCity'] = $this->scheduleVia_model->ScheduleViaListing_byCity($monthVia, $yearVia);
        $data['title'] = 'ScheduleVia';
        $data['menu_navigation'] = 'navigation';
        $data['main_view'] = 'schedule/schedulesVia';

        $this->load->view('afg_template', $data);
    }

    /** Backward compatible for old editable select. */
    public function getCities()
    {
        $query = $this->scheduleVia_model->getCities();
        $getCities = array();
        foreach ($query as $result) {
            $name = is_object($result) ? $result->name : (isset($result['name']) ? $result['name'] : '');
            $getCities[] = array('value' => $name, 'text' => $name);
        }
        return $this->_json($getCities);
    }

    public function searchDestinations()
    {
        $q = strtoupper(trim((string) $this->input->get('q')));
        $rows = $this->scheduleVia_model->searchDestinationOptions($q, 30);
        return $this->_json(array('results' => $rows));
    }

    public function searchLiners()
    {
        $q = strtoupper(trim((string) $this->input->get('q')));
        $rows = $this->scheduleVia_model->searchLinerOptions($q, 30);
        return $this->_json(array('results' => $rows));
    }

    /** Backward compatible old submit. New view uses saveForm(). */
    public function createScheduleVia()
    {
        $payload = $this->_buildScheduleViaPayloadFromForm(false);
        $payload['createdBy'] = $this->session->userdata('fullname');
        $payload['createdDtm'] = date('Y-m-d H:i:s');
        $payload['region_id'] = $this->session->userdata('region_id');

        $result = $this->scheduleVia_model->createScheduleViaSafe($payload);
        $this->session->set_flashdata($result > 0 ? 'success' : 'error', $result > 0 ? 'Schedule Via created successfully' : 'Schedule creation failed');

        $month = $this->input->post('month_via');
        if (empty($month)) {
            $month = $this->input->post('month');
        }
        if (!empty($month)) {
            $this->session->set_userdata(array('month_via' => str_pad((int) $month, 2, '0', STR_PAD_LEFT)));
        }

        redirect('schedule/ScheduleVia/scheduleViaListing');
    }

    /** Backward compatible old inline edit. New view uses saveForm(). */
    public function updateScheduleVia()
    {
        $name  = $this->input->post('name');
        $value = $this->input->post('value');
        $id    = (int) $this->input->post('pk');

        if ($id <= 0 || $name === '') {
            return $this->_json(array('status' => false, 'message' => 'Invalid request'));
        }

        $scheduleViaInfo = array(
            $name => is_string($value) ? strtoupper(trim($value)) : $value,
            'updatedBy' => $this->session->userdata('fullname'),
            'updatedDtm' => date('Y-m-d H:i:s'),
        );

        $result = $this->scheduleVia_model->updateScheduleViaSafe($scheduleViaInfo, $id);

        $schedule = $this->scheduleVia_model->getScheduleViaById($id);
        if ($schedule) {
            $this->_syncScheduleViaToBookingAndStuffing($id, $schedule);
        }

        return $this->_json(array('status' => (bool) $result));
    }

    public function getFormData()
    {
        $id = (int) $this->input->get('id');
        if ($id <= 0) {
            return $this->_json(array('status' => false, 'message' => 'Invalid schedule id'));
        }

        $schedule = $this->scheduleVia_model->getScheduleViaById($id);
        if (!$schedule) {
            return $this->_json(array('status' => false, 'message' => 'Schedule not found'));
        }

        $bookings = array();
        if (method_exists($this->scheduleVia_model, 'getBookingsByScheduleId')) {
            $bookings = $this->scheduleVia_model->getBookingsByScheduleId($id);
        }

        return $this->_json(array(
            'status' => true,
            'data' => array(
                'schedule' => $schedule,
                'bookings' => $bookings,
            ),
        ));
    }

    public function saveForm()
    {
        $id = (int) $this->input->post('id');
        $payload = $this->_buildScheduleViaPayloadFromForm($id > 0);

        $required = array('eta_city_id', 'liner_id', 'vessel', 'voy_vessel', 'stf_cls', 'etd_jkt', 'stuffing_date', 'stuffing_location', 'connecting', 'voy_con', 'etd_con', 'etd_city_con_id', 'eta');
        foreach ($required as $field) {
            if (empty($payload[$field])) {
                return $this->_json(array(
                    'status' => false,
                    'message' => 'Field wajib belum lengkap: ' . $field,
                ));
            }
        }

        $this->dbx->trans_begin();

        if ($id > 0) {
            $payload['updatedBy']  = $this->session->userdata('fullname');
            $payload['updatedDtm'] = date('Y-m-d H:i:s');
            $resultId = $this->scheduleVia_model->updateScheduleViaSafe($payload, $id);
            $scheduleId = $id;
        } else {
            $payload['createdBy']  = $this->session->userdata('fullname');
            $payload['createdDtm'] = date('Y-m-d H:i:s');
            $payload['region_id']  = $this->session->userdata('region_id');
            $scheduleId = $this->scheduleVia_model->createScheduleViaSafe($payload);
            $resultId = $scheduleId;
        }

        if (!$resultId) {
            $this->dbx->trans_rollback();
            return $this->_json(array('status' => false, 'message' => 'Gagal menyimpan schedule via.'));
        }

        $schedule = $this->scheduleVia_model->getScheduleViaById($scheduleId);
        if ($schedule) {
            $this->_syncScheduleViaToBookingAndStuffing($scheduleId, $schedule);
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
			'VIA', // Gunakan 'VIA' jika diletakkan di ScheduleVia.php[cite: 6]
			$is_new_schedule, 
			$do_file_token
		);
		// ========================================================

        $bookingCount = 0;
        if (method_exists($this->scheduleVia_model, 'countBookingsByScheduleId')) {
            $bookingCount = (int) $this->scheduleVia_model->countBookingsByScheduleId($scheduleId);
        }

        $bcResult = array('sent' => 0, 'skipped' => true);
        if ($id > 0 && $bookingCount > 0 && $this->input->post('send_bc_update') == '1') {
            $bcResult = $this->_sendScheduleUpdateBookingConfirmation($scheduleId);
        }

        $month = $this->input->post('month_via');
        if (empty($month)) {
            $month = $this->input->post('month');
        }
        if (!empty($month)) {
            $this->session->set_userdata(array('month_via' => str_pad((int) $month, 2, '0', STR_PAD_LEFT)));
        }

        return $this->_json(array(
            'status' => true,
            'message' => ($id > 0 ? 'Schedule Via berhasil diupdate.' : 'Schedule Via berhasil dibuat.'),
            'schedule_id' => $scheduleId,
            'booking_count' => $bookingCount,
            'bc_result' => $bcResult,
        ));
    }

    public function deleteScheduleVia()
    {
        $id = (int) $this->input->post('id');
        if ($id <= 0) {
            return $this->_json(array('status' => false, 'message' => 'Invalid id'));
        }

        $payload = array(
            'isDeleted' => 1,
            'updatedBy' => $this->session->userdata('fullname'),
            'updatedDtm' => date('Y-m-d H:i:s'),
        );

        $result = $this->scheduleVia_model->deleteScheduleVia($id, $payload);
        return $this->_json(array('status' => $result > 0));
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
        $debugMode = true;

        $result = $this->schedule_do_extractor->extract(
            $upload['full_path'],
            $upload['original_name'],
            $debugMode
        );

        $rawText = isset($result['raw_text']) ? (string) $result['raw_text'] : '';

        $response = array(
            'status' => true,
            'message' => 'OCR selesai. Tetap review manual sebelum save.',
            'file_token' => $upload['file_token'],
            'raw_text' => $rawText,
            'fields' => isset($result['fields']) ? $result['fields'] : array(),
            'missing_fields' => isset($result['missing_fields']) ? $result['missing_fields'] : array(),
            'debug' => isset($result['debug']) ? $result['debug'] : array(),
        );

        $response['debug']['upload'] = array(
            'full_path' => $upload['full_path'],
            'original_name' => $upload['original_name'],
            'file_token' => $upload['file_token'],
            'php_user' => function_exists('get_current_user') ? get_current_user() : '',
            'sys_temp_dir' => sys_get_temp_dir(),
            'temp_writable' => is_writable(sys_get_temp_dir()) ? 1 : 0,
        );

        if (strlen(trim($rawText)) < 20) {
            $response['status'] = false;
            $response['message'] = 'Extract gagal: raw_text masih kosong. Lihat debug untuk cek pdftotext/pdftoppm/tesseract/permission.';
            $response['warning'] = 'Untuk PDF, server wajib punya poppler-utils: pdftotext dan pdftoppm. Tesseract saja tidak cukup untuk membaca PDF langsung.';
        }

        return $this->_json($response);
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

    private function _buildScheduleViaPayloadFromForm($isUpdate = false)
    {
        $cols = array(
            'liner_id', 'vessel', 'voy_vessel', 'stf_cls', 'etd_jkt', 'stuffing_date', 'stuffing_location',
            'connecting', 'voy_con', 'etd_con', 'etd_city_con_id', 'eta', 'eta_city_id',
            'booking_number', 'si_no', 'mbl_no', 'do_date', 'pol', 'pod', 'final_destination', 'transit_port', 'transit_terminal',
            'terminal', 'pickup_depot', 'full_return_cy', 'empty_pickup_at', 'open_stack_at',
            'cargo_closing_at', 'doc_closing_at', 'vgm_cutoff_at', 'si_cutoff_at',
            'container_qty', 'container_type_raw', 'commodity', 'gross_weight', 'cargo_type', 'is_dg',
            'dg_details', 'remarks', 'do_file_token', 'do_raw_text', 'do_extracted_json', 'schedule_kind_hint'
        );

        $payload = array();
        foreach ($cols as $col) {
            $val = $this->input->post($col);
            if (is_array($val)) {
                $val = json_encode($val, JSON_UNESCAPED_UNICODE);
            }
            if ($val !== null) {
                $payload[$col] = $this->_normalizePostValue($col, $val);
            }
        }

        if (!$isUpdate && empty($payload['region_id'])) {
            $payload['region_id'] = $this->session->userdata('region_id');
        }
		$payload['container_type'] = $this->_build_container_type_json_from_post();
		
		// do_extracted_json tidak boleh string kosong karena ada CHECK json_valid
		if (array_key_exists('do_extracted_json', $payload)) {
			$json = trim((string) $payload['do_extracted_json']);

			if ($json === '') {
				if ($isUpdate) {
					// Saat update, jangan overwrite OCR lama dengan kosong
					unset($payload['do_extracted_json']);
				} else {
					// Saat create tanpa OCR, simpan NULL agar lolos CHECK
					$payload['do_extracted_json'] = null;
				}
			} else {
				json_decode($json, true);

				if (json_last_error() !== JSON_ERROR_NONE) {
					// Fallback aman kalau isi JSON rusak
					$payload['do_extracted_json'] = json_encode(array(), JSON_UNESCAPED_UNICODE);
				} else {
					$payload['do_extracted_json'] = $json;
				}
			}
		}
		
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

        return $payload;
    }

    private function _normalizePostValue($col, $val)
    {
        $val = trim((string) $val);

        if ($col === 'do_raw_text' || $col === 'do_extracted_json' || $col === 'do_file_token') {
            return $val;
        }

        if (in_array($col, array('stf_cls', 'etd_jkt', 'stuffing_date', 'etd_con', 'eta', 'do_date'), true)) {
            return $this->_toDateOnly($val);
        }

        if (in_array($col, array('empty_pickup_at', 'open_stack_at', 'cargo_closing_at', 'doc_closing_at', 'vgm_cutoff_at', 'si_cutoff_at'), true)) {
            return $this->_toDateTime($val);
        }

        return strtoupper($val);
    }

    private function _syncScheduleViaToBookingAndStuffing($scheduleId, $schedule)
    {
        $scheduleId = (int) $scheduleId;

        $destination     = strtoupper(trim((string) (isset($schedule->eta_city_id) ? $schedule->eta_city_id : '')));
        $transitPort     = strtoupper(trim((string) (isset($schedule->etd_city_con_id) ? $schedule->etd_city_con_id : '')));
        $vessel          = strtoupper(trim((string) (isset($schedule->vessel) ? $schedule->vessel : '')));
        $voyage          = strtoupper(trim((string) (isset($schedule->voy_vessel) ? $schedule->voy_vessel : '')));
        $connecting      = strtoupper(trim((string) (isset($schedule->connecting) ? $schedule->connecting : '')));
        $voyCon          = strtoupper(trim((string) (isset($schedule->voy_con) ? $schedule->voy_con : '')));
        $warehouse       = strtoupper(trim((string) (isset($schedule->stuffing_location) ? $schedule->stuffing_location : '')));
        $carrier         = strtoupper(trim((string) (isset($schedule->liner_id) ? $schedule->liner_id : '')));
        $bookingNumber   = strtoupper(trim((string) (isset($schedule->booking_number) ? $schedule->booking_number : '')));

        $etdDate         = $this->_toDateOnly(isset($schedule->etd_jkt) ? $schedule->etd_jkt : '');
        $etaDate         = $this->_toDateOnly(isset($schedule->eta) ? $schedule->eta : '');
        $stuffingDate    = $this->_toDateOnly(isset($schedule->stuffing_date) ? $schedule->stuffing_date : '');
        $closingDate     = $this->_toDateOnly(isset($schedule->stf_cls) ? $schedule->stf_cls : '');
        $etdConDate      = $this->_toDateOnly(isset($schedule->etd_con) ? $schedule->etd_con : '');

        $scac = '';
        if ($carrier !== '' && method_exists($this->scheduleVia_model, 'getScacByCarrierCode')) {
            $scac = strtoupper(trim((string) $this->scheduleVia_model->getScacByCarrierCode($carrier)));
        }

        $spUpdate = array(
            'etd_jkt'           => $this->_toCompactDate($etdDate),
            'eta'               => $this->_toCompactDate($etaDate),
            'stuffing_date'     => $this->_toCompactDate($stuffingDate),
            'closing_date_time' => $this->_toCompactDate($closingDate),
            'vessel'            => $vessel,
            'voyage'            => $voyage,
            'warehouse'         => $warehouse,
            'carrier'           => $carrier,
            'destination'       => $destination,
            'transhipment_port' => $transitPort,
            'mother_bl'         => $bookingNumber,
            'scac'              => $scac,
            'connecting'        => $connecting,
            'voy_con'           => $voyCon,
            'etd_con'           => $this->_toCompactDate($etdConDate),
            'updated_by'        => $this->session->userdata('fullname'),
            'updated_at'        => date('Y-m-d H:i:s'),
        );

        $spUpdate = $this->_filterExistingColumns('trnstuffingplan', $this->_removeEmptyValues($spUpdate));
        if (!empty($spUpdate)) {
            $this->dbx->where('schedule_id', $scheduleId);
            $this->dbx->where('schedule_type', 'VIA');
            $this->dbx->update('trnstuffingplan', $spUpdate);
        }

        $bookingUpdate = array(
            'destination_city' => $destination,
            'expect_etd'      => $etdDate,
            'eta'             => $etaDate,
            'vessel'          => $vessel,
            'voyage'          => $voyage,
            'stuffing_date'   => $this->_toDateTimeStart($stuffingDate),
            'warehouse'       => $warehouse,
            'closing_date'    => $this->_toDateTimeStart($closingDate),
            'closing_cargo'   => $this->_toDateTimeStart($stuffingDate),
            'transhipment_port' => $transitPort,
            'connecting'      => $connecting,
            'voy_con'         => $voyCon,
            'etd_con'         => $etdConDate,
            'updated_at'      => date('Y-m-d H:i:s'),
        );

        $bookingUpdate = $this->_filterExistingColumns('trnstuffingplan_booking', $this->_removeEmptyValues($bookingUpdate));
        if (!empty($bookingUpdate)) {
            $this->dbx->where('schedule_id', $scheduleId);
            if ($this->dbx->field_exists('route_type', 'trnstuffingplan_booking')) {
                $this->dbx->where('route_type', 'VIA');
            }
            $this->dbx->update('trnstuffingplan_booking', $bookingUpdate);
        }
    }

    private function _sendScheduleUpdateBookingConfirmation($scheduleId)
    {
        log_message('error', 'SCHEDULE_VIA_BC_UPDATE_REQUEST schedule_id=' . (int) $scheduleId . ' by=' . $this->session->userdata('fullname'));
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

    private function _removeEmptyValues($payload)
    {
        $out = array();
        foreach ((array) $payload as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $out[$key] = $value;
        }
        return $out;
    }

    private function _filterExistingColumns($table, $payload)
    {
        $out = array();
        foreach ((array) $payload as $col => $val) {
            if ($this->dbx->field_exists($col, $table)) {
                $out[$col] = $val;
            }
        }
        return $out;
    }

    private function _toDateOnly($value)
    {
        $value = trim((string) $value);
        if ($value === '' || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
            return '';
        }

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $value, $m)) {
            return $m[1] . '-' . $m[2] . '-' . $m[3];
        }

        if (preg_match('/^(\d{4})(\d{2})(\d{2})$/', $value, $m)) {
            return $m[1] . '-' . $m[2] . '-' . $m[3];
        }

        $ts = strtotime($value);
        return $ts === false ? '' : date('Y-m-d', $ts);
    }

    private function _toCompactDate($value)
    {
        $date = $this->_toDateOnly($value);
        return $date === '' ? '' : str_replace('-', '', $date);
    }

    private function _toDateTimeStart($value)
    {
        $date = $this->_toDateOnly($value);
        return $date === '' ? '' : $date . ' 00:00:00';
    }

    private function _toDateTime($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }
        if (preg_match('/^(\d{4}-\d{2}-\d{2})\s+(\d{2}:\d{2})(?::\d{2})?$/', $value, $m)) {
            return $m[1] . ' ' . $m[2] . ':00';
        }
        $ts = strtotime($value);
        return $ts === false ? '' : date('Y-m-d H:i:s', $ts);
    }

    private function _upper_post($name)
    {
        return strtoupper(trim((string) $this->input->post($name)));
    }

    private function _json($arr)
    {
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($arr));
    }

    public function scheduleViaListingImport()
    {
        $now = new DateTime('now');
        $monthDefault = $now->format('m');
        $yearDefault = $now->format('Y');
        $monthVia = !empty($this->input->post('month_via')) ? $this->input->post('month_via') : $monthDefault;
        $yearVia = !empty($this->input->post('year_via')) ? $this->input->post('year_via') : $yearDefault;

        $this->session->set_userdata(array('month_via' => $monthVia, 'year_via' => $yearVia));
        $data['scheduleViaRecordsImport'] = $this->scheduleViaImport_model->ScheduleViaImportByCities();
        $data['scheduleViaRecordsImport_byCity'] = $this->scheduleViaImport_model->ScheduleViaListingImport_byCity($monthVia, $yearVia);
        $data['cities'] = $this->scheduleViaImport_model->getCities();
        $data['title'] = 'ScheduleViaImport';
        $data['menu_navigation'] = 'navigation';
        $data['main_view'] = 'schedule/schedulesViaImport';
        $this->load->view('afg_template', $data);
    }

    public function createScheduleViaImport()
    {
        $month = $this->input->post('month');
        $year = $this->input->post('year');

        $scheduleViaInfo = array(
            'vessel' => $this->_upper_post('vessel'),
            'voy_vessel' => $this->_upper_post('voy_vessel'),
            'cut_off' => $this->input->post('stf_cls'),
            'etd' => $this->input->post('etd_jkt'),
            'connecting' => $this->_upper_post('connecting'),
            'voy_con' => $this->_upper_post('voy_con'),
            'eta_con' => $this->input->post('etd_con'),
            'etd_city_con_id' => $this->_upper_post('etd_city_con_id'),
            'eta_jkt' => $this->input->post('eta'),
            'eta_city_id' => $this->_upper_post('eta_city_id'),
            'createdBy' => $this->session->userdata('fullname'),
            'createdDtm' => date('Y-m-d H:i:s'),
            'region_id' => $this->session->userdata('region_id'),
        );

        $result = $this->scheduleViaImport_model->createScheduleViaImport($scheduleViaInfo);
        $this->session->set_flashdata($result > 0 ? 'success' : 'error', $result > 0 ? 'Schedule Via created successfully' : 'Contact creation failed');
        $this->session->set_userdata(array('month_create_via' => $month, 'year_create_via' => $year, 'city_via' => $scheduleViaInfo['eta_city_id']));
        redirect('schedule/ScheduleVia/scheduleViaListingImport');
    }

    public function updateScheduleViaImport()
    {
        $name = $this->input->post('name');
        $value = $this->input->post('value');
        $id = $this->input->post('pk');
        $scheduleViaInfo = array(
            $name => $value,
            'updatedBy' => $this->session->userdata('fullname'),
            'updatedDtm' => date('Y-m-d H:i:s'),
        );
        $this->scheduleViaImport_model->updateScheduleVia($scheduleViaInfo, $id);
    }

    public function deleteScheduleViaImport()
    {
        $id = $this->input->post('id');
        $scheduleViaInfo = array('isDeleted' => 1, 'updatedBy' => $this->session->userdata('fullname'), 'updatedDtm' => date('Y-m-d H:i:s'));
        $result = $this->scheduleVia_model->deleteScheduleVia($id, $scheduleViaInfo);
        return $this->_json(array('status' => $result > 0));
    }

    public function pageNotFound()
    {
        $this->global['pageTitle'] = 'CodeInsect : 404 - Page Not Found';
        $this->loadViews('404', $this->global, NULL, NULL);
    }
}
