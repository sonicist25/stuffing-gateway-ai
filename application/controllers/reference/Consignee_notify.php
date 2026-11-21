<?php
class Consignee_notify extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->library('fungsi');
		$this->fungsi->restrict();
		$this->load->model('reference/consignee_notify_model');
		$this->fungsi->dbx = $this->consignee_notify_model->dbx;
	}

	function index() {
		$this->fungsi->auth(13,'Consignee Notify');
		$data = array('menu_navigation' => 'navigation', 'main_view' => 'reference/v_consignee_notify', 'h1_title' => 'Management Warehose', 'title' => 'Management Warehose' );
		$this->load->view('afg_template', $data);
	}
	
	function getDatarefconsigneenotify(){
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
		$sord = isset($_POST['sord'])?$_POST['sord']:'';
				
		$start = $batas * $hal - $batas;
		$start = ($start<0)?0:$start;			
		$_POST['start'] = $start;
		
		$q = $this->consignee_notify_model->getDataconsignee_notify($_POST,false);
		$query = $this->consignee_notify_model->getDataconsignee_notify($_POST,true);
	 
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
		    $data->rows[$i]['id']=$row->consignee_notify_id;
		    $data->rows[$i]['cell']=array(
												$row->consignee_notify_code,
												$row->consignee_notify_name,
												$row->consignee_notify_address,
												$row->consignee_notify_npwp,
												);
		    $i++;
		}
		echo json_encode($data);
	}

	function manage_data() {
		$value = $this->consignee_notify_model->manage_data($_POST);
		echo json_encode($value);	
	}
	
	
}
