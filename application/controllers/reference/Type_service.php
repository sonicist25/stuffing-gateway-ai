<?php
class Type_Service extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->library('fungsi');
		$this->fungsi->restrict();
		$this->load->model('reference/type_service_model');
	}

	function index() {
		$this->fungsi->auth(2,'Type Service');
		$data = array('menu_navigation' => 'navigation', 'main_view' => 'reference/v_type_service', 'h1_title' => 'Management Warehose', 'title' => 'Management Warehose' );
		$this->load->view('afg_template', $data);
	}
	
	function getDatareftypeservice(){
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
	 	$sord = isset($_POST['sord'])?$_POST['sord']:'';
	 	
		$start = $batas*$hal - $batas;
		$start = ($start<0)?0:$start;
		$_POST['start']=$start;
		
		$q = $this->type_service_model->getDatareftypeservice($_POST,$limit=false);
		$query = $this->type_service_model->getDatareftypeservice($_POST,$limit=true);
	 
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
		    $data->rows[$i]['id']=$row->typeservice_id;
		    $data->rows[$i]['cell']=array(
												$row->typeservice_code,
												$row->typeservice_name,
												$row->typeservice_ppn,
												);
		    $i++;
		}
		echo json_encode($data);
	}

	function manage_data() 
	{
		$wh_code = $this->input->post('typeservice_code');
		$wh_name = $this->input->post('typeservice_name');
		$wh_ppn = $this->input->post('typeservice_ppn');
 		$id = $this->input->post('id');
 		$opr = $this->input->post('oper'); 		
 		
		if($opr=='add')
 		{
 			$this->type_service_model->insert_type_service($wh_code, $wh_name, $wh_ppn);
		}
		else if($opr=='edit')
 		{
 			$this->type_service_model->update_type_service($wh_code, $wh_name, $wh_ppn, $id);
 		}
		else if($opr=='del')
 		{
			 $this->type_service_model->delete_type_service($id);
 		}	
	}
}
