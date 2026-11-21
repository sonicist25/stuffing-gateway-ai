<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Install as:
 * application/controllers/api/finance/Revenue_orders.php
 *
 * Frontend read endpoints (Bearer JWT):
 * GET /api/finance/revenue_orders
 * GET /api/finance/revenue_orders/detail?revenue_order_id=123
 *
 * Internal endpoints (X-API-KEY):
 * POST /api/finance/revenue_orders/sync
 * POST /api/finance/revenue_orders/unbill_item
 * POST /api/finance/revenue_orders/mark_billed
 */
class Revenue_orders extends MY_Controller
{
    protected $internal_api_key_methods = ['sync', 'unbill_item', 'mark_billed'];

    public function __construct()
    {
        parent::__construct();
        $this->load->model('api/finance/M_revenue_orders', 'revenue_orders');
    }

    private function input_json()
    {
        $raw = $this->input->raw_input_stream;
        $data = json_decode($raw, true);
        if (is_array($data)) return $data;
        return $this->input->post(NULL, true) ?: [];
    }

    private function send_result(array $res)
    {
        $http = (int)($res['http'] ?? (!empty($res['ok']) ? 200 : 400));
        return $this->output
            ->set_content_type('application/json')
            ->set_status_header($http)
            ->set_output(json_encode([
                'status'  => !empty($res['ok']),
                'message' => $res['message'] ?? (!empty($res['ok']) ? 'OK' : 'ERROR'),
                'data'    => $res['data'] ?? null,
            ]));
    }

    public function index()
    {
        if (strtoupper((string)$this->input->method()) !== 'GET') {
            return $this->send_result(['ok'=>false,'http'=>405,'message'=>'METHOD_NOT_ALLOWED']);
        }

        return $this->send_result($this->revenue_orders->frontend_list([
            'q'           => $this->input->get('q', true),
            'status'      => $this->input->get('status', true),
            'source_type' => $this->input->get('source_type', true),
            'date_from'   => $this->input->get('date_from', true),
            'date_to'     => $this->input->get('date_to', true),
            'limit'       => $this->input->get('limit', true),
            'offset'      => $this->input->get('offset', true),
        ]));
    }

    public function detail()
    {
        if (strtoupper((string)$this->input->method()) !== 'GET') {
            return $this->send_result(['ok'=>false,'http'=>405,'message'=>'METHOD_NOT_ALLOWED']);
        }

        return $this->send_result($this->revenue_orders->frontend_detail([
            'revenue_order_id' => $this->input->get('revenue_order_id', true),
        ]));
    }

    public function sync()
    {
        return $this->send_result($this->revenue_orders->sync($this->input_json()));
    }

    public function unbill_item()
    {
        return $this->send_result($this->revenue_orders->unbill_item($this->input_json()));
    }

    public function mark_billed()
    {
        return $this->send_result($this->revenue_orders->mark_billed($this->input_json()));
    }
}
