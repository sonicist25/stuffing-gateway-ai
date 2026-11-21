<?php
class Import_cost_price_warehouse extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->library('fungsi');
		$this->fungsi->restrict();
		$this->load->model(array(
			'afg_m_cities',
			'pricelist/export_cost_price_warehouse_model',
			'reference/type_container_model'
		));
	}
	
	function index() 
	{
		$this->fungsi->auth(39,'import_cost_price_warehouse');
		$data = array('menu_navigation' => 'navigation', 'main_view' => 'pricelist/export/v_price_warehouse',
			'title' => 'Cost Price Export','table_title' => 'COST WAREHOUSE IMPORT',
			'rowList'=>'',
			 );
		$this->load->view('afg_template', $data);
	}
	
	function open_form() 
	{
		$data['id'] =  $this->input->post('id');
		$data['code'] =  $this->input->post('code');
		$data['name'] =  $this->input->post('name');
		$this->load->view('pricelist/export/v_price_warehouse_data', $data);
	}
	
	function get_warehouse()
	{
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
	 	$sord = isset($_POST['sord'])?$_POST['sord']:'';
	 	
		if(!$sidx) $sidx =1;
		$q = $this->export_cost_price_warehouse_model->get_warehouse();
	 
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
		    $data->rows[$i]['id']=$row->warehouse_id;
		    $data->rows[$i]['cell']=array(
		    									$row->warehouse_code,
		    									$row->warehouse_name,
											);
		    $i++;
		}
		echo json_encode($data);
	}
	
	function get_data() 
	{
		$shipper_id = $this->input->post('shipper_id');
		$id = $this->input->post('idData');
		$mode = $this->input->post('mode');	
		
		if($mode == 'last')
			$data = $this->export_cost_price_warehouse_model->lastData($shipper_id);
		else if($mode == 'prev')
			$data = $this->export_cost_price_warehouse_model->prevData($shipper_id,$id);
		else if($mode == 'next')
			$data = $this->export_cost_price_warehouse_model->nextData($shipper_id,$id);
		else if($mode == 'pilih')
			$data = $this->export_cost_price_warehouse_model->pilihData($shipper_id,$id);
		if($data) {	
		echo json_encode(array(
			'id' => $data->id,
			'refNo' => $data->ref_no,
			'dateForm' => $this->fungsi->jin_date_str($data->tanggal),
			'portload' => $data->dari,
			'transipment' => $data->kepada,		
		));
		} else {
			echo json_encode(array(
			'id' => '',
			'refNo' => '',
			'dateForm' => '',
			'portload' => '',
			'transipment' => '',		
		));
		}
	}
	
	function get_table_satu() 
	{
		
	}
}
