<?php
class Agent_company_model extends CI_Model
{
	function __construct(){
		parent::__construct();
		$y = $this->session->userdata('db');  
    	if($y=='') : $db='master'; else : $db=switch_db($y); endif;
    	$this->load->database($db);

	}
	
	function getDataagent_company($data,$limit){
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
		return $this->dbx->get_where('refagentcompany',array('region_id'=>$region,'penagihan'=>'NO'))->result();
	}
	
	function getDataagent_penagihan($data,$limit){
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
		return $this->dbx->get_where('refagentcompany',array('region_id'=>$region,'penagihan'=>'YES'))->result();
	}

	function manage_data($data){
		$oper = $data['oper'];
		$id = $data['id'];
		$data['agent_company_id'] = $id;
		$data['region_id']= $this->session->userdata('region_id');
		
		unset($data['oper']);
		unset($data['id']);
		unset($data['agent_company_id']);
		
		if($oper=='add'||$oper=='edit') {
			$data['agent_company_code'] = strtoupper($data['agent_company_code']);
			$data['agent_company_name'] = strtoupper($data['agent_company_name']);
			$data['agent_company_city'] = strtoupper($data['agent_company_city']);
			$data['agent_company_address'] = ucfirst($data['agent_company_address']);
			$data['agent_company_npwp'] = strtoupper($data['agent_company_npwp']);
		}
		
		if($oper=='add') {
			
			$this->dbx->insert('refagentcompany',$data);
		} 
		elseif($oper=='edit') {
			$this->dbx->update('refagentcompany',$data,array('agent_company_id'=>$id));
		}
		else {
			$this->dbx->delete('refagentcompany',array('agent_company_id'=>$id));
		}
		return array('status'=>$this->dbx->affected_rows(),'oper'=>$oper);
	}
	
	/*
	function manage_data_penagihan($data){
		$oper = $data['oper'];
		$id = $data['id'];
		$data['agent_company_id'] = $id;
		$data['region_id']= $this->session->userdata('region_id');
		
		unset($data['oper']);
		unset($data['id']);
		unset($data['agent_company_id']);
		
		if($oper=='add'||$oper=='edit') {
			$data['agent_company_code'] = strtoupper($data['agent_company_code']);
			$data['agent_company_name'] = strtoupper($data['agent_company_name']);
			$data['agent_company_city'] = strtoupper($data['agent_company_city']);
			$data['agent_company_address'] = ucfirst($data['agent_company_address']);
			$data['agent_company_npwp'] = strtoupper($data['agent_company_npwp']);
		}
		
		if($oper=='add') {
			
			$this->dbx->insert('refagentpenagihan',$data);
		} 
		elseif($oper=='edit') {
			$this->dbx->update('refagentpenagihan',$data,array('agent_company_id'=>$id));
		}
		else {
			$this->dbx->delete('refagentpenagihan',array('agent_company_id'=>$id));
		}
		return array('status'=>$this->dbx->affected_rows(),'oper'=>$oper);
	}
	*/
	
	function getListAgent(){
		$this->dbx->select ('agent_company_id,agent_company_code ',FALSE);
		$this->dbx->order_by('agent_company_code','ASC');
		$this->dbx->where ('region_id',$this->session->userdata("region_id"));
		return $this->dbx->get('refagentcompany');
	}
	
	function getListAgentPenagihan(){
		$this->dbx->select ('agent_company_id,agent_company_code ',FALSE);
		$this->dbx->order_by('agent_company_code','ASC');
		$this->dbx->where ('region_id',$this->session->userdata("region_id"));
		return $this->dbx->get('refagentpenagihan');
	}
	
	function getAgentById($id)	{
		$q1 = $this->dbx->get_where('refagentcompany',array('agent_company_id'=>$id));
		if($q1->num_rows() > 0) {
			$q2 = $q1->row();
			return $q2->agent_company_code;
		} else {
			return"";
		}
	}
	
	function getAgentByCode($code)	{
		$q = $this->dbx->get_where('refagentcompany',array('agent_company_code'=>$code));
		if($q->num_rows() > 0) {
			return $q->row_array();
			} else {
			return"0";
		}
	}
	
	function getAgentCompanyByCode($code)	{
		$q = $this->dbx->get_where('refagentcompany',array('agent_company_code'=>$code));
		if($q->num_rows() > 0) {
			$q = $q->row();
			return $q->agent_company_name;
			} else {
			return"0";
		}
	}
}	


