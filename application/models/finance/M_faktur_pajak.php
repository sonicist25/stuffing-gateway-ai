<?php
class M_faktur_pajak extends CI_Model
{	
	public function __construct(){
    	parent::__construct();
    	$y = $this->session->userdata('db');  
    	if($y=='') : $db='master'; else : $db=switch_db($y); endif;
    	$this->load->database($db);
		$this->region = $this->session->userdata("region_id");
   }
   
   function get_table_satu($post,$limit){
		if($post['_search']=='true') {
			$searchField = $post['searchField'];
			$searchString = $post['searchString'];
      	$searchOper  =  $post['searchOper'];
			
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
      	if($searchOper == 'cn' ) $searchString = '%'.$searchString.'%';
				
      	$where = "$searchField $ops '$searchString' ";                     
			$this->dbx->where($where); 				            
		}
		
		if($limit==true) {
			$this->dbx->limit($post['rows'],$post['start']);
		}
		
		$this->dbx->order_by($post['sidx'],$post['sord']);
		return $this->dbx->get_where('x_faktur_pajak',array(
			'seri'=>$post['seri'],
			'region_id'=>$this->session->userdata('region_id'),
		))->result();
	}
   
	function get_content($post){
		if($post['mode']=='pilih') {
			$data = $this->dbx->get_where('x_faktur_pajak',array(
				'id'=>$post['id'],
				'seri'=>$post['seri'],
				'region_id'=>$this->session->userdata('region_id'),
			))->row();
			if($data) {
				$data->kurs = $this->fungsi->pecah($data->kurs, ' ');
				$data->date;
				$data->id_last = $this->get_id('max',$post['seri']);
				$data->id_first = $this->get_id('min',$post['seri']);
				return $data;
			} else {
				$data = new stdClass();
				$data->id='';
				return $data;
			}
		}
		elseif($post['mode']=='prev') {
			$this->dbx->order_by('id','desc');
			$data = $this->dbx->get_where('x_faktur_pajak',array(
				'id <'=>$post['id'],
				'seri'=>$post['seri'],
				'region_id'=>$this->session->userdata('region_id'),
			),1,false)->row();
			if($data) {
				$data->kurs = $this->fungsi->pecah($data->kurs, ' ');
				$data->date;
				$data->id_last = $this->get_id('max',$post['seri']);
				$data->id_first = $this->get_id('min',$post['seri']);
				return $data;
			} else {
				$data = new stdClass();
				$data->id = '';
				return $data;
			}
		}
		elseif($post['mode']=='next') {
			$this->dbx->order_by('id','asc');
			$data = $this->dbx->get_where('x_faktur_pajak',array(
				'id >'=>$post['id'],
				'seri'=>$post['seri'],
				'region_id'=>$this->session->userdata('region_id'),
			),1,false)->row();
			if($data) {
				$data->kurs = $this->fungsi->pecah($data->kurs, ' ');
				$data->date;
				$data->id_last = $this->get_id('max',$post['seri']);
				$data->id_first = $this->get_id('min',$post['seri']);
				return $data;
			} else {
				$data = new stdClass();
				$data->id = '';
				return $data;
			}
		}
		elseif($post['mode']=='last') {
			$this->dbx->order_by('id','desc');
			$data = $this->dbx->get_where('x_faktur_pajak',array(
				'seri'=>$post['seri'],
				'region_id'=>$this->session->userdata('region_id'),
			),1,false)->row();
			if($data) {
				$data->kurs = $this->fungsi->pecah($data->kurs, ' ');
				$data->date;
				$data->id_last = $this->get_id('max',$post['seri']);
				$data->id_first = $this->get_id('min',$post['seri']);
				return $data;
			} else {
				$data = new stdClass();
				$data->id = '';
				return $data;
			}
		}
	}   	
	function get_id($mode,$seri) {
		$select = 'select_'.$mode;
		$this->dbx->$select('id');
		$query = $this->dbx->get_where('x_faktur_pajak',array(
			'seri'=>$seri,
			'region_id'=>$this->session->userdata('region_id'),
		))->row();
		return $query->id;
	}
	
   function get_faktur_pajak($data){
   	$tanggal=$this->fungsi->jin_date_sql($data['date']);
   	$q = $this->dbx->get_where('a_kurs_pajak',array('mulai_tgl	<='=>$tanggal,'smp_dgn >='=>$tanggal))->row();
   	if($q) {
   		$kurs = $this->fungsi->pecah($q->kurs,' ');
   		$no_kmk = $q->no_kmk;
   		$tgl_kmk = $this->fungsi->jin_date_str($q->tgl_kmk);
   		$mssg = '';
   	} else {
   		$kurs = '';
   		$no_kmk = '';
   		$tgl_kmk = '';
   		$mssg = 'Data kurs pajak has not been updated';
   	}
   	return array('kurs'=>$kurs,'no_kmk'=>$no_kmk,'tgl_kmk'=>$tgl_kmk,'messages'=>$mssg);
   }
   
   function insert_shipper_data($data){
   	$q = $this->dbx->get_where('x_finance',array('number'=>$data['no_inv']))->row();
   	if($q) {
   		$kode_ref = $q->job_no;
   		$shipper = $q->kepada_kode;
   		$tgl_inv = $this->fungsi->jin_date_str($q->date);
   		$mssg = '';
   	} else {
   		$kode_ref = '';
   		$shipper = '';
   		$tgl_inv = '';
   		$mssg = 'Invalid data no Inv';
   	}
   	return array('kode_ref'=>$kode_ref,'shipper'=>$shipper,'tgl_inv'=>$tgl_inv,'messages'=>$mssg);
   }
   
   function get_list_invoice(){
   	$this->dbx->select('number',false);
   	$this->dbx->order_by('number','desc');
   	return $this->dbx->get_where('x_finance',array("region_id"=>$this->region));
   }
	
	function manage_data($data){
		$region = $this->session->userdata('region_id');
		if($data['oper']=='add') {
			unset($data['oper']);
			unset($data['id']);
			$this->dbx->insert('x_faktur_pajak',$data);
		} elseif($data['oper']=='edit'){
			unset($data['oper']);
			$this->dbx->where('id',$data['id']);
			$this->dbx->where('region_id',$region);
			$this->dbx->update('x_faktur_pajak',$data);
		} elseif($data['oper']=='del') {
			$this->dbx->where('id',$data['id']);
			$this->dbx->where('region_id',$region);
			$this->dbx->delete('x_faktur_pajak');
			$this->dbx->where('id_faktur_pajak',$data['id']);
			$this->dbx->delete('x_faktur_pajak_item');
		}
		if($this->dbx->affected_rows() > 0) {
			return 1;
		} else {
			return 0;
		}
	}
	
	function manage_data_dua($data){
		$this->dbx->where('id',$data['id']);
		$this->dbx->delete('x_faktur_pajak_item');

		if($this->dbx->affected_rows() > 0) {
			return 1;
		} else {
			return 0;
		}
	}

	function tarik_data($data){
		
		$this->dbx->delete('x_faktur_pajak_item',array('id_faktur_pajak'=>$data['id_fp']));	
		
		$q = $this->dbx->get_where("x_finance",
				array(
				"number"=>$data["number"],
			)
		);
		
		if($q->num_rows()>0) {
			$id = $q->row()->id;
		}
		
		$query=$this->dbx->get_where("x_finance_item",array("number"=>$id,"ppn"=>"TRUE","currency"=>$data["currency"]));
		//$query=$this->dbx->get_where("x_finance_item",array("number"=>$id,"ppn"=>"TRUE"));
		
		////print_r(array('r'=>$query->result(),'cur'=>$data["currency"],'number'=>$id));
		//return;
		
		if($query->num_rows()>0) {
			foreach($query->result() as $row){
				$this->dbx->insert('x_faktur_pajak_item', array(
							'id_faktur_pajak'=>$data['id_fp'],
							'nama_jasa'=>$row->item,
							'jumlah'=>$row->qty,
							'price'=>$row->price,
							'unit_price'=>$row->unit_price,
							'currency'=>$row->currency,
				));
			}
		} 
		return array($data,$q->result(),$query->result());
	}

	function get_table_dua($data,$limit){	
		$this->dbx->order_by($data['sidx'],$data['sord']);
		return $this->dbx->get_where('x_faktur_pajak_item',array(
			'id_faktur_pajak'=>$data['id_fp'],
		))->result();
	}

	function print_data($id) {
		$q = $this->dbx->get_where('x_faktur_pajak',array('id'=>$id));
		if($q->num_rows() > 0 ) {
			$head = $q->row();	
		} else {
			$head = "";
		}
		$q1 = $this->dbx->get_where('x_faktur_pajak_item',array('id_faktur_pajak'=>$id));
		if($q->num_rows() > 0 ) {
			$content = $q1->result();
		} else {
			$content = "";
		}
		return array($head,$content);
	}

	function get_perusahaan($id){
		
	//	$table = array('mstshipper','refagentcompany','refshippingcompany','refconsigneenotify');
	
		$data = new stdClass();
		$data->name = '';
		$data->address = '';
		$data->npwp = '';
		
		$this->dbx->like('shipper_id',$id);
		$q = $this->dbx->get('mstshipper');
		if($q->num_rows() > 0 ) {
			$data = new stdClass();
			$data->name = $q->row()->shipper_name;
			$data->address = $q->row()->shipper_address;	
			$data->npwp = $q->row()->shipper_npwp;
		}
		
		$this->dbx->like('agent_company_code',$id);
		$q = $this->dbx->get('refagentcompany');
		if($q->num_rows() > 0 ) {
			$data = new stdClass();
			$data->name = $q->row()->agent_company_name;
			$data->address = $q->row()->agent_company_address;	
			$data->npwp = $q->row()->agent_company_npwp;	
		}
		
		$this->dbx->like('shipping_company_code',$id);
		$q = $this->dbx->get('refshippingcompany');
		if($q->num_rows() > 0 ) {
			$data = new stdClass();
			$data->name = $q->row()->shipping_company_name;
			$data->address = $q->row()->shipping_company_address;	
			$data->npwp = $q->row()->shipping_company_npwp;	
		}
		
		$this->dbx->like('consignee_notify_code', $id);
		$this->dbx->or_like('consignee_notify_name', $id);
		$q = $this->dbx->get('refconsigneenotify');
		if($q->num_rows() > 0 ) {
			$data = new stdClass();
			$data->name = $q->row()->consignee_notify_name;
			$data->address = $q->row()->consignee_notify_address;	
			$data->npwp = $q->row()->consignee_notify_npwp;	
		}
		
		return $data;
	}

	function get_same_number()
	{
		$this->dbx->order_by("number","asc");
		$this->dbx->select("number as no, job_no as job, jenis, date, group");
		$q = $this->dbx->get("x_finance");
		return $q;
		/*
		foreach($q->result() as $row)
		{
			$no[]=$row->no." ".$row->job_no;
		}
		//print_r($no);
		*/
	}
}
?>