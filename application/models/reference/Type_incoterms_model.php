<?php
class Type_incoterms_model extends CI_Model
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
	
	function getListincoterms(){
		$this->dbx->select ('*');
		return $this->dbx->get('reftypeincoterms');
	}
	
	function getDatarefincoterms($data,$limit){
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
		$q = $this->dbx->get("reftypeincoterms")->result();
		return $q;
	}
	
	function insert_incoterms($term,$term_code){
		$this->dbx->insert('reftypeincoterms', array('incoterms_code'=>strtoupper($term_code),'incoterms_name'=>strtoupper($term)));
	}
	
	function update_incoterms($term,$term_code, $id){
		$this->dbx->where('incoterms_code', $term_code);
		$this->dbx->update('reftypeincoterms', array('incoterms_code'=>strtoupper($term_code),'incoterms_name'=>strtoupper($term)));
	}
	
	function delete_incoterms($id){
		$this->dbx->where('id',$id);
		$this->dbx->delete('reftypeincoterms');
	}
	
}	
