<?php
class Warehouse_model extends CI_Model
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
	
	function getDataWarehouse($data,$limit){
		$region = $this->session->userdata('region_id');
		if($data['_search']=='true') {
			$searchField = $data['searchField'];
			$searchString = $data['searchString'];
      	$searchOper  =  $data['searchOper'];
			
			$ops = array(
            	'eq'=>'=', //equal
            	'ne'=>'<>',//not equal
            	'bw'=>'LIKE', //begins with
            	'lt'=>'<', //less than
            	'le'=>'<=',//less than or equal
            	'gt'=>'>', //greater than
            	'ge'=>'>=',//greater than or equal 
            	'bn'=>'NOT LIKE', //doesn't begin with
            	'in'=>'LIKE', //is in
            	'ni'=>'NOT LIKE', //is not in
            	'ew'=>'LIKE', //ends with
            	'en'=>'NOT LIKE', //doesn't end with
            	'cn'=>'LIKE', // contains
            	'nc'=>'NOT LIKE'  //doesn't contain
        	);

      	foreach ($ops as $key=>$value){
 				if($searchOper==$key) {
            	$ops = $value;
          	}
     		} 
            
       	if($searchOper == 'eq' ) $searchString = $searchString;
       	if($searchOper == 'bw' || $searchOper == 'bn') $searchString .= '%';
       	if($searchOper == 'ew' || $searchOper == 'en' ) $searchString = '%'.$searchString;
      	if($searchOper == 'cn' || $searchOper == 'nc' || $searchOper == 'in' || $searchOper == 'ni') $searchString = '%'.$searchString.'%';
				
      	$where = "$searchField $ops '$searchString' ";                     
			$this->dbx->where($where); 				            
		}
		
		if($limit==true) {
			$this->dbx->limit($data['rows'],$data['start']);
		}
		$this->dbx->order_by($data['sidx'],$data['sord']);
		return $this->dbx->get_where('refwarehouse',array('region_id'=>$region))->result();
	}
	
	function manage_data($data){
		$oper = $data['oper'];
		$id = $data['id'];
		$data['warehouse_id'] = $id;
		$data['region_id']= $this->session->userdata('region_id');
		
		unset($data['oper']);
		unset($data['id']);
		unset($data['warehouse_id']);
		
		if($oper=='add'||$oper=='edit') {
			$data['warehouse_code'] = strtoupper($data['warehouse_code']);
			$data['warehouse_name'] = strtoupper($data['warehouse_name']);
			$data['warehouse_address'] = ucfirst($data['warehouse_address']);
			$data['position'] = $data['position'];
		}
		
		if($oper=='add') {
			
			$this->dbx->insert('refwarehouse',$data);
		} 
		elseif($oper=='edit') {
			// //print_r($data);
			// die();
			$this->dbx->update('refwarehouse',$data,array('warehouse_id'=>$id));
			// //print_r($this->dbx->last_query());
			// die();
		}
		else {
			$this->dbx->delete('refwarehouse',array('warehouse_id'=>$id));
		}
		return array('status'=>$this->dbx->affected_rows(),'oper'=>$oper);
	}
	
	/*
	function insert_warehouse($wh_code, $wh_name, $wh_address)
	{
		$this->dbx->insert('refwarehouse', array('warehouse_code'=>strtoupper($wh_code), 'warehouse_name'=>ucwords($wh_name), 'warehouse_address'=>$wh_address));
	}
	
	function update_warehouse($wh_code, $wh_name, $wh_address, $id)
	{
		$this->dbx->where('warehouse_id',$id);
		$this->dbx->update('refwarehouse', array('warehouse_code'=>strtoupper($wh_code), 'warehouse_name'=>ucwords($wh_name), 'warehouse_address'=>$wh_address));
	}
	
	function delete_warehouse($id)
	{
		$this->dbx->where('warehouse_id',$id);
		$this->dbx->delete('refwarehouse');
	}
	*/
}	
