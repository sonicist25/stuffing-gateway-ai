<?php
class Type_Incoterms extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->library('fungsi');
		$this->fungsi->restrict();
		$this->load->model('reference/type_incoterms_model');
	}

	function index() {
		$this->fungsi->auth(48,'Type Incoterms');
		$data = array('menu_navigation' => 'navigation', 'main_view' => 'reference/incoterms', 'h1_title' => 'Incoterms', 'title' => 'Incoterms' );
		$this->load->view('afg_template', $data);
	}
	
	function getDatarefincoterms(){
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
	 
		$start = $batas*$hal - $batas;
		$start = ($start<0)?0:$start;
		$_POST['start']=$start;
		
		$q = $this->type_incoterms_model->getDatarefincoterms($_POST,$limit=false);
		$query = $this->type_incoterms_model->getDatarefincoterms($_POST,$limit=true);
	 
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
												$row->incoterms_code,
												$row->incoterms_name,
												);
		    $i++;
		}
		echo json_encode($data);
	}

	function manage_data() {
		$term = $this->input->post('incoterms_name');
		$term_code = $this->input->post('incoterms_code');
 		$id = $this->input->post('id');
 		$opr = $this->input->post('oper'); 		
 		
		if($opr=='add')
 		{
 			$this->type_incoterms_model->insert_incoterms($term,$term_code);
		}
		else if($opr=='edit')
 		{
 			$this->type_incoterms_model->update_incoterms($term,$term_code,$id);
 		}
		else if($opr=='del')
 		{
			 $this->type_incoterms_model->delete_incoterms($id);
 		}	
	}
}
