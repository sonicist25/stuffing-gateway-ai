<?php
class Afg_m_export extends CI_Model
{	
	var $table='trnexport';
	
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
	
	function getNextID()
	{
		$this->dbx->select_max('export_id');
		$query = $this->dbx->get($this->table);
		if ( $query->result() == '')
		{
			return 1;
		}
		else
		{
			return $query->row()->export_id+1;
		}
	}
	
	function getDataExport($country_id,$start,$limit,$sidx,$sord,$where)
	{
		$this->dbx->select('*');
    	$this->dbx->limit($limit);
    	if($where != NULL)$this->dbx->where($where,NULL,FALSE);
		$this->dbx->where('final_destination_country_id',$country_id);
    	$query = $this->dbx->get($this->table,$limit,$start);
		
		return $query;
	}
	
	function getExportById($export_id)
	{
		$this->dbx->select('*');
		$this->dbx->where('export_id',$export_id);
		$query = $this->dbx->get($this->table);
		return $query->row();	
	}
	
	function add_data_export($data)
	{
		$this->dbx->insert($this->table,$data);
		if($this->dbx->affected_rows() > 0) {
			return '1';
		} else {
			return '0';	
		}
	}
	
	function update_data_export($data,$id)
	{
		$this->dbx->where('export_id',$id);
		$this->dbx->update($this->table,$data);
		if($this->dbx->affected_rows() > 0) {
			return '1';
		} else {
			return '0';	
		}
	}
	
	function delete_data_export($id)
	{
		$this->dbx->delete($this->table, array('export_id' => $id));
		if($this->dbx->affected_rows() > 0) {
			return '1';
		} else {
			return '0';	
		}
	}
	
	function add_data_connecting($data)
	{
		$this->dbx->insert('trnexportconecting',$data);
		if($this->dbx->affected_rows() > 0) {
			return '1';
		} else {
			return '0';	
		}
	}
	
	function update_data_connecting($data,$id)
	{
		$this->dbx->where('conn_id',$id);
		$this->dbx->update('trnexportconecting',$data);
		if($this->dbx->affected_rows() > 0) {
			return '1';
		} else {
			return '0';	
		}
	}
	
	function delete_data_connecting($id)
	{
		$this->dbx->where('conn_id',$id);
		$this->dbx->delete('trnexportconecting');
		if($this->dbx->affected_rows() > 0) {
			return '1';
		} else {
			return '0';	
		}
	}
	
	function hide_shipment_request($id){
		$this->dbx->where('shipment_request_id',$id);
		$this->dbx->update('trnshipmentrequest', array('isProcessed'=>'Yes'));
		if($this->dbx->affected_rows() > 0) {
			return '1';
		} else {
			return '0';	
		}
	}
}
