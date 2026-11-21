<?php
class Afg_m_tracking_export extends CI_Model
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
   
	
	function tampil_semua_tracking_export($data,$limit){
		/*
		$q = $this->dbx->query("select * from x_tracking");
		return $q;
		*/
		
		$region = $this->session->userdata('region_id');
		$session= $this->session->userdata('user_id');
		
		//$this->dbx->select('sp_id,kode_ref,closing_date_time,etd_jkt,vessel,voyage,port_loading,transhipment_port,destination,prepared',false);
		
		if($data['_search']=='true') {
			$searchField = $data['searchField'];
			$searchString = $data['searchString'];
      	$searchOper  =  $data['searchOper'];
			
			$ops = array(
            	'eq'=>'=', //equal
            	'cn'=>'LIKE', // contains
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
		
		return $this->dbx->get('x_tracking_export');
	}
	
	function add($data){
		$data['region_id']=$this->session->userdata('region_id');
		$q = $this->dbx->get_where('x_tracking_export',array('hbl_no'=>$data['hbl_no']));
		if($q->num_rows() > 0) {
			$this->dbx->where('hbl_no',$data['hbl_no']);
			$this->dbx->update('x_tracking_export',$data);
		} else {
			$this->dbx->insert('x_tracking_export',$data);
		}
		if($this->dbx->affected_rows() > 0) {
			return '1';
		} else {
			return '0';	
		}
	}
	
	function update($data,$id){
		$this->dbx->where('id_tracking',$id);
		$this->dbx->update('x_tracking_export',$data);
		if($this->dbx->affected_rows() > 0) {
			return '1';
		} else {
			return '0';	
		}
	}
	
	function delete($id){
		$this->dbx->where('id_tracking',$id);
		$this->dbx->delete('x_tracking_export');
		if($this->dbx->affected_rows() > 0) {
			return '1';
		} else {
			return '0';	
		}
	}
}
?>