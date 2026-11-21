<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Ticket_warehouse extends CI_Controller
{
    /**
     * Fixed bearer token provided by WMS.
     * This token is only for Gateway -> WMS POST requests.
     * Pull endpoints (get_ticket/get_stuffing) still use X-API-Key.
     */
    private $wms_bearer_token = "GCL-APP-9246b8082f66208961668b10f85896f8";

    private $api_base_url = "http://192.168.1.8:3010/api/v1/inventory/booking";
    private $api_booking_stuffing = "http://192.168.1.8:3010/api/v1/stuffing";

    /** @var CI_DB_query_builder */
    private $wms_db;

    public function __construct()
    {
        parent::__construct();

        $this->load->library('curl');
        $this->load->library('session');
        $this->load->model('afg_m_stuffing_plan');

        $this->fungsi->dbx = $this->afg_m_stuffing_plan->dbx;

        // Outbox sengaja disimpan di DB master agar endpoint GET WMS
        // tidak bergantung pada session tahun/region browser.
        $this->wms_db = $this->load->database('master', TRUE);
    }

    /**
     * ============================================================
     * WMS HTTP HELPERS
     * ============================================================
     * Authentication WMS sekarang memakai FIXED bearer token.
     * Tidak ada login, refresh token, ataupun token session.
     */
    private function send_ticket_request($url, array $post_data = [], $method = 'POST')
    {
        $method = strtoupper(trim((string) $method));
        if (!in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
            $method = 'POST';
        }

        $payload_json = null;
        if ($method !== 'DELETE' || !empty($post_data)) {
            $payload_json = json_encode($post_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($payload_json === false) {
                return [
                    'response' => false,
                    'http_code' => 0,
                    'curl_errno' => 0,
                    'curl_error' => 'JSON_ENCODE_FAILED: ' . json_last_error_msg(),
                ];
            }
        }

        $ch = curl_init($url);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Bearer ' . $this->wms_bearer_token,
            ],
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 30,
        ];
        if ($payload_json !== null) {
            $opts[CURLOPT_POSTFIELDS] = $payload_json;
        }
        curl_setopt_array($ch, $opts);

        $response = curl_exec($ch);
        $http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_errno = curl_errno($ch);
        $curl_error = curl_error($ch);
        curl_close($ch);

        return [
            'response' => $response,
            'http_code' => $http_code,
            'curl_errno' => $curl_errno,
            'curl_error' => $curl_error,
        ];
    }

    /**
     * Response WMS versi baru tidak diasumsikan hanya memakai
     * {status: true/false}. HTTP 2xx dianggap sukses KECUALI body
     * secara eksplisit menyatakan gagal.
     *
     * Didukung:
     *   status      : true/false, 1/0, success/failed/error/rejected
     *   success     : true/false, 1/0
     *   statusCode  : 200..299 / >=400
     *   code        : 200..299 / >=400
     *   nested data.status / data.success
     */
    private function is_wms_success($http_code, $response)
    {
        $http_code = (int) $http_code;

        if ($http_code < 200 || $http_code >= 300) {
            return false;
        }

        $body = trim((string) $response);

        // WMS may return 204 / empty body.
        if ($body === '') {
            return true;
        }

        $decoded = json_decode($body, true);

        // Non-JSON with HTTP 2xx is still considered accepted.
        if (!is_array($decoded)) {
            return true;
        }

        if ($this->wms_payload_explicit_failure($decoded)) {
            return false;
        }

        return true;
    }

    /**
     * Detect explicit failure markers in WMS response.
     */
    private function wms_payload_explicit_failure(array $payload)
    {
        foreach (['statusCode', 'status_code', 'httpCode', 'http_code', 'code'] as $key) {
            if (array_key_exists($key, $payload) && is_numeric($payload[$key])) {
                $code = (int) $payload[$key];

                // Only treat HTTP-like error codes as failure.
                if ($code >= 400 && $code <= 599) {
                    return true;
                }
            }
        }

        foreach (['status', 'success', 'result'] as $key) {
            if (!array_key_exists($key, $payload)) {
                continue;
            }

            $value = $payload[$key];

            if ($value === false || $value === 0 || $value === '0') {
                return true;
            }

            if (is_string($value)) {
                $normalized = strtolower(trim($value));

                if (in_array($normalized, [
                    'false',
                    'failed',
                    'failure',
                    'error',
                    'rejected',
                    'reject',
                    'unauthorized',
                    'forbidden',
                    'invalid',
                ], true)) {
                    return true;
                }
            }
        }

        if (isset($payload['data']) && is_array($payload['data'])) {
            foreach (['status', 'success', 'result'] as $key) {
                if (!array_key_exists($key, $payload['data'])) {
                    continue;
                }

                $value = $payload['data'][$key];

                if ($value === false || $value === 0 || $value === '0') {
                    return true;
                }

                if (is_string($value)) {
                    $normalized = strtolower(trim($value));

                    if (in_array($normalized, [
                        'false',
                        'failed',
                        'failure',
                        'error',
                        'rejected',
                        'reject',
                        'unauthorized',
                        'forbidden',
                        'invalid',
                    ], true)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    private function decode_wms_response($response)
    {
        $decoded = json_decode((string) $response, true);

        if (is_array($decoded)) {
            return $decoded;
        }

        $text = trim((string) $response);

        return $text !== '' ? $text : null;
    }

    /**
     * Debug outbound inventory booking ke WMS.
     * Tidak mencetak Authorization/Bearer token.
     */
    private function log_wms_ticket_outbound(
        $booking_code,
        $method,
        $url,
        array $payload,
        $attempt = 1
    ) {
        $payload_json = json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        if ($payload_json === false) {
            $payload_json = 'JSON_ENCODE_FAILED: ' . json_last_error_msg();
        }

        log_message(
            'info',
            'WMS TICKET OUTBOUND' .
            ' Booking=' . trim((string) $booking_code) .
            ' Attempt=' . (int) $attempt .
            ' Method=' . strtoupper(trim((string) $method)) .
            ' URL=' . (string) $url .
            ' Payload=' . $payload_json
        );
    }

    private function build_stuffing_update_payload(array $create_payload)
    {
        $header = isset($create_payload['header']) && is_array($create_payload['header'])
            ? $create_payload['header'] : [];

        return [
            'stuffing_owner' => (string)($create_payload['stuffing_owner'] ?? ''),
            'stuffing_date' => (string)($header['stuffing_date'] ?? ''),
            'etd' => (string)($header['etd'] ?? ''),
            'closing_date' => (string)($header['closing_date'] ?? ''),
            'container_size' => (string)($header['container_size'] ?? ''),
            'container_number' => (string)($header['container_number'] ?? ''),
            'seal_number' => (string)($header['seal_number'] ?? ''),
            'vessel' => (string)($header['vessel'] ?? ''),
            'destination' => (string)($header['destination'] ?? ''),
            'agent' => (string)($header['agent'] ?? ''),
            'instruction' => (string)($header['instruction'] ?? ''),
            'stuffing_location' => (string)($header['stuffing_location'] ?? ''),
            'stuffing_fills' => isset($create_payload['stuffing_fills']) && is_array($create_payload['stuffing_fills'])
                ? $create_payload['stuffing_fills'] : [],
        ];
    }

    private function is_wms_stuffing_duplicate(array $result)
    {
        if ((int)($result['http_code'] ?? 0) === 409) return true;
        $body = strtolower((string)($result['response'] ?? ''));
        return $body !== ''
            && strpos($body, 'duplicate entry') !== false
            && (strpos($body, '1062') !== false || strpos($body, 'uni_stuffings_job_no') !== false);
    }

    /**
     * PUT ditolak karena booking BELUM ADA di WMS.
     * WMS tidak konsisten: kadang HTTP 404, kadang HTTP 500 + "booking not found".
     * Karena itu cek http code DAN isi pesan.
     */
    private function is_wms_booking_not_found(array $result)
    {
        if ((int) ($result['curl_errno'] ?? 0) !== 0) {
            return false;
        }

        if ((int) ($result['http_code'] ?? 0) === 404) {
            return true;
        }

        $body = strtolower((string) ($result['response'] ?? ''));

        if ($body === '') {
            return false;
        }

        foreach ([
            'booking not found',
            'not found',
            'tidak ditemukan',
            'does not exist',
            'not exist',
            'no record',
        ] as $needle) {
            if (strpos($body, $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * POST ditolak karena booking SUDAH ADA di WMS.
     */
    private function is_wms_booking_duplicate(array $result)
    {
        if ((int) ($result['curl_errno'] ?? 0) !== 0) {
            return false;
        }

        if ((int) ($result['http_code'] ?? 0) === 409) {
            return true;
        }

        $body = strtolower((string) ($result['response'] ?? ''));

        return $body !== '' && (
            strpos($body, 'duplicate entry') !== false ||
            strpos($body, 'error 1062') !== false ||
            strpos($body, '1062 (23000)') !== false ||
            strpos($body, 'already exists') !== false ||
            strpos($body, 'already exist') !== false
        );
    }

    private function output_ticket_json($http_status, $payload)
    {
        return $this->output
            ->set_status_header($http_status)
            ->set_content_type('application/json')
            ->set_output(json_encode(
                $payload,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ));
    }

    /**
     * ============================================================
     * WMS PULL API KEY
     * ============================================================
     * Preferred: Apache/Nginx/PHP environment variable:
     *   WMS_PULL_API_KEY=<long random secret>
     *
     * WMS sends:
     *   X-API-Key: <secret>
     */
    private function validate_wms_api_key()
    {
        $expected = '';

        // Apache mod_php + SetEnv
        if (function_exists('apache_getenv')) {
            $expected = trim((string) apache_getenv('WMS_PULL_API_KEY', true));
        }

        // Fallback standard PHP environment
        if ($expected === '') {
            $expected = trim((string) getenv('WMS_PULL_API_KEY'));
        }

        // Last fallback
        if ($expected === '' && isset($_SERVER['WMS_PULL_API_KEY'])) {
            $expected = trim((string) $_SERVER['WMS_PULL_API_KEY']);
        }

        if ($expected === '') {
            log_message('error', 'WMS_PULL_API_KEY is not configured.');

            $this->output_ticket_json(503, [
                'status'  => false,
                'message' => 'WMS_API_KEY_NOT_CONFIGURED',
            ]);

            return false;
        }

        $provided = trim((string) $this->input->get_request_header('X-API-Key', true));

        if ($provided === '' || !hash_equals($expected, $provided)) {
            log_message(
                'error',
                'Invalid WMS API key. IP=' . $this->input->ip_address()
            );

            $this->output_ticket_json(401, [
                'status'  => false,
                'message' => 'INVALID_API_KEY',
            ]);

            return false;
        }

        return true;
    }

    /**
     * ============================================================
     * CANONICAL PAYLOAD
     * ============================================================
     * Satu-satunya source of truth untuk:
     *   1) body JSON POST ke WMS
     *   2) snapshot outbox
     *   3) body JSON GET fallback oleh WMS
     *
     * Payload merupakan SUPERSET:
     * - semua field asli booking_warehouse()
     * - field derived yang sebelumnya hanya ada di POST WMS
     * - alias lama dipertahankan agar kompatibel dengan WMS existing
     */
    private function build_canonical_ticket_payload($booking_code)
    {
        $booking_code     = trim((string) $booking_code);
        $destination_city = trim((string) $this->input->post('destination_city'));
        $warehouse        = trim((string) $this->input->post('warehouse'));
        $mode             = strtoupper(trim((string) $this->input->post('mode')));
        $region           = trim((string) $this->session->userdata('region_id'));

        // Pada POST browser normal, get_route() masih valid karena session region tersedia.
        // Jika tidak ditemukan, fallback ke destination_city.
        $route = $this->afg_m_stuffing_plan->get_route($booking_code);
        $real_dest = (
            isset($route[0]->real_dest) &&
            trim((string) $route[0]->real_dest) !== ''
        ) ? trim((string) $route[0]->real_dest) : $destination_city;

        switch ($region) {
            case 'Cikarang':
                $cargo_owner = 'GCL-CIKARANG';
                break;

            case 'Bandung':
                $cargo_owner = 'GCL-BANDUNG';
                break;

            case 'Surabaya':
                $cargo_owner = 'GCL-SURABAYA';
                break;

            default:
                $cargo_owner = 'GCL-JAKARTA';
                break;
        }

        if ($warehouse !== '' && stripos($warehouse, 'BANDUNG') !== false) {
            $godownlocation = 'GCLBandung';
        } elseif ($warehouse !== '' && stripos($warehouse, 'SARIADI SUB') !== false) {
            $godownlocation = 'GCLSariadiSurabaya';
        } else {
            $godownlocation = 'GCLMarunda';
        }

        $service_type_map = [
            'LCL'      => 'LCL',
            'FCL'      => 'FCL',
            'AIR'      => 'AIR',
            'TRUCKING' => 'TRUCKING',
        ];

        $cargo_service_type = $service_type_map[$mode]
            ?? ($mode !== '' ? $mode : 'LCL');

        $expect_etd        = (string) $this->input->post('expect_etd');
        $wh_arrival        = (string) $this->input->post('wh_arrival');
        $description_print = (string) $this->input->post('description_print');
        $desc_of_goods     = (string) $this->input->post('desc_of_goods');
        $voyage            = (string) $this->input->post('voyage');
        $cp_name           = (string) $this->input->post('cp_name');
        $cp_email          = (string) $this->input->post('cp_email');

        $payload = [
            // ----------------------------------------------------
            // Payload asli dari booking_warehouse()
            // ----------------------------------------------------
            'booking_code'       => $booking_code,
            'si_number'          => (string) $this->input->post('si_number'),
            'mode'               => $mode,
            'movement_type'      => (string) $this->input->post('movement_type'),
            'incoterm'           => (string) $this->input->post('incoterm'),
            'origin_city'        => (string) $this->input->post('origin_city'),
            'destination_city'   => $destination_city,
            'trans_city'         => (string) $this->input->post('trans_city'),
            'shipper_id'         => (string) $this->input->post('shipper_id'),
            'sales'              => (string) $this->input->post('sales'),
            'shipper_name'       => (string) $this->input->post('shipper_name'),
            'shipper_address'    => (string) $this->input->post('shipper_address'),
            'consignee_name'     => (string) $this->input->post('consignee_name'),
            'consignee_id'       => (string) $this->input->post('consignee_id'),
            'consignee'          => (string) $this->input->post('consignee'),
            'notify_name'        => (string) $this->input->post('notify_name'),
            'notify_id'          => (string) $this->input->post('notify_id'),
            'notify'             => (string) $this->input->post('notify'),
            'freight'            => (string) $this->input->post('freight'),
            'number_of_bl'       => (string) $this->input->post('number_of_bl'),
            'expect_etd'         => $expect_etd,
            'eta'                => (string) $this->input->post('eta'),
            'closing_doc'        => (string) $this->input->post('closing_doc'),
            'closing_car'        => (string) $this->input->post('closing_car'),
            'quantity'           => (int) $this->input->post('quantity'),
            'package_type'       => (string) $this->input->post('package_type'),
            'volume'             => (string) $this->input->post('volume'),
            'weight'             => (string) $this->input->post('weight'),
            'netto'              => (string) $this->input->post('netto'),
            'marking'            => (string) $this->input->post('marking'),
            'shipping_mark'      => (string) $this->input->post('shipping_mark'),
            'description_print'  => $description_print,
            'desc_of_goods'      => $desc_of_goods,
            'vessel'             => (string) $this->input->post('vessel'),
            'voyage'             => $voyage,
            'warehouse'          => $warehouse,
            'cp_name'            => $cp_name,
            'cp_email'           => $cp_email,
            'do_number'          => (string) $this->input->post('do_number'),
            'wh_arrival'         => $wh_arrival,
            'schedule_id'        => (string) $this->input->post('schedule_id'),
            'route_type'         => (string) $this->input->post('route_type'),
            'cargo_type'         => (string) $this->input->post('cargo_type'),
            'dg_class'           => (string) $this->input->post('dg_class'),
            'hs_code'            => (string) $this->input->post('hs_code'),

            // Pengawasan BC Hanggar
            'aju_number'         => strtoupper(trim((string) $this->input->post('aju_number'))),
            'peb_number'         => strtoupper(trim((string) $this->input->post('peb_number'))),
            'peb_date'           => trim((string) $this->input->post('peb_date')),
            'npe_number'         => strtoupper(trim((string) $this->input->post('npe_number'))),
            'npe_date'           => trim((string) $this->input->post('npe_date')),
            'jalur'              => strtoupper(trim((string) $this->input->post('jalur'))),
            'jalur_date'         => trim((string) $this->input->post('jalur_date')),

            // ----------------------------------------------------
            // Derived / normalized fields yang dipakai WMS existing
            // ----------------------------------------------------
            'cargo_service_type'       => $cargo_service_type,
            'cargo_owner'              => $cargo_owner,
            'godownlocation'           => $godownlocation,
            'real_dest'                => $real_dest,
            'pic_order_ticket'         => (string) $this->session->userdata('fullname'),
            'contact_pic_order_ticket' => (string) $this->session->userdata('email'),

            // ----------------------------------------------------
            // Alias backward-compatible dari payload POST lama.
            // Jangan hapus jika WMS existing sudah membaca nama ini.
            // ----------------------------------------------------
            'connnecting_vessel'   => $voyage,
            'description_of_goods' => $desc_of_goods,
            'description_of_print' => $description_print,
            'plan_date_in'         => $wh_arrival,
            'etd'                  => $expect_etd,
            'contact_name'         => $cp_name,
            'contact_email'        => $cp_email,
            'container_number'     => '',
            'seal_number'          => '',
            'size'                 => '',
        ];

        // PEB/NPE: sumber kebenaran = database (bukan isian browser).
        $peb = $this->load_peb_ticket_data(
            $this->load_pull_booking_db($this->resolve_pull_period_year()),
            $booking_code
        );
        if ($peb !== null) {
            $payload = array_merge($payload, $peb['summary']);
        }
        $payload['peb_list'] = $peb !== null ? $peb['peb_list'] : [];

        return $payload;
    }

    /**
     * ============================================================
     * DB FALLBACK FOR PULL GET
     * ============================================================
     * GET dari WMS tidak punya session browser. Booking Gateway disimpan
     * di DB tahunan, jadi period year harus ditentukan secara eksplisit.
     * Priority:
     *   1) X-Period-Year header
     *   2) ?period_year=YYYY
     *   3) session db (jika kebetulan ada)
     *   4) current year
     */
    private function resolve_pull_period_year()
    {
        $year = trim((string) $this->input->get_request_header('X-Period-Year', true));

        if ($year === '') {
            $year = trim((string) $this->input->get('period_year', true));
        }

        if ($year === '') {
            $year = trim((string) $this->session->userdata('db'));
        }

        if (!preg_match('/^20\d{2}$/', $year)) {
            $year = date('Y');
        }

        return $year;
    }

    private function load_pull_booking_db($period_year)
    {
        $period_year = trim((string) $period_year);

        if ($period_year !== '' && function_exists('switch_db')) {
            return $this->load->database(switch_db($period_year), true);
        }

        // Safety fallback. Pada project existing switch_db() normalnya tersedia.
        return $this->load->database('master', true);
    }

    /**
     * ============================================================
     * PEB / NPE (multi-PEB) UNTUK TICKET
     * ============================================================
     * Sumber kebenaran = tabel trnstuffingplan_booking_peb (diisi lewat widget
     * upload PEB/NPE). Ringkasan (PEB utama) tetap dikirim pada field lama
     * aju_number/peb_number/npe_number/jalur..., dan seluruh PEB dikirim pada
     * field baru `peb_list` (array). Bila tabel belum ada / belum ada data,
     * mengembalikan null sehingga payload lama (dari POST / detail) dipakai.
     *
     * @return array|null ['summary' => [...], 'peb_list' => [...]]
     */
    private function load_peb_ticket_data($db, $booking_code)
    {
        try {
            if (!$db->table_exists('trnstuffingplan_booking_peb')) {
                return null;
            }

            $rows = $db
                ->where('booking_code', $booking_code)
                ->where('deleted_at IS NULL', null, false)
                ->order_by('id', 'ASC')
                ->get('trnstuffingplan_booking_peb')
                ->result_array();
        } catch (Throwable $e) {
            log_message('error', 'WMS load_peb_ticket_data failed: ' . $e->getMessage());
            return null;
        }

        if (!$rows) {
            return null;
        }

        $summary = [
            'aju_number' => '', 'peb_number' => '', 'peb_date' => '',
            'npe_number' => '', 'npe_date' => '', 'jalur' => '', 'jalur_date' => '',
            'ppb_number' => '', 'ppb_date' => '', 'ppb_inspection_date' => '',
        ];
        $list = [];

        // Ringkasan AJU/PEB/NPE diambil dari SATU PEB yang paling lengkap (punya NPE > punya PEB > punya AJU;
        // seri -> id terkecil) agar pasangannya tidak tertukar. Aturan sama dengan Peb_model::sync_summary().
        $primary = null;
        $best = -1;
        foreach ($rows as $r) {
            $score = (!empty($r['npe_number']) ? 4 : 0) + (!empty($r['peb_number']) ? 2 : 0) + (!empty($r['aju_number']) ? 1 : 0);
            if ($score > $best) { $best = $score; $primary = $r; }
        }
        if ($primary !== null) {
            if (!empty($primary['aju_number'])) {
                $summary['aju_number'] = (string) $primary['aju_number'];
            }
            if (!empty($primary['peb_number'])) {
                $summary['peb_number'] = (string) $primary['peb_number'];
                $summary['peb_date']   = $this->normalize_date_only($primary['peb_date'] ?? '');
            }
            if (!empty($primary['npe_number'])) {
                $summary['npe_number'] = (string) $primary['npe_number'];
                $summary['npe_date']   = $this->normalize_date_only($primary['npe_date'] ?? '');
            }
        }

        // PPB (pemeriksaan fisik / jalur MERAH): dari PEB utama bila punya PPB, selain itu PEB pertama yang punya PPB.
        $ppb_row = null;
        if ($primary !== null && !empty($primary['ppb_number'])) {
            $ppb_row = $primary;
        } else {
            foreach ($rows as $r) {
                if (!empty($r['ppb_number'])) { $ppb_row = $r; break; }
            }
        }
        if ($ppb_row !== null) {
            $summary['ppb_number']          = (string) $ppb_row['ppb_number'];
            $summary['ppb_date']            = $this->normalize_date_only($ppb_row['ppb_date'] ?? '');
            $summary['ppb_inspection_date'] = $this->normalize_date_only($ppb_row['ppb_tgl_periksa'] ?? '');
        }

        foreach ($rows as $r) {
            $list[] = [
                'aju_number'      => (string) ($r['aju_number'] ?? ''),
                'peb_number'      => (string) ($r['peb_number'] ?? ''),
                'peb_date'        => $this->normalize_date_only($r['peb_date'] ?? ''),
                'npe_number'      => (string) ($r['npe_number'] ?? ''),
                'npe_date'        => $this->normalize_date_only($r['npe_date'] ?? ''),
                'jalur'           => (string) ($r['jalur'] ?? ''),
                'jalur_date'      => $this->normalize_date_only($r['jalur_date'] ?? ''),
                'ppb_number'            => (string) ($r['ppb_number'] ?? ''),
                'ppb_date'              => $this->normalize_date_only($r['ppb_date'] ?? ''),
                'ppb_inspection_date'   => $this->normalize_date_only($r['ppb_tgl_periksa'] ?? ''),
                'ppb_inspection_office' => (string) ($r['ppb_kantor_periksa'] ?? ''),
                'ppb_inspection_location' => (string) ($r['ppb_lokasi_periksa'] ?? ''),
                'ppb_exporter_officer'  => (string) ($r['ppb_petugas_eksportir'] ?? ''),
                'ppb_stuffing_date'     => $this->normalize_date_only($r['ppb_tgl_stuffing'] ?? ''),
                'ppb_stuffing_place'    => (string) ($r['ppb_tempat_stuffing'] ?? ''),
                'ppb_container_count'   => ($r['ppb_jml_kontainer'] ?? null) !== null ? (int) $r['ppb_jml_kontainer'] : null,
                'ppb_package_count'     => ($r['ppb_jml_kemasan'] ?? null) !== null ? (int) $r['ppb_jml_kemasan'] : null,
                'kategori_ekspor' => (string) ($r['kategori_ekspor'] ?? ''),
                'term'            => (string) ($r['incoterm'] ?? ''),
                'valuta'          => (string) ($r['valuta_kode'] ?? ''),
                'nilai_ekspor'    => ($r['nilai_ekspor'] ?? null) !== null ? (float) $r['nilai_ekspor'] : null,
                'ndpbm'           => ($r['ndpbm'] ?? null) !== null ? (float) $r['ndpbm'] : null,
                'asuransi'        => ($r['asuransi'] ?? null) !== null ? (float) $r['asuransi'] : null,
                'biaya_pengangkutan' => ($r['biaya_pengangkutan'] ?? null) !== null ? (float) $r['biaya_pengangkutan'] : null,
                'kemasan_qty'     => ($r['kemasan_qty'] ?? null) !== null ? (int) $r['kemasan_qty'] : null,
                'kemasan_jenis'   => (string) ($r['kemasan_jenis'] ?? ''),
                'marking'         => (string) ($r['kemasan_marking'] ?? ''),
                'berat_kotor'     => ($r['berat_kotor'] ?? null) !== null ? (float) $r['berat_kotor'] : null,
                'berat_bersih'    => ($r['berat_bersih'] ?? null) !== null ? (float) $r['berat_bersih'] : null,
                'hs_codes'        => (string) ($r['hs_codes'] ?? ''),
                'bl_number'       => (string) ($r['bl_number'] ?? ''),
            ];

            // Jalur ringkasan: MERAH bila ada satu saja yang merah (tanggal dari MERAH pertama).
            $j = strtoupper((string) ($r['jalur'] ?? ''));
            if (($j === 'MERAH' && $summary['jalur'] !== 'MERAH') || ($j === 'HIJAU' && $summary['jalur'] === '')) {
                $summary['jalur']      = $j;
                $summary['jalur_date'] = $this->normalize_date_only($r['jalur_date'] ?? '');
            }
        }

        return ['summary' => $summary, 'peb_list' => $list];
    }

    private function normalize_datetime_local($value)
    {
        $value = trim((string) $value);

        if (
            $value === '' ||
            $value === '0000-00-00 00:00:00' ||
            $value === '0000-00-00'
        ) {
            return '';
        }

        $ts = strtotime($value);

        return $ts ? date('Y-m-d\\TH:i', $ts) : $value;
    }

    private function normalize_date_only($value)
    {
        $value = trim((string) $value);

        if ($value === '' || strpos($value, '0000-00-00') === 0) {
            return '';
        }

        return substr($value, 0, 10);
    }

    private function cargo_owner_from_region($region)
    {
        switch (trim((string) $region)) {
            case 'Cikarang':
                return 'GCL-CIKARANG';
            case 'Bandung':
                return 'GCL-BANDUNG';
            case 'Surabaya':
                return 'GCL-SURABAYA';
            default:
                return 'GCL-JAKARTA';
        }
    }

    private function godown_from_warehouse($warehouse)
    {
        $warehouse = trim((string) $warehouse);

        if ($warehouse !== '' && stripos($warehouse, 'BANDUNG') !== false) {
            return 'GCLBandung';
        }

        if ($warehouse !== '' && stripos($warehouse, 'SARIADI SUB') !== false) {
            return 'GCLSariadiSurabaya';
        }

        return 'GCLMarunda';
    }

    private function service_type_from_mode($mode)
    {
        $mode = strtoupper(trim((string) $mode));

        $map = [
            'LCL'      => 'LCL',
            'FCL'      => 'FCL',
            'AIR'      => 'AIR',
            'TRUCKING' => 'TRUCKING',
        ];

        return $map[$mode] ?? ($mode !== '' ? $mode : 'LCL');
    }

    /**
     * Reconstruct canonical payload from stored booking data.
     * Dipakai hanya jika snapshot outbox belum ada (legacy/pre-patch booking).
     * Untuk booking yang pernah melewati post_ticket() setelah patch, GET tetap
     * mengambil snapshot outbox agar body GET == body POST byte-for-structure.
     */
    private function build_canonical_ticket_payload_from_db($booking_code, $period_year = null)
    {
        $booking_code = trim((string) $booking_code);
        $period_year  = $period_year ?: $this->resolve_pull_period_year();

        if ($booking_code === '') {
            return null;
        }

        $db = $this->load_pull_booking_db($period_year);

        $header = $db
            ->where('booking_code', $booking_code)
            ->get('trnstuffingplan_booking')
            ->row_array();

        // Optional fallback to master in case deployment keeps booking there.
        if (!$header) {
            $master = $this->load->database('master', true);
            $header = $master
                ->where('booking_code', $booking_code)
                ->get('trnstuffingplan_booking')
                ->row_array();

            if ($header) {
                $db = $master;
            }
        }

        if (!$header) {
            return null;
        }

        $detail = $db
            ->where('booking_id', $booking_code)
            ->get('trnstuffingplan_booking_detail')
            ->row_array();

        if (!is_array($detail)) {
            $detail = [];
        }

        $region           = trim((string) ($header['region_id'] ?? ''));
        $warehouse        = trim((string) ($header['warehouse'] ?? ''));
        $mode             = strtoupper(trim((string) ($header['mode'] ?? '')));
        $destination_city = trim((string) ($header['destination_city'] ?? ''));
        $real_dest        = trim((string) ($header['real_dest'] ?? ''));
        $real_dest        = $real_dest !== '' ? $real_dest : $destination_city;

        $cargo_owner        = $this->cargo_owner_from_region($region);
        $godownlocation     = $this->godown_from_warehouse($warehouse);
        $cargo_service_type = $this->service_type_from_mode($mode);

        $pic_name  = trim((string) ($header['pic_document'] ?? ''));
        $pic_email = '';

        // Resolve email PIC when mstadmin has the standard email column.
        if ($pic_name !== '' && $db->table_exists('mstadmin')) {
            $fields = $db->list_fields('mstadmin');

            if (in_array('email', $fields, true) && in_array('fullname', $fields, true)) {
                $db->select('email');
                $db->where('fullname', $pic_name);

                if ($region !== '' && in_array('region_id', $fields, true)) {
                    $db->where('region_id', $region);
                }

                $pic_row = $db->get('mstadmin')->row_array();
                $pic_email = trim((string) ($pic_row['email'] ?? ''));
            }
        }

        $expect_etd        = trim((string) ($header['expect_etd'] ?? ''));
        $wh_arrival        = $this->normalize_datetime_local($detail['wh_arrival'] ?? '');
        $description_print = (string) ($detail['description_print'] ?? '');
        $desc_of_goods     = (string) ($detail['description_of_goods'] ?? '');
        $voyage            = (string) ($header['voyage'] ?? '');
        $cp_name           = (string) ($detail['cp_name'] ?? '');
        $cp_email          = (string) ($detail['cp_email'] ?? '');

        // IMPORTANT: key order intentionally mirrors build_canonical_ticket_payload().
        $payload = [
            'booking_code'       => $booking_code,
            'si_number'          => (string) ($header['si_number'] ?? ''),
            'mode'               => $mode,
            'movement_type'      => (string) ($header['movement_type'] ?? ''),
            'incoterm'           => (string) ($header['incoterm'] ?? ''),
            'origin_city'        => (string) ($header['origin_city'] ?? ''),
            'destination_city'   => $destination_city,
            'trans_city'         => (string) ($header['trans_city'] ?? ''),
            'shipper_id'         => (string) ($header['shipper_id'] ?? ''),
            'sales'              => (string) ($header['sales'] ?? ''),
            'shipper_name'       => (string) ($detail['shipper_name'] ?? ($header['forwarder_name'] ?? '')),
            'shipper_address'    => (string) ($detail['shipper_address'] ?? ''),
            'consignee_name'     => (string) ($detail['consignee_name'] ?? ''),
            'consignee_id'       => (string) ($header['consignee_id'] ?? ''),
            'consignee'          => (string) ($detail['consignee'] ?? ''),
            'notify_name'        => (string) ($detail['notify_name'] ?? ''),
            'notify_id'          => (string) ($header['notify_id'] ?? ''),
            'notify'             => (string) ($detail['notify'] ?? ''),
            'freight'            => (string) ($header['freight'] ?? ''),
            'number_of_bl'       => (string) ($header['number_of_bl'] ?? ''),
            'expect_etd'         => $expect_etd,
            'eta'                => (string) ($header['eta'] ?? ''),
            'closing_doc'        => $this->normalize_datetime_local($header['closing_date'] ?? ''),
            'closing_car'        => $this->normalize_datetime_local($header['closing_cargo'] ?? ''),
            'quantity'           => (int) ($detail['quantity'] ?? 0),
            'package_type'       => (string) ($detail['package_type'] ?? ''),
            'volume'             => (string) ($detail['volume'] ?? ''),
            'weight'             => (string) ($detail['weight'] ?? ''),
            'netto'              => (string) ($detail['netto'] ?? ''),
            'marking'            => (string) ($detail['marking'] ?? ''),
            'shipping_mark'      => (string) ($detail['shipping_mark'] ?? ''),
            'description_print'  => $description_print,
            'desc_of_goods'      => $desc_of_goods,
            'vessel'             => (string) ($header['vessel'] ?? ''),
            'voyage'             => $voyage,
            'warehouse'          => $warehouse,
            'cp_name'            => $cp_name,
            'cp_email'           => $cp_email,
            'do_number'          => (string) ($detail['do_number'] ?? ''),
            'wh_arrival'         => $wh_arrival,
            'schedule_id'        => (string) ($header['schedule_id'] ?? ''),
            'route_type'         => (string) ($header['route_type'] ?? ''),
            'cargo_type'         => (string) ($header['cargo_type'] ?? ''),
            'dg_class'           => (string) ($detail['dg_class'] ?? ''),
            'hs_code'            => (string) ($detail['hs_code'] ?? ''),

            // Pengawasan BC Hanggar
            'aju_number'         => (string) ($detail['aju_number'] ?? ''),
            'peb_number'         => (string) ($detail['peb_number'] ?? ''),
            'peb_date'           => $this->normalize_date_only($detail['peb_date'] ?? ''),
            'npe_number'         => (string) ($detail['npe_number'] ?? ''),
            'npe_date'           => $this->normalize_date_only($detail['npe_date'] ?? ''),
            'jalur'              => (string) ($detail['jalur'] ?? ''),
            'jalur_date'         => $this->normalize_date_only($detail['jalur_date'] ?? ''),

            'cargo_service_type'       => $cargo_service_type,
            'cargo_owner'              => $cargo_owner,
            'godownlocation'           => $godownlocation,
            'real_dest'                => $real_dest,
            'pic_order_ticket'         => $pic_name,
            'contact_pic_order_ticket' => $pic_email,

            'connnecting_vessel'   => $voyage,
            'description_of_goods' => $desc_of_goods,
            'description_of_print' => $description_print,
            'plan_date_in'         => $wh_arrival,
            'etd'                  => $expect_etd,
            'contact_name'         => $cp_name,
            'contact_email'        => $cp_email,
            'container_number'     => '',
            'seal_number'          => '',
            'size'                 => '',
        ];

        $peb = $this->load_peb_ticket_data($db, $booking_code);
        if ($peb !== null) {
            $payload = array_merge($payload, $peb['summary']);
        }
        $payload['peb_list'] = $peb !== null ? $peb['peb_list'] : [];

        return $payload;
    }

    /**
     * ============================================================
     * OUTBOX HELPERS
     * ============================================================
     */
    private function save_wms_outbox($booking_code, array $payload)
    {
        $booking_code = trim((string) $booking_code);

        if ($booking_code === '') {
            return false;
        }

        $json = json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        if ($json === false) {
            log_message(
                'error',
                'Failed encoding canonical WMS payload. Booking=' . $booking_code
            );
            return false;
        }

        $existing = $this->wms_db
            ->select('id')
            ->where('booking_code', $booking_code)
            ->get('wms_ticket_outbox')
            ->row_array();

        $data = [
            'payload_json'   => $json,
            'push_status'    => 'PENDING',
            'last_http_code' => null,
            'last_error'     => null,
            'updated_at'     => date('Y-m-d H:i:s'),
        ];

        if ($existing) {
            return $this->wms_db
                ->where('id', $existing['id'])
                ->update('wms_ticket_outbox', $data);
        }

        $data['booking_code'] = $booking_code;
        $data['created_at']   = date('Y-m-d H:i:s');

        return $this->wms_db->insert('wms_ticket_outbox', $data);
    }

    private function update_wms_outbox_attempt(
        $booking_code,
        $success,
        $http_code = null,
        $error = null
    ) {
        $booking_code = trim((string) $booking_code);

        if ($booking_code === '') {
            return;
        }

        $this->wms_db
            ->set('push_attempts', 'push_attempts + 1', false)
            ->set('push_status', $success ? 'SENT' : 'PENDING')
            ->set('last_http_code', $http_code !== null ? (int) $http_code : null)
            ->set(
                'last_error',
                $error !== null ? substr((string) $error, 0, 65000) : null
            )
            ->set('last_push_at', date('Y-m-d H:i:s'))
            ->set('updated_at', date('Y-m-d H:i:s'))
            ->where('booking_code', $booking_code)
            ->update('wms_ticket_outbox');
    }

    private function mark_wms_ticket_pulled($id)
    {
        $this->wms_db
            ->set('pull_count', 'pull_count + 1', false)
            ->set('last_pulled_at', date('Y-m-d H:i:s'))
            ->set('updated_at', date('Y-m-d H:i:s'))
            ->where('id', (int) $id)
            ->update('wms_ticket_outbox');
    }

    /**
     * ============================================================
     * POST TICKET
     * ============================================================
     * Endpoint browser/internal Gateway.
     *
     * Canonical payload disimpan ke outbox SEBELUM mencoba login/post
     * ke WMS. Jika WMS tidak dapat menerima POST, data tetap tersedia
     * melalui get_ticket().
     *
     * Body POST ke WMS == body GET fallback.
     */
    public function post_ticket()
    {
        if (strtoupper($this->input->method(true)) !== 'POST') {
            return $this->output_ticket_json(405, [
                'status'  => false,
                'message' => 'METHOD_NOT_ALLOWED',
            ]);
        }

        $booking_code = trim((string) $this->input->post('booking_code'));

        if ($booking_code === '') {
            log_message('error', 'WMS post_ticket rejected: BOOKING_CODE_EMPTY');

            return $this->output_ticket_json(422, [
                'status'  => false,
                'message' => 'BOOKING_CODE_EMPTY',
            ]);
        }

        // 1. Build canonical payload.
        $canonical_payload = $this->build_canonical_ticket_payload($booking_code);

        return $this->push_ticket($booking_code, $canonical_payload);
    }

    /**
     * Kirim payload kanonik ke WMS: simpan snapshot outbox, lalu PUT (update) atau
     * POST (create) dengan fallback dua arah. Dipakai oleh post_ticket() dan sync_peb().
     * Menulis respons JSON ke output (sama seperti post_ticket() sebelumnya).
     */
    private function push_ticket($booking_code, array $canonical_payload)
    {
        // Tentukan CREATE vs UPDATE SEBELUM snapshot diperbarui.
        // Jangan hanya bergantung pada push_status=SENT karena WMS bisa sudah
        // mempunyai booking sementara outbox Gateway masih PENDING (misalnya
        // booking dibuat manual / request sebelumnya sukses di WMS tetapi
        // response ke Gateway gagal). Jika booking pernah masuk outbox, coba
        // PUT dulu. Jika WMS belum mempunyai booking, PUT 404 akan fallback POST.
        $previous_outbox = $this->wms_db
            ->select('id, push_status, last_http_code')
            ->where('booking_code', $booking_code)
            ->get('wms_ticket_outbox')
            ->row_array();

        $wms_method = $previous_outbox ? 'PUT' : 'POST';

        // 2. Save fallback snapshot BEFORE WMS request.
        if (!$this->save_wms_outbox($booking_code, $canonical_payload)) {
            log_message(
                'error',
                'WMS outbox save failed. Booking=' . $booking_code
            );

            return $this->output_ticket_json(500, [
                'status'       => false,
                'booking_code' => $booking_code,
                'message'      => 'WMS_OUTBOX_SAVE_FAILED',
            ]);
        }

        // 3. CREATE memakai POST ke base endpoint.
        //    UPDATE memakai PUT ke /{booking_code}.
        $url = $this->api_base_url;
        if ($wms_method === 'PUT') {
            $url .= '/' . rawurlencode($booking_code);
        }

        $attempt_no = 1;
        $this->log_wms_ticket_outbound(
            $booking_code,
            $wms_method,
            $url,
            $canonical_payload,
            $attempt_no
        );

        $result = $this->send_ticket_request(
            $url,
            $canonical_payload,
            $wms_method
        );

        // Safety fallback dua arah:
        // - POST duplicate/already-exists -> booking sudah ada di WMS -> retry sebagai PUT.
        // - PUT not-found (HTTP 404 ATAU HTTP 500 "booking not found") -> booking belum
        //   ada di WMS -> retry sebagai POST.
        // Maksimal 3 request; semua jejak dicatat di $wms_attempts.
        $wms_attempts = [$wms_method . ':' . (int) $result['http_code']];

        if ($wms_method === 'POST' && $this->is_wms_booking_duplicate($result)) {
            log_message(
                'info',
                'WMS create indicates booking already exists; retrying as PUT. Booking=' .
                $booking_code . ' HTTP=' . $result['http_code']
            );

            $wms_method = 'PUT';
            $url = $this->api_base_url . '/' . rawurlencode($booking_code);
            $attempt_no++;
            $this->log_wms_ticket_outbound(
                $booking_code,
                'PUT',
                $url,
                $canonical_payload,
                $attempt_no
            );
            $result = $this->send_ticket_request($url, $canonical_payload, 'PUT');
            $wms_attempts[] = 'PUT:' . (int) $result['http_code'];

        } elseif ($wms_method === 'PUT' && $this->is_wms_booking_not_found($result)) {
            log_message(
                'info',
                'WMS update says booking not found (HTTP ' . $result['http_code'] .
                '); retrying as POST. Booking=' . $booking_code
            );

            $wms_method = 'POST';
            $url = $this->api_base_url;
            $attempt_no++;
            $this->log_wms_ticket_outbound(
                $booking_code,
                'POST',
                $url,
                $canonical_payload,
                $attempt_no
            );
            $result = $this->send_ticket_request($url, $canonical_payload, 'POST');
            $wms_attempts[] = 'POST:' . (int) $result['http_code'];

            // Edge case: POST setelah PUT not-found ternyata dibalas duplicate.
            // Retry terakhir sebagai PUT agar state Gateway/WMS bisa self-heal.
            if ($this->is_wms_booking_duplicate($result)) {
                log_message(
                    'info',
                    'WMS create retry indicates duplicate; final retry as PUT. Booking=' .
                    $booking_code . ' HTTP=' . $result['http_code']
                );

                $wms_method = 'PUT';
                $url = $this->api_base_url . '/' . rawurlencode($booking_code);
                $attempt_no++;
                $this->log_wms_ticket_outbound(
                    $booking_code,
                    'PUT',
                    $url,
                    $canonical_payload,
                    $attempt_no
                );
                $result = $this->send_ticket_request($url, $canonical_payload, 'PUT');
                $wms_attempts[] = 'PUT:' . (int) $result['http_code'];
            }
        }

        // Isi persis yang dikirim ke WMS (tanpa token). Dikembalikan ke browser supaya bisa di-console.log
        // dan dicocokkan oleh tim WMS.
        $wms_request = [
            'method'   => $wms_method,
            'url'      => $url,
            'attempts' => $wms_attempts,
            'payload'  => $canonical_payload,
        ];

        if ($result['curl_errno'] !== 0 || $result['curl_error'] !== '') {
            $error = $result['curl_error'] !== ''
                ? $result['curl_error']
                : 'WMS_CURL_ERROR';

            $this->update_wms_outbox_attempt(
                $booking_code,
                false,
                $result['http_code'],
                $error
            );

            log_message(
                'error',
                'WMS connection error. Booking=' . $booking_code .
                ' Error=' . $error
            );

            return $this->output_ticket_json(502, [
                'status'       => false,
                'booking_code' => $booking_code,
                'http_code'    => $result['http_code'],
                'wms_method'   => $wms_method,
                'wms_attempts' => $wms_attempts,
                'wms_request'  => $wms_request,
                'message'      => 'WMS_CONNECTION_ERROR',
                'error'        => $error,
            ]);
        }

        $response_payload = $this->decode_wms_response($result['response']);

        // Fixed bearer means 401/403 is final; DO NOT refresh/retry token.
        if (in_array((int) $result['http_code'], [401, 403], true)) {
            $failure_text = is_string($result['response'])
                ? $result['response']
                : json_encode($result['response']);

            $this->update_wms_outbox_attempt(
                $booking_code,
                false,
                $result['http_code'],
                $failure_text ?: 'WMS_AUTH_FAILED'
            );

            log_message(
                'error',
                'WMS fixed bearer rejected. Booking=' . $booking_code .
                ' HTTP=' . $result['http_code']
            );

            return $this->output_ticket_json(502, [
                'status'       => false,
                'booking_code' => $booking_code,
                'http_code'    => $result['http_code'],
                'wms_method'   => $wms_method,
                'wms_attempts' => $wms_attempts,
                'wms_request'  => $wms_request,
                'message'      => 'WMS_AUTH_FAILED',
                'response'     => $response_payload,
            ]);
        }

        $success = $this->is_wms_success(
            $result['http_code'],
            $result['response']
        );

        if ($success) {
            $this->update_wms_outbox_attempt(
                $booking_code,
                true,
                $result['http_code'],
                null
            );

            log_message(
                'info',
                'WMS ticket SUCCESS. Booking=' . $booking_code .
                ' Method=' . $wms_method .
                ' HTTP=' . $result['http_code']
            );

            return $this->output_ticket_json(200, [
                'status'       => true,
                'booking_code' => $booking_code,
                'http_code'    => $result['http_code'],
                'wms_method'   => $wms_method,
                'wms_attempts' => $wms_attempts,
                'wms_request'  => $wms_request,
                'message'      => 'WMS_TICKET_SUCCESS',
                'response'     => $response_payload,
            ]);
        }

        $failure_text = is_string($result['response'])
            ? $result['response']
            : json_encode($result['response']);

        $this->update_wms_outbox_attempt(
            $booking_code,
            false,
            $result['http_code'],
            $failure_text
        );

        log_message(
            'error',
            'WMS ticket FAILED. Booking=' . $booking_code .
            ' Method=' . $wms_method .
            ' HTTP=' . $result['http_code'] .
            ' Response=' . (string) $result['response']
        );

        return $this->output_ticket_json(502, [
            'status'       => false,
            'booking_code' => $booking_code,
            'http_code'    => $result['http_code'],
            'wms_method'   => $wms_method,
            'wms_attempts' => $wms_attempts,
            'wms_request'  => $wms_request,
            'message'      => 'WMS_TICKET_FAILED',
            'response'     => $response_payload,
        ]);
    }

    /**
     * ============================================================
     * SINKRON PEB / NPE KE WMS
     * ============================================================
     * POST api/ticket_warehouse/sync_peb   booking_code=GTW-xxxx
     *
     * Dipanggil widget PEB/NPE setiap kali PEB/NPE di-upload, diubah, atau dihapus,
     * supaya gudang langsung menerima data terbaru tanpa menunggu tombol SAVE.
     *
     * - Dasarnya adalah snapshot ticket TERAKHIR di outbox (apa yang terakhir dikirim/
     *   direncanakan ke WMS); hanya field PEB/NPE yang ditimpa dari database
     *   (aju/peb/npe/jalur ringkasan + peb_list). Field booking lain TIDAK berubah.
     * - Booking yang belum pernah dikirim ke WMS (tidak ada di outbox) dilewati:
     *   PEB/NPE-nya otomatis ikut terkirim saat ticket pertama dikirim.
     * - Alur kirim sama dengan post_ticket (PUT, fallback POST, outbox, log), sehingga
     *   GET get_ticket dari WMS juga langsung mendapat data terbaru.
     */
    public function sync_peb()
    {
        if (strtoupper($this->input->method(true)) !== 'POST') {
            return $this->output_ticket_json(405, [
                'status'  => false,
                'message' => 'METHOD_NOT_ALLOWED',
            ]);
        }

        $booking_code = trim((string) $this->input->post('booking_code'));
        if ($booking_code === '') {
            return $this->output_ticket_json(422, [
                'status'  => false,
                'message' => 'BOOKING_CODE_EMPTY',
            ]);
        }

        $row = $this->wms_db
            ->select('payload_json')
            ->where('booking_code', $booking_code)
            ->get('wms_ticket_outbox')
            ->row_array();

        if (!$row) {
            return $this->output_ticket_json(200, [
                'status'       => true,
                'skipped'      => true,
                'booking_code' => $booking_code,
                'message'      => 'WMS_NOT_SENT_YET',
            ]);
        }

        $payload = json_decode((string) $row['payload_json'], true);
        if (!is_array($payload)) {
            log_message('error', 'WMS sync_peb: invalid outbox JSON. Booking=' . $booking_code);

            return $this->output_ticket_json(500, [
                'status'       => false,
                'booking_code' => $booking_code,
                'message'      => 'WMS_OUTBOX_PAYLOAD_INVALID',
            ]);
        }

        $db  = $this->load_pull_booking_db($this->resolve_pull_period_year());
        $peb = $this->load_peb_ticket_data($db, $booking_code);

        $blank = [
            'aju_number' => '', 'peb_number' => '', 'peb_date' => '',
            'npe_number' => '', 'npe_date' => '', 'jalur' => '', 'jalur_date' => '',
            'ppb_number' => '', 'ppb_date' => '', 'ppb_inspection_date' => '',
        ];
        $payload = array_merge($payload, $blank, $peb !== null ? $peb['summary'] : []);
        $payload['peb_list'] = $peb !== null ? $peb['peb_list'] : [];

        // Kirim ke WMS bisa makan waktu (timeout 30 dtk x maks 3 percobaan). Lepas kunci
        // session lebih dulu supaya request lain user yang sama (SAVE, pindah menu) tidak
        // ikut menunggu. Bagian di bawah ini tidak memakai session.
        if (function_exists('session_status') && session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        return $this->push_ticket($booking_code, $payload);
    }

    /**
     * ============================================================
     * GET TICKET - WMS FALLBACK/PULL
     * ============================================================
     * Auth:
     *   X-API-Key: <WMS_PULL_API_KEY>
     *
     * A. One ticket:
     *   GET /api/ticket_warehouse/get_ticket?booking_code=GTW-xxxx
     *   -> returns the exact canonical JSON object used for POST.
     *
     * B. Pending tickets:
     *   GET /api/ticket_warehouse/get_ticket?limit=50
     *   -> returns array of canonical JSON objects whose direct POST
     *      has not succeeded.
     */
    public function get_ticket()
    {
        if (strtoupper($this->input->method(true)) !== 'GET') {
            return $this->output_ticket_json(405, [
                'status'  => false,
                'message' => 'METHOD_NOT_ALLOWED',
            ]);
        }

        if (!$this->validate_wms_api_key()) {
            return;
        }

        $booking_code = trim((string) $this->input->get('booking_code', true));

        // --------------------------------------------------------
        // GET exact one booking.
        // --------------------------------------------------------
        if ($booking_code !== '') {
            $row = $this->wms_db
                ->where('booking_code', $booking_code)
                ->get('wms_ticket_outbox')
                ->row_array();

            if (!$row) {
                // Legacy/pre-patch booking: reconstruct from annual booking DB.
                $period_year = $this->resolve_pull_period_year();
                $payload = $this->build_canonical_ticket_payload_from_db(
                    $booking_code,
                    $period_year
                );

                if (!is_array($payload)) {
                    return $this->output_ticket_json(404, [
                        'status'       => false,
                        'booking_code' => $booking_code,
                        'period_year'  => $period_year,
                        'message'      => 'BOOKING_NOT_FOUND',
                    ]);
                }

                // Cache reconstructed canonical payload so the next GET uses
                // the same stable snapshot and it also enters pending outbox.
                $this->save_wms_outbox($booking_code, $payload);

                $row = $this->wms_db
                    ->where('booking_code', $booking_code)
                    ->get('wms_ticket_outbox')
                    ->row_array();

                if ($row) {
                    $this->mark_wms_ticket_pulled($row['id']);
                }

                return $this->output_ticket_json(200, $payload);
            }

            $payload = json_decode((string) $row['payload_json'], true);

            if (!is_array($payload)) {
                return $this->output_ticket_json(500, [
                    'status'       => false,
                    'booking_code' => $booking_code,
                    'message'      => 'INVALID_STORED_PAYLOAD',
                ]);
            }

            $this->mark_wms_ticket_pulled($row['id']);

            // NO wrapper. This object is identical to POST body.
            return $this->output_ticket_json(200, $payload);
        }

        // --------------------------------------------------------
        // GET pending list.
        // --------------------------------------------------------
        $limit = (int) $this->input->get('limit', true);

        if ($limit <= 0) {
            $limit = 50;
        }

        if ($limit > 200) {
            $limit = 200;
        }

        $rows = $this->wms_db
            ->where('push_status', 'PENDING')
            ->order_by('created_at', 'ASC')
            ->limit($limit)
            ->get('wms_ticket_outbox')
            ->result_array();

        $payloads = [];

        foreach ($rows as $row) {
            $payload = json_decode((string) $row['payload_json'], true);

            if (!is_array($payload)) {
                log_message(
                    'error',
                    'Invalid WMS outbox JSON. ID=' . $row['id'] .
                    ' Booking=' . $row['booking_code']
                );
                continue;
            }

            $payloads[] = $payload;
            $this->mark_wms_ticket_pulled($row['id']);
        }

        return $this->output_ticket_json(200, $payloads);
    }

    /**
     * ============================================================
     * STUFFING PULL API HELPERS
     * ============================================================
     * GET WMS tidak mempunyai session browser, sehingga DB periode
     * dipilih dari X-Period-Year / period_year / tahun berjalan.
     */
    private function prepare_stuffing_pull_db($period_year = null)
    {
        $period_year = $period_year ?: $this->resolve_pull_period_year();
        $db = $this->load_pull_booking_db($period_year);

        // Model existing memakai property dbx. Arahkan ke DB periode
        // supaya cetak_stuffing_header/detail/get_instruksi membaca DB
        // yang sama walaupun request berasal dari WMS tanpa session.
        $this->afg_m_stuffing_plan->dbx = $db;
        $this->fungsi->dbx = $db;

        return $db;
    }

    private function stuffing_owner_from_region($region)
    {
        return $this->cargo_owner_from_region($region);
    }

    /**
     * cont_seal_no legacy disimpan sebagai CONTAINER/SEAL.
     * Endpoint WMS baru membutuhkan container_number dan seal_number terpisah.
     */
    private function split_stuffing_container_seal($value)
    {
        $value = trim((string) $value);

        if ($value === '') {
            return ['', ''];
        }

        $parts = preg_split('/\s*\/\s*/', $value, 2);

        return [
            trim((string) ($parts[0] ?? '')),
            trim((string) ($parts[1] ?? '')),
        ];
    }


    /**
     * Format datetime sesuai kontrak WMS baru:
     * 2026-09-28T14:00:00Z
     */
    private function format_wms_stuffing_datetime($value)
    {
        $value = trim((string) $value);

        if (
            $value === '' ||
            $value === '0000-00-00' ||
            $value === '0000-00-00 00:00:00'
        ) {
            return '';
        }

        $ts = strtotime($value);
        if (!$ts) {
            return $value;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return date('Y-m-d', $ts) . 'T00:00:00Z';
        }

        return date('Y-m-d\TH:i:s\Z', $ts);
    }

    /**
     * Pastikan numeric field WMS menjadi JSON number, bukan string format UI.
     */
    private function normalize_wms_number($value)
    {
        if (is_int($value) || is_float($value)) {
            return $value + 0;
        }

        $value = trim((string) $value);
        if ($value === '') {
            return 0;
        }

        $value = str_replace(["\xc2\xa0", ' '], '', $value);
        $lastComma = strrpos($value, ',');
        $lastDot   = strrpos($value, '.');

        if ($lastComma !== false && $lastDot !== false) {
            if ($lastComma > $lastDot) {
                $value = str_replace('.', '', $value);
                $value = str_replace(',', '.', $value);
            } else {
                $value = str_replace(',', '', $value);
            }
        } elseif ($lastComma !== false) {
            $value = str_replace(',', '.', $value);
        }

        $value = preg_replace('/[^0-9.\-]/', '', $value);

        return is_numeric($value) ? (float) $value : 0;
    }

    /**
     * Build payload dengan struktur SAMA seperti body POST stuffing ke WMS.
     */
    private function build_stuffing_payload($code, $period_year = null, $region_override = '')
    {
        $code = trim((string) $code);
        if ($code === '') {
            return null;
        }

        $db = $this->prepare_stuffing_pull_db($period_year);

        $master_row = $db
            ->select('kode_ref, region_id')
            ->where('kode_ref', $code)
            ->limit(1)
            ->get('trnstuffingplan')
            ->row_array();

        if (!$master_row) {
            return null;
        }

        $stored_region    = $this->session->userdata('region_id');
        $effective_region = trim((string) $region_override);

        if ($effective_region === '') {
            $effective_region = trim((string) ($master_row['region_id'] ?? ''));
        }

        if ($effective_region !== '') {
            $this->session->set_userdata('region_id', $effective_region);
        }

        $header = $this->afg_m_stuffing_plan->cetak_stuffing_header($code);

        if (!$header) {
            if ($stored_region !== null && $stored_region !== '') {
                $this->session->set_userdata('region_id', $stored_region);
            } else {
                $this->session->unset_userdata('region_id');
            }
            return null;
        }

        $detail = $this->afg_m_stuffing_plan->cetak_stuffing_detail($code);
        if (!is_array($detail)) {
            $detail = [];
        }

        $instruksi = $this->afg_m_stuffing_plan->get_instruksi($header->sp_id);

        if ($stored_region !== null && $stored_region !== '') {
            $this->session->set_userdata('region_id', $stored_region);
        } else {
            $this->session->unset_userdata('region_id');
        }

        $region = trim((string) $region_override);
        if ($region === '' && isset($header->region_id)) {
            $region = trim((string) $header->region_id);
        }
        if ($region === '') {
            $region = $effective_region;
        }

        $warehouse      = (string) ($header->warehouse ?? '');
        $godownlocation = $this->godown_from_warehouse($warehouse);
        $stuffing_owner = $this->stuffing_owner_from_region($region);

        [$container_number, $seal_number] = $this->split_stuffing_container_seal(
            $header->cont_seal_no ?? ''
        );

        // Contract WMS stuffing terbaru:
        // root: job_no, stuffing_owner, header, stuffing_fills
        $payload = [
            'job_no'         => (string) ($header->kode_ref ?? $code),
            'stuffing_owner' => $stuffing_owner,
            'header'         => [
                'stuffing_date'     => $this->format_wms_stuffing_datetime($header->stuffing_date ?? ''),
                'etd'               => $this->format_wms_stuffing_datetime($header->etd_jkt ?? ''),
                'closing_date'      => $this->format_wms_stuffing_datetime($header->closing_date_time ?? ''),
                'container_size'    => (string) ($header->type_cont_size ?? ''),
                'container_number'  => $container_number,
                'seal_number'       => $seal_number,
                'vessel'            => trim(
                    (string) ($header->vessel ?? '') . ' ' .
                    (string) ($header->voyage ?? '')
                ),
                'destination'       => (string) ($header->destination ?? ''),
                'agent'             => (string) ($header->agen_penagihan ?? ''),
                'instruction'       => (string) ($instruksi->instruksi_stuffing ?? ''),
                'stuffing_location' => $godownlocation,
            ],
            'stuffing_fills' => [],
        ];

        foreach ($detail as $row) {
            $payload['stuffing_fills'][] = [
                'booking_code'    => (string) ($row['booking_code'] ?? ''),
                'target_quantity' => (int) ($row['no_pack_qty'] ?? 0),
                'gross_weight'    => $this->normalize_wms_number($row['weight'] ?? 0),
                'shipper'         => (string) ($row['shipper_print'] ?? ''),
                'packages'        => (string) ($row['no_pack_unit'] ?? ''),
                'main_marking'    => (string) ($row['shipping_mark'] ?? ''),
                'peb'             => (string) ($row['doc'] ?? ''),
                'description'     => (string) ($row['desc_print'] ?? ''),
            ];
        }

        return $payload;
    }

    /**
     * Cari stuffing plan paling baru pada DB periode.
     * Bila region_id diberikan, latest dibatasi region tersebut.
     */
    private function find_latest_stuffing_code($period_year = null, $region = '')
    {
        $db = $this->prepare_stuffing_pull_db($period_year);

        if (!$db->table_exists('trnstuffingplan')) {
            return null;
        }

        $fields = $db->list_fields('trnstuffingplan');
        if (!in_array('kode_ref', $fields, true)) {
            return null;
        }

        $db->select('kode_ref');
        $db->from('trnstuffingplan');
        $db->where('kode_ref IS NOT NULL', null, false);
        $db->where("TRIM(kode_ref) <> ''", null, false);

        $region = trim((string) $region);
        if ($region !== '' && in_array('region_id', $fields, true)) {
            $db->where('region_id', $region);
        }

        if (in_array('sp_id', $fields, true)) {
            $db->order_by('sp_id', 'DESC');
        } elseif (in_array('updated_at', $fields, true)) {
            $db->order_by('updated_at', 'DESC');
        } elseif (in_array('created_at', $fields, true)) {
            $db->order_by('created_at', 'DESC');
        }

        $row = $db->limit(1)->get()->row_array();
        return $row ? trim((string) ($row['kode_ref'] ?? '')) : null;
    }

    /**
     * ============================================================
     * GET STUFFING PLAN FOR WMS
     * ============================================================
     * GET /api/ticket_warehouse/get_stuffing
     * GET /api/ticket_warehouse/get_stuffing?kode_ref=GCL-10126090001A
     *
     * Optional:
     *   X-Period-Year: 2026
     *   ?region_id=Jakarta
     *
     * Tanpa kode_ref: return stuffing plan PALING BARU.
     * Response langsung canonical body yang sama dengan POST WMS.
     */
    public function get_stuffing()
    {
        if (!$this->validate_wms_api_key()) {
            return;
        }

        $period_year = $this->resolve_pull_period_year();
        $region = trim((string) $this->input->get('region_id', true));
        $code = trim((string) $this->input->get('kode_ref', true));

        if ($code === '') {
            $code = (string) $this->find_latest_stuffing_code($period_year, $region);
        }

        if ($code === '') {
            return $this->output_ticket_json(404, [
                'status'  => false,
                'message' => 'STUFFING_PLAN_NOT_FOUND',
            ]);
        }

        $payload = $this->build_stuffing_payload($code, $period_year, $region);

        if (!$payload) {
            return $this->output_ticket_json(404, [
                'status'   => false,
                'kode_ref' => $code,
                'message'  => 'STUFFING_PLAN_NOT_FOUND',
            ]);
        }

        return $this->output_ticket_json(200, $payload);
    }

    /**
     * ============================================================
     * EXISTING STUFFING POST
     * ============================================================
     * Dipertahankan dari controller existing.
     */
    public function post_stuffing()
    {
        if (strtoupper($this->input->method(true)) !== 'POST') {
            return $this->output_ticket_json(405, ['status'=>false,'message'=>'METHOD_NOT_ALLOWED']);
        }

        $code = trim((string)$this->input->post('kode_ref'));
        if ($code === '') {
            return $this->output_ticket_json(422, ['status'=>false,'message'=>'KODE_REF_EMPTY']);
        }

        $period_year = trim((string)$this->session->userdata('db'));
        if (!preg_match('/^20\d{2}$/', $period_year)) $period_year = date('Y');
        $region = trim((string)$this->session->userdata('region_id'));
        $create_payload = $this->build_stuffing_payload($code, $period_year, $region);

        if (!$create_payload) {
            return $this->output_ticket_json(404, ['status'=>false,'kode_ref'=>$code,'message'=>'STUFFING_PLAN_NOT_FOUND']);
        }

        $wms_method = 'POST';
        $outbound_payload = $create_payload;
        $url = $this->api_booking_stuffing;

        log_message('info', 'WMS STUFFING OUTBOUND Method=POST Job=' . $code . ' Payload=' . json_encode($outbound_payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
        $result = $this->send_ticket_request($url, $outbound_payload, 'POST');

        if ($result['curl_errno'] === 0 && $result['curl_error'] === '' && $this->is_wms_stuffing_duplicate($result)) {
            $wms_method = 'PUT';
            $url = $this->api_booking_stuffing . '/' . rawurlencode($code);
            $outbound_payload = $this->build_stuffing_update_payload($create_payload);
            log_message('info', 'WMS STUFFING OUTBOUND Method=PUT Job=' . $code . ' Payload=' . json_encode($outbound_payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
            $result = $this->send_ticket_request($url, $outbound_payload, 'PUT');
        }

        if ($result['curl_errno'] !== 0 || $result['curl_error'] !== '') {
            return $this->output_ticket_json(502, [
                'status'=>false,'kode_ref'=>$code,'http_code'=>$result['http_code'],'wms_method'=>$wms_method,
                'message'=>'WMS_CONNECTION_ERROR','error'=>$result['curl_error'] ?: 'WMS_CURL_ERROR',
                'debug_version'=>'stuffing-debug-v11','debug_fill_count'=>count($create_payload['stuffing_fills'] ?? []),'debug_payload'=>$outbound_payload,
            ]);
        }

        $response_payload = $this->decode_wms_response($result['response']);
        $success = $this->is_wms_success($result['http_code'], $result['response']);

        return $this->output_ticket_json($success ? 200 : 502, [
            'status'=>$success,'kode_ref'=>$code,'http_code'=>$result['http_code'],'wms_method'=>$wms_method,
            'message'=>$success ? ($wms_method === 'PUT' ? 'WMS_STUFFING_UPDATED' : 'WMS_STUFFING_CREATED') : 'WMS_STUFFING_FAILED',
            'response'=>$response_payload,'debug_version'=>'stuffing-debug-v11',
            'debug_fill_count'=>count($create_payload['stuffing_fills'] ?? []),'debug_payload'=>$outbound_payload,
        ]);
    }

    public function delete_stuffing()
    {
        $method = strtoupper($this->input->method(true));
        if (!in_array($method, ['POST','DELETE'], true)) {
            return $this->output_ticket_json(405, ['status'=>false,'message'=>'METHOD_NOT_ALLOWED']);
        }

        $code = trim((string)$this->input->post('kode_ref'));
        if ($code === '' && $method === 'DELETE') {
            $raw = json_decode((string)$this->input->raw_input_stream, true);
            if (is_array($raw)) $code = trim((string)($raw['kode_ref'] ?? $raw['job_no'] ?? ''));
        }
        if ($code === '') {
            return $this->output_ticket_json(422, ['status'=>false,'message'=>'KODE_REF_EMPTY']);
        }

        $url = $this->api_booking_stuffing . '/' . rawurlencode($code);
        $result = $this->send_ticket_request($url, [], 'DELETE');
        if ($result['curl_errno'] !== 0 || $result['curl_error'] !== '') {
            return $this->output_ticket_json(502, [
                'status'=>false,'kode_ref'=>$code,'http_code'=>$result['http_code'],'wms_method'=>'DELETE',
                'message'=>'WMS_CONNECTION_ERROR','error'=>$result['curl_error'] ?: 'WMS_CURL_ERROR'
            ]);
        }

        $http_code = (int)$result['http_code'];
        $success = $http_code === 404 || $this->is_wms_success($http_code, $result['response']);
        return $this->output_ticket_json($success ? 200 : 502, [
            'status'=>$success,'kode_ref'=>$code,'http_code'=>$http_code,'wms_method'=>'DELETE',
            'already_gone'=>$http_code === 404,
            'message'=>$success ? ($http_code === 404 ? 'WMS_STUFFING_ALREADY_DELETED' : 'WMS_STUFFING_DELETED') : 'WMS_STUFFING_DELETE_FAILED',
            'response'=>$this->decode_wms_response($result['response']),
        ]);
    }

}
