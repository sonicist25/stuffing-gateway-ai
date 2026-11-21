<?php
class Afg_c_cost extends CI_Controller
{
	var $title = 'Cost';
	public function __construct(){
		parent::__construct();
		$this->fungsi->restrict();
	//	$this->fungsi->delete_trucking();
		$this->load->model(array('afg_m_export',
			'afg_m_countries',
			'm_bill_of_ladding',
			'afg_m_cost',
			'afg_m_income',
			'reference/type_service_model',
			'reference/type_container_model'
		));
		$this->fungsi->dbx = $this->afg_m_cost->dbx;
		$this->load->library('access_control');
	}

	function index(){
		die();
	}
	
	function saving_cost()
	{
		$data = $this->afg_m_cost->saving_cost($_POST);
		////print_r($this->dbx->last_query());
		echo json_encode($data);
	}
	
	function receive_data(){
		$data = $this->afg_m_income->getReceiveFrom();
      //print_r($data);
	}
	
	function tarik_data_refund(){
		$data = $this->afg_m_cost->tarik_data_refund($_POST);
		//print_r($this->dbx->last_query());
	}
	
	function tarik_cost(){
		$data = $this->afg_m_cost->tarik_cost($_POST);
      //print_r(array('post'=>$_POST,'get'=>$data));
	}
	
	function tampil_table_satu(){
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
		$sord = isset($_POST['sord'])?$_POST['sord']:'desc';
      
      $start = $batas * $hal - $batas;
		$start = ($start<0)?0:$start;
		$_POST['start'] = $start;
		
		$q = $this->afg_m_cost->tampil_table_satu($_POST,false);
		$query = $this->afg_m_cost->tampil_table_satu($_POST,true);
		$hitung = count($q);
	 
		if( $hitung > 0 ) {
		   $total_hal = ceil($hitung/$batas);
		} else {
		   $total_hal = 0;
		}
	 
		if ($hal > $total_hal) $hal=$total_hal;
		
	 	$data = new stdClass();	 
		$data->page = $hal;
		$data->total = $total_hal;
		$data->records = $hitung;
		$i=0;

		foreach($query as $row) {
			$invoice_db = $row->invoice_db;
			
			$quantity=$row->qty;
			if($quantity=='') {
				$quantity=$_POST['qty'];
			}
			if(is_numeric($quantity) && is_numeric($row->unit_price)){
			$price = $quantity * $row->unit_price;}
			else {
			$price = '';
			}
			$data->rows[$i]['id']=$row->id;
		   $data->rows[$i]['cell']=array(
		   	$row->sp_detail_id,
		    	$row->code_service,
		    	$row->item_service,
		    	$quantity,
		    	$row->currency,
		    	$row->unit_price,
				$row->price,
		    	$row->prep_coll,
		    	$row->payable_to,
		    	$row->remark,
		    	$row->g,
				$row->status,
				$row->ppn,
				$row->created_by,
				$row->created_at,
				$row->updated_by,
				$row->updated_at,
		   );
		   $i++;
		}
		echo json_encode($data);
	//	//print_r($data);
	}	
	
	function manage_table_satu(){
		$mode = $this->input->post('oper');
		$id = $this->input->post('id');
		$spd = $this->input->post('spd');
		$kode_ref = $this->input->post('kode_ref');
		$kolom = $this->input->post('kolom');
		$isi = $this->input->post('isi');
		$qty        = (float) $this->input->post('qty');
		$unit_price = (float) $this->input->post('unit_price');
		$price      = $qty * $unit_price;
		
		if($mode == 'add'){
			$status = $this->afg_m_cost->add_tbl_satu($spd,$kode_ref,$kolom,$isi);
			echo json_encode(array('status'=>$status,'mode'=>'add'));
		} else if($mode == 'edit') {
			$status = $this->afg_m_cost->update_tbl_satu($kolom,$isi,$id,$price,$qty);
			echo json_encode(array('status'=>$status,'mode'=>'edit','field'=>$kolom));
		}	else if($mode == 'del') {
			$status = $this->afg_m_cost->delete_tbl_satu($id);
			echo json_encode(array('status'=>$status,'mode'=>'delete'));
		}	

		//$value = $this->afg_m_cost->manage_table_satu($_POST);
		//echo json_encode($value);
	}
	
	function open_form(){
		$this->access_control->require_feature('stuffing_cost', 'view', true);
		$data['id'] = $this->input->post('rowid');
		$data['kode_ref'] = $this->input->post('kode_ref');
		$data['customer'] = $this->input->post('customer');
		$row = $this->afg_m_cost->check_data_cost($data['kode_ref']);
		$row1 = $this->afg_m_income->check_data_income($data['kode_ref']);
		$data['RC-TDK-PROSES']=$row['rc'];
		if($row['kurs']==0.00)
			{$data['kurs']=$this->input->post('kurs');
		}else{
			$data['kurs']=$row['kurs'];}
		$data['created']=$row1['created_by'].' '.$row1['created_at'];
		$data['last_updated']=$row1['last_update_by'].' '.$row1['last_update_at'];
		$data['post']= $row1['created_at'];
		$this->load->view('trucking/form_cost',$data);
	}
	
	function open_form_global(){
		$this->access_control->require_feature('stuffing_global_cost', 'view', true);
		$data['job_no']= $this->input->post('ref_no');
		$row = $this->afg_m_cost->check_data_global_cost($data['job_no']);
		$data['created']=$row['created_by'].' '.$row['created_at'];
		$this->load->view('trucking/form_global_cost',$data);
	}
	
	function open_form_global_edit(){
		$this->access_control->require_feature('stuffing_global_cost', 'edit', true);
		$data['id'] = $this->input->post('id');
		$data['oper'] = $this->input->post('oper');
		$this->load->view('trucking/form_global_cost_edit',$data);
	}

	function manage_data_refund(){
		$value = $this->afg_m_cost->manage_data_refund($_POST);
		echo json_encode($value);
	}
	
	function get_global_cost(){

		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
		$sord = isset($_POST['sord'])?$_POST['sord']:'desc';
      
      $start = $batas * $hal - $batas;
		$start = ($start<0)?0:$start;
		$_POST['start'] = $start;
		
		$q = $this->afg_m_cost->get_global_cost($_POST,false);
		$query = $this->afg_m_cost->get_global_cost($_POST,true);
		$hitung = count($q);
	 
		if( $hitung > 0 ) {
		   $total_hal = ceil($hitung/$batas);
		} else {
		   $total_hal = 0;
		}
	 
		if ($hal > $total_hal) $hal=$total_hal;
		
	 	$data = new stdClass();	 
		$data->page = $hal;
		$data->total = $total_hal;
		$data->records = $hitung;
		$i=0;

		foreach($query as $row) {
		    $data->rows[$i]['id']=$row->id;
		    $data->rows[$i]['cell']=array(
		    		$row->job_no,
		    		$row->code_service, 
		    		$row->item_service,
		    		$row->qty,
		    		$row->container,  
		    		$row->currency, 
		    		$row->unit_price,
		    		$row->price, 
		    		$row->prep_coll, 
		    		$row->payeble_to, 
		    		$row->group,
					$row->created_by,
					$row->created_at,
					$row->updated_by,
					$row->updated_at,
		    );
		    $i++;
		}
		echo json_encode($data);
	}
	
	function manage_global(){
		$mode = $this->input->post('oper');
		$id = $this->input->post('id');
		
		$data = array (
			'job_no' 			=> $this->input->post('job_no'),
			'code_service'		=> $this->input->post('code_service'),
			'item_service' 	=> $this->input->post('item_service'),
			'qty' 				=> $this->input->post('qty'),
			'container' 		=> $this->input->post('container'),
			'currency' 			=> $this->input->post('currency'),	
			'unit_price' 		=> $this->input->post('unit_price'),
			'price' 				=> $this->input->post('qty') * $this->input->post('unit_price'),
			'prep_coll'			=> $this->input->post('prep_coll'),
			'payeble_to' 	=> $this->input->post('payeble_to'),
			'group' 			=> $this->input->post('group'),
		);
		
		if($mode == 'add')
		{
			$this->access_control->require_feature('stuffing_global_cost', 'create', true);
			$status = $this->afg_m_cost->add_global($data);
			echo json_encode(array('status'=>$status,'mode'=>'add'));
		}
		else if($mode == 'edit')
		{
			$this->access_control->require_feature('stuffing_global_cost', 'edit', true);
			$status = $this->afg_m_cost->update_global($data,$id);
			echo json_encode(array('status'=>$status,'mode'=>'edit'));
		}	
		else if($mode == 'del')
		{
			$this->access_control->require_feature('stuffing_global_cost', 'delete', true);
			$status = $this->afg_m_cost->delete_global($id);
			echo json_encode(array('status'=>$status,'mode'=>'delete'));
		}	
	}
	
	function tampil_data_table(){
	
		
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
		$sord = isset($_POST['sord'])?$_POST['sord']:'desc';
      
      $start = $batas * $hal - $batas;
		$start = ($start<0)?0:$start;
		$_POST['start'] = $start;
		
		$q = $this->afg_m_cost->tampil_data_table($_POST,false);
		$query = $this->afg_m_cost->tampil_data_table($_POST,true);
		//print_r($q);
		$hitung = count($q);
	 
		if( $hitung > 0 ) {
		   $total_hal = ceil($hitung/$batas);
		} else {
		   $total_hal = 0;
		}
	 
		if ($hal > $total_hal) $hal=$total_hal;
		
	 	$data = new stdClass();	 
		$data->page = $hal;
		$data->total = $total_hal;
		$data->records = $hitung;
		$i=0;

		foreach($query as $row) {
			if(is_numeric($row->qty) && is_numeric($row->unit_price)){
			$price = $row->qty  * $row->unit_price;}
			else {
			$price = '';
			}
			$data->rows[$i]['id']=$row->id;
		   $data->rows[$i]['cell']=array(
		    		$row->code_service,
		    		$row->item_service,  
		    		$row->qty, 
		    		$row->currency,
		    		$row->unit_price,
		    		$price,
		    		$row->prep_coll, 
		    		$row->payable_to, 
		    		$row->remark,
		    		$row->g,
					$row->status,
					$row->ppn,
					$row->created_by,
					$row->created_at,
					$row->updated_by,
					$row->updated_at,
		    );
		    $i++;
		}
		echo json_encode($data);
	}
	
	function manage_header_cost(){
		$data = $this->afg_m_cost->update_header_cost($_POST);
		echo json_encode($data);	
	}
	
	
	function clear_refund()
	{
		$kode_ref = $this->input->post('kode_ref');
		$status = $this->afg_m_cost->clear_refund($kode_ref);
		echo json_encode(array('status'=>$status,'mode'=>'clear table'));
	}
}

/*
tabel REFOUNT 1 TARIK DARI selling rate > > itemservice.discount
tabel REFOUNT 2 TARIK DARI selling rate > > itemservice.sales comision
*/