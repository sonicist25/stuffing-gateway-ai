<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Vendor_deposits extends MY_Controller
{
  public function __construct()
  {
    parent::__construct();
    $this->load->model('api/finance/ap/M_vendor_deposits', 'mvd');
  }

  public function index()
  {
    $p = [
      'vendor_code' => $this->input->get('vendor_code', true),
      'vendor_name' => $this->input->get('vendor_name', true),
      'status'      => $this->input->get('status', true),
      'currency'    => $this->input->get('currency', true),
      'search'      => $this->input->get('search', true),
      'date_from'   => $this->input->get('date_from', true),
      'date_to'     => $this->input->get('date_to', true),
    ];

    $rows = $this->mvd->list($p);
    return $this->_json(true, 'OK', $rows);
  }

  public function history()
  {
    $p = [
      'vendor_code' => $this->input->get('vendor_code', true),
      'vendor_name' => $this->input->get('vendor_name', true),
      'search'      => $this->input->get('search', true),
      'date_from'   => $this->input->get('date_from', true),
      'date_to'     => $this->input->get('date_to', true),
    ];

    $rows = $this->mvd->history($p);
    return $this->_json(true, 'OK', $rows);
  }

  public function summary()
  {
    $p = [
      'vendor_code' => $this->input->get('vendor_code', true),
      'search'      => $this->input->get('search', true),
    ];

    $rows = $this->mvd->summary($p);
    return $this->_json(true, 'OK', $rows);
  }

  public function show($deposit_id)
  {
    $row = $this->mvd->get((int)$deposit_id);

    if (!$row) {
      return $this->_json(false, 'Not found', null, null, 404);
    }

    return $this->_json(true, 'OK', $row);
  }

  protected function _json($ok, $message, $data = null, $errors = null, $http = 200)
  {
    $this->output
      ->set_status_header((int)$http)
      ->set_content_type('application/json')
      ->set_output(json_encode([
        'status'  => (bool)$ok,
        'message' => $message,
        'data'    => $data,
        'errors'  => $errors,
        'meta'    => [
          'timezone'    => 'Asia/Jakarta',
          'server_time' => date('Y-m-d H:i:s'),
        ],
      ]));
  }
}