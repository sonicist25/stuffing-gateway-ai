<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;

class Quotation_items extends RestController {

    public function __construct() {
        parent::__construct();
        $this->load->library('crm_auth');
        $this->load->model('api/crm/M_quotations', 'm_quotes');
    }

    private function json_body() {
        $raw = file_get_contents('php://input');
        $j = json_decode($raw, true);
        return is_array($j) ? $j : [];
    }

    public function index_get() {
        try {
            $u = $this->crm_auth->require_user();

            $quote_id = (int)($this->get('quote_id') ?? 0);
            if ($quote_id <= 0) return $this->response(['status' => false, 'message' => 'quote_id is required'], 400);

            $group = (int)($this->get('group') ?? 0) === 1;
            $out = $this->m_quotes->list_items($quote_id, $group);

            return $this->response($out, 200);

        } catch (Exception $e) {
            return $this->response(['status' => false, 'message' => $e->getMessage()], 401);
        }
    }

    public function index_post() {
        try {
            $u = $this->crm_auth->require_user();
            $body = $this->json_body();

            $res = $this->m_quotes->create_item($body);
            if (!$res['status']) return $this->response($res, 400);

            return $this->response($res, 201);

        } catch (Exception $e) {
            return $this->response(['status' => false, 'message' => $e->getMessage()], 401);
        }
    }

    public function index_put() {
        try {
            $u = $this->crm_auth->require_user();
            $quote_item_id = (int)($this->get('quote_item_id') ?? 0);
            if ($quote_item_id <= 0) return $this->response(['status' => false, 'message' => 'quote_item_id is required'], 400);

            $body = $this->json_body();
            $res = $this->m_quotes->update_item($quote_item_id, $body);
            if (!$res['status']) return $this->response($res, 400);

            return $this->response($res, 200);

        } catch (Exception $e) {
            return $this->response(['status' => false, 'message' => $e->getMessage()], 401);
        }
    }

    public function index_delete() {
        try {
            $u = $this->crm_auth->require_user();
            $quote_item_id = (int)($this->get('quote_item_id') ?? 0);
            if ($quote_item_id <= 0) return $this->response(['status' => false, 'message' => 'quote_item_id is required'], 400);

            $res = $this->m_quotes->delete_item($quote_item_id);
            if (!$res['status']) return $this->response($res, 400);

            return $this->response($res, 200);

        } catch (Exception $e) {
            return $this->response(['status' => false, 'message' => $e->getMessage()], 401);
        }
    }
}
