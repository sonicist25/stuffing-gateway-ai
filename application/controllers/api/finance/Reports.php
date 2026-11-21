<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Reports extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('api/finance/M_reports', 'mr');
    }

    public function gl_get()
    {
        $filters = [
            'account_id' => $this->input->get('account_id', true),
            'party_id'   => $this->input->get('party_id', true),
            'date_from'  => $this->input->get('date_from', true),
            'date_to'    => $this->input->get('date_to', true),
        ];
        $res = $this->mr->general_ledger($filters);
        return $this->_json(true, 'OK', $res);
    }

    public function trial_balance_get()
    {
        $filters = [
            'date_from'  => $this->input->get('date_from', true),
            'date_to'    => $this->input->get('date_to', true),
        ];
        $res = $this->mr->trial_balance($filters);
        return $this->_json(true, 'OK', $res);
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
	
	public function profit_loss_get()
	{
		$filters = [
			'date_from' => $this->input->get('date_from', true),
			'date_to'   => $this->input->get('date_to', true),
		];
		$res = $this->mr->profit_loss($filters);
		return $this->_json(true, 'OK', $res);
	}

	public function balance_sheet_get()
	{
		$filters = [
			'as_of' => $this->input->get('as_of', true), // default hari ini
		];
		$res = $this->mr->balance_sheet($filters);
		return $this->_json(true, 'OK', $res);
	}

}
