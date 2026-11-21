<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Transhipment_model extends CI_Model {

    public function resolve_country_name($city_name) {
		// 1. Dapatkan 2-huruf country_id (misal: "MX") dari tableName
		$this->db->select('country_id');
		$this->db->from('tableName');
		$this->db->where('name', $city_name);
		$this->db->limit(1);
		$query = $this->db->get();

		if ($query->num_rows() > 0) {
			$country_code = $query->row()->country_id;
			
			// 2. Terjemahkan "MX" menjadi "MEXICO"
			// Asumsi: Kamu memiliki tabel master negara (misal 'master_country' atau 'm_country')
			// Silakan sesuaikan 'master_country', 'country_code', dan 'country_name' dengan field di databasemu.
			$this->db->select('country_name'); 
			$this->db->from('mstcountries'); 
			$this->db->where('country_id', $country_code);
			$country_query = $this->db->get();
			
			if ($country_query->num_rows() > 0) {
				return strtoupper($country_query->row()->country_name); // Mengembalikan "MEXICO"
			}
			
			// Fallback manual jika tabel master_country belum ada/belum disesuaikan
			$manual_countries = [
				'MX' => 'MEXICO',
				'PH' => 'PHILIPPINES',
				'US' => 'USA',
				'MY' => 'MALAYSIA',
				'TH' => 'THAILAND',
				'VN' => 'VIETNAM',
				'CN' => 'CHINA',
				'JP' => 'JAPAN',
				'KR' => 'KOREA',
				'IN' => 'INDIA',
				'ID' => 'INDONESIA'
			];
			
			return $manual_countries[$country_code] ?? $country_code;
		}
		
		return null;
	}
	
	 public function search_destinations($keyword, $limit = 30) {
		$this->db->select('name, code, country_id');
		$this->db->from('tableName');
		$this->db->where('is_active', 'TRUE'); 
		
		if (!empty($keyword)) {
			$this->db->group_start();
			$this->db->like('name', $keyword);
			$this->db->or_like('code', $keyword);
			$this->db->group_end();
		}
		
		$this->db->order_by('name', 'ASC');
		$this->db->limit($limit);
		
		return $this->db->get()->result_array();
	}
	
	public function get_city_name_by_code($code) {
		$this->db->select('name');
		$this->db->from('tableName');
		$this->db->where('code', $code);
		$this->db->limit(1);
		$query = $this->db->get();

		if ($query->num_rows() > 0) {
			return $query->row()->name;
		}
		
		return $code; // Fallback jika tidak ditemukan
	}
	
	public function get_location_details($code) {
		$this->db->select('t.name as city_name, c.country_name');
		$this->db->from('tableName t');
		// Melakukan JOIN dengan mstcountries berdasarkan country_id
		$this->db->join('mstcountries c', 't.country_id = c.country_id', 'left');
		$this->db->where('t.code', $code); // Pencarian dijamin unik berdasarkan kode (MXZLO)
		$this->db->limit(1);
		$query = $this->db->get();

		if ($query->num_rows() > 0) {
			$row = $query->row();
			return [
				'city' => $row->city_name,
				'country' => $row->country_name ? strtoupper($row->country_name) : null
			];
		}
		
		// Fallback jika kode tidak ditemukan
		return ['city' => $code, 'country' => null]; 
	}
}