<?php
class  Export_cost_price_agent_model extends CI_Model
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
	
	function pilih_id($data)
	{
		$select = "select_".$data[0];
		$this->dbx->$select('id');
		$q = $this->dbx->get_where("cost_price_agent_export",array('agent'=>$data[1]));
		return $q->row()->id;
	}
	
	function new_data($data)
	{
		$q = $this->dbx->get_where("cost_price_agent_export",array('agent'=>$data['agent']));
		$ref_no = date("y") ."-". date("m") ."-". str_pad($q->num_rows()+1, 4, "0", STR_PAD_LEFT);
		return array('ref_no'=>$ref_no );
	}
	
	function manage_table_data($data)
	{
		$oper = $data['oper'];
		$id=$data['id'];
		unset($data['oper']);
		unset($data['id']);
		
		if($oper=='add') 
		{
			$this->dbx->insert('cost_price_agent_export_detail',$data);	
		}
		elseif($oper=='edit') 
		{
			$this->dbx->update('cost_price_agent_export_detail',$data,array('id'=>$id));	
		}
		else 
		{
			$this->dbx->delete('cost_price_agent_export_detail',array('id'=>$id));
		}
		
		return array('satus'=>$this->dbx->affected_rows(),'oper'=>$oper);
	}
	
	function table_listing($data,$limit)
	{
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
		$q = $this->dbx->get_where("cost_price_agent_export",array('agent'=>$data['agent']));
		return $q->result();
	}
	
	function get_table_data($data,$limit)
	{
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
		$q = $this->dbx->get_where("cost_price_agent_export_detail",array('id_data'=>$data['id_data']));
		return $q->result();
	}
	
	function manage_data_header($data)
	{
		$oper = $data['oper'];
		$id=$data['id'];
		unset($data['oper']);
		unset($data['id']);
		if($oper!='del') 
		{
			$data['date']=$this->fungsi->jin_date_sql($data['date']);
			$data['valid']=$this->fungsi->jin_date_sql($data['valid']);
		}
		
		if($oper=='add') 
		{
			$this->dbx->insert('cost_price_agent_export',$data);	
		}
		elseif($oper=='edit') 
		{
			$this->dbx->update('cost_price_agent_export',$data,array('id'=>$id));	
		}
		else 
		{
			$this->dbx->delete('cost_price_agent_export',array('id'=>$id));
			if($this->dbx->affected_rows()>0) {
				$this->dbx->delete('cost_price_agent_export_detail',array('id_data'=>$id));
			}
		}
		
		return array('satus'=>$this->dbx->affected_rows(),'oper'=>$oper);
	}
	
	function get_agent(){
		$q = $this->dbx->query("select * from refagentcompany");
		return $q;
	}
	
	function lastData($data)
	{
		$this->dbx->order_by('id','desc');
		$this->dbx->limit(1);
		$this->dbx->where('agent',$data['agent']);
		$q = $this->dbx->get('cost_price_agent_export');
		if($q->num_rows()>0) 
		{
			return $q->row();
		} 
		else 
		{
			return false; 
		}
	}
	
	function pilihData($data)
	{
		$q = $this->dbx->get_where('cost_price_agent_export',array('id'=>$data['id'],'agent'=>$data['agent']));
		if($q->num_rows()>0) 
		{
			return $q->row();
		} 
		else 
		{
			return false; 
		}
	}
	
	function nextData($data)
	{
		$this->dbx->limit(1);
		$this->dbx->order_by('id','asc');
		$q = $this->dbx->get_where('cost_price_agent_export',array('id >'=>$data['id'],'agent'=>$data['agent']));
		if($q->num_rows()>0) 
		{
			return $q->row();
		} 
		else 
		{
			return false; 
		}
	}
	
	function prevData($data)
	{
		$this->dbx->limit(1);
		$this->dbx->order_by('id','desc');
		$q = $this->dbx->get_where('cost_price_agent_export',array('id <'=>$data['id'],'agent'=>$data['agent']));
		if($q->num_rows()>0) 
		{
			return $q->row();
		} 
		else 
		{
			return false; 
		}
	}
}


