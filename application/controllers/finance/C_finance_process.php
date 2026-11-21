<?php
class C_finance_process extends CI_Controller{
	
	var $title ='Finance Proses';
	
	public function __construct()
	{
		parent::__construct();
		$this->fungsi->restrict();
		$this->load->model(array('finance/m_finance_proses','finance/m_inv_to_shipper'));
	}
		
	function index()
	{
		$this->fungsi->auth(41,'Finance Proses');
		$data['title'] = $this->title;
		$data['h1_title'] ='INVOICE TO SHIPPER';
		$data['table_title'] ='INVOICE';
		$data['menu_navigation'] ='navigation';
		$data['main_view'] ='finance/v_finance_proses';
		$this->load->view('afg_template', $data);
	}
	
		function open_form_invoice()
	{
		$oper = $this->input->post('oper');
		$data['job_no'] = $this->input->post('job_no');
		$data['id'] = 	$this->input->post('id');
		$data['kurs']= $this->kurs($this->input->post('job_no'));
		if($oper=='add') {
			$data['new_no'] = $this->fungsi->get_new_num_inv($_POST);
			//print_r($this->db->last_query());
			$data['agent']	= $this->input->post('agent');
			$opt = $this->m_finance_proses->get_opt_agent_cost($data['agent'])->result();
			$group['']='';
			foreach ($opt as $row)
			{	
				$group[$row->g]=$row->g;
			}
			$data['group']=$group;
			$data['agent_name'] = $this->fungsi->get_nama_code('refagentcompany','agent_company_code','agent_company_name',$data['agent']);
			$data['agent_add'] = $this->fungsi->get_alamat_code('refagentcompany','agent_company_code','agent_company_address',$data['agent']);
			$data['id']= floatval($this->fungsi->get_last_id_proses(array(
				'region_id'=> $this->session->userdata('region_id'),
				'fin_proses'=>'1')))+1;	
			$this->load->view('finance/v_finance_proses_add', $data);
		} else {
			//print_r($data['kurs']);
			//die();
			$this->load->view('finance/v_finance_proses_edit', $data);
		}
	}

	function kurs($kode_ref)
		{
			$data = $this->m_inv_to_shipper->get_kurs($kode_ref);
			//print_r($this->db->last_query());
			return $data;
		}	
	

	public function reopen_paid_invoice_revision()
	{
		if (!$this->m_finance_proses->can_revise_paid_invoice()) {
			return $this->_json_post_response(403, [
				'status'            => 0,
				'http'              => 403,
				'message'           => 'PAID_INVOICE_REVISION_ACCESS_DENIED',
				'requires_revision' => 0,
			]);
		}

		$finance_id = (int)$this->input->post('finance_id', true);
		$reason     = trim((string)$this->input->post('reason', true));

		$result = $this->m_finance_proses->reopen_paid_invoice_for_revision(
			$finance_id,
			$reason
		);

		$http = isset($result['http'])
			? (int)$result['http']
			: (!empty($result['status']) ? 200 : 422);

		return $this->_json_post_response($http, $result);
	}


	function tarik_data_table_satu()
	{
		$data = $this->m_finance_proses->tarik_data_table_satu($_POST);
		//print_r($this->db->last_query());
		//die();
		echo json_encode($data);
	}
	
	public function post_invoice_debit_note_table_satu()
	{
		$region_id  = trim((string)$this->session->userdata('region_id'));
		$finance_id = (int)($this->input->post('id', true) ?: $this->input->post('finance_id', true));
		$invoice_no = trim((string)$this->input->post('invoice_no', true));

		if ($region_id === '') {
			return $this->_json_post_response(422, [
				'status'  => 0,
				'message' => 'region_id session wajib tersedia',
			]);
		}

		$finance = $this->m_finance_proses->find_finance_for_post($finance_id, $invoice_no);

		if (!$finance) {
			return $this->_json_post_response(404, [
				'status'  => 0,
				'message' => 'Invoice/DN tidak ditemukan pada region aktif',
				'data'    => [
					'finance_id' => $finance_id,
					'invoice_no' => $invoice_no,
					'region_id'  => $region_id,
				],
			]);
		}

		$finance_id = (int)$finance['id'];
		$invoice_no = trim((string)$finance['number']);
		$jenis      = strtoupper(trim((string)($finance['jenis'] ?? '')));

		if (!in_array($jenis, ['INVOICE', 'DEBIT NOTE'], true)) {
			return $this->_json_post_response(422, [
				'status'  => 0,
				'message' => 'Hanya INVOICE / DEBIT NOTE yang dapat diposting dari proses ini',
				'data'    => ['jenis' => $jenis],
			]);
		}

		/*
		 * Jakarta tetap menggunakan ERP legacy via curl_helper.
		 * Cabang lain masuk ERP regional/CI3 melalui M_posting.
		 */
		if (strcasecmp($region_id, 'Jakarta') === 0) {
			if (!function_exists('body_jasaweb_invoice')) {
				$this->load->helper('curl');
			}

			if (!function_exists('body_jasaweb_invoice')) {
				return $this->_json_post_response(500, [
					'status'  => 0,
					'message' => 'curl_helper/body_jasaweb_invoice tidak tersedia',
				]);
			}

			$dbx = $this->m_finance_proses->get_active_db();
			return body_jasaweb_invoice(['invoice' => $invoice_no], $dbx);
		}

		$postRes = $this->m_finance_proses->post_invoice_debit_note_table_satu([
			'id'          => $finance_id,
			'job_no'      => trim((string)($finance['job_no'] ?? '')),
			'agent'       => trim((string)($finance['kepada_kode'] ?? '')),
			'group'       => trim((string)($finance['group'] ?? '')),
			'jenis'       => $jenis,
			'kepada'      => trim((string)($finance['kepada'] ?? '')),
			'number'      => $invoice_no,
			'kurs'        => (float)($finance['kurs'] ?? 1),
			'destination' => '',
		]);

		$ok   = !empty($postRes['status']);
		$http = !empty($postRes['http']) ? (int)$postRes['http'] : ($ok ? 200 : 500);

		return $this->_json_post_response($http, [
			'status'  => $ok ? 1 : 0,
			'message' => $ok
				? 'Invoice/DN berhasil diposting ke ERP region ' . $region_id
				: ($postRes['message'] ?? 'POST_FAILED'),
			'data'    => $postRes['data'] ?? null,
		]);
	}

	private function _json_post_response($http, array $payload)
	{
		return $this->output
			->set_status_header((int)$http)
			->set_content_type('application/json', 'utf-8')
			->set_output(json_encode($payload));
	}
		
	function tampil_table_satu()
	{
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
	 	$sord = isset($_POST['sord'])?$_POST['sord']:'';
	
	 	$data_post = array(
	 		'table'=>$_POST['table'],
	 		'kode_ref'=>$_POST['kode_ref'],
	 		'number'=>$_POST['number'],
	 	);
	 		 	
		if(!$sidx) 
		$sidx = 1; 
				
		$q = $this->m_finance_proses->tampil_data($data_post);
	 
		$hitung = count((array)$q);
	 
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
					$row->bl_no,
					$row->code,
					$row->item,
					$row->qty,
					$row->unit_price,
					floatval($row->qty) * floatval($row->unit_price),
				);
		    $i++;
		}
		echo json_encode($data);	
}
	
	function addProcess()
	{
		$data = array
		(
			'job_no' 			=> $this->input->post('job_nomer'),
			'jenis'				=>	$this->input->post('jenis'),
			'group_invoice' 	=> strtoupper($this->input->post('group_invoice')),
			'invoice_no' 		=> str_replace('IN/', '', $this->input->post('invoice_no')),
			'invoice_date' 	=> $this->fungsi->jin_date_sql($this->input->post('invoice_date')),
			'shipper_id' 		=> $this->input->post('shipper_id'),
			'shipper_name' 	=> $this->input->post('shipper_id'),
			'shipper_address' => $this->input->post('shipper_address'),
			'si_exportir' 		=> $this->input->post('si_exportir'),
			'kurs' 				=> $this->input->post('kurs')
    	);
		
		$id = $this->input->post('id');
		$oper = $this->input->post('oper');
		
		if($oper == 'add') {
			$response = $this->m_finance_proses->add($data);
			echo json_encode(array('status'=>$response,'oper'=>'add'));
			
		}elseif($oper == 'edit'){
			$response = $this->m_finance_proses->update($data,$id);
			echo json_encode(array('status'=>$response,'oper'=>'edit'));
		}
	}
	
	function delete()
	{
		$cn_id_del = $this->input->post('id_del');
		$this->m_finance_proses->delete($cn_id_del);
	}
	
	
	function get_table_dua()
	{
		
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
	 	$sord = isset($_POST['sord'])?$_POST['sord']:'';
	 	
	 	$start = $batas*$hal - $batas;
		$start = ($start<0)?0:$start;
		$_POST['start'] = $start;	
	 
	 	$q = $this->m_finance_proses->get_table_dua($_POST,$limit=false);
	 	$query = $this->m_finance_proses->get_table_dua($_POST,$limit=true);
	 	
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
			$data->rows[$i]['id']=$row->sp_id;
			$data->rows[$i]['cell']=array(
												$row->kode_ref,
												$row->agen_penagihan,
												$row->trk_data,
												$this->m_finance_proses->get_blue($row->kode_ref),
												);
		    $i++;
		}
		echo json_encode($data);
	}
	
	function manage_data()
	{
		$status = $this->m_finance_proses->manage_data($_POST);

		if (is_array($status)) {
			$status['oper'] = $_POST['oper'] ?? '';
			echo json_encode($status);
			return;
		}

		echo json_encode(array(
			'status' => $status,
			'oper'   => $_POST['oper'] ?? ''
		));
	}
	
	function get_table_tiga()
	{
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
	 	$sord = isset($_POST['sord'])?$_POST['sord']:'';
	 	
	 	$data_post = array(
	 		'table'=>$_POST['table'],
	 		'job_no'=>$_POST['job_no'],
	 	);
	 	
		if(!$sidx) $sidx =1;
		$q = $this->m_finance_proses->tampil_data($data_post);

		$hitung = count($q->result());
	 
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
			if ($row->jenis == 'CREDIT NOTE'){
			$status = '';
			}else{
			$status = $row->kurs;	
			}
		    $data->rows[$i]['id']=$row->id;
			 $data->rows[$i]['cell']=array(
					$row->number,
					$this->fungsi->jin_date_str($row->date),
					$row->kepada,
					$row->kepada_kode,
					$row->group,
					$row->currency,
					$row->jenis,
					$row->ppn,
					$row->kurs,
					$row->status,
					$row->created_by,
				);
		    $i++;
		}
		echo json_encode($data);
	}

	function manage_table_tiga()
	{
		$id = $this->input->post('id');
 		$opr = $this->input->post('oper');
 		
 		$data = array(
							'no_invoice'	=>	$this->input->post('no_invoice'), 
							'item'			=>	$this->input->post('item'),
							'qty'				=>	$this->input->post('qty'), 
							'currency'		=>	strtoupper($this->input->post('currency')),
							'price'			=>	$this->input->post('price'),
							'remarks'		=>	$this->input->post('remarks'),
							'pre_col'		=>	$this->input->post('pre_col')
		);
 		
		if($opr=='add')
 		{
 			$response = $this->m_finance_proses->insertDataTabelTiga($data);
 			echo json_encode(array('status'=>$response,'oper'=>'add'));
		}
		else if($opr=='edit')
 		{
 			$response = $this->m_finance_proses->updateDataTabelTiga($data, $id);
 			echo json_encode(array('status'=>$response,'oper'=>'edit'));
 		}
		else if($opr=='del')
 		{
			$response = $this->m_finance_proses->deleteDataTabelTiga($id);
			echo json_encode(array('status'=>$response,'oper'=>'del'));
 		}
	}
			
	function dropdown()
	{
		$value = $this->input->post('value');
		$data['option']['']='';
		if($value == 'AGENT' ) {
			$q = $this->m_finance_proses->getListAgent()->result();
			foreach ($q as $row)
			{	
				$data['option'][$row->agent_company_code]=$row->agent_company_code;
			}	
		} else if($value == 'SHIPCO' ||  $value == 'COLOADER') {
			$q = $this->m_finance_proses->getListShippco()->result();
			foreach ($q as $row)
			{	
				$data['option'][$row->shipping_company_code]=$row->shipping_company_code;
			}
		} else if($value == 'WAREHOUSE' ) {
			$q = $this->m_finance_proses->getListWarehouse()->result();
			foreach ($q as $row)
			{	
				$data['option'][$row->warehouse_code]=$row->warehouse_code;
			}
		} else if($value == 'TRUCKING' ) {
			$q = $this->m_finance_proses->getListTrucking()->result();
			foreach ($q as $row)
			{	
				$data['option'][$row->trucking_company_code]=$row->trucking_company_code;
			}
		} else if($value == 'CONSIGNEE' ) {
			$q = $this->m_finance_proses->getListConsignee()->result();
			foreach ($q as $row)
			{	
				$data['option'][$row->consignee_notify_code]=$row->consignee_notify_code;
			}
		} else if($value == 'SHIPPER' ) {
			$q = $this->m_finance_proses->getListShipper()->result();
			foreach ($q as $row)
			{	
				$data['option'][$row->shipper_id]=$row->shipper_id;
			}
		}
		
		
		$dropdown = form_dropdown('nameForm',$data['option'],'','id="nameForm" style="width: 200px; margin-bottom: 5px"');
		//$group = form_dropdown('groupForm',array('',''),'','id="groupForm" style="width: 100px; margin-bottom: 5px"');
		//echo json_encode(array("dropdown" => $dropdown,'group'=>$group));		
		echo json_encode(array("dropdown" => $dropdown));		
	}
	
	function get_value()
	{
		$jenis 	= $this->input->post('jenis');
		$kepada 	= $this->input->post('kepada');
		$code 	= $this->input->post('code');
		
		if($_POST['oper']=='add') {
			$data['id']= $this->fungsi->get_last_id_proses()+1;
			$id = $data['id'];
		} else {
			$id = $_POST['id'];
		}
		
		if($kepada == 'AGENT' ) {
			$nama = $this->fungsi->get_nama_code('refagentcompany','agent_company_code','agent_company_name',$code);
			$alamat = $this->fungsi->get_alamat_code('refagentcompany','agent_company_code','agent_company_address',$code);	
		}
		if($kepada == 'SHIPCO' ||  $kepada == 'COLOADER') {
			$nama = $this->fungsi->get_nama_code('refshippingcompany','shipping_company_code','shipping_company_name',$code);
			$alamat = $this->fungsi->get_alamat_code('refshippingcompany','shipping_company_code','shipping_company_address',$code);	
		}
		if($kepada == 'WAREHOUSE' ) {
			$nama = $this->fungsi->get_nama_code('refwarehouse','warehouse_code','warehouse_name',$code);
			$alamat = $this->fungsi->get_alamat_code('refwarehouse','warehouse_code','warehouse_address',$code);	
		}
			if($kepada == 'TRUCKING' ) {
			$nama = $this->fungsi->get_nama_code('reftruckingcompany','truckingcompany_code','truckingcompany_name',$code);
			$alamat = $this->fungsi->get_alamat_code('reftruckingcompany','truckingcompany_code','truckingcompany_address',$code);	
		}
			if($kepada == 'SHIPPER' ) {
			$nama = $this->fungsi->get_nama_code('mstshipper','shipper_id','shipper_name',$code);
			$alamat = $this->fungsi->get_alamat_code('reftruckingcompany','truckingcompany_code','truckingcompany_address',$code);	
		}
			if($kepada == 'CONSIGNEE' ) {
			$nama = $this->fungsi->get_nama_code('refconsigneenotify','consignee_notify_code','consignee_notify_name',$code);
			$alamat = $this->fungsi->get_alamat_code('refconsigneenotify','consignee_notify_code','consignee_notify_address',$code);	
		}
		echo json_encode(array("status"=>'1',"nama" => $nama,'alamat'=>$alamat, 'id'=>$id));	
	}
	
	function get_content()
	{
		$data = $this->m_finance_proses->get_content($_POST);
		echo json_encode($data);
	}

	function print_out()
	{
		$ttlnya ='';
		$total = array();
		$curr = array("","");
		header('HTTP/1.0 200 OK');
		header('HTTP/1.1 200 OK');
		header('Content-Type: application/pdf, UTF-8');
  		header('Content-Disposition: inline; filename="'.$this->title.'";');
  		header('Last-Modified: '.gmdate('D, d M Y H:i:s', time()).' GMT');
  		
  		$id=$this->input->get('id');
  		$finance_hdr = $this->db->get_where('x_finance', ['id' => $id])->row();
		$agent_code  = $finance_hdr ? strtoupper(trim((string)$finance_hdr->kepada_kode)) : '';
		$jenis       = strtoupper((string)$this->input->get('jenis', TRUE));
		$this->load->library('FPDF_MC_Table');
		$pdf=new FPDF_MC_Table();	
		$pdf->AddPage('P','Legal');
		$region = trim((string)$this->session->userdata('region_id'));
		$logo='logo.png';
		$company = 'PT. GATEWAY CONTAINER LINE';
		
		if ($region=='Jakarta') {
			$pdf->SetFont('verdana','',12);
			$pdf->SetXY(130, 10);
			$pdf->write(5,'PT. GATEWAY CONTAINER LINE');
			$pdf->SetFont('verdana','',7);
			$pdf->SetXY(148, 17);
			$pdf->write(0,'JL. OTISTA III RT. 001 RW. 001 NO. 18');
			$pdf->SetXY(153, 20);
			$pdf->write(0,'CIPINANG CEMPEDAK JATINEGARA');
			$pdf->SetXY(168, 23);
			$pdf->write(0,'JAKARTA TIMUR 13340');
			$pdf->SetXY(143, 26);
			$pdf->write(0,'PHONE : ( 62-21 ) 8591-8669 ( HUNTING )');
			$pdf->SetXY(163, 29);
			$pdf->write(0,'FAX : ( 62-21 ) 8591-8664');
			$pdf->SetFont('verdana','',8);
			$pdf->SetXY(161, 32);
			$pdf->write(0,'general@gateway-id.com');
		} 
		else if ($region=='Surabaya')
		{
		$pdf->SetFont('verdana','',12);	
		$pdf->SetXY(10, 13);
		$pdf->Cell(190,4, 'PT. GATEWAY CONTAINER LINE',0,1,'R',false,'');
		$pdf->SetFont('verdana','',8);
		$pdf->Cell(190,4,strtoupper('JL GAYUNGSARI BARAT NO 91B'),0,1,'R',false,'');
		$pdf->Cell(190,4,'GAYUNGAN, KEC. GAYUNGAN',0,1,'R',false,'');
		$pdf->Cell(190,4,'SURABAYA 60235, JAWA TIMUR',0,1,'R',false,'');
		$pdf->Cell(190,4,'PHONE : 031 82522370 / 031 82522405/031 82522827-830',0,1,'R',false,'');
		$pdf->Cell(190,4,'mgtsby@gateway-id.com',0,1,'R',false,'');
		} 
		else if ($region=='Cikarang')
		{
		$pdf->SetFont('verdana','',12);
		$pdf->SetXY(130, 10);
		$pdf->write(5,'PT. GATEWAY CONTAINER LINE');
		$pdf->SetFont('verdana','',7);
		$pdf->SetXY(172, 17);
		$pdf->write(0,'THE HIVE TANAMERA LIPPO CIKARANG');
		$pdf->SetXY(173, 20);
		$pdf->write(0,'JL. WATERFRONT RIVER DRIVE NO. B10 (LT. 3)');
		$pdf->SetXY(173.5, 23);
		$pdf->write(0,'CIBATU, CIKARANG SELATAN, BEKASI');
		$pdf->SetXY(166, 26);
		$pdf->write(0,'PHONE :(021)89100449');
		$pdf->SetXY(170, 29);
		$pdf->write(0,'FAX :(021)89100449');
		$pdf->SetFont('verdana','',8);
		$pdf->SetXY(161, 32);
		$pdf->write(0,'lisbeth@gateway-id.com');
		}
		else if ($region=='Makassar')
		{
			$pdf->SetFont('verdana','',12);
			$pdf->SetXY(10, 13);
			$pdf->Cell(190,4, 'PT. GATEWAY CONTAINER LINE',0,1,'R',false,'');
			$pdf->SetFont('verdana','',8);
			$pdf->Cell(190,4,strtoupper('JL. DR. WAHIDIN SUDIRO HUSODO NO.278'),0,1,'R',false,'');
			$pdf->Cell(190,4,'MALIMONGAN, KEC. WAJO',0,1,'R',false,'');
			$pdf->Cell(190,4,'KOTA MAKASSAR, SULAWESI SELATAN 90163',0,1,'R',false,'');
			$pdf->Cell(190,4,'PHONE : (0411) 878658',0,1,'R',false,'');
			$pdf->Cell(190,4,'mgtupg@gateway-id.com',0,1,'R',false,'');
		}
		else if ($region=='Semarang')
		{
		$pdf->SetFont('verdana','',12);	
		$pdf->SetXY(10, 13);
		$pdf->Cell(190,4, 'PT. GATEWAY CONTAINER LINE',0,1,'R',false,'');
		$pdf->SetFont('verdana','',8);
		$pdf->Cell(190,4,strtoupper('JL. SEMARANG INDAH NO.D14 / 20A, TAWANGMAS'),0,1,'R',false,'');
		$pdf->Cell(190,4,'KEC. SEMARANG BAR.',0,1,'R',false,'');
		$pdf->Cell(190,4,'KOTA SEMARANG, JAWA TENGAH 50144',0,1,'R',false,'');
		$pdf->Cell(190,4,'PHONE : ( 62-24 ) 76632972',0,1,'R',false,'');
		$pdf->Cell(190,4,'mgtsrg@gateway-id.com',0,1,'R',false,'');
		}
		else if ($region=='Medan')
		{
		$pdf->SetFont('verdana','',12);	
		$pdf->SetXY(10, 13);
		$pdf->Cell(190,4, 'PT. GATEWAY CONTAINER LINE',0,1,'R',false,'');
		$pdf->SetFont('verdana','',8);
		$pdf->Cell(190,4,strtoupper('MANDIRI BUILDING 7TH FLOOR'),0,1,'R',false,'');
		$pdf->Cell(190,4,'JL. IMAM BONJOL NO. 16-D MEDAN 20112',0,1,'R',false,'');
		$pdf->Cell(190,4,'SUMATRA UTARA 20112 - INDONESIA',0,1,'R',false,'');
		$pdf->Cell(190,4,'PHONE : (62-61) 4559876',0,1,'R',false,'');
		}
		else if ($region=='Bandung')
		{
		$pdf->SetFont('verdana','',12);	
		$pdf->SetXY(10, 13);
		$pdf->Cell(190,4, 'PT. GATEWAY CONTAINER LINE',0,1,'R',false,'');
		$pdf->SetFont('verdana','',8);
		$pdf->Cell(190,4,strtoupper('METRO TRADE CENTRE, JL SOEKARNO HATTA'),0,1,'R',false,'');
		$pdf->Cell(190,4,'BLOK D NO.21 SEKEJATI, KEC. BUAH BATU',0,1,'R',false,'');
		$pdf->Cell(190,4,'KOTA BANDUNG, JAWA BARAT 40286',0,1,'R',false,'');
		$pdf->Cell(190,4,'PHONE : (022) 7502283',0,1,'R',false,'');
		}
		
		$pdf->Image(APPPATH.'third_party/img/'.$logo,10,12,+50);
		$pdf->SetFont('verdana','B',11);
		$pdf->SetXY(87, 45);
		$pdf->SetFont('verdana','',14);
		$pdf->write(0,'** '.$this->input->get('jenis').' **');
		$pdf->SetFont('verdana','',11);
		$pdf->SetXY(175, 49);
		$pdf->write(0,$this->input->get('no_invoice'));				
		$pdf->Line(10,53,200,53);		
		$pdf->Line(120,87,120,53);		
		$pdf->SetFont('verdana','',8);
		$pdf->SetXY(10, 56);
		$pdf->write(0,'TO :');
		$pdf->SetXY(10, 59);	
				
		$pdf->MultiCell(105,3.5,strtoupper($this->input->get('kepada')),0,"L",false );	
				
		$pdf->SetXY(123, 56);
		$pdf->write(0,'VESSEL');
		$pdf->SetXY(149, 56);
		$pdf->write(0,':');		
		$pdf->SetXY(152, 56);
		$pdf->write(0,$this->input->get('vessel'));		
		$pdf->SetXY(123, 60);
		$pdf->write(0,'ETD / ETA');
		$pdf->SetXY(149, 60);
		$pdf->write(0,':');
		$pdf->SetXY(152, 60);
		$pdf->write(0,$this->input->get('etd').' / '.$this->input->get('eta'));		
		$pdf->SetXY(123, 64);
		$pdf->write(0,'ORIGIN');
		$pdf->SetXY(149, 64);
		$pdf->write(0,':');
		$pdf->SetXY(152, 64);
		$pdf->write(0,$this->input->get('origin'));	
		$pdf->SetXY(123, 68);
		$pdf->write(0,'DESTINATION');
		$pdf->SetXY(149, 68);
		$pdf->write(0,':');
		$pdf->SetXY(151, 68);
		$pdf->write(0,$this->input->get('destination'));		
		$pdf->SetXY(123, 72);
		$pdf->write(0,'TERM');
		$pdf->SetXY(149, 72);
		$pdf->write(0,':');
		$pdf->SetXY(151, 72);
		if ($this->input->get('jenis') != "INVOICE" )
		{$pdf->write(0,'COLLECT'); }
		else
		{$pdf->write(0,'PREPAID');}

		$pdf->SetXY(123, 76);
		$pdf->write(0,'DATE');
		$pdf->SetXY(149, 76);
		$pdf->write(0,':');
		$pdf->SetXY(151, 76);
		$pdf->write(0,$this->input->get('date'));
		
		$pdf->SetXY(10, 87);
		$pdf->Cell(80, 8,"DETAIL OF CHARGES",'TRB',0,'C');
		$pdf->Cell(30, 8,"QUANTITY",'TRB',0,'C');
		$pdf->Cell(30, 8,"UNIT RATE",'TRB',0,'C');
		$pdf->Cell(50, 8,"AMOUNT",'TB',1,'C');
		$pdf->Ln(3);
		
		$pdf->Cell(40, 4,"MANIFEST NO.",0,0,'L');
		$pdf->Cell(10, 4,":",0,0,'L');
		$pdf->Cell(50, 4,$this->input->get('manifest'),0,1,'L');
		$pdf->Cell(40, 4,"CONTAINER NO.",0,0,'L');
		$pdf->Cell(10, 4,":",0,0,'L');
		$pdf->Cell(50, 4,$this->input->get('container'),0,1,'L');		
		$pdf->Cell(40, 4,"MBL NO.",0,0,'L');
		$pdf->Cell(10, 4,":",0,0,'L');
		$pdf->Cell(50, 4,$this->input->get('obl'),0,1,'L');	
		$pdf->Cell(40, 4,"HBL NO.",0,0,'L');
		$pdf->Cell(10, 4,":",0,0,'L');
		$pdf->Cell(50, 4,"SEE OUR MANIFEST",0,0,'L');
		
				
		$data = $this->m_finance_proses->tampil_data(array('table'=>'x_finance_item','number'=>$id,'item LIKE'=>'REBATE AGENT%'));
		if($data->num_rows()>0) {
			$pdf->SetXY(20, 125);
			$pdf->write(0,'REBATES :');
		}
		$pdf->Ln(5);
		
		$ln = $data->num_rows();
		
		
		$i=1;
		foreach($data->result() as $row) {
			$pdf->Cell(10, 5,$i,0,0,'C');
			$pdf->Cell(70, 5,$row->bl_no.' - '.strtoupper($row->item),0,0,'L',false);
			$pdf->Cell(30, 5,$row->qty,0,0,'C',false);
			$pdf->Cell(8, 5,$row->currency,0,0,'R',false);
			$pdf->Cell(20, 5,$this->fungsi->pecah($row->unit_price,','),0,0,'R',false);
			$pdf->Cell(12, 5,$row->currency,0,0,'R',false);
			$pdf->Cell(30, 5,$this->fungsi->pecah($row->price,','),0,0,'R',false);
			$pdf->Ln();
			$i++;
		}
		$pdf->Ln(5);
		
		$data_of = $this->m_finance_proses->tampil_data(array('table'=>'x_finance_item','number'=>$id,'item not LIKE'=>'REBATE AGENT%'));
		if($data_of->num_rows()>0 && $this->input->get('jenis') != "INVOICE" ) {
			$pdf->Cell(10, 5,'',0,0,'L');
			$pdf->Cell(190, 5,'O/F COLLECT',0,0,'L');
		}
		$pdf->Ln(8);
		$n=1+$ln;
		foreach($data_of->result() as $row) {
			$cell = $row->bl_no.' - '.strtoupper($row->item);
			if($row->bl_no=="") {
				$cell = $row->item;
			}
			$pdf->Cell(10, 5,$n,0,0,'C');
			$pdf->Cell(70, 5,$cell,0,0,'L',false);
			$pdf->Cell(30, 5,$this->fungsi->pecah4($row->qty,','),0,0,'C',false);
			$pdf->Cell(8, 5,$row->currency,0,0,'R',false);
			$pdf->Cell(20, 5,$this->fungsi->pecah($row->unit_price,','),0,0,'R',false);
			$pdf->Cell(12, 5,$row->currency,0,0,'R',false);
			$pdf->Cell(30, 5,$this->fungsi->pecah($row->price,','),0,0,'R',false);
			$pdf->Ln();
			$n++;
		}
		
		$lm = $data_of->num_rows();
		
		$lz = $ln+$lm;
		
		if($lz<=27) 
		{
			$lq = 27 - $lz;
			for($i=0;$i<=$lq;$i++) 
			{
				$pdf->Cell(10, 5,"",0,1,'C');
			}
		}
		else 
		{
			for($i=0;$i<=7;$i++) 
			{
				$pdf->Cell(10, 5,"",0,1,'C');
			}
		}
		
		$ttl_usd = '';
		$ttl_idr = '';
		
		$jumlah_idr = $this->m_inv_to_shipper->get_ttl_db_note($id,'RP.');
		$jumlah_usd = $this->m_inv_to_shipper->get_ttl_db_note($id,'USD');
		
		if(!empty($jumlah_usd)) 
		$ttl_usd=$this->fungsi->pecah(array_sum($jumlah_usd),',');
		
		if(!empty($jumlah_idr)) 
		$ttl_idr=$this->fungsi->pecah(array_sum($jumlah_idr),',');

		$pdf->Ln(2);
		
		if($ttl_usd!='') { $usd = 'USD :'; } else { $usd = ''; }
		if($ttl_idr!='') { $idr = 'IDR :'; } else { $idr = ''; }

		$pdf->Cell(140, 5,'','T',0,'L',false);
		$pdf->Cell(12, 5,$usd,'TL',0,'R',false);
		$pdf->Cell(28, 5,$ttl_usd,'T',0,'R',false);
		$pdf->Cell(17, 5,'','T',1,'R',false);
		
		$pdf->Cell(140, 5,'',0,0,'L',false);
		$pdf->Cell(12, 5,$idr,'LB',0,'R',false);
		$pdf->Cell(28, 5,$ttl_idr,'B',0,'R',false);
		$pdf->Cell(17, 5,'','B',1,'R',false);
		
		$pdf->Ln(2);
		if ($ttl_idr!=''){
			$pdf->SetFont('verdana','',8);
			$pdf->Cell(190, 4,$company,0,1,'R',false);
			$pdf->Cell(10, 4,'IDR : ',0,0,'L',false);
			$pdf->Cell(180, 4,$company,0,1,'L',false);				
			$pdf->Cell(10, 4,'',0,0,'L',false);
			$pdf->Cell(180, 4,'MAYBANK INDONESIA ',0,1,'L',false);
			$pdf->Cell(10, 4,'',0,0,'L',false);
			$pdf->Cell(180, 4,'CAB. TG. PRIOK ENGGANO',0,1,'L',false);
			$pdf->Cell(10, 4,'',0,0,'L',false);
			$pdf->Cell(180, 4,'A/C : 2082409727',0,1,'L',false);
		}
		else 
		{
			$pdf->Cell(190, 4,$company,0,1,'R',false);
			$pdf->Cell(10, 4,'USD : ',0,0,'L',false);
			$pdf->Cell(180, 4,$company,0,1,'L',false);				
			$pdf->Cell(10, 4,'',0,0,'L',false);
			$pdf->Cell(180, 4,'MAYBANK INDONESIA ',0,1,'L',false);
			$pdf->Cell(10, 4,'',0,0,'L',false);
			$pdf->Cell(180, 4,'CAB. TG. PRIOK ENGGANO',0,1,'L',false);
			$pdf->Cell(10, 4,'',0,0,'L',false);
			$pdf->Cell(180, 4,'A/C : 2082001085',0,1,'L',false);
		}
		$pdf->Ln(2);
		$pdf->Cell(186, 4,'______________________',0,1,'R',false);
		$pdf->Output('Finance process.pdf','I'); 
	}
	
	function print_out_new()
{

	header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="'.$this->title.'.pdf";');
    header('Last-Modified: '.gmdate('D, d M Y H:i:s', time()).' GMT');

    $id      = $this->input->get('id', TRUE);
    $jenis   = strtoupper((string)$this->input->get('jenis', TRUE));       // DEBIT NOTE / CREDIT NOTE / INVOICE
    $no_doc  = (string)$this->input->get('no_invoice', TRUE);
    $dateStr = (string)$this->input->get('date', TRUE);
	// parsing string dd-mm-yyyy
	$dateObj = DateTime::createFromFormat('d-m-Y', $dateStr);

	// Null-safe: jika format tanggal tidak sesuai, tampilkan nilai aslinya.
	$intlDate = $dateObj instanceof DateTime ? $dateObj->format('d-F-Y') : $dateStr;


    // TO
    $toName  = (string)$this->input->get('kepada', TRUE);
    $toTaxId = (string)$this->input->get('to_tax_id', TRUE);
    $toTel   = (string)$this->input->get('to_tel', TRUE);
    $toEmail = (string)$this->input->get('to_email', TRUE);

    // Service Details
    $vessel      = (string)$this->input->get('vessel', TRUE);
    $etd         = (string)$this->input->get('etd', TRUE);
    $eta         = (string)$this->input->get('eta', TRUE);
    $origin = (string)$this->input->get('origin', TRUE);
    $destination = (string)$this->input->get('destination', TRUE);
    $term        = ($jenis != "INVOICE") ? 'COLLECT' : 'PREPAID';

    // Job Details
    $manifest  = (string)$this->input->get('manifest', TRUE);
    $container = (string)$this->input->get('container', TRUE);
    $mbl       = (string)$this->input->get('obl', TRUE);
    $hbl       = "SEE DETAIL OF CHARGES";

    $this->load->library('FPDF_MC_Table');
    $pdf = new FPDF_MC_Table();
    $pdf->AliasNbPages();
    $pdf->SetMargins(10, 10, 10);
    $pdf->SetAutoPageBreak(true, 15);

    // a.pdf itu A4
    $pdf->AddPage('P','A4');

    $region  = trim((string)$this->session->userdata('region_id'));
    $logo    = APPPATH.'third_party/img/logo.png';
    $company = 'PT. GATEWAY CONTAINER LINE';

    // warna template (navy)
    $NAVY = [10, 52, 92];      // biru tua
    $LGRAY = [245, 247, 250];  // background box
    $BORD = [180, 180, 180];

    // =========================
    // HEADER REGION (TETAP ADA) -> isi variabel agar bisa ditata seperti a.pdf
    // =========================
    $addrLines = [];
    $phoneFaxEmail = '';

    if ($region=='Jakarta') {
        $addrLines = [
            'JL. OTISTA III RT. 001 RW. 001 NO. 18 CIPINANG CEMPEDAK',
            'JATINEGARA JAKARTA TIMUR 13340 INDONESIA'
        ];
        $phoneFaxEmail = 'PHONE : +62218591-8669, FAX : +62218591-8664, Email : general@gateway-id.com';
    }
    else if ($region=='Surabaya') {
        $addrLines = [
            'JL GAYUNGSARI BARAT NO 91B, GAYUNGAN, KEC. GAYUNGAN',
            'SURABAYA 60235, JAWA TIMUR'
        ];
        $phoneFaxEmail = 'PHONE : 031 82522370 / 031 82522405 / 031 82522827-830 / Email : mgtsby@gateway-id.com';
    }
    else if ($region=='Cikarang') {
        $addrLines = [
            'THE HIVE TANAMERA LIPPO CIKARANG',
			'JL. WATERFRONT RIVER DRIVE NO. B10 (LT. 3)',
            'CIBATU, CIKARANG SELATAN, BEKASI'
        ];
        $phoneFaxEmail = 'PHONE : (021) 89100449 / FAX : (021) 89100449 / Email : lisbeth@gateway-id.com';
    }
    else if ($region=='Makassar') {
        $addrLines = [
            'JL. DR. WAHIDIN SUDIRO HUSODO NO.278, MALIMONGAN, KEC. WAJO',
            'KOTA MAKASSAR, SULAWESI SELATAN 90163'
        ];
        $phoneFaxEmail = 'PHONE : (0411) 878658 / Email : mgtupg@gateway-id.com';
    }
    else if ($region=='Semarang') {
        $addrLines = [
            'JL. SEMARANG INDAH NO.D14 / 20A, TAWANGMAS, KEC. SEMARANG BAR.',
            'KOTA SEMARANG, JAWA TENGAH 50144'
        ];
        $phoneFaxEmail = 'PHONE : (62-24) 76632972 / Email : mgtsrg@gateway-id.com';
    }
    else if ($region=='Medan') {
        $addrLines = [
            'MANDIRI BUILDING 7TH FLOOR',
            'JL. IMAM BONJOL NO. 16-D MEDAN 20112'
        ];
        $phoneFaxEmail = 'PHONE : (62-61) 4559876';
    }
    else if ($region=='Bandung') {
        $addrLines = [
            'METRO TRADE CENTRE, JL SOEKARNO HATTA',
            'BLOK D NO.21 SEKEJATI, KEC. BUAH BATU, KOTA BANDUNG 40286'
        ];
        $phoneFaxEmail = 'PHONE : (022) 7502283';
    }

    // =========================
    // HEADER (logo kiri, company+alamat tengah, kartu biru kanan)
    // =========================
    if (file_exists($logo)) {
        $pdf->Image($logo, 10, 12, 45);
    }

    // Company + alamat (tengah)
    $pdf->SetFont('Arial','B',12);
    $pdf->SetXY(60, 12);
    $pdf->Cell(80, 6, $company, 0, 1, 'L');

    $pdf->SetFont('Arial','',6);
    $y = 18;
    foreach ($addrLines as $ln) {
        $pdf->SetXY(60, $y);
        $pdf->MultiCell(88, 3, $ln, 0, 'L');
        $y += 3;
    }
    $pdf->SetXY(60, $y);
    $pdf->MultiCell(88, 3, $phoneFaxEmail, 0, 'L');

    
	// Posisi dan ukuran kartu
	$cardX = 150; 
	$cardY = 10; 
	$cardW = 50; 
	$cardH = 22;

	// Warna navy
	$pdf->SetFillColor(0,32,96);
    $pdf->RoundedRect(150, 10, 50, 18, 5, 'F');


	// Teks putih di dalam kartu
	$pdf->SetTextColor(255,255,255);

	// Judul (jenis dokumen)
	$pdf->SetFont('Arial','B',12);
	$pdf->SetXY($cardX, $cardY+2);
	$pdf->Cell($cardW-2, 5, strtoupper($jenis), 0, 1, 'C');

	// Nomor dokumen
	$pdf->SetFont('Arial','B',10);
	$pdf->SetXY($cardX, $cardY+7.5);
	$pdf->Cell($cardW-2, 5, $no_doc, 0, 1, 'C');

	// Tanggal
	$pdf->SetFont('Arial','',8.5);
	$pdf->SetXY($cardX, $cardY+12);
	$pdf->Cell($cardW-2, 5, $intlDate, 0, 1, 'C');
    $pdf->SetTextColor(0,0,0);

    // =========================
    // BOX BESAR (TO + details) seperti a.pdf
    // =========================
    $outerX=10; $outerY=35; $outerW=190; $outerH=40;
    $pdf->SetDrawColor($NAVY[0],$NAVY[1],$NAVY[2]);
    $pdf->SetLineWidth(0.6);
    $pdf->Rect($outerX, $outerY, $outerW, $outerH);

    $pdf->SetFillColor($LGRAY[0],$LGRAY[1],$LGRAY[2]);
    $pdf->Rect($outerX+1, $outerY+1, $outerW-2, $outerH-2, 'F');

    // TO kiri
    $pdf->SetDrawColor($BORD[0],$BORD[1],$BORD[2]);
    $pdf->SetLineWidth(0.2);
    $pdf->SetFont('Arial','B',8);
    $pdf->SetXY(14, $outerY+2);
    $pdf->Cell(10,4,'TO:',0,1,'L');

    $pdf->SetFont('Arial','B',11);
    $pdf->SetXY(14, $outerY+7);
    $toLines = preg_split("/\r\n|\n|\r/", trim($toName));
    $pdf->Cell(70,5, strtoupper((string)($toLines[0] ?? $toName)),0,1,'L');

    $pdf->SetFont('Arial','',6);
    $yy = $outerY+15;
	$yy = $outerY + 15;
	$cellWidth = 60; // Lebar yang lebih kecil
	$cellHeight = 3.6;

	foreach (array_slice($toLines, 1) as $ln) {
		$ln = trim($ln);
		if ($ln === '') continue;

		$pdf->SetXY(14, $yy); // Set posisi awal
		$pdf->MultiCell($cellWidth, $cellHeight, strtoupper($ln), 0, 'L');

		// Tambah Y manual karena MultiCell pindah ke bawah otomatis
		$yy += $pdf->GetY() - $yy;
	}
    if ($toTaxId) { $pdf->SetXY(14,$yy); $pdf->Cell(70,3.6,'TAX ID: '.$toTaxId,0,1,'L'); $yy+=3.6; }
    if ($toTel)   { $pdf->SetXY(14,$yy); $pdf->Cell(70,3.6,'TEL: '.$toTel,0,1,'L'); $yy+=3.6; }
    if ($toEmail) { $pdf->SetXY(14,$yy); $pdf->Cell(70,3.6,'EMAIL: '.$toEmail,0,1,'L'); }

    // Details box kanan (Service + Job) seperti a.pdf
    $dX=75; $dY=$outerY+13; $dW=120; $dH=23;
    $pdf->SetDrawColor($BORD[0],$BORD[1],$BORD[2]);
    $pdf->Rect($dX, $dY, $dW, $dH);

    // garis pemisah Service vs Job
    $mid = $dX + ($dW/2);
    $pdf->Line($mid, $dY, $mid, $dY+$dH);

    // headings
    $pdf->SetFont('Arial','B',8);
    $pdf->SetXY($dX+3, $dY+2);
    $pdf->Cell(($dW/2)-6, 4, 'Service Details', 0, 0, 'L');
    $pdf->SetXY($mid+3, $dY+2);
    $pdf->Cell(($dW/2)-6, 4, 'Job Details', 0, 1, 'L');

    $pdf->SetFont('Arial','',7.2);
    $sx=$dX+3; $sy=$dY+6.5;
    $pdf->SetXY($sx,$sy);      $pdf->Cell(55,3.7,'VESSEL : '.$vessel,0,1,'L');
    $pdf->SetXY($sx,$sy+3.7);  $pdf->Cell(55,3.7,'ETD / ETA   : '.$etd.' / '.$eta,0,1,'L');
    $pdf->SetXY($sx,$sy+7.4);  $pdf->Cell(55,3.7,'ORIGIN   : '.$origin,0,1,'L');
    $pdf->SetXY($sx,$sy+11.1); $pdf->Cell(55,3.7,'DESTINATION : '.$destination,0,1,'L');
    //$pdf->SetXY($sx,$sy+14.8); $pdf->Cell(55,3.7,'TERM  : '.$term,0,1,'L');

    $jx=$mid+3; $jy=$dY+6.5;
    $pdf->SetXY($jx,$jy);      $pdf->Cell(55,3.7,'MANIFEST NO : '.$manifest,0,1,'L');
    $pdf->SetXY($jx,$jy+3.7);  $pdf->Cell(55,3.7,'CONTAINER NO : '.$container,0,1,'L');
    $pdf->SetXY($jx,$jy+7.4);  $pdf->Cell(55,3.7,'MBL NO : '.$mbl,0,1,'L');
    $pdf->SetXY($jx,$jy+11.1); $pdf->Cell(55,3.7,'HBL NO : '.$hbl,0,1,'L');

    // =========================
    // Data items: split Ocean vs Rebate (REBATE berdasarkan nama item)
    // =========================
    // Ambil header untuk mengecek kode agen
	$finance_hdr = $this->db->get_where('x_finance', ['id' => $id])->row();
	$agent_code  = $finance_hdr ? strtoupper(trim((string)$finance_hdr->kepada_kode)) : '';

    $items = $this->m_finance_proses->tampil_data([
        'table'  => 'x_finance_item',
        'number' => $id
    ]);

    $ocean = [];
    $rebate = [];
    
    foreach ($items->result() as $row) {
        $nm = strtoupper((string)$row->item);
        
        // 1. Tentukan kategori berdasarkan nama ASLI di database
        $is_rebate = (strpos($nm, 'REBATE') !== false);

        // 2. Manipulasi nama item HANYA untuk agen CTLHPH pada DN/CN
        if ($agent_code === 'CTLHPH' && in_array($jenis, ['DEBIT NOTE', 'CREDIT NOTE'])) {
            if ($nm === 'REBATE AGENT VOLUME') {
                $row->item = 'FREIGHT COST SHARING /CBM';
            } elseif ($nm === 'REBATE AGENT SET') {
                $row->item = 'FREIGHT COST SHARING /DOC';
            }
        }

        // 3. Masukkan ke array cetak
        if ($is_rebate) {
            $rebate[] = $row;
        } else {
            $ocean[] = $row;
        }
    }

    // =========================
    // Helper render section + table
    // =========================
   $renderSection = function($title, $rows) use ($pdf, $NAVY, $BORD) {

		if (empty($rows)) return;

		// section title navy + underline
		$pdf->Ln(10);
		$pdf->SetFont('Arial','B',13);
		$pdf->SetTextColor($NAVY[0],$NAVY[1],$NAVY[2]);
		$pdf->Cell(190, 6, strtoupper($title), 0, 1, 'L');
		$yline = $pdf->GetY();
		$pdf->SetDrawColor($NAVY[0],$NAVY[1],$NAVY[2]);
		$pdf->SetLineWidth(0.6);
		$pdf->Line(10, $yline, 200, $yline);
		$pdf->SetTextColor(0,0,0);

		// Penyesuaian lebar kolom (Total tetap 190)
		$w0=10; // Lebar kolom No.
		$w1=80; // Dikurangi dari 90 ke 80 agar total tetap balance
		$w2=25; 
		$w3=35; 
		$w4=40; 

		// header row dark navy
		$pdf->SetFillColor($NAVY[0],$NAVY[1],$NAVY[2]);
		$pdf->SetTextColor(255,255,255);
		$pdf->SetFont('Arial','B',8);

		$pdf->Cell($w0, 7, 'NO.', 0, 0, 'C', true); // Header No.
		$pdf->Cell($w1, 7, 'DETAIL OF CHARGES', 0, 0, 'C', true);
		$pdf->Cell($w2, 7, 'QUANTITY',          0, 0, 'C', true);
		$pdf->Cell($w3, 7, 'UNIT RATE',         0, 0, 'C', true);
		$pdf->Cell($w4, 7, 'AMOUNT',            0, 1, 'C', true);

		// reset
		$pdf->SetTextColor(0,0,0);
		$pdf->SetDrawColor($BORD[0],$BORD[1],$BORD[2]);
		$pdf->SetLineWidth(0.2);
		$pdf->SetFont('Arial','',8.5);

		$sub = [];
		$z = false;
		$i = 1; // Inisialisasi nomor urut

		foreach ($rows as $r) {
			$z = !$z;
			$pdf->SetFillColor($z ? 255 : 250, $z ? 255 : 250, $z ? 255 : 250);

			$desc = strtoupper((string)$r->item);
			if (!empty($r->bl_no)) $desc = $r->bl_no.' - '.$desc;

			$qty  = (isset($r->qty) && $r->qty !== '') ? $r->qty : 0;
			$cur  = (string)$r->currency;

			$unit = $cur.' '.$this->fungsi->pecah($r->unit_price, ',');
			$amt  = $cur.' '.$this->fungsi->pecah($r->price, ',');

			// Render Baris Data
			$pdf->Cell($w0, 7, $i++, 1, 0, 'C', true); // Kolom No.
			$pdf->Cell($w1, 7, $desc, 1, 0, 'L', true);
			$pdf->Cell($w2, 7, $qty,  1, 0, 'C', true);
			$pdf->Cell($w3, 7, $unit, 1, 0, 'R', true);
			$pdf->Cell($w4, 7, $amt,  1, 1, 'R', true);

			if (!isset($sub[$cur])) $sub[$cur] = 0;
			$sub[$cur] += (float)$r->price;
		}

		// subtotal line
		$pdf->Ln(2);
		$pdf->SetDrawColor($NAVY[0],$NAVY[1],$NAVY[2]);
		$pdf->SetLineWidth(0.6);
		$y = $pdf->GetY();
		$pdf->Line(10, $y, 200, $y);

		$pdf->SetLineWidth(0.2);
		$pdf->SetDrawColor(180,180,180);
	};

    // Render 2 section sesuai request
    $renderSection('Freight', $ocean);
    $renderSection('Rebate', $rebate);
	
	// =====================
	// GRAND TOTAL (USD/IDR)
	// =====================
	$ttl_usd = '';
	$ttl_idr = '';

	$jumlah_idr = $this->m_inv_to_shipper->get_ttl_db_note($id,'RP.');
	$jumlah_usd = $this->m_inv_to_shipper->get_ttl_db_note($id,'USD');

	if(!empty($jumlah_usd)) $ttl_usd = $this->fungsi->pecah(array_sum($jumlah_usd),',');
	if(!empty($jumlah_idr)) $ttl_idr = $this->fungsi->pecah(array_sum($jumlah_idr),',');

	// siapkan label
	$showUSD = ($ttl_usd !== '');
	$showIDR = ($ttl_idr !== '');

	// jaga kalau mepet bawah halaman
	$needH = 22; // tinggi area total
	if (($pdf->GetY() + $needH) > ($pdf->GetPageHeight() - 25)) $pdf->AddPage();

	// box total di kanan (lebih professional daripada cell random)
	$pdf->Ln(3);
	$pdf->SetDrawColor(180,180,180);
	$pdf->SetLineWidth(0.2);

	$boxW = 70;
	$boxH = 16;
	$boxX = 200 - 0 - $boxW; // right margin = 10
	$boxY = $pdf->GetY();

	$pdf->SetFillColor(248,248,248);
	$pdf->Rect($boxX, $boxY, $boxW, $boxH, 'DF');

	$pdf->SetFont('Arial','',9);
	$pdf->SetXY($boxX+4, $boxY+2);
	$pdf->Cell($boxW-8, 4, 'GRAND TOTAL', 0, 1, 'L');

	$pdf->SetFont('Arial','B',12);

	$yRow = $boxY + 7;

	if ($showUSD) {
		$pdf->SetXY($boxX+4, $yRow);
		$pdf->Cell(18, 4, 'USD', 0, 0, 'L');
		$pdf->Cell($boxW-22, 4, $ttl_usd, 0, 1, 'R');
		$yRow += 4.5;
	}
	if ($showIDR) {
		$pdf->SetXY($boxX+4, $yRow);
		$pdf->Cell(18, 4, 'IDR', 0, 0, 'L');
		$pdf->Cell($boxW-22, 4, $ttl_idr, 0, 1, 'R');
	}
	$pdf->SetY($boxY + $boxH + 2);
	if ($jenis != "CREDIT NOTE"){
	// =====================
	// PAYMENT DETAILS BOX
	// =====================
	$needH = 42;
	if (($pdf->GetY() + $needH) > ($pdf->GetPageHeight() - 25)) $pdf->AddPage();

	$pdf->Ln(4);
	$pdf->SetFont('Arial','B',10);
	$pdf->SetTextColor(10,52,92); // navy
	$pdf->Cell(190,6,'Payment Details',0,1,'L');
	$pdf->SetTextColor(0,0,0);

	// tentukan rekening berdasarkan currency yang tampil
	if ($ttl_idr != '') {
		$payCurrency = 'IDR';
		$accNo = '2082409727';
	} else {
		$payCurrency = 'USD';
		$accNo = '2082001085';
	}

	$bankName = 'Maybank Indonesia';
	$branch   = 'Cabang Tanjung Priok Enggano';
	$accName  = $company;

	// style tabel
	$pdf->SetDrawColor(180,180,180);
	$pdf->SetLineWidth(0.2);
	$pdf->SetFillColor(248,248,248);

	$tableX = 10;
	$tableW = 190;
	$rowH   = 6;
	$colL   = 45;
	$colR   = $tableW - $colL;

	// outer border
	$startY = $pdf->GetY();
	$rowsCount = 5;
	$pdf->Rect($tableX, $startY, $tableW, $rowH * $rowsCount);

	$pdf->SetFont('Arial','',9);

	// helper row
	$printRow = function($label, $value) use ($pdf, $tableX, $colL, $colR, $rowH) {
		$y = $pdf->GetY();
		$pdf->SetXY($tableX, $y);
		$pdf->Cell($colL, $rowH, $label, 1, 0, 'L', true);
		$pdf->Cell($colR, $rowH, $value, 1, 1, 'L', false);
	};

	$printRow('Currency', $payCurrency);
	$printRow('Bank Name', $bankName);
	$printRow('Branch', $branch);
	$printRow('Account Number', $accNo);
	$printRow('Swift Code', 'IBBKIDJA');
	$printRow('Account Name', $accName);

	$pdf->Ln(2);
	
	// =====================
	// IMPORTANT NOTICE
	// =====================
	$needH = 16;
	if (($pdf->GetY() + $needH) > ($pdf->GetPageHeight() - 20)) $pdf->AddPage();

	$pdf->Ln(2);
	$pdf->SetFont('Arial','I',8);

	// border tipis supaya kelihatan "notice"
	$pdf->SetDrawColor(220,100,100);
	$pdf->SetLineWidth(0.2);
	$nx = 10;
	$ny = $pdf->GetY();
	$nw = 190;

	// tinggi kira-kira, biar aman kita bikin box fixed 14mm
	$nh = 14;
	$pdf->Rect($nx, $ny, $nw, $nh, 'D');

	$pdf->SetXY($nx+3, $ny+2);
	$pdf->SetTextColor(180,0,0);
	$pdf->MultiCell(184, 4,
		'Important Notice: To ensure secure transactions, please note that PT. Gateway Container Line never changes bank details via email. If you have any concerns or questions, kindly verify with our Finance Department before proceeding with your payment.',
	0,'L'
	);
	}
	$pdf->SetTextColor(0,0,0);

	// sedikit jarak setelah notice
	$pdf->Ln(1);
	
	// =====================
	// SIGNATURE AREA (rapi)
	// =====================
	$lineY = 285;          // posisi garis tanda tangan (ubah sesuai selera)
	$textGap = 4;          // jarak teks ke garis (mm)

	$pdf->SetDrawColor(220,220,220);
	$pdf->SetLineWidth(0.3);
	$pdf->Line(10, $lineY-30, 200, $lineY-30); // separator halus di atas blok signature (opsional)

	// Label
	$pdf->SetFont('Arial','',10);
	$pdf->SetXY(10, $lineY-18);
	$pdf->Cell(90, 5, 'Authorized by,', 0, 0, 'L');

	/* // Garis tanda tangan
	$pdf->SetLineWidth(0.6);
	$pdf->SetDrawColor(50,50,50);
	$pdf->Line(10, $lineY, 45, $lineY);
 */
	// Teks "Finance Department" tepat di atas garis
	$pdf->SetFont('Arial','',10);
	$pdf->SetXY(10, $lineY-9);   // 6 = tinggi cell
	$pdf->Cell(70, 6, 'Finance Department', 0, 0, 'L');
	
// =====================
// QR CODE (phpqrcode)
// =====================
require_once("pdf/phpqrcode/qrlib.php");

// pakai URL internal + token (jangan expose API key)
$token  = $this->make_qr_token($no_doc);
$qrText = 'https://gclid.cloud/verify/invoice?number='.rawurlencode($no_doc).'&t='.$token;

// folder temp
$tmpDir = FCPATH.'pdf/tmp_qr/';
if (!is_dir($tmpDir)) @mkdir($tmpDir, 0775, true);

$qrPath = $tmpDir.'dn_'.$id.'_'.time().'.png';

// generate png
QRcode::png($qrText, $qrPath, 'H', 5, 2);

// size & posisi
$qrMm    = 22;
$marginR = 10;
$marginB = 12;

$x = $pdf->GetPageWidth()  - $marginR - $qrMm;
$y = $pdf->GetPageHeight() - $marginB - $qrMm;

// OPTIONAL: kalau takut nabrak area signature, naikin dikit
// misal signature lineY = 285, taruh QR di atasnya
$lineY = 285;
$maxY  = $lineY - $qrMm - 2;
if ($y > $maxY) $y = $maxY;

if (file_exists($qrPath)) {
    $pdf->Image($qrPath, $x, $y, $qrMm, $qrMm);

    $pdf->SetFont('Arial','',6.5);
    $pdf->SetTextColor(100,100,100);
    $pdf->SetXY($x-2, $y - 4);
	$pdf->Cell($qrMm+4, 3, 'Scan to validate', 0, 0, 'C');
    $pdf->SetTextColor(0,0,0);

    @unlink($qrPath);
}

    if (ob_get_length()) ob_end_clean();
$pdf->Output('I','Finance process.pdf');
exit;


}


	function manage_table_satu() 
	{
		$status = $this->m_finance_proses->manage_table_satu($_POST);
 		echo json_encode($status);
	}
	
	function get_nama_alamat()
	{
		$post = array(
			'kepada'  => ($this->input->post('kepada') !== null && $this->input->post('kepada') !== '')
							? $this->input->post('kepada')
							: $this->input->post('cari'),
			'code'    => $this->input->post('code'),
			'number'  => $this->input->post('number'),
			'confirm' => $this->input->post('confirm'),
		);
		$status = $this->m_finance_proses->get_nama_alamat($post);
		echo json_encode($status);
	}

	/**
	 * Generate token HMAC untuk QR validasi dokumen.
	 * Dipakai oleh print_out_new().
	 */
	protected function make_qr_token($docNo)
	{
		$secret = (string)$this->config->item('qr_secret');
		$payload = trim((string)$docNo);

		return hash_hmac('sha256', $payload, $secret);
	}
}


