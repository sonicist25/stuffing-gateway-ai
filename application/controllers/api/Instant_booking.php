<?php
use chriskacerguis\RestServer\RestController;

class Instant_booking extends RestController {

    public function __construct(){
        parent::__construct();

        // Seluruh customer portal memakai validator JWT yang sama.
        // Customer_auth membaca GCL_JWT_KEY / $config['gcl_jwt_key'],
        // memvalidasi token_version, user aktif, permission, dan akses shipper.
        $this->load->library('Customer_auth');

        $this->load->model(array(
            'api/m_instant_booking',
            'm_bill_of_ladding',
            'm_print_out_bl',
            'pricelist/booking_model'
        ));

        $this->dbx = $this->m_instant_booking->dbx;
    }
	
public function upload_si_post() {
        // Upload SI merupakan bagian dari proses pembuatan booking,
        // sehingga wajib memakai access token customer portal yang valid.
        $auth = $this->customer_auth->authenticate('booking.create');

        if (empty($auth['ok'])) {
            return $this->response([
                'status'  => false,
                'message' => $auth['message'] ?? 'Unauthorized'
            ], (int) ($auth['status'] ?? RestController::HTTP_UNAUTHORIZED));
        }

        $upload_dir = APPPATH.'third_party/file/si_documents/';
        
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $tmp_name = $_FILES['file']['tmp_name'];
            $original_name = basename($_FILES['file']['name']);
            $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

            if ($ext !== 'pdf') {
                return $this->response([
                    'status' => false, 
                    'error' => 'Upload ditolak! Hanya file PDF yang diperbolehkan.'
                ], 400);
            }

            $new_name = md5(time() . $original_name) . '.pdf';
            $destination = $upload_dir . $new_name;

            if (move_uploaded_file($tmp_name, $destination)) {
                return $this->response([
                    'status'    => true, 
                    'file_name' => $new_name,
                    'message'   => 'File berhasil disimpan'
                ], 200);
            }
        }
        
        return $this->response([
            'status' => false, 
            'error' => 'Gagal membaca atau menyimpan file'
        ], 400);
    }

public function index_post() {
        // --- 1. VALIDASI AUTHENTICATION TERPUSAT ---
        // Jangan decode JWT langsung di controller. Customer_auth memastikan
        // secret, token_version, status user, permission, dan akses shipper
        // selalu konsisten dengan Customer_login.
        $auth = $this->customer_auth->authenticate('booking.create');

        if (empty($auth['ok'])) {
            return $this->response([
                'status'  => false,
                'message' => $auth['message'] ?? 'Unauthorized'
            ], (int) ($auth['status'] ?? RestController::HTTP_UNAUTHORIZED));
        }

        // Mendukung customer yang mempunyai beberapa id_shipper.
        // Bila frontend tidak mengirim id_shipper, gunakan shipper default.
        $requestedShipperId = (int) ($this->post('id_shipper') ?: 0);
        $idShipperFromAuth = $this->customer_auth->resolveShipperId(
            $auth,
            $requestedShipperId
        );

        if ($idShipperFromAuth <= 0) {
            return $this->response([
                'status'  => false,
                'message' => 'Shipper tidak termasuk dalam akses user.'
            ], RestController::HTTP_FORBIDDEN);
        }

        $selectedShipperAccess = null;

        foreach (($auth['shipper_access'] ?? []) as $access) {
            if ((int) ($access['id_shipper'] ?? 0) === $idShipperFromAuth) {
                $selectedShipperAccess = $access;
                break;
            }
        }

        $shipper_name_input =
            $selectedShipperAccess['shipper_name']
            ?? $auth['user']['shipper_name']
            ?? '';

        if ($shipper_name_input === '') {
            return $this->response([
                'status'  => false,
                'message' => 'Data shipper pada akun tidak ditemukan.'
            ], RestController::HTTP_FORBIDDEN);
        }

        // Tetap gunakan method model lama agar mapping field sales/region tidak berubah.
        $shipper = $this->m_instant_booking->get_shipper_detail($shipper_name_input);

        // Format Tanggal
        $var_etd = $this->post('txtETDJKT');
        $show_date = ($var_etd) ? date('Y-m-d', strtotime($var_etd)) : null;
        
        $var_closing = $this->post('txtClosingDoc');
        $show_closing = ($var_closing) ? date('Y-m-d H:i:s', strtotime($var_closing)) : null;

        if (is_array($shipper) && !empty($shipper)) {
            
            // --- DATA HEADER ---
            $data = array(
                'booking_code'    => $this->post('booking_number'),
                'type_of_booking' => "EXPORT",
                'mode'            => $this->post('TypeTransaction'),
                'movement_type'   => $this->post('textMovementtype'),
                'incoterm'        => $this->post('textfieldIncoterm'),
                'freight'         => $this->post('textfieldFreight'),
                'number_of_bl'    => $this->post('BLtype'),
                
                // Location
                'origin_city'      => $this->post('dropdownPortLoading'),
                'destination_city' => $this->post('dropdownPortdestination'),
                'trans_city'       => $this->post('dropdownPortTrans') ? $this->post('dropdownPortTrans') : '', 
                
                // Cek route type
                'vessel' => ($this->post('route_type_text') == 'DIRECT')
                    ? ($this->post('vessel') ? $this->post('vessel') . ' ' . $this->post('voyage') : '')
                    : ($this->post('vessel') ? $this->post('vessel') : ''),

                'voyage' => ($this->post('route_type_text') == 'DIRECT')
                    ? ''
                    : ($this->post('voyage') ? $this->post('voyage') : ''),
                
                // Dates
                'expect_etd'       => $show_date,
                'eta'              => $this->post('txtETA'),
                'closing_date'     => $show_closing ? $show_closing : date('Y-m-d H:i:s'),
                'closing_cargo'    => $this->post('txtClosingCar') ? date('Y-m-d H:i:s', strtotime($this->post('txtClosingCar'))) : date('Y-m-d H:i:s'),

                // Ops & Admin
                'si_number'        => $this->post('textfieldSI') ? $this->post('textfieldSI') : '',
                'warehouse'        => $this->post('warehouse') ? $this->post('warehouse') : 'GCL WAREHOUSE',
                'sales'            => $shipper['sales'], 
                'shipper_id'       => $shipper['shipper'],
                'region_id'        => $shipper['region_id'],
                'status'           => 'finalize',
                'created_at'       => date("Y-m-d H:i:s"),
                
                // --- FIX KOLOM WAJIB (NOT NULL) ---
                'origin_country'   => 'INDONESIA',
                'destination_country' => '',
                'cargo_type'       => $this->post('cargoCategory'),
                'forwarder_name'   => $this->post('shipper') ? $this->post('shipper') : '',
                'currency'         => 'USD',
                'value_for_custom' => '0',
                'value_for_carrier'=> '0',
                'job_no'           => '', 
                'warehouse_status' => '',
                'stuffing_plan'    => '',
                'coloader'         => '',
                'schedule_id'      => $this->post('schedule_id'),
                'route_type'       => $this->post('route_type_text') ? $this->post('route_type_text') : 'DIRECT',
                'for_delivery'     => '',
                'payable'          => 'PREPAID',
                'shipped_on_board' => 0,
                
                // Optional/Nullable
                'insurance'        => $this->post('insurance') ? $this->post('insurance') : 0,
                'trucking'         => $this->post('trucking') ? $this->post('trucking') : '',
                'clearance'        => 0,
                'peb'              => 0,
                'total'            => 0,
                'pickup_datetime'  => null,
                'remark'           => $this->post('textareaDog') ? $this->post('textareaDog') : '',
                'pickup_location'  => '',
                'drop_location'    => '',
                
                // Files
                'file_msds'        => '',
                'file_packing_list'=> '',
                'file_invoice'     => '',
                'file_bl'          => '',
                'file_co'          => '',
                // UBAH BARIS INI: Tangkap nilai file_other dari payload React
				'file_other'       => $this->post('file_other') ? $this->post('file_other') : '',
            );

            // --- DATA DETAIL ---
            $data_detail = array(
                'booking_id'      => $this->post('booking_number'),
                'region_id'       => $shipper['region_id'],
                
                // Parties
                'shipper_name'    => $this->post('shipper'),
                'shipper_address' => $this->post('textareaShipper') ? $this->post('textareaShipper') : '',
                'consignee_name'  => $this->post('consignee') ? $this->post('consignee') : '',
                'consignee'       => $this->post('textareaConsignee') ? $this->post('textareaConsignee') : '',
                'notify_name'     => $this->post('notify') ? $this->post('notify') : '',
                'notify'          => $this->post('textareaNotify') ? $this->post('textareaNotify') : '-',
                
                // Goods Details
                'quantity'           => $this->post('textQty') ? $this->post('textQty') : 0,
                'package_type'       => $this->post('textPackaging') ? $this->post('textPackaging') : '',
                'weight'             => $this->post('textweight') ? $this->post('textweight') : 0,
                'netto'              => $this->post('textnetto') ? $this->post('textnetto') : 0,
                'volume'             => $this->post('textmeas') ? $this->post('textmeas') : 0,
                'marking'            => $this->post('textareamarkingNos') ? $this->post('textareamarkingNos') : '',
                'description_of_goods' => $this->post('textareadesc') ? $this->post('textareadesc') : '',
                
                // --- FIX KOLOM WAJIB DETAIL ---
                'note'            => trim($this->post('shipper')) . "\n" . trim($this->post('textareaShipper')),
                'as_agent'        => '',
                'cp_name'         => $this->post('contactshipper') ? $this->post('contactshipper') : '',
                'cp_email'        => $this->post('contactemail') ? $this->post('contactemail') : '',
                'do_number'       => '',
                'wh_arrival'      => date('Y-m-d H:i:s'),
                'container_no'    => '',
                
                // Technical
                'container_type'     => $this->post('container_type') ? $this->post('container_type') : '',
                'shipping_mark'      => '',
                'description_print'  => '',
                'dimension'          => '[]',
                'created_at'         => date("Ymd"),
                'updated_at'         => '',
            );

            // 1. Eksekusi simpan booking
			$response = $this->m_instant_booking->create_quotation($data, $data_detail);

			// 2. Cek apakah proses berhasil
			$is_success = (isset($response['status']) && $response['status'] == true) || 
						   (isset($response['code']) && $response['code'] == 200); 

			if ($is_success) {
				// Ambil ID booking yang baru saja dibuat
				// Asumsi: model mengembalikan 'booking_id' atau 'id' di dalam array $response
				$bookingId = $response['booking_id'] ?? $response['id'] ?? 0;
				$houseBl   = $response['house_bl'] ?? '';   // ambil nomor house_bl

				$idShipper = $idShipperFromAuth; 
				// Tambahkan parameter ke-5: $data['file_other']
				$this->_notify_new_booking($bookingId, $data, $idShipper, $houseBl, $data['file_other']);
			}

            echo json_encode($response);

        } else {
            echo json_encode($this->failed_shipper());
        }
    }
	
	/**
     * Helper untuk Notifikasi Booking Baru
     * Digunakan setelah proses insert booking sukses
     */
    private function _notify_new_booking($bookingId, $bookingData, $idShipper, $houseBl, $fileOther = '') {
        $db = $this->db; 

        // 1. Ambil data shipper dan sales terkait
        $db->select('
            s.shipper_name, 
            s.id_sales,
            r.user_id AS sales_user_id, 
            r.email AS sales_email, 
            r.management AS is_management
        ');
        $db->from('mstshipper s');
        $db->join('refsales r', 's.id_sales = r.id', 'left');
        $db->where('s.id_shipper', $idShipper);
        $result = $db->get()->row_array();

        if (!$result) return false;

        $shipperName  = $result['shipper_name'] ?? 'Unknown Shipper';
        $idSales      = (int)$result['id_sales'];
        $salesUserId  = $result['sales_user_id'];
        $salesEmail   = $result['sales_email'];
        $isManagement = (int)$result['is_management'];
        
        // 2. LOGIKA PENERIMA EMAIL
        $targetEmails = [];

        if ($idSales === 91) {
            // Jika sales = 91 (Overseas tim)
            $targetEmails = [
                'overseas@gateway-id.com',
                'overseas1@gateway-id.com',
                'overseas4@gateway-id.com'
            ];
        } else if ($isManagement === 1) {
            // Jika management = 1
            $targetEmails = ['cc@gateway-id.com'];
        } else {
            // Sales person terkait
            $email = !empty($salesEmail) ? $salesEmail : 'general@gateway-id.com';
            $targetEmails = [$email];
        }

        // 3. INSERT KE crm_notifications
        if ($salesUserId) {
            $db->insert('crm_notifications', [
                'user_id'    => $salesUserId,
                'title'      => 'New Online Booking',
                'body'       => "New Booking [{$bookingData['booking_code']}] to {$bookingData['destination_city']} received from {$shipperName}",
                'link'       => '/app/bookings/view/' . $bookingId,
                'ref_type'   => 'BOOKING',
                'ref_id'     => $bookingId,
                'severity'   => 'HIGH',
                'is_read'    => 0,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }

        // 4. KIRIM EMAIL via ElasticEmail
        $api_url  = 'https://api.elasticemail.com/v4/emails/transactional';
        $api_key  = '7705ADF9ED12C8B976F26499C8F43A6993B6E0704BBB2F4DE10B16388A78F14FDAAFF20B37264C90ECA327A00DB13F79';
        $sender   = 'no-reply@gcl.digital';

        $html_content = "
            <div style='font-family: Arial, sans-serif; padding: 20px; color: #333;'>
                <h2 style='color: #0f4fd8;'>New Online Booking Alert</h2>
                <p>Telah diterima booking baru via <strong>Gateway Customer Portal</strong>.</p>
                <br>
                <table border='0' cellpadding='6' cellspacing='0' style='width: 100%; max-width: 600px; border: 1px solid #ddd;'>
                    <tr style='background-color: #f9f9f9;'>
                        <td style='width: 40%;'><strong>Booking Number</strong></td>
                        <td>: {$bookingData['booking_code']}</td>
                    </tr>
					<tr>
                        <td><strong>HBL</strong></td>
                        <td>: {$houseBl}</td>
                    </tr>
                    <tr>
                        <td><strong>Shipper</strong></td>
                        <td>: {$shipperName}</td>
                    </tr>
                    <tr style='background-color: #f9f9f9;'>
                        <td><strong>Consignee</strong></td>
                        <td>: " . ($bookingData['consignee_name'] ?: '-') . "</td>
                    </tr>
                    <tr>
                        <td><strong>Port of Loading</strong></td>
                        <td>: " . ($bookingData['origin_city'] ?: '-') . "</td>
                    </tr>
                    <tr style='background-color: #f9f9f9;'>
                        <td><strong>Port of Destination</strong></td>
                        <td>: " . ($bookingData['destination_city'] ?: '-') . "</td>
                    </tr>
                    <tr>
                        <td><strong>ETD</strong></td>
                        <td>: " . ($bookingData['expect_etd'] ?: '-') . "</td>
                    </tr>
                    <tr style='background-color: #f9f9f9;'>
                        <td><strong>Qty / Package</strong></td>
                        <td>: {$bookingData['quantity']} {$bookingData['package_type']}</td>
                    </tr>
                    <tr>
                        <td><strong>Weight / Volume</strong></td>
                        <td>: {$bookingData['weight']} KGs / {$bookingData['volume']} M3</td>
                    </tr>
                </table>
                <br>
                <p>Silakan dicek dan ditindaklanjuti pada sistem CRM.</p>
            </div>
        ";

        $emailPayload = [
            "Recipients" => [ "To" => $targetEmails ],
            "Content" => [
                "Body" => [
                    [
                        "ContentType" => "HTML",
                        "Charset"     => "utf-8",
                        "Content"     => $html_content
                    ]
                ],
                "From"    => $sender,
                "Subject" => "New Online Booking: {$shipperName} [{$bookingData['booking_code']}] - HBL {$houseBl} - to {$bookingData['destination_city']}"
            ]
        ];
		
		// === TAMBAHKAN LOGIC ATTACHMENT DI SINI ===
        if (!empty($fileOther)) {
            $filePath = APPPATH . 'third_party/file/si_documents/' . $fileOther;
            // Pastikan file fisiknya ada di server
            if (file_exists($filePath)) {
                // ElasticEmail meminta file diconvert ke Base64
                $fileBase64 = base64_encode(file_get_contents($filePath));
                
                $emailPayload["Content"]["Attachments"] = [
                    [
                        "BinaryContent" => $fileBase64,
                        "Name"          => "SI_Document_" . $bookingData['booking_code'] . ".pdf",
                        "ContentType"   => "application/pdf"
                    ]
                ];
            }
        }
        // ==========================================

        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($emailPayload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-ElasticEmail-ApiKey: ' . $api_key
        ]);

        curl_exec($ch);
        curl_close($ch);

        return true;
    }

public function index_get()
{
    $booking_code = $this->get('booking_code'); // string
    $hbl_param    = $this->get('hbl');          // optional

    if (!$booking_code && !$hbl_param) {
        return $this->response([
            'status'  => false,
            'message' => 'booking_code is required (or provide hbl for fallback)'
        ], RestController::HTTP_BAD_REQUEST);
    }

    $q1 = null;
    $q2 = null;
	$readonly = "editable";
    // =========================
    // 1) Primary: tabel booking
    // =========================
    if ($booking_code) {
        $q1 = $this->dbx
            ->get_where('trnstuffingplan_booking', ['booking_code' => $booking_code])
            ->row_array();

        $q2 = $this->dbx
            ->get_where('trnstuffingplan_booking_detail', ['booking_id' => $booking_code])
            ->row_array();
    }

    // =========================
    // 2) Fallback: confirmed_* by HBL
    // =========================
    if (empty($q1) && empty($q2)) {
        $hbl = $hbl_param ?: $booking_code;
		$readonly = "readonly";
        $hdr = $this->confirmed_header_booking_row($hbl);  // row_array()
        $dtl = $this->confirmed_detail_booking_row($hbl);  // row_array()

        if (!empty($hdr) || !empty($dtl)) {
            // ---- mapping header -> booking schema ----
            $q1 = [
                'id'                  => (int)($hdr['sp_id'] ?? 0), // sesuaikan kalau id bookingmu beda
                'type_of_booking'     => $hdr['type_of_booking'] ?? '',
                'booking_code'        => $booking_code, // fallback: pakai param yg diminta user
                'mode'                => $hdr['mode'] ?? '',
                'movement_type'       => $hdr['movement_type'] ?? null,
                'incoterm'            => $hdr['incoterms'] ?? null,
                'origin_country'      => $hdr['origin_country'] ?? 'INDONESIA',
                'origin_city'         => $hdr['origin_city'] ?? '',
                'destination_country' => '',
                'destination_city'    => $hdr['destination_city'] ?? '',
                'trans_city'          => '',
                'insurance'           => null,
                'trucking'            => null,
                'freight'             => $hdr['freight'] ?? '',
                'clearance'           => 0,
                'peb'                 => 0,
                'cargo_type'          => '',
                'forwarder_name'      => $hdr['forwarder_name'] ?? '',
                'shipper_id'          => (int)($hdr['shipper_id'] ?? 0),
                'sales'               => (int)($hdr['sales'] ?? 0),
                'consignee_id'        => 0,
                'notify_id'           => 0,
                'drop_location'       => null,
                'pickup_location'     => null,
                'number_of_bl'        => ($hdr['number_of_bl'] ?? null) === 'TELEX' ? 'EXPRESS RELEASE' : ($hdr['number_of_bl'] ?? null),
                'status'              => 'BOOKED',
                'total'               => null,
                'created_by'          => (int)($hdr['shipper_id'] ?? 0),
                'file_msds'           => null,
                'file_invoice'        => '',
                'file_packing_list'   => '',
                'file_bl'             => '',
                'file_co'             => '',
                'file_other'          => '',
                'pickup_datetime'     => null,
                'expect_etd'          => $this->fmt_ymd($hdr['expect_etd'] ?? null),  // 20260127 -> 2026-01-27
                'eta'                 => $this->fmt_ymd($hdr['eta'] ?? null),
                'vessel'              => $hdr['vessel'] ?? '',
                'voyage'              => '',
                'pic_document'        => '',
                'warehouse'           => '',
                'si_number'           => $hdr['booking_code'] ?? '', // dari confirmed = d.no_from_shipper
                'pol_bl'              => $hdr['pol_bl'] ?? null,
                'tp_bl'               => null,
                'pod_bl'              => $hdr['pod_bl'] ?? null,
                'remark'              => null,
                'valid_at'            => null,
                'closing_date'        => $this->fmt_ymd_datetime($hdr['closing_date'] ?? null), // kalau 20260124 -> 2026-01-24 00:00:00
                'closing_cargo'       => $this->fmt_ymd_datetime($hdr['closing_date'] ?? null),
                'created_at'          => null,
                'updated_at'          => null,
                'currency'            => '',
                'value_for_custom'    => '',
                'value_for_carrier'   => '',
                'region_id'           => '',
                'grouping'            => '',
                'real_dest'           => $hdr['destination_city'] ?? '',
                'job_no'              => '',
                'warehouse_status'    => '',
                'stuffing_plan'       => '',
                'coloader'            => '',
                'schedule_id'         => 0,
                'route_type'          => '',
                'house_bl'            => $hdr['house_bl'] ?? '',
                'for_delivery'        => '',
                'payable'             => '',
                'shipped_on_board'    => 0
            ];

            // ---- mapping detail -> detail schema ----
            $q2 = [
                'id'                  => (int)($dtl['sp_detail_id'] ?? 0), // sesuaikan kalau id detailmu beda
                'booking_id'          => $booking_code,
                'note'                => $dtl['note'] ?? '',
                'shipper_name'        => $dtl['shipper_name'] ?? '',
                'shipper_address'     => $dtl['shipper_address'] ?? '',
                'consignee_name'      => '',
                'consignee'           => $dtl['consignee_address'] ?? '',
                'notify_name'         => '',
                'notify'              => $dtl['notify'] ?? '',
                'package_type'        => $dtl['package_type'] ?? '',
                'quantity'            => (int)($dtl['quantity'] ?? 0),
                'dimension'           => null,
                'volume'              => $dtl['volume'] ?? null,
                'weight'              => $dtl['weight'] ?? null,
                'netto'               => $dtl['netto'] ?? null,
                'container_type'      => null,
                'shipping_mark'       => '',
                'marking'             => $dtl['marking'] ?? '',
                'description_print'   => '',
                'description_of_goods'=> $dtl['description_of_goods'] ?? '',
                'as_agent'            => '',
                'cp_name'             => '',
                'cp_email'            => '',
                'do_number'           => '',
                'wh_arrival'          => '0000-00-00 00:00:00',
                'created_at'          => null,
                'updated_at'          => null,
                'region_id'           => '',
                'container_no'        => ''
            ];
        }
    }

    if (!empty($q1) || !empty($q2)) {
        return $this->response([
            'status'  => true,
            'editable'  =>$readonly,
            'booking' => $q1,  // OBJECT, bukan array list
            'detail'  => $q2   // OBJECT, bukan array list
        ], RestController::HTTP_OK);
    }

    return $this->response([
        'status'  => false,
        'message' => 'Data not found'
    ], RestController::HTTP_NOT_FOUND);
}

/** helper: 20260127 -> 2026-01-27 */
private function fmt_ymd($v)
{
    if (!$v) return null;
    $v = trim((string)$v);
    if (preg_match('/^\d{8}$/', $v)) {
        return substr($v, 0, 4) . '-' . substr($v, 4, 2) . '-' . substr($v, 6, 2);
    }
    return $v; // sudah ISO, biarkan
}

/** helper: 20260124 -> 2026-01-24 00:00:00 */
private function fmt_ymd_datetime($v)
{
    $d = $this->fmt_ymd($v);
    if (!$d) return null;
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) return $d . ' 00:00:00';
    return $d;
}

/** CONFIRMED HEADER: ambil 1 row saja */
private function confirmed_header_booking_row($hbl)
{
    return $this->dbx->select("
            sp.sp_id as sp_id,
            spd.sp_detail_id as sp_detail_id,
            CASE
              WHEN LOWER(sp.type_of_trans) LIKE '%import%' THEN 'IMPORT'
              ELSE 'EXPORT'
            END AS type_of_booking,
            d.no_from_shipper as booking_code,
            sp.type_of_trans as mode,
            CASE
              WHEN d.diff_status_2 IS NOT NULL AND d.diff_status_2 <> '' THEN d.diff_status_2
              WHEN d.cfs_cfs = 'TRUE' THEN 'CFS/CFS'
              WHEN d.cy_cy  = 'TRUE' THEN 'CY/CY'
              ELSE NULL
            END AS movement_type,
            spd.incoterms AS incoterms,
            'INDONESIA' as origin_country,
            CASE 
                WHEN sp.type_of_trans LIKE '%import%' THEN spd.destination 
                ELSE sp.port_loading 
            END AS origin_city,
            CASE 
                WHEN sp.type_of_trans LIKE '%import%' THEN sp.destination 
                ELSE spd.destination
            END AS destination_city,
            spd.freight AS freight,
            spd.shipper_print AS forwarder_name,
            spd.shipper AS shipper_id,
            spd.sales AS sales,
            CASE
              WHEN spd.express_release = 'TRUE' THEN 'TELEX'
              WHEN spd.cetak_jml_doc_origi  = 'TRUE' THEN '3 ORG'
              ELSE NULL
            END AS number_of_bl,
            spd.hb_l AS house_bl,
            sp.etd_jkt as expect_etd,
            sp.eta as eta,
            d.feeder_vessel as vessel,
            d.port_of_loading as pol_bl,
            d.port_of_discharge as pod_bl,
            sp.stuffing_date as closing_date
        ", false)
        ->from('tbl_detail d')
        ->join('trnstuffingplandetail spd', 'd.detail_id = spd.sp_detail_id', 'inner')
        ->join('trnstuffingplan sp', 'spd.sp_id = sp.sp_id', 'inner')
        ->where('spd.hb_l', $hbl)
        ->order_by('sp.etd_jkt', 'DESC')
        ->get()
        ->row_array(); // <--- penting: object tunggal
}

/** CONFIRMED DETAIL: ambil 1 row saja */
private function confirmed_detail_booking_row($hbl)
{
    return $this->dbx->select("
            spd.sp_detail_id as sp_detail_id,
            spd.note AS note,
            d.shipper_name AS shipper_name,
            d.shipper_address AS shipper_address,
            d.consignee AS consignee_address,
            d.notify AS notify,
            spd.no_pack_qty AS quantity,
            spd.no_pack_unit AS package_type,
            spd.bl_meas AS volume,
            spd.weight AS weight,
            spd.netto AS netto,
            spd.marking_nos AS marking,
            spd.desc_goods AS description_of_goods
        ", false)
        ->from('tbl_detail d')
        ->join('trnstuffingplandetail spd', 'd.detail_id = spd.sp_detail_id', 'inner')
        ->join('trnstuffingplan sp', 'spd.sp_id = sp.sp_id', 'inner')
        ->where('spd.hb_l', $hbl)
        ->order_by('sp.etd_jkt', 'DESC')
        ->get()
        ->row_array(); // <--- penting: object tunggal
}




	function cetak_bc_api($data,$data_detail, $filename) 
	{  
		//include the libraries
		require_once("pdf/phpqrcode/qrlib.php");
		
		//create a QR Code and save it as a png image file named test.png
		$si = $data['si_number'];
		QRcode::png($si,"test.png");
		$this->load->library('FPDF_MC_Table');
		$pdf=new FPDF_MC_Table();		
		$pdf->AddPage('P','A4');
		$pdf->SetAutoPageBreak(false);
	
		
			$logo = 'logo.png';
			$pt = 'PT GATEWAY CONTAINER LINE';
			$add = 'JL. OTISTA III RT. 001 RW. 001 NO. 18
						CIPINANG CEMPEDAK JATINEGARA
						JAKARTA TIMUR 13340
						PHONE : ( 62-21 ) 8591-8669 ( HUNTING )
						FAX : ( 62-21 ) 8591-8664';	
		
		
		$booking=$this->booking_model->find($data['booking_code']);
		//print_r($this->dbx->last_query());
		//die();
		$wh=$this->booking_model->get_warehouse_detail($booking->warehouse);
		//print_r($this->dbx->last_query());
		//die();
		$closing_date=$data['closing_date'];
		//print_r($_GET);
		//die();
		$pdf->Image(APPPATH.'third_party/img/BC.jpg',0,0,+200);
		$pdf->SetFont('Arial','',7);
		$pdf->SetXY(50, 40);
		$pdf->write(0,$booking->mode);
		$pdf->SetXY(50, 44.5);
		$pdf->write(0,$data['booking_code']);
		$pdf->SetXY(50, 49);
		$pdf->write(0,date("Y/m/d"));
		$pdf->SetXY(50, 53.5);
		$pdf->write(0,$closing_date);
		$pdf->SetXY(50, 62.5);
		$pdf->write(0,$booking->trucking);
		$pdf->SetXY(20,72);
		$pdf->MultiCell(60,3,strtoupper($data_detail['shipper_name']),0,'L',false);
		$pdf->SetXY(20,75);
		$pdf->MultiCell(60,3,strtoupper($data_detail['shipper_address']),0,'L',false);
		$pdf->SetXY(105,72);
		$pdf->MultiCell(60,3,$data_detail['consignee_name'],0,'L',false);
		$pdf->SetXY(105,75);
		$pdf->MultiCell(60,3,$data_detail['consignee'],0,'L',false);
		$pdf->SetXY(20,92);
		$pdf->MultiCell(60,3,$data_detail['notify_name'],0,'L',false);
		$pdf->SetXY(20,95);
		$pdf->MultiCell(60,3,$data_detail['notify'],0,'L',false);
		$pdf->SetXY(105, 94);
		$pdf->write(0,strtoupper($booking->vessel));
		$pdf->SetXY(148, 94);
		$pdf->write(0,strtoupper($booking->voyage));
		$pdf->SetXY(105, 102);
		$pdf->write(0,$booking->expect_etd);
		$pdf->SetXY(148, 102);
		$pdf->write(0,$booking->eta);
		$pdf->SetXY(105, 111);
		$pdf->write(0,$booking->origin_city);
		$pdf->SetXY(148, 111);
		$pdf->write(0,$booking->destination_city);
		$pdf->SetXY(20, 119);
		$pdf->write(0,$booking->number_of_bl);
		$pdf->SetXY(75, 119);
		$pdf->write(0,$data_detail['quantity']." ".$data_detail['package_type']);
		$pdf->SetXY(105, 119);
		$pdf->write(0,$data_detail['weight']." KGs");
		$pdf->SetXY(135, 119);
		$pdf->write(0,$data_detail['netto']." KGs");
		$pdf->SetXY(162, 119);
		$pdf->write(0,$data_detail['volume']." M3");
		$pdf->SetXY(20, 126);
		$pdf->write(0,$data['freight']);
		$pdf->SetXY(105, 126);
		$pdf->write(0,$data['incoterm']);
		$pdf->SetXY(20, 135);
		$pdf->MultiCell(60,3,$data_detail['marking'],0,'L',false);
		$pdf->SetXY(105, 135);
		$pdf->MultiCell(60,3,$data_detail['description_of_goods'],0,'L',false);
		$pdf->SetXY(20, 187);
		$pdf->write(0,$wh['warehouse_name']);
		$pdf->SetXY(20, 190);
		$pdf->multiCell(100,3,$wh['warehouse_address']);
		$pdf->SetXY(11, 228);
		if($booking->mode=='FCL'|| $booking->mode=='LCL'){
		$pdf->write(0,'SEA');
		}
		$pdf->SetXY(30, 228);
		$pdf->write(0,$booking->vessel);
		$pdf->SetXY(75, 228);
		$pdf->write(0,$booking->origin_city);
		if(!empty($booking->trans_city)){
		$pdf->SetXY(116, 228);
		$pdf->write(0,$booking->trans_city);
		$pdf->SetXY(11, 231);
		if($booking->mode=='FCL'|| $booking->mode=='LCL'){
		$pdf->write(0,'SEA');}
		$pdf->SetXY(30, 231);
		$pdf->write(0,$booking->voyage);
		$pdf->SetXY(75, 231);
		$pdf->write(0,$booking->trans_city);
		$pdf->SetXY(116, 231);
		$pdf->write(0,$booking->origin_city);
		$pdf->SetXY(147, 228);
		$pdf->write(0,$booking->expect_etd);
		$pdf->SetXY(167, 228);
		$pdf->write(0,$booking->eta);
		}else{
		$pdf->SetXY(116, 228);
		$pdf->write(0,$booking->destination_city);
		$pdf->SetXY(147, 228);
		$pdf->write(0,$booking->expect_etd);
		$pdf->SetXY(167, 228);
		$pdf->write(0,$booking->eta);
		}
		$pdf->SetXY(11,277);
		$pdf->write(0,$this->session->userdata('fullname'));
		$pdf->Image("http://gateway-cl.com/pdf/qr_generator.php?code=$si", 160, 246, 28, 28, "png");
		$pdfdoc = $pdf->Output($filename,'F');
	}
	public function failed_shipper(){
    $response='Username does not exist';
    return $response;
	}
	
} 
?>