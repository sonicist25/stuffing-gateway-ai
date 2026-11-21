<?php
class C_kurs_pajak extends CI_Controller {	

	function __construct(){
		parent::__construct();
		$this->load->library('fungsi');
		$this->fungsi->restrict();
		$this->load->model(array('reference/m_kurs_pajak'));
	}
	
	function index() {
		$this->fungsi->auth(22,'Kurs Pajak');
		$data = array(
			'menu_navigation' => 'navigation', 
			'main_view' => 'reference/v_kurs_pajak', 
			'title' => 'Kurs Pajak',
		);
		$this->load->view('afg_template', $data);
	}
	
	function manage_data() {
		$mode = $this->input->post('oper');
		$id = $this->input->post('id');
		$kolom = $this->input->post('kolom');
		$isi = $this->input->post('isi');

		
		if($mode == 'add')
		{
			$status = $this->m_kurs_pajak->add_data($kolom,$isi);
			echo json_encode(array('status'=>$status,'mode'=>'add'));
		}
		else if($mode == 'edit')
		{
			$status = $this->m_kurs_pajak->update_data($kolom,$isi,$id);
			echo json_encode(array('status'=>$status,'mode'=>'edit'));
		}	
		else if($mode == 'del')
		{
			$status = $this->m_kurs_pajak->delete_data($id);
			echo json_encode(array('status'=>$status,'mode'=>'delete'));
		}
	}
	
	function show_table(){
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
	 	$sord = isset($_POST['sord'])?$_POST['sord']:'desc';
	 	
		if(!$sidx) $sidx =1;
		$q = $this->m_kurs_pajak->getDataKursPajak();
	 
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
												$this->fungsi->jin_date_str($row->mulai_tgl),
												$this->fungsi->jin_date_str($row->smp_dgn),
												$row->kurs,
												$row->no_kmk,
												$this->fungsi->jin_date_str($row->tgl_kmk),
										);
		    $i++;
		}
		echo json_encode($data);
	}
	
}	

/* END OF USER MANAGEMENT */