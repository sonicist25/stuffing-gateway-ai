<?php
class M_track_export extends CI_Model{

	function track_list(){
		$prepared = $this->session->userdata('user_id');
		$region = $this->session->userdata('region_id');
		$hasil=$this->dbx->get_where('x_tracking_export', array ('region_id'=>$region,'prepared'=>$prepared));
		return $hasil->result();
	}
	
}