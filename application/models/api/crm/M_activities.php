<?php
defined('BASEPATH') OR exit('No direct script access allowed');

#[\AllowDynamicProperties]
class M_activities extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->dbx = $this->load->database('master', TRUE);
    }

    public function create($data)
    {
        $this->dbx->insert('crm_activities', $data);
        return (int)$this->dbx->insert_id();
    }
	
	public function insert_attachments($activityId, array $attachments)
	{
		foreach ($attachments as $file) {
			$row = [
				'activity_id' => $activityId,
				'file_url'    => (string)($file['file_url'] ?? ''),
				'filename'    => (string)($file['filename'] ?? null),
				'mime_type'   => (string)($file['mime_type'] ?? null),
				'file_size'   => isset($file['file_size']) ? (int)$file['file_size'] : null,
			];

			if (!empty($row['file_url'])) {
				$this->dbx->insert('crm_activity_attachments', $row);
			}
		}
	}
}
