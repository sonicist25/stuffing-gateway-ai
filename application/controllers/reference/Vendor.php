<?php
class Vendor extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->library('fungsi');
		$this->fungsi->restrict();
		$this->load->model('reference/vendor_model');
		$this->fungsi->dbx = $this->vendor_model->dbx;
	}

	function index() {
		$this->fungsi->auth(62,'Vendor');
		$data = array('menu_navigation' => 'navigation', 'main_view' => 'reference/v_vendor', 'h1_title' => 'Management Vendor', 'title' => 'Management Vendor' );
		$this->load->view('afg_template', $data);
	}
	
	function getDataVendor(){
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
		$sord = isset($_POST['sord'])?$_POST['sord']:'';
				
		$start = $batas * $hal - $batas;
		$start = ($start<0)?0:$start;			
		$_POST['start'] = $start;
		
		$q = $this->vendor_model->getDataVendor($_POST,false);
		$query = $this->vendor_model->getDataVendor($_POST,true);
	 
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
			$data->rows[$i]['id']=$row->vendor_id;
			////print_r($row);
			//die();

		    $data->rows[$i]['cell']=array(
												$row->vendor_code,
												$row->vendor_name,
												$row->vendor_address,
												$row->type,
												);
		    $i++;
		}
		echo json_encode($data);
	}

	function manage_data() {
		$value = $this->vendor_model->manage_data($_POST);
		//Print_r($this->dbx->last_query());
		//die();
		echo $value;	
	}
}
