<?php
use chriskacerguis\RestServer\RestController;

defined('BASEPATH') OR exit('No direct script access allowed');

class Task_files extends RestController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('api/crm/M_task_files', 'tf');
        // TODO auth
        // $this->auth->restrict();
    }

    // POST /api/crm/tasks/{task_id}/comment-with-attachments
    public function comment_with_attachments_post($task_id)
    {
        $sales_user_id = (int)($this->get('mock_user_id') ?: 0);
        $note = trim((string)$this->post('note'));

        if ($note === '') {
            return $this->response(['status'=>false, 'message'=>'note is required'], 400);
        }

        if (empty($_FILES['files'])) {
            return $this->response(['status'=>false, 'message'=>'files[] is required'], 400);
        }

        $res = $this->tf->create_activity_and_upload((int)$task_id, $sales_user_id, 'COMMENT', $note, $_FILES['files']);
        return $this->response($res, $res['status'] ? 201 : 400);
    }

    // POST /api/crm/tasks/{task_id}/attachments
    public function attachments_post($task_id)
    {
        $sales_user_id = (int)($this->get('mock_user_id') ?: 0);
        $note = trim((string)$this->post('note')); // optional

        if (empty($_FILES['files'])) {
            return $this->response(['status'=>false, 'message'=>'files[] is required'], 400);
        }

        $action = $note !== '' ? 'COMMENT' : 'ATTACH';
        $note2  = $note !== '' ? $note : 'Attachment uploaded';

        $res = $this->tf->create_activity_and_upload((int)$task_id, $sales_user_id, $action, $note2, $_FILES['files']);
        return $this->response($res, $res['status'] ? 201 : 400);
    }

    // GET /api/crm/attachments/{attachment_id}/download
    public function download_get($attachment_id)
    {
        $sales_user_id = (int)($this->get('mock_user_id') ?: 0);

        $file = $this->tf->get_attachment((int)$attachment_id, $sales_user_id);
        if (!$file) {
            return $this->response(['status'=>false, 'message'=>'Attachment not found'], 404);
        }

        $abs = FCPATH . ltrim($file['file_path'], '/');
        if (!is_file($abs)) {
            return $this->response(['status'=>false, 'message'=>'File missing on server'], 404);
        }

        // Output file
        $this->load->helper('download');
        force_download($file['file_name'], file_get_contents($abs));
    }
}
