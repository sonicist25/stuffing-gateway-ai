<?php
class C_proforma_invoice extends CI_Controller{
	
	var $title ='Proforma Invoice';
	
	public function __construct()
	{
		parent::__construct();
		$this->fungsi->restrict();
		$this->load->model(array('finance/m_proforma_invoice','finance/m_inv_to_shipper','afg_m_cities','finance/m_finance_proses','afg_m_job_sheet'));
		$this->dbx = $this->m_proforma_invoice->dbx;
		$this->fungsi->dbx = $this->m_proforma_invoice->dbx;
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
			$data["shipper_name"]=$this->m_proforma_invoice->get_x_proforma_detail(array($_POST["inv_no"],"shipper_name"));
			$data["shipper_address"]=$this->m_proforma_invoice->get_x_proforma_detail(array($_POST["inv_no"],"shipper_address"));
		} 
		$data['option']=$option;
		$data['proses']= $this->m_proforma_invoice->get_proses($_POST);
		$data['destination']= $this->get_destination($this->input->post('job_no'));
		$data['si_exportir']=$this->m_inv_to_shipper->si_exportir($this->input->post('job_no'));
		$data['seller_import']= $this->seller_import($this->input->post('job_no'));
		
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
	
		function seller_import($job)
	{
		$region = $this->session->userdata('region_id');
		$this->dbx->join('tbl_detail','tbl_detail.detail_id=trnstuffingplandetail.sp_detail_id');
		$this->dbx->where('trnstuffingplandetail.region_id',$region);
		$this->dbx->where('trnstuffingplandetail.kode_ref',$job);
		$q = $this->dbx->get('trnstuffingplandetail');
		if($q->num_rows()>0)
		{
			return $q->row()->no_from_shipper;
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
	
	private function get_awb_type_from_get()
	{
		$t = strtolower(trim((string)$this->input->get('awb_type')));
		return ($t === 'hawb') ? 'hawb' : 'mawb';
	}
	
	/**
	 * Tampilan PDF proforma invoice ini SENGAJA dibuat sama persis (layout,
	 * urutan field, ukuran/posisi) dengan invoice sungguhan
	 * (C_inv_to_shipper::invoice()), supaya yang disetujui customer di
	 * proforma terlihat identik dengan invoice yang nanti terbit. Bedanya
	 * hanya dua: (1) judul "PROFORMA INVOICE" bukan "INVOICE", dan
	 * (2) TIDAK ada badge Total Emisi CO2 (proforma bukan dokumen final,
	 * jadi info emisi belum relevan) - digantikan 2 baris disclaimer proforma
	 * di posisi yang sama.
	 */
	function print_invoice($kode_ref,$id,$pph)
	{
		$this->session->set_flashdata($_POST);
		header('HTTP/1.0 200 OK');
		header('HTTP/1.1 200 OK');
		header('Content-Type: application/pdf, UTF-8');
  		header('Content-Disposition: inline; filename="'.$this->title.'";');
  		header('Last-Modified: '.gmdate('D, d M Y H:i:s', time()).' GMT');

		$no_invoice   = $this->session->flashdata('no_invoice');
		$invoice_date = $this->session->flashdata('invoice_date');
		$currency     = $this->session->flashdata('currency');
		$si_shipper   = $this->session->flashdata('si_shipper')?? $this->session->flashdata('seller') ;
		$awb_type     = strtolower(trim((string)$this->session->flashdata('awb_type')));
		$awb_type     = ($awb_type === 'hawb') ? 'hawb' : 'mawb';
		$numb         = substr($no_invoice,0,3);

		$kepada = $this->m_proforma_invoice->get_x_proforma_detail(array($no_invoice,"shipper_name")). htmlentities("\n"). $this->m_proforma_invoice->get_x_proforma_detail(array($no_invoice,"shipper_address"));

		$query = $this->m_proforma_invoice->get_report_pdf($kode_ref);

		$data = ($pph == 'yes')
			? $this->m_proforma_invoice->get_form_inv_pph($id)
			: $this->m_proforma_invoice->get_form_inv($id);

		$logo='logo.png';
		$company = 'PT. GATEWAY CONTAINER LINE';

		$this->load->library('FPDF_MC_Table');
		$pdf=new FPDF_MC_Table();
		$pdf->AliasNbPages();
		// PENTING: AddPage() HARUS dipanggil SEBELUM setDataHeader(), persis seperti di
		// C_inv_to_shipper::invoice(). FPDF_MC_Table memanggil Header() otomatis di dalam
		// AddPage(); jika setDataHeader() sudah diisi sebelum AddPage() dipanggil, Header()
		// akan otomatis menggambar logo+alamat sendiri, lalu kode manual di bawah ini
		// menggambar logo+alamat yang SAMA lagi -> hasilnya dobel/tumpang tindih (bug yang
		// dilaporkan user). Dengan AddPage() dipanggil duluan (dataHeader masih kosong),
		// Header() tidak menggambar apa-apa untuk halaman pertama, dan setDataHeader() yang
		// dipanggil sesudahnya hanya menyiapkan letterhead otomatis untuk halaman lanjutan
		// (jika isi tabel meluap ke halaman berikutnya) - sama seperti invoice().
		$pdf->AddPage('P','invoice');
		$pdf->SetAutoPageBreak(true,10);

		$region = $this->session->userdata('region_id');

		if ($region=='Jakarta'){
			$pdf->setDataHeader(array('report'=>'invoice'));
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
		else if ($region=='Bandung')
		{
		$pdf->setDataHeader(array('report'=>'invoice_bandung'));
		$pdf->SetFont('verdana','',12);
		$pdf->SetXY(10, 13);
		$pdf->Cell(190,4, 'PT. GATEWAY CONTAINER LINE',0,1,'R',false,'');
		$pdf->SetFont('verdana','',8);
		$pdf->Cell(190,4,strtoupper('METRO TRADE CENTRE, JL SOEKARNO HATTA'),0,1,'R',false,'');
		$pdf->Cell(190,4,'BLOK D NO.21 SEKEJATI, KEC. BUAH BATU',0,1,'R',false,'');
		$pdf->Cell(190,4,'KOTA BANDUNG, JAWA BARAT 40286',0,1,'R',false,'');
		$pdf->Cell(190,4,'PHONE : (022) 7502283',0,1,'R',false,'');
		}
		else if ($region=='GCL WAREHOUSE')
		{
		$pdf->SetFont('verdana','',12);
		$pdf->SetXY(10, 13);
		$pdf->Cell(190,4, 'PT. GATEWAY CONTAINER LINE',0,1,'R',false,'');
		$pdf->SetFont('verdana','',8);
		$pdf->Cell(190,4,strtoupper('KAWASAN INDUSTRI DAN PERGUDANGAN'),0,1,'R',false,'');
		$pdf->Cell(190,4,'MARUNDA CENTER BLOK B-27',0,1,'R',false,'');
		$pdf->Cell(190,4,'KOTA BEKASI, JAWA BARAT 40286',0,1,'R',false,'');
		$pdf->Cell(190,4,'PHONE : (021) 89088309',0,1,'R',false,'');
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
		$pdf->SetXY(148, 17);
		$pdf->write(0,'THE HIVE TANAMERA LIPPO CIKARANG');
		$pdf->SetXY(137, 20);
		$pdf->write(0,'JL. WATERFRONT RIVER DRIVE NO. B10 (LT. 3)');
		$pdf->SetXY(149, 23);
		$pdf->write(0,'CIBATU, CIKARANG SELATAN, BEKASI');
		$pdf->SetXY(166.5, 26);
		$pdf->write(0,'PHONE :(021)89100449');
		$pdf->SetXY(170.5, 29);
		$pdf->write(0,'FAX :(021)89100449');
		$pdf->SetFont('verdana','',8);
		$pdf->SetXY(162, 32);
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
		$pdf->Cell(190,4,'PHONE :  ( 0411 ) 878658',0,1,'R',false,'');
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
		$pdf->Cell(190,4,'mgtblw@gateway-id.com',0,1,'R',false,'');
		}
		$pdf->Image(APPPATH.'third_party/img/'.$logo,10,13,+55);

		$pdf->SetFont('Arial','B',16);
		if ($numb == 'INR')
		{
			$pdf->SetXY(90, 45);
			$pdf->write(0,'PROFORMA REIMBURSEMENT ');
		}
		else {
			$pdf->SetXY(90, 45);
			$pdf->write(0,' PROFORMA INVOICE ');
		}
			/* $pdf->SetFont('verdana','',10);
			$pdf->SetXY(170, 45);
			$pdf->MultiCell(50,8,$no_invoice,0,0,false); */

			$pdf->Line(10,53,200,53);
			$pdf->Line(120,110,120,53);

			$pdf->SetFont('verdana','',8);
		$pdf->SetXY(10, 56);
		$pdf->write(0,'BILL TO :');
		$pdf->SetXY(10, 60);
		$pdf->MultiCell(105,3,$kepada);
		$pdf->SetXY(123, 56);
		$pdf->write(0,'REF NUMBER');
		$pdf->SetXY(149, 56);
		$pdf->write(0,':');
		$pdf->SetXY(151, 56);
		$pdf->write(0,$kode_ref);

		$pdf->SetXY(123, 60);
		$pdf->write(0,'DATE');
		$pdf->SetXY(149, 60);
		$pdf->write(0,':');
		$pdf->SetXY(151, 60);
		$pdf->write(0,$invoice_date);

		$pdf->SetXY(123, 64);
		$pdf->write(0,'TERM');
		$pdf->SetXY(149, 64);
		$pdf->write(0,':');
		$pdf->SetXY(151, 64);
		$pdf->write(0,$query['incoterms']);

		$pdf->SetXY(123, 68);
		$pdf->write(0,'PARTY');
		$pdf->SetXY(149, 68);
		$pdf->write(0,':');
		$pdf->SetXY(151, 68);
		$pdf->write(0,$query['no_pack_qty'].' '.strtoupper($query['no_pack_unit']));

		$pdf->SetXY(123, 72);
		$pdf->write(0,'GWT');
		$pdf->SetXY(149, 72);
		$pdf->write(0,':');
		$pdf->SetXY(151, 72);
		$pdf->write(0,$query['weight'].' '.'KGS');

		$pdf->SetXY(123, 76);
		$pdf->write(0,'CBM');
		$pdf->SetXY(149, 76);
		$pdf->write(0,':');
		$pdf->SetXY(151, 76);
		$pdf->write(0,$query['bl_meas'].' '.'M3');

		$pdf->Line(10,79,200,79);

		$job = substr($kode_ref,4,3);
		// Job 200 (AF export) / 600 (AF import) = mode Air Freight: label VESSEL jadi FLIGHT,
		// dan B/L NO+MBL NO diganti 1 baris HAWB atau MAWB (sesuai radio pilihan di editor).
		$is_af_job = ($job=='200'||$job=='600');

		$pdf->SetXY(10, 83);
		$pdf->write(0,$is_af_job ? 'FLIGHT' : 'VESSEL');
		$pdf->SetXY(27, 83);
		$pdf->write(0,':');
		$pdf->SetXY(29, 83);

		$detail = $this->afg_m_job_sheet->get_detail_job($query['sp_detail_id']);
		if($job=='300'||$job=='301'||$job=='302'||$job=='400'||$job=='402'||$job=='600'||$job=='900'||$job=='901'||$job=='902')
		{
			$pdf->write(0,$query['vessel'].' '.$query['voyage']);
		}
		else
		{
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
			$pdf->write(0,$vessel??$query['vessel'].' '.$query['voyage']);
		}

		$pdf->SetXY(10, 87);
		$pdf->write(0,'ETD/ETA');
		$pdf->SetXY(27, 87);
		$pdf->write(0,':');
		$pdf->SetXY(29, 87);
		$etd_jkt = $query['etd_jkt'];
		$eta = $query['eta'];
		$pdf->write(0,substr($etd_jkt,6,2).'-'. substr($etd_jkt,4,2).'-'. substr($etd_jkt,0,4).'/'.substr($eta,6,2).'-'. substr($eta,4,2).'-'. substr($eta,0,4));

		
		$awb_type = $this->get_awb_type_from_get();
		$region = $this->session->userdata('region_id');
		$destination = $this->afg_m_cities->get_city_id($query['destination']);
	
		if ($is_af_job) {
		if($job=='600')   {
		$pdf->SetXY(10, 91);
		$pdf->write(0,'POL');
		$pdf->SetXY(27, 91);
		$pdf->write(0,':');
		$pdf->SetXY(29, 91);
		$pdf->write(0,$query['destination']);
		$pdf->SetXY(10, 95);
		$pdf->write(0,'POD');
		$pdf->SetXY(27, 95);
		$pdf->write(0,':');
		$pdf->SetXY(29, 95);
		$pdf->write(0,$query['port_dest']);
		$pdf->SetXY(10, 99);
		$pdf->write(0,$awb_type=='hawb' ? 'HAWB' : 'MAWB');
		$pdf->SetXY(27, 99);
		$pdf->write(0,':');
		$pdf->SetXY(29, 99);
		$pdf->write(0,$awb_type=='hawb' ? strtoupper($query['hb_l']) : $query['mother_bl']);
		}
		else{
		$pdf->SetXY(10, 91);
		$pdf->write(0,'POL');
		$pdf->SetXY(27, 91);
		$pdf->write(0,':');
		$pdf->SetXY(29, 91);
		$pdf->write(0,$query['port_loading']);
		$pdf->SetXY(10, 95);
		$pdf->write(0,'POD');
		$pdf->SetXY(27, 95);
		$pdf->write(0,':');
		$pdf->SetXY(29, 95);
		$pdf->write(0,$query['destination']);
		$pdf->SetXY(10, 99);
		$pdf->write(0,$awb_type=='hawb' ? 'HAWB' : 'MAWB');
		$pdf->SetXY(27, 99);
		$pdf->write(0,':');
		$pdf->SetXY(29, 99);
		$pdf->write(0,$awb_type=='hawb' ? strtoupper($query['hb_l']) : $query['mother_bl']);
		}
		} else {
			$pdf->SetXY(10, 91);
			$pdf->write(0,'POL/POD');
			$pdf->SetXY(27, 91);
			$pdf->write(0,':');
			$pdf->SetXY(29, 91);
			if($job=='300'||$job=='301'||$job=='302'||$job=='400'||$job=='402'||$job=='900'||$job=='901'||$job=='902')   {
			$pdf->write(0,$query['destination'].' / '.$query['port_dest']);}
			else{
			$pdf->write(0,$query['port_loading'].' / '.$query['destination']);}
			$pdf->SetXY(10, 95);
			$pdf->write(0,'B/L NO');
			$pdf->SetXY(27, 95);
			$pdf->write(0,':');
			$pdf->SetXY(29, 95);

			$pdf->write(0,strtoupper($query['hb_l']));

			$pdf->SetXY(10, 99);
			$pdf->write(0,'MBL NO');
			$pdf->SetXY(27, 99);
			$pdf->write(0,':');
			$pdf->SetXY(29, 99);
			$pdf->write(0,$query['mother_bl']);
		}

		$pdf->Line(10,103,200,103);
		if($job=='300'||$job=='301'||$job=='302'||$job=='400'||$job=='402'||$job=='600'||$job=='900'||$job=='901'||$job=='902')  {
		$pdf->SetXY(123, 85);
		$pdf->write(0,'SUPPLIER');
		$pdf->SetXY(140, 85);
		$pdf->write(0,':');
		$pdf->SetXY(145, 83);
		$pdf->MultiCell(50, 4,$si_shipper,'0','L');
		} else {
		$pdf->SetXY(123, 83);
		$pdf->write(0,'SI NUMBER');
		$pdf->SetXY(143, 83);
		$pdf->write(0,':');
		$pdf->SetXY(148, 81);
		$pdf->MultiCell(50, 4,$si_shipper,'0','L');
		}
		$pdf->SetXY(36, 107);
		$pdf->write(0,'DETAIL OF CHARGES');
		$pdf->SetXY(96, 107);
		$pdf->write(0,'QUANTITY');
		$pdf->SetXY(127, 107);
		$pdf->write(0,'UNIT RATE');
		$pdf->SetXY(168, 107);
		$pdf->write(0,'AMOUNT');
		$pdf->Ln(3);

		$pdf->Ln();

		$pdf->Line(10,110,200,110);
		$pdf->Line(90,110,90,103);
		$pdf->Line(151,110,151,103);

		$j=1;
		if($data) {
			foreach ($data->result_array() as $row){
				$amount = round($row['price']);
				$codes = array('TAX10', 'TAX1', 'TAX 11', 'TAX 1.1', 'TAX 12', 'TAX 1.2');
				if (preg_match('/' . implode('|', array_map('preg_quote', $codes)) . '/', $row['code'])) {
					$item = 'VAT';
				}else{
					$item = $row['item'];
				}
				$pdf->Cell(80, 6,strtoupper($item),0,0,'L');
				$pdf->Cell(30, 6,$row['qty'],0,0,'C');
				$pdf->Cell(10, 6,$row['currency'],0,0,'L');
				$pdf->Cell(21, 6,$this->fungsi->pecah($row['unit_price'],','),0,0,'R');
				$pdf->Cell(13, 6,$row['currency'],0,0,'R');
				$pdf->Cell(36, 6,$this->fungsi->pecah($amount,','),0,0,'R');
				$pdf->Ln();
				$j++;
				if ($j>20)
				{ $pdf->AddPage();
			   $j=1;
				}
			}
		}

		if ($pph == 'yes'){
		$jumlah_idr = $this->m_proforma_invoice->get_ttl_inv_pph($id,'RP.');
		$jumlah_pph = round(array_sum($jumlah_idr) * 0.02);
		$jumlah_tax = $this->m_proforma_invoice->get_ttl_tax_pph($id,'RP.');
		$jumlah_ttl = round(array_sum($jumlah_idr)) - $jumlah_pph + round(array_sum($jumlah_tax));
		$jumlah_usd = $this->m_proforma_invoice->get_ttl_inv($id,'USD');
		$code_pajak = $this->m_proforma_invoice->get_code_pajak($id);

		$ttl_usd = '';
		$ttl_idr = '';

		if(!empty($jumlah_usd))
		$ttl_usd=$this->fungsi->pecah(array_sum($jumlah_usd),',');

		if(!empty($jumlah_idr))
		$ttl_idr=$this->fungsi->pecah(round(array_sum($jumlah_idr)),',');
		$ttl_tax=$this->fungsi->pecah(round(array_sum($jumlah_tax)),',');
		$ttl_pph=$this->fungsi->pecah(round($jumlah_pph),',');
		$ttl_titak=$this->fungsi->pecah(round($jumlah_ttl),',');
		$lineHeight = 8;

		$pdf->Line(10,213,200,213);
		$pdf->SetXY(120, 213);
		$pdf->Cell(40, $lineHeight, 'Sub Total:', 'B', 0, 'L');
		$pdf->Cell(40, $lineHeight, $ttl_idr, 'B', 1, 'R');

		$pdf->SetXY(120, 213 + $lineHeight);
		$pdf->Cell(40, $lineHeight, 'PPh23 (2%):', 'B', 0, 'L');
		$pdf->Cell(40, $lineHeight, $ttl_pph, 'B', 1, 'R');

		$pdf->SetXY(120, 213 + 2 * $lineHeight);
		$pdf->Cell(40, $lineHeight, 'VAT', 'B', 0, 'L');
		$pdf->Cell(40, $lineHeight, $ttl_tax, 'B', 1, 'R');

		$pdf->SetFont('verdana', 'B', 10);
		$pdf->SetXY(120, 213 + 3 * $lineHeight);
		$pdf->Cell(40, $lineHeight, 'Total:', 'B', 0, 'L');
		$pdf->Cell(40, $lineHeight, $ttl_titak, 'B', 1, 'R');
		$pdf->SetFont('verdana','',8);
		}else{
		$jumlah_idr = $this->m_proforma_invoice->get_ttl_inv($id,'RP.');
		$jumlah_usd = $this->m_proforma_invoice->get_ttl_inv($id,'USD');

		$ttl_usd = '';
		$ttl_idr = '';

		if(!empty($jumlah_usd))
		$ttl_usd=$this->fungsi->pecah(array_sum($jumlah_usd),',');

		if(!empty($jumlah_idr))
		$ttl_idr=$this->fungsi->pecah(round(array_sum($jumlah_idr)),',');

		$pdf->Line(10,235,200,235);
		$pdf->Line(151,245,151,235);
		$pdf->Line(200,245,151,245);
		$pdf->Line(200,280,135,280);
		if($ttl_usd!='') {
			$pdf->SetXY(153, 236);
			$pdf->MultiCell(12,4,'USD : ',0,'L',false);
			$pdf->SetXY(160, 236);
			$pdf->MultiCell(38,4, $ttl_usd,0,'R',false);
		}
		if($ttl_idr!='') {
			$pdf->SetXY(153, 240);
			$pdf->MultiCell(12,4,'RP. ',0,'L',false);
			$pdf->SetXY(160, 240);
			$pdf->MultiCell(38,4, $ttl_idr,0,'R',false);
		}
		}

		if($region =='Osline') {
		$pdf->SetXY(145, 250);
		$pdf->write(0,$company);
		$pdf->SetXY(155, 255);
		$pdf->write(0,'AS AGENT OSLINE');
		}else{
		$pdf->SetXY(145, 250);
		$pdf->write(0,$company);
		}
			// Tidak ada CO2 badge di proforma (lihat catatan di atas fungsi ini).
			// Disclaimer proforma ditaruh di baris kosong tepat di atas BANK
			// ACCOUNT DETAIL (y=247/250) - area ini selalu kosong di kedua
			// varian totals (pph23 yes/no), jadi tidak menabrak kotak totals.
			$pdf->SetFont('verdana','I',6);
			$pdf->SetXY(10, 247);
			$pdf->write(0,'* Ini adalah tagihan proforma/atau sementara, mohon tidak memotong PPH 23 berdasarkan proforma ini');
			$pdf->SetXY(10, 250);
			$pdf->write(0,'* Mohon melakukan pengecekan di bagian nama dan alamat, karena akan dijadikan acuan untuk pembuatan faktur pajak');

			$pdf->SetFont('verdana','',7);
			$pdf->SetXY(10, 263);
			$pdf->write(0,'BANK ACCOUNT DETAIL :');
			$pdf->SetXY(10, 267);
			$pdf->write(0,'IDR :');
			$pdf->SetXY(20, 267);
			$pdf->write(0,'PT. GATEWAY CONTAINER LINE');
			$pdf->SetXY(20, 271);
			$pdf->write(0,'MAYBANK INDONESIA' );
			$pdf->SetXY(20, 275);
			$pdf->write(0,'A/C NUMBER : 2082409727');
			$pdf->SetXY(148,277);
			$pdf->SetFont('verdana','',6);
			$pdf->write(0,'Created by : '.$this->session->userdata('fullname'));

		ob_clean();
		$pdf->Output($no_invoice.'.pdf','I');
	}

	function tarik_data_invoice()
	{
		if($_POST['invoice_db'] == '5'){
		$data = $this->m_proforma_invoice->tarik_data_proforma_pajak($_POST);
		}else{
		$data = $this->m_proforma_invoice->tarik_data_invoice($_POST);
		}
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