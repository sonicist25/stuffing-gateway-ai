<?php
class M_cn_to_shipper extends CI_Model
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
  	
function get_report_pdf($kode_ref){
		$this->dbx->select(array(
			'trnstuffingplan.vessel',
			'trnstuffingplan.voyage',
			'trnstuffingplan.etd_jkt',
			'trnstuffingplan.port_loading',
			'trnstuffingplan.mother_bl',
			'trnstuffingplan.kurs',
			'trnstuffingplan.region_id as region_id',
			'trnstuffingplandetail.destination',
			'trnstuffingplandetail.no_pack_qty',
			'trnstuffingplandetail.no_pack_unit',
			'trnstuffingplandetail.no_of_pack',
			'trnstuffingplandetail.freight',
			'trnstuffingplandetail.bl_meas',
			'trnstuffingplandetail.code',
			'trnstuffingplandetail.kode_ref',
         'trnstuffingplandetail.weight',
         'trnstuffingplandetail.hb_l',
         'trnstuffingplandetail.sp_detail_id',
			)
		);
		$this->dbx->from('trnstuffingplandetail');
		$this->dbx->join('trnstuffingplan','trnstuffingplan.sp_id=trnstuffingplandetail.sp_id','left');		
		$this->dbx->where('trnstuffingplandetail.kode_ref', $kode_ref);	
		$this->dbx->where('trnstuffingplan.region_id',$this->session->userdata('region_id'));
		$q = $this->dbx->get();
		return $q->row_array();
	}

        function get_ttl_detail($id,$cur){
		$this->dbx->select('price');
		$query = $this->dbx->get_where('x_finance_item',array('number'=>$id,'currency'=>$cur));
		if($query->num_rows() > 0 ) {
			foreach($query->result_array() as $row => $key)
			{
				//$ttl[] = $key['qty']*$key['price'];
				$ttl[] = $key['price'];
			}
			return $ttl;
		} else {
			return null;	
		}
	}
	
	function update_price($data)
	{
		$this->dbx->update("x_finance_item",array("price"=>$data["price"]),array("id"=>$data["id"]));
		return array("status"=>$this->dbx->affected_rows());	
	}
	
	function saving_cn($data)
	{
		/*
		unset($data['myData']['valid']);
		if($data['myData']['g'] == "") {
			$data['myData']['g'] = null;
		}
		*/
		
		$this->dbx->update("x_finance_item",
			$data['myData'],
			array('id'=>$data['id'])
		);
		return array("status"=>$this->dbx->affected_rows());
	}
	
	function tarik_data_detail($data) {
		$this->dbx->delete("x_finance_item",array("number"=>$data["number"],"id_tarik"=>1));
		
		$this->dbx->order_by("id","asc");
		$q = $this->dbx->get_where("inf_cost_tbl_satu", array(
				"kode_ref"=>$data["kode_ref"],
				"prep_coll"=>"COLLECT",
				"currency"=>"RP.",
		));
		if($q->num_rows()>0) 
		{
			foreach($q->result() as $row)
			{
				$this->dbx->insert("x_finance_item",array(
					"number"=>$data["number"],
					"id_tarik"=>1,
					"item"=>$row->item_service,
					"qty"=>$row->qty,
					"unit_price"=>$row->unit_price,
					"price"=>$row->price,
					"currency"=>$row->currency,
					"pre_col"=>$row->prep_coll,
					'region_id'			=>$this->session->userdata('region_id'),
					'anak_job'=>$row->kode_ref,
				));
			}
		}
		return $q->result();
	}
	
	function manage_data_detail($data) {
		$oper = $data["oper"];
		unset($data["oper"]);
		if($oper=="add") {
			$this->dbx->insert("x_finance_item",$data);
		}
		if($oper=="del") {
			$this->dbx->delete("x_finance_item",array("id"=>$data["id"]));
		}
		return array("status"=>$this->dbx->affected_rows());
	}
	
	function get_content($post){
		if($post['mode']=='pilih') {
			$data = $this->dbx->get_where('x_finance',array(
				'id'=>$post['id'],
				'jenis'=>'CREDIT NOTE',
				'kepada'=>'SHIPPER',
				'region_id'=>$this->session->userdata('region_id'),
			))->row();
			if($data) {
				//print_r($data);
				$data->kepada_nama = $this->get_shipper($data->kepada_kode)[0]->shipper_name;
				$data->kepada_alamat = $this->get_shipper($data->kepada_kode)[0]->shipper_address;
				$data->date = $this->fungsi->jin_date_str($data->date);
				$data->id_last = $this->get_id('max');
				$data->id_first = $this->get_id('min');
				return $data;
			} else {
				$data = new stdClass();
				$data->job_no = '';
				return $data;
			}
		}
		elseif($post['mode']=='prev') {
			$this->dbx->order_by('id','desc');
			$data = $this->dbx->get_where('x_finance',array(
				'id <'=>$post['id'],
				'jenis'=>'CREDIT NOTE',
				'kepada'=>'SHIPPER',
				'region_id'=>$this->session->userdata('region_id'),
			),1,false)->row();
			if($data) {
				$data->kepada_nama = $this->get_shipper($data->kepada_kode)->shipper_name;
				$data->kepada_alamat = $this->get_shipper($data->kepada_kode)->shipper_address;
				$data->date = $this->fungsi->jin_date_str($data->date);
				$data->id_last = $this->get_id('max');
				$data->id_first = $this->get_id('min');
				return $data;
			} else {
				$data = new stdClass();
				$data->job_no = '';
				return $data;
			}
		}
		elseif($post['mode']=='next') {
			$this->dbx->order_by('id','asc');
			$data = $this->dbx->get_where('x_finance',array(
				'id >'=>$post['id'],
				'jenis'=>'CREDIT NOTE',
				'kepada'=>'SHIPPER',
				'region_id'=>$this->session->userdata('region_id'),
			),1,false)->row();
			if($data) {
				$data->kepada_nama = $this->get_shipper($data->kepada_kode)->shipper_name;
				$data->kepada_alamat = $this->get_shipper($data->kepada_kode)->shipper_address;
				$data->date = $this->fungsi->jin_date_str($data->date);
				$data->id_last = $this->get_id('max');
				$data->id_first = $this->get_id('min');
				return $data;
			} else {
				$data = new stdClass();
				$data->job_no = '';
				return $data;
			}
		}
		elseif($post['mode']=='last') {
			$this->dbx->order_by('id','desc');
			$data = $this->dbx->get_where('x_finance',array(
				'jenis'=>'CREDIT NOTE',
				'kepada'=>'SHIPPER',
				'region_id'=>$this->session->userdata('region_id'),
			),1,false)->row();
			if($data) {
				//$data->kepada_nama = $this->get_consignee($data->kepada_kode)->consignee_import_name;
				//$data->kepada_alamat = $this->get_consignee($data->kepada_kode)->consignee_import_address;
				var_dump($this->get_shipper($data->kepada_kode));
				$data->kepada_nama = $this->get_shipper($data->kepada_kode)->shipper_name;
				$data->kepada_alamat = $this->get_shipper($data->kepada_kode)->shipper_address;
				$data->date = $this->fungsi->jin_date_str($data->date);
				$data->id_last = $this->get_id('max');
				$data->id_first = $this->get_id('min');
				return $data;
			} else {
				$data = new stdClass();
				$data->job_no = '';
				return $data;
			}
		}
	}
	
	function get_id($mode) {
		$select = 'select_'.$mode;
		$this->dbx->$select('id');
		$query = $this->dbx->get_where('x_finance',array(
			'jenis'=>'CREDIT NOTE',
			'kepada'=>'SHIPPER',
			'region_id'=>$this->session->userdata('region_id'),
		))->row();
		return $query->id;
	}
	
	function get_shipper($post){
		$this->dbx->select(array('shipper_id','shipper_name','shipper_address'));
		$this->dbx->like('shipper_name',$post);
		$data = $this->dbx->get_where('mstshipper',array('region_id'=>$this->session->userdata('region_id')))->result();
		//print_r($post);
		//die();
		if($data) {
			return $data;	
		} else {
			$data = new stdClass();
			$data->shipper_id = '';
			$data->shipper_name = '';
			$data->shipper_address = '';
			return $data;
		}
	}
	
	function get_shipper_byJOB($post){
		$this->dbx->select('shipper_print');
		$data = $this->dbx->get_where('trnstuffingplandetail',array('kode_ref'=>$post))->row()->shipper_print;
		//print_r($data);
		//die();
		if($data) {
			return $this->get_shipper($data);	
		} else {
			$data = new stdClass();
			$data->shipper_name = '';
			$data->shipper_address = '';
			return $data;
		}
	}
		
public function select_job($searchTerm = '') {
    $this->dbx->select('kode_ref, region_id');
    
    // Apply search filter if search term is provided
    if (!empty($searchTerm)) {
        $this->dbx->like('kode_ref', $searchTerm);
    }

    // Add condition for 'prep_coll' and 'region_id'
    $this->dbx->where('prep_coll', 'COLLECT');
    $this->dbx->where('region_id', $this->session->userdata('region_id'));
    
    // Group by kode_ref to avoid duplicates
    $this->dbx->group_by('kode_ref');
    $this->dbx->limit(10);

    // Execute query and return result
    return $this->dbx->get('inf_cost_tbl_satu');
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
       	if($searchOper == 'bw' || $searchOper == 'bn') $searchString .= '%';
       	if($searchOper == 'ew' || $searchOper == 'en' ) $searchString = '%'.$searchString;
      	if($searchOper == 'cn' || $searchOper == 'nc' || $searchOper == 'in' || $searchOper == 'ni') $searchString = '%'.$searchString.'%';
				
      	$where = "$searchField $ops '$searchString' ";                     
			$this->dbx->where($where); 				            
		}
		$this->dbx->order_by($post['sidx'],$post['sord']);
		
		if($limit==true) {
			$this->dbx->limit($post['rows'],$post['start']);
		}			
		return $this->dbx->get_where('x_finance',array('jenis'=>'CREDIT NOTE','kepada'=>'SHIPPER','region_id'=>$this->session->userdata('region_id')))->result();	
	}
	
	function get_table_dua($post,$limit){
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
       	if($searchOper == 'bw' || $searchOper == 'bn') $searchString .= '%';
       	if($searchOper == 'ew' || $searchOper == 'en' ) $searchString = '%'.$searchString;
      	if($searchOper == 'cn' || $searchOper == 'nc' || $searchOper == 'in' || $searchOper == 'ni') $searchString = '%'.$searchString.'%';
				
      	$where = "$searchField $ops '$searchString' ";                     
			$this->dbx->where($where); 				            
		}
		$this->dbx->order_by($post['sidx'],$post['sord']);
		
		if($limit==true) {
			$this->dbx->limit($post['rows'],$post['start']);
		}			
		return $this->dbx->get_where('x_finance_item',array('number'=>$post['number']))->result();
	}
	
	function get_detail($number)
	{
		return $this->dbx->get_where('x_finance_item',array('number'=>$number));
	}
	
	function manage_data($data){
		if($data['oper']=='add') {
			unset($data['oper']);
			unset($data['id']);
			$q = $this->dbx->insert('x_finance',$data);
			if($this->dbx->affected_rows() > 0) {
				$this->dbx->where('kode_ref',$data['job_no']);
				$this->dbx->update('trnstuffingplandetail',array('trk_cn_shipper'=>'1'));
			}
		}
		elseif($data['oper']=='edit') {
			unset($data['oper']);
			// kolom identitas & relasi tidak boleh diubah lewat grid edit
			unset($data['id']);
			unset($data['number']);      // dipakai sebagai WHERE, bukan nilai baru
			unset($data['kepada']);
			unset($data['kepada_kode']);
			unset($data['region_id']);
			unset($data['job_no']);
			unset($data['created_by']);
			unset($data['created_at']);

			$data['updated_by'] = $this->session->userdata('fullname');
			$data['updated_at'] = date('Y-m-d H:i:s');

			$this->dbx->where('number', $this->input->post('number'));
			$this->dbx->where('region_id', $this->session->userdata('region_id'));
			$this->dbx->update('x_finance', $data);
		}
		else {
			$this->dbx->where('id',$data['id']);
			$this->dbx->delete('x_finance');
			if($this->dbx->affected_rows() > 0) {
				$this->dbx->where('kode_ref',$data['kode_ref']);
				$this->dbx->update('trnstuffingplandetail',array('trk_cn_shipper'=>'0'));
			}
		}
		return $this->dbx->affected_rows();
	}
}
?>