<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Charterlink_cron extends CI_Controller
{
    private $poll_policy = [];
    public function __construct()
    {
        parent::__construct();

        $this->load->helper('charterlink');
        $this->load->helper('tracking_route');
        $this->load->model('api/M_api');
        $this->config->load('transvoyant');
        $cfg = $this->config->item('transvoyant');
        $this->poll_policy = $cfg['charterlink_poll_policy'] ?? [];
    }

    /**
     * Normalisasi region dari URL.
     *
     * Contoh:
     * /cron/Charterlink_cron/run_sync?region_id=Surabaya&limit=10
     * /cron/Charterlink_cron/run_sync?region_id=sby&limit=10
     */
    private function get_region_id()
    {
        $region = trim((string) $this->input->get('region_id', true));

        if ($region === '') {
            // Backward compatible: cron lama tanpa region_id tetap Jakarta.
            return 'Jakarta';
        }

        $key = strtolower($region);

        $map = [
            'jkt'      => 'Jakarta',
            'jakarta'  => 'Jakarta',

            'sby'      => 'Surabaya',
            'surabaya' => 'Surabaya',

            'smg'      => 'Semarang',
            'semarang' => 'Semarang',
        ];

        return isset($map[$key]) ? $map[$key] : $region;
    }

    private function get_limit()
    {
        $limit = (int) $this->input->get('limit');

        if ($limit <= 0) {
            $limit = 10;
        }

        if ($limit > 100) {
            $limit = 100;
        }

        return $limit;
    }

    /** Parse CTL dates without converting blank/invalid values into 01/01/1970. */
    private function ctl_date($raw, $actual = false)
    {
        if (!is_string($raw)) {
            return null;
        }
        $raw = trim($raw);
        // CTL may return ISO dates or local dd/mm/yyyy dates.
        if (preg_match('~^(\d{2})/(\d{2})/(\d{4})(.*)$~D', $raw, $m)) {
            $raw = $m[3] . '-' . $m[2] . '-' . $m[1] . $m[4];
        }
        if (!preg_match('~^(\d{4})-(\d{2})-(\d{2})(?:[ T]\d{2}:\d{2}(?::\d{2}(?:\.\d{1,7})?)?(?:\s*(?:Z|[+-]\d{2}:?\d{2}))?)?$~D', $raw, $m)
            || (int) $m[1] <= 1970
            || !checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            return null;
        }
        try {
            $date = new DateTimeImmutable($raw);
            $errors = DateTimeImmutable::getLastErrors();
            if ($errors !== false && ($errors['warning_count'] || $errors['error_count'])) {
                return null;
            }
            if ($actual && $date->getTimestamp() > time()) {
                return null;
            }
            return $date->setTimezone(new DateTimeZone(date_default_timezone_get()));
        } catch (Exception $e) {
            return null;
        }
    }

    private function ctl_port($value)
    {
        $value = strtoupper(trim((string) $value));
        if (in_array($value, ['HONG KONG', 'HONGKONG', 'HKHKG', 'HKG'], true)) {
            return 'HONG KONG';
        }
        if (in_array($value, ['SINGAPORE', 'SGSIN', 'SIN'], true)) {
            return 'SINGAPORE';
        }
        return $value;
    }

    /** Shared HKG/SG second-leg mapping: never promote ETD/ETA to actual. */
    private function ctl_second_leg_update(array $dt, $eta_source, $hbl, $region_id)
    {
        $update = [];
        $departure = $this->ctl_date($dt['atd2nd'] ?? null, true);
        $arrival = $this->ctl_date($dt['ata2nd'] ?? null, true);
        $eta = $this->ctl_date($dt['eta2nd'] ?? null);
        $container = trim((string) ($dt['container2nd'] ?? ''));
        $seal = trim((string) ($dt['sealno2nd'] ?? ''));
        if ($seal !== '') {
            $container .= ($container !== '' ? ' / ' : '') . $seal;
        }
        $vessel = trim((string) ($dt['vessel2nd'] ?? '') . ' ' . (string) ($dt['voyage2nd'] ?? ''));

        // A missing API key is not an explicit empty actual. Leave existing data alone.
        if ($departure) {
            $update['ctl_actual_second_departure_at'] = $departure->format('Y-m-d H:i:s');
        }
        if (array_key_exists('atd2nd', $dt)) {
            $old = $this->db->select('ctl_actual_second_departure_at')->where('hbl_no', $hbl)->where('region_id', $region_id)->limit(1)->get('x_tracking_export')->row_array();
            if (!$departure) $departure = $this->ctl_date($old['ctl_actual_second_departure_at'] ?? null, true);
            $update['dft'] = json_encode([
                'Departure from Transhipment Port' => $departure ? $departure->format('d/m/Y') : null,
                'Container No' => $container !== '' ? $container : null,
            ]);
        }
        if ($eta) {
            $update['lt_pod_eta_at'] = $eta->format('Y-m-d H:i:s');
            $update['lt_pod_eta_source'] = $eta_source;
        }
        if ($arrival) {
            $update['lt_actual_pod_arrival_at'] = $arrival->format('Y-m-d H:i:s');
            $update['ctl_actual_pod_confirmed_at'] = $arrival->format('Y-m-d H:i:s');
            $update['atd'] = json_encode([
                'Actual Time of Arrival (ATA)' => $arrival->format('d/m/Y'),
                'Vessel/Voyage' => $vessel !== '' ? $vessel : null,
            ]);
        } elseif (array_key_exists('ata2nd', $dt)) {
            // Do not erase a previously recorded valid structured actual on a blank API response.
            // The old legacy JSON is not reliable evidence (it could contain eta2nd).
            $existing = $this->db->select('lt_actual_pod_arrival_at')
                ->where('hbl_no', $hbl)->where('region_id', $region_id)
                ->get('x_tracking_export')->result_array();
            // Multiple rows: leave the old JSON untouched if any has a valid structured actual.
            $has_actual = false;
            foreach ($existing as $record) {
                if ($this->ctl_date($record['lt_actual_pod_arrival_at'] ?? null, true)) {
                    $has_actual = true;
                    break;
                }
            }
            if (!$has_actual) {
                $update['atd'] = json_encode([
                    'Actual Time of Arrival (ATA)' => null,
                    'Vessel/Voyage' => $vessel !== '' ? $vessel : null,
                ]);
            }
        }
        foreach (['mbl2nd' => '2nd_mbl_no', 'hbl2nd' => '2nd_hbl_no', 'liner2nd' => 'liner2nd'] as $key => $column) {
            if (array_key_exists($key, $dt)) {
                $update[$column] = $dt[$key];
            }
        }
        return $update;
    }

    /**
     * Test login manual per region:
     * /cron/Charterlink_cron/sinkronisasi_otomatis?region_id=Surabaya
     */
    public function sinkronisasi_otomatis()
    {
        $region_id = $this->get_region_id();

        // Username/password dipilih di helper berdasarkan region_id.
        // Helper harus sudah versi region-aware.
        $hasil_login = login_partner_api('', '', $region_id);

        header('Content-Type: application/json');

        if (!empty($hasil_login['status'])) {
            echo json_encode([
                'status'      => true,
                'region_id'   => $region_id,
                'login_name'  => isset($hasil_login['login_name']) ? $hasil_login['login_name'] : null,
                'message'     => $hasil_login['message'],
                'cookie_path' => isset($hasil_login['cookie_path']) ? $hasil_login['cookie_path'] : null,
            ], JSON_PRETTY_PRINT);
            return;
        }

        echo json_encode([
            'status'    => false,
            'region_id' => $region_id,
            'message'   => isset($hasil_login['message']) ? $hasil_login['message'] : 'Login gagal.',
            'detail'    => $hasil_login,
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Cron utama:
     * /cron/Charterlink_cron/run_sync?region_id=Jakarta&limit=10
     * /cron/Charterlink_cron/run_sync?region_id=Surabaya&limit=10
     * /cron/Charterlink_cron/run_sync?region_id=Semarang&limit=10
     */
    public function run_sync()
    {
        $region_id = $this->get_region_id();
        $limit     = $this->get_limit();

        $response_log = [];

        // M_api::get_target_cron_charterlink WAJIB dipatch agar menerima parameter region_id
        // dan menambahkan WHERE region_id = $region_id.
        $data_kargo = $this->M_api->get_target_cron_charterlink($limit, $region_id, (int)($this->poll_policy['transshipment_days'] ?? 90), (int)($this->poll_policy['direct_days'] ?? 30));

        header('Content-Type: application/json');

        if (empty($data_kargo)) {
            echo json_encode([
                'status'    => false,
                'region_id' => $region_id,
                'limit'     => $limit,
                'message'   => 'Tidak ada data di database yang membutuhkan update tracking untuk region ini.',
            ], JSON_PRETTY_PRINT);
            return;
        }

        $data_kargo = array_values(array_filter($data_kargo, function ($row) {
            return $this->M_api->claim_charterlink_poll($row['id_tracking'] ?? 0);
        }));
        if (!$data_kargo) {
            echo json_encode(['status'=>true,'message'=>'All targets already attempted today','total'=>0]);
            return;
        }

        // Login sesuai region.
        // - Jakarta   => gatewayjkt
        // - Surabaya  => gatewaysby
        // - Semarang  => gatewaysmg
        $hasil_login = login_partner_api('', '', $region_id);

        if (empty($hasil_login['status'])) {
            echo json_encode([
                'status'    => false,
                'region_id' => $region_id,
                'message'   => 'Gagal login API Charterlink.',
                'detail'    => $hasil_login,
            ], JSON_PRETTY_PRINT);
            return;
        }

        $jwt_token = isset($hasil_login['raw_response']['data']['accessToken'])
            ? $hasil_login['raw_response']['data']['accessToken']
            : null;

        if (empty($jwt_token)) {
            echo json_encode([
                'status'    => false,
                'region_id' => $region_id,
                'message'   => 'Login sukses tetapi accessToken kosong.',
                'detail'    => $hasil_login,
            ], JSON_PRETTY_PRINT);
            return;
        }

        foreach ($data_kargo as $row) {
            $hbl         = trim($row['hbl_no'] ?? '');
            $destination = $this->ctl_port($row['destination'] ?? '');
            $consol_destination = $this->ctl_port($row['consol_destination'] ?? '');
            // This endpoint uses the HKG API. SG has a separate tracking endpoint.
            $route_kind = tracking_route_kind($row);
            $is_hkg_final = $route_kind === 'DIRECT' && $destination === 'HONG KONG';
            $is_transit = $route_kind === 'TRANSIT' && $consol_destination === 'HONG KONG';
            $cbm         = trim($row['cbm'] ?? '');

            if ($hbl === '') {
                $response_log[] = [
                    'hbl'       => '',
                    'region_id' => $region_id,
                    'status_db' => 'Dilewati karena HBL kosong',
                ];
                continue;
            }

            $update_data    = [];
            $log_api_status = [];

            // =======================================================
            // 1. API TRACKING, hanya NON-HONG KONG
            // =======================================================
            if ($is_transit) {
                $api_track = get_charterlink_tracking($hbl, $jwt_token);

                if (!empty($api_track['status']) && !empty($api_track['data'])) {
                    $dt = $api_track['data'];

                    $update_data = $this->ctl_second_leg_update(
                        $dt, 'CTL_ETA2ND', $hbl, $region_id
                    );

                    $log_api_status['Tracking'] = 'Sukses';
                } else {
                    $log_api_status['Tracking'] = 'Gagal/Kosong';
                }
            } else {
                $log_api_status['Tracking'] = 'SKIPPED_NOT_TRANSIT';
            }

            // =======================================================
            // 2. HKG IMPORT: only for shipments terminating in HKG.
            // A hub pickup/release is not final delivery for a transshipment.
            // Do not clear old delivery values here: they may be agent/manual data.
            // =======================================================
            if ($is_hkg_final) {
                $api_import = get_charterlink_import($hbl, $cbm, $jwt_token);
                if (!empty($api_import['status']) && !empty($api_import['data'])) {
                    $di = $api_import['data'];
                    $cargo_date = $this->ctl_date($di['pick_up_date'] ?? null, true);
                    $do_date = $this->ctl_date($di['release_date'] ?? null, true);
                    if ($cargo_date) {
                        $update_data['cargo_delivery'] = json_encode([
                            'Cargo delivered' => $cargo_date->format('d/m/Y'),
                        ]);
                    }
                    if ($do_date) {
                        $update_data['pickup_do'] = json_encode([
                            'Pickup DO' => $do_date->format('d/m/Y'),
                        ]);
                    }
                    $log_api_status['Import'] = 'Sukses';
                } else {
                    $log_api_status['Import'] = 'Gagal/Kosong';
                }
            } else {
                $log_api_status['Import'] = 'SKIPPED_NOT_FINAL_HKG';
            }

            if (empty($update_data)) {
                $response_log[] = [
                    'hbl' => $hbl,
                    'region_id' => $region_id,
                    'status_api' => $log_api_status,
                    'status_db' => 'Tidak ada data baru yang valid untuk update',
                    'affected_db' => 0,
                ];
                continue;
            }

            // =======================================================
            // 3. Update database
            // =======================================================
            $update_data['updated_at'] = date('Y-m-d H:i:s');
            $update_data['updated_by'] = 'CRON CTL ' . strtoupper($region_id);

            $this->db->where('id_tracking', (int)$row['id_tracking']);
            $this->db->where('hbl_no', $hbl);
            $this->db->where('region_id', $region_id);
            $this->db->update('x_tracking_export', $update_data);

            $affected_db = $this->db->affected_rows();

            $response_log[] = [
                'hbl'             => $hbl,
                'region_id'       => $region_id,
                'kategori'        => $is_hkg_final ? 'HKG_FINAL' : 'NON_HKG_FINAL',
                'status_api'      => $log_api_status,
                'status_db'       => $affected_db >= 0 ? 'Selesai update DB' : 'Gagal update DB',
                'affected_db'     => $affected_db,
                'data_diupdate'   => $update_data,
            ];
        }

        echo json_encode([
            'status'        => true,
            'region_id'     => $region_id,
            'limit'         => $limit,
            'login_name'    => isset($hasil_login['login_name']) ? $hasil_login['login_name'] : null,
            'total_hit_api' => count($response_log),
            'log_detail'    => $response_log,
        ], JSON_PRETTY_PRINT);
    }
	
	public function run_sync_singapore()
{
    header('Content-Type: application/json');

    $response_log = [];
    $token_by_region = [];

    // =========================================================
    // 1. LIMIT
    // =========================================================
    $limit = (int) $this->input->get('limit');

    if ($limit <= 0) {
        $limit = 10;
    }

    if ($limit > 100) {
        $limit = 100;
    }

    // =========================================================
    // 2. OPTIONAL FILTER REGION
    //
    // kosong = ambil semua region
    // =========================================================
    $region_filter = trim(
        (string) $this->input->get('region_id', true)
    );

    if ($region_filter !== '') {

        $key = strtolower($region_filter);

        $map = [
            'jkt'      => 'Jakarta',
            'jakarta'  => 'Jakarta',

            'sby'      => 'Surabaya',
            'surabaya' => 'Surabaya',

            'smg'      => 'Semarang',
            'semarang' => 'Semarang',
        ];

        $region_filter =
            isset($map[$key])
                ? $map[$key]
                : $region_filter;

    } else {

        $region_filter = null;
    }

    // =========================================================
    // 3. AMBIL TARGET CTL SINGAPORE
    // =========================================================
    $data_kargo = $this->M_api
        ->get_target_cron_charterlink_singapore(
            $limit,
            $region_filter,
            (int)($this->poll_policy['transshipment_days'] ?? 90)
        );

    if (empty($data_kargo)) {

        echo json_encode([
            'status' => true,
            'branch' => 'SG',
            'region_filter' => $region_filter,
            'message' =>
                'Tidak ada Charterlink Singapore yang perlu di-update.',
            'total' => 0
        ], JSON_PRETTY_PRINT);

        return;
    }

    // =========================================================
    // 4. LOOP TARGET
    //
    // Login dilakukan per region dan token dicache.
    // =========================================================
    foreach ($data_kargo as $row) {

        $hbl = trim(
            (string) ($row['hbl_no'] ?? '')
        );

        $agent = trim(
            (string) ($row['id_agent'] ?? '')
        );

        $destination = strtoupper(
            trim(
                (string) ($row['destination'] ?? '')
            )
        );

        $consol_destination = strtoupper(
            trim(
                (string) ($row['consol_destination'] ?? '')
            )
        );

        $row_region = trim(
            (string) ($row['region_id'] ?? '')
        );

        if ($row_region === '') {
            $row_region = 'Jakarta';
        }

        // Normalisasi region
        $region_key_raw = strtolower($row_region);

        $region_map = [
            'jkt'      => 'Jakarta',
            'jakarta'  => 'Jakarta',

            'sby'      => 'Surabaya',
            'surabaya' => 'Surabaya',

            'smg'      => 'Semarang',
            'semarang' => 'Semarang',
        ];

        $row_region =
            isset($region_map[$region_key_raw])
                ? $region_map[$region_key_raw]
                : $row_region;

        $region_key = strtolower($row_region);

        if ($hbl === '') {

            $response_log[] = [
                'hbl' => '',
                'agent' => $agent,
                'region_id' => $row_region,
                'branch' => 'SG',
                'api_status' => 'SKIPPED_HBL_EMPTY'
            ];

            continue;
        }

        if (tracking_route_kind($row) !== 'TRANSIT' || tracking_route_port($row['consol_destination'] ?? '') !== 'SINGAPORE') {
            $response_log[] = ['hbl'=>$hbl,'region_id'=>$row_region,'branch'=>'SG','api_status'=>'SKIPPED_NOT_TRANSIT'];
            continue;
        }

        if (!$this->M_api->claim_charterlink_poll($row['id_tracking'] ?? 0)) {
            $response_log[] = ['hbl'=>$hbl,'region_id'=>$row_region,'branch'=>'SG','api_status'=>'ALREADY_ATTEMPTED_TODAY'];
            continue;
        }

        // =====================================================
        // 5. LOGIN PER REGION
        //
        // Jakarta   -> gatewayjkt
        // Surabaya  -> gatewaysby
        // Semarang  -> gatewaysmg
        //
        // Login hanya 1x per region per eksekusi cron.
        // =====================================================
        if (!isset($token_by_region[$region_key])) {

            $hasil_login = login_partner_api(
                '',
                '',
                $row_region
            );

            if (
                empty($hasil_login['status']) ||
                empty(
                    $hasil_login['raw_response']['data']['accessToken']
                )
            ) {

                $token_by_region[$region_key] = [
                    'failed' => true,
                    'login_name' =>
                        $hasil_login['login_name'] ?? null,
                    'detail' => $hasil_login
                ];

            } else {

                $token_by_region[$region_key] = [
                    'failed' => false,

                    'token' =>
                        $hasil_login['raw_response']['data']['accessToken'],

                    'login_name' =>
                        $hasil_login['login_name'] ?? null
                ];
            }
        }

        // =====================================================
        // REGION LOGIN GAGAL
        // =====================================================
        if (
            !empty(
                $token_by_region[$region_key]['failed']
            )
        ) {

            $response_log[] = [
                'hbl' => $hbl,
                'agent' => $agent,
                'destination' => $destination,
                'consol_destination' =>
                    $consol_destination,
                'region_id' => $row_region,

                'login_name' =>
                    $token_by_region[$region_key]['login_name']
                    ?? null,

                'branch' => 'SG',
                'api_status' => 'LOGIN_FAILED',

                'login_detail' =>
                    $token_by_region[$region_key]['detail']
                    ?? null
            ];

            continue;
        }

        $jwt_token =
            $token_by_region[$region_key]['token'];

        $login_name =
            $token_by_region[$region_key]['login_name']
            ?? null;

        // =====================================================
        // 6. TRACKING SG
        //
        // hbl=xxxxx&branch=SG
        // =====================================================
        $api_track = get_charterlink_tracking_sg(
            $hbl,
            $jwt_token
        );

        // =====================================================
        // API GAGAL
        //
        // PENTING:
        // Jangan update updated_at. Percobaan sudah dicatat, retry besok.
        // =====================================================
        if (
            empty($api_track['status']) ||
            empty($api_track['data'])
        ) {

            $response_log[] = [
                'hbl' => $hbl,
                'agent' => $agent,
                'destination' => $destination,
                'consol_destination' =>
                    $consol_destination,
                'region_id' => $row_region,

                'login_name' => $login_name,

                'branch' => 'SG',

                'api_status' =>
                    'EMPTY_OR_FAILED',

                'api_detail' =>
                    $api_track
            ];

            continue;
        }

        // =====================================================
        // 7. API SUCCESS
        // =====================================================
        $dt = $api_track['data'];

        $update_data = $this->ctl_second_leg_update(
            $dt, 'CTL_SG_ETA2ND', $hbl, $row_region
        );
        // SG tracking does not supply a verified final cargo delivery field.
        // Never synthesize cargo_delivery/pickup_do from second-leg tracking.
        if (empty($update_data)) {
            $response_log[] = [
                'hbl' => $hbl,
                'region_id' => $row_region,
                'branch' => 'SG',
                'api_status' => 'NO_VALID_UPDATE',
            ];
            continue;
        }

        // =====================================================
        // SUCCESS MARKER
        // =====================================================
        $update_data['updated_at'] =
            date('Y-m-d H:i:s');

        $update_data['updated_by'] =
            'CRON CTL SG';

        // =====================================================
        // 8. UPDATE DB
        //
        // gunakan HBL + region
        // =====================================================
        $this->db->where('id_tracking', (int)$row['id_tracking']);
        $this->db->where(
            'hbl_no',
            $hbl
        );

        $this->db->where(
            'region_id',
            $row_region
        );

        $this->db->update(
            'x_tracking_export',
            $update_data
        );

        $affected_rows =
            $this->db->affected_rows();

        // =====================================================
        // RESPONSE LOG
        // =====================================================
        $response_log[] = [
            'hbl' => $hbl,
            'agent' => $agent,

            'destination' =>
                $destination,

            'consol_destination' =>
                $consol_destination,

            'region_id' =>
                $row_region,

            'login_name' =>
                $login_name,

            'branch' =>
                'SG',

            'api_status' =>
                'SUCCESS',

            'db_affected_rows' =>
                $affected_rows,

            'data_diupdate' =>
                $update_data
        ];
    }

    // =========================================================
    // 9. SUMMARY
    // =========================================================
    $success = 0;
    $failed  = 0;

    foreach ($response_log as $log) {

        if (
            isset($log['api_status']) &&
            $log['api_status'] === 'SUCCESS'
        ) {
            $success++;
        } else {
            $failed++;
        }
    }

    echo json_encode([
        'status' => true,

        'branch' =>
            'SG',

        'consol_destination' =>
            'SINGAPORE',

        'region_filter' =>
            $region_filter,

        'total_target' =>
            count($data_kargo),

        'total_processed' =>
            count($response_log),

        'success' =>
            $success,

        'failed' =>
            $failed,

        'login_regions' =>
            array_map(
                function ($x) {
                    return [
                        'login_name' =>
                            $x['login_name'] ?? null,

                        'failed' =>
                            !empty($x['failed'])
                    ];
                },
                $token_by_region
            ),

        'log_detail' =>
            $response_log

    ], JSON_PRETTY_PRINT);
}
}
