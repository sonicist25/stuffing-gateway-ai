<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Transvoyant_sync_service
{
    protected $CI;
    protected $cfg;

    public function __construct()
    {
        $this->CI =& get_instance();

        $this->CI->load->helper('transvoyant');

        $this->CI->load->library('Transvoyant_client');
        $this->CI->load->library('Transvoyant_attribute_service');
        $this->CI->load->library('Transvoyant_event_mapper');

        $this->CI->load->model('Transvoyant_shipment_sync_model');
        $this->CI->load->model('Transvoyant_attribute_log_model');
        $this->CI->load->model('Transvoyant_event_log_model');
        $this->CI->load->model('Transvoyant_snapshot_model');

        $this->CI->config->load('transvoyant');
        $this->cfg = $this->CI->config->item('transvoyant');
        if (!is_array($this->cfg)) {
            $this->cfg = [];
        }
    }

    public function get_due_shipments($limit = 100)
    {
        $shipperIds = $this->cfg['enabled_shipper_ids'] ?? [];
        return $this->CI->Transvoyant_shipment_sync_model->get_due_shipments($limit, $shipperIds);
    }

    protected function mixed_cfg()
    {
        return $this->cfg['mixed_tracking'] ?? [];
    }

    protected function get_retry_seconds()
    {
        return (int)($this->cfg['mixed_tracking']['retry_seconds'] ?? 1800); // 30 menit
    }

    protected function next_retry_at()
    {
        return date('Y-m-d H:i:s', time() + $this->get_retry_seconds());
    }

    public function is_firmenich_shipment($shipperId, array $postData = [])
    {
        $overrideFields = $this->cfg['override_fields'] ?? [];

        foreach ($overrideFields as $field) {
            if (!array_key_exists($field, $postData)) {
                continue;
            }

            $value = tv_norm((string)$postData[$field]);

            if ($field === 'is_firmenich_tracking') {
                if (in_array($value, ['1', 'true', 'yes', 'y'], true)) {
                    return true;
                }
                if (in_array($value, ['0', 'false', 'no', 'n'], true)) {
                    return false;
                }
            }

            if ($field === 'tracking_partner') {
                if (in_array($value, ['firmenich', 'dsm', 'dsm-firmenich', 'dsm firmenich'], true)) {
                    return true;
                }
                if ($value !== '') {
                    return false;
                }
            }
        }

        $enabledShippers = $this->cfg['enabled_shipper_ids'] ?? [];
        $enabledShippers = array_map('intval', $enabledShippers);

        return in_array((int)$shipperId, $enabledShippers, true);
    }

    public function is_eligible_for_sync($status, $shipperId, array $postData = [], array $bookingResult = [])
    {
        if ((int)$status <= 0) {
            return [false, 'Booking status tidak sukses'];
        }

        if (!$this->is_firmenich_shipment($shipperId, $postData)) {
            return [false, 'Bukan shipment Firmenich'];
        }

        $trackingNumber = $this->resolve_tracking_number($postData, $bookingResult);
        if (empty($trackingNumber)) {
            return [false, 'Tracking number/customer reference kosong'];
        }

        return [true, 'OK'];
    }

    public function resolve_tracking_number(array $postData = [], array $bookingResult = [])
    {
        $candidates = [
            $postData['tracking_number'] ?? null,
            $postData['customer_reference_no'] ?? null,
            $postData['si_number_firmenich'] ?? null,
            $bookingResult['tracking_number'] ?? null,
            $bookingResult['customer_reference_no'] ?? null,
            $bookingResult['si_number'] ?? null,
        ];

        foreach ($candidates as $value) {
            $value = trim((string)$value);
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    public function resolve_booking_id(array $bookingResult = [])
    {
        $candidates = [
            $bookingResult['booking_id'] ?? null,
            $bookingResult['id'] ?? null,
        ];

        foreach ($candidates as $value) {
            if (!empty($value)) {
                return $value;
            }
        }

        return null;
    }

    public function build_sync_payload_from_booking(array $postData = [], array $bookingResult = [])
    {
        $trackingNumber = $this->resolve_tracking_number($postData, $bookingResult);
        $bookingId      = $this->resolve_booking_id($bookingResult);

        $hbl = null;
        $hblCandidates = [
            $postData['hbl'] ?? null,
            $postData['house_bl'] ?? null,
            $bookingResult['house_bl'] ?? null,
            $bookingResult['hbl'] ?? null,
        ];

        foreach ($hblCandidates as $candidate) {
            $candidate = trim((string)$candidate);
            if ($candidate !== '') {
                $hbl = $candidate;
                break;
            }
        }

        $hasHbl = !empty($hbl);

		return [
			'booking_id'            => $bookingId,
			'shipper_id'            => !empty($postData['shipper_id']) ? (int)$postData['shipper_id'] : null,
			'customer_reference_no' => $trackingNumber,
			'tracking_number'       => $trackingNumber,
			'hbl'                   => $hbl,
			'mbl'                   => $postData['mbl'] ?? ($bookingResult['mbl'] ?? null),
			'container_no'          => $postData['container_no'] ?? ($postData['container'] ?? ($bookingResult['container_no'] ?? null)),
			'container_type'        => $postData['container_type'] ?? ($bookingResult['container_type'] ?? null),
			'carrier'               => $postData['carrier'] ?? ($bookingResult['carrier'] ?? null),
			'carrier_scac'          => $postData['carrier_scac'] ?? ($bookingResult['carrier_scac'] ?? null),
			'vessel_name'           => $postData['vessel_name'] ?? ($postData['vessel'] ?? ($bookingResult['vessel'] ?? null)),
			'voyage'                => $postData['voyage'] ?? ($bookingResult['voyage'] ?? null),
			'pol'                   => $postData['pol'] ?? ($postData['pol_code'] ?? ($bookingResult['pol'] ?? null)),
			'pot'                   => $postData['pot'] ?? ($postData['pot_code'] ?? ($bookingResult['pot'] ?? null)),
			'pod'                   => $postData['pod'] ?? ($postData['pod_code'] ?? ($bookingResult['pod'] ?? null)),

			// Penting:
			// Kalau HBL sudah ada, langsung masuk antrean cron.
			// Kalau HBL belum ada, jangan dipoll dulu.
			'sync_status'           => $hasHbl ? 'TRACKING_ACTIVE' : 'NEW',
			'patch_status'          => 'PENDING',
			'next_poll_at'          => $hasHbl ? date('Y-m-d H:i:s') : null,
			'last_error'            => null,
		];
    }

    public function ensure_sync_from_booking(array $postData = [], array $bookingResult = [])
    {
        $payload = $this->build_sync_payload_from_booking($postData, $bookingResult);

        if (empty($payload['tracking_number'])) {
            return [
                'success' => false,
                'message' => 'Tracking number kosong, sync tidak dibuat',
            ];
        }

        $syncId = $this->CI->Transvoyant_shipment_sync_model->ensure_sync($payload);
        $row    = $this->CI->Transvoyant_shipment_sync_model->find_by_id($syncId);

        return [
            'success' => true,
            'sync_id' => $syncId,
            'data'    => $row,
        ];
    }

    protected function build_mixed_request_from_sync(array $syncRow)
    {
        $params = [];

        if (!empty($syncRow['hbl'])) {
            $params['si_number'] = trim((string)$syncRow['hbl']);
        }

        return $params;
    }

public function fetch_mixed_tracking(array $syncRow)
    {
        $mixedCfg = $this->mixed_cfg();

        if (empty($mixedCfg['enabled']) || empty($mixedCfg['url'])) {
            return [
                'success' => false,
                'message' => 'Mixed tracking config belum aktif / url kosong',
            ];
        }

        $params = $this->build_mixed_request_from_sync($syncRow);

        /*
         * Cron Transvoyant hanya butuh event/header untuk dikirim ke Transvoyant.
         * AIS/Jarvis harus dimatikan supaya token Jarvis tidak habis oleh polling cron.
         */
        $fixedParams = $mixedCfg['fixed_params'] ?? [];
        if (!is_array($fixedParams)) {
            $fixedParams = [];
        }

        foreach ($fixedParams as $key => $value) {
            $params[$key] = $value;
        }

        // Force lagi walaupun config berubah, supaya cron ini tidak pernah memanggil AIS/Jarvis.
        $params['include_ais'] = '0';
        $params['mode']        = 'events_only';
        $params['source']      = $params['source'] ?? 'transvoyant_cron';

        if (empty($params['si_number'])) {
            return [
                'success'   => false,
                'http_code' => null,
                'message'   => 'Mixed tracking skipped: HBL belum tersedia untuk parameter si_number',
                'skipped'   => true,
                'params'    => $params,
            ];
        }

        $method  = strtoupper($mixedCfg['method'] ?? 'GET');
        $url     = $mixedCfg['url'];
        $timeout = (int)($mixedCfg['timeout'] ?? 45);
        $apiKey  = $mixedCfg['api_key'] ?? null;

        $headers = [
            'Accept: application/json',
        ];

        if (!empty($apiKey)) {
            $headers[] = 'X-API-Key: ' . $apiKey;
        }

        $ch = curl_init();
        $finalUrl = $url;

        if ($method === 'POST') {
            curl_setopt_array($ch, [
                CURLOPT_URL            => $url,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => http_build_query($params),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => array_merge($headers, [
                    'Content-Type: application/x-www-form-urlencoded',
                ]),
                CURLOPT_TIMEOUT        => $timeout,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ]);
        } else {
            $finalUrl = $url . (strpos($url, '?') === false ? '?' : '&') . http_build_query($params);

            curl_setopt_array($ch, [
                CURLOPT_URL            => $finalUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_TIMEOUT        => $timeout,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ]);
        }

        log_message('error', 'DEBUG_TV_MIXED_TRACKING_REQUEST=' . json_encode([
            'sync_id' => $syncRow['id'] ?? null,
            'hbl'     => $syncRow['hbl'] ?? null,
            'method'  => $method,
            'url'     => $method === 'POST' ? $url : $finalUrl,
            'params'  => $params,
            'time'    => date('Y-m-d H:i:s'),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            return [
                'success'   => false,
                'http_code' => 0,
                'message'   => 'Mixed tracking curl error: ' . $curlErr,
                'params'    => $params,
            ];
        }

        $json = json_decode($response, true);

        if ($httpCode < 200 || $httpCode >= 300) {
            return [
                'success'   => false,
                'http_code' => $httpCode,
                'message'   => 'Mixed tracking request failed',
                'body'      => $response,
                'params'    => $params,
            ];
        }

        if (!is_array($json)) {
            return [
                'success'   => false,
                'http_code' => $httpCode,
                'message'   => 'Mixed tracking response bukan JSON array/object valid',
                'body'      => $response,
                'params'    => $params,
            ];
        }

        return [
            'success'   => true,
            'http_code' => $httpCode,
            'data'      => $json,
            'raw_body'  => $response,
            'params'    => $params,
        ];
    }

    protected function snapshot_cfg()
    {
        $mixedCfg = $this->mixed_cfg();

        return [
            // Simpan sedikit snapshot terakhir untuk debug, jangan semua polling.
            'keep_last'      => (int)($mixedCfg['snapshot_keep_last'] ?? ($this->cfg['snapshot_keep_last'] ?? 5)),
            // Update fetched_at pada snapshot terakhir saat tidak ada perubahan bermakna.
            'touch_unchanged'=> (bool)($mixedCfg['snapshot_touch_unchanged'] ?? ($this->cfg['snapshot_touch_unchanged'] ?? true)),
        ];
    }

    protected function is_assoc_array(array $value)
    {
        if ($value === []) {
            return false;
        }

        return array_keys($value) !== range(0, count($value) - 1);
    }

    protected function normalize_snapshot_payload($value)
    {
        if (!is_array($value)) {
            return $value;
        }

        $normalized = [];
        foreach ($value as $key => $child) {
            $normalizedKey = strtolower(trim(str_replace(['-', ' '], '_', (string)$key)));

            /*
             * Field ini berubah setiap polling tapi tidak merepresentasikan event baru.
             * Jangan buang "event time" karena itu data tracking yang bermakna.
             */
            if (in_array($normalizedKey, [
                'last_update',
                'last_updated',
                'fetched_at',
                'created_at',
                'updated_at',
                'generated_at',
                'request_time',
                'server_time',
                'response_time',
            ], true)) {
                continue;
            }

            $normalized[$key] = $this->normalize_snapshot_payload($child);
        }

        if ($this->is_assoc_array($normalized)) {
            ksort($normalized);
        }

        return $normalized;
    }

    protected function build_snapshot_hash($rawBody, array $decodedBody = null)
    {
        $payload = $decodedBody;

        if ($payload === null) {
            $payload = json_decode((string)$rawBody, true);
        }

        if (!is_array($payload)) {
            return tv_hash_payload(['raw' => (string)$rawBody]);
        }

        $normalized = $this->normalize_snapshot_payload($payload);
        return sha1(json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    protected function compact_snapshot_body($rawBody, array $decodedBody = null)
    {
        $payload = $decodedBody;

        if ($payload === null) {
            $payload = json_decode((string)$rawBody, true);
        }

        if (is_array($payload)) {
            return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return (string)$rawBody;
    }

    protected function find_last_snapshot($syncId)
    {
        return $this->CI->db
            ->select('id, response_hash, fetched_at')
            ->from('tv_mixed_tracking_snapshots')
            ->where('sync_id', (int)$syncId)
            ->order_by('fetched_at', 'DESC')
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get()
            ->row_array();
    }

    protected function touch_snapshot_fetched_at($snapshotId)
    {
        if (empty($snapshotId)) {
            return;
        }

        $this->CI->db
            ->where('id', (int)$snapshotId)
            ->update('tv_mixed_tracking_snapshots', [
                'fetched_at' => date('Y-m-d H:i:s'),
            ]);
    }

    protected function prune_snapshots($syncId)
    {
        $cfg = $this->snapshot_cfg();
        $keepLast = max(1, (int)$cfg['keep_last']);
        $keepRows = $this->CI->db
            ->select('id')
            ->from('tv_mixed_tracking_snapshots')
            ->where('sync_id', (int)$syncId)
            ->order_by('fetched_at', 'DESC')
            ->order_by('id', 'DESC')
            ->limit($keepLast)
            ->get()
            ->result_array();

        $keepIds = [];
        foreach ($keepRows as $row) {
            if (!empty($row['id'])) {
                $keepIds[] = (int)$row['id'];
            }
        }

        if (empty($keepIds)) {
            return [
                'keep_last'      => $keepLast,
                'deleted_rows'   => 0,
                'message'        => 'Tidak ada snapshot yang perlu dipruning',
            ];
        }

        /*
         * Hard cap per sync_id: hanya simpan N snapshot terakhir.
         * Snapshot ini hanya cache/debug, bukan histori permanen.
         */
        $this->CI->db
            ->where('sync_id', (int)$syncId)
            ->where_not_in('id', $keepIds)
            ->delete('tv_mixed_tracking_snapshots');

        return [
            'keep_last'      => $keepLast,
            'deleted_rows'   => $this->CI->db->affected_rows(),
            'message'        => 'Snapshot lama dipruning; hanya simpan snapshot terakhir per sync_id',
        ];
    }

    protected function save_snapshot_if_changed($syncId, $rawBody, array $decodedBody = null)
    {
        $hash = $this->build_snapshot_hash($rawBody, $decodedBody);
        $lastSnapshot = $this->find_last_snapshot($syncId);
        $pruneResult = null;

        if (!empty($lastSnapshot) && ($lastSnapshot['response_hash'] ?? null) === $hash) {
            $cfg = $this->snapshot_cfg();
            if (!empty($cfg['touch_unchanged'])) {
                $this->touch_snapshot_fetched_at($lastSnapshot['id']);
            }

            $pruneResult = $this->prune_snapshots($syncId);

            return [
                'changed'     => false,
                'inserted'    => false,
                'touched_id'  => (int)$lastSnapshot['id'],
                'hash'        => $hash,
                'prune'       => $pruneResult,
                'message'     => 'Snapshot tidak berubah bermakna; tidak insert row baru',
            ];
        }

        $bodyToStore = $this->compact_snapshot_body($rawBody, $decodedBody);

        $this->CI->Transvoyant_snapshot_model->insert_snapshot([
            'sync_id'       => $syncId,
            'response_hash' => $hash,
            'response_body' => $bodyToStore,
            'fetched_at'    => date('Y-m-d H:i:s'),
        ]);

        $pruneResult = $this->prune_snapshots($syncId);

        return [
            'changed'   => true,
            'inserted'  => true,
            'hash'      => $hash,
            'body_bytes'=> strlen($bodyToStore),
            'prune'     => $pruneResult,
        ];
    }

    protected function poll_policy()
    {
        return $this->cfg['poll_policy'] ?? [];
    }

    protected function local_tracking(array $row)
    {
        if (empty($row['hbl']) || empty($row['shipper_id'])) return [];
        $rows = $this->CI->db->select('id_tracking, hbl_no, id_agent, connecting, consol_destination, destination, eta, ata, ata_by, dpol, atp, dft, atd, cargo_delivery, lt_actual_pod_arrival_at, lt_pod_eta_at, lt_pod_eta_source, ctl_actual_pod_confirmed_at, ctl_actual_second_departure_at')
            ->from('x_tracking_export')->where('hbl_no', $row['hbl'])->where('id_shipper', $row['shipper_id'])->limit(2)->get()->result_array();
        if (count($rows) > 1) throw new RuntimeException('Ambiguous internal tracking: duplicate HBL + shipper');
        return $rows[0] ?? [];
    }

    protected function pod_only(array $row)
    {
        // Check actual booking, not agent name or POT = POD. Unknown defaults to delivery required.
        if (empty($row['booking_id'])) return false;
        $booking = $this->CI->db->select('movement_type, house_bl, shipper_id')
            ->from('trnstuffingplan_booking')->where('id', (int)$row['booking_id'])->limit(1)->get()->row_array();
        if (!$booking || (int)$booking['shipper_id'] !== (int)$row['shipper_id']
            || strcasecmp(trim((string)$booking['house_bl']), trim((string)$row['hbl'])) !== 0) return false;
        return in_array(strtoupper(trim((string)$booking['movement_type'])), $this->poll_policy()['pod_only_movements'] ?? [], true);
    }

    protected function ctl_transit(array $local)
    {
        $hub = strtoupper(trim((string)($local['consol_destination'] ?? '')));
        $dest = strtoupper(trim((string)($local['destination'] ?? '')));
        return $dest !== '' && $dest !== $hub && in_array($hub, ['HONG KONG','SINGAPORE'], true)
            && in_array(strtoupper(trim((string)($local['id_agent'] ?? ''))), ['CHAHKG','CHARTER SH','CTL - HKG','CTL - SIN','CTL HONGKO','CTL SIN','CTLSIN','CTLUSA'], true);
    }

    protected function policy_date($raw)
    {
        $raw = trim((string)$raw);
        foreach (['!d/m/Y H:i:s','!d/m/Y','!Y-m-d H:i:s','!Y-m-d','!Ymd'] as $format) {
            $d = DateTimeImmutable::createFromFormat($format,$raw);
            $err = DateTimeImmutable::getLastErrors();
            if ($d && (!$err || (!$err['warning_count'] && !$err['error_count'])) && $d->format(ltrim($format,'!')) === $raw && $d->format('Y') > 1970) return $d->getTimestamp();
        }
        return null;
    }

    protected function final_eta(array $local)
    {
        $source = strtoupper((string)($local['lt_pod_eta_source'] ?? ''));
        // Backfilled ATA proxies are not reliable ETA baselines.
        if (strpos($source, 'BACKFILL') === false) {
            $eta = $this->policy_date($local['lt_pod_eta_at'] ?? '');
            if ($eta) return $eta;
        }
        $hub = strtoupper(trim((string)($local['consol_destination'] ?? '')));
        $dest = strtoupper(trim((string)($local['destination'] ?? '')));
        return $hub !== '' && $hub === $dest ? $this->policy_date($local['eta'] ?? '') : null;
    }

    protected function deadline(array $row, array $local)
    {
        $p = $this->poll_policy();
        if (empty($p['enabled'])) return null;
        $created = $this->policy_date($row['created_at'] ?? '');
        $eta = $this->final_eta($local);
        $limits = [];
        if ($created) $limits[] = $created + max(1, (int)($p['max_days_since_registration'] ?? 180))*86400;
        if ($eta) $limits[] = $eta + max(1, (int)($p['max_days_after_final_eta'] ?? 60))*86400;
        return $limits ? min($limits) : null;
    }

    protected function determine_next_poll_at(array $syncRow, array $mappedEvents = [], array $local = [], $waitingDelivery = false)
    {
        $p = $this->poll_policy();
        $eta = $this->final_eta($local);
        $days = $eta ? max(0, (time()-$eta)/86400) : 0;
        $key = $days <= 0 ? 'before_eta_minutes' : ($days <= 7 ? 'overdue_7_days_minutes' : ($days <= 14 ? 'overdue_14_days_minutes' : ($days <= 30 ? 'overdue_30_days_minutes' : 'overdue_60_days_minutes')));
        $defaults = ['before_eta_minutes'=>60,'overdue_7_days_minutes'=>120,'overdue_14_days_minutes'=>360,'overdue_30_days_minutes'=>720,'overdue_60_days_minutes'=>1440];
        $minutes = !empty($p['enabled']) ? (int)($p[$key] ?? $defaults[$key]) : (int)($this->cfg['default_poll_minutes'] ?? 30);
        if ($waitingDelivery && $this->ctl_transit($local)) $minutes = max($minutes, (int)($p['delivery_unavailable_minutes'] ?? 1440));
        $next = time()+max(1,$minutes)*60;
        $deadline = $this->deadline($syncRow,$local);
        return date('Y-m-d H:i:s', $deadline ? min($next,$deadline) : $next);
    }

    protected function stop_policy(array $row, $reason, $message, $fetched = false)
    {
        $data = ['sync_status'=>'STOPPED','next_poll_at'=>null,'stopped_at'=>date('Y-m-d H:i:s'), 'poll_stop_reason'=>$reason,'last_error'=>$message];
        if ($fetched) $data['last_mixed_poll_at']=date('Y-m-d H:i:s');
        $ok = $this->CI->Transvoyant_shipment_sync_model->update_sync($row['id'],$data);
        return ['success'=>false,'sync_id'=>(int)$row['id'],'stopped'=>(bool)$ok,'stage'=>'poll_policy','reason'=>$reason,'message'=>$message,'next_poll_at'=>null];
    }

    protected function technical_failure(array $row, array $local, $message, $fetched = false, $http = null)
    {
        $p = $this->poll_policy();
        $fresh = $this->CI->Transvoyant_shipment_sync_model->find_by_id($row['id']);
        $count = (int)($fresh['poll_error_count'] ?? 0)+1;
        $model = $this->CI->Transvoyant_shipment_sync_model;
        $model->update_sync($row['id'], ['poll_error_count'=>$count]);
        $deadline = $this->deadline($row,$local);
        if ($deadline && time() >= $deadline) return $this->stop_policy($row,'POLL_TIMEOUT',$message,$fetched);
        if (!empty($p['enabled']) && (in_array((int)$http,[401,403],true) || $count >= max(1,(int)($p['max_consecutive_errors'] ?? 10))))
            return $this->stop_policy($row, 'TECHNICAL_LIMIT', $message,$fetched);
        $minutes = min(max(1,(int)($p['retry_max_minutes'] ?? 1440)),max(1,(int)($p['retry_base_minutes'] ?? 30))*pow(2,min(10,$count-1)));
        $next = time()+(int)$minutes*60;
        if ($deadline) $next=min($next,$deadline);
        $nextAt = date('Y-m-d H:i:s',$next);
        $model->save_retryable_error($row['id'],$message,$nextAt,$fetched);
        return ['success'=>false,'sync_id'=>(int)$row['id'],'stage'=>'retry','message'=>$message,'poll_error_count'=>$count,'next_poll_at'=>$nextAt,'stopped'=>false];
    }

    protected function update_sync_from_mixed_header($syncId, array $mixed)
    {
        $header = [];
        if (isset($mixed['header'][0][0]) && is_array($mixed['header'][0][0])) {
            $header = $mixed['header'][0][0];
        }

        if (empty($header)) {
            return [
                'success' => false,
                'message' => 'Header mixed response kosong',
                'updated' => false,
            ];
        }

        $update = [
            'hbl'            => !empty($header['hbl']) ? trim((string)$header['hbl']) : null,
            'mbl'            => !empty($header['mbl']) ? trim((string)$header['mbl']) : null,
            'carrier'        => !empty($header['carrier']) ? trim((string)$header['carrier']) : null,
            'carrier_scac'   => !empty($header['scac']) ? trim((string)$header['scac']) : null,
            'vessel_name'    => !empty($header['vessel']) ? trim((string)$header['vessel']) : null,
            'voyage'         => !empty($header['voyage']) ? trim((string)$header['voyage']) : null,
            'pol'            => !empty($header['pol']) ? trim((string)$header['pol']) : null,
            'pot'            => !empty($header['pot']) ? trim((string)$header['pot']) : null,
            'pod'            => !empty($header['pod']) ? trim((string)$header['pod']) : null,
            'container_no'   => (!empty($header['container no']) && tv_norm($header['container no']) !== 'unassigned')
                                ? trim((string)$header['container no'])
                                : null,
            'container_type' => !empty($header['container_size']) ? trim((string)$header['container_size']) : null,
        ];

        $finalUpdate = [];
        foreach ($update as $k => $v) {
            if ($v !== null && $v !== '') {
                $finalUpdate[$k] = $v;
            }
        }

        if (empty($finalUpdate)) {
            return [
                'success' => true,
                'message' => 'Tidak ada field header yang perlu diupdate',
                'updated' => false,
            ];
        }

        $this->CI->Transvoyant_shipment_sync_model->update_sync($syncId, $finalUpdate);

        return [
            'success' => true,
            'message' => 'Sync row updated from mixed header',
            'updated' => true,
            'data'    => $finalUpdate,
        ];
    }

    protected function is_daily_milestone($key)
    {
        return in_array($key, ['vessel_departed','arrival_pod','carrier_delivery_destination'], true);
    }

    // Compare the date written in the original payload rather than converting
    // it to the PHP server timezone. Legacy event_timestamp may be UTC.
    protected function milestone_calendar_date($timestamp)
    {
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})(?:T| |$)/', (string)$timestamp, $m)) return null;
        return checkdate((int)$m[2], (int)$m[3], (int)$m[1]) ? $m[1].'-'.$m[2].'-'.$m[3] : null;
    }

    protected function historical_daily_match(array $history, array $payload, $eventKey)
    {
        $day = $this->milestone_calendar_date($payload['timestamp'] ?? '');
        $port = strtoupper(trim((string)($payload['location']['code'] ?? '')));
        if (!$day || !$port) return 'AMBIGUOUS';
        $ambiguous = false;
        foreach ($history as $row) {
            if (($row['status'] ?? '') !== 'SUCCESS'
                || ($row['event_key'] ?? '') !== $eventKey
                || ($row['tracking_number'] ?? '') !== ($payload['trackingNumber'] ?? '')
                || ($row['event_code'] ?? '') !== ($payload['code'] ?? '')
                || ($row['event_description'] ?? '') !== ($payload['description'] ?? '')) continue;
            $old = json_decode((string)($row['request_payload'] ?? ''), true);
            $oldDay = $this->milestone_calendar_date($old['timestamp'] ?? '');
            if (!$oldDay) {
                if ($this->milestone_calendar_date($row['event_timestamp'] ?? '') === $day) $ambiguous = true;
                continue;
            }
            $oldInstant = strtotime((string)($old['timestamp'] ?? ''));
            $newInstant = strtotime((string)($payload['timestamp'] ?? ''));
            if ($oldDay !== $day && ($oldInstant === false || $newInstant === false || $oldInstant !== $newInstant)) continue;
            $oldPort = strtoupper(trim((string)($old['location']['code'] ?? '')));
            if (!$oldPort) { $ambiguous = true; continue; }
            if ($oldPort === $port) return 'MATCH';
        }
        return $ambiguous ? 'AMBIGUOUS' : 'NONE';
    }

    protected function find_daily_success(array $syncRow, $eventKey, array $payload, $eventTimestampMysql)
    {
        $day = $this->milestone_calendar_date($payload['timestamp'] ?? '');
        if (!$day || empty($payload['location']['code'])) return 'AMBIGUOUS';
        // A one-day buffer on either side handles historical timezone offsets.
        // Look only at matching shipment/key/code, never use INFO as ARRIVED.
        $date = new DateTimeImmutable($day.' 00:00:00');
        $rows = $this->CI->db->select('tracking_number,event_key,event_code,event_description,event_timestamp,status,request_payload')
            ->from('tv_event_logs')->where('sync_id',(int)$syncRow['id'])
            ->where('tracking_number',$payload['trackingNumber'])->where('event_key',$eventKey)
            ->where('event_code',$payload['code'])->where('event_description',$payload['description'])->where('status','SUCCESS')
            ->where('event_timestamp >=',$date->modify('-1 day')->format('Y-m-d H:i:s'))
            ->where('event_timestamp <',$date->modify('+2 days')->format('Y-m-d H:i:s'))->get();
        if (!$rows) return 'AMBIGUOUS';
        return $this->historical_daily_match($rows->result_array(),$payload,$eventKey);
    }

    protected function log_and_send_event(array $syncRow, array $mappedEvent)
    {
        $payload      = $mappedEvent['payload'] ?? [];
        $eventKey     = $mappedEvent['event_key'] ?? null;
        $sourceSystem = $mappedEvent['source_system'] ?? 'mixed';
        $sourceRef    = $mappedEvent['source_ref'] ?? null;

        if (empty($payload['trackingNumber']) || empty($payload['code']) || empty($payload['description']) || empty($payload['timestamp'])) {
            return [
                'success' => false,
                'message' => 'Payload event tidak lengkap',
                'skipped' => true,
            ];
        }

        if (in_array($eventKey, ['vessel_departed', 'arrival_pod', 'carrier_delivery_destination'], true)
            ) {
            $actualTs = strtotime((string) $payload['timestamp']);
            if ($actualTs === false || $actualTs > time()) {
                return ['success' => false, 'event_key' => $eventKey,
                    'message' => 'Actual milestone date invalid or in the future'];
            }
        }
        $eventTimestampMysql = tv_iso8601_to_mysql($payload['timestamp']);
        if (empty($eventTimestampMysql)) {
            return [
                'success' => false,
                'message' => 'Timestamp event tidak valid',
                'skipped' => true,
            ];
        }
		
        // Core milestones are one occurrence per reported calendar date and port.
        // An INFO arrival must never satisfy an ARRIVED event.
        if ($this->is_daily_milestone($eventKey)) {
            $daily = $this->find_daily_success($syncRow, $eventKey, $payload, $eventTimestampMysql);
            if ($daily === 'MATCH') {
                return ['success'=>true, 'skipped'=>true, 'event_key'=>$eventKey,
                    'message'=>'Milestone tanggal dan lokasi yang sama sudah berhasil dikirim'];
            }
            if ($daily === 'AMBIGUOUS') {
                return ['success'=>false, 'skipped'=>true, 'event_key'=>$eventKey,
                    'message'=>'Historical milestone has missing/invalid location or timestamp; verify request_payload before resend'];
            }
        } else {
            $alreadySent = $this->CI->Transvoyant_event_log_model->exists_success_event(
                $payload['trackingNumber'], $payload['code'], $payload['description'], $eventTimestampMysql
            );
            if ($alreadySent) {
                return ['success'=>true, 'skipped'=>true, 'event_key'=>$eventKey,
                    'message'=>'Event already sent'];
            }
        }

        $logId = $this->CI->Transvoyant_event_log_model->insert_log([
            'sync_id'           => $syncRow['id'],
            'booking_id'        => $syncRow['booking_id'] ?? null,
            'reference_no'      => $syncRow['customer_reference_no'],
            'tracking_number'   => $payload['trackingNumber'],
            'event_key'         => $eventKey,
            'event_code'        => $payload['code'],
            'event_description' => $payload['description'],
            'event_timestamp'   => $eventTimestampMysql,
            'endpoint'          => '/dsm/v1/data/eco/shipments/events',
            'request_payload'   => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'status'            => 'PENDING',
            'source_system'     => $sourceSystem,
            'source_ref'        => $sourceRef,
        ]);

        if (!$logId) {
            return ['success' => false, 'event_key' => $eventKey,
                'message' => 'Event log insert failed; event not posted'];
        }

        $result = $this->CI->transvoyant_client->post_event($payload);

        $this->CI->Transvoyant_event_log_model->update_log($logId, [
            'response_http_code' => $result['http_code'] ?? null,
            'response_body'      => $result['body'] ?? ($result['message'] ?? null),
            'status'             => !empty($result['success']) ? 'SUCCESS' : 'FAILED',
            'sent_at'            => !empty($result['success']) ? date('Y-m-d H:i:s') : null,
        ]);

        if (!empty($result['success'])) {
            $this->CI->Transvoyant_shipment_sync_model->update_sync($syncRow['id'], [
                'last_event_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $result['log_id']    = $logId;
        $result['event_key'] = $eventKey;
        $result['payload']   = $payload;

        return $result;
    }

    public function process_sync(array $syncRow, $force = false)
    {
        $syncId = (int)$syncRow['id'];
        if (!$force && in_array($syncRow['sync_status'] ?? '', ['DELIVERED','COMPLETED','STOPPED','ERROR'], true)) return ['success'=>true,'sync_id'=>$syncId,'skipped'=>true,'stopped'=>true,'message'=>'Terminal sync; use CLI process_one for reviewed recovery'];
        $local = $this->local_tracking($syncRow);
        $deadline = $this->deadline($syncRow, $local);
        if (!$force && $deadline && time() >= $deadline)
            return $this->stop_policy($syncRow,'POLL_TIMEOUT','Polling deadline reached; audit missing milestones');
        $fetch = $this->fetch_mixed_tracking($syncRow);
        if (empty($fetch['success'])) {
            return $this->technical_failure($syncRow,$local,$fetch['message'] ?? 'Mixed tracking failed',false,$fetch['http_code'] ?? null);
        }
        $snapshotInfo = $this->save_snapshot_if_changed($syncId,$fetch['raw_body'],$fetch['data']);
        $headerHbl = $fetch['data']['header'][0][0]['hbl'] ?? '';
        if ($headerHbl && strcasecmp(trim((string)$headerHbl),trim((string)$syncRow['hbl'])) !== 0)
            return $this->technical_failure($syncRow,$local,'Mixed header HBL mismatch',true);
        $headerUpdateResult = $this->update_sync_from_mixed_header($syncId,$fetch['data']);
        $fresh = $this->CI->Transvoyant_shipment_sync_model->find_by_id($syncId);
        if (empty($fresh) || strcasecmp(trim((string)$fresh['hbl']),trim((string)$syncRow['hbl'])) !== 0)
            return $this->technical_failure($syncRow,$local,'Mixed header HBL mismatch',true);
        $patchResult = !empty($fresh['carrier']) ? $this->CI->transvoyant_attribute_service->patch_if_changed($fresh)
            : ['success'=>($fresh['patch_status'] ?? '') === 'SUCCESS','waiting'=>true,'skipped'=>true,'message'=>'Carrier unavailable'];
        $mixedForMapping = $fetch['data'];
        foreach (['pol','pot','pod'] as $field) {
            if (empty($mixedForMapping['header'][0][0][$field]) && !empty($fresh[$field])) $mixedForMapping['header'][0][0][$field] = $fresh[$field];
        }
        $mappedEvents = $this->CI->transvoyant_event_mapper->map_merged_tracking_to_tv_events($fresh['tracking_number'],$mixedForMapping,$local,$this->poll_policy());
        $podOnly = $this->pod_only($fresh);
        $outcome = $this->send_mapped_events($fresh,$mappedEvents,$patchResult,$podOnly);
        $model = $this->CI->Transvoyant_shipment_sync_model;
        $stopped = false;
        $nextPollAt = null;
        $reason = null;
        if ($outcome['can_close']) {
            $state = $outcome['delivery_sent'] ? 'DELIVERED' : 'COMPLETED';
            $data = ['sync_status'=>$state,'next_poll_at'=>null,'last_error'=>null,'poll_error_count'=>0,'poll_stop_reason'=>null,'last_mixed_poll_at'=>date('Y-m-d H:i:s')];
            if ($state === 'DELIVERED') $data['delivered_at'] = $fresh['delivered_at'] ?: date('Y-m-d H:i:s');
            else $data['stopped_at'] = date('Y-m-d H:i:s');
            $stopped = (bool)$model->update_sync($syncId,$data);
            if (!$stopped) $outcome['errors'][] = 'Failed to persist terminal status';
        }
        if (!$stopped) {
            if ($outcome['errors']) {
                $retry = $this->technical_failure($fresh,$local,implode('; ',$outcome['errors']),true,$outcome['http_code'] ?? ($patchResult['http_code'] ?? null));
                $stopped = $retry['stopped']; $nextPollAt = $retry['next_poll_at']; $reason = $retry['reason'] ?? 'TECHNICAL_RETRY';
            } elseif ($deadline && time() >= $deadline) {
                $reason = empty($outcome['required_events']['arrival_pod']) ? 'AWAITING_ATA_TIMEOUT' : 'MILESTONE_TIMEOUT';
                $stop = $this->stop_policy($fresh,$reason,'Polling deadline reached; required milestone missing',true);
                $stopped = $stop['stopped'];
            } else {
                $waitingDelivery = !empty($outcome['required_events']['arrival_pod']) && !$podOnly;
                $reason = empty($outcome['required_events']['arrival_pod']) ? 'AWAITING_ATA' : ($waitingDelivery ? 'AWAITING_DELIVERY' : 'AWAITING_MILESTONE');
                $nextPollAt = $this->determine_next_poll_at($fresh,$mappedEvents,$local,$waitingDelivery);
                $model->update_sync($syncId,['sync_status'=>'TRACKING_ACTIVE','next_poll_at'=>$nextPollAt,'last_mixed_poll_at'=>date('Y-m-d H:i:s'),'last_error'=>null,'poll_error_count'=>0,'poll_stop_reason'=>null]);
            }
        }
        return ['success'=>empty($outcome['errors']) && (!$stopped || $outcome['can_close']), 'sync_id'=>$syncId,'snapshot'=>$snapshotInfo,'header_update'=>$headerUpdateResult,'patch_result'=>$patchResult,'mapped_events'=>$mappedEvents,'send_results'=>$outcome['send_results'],'required_events'=>$outcome['required_events'],'errors'=>$outcome['errors'],'next_poll_at'=>$nextPollAt,'stopped'=>$stopped,'reason'=>$reason,'pod_only'=>$podOnly];
    }

    /** Existing mapper defines departure as INFO with exact key and description. */
    protected function required_event_codes()
    {
        return [
            'vessel_departed' => strtoupper((string) ($this->cfg['required_departure_code'] ?? 'INFO')),
            'arrival_pod' => strtoupper((string) ($this->cfg['required_arrival_code'] ?? 'ARRIVED')),
        ];
    }

    /** Delivery is sent last, only when the actual departure/arrival succeeded (or were deduplicated). */
    protected function send_mapped_events(array $syncRow, array $events, array $patchResult, $podOnly = false)
    {
        $required = $this->required_event_codes();
        $done = array_fill_keys(array_keys($required), false);
        $errors = [];
        $results = [];
        $delivery = [];
        $arrivalTime = null;
        $http = null;
        if (empty($patchResult['success']) && empty($patchResult['waiting'])) {
            $errors[] = 'Attribute patch failed';
        }
        foreach ($events as $event) {
            $key = $event['event_key'] ?? '';
            if ($key === 'carrier_delivery_destination') {
                $delivery[] = $event;
                continue;
            }
            $send = $this->log_and_send_event($syncRow, $event);
            $send['event_key'] = $key;
            $results[] = $send;
            if (empty($send['success'])) {
                $http = $send['http_code'] ?? $http;
                $errors[] = 'Event failed: ' . $key . ' - ' . ($send['message'] ?? 'see event log');
                continue;
            }
            $code = strtoupper((string) ($event['payload']['code'] ?? ''));
            if (isset($required[$key]) && $code === $required[$key] && ($key !== 'vessel_departed' || ($event['payload']['description'] ?? '') === 'Vessel Departed')) {
                $done[$key] = true;
                if ($key === 'arrival_pod') {
                    $ts = strtotime((string) ($event['payload']['timestamp'] ?? ''));
                    if ($ts !== false) {
                        $arrivalTime = max($arrivalTime ?? $ts, $ts);
                    }
                }
            }
        }
        $allRequired = !in_array(false, $done, true) && !empty($patchResult['success']);
        if ($podOnly || !$delivery || !$allRequired || $errors) {
            if ($delivery && !$podOnly) $results[] = ['success'=>true,'skipped'=>true,'event_key'=>'carrier_delivery_destination','message'=>'Delivery deferred until required milestones succeed'];
            return ['can_close'=>$podOnly && $allRequired && !$errors, 'delivery_sent'=>false, 'required_events'=>$done,'errors'=>$errors,'send_results'=>$results,'http_code'=>$http];
        }
        // Validate all delivery dates before sending any delivery event.
        foreach ($delivery as $event) {
            $ts = strtotime((string) ($event['payload']['timestamp'] ?? ''));
            if ($arrivalTime === null || $ts === false || $ts < $arrivalTime || $ts > time()) {
                $results[] = ['success'=>true,'skipped'=>true,'event_key'=>'carrier_delivery_destination','message'=>'Invalid delivery date; waiting for corrected source data'];
                return ['can_close'=>false,'delivery_sent'=>false,'required_events'=>$done,'errors'=>$errors,'send_results'=>$results];
            }
        }
        if ($errors) {
            return ['can_close' => false, 'required_events' => $done,
                'errors' => $errors, 'send_results' => $results];
        }
        foreach ($delivery as $event) {
            $send = $this->log_and_send_event($syncRow, $event);
            $send['event_key'] = 'carrier_delivery_destination';
            $results[] = $send;
            if (empty($send['success'])) {
                $http = $send['http_code'] ?? $http;
                $errors[] = 'Delivery event failed: ' . ($send['message'] ?? 'see event log');
            }
        }
        return ['can_close' => !$errors, 'delivery_sent' => !$errors, 'http_code'=>$http, 'required_events' => $done,
            'errors' => $errors, 'send_results' => $results];
    }

    /** Offline inspection of latest stored mixed response. No fetch, patch, post or DB write here. */
    public function audit_sync_snapshot(array $syncRow)
    {
        $id = (int) $syncRow['id'];
        $snapshot = $this->CI->db->select('id, fetched_at, response_body')
            ->from('tv_mixed_tracking_snapshots')->where('sync_id', $id)
            ->order_by('fetched_at', 'DESC')->order_by('id', 'DESC')->limit(1)->get()->row_array();
        $logs = $this->CI->db->select('event_key, event_code, event_timestamp, tracking_number, status, response_http_code, sent_at')
            ->from('tv_event_logs')->where('sync_id', $id)->order_by('event_timestamp', 'ASC')->get()->result_array();
        $tracking = $this->CI->db->select('id_tracking, hbl_no, id_agent, connecting, consol_destination, destination, eta, ata, ata_by, dpol, atp, dft, atd, cargo_delivery, lt_actual_pod_arrival_at, lt_pod_eta_at, lt_pod_eta_source, ctl_actual_pod_confirmed_at, ctl_actual_second_departure_at')
            ->from('x_tracking_export')->where('hbl_no', $syncRow['hbl'])->where('id_shipper', $syncRow['shipper_id'])->get()->result_array();
        $mixed = $snapshot ? json_decode((string) $snapshot['response_body'], true) : null;
        $mappingInput = $mixed;
        if (is_array($mappingInput)) {
            foreach (['pol','pot','pod'] as $field) {
                if (empty($mappingInput['header'][0][0][$field]) && !empty($syncRow[$field])) $mappingInput['header'][0][0][$field] = $syncRow[$field];
            }
        }
        $mapped = is_array($mappingInput) && count($tracking) <= 1 ? $this->CI->transvoyant_event_mapper->map_merged_tracking_to_tv_events($syncRow['tracking_number'], $mappingInput, $tracking[0] ?? [], $this->poll_policy()) : [];
        return ['success' => is_array($mixed), 'sync_id' => $id,
            'tracking_number' => $syncRow['tracking_number'], 'sync_status' => $syncRow['sync_status'],
            'snapshot_id' => $snapshot['id'] ?? null, 'snapshot_fetched_at' => $snapshot['fetched_at'] ?? null,
            'required_codes' => $this->required_event_codes(), 'poll_policy'=>$this->poll_policy(), 'pod_only'=>$this->pod_only($syncRow), 'internal_tracking' => $tracking,
            'mixed_response' => $mixed, 'mapped_events' => $mapped, 'event_logs' => $logs,
            'message' => is_array($mixed) ? 'Stored snapshot preview (not live)' : 'No valid stored snapshot'];
    }

    public function run_scheduler($limit = 100)
    {
        $rows = $this->get_due_shipments($limit);

        $results = [];
        foreach ($rows as $row) {
            try {
                $results[] = $this->process_sync($row);
            } catch (Throwable $e) {
                $results[] = $this->technical_failure($row, [], $e->getMessage());
            }
        }

        return [
            'success' => !in_array(false, array_map(function($r){return !empty($r['success']);},$results), true),
            'count'   => count($rows),
            'results' => $results,
        ];
    }
}