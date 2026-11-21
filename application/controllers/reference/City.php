<?php class City extends CI_Controller {
	function __construct(){
		parent::__construct();
		$this->load->library('fungsi');
		$this->fungsi->restrict();
		$this->load->model(array('reference/city_model','afg_m_countries'));
		$this->fungsi->dbx = $this->city_model->dbx;
	}

	function index() 
	{
		$this->fungsi->auth(6,'Manage Cities');
		$data = array('menu_navigation' => 'navigation', 'main_view' => 'reference/v_city', 'h1_title' => 'Management City', 'title' => 'Management City' );
		$this->load->view('afg_template', $data);
	}
	
	function getDataCity()
	{
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
		$sord = isset($_POST['sord'])?$_POST['sord']:'';
	 
		$start = $batas * $hal - $batas;
		$start = ($start<0)?0:$start;			
		$_POST['start'] = $start;

		$q = $this->city_model->getDataCity($_POST,false);
		$query = $this->city_model->getDataCity($_POST,true);
	 
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
		    $data->rows[$i]['id']=$row->id_city;
		    $data->rows[$i]['cell']=array(
		    									$row->city_id,
												$row->city_name,
												$row->country_id,
												$row->group_id,
												$row->city_name,
												$row->origin_id,
												);
		    $i++;
		}
		echo json_encode($data);
	}

	function manage_data() 
	{
		 $value = $this->city_model->manage_data($_POST);
		 echo json_encode(array($value));
		 
		/*
 		$id = $this->input->post('id');
 		$opr = $this->input->post('oper'); 	
 		$data = array(
 			'city_id'=>strtoupper($this->input->post('city_id')),
 			'city_name'=>ucwords($this->input->post('city_name')),
 			'country_id'=>strtoupper($this->input->post('country_id')),
 			'group_id'=>$this->input->post('group_id'),
 			'origin_id'=>$this->input->post('origin_id'),
 		);
 		
 		$data2 = array(
 			'portdestination_code'=>strtoupper($this->input->post('city_id')),
 			'portdestination_name'=>strtoupper($this->input->post('city_name')),
 			'portdestination_country'=>strtoupper($this->afg_m_countries->get_nama_country($this->input->post('country_id'))),
 			'portdestination_remark'=>strtoupper($this->input->post('remarks')),
 		);

		if($opr=='add')
 		{
 			$status = $this->city_model->insert_city($data,$data2);
 			echo json_encode(array('status'=>$status,'mode'=>'add'));
		}
		else if($opr=='edit')
 		{
 			$status = $this->city_model->update_city($data, $data2, $id);
 			echo json_encode(array('status'=>$status,'mode'=>'edit'));
 		}
		else if($opr=='del')
 		{
			 $status = $this->city_model->delete_city($id);
			 echo json_encode(array('status'=>$status,'mode'=>'del'));
 		}	
 		*/
 		////print_r($this->dbx->last_query());
		
	}
	
	function dropdowncauntry()
	{
		$countries = $this->afg_m_countries->getListCountries()->result();
		$data['option_countries'][0]='  ';
		foreach ($countries as $row)
		{	
			$data['option_countries'][strtoupper($row->country_name)]=strtoupper($row->country_name);
		}		
		$country = form_dropdown('dropdownCountries',$data['option_countries'],'','id="dropdownCountries" disabled="disabled" style="width: 110px;"');		
		echo $country;
	}

}