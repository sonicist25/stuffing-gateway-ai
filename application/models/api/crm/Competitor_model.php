<?php
#[\AllowDynamicProperties]  
class Competitor_model extends CI_Model {

    public function __construct() {
        parent::__construct();

        $tahun ='2026';

        if ($tahun == '' || !is_numeric($tahun)) {
            $this->dbx = $this->load->database('master', TRUE);
        } else {
            $this->dbx = $this->load->database(switch_db($tahun), TRUE);
        }
    }

    public function get_competitors($search = null, $type = null) {
		$this->dbx->select('id, name, website, type, pricing_level, country_origin');
		$this->dbx->from('competitors');
		$this->dbx->where('is_active', 'TRUE');

		if ($search) {
			$this->dbx->like('name', $search);
		}
		
		if ($type) {
			$this->dbx->where('type', $type);
		}

		$this->dbx->order_by('name', 'ASC');
		return $this->dbx->get()->result();
	}
}