<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Menu_model extends CI_Model { 

  public $dbx;

    public function __construct() {
        parent::__construct();

        $tahun = $this->session->userdata('db');

        if ($tahun == '' || !is_numeric($tahun)) {
            $this->dbx = $this->load->database('master', TRUE);
        } else {
            $this->dbx = $this->load->database(switch_db($tahun), TRUE);
        }
    }

	public function __cosntruct() { 
   	parent::__cosntruct(); 
   }
   
   function get_menu(){
		$query = $this->dbx->get('x_menu');
		return $query->result();
	}
   
   function get_menu_1(){
		$query = $this->dbx->get_where('x_menu', array('type_menu'=> 1 ));
		return $query;
	}
	
	function get_menu_2(){
		$query = $this->dbx->get_where('x_menu', array('type_menu'=> 2, 'kat_menu' => 1));
		return $query;
	}
	
	function get_type_menu($type, $kat){
		$query = $this->dbx->get_where('x_menu', array('type_menu'=> $type,'kat_menu'=>$kat));
		return $query;
	}
}