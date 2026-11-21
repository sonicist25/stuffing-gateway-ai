<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Firmenich KPI B..CY validation controller.
 *
 * Install:
 *   application/controllers/Firmenich_kpi_debug.php
 *   application/config/firmenich_kpi_reference.php
 *   application/models/M_firmenich_kpi_report.php   (use V2 model)
 *
 * Security:
 * - CLI is allowed.
 * - Browser access requires an authenticated CI session.
 *
 * Examples:
 *   Browser (while logged in):
 *     /firmenich_kpi_debug/hbl?hbl=JPHMNL-GCL2609797
 *     /firmenich_kpi_debug/sample
 *
 *   CLI:
 *     php index.php firmenich_kpi_debug hbl JPHMNL-GCL2609797
 */
class Firmenich_kpi_debug extends CI_Controller
{
    protected $dateColumns = array();
    protected $headers = array();
    protected $requirements = array();
    protected $samples = array();

    public function __construct()
    {
        parent::__construct();

        $this->guard_debug_access();

        $this->load->model('M_firmenich_kpi_report');
        $this->config->load('firmenich_kpi_reference', true);

        $cfg = $this->config->item('firmenich_kpi_reference');

        $this->headers = isset($cfg['firmenich_kpi_headers'])
            ? $cfg['firmenich_kpi_headers']
            : array();

        $this->requirements = isset($cfg['firmenich_kpi_requirements'])
            ? $cfg['firmenich_kpi_requirements']
            : array();

        $this->dateColumns = isset($cfg['firmenich_kpi_date_columns'])
            ? $cfg['firmenich_kpi_date_columns']
            : array();

        $this->samples = isset($cfg['firmenich_kpi_template_samples'])
            ? $cfg['firmenich_kpi_template_samples']
            : array();
    }

    public function index()
    {
        return $this->json(array(
            'status' => true,
            'message' => 'Firmenich KPI debug endpoint',
            'endpoints' => array(
                'hbl' => '/firmenich_kpi_debug/hbl?hbl=JPHMNL-GCL2609797',
                'sample' => '/firmenich_kpi_debug/sample',
                'coverage' => '/firmenich_kpi_debug/coverage?shipper_id=4407&date_from=2026-06-01&date_to=2026-08-31',
                'diagnostic' => '/firmenich_kpi_debug/diagnostic?hbl=JPHMNL-GCL2609797',
                'version' => '/firmenich_kpi_debug/version'
            )
        ));
    }

    public function version()
    {
        return $this->json(array(
            'status' => true,
            'controller_file' => __FILE__,
            'controller_mtime' => @filemtime(__FILE__) ?: null,
            'model' => $this->M_firmenich_kpi_report->get_model_version()
        ));
    }

    /**
     * Validate one HBL and compare it with the uploaded template sample
     * when the HBL exists in the reference config.
     */
    public function hbl($hbl = null)
    {
        if ($hbl === null || trim((string)$hbl) === '') {
            $hbl = $this->input->get('hbl', true);
        }

        $hbl = trim((string)$hbl);

        if ($hbl === '') {
            return $this->json(array(
                'status' => false,
                'message' => 'Parameter hbl wajib diisi.'
            ), 400);
        }

        $shipperIds = $this->get_shipper_ids();

        $row = $this->M_firmenich_kpi_report->get_row_by_hbl_cy(
            $hbl,
            $shipperIds,
            true
        );

        if (empty($row)) {
            return $this->json(array(
                'status' => false,
                'message' => 'HBL tidak ditemukan di x_tracking_export untuk shipper yang dipilih.',
                'hbl' => $hbl,
                'shipper_ids' => $shipperIds
            ), 404);
        }

        $expected = isset($this->samples[$hbl])
            ? $this->samples[$hbl]
            : null;

        return $this->json(array(
            'status' => true,
            'hbl' => $hbl,
            'shipper_ids' => $shipperIds,
            'meta' => isset($row['_meta']) ? $row['_meta'] : array(),
            'coverage' => $this->row_coverage($row),
            'template_reference_found' => $expected !== null,
            'comparison' => $expected !== null
                ? $this->compare_row($row, $expected)
                : null,
            'kpi' => $this->kpi_output($row),
            'row_b_to_bv' => $this->column_slice($row, 'B', 'BV'),
            'row_bw_to_ch_cost' => $this->column_slice($row, 'BW', 'CH'),
            'row_ci_to_cy_kpi' => $this->column_slice($row, 'CI', 'CY'),
            'row_b_to_cy' => $this->without_meta($row)
        ));
    }

    /**
     * Show raw source values, contract lookup attempts and milestone candidate
     * events for one HBL. Use this when a B..BV mapping is still missing/diff.
     */
    public function diagnostic($hbl = null)
    {
        if ($hbl === null || trim((string)$hbl) === '') {
            $hbl = $this->input->get('hbl', true);
        }

        $hbl = trim((string)$hbl);
        if ($hbl === '') {
            return $this->json(array(
                'status' => false,
                'message' => 'Parameter hbl wajib diisi.'
            ), 400);
        }

        $data = $this->M_firmenich_kpi_report->debug_hbl_sources(
            $hbl,
            $this->get_shipper_ids()
        );

        if (empty($data)) {
            return $this->json(array(
                'status' => false,
                'message' => 'HBL tidak ditemukan.',
                'hbl' => $hbl
            ), 404);
        }

        return $this->json(array(
            'status' => true,
            'hbl' => $hbl,
            'diagnostic' => $data
        ));
    }

    /**
     * Validate all four HBL examples that exist in the supplied KPI template.
     */
    public function sample()
    {
        $shipperIds = $this->get_shipper_ids();
        $results = array();

        foreach ($this->samples as $hbl => $expected) {
            $row = $this->M_firmenich_kpi_report->get_row_by_hbl_cy(
                $hbl,
                $shipperIds,
                true
            );

            if (empty($row)) {
                $results[$hbl] = array(
                    'found' => false,
                    'message' => 'HBL tidak ditemukan di database aktif.'
                );
                continue;
            }

            $results[$hbl] = array(
                'found' => true,
                'meta' => isset($row['_meta']) ? $row['_meta'] : array(),
                'coverage' => $this->row_coverage($row),
                'comparison' => $this->compare_row($row, $expected)
            );
        }

        return $this->json(array(
            'status' => true,
            'shipper_ids' => $shipperIds,
            'sample_count' => count($this->samples),
            'results' => $results
        ));
    }

    /**
     * Coverage check for a date range.
     * This does not compare shipment values with the sample workbook;
     * it checks contract matching and required B..BV source completeness.
     */
    public function coverage()
    {
        $shipperIds = $this->get_shipper_ids();
        $dateFrom = trim((string)$this->input->get('date_from', true));
        $dateTo   = trim((string)$this->input->get('date_to', true));

        if (!$this->is_iso_date($dateFrom) || !$this->is_iso_date($dateTo)) {
            return $this->json(array(
                'status' => false,
                'message' => 'date_from dan date_to wajib format YYYY-MM-DD.'
            ), 400);
        }

        $rows = $this->M_firmenich_kpi_report->get_rows_b_to_cy(
            $shipperIds,
            $dateFrom,
            $dateTo,
            true
        );

        $summary = array(
            'total_rows' => count($rows),
            'contract_matched' => 0,
            'contract_unmatched' => 0,
            'rows_with_tracking_events' => 0,
            'rows_without_tracking_events' => 0,
            'missing_required_by_column' => array(),
            'known_source_gaps' => array(
                'T' => 'Flow',
                'V' => 'Incoterms Place',
                'AA' => 'Business Unit',
                'BO' => 'Leg2 Full Gate out Planned'
            )
        );

        $issues = array();

        foreach ($rows as $row) {
            $meta = isset($row['_meta']) ? $row['_meta'] : array();

            if (!empty($meta['contract_match'])) {
                $summary['contract_matched']++;
            } else {
                $summary['contract_unmatched']++;
            }

            if (!empty($meta['tracking_event_count'])) {
                $summary['rows_with_tracking_events']++;
            } else {
                $summary['rows_without_tracking_events']++;
            }

            $coverage = $this->row_coverage($row);

            foreach ($coverage['missing_required'] as $item) {
                $col = $item['column'];
                if (!isset($summary['missing_required_by_column'][$col])) {
                    $summary['missing_required_by_column'][$col] = 0;
                }
                $summary['missing_required_by_column'][$col]++;
            }

            if (
                empty($meta['contract_match']) ||
                !empty($coverage['missing_required'])
            ) {
                $issues[] = array(
                    'hbl' => isset($row['F']) ? $row['F'] : null,
                    'job_no' => isset($meta['no_job']) ? $meta['no_job'] : null,
                    'contract_match' => !empty($meta['contract_match']),
                    'contract_pair' => isset($meta['contract_pair']) ? $meta['contract_pair'] : null,
                    'contract_match_type' => isset($meta['contract_match_type']) ? $meta['contract_match_type'] : null,
                    'tracking_event_count' => isset($meta['tracking_event_count']) ? $meta['tracking_event_count'] : 0,
                    'missing_required' => $coverage['missing_required']
                );
            }
        }

        ksort($summary['missing_required_by_column']);

        return $this->json(array(
            'status' => true,
            'shipper_ids' => $shipperIds,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'summary' => $summary,
            'issues' => $issues
        ));
    }

    protected function compare_row($actual, $expected)
    {
        $result = array(
            'summary' => array(
                'MATCH' => 0,
                'MISSING' => 0,
                'DIFF' => 0,
                'RULE_OVERRIDE' => 0,
                'SYSTEM_ENRICHMENT' => 0
            ),
            'items' => array()
        );

        foreach ($this->headers as $column => $header) {
            $aRaw = array_key_exists($column, $actual) ? $actual[$column] : null;
            $eRaw = array_key_exists($column, $expected) ? $expected[$column] : null;

            $a = $this->normalize_compare_value($column, $aRaw);
            $e = $this->normalize_compare_value($column, $eRaw);

            if ($column === 'BB' && $a !== $e && $a !== null) {
                // Agreed project rule overrides the supplied sample:
                // Documentation Final BL = ATD.
                $status = 'RULE_OVERRIDE';
            } elseif ($a === $e) {
                $status = 'MATCH';
            } elseif ($e === null && $a !== null) {
                // The supplied template example leaves many support columns empty.
                // Populating them from verified DB/tracking is a valid enrichment.
                $status = 'SYSTEM_ENRICHMENT';
            } elseif ($e !== null && $a === null) {
                $status = 'MISSING';
            } else {
                $status = 'DIFF';
            }

            $result['summary'][$status]++;

            if ($status !== 'MATCH') {
                $result['items'][] = array(
                    'column' => $column,
                    'header' => $header,
                    'status' => $status,
                    'template' => $eRaw,
                    'system' => $aRaw
                );
            }
        }

        return $result;
    }

    protected function row_coverage($row)
    {
        $shipmentType = strtoupper(trim((string)(isset($row['L']) ? $row['L'] : '')));
        $missing = array();
        $desired = array();

        foreach ($this->requirements as $column => $rule) {
            $ruleText = trim((string)$rule);

            if ($ruleText === '') {
                continue;
            }

            $upper = strtoupper($ruleText);

            if (strpos($upper, 'NOT APPLICABLE') !== false) {
                continue;
            }

            if (
                strpos($upper, 'MANDATORY FOR FCL') !== false &&
                $shipmentType !== 'FCL'
            ) {
                continue;
            }

            if (
                strpos($upper, 'MANDATORY TO FCL') !== false &&
                $shipmentType !== 'FCL'
            ) {
                continue;
            }

            if (
                strpos($upper, 'MANDATORY FOR LCL') !== false &&
                $shipmentType !== 'LCL'
            ) {
                continue;
            }

            $value = array_key_exists($column, $row) ? $row[$column] : null;
            $empty = $this->is_empty_required_value($column, $value);

            if (strpos($upper, 'MANDATORY') !== false && $empty) {
                $missing[] = array(
                    'column' => $column,
                    'header' => isset($this->headers[$column]) ? $this->headers[$column] : null,
                    'requirement' => $ruleText
                );
            }

            if (strpos($upper, 'HIGHLY DESIRED') !== false && $empty) {
                $desired[] = array(
                    'column' => $column,
                    'header' => isset($this->headers[$column]) ? $this->headers[$column] : null,
                    'requirement' => $ruleText
                );
            }
        }

        return array(
            'missing_required_count' => count($missing),
            'missing_required' => $missing,
            'missing_highly_desired_count' => count($desired),
            'missing_highly_desired' => $desired
        );
    }

    protected function is_empty_required_value($column, $value)
    {
        if ($value === null) {
            return true;
        }

        if (is_string($value)) {
            $v = trim($value);

            if ($v === '' || strtolower($v) === 'null') {
                return true;
            }

            // For date columns "-" means no milestone.
            if (in_array($column, $this->dateColumns, true) && $v === '-') {
                return true;
            }
        }

        return false;
    }

    protected function normalize_compare_value($column, $value)
    {
        if ($value === null) {
            return null;
        }

        if (in_array($column, $this->dateColumns, true)) {
            $s = trim((string)$value);

            if ($s === '' || $s === '-' || strtolower($s) === 'null') {
                return null;
            }

            $ts = strtotime($s);
            if ($ts !== false) {
                return date('Y-m-d', $ts);
            }

            return strtoupper($s);
        }

        if (is_numeric($value)) {
            return round((float)$value, 4);
        }

        $s = trim((string)$value);
        if ($s === '') {
            return null;
        }

        $s = preg_replace('/\s+/', ' ', $s);

        return strtoupper($s);
    }

    protected function kpi_output($row)
    {
        return array(
            'sailing_reliability' => array(
                'CI' => isset($row['CI']) ? $row['CI'] : null,
                'planned' => isset($row['BK']) ? $row['BK'] : null,
                'actual' => isset($row['BL']) ? $row['BL'] : null
            ),
            'departure_reliability' => array(
                'CN' => isset($row['CN']) ? $row['CN'] : null,
                'actual_field' => isset($row['CJ']) ? $row['CJ'] : null,
                'planned_field' => isset($row['CK']) ? $row['CK'] : null,
                'actual' => isset($row['CL']) ? $row['CL'] : null,
                'planned' => isset($row['CM']) ? $row['CM'] : null
            ),
            'arrival_reliability' => array(
                'CS' => isset($row['CS']) ? $row['CS'] : null,
                'actual_field' => isset($row['CO']) ? $row['CO'] : null,
                'planned_field' => isset($row['CP']) ? $row['CP'] : null,
                'actual' => isset($row['CQ']) ? $row['CQ'] : null,
                'planned' => isset($row['CR']) ? $row['CR'] : null
            ),
            'documentation_final_bl' => array(
                'CT' => isset($row['CT']) ? $row['CT'] : null,
                'BB_final_bl_atd' => isset($row['BB']) ? $row['BB'] : null,
                'BK_etd' => isset($row['BK']) ? $row['BK'] : null
            ),
            'leadtime_adherence' => array(
                'CU_actual_days' => isset($row['CU']) ? $row['CU'] : null,
                'CV_difference_days' => isset($row['CV']) ? $row['CV'] : null,
                'CW' => isset($row['CW']) ? $row['CW'] : null
            ),
            'booking_confirmation_responsiveness' => array(
                'CX' => isset($row['CX']) ? $row['CX'] : null
            ),
            'extra_cost' => array(
                'CY' => isset($row['CY']) ? $row['CY'] : null
            )
        );
    }

    protected function column_slice($row, $from, $to)
    {
        $out = array();
        $started = false;

        foreach ($row as $column => $value) {
            if ($column === '_meta') {
                continue;
            }

            if ($column === $from) {
                $started = true;
            }

            if ($started) {
                $out[$column] = $value;
            }

            if ($column === $to) {
                break;
            }
        }

        return $out;
    }

    protected function get_shipper_ids()
    {
        $raw = trim((string)$this->input->get('shipper_id', true));

        if ($raw === '') {
            return array(4407, 6412);
        }

        $ids = array();

        foreach (explode(',', $raw) as $id) {
            $id = trim($id);
            if ($id !== '' && ctype_digit($id)) {
                $ids[(int)$id] = (int)$id;
            }
        }

        return empty($ids) ? array(4407, 6412) : array_values($ids);
    }

    protected function without_meta($row)
    {
        if (isset($row['_meta'])) {
            unset($row['_meta']);
        }
        return $row;
    }

    protected function is_iso_date($value)
    {
        $dt = DateTime::createFromFormat('!Y-m-d', (string)$value);
        return $dt && $dt->format('Y-m-d') === (string)$value;
    }

    protected function guard_debug_access()
    {
        if ($this->input->is_cli_request()) {
            return;
        }

        $userId = $this->session->userdata('user_id');
        $fullName = trim((string)$this->session->userdata('fullname'));

        if (empty($userId) && $fullName === '') {
            show_error('Unauthorized Firmenich KPI debug access.', 401);
        }
    }

    protected function json($payload, $status = 200)
    {
        return $this->output
            ->set_status_header($status)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode(
                $payload,
                JSON_UNESCAPED_SLASHES |
                JSON_UNESCAPED_UNICODE |
                JSON_PRETTY_PRINT
            ));
    }
}
