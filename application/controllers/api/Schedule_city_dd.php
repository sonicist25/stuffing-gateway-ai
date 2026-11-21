<?php
use chriskacerguis\RestServer\RestController;

defined('BASEPATH') OR exit('No direct script access allowed');

class Schedule_city_dd extends RestController {

    public function __construct() {
        parent::__construct();
        $this->dbx = $this->load->database('master', TRUE);
    }

    // GET /schedule_city_dd/city_options?region_id=xxx
  public function city_options_get() {
    $region = trim($this->get('region_id'), "\"'");

    if (!$region) {
        return $this->response([
            'status' => false,
            'error' => 'region_id is required'
        ], RestController::HTTP_BAD_REQUEST);
    }

    // aktifkan simpan query
    $this->dbx->save_queries = TRUE;

    $this->dbx->distinct()
        ->select('city_id')
        ->where('region_id', $region)
        ->where('isDeleted', 0)
        ->where('etd_jkt >= CURDATE()', NULL, FALSE);
    $query = $this->dbx->get('tbl_schedules_direct');
    $direct = $query->result_array();

    $sql = $this->dbx->last_query();

    $via = $this->dbx->distinct()
        ->select('eta_city_id')
        ->where('region_id', $region)
        ->where('isDeleted', 0)
        ->where('etd_jkt >= CURDATE()', NULL, FALSE)
        ->get('tbl_schedules_via')
        ->result_array();

    $cities = array_unique(array_merge(
        array_column($direct, 'city_id'),
        array_column($via, 'eta_city_id')
    ));

    return $this->response([
        'status' => true,
        'data'   => $cities
    ], RestController::HTTP_OK);
}

    // GET /schedule_city_dd/etd_options?region_id=xxx&city_identifier=yyy
	 public function etd_options_get() {
    // Bersihkan parameter dari kutip ganda/spasi
    $region = trim($this->get('region_id'), "\"'");
    $city   = trim($this->get('city_identifier'), "\"'");

    if (!$region || !$city) {
        return $this->response([
            'status' => false,
            'error'  => 'region_id and city_identifier are required'
        ], RestController::HTTP_BAD_REQUEST);
    }

    // aktifkan simpan query agar last_query tidak false
    $this->dbx->save_queries = TRUE;

    // Query direct
    $this->dbx->select('etd_jkt')->distinct();
    $this->dbx->where('region_id', $region);
    $this->dbx->where('city_id', $city);
    $this->dbx->where('isDeleted', 0);
    $this->dbx->where('etd_jkt >= CURDATE()', NULL, FALSE); // gunakan fungsi SQL langsung
    $directQuery = $this->dbx->get('tbl_schedules_direct');
    $direct      = $directQuery->result_array();
    $sqlDirect   = $this->dbx->last_query();

    // Query via
    $this->dbx->select('etd_jkt')->distinct();
    $this->dbx->where('region_id', $region);
    $this->dbx->where('eta_city_id', $city);
    $this->dbx->where('isDeleted', 0);
    $this->dbx->where('etd_jkt >= CURDATE()', NULL, FALSE);
    $viaQuery = $this->dbx->get('tbl_schedules_via');
    $via      = $viaQuery->result_array();
    $sqlVia   = $this->dbx->last_query();

    // Gabungkan hasil
    $etds = array_unique(array_merge(
        array_column($direct, 'etd_jkt'),
        array_column($via, 'etd_jkt')
    ));

    return $this->response([
        'status'    => true,
        'data'      => $etds
    ], RestController::HTTP_OK);
}
}