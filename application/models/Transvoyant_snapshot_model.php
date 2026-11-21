<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Transvoyant_snapshot_model extends CI_Model
{
    protected $table = 'tv_mixed_tracking_snapshots';

    public function insert_snapshot(array $data)
    {
        $now = date('Y-m-d H:i:s');
        $data['created_at'] = $data['created_at'] ?? $now;

        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    public function find_last_hash($syncId)
    {
        $row = $this->db
            ->select('response_hash')
            ->from($this->table)
            ->where('sync_id', (int)$syncId)
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get()
            ->row_array();

        return $row ? $row['response_hash'] : null;
    }
}