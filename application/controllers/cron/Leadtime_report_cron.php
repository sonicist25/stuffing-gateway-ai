<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Leadtime_report_cron extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('M_leadtime_report');
    }

    public function recalculate()
    {
        $shipper_id = (int) $this->input->get('shipper_id');
        if ($shipper_id <= 0) {
            $shipper_id = 4407;
        }

        $date_from = $this->input->get('date_from');
        $date_to   = $this->input->get('date_to');

        if (empty($date_from)) {
            $date_from = '2026-05-01';
        }

        if (empty($date_to)) {
			$date_to = date('Y-m-d', strtotime('+1 day'));
		}

        try {
            $affected = $this->M_leadtime_report->recalculate_leadtime($shipper_id, $date_from, $date_to);

            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => true,
                    'message' => 'Leadtime recalculated',
                    'shipper_id' => $shipper_id,
                    'date_from' => $date_from,
                    'date_to' => $date_to,
                    'affected_rows' => $affected,
                    'timestamp' => date('Y-m-d H:i:s')
                ]));
        } catch (Exception $e) {
            $this->output
                ->set_status_header(500)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => false,
                    'message' => $e->getMessage(),
                    'timestamp' => date('Y-m-d H:i:s')
                ]));
        }
    }

    public function close_month()
    {
        $shipper_id = (int) $this->input->get('shipper_id');
        if ($shipper_id <= 0) {
            $shipper_id = 4407;
        }

        $period = $this->input->get('period');

        if (empty($period)) {
            $period = date('Y-m', strtotime('first day of last month'));
        }

        try {
            $result = $this->M_leadtime_report->generate_monthly_report($shipper_id, $period);
			$sheet_export = [
				'status'  => false,
				'skipped' => true,
				'message' => 'Google Sheet export not executed',
			];

			try {
				$this->load->library('Leadtime_google_sheet');

				if (!empty($result['run_id'])) {
					$sheet_export = $this->leadtime_google_sheet->export_monthly_report($result['run_id']);
				} else {
					$sheet_export = [
						'status'  => false,
						'skipped' => true,
						'message' => 'run_id kosong dari close_month',
					];
				}
			} catch (Exception $e) {
				log_message('error', 'LEADTIME_GOOGLE_SHEET_EXPORT_ERROR=' . $e->getMessage());

				$sheet_export = [
					'status'  => false,
					'skipped' => false,
					'message' => $e->getMessage(),
				];
			}

			$result['sheet_export'] = $sheet_export;

            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => true,
                    'message' => 'Monthly leadtime report generated',
                    'result' => $result,
                    'timestamp' => date('Y-m-d H:i:s')
                ]));
        } catch (Exception $e) {
            $this->output
                ->set_status_header(500)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => false,
                    'message' => $e->getMessage(),
                    'timestamp' => date('Y-m-d H:i:s')
                ]));
        }
    }
}