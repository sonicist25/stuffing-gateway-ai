<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Export monthly Firmenich leadtime report to Google Spreadsheet.
 *
 * Output design:
 * - One tab per period: YYYY-MM
 * - Clean header: PT FIRMENICH INDONESIA PERFORMANCE LEAD TIME TABLE
 * - KPI cards
 * - Dynamic top 10 lane summary
 * - Additional KPI tracker from DSM-Firmenich KPI list
 * - Detail section grouped by lane
 *
 * Requirements:
 * composer require google/apiclient:^2.0
 * Share target spreadsheet to the service account email as Editor.
 */
class Leadtime_google_sheet
{
    protected $CI;
    protected $enabled = false;
    protected $spreadsheet_id = '';
    protected $credentials_path = '';
    protected $sales_id = 33;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->database();
        $this->CI->config->load('google_sheets', true);

        $cfg = $this->CI->config->item('google_sheets');
        if (!is_array($cfg)) {
            $cfg = [];
        }

        $this->enabled          = !empty($cfg['leadtime_google_sheet_enabled']);
        $this->spreadsheet_id   = trim((string)($cfg['leadtime_google_sheet_spreadsheet_id'] ?? ''));
        $this->credentials_path = trim((string)($cfg['leadtime_google_sheet_credentials'] ?? ''));
        $this->sales_id = !empty($cfg['leadtime_google_sheet_sales_id']) ? (int)$cfg['leadtime_google_sheet_sales_id'] : 33;
    }

    public function export_monthly_report($run_id)
    {
        $run_id = (int)$run_id;
        if ($run_id <= 0) {
            throw new Exception('Invalid run_id for Google Sheet export');
        }

        if (!$this->enabled) {
            return [
                'status'  => true,
                'skipped' => true,
                'message' => 'Google Sheet export disabled',
            ];
        }

        if ($this->spreadsheet_id === '') {
            throw new Exception('Google Sheet spreadsheet_id is empty');
        }

        if ($this->credentials_path === '' || !file_exists($this->credentials_path)) {
            throw new Exception('Google service account credential file not found: ' . $this->credentials_path);
        }

        if (!is_readable($this->credentials_path)) {
            throw new Exception('Google service account credential file is not readable: ' . $this->credentials_path);
        }

        $run = $this->CI->db
            ->where('id', $run_id)
            ->get('lt_monthly_report_runs')
            ->row_array();

        if (!$run) {
            throw new Exception('Monthly report run not found: ' . $run_id);
        }

        $sheet_title = $this->make_sheet_title($run['period_month']);
        $service     = $this->get_service();
        $sheet_id    = $this->ensure_sheet($service, $this->spreadsheet_id, $sheet_title);
        $rows_data   = $this->build_rows($run_id, $run);
        $rows        = $rows_data['rows'];

        // Clear content only. Formatting will be overwritten by batch formatting below.
        $clear_request = new Google_Service_Sheets_ClearValuesRequest();
        $service->spreadsheets_values->clear(
            $this->spreadsheet_id,
            $this->range($sheet_title, 'A:Z'),
            $clear_request
        );

        $body = new Google_Service_Sheets_ValueRange([
            'values' => $rows,
        ]);

        $update_result = $service->spreadsheets_values->update(
            $this->spreadsheet_id,
            $this->range($sheet_title, 'A1'),
            $body,
            ['valueInputOption' => 'USER_ENTERED']
        );

        $this->format_sheet($service, $this->spreadsheet_id, $sheet_id, count($rows), $rows_data['meta']);

        return [
            'status'         => true,
            'skipped'        => false,
            'spreadsheet_id' => $this->spreadsheet_id,
            'sheet_title'    => $sheet_title,
            'updated_range'  => method_exists($update_result, 'getUpdatedRange') ? $update_result->getUpdatedRange() : null,
            'updated_rows'   => count($rows),
        ];
    }

    protected function get_service()
    {
        $autoload = FCPATH . 'vendor/autoload.php';
        if (!file_exists($autoload)) {
            throw new Exception('Google API client not found. Run: composer require google/apiclient:^2.0');
        }

        require_once $autoload;

        $client = new Google_Client();
        $client->setApplicationName('Gateway Firmenich Leadtime Report');
        $client->setScopes([Google_Service_Sheets::SPREADSHEETS]);
        $client->setAuthConfig($this->credentials_path);
        $client->setAccessType('offline');

        return new Google_Service_Sheets($client);
    }

    protected function ensure_sheet($service, $spreadsheet_id, $sheet_title)
    {
        $spreadsheet = $service->spreadsheets->get($spreadsheet_id);

        foreach ($spreadsheet->getSheets() as $sheet) {
            $props = $sheet->getProperties();
            if ($props && $props->getTitle() === $sheet_title) {
                return (int)$props->getSheetId();
            }
        }

        $batch = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
            'requests' => [
                [
                    'addSheet' => [
                        'properties' => [
                            'title' => $sheet_title,
                            'gridProperties' => [
                                'rowCount' => 1000,
                                'columnCount' => 20,
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $response = $service->spreadsheets->batchUpdate($spreadsheet_id, $batch);
        $replies = $response->getReplies();
        if (!empty($replies[0]) && $replies[0]->getAddSheet()) {
            return (int)$replies[0]->getAddSheet()->getProperties()->getSheetId();
        }

        // Fallback fetch.
        $spreadsheet = $service->spreadsheets->get($spreadsheet_id);
        foreach ($spreadsheet->getSheets() as $sheet) {
            $props = $sheet->getProperties();
            if ($props && $props->getTitle() === $sheet_title) {
                return (int)$props->getSheetId();
            }
        }

        throw new Exception('Failed to create/find Google Sheet tab: ' . $sheet_title);
    }

    protected function build_rows($run_id, array $run)
    {
        $summary = $this->CI->db
            ->where('run_id', $run_id)
            ->order_by('sort_no', 'ASC')
            ->get('lt_monthly_report_lane_summary')
            ->result_array();

        // Detail rows are enriched with ETD / ETA directly from x_tracking_export.
        // x_tracking_export uses id_tracking as primary key in this project, not id.
        $details = $this->get_details_with_schedule($run_id, (int)$run['shipper_id']);

        // KPI values that can already be calculated from x_tracking_export.etd / eta
        // and leadtime actual departure / arrival values.
        $additional_kpis = $this->calculate_additional_kpis($run_id, (int)$run['shipper_id']);

        $rows = [];
        $meta = [
            'summary_header' => null,
            'summary_start' => null,
            'summary_end' => null,
            'kpi_title' => null,
            'kpi_header' => null,
            'kpi_end' => null,
            'detail_period' => null,
            'detail_group_rows' => [],
            'detail_header_rows' => [],
            'detail_body_ranges' => [],
        ];

        $period_label = $this->period_label($run['period_month']);
        $open_total = (int)($run['missing_planned_gi'] ?? 0) + (int)($run['missing_departure'] ?? 0);

        // Header area A:M.
        $rows[] = ['PT FIRMENICH INDONESIA PERFORMANCE LEAD TIME TABLE'];
        $rows[] = [$period_label];
        $rows[] = [];
        $rows[] = [];
        $rows[] = [];

        // KPI cards.
        $rows[] = [
            '📦 Total Shipment',
            '✅ Complete Shipment',
            '⏳ Open / Not Departed',
            '🚢 Sailing / Not Arrived',
            '⚠️ Data Issue',
            '📈 Completion Rate',
        ];
        $rows[] = [
            (int)$run['total_shipment'],
            (int)$run['complete_shipment'],
            $open_total,
            (int)$run['missing_arrival'],
            (int)$run['data_issue'],
            $this->pct($run['complete_rate_percent']),
        ];
        $rows[] = [];

        // Lane summary.
        $meta['summary_header'] = count($rows);
        $rows[] = [
            'Lane',
            'Country',
            'Total',
            'Complete',
            'Open',
            'Arrival Pending',
            'Data Issue',
            'Complete %',
            'Avg TT1',
            'Avg TT2',
            'Avg TT3',
            'Best TT3',
            'Worst TT3',
        ];
        $meta['summary_start'] = count($rows);

        foreach ($summary as $row) {
            $missing_planned = (int)($row['missing_planned_gi'] ?? 0);
            $missing_departure = (int)$row['missing_departure'];
            $country = trim((string)($row['country'] ?? ''));
            if ($country === '') {
                $country = $this->guess_country_from_lane($row['lane']);
            }

            $rows[] = [
                $row['lane'],
                $country,
                (int)$row['total_shipment'],
                (int)$row['complete_shipment'],
                $missing_planned + $missing_departure,
                (int)$row['missing_arrival'],
                (int)$row['data_issue'],
                $this->pct($row['complete_rate_percent']),
                $this->num_or_na($row['avg_tt1_days']),
                $this->num_or_na($row['avg_tt2_days']),
                $this->num_or_na($row['avg_tt3_days']),
                $this->num_or_na($row['best_tt3_days']),
                $this->num_or_na($row['worst_tt3_days']),
            ];
        }
        $meta['summary_end'] = count($rows);

        $rows[] = [];
        $rows[] = [];

        // Additional KPI tracker. These are included as columns even when data source is not integrated yet.
        $meta['kpi_title'] = count($rows);
        $rows[] = ['📊 ADDITIONAL KPI TRACKER'];
        $meta['kpi_header'] = count($rows);
        $rows[] = [
            'KPI Name',
            'Definition',
            'Measurement',
            'Target',
            'Actual',
            'Numerator',
            'Denominator',
            'Data Source / Status',
            'Remarks',
        ];

        foreach ($this->additional_kpi_rows($additional_kpis) as $kpi) {
            $rows[] = $kpi;
        }
        $meta['kpi_end'] = count($rows);

        $rows[] = [];
        $rows[] = [];

        // Detail grouped by lane.
        $meta['detail_period'] = count($rows);
        $rows[] = ['🗓️ PERIODE: ' . strtoupper($period_label)];

        $details_by_lane = [];
        foreach ($details as $row) {
            $lane = trim((string)$row['lane']);
            if ($lane === '') {
                $lane = 'UNKNOWN';
            }
            if (!isset($details_by_lane[$lane])) {
                $details_by_lane[$lane] = [];
            }
            $details_by_lane[$lane][] = $row;
        }

        foreach ($summary as $sum) {
            $lane = trim((string)$sum['lane']);
            if ($lane === '' || empty($details_by_lane[$lane])) {
                continue;
            }

            $country = trim((string)($sum['country'] ?? ''));
            if ($country === '') {
                $country = $this->guess_country_from_lane($lane);
            }

            $meta['detail_group_rows'][] = count($rows);
            $rows[] = [$this->lane_display_title($lane, $country)];

            $meta['detail_header_rows'][] = count($rows);
            $rows[] = [
                'HBL No.',
                'Job No.',
                'ETD',
                'ETA',
                'Planned GI',
                'Act. Dep',
                'Act. Arr',
                'TT1',
                'TT2',
                'TT3',
                'Status',
                'Issue Note',
            ];

            $detail_body_start = count($rows);
            foreach ($details_by_lane[$lane] as $row) {
                $rows[] = [
                    $this->clean_cell($row['hbl_no'] ?? ''),
                    $this->clean_cell($row['no_job'] ?? ''),
                    $this->fmt_mixed_date($row['etd_raw'] ?? ''),
                    $this->fmt_mixed_date($row['eta_raw'] ?? ''),
                    $this->fmt_datetime($row['planned_gi_at']),
                    $this->fmt_datetime($row['actual_departure_at']),
                    $this->fmt_datetime($row['actual_pod_arrival_at']),
                    $this->num_or_blank($row['tt1_days']),
                    $this->num_or_blank($row['tt2_days']),
                    $this->num_or_blank($row['tt3_days']),
                    $this->clean_cell($row['lt_status'] ?? ''),
                    $this->clean_cell($row['lt_issue_note'] ?? ''),
                ];
            }
            if (count($rows) > $detail_body_start) {
                $meta['detail_body_ranges'][] = [$detail_body_start, count($rows)];
            }

            $rows[] = [];
        }

        return [
            'rows' => $rows,
            'meta' => $meta,
        ];
    }

    protected function get_details_with_schedule($run_id, $shipper_id)
    {
        $hbl_col  = $this->get_detail_hbl_column();
        $hbl_expr = 'd.`' . $hbl_col . '`';

        $sql = "
SELECT
    " . $hbl_expr . " AS hbl_no,
    d.no_job,
    d.lane,
    d.country,
    d.destination,
    d.consol_destination,
    d.planned_gi_at,
    d.actual_departure_at,
    d.actual_pod_arrival_at,
    d.tt1_days,
    d.tt2_days,
    d.tt3_days,
    d.lt_status,
    d.lt_issue_note,
    xsrc.etd AS etd_raw,
    xsrc.eta AS eta_raw
FROM lt_monthly_report_details d
LEFT JOIN (
    SELECT
        UPPER(TRIM(hbl_no)) AS hbl_key,
        MAX(etd) AS etd,
        MAX(eta) AS eta
    FROM x_tracking_export
    WHERE id_shipper = ?
      AND id_sales = ?
      AND hbl_no IS NOT NULL
      AND TRIM(hbl_no) <> ''
    GROUP BY UPPER(TRIM(hbl_no))
) xsrc
    ON xsrc.hbl_key = UPPER(TRIM(" . $hbl_expr . "))
WHERE d.run_id = ?
ORDER BY
    d.lane ASC,
    d.planned_gi_at ASC,
    " . $hbl_expr . " ASC
";

        return $this->CI->db->query($sql, [(string)$shipper_id, (string)$this->sales_id, (int)$run_id])->result_array();
    }

    protected function calculate_additional_kpis($run_id, $shipper_id)
    {
        $hbl_col  = $this->get_detail_hbl_column();
        $hbl_expr = 'd.`' . $hbl_col . '`';

        $sql = "
SELECT
    COUNT(DISTINCT hbl_no) AS denominator,

    COUNT(DISTINCT CASE
        WHEN etd_at IS NOT NULL
         AND actual_departure_at IS NOT NULL
         AND ABS(TIMESTAMPDIFF(HOUR, etd_at, actual_departure_at)) <= 24
        THEN hbl_no
    END) AS departure_numerator,

    COUNT(DISTINCT CASE WHEN etd_at IS NULL THEN hbl_no END) AS missing_etd,
    COUNT(DISTINCT CASE WHEN actual_departure_at IS NULL THEN hbl_no END) AS missing_atd,

    COUNT(DISTINCT CASE
        WHEN eta_at IS NOT NULL
         AND actual_pod_arrival_at IS NOT NULL
         AND ABS(TIMESTAMPDIFF(HOUR, eta_at, actual_pod_arrival_at)) <= 72
        THEN hbl_no
    END) AS arrival_numerator,

    COUNT(DISTINCT CASE WHEN eta_at IS NULL THEN hbl_no END) AS missing_eta,
    COUNT(DISTINCT CASE WHEN actual_pod_arrival_at IS NULL THEN hbl_no END) AS missing_ata

FROM (
    SELECT
        hbl_no,
        actual_departure_at,
        actual_pod_arrival_at,

        CASE
            WHEN etd_clean IS NULL OR etd_clean = '' OR UPPER(etd_clean) = 'NULL' THEN NULL
            WHEN etd_clean REGEXP '^[0-9]{2}/[0-9]{2}/[0-9]{4} [0-9]{2}:[0-9]{2}:[0-9]{2}$'
                THEN STR_TO_DATE(etd_clean, '%d/%m/%Y %H:%i:%s')
            WHEN etd_clean REGEXP '^[0-9]{2}/[0-9]{2}/[0-9]{4}$'
                THEN STR_TO_DATE(etd_clean, '%d/%m/%Y')
            WHEN etd_clean REGEXP '^[0-9]{8}$'
                THEN STR_TO_DATE(etd_clean, '%Y%m%d')
            WHEN etd_clean REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}$'
                THEN STR_TO_DATE(etd_clean, '%Y-%m-%d')
            WHEN etd_clean REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$'
                THEN STR_TO_DATE(etd_clean, '%Y-%m-%d %H:%i:%s')
            ELSE NULL
        END AS etd_at,

        CASE
            WHEN eta_clean IS NULL OR eta_clean = '' OR UPPER(eta_clean) = 'NULL' THEN NULL
            WHEN eta_clean REGEXP '^[0-9]{2}/[0-9]{2}/[0-9]{4} [0-9]{2}:[0-9]{2}:[0-9]{2}$'
                THEN STR_TO_DATE(eta_clean, '%d/%m/%Y %H:%i:%s')
            WHEN eta_clean REGEXP '^[0-9]{2}/[0-9]{2}/[0-9]{4}$'
                THEN STR_TO_DATE(eta_clean, '%d/%m/%Y')
            WHEN eta_clean REGEXP '^[0-9]{8}$'
                THEN STR_TO_DATE(eta_clean, '%Y%m%d')
            WHEN eta_clean REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}$'
                THEN STR_TO_DATE(eta_clean, '%Y-%m-%d')
            WHEN eta_clean REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$'
                THEN STR_TO_DATE(eta_clean, '%Y-%m-%d %H:%i:%s')
            ELSE NULL
        END AS eta_at

    FROM (
        SELECT
            " . $hbl_expr . " AS hbl_no,
            d.actual_departure_at,
            d.actual_pod_arrival_at,
            TRIM(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(xsrc.etd, ''), '*', ''), '\\r', ''), '\\n', ''), '  ', ' ')) AS etd_clean,
            TRIM(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(xsrc.eta, ''), '*', ''), '\\r', ''), '\\n', ''), '  ', ' ')) AS eta_clean
        FROM lt_monthly_report_details d
        LEFT JOIN (
            SELECT
                UPPER(TRIM(hbl_no)) AS hbl_key,
                MAX(etd) AS etd,
                MAX(eta) AS eta
            FROM x_tracking_export
            WHERE id_shipper = ?
              AND id_sales = ?
              AND hbl_no IS NOT NULL
              AND TRIM(hbl_no) <> ''
            GROUP BY UPPER(TRIM(hbl_no))
        ) xsrc
            ON xsrc.hbl_key = UPPER(TRIM(" . $hbl_expr . "))
        WHERE d.run_id = ?
    ) raw
) parsed
";

        $row = $this->CI->db->query($sql, [(string)$shipper_id, (string)$this->sales_id, (int)$run_id])->row_array();
        if (!$row) {
            $row = [];
        }

        $den = (int)($row['denominator'] ?? 0);
        $dep_num = (int)($row['departure_numerator'] ?? 0);
        $arr_num = (int)($row['arrival_numerator'] ?? 0);

        return [
            'departure_reliability' => [
                'numerator' => $dep_num,
                'denominator' => $den,
                'actual_percent' => $den > 0 ? round(($dep_num / $den) * 100, 2) : null,
                'missing_etd' => (int)($row['missing_etd'] ?? 0),
                'missing_atd' => (int)($row['missing_atd'] ?? 0),
            ],
            'arrival_reliability' => [
                'numerator' => $arr_num,
                'denominator' => $den,
                'actual_percent' => $den > 0 ? round(($arr_num / $den) * 100, 2) : null,
                'missing_eta' => (int)($row['missing_eta'] ?? 0),
                'missing_ata' => (int)($row['missing_ata'] ?? 0),
            ],
        ];
    }

    protected function additional_kpi_rows(array $kpi)
    {
        $dep = $kpi['departure_reliability'];
        $arr = $kpi['arrival_reliability'];

        return [
            [
                'Departure Reliability (ATD vs ETD ≤ 1 day)',
                'Percentage of shipments where the Actual Time of Departure (ATD) is within 1 calendar day of the Estimated Time of Departure (ETD).',
                '(Shipments with ≤ 1 day difference between ATD and ETD ÷ Total shipments) × 100',
                '85%',
                $this->pct($dep['actual_percent']),
                (int)$dep['numerator'],
                (int)$dep['denominator'],
                'Calculated from x_tracking_export.etd and lt_actual_departure_at.',
                'ETD missing: ' . (int)$dep['missing_etd'] . '; ATD missing: ' . (int)$dep['missing_atd'],
            ],
            [
                'Arrival Reliability (ATA vs ETA ≤ 3 days)',
                'Percentage of shipments where the Actual Time of Arrival (ATA) is within 3 calendar days of the Estimated Time of Arrival (ETA), measured after vessel departure.',
                '(Shipments with ≤ 3 days difference between ATA and ETA ÷ Total shipments) × 100',
                '95%',
                $this->pct($arr['actual_percent']),
                (int)$arr['numerator'],
                (int)$arr['denominator'],
                'Calculated from x_tracking_export.eta and lt_actual_pod_arrival_at.',
                'ETA missing: ' . (int)$arr['missing_eta'] . '; ATA missing: ' . (int)$arr['missing_ata'],
            ],
            [
                'Documentation final BL',
                'Receipt of the final/approved BL latest 2 working days after sailing.',
                'BL received date - vessel sailing date',
                '90%',
                'N/A',
                '',
                '',
                'Source field not confirmed yet.',
                'Column prepared; can be automated after final BL received date source is mapped.',
            ],
            [
                'Leadtime Adherence',
                'Percentage of shipments where total leadtime matches the agreed contractual time window.',
                '(Shipments with actual leadtime within agreed time window ÷ Total shipments) × 100',
                '90%',
                'N/A',
                '',
                '',
                'Agreed leadtime target per lane required.',
                'Add target table per lane to automate.',
            ],
            [
                'Booking Confirmation responsiveness',
                'Percentage of bookings confirmed within contractual limits: ≤ 1 day for non-DG, ≤ 2 days for DG.',
                '(Bookings confirmed within target time ÷ Total booking requests) × 100',
                '95%',
                'N/A',
                '',
                '',
                'Booking request date, confirmation date, and DG flag required.',
                'Column prepared for future integration with booking module.',
            ],
            [
                'Non-Compliance Returns',
                'Measures supplier compliance with container and equipment standards to prevent shipment rejections. Includes cleanliness, condition, and proper functioning of equipment.',
                'Number of rejections',
                '0',
                'N/A',
                '',
                '',
                'Manual quality/non-compliance source not integrated.',
                'Column prepared for manual/future input.',
            ],
            [
                'Safety Incident Rate',
                'Number of supplier-caused near misses and recordable accidents during ocean transport.',
                'Count of supplier-caused near misses + recordable accidents',
                '0',
                'N/A',
                '',
                '',
                'Manual safety source not integrated.',
                'Column prepared for manual/future input.',
            ],
            [
                'Investigation Timeliness (CRT-S)',
                'Measures supplier performance in providing complete and approved investigation results within 4 calendar days after a supplier-caused quality complaint.',
                '(Supplier investigations submitted within 4 calendar days ÷ Total supplier-caused complaints) × 100',
                '100%',
                'N/A',
                '',
                '',
                'Complaint and investigation date source not integrated.',
                'Column prepared for future complaint module mapping.',
            ],
        ];
    }

    protected function format_sheet($service, $spreadsheet_id, $sheet_id, $row_count, array $meta)
    {
        $requests = [];

        // Remove old merges from previous exports, so rerun is safe.
        $requests[] = [
            'unmergeCells' => [
                'range' => [
                    'sheetId' => $sheet_id,
                    'startRowIndex' => 0,
                    'endRowIndex' => max($row_count, 1),
                    'startColumnIndex' => 0,
                    'endColumnIndex' => 20,
                ],
            ],
        ];

        // Sheet baseline.
        $requests[] = [
            'updateSheetProperties' => [
                'properties' => [
                    'sheetId' => $sheet_id,
                    'gridProperties' => [
                        'frozenRowCount' => 8,
                        'hideGridlines' => true,
                    ],
                ],
                'fields' => 'gridProperties.frozenRowCount,gridProperties.hideGridlines',
            ],
        ];

        // Merge title and period rows across A:M.
        $requests[] = $this->merge_request($sheet_id, 0, 1, 0, 13);
        $requests[] = $this->merge_request($sheet_id, 1, 2, 0, 13);

        // Default font and wrapping in used range.
        $requests[] = $this->repeat_format($sheet_id, 0, max($row_count, 1), 0, 13, [
            'textFormat' => ['fontFamily' => 'Arial', 'fontSize' => 10],
            'verticalAlignment' => 'MIDDLE',
            'wrapStrategy' => 'CLIP',
        ], 'userEnteredFormat.textFormat.fontFamily,userEnteredFormat.textFormat.fontSize,userEnteredFormat.verticalAlignment,userEnteredFormat.wrapStrategy');

        // Reset row heights from previous exports. Without this, old wrapped rows can stay very tall.
        $requests[] = $this->row_height_request($sheet_id, 0, max($row_count, 1), 24);

        // Title row.
        $requests[] = $this->repeat_format($sheet_id, 0, 1, 0, 13, [
            'backgroundColor' => $this->rgb('1F4E78'),
            'horizontalAlignment' => 'CENTER',
            'verticalAlignment' => 'MIDDLE',
            'textFormat' => ['bold' => true, 'fontSize' => 16, 'foregroundColor' => $this->rgb('FFFFFF')],
        ], 'userEnteredFormat.backgroundColor,userEnteredFormat.horizontalAlignment,userEnteredFormat.verticalAlignment,userEnteredFormat.textFormat');

        // Period row.
        $requests[] = $this->repeat_format($sheet_id, 1, 2, 0, 13, [
            'backgroundColor' => $this->rgb('D9EAF7'),
            'horizontalAlignment' => 'CENTER',
            'textFormat' => ['bold' => true, 'fontSize' => 12, 'foregroundColor' => $this->rgb('1F1F1F')],
        ], 'userEnteredFormat.backgroundColor,userEnteredFormat.horizontalAlignment,userEnteredFormat.textFormat');

        // KPI cards.
        $requests[] = $this->repeat_format($sheet_id, 5, 6, 0, 6, [
            'backgroundColor' => $this->rgb('E2F0D9'),
            'horizontalAlignment' => 'CENTER',
            'textFormat' => ['bold' => true, 'fontSize' => 10],
            'borders' => $this->thin_borders('A6A6A6'),
        ], 'userEnteredFormat.backgroundColor,userEnteredFormat.horizontalAlignment,userEnteredFormat.textFormat,userEnteredFormat.borders');

        $requests[] = $this->repeat_format($sheet_id, 6, 7, 0, 6, [
            'backgroundColor' => $this->rgb('F7FBF3'),
            'horizontalAlignment' => 'CENTER',
            'textFormat' => ['bold' => true, 'fontSize' => 12],
            'borders' => $this->thin_borders('A6A6A6'),
        ], 'userEnteredFormat.backgroundColor,userEnteredFormat.horizontalAlignment,userEnteredFormat.textFormat,userEnteredFormat.borders');

        // Summary header.
        if ($meta['summary_header'] !== null) {
            $requests[] = $this->repeat_format($sheet_id, $meta['summary_header'], $meta['summary_header'] + 1, 0, 13, [
                'backgroundColor' => $this->rgb('1F4E78'),
                'horizontalAlignment' => 'CENTER',
                'textFormat' => ['bold' => true, 'foregroundColor' => $this->rgb('FFFFFF')],
                'borders' => $this->thin_borders('808080'),
                'wrapStrategy' => 'CLIP',
            ], 'userEnteredFormat.backgroundColor,userEnteredFormat.horizontalAlignment,userEnteredFormat.textFormat,userEnteredFormat.borders,userEnteredFormat.wrapStrategy');
        }

        // Summary body borders.
        if ($meta['summary_start'] !== null && $meta['summary_end'] !== null && $meta['summary_end'] > $meta['summary_start']) {
            $requests[] = $this->repeat_format($sheet_id, $meta['summary_start'], $meta['summary_end'], 0, 13, [
                'borders' => $this->thin_borders('D9D9D9'),
                'verticalAlignment' => 'MIDDLE',
                'wrapStrategy' => 'CLIP',
            ], 'userEnteredFormat.borders,userEnteredFormat.verticalAlignment,userEnteredFormat.wrapStrategy');
        }

        // Additional KPI title + header.
        if ($meta['kpi_title'] !== null) {
            $requests[] = $this->merge_request($sheet_id, $meta['kpi_title'], $meta['kpi_title'] + 1, 0, 9);
            $requests[] = $this->repeat_format($sheet_id, $meta['kpi_title'], $meta['kpi_title'] + 1, 0, 9, [
                'backgroundColor' => $this->rgb('F4B183'),
                'horizontalAlignment' => 'LEFT',
                'textFormat' => ['bold' => true, 'fontSize' => 12],
                'wrapStrategy' => 'CLIP',
            ], 'userEnteredFormat.backgroundColor,userEnteredFormat.horizontalAlignment,userEnteredFormat.textFormat,userEnteredFormat.wrapStrategy');
        }

        if ($meta['kpi_header'] !== null) {
            $requests[] = $this->repeat_format($sheet_id, $meta['kpi_header'], $meta['kpi_header'] + 1, 0, 9, [
                'backgroundColor' => $this->rgb('C65911'),
                'horizontalAlignment' => 'CENTER',
                'textFormat' => ['bold' => true, 'foregroundColor' => $this->rgb('FFFFFF')],
                'borders' => $this->thin_borders('808080'),
                'wrapStrategy' => 'CLIP',
            ], 'userEnteredFormat.backgroundColor,userEnteredFormat.horizontalAlignment,userEnteredFormat.textFormat,userEnteredFormat.borders,userEnteredFormat.wrapStrategy');
        }

        if ($meta['kpi_header'] !== null && $meta['kpi_end'] !== null && $meta['kpi_end'] > $meta['kpi_header'] + 1) {
            $requests[] = $this->repeat_format($sheet_id, $meta['kpi_header'] + 1, $meta['kpi_end'], 0, 9, [
                'borders' => $this->thin_borders('D9D9D9'),
                'verticalAlignment' => 'MIDDLE',
            ], 'userEnteredFormat.borders,userEnteredFormat.verticalAlignment');
        }

        // Detail period title.
        if ($meta['detail_period'] !== null) {
            $requests[] = $this->merge_request($sheet_id, $meta['detail_period'], $meta['detail_period'] + 1, 0, 12);
            $requests[] = $this->repeat_format($sheet_id, $meta['detail_period'], $meta['detail_period'] + 1, 0, 12, [
                'backgroundColor' => $this->rgb('D9EAD3'),
                'textFormat' => ['bold' => true, 'fontSize' => 12],
            ], 'userEnteredFormat.backgroundColor,userEnteredFormat.textFormat');
        }

        foreach ($meta['detail_group_rows'] as $r) {
            $requests[] = $this->merge_request($sheet_id, $r, $r + 1, 0, 12);
            $requests[] = $this->repeat_format($sheet_id, $r, $r + 1, 0, 12, [
                'backgroundColor' => $this->rgb('DDEBF7'),
                'textFormat' => ['bold' => true],
            ], 'userEnteredFormat.backgroundColor,userEnteredFormat.textFormat');
        }

        foreach ($meta['detail_header_rows'] as $r) {
            $requests[] = $this->repeat_format($sheet_id, $r, $r + 1, 0, 12, [
                'backgroundColor' => $this->rgb('5B9BD5'),
                'horizontalAlignment' => 'CENTER',
                'verticalAlignment' => 'MIDDLE',
                'textFormat' => ['bold' => true, 'foregroundColor' => $this->rgb('FFFFFF')],
                'borders' => $this->thin_borders('808080'),
                'wrapStrategy' => 'CLIP',
            ], 'userEnteredFormat.backgroundColor,userEnteredFormat.horizontalAlignment,userEnteredFormat.verticalAlignment,userEnteredFormat.textFormat,userEnteredFormat.borders,userEnteredFormat.wrapStrategy');
        }

        foreach (($meta['detail_body_ranges'] ?? []) as $range) {
            $start = (int)$range[0];
            $end   = (int)$range[1];
            if ($end <= $start) {
                continue;
            }
            $requests[] = $this->repeat_format($sheet_id, $start, $end, 0, 12, [
                'verticalAlignment' => 'MIDDLE',
                'wrapStrategy' => 'CLIP',
                'borders' => $this->thin_borders('E6E6E6'),
            ], 'userEnteredFormat.verticalAlignment,userEnteredFormat.wrapStrategy,userEnteredFormat.borders');

            // Center dates / numbers / status columns only.
            $requests[] = $this->repeat_format($sheet_id, $start, $end, 2, 11, [
                'horizontalAlignment' => 'CENTER',
            ], 'userEnteredFormat.horizontalAlignment');
        }

        // Column widths.
        $widths = [
            0  => 220, // A HBL / Lane
            1  => 165, // B Job / Country
            2  => 90,  // C ETD / Total
            3  => 90,  // D ETA / Complete
            4  => 125, // E Planned GI / Open
            5  => 125, // F Act. Dep / Arrival Pending
            6  => 125, // G Act. Arr / Data Issue
            7  => 75,  // H TT1 / Complete %
            8  => 75,  // I TT2 / Avg TT1
            9  => 75,  // J TT3 / Avg TT2
            10 => 135, // K Status / Avg TT3
            11 => 360, // L Issue Note / Best TT3
            12 => 110, // M Worst TT3
        ];
        foreach ($widths as $col => $px) {
            $requests[] = $this->column_width_request($sheet_id, $col, $col + 1, $px);
        }

        // Bigger rows for title/cards.
        $requests[] = $this->row_height_request($sheet_id, 0, 1, 34);
        $requests[] = $this->row_height_request($sheet_id, 1, 2, 28);
        $requests[] = $this->row_height_request($sheet_id, 5, 7, 32);

        // Auto resize remaining visible columns if needed, after fixed widths for key columns.
        $requests[] = [
            'autoResizeDimensions' => [
                'dimensions' => [
                    'sheetId' => $sheet_id,
                    'dimension' => 'COLUMNS',
                    'startIndex' => 13,
                    'endIndex' => 20,
                ],
            ],
        ];

        $batch = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
            'requests' => $requests,
        ]);

        $service->spreadsheets->batchUpdate($spreadsheet_id, $batch);
    }

    protected function get_detail_hbl_column()
    {
        if ($this->CI->db->field_exists('hbl_no', 'lt_monthly_report_details')) {
            return 'hbl_no';
        }
        if ($this->CI->db->field_exists('hbl', 'lt_monthly_report_details')) {
            return 'hbl';
        }
        if ($this->CI->db->field_exists('hbl_number', 'lt_monthly_report_details')) {
            return 'hbl_number';
        }

        throw new Exception('No HBL column found in lt_monthly_report_details. Expected hbl_no / hbl / hbl_number. Please check DESCRIBE lt_monthly_report_details.');
    }

    protected function make_sheet_title($period_month)
    {
        $period_month = trim((string)$period_month);
        if (!preg_match('/^\d{4}-\d{2}$/', $period_month)) {
            $period_month = date('Y-m');
        }
        return $period_month;
    }

    protected function range($sheet_title, $a1)
    {
        $safe_title = str_replace("'", "''", $sheet_title);
        return "'" . $safe_title . "'!" . $a1;
    }

    protected function clean_cell($value)
    {
        $value = trim((string)$value);
        if ($value === '') {
            return '';
        }

        // Keep cells one-line to avoid chaotic row heights in Google Sheets.
        $value = str_replace(["
", "
", "	"], ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value);

        return trim($value);
    }

    protected function fmt_mixed_date($value)
    {
        $value = trim((string)$value);
        if ($value === '' || strtolower($value) === 'null' || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
            return '';
        }

        $value = trim(str_replace(['*', "\r", "\n"], '', $value));
        $value = trim(strtok($value, '('));

        $formats = ['d/m/Y H:i:s', 'd/m/Y H:i', 'd/m/Y', 'Ymd', 'Y-m-d H:i:s', 'Y-m-d'];
        foreach ($formats as $format) {
            $dt = DateTime::createFromFormat($format, $value);
            if ($dt instanceof DateTime) {
                return $dt->format('d/m/Y');
            }
        }

        return $value;
    }

    protected function fmt_datetime($value)
    {
        $value = trim((string)$value);
        if ($value === '' || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
            return '';
        }

        $ts = strtotime($value);
        if ($ts === false) {
            return $value;
        }

        return date('d/m/Y H:i', $ts);
    }

    protected function num_or_blank($value)
    {
        if ($value === null || $value === '') {
            return '';
        }
        return (float)$value;
    }

    protected function num_or_na($value)
    {
        if ($value === null || $value === '') {
            return 'N/A';
        }
        return (float)$value;
    }

    protected function pct($value)
    {
        if ($value === null || $value === '') {
            return 'N/A';
        }
        return number_format((float)$value, 2, '.', '') . '%';
    }

    protected function period_label($period_month)
    {
        $period_month = trim((string)$period_month);
        if (!preg_match('/^(\d{4})-(\d{2})$/', $period_month, $m)) {
            return strtoupper(date('F Y'));
        }

        $months = [
            '01' => 'Januari',
            '02' => 'Februari',
            '03' => 'Maret',
            '04' => 'April',
            '05' => 'Mei',
            '06' => 'Juni',
            '07' => 'Juli',
            '08' => 'Agustus',
            '09' => 'September',
            '10' => 'Oktober',
            '11' => 'November',
            '12' => 'Desember',
        ];

        return ($months[$m[2]] ?? $m[2]) . ' ' . $m[1];
    }

    protected function lane_display_title($lane, $country = '')
    {
        $name = trim((string)$lane);
        $name = preg_replace('/^Jakarta\s*-\s*/i', '', $name);
        $country = trim((string)$country);

        if ($country !== '') {
            return '📍 ' . $name . ' (' . $country . ')';
        }

        return '📍 ' . $name;
    }

    protected function guess_country_from_lane($lane)
    {
        $lane = strtoupper((string)$lane);

        $map = [
            'HO CHI MINH' => 'Vietnam',
            'HAIPHONG' => 'Vietnam',
            'HAI PHONG' => 'Vietnam',
            'MANILA' => 'Philippines',
            'PORT KELANG' => 'Malaysia',
            'PORT KLANG' => 'Malaysia',
            'PENANG' => 'Malaysia',
            'SYDNEY' => 'Australia',
            'LAEM CHABANG' => 'Thailand',
            'BANGKOK' => 'Thailand',
            'SHANGHAI' => 'China',
            'JAWAHARLAL' => 'India',
            'NHAVA' => 'India',
            'CHENNAI' => 'India',
            'COCHIN' => 'India',
            'JACKSONVILLE' => 'USA',
            'NEW YORK' => 'USA',
            'ISTANBUL' => 'Turkey',
            'GEBZE' => 'Turkey',
            'YOKOHAMA' => 'Japan',
            'BUSAN' => 'South Korea',
            'PUSAN' => 'South Korea',
            'KARACHI' => 'Pakistan',
            'ANTWERP' => 'Belgium',
            'AQABA' => 'Jordan',
            'BUENOS AIRES' => 'Argentina',
            'CHITTAGONG' => 'Bangladesh',
            'COLOMBO' => 'Sri Lanka',
            'DURBAN' => 'South Africa',
            'KAOHSIUNG' => 'Taiwan',
            'KEELUNG' => 'Taiwan',
            'MANZANILLO' => 'Mexico',
            'VERACRUZ' => 'Mexico',
            'SIHANOUKVILLE' => 'Cambodia',
            'SINGAPORE' => 'Singapore',
            'YANGON' => 'Myanmar',
        ];

        foreach ($map as $keyword => $country) {
            if (strpos($lane, $keyword) !== false) {
                return $country;
            }
        }

        return '';
    }

    protected function merge_request($sheet_id, $start_row, $end_row, $start_col, $end_col)
    {
        return [
            'mergeCells' => [
                'range' => [
                    'sheetId' => $sheet_id,
                    'startRowIndex' => $start_row,
                    'endRowIndex' => $end_row,
                    'startColumnIndex' => $start_col,
                    'endColumnIndex' => $end_col,
                ],
                'mergeType' => 'MERGE_ALL',
            ],
        ];
    }

    protected function repeat_format($sheet_id, $start_row, $end_row, $start_col, $end_col, array $format, $fields)
    {
        return [
            'repeatCell' => [
                'range' => [
                    'sheetId' => $sheet_id,
                    'startRowIndex' => $start_row,
                    'endRowIndex' => $end_row,
                    'startColumnIndex' => $start_col,
                    'endColumnIndex' => $end_col,
                ],
                'cell' => [
                    'userEnteredFormat' => $format,
                ],
                'fields' => $fields,
            ],
        ];
    }

    protected function column_width_request($sheet_id, $start_col, $end_col, $pixel_size)
    {
        return [
            'updateDimensionProperties' => [
                'range' => [
                    'sheetId' => $sheet_id,
                    'dimension' => 'COLUMNS',
                    'startIndex' => $start_col,
                    'endIndex' => $end_col,
                ],
                'properties' => [
                    'pixelSize' => $pixel_size,
                ],
                'fields' => 'pixelSize',
            ],
        ];
    }

    protected function row_height_request($sheet_id, $start_row, $end_row, $pixel_size)
    {
        return [
            'updateDimensionProperties' => [
                'range' => [
                    'sheetId' => $sheet_id,
                    'dimension' => 'ROWS',
                    'startIndex' => $start_row,
                    'endIndex' => $end_row,
                ],
                'properties' => [
                    'pixelSize' => $pixel_size,
                ],
                'fields' => 'pixelSize',
            ],
        ];
    }

    protected function thin_borders($hex)
    {
        $color = $this->rgb($hex);
        $border = [
            'style' => 'SOLID',
            'width' => 1,
            'color' => $color,
        ];

        return [
            'top' => $border,
            'bottom' => $border,
            'left' => $border,
            'right' => $border,
        ];
    }

    protected function rgb($hex)
    {
        $hex = ltrim((string)$hex, '#');
        if (strlen($hex) !== 6) {
            $hex = 'FFFFFF';
        }

        return [
            'red' => hexdec(substr($hex, 0, 2)) / 255,
            'green' => hexdec(substr($hex, 2, 2)) / 255,
            'blue' => hexdec(substr($hex, 4, 2)) / 255,
        ];
    }
}
