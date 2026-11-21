<?php
use chriskacerguis\RestServer\RestController;

defined('BASEPATH') OR exit('No direct script access allowed');

class Sync extends RestController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('api/crm/M_sync', 'm');
    }

    // POST /api/crm/sync/lead_converted
    public function lead_converted_post()
    {
        $payload = json_decode($this->input->raw_input_stream, true);
        $res = $this->m->lead_converted($payload);

        return $this->response($res, $res['status'] ? 200 : 400);
    }
}
