<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_vendors extends MY_Model
{
    public function get_select2_data($region_id, $searchTerm = "")
    {
        $region_id  = trim((string)$region_id);
        $searchTerm = trim((string)$searchTerm);

        // Region wajib.
        // Jangan izinkan query vendor tanpa region karena sistem multi-region.
        if ($region_id === '') {
            return [];
        }

        $this->db->select("
            vendor_id AS id,
            vendor_name AS text,
            type,
            vendor_code,
            region_id
        ", false);

        $this->db->from('refvendor');

        // Vendor hanya boleh dari region user yang aktif.
        $this->db->where('region_id', $region_id);

        if ($searchTerm !== '') {
            $this->db->group_start()
                ->like('vendor_name', $searchTerm)
                ->or_like('vendor_code', $searchTerm)
            ->group_end();
        }

        $this->db->order_by('vendor_name', 'ASC');
        $this->db->limit(50);

        return $this->db->get()->result_array();
    }
}