<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class SupportTicket_model extends CI_Model {

    private $table = 'tickets';

    public $dbx;

    public function __construct() {
        parent::__construct();

        $tahun = $this->session->userdata('db');

        if ($tahun == '' || !is_numeric($tahun)) {
            $this->dbx = $this->load->database('master', TRUE);
        } else {
            $this->dbx = $this->load->database(switch_db($tahun), TRUE);
        }
    }

    public function get_tickets($limit, $start, $sidx, $sord, $search = null)
    {
        $this->dbx->from($this->table);

        if (!empty($search)) {
            $this->dbx->group_start();
            $this->dbx->like('username', $search);
            $this->dbx->or_like('description', $search);
            $this->dbx->or_like('status', $search);
            $this->dbx->group_end();
        }

        $count = $this->dbx->count_all_results('', false);

        if ($count == 0) { // no results
            return ['count' => 0, 'results' => []];
        }

        if (!empty($sidx) && !empty($sord)) {
            $this->dbx->order_by($sidx, $sord);
        }

        $this->dbx->limit($limit, $start);
        $query = $this->dbx->get();
        $results = $query->result();

        return ['count' => $count, 'results' => $results];
    }

    public function get_ticket($id)
    {
        return $this->dbx->get_where($this->table, ['id' => $id])->row();
    }

    public function add_ticket($data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->dbx->insert($this->table, $data);
        return $this->dbx->insert_id();
    }

    public function update_ticket($id, $data)
    {
        $this->dbx->where('id', $id);
        return $this->dbx->update($this->table, $data);
    }

    public function delete_ticket($id)
    {
        $this->dbx->where('id', $id);
        return $this->dbx->delete($this->table);
    }
}

