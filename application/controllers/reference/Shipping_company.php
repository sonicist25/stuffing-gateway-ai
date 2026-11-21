<?php
class Shipping_Company extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->library('fungsi');
		$this->fungsi->restrict();
		$this->load->model('reference/shipping_company_model');
		$this->fungsi->dbx = $this->shipping_company_model->dbx;
	}

	function index() {
		$this->fungsi->auth(9,'Shipping Company');
		$data = array('menu_navigation' => 'navigation', 'main_view' => 'reference/v_shipping_company', 'h1_title' => 'Management Warehose', 'title' => 'Management Warehose' );
		$this->load->view('afg_template', $data);
	}
	
	function getDatarefshippingcompany(){
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
		$sord = isset($_POST['sord'])?$_POST['sord']:'';
				
		$start = $batas * $hal - $batas;
		$start = ($start<0)?0:$start;			
		$_POST['start'] = $start;
		
		$q = $this->shipping_company_model->getDatarefshippingcompany($_POST,false);
		$query = $this->shipping_company_model->getDatarefshippingcompany($_POST,true);
	 
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
		    $data->rows[$i]['id']=$row->shipping_company_id;
		    $data->rows[$i]['cell']=array(
												$row->shipping_company_code,
												$row->shipping_company_name,
												$row->type_carrier,
												$row->shipping_company_cp,
												$row->shipping_company_address,
												$row->shipping_company_remark,
												$row->shipping_company_npwp,
												);
		    $i++;
		}
		echo json_encode($data);
	}

	function manage_data() {
		$value = $this->shipping_company_model->manage_data($_POST);
		echo json_encode($value);	
	}
}
