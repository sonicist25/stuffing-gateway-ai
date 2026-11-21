<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Firmenich_kpi_sheet extends CI_Controller
{
    protected $cfg = array();

    public function __construct()
    {
        parent::__construct();

        $this->load->model('M_firmenich_kpi_report');
        $this->load->library('Firmenich_google_sheet_client');

        $this->config->load('firmenich_kpi_google_sheet', true);
        $cfg = $this->config->item('firmenich_kpi_google_sheet');

        if (is_array($cfg) && isset($cfg['firmenich_kpi_google_sheet'])) {
            $cfg = $cfg['firmenich_kpi_google_sheet'];
        }

        $this->cfg = is_array($cfg) ? $cfg : array();
    }

    /**
     * Existing Firmenich KPI report.
     *
     * IMPORTANT:
     * This endpoint stays dedicated to the original KPI workbook/template.
     * Pakistan monthly tabs are NOT created here.
     *
     * Example:
     * /firmenich_kpi_sheet/create?shipper_id=4407,6412&date_from=2026-01-01&date_to=2026-09-30
     */
    public function create()
    {
        try {
            $shipperIds = $this->shipper_ids();
            list($dateFrom, $dateTo) = $this->date_filters();

            $rows = $this->M_firmenich_kpi_report->get_rows_b_to_cy(
                $shipperIds,
                $dateFrom,
                $dateTo,
                false
            );

            if (empty($rows)) {
                return $this->json(array(
                    'status' => false,
                    'message' => 'Tidak ada shipment Firmenich untuk filter tersebut.'
                ), 404);
            }

            $result = $this->firmenich_google_sheet_client->create_report(
                $rows,
                array(
                    'report_name' => $this->build_report_name(
                        isset($this->cfg['report_name_prefix'])
                            ? $this->cfg['report_name_prefix']
                            : 'Firmenich KPI Report',
                        $dateFrom,
                        $dateTo
                    )
                )
            );

            return $this->json(array(
                'status' => true,
                'report_type' => 'kpi',
                'model_version' => M_firmenich_kpi_report::MODEL_VERSION,
                'filters' => array(
                    'shipper_ids' => $shipperIds,
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo
                ),
                'rows_written' => isset($result['rows_written'])
                    ? $result['rows_written']
                    : count($rows),
                'spreadsheet_id' => isset($result['spreadsheet_id'])
                    ? $result['spreadsheet_id']
                    : null,
                'spreadsheet_name' => isset($result['spreadsheet_name'])
                    ? $result['spreadsheet_name']
                    : null,
                'spreadsheet_url' => isset($result['spreadsheet_url'])
                    ? $result['spreadsheet_url']
                    : null,
                'sharing' => isset($result['sharing'])
                    ? $result['sharing']
                    : null
            ));
        } catch (Throwable $e) {
            log_message(
                'error',
                'FIRMENICH_KPI_GOOGLE_SHEET: '.$e->getMessage()
            );

            return $this->json(array(
                'status' => false,
                'message' => $e->getMessage()
            ), 500);
        }
    }

    /**
     * Dedicated Pakistan monthly shipment report.
     *
     * Creates a NEW blank Google Spreadsheet containing ONLY monthly Pakistan tabs:
     * PK Jan 2026, PK Feb 2026, ...
     *
     * Example:
     * /firmenich_kpi_sheet/create_pakistan?shipper_id=4407,6412&date_from=2026-01-01&date_to=2026-09-30
     */
    public function create_pakistan()
    {
        try {
            $shipperIds = $this->shipper_ids();
            list($dateFrom, $dateTo) = $this->date_filters();

            $pakistanMonthly = $this->M_firmenich_kpi_report->get_pakistan_monthly_report(
                $shipperIds,
                $dateFrom,
                $dateTo
            );

            $pakistanRows = isset($pakistanMonthly['rows']) && is_array($pakistanMonthly['rows'])
                ? $pakistanMonthly['rows']
                : array();

            if (empty($pakistanRows)) {
                return $this->json(array(
                    'status' => false,
                    'report_type' => 'pakistan_monthly',
                    'message' => 'Tidak ada shipment Firmenich ke Pakistan untuk filter tersebut.',
                    'filters' => array(
                        'shipper_ids' => $shipperIds,
                        'date_from' => $dateFrom,
                        'date_to' => $dateTo
                    )
                ), 404);
            }

            $prefix = isset($this->cfg['pakistan_report_name_prefix'])
                ? trim((string)$this->cfg['pakistan_report_name_prefix'])
                : 'Firmenich Pakistan Shipment Report';

            if ($prefix === '') {
                $prefix = 'Firmenich Pakistan Shipment Report';
            }

            $result = $this->firmenich_google_sheet_client->create_pakistan_report(
                $pakistanMonthly,
                array(
                    'report_name' => $this->build_report_name(
                        $prefix,
                        $dateFrom,
                        $dateTo
                    )
                )
            );

            return $this->json(array(
                'status' => true,
                'report_type' => 'pakistan_monthly',
                'model_version' => M_firmenich_kpi_report::MODEL_VERSION,
                'filters' => array(
                    'shipper_ids' => $shipperIds,
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo
                ),
                'pakistan_rows_written' => isset($result['pakistan_rows_written'])
                    ? $result['pakistan_rows_written']
                    : count($pakistanRows),
                'pakistan_tabs_written' => isset($result['pakistan_tabs_written'])
                    ? $result['pakistan_tabs_written']
                    : 0,
                'pakistan_tabs' => isset($result['pakistan_tabs'])
                    ? $result['pakistan_tabs']
                    : array(),
                'spreadsheet_id' => isset($result['spreadsheet_id'])
                    ? $result['spreadsheet_id']
                    : null,
                'spreadsheet_name' => isset($result['spreadsheet_name'])
                    ? $result['spreadsheet_name']
                    : null,
                'spreadsheet_url' => isset($result['spreadsheet_url'])
                    ? $result['spreadsheet_url']
                    : null,
                'sharing' => isset($result['sharing'])
                    ? $result['sharing']
                    : null
            ));
        } catch (Throwable $e) {
            log_message(
                'error',
                'FIRMENICH_PAKISTAN_GOOGLE_SHEET: '.$e->getMessage()
            );

            return $this->json(array(
                'status' => false,
                'report_type' => 'pakistan_monthly',
                'message' => $e->getMessage()
            ), 500);
        }
    }

    /**
     * Existing KPI JSON preview only.
     */
    public function preview()
    {
        $shipperIds = $this->shipper_ids();
        list($dateFrom, $dateTo) = $this->date_filters();

        $rows = $this->M_firmenich_kpi_report->get_rows_b_to_cy(
            $shipperIds,
            $dateFrom,
            $dateTo,
            false
        );

        return $this->json(array(
            'status' => true,
            'report_type' => 'kpi',
            'model_version' => M_firmenich_kpi_report::MODEL_VERSION,
            'total' => count($rows),
            'rows' => $rows
        ));
    }

    /**
     * Pakistan monthly JSON preview only; does NOT create Google Sheet.
     *
     * Example:
     * /firmenich_kpi_sheet/preview_pakistan?shipper_id=4407,6412&date_from=2026-01-01&date_to=2026-09-30
     */
    public function preview_pakistan()
    {
        $shipperIds = $this->shipper_ids();
        list($dateFrom, $dateTo) = $this->date_filters();

        $report = $this->M_firmenich_kpi_report->get_pakistan_monthly_report(
            $shipperIds,
            $dateFrom,
            $dateTo
        );

        return $this->json(array(
            'status' => true,
            'report_type' => 'pakistan_monthly',
            'model_version' => M_firmenich_kpi_report::MODEL_VERSION,
            'filters' => array(
                'shipper_ids' => $shipperIds,
                'date_from' => $dateFrom,
                'date_to' => $dateTo
            ),
            'total' => isset($report['rows']) && is_array($report['rows'])
                ? count($report['rows'])
                : 0,
            'pakistan_monthly' => $report
        ));
    }

    protected function date_filters()
    {
        $dateFromRaw = trim((string)$this->input->get('date_from', true));
        $dateToRaw = trim((string)$this->input->get('date_to', true));

        return array(
            $dateFromRaw !== '' ? $dateFromRaw : null,
            $dateToRaw !== '' ? $dateToRaw : null
        );
    }

    protected function build_report_name($prefix, $dateFrom, $dateTo)
    {
        $parts = array(trim((string)$prefix));

        if ($dateFrom !== null || $dateTo !== null) {
            $parts[] = trim((string)$dateFrom.' - '.(string)$dateTo, ' -');
        }

        $parts[] = date('Ymd_His');

        return trim(implode(' ', array_filter($parts, 'strlen')));
    }

    protected function shipper_ids()
    {
        $raw = trim((string)$this->input->get('shipper_id', true));

        if ($raw === '') {
            return isset($this->cfg['default_shipper_ids'])
                ? array_values((array)$this->cfg['default_shipper_ids'])
                : array(4407, 6412);
        }

        $result = array();

        foreach (explode(',', $raw) as $id) {
            $id = trim($id);

            if ($id !== '' && ctype_digit($id)) {
                $result[(int)$id] = (int)$id;
            }
        }

        return array_values($result);
    }

    protected function json(array $payload, $status = 200)
    {
        $this->output
            ->set_status_header($status)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode(
                $payload,
                JSON_PRETTY_PRINT |
                JSON_UNESCAPED_SLASHES |
                JSON_UNESCAPED_UNICODE
            ));

        return null;
    }

    public function config_check()
    {
        $this->config->load('firmenich_kpi_google_sheet', true);
        $cfg = $this->config->item('firmenich_kpi_google_sheet');

        if (isset($cfg['firmenich_kpi_google_sheet'])) {
            $cfg = $cfg['firmenich_kpi_google_sheet'];
        }

        $token = isset($cfg['api_token']) ? $cfg['api_token'] : '';

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'http_host' => isset($_SERVER['HTTP_HOST'])
                    ? $_SERVER['HTTP_HOST']
                    : null,
                'base_url' => $this->config->item('base_url'),
                'web_app_url' => isset($cfg['web_app_url'])
                    ? $cfg['web_app_url']
                    : null,
                'token_exists' => !empty($token),
                'token_length' => strlen($token),
            ), JSON_PRETTY_PRINT));
    }
}
