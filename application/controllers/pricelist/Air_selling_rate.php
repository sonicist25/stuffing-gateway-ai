<?php
class Air_selling_rate extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->library('fungsi');
		$this->fungsi->restrict();
		$this->load->model(array('reference/port_destination_model','reference/agent_company_model','pricelist/air_rate_model','user_model'));
		$this->fungsi->dbx = $this->air_rate_model->dbx;
	}
	
	function index() 
	{
		$this->fungsi->auth(25,'Harga Jual');
		$data = array( 'menu_navigation' => 'navigation', 
							'main_view' => 'pricelist/v_selling_air',
							'title' => 'Harga Jual Air Freight',
							'rowList'=>'',
		);
		$this->load->view('afg_template', $data);
	}
	
	function open_form() 
	{
		$data = '';
		$this->load->view('pricelist/v_harga_jual_form', $data);
	}
	
	function open_form_quotation() 
	{
		$data = '';
		$this->load->view('pricelist/v_harga_jual_form', $data);
	}
	
	function cetak_form() 
	{
		$this->load->library(array('fpdf', 'fungsi'));
		$this->fpdf->FPDF('P','mm','A4');
		$this->fpdf->AddPage();
		$this->fpdf->SetFont('Arial','',8);
		
		$this->fpdf->Output('HARGA JUAL '. date('d-M-Y'),'I');
	}
	function get_selling_air()
	{
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
	 	$sord = isset($_POST['sord'])?$_POST['sord']:'';	 
	 			 	
		$start = $batas * $hal - $batas;
		$start = ($start<0)?0:$start;
		$_POST['start'] = $start;
				
		$q = $this->air_rate_model->get_selling_rate($_POST,false);
		$query = $this->air_rate_model->get_selling_rate($_POST,true);	 
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
		    $data->rows[$i]['id']=$row->id_shipper;
		    $data->rows[$i]['cell']=array(
		    									$row->shipper_id,
												$row->shipper_name,
												$this->fungsi->get_user_by_id($row->sales),
											);
		    $i++;
		}
		echo json_encode($data);
	}
	function get_table_air_selling(){
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
		$sord = isset($_POST['sord'])?$_POST['sord']:'';	   	   
		$start = $batas * $hal - $batas;
		$start = ($start<0)?0:$start;
		$_POST['start'] = $start; 		
		$q = $this->air_rate_model->get_table_selling($_POST,false);
		$query = $this->air_rate_model->get_table_selling($_POST,true);	 	
		$misc= $this->air_rate_model->get_value_emkl($_POST['id_selling']);
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
		if ($misc == 0){
		foreach($query as $row) {
		    $data->rows[$i]['id']=$row->id;
		    $data->rows[$i]['cell']=array(		    
		    									$row->of_emkl,
		    									$row->air_line,
		    									$row->nama_service,
		    									$row->destination,
												$row->type,
												$row->currency,
												$row->empatlima,
												$row->seratus,
												$row->tigaratus,
												$row->limaratus,
												$row->seribu,
												$row->tigaribu,
												$row->limaribu,
												$row->rebate,
												$row->invoice_db,
											);
		    $i++;
		}
	}else{
		foreach($query as $row) {
		    $data->rows[$i]['id']=$row->id;
		    $data->rows[$i]['cell']=array(		    
		    									$row->of_emkl,
		    									$row->nama_service,
												$row->invoice_db,
												$row->type,
		    									$row->destination,
												$row->currency,
												$row->rebate,
												$row->price,
												$row->remarks,
											);
		    $i++;
		}

	}
		echo json_encode($data);
	}
	function get_table_listing(){
		
		//$shipper_id = $this->input->post('shipper_id');
		//$user_id = $this->session->userdata('user_id');
		
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
		$sord = isset($_POST['sord'])?$_POST['sord']:'';
				
		$start = $batas * $hal - $batas;
		$start = ($start<0)?0:$start;			
		$_POST['start'] = $start;
					
		$q = $this->air_rate_model->get_table_listing($_POST,false);
		$query = $this->air_rate_model->get_table_listing($_POST,true);
					 
		$hitung = count(array($q));	 
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
		    $data->rows[$i]['id']=$row->id_selling;
		    $data->rows[$i]['cell']=array(
		    									$row->id_selling,
												$row->ref_no,
		    									$this->fungsi->jin_date_str($row->tanggal),
												$this->fungsi->jin_date_str($row->valid),
												$row->kepada,
												$row->attn,
												$row->of_emkl,
												$this->user_model->get_user_by_id($row->dari),
											);
		    $i++;
		}
		echo json_encode($data);
	}
	function open_form_selling()
	{
		$data['shipper_id'] =  $this->input->post('shipper_id');
		$data['shipper_name'] =  $this->input->post('shipper_name');
		$data['id_selling'] = $this->input->post('id_selling');
		$data['sales'] =  $this->input->post('sales');
		$this->load->view('pricelist/v_air_rate_shipper', $data);
	}
		
	function get_selling() 
	{
		$post = array(
			'user_id' => $this->session->userdata('user_id'),
			'shipper_id' => $this->input->post('shipper_id'),
			'id'		=> $this->input->post('idSelling'),
			'mode'		=> $this->input->post('mode'),
		);	
		$data = $this->air_rate_model->get_selling($post);
		echo json_encode($data);
	}
	
	function manage_data_selling(){
		$mode = $this->input->post('oper');
		$id = $this->input->post('id');
		$data = array (
			'of_emkl'=> $this->input->post('of_emkl'),
			'kode_shipper' => $this->input->post('kode_shipper'),
			'ref_no' => $this->input->post('ref_no'),
			'tanggal' => $this->fungsi->jin_date_sql($this->input->post('tanggal')),
			'valid' => $this->fungsi->jin_date_sql($this->input->post('valid')),
			'dari' => $this->input->post('dari'),
			'kepada' => $this->input->post('kepada'),
			'attn' => $this->input->post('attn'),
			'peti_kemas' => $this->input->post('peti_kemas'),			
			'remarks' => $this->input->post('remarks'),
			'region_id'=>$this->session->userdata('region_id'),
		);
		
		if($mode == 'add')
		{
			$status = $this->air_rate_model->add_data_selling($data);
			echo json_encode(array('status'=>$status,'mode'=>'add'));
		}
		else if($mode == 'edit')
		{
			$status = $this->air_rate_model->update_data_selling($data,$id);
			echo json_encode(array('status'=>$status,'mode'=>'edit'));
		}	
		else if($mode == 'del')
		{
			$status = $this->air_rate_model->delete_data_selling_air($id);
			echo json_encode(array('status'=>$status,'mode'=>'delete'));
		}	
	}

	function manage_data(){
		if ($_POST['oper'] == 'add'){
		$status = $this->air_rate_model->insert_data($_POST);
		}else if ($_POST['oper'] == 'edit'){
		$status = $this->air_rate_model->saving_data($_POST);
		}else{
		$status = $this->air_rate_model->delete_data($_POST);	
		}
		// print_r($this->dbx->last_query());
		// die();
		echo json_encode(array('status'=>$status,'mode'=>$_POST['oper']));
	}
	
	
}

