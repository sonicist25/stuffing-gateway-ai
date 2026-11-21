<?php
class Afg_c_request_trucking extends CI_Controller
{
	var $title = 'Request Trucking';
	public function __construct(){
		parent::__construct();
		$this->fungsi->restrict();
		$this->load->model(array('afg_m_countries','afg_m_cities','afg_m_req_trucking','afg_m_ref'));
	}
	
	function index(){
		$regionName = $this->session->userdata('region_name');
		$this->session->unset_userdata('country_id');
		$data['title'] = $this->title;
		$data['h1_title'] = 'Stuffing Plan';
		$data['table_title'] = 'Tabel Request Trucking';
		$data['menu_navigation'] = 'navigation';
		$data['main_view'] = 'trucking/request_trucking';
		$data['authentic_level'] = $this->fungsi->authentic_level(array(0,'1','2','3',4));		
		$data['regionName'] = $regionName;			
		$data['readonly'] = 'disabled="disabled"';			
		$data['style'] = 'stuffing_plan';			
		$data['level'] = $this->session->userdata('level_id');			
		$data['region_id'] = $this->session->userdata('region_id');					
		$this->load->view('afg_template', $data);
	}
	function request_trucking(){
		$data = $this->afg_m_req_trucking->find($_POST['kodeRef']);
		$this->load->view('trucking/request_trucking_header', $data);
	}

	function request_trucking_header()
	{
		$data = $this->afg_m_req_trucking->insert_or_update_header_truck($_POST);
		echo json_encode($data);
	}
	
	function manage_trucking_export(){
		$mode = $this->input->post('oper');
		$id = $this->input->post('id');
						
		$data = array (
			'kode_ref' 						=> $this->input->post('no_job'),
			'sp_detail_id' 					=> $this->input->post('sp_detail_id'),
			'pickup_date' 					=> $this->input->post('pickup_date'),
			'pickup_time' 					=> $this->input->post('pickup_time'),
			'party' 						=> $this->input->post('party'),
			'pic_document' 				=> $this->input->post('prepared'),
			'destination' 					=> $this->input->post('destination'),
			'remarks' 						=> $this->input->post('remarks'),
			'sp_detail_id' 					=> $this->input->post('sp_detail_id'),
			'pic_document' 				=> $this->input->post('prepared'),
			
		);
		
		if($mode == 'add')
		{
			$data['shipper']=$this->get_data($data['sp_detail_id'],'shipper');
			$status = $this->afg_m_req_trucking->add_export($data);
			echo json_encode(array('status'=>$status,'mode'=>'add'));
		}
	}
	
function get_data($id,$con){
		$q= $this->dbx->get_where("trnstuffingplandetail",array('sp_detail_id'=>$id));
		$code = substr($q->row()->code,4,3);
		if($con=='shipper') {
			if($code=='100' || $code=='101' || $code=='102' || $code=='200' || $code=='401' ||$code=='500' || $code=='700' || $code=='900') {
				return $q->row()->shipper_print;	
			} else {
				return $q->row()->consignee;
			}
		} else {
			if($code=='100' || $code=='101' || $code=='102' || $code=='200' || $code=='401' ||$code=='500' || $code=='700' || $code=='900')  {
				return $q->row()->consignee_add;	
			} else {
				return $q->row()->shipper_import;
			}		
		}
	}
}
	
	?>