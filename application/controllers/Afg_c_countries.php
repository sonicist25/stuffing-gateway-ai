<?php
class Afg_c_countries extends CI_Controller
{
	var $title = 'Destinaton Country';
	public function __construct()

	{
		parent::__construct();
		$this->load->model('afg_m_login');
		$this->load->model('afg_m_countries');
		$this->load->model('afg_m_cities');
		session_start();
	}
	
	function index()
	{
		if ($this->session->userdata('login') == TRUE)
		{
			$this->setTampilan();
		}
		else
		{
			redirect('afg_c_login');
		}
	}
	
	function setTampilan()
	{
		$data['title'] = $this->title;
		$data['h1_title'] = 'Country';
		$data['table_title'] = 'Country List';
		$data['menu_navigation'] = 'navigation';
		$data['mode'] = 'country';
		$data['main_view'] = 'afg_v_countries';
		$this->load->view('afg_template1', $data);
	}
	
	function submitCountry()
	{
		$id = $this->input->post('id');
		$country_id = $this->input->post('country_id');
		$country_name = $this->input->post('country_name');
		$mode = $this->input->post('mode');
		if($mode=='add')
			$this->afg_m_countries->add($country_id,$country_name);
		else if($mode=='edit')
			$this->afg_m_countries->update($id,$country_id,$country_name);
		else if($mode=='del')
			$this->afg_m_countries->delete($id);	
	}
	
	function deleteCountry()
	{
		$country_id_del = $this->input->post('country_id_del');
		$this->afg_m_countries->delete($country_id_del);	
	}
	
	function jin_date_str($date)
	{
		$exp = explode('-',$date);
		if(count($exp) == 3) 
		{
			$date = $exp[2].'/'.$exp[1].'/'.$exp[0];
		}
		return $date;
	}
	
	
	
	function displayCountry()
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
		
		$responce->page = $page;
		$responce->total = $total_pages;
		$responce->records = $count;
		$i=0;
		foreach($query as $row) 
		{
			$responce->rows[$i]['country_id']=$row->country_id;
			$responce->rows[$i]['cell']=array(
												anchor('#','edit',array('class' => 'editBtn','onclick'=>'return false')).' '.
												anchor('#','delete',array('class' => 'hapusBtn','onclick'=>'return false')),
												$row->country_id,
												$row->country_name
												);
			$i++;
		}
		echo json_encode($responce);
	}
	
	function dropdownCountry()
	{
		if('IS_AJAX')
		{
			$countries = $this->afg_m_countries->getListCountries()->result();
			$data['option_countries'][0]='-country-';
			foreach ($countries as $row)
			{	
				$data['option_countries'][$row->country_id]=$row->country_name;
			}
			$countries = form_dropdown('dropdownCountries',$data['option_countries'],'','id="dropdownCountries"');
			echo json_encode(array(
									"countries" => $countries
									));
		}
	}	
	
}
?>
