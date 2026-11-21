<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Firmenich KPI Report - B..CY dataset builder.
 *
 * Design:
 * - x_tracking_export is the driving table: 1 tracking/HBL per row.
 * - Shipment operational data is read from the year DB (stuffing_gateway_YYYY).
 * - Contract lanes are read from an explicit schema (default: stuffing_gateway_2026).
 * - No cross-database SQL JOIN is required; contract rows are bulk-loaded and
 *   merged in PHP.
 * - Normalized tracking events are overlaid when the table exposes a usable
 *   shipment key. If it does not, the model safely uses verified local fallbacks.
 *
 * IMPORTANT:
 * x_tracking_export.pickup_cargo is treated as PLANNED GI / planned pickup,
 * not as actual Carrier Pick-up from Origin.
 */
class M_firmenich_kpi_report extends CI_Model
{
    const MODEL_VERSION = '3.5.0';
    /** @var CI_DB_query_builder */
    protected $dbx;

    /** @var CI_DB_query_builder */
    protected $dbref;

    protected $contractVersion = 'DSMF-OCEAN-2026-LCL-GATEWAY';
    protected $contractDatabase = 'stuffing_gateway_2026';

    protected $profiles = array();

    public function get_model_version()
    {
        return array(
            'version' => self::MODEL_VERSION,
            'file' => __FILE__,
            'mtime' => @filemtime(__FILE__) ?: null,
            'has_event_name_summary' => true,
            'has_source_priority_fix' => true
        );
    }

    public function __construct()
    {
        parent::__construct();

        $year = trim((string)$this->session->userdata('db'));

        if ($year !== '' && ctype_digit($year)) {
            $this->dbx = $this->load->database(switch_db($year), true);
        } else {
            $this->dbx = $this->load->database('master', true);
        }

        // Preferred shared/reference DB. load_contract_index() also checks the
        // active year DB, so the report still works when the contract table was
        // installed there.
        $this->dbref = $this->load->database('master', true);

        $this->config->load('firmenich_kpi_profile', true);
        $profileCfg = $this->config->item('firmenich_kpi_profile');
        if (is_array($profileCfg) && !empty($profileCfg['firmenich_kpi_profiles'])) {
            $this->profiles = $profileCfg['firmenich_kpi_profiles'];
        }

        if (
            is_array($profileCfg) &&
            !empty($profileCfg['contract_database']) &&
            preg_match('/^[A-Za-z0-9_]+$/', $profileCfg['contract_database'])
        ) {
            $this->contractDatabase = $profileCfg['contract_database'];
        }
    }

    /**
     * Main public method.
     *
     * @param array|int|string $shipperIds e.g. [4407, 6412]
     * @param string|null $dateFrom YYYY-MM-DD, filter by planned sailing
     * @param string|null $dateTo   YYYY-MM-DD, filter by planned sailing
     * @param bool $includeMeta add _meta diagnostics to every row
     * @return array
     */
    public function get_rows_b_to_bv($shipperIds, $dateFrom = null, $dateTo = null, $includeMeta = false)
    {
        $shipperIds = $this->normalize_shipper_ids($shipperIds);
        if (empty($shipperIds)) {
            return array();
        }

        $baseRows = $this->get_base_shipments($shipperIds, $dateFrom, $dateTo);
        if (empty($baseRows)) {
            return array();
        }

        $contracts = $this->load_contract_index($dateFrom, $dateTo);
        $mixedMap  = $this->load_mixed_event_map($baseRows);
        $localMap  = $this->load_tv_event_logs_map($baseRows);
        $eventMap  = $this->merge_event_maps($mixedMap, $localMap);

        $out = array();

        foreach ($baseRows as $raw) {
            $shipmentKey = $this->event_map_key_for_row($raw, $eventMap);
            $events = ($shipmentKey !== '' && isset($eventMap['rows'][$shipmentKey]))
                ? $eventMap['rows'][$shipmentKey]
                : array();

            $milestones = $this->build_milestones($raw, $events);

            $plannedSailing = $milestones['BK'];
            $contract = $this->find_contract_lane($raw, $contracts, $plannedSailing);

            $row = $this->build_b_to_bv_row($raw, $milestones, $contract);

            if ($includeMeta) {
                $row['_meta'] = array(
                    'model_version' => self::MODEL_VERSION,
                    'model_file' => __FILE__,
                    'id_tracking' => isset($raw['id_tracking']) ? $raw['id_tracking'] : null,
                    'no_job'      => isset($raw['no_job']) ? $raw['no_job'] : null,
                    'shipper_id'  => isset($raw['id_shipper']) ? $raw['id_shipper'] : null,
                    'contract_match' => !empty($contract),
                    'contract_pair' => !empty($contract)
                        ? trim($contract['pol_code']).'-'.trim($contract['pod_code'])
                        : null,
                    'contract_match_type' => !empty($contract['_match_type'])
                        ? $contract['_match_type']
                        : null,
                    'event_relation_field' => isset($eventMap['relation_field'])
                        ? $eventMap['relation_field']
                        : null,
                    'event_sources' => isset($eventMap['sources'])
                        ? $eventMap['sources']
                        : array(),
                    'tracking_event_count' => count($events),
                    'contract_source_database' => isset($contracts['_meta']['source_database'])
                        ? $contracts['_meta']['source_database'] : null,
                    'contract_rows_loaded' => isset($contracts['_meta']['rows_loaded'])
                        ? $contracts['_meta']['rows_loaded'] : 0,
                    'kpi_route' => $this->resolve_lt_route($row, $raw, $contract),
                );
            }

            $out[] = $row;
        }

        return $out;
    }


    /**
     * Explicit Stage 3 alias. Existing get_rows_b_to_bv() remains compatible
     * but now returns the full B..CY row.
     */
    public function get_rows_b_to_cy($shipperIds, $dateFrom = null, $dateTo = null, $includeMeta = false)
    {
        return $this->get_rows_b_to_bv($shipperIds, $dateFrom, $dateTo, $includeMeta);
    }

    /**
     * Pakistan monthly shipment table requested by Firmenich.
     *
     * The main KPI dataset remains unchanged. This method returns a second,
     * compact dataset that Google Apps Script writes into one tab per month.
     * Month assignment follows planned sailing / ETD, consistent with the
     * main report date filter.
     */
    public function get_pakistan_monthly_report($shipperIds, $dateFrom = null, $dateTo = null)
    {
        $shipperIds = $this->normalize_shipper_ids($shipperIds);
        if (empty($shipperIds)) {
            return array('periods' => array(), 'rows' => array());
        }

        $baseRows = $this->get_base_shipments($shipperIds, $dateFrom, $dateTo);
        if (empty($baseRows)) {
            return array(
                'periods' => $this->build_month_periods($dateFrom, $dateTo, array()),
                'rows' => array()
            );
        }

        $mixedMap = $this->load_mixed_event_map($baseRows);
        $localMap = $this->load_tv_event_logs_map($baseRows);
        $eventMap = $this->merge_event_maps($mixedMap, $localMap);
        $invoiceMap = $this->load_invoice_totals_by_job($baseRows);

        $rows = array();

        foreach ($baseRows as $raw) {
            if (!$this->is_pakistan_shipment($raw)) {
                continue;
            }

            $shipmentKey = $this->event_map_key_for_row($raw, $eventMap);
            $events = ($shipmentKey !== '' && isset($eventMap['rows'][$shipmentKey]))
                ? $eventMap['rows'][$shipmentKey]
                : array();

            $m = $this->build_milestones($raw, $events);

            $plannedSailing = $this->first_non_empty(array(
                $this->value($m, 'BK'),
                $this->clean_datetime($this->value($raw, 'expect_etd')),
                $this->clean_datetime($this->value($raw, 'etd_jkt')),
                $this->clean_datetime($this->value($raw, 'x_etd'))
            ));

            if ($plannedSailing === null) {
                continue;
            }

            $shipmentTs = strtotime((string)$plannedSailing);
            if ($shipmentTs === false) {
                continue;
            }

            $jobNo = trim((string)$this->value($raw, 'no_job'));
            $invoice = isset($invoiceMap[$jobNo])
                ? $invoiceMap[$jobNo]
                : $this->empty_invoice_summary();

            $actualLeadTime = $this->signed_days_between(
                $this->value($m, 'BL'),
                $this->value($m, 'BN')
            );

            // Negative lead time indicates invalid source chronology; do not publish it.
            if ($actualLeadTime !== null && $actualLeadTime < 0) {
                $actualLeadTime = null;
            }

            $shipmentType = strtoupper(trim((string)$this->first_non_empty(array(
                $this->value($raw, 'type_of_trans'),
                $this->value($raw, 'booking_mode'),
                'LCL'
            ))));

            $shippingMethod = 'OCEAN';
            if ($shipmentType !== '') {
                $shippingMethod .= ' - '.$shipmentType;
            }

            // Firmenich Shipment No mengikuti shipment/SAP ID numerik yang juga
            // dipakai pada KPI utama (kolom B), bukan HBL.
            $shipmentNo = $this->extract_dsmf_sap_id($raw);

            // Fallback ke SI Number booking jika shipment/SAP ID tidak tersedia.
            if ($shipmentNo === null || $shipmentNo === '') {
                $bookingSiNumber = trim((string)$this->value($raw, 'booking_si_number'));
                if ($bookingSiNumber !== '') {
                    $shipmentNo = ctype_digit($bookingSiNumber)
                        ? (0 + $bookingSiNumber)
                        : $bookingSiNumber;
                }
            }

            $volume = $this->nullable_number($this->first_non_empty(array(
                $this->value($raw, 'cbm'),
                $this->value($raw, 'bl_meas'),
                $this->value($raw, 'ship_meas')
            )));

            $rows[] = array(
                'month_key' => date('Y-m', $shipmentTs),
                'shipment_date' => date('Y-m-d', $shipmentTs),
                'shipment_no' => $shipmentNo,
                'job_no' => $jobNo,
                'shipping_method' => $shippingMethod,
                'lead_time_days' => $actualLeadTime !== null
                    ? round($actualLeadTime, 1)
                    : null,
                'lead_time_display' => $actualLeadTime !== null
                    ? $this->format_days_label($actualLeadTime)
                    : 'IN TRANSIT',
                'volume' => $volume,
                'invoice_total' => $invoice['single_currency_total'],
                'invoice_currency' => $invoice['single_currency'],
                'invoice_display' => $invoice['display'],
                'invoice_count' => $invoice['invoice_count'],
                'invoice_breakdown' => $invoice['breakdown']
            );
        }

        usort($rows, function($a, $b) {
            if ($a['shipment_date'] === $b['shipment_date']) {
                return strcasecmp((string)$a['shipment_no'], (string)$b['shipment_no']);
            }
            return strcmp((string)$a['shipment_date'], (string)$b['shipment_date']);
        });

        return array(
            'periods' => $this->build_month_periods($dateFrom, $dateTo, $rows),
            'rows' => $rows
        );
    }

    /**
     * Bulk-load invoice header totals. x_finance has one header row per invoice,
     * so we aggregate headers by job and currency without joining invoice items.
     * This prevents item-level multiplication / double counting.
     */
    protected function load_invoice_totals_by_job($baseRows)
    {
        if (!$this->dbx || !$this->dbx->table_exists('x_finance')) {
            return array();
        }

        $jobs = array();
        foreach ($baseRows as $row) {
            $job = trim((string)$this->value($row, 'no_job'));
            if ($job !== '') {
                $jobs[$job] = $job;
            }
        }

        if (empty($jobs)) {
            return array();
        }

        $result = array();

        foreach (array_chunk(array_values($jobs), 300) as $chunk) {
            $query = $this->dbx
                ->select('id, job_no, number, currency, grand_total, status', false)
                ->from('x_finance')
                ->where_in('job_no', $chunk)
                ->where('jenis', 'INVOICE')
                ->where('kepada', 'SHIPPER')
                ->get();

            if ($query === false) {
                continue;
            }

            foreach ($query->result_array() as $finance) {
                $status = strtoupper(trim((string)$this->value($finance, 'status')));
                if (in_array($status, array('CANCELLED', 'CANCELED', 'VOID', 'DELETED'), true)) {
                    continue;
                }

                $job = trim((string)$this->value($finance, 'job_no'));
                if ($job === '') {
                    continue;
                }

                $currency = strtoupper(trim((string)$this->value($finance, 'currency')));
                if ($currency === '') {
                    $currency = 'IDR';
                }

                $amount = $this->nullable_number($this->value($finance, 'grand_total'));
                if ($amount === null) {
                    continue;
                }

                if (!isset($result[$job])) {
                    $result[$job] = array(
                        'invoice_ids' => array(),
                        'breakdown' => array(),
                        'invoice_count' => 0
                    );
                }

                $invoiceId = trim((string)$this->value($finance, 'id'));
                if ($invoiceId !== '' && isset($result[$job]['invoice_ids'][$invoiceId])) {
                    continue;
                }
                if ($invoiceId !== '') {
                    $result[$job]['invoice_ids'][$invoiceId] = true;
                }

                if (!isset($result[$job]['breakdown'][$currency])) {
                    $result[$job]['breakdown'][$currency] = 0.0;
                }

                $result[$job]['breakdown'][$currency] += (float)$amount;
                $result[$job]['invoice_count']++;
            }
        }

        foreach ($result as $job => &$summary) {
            ksort($summary['breakdown']);
            $currencies = array_keys($summary['breakdown']);

            $summary['single_currency'] = count($currencies) === 1
                ? $currencies[0]
                : null;
            $summary['single_currency_total'] = count($currencies) === 1
                ? round((float)$summary['breakdown'][$currencies[0]], 2)
                : null;
            $summary['display'] = $this->invoice_breakdown_display($summary['breakdown']);

            unset($summary['invoice_ids']);
        }
        unset($summary);

        return $result;
    }

    protected function empty_invoice_summary()
    {
        return array(
            'single_currency' => null,
            'single_currency_total' => null,
            'display' => '',
            'invoice_count' => 0,
            'breakdown' => array()
        );
    }

    protected function invoice_breakdown_display($breakdown)
    {
        if (empty($breakdown)) {
            return '';
        }

        $parts = array();
        foreach ($breakdown as $currency => $amount) {
            $parts[] = strtoupper((string)$currency).' '.number_format((float)$amount, 2, '.', ',');
        }

        return implode(' / ', $parts);
    }

    protected function format_days_label($days)
    {
        $days = (float)$days;
        $rounded = round($days, 1);
        $display = (abs($rounded - round($rounded)) < 0.00001)
            ? (string)(int)round($rounded)
            : number_format($rounded, 1, '.', '');

        return $display.' Days';
    }

    protected function is_pakistan_shipment($raw)
    {
        $countryCandidates = array(
            $this->value($raw, 'destination_country'),
            $this->value($raw, 'lt_destination_country')
        );

        foreach ($countryCandidates as $country) {
            $key = $this->normalize_port_key($country);
            if ($key === 'PAKISTAN' || $key === 'PK') {
                return true;
            }
        }

        $podCode = strtoupper(trim((string)$this->value($raw, 'pod_code')));
        if (preg_match('/^PK[A-Z0-9]{3}$/', $podCode)) {
            return true;
        }

        $destinationText = $this->normalize_port_key(implode(' ', array_filter(array(
            $this->value($raw, 'destination_city'),
            $this->value($raw, 'sp_destination'),
            $this->value($raw, 'x_destination'),
            $this->value($raw, 'x_consol_destination')
        ))));

        foreach (array(
            'PAKISTAN', 'KARACHI', 'PORT QASIM', 'QASIM',
            'LAHORE', 'ISLAMABAD', 'FAISALABAD', 'SIALKOT'
        ) as $keyword) {
            if ($destinationText !== '' && strpos($destinationText, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    protected function build_month_periods($dateFrom, $dateTo, $rows)
    {
        $start = $this->is_iso_date($dateFrom) ? substr((string)$dateFrom, 0, 7).'-01' : null;
        $end = $this->is_iso_date($dateTo) ? substr((string)$dateTo, 0, 7).'-01' : null;

        if ($start === null || $end === null) {
            $keys = array();
            foreach ((array)$rows as $row) {
                $key = isset($row['month_key']) ? trim((string)$row['month_key']) : '';
                if (preg_match('/^\d{4}-\d{2}$/', $key)) {
                    $keys[$key] = $key;
                }
            }

            if (empty($keys)) {
                return array();
            }

            sort($keys);
            $keys = array_values($keys);
            $start = $keys[0].'-01';
            $end = $keys[count($keys) - 1].'-01';
        }

        $startTs = strtotime($start);
        $endTs = strtotime($end);
        if ($startTs === false || $endTs === false || $startTs > $endTs) {
            return array();
        }

        $periods = array();
        $cursor = new DateTime(date('Y-m-01', $startTs));
        $last = new DateTime(date('Y-m-01', $endTs));

        while ($cursor <= $last) {
            $key = $cursor->format('Y-m');
            $label = $cursor->format('M Y');
            $periods[] = array(
                'key' => $key,
                'label' => $label,
                'sheet_name' => 'PK '.$label
            );
            $cursor->modify('+1 month');
        }

        return $periods;
    }



    /**
     * Debug/validation helper: build exactly one HBL.
     *
     * @param string $hbl
     * @param array|int|string $shipperIds
     * @param bool $includeMeta
     * @return array|null
     */
    public function get_row_by_hbl($hbl, $shipperIds = array(4407, 6412), $includeMeta = true)
    {
        $hbl = trim((string)$hbl);
        if ($hbl === '') {
            return null;
        }

        $shipperIds = $this->normalize_shipper_ids($shipperIds);
        if (empty($shipperIds)) {
            return null;
        }

        $baseRows = $this->get_base_shipments($shipperIds, null, null, $hbl);
        if (empty($baseRows)) {
            return null;
        }

        // x_tracking_export.hbl_no is unique in the current schema.
        $raw = $baseRows[0];

        $contracts = $this->load_contract_index(null, null);
        $mixedMap  = $this->load_mixed_event_map($baseRows);
        $localMap  = $this->load_tv_event_logs_map($baseRows);
        $eventMap  = $this->merge_event_maps($mixedMap, $localMap);

        $shipmentKey = $this->event_map_key_for_row($raw, $eventMap);
        $events = ($shipmentKey !== '' && isset($eventMap['rows'][$shipmentKey]))
            ? $eventMap['rows'][$shipmentKey]
            : array();

        $milestones = $this->build_milestones($raw, $events);
        $contract = $this->find_contract_lane($raw, $contracts, $milestones['BK']);

        $row = $this->build_b_to_bv_row($raw, $milestones, $contract);

        if ($includeMeta) {
            $row['_meta'] = array(
                'model_version' => self::MODEL_VERSION,
                'model_file' => __FILE__,
                'id_tracking' => isset($raw['id_tracking']) ? $raw['id_tracking'] : null,
                'no_job'      => isset($raw['no_job']) ? $raw['no_job'] : null,
                'shipper_id'  => isset($raw['id_shipper']) ? $raw['id_shipper'] : null,
                'contract_match' => !empty($contract),
                'contract_pair' => !empty($contract)
                    ? trim($contract['pol_code']).'-'.trim($contract['pod_code'])
                    : null,
                'contract_match_type' => !empty($contract['_match_type'])
                    ? $contract['_match_type']
                    : null,
                'event_relation_field' => isset($eventMap['relation_field'])
                    ? $eventMap['relation_field']
                    : null,
                'event_sources' => isset($eventMap['sources'])
                    ? $eventMap['sources']
                    : array(),
                'tracking_event_count' => count($events),
                'base_rows_found' => count($baseRows),
                'contract_source_database' => isset($contracts['_meta']['source_database'])
                    ? $contracts['_meta']['source_database'] : null,
                'contract_rows_loaded' => isset($contracts['_meta']['rows_loaded'])
                    ? $contracts['_meta']['rows_loaded'] : 0,
                'contract_lookup_attempts' => isset($contracts['_meta']['attempts'])
                    ? $contracts['_meta']['attempts'] : array(),
                'contract_candidate_keys' => $this->contract_candidate_keys($raw),
            );
        }

        return $row;
    }

    /**
     * Query only verified existing fields.
     * x_tracking_export.no_job has an FK to trnstuffingplandetail.kode_ref.
     */
    protected function get_base_shipments($shipperIds, $dateFrom, $dateTo, $hbl = null)
    {
        $select = "
            x.id_tracking,
            x.no_job,
            x.id_shipper,
            x.id_request AS dsmf_sap_id,
            x.booking_code AS x_booking_code,
            x.mbl_no,
            x.hbl_no,
            x.cbm,
            x.container_no,
            x.feeder_name,
            x.liner,
            x.liner2nd,
            x.booking_received,
            x.pickup_cargo,
            x.received_date AS x_received_date,
            x.stuffing_date AS x_stuffing_date,
            x.gate_date AS x_gate_date,
            x.dpol,
            x.atp,
            x.dft,
            x.atd AS x_atd_json,
            x.cargo_delivery,
            x.etd AS x_etd,
            x.eta AS x_eta,
            x.ata AS x_ata,
            x.lt_planned_gi_at,
            x.lt_actual_departure_at,
            x.lt_pod_eta_at,
            x.lt_pod_eta_source,
            x.lt_actual_pod_arrival_at,
            x.lt_tt3_days,
            x.lt_destination_country,
            x.shipper AS x_shipper,
            x.consignee AS x_consignee,
            x.destination AS x_destination,
            x.consol_destination AS x_consol_destination,
            x.region_id AS x_region_id,

            spd.sp_detail_id,
            spd.sp_id,
            spd.booking_code AS spd_booking_code,
            spd.shipment_request_id AS detail_shipment_request_id,
            spd.hb_l AS detail_hbl,
            spd.weight,
            spd.bl_meas,
            spd.ship_meas,
            spd.shipper_print,
            spd.shipper AS detail_shipper_id,
            spd.consignee,
            spd.consignee_add,
            spd.incoterms AS detail_incoterms,
            spd.tanggal_bl,
            spd.received_date AS detail_received_date,
            spd.stuffing_date AS detail_stuffing_date,
            spd.gate_date AS detail_gate_date,

            sp.type_of_trans,
            sp.etd_jkt,
            sp.atd AS sp_atd,
            sp.eta AS sp_eta,
            sp.ata AS sp_ata,
            sp.type_cont_qty,
            sp.type_cont_size,
            sp.vessel,
            sp.voyage,
            sp.warehouse AS sp_warehouse,
            sp.carrier,
            sp.port_loading,
            sp.destination AS sp_destination,
            sp.cont_seal_no,
            sp.mother_bl,
            sp.region_id AS sp_region_id,

            b.id AS booking_id,
            b.booking_code,
            b.mode AS booking_mode,
            b.movement_type,
            b.incoterm AS booking_incoterm,
            b.origin_country,
            b.origin_city,
            b.destination_country,
            b.destination_city,
            b.pol_code,
            b.pod_code,
            b.cargo_type,
            b.trucking,
            b.pickup_location,
            b.drop_location,
            b.pickup_datetime,
            b.expect_etd,
            b.eta AS booking_eta,
            b.closing_date AS booking_closing_date,
            b.closing_cargo AS booking_closing_cargo,
            b.warehouse AS booking_warehouse,
            b.house_bl AS booking_house_bl,
            b.si_number AS booking_si_number,
            b.route_type,
            b.created_at AS booking_created_at
        ";

        $this->dbx->select($select, false);
        $this->dbx->from('x_tracking_export x');

        $this->dbx->join(
            'trnstuffingplandetail spd',
            'spd.kode_ref = x.no_job',
            'left'
        );

        $this->dbx->join(
            'trnstuffingplan sp',
            'sp.sp_id = spd.sp_id',
            'left'
        );

        // booking_code is the safest verified bridge to online/customer booking.
        // COALESCE permits older tracking rows whose x.booking_code was empty.
        $this->dbx->join(
            'trnstuffingplan_booking b',
            "b.booking_code = COALESCE(NULLIF(x.booking_code,''), NULLIF(spd.booking_code,''))",
            'left',
            false
        );

        $this->dbx->where_in('x.id_shipper', $shipperIds);

        if ($hbl !== null && trim((string)$hbl) !== '') {
            $this->dbx->where('x.hbl_no', trim((string)$hbl));
        }

        $dateExpr = "COALESCE(
            NULLIF(b.expect_etd, '0000-00-00'),
            STR_TO_DATE(NULLIF(sp.etd_jkt,''), '%Y%m%d'),
            STR_TO_DATE(NULLIF(x.etd,''), '%d/%m/%Y')
        )";

        if ($this->is_iso_date($dateFrom)) {
            $this->dbx->where(
                $dateExpr.' >= '.$this->dbx->escape($dateFrom),
                null,
                false
            );
        }

        if ($this->is_iso_date($dateTo)) {
            $this->dbx->where(
                $dateExpr.' <= '.$this->dbx->escape($dateTo),
                null,
                false
            );
        }

        $this->dbx->order_by($dateExpr, 'ASC', false);
        $this->dbx->order_by('x.id_tracking', 'ASC');

        return $this->dbx->get()->result_array();
    }

    /**
     * Load all relevant contracts once (no N+1 query).
     */
    protected function load_contract_index($dateFrom = null, $dateTo = null)
    {
        /*
         * Stage 2.5
         * ----------
         * Do NOT probe INFORMATION_SCHEMA / DATABASE() / table_exists().
         * The installation has already proven that a fully-qualified direct query
         * works:
         *
         *   stuffing_gateway_2026.firmenich_contract_lane
         *
         * So we query that table directly and treat a successful SELECT as proof
         * that the contract master is available.
         */
        $db = $this->dbx;

        $schema = trim((string)$this->contractDatabase);
        if ($schema === '' || !preg_match('/^[A-Za-z0-9_]+$/', $schema)) {
            $schema = 'stuffing_gateway_2026';
        }

        $rows = array();

        $attempt = array(
            'label' => 'direct-qualified-select',
            'configured_database' => isset($db->database) ? (string)$db->database : null,
            'contract_schema' => $schema,
            'table_exists' => null,
            'active_rows' => 0,
            'query_ok' => false,
            'error' => null
        );

        try {
            // Build the SQL with the schema explicitly qualified.
            // Values are escaped by the active CI database connection.
            $sql = "
                SELECT *
                FROM `{$schema}`.`firmenich_contract_lane`
                WHERE `contract_version` = ".$db->escape($this->contractVersion)."
                  AND `is_active` = 1
            ";

            if ($this->is_iso_date($dateFrom)) {
                $sql .= " AND `valid_to` >= ".$db->escape($dateFrom);
            }

            if ($this->is_iso_date($dateTo)) {
                $sql .= " AND `valid_from` <= ".$db->escape($dateTo);
            }

            $query = $db->query($sql);

            if ($query !== false) {
                $rows = $query->result_array();
                $attempt['query_ok'] = true;
                $attempt['table_exists'] = true;
                $attempt['active_rows'] = count($rows);
            } else {
                $attempt['table_exists'] = false;

                $dbError = method_exists($db, 'error') ? $db->error() : array();
                if (!empty($dbError['message'])) {
                    $attempt['error'] = $dbError['message'];
                } else {
                    $attempt['error'] = 'Direct contract SELECT returned false.';
                }
            }
        } catch (Exception $e) {
            $attempt['table_exists'] = false;
            $attempt['error'] = $e->getMessage();
        }

        $index = array(
            'by_code'  => array(),
            'by_alias' => array(),
            'rows'     => $rows,
            '_meta'    => array(
                'source_label' => !empty($rows) ? 'direct-qualified-select' : null,
                'source_database' => $schema,
                'rows_loaded' => count($rows),
                'attempts' => array($attempt)
            )
        );

        foreach ($rows as $row) {
            $polCode = strtoupper(trim((string)$row['pol_code']));
            $podCode = strtoupper(trim((string)$row['pod_code']));

            if ($polCode !== '' && $podCode !== '') {
                $key = $polCode.'|'.$podCode;

                if (!isset($index['by_code'][$key])) {
                    $index['by_code'][$key] = array();
                }

                $index['by_code'][$key][] = $row;
            }

            $polAliases = $this->decode_aliases(
                isset($row['pol_aliases_json']) ? $row['pol_aliases_json'] : null,
                array($row['pol_code'], $row['pol_name'])
            );

            $podAliases = $this->decode_aliases(
                isset($row['pod_aliases_json']) ? $row['pod_aliases_json'] : null,
                array($row['pod_code'], $row['pod_name'])
            );

            foreach ($polAliases as $pa) {
                foreach ($podAliases as $da) {
                    $aliasKey =
                        $this->normalize_port_key($pa).'|'.
                        $this->normalize_port_key($da);

                    if ($aliasKey === '|') {
                        continue;
                    }

                    if (!isset($index['by_alias'][$aliasKey])) {
                        $index['by_alias'][$aliasKey] = array();
                    }

                    $index['by_alias'][$aliasKey][] = $row;
                }
            }
        }

        return $index;
    }

    protected function find_contract_lane($raw, $index, $shipmentDate)
    {
        $polCode = strtoupper(trim((string)$this->value($raw, 'pol_code')));
        $podCode = strtoupper(trim((string)$this->value($raw, 'pod_code')));

        if ($polCode !== '' && $podCode !== '') {
            $key = $polCode.'|'.$podCode;

            if (!empty($index['by_code'][$key])) {
                $picked = $this->pick_valid_contract($index['by_code'][$key], $shipmentDate);
                if (!empty($picked)) {
                    $picked['_match_type'] = 'UNLOCODE';
                    return $picked;
                }
            }
        }

        // Historical rows frequently have blank UNLOCODE in booking.
        // Fallback uses aliases stored in the CONTRACT MASTER, not hard-coded lanes.
        $polNames = array(
            $this->value($raw, 'origin_city'),
            $this->value($raw, 'port_loading'),
            $this->value($raw, 'x_region_id'),
            $this->value($raw, 'sp_region_id')
        );

        $podNames = array(
            $this->value($raw, 'destination_city'),
            $this->value($raw, 'sp_destination'),
            $this->value($raw, 'x_destination'),
            $this->value($raw, 'x_consol_destination')
        );

        foreach ($polNames as $polName) {
            foreach ($podNames as $podName) {
                $key = $this->normalize_port_key($polName).'|'.$this->normalize_port_key($podName);

                if ($key !== '|' && !empty($index['by_alias'][$key])) {
                    $picked = $this->pick_valid_contract($index['by_alias'][$key], $shipmentDate);
                    if (!empty($picked)) {
                        $picked['_match_type'] = 'ALIAS';
                        return $picked;
                    }
                }
            }
        }

        return array();
    }

    protected function pick_valid_contract($candidates, $shipmentDate)
    {
        if (empty($candidates)) {
            return array();
        }

        $d = $this->to_date($shipmentDate);

        foreach ($candidates as $row) {
            $from = $this->to_date($row['valid_from']);
            $to   = $this->to_date($row['valid_to']);

            if ($d === null) {
                return $row;
            }

            if ($from !== null && $to !== null && $d >= $from && $d <= $to) {
                return $row;
            }
        }

        return array();
    }

    /**
     * Adaptive event reader.
     *
     * We intentionally do NOT invent the link schema of tv_mixed_tracking_events.
     * The method inspects available columns and only uses the table when a verified
     * relation field exists.
     */
    protected function load_mixed_event_map($baseRows)
    {
        $empty = array(
            'relation_field' => null,
            'relation_kind'  => 'canonical_hbl',
            'rows'           => array(),
            'sources'        => array()
        );

        $dbEvents = $this->db_for_table('tv_mixed_tracking_events');
        if (!$dbEvents) {
            return $empty;
        }

        $fields = $dbEvents->list_fields('tv_mixed_tracking_events');
        $fieldSet = array_fill_keys($fields, true);

        $timeField = null;
        foreach (array('event_time_at', 'event_timestamp', 'event_time') as $field) {
            if (isset($fieldSet[$field])) {
                $timeField = $field;
                break;
            }
        }

        $nameField = null;
        foreach (array('event_name', 'event_description', 'event_code') as $field) {
            if (isset($fieldSet[$field])) {
                $nameField = $field;
                break;
            }
        }

        if ($timeField === null || $nameField === null) {
            return $empty;
        }

        $identity = $this->shipment_identity_maps($baseRows);
        if (empty($identity['hbls'])) {
            return $empty;
        }

        // Strategy 1: event table already has an HBL/job relation.
        $relationCandidates = array(
            'hbl_no'          => 'hbl',
            'hbl'             => 'hbl',
            'house_bl'        => 'hbl',
            'tracking_number' => 'hbl',
            'no_job'          => 'job',
            'job_no'          => 'job',
            'reference_no'    => 'job'
        );

        foreach ($relationCandidates as $relationField => $kind) {
            if (!isset($fieldSet[$relationField])) {
                continue;
            }

            $keys = ($kind === 'hbl') ? $identity['hbls'] : $identity['jobs'];
            if (empty($keys)) {
                continue;
            }

            $rows = $this->read_event_rows(
                $dbEvents,
                'tv_mixed_tracking_events',
                $relationField,
                $keys,
                $nameField,
                $timeField,
                $fieldSet
            );

            $grouped = array();

            foreach ($rows as $event) {
                $rawKey = trim((string)$event['relation_value']);
                $hbl = ($kind === 'hbl')
                    ? $this->canonical_hbl($rawKey, $identity)
                    : (isset($identity['hbl_by_job'][$rawKey]) ? $identity['hbl_by_job'][$rawKey] : '');

                if ($hbl === '') {
                    continue;
                }

                if (!isset($grouped[$hbl])) {
                    $grouped[$hbl] = array();
                }

                $event['event_source_table'] = 'tv_mixed_tracking_events';
                $grouped[$hbl][] = $event;
            }

            if (!empty($grouped)) {
                return array(
                    'relation_field' => $relationField,
                    'relation_kind'  => 'canonical_hbl',
                    'rows'           => $grouped,
                    'sources'        => array('tv_mixed_tracking_events')
                );
            }
        }

        // Strategy 2: normalized events are keyed by sync_id.
        if (isset($fieldSet['sync_id'])) {
            $syncMap = $this->get_sync_id_to_hbl_map($identity['hbls']);

            if (!empty($syncMap)) {
                $syncIds = array_keys($syncMap);

                $rows = $this->read_event_rows(
                    $dbEvents,
                    'tv_mixed_tracking_events',
                    'sync_id',
                    $syncIds,
                    $nameField,
                    $timeField,
                    $fieldSet
                );

                $grouped = array();

                foreach ($rows as $event) {
                    $syncId = trim((string)$event['relation_value']);
                    $hbl = isset($syncMap[$syncId]) ? $syncMap[$syncId] : '';

                    if ($hbl === '') {
                        continue;
                    }

                    if (!isset($grouped[$hbl])) {
                        $grouped[$hbl] = array();
                    }

                    $event['event_source_table'] = 'tv_mixed_tracking_events';
                    $grouped[$hbl][] = $event;
                }

                if (!empty($grouped)) {
                    return array(
                        'relation_field' => 'sync_id via tv_shipment_syncs',
                        'relation_kind'  => 'canonical_hbl',
                        'rows'           => $grouped,
                        'sources'        => array('tv_mixed_tracking_events')
                    );
                }
            }
        }

        return $empty;
    }

    /**
     * Local Transvoyant event log overlay.
     * Booking Confirmation and other operational events can exist here even
     * when GoComet normalized events do not contain them.
     */
    protected function load_tv_event_logs_map($baseRows)
    {
        $empty = array(
            'relation_field' => null,
            'relation_kind'  => 'canonical_hbl',
            'rows'           => array(),
            'sources'        => array()
        );

        $dbEvents = $this->db_for_table('tv_event_logs');
        if (!$dbEvents) {
            return $empty;
        }

        $fields = $dbEvents->list_fields('tv_event_logs');
        $fieldSet = array_fill_keys($fields, true);

        if (!isset($fieldSet['sync_id'])) {
            return $empty;
        }

        $timeField = null;
        foreach (array('event_timestamp', 'event_time_at', 'event_time') as $field) {
            if (isset($fieldSet[$field])) {
                $timeField = $field;
                break;
            }
        }

        $nameField = null;
        foreach (array('event_description', 'event_name', 'event_code', 'event_key') as $field) {
            if (isset($fieldSet[$field])) {
                $nameField = $field;
                break;
            }
        }

        if ($timeField === null || $nameField === null) {
            return $empty;
        }

        $identity = $this->shipment_identity_maps($baseRows);
        $syncMap = $this->get_sync_id_to_hbl_map($identity['hbls']);

        if (empty($syncMap)) {
            return $empty;
        }

        $rows = $this->read_event_rows(
            $dbEvents,
            'tv_event_logs',
            'sync_id',
            array_keys($syncMap),
            $nameField,
            $timeField,
            $fieldSet
        );

        $grouped = array();

        foreach ($rows as $event) {
            $syncId = trim((string)$event['relation_value']);
            $hbl = isset($syncMap[$syncId]) ? $syncMap[$syncId] : '';

            if ($hbl === '') {
                continue;
            }

            // If event_description exists but is blank, append event_code as fallback.
            if (
                trim((string)$event['event_name']) === '' &&
                isset($event['event_code']) &&
                trim((string)$event['event_code']) !== ''
            ) {
                $event['event_name'] = str_replace('_', ' ', $event['event_code']);
            }

            if (!isset($grouped[$hbl])) {
                $grouped[$hbl] = array();
            }

            $event['event_source_table'] = 'tv_event_logs';
            $grouped[$hbl][] = $event;
        }

        return array(
            'relation_field' => 'sync_id via tv_shipment_syncs',
            'relation_kind'  => 'canonical_hbl',
            'rows'           => $grouped,
            'sources'        => empty($grouped) ? array() : array('tv_event_logs')
        );
    }

    protected function merge_event_maps($a, $b)
    {
        $out = array(
            'relation_field' => null,
            'relation_kind'  => 'canonical_hbl',
            'rows'           => array(),
            'sources'        => array()
        );

        foreach (array($a, $b) as $map) {
            if (empty($map) || empty($map['rows'])) {
                continue;
            }

            if ($out['relation_field'] === null && !empty($map['relation_field'])) {
                $out['relation_field'] = $map['relation_field'];
            }

            if (!empty($map['sources'])) {
                foreach ($map['sources'] as $source) {
                    $out['sources'][$source] = $source;
                }
            }

            foreach ($map['rows'] as $hbl => $events) {
                if (!isset($out['rows'][$hbl])) {
                    $out['rows'][$hbl] = array();
                }

                foreach ($events as $event) {
                    $out['rows'][$hbl][] = $event;
                }
            }
        }

        $out['sources'] = array_values($out['sources']);

        foreach ($out['rows'] as $hbl => &$events) {
            $deduped = array();
            $seenEvents = array();

            foreach ($events as $event) {
                $sourceTable = strtolower(trim((string)$this->value($event, 'event_source_table')));
                $eventDt = $this->clean_datetime($this->value($event, 'event_time_value'));

                // tv_event_logs can contain the same generated milestone repeatedly
                // on every poll. Collapse those repeats by business event + calendar day.
                // Mixed normalized events retain their full timestamp.
                $eventTimeKey = $eventDt;
                if ($sourceTable === 'tv_event_logs' && $eventDt !== null) {
                    $eventTimeKey = substr($eventDt, 0, 10);
                }

                $eventKey = $sourceTable.'|'.
                    strtolower(trim((string)$this->value($event, 'event_name'))).'|'.
                    (string)$eventTimeKey.'|'.
                    strtolower(trim((string)$this->value($event, 'location_name'))).'|'.
                    strtolower(trim((string)$this->value($event, 'port_name')));

                if (isset($seenEvents[$eventKey])) {
                    continue;
                }
                $seenEvents[$eventKey] = true;
                $deduped[] = $event;
            }

            $events = $deduped;

            usort($events, function($x, $y) {
                $aTime = isset($x['event_time_value']) ? strtotime((string)$x['event_time_value']) : 0;
                $bTime = isset($y['event_time_value']) ? strtotime((string)$y['event_time_value']) : 0;
                if ($aTime === $bTime) return 0;
                return ($aTime < $bTime) ? -1 : 1;
            });
        }
        unset($events);

        return $out;
    }

    protected function read_event_rows(
        $db,
        $table,
        $relationField,
        $keys,
        $nameField,
        $timeField,
        $fieldSet
    ) {
        $keys = array_values(array_unique(array_filter(array_map('strval', $keys))));
        if (empty($keys)) {
            return array();
        }

        $select = array(
            $relationField.' AS relation_value',
            $nameField.' AS event_name',
            $timeField.' AS event_time_value'
        );

        foreach (array(
            'event_source','location_name','port_name','vessel','container_no',
            'event_code','event_key','event_description','status','source_system',
            'remarks','carrier_event','display_event'
        ) as $optional) {
            if (isset($fieldSet[$optional]) && $optional !== $nameField) {
                $select[] = $optional;
            }
        }

        $rows = array();

        foreach (array_chunk($keys, 500) as $chunk) {
            $part = $db
                ->select(implode(',', $select), false)
                ->from($table)
                ->where_in($relationField, $chunk)
                ->get()
                ->result_array();

            if (!empty($part)) {
                $rows = array_merge($rows, $part);
            }
        }

        return $rows;
    }

    protected function shipment_identity_maps($baseRows)
    {
        $hbls = array();
        $jobs = array();
        $hblByJob = array();

        foreach ($baseRows as $row) {
            $hbl = trim((string)$this->first_non_empty(array(
                $this->value($row, 'hbl_no'),
                $this->value($row, 'detail_hbl'),
                $this->value($row, 'booking_house_bl')
            )));
            $job = trim((string)$this->value($row, 'no_job'));

            if ($hbl !== '') {
                $hbls[$hbl] = $hbl;
            }

            if ($job !== '') {
                $jobs[$job] = $job;
                if ($hbl !== '') {
                    $hblByJob[$job] = $hbl;
                }
            }
        }

        return array(
            'hbls' => array_values($hbls),
            'jobs' => array_values($jobs),
            'hbl_by_job' => $hblByJob
        );
    }

    protected function canonical_hbl($raw, $identity)
    {
        $raw = trim((string)$raw);
        if ($raw === '') {
            return '';
        }

        foreach ($identity['hbls'] as $hbl) {
            if (strcasecmp($raw, $hbl) === 0) {
                return $hbl;
            }
        }

        return '';
    }

    protected function get_sync_id_to_hbl_map($hbls)
    {
        $hbls = array_values(array_unique(array_filter(array_map('strval', $hbls))));
        if (empty($hbls)) {
            return array();
        }

        $dbSync = $this->db_for_table('tv_shipment_syncs');
        if (!$dbSync) {
            return array();
        }

        $fields = $dbSync->list_fields('tv_shipment_syncs');
        $fieldSet = array_fill_keys($fields, true);

        if (!isset($fieldSet['id'])) {
            return array();
        }

        $hblFields = array();
        foreach (array('hbl','tracking_number','customer_reference_no') as $field) {
            if (isset($fieldSet[$field])) {
                $hblFields[] = $field;
            }
        }

        if (empty($hblFields)) {
            return array();
        }

        $select = array('id');
        foreach ($hblFields as $field) {
            $select[] = $field;
        }

        $rows = array();

        foreach (array_chunk($hbls, 300) as $chunk) {
            $dbSync->select(implode(',', $select), false)->from('tv_shipment_syncs');
            $dbSync->group_start();
            foreach ($hblFields as $i => $field) {
                if ($i === 0) {
                    $dbSync->where_in($field, $chunk);
                } else {
                    $dbSync->or_where_in($field, $chunk);
                }
            }
            $dbSync->group_end();

            $part = $dbSync->get()->result_array();
            if (!empty($part)) {
                $rows = array_merge($rows, $part);
            }
        }

        $map = array();

        foreach ($rows as $row) {
            $matchedHbl = '';

            foreach ($hblFields as $field) {
                $candidate = trim((string)$this->value($row, $field));
                if ($candidate === '') {
                    continue;
                }

                foreach ($hbls as $hbl) {
                    if (strcasecmp($candidate, $hbl) === 0) {
                        $matchedHbl = $hbl;
                        break 2;
                    }
                }
            }

            if ($matchedHbl !== '') {
                $map[(string)$row['id']] = $matchedHbl;
            }
        }

        return $map;
    }

    protected function db_for_table($table)
    {
        if ($this->dbx && $this->dbx->table_exists($table)) {
            return $this->dbx;
        }

        if ($this->dbref && $this->dbref->table_exists($table)) {
            return $this->dbref;
        }

        return null;
    }

    protected function event_map_key_for_row($raw, $eventMap)
    {
        if (empty($eventMap['relation_kind'])) {
            return '';
        }

        if (
            $eventMap['relation_kind'] === 'hbl' ||
            $eventMap['relation_kind'] === 'canonical_hbl'
        ) {
            return trim((string)$this->first_non_empty(array(
                $this->value($raw, 'hbl_no'),
                $this->value($raw, 'detail_hbl'),
                $this->value($raw, 'booking_house_bl')
            )));
        }

        return trim((string)$this->value($raw, 'no_job'));
    }

    /**
     * Build milestone values used by AP..BV.
     * Actual pickup is intentionally event-driven.
     */
    protected function build_milestones($raw, $events)
    {
        $actualDeparture = $this->first_non_empty(array(
            $this->clean_datetime($this->value($raw, 'lt_actual_departure_at')),
            $this->extract_json_datetime(
                $this->value($raw, 'dpol'),
                array('Actual Time of Departure (ATD)', 'ATD')
            ),
            $this->clean_datetime($this->value($raw, 'sp_atd'))
        ));

        /*
         * Stage 3.4:
         * legacy x_tracking_export.atd JSON is ambiguous: old partner cron can
         * store final ETA under the ATA label. Do not use it as actual arrival.
         */
        $actualArrival = $this->first_non_empty(array(
            $this->clean_datetime($this->value($raw, 'lt_actual_pod_arrival_at')),
            $this->clean_datetime($this->value($raw, 'x_ata')),
            $this->clean_datetime($this->value($raw, 'sp_ata'))
        ));

        if ($actualArrival !== null && strtotime($actualArrival) > time()) {
            $actualArrival = null;
        }

        $plannedGi = $this->first_non_empty(array(
            $this->clean_datetime($this->value($raw, 'lt_planned_gi_at')),
            $this->extract_json_datetime(
                $this->value($raw, 'pickup_cargo'),
                array('Pickup Cargo', 'Planned GI', 'Planned Pickup')
            )
        ));

        $bookingReceived = $this->first_non_empty(array(
            $this->clean_datetime($this->value($raw, 'booking_received')),
            $this->clean_datetime($this->value($raw, 'booking_created_at'))
        ));

        $originCfsActual = $this->first_non_empty(array(
            $this->clean_datetime($this->value($raw, 'x_received_date')),
            $this->clean_datetime($this->value($raw, 'detail_received_date'))
        ));

        $originGateActual = $this->first_non_empty(array(
            $this->clean_datetime($this->value($raw, 'x_gate_date')),
            $this->clean_datetime($this->value($raw, 'detail_gate_date'))
        ));

        $plannedPickup = $this->first_non_empty(array(
            $plannedGi,
            $this->clean_datetime($this->value($raw, 'pickup_datetime'))
        ));

        $plannedDeparture = $this->first_non_empty(array(
            $this->clean_datetime($this->value($raw, 'expect_etd')),
            $this->clean_datetime($this->value($raw, 'etd_jkt')),
            $this->clean_datetime($this->value($raw, 'x_etd'))
        ));

        /*
         * Stage 3.4 - Final POD ETA.
         * lt_pod_eta_at is authoritative when available.
         * sp_eta/x_eta are allowed only for direct shipment because on
         * transshipment shipment they commonly represent ETA transshipment port.
         */
        $consolDestination = $this->normalize_port_key(
            $this->value($raw, 'x_consol_destination')
        );

        $finalDestination = $this->normalize_port_key(
            $this->first_non_empty(array(
                $this->value($raw, 'x_destination'),
                $this->value($raw, 'destination_city'),
                $this->value($raw, 'sp_destination')
            ))
        );

        $isTransshipment = (
            $consolDestination !== '' &&
            $finalDestination !== '' &&
            $consolDestination !== $finalDestination
        );

        $plannedArrivalCandidates = array(
            $this->clean_datetime($this->value($raw, 'lt_pod_eta_at')),
            $this->clean_datetime($this->value($raw, 'booking_eta'))
        );

        if (!$isTransshipment) {
            $plannedArrivalCandidates[] =
                $this->clean_datetime($this->value($raw, 'sp_eta'));

            $plannedArrivalCandidates[] =
                $this->clean_datetime($this->value($raw, 'x_eta'));
        }

        $plannedArrival = $this->first_non_empty($plannedArrivalCandidates);

        // Event overlay, when normalized tracking exposes a direct shipment key.
        $bookingConfirmation = $this->event_date(
            $events,
            array('booking confirmation'),
            'first'
        );

        $pickupActual = $this->event_date(
            $events,
            array(
                'carrier pick-up from origin',
                'carrier pickup from origin',
                'picked up from origin'
            ),
            'first'
        );

        $arrivalPol = $this->event_date(
            $events,
            array('arrival at port of load', 'arrival at pol'),
            'first'
        );

        // Prefer explicit normalized loaded event.
        $loadedOnVessel = $this->event_date_from_sources(
            $events,
            array(
                'loaded on vessel',
                'load on vessel',
                'loaded (fcl) on vessel',
                'vessel loaded'
            ),
            array('tv_mixed_tracking_events'),
            'first'
        );

        if ($loadedOnVessel === null) {
            $loadedOnVessel = $this->event_date_from_sources(
                $events,
                array(
                    'loaded on vessel',
                    'load on vessel',
                    'loaded (fcl) on vessel',
                    'vessel loaded'
                ),
                array('tv_event_logs'),
                'first'
            );
        }

        /*
         * For LCL, many carrier feeds do not expose a separate LOADED milestone.
         * At date granularity, normalized "Departure from origin port" is an
         * acceptable fallback for Loaded on Vessel because loading must have
         * completed by origin departure. We deliberately use ONLY mixed normalized
         * tracking for this proxy, never repeated tv_event_logs INFO events.
         */
        $shipmentTypeForMilestone = strtoupper(trim((string)$this->first_non_empty(array(
            $this->value($raw, 'type_of_trans'),
            $this->value($raw, 'booking_mode'),
            'LCL'
        ))));

        /*
         * Stage 2.8:
         * A carrier feed can expose two "Departure from origin port" milestones
         * for the same origin port:
         *
         *   first occurrence  = cargo/container loaded / initial port departure proxy
         *   last occurrence   = actual vessel departure used for KPI
         *
         * For the validated Manila sample this is:
         *   2026-07-02 -> AS Loaded on Vessel
         *   2026-07-05 -> AT Vessel Departed
         *
         * We only apply the first/last proxy when there is no explicit loaded event.
         */
        $mixedOriginDepartureFirst = $this->event_date_from_sources(
            $events,
            array(
                'departure from origin port',
                'origin departure',
                'departure from pol'
            ),
            array('tv_mixed_tracking_events'),
            'first'
        );

        $mixedOriginDepartureLast = $this->event_date_from_sources(
            $events,
            array(
                'departure from origin port',
                'origin departure',
                'departure from pol'
            ),
            array('tv_mixed_tracking_events'),
            'last'
        );

        if ($loadedOnVessel === null && $shipmentTypeForMilestone === 'LCL') {
            $loadedOnVessel = $mixedOriginDepartureFirst;
        }

        /*
         * Actual Vessel Departed source priority:
         *   1. LAST normalized origin-departure event
         *   2. explicit normalized Vessel Departed event
         *   3. normalized fact in x_tracking_export
         *   4. LAST local tv_event_logs Vessel Departed as final fallback
         *
         * Using LAST is intentional here. The diagnostic showed the origin feed
         * carrying an earlier 2-Jul event and the final departure on 5-Jul.
         */
        $vesselDeparted = $this->first_non_empty(array(
            $mixedOriginDepartureLast,
            $this->event_date_from_sources(
                $events,
                array('vessel departed'),
                array('tv_mixed_tracking_events'),
                'last'
            ),
            $actualDeparture,
            $this->event_date_from_sources(
                $events,
                array('vessel departed', 'origin departure', 'departure from pol'),
                array('tv_event_logs'),
                'last'
            )
        ));

        $arrivalPod = $this->first_non_empty(array(
            $this->event_date(
                $events,
                array('arrival at port of destination', 'arrival at pod', 'destination arrival'),
                'last'
            ),
            $actualArrival
        ));

        $unloaded = $this->event_date(
            $events,
            array('unloaded from vessel', 'discharged from vessel'),
            'last'
        );

        $customsCleared = $this->event_date(
            $events,
            array('customs cleared', 'custom cleared'),
            'last'
        );

        $outGate = $this->event_date(
            $events,
            array('out-gate', 'out gate', 'gate out'),
            'last'
        );

        $delivery = $this->first_non_empty(array(
            $this->event_date(
                $events,
                array('carrier delivery to destination', 'delivery to destination', 'delivered'),
                'last'
            ),
            $this->extract_any_json_datetime($this->value($raw, 'cargo_delivery'))
        ));

        $emptyReturn = $this->event_date(
            $events,
            array('empty container returned', 'empty return'),
            'last'
        );

        $deconsolReady = $this->event_date(
            $events,
            array(
                'ready to pick up at cfs deconsol',
                'ready for pickup at cfs deconsol',
                'cargo available at cfs'
            ),
            'last'
        );

        return array(
            // AP..AZ shipment milestone
            'AP' => $bookingConfirmation,
            'AQ' => $pickupActual,
            'AR' => $this->first_non_empty(array($arrivalPol, $originCfsActual)),
            // Agreed Firmenich KPI rule:
            // AS Loaded on Vessel = ETD / planned sailing departure
            'AS' => $plannedDeparture,

            // AT Vessel Departed = ATD / actual vessel departure
            'AT' => $vesselDeparted,
            'AU' => $arrivalPod,
            'AV' => $unloaded,
            'AW' => $customsCleared,
            'AX' => $outGate,
            'AY' => $delivery,
            'AZ' => $emptyReturn,

            // BA..BV
            'BA' => $bookingReceived,

            // Agreed Firmenich KPI rule:
            // BB Documentation Final BL = ATD / actual vessel departure.
            'BB' => $vesselDeparted,

            'BD' => $plannedPickup,
            'BE' => $pickupActual,

            'BF' => $this->first_non_empty(array(
                $this->clean_datetime($this->value($raw, 'booking_closing_cargo')),
                $plannedGi
            )),
            'BG' => $originCfsActual,

            'BH' => $this->clean_datetime($this->value($raw, 'booking_closing_date')),
            'BI' => $originGateActual,

            'BK' => $plannedDeparture,
            'BL' => $vesselDeparted,
            'BM' => $plannedArrival,
            'BN' => $arrivalPod,

            // Destination-side planned gate-out is not stored reliably yet.
            'BO' => null,
            'BP' => $outGate,
            'BQ' => $deconsolReady,

            // Door-Port contract: Leg 3 operational fields remain empty unless
            // a future contract/shipment explicitly includes on-carriage.
            'BS' => null,
            'BT' => null,
            'BU' => null,
            'BV' => null,
        );
    }

    protected function build_b_to_bv_row($raw, $m, $contract)
    {
        $profile = $this->profile_for_shipper($this->value($raw, 'id_shipper'));

        $container = $this->container_counts(
            $this->value($raw, 'type_cont_qty'),
            $this->value($raw, 'type_cont_size'),
            $this->value($raw, 'container_no')
        );

        $consigneeName = trim((string)$this->value($raw, 'consignee'));
        $consigneeAddr = trim((string)$this->value($raw, 'consignee_add'));

        if ($consigneeName === '') {
            $consigneeName = $this->first_line($this->value($raw, 'consignee_add'));
        }

        $shipperName = trim((string)$this->first_non_empty(array(
            $this->value($profile, 'canonical_shipper_name'),
            $this->value($raw, 'shipper_print'),
            $this->value($raw, 'x_shipper')
        )));

        $polName = !empty($contract)
            ? trim((string)$contract['pol_name'])
            : $this->first_non_empty(array(
                $this->value($raw, 'origin_city'),
                $this->value($raw, 'port_loading'),
                $this->value($raw, 'sp_region_id')
            ));

        $podName = !empty($contract)
            ? trim((string)$contract['pod_name'])
            : $this->first_non_empty(array(
                $this->value($raw, 'destination_city'),
                $this->value($raw, 'sp_destination')
            ));

        $originDoor = $this->first_non_empty(array(
            $this->value($profile, 'origin_door_address'),
            $this->value($raw, 'booking_warehouse'),
            $this->value($raw, 'pickup_location'),
            $this->value($raw, 'sp_warehouse')
        ));

        $pickupAddress = $this->first_non_empty(array(
            $this->value($profile, 'pickup_address'),
            $this->value($raw, 'pickup_location'),
            $this->value($raw, 'origin_city'),
            $originDoor
        ));

        $deliveryType = $this->derive_delivery_type($raw, $contract);

        $serviceType = !empty($contract['service_type'])
            ? $contract['service_type']
            : null;

        $leg1Carrier = $this->first_non_empty(array(
            $this->value($raw, 'feeder_name'),
            trim(
                trim((string)$this->value($raw, 'vessel')).' '.
                trim((string)$this->value($raw, 'voyage'))
            ),
            $this->value($raw, 'carrier'),
            $this->value($raw, 'liner')
        ));

        $leg2Carrier = $this->first_non_empty(array(
            $this->value($raw, 'carrier'),
            $this->value($raw, 'liner')
        ));

        $originCountry = $this->first_non_empty(array(
            $this->value($profile, 'origin_country'),
            $this->value($raw, 'origin_country'),
            !empty($contract['pol_country']) ? $contract['pol_country'] : null
        ));

        $destinationCountry = $this->first_non_empty(array(
            $this->value($raw, 'destination_country'),
            !empty($contract['pod_country']) ? $contract['pod_country'] : null,
            $this->value($raw, 'lt_destination_country')
        ));

        $row = array(
            'B'  => $this->extract_dsmf_sap_id($raw),
            'C'  => !empty($contract['forwarder_name']) ? $contract['forwarder_name'] : 'Gateway',
            'D'  => 'Ocean',
            'E'  => $this->first_non_empty(array(
                        $this->value($raw, 'mbl_no'),
                        $this->value($raw, 'mother_bl')
                    )),
            'F'  => $this->first_non_empty(array(
                        $this->value($raw, 'hbl_no'),
                        $this->value($raw, 'detail_hbl'),
                        $this->value($raw, 'booking_house_bl')
                    )),
            'G'  => $container['20'],
            'H'  => $container['40'],
            'I'  => $container['teu'],
            'J'  => $this->nullable_number($this->first_non_empty(array(
                        $this->value($raw, 'cbm'),
                        $this->value($raw, 'bl_meas'),
                        $this->value($raw, 'ship_meas')
                    ))),
            'K'  => $this->nullable_number($this->value($raw, 'weight')),
            'L'  => strtoupper(trim((string)$this->first_non_empty(array(
                        $this->value($raw, 'type_of_trans'),
                        $this->value($raw, 'booking_mode'),
                        'LCL'
                    )))),
            'M'  => $this->upper_or_null($polName),
            'N'  => $this->upper_or_null($podName),
            'O'  => $originDoor,
            'P'  => $this->upper_or_null($originCountry),
            'Q'  => $consigneeAddr,
            'R'  => $this->upper_or_null($destinationCountry),
            'S'  => $this->derive_dg($raw),
            'T'  => null, // Flow: no verified existing source yet.
            'U'  => strtoupper(trim((string)$this->first_non_empty(array(
                        $this->value($raw, 'detail_incoterms'),
                        $this->value($raw, 'booking_incoterm')
                    )))),
            'V'  => $this->upper_or_null($this->first_non_empty(array(
                        $this->value($profile, 'incoterms_place'),
                        !empty($contract['pol_name']) ? $contract['pol_name'] : null
                    ))),
            'W'  => $deliveryType,
            'X'  => $this->derive_reefer($this->value($raw, 'type_cont_size')),
            'Y'  => $consigneeName,
            'Z'  => $shipperName,
            'AA' => null, // DSMF Business Unit is not stored in current shipment DB.
            'AB' => 'GATEWAY',
            'AC' => $pickupAddress,
            'AD' => $this->first_non_empty(array(
                        $this->value($profile, 'pickup_postcode'),
                        $this->extract_postcode($originDoor)
                    )),
            'AE' => $this->upper_or_null($originCountry),
            'AF' => $consigneeName,
            'AG' => $this->remove_first_line_if_name($consigneeAddr, $consigneeName),
            'AH' => $this->extract_postcode($consigneeAddr),
            'AI' => $this->upper_or_null($destinationCountry),
            'AJ' => $this->container_number_only($this->value($raw, 'container_no')),
            'AK' => $serviceType,

            'AL' => !empty($contract) ? $this->nullable_number($contract['pre_carriage_days']) : null,
            'AM' => !empty($contract) ? $this->nullable_number($contract['sea_freight_days']) : null,
            'AN' => !empty($contract) ? $this->nullable_number($contract['on_carriage_days']) : null,
            'AO' => !empty($contract) ? $this->nullable_number($contract['additional_terminal_days']) : null,

            'AP' => $m['AP'],
            'AQ' => $m['AQ'],
            'AR' => $m['AR'],
            'AS' => $m['AS'],
            'AT' => $m['AT'],
            'AU' => $m['AU'],
            'AV' => $m['AV'],
            'AW' => $m['AW'],
            'AX' => $m['AX'],
            'AY' => $m['AY'],
            'AZ' => $m['AZ'],

            'BA' => $m['BA'],
            'BB' => $m['BB'],
            'BC' => $leg1Carrier,
            'BD' => $m['BD'],
            'BE' => $m['BE'],
            'BF' => $m['BF'],
            'BG' => $m['BG'],
            'BH' => $m['BH'],
            'BI' => $m['BI'],
            'BJ' => $leg2Carrier,
            'BK' => $m['BK'],
            'BL' => $m['BL'],
            'BM' => $m['BM'],
            'BN' => $m['BN'],
            'BO' => $m['BO'],
            'BP' => $m['BP'],
            'BQ' => $m['BQ'],
            'BR' => null,
            'BS' => $m['BS'],
            'BT' => $m['BT'],
            'BU' => $m['BU'],
            'BV' => $m['BV'],
        );

        // BW..CH: cost fields. No verified accounting source is wired yet.
        // Keep source fields NULL, while KPI CY safely treats missing waste cost as zero.
        $costColumns = $this->build_cost_columns($raw);
        foreach ($costColumns as $column => $value) {
            $row[$column] = $value;
        }

        // CI..CY: server-side KPI calculation.
        $kpiColumns = $this->build_kpi_columns($row, $raw, $contract);
        foreach ($kpiColumns as $column => $value) {
            $row[$column] = $value;
        }

        return $row;
    }

    /**
     * BW..CH cost support columns.
     *
     * Future accounting integration can populate these from ERP/finance.
     * For now they remain NULL because the current shipment/tracking DB does
     * not provide verified cost components.
     */
    protected function build_cost_columns($raw)
    {
        return array(
            'BW' => null, // Leg 1 Charges. Transport
            'BX' => null, // Leg 1 Other Charges excl. Duties
            'BY' => null, // Leg 1 Other Charges Tax & Duties
            'BZ' => null, // Leg 1 Waste cost - Detention and demurrages
            'CA' => null, // Leg 2 Charges. Transport
            'CB' => null, // Leg 2 Other Charges excl. Duties
            'CC' => null, // Leg 2 Other Charges Tax & Duties
            'CD' => null, // Leg 2 Waste cost - Detention and demurrages
            'CE' => null, // Leg 3 Charges. Transport
            'CF' => null, // Leg 3 Other Charges excl. Duties
            'CG' => null, // Leg 3 Other Charges Tax & Duties
            'CH' => null, // Leg 3 Waste cost - Detention and demurrages
        );
    }

    /**
     * CI..CY Firmenich KPI calculation.
     *
     * Important corrections versus the supplied Excel:
     * - CM and CR are calculated explicitly. The template has no formulas there.
     * - Route lookup does NOT use W (which contains TRUCKING in the supplied
     *   report). It uses contract delivery_type / booking movement_type.
     * - Missing planned/actual endpoint => KPI NULL (N/A), never a false PASS.
     */
    protected function build_kpi_columns($row, $raw, $contract)
    {
        /*
         * Stage 3.1 aligns detail KPI fields with the KPI Tracker definitions:
         *
         * Departure Reliability = ATD vs ETD <= 1 day
         * Arrival Reliability   = ATA vs ETA <= 3 days
         *
         * We therefore no longer depend on CFS deconsol/gate-out fields for
         * these two top-level KPI metrics.
         */

        $etd = $this->first_non_empty(array(
            $this->value($row, 'AS'),
            $this->value($row, 'BK')
        ));

        $atd = $this->first_non_empty(array(
            $this->value($row, 'AT'),
            $this->value($row, 'BL')
        ));

        $eta = $this->first_non_empty(array(
            $this->value($row, 'BM'),
            $this->clean_datetime($this->value($raw, 'lt_pod_eta_at'))
        ));

        // ATA proxy is historical coverage only, not a valid strict ETA.
        $etaSource = strtoupper(trim((string)$this->value($raw, 'lt_pod_eta_source')));
        if ($etaSource === 'BACKFILL_ATA_PROXY') {
            $eta = null;
        }

        $ata = $this->first_non_empty(array(
            $this->value($row, 'BN'),
            $this->value($row, 'AU'),
            $this->clean_datetime($this->value($raw, 'lt_actual_pod_arrival_at'))
        ));

        $departureDiff = $this->absolute_days_between($etd, $atd);
        $arrivalDiff   = $this->absolute_days_between($eta, $ata);

        // CI keeps the same sailing reliability basis.
        $sailingDiff = $this->absolute_days_between(
            $this->value($row, 'BK'),
            $this->value($row, 'BL')
        );

        /*
         * Stage 3.3 - Documentation Final BL
         *
         * Agreed project rule is BB = ATD.
         * Therefore BB must NOT be compared to ETD, otherwise CT becomes
         * mathematically identical to Departure Reliability.
         *
         * With BB sourced from ATD, CT measures availability/completion:
         * ATD available => Final BL KPI met.
         * ATD missing   => KPI not evaluable (summary denominator still uses
         *                  total shipment, so missing data lowers the overall %).
         */
        $finalBlAtd = $this->clean_datetime($this->value($row, 'BB'));

        /*
         * Stage 3.3 - Leadtime Adherence
         *
         * Keep the detail-sheet KPI internally consistent:
         *   CL = ATD
         *   CQ = ATA
         *   CU = CQ - CL = ATA - ATD
         *
         * Contract commitment follows the supplied Firmenich template:
         *   AL + AM + AN + AO
         * (Pre-carriage + Sea + On-carriage + Additional Terminal)
         *
         * CW is met when actual transit does not exceed committed transit.
         */
        $actualLeadTime = $this->signed_days_between(
            $atd,
            $ata
        );

        $committed = $this->sum_numeric_values(array(
            $this->value($row, 'AL'),
            $this->value($row, 'AM'),
            $this->value($row, 'AN'),
            $this->value($row, 'AO')
        ));

        $leadTimeDifference = null;
        if ($actualLeadTime !== null && $committed !== null) {
            $leadTimeDifference = abs($actualLeadTime - $committed);
        }

        /*
         * Booking Confirmation responsiveness:
         * AP = Booking Confirmation
         * BA = Booking Received
         *
         * DG <= 3 days
         * NDG <= 1 day
         */
        $bookingResponse = null;
        $bookingDiff = $this->absolute_days_between(
            $this->value($row, 'AP'),
            $this->value($row, 'BA')
        );

        $dg = strtoupper(trim((string)$this->value($row, 'S')));
        if ($bookingDiff !== null) {
            if ($dg === 'DG') {
                $bookingResponse = ($bookingDiff <= 3);
            } elseif ($dg === 'NDG') {
                $bookingResponse = ($bookingDiff <= 1);
            }
        }

        $extraCost = $this->sum_numeric_values(array(
            $this->value($row, 'BZ'),
            $this->value($row, 'CD'),
            $this->value($row, 'CH')
        ), true);

        return array(
            // Sailing Reliability
            'CI' => $sailingDiff !== null ? ($sailingDiff <= 1) : null,

            // Departure Reliability: ATD vs ETD <= 1 day
            'CJ' => 'ATD',
            'CK' => 'ETD',
            'CL' => $atd,
            'CM' => $etd,
            'CN' => $departureDiff !== null
                ? ($departureDiff <= 1)
                : null,

            // Arrival Reliability: ATA vs ETA <= 3 days
            'CO' => 'ATA',
            'CP' => 'ETA',
            'CQ' => $ata,
            'CR' => $eta,
            'CS' => $arrivalDiff !== null
                ? ($arrivalDiff <= 3)
                : null,

            // Documentation Final BL.
            // BB is intentionally sourced from ATD per agreed reporting rule.
            'CT' => $finalBlAtd !== null ? true : null,

            // Lead time adherence
            'CU' => $actualLeadTime,
            'CV' => $leadTimeDifference,
            // Leadtime Adherence: actual lead time must not exceed commitment.
            'CW' => ($actualLeadTime !== null && $committed !== null)
                ? (round($actualLeadTime, 0) <= round($committed, 0))
                : null,

            // Booking Confirmation responsiveness
            'CX' => $bookingResponse,

            // Extra Cost
            'CY' => $extraCost
        );
    }

    /**
     * Resolve LT Adherence endpoints using the supplied workbook's LT Adherence
     * definitions, but without its broken W+L lookup.
     */
    protected function resolve_lt_route($row, $raw, $contract)
    {
        $shipmentType = strtoupper(trim((string)$this->value($row, 'L')));
        if ($shipmentType !== 'FCL' && $shipmentType !== 'LCL') {
            return array();
        }

        $movement = $this->first_non_empty(array(
            !empty($contract['delivery_type']) ? $contract['delivery_type'] : null,
            $this->value($raw, 'movement_type')
        ));

        $movement = $this->normalize_movement_type($movement);
        if ($movement === null) {
            return array();
        }

        $routes = array(
            'FCL' => array(
                'Door to door' => array(
                    'start_actual' => 'BE',
                    'start_planned' => 'BD',
                    'arrival_actual' => 'BV',
                    'arrival_planned' => 'BU'
                ),
                'Door to port' => array(
                    'start_actual' => 'BE',
                    'start_planned' => 'BD',
                    'arrival_actual' => 'BP',
                    'arrival_planned' => 'BO'
                ),
                'Port to port' => array(
                    'start_actual' => 'BI',
                    'start_planned' => 'BH',
                    'arrival_actual' => 'BP',
                    'arrival_planned' => 'BO'
                ),
                'Port to door' => array(
                    'start_actual' => 'BI',
                    'start_planned' => 'BH',
                    'arrival_actual' => 'BV',
                    'arrival_planned' => 'BU'
                ),
            ),
            'LCL' => array(
                'Door to door' => array(
                    'start_actual' => 'BE',
                    'start_planned' => 'BD',
                    'arrival_actual' => 'BV',
                    'arrival_planned' => 'BU'
                ),
                'Door to port' => array(
                    'start_actual' => 'BE',
                    'start_planned' => 'BD',
                    'arrival_actual' => 'BQ',
                    // The supplied LT Adherence sheet has no planned field here.
                    'arrival_planned' => null
                ),
                'Port to port' => array(
                    'start_actual' => 'BG',
                    'start_planned' => 'BF',
                    'arrival_actual' => 'BQ',
                    // The supplied LT Adherence sheet has no planned field here.
                    'arrival_planned' => null
                ),
                'Port to door' => array(
                    'start_actual' => 'BG',
                    'start_planned' => 'BF',
                    'arrival_actual' => 'BV',
                    'arrival_planned' => 'BU'
                ),
            )
        );

        if (empty($routes[$shipmentType][$movement])) {
            return array();
        }

        $route = $routes[$shipmentType][$movement];
        $route['movement'] = $movement;
        $route['shipment_type'] = $shipmentType;

        return $route;
    }

    protected function normalize_movement_type($value)
    {
        $s = strtoupper(trim((string)$value));
        if ($s === '') {
            return null;
        }

        $s = str_replace(array('_', '-'), ' ', $s);
        $s = preg_replace('/\s+/', ' ', $s);
        $s = trim($s);

        $map = array(
            'DOOR PORT' => 'Door to port',
            'DOOR TO PORT' => 'Door to port',
            'DOOR DOOR' => 'Door to door',
            'DOOR TO DOOR' => 'Door to door',
            'PORT PORT' => 'Port to port',
            'PORT TO PORT' => 'Port to port',
            'PORT DOOR' => 'Port to door',
            'PORT TO DOOR' => 'Port to door',
        );

        return isset($map[$s]) ? $map[$s] : null;
    }

    protected function kpi_column_label($column)
    {
        $labels = array(
            'BD' => 'Leg1 Pickup Planned',
            'BE' => 'Leg1 Pickup Actual',
            'BF' => 'Leg 2 CFS Consol Arrival Planned',
            'BG' => 'Leg 2 CFS Consol Arrival Actual',
            'BH' => 'Leg2 Full Ingate Planned',
            'BI' => 'Leg2 Full Ingate Actual',
            'BO' => 'Leg2 Full Gate out Planned',
            'BP' => 'Leg2 Full Gate out Actual',
            'BQ' => 'Leg 2 Ready to pick up at CFS Deconsol Actual',
            'BU' => 'Leg3 Delivery Planned',
            'BV' => 'Leg3 Delivery Actual',
        );

        return isset($labels[$column]) ? $labels[$column] : $column;
    }

    /**
     * Absolute difference in days. Keeps fractions when timestamps contain time.
     */
    protected function absolute_days_between($a, $b)
    {
        $v = $this->signed_days_between($a, $b);
        return $v === null ? null : abs($v);
    }

    /**
     * Signed difference: end - start in days.
     */
    protected function signed_days_between($start, $end)
    {
        $a = $this->clean_datetime($start);
        $b = $this->clean_datetime($end);

        if ($a === null || $b === null) {
            return null;
        }

        $ta = strtotime($a);
        $tb = strtotime($b);

        if ($ta === false || $tb === false) {
            return null;
        }

        return ($tb - $ta) / 86400;
    }

    /**
     * Sum numeric values.
     * When $zeroIfAllMissing is false, all-missing => NULL.
     */
    protected function sum_numeric_values($values, $zeroIfAllMissing = false)
    {
        $sum = 0.0;
        $found = false;

        foreach ($values as $value) {
            if ($value === null || $value === '') {
                continue;
            }

            if (is_numeric($value)) {
                $sum += (float)$value;
                $found = true;
            }
        }

        if (!$found) {
            return $zeroIfAllMissing ? 0 : null;
        }

        return $sum;
    }

    protected function event_date($events, $patterns, $pick = 'first')
    {
        if (empty($events)) {
            return null;
        }

        $matches = array();

        foreach ($events as $event) {
            $name = $this->event_text($event);
            if ($name === '') {
                continue;
            }

            $matched = false;
            foreach ($patterns as $pattern) {
                if (strpos($name, strtolower($pattern)) !== false) {
                    $matched = true;
                    break;
                }
            }

            if (!$matched) {
                continue;
            }

            $dt = $this->clean_datetime($this->value($event, 'event_time_value'));
            if ($dt !== null) {
                $matches[] = $dt;
            }
        }

        if (empty($matches)) {
            return null;
        }

        sort($matches);

        return $pick === 'last'
            ? $matches[count($matches) - 1]
            : $matches[0];
    }

    protected function event_date_from_sources($events, $patterns, $sources, $pick = 'first')
    {
        if (empty($events) || empty($sources)) {
            return null;
        }

        $allowed = array();
        foreach ($sources as $source) {
            $key = strtolower(trim((string)$source));
            if ($key !== '') {
                $allowed[$key] = true;
            }
        }

        $matches = array();

        foreach ($events as $event) {
            $source = strtolower(trim((string)$this->value($event, 'event_source_table')));
            if ($source === '' || !isset($allowed[$source])) {
                continue;
            }

            $name = $this->event_text($event);
            if ($name === '') {
                continue;
            }

            $matched = false;
            foreach ($patterns as $pattern) {
                if (strpos($name, strtolower($pattern)) !== false) {
                    $matched = true;
                    break;
                }
            }

            if (!$matched) {
                continue;
            }

            $dt = $this->clean_datetime($this->value($event, 'event_time_value'));
            if ($dt !== null) {
                $matches[] = $dt;
            }
        }

        if (empty($matches)) {
            return null;
        }

        sort($matches);

        return $pick === 'last'
            ? $matches[count($matches) - 1]
            : $matches[0];
    }

    protected function profile_for_shipper($shipperId)
    {
        $key = trim((string)$shipperId);
        if ($key !== '' && isset($this->profiles[$key]) && is_array($this->profiles[$key])) {
            return $this->profiles[$key];
        }
        if ($key !== '' && isset($this->profiles[(int)$key]) && is_array($this->profiles[(int)$key])) {
            return $this->profiles[(int)$key];
        }
        return array();
    }

    protected function extract_dsmf_sap_id($raw)
    {
        $candidates = array(
            $this->value($raw, 'dsmf_sap_id'),
            $this->value($raw, 'detail_shipment_request_id')
        );

        foreach ($candidates as $candidate) {
            $s = trim((string)$candidate);
            if ($s === '') {
                continue;
            }

            if (ctype_digit($s)) {
                return 0 + $s;
            }

            if (preg_match('/(?:SHIPMENT\s*[:#-]?\s*)?(\d{6,10})/i', $s, $m)) {
                return 0 + $m[1];
            }
        }

        return null;
    }

    protected function container_number_only($value)
    {
        $s = strtoupper(trim((string)$value));
        if ($s === '') {
            return null;
        }

        if (preg_match('/\b([A-Z]{4}\d{7})\b/', $s, $m)) {
            return $m[1];
        }

        $parts = preg_split('/\s*\/\s*/', $s);
        return !empty($parts[0]) ? trim($parts[0]) : $s;
    }

    protected function event_text($event)
    {
        $parts = array();
        foreach (array(
            'event_name','event_description','event_code','event_key',
            'location_name','port_name','remarks','carrier_event','display_event'
        ) as $field) {
            $v = trim((string)$this->value($event, $field));
            if ($v !== '') {
                $parts[] = $v;
            }
        }
        return strtolower(implode(' | ', $parts));
    }

    protected function contract_candidate_keys($raw)
    {
        $keys = array();

        $polCode = strtoupper(trim((string)$this->value($raw, 'pol_code')));
        $podCode = strtoupper(trim((string)$this->value($raw, 'pod_code')));
        if ($polCode !== '' || $podCode !== '') {
            $keys[] = array('type' => 'UNLOCODE', 'key' => $polCode.'|'.$podCode);
        }

        $polNames = array(
            $this->value($raw, 'origin_city'),
            $this->value($raw, 'port_loading'),
            $this->value($raw, 'x_region_id'),
            $this->value($raw, 'sp_region_id')
        );
        $podNames = array(
            $this->value($raw, 'destination_city'),
            $this->value($raw, 'sp_destination'),
            $this->value($raw, 'x_destination'),
            $this->value($raw, 'x_consol_destination')
        );

        foreach ($polNames as $polName) {
            foreach ($podNames as $podName) {
                $key = $this->normalize_port_key($polName).'|'.$this->normalize_port_key($podName);
                if ($key !== '|' && $key !== '') {
                    $keys[$key] = array('type' => 'ALIAS', 'key' => $key);
                }
            }
        }

        return array_values($keys);
    }


    public function get_row_by_hbl_cy($hbl, $shipperIds = array(4407, 6412), $includeMeta = true)
    {
        return $this->get_row_by_hbl($hbl, $shipperIds, $includeMeta);
    }

    public function debug_hbl_sources($hbl, $shipperIds = array(4407, 6412))
    {
        $hbl = trim((string)$hbl);
        $shipperIds = $this->normalize_shipper_ids($shipperIds);
        if ($hbl === '' || empty($shipperIds)) {
            return null;
        }

        $baseRows = $this->get_base_shipments($shipperIds, null, null, $hbl);
        if (empty($baseRows)) {
            return null;
        }

        $raw = $baseRows[0];
        $contracts = $this->load_contract_index(null, null);
        $mixedMap = $this->load_mixed_event_map($baseRows);
        $localMap = $this->load_tv_event_logs_map($baseRows);
        $eventMap = $this->merge_event_maps($mixedMap, $localMap);
        $shipmentKey = $this->event_map_key_for_row($raw, $eventMap);
        $events = ($shipmentKey !== '' && isset($eventMap['rows'][$shipmentKey]))
            ? $eventMap['rows'][$shipmentKey] : array();
        $contract = $this->find_contract_lane($raw, $contracts, $this->value($raw, 'expect_etd'));

        $eventCandidates = array();
        $eventNameSummary = array();

        foreach ($events as $event) {
            $source = trim((string)$this->value($event, 'event_source_table'));
            $name = trim((string)$this->value($event, 'event_name'));
            $code = trim((string)$this->value($event, 'event_code'));
            $dt = $this->clean_datetime($this->value($event, 'event_time_value'));

            $summaryKey = strtolower($source.'|'.$name.'|'.$code);

            if (!isset($eventNameSummary[$summaryKey])) {
                $eventNameSummary[$summaryKey] = array(
                    'source' => $source,
                    'name' => $name,
                    'code' => $code,
                    'count' => 0,
                    'first_time' => null,
                    'last_time' => null
                );
            }

            $eventNameSummary[$summaryKey]['count']++;

            if ($dt !== null) {
                if (
                    $eventNameSummary[$summaryKey]['first_time'] === null ||
                    $dt < $eventNameSummary[$summaryKey]['first_time']
                ) {
                    $eventNameSummary[$summaryKey]['first_time'] = $dt;
                }

                if (
                    $eventNameSummary[$summaryKey]['last_time'] === null ||
                    $dt > $eventNameSummary[$summaryKey]['last_time']
                ) {
                    $eventNameSummary[$summaryKey]['last_time'] = $dt;
                }
            }

            $text = $this->event_text($event);
            if (
                strpos($text, 'load') === false &&
                strpos($text, 'depart') === false &&
                strpos($text, 'gate') === false &&
                strpos($text, 'delivery') === false &&
                strpos($text, 'delivered') === false &&
                strpos($text, 'document') === false &&
                strpos($text, 'final bl') === false &&
                strpos($text, 'bill of lading') === false &&
                strpos($text, 'deconsol') === false &&
                strpos($text, 'cargo available') === false &&
                strpos($text, 'available') === false &&
                strpos($text, 'pickup') === false &&
                strpos($text, 'pick-up') === false
            ) {
                continue;
            }

            /*
             * Candidate list is de-duplicated by business event + source + DAY,
             * so one noisy Vessel Departed event cannot consume the 80-row limit.
             */
            $candidateKey = strtolower($source).'|'.
                strtolower($name).'|'.
                ($dt !== null ? substr($dt, 0, 10) : '').'|'.
                strtolower(trim((string)$this->value($event, 'location_name'))).'|'.
                strtolower(trim((string)$this->value($event, 'port_name')));

            if (isset($eventCandidates[$candidateKey])) {
                continue;
            }

            $eventCandidates[$candidateKey] = array(
                'time' => $this->value($event, 'event_time_value'),
                'name' => $this->value($event, 'event_name'),
                'code' => $this->value($event, 'event_code'),
                'location' => $this->value($event, 'location_name'),
                'port' => $this->value($event, 'port_name'),
                'source' => $source
            );
        }

        $eventCandidates = array_values($eventCandidates);
        usort($eventCandidates, function($a, $b) {
            $ta = !empty($a['time']) ? strtotime((string)$a['time']) : 0;
            $tb = !empty($b['time']) ? strtotime((string)$b['time']) : 0;
            if ($ta === $tb) return 0;
            return ($ta < $tb) ? -1 : 1;
        });

        if (count($eventCandidates) > 120) {
            $eventCandidates = array_slice($eventCandidates, 0, 120);
        }

        $eventNameSummary = array_values($eventNameSummary);
        usort($eventNameSummary, function($a, $b) {
            if ($a['source'] === $b['source']) {
                return strcasecmp((string)$a['name'], (string)$b['name']);
            }
            return strcasecmp((string)$a['source'], (string)$b['source']);
        });

        return array(
            'model_version' => self::MODEL_VERSION,
            'model_file' => __FILE__,
            'raw_sources' => array(
                'x_id_request' => $this->value($raw, 'dsmf_sap_id'),
                'detail_shipment_request_id' => $this->value($raw, 'detail_shipment_request_id'),
                'x_lt_planned_gi_at' => $this->value($raw, 'lt_planned_gi_at'),
                'x_lt_pod_eta_at' => $this->value($raw, 'lt_pod_eta_at'),
                'x_lt_pod_eta_source' => $this->value($raw, 'lt_pod_eta_source'),
                'x_lt_actual_pod_arrival_at' => $this->value($raw, 'lt_actual_pod_arrival_at'),
                'x_pickup_cargo' => $this->value($raw, 'pickup_cargo'),
                'booking_pickup_datetime' => $this->value($raw, 'pickup_datetime'),
                'detail_incoterms' => $this->value($raw, 'detail_incoterms'),
                'booking_incoterm' => $this->value($raw, 'booking_incoterm'),
                'booking_origin_country' => $this->value($raw, 'origin_country'),
                'booking_destination_country' => $this->value($raw, 'destination_country'),
                'x_lt_destination_country' => $this->value($raw, 'lt_destination_country'),
                'booking_pol_code' => $this->value($raw, 'pol_code'),
                'booking_pod_code' => $this->value($raw, 'pod_code'),
                'booking_origin_city' => $this->value($raw, 'origin_city'),
                'booking_destination_city' => $this->value($raw, 'destination_city'),
                'stuffing_port_loading' => $this->value($raw, 'port_loading'),
                'stuffing_destination' => $this->value($raw, 'sp_destination'),
                'x_destination' => $this->value($raw, 'x_destination'),
                'x_consol_destination' => $this->value($raw, 'x_consol_destination'),
                'booking_trucking' => $this->value($raw, 'trucking'),
                'booking_movement_type' => $this->value($raw, 'movement_type'),
                'booking_warehouse' => $this->value($raw, 'booking_warehouse'),
                'booking_pickup_location' => $this->value($raw, 'pickup_location'),
                'stuffing_warehouse' => $this->value($raw, 'sp_warehouse'),
                'x_container_raw' => $this->value($raw, 'container_no'),
                'stuffing_container_seal_raw' => $this->value($raw, 'cont_seal_no'),
                'detail_tanggal_bl' => $this->value($raw, 'tanggal_bl')
            ),
            'profile' => $this->profile_for_shipper($this->value($raw, 'id_shipper')),
            'contract' => array(
                'meta' => isset($contracts['_meta']) ? $contracts['_meta'] : array(),
                'candidate_keys' => $this->contract_candidate_keys($raw),
                'matched' => !empty($contract),
                'match_type' => !empty($contract['_match_type']) ? $contract['_match_type'] : null,
                'pair' => !empty($contract) ? $contract['pol_code'].'-'.$contract['pod_code'] : null,
                'row' => !empty($contract) ? $contract : null
            ),
            'events' => array(
                'count_after_dedupe' => count($events),
                'candidate_count' => count($eventCandidates),
                'event_name_summary' => $eventNameSummary,
                'candidates' => $eventCandidates
            )
        );
    }

    protected function derive_delivery_type($raw, $contract)
    {
        $profile = $this->profile_for_shipper($this->value($raw, 'id_shipper'));
        $trucking = strtoupper(trim((string)$this->value($raw, 'trucking')));
        $movement = strtoupper(trim((string)$this->value($raw, 'movement_type')));

        if (
            in_array($trucking, array('1','Y','YES','TRUE','TRUCKING'), true) ||
            strpos($movement, 'DOOR') !== false
        ) {
            return 'TRUCKING';
        }

        if (!empty($profile['default_delivery_type'])) {
            return strtoupper(trim((string)$profile['default_delivery_type']));
        }

        // Contract Delivery type is lane scope (e.g. Door-Port), whereas KPI W
        // expects operational mode. A Door-* nominated lane implies a trucking
        // pre-carriage leg for this report.
        if (!empty($contract['delivery_type']) && stripos($contract['delivery_type'], 'Door') === 0) {
            return 'TRUCKING';
        }

        return !empty($contract['delivery_type']) ? $contract['delivery_type'] : null;
    }

    protected function derive_dg($raw)
    {
        $cargo = strtoupper(trim((string)$this->value($raw, 'cargo_type')));

        if (
            strpos($cargo, 'DG') !== false ||
            strpos($cargo, 'DANGEROUS') !== false ||
            strpos($cargo, 'HAZ') !== false
        ) {
            return 'DG';
        }

        // Business/reporting convention: shipment without a DG indication is NDG.
        return 'NDG';
    }

    protected function derive_reefer($size)
    {
        $s = strtoupper(trim((string)$size));

        if (
            strpos($s, 'REEFER') !== false ||
            preg_match('/(^|[^A-Z])RF([^A-Z]|$)/', $s)
        ) {
            return 'YES';
        }

        return 'NO';
    }

    protected function container_counts($qtyRaw, $sizeRaw, $containerNo)
    {
        $qty = (int)$qtyRaw;
        if ($qty <= 0 && trim((string)$containerNo) !== '') {
            $qty = 1;
        }

        $size = strtoupper(trim((string)$sizeRaw));
        $n20 = 0;
        $n40 = 0;

        if (strpos($size, '20') !== false) {
            $n20 = $qty;
        } elseif (
            strpos($size, '40') !== false ||
            strpos($size, '45') !== false
        ) {
            $n40 = $qty;
        }

        return array(
            '20'  => $n20,
            '40'  => $n40,
            'teu' => $n20 + ($n40 * 2)
        );
    }

    protected function extract_json_datetime($raw, $keys)
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        $data = is_array($raw) ? $raw : json_decode((string)$raw, true);

        if (!is_array($data)) {
            return null;
        }

        foreach ($keys as $key) {
            if (isset($data[$key])) {
                $dt = $this->clean_datetime($data[$key]);
                if ($dt !== null) {
                    return $dt;
                }
            }
        }

        return null;
    }

    protected function extract_any_json_datetime($raw)
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        $data = is_array($raw) ? $raw : json_decode((string)$raw, true);
        if (!is_array($data)) {
            return $this->clean_datetime($raw);
        }

        $stack = $data;

        while (!empty($stack)) {
            $v = array_shift($stack);

            if (is_array($v)) {
                foreach ($v as $child) {
                    $stack[] = $child;
                }
                continue;
            }

            $dt = $this->clean_datetime($v);
            if ($dt !== null) {
                return $dt;
            }
        }

        return null;
    }

    protected function clean_datetime($value)
    {
        if ($value === null) {
            return null;
        }

        $s = trim((string)$value);

        if (
            $s === '' ||
            strtolower($s) === 'null' ||
            $s === '0000-00-00' ||
            $s === '0000-00-00 00:00:00'
        ) {
            return null;
        }

        $formats = array(
            'Y-m-d H:i:s',
            'Y-m-d H:i',
            'Y-m-d',
            'Ymd',
            'd/m/Y H:i:s',
            'd/m/Y H:i',
            'd/m/Y',
            'd-m-Y H:i:s',
            'd-m-Y'
        );

        foreach ($formats as $format) {
            $dt = DateTime::createFromFormat('!'.$format, $s);
            if ($dt && $dt->format($format) === $s) {
                return $dt->format('Y-m-d H:i:s');
            }
        }

        $ts = strtotime($s);
        if ($ts !== false && $ts > 0) {
            return date('Y-m-d H:i:s', $ts);
        }

        return null;
    }

    protected function to_date($value)
    {
        $dt = $this->clean_datetime($value);
        return $dt === null ? null : substr($dt, 0, 10);
    }

    protected function normalize_shipper_ids($shipperIds)
    {
        if (!is_array($shipperIds)) {
            $shipperIds = array($shipperIds);
        }

        $out = array();

        foreach ($shipperIds as $id) {
            $id = trim((string)$id);
            if ($id !== '' && ctype_digit($id)) {
                $out[$id] = $id;
            }
        }

        return array_values($out);
    }

    protected function is_iso_date($value)
    {
        if ($value === null || $value === '') {
            return false;
        }

        $dt = DateTime::createFromFormat('!Y-m-d', (string)$value);
        return $dt && $dt->format('Y-m-d') === (string)$value;
    }

    protected function decode_aliases($json, $fallback)
    {
        $aliases = array();

        if (!empty($json)) {
            $decoded = json_decode((string)$json, true);
            if (is_array($decoded)) {
                $aliases = $decoded;
            }
        }

        foreach ($fallback as $v) {
            if ($v !== null && trim((string)$v) !== '') {
                $aliases[] = $v;
            }
        }

        $out = array();
        foreach ($aliases as $v) {
            $v = trim((string)$v);
            if ($v !== '') {
                $out[strtoupper($v)] = $v;
            }
        }

        return array_values($out);
    }

    protected function normalize_port_key($value)
    {
        $s = strtoupper(trim((string)$value));
        if ($s === '') {
            return '';
        }

        $s = str_replace(array("\r", "\n", "\t"), ' ', $s);
        $s = preg_replace('/[^A-Z0-9]+/', ' ', $s);
        $s = preg_replace('/\s+/', ' ', $s);

        return trim($s);
    }

    protected function extract_postcode($value)
    {
        $s = trim((string)$value);
        if ($s === '') {
            return null;
        }

        // Generic 4-6 digit postal code. This intentionally does not assume a country.
        if (preg_match_all('/\b\d{4,6}\b/', $s, $m) && !empty($m[0])) {
            return $m[0][count($m[0]) - 1];
        }

        return null;
    }

    protected function remove_first_line_if_name($address, $name)
    {
        $address = trim((string)$address);
        $name    = trim((string)$name);

        if ($address === '') {
            return null;
        }

        if ($name !== '') {
            $lines = preg_split('/\r\n|\r|\n/', $address);
            if (!empty($lines) && strcasecmp(trim($lines[0]), $name) === 0) {
                array_shift($lines);
                return trim(implode("\n", $lines));
            }
        }

        return $address;
    }

    protected function first_line($value)
    {
        $s = trim((string)$value);
        if ($s === '') {
            return '';
        }

        $parts = preg_split('/\r\n|\r|\n/', $s);
        return trim((string)$parts[0]);
    }

    protected function first_non_empty($values)
    {
        foreach ($values as $v) {
            if ($v === null) {
                continue;
            }

            if (is_string($v)) {
                $t = trim($v);
                if (
                    $t === '' ||
                    strtolower($t) === 'null' ||
                    $t === '0000-00-00' ||
                    $t === '0000-00-00 00:00:00'
                ) {
                    continue;
                }
                return $t;
            }

            if ($v !== '') {
                return $v;
            }
        }

        return null;
    }

    protected function value($array, $key, $default = null)
    {
        return (is_array($array) && array_key_exists($key, $array))
            ? $array[$key]
            : $default;
    }

    protected function nullable_number($value)
    {
        if ($value === null) {
            return null;
        }

        $s = trim((string)$value);
        if ($s === '' || !is_numeric($s)) {
            return null;
        }

        return 0 + $s;
    }

    protected function upper_or_null($value)
    {
        $s = trim((string)$value);
        return $s === '' ? null : strtoupper($s);
    }
}
