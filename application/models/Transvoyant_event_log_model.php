<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Transvoyant_event_log_model extends CI_Model
{
    protected $table = 'tv_event_logs';

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

    public function exists_success_event($trackingNumber, $code, $description, $eventTimestamp)
    {
        return $this->db
            ->from($this->table)
            ->where('tracking_number', $trackingNumber)
            ->where('event_code', $code)
            ->where('event_description', $description)
            ->where('event_timestamp', $eventTimestamp)
            ->where('status', 'SUCCESS')
            ->limit(1)
            ->count_all_results() > 0;
    }

    public function find_last_success_by_event_key($syncId, $eventKey)
    {
        return $this->db
            ->from($this->table)
            ->where('sync_id', (int)$syncId)
            ->where('event_key', $eventKey)
            ->where('status', 'SUCCESS')
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get()
            ->row_array();
    }
}