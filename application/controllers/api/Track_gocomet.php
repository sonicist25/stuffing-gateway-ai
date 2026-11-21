<?php
use chriskacerguis\RestServer\RestController;

class Track_gocomet extends RestController
{
	private $jarvisAuthToken = null;
    private $allowJarvis = true;
    private $aisMode = [
        'include_ais' => true,
        'include_ais_param' => null,
        'mode' => '',
    ];
	private $portAliasCache = [];
	
	private function get_related_port_codes($code)
	{
		$code = strtoupper(trim((string)$code));

		// Mapping operasional khusus.
		// Ini perlu karena table master biasanya menyimpan IDTPP dan IDJKT sebagai code berbeda.
		$map = [
			'IDTPP' => ['IDJKT'],
			'IDJKT' => ['IDTPP'],

			// Tambah di sini kalau ada equivalence operasional lain
			// 'THLCH' => ['THBKK'],
			// 'THBKK' => ['THLCH'],
		];

		return $map[$code] ?? [];
	}

	private function get_port_aliases_from_master($code)
	{
		$code = strtoupper(trim((string)$code));

		if ($code === '') {
			return [];
		}

		if (isset($this->portAliasCache[$code])) {
			return $this->portAliasCache[$code];
		}

		$codesToLookup = array_values(array_unique(array_merge(
			[$code],
			$this->get_related_port_codes($code)
		)));

		$aliases = [];

		foreach ($codesToLookup as $lookupCode) {
			$lookupCode = strtoupper(trim((string)$lookupCode));
			if ($lookupCode === '') continue;

			$aliases[] = $lookupCode;

			try {
				$this->dbx->select('code, name, country_id, city_id, latitude, longitude');
				$this->dbx->from('tableName'); // GANTI kalau nama tabel asli beda
				$this->dbx->where('is_active', 'TRUE');
				$this->dbx->where(
					"UPPER(TRIM(code)) = " . $this->dbx->escape($lookupCode),
					null,
					false
				);
				$this->dbx->limit(1);

				$q = $this->dbx->get();

				if ($q && $q->num_rows() > 0) {
					$row = $q->row_array();

					if (!empty($row['code'])) {
						$aliases[] = strtoupper(trim((string)$row['code']));
					}

					if (!empty($row['name'])) {
						$aliases[] = strtoupper(trim((string)$row['name']));
					}

					if (!empty($row['country_id']) && !empty($row['city_id'])) {
						$aliases[] = strtoupper(trim((string)$row['country_id'] . $row['city_id']));
					}
				}
			} catch (Exception $ex) {
				log_message('error', 'PORT_ALIAS_MASTER_ERROR=' . $ex->getMessage());
			}
		}

		$aliases = array_values(array_unique(array_filter(array_map(function ($v) {
			return strtoupper(trim((string)$v));
		}, $aliases))));

		$this->portAliasCache[$code] = $aliases;

		return $aliases;
	}

    public function __construct(){
        parent::__construct();
        $this->load->model(array('api/m_api','api/m_tracking_api'));
        $this->dbx = $this->m_api->dbx;
    }

    /**
     * Mode AIS/Jarvis dipakai oleh FE map biasa.
     * Cron Transvoyant harus mengirim include_ais=0 atau mode=events_only.
     * Ketika disabled, seluruh function Jarvis di bawah akan return sebelum login/token/curl.
     */
    private function parse_ais_mode($includeAisParam = null, $modeParam = null)
    {
        $mode = strtolower(trim((string)$modeParam));
        $param = is_null($includeAisParam) ? null : strtolower(trim((string)$includeAisParam));

        $includeAis = true;

        if (
            $includeAisParam === '0' ||
            $includeAisParam === 0 ||
            $param === 'false' ||
            $param === 'no' ||
            $param === 'off' ||
            $mode === 'events_only'
        ) {
            $includeAis = false;
        }

        return [
            'include_ais' => $includeAis,
            'include_ais_param' => $includeAisParam,
            'mode' => $mode,
        ];
    }

    private function configure_ais_mode_from_request()
    {
        $includeAisParam = $this->input->get('include_ais');
        $modeParam = $this->input->get('mode');

        $this->aisMode = $this->parse_ais_mode($includeAisParam, $modeParam);
        $this->allowJarvis = (bool)$this->aisMode['include_ais'];

        if (!$this->allowJarvis) {
            log_message('error', 'DEBUG_JARVIS_DISABLED=' . json_encode([
                'si_number' => $this->get('si_number'),
                'include_ais' => $includeAisParam,
                'mode' => $this->aisMode['mode'],
                'uri' => $_SERVER['REQUEST_URI'] ?? '',
                'time' => date('Y-m-d H:i:s'),
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        }

        return $this->aisMode;
    }

    private function is_jarvis_allowed()
    {
        return $this->allowJarvis === true;
    }

    private function log_jarvis_blocked($tag, $context = [])
    {
        $payload = array_merge([
            'tag' => $tag,
            'reason' => 'include_ais_disabled',
            'include_ais' => $this->aisMode['include_ais_param'] ?? null,
            'mode' => $this->aisMode['mode'] ?? '',
            'uri' => $_SERVER['REQUEST_URI'] ?? '',
            'time' => date('Y-m-d H:i:s'),
        ], is_array($context) ? $context : []);

        log_message('error', 'DEBUG_JARVIS_BLOCKED=' . json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    private function login_jarvis()
    {
        if (!$this->is_jarvis_allowed()) {
            $this->log_jarvis_blocked('login_jarvis');
            return null;
        }

        if (!empty($this->jarvisAuthToken)) {
            return $this->jarvisAuthToken;
        }

        $url = 'https://api-gw.jar-vis.com/jarvis/api/v1/auth/client/login';

        $payload = [
            'email'    => 'sony01@gateway-id.com',
            'password' => 'sony',
        ];

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Jarvis-Api-Key: JARVIS-Z0CXPNc0Xu',
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 31,
        ]);

        $body = curl_exec($ch);
        $err  = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($err) {
            log_message('error', 'Jarvis login curl error: ' . $err);
            return null;
        }

        if ($code < 200 || $code >= 300 || empty($body)) {
            log_message('error', 'Jarvis login bad response HTTP ' . $code . ' | body: ' . $body);
            return null;
        }

        $json = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            log_message('error', 'Jarvis login invalid JSON: ' . json_last_error_msg() . ' | body: ' . $body);
            return null;
        }

        $accessToken = null;

        if (!empty($json['data']['accessToken'])) {
            $accessToken = $json['data']['accessToken'];
        } elseif (!empty($json['accessToken'])) {
            $accessToken = $json['accessToken'];
        } elseif (!empty($json['token'])) {
            $accessToken = $json['token'];
        } elseif (!empty($json['access_token'])) {
            $accessToken = $json['access_token'];
        }

        if (empty($accessToken)) {
            log_message('error', 'Jarvis login token not found | body: ' . $body);
            return null;
        }

        $this->jarvisAuthToken = 'Bearer ' . $accessToken;

        return $this->jarvisAuthToken;
    }

    private function fetch_jarvis($endpoint)
    {
        if (!$this->is_jarvis_allowed()) {
            $this->log_jarvis_blocked('fetch_jarvis', ['endpoint' => $endpoint]);
            return [
                'success' => false,
                'skipped' => true,
                'reason' => 'include_ais_disabled',
            ];
        }

        // Ambil token dinamis (login)
        $token = $this->login_jarvis();

        // Batalkan request jika gagal mendapatkan token
        if (!$token) {
            return array('success' => false, 'message' => 'Failed to obtain Jarvis Auth Token');
        }

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Authorization: ' . $token, // Gunakan token dinamis di sini
                'Jarvis-Api-Key: JARVIS-Z0CXPNc0Xu'
            ),
        ));
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        
        if ($err) return null;
        
        return json_decode($response, true);
    }
	

    // AGUNG RAYA - import warehouse, HBL only.
    private function is_agung_raya_warehouse($name)
    {
        $name = strtoupper(trim((string)$name));
        $name = preg_replace('/[^A-Z0-9]+/', ' ', $name);
        return (bool)preg_match('/(?:^| )AGUNG RAYA(?: |$)/', $name);
    }

    private function fetch_agung_raya_hbl($hbl)
    {
        $hbl = trim((string)$hbl);
        $username = getenv('AGUNG_RAYA_API_USERNAME');
        $password = getenv('AGUNG_RAYA_API_PASSWORD');
        $baseUrl = getenv('AGUNG_RAYA_API_BASE_URL') ?: 'https://agungwarehouse.co.id:8581';
        if ($hbl === '' || $username === false || $username === '' || $password === false || $password === '') {
            log_message('error', 'AGUNG_RAYA: missing HBL or API configuration');
            return [];
        }
        if (preg_match('/[\r\n]/', $username . $password) || !preg_match('~^https://~i', $baseUrl)) {
            log_message('error', 'AGUNG_RAYA: invalid API configuration');
            return [];
        }
        $url = rtrim($baseUrl, '/') . '/api/raya/v1/search-lcl-by-hbl?hbl=' . rawurlencode($hbl);
        $ch = curl_init($url);
        if (!$ch) return [];
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPGET => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'X-Api-Username: ' . $username,
                'X-Api-Password: ' . $password,
                'X-CSRF-TOKEN;',
            ],
        ]);
        $body = curl_exec($ch);
        $errno = curl_errno($ch);
        $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($errno || $http !== 200 || !is_string($body)) {
            // Never log credentials or vendor response containing shipment details.
            log_message($http === 404 ? 'debug' : 'error', 'AGUNG_RAYA: HTTP=' . $http . ' CURL_ERRNO=' . $errno);
            return [];
        }
        $json = json_decode($body, true);
        if (!is_array($json) || ($json['status'] ?? '') !== 'SUCCESS' || !is_array($json['data'] ?? null)) {
            log_message('error', 'AGUNG_RAYA: invalid or unsuccessful response');
            return [];
        }
        return $json['data'];
    }

    // Vendor dates are local warehouse time (assumed Asia/Jakarta).
    // Never turn null, zero date, invalid calendar date or future date into an actual milestone.
    private function agung_raya_actual_time($date, $time = '')
    {
        $date = trim((string)$date);
        $time = trim((string)$time);
        if ($date === '') return null;
        $value = $date . ($time !== '' ? ' ' . $time : '');
        $tz = new DateTimeZone('Asia/Jakarta');
        foreach (['Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d'] as $format) {
            $dt = DateTimeImmutable::createFromFormat('!' . $format, $value, $tz);
            if (!$dt || $dt->format($format) !== $value || (int)$dt->format('Y') < 1900) continue;
            if ($dt->getTimestamp() > time()) return null;
            return ['ts' => $dt->getTimestamp(), 'text' => $dt->format($format === 'Y-m-d' ? 'd/m/Y' : 'd/m/Y H:i:s')];
        }
        return null;
    }

    private function normalize_agung_raya_hbl($data, $hbl)
    {
        $master = $data['master'] ?? null;
        $details = $data['details'] ?? null;
        if (!is_array($master) || !is_array($details) || !$details) return null;
        // Confirm that vendor data belongs exclusively to the requested house BL.
        foreach ($details as $detail) {
            if (!is_array($detail) || strcasecmp(trim((string)($detail['hbl'] ?? '')), $hbl) !== 0) {
                log_message('error', 'AGUNG_RAYA: HBL mismatch or missing HBL');
                return null;
            }
        }
        $timeline = [];
        $add = function ($name, $location, $when, $badge = 'Done') use (&$timeline) {
            if (!$when) return;
            $timeline[] = [
                'status_name' => $name,
                'event_name' => $name,
                'location_name' => $location,
                'event_time' => $when['text'],
                'badge_status' => $badge,
                'images' => [],
                '_ts' => $when['ts'],
            ];
        };
        $add('Container Received at Warehouse', 'Warehouse Container Received',
            $this->agung_raya_actual_time($master['date_in'] ?? '', $master['time_in'] ?? ''));
        $add('Stripping Cargo', 'Unloading Cargo at CFS',
            $this->agung_raya_actual_time($master['unstuffing_date_time'] ?? ''));
        $releasedCount = 0;
        $lastRelease = null;
        foreach ($details as $detail) {
            $release = $this->agung_raya_actual_time($detail['date_released'] ?? '', $detail['time_released'] ?? '');
            if (!$release) continue;
            $releasedCount++;
            if (!$lastRelease || $release['ts'] > $lastRelease['ts']) $lastRelease = $release;
        }
        $allReleased = $releasedCount === count($details);
        if ($lastRelease) {
            $add($allReleased ? 'Release Cargo' : 'Partial Cargo Release',
                $allReleased ? 'Final Delivery' : 'Warehouse Partial Release', $lastRelease,
                $allReleased ? 'Done' : 'Partial');
        }
        usort($timeline, function ($a, $b) { return $a['_ts'] <=> $b['_ts']; });
        $latest = $timeline ? end($timeline) : [];
        foreach ($timeline as &$event) unset($event['_ts']);
        unset($event);
        // Keep response compact; do not expose raw manifest, consignee or customs numbers.
        return [
            'source' => 'agung_raya',
            'queried_no_bl' => $hbl,
            'no_bl' => $hbl,
            'warehouse' => 'Agung Raya',
            'latest_status' => $latest['status_name'] ?? '',
            'latest_update' => $latest['event_time'] ?? '',
            'released_items' => $releasedCount,
            'total_items' => count($details),
            'all_released' => $allReleased,
            'timeline' => $timeline,
            'documents' => [],
        ];
    }

    // =====================================================
    // BINTANG LOGISTIK IMPORT WAREHOUSE PROGRESS
    // =====================================================
    private function fetch_bintang_progress_bl($noBl)
    {
        $noBl = trim((string)$noBl);

        if ($noBl === '') {
            return [];
        }

        $url = 'https://api.bintanglogistikindonusa.id/api/customer/progressBL?no_bl=' . rawurlencode($noBl);

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPGET        => true,
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
            ],
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT        => 25,
        ]);

        $body = curl_exec($ch);
        $err  = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($err) {
            log_message('error', 'BINTANG_PROGRESS_CURL_ERROR=' . $err . ' | no_bl=' . $noBl);
            return [];
        }

        if ($code < 200 || $code >= 300 || empty($body)) {
            log_message('error', 'BINTANG_PROGRESS_BAD_RESPONSE=' . json_encode([
                'no_bl' => $noBl,
                'http_code' => $code,
                'body' => substr((string)$body, 0, 1000),
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            return [];
        }

        $json = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($json)) {
            log_message('error', 'BINTANG_PROGRESS_INVALID_JSON=' . json_encode([
                'no_bl' => $noBl,
                'error' => json_last_error_msg(),
                'body' => substr((string)$body, 0, 1000),
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            return [];
        }

        if (empty($json['manifest']) && empty($json['details']) && empty($json['timeline'])) {
            return [];
        }

        $json['_queried_no_bl'] = $noBl;

        return $json;
    }

    private function is_valid_bintang_progress($data)
    {
        return (
            is_array($data) &&
            (
                !empty($data['manifest']) ||
                !empty($data['details']) ||
                !empty($data['timeline'])
            )
        );
    }

    private function format_bintang_datetime($value)
    {
        $value = trim((string)$value);

        if ($value === '') {
            return '';
        }

        $dt = $this->parse_iso_or_flexible_dt($value);

        if ($dt instanceof DateTime) {
            if (strpos($value, 'T') !== false || substr($value, -1) === 'Z') {
                $dt->setTimezone(new DateTimeZone('Asia/Jakarta'));
            }

            $timePart = $dt->format('H:i:s');
            if ($timePart === '00:00:00' && !preg_match('/\d{2}:\d{2}/', $value)) {
                return $dt->format('d/m/Y');
            }

            return $dt->format('d/m/Y H:i:s');
        }

        return $value;
    }

    private function bintang_abs_url($path)
    {
        $path = trim((string)$path);

        if ($path === '') {
            return '';
        }

        if (preg_match('/^https?:\/\//i', $path)) {
            return $path;
        }

        return 'https://api.bintanglogistikindonusa.id/' . ltrim($path, '/');
    }

    private function normalize_bintang_images($images)
    {
        if (empty($images) || !is_array($images)) {
            return [];
        }

        $out = [];

        foreach ($images as $img) {
            if (!is_array($img)) continue;

            $filePath = trim((string)($img['file_path'] ?? ''));
            if ($filePath === '') continue;

            $out[] = [
                'id' => $img['id'] ?? null,
                'shipment_id' => $img['shipment_id'] ?? null,
                'file_path' => $filePath,
                'url' => $this->bintang_abs_url($filePath),
                'upload_group_id' => $img['upload_group_id'] ?? null,
                'created_at' => $img['created_at'] ?? null,
                'updated_at' => $img['updated_at'] ?? null,
            ];
        }

        return $out;
    }

    private function normalize_bintang_documents($documents)
    {
        if (empty($documents) || !is_array($documents)) {
            return [];
        }

        $out = [];

        foreach ($documents as $type => $rows) {
            if (!is_array($rows)) {
                $out[$type] = [];
                continue;
            }

            $out[$type] = [];
            foreach ($rows as $row) {
                if (!is_array($row)) continue;

                $filePath = trim((string)($row['file_path'] ?? ''));
                $row['url'] = $filePath !== '' ? $this->bintang_abs_url($filePath) : '';
                $out[$type][] = $row;
            }
        }

        return $out;
    }

    private function get_latest_bintang_status($timeline)
    {
        if (empty($timeline) || !is_array($timeline)) {
            return null;
        }

        $latest = null;
        $latestTs = 0;

        foreach ($timeline as $row) {
            if (!is_array($row)) continue;

            $ts = $this->normalize_event_timestamp($row['update_date'] ?? $row['updated_at'] ?? $row['created_at'] ?? '');

            if ($ts >= $latestTs) {
                $latestTs = $ts;
                $latest = $row;
            }
        }

        return $latest;
    }

    private function map_bintang_status_event($statusName)
    {
        $s = strtolower(trim((string)$statusName));

        if ($s === 'receive order') {
            return ['event_name' => 'Receive Order', 'location_name' => 'Warehouse Order'];
        }

        if ($s === 'overbrengen') {
            return ['event_name' => 'Overbrengen', 'location_name' => 'Customs / Transfer'];
        }

        if ($s === 'izin stripping') {
            return ['event_name' => 'Izin Stripping', 'location_name' => 'Stripping Permit'];
        }

        if ($s === 'stripping') {
            return ['event_name' => 'Stripping Cargo', 'location_name' => 'Unloading Cargo at CFS'];
        }

        if ($s === 'barang masuk gudang') {
            return ['event_name' => 'Barang Masuk Gudang', 'location_name' => 'Cargo Received at Warehouse'];
        }

        if ($s === 'pembayaran lunas') {
            return ['event_name' => 'Pembayaran Lunas', 'location_name' => 'Payment Completed'];
        }

        if ($s === 'barang keluar') {
            return ['event_name' => 'Release Cargo', 'location_name' => 'Final Delivery'];
        }

        return ['event_name' => $statusName, 'location_name' => 'Warehouse Progress'];
    }

    private function get_point_by_code($code)
    {
        $code = strtoupper(trim((string)$code));

        if ($code === '') {
            return null;
        }

        try {
            $this->dbx->select('latitude, longitude');
            $this->dbx->from('tableName'); // GANTI kalau nama tabel port master asli berbeda
            $this->dbx->where('is_active', 'TRUE');
            $this->dbx->where(
                "UPPER(TRIM(code)) = " . $this->dbx->escape($code),
                null,
                false
            );
            $this->dbx->limit(1);

            $q = $this->dbx->get();

            if ($q && $q->num_rows() > 0) {
                $row = $q->row();

                if (
                    isset($row->latitude, $row->longitude) &&
                    is_numeric($row->latitude) &&
                    is_numeric($row->longitude)
                ) {
                    return $row->latitude . ', ' . $row->longitude;
                }
            }
        } catch (Exception $ex) {
            log_message('error', 'GET_POINT_BY_CODE_ERROR=' . $ex->getMessage());
        }

        return null;
    }

	private function normalize_route_port($value)
{
    $value = trim((string)$value);

    // buang suffix event dari GoComet route
    $value = preg_replace('/\s*-\s*(Origin Departure|Arrival|Trans Shipment Arrival|Trans Shipment Departure).*$/i', '', $value);

    return trim($value);
}

private function get_historical_ais_from_jarvis_chunked($imoNumber, $historyFromIso, $historyUntilIso, $chunkDays = 3)
{
    if (!$this->is_jarvis_allowed()) {
        $this->log_jarvis_blocked('get_historical_ais_from_jarvis_chunked', [
            'imo' => $imoNumber,
            'from' => $historyFromIso,
            'until' => $historyUntilIso,
        ]);

        return [
            'source' => 'jarvis_chunked',
            'tracks' => [],
            'raw' => null,
            'skipped' => true,
        ];
    }

    $imoNumber = trim((string)$imoNumber);

    if ($imoNumber === '' || empty($historyFromIso) || empty($historyUntilIso)) {
        return [];
    }

    $fromDt = $this->parse_iso_or_flexible_dt($historyFromIso);
    $untilDt = $this->parse_iso_or_flexible_dt($historyUntilIso);

    if (!($fromDt instanceof DateTime) || !($untilDt instanceof DateTime)) {
        return [];
    }

    $fromDt->setTimezone(new DateTimeZone('UTC'));
    $untilDt->setTimezone(new DateTimeZone('UTC'));

    if ($fromDt >= $untilDt) {
        return [];
    }

    $allTracks = [];
    $rawChunks = [];

    $cursor = clone $fromDt;

    while ($cursor < $untilDt) {
        $chunkFrom = clone $cursor;
        $chunkUntil = clone $cursor;
        $chunkUntil->modify('+' . (int)$chunkDays . ' days');

        if ($chunkUntil > $untilDt) {
            $chunkUntil = clone $untilDt;
        }

        $fromIso = $chunkFrom->format('Y-m-d\TH:i:s\Z');
        $untilIso = $chunkUntil->format('Y-m-d\TH:i:s\Z');

        $raw = $this->get_historical_ais_from_jarvis($imoNumber, $fromIso, $untilIso);

        if (!empty($raw['_jarvis_error'])) {
            $rawChunks[] = [
                'from' => $fromIso,
                'until' => $untilIso,
                'raw' => $raw,
                'track_count' => 0,
            ];

            // 5xx/HTTP error pada endpoint yang sama tidak perlu diulang per chunk.
            return [
                'source' => 'jarvis_chunked',
                'tracks' => [],
                'raw' => $rawChunks,
                'historical_tracks_error' => $raw,
            ];
        }

        $normalized = $this->normalize_jarvis_history($raw);

        log_message('error', 'AIS HIST CHUNK TRY: imo=' . $imoNumber
            . ' from=' . $fromIso
            . ' until=' . $untilIso
            . ' tracks=' . (isset($normalized['tracks']) ? count($normalized['tracks']) : 0)
        );

        $rawChunks[] = [
            'from' => $fromIso,
            'until' => $untilIso,
            'raw' => $raw,
            'track_count' => isset($normalized['tracks']) ? count($normalized['tracks']) : 0,
        ];

        if (!empty($normalized['tracks'])) {
            foreach ($normalized['tracks'] as $track) {
                $allTracks[] = $track;
            }
        }

        $cursor = clone $chunkUntil;

        // kecilkan tekanan ke API
        usleep(200000);
    }

    if (empty($allTracks)) {
        return [
            'source' => 'jarvis_chunked',
            'tracks' => [],
            'raw' => $rawChunks,
        ];
    }

    // dedupe titik by lat/lon/timestamp
    $seen = [];
    $deduped = [];

    foreach ($allTracks as $track) {
        $lat = $track['lat'] ?? null;
        $lon = $track['lon'] ?? null;
        $ts  = $track['timestamp'] ?? '';

        if (!$this->is_valid_lat_lon($lat, $lon)) {
            continue;
        }

        $key = md5((string)$lat . '|' . (string)$lon . '|' . (string)$ts);

        if (isset($seen[$key])) {
            continue;
        }

        $seen[$key] = true;
        $track['_sort_ts'] = $this->normalize_event_timestamp($track['timestamp'] ?? '');
        $deduped[] = $track;
    }

    usort($deduped, function ($a, $b) {
        return ((int)($a['_sort_ts'] ?? 0)) <=> ((int)($b['_sort_ts'] ?? 0));
    });

    foreach ($deduped as &$track) {
        unset($track['_sort_ts']);
    }
    unset($track);

    return [
        'source' => 'jarvis_chunked',
        'tracks' => $deduped,
        'raw' => $rawChunks,
    ];
}

private function get_route_source_priority($route)
{
    $source = strtolower(trim((string)($route['_source_name'] ?? '')));

    if ($source === 'local_export' || $source === 'local_import' || $source === 'local') {
        return 100;
    }

    if ($source === 'gocomet') {
        return 50;
    }

    return 0;
}

private function clean_route_internal_fields($route)
{
    unset(
        $route['_source_name'],
        $route['_source_priority'],
        $route['_dedupe_key'],
        $route['_route_leg_type'],
        $route['_route_leg_no'],
        $route['_priority']
    );

    return $route;
}

private function route_has_required_fields($route)
{
    return (
        !empty($route['port of loading']) &&
        !empty($route['time of departure']) &&
        !empty($route['port of discharge']) &&
        !empty($route['time of discharge'])
    );
}

private function find_stepper_time_for_route($steppers, $port, $typeKeyword)
{
    $port = strtoupper(trim((string)$port));
    $typeKeyword = strtolower(trim((string)$typeKeyword));

    if ($port === '') return '';

    foreach ($steppers as $step) {
        if (!is_array($step)) continue;

        $loc = strtoupper(trim((string)($step['location'] ?? '')));
        $type = strtolower(trim((string)($step['location type'] ?? '')));
        $time = trim((string)($step['event time'] ?? ''));

        if ($time === '') continue;

        $samePort =
            stripos($loc, $port) !== false ||
            stripos($port, $loc) !== false;

        if (!$samePort) continue;

        if (strpos($type, $typeKeyword) !== false) {
            return $time;
        }
    }

    return '';
}

private function build_final_routing($items, $steppers)
{
    $routes = [];

    foreach ($items as $route) {
        if (!is_array($route)) continue;

        $route['port of loading'] = $this->normalize_route_port($route['port of loading'] ?? '');
        $route['port of discharge'] = $this->normalize_route_port($route['port of discharge'] ?? '');

        $route['time of departure'] = trim((string)($route['time of departure'] ?? ''));
        $route['time of discharge'] = trim((string)($route['time of discharge'] ?? ''));
        $route['vessel'] = trim((string)($route['vessel'] ?? ''));
        $route['container'] = trim((string)($route['container'] ?? ''));

        // isi ETD dari stepper kalau kosong
        if (empty($route['time of departure']) && !empty($route['port of loading'])) {
            $route['time of departure'] = $this->find_stepper_time_for_route(
                $steppers,
                $route['port of loading'],
                'departure'
            );
        }

        // isi ETA dari stepper kalau kosong
        if (empty($route['time of discharge']) && !empty($route['port of discharge'])) {
            $route['time of discharge'] = $this->find_stepper_time_for_route(
                $steppers,
                $route['port of discharge'],
                'arrival'
            );
        }

        // route wajib punya POL POD ETD ETA
        if (!$this->route_has_required_fields($route)) {
            continue;
        }

        $route['_priority'] = $this->get_route_source_priority($route);

        $key = md5(
            strtoupper($route['port of loading']) . '|' .
            strtoupper($route['port of discharge']) . '|' .
            $route['time of departure'] . '|' .
            $route['time of discharge']
        );

        if (!isset($routes[$key])) {
            $routes[$key] = $route;
            continue;
        }

        // kalau duplicate, local menang dari GoComet
        if (($route['_priority'] ?? 0) > ($routes[$key]['_priority'] ?? 0)) {
            $routes[$key] = $route;
        }
    }

    $routes = array_values($routes);

    usort($routes, function ($a, $b) {
        $ta = $this->normalize_event_timestamp($a['time of departure'] ?? '');
        $tb = $this->normalize_event_timestamp($b['time of departure'] ?? '');

        if ($ta == $tb) return 0;
        return ($ta < $tb) ? -1 : 1;
    });

    return array_map(function ($route) {
        unset($route['_priority']);
        return $this->clean_route_internal_fields($route);
    }, $routes);
}
	
	private function normalize_event_date_key($value)
	{
		if (empty($value)) return '';

		$value = trim((string)$value);
		$value = str_replace(['**', '*', ' (Predicted)'], '', $value);
		$value = trim($value);

		$formats = [
			'd/m/Y H:i:s',
			'd/m/Y H:i',
			'd/m/Y',
			'd-m-Y H:i:s',
			'd-m-Y H:i',
			'd-m-Y',
			'Y-m-d H:i:s',
			'Y-m-d H:i',
			'Y-m-d',
			'Ymd H:i',
			'Ymd',
		];

		foreach ($formats as $fmt) {
			$dt = DateTime::createFromFormat($fmt, $value);
			if ($dt instanceof DateTime) {
				return $dt->format('Y-m-d');
			}
		}

		$ts = strtotime($value);
		if ($ts !== false) {
			return date('Y-m-d', $ts);
		}

		return strtolower($value);
	}
	private function normalize_tracking_event_key($eventName, $locationName)
	{
		$eventName = strtolower(trim((string)$eventName));
		$locationName = strtolower(trim((string)$locationName));

		// Buang tanggal di belakang nama event
		$eventName = preg_replace('/\s+on\s+\d{2}\/\d{2}\/\d{4}$/i', '', $eventName);
		$eventName = preg_replace('/\s+on\s+\d{2}-\d{2}-\d{4}$/i', '', $eventName);

		$text = $eventName . ' ' . $locationName;

		// WAJIB sebelum general arrival
		if (
			strpos($text, 'arrival at transhipment') !== false ||
			strpos($text, 'arrival at transshipment') !== false ||
			strpos($text, 'transhipment arrival') !== false ||
			strpos($text, 'transshipment arrival') !== false ||
			strpos($text, 'trans shipment arrival') !== false ||
			strpos($text, 'arrival transhipment') !== false ||
			strpos($text, 'arrival transshipment') !== false ||
			strpos($text, 'arrival trans shipment') !== false
		) {
			return 'transshipment_arrival';
		}

		if (
			strpos($text, 'departure from transhipment') !== false ||
			strpos($text, 'departure from transshipment') !== false ||
			strpos($text, 'departure from trans shipment') !== false ||
			strpos($text, 'transhipment departure') !== false ||
			strpos($text, 'transshipment departure') !== false ||
			strpos($text, 'trans shipment departure') !== false
		) {
			return 'transshipment_departure';
		}

		if (strpos($text, 'arriv') !== false) {
			return 'arrival';
		}

		if (
			strpos($text, 'origin departure') !== false ||
			strpos($text, 'departed from origin') !== false ||
			strpos($text, 'departure from origin') !== false ||
			strpos($text, 'departure from pol') !== false ||
			strpos($text, 'departur from pol') !== false ||
			strpos($text, 'port of loading') !== false
		) {
			return 'origin_departure';
		}

		if (strpos($text, 'gate in') !== false || strpos($text, 'gated in') !== false) {
			return 'gate_in';
		}

		if (strpos($text, 'gate out') !== false || strpos($text, 'gated out') !== false) {
			return 'gate_out';
		}

		if (strpos($text, 'empty return') !== false || strpos($text, 'empty returned') !== false) {
			return 'empty_return';
		}

		if (strpos($text, 'empty pickup') !== false) {
			return 'empty_pickup';
		}

		return trim($eventName . '|' . $locationName);
	}
	
	private function is_prediction_event_time($value)
	{
		$value = trim((string)$value);
		return (strpos($value, '**') !== false || strpos($value, '*') !== false || stripos($value, 'predicted') !== false);
	}

	private function event_row_score($row)
	{
		$eventName = strtolower(trim((string)($row['event name'] ?? '')));
		$eventTime = trim((string)($row['event time'] ?? ''));
		$sourcePriority = (int)($row['_source_priority'] ?? 0);

		$score = 0;

		// Paling utama: source
		// GoComet = 100, local = 40/50, default = 0
		$score += ($sourcePriority * 1000);
		
		$locName = strtolower(trim((string)($row['location name'] ?? '')));
		$sourceName = strtolower(trim((string)($row['_source_name'] ?? '')));

		if (
			strpos($locName, 'arrival') !== false &&
			in_array($sourceName, ['bintang_import', 'local_import', 'gocomet'], true)
		) {
			$score += 60;
		}

		if (
			strpos($eventName, 'shipment arrived at destination port') !== false &&
			preg_match('/\d{2}:\d{2}/', $eventTime)
		) {
			$score += 30;
		}

		// Nama event yang lebih informatif
		if (strpos($eventName, 'shipment arrived at destination port on') !== false) $score += 50;
		if (strpos($eventName, 'shipment arrived at destination port') !== false) $score += 40;
		if ($eventName === 'arrival') $score += 30;

		if (strpos($eventName, 'shipment departed from origin port on') !== false) $score += 50;
		if (strpos($eventName, 'shipment departed from origin port') !== false) $score += 40;
		if ($eventName === 'origin departure') $score += 30;
		if (strpos($eventName, 'departure from origin port') !== false) $score += 20;

		// Actual lebih baik daripada predicted jika source sama
		if (!$this->is_prediction_event_time($eventTime)) {
			$score += 20;
		}

		// Timestamp detail lebih baik
		if (preg_match('/\d{2}:\d{2}/', $eventTime)) {
			$score += 5;
		}

		return $score;
	}
	
	private function is_valid_lat_lon($lat, $lon)
	{
		if ($lat === null || $lon === null) return false;
		if ($lat === '' || $lon === '') return false;

		if (!is_numeric($lat) || !is_numeric($lon)) return false;

		$lat = (float)$lat;
		$lon = (float)$lon;

		if ($lat < -90 || $lat > 90) return false;
		if ($lon < -180 || $lon > 180) return false;

		// Hindari kasus null yang berubah jadi 0,0
		if ($lat == 0.0 && $lon == 0.0) return false;

		return true;
	}

	private function has_valid_ais_current($ais)
	{
		if (empty($ais) || !is_array($ais)) return false;

		$lat = $ais['lat'] ?? null;
		$lon = $ais['lon'] ?? null;

		return $this->is_valid_lat_lon($lat, $lon);
	}

    function datetime_en($datetime)
    {
        if (!empty($datetime)) {
            return $datetime;
        } else {
            return false;
        }
    }

    private function add_event_no(array $table, string $key = 'no'): array
    {
        $i = 1;
        $out = [];

        foreach ($table as $ev) {
            if (!is_array($ev)) continue;
            $out[] = [$key => $i++] + $ev;
        }

        return $out;
    }

    function month_name($month)
    {
        $m_name = array(
            "01" => "Jan", "02" => "Feb", "03" => "Mar", "04" => "Apr",
            "05" => "May", "06" => "Jun", "07" => "Jul", "08" => "Aug",
            "09" => "Sep", "10" => "Oct", "11" => "Nov", "12" => "Dec"
        );
        return $m_name[$month];
    }

    function date_en($date)
    {
        $date_en = '';
        if (!empty($date)) {
            $tgl = substr($date, 8, 2);
            $bln = substr($date, 5, 2);
            $thn = substr($date, 0, 4);
            $date_en = $this->month_name($bln) . " $tgl" . ", $thn";
        }
        return $date_en;
    }

    function time_en($date)
    {
        $time_en = '';
        if (!empty($date)) {
            list($h, $m, $s) = explode(":", $date);
            $time_en = $h . ":" . $m;
        }
        return $time_en;
    }

    function parseFlexibleDate($dateString)
    {
        $formats = ['d/m/Y', 'Y-m-d', 'd-m-Y', 'Y/m/d'];

        foreach ($formats as $format) {
            $dt = DateTime::createFromFormat($format, $dateString);
            $errors = DateTime::getLastErrors();

            if ($dt !== false) {
                return $dt;
            }
        }

        return null;
    }

    function get_code_by_name($name)
    {
        $this->dbx->select('code');
        $this->dbx->from('tableName');
        $this->dbx->where('name', $name);
        $this->dbx->where('is_active', 'TRUE');
        $query = $this->dbx->get();

        if ($query->num_rows() > 0) {
            return $query->row()->code;
        }
        return null;
    }
	
	function get_point_by_name($name)
	{
		// Cukup select latitude dan longitude saja
		$this->dbx->select('latitude, longitude');
		$this->dbx->from('tableName'); // Pastikan query ke tabel yang sudah ada lat/long nya
		$this->dbx->where('name', $name);
		$this->dbx->where('is_active', 'TRUE');
		$query = $this->dbx->get();

		if ($query->num_rows() > 0) {
			$row = $query->row();
			
			// Pastikan nilainya ada (tidak null) agar tidak mengembalikan string kosong atau koma saja
			if ($row->latitude != '' && $row->longitude != '') {
				// Menggabungkan latitude dan longitude dengan pemisah koma
				return $row->latitude . ', ' . $row->longitude; 
			}
		}
		return null;
	}


    private function clean_vessel_lookup_name($name)
    {
        $name = strtoupper(trim((string)$name));
        $name = preg_replace('/\s+/', ' ', $name);
        return $name;
    }

private function get_vessel_imo_master($vesselName)
{
    $vesselName = $this->clean_vessel_lookup_name($vesselName);
    if ($vesselName === '') {
        return [];
    }

    try {
        // 1) exact match
        $this->dbx->select('imo, mmsi, name');
        $this->dbx->from('vessel_imo');
        $this->dbx->where('UPPER(TRIM(name))', $vesselName);
        $this->dbx->limit(1);
        $q = $this->dbx->get();

        if ($q && $q->num_rows() > 0) {
            $row = $q->row();
            return [
                'imo'  => !empty($row->imo) ? trim((string)$row->imo) : '',
                'mmsi' => !empty($row->mmsi) ? trim((string)$row->mmsi) : '',
                'name' => $row->name ?? $vesselName,
            ];
        }

        // 2) LIKE fallback, misalnya "CMA CGM APOLLON" vs "CMA CGM APOLLON XXX"
        $this->dbx->select('imo, mmsi, name');
        $this->dbx->from('vessel_imo');
        $this->dbx->group_start();
        $this->dbx->like('UPPER(name)', $vesselName);
        $this->dbx->or_where("'" . $this->dbx->escape_str($vesselName) . "' LIKE CONCAT('%', UPPER(name), '%')", null, false);
        $this->dbx->group_end();
        $this->dbx->limit(1);
        $q2 = $this->dbx->get();

        if ($q2 && $q2->num_rows() > 0) {
            $row = $q2->row();
            return [
                'imo'  => !empty($row->imo) ? trim((string)$row->imo) : '',
                'mmsi' => !empty($row->mmsi) ? trim((string)$row->mmsi) : '',
                'name' => $row->name ?? $vesselName,
            ];
        }
    } catch (Exception $ex) {
        log_message('error', 'vessel_imo lookup error: ' . $ex->getMessage());
    }

    return [];
}

    private function is_now_within_etd_eta($etdDt, $etaDt)
    {
        $nowUtc = new DateTime('now', new DateTimeZone('UTC'));

        if ($etdDt instanceof DateTime) {
            $etd = clone $etdDt;
            $etd->setTimezone(new DateTimeZone('UTC'));
        } else {
            $etd = null;
        }

        if ($etaDt instanceof DateTime) {
            $eta = clone $etaDt;
            $eta->setTimezone(new DateTimeZone('UTC'));
        } else {
            $eta = null;
        }

        // Ideal: hanya hit Jarvis current ketika sekarang ada di range ETD - ETA
        if ($etd instanceof DateTime && $eta instanceof DateTime) {
            return ($nowUtc >= $etd && $nowUtc <= $eta);
        }

        // Kalau ETD tidak tersedia, fallback aman: sebelum/sampai ETA saja.
        if ($eta instanceof DateTime) {
            return ($nowUtc <= $eta);
        }

        // Kalau ETA tidak tersedia, jangan hit Jarvis untuk current location.
        return false;
    }

    private function normalize_event_timestamp($value)
    {
        if (empty($value)) {
            return 0;
        }

        $value = trim((string)$value);
        $value = str_replace(['**', '*', ' (Predicted)', '(Predicted)'], '', $value);
        $value = trim($value);

        if ($value === '' || strlen($value) > 100) {
            return 0;
        }

        // Satu parser untuk format lokal dan ISO-8601 Jarvis, termasuk:
        // 2026-10-13T09:56:31Z dan 2026-10-13T09:56:31.919Z.
        $dt = $this->parse_iso_or_flexible_dt($value);

        return ($dt instanceof DateTime) ? $dt->getTimestamp() : 0;
    }



    private function parse_iso_or_flexible_dt($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = trim((string)$value);
        $value = str_replace(['**', '*', ' (Predicted)', '(Predicted)'], '', $value);
        $value = trim($value);

        if ($value === '' || strlen($value) > 100) {
            return null;
        }

        static $utc = null;
        if (!($utc instanceof DateTimeZone)) {
            $utc = new DateTimeZone('UTC');
        }

        /*
         * FAST PATH
         * Jangan loop banyak format untuk setiap AIS point.
         * history-snapshots bisa berisi >20.000 titik dan parser ini
         * dipanggil sangat sering saat filter/sort.
         */

        // ISO-8601 Jarvis:
        // 2026-10-13T09:56:31Z
        // 2026-10-13T09:56:31.919Z
        // 2026-10-13T09:56:31+00:00
        if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}/', $value)) {
            try {
                return new DateTime($value);
            } catch (Exception $e) {
                return null;
            }
        }

        $fmt = null;

        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value)) {
            $fmt = 'Y-m-d H:i:s';
        } elseif (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $value)) {
            $fmt = 'Y-m-d H:i';
        } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            $fmt = 'Y-m-d';
        } elseif (preg_match('/^\d{2}\/\d{2}\/\d{4} \d{2}:\d{2}:\d{2}$/', $value)) {
            $fmt = 'd/m/Y H:i:s';
        } elseif (preg_match('/^\d{2}\/\d{2}\/\d{4} \d{2}:\d{2}$/', $value)) {
            $fmt = 'd/m/Y H:i';
        } elseif (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $value)) {
            $fmt = 'd/m/Y';
        } elseif (preg_match('/^\d{2}-\d{2}-\d{4} \d{2}:\d{2}:\d{2}$/', $value)) {
            $fmt = 'd-m-Y H:i:s';
        } elseif (preg_match('/^\d{2}-\d{2}-\d{4} \d{2}:\d{2}$/', $value)) {
            $fmt = 'd-m-Y H:i';
        } elseif (preg_match('/^\d{2}-\d{2}-\d{4}$/', $value)) {
            $fmt = 'd-m-Y';
        } elseif (preg_match('/^\d{8} \d{2}:\d{2}$/', $value)) {
            $fmt = 'Ymd H:i';
        } elseif (preg_match('/^\d{8}$/', $value)) {
            $fmt = 'Ymd';
        }

        if ($fmt !== null) {
            $dt = DateTime::createFromFormat($fmt, $value);
            if ($dt instanceof DateTime) {
                return $dt;
            }
            return null;
        }

        $ts = @strtotime($value);
        if ($ts === false) {
            return null;
        }

        $dt = new DateTime('@' . $ts);
        $dt->setTimezone($utc);

        return $dt;
    }

    private function extract_tracking_eta($trk)
    {
        $candidates = [
            $trk->stats->predicted_eta ?? '',
            $trk->stats->best_case_eta ?? '',
        ];

        $events = (isset($trk->events) && is_array($trk->events)) ? $trk->events : [];
        foreach ($events as $ev) {
            if (strtolower($ev->event ?? '') === 'arrival') {
                $candidates[] = $ev->actual_datetime ?? '';
                $candidates[] = $ev->planned_datetime ?? '';
                $candidates[] = $ev->original_planned_datetime ?? '';
                $candidates[] = $ev->actual_date ?? '';
                $candidates[] = $ev->planned_date ?? '';
                $candidates[] = $ev->original_planned_date ?? '';
            }
        }

        foreach ($candidates as $val) {
            $dt = $this->parse_iso_or_flexible_dt($val);
            if ($dt instanceof DateTime) {
                return $dt;
            }
        }

        return null;
    }

    private function extract_tracking_etd($trk)
    {
        $events = (isset($trk->events) && is_array($trk->events)) ? $trk->events : [];

        foreach ($events as $ev) {
            if (in_array(strtolower($ev->event ?? ''), ['origin_departure', 'departure', 'load_full'], true)) {
                $candidates = [
                    $ev->actual_datetime ?? '',
                    $ev->planned_datetime ?? '',
                    $ev->original_planned_datetime ?? '',
                    $ev->discharge_or_loading_datetime ?? '',
                    $ev->actual_date ?? '',
                    $ev->planned_date ?? '',
                ];

                foreach ($candidates as $val) {
                    $dt = $this->parse_iso_or_flexible_dt($val);
                    if ($dt instanceof DateTime) {
                        return $dt;
                    }
                }
            }
        }

        return null;
    }

    private function normalize_lat_lon_pair($value)
    {
        if (is_array($value) && count($value) >= 2) {
            return [
                'lat' => $value[0],
                'lon' => $value[1],
            ];
        }

        return [
            'lat' => null,
            'lon' => null,
        ];
    }

    private function get_current_ais_from_jarvis($imoNumber = null, $mmsiNumber = null, $vesselName = null)
    {
        if (!$this->is_jarvis_allowed()) {
            $this->log_jarvis_blocked('get_current_ais_from_jarvis', [
                'imo' => $imoNumber,
                'mmsi' => $mmsiNumber,
                'vessel' => $vesselName,
            ]);

            return [];
        }

        $token = $this->login_jarvis();

        if (empty($token)) {
            return [];
        }

        $mmsiNumber = trim((string)$mmsiNumber);
        $imoNumber  = trim((string)$imoNumber);
        $vesselName = trim((string)$vesselName);

        $attempts = [];

        if ($mmsiNumber !== '') {
            $attempts[] = [
                'label' => 'mmsi',
                'query' => ['mmsi' => $mmsiNumber],
            ];
        }

        if ($imoNumber !== '') {
            $attempts[] = [
                'label' => 'imo',
                'query' => ['imo' => $imoNumber],
            ];
        }

        if ($vesselName !== '') {
            $attempts[] = [
                'label' => 'name',
                'query' => ['name' => $vesselName],
            ];
        }

        if (empty($attempts)) {
            return [];
        }

        try {
            $client = new \GuzzleHttp\Client([
                'timeout'         => 10,
                'connect_timeout' => 5,
                'http_errors'     => false,
            ]);

            $url = 'https://api-gw.jar-vis.com/jarvis/api/v1/navapi/vessels/last-position';

            foreach ($attempts as $attempt) {
                $response = $client->request('GET', $url, [
                    'headers' => [
                        'Accept'         => 'application/json',
                        'Authorization'  => $token,
                        'Jarvis-Api-Key' => 'JARVIS-Z0CXPNc0Xu',
                    ],
                    'query' => $attempt['query'],
                ]);

                $status = $response->getStatusCode();
                $body   = (string)$response->getBody();

                if ($status < 200 || $status >= 300 || empty($body)) {
                    log_message(
                        'error',
                        'Jarvis current AIS bad response [' . $attempt['label'] . ']: HTTP ' . $status . ' | body: ' . $body
                    );
                    continue;
                }

                $json = json_decode($body, true);

                if (json_last_error() !== JSON_ERROR_NONE || empty($json)) {
                    log_message(
                        'error',
                        'Jarvis current AIS invalid JSON [' . $attempt['label'] . ']: ' . json_last_error_msg()
                    );
                    continue;
                }

                return $json;
            }

            return [];
        } catch (\Exception $ex) {
            log_message('error', 'Jarvis current AIS error: ' . $ex->getMessage());
            return [];
        }
    }

    private function get_historical_ais_from_jarvis($imoNumber, $historyFromIso, $historyUntilIso)
    {
        if (!$this->is_jarvis_allowed()) {
            $this->log_jarvis_blocked('get_historical_ais_from_jarvis', [
                'imo' => $imoNumber,
                'from' => $historyFromIso,
                'until' => $historyUntilIso,
            ]);

            return [];
        }

        if (empty($imoNumber) || empty($historyFromIso) || empty($historyUntilIso)) {
            return [];
        }

        $token = $this->login_jarvis();

        if (empty($token)) {
            return [];
        }

        try {
            $client = new \GuzzleHttp\Client([
                'timeout' => 20,
                'connect_timeout' => 5,
                'http_errors' => false,
            ]);

            $response = $client->request(
                'POST',
                'https://api-gw.jar-vis.com/jarvis/api/v1/navapi/vessels/historical-tracks',
                [
                    'headers' => [
                        'Accept'         => 'application/json',
                        'Content-Type'   => 'application/json',
                        'Authorization'  => $token,
                        'Jarvis-Api-Key' => 'JARVIS-Z0CXPNc0Xu',
                    ],
                    'json' => [
                        'imoNumber'    => (string)$imoNumber,
                        'historyFrom'  => $historyFromIso,
                        'historyUntil' => $historyUntilIso,
                    ],
                ]
            );

            $status = $response->getStatusCode();
            $body = (string)$response->getBody();

            if ($status < 200 || $status >= 300 || empty($body)) {
                $error = [
                    '_jarvis_error' => true,
                    'error_type' => 'http_error',
                    'http_status' => (int)$status,
                    'message' => 'Jarvis historical-tracks returned HTTP ' . $status,
                    'body' => substr((string)$body, 0, 1000),
                    'tracks' => [],
                ];

                log_message('error', 'Jarvis historical AIS bad response: ' . json_encode($error, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
                return $error;
            }

            $json = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $error = [
                    '_jarvis_error' => true,
                    'error_type' => 'invalid_json',
                    'http_status' => (int)$status,
                    'message' => json_last_error_msg(),
                    'body' => substr((string)$body, 0, 1000),
                    'tracks' => [],
                ];

                log_message('error', 'Jarvis historical AIS invalid JSON: ' . json_encode($error, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
                return $error;
            }

            return is_array($json) ? $json : [];
        } catch (Exception $ex) {
            $error = [
                '_jarvis_error' => true,
                'error_type' => 'exception',
                'http_status' => null,
                'message' => $ex->getMessage(),
                'tracks' => [],
            ];

            log_message('error', 'Jarvis historical AIS error: ' . json_encode($error, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            return $error;
        }
    }

 private function clean_vessel_name_from_route($value)
	{
		$value = trim((string)$value);

		if ($value === '' || $value === '=' || strtoupper($value) === 'TBA') {
			return '';
		}

		// Buang inline IMO: "INCRES V.073N - IMO:9833371" => "INCRES V.073N"
		$value = preg_replace('/\s*-\s*IMO\s*:\s*\d+/i', '', $value);

		// Buang slash voyage: "OTANA BHUM / 259N" => "OTANA BHUM"
		$value = preg_replace('/\s*\/\s*[A-Z0-9.\- ]+$/i', '', $value);

		// Buang voyage format: "INCRES 073N", "INCRES V.073N", "INCRES v.028W"
		$value = preg_replace('/\s+v\.?\s*[a-z0-9.\-]+$/i', '', $value);
		$value = preg_replace('/\s+[a-z]?\d{2,4}[a-z]?$/i', '', $value);

		$value = preg_replace('/\s+vessel\s*$/i', '', $value);
		$value = preg_replace('/\s+/', ' ', $value);

		return trim($value);
	}

    private function extract_imo_from_route_vessel($value)
    {
        $value = trim((string)$value);

        if (preg_match('/IMO\s*:\s*(\d+)/i', $value, $m)) {
            return $m[1];
        }

        return '';
}

private function normalize_port_key($value)
{
    $value = strtoupper(trim((string)$value));

    $value = preg_replace('/\s*-\s*(ORIGIN DEPARTURE|ARRIVAL|TRANS SHIPMENT ARRIVAL|TRANS SHIPMENT DEPARTURE).*$/i', '', $value);
    $value = preg_replace('/\([^)]*\)/', '', $value);
    $value = preg_replace('/[^A-Z0-9]+/', ' ', $value);
    $value = preg_replace('/\s+/', ' ', $value);

    return trim($value);
}

private function is_same_port_text($a, $b)
{
    $a = $this->normalize_port_key($a);
    $b = $this->normalize_port_key($b);

    if ($a === '' || $b === '') return false;

    return (
        stripos($a, $b) !== false ||
        stripos($b, $a) !== false
    );
}
private function parse_point_to_array($value)
{
    if (empty($value)) return null;

    if (is_array($value) && count($value) >= 2) {
        $lat = $value[0];
        $lon = $value[1];

        return $this->is_valid_lat_lon($lat, $lon)
            ? [(float)$lat, (float)$lon]
            : null;
    }

    if (!is_string($value)) return null;

    $parts = explode(',', $value);
    if (count($parts) < 2) return null;

    $lat = trim($parts[0]);
    $lon = trim($parts[1]);

    return $this->is_valid_lat_lon($lat, $lon)
        ? [(float)$lat, (float)$lon]
        : null;
}

private function normalize_port_match_key($value)
{
    $value = strtoupper(trim((string)$value));
    $value = preg_replace('/\s*-\s*(ORIGIN DEPARTURE|ARRIVAL|TRANS SHIPMENT ARRIVAL|TRANS SHIPMENT DEPARTURE).*$/i', '', $value);
    $value = preg_replace('/\([^)]*\)/', '', $value);
    $value = preg_replace('/[^A-Z0-9]+/', ' ', $value);
    $value = preg_replace('/\s+/', ' ', $value);

    return trim($value);
}

private function route_port_matches_code_or_name($routePort, $code, $extraNames = [])
{
    $p = $this->normalize_port_match_key($routePort);
    $c = $this->normalize_port_match_key($code);

    if ($p === '') {
        return false;
    }

    // 1. Direct match code
    if ($c !== '' && (strpos($p, $c) !== false || strpos($c, $p) !== false)) {
        return true;
    }

    // 2. Match dari names yang dikirim caller
    foreach ((array)$extraNames as $name) {
        $n = $this->normalize_port_match_key($name);

        if ($n !== '' && (strpos($p, $n) !== false || strpos($n, $p) !== false)) {
            return true;
        }
    }

    // 3. Match dari master port table
    if ($c !== '') {
        $masterAliases = $this->get_port_aliases_from_master($c);

        foreach ($masterAliases as $alias) {
            $a = $this->normalize_port_match_key($alias);

            if ($a !== '' && (strpos($p, $a) !== false || strpos($a, $p) !== false)) {
                return true;
            }
        }
    }

    return false;
}

private function is_same_pot_pod_context($transPort, $podCode, $potCode, $podNames = [], $potNames = [])
{
    $transPort = trim((string)$transPort);
    $podCode   = strtoupper(trim((string)$podCode));
    $potCode   = strtoupper(trim((string)$potCode));

    // Case paling jelas: code sama
    if ($podCode !== '' && $potCode !== '' && $podCode === $potCode) {
        return true;
    }

    // Cocokkan text transhipment port ke POD
    if ($transPort !== '' && $podCode !== '') {
        if ($this->route_port_matches_code_or_name($transPort, $podCode, $podNames)) {
            return true;
        }
    }

    // Cocokkan text transhipment port ke POT, lalu POT code sama/alias ke POD name
    if ($transPort !== '' && $potCode !== '') {
        if ($this->route_port_matches_code_or_name($transPort, $potCode, $potNames)) {
            if ($podCode !== '' && $potCode === $podCode) {
                return true;
            }

            foreach ((array)$podNames as $podName) {
                if ($this->is_same_port_text($transPort, $podName)) {
                    return true;
                }
            }
        }
    }

    return false;
}

private function get_leg_point_for_route_port($routePort, $pointContext)
{
    $pointContext = is_array($pointContext) ? $pointContext : [];

    if ($this->route_port_matches_code_or_name($routePort, $pointContext['pol'] ?? '', $pointContext['pol_names'] ?? [])) {
        return $this->parse_point_to_array($pointContext['pol_point'] ?? null);
    }

    if ($this->route_port_matches_code_or_name($routePort, $pointContext['pot'] ?? '', $pointContext['pot_names'] ?? [])) {
        return $this->parse_point_to_array($pointContext['pot_point'] ?? null);
    }

    if ($this->route_port_matches_code_or_name($routePort, $pointContext['pod'] ?? '', $pointContext['pod_names'] ?? [])) {
        return $this->parse_point_to_array($pointContext['pod_point'] ?? null);
    }

    return null;
}

private function build_ais_legs_from_routing($items, $steppers, $pointContext = [])
{
    $legs = [];

    foreach ($items as $idx => $route) {
        if (!is_array($route)) continue;

        $rawVessel = trim((string)($route['vessel'] ?? ''));
        $vesselName = $this->clean_vessel_name_from_route($rawVessel);

        if ($vesselName === '') {
            continue;
        }

        $from = trim((string)($route['port of loading'] ?? ''));
        $to   = trim((string)($route['port of discharge'] ?? ''));

        $etd = trim((string)($route['time of departure'] ?? ''));
        $eta = trim((string)($route['time of discharge'] ?? ''));

        foreach ($steppers as $step) {
            if (!is_array($step)) continue;

            $stepVessel = $this->clean_vessel_name_from_route($step['vessel'] ?? '');
            $stepLoc    = trim((string)($step['location'] ?? ''));
            $stepType   = strtolower(trim((string)($step['location type'] ?? '')));
            $stepTime   = trim((string)($step['event time'] ?? ''));

            if ($stepTime === '') continue;

            $sameVessel = false;

            if ($stepVessel !== '' && $vesselName !== '') {
                $sameVessel =
                    stripos($stepVessel, $vesselName) !== false ||
                    stripos($vesselName, $stepVessel) !== false;
            }

            $sameFrom = $this->is_same_port_text($stepLoc, $from);
            $sameTo   = $this->is_same_port_text($stepLoc, $to);

            if (
                $etd === '' &&
                ($sameVessel || $sameFrom) &&
                (
                    strpos($stepType, 'departure') !== false ||
                    strpos($stepType, 'origin') !== false
                )
            ) {
                $etd = $stepTime;
            }

            if (
                $eta === '' &&
                ($sameVessel || $sameTo) &&
                strpos($stepType, 'arrival') !== false
            ) {
                $eta = $stepTime;
            }
        }

        $startPoint = $this->get_leg_point_for_route_port($from, $pointContext);
        $endPoint   = $this->get_leg_point_for_route_port($to, $pointContext);

        $legs[] = [
            'leg_no' => count($legs) + 1,
            'vessel' => $vesselName,
            'raw_vessel' => $rawVessel,
            'imo' => $this->extract_imo_from_route_vessel($rawVessel),
            'mmsi' => '',
            'from' => $from,
            'to' => $to,
            'etd' => $etd,
            'eta' => $eta,
            'start_point' => $startPoint,
            'end_point' => $endPoint,
            'container' => trim((string)($route['container'] ?? '')),
            'is_active' => false,
            'is_completed' => false,
            'is_future' => false,
            'cargo_current' => null,
            'cargo_current_source' => 'none',
            'ais_current' => [],
            'ais_history' => [],
        ];
    }

    return $legs;
}

private function filter_ais_history_by_range($history, $etdDt, $etaDt)
{
    if (empty($history) || !is_array($history)) {
        return [
            'source' => 'jarvis',
            'tracks' => [],
            'raw' => $history,
        ];
    }

    $tracks = !empty($history['tracks']) && is_array($history['tracks'])
        ? $history['tracks']
        : [];

    if (empty($tracks)) {
        $history['tracks'] = [];
        return $history;
    }

    $fromTs = null;
    $untilTs = null;

    if ($etdDt instanceof DateTime) {
        $d = clone $etdDt;
        $d->setTimezone(new DateTimeZone('UTC'));
        $fromTs = $d->getTimestamp();
    }

    if ($etaDt instanceof DateTime) {
        $d = clone $etaDt;
        $d->setTimezone(new DateTimeZone('UTC'));
        $untilTs = $d->getTimestamp();
    }

    $filtered = [];

    foreach ($tracks as $track) {
        if (!is_array($track)) continue;

        $lat = $track['lat'] ?? null;
        $lon = $track['lon'] ?? null;

        if (!$this->is_valid_lat_lon($lat, $lon)) {
            continue;
        }

        $ts = $this->normalize_event_timestamp($track['timestamp'] ?? '');

        // If a history point has no timestamp, keep it only when no date range is available.
        if ($ts <= 0 && ($fromTs !== null || $untilTs !== null)) {
            continue;
        }

        if ($fromTs !== null && $ts > 0 && $ts < $fromTs) {
            continue;
        }

        if ($untilTs !== null && $ts > 0 && $ts > $untilTs) {
            continue;
        }

        $track['lat'] = (float)$lat;
        $track['lon'] = (float)$lon;
        $track['lat_lon'] = [(float)$lat, (float)$lon];
        $track['_sort_ts'] = $ts;

        $filtered[] = $track;
    }

    usort($filtered, function ($a, $b) {
        return ((int)($a['_sort_ts'] ?? 0)) <=> ((int)($b['_sort_ts'] ?? 0));
    });

    foreach ($filtered as &$track) {
        unset($track['_sort_ts']);
    }
    unset($track);

    $history['tracks'] = array_values($filtered);

    return $history;
}

private function build_cargo_current_from_history_last($history, $leg, $source = 'history_last_point')
{
    if (empty($history['tracks']) || !is_array($history['tracks'])) {
        return null;
    }

    $tracks = array_values($history['tracks']);
    $last = end($tracks);

    if (empty($last) || !$this->is_valid_lat_lon($last['lat'] ?? null, $last['lon'] ?? null)) {
        return null;
    }

    return [
        'source' => $source,
        'current_vessel' => $leg['vessel'] ?? '',
        'speed' => $last['speed'] ?? '',
        'lat' => (float)$last['lat'],
        'lon' => (float)$last['lon'],
        'lat_lon' => [(float)$last['lat'], (float)$last['lon']],
        'last_updated' => $last['timestamp'] ?? '',
        'imo_number' => $leg['imo'] ?? '',
        'mmsi_number' => $leg['mmsi'] ?? '',
        'raw' => $last['raw'] ?? $last,
    ];
}

private function get_snapshot_history_for_leg($mmsi, $imo, $vesselName)
{
    if (!$this->is_jarvis_allowed()) {
        $this->log_jarvis_blocked('get_snapshot_history_for_leg', [
            'mmsi' => $mmsi,
            'imo' => $imo,
            'vessel' => $vesselName,
        ]);

        return [
            'source' => 'jarvis_history_snapshots',
            'tracks' => [],
            'raw' => null,
            'skipped' => true,
        ];
    }

    $attempts = [];

    if (!empty($mmsi)) {
        $attempts[] = [
            'by' => 'mmsi',
            'url' => 'https://api-gw.jar-vis.com/jarvis/api/v1/navapi/vessels/history-snapshots?mmsi=' . rawurlencode($mmsi),
        ];
    }

    if (!empty($imo)) {
        $attempts[] = [
            'by' => 'imo',
            'url' => 'https://api-gw.jar-vis.com/jarvis/api/v1/navapi/vessels/history-snapshots?imo=' . rawurlencode($imo),
        ];
    }

    if (!empty($vesselName)) {
        $attempts[] = [
            'by' => 'name',
            'url' => 'https://api-gw.jar-vis.com/jarvis/api/v1/navapi/vessels/history-snapshots?name=' . rawurlencode($vesselName),
        ];
    }

    foreach ($attempts as $attempt) {
        $raw = $this->fetch_jarvis($attempt['url']);
        $history = $this->normalize_jarvis_history($raw);
        $history['source'] = 'jarvis_history_snapshots';
        $history['snapshot_by'] = $attempt['by'];

        log_message('error', 'AIS LEG SNAPSHOT TRY: vessel=' . $vesselName
            . ' by=' . $attempt['by']
            . ' tracks=' . (isset($history['tracks']) ? count($history['tracks']) : 0)
            . ' raw=' . substr(json_encode($raw), 0, 500)
        );

        if (!empty($history['tracks'])) {
            return $history;
        }
    }

    return [
        'source' => 'jarvis_history_snapshots',
        'tracks' => [],
        'raw' => null,
    ];
}

private function resolve_leg_imo_mmsi(&$leg)
{
    $vesselName = trim((string)($leg['vessel'] ?? ''));
    $imo        = preg_replace('/\D+/', '', (string)($leg['imo'] ?? ''));
    $mmsi       = preg_replace('/\D+/', '', (string)($leg['mmsi'] ?? ''));

    $isValidImo = function ($value) {
        $v = preg_replace('/\D+/', '', (string)$value);
        return strlen($v) === 7 && !in_array($v, ['1110000', '0000000'], true);
    };

    $isValidMmsi = function ($value) {
        $v = preg_replace('/\D+/', '', (string)$value);
        return strlen($v) === 9 && !in_array($v, ['421000000', '000000000'], true);
    };

    if (!$isValidImo($imo)) {
        $imo = '';
    }

    if (!$isValidMmsi($mmsi)) {
        $mmsi = '';
    }

    if (($imo === '' || $mmsi === '') && $vesselName !== '') {
        $master = $this->get_vessel_imo_master($vesselName);

        if (!empty($master)) {
            if ($imo === '' && !empty($master['imo']) && $isValidImo($master['imo'])) {
                $imo = preg_replace('/\D+/', '', (string)$master['imo']);
            }

            if ($mmsi === '' && !empty($master['mmsi']) && $isValidMmsi($master['mmsi'])) {
                $mmsi = preg_replace('/\D+/', '', (string)$master['mmsi']);
            }
        }
    }

    // Use Jarvis current only to resolve IMO/MMSI when master does not have it.
    // This does NOT mean the current position is valid for cargo yet.
    if (($imo === '' || $mmsi === '') && $vesselName !== '') {
        $raw = $this->get_current_ais_from_jarvis($imo, $mmsi, $vesselName);

        $rawImo = $this->find_first_value_recursive($raw, [
            'imo',
            'imoNumber',
            'imo_number',
            'ImoNumber',
            'IMO',
        ]);

        $rawMmsi = $this->find_first_value_recursive($raw, [
            'mmsi',
            'mmsiNumber',
            'MmsiNumber',
            'MMSI',
        ]);

        if ($imo === '' && !empty($rawImo)) {
            $candidateImo = preg_replace('/\D+/', '', (string)$rawImo);

            if ($isValidImo($candidateImo)) {
                $imo = $candidateImo;
            } else {
                log_message('error', 'DEBUG REJECT_JARVIS_IMO=' . json_encode([
                    'vessel' => $vesselName,
                    'raw_imo' => $rawImo,
                ]));
            }
        }

        if ($mmsi === '' && !empty($rawMmsi)) {
            $candidateMmsi = preg_replace('/\D+/', '', (string)$rawMmsi);

            if ($isValidMmsi($candidateMmsi)) {
                $mmsi = $candidateMmsi;
            } else {
                log_message('error', 'DEBUG REJECT_JARVIS_MMSI=' . json_encode([
                    'vessel' => $vesselName,
                    'raw_mmsi' => $rawMmsi,
                ]));
            }
        }
    }

    $leg['imo'] = $imo;
    $leg['mmsi'] = $mmsi;

    return [$imo, $mmsi];
}

private function get_leg_window_status($etdDt, $etaDt)
{
    $now = new DateTime('now', new DateTimeZone('UTC'));

    $etdUtc = null;
    $etaUtc = null;

    if ($etdDt instanceof DateTime) {
        $etdUtc = clone $etdDt;
        $etdUtc->setTimezone(new DateTimeZone('UTC'));
    }

    if ($etaDt instanceof DateTime) {
        $etaUtc = clone $etaDt;
        $etaUtc->setTimezone(new DateTimeZone('UTC'));
    }

    if ($etdUtc instanceof DateTime && $etaUtc instanceof DateTime) {
        if ($now < $etdUtc) return 'future';
        if ($now > $etaUtc) return 'completed';
        return 'active';
    }

    if ($etdUtc instanceof DateTime && $now >= $etdUtc) {
        return 'active';
    }

    if ($etaUtc instanceof DateTime && $now > $etaUtc) {
        return 'completed';
    }

    return 'unknown';
}

private function enrich_ais_legs_from_jarvis($legs)
{
    if (!$this->is_jarvis_allowed()) {
        $this->log_jarvis_blocked('enrich_ais_legs_from_jarvis');
        return is_array($legs) ? $legs : [];
    }

    if (empty($legs) || !is_array($legs)) {
        return [];
    }

    foreach ($legs as &$leg) {
        $vesselName = trim((string)($leg['vessel'] ?? ''));

        if ($vesselName === '') {
            continue;
        }

        list($imo, $mmsi) = $this->resolve_leg_imo_mmsi($leg);

        $etdDt = $this->parse_iso_or_flexible_dt($leg['etd'] ?? '');
        $etaDt = $this->parse_iso_or_flexible_dt($leg['eta'] ?? '');

        $status = $this->get_leg_window_status($etdDt, $etaDt);

        $leg['is_active'] = ($status === 'active');
        $leg['is_completed'] = ($status === 'completed');
        $leg['is_future'] = ($status === 'future');
        $leg['cargo_current'] = null;
        $leg['cargo_current_source'] = 'none';
        $leg['ais_current'] = [];
        $leg['ais_history'] = [
            'source' => 'jarvis',
            'tracks' => [],
        ];

        $fromIso = null;
        $untilIso = null;

        if ($etdDt instanceof DateTime) {
            $fromIso = (clone $etdDt)
                ->setTimezone(new DateTimeZone('UTC'))
                ->format('Y-m-d\TH:i:s\Z');
        }

        $nowUtc = new DateTime('now', new DateTimeZone('UTC'));
        $historyUntilDt = clone $nowUtc;

        if ($etaDt instanceof DateTime) {
            $etaUtcForHistory = clone $etaDt;
            $etaUtcForHistory->setTimezone(new DateTimeZone('UTC'));

            // Historical endpoint hanya diminta sampai waktu yang sudah terjadi.
            if ($etaUtcForHistory < $historyUntilDt) {
                $historyUntilDt = $etaUtcForHistory;
            }
        }

        $untilIso = $historyUntilDt->format('Y-m-d\TH:i:s\Z');

        // Active leg: ambil current AIS, lalu sanity-check dilakukan di bawah.
        if (
            $status === 'active' &&
            (!empty($mmsi) || !empty($imo) || !empty($vesselName))
        ) {
            $rawCurrent = $this->get_current_ais_from_jarvis($imo, $mmsi, $vesselName);
            $normalizedCurrent = $this->normalize_jarvis_current_ais($rawCurrent, $imo, $vesselName);

            if ($this->has_valid_ais_current($normalizedCurrent)) {
                if (empty($normalizedCurrent['mmsi_number']) && !empty($mmsi)) {
                    $normalizedCurrent['mmsi_number'] = $mmsi;
                }
                if (empty($normalizedCurrent['imo_number']) && !empty($imo)) {
                    $normalizedCurrent['imo_number'] = $imo;
                }

                $leg['ais_current'] = $normalizedCurrent;
            }
        }

        log_message('error', 'AIS LEG ENRICH START: leg=' . ($leg['leg_no'] ?? '-')
            . ' status=' . $status
            . ' vessel=' . $vesselName
            . ' imo=' . ($imo ?: '-')
            . ' mmsi=' . ($mmsi ?: '-')
            . ' etd=' . ($leg['etd'] ?? '')
            . ' eta=' . ($leg['eta'] ?? '')
        );

        // Always load history for the leg date range.
        // If live/current is not relevant anymore, this becomes the cargo position source.
        $history = [
            'source' => 'jarvis',
            'tracks' => [],
        ];

        $historicalTracksError = null;

        if (!empty($imo) && !empty($fromIso) && !empty($untilIso)) {
            $rawHistory = $this->get_historical_ais_from_jarvis($imo, $fromIso, $untilIso);

            if (!empty($rawHistory['_jarvis_error'])) {
                $historicalTracksError = $rawHistory;
                $history = [
                    'source' => 'jarvis',
                    'tracks' => [],
                    'raw' => $rawHistory,
                    'historical_tracks_error' => $rawHistory,
                ];
            } else {
                $history = $this->normalize_jarvis_history($rawHistory);
                $history = $this->filter_ais_history_by_range($history, $etdDt, $historyUntilDt);

                // Chunking hanya berguna kalau endpoint merespons normal tetapi dataset kosong.
                // Kalau upstream sudah 5xx, jangan spam endpoint yang sama.
                if (empty($history['tracks'])) {
                    $history = $this->get_historical_ais_from_jarvis_chunked($imo, $fromIso, $untilIso, 3);
                    $history = $this->filter_ais_history_by_range($history, $etdDt, $historyUntilDt);

                    if (!empty($history['historical_tracks_error'])) {
                        $historicalTracksError = $history['historical_tracks_error'];
                    }
                }
            }
        }

        if (empty($history['tracks']) && (!empty($mmsi) || !empty($imo) || !empty($vesselName))) {
            $snapshotHistory = $this->get_snapshot_history_for_leg($mmsi, $imo, $vesselName);
            $snapshotHistory = $this->filter_ais_history_by_range($snapshotHistory, $etdDt, $historyUntilDt);

            if (!empty($snapshotHistory['tracks'])) {
                $history = $snapshotHistory;
            } else {
                // Simpan fakta bahwa fallback snapshot dicoba tetapi tidak punya titik di window voyage.
                $history['snapshot_fallback'] = [
                    'source' => $snapshotHistory['source'] ?? 'jarvis_history_snapshots',
                    'snapshot_by' => $snapshotHistory['snapshot_by'] ?? null,
                    'tracks_in_leg_window' => 0,
                ];
            }
        }

        if (!empty($historicalTracksError)) {
            $history['historical_tracks_error'] = $historicalTracksError;
        }

        $history['imo_number'] = $imo ?: null;
        $history['mmsi_number'] = $mmsi ?: null;

        if (!empty($fromIso)) {
            $history['history_from'] = $fromIso;
        }

        if (!empty($untilIso)) {
            $history['history_until'] = $untilIso;
        }

        $leg['ais_history'] = $history;

        // =====================================================
		// FINAL PER-LEG CARGO POSITION RULE
		// 1. Active leg: pakai Jarvis current hanya kalau sane.
		// 2. Completed leg: pakai last point history.
		// 3. Jika current tidak sane: fallback ke last history.
		// =====================================================
		$etdDt = $this->parse_iso_or_flexible_dt($leg['etd'] ?? '');
		$etaDt = $this->parse_iso_or_flexible_dt($leg['eta'] ?? '');

		$now = new DateTime('now', new DateTimeZone('UTC'));

		$etdUtc = $etdDt instanceof DateTime ? clone $etdDt : null;
		$etaUtc = $etaDt instanceof DateTime ? clone $etaDt : null;

		if ($etdUtc) $etdUtc->setTimezone(new DateTimeZone('UTC'));
		if ($etaUtc) $etaUtc->setTimezone(new DateTimeZone('UTC'));

		$isActive = false;
		$isCompleted = false;

		if ($etdUtc instanceof DateTime && $etaUtc instanceof DateTime) {
			$isActive = ($now >= $etdUtc && $now <= $etaUtc);
			$isCompleted = ($now > $etaUtc);
		} elseif ($etdUtc instanceof DateTime) {
			$isActive = ($now >= $etdUtc);
		}

		// Filter history supaya snapshot voyage lama tidak ikut digambar.
		if (!empty($leg['ais_history']) && is_array($leg['ais_history'])) {
			$leg['ais_history'] = $this->filter_ais_history_by_leg_range(
				$leg['ais_history'],
				$etdUtc,
				$etaUtc
			);
		}

		$leg['is_active'] = $isActive;
		$leg['is_completed'] = $isCompleted;
		$leg['cargo_current'] = null;
		$leg['cargo_current_source'] = 'none';
		$leg['cargo_current_reject_reason'] = '';

		if ($isActive && !empty($leg['ais_current']) && is_array($leg['ais_current'])) {
			[$okCurrent, $reason] = $this->is_current_sane_for_leg(
				$leg['ais_current'],
				$leg,
				$etdUtc,
				$etaUtc
			);

			if ($okCurrent) {
				$leg['cargo_current'] = [
					'source' => 'jarvis_current',
					'lat' => (float)$leg['ais_current']['lat'],
					'lon' => (float)$leg['ais_current']['lon'],
					'lat_lon' => [
						(float)$leg['ais_current']['lat'],
						(float)$leg['ais_current']['lon'],
					],
					'timestamp' => $leg['ais_current']['last_updated'] ?? '',
					'speed' => $leg['ais_current']['speed'] ?? '',
					'current_vessel' => $leg['ais_current']['current_vessel'] ?? $vesselName,
				];
				$leg['cargo_current_source'] = 'jarvis_current';
			} else {
				$leg['ais_current'] = [];
				$leg['cargo_current_reject_reason'] = $reason;
			}
		}

        // Active cargo: compare timestamps, not endpoint priority.
        // ais_current remains the raw provider position for diagnostic purposes.
        if ($isActive) {
            $leg['cargo_current'] = $this->select_latest_active_cargo_position(
                $leg, $etdUtc, $etaUtc, $now->getTimestamp()
            );
            $leg['cargo_current_source'] = $leg['cargo_current']['source'] ?? 'none';
        }

		// Preserve the existing completed-leg position policy.
        if (
			$isCompleted &&
			!empty($leg['end_point']) &&
			is_array($leg['end_point']) &&
			count($leg['end_point']) >= 2 &&
			$this->is_valid_lat_lon($leg['end_point'][0], $leg['end_point'][1])
		) {
			$leg['cargo_current'] = [
				'source' => 'leg_end_point',
				'current_vessel' => $vesselName,
				'lat' => (float)$leg['end_point'][0],
				'lon' => (float)$leg['end_point'][1],
				'lat_lon' => [
					(float)$leg['end_point'][0],
					(float)$leg['end_point'][1],
				],
				'timestamp' => $leg['eta'] ?? '',
				'speed' => 0,
				'course' => null,
				'heading' => '',
				'imo_number' => $leg['imo'] ?? '',
				'mmsi_number' => $leg['mmsi'] ?? '',
			];

			$leg['cargo_current_source'] = 'leg_end_point';
		}
		
        if (!$isActive && empty($leg['cargo_current'])) {
            $lastHistoryCurrent = $this->cargo_current_from_last_history($leg['ais_history'] ?? []);

            if (!empty($lastHistoryCurrent)) {
                $leg['cargo_current'] = $lastHistoryCurrent;
                $leg['cargo_current_source'] = 'history_last_point';
            }
        }

		// Kalau masih tidak ada cargo_current, pastikan current tidak dikirim sembarangan.
		if (empty($leg['cargo_current'])) {
			$leg['ais_current'] = [];

            if (
                $isActive &&
                empty($leg['cargo_current_reject_reason']) &&
                empty($leg['ais_history']['tracks'])
            ) {
                if (!empty($leg['ais_history']['historical_tracks_error']['http_status'])) {
                    $leg['cargo_current_reject_reason'] =
                        'jarvis_historical_http_' . (int)$leg['ais_history']['historical_tracks_error']['http_status'];
                } else {
                    $leg['cargo_current_reject_reason'] = 'jarvis_data_not_fresh';
                }
            }
		}
			}

			unset($leg);

			return $legs;
		}

private function pick_active_ais_leg($legs)
{
    if (empty($legs) || !is_array($legs)) {
        return null;
    }

    // 1. Prefer the leg that is currently within ETD-ETA and has a cargo point.
    foreach ($legs as $leg) {
        if (!empty($leg['is_active']) && !empty($leg['cargo_current'])) {
            return $leg;
        }
    }

    // 2. Then prefer active leg even if current is not available yet.
    foreach ($legs as $leg) {
        if (!empty($leg['is_active'])) {
            return $leg;
        }
    }

    // 3. If no active leg, use the latest completed leg with cargo point.
    for ($i = count($legs) - 1; $i >= 0; $i--) {
        if (!empty($legs[$i]['is_completed']) && !empty($legs[$i]['cargo_current'])) {
            return $legs[$i];
        }
    }

    // 4. Then latest leg that has cargo point.
    for ($i = count($legs) - 1; $i >= 0; $i--) {
        if (!empty($legs[$i]['cargo_current'])) {
            return $legs[$i];
        }
    }

    // 5. Otherwise return first future/unknown leg for route fallback.
    return $legs[0];
}


    private function normalize_gocomet_ais($aisData, $imoNumber = null)
    {
        if (empty($aisData)) return [];

        $coords = $this->normalize_lat_lon_pair($aisData->lat_lon ?? []);

        return [
            'source' => 'gocomet',
            'current_vessel' => $aisData->current_vessel ?? '',
            'speed' => $aisData->speed ?? '',
            'lat' => $coords['lat'],
            'lon' => $coords['lon'],
            'lat_lon' => [$coords['lat'], $coords['lon']],
            'last_updated' => $aisData->last_updated ?? '',
            'imo_number' => $aisData->imo_number ?? $imoNumber,
        ];
    }

    private function find_first_value_recursive($data, array $keys)
    {
        if (is_array($data)) {
            foreach ($keys as $key) {
                if (array_key_exists($key, $data) && $data[$key] !== '' && $data[$key] !== null) {
                    return $data[$key];
                }
            }

            foreach ($data as $value) {
                $found = $this->find_first_value_recursive($value, $keys);
                if ($found !== null && $found !== '') return $found;
            }
        }

        return null;
    }

private function normalize_jarvis_current_ais($jarvis, $fallbackImo = null, $fallbackVessel = '')
{
    if (empty($jarvis) || !is_array($jarvis)) return [];

    $lat = $this->find_first_value_recursive($jarvis, [
        'latitude',
        'Latitude',
        'lat',
        'Lat',
    ]);

    $lon = $this->find_first_value_recursive($jarvis, [
        'longitude',
        'Longitude',
        'lon',
        'lng',
        'Lon',
        'Lng',
    ]);

    $speed = $this->find_first_value_recursive($jarvis, [
        'speed',
        'speedKnots',
        'speed_knots',
        'SpeedOverGround',
    ]);

    $vessel = $this->find_first_value_recursive($jarvis, [
        'vesselName',
        'current_vessel',
        'name',
        'ShipName',
    ]);

    $lastUpdated = $this->find_first_value_recursive($jarvis, [
        'lastUpdated',
        'last_updated',
        'updatedAt',
        'timestamp',
        'PositionLastUpdated',
        'DbLastEdit',
        'LastEventChange',
        'lastPositionTime',
        'LastPositionTime',
    ]);

    $imo = $this->find_first_value_recursive($jarvis, [
        'imoNumber',
        'imo_number',
        'imo',
        'ImoNumber',
    ]);

    $mmsi = $this->find_first_value_recursive($jarvis, [
        'mmsi',
        'MmsiNumber',
        'mmsiNumber',
        'MmsiNumber',
    ]);

    return [
        'source' => 'jarvis',
        'current_vessel' => $vessel ?: $fallbackVessel,
        'speed' => $speed ?: '',
        'lat' => $lat,
        'lon' => $lon,
        'lat_lon' => [$lat, $lon],
        'last_updated' => $lastUpdated ?: '',
        'imo_number' => $imo ?: $fallbackImo,
        'mmsi_number' => $mmsi ?: '',
        'raw' => $jarvis,
    ];
}
	
	private function flatten_jarvis_api_results($data)
{
    $out = [];

    if (!is_array($data)) {
        return $out;
    }

    foreach ($data as $value) {
        if (!is_array($value)) {
            continue;
        }

        if (
            isset($value['Latitude']) ||
            isset($value['latitude']) ||
            isset($value['Longitude']) ||
            isset($value['longitude'])
        ) {
            $out[] = $value;
            continue;
        }

        foreach ($value as $child) {
            if (!is_array($child)) continue;

            if (
                isset($child['Latitude']) ||
                isset($child['latitude']) ||
                isset($child['Longitude']) ||
                isset($child['longitude'])
            ) {
                $out[] = $child;
            } elseif (is_array($child)) {
                $out = array_merge($out, $this->flatten_jarvis_api_results($child));
            }
        }
    }

    return $out;
}

private function normalize_jarvis_history($jarvisHistory)
{
    if (empty($jarvisHistory) || !is_array($jarvisHistory)) {
        return [
            'source' => 'jarvis',
            'tracks' => [],
            'raw' => $jarvisHistory,
        ];
    }

    $trackCandidates = [];

    if (isset($jarvisHistory['tracks']) && is_array($jarvisHistory['tracks'])) {
        $trackCandidates = $jarvisHistory['tracks'];
    } elseif (isset($jarvisHistory['data']['points']) && is_array($jarvisHistory['data']['points'])) {
        $trackCandidates = $jarvisHistory['data']['points'];
    } elseif (isset($jarvisHistory['data']['tracks']) && is_array($jarvisHistory['data']['tracks'])) {
        $trackCandidates = $jarvisHistory['data']['tracks'];
    } elseif (isset($jarvisHistory['data']['raw']['ApiResults']) && is_array($jarvisHistory['data']['raw']['ApiResults'])) {
        $trackCandidates = $this->flatten_jarvis_api_results($jarvisHistory['data']['raw']['ApiResults']);
    } elseif (isset($jarvisHistory['raw']['ApiResults']) && is_array($jarvisHistory['raw']['ApiResults'])) {
        $trackCandidates = $this->flatten_jarvis_api_results($jarvisHistory['raw']['ApiResults']);
    } elseif (isset($jarvisHistory['data']) && is_array($jarvisHistory['data'])) {
        $trackCandidates = $this->flatten_jarvis_api_results($jarvisHistory['data']);
    } elseif (isset($jarvisHistory[0]) && is_array($jarvisHistory)) {
        $trackCandidates = $jarvisHistory;
    } else {
        $nested = $this->find_first_value_recursive($jarvisHistory, [
            'points',
            'tracks',
            'positions',
            'historicalTracks',
            'svsl_HistorySnapshots',
            'svsl_HistoricalTracks',
        ]);

        if (is_array($nested)) {
            $trackCandidates = $nested;
        }
    }

    $tracks = [];

    foreach ($trackCandidates as $row) {
        if (!is_array($row)) continue;

        $lat = $this->find_first_value_recursive($row, [
            'latitude',
            'Latitude',
            'lat',
            'Lat',
        ]);

        $lon = $this->find_first_value_recursive($row, [
            'longitude',
            'Longitude',
            'lon',
            'lng',
            'Lon',
            'Lng',
        ]);

        if (!$this->is_valid_lat_lon($lat, $lon)) {
            continue;
        }

        $speed = $this->find_first_value_recursive($row, [
            'speed',
            'speedKnots',
            'speed_knots',
            'SpeedOverGround',
        ]);

        $course = $this->find_first_value_recursive($row, [
            'course',
            'courseDegrees',
            'courseOverGround',
            'CourseOverGround',
            'cog',
        ]);

        $heading = $this->find_first_value_recursive($row, [
            'heading',
            'headingDegrees',
            'CourseTransmitted',
            'trueHeading',
        ]);

        $timestamp = $this->find_first_value_recursive($row, [
            'timestamp',
            'Timestamp',
            'ArraysTimestamp',
            'PositionLastUpdated',
            'positionTime',
            'lastUpdated',
            'last_updated',
            'time',
        ]);

        $tracks[] = [
            'lat' => (float)$lat,
            'lon' => (float)$lon,
            'lat_lon' => [(float)$lat, (float)$lon],
            'speed' => $speed,
            'course' => $course,
            'heading' => $heading,
            'timestamp' => $timestamp,
            '_sort_ts' => $this->normalize_event_timestamp($timestamp),
            'raw' => $row,
        ];
    }

    // Jangan parse tanggal di comparator usort().
    usort($tracks, function ($a, $b) {
        return ((int)($a['_sort_ts'] ?? 0)) <=> ((int)($b['_sort_ts'] ?? 0));
    });

    foreach ($tracks as &$track) {
        unset($track['_sort_ts']);
    }
    unset($track);

    return [
        'source' => 'jarvis',
        'tracks' => $tracks,
        'raw' => $jarvisHistory,
    ];
}

private function distance_km_between_points($a, $b)
{
    if (!is_array($a) || !is_array($b) || count($a) < 2 || count($b) < 2) {
        return PHP_INT_MAX;
    }

    $lat1 = (float)$a[0];
    $lon1 = (float)$a[1];
    $lat2 = (float)$b[0];
    $lon2 = (float)$b[1];

    if (!$this->is_valid_lat_lon($lat1, $lon1) || !$this->is_valid_lat_lon($lat2, $lon2)) {
        return PHP_INT_MAX;
    }

    $earth = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);

    $h = sin($dLat / 2) * sin($dLat / 2) +
        cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
        sin($dLon / 2) * sin($dLon / 2);

    return 2 * $earth * atan2(sqrt($h), sqrt(1 - $h));
}

private function point_from_lat_lon_array($value)
{
    if (!is_array($value) || count($value) < 2) return null;

    $lat = $value[0];
    $lon = $value[1];

    if (!$this->is_valid_lat_lon($lat, $lon)) return null;

    return [(float)$lat, (float)$lon];
}

private function ais_track_timestamp($track)
{
    if (!is_array($track)) return 0;

    $value = $track['timestamp']
        ?? $track['last_updated']
        ?? $track['PositionLastUpdated']
        ?? $track['ArraysTimestamp']
        ?? '';

    return $this->normalize_event_timestamp($value);
}

private function filter_ais_history_by_leg_range($history, $etdDt, $etaDt)
{
    if (empty($history) || !is_array($history)) {
        return [
            'source' => 'jarvis',
            'tracks' => [],
            'raw' => $history,
        ];
    }

    $tracks = $history['tracks'] ?? [];
    if (empty($tracks) || !is_array($tracks)) {
        $history['tracks'] = [];
        return $history;
    }

    $fromTs = $etdDt instanceof DateTime ? $etdDt->getTimestamp() : 0;
    $untilTs = $etaDt instanceof DateTime ? $etaDt->getTimestamp() : 0;

    $filtered = [];

    foreach ($tracks as $track) {
        if (!is_array($track)) continue;

        $lat = $track['lat'] ?? null;
        $lon = $track['lon'] ?? null;

        if (!$this->is_valid_lat_lon($lat, $lon)) continue;

        $ts = $this->ais_track_timestamp($track);

        // Kalau track punya timestamp, wajib berada di range ETD-ETA leg.
        if ($ts > 0) {
            if ($fromTs > 0 && $ts < ($fromTs - 6 * 3600)) continue;
            if ($untilTs > 0 && $ts > ($untilTs + 6 * 3600)) continue;
        }

        $track['_sort_ts'] = $ts;
        $filtered[] = $track;
    }

    usort($filtered, function ($a, $b) {
        return ((int)($a['_sort_ts'] ?? 0)) <=> ((int)($b['_sort_ts'] ?? 0));
    });

    foreach ($filtered as &$track) {
        unset($track['_sort_ts']);
    }
    unset($track);

    $history['tracks'] = array_values($filtered);

    return $history;
}

/**
 * Select the newest valid observation for this active vessel leg.
 * Input cargo_current has already passed the existing sanity check.
 * History is already filtered by leg; enforce timestamp bounds again here.
 */
private function select_latest_active_cargo_position($leg, $etdDt, $etaDt, $nowTs)
{
    $fromTs = $etdDt instanceof DateTime ? $etdDt->getTimestamp() : 0;
    $untilTs = $etaDt instanceof DateTime ? $etaDt->getTimestamp() : $nowTs;
    $untilTs = min($untilTs, $nowTs);
    $best = null;
    $bestTs = 0;
    $current = $leg['cargo_current'] ?? null;

    if ($this->has_valid_ais_current($current)) {
        $ts = $this->ais_track_timestamp($current);
        if ($ts > 0 && $ts >= $fromTs && $ts <= $untilTs) {
            $best = $current;
            $bestTs = $ts;
        }
    }

    $tracks = $leg['ais_history']['tracks'] ?? [];
    if (is_array($tracks)) {
        foreach ($tracks as $track) {
            if (!is_array($track) || !$this->has_valid_ais_current($track)) continue;
            $ts = $this->ais_track_timestamp($track);
            if ($ts <= $bestTs || $ts < $fromTs || $ts > $untilTs) continue;

            $best = $this->cargo_current_from_last_history(['tracks' => [$track]]);
            // Preserve timestamp aliases recognized by ais_track_timestamp().
            $best['timestamp'] = gmdate('Y-m-d\TH:i:s\Z', $ts);
            $bestTs = $ts;
        }
    }

    if ($best !== null) {
        $best['current_vessel'] = $best['current_vessel'] ?? $leg['vessel'] ?? '';
        $best['imo_number'] = $best['imo_number'] ?? $leg['imo'] ?? '';
        $best['mmsi_number'] = $best['mmsi_number'] ?? $leg['mmsi'] ?? '';
    }

    return $best;
}

private function cargo_current_from_last_history($history)
{
    if (empty($history['tracks']) || !is_array($history['tracks'])) {
        return null;
    }

    $tracks = array_values($history['tracks']);
    $last = end($tracks);

    if (empty($last) || !is_array($last)) return null;

    $lat = $last['lat'] ?? null;
    $lon = $last['lon'] ?? null;

    if (!$this->is_valid_lat_lon($lat, $lon)) return null;

    return [
        'source' => 'history_last_point',
        'lat' => (float)$lat,
        'lon' => (float)$lon,
        'lat_lon' => [(float)$lat, (float)$lon],
        'timestamp' => $last['timestamp'] ?? '',
        'speed' => $last['speed'] ?? '',
        'course' => $last['course'] ?? '',
        'heading' => $last['heading'] ?? '',
    ];
}

private function is_current_sane_for_leg($current, $leg, $etdDt, $etaDt)
{
    if (!$this->has_valid_ais_current($current)) {
        return [false, 'invalid_lat_lon'];
    }

    $startPoint = $this->point_from_lat_lon_array($leg['start_point'] ?? null);
    $endPoint   = $this->point_from_lat_lon_array($leg['end_point'] ?? null);

    $currentPoint = [
        (float)$current['lat'],
        (float)$current['lon'],
    ];

    $now = new DateTime('now', new DateTimeZone('UTC'));
    $nowTs = $now->getTimestamp();

    $etdTs = $etdDt instanceof DateTime ? $etdDt->getTimestamp() : 0;
    $etaTs = $etaDt instanceof DateTime ? $etaDt->getTimestamp() : 0;

    $currentTs = $this->normalize_event_timestamp($current['last_updated'] ?? '');

    // Current AIS harus berada di window leg, atau minimal tidak terlalu tua.
    if ($currentTs > 0) {
        if ($etdTs > 0 && $currentTs < ($etdTs - 12 * 3600)) {
            return [false, 'current_before_leg_etd'];
        }

        if ($etaTs > 0 && $currentTs > ($etaTs + 12 * 3600)) {
            return [false, 'current_after_leg_eta'];
        }

        if (($nowTs - $currentTs) > (72 * 3600)) {
            return [false, 'current_too_old'];
        }
    }

    if ($startPoint && $endPoint && $etdTs > 0 && $etaTs > $etdTs) {
        $totalDistance = $this->distance_km_between_points($startPoint, $endPoint);
        $startToCurrent = $this->distance_km_between_points($startPoint, $currentPoint);
        $currentToEnd = $this->distance_km_between_points($currentPoint, $endPoint);

        if ($totalDistance > 50 && $totalDistance < PHP_INT_MAX) {
            $timeProgress = max(0, min(1, ($nowTs - $etdTs) / ($etaTs - $etdTs)));
            $distanceProgress = max(0, min(2, $startToCurrent / $totalDistance));

            // Contoh bug: baru departed Jakarta, tapi current sudah dekat Shanghai.
            // Kalau progress jarak terlalu jauh dibanding progress waktu, reject.
            if ($distanceProgress > ($timeProgress + 0.35)) {
                return [false, 'current_too_far_for_elapsed_time'];
            }

            // Kalau current sudah dekat destination jauh sebelum ETA, reject.
            if ($currentToEnd <= 120 && $etaTs > 0 && $nowTs < ($etaTs - 12 * 3600)) {
                return [false, 'current_near_destination_too_early'];
            }
        }
    }

    return [true, 'ok'];
}

/**
 * Mengubah hasil model/query menjadi satu row object secara aman.
 */
private function safe_row_object($result)
{
    if ($result === null || $result === false || $result === '') {
        return null;
    }

    if (is_object($result)) {
        if (method_exists($result, 'row')) {
            return $result->row();
        }

        // Sudah berupa stdClass / object row.
        return $result;
    }

    if (is_array($result)) {
        if (empty($result)) {
            return null;
        }

        // Array associative satu row.
        if (array_keys($result) !== range(0, count($result) - 1)) {
            return (object) $result;
        }

        // Array list.
        return isset($result[0])
            ? (is_object($result[0]) ? $result[0] : (object) $result[0])
            : null;
    }

    return null;
}

/**
 * Mengubah hasil model/query menjadi satu row array secara aman.
 */
private function safe_row_array($result)
{
    if ($result === null || $result === false || $result === '') {
        return [];
    }

    if (is_object($result)) {
        if (method_exists($result, 'row_array')) {
            $row = $result->row_array();
            return is_array($row) ? $row : [];
        }

        return get_object_vars($result);
    }

    if (is_array($result)) {
        if (empty($result)) {
            return [];
        }

        // Sudah array associative.
        if (array_keys($result) !== range(0, count($result) - 1)) {
            return $result;
        }

        // Array list.
        $first = $result[0] ?? [];

        if (is_object($first)) {
            return get_object_vars($first);
        }

        return is_array($first) ? $first : [];
    }

    // String bukan dianggap data valid.
    return [];
}

/**
 * Memeriksa apakah hasil tracking mempunyai data.
 */
private function tracking_result_has_data($result)
{
    if ($result === null || $result === false || $result === '') {
        return false;
    }

    if (is_array($result)) {
        return count($result) > 0;
    }

    if (is_object($result)) {
        if (method_exists($result, 'num_rows')) {
            return (int) $result->num_rows() > 0;
        }

        if (method_exists($result, 'result_array')) {
            return count($result->result_array()) > 0;
        }

        return count(get_object_vars($result)) > 0;
    }

    return false;
}


    // =====================================================
    // AIR FREIGHT / MAWB TRACKING
    // =====================================================
    private function normalize_awb_digits($value)
    {
        return preg_replace('/\D+/', '', (string)$value);
    }

    private function canonical_awb($value)
    {
        $digits = $this->normalize_awb_digits($value);
        if (strlen($digits) !== 11) {
            return null;
        }

        return substr($digits, 0, 3) . '-' . substr($digits, 3);
    }

    private function find_air_shipment_by_awb($input)
    {
        $digits = $this->normalize_awb_digits($input);

        // MAWB normal: 3-digit airline prefix + 8-digit serial.
        if (strlen($digits) !== 11) {
            return null;
        }

        $escaped = $this->dbx->escape($digits);

        $q = $this->dbx
            ->select('sp_id, kode_ref, mother_bl, type_of_trans, air_line, vessel, carrier, port_loading, transhipment_port, destination, etd_jkt, atd, eta, ata, region_id')
            ->from('trnstuffingplan')
            ->where("UPPER(COALESCE(type_of_trans, '')) LIKE '%AIR%'", null, false)
            ->where("REGEXP_REPLACE(COALESCE(mother_bl, ''), '[^0-9]', '') = {$escaped}", null, false)
            ->order_by('sp_id', 'DESC')
            ->limit(1)
            ->get();

        if (!$q || $q->num_rows() < 1) {
            return null;
        }

        return $q->row_array();
    }

    private function rapidapi_air_key()
    {
        $key = '';

        if (function_exists('apache_getenv')) {
            $key = (string)apache_getenv('RAPIDAPI_AIR_CARGO_KEY');
        }

        if ($key === '') {
            $key = (string)getenv('RAPIDAPI_AIR_CARGO_KEY');
        }

        return trim($key);
    }

    /**
     * RapidAPI returns coordinates as "longitude,latitude".
     * FE/Leaflet in this project expects [latitude, longitude].
     */
    private function air_coord_to_point($raw)
    {
        if (empty($raw) || !is_string($raw)) {
            return null;
        }

        $parts = array_map('trim', explode(',', $raw));
        if (count($parts) < 2 || !is_numeric($parts[0]) || !is_numeric($parts[1])) {
            return null;
        }

        $lon = (float)$parts[0];
        $lat = (float)$parts[1];

        if ($lat < -90 || $lat > 90 || $lon < -180 || $lon > 180) {
            return null;
        }

        return [$lat, $lon];
    }

    private function air_point_string($point)
    {
        if (!is_array($point) || count($point) < 2) {
            return null;
        }

        return $point[0] . ',' . $point[1];
    }

    private function air_event_label($code)
    {
        $labels = [
            'BKD' => 'Shipment Booked',
            'RCS' => 'Cargo Received from Shipper',
            'MAN' => 'Cargo Manifested',
            'DEP' => 'Flight Departed',
            'ARR' => 'Flight Arrived',
            'RCF' => 'Cargo Received from Flight',
            'NFD' => 'Cargo Ready / Delivery Notification',
            'DLV' => 'Cargo Delivered',
        ];

        $code = strtoupper(trim((string)$code));
        return $labels[$code] ?? ($code !== '' ? $code : 'Air Cargo Update');
    }

    private function air_get_airport($iata)
    {
        static $cache = [];

        $iata = strtoupper(trim((string)$iata));
        if ($iata === '') {
            return null;
        }

        if (array_key_exists($iata, $cache)) {
            return $cache[$iata];
        }

        if (!method_exists($this->dbx, 'table_exists') || !$this->dbx->table_exists('ref_airport')) {
            $cache[$iata] = null;
            return null;
        }

        $escaped = $this->dbx->escape($iata);
        $q = $this->dbx
            ->select('id, ident, type, name, municipality, iso_country, gps_code, iata_code, local_code, coordinates')
            ->from('ref_airport')
            ->where("UPPER(TRIM(COALESCE(iata_code, ''))) = {$escaped}", null, false)
            ->limit(1)
            ->get();

        if (!$q || $q->num_rows() < 1) {
            $cache[$iata] = null;
            return null;
        }

        $row = $q->row_array();
        $row['iata_code'] = strtoupper(trim((string)($row['iata_code'] ?? $iata)));
        $row['point'] = $this->air_coord_to_point($row['coordinates'] ?? null);

        $cache[$iata] = $row;
        return $row;
    }

    private function air_resolve_point($iata, $apiCoord = null)
    {
        $point = $this->air_coord_to_point($apiCoord);
        if ($point) {
            return $point;
        }

        $airport = $this->air_get_airport($iata);
        return is_array($airport) ? ($airport['point'] ?? null) : null;
    }

    private function air_tracking_cache_get($awb)
    {
        if (!method_exists($this->dbx, 'table_exists') || !$this->dbx->table_exists('air_tracking_cache')) {
            return null;
        }

        $digits = $this->normalize_awb_digits($awb);
        if ($digits === '') {
            return null;
        }

        $q = $this->dbx
            ->from('air_tracking_cache')
            ->where('awb_digits', $digits)
            ->where('is_delivered', 1)
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get();

        if (!$q || $q->num_rows() < 1) {
            return null;
        }

        $row = $q->row_array();
        if (empty($row['api_response_json'])) {
            return null;
        }

        $decoded = json_decode($row['api_response_json'], true);
        if (json_last_error() !== JSON_ERROR_NONE || empty($decoded)) {
            log_message('error', 'AIR_TRACKING_CACHE_INVALID_JSON awb=' . $awb . ' cache_id=' . ($row['id'] ?? ''));
            return null;
        }

        $row['_decoded'] = $decoded;
        return $row;
    }

    private function air_tracking_cache_save($awb, array $shipment, array $apiResponse, ?array $deliveredEvent = null, $status = 'DELIVERED')
    {
        if (!method_exists($this->dbx, 'table_exists') || !$this->dbx->table_exists('air_tracking_cache')) {
            log_message('error', 'AIR_TRACKING_CACHE_TABLE_MISSING table=air_tracking_cache');
            return false;
        }

        $digits = $this->normalize_awb_digits($awb);
        if ($digits === '') {
            return false;
        }

        $deliveredRaw = trim((string)($deliveredEvent['eventDate'] ?? ''));
        $deliveredUtc = null;

        if ($deliveredRaw !== '') {
            try {
                $dt = new DateTime($deliveredRaw);
                $dt->setTimezone(new DateTimeZone('UTC'));
                $deliveredUtc = $dt->format('Y-m-d H:i:s');
            } catch (Exception $ex) {
                $deliveredUtc = null;
            }
        }

        $payload = [
            'awb' => $awb,
            'awb_digits' => $digits,
            'sp_id' => !empty($shipment['sp_id']) ? (int)$shipment['sp_id'] : null,
            'kode_ref' => $shipment['kode_ref'] ?? null,
            'region_id' => $shipment['region_id'] ?? null,
            'status' => strtoupper(trim((string)$status)),
            'last_event_code' => strtoupper(trim((string)($deliveredEvent['code'] ?? 'DLV'))),
            'delivered_event_time' => $deliveredRaw !== '' ? $deliveredRaw : null,
            'delivered_at_utc' => $deliveredUtc,
            'is_delivered' => 1,
            'api_response_json' => json_encode($apiResponse, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'fetched_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $existing = $this->dbx
            ->select('id')
            ->from('air_tracking_cache')
            ->where('awb_digits', $digits)
            ->limit(1)
            ->get();

        if ($existing && $existing->num_rows() > 0) {
            $id = (int)$existing->row()->id;
            $this->dbx->where('id', $id)->update('air_tracking_cache', $payload);
            return true;
        }

        $payload['created_at'] = date('Y-m-d H:i:s');
        return (bool)$this->dbx->insert('air_tracking_cache', $payload);
    }

    private function air_tracking_response($input, array $shipment)
    {
        $awb = $this->canonical_awb($shipment['mother_bl'] ?? $input);
        if (!$awb) {
            return $this->response([
                'status' => false,
                'found' => false,
                'code' => 'INVALID_AWB',
                'message' => 'Invalid MAWB format.',
            ], 400);
        }

        // Delivered AWB is immutable for public tracking purposes. If it is already
        // cached, NEVER consume RapidAPI quota again.
        $cacheRow = $this->air_tracking_cache_get($awb);
        $cacheHit = false;
        $decoded = null;

        if (!empty($cacheRow['_decoded'])) {
            $decoded = $cacheRow['_decoded'];
            $cacheHit = true;
        }

        if (!$cacheHit) {
            $apiKey = $this->rapidapi_air_key();
            if ($apiKey === '') {
                log_message('error', 'AIR_TRACKING_CONFIG_ERROR: RAPIDAPI_AIR_CARGO_KEY is empty');

                return $this->response([
                    'status' => false,
                    'found' => true,
                    'code' => 'AIR_TRACKING_NOT_CONFIGURED',
                    'message' => 'Air tracking service is temporarily unavailable.',
                ], 503);
            }

            $host = 'air-cargo-co2-track-and-trace.p.rapidapi.com';
            $url = 'https://' . $host . '/track?awb=' . rawurlencode($awb);

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CONNECTTIMEOUT => 8,
                CURLOPT_TIMEOUT => 25,
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                    'X-RapidAPI-Key: ' . $apiKey,
                    'X-RapidAPI-Host: ' . $host,
                ],
            ]);

            $body = curl_exec($ch);
            $curlError = curl_error($ch);
            $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($curlError !== '') {
                log_message('error', 'AIR_TRACKING_CURL_ERROR awb=' . $awb . ' error=' . $curlError);

                return $this->response([
                    'status' => false,
                    'found' => true,
                    'code' => 'AIR_TRACKING_UPSTREAM_ERROR',
                    'message' => 'Air tracking service could not be reached.',
                ], 502);
            }

            $decoded = json_decode((string)$body, true);
            if ($httpCode < 200 || $httpCode >= 300 || json_last_error() !== JSON_ERROR_NONE) {
                log_message('error', 'AIR_TRACKING_BAD_RESPONSE awb=' . $awb . ' http=' . $httpCode);

                return $this->response([
                    'status' => false,
                    'found' => true,
                    'code' => 'AIR_TRACKING_UPSTREAM_ERROR',
                    'message' => 'Air tracking service returned an invalid response.',
                ], 502);
            }
        }

        // API sample is an array containing one AWB object.
        $air = (isset($decoded[0]) && is_array($decoded[0])) ? $decoded[0] : $decoded;
        if (!is_array($air) || empty($air)) {
            return $this->response([
                'status' => false,
                'found' => true,
                'code' => 'AIR_TRACKING_NO_DATA',
                'message' => 'No air tracking information is available for this MAWB.',
            ], 404);
        }

        $events = (isset($air['events']) && is_array($air['events'])) ? $air['events'] : [];

        // Keep the API events in real chronological order. This is important for
        // multi-leg flights (e.g. COK -> KUL -> CGK).
        usort($events, function ($a, $b) {
            $ta = !empty($a['eventDate']) ? strtotime($a['eventDate']) : 0;
            $tb = !empty($b['eventDate']) ? strtotime($b['eventDate']) : 0;
            return $ta <=> $tb;
        });

        $originCode = strtoupper(trim((string)($air['origin'] ?? '')));
        $destinationCode = strtoupper(trim((string)($air['destination'] ?? '')));
        $originAirport = $this->air_get_airport($originCode);
        $destinationAirport = $this->air_get_airport($destinationCode);

        $originPoint = $this->air_resolve_point($originCode, $air['originCoord'] ?? null);
        $destinationPoint = $this->air_resolve_point($destinationCode, $air['destinationCoord'] ?? null);

        $lastEvent = null;
        $lastPoint = null;
        $deliveredEvent = null;

        foreach ($events as $event) {
            $eventCode = strtoupper(trim((string)($event['code'] ?? '')));
            $eventLocation = strtoupper(trim((string)($event['eventLocation'] ?? '')));
            $point = $this->air_resolve_point($eventLocation, $event['eventLocationCoord'] ?? null);

            if ($point) {
                $lastEvent = $event;
                $lastPoint = $point;
            }

            if ($eventCode === 'DLV') {
                $deliveredEvent = $event;
            }
        }

        if (!$lastPoint) {
            $lastPoint = $originPoint;
        }

        $lastFlight = '';
        if (is_array($lastEvent)) {
            $lastFlight = trim((string)($lastEvent['flightNumber'] ?? ($lastEvent['flight']['number'] ?? '')));
        }

        // Timeline: de-duplicate duplicated status records returned once per flight leg.
        $eventList = [];
        $seenTimelineEvents = [];
        foreach ($events as $idx => $event) {
            $eventCode = strtoupper(trim((string)($event['code'] ?? '')));
            $eventLocation = strtoupper(trim((string)($event['eventLocation'] ?? ($event['origin'] ?? ''))));
            $eventDate = trim((string)($event['eventDate'] ?? ''));

            $dedupeKey = $eventCode . '|' . $eventLocation . '|' . $eventDate;
            if (isset($seenTimelineEvents[$dedupeKey])) {
                continue;
            }
            $seenTimelineEvents[$dedupeKey] = true;

            $airport = $this->air_get_airport($eventLocation);
            $flightNo = trim((string)($event['flightNumber'] ?? ($event['flight']['number'] ?? '')));
            $flightOrigin = strtoupper(trim((string)($event['origin'] ?? ($event['flight']['origin'] ?? ''))));
            $flightDestination = strtoupper(trim((string)($event['destination'] ?? ($event['flight']['destination'] ?? ''))));
            $flightOriginAirport = $this->air_get_airport($flightOrigin);
            $flightDestinationAirport = $this->air_get_airport($flightDestination);

            $eventList[] = [
                'no' => count($eventList) + 1,
                'port name' => $eventLocation,
                'airport_name' => $airport['name'] ?? null,
                'airport_municipality' => $airport['municipality'] ?? null,
                'airport_country' => $airport['iso_country'] ?? null,
                'location name' => 'Air Cargo',
                'event name' => $this->air_event_label($eventCode),
                'event time' => $eventDate,
                'flight_number' => $flightNo,
                'flight_origin' => $flightOrigin,
                'flight_destination' => $flightDestination,
                'flight_origin_name' => $flightOriginAirport['name'] ?? null,
                'flight_destination_name' => $flightDestinationAirport['name'] ?? null,
                'event_code' => $eventCode,
                'is_planned' => !empty($event['isPlanned']),
                'weight' => $event['weight'] ?? ($air['weight'] ?? null),
                'volume' => $event['volume'] ?? ($air['volume'] ?? null),
                'pieces' => $event['pieces'] ?? ($air['pieces'] ?? null),
            ];
        }

        // Build one clean route leg per unique flight and sort by departure time.
        $routing = [];
        $seenFlights = [];
        foreach ($events as $event) {
            $flightNo = trim((string)($event['flightNumber'] ?? ($event['flight']['number'] ?? '')));
            $from = strtoupper(trim((string)($event['origin'] ?? ($event['flight']['origin'] ?? ''))));
            $to = strtoupper(trim((string)($event['destination'] ?? ($event['flight']['destination'] ?? ''))));

            if ($flightNo === '' || $from === '' || $to === '') {
                continue;
            }

            $key = $flightNo . '|' . $from . '|' . $to;
            if (isset($seenFlights[$key])) {
                continue;
            }
            $seenFlights[$key] = true;

            $fromAirport = $this->air_get_airport($from);
            $toAirport = $this->air_get_airport($to);
            $fromPoint = $this->air_resolve_point(
                $from,
                $event['originCoord'] ?? ($event['flight']['originCoord'] ?? null)
            );
            $toPoint = $this->air_resolve_point(
                $to,
                $event['destinationCoord'] ?? ($event['flight']['destinationCoord'] ?? null)
            );

            $routing[] = [
                'vessel' => $flightNo,
                'flight_number' => $flightNo,
                'port of loading' => $from,
                'port of discharge' => $to,
                'origin_name' => $fromAirport['name'] ?? null,
                'destination_name' => $toAirport['name'] ?? null,
                'origin_municipality' => $fromAirport['municipality'] ?? null,
                'destination_municipality' => $toAirport['municipality'] ?? null,
                'origin_point' => $this->air_point_string($fromPoint),
                'destination_point' => $this->air_point_string($toPoint),
                'time of departure' => $event['flight']['actualDeparture']
                    ?? $event['flight']['estimatedDeparture']
                    ?? $event['flight']['scheduledDeparture']
                    ?? '',
                'time of arrival' => $event['flight']['actualArrival']
                    ?? $event['flight']['estimatedArrival']
                    ?? $event['flight']['scheduledArrival']
                    ?? '',
                'mode' => 'AIR',
            ];
        }

        usort($routing, function ($a, $b) {
            $ta = !empty($a['time of departure']) ? strtotime($a['time of departure']) : PHP_INT_MAX;
            $tb = !empty($b['time of departure']) ? strtotime($b['time of departure']) : PHP_INT_MAX;
            return $ta <=> $tb;
        });

        // Build the actual multi-leg route for the map: COK -> KUL -> CGK, etc.
        $airRoutePoints = [];
        $appendRoutePoint = function ($iata, $name, $municipality, $point, $role = 'transit') use (&$airRoutePoints) {
            if (!is_array($point) || count($point) < 2) {
                return;
            }

            $iata = strtoupper(trim((string)$iata));
            $last = !empty($airRoutePoints) ? $airRoutePoints[count($airRoutePoints) - 1] : null;

            if ($last && $iata !== '' && strtoupper((string)($last['iata'] ?? '')) === $iata) {
                return;
            }

            $airRoutePoints[] = [
                'iata' => $iata,
                'name' => $name ?: null,
                'municipality' => $municipality ?: null,
                'lat' => (float)$point[0],
                'lon' => (float)$point[1],
                'point' => [(float)$point[0], (float)$point[1]],
                'role' => $role,
            ];
        };

        foreach ($routing as $routeIndex => $route) {
            $from = $route['port of loading'] ?? '';
            $to = $route['port of discharge'] ?? '';
            $fromAirport = $this->air_get_airport($from);
            $toAirport = $this->air_get_airport($to);
            $fromPoint = $this->air_resolve_point($from, null);
            $toPoint = $this->air_resolve_point($to, null);

            // Prefer coordinates already resolved from RapidAPI for this leg.
            if (!empty($route['origin_point'])) {
                $tmp = array_map('trim', explode(',', $route['origin_point']));
                if (count($tmp) >= 2 && is_numeric($tmp[0]) && is_numeric($tmp[1])) {
                    $fromPoint = [(float)$tmp[0], (float)$tmp[1]];
                }
            }
            if (!empty($route['destination_point'])) {
                $tmp = array_map('trim', explode(',', $route['destination_point']));
                if (count($tmp) >= 2 && is_numeric($tmp[0]) && is_numeric($tmp[1])) {
                    $toPoint = [(float)$tmp[0], (float)$tmp[1]];
                }
            }

            $appendRoutePoint(
                $from,
                $fromAirport['name'] ?? ($route['origin_name'] ?? null),
                $fromAirport['municipality'] ?? ($route['origin_municipality'] ?? null),
                $fromPoint,
                $routeIndex === 0 ? 'origin' : 'transit'
            );
            $appendRoutePoint(
                $to,
                $toAirport['name'] ?? ($route['destination_name'] ?? null),
                $toAirport['municipality'] ?? ($route['destination_municipality'] ?? null),
                $toPoint,
                'transit'
            );
        }

        if (empty($airRoutePoints)) {
            $appendRoutePoint($originCode, $originAirport['name'] ?? null, $originAirport['municipality'] ?? null, $originPoint, 'origin');
            $appendRoutePoint($destinationCode, $destinationAirport['name'] ?? null, $destinationAirport['municipality'] ?? null, $destinationPoint, 'destination');
        }

        if (count($airRoutePoints) > 0) {
            $airRoutePoints[0]['role'] = 'origin';
            $airRoutePoints[count($airRoutePoints) - 1]['role'] = 'destination';
        }

        // Use route endpoints as coordinate fallback if top-level coordinates are missing.
        if (!$originPoint && !empty($airRoutePoints[0]['point'])) {
            $originPoint = $airRoutePoints[0]['point'];
        }
        if (!$destinationPoint && !empty($airRoutePoints[count($airRoutePoints) - 1]['point'])) {
            $destinationPoint = $airRoutePoints[count($airRoutePoints) - 1]['point'];
        }

        $transitStops = count($airRoutePoints) > 2 ? array_slice($airRoutePoints, 1, -1) : [];
        $firstTransit = !empty($transitStops[0]) ? $transitStops[0] : null;

        $lastUpdate = $lastEvent['eventDate'] ?? date('c');
        $status = strtoupper(trim((string)($air['status'] ?? 'IN TRANSIT')));
        $isDelivered = !empty($deliveredEvent) || $status === 'DELIVERED' || $status === 'DLV';
        if ($isDelivered) {
            $status = 'DELIVERED';
        }

        // Save only completed shipments. Future requests for this AWB read the cache
        // above and do not hit RapidAPI again.
        if (!$cacheHit && $isDelivered) {
            $this->air_tracking_cache_save($awb, $shipment, $decoded, $deliveredEvent, $status);
        }

        $airline = trim((string)($shipment['air_line'] ?? ''));
        if ($airline === '') {
            $airline = trim((string)($shipment['carrier'] ?? ''));
        }

        $header = [[
            'hbl' => '',
            'mbl' => $awb,
            'awb' => $awb,
            'container no' => $awb,
            'status' => $status,
            'last update' => $lastUpdate,
            'arrived' => $this->air_event_label($lastEvent['code'] ?? ''),
            'scac' => $airline !== '' ? $airline : substr($awb, 0, 3),
            'carrier' => $airline,
            'vessel' => $lastFlight,
            'flight_number' => $lastFlight,
            'pol' => $originCode !== '' ? $originCode : ($shipment['port_loading'] ?? ''),
            'pol_name' => $originAirport['name'] ?? null,
            'pod' => $destinationCode !== '' ? $destinationCode : ($shipment['destination'] ?? ''),
            'pod_name' => $destinationAirport['name'] ?? null,
            'pot' => $firstTransit['iata'] ?? null,
            'pot_name' => $firstTransit['name'] ?? null,
            'pol_point' => $this->air_point_string($originPoint),
            'pod_point' => $this->air_point_string($destinationPoint),
            'pot_point' => !empty($firstTransit['point']) ? $this->air_point_string($firstTransit['point']) : null,
            'weight' => $air['weight'] ?? null,
            'volume' => $air['volume'] ?? null,
            'pieces' => $air['pieces'] ?? null,
            'distance_km' => isset($air['distance']) && is_numeric($air['distance']) ? (float)$air['distance'] : null,
            'duration' => $air['time'] ?? null,
            'transport_mode' => 'AIR',
        ]];

        $co2Kg = null;
        if (!empty($air['carbonEmission']) && preg_match('/([\d.]+)/', (string)$air['carbonEmission'], $m)) {
            $co2Kg = (float)$m[1];
        }

        $cargoCurrent = [];
        if ($lastPoint) {
            $lastLocationCode = strtoupper(trim((string)($lastEvent['eventLocation'] ?? '')));
            $lastAirport = $this->air_get_airport($lastLocationCode);

            $cargoCurrent = [
                'lat' => $lastPoint[0],
                'lon' => $lastPoint[1],
                'current_vessel' => $lastFlight !== '' ? $lastFlight : 'Aircraft',
                'flight_number' => $lastFlight,
                'last_updated' => $lastUpdate,
                'timestamp' => $lastUpdate,
                'source' => 'air_cargo_event',
                'event_code' => $lastEvent['code'] ?? null,
                'event_name' => $this->air_event_label($lastEvent['code'] ?? ''),
                'location' => $lastLocationCode,
                'airport_name' => $lastAirport['name'] ?? null,
                'is_live' => false,
            ];
        }

        return $this->response([
            'status' => true,
            'found' => true,
            'tracking_type' => 'air',
            'transport_mode' => 'AIR',
            'query' => $input,
            'awb' => $awb,
            'header' => $header,
            'routing' => array_values($routing),
            'air_route_points' => array_values($airRoutePoints),
            'stepper' => array_values($eventList),
            'event list' => array_values($eventList),
            'ais_current' => [],
            'ais_history' => [],
            'cargo_current' => $cargoCurrent,
            'active_ais_leg' => null,
            'ais_legs' => [],
            'warehouse_tracking' => null,
            'maritime_path' => [],
            'co2_emission' => $co2Kg !== null ? [
                'source' => 'rapidapi_air_cargo',
                'co2_kg' => $co2Kg,
            ] : null,
            'air_meta' => [
                'origin' => $originCode ?: null,
                'origin_name' => $originAirport['name'] ?? null,
                'destination' => $destinationCode ?: null,
                'destination_name' => $destinationAirport['name'] ?? null,
                'transit_airports' => $transitStops,
                'distance_km' => $air['distance'] ?? null,
                'duration' => $air['time'] ?? null,
                'weight' => $air['weight'] ?? null,
                'volume' => $air['volume'] ?? null,
                'pieces' => $air['pieces'] ?? null,
                'last_position_is_live' => false,
                'cache_hit' => $cacheHit,
                'cache_source' => $cacheHit ? 'air_tracking_cache' : 'rapidapi',
                'is_delivered' => $isDelivered,
            ],
        ], 200);
    }

    public function index_get()
    {
        
        $aisMode = $this->configure_ais_mode_from_request();
        $includeAis = (bool)$aisMode['include_ais'];
        $includeAisParam = $aisMode['include_ais_param'];
        $mode = $aisMode['mode'];

		$si_number = $this->get('si_number');
		if (!$si_number) {
			$si_number = $this->input->get('bl'); // fallback ke bl
		}

        $si_number = trim((string)$si_number);

        // Unified search: bila input cocok dengan mother_bl shipment AIR FREIGHT,
        // gunakan air tracking dan JANGAN meneruskan ke GoComet/Jarvis.
        $airShipment = $this->find_air_shipment_by_awb($si_number);
        if (!empty($airShipment)) {
            return $this->air_tracking_response($si_number, $airShipment);
        }


        $safe_json = function ($value, $assoc = true) {
            if (empty($value)) return $assoc ? [] : (object)[];
            $decoded = json_decode($value, $assoc);
            return (json_last_error() === JSON_ERROR_NONE && !empty($decoded))
                ? $decoded
                : ($assoc ? [] : (object)[]);
        };

        $parse_dt = function ($dateString) {
            if (empty($dateString)) return null;

            $dateString = trim((string)$dateString);
            $formats = [
                'd/m/Y H:i:s',
                'd/m/Y H:i',
                'd/m/Y',
                'Y-m-d H:i:s',
                'Y-m-d H:i',
                'Y-m-d',
                'Ymd H:i',
                'Ymd',
                DateTime::ATOM,
                'Y-m-d\TH:i:s\Z',
                'Y-m-d\TH:i:s.u\Z',
                'Y-m-d\TH:i:sP',
            ];

            foreach ($formats as $fmt) {
                $dt = DateTime::createFromFormat($fmt, $dateString);
                if ($dt instanceof DateTime) {
                    return $dt;
                }
            }

            $ts = strtotime($dateString);
            if ($ts !== false) {
                $dt = new DateTime();
                $dt->setTimestamp($ts);
                return $dt;
            }

            return null;
        };

        $fmt_event_time = function ($actual, $planned, $original, $dischargeOrLoading = '') use ($parse_dt) {
			// 1. Actual selalu menang.
			if (!empty($actual)) {
				$dt = $parse_dt($actual);
				return $dt ? $dt->format('d/m/Y H:i:s') : $actual;
			}

			// 2. Planned terbaru dari GoComet harus menang dari original planned.
			if (!empty($planned)) {
				$dt = $parse_dt($planned);
				return $dt ? $dt->format('d/m/Y H:i:s') . '*' : ($planned . '*');
			}

			// 3. Untuk beberapa ocean event, GoComet kadang taruh jadwal di discharge_or_loading_datetime.
			if (!empty($dischargeOrLoading)) {
				$dt = $parse_dt($dischargeOrLoading);
				return $dt ? $dt->format('d/m/Y H:i:s') . '*' : ($dischargeOrLoading . '*');
			}

			// 4. Original planned hanya fallback terakhir, jangan jadi tanggal utama.
			if (!empty($original)) {
				$dt = $parse_dt($original);
				return $dt ? $dt->format('d/m/Y H:i:s') . '**' : ($original . '**');
			}

			return '';
		};

        $is_past = function ($dateString) use ($parse_dt) {
            $dt = $parse_dt($dateString);
            if (!$dt) return false;
            $today = new DateTime();
            return $dt <= $today;
        };

        $push_unique = function (&$arr, $row) {
            if (!is_array($row) || empty($row)) return;

            static $seen = [];
            $hash = md5(json_encode($row));

            if (!isset($seen[$hash])) {
                $seen[$hash] = true;
                $arr[] = $row;
            }
        };

        $gocomet_result = $this->m_api->get_gocomet($si_number);
		$gocomet = $this->safe_row_object($gocomet_result);

		$consol_result = $this->m_api->get_consol_destination($si_number);
		$consol = $this->safe_row_array($consol_result);

		$hasil_lokal = $this->m_api->get_x_tracking_export($si_number);
		$hasil_gudang = $this->m_api->get_x_tracking_gudang($si_number);
		$hasil_lokal_import = $this->m_api->get_x_tracking_import($si_number);

		$do_result = $this->m_api->get_do_number($si_number);
		$do_row = $this->safe_row_array($do_result);
		$do_number = $do_row['do_number'] ?? null;
		
		
		// Helper foto cargo GCL Warehouse.
		// Endpoint public baru tidak membutuhkan JWT/Bearer token.
		// Return tetap array URL agar kompatibel dengan key timeline `images`.
		$get_cargo_arrival_photos = function ($booking_code) {
			$booking_code = trim((string)$booking_code);

			if ($booking_code === '') {
				return [];
			}

			$url = 'https://gcl-warehouse.com/api/v1/inventory/public/cargo-history/'
				. rawurlencode($booking_code)
				. '/photos';

			log_message('debug', 'WMS_CARGO_PHOTOS_REQUEST=' . json_encode([
				'booking_code' => $booking_code,
				'url' => $url,
			], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

			$ch = curl_init();
			curl_setopt_array($ch, [
				CURLOPT_URL => $url,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_HTTPGET => true,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_CONNECTTIMEOUT => 5,
				CURLOPT_TIMEOUT => 15,
				CURLOPT_HTTPHEADER => [
					'Accept: application/json',
				],
			]);

			$body = curl_exec($ch);
			$curl_error = curl_error($ch);
			$http_code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);

			if ($curl_error !== '') {
				log_message('error', 'WMS_CARGO_PHOTOS_CURL_ERROR=' . json_encode([
					'booking_code' => $booking_code,
					'error' => $curl_error,
				], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
				return [];
			}

			if ($http_code < 200 || $http_code >= 300 || empty($body)) {
				log_message('error', 'WMS_CARGO_PHOTOS_BAD_RESPONSE=' . json_encode([
					'booking_code' => $booking_code,
					'http_code' => $http_code,
					'body' => substr((string)$body, 0, 1000),
				], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
				return [];
			}

			$result = json_decode($body, true);

			if (json_last_error() !== JSON_ERROR_NONE || !is_array($result)) {
				log_message('error', 'WMS_CARGO_PHOTOS_INVALID_JSON=' . json_encode([
					'booking_code' => $booking_code,
					'error' => json_last_error_msg(),
					'body' => substr((string)$body, 0, 1000),
				], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
				return [];
			}

			// API WMS versi baru memakai {success:true, message:..., data:{...}}.
			// Tetap dukung format code=200 bila backend mengembalikannya di versi lain.
			$api_success = (($result['success'] ?? false) === true)
				|| ((int)($result['code'] ?? 0) === 200);

			if (!$api_success || empty($result['data']) || !is_array($result['data'])) {
				log_message('error', 'WMS_CARGO_PHOTOS_API_ERROR=' . json_encode([
					'booking_code' => $booking_code,
					'success' => $result['success'] ?? null,
					'api_code' => $result['code'] ?? null,
					'message' => $result['message'] ?? '',
				], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
				return [];
			}

			$data = $result['data'];
			$photos = [];

			/*
			 * WMS pernah menyimpan photos dalam beberapa bentuk:
			 *   1) "https://...jpg"
			 *   2) ["https://...jpg"]
			 *   3) "[\"https://...jpg\"]"  <-- array JSON yang tersimpan sebagai string
			 * Helper ini flatten semuanya menjadi array URL normal.
			 */
			$extract_photo_urls = null;
			$extract_photo_urls = function ($value) use (&$extract_photo_urls) {
				$out = [];

				if ($value === null || $value === '') {
					return $out;
				}

				if (is_array($value)) {
					foreach ($value as $item) {
						foreach ($extract_photo_urls($item) as $url) {
							$out[] = $url;
						}
					}
					return $out;
				}

				if (!is_string($value)) {
					return $out;
				}

				$value = trim($value);
				if ($value === '') {
					return $out;
				}

				// Jika value sendiri adalah JSON encoded array/string, decode lalu recurse.
				$decoded = json_decode($value, true);
				if (json_last_error() === JSON_ERROR_NONE && $decoded !== $value) {
					return $extract_photo_urls($decoded);
				}

				// Direct URL.
				if (filter_var($value, FILTER_VALIDATE_URL)) {
					$scheme = strtolower((string)parse_url($value, PHP_URL_SCHEME));
					if (in_array($scheme, ['http', 'https'], true)) {
						$out[] = $value;
					}
					return $out;
				}

				// Safety fallback untuk data legacy yang membungkus URL dengan text/markdown.
				if (preg_match_all('~https?://[^\s"\'<>\]\)\\]+~i', $value, $matches)) {
					foreach ($matches[0] as $candidate) {
						$candidate = trim($candidate);
						if (filter_var($candidate, FILTER_VALIDATE_URL)) {
							$out[] = $candidate;
						}
					}
				}

				return $out;
			};

			// Event ini khusus Cargo Arrival: prioritaskan history bertipe Arrival.
			if (!empty($data['history_photos']) && is_array($data['history_photos'])) {
				foreach ($data['history_photos'] as $history) {
					if (!is_array($history)) {
						continue;
					}

					$transaction_type = strtolower(trim((string)($history['cargo_transaction_type'] ?? '')));
					if ($transaction_type !== 'arrival') {
						continue;
					}

					foreach ($extract_photo_urls($history['photos'] ?? []) as $img_url) {
						$photos[] = $img_url;
					}
				}
			}

			// Fallback ke foto agregat bila history Arrival kosong.
			if (empty($photos)) {
				foreach ($extract_photo_urls($data['photos'] ?? []) as $img_url) {
					$photos[] = $img_url;
				}
			}

			$photos = array_values(array_unique($photos));

			log_message('debug', 'WMS_CARGO_PHOTOS_OK=' . json_encode([
				'booking_code_requested' => $booking_code,
				'api_booking_code' => $data['booking_code'] ?? null,
				'linked_booking_code' => $data['linked_booking_code'] ?? null,
				'total_photos_api' => $data['total_photos'] ?? null,
				'arrival_photos_used' => count($photos),
				'images' => $photos,
			], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

			return $photos;
		};

		/*
		 * Hindari:
		 * $this->dbx->get('tokens')->row()->access_token
		 *
		 * Karena row() juga dapat menghasilkan NULL.
		 */
		$token_query = $this->dbx
			->select('access_token')
			->from('tokens')
			->limit(1)
			->get();

		$token_row = $this->safe_row_object($token_query);
		$token = $token_row->access_token ?? null;

		$etdString = !empty($consol['atd'])
			? $consol['atd']
			: ($consol['etd_jkt'] ?? null);

		$etaString = !empty($consol['ata'])
			? $consol['ata']
			: ($consol['eta'] ?? null);

		$etd_db = !empty($etdString) ? DateTime::createFromFormat("Ymd", $etdString, new DateTimeZone("Asia/Jakarta")) : null;
		$eta_db = !empty($etaString) ? DateTime::createFromFormat("Ymd", $etaString, new DateTimeZone("Asia/Jakarta")) : null;

		$etd_fallback = $etd_db ? $etd_db->format("Y-m-d H:i:s T") : null;
		$eta_fallback = $eta_db ? $eta_db->format("Y-m-d H:i:s T") : null;
		$has_gocomet = $gocomet !== null;

		$has_consol = !empty($consol);

		$has_local = $this->tracking_result_has_data($hasil_lokal);

		$has_warehouse = $this->tracking_result_has_data($hasil_gudang);

		$has_import = $this->tracking_result_has_data($hasil_lokal_import);

		if (
			!$has_gocomet &&
			!$has_consol &&
			!$has_local &&
			!$has_warehouse &&
			!$has_import
		) {
			return $this->response([
				'status' => false,
				'found' => false,
				'code' => 'TRACKING_NOT_FOUND',
				'message' => 'No tracking information was found for this BL / HBL number.',
				'bl' => $si_number,
				'data' => null,
			], RestController::HTTP_NOT_FOUND);
		}

		$tracking_id    = $gocomet->tracking_id ?? null;
		$type_shipment  = $consol['type_of_trans'] ?? '';
		$scac           = $consol['scac'] ?? '';
		$carrier        = $consol['carrier'] ?? '';
		$vessel_master  = trim($consol['vessel'] ?? '');
		$voyage_master  = $consol['voyage'] ?? '';
		$hbl            = $consol['hbl'] ?? '';
		$volume         = $consol['volume'] ?? '';
		$cbm            = is_numeric($volume) ? (float)$volume : 0;
		$mbl            = $consol['mbl'] ?? ($gocomet->mother_bl ?? '');
		$size           = $consol['type_cont_size'] ?? '';

		// default assignment
		$pol       = !empty($consol['pol']) ? ($this->get_code_by_name($consol['pol']) ?? $consol['pol']) : '';
		$pol_point = !empty($consol['pol']) ? $this->get_point_by_name($consol['pol']) : null;
		$pod       = !empty($consol['destination']) ? ($this->get_code_by_name($consol['destination']) ?? $consol['destination']) : '';
		$pod_point = !empty($consol['destination']) ? $this->get_point_by_name($consol['destination']) : null;
		$pot       = !empty($consol['transhipment_port']) ? ($this->get_code_by_name($consol['transhipment_port']) ?? $consol['transhipment_port']) : '';
		$pot_point = !empty($consol['transhipment_port']) ? $this->get_point_by_name($consol['transhipment_port']) : null;

		// override jika bukan LCL/FCL/coload
		if (!in_array(strtoupper($type_shipment), ['LCL', 'FCL', 'COLOAD'])) {
			$pot       = '';
			$pot_point = null;
			$pod       = !empty($consol['consol_destination']) ? ($this->get_code_by_name($consol['consol_destination']) ?? $consol['consol_destination']) : '';
			$pod_point = !empty($consol['consol_destination']) ? $this->get_point_by_name($consol['consol_destination']) : null;
		}

		$satu = trim($consol['consol_destination'] ?? '');
		$dua  = trim($consol['destination'] ?? '');
		$tiga = trim($consol['transhipment_port'] ?? '');

		$stuffing_date      = $consol['stuffing_date'] ?? null;
		$closing_date_time  = $consol['closing_date_time'] ?? null;
		$closing_time       = $consol['closing_time'] ?? null;

        $closing_datetime   = null;

        if (!empty($closing_date_time) && !empty($closing_time)) {
            $closing_datetime = DateTime::createFromFormat('Ymd H:i', $closing_date_time . ' ' . $closing_time);
        }

        $header = [];
        $items = [];
        $local_routes = [];
        $steppers = [];
        $table = [];
        $table_import = [];
        $bintang_progress = [];
        $bintang_tracking_summary = null;

        $ais_current = [];
        $ais_history = [];
		
		$container_list = [];
		$header_created = false;

        $hasil = null;
        $gocomet_routes_for_imo = [];

        try {
            if (!empty($tracking_id) && !empty($token)) {
                $client = new \GuzzleHttp\Client([
                    'timeout' => 8,
                    'connect_timeout' => 5,
                ]);

                $response = $client->request('GET', 'https://tracking.gocomet.com/api/v1/integrations/live-tracking', [
                    'headers' => [
                        'Accept' => 'application/json',
                    ],
                    'query' => [
                        'tracking_ids[]' => $tracking_id,
                        'token' => $token,
                    ],
                ]);

                $hasil = json_decode($response->getBody());
            }
        } catch (Exception $ex) {
            log_message('error', 'Track_gocomet index_get GoComet error: ' . $ex->getMessage());
            $hasil = null;
        }

        $has_valid_gocomet = false;

        if (!empty($hasil->updated_trackings) && is_array($hasil->updated_trackings)) {
            foreach ($hasil->updated_trackings as $trk) {
                $status = $trk->status ?? '';

                if (in_array($status, ['Data Not Found', 'Invalid'], true)) {
                    continue;
                }

                $has_valid_gocomet = true;

                $container_no = $trk->container_number ?? '';
                $shipment_remark = $trk->shipment_remark ?? '';
                $updated_at = $trk->updated_at ?? '';
                $events = (isset($trk->events) && is_array($trk->events)) ? $trk->events : [];

                $delay_text = 'On Transit';
                $best_case_eta = $trk->stats->best_case_eta ?? null;
                $predicted_eta = $trk->stats->predicted_eta ?? null;

                if (!empty($best_case_eta) && !empty($predicted_eta)) {
                    $dt1 = DateTime::createFromFormat('d/m/Y', $best_case_eta);
                    $dt2 = DateTime::createFromFormat('d/m/Y', $predicted_eta);
                    if ($dt1 && $dt2) {
                        $diff = $dt1->diff($dt2);
                        $delay_text = ($diff->days == 0) ? 'On Time' : ($diff->days . ' days delay');
                    }
                }

                $head = [
                    'hbl'          => $hbl,
                    'cbm'          => $volume,
                    'scac'         => $scac,
                    'container no' => htmlspecialchars($container_no),
                    'status'       => $status,
                    'last update'  => htmlspecialchars($updated_at),
                    'delay'        => $delay_text,
                    'arrived'      => !empty($shipment_remark) ? htmlspecialchars($shipment_remark) : '',
                ];
                if (!empty($carrier))       $head['carrier'] = $carrier;
                if (!empty($vessel_master)) $head['vessel'] = $vessel_master;
                if (!empty($voyage_master)) $head['voyage'] = $voyage_master;
                if (!empty($pol))           $head['pol'] = $pol;
                if (!empty($pod))           $head['pod'] = $pod;
                if (!empty($pot))           $head['pot'] = $pot;
                if (!empty($mbl))           $head['mbl'] = $mbl;
                if (!empty($etd_fallback))           $head['etd'] = $etd_fallback;
                if (!empty($eta_fallback))           $head['eta'] = $eta_fallback;
                if (!empty($size))          $head['container_size'] = $size;
                if (!empty($pol_point))     $head['pol_point'] = $pol_point;
                if (!empty($pod_point))     $head['pod_point'] = $pod_point;
                if (!empty($pot_point))     $head['pot_point'] = $pot_point;

               if (!empty($container_no)) {
					$container_list[] = $container_no;
					$container_list = array_values(array_unique($container_list));
				}

				if (!$header_created) {
					$head['container no'] = implode(' / ', $container_list);
					$head['containers'] = $container_list;
					$header[] = [$head];
					$header_created = true;
				} else {
					// update header pertama agar container list bertambah
					$header[0][0]['container no'] = implode(' / ', $container_list);
					$header[0][0]['containers'] = $container_list;
				}

                $groupedByImo = [];
                foreach ($events as $ev) {
                    $imo = $ev->vessel_details->imo_number ?? null;
                    $voy = $ev->vessel_details->voyage_num ?? null;
                    $mode = strtoupper($ev->mode ?? '');

                    if ($mode === 'OCEAN' && !empty($imo) && !empty($voy)) {
                        if (!isset($groupedByImo[$imo])) {
                            $groupedByImo[$imo] = [];
                        }
                        $groupedByImo[$imo][] = $ev;
                    }
                }

                foreach ($groupedByImo as $imo => $evs) {
                    $first = reset($evs);
                    $last  = end($evs);

                    $route = [
						'vessel'          => trim(($first->vessel_details->vessel_name ?? '') . '  V.' . ($first->vessel_details->voyage_num ?? '') . ' - IMO:' . ($first->vessel_details->imo_number ?? '')),
						'port of loading' => ($first->connected_port ?? '') . (!empty($first->display_event) ? ' - ' . $first->display_event : ''),
						'container'       => htmlspecialchars($container_no),
						'_source_name'    => 'gocomet',
						'_source_priority'=> 50,
					];

					if (!empty($last->connected_port)) {
						$route['port of discharge'] = $last->connected_port;
					}

					$route['time of departure'] = $fmt_event_time(
						$first->actual_datetime ?? '',
						$first->planned_datetime ?? '',
						$first->original_planned_datetime ?? '',
						$first->discharge_or_loading_datetime ?? ''
					);

					$route['time of discharge'] = $fmt_event_time(
						$last->actual_datetime ?? '',
						$last->planned_datetime ?? '',
						$last->original_planned_datetime ?? '',
						$last->discharge_or_loading_datetime ?? ''
					);
					
					$route['_dedupe_key'] = md5(
						($route['vessel'] ?? '') . '|' .
						($route['port of loading'] ?? '') . '|' .
						($route['port of discharge'] ?? '')
					);

                    $push_unique($items, $route);
                    $gocomet_routes_for_imo[] = $route;
                }

                $grouped_events = [];
                foreach ($events as $ev) {
                    $portKey = $ev->connected_port ?? $ev->location ?? 'Unknown';
                    if (!isset($grouped_events[$portKey])) {
                        $grouped_events[$portKey] = [];
                    }
                    $grouped_events[$portKey][] = $ev;
                }

                foreach ($grouped_events as $port => $evs) {
                    $picked = null;

                    foreach ($evs as $ev) {
                        if (strtoupper($ev->mode ?? '') === 'OCEAN') {
                            $picked = $ev;
                        }
                    }

                    if ($picked === null) {
                        $picked = end($evs);
                    }

                    if (!$picked) continue;

                    $step = [
                        'location' => $port,
                        'location type' => $picked->display_event ?? '',
                        'event name' => $picked->mode ?? '',
                        'vessel' => trim(($picked->vessel_details->vessel_name ?? '') . ' ' . ($picked->vessel_details->voyage_num ?? '')),
                        'container' => htmlspecialchars($container_no),
                        'event time' => $fmt_event_time(
							$picked->actual_datetime ?? '',
							$picked->planned_datetime ?? '',
							$picked->original_planned_datetime ?? '',
							$picked->discharge_or_loading_datetime ?? ''
						),
                    ];

                    $push_unique($steppers, $step);
                }

                foreach ($events as $ev) {
                    $event_name = !empty($ev->remarks) ? $ev->remarks : ($ev->display_event ?? '');
                    $location_name = $ev->display_event ?? '';
                    $port_name = $ev->location ?? ($ev->connected_port ?? '');

                    if ($location_name === 'Gate Out') {
                        if ($type_shipment === "LCL" && $satu !== $dua && $dua === $tiga) {
                            $event_name = 'Unloaded and New Vessel Assignments are Prepared';
                            $location_name = 'Re-consolidated';
                        }
                    }
					$eventSlug = strtolower(trim((string)($ev->event ?? '')));
					$hasActual = !empty($ev->actual_datetime) || !empty($ev->actual_date);
					$hasPlanned = !empty($ev->planned_datetime) || !empty($ev->planned_date);

					if (
						in_array($eventSlug, ['gate_out', 'empty_return'], true) &&
						!$hasActual
					) {
						continue;
					}
					if ($eventSlug === 'arrival' && !$hasActual && $hasPlanned) {
						$event_name = 'Predicted arrival at final port of destination';
					}

                    $row = [
						'event name' => $event_name,
						'port name' => $port_name,
						'location name' => $location_name,
						'event time' => $fmt_event_time(
							$ev->actual_datetime ?? '',
							$ev->planned_datetime ?? '',
							$ev->original_planned_datetime ?? '',
							$ev->discharge_or_loading_datetime ?? ''
						),

						// internal only untuk dedupe
						'_source_name' => 'gocomet',
						'_source_priority' => 100,
					];

                    $push_unique($table, $row);
                }

                // AIS current + historical
                $imoNumber = null;
                $mmsiNumber = null;
                $vesselNameForLookup = trim((string)$vessel_master);

                foreach ($events as $evx) {
                    if (empty($vesselNameForLookup) && !empty($evx->vessel_details->vessel_name)) {
                        $vesselNameForLookup = trim((string)$evx->vessel_details->vessel_name);
                    }

                    if (!empty($evx->vessel_details->mmsi_number)) {
                        $mmsiNumber = trim((string)$evx->vessel_details->mmsi_number);
                    } elseif (!empty($evx->vessel_details->mmsi)) {
                        $mmsiNumber = trim((string)$evx->vessel_details->mmsi);
                    }

                    if (!empty($evx->vessel_details->imo_number)) {
                        $imoNumber = trim((string)$evx->vessel_details->imo_number);
                        break;
                    }
                }

                if (empty($imoNumber) && !empty($trk->ais_data->imo_number)) {
                    $imoNumber = trim((string)$trk->ais_data->imo_number);
                }

                if (empty($vesselNameForLookup) && !empty($trk->ais_data->current_vessel)) {
                    $vesselNameForLookup = trim((string)$trk->ais_data->current_vessel);
                }

                // Jika IMO dari GoComet kosong, cari dulu ke master vessel_imo by vessel name.
                // MMSI dari master akan dipakai untuk pencarian Jarvis.
                if ((empty($imoNumber) || empty($mmsiNumber)) && !empty($vesselNameForLookup)) {
                    $masterVessel = $this->get_vessel_imo_master($vesselNameForLookup);
                    if (!empty($masterVessel)) {
                        if (empty($imoNumber) && !empty($masterVessel['imo'])) {
                            $imoNumber = $masterVessel['imo'];
                        }
                        if (empty($mmsiNumber) && !empty($masterVessel['mmsi'])) {
                            $mmsiNumber = $masterVessel['mmsi'];
                        }
                    }
                }

                $etaDt = $this->extract_tracking_eta($trk) ?: (($eta_db instanceof DateTime) ? clone $eta_db : null);
				$etdDt = $this->extract_tracking_etd($trk) ?: (($etd_db instanceof DateTime) ? clone $etd_db : null);
                $shouldHitJarvisCurrent = $this->is_now_within_etd_eta($etdDt, $etaDt);
                $shouldFetchHistory = ($etaDt instanceof DateTime) ? ((new DateTime('now', new DateTimeZone('UTC'))) < $etaDt) : false;

                // 1. Ambil GoComet AIS dulu sebagai baseline.
				if (!empty($trk->ais_data)) {
					$gocometAis = $this->normalize_gocomet_ais($trk->ais_data, $imoNumber);

					if ($this->has_valid_ais_current($gocometAis)) {
						$ais_current = $gocometAis;
					}
				}

				// 2. Selama masih range ETD - ETA, tetap coba Jarvis current.
				// Tujuannya agar current cargo position sinkron dengan AIS history Jarvis.
				// Jarvis current boleh menggantikan GoComet jika valid.
				if (
					$includeAis &&
					$shouldHitJarvisCurrent &&
					(!empty($mmsiNumber) || !empty($imoNumber) || !empty($vesselNameForLookup))
				) {
                    $jarvisCurrent = $this->get_current_ais_from_jarvis(
                        $imoNumber,
                        $mmsiNumber,
                        $vesselNameForLookup
                    );

                    $jarvisAis = $this->normalize_jarvis_current_ais(
                        $jarvisCurrent,
                        $imoNumber,
                        $vesselNameForLookup
                    );

                    if ($this->has_valid_ais_current($jarvisAis)) {
					$ais_current = $jarvisAis;

					// IMPORTANT:
					// Jika current AIS berhasil dari Jarvis dan membawa IMO,
					// pakai IMO ini untuk historical AIS.
					if (empty($imoNumber) && !empty($jarvisAis['imo_number'])) {
						$imoNumber = $jarvisAis['imo_number'];
					}

					// Kalau raw Jarvis punya MMSI, simpan juga untuk debug / fallback.
					if (empty($mmsiNumber)) {
						$foundMmsi = $this->find_first_value_recursive($jarvisCurrent, [
							'mmsi',
							'MmsiNumber',
							'mmsiNumber',
						]);

						if (!empty($foundMmsi)) {
							$mmsiNumber = $foundMmsi;
						}
					}
				}
                }
				
				// Safety: kalau IMO masih kosong, coba ambil dari ais_current hasil GoComet/Jarvis.
				if (empty($imoNumber) && !empty($ais_current['imo_number'])) {
					$imoNumber = $ais_current['imo_number'];
				}

                // 3. Historical Jarvis tetap membutuhkan IMO.
                // Kalau IMO awal kosong tapi master vessel_imo punya IMO, history masih bisa jalan.
                if (
                    $shouldFetchHistory &&
                    !empty($imoNumber) &&
                    $etdDt instanceof DateTime &&
                    $etaDt instanceof DateTime
                ) {
                    $historyFromIso = clone $etdDt;
                    $historyUntilIso = clone $etaDt;

                    $historyFromIso = $historyFromIso
                        ->setTimezone(new DateTimeZone('UTC'))
                        ->format('Y-m-d\TH:i:s\Z');

                    $historyUntilIso = $historyUntilIso
                        ->setTimezone(new DateTimeZone('UTC'))
                        ->format('Y-m-d\TH:i:s\Z');

                    $jarvisHistory = $this->get_historical_ais_from_jarvis(
                        $imoNumber,
                        $historyFromIso,
                        $historyUntilIso
                    );

                    $ais_history = $this->normalize_jarvis_history($jarvisHistory);
                    $ais_history['imo_number'] = $imoNumber;
                    $ais_history['mmsi_number'] = $mmsiNumber;
                    $ais_history['history_from'] = $historyFromIso;
                    $ais_history['history_until'] = $historyUntilIso;
                }
            }
        }

        if ($hasil_lokal && method_exists($hasil_lokal, 'num_rows') && $hasil_lokal->num_rows() > 0) {
            foreach ($hasil_lokal->result() as $r) {
                $dpol  = $safe_json($r->dpol ?? '');
                $atp   = $safe_json($r->atp ?? '');
                $dft   = $safe_json($r->dft ?? '');
                $atd   = $safe_json($r->atd ?? '');
                $pickup_do_json = $safe_json($r->pickup_do ?? '');
                $cargo_delivery_json = $safe_json($r->cargo_delivery ?? '');

                $departur_from_pol   = $dpol['Actual Time of Departure (ATD)'] ?? '';
                $feeder_vessel       = $dpol['Vessel Name'] ?? '';
                $arrival_trans_time  = $atp['Arrival at Transhipment Port'] ?? '';
                $departur_trans_time = $dft['Departure from Transhipment Port'] ?? '';
                $container_no_local  = $dft['Container No'] ?? ($r->container_no ?? '');
                $arrival_des_time    = $atd['Actual Time of Arrival (ATA)'] ?? '';
                $arrival_vessel      = $atd['Vessel/Voyage'] ?? '';

                $pickup_do = '';
                if (is_array($pickup_do_json) && !empty($pickup_do_json['Pickup DO'])) {
                    $pickup_do = $pickup_do_json['Pickup DO'];
                }

                $cargo_delivery = '';
                if (is_array($cargo_delivery_json) && !empty($cargo_delivery_json)) {
                    $cargo_delivery = implode(', ', $cargo_delivery_json);
                }

                if (!$has_valid_gocomet && empty($header)) {
                    $head = [
                        'hbl'          => $r->hbl_no ?? $hbl,
                        'cbm'          => $volume,
                        'scac'         => !empty($r->mbl_no) ? substr($r->mbl_no, 0, 4) : $scac,
                        'container no' => $r->container_no ?? '',
                        'status'       => 'SUCCESS',
                        'last update'  => (!empty($r->updated_at) && $r->updated_at !== '0000-00-00 00:00:00') ? $r->updated_at : date('Y-m-d H:i:s'),
                        'delay'        => 'On Time',
                        'arrived'      => $is_past($arrival_des_time) ? 'Arrived' : 'On Journey',
                    ];
                    if (!empty($carrier))       $head['carrier'] = $carrier;
                    if (!empty($vessel_master)) $head['vessel'] = $vessel_master;
                    if (!empty($voyage_master)) $head['voyage'] = $voyage_master;
                    if (!empty($pol))           $head['pol'] = $pol;
                    if (!empty($pod))           $head['pod'] = $pod;
                    if (!empty($pot))           $head['pot'] = $pot;
                    if (!empty($mbl))           $head['mbl'] = $mbl;
					if (!empty($etd_fallback))           $head['etd'] = $etd_fallback;
                    if (!empty($eta_fallback))           $head['eta'] = $eta_fallback;
                    if (!empty($size))          $head['container_size'] = $size;
					if (!empty($pol_point))     $head['pol_point'] = $pol_point;
					if (!empty($pod_point))     $head['pod_point'] = $pod_point;
					if (!empty($pot_point))     $head['pot_point'] = $pot_point;
                    $header[] = [$head];
                }

                $localConnecting   = trim((string)($r->connecting ?? ''));
				$localDestination  = trim((string)($r->destination ?? ''));
				$localRegion       = trim((string)($r->region_id ?? ''));

				$atpTransPort = '';
				if (is_array($atp) && !empty($atp['Transhipment Port'])) {
					$atpTransPort = trim((string)$atp['Transhipment Port']);
				}

				// Prioritas nama transhipment port:
				// 1. atp.Transhipment Port
				// 2. r->connecting
				$transPort = !empty($atpTransPort) ? $atpTransPort : $localConnecting;

                $localOceanPod = trim((string)($r->consol_destination ?? ''));

				if ($localOceanPod === '' && !empty($consol['consol_destination'] ?? '')) {
					$localOceanPod = trim((string)$consol['consol_destination']);
				}

				if ($localOceanPod === '' && !empty($consol['destination'] ?? '')) {
					$localOceanPod = trim((string)$consol['destination']);
				}

				/*
				 * IMPORTANT:
				 * Jangan pakai consol_destination/localOceanPod sebagai alias POD final.
				 * Untuk export transhipment, consol_destination sering berisi POT,
				 * contoh:
				 *   connecting=SINGAPORE
				 *   consol_destination=SINGAPORE
				 *   destination=PASIR GUDANG
				 *
				 * Kalau localOceanPod dimasukkan ke POD aliases,
				 * Singapore akan dianggap final destination.
				 */
				$transPortIsOceanPod = false;

				$finalPodAliasNames = array_filter([
					$localDestination,
					$consol['destination'] ?? '',
					$pod ?? '',
				]);

				$potAliasNames = array_filter([
					$transPort,
					$localConnecting,
					$localOceanPod,
					$consol['consol_destination'] ?? '',
					$consol['transhipment_port'] ?? '',
					$pot ?? '',
				]);

				if (!empty($transPort)) {
					$transPortIsOceanPod =
						$this->is_same_port_text($transPort, $localDestination) ||
						$this->route_port_matches_code_or_name($transPort, $pod ?? '', $finalPodAliasNames) ||
						$this->is_same_pot_pod_context(
							$transPort,
							$pod ?? '',
							$pot ?? '',
							$finalPodAliasNames,
							$potAliasNames
						);
				}

				// Jangan tentukan transhipment hanya dari destination != connecting.
				// Kalau ada Departure from Transhipment Port, berarti shipment punya leg 2.
				$hasTranshipment = (
                    !empty($transPort) &&
                    !$transPortIsOceanPod &&
                    (
                        !empty($arrival_trans_time) ||
                        !empty($departur_trans_time)
                    )
                );

				if ($hasTranshipment) {
					// =========================
					// LEG 1: POL -> POT
					// =========================
					if (!empty($departur_from_pol) && !empty($arrival_trans_time)) {
						$leg1 = [
							'port of loading'    => $localRegion,
							'time of departure'  => $is_past($departur_from_pol)
								? $departur_from_pol
								: ($departur_from_pol . ' (Predicted)'),
							'time of discharge'  => $is_past($arrival_trans_time)
								? $arrival_trans_time
								: ($arrival_trans_time . ' (Predicted)'),
							'port of discharge'  => $transPort,
							'vessel'             => $feeder_vessel,
							'container'          => !empty($r->container_no) ? $r->container_no : $container_no_local,
							'_source_name'       => 'local_export',
							'_source_priority'   => 100,
							'_route_leg_type'    => 'POL_TO_POT',
							'_route_leg_no'      => 1,
						];

						$leg1['_dedupe_key'] = md5(
							'local_export|1|' .
							strtoupper($leg1['port of loading']) . '|' .
							strtoupper($leg1['port of discharge']) . '|' .
							$leg1['time of departure'] . '|' .
							$leg1['time of discharge']
						);

						$push_unique($local_routes, $leg1);
					}

					// =========================
					// LEG 2: POT -> POD
					// =========================
					if (!empty($departur_trans_time) && !empty($arrival_des_time)) {
						$leg2 = [
							'port of loading'    => $transPort,
							'time of departure'  => $is_past($departur_trans_time)
								? $departur_trans_time
								: ($departur_trans_time . ' (Predicted)'),
							'time of discharge'  => $is_past($arrival_des_time)
								? $arrival_des_time
								: ($arrival_des_time . ' (Predicted)'),
							'port of discharge'  => $localDestination,
							'vessel'             => $arrival_vessel,
							'container'          => $container_no_local,
							'_source_name'       => 'local_export',
							'_source_priority'   => 100,
							'_route_leg_type'    => 'POT_TO_POD',
							'_route_leg_no'      => 2,
						];

						$leg2['_dedupe_key'] = md5(
							'local_export|2|' .
							strtoupper($leg2['port of loading']) . '|' .
							strtoupper($leg2['port of discharge']) . '|' .
							$leg2['time of departure'] . '|' .
							$leg2['time of discharge']
						);

						$push_unique($local_routes, $leg2);
					}
				} else {
					// =========================
					// DIRECT: POL -> POD
					// =========================
					if (!empty($departur_from_pol) && !empty($arrival_des_time)) {
						$directLeg = [
							'port of loading'    => $localRegion,
							'time of departure'  => $is_past($departur_from_pol)
								? $departur_from_pol
								: ($departur_from_pol . ' (Predicted)'),
							'time of discharge'  => $is_past($arrival_des_time)
								? $arrival_des_time
								: ($arrival_des_time . ' (Predicted)'),
							'port of discharge'  => $localDestination,
							'vessel'             => $feeder_vessel ?: $arrival_vessel,
							'container'          => !empty($r->container_no) ? $r->container_no : $container_no_local,
							'_source_name'       => 'local_export',
							'_source_priority'   => 100,
							'_route_leg_type'    => 'DIRECT',
							'_route_leg_no'      => 1,
						];

						$directLeg['_dedupe_key'] = md5(
							'local_export|direct|' .
							strtoupper($directLeg['port of loading']) . '|' .
							strtoupper($directLeg['port of discharge']) . '|' .
							$directLeg['time of departure'] . '|' .
							$directLeg['time of discharge']
						);

						$push_unique($local_routes, $directLeg);
					}
}

                if (!empty($departur_from_pol)) {
                    $push_unique($steppers, [
                        'location' => $r->region_id,
                        'location type' => 'Origin Departure',
                        'event name' => 'Departure',
                        'event time' => $departur_from_pol,
                    ]);
                }
				
				if (!empty($arrival_trans_time)) {
					if ($transPortIsOceanPod) {
						$push_unique($steppers, [
							'location' => !empty($localDestination) ? $localDestination : $r->connecting,
							'location type' => 'Arrival at Destination',
							'event name' => 'Arrival',
							'vessel' => $arrival_vessel,
							'container' => $container_no_local,
							'event time' => $arrival_trans_time,
						]);
					} else {
						$push_unique($steppers, [
							'location' => $r->connecting,
							'location type' => 'Trans Shipment Arrival',
							'event name' => 'Arrival',
							'event time' => $arrival_trans_time,
						]);
					}
				}

                if (!empty($departur_trans_time)) {
                    $push_unique($steppers, [
                        'location' => $r->connecting,
                        'location type' => 'Trans Shipment Departure',
                        'event name' => 'Departure',
                        'event time' => $departur_trans_time,
                    ]);
                }

                if (!empty($arrival_des_time)) {
                    $push_unique($steppers, [
                        'location' => $r->destination,
                        'location type' => 'Arrival at Destination',
                        'event name' => 'Arrival',
                        'vessel' => $arrival_vessel,
                        'container' => $container_no_local,
                        'event time' => $arrival_des_time,
                    ]);
                }

                if (!empty($departur_from_pol)) {
                    $push_unique($table, [
                        'event name' => 'Departure from origin port',
                        'port name' => $r->region_id,
                        'location name' => 'Departure from origin port',
                        'event time' => $departur_from_pol,
                        '_source_name' => 'local_export',
                        '_source_priority' => 50,
                    ]);
                }

                if (!empty($arrival_trans_time)) {
					if ($transPortIsOceanPod) {
						$push_unique($table, [
							'event name' => 'Arrival at final port of destination',
							'port name' => !empty($localDestination) ? $localDestination : $r->connecting,
							'location name' => 'Arrival',
							'event time' => $arrival_trans_time,
							'_source_name' => 'local_export',
							'_source_priority' => 60,
						]);
					} else {
						$push_unique($table, [
							'event name' => 'Arrival at transhipment port',
							'port name' => !empty($transPort) ? $transPort : $r->connecting,
							'location name' => 'Arrival at transhipment port',
							'event time' => $arrival_trans_time,
							'container' => !empty($r->container_no) ? $r->container_no : $container_no_local,
							'_event_scope' => 'transshipment_arrival',
							'_source_name' => 'local_export',
							'_source_priority' => 65,
						]);
					}
				}

                if (!empty($departur_trans_time)) {
					$push_unique($table, [
						'event name' => 'Departure from transhipment port',
						'port name' => !empty($transPort) ? $transPort : $r->connecting,
						'location name' => 'Transshipment Departure',
						'event time' => $departur_trans_time,

						// Tambahan supaya FE bisa render card kapal leg 2
						'vessel' => !empty($arrival_vessel) ? $arrival_vessel : '',
						'container' => !empty($container_no_local)
							? $container_no_local
							: (!empty($r->container_no) ? $r->container_no : ''),

						'_source_name' => 'local_export',
						'_source_priority' => 50,
					]);
				}

                // =====================================================
                // LOCAL EXPORT FINAL POD ETA/ATA
                // Case classic:
                // - Direct shipment POL -> POD
                // - atd JSON sudah punya Actual Time of Arrival (ATA)
                // - tapi tidak ada departur_trans_time karena memang tidak transhipment
                // Sebelumnya final arrival hanya dipush jika ada departur_trans_time,
                // sehingga event list berhenti di Origin Departure.
                // =====================================================
                $shouldPushFinalArrivalFromLocalAtd = false;

				if (!empty($arrival_des_time)) {
					if ($hasTranshipment) {
						$shouldPushFinalArrivalFromLocalAtd = !empty($departur_trans_time);
					} else {
						$shouldPushFinalArrivalFromLocalAtd = true;
					}
				}

				if ($shouldPushFinalArrivalFromLocalAtd) {
					$arrival_des_event_time = $is_past($arrival_des_time)
						? $arrival_des_time
						: ($arrival_des_time . ' (Predicted)');

					$push_unique($table, [
						'event name' => $is_past($arrival_des_time)
							? 'Arrival at final port of destination'
							: 'Predicted arrival at final port of destination',
						'port name' => !empty($localDestination) ? $localDestination : ($r->destination ?? ''),
						'location name' => 'Arrival',
						'event time' => $arrival_des_event_time,
						'vessel' => !empty($arrival_vessel) ? $arrival_vessel : '',
						'container' => !empty($container_no_local)
							? $container_no_local
							: (!empty($r->container_no) ? $r->container_no : ''),
						'_event_scope' => 'final_pod_arrival',
						'_source_name' => 'local_export',
						'_source_priority' => 75,
					]);
				}

                if (!empty($pickup_do)) {
                    $push_unique($table, [
                        'event name' => 'Pickup DO',
                        'port name' => $r->destination,
                        'location name' => 'Documentation',
                        'event time' => $pickup_do,
                        '_source_name' => 'local_export',
                        '_source_priority' => 50,
                    ]);
                }

                if (!empty($cargo_delivery)) {
                    $push_unique($table, [
                        'event name' => 'Release Cargo',
                        'port name' => $r->destination,
                        'location name' => 'Deliver',
                        'event time' => $cargo_delivery,
                        '_source_name' => 'local_export',
                        '_source_priority' => 50,
                    ]);
                }
            }
        }

        if ($hasil_gudang && method_exists($hasil_gudang, 'num_rows') && $hasil_gudang->num_rows() > 0) {
            foreach ($hasil_gudang->result() as $row) {
                if (!empty($row->booking_time)) {
                    $push_unique($table, [
                        'event name' => 'Booking Confirmation',
                        'port name' => $row->region_id,
                        'location name' => 'Booking Confirmed',
                        'event time' => date('d/m/Y H:i:s', strtotime($row->booking_time)),
                        '_source_name' => 'warehouse',
                        '_source_priority' => 40,
                    ]);
                }

				if (!empty($row->received_status) && !empty($row->received_date)) {
					
					// 1. Panggil fungsi foto menggunakan $row->booking_code
					$arrival_photos = [];
					if (!empty($row->booking_code)) {
						$arrival_photos = $get_cargo_arrival_photos($row->booking_code);
					}

					// 2. Masukkan ke dalam timeline dengan key 'images'
					$push_unique($table, [
						'event name' => 'Cargo Arrival Confirmation at CFS',
						'port name' => 'GCL Warehouse',
						'location name' => 'Cargo Collection',
						'event time' => date('d/m/Y H:i:s', strtotime($row->received_date)),
						'images' => $arrival_photos, // <--- FOTO DISISIPKAN DI SINI
						'_source_name' => 'warehouse',
						'_source_priority' => 40,
					]);
				}

                if (!empty($stuffing_date) && stripos($type_shipment, 'import') === false) {
                    $push_unique($table, [
                        'event name' => 'Cargo Stuffing in Progress',
                        'port name' => (stripos($type_shipment, 'FCL') !== false) ? "Shipper's Warehouse" : 'GCL Warehouse',
                        'location name' => (stripos($type_shipment, 'FCL') !== false) ? 'Loading goods into container' : 'Consolidation',
                        'event time' => date('d/m/Y H:i:s', strtotime($stuffing_date)),
                        '_source_name' => 'warehouse',
                        '_source_priority' => 40,
                    ]);
                }

                if ($closing_datetime instanceof DateTime) {
                    $push_unique($table, [
                        'event name' => 'Moving to Container Yard',
                        'port name' => 'GCL Warehouse',
                        'location name' => 'Container Sealed',
                        'event time' => $closing_datetime->format('d/m/Y H:i:s'),
                        '_source_name' => 'warehouse',
                        '_source_priority' => 40,
                    ]);
                }
            }
        }


        // =====================================================
        // BINTANG WAREHOUSE PROGRESS EVENTS - IMPORT
        // Source ini diproses sebelum $hasil_lokal_import dan memakai priority 90,
        // sehingga event gudang dari API Bintang menang di atas local_import (50).
        // =====================================================
        // Vendor selection is based on the local import record, not on a blind API search.
        $isAgungRaya = false;
        $rayaHbls = [];
        $rayaSummary = null;
        if ($hasil_lokal_import && method_exists($hasil_lokal_import, 'num_rows') && $hasil_lokal_import->num_rows() > 0) {
            foreach ($hasil_lokal_import->result() as $rowImp) {
                if (!$this->is_agung_raya_warehouse($rowImp->warehouse ?? '')) continue;
                $isAgungRaya = true;
                $houseBl = trim((string)($rowImp->hbl_no ?? ''));
                if ($houseBl === '') $houseBl = trim((string)($rowImp->house_bl_no ?? ''));
                if ($houseBl !== '') $rayaHbls[strtoupper($houseBl)] = $houseBl;
            }
        }
        if ($isAgungRaya) {
            // Prefer exact search HBL, otherwise use a single unambiguous local HBL.
            $rayaHbl = $rayaHbls[strtoupper($si_number)] ?? '';
            if ($rayaHbl === '' && count($rayaHbls) === 1) $rayaHbl = reset($rayaHbls);
            if ($rayaHbl !== '') {
                $rayaSummary = $this->normalize_agung_raya_hbl($this->fetch_agung_raya_hbl($rayaHbl), $rayaHbl);
                if ($rayaSummary) {
                    foreach ($rayaSummary['timeline'] as $tl) {
                        $event = [
                            'event name' => $tl['event_name'],
                            'port name' => 'Agung Raya',
                            'location name' => $tl['location_name'],
                            'event time' => $tl['event_time'],
                            'warehouse' => 'Agung Raya',
                            'badge_status' => $tl['badge_status'],
                            'images' => [],
                            '_source_name' => 'agung_raya_import',
                            '_source_priority' => 90,
                        ];
                        $push_unique($table, $event);
                        $table_import[] = $event;
                    }
                }
            } else {
                log_message('error', 'AGUNG_RAYA: missing or ambiguous local HBL; vendor request skipped');
            }
        }
        $rayaHasStripping = $rayaSummary && in_array('Stripping Cargo', array_column($rayaSummary['timeline'], 'event_name'), true);
        $rayaHasRelease = $rayaSummary && $rayaSummary['released_items'] > 0;

        $bintangCandidates = [];

        foreach ([
            $si_number ?? '',
            $hbl ?? '',
            $do_number ?? '',
            $consol['hbl'] ?? '',
            $consol['mbl'] ?? '',
        ] as $candidate) {
            $candidate = trim((string)$candidate);
            if ($candidate !== '') {
                $bintangCandidates[] = $candidate;
            }
        }

        if ($hasil_lokal_import && method_exists($hasil_lokal_import, 'num_rows') && $hasil_lokal_import->num_rows() > 0) {
            foreach ($hasil_lokal_import->result() as $rowImp) {
                foreach ([
                    $rowImp->hbl_no ?? '',
                    $rowImp->no_bl ?? '',
                    $rowImp->bl_no ?? '',
                    $rowImp->house_bl_no ?? '',
                    $rowImp->mbl_no ?? '',
                    $rowImp->master_bl_no ?? '',
                    $rowImp->do_number ?? '',
                ] as $candidate) {
                    $candidate = trim((string)$candidate);
                    if ($candidate !== '') {
                        $bintangCandidates[] = $candidate;
                    }
                }
            }
        }

        $bintangCandidates = array_values(array_unique(array_filter($bintangCandidates)));
        $bintangCandidates = array_slice($bintangCandidates, 0, 8);

        foreach ($isAgungRaya ? [] : $bintangCandidates as $candidateNoBl) {
            $tmpBintang = $this->fetch_bintang_progress_bl($candidateNoBl);

            if ($this->is_valid_bintang_progress($tmpBintang)) {
                $bintang_progress = $tmpBintang;

                log_message('error', 'DEBUG BINTANG_PROGRESS_FOUND=' . json_encode([
                    'si_number' => $si_number,
                    'queried_no_bl' => $candidateNoBl,
                    'manifest_mbl' => $tmpBintang['manifest']['no_master_bl'] ?? '',
                    'details_no_bl' => $tmpBintang['details']['no_bl'] ?? '',
                    'timeline_count' => isset($tmpBintang['timeline']) && is_array($tmpBintang['timeline'])
                        ? count($tmpBintang['timeline'])
                        : 0,
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

                break;
            }
        }

        if ($this->is_valid_bintang_progress($bintang_progress)) {
            $bm = $bintang_progress['manifest'] ?? [];
            $bd = $bintang_progress['details'] ?? [];
            $bt = isset($bintang_progress['timeline']) && is_array($bintang_progress['timeline'])
                ? $bintang_progress['timeline']
                : [];

            $bNoBl       = trim((string)($bd['no_bl'] ?? ($bintang_progress['_queried_no_bl'] ?? $si_number)));
            $bMbl        = trim((string)($bm['no_master_bl'] ?? ''));
            $bVessel     = trim((string)($bm['vessel_name'] ?? ''));
            $bVoyage     = trim((string)($bm['no_voyage'] ?? ''));
            $bOriginName = trim((string)($bm['port_of_origin'] ?? ''));
            $bOriginCode = trim((string)($bm['port_of_origin_code'] ?? ''));
            $bDestName   = trim((string)($bm['port_of_destination'] ?? ''));
            $bDestCode   = trim((string)($bm['port_of_destination_code'] ?? ''));
            $bMblDate    = trim((string)($bm['master_bl_date'] ?? ''));
            $bArrival    = trim((string)($bm['arrival_date'] ?? ''));

            $bWarehouse  = trim((string)($bd['warehouse'] ?? ''));
            $bContainer  = trim((string)($bd['no_container'] ?? ''));
            $bSize       = trim((string)($bd['container_size'] ?? ''));
            $bVolume     = trim((string)($bd['volume'] ?? ''));
            $bPackage    = trim((string)($bd['package'] ?? ''));
            $bUnit       = trim((string)($bd['unit'] ?? ''));

            $bLatest = $this->get_latest_bintang_status($bt);
            $bLatestStatus = is_array($bLatest) ? trim((string)($bLatest['status_name'] ?? '')) : '';
            $bLatestTime   = is_array($bLatest) ? $this->format_bintang_datetime($bLatest['update_date'] ?? $bLatest['updated_at'] ?? '') : '';
            $bDocuments = $this->normalize_bintang_documents($bintang_progress['documents'] ?? []);

            // Override import POL/POD dari manifest Bintang.
            if ($bOriginCode !== '') {
                $pol = $bOriginCode;
                $p = $this->get_point_by_code($bOriginCode);
                if (!empty($p)) $pol_point = $p;
            }

            if ($bDestCode !== '') {
                $pod = $bDestCode;
                $p = $this->get_point_by_code($bDestCode);
                if (!empty($p)) $pod_point = $p;
            }

            $pot = '';
            $pot_point = null;

            if ($bVessel !== '') $vessel_master = $bVessel;
            if ($bVoyage !== '') $voyage_master = $bVoyage;
            if ($bMbl !== '')    $mbl = $bMbl;

            if ($bVolume !== '') {
                $volume = $bVolume;
                $cbm = is_numeric($bVolume) ? (float)$bVolume : $cbm;
            }

            if ($bSize !== '') {
                $size = $bSize . 'FT';
            }

            $bHeaderStatus = (strtolower($bLatestStatus) === 'barang keluar') ? 'Completed' : 'On Progress';
            $bVesselVoyage = trim($bVessel . ' ' . $bVoyage);

            if (empty($header)) {
                $header[] = [[
                    'hbl'              => $bNoBl ?: $hbl,
                    'cbm'              => $bVolume ?: $volume,
                    'scac'             => $bMbl !== '' ? substr($bMbl, 0, 4) : $scac,
                    'container no'     => $bContainer,
                    'status'           => $bHeaderStatus,
                    'last update'      => $bLatestTime ?: date('Y-m-d H:i:s'),
                    'delay'            => 'On Time',
                    'arrived'          => (strtolower($bLatestStatus) === 'barang keluar') ? 'Cargo Released' : $bLatestStatus,
                    'carrier'          => $carrier,
                    'vessel'           => $bVessel ?: $vessel_master,
                    'voyage'           => $bVoyage ?: $voyage_master,
                    'pol'              => $bOriginCode ?: $pol,
                    'pod'              => $bDestCode ?: $pod,
                    'mbl'              => $bMbl ?: $mbl,
                    'etd'              => $this->format_bintang_datetime($bMblDate),
                    'eta'              => $this->format_bintang_datetime($bArrival),
                    'container_size'   => $bSize !== '' ? ($bSize . 'FT') : $size,
                    'pol_point'        => $pol_point,
                    'pod_point'        => $pod_point,
                    'warehouse'        => $bWarehouse,
                    'warehouse_status' => $bLatestStatus,
                    'containers'       => $bContainer !== '' ? [$bContainer] : [],
                ]];
            } else {
                if (!empty($header[0][0]) && is_array($header[0][0])) {
                    $header[0][0]['warehouse'] = $bWarehouse;
                    $header[0][0]['warehouse_status'] = $bLatestStatus;

                    if ($bLatestTime !== '') $header[0][0]['last update'] = $bLatestTime;
                    if ($bHeaderStatus === 'Completed') {
                        $header[0][0]['status'] = 'Completed';
                        $header[0][0]['arrived'] = 'Cargo Released';
                    }

                    if ($bContainer !== '') {
                        $header[0][0]['container no'] = $bContainer;
                        $header[0][0]['containers'] = [$bContainer];
                    }

                    if ($bMbl !== '') $header[0][0]['mbl'] = $bMbl;
                    if ($bVessel !== '') $header[0][0]['vessel'] = $bVessel;
                    if ($bVoyage !== '') $header[0][0]['voyage'] = $bVoyage;
                    if ($bOriginCode !== '') $header[0][0]['pol'] = $bOriginCode;
                    if ($bDestCode !== '') $header[0][0]['pod'] = $bDestCode;
                    if (!empty($pol_point)) $header[0][0]['pol_point'] = $pol_point;
                    if (!empty($pod_point)) $header[0][0]['pod_point'] = $pod_point;
                }
            }

            // Routing manifest Bintang: priority 90, di atas local_import.
            if ($bOriginName !== '' && $bDestName !== '' && $bVesselVoyage !== '') {
                $bRoute = [
                    'vessel'            => $bVesselVoyage,
                    'port of loading'   => $bOriginName . ($bOriginCode !== '' ? ', ' . $bOriginCode : ''),
                    'time of departure' => $this->format_bintang_datetime($bMblDate),
                    'time of discharge' => $this->format_bintang_datetime($bArrival),
                    'port of discharge' => $bDestName . ($bDestCode !== '' ? ', ' . $bDestCode : ''),
                    'container'         => $bContainer,
                    '_source_name'      => 'bintang_import',
                    '_source_priority'  => 90,
                    '_route_leg_type'   => 'IMPORT_DIRECT',
                    '_route_leg_no'     => 1,
                ];

                $bRoute['_dedupe_key'] = md5(
                    'bintang_import|' .
                    strtoupper($bRoute['port of loading']) . '|' .
                    strtoupper($bRoute['port of discharge']) . '|' .
                    $bRoute['time of departure'] . '|' .
                    $bRoute['time of discharge']
                );

                $push_unique($local_routes, $bRoute);
            }

            // Ocean events dari manifest Bintang.
            if ($bMblDate !== '') {
                $eventTime = $this->format_bintang_datetime($bMblDate);
                $push_unique($table, [
                    'event name' => 'Shipment Departed From Origin Port',
                    'port name' => $bOriginName ?: $bOriginCode,
                    'location name' => 'Origin Departure',
                    'event time' => $eventTime,
                    'vessel' => $bVesselVoyage,
                    'container' => $bContainer,
                    '_source_name' => 'bintang_import',
                    '_source_priority' => 90,
                ]);

                $push_unique($steppers, [
                    'location' => $bOriginName . ($bOriginCode !== '' ? ', ' . $bOriginCode : ''),
                    'location type' => 'Origin Departure',
                    'event name' => 'OCEAN',
                    'vessel' => $bVesselVoyage,
                    'container' => $bContainer,
                    'event time' => $eventTime,
                ]);
            }

            if ($bArrival !== '') {
                $eventTime = $this->format_bintang_datetime($bArrival);
                $push_unique($table, [
                    'event name' => 'Shipment Arrived At Destination Port',
                    'port name' => $bDestName ?: $bDestCode,
                    'location name' => 'Arrival',
                    'event time' => $eventTime,
                    'vessel' => $bVesselVoyage,
                    'container' => $bContainer,
                    '_source_name' => 'bintang_import',
                    '_source_priority' => 90,
                ]);

                $push_unique($steppers, [
                    'location' => $bDestName . ($bDestCode !== '' ? ', ' . $bDestCode : ''),
                    'location type' => 'Arrival',
                    'event name' => 'OCEAN',
                    'vessel' => $bVesselVoyage,
                    'container' => $bContainer,
                    'event time' => $eventTime,
                ]);
            }

            $normalizedBintangTimeline = [];

            foreach ($bt as $tl) {
                if (!is_array($tl)) continue;

                $statusName = trim((string)($tl['status_name'] ?? ''));
                $eventTime  = $this->format_bintang_datetime($tl['update_date'] ?? $tl['updated_at'] ?? $tl['created_at'] ?? '');

                if ($statusName === '' || $eventTime === '') {
                    continue;
                }

                $mapped = $this->map_bintang_status_event($statusName);
                $images = $this->normalize_bintang_images($tl['images'] ?? []);

                $portName = $bWarehouse !== ''
                    ? $bWarehouse
                    : ($bDestName ?: ($bDestCode ?: 'Warehouse'));

                if (strtolower($statusName) === 'overbrengen') {
                    $portName = $bDestName ?: ($bDestCode ?: $portName);
                }

                $event = [
                    'event name' => $mapped['event_name'],
                    'port name' => $portName,
                    'location name' => $mapped['location_name'],
                    'event time' => $eventTime,
                    'warehouse' => $bWarehouse,
                    'badge_status' => $tl['badge_status'] ?? '',
                    'notes' => $tl['notes'] ?? '',
                    'images' => $images,
                    '_source_name' => 'bintang_import',
                    '_source_priority' => 90,
                ];

                $push_unique($table, $event);
                $table_import[] = $event;

                $normalizedBintangTimeline[] = [
                    'id' => $tl['id'] ?? null,
                    'status_name' => $statusName,
                    'event_name' => $mapped['event_name'],
                    'location_name' => $mapped['location_name'],
                    'event_time' => $eventTime,
                    'updated_by' => $tl['updated_by'] ?? '',
                    'badge_status' => $tl['badge_status'] ?? '',
                    'notes' => $tl['notes'] ?? '',
                    'images' => $images,
                ];
            }

            $bintang_tracking_summary = [
                'source' => 'bintanglogistikindonusa',
                'queried_no_bl' => $bintang_progress['_queried_no_bl'] ?? '',
                'no_bl' => $bNoBl,
                'warehouse' => $bWarehouse,
                'latest_status' => $bLatestStatus,
                'latest_update' => $bLatestTime,
                'details' => [
                    'forwarder' => $bd['forwarder'] ?? '',
                    'consignee' => $bd['consignee'] ?? '',
                    'no_container' => $bContainer,
                    'container_size' => $bSize,
                    'package' => $bPackage,
                    'unit' => $bUnit,
                    'volume' => $bVolume,
                    'bruto' => $bd['bruto'] ?? '',
                    'description' => $bd['description'] ?? '',
                ],
                'manifest' => [
                    'no_master_bl' => $bMbl,
                    'vessel_name' => $bVessel,
                    'no_voyage' => $bVoyage,
                    'port_of_origin' => $bOriginName,
                    'port_of_origin_code' => $bOriginCode,
                    'port_of_destination' => $bDestName,
                    'port_of_destination_code' => $bDestCode,
                    'master_bl_date' => $bMblDate,
                    'arrival_date' => $bArrival,
                ],
                'timeline' => $normalizedBintangTimeline,
                'documents' => $bDocuments,
            ];

            log_message('error', 'DEBUG BINTANG_PROGRESS_APPLIED=' . json_encode($bintang_tracking_summary, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        }

        if ($hasil_lokal_import && method_exists($hasil_lokal_import, 'num_rows') && $hasil_lokal_import->num_rows() > 0) {
            foreach ($hasil_lokal_import->result() as $row) {

                if (!$rayaHasStripping && !empty($row->stripping_date) && $row->stripping_date !== '0000-00-00 00:00:00' && $row->stripping_date !== '0000-00-00') {
                    $dt = $parse_dt($row->stripping_date);
                    if ($dt) {
                        $event = [
                            'job_number' => $row->no_job,
                            'party' => $row->party,
                            'event name' => 'Stripping Cargo',
                            'port name' => $row->port_of_destination ?? $row->region_id,
                            'location name' => 'Unloading Cargo at ' . trim(($row->warehouse ?? '')) . ' (CFS)',
                            'event time' => $dt->format('d/m/Y'),
                            '_source_name' => 'local_import',
                            '_source_priority' => 50,
                        ];
                        $push_unique($table, $event);
                        $table_import[] = $event;
                    }
                }

                if (!empty($row->atd) && $row->atd !== '0000-00-00 00:00:00' && $row->atd !== '0000-00-00') {
                    $push_unique($table, [
                        'event name' => 'Shipment Departed From Origin Port',
                        'port name' => $row->destination,
                        'location name' => 'Origin Departure',
                        'event time' => $row->atd,
						 // internal only untuk dedupe
						'_source_name' => 'local_import',
						'_source_priority' => 50,
                    ]);
                }

                if (!empty($row->ata) && $row->ata !== '0000-00-00 00:00:00' && $row->ata !== '0000-00-00') {
                    $push_unique($table, [
                        'event name' => 'Shipment Arrived At Destination Port',
                        'port name' => $row->port_of_destination,
                        'location name' => 'Arrival',
                        'event time' => $row->ata,
                        '_source_name' => 'local_import',
                        '_source_priority' => 50,
                    ]);
                }

                if (!empty($row->pickup_do)) {
                    $dt = $parse_dt($row->pickup_do);
                    if ($dt) {
                        $event = [
                            'job_number' => $row->no_job,
                            'party' => $row->party,
                            'event name' => 'Pickup DO',
                            'port name' => $row->port_of_destination ?? $row->region_id,
                            'location name' => 'Documentation',
                            'event time' => $dt->format('d/m/Y'),
                            '_source_name' => 'local_import',
                            '_source_priority' => 50,
                        ];
                        $push_unique($table, $event);
                        $table_import[] = $event;
                    }
                }

                if (!$rayaHasRelease && !empty($row->pickup_cargo)) {
                    $dt = $parse_dt($row->pickup_cargo);
                    if ($dt) {
                        $event = [
                            'job_number' => $row->no_job,
                            'party' => $row->party,
                            'event name' => 'Release Cargo',
                            'port name' => $row->port_of_destination ?? $row->region_id,
                            'location name' => 'Final Delivery',
                            'event time' => $dt->format('d/m/Y'),
                            '_source_name' => 'local_import',
                            '_source_priority' => 50,
                        ];
                        $push_unique($table, $event);
                        $table_import[] = $event;
                    }
                }

                if (empty($header)) {
                    $arrival_des_time = $row->ata ?? '';
                    $header[] = [[
                        'hbl' => $row->hbl_no ?? $hbl,
                        'cbm' => $volume,
                        'scac' => $row->mbl_no ?? $scac,
                        'container no' => $row->container_no ?? '',
                        'status' => 'SUCCESS',
                        'last update' => $row->updated_at ?? date('Y-m-d H:i:s'),
                        'delay' => 'On Time',
                        'arrived' => $is_past($arrival_des_time) ? 'Arrived' : 'On Journey',
                    ]];
                }
            }
        }
		
        if ($isAgungRaya && isset($header[0][0]) && is_array($header[0][0])) {
            $header[0][0]['warehouse'] = 'Agung Raya';
            if ($rayaSummary && $rayaSummary['latest_status'] !== '') {
                $header[0][0]['warehouse_status'] = $rayaSummary['latest_status'];
                if ($this->normalize_event_timestamp($rayaSummary['latest_update']) > $this->normalize_event_timestamp($header[0][0]['last update'] ?? '')) {
                    $header[0][0]['last update'] = $rayaSummary['latest_update'];
                }
            }
            // Do not change ocean route, POD, ETA, or AIS on the basis of warehouse data.
        }

		// =====================================================
		// FINAL AIS FALLBACK FOR LOCAL-ONLY ROUTING
		// Jika GoComet tidak punya AIS/current/history,
		// tetap coba ambil dari Jarvis berdasarkan vessel master/local.
		// =====================================================
		if (
			empty($ais_history) ||
			(is_array($ais_history) && empty($ais_history['tracks']))
		) {
			$localVesselName = trim((string)$vessel_master);

			if (empty($localVesselName) && !empty($header[0][0]['vessel'])) {
				$localVesselName = trim((string)$header[0][0]['vessel']);
			}

			$localImo = isset($imoNumber) ? trim((string)$imoNumber) : '';
			$localMmsi = isset($mmsiNumber) ? trim((string)$mmsiNumber) : '';

			// Cari ke master vessel_imo jika IMO/MMSI belum ada
			if ((empty($localImo) || empty($localMmsi)) && !empty($localVesselName)) {
				$masterVessel = $this->get_vessel_imo_master($localVesselName);

				if (!empty($masterVessel)) {
					if (empty($localImo) && !empty($masterVessel['imo'])) {
						$localImo = trim((string)$masterVessel['imo']);
					}

					if (empty($localMmsi) && !empty($masterVessel['mmsi'])) {
						$localMmsi = trim((string)$masterVessel['mmsi']);
					}
				}
			}

			// Pakai ETD/ETA dari database fallback kalau tidak ada dari GoComet
			$localEtdDt = null;
			$localEtaDt = null;

			if (isset($etdDt) && $etdDt instanceof DateTime) {
				$localEtdDt = clone $etdDt;
			} elseif ($etd_db instanceof DateTime) {
				$localEtdDt = clone $etd_db;
			}

			if (isset($etaDt) && $etaDt instanceof DateTime) {
				$localEtaDt = clone $etaDt;
			} elseif ($eta_db instanceof DateTime) {
				$localEtaDt = clone $eta_db;
			}

			if (
					$includeAis &&
				!empty($localImo) &&
				$localEtdDt instanceof DateTime &&
				$localEtaDt instanceof DateTime
			) {
				$historyFromIso = clone $localEtdDt;
				$historyUntilIso = clone $localEtaDt;

				$historyFromIso = $historyFromIso
					->setTimezone(new DateTimeZone('UTC'))
					->format('Y-m-d\TH:i:s\Z');

				$historyUntilIso = $historyUntilIso
					->setTimezone(new DateTimeZone('UTC'))
					->format('Y-m-d\TH:i:s\Z');

				// 1. Coba endpoint historical-tracks by IMO
				$jarvisHistory = $this->get_historical_ais_from_jarvis(
					$localImo,
					$historyFromIso,
					$historyUntilIso
				);

				$localAisHistory = $this->normalize_jarvis_history($jarvisHistory);
				$localAisHistory['imo_number'] = $localImo;
				$localAisHistory['mmsi_number'] = $localMmsi ?: null;
				$localAisHistory['history_from'] = $historyFromIso;
				$localAisHistory['history_until'] = $historyUntilIso;

				if (!empty($localAisHistory['tracks'])) {
					$ais_history = $localAisHistory;
				}
			}

			// 2. Kalau historical-tracks kosong, coba history-snapshots
			if (
					$includeAis &&
				(
					empty($ais_history) ||
					(is_array($ais_history) && empty($ais_history['tracks']))
				) &&
				(!empty($localMmsi) || !empty($localImo) || !empty($localVesselName))
			) {
				$snapshotHistory = [];

				if (!empty($localMmsi)) {
					$snapshotHistory = $this->fetch_jarvis(
						'https://api-gw.jar-vis.com/jarvis/api/v1/navapi/vessels/history-snapshots?mmsi=' .
						rawurlencode($localMmsi)
					);
				}

				if (empty($snapshotHistory) && !empty($localImo)) {
					$snapshotHistory = $this->fetch_jarvis(
						'https://api-gw.jar-vis.com/jarvis/api/v1/navapi/vessels/history-snapshots?imo=' .
						rawurlencode($localImo)
					);
				}

				if (empty($snapshotHistory) && !empty($localVesselName)) {
					$snapshotHistory = $this->fetch_jarvis(
						'https://api-gw.jar-vis.com/jarvis/api/v1/navapi/vessels/history-snapshots?name=' .
						rawurlencode($localVesselName)
					);
				}

				if (!empty($snapshotHistory)) {
					$snapshotAisHistory = $this->normalize_jarvis_history($snapshotHistory);

					if (!empty($snapshotAisHistory['tracks'])) {
						$snapshotAisHistory['source'] = 'jarvis_history_snapshots';
						$snapshotAisHistory['imo_number'] = $localImo ?: null;
						$snapshotAisHistory['mmsi_number'] = $localMmsi ?: null;

						if ($localEtdDt instanceof DateTime) {
							$snapshotAisHistory['history_from'] = (clone $localEtdDt)
								->setTimezone(new DateTimeZone('UTC'))
								->format('Y-m-d\TH:i:s\Z');
						}

						if ($localEtaDt instanceof DateTime) {
							$snapshotAisHistory['history_until'] = (clone $localEtaDt)
								->setTimezone(new DateTimeZone('UTC'))
								->format('Y-m-d\TH:i:s\Z');
						}

						$ais_history = $snapshotAisHistory;
					}
				}
			}
		}

        $table = array_values(array_filter($table, function ($row) {
            $eventTime = trim((string)($row['event time'] ?? ''));
            return $eventTime !== '';
        }));

        $fixed_last = [];
        $normal_table = [];

        foreach ($table as $row) {
            $eventName = strtolower(trim((string)($row['event name'] ?? '')));

            if (in_array($eventName, ['pickup do', 'release cargo'], true)) {
                $fixed_last[] = $row;
            } else {
                $normal_table[] = $row;
            }
        }

        usort($normal_table, function ($a, $b) {
            $ta = $this->normalize_event_timestamp($a['event time'] ?? '');
            $tb = $this->normalize_event_timestamp($b['event time'] ?? '');

            if ($ta == $tb) return 0;
            return ($ta < $tb) ? -1 : 1;
        });

        usort($fixed_last, function ($a, $b) {
            $priority = [
                'pickup do' => 1,
                'release cargo' => 2,
            ];

            $ea = strtolower(trim((string)($a['event name'] ?? '')));
            $eb = strtolower(trim((string)($b['event name'] ?? '')));

            $pa = $priority[$ea] ?? 99;
            $pb = $priority[$eb] ?? 99;

            if ($pa == $pb) {
                $ta = $this->normalize_event_timestamp($a['event time'] ?? '');
                $tb = $this->normalize_event_timestamp($b['event time'] ?? '');

                if ($ta == $tb) return 0;
                return ($ta < $tb) ? -1 : 1;
            }

            return ($pa < $pb) ? -1 : 1;
        });

        $table = array_merge($normal_table, $fixed_last);

        // 5.x filter stepper: buang yang event time kosong
		$steppers = array_values(array_filter($steppers, function ($row) {
			$eventTime = trim((string)($row['event time'] ?? ''));
			return $eventTime !== '';
		}));
		
		// 5.x dedupe stepper untuk multi-container + local-vs-GoComet.
		// Local origin departure: location=Jakarta, event=Departure.
		// GoComet origin departure: location=Jakarta, Indonesia, IDJKT, event=OCEAN.
		// Keduanya harus dianggap satu stage; pilih GoComet/actual yang lebih informatif.
		$deduped_steppers = [];
		$seen_steppers = [];

		$stepper_score = function ($row) {
			$event     = strtolower(trim((string)($row['event name'] ?? '')));
			$locType   = strtolower(trim((string)($row['location type'] ?? '')));
			$vessel    = trim((string)($row['vessel'] ?? ''));
			$eventTime = trim((string)($row['event time'] ?? ''));

			$score = 0;
			if ($event === 'ocean') $score += 100;
			if ($vessel !== '') $score += 50;
			if (strpos($locType, 'origin departure') !== false) $score += 20;
			if (preg_match('/\d{2}\/\d{2}\/\d{4}\s+\d{2}:\d{2}/', $eventTime)) $score += 10;

			return $score;
		};

		foreach ($steppers as $row) {
			$location = strtolower(trim((string)($row['location'] ?? '')));
			$locType  = strtolower(trim((string)($row['location type'] ?? '')));
			$event    = strtolower(trim((string)($row['event name'] ?? '')));
			$vessel   = strtolower(trim((string)($row['vessel'] ?? '')));

			$stageKey = $this->normalize_tracking_event_key($event, $locType);

			// Origin departure hanya satu per shipment. Jangan pakai location/vessel,
			// karena local dan GoComet menulis format berbeda.
			if ($stageKey === 'origin_departure') {
				$key = 'stepper_origin_departure';
			} else {
				// Jangan pakai container sebagai key, supaya multi-container tidak dobel.
				$key = md5($location . '|' . $locType . '|' . $event . '|' . $vessel);
			}

			$row['container'] = implode(' / ', array_values(array_unique($container_list)));

			if (!isset($seen_steppers[$key])) {
				$seen_steppers[$key] = count($deduped_steppers);
				$deduped_steppers[] = $row;
				continue;
			}

			$idx = $seen_steppers[$key];
			$existing = $deduped_steppers[$idx];

			$oldTs = $this->normalize_event_timestamp($existing['event time'] ?? '');
			$newTs = $this->normalize_event_timestamp($row['event time'] ?? '');

			if ($stageKey === 'origin_departure') {
				$oldScore = $stepper_score($existing);
				$newScore = $stepper_score($row);

				if ($newScore > $oldScore || ($newScore === $oldScore && $newTs > $oldTs)) {
					$deduped_steppers[$idx] = $row;
				}
			} else {
				// Untuk duplicate selain origin, tetap pilih waktu paling awal.
				if ($newTs > 0 && ($oldTs <= 0 || $newTs < $oldTs)) {
					$deduped_steppers[$idx] = $row;
				}
			}
		}

		$steppers = $deduped_steppers;

		// 5.x sort stepper
		usort($steppers, function ($a, $b) {
			$ta = $this->normalize_event_timestamp($a['event time'] ?? '');
			$tb = $this->normalize_event_timestamp($b['event time'] ?? '');

			if ($ta == $tb) return 0;
			return ($ta < $tb) ? -1 : 1;
		});
		
        // FINAL ROUTING SOURCE PRIORITY:
        // Jika local export punya route, pakai local_routes sebagai route final.
        // Ini harus dilakukan sebelum build AIS legs agar ais_legs tidak menjadi campuran GoComet + Local.
       if (!empty($local_routes)) {
			usort($local_routes, function ($a, $b) {
				$la = (int)($a['_route_leg_no'] ?? 999);
				$lb = (int)($b['_route_leg_no'] ?? 999);

				if ($la !== $lb) return $la - $lb;

				$ta = $this->normalize_event_timestamp($a['time of departure'] ?? '');
				$tb = $this->normalize_event_timestamp($b['time of departure'] ?? '');

				if ($ta == $tb) return 0;
				return ($ta < $tb) ? -1 : 1;
			});

			/*
			 * PATCH:
			 * Jangan langsung buang GoComet route.
			 * Kalau local_routes hanya berisi leg transhipment / leg 2,
			 * route GoComet POL -> POT tetap harus dimasukkan.
			 */
			$merged_routes = $local_routes;

			$firstLocalDepartTs = 0;
			foreach ($local_routes as $lr) {
				$ts = $this->normalize_event_timestamp($lr['time of departure'] ?? '');
				if ($ts > 0 && ($firstLocalDepartTs <= 0 || $ts < $firstLocalDepartTs)) {
					$firstLocalDepartTs = $ts;
				}
			}

			foreach ($gocomet_routes_for_imo as $gr) {
				if (!is_array($gr)) continue;

				$grDepartTs = $this->normalize_event_timestamp($gr['time of departure'] ?? '');
				$grArriveTs = $this->normalize_event_timestamp($gr['time of discharge'] ?? '');

				/*
				 * Ambil GoComet route yang selesai sebelum local route pertama.
				 * Untuk case ini:
				 * GoComet INCRES JKT -> SIN selesai 04/06
				 * Local OTANA SIN -> KHSIH mulai 10/06
				 */
				if ($firstLocalDepartTs > 0 && $grArriveTs > 0 && $grArriveTs > $firstLocalDepartTs) {
					continue;
				}

				$exists = false;

				foreach ($merged_routes as $mr) {
					$sameFrom = $this->is_same_port_text(
						$mr['port of loading'] ?? '',
						$gr['port of loading'] ?? ''
					);

					$sameTo = $this->is_same_port_text(
						$mr['port of discharge'] ?? '',
						$gr['port of discharge'] ?? ''
					);

					$sameDepart = $this->normalize_event_date_key($mr['time of departure'] ?? '') ===
								  $this->normalize_event_date_key($gr['time of departure'] ?? '');

					$sameArrive = $this->normalize_event_date_key($mr['time of discharge'] ?? '') ===
								  $this->normalize_event_date_key($gr['time of discharge'] ?? '');

					if ($sameFrom && $sameTo && $sameDepart && $sameArrive) {
						$exists = true;
						break;
					}
				}

				if (!$exists) {
					$gr['_source_name'] = $gr['_source_name'] ?? 'gocomet';
					$gr['_source_priority'] = $gr['_source_priority'] ?? 50;
					$gr['_route_leg_no'] = 0;

					$merged_routes[] = $gr;
				}
			}

			usort($merged_routes, function ($a, $b) {
				$ta = $this->normalize_event_timestamp($a['time of departure'] ?? '');
				$tb = $this->normalize_event_timestamp($b['time of departure'] ?? '');

				if ($ta == $tb) return 0;
				return ($ta < $tb) ? -1 : 1;
			});

			$items = array_map(function ($route) {
				return $this->clean_route_internal_fields($route);
			}, $merged_routes);

		} else {
			$items = $this->build_final_routing($items, $steppers);
		}

		$deduped_table = [];
		$seen_events = [];

		foreach ($table as $row) {
			$eventName = trim((string)($row['event name'] ?? ''));
			$locName   = trim((string)($row['location name'] ?? ''));
			$eventTime = trim((string)($row['event time'] ?? ''));

			$eventKey = $this->normalize_tracking_event_key($eventName, $locName);
			$dateKey  = $this->normalize_event_date_key($eventTime);

			/*
			 * Arrival dibuat satu saja per shipment.
			 * Jika ada GoComet dan local, GoComet akan menang lewat _source_priority.
			 */
			$portName = strtolower(trim((string)($row['port name'] ?? '')));
			$eventNameLower = strtolower(trim((string)($row['event name'] ?? '')));
			$locationLower = strtolower(trim((string)($row['location name'] ?? '')));

			/*
			 * PATCH:
			 * Jangan gabungkan semua arrival jadi key "arrival".
			 * Case transhipment:
			 * - Arrival Singapore dari GoComet/local = event antara
			 * - Arrival Istanbul dari local atd = final POD
			 * Keduanya harus tampil.
			 */
			if ($eventKey === 'arrival') {
			$eventScope = strtolower(trim((string)($row['_event_scope'] ?? '')));
			$portNameRaw = trim((string)($row['port name'] ?? ''));
			$portName = strtolower($portNameRaw);

			$isPortFinalPod = $this->route_port_matches_code_or_name($portNameRaw, $pod ?? '', [
				$consol['destination'] ?? '',
				$localDestination ?? '',
				$r->destination ?? '',
			]);

			$isPortPot = $this->route_port_matches_code_or_name($portNameRaw, $pot ?? '', [
				$consol['transhipment_port'] ?? '',
				$localConnecting ?? '',
				$transPort ?? '',
			]);

			if ($eventScope === 'final_pod_arrival') {
				$key = 'final_pod_arrival';
			} elseif ($eventScope === 'transshipment_arrival') {
				$key = 'transshipment_arrival|' . $portName . '|' . $dateKey;
			} elseif ($isPortFinalPod && !$isPortPot) {
				$key = 'final_pod_arrival';
			} elseif ($isPortPot) {
				// Contoh: GoComet kirim "Shipment Arrived At Destination Port"
				// tapi port-nya SINGAPORE/POT, maka jangan dianggap final POD.
				$key = 'transshipment_arrival|' . $portName . '|' . $dateKey;
			} else {
				$key = 'arrival|' . $portName . '|' . $dateKey;
			}
		} elseif ($eventKey === 'origin_departure') {

				// Origin departure hanya boleh satu per shipment.
				// Jangan pakai portName karena local = "Jakarta" sedangkan GoComet =
				// "JAKARTA, INDONESIA, IDJKT". Kalau port dimasukkan ke key,
				// keduanya dianggap beda dan duplicate tetap lolos.
				$key = 'origin_departure';

			} elseif (in_array($eventKey, [
				'transshipment_arrival',
				'transshipment_departure',
				'gate_in',
				'gate_out',
				'empty_return',
			], true)) {
				$key = $eventKey . '|' . $portName . '|' . $dateKey;
			} else {
				$key = $eventKey . '|' . $portName . '|' . $dateKey;
			}

			if (!isset($seen_events[$key])) {
				$seen_events[$key] = count($deduped_table);
				$deduped_table[] = $row;
				continue;
			}

			$idx = $seen_events[$key];

			$oldScore = $this->event_row_score($deduped_table[$idx]);
			$newScore = $this->event_row_score($row);

			$oldTs = $this->normalize_event_timestamp($deduped_table[$idx]['event time'] ?? '');
			$newTs = $this->normalize_event_timestamp($row['event time'] ?? '');

			$oldHasTime = preg_match('/\d{2}:\d{2}/', (string)($deduped_table[$idx]['event time'] ?? ''));
			$newHasTime = preg_match('/\d{2}:\d{2}/', (string)($row['event time'] ?? ''));

			$oldName = strtolower(trim((string)($deduped_table[$idx]['event name'] ?? '')));
			$newName = strtolower(trim((string)($row['event name'] ?? '')));

			$preferExplicitTransshipment =
				strpos($key, 'transshipment_arrival|') === 0 &&
				(
					strpos($newName, 'transhipment') !== false ||
					strpos($newName, 'transshipment') !== false
				) &&
				(
					strpos($oldName, 'destination port') !== false ||
					$oldName === 'arrival'
				);

			if (
				$preferExplicitTransshipment ||
				$newScore > $oldScore ||
				(
					in_array($key, ['final_pod_arrival', 'final_pod_gate_out'], true) &&
					$newScore === $oldScore &&
					(
						($newHasTime && !$oldHasTime) ||
						($newHasTime == $oldHasTime && $newTs > $oldTs)
					)
				)
			) {
				$deduped_table[$idx] = $row;
			}
		}

		$table = $deduped_table;

		// sort ulang setelah dedupe,
		// karena dedupe bisa replace row lama dengan row GoComet yang tanggalnya lebih baru
		usort($table, function ($a, $b) {
			$ta = $this->normalize_event_timestamp($a['event time'] ?? '');
			$tb = $this->normalize_event_timestamp($b['event time'] ?? '');

			if ($ta == $tb) return 0;
			return ($ta < $tb) ? -1 : 1;
		});

		// bersihkan field internal sebelum response
		$table = array_map(function ($row) {
			unset($row['_source_name'], $row['_source_priority'], $row['_event_scope']);
			return $row;
		}, $table);

		$table = $this->add_event_no($table);

        // --- FINAL AIS FALLBACK FOR LOCAL-ONLY SHIPMENT ---
        // Jika GoComet tidak mengembalikan updated_trackings, blok AIS di dalam loop GoComet tidak pernah jalan.
        // Untuk shipment local fallback, tetap cari vessel_imo berdasarkan nama kapal dari consol/header,
        // lalu hit Jarvis current hanya jika sekarang masih dalam range ETD - ETA.
        $finalVesselName = '';
        if (!empty($vesselNameForLookup ?? '')) {
            $finalVesselName = trim((string)$vesselNameForLookup);
        } elseif (!empty($vessel_master)) {
            $finalVesselName = trim((string)$vessel_master);
        } elseif (!empty($header[0][0]['vessel'])) {
            $finalVesselName = trim((string)$header[0][0]['vessel']);
        }

        $finalImoNumber = !empty($imoNumber ?? '') ? trim((string)$imoNumber) : '';
        $finalMmsiNumber = !empty($mmsiNumber ?? '') ? trim((string)$mmsiNumber) : '';

        if ((empty($finalImoNumber) || empty($finalMmsiNumber)) && !empty($finalVesselName)) {
            $masterVesselFinal = $this->get_vessel_imo_master($finalVesselName);

            if (!empty($masterVesselFinal)) {
                if (empty($finalImoNumber) && !empty($masterVesselFinal['imo'])) {
                    $finalImoNumber = trim((string)$masterVesselFinal['imo']);
                }

                if (empty($finalMmsiNumber) && !empty($masterVesselFinal['mmsi'])) {
                    $finalMmsiNumber = trim((string)$masterVesselFinal['mmsi']);
                }
            }
        }

        // Sinkronkan balik ke variable lama supaya fallback snapshot dan CO2 di bawah tetap memakai MMSI/IMO ini.
        if (empty($imoNumber ?? '') && !empty($finalImoNumber)) {
            $imoNumber = $finalImoNumber;
        }
        if (empty($mmsiNumber ?? '') && !empty($finalMmsiNumber)) {
            $mmsiNumber = $finalMmsiNumber;
        }
        if (empty($vesselNameForLookup ?? '') && !empty($finalVesselName)) {
            $vesselNameForLookup = $finalVesselName;
        }

        $finalEtdDt = ($etd_db instanceof DateTime) ? clone $etd_db : null;
        $finalEtaDt = ($eta_db instanceof DateTime) ? clone $eta_db : null;
        $shouldHitJarvisCurrentFinal = $this->is_now_within_etd_eta($finalEtdDt, $finalEtaDt);

        if (
            $includeAis &&
            !$this->has_valid_ais_current($ais_current) &&
            $shouldHitJarvisCurrentFinal &&
            (!empty($finalMmsiNumber) || !empty($finalImoNumber) || !empty($finalVesselName))
        ) {
            $jarvisCurrentFinal = $this->get_current_ais_from_jarvis(
                $finalImoNumber,
                $finalMmsiNumber,
                $finalVesselName
            );

            $jarvisAisFinal = $this->normalize_jarvis_current_ais(
                $jarvisCurrentFinal,
                $finalImoNumber,
                $finalVesselName
            );

            if ($this->has_valid_ais_current($jarvisAisFinal)) {
                // Kalau master punya MMSI tapi response normalizer belum mengisi, isi dari master.
                if (empty($jarvisAisFinal['mmsi_number']) && !empty($finalMmsiNumber)) {
                    $jarvisAisFinal['mmsi_number'] = $finalMmsiNumber;
                }

                $ais_current = $jarvisAisFinal;

                if (empty($imoNumber ?? '') && !empty($jarvisAisFinal['imo_number'])) {
                    $imoNumber = $jarvisAisFinal['imo_number'];
                }
                if (empty($mmsiNumber ?? '') && !empty($jarvisAisFinal['mmsi_number'])) {
                    $mmsiNumber = $jarvisAisFinal['mmsi_number'];
                }
            }
        }

		if (!$this->has_valid_ais_current($ais_current)) {
			$ais_current = [];
		}

        // --- NEW LOGIC: FALLBACK AIS HISTORY ---
        // Jika historical-tracks kosong, coba history-snapshots sebelum FE jatuh ke fallback port route.
        if ($includeAis && empty($ais_history['tracks'])) {
            $snapshot_res = null;

            // Prioritas identifier: MMSI dari loop/master/current, lalu IMO, lalu vessel name.
            $mmsi = isset($mmsiNumber) && !empty($mmsiNumber)
                ? $mmsiNumber
                : ($ais_current['mmsi_number'] ?? ($ais_history['mmsi_number'] ?? ''));

            $imo = isset($imoNumber) && !empty($imoNumber)
                ? $imoNumber
                : ($ais_current['imo_number'] ?? ($ais_history['imo_number'] ?? ''));

            $vessel_name = $vesselNameForLookup ?? ($header[0][0]['vessel'] ?? '');

            // Tahap 1: Coba dengan MMSI
            if (!empty($mmsi)) {
                $snapshot_res = $this->fetch_jarvis(
                    'https://api-gw.jar-vis.com/jarvis/api/v1/navapi/vessels/history-snapshots?mmsi=' . rawurlencode((string)$mmsi)
                );
            }

            // Tahap 2: Jika kosong, coba dengan IMO
            if ((empty($snapshot_res) || empty($snapshot_res['data']['points'])) && !empty($imo)) {
                $snapshot_res = $this->fetch_jarvis(
                    'https://api-gw.jar-vis.com/jarvis/api/v1/navapi/vessels/history-snapshots?imo=' . rawurlencode((string)$imo)
                );
            }

            // Tahap 3: Jika masih kosong, coba dengan nama kapal
            if ((empty($snapshot_res) || empty($snapshot_res['data']['points'])) && !empty($vessel_name)) {
                $snapshot_res = $this->fetch_jarvis(
                    'https://api-gw.jar-vis.com/jarvis/api/v1/navapi/vessels/history-snapshots?name=' . rawurlencode((string)$vessel_name)
                );
            }

            if (!empty($snapshot_res['data']['points']) && is_array($snapshot_res['data']['points'])) {
                $mapped_tracks = [];

                foreach ($snapshot_res['data']['points'] as $pt) {
                    $lat = $pt['latitude'] ?? ($pt['lat'] ?? null);
                    $lon = $pt['longitude'] ?? ($pt['lon'] ?? ($pt['lng'] ?? null));

                    if (!$this->is_valid_lat_lon($lat, $lon)) {
                        continue;
                    }

                    $snapshotTs = $pt['timestamp']
                        ?? ($pt['ArraysTimestamp']
                        ?? ($pt['PositionLastUpdated']
                        ?? ($pt['lastPositionTime'] ?? '')));

                    $mapped_tracks[] = [
                        'lat'       => $lat,
                        'lon'       => $lon,
                        'lat_lon'   => [$lat, $lon],
                        'speed'     => $pt['speedKnots'] ?? ($pt['speed'] ?? ($pt['SpeedOverGround'] ?? 0)),
                        'course'    => $pt['courseDegrees'] ?? ($pt['course'] ?? ($pt['CourseOverGround'] ?? null)),
                        'heading'   => $pt['headingDegrees'] ?? ($pt['heading'] ?? ($pt['TrueHeading'] ?? null)),
                        'timestamp' => $snapshotTs,
                        '_sort_ts'  => $this->normalize_event_timestamp($snapshotTs),
                        'raw'       => [$pt],
                    ];
                }

                usort($mapped_tracks, function ($a, $b) {
                    return ((int)($a['_sort_ts'] ?? 0)) <=> ((int)($b['_sort_ts'] ?? 0));
                });

                foreach ($mapped_tracks as &$mappedTrack) {
                    unset($mappedTrack['_sort_ts']);
                }
                unset($mappedTrack);

                if (!empty($mapped_tracks)) {
                    $ais_history['source'] = 'jarvis-snapshot-fallback';
                    $ais_history['tracks'] = $mapped_tracks;
                    $ais_history['raw'] = $snapshot_res;
                    if (!empty($imo)) $ais_history['imo_number'] = $imo;
                    if (!empty($mmsi)) $ais_history['mmsi_number'] = $mmsi;
                }
            }
        }

        // --- NEW LOGIC: CO2 EMISSION ---
        // Jarvis emissions memakai parameter "mmsi"; value-nya boleh MMSI atau IMO.
        $co2_emission = null;

        $emissionMmsi = $ais_current['mmsi_number'] ?? ($ais_history['mmsi_number'] ?? '');
        $emissionImo  = $ais_current['imo_number'] ?? ($ais_history['imo_number'] ?? '');
        $vessel_identifier = !empty($emissionMmsi) ? $emissionMmsi : $emissionImo;

        $days = 0;
        $etdForEmission = $etd_fallback ?? '';
        $etaForEmission = $eta_fallback ?? '';

        if (!empty($etdForEmission) && !empty($etaForEmission)) {
            // Hilangkan string zona waktu (' WIB') agar strtotime lebih aman.
            $etd_clean = preg_replace('/\s+[A-Z]+$/', '', (string)$etdForEmission);
            $eta_clean = preg_replace('/\s+[A-Z]+$/', '', (string)$etaForEmission);

            $date_etd = strtotime($etd_clean);
            $date_eta = strtotime($eta_clean);

            if ($date_etd && $date_eta) {
                $days = (int)ceil(($date_eta - $date_etd) / 86400);
            }
        }

        if ($days <= 0) $days = 1;

       $co2_emission = [];

		if ($includeAis && !empty($vessel_identifier) && (float)$cbm > 0) {
			$days = !empty($days) ? (int)$days : 5;
			if ($days < 1) {
				$days = 1;
			}

			/*
			 * MMSI biasanya 9 digit, IMO biasanya 7 digit.
			 * Jangan selalu pakai mmsi= kalau isinya IMO.
			 */
			$identifier = trim((string)$vessel_identifier);

			if (preg_match('/^\d{9}$/', $identifier)) {
				$identifierParam = 'mmsi';
			} elseif (preg_match('/^\d{7}$/', $identifier)) {
				$identifierParam = 'imo';
			} else {
				$identifierParam = 'name';
			}

			$emission_endpoint =
				'https://api-gw.jar-vis.com/jarvis/api/v1/navapi/vessels/emissions?' .
				http_build_query([
					$identifierParam => $identifier,
					'days'           => $days,
					'cargoCbm'       => (float)$cbm,
				], '', '&', PHP_QUERY_RFC3986);

			log_message('error', 'DEBUG_JARVIS_EMISSION_REQUEST=' . json_encode([
				'hbl'      => $si_number,
				'endpoint' => $emission_endpoint,
				'cbm'      => $cbm,
				'days'     => $days,
			], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

			$co2_res = $this->fetch_jarvis($emission_endpoint);

			if (!empty($co2_res['success'])) {
				$co2Data   = isset($co2_res['data']) && is_array($co2_res['data']) ? $co2_res['data'] : [];
				$emissions = isset($co2Data['emissions']) && is_array($co2Data['emissions']) ? $co2Data['emissions'] : [];
				$distance  = isset($co2Data['distance']) && is_array($co2Data['distance']) ? $co2Data['distance'] : [];
				$vessel    = isset($co2Data['vessel']) && is_array($co2Data['vessel']) ? $co2Data['vessel'] : [];
				$voyage    = isset($co2Data['voyage']) && is_array($co2Data['voyage']) ? $co2Data['voyage'] : [];

				$co2Total = null;

				if (isset($emissions['co2_t_total'])) {
					$co2Total = (float)$emissions['co2_t_total'];
				} elseif (isset($emissions['co2_t_sailed'])) {
					$co2Total = (float)$emissions['co2_t_sailed'];
				}

				/*
				 * Untuk response API tracking.
				 * Ini aman karena tetap array untuk JSON response.
				 */
				$co2_emission = [
					'source'              => 'jarvis',
					'co2_t_total'          => $co2Total,
					'co2_t_sailed'         => isset($emissions['co2_t_sailed']) ? (float)$emissions['co2_t_sailed'] : null,
					'co2_t_propulsion'     => isset($emissions['co2_t_propulsion']) ? (float)$emissions['co2_t_propulsion'] : null,
					'co2_t_auxiliary'      => isset($emissions['co2_t_auxiliary']) ? (float)$emissions['co2_t_auxiliary'] : null,
					'co2_kg_per_nm'        => isset($emissions['co2_kg_per_nm']) ? (float)$emissions['co2_kg_per_nm'] : null,
					'co2_factor'           => isset($emissions['co2_factor']) ? (float)$emissions['co2_factor'] : null,
					'sailed_nm'            => isset($distance['sailedNm']) ? (float)$distance['sailedNm'] : null,
					'vessel_name'          => $vessel['name'] ?? null,
					'mmsi'                 => $vessel['mmsi'] ?? null,
					'imo'                  => $vessel['imo'] ?? null,
					'voyage_from'          => $voyage['from'] ?? null,
					'voyage_to'            => $voyage['to'] ?? null,
					'days_covered'         => isset($voyage['daysCovered']) ? (float)$voyage['daysCovered'] : null,
					'elapsed_days'         => isset($voyage['elapsedDays']) ? (float)$voyage['elapsedDays'] : null,
					'points_used'          => isset($voyage['pointsUsed']) ? (int)$voyage['pointsUsed'] : null,
					'avg_speed_kn'         => isset($voyage['avgSpeedKn']) ? (float)$voyage['avgSpeedKn'] : null,
					'confidence'           => $co2Data['quality']['confidence'] ?? null,
					'cache_hit'            => $co2Data['cacheHit'] ?? null,
					'message'              => $co2_res['message'] ?? null,
				];

				/*
				 * Untuk database x_tracking_export.co2:
				 * Simpan angka saja, bukan array.
				 */
				if ($co2Total !== null && $co2Total > 0) {
					$this->dbx->where('hbl_no', $si_number);
					$this->dbx->update('x_tracking_export', [
						'co2' => $co2Total,
					]);
					
					 // Update import
					$this->dbx->where('hbl_no', $si_number);
					$this->dbx->update('x_tracking', [
						'co2' => $co2Total,
					]);

					log_message('error', 'DEBUG_CO2_UPDATE_EXPORT=' . json_encode([
						'hbl'       => $si_number,
						'co2_total' => $co2Total,
						'affected'   => method_exists($this->dbx, 'affected_rows') ? $this->dbx->affected_rows() : null,
					], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
				}
			} else {
				log_message('error', 'DEBUG_JARVIS_EMISSION_FAILED=' . json_encode([
					'hbl'      => $si_number,
					'endpoint' => $emission_endpoint,
					'response' => $co2_res,
				], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			}
		}

        // =====================================================
		// AIS / JARVIS MODE
		// Default: FE normal tetap include AIS.
		// Cron Transvoyant kirim include_ais=0 atau mode=events_only
		// supaya hanya ambil event tracking tanpa AIS/Jarvis.
		// =====================================================
        // Pakai hasil parsing awal. Jangan parse ulang agar mode Jarvis konsisten.
        $modeParam = $mode;

		$ais_current    = [];
		$ais_history    = [];
		$ais_legs       = [];
		$active_ais_leg = null;

		// =====================================================
		// AIS legs hanya dibuat dan di-enrich kalau include_ais=true
		// =====================================================
		if ($includeAis && !empty($items) && is_array($items)) {

			// Point context harus mengikuti POL/POD/POT final.
			// Jangan masukkan lagi consol lama setelah source import/Bintang sudah override.
			$polNames = array_merge(
				[$pol],
				$this->get_port_aliases_from_master($pol)
			);

			$podNames = array_merge(
				[$pod],
				$this->get_port_aliases_from_master($pod)
			);

			$potNames = !empty($pot)
				? array_merge([$pot], $this->get_port_aliases_from_master($pot))
				: [];

			$pointContext = [
				'pol'       => $pol,
				'pod'       => $pod,
				'pot'       => $pot,
				'pol_point' => $pol_point,
				'pod_point' => $pod_point,
				'pot_point' => $pot_point,
				'pol_names' => array_values(array_unique(array_filter($polNames))),
				'pod_names' => array_values(array_unique(array_filter($podNames))),
				'pot_names' => array_values(array_unique(array_filter($potNames))),
			];

			$ais_legs = $this->build_ais_legs_from_routing($items, $steppers, $pointContext);
			log_message('error', 'DEBUG AIS_LEGS_BUILD=' . json_encode($ais_legs));

			$ais_legs = $this->enrich_ais_legs_from_jarvis($ais_legs);
			log_message('error', 'DEBUG AIS_LEGS_ENRICH=' . json_encode($ais_legs));

			$activeLeg = $this->pick_active_ais_leg($ais_legs);

			if (!empty($activeLeg)) {
				// Top-level AIS derived from selected cargo leg.
				// Active leg   => Jarvis current.
				// Completed leg => last history point.
				if (
					!empty($activeLeg['cargo_current']) &&
					$this->has_valid_ais_current($activeLeg['cargo_current'])
				) {
					$ais_current = $activeLeg['cargo_current'];
				} elseif (
					!empty($activeLeg['ais_current']) &&
					$this->has_valid_ais_current($activeLeg['ais_current'])
				) {
					$ais_current = $activeLeg['ais_current'];
				} else {
					$ais_current = [];
				}

				if (!empty($activeLeg['ais_history']['tracks'])) {
					$ais_history = $activeLeg['ais_history'];
				} else {
					$ais_history = [];
				}

				$active_ais_leg = $activeLeg;
			}
		} else {
			log_message(
				'error',
				'DEBUG AIS_SKIP include_ais=' . ($includeAis ? '1' : '0') .
				' mode=' . $modeParam
			);
		}

		// =====================================================
		// CO2 juga dari Jarvis, jadi hanya boleh jalan kalau include_ais=true
		// =====================================================
		if ($includeAis && empty($co2_emission)) {
			$emissionMmsi = $ais_current['mmsi_number'] ?? '';
			$emissionImo  = $ais_current['imo_number'] ?? '';

			// Endpoint emission kamu pakai param mmsi,
			// jadi sebaiknya prioritas mmsi dulu.
			$vessel_identifier = !empty($emissionMmsi) ? $emissionMmsi : $emissionImo;

			$days = 0;
			$etdForEmission = $etd_fallback ?? '';
			$etaForEmission = $eta_fallback ?? '';

			if (!empty($etdForEmission) && !empty($etaForEmission)) {
				$etd_clean = preg_replace('/\s+[A-Z]+$/', '', (string) $etdForEmission);
				$eta_clean = preg_replace('/\s+[A-Z]+$/', '', (string) $etaForEmission);

				$date_etd = strtotime($etd_clean);
				$date_eta = strtotime($eta_clean);

				if ($date_etd && $date_eta) {
					$days = (int) ceil(($date_eta - $date_etd) / 86400);
				}
			}

			if ($days <= 0) {
				$days = 1;
			}

			if (!empty($vessel_identifier) && $cbm > 0) {
				$emission_endpoint =
					'https://api-gw.jar-vis.com/jarvis/api/v1/navapi/vessels/emissions?mmsi=' .
					rawurlencode((string) $vessel_identifier) .
					'&days=' . rawurlencode((string) $days) .
					'&cargoCbm=' . rawurlencode((string) $cbm);

				$co2_res = $this->fetch_jarvis($emission_endpoint);

				if (isset($co2_res['success']) && $co2_res['success']) {
					$co2_emission = $co2_res['data'] ?? null;
				}
			}
		} elseif (!$includeAis) {
			$co2_emission = null;
		}

		// Bersihkan field internal route setelah AIS legs selesai dibuat.
		// Tetap jalan untuk mode full maupun events_only.
		$items = array_map(function ($route) {
			return $this->clean_route_internal_fields($route);
		}, $items);

        // 1. Fungsi fetch ke Docker (VERSI FINAL IPv4 & WAYPOINTS)
        $fetch_maritime_route = function($from, $to) {
            $from = strtoupper(trim((string)$from));
            $to = strtoupper(trim((string)$to));
            if (empty($from) || empty($to)) return [];
            
            // Wajib 127.0.0.1 agar tidak nyasar ke IPv6
            $api_url = "http://127.0.0.1:3000/api/distance?origin={$from}&destination={$to}";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $api_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 25); 
            $response = curl_exec($ch);
            curl_close($ch);
            
            $routeData = json_decode($response, true);
            $coords = [];
            
            if (isset($routeData['waypoints']) && is_array($routeData['waypoints'])) {
                foreach ($routeData['waypoints'] as $pt) {
                    if (isset($pt['lat']) && isset($pt['lng'])) {
                        $coords[] = [(float)$pt['lat'], (float)$pt['lng']]; 
                    }
                }
            }
            return $coords;
        };

        // 2. SMART FALLBACK ROUTER (DENGAN MANUAL BYPASS HINDIA)
        $fetch_with_fallback = function($from, $to) use ($fetch_maritime_route) {
            $route = $fetch_maritime_route($from, $to);
            
            if (empty($route) && $from !== 'SGSIN' && $to !== 'SGSIN') {
                $leg1 = $fetch_maritime_route($from, 'SGSIN');
                $leg2 = $fetch_maritime_route('SGSIN', $to);
                if (!empty($leg1) && !empty($leg2)) {
                    $route = array_merge($leg1, $leg2);
                }
            }

            // HACK: Jika ArcNautical gagal berbelok dan hanya menghasilkan garis lurus (<= 3 titik)
            if (!empty($route) && count($route) <= 3) {
                // Daftar pelabuhan yang butuh melambung di bawah India
                $need_bypass = ['PKKHI', 'INNSA', 'INBOM', 'AEJEA', 'AEDXB', 'AESHJ'];
                if ($from === 'SGSIN' && in_array($to, $need_bypass)) {
                    $first = $route[0];
                    $last = end($route);
                    // Rute Paksa: Singapura -> Ujung Aceh -> Selatan Sri Lanka -> Tujuan
                    $route = [
                        $first,
                        [5.80, 95.30], // Laut utara Sumatera (Bypass 1)
                        [5.80, 80.00], // Laut selatan Sri Lanka (Bypass 2)
                        $last
                    ];
                }
            }
            return $route;
        };

        // 3. MAPPER KHUSUS UN/LOCODE (Hanya untuk Routing ArcNautical)
        $standardize_for_routing = function($code) {
            $code = strtoupper(trim((string)$code));
            // Tambahkan bypass/alias operasional Anda di sini jika rute lautnya sering gagal
            $mapping = [
                'IDTPP' => 'IDJKT', 
                'SGPAS' => 'SGSIN', 
                'JNTKO' => 'JPTYO', 
                'USCHI' => 'USNYC', 
                'HKHKG' => 'CNSZX',
				'TRAMB' => 'TRIST',
				'IDSUR' => 'IDSUB',
				'THLKR' => 'THLCH',
            ];
            return $mapping[$code] ?? $code;
        };

        // Routing harus memakai POL/POT/POD FINAL yang sudah dipilih di atas.
        // Jangan kembali ke $consol karena import/Bintang bisa sudah mengoverride lane.
        $pol_code = $standardize_for_routing($pol);
        $pot_code = $standardize_for_routing($pot);
        $pod_code = $standardize_for_routing($pod);

        // 4. EKSEKUSI PENGGABUNGAN RUTE
        $maritime_path = [];
        if (!empty($pol_code) && !empty($pod_code)) {
            if (!empty($pot_code)) {
                $leg1 = $fetch_with_fallback($pol_code, $pot_code);
                $leg2 = $fetch_with_fallback($pot_code, $pod_code);
                
                if (!empty($leg1) && !empty($leg2)) {
                    $maritime_path = array_merge($leg1, $leg2);
                } elseif (!empty($leg1)) {
                    $maritime_path = $leg1;
                } elseif (!empty($leg2)) {
                    $maritime_path = $leg2;
                } else {
                    $maritime_path = $fetch_with_fallback($pol_code, $pod_code);
                }
            } else {
                $maritime_path = $fetch_with_fallback($pol_code, $pod_code);
            }
        }

        // Variabel $maritime_path siap dimasukkan ke dalam $body JSON response
		// 5. OVERWRITE KOORDINAT DATABASE DENGAN DATA API (AKURAT 100%)
        if (!empty($maritime_path) && isset($header[0][0])) {
            // Ambil titik pertama (Origin) dan terakhir (Destination) dari rute laut
            $first_point = $maritime_path[0];
            $last_point = end($maritime_path); 

            // Timpa pol_point dan pod_point dari database menjadi string "lat, lng"
            $header[0][0]['pol_point'] = $first_point[0] . ", " . $first_point[1];
            $header[0][0]['pod_point'] = $last_point[0] . ", " . $last_point[1];

            // Jika ada transit (POT), kita bisa ambil titik tengahnya dari akhir leg1
            if (!empty($pot_code) && !empty($leg1)) {
                $transit_point = end($leg1);
                $header[0][0]['pot_point'] = $transit_point[0] . ", " . $transit_point[1];
            }
        }

        // Jika final shipment tidak punya POT, jangan biarkan pot_point lama dari consol ikut terbawa.
        if (empty($pot) && isset($header[0][0]['pot_point'])) {
            unset($header[0][0]['pot_point']);
        }
		// ---> START: FALLBACK CO2 EMISSION (GLEC Framework) <---
        // Hanya dieksekusi jika Jarvis gagal mengembalikan data CO2 dan rute maritim tersedia
        if (empty($co2_emission) && !empty($maritime_path)) {
            
            // 1. Ambil berat kargo (Asumsi nama kolom di database Anda adalah 'weight')
            // Ganti 'weight' dengan 'gross_weight' atau nama field yang sesuai di query $consol Anda
            $raw_weight = $consol['weight'] ?? 20; // Default 20 ton jika tidak ditemukan
            $weight_ton = is_numeric($raw_weight) ? (float)$raw_weight : 0;

            // Auto-koreksi: Jika database Anda menyimpan dalam hitungan KG (misal 20000), konversi ke Ton
            if ($weight_ton > 1000) {
                $weight_ton = $weight_ton / 1000;
            }

            if ($weight_ton > 0) {
                // 2. Hitung total jarak dari array maritime_path
                $total_distance_km = 0;
                $path_count = count($maritime_path);
                
                for ($i = 0; $i < $path_count - 1; $i++) {
                    $p1 = $maritime_path[$i];
                    $p2 = $maritime_path[$i+1];
                    $dist = $this->distance_km_between_points($p1, $p2); // Gunakan fungsi bawaan CI3 Anda
                    
                    if ($dist < PHP_INT_MAX) {
                        $total_distance_km += $dist;
                    }
                }

                // 3. Hitung emisi (Formula: Berat x Jarak x 0.015)
                if ($total_distance_km > 0) {
                    $emission_factor = 0.015; // kg CO2e/ton-km
                    $co2_kg = $weight_ton * $total_distance_km * $emission_factor;
                    $co2_ton = $co2_kg / 1000;

                    // 4. Timpa variabel $co2_emission
                    $co2_emission = [
                        'source' => 'fallback_glec',
                        'co2_kg' => $co2_kg,         // Key ini otomatis ditangkap oleh Frontend React
                        'co2_ton' => $co2_ton,       
                        'distance_km' => round($total_distance_km, 2),
                        'weight_ton' => $weight_ton,
                        'emission_factor' => $emission_factor,
                        'message' => 'Calculated via GLEC Framework Fallback'
                    ];
                }
            }
        }
        // ---> END: FALLBACK CO2 EMISSION <---
		

        $body = [
            'header' => $header,
            'routing' => array_values($items),
            'stepper' => array_values($steppers),
            'event list' => array_values($table),
            'ais_current' => $ais_current,
            'ais_history' => $ais_history,
            'co2_emission' => $co2_emission,
            'cargo_current' => $ais_current,
            'active_ais_leg' => $active_ais_leg,
            'ais_legs' => $ais_legs,
            'warehouse_tracking' => $isAgungRaya ? $rayaSummary : $bintang_tracking_summary,
            'maritime_path' => $maritime_path,
        ];


        header('Content-Type: application/json');
        return $this->response($body);
    }
}
?>