<?php
use chriskacerguis\RestServer\RestController;

defined('BASEPATH') OR exit('No direct script access allowed');

class Workbench extends RestController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('api/crm/M_workbench', 'wb');
        // auth...
    }

    public function summary_get()
    {
        $user_id = (int)($this->get('mock_user_id') ?: 0);
        $data = $this->wb->summary($user_id);
        return $this->response(['status'=>true, 'data'=>$data], 200);
    }
}
