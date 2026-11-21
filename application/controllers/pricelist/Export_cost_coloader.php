<?php
class Export_cost_coloader extends CI_Controller
{
	function __construct(){
		parent::__construct();
		$this->fungsi->restrict();
		$this->load->model(array(
			'afg_m_cities',
			'pricelist/export_cost_coloader_model',
			'reference/type_container_model'
		));
	}
	
	function table_detail() 
	{
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
	 	$sord = isset($_POST['sord'])?$_POST['sord']:'';
	 	$start = $batas * $hal - $batas;
		$start = ($start<0)?0:$start;
		$_POST['start'] = $start;
		$q 		= $this->export_cost_coloader_model->table_detail($_POST,false) ;
		$query 	= $this->export_cost_coloader_model->table_detail($_POST,true) ;
	 	$hitung 	= count($q);	 
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
		    									$row->item_of_service,
		    									$row->currency,
		    									$row->condition,
		    									$row->destination,
		    									$row->emkl,
		    									$row->type,
		    									$row->price,
		    									$row->remarks,
		    									$row->mchgs,
											);
		    $i++;
		}
		echo json_encode($data);
	}
	
	function manage_header()
	{
		$value = $this->export_cost_coloader_model->manage_header($_POST);
		echo json_encode($value);
	}
	
	function manage_detail()
	{
		$value = $this->export_cost_coloader_model->manage_detail($_POST);
		echo json_encode($value);
	}
	
	function new_data()
	{
		$value = $this->export_cost_coloader_model->new_data($_POST);
		echo json_encode($value);
	}
	
	function index()
	{
		$this->fungsi->auth(29,'export_cost_coloader_rate');
	  	$data = array('menu_navigation' => 'navigation', 'main_view' => 'pricelist/v_export_cost_coloader',
			'title' => 'Cost Coloader','table_title' => 'COST COLOADER - EXPORT',
			'rowList'=>'',
			 );
		$this->load->view('afg_template', $data);
	}
	
	function open_form(){
		$data['id'] =  $this->input->post('id');
		$data['code'] =  $this->input->post('code');
		$data['name'] =  $this->input->post('name');
		$this->load->view('pricelist/v_export_cost_coloader_shipp', $data);
	}
	
	function get_export_feeder_rate(){
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
	 	$sord = isset($_POST['sord'])?$_POST['sord']:'';
	 	
		if(!$sidx) $sidx =1;
		$q = $this->export_feeder_rate_model->get_export_feeder_rate();
	 
		$hitung = count($q);
	 
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
		    $data->rows[$i]['id']=$row->shipping_company_id;
		    $data->rows[$i]['cell']=array(
		    									$row->shipping_company_code,
												$row->shipping_company_name,
											);
		    $i++;
		}
		echo json_encode($data);
	}
	
	function get_data() 
	{
		$mode = $this->input->post('oper');	
		if($mode == 'last')
			$data = $this->export_cost_coloader_model->last($_POST);
		else if($mode == 'prev')
			$data = $this->export_cost_coloader_model->prev($_POST);
		else if($mode == 'next')
			$data = $this->export_cost_coloader_model->next($_POST);
		else if($mode == 'pilih')
			$data = $this->export_cost_coloader_model->pilih($_POST);
			
		if($data!=false) {
			$data->date = $this->fungsi->jin_date_str($data->date);
			$data->id_first = $this->export_cost_coloader_model->pilih_id(array('min',$_POST['shipping_co']));	
			$data->id_last = 	$this->export_cost_coloader_model->pilih_id(array('max',$_POST['shipping_co']));
			echo json_encode($data);
		}
		else {
			$data = new stdClass();
			$data->id="";
			$data->ref_no = "";
			$data->date = "";
			$data->shipping_co = $_POST['shipping_co'];
			$data->id_first = "";	
			$data->id_last = 	"";
			echo json_encode($data);
		}
	}
	
	/*
	function get_table_satu() {
		
	}
	*/
}
