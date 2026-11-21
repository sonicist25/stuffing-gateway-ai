<?php
class Account extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->library('fungsi');
		$this->fungsi->restrict();
		$this->load->model('reference/account_model');
		$this->fungsi->dbx = $this->account_model->dbx;
	}

	function index() {
		$this->fungsi->auth(20,'Account');
		$data = array('menu_navigation' => 'navigation', 'main_view' => 'reference/v_account', 'h1_title' => '', 'title' => 'Manajemen Akun Induk' );
		$this->load->view('afg_template', $data);
	}
	
	function getDatarefAccount()
	{
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'account_code';
	 
		if(!$sidx) $sidx =1;
		$q = $this->account_model->getDatarefAccount();
		//print_r($this->dbx->last_query());
		//die();
	 
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
												$row->tax_id,
												$row->account_name,
												$row->account_address,
												$row->blok,
												$row->nomor,
												$row->rt,
												$row->rw,
												$row->kecamatan,
												$row->kelurahan,
												$row->kabupaten,
												$row->propinsi,
												$row->kodepos,
												$row->account_phone,
												$row->account_email,
												$row->account_cp,
												$row->created_by,
												);
		    $i++;
		}
		echo json_encode($data);
	}

	function manage_data() 
	{
		$tax_id = $this->input->post('tax_id');
		$account_name = $this->input->post('account_name');
		$account_address = $this->input->post('account_address'); 
		$blok = $this->input->post('blok'); 
		$nomor = $this->input->post('nomor'); 
		$rt = $this->input->post('rt'); 
		$rw = $this->input->post('rw'); 
		$kecamatan = $this->input->post('kecamatan'); 
		$kelurahan = $this->input->post('kelurahan'); 
		$kabupaten = $this->input->post('kabupaten'); 
		$propinsi = $this->input->post('propinsi'); 
		$kodepos = $this->input->post('kodepos'); 
		$account_email = $this->input->post('account_email');
		$account_phone = $this->input->post('account_phone');
		$account_cp = $this->input->post('account_cp');
 		$id = $this->input->post('id');
 		$opr = $this->input->post('oper'); 		
 		
		if($opr=='add')
 		{
 		$status =$this->account_model->insert_account($tax_id,$account_name,$account_address,$blok,$nomor,$rt,$rw,$kecamatan,$kelurahan,$kabupaten,$propinsi,$kodepos,$account_phone,$account_email,$account_cp);
		echo json_encode(array('status'=>$status,'mode'=>'add'));
		//print_r($this->dbx->last_query());
		//die();
		}
		else if($opr=='edit')
 		{
 			$this->account_model->update_account($tax_id,$account_name,$account_address,$blok,$nomor,$rt,$rw,$kecamatan,$kelurahan,$kabupaten,$propinsi,$kodepos,$account_phone,$account_email,$account_cp,$id);
			print_r($this->dbx->last_query());
			die();
 		}
		else if($opr=='del')
 		{
		$this->account_model->delete_account($id);
		//print_r($this->dbx->last_query());
		//die();
 		}	
	}
}
