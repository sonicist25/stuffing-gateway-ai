<?php
#[\AllowDynamicProperties] 
class Sales_target_model extends CI_Model {
    
    public function __construct() {
        parent::__construct();

        $tahun = $this->session->userdata('db');

        if ($tahun == '' || !is_numeric($tahun)) {
            $this->db = $this->load->database('master', TRUE);
        } else {
            $config = switch_db($tahun);            // ambil array config
            $this->db = $this->load->database($config, TRUE); // load pakai array
        }
    }
    public function get_master($region_id, $year) {
        return $this->db->get_where('sales_target_master', [
            'region_id' => $region_id,
            'year'      => $year
        ])->row();
    }

    public function get_monthly($target_id) {
        $result = $this->db->get_where('sales_target_monthly', [
            'target_id' => $target_id
        ])->result();

        $monthly = [];
        foreach ($result as $row) {
            $monthly[$row->month] = [
                'revenue_target' => $row->revenue_target,
                'profit_target'  => $row->profit_target
            ];
        }
        return $monthly;
    }
    public function save_master($data) {
        // cek apakah sudah ada master untuk region + year
        $exists = $this->db->get_where('sales_target_master', [
            'region_id' => $data['region_id'],
            'year'      => $data['year']
        ])->row();

        if ($exists) {
            // update
            $this->db->where('target_id', $exists->target_id);
            $this->db->update('sales_target_master', $data);
            return $exists->target_id;
        } else {
            // insert baru
            $this->db->insert('sales_target_master', $data);
            return $this->db->insert_id();
        }
    }

    public function save_monthly($target_id, $monthly_revenue, $monthly_profit) {
        foreach ($monthly_revenue as $month => $rev) {
            $rev_clean  = preg_replace('/[^0-9]/','',$rev);
            $prof_clean = preg_replace('/[^0-9]/','',$monthly_profit[$month]);

            // cek apakah sudah ada distribusi untuk target_id + month
            $exists = $this->db->get_where('sales_target_monthly', [
                'target_id' => $target_id,
                'month'     => $month
            ])->row();

            $data = [
                'target_id'      => $target_id,
                'month'          => $month,
                'revenue_target' => $rev_clean,
                'profit_target'  => $prof_clean
            ];

            if ($exists) {
                $this->db->where('target_id', $target_id);
                $this->db->where('month', $month);
                $this->db->update('sales_target_monthly', $data);
                $monthly_id = $exists->monthly_id;
            } else {
                $this->db->insert('sales_target_monthly', $data);
                $monthly_id = $this->db->insert_id();
            }

            // --- Distribusi ke salesman ---
            $region_id = $this->session->userdata('region_id');
            $this->distribute_to_salesman($monthly_id, $rev_clean, $prof_clean, $region_id);
        }
    }
    
    public function distribute_to_salesman($monthly_id, $rev_target, $prof_target, $region_id) {
    // Ambil semua salesman aktif di region
        $salesmen = $this->db->get_where('refsales', [
            'region_id' => $region_id,
            'is_active' => 1
        ])->result();

        foreach ($salesmen as $s) {
            $percentage = $s->percentage;
            $rev_sales  = ($rev_target * $percentage) / 100;
            $prof_sales = ($prof_target * $percentage) / 100;

            $data = [
                'monthly_id'     => $monthly_id,
                'salesman_id'    => $s->user_id,   // foreign key ke mstadmin.user_id
                'percentage'     => $s->percentage,
                'revenue_target' => $rev_sales,
                'profit_target'  => $prof_sales,
                'fullname'       => $s->fullname,
                'region_id'      => $s->region_id,
                'management'     => $s->management
            ];
    
            // cek apakah sudah ada distribusi untuk salesman + monthly
            $exists = $this->db->get_where('sales_target_salesman', [
                'monthly_id'  => $monthly_id,
                'salesman_id' => $s->user_id
            ])->row();

            if ($exists) {
                $this->db->where('monthly_id', $monthly_id);
                $this->db->where('salesman_id', $s->user_id);
                $this->db->update('sales_target_salesman', $data);
            } else {
                $this->db->insert('sales_target_salesman', $data);
            }
        }
    }
    public function get_salesmen_targets($year, $region_id) {
            $this->db->select('sts.*, r.fullname, r.region_id, r.management, stm.month');
            $this->db->from('sales_target_salesman sts');
            $this->db->join('sales_target_monthly stm', 'stm.monthly_id = sts.monthly_id');
            $this->db->join('refsales r', 'r.user_id = sts.salesman_id');
            $this->db->where('r.region_id', $region_id);
            $this->db->order_by('r.management','desc');
            return $this->db->get()->result();
        }

}
