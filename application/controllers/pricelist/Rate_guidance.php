<?php
class Rate_guidance extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->library('fungsi');
		$this->fungsi->restrict();
		$this->load->model('pricelist/rate_guidance_model');
	}

	function index() {
		$this->fungsi->auth(23,'Rate Guide Line');
		$data = array('menu_navigation' => 'navigation', 'main_view' => 'pricelist/v_rate_guidance','title' => 'Rate Guidance','table_title' => 'Rate Guidance' );
		$this->load->view('afg_template', $data);
	}
	
	function dropdownCityId() {
		$countries = $this->rate_guidance_model->getListCitiesId()->result();
		$data['option_cityid'][0]=' - select city code - ';
		foreach ($countries as $row)
		{	
			$data['option_cityid'][$row->city_id]=$row->city_name;
		}		
		$country = form_dropdown('dropdownCityId',$data['option_cityid'],'','id="dropdownCityId" style="width: 110px;"');		
		echo $country;
	}
	
	function get_rate_guidance(){
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
		$sord = isset($_POST['sord'])?$_POST['sord']:'desc';
      
      $start = $batas * $hal - $batas;
		$start = ($start<0)?0:$start;
		$_POST['start'] = $start;
		$q = $this->rate_guidance_model->get_rate_guidance($_POST,false);
		$query = $this->rate_guidance_model->get_rate_guidance($_POST,true);
		
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
		    $data->rows[$i]['id']=$row->id;
		    $data->rows[$i]['cell']=array(
												$row->name,
												$row->user,
											);
		    $i++;
		}
		echo json_encode($data);
	}

	function manage_data() {
		$value = $this->rate_guidance_model->manage_data($_POST);
		echo json_encode($value);
	}
	
	function get_rate_guidance_destination(){
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
		$sord = isset($_POST['sord'])?$_POST['sord']:'desc';
      
      $start = $batas * $hal - $batas;
		$start = ($start<0)?0:$start;
		$_POST['start'] = $start;
		$q = $this->rate_guidance_model->get_rate_guidance_destination($_POST,false);
		$query = $this->rate_guidance_model->get_rate_guidance_destination($_POST,true);
		
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
		    $data->rows[$i]['id']=$row->id;
		    $data->rows[$i]['cell']=array(
												$row->rate_id,
												$row->destination,
												$row->rate,
												$row->remaks,
												);
		    $i++;
		}
		echo json_encode($data);
	}
	
	function manage_data_destination() {
		$value = $this->rate_guidance_model->manage_data_destination($_POST);
		echo json_encode($value);
	}
	
	function print_data($id){
		$this->load->library('fpdf');
		$this->fpdf->FPDF('P','mm','A4');
		$this->fpdf->AddPage();
		$this->fpdf->SetFont('Arial','',10);
		$parent = $this->rate_guidance_model->rate_parent($id) ;
		//$parentname = $parent->rateguidance_destination;
		
		$this->fpdf->write(0,'BASE RATE for ');
		$this->fpdf->write(0,$parent['name']);
		$this->fpdf->Ln(10);
		
		$w = array(15, 50, 50, 75); //column width
		
		//TABLE HEADER	
		$this->fpdf->Cell($w[0],6,'NO','TB');	
		$this->fpdf->Cell($w[1],6,'DESTINATION','TB');
       $this->fpdf->Cell($w[2],6,'RATE','TB');
		$this->fpdf->Cell($w[3],6,'REMARK','TB');
		$this->fpdf->Ln();
		$no = 0;
		
		//GET THE DATA
		$ratedata =  $this->rate_guidance_model->edit($id); ;
		foreach( $ratedata as $data ){
			$no++;
			$destination = $data->destination;
			$rate = $data->rate;
			$remark = $data->remaks;
			//The cell
			$this->fpdf->Cell($w[0],6,$no,'B'); 
			$this->fpdf->Cell($w[1],6,$destination,'B');
			$this->fpdf->Cell($w[2],6,$this->fungsi->pecah($rate,'.'),'B');
			$this->fpdf->Cell($w[3],6,$remark,'B');
        
        	$this->fpdf->Ln();
		  
		  }
		//PRINT PDF  
		$this->fpdf->Output('BASE RATE for : '.$parent['name'],'I'); 
	}
}
