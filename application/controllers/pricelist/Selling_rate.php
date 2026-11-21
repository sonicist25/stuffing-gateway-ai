<?php
class Selling_rate extends CI_Controller
{
	function __construct(){
		parent::__construct();
		$this->load->library('fungsi');
		$this->fungsi->restrict();
		$this->load->model(array(
			'user_model',
			'afg_m_cities',
			'pricelist/selling_rate_model',
			'reference/type_container_model'
		));
		$this->fungsi->dbx = $this->selling_rate_model->dbx;
	}
	
	function copy_data()
	{
		$data = $this->selling_rate_model->copy_data($_POST);
		echo json_encode($data);
	}
	
	function insert_data() 
	{
		$data = $this->selling_rate_model->insert_data($_POST);
		echo json_encode($data);
	}
	
	function saving_data()
	{
		$data = $this->selling_rate_model->saving_data($_POST);
		echo json_encode($data);
	}
	
	function index(){
		$this->fungsi->auth(24,'Selling Rate');
		$data = array('menu_navigation' => 'navigation', 'main_view' => 'pricelist/v_selling_rate',
				'title' => 'Selling Rate','table_title' => 'Selling Rate to Shipper / Exportir',
				'rowList'=>'',
		);
		$this->load->view('afg_template', $data);
	}
	
	function get_selling() 
	{
		$post = array(
			'user_id' => $this->session->userdata('user_id'),
			'shipper_id' => $this->input->post('shipper_id'),
			'id'		=> $this->input->post('idSelling'),
			'mode'		=> $this->input->post('mode'),
		);	
		$data = $this->selling_rate_model->get_selling($post);
		echo json_encode($data);
	}
	
	function open_form_selling()
	{
		$data['shipper_id'] =  $this->input->post('shipper_id');
		$data['shipper_name'] =  $this->input->post('shipper_name');
		$data['id_selling'] = $this->input->post('id_selling');
		$data['sales'] =  $this->input->post('sales');
		$this->load->view('pricelist/v_selling_rate_shipper', $data);
	}
	
		function open_form_quotation() 
	{
		$data = $_POST;
		$this->load->view('pricelist/v_quotation', $data);
	}
	
	function get_selling_rate()
	{
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
	 	$sord = isset($_POST['sord'])?$_POST['sord']:'';	 
	 			 	
		$start = $batas * $hal - $batas;
		$start = ($start<0)?0:$start;
		$_POST['start'] = $start;
				
		// Sebelumnya get_selling_rate() dipanggil 2x penuh (dengan GROUP_CONCAT,
		// tanpa limit) hanya untuk menghitung jumlah baris. Sekarang pakai
		// count_selling_rate() yang jauh lebih ringan.
		$hitung = $this->selling_rate_model->count_selling_rate($_POST);
		$query  = $this->selling_rate_model->get_selling_rate($_POST,true);
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
		$i = 0;
		foreach($query as $row) {
			$data->rows[$i]['id'] = $row->id_shipper;
			$data->rows[$i]['cell'] = array(
				$row->shipper_id,
				$row->shipper_name,
				$row->sales_names, // Diambil langsung dari hasil JOIN model
				isset($row->valid_terdekat) ? $row->valid_terdekat : '',
			);
			$i++;
		}
		echo json_encode($data);
	}
	
	function get_table_selling(){
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
	   $sord = isset($_POST['sord'])?$_POST['sord']:'';	   	   
		$start = $batas * $hal - $batas;
		$start = ($start<0)?0:$start;
		$_POST['start'] = $start; 		
		// Sebelumnya get_table_selling() dipanggil 2x penuh hanya untuk hitung
		// jumlah baris. Sekarang pakai count_table_selling() (COUNT saja).
		$hitung = $this->selling_rate_model->count_table_selling($_POST);
		$query  = $this->selling_rate_model->get_table_selling($_POST,true);
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
												$this->get_value_emkl($row->id_selling),		    
		    									//$row->of_emkl,
		    									$row->nama_service,
		    									$row->kondisi,
												$row->set_m3,
												$row->destination,
												$row->location,
												$row->type,
												$row->container,
												$row->currency,
												$row->sales_comission,
												$row->discount,
												$row->price,
												$row->base_rate,
												$row->mchgs,
												$row->remarks,
												$row->rate_rgl,
												$row->approval,
											);
		    $i++;
		}
		echo json_encode($data);
	}
	
	function get_value_emkl($id)
	{
		$value = $this->selling_rate_model->get_value_emkl($id);
		return $value;
	}
	
	function get_table_listing(){
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
		$sord = isset($_POST['sord'])?$_POST['sord']:'';
				
		$start = $batas * $hal - $batas;
		$start = ($start<0)?0:$start;			
		$_POST['start'] = $start;
					
		// BUG LAMA: $hitung = count(array($q)) selalu bernilai 1 (membungkus
		// result set di dalam array() baru menghasilkan array 1 elemen), jadi
		// total halaman paginasi sebenarnya selalu salah. Diperbaiki pakai
		// count_table_listing() yang menghitung baris sungguhan.
		$hitung = $this->selling_rate_model->count_table_listing($_POST);
		$query  = $this->selling_rate_model->get_table_listing($_POST,true);
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
		    $data->rows[$i]['id']=$row->id_selling;
		    // PENTING: urutan array ini HARUS persis sama dengan urutan colModel
		    // di table_listing() (v_selling_rate_script.php):
		    // id_selling, kode_shipper, ref_no, tanggal, valid, kepada, attn,
		    // of_emkl, dari, remarks, valid_raw. jqGrid mencocokkan cell[i] ke
		    // colModel[i] berdasarkan POSISI, bukan nama -- kalau urutan di
		    // sini beda dengan colModel, semua kolom akan tampil geser/tertukar.
		    $data->rows[$i]['cell']=array(
		    									$row->id_selling,
												$row->kode_shipper,
												$row->ref_no,
		    									$this->fungsi->jin_date_str($row->tanggal),
												$this->fungsi->jin_date_str($row->valid),
												$row->kepada,
												$row->attn,
												$row->of_emkl,
												$this->user_model->get_user_by_id($row->dari),
												$row->remarks,
												$row->valid, // mentah (Ymd), khusus buat highlight H-7 di JS
											);
		    $i++;
		}
		echo json_encode($data);
	}
	
	// Sumber data autocomplete pencarian customer BARU di grid utama $("#table")
	// (v_selling_rate.php) -- dipakai untuk mulai bikin selling rate pertama
	// buat customer yang belum punya data valid (makanya belum muncul di list).
	function dropdown_shipper()
	{
		$term = $this->input->post('term');
		if ($term === null) $term = $this->input->get('term');
		$result = $this->selling_rate_model->dropdown_shipper($term);
		echo json_encode($result);
	}

	// Dipanggil saat form "Tambah Data Selling" di grid #table-listing dibuka
	// (di DALAM modal customer yang sudah pasti/terkunci -- lihat #shipperId).
	// Kembalikan: nama shipper resmi, UP (dari cp_name + cp_email mstshipper),
	// dan ref_no baru yang unik -- semua dalam satu round-trip.
	function get_shipper_context()
	{
		$shipper_id = $this->input->post('shipper_id'); // kode shipper, mis. "OSLINE"
		$shipper = $this->selling_rate_model->get_shipper_by_code($shipper_id);

		if (!$shipper) {
			echo json_encode(array('status' => 0));
			return;
		}

		$attn = trim($shipper->cp_name);
		if (!empty($shipper->cp_email)) {
			$attn .= ' ('.$shipper->cp_email.')';
		}

		echo json_encode(array(
			'status'       => 1,
			'shipper_id'   => $shipper->shipper_id,
			'shipper_name' => $shipper->shipper_name,
			'attn'         => $attn,
			'ref_no'       => $this->selling_rate_model->generate_unique_ref_no(),
		));
	}

	function dropdownCityId() {
		$countries = $this->selling_rate_model->getListCitiesId()->result();
		$data['option_cityid']['']=' ';
		foreach ($countries as $row)
		{	
			$data['option_cityid'][$row->city_id]=$row->city_name;
		}		
		$country = form_dropdown('dropdownCityId',$data['option_cityid'],'','id="dropdownCityId" style="width: 110px;"');		
		echo $country;
	}
	
	function dropdownCities(){
		$cities = $this->afg_m_cities->getListCitiesDrop()->result();
		$data['option_cities']['']=' ';
		foreach ($cities as $row)
		{	
			$data['option_cities'][$row->city_name]=$row->city_name;
		}		
		echo form_dropdown('dropdownCities',$data['option_cities'],'','id="dropdownCities"');
	}
	
	function dropdownContainer(){
		$agent = $this->type_container_model->getListContainer()->result();
		$data['option_container']['']=' ';
		foreach ($agent as $row)
		{	
			$data['option_container'][$row->typecontainer_name]=$row->typecontainer_name;
		}		
		echo form_dropdown('dropdownContainer',$data['option_container'],'','id="dropdownContainer"');
	}
		
	function manage_data_selling(){
		$mode = $this->input->post('oper');
		$id = $this->input->post('id');
		
		// Tangkap input mentah dari form jqGrid (contoh: 28/01/2025)
		$tanggal_raw = $this->input->post('tanggal');
		$valid_raw   = $this->input->post('valid');

		// FIX: Ganti garis miring (/) menjadi strip (-) agar strtotime PHP 
		// tidak salah mengiranya sebagai format Amerika (Bulan/Hari/Tahun)
		$tanggal_clean = str_replace('/', '-', $tanggal_raw);
		$valid_clean   = str_replace('/', '-', $valid_raw);

		// Parse ke format YYYYMMDD (tanpa strip)
		$tanggal_ymd = !empty($tanggal_clean) ? date('Ymd', strtotime($tanggal_clean)) : '';
		$valid_ymd   = !empty($valid_clean) ? date('Ymd', strtotime($valid_clean)) : '';

		$data = array (
			'of_emkl'      => $this->input->post('of_emkl'),
			'kode_shipper' => $this->input->post('kode_shipper'),
			'ref_no'       => $this->input->post('ref_no'),
			'tanggal'      => $tanggal_ymd, // Akan masuk ke database sbg 20250128
			'valid'        => $valid_ymd,   // Akan masuk ke database sbg 20260831
			'kepada'       => $this->input->post('kepada'),
			'attn'         => $this->input->post('attn'),
			'peti_kemas'   => $this->input->post('peti_kemas'),			
			'remarks'      => $this->input->post('remarks'),
			'dari'         => $this->input->post('dari'), 
			'region_id'    => $this->session->userdata('region_id'),
		);
		
		if($mode == 'add')
		{
			$status = $this->selling_rate_model->add_data_selling($data);
			echo json_encode(array('status'=>$status,'mode'=>'add'));
		}
		else if($mode == 'edit')
		{
			$status = $this->selling_rate_model->update_data_selling($data,$id);
			echo json_encode(array('status'=>$status,'mode'=>'edit'));
		}	
		else if($mode == 'del')
		{
			$status = $this->selling_rate_model->delete_data_selling($id);
			echo json_encode(array('status'=>$status,'mode'=>'delete'));
		}	
	}
	
	// ==========================================================
	// FUNGSI UNTUK DROPDOWN SALES JQGRID (VIA AJAX JSON)
	// ==========================================================
	public function get_sales_options_json() {
		// PENTING: sebelumnya fungsi ini pakai $this->db (koneksi default
		// CodeIgniter), padahal SEMUA query lain ke a_selling/mstshipper di
		// seluruh aplikasi ini (termasuk yang berhasil resolve nama/UP
		// customer) pakai $this->selling_rate_model->dbx -- koneksi yang
		// berpindah sesuai "DB Year" yang sedang dipilih user. Kalau
		// keduanya menunjuk database yang berbeda, data yang baru diinput
		// di DB Year aktif tidak akan pernah "terlihat" oleh $this->db.
		// Disamakan di sini supaya konsisten dengan seluruh query lain.
		$dbx = $this->selling_rate_model->dbx;

		$id_selling         = $this->input->post('id_selling');
		// Fallback untuk mode "Add" -- saat itu id_selling belum ada sama
		// sekali (record baru), tapi kode_shipper customer yang sedang aktif
		// sudah diketahui dari context (#shipperId di front-end).
		$kode_shipper_input = $this->input->post('kode_shipper');
		$result = array();
		$kode_shipper = null;

		if ($id_selling && $id_selling != '_empty') {
			// 1. Cari kode_shipper dari a_selling (perilaku LAMA, tidak diubah)
			$dbx->select('kode_shipper');
			$selling = $dbx->get_where('a_selling', ['id_selling' => $id_selling])->row();
			if ($selling) {
				$kode_shipper = $selling->kode_shipper;
			}
		} elseif (!empty($kode_shipper_input)) {
			// 1b. Mode Add: belum ada id_selling, langsung pakai kode_shipper
			// yang dikirim dari front-end.
			$kode_shipper = $kode_shipper_input;
		}

		if ($kode_shipper) {
			// 2. Cari list ID sales dari mstshipper
			$dbx->select('sales');
			$shipper = $dbx->get_where('mstshipper', ['shipper_id' => $kode_shipper])->row();

			if ($shipper && !empty($shipper->sales)) {
				$sales_ids = explode(',', $shipper->sales);
				
				// 3. Cari fullname dari mstadmin
				$dbx->select('user_id, fullname');
				$dbx->where_in('user_id', $sales_ids);
				$result = $dbx->get('mstadmin')->result_array();
			}
		}
		
		$this->output->set_content_type('application/json')->set_output(json_encode($result));
	}
	
	function print_data(){
		$this->load->library('FPDF_MC_Table');
		$pdf=new FPDF_MC_Table();	
		$pdf->AliasNbPages();		
		$pdf->setDataHeader(array('report'=>'selling_rate'));
		$pdf->AddPage('P','A4');
		$pdf->SetMargins(10, 38, 0);
		if($this->session->userdata('region_id')=='Osline') {
			$logo = 'osline.png';
			$pt = 'OCEAN SKY LINE';
			$add = 'GCL BUILDING, 1ST FLOOR
				Jl OTISTA III NO. 18
				JAKARTA TIMUR 13340 - INDONESIA
				PHONE (62) 21 21012840';
		} else {
			$logo = 'logo.png';
			$pt = 'PT GATEWAY CONTAINER LINE';
			$add = 'JL. OTISTA III RT. 001 RW. 001 NO. 18
						CIPINANG CEMPEDAK JATINEGARA
						JAKARTA TIMUR 13340
						PHONE : ( 62-21 ) 8591-8669 ( HUNTING )
						FAX : ( 62-21 ) 8591-8664';	
		}
	
	
		
		$pdf->SetFont('Arial','B',10);
		$pdf->SetXY(130, 28);
		$pdf->Cell(70, 6, $pt,0,1,'R',false);
		
		/*
		$pdf->SetXY(137, 17);
		$pdf->write(0,$add1);
		$pdf->SetXY(158, 21);
		$pdf->write(0,'Jl OTISTA III NO. 18');
		$pdf->SetXY(166, 25);
		$pdf->write(0,'JAKARTA TIMUR 13340 - INDONESIA');
		$pdf->SetXY(143, 29);
		$pdf->write(0,'PHONE : ( 62-21 ) 8591-8669 ( HUNTING )');
		$pdf->SetXY(165, 33);
		$pdf->write(0,'FAX : ( 62-21 ) 8591-8664');
		$pdf->SetXY(165, 36);
		$pdf->write(0,'general@gateway-id.com');
		*/	
		
		
		$pdf->SetFont('Arial','',8);
		
		$pdf->SetXY(10, 51);
		$pdf->write(0,'DATE');
		$pdf->SetXY(25, 51);
		$pdf->write(0,':');
		$pdf->SetXY(30, 51);
		$pdf->write(0,$_GET['date']);
		
		$pdf->SetXY(10, 56);
		$pdf->write(0,'FROM');
		$pdf->SetXY(25, 56);
		$pdf->write(0,':');
		$pdf->SetXY(30, 56);
		
		$from = $_GET['from'];
		$pdf->write(0,$this->fungsi->get_user_by_id($from));
		
		$pdf->SetXY(10, 61);
		$pdf->write(0,'ATTN');
		$pdf->SetXY(25, 61);
		$pdf->write(0,':');
		$pdf->SetXY(30, 61);
		$pdf->write(0,$_GET['attn']);
		
		$pdf->SetXY(10, 67);
		$pdf->write(0,'VALID');
		$pdf->SetXY(25, 67);
		$pdf->write(0,':');
		$pdf->SetXY(30, 67);
		//$tglawal = substr($date,6,2).'-'. substr($date,4,2).'-'. substr($date,0,4);
		$pdf->write(0,$_GET['valid']);
		
		$pdf->SetFont('Arial','BI',10);
		$pdf->SetXY(10, 39);
		$pdf->MultiCell(190,6,'FREIGHTS QUATATION '.$_GET['to'],0,'C',false);
		
		
		$pdf->SetFont('Arial','',9);
		$pdf->SetXY(10, 80);
		$pdf->write(0,'Thank you for giving us the apportunity to quote our best rates as shown below :');
		/*
		$pdf->SetFont('Arial','B',8);
		$pdf->SetFillColor(230,230,230);
		$pdf->SetTextColor(100,100,100);
		$pdf->SetLineWidth(0.1);
		
		$pdf->SetXY(10, 87);
		$pdf->MultiCell(50,6,'Type of Service',1,'C',true);
		$pdf->SetXY(60, 87);
		$pdf->MultiCell(10,6,'Ctnr',1,'C',true);
		$pdf->SetXY(70, 87);
		$pdf->MultiCell(10,6,'Type',1,'C',true);
		$pdf->SetXY(80, 87);
		$pdf->MultiCell(32,6,'Destination',1,'C',true);
		$pdf->SetXY(112, 87);
		$pdf->MultiCell(48,6,'Currency and Rates',1,'C',true);
		$pdf->SetXY(160, 87);
		$pdf->MultiCell(40,6,'Condition',1,'C',true);
		
		$shipper = $_GET['shipper'];
		
		$pdf->SetTextColor(10,10,10);
		$pdf->SetFont('Arial','',8);

		$post = $this->session->flashdata('shipper');		
		$result=$this->selling_rate_model->print_data($shipper)->result();
		
		foreach ($result as $row ) {
			$pdf->Cell(50, 6, $row->nama_service,'B',0,'L',false);
			$pdf->Cell(10, 6, $row->container,'B',0,'L',false);
			$pdf->Cell(10, 6, $row->type,'B',0,'L',false);
			$pdf->Cell(32, 6, $row->destination,'B',0,'L',false);
			$pdf->Cell(18, 6, $row->currency,'B',0,'C',false);
			$pdf->Cell(25, 6, $this->fungsi->pecah($row->price,','),'B',0,'R',false);
			$pdf->Cell(44, 6, $row->kondisi,'B',0,'C',false);
			$pdf->Ln();
		}
		$pdf->Cell(190,1,'','LBR',0,'C',true);
		
		$pdf->Ln(7);
		$result2=$this->selling_rate_model->print_data_misc($shipper)->result();
		
		foreach ($result2 as $row ) {
			$pdf->Cell(50, 6, $row->nama_service,'B',0,'L',false);
			$pdf->Cell(10, 6, $row->container,'B',0,'L',false);
			$pdf->Cell(10, 6, $row->type,'B',0,'L',false);
			$pdf->Cell(32, 6, $row->destination,'B',0,'L',false);
			$pdf->Cell(18, 6, $row->currency,'B',0,'C',false);
			$pdf->Cell(25, 6, $this->fungsi->pecah($row->price,','),'B',0,'R',false);
			$pdf->Cell(44, 6, $row->kondisi,'B',0,'C',false);
			$pdf->Ln();
		}
		
		*/
		$shipper = $_GET['to'];
		$headerselling = array('Type of Service', 'Ctnr', 'Type', 'Destination','Currency and Rates','Condition');
		$dataselling = $this->selling_rate_model->print_data($shipper)->result();
		//print_r($this->dbx->last_query());
		$pdf->SetXY(10, 87);
		$pdf->FancyTable3($headerselling,$dataselling);
		//$pdf->Cell(190,1,'','LBR',0,'C',true);
		$pdf->Ln(15);
		$pdf->SetFont('Arial','B',9);
		$pdf->write(0,'REMARKS : ');	
		$pdf->Ln(7);
		$pdf->SetFont('Arial','',8);
		$result2=$this->selling_rate_model->print_data_misc($shipper)->result();
		foreach ($result2 as $row ) {
			$pdf->Cell(30, 6, $row->nama_service,'B',0,'L',false);
			$pdf->Cell(10, 6, $row->container,'B',0,'L',false);
			$pdf->Cell(10, 6, $row->type,'B',0,'L',false);
			$pdf->Cell(32, 6, $row->destination,'B',0,'L',false);
			$pdf->Cell(25, 6, $row->currency." ".$this->fungsi->pecah($row->price,','),'B',0,'R',false);
			$pdf->Ln();
		}
		$pdf->Ln(15);	
		$pdf->write(0,'Should you require any further information, please feel free to contact us.');
		$pdf->Ln(5);
		$pdf->write(0,'Kind Regards,');
		$pdf->Ln(20);	
		$pdf->write(0,$this->fungsi->get_user_by_id($from));
		
		
		$pdf->Output('Selling-Rate-To-'.$_GET['to'],'I'); 
	}

	function manage_data(){
		$result = $this->selling_rate_model->manage_data($_POST);
		$result['mode'] = isset($_POST['oper']) ? $_POST['oper'] : '';
		echo json_encode($result);
	}

}