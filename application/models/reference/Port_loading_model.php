<?php
class Port_loading_model extends CI_Model
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
	
	function getDatarefportloading($data,$limit)
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
		$q = $this->dbx->get("refportloading")->result();
		return $q;
	}
	
	function insert_port_loading($wh_port, $wh_city, $wh_remark)
	{
		$this->dbx->insert('refportloading', array('portloading_port'=>strtoupper($wh_port), 'portloading_city'=>ucwords($wh_city), 'portloading_remark'=>$wh_remark));
	}
	
	function update_port_loading($wh_port, $wh_city, $wh_remark, $id)
	{
		$this->dbx->where('portloading_id',$id);
		$this->dbx->update('refportloading', array('portloading_port'=>strtoupper($wh_port), 'portloading_city'=>ucwords($wh_city), 'portloading_remark'=>$wh_remark));
	}
	
	function delete_port_loading($id)
	{
		$this->dbx->where('portloading_id',$id);
		$this->dbx->delete('refportloading');
	}
	
}	
