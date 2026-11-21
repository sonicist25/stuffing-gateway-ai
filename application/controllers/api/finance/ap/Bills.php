<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Bills extends MY_Controller
{
    protected $internal_api_key_methods = ['sync_from_existing'];

    public function __construct()
    {
        parent::__construct();
        $this->load->model('api/finance/ap/M_bills', 'mb');
    }

    public function index()
    {
        $region_id = $this->require_region();

        $p = [
            'status'      => $this->input->get('status', true),
            'search'      => $this->input->get('search', true),
            'vendor_type' => $this->input->get('vendor_type', true),
            'date_from'   => $this->input->get('date_from', true),
            'date_to'     => $this->input->get('date_to', true),
            'currency'    => $this->input->get('currency', true),
            'region_id'   => $region_id,
        ];

        $rows = $this->mb->list($p);
        return $this->_json(true, 'OK', $rows);
    }

    public function create()
    {
        $region_id = $this->require_region();
        $p = json_decode($this->input->raw_input_stream, true) ?: [];

        // Region request user selalu dipaksa dari JWT.
        $p['region_id'] = $region_id;

        $res = $this->mb->create($p);

        return $this->_json(
            $res['ok'],
            $res['message'],
            $res['data'] ?? null,
            $res['errors'] ?? null,
            $res['http'] ?? 200
        );
    }

    public function show($bill_id)
    {
        $region_id = $this->require_region();
        $row = $this->mb->get((int)$bill_id, $region_id);

        if (!$row) {
            return $this->_json(false, 'Not found', null, null, 404);
        }

        return $this->_json(true, 'OK', $row);
    }

    public function payments($bill_id)
    {
        $region_id = $this->require_region();

        // Validasi ownership bill terhadap region user lebih dulu.
        $bill = $this->mb->get((int)$bill_id, $region_id);

        if (!$bill) {
            return $this->_json(false, 'Not found', null, null, 404);
        }

        $rows = $this->mb->payments((int)$bill_id, $region_id);
        return $this->_json(true, 'OK', $rows);
    }

    public function create_from_existing()
    {
        $region_id = $this->require_region();
        $p = json_decode($this->input->raw_input_stream, true) ?: [];

        // Tidak boleh menerima region user dari frontend.
        $p['region_id'] = $region_id;

        $res = $this->mb->create_from_existing($p);

        return $this->_json(
            $res['ok'],
            $res['message'],
            $res['data'] ?? null,
            $res['errors'] ?? null,
            $res['http'] ?? 200
        );
    }

    public function sync_from_existing()
    {
        /*
         * Endpoint internal menggunakan X-API-KEY.
         * Region WAJIB dikirim oleh trusted source payload dan divalidasi model.
         */
        $p = json_decode($this->input->raw_input_stream, true) ?: [];

        $res = $this->mb->sync_from_existing($p);

        return $this->_json(
            $res['ok'],
            $res['message'],
            $res['data'] ?? null,
            $res['errors'] ?? null,
            $res['http'] ?? 200
        );
    }
}
