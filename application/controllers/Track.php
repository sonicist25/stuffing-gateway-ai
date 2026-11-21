<?php
class Track extends CI_Controller{
	var $title = 'Table Update Track and Trace';
	
function __construct(){
		parent::__construct();
		$this->load->model('m_track');
	}
	function index(){
		$this->load->view('v_track');
	}

	function data_track(){
		$data=$this->m_track->track_list();
		echo json_encode($data);
	}
}