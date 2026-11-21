<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Accounts extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('api/finance/M_accounts', 'macc');
    }

    public function get()
    {
        $q = $this->input->get('q', true);
        $rows = $this->macc->list($q);
        return $this->_json(true, 'OK', $rows);
    }

   public function tree()
	{
		$p = [
			'with_balance' => $this->input->get('with_balance', true) !== null
				? (int)$this->input->get('with_balance', true)
				: 1,
			'as_of' => $this->input->get('as_of', true),
		];

		$tree = $this->macc->tree($p);
		return $this->_json(true, 'OK', $tree);
	}

    public function post()
    {
        $payload = json_decode($this->input->raw_input_stream, true) ?: [];
        $res = $this->macc->create($payload);
        return $this->_json($res['ok'], $res['message'], $res['data'] ?? null, $res['errors'] ?? null, $res['http'] ?? 200);
    }

    public function put($id)
    {
        $payload = json_decode($this->input->raw_input_stream, true) ?: [];
        $res = $this->macc->update($id, $payload);
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
	public function create()
		{
		  $payload = json_decode($this->input->raw_input_stream, true) ?: [];
		  $res = $this->macc->create($payload);
		  return $this->_json($res['ok'], $res['message'], $res['data'] ?? null, $res['errors'] ?? null, $res['http'] ?? 200);
		}

}
