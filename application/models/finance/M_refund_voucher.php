<?php
class M_refund_voucher extends CI_Model
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
   
	function print_data($id){
		$q1 = $this->dbx->get_where('x_refund_voucher',array('id'=>$id));
		if($q1->num_rows() > 0 ) {
			$query1 = $q1->row();	
		} else {
			$query1 = new stdClass();
		}
		$q2 = $this->dbx->get_where('x_refund_voucher_item',array('id_xdv'=>$id));
		if($q1->num_rows() > 0 ) {
			$query2 = $q2->result_array();	
		} else {
			$query2 = array('','');
		}
		return array('header'=>$query1,'content'=>$query2);
	}

	function get_table_dua($data,$limit) {
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
		
		$this->dbx->order_by($data['sidx'], $data['sord']);
		if($limit==true) {
			$this->dbx->limit($data['rows'],$data['start']);
		}
		return $this->dbx->get_where('x_refund_voucher',array(
			'region_id'=>$this->session->userdata('region_id'),
		))->result();
	}

	function get_table_satu($data,$limit) {
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
		
		$this->dbx->order_by($data['sidx'], $data['sord']);
		if($limit==true) {
			$this->dbx->limit($data['rows'],$data['start']);
		}
		return $this->dbx->get_where('x_refund_voucher_item',array(
			'id_xdv'=>$data['id_xdv'],
			'region_id'=>$this->session->userdata('region_id'),
		))->result();
	}

	function get_content($post){
		$this->dbx->select('id,bl_no,kode_job,group,date,destination,no_invoice,payable');
		if($post['mode']=='pilih') {
			$data = $this->dbx->get_where('x_refund_voucher',array(
				'id'=>$post['id'],
				'region_id'=>$this->session->userdata('region_id'),
			))->row();
			if($data) {
				$data->date = $this->fungsi->jin_date_str($data->date);
				$data->id_last = $this->get_id('max');
				$data->id_first = $this->get_id('min');
				return $data;
			} else {
				$data = new stdClass();
				$data->kode_job = '';
				return $data;
			}
		}
		elseif($post['mode']=='prev') {
			$this->dbx->order_by('id','desc');
			$data = $this->dbx->get_where('x_refund_voucher',array(
				'id <'=>$post['id'],
				'region_id'=>$this->session->userdata('region_id'),
			),1,false)->row();
			if($data) {
				$data->date = $this->fungsi->jin_date_str($data->date);
				$data->id_last = $this->get_id('max');
				$data->id_first = $this->get_id('min');
				return $data;
			} else {
				$data = new stdClass();
				$data->kode_job = '';
				return $data;
			}
		}
		elseif($post['mode']=='next') {
			$this->dbx->order_by('id','asc');
			$data = $this->dbx->get_where('x_refund_voucher',array(
				'id >'=>$post['id'],
				'region_id'=>$this->session->userdata('region_id'),
			),1,false)->row();
			if($data) {
				$data->date = $this->fungsi->jin_date_str($data->date);
				$data->id_last = $this->get_id('max');
				$data->id_first = $this->get_id('min');
				return $data;
			} else {
				$data = new stdClass();
				$data->kode_job = '';
				return $data;
			}
		}
		elseif($post['mode']=='last') {
			$this->dbx->order_by('id','desc');
			$data = $this->dbx->get_where('x_refund_voucher',array(
				'region_id'=>$this->session->userdata('region_id'),
			),1,false)->row();
			if($data) {
				$data->date = $this->fungsi->jin_date_str($data->date);
				$data->id_last = $this->get_id('max');
				$data->id_first = $this->get_id('min');
				return $data;
			} else {
				$data = new stdClass();
				$data->kode_job = '';
				return $data;
			}
		}
	}
	
	function get_id($mode) {
		$select = 'select_'.$mode;
		$this->dbx->$select('id');
		$query = $this->dbx->get_where('x_refund_voucher',array(
			'region_id'=>$this->session->userdata('region_id'),
		))->row();
		return $query->id;
	}

	function manage_data($data) {
		$oper = $data['oper'];
		unset($data['oper']);
		if($oper=='add'){
			unset($data['id']);
			$data['region_id']=$this->session->userdata('region_id');
			$data['date']=$this->fungsi->jin_date_sql($data['date']);
			$this->dbx->insert('x_refund_voucher',$data);
		}
		if($oper=='edit'){
			$data['date']=$this->fungsi->jin_date_sql($data['date']);
			$this->dbx->where('id',$data['id']);
			$this->dbx->update('x_refund_voucher',$data);
		}
		if($oper=='del'){
			$this->dbx->where('id',$data['id']);
			$this->dbx->delete('x_refund_voucher');
		}
		return $this->dbx->affected_rows();
	}

	function get_kode_job(){
		$this->dbx->select ('kode_ref',FALSE);
		return $this->dbx->get('inf_cost_refund_sc');
	}
	
	function tarik_data_refund($data){
		$this->dbx->select (array(
			'kode_ref','payable_to','g',
		),false);
		return $this->dbx->get_where('inf_cost_refund_sc',array('kode_ref'=>$data['kode_ref']));
	}
	
	function get_bl_no($kode_ref){
		$this->dbx->select (array('kode_ref','bl_no'),FALSE);
		$this->dbx->order_by('id','desc');
		$data = $this->dbx->get_where('x_finance_item',array(
			'kode_ref'=>$kode_ref,
		),1,false);
		if($data->num_rows() > 0 ) {
			return $data->row()->bl_no;
		} else {
			return "";
		}
	}
	
	function get_no_invoice($kode_ref){
		$this->dbx->select (array('job_no','number'),false);
		$this->dbx->order_by('id','desc');
		$data = $this->dbx->get_where('x_finance',array(
			'job_no'=>$kode_ref,
		));
		if($data->num_rows() > 0 ) {
			foreach ($data->result() as $row ){
				$number[]= $row->number;
			}
			return $number;
		} else {
			return "";
		}
	}
	
	function tarik_table_satu($data){
		$this->dbx->select (array(
			'item_service','qty','unit_price','currency',
		),false);
		$query = $this->dbx->get_where('inf_cost_refund_sc',array('kode_ref'=>$data['kode_ref']));
		if($query->num_rows() > 0 ) {
			$i=1;
			foreach($query->result() as $row){
				$cur = $row->currency;
				$amount = $row->qty * $row->unit_price;
				if($cur=='RP.') {
					$cur='IDR';
				}
				$hasil[] = array(
					'id'=>$i,
					'id_xdv'=>$data['id'],
					'item'=>$row->item_service.'<br>( '.$row->qty.' X '.$cur.' '.$row->unit_price.' )',
					'currency'=>$cur,
					'amount'=>$amount,
					'region_id'=>$this->session->userdata('region_id'),
				);
				$i++;
			}
			return array('rows'=>$hasil);
		} else {
			return "No data injectted";
		}
	}
	
	function save_table_satu($data){
		for($i = 0; $i < count($data['rows']); ++$i) {
			$this->dbx->insert('x_refund_voucher_item', array(
				'id_xdv'=>$this->get_id('max'),
				'item'=>$data['rows'][$i]['item'],
				'currency'=>$data['rows'][$i]['currency'],
				'amount'=>$data['rows'][$i]['amount'],
				'region_id'=>$this->session->userdata('region_id'),
			));
		}
		
		return array('status'=>$this->dbx->affected_rows());
	}

}



	/*
	function get_data($data){
		$region = $this->session->userdata('region_id');
		$this->dbx->select('id,bl_no,kode_job,group,date,destination,no_invoice,payable');
		if($data['mode']=='last') {
			$this->dbx->limit(1);
			$this->dbx->order_by('id','desc');
			return $this->dbx->get_where('testtable',array('region_id'=>$region))->row();
		}
		if($data['mode']=='next') {
			$this->dbx->limit(1);
			$this->dbx->order_by('id','asc');
			return $this->dbx->get_where('testtable',array('id >'=>$data['id'],'region_id'=>$region))->row();
		}
		if($data['mode']=='prev') {
			$this->dbx->limit(1);
			$this->dbx->order_by('id','desc');
			return $this->dbx->get_where('testtable',array('id <'=>$data['id'],'region_id'=>$region))->row();
		}
		if($data['mode']=='pilih') {
			return $this->dbx->get_where('testtable',array('id'=>$data['id'],'region_id'=>$region))->row();
		}
	}

	
	function get_last_id(){
		$region = $this->session->userdata('region_id');
		$this->dbx->select_max('id');
		return $this->dbx->get_where('testtable',array('region_id'=>$region))->row()->id;
	}
	
	function get_first_id(){
		$region = $this->session->userdata('region_id');
		$this->dbx->select_min('id');
		return $this->dbx->get_where('testtable',array('region_id'=>$region))->row()->id;
	}
	
	$this->dbx->insert('x_refund_voucher_item', array(
							'chache'=>$this->session->userdata('user_id'),
							'id_xdv'=>$data['id'],
							'item'=>$row->item_service.'<br>( '.$row->qty.' X '.$cur.' '.$row->unit_price.' )',
							'currency'=>$cur,
							'amount'=>$row->qty * $row->unit_price,
							'region_id'=>$this->session->userdata('region_id'),
				));
				return $this->dbx->affected_rows();
*/
	
	
?>
	