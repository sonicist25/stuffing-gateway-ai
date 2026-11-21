<?php
class Afg_m_countries extends CI_Model
{	
	var $table='mstcountries';
	
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
	
	function getDataCountries($start,$limit,$sidx,$sord,$where)
	{
		$this->dbx->select('*');
		$this->dbx->limit($limit);
    	if($where != NULL)$this->dbx->where($where,NULL,FALSE);
    	$this->dbx->order_by($sidx,$sord);
    	$query = $this->dbx->get($this->table,$limit,$start);
		return $query;	
	}
	
	function getCountriesById($country_id)
	{
		$sql = "select * from mstcountries where country_id = ?"; 		
		$query = $this->dbx->query($sql,array($country_id));
		return $query->row();
	}
	
	function add($country_id,$country_name)
	{
		if($country_id != '' && $country_name != '') {
    		$data = array
			(
      		'country_id'=>strtoupper($country_id),
      		'country_name'=>ucwords($country_name)
    		);
    		$this->dbx->insert($this->table,$data);
    	} else {
			return FALSE;
    	}
	}
	
	function update($id,$country_id,$country_name)
	{
    	$data = array
		(
      		'country_id'=>strtoupper($country_id),
      		'country_name'=>ucwords($country_name)
    	);
    	$this->dbx->where('idnya',$id);
    	$this->dbx->update($this->table,$data);
	}
	function delete($id)
	{
		$this->dbx->delete($this->table, array('idnya' => $id));
	}
	
	function getListCountries()
	{
		$this->dbx->select
		('
			country_id,country_name
		',FALSE);
		return $this->dbx->get($this->table);
	}
	
	function get_nama_country($country_id)
	{
		$this->dbx->select('*');
		$this->dbx->from($this->table);
		$this->dbx->where('country_id',$country_id);
    	$data = $this->dbx->get();
    	if($data->num_rows() > 0) {
    		$row = $data->row();
    		return $row->country_name;
    	} else {
    		return "";
    	}
	}
}
?>