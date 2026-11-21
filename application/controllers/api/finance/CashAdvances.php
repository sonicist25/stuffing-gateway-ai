<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class CashAdvances extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('api/finance/M_cash_advances', 'mca');
    }

    // GET /api/finance/cash-advances?status=OPEN
    public function index()
    {
        $status = strtoupper((string)$this->input->get('status', true));
		$date_from = trim((string)$this->input->get('date_from'));
		$date_to   = trim((string)$this->input->get('date_to'));
        if (!$status) $status = 'OPEN';
        $rows = $this->mca->list($status,$date_from,$date_to);
        return $this->_json(true, 'OK', $rows);
    }

    // GET /api/finance/cash-advances/{id}
    public function show($id)
    {
        $row = $this->mca->get((int)$id);
        if (!$row) return $this->_json(false, 'Not found', null, null, 404);
        return $this->_json(true, 'OK', $row);
    }

    // POST /api/finance/cash-advances/issue
    public function issue()
    {
        $p = json_decode($this->input->raw_input_stream, true) ?: [];
        $res = $this->mca->issue($p);
        return $this->_json($res['ok'], $res['message'], $res['data'] ?? null, $res['errors'] ?? null, $res['http'] ?? 200);
    }

    // POST /api/finance/cash-advances/settle
    public function settle()
    {
        $p = json_decode($this->input->raw_input_stream, true) ?: [];
        $res = $this->mca->settle($p);
        return $this->_json($res['ok'], $res['message'], $res['data'] ?? null, $res['errors'] ?? null, $res['http'] ?? 200);
    }

    protected function _json($ok, $message, $data=null, $errors=null, $http=200)
    {
        $this->output
            ->set_status_header((int)$http)
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => (bool)$ok,
                'message'=> $message,
                'data'   => $data,
                'errors' => $errors,
                'meta'   => [
                    'timezone' => 'Asia/Jakarta',
                    'server_time' => date('Y-m-d H:i:s')
                ]
            ]));
    }
}
