<?php
class City_model extends CI_Model
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
	
	function manage_data($data)
	{
		$oper=$data['oper'];
		$id_city=$data['id'];
		
		unset($data['oper']);
		unset($data['id']);
		
		unset($data['buttonCountry']);
		$data['city_remark']=$data['remarks'];
		unset($data['remarks']);
		
		if($oper=='add') 
		{
			$data['city_id']=strtoupper($data['city_id']);
			$data['city_name']=strtoupper($data['city_name']);
			$data['origin_id']=strtoupper($data['origin_id']);
			$data['city_remark']=strtoupper($data['city_remark']);	
			$data['country_id']=strtoupper($data['country_id']);
			
			$this->dbx->insert('mstcities',$data);
			
			if($this->dbx->affected_rows()>0) 
			{
				$this->dbx->insert('refportdestination', array(
					'portdestination_code'=>$data['city_id'],
					'portdestination_name'=>$data['city_name'],
					'portdestination_country'=>$data['country_id'],
					'origin_id'=>$data['origin_id'],
					'portdestination_remark'=>$data['city_remark'],
				));
			}
		}
		else if($oper=='edit') 
		{
			$data['city_id']=strtoupper($data['city_id']);
			$data['city_name']=strtoupper($data['city_name']);
			$data['city_remark']=strtoupper($data['city_remark']);
			$data['country_id']=strtoupper($data['country_id']);
			$data['origin_id']=strtoupper($data['origin_id']);
			$this->dbx->update('mstcities',$data,array('id_city'=>$id_city));
			
			if($this->dbx->affected_rows()>0) 
			{
				$this->dbx->update('refportdestination', 
					array(
						'portdestination_code'=>$data['city_id'],
						'portdestination_name'=>$data['city_name'],
						'portdestination_country'=>$data['country_id'],
						'origin_id'=>$data['origin_id'],
						'portdestination_remark'=>$data['city_remark'],
					),
					array('portdestination_code'=>$data['city_id'],'portdestination_country'=>$data['country_id'])
				);
			}
		}
		else 
		{
			$get = $this->dbx->get_where('mstcities',array('id_city'=>$id_city));
			if($get->num_rows()>0) 
			{
				$code = $get->row()->city_code;
				$country = $get->row()->country_id;
				$this->dbx->delete('refportdestination', array('portdestination_code'=>$code,'portdestination_country'=>$country));
			}
			$this->dbx->delete('mstcities',array('id_city'=>$id_city));
		}
		
		return array('status'=>$this->dbx->affected_rows(),'oper'=>$oper);
	}
	
	function getDataCity($data,$limit)
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
		$q = $this->dbx->get("mstcities")->result();
		return $q;
	}
	
	function insert_city($data,$data2)
	{
		$this->dbx->insert('mstcities',$data);
		$this->dbx->insert('refportdestination',$data2);
		if($this->dbx->affected_rows() > 0) {
			return '1';
		} else {
			return '0';	
		}
	}
	
	function update_city($data,$data2, $id)
	{
		$this->dbx->where('id_city',$id);
		$this->dbx->update('mstcities',$data);
		$this->dbx->update('refportdestination',$data2);
		if($this->dbx->affected_rows() > 0) {
			return '1';
		} else {
			return '0';	
		}
	}
	
	function delete_city($id)
	{
		$this->dbx->where('id_city',$id);
		$this->dbx->delete('mstcities');
		if($this->dbx->affected_rows() > 0) {
			$this->dbx->where('portdestination_id',$id);
			$this->dbx->delete('refportdestination');
			return '1';
		} else {
			return '0';	
		}
	}

}	
