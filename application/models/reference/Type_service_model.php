<?php
class Type_service_model extends CI_Model
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
	
	function get_item_service_nya($data){
		$this->dbx->select ('typeservice_name');
		$q = $this->dbx->get_where('reftypeservice',$data)->row();
		return $q;
		
		/*
		if($data["typeservice_code"]!="") {
			
			 array('typeservice_name'=>$q->row()->typeservice_name);
		} else {
			array('typeservice_name'=>"");		
		}
		*/
	}
	
	function getListService(){
		$this->dbx->select ('*');
		$this->dbx->order_by('typeservice_code','asc');
		return $this->dbx->get('reftypeservice');
	}
	
	function getDatareftypeservice($data,$limit){
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
		$q = $this->dbx->get("reftypeservice")->result();
		return $q;
	}
	
	function getDataItemService($code){
		$q = $this->dbx->get_where('reftypeservice',array('typeservice_code'=>$code));
		$row = $q->row();
		return $row->typeservice_name;
	}
	
	function getDataCodeService($item){
		static $cache = array();

		$key = (string) $item;
		if (array_key_exists($key, $cache)) {
			return $cache[$key];
		}

		$q = $this->dbx->select('typeservice_code')
			->get_where('reftypeservice', array('typeservice_name' => $item));

		$cache[$key] = ($q->num_rows() > 0) ? $q->row()->typeservice_code : null;
		return $cache[$key];
	}

	/**
	 * Ambil seluruh mapping service sekali untuk kebutuhan report/PDF.
	 * Menghindari query per baris charge (N+1 query).
	 */
	function getServiceCodeMap(){
		$rows = $this->dbx
			->select('typeservice_name, typeservice_code')
			->get('reftypeservice')
			->result();

		$map = array();
		foreach ($rows as $row) {
			$map[strtoupper(trim((string) $row->typeservice_name))] = $row->typeservice_code;
		}

		return $map;
	}
	
	function insert_type_service($wh_code, $wh_name, $wh_ppn){
		$this->dbx->insert('reftypeservice', array('typeservice_code'=>strtoupper($wh_code), 'typeservice_name'=>ucwords($wh_name), 'typeservice_ppn'=>$wh_ppn));
	}
	
	function update_type_service($wh_code, $wh_name, $wh_ppn, $id){
		$this->dbx->where('typeservice_id',$id);
		$this->dbx->update('reftypeservice', array('typeservice_code'=>strtoupper($wh_code), 'typeservice_name'=>ucwords($wh_name), 'typeservice_ppn'=>$wh_ppn));
	}
	
	function delete_type_service($id){
		$this->dbx->where('typeservice_id',$id);
		$this->dbx->delete('reftypeservice');
	}
	
	public function search_service($term) {
        $this->db->select('typeservice_id, typeservice_code, typeservice_name, typeservice_ppn');
        $this->db->from('reftypeservice');
        $this->db->like('typeservice_name', $term);
        $this->db->or_like('typeservice_code', $term);
        $query = $this->db->get();

        return $query->result_array();
    }
}	
