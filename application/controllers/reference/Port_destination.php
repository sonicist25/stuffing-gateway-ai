<?php
class Port_Destination extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->library('fungsi');
		$this->fungsi->restrict();
		$this->load->model('reference/port_destination_model');
	}

	function index() 
	{
		$this->fungsi->auth(3,'Port destination');
		$data = array('menu_navigation' => 'navigation', 'main_view' => 'reference/v_port_destination', 'h1_title' => 'Management Warehose', 'title' => 'Management Warehose' );
		$this->load->view('afg_template', $data);
	}
	
	function getDatarefportdestination()
	{
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'port_destination_code';
	 
		if(!$sidx) $sidx =1;
		$q = $this->port_destination_model->getDatarefportdestination();
	 
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
		    $data->rows[$i]['id']=$row->portdestination_id;
		    $data->rows[$i]['cell']=array(
												$row->portdestination_code,
												$row->portdestination_name,
												$row->portdestination_country,
												$row->origin_id,
												$row->portdestination_remark,
												);
		    $i++;
		}
		echo json_encode($data);
	}

	function manage_data() 
	{
		$wh_code = $this->input->post('portdestination_code');
		$wh_name = $this->input->post('portdestination_name');
		$wh_country = $this->input->post('portdestination_country');
		$wh_remark = $this->input->post('portdestination_remark');
 		$id = $this->input->post('id');
 		$opr = $this->input->post('oper'); 		
 		
		if($opr=='add')
 		{
 			$this->port_destination_model->insert_port_destination($wh_code, $wh_name, $wh_country, $wh_remark);
		}
		else if($opr=='edit')
 		{
 			$this->port_destination_model->update_port_destination($wh_code, $wh_name, $wh_country, $wh_remark, $id);
 		}
		else if($opr=='del')
 		{
			 $this->port_destination_model->delete_port_destination($id);
 		}	
	}
	
	function dropdownCountry() 
	{
		$this->load->model('afg_m_countries');
		$countries = $this->afg_m_countries->getListCountries()->result();
		$data['option_countries'][0]=' - Country - ';
		foreach ($countries as $row)
		{	
			$data['option_countries'][$row->country_name]=$row->country_name;
		}		
		echo form_dropdown('dropdownCountries',$data['option_countries'],'','id="dropdownCountries" disabled="disabled" style="width: 180px;"');
		
	}
}
