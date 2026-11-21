<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;

class Tasks extends RestController
{
    /** @var array */
    private $user = [];

    public function __construct()
    {
        parent::__construct();

        $this->load->model('api/crm/M_tasks', 'tasks');
        $this->load->library('Crm_auth');

        // Enforce auth for all endpoints in this controller
        try {
            $this->user = $this->crm_auth->require_user(); // {user_id, region_id, management, ...}
        } catch (Throwable $e) {
            // NOTE: Crm_auth already returns JSON 401 + exit for invalid/expired token.
            // We handle API key / header format / payload issues here.
            return $this->response([
                'status'  => false,
                'message' => $e->getMessage() ?: 'Unauthorized',
            ], 401);
        }
    }

    /**
     * GET /api/crm/tasks
     * Query: scope=my|queue|watched|all, status, source, priority, due=today|overdue|week, q, page, limit, queue
     */
    public function index_get()
    {
        $user_id = (int)($this->user['user_id'] ?? 0);
        if ($user_id <= 0) {
            return $this->response(['status'=>false, 'message'=>'Unauthorized'], 401);
        }

        $filters = [
            'scope'    => (string)($this->get('scope') ?: 'my'),   // my|queue|watched|all
            'queue'    => $this->get('queue'),                     // optional (jika kolom queue ada)
            'status'   => $this->get('status'),
            'source'   => $this->get('source'),
            'priority' => $this->get('priority'),
            'due'      => $this->get('due'),                       // today|overdue|week
            'q'        => $this->get('q'),
        ];

        // Normalisasi string null -> ''
        foreach (['queue','status','source','priority','due','q'] as $k) {
            if ($filters[$k] === null) $filters[$k] = '';
            $filters[$k] = is_string($filters[$k]) ? trim($filters[$k]) : $filters[$k];
        }

        $page  = max(1, (int)($this->get('page') ?: 1));
        $limit = min(100, max(1, (int)($this->get('limit') ?: 20)));

        $res = $this->tasks->list($user_id, $filters, $page, $limit);

        return $this->response([
            'status' => true,
            'data'   => $res['rows'] ?? [],
            'meta'   => $res['meta'] ?? ['page'=>$page,'limit'=>$limit,'total'=>0],
        ], 200);
    }

    /**
     * GET /api/crm/tasks/{id}
     */
    public function show_get($id)
    {
        $user_id = (int)($this->user['user_id'] ?? 0);
        $id = (int)$id;
        if ($id <= 0) {
            return $this->response(['status'=>false, 'message'=>'Invalid task id'], 400);
        }

        $task = $this->tasks->get_detail($user_id, $id);
        if (!$task) {
            return $this->response(['status' => false, 'message' => 'Task not found'], 404);
        }

        return $this->response(['status' => true, 'data' => $task], 200);
    }

    /**
     * POST /api/crm/tasks
     */
    public function index_post()
    {
        $user_id = (int)($this->user['user_id'] ?? 0);
        $region_id = $this->user['region_id'];

        // RestServer: $this->post() sudah array
        $payload = $this->post();
        if (!is_array($payload)) $payload = [];

        $res = $this->tasks->create($user_id, $region_id, $payload);
        return $this->response($res, !empty($res['status']) ? 201 : 400);
    }

    /**
     * PUT /api/crm/tasks/{id}
     */
    public function update_put($id)
    {
        $user_id = (int)($this->user['user_id'] ?? 0);
        $id = (int)$id;
        if ($id <= 0) {
            return $this->response(['status'=>false, 'message'=>'Invalid task id'], 400);
        }

        $payload = $this->put();
        if (!is_array($payload)) $payload = [];

        $res = $this->tasks->update($user_id, $id, $payload);
        return $this->response($res, !empty($res['status']) ? 200 : 400);
    }

    /**
     * POST /api/crm/tasks/{id}/assign
     */
    public function assign_post($id)
    {
        $user_id = (int)($this->user['user_id'] ?? 0);
        $id = (int)$id;
        if ($id <= 0) {
            return $this->response(['status'=>false, 'message'=>'Invalid task id'], 400);
        }

        $assignee_sales_id = $this->post('assignee_sales_id');

        $res = $this->tasks->assign($user_id, $id, $assignee_sales_id);
        return $this->response($res, !empty($res['status']) ? 200 : 400);
    }

    /**
     * POST /api/crm/tasks/{id}/status
     */
    public function status_post($id)
    {
        $user_id = (int)($this->user['user_id'] ?? 0);
        $id = (int)$id;
        if ($id <= 0) {
            return $this->response(['status'=>false, 'message'=>'Invalid task id'], 400);
        }

        $status = strtoupper(trim((string)$this->post('status')));

        $res = $this->tasks->set_status($user_id, $id, $status);
        return $this->response($res, !empty($res['status']) ? 200 : 400);
    }

    /**
     * POST /api/crm/tasks/{id}/comment
     */
    public function comment_post($id)
    {
        $user_id = (int)($this->user['user_id'] ?? 0);
        $region_id = $this->user['region_id'];
        $id = (int)$id;
        if ($id <= 0) {
            return $this->response(['status'=>false, 'message'=>'Invalid task id'], 400);
        }

        $note = trim((string)$this->post('note'));
        $res = $this->tasks->add_comment($user_id, $region_id, $id, $note);

        return $this->response($res, !empty($res['status']) ? 201 : 400);
    }
	// application/controllers/api/crm/Tasks.php

	// application/controllers/api/crm/Tasks.php

public function attachments_post($id)
{
    $user_id = (int)($this->user['user_id'] ?? 0);
    $is_mgmt = (int)($this->user['management'] ?? 0);

    $task_id = (int)$id;
    if ($user_id <= 0) {
        return $this->response(['status'=>false,'message'=>'Unauthorized'], 401);
    }
    if ($task_id <= 0) {
        return $this->response(['status'=>false,'message'=>'Invalid task id'], 400);
    }

    $note = trim((string)$this->post('note'));
    if ($note === '') $note = 'Attachments added';

    // PHP akan taruh files[] -> $_FILES['files']
    if (empty($_FILES) || empty($_FILES['files'])) {
        return $this->response([
            'status'=>false,
            'message'=>'No files uploaded. Use multipart field name: files[]',
        ], 400);
    }

    $res = $this->tasks->add_attachments($user_id, $is_mgmt, $task_id, $note, $_FILES['files']);
    return $this->response($res, !empty($res['status']) ? 201 : 400);
}




}
