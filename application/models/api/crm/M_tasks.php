<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_tasks extends CI_Model
{
    private $t_tasks = 'crm_tasks';
    private $t_watch = 'crm_task_watchers';
    private $t_notif = 'crm_notifications';
    private $t_act   = 'crm_activities';
    private $t_att   = 'crm_activity_attachments';

    public function list($sales_user_id, $filters, $page, $limit)
    {
        $offset = ($page - 1) * $limit;

        $this->db->from($this->t_tasks . ' t');

		// JOIN kandidat requester
		$this->db->join('crm_leads l', "l.id_lead = t.requester_id", 'left');          // requester_type=lead
		$this->db->join('mstshipper s', "s.id_shipper = t.requester_id", 'left');      // requester_type=customer (atau shipper)
		$this->db->join('mstadmin a', "a.user_id = t.requester_id", 'left');           // requester_type=internal

		$this->db->select("
		  t.*,
		  CASE
			WHEN LOWER(t.requester_type) = 'lead'     THEN l.company_name
			WHEN LOWER(t.requester_type) = 'customer' THEN s.shipper_name
			WHEN LOWER(t.requester_type) = 'internal' THEN a.fullname
			ELSE NULL
		  END AS requester_name
		", false);
        $this->db->where('t.deleted_at IS NULL', null, false);

        $scope = $filters['scope'] ?? 'my';

        if ($scope === 'my') {
            $this->db->where('assignee_sales_id', $sales_user_id);
        } elseif ($scope === 'queue') {
            // queue CSOPS, biasanya unassigned atau assigned ke CSOPS user (kalau sudah ada)
            if (!empty($filters['queue'])) {
                $this->db->where('queue', $filters['queue']);
            }
        } elseif ($scope === 'watched') {
            $this->db->join($this->t_watch.' w', 'w.task_id = '.$this->t_tasks.'.task_id', 'inner');
            $this->db->where('w.sales_user_id', $sales_user_id);
        } else {
            // all -> no additional filter (opsional: batasi by role/branch)
        }

        if (!empty($filters['status']))   $this->db->where('t.status', $filters['status']);
        if (!empty($filters['source']))   $this->db->where('source', $filters['source']);
        if (!empty($filters['priority'])) $this->db->where('priority', $filters['priority']);

        if (!empty($filters['q'])) {
            $q = $this->db->escape_like_str($filters['q']);
            $this->db->group_start()
                ->like('title', $q)
                ->or_like('description', $q)
            ->group_end();
        }

        // Due shortcut
        $due = $filters['due'] ?? '';
        if ($due === 'today') {
            $this->db->where('DATE(due_at) = CURDATE()', null, false);
        } elseif ($due === 'overdue') {
            $this->db->where('due_at IS NOT NULL', null, false);
            $this->db->where('due_at < NOW()', null, false);
            $this->db->where_in('t.status', ['OPEN','IN_PROGRESS','WAITING']);
        } elseif ($due === 'week') {
            $this->db->where('due_at >= DATE_SUB(CURDATE(), INTERVAL 1 DAY)', null, false);
            $this->db->where('due_at <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)', null, false);
        }

        // Count
        $count_db = clone $this->db;
        $total = (int)$count_db->count_all_results('', false);

        // Order
        $this->db->order_by("FIELD(t.status,'OPEN','IN_PROGRESS','WAITING','DONE','CANCELED')", '', false);
        $this->db->order_by('due_at IS NULL, due_at ASC', '', false);

        $this->db->limit($limit, $offset);
        $rows = $this->db->get()->result_array();

        return [
            'rows' => $rows,
            'meta' => ['page'=>$page, 'limit'=>$limit, 'total'=>$total],
        ];
    }

 public function get_detail($sales_user_id, $task_id)
	{
		// Task + assignee fullname
		$task = $this->db
			->select('t.*, ma.fullname AS assignee_fullname')
			->from($this->t_tasks . ' t')
			->join('mstadmin ma', 'ma.user_id = t.assignee_sales_id', 'left')
			->where('t.task_id', (int)$task_id)
			->get()
			->row_array();

		if (!$task || $task['deleted_at'] !== null) return null;

		// Timeline activities
		 $activities = $this->db
				->select('a.*, mb.fullname AS created_by_fullname')
				->from($this->t_act . ' a')
				->join('mstadmin mb', 'mb.user_id = a.created_by_sales_id', 'left')
				->where('a.entity_type', 'task')
				->where('a.entity_id', (int)$task_id)
				->order_by('a.created_at','ASC')
				->get()
				->result_array();

		// Attachments by activity_id
		$activity_ids = array_column($activities, 'activity_id');
		$attachments_by_activity = [];
		if (!empty($activity_ids)) {
			$atts = $this->db
				->where_in('activity_id', $activity_ids)
				->where('deleted_at IS NULL', null, false) // pastikan kolom deleted_at ada di table attachments
				->order_by('created_at', 'ASC')
				->get($this->t_att)
				->result_array();

			foreach ($atts as $a) {
				$attachments_by_activity[$a['activity_id']][] = $a;
			}
		}

		foreach ($activities as &$a) {
			$a['attachments'] = $attachments_by_activity[$a['activity_id']] ?? [];
		}
		unset($a);

		// Watchers
		$watchers = $this->db
			->select('sales_user_id, created_at')
			->get_where($this->t_watch, ['task_id' => (int)$task_id])
			->result_array();

		$task['activities'] = $activities;
		$task['watchers']   = $watchers;

		return $task;
	}


    public function create($sales_user_id, $region_id, $payload)
    {
        $title = trim((string)($payload['title'] ?? ''));
        if ($title === '') return ['status'=>false, 'message'=>'Title is required'];

        $data = [
            'title'        => $title,
            'description'  => $payload['description'] ?? null,
            'type'         => $payload['type'] ?? 'GENERAL',     // existing column
            'priority'     => $payload['priority'] ?? 'MEDIUM',
            'status'       => $payload['status'] ?? 'OPEN',
            'due_at'       => $payload['due_at'] ?? null,

            // new columns (jika sudah Anda ALTER)
            'source'       => $payload['source'] ?? 'MANUAL',
            'channel'      => $payload['channel'] ?? null,
            'requester_type' => $payload['requester_type'] ?? null,
            'requester_id'   => $payload['requester_id'] ?? null,
            'severity'       => $payload['severity'] ?? null,
            'remind_at'      => $payload['remind_at'] ?? null,
            'queue'          => $payload['queue'] ?? null,
            'payload_json'   => isset($payload['payload_json']) ? json_encode($payload['payload_json']) : null,

            'related_entity' => $payload['related_entity'] ?? null,
            'related_id'     => $payload['related_id'] ?? null,

            // assignee: untuk complaint CSOPS biasanya NULL (queue)
            'assignee_sales_id' => $payload['assignee_sales_id'] ?? null,

            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $this->db->trans_start();
        $this->db->insert($this->t_tasks, $data);
        $task_id = (int)$this->db->insert_id();

        // Log activity CREATED
        $this->_activity($task_id, $region_id,$data['source'], 'SYSTEM', $data['title'], $data['description'], $sales_user_id);
		$shipper_id = (int)($data['requester_id'] ?? 0);
		$severity   = (string)($data['severity'] ?? 'HIGH');
		
        // Special handling: complaint -> add watcher sales owner + notif
        if (($data['source'] ?? '') === 'COMPLAINT'
            && ($data['requester_type'] ?? '') === 'customer'
            && !empty($data['requester_id'])) {

            $sales_owner_id = $this->_get_shipper_sales_owner((int)$data['requester_id']);
			$shipper_name   = $this->db->get_where('mstshipper',array('id_shipper'=>$shipper_id))->row('shipper_name');
			$dedup_key = 'SEED:COMPLAINT:' . $task_id;
            if ($sales_owner_id) {
                $this->_add_watcher($task_id, $sales_owner_id);

                // in-app notification untuk Sales owner
                $this->load->model('api/crm/M_notifications', 'notif');
				$this->notif->push(
				  (int)$sales_owner_id,
				  'New Customer Complaint',
				  'Complaint received from shipper ID: '.$shipper_name,
				  '/app/tasks/'.$task_id,
				  'COMPLAINT',
				  $task_id,
				  $severity ?: 'HIGH'
				);

                //$this->_activity($task_id, $region_id, 'WATCHER_ADDED', 'SYSTEM', $data['title'], 'Sales watcher added: '.$sales_owner_id, $sales_user_id);
            }
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === false) {
            return ['status'=>false, 'message'=>'Failed to create task'];
        }

        return ['status'=>true, 'data'=>['task_id'=>$task_id]];
    }

    public function update($sales_user_id, $task_id, $payload)
    {
        $task = $this->db->get_where($this->t_tasks, ['task_id'=>$task_id])->row_array();
        if (!$task || $task['deleted_at'] !== null) return ['status'=>false, 'message'=>'Task not found'];

        $allowed = [
            'title','description','type','priority','status','due_at',
            'source','channel','requester_type','requester_id','severity','remind_at','queue',
            'related_entity','related_id'
        ];

        $data = [];
        foreach ($allowed as $k) {
            if (array_key_exists($k, $payload)) {
                $data[$k] = $payload[$k];
            }
        }
        if (isset($payload['payload_json'])) {
            $data['payload_json'] = json_encode($payload['payload_json']);
        }

        if (empty($data)) return ['status'=>false, 'message'=>'No changes'];

        $data['updated_at'] = date('Y-m-d H:i:s');

        $this->db->update($this->t_tasks, $data, ['task_id'=>$task_id]);
		
        $this->_activity($task_id, $region_id, 'UPDATED', 'SYSTEM', $data['title'], 'Task updated', $sales_user_id);

        return ['status'=>true, 'message'=>'Updated'];
    }

    public function assign($sales_user_id, $task_id, $assignee_sales_id)
    {
        $task = $this->db->get_where($this->t_tasks, ['task_id'=>$task_id])->row_array();
        if (!$task || $task['deleted_at'] !== null) return ['status'=>false, 'message'=>'Task not found'];

        $assignee_sales_id = ($assignee_sales_id === null || $assignee_sales_id === '') ? null : (int)$assignee_sales_id;

        $this->db->update($this->t_tasks, [
            'assignee_sales_id' => $assignee_sales_id,
            'updated_at' => date('Y-m-d H:i:s')
        ], ['task_id'=>$task_id]);
		
        $this->_activity($task_id, $region_id, 'ASSIGNED', 'SYSTEM', $data['title'], 'Assigned to: '.($assignee_sales_id ?: 'NULL'), $sales_user_id);

        $this->load->model('api/crm/M_notifications', 'notif');
		$this->notif->push(
		  (int)$assignee_sales_id,
		  'Task Assigned',
		  'You have been assigned: '.$task['title'],
		  '/app/tasks/'.$task_id,
		  'TASK',
		  $task_id,
		  'INFO'
		);

        return ['status'=>true, 'message'=>'Assigned'];
    }

    public function set_status($sales_user_id, $task_id, $status)
    {
        $task = $this->db->get_where($this->t_tasks, ['task_id'=>$task_id])->row_array();
        if (!$task || $task['deleted_at'] !== null) return ['status'=>false, 'message'=>'Task not found'];

        $valid = ['OPEN','IN_PROGRESS','WAITING','DONE','CANCELED'];
        if (!in_array($status, $valid, true)) return ['status'=>false, 'message'=>'Invalid status'];

        $data = ['status'=>$status, 'updated_at'=>date('Y-m-d H:i:s')];
        if ($status === 'DONE') {
            $data['completed_at'] = date('Y-m-d H:i:s');
            $data['completed_by_sales_id'] = $sales_user_id;
        } else {
            $data['completed_at'] = null;
            $data['completed_by_sales_id'] = null;
        }

        $this->db->update($this->t_tasks, $data, ['task_id'=>$task_id]);
		
        $this->_activity($task_id,'STATUS', 'SYSTEM' , 'Status changed: '.$task['status'].' -> '.$status, $sales_user_id);

        return ['status'=>true, 'message'=>'Status updated'];
    }

    public function add_comment($sales_user_id, $region_id, $task_id, $note)
    {
        $note = trim((string)$note);
        if ($note === '') return ['status'=>false, 'message'=>'Note is required'];

        $task = $this->db->get_where($this->t_tasks, ['task_id'=>$task_id])->row_array();
        if (!$task || $task['deleted_at'] !== null) return ['status'=>false, 'message'=>'Task not found'];
		
        $this->_activity($task_id, $region_id, 'COMMENT', 'USER',  '', $note, $sales_user_id);

        // Notif ke assignee jika commenter bukan assignee
       if (!empty($task['assignee_sales_id']) && (int)$task['assignee_sales_id'] !== (int)$sales_user_id) {
			$this->load->model('api/crm/M_notifications', 'notif');
			$this->notif->push(
			  (int)$task['assignee_sales_id'],
			  'Task Comment',
			  'New comment on: '.$task['title'],
			  '/app/tasks/'.$task_id,
			  'TASK',
			  $task_id,
			  'INFO'
			);
		}

        return ['status'=>true, 'message'=>'Comment added'];
    }

    private function _get_shipper_sales_owner($id_shipper)
    {
        // shipper_sales: id_shipper, sales_id, ...
        $row = $this->db->select('sales_id')
            ->from('shipper_sales')
            ->where('id_shipper', $id_shipper)
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get()->row_array();

        return $row ? (int)$row['sales_id'] : 0;
    }

    private function _add_watcher($task_id, $sales_user_id)
    {
        // insert ignore style
        $exists = $this->db->get_where($this->t_watch, [
            'task_id'=>$task_id,
            'sales_user_id'=>$sales_user_id
        ])->row_array();

        if (!$exists) {
            $this->db->insert($this->t_watch, [
                'task_id'=>$task_id,
                'sales_user_id'=>$sales_user_id,
                'created_at'=>date('Y-m-d H:i:s'),
            ]);
        }
    }

    private function _notify($user_id, $title, $body, $link)
    {
        $this->db->insert($this->t_notif, [
            'user_id' => (int)$user_id,
            'title'   => (string)$title,
            'body'    => (string)$body,
            'link'    => (string)$link,
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'read_at' => null,
        ]);
    }

    private function _activity($task_id, $region_id, $action_type, $channel, $subject,$note, $created_by_sales_id)
    {
        // Asumsi crm_activities punya kolom:
        // activity_id, entity_type, entity_id, action_type, channel, note, created_by_sales_id, created_at, ...
        $this->db->insert($this->t_act, [
            'entity_type' => 'task',
            'entity_id'   => $task_id,
            'region_id'   => $region_id,
            'owner_sales_id'   => $created_by_sales_id,
            'subject'   => $subject,
            'action_type' => $action_type,
            'channel'     => $channel,
            'note'        => $note,
            'created_by_sales_id' => $created_by_sales_id,
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);
		return (int)$this->db->insert_id();
    }
	
	// application/models/api/crm/M_tasks.php

public function add_attachments($sales_user_id, $is_mgmt, $task_id, $note, $files)
{
    // 1) cek task ada + tidak deleted (hindari trim(null) warning)
    $task = $this->db->from($this->t_tasks)
        ->where('task_id', (int)$task_id)
        ->where('deleted_at IS NULL', null, false)
        ->get()->row_array();

    if (!$task) return ['status'=>false,'message'=>'Task not found'];

    // 2) permission minimal:
    // - management: boleh
    // - non mgmt: hanya assignee
    if ((int)$is_mgmt !== 1) {
        if ((int)$task['assignee_sales_id'] !== (int)$sales_user_id) {
            return ['status'=>false,'message'=>$task]; // sengaja 404 style (biar tidak leak)
        }
    }

    // 3) buat activity “ATTACHMENT”
    $this->db->trans_start();

    $this->db->insert($this->t_act, [
        'entity_type' => 'task',
        'entity_id'   => (int)$task_id,
        'action_type' => 'ATTACHMENT',
        'channel'     => 'USER',
        'note'        => (string)$note,
        'created_by_sales_id' => (int)$sales_user_id,
        'created_at'  => date('Y-m-d H:i:s'),
        'updated_at'  => date('Y-m-d H:i:s'),
    ]);
    $activity_id = (int)$this->db->insert_id();

    // 4) upload file(s)
    $upload_dir = FCPATH.'public/uploads/activities/' . (int)$task_id . '/';
    if (!is_dir($upload_dir)) @mkdir($upload_dir, 0775, true);

    $this->load->library('upload');

    $saved = [];
    $errors = [];

    foreach ($this->_normalize_files_array($files) as $f) {
        if ((int)$f['error'] !== UPLOAD_ERR_OK) {
            $errors[] = $f['name'].' (upload error '.$f['error'].')';
            continue;
        }

        $_FILES['__one'] = $f;

        $config = [
            'upload_path'   => $upload_dir,
            'allowed_types' => '*',
            'max_size'      => 20480, // 20MB (sesuaikan)
            'encrypt_name'  => true,
        ];
        $this->upload->initialize($config, true);

        if (!$this->upload->do_upload('__one')) {
            $errors[] = $f['name'].' ('.$this->upload->display_errors('','').')';
            continue;
        }

        $u = $this->upload->data();
        $rel = 'public/uploads/activities/'.(int)$task_id.'/'.$u['file_name'];
        $url = base_url($rel);

        // insert attachment record (kolom Anda: file_url)
        $this->db->insert($this->t_att, [
            'activity_id' => $activity_id,
            'file_url'    => $url,
            'filename'    => $u['client_name'] ?: $u['file_name'],
            'mime_type'   => $u['file_type'] ?? null,
            'file_size'   => (int)($u['file_size'] * 1024), // CI biasanya KB
            'created_at'  => date('Y-m-d H:i:s'),
            'uploaded_at' => date('Y-m-d H:i:s'),
            'deleted_at'  => null,
        ]);

        $saved[] = [
            'filename' => $u['client_name'] ?: $u['file_name'],
            'file_url' => $url,
            'mime_type'=> $u['file_type'] ?? null,
            'file_size'=> (int)($u['file_size'] * 1024),
        ];
    }

    // update task updated_at (kalau kolom ada)
    $this->db->set('updated_at', date('Y-m-d H:i:s'))->where('task_id', (int)$task_id)->update($this->t_tasks);

    $this->db->trans_complete();
    if ($this->db->trans_status() === false) {
        return ['status'=>false,'message'=>'Failed to save attachments'];
    }

    return [
        'status'=>true,
        'data'=>[
            'activity_id'=>$activity_id,
            'saved'=>$saved,
            'errors'=>$errors,
        ]
    ];
}

// helper normalize files[] / single file
private function _normalize_files_array($files)
{
    $out = [];
    // multiple
    if (isset($files['name']) && is_array($files['name'])) {
        foreach ($files['name'] as $i => $name) {
            $out[] = [
                'name'     => $name,
                'type'     => $files['type'][$i] ?? '',
                'tmp_name' => $files['tmp_name'][$i] ?? '',
                'error'    => $files['error'][$i] ?? 4,
                'size'     => $files['size'][$i] ?? 0,
            ];
        }
        return $out;
    }
    // single
    if (isset($files['name'])) {
        $out[] = $files;
    }
    return $out;
}




}
