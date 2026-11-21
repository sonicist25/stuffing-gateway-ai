<?php
class Afg_c_schedule extends CI_Controller
{
	var $title = 'Schedules';
	
	public function __construct(){
		parent::__construct();
		$this->fungsi->restrict();
		$this->load->model(array('afg_m_schedule','afg_m_countries','afg_m_cities','m_bill_of_ladding'));
	}
	
	function index(){
		die();
	}
	
	function get_country_by_city(){
		$city_id = $this->input->post('city_id');
		$county_id = $this->afg_m_cities->get_country_id_by_city($city_id);
		echo json_encode(array('country_id'=>$county_id));
	}
	
	function get_country_id(){
		$city_id = $this->input->post('city_id');
		$sp_id = $this->input->post('sp_id');
		$county_id = $this->afg_m_cities->get_country_id_by_city($city_id);
		$export = $this->m_bill_of_ladding->get_export_by_sp_id($sp_id);
		if($export->num_rows() > 0 ) {
         	$row = $export->row_array();
         	$eta_destination = $row['eta_destination'];
         	$direct = $row['direct_export'];
         } else {
         	$eta_destination = "";
         	$direct = "";         	
      }
		echo json_encode(array('country_id'=>$county_id, 'eta_destination'=>$eta_destination,'direct'=>$direct));
	}
	
	function displayExport(){
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
		$sord = isset($_POST['sord'])?$_POST['sord']:'';
				
		$start = $batas * $hal - $batas;
		$start = ($start<0)?0:$start;			
		$_POST['start'] = $start;
		
      $q = $this->afg_m_schedule->get_all_schedule($_POST,false); 
      $query = $this->afg_m_schedule->get_all_schedule($_POST,true); 
		
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
		    $data->rows[$i]['id']=$row->export_id;
		    $data->rows[$i]['cell']=array(
											$this->afg_m_cities->get_nama_city($row->origin_city_id),
											$row->origin_vessel,
											$row->origin_voyage,
											$this->fungsi->jin_date_str($row->stf_cls),
											$this->fungsi->jin_date_str($row->origin_etd),
											$row->direct_export,
											$this->afg_m_cities->get_nama_city($row->connecting_city_id),
											$this->afg_m_cities->get_nama_city($row->final_destination_city_id),
											$this->fungsi->jin_date_str($row->eta_destination),
											$row->final_destination_country_id,
											$row->type,
											$row->sp_id,
											$row->created,
											);
		    $i++;
		}
		echo json_encode($data);
	}
	
	/*
	
	function displayExport(){
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'shipper_id';
	 	$sord = isset($_POST['sord'])?$_POST['sord']:'';
	 	
	 	if(!$sidx) $sidx =1;
		$q = $this->afg_m_schedule->tampil_data_export_semua();
	 	
	 	$sp_id = $this->input->post('id_sp');
		if($sidx) $sidx = $sp_id;
		$q = $this->afg_m_schedule->tampil_data_export($sp_id);
	 
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
		    $data->rows[$i]['id']=$row->export_id;
		    $data->rows[$i]['cell']=array(
											$this->afg_m_cities->get_nama_city($row->origin_city_id),
											$row->origin_vessel,
											$row->origin_voyage,
											$this->fungsi->jin_date_str($row->stf_cls),
											$this->fungsi->jin_date_str($row->origin_etd),
											$row->direct_export,
											$this->afg_m_cities->get_nama_city($row->connecting_city_id),
											$this->afg_m_cities->get_nama_city($row->final_destination_city_id),
											$this->fungsi->jin_date_str($row->eta_destination),
											$row->final_destination_country_id,
											$row->type,
											$row->sp_id,
											$row->created,
											);
		    $i++;
		}
		echo json_encode($data);
	}
	*/
	
	function manage_data(){
		$mode = $this->input->post('oper');
		$id = $this->input->post('id');
		$sp_id = $this->input->post('sp_id');
		$sp_id_new = $this->afg_m_schedule->get_id_stuffing();
		$sp_id_edit = $this->input->post('sp_id_edit');
		$sp_id_del = $this->input->post('sp_id_del');
		
		
		$data = array (
			'origin_city_id' 						=> $this->afg_m_cities->get_city_id($this->input->post('origin_city_id')),
			'origin_vessel' 						=> strtoupper($this->input->post('origin_vessel')),
			'origin_voyage' 						=> strtoupper($this->input->post('origin_voyage')),
			'stf_cls' 								=> $this->fungsi->jin_date_sql($this->input->post('stf_cls')),
			'connecting_city_id'					=> $this->afg_m_cities->get_city_id($this->input->post('connecting_city_id')),
			'origin_etd' 							=> $this->fungsi->jin_date_sql($this->input->post('origin_etd')),		
			'eta_destination' 					=> $this->fungsi->jin_date_sql($this->input->post('eta_destination')),
			'final_destination_city_id' 		=> $this->afg_m_cities->get_city_id($this->input->post('final_destination_city_id')),
			'final_destination_country_id' 	=> $this->input->post('final_destination_country_id'),		
			'direct_export' 						=> $this->input->post('direct_export'),
			'type'									=> $this->input->post('type'),
			'sp_id'									=> $this->input->post('sp_id'),
		);
		
		$data2 = array(
			'origin_city_id' 						=> $this->afg_m_cities->get_city_id($this->input->post('origin_city_id')),
			'origin_vessel' 						=> strtoupper($this->input->post('origin_vessel')),
			'origin_voyage' 						=> strtoupper($this->input->post('origin_voyage')),
			'stf_cls' 								=> $this->fungsi->jin_date_sql($this->input->post('stf_cls')),
			'connecting_city_id'					=> $this->input->post('connecting_city_id'),
			'origin_etd' 							=> $this->fungsi->jin_date_sql($this->input->post('origin_etd')),		
			'eta_destination' 					=> $this->fungsi->jin_date_sql($this->input->post('eta_destination')),
			'final_destination_city_id' 		=> $this->afg_m_cities->get_city_id($this->input->post('final_destination_city_id')),
			'final_destination_country_id' 	=> $this->input->post('final_destination_country_id'),		
			'direct_export' 						=> $this->input->post('direct_export'),
			'type'									=> $this->input->post('type'),
			'sp_id'									=> $sp_id_new,
			'created'								=> $this->session->userdata('fullname'),
			'region_id'								=> $this->session->userdata('region_id'),
		);
		
		if($mode == 'add') {
			$status = $this->afg_m_schedule->add_data_export_new($data2);
			echo json_encode(array('status'=>$status,'mode'=>'add_schedule','connecting'=>$this->input->post('direct_export'),
				'id'=>$this->afg_m_schedule->get_last_id_export_new(),
			));
		} else if($mode == 'edit') {
			$status = $this->afg_m_schedule->update_data_export($data,$id);
			echo json_encode(array('status'=>$status,'mode'=>'edit','id'=>$id));
		}  else if($mode == 'del') {
			$status = $this->afg_m_schedule->delete_data_export($id,$sp_id);
			echo json_encode(array('status'=>$status,'mode'=>'del'));
		} 
		
		else if($mode == 'add_schedule') {
			$status = $this->afg_m_schedule->add_data_export_new($data2);
			echo json_encode(array('status'=>$status,'mode'=>'add_schedule','connecting'=>$this->input->post('direct_export')));
		} else if($mode == 'edit_schedule') {
			$status = $this->afg_m_schedule->update_data_export_edit($data,$sp_id_edit);
			echo json_encode(array('status'=>$status,'mode'=>'edit_schedule'));
		} else if($mode == 'del_schedule') {
			$status = $this->afg_m_schedule->delete_data_export_where($sp_id_del);
			echo json_encode(array('status'=>$status,'mode'=>'delete schedule'));
		}
	}
	
	function dropdownCity(){
		$country_id = $this->session->userdata('country_id');
		$cities = $this->afg_m_cities->getListCities($country_id)->result();
		$data['option_cities']['']=' - cities - ';
		foreach ($cities as $row)
		{	
			$data['option_cities'][$row->city_id]=$row->city_name;
		}
		echo form_dropdown('dropdownOriginCity',$data['option_cities'],'','id="dropdownOriginCity" class="city-dropdown"');
	}
}
