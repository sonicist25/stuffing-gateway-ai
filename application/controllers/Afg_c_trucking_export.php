<?php
class Afg_c_trucking_export extends CI_Controller
{
	var $title = 'Track Export';
	public function __construct(){
		parent::__construct();
		$this->fungsi->restrict();
	//	$this->fungsi->delete_trucking();
		$this->load->model(array('afg_m_export','afg_m_countries','afg_m_cities','afg_m_tracking_export'));
	}
	
	function index(){
		$regionName = $this->session->userdata('region_name');
		$this->session->unset_userdata('country_id');
		$data['title'] = $this->title;
		$data['h1_title'] = 'Stuffing Plan';
		$data['table_title'] = 'List Shipment Request';
		$data['menu_navigation'] = 'navigation';
		$data['main_view'] = 'trucking/form_trucking_export';
		$data['authentic_level'] = $this->fungsi->authentic_level(array(0,'1','2','3',4));		
		$data['regionName'] = $regionName;			
		$data['readonly'] = 'disabled="disabled"';			
		$data['style'] = 'stuffing_plan';			
		$data['level'] = $this->session->userdata('level_id');			
		$data['region_id'] = $this->session->userdata('region_id');					
		$this->load->view('afg_template', $data);
	}
	
	function open_trucking_table(){
		$this->load->view('trucking/form_trucking_export');
	}
	
function get_tracking_export(){
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
		$sord = isset($_POST['sord'])?$_POST['sord']:'';
				
		$start = $batas * $hal - $batas;
		$start = ($start<0)?0:$start;			
		$_POST['start'] = $start;
		
		$q = $this->afg_m_tracking_export->tampil_semua_tracking_export($_POST,false);
		$query = $this->afg_m_tracking_export->tampil_semua_tracking_export($_POST,true);

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
		foreach($q->result() as $row) {
		    $data->rows[$i]['id']=$row->id_tracking;
		    $data->rows[$i]['cell']=array(
		    		$row->id_shipper,
		    		$row->id_request,
		    		$row->id_agent,
		    		$row->mbl_no,
		    		$row->hbl_no, 
		    		$row->port_of_destination, 
		    		$row->feeder_name,
					$row->connecting_name,
		    		$this->fungsi->jin_date_str($row->etd),
		    		$this->fungsi->jin_date_str($row->atd),
		    		$this->fungsi->jin_date_str($row->eta), 
		    		$this->fungsi->jin_date_str($row->ata), 
		    		$row->shipper, 
		    		substr($row->consignee,0,30),
		    		$row->destination, 
		    		$row->pickup_do, 
		    		$row->pickup_cargo, 
		    		$row->container_no, 
		    		$row->remarks,
		    		$row->sp_detail_id,
		    		);
		    $i++;
		}
		echo json_encode($data);
	}
	
	function manage_tracking_export(){
		$mode = $this->input->post('oper');
		$id = $this->input->post('id');
		
		$data = array (
			'id_shipper' 					=> $this->input->post('id_shipper'),
			'id_request' 					=> $this->input->post('id_request'),
			'id_agent' 						=> $this->input->post('id_agent'),
			'mbl_no' 						=> $this->input->post('mbl_no'),
			'hbl_no' 						=> $this->input->post('hbl_no'),
			'port_of_destination'			=> $this->input->post('port_of_destination'),
			'feeder_name' 					=> $this->input->post('feeder_name'),
			'connecting_name' 				=> $this->input->post('connecting_name'),
			'etd' 							=> $this->fungsi->jin_date_sql($this->input->post('etd')),	
			'atd' 							=> $this->fungsi->jin_date_sql($this->input->post('atd')),	
			'eta' 							=> $this->fungsi->jin_date_sql($this->input->post('eta')),
			'ata'        					=> $this->fungsi->jin_date_sql($this->input->post('actual_eta')),
			'shipper' 						=> $this->input->post('shipper'),
			'consignee'						=> $this->input->post('consignee'),
			'destination' 					=> $this->input->post('destination'),
			'pickup_do' 					=> strtoupper($this->input->post('pickup_do')),
			'pickup_cargo'					=> strtoupper($this->input->post('pickup_cargo')),
			'container_no'					=> strtoupper($this->input->post('container_no')),
			'remarks' 						=> $this->input->post('remarks'),
			'sp_detail_id' 					=> $this->input->post('sp_detail_id'),
		);
		
		if($mode == 'add')
		{
			$data['shipper']=$this->get_data($data['sp_detail_id'],'shipper');
			$data['consignee']=$this->get_data($data['sp_detail_id'],'consignee');
			$status = $this->afg_m_tracking_export->add($data);
			echo json_encode(array('status'=>$status,'mode'=>'add'));
		}
		else if($mode == 'edit')
		{
			$status = $this->afg_m_tracking_export->update($data,$id);
			echo json_encode(array('status'=>$status,'mode'=>'edit'));
		}	
		else if($mode == 'del')
		{
			$status = $this->afg_m_tracking_export->delete($id);
			echo json_encode(array('status'=>$status,'mode'=>'delete'));
		}	
	}

	function get_data($id,$con){
		$q= $this->dbx->get_where("trnstuffingplandetail",array('sp_detail_id'=>$id));
		$code = substr($q->row()->code,4,3);
		if($con=='shipper') {
			if($code=='100' || $code=='101' ||$code=='102' ||$code=='401' ||$code=='200' || $code=='400' ||$code=='402'||$code=='500' || $code=='700' || $code=='900') {
				return $q->row()->shipper_print;	
			} else {
				return $q->row()->consignee;
			}
		} else {
			if($code=='100' || $code=='101' ||$code=='102' ||$code=='401' ||$code=='200' || $code=='400' ||$code=='500' || $code=='700' || $code=='900') {
				return $q->row()->consignee_add;	
			} else {
				return $q->row()->shipper_import;
			}		
		}
	}

}
