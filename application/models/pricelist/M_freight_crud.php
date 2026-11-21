<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_freight_crud extends CI_Model {

   public function get_all_rates($page, $limit, $sidx, $sord, $filters = null) {
        $view_type = $this->input->post('view_type') ?: 'FCL'; // Tangkap list apa yang sedang dilihat
        
        // Base query
        $this->db->from('freight_rates r');
        $this->db->join('tableName o', 'o.id = r.origin_id', 'left');
        $this->db->join('tableName d', 'd.id = r.destination_id', 'left');

        $this->db->select('r.id, r.liner_name, r.cargo_type, r.fcl_validity_to as fcl_validity, r.fcl_transit_time, r.lcl_validity_to as lcl_validity');
        $this->db->select("CONCAT(COALESCE(o.name, '-'), ' ➝ ', COALESCE(d.name, '-')) as route", FALSE);

        // FILTER PINTAR: Saring data berdasarkan tombol switcher list yang aktif
        if ($view_type == 'LCL') {
            $this->db->where('(r.lcl_validity_to IS NOT NULL OR (SELECT COUNT(id) FROM freight_rate_lcl WHERE freight_rate_id = r.id) > 0)', NULL, FALSE);
        } else {
            $this->db->where('(r.fcl_validity_to IS NOT NULL OR (SELECT COUNT(id) FROM freight_rate_details WHERE freight_rate_id = r.id) > 0)', NULL, FALSE);
        }

        // Subselect LCL (Tetap Aman)
        $this->db->select('(SELECT price FROM freight_rate_lcl WHERE freight_rate_id = r.id AND type_name = "Direct Consol" LIMIT 1) as lcl_direct', FALSE);
        $this->db->select('(SELECT price FROM freight_rate_lcl WHERE freight_rate_id = r.id AND type_name = "Via SIN" LIMIT 1) as lcl_sin', FALSE);
        $this->db->select('(SELECT price FROM freight_rate_lcl WHERE freight_rate_id = r.id AND type_name = "Via Busan" LIMIT 1) as lcl_busan', FALSE);
        $this->db->select('(SELECT price FROM freight_rate_lcl WHERE freight_rate_id = r.id AND type_name = "Via HKG" LIMIT 1) as lcl_hkg', FALSE);
        $this->db->select('(SELECT price FROM freight_rate_lcl WHERE freight_rate_id = r.id AND type_name = "Via Nhava Sheva" LIMIT 1) as lcl_nhava', FALSE);
        $this->db->select('(SELECT price FROM freight_rate_lcl WHERE freight_rate_id = r.id AND type_name = "Via Colombo" LIMIT 1) as lcl_colombo', FALSE);
        $this->db->select('(SELECT price FROM freight_rate_lcl WHERE freight_rate_id = r.id AND type_name = "Via Jebel Ali" LIMIT 1) as lcl_jebel_ali', FALSE);

        // Kalkulasi FCL Total All-In
        $this->db->select('COALESCE(SUM(c.price_20), 0) as total_20, COALESCE(SUM(c.price_40), 0) as total_40, COALESCE(SUM(c.price_40hc), 0) as total_40hc', FALSE);
        $this->db->join('freight_rate_details c', 'c.freight_rate_id = r.id', 'left');

        // --- APPLY FILTERS (jika ada) ---
        if ($filters) {
            $f = json_decode($filters, true);
            if ($f && isset($f['rules']) && is_array($f['rules'])) {
                $this->db->group_start();
                foreach ($f['rules'] as $rule) {
                    $field = isset($rule['field']) ? $rule['field'] : '';
                    $op    = isset($rule['op']) ? $rule['op'] : '';
                    $data  = isset($rule['data']) ? $rule['data'] : '';

                    if ($field === 'route') {
                        if ($op == 'cn') {
                            $this->db->group_start(); $this->db->like('o.name', $data); $this->db->or_like('d.name', $data); $this->db->group_end();
                        } elseif ($op == 'eq') {
                            $this->db->group_start(); $this->db->where('o.name', $data); $this->db->or_where('d.name', $data); $this->db->group_end();
                        }
                    } else {
                        if ($op == 'cn') { $this->db->like($field, $data); } 
                        elseif ($op == 'eq') { $this->db->where($field, $data); }
                    }
                }
                $this->db->group_end();
            }
        }

        // Count & Pagination
        $countQuery = clone $this->db;
        $count = $countQuery->select('COUNT(DISTINCT r.id) as cnt', FALSE)->get()->row()->cnt;
        $total_pages = ($count > 0) ? ceil($count / $limit) : 0;

        $this->db->group_by('r.id');
        if ($sidx == 'route') {
            $this->db->order_by('d.name', $sord); $this->db->order_by('o.name', $sord);
        } else {
            if (strpos($sidx, ',') !== false || strpos($sidx, ' ') !== false) {
                $this->db->order_by($sidx, '', FALSE);
            } else {
                $this->db->order_by($sidx, $sord);
            }
        }

        $start = ($page - 1) * $limit;
        $this->db->limit($limit, $start);
        $query = $this->db->get();

        $response = new stdClass();
        $response->page = $page;
        $response->total = $total_pages;
        $response->records = $count;
        $response->rows = array();

        $format_price = function($val) {
            if (!is_numeric($val) || $val === null) return '<span style="color:#aaa;">-</span>';
            if ($val == 0) return '<b style="color:#17a2b8;">FREE</b>';
            if ($val < 0) return '<b style="color:#ffc107;">Rebate ($'.number_format(abs($val), 2).')</b>';
            return '$ ' . number_format((float)$val, 2);
        };

        foreach ($query->result_array() as $i => $row) {
            $response->rows[$i]['id'] = $row['id'];
            $response->rows[$i]['cell'] = array(
                $row['id'],
                $row['route'],
                $row['liner_name'],
                $row['cargo_type'],
                $row['fcl_validity'] ?? '-',
                $row['fcl_transit_time'] ?? '-',
                $row['total_20'] > 0 ? '$ ' . number_format((float)$row['total_20'], 2) : '-',
                $row['total_40'] > 0 ? '$ ' . number_format((float)$row['total_40'], 2) : '-',
                $row['total_40hc'] > 0 ? '$ ' . number_format((float)$row['total_40hc'], 2) : '-',
                $row['lcl_validity'] ?? '-',
                $format_price($row['lcl_direct']),
                $format_price($row['lcl_sin']),
                $format_price($row['lcl_busan']),
                $format_price($row['lcl_hkg']),
                $format_price($row['lcl_nhava']),
                $format_price($row['lcl_colombo']),
                $format_price($row['lcl_jebel_ali'])
            );
        }
        return $response;
    }


    public function save_rate_data($id, $header_data, $fcl_components, $lcl_components, $lcl_routes, $lcl_transit, $lcl_priority) {
        $this->db->trans_start();

        // 1. PROSES SIMPAN / UPDATE TABLE MASTER (freight_rates)
        if (!empty($id)) {
            $this->db->where('id', $id);
            $this->db->update('freight_rates', $header_data);
        } else {
            $this->db->insert('freight_rates', $header_data);
            $id = $this->db->insert_id();
        }

        // =========================================================================
        // 2. PROSES RE-INSERT RINGKASAN RUTE LCL (freight_rate_lcl) - DIPERBAIKI!
        // =========================================================================
        $this->db->delete('freight_rate_lcl', array('freight_rate_id' => $id));
        
        $lcl_mapping = [
            'direct_consol' => 'Direct Consol',
            'via_sin'       => 'Via SIN',
            'via_busan'     => 'Via Busan',
            'via_hkg'       => 'Via HKG',
            'via_nhava'     => 'Via Nhava Sheva',
            'via_colombo'   => 'Via Colombo',
            'via_jebel_ali' => 'Via Jebel Ali'
        ];

        $batch_lcl = [];
        foreach ($lcl_routes as $key => $price) {
            // Hanya masukkan rute jika harganya diisi (tidak kosong)
            if (isset($lcl_mapping[$key]) && $price !== '' && $price !== null) {
                
                // MENGUBAH ARRAY CHECKBOX MENJADI NILAI 1 ATAU 0
                $is_prio = 0; 
                // Pastikan $lcl_priority ada, berupa array, dan rute saat ini ($key) dicentang
                if (!empty($lcl_priority) && is_array($lcl_priority)) {
                    if (in_array($key, $lcl_priority)) {
                        $is_prio = 1;
                    }
                }

                $batch_lcl[] = array(
                    'freight_rate_id' => $id,
                    'type_name'       => $lcl_mapping[$key],
                    'price'           => $price,
                    'transit_time'    => isset($lcl_transit[$key]) ? $lcl_transit[$key] : null,
                    'is_priority'     => $is_prio // Memasukkan nilai 1 (jika dicentang) atau 0
                );
            }
        }
        
        if (!empty($batch_lcl)) {
            $this->db->insert_batch('freight_rate_lcl', $batch_lcl);
			//print_r($this->db->last_query());
			//die();
        }
        // =========================================================================


        // 3. PROSES RE-INSERT RINCIAN CHARGES FCL (freight_rate_details)
        $this->db->delete('freight_rate_details', array('freight_rate_id' => $id));
        
        // Map nama grup text dari select HTML menjadi ID angka agar sinkron dengan data lama
        $group_mapping = [
            'Freight Components' => 1,
            'Inland Moda'        => 2,
            'Subject To'         => 3
        ];

        if (!empty($fcl_components['service_name'])) {
            $batch_fcl_details = [];
            $sort = 1;
            foreach ($fcl_components['service_name'] as $i => $name) {
                if (empty($name)) continue;
                
                $group_text = $fcl_components['group'][$i];
                $group_id = isset($group_mapping[$group_text]) ? $group_mapping[$group_text] : 1;

                $batch_fcl_details[] = array(
                    'freight_rate_id' => $id,
                    'group_id'        => $group_id,
                    'component_name'  => $name,
                    'unit_basis'      => $fcl_components['unit'][$i],
                    'price_20'        => ($fcl_components['price_20'][$i] !== '') ? $fcl_components['price_20'][$i] : null,
                    'price_40'        => ($fcl_components['price_40'][$i] !== '') ? $fcl_components['price_40'][$i] : null,
                    'price_40hc'      => ($fcl_components['price_40hc'][$i] !== '') ? $fcl_components['price_40hc'][$i] : null,
                    'sort_order'      => $sort++
                );
            }
            if (!empty($batch_fcl_details)) {
                $this->db->insert_batch('freight_rate_details', $batch_fcl_details);
            }
        }

        // 4. PROSES RE-INSERT RINCIAN CHARGES LCL (freight_rate_lcl_details - TABEL BARU)
        $this->db->delete('freight_rate_lcl_details', array('freight_rate_id' => $id));
        if (!empty($lcl_components['service_name'])) {
            $batch_lcl_details = [];
            foreach ($lcl_components['service_name'] as $i => $name) {
                if (empty($name)) continue;

                $batch_lcl_details[] = array(
                    'freight_rate_id' => $id,
                    'lcl_group'       => $lcl_components['group'][$i],
                    'service_name'    => $name,
                    'unit_basis'      => $lcl_components['unit'][$i],
                    'price_wm'        => ($lcl_components['price'][$i] !== '') ? $lcl_components['price'][$i] : null
                );
            }
            if (!empty($batch_lcl_details)) {
                $this->db->insert_batch('freight_rate_lcl_details', $batch_lcl_details);
            }
        }

        $this->db->trans_complete();
        return $this->db->trans_status();
    }
}