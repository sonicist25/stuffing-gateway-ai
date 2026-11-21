<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Transvoyant_attribute_log_model extends CI_Model
{
    protected $table = 'tv_attribute_logs';

    public function insert_log(array $data)
    {
        $now = date('Y-m-d H:i:s');
        $data['created_at'] = $data['created_at'] ?? $now;
        $data['updated_at'] = $data['updated_at'] ?? $now;

        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    public function update_log($id, array $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');

        return $this->db
            ->where('id', (int)$id)
            ->update($this->table, $data);
    }

    public function find_last_success_by_hash($syncId, $payloadHash)
    {
        return $this->db
            ->from($this->table)
            ->where('sync_id', (int)$syncId)
            ->where('payload_hash', $payloadHash)
            ->where('status', 'SUCCESS')
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get()
            ->row_array();
    }

    public function find_last_success($syncId)
    {
        return $this->db
            ->from($this->table)
            ->where('sync_id', (int)$syncId)
            ->where('status', 'SUCCESS')
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get()
            ->row_array();
    }
}