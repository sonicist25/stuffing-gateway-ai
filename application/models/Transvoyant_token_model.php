<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Transvoyant_token_model extends CI_Model
{
    protected $table = 'tv_api_tokens';

    public function get_active_token($env, $tenant)
    {
        return $this->db
            ->from($this->table)
            ->where('env', $env)
            ->where('tenant_identifier', $tenant)
            ->where('is_active', 1)
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get()
            ->row_array();
    }

    public function deactivate_tokens($env, $tenant)
    {
        return $this->db
            ->where('env', $env)
            ->where('tenant_identifier', $tenant)
            ->where('is_active', 1)
            ->update($this->table, [
                'is_active'  => 0,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
    }

    public function insert_token(array $data)
    {
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }
}