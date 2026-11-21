<?php
class Insentive_agent extends CI_Controller
{
	function __construct(){
		parent::__construct();
		$this->load->library('fungsi');
		$this->fungsi->restrict();
		$this->load->model(array('reference/agent_company_model','pricelist/insentive_agent_model',));
		$this->fungsi->dbx = $this->agent_company_model->dbx;
	}
	
	function index() 
	{
		$this->fungsi->auth(26,'Insentive Agent');
		$data = array( 'menu_navigation' => 'navigation', 
							'main_view' => 'pricelist/v_insentive_agent',
							'title' => 'INTENSIVE FROM AGENT',
							'rowList'=>'',
		);
		$this->load->view('afg_template', $data);
	}
	
	function insert_table() 
	{
		$this->insentive_agent_model->add_data_detail($_POST);
	}
	
	function open_form()
	{
		$data = array( 'title' => 'INTENSIVE FROM AGENT');
		$this->load->view('pricelist/v_insentive_agent_form', $data);
	}
	
	function get_insentive()
	{
		$data = $this->insentive_agent_model->get_insentive($_POST)->row();
		if($data) {
			$data->date = $this->fungsi->jin_date_str($data->date);
			$data->first_id = $this->insentive_agent_model->get_first_id();
			$data->last_id = 	$this->insentive_agent_model->get_last_id();		
		} else {
			$data = new stdClass();
			$data->id = '';
			$data->first_id = '';
			$data->last_id = 	'';
		}
		echo json_encode($data);
	}
	
	function table_browse()
	{
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
		$sord = isset($_POST['sord'])?$_POST['sord']:'';
				
		$start = $batas * $hal - $batas;
		$start = ($start<0)?0:$start;			
		$_POST['start'] = $start;
		
		$q = $this->insentive_agent_model->table_browse($_POST,false);
		$query = $this->insentive_agent_model->table_browse($_POST,true);
	 
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
												$this->fungsi->jin_date_str($row->date),
												$row->agent_code,
												$row->id,
											);
		    $i++;
		}
		echo json_encode($data);
	}
	
	function table_detail()
	{
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
	 	$sord = isset($_POST['sord'])?$_POST['sord']:'';
	 	$ref_no = isset($_POST['ref_no'])?$_POST['ref_no']:'';
		
		$start = $batas * $hal - $batas;
		$start = ($start<0)?0:$start;
		$_POST['start'] = $start;
		
		$q = $this->insentive_agent_model->table_detail($_POST,false);
		$query = $this->insentive_agent_model->table_detail($_POST,true);
	 	
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
												$row->countainer,
												$row->destination,
												$row->amount,
												$row->currency,
											);
		    $i++;
		}
		echo json_encode($data);
	}
	
	function manage_data()
	{
		$data = $this->insentive_agent_model->manage_data($_POST);
		echo json_encode($data);
	}
	
	function manage_detail()
	{
		$data = $this->insentive_agent_model->manage_detail($_POST);
		echo json_encode($data);
	}


}

