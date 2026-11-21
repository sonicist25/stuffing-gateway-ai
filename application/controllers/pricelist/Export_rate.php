<?php
class Export_rate extends CI_Controller
{
	function __construct(){
		parent::__construct();
		$this->load->library('fungsi');
		$this->fungsi->restrict();
		$this->load->model(array(
			'afg_m_cities',
			'pricelist/export_feeder_rate_model',
			'reference/type_container_model'
		));
	}
	
	function get_table_data() 
	{
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
	 	$sord = isset($_POST['sord'])?$_POST['sord']:'';
	 	$start = $batas * $hal - $batas;
		$start = ($start<0)?0:$start;
		$_POST['start'] = $start;
		// Sebelumnya get_table_data() dipanggil 2x penuh (tanpa limit) hanya untuk
		// menghitung total baris. Sekarang pakai count_table_data() (COUNT query saja).
		$hitung = $this->export_feeder_rate_model->count_table_data($_POST);
		$query  = $this->export_feeder_rate_model->get_table_data($_POST,true);
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
		    									$row->destination,
		    									$row->country,
												$row->container,
												$row->freight,
												$row->usd,
												$row->idr,
												$row->kurs,
												"",
												$row->condition,
												$row->remarks,
											);
		    $i++;
		}
		echo json_encode($data);
	}
	
	function manage_detail()
	{
		$value = $this->export_feeder_rate_model->manage_detail($_POST);
		echo json_encode($value);
	}
	
	function index() 
	{
		$this->fungsi->auth(28,'Export Cost Feeder Rate');
		$data = array('menu_navigation' => 'navigation', 'main_view' => 'pricelist/v_export_feeder_rate',
			'title' => 'Feeder Rate','table_title' => 'COST FEEDER EXPORT',
			'rowList'=>'',
			 );
		$this->load->view('afg_template', $data);
	}
	
	function open_form() 
	{
		$data['id'] =  $this->input->post('id');
		$data['code'] =  $this->input->post('code');
		$data['name'] =  $this->input->post('name');
		$this->load->view('pricelist/v_export_feeder_rate_shipp', $data);
	}
	
	function get_data()
	{
		$mode = $this->input->post('oper');	
		if($mode == 'last')
			$data = $this->export_feeder_rate_model->last($_POST);
		else if($mode == 'prev')
			$data = $this->export_feeder_rate_model->prev($_POST);
		else if($mode == 'next')
			$data = $this->export_feeder_rate_model->next($_POST);
		else if($mode == 'pilih')
			$data = $this->export_feeder_rate_model->pilih($_POST);
			
		if($data!=false) {
			$data->date = $this->fungsi->jin_date_str($data->date);
			$data->id_first = $this->export_feeder_rate_model->pilih_id(array('min',$_POST['shipping_co']));	
			$data->id_last = 	$this->export_feeder_rate_model->pilih_id(array('max',$_POST['shipping_co']));
			echo json_encode($data);
		}
		else {
			$data = new stdClass();
			$data->id="";
			$data->ref_no = "";
			$data->date = "";
			$data->shipping_co = $_POST['shipping_co'];
			$data->id_first = "";	
			$data->id_last = 	"";
			echo json_encode($data);
		}
	}
}
