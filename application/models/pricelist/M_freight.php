<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_freight extends CI_Model {

    // =======================================================================
    // 1. DATA SEA FREIGHT (FCL & LCL)
    // =======================================================================
    public function get_grouped_rates() {
        $this->db->select('
            r.id, 
            o.name as origin_name, o.country_id as o_country, o.code as o_code, 
            d.name as destination_name, d.country_id as d_country, d.code as d_code, 
            r.liner_name, r.liner_logo, r.cargo_type, 
            r.fcl_validity_from, r.fcl_validity_to, r.fcl_transit_time,
            r.lcl_validity_from, r.lcl_validity_to 
        ');
        
        $this->db->select('COALESCE(SUM(c.price_20), 0) as total_20, COALESCE(SUM(c.price_40), 0) as total_40, COALESCE(SUM(c.price_40hc), 0) as total_40hc', FALSE);
        
        $this->db->from('freight_rates r');
        $this->db->join('tableName o', 'o.id = r.origin_id', 'left'); 
        $this->db->join('tableName d', 'd.id = r.destination_id', 'left'); 
        $this->db->join('freight_rate_details c', 'c.freight_rate_id = r.id', 'left'); 
        
        $this->db->group_start();
        $this->db->where('r.fcl_validity_to >=', date('Y-m-d'));
        $this->db->or_where('r.lcl_validity_to >=', date('Y-m-d'));
        $this->db->group_end();
        
        $this->db->group_by('r.id');
        $this->db->order_by('d.name', 'ASC');
        $this->db->order_by('o.name', 'ASC');
		$this->db->order_by('total_40', 'ASC'); // <-- Tambahkan baris ini
        
        $query = $this->db->get()->result();
        
        // Ambil SEMUA LCL
        $rate_ids = array_column($query, 'id');
        $all_lcls = [];
        if (!empty($rate_ids)) {
            $this->db->where_in('freight_rate_id', $rate_ids);
            $this->db->where('price IS NOT NULL');
            $this->db->order_by('is_priority', 'DESC');
            $this->db->order_by('price', 'ASC');
            $lcl_data = $this->db->get('freight_rate_lcl')->result();
            foreach ($lcl_data as $l) {
                $all_lcls[$l->freight_rate_id][] = $l;
            }
        }

        $grouped = [];
        foreach ($query as $row) {
            $route = (!empty($row->origin_name) ? trim($row->origin_name) : '-') . ' ➝ ' . (!empty($row->destination_name) ? trim($row->destination_name) : '-');
            $grouped[$route][] = [
                'id' => $row->id,
                'liner_name' => $row->liner_name,
                'cargo_type' => $row->cargo_type,
                'fcl_validity_from' => $row->fcl_validity_from,
                'fcl_validity_to' => $row->fcl_validity_to,   // <-- Sudah diubah
                'fcl_transit_time' => $row->fcl_transit_time,
                'lcl_validity_from' => $row->lcl_validity_from,      
                'lcl_validity_to' => $row->lcl_validity_to,   // <-- Sudah diubah
                'lcl_options' => $all_lcls[$row->id] ?? [],
                'prices' => [
                    '20' => $row->total_20, '40' => $row->total_40, '40HC' => $row->total_40hc
                ]
            ];
        }
        return $grouped;
    }

    // 2. FUNGSI AMBIL DETAIL FCL
    public function get_fcl_details($rate_id) {
        return $this->db->order_by('sort_order', 'ASC')
                        ->get_where('freight_rate_details', ['freight_rate_id' => $rate_id]) // PERBAIKAN NAMA TABEL
                        ->result();
    }

    // 3. FUNGSI AMBIL DETAIL LCL
    public function get_lcl_details($rate_id) {
        return $this->db->get_where('freight_rate_lcl_details', ['freight_rate_id' => $rate_id])
                        ->result();
    }

    // =======================================================================
    // 4. DATA AIR FREIGHT (MASKAPAI)
    // =======================================================================
    public function get_grouped_air_rates() {
        $this->db->select('*');
        $this->db->from('airline_cost');
        $this->db->order_by('origin', 'ASC');
        $this->db->order_by('destination', 'ASC');
        $this->db->order_by('airline', 'ASC');
        
        $query = $this->db->get();
        $result = $query->result();

        $grouped = [];
        foreach ($result as $row) {
            // Bentuk format rute seperti: CGK (SOEKARNO-HATTA INTER) ➝ KUL (KUALA LUMPUR INTERNA)
            $route = "{$row->origin_code} (" . trim($row->origin) . ") ➝ {$row->destination_code} (" . trim($row->destination) . ")";
            
            $grouped[$route][] = [
                'id' => $row->id,
                'airline' => $row->airline,
                'airline_code' => $row->airline_code,
                'shc' => $row->shc,
                'commodity' => $row->commodity,
                'minimum' => $row->minimum,
                'normal' => $row->dasar,         // Mapping kolom 'dasar' ke 'normal'
                'rate_45' => $row->empatlima,    // Mapping kolom 'empatlima' ke 'rate_45'
                'rate_100' => $row->seratus,     // Mapping kolom 'seratus' ke 'rate_100'
                'rate_300' => $row->tigaratus,   // Mapping kolom 'tigaratus' ke 'rate_300'
                'rate_500' => $row->limaratus,   // Mapping kolom 'limaratus' ke 'rate_500'
                'rate_1000' => $row->seribu,     // Mapping kolom 'seribu' ke 'rate_1000'
                'valid' => $row->valid,
                'remarks' => $row->remarks
            ];
        }
        return $grouped;
    }

    // =======================================================================
    // 5. DATA INLAND / TRUCKING
    // =======================================================================
    public function get_trucking_rates() {
        $this->db->select('*');
        $this->db->from('trucking_rates');
        $this->db->order_by('destination_name', 'ASC');
        return $this->db->get()->result_array(); 
    }
	
	// =======================================================================
    // 6. DATA TRANSHIPMENT
    // =======================================================================
  public function get_grouped_transhipment_rates() {
        $this->db->select('r.*, h.agent_code, h.agent_name, h.validity_date');
        $this->db->from('ts_rates r');
        // PATCH: join via FK tariff_header_id, BUKAN ts_type.
        // Join lewat ts_type akan menduplikasi baris bila suatu saat ada
        // lebih dari satu header per ts_type (mis. menyimpan histori tarif).
        $this->db->join('ts_tariff_headers h', 'h.id = r.tariff_header_id', 'left');
        $this->db->order_by('r.destination', 'ASC');
        
        $query = $this->db->get();
        $result = $query->result();

        $grouped = [];
        foreach ($result as $row) {
            $hub = $row->ts_type;
            $grouped[$hub][] = $row;
        }

        // PATCH: VIA SIN punya 3 agen (PT Gateway, Freight Links, Astro
        // Pacific). Menampilkan baris mentah akan memunculkan tujuan yang
        // sama tiga kali. Ganti grup VIA SIN dengan baris perbandingan:
        // satu baris per pelabuhan, tarif ketiga agen bersebelahan.
        $compare = $this->get_sin_comparison();
        if (!empty($compare)) {
            $grouped['VIA SIN'] = $compare;
        }
        return $grouped;
    }

    // PATCH: baris perbandingan 3 agen Singapura untuk tampilan daftar.
    // Aman bila view belum dibuat: kembalikan array kosong sehingga
    // pemanggil memakai baris mentah seperti sebelumnya.
    public function get_sin_comparison() {
        if (!$this->db->table_exists('v_ts_sin_compare')) {
            return [];
        }
        $this->db->select('*');
        $this->db->from('v_ts_sin_compare');
        $this->db->order_by('destination', 'ASC');
        $rows = $this->db->get()->result();
        foreach ($rows as $r) {
            $r->is_compare = 1;          // penanda utk template
            $r->ts_type    = 'VIA SIN';
        }
        return $rows;
    }

    // PATCH: baris wakil untuk sebuah port_key. Dipakai saat modal dibuka
    // dari baris perbandingan, yang mewakili 3 agen sekaligus sehingga
    // tidak membawa satu rate_id pun. Dipilih tarif tertinggi agar
    // ringkasan di kepala modal tidak memberi kesan lebih murah dari
    // kenyataan; rincian ketiga agen tetap ditampilkan di bawahnya.
    public function get_sin_representative_rate_id($port_key) {
        if (!$this->db->table_exists('ts_port_map')) return null;
        $sql = "SELECT r.id
                  FROM ts_rates r
                  JOIN ts_port_map p
                    ON p.source='RATE' AND p.port_raw = r.destination
                 WHERE r.ts_type='VIA SIN'
                   AND r.tariff_header_id IN (1,4,5,6)
                   AND p.port_key = ?
                 ORDER BY (r.freight_wm IS NULL), r.freight_wm DESC
                 LIMIT 1";
        $row = $this->db->query($sql, [$port_key])->row();
        return $row ? (int)$row->id : null;
    }

    // PATCH: rincian per agen untuk satu pelabuhan (dipakai modal detail).
    public function get_sin_agent_rows($port_key) {
        if (!$this->db->table_exists('ts_port_map')) return [];
        $sql = "SELECT r.*, h.agent_code, h.agent_name, h.validity_date
                  FROM ts_rates r
                  JOIN ts_port_map p
                    ON p.source='RATE' AND p.port_raw = r.destination
                  LEFT JOIN ts_tariff_headers h ON h.id = r.tariff_header_id
                 WHERE r.ts_type='VIA SIN'
                   AND r.tariff_header_id IN (1,4,5,6)
                   AND p.port_key = ?
                 ORDER BY r.tariff_header_id";
        return $this->db->query($sql, [$port_key])->result();
    }

    // PATCH: ikut ambil data header (agen, validity, T&C) lewat FK.
    public function get_transhipment_detail($rate_id) {
        $this->db->select('r.*,
            h.agent_code, h.agent_name, h.validity, h.validity_date,
            h.currency_code, h.origin_region_name, h.origin_country_name');
        $this->db->from('ts_rates r');
        $this->db->join('ts_tariff_headers h', 'h.id = r.tariff_header_id', 'left');
        $this->db->where('r.id', $rate_id);
        return $this->db->get()->row();
    }

    // =======================================================================
    // 7. BIAYA LOKAL TUJUAN & DATA AGEN  (PATCH)
    //    Dicocokkan lewat ts_port_map karena ejaan nama pelabuhan di file
    //    tarif, file local charges, dan file agent list tidak selalu sama.
    //    Semua fungsi di bawah AMAN dijalankan walau tabel barunya belum
    //    dibuat: akan mengembalikan array kosong, bukan error.
    // =======================================================================

    private function ts_mapping_ready() {
        return $this->db->table_exists('ts_port_map');
    }

    public function get_local_charges_for_destination($destination) {
        if (!$this->ts_mapping_ready() || !$this->db->table_exists('ts_local_charges')) {
            return [];
        }
        $sql = "SELECT DISTINCT c.*
                  FROM ts_local_charges c
                  JOIN ts_port_map mc ON mc.source = 'CHARGE' AND mc.port_raw = c.port
                  JOIN ts_port_map mr ON mr.source = 'RATE'   AND mr.port_key = mc.port_key
                 WHERE mr.port_raw = ?
                 ORDER BY c.source_agent ASC, c.port ASC,
                          FIELD(c.party_type,'COLOADER','DIRECT','PERSONAL EFFECTS'),
                          c.line_seq ASC";
        return $this->db->query($sql, [$destination])->result();
    }

    public function get_agents_for_destination($destination) {
        if (!$this->ts_mapping_ready() || !$this->db->table_exists('ts_agents')) {
            return [];
        }
        $sql = "SELECT DISTINCT a.*
                  FROM ts_agents a
                  JOIN ts_port_map ma ON ma.source = 'AGENT' AND ma.port_raw = a.port
                  JOIN ts_port_map mr ON mr.source = 'RATE'  AND mr.port_key = ma.port_key
                 WHERE mr.port_raw = ?
                 ORDER BY a.source_agent ASC, a.agent_name ASC";
        return $this->db->query($sql, [$destination])->result();
    }
}