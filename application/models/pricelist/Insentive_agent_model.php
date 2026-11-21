<?php
class Insentive_agent_model extends CI_Model {
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
	
	function get_first_id() 
	{
		$region = $this->session->userdata('region_id');
		$this->dbx->select_min('id');
		return $this->dbx->get_where('x_insentive', array('region_id'=>$region))->row()->id;	
	}
	
	function get_last_id() 
	{
		$region = $this->session->userdata('region_id');
		$this->dbx->select_max('id');
		return $this->dbx->get_where('x_insentive', array('region_id'=>$region))->row()->id;	
	}
	
	function table_detail($data,$limit)
	{
		if($limit==true) {
			$this->dbx->limit($data['rows'],$data['start']);
		}
		$query = $this->dbx->get_where('x_insentive_detail',array('id_insentive'=>$data['id_insentive']));
		return $query->result();
	}
	
	function table_browse($data,$limit)
	{
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
		$query = $this->dbx->get_where('x_insentive',array('region_id'=>$region));
		return $query->result();
	}
	
	function get_insentive($data)
	{
		$region = $this->session->userdata('region_id');
		if($data['mode']=='last'){
			$this->dbx->order_by('id','desc');
			$this->dbx->limit(1);
			$q = $this->dbx->get_where('x_insentive',array('region_id'=>$region));
			return $q;
		}
		
		if($data['mode']=='prev'){
			$this->dbx->order_by('id','desc');
			$this->dbx->limit(1);
			$q = $this->dbx->get_where('x_insentive',array('region_id'=>$region,'id <'=>$data['idInsentive']));
			return $q;
		}
		
		if($data['mode']=='next'){
			$this->dbx->order_by('id','asc');
			$this->dbx->limit(1);
			$q = $this->dbx->get_where('x_insentive',array('region_id'=>$region,'id >'=>$data['idInsentive']));
			return $q;
		}
		
		if($data['mode']=='pilih'){
			$q = $this->dbx->get_where('x_insentive',array('region_id'=>$region,'id'=>$data['idInsentive']));
			return $q;
		}
	}
	
	function manage_data($data)
	{
		$region = $this->session->userdata('region_id');
		$oper = $data['oper'];
		unset($data['oper']);
		
		if($oper=='add') {
			$q = $this->dbx->get_where('x_insentive',array('region_id'=>$region,'agent_code'=>$data['agent_code']));
			if($q->num_rows() > 0 ) 
			{
				return array('status'=>'0','mode'=>$oper, 'messages'=>'Data Agent code '.$data['agent_code'].' allrady exist');
			} 
			else 
			{
				$data['date']= $this->fungsi->jin_date_sql($data['date']);
				$data['region_id']= $region;
				$this->dbx->insert('x_insentive',$data);
				return array('status'=>$this->dbx->affected_rows(),'mode'=>$oper, 'messages'=>'Insert data success');
			}
		} 
		else if($oper=='edit') 
		{
			$data['date']= $this->fungsi->jin_date_sql($data['date']);
			$this->dbx->update('x_insentive',$data,array('id'=>$data['id']));
			return array('status'=>$this->dbx->affected_rows(),'mode'=>$oper, 'messages'=>'Edit data success');
		} 
		else if($oper=='edit_rows') 
		{
			if($data['kolom']=='date') { $data['isi']=$this->fungsi->jin_date_sql($data['isi']);}
			$this->dbx->update('x_insentive',array($data['kolom']=>$data['isi']), array('id'=>$data['id']));
			return array('status'=>$this->dbx->affected_rows(),'mode'=>$oper, 'messages'=>'Edit Row data success');
		}
		else 
		{
			$this->dbx->delete('x_insentive',array('id'=>$data['id']));
			return array('status'=>$this->dbx->affected_rows(),'mode'=>$oper, 'messages'=>'Delete data success');
		}
	}
	
	function manage_detail($data){
		$oper = $data['oper'];
		if($oper=='add') {
			unset($data['oper']);
			$this->dbx->insert('x_insentive_detail',$data);
		}
		if($oper=='edit') {
			$this->dbx->where('id',$data['id']);
			$this->dbx->update('x_insentive_detail',array($data['kolom']=>$data['isi']));
		}
		if($oper=='del') {
			$this->dbx->where('id',$data['id']);
			$this->dbx->delete('x_insentive_detail');	
		}
		return array('status'=>$this->dbx->affected_rows(),'mode'=>$oper);
	}
}


