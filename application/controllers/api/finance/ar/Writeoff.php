<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Writeoff extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('api/finance/ar/M_ar_writeoff', 'mwo');
    }

    public function create()
    {
        $raw = file_get_contents('php://input');
        $p = json_decode($raw, true);

        if (!is_array($p)) {
            $p = $this->input->post(NULL, true) ?: [];
        }

        $res = $this->mwo->create($p);

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