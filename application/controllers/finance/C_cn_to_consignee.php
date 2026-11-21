<?php
class C_cn_to_consignee extends CI_Controller{
	
	var $title ='Credite Note To Consignee';
	
	public function __construct(){
		parent::__construct();
		$this->fungsi->restrict();
		$this->load->model('finance/m_cn_to_consignee');
		$this->fungsi->dbx = $this->m_cn_to_consignee->dbx;
		$this->dbx = $this->m_cn_to_consignee->dbx;
	}
	
	function index(){
		$this->fungsi->auth(43,'Credite Note To Consignee');
		$data['title'] = $this->title;
		$data['h1_title'] ='INVOICE TO CONSIGNEE';
		$data['table_title'] ='INVOICE';
		$data['menu_navigation'] ='navigation';
		$data['main_view'] ='finance/v_cn_to_consignee';
		$data['kepada'] ='CONSIGNEE';
		$this->load->view('afg_template', $data);
	}
	
	function update_price()
	{
		$data = $this->m_cn_to_consignee->update_price($_POST);
		echo json_encode($data);
	}
	
	function tarik_data_detail() {
		$value = $this->m_cn_to_consignee->tarik_data_detail($_POST);
		echo json_encode($value);
	}
	
	function saving_cn()
	{
		$data = $this->m_cn_to_consignee->saving_cn($_POST);
		echo json_encode($data);
	}
	
	function manage_data_detail() {
		$value = $this->m_cn_to_consignee->manage_data_detail($_POST);
		echo json_encode($value);
	}
	
	function get_first_id($kepada,$jenis) {
		$region = $this->session->userdata('region_id');
		$this->dbx->select(array('id','region_id'));
		$this->dbx->order_by('id','asc');
		$this->dbx->limit(1);
		$query = $this->dbx->get_where('x_finance',array('region_id'=>$region,'kepada'=>$kepada,'jenis'=>$jenis));
		if ( $query->num_rows() > 0)
		{
			return $query->row()->id;
		}
		else
		{
			return "";		
		}
	}
	
	function get_last_id($kepada,$jenis) {
		$region = $this->session->userdata('region_id');
		$this->dbx->select(array('id','region_id'));
		$this->dbx->order_by('id','desc');
		$this->dbx->limit(1);
		$query = $this->dbx->get_where('x_finance',array('region_id'=>$region,'kepada'=>$kepada,'jenis'=>$jenis));
		if ( $query->num_rows() > 0)
		{
			return $query->row()->id;
		}
		else
		{
			return "";		
		}
	}
	
	function get_table_satu(){
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
	 	$sord = isset($_POST['sord'])?$_POST['sord']:'';
	 	
		$start = $batas*$hal - $batas;
		$start = ($start<0)?0:$start;
		$_POST['start']=$start;
	 	
	 	$q = $this->m_cn_to_consignee->get_table_satu($_POST,$limit=false);				
		$query = $this->m_cn_to_consignee->get_table_satu($_POST,$limit=true);
		
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
												$this->fungsi->jin_date_str($row->date),
												$row->job_no,
												$row->kepada_kode,
												$this->m_cn_to_consignee->get_data_consignee_by_code($row->kepada_kode)->consignee_import_name,
												$this->m_cn_to_consignee->get_data_consignee_by_code($row->kepada_kode)->consignee_import_address,
												$row->group,
												$row->se_exportir,
											);
		    $i++;
		}
		echo json_encode($data);
	}
	
	function get_content() {
		$data = $this->m_cn_to_consignee->get_content($_POST);
		echo json_encode($data);
	}
	
	function dropdown_job_no(){
		$job = $this->m_cn_to_consignee->select_job()->result();
		$oper = $this->input->post('oper');
		$job_no = $this->input->post('job_no');		
		if($oper=='edit') {
			$option[$job_no]=$job_no;
			$id = $this->input->post('id');
		}
		if($oper=='add'){
			$option[]='';
			$id = floatval($this->get_last_id('CONSIGNEE','CREDIT NOTE'))+1;
		}
		
      foreach($job as $row){
         $option[$row->job_no]= $row->job_no;
      }
      
  		$dropdown = ': '. form_dropdown('jobNoForm',$option,$job_no,'id="jobNoForm" style="width: 200px;" onchange="get_nama_alamat()"');
  		$cn_no = $this->fungsi->get_new_num_inv($_POST);
  		echo json_encode(array('dropdown'=>$dropdown,'new_no'=>$cn_no, 'new_id'=>$id));
	}
	
	function get_nama_alamat(){
		$code = $this->m_cn_to_consignee->get_consignee_by_job($_POST)->shipper_import_code;
		$nama = $this->m_cn_to_consignee->get_consignee_by_job($_POST)->shipper_import;
		$alamat = $this->m_cn_to_consignee->get_consignee_by_job($_POST)->shipper_import_add;
		echo json_encode(array('code'=>$code,'nama'=>$nama,'alamat'=>$alamat));
	}
	
	function open_form_invoice(){
		$data['job_no'] = $this->input->post('job_no');
		$this->load->view('finance/v_cn_to_consignee_editor', $data);
	}

	function get_table_dua(){
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
	 	$sord = isset($_POST['sord'])?$_POST['sord']:'';
	 	
	 	$start = $batas*$hal - $batas;
		$start = ($start<0)?0:$start;
		$_POST['start']=$start;
		
		$q = $this->m_cn_to_consignee->get_table_dua($_POST,$limit=false);				
		$query = $this->m_cn_to_consignee->get_table_dua($_POST,$limit=true);
		
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
		foreach($query as $row) {
		   $data->rows[$i]['id']=$row->id;
			$data->rows[$i]['cell']=array(
											$row->item,
											$row->qty,
											$row->currency,
											$row->unit_price,
											$row->price,
											$row->pre_col,
											);
		    $i++;
		}
		echo json_encode($data);
	}

	function manage_data(){
		$status = $this->m_cn_to_consignee->manage_data($_POST);
		echo json_encode(array('status'=>$status,'oper'=>$_POST['oper']));
	}
	
	function print_out(){
		header('HTTP/1.0 200 OK');
		header('HTTP/1.1 200 OK');
  		header('Content-Disposition: inline; filename="'.$this->title.'";');
  		header('Last-Modified: '.gmdate('D, d M Y H:i:s', time()).' GMT');
		$kode_ref = $_GET['job'];
		$query = $this->m_cn_to_consignee->get_report_pdf($kode_ref);
		$region = $this->session->userdata('region_id');
		$logo='logo.png';
		$company = 'PT. GATEWAY CONTAINER LINE';
		$this->load->library('FPDF_MC_Table');
		$pdf=new FPDF_MC_Table();
		$pdf->AddPage('P','A4');
		if ($region=='Jakarta') {
			$pdf->SetFont('helvetica','',12);
			$pdf->SetXY(130, 10);
			$pdf->write(5,'PT. GATEWAY CONTAINER LINE');
			$pdf->SetFont('helvetica','',7);
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
			$pdf->SetFont('helvetica','',8);
			$pdf->SetXY(161, 32);
			$pdf->write(0,'general@gateway-id.com');
		} 
		else if ($region=='Surabaya')
		{
		$pdf->SetFont('helvetica','',12);
		$pdf->SetXY(130, 10);
		$pdf->write(5,'PT. GATEWAY CONTAINER LINE');
		$pdf->SetFont('helvetica','',7);
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
		$pdf->SetFont('helvetica','',8);
		$pdf->SetXY(161, 32);
		$pdf->write(0,'mgtsby@gateway-id.com');
		} 
		else if ($region=='Cikarang')
		{
		$pdf->SetFont('helvetica','',12);
		$pdf->SetXY(130, 10);
		$pdf->write(5,'PT. GATEWAY CONTAINER LINE');
		$pdf->SetFont('helvetica','',7);
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
		$pdf->SetFont('helvetica','',8);
		$pdf->SetXY(161, 32);
		$pdf->write(0,'lisbeth@gateway-id.com');
		}
		else if ($region=='Project')
		{
		$pdf->SetFont('helvetica','',12);
		$pdf->SetXY(130, 10);
		$pdf->write(5,'PT. GATEWAY CONTAINER LINE');
		$pdf->SetFont('helvetica','',7);
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
		$pdf->SetFont('helvetica','',8);
		$pdf->SetXY(165, 32);
		$pdf->write(0,'alex@gateway-id.com');
		}
		else if ($region=='Semarang')
		{
		$pdf->SetFont('helvetica','',12);	
		$pdf->SetXY(10, 13);
		$pdf->Cell(190,4, 'PT. GATEWAY CONTAINER LINE',0,1,'R',false,'');
		$pdf->SetFont('helvetica','',8);
		$pdf->Cell(190,4,strtoupper('JL. SEMARANG INDAH NO.D14 / 20A, TAWANGMAS'),0,1,'R',false,'');
		$pdf->Cell(190,4,'KEC. SEMARANG BAR.',0,1,'R',false,'');
		$pdf->Cell(190,4,'KOTA SEMARANG, JAWA TENGAH 50144',0,1,'R',false,'');
		$pdf->Cell(190,4,'PHONE : ( 62-24 ) 76632972',0,1,'R',false,'');
		$pdf->Cell(190,4,'mgtsrg@gateway-id.com',0,1,'R',false,'');
		}
		else if ($region=='Medan')
		{
		$pdf->SetFont('helvetica','',12);	
		$pdf->SetXY(10, 13);
		$pdf->Cell(190,4, 'PT. GATEWAY CONTAINER LINE',0,1,'R',false,'');
		$pdf->SetFont('helvetica','',8);
		$pdf->Cell(190,4,strtoupper('MANDIRI BUILDING 7TH FLOOR'),0,1,'R',false,'');
		$pdf->Cell(190,4,'JL. IMAM BONJOL NO. 16-D MEDAN 20112',0,1,'R',false,'');
		$pdf->Cell(190,4,'SUMATRA UTARA 20112 - INDONESIA',0,1,'R',false,'');
		$pdf->Cell(190,4,'PHONE : (62-61) 4559876',0,1,'R',false,'');
		}
		else if ($region=='Bandung')
		{
		$pdf->SetFont('helvetica','',12);	
		$pdf->SetXY(10, 13);
		$pdf->Cell(190,4, 'PT. GATEWAY CONTAINER LINE',0,1,'R',false,'');
		$pdf->SetFont('helvetica','',8);
		$pdf->Cell(190,4,strtoupper('METRO TRADE CENTRE, JL SOEKARNO HATTA'),0,1,'R',false,'');
		$pdf->Cell(190,4,'BLOK D NO.21 SEKEJATI, KEC. BUAH BATU',0,1,'R',false,'');
		$pdf->Cell(190,4,'KOTA BANDUNG, JAWA BARAT 40286',0,1,'R',false,'');
		$pdf->Cell(190,4,'PHONE : (022) 7502283',0,1,'R',false,'');
		}
		else if ($region=='Osline')
		{
			$pdf->SetFont('helvetica','',12);
			$pdf->SetXY(10, 13);
			$pdf->Cell(190,4, 'PT. BINTANG SAMUDERA ANGKASA',0,1,'R',false,'');
			$pdf->SetFont('helvetica','',8);
			$pdf->Cell(190,4,strtoupper('WISMA MITRA SUNTER 15th FLOOR, SUITE 15-04'),0,1,'R',false,'');
			$pdf->Cell(190,4,'Jl Yos sudarso Kav.89 Boulevard Mitra Sunter Blok C2',0,1,'R',false,'');
			$pdf->Cell(190,4,'JAKARTA UTARA 14350 - INDONESIA',0,1,'R',false,'');
			$pdf->Cell(190,4,'PHONE (62) 21 22651085',0,1,'R',false,'');
			$logo = 'osline.png';
			$company = 'PT. BINTANG SAMUDERA ANGKASA';
		}
		else if ($region=='OSLSRG')
		{
			$pdf->setDataHeader(array('report'=>'invoice'));
			$pdf->SetFont('helvetica','',12);
			$pdf->SetXY(10, 13);
			$pdf->Cell(190,4, 'PT. BINTANG SAMUDERA ANGKASA',0,1,'R',false,'');
			$pdf->SetFont('helvetica','',8);
			$pdf->Cell(190,4,strtoupper('RUKO SEMARANG INDAH '),0,1,'R',false,'');
			$pdf->Cell(190,4,'BLOK E 2 NO 19 RT. 002 RW. 010',0,1,'R',false,'');
			$pdf->Cell(190,4,'TAWANGMAS SEMARANG BARAT, KOTA SEMARANG',0,1,'R',false,'');
			$pdf->Cell(190,4,'PHONE ( 024) 764 32 882',0,1,'R',false,'');
			$logo = 'osline.png';
			$company = 'PT. BINTANG SAMUDERA ANGKASA';
		}
		else if ($region=='OSLSUB')
		{
			$pdf->setDataHeader(array('report'=>'invoice'));
			$pdf->SetFont('helvetica','',12);
			$pdf->SetXY(10, 13);
			$pdf->Cell(190,4, 'PT. BINTANG SAMUDERA ANGKASA',0,1,'R',false,'');
			$pdf->SetFont('helvetica','',8);
			$pdf->Cell(190,4,strtoupper('JL TANJUNG SADARI NO. 2-4'),0,1,'R',false,'');
			$pdf->Cell(190,4,'FLOOR II ROOM B204',0,1,'R',false,'');
			$pdf->Cell(190,4,'SURABAYA JAWA TIMUR 60177',0,1,'R',false,'');
			$pdf->Cell(190,4,'PHONE ( 031) 99092318',0,1,'R',false,'');
			$logo = 'osline.png';
			$company = 'PT. BINTANG SAMUDERA ANGKASA';
		}
		else if ($region=='Winfast')
		{
			$pdf->setDataHeader(array('report'=>'invoice'));
			$pdf->SetFont('helvetica','',12);
			$pdf->SetXY(10, 13);
			$pdf->Cell(190,4, 'PT. WINFASTSHIPPING INDONESIA',0,1,'R',false,'');
			$pdf->SetFont('helvetica','',8);
			$pdf->Cell(190,4,strtoupper('MTH SQUARE LANTAI 3'),0,1,'R',false,'');
			$pdf->Cell(190,4,'JL. OTTO ISKANDARDINATA BLOK 390 NO. 16',0,1,'R',false,'');
			$pdf->Cell(190,4,'JAKARTA TIMUR 13330',0,1,'R',false,'');
			$pdf->Cell(190,4,'PHONE (62) 21 501108 26 - 27',0,1,'R',false,'');
			$logo = 'winfast.png';
			$company = 'PT. WINFASTSHIPPING INDONESIA';
		}
		else if ($region=='OSLBDG')
		{
			$pdf->setDataHeader(array('report'=>'invoice'));
			$pdf->SetFont('helvetica','',12);
			$pdf->SetXY(10, 13);
			$pdf->Cell(190,4, 'PT. BINTANG SAMUDERA ANGKASA',0,1,'R',false,'');
			$pdf->SetFont('helvetica','',8);
			$pdf->Cell(190,4,'RUKO BLOK H-22',0,1,'R',false,'');
			$pdf->Cell(190,4,'KAWASAN NIAGA METRO INDAH MALL',0,1,'R',false,'');
			$pdf->Cell(190,4,'JL. SOEKARNO HATTA 590 BANDUNG 40285',0,1,'R',false,'');
			$pdf->Cell(190,4,'PHONE ( 022) 87504221',0,1,'R',false,'');
			$logo = 'osline.png';
			$company = 'PT. BINTANG SAMUDERA ANGKASA';
		}
		$pdf->Image(APPPATH.'third_party/img/'.$logo,10,12,+50);
		$pdf->SetXY(87, 45);
		$pdf->SetFont('helvetica','B',16);
		$pdf->write(0,$_GET['jenis']);
		$pdf->SetXY(177, 49);
		$pdf->SetFont('helvetica','',11);
		$pdf->write(0,$_GET['no_invoice']);
		$pdf->Line(10,53,200,53);		
		$pdf->Line(120,87,120,53);		
		$pdf->SetFont('helvetica','',8);
		$pdf->SetXY(10, 56);
		$pdf->write(0,'MSSRS :');
		$pdf->SetXY(10, 59);
		$pdf->MultiCell(105,3.5,$_GET['kepada']);
		$pdf->SetXY(123, 56);
		$pdf->write(0,'CN NUMBER');
		$pdf->SetXY(149, 56);
		$pdf->write(0,':');		
		$pdf->SetXY(152, 56);
		$pdf->write(0,$_GET['no_invoice']);		
		$pdf->SetXY(123, 60);
		$pdf->write(0,'DATE');
		$pdf->SetXY(149, 60);
		$pdf->write(0,':');
		$pdf->SetXY(152, 60);
		$pdf->write(0,$_GET['date']);		
		$pdf->SetXY(123, 64);
		$pdf->write(0,'PARTY');
		$pdf->SetXY(149, 64);
		$pdf->write(0,':');
		$pdf->SetXY(152, 64);
		$pdf->write(0,strtoupper($query['no_of_pack']??""));
		$pdf->SetXY(123, 68);
		$pdf->write(0,'GWT');
		$pdf->SetXY(149, 68);
		$pdf->write(0,':');
		$pdf->SetXY(152, 68);
		$pdf->write(0,$query['weight']??"".' '.'KGS');
		$pdf->SetXY(123, 72);
		$pdf->write(0,'CBM');
		$pdf->SetXY(149, 72);
		$pdf->write(0,':');
		$pdf->SetXY(152, 72);
		$pdf->write(0,$query['bl_meas']??"".' '.'M3');
		$pdf->SetXY(123, 76);
		$pdf->write(0,'KODE REF');
		$pdf->SetXY(149, 76);
		$pdf->write(0,':');
		$pdf->SetXY(152, 76);
		$pdf->write(0,$kode_ref);				
		$pdf->Ln();
		$pdf->Line(10,79,200,79);					
		$pdf->SetXY(36, 83);
		$pdf->write(0,'DETAIL OF CHARGES');
		$pdf->SetXY(96, 83);
		$pdf->write(0,'QUANTITY');
		$pdf->SetXY(127, 83);
		$pdf->write(0,'UNIT RATE');
		$pdf->SetXY(160, 83);
		$pdf->write(0,'AMOUNT');				
		$pdf->Line(10,87,200,87);	
			
		$pdf->Line(90,79,90,87);
		$pdf->Line(150,79,150,87);		
		
		$pdf->Ln(5);
		
		$number = $_GET['number'];
		$data =  $this->m_cn_to_consignee->get_detail($number); 
		
		if($data) {
			foreach ($data->result_array() as $row){
				$amount = $row['qty'] * $row['unit_price'];
				$pdf->Cell(80, 6,strtoupper($row['item']),0,0,'L');
				$pdf->Cell(30, 6,$row['qty'],0,0,'C');
				$pdf->Cell(10, 6,$row['currency'],0,0,'R');
				$pdf->Cell(19, 6,$this->fungsi->pecah($row['unit_price'],','),0,0,'R');
				$pdf->Cell(13, 6,$row['currency'],0,0,'R');
				$pdf->Cell(39, 6,$this->fungsi->pecah($amount,','),0,0,'R');
				$pdf->Ln();
			}
		}
		
		//$jumlah_idr = $this->m_inv_to_shipper->get_ttl_inv($id,'RP.');
		//$jumlah_usd = $this->m_inv_to_shipper->get_ttl_inv($id,'USD');
		
		$jumlah_idr = $this->m_cn_to_consignee->get_ttl_detail($number,'RP.');
		$jumlah_usd = $this->m_cn_to_consignee->get_ttl_detail($number,'USD');
		
		$ttl_usd = '';
		$ttl_idr = '';
		
		if(!empty($jumlah_usd)) 
		$ttl_usd=$this->fungsi->pecah(array_sum($jumlah_usd),',');
		
		if(!empty($jumlah_idr)) 
		$ttl_idr=$this->fungsi->pecah(array_sum($jumlah_idr),',');
		
		$pdf->Line(10,230,200,230);
		$pdf->Line(151,240,151,230);
		$pdf->Line(200,240,151,240);
		$pdf->Line(200,280,135,280);

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
		$pdf->Ln(2);
		if ($ttl_idr!=''){
			if ($region=='Jakarta'){
			$pdf->SetFont('helvetica','',8);
			$pdf->Cell(190, 4,$company,0,1,'R',false);
			$pdf->Cell(10, 4,'IDR : ',0,0,'L',false);
			$pdf->Cell(180, 4,$company,0,1,'L',false);				
			$pdf->Cell(10, 4,'',0,0,'L',false);
			$pdf->Cell(180, 4,'MAYBANK INDONESIA ',0,1,'L',false);
			$pdf->Cell(10, 4,'',0,0,'L',false);
			$pdf->Cell(180, 4,'CAB. TG. PRIOK ENGGANO',0,1,'L',false);
			$pdf->Cell(10, 4,'',0,0,'L',false);
			$pdf->Cell(180, 4,'A/C : 2082409727',0,1,'L',false);
			}else if ( $region =='Osline' || $region =='OSLSRG' || $region =='OSLSUB' || $region =='OSLBDG'){
			$pdf->SetFont('helvetica','',8);
			$pdf->Cell(190, 4,$company,0,1,'R',false);
			$pdf->Cell(10, 4,'IDR : ',0,0,'L',false);
			$pdf->Cell(180, 4,$company,0,1,'L',false);				
			$pdf->Cell(10, 4,'',0,0,'L',false);
			$pdf->Cell(180, 4,'MAYBANK INDONESIA ',0,1,'L',false);
			$pdf->Cell(10, 4,'',0,0,'L',false);
			$pdf->Cell(180, 4,'CAB. TG. PRIOK ENGGANO',0,1,'L',false);
			$pdf->Cell(10, 4,'',0,0,'L',false);
			$pdf->Cell(180, 4,'A/C : 2082411362',0,1,'L',false);
			}
		}else{
		if($region=='Project') {
			$pdf->Cell(143, 4,'',0,0,'L',false);
			$pdf->Cell(35, 4,$company,0,1,'L',false);
			$pdf->Cell(153, 4,'',0,0,'L',false);
			$pdf->Cell(35, 4,'PROJECT DIVISION',0,1,'L',false);
			$pdf->Cell(10, 4,'',0,0,'L',false);
			$pdf->Cell(180, 4,'BANK DETAIL :',0,1,'L',false);
			$pdf->Cell(10, 4,'',0,0,'L',false);
			$pdf->Cell(180, 4,'MAYBANK INDONESIA',0,1,'L',false);
			$pdf->Cell(10, 4,'',0,0,'L',false);
			$pdf->Cell(180, 4,'CAB. TG. PRIOK ENGGANO',0,1,'L',false);
			$pdf->Cell(10, 4,'',0,0,'L',false);
			$pdf->Cell(180, 4,'A/C NAME : PT. GATEWAY CONTAINER LINE',0,1,'L',false);
			$pdf->Cell(10, 4,'',0,0,'L',false);
			$pdf->Cell(180, 4,'A/C NUMBER : 2082001561',0,1,'L',false);
		}
			else if($region =='Osline' || $region =='OSLSRG' || $region =='OSLSUB' || $region == 'OSLBDG') { 
			$pdf->Cell(143, 4,'',0,0,'L',false);
			$pdf->Cell(35, 4,$company,0,1,'L',false);
			$pdf->Cell(153, 4,'',0,0,'L',false);
			$pdf->Cell(35, 4,'AS AGENT OSLINE',0,1,'L',false);
			$pdf->Cell(10, 4,'',0,0,'L',false);
			$pdf->Cell(180, 4,'MAYBANK INDONESIA',0,1,'L',false);
			$pdf->Cell(10, 4,'',0,0,'L',false);
			$pdf->Cell(180, 4,'CAB. TG. PRIOK ENGGANO',0,1,'L',false);
			$pdf->Cell(10, 4,'',0,0,'L',false);
			$pdf->Cell(180, 4,'A/C NAME : PT. BINTANG SAMUDERA ANGKASA',0,1,'L',false);
			$pdf->Cell(10, 4,'',0,0,'L',false);
			$pdf->Cell(180, 4,'A/C NUMBER : 2082002128',0,1,'L',false);
			$pdf->Cell(10, 4,'',0,0,'L',false);
			$pdf->Cell(180, 4,'SWIFT CODE : IBBKIDJA',0,1,'L',false);
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
		}		
		$pdf->Output('Invoce Proses','I'); 
	}

	protected function list_table(){
		$table = $this->dbx->list_tables();
		echo $this->dbx->total_queries($table);
	//	$field = $this->dbx->list_fields($table[5]);
	//	//print_r($field); 
	}
}
