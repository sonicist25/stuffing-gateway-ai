<?php
class Baserate_export extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->library('fungsi');
		$this->fungsi->restrict();
		$this->load->model('reference/baserate_export_model');
	}

	function index() 
	{
		$this->fungsi->auth(16,'Baserate Export');
		$data = array('menu_navigation' => 'navigation', 'main_view' => 'reference/v_baserate_export', 'h1_title' => 'Management Warehose', 'title' => 'Management Warehose' );
		$this->load->view('afg_template', $data);
	}
	
	function getDatarefBaserateexport()
	{
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'baserate_export_code';
	 
		if(!$sidx) $sidx =1;
		$q = $this->baserate_export_model->getDatarefBaserateexport();
	 
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
		    $data->rows[$i]['id']=$row->baserate_export_id;
		    $data->rows[$i]['cell']=array(
												$row->baserate_export_code,
												);
		    $i++;
		}
		echo json_encode($data);
	}

	function manage_data() 
	{
		$wh_code = $this->input->post('baserate_export_code');
 		$id = $this->input->post('id');
 		$opr = $this->input->post('oper'); 		
 		
		if($opr=='add')
 		{
 			$this->baserate_export_model->insert_baserate_export($wh_code);
		}
		else if($opr=='edit')
 		{
 			$this->baserate_export_model->update_baserate_export($wh_code, $id);
 		}
		else if($opr=='del')
 		{
			 $this->baserate_export_model->delete_baserate_export($id);
 		}	
	}
}
