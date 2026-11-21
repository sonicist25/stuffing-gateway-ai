<?php
class Import_selling_rate extends CI_Controller
{
	function __construct(){
		parent::__construct();
		$this->load->library('fungsi');
		$this->fungsi->restrict();
		$this->load->model('pricelist/import_selling_rate_model');
		$this->fungsi->dbx = $this->import_selling_rate_model->dbx;
		$this->dbx = $this->import_selling_rate_model->dbx;
	}
	
	function index() {
		$this->fungsi->auth(34,'Import_selling_rate');
		$data = array('menu_navigation' => 'navigation', 'main_view' => 'pricelist/v_selling_import',
			'title' => 'Selling Import','table_title' => 'SELLING IMPORT',
			'rowList'=>'',
			 );
		$this->load->view('afg_template', $data);
	}
	
	function save_data_detail()
	{
		$value = $this->import_selling_rate_model->save_data_detail($_POST);
		echo json_encode($value);
	}
	
	function insert_new_data()
	{
		$value = $this->import_selling_rate_model->insert_new_data($_POST);
		echo json_encode($value);
	}
	
	function open_form_selling() {
		$data['shipper_id'] =  $this->input->post('shipper_id');
		$data['shipper_name'] =  $this->input->post('shipper_name');
		$data['id_selling'] = $this->input->post('id_selling');
		$this->load->view('pricelist/v_selling_import_form', $data);
	}
	
	function get_data_consignee(){
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
		$sord = isset($_POST['sord'])?$_POST['sord']:'';
				
		$start = $batas * $hal - $batas;
		$start = ($start<0)?0:$start;			
		$_POST['start'] = $start;
		
      $q = $this->import_selling_rate_model->get_data($_POST,false);
      $query = $this->import_selling_rate_model->get_data($_POST,true);
	 
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
												$this->fungsi->get_user_by_id_array($row->sales),
											);
		    $i++;
		}
		echo json_encode($data);
	}
	
	function get_data_form(){
		$data = $this->import_selling_rate_model->get_data_form($_POST);
		//print_R($this->dbx->last_query());
		//die();
		if($data) {
			echo json_encode($data);
		} else {
			echo json_encode(array(
				'ref_no'=>'','id'=>'','first_id'=>'','last_id'=>'','sales_id'=>''
				)
			);
		}
	}
	
	function manage_data_form(){
		$data = $this->import_selling_rate_model->manage_data_form($_POST);
		//print_R($this->dbx->last_query());
		//die();
		echo json_encode(array('status'=>$data,'oper'=>$_POST['oper']));
	}
	
	function get_table_detail(){
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
	   $sord = isset($_POST['sord'])?$_POST['sord']:'';
	   
		if(!$sidx) $sidx =1;
		$q = $this->import_selling_rate_model->get_table_detail($_POST);
	 
		$hitung = count((array)$q);
	 
		if( $hitung > 0 ) {
		    $total_hal = ceil($hitung/$batas);
		} else {
		    $total_hal = 0;
		}
	 
		if ($hal > $total_hal) $hal=$total_hal;
	 
		$start = $batas*$hal - $batas;
		$start = ($start<0)?0:$start;		
		
		$data = new stdClass();	 
		$data->page = $hal;
		$data->total = $total_hal;
		$data->records = $hitung;
		$i=0;
		foreach($q->result() as $row) {
		    $data->rows[$i]['id']=$row->id;
		    $data->rows[$i]['cell']=array(
		    									$row->service,
		    									$row->port_loading,
												$row->pre_col,
												$row->type,
												$row->container,
												$row->currency,
												$row->unit_price,
												$row->mchgs,
											);
		    $i++;
		}
		echo json_encode($data);
	}

	function manage_data_detail(){
		$data = $this->import_selling_rate_model->manage_data_detail($_POST);
		//print_r($this->dbx->last_query());
		echo json_encode(array('status'=>$data,'oper'=>$_POST['oper']));
	//	//print_r($_POST);
	}

	function get_table_listing(){
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
		$sord = isset($_POST['sord'])?$_POST['sord']:'';
	 
		if(!$sidx) $sidx =1;		
		$q = $this->import_selling_rate_model->get_table_listing($_POST);
			 
		$hitung = count((array)$q);
	 
		if( $hitung > 0 ) {
		    $total_hal = ceil($hitung/$batas);
		} else {
		    $total_hal = 0;
		}
	 
		if ($hal > $total_hal) $hal=$total_hal;
	 
		$start = $batas*$hal - $batas;
		$start = ($start<0)?0:$start;		
		
		$data = new stdClass();	 
		$data->page = $hal;
		$data->total = $total_hal;
		$data->records = $hitung;
		$i=0;
		foreach($q->result() as $row) {
		    $data->rows[$i]['id']=$row->id;
		    $data->rows[$i]['cell']=array(
		    									'IMP-'. date('y').'-'.$row->ref_no,
		    									$this->import_selling_rate_model->get_consignee_name($row->consignee_id),
												$this->fungsi->get_user_by_id($row->sales_id),
											);
		    $i++;
		}
		echo json_encode($data);
	}

}
