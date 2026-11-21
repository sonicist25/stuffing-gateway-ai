<?php
class Vendor_model extends CI_Model
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
	
	function getDataVendor($data,$limit){
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
		return $this->dbx->get_where('refvendor',array('region_id'=>$region))->result();
	}
	
	function manage_data($data){
		$oper = $data['oper'];
		$id = $data['id'];
		$data['vendor_id'] = $id;
		$data['region_id']= $this->session->userdata('region_id');
		
		unset($data['oper']);
		unset($data['id']);
		unset($data['vendor_id']);
		
		if($oper=='add'||$oper=='edit') {
			$data['vendor_code'] = strtoupper($data['vendor_code']);
			$data['vendor_name'] = strtoupper($data['vendor_name']);
			$data['vendor_address'] = ucfirst($data['vendor_address']);
			$data['type'] = $data['type'];
		}
		
		if($oper=='add') {
			
			$this->dbx->insert('refvendor',$data);
		} 
		elseif($oper=='edit') {
			// //print_r($data);
			// die();
			$this->dbx->update('refvendor',$data,array('vendor_id'=>$id));
			// //print_r($this->dbx->last_query());
			// die();
		}
		else {
			$this->dbx->delete('refvendor',array('vendor_id'=>$id));
		}
		return ($this->dbx->affected_rows());
	}
	
	/*
	function insert_vendor($wh_code, $wh_name, $wh_address)
	{
		$this->dbx->insert('refvendor', array('vendor_code'=>strtoupper($wh_code), 'vendor_name'=>ucwords($wh_name), 'vendor_address'=>$wh_address));
	}
	
	function update_vendor($wh_code, $wh_name, $wh_address, $id)
	{
		$this->dbx->where('vendor_id',$id);
		$this->dbx->update('refvendor', array('vendor_code'=>strtoupper($wh_code), 'vendor_name'=>ucwords($wh_name), 'vendor_address'=>$wh_address));
	}
	
	function delete_vendor($id)
	{
		$this->dbx->where('vendor_id',$id);
		$this->dbx->delete('refvendor');
	}
	*/
}	
