<?php
class Import_selling_rate_model extends CI_Model
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
	
	function save_data_detail($data)
	{
		$this->dbx->update("ref_import_detail",
			$data['myData'],
			array('id'=>$data['id'])
		);
		return array("status"=>$this->dbx->affected_rows());
		
	}
	
	function insert_new_data($data)
	{
		$this->dbx->insert("ref_import_detail",$data);
		return array("status"=>$this->dbx->affected_rows());
	}
	
	function get_data($data,$limit){
		$sales = $this->session->userdata('user_id');
		$level = $this->session->userdata('level_id');	
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
		if($level==2) {	
			$this->dbx->where('find_in_set("'.$sales.'", sales) <> 0');;
			$this->dbx->from('mstshipper');
			$q = $this->dbx->get();
		} else {
			$q = $this->dbx->get_where("mstshipper",array('region_id'=>$region));
		}
		return $q->result();
	}
	
	function get_data_form($data){
		$region_id = $this->session->userdata("region_id");
		if($data['mode']=='last') {
			$this->dbx->order_by('id','desc');
			$this->dbx->limit(1);
			$query = $this->dbx->get_where("ref_import",array("region_id"=>$region_id,'consignee_id'=>$data['consignee_id']))->row();
		}
		if($data['mode']=='prev') {
			$this->dbx->order_by('id','desc');
			$this->dbx->limit(1);
			$query = $this->dbx->get_where("ref_import",array("region_id"=>$region_id,'consignee_id'=>$data['consignee_id'],'id <'=>$data['id']))->row();
		}
		
		if($data['mode']=='next') {
			$this->dbx->order_by('id','asc');
			$this->dbx->limit(1);
			$query = $this->dbx->get_where("ref_import",array("region_id"=>$region_id,'consignee_id'=>$data['consignee_id'],'id >'=>$data['id']))->row();
		}
		
		if($data['mode']=='pilih') {
			$query = $this->dbx->get_where("ref_import",array("region_id"=>$region_id,'consignee_id'=>$data['consignee_id'],'id'=>$data['id']))->row();
		}
		
		if($query) {
				$query->first_id = $this->get_first_id($_POST['consignee_id']);
				$query->last_id = $this->get_last_id($_POST['consignee_id']);
				$query->last_ref_no = $this->get_last_ref_no($_POST['consignee_id']);
				$query->date = $this->fungsi->jin_date_str($query->date);
				$query->valid = $this->fungsi->jin_date_str($query->valid);
			} else {
				$query = new stdClass();
				$query->consignee_id = $data['consignee_id'];
				$query->first_id = '';
				$query->last_id = '';
				$query->sales_id = '';
				$query->ref_no = '';
				$query->last_ref_no = '0';
				$query->id = '';
				$query->date = '';
				$query->valid = '';
				$query->region_id = $region_id;
		}
		return $query;
	}
	
	function get_first_id($id){
		$this->dbx->select_min('id');
		$q = $this->dbx->get_where('ref_import',array('consignee_id'=>$id));
		return $q->row()->id;
	}
	
	function get_last_id($id){
		$this->dbx->select_max('id');
		$q = $this->dbx->get_where('ref_import',array('consignee_id'=>$id));
		return $q->row()->id;
	}
	
	function get_last_ref_no($id){
		$this->dbx->select_max('ref_no');
		$q = $this->dbx->get_where('ref_import',array('consignee_id'=>$id));
		return $q->row()->ref_no;
	}
	
	function manage_data_form($data){
		$region_id = $this->session->userdata("region_id");
		if($data['oper']=='add'){
			unset($data['oper']);
			$data["date"]=$this->fungsi->jin_date_sql($data["date"]);
			$data["valid"]=$this->fungsi->jin_date_sql($data["valid"]);
			$this->dbx->insert('ref_import',$data);
		}
		else if($data['oper']=='edit'){
			unset($data['oper']);
			$data["date"]=$this->fungsi->jin_date_sql($data["date"]);
			$data["valid"]=$this->fungsi->jin_date_sql($data["valid"]);
			$this->dbx->where('id',$data['id']);
			$this->dbx->update('ref_import',$data);
		}
		elseif($data['oper']=='del'){
			unset($data['oper']);
			$this->dbx->where('id',$data['id']);
			$this->dbx->delete('ref_import');
			if($this->dbx->affected_rows() > 0 ) {
				$this->dbx->where('data_id',$data['id']);
				$this->dbx->delete('ref_import_detail');
			}
		}
		return $this->dbx->affected_rows();
	}

	function manage_data_detail($data){
		if($data['oper']=='add'){
			unset($data['oper']);
			$this->dbx->insert('ref_import_detail',array('data_id'=>$data['data_id'],$data['kolom']=>$data['isi']));
		}
		else if($data['oper']=='edit'){
			unset($data['oper']);
			$this->dbx->where('id',$data['id']);
			$this->dbx->update('ref_import_detail',array($data['kolom']=>$data['isi']));
		}
		elseif($data['oper']=='del'){
			$idx = explode(",",$data['id']);
			for($i=0;$i<=count($idx)-1;$i++) {
			$this->dbx->delete('ref_import_detail',array("id"=>$idx[$i]));
			}
		}
		return $this->dbx->affected_rows();
	}
	
 	function get_table_listing($data){
		$level = $this->session->userdata('level_id');	
		$sales = $this->session->userdata('user_id');
		if($level==2) {	
		$this->dbx->where('find_in_set("'.$sales.'", sales_id) <> 0');
		$this->dbx->where('consignee_id',$data['consignee_id']);
		$this->dbx->from('ref_import');
		$q = $this->dbx->get();
		return $q;
		}else{
 		return $this->dbx->get_where('ref_import',array('consignee_id'=>$data['consignee_id']));
		}
 	}
 	
 	function get_table_detail($data){
 		return $this->dbx->get_where('ref_import_detail',array('data_id'=>$data['id']));
 	}
 	
 	function get_consignee_name($id) {
 		return $this->dbx->get_where('mstshipper',array('id_shipper'=>$id))->row()->shipper_name;
 	}
}


