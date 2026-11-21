<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Job_status extends MY_Controller
{
  public function __construct()
  {
    parent::__construct();
    $this->load->model('api/finance/M_job_profit', 'm'); // reuse model yang sudah ada
    header('Content-Type: application/json; charset=utf-8');
  }

  // GET /api/finance/job_status?job_no=HNS-...
  public function index()
  {
    $job_no = trim((string)$this->input->get('job_no', true));

    $res = $this->m->get_job_status([
      'job_no' => $job_no,
    ]);

    $http = $res['http'] ?? 200;
    $payload = [
      'status'  => (bool)($res['ok'] ?? false),
      'message' => $res['message'] ?? '',
      'data'    => $res['data'] ?? null,
      'errors'  => $res['errors'] ?? null,
    ];

    $this->output->set_status_header($http)->set_output(json_encode($payload));
  }
}