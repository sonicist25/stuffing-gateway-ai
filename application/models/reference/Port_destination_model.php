<?php
class Port_destination_model extends CI_Model
{
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
	
	function getListDestination(){
		$this->dbx->select ('portdestination_code,portdestination_name ',FALSE);
		$this->dbx->from('refportdestination');
		$this->dbx->order_by('portdestination_name','asc');
		return $this->dbx->get();
	}
	
	function getDatarefportdestination(){
		$q = $this->dbx->query("select * from refportdestination");
		return $q;
	}
	
	function insert_port_destination($wh_code, $wh_name, $wh_country, $wh_remark){
		$this->dbx->insert('refportdestination', array('portdestination_code'=>strtoupper($wh_code), 'portdestination_name'=>ucwords($wh_name), 'portdestination_country'=>$wh_country , 'portdestination_remark'=>ucwords($wh_remark)));
	}
	
	function update_port_destination($wh_code, $wh_name, $wh_country, $wh_remark, $id){
		$this->dbx->where('portdestination_id',$id);
		$this->dbx->update('refportdestination',array('portdestination_code'=>strtoupper($wh_code), 'portdestination_name'=>ucwords($wh_name), 'portdestination_country'=>$wh_country , 'portdestination_remark'=>ucwords($wh_remark)));
	}
	
	function delete_port_destination($id){
		$this->dbx->where('portdestination_id',$id);
		$this->dbx->delete('refportdestination');
	}
	
}	
