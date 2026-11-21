<?php
class M_booking_list extends CI_Model
{
    /** @var CI_DB_query_builder */
    protected $replica;

    public function __construct()
    {
        parent::__construct();
        $this->replica = $this->load->database('replica', true);
    }

    public function get_bookings($shipperIds, $dateFrom = null, $dateTo = null)
    {
        $shipperIds = $this->normalizeShipperIds($shipperIds);
        if (!$shipperIds) {
            return [];
        }

        $this->replica
            ->select("
                b.booking_code AS no_from_shipper,
                b.house_bl AS hbl,
                MAX(bd.volume) AS measurement,
                b.expect_etd AS etd_jkt,
                b.eta,
                b.vessel AS feeder_vessel,
                b.origin_city AS origin,
                b.destination_city AS destination,
                'PREPARED' AS status_gocomet,
                b.mode AS type_of_trans,
                b.shipper_id AS id_shipper
            ", false)
            ->from('trnstuffingplan_booking b')
            ->join(
                'trnstuffingplan_booking_detail bd',
                'bd.booking_id = b.id',
                'left'
            )
            ->where_in('b.shipper_id', $shipperIds);

        if ($dateFrom) {
            $this->replica->where('b.expect_etd >=', $dateFrom);
        }
        if ($dateTo) {
            $this->replica->where('b.expect_etd <=', $dateTo);
        }

        return $this->replica
            ->group_by('b.id')
            ->order_by('b.expect_etd', 'DESC')
            ->get()
            ->result_array();
    }

    public function get_last_bookings($shipperIds, $dateFrom = null, $dateTo = null)
    {
        $shipperIds = $this->normalizeShipperIds($shipperIds);
        if (!$shipperIds) {
            return [];
        }

        $dateExpr = $this->stuffingDateExpr('sp.etd_jkt');

        $this->replica
            ->select("
                d.no_from_shipper,
                spd.hb_l AS hbl,
                spd.bl_meas AS measurement,
                sp.type_of_trans AS type_of_trans,
                sp.etd_jkt,
                sp.eta,
                d.feeder_vessel,
                CASE
                    WHEN sp.type_of_trans LIKE '%import%'
                    THEN spd.destination
                    ELSE sp.port_loading
                END AS origin,
                CASE
                    WHEN sp.type_of_trans LIKE '%import%'
                    THEN sp.destination
                    ELSE spd.destination
                END AS destination,
                sp.status_gocomet,
                spd.shipper AS id_shipper
            ", false)
            ->from('tbl_detail d')
            ->join('trnstuffingplandetail spd', 'd.detail_id = spd.sp_detail_id', 'inner')
            ->join('trnstuffingplan sp', 'spd.sp_id = sp.sp_id', 'inner')
            ->where_in('spd.shipper', $shipperIds);

        if ($dateFrom) {
            $this->replica->where(
                "{$dateExpr} >= " . $this->replica->escape($dateFrom),
                null,
                false
            );
        }

        if ($dateTo) {
            $this->replica->where(
                "{$dateExpr} <= " . $this->replica->escape($dateTo),
                null,
                false
            );
        }

        return $this->replica
            ->order_by('sp.etd_jkt', 'DESC')
            ->get()
            ->result_array();
    }

    public function get_combined_bookings($shipperIds, $dateFrom = null, $dateTo = null)
    {
        return [
            'bookings'      => $this->get_bookings($shipperIds, $dateFrom, $dateTo),
            'last_bookings' => $this->get_last_bookings($shipperIds, $dateFrom, $dateTo),
        ];
    }

    /**
     * Firmenich shipment performance.
     *
     * Date range basis: trnstuffingplan_booking.expect_etd.
     *
     * TT1 = Planned GI -> Actual Vessel Departure
     * TT2 = Actual Vessel Departure -> Final POD Arrival
     * TT3 = Planned GI -> Final POD Arrival
     *
     * Leadtime target source:
     * 1. firmenich_contract_lane.sap_leadtime_days
     * 2. firmenich_contract_lane.source_total_lead_time_days
     *
     * Planned GI source priority:
     * 1. x_tracking_export.lt_planned_gi_at
     * 2. trnstuffingplan_booking.pickup_datetime
     * 3. derived from ETD - contract pre_carriage_days
     *
     * Performance benchmark uses TT3 decimal days:
     * EARLY   : TT3 < SAP Leadtime
     * ON_TIME : TT3 = SAP Leadtime
     * LATE    : TT3 > SAP Leadtime
     */
    public function get_firmenich_performance($shipperId, $dateFrom, $dateTo)
    {
        $shipperId = (int) $shipperId;

        if (!in_array($shipperId, [4407, 6412], true)) {
            return [
                'rows' => [],
                'kpi'  => $this->emptyPerformanceKpi($dateFrom, $dateTo),
            ];
        }

        $rows = $this->replica
            ->select("
                b.id AS booking_id,
                b.booking_code,
                b.si_number AS shipment_number,
                b.house_bl AS hbl,
                b.origin_city AS pol,
                b.pol_code,
                b.destination_city AS pod,
                b.pod_code,
                b.expect_etd,
                b.pickup_datetime,

                x.id_tracking,
                x.lt_planned_gi_at,
                x.lt_actual_departure_at,
                x.lt_actual_pod_arrival_at,
                x.lt_tt1_days,
                x.lt_tt2_days,
                x.lt_tt3_days,
                x.dpol,
                x.atd,
                x.lt_status,
                x.lt_issue_note
            ", false)
            ->from('trnstuffingplan_booking b')
            ->join(
                'x_tracking_export x',
                "x.hbl_no = b.house_bl
                 AND CAST(x.id_shipper AS UNSIGNED) = b.shipper_id",
                'left',
                false
            )
            ->where('b.shipper_id', $shipperId);

        // All Period: jika tanggal null/kosong, jangan pasang filter tanggal.
        if ($dateFrom !== null && $dateFrom !== '') {
            $this->replica->where('b.expect_etd >=', $dateFrom);
        }
        if ($dateTo !== null && $dateTo !== '') {
            $this->replica->where('b.expect_etd <=', $dateTo);
        }

        $rows = $this->replica
            // Raw condition penuh. Jangan kirim empty string sebagai value
            // pada CI3 Query Builder karena dapat menghasilkan WHERE yang invalid.
            ->where("COALESCE(TRIM(b.house_bl), '') <> ''", null, false)
            ->order_by('b.expect_etd', 'DESC')
            ->order_by('b.id', 'DESC')
            ->get()
            ->result_array();

        $references = $this->loadSapLeadtimeReferences();
        $referenceSource = $references
            ? 'firmenich_contract_lane.sap_leadtime_days|source_total_lead_time_days'
            : 'firmenich_contract_lane:no_leadtime_reference';

        $result = [];

        foreach ($rows as $row) {
            /*
             * Match the nominated contract lane first because pre_carriage_days
             * is also used as the controlled fallback for Planned GI.
             */
            $plannedGi = null;
            $plannedGiSource = null;
            $derivedPlannedGi = false;

            $plannedGi = $this->parseDateTime($row['lt_planned_gi_at'] ?? null);
            if ($plannedGi instanceof DateTimeImmutable) {
                $plannedGiSource = 'lt_planned_gi_at';
            } else {
                $plannedGi = $this->parseDateTime($row['pickup_datetime'] ?? null);
                if ($plannedGi instanceof DateTimeImmutable) {
                    $plannedGiSource = 'pickup_datetime';
                }
            }

            /*
             * Controlled fallback:
             * when no Planned GI exists in source data, derive it from
             * booking ETD minus the nominated lane pre-carriage commitment.
             *
             * This value is used at runtime only; it does NOT update
             * x_tracking_export.lt_planned_gi_at.
             */
            if (
                !($plannedGi instanceof DateTimeImmutable)
                && $reference
                && isset($reference['pre_carriage_days'])
                && is_numeric($reference['pre_carriage_days'])
                && (float) $reference['pre_carriage_days'] >= 0
            ) {
                $etdForDerivedGi = $this->parseDateTime($row['expect_etd'] ?? null);

                if ($etdForDerivedGi instanceof DateTimeImmutable) {
                    $preCarriageDays = (float) $reference['pre_carriage_days'];
                    $plannedGi = $etdForDerivedGi->modify(
                        sprintf('-%s seconds', (string) round($preCarriageDays * 86400))
                    );

                    $plannedGiSource = 'derived_from_etd_contract';
                    $derivedPlannedGi = true;
                }
            }

            $actualDeparture = $this->firstValidDateTime([
                $row['lt_actual_departure_at'] ?? null,
                $this->extractJsonDate(
                    $row['dpol'] ?? null,
                    ['Actual Time of Departure (ATD)', 'ATD', 'Departure']
                ),
            ]);

            $actualArrival = $this->firstValidDateTime([
                $row['lt_actual_pod_arrival_at'] ?? null,
                $this->extractJsonDate(
                    $row['atd'] ?? null,
                    ['Actual Time of Arrival (ATA)', 'ATA', 'Arrival']
                ),
            ]);

            /*
             * Some carrier feeds populate the JSON "ATA" field with a future
             * predicted date. A future timestamp is not an actual arrival.
             * Keep it NULL until the date has really occurred / normalized
             * tracking has confirmed the POD arrival.
             */
            if (
                $actualArrival instanceof DateTimeInterface
                && $actualArrival > new DateTimeImmutable('now')
            ) {
                $actualArrival = null;
            }

            $tt1 = $this->validStoredDays($row['lt_tt1_days'] ?? null);
            if ($tt1 === null) {
                $tt1 = $this->diffDays($plannedGi, $actualDeparture);
            }

            $tt2 = $this->validStoredDays($row['lt_tt2_days'] ?? null);
            if ($tt2 === null) {
                $tt2 = $this->diffDays($actualDeparture, $actualArrival);
            }

            $tt3 = $this->validStoredDays($row['lt_tt3_days'] ?? null);
            if ($tt3 === null) {
                $tt3 = $this->diffDays($plannedGi, $actualArrival);
            }

            $reference = $this->matchSapLeadtimeReference(
                $references,
                $row['pol_code'] ?? '',
                $row['pol'] ?? '',
                $row['pod_code'] ?? '',
                $row['pod'] ?? '',
                $row['expect_etd'] ?? null
            );

            /*
             * Performance target:
             * 1. Prefer sap_leadtime_days when maintained.
             * 2. Fallback to source_total_lead_time_days from the nominated lane.
             *
             * This is required for lanes such as JPYOK where the contract row
             * has a valid total target but sap_leadtime_days is still NULL.
             */
            $sapLeadtime = null;
            $leadtimeSource = null;

            if (
                $reference
                && isset($reference['sap_leadtime_days'])
                && is_numeric($reference['sap_leadtime_days'])
                && (float) $reference['sap_leadtime_days'] > 0
            ) {
                $sapLeadtime = (float) $reference['sap_leadtime_days'];
                $leadtimeSource = 'sap_leadtime_days';
            } elseif (
                $reference
                && isset($reference['source_total_lead_time_days'])
                && is_numeric($reference['source_total_lead_time_days'])
                && (float) $reference['source_total_lead_time_days'] > 0
            ) {
                $sapLeadtime = (float) $reference['source_total_lead_time_days'];
                $leadtimeSource = 'source_total_lead_time_days';
            }

            /*
             * IMPORTANT:
             * Use the same TT3 decimal value that is displayed on Booking Page.
             * Do not recalculate with calendarDiffDays(), otherwise a displayed
             * TT3 such as 15.54 days can be classified using a different integer
             * calendar-day value.
             */
            $performanceDays = $tt3;

            $performance = 'NO_DATA';

            /*
             * Once the vessel has actually departed but no valid actual POD
             * arrival exists yet, the shipment is operationally IN_TRANSIT.
             * This is more accurate than NO_DATA and avoids treating a future
             * predicted ATA as an actual milestone.
             */
            if ($actualDeparture !== null && $actualArrival === null) {
                $performance = 'IN_TRANSIT';
            } elseif ($sapLeadtime === null) {
                $performance = 'NO_TARGET';
            } elseif ($performanceDays !== null) {
                $delta = round((float) $performanceDays - (float) $sapLeadtime, 4);

                if ($delta < 0) {
                    $performance = 'EARLY';
                } elseif (abs($delta) < 0.0001) {
                    $performance = 'ON_TIME';
                } else {
                    $performance = 'LATE';
                }
            }

            $result[] = [
                'booking_id'       => (int) ($row['booking_id'] ?? 0),
                'booking_code'     => (string) ($row['booking_code'] ?? ''),
                'shipment_number'  => trim((string) ($row['shipment_number'] ?? '')),
                'hbl'              => trim((string) ($row['hbl'] ?? '')),
                'pol'              => trim((string) ($row['pol'] ?? '')),
                'pol_code'         => trim((string) ($row['pol_code'] ?? '')),
                'pod'              => trim((string) ($row['pod'] ?? '')),
                'pod_code'         => trim((string) ($row['pod_code'] ?? '')),
                'expect_etd'       => $row['expect_etd'] ?? null,

                'planned_gi_at'       => $this->formatDateTime($plannedGi),
                'planned_gi_source'   => $plannedGiSource,
                'derived_planned_gi'  => $derivedPlannedGi,
                'departure_at'        => $this->formatDateTime($actualDeparture),
                'pod_arrival_at'      => $this->formatDateTime($actualArrival),

                'tt1_days'         => $tt1,
                'tt2_days'         => $tt2,
                'tt3_days'         => $tt3,

                'destination_country' => $reference['destination_country'] ?? null,
                'sap_pod_name'        => $reference['pod_name'] ?? null,
                'sap_pod_code'        => $reference['sap_pod_code'] ?? null,
                'sap_leadtime_days'   => $sapLeadtime,
                'leadtime_target_source' => $leadtimeSource,

                // Audit contract lane yang dipakai sebagai leadtime reference.
                'contract_lane_id'      => isset($reference['id'])
                    ? (int) $reference['id']
                    : null,
                'contract_version'      => $reference['contract_version'] ?? null,
                'contract_pol_code'     => $reference['pol_code'] ?? null,
                'contract_pod_code'     => $reference['pod_code'] ?? null,
                'contract_valid_from'   => $reference['valid_from'] ?? null,
                'contract_valid_to'     => $reference['valid_to'] ?? null,

                'performance_days'   => $performanceDays,
                'performance_status' => $performance,
                'is_in_transit'      => ($performance === 'IN_TRANSIT'),
                'arrival_is_actual'  => ($actualArrival !== null),

                'tracking_id'       => isset($row['id_tracking'])
                    ? (int) $row['id_tracking']
                    : null,
                'lt_status'         => $row['lt_status'] ?? null,
                'lt_issue_note'     => $row['lt_issue_note'] ?? null,
            ];
        }

        return [
            'rows' => $result,
            'kpi'  => $this->buildPerformanceKpi(
                $result,
                $dateFrom,
                $dateTo,
                $referenceSource
            ),
        ];
    }

    private function buildPerformanceKpi(array $rows, $dateFrom, $dateTo, $referenceSource)
    {
        $counts = [
            'EARLY'      => 0,
            'ON_TIME'    => 0,
            'LATE'       => 0,
            'IN_TRANSIT' => 0,
            'NO_DATA'    => 0,
            'NO_TARGET'  => 0,
        ];

        foreach ($rows as $row) {
            $status = (string) ($row['performance_status'] ?? 'NO_DATA');
            if (!array_key_exists($status, $counts)) {
                $status = 'NO_DATA';
            }
            $counts[$status]++;
        }

        $total = count($rows);
        $comparable = $counts['EARLY'] + $counts['ON_TIME'] + $counts['LATE'];

        return [
            'date_from' => (string) $dateFrom,
            'date_to'   => (string) $dateTo,
            'date_basis'=> 'expect_etd',
            'performance_basis' => 'TT3 decimal days vs lane leadtime target',
            'reference_source'  => $referenceSource,
            'total_shipments'   => $total,
            'comparable_shipments' => $comparable,
            'coverage_rate' => $total > 0
                ? round(($comparable / $total) * 100, 2)
                : 0.0,
            'early' => [
                'count' => $counts['EARLY'],
                'percentage' => $comparable > 0
                    ? round(($counts['EARLY'] / $comparable) * 100, 2)
                    : 0.0,
            ],
            'on_time' => [
                'count' => $counts['ON_TIME'],
                'percentage' => $comparable > 0
                    ? round(($counts['ON_TIME'] / $comparable) * 100, 2)
                    : 0.0,
            ],
            'late' => [
                'count' => $counts['LATE'],
                'percentage' => $comparable > 0
                    ? round(($counts['LATE'] / $comparable) * 100, 2)
                    : 0.0,
            ],
            'in_transit' => $counts['IN_TRANSIT'],
            'no_data'    => $counts['NO_DATA'],
            'no_target'  => $counts['NO_TARGET'],
        ];
    }

    private function emptyPerformanceKpi($dateFrom, $dateTo)
    {
        return [
            'date_from' => (string) $dateFrom,
            'date_to' => (string) $dateTo,
            'date_basis' => 'expect_etd',
            'performance_basis' => 'TT3 decimal days vs lane leadtime target',
            'reference_source' => null,
            'total_shipments' => 0,
            'comparable_shipments' => 0,
            'coverage_rate' => 0.0,
            'early' => ['count' => 0, 'percentage' => 0.0],
            'on_time' => ['count' => 0, 'percentage' => 0.0],
            'late' => ['count' => 0, 'percentage' => 0.0],
            'in_transit' => 0,
            'no_data' => 0,
            'no_target' => 0,
        ];
    }

    /**
     * Load SAP Leadtime dari master lane yang SUDAH ada.
     *
     * Leadtime target priority:
     * 1. firmenich_contract_lane.sap_leadtime_days
     * 2. firmenich_contract_lane.source_total_lead_time_days
     *
     * sap_pod_code is optional. Exact contract pod_code and aliases are enough
     * to match lanes such as JPYOK / YOKOHAMA.
     */
    private function loadSapLeadtimeReferences()
    {
        $table = 'firmenich_contract_lane';

        if (!$this->replica->table_exists($table)) {
            return [];
        }

        if (!$this->replica->field_exists('source_total_lead_time_days', $table)) {
            log_message(
                'error',
                'Firmenich source_total_lead_time_days missing on firmenich_contract_lane.'
            );
            return [];
        }

        $hasSapPodCode = $this->replica->field_exists('sap_pod_code', $table);
        $hasSapLeadtime = $this->replica->field_exists('sap_leadtime_days', $table);

        $sapPodSelect = $hasSapPodCode
            ? 'sap_pod_code'
            : 'NULL AS sap_pod_code';

        $sapLeadtimeSelect = $hasSapLeadtime
            ? 'sap_leadtime_days'
            : 'NULL AS sap_leadtime_days';

        $leadtimeWhere = $hasSapLeadtime
            ? 'COALESCE(sap_leadtime_days, source_total_lead_time_days) > 0'
            : 'source_total_lead_time_days > 0';

        $rows = $this->replica
            ->select("
                id,
                contract_version,
                valid_from,
                valid_to,
                mode,
                shipment_type,
                delivery_type,
                service_type,
                pol_code,
                pod_code,
                pol_name,
                pod_name,
                pol_country,
                pod_country AS destination_country,
                pol_aliases_json,
                pod_aliases_json,
                pre_carriage_days,
                source_total_lead_time_days,
                {$sapPodSelect},
                {$sapLeadtimeSelect}
            ", false)
            ->from($table)
            ->where('is_active', 1)
            ->where($leadtimeWhere, null, false)
            ->order_by('valid_from', 'DESC')
            ->order_by('id', 'ASC')
            ->get()
            ->result_array();

        foreach ($rows as &$row) {
            $polAliases = json_decode(
                (string) ($row['pol_aliases_json'] ?? ''),
                true
            );
            $podAliases = json_decode(
                (string) ($row['pod_aliases_json'] ?? ''),
                true
            );

            $row['pol_aliases'] = is_array($polAliases) ? $polAliases : [];
            $row['pod_aliases'] = is_array($podAliases) ? $podAliases : [];

            $row['sap_leadtime_days'] = (
                isset($row['sap_leadtime_days'])
                && is_numeric($row['sap_leadtime_days'])
                && (float) $row['sap_leadtime_days'] > 0
            )
                ? (float) $row['sap_leadtime_days']
                : null;

            $row['pre_carriage_days'] = (
                isset($row['pre_carriage_days'])
                && is_numeric($row['pre_carriage_days'])
                && (float) $row['pre_carriage_days'] >= 0
            )
                ? (float) $row['pre_carriage_days']
                : null;

            $row['source_total_lead_time_days'] = (
                isset($row['source_total_lead_time_days'])
                && is_numeric($row['source_total_lead_time_days'])
                && (float) $row['source_total_lead_time_days'] > 0
            )
                ? (float) $row['source_total_lead_time_days']
                : null;
        }
        unset($row);

        return $rows;
    }

    /**
     * Match lane berdasarkan:
     * 1. contract validity date
     * 2. POL
     * 3. POD exact code
     * 4. POD aliases
     * 5. POD name fallback
     *
     * Ini sengaja mempertahankan UN/LOCODE existing, misalnya:
     * IDJKT -> PHMNL.
     * sap_pod_code hanya kode pendek untuk display/reference SAP.
     */
    private function matchSapLeadtimeReference(
        array $refs,
        $rawPolCode,
        $rawPolName,
        $rawPodCode,
        $rawPodName,
        $shipmentDate = null
    ) {
        if (!$refs) {
            return null;
        }

        $eligible = [];

        foreach ($refs as $ref) {
            if (!$this->referenceValidForDate($ref, $shipmentDate)) {
                continue;
            }

            if (!$this->referenceMatchesPol($ref, $rawPolCode, $rawPolName)) {
                continue;
            }

            $eligible[] = $ref;
        }

        if (!$eligible) {
            return null;
        }

        $podCode = strtoupper(trim((string) $rawPodCode));
        $podCode3 = strlen($podCode) >= 3
            ? substr($podCode, -3)
            : $podCode;

        $podName = $this->normalizeText($rawPodName);

        // 1) Exact contract POD code: PHMNL == PHMNL
        foreach ($eligible as $ref) {
            $refCode = strtoupper(trim((string) ($ref['pod_code'] ?? '')));

            if ($podCode !== '' && $refCode === $podCode) {
                return $ref;
            }
        }

        // 2) SAP short code or POD alias code.
        foreach ($eligible as $ref) {
            $sapCode = strtoupper(trim((string) ($ref['sap_pod_code'] ?? '')));

            if ($podCode3 !== '' && $sapCode !== '' && $sapCode === $podCode3) {
                return $ref;
            }

            foreach (($ref['pod_aliases'] ?? []) as $alias) {
                $aliasRaw = strtoupper(trim((string) $alias));
                if ($aliasRaw === '') {
                    continue;
                }

                $alias3 = strlen($aliasRaw) >= 3
                    ? substr($aliasRaw, -3)
                    : $aliasRaw;

                if (
                    $podCode !== ''
                    && ($aliasRaw === $podCode || $alias3 === $podCode3)
                ) {
                    return $ref;
                }
            }
        }

        // 3) Name fallback.
        foreach ($eligible as $ref) {
            $candidates = array_merge(
                [(string) ($ref['pod_name'] ?? '')],
                $ref['pod_aliases'] ?? []
            );

            foreach ($candidates as $candidate) {
                $candidateNorm = $this->normalizeText($candidate);

                if ($candidateNorm === '' || $podName === '') {
                    continue;
                }

                if (
                    $candidateNorm === $podName
                    || strpos($podName, $candidateNorm) !== false
                    || strpos($candidateNorm, $podName) !== false
                ) {
                    return $ref;
                }
            }
        }

        return null;
    }

    private function referenceValidForDate(array $ref, $shipmentDate)
    {
        $shipmentDate = trim((string) $shipmentDate);

        if ($shipmentDate === '') {
            return true;
        }

        $date = substr($shipmentDate, 0, 10);
        $validFrom = trim((string) ($ref['valid_from'] ?? ''));
        $validTo = trim((string) ($ref['valid_to'] ?? ''));

        if ($validFrom !== '' && $date < $validFrom) {
            return false;
        }

        if ($validTo !== '' && $date > $validTo) {
            return false;
        }

        return true;
    }

    private function referenceMatchesPol(array $ref, $rawPolCode, $rawPolName)
    {
        $rawCode = strtoupper(trim((string) $rawPolCode));
        $rawName = $this->normalizeText($rawPolName);

        // Jika shipment tidak punya POL, jangan gagalkan POD matching.
        if ($rawCode === '' && $rawName === '') {
            return true;
        }

        $refCode = strtoupper(trim((string) ($ref['pol_code'] ?? '')));
        if ($rawCode !== '' && $refCode !== '' && $rawCode === $refCode) {
            return true;
        }

        foreach (($ref['pol_aliases'] ?? []) as $alias) {
            $aliasRaw = strtoupper(trim((string) $alias));
            if ($rawCode !== '' && $aliasRaw === $rawCode) {
                return true;
            }

            $aliasName = $this->normalizeText($alias);
            if (
                $rawName !== ''
                && $aliasName !== ''
                && (
                    $aliasName === $rawName
                    || strpos($rawName, $aliasName) !== false
                    || strpos($aliasName, $rawName) !== false
                )
            ) {
                return true;
            }
        }

        $refName = $this->normalizeText($ref['pol_name'] ?? '');
        if (
            $rawName !== ''
            && $refName !== ''
            && (
                $refName === $rawName
                || strpos($rawName, $refName) !== false
                || strpos($refName, $rawName) !== false
            )
        ) {
            return true;
        }

        return false;
    }

    private function extractJsonDate($json, array $keys)
    {
        $json = trim((string) $json);
        if ($json === '' || $json === '[]' || $json === '{}') {
            return null;
        }

        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return null;
        }

        foreach ($keys as $key) {
            if (isset($decoded[$key]) && trim((string) $decoded[$key]) !== '') {
                return $decoded[$key];
            }
        }

        return null;
    }

    private function firstValidDateTime(array $values)
    {
        foreach ($values as $value) {
            $dt = $this->parseDateTime($value);
            if ($dt instanceof DateTimeImmutable) {
                return $dt;
            }
        }

        return null;
    }

    private function parseDateTime($value)
    {
        if ($value instanceof DateTimeImmutable) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return new DateTimeImmutable($value->format('Y-m-d H:i:s'));
        }

        $value = trim((string) $value);
        if ($value === ''
            || $value === '0000-00-00'
            || $value === '0000-00-00 00:00:00'
            || strtolower($value) === 'null') {
            return null;
        }

        $formats = [
            '!Y-m-d H:i:s',
            '!Y-m-d H:i',
            '!Y-m-d',
            '!d/m/Y H:i:s',
            '!d/m/Y H:i',
            '!d/m/Y',
            '!Ymd',
        ];

        foreach ($formats as $format) {
            $dt = DateTimeImmutable::createFromFormat($format, $value);
            $errors = DateTimeImmutable::getLastErrors();

            if ($dt instanceof DateTimeImmutable
                && ($errors === false || (
                    (int) $errors['warning_count'] === 0
                    && (int) $errors['error_count'] === 0
                ))) {
                return $dt;
            }
        }

        return null;
    }

    private function validStoredDays($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_numeric($value)) {
            return null;
        }

        $number = (float) $value;
        return $number >= 0 ? round($number, 2) : null;
    }

    private function diffDays($from, $to)
    {
        if (!($from instanceof DateTimeInterface)
            || !($to instanceof DateTimeInterface)
            || $to < $from) {
            return null;
        }

        $seconds = $to->getTimestamp() - $from->getTimestamp();
        return round($seconds / 86400, 2);
    }

    private function calendarDiffDays($from, $to)
    {
        if (!($from instanceof DateTimeInterface)
            || !($to instanceof DateTimeInterface)
            || $to < $from) {
            return null;
        }

        $fromDate = new DateTimeImmutable($from->format('Y-m-d'));
        $toDate = new DateTimeImmutable($to->format('Y-m-d'));

        return (int) $fromDate->diff($toDate)->format('%a');
    }

    private function formatDateTime($value)
    {
        return $value instanceof DateTimeInterface
            ? $value->format('Y-m-d H:i:s')
            : null;
    }

    private function normalizeText($value)
    {
        $value = strtoupper(trim((string) $value));
        $value = preg_replace('/[^A-Z0-9]+/', ' ', $value);
        return trim(preg_replace('/\s+/', ' ', $value));
    }

    private function stuffingDateExpr($field)
    {
        return "(
            CASE
                WHEN {$field} REGEXP '^[0-9]{8}$'
                    THEN STR_TO_DATE({$field}, '%Y%m%d')
                WHEN {$field} REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}$'
                    THEN STR_TO_DATE({$field}, '%Y-%m-%d')
                WHEN {$field} REGEXP '^[0-9]{2}/[0-9]{2}/[0-9]{4}$'
                    THEN STR_TO_DATE({$field}, '%d/%m/%Y')
                ELSE NULL
            END
        )";
    }

    private function normalizeShipperIds($shipperIds)
    {
        if (!is_array($shipperIds)) {
            $shipperIds = [$shipperIds];
        }

        $normalized = [];
        foreach ($shipperIds as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $normalized[$id] = $id;
            }
        }

        return array_values($normalized);
    }
}
