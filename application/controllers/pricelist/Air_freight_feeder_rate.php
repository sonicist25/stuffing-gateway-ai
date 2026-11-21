<?php
class Air_freight_feeder_rate extends CI_Controller
{
	function __construct(){
		parent::__construct();
		$this->load->library('fungsi');
		//$this->fungsi->restrict();
		$this->load->model(array(
			'afg_m_cities',
			'pricelist/export_feeder_rate_model',
		));
		$this->fungsi->dbx = $this->export_feeder_rate_model->dbx;
	}
	
	function get_table_data() 
	{
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
	 	$sord = isset($_POST['sord'])?$_POST['sord']:'';
	 	$start = $batas * $hal - $batas;
		$start = ($start<0)?0:$start;
		$_POST['start'] = $start;
		$q = $this->export_feeder_rate_model->get_table_airline($_POST,false) ;
		//print_r($this->dbx->last_query());
		//die();
		$query = $this->export_feeder_rate_model->get_table_airline($_POST,true) ;
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
		    									$row->airline,
												$row->airline_code,
		    									$row->shc,
												$row->commodity,
												$row->origin,
												$row->origin_code,
												$row->destination,
												$row->destination_code,
												$row->minimum,
												$row->dasar,
												$row->empatlima,
												$row->seratus,
												$row->tigaratus,
												$row->limaratus,
												$row->seribu,
												$row->valid,
												$row->remarks,
												$row->region_id,
											);
		    $i++;
		}
		echo json_encode($data);
	}

	
	function manage_detail()
	{
		$value = $this->export_feeder_rate_model->manage_airline_detail($_POST);
		echo json_encode($value);
	}
	
	function index() 
	{
		$this->fungsi->auth(28,'Export Cost Feeder Rate');
		$data = array('menu_navigation' => 'navigation', 'main_view' => 'pricelist/v_export_airline_rate',
			'title' => 'Feeder Rate','table_title' => 'COST AIRLINE',
			'rowList'=>'',
			 );
		$this->load->view('afg_template', $data);
	}

	
}
