<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class YearEnd extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('api/finance/M_year_end', 'myec');
    }

    // GET /api/finance/year-end/preview?year=2026&retained_account_id=123&closing_date=2026-12-31
    public function preview()
    {
        $year = (int)$this->input->get('year', true);
        $retained = (int)$this->input->get('retained_account_id', true);
        $closing_date = $this->input->get('closing_date', true);

        $res = $this->myec->preview($year, $retained, $closing_date);
        return $this->_json($res['ok'], $res['message'], $res['data'] ?? null, $res['errors'] ?? null, $res['http'] ?? 200);
    }

    // POST /api/finance/year-end/close
    // { "year": 2026, "retained_account_id": 123, "closing_date":"2026-12-31" }
    public function close()
    {
        $p = json_decode($this->input->raw_input_stream, true) ?: [];
        $year = (int)($p['year'] ?? 0);
        $retained = (int)($p['retained_account_id'] ?? 0);
        $closing_date = (string)($p['closing_date'] ?? '');

        $res = $this->myec->close_year($year, $retained, $closing_date);
        return $this->_json($res['ok'], $res['message'], $res['data'] ?? null, $res['errors'] ?? null, $res['http'] ?? 200);
    }

    // POST /api/finance/year-end/cancel/{closing_id}
    public function cancel($closing_id)
    {
        $res = $this->myec->cancel_closing((int)$closing_id);
        return $this->_json($res['ok'], $res['message'], $res['data'] ?? null, $res['errors'] ?? null, $res['http'] ?? 200);
    }

    protected function _json($ok, $message, $data=null, $errors=null, $http=200)
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
                    'timezone' => 'Asia/Jakarta',
                    'server_time' => date('Y-m-d H:i:s'),
                ],
            ]));
    }
}
