<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_transhipment extends CI_Model {

    public function get_grid_data($page, $limit, $sidx, $sord, $filters = null) {
        $this->db->from('ts_rates');
        
        if ($filters) {
            $f = json_decode($filters, true);
            if ($f && isset($f['rules']) && is_array($f['rules'])) {
                foreach ($f['rules'] as $rule) {
                    if ($rule['op'] == 'cn') {
                        $this->db->like($rule['field'], $rule['data']);
                    } else if ($rule['op'] == 'eq') {
                        $this->db->where($rule['field'], $rule['data']);
                    }
                }
            }
        }

        $countQuery = clone $this->db;
        $count = $countQuery->select('COUNT(id) as cnt', FALSE)->get()->row()->cnt;
        $total_pages = ($count > 0) ? ceil($count / $limit) : 0;

        $this->db->select('*');
        $this->db->order_by($sidx, $sord);
        $this->db->limit($limit, ($page - 1) * $limit);
        $query = $this->db->get();

        $response = new stdClass();
        $response->page = $page;
        $response->total = $total_pages;
        $response->records = $count;
        $response->rows = array();

        foreach ($query->result_array() as $i => $row) {
            $response->rows[$i]['id'] = $row['id'];
            $response->rows[$i]['cell'] = array(
                $row['id'],
                '<span class="badge ' . ($row['ts_type'] == 'VIA SIN' ? 'bg-info' : 'bg-warning') . '">' . $row['ts_type'] . '</span>',
                $row['destination'],
                $row['country'],
                $row['via_port'],
                '$ ' . ($row['freight_wm'] === null 
				? '-' 
				: number_format($row['freight_wm'], 2)),

                $row['transit_time_1st'],
                $row['transit_time_2nd'],
                $row['dg_class3'],
                $row['dg_class2']
            );
        }
        return $response;
    }

    public function save_rate($id, $data) {
        if (!empty($id)) {
            return $this->db->where('id', $id)->update('ts_rates', $data);
        } else {
            return $this->db->insert('ts_rates', $data);
        }
    }

    public function delete_rate($id) {
        return $this->db->where('id', $id)->delete('ts_rates');
    }
}