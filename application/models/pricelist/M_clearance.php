<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_clearance extends CI_Model {

    // --- FUNGSI UNTUK API REACT ---
	public function get_all_for_api() {
		$tariffs = $this->db->get('clearance_tariffs')->result();
		$info = $this->db->get('clearance_info')->row();

		// Grouping sesuai ENUM baru
		$grouped = [
			'EXP_LCL'    => [],
			'EXP_FCL'    => [],
			'IMP_LCL'    => [],
			'IMP_FCL'    => [],
			'FASILITAS'  => []
		];

		foreach ($tariffs as $t) {
			if (isset($grouped[$t->service_type])) {
				$grouped[$t->service_type][] = $t;
			}
		}

		return [
			'tariffs' => $grouped,
			'info'    => $info
		];
	}


    // --- FUNGSI UNTUK CRUD INTERNAL CI3 ---
    public function get_data_by_id($id) {
        return $this->db->get_where('clearance_tariffs', ['id' => $id])->row_array();
    }

    public function save_tariff($data, $id = null) {
        // Jika As Per Receipt, paksa harga jadi 0 agar data konsisten
        if ($data['is_at_cost'] === 'Y') {
            $data['price_idr'] = 0;
        }

        if ($id) {
            $this->db->where('id', $id);
            return $this->db->update('clearance_tariffs', $data);
        } else {
            return $this->db->insert('clearance_tariffs', $data);
        }
    }

    public function delete_tariff($id) {
        return $this->db->delete('clearance_tariffs', ['id' => $id]);
    }
}