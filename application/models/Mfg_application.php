<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Mfg_application extends CI_Model { 

	public function __construct(){
    	parent::__construct();
    	$this->load->database('master',false);
		$this->load->database(''.$this->session->userdata('db').'',true);
   }
   
   
}