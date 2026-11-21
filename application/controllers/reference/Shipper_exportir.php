<?php
class Shipper_exportir extends CI_Controller
{
	function __construct(){
		parent::__construct();
		$this->load->library('fungsi');
		$this->fungsi->restrict();
		$this->load->model('reference/shipper_exportir_model');
	}

	function index() {
		$this->fungsi->auth(12,'Shipper Exportir');
		$data = array('menu_navigation' => 'navigation', 'main_view' => 'reference/v_shipper_exportir', 'h1_title' => 'Management Warehose', 'title' => 'Management Warehose' );
		$this->load->view('afg_template', $data);
	}
	
	function getDatarefshipperexportir(){
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
		$sord = isset($_POST['sord'])?$_POST['sord']:'';
				
		$start = $batas * $hal - $batas;
		$start = ($start<0)?0:$start;			
		$_POST['start'] = $start;
	 
		$q = $this->shipper_exportir_model->getDatarefshipperexportir($_POST,false);
	 	$query = $this->shipper_exportir_model->getDatarefshipperexportir($_POST,true);
	 	
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
		    $data->rows[$i]['id']=$row->id_shipper;
		    $data->rows[$i]['cell']=array(
												$row->shipper_id,
												$row->shipper_name,
												$row->shipper_address,
												$row->shipper_npwp,
											);
		    $i++;
		}
		echo json_encode($data);
	}

	function manage_data() {
		$value = $this->shipper_exportir_model->manage_data($_POST);
		echo json_encode($value);
		
		/*
		$wh_code = $this->input->post('shipper_exportir_code');
		$wh_name = $this->input->post('shipper_exportir_name');
		$wh_cp = $this->input->post('shipper_exportir_cp');
		$wh_address = $this->input->post('shipper_exportir_address');
		$wh_remark = $this->input->post('shipper_exportir_remark');
		$wh_qq = $this->input->post('shipper_exportir_qq');
		$wh_marketing = $this->input->post('shipper_exportir_marketing');
 		$id = $this->input->post('id');
 		$opr = $this->input->post('oper'); 		
 		
		if($opr=='add'){
 			$this->shipper_exportir_model->insert_shipper_exportir($wh_code, $wh_name, $wh_cp, $wh_address, $wh_remark, $wh_qq, $wh_marketing);
		}
		else if($opr=='edit'){
 			$this->shipper_exportir_model->update_shipper_exportir($wh_code, $wh_name, $wh_cp, $wh_address, $wh_remark, $wh_qq, $wh_marketing, $id);
 		}
		else if($opr=='del'){
			 $this->shipper_exportir_model->delete_shipper_exportir($id);
 		}	
 		*/
	}


}
