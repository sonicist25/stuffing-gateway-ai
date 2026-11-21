<?php
class Air_rate_model extends CI_Model
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
	
	function copy_data($data)
	{
		$q = $this->dbx->get_where("a_selling_air",array("id_selling"=>$data["id"]))->result_array();
		unset($q[0]["id_selling"]);
		$q[0]["ref_no"]= $this->get_ref_no($q[0]["kode_shipper"]);
		$query = $this->dbx->insert("a_selling_air",$q[0]);
		if($this->dbx->affected_rows()>0) 
		{
			$this->dbx->select_max("id_selling");
			$id = $this->dbx->get("a_selling_air")->row()->id_selling;
			$qr = $this->dbx->get_where("a_selling_air_rate",array("id_selling"=>$data["id"]))->result();
			foreach($qr as $row)
			{
				unset($row->id);
				$row->id_selling = $id;
				$d[]= $row;
			}
			
			foreach($d as $isi) {
				$this->dbx->insert("a_selling_air_rate",$isi);
			}
			
		}
		return array("status"=>$this->dbx->affected_rows(),"row"=>count($d));
	}
	
	function get_ref_no($shipper)
	{
		$this->dbx->select_max("ref_no");
		$q = $this->dbx->get_where("a_selling_air",array("kode_shipper"=>$shipper))->row()->ref_no;
		$refno = substr($q, 6, 4);
		$hasil=$refno+1;
		return date("y")."-".date("m")."-".str_pad($hasil, 4, "0", STR_PAD_LEFT);
	}
	
	function insert_data($data)
	{
		if ($data['of_emkl'] == 1){
			$tambah = array ('id_selling'=>$data['id_selling'],'of_emkl'=>$data['of_emkl'],'type'=>'SET','region_id'=>$this->session->userdata('region_id'));
			$this->dbx->insert("a_selling_air_rate", $tambah);
		}else{
			$tambah = array ('id_selling'=>$data['id_selling'],'of_emkl'=>$data['of_emkl'],'region_id'=>$this->session->userdata('region_id'));
			$this->dbx->insert("a_selling_air_rate", $tambah);
		}
		return $this->dbx->affected_rows();
	}
	
	function saving_data($data)
	{
		unset($data['myData']["space"]);
		$this->dbx->update("a_selling_air_rate",
			$data['myData'],
			array('id'=>$data['id'])
		);
		return array("status"=>$this->dbx->affected_rows());
	}
	
	function get_value_emkl($id)
	{
		return $this->dbx->get_where('a_selling_air',array('id_selling'=>$id))->row()->of_emkl;
	}
	
	function print_data($data){
		$this->dbx->select_max('a_selling_air.tanggal');
		$refno = $this->dbx->get_where('a_selling_air',array('kode_shipper'=>$data))->result_object()[0]->tanggal;
		
		////print_r($refno);
		//exit();
		
		$this->dbx->select('
			a_selling_air_rate.nama_service,
			a_selling_air_rate.destination,
			a_selling_air_rate.currency,
			a_selling_air_rate.price,
			a_selling_air_rate.kondisi,
			a_selling_air_rate.container,
			a_selling_air_rate.type,
			a_selling_air.tanggal
		');
		$this->dbx->join('a_selling_air','a_selling_air.id_selling=a_selling_air_rate.id_selling','left');
		$this->dbx->where('a_selling_air.kode_shipper',$data);
		$this->dbx->where('a_selling_air.tanggal',$refno);
		$this->dbx->where('a_selling_air_rate.of_emkl','0');
		$this->dbx->order_by('a_selling_air_rate.destination');
		$q = $this->dbx->get('a_selling_air_rate');
		
		return $q;
	}
	
	function print_data_misc($data){
		$this->dbx->select_max('a_selling_air.tanggal');
		$refno = $this->dbx->get_where('a_selling_air',array('kode_shipper'=>$data))->result_object()[0]->tanggal;
		
		////print_r($refno);
		//exit();
		
		$this->dbx->select('
			a_selling_air_rate.nama_service,
			a_selling_air_rate.destination,
			a_selling_air_rate.currency,
			a_selling_air_rate.price,
			a_selling_air_rate.kondisi,
			a_selling_air_rate.container,
			a_selling_air_rate.type,
			a_selling_air.tanggal
		');
		$this->dbx->join('a_selling_air','a_selling_air.id_selling=a_selling_air_rate.id_selling','left');
		$this->dbx->where('a_selling_air.kode_shipper',$data);
		$this->dbx->where('a_selling_air.tanggal',$refno);
		$this->dbx->where('a_selling_air_rate.of_emkl','1');
		$this->dbx->order_by('a_selling_air_rate.nama_service');
		$q = $this->dbx->get('a_selling_air_rate');
		
		return $q;
	}
	function manage_data($data){

		if($data['oper']=='add'){
			$this->dbx->insert('a_selling_air_rate', array(
				'id_selling'=>$data['id_selling'],
				'invoice_db'=>'PREPAID',
				'type'=>'KGs',
				'of_emkl'=>$data['of_emkl'],
			));	
		}
		if($data['oper']=='edit'){
			$this->dbx->where('id',$data['id']);
			$this->dbx->update('a_selling_air_rate', array($data['kolom']=>$data['isi'],'of_emkl'=>$data['of_emkl']));	
		}
		if($data['oper']=='del'){
			/*
			$this->dbx->where('id',$data['id']);
			$this->dbx->delete('a_selling_air_rate');
			*/
			$id=$data["id"];
			$idx = explode(",",$id);
			for($i=0;$i<=count($idx)-1;$i++) {
				$this->dbx->delete('a_selling_air_rate',array("id"=>$idx[$i]));
			}	
		}
	}
	function add_data_selling($data){
		$this->dbx->insert('a_selling_air',$data);
		if($this->dbx->affected_rows() > 0) {
			return '1';
		} else {
			return '0';	
		}
	}
	
	function update_data_selling($data,$id){
		$this->dbx->where('id_selling',$id);
		$this->dbx->update('a_selling_air',$data);
		if($this->dbx->affected_rows() > 0) {
			return '1';
		} else {
			return '0';	
		}
	}
	
	function get_data_print($shipper,$from){
		$this->dbx->select(array('shipper_id','dari','kode_shipper','destination'));
		$this->dbx->from('a_sellin');
		$this->dbx->join('a_selling_air_rate','a_selling_air_rate.shipper_id=a_selling_air.kode_shipper');
		$this->dbx->where('a_selling_air_rate.shipper_id',$shipper);
		$this->dbx->where('a_selling_air.dari',$from);
		$this->dbx->order_by('a_selling_air_rate.destination','asc');
		$q = $this->dbx->get();
		return $q->result_array();
		
	}
	
	function get_selling_rate($data,$limit){
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
			$q = $this->dbx->get_where("mstshipper", array('sales'=>$sales));
		} else {
			$q = $this->dbx->get_where("mstshipper",array('region_id'=>$region));
		}
		return $q->result();
	}
	
	function get_table_selling($data,$limit){
		
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
		return $this->dbx->get_where('a_selling_air_rate',array('id_selling'=>$data['id_selling']))->result();
	}
	
	function get_table_listing($data,$limit){
		$user_level = $this->session->userdata('level_id');
		$user_id = $this->session->userdata('user_id');
		
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
		$this->dbx->where ('kode_shipper',$data['shipper_id']);
		if($user_level==2) {
			$this->dbx->where ('dari',$user_id);
		}
		
		$query = $this->dbx->get_where('a_selling_air',array('region_id'=>$this->session->userdata('region_id')));
		return $query->result();
	}

	function get_selling($data){
		$this->dbx->select('ref_no,tanggal,valid,of_emkl,kode_shipper,kepada,id_selling as aktiv_id,dari,attn,peti_kemas,remarks');
		if($data['mode']=='last') {
			$this->dbx->limit('1');
			$this->dbx->order_by('id_selling','desc');
			$query = $this->dbx->get_where('a_selling_air',array('kode_shipper'=>$data['shipper_id'],'region_id'=>$this->session->userdata('region_id')))->row();
		}
		if($data['mode']=='prev') {
			$this->dbx->limit('1');
			$this->dbx->order_by('id_selling','desc');
			$this->dbx->where('id_selling <',$data['id']);
			$query = $this->dbx->get_where('a_selling_air',array('kode_shipper'=>$data['shipper_id'],'region_id'=>$this->session->userdata('region_id')))->row();
		}
		if($data['mode']=='next') {
			$this->dbx->limit('1');
			$this->dbx->order_by('id_selling','asc');
			$this->dbx->where('id_selling >',$data['id']);
			$query = $this->dbx->get_where('a_selling_air',array('kode_shipper'=>$data['shipper_id'],'region_id'=>$this->session->userdata('region_id')))->row();
		}
		if($data['mode']=='pilih') {
			$this->dbx->where('id_selling',$data['id']);
			$query = $this->dbx->get_where('a_selling_air',array('kode_shipper'=>$data['shipper_id'],'region_id'=>$this->session->userdata('region_id')))->row();
		}
		if($query) {
			$query->max_id = $this->get_max_id($data);
			$query->min_id = $this->get_min_id($data);
			$query->tanggal = $this->fungsi->jin_date_str($query->tanggal);
			$query->valid = $this->fungsi->jin_date_str($query->valid);
			return $query;
		} else {
			return false;
		}
	}
	
	function get_max_id($data){
		$this->dbx->select_max('id_selling');
		return $this->dbx->get_where('a_selling_air',array('kode_shipper'=>$data['shipper_id'],'region_id'=>$this->session->userdata('region_id')))->row()->id_selling;
	}
	
	function get_min_id($data){
		$this->dbx->select_min('id_selling');
		return $this->dbx->get_where('a_selling_air',array('kode_shipper'=>$data['shipper_id'],'region_id'=>$this->session->userdata('region_id')))->row()->id_selling;
	}
	
	function get_table_urut(){
		$this->dbx->select ('*');
		$query = $this->dbx->get('a_selling_air_urut');
		return $query;
	}
	
	function add_data($data){
		$this->dbx->insert('a_selling_air_rate',$data);
		if($this->dbx->affected_rows() > 0) {
			return '1';
		} else {
			return '0';	
		}
	}
	
	function update_data($data,$id){
		$this->dbx->where('id',$id);
		$this->dbx->update('a_selling_air_rate',$data);
		if($this->dbx->affected_rows() > 0) {
			return '1';
		} else {
			return '0';	
		}
	}
	
	function delete_data($data){
		$this->dbx->where('id',$data['id']);
		$this->dbx->delete('a_selling_air_rate');
		if($this->dbx->affected_rows() > 0) {
			return '1';
		} else {
			return '0';	
		}
	}
	
	function add_data_selling_air($data){
		$this->dbx->insert('a_selling_air',$data);
		if($this->dbx->affected_rows() > 0) {
			return '1';
		} else {
			return '0';	
		}
	}
	
	function update_data_selling_air($data,$id){
		$this->dbx->where('id_selling',$id);
		$this->dbx->update('a_selling_air',$data);
		if($this->dbx->affected_rows() > 0) {
			return '1';
		} else {
			return '0';	
		}
	}
	
	function delete_data_selling_air($id){
		$this->dbx->where('id_selling',$id);
		$this->dbx->delete('a_selling_air');
		/*
		$idx = explode(",",$id);
		for($i=0;$i<=count($idx)-1;$i++) {
			$this->dbx->delete('a_selling_air',array("id"=>$idx[$i],'region_id'=>$this->session->userdata('region_id')));
		}
		*/
		if($this->dbx->affected_rows() > 0) {
			$this->dbx->where('id_selling',$id);
			$this->dbx->delete('a_selling_air_rate');
			return '1';
		} else {
			return '0';	
		}
	}

}


