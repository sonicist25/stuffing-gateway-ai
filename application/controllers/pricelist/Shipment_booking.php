<?php
class Shipment_booking extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->library('fungsi');
		$this->fungsi->restrict();
		$this->load->model(array('pricelist/booking_model','user_model','reference/stuffing_location_model','afg_m_detail','reference/consignee_notify_model','afg_m_cities','afg_m_shipment_request'));
	}
	
	function index() 
	{
		//$this->fungsi->auth(61,'Booking List');
		$data = array( 'menu_navigation' => 'navigation', 
							'main_view' => 'pricelist/v_shipment_booking',
							'title' => 'Shipment Booking',
							'rowList'=>'',
		);
		$this->load->view('afg_template', $data);
	}
	
	function displayShipmentBookedFiltered(){
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
		$sord = isset($_POST['sord'])?$_POST['sord']:'';
				
		$start = $batas * $hal - $batas;
		$start = ($start<0)?0:$start;			
		$_POST['start'] = $start;
		
		// Memanggil method baru di model khusus untuk status "booked"
		$q = $this->booking_model->get_all_booked_order($_POST,false); 
		$query = $this->booking_model->get_all_booked_order($_POST,true); 
		
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
			$jobNo = trim((string)($row->job_no ?? ''));
			$destination = trim((string)($row->destination ?? ''));

			// Grouping: NOMOR JOB - DESTINATION
			$jobGroup = ($jobNo !== '' ? $jobNo : 'NO JOB')
					  . ' - '
					  . $destination;

			$data->rows[$i]['id'] = $row->id;
            $data->rows[$i]['cell']=array(
                $row->real_id,             // 1. Booking Code
                $row->house_bl,            // 2. Nomor HBL
                $row->mode,                // 3. Mode
                $row->si_number,           // 4. SI Number
                $row->expect_etd,          // 5. ETD
                $row->shipper_name,        // 6. Shipper
                $row->destination_city,    // 7. Destination City
                $row->destination,         // 8. Consol Destination (real_dest)
                $row->consignee_name,      // 9. Consignee
                $row->notify_name,         // 10. Notify
                $row->quantity.' '.$row->package_type, // 11. Party
                $row->description_print,   // 12. Description
                $row->weight,              // 13. Weight
                $row->volume,              // 14. Volume
                $row->shipping_mark,       // 15. Marking
                $row->incoterm,            // 16. Incoterm
                $row->warehouse,           // 17. Warehouse
                $row->number_of_bl,        // 19. Number of BL
                $row->pic_document??"Online", // 20. Prepared
				$jobGroup,                                  // 21. Hidden Group
				$row->job_no ?? '',
				$this->_titipan_owner($row)                // Hidden: cabang pemilik bila titipan coload
            );
            $i++;
        }
		echo json_encode($data);
	}
	
	function displayShipmentRequestFiltered(){
	 	$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
		$sord = isset($_POST['sord'])?$_POST['sord']:'';
				
		$start = $batas * $hal - $batas;
		$start = ($start<0)?0:$start;			
		$_POST['start'] = $start;
		
		$q = $this->booking_model->get_all_finalize_order($_POST,false); 
		$query = $this->booking_model->get_all_finalize_order($_POST,true); 
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
                $row->real_id,             // 1. Booking Code
                $row->house_bl,            // 2. Nomor HBL
                $row->mode,                // 3. Mode
                $row->si_number,           // 4. SI Number
                $row->expect_etd,          // 5. ETD
                $row->shipper_name,        // 6. Shipper
                $row->destination_city,    // 7. Destination City
                $row->destination,         // 8. Consol Destination (real_dest)
                $row->consignee_name,      // 9. Consignee
                $row->notify_name,         // 10. Notify
                $row->quantity.' '.$row->package_type, // 11. Party
                $row->description_print,   // 12. Description
                $row->weight,              // 13. Weight
                $row->volume,              // 14. Volume
                $row->shipping_mark,       // 15. Marking
                $row->incoterm,            // 16. Incoterm
                $row->warehouse,           // 17. Warehouse
                $row->number_of_bl,        // 19. Number of BL
                $row->pic_document??"Online", // 20. Prepared
				'', // job_group jika ada
				'', // job_no
				$this->_titipan_owner($row)  // Hidden: cabang pemilik bila titipan coload
            );
            $i++;
        }
		//print_r($this->dbx->last_query());
		//die();
		echo json_encode($data);
	}
	
	public function save_booking()
	{
		$this->load->helper('transvoyant');
		$this->load->library('Transvoyant_client');
		$this->load->library('Transvoyant_sync_service');
		$this->load->model('Transvoyant_event_log_model');
		$this->load->model('Transvoyant_shipment_sync_model');

		$data = $this->booking_model->save_booking($_POST);

		$stuffing_plan   = $this->input->post('stuffing_plan');
		$coloader        = $this->input->post('coloader');
		$region_id       = $this->session->userdata('region_id');
		$shipper_id      = $this->input->post('shipper_id');
		$tracking_number = $this->input->post('si_number');
		$status          = isset($data['status']) ? (int)$data['status'] : 0;

		/**
		 * =========================================================
		 * 1. COLOAD
		 * =========================================================
		 * Coload ke cabang Gateway lain: cukup kolom stuffing_region di booking ini
		 * (diisi Booking_model). Panggilan HTTP lama ke /api/coload_gateway dengan
		 * key 'gateway-fms' dihapus: dulu tidak pernah jalan (form tidak mengirim
		 * stuffing_plan/coloader), dan sekarang akan membuat booking ganda.
		 */

		/**
		 * =========================================================
		 * 2. PILIH SHIPMENT FIRMENICH
		 * =========================================================
		 */
		$tvResult   = null;
		$tvSyncInfo = null;

		list($eligible, $eligibleMessage) = $this->transvoyant_sync_service->is_eligible_for_sync(
			$status,
			$shipper_id,
			$this->input->post(),
			$data
		);

		if ($eligible) {
			$tvSyncInfo = $this->transvoyant_sync_service->ensure_sync_from_booking(
				$this->input->post(),
				$data
			);

			if (!empty($tvSyncInfo['success'])) {
				$syncRow    = $tvSyncInfo['data'];
				$syncId     = $syncRow['id'];
				$bookingId  = $syncRow['booking_id'];
				$trackNo    = $syncRow['tracking_number'];

				$locationCode = $this->_get_transvoyant_location_code($region_id);
				$locationName = $this->_get_transvoyant_location_name($region_id);

				$payload = [
					'trackingNumber' => $trackNo,
					'code'           => 'INFO',
					'description'    => 'Booking Confirmation',
					'timestamp'      => tv_now_iso8601('UTC'),
					'location'       => [
						'code' => $locationCode,
						'name' => $locationName,
					],
				];

				$eventTimestampMysql = tv_iso8601_to_mysql($payload['timestamp']);

				$alreadySent = $this->Transvoyant_event_log_model->exists_success_event(
					$payload['trackingNumber'],
					$payload['code'],
					$payload['description'],
					$eventTimestampMysql
				);

				if (!$alreadySent) {
					$logId = $this->Transvoyant_event_log_model->insert_log([
						'sync_id'           => $syncId,
						'booking_id'        => $bookingId,
						'reference_no'      => $trackNo,
						'tracking_number'   => $payload['trackingNumber'],
						'event_key'         => 'booking_confirmation',
						'event_code'        => $payload['code'],
						'event_description' => $payload['description'],
						'event_timestamp'   => $eventTimestampMysql,
						'endpoint'          => '/dsm/v1/data/eco/shipments/events',
						'request_payload'   => json_encode($payload),
						'status'            => 'PENDING',
						'source_system'     => 'local',
						'source_ref'        => 'save_booking',
					]);

					$tvResult = $this->transvoyant_client->post_event($payload);

					$this->Transvoyant_event_log_model->update_log($logId, [
						'response_http_code' => $tvResult['http_code'] ?? null,
						'response_body'      => $tvResult['body'] ?? ($tvResult['message'] ?? null),
						'status'             => !empty($tvResult['success']) ? 'SUCCESS' : 'FAILED',
						'sent_at'            => !empty($tvResult['success']) ? date('Y-m-d H:i:s') : null,
					]);

					if (!empty($tvResult['success'])) {
						$nextPollAt = $this->transvoyant_sync_service->mark_started_after_bc($syncId);
						$tvResult['next_poll_at'] = $nextPollAt;
					}
				} else {
					$tvResult = [
						'success' => true,
						'message' => 'Booking Confirmation already sent',
						'skipped' => true,
					];
				}
			} else {
				$tvResult = $tvSyncInfo;
			}
		} else {
			$tvResult = [
				'success' => false,
				'message' => $eligibleMessage,
				'skipped' => true,
			];
		}

		$data['transvoyant'] = $tvResult;
		$data['transvoyant_sync'] = $tvSyncInfo;

		echo json_encode($data);
	}
	
	/**
	 * Booking titipan coload dari cabang lain (di-stuffing di cabang ini)?
	 * @return string nama cabang pemilik, atau '' bila milik sendiri
	 */
	private function _titipan_owner($row)
	{
		$region = trim((string) $this->session->userdata('region_id'));
		$owner  = trim((string) ($row->owner_region ?? ''));
		return ($owner !== '' && $region !== '' && strcasecmp($owner, $region) !== 0) ? $owner : '';
	}

	private function _get_transvoyant_location_code($region_id)
	{
		$map = [
			1 => 'IDJKT',
			2 => 'IDSUB',
			3 => 'IDCKR',
			4 => 'IDSRG',
			5 => 'IDMES',
			6 => 'IDBDO',
		];

		return $map[$region_id] ?? 'IDJKT';
	}

	private function _get_transvoyant_location_name($region_id)
	{
		$map = [
			1 => 'Jakarta',
			2 => 'Surabaya',
			3 => 'Cikarang',
			4 => 'Semarang',
			5 => 'Medan',
			6 => 'Bandung',
		];

		return $map[$region_id] ?? 'Jakarta';
	}
	
	
public function upload_si_document() {
    $upload_dir = APPPATH.'third_party/file/si_documents/';
    
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $tmp_name = $_FILES['file']['tmp_name'];
        $original_name = basename($_FILES['file']['name']);
        $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

        if ($ext !== 'pdf') {
            echo json_encode(array('status' => false, 'error' => 'Upload ditolak! Hanya PDF.'));
            return;
        }

        $new_name = md5(time() . $original_name) . '.pdf';
        $destination = $upload_dir . $new_name;

        if (move_uploaded_file($tmp_name, $destination)) {
            // Berhasil! Kembalikan nama file baru ke javascript
            echo json_encode(array(
                'status'    => true, 
                'file_name' => $new_name
            ));
        } else {
            echo json_encode(array('status' => false, 'error' => 'Gagal menyimpan file.'));
        }
    } else {
        echo json_encode(array('status' => false, 'error' => 'File tidak valid.'));
    }
}
	
	public function edit_booking()
	{
		$this->load->helper('transvoyant');
		$this->load->library('Transvoyant_sync_service');
		$this->load->model('Transvoyant_shipment_sync_model');

		$postData = $this->input->post();

		// Normalisasi schedule_id dari form edit. Nilai kosong / 0 bukan berarti
		// user ingin melepas schedule; kondisi ini biasanya terjadi saat toolbar
		// schedule di halaman edit belum ter-load. Model akan mempertahankan
		// schedule_id existing jika key ini tidak dikirim.
		if (array_key_exists('schedule_id', $postData)) {
			$rawScheduleId = trim((string) $postData['schedule_id']);

			if ($rawScheduleId !== '' && ctype_digit($rawScheduleId) && (int) $rawScheduleId > 0) {
				$postData['schedule_id'] = (int) $rawScheduleId;
			} else {
				unset($postData['schedule_id']);
			}
		}

		$result   = $this->booking_model->edit_booking($postData);

		$status    = isset($result['status']) ? (int)$result['status'] : 0;
		$shipperId = $postData['shipper_id'] ?? ($result['shipper_id'] ?? null);

		$tvUpdate = null;

		list($eligible, $eligibleMessage) = $this->transvoyant_sync_service->is_eligible_for_sync(
			$status,
			$shipperId,
			$postData,
			$result
		);

		if ($eligible) {
			$trackingNumber = $this->transvoyant_sync_service->resolve_tracking_number($postData, $result);

			if (!empty($trackingNumber)) {
				$syncRow = $this->Transvoyant_shipment_sync_model->find_by_tracking_number($trackingNumber);

				if ($syncRow) {
					$oldHbl = $syncRow['hbl'] ?? null;
					$newHbl = $result['house_bl'] ?? null;

					$updateData = array_filter([
						'shipper_id'     => !empty($shipperId) ? (int)$shipperId : null,
						'hbl'            => $newHbl,
						'mbl'            => $result['mbl'] ?? null,
						'carrier'        => $result['carrier'] ?? null,
						'container_no'   => $result['container_no'] ?? null,
						'container_type' => $result['container_type'] ?? null,
						'vessel_name'    => $result['vessel'] ?? null,
						'voyage'         => $result['voyage'] ?? null,
						'pol'            => $result['pol'] ?? null,
						'pod'            => $result['pod'] ?? null,
						'last_error'     => null,
					], function ($v) {
						return $v !== null && $v !== '';
					});

					// kalau HBL berubah, aktifkan lagi polling sekarang juga
					if (!empty($newHbl) && $newHbl !== $oldHbl) {
						$updateData['sync_status']  = 'TRACKING_ACTIVE';
						$updateData['next_poll_at'] = date('Y-m-d H:i:s');
					}

					// kalau sebelumnya row masih NEW / STOPPED / ERROR, aktifkan lagi polling
					if (empty($updateData['next_poll_at']) && in_array(($syncRow['sync_status'] ?? ''), ['NEW', 'STOPPED', 'ERROR'], true)) {
						$updateData['sync_status']  = 'TRACKING_ACTIVE';
						$updateData['next_poll_at'] = date('Y-m-d H:i:s');
					}

					$this->Transvoyant_shipment_sync_model->update_sync($syncRow['id'], $updateData);

					$tvUpdate = [
						'success' => true,
						'message' => 'tv_shipment_syncs updated after edit_booking',
						'sync_id' => $syncRow['id'],
						'updated' => $updateData,
					];
				} else {
					$tvUpdate = $this->transvoyant_sync_service->ensure_sync_from_booking($postData, $result);

					if (!empty($tvUpdate['success']) && !empty($tvUpdate['sync_id'])) {
						$this->Transvoyant_shipment_sync_model->update_sync($tvUpdate['sync_id'], [
							'sync_status'  => 'TRACKING_ACTIVE',
							'next_poll_at' => date('Y-m-d H:i:s'),
							'last_error'   => null,
						]);
					}
				}
			} else {
				$tvUpdate = [
					'success' => false,
					'message' => 'Tracking number kosong saat update booking',
				];
			}
		} else {
			$tvUpdate = [
				'success' => false,
				'message' => $eligibleMessage,
				'skipped' => true,
			];
		}

		$result['transvoyant_sync_update'] = $tvUpdate;

		echo json_encode($result);
	}
	/**
	 * Simpan PEB / NPE saja (boleh dipakai saat status BOOKED).
	 */
	public function update_customs_doc()
	{
		$result = $this->booking_model->update_customs_doc($this->input->post());
		echo json_encode($result);
	}


	function cancel_booking()
	{
		/*
		 * Endpoint legacy ini DILARANG melakukan hard delete langsung karena
		 * akan bypass sinkronisasi WMS/outbox.
		 *
		 * Delete resmi hanya melalui:
		 * /pricelist/afg_c_booking/cancel_booking
		 * yang melakukan delete guard + WMS + local secara berurutan.
		 */
		$booking_code = trim((string) $this->input->post('booking_code', true));
		$guard = $this->booking_model->get_booking_delete_guard($booking_code);

		return $this->output
			->set_status_header(409)
			->set_content_type('application/json')
			->set_output(json_encode(array(
				'status'       => false,
				'booking_code' => $booking_code,
				'message'      => 'DIRECT_BOOKING_DELETE_DISABLED',
				'guard'        => $guard
			)));
	}
function get_data_form()
	{
		$shipper = $this->afg_m_detail->get_shipper($_POST);
		echo json_encode(array(
			'nama'=>$shipper->shipper_name,
			'alamat'=>$shipper->shipper_address,
			'id'=>$shipper->id_shipper,
			'sales'=>$shipper->sales,
			'cp_name'=>$shipper->cp_name,
			'cp_email'=>$shipper->cp_email,
		));
	}
function get_sales_name()
	{
		$sales_id = $_POST['sales_id'];
		$sales = $this->user_model->get_user_by_id($sales_id);
		echo json_encode(array(
			'nama_sales'=>$sales,
		));
	}
}
?>