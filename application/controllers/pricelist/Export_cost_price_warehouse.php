<?php
class Export_cost_price_warehouse extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->fungsi->restrict();
		$this->load->model(array(
			'afg_m_cities',
			'pricelist/export_cost_price_warehouse_model',
			'reference/type_container_model'
		));
	}
	
	function manage_header()
	{
		$value = $this->export_cost_price_warehouse_model->manage_header($_POST);
		echo json_encode($value);
	}
	
	function manage_detail()
	{
		$value = $this->export_cost_price_warehouse_model->manage_detail($_POST);
		echo json_encode($value);
	}
	
	function new_data()
	{
		$value = $this->export_cost_price_warehouse_model->new_data($_POST);
		echo json_encode($value);
	}
	
	function index() 
	{
		$this->fungsi->auth(32,'export_Cost_Price_Warehouse');
		$data = array('menu_navigation' => 'navigation', 'main_view' => 'pricelist/export/v_price_warehouse',
			'title' => 'Cost Price Export','table_title' => 'COST WAREHOUSE EXPORT',
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
		$mode = $this->input->post('oper');	
		if($mode == 'last')
			$data = $this->export_cost_price_warehouse_model->last($_POST);
		else if($mode == 'prev')
			$data = $this->export_cost_price_warehouse_model->prev($_POST);
		else if($mode == 'next')
			$data = $this->export_cost_price_warehouse_model->next($_POST);
		else if($mode == 'pilih')
			$data = $this->export_cost_price_warehouse_model->pilih($_POST);
			
		if($data!=false) {
			$data->date = $this->fungsi->jin_date_str($data->date);
			$data->id_first = $this->export_cost_price_warehouse_model->pilih_id(array('min',$_POST['shipping_co']));	
			$data->id_last = 	$this->export_cost_price_warehouse_model->pilih_id(array('max',$_POST['shipping_co']));
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
	
	function table_detail() 
	{
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
	 	$sord = isset($_POST['sord'])?$_POST['sord']:'';
	 	$start = $batas * $hal - $batas;
		$start = ($start<0)?0:$start;
		$_POST['start'] = $start;
		$q 		= $this->export_cost_price_warehouse_model->table_detail($_POST,false) ;
		$query 	= $this->export_cost_price_warehouse_model->table_detail($_POST,true) ;
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
												$row->container,
												$row->price,
											);
		    $i++;
		}
		echo json_encode($data);
	}
}
