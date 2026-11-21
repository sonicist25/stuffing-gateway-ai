<?php
class Port_loading extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->library('fungsi');
		$this->fungsi->restrict();
		$this->load->model('reference/port_loading_model');
	}

	function index() 
	{
		$this->fungsi->auth(4,'Port of Loading');
		$data = array('menu_navigation' => 'navigation', 'main_view' => 'reference/v_port_loading', 'h1_title' => 'Management Warehose', 'title' => 'Management Warehose' );
		$this->load->view('afg_template', $data);
	}
	
	function getDatarefportloading()
	{
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'port_loading_code';
	 
		$start = $batas*$hal - $batas;
		$start = ($start<0)?0:$start;
		$_POST['start']=$start;
		
		$q = $this->port_loading_model->getDatarefportloading($_POST,$limit=false);
		$query = $this->port_loading_model->getDatarefportloading($_POST,$limit=true);
	 
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
		    $data->rows[$i]['id']=$row->	portloading_id;
		    $data->rows[$i]['cell']=array(
		    									$row->portloading_port,
		    									$row->portloading_city,
												$row->portloading_remark,
											);
		    $i++;
		}
		echo json_encode($data);
	}

	function manage_data() 
	{
		$wh_port = $this->input->post('portloading_port');
		$wh_city = $this->input->post('portloading_city');
		$wh_remark = $this->input->post('portloading_remark');
 		$id = $this->input->post('id');
 		$opr = $this->input->post('oper'); 		
 		
		if($opr=='add')
 		{
 			$this->port_loading_model->insert_port_loading($wh_port, $wh_city, $wh_remark);
		}
		else if($opr=='edit')
 		{
 			$this->port_loading_model->update_port_loading($wh_port, $wh_city, $wh_remark, $id);
 		}
		else if($opr=='del')
 		{
			 $this->port_loading_model->delete_port_loading($id);
 		}	
	}
	
	function dropdownCity() 
	{
		$this->load->model('afg_m_cities');
		$countries = $this->afg_m_cities->getListCitiesDrop()->result();
		$data['option_countries'][0]=' - city - ';
		foreach ($countries as $row)
		{	
			$data['option_countries'][$row->city_name]=$row->city_name;
		}		
		echo form_dropdown('dropdownCountries',$data['option_countries'],'','id="dropdownCountries" disabled="disabled" style="width: 180px;"');
		
	}
}
