<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Transvoyant_event_mapper
{
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->helper('transvoyant');
        $this->CI->load->helper('tracking_route');
    }

    protected function first_header(array $merged)
    {
        return (isset($merged['header'][0][0]) && is_array($merged['header'][0][0]))
            ? $merged['header'][0][0]
            : [];
    }

    protected function event_list(array $merged)
    {
        return isset($merged['event list']) && is_array($merged['event list']) ? $merged['event list'] : [];
    }

    protected function stepper(array $merged)
    {
        return isset($merged['stepper']) && is_array($merged['stepper']) ? $merged['stepper'] : [];
    }

    protected function find_event_list(array $eventList, array $rules)
    {
        foreach ($eventList as $row) {
            if (!$this->actual_row_timestamp($row)) continue;
            $eventName    = $row['event name'] ?? '';
            $locationName = $row['location name'] ?? '';
            $portName     = $row['port name'] ?? '';

            $ok = true;

            if (!empty($rules['event_name_contains']) && !tv_contains_any($eventName, $rules['event_name_contains'])) {
                $ok = false;
            }

            if ($ok && !empty($rules['location_name_contains']) && !tv_contains_any($locationName, $rules['location_name_contains'])) {
                $ok = false;
            }

            if ($ok && !empty($rules['port_name_contains']) && !tv_contains_any($portName, $rules['port_name_contains'])) {
                $ok = false;
            }

            if ($ok) {
                return $row;
            }
        }

        return null;
    }

    protected function find_stepper(array $stepper, array $rules)
    {
        foreach ($stepper as $row) {
            if (!$this->actual_row_timestamp($row)) continue;
            $eventName    = $row['event name'] ?? '';
            $locationType = $row['location type'] ?? '';
            $location     = $row['location'] ?? '';

            $ok = true;

            if (!empty($rules['event_name_contains']) && !tv_contains_any($eventName, $rules['event_name_contains'])) {
                $ok = false;
            }

            if ($ok && !empty($rules['location_type_contains']) && !tv_contains_any($locationType, $rules['location_type_contains'])) {
                $ok = false;
            }

            if ($ok && !empty($rules['location_contains']) && !tv_contains_any($location, $rules['location_contains'])) {
                $ok = false;
            }

            if ($ok) {
                return $row;
            }
        }

        return null;
    }
	
	/**
	 * Cari ACTUAL arrival di final POD.
	 *
	 * Jangan gunakan:
	 * - predicted arrival
	 * - estimated arrival
	 * - generic "arrival"
	 * - transshipment arrival
	 */
    protected function actual_timestamp($raw)
    {
        $raw = trim((string)$raw);
        if ($raw === '' || preg_match('/estimated|predicted|planned|0000-00-00|00\/00\/0000/i', $raw)) return null;
        if (preg_match('/^(?:\d{2}\/\d{2}\/\d{4}|\d{4}-\d{2}-\d{2}|\d{8})$/D', $raw)) {
            return $this->local_actual_timestamp($raw);
        }
        $ts = tv_parse_datetime_flexible($raw);
        $unix = $ts ? strtotime($ts) : false;
        return $unix !== false && $unix > 0 && $unix <= time() ? $ts : null;
    }

    protected function actual_row_timestamp(array $row)
    {
        $label = implode(' ', array_map('strval', array_filter([
            $row['event name'] ?? '', $row['location type'] ?? '', $row['event type'] ?? '', $row['status'] ?? ''
        ], 'is_scalar')));
        if (preg_match('/estimated|predicted|planned|scheduled/i', $label)) return null;
        return $this->actual_timestamp($row['event time'] ?? '');
    }

    protected function find_actual_pod_row(array $eventList, array $stepper, $direct = false)
    {
        foreach (['arrival at final port of destination', 'shipment arrived at destination port'] as $prefix) {
            foreach ($eventList as $row) {
                if (strpos(tv_norm($row['event name'] ?? ''), $prefix) === 0 && $this->actual_row_timestamp($row)) return $row;
            }
        }
        foreach ($stepper as $row) {
            if (strpos(tv_norm($row['location type'] ?? ''), 'arrival at destination') !== false
                && $this->actual_row_timestamp($row)) return $row;
        }
        return null;
    }

    protected function local_actual_timestamp($raw)
    {
        $raw = trim((string)$raw);
        foreach (['!d/m/Y H:i:s','!d/m/Y','!Y-m-d H:i:s','!Y-m-d','!Ymd'] as $format) {
            $d = DateTimeImmutable::createFromFormat($format, $raw);
            $err = DateTimeImmutable::getLastErrors();
            if ($d && (!$err || (!$err['warning_count'] && !$err['error_count'])) && $d->format(ltrim($format,'!')) === $raw && $d->format('Y') > 1970 && $d->getTimestamp() <= time()) return $d->format(DATE_ATOM);
        }
        return null;
    }

    protected function local_json_date(array $local, $field, $key)
    {
        $json = json_decode((string)($local[$field] ?? ''), true);
        return is_array($json) ? $this->local_actual_timestamp($json[$key] ?? '') : null;
    }

    protected function local_pod_timestamp(array $local, $direct, $ctlTransit, array $policy = [])
    {
        if ($direct) {
            return $this->local_actual_timestamp($local['ata'] ?? '')
                ?: $this->local_json_date($local, 'atd', 'Actual Time of Arrival (ATA)')
                ?: $this->local_actual_timestamp($local['lt_actual_pod_arrival_at'] ?? '');
        }
        if ($ctlTransit) {
            // Legacy CTL atd could contain ETA. Only use separately recorded actual;
            // proxy/backfill values must be verified by operations or refreshed by CTL.
            $actual = $this->local_actual_timestamp($local['lt_actual_pod_arrival_at'] ?? '');
            $confirmed = $this->local_actual_timestamp($local['ctl_actual_pod_confirmed_at'] ?? '');
            $manualVerified = in_array((string)($local['hbl_no'] ?? ''), $policy['verified_ctl_final_arrival_hbls'] ?? [], true);
            return $actual && ($actual === $confirmed || $manualVerified) ? $actual : null;
        }
        return $this->local_json_date($local, 'atd', 'Actual Time of Arrival (ATA)')
            ?: $this->local_actual_timestamp($local['lt_actual_pod_arrival_at'] ?? '');
    }

    /**
     * Cari Out-Gate yang benar-benar terjadi di FINAL POD.
     *
     * Masalah yang dicegah:
     * shipment transshipment dapat mempunyai Gate Out di POT
     * sebelum kapal second leg berangkat. Event seperti itu tidak
     * boleh dikirim sebagai milestone final POD (-g30).
     *
     * Rule:
     * - kandidat harus event Gate Out aktual;
     * - bila ATA final POD tersedia, Out-Gate harus >= ATA final POD;
     * - bila ATA final POD belum tersedia dan shipment punya POT yang
     *   berbeda dari POD, jangan menebak Out-Gate final;
     * - pilih kandidat paling awal setelah ATA final POD.
     */
    protected function find_final_pod_outgate_row(
        array $eventList,
        $podTimestamp = null,
        $pot = null,
        $pod = null
    ) {
        $podUnix = null;

        if (!empty($podTimestamp)) {
            $parsed = strtotime($podTimestamp);
            if ($parsed !== false) {
                $podUnix = $parsed;
            }
        }

        $potCode = strtoupper(trim((string)$pot));
        $podCode = strtoupper(trim((string)$pod));

        $hasDistinctTransshipment = (
            $potCode !== ''
            && $podCode !== ''
            && $potCode !== $podCode
        );

        $candidates = [];

        foreach ($eventList as $row) {
            $eventName = tv_norm(
                (string)($row['event name'] ?? '')
            );

            $locationName = tv_norm(
                (string)($row['location name'] ?? '')
            );

            $eventTimeRaw = (string)($row['event time'] ?? '');
            $eventTimeNorm = tv_norm($eventTimeRaw);

            /*
             * Jangan terima predicted / estimated gate-out.
             */
            if (
                strpos($eventName, 'predicted') !== false
                || strpos($eventName, 'estimated') !== false
                || strpos($eventTimeNorm, 'predicted') !== false
                || strpos($eventTimeNorm, 'estimated') !== false
            ) {
                continue;
            }

            $isOutGate = (
                tv_contains_any(
                    $eventName,
                    [
                        'shipment gated out',
                        'get out',
                        'gate out',
                        'gated out',
                    ]
                )
                || tv_contains_any(
                    $locationName,
                    [
                        'gate out',
                        'gated out',
                    ]
                )
            );

            if (!$isOutGate) {
                continue;
            }

            $timestamp = $this->actual_timestamp(
                $eventTimeRaw
            );

            if (empty($timestamp)) {
                continue;
            }

            $eventUnix = strtotime($timestamp);
            if ($eventUnix === false || $eventUnix > time()) {
                continue;
            }

            /*
             * Kalau ATA final POD tersedia, final Out-Gate tidak
             * mungkin terjadi sebelum kapal tiba di final POD.
             */
            if (
                $podUnix !== null
                && $eventUnix < $podUnix
            ) {
                continue;
            }

            /*
             * Kalau shipment punya transshipment yang jelas
             * (POT != POD) tetapi ATA final POD belum tersedia,
             * kita tidak punya dasar aman untuk membedakan Gate Out
             * POT dengan Gate Out final POD.
             *
             * Lebih baik tidak kirim daripada salah mengirim Gate Out
             * transshipment sebagai milestone final destination.
             */
            if (
                $podUnix === null
                && $hasDistinctTransshipment
            ) {
                continue;
            }

            $candidates[] = [
                'row'       => $row,
                'timestamp' => $timestamp,
                'unix'      => $eventUnix,
            ];
        }

        if (empty($candidates)) {
            return null;
        }

        /*
         * Jika ada lebih dari satu Gate Out final,
         * ambil yang pertama setelah ATA final POD.
         */
        usort($candidates, function ($a, $b) {
            if ($a['unix'] === $b['unix']) {
                return 0;
            }

            return ($a['unix'] < $b['unix']) ? -1 : 1;
        });

        return $candidates[0]['row'];
    }

    protected function make_payload($trackingNumber, $code, $description, $timestamp, $locationCode = null, $locationName = null)
    {
        if (empty($trackingNumber) || empty($code) || empty($description) || empty($timestamp) || strtotime($timestamp) === false || strtotime($timestamp) > time()) {
            return null;
        }

        $payload = [
            'trackingNumber' => $trackingNumber,
            'code'           => $code,
            'description'    => $description,
            'timestamp'      => $timestamp,
        ];

        $location = array_filter([
            'code' => $locationCode,
            'name' => $locationName,
        ], function ($v) {
            return $v !== null && $v !== '';
        });

        if (!empty($location)) {
            $payload['location'] = $location;
        }

        return $payload;
    }

    public function map_merged_tracking_to_tv_events($rawBaseTrackingNumber, array $merged, array $local = [], array $policy = [])
    {
        $events = [];

        $baseTrackingNumber = tv_base_tracking_number($rawBaseTrackingNumber);
        if (empty($baseTrackingNumber)) {
            return [];
        }

        $header    = $this->first_header($merged);
        $eventList = $this->event_list($merged);
        $stepper   = $this->stepper($merged);

        $pol = tv_clean_location_code($header['pol'] ?? null);
		$pot = tv_clean_location_code($header['pot'] ?? null);
        $pod = tv_clean_location_code($header['pod'] ?? null);
        $hub = strtoupper(trim((string)($local['consol_destination'] ?? '')));
        $destination = strtoupper(trim((string)($local['destination'] ?? '')));
        $route = tracking_route_kind($local);
        $direct = $route === 'DIRECT' || ($route === 'UNKNOWN' && $pod && (!$pot || $pot === $pod));
        $localRouteKnown = $route !== 'UNKNOWN';
        $proxyEligible = tracking_proxy_source($local, (string)($local['lt_pod_eta_source'] ?? ''));
        $ctlTransit = !$direct && in_array($hub, ['HONG KONG','SINGAPORE'], true)
            && in_array(strtoupper(trim((string)($local['id_agent'] ?? ''))), ['CHAHKG','CHARTER SH','CTL - HKG','CTL - SIN','CTL HONGKO','CTL SIN','CTLSIN','CTLUSA'], true);
        $verifiedCtlDelivery = in_array((string)($local['hbl_no'] ?? ''), $policy['verified_ctl_final_delivery_hbls'] ?? [], true);


        $push = function($key, $payload, $sourceSystem = 'mixed', $sourceRef = null) use (&$events) {
            if (!$payload) return;

            $events[$key] = [
                'event_key'      => $key,
                'payload'        => $payload,
                'source_system'  => $sourceSystem,
                'source_ref'     => $sourceRef,
            ];
        };

        /**
         * 2. Carrier Pick-up from Origin
         * prioritas:
         * - location name contains empty pickup
         * - fallback event name Moving to Container Yard
         */
        $pickupRow = $this->find_event_list($eventList, [
            'location_name_contains' => ['empty pickup']
        ]);

        if (!$pickupRow) {
            $pickupRow = $this->find_event_list($eventList, [
                'event_name_contains' => ['moving to container yard']
            ]);
        }

        if ($pickupRow) {
            $push(
                'carrier_pickup_origin',
                $this->make_payload(
                    tv_tracking_number_with_suffix($baseTrackingNumber, '-g10'),
                    'DEPARTED',
                    'Carrier Pick-up from Origin',
                    $this->actual_timestamp($pickupRow['event time'] ?? ''),
                    $pol,
                    $pickupRow['port name'] ?? null
                ),
                'mixed',
                'event_list'
            );
        }

        /**
         * 3. Arrival at Port of Load (POL)
         * Gate In
         */
        $polRow = $this->find_event_list($eventList, [
            'location_name_contains' => ['gate in']
        ]);

        if ($polRow) {
            $push(
                'arrival_pol',
                $this->make_payload(
                    tv_tracking_number_with_suffix($baseTrackingNumber, '-g10'),
                    'ARRIVED',
                    'Arrival at Port of Load (POL)',
                    $this->actual_timestamp($polRow['event time'] ?? ''),
                    $pol,
                    $polRow['port name'] ?? null
                ),
                'mixed',
                'event_list'
            );
        }

        /**
         * 4. Loaded on Vessel
         */
        $loadedRow = $this->find_event_list($eventList, [
            'event_name_contains' => ['loaded on vessel']
        ]);

        if (!$loadedRow) {
            $loadedRow = $this->find_stepper($stepper, [
                'event_name_contains' => ['loaded on vessel']
            ]);
        }

        if ($loadedRow) {
            $push(
                'loaded_on_vessel',
                $this->make_payload(
                    tv_tracking_number_with_suffix($baseTrackingNumber),
                    'INFO',
                    'Loaded on Vessel',
                    $this->actual_timestamp($loadedRow['event time'] ?? ''),
                    $pol,
                    $loadedRow['port name'] ?? ($loadedRow['location'] ?? null)
                ),
                'mixed',
                'loaded'
            );
        }

        /**
         * 5. Vessel Departed
         */
        $departRow = $this->find_event_list($eventList, [
            'event_name_contains' => ['shipment departed', 'departure from origin port']
        ]);

        if (!$departRow) {
            $departRow = $this->find_stepper($stepper, [
                'location_type_contains' => ['origin departure']
            ]);
        }

        $departTs = $this->local_json_date($local, 'dpol', 'Actual Time of Departure (ATD)');
        if (!$departTs && $departRow) $departTs = $this->actual_row_timestamp($departRow);
        if ($departTs) {
            $push(
                'vessel_departed',
                $this->make_payload(
                    tv_tracking_number_with_suffix($baseTrackingNumber),
                    'INFO',
                    'Vessel Departed',
                    $departTs,
                    $pol,
                    $departRow['port name'] ?? ($departRow['location'] ?? null)
                ),
                'mixed',
                'depart'
            );
        }

        /**
         * Extra transhipment events => INFO
         */
        foreach ($eventList as $row) {
			$eventName    = $row['event name'] ?? '';
			$locationName = $row['location name'] ?? '';

			$isTransArrival =
				(
					tv_contains_any($eventName, [
						'shipment arrived at trans shipment port',
						'shipment arrived at transhipment port',
						'arrival at trans shipment port',
						'arrival at transhipment port'
					]) ||
					tv_contains_any($locationName, [
						'trans shipment arrival',
						'transshipment arrival'
					])
				);

			$isTransDeparture =
				(
					tv_contains_any($eventName, [
						'shipment departed from trans shipment port',
						'shipment departed from transhipment port',
						'departure from trans shipment port',
						'departure from transhipment port'
					]) ||
					tv_contains_any($locationName, [
						'trans shipment departure',
						'transshipment departure'
					])
				);

			$actualTransTs = $this->actual_row_timestamp($row);
            if ($proxyEligible && $isTransDeparture) {
                $dft = tracking_proxy_json($local, 'dft') ?? [];
                if (($dft['Departure Source'] ?? '') === 'AGENT_ETD_PROXY') {
                    $current = tracking_agent_date($dft['Estimated Departure from Transhipment Port'] ?? '', true);
                    // Preserve old event timestamp and dedup identity; reject stale mixed dates.
                    if (!$current || !$actualTransTs || date('Y-m-d', strtotime($actualTransTs)) !== $current->format('Y-m-d')) continue;
                }
            }
            $ctlVerifiedTs = $isTransArrival
                ? $this->local_json_date($local, 'atp', 'Arrival at Transhipment Port')
                : $this->local_actual_timestamp($local['ctl_actual_second_departure_at'] ?? '');
            $transVerified = !$ctlTransit || ($actualTransTs && $ctlVerifiedTs && date('Y-m-d',strtotime($actualTransTs)) === date('Y-m-d',strtotime($ctlVerifiedTs)));
            if (!$direct && $transVerified && ($isTransArrival || $isTransDeparture) && $actualTransTs) {
				$loc = $this->resolve_transshipment_location(
					$row,
					$stepper,
					$isTransArrival ? 'arrival' : 'departure'
				);

				// pakai header.pot sebagai code utama, fallback ke stepper/parser
				$transCode = $pot ?: ($loc['code'] ?? null);

				$key = 'extra_' . md5(json_encode($row));

				$push(
					$key,
					$this->make_payload(
						tv_tracking_number_with_suffix($baseTrackingNumber),
						'INFO',
						$row['event name'],
						$actualTransTs,
						$transCode,
						$loc['name'] ?? ($row['port name'] ?? null)
					),
					'mixed',
					'event_list'
				);
			}
		}

        /**
		 * =========================================================
		 * 6. Arrival at Port of Destination (POD)
		 * =========================================================
		 *
		 * IMPORTANT:
		 *
		 * Hanya ACTUAL final POD.
		 *
		 * Jangan kirim:
		 * - predicted arrival
		 * - estimated arrival
		 * - transshipment arrival
		 *
		 * ARRIVED digunakan agar event diperlakukan sebagai
		 * actual arrival, bukan informational event.
		 */
        $podRow = $this->find_actual_pod_row($eventList, $stepper, $direct);
        $podTimestamp = $localRouteKnown ? $this->local_pod_timestamp($local, $direct, $ctlTransit, $policy) : null;
        // For known CTL transit, old mixed/local events may simply reflect legacy ETA.
        $isProxy = false;
        if ($proxyEligible) {
            list($candidate, $isProxy) = tracking_proxy_arrival($local);
            $podTimestamp = $candidate ? $candidate->format(DATE_ATOM) : null;
        }
        if (!$podTimestamp && !$ctlTransit && !$proxyEligible && $podRow) $podTimestamp = $this->actual_row_timestamp($podRow);
        if ($podTimestamp && $pod && (!$departTs || strtotime($podTimestamp) >= strtotime($departTs))) {
            $push('arrival_pod', $this->make_payload(
                tv_tracking_number_with_suffix($baseTrackingNumber), 'ARRIVED',
                'Arrival at Port of Destination (POD)', $podTimestamp, $pod,
                $local['destination'] ?? ($podRow['port name'] ?? ($podRow['location'] ?? null))
            ), 'mixed', $isProxy ? $local['lt_pod_eta_source'] : 'arrival_pod');
        } else { $podTimestamp = null; }

        /**
         * 7. Unloaded from Vessel
         */
        $unloadedRow = $this->find_event_list($eventList, [
            'event_name_contains' => ['unloaded from vessel', 'discharged']
        ]);

        if (!$unloadedRow) {
            $unloadedRow = $this->find_stepper($stepper, [
                'event_name_contains' => ['unloaded from vessel', 'discharged']
            ]);
        }

        if ($unloadedRow) {
            $push(
                'unloaded_from_vessel',
                $this->make_payload(
                    tv_tracking_number_with_suffix($baseTrackingNumber),
                    'INFO',
                    'Unloaded from Vessel',
                    $this->actual_timestamp($unloadedRow['event time'] ?? ''),
                    $pod,
                    $unloadedRow['port name'] ?? ($unloadedRow['location'] ?? null)
                ),
                'mixed',
                'unloaded'
            );
        }

        /**
         * 8. Customs Cleared
         */
        $customsRow = $this->find_event_list($eventList, [
            'event_name_contains' => ['customs cleared']
        ]);

        if ($customsRow) {
            $push(
                'customs_cleared',
                $this->make_payload(
                    tv_tracking_number_with_suffix($baseTrackingNumber),
                    'INFO',
                    'Customs Cleared',
                    $this->actual_timestamp($customsRow['event time'] ?? ''),
                    $pod,
                    $customsRow['port name'] ?? null
                ),
                'mixed',
                'customs'
            );
        }

        /**
         * =========================================================
         * 9. Out-Gate FINAL POD
         * =========================================================
         *
         * Jangan ambil Gate Out transshipment.
         * Final Out-Gate harus terjadi setelah/sama dengan ATA
         * final POD apabila ATA tersedia.
         */
        $outgateRow = $this->find_final_pod_outgate_row(
            $eventList,
            $podTimestamp,
            $pot,
            $pod
        );

        if ($outgateRow && (!$ctlTransit || $verifiedCtlDelivery)) {
            $outgateTimestamp = $this->actual_timestamp(
                $outgateRow['event time'] ?? ''
            );

            if (!empty($outgateTimestamp)) {
                $push(
                    'out_gate',
                    $this->make_payload(
                        tv_tracking_number_with_suffix(
                            $baseTrackingNumber,
                            '-g30'
                        ),
                        'DEPARTED',
                        'Out-Gate',
                        $outgateTimestamp,
                        $pod,
                        $outgateRow['port name']
                            ?? ($podRow['port name'] ?? null)
                            ?? ($podRow['location'] ?? null)
                    ),
                    'mixed',
                    'outgate'
                );
            }
        }

        /**
         * 10. Carrier Delivery to Destination
         */
        $deliveryRow = null;
        $deliveryTs = $this->local_json_date($local, 'cargo_delivery', 'Cargo delivered');
        if (!$localRouteKnown || ($ctlTransit && !$verifiedCtlDelivery)) $deliveryTs = null;
        if ($deliveryTs && $podTimestamp && $pod && strtotime($deliveryTs) >= strtotime($podTimestamp)) {
            $push('carrier_delivery_destination', $this->make_payload(
                tv_tracking_number_with_suffix($baseTrackingNumber, '-g30'), 'ARRIVED',
                'Carrier Delivery to Destination', $deliveryTs, $pod,
                $local['destination'] ?? ($deliveryRow['port name'] ?? null)
            ), 'mixed', 'release_cargo');
        }

        /**
         * 11. Empty Container Returned
         */
        $emptyReturnRow = $this->find_event_list($eventList, [
            'event_name_contains' => ['empty container returned']
        ]);

        if ($emptyReturnRow) {
            $push(
                'empty_container_returned',
                $this->make_payload(
                    tv_tracking_number_with_suffix($baseTrackingNumber),
                    'INFO',
                    'Empty Container Returned',
                    $this->actual_timestamp($emptyReturnRow['event time'] ?? ''),
                    $pod,
                    $emptyReturnRow['port name'] ?? null
                ),
                'mixed',
                'empty_return'
            );
        }

        return array_values($events);
    }
	protected function resolve_transshipment_location(array $eventRow, array $stepper, $type = 'departure')
	{
		$eventTime = $this->actual_timestamp($eventRow['event time'] ?? '');
		$portName  = trim((string)($eventRow['port name'] ?? ''));

		$targetKeywords = ($type === 'arrival')
			? ['trans shipment arrival', 'transshipment arrival']
			: ['trans shipment departure', 'transshipment departure'];

		foreach ($stepper as $row) {
			$locationType = $row['location type'] ?? '';
			$rowTime      = $this->actual_timestamp($row['event time'] ?? '');

			if (
				tv_contains_any($locationType, $targetKeywords) &&
				!empty($eventTime) &&
				!empty($rowTime) &&
				$rowTime === $eventTime
			) {
				$locationText = trim((string)($row['location'] ?? ''));
				return [
					'code' => tv_extract_location_code($locationText),
					'name' => $portName !== '' ? $portName : $locationText,
				];
			}
		}

		foreach ($stepper as $row) {
			$locationType = $row['location type'] ?? '';
			if (tv_contains_any($locationType, $targetKeywords)) {
				$locationText = trim((string)($row['location'] ?? ''));
				return [
					'code' => tv_extract_location_code($locationText),
					'name' => $portName !== '' ? $portName : $locationText,
				];
			}
		}

		return [
			'code' => tv_extract_location_code($portName),
			'name' => $portName !== '' ? $portName : null,
		];
	}
}