<?php
class Afg_m_ref extends CI_Model
{	
	var $table='refcontainer';
	
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
	
	function getListCarrier()
	{
		$this->dbx->order_by("shipping_company_code","asc");
		return $this->dbx->get('refshippingcompany');
	}
	
	function getDataContainer($start,$limit,$sidx,$sord,$where)
	{
		$this->dbx->select('
							container_id,
							container_name
							');
		$this->dbx->limit($limit);
    	if($where != NULL)$this->dbx->where($where,NULL,FALSE);
    	$this->dbx->order_by($sidx,$sord);
    	$query = $this->dbx->get($this->table,$limit,$start);
		return $query;	
	}
	
	function add($container_id,$container_name)
	{
    	$data = array
		(
      		'container_id'=>strtoupper($container_id),
      		'container_name'=>ucwords($container_name)
    	);
    	$this->dbx->insert($this->table,$data);
	}
	function update($container_id_old,$container_id,$container_name)
	{
    	$data = array
		(
      		'container_id'=>strtoupper($container_id),
      		'container_name'=>ucwords($container_name)
    	);
    	$this->dbx->where('container_id',$container_id_old);
    	$this->dbx->update($this->table,$data);
	}
	function delete($container_id_del)
	{
		$this->dbx->delete($this->table, array('container_id' => $container_id_del));
	}
	
	function getListColoader()
	{
		$this->dbx->select('shipping_company_id,shipping_company_name',FALSE);
		$this->dbx->where ('region_id',$this->session->userdata("region_id"));
		return $this->dbx->get('refshippingcompany');
	}
	
	function getListAgent()
	{
		$this->dbx->select
		('
			agent_company_id,agent_company_name
		',FALSE);
		$this->dbx->where ('region_id',$this->session->userdata("region_id"));
		return $this->dbx->get('refagentcompany');
	}
	
	function getListWarehouse()
	{
		$this->dbx->select
		('warehouse_id,warehouse_code,warehouse_name,warehouse_address,position
		',FALSE);
		$this->dbx->where ('region_id',$this->session->userdata("region_id"));
		return $this->dbx->get('refwarehouse');
	}
	function getListGudang(){
		$searchTerm = $_GET['term'];
		$this->dbx->select(array('warehouse_code','warehouse_name'));
		$this->dbx->like('warehouse_code',$searchTerm);
		$q = $this->dbx->get('foobar');
		return $q;
	}
	
	function getListPickup($id_shipper)
	{
		$this->dbx->select
		('id,shipper_id,name,map_address,location',FALSE);
		$this->dbx->where ('shipper_id',$id_shipper);
		return $this->dbx->get('shipper_location');
	}
	
	function getListPortOfLoading()
	{
		$this->dbx->select
		('
			portloading_id,portloading_port,portloading_city,portloading_remark
		',FALSE);
		return $this->dbx->get('refportloading');
	}
	
	function getListContainer()
	{
		$this->dbx->select
			('
			typecontainer_name
			',FALSE);
		return $this->dbx->get('reftypecontainer');
	}
	
	
}
?>