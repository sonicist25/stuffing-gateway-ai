<?php
class C_proforma_invoice extends CI_Controller{
	
	var $title ='Proforma Invoice';
	
	public function __construct()
	{
		parent::__construct();
		$this->fungsi->restrict();
		$this->load->model(array('finance/m_proforma_invoice','finance/m_inv_to_shipper','afg_m_cities','finance/m_finance_proses','afg_m_job_sheet'));
	}
	
	function get_nomer_invoice() {
		$value = $this->fungsi->get_nomer_invoice($_POST);
		echo json_encode(array('num_inv'=>$value,"jenis"=>$_POST["jenis"]));
	}	
	
	function get_last_inv_no()
	{
		
		$inv_last = $this->fungsi->get_new_num_inv($_POST);
		echo json_encode(array('new_no'=>$inv_last));
	}
	
	function index()
	{
		$this->fungsi->auth(63,'Proforma Invoice');
		$data['title'] = $this->title;
		$data['h1_title'] ='PROFORMA INVOICE';
		$data['table_title'] ='INVOICE';
		$data['menu_navigation'] ='navigation';
		$data['main_view'] ='finance/v_proforma_invoice';
		$this->load->view('afg_template', $data);
	}
	
	function get_table_satu()
	{
	
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'id_proforma_invoice';
	 	$sord = isset($_POST['sord'])?$_POST['sord']:'desc';	 	
	 	$start = $batas*$hal - $batas;
		$start = ($start<0)?0:$start;
		$_POST['start']=$start;	 		
		$q = $this->m_proforma_invoice->get_table_satu($_POST,false);
		$query = $this->m_proforma_invoice->get_table_satu($_POST,true);	 
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
												$row->job_no,
												$row->jenis,
												$row->number,
												$this->fungsi->jin_date_str($row->date),
												$row->group,
												$row->kepada_kode,
												);
		    $i++;
		}
		echo json_encode($data);
	}
	
	function get_table_dua()
	{
		
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:5;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
	 	$sord = isset($_POST['sord'])?$_POST['sord']:'';	 	
	 	$start = $batas*$hal - $batas;
		$start = ($start<0)?0:$start;
		$_POST['start'] = $start;		
		$q = $this->m_proforma_invoice->get_table_dua($_POST,false);
		$query = $this->m_proforma_invoice->get_table_dua($_POST,true);		
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
			
		   $data->rows[$i]['id']=$row->sp_detail_id;
		   $data->rows[$i]['cell']=array(
												$row->code_job, 																		
												$this->fungsi->jin_date_str($row->stuffing_date),
												'',
												$row->destination,
												$row->carrier,
												$row->vessel.' '.$row->voyage,									
												$row->kurs,
												$this->m_proforma_invoice->get_nama_sales($row->sales),
												$this->m_proforma_invoice->get_code_shipper($row->shipper_print),
												$row->shipper_print,
												$this->m_proforma_invoice->get_alamat_shipper($row->shipper_print),
												$row->shipper_import_code,
												$row->shipper_import,
												$row->shipper_import_add,
												$this->m_proforma_invoice->get_blue($row->code_job),
												$row->region_id,
			);
		   $i++;
		}
		echo json_encode($data);
	}
	
	function open_form_invoice()
	{
		$option = array();
		$data['job_no'] = $this->input->post('job_no');
		if($_POST['oper']=='add') 
		{
			$data["shipper_name"]="";
			$data["shipper_address"]="";
			$id = $this->fungsi->get_id_terakhir('SHIPPER','INVOICE');
			
			if($id->num_rows()>0) {
				$data['id'] = $id->row()->id+1;
			}
			else 
			{
				$data['id'] = 0;
			}
			$q = $this->m_proforma_invoice->getListGroup(array(
				'table'=>'inf_income_selling_price',
				'kode_ref'=>$data['job_no'],
			));
			
			if($q) {
				foreach ($q->result() as $row)
				{	
					$option[$row->g]=$row->g;
				}
			}	
					
		} else {
			$data['id']= $_POST['id'];	
			$option = array($_POST['group']=>$_POST['group']);
			$data["shipper_name"]=$this->m_proforma_invoice->get_x_invoice(array($_POST["inv_no"],"shipper_name"));
			$data["shipper_address"]=$this->m_proforma_invoice->get_x_invoice(array($_POST["inv_no"],"shipper_address"));
		} 
		$data['option']=$option;
		$data['proses']= $this->m_proforma_invoice->get_proses($_POST);
		$data['destination']= $this->get_destination($this->input->post('job_no'));
		$data['si_exportir']= $this->si_exportir($this->input->post('job_no'));
		
		$this->load->view('finance/v_proforma_invoice_editor', $data);
	}

	
	function si_exportir($job)
	{
		$region = $this->session->userdata('region_id');
		$this->dbx->join('trnstuffingplan','trnstuffingplan.sp_id=trnstuffingplandetail.sp_id');
		$this->dbx->where('trnstuffingplan.region_id',$region);
		$this->dbx->where('trnstuffingplandetail.kode_ref',$job);
		$id = $this->dbx->get('trnstuffingplandetail')->row()->code;
		$q = $this->dbx->get_where('trnstuffingplan',array('kode_ref'=>$id));
		if($q->num_rows()>0)
		{
			return $q->row()->cont_seal_no;
		}
		else
		{
			return '';
		}
	}
	
	function get_destination($kode_ref)
	{
		$data = $this->m_proforma_invoice->get_destination($kode_ref);
		return $data;
	}
	
	function get_table_tiga()
	{
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
	 	$sord = isset($_POST['sord'])?$_POST['sord']:'';
	 	
	 	$start = $batas*$hal - $batas;
		$start = ($start<0)?0:$start;
		$_POST['start'] = $start;
	 	
	 	if(!$sidx) $sidx = 1;
		$q = $this->m_proforma_invoice->get_table_tiga($_POST,false);
		$query = $this->m_proforma_invoice->get_table_tiga($_POST,true);
		
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
												$row->number,
												strtoupper($row->item),
												$row->qty,
												$row->currency,
												$row->price,
												$row->remarks,
												$row->pre_col
												);
		    $i++;
		}
		echo json_encode($data);
	}
	
	function manage_data()
	{
 		$status = $this->m_proforma_invoice->manage_data($_POST);
 		echo json_encode(array('status'=>$status,'oper'=>$_POST['oper']));
	}
	function print_proforma($kode_ref,$id)
	{
		$this->session->set_flashdata($_POST);
		header('HTTP/1.0 200 OK');
		header('HTTP/1.1 200 OK');
		header('Content-Type: application/pdf, UTF-8');
  		header('Content-Disposition: inline; filename="'.$this->title.'";');
  		header('Last-Modified: '.gmdate('D, d M Y H:i:s', time()).' GMT');
		$this->load->library('FPDF_MC_Table');
		$pdf=new FPDF_MC_Table();
		$pdf->AddPage('P','invoice');
		
		
		$region = $this->session->userdata('region_id');
		if ($region=='Jakarta'){
		$pdf->Image(APPPATH.'third_party/img/logo.png',150,8,+50);
		$pdf->SetFont('verdana','',12);
		$pdf->SetXY(10, 10);
		$pdf->write(5,'PT. GATEWAY CONTAINER LINE');
		$pdf->SetFont('verdana','',7);
		$pdf->SetXY(10, 17);
		$pdf->write(0,'JL. OTISTA III RT. 001 RW. 001 NO. 18');
		$pdf->SetXY(10, 20);
		$pdf->write(0,'CIPINANG CEMPEDAK JATINEGARA');
		$pdf->SetXY(10, 23);
		$pdf->write(0,'JAKARTA TIMUR 13340');
		$pdf->SetXY(10, 26);
		$pdf->write(0,'PHONE : ( 62-21 ) 8591-8669 ( HUNTING )');
		$pdf->SetXY(10, 29);
		$pdf->write(0,'FAX : ( 62-21 ) 8591-8664');
		$pdf->SetFont('verdana','',8);
		$pdf->SetXY(10, 32);
		$pdf->write(0,'general@gateway-id.com');
		} else if ($region=='Surabaya')
		{
			$pdf->Image(APPPATH.'third_party/img/logo.png',150,8,+50);
		$pdf->SetFont('arial','',12);
		$pdf->SetXY(10, 10);
		$pdf->write(5,'PT. GATEWAY CONTAINER LINE');
		$pdf->SetFont('arial','',7);
		$pdf->SetXY(10, 17);
		$pdf->write(0,'JL GAYUNGSARI BARAT NO 91B');
		$pdf->SetXY(10, 20);
		$pdf->write(0,'GAYUNGAN, KEC. GAYUNGAN');
		$pdf->SetXY(10, 23);
		$pdf->write(0,'SURABAYA 60235, JAWA TIMUR ');
		$pdf->SetXY(10, 26);
		$pdf->write(0,'PHONE : 031 82522370 / 031 82522405/031 82522827-830 ');
		$pdf->SetXY(10, 29);
		$pdf->write(0,'FAX : ( 031 ) 3765620');
		$pdf->SetFont('arial','',8);
		$pdf->SetXY(10, 32);
		$pdf->write(0,'mgtsby@gateway-id.com');
		} else if ($region=='Cikarang')
		{
			$pdf->Image(APPPATH.'third_party/img/logo.png',150,8,+50);
		$pdf->SetFont('arial','',12);
		$pdf->SetXY(130, 10);
		$pdf->write(5,'PT. GATEWAY CONTAINER LINE');
		$pdf->SetFont('arial','',7);
		$pdf->SetXY(172, 17);
		$pdf->write(0,'JL. INDUSTRI PASIR');
		$pdf->SetXY(173, 20);
		$pdf->write(0,'GOMBONG NO.114');
		$pdf->SetXY(173.5, 23);
		$pdf->write(0,'CIKARANG UTARA ');
		$pdf->SetXY(166, 26);
		$pdf->write(0,'PHONE :(021)89100449');
		$pdf->SetXY(170, 29);
		$pdf->write(0,'FAX :(021)89100449');
		$pdf->SetFont('arial','',8);
		$pdf->SetXY(161, 32);
		$pdf->write(0,'lisbeth@gateway-id.com');
		}
		 else if ($region=='BPLINE')
		{
		$pdf->Image(APPPATH.'third_party/img/bpline.png',10,8,+50);
		$pdf->SetFont('arial','',12);
		$pdf->SetXY(135, 10);
		$pdf->write(5,'PT. BANGUN PUTRA PESAKA');
		$pdf->SetFont('arial','',7);
		$pdf->SetXY(164, 17);
		$pdf->write(0,'JL. TIPAR CAKUNG NO 52');
		$pdf->SetXY(167, 20);
		$pdf->write(0,'SUKAPURA, CILINCING');
		$pdf->SetXY(167, 23);
		$pdf->write(0,'JAKARTA UTARA 14140');
		$pdf->SetXY(167.5, 26);
		$pdf->write(0,'PHONE (021) 44833450');
		}
		$no_invoice = $this->session->flashdata('no_invoice');
		$numb = substr($no_invoice,0,3);
		$invoice_date = $this->session->flashdata('invoice_date');
		$currency = $this->session->flashdata('currency');
		$jenis_inv = $this->session->flashdata('jenis');
		$kepada = $this->m_proforma_invoice->get_x_invoice(array($no_invoice,"shipper_name")). htmlentities("\n"). $this->m_proforma_invoice->get_x_invoice(array($no_invoice,"shipper_address"));
		$kepada_pajak = $this->session->flashdata('kepada_pajak');
		$query = $this->m_inv_to_shipper->get_report_pdf($kode_ref);	
		$data =  $this->m_inv_to_shipper->get_form_inv($id);  
		$pdf->SetFont('arial','',10);
		
		$pdf->SetFont('arial','BU',16);
		$pdf->SetXY(75, 44);
		$pdf->write(0,'PROFORMA INVOICE');
		$pdf->SetFont('arial','B',12);
		$pdf->SetXY(83, 49);
		$pdf->write(0,$kode_ref);
		$pdf->Line(10,53,200,53);		
		//$pdf->Line(120,100,120,53);
		$jenis = substr($kode_ref,4,8);
		$pdf->SetFont('arial','',8);
		$pdf->SetXY(15, 56);
		$pdf->write(0,'BILL TO :');
		$pdf->SetXY(15, 60);
		if($jenis_inv=='3'){
		$pdf->MultiCell(100,3,$kepada_pajak);
		}else{
		$pdf->MultiCell(100,3,$kepada);
		}
		$pdf->SetXY(123, 60);
		$pdf->write(0,'CONTAINER/SEAL');
		$pdf->SetXY(149, 60);
		$pdf->write(0,':');		
		$pdf->SetXY(151, 60);
		$pdf->write(0,$query['cont_seal_no']);
		
		$pdf->SetXY(123, 64);
		$pdf->write(0,'PACKAGING');
		$pdf->SetXY(149, 64);
		$pdf->write(0,':');
		$pdf->SetXY(151, 64);
		$pdf->write(0,$query['no_pack_qty'].' X'.$query['no_pack_unit']);
		
		$pdf->SetXY(123, 68);
		$pdf->write(0,'GROSS WEIGHT');
		$pdf->SetXY(149, 68);
		$pdf->write(0,':');
		$pdf->SetXY(151, 68);
		$pdf->write(0,$query['weight'].' '.'KGS');

		$pdf->SetXY(123, 72);
		$pdf->write(0,'MEASSUREMENT');
		$pdf->SetXY(149, 72);
		$pdf->write(0,':');
		$pdf->SetXY(151, 72);
		$pdf->write(0,$query['bl_meas'].' '.'M3');
		
		$pdf->Line(10,79,200,79);
		
		$pdf->SetXY(123, 83);
		$pdf->write(0,'VESSEL');
		$pdf->SetXY(138, 83);
		$pdf->write(0,':'); 
		$pdf->SetXY(140, 83);
		
		$detail = $this->afg_m_job_sheet->get_detail_job($query['sp_detail_id']);
		
		////print_r($detail);
		
		if($detail->last_vessel != '') 
			{
				$vessel = $detail->last_vessel;
				$vessel_etd = $detail->last_vessel_etd;
				$vessel_eta = $detail->last_vessel_eta;
				$vessel_etd_date = $detail->last_vessel_etd_date;
				$vessel_eta_date = $detail->last_vessel_eta_date;
			}
			else 
			{
				$vessel = $detail->feeder_vessel;
				$vessel_etd = $detail->feeder_vessel_etd;
				$vessel_eta = $detail->feeder_vessel_eta;
				$vessel_etd_date = $detail->feeder_vessel_etd_date;
				$vessel_eta_date = $detail->feeder_vessel_eta_date;
			}
		
		
		$pdf->write(0,$vessel);
		
		
		//$pdf->write(0,$query['vessel'].' '.$query['voyage']);

		$pdf->SetXY(123, 87);
		$pdf->write(0,'ETD');
		$pdf->SetXY(138, 87);
		$pdf->write(0,':');
		$pdf->SetXY(140, 87);
		$etd_jkt = $query['etd_jkt'];
		$pdf->write(0,substr($etd_jkt,6,2).'-'. substr($etd_jkt,4,2).'-'. substr($etd_jkt,0,4));
		
		$pdf->SetXY(123, 91);
		$pdf->write(0,'POL/POD');
		$pdf->SetXY(138, 91);
		$pdf->write(0,':');
		$pdf->SetXY(140, 91);
		$pdf->write(0,$query['port_loading'].' / '.$query['destination']);
		
		$pdf->SetXY(123, 95);
		$pdf->write(0,'B/L NO');
		$pdf->SetXY(138, 95);
		$pdf->write(0,':');
		$pdf->SetXY(140, 95);
		$region = $this->session->userdata('region_id');
		$destination = $this->afg_m_cities->get_city_id($query['destination']);

		$pdf->write(0,$query['hb_l']);
		if ($jenis =='300' || $jenis =='301' || $jenis =='302' || $jenis =='400' || $jenis =='600'){
		$pdf->SetXY(123, 99);
		$pdf->write(0,'MBL NO');
		$pdf->SetXY(138, 99);
		$pdf->write(0,':');
		$pdf->SetXY(140, 99);
		$pdf->write(0,$query['mother_bl']);
		}else{
		$pdf->SetXY(123, 99);
		$pdf->write(0,'SI NO');
		$pdf->SetXY(138, 99);
		$pdf->write(0,':');
		$pdf->SetXY(140, 99);
		$pdf->write(0,$this->session->flashdata('si_shipper'));	
		}
		$pdf->Line(10,103,200,103);
		
		$pdf->SetXY(15, 82);
		$pdf->write(0,'SHIPPER');
		$pdf->SetXY(35, 82);
		$pdf->write(0,':');
		if ($jenis =='300' || $jenis =='301' || $jenis =='302' || $jenis =='400' || $jenis =='600'){
		$pdf->SetXY(15, 86);
		$pdf->write(0,$query['consignee']);
		$pdf->SetXY(15, 88);
		$pdf->MultiCell(100,3,$query['consignee_add']);
		}else{
		$pdf->SetXY(15, 86);
		$pdf->MultiCell(100,3,$query['note']);
		}
		$pdf->SetXY(39, 107);
		$pdf->write(0,'DESCRIPTION');
		$pdf->SetXY(95, 107);
		$pdf->write(0,'QUANTITY');
		$pdf->SetXY(118, 107);
		$pdf->write(0,'CUR');
		$pdf->SetXY(136, 107);
		$pdf->write(0,'UNIT RATE');
		$pdf->SetXY(174, 107);
		$pdf->write(0,'TOTAL');
		$pdf->Ln(3);
		
		$pdf->Ln();
		
		/* $pdf->Line(10,110,200,110);
		$pdf->Line(90,103,90,230);
		$pdf->Line(115,103,115,230);
		$pdf->Line(161,103,161,230);//Garis Ammount
		$pdf->Line(10,230,200,230);
		$pdf->Line(130,103,130,230);
		$pdf->Line(151,240,151,230);//GARIS HORISONTAL TOTAL
		$pdf->Line(200,240,151,240);
		//$pdf->Line(200,280,135,280);
		$pdf->Line(10,53,10,230);
		$pdf->Line(200,53,200,240);
		 */

		$j=1;
		if($data) {
			foreach ($data->result_array() as $row){
				$pdf->Cell(80, 6,'     '.strtoupper($row['item']),0,0,'L');
				$pdf->Cell(25, 6,$row['qty'],0,0,'C');
				$pdf->Cell(14, 6,$row['currency'],0,0,'C');
				$pdf->Cell(30, 6,$this->fungsi->pecah($row['unit_price'],','),0,0,'R');
				$pdf->Cell(36, 6,$this->fungsi->pecah($row['price'],','),0,0,'R');
				$pdf->Ln();
				$j++;
				if ($j>19)
				{ $pdf->AddPage();
			   $j=1;
				}
			}
		} 
		
		$jumlah_idr = $this->m_inv_to_shipper->get_ttl_inv($id,'RP.');
		$jumlah_usd = $this->m_inv_to_shipper->get_ttl_inv($id,'USD');
		
		$ttl_usd = '';
		$ttl_idr = '';
		
		if(!empty($jumlah_usd)) 
		$ttl_usd=$this->fungsi->pecah(array_sum($jumlah_usd),',');
		
		if(!empty($jumlah_idr)) 
		$ttl_idr=$this->fungsi->pecah(array_sum($jumlah_idr),',');
		
		if($ttl_usd!='') {
			$pdf->SetXY(153, 231);
			$pdf->MultiCell(10,4,'USD : ',0,'L',false);
			$pdf->SetXY(160, 231);
			$pdf->MultiCell(38,4, $ttl_usd,0,'R',false);
		}
		if($ttl_idr!='') {
			$pdf->SetXY(153, 235);
			$pdf->MultiCell(10,4,'IDR : ',0,'L',false);
			$pdf->SetXY(160, 235);
			$pdf->MultiCell(38,4,$ttl_idr,0,'R',false);
		}
		
		
		$pdf->SetXY(10, 235);
		$pdf->SetFont('arial','I',8);
		$pdf->write(0,'* Ini adalah tagihan proforma/atau sementara, mohon tidak memotong PPH 23 berdasarkan proforma ini');
		$pdf->SetXY(10, 238);
		$pdf->write(0,'* Mohon melakukan pengecekan di bagian nama dan alamat, karena akan dijadikan acuan untuk faktur pajak');
		
		$pdf->Ln(6);
		$pdf->SetFont('arial','B',8);
		$pdf->Cell(10, 4,'All DO payment must be made through bank transfer to bank account as follow:',0,0,'L',false);
		$pdf->Ln(4);
		if ($region=='Jakarta'){
		$pdf->Cell(10, 4,'',0,0,'L',false);
		$pdf->Cell(180, 4,'PT. GATEWAY CONTAINER LINE',0,1,'L',false);				
		$pdf->Cell(10, 4,'',0,0,'L',false);
		$pdf->Cell(180, 4,'MAYBANK INDONESIA CAB. TG. PRIOK ENGGANO',0,1,'L',false);
		$pdf->Cell(10, 4,'',0,0,'L',false);
		$pdf->Cell(180, 4,'A/C : 2082409727',0,1,'L',false);
		} else if($region=='BPLINE'){
		$pdf->Cell(10, 4,'',0,0,'L',false);
		$pdf->Cell(180, 4,'PT. BANGUN PUTRA PESAKA',0,1,'L',false);				
		$pdf->Cell(10, 4,'',0,0,'L',false);
		$pdf->Cell(180, 4,'BNI 46',0,1,'L',false);
		$pdf->Cell(10, 4,'',0,0,'L',false);
		$pdf->Cell(180, 4,'ACCOUNT NUMBER : 1555566771',0,1,'L',false);
		}
			
		$pdf->Ln(2);
		
		$pdf->Output($no_invoice,'I'); 
	}
	
function print_invoice($kode_ref,$id)
	{
		$this->session->set_flashdata($_POST);
		header('HTTP/1.0 200 OK');
		header('HTTP/1.1 200 OK');
		header('Content-Type: application/pdf, UTF-8');
  		header('Content-Disposition: inline; filename="'.$this->title.'";');
  		header('Last-Modified: '.gmdate('D, d M Y H:i:s', time()).' GMT');
		$this->load->library('FPDF_MC_Table');
		$pdf=new FPDF_MC_Table();
		$pdf->AliasNbPages();
		$pdf->setDataHeader(array('report'=>'proforma_invoice'));
		$pdf->AddPage('P','invoice');
		$pdf->SetAutoPageBreak(true,10);
		
		
		$region = $this->session->userdata('region_id');
		if ($region=='Jakarta'){
		$pdf->Image(APPPATH.'third_party/img/logo.png',150,8,+50);
		$pdf->SetFont('verdana','',12);
		$pdf->SetXY(10, 10);
		$pdf->write(5,'PT. GATEWAY CONTAINER LINE');
		$pdf->SetFont('verdana','',7);
		$pdf->SetXY(10, 17);
		$pdf->write(0,'JL. OTISTA III RT. 001 RW. 001 NO. 18');
		$pdf->SetXY(10, 20);
		$pdf->write(0,'CIPINANG CEMPEDAK JATINEGARA');
		$pdf->SetXY(10, 23);
		$pdf->write(0,'JAKARTA TIMUR 13340');
		$pdf->SetXY(10, 26);
		$pdf->write(0,'PHONE : ( 62-21 ) 8591-8669 ( HUNTING )');
		$pdf->SetXY(10, 29);
		$pdf->write(0,'FAX : ( 62-21 ) 8591-8664');
		$pdf->SetFont('verdana','',8);
		$pdf->SetXY(10, 32);
		$pdf->write(0,'general@gateway-id.com');
		} else if ($region=='Surabaya')
		{
			$pdf->Image(APPPATH.'third_party/img/logo.png',150,8,+50);
		$pdf->SetFont('arial','',12);
		$pdf->SetXY(130, 10);
		$pdf->write(5,'PT. GATEWAY CONTAINER LINE');
		$pdf->SetFont('arial','',7);
		$pdf->SetXY(170, 17);
		$pdf->write(0,'JL. DONOREJO NO. 54 ');
		$pdf->SetXY(182.5, 20);
		$pdf->write(0,'SIMOKERTO');
		$pdf->SetXY(175.5, 23);
		$pdf->write(0,'SURABAYA 60141 ');
		$pdf->SetXY(153, 26);
		$pdf->write(0,'PHONE :  (031)3765610 / 3765624 ');
		$pdf->SetXY(169, 29);
		$pdf->write(0,'FAX : ( 031 ) 3765620');
		$pdf->SetFont('arial','',8);
		$pdf->SetXY(161, 32);
		$pdf->write(0,'mgtsby@gateway-id.com');
		} else if ($region=='Cikarang')
		{
			$pdf->Image(APPPATH.'third_party/img/logo.png',150,8,+50);
		$pdf->SetFont('arial','',12);
		$pdf->SetXY(130, 10);
		$pdf->write(5,'PT. GATEWAY CONTAINER LINE');
		$pdf->SetFont('arial','',7);
		$pdf->SetXY(172, 17);
		$pdf->write(0,'JL. INDUSTRI PASIR');
		$pdf->SetXY(173, 20);
		$pdf->write(0,'GOMBONG NO.114');
		$pdf->SetXY(173.5, 23);
		$pdf->write(0,'CIKARANG UTARA ');
		$pdf->SetXY(166, 26);
		$pdf->write(0,'PHONE :(021)89100449');
		$pdf->SetXY(170, 29);
		$pdf->write(0,'FAX :(021)89100449');
		$pdf->SetFont('arial','',8);
		$pdf->SetXY(161, 32);
		$pdf->write(0,'lisbeth@gateway-id.com');
		}
		 else if ($region=='BPLINE')
		{
		$pdf->Image(APPPATH.'third_party/img/bpline.png',150,8,+50);
		$pdf->SetFont('arial','',12);
		$pdf->SetXY(130, 10);
		$pdf->write(5,'PT. BANGUN PUTRA PESAKA');
		$pdf->SetFont('arial','',7);
		$pdf->SetXY(172, 17);
		$pdf->write(0,'JL. TIPAR CAKUNG NO 52');
		$pdf->SetXY(173, 20);
		$pdf->write(0,'SUKAPURA, CILINCING');
		$pdf->SetXY(173.5, 23);
		$pdf->write(0,'JAKARTA UTARA 14140');
		$pdf->SetXY(166, 26);
		$pdf->write(0,'PHONE (021) 44833450');
		}
		$no_invoice = $this->session->flashdata('no_invoice');
		$numb = substr($no_invoice,0,3);
		$invoice_date = $this->session->flashdata('invoice_date');
		$currency = $this->session->flashdata('currency');
		
		$kepada = $this->m_proforma_invoice->get_x_invoice(array($no_invoice,"shipper_name")). htmlentities("\n"). $this->m_proforma_invoice->get_x_invoice(array($no_invoice,"shipper_address"));
		
		$query = $this->m_proforma_invoice->get_report_pdf($kode_ref);
		
		$data =  $this->m_proforma_invoice->get_form_inv($this->input->get('id'));
//print_r($this->dbx->last_query());		
		$pdf->SetFont('arial','',10);
		
		$pdf->SetFont('arial','BU',16);
		$pdf->SetXY(75, 44);
		$pdf->write(0,'PROFORMA INVOICE');
		$pdf->SetFont('arial','B',12);
		$pdf->SetXY(83, 49);
		$pdf->write(0,$kode_ref);
		//$pdf->Line(10,53,200,53);		
		//$pdf->Line(120,100,120,53);
		
		$pdf->SetFont('arial','',8);
		$pdf->SetXY(15, 56);
		$pdf->write(0,'BILL TO :');
		$pdf->SetXY(15, 60);
		//$pdf->MultiCell(510,3,$this->session->flashdata('kepada'));
		$pdf->MultiCell(100,3,$kepada);
		
		
		$pdf->SetXY(123, 60);
		$pdf->write(0,'CONTAINER/SEAL');
		$pdf->SetXY(149, 60);
		$pdf->write(0,':');		
		$pdf->SetXY(151, 60);
		$pdf->write(0,$this->session->flashdata('si_shipper'));
		
		$pdf->SetXY(123, 64);
		$pdf->write(0,'PACKAGING');
		$pdf->SetXY(149, 64);
		$pdf->write(0,':');
		$pdf->SetXY(151, 64);
		$pdf->write(0,$query['no_pack_qty'].' X'.$query['no_pack_unit']);
		
		$pdf->SetXY(123, 68);
		$pdf->write(0,'GROSS WEIGHT');
		$pdf->SetXY(149, 68);
		$pdf->write(0,':');
		$pdf->SetXY(151, 68);
		$pdf->write(0,$query['weight'].' '.'KGS');

		$pdf->SetXY(123, 72);
		$pdf->write(0,'MEASSUREMENT');
		$pdf->SetXY(149, 72);
		$pdf->write(0,':');
		$pdf->SetXY(151, 72);
		$pdf->write(0,$query['bl_meas'].' '.'M3');
		
		//$pdf->Line(10,79,200,79);
		
		$pdf->SetXY(123, 83);
		$pdf->write(0,'VESSEL');
		$pdf->SetXY(138, 83);
		$pdf->write(0,':'); 
		$pdf->SetXY(140, 83);
		
		$detail = $this->afg_m_job_sheet->get_detail_job($query['sp_detail_id']);
		
		////print_r($detail);
		
		if($detail->last_vessel != '') 
			{
				$vessel = $detail->last_vessel;
				$vessel_etd = $detail->last_vessel_etd;
				$vessel_eta = $detail->last_vessel_eta;
				$vessel_etd_date = $detail->last_vessel_etd_date;
				$vessel_eta_date = $detail->last_vessel_eta_date;
			}
			else 
			{
				$vessel = $detail->feeder_vessel;
				$vessel_etd = $detail->feeder_vessel_etd;
				$vessel_eta = $detail->feeder_vessel_eta;
				$vessel_etd_date = $detail->feeder_vessel_etd_date;
				$vessel_eta_date = $detail->feeder_vessel_eta_date;
			}
		
		
		$pdf->write(0,$vessel);
		
		
		//$pdf->write(0,$query['vessel'].' '.$query['voyage']);

		$pdf->SetXY(123, 87);
		$pdf->write(0,'ETD');
		$pdf->SetXY(138, 87);
		$pdf->write(0,':');
		$pdf->SetXY(140, 87);
		$etd_jkt = $query['etd_jkt'];
		$pdf->write(0,substr($etd_jkt,6,2).'-'. substr($etd_jkt,4,2).'-'. substr($etd_jkt,0,4));
		
		$pdf->SetXY(123, 91);
		$pdf->write(0,'POL/POD');
		$pdf->SetXY(138, 91);
		$pdf->write(0,':');
		$pdf->SetXY(140, 91);
		$pdf->write(0,$query['port_loading'].' / '.$query['destination']);
		
		$pdf->SetXY(123, 95);
		$pdf->write(0,'B/L NO');
		$pdf->SetXY(138, 95);
		$pdf->write(0,':');
		$pdf->SetXY(140, 95);
		$region = $this->session->userdata('region_id');
		$destination = $this->afg_m_cities->get_city_id($query['destination']);

		$pdf->write(0,$query['hb_l']);

		$pdf->SetXY(123, 99);
		$pdf->write(0,'MBL NO');
		$pdf->SetXY(138, 99);
		$pdf->write(0,':');
		$pdf->SetXY(140, 99);
		$pdf->write(0,$query['mother_bl']);
		
		//$pdf->Line(10,103,200,103);
		
		$pdf->SetXY(15, 82);
		$pdf->write(0,'SHIPPER');
		$pdf->SetXY(35, 82);
		$pdf->write(0,':');
		$pdf->SetXY(15, 86);
		$pdf->write(0,$query['consignee']);
		$pdf->SetXY(15, 88);
		$pdf->MultiCell(100,3,$query['consignee_add']);
		
		$pdf->SetXY(39, 107);
		$pdf->write(0,'DESCRIPTION\O');
		$pdf->SetXY(95, 107);
		$pdf->write(0,'QUANTITY');
		$pdf->SetXY(118, 107);
		$pdf->write(0,'CUR');
		$pdf->SetXY(136, 107);
		$pdf->write(0,'UNIT RATE');
		$pdf->SetXY(174, 107);
		$pdf->write(0,'TOTAL');
		$pdf->Ln(3);
		
		$pdf->Ln();
		/* 
		$pdf->Line(10,110,200,110);
		$pdf->Line(90,103,90,230);
		$pdf->Line(115,103,115,230);
		$pdf->Line(161,103,161,230);//Garis Ammount
		$pdf->Line(10,230,200,230);
		$pdf->Line(130,103,130,230);
		$pdf->Line(151,240,151,230);//GARIS HORISONTAL TOTAL
		$pdf->Line(200,240,151,240);
		//$pdf->Line(200,280,135,280);
		$pdf->Line(10,53,10,230);
		$pdf->Line(200,53,200,240); */
		$pdf->Cell(80, 6,'kok kosong     ',0,0,'L');
		$j=1;
		//print_r($data->result_array());
		if($data) {
			foreach ($data->result_array() as $row){
				//print_r($row['item']);
				$pdf->Cell(80, 6,'kok kosong     ',0,0,'L');
				$pdf->Cell(25, 6,$row['qty'],0,0,'C');
				$pdf->Cell(14, 6,$row['currency'],0,0,'C');
				$pdf->Cell(30, 6,$this->fungsi->pecah($row['unit_price'],','),0,0,'R');
				$pdf->Cell(36, 6,$this->fungsi->pecah($row['price'],','),0,0,'R');
				$pdf->Ln();
				$j++;
				if ($j>19)
				{ $pdf->AddPage();
			   $j=1;
				}
			}
		} 
		$jumlah_idr = $this->m_proforma_invoice->get_ttl_inv($id,'RP.');
		$jumlah_usd = $this->m_proforma_invoice->get_ttl_inv($id,'USD');
		
		$ttl_usd = '';
		$ttl_idr = '';
		
		if(!empty($jumlah_usd)) 
		$ttl_usd=$this->fungsi->pecah(array_sum($jumlah_usd),',');
		
		if(!empty($jumlah_idr)) 
		$ttl_idr=$this->fungsi->pecah(array_sum($jumlah_idr),',');
		
		

		
		if($ttl_usd!='') {
			$pdf->SetXY(153, 231);
			$pdf->MultiCell(10,4,'USD : ',0,'L',false);
			$pdf->SetXY(160, 231);
			$pdf->MultiCell(38,4, $ttl_usd,0,'R',false);
		}
		if($ttl_idr!='') {
			$pdf->SetXY(153, 235);
			$pdf->MultiCell(10,4,'IDR : ',0,'L',false);
			$pdf->SetXY(160, 235);
			$pdf->MultiCell(38,4,$ttl_idr,0,'R',false);
		}
		
		
		$pdf->SetXY(10, 235);
		$pdf->SetFont('arial','I',8);
		$pdf->write(0,'* Ini adalah tagihan proforma/atau sementara, mohon tidak memotong PPH 23 berdasarkan proforma ini');
		$pdf->SetXY(10, 238);
		$pdf->write(0,'* Mohon melakukan pengecekan di bagian nama dan alamat, karena akan dijadikan acuan untuk faktur pajak');
		
		$pdf->Ln(6);
		$pdf->SetFont('arial','B',8);
		$pdf->Cell(10, 4,'All DO payment must be made through bank transfer to bank account as follow:',0,0,'L',false);
		$pdf->Ln(4);
		$pdf->Cell(10, 4,'',0,0,'L',false);
		$pdf->Cell(180, 4,'PT. GATEWAY CONTAINER LINE',0,1,'L',false);				
		$pdf->Cell(10, 4,'',0,0,'L',false);
		$pdf->Cell(180, 4,'MAYBANK INDONESIA CAB. TG. PRIOK ENGGANO',0,1,'L',false);
		$pdf->Cell(10, 4,'',0,0,'L',false);
		$pdf->Cell(180, 4,'A/C : 2082409727',0,1,'L',false);
		
		$pdf->Ln(2);
		
		$pdf->Output($no_invoice,'I'); 
	}

	function tarik_data_invoice()
	{
		$data = $this->m_proforma_invoice->tarik_data_invoice($_POST);
		echo json_encode($data);
	}
	
	function get_id_akhir()
	{
		$data = $this->m_proforma_invoice->get_id_akhir();
		return $data;
	}

	function manage_data_table()
	{
		$status = $this->m_proforma_invoice->manage_data_table($_POST);
 		echo json_encode(array('status'=>$status,'oper'=>$_POST['oper']));
	}
}