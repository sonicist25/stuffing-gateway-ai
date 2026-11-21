<?php
class Type_Container extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->library('fungsi');
		$this->fungsi->restrict();
		$this->load->model('reference/type_container_model');
		$this->fungsi->dbx = $this->type_container_model->dbx;
	}

	function index() {
		$this->fungsi->auth(1,'Type Container');
		$data = array('menu_navigation' => 'navigation', 'main_view' => 'reference/v_type_container', 'h1_title' => 'Type Container', 'title' => 'Type Container' );
		$this->load->view('afg_template', $data);
	}
	
	function getDatareftypecontainer(){
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
	 
		$start = $batas*$hal - $batas;
		$start = ($start<0)?0:$start;
		$_POST['start']=$start;
		
		$q = $this->type_container_model->getDatareftypecontainer($_POST,$limit=false);
		$query = $this->type_container_model->getDatareftypecontainer($_POST,$limit=true);
	 
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
		    $data->rows[$i]['id']=$row->typecontainer_id;
		    $data->rows[$i]['cell']=array(
												$row->typecontainer_name,
												);
		    $i++;
		}
		echo json_encode($data);
	}

	function manage_data() {
		$wh_name = $this->input->post('typecontainer_name');
		
 		$id = $this->input->post('id');
 		$opr = $this->input->post('oper'); 		
 		
		if($opr=='add')
 		{
 			$this->type_container_model->insert_type_container($wh_name);
		}
		else if($opr=='edit')
 		{
 			$this->type_container_model->update_type_container($wh_name, $id);
 		}
		else if($opr=='del')
 		{
			 $this->type_container_model->delete_type_container($id);
 		}	
	}
}
