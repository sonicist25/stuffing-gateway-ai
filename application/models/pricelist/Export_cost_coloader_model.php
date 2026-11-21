<?php
class  Export_cost_coloader_model extends CI_Model
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
	
	function manage_detail($data)
	{
		$oper = $data['oper'];
		$id=$data['id'];
		unset($data['oper']);
		unset($data['id']);
		if($oper!='del') 
		{
			$data['price']=str_replace(" ","",$data['price']);
		}
		
		if($oper=='add') 
		{
			$this->dbx->insert('cost_coloader_export_detail',$data);	
		}
		elseif($oper=='edit') 
		{
			$this->dbx->update('cost_coloader_export_detail',$data,array('id'=>$id));	
		}
		else 
		{
			$this->dbx->delete('cost_coloader_export_detail',array('id'=>$id));
		}
		return array('satus'=>$this->dbx->affected_rows(),'oper'=>$oper);
	}
	
	function table_detail($data,$limit)
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
		$q = $this->dbx->get_where("cost_coloader_export_detail",array('id_data'=>$data['id_data']));
		return $q->result();
	}
	
	function pilih_id($data)
	{
		$select = "select_".$data[0];
		$this->dbx->$select('id');
		$q = $this->dbx->get_where("cost_coloader_export",array('shipping_co'=>$data[1]));
		return $q->row()->id;
	}
	
	function manage_header($data)
	{
		$oper = $data['oper'];
		$id=$data['id'];
		unset($data['oper']);
		unset($data['id']);
		if($oper!='del') 
		{
			$data['date']=$this->fungsi->jin_date_sql($data['date']);
		}
		
		if($oper=='add') 
		{
			$this->dbx->insert('cost_coloader_export',$data);	
		}
		elseif($oper=='edit') 
		{
			$this->dbx->update('cost_coloader_export',$data,array('id'=>$id));	
		}
		else 
		{
			$this->dbx->delete('cost_coloader_export',array('id'=>$id));
			if($this->dbx->affected_rows()>0) {
				$this->dbx->delete('cost_coloader_export_detail',array('id_data'=>$id));
			}
		}
		return array('satus'=>$this->dbx->affected_rows(),'oper'=>$oper);
	}
	
	function new_data($data)
	{
		$q = $this->dbx->get_where("cost_coloader_export",array('shipping_co'=>$data['shipping_co']));
		$ref_no = date("y") ."-". date("m") ."-". str_pad($q->num_rows()+1, 4, "0", STR_PAD_LEFT);
		return array('ref_no'=>$ref_no );
	}
	
	function last($data)
	{
		$this->dbx->order_by('id','desc');
		$this->dbx->limit(1);
		$this->dbx->where('shipping_co',$data['shipping_co']);
		$q = $this->dbx->get('cost_coloader_export');
		if($q->num_rows()>0) 
		{
			return $q->row();
		} 
		else 
		{
			return false; 
		}
	}
	
	function pilih($data)
	{
		$q = $this->dbx->get_where('cost_coloader_export',array('id'=>$data['id'],'shipping_co'=>$data['shipping_co']));
		if($q->num_rows()>0) 
		{
			return $q->row();
		} 
		else 
		{
			return false; 
		}
	}
	
	function next($data)
	{
		$this->dbx->limit(1);
		$this->dbx->order_by('id','asc');
		$q = $this->dbx->get_where('cost_coloader_export',array('id >'=>$data['id'],'shipping_co'=>$data['shipping_co']));
		if($q->num_rows()>0) 
		{
			return $q->row();
		} 
		else 
		{
			return false; 
		}
	}
	
	function prev($data)
	{
		$this->dbx->limit(1);
		$this->dbx->order_by('id','desc');
		$q = $this->dbx->get_where('cost_coloader_export',array('id <'=>$data['id'],'shipping_co'=>$data['shipping_co']));
		if($q->num_rows()>0) 
		{
			return $q->row();
		} 
		else 
		{
			return false; 
		}
	}

	function get_warehouse()
	{
		$q = $this->dbx->query("select * from refwarehouse");
		return $q;
	}
}


