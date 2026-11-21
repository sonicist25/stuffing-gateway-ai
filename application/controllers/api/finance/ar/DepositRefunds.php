<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class DepositRefunds extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('api/finance/ar/M_customer_deposit', 'mdep');
    }

    public function index()
    {
        $p = $this->input->get(NULL, true) ?: [];
        $res = $this->mdep->list_refunds($p);

        return $this->_json(
            true,
            'OK',
            $res,
            null,
            200
        );
    }

    public function show($refund_no)
    {
        $row = $this->mdep->get_refund($refund_no);
        if (!$row) {
            return $this->_json(false, 'Refund not found', null, null, 404);
        }

        return $this->_json(true, 'OK', $row, null, 200);
    }

    public function create()
    {
        $p = json_decode($this->input->raw_input_stream, true) ?: [];
        $res = $this->mdep->refund($p);

        return $this->_json(
            $res['ok'],
            $res['message'],
            $res['data'] ?? null,
            $res['errors'] ?? null,
            $res['http'] ?? 200
        );
    }

    public function cancel($refund_no)
    {
        $res = $this->mdep->cancel_refund($refund_no);

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
	
	public function available_parties()
	{
		$p = $this->input->get(NULL, true) ?: [];
		$rows = $this->mdep->list_available_parties($p);

		return $this->_json(true, 'OK', $rows, null, 200);
	}
}