<?php
class Afg_c_payment_voucher extends CI_Controller
{
	var $title = 'Payment Voucher';
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
		$this->load->library('FPDF_MC_Table');	
	}

	function index(){
		die();
	}
	
	function saving_cost()
	{
		$data = $this->afg_m_cost->saving_cost($_POST);
		echo json_encode($data);
	}
	
	function receive_data(){
		$data = $this->afg_m_income->getReceiveFrom();
      //print_r($data);
	}
	
	function tarik_data_refund(){
		$data = $this->afg_m_cost->tarik_data_refund($_POST);
      //print_r(array('post'=>$_POST,'get'=>$data));
	}
	
	function tarik_cost(){
		$data = $this->afg_m_cost->tarik_cost($_POST);
      //print_r(array('post'=>$_POST,'get'=>$data));
	}
	
	function tampil_table_pv(){
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
		$sord = isset($_POST['sord'])?$_POST['sord']:'desc';
      
      $start = $batas * $hal - $batas;
		$start = ($start<0)?0:$start;
		$_POST['start'] = $start;
		
		$q = $this->afg_m_cost->tampil_table_pv($_POST,false);
		$query = $this->afg_m_cost->tampil_table_pv($_POST,true);
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
				$price,
		    	$row->prep_coll,
		    	$row->payable_to,
		    	$row->remark,
		    	$row->g,
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
		$price = $this->input->post('price');
		$qty = $this->input->post('qty');
		
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
	
	function open_form_payment(){
		$data['id'] = $this->input->post('rowid');
		$data['kode_ref'] = $this->input->post('kode_ref');
		$this->load->view('trucking/form_pv',$data);
	}
	
	function open_form_global(){
		$data['job_no']= $this->input->post('ref_no');
		$this->load->view('trucking/form_global_cost',$data);
	}
	
	function open_form_global_edit(){
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
			$status = $this->afg_m_cost->add_global($data);
			echo json_encode(array('status'=>$status,'mode'=>'add'));
		}
		else if($mode == 'edit')
		{
			$status = $this->afg_m_cost->update_global($data,$id);
			echo json_encode(array('status'=>$status,'mode'=>'edit'));
		}	
		else if($mode == 'del')
		{
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
		    );
		    $i++;
		}
		echo json_encode($data);
	}
	
	function manage_header_cost(){
		echo json_encode(array('status'=>1));
	}
	
	function delete_data_refund_discount(){
		
	}
	
	function delete_data_refund_(){
		
	}
	
	function clear_refund()
	{
		$kode_ref = $this->input->post('kode_ref');
		$status = $this->afg_m_cost->clear_refund($kode_ref);
		echo json_encode(array('status'=>$status,'mode'=>'clear table'));
	}
	
	function print_data(){
	$pdf=new FPDF_MC_Table();	
		$pdf->AddPage('L','Letter');
		
		$pdf->SetFont('Arial','B',8);
		$pdf->SetMargins(5,0);
		
		$pdf->SetXY(5, 5);
		$pdf->write(0, 'PT. GATEWAY CONTAINER LINE');
		$pdf->Output("PRINT-REKAP-",'I');
	}	
}


/*
tabel REFOUNT 1 TARIK DARI selling rate > > itemservice.discount
tabel REFOUNT 2 TARIK DARI selling rate > > itemservice.sales comision
*/