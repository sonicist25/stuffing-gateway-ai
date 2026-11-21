<?php
class Country extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->library('fungsi');
		$this->fungsi->restrict();
		$this->load->model('reference/country_model');
	}

	function index() 
	{
		$this->fungsi->auth(5,'Manage Country');
		$data = array('menu_navigation' => 'navigation', 'main_view' => 'reference/v_country', 'h1_title' => 'Management Country', 'title' => 'Management Country' );
		$this->load->view('afg_template', $data);
	}
	
	function getdatacountry()
	{
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
		$sord = isset($_POST['sord'])?$_POST['sord']:'';
	 
		if(!$sidx) $sidx =1;
		$q = $this->country_model->getdatacountry();
	 
		$hitung = count(array($q));
	 
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
		    $data->rows[$i]['id']=$row->idnya;
		    $data->rows[$i]['cell']=array(
												$row->country_id,
												$row->country_name,
												);
		    $i++;
		}
		echo json_encode($data);
	}

	function manage_data() 
	{
		
 		$id = $this->input->post('id');
 		$opr = $this->input->post('oper'); 		
 		
 		$data = array(
 			'country_id' => strtoupper($this->input->post('country_id')),
			'country_name' => ucwords($this->input->post('country_name')),
 		);
 		
		if($opr=='add')
 		{
 			$status = $this->country_model->insert_country($data);
 			echo json_encode(array('status'=>$status,'mode'=>'add'));
		}
		else if($opr=='edit')
 		{
 			$status = $this->country_model->update_country($data,$id);
 			echo json_encode(array('status'=>$status,'mode'=>'edit'));
 		}
		else if($opr=='del')
 		{
			 $status = $this->country_model->delete_country($id);
			 echo json_encode(array('status'=>$status,'mode'=>'del'));
 		}	
	}
}
