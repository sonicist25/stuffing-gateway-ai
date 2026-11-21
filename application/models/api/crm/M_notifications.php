<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_notifications extends CI_Model
{
    private $t = 'crm_notifications';

    public function list($user_id, $only_unread, $page, $limit)
    {
        $offset = ($page - 1) * $limit;

        $this->db->from($this->t);
        $this->db->where('user_id', $user_id);
        if ($only_unread) $this->db->where('is_read', 0);

        $count_db = clone $this->db;
        $total = (int)$count_db->count_all_results('', false);

        $this->db->order_by('created_at','DESC');
        $this->db->limit($limit, $offset);
        $rows = $this->db->get()->result_array();

        return ['rows'=>$rows, 'meta'=>['page'=>$page,'limit'=>$limit,'total'=>$total]];
    }

    public function unread_count($user_id)
    {
        return (int)$this->db->from($this->t)
            ->where('user_id', $user_id)
            ->where('is_read', 0)
            ->count_all_results();
    }

    public function mark_read($user_id, $notification_id)
    {
        $row = $this->db->get_where($this->t, [
            'notification_id'=>$notification_id,
            'user_id'=>$user_id
        ])->row_array();

        if (!$row) return ['status'=>false, 'message'=>'Notification not found'];

        $this->db->update($this->t, [
            'is_read'=>1,
            'read_at'=>date('Y-m-d H:i:s')
        ], ['notification_id'=>$notification_id]);

        return ['status'=>true, 'message'=>'Marked as read'];
    }

    public function mark_all_read($user_id)
    {
        $this->db->where('user_id', $user_id)
            ->where('is_read', 0)
            ->update($this->t, [
                'is_read'=>1,
                'read_at'=>date('Y-m-d H:i:s')
            ]);

        return ['status'=>true, 'message'=>'All marked as read'];
    }

    // Dipanggil oleh module lain (Tasks/Complaints)
    public function push($user_id, $title, $body = null, $link = null, $ref_type = null, $ref_id = null, $severity = 'INFO')
    {
        $this->db->insert($this->t, [
            'user_id' => (int)$user_id,
            'title' => (string)$title,
            'body' => $body,
            'link' => $link,
            'ref_type' => $ref_type,
            'ref_id' => $ref_id ? (int)$ref_id : null,
            'severity' => $severity,
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'read_at' => null,
        ]);
        return (int)$this->db->insert_id();
    }
	
	public function push_dedup($user_id, $title, $body, $link, $ref_type, $ref_id, $severity, $dedup_key)
{
    // Coba insert; kalau duplicate key, anggap sukses tapi tidak kirim ulang.
    try {
        $this->db->insert($this->t, [
            'user_id' => (int)$user_id,
            'title' => (string)$title,
            'body' => $body,
            'link' => $link,
            'ref_type' => $ref_type,
            'ref_id' => $ref_id ? (int)$ref_id : null,
            'severity' => $severity,
            'dedup_key' => $dedup_key,
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'read_at' => null,
        ]);
        return true;
    } catch (Exception $e) {
        // MySQL duplicate key biasanya error 1062
        $msg = $e->getMessage();
        if (strpos($msg, 'Duplicate') !== false || strpos($msg, '1062') !== false) {
            return false; // sudah pernah dikirim hari ini
        }
        // error lain -> bisa log
        return false;
    }
}

}
