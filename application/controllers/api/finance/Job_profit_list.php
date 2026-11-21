<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Job_profit_list extends MY_Controller
{
  public function __construct()
  {
    parent::__construct();
    $this->load->model('api/finance/M_job_profit', 'm');
    header('Content-Type: application/json; charset=utf-8');
  }

  // GET /api/finance/job_profit_list?date_from=2026-01-01&date_to=2026-12-31&limit=50&offset=0&sort=profit_desc
  public function index()
  {
    $dateFrom = trim((string)$this->input->get('date_from', true));
    $dateTo   = trim((string)$this->input->get('date_to', true));
    $limit    = (int)$this->input->get('limit', true);
    $offset   = (int)$this->input->get('offset', true);
    $sort     = trim((string)$this->input->get('sort', true)); // profit_desc | profit_asc | revenue_desc | cogs_desc

    if ($limit <= 0 || $limit > 200) $limit = 50;
    if ($offset < 0) $offset = 0;

    $res = $this->m->list_job_profit([
      'date_from'=>$dateFrom,
      'date_to'=>$dateTo,
      'limit'=>$limit,
      'offset'=>$offset,
      'sort'=>$sort,
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