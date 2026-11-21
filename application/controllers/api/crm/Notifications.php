<?php
use chriskacerguis\RestServer\RestController;

defined('BASEPATH') OR exit('No direct script access allowed');

class Notifications extends RestController
{
    public function __construct()
    {
        parent::__construct();
		$this->load->library('crm_auth');
        $this->load->model('api/crm/M_notifications', 'notif');
        // TODO: auth Anda
    }

    public function index_get()
    {
        $user_id = $this->_current_sales_user_id();
        $only_unread = (int)($this->get('unread') ?: 0);
        $page  = max(1, (int)($this->get('page') ?: 1));
        $limit = min(100, max(1, (int)($this->get('limit') ?: 20)));

        $res = $this->notif->list($user_id, $only_unread, $page, $limit);
        return $this->response(['status'=>true, 'data'=>$res['rows'], 'meta'=>$res['meta']], 200);
    }

    public function unread_count_get()
    {
        $user_id = $this->_current_sales_user_id();
        $count = $this->notif->unread_count($user_id);
        return $this->response(['status'=>true, 'data'=>['unread'=>$count]], 200);
    }

    public function read_post($id)
    {
        $user_id = $this->_current_sales_user_id();
        $res = $this->notif->mark_read($user_id, (int)$id);
        return $this->response($res, $res['status'] ? 200 : 400);
    }

    public function read_all_post()
    {
        $user_id = $this->_current_sales_user_id();
        $res = $this->notif->mark_all_read($user_id);
        return $this->response($res, 200);
    }

    private function _current_sales_user_id()
    {
        $u = $this->crm_auth->require_user();
        return (string)$u['user_id'];
    }
}
