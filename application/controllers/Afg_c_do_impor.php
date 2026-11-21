<?php
class Afg_c_do_impor extends CI_Controller
{

	
	public function __construct()
	{
		parent::__construct();
		$this->fungsi->restrict();
		$this->load->model(array('afg_m_do_impor','afg_m_stuffing_plan'));
	}
	
	function inject_db(){
		//print_r($_POST);
		//die();
		$value = $this->afg_m_do_impor->inject_db($_POST);
		echo json_encode($value);
	}
	
	function inject_db_ceisa()
	{
		// Support payload JSON agar tidak terkena batas PHP max_input_vars
		// pada detail CEISA yang jumlah row/kolomnya besar.
		$payload = $_POST;

		$contentType = (string) $this->input->get_request_header('Content-Type', TRUE);
		if (stripos($contentType, 'application/json') !== false) {
			$raw = $this->input->raw_input_stream;
			$decoded = json_decode($raw, true);

			if (is_array($decoded)) {
				$payload = $decoded;
			} else {
				$this->output
					->set_content_type('application/json')
					->set_output(json_encode(array(
						'status' => 0,
						'code' => 'INVALID_JSON',
						'message' => 'Payload JSON tidak valid.'
					)));
				return;
			}
		}

		$value = $this->afg_m_do_impor->inject_db_ceisa($payload);

		$this->output
			->set_content_type('application/json')
			->set_output(json_encode($value));
	}
	
	function check_db () {
		$this->fungsi->odbc_define();
		$this->load->model('odbc_model');
		$data = $this->odbc_model->check_db();
		echo json_encode($data);
	}
	
	function index() {
		$this->fungsi->auth(60,'DO Import');
		$data['title'] = 'DO Impor';
		$data['menu_navigation'] = 'navigation';
		$data['main_view'] = 'do_impor/index';				
		$this->load->view('afg_template', $data);
	}
	
	
	function manage_data() {
		$do_id = $this->input->post('id');
		$mode = $this->input->post('oper');
		
		$package = explode(" ",$this->input->post('no_of_pack'));
		if(count($package)>=2) 
		{
			$total = $package[0];
			$dp = substr($this->input->post('no_of_pack'),strlen($package[0]),200);
			$pack = strtoupper($dp);
		}
		else 
		{
			$total = "";
			$pack = "";
		}
		
		$data = array(
			'bl_no'=>$this->input->post('bl_no'),
			'job_no'=>$this->input->post('job_no'),
			'tanggal_do'=>$this->fungsi->jin_date_sql($this->input->post('tanggal_do')),
			'consignee'=>strtoupper($this->input->post('consignee')), 
			'from'=>$this->input->post('from'),
			'vessel'=>$this->input->post('vessel'),
			'arrive_date'=>$this->fungsi->jin_date_sql($this->input->post('arrive_date')),
			'containeer'=>$this->input->post('containeer'),
			'warehouse'=>$this->input->post('warehouse'),	
			'shipping_mark'=>strtoupper($this->input->post('notes')),
			'description'=>strtoupper($this->input->post('description')),
			'wg'=>$this->input->post('wg'),
			'ms'=>$this->input->post('ms'),
			'total'=>$total,
			'pack'=>$pack,
		);
		if($mode == 'add') 
		{
			$query = $this->afg_m_do_impor->add_data_import($data);
			echo json_encode(array( "status" => $query,'mode'=>'add' ));
		} 
		else if($mode == 'edit') 
		{
			$query = $this->afg_m_do_impor->update_data_import($data,$do_id);
			echo json_encode(array( "status" => $query,'mode'=>'edit' ));
		}
		else if($mode == 'del') 
		{
			$query = $this->afg_m_do_impor->delete_data_import($_POST);
			echo json_encode(array( "status" => $query,'mode'=>'delete' ));
		}
	}
	
	function dropdownjobnonew()
	{
		$job_no = $this->afg_m_do_impor->getListJobNoBlForm()->result();
		$data['option_job_no'][0]=' ';
		foreach ($job_no as $row)
		{	
			$data['option_job_no'][$row->kode_ref]=$row->kode_ref;
		}
		$job_no = form_dropdown('selectJobNo',$data['option_job_no'],'','id="selectJobNo" disabled="disabled" style="width: 180px;"');
		echo json_encode(array( "dropdown" => $job_no ));
	}
	
	function dropdownjobno()
	{
		$job = $this->afg_m_do_impor->getListJobNoBlForm();
		//print_r ($job);
		//die();
		$count = count($job);
		if($count>=1){
			$data['option']['']='';
			foreach ($job as $i => $row) {
    			unset($job[$i]);
    			$data['option'][$row]=$row;
			}
			echo form_dropdown('opt_job_no',$data['option'],'','id="opt_job_no" style="max-width: 180px;"');
		}  else {
			echo form_dropdown('opt_job_no',array(""=>""),'','id="opt_job_no" style="min-width: 180px;"');
		}
	}
	
	function getDataImporByJobNo()
	{
		$query = $this->afg_m_do_impor->getDataImporByJobNo($_POST);
		if($query) {
			$row = $query->row();
			$row->vessel =  $row->vessel.' '.$row->voyage;
			$row->arrive_date = $this->fungsi->jin_date_str($row->eta);
			$row->tanggal_do = date('d/m/Y');
			echo json_encode($row);
		} else {
			echo json_encode(array(
				'arrive_date'=>'',
				'cont_seal_no'=>'',
				'desc_goods'=>'',
				'hb_l'=>'',
				'no_pack_qty'=>'',
				'no_pack_unit'=>'',
				'port_loading' => '',
				'ship_meas' => '',
				'shipper_import' => '',
				'shipper_import_add' => '',
				'shipper_import_code' => '',
				'shipping_mark' => '',
				'stuffing_date' => '',
				'tanggal_do' => '',
				'vessel' => '',
				'voyage' => '',
				'warehouse' => '',
				'weight' => '',
			));
		}	
	}
	
	function getDataImpor()
	{
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
		$sord = isset($_POST['sord'])?$_POST['sord']:'';
				
		$start = $batas * $hal - $batas;
		$start = ($start<0)?0:$start;			
		$_POST['start'] = $start;
		
		$q = $this->afg_m_do_impor->tampil_data($_POST,false);
		$query = $this->afg_m_do_impor->tampil_data($_POST,true);

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
			 $consignee = explode("\n",$row->consignee);
		    $data->rows[$i]['id']=$row->do_id;
		    $data->rows[$i]['cell']=array(
		    								$row->job_no,
											$row->bl_no,		    								
											$this->fungsi->jin_date_str($row->tanggal_do),
											$consignee[0],
											$row->from,
											$row->vessel,
											$row->arrive_date,
											$row->containeer,
											$row->warehouse,
											htmlentities($row->description),
											$row->wg,
											$row->ms,
											$row->total." ".$row->pack,
											str_replace($consignee[0],"",$row->consignee),
											htmlentities($row->shipping_mark),
										);
		    $i++;
		}
		echo json_encode($data);
	}
	
	function manage_file() 
	{
		$value = $this->afg_m_do_impor->manage_file($_POST);
		echo json_encode($value);
	}
	
	function ln($new='\n') {
		return $new;
	}
	
	function print_out($id)
	{
		//include the libraries
		require_once("pdf/phpqrcode/qrlib.php");
		
		//create a QR Code and save it as a png image file named test.png
		$query = $this->afg_m_do_impor->get_data_print($id);
		//QRcode::png($query['job_no'],"test.png");
		$jobno = $query['job_no'];
		
		if($this->session->userdata('region_id')=='Osline') {
			$cmp = 'OCEAN SKY LINE';
			$logo ='osline.png';
			$add = 'WISMA MITRA SUNTER 15th FLOOR, SUITE 15-04
Jl Yos sudarso Kav.89 Boulevard Mitra Sunter Blok C2
JAKARTA UTARA 14350 - INDONESIA
PHONE (62) 21 22651085';
} else if ($this->session->userdata('region_id')=='Medan'){
			$cmp = 'PT GATEWAY CONTAINER LINE';
			$logo = 'logo.png';
			$add = 'MANDIRI BUILDING 7TH FLOOR
JL. IMAM BONJOL NO. 16-D MEDAN - 20112
PHONE : (62-61) 4559876
FAX : (62-61) 4534696
mgtblw@gateway-id.com';
		} else if ($this->session->userdata('region_id')=='Surabaya'){
			$cmp = 'PT GATEWAY CONTAINER LINE';
			$logo = 'logo.png';
			$add = 'JL GAYUNGSARI BARAT NO 91B
GAYUNGAN, KEC. GAYUNGAN, KOTA SBY, JAWA TIMUR 60235
PHONE : 031 82522370
		031 82522405
		031 82522827-830
mgtsby@gateway-id.com';
		} else if ($this->session->userdata('region_id')=='Semarang'){
			$cmp = 'PT GATEWAY CONTAINER LINE';
			$logo = 'logo.png';
			$add = 'RUKO SEMARANG INDAH BLOK E-2 NO.19,
JL MADUKORO KOTA SEMARANG JAWA TENGAH
PHONE : (024) 76632972
FAX :(024) 76632972
mgtsrg@gateway-id.com';
		}else if ($this->session->userdata('region_id')=='OSLSRG'){
			$cmp = 'PT BINTANG SAMUDERA ANGKASA';
			$logo = 'osline.png';
			$add = 'RUKO SEMARANG INDAH  BLOK E 2 NO 19 RT. 002 RW. 010
KOTA SEMARANG, JAWA TENGAH 50144
PHONE : (024) 764 32 882
mngt-srg@osline.co.id';
		} else if ($this->session->userdata('region_id')=='OSLSUB'){
			$cmp = 'PT BINTANG SAMUDERA ANGKASA';
			$logo = 'osline.png';
			$add = 'JL TANJUNG SADARI NO. 2-4
FLOOR II ROOM B204
SURABAYA JAWA TIMUR 60177
PHONE : (031) 99092318';
		} else if ($this->session->userdata('region_id')=='OSLBDG'){
			$cmp = 'PT BINTANG SAMUDERA ANGKASA';
			$logo = 'osline.png';
			$add = 'RUKO BLOK H-22, 
KAWASAN NIAGA METRO INDAH MALL,
JL. SOEKARNO HATTA 590 BANDUNG 40285';
		} else if ($this->session->userdata('region_id')=='BPLINE'){
			$cmp = 'PT BANGUN PUTRA PESAKA';
			$logo = 'bpline.png';
			$add = 'JL.  TIPAR CAKUNG NO 52
KELURAHAN SUKAPURA
KEC CILINCING.
JAKARTA UTARA 14140';
		} else if ($this->session->userdata('region_id')=='Winfast'){
			$cmp = 'PT WINFASTSHIPPING INDONESIA';
			$logo = 'winfast.png';
			$add = 'MTH SQUARE LANTAI 3
JL. OTTO ISKANDARDINATA 
BLOK 390 NO. 16
JAKARTA TIMUR 13330';
		} else {
			$cmp = 'PT GATEWAY CONTAINER LINE';
			$logo = 'logo.png';
			$add = 'JL. OTISTA III RT. 001 RW. 001 NO. 18
CIPINANG CEMPEDAK JATINEGARA
JAKARTA TIMUR 13340
PHONE : ( 62-21 ) 8591-8669 ( HUNTING )
FAX : ( 62-21 ) 8591-8664
general@gateway-id.com';
		}
    	$this->load->library('FPDF_MC_Table');
		$pdf=new FPDF_MC_Table();	
		$pdf->setDataHeader(array('report'=>'e_do'));
		$pdf->AddPage('P','letter');
		if ($this->session->userdata('region_id')=='Winfast'){
		$pdf->Image(APPPATH.'third_party/img/'.$logo,10,12.5,+30);
		}else{
		$pdf->Image(APPPATH.'third_party/img/'.$logo,10,12.5,+43);
		}
		$pdf->SetFont('Arial','B',12);
		$pdf->SetXY(10, 13);
		$pdf->Cell(195,5,$cmp,0,1,'R',false,'');
		$pdf->SetFont('Arial','',10);
		$pdf->Cell(195,5,'DELIVERY ORDER',0,1,'R',false,'');
		$pdf->Cell(195,5,'SURAT PERINTAH PENYERAHAN',0,1,'R',false,'');
		
		$pdf->Line(10,35,200,35);		
		
		$pdf->SetFont('Arial','',9);
		$pdf->SetXY(10, 39);
		$pdf->write(0,'Please Deliver To   :');
		$pdf->SetXY(15, 43);
		$pdf->MultiCell(95,4,$query['consignee'],0,'','');
		
		$pdf->SetFont('Arial','',10);
		$pdf->SetXY(110, 39);
		$pdf->write(0,'Vessel');
		$pdf->SetXY(130, 39);
		$pdf->write(0,':');
		$pdf->SetXY(132, 39);
		$pdf->write(0,$query['vessel']);
		
		$pdf->SetFont('Arial','',10);
		$pdf->SetXY(110, 45);
		$pdf->write(0,'From ');
		$pdf->SetXY(130, 45);
		$pdf->write(0,':');
		$pdf->SetXY(132, 45);
		$pdf->write(0,$query['from']);
		
		$pdf->SetFont('Arial','',10);
		$pdf->SetXY(110, 51);
		$pdf->write(0,'B/L No. ');
		$pdf->SetXY(130, 51);
		$pdf->write(0,':');
		$pdf->SetXY(132, 51);
		$pdf->write(0,strtoupper($query['bl_no']));
		
		$pdf->SetFont('Arial','',10);
		$pdf->SetXY(110, 57);
		$pdf->write(0,'Arrive ');
		$pdf->SetXY(130, 57);
		$pdf->write(0,':');
		$pdf->SetXY(132, 57);
		$pdf->write(0,$this->fungsi->jin_date_str($query['arrive_date']));
		
		$pdf->SetXY(110, 63);
		$pdf->write(0,'Warehouse');
		$pdf->SetXY(130, 63);
		$pdf->write(0,':');
		$pdf->SetXY(132, 63);
		$pdf->write(0,$query['warehouse']);
		
		$pdf->SetFont('Arial','',9);
		$pdf->SetXY(20, 79);
		$pdf->write(0,'MARK & NUMBER');
		
		$pdf->SetXY(80, 76);		
		$pdf->write(0,'NO OF');
		$pdf->SetXY(78, 79);
		$pdf->write(0,'PACKAGE');
		
		$pdf->SetXY(118, 79);
		$pdf->write(0,'DESCRIPTION OF CARGO');
		
		$pdf->SetXY(175, 76);
		$pdf->write(0,'WEIGHT');
		$pdf->SetXY(174, 79);
		$pdf->write(0,'MEASURE');
		
		$pdf->SetXY(172, 91);
		$pdf->write(0,'WEIGHT  :');
		$pdf->SetXY(172, 108);
		$pdf->write(0,'MEASS  :');
		
		$pack = strtoupper($this->afg_m_do_impor->get_pack($query['pack']));
				
		$pdf->SetXY(70, 196);
		$pdf->write(0,$query['total'].' ( '.$this->fungsi->num_to_words($query['total'],'',0,'').' ) '.$pack.' ONLY');
		$pdf->SetFont('Arial','',9);
		$pdf->SetXY(10, 205);
		$pdf->write(0,'Container No.   :');
		
		$pdf->SetXY(37, 205);
		$pdf->write(0,$query['containeer']);

		if($this->session->userdata('region_id')=='Surabaya' || $this->session->userdata('region_id')=='OSLSUB'){
		$pdf->SetXY(140, 205);
		$pdf->write(0,'Tanjung Perak, '.$this->fungsi->jin_date_str2($query['arrive_date']));}
		else if ($this->session->userdata('region_id')=='Semarang' || $this->session->userdata('region_id')=='OSLSRG'){
		$pdf->SetXY(140, 205);
		$pdf->write(0,'Tanjung Mas, '.$this->fungsi->jin_date_str2($query['arrive_date']));
		}
		else if ($this->session->userdata('region_id')=='Medan'){
		$pdf->SetXY(140, 205);
		$pdf->write(0,'Belawan, '.$this->fungsi->jin_date_str2($query['arrive_date']));
		}
		else{
		$pdf->SetXY(140, 205);
		$pdf->write(0,'Tanjung Priok, '.$this->fungsi->jin_date_str2($query['arrive_date']));}

		$pdf->SetXY(140, 209);
		$pdf->write(0,$cmp);
		
		$pdf->SetFont('Arial','',9);
		$pdf->SetXY(10, 234);
		$pdf->write(0,$cmp);
		$pdf->SetFont('Arial','',8);
		$pdf->SetXY(10, 236);
		$pdf->MultiCell(95,3.5,$add,0,'','');
		$pdf->SetXY(100, 236);
		$pdf->Image("http://localhost/pdf/qr_generator.php?code=$jobno", 150, 230, 28, 28, "png");

		
		$pdf->Line(10,70,200,70);
		$pdf->Line(10,73,200,73);
		$pdf->Line(10,83,200,83);
		$pdf->Line(10,200,200,200);
		$pdf->Line(10,230,200,230);
		
		$pdf->Line(108,35,108,70);
		$pdf->Line(65,73,65,83);
		$pdf->Line(108,73,108,83);
		$pdf->Line(170,73,170,83);
		
		$pdf->SetFont('Arial','',9);
		$pdf->SetXY(10, 87);
		$pdf->MultiCell(50,4,$query['shipping_mark'],0,'','');
		
		$pdf->SetXY(70, 87);
		
		$pdf->MultiCell(35,4,$query['total'].' '.$pack,0,'','');
	
		$pdf->SetXY(115, 87);
		$hsCode = trim((string)($query['hs_code'] ?? ''));
		if ($hsCode !== '') {
			$pdf->SetFont('Arial','B',9);
			$pdf->MultiCell(50,4,'*HS CODE : '.$hsCode,0,'','');
			$pdf->SetFont('Arial','',9);
			$pdf->SetX(115);
		}
		$pdf->MultiCell(50,4,$query['description'],0,'','');
		
		$pdf->SetXY(174, 97);
		$pdf->write(0,$query['wg']);
		$pdf->SetXY(176, 114);
		$pdf->write(0,$query['ms']);
		
		$pdf->SetXY(190, 97);
		$pdf->write(0,'KGS');
		$pdf->SetXY(190, 114);
		$pdf->write(0, ' M');
		$pdf->SetXY(193, 113);
		$pdf->SetFont('Arial','',6);		
		$pdf->write(0, ' 3');
		
		$pdf->Output('DO-'.$query['bl_no'],'I'); 
	}
	
	function get_code_shipper_import()
	{
		$data = $this->afg_m_do_impor->get_code_shipper_import($_POST);
		echo json_encode($data);
	}

	function insert_consignee()
	{
		$data = $this->afg_m_do_impor->insert_consignee($_POST);
	}
	
	function ajaxFileUpload() 
	{
		$error = "";
		$msg = "";
		$files = "";
		$fileElementName = 'nama_file';
		
		if(!empty($_FILES[$fileElementName]['error']))
		{
			switch($_FILES[$fileElementName]['error'])
			{
				case '1':
					$error = 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
					break;
				case '2':
					$error = 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
					break;
				case '3':
					$error = 'The uploaded file was only partially uploaded';
					break;
				case '4':
					$error = 'No file was uploaded.';
					break;
				case '6':
					$error = 'Missing a temporary folder';
					break;
				case '7':
					$error = 'Failed to write file to disk';
					break;
				case '8':
					$error = 'File upload stopped by extension';
					break;
				case '999':
					default:
					$error = 'No error code avaiable';
			}
		} 
		elseif(empty($_FILES['nama_file']['tmp_name']) || $_FILES['nama_file']['tmp_name'] == 'none')  
		{
			$error = 'No file was uploaded..';
		} 
		else 
		{
			$msg .= " File Name: " . $_FILES['nama_file']['name'] . ", ";
			$msg .= " File Size: " . @filesize($_FILES['nama_file']['tmp_name']);
			
			//$uploads_dir = $_SERVER["DOCUMENT_ROOT"]."/public/file/xls";
			$uploads_dir = APPPATH.'third_party/file/mdb/';
			$tmp_name = $_FILES["nama_file"]["tmp_name"];
        	$name = md5(time().$_FILES["nama_file"]["name"]).'.mdb';
			
			$this->load->helper('file');
			delete_files(APPPATH.'third_party/file/mdb');
			
        	if(move_uploaded_file($tmp_name, "$uploads_dir/$name")){
				$text = $name . ';' . $this->session->userdata('fullname') . ';' . date('d-m-Y') . ';' . date('H:i:s');
				write_file(APPPATH.'third_party/file/mdb/0000.txt', $text);
			}
		}	
		echo json_encode(array('error'=>$error,'msg'=>$msg));
	}
	
	/* new module */
	
	
	function get_file() {
		$this->load->helper('directory');
		$this->load->helper('file');
		$files = directory_map(APPPATH.'third_party/file/mdb');
		if(empty($files)) :
			echo json_encode(array('status'=>0));
			return;
		else :
			if(count($files)!=2):
				delete_files(APPPATH.'third_party/file/mdb');
				echo json_encode(array('status'=>0));
				return;
			else :
				sort($files);
				$string = read_file(APPPATH.'third_party/file/mdb/0000.txt');
				$text = explode(';',$string);
				$file = $text[0];
				$name = $text[1];
				$date = $text[2];
				$time = $text[3];
				$this->odbc_connect_db($file);
			endif;
		endif;
		
		echo json_encode(
			array(
				'status'=>1,
				'file'=>$file,
				'name'=>$name,
				'date'=>$date,
				'time'=>$time,
				'nmbl' =>$this->get_nmbl($file),
			)
		);
	}
	
	function odbc_connect_db($file) {
		
		//$pdo = PDO::getAvailableDrivers();
		
		$uname = explode(" ",php_uname());
		$os = $uname[0];
		switch ($os){
  			case 'Windows': 
				$dsn = 'DRIVER={Microsoft Access Driver (*.mdb, *.accdb)};DBQ='.APPPATH.'third_party/file/mdb/'.$file; 
				$driver = 'odbc';
				break;
  			case 'Linux': 	
				$dsn = 'odbc:DRIVER=MDBTools;DBQ='.APPPATH.'third_party/file/mdb/'.$file;
				$driver = 'pdo';
				break;
  			default:
    		echo json_encode(array('status'=>0));
		}
		$dbs=array('odbc_dsn'=>$dsn,'odbc_driver'=>$driver);
		$this->session->set_userdata($dbs);
		//$con = odbc_connect($dsn, "", "admin");
		//return $con;
	}
	
	
	/*
	
	yang ini bapak belum ke update sama belum kebuang uncomment nya ;) 
	
	
	function get_file() {
		$this->load->helper('directory');
		$this->load->helper('file');
		$files = directory_map(APPPATH.'third_party/file/mdb');
		if(empty($files)) :
			echo json_encode(array('status'=>0));
			return;
		else :
			if(count($files)!=2):
				delete_files(APPPATH.'third_party/file/mdb');
				echo json_encode(array('status'=>0));
				return;
			else :
				sort($files);
				$string = read_file(APPPATH.'third_party/file/mdb/0000.txt');
				$text = explode(';',$string);
				$file = $text[0];
				$name = $text[1];
				$date = $text[2];
				$time = $text[3];
				//$this->odbc_connect_db($file);
			endif;
		endif;
		
		echo json_encode(
			array(
				'status'=>1,
				'file'=>$file,
				'name'=>$name,
				'date'=>$date,
				'time'=>$time,
				'nmbl' =>$this->get_nmbl($file),
			)
		);
	}
	
	function odbc_connect_db($file) {
		$uname = explode(" ",php_uname());
		$os = $uname[0];
		switch ($os){
  			case 'Windows': $dsn = 'DRIVER={Microsoft Access Driver (*.mdb, *.accdb)};DBQ='.APPPATH.'third_party/file/mdb/'.$file; break;
  			case 'Linux': 	$dsn = 'DRIVER=MDBTools;DBQ='.APPPATH.'third_party/file/mdb/'.$file; break;
  			default: 'Windows';
    		echo json_encode(array('status'=>0));
		}
		$dbs=array('odbc_dsn'=>$dsn);
		$this->session->set_flashdata($dbs);
		//$con = odbc_connect($dsn, "", "admin");
		//return $con;
	}
	
	*/
	
	function get_nmbl($file) {
		$this->load->model('odbc_model');
		$data = $this->odbc_model->get_nmbl();
		$opt['']='';
		foreach($data as $val) {
			$opt[$val] = strtoupper($val);
		}
		return form_dropdown('mother_bl',$opt,'','id="no_master" onchange="get_data_header()" class="inject-manifest""');

	}
	
	function get_cont_seal() {
		$this->load->model('odbc_model');
		$q = $this->odbc_model->get_cont_seal($_GET['val']);
		echo json_encode($q);
	}
	
	function get_data_header() {
		$this->load->model('odbc_model');
		$q = $this->odbc_model->get_data_header($_GET['val']);
		//$q->tabel= $this->odbc_model->get_tables();
		//$q->kolom= $this->odbc_model->get_fields('tbldetail');
		echo json_encode($q);
	}
	
	function get_data_detail() {
		$this->load->model('odbc_model');
		if(isset($_GET['nodaftar'])!= '') {
			$hal = isset($_GET['page'])?$_GET['page']:1; 
			$batas = isset($_GET['rows'])?$_GET['rows']:10;
			$sidx = isset($_GET['sidx'])?$_GET['sidx']:'';
			$sord = isset($_GET['sord'])?$_GET['sord']:'';
				
			$start = $batas * $hal - $batas;
			$start = ($start<0)?0:$start;			
			$_GET['start'] = $start;
			
			/*
			$q = $this->odbc_model->get_detail($_GET['nodaftar']);
			
			/*
			$q = $this->odbc_model->get_query(array(
				//'nopos,nobl,mot_vessel,tglbl,pasalcd,pmuatcd,pbongkarcd,pakhircd,snm,sna,cnm,cna,nnm,nna,mark,bruto,volume,jmlkemasan,satuankemasan',
				'nopos,nobl,mot_vessel,tglbl,pasalcd,pmuatcd,pbongkarcd,pakhircd,snm,sna,cnm,cna,nnm,nna,mark',
				'tbldetail',
				'nodaftar',
				$_GET['nodaftar']
			));
			*/
			
			$q = $this->odbc_model->get_query("SELECT * FROM tbldetail WHERE nodaftar = '".$_GET['nodaftar']."' ORDER BY nopos ASC");
			$query = $q->result_array();
			$hitung = $this->odbc_model->get_num_rows($q);
			$data = new stdClass();
			$i=0;
			foreach($query as $row) {
				$data->rows[$i]['id']=$row['nopos'];
		    	$data->rows[$i]['cell']=array(
						$row['nopos'],
		    			$row['nobl'],
		    			$row['mot_vessel'],
		    			$row['tglbl'],
		    			$this->odbc_model->get_query("SELECT nmport FROM tblpelabuhan WHERE kdport = '".$row['pasalcd']."'")->result_object()[0]->nmport,
		    			$this->odbc_model->get_query("SELECT nmport FROM tblpelabuhan WHERE kdport = '".$row['pmuatcd']."'")->result_object()[0]->nmport,
		    			$this->odbc_model->get_query("SELECT nmport FROM tblpelabuhan WHERE kdport = '".$row['pbongkarcd']."'")->result_object()[0]->nmport,
						$this->odbc_model->get_query("SELECT nmport FROM tblpelabuhan WHERE kdport = '".$row['pakhircd']."'")->result_object()[0]->nmport,
		    			$row['snm'],
						$row['sna'],
						$row['cnm'],
						$row['cna'],
						$row['nnm'],
						$row['nna'],
						$row['mark'],
						$row['bruto'],
						$row['volume'],
						$row['jmlkemasan'],
						$this->odbc_model->get_query("SELECT UREDI FROM tblKemasan WHERE KDEDI = '".$row['satuankemasan']."'")->result_object()[0]->UREDI,
						$this->odbc_model->get_query("SELECT noseq FROM tblcontainer WHERE nodaftar = '".$row['nodaftar']."'")->result_object()[0]->noseq,
						$this->odbc_model->get_query("SELECT nocountener FROM tblcontainer WHERE nodaftar = '".$row['nodaftar']."'")->result_object()[0]->nocountener,
						$this->odbc_model->get_query("SELECT ukuran FROM tblcontainer WHERE nodaftar = '".$row['nodaftar']."'")->result_object()[0]->ukuran,
						$this->odbc_model->get_query("SELECT tipe FROM tblcontainer WHERE nodaftar = '".$row['nodaftar']."'")->result_object()[0]->tipe,
						$this->odbc_model->get_query("SELECT seal FROM tblcontainer WHERE nodaftar = '".$row['nodaftar']."'")->result_object()[0]->seal,
						$this->odbc_model->get_query("SELECT uraian FROM tblhs WHERE nodaftar = '".$row['nodaftar']."' AND nopos = '".$row['nopos']."'")->result_object()[0]->uraian,
						//	$row['flagconsolidate'],
						//	$row['flagpartial'],
						//	$row['grandcontainer'],
						//	$row['grandpackage'],
						//	$row['kpbc'],
						//	$row['pebno'],
						//	$row['pebtgl'],
						//	$row['tblGrup'],
						//	$row['urkdgrup'],
						//	$row['adadetil'],
						//	$row['inward'],
						//	$row['outward'],	    										    								
				);
				$i++;
			}
			
			if($hitung > 0) : $total_hal = ceil($hitung/$batas); else : $total_hal = 0; endif;
			if ($hal > $total_hal) $hal=$total_hal;
			
			$data->page = $hal;
			$data->total = $total_hal;
			$data->records = $hitung;
			
			echo json_encode($data);
		
		} else {
			echo json_encode(array());
		}
	}
	
	
}