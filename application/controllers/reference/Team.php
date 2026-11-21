<?php
class Team extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->library('fungsi');
		$this->fungsi->restrict();
		$this->load->model('reference/team_model');
	}

	function index() {
		$this->fungsi->auth(15,'Team');
		$data = array('menu_navigation' => 'navigation', 'main_view' => 'reference/v_team', 'h1_title' => 'Management Team', 'title' => 'Management Team' );
		$this->load->view('afg_template', $data);
	}
	
	function getDatarefteam(){
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'id';
	 
		$start = $batas*$hal - $batas;
		$start = ($start<0)?0:$start;
		$_POST['start']=$start;
		
		$q = $this->team_model->getDatarefteam($_POST,$limit=false);
		$query = $this->team_model->getDatarefteam($_POST,$limit=true);
	 
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
												$row->nama_team,
												$row->anggota,
												$row->aktiv,
												);
		    $i++;
		}
		echo json_encode($data);
	}

	function manage_data() {
		$data = $this->team_model->manage_data($_POST);
		return $data;
	}
}
