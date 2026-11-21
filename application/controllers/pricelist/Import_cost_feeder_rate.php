<?php
class Import_cost_feeder_rate extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->library('fungsi');
		$this->fungsi->restrict();
		$this->load->model(array(
			'afg_m_cities',
			'pricelist/import_feeder_rate_model',
			'reference/type_container_model'
		));
	}
	
	function index() 
	{
		$this->fungsi->auth(35,'import_cost_feeder_rate');
		$data = array('menu_navigation' => 'navigation', 'main_view' => 'pricelist/import/v_feeder_rate',
			'title' => 'Feeder Rate','table_title' => 'COST FEEDER IMPORT',
			'rowList'=>'',
			 );
		$this->load->view('afg_template', $data);
	}
	
	function open_form() 
	{
		$data['id'] =  $this->input->post('id');
		$data['code'] =  $this->input->post('code');
		$data['name'] =  $this->input->post('name');
		$this->load->view('pricelist/import/v_feeder_rate_shipp', $data);
	}
	
	function get_feeder_rate()
	{
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
	 	$sord = isset($_POST['sord'])?$_POST['sord']:'';
	 	
		if(!$sidx) $sidx =1;
		$q = $this->import_feeder_rate_model->get_feeder_rate();
	 
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
		$shipper_id = $this->input->post('shipper_id');
		$id = $this->input->post('idFeeder');
		$mode = $this->input->post('mode');	
		
		if($mode == 'last')
			$data = $this->import_feeder_rate_model->lastFeeder($shipper_id);
		else if($mode == 'prev')
			$data = $this->import_feeder_rate_model->prevFeeder($shipper_id,$id);
		else if($mode == 'next')
			$data = $this->import_feeder_rate_model->nextFeeder($shipper_id,$id);
		else if($mode == 'pilih')
			$data = $this->import_feeder_rate_model->pilihFeeder($shipper_id,$id);
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
