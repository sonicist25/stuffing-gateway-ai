<?php
class Rebate_coloader extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->library('fungsi');
		$this->fungsi->restrict();
		$this->load->model(array('reference/shipping_company_model','pricelist/rebate_coloader_model',));
		$this->fungsi->dbx = $this->rebate_coloader_model->dbx;
	}
	
	function index()
	{
		$this->fungsi->auth(27,'Rebate Coloader');
		$data = array( 'menu_navigation' => 'navigation', 
							'main_view' => 'pricelist/v_rebate_coloader',
							'title' => 'REBATE FROM COLOADER',
							'rowList'=>'',
		);
		$this->load->view('afg_template', $data);
	}
	
	function open_form()
	{
		$data = array( 'title' => 'REBATE FROM COLOADER');
		$this->load->view('pricelist/v_rebate_coloader_form', $data);
	}
	public function ajax_company_list() {
		$searchTerm = $this->input->get('term'); // Select2 kirim 'term'
		$result = $this->shipping_company_model->getListColoader($searchTerm);

		$data = [];
		foreach ($result as $row) {
			$data[] = [
				'id' => $row->shipping_company_code,
				'text' => $row->shipping_company_code . ' - ' . $row->shipping_company_name
			];
		}

		echo json_encode($data);
	}
	function get_rebate()
	{
		$id = $this->input->post('idRebate');
		$mode = $this->input->post('mode');	
		
		if($mode == 'last')
			$data = $this->rebate_coloader_model->lastRebate();
		else if($mode == 'prev')
			$data = $this->rebate_coloader_model->prevRebate($id);
		else if($mode == 'next')
			$data = $this->rebate_coloader_model->nextRebate($id);
		else if($mode == 'pilih')
			$data = $this->rebate_coloader_model->pilihRebate($id);
			
		echo json_encode(array(
			'idRebate' => $data->id,
			'ref_no' => $data->ref_no,
			'date' => $data->date,
			'code_shipco' => $data->code_shipco,		
		));
	}
	
	function table_browse()
	{
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
	 	$sord = isset($_POST['sord'])?$_POST['sord']:'';
	 	
		if(!$sidx) $sidx =1;
		$q = $this->rebate_coloader_model->table_browse();
	 
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
		    $data->rows[$i]['id']=$row->id;
		    $data->rows[$i]['cell']=array(
		    									$row->ref_no,
												$row->date,
												$row->code_shipco,
											);
		    $i++;
		}
		echo json_encode($data);
	}
	
	function table_detail()
	{
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
	 	$sord = isset($_POST['sord'])?$_POST['sord']:'';
	 	$ref_no = isset($_POST['ref_no'])?$_POST['ref_no']:'';
	
		$q = $this->rebate_coloader_model->table_detail_where($ref_no);
		
		if($sidx)
		$q = $this->rebate_coloader_model->table_detail_where_order($ref_no,$sidx,$sord);
	 	
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
		    $data->rows[$i]['id']=$row->id_detail;
		    $data->rows[$i]['cell']=array(
		    									$row->ref_no,
		    									$row->destination,
												$row->freight,
												$row->amount,
												$row->min_rebate,
											);
		    $i++;
		}
		echo json_encode($data);
	}
	
	function manage_data()
	{
		$mode = $this->input->post('oper');
		$id = $this->input->post('id');
		$data = array (
			'ref_no' 		=> $this->input->post('ref_no'),
			'date'			=> $this->input->post('date'),
			'code_shipco' 	=> $this->input->post('code_shipco'),
		);
		
		if($mode == 'add'){
			$status = $this->rebate_coloader_model->add_data($data);
			echo json_encode(array('status'=>$status,'mode'=>'add'));
		}
		else if($mode == 'edit')
		{
			$status = $this->rebate_coloader_model->update_data($data,$id);
			echo json_encode(array('status'=>$status,'mode'=>'edit'));
		}	
		else if($mode == 'del')
		{
			$status = $this->rebate_coloader_model->delete_data($id);
			echo json_encode(array('status'=>$status,'mode'=>'delete'));
		}	
	}
	
	function manage_detail()
	{
		$mode = $this->input->post('oper');
		$id = $this->input->post('id');
		$data = array (
			'ref_no' 		=> $this->input->post('ref_no'),
			'destination' 	=> $this->input->post('destination'),
			'freight'		=> $this->input->post('freight'),
			'amount'			=> str_replace(',','',$this->input->post('amount')),
			'min_rebate'	=> str_replace(',','',$this->input->post('min_rebate')),
		);
		
		if($mode == 'add')
		{
			$status = $this->rebate_coloader_model->add_data_detail($data);
			echo json_encode(array('status'=>$status,'mode'=>'add'));
		}
		else if($mode == 'edit')
		{
			$status = $this->rebate_coloader_model->update_data_detail($data,$id);
			echo json_encode(array('status'=>$status,'mode'=>'edit'));
		}	
		else if($mode == 'del')
		{
			$status = $this->rebate_coloader_model->delete_data_detail($id);
			echo json_encode(array('status'=>$status,'mode'=>'delete'));
		}	
	}

}

