<?php
#[\AllowDynamicProperties]  
Class Customer_model extends CI_Model
{
    public function __construct() {
        parent::__construct();

        $tahun ='2026';

        if ($tahun == '' || !is_numeric($tahun)) {
            $this->dbx = $this->load->database('master', TRUE);
        } else {
            $this->dbx = $this->load->database(switch_db($tahun), TRUE);
        }
    }

    public function get_shipper($region_id = NULL, $sales = NULL, $limit = NULL, $offset = NULL) {
        // select kolom shipper + sales_name
        $this->db->select('mstshipper.*, GROUP_CONCAT(shipper_sales.sales_name) as sales_name');
        $this->db->from('mstshipper');
        $this->db->join('shipper_sales', 'shipper_sales.id_shipper = mstshipper.id_shipper', 'left');

        if ($region_id !== NULL && $sales !== NULL) {
            $this->db->group_start()
                     ->where('mstshipper.region_id', $region_id)
                     ->or_where('shipper_sales.sales_id', $sales)
                     ->group_end();
        } else {
            if ($region_id !== NULL) {
                $this->db->where('mstshipper.region_id', $region_id);
            }
            if ($sales !== NULL) {
                $this->db->where('shipper_sales.sales_id', $sales);
            }
        }

        // Hitung total data
        $this->db->group_by('mstshipper.id_shipper'); // penting supaya GROUP_CONCAT bekerja per shipper
        $total = $this->db->count_all_results('', FALSE);

        // Pagination
        if ($limit !== NULL) {
            $this->db->limit($limit, $offset);
        }

        $query = $this->db->get();
        return [
            'data'  => $query->result(),
            'total' => $total
        ];
    }
    public function search_customer($region_id = NULL, $sales = NULL, $q = NULL, $limit = 20, $offset = 0) {
    // select kolom shipper + sales_name
    $this->db->select('mstshipper.*, GROUP_CONCAT(shipper_sales.sales_name) as sales_name');
    $this->db->from('mstshipper');
    $this->db->join('shipper_sales', 'shipper_sales.id_shipper = mstshipper.id_shipper', 'left');

    // Filter sales
    if ($sales !== NULL) {
        $this->db->where('shipper_sales.sales_id', $sales);
    }

    // Filter keyword
    if ($q !== NULL) {
        $this->db->group_start()
                 ->like('mstshipper.shipper_name', $q)
                 ->or_like('mstshipper.cp_name', $q)
                 ->or_like('mstshipper.cp_email', $q)
                 ->or_like('mstshipper.shipper_npwp', $q)
                 ->group_end();
    }
	
	if ($region_id !== NULL) {
                $this->db->where('mstshipper.region_id', $region_id);
            }

    // Group by supaya GROUP_CONCAT bekerja per shipper
    $this->db->group_by('mstshipper.id_shipper');

    // Hitung total data
    $total = $this->db->count_all_results('', FALSE);

    // Pagination
    $this->db->limit($limit, $offset);

    $query = $this->db->get();

    return [
        'data'  => $query->result(),
        'total' => $total
    ];
}

}