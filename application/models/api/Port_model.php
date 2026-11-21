<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Port_model extends CI_Model {
	
	   public function __construct()
    {
        parent::__construct();
        $this->load->database('master', TRUE);
    }

    // 1 & 2: Ambil data dari tableName (Ekspor & Impor)
	  // Endpoint 1: Export
	// POL: Indonesia (ID), POD: Luar Indonesia (!ID)
public function get_locations($is_indonesia = TRUE, $q = '') {
    $this->db->select('code, name, country_id');
    $this->db->from('tableName');
    $this->db->where('is_active', 'TRUE');
    
    if ($is_indonesia) {
        $this->db->where('country_id', 'ID');
    } else {
        $this->db->where('country_id !=', 'ID');
    }

    // Tambahkan filter pencarian jika ada parameter q
    if (!empty($q)) {
        $this->db->group_start();
        $this->db->like('name', $q);
        $this->db->or_like('code', $q);
        $this->db->group_end();
    }
    
    return $this->db->get()->result();
}

    // 3: Ambil data Domestik (Wilayah Indonesia)
	public function search_domestic($query) {
		if (empty($query)) return [];

		$sql = "
			SELECT id, UPPER(name) AS name, 'Province' as type 
			FROM reg_provinces 
			WHERE name LIKE ? OR CAST(id AS CHAR) LIKE ?
			UNION
			SELECT id, UPPER(name) AS name, 'Regency' as type 
			FROM reg_regencies 
			WHERE name LIKE ? OR CAST(id AS CHAR) LIKE ?
			UNION
			SELECT id, UPPER(name) AS name, 'District' as type 
			FROM reg_districts 
			WHERE name LIKE ? OR CAST(id AS CHAR) LIKE ?
			LIMIT 50
		";

		$search = "%$query%";
		return $this->db->query($sql, [
			$search, $search, // provinces
			$search, $search, // regencies
			$search, $search  // districts
		])->result();
	}
	
	 public function search_airport($query) {
        $this->db->select('iata_code, name');
        $this->db->from('ref_airport');
        $this->db->like('name',$query);
        $this->db->or_like('iata_code',$query);
        
        return $this->db->get()->result();
    }
	
	public function get_port($q = '') {
    $this->db->select('code, name, country_id');
    $this->db->from('tableName');
    $this->db->where('is_active', 'TRUE');

    // Tambahkan filter pencarian jika ada parameter q
    if (!empty($q)) {
        $this->db->group_start();
        $this->db->like('name', $q);
        $this->db->or_like('code', $q);
        $this->db->group_end();
    }
    
    return $this->db->get()->result();
}
}