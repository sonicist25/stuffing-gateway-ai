<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Mfg_user extends CI_Model { 

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
   
   function get_all_shipper() {
   	return $this->dbx->get_where('mstshipper',array('region_id'=>$this->session->userdata('region_id')));
   }
   
   function update_menu_user($data) {
   	$menu = $data['user_menu'];
		if($menu!='') {
			$menu = implode('+',$menu);
		}
		$this->dbx->where('user_id',$data['user_id']);
		$this->dbx->update('mstadmin',array('user_menu'=>'+'.$menu.'+'));
		if($this->dbx->affected_rows() > 0){
			return(array('status'=>'1', 'message'=>'Data menu akses user updated'));
		} else {
			return(array('status'=>'0', 'message'=>'Failed updated menu user'));
		}
   }
   
   function get_checked_menu($data){
  		$this->dbx->select('user_id,user_menu');
   	$this->dbx->from('mstadmin');
   	$this->dbx->where('user_id',$data['user_id']);
   	$this->dbx->like('user_menu','+'.$data['menu_id'].'+');
   	$query = $this->dbx->get();
   	if($query->num_rows()==1) {
   			return 'checked="checked"';
   	} else {
   			return '';
   	}
   }
   
   function getUserById($shipper_id)	{
		$query = $this->dbx->get_where('mstshipper',array('shipper_id' => $shipper_id));
		if($query->num_rows() > 0 ) {
			return $query->row();
		} else {
			$data = new stdClass();	 
			$data->shipper_name = '';
			$data->shipper_address = '';
			return $data;
		}
	}
	
	function get_consignee_id($consignee_id)	{
		$query = $this->dbx->get_where('refconsigneenotify',array('consignee_notify_code' => $consignee_id));
		return $query->row();
	}
	
	function get_data_sales(){
		/*
		$region = $this->session->userdata('region_id');
		$this->dbx->select('user_id,level,fullname,region_id');
		$query1 = $this->dbx->get_where('mstadmin',array('region_id'=>$region, 'level'=>1))->result();
		$this->dbx->select('user_id,level,fullname,region_id');
		$query2 = $this->dbx->get_where('mstadmin',array('region_id'=>$region, 'level'=>2))->result();
		$data = array_merge($query1,$query2);
		return $data;
		*/
		$this->dbx->join('mstadmin','mstadmin.user_id=refsales.user_id','left');
		$q = $this->dbx->get_where('refsales',array('mstadmin.region_id'=>$this->session->userdata('region_id')));
		return $q;
	}
	
	function get_data_shipper(){
		$this->dbx->order_by('shipper_id','asc');
		$q = $this->dbx->get_where('mstshipper',array('region_id'=>$this->session->userdata('region_id')));
		return $q;
	}
	
	function insertDataUserBooking($data)	{
	//	$this->slave = $this->load->database('slave', TRUE);
		$data['password']= md5($data['password']);
		$q = $this->dbx->insert('mstshipper',$data);
		return $this->dbx->affected_rows();
	}
	
	function updateDataUserBooking($data)	{
		$data['password']= md5($data['password']);
		$this->dbx->where('id_shipper', $data['id_shipper']);
		$this->dbx->update('mstshipper',$data);
		return $this->dbx->affected_rows();
	}
	
	function deleteDataUserBooking($data)	{
		$this->dbx->delete('mstshipper', array('id_shipper'=>$data['id_shipper']));
		return $this->dbx->affected_rows();
	}
	
	function getUserBooking($id)	{
		$query = $this->dbx->get_where('mstshipper',array('id_shipper' => $id));
		return $query->row();
	}
	
	function getAktivasiUser($id)	{
		$query = $this->dbx->get_where('mstshipper',array('id_shipper' => $id));
		$data = $query->row();
		return $data->isActive;		
	}
	
	function getAllUser($data,$limit){
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
		
		$region = $this->session->userdata('region_id');
		
		return $this->dbx->get_where('mstshipper',array('region_id'=>$region))->result();
		
		/*
		return $this->dbx->query("select * from mstshipper where region_id='$region'")->result();
		*/
	}
	
	function getAllUserAdmin($data,$limit){
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
		
		$region = $this->session->userdata('region_id');
		$level = $this->session->userdata('level_id');
		$user_id = $this->session->userdata('user_id');
		
		if($level == 0 || $level == 1){
			$q = $this->dbx->get_where('mstadmin', array('username !='=>'root','region_id'=>$region));
		} else {
			$q = $this->dbx->get_where('mstadmin', array('username !='=>'root','user_id'=>$user_id));
		}
	
		return $q->result();		
	}
	
	function getUserAdmin($username)	{
		$q = $this->dbx->get_where('mstadmin', array('username'=>$username));
		return $q;
	}
	
	function insertDataUserAdmin($data)	{
		$this->dbx->insert('mstadmin',$data);
		if($this->dbx->affected_rows() > 0 ) {
			return "1";
			} else {
			return "0";	
		}
	}
	
	function updateDataUserAdmin($id,$data)	{
		$this->dbx->where('user_id', $id);
		$this->dbx->update('mstadmin',$data);
		if($this->dbx->affected_rows() > 0 ) {
			return "1";
			} else {
			return "0";	
		}
	}
	
	function deleteDataUserAdmin($id)	{
		$this->dbx->where('user_id', $id);
		$this->dbx->delete('mstadmin');
		if($this->dbx->affected_rows() > 0 ) {
			return "1";
			} else {
			return "0";	
		}
	}
	
	function getListRegion()	{
		$query = $this->dbx->get('refregion');
		return $query->result();
	}
	
	function get_shipper_by_id($id)	{
		$query = $this->dbx->get_where('mstshipper',array('shipper_id'=>$id));
		return $query->row();
	}
	
	function get_shipper_by_id_shipper($id,$id_print)	
	{
		if($id!='') 
		{
			$query = $this->dbx->get_where('mstshipper',array('id_shipper'=>$id));
			if($query->num_rows()>0) 
			{
				return $query->row();
			}
			else 
			{
				$data = new stdClass();
				$data->shipper_name = '';
				return $data;
			}
		}
		else 
		{
			$query = $this->dbx->get_where('mstshipper',array('shipper_id'=>$id_print));
			if($query->num_rows()>0) 
			{
				return $query->row();
			}
			else 
			{
				$data = new stdClass();
				$data->shipper_name = '';
				return $data;
			}
		}
	}
	
	function get_user_by_id($id)	{
		$q  = $this->dbx->get_where('mstadmin',array('user_id'=>$id));
		$q2 = $q->row_array();
		return $q2['fullname'];
	}
	
	function check_username($user) {
		return $this->dbx->get_where('mstadmin',array('username'=>$user))->num_rows();
	}

}

/*
	function getDataUserBooking($start,$limit,$sidx,$sord,$where)	{
		$this->dbx->select('*');
    	$this->dbx->limit($limit);
    	if($where != NULL)$this->dbx->where($where,NULL,FALSE);
    	$query = $this->dbx->get('mstshipper',$limit,$start);		
		return $query;
	}


		$user_id = $this->session->userdata('user_id');
		if($this->session->userdata('level_id')== 0 ) {
			$q = $this->dbx->get_where('mstadmin', array('username !='=>'root'));
		}
		else if($this->session->userdata('level_id')== 1 ) {
			$region = $this->session->userdata('region_id');
			$q = $this->dbx->get_where('mstadmin', array('username !='=>'root','region_id'=>$region));
		}
		else {
			$q = $this->dbx->get_where('mstadmin', array('username !='=>'root','user_id'=>$user_id));
		}
		return $q;
*/	
	