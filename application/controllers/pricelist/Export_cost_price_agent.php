<?php
class Export_cost_price_agent extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->fungsi->restrict();
		$this->load->model(array(
			'afg_m_cities',
			'pricelist/export_cost_price_agent_model',
			'reference/type_container_model'
		));
	}
	
	function new_data()
	{
		$value = $this->export_cost_price_agent_model->new_data($_POST);
		echo json_encode($value);
	}
	
	function manage_table_data()
	{
		$value = $this->export_cost_price_agent_model->manage_table_data($_POST);
		echo json_encode($value);
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
		$q = $this->export_cost_price_agent_model->get_table_data($_POST,false) ;
		$query = $this->export_cost_price_agent_model->get_table_data($_POST,true) ;
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
		    									$row->service,
		    									$row->shipper,
												$row->port_loading,
												$row->destination,
												$row->prep_coll,
												$row->c20,
												$row->c40,
												$row->c40hc,
												$row->mchgs,
												$row->hton,
												$row->hcbm,
											);
		    $i++;
		}
		echo json_encode($data);
	}
	
	function table_listing() 
	{
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
	 	$sord = isset($_POST['sord'])?$_POST['sord']:'';
	 	$start = $batas * $hal - $batas;
		$start = ($start<0)?0:$start;
		$_POST['start'] = $start;
		$q = $this->export_cost_price_agent_model->table_listing($_POST,false) ;
		$query = $this->export_cost_price_agent_model->table_listing($_POST,true) ;
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
		    									$row->ref_no,
		    									$row->agent,
												$this->fungsi->jin_date_str($row->date),
												$this->fungsi->jin_date_str($row->valid),
											);
		    $i++;
		}
		echo json_encode($data);
	}
	
	function index() 
	{
		$this->fungsi->auth(30,'export_Cost_Price_Warehouse');
		$data = array('menu_navigation' => 'navigation', 'main_view' => 'pricelist/export/v_price_agent',
			'title' => 'Cost Agent Export','table_title' => 'COST AGENT - EXPORT',
			'rowList'=>'',
			 );
		$this->load->view('afg_template', $data);
	}
	
	function open_form() 
	{
		$data['id'] =  $this->input->post('id');
		$data['code'] =  $this->input->post('code');
		$data['name'] =  $this->input->post('name');
		$this->load->view('pricelist/export/v_price_agent_data', $data);
	}
	
	function get_agent()
	{
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
	 	$sord = isset($_POST['sord'])?$_POST['sord']:'';
	 	
		if(!$sidx) $sidx =1;
		$q = $this->export_cost_price_agent_model->get_agent();
	 
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
		    $data->rows[$i]['id']=$row->agent_company_id;
		    $data->rows[$i]['cell']=array(
		    									$row->agent_company_code,
		    									$row->agent_company_name,
												$row->agent_company_city,
											);
		    $i++;
		}
		echo json_encode($data);
	}
	
	function get_data() 
	{
		$mode = $this->input->post('oper');	
		
		if($mode == 'last')
			$data = $this->export_cost_price_agent_model->lastData($_POST);
		else if($mode == 'prev')
			$data = $this->export_cost_price_agent_model->prevData($_POST);
		else if($mode == 'next')
			$data = $this->export_cost_price_agent_model->nextData($_POST);
		else if($mode == 'pilih')
			$data = $this->export_cost_price_agent_model->pilihData($_POST);
		
		if($data!=false) {
			$data->date = $this->fungsi->jin_date_str($data->date);	
			$data->valid = $this->fungsi->jin_date_str($data->valid);	
			$data->id_first = $this->export_cost_price_agent_model->pilih_id(array('min',$_POST['agent']));	
			$data->id_last = 	$this->export_cost_price_agent_model->pilih_id(array('max',$_POST['agent']));
			echo json_encode($data);
		}
		else {
			$data = new stdClass();
			$data->id="";
			$data->ref_no = "";
			$data->date = "";
			$data->valid = "";
			$data->id_first = "";	
			$data->id_last = 	"";
			$data->agent = $_POST['agent'];
			echo json_encode($data);
		}
		
	}
	
	function manage_data_header()
	{
		$value = $this->export_cost_price_agent_model->manage_data_header($_POST);
		echo json_encode($value);
	}
}
