<?php
class Export_cost_operasional extends CI_Controller 
{
	function __construct(){
		parent::__construct();
		$this->fungsi->restrict();
		$this->load->model(array(
			'user_model',
			'afg_m_cities',
			'pricelist/selling_rate_model',
			'reference/type_container_model'
		));
	}
	
	function index(){
		$this->fungsi->auth(33,'Selling Rate');
		$data = array('menu_navigation' => 'navigation', 'main_view' => 'pricelist/v_selling_rate',
				'title' => 'Selling Rate','table_title' => 'Selling Rate to Shipper / Exportir',
				'rowList'=>'',
		);
		$this->load->view('afg_template', $data);
	}
	
	function get_selling() {	
		$post = array(
			'user_id' => $this->session->userdata('user_id'),
			'shipper_id' => $this->input->post('shipper_id'),
			'id'		=> $this->input->post('idSelling'),
			'mode'		=> $this->input->post('mode'),
		);
		
		$data = $this->selling_rate_model->get_selling($post);
		echo json_encode($data);
	}
	
	function open_form_selling(){
		$data['shipper_id'] =  $this->input->post('shipper_id');
		$data['shipper_name'] =  $this->input->post('shipper_name');
		$data['id_selling'] = $this->input->post('id_selling');
		$this->load->view('pricelist/v_selling_rate_shipper', $data);
	}
	
	function get_selling_rate(){
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
	 	$sord = isset($_POST['sord'])?$_POST['sord']:'';
	 	
		if(!$sidx) $sidx =1;
		$q = $this->selling_rate_model->get_selling_rate();
	 
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
		    $data->rows[$i]['id']=$row->id_shipper;
		    $data->rows[$i]['cell']=array(
		    									$row->shipper_id,
												$row->shipper_name,
												$this->fungsi->get_user_by_id($row->sales),
											);
		    $i++;
		}
		echo json_encode($data);
	}
	
	function get_table_selling(){
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
	   $sord = isset($_POST['sord'])?$_POST['sord']:'';
	   	   
		if(!$sidx) $sidx=1; 		
		$q = $this->selling_rate_model->get_table_selling($_POST);
	 	
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
		    $data->rows[$i]['id']=$row->id;
		    $data->rows[$i]['cell']=array(
		    									$row->nama_service,
		    									$row->kondisi,
												$row->set_m3,
												$row->destination,
												$row->location,
												$row->type,
												$row->container,
												$row->currency,
												$row->sales_comission,
												$row->discount,
												$row->price,
												$row->base_rate,
												$row->mchgs,
												$row->remarks,
												$row->rate_rgl,
												$row->approval,
											);
		    $i++;
		}
		echo json_encode($data);
	}
	
	function get_table_listing(){
		$shipper_id = $this->input->post('shipper_id');
		$user_id = $this->session->userdata('user_id');
		
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
		$sord = isset($_POST['sord'])?$_POST['sord']:'';
	 
		if(!$sidx) $sidx =1;		
		$q = $this->selling_rate_model->get_table_listing($shipper_id,$user_id);
			 
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
		    $data->rows[$i]['id']=$row->id_selling;
		    $data->rows[$i]['cell']=array(
		    									$row->ref_no,
		    									$this->fungsi->jin_date_str($row->tanggal),
												$this->fungsi->jin_date_str($row->valid),
												$row->kepada,
												$row->attn,
												$this->user_model->get_user_by_id($row->dari),
											);
		    $i++;
		}
		echo json_encode($data);
	}
	
	function get_table_urut(){

	}
	
	function dropdownCityId() {
		$countries = $this->selling_rate_model->getListCitiesId()->result();
		$data['option_cityid']['']=' ';
		foreach ($countries as $row)
		{	
			$data['option_cityid'][$row->city_id]=$row->city_name;
		}		
		$country = form_dropdown('dropdownCityId',$data['option_cityid'],'','id="dropdownCityId" style="width: 110px;"');		
		echo $country;
	}
	
	function dropdownCities(){
		$cities = $this->afg_m_cities->getListCitiesDrop()->result();
		$data['option_cities']['']=' ';
		foreach ($cities as $row)
		{	
			$data['option_cities'][$row->city_name]=$row->city_name;
		}		
		echo form_dropdown('dropdownCities',$data['option_cities'],'','id="dropdownCities"');
	}
	
	function dropdownContainer(){
		$agent = $this->type_container_model->getListContainer()->result();
		$data['option_container']['']=' ';
		foreach ($agent as $row)
		{	
			$data['option_container'][$row->typecontainer_name]=$row->typecontainer_name;
		}		
		echo form_dropdown('dropdownContainer',$data['option_container'],'','id="dropdownContainer"');
	}
	
	function manage_data_selling(){
		$mode = $this->input->post('oper');
		$id = $this->input->post('id');
		$data = array (
			'of_emkl'=> $this->input->post('of_emkl'),
			'kode_shipper' => $this->input->post('kode_shipper'),
			'ref_no' => $this->input->post('ref_no'),
			'tanggal' => $this->fungsi->jin_date_sql($this->input->post('tanggal')),
			'valid' => $this->fungsi->jin_date_sql($this->input->post('valid')),
			'dari' => $this->input->post('dari'),
			'kepada' => $this->input->post('kepada'),
			'attn' => $this->input->post('attn'),
			'peti_kemas' => $this->input->post('peti_kemas'),			
			'remarks' => $this->input->post('remarks'),
		);
		
		if($mode == 'add')
		{
			$status = $this->selling_rate_model->add_data_selling($data);
			echo json_encode(array('status'=>$status,'mode'=>'add'));
		}
		else if($mode == 'edit')
		{
			$status = $this->selling_rate_model->update_data_selling($data,$id);
			echo json_encode(array('status'=>$status,'mode'=>'edit'));
		}	
		else if($mode == 'del')
		{
			$status = $this->selling_rate_model->delete_data_selling($id);
			echo json_encode(array('status'=>$status,'mode'=>'delete'));
		}	
	}
	
	function print_data($date){
		$this->session->set_flashdata($_POST);
		
		$this->load->library(array('fpdf','fungsi'));
		$this->fpdf->FPDF('P','mm','A4');
		$this->fpdf->AddPage();
		$this->fpdf->Link(10,10,40,15, 'http://gateway-id.com');
		
		$this->fpdf->Image( $this->config->item('images').'logo.png',10,10,+40);
		
		$this->fpdf->SetFont('Arial','',12);
		$this->fpdf->SetXY(133, 10);
		$this->fpdf->write(5,'PT GATEWAY CONTAINER LINE');
		$this->fpdf->SetFont('Arial','',8);
		$this->fpdf->SetXY(137, 17);
		$this->fpdf->write(0,'IS PLAZA BUILDING, 3RD FLOOR, ROOM: 303');
		$this->fpdf->SetXY(158, 21);
		$this->fpdf->write(0,'JL. PRAMUKA RAYA KAV. 151');
		$this->fpdf->SetXY(166, 25);
		$this->fpdf->write(0,'JAKARTA TIMUR 13120');
		$this->fpdf->SetXY(143, 29);
		$this->fpdf->write(0,'PHONE : ( 62-21 ) 8591-8669 ( HUNTING )');
		$this->fpdf->SetXY(165, 33);
		$this->fpdf->write(0,'FAX : ( 62-21 ) 8591-8664');
		$this->fpdf->SetXY(165, 36);
		$this->fpdf->write(0,'general@gateway-id.com');	
		
		$this->fpdf->SetLineWidth(0.6);
		$this->fpdf->Line(10,43,200,43);	
		$this->fpdf->SetLineWidth(0.1);
		$this->fpdf->Line(10,43.9,200,43.9);
					
		$this->fpdf->SetFont('Arial','',8);
		
		$this->fpdf->SetXY(10, 50);
		$this->fpdf->write(0,'TO');
		$this->fpdf->SetXY(25, 50);
		$this->fpdf->write(0,':');
		$this->fpdf->SetXY(30, 50);
		$this->fpdf->write(0,$this->session->flashdata('to'));
		
		$this->fpdf->SetXY(10, 56);
		$this->fpdf->write(0,'FROM');
		$this->fpdf->SetXY(25, 56);
		$this->fpdf->write(0,':');
		$this->fpdf->SetXY(30, 56);
		$from = $this->session->flashdata('from');
		$this->fpdf->write(0,$this->fungsi->get_user_by_id($from));
		
		$this->fpdf->SetXY(10, 61);
		$this->fpdf->write(0,'ATTN');
		$this->fpdf->SetXY(25, 61);
		$this->fpdf->write(0,':');
		$this->fpdf->SetXY(30, 61);
		$this->fpdf->write(0,$this->session->flashdata('attn'));
		
		$this->fpdf->SetXY(10, 67);
		$this->fpdf->write(0,'DATE');
		$this->fpdf->SetXY(25, 67);
		$this->fpdf->write(0,':');
		$this->fpdf->SetXY(30, 67);
		$tglawal = substr($date,6,2).'-'. substr($date,4,2).'-'. substr($date,0,4);
		$this->fpdf->write(0,$tglawal);
		
		$this->fpdf->SetFont('Arial','BI',10);
		$this->fpdf->SetXY(10, 75);
		$this->fpdf->write(0,'RE  :  FREIGHT QUATATION LCL SHIPMENT EX. JAKARTA CFS');
		
		$this->fpdf->SetFont('Arial','',8);
		$this->fpdf->SetXY(10, 83);
		$this->fpdf->write(0,'Thank you for giving us the apportunity to quote our best rates as shown below :');
		
		$this->fpdf->SetFont('Arial','B',8);
		$this->fpdf->SetFillColor(230,230,230);
		$this->fpdf->SetTextColor(100,100,100);
		$this->fpdf->SetLineWidth(0.1);
		
		$this->fpdf->SetXY(10, 87);
		$this->fpdf->MultiCell(70,6,'Type of Service',1,'C',true);
		$this->fpdf->SetXY(80, 87);
		$this->fpdf->MultiCell(32,6,'Destination',1,'C',true);
		$this->fpdf->SetXY(112, 87);
		$this->fpdf->MultiCell(48,6,'Rates W/M',1,'C',true);
		$this->fpdf->SetXY(160, 87);
		$this->fpdf->MultiCell(40,6,'Condition',1,'C',true);
		
		$shipper = $this->session->flashdata('shipper');
		$this->fpdf->SetTextColor(10,10,10);
		$this->fpdf->SetFont('Arial','',8);

		$post = $this->session->flashdata('shipper');		
		$result=$this->selling_rate_model->print_data($post)->result();		
		foreach ($result as $row ) {
			$this->fpdf->Cell(70, 6, $row->nama_service,'B',0,'L',false);
      	$this->fpdf->Cell(32, 6, $row->destination,'B',0,'L',false);
      	$this->fpdf->Cell(18, 6, $row->currency,'B',0,'C',false);
      	$this->fpdf->Cell(25, 6, $this->fungsi->pecah($row->price,' '),'B',0,'R',false);
      	$this->fpdf->Cell(44, 6, $row->kondisi,'B',0,'C',false);
			$this->fpdf->Ln();
		}
		
		$this->fpdf->Cell(190,1,'','LBR',0,'C',true);
		$this->fpdf->Ln(7);
		$this->fpdf->write(0,'Remarks : ');
		$this->fpdf->Ln(3);	
		$this->fpdf->MultiCell(190,6,$this->session->flashdata('remarks'),0,'L',false);
		
		$this->fpdf->Ln(20);	
		$this->fpdf->write(0,'Should you require any further information, please feel free to contact us.');
		$this->fpdf->Ln(5);
		$this->fpdf->write(0,'Kind Regards,');
		$this->fpdf->Ln(20);	
		$this->fpdf->write(0,$this->fungsi->get_user_by_id($from));
		
		$this->fpdf->Output('Selling-Rate-To-'.$this->session->flashdata('to'),'I'); 
	}

	function manage_data(){
		$this->selling_rate_model->manage_data($_POST);
		echo json_encode(array('mode'=>$_POST['oper']));
	}
}











