<?php
class Shipping_company_model extends CI_Model
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
	
	function getDatarefshippingcompany($data,$limit){
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
		return $this->dbx->get_where('refshippingcompany',array('region_id'=>$region))->result();
	}
	
	function manage_data($data){
		$oper = $data['oper'];
		$id = $data['id'];
		$data['shipping_company_id'] = $id;
		$data['region_id']= $this->session->userdata('region_id');
		
		unset($data['oper']);
		unset($data['id']);
		unset($data['shipping_company_id']);
		
		if($oper=='add'||$oper=='edit') {
			$data['shipping_company_code'] = strtoupper($data['shipping_company_code']);
			$data['shipping_company_name'] = strtoupper($data['shipping_company_name']);
			$data['shipping_company_address'] = ucfirst($data['shipping_company_address']);
			$data['shipping_company_npwp'] = strtoupper($data['shipping_company_npwp']);
		}
		
		if($oper=='add') {
			
			$this->dbx->insert('refshippingcompany',$data);
		} 
		elseif($oper=='edit') {
			$this->dbx->update('refshippingcompany',$data,array('shipping_company_id'=>$id));
		}
		else {
			$this->dbx->delete('refshippingcompany',array('shipping_company_id'=>$id));
		}
		return array('status'=>$this->dbx->affected_rows(),'oper'=>$oper);
	}
	
	function getListCompany($searchTerm){
		$this->dbx->select ('shipping_company_code,shipping_company_name ',FALSE);
		$this->dbx->like ('shipping_company_code',$searchTerm);
		$this->dbx->where ('region_id',$this->session->userdata("region_id"));
		$this->dbx->order_by('shipping_company_code','ASC');
		return $this->dbx->get('refshippingcompany');
	}
	
	function getListColoader($searchTerm = null){
    $this->dbx->select('shipping_company_code, shipping_company_name', FALSE);
    if ($searchTerm) {
        $this->dbx->like('shipping_company_code', $searchTerm);
    }
    $this->dbx->where('region_id', $this->session->userdata("region_id"));
    $this->dbx->order_by('shipping_company_code', 'ASC');
    return $this->dbx->get('refshippingcompany')->result();
}

	
	function getListScac($searchTerm){
		$this->dbx->select ('scac',FALSE);
		$this->dbx->like ('scac',$searchTerm);
		$this->dbx->order_by('scac','ASC');
		return $this->dbx->get('ref_scac');
	}
}	
