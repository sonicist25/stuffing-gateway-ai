<?php
class Shipper_import extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->library('fungsi');
		$this->fungsi->restrict();
		$this->load->model('reference/shipper_import_model');
	}

	function index() 
	{
		$this->fungsi->auth(18,'Shipper Import');
		$data = array('menu_navigation' => 'navigation', 'main_view' => 'reference/v_shipper_import', 'h1_title' => 'Management Warehose', 'title' => 'Management Warehose' );
		$this->load->view('afg_template', $data);
	}
	
	function getDatarefshipperimport()
	{
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'shipper_import_code';
	 
		if(!$sidx) $sidx =1;
		$q = $this->shipper_import_model->getDatarefshipperimport();
	 
		$hitung = count(array($q));
	 
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
		    $data->rows[$i]['id']=$row->shipper_import_id;
		    $data->rows[$i]['cell']=array(
												$row->shipper_import_code,
												$row->shipper_import_name,
												$row->shipper_import_address,
												);
		    $i++;
		}
		echo json_encode($data);
	}

	function manage_data() 
	{
		$wh_code = $this->input->post('shipper_import_code');
		$wh_name = $this->input->post('shipper_import_name');
		$wh_address = $this->input->post('shipper_import_address');
 		$id = $this->input->post('id');
 		$opr = $this->input->post('oper'); 		
 		
		if($opr=='add')
 		{
 			$this->shipper_import_model->insert_shipper_import($wh_code, $wh_name, $wh_address);
		}
		else if($opr=='edit')
 		{
 			$this->shipper_import_model->update_shipper_import($wh_code, $wh_name, $wh_address, $id);
 		}
		else if($opr=='del')
 		{
			 $this->shipper_import_model->delete_shipper_import($id);
 		}	
	}
}
