<?php
class Consignee_import extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->library('fungsi');
		$this->fungsi->restrict();
		$this->load->model('reference/consignee_import_model');
		$this->fungsi->dbx = $this->consignee_import_model->dbx;
	}

	function index() 
	{
		$this->fungsi->auth(17,'Consignee Import');
		$data = array('menu_navigation' => 'navigation', 'main_view' => 'reference/v_consignee_import', 'h1_title' => 'Management Warehose', 'title' => 'Management Warehose' );
		$this->load->view('afg_template', $data);
	}
	
	function getDatarefconsigneeimport()
	{
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'consignee_import_code';
	 
		if(!$sidx) $sidx =1;
		$q = $this->consignee_import_model->getDatarefconsigneeimport();
	 
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
		    $data->rows[$i]['id']=$row->consignee_import_id;
		    $data->rows[$i]['cell']=array(
												$row->consignee_import_code,
												$row->consignee_import_name,
												$row->consignee_import_address,
												$row->fowarder,
												);
		    $i++;
		}
		echo json_encode($data);
	}

	function manage_data() 
	{
		$wh_code = $this->input->post('consignee_import_code');
		$wh_name = $this->input->post('consignee_import_name');
		$wh_address = $this->input->post('consignee_import_address');
 		$id = $this->input->post('id');
 		$opr = $this->input->post('oper'); 		
 		
		if($opr=='add')
 		{
 			$this->consignee_import_model->insert_consignee_import($wh_code, $wh_name, $wh_address);
		}
		else if($opr=='edit')
 		{
 			$this->consignee_import_model->update_consignee_import($wh_code, $wh_name, $wh_address, $id);
 		}
		else if($opr=='del')
 		{
			 $this->consignee_import_model->delete_consignee_import($id);
 		}	
	}
}
