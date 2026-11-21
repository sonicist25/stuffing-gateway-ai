<?php
class Stuffing_location extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->library('fungsi');
		$this->fungsi->restrict();
		$this->load->model('reference/stuffing_location_model');
		$this->fungsi->dbx = $this->stuffing_location_model->dbx;
	}

	function index() 
	{
		$this->fungsi->auth(7,'Stuffing Location');
		$data = array('menu_navigation' => 'navigation', 'main_view' => 'reference/v_stuffing_location', 'h1_title' => 'Management Warehose', 'title' => 'Management Warehose' );
		$this->load->view('afg_template', $data);
	}
	
	function getDataStuffing_location()
	{
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'stuffing_location_code';
	 
		if(!$sidx) $sidx =1;
		$q = $this->stuffing_location_model->getDataStuffing_location();
	 
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
		    $data->rows[$i]['id']=$row->id;
		    $data->rows[$i]['cell']=array(
												$row->warehouse_code,
												$row->warehouse_name,
												$row->warehouse_address,
												);
		    $i++;
		}
		echo json_encode($data);
	}

	function manage_data() 
	{
		$wh_code = $this->input->post('warehouse_code');
		$wh_company = $this->input->post('warehouse_name');
		$wh_address = $this->input->post('warehouse_address');
		
 		$id = $this->input->post('id');
 		$opr = $this->input->post('oper'); 		
 		
		if($opr=='add')
 		{
 			$this->stuffing_location_model->insert_stuffing_location($wh_code,$wh_company,$wh_address);
		}
		else if($opr=='edit')
 		{
 			$this->stuffing_location_model->update_stuffing_location($wh_code,$wh_company, $wh_address, $id);
 		}
		else if($opr=='del')
 		{
			 $this->stuffing_location_model->delete_stuffing_location($id);
 		}	
	}
}
