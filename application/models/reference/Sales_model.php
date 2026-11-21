<?php
class Sales_model extends CI_Model
{
  public $dbx;

    public function __construct() {
        parent::__construct();

        $tahun = $this->session->userdata('db');

        if ($tahun == '' || !is_numeric($tahun)) {
            $this->dbx = $this->load->database('master', TRUE);
        } else {
            $config = switch_db($tahun);            // ambil array config
            $this->dbx = $this->load->database($config, TRUE); // load pakai array
        }
    }
	
	function getDatarefsales($data,$limit){
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
		$this->dbx->join('mstadmin','mstadmin.user_id=refsales.user_id','left');
		$q = $this->dbx->get_where('refsales',array('mstadmin.region_id'=>$this->session->userdata('region_id')));
		return $q->result();
		
	}
	
	function manage_data($data){
		$oper = $data['oper'];
		if($oper== 'add' ) {
			unset($data['oper']);
			$this->dbx->insert('refsales',$data);
		}
		if($oper== 'edit' ) {
			unset($data['oper']);
			$this->dbx->where('id',$data['id']);
			$this->dbx->update('refsales',$data);
		}
		if($oper== 'del' ) {
			unset($data['oper']);
			$this->dbx->where('id',$data['id']);
			$this->dbx->delete('refsales');
		}
		return array('status'=>$this->dbx->affected_rows(),'mode'=>$oper);
	}
}	
