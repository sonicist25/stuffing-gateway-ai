<?php
class Afg_c_delete_job extends CI_Controller{
	
	var $title ='List Deleted_Job';

	public function __construct(){
		parent::__construct();
		$this->fungsi->restrict();
		$this->load->model(array('m_list_delete'));
	}
	
	function index(){
		$data['title'] = $this->title;
		$data['menu_navigation'] ='navigation';
		$data['main_view'] ='finance/v_list_delete_job';
		$this->load->view('afg_template', $data);
	}
	
	function get_table_delete()
	{
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
	 	$sord = isset($_POST['sord'])?$_POST['sord']:'';
	 	
	 	$start = $batas*$hal - $batas;
		$start = ($start<0)?0:$start;
		$_POST['start']=$start;
	 	
	 	$q = $this->m_list_delete->get_table_delete($_POST,$limit=false);				
		$query = $this->m_list_delete->get_table_delete($_POST,$limit=true);	
		
		$hitung = count((array)$q);	 
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
												$row->kode_ref,
												$row->deleted_by,
												$row->pesan,
											);
		    $i++;
		}
		echo json_encode($data);
	}
	
	function manage_data(){
		$status = $this->m_list_delete->manage_data($_POST);
		echo json_encode(array('status'=>$status,'oper'=>$_POST['oper']));
	}

}


/*
Lembar ke-2:Untuk Penjual BKP/Pemberi JKP
		Sebagai Bukti Pajak Keluaran
*/