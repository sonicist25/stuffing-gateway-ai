<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_trucking extends CI_Model {

    public function get_all_trucking($page, $limit, $sidx, $sord, $filters = null) {
        $this->db->from('trucking_rates t');
        
        if ($filters) {
            $f = json_decode($filters, true);
            if ($f && isset($f['rules']) && is_array($f['rules'])) {
                foreach ($f['rules'] as $rule) {
                    if ($rule['op'] == 'cn') {
                        $this->db->like($rule['field'], $rule['data']);
                    }
                }
            }
        }

        $countQuery = clone $this->db;
        $count = $countQuery->select('COUNT(t.id) as cnt', FALSE)->get()->row()->cnt;
        $total_pages = ($count > 0) ? ceil($count / $limit) : 0;

        $this->db->select('t.*');
        $this->db->order_by($sidx, $sord);
        $this->db->limit($limit, ($page - 1) * $limit);
        
        $query = $this->db->get();

        $response = new stdClass();
        $response->page = $page;
        $response->total = $total_pages;
        $response->records = $count;
        $response->rows = array();

        // Buat fungsi helper kecil untuk memformat harga
        $format_price = function($val) {
            if ($val == 0 || $val == null || $val == '') {
                return '-'; // Ganti dengan '' jika Anda ingin benar-benar kosong melompong
            }
            return number_format((float)$val, 0, ',', '.');
        };

        foreach ($query->result_array() as $i => $row) {
            $response->rows[$i]['id'] = $row['id'];
            $response->rows[$i]['cell'] = array(
                $row['id'],
                $row['destination_name'],
                $format_price($row['cdd_price']),
                $format_price($row['fuso_price']),
                $format_price($row['wb_price']),
                $format_price($row['price_20']),
                $format_price($row['price_40'])
            );
        }
        return $response;
    }

    public function save_rate($id, $data) {
        if (!empty($id)) {
            return $this->db->where('id', $id)->update('trucking_rates', $data);
        } else {
            return $this->db->insert('trucking_rates', $data);
        }
    }

    public function delete_rate($id) {
        return $this->db->where('id', $id)->delete('trucking_rates');
    }
}