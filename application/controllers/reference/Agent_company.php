<?php
class Agent_company extends CI_Controller
{
	function __construct(){
		parent::__construct();
		$this->load->library('fungsi');
		$this->fungsi->restrict();
		$this->load->model('reference/agent_company_model');
		$this->fungsi->dbx = $this->agent_company_model->dbx;
	}

	function index(){
		$this->fungsi->auth(10,'Agent Company');
		$data = array('menu_navigation' => 'navigation', 'main_view' => 'reference/v_agent_company', 'h1_title' => 'Management Warehose', 'title' => 'Management Warehose' );
		$this->load->view('afg_template', $data);
	}
	
	function getDataagent_company(){
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
		$sord = isset($_POST['sord'])?$_POST['sord']:'';
				
		$start = $batas * $hal - $batas;
		$start = ($start<0)?0:$start;			
		$_POST['start'] = $start;
		
		$q = $this->agent_company_model->getDataagent_company($_POST,false);
		$query = $this->agent_company_model->getDataagent_company($_POST,true);
	 
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
		    $data->rows[$i]['id']=$row->agent_company_id;
		    $data->rows[$i]['cell']=array(
												$row->agent_company_code,
												$row->agent_company_name,
												$row->agent_company_city,
												$row->agent_company_address,
												$row->agent_company_remark,
												$row->agent_company_npwp,
												$row->penagihan,
										);
		    $i++;
		}
		echo json_encode($data);
	}
	
	function getDataagent_penagihan(){
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
		$sord = isset($_POST['sord'])?$_POST['sord']:'';
				
		$start = $batas * $hal - $batas;
		$start = ($start<0)?0:$start;			
		$_POST['start'] = $start;
		
		$q = $this->agent_company_model->getDataagent_penagihan($_POST,false);
		$query = $this->agent_company_model->getDataagent_penagihan($_POST,true);
	 
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
												$row->agent_company_code,
												$row->agent_company_name,
												$row->agent_company_city,
												$row->agent_company_address
											);
		    $i++;
		}
		echo json_encode($data);
	}

	function manage_data() {
		$value = $this->agent_company_model->manage_data($_POST);
		//print_r($this->dbx->last_query());
		echo json_encode($value);	
	}
	
	function manage_data_penagihan() {
		$value = $this->agent_company_model->manage_data_penagihan($_POST);
		echo json_encode($value);	
	}
}
