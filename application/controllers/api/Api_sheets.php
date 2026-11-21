<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Api_sheets extends CI_Controller {
  public function __construct() {
    parent::__construct();
    $this->load->model('api/Sheets_model', 'm');
  }

  private function auth_or_401() {
    $key = $this->input->get_request_header('X-API-KEY');
    $cfg = getenv('API_KEY_SHEETS') ?: 'gateway-fms'; // fallback
    if (!$key || !hash_equals($cfg, $key)) {
      $this->output->set_status_header(401)->set_output('Unauthorized'); exit;
    }
  }

  // GET /api/sheets/tracking?region=Jakarta&limit=400
public function tracking() {
  $this->auth_or_401();

  $region = $this->input->get('region') ?: 'Jakarta';
  $limit  = (int)$this->input->get('limit');
  if ($limit < 1 || $limit > 1500) $limit = 1500;

  // NEW: warehouse filter (All | PRJP | AIRIN)
  $wh = strtoupper($this->input->get('warehouse') ?: 'ALL');
  if (!in_array($wh, ['ALL','PRJP','AIRIN'], true)) {
    $wh = 'ALL';
  }

  // READ ke REPLICA
  $rows = $this->m->fetch_tracking($region, $limit, $wh);

  $columns = ['NO_JOB','ETA','HBL','CONSIGNEE','POL','CONTAINER','STRIPPING','RELEASE_DO','RELEASE_CARGO','GUDANG'];
  return $this->output->set_content_type('application/json')
    ->set_output(json_encode(['columns'=>$columns,'rows'=>$rows]));
}


  // POST /api/sheets/tracking/batch-update
  public function batch_update() {
    $this->auth_or_401();

    $idem = $this->input->get_request_header('Idempotency-Key');
    if (!$idem) {
      return $this->output->set_status_header(400)->set_output('Missing Idempotency-Key');
    }

    $payload = json_decode($this->input->raw_input_stream, true);
    $updates = $payload['updates'] ?? [];
    if (!is_array($updates) || empty($updates)) {
      return $this->output->set_status_header(400)->set_output('No updates');
    }

	// sementara di controller batch_update():
    $result = $this->m->apply_updates($idem, $updates);

    return $this->output->set_content_type('application/json')
      ->set_output(json_encode($result));
  }
}
?>