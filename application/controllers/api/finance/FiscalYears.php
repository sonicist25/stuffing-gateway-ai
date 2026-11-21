<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class FiscalYears extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('api/finance/M_fiscal_years', 'mfy');
    }

    public function index()
    {
        $rows = $this->mfy->list_years();
        return $this->_json(true, 'OK', $rows);
    }

    protected function _json($ok, $message, $data=null, $errors=null, $http=200)
    {
        $this->output
            ->set_status_header((int)$http)
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status'=>(bool)$ok,
                'message'=>$message,
                'data'=>$data,
                'errors'=>$errors
            ]));
    }
}
