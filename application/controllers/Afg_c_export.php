<?php
class Afg_c_export extends CI_Controller
{
	var $title = 'Schedules';
	public function __construct()
	{
		parent::__construct();
		$this->fungsi->restrict();
		$this->load->model(array('afg_m_export','afg_m_countries','afg_m_cities','afg_m_schedule'));
	}
	
	function index(){
		if ($this->session->userdata('login') == TRUE){
			$this->session->unset_userdata('country_id');
			$data['title'] = $this->title;
			$data['h1_title'] = 'Schedules';
			$data['table_title'] = 'Select destination country';
			$data['menu_navigation'] = 'navigation';
			$data['main_view'] = 'afg_v_export_pilih_country';
			$this->load->view('afg_template', $data);
			
		}
		else
		{
			redirect('afg_c_login');
		}
	}
	

	function selectCountry($country_id){
		$this->session->set_userdata('pilih_country', TRUE);
		$this->session->set_userdata('country_id', $country_id);
		redirect('afg_c_export/export');
	}	
	public function export(){
		
		$country_id = $this->session->userdata('country_id');
		
		if($country_id != '' ) {
			$country_name = $this->afg_m_countries->getCountriesById($country_id)->country_name;
			$data['title'] = $this->title;
			$data['h1_title'] = 'Schedule';
			$data['menu_navigation'] = 'navigation';
			$data['main_view'] = 'afg_v_export';
			$data['table_title'] = $country_name;
			$data['next_id'] = $this->afg_m_export->getNextID();
		
			$cities = $this->afg_m_cities->getListCities($country_id)->result();
			$data['option_cities'][0]='-cities-';
			foreach ($cities as $row)
			{	
				$data['option_cities'][$row->city_id]=$row->city_name;
			}
		
			$countries = $this->afg_m_countries->getListCountries()->result();
			$data['option_countries'][0]='-country-';
			foreach ($countries as $row)
			{	
				$data['option_countries'][$row->country_id]=$row->country_name;
			}
		
			$data['dropdown_origin_city'] = form_dropdown('dropdownOriginCity',$data['option_cities'],'','id="dropdownOriginCity"');
			$data['dropdown_conn_city'] = form_dropdown('dropdownConnCity',$data['option_cities'],'','id="dropdownConnCity"');
			$data['dropdown_country'] = form_dropdown('dropdownCountries',$data['option_countries'],'','id="dropdownCountries"');
			
			$data['country_name'] = $country_name;
			$this->load->view('afg_template', $data);
		}else {
			$this->index();
		}
	}
	
	function dropdownCity(){
		$country_id = $this->session->userdata('country_id');
		$cities = $this->afg_m_cities->getListCities($country_id)->result();
		$data['option_cities']['']=' ';
		foreach ($cities as $row)
		{	
			$data['option_cities'][$row->city_name]=$row->city_name;
		}
		echo form_dropdown('dropdownOriginCity',$data['option_cities'],'','id="dropdownOriginCity" class="city-dropdown"');
	}
	
	function dropdownCityOrigin(){
		$cities = $this->afg_m_cities->getListCitiesOrigin()->result();
		$data['option_cities']['']=' - cities - ';
		foreach ($cities as $row)
		{	
			$data['option_cities'][$row->city_id]=$row->city_name;
		}
		echo form_dropdown('dropdownOriginCity',$data['option_cities'],'','id="dropdownOriginCity" class="city-dropdown"');
	}
	
	function dropdownCityId($country_id){
		$cities = $this->afg_m_cities->getListCitiesId($country_id)->result();
		$data['option_cities']['']=' - cities - ';
		foreach ($cities as $row)
		{	
			$data['option_cities'][$row->city_id]=$row->city_name;
		}
		echo form_dropdown('dropdownOriginCity',$data['option_cities'],'','id="dropdownOriginCity" class="city-dropdown"');
	}
	
	function manage_data_connecting(){
		$value = $this->afg_m_schedule->manage_data_connecting($_POST);
		echo json_encode($value);
		
		/*
		$mode = $this->input->post('oper');
		$id = $this->input->post('id');
		
		$data = array (
			'id_export' 					=> $this->input->post('id_export'),
			'connecting_city'		=> $this->input->post('connecting_city'),
			'connecting_vesel' 	=> strtoupper($this->input->post('connecting_vesel')),
			'connecting_voyage'	=> strtoupper($this->input->post('connecting_voyage')),
			'connecting_eta' 		=> $this->fungsi->jin_date_sql($this->input->post('connecting_eta')),	
			'connecting_etd' 		=> $this->fungsi->jin_date_sql($this->input->post('connecting_etd')),	
			'sp_id'									=> $this->input->post('id_sp'),
		);
		
		if($mode == 'add')
		{
			$status = $this->afg_m_export->add_data_connecting($data);
			echo json_encode(array('status'=>$status,'mode'=>'add'));
		}
		else if($mode == 'edit')
		{
			$status = $this->afg_m_export->update_data_connecting($data,$id);
			echo json_encode(array('status'=>$status,'mode'=>'edit'));
		}	
		else if($mode == 'del')
		{
			$status = $this->afg_m_export->delete_data_connecting($id);
			echo json_encode(array('status'=>$status,'mode'=>'delete'));
		}	
		else if($mode == 'add_schedule_connecting')
		{
			$status = $this->afg_m_export->add_data_connecting($data);
			echo json_encode(array('status'=>$status,'mode'=>'add_schedule_connecting'));
		}
		*/
	}
	
	function jin_date_sql($date){
		$exp = explode('/',$date);
		if(count($exp) == 3) 
		{
			$date = $exp[2].'-'.$exp[1].'-'.$exp[0];
		}
		return $date;
	}
	
	function edit_data_city() {
		$city_id = $this->input->post('city_id');
		$city_name = $this->input->post('city_name');
 		$country_id = $this->input->post('country_id');
		$group_id = $this->input->post('group_id');
 		$opr = $this->input->post('oper');
 		$id = $this->input->post('id');
 		 		
		if($opr=='add')
 		{
 			$this->afg_m_cities->add_city($city_id,$city_name,$country_id,$group_id);
		}
		else if($opr=='edit')
 		{
 			$this->afg_m_cities->edit_city($id,$city_id,$city_name,$country_id,$group_id);
 		}
		else if($opr=='del')
 		{
			 $this->afg_m_cities->del_city($id);
 		}
		
	}

	public function getNextID(){
		echo $this->afg_m_export->getNextID();
	}
	
	function jin_date_str($date){
		$exp = explode('-',$date);
		if(count($exp) == 3) 
		{
			$date = $exp[2].'/'.$exp[1].'/'.$exp[0];
		}
		return $date;
	}
	
	function manageCountry(){
		$id = $this->input->post('id');
		$country_id = $this->input->post('country_id');
		$country_name = $this->input->post('country_name');
		$mode = $this->input->post('oper');
		if($mode=='add')
			$this->afg_m_countries->add($country_id,$country_name);
		else if($mode=='edit')
			$this->afg_m_countries->update($id,$country_id,$country_name);
		else if($mode=='del')
			$this->afg_m_countries->delete($id);	
	}
	
	function displayCityTable (){
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
		$sord = isset($_POST['sord'])?$_POST['sord']:'desc';
      
      if(!$sidx) $sidx =1;
		$q = $this->afg_m_cities->tampil_semua_city();
		
		$country = $_POST['country'];
		if($sidx) $sidx = $country;
		$q = $this->afg_m_cities->tampil_city($country);

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
		    $data->rows[$i]['id']=$row->id_city;
		    $data->rows[$i]['cell']=array($row->city_id, $row->city_name, $row->country_id, $row->group_id);
		    $i++;
		}
		echo json_encode($data);
	}
	
	function displayConnectingTable (){
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
		$sord = isset($_POST['sord'])?$_POST['sord']:'';
				
		$start = $batas * $hal - $batas;
		$start = ($start<0)?0:$start;			
		$_POST['start'] = $start;
		
      $q = $this->afg_m_schedule->tampil_connecting($_POST,false); 
      $query = $this->afg_m_schedule->tampil_connecting($_POST,true); 
		
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
		    		$row->exp_id, 
		    		$row->connecting_city,
		    		$this->fungsi->jin_date_str($row->connecting_eta), 
		    		$row->connecting_vesel, 
		    		$row->connecting_voyage,
		    		$this->fungsi->jin_date_str($row->connecting_etd),
		    	);
		    $i++;
		}
		echo json_encode($data);
	}	
	
	function displayExport(){
		
		$page = isset($_POST['page'])?$_POST['page']:1;
		$limit = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'name';
		$sord = isset($_POST['sord'])?$_POST['sord']:'';

      $start = $limit*$page - $limit;
		$start = ($start<0)?0:$start;

		$where = ""; 
      $searchField = isset($_POST['searchField']) ? $_POST['searchField'] : false;
      $searchOper = isset($_POST['searchOper']) ? $_POST['searchOper']: false;
      $searchString = isset($_POST['searchString']) ? $_POST['searchString'] : false;

        if ($_POST['_search'] == 'true') {
            $ops = array(
            'eq'=>'=', //equal
            'ne'=>'<>',//not equal
            'lt'=>'<', //less than
            'le'=>'<=',//less than or equal
            'gt'=>'>', //greater than
            'ge'=>'>=',//greater than or equal
            'bw'=>'LIKE', //begins with
            'bn'=>'NOT LIKE', //doesn't begin with
            'in'=>'LIKE', //is in
            'ni'=>'NOT LIKE', //is not in
            'ew'=>'LIKE', //ends with
            'en'=>'NOT LIKE', //doesn't end with
            'cn'=>'LIKE', // contains
            'nc'=>'NOT LIKE'  //doesn't contain
            );

            foreach ($ops as $key=>$value){
                if ($searchOper==$key) {
                    $ops = $value;
                }
            }
            if($searchOper == 'eq' ) $searchString = $searchString;
            if($searchOper == 'bw' || $searchOper == 'bn') $searchString .= '%';
            if($searchOper == 'ew' || $searchOper == 'en' ) $searchString = '%'.$searchString;
            if($searchOper == 'cn' || $searchOper == 'nc' || $searchOper == 'in' || $searchOper == 'ni') $searchString = '%'.$searchString.'%';

            $where = "$searchField $ops '$searchString' ";
        }
        
		$country_id = $this->session->userdata('country_id');
		
		if(!$sidx) $sidx =1;
        $count = $this->afg_m_export->getDataExport($country_id,$start,$limit,$sidx,$sord,$where)->num_rows();

		if( $count > 0 ) {
			$total_pages = ceil($count/$limit);    //calculating total number of pages
		} else {
			$total_pages = 0;
		}

		if ($page > $total_pages) $page=$total_pages;
   		$query = $this->afg_m_export->getDataExport($country_id,$start,$limit,$sidx,$sord,$where)->result();
   		
   	$responce = new stdClass();	
		$responce->page = $page;
		$responce->total = $total_pages;
		$responce->records = $count;
		$i=0;
		
		foreach($query as $row) 
		{
			$responce->rows[$i]['id']=$row->export_id;
			$responce->rows[$i]['cell']=array(
											$row->origin_city_id,
											$row->origin_vessel,
											$row->origin_voyage,
											$this->fungsi->jin_date_str($row->stf_cls),
											$this->fungsi->jin_date_str($row->origin_etd),
											$this->fungsi->jin_date_str($row->eta_destination),
											$row->final_destination_city_id,
											$row->direct_export,
											$row->final_destination_country_id,
											);
			$i++;
		}
		echo json_encode($responce);
	
	}
	/*
	function displayCountrySelect()
	{
		$page = isset($_POST['page'])?$_POST['page']:1; // get the requested page
		$limit = isset($_POST['rows'])?$_POST['rows']:10; // get how many rows we want to have into the grid
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'name'; // get index row - i.e. user click to sort
		$sord = isset($_POST['sord'])?$_POST['sord']:''; // get the direction
		
      $start = $limit*$page - $limit; // do not put $limit*($page - 1)
		$start = ($start<0)?0:$start;  // make sure that $start is not a negative value

		$where = ""; //if there is no search request sent by jqgrid, $where should be empty
      $searchField = isset($_POST['searchField']) ? $_POST['searchField'] : false;
      $searchOper = isset($_POST['searchOper']) ? $_POST['searchOper']: false;
      $searchString = isset($_POST['searchString']) ? $_POST['searchString'] : false;

        if ($_POST['_search'] == 'true') {
            $ops = array(
            'eq'=>'=', //equal
            'ne'=>'<>',//not equal
            'lt'=>'<', //less than
            'le'=>'<=',//less than or equal
            'gt'=>'>', //greater than
            'ge'=>'>=',//greater than or equal
            'bw'=>'LIKE', //begins with
            'bn'=>'NOT LIKE', //doesn't begin with
            'in'=>'LIKE', //is in
            'ni'=>'NOT LIKE', //is not in
            'ew'=>'LIKE', //ends with
            'en'=>'NOT LIKE', //doesn't end with
            'cn'=>'LIKE', // contains
            'nc'=>'NOT LIKE'  //doesn't contain
            );

            foreach ($ops as $key=>$value){
                if ($searchOper==$key) {
                    $ops = $value;
                }
            }
            if($searchOper == 'eq' ) $searchString = $searchString;
            if($searchOper == 'bw' || $searchOper == 'bn') $searchString .= '%';
            if($searchOper == 'ew' || $searchOper == 'en' ) $searchString = '%'.$searchString;
            if($searchOper == 'cn' || $searchOper == 'nc' || $searchOper == 'in' || $searchOper == 'ni') $searchString = '%'.$searchString.'%';
			if(strpos($searchString,',')>0)
			{
				$searchString1 = substr($searchString, 0, strpos($searchString,','));
				$searchString2 = substr($searchString, strpos($searchString,',')+1, strlen($searchString)-(strpos($searchString,',')+1)); 
			}
			
			if(strpos($searchField,',')>0)
			{
				$searchField1 = 'disloc';//substr($searchField, strpos($searchField,','));
				$searchField2 = 'datebook';//substr($searchField, -strpos($searchField,','));
				$where = "$searchField1 $ops '$searchString1' and $searchField2 = '$searchString2'"; 
			}
			else
			{
				$where = "$searchField $ops '$searchString' ";
			}
        }

		if(!$sidx) $sidx =1;
        $count = $this->afg_m_countries->getDataCountries($start,$limit,$sidx,$sord,$where)->num_rows();

		if( $count > 0 ) {
			$total_pages = ceil($count/$limit);    //calculating total number of pages
		} else {
			$total_pages = 0;
		}

		if ($page > $total_pages) $page=$total_pages;
   		$query = $this->afg_m_countries->getDataCountries($start,$limit,$sidx,$sord,$where)->result();
   				
		$responce = new stdClass();		
		$responce->page = $page;
		$responce->total = $total_pages;
		$responce->records = $count;
		$i=0;
		foreach($query as $row) 
		{
			$responce->rows[$i]['id']=$row->idnya;
			$responce->rows[$i]['cell']=array(
												$row->country_id,       
												$row->country_name,
											);
			$i++;
		}
		echo json_encode($responce);
	}	
	*/
}
