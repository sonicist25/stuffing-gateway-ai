<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Deposits extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('api/finance/ar/M_customer_deposit', 'mdep');
    }

    public function available()
    {
        $party_code = trim((string)$this->input->get('party_code', true));
        $currency   = strtoupper(trim((string)$this->input->get('currency', true)));

        $res = $this->mdep->available([
            'party_code' => $party_code,
            'currency'   => $currency,
        ]);

        return $this->_json(
            $res['ok'],
            $res['message'],
            $res['data'] ?? null,
            $res['errors'] ?? null,
            $res['http'] ?? 200
        );
    }

    protected function _json($status, $message, $data = null, $errors = null, $http = 200)
    {
        $this->output
            ->set_status_header($http)
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status'  => (bool)$status,
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