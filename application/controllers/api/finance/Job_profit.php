<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Job_profit extends MY_Controller
{
  public function __construct()
  {
    parent::__construct();
    $this->load->model('api/finance/M_job_profit', 'm');
    header('Content-Type: application/json; charset=utf-8');
  }

  // GET /api/finance/job_profit?job_no=HNS-...&date_from=2026-01-01&date_to=2026-12-31
  public function index()
  {
    $job_no   = trim((string)$this->input->get('job_no', true));
    $dateFrom = trim((string)$this->input->get('date_from', true));
    $dateTo   = trim((string)$this->input->get('date_to', true));

    $res = $this->m->get_job_profit([
      'job_no' => $job_no,
      'date_from' => $dateFrom,
      'date_to' => $dateTo,
    ]);

    $http = $res['http'] ?? 200;
    $payload = [
      'status'  => (bool)($res['ok'] ?? false),
      'message' => $res['message'] ?? '',
      'data'    => $res['data'] ?? null,
      'errors'  => $res['errors'] ?? null,
    ];

    $this->output
      ->set_status_header($http)
      ->set_output(json_encode($payload));
  }
}