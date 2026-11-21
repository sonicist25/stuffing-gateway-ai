<?php
class Consignee_notify_model extends CI_Model
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
	
	function getDataconsignee_notify($data,$limit){
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
		return $this->dbx->get_where('refconsigneenotify',array('region_id'=>$region))->result();
	}
	
	function manage_data($data){
		$oper = $data['oper'];
		$id = $data['id'];
		$data['consignee_notify_id'] = $id;
		$data['region_id']= $this->session->userdata('region_id');
		
		unset($data['oper']);
		unset($data['id']);
		unset($data['consignee_notify_id']);
		
		if($oper=='add'||$oper=='edit') {
			$data['consignee_notify_code'] = strtoupper($data['consignee_notify_code']);
			$data['consignee_notify_name'] = strtoupper($data['consignee_notify_name']);
			$data['consignee_notify_address'] = ucfirst($data['consignee_notify_address']);
			$data['consignee_notify_npwp'] = strtoupper($data['consignee_notify_npwp']);
		}
		
		if($oper=='add') {
			
			$this->dbx->insert('refconsigneenotify',$data);
		} 
		elseif($oper=='edit') {
			$this->dbx->update('refconsigneenotify',$data,array('consignee_notify_id'=>$id));
		}
		else {
			$this->dbx->delete('refconsigneenotify',array('consignee_notify_id'=>$id));
		}
		return array('status'=>$this->dbx->affected_rows(),'oper'=>$oper);
	}
	
	/*
	function insert_consignee_notify($wh_code, $wh_name, $wh_address)
	{
		$this->dbx->insert('refconsigneenotify', array('consignee_notify_code'=>strtoupper($wh_code), 'consignee_notify_name'=>ucwords($wh_name), 'consignee_notify_address'=>$wh_address));
	}
	
	function update_consignee_notify($wh_code, $wh_name, $wh_address, $id)
	{
		$this->dbx->where('consignee_notify_id',$id);
		$this->dbx->update('refconsigneenotify', array('consignee_notify_code'=>strtoupper($wh_code), 'consignee_notify_name'=>ucwords($wh_name), 'consignee_notify_address'=>$wh_address));
	}
	
	function delete_consignee_notify($id)
	{
		$this->dbx->where('consignee_notify_id',$id);
		$this->dbx->delete('refconsigneenotify');
	}
	*/
	
	function getListConsignee(){
		$this->dbx->select ('consignee_notify_id,consignee_notify_code,consignee_notify_name, consignee_notify_address',FALSE);
		$this->dbx->where('region_id',$this->session->userdata('region_id'));
		return $this->dbx->get('refconsigneenotify');
	}
	
}	
