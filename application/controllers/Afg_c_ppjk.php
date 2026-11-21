<?php
class Afg_c_ppjk extends CI_Controller{
	
	var $title ='PPJK';
	
	public function __construct()
	{
		parent::__construct();
		$this->fungsi->restrict();	
		$this->load->model(array(
			'user_model',
			'afg_m_stuffing_plan',			
			'stuffing_ppjk'
		));		
	}		
	function index()
	{
		$regionName = $this->session->userdata('region_name');
		$this->session->unset_userdata('country_id');
		$data['title'] = $this->title;
		$data['h1_title'] = 'Stuffing Plan';
		$data['table_title'] = 'List Shipment Request';
		$data['menu_navigation'] = 'navigation';
		$data['main_view'] = 'ppjk/v_ppjk';
		$data['authentic_level'] = $this->fungsi->authentic_level(array(0,'1','2','3',4));		
		$data['regionName'] = $regionName;			
		$data['readonly'] = 'disabled="disabled"';			
		$data['style'] = 'stuffing_plan';			
		$data['level'] = $this->session->userdata('level_id');			
		$data['region_id'] = $this->session->userdata('region_id');					
		$this->load->view('afg_template', $data);
	}	

	function getData_ppjk()
	{
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'ppjk_id';
	 
		if(!$sidx) $sidx =1;
			$q = $this->stuffing_ppjk->getDataPpjk();	 
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
		    $data->rows[$i]['id']=$row->ppjk_id;
		    $data->rows[$i]['cell']=array(
				$row->ppjk_id,
				$row->kode_ref,				
				$row->type_of_trans,				
				$row->shipper,				
				$row->consignee,				
				$row->awb,				
				$row->hb_l,				
				$row->etd,				
				$row->atd,				
				$row->eta_port,				
				$row->ata_port,				
				$row->aju,				
				$row->pre_alert,				
				$row->draft_pib,				
				$row->pib_to_bank,				
				$row->pib_from_bank,				
				$row->actual_edi,				
				$row->red_lane,				
				$row->inspection,				
				$row->sppb,				
				$row->actual_delivery,				
				);
		    $i++;
		}
		echo json_encode($data);
	}
	function manage_data() 
	{
		$wh_company = $this->input->post('stuffing_location_company');
		$wh_address = $this->input->post('stuffing_location_address');
 		$id = $this->input->post('id'); 		
 		$opr = $this->input->post('oper'); 		

 		$data = array(			
			'kode_ref'=>$this->input->post('kode_ref'),
			'type_of_trans'=>$this->input->post('type_of_trans'),
			'shipper'=>$this->input->post('shipper'),
			'consignee'=>$this->input->post('consignee'),
			'awb'=>$this->input->post('awb'),
			'hb_l'=>$this->input->post('hb_l'),
			'etd'=>$this->input->post('etd'),
			'atd'=>$this->input->post('atd'),
			'eta_port'=>$this->input->post('eta_port'),
			'ata_port'=>$this->input->post('ata_port'),
			'aju'=>$this->input->post('aju'),
			'pre_alert'=>$this->input->post('pre_alert'),
			'draft_pib'=>$this->input->post('draft_pib'),
			'pib_to_bank'=>$this->input->post('pib_to_bank'),
			'pib_from_bank'=>$this->input->post('pib_from_bank'),
			'actual_edi'=>$this->input->post('actual_edi'),
			'red_lane'=>$this->input->post('red_lane'),
			'inspection'=>$this->input->post('inspection'),
			'sppb'=>$this->input->post('sppb'),
			'actual_delivery'=>$this->input->post('actual_delivery'),
		);
 		
		if($opr=='add')
 		{
 			$this->stuffing_ppjk->insert_ppjk($data);
		}
		else if($opr=='edit')
 		{
 			$this->stuffing_ppjk->update_ppjk($data, $id);
 		}
		else if($opr=='del')
 		{
			 $this->stuffing_ppjk->delete_ppjk($id);
 		}	
	}

	function dropdown_kode()
	{
		$query = $this->stuffing_ppjk->getDataImporByKodeRef($_POST);
		if($query) {
			$row = $query->row();
			$row->type_of_trans =  $row->type_of_trans;			
			$row->shipper_print =  $row->shipper_print;			
			$row->consignee =  $row->consignee;			
			$row->mother_bl =  $row->mother_bl;			
			$row->hb_l =  $row->hb_l;			
			$row->etd_jkt =  $row->etd_jkt;			
			echo json_encode($row);
		} else {
			echo json_encode(array(
				'type_of_trans'=>'',
				'shipper_print'=>'',
				'consignee'=>'',
				'hb_l'=>'',
				'mother_bl'=>'',
				'etd_jkt'=>'',				
			));
		}	
	}
}