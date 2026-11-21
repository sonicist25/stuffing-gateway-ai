<?php
class Trucking_Company extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->library('fungsi');
		$this->fungsi->restrict();
		$this->load->model('reference/trucking_company_model');
		$this->fungsi->dbx = $this->trucking_company_model->dbx;
		$this->dbx = $this->trucking_company_model->dbx;
	}

	function index() 
	{
		$this->fungsi->auth(8,'Trucking Company');
		$data = array('menu_navigation' => 'navigation', 'main_view' => 'reference/v_trucking_company', 'h1_title' => 'Management Warehose', 'title' => 'Management Warehose' );
		$this->load->view('afg_template', $data);
	}
	
	function getDatareftruckingcompany()
	{
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'trucking_company_code';
	 
		if(!$sidx) $sidx =1;
		$q = $this->trucking_company_model->getDatareftruckingcompany();
	 
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
		    $data->rows[$i]['id']=$row->trucking_company_id;
		    $data->rows[$i]['cell']=array(
												$row->trucking_company_code,
												$row->trucking_company_name,
												$row->trucking_company_cp,
												$row->trucking_company_address,
												$row->trucking_company_remark,
												);
		    $i++;
		}
		echo json_encode($data);
	}

	function manage_data() 
	{
		$wh_code = $this->input->post('trucking_company_code');
		$wh_name = $this->input->post('trucking_company_name');
		$wh_cp = $this->input->post('trucking_company_cp');
		$wh_address = $this->input->post('trucking_company_address');
		$wh_remark = $this->input->post('trucking_company_remark');
 		$id = $this->input->post('id');
 		$opr = $this->input->post('oper'); 	
		$region_id = $this->session->userdata('region_id');
 		
		if($opr=='add')
 		{
 			$this->trucking_company_model->insert_trucking_company($wh_code, $wh_name, $wh_cp, $wh_address, $wh_remark,$region_id);
		}
		else if($opr=='edit')
 		{
 			$this->trucking_company_model->update_trucking_company($wh_code, $wh_name, $wh_cp, $wh_address, $wh_remark, $id,$region_id);
 		}
		else if($opr=='del')
 		{
			 $this->trucking_company_model->delete_trucking_company($id,$region_id);
 		}	
	}
}
