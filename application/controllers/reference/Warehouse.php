<?php
class Warehouse extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->library('fungsi');
		$this->fungsi->restrict();
		$this->load->model('reference/warehouse_model');
		$this->fungsi->dbx = $this->warehouse_model->dbx;
		$this->dbx = $this->warehouse_model->dbx;
	}

	function index() {
		$this->fungsi->auth(11,'Warehouse');
		$data = array('menu_navigation' => 'navigation', 'main_view' => 'reference/v_warehouse', 'h1_title' => 'Management Warehose', 'title' => 'Management Warehose' );
		$this->load->view('afg_template', $data);
	}
	
	function getDataWarehouse(){
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
		$sord = isset($_POST['sord'])?$_POST['sord']:'';
				
		$start = $batas * $hal - $batas;
		$start = ($start<0)?0:$start;			
		$_POST['start'] = $start;
		
		$q = $this->warehouse_model->getDataWarehouse($_POST,false);
		$query = $this->warehouse_model->getDataWarehouse($_POST,true);
	 
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
			$data->rows[$i]['id']=$row->warehouse_id;
			////print_r($row);
			//die();

		    $data->rows[$i]['cell']=array(
												$row->warehouse_code,
												$row->warehouse_name,
												$row->warehouse_address,
												$row->position,
												);
		    $i++;
		}
		echo json_encode($data);
	}

	function manage_data() {
		$value = $this->warehouse_model->manage_data($_POST);
		json_encode($value);	
	}
}
