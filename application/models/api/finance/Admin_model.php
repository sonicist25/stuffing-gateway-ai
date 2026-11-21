<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin_model extends MY_Model
{
    public function check_login($username, $password)
    {
        $this->db->where('username', $username);
        $this->db->where('password', md5($password)); // legacy sesuai data mstadmin saat ini
        $this->db->where('active', 'YES');

        return $this->db->get('mstadmin')->row();
    }

    public function get_user_by_id($user_id)
    {
        $this->db->where('user_id', (int)$user_id);
        $this->db->where('active', 'YES');

        return $this->db->get('mstadmin')->row();
    }

    public function get_employees($search = '', $region_id = '')
    {
        $region_id = trim((string)$region_id);

        if ($region_id === '') {
            return [];
        }

        $this->db->select('user_id as id, fullname as text');
        $this->db->where('region_id', $region_id);
        $this->db->where('active', 'YES');

        if ($search !== '') {
            $this->db->like('fullname', $search);
        }

        $this->db->order_by('fullname', 'ASC');

        return $this->db->get('mstadmin')->result_array();
    }
}
