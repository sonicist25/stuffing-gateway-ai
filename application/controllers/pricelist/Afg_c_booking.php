<?php
class Afg_c_booking extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->library('fungsi');
		$this->fungsi->restrict();
		$this->load->model(array('pricelist/booking_model','pricelist/quotation_model','user_model','reference/stuffing_location_model','afg_m_detail','reference/consignee_notify_model','afg_m_cities','afg_m_job_sheet','afg_m_ref'));
	}
	
	function index() 
	{
		//$this->fungsi->auth(60,'Harga Jual');
		$data = array( 'menu_navigation' => 'navigation', 
							'main_view' => 'pricelist/v_harga_jual_form',
							'title' => 'Harga Jual',
							'rowList'=>'',
		);
		$this->load->view('afg_template', $data);
	}
	
	function open_form() 
	{
		$data = '';
		$this->load->view('pricelist/v_harga_jual_form', $data);
	}
	
	function cetak_form() 
	{
		$this->load->library(array('fpdf', 'fungsi'));
		$this->fpdf->FPDF('P','mm','A4');
		$this->fpdf->AddPage();
		$this->fpdf->SetFont('courier','',8);
		$this->fpdf->Output('HARGA JUAL '. date('d-M-Y'),'I');
	}
	
function cetak_bc() 
{  
	require_once("pdf/phpqrcode/qrlib.php");
	
	$si = $this->input->get('booking_number');
	$this->load->library('FPDF_MC_Table');
	$pdf = new FPDF_MC_Table();       
	$pdf->AddPage('P','A4');
	$pdf->AliasNbPages();
	$pdf->SetAutoPageBreak(false);

	$wh = $this->booking_model->get_warehouse_detail($_GET['warehouse']);
	
	// --- 1. CETAK LOGO (Desain.png) ---
	if(file_exists(APPPATH.'third_party/img/Desain.png')){
		$pdf->Image(APPPATH.'third_party/img/Desain.png', 155, 12, 45);
	}

	$pdf->AddFont('calibri','B','Calibribold.php');
	$pdf->AddFont('calibri','','Calibri.php');

	// --- 2. JUDUL ---
	$pdf->SetFont('calibri', 'B', 16);
	$pdf->SetXY(55, 18);
	$pdf->Cell(100, 10, 'BOOKING CONFIRMATION', 0, 0, 'C');

	// --- 3. GAMBAR GARIS & KOTAK FORM VIA FPDF ---
	$pdf->SetLineWidth(0.3);
	$pdf->SetDrawColor(0, 0, 0);
	
	// Kotak Terluar (X=10, Y=34, W=190, H=241)
	$pdf->Rect(10, 34, 190, 241); 

	// Garis Horizontal Utama Pembagi Blok
	$pdf->Line(10, 40, 200, 40); // Bawah BL No Header
	//$pdf->Line(10, 65, 200, 65); // Bawah SI Number (Batas Atas Shipment Details)
	$pdf->Line(10, 70, 200, 70); // Bawah Header "SHIPMENT DETAILS"
	$pdf->Line(10, 120, 200, 120); // Bawah Shipper/Consignee
	$pdf->Line(10, 147, 200, 147); // Bawah Notify/Vessel dll
	$pdf->Line(10, 155, 200, 155); // Bawah BL, Party, Weight
	$pdf->Line(10, 163, 200, 163); // Bawah Freight & Term
	$pdf->Line(10, 220, 200, 220); // Bawah Marking & Description
	$pdf->Line(10, 258, 200, 258); // Bawah Warehouse & Consolidator
	$pdf->Line(10, 263, 200, 263); // Bawah ROUTING INFO Header
	$pdf->Line(10, 268, 200, 268); // Bawah kolom ROUTING INFO

	// Garis Horizontal Spesifik (Sub-Row)
	// 1. Top Section (3 Kolom)
	$pdf->Line(10, 45, 130, 45);
	$pdf->Line(10, 50, 130, 50);
	$pdf->Line(10, 55, 130, 55);
	$pdf->Line(10, 60, 130, 60);
	
	// --- Garis Horizontal untuk kolom Extra (Arrive, Type, HS, SI) ---
	$pdf->Line(85, 45, 200, 45); // bawah Arrive CFS at
	$pdf->Line(85, 50, 200, 50); // bawah TYPE CARGO
	$pdf->Line(85, 55, 200, 55); // bawah HS CODE
	$pdf->Line(85, 60, 200, 60); // bawah SI NUMBER

	// 2. Shipper/Consignee Label Line
	$pdf->Line(10, 75, 200, 75);

	// 3. Notify Label Line
	$pdf->Line(10, 125, 105, 125);

	// 4. Vessel/Voyage Sub-Rows
	$pdf->Line(105, 125, 200, 125); // Label Vessel
	$pdf->Line(105, 129, 200, 129); // Value Vessel / Label ETD
	$pdf->Line(105, 133, 200, 133); // Label ETD
	$pdf->Line(105, 138, 200, 138); // Value ETD / Label POL
	$pdf->Line(105, 142, 200, 142); // Label POL

	// 5. BL / Party / Weight / Vol Label Line
	$pdf->Line(10, 151, 200, 151);

	// 6. Freight / Term Label Line
	$pdf->Line(10, 159, 200, 159);

	// 7. Marking / Desc Label Line
	$pdf->Line(10, 168, 200, 168);

	// 8. Warehouse / Consolidator Label Line
	$pdf->Line(10, 225, 200, 225);

	// Garis Vertikal Utama & Spesifik
	$pdf->Line(130, 34, 130, 60); // Pembagi Top Left (3 Kolom) & Top Right (BL No)
	
	// Top Section Columns (Kolom Label | Kolom Value | Kolom Extra)
	$pdf->Line(45, 40, 45, 60); 
	$pdf->Line(85, 40, 85, 60);  

	// Garis Tengah (X=105) untuk Shipper s/d Warehouse
	// (diputus di baris Freight/Term 155-163 karena baris itu punya kolom sendiri)
	$pdf->Line(105, 70, 105, 155);
	$pdf->Line(105, 163, 105, 258);

	// Vertikal Vessel / Connecting
	$pdf->Line(150, 120, 150, 147);

	// Vertikal BL / Party / Weight dll
	$pdf->Line(75, 147, 75, 155);
	$pdf->Line(135, 147, 135, 155);
	$pdf->Line(165, 147, 165, 155); 
	
	// --- 4. CETAK LABEL-LABEL & VALUE FORM ---

	// BL NO HEADER
	$pdf->SetFont('calibri', 'B', 7);
	$pdf->SetXY(105, 35); $pdf->Cell(23, 4, 'BILL OF LADING No.', 0, 0, 'R');
	$pdf->SetFont('calibri', 'B', 12);
	$pdf->SetXY(133, 35); $pdf->Cell(65, 4, $_GET['house_bl'], 0, 0, 'C');

	// TOP SECTION (3 KOLOM)
	$pdf->SetFont('calibri', 'B', 7);
	$pdf->SetXY(11, 41); $pdf->Cell(33, 4, 'SHIPMENT TYPE', 0, 0, 'L');
	$pdf->SetXY(11, 46); $pdf->Cell(33, 4, 'BOOKING NUMBER', 0, 0, 'L');
	$pdf->SetXY(11, 51); $pdf->Cell(33, 4, 'STUFFING DATE', 0, 0, 'L');
	$pdf->SetXY(11, 56); $pdf->Cell(33, 4, 'CLOSING DATE', 0, 0, 'L');
	

	$pdf->SetFont('calibri', '', 9);
	$pdf->SetXY(46, 41); $pdf->Cell(38, 4, $_GET['type'], 0, 0, 'L');
	$pdf->SetXY(46, 46); $pdf->Cell(38, 4, $_GET['booking_number'], 0, 0, 'L');
	
	if (!empty($_GET['stuffing_date'])) {
		$pdf->SetXY(46, 51); $pdf->Cell(38, 4, date('Y/m/d', strtotime($_GET['stuffing_date'])), 0, 0, 'L');
	}
	if (!empty($_GET['closing_date'])) {
		$pdf->SetXY(46, 56); $pdf->Cell(38, 4, date('Y/m/d', strtotime($_GET['closing_date'])), 0, 0, 'L');
	}
	

	// Extra Kolom 3 (Arrive CFS at, Type, HS)
	$pdf->SetFont('calibri', 'B', 8);
	$pickup_date = (!empty($_GET['wh_arrival']) && $_GET['wh_arrival'] != '0000-00-00 00:00:00') ? date('Y/m/d', strtotime($_GET['wh_arrival'])) : '-';
	$cargo_type = !empty($_GET['cargo_type']) ? strtoupper($_GET['cargo_type']) : 'GENERALCARGO';
	if ($cargo_type === 'DG' && !empty($_GET['dg_class'])) {
		$cargo_type .= ' (' . strtoupper($_GET['dg_class']) . ')';
	}
	$hs_code = !empty($_GET['hs_code']) ? strtoupper($_GET['hs_code']) : '-';

	$pdf->SetXY(86, 41); $pdf->Cell(43, 4, 'Arrive CFS at ', 0, 0, 'L');
	$pdf->SetXY(86, 46); $pdf->Cell(43, 4, 'TYPE CARGO ', 0, 0, 'L');
	$pdf->SetXY(86, 51); $pdf->Cell(43, 4, 'HS CODE ' , 0, 0, 'L');
	$pdf->SetXY(86, 56); $pdf->Cell(43, 4, 'SI NUMBER', 0, 0, 'L');
	// --- Value Kolom Extra ---
$pdf->SetFont('calibri', '', 9);
$pdf->SetXY(136, 41); $pdf->Cell(38, 4, $pickup_date, 0, 0, 'L');
$pdf->SetXY(136, 46); $pdf->Cell(38, 4, $cargo_type, 0, 0, 'L');
$pdf->SetXY(136, 51); $pdf->Cell(38, 4, $hs_code, 0, 0, 'L');
$pdf->SetXY(136, 56); $pdf->Cell(38, 4, urldecode($_GET['si']), 0, 0, 'L');

	// SHIPMENT DETAILS
	$pdf->SetFont('calibri', 'B', 12);
	$pdf->SetXY(80, 64); $pdf->Cell(50, 4, 'SHIPMENT DETAILS', 0, 0, 'C');

	// SHIPPER & CONSIGNEE
	$pdf->SetFont('calibri', 'B', 8);
	$pdf->SetXY(11, 71); $pdf->Cell(30, 4, 'SHIPPER', 0, 0, 'L');
	$pdf->SetXY(106, 71); $pdf->Cell(30, 4, 'CONSIGNEE', 0, 0, 'L');

	$pdf->SetFont('calibri', '', 10);
	$pdf->SetXY(12, 76);
	$pdf->MultiCell(91, 4, strtoupper($_GET['shipper']), 0, 'L');
	$pdf->SetXY(12, $pdf->GetY());
	$pdf->MultiCell(91, 4, strtoupper($_GET['note']), 0, 'L');

	// =============================================================
	// BLOK CONSIGNEE (Dengan Filter Pembersih Data Lama)
	// =============================================================
	$con_name = strtoupper(trim($_GET['consignee'] ?? ''));
	$con_add  = strtoupper(trim($_GET['con_add'] ?? ''));

	// Deteksi data lama: Jika isi Textarea (con_add) diawali dengan Nama Consignee, potong namanya!
	if (!empty($con_name) && strpos($con_add, $con_name) === 0) {
		$con_add = trim(substr($con_add, strlen($con_name)));
		$con_add = ltrim($con_add, ",\n\r\t "); // Bersihkan sisa enter/koma di depan
	}

	$pdf->SetXY(106, 76);
	$pdf->MultiCell(91, 3.5, $con_name, 0, 'L');
	$pdf->SetXY(106, $pdf->GetY());
	$pdf->MultiCell(91, 3.5, $con_add, 0, 'L');


	// =============================================================
	// BLOK NOTIFY (Dengan Filter Pembersih Data Lama)
	// =============================================================
	$pdf->SetFont('calibri', 'B', 8);
	$pdf->SetXY(11, 121); $pdf->Cell(30, 4, 'NOTIFY PARTY', 0, 0, 'L');
	$pdf->SetFont('calibri', '', 9);
	
	$not_name = strtoupper(trim($_GET['not_name'] ?? ''));
	$not_add  = strtoupper(trim($_GET['not'] ?? ''));

	// Deteksi data lama untuk Notify
	if (!empty($not_name) && strpos($not_add, $not_name) === 0) {
		$not_add = trim(substr($not_add, strlen($not_name)));
		$not_add = ltrim($not_add, ",\n\r\t ");
	}

	$pdf->SetXY(12, 126);
	if (!empty($not_name)) {
		$pdf->MultiCell(91, 3.5, $not_name, 0, 'L');
		$pdf->SetXY(12, $pdf->GetY());
	}
	$pdf->MultiCell(91, 3.5, $not_add, 0, 'L');

	// VESSEL & ROUTE (KANAN)
	$pdf->SetFont('calibri', 'B', 7);
	$pdf->SetXY(106, 121); $pdf->Cell(30, 3, 'VESSEL', 0, 0, 'L');
	$pdf->SetXY(151, 121); $pdf->Cell(30, 3, 'CONNECTING VESSEL', 0, 0, 'L');
	$pdf->SetFont('calibri', '', 9);
	$pdf->SetXY(106, 125.5); $pdf->Cell(43, 4, $_GET['vessel'], 0, 0, 'L');
	$pdf->SetXY(151, 125.5); $pdf->Cell(48, 4, $_GET['voyage'], 0, 0, 'L');

	$pdf->SetFont('calibri', 'B', 7);
	$pdf->SetXY(106, 130); $pdf->Cell(30, 3, 'ETD', 0, 0, 'L');
	$pdf->SetXY(151, 130); $pdf->Cell(30, 3, 'ETA', 0, 0, 'L');
	$pdf->SetFont('calibri', '', 9);
	$pdf->SetXY(106, 134); $pdf->Cell(43, 4, $_GET['etd'], 0, 0, 'L');
	$pdf->SetXY(151, 134); $pdf->Cell(48, 4, $_GET['eta'], 0, 0, 'L');


	// Gabungkan POL + Code
$pol_display = !empty($_GET['pol']) ? $_GET['pol'] : '';
if (!empty($_GET['pol_code'])) {
    $pol_display .= ' - ' . strtoupper($_GET['pol_code']);
}

// Gabungkan POD + Code
$pod_display = !empty($_GET['pod']) ? $_GET['pod'] : '';
if (!empty($_GET['pod_code'])) {
    $pod_display .= ' - ' . strtoupper($_GET['pod_code']);
}
	
	
	$pdf->SetFont('calibri', 'B', 7);
	$pdf->SetXY(106, 139); $pdf->Cell(30, 3, 'POL', 0, 0, 'L');
	$pdf->SetXY(151, 139); $pdf->Cell(30, 3, 'POD', 0, 0, 'L');
	$pdf->SetFont('calibri', '', 9);
	$pdf->SetXY(106, 143); $pdf->Cell(43, 4, $pol_display, 0, 0, 'L');
	$pdf->SetXY(151, 143); $pdf->Cell(48, 4, $pod_display, 0, 0, 'L');

	// BL, PARTY, WEIGHT, VOL
	$pdf->SetFont('calibri', 'B', 7);
	$pdf->SetXY(11, 148); $pdf->Cell(30, 3, 'OCEAN BILL OF LADING', 0, 0, 'L');
	$pdf->SetXY(76, 148); $pdf->Cell(25, 3, 'PARTY', 0, 0, 'L');
	$pdf->SetXY(106, 148); $pdf->Cell(25, 3, 'GROSS WEIGHT', 0, 0, 'L');
	$pdf->SetXY(136, 148); $pdf->Cell(25, 3, 'NET WEIGHT', 0, 0, 'L');
	$pdf->SetXY(166, 148); $pdf->Cell(25, 3, 'VOLUME', 0, 0, 'L');
	
	$pdf->SetFont('calibri', '', 9);
	// BL
		if (!empty($_GET['bl'])) {
			$pdf->SetXY(11, 152);
			$pdf->Cell(63, 3, $_GET['bl'], 0, 0, 'L');
		}

		// PARTY (Qty + Pack)
		if (!empty($_GET['qty']) && floatval($_GET['qty']) > 0) {
			$pdf->SetXY(76, 152);
			$pdf->Cell(28, 3, $_GET['qty'] . " " . $_GET['pack'], 0, 0, 'L');
		}

		// GROSS WEIGHT
		if (!empty($_GET['weight']) && floatval($_GET['weight']) > 0) {
			$pdf->SetXY(106, 152);
			$pdf->Cell(28, 3, $_GET['weight'] . " KGs", 0, 0, 'L');
		}

		// NET WEIGHT
		if (!empty($_GET['netto']) && floatval($_GET['netto']) > 0) {
			$pdf->SetXY(136, 152);
			$pdf->Cell(28, 3, $_GET['netto'] . " KGs", 0, 0, 'L');
		}

		// VOLUME
		if (!empty($_GET['meas']) && floatval($_GET['meas']) > 0) {
			$pdf->SetXY(166, 152);
			$pdf->Cell(33, 3, $_GET['meas'] . " M3", 0, 0, 'L');
		}


	// FREIGHT | TERM | NO. AJU | NO./TGL PEB | NO./TGL NPE
	// Kolom: FREIGHT (10-35) | TERM (35-60) | AJU (60-110) | PEB (110-155) | NPE (155-200)
	// Jalur pemeriksaan & karantina sengaja TIDAK dicetak (hanya dikirim ke gudang).
	$pdf->Line(35, 155, 35, 163);
	$pdf->Line(60, 155, 60, 163);
	$pdf->Line(110, 155, 110, 163);
	$pdf->Line(155, 155, 155, 163);

	// Data AJU / PEB / NPE diambil dari tabel PEB (1 booking bisa >1 PEB).
	// Bila tabel belum ada / belum ada data, pakai parameter GET versi lama (aju_number, peb_number, ...).
	$this->load->model('pricelist/peb_model');
	$cl = $this->peb_model->print_lines($si);
	if (!$cl['aju'] && !$cl['peb'] && !$cl['npe']) {
		$fmt_customs = function ($no, $date) {
			$no   = strtoupper(trim((string)$no));
			$date = trim((string)$date);
			if ($no === '') return null;
			if ($date !== '' && $date !== '0000-00-00' && strtotime($date)) {
				return $no . ' / ' . date('d-m-Y', strtotime($date));
			}
			return $no;
		};
		$aju_get = strtoupper(trim((string)($_GET['aju_number'] ?? '')));
		if ($aju_get !== '') $cl['aju'][] = $aju_get;
		if (($l = $fmt_customs($_GET['peb_number'] ?? '', $_GET['peb_date'] ?? '')) !== null) $cl['peb'][] = $l;
		if (($l = $fmt_customs($_GET['npe_number'] ?? '', $_GET['npe_date'] ?? '')) !== null) $cl['npe'][] = $l;
	}

	// Muatkan semua entri dalam 1 baris dengan mengecilkan font (8 -> 6 pt).
	// Jika tetap tidak muat: tampilkan entri pertama + "(+N)" dan cetak halaman lampiran.
	$need_peb_appendix = false;
	$fit_customs = function ($items, $width) use ($pdf, &$need_peb_appendix) {
		if (!$items) { $pdf->SetFont('calibri', '', 8); return '-'; }
		$text = implode('; ', $items);
		foreach (array(8, 7.5, 7, 6.5, 6) as $fs) {
			$pdf->SetFont('calibri', '', $fs);
			if ($pdf->GetStringWidth($text) <= $width) return $text;
		}
		$need_peb_appendix = true;
		$text = $items[0] . ' (+' . (count($items) - 1) . ')';
		foreach (array(8, 7, 6.5, 6) as $fs) {
			$pdf->SetFont('calibri', '', $fs);
			if ($pdf->GetStringWidth($text) <= $width) break;
		}
		return $text;
	};

	$pdf->SetFont('calibri', 'B', 7);
	$pdf->SetXY(11, 156);  $pdf->Cell(23, 3, 'FREIGHT', 0, 0, 'L');
	$pdf->SetXY(36, 156);  $pdf->Cell(23, 3, 'TERM', 0, 0, 'L');
	$pdf->SetXY(61, 156);  $pdf->Cell(48, 3, 'NO. AJU', 0, 0, 'L');
	$pdf->SetXY(111, 156); $pdf->Cell(43, 3, 'NO. / TGL PEB', 0, 0, 'L');
	$pdf->SetXY(156, 156); $pdf->Cell(43, 3, 'NO. / TGL NPE', 0, 0, 'L');
	$pdf->SetFont('calibri', '', 9);
	$pdf->SetXY(11, 160);  $pdf->Cell(23, 3, $_GET['freight'], 0, 0, 'L');
	$pdf->SetXY(36, 160);  $pdf->Cell(23, 3, $_GET['incoterm'], 0, 0, 'L');
	$aju_txt = $fit_customs($cl['aju'], 47);
	$pdf->SetXY(61, 160);  $pdf->Cell(48, 3, $aju_txt, 0, 0, 'L');
	$peb_txt = $fit_customs($cl['peb'], 42);
	$pdf->SetXY(111, 160); $pdf->Cell(43, 3, $peb_txt, 0, 0, 'L');
	$npe_txt = $fit_customs($cl['npe'], 42);
	$pdf->SetXY(156, 160); $pdf->Cell(43, 3, $npe_txt, 0, 0, 'L');

	// MARKING & DESCRIPTION
	$pdf->SetFont('calibri', 'B', 7);
	$pdf->SetXY(11, 164); $pdf->Cell(30, 3, 'MARKING NOS', 0, 0, 'L');
	$pdf->SetXY(106, 164); $pdf->Cell(30, 3, 'DESCRIPTION OF GOODS', 0, 0, 'L');

	$pdf->SetFont('calibri', '', 10);
	$long_mark = strtoupper($_GET['long_mark'] ?? '');
	$long_desc = strtoupper($_GET['long_description'] ?? '');
	$lines_mark = $pdf->getNumLines(91, 4, $long_mark);
	$lines_desc = $pdf->getNumLines(91, 4, $long_desc);

	// WAREHOUSE & CONSOLIDATOR
	$pdf->SetFont('calibri', 'B', 7);
	$pdf->SetXY(11, 221); $pdf->Cell(30, 3, 'WAREHOUSE', 0, 0, 'L');
	$pdf->SetXY(106, 221); $pdf->Cell(30, 3, 'CONSOLIDATOR DETAIL', 0, 0, 'L');

	$pdf->SetFont('calibri', '', 9);
	$pdf->SetXY(12, 228); $pdf->write(0, $wh['warehouse_name']);
	if($this->session->userdata('region_id')=='Semarang') {
		$pdf->SetFont('calibri', '', 7);
		$pdf->SetXY(12, 230); $pdf->multiCell(91, 3, $wh['warehouse_address']);
		$pdf->SetFont('calibri', '', 9);
	}else{
	$pdf->SetXY(12, 230); $pdf->multiCell(91, 4, $wh['warehouse_address']);	
	}
	
	$pdf->SetXY(106, 228); $pdf->write(0, 'NPWP : ' . $wh['npwp']);
	$pdf->SetXY(106, 230); $pdf->multiCell(91, 4, 'NITKU : ' . $wh['nitku']);
	$pdf->SetXY(106, 234); $pdf->multiCell(91, 4, 'ALAMAT : ' . $wh['alamat']);

	// ROUTING INFO
	$pdf->SetFont('calibri', 'B', 7);
	$pdf->SetXY(11, 259); $pdf->Cell(30, 4, 'ROUTING INFORMATION', 0, 0, 'L');
	
	$pdf->SetXY(11, 265); $pdf->Cell(15, 3, 'Mode', 0, 0, 'L');
	$pdf->SetXY(28, 265); $pdf->Cell(45, 3, 'Vessel / Voyage', 0, 0, 'L');
	$pdf->SetXY(75, 265); $pdf->Cell(35, 3, 'Load', 0, 0, 'L');
	$pdf->SetXY(116, 265); $pdf->Cell(30, 3, 'Disch.', 0, 0, 'L');
	$pdf->SetXY(147, 265); $pdf->Cell(20, 3, 'ETD', 0, 0, 'L');
	$pdf->SetXY(167, 265); $pdf->Cell(20, 3, 'ETA', 0, 0, 'L');

	$pdf->SetFont('calibri', '', 9);
	$pdf->SetXY(11, 270);
	if ($_GET['type'] == 'FCL' || $_GET['type'] == 'LCL') {
		$pdf->Cell(15, 4, 'SEA', 0, 0, 'L');
	}


// Cetak Routing Info
$pdf->SetXY(28, 270); $pdf->Cell(45, 4, $_GET['vessel'], 0, 0, 'L');
$pdf->SetXY(75, 270); $pdf->Cell(35, 4, $pol_display, 0, 0, 'L');
$pdf->SetXY(116, 270); $pdf->Cell(30, 4, $pod_display, 0, 0, 'L');
$pdf->SetXY(147, 270); $pdf->Cell(20, 4, $_GET['etd'], 0, 0, 'L');
$pdf->SetXY(167, 270); $pdf->Cell(20, 4, $_GET['eta'], 0, 0, 'L');

	
	$pdf->SetXY(11, 280); 
	$pdf->write(0, $this->session->userdata('fullname'));
	
	// --- 6. QR CODE GENERATOR ---
	$si = $this->input->get('booking_number');
	ob_start();
	QRcode::png($si);
	$imageString = ob_get_contents();
	ob_end_clean();

	$im = imagecreatefromstring($imageString);
	$tmpFile = tempnam(sys_get_temp_dir(), 'qr_').'.png';
	imagepng($im, $tmpFile);
	imagedestroy($im);

	$pdf->Image($tmpFile, 12, 8, 25, 25, "png");
	unlink($tmpFile);
	
	// --- 7. ATTACHMENT MULTI-PAGE (Bila Marking / Desc > 12 Baris) ---
	if ($lines_mark > 12 || $lines_desc > 12) {
		$pdf->SetXY(12, 169);
		$pdf->SetFont('calibri', 'B', 9);
		$pdf->MultiCell(91, 5, 'MARKING AND DESCRIPTION ARE ATTACHED IN THE NEXT PAGE', 0, 'L');
		
		$pdf->AddPage();
		$pdf->SetFont('calibri', 'B', 12);
		$pdf->SetXY(50, 15);
		$pdf->Cell(0, 10, 'Attachment - Marking and Description Details', 0, 1, 'L');

		$pdf->SetFont('calibri', 'B', 10);
		$pdf->SetXY(12, 30);
		$pdf->write(0, 'MARK :');
		
		$pdf->SetFont('calibri', '', 10);
		$pdf->SetXY(12, 35);
		$pdf->MultiCell(180, 4, $long_mark, 0, 'L');
		
		$pdf->SetFont('calibri', 'B', 10);
		$pdf->SetXY(12, $pdf->GetY() + 10);
		$pdf->write(0, 'DESCRIPTION :');
		
		$pdf->SetFont('calibri', '', 10);
		$pdf->SetXY(12, $pdf->GetY() + 5);
		$pdf->MultiCell(180, 4, $long_desc, 0, 'L');
	} else {
		$pdf->SetXY(12, 169);
		$pdf->MultiCell(91, 4, $long_mark, 0, 'L');
		$pdf->SetXY(106, 169);
		$pdf->MultiCell(91, 4, $long_desc, 0, 'L');
	}

	// --- 8. LAMPIRAN PEB / NPE (bila entri tidak muat di kolom BC) ---
	if ($need_peb_appendix && $this->peb_model->schema_status()['ok']) {
		$pdf->AddPage();
		$pdf->SetFont('calibri', 'B', 12);
		$pdf->SetXY(12, 15);
		$pdf->Cell(0, 10, 'Attachment - PEB / NPE', 0, 1, 'L');
		$pdf->SetFont('calibri', '', 9);
		$pdf->SetXY(12, 24);
		$pdf->Cell(0, 5, 'Booking: ' . $si, 0, 1, 'L');

		$w = array(8, 56, 26, 24, 48, 24);
		$hd = array('No', 'NO. AJU', 'NO. PEB', 'TGL PEB', 'NO. NPE', 'TGL NPE');
		$y = 32;
		$pdf->SetFont('calibri', 'B', 8);
		$x = 12;
		foreach ($hd as $i => $t) { $pdf->SetXY($x, $y); $pdf->Cell($w[$i], 6, $t, 1, 0, 'C'); $x += $w[$i]; }
		$y += 6;
		$pdf->SetFont('calibri', '', 8);
		$d = function ($v) { return ($v && $v !== '0000-00-00') ? date('d-m-Y', strtotime($v)) : '-'; };
		$no = 0;
		foreach ($this->peb_model->list_by_booking($si) as $r) {
			if ($y > 275) { $pdf->AddPage(); $y = 15; }
			$no++;
			$cells = array($no, $r['aju_number'] ?: '-', $r['peb_number'] ?: '-', $d($r['peb_date']), $r['npe_number'] ?: '-', $d($r['npe_date']));
			$x = 12;
			foreach ($cells as $i => $t) { $pdf->SetXY($x, $y); $pdf->Cell($w[$i], 6, $t, 1, 0, ($i === 0 ? 'C' : 'L')); $x += $w[$i]; }
			$y += 6;
		}
	}

	$pdf->Output("BC " . $si . " " . date('d-M-Y') . '.pdf', 'I');
}
	

function post_quot()
	{
		$booking_id = $_POST['booking_id'];
		$booking_code = $_POST['booking_code'];
		$shipper = $_POST['shipper'];
		$items = array();
		$arr = $this->booking_model->get_price($booking_id);
		foreach($arr->result_array() as $res){
			if ($res['of_emkl']==0){
			$itemtype = 'freight';
			} else{
			$itemtype = 'local-dest';
			}
			$data['quote_no'] = $booking_code;
			$data['code'] = 'X';
			$data['itemSevice'] = $res['item'];
			$data['qty'] = $res['qty'];
			$data['unitPrice'] = $res['unit_price'];
			$data['price'] = $res['price'];
			$data['condition'] = $res['kondisi'];
			$data['itemType'] = $itemtype;
			array_push($items, $data);	
		}
		$kirim = json_encode($items);
		//print_r($kirim);
		//die();
		$client = new \GuzzleHttp\Client();
		$publicKey = '38f7313b-230e-4e54-91dc-5ef153f21ccd';
		$raw_response = $client->request('POST','https://app.gateway-id.com/api/selling-price/add', [
		'headers' => ['Authorization' => 'Bearer ' . $publicKey ],
		'body' => $kirim,
		]);
		$response = $raw_response->getBody()->getContents();
		
		$to = $this->booking_model->get_shipper_details($shipper);
		$sales = $to->sales;
		$to_sales = $this->dbx->get_where('mstadmin', array('user_id' => $sales))->row();
			//print_r($to_sales);
			//die();
		$data_book = $this->booking_model->findresult($_POST['booking_code'])[0];
		$filename = "Quotation-".$_POST['booking_code'].".pdf";
		$this->file_pdf($data_book, $_POST, $filename);
			$dataEmail = array(
				'nama' => $to->shipper_name,
				'nama_sales' => $to_sales->fullname,
				'code'=>$booking_code,
			);
			$html = $this->load->view('emails/reservation.php', $dataEmail,TRUE);
			
			$response_email = send_email_elastic("Booking ".$dataEmail['nama']." has been prepared", $html, array($to->cp_email), $filename);
			
			if($response_email['code'] == 500){
				return $this->output->set_status_header(500)
				->set_content_type('application/json')
				->set_output(json_encode($response_email['response']));
			}else{
				echo json_encode($response_email);	
			}
		}

	function update_finance(){
		$data = $this->booking_model->update_finance($_POST);
		//print_r($_POST);
		//$data_book = $this->booking_model->find($_POST['booking_code'])[0];
		//$filename = "Quotation-".$_POST['booking_code'].".pdf";
		//$this->file_pdf($data_book, $_POST, $filename);
		//$to = $this->booking_model->get_shipper_details($data_book->created_by);
		//$dataEmail = array('nama' => $to->shipper_name, 'booking_code' => $_POST['booking_code']);
		//$html = $this->load->view('emails/preparedQuote.php',$dataEmail,TRUE);
		//$response = send_email_elastic("e-Quotation - ".$_POST['booking_code'], $html, array($to->cp_email), $filename);
		//if($response['code'] == 500){
		//	return $this->output->set_status_header(500)
		//	->set_content_type('application/json')
		//	->set_output(json_encode($response['response']));
		//}else{
		//	unlink(realpath(APPPATH.'../'.$filename));
		//	echo json_encode($response);	
		//}
		echo json_encode($data);
	}
	
	function getDataQuotation() {
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
		$sord = isset($_POST['sord'])?$_POST['sord']:'';
				
		$start = $batas * $hal - $batas;
		$start = ($start<0)?0:$start;			
		$_POST['start'] = $start;
		
		$q = $this->booking_model->getDataQuotation($_POST,false);
		$query = $this->booking_model->getDataQuotation($_POST,true);
		//print_r($this->dbx->last_query());
		//die();
	 
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

		// //print_r($q);
		// die();
		foreach($query as $row) {
			$data->rows[$i]['id']=$row->id;
			$data->rows[$i]['cell']=array(
				$row->booking_code,
				$row->type_of_booking,
				$row->mode,
				$row->movement_type,
				$row->incoterm,
				$row->shipper_name,
				$row->origin_city,
				$row->destination_city,
				$row->created_at,
			);
			$i++;
		}
		echo json_encode($data);
	}
	function get_data_form()
	{
		$shipper = $this->booking_model->get_shipper_by_id($_POST);
		echo json_encode(array(
			'nama'=>$shipper['shipper_name'],
			'alamat'=>$shipper['shipper_address'],
			'id'=>$shipper['id_shipper'],
			'sales'=>$shipper['sales']
		));
	}
	function get_data_consignee()
	{
		$data = $this->booking_model->get_data_consignee($_POST);
		echo json_encode(array(
			'consignee' => $data->row()->consignee_notify_address, // Diubah: HANYA ALAMAT SAJA
			'id' => $data->row()->consignee_notify_id
		));
	}

	function get_data_notify()
	{
		$data = $this->booking_model->get_data_notify($_POST);
		echo json_encode(array(
			'notify' => $data->row()->consignee_notify_address, // Diubah: HANYA ALAMAT SAJA
			'id' => $data->row()->consignee_notify_id
		));
	}
	function open_quote_pricing(){
		$data['cities'] = $this->fungsi->get_data_object(REF_CTY,array('city_name','city_name'),'city_name',FALSE,FALSE);
		$data['booking'] = $this->booking_model->findresult($_POST['bookingCode'])[0];
		$this->load->view('pricelist/v_quote_pricing_form', $data);
	}

	function insert_data(){
		$data = $this->booking_model->insert_data($_POST);
		echo json_encode($data);
	}

	function manage_data(){
		$this->booking_model->manage_data($_POST);
		echo json_encode(array('mode'=>$_POST['oper']));
	}

	function cancel_booking()
	{
		// 1. Ambil dan bersihkan data POST
		$booking_code = $this->input->post('booking', true);
		if (is_array($booking_code)) {
			$booking_code = isset($booking_code['booking']) ? $booking_code['booking'] : '';
		}
		$booking_code = trim((string) $booking_code);

		// PASTIKAN TIDAK ADA PERINTAH echo ATAU print_r DI SINI

		// Validasi kosong
		if ($booking_code === '') {
			return $this->output
				->set_status_header(422)
				->set_content_type('application/json')
				->set_output(json_encode(array(
					'status'  => false,
					'message' => 'BOOKING_CODE_EMPTY'
				)));
		}

		// 2. Guard Check (Cek apakah sudah masuk stuffing plan)
		// (juga menolak booking coload dari Osline/Winfast; dicek SEBELUM ticket WMS dihapus)
		$guard = $this->booking_model->get_booking_delete_guard($booking_code);
		if (empty($guard['can_delete'])) {
			return $this->output
				->set_status_header(409)
				->set_content_type('application/json')
				->set_output(json_encode(array(
					'status'       => false,
					'booking_code' => $booking_code,
					'code'         => isset($guard['message']) ? $guard['message'] : '',
					'message'      => !empty($guard['user_message'])
						? $guard['user_message']
						: 'Booking sudah masuk job stuffing, tidak boleh dihapus.',
					'job_no'       => isset($guard['job_no']) ? $guard['job_no'] : ''
				)));
		}

		// 3. Hapus WMS (Hanya dieksekusi jika fungsi benar-benar ada, mencegah Fatal Error)
		$wms = array('ok' => true, 'note' => 'Bypass WMS');
		if (method_exists($this, 'delete_wms_booking')) {
			$wms = $this->delete_wms_booking($booking_code);
			if (empty($wms['ok'])) {
				return $this->output
					->set_status_header(502)
					->set_content_type('application/json')
					->set_output(json_encode(array(
						'status'  => false,
						'message' => 'Gagal menghapus dari sistem WMS.',
						'wms'     => $wms
					)));
			}
		}

		// 4. Hapus data lokal Gateway
		$local = $this->booking_model->cancel_booking($booking_code);
		if (empty($local['status'])) {
			return $this->output
				->set_status_header(500)
				->set_content_type('application/json')
				->set_output(json_encode(array(
					'status'  => false,
					'message' => 'Gagal menghapus data di database lokal.',
					'local'   => $local
				)));
		}

		// 5. Bersihkan outbox (Hanya dieksekusi jika fungsi benar-benar ada)
		$outbox_deleted = false;
		if (method_exists($this, 'delete_wms_outbox')) {
			$outbox_deleted = $this->delete_wms_outbox($booking_code);
		}

		// 6. Return response sukses final yang WAJIB dibaca oleh Javascript
		return $this->output
			->set_status_header(200)
			->set_content_type('application/json')
			->set_output(json_encode(array(
				'status'         => true,
				'booking_code'   => $booking_code,
				'message'        => 'Booking berhasil dihapus sepenuhnya!',
				'wms'            => $wms,
				'local'          => $local,
				'outbox_deleted' => $outbox_deleted
			)));
	}

	function saving_data()
	{
		$data = $this->booking_model->saving_data($_POST);
		echo json_encode($data);
	}

	function update_price(){
		$data = $this->booking_model->saving_data($_POST);
		echo json_encode($data);
	}
	
	function open_booking_form()
	{
		$data = array(
			'id'=> $this->input->post('spd'),
			);
		$this->load->view('pricelist/v_booking_form',$data);
	}
	function collect_data_booking()
	{
		$data = $this->booking_model->collect_data_booking($_POST['id_booking'],$_POST['booking_code']);
		$this->load->view('pricelist/v_booking_edit',$data );
		//print_r($data);
		//die();
	}
	function get_table_selling(){
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
	   $sord = isset($_POST['sord'])?$_POST['sord']:'';	   	   
		$start = $batas * $hal - $batas;
		$start = ($start<0)?0:$start;
		$_POST['start'] = $start; 	
		$_POST['_search'] = 'true';
		$_POST['searchField'] = 'of_emkl';
		$_POST['searchString'] = '0';
		$_POST['searchOper'] = 'eq';
	
		$q = $this->booking_model->get_table_selling($_POST,false);
		$query = $this->booking_model->get_table_selling($_POST,true);	 	
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
		    								$row->of_emkl,
		    								$row->item,
		    								$row->kondisi,
												$row->currency,
		    								$row->qty,
												$row->unit_price,
												$row->price,
											);
		    $i++;
		}
		echo json_encode($data);
	}

	function get_table_selling_misc(){
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
	   $sord = isset($_POST['sord'])?$_POST['sord']:'';	   	   
		$start = $batas * $hal - $batas;
		$start = ($start<0)?0:$start;
		$_POST['start'] = $start; 		
		$_POST['_search'] = 'true';
		$_POST['searchField'] = 'of_emkl';
		$_POST['searchString'] = '1';
		$_POST['searchOper'] = 'eq';

		$q = $this->booking_model->get_table_selling($_POST,false);
		$query = $this->booking_model->get_table_selling($_POST,true);	 	
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
		    								$row->of_emkl,
		    								$row->item,
		    								$row->kondisi,
												$row->currency,
		    								$row->qty,
												$row->unit_price,
												$row->price,
											);
		    $i++;
		}
		echo json_encode($data);
	}
	
	function print_data(){
		$this->load->library('alphapdf');
		$pdf=new AlphaPDF();		
		$pdf->AddPage('P','A4');
		
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
		
		
		$pdf->SetAlpha(0.5);
		$pdf->Image(APPPATH.'third_party/img/yellowblue.jpg', -10,0,-170);
		$pdf->SetAlpha(1);
		$pdf->Image(APPPATH.'third_party/img/'.$logo,10,10,+50);
		/* $pdf->SetFont('courier','',12);
		$pdf->SetXY(130, 10);
		$pdf->Cell(70, 6, $pt,0,1,'R',false);
		$pdf->SetFont('courier','',6);
		$pdf->SetXY(130, 17);
		$pdf->MultiCell(70,3,$add,0,'R',false);
	
		$pdf->SetDrawColor(0,0,71);
		$pdf->SetLineWidth(0.1);
		$pdf->Line(10,33.9,200,33.9); */
		$pdf->SetXY(10, 35);
		$pdf->SetFont('courier','B',12);
		$pdf->Cell(0,10,'QUOTATION '.strtoupper($_GET['mode']),0,0,'C');		
		$pdf->SetXY(10, 50);
		$pdf->SetFont('courier','B',8);
		$pdf->write(0,'TO :');
		$pdf->SetFont('courier','',8);
		$contact = $this->booking_model->get_shipper_details($_GET['shipper_id']);
		$pdf->SetXY(10, 55);
		$pdf->write(0,$contact->shipper_name);
		$pdf->SetXY(10, 58);
		$pdf->write(0,$contact->shipper_address);
		$pdf->SetXY(10, 59);
		//$pdf->write(0,$contact->cp_email);
		$pdf->SetXY(140, 55);
		$pdf->write(0,'Quotation No.');
		$pdf->SetXY(161, 55);
		$pdf->write(0,': '.$_GET['booking_code']);
		$pdf->SetXY(140, 58);
		$pdf->write(0,'Expires');
		$pdf->SetXY(161, 58);
		$pdf->write(0,': '.$_GET['valid']);
		$pdf->SetDrawColor(0,0,71);
		$pdf->SetLineWidth(0.1);
		$pdf->Line(10,63,200,63);
		$pdf->SetXY(10, 66);
		$pdf->SetFont('courier','B',9);
		$pdf->Cell(0,0,'ROUTE AND SCHEDULE ',0,0,'C');
		$pdf->SetDrawColor(0,0,71);
		$pdf->SetLineWidth(0.1);
		$pdf->Line(10,68,200,68);
		$pdf->SetFont('courier','',8);
		$pdf->SetXY(10, 71);
		$pdf->write(0,'POD / POL');
		$pdf->SetXY(35, 71);
		$pdf->write(0,': '.$_GET['origin']);
		$pdf->SetXY(10, 75);
		$pdf->write(0,'Vessel');
		$pdf->SetXY(35, 75);
		$pdf->write(0,': '.$_GET['vessel']);
		$pdf->SetXY(85, 71);
		$pdf->write(0,'Movement / Term');
		$pdf->SetXY(110, 71);
		$pdf->write(0,': '.strtoupper($_GET['movement_type']));
		$pdf->SetXY(85, 75);
		$pdf->write(0,'Cargo Type');
		$pdf->SetXY(110, 75);
		$pdf->write(0,': '.strtoupper($_GET['cargo_type']));
		$pdf->SetXY(160, 71);
		$pdf->write(0,'ETD');
		$pdf->SetXY(180, 71);
		$pdf->write(0,': '.$_GET['etd']);
		$pdf->SetXY(160, 75);
		$pdf->write(0,'ETA');
		$pdf->SetXY(180, 75);
		$pdf->write(0,': '.$_GET['eta']);
		$pdf->SetDrawColor(0,0,71);
		$pdf->SetLineWidth(0.1);
		$pdf->Line(10,85,200,85);
		$pdf->SetXY(10, 88);
		$pdf->SetFont('courier','B',9);
		$pdf->Cell(0,0,'CARGO DETAIL',0,0,'C');
		$pdf->SetDrawColor(0,0,71);
		$pdf->SetLineWidth(0.1);
		$pdf->Line(10,91,200,91);
		$pdf->SetXY(10, 110);
		$pdf->SetFont('courier','B',8);
		$pdf->write(0,'RATES INFORMATION');
		$pdf->SetFont('courier','',8);
		$header = array('QUANTITY', 'PACKAGE TYPE', 'CARGO DESCRIPTION', 'VOLUME','GROSS WEIGHT');
		// Data loading
		$data = array($_GET['quantity'], strtoupper($_GET['package_type']), strtoupper($_GET['cargo_description']), $_GET['volume'],$_GET['weight']);
		$pdf->SetXY(10, 94);
		$pdf->FancyTable($header,$data);
		$header2 = array('ITEM CHARGES', 'QTY', 'RATE', 'TOTAL','CONDITION');
		$data2=$this->dbx->get_where('x_finance_booking_detail',array('booking_id'=>$_GET['booking_id']))->result();
		$pdf->SetXY(10, 118);
		$pdf->FancyTable2($header2,$data2);
		$pdf->Ln();
		$total=array_sum(array_column($data2, 'price'));
		$pdf->Cell(145, 6, number_format($total, 2), 'BLR', 0, 'R', 0);
		
		$pdf->Output('Selling-Rate-To-','I'); 
	}

	function file_pdf($book, $update, $filename){
		$this->load->library('FPDF_MC_Table');
		$pdf=new FPDF_MC_Table();		
		$pdf->AddPage('P','A4');
		
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
		
		$pdf->Image(APPPATH.'third_party/img/'.$logo,10,10,+50);
		$pdf->SetFont('courier','',12);
		$pdf->SetXY(130, 10);
		$pdf->Cell(70, 6, $pt,0,1,'R',false);
		$pdf->SetFont('courier','',6);
		$pdf->SetXY(130, 17);
		$pdf->MultiCell(70,3,$add,0,'R',false);
	
		$pdf->SetDrawColor(0,0,71);
		$pdf->SetLineWidth(0.6);
		$pdf->Line(10,33,200,33);	
		$pdf->SetLineWidth(0.1);
		$pdf->Line(10,33.9,200,33.9);
		$pdf->SetXY(10, 35);
		$pdf->SetFont('courier','B',12);
		$pdf->Cell(0,10,'QUOTATION '.strtoupper($book->mode),0,0,'C');		
		$pdf->SetFont('courier','',8);
		
		$pdf->SetXY(10, 50);
		$pdf->SetFont('courier','B',8);
		$pdf->write(0,'TO :');
		$pdf->SetFont('courier','',8);
		$pdf->SetXY(10, 53);
		
		$contact = $contact = $this->booking_model->get_shipper_details($book->shipper_id);
		$pdf->write(0,$contact->shipper_name);
		$pdf->SetXY(10, 56);
		$pdf->write(0,$contact->cp_email);
		$pdf->SetXY(10, 59);
		
		$pdf->SetXY(140, 50);
		$pdf->write(0,'Quotation No.');
		$pdf->SetXY(161, 50);
		$pdf->write(0,': '.$book->booking_code);
		$pdf->SetXY(140, 53);
		$pdf->write(0,'Valid before');
		$pdf->SetXY(161, 53);
		$pdf->write(0,': '.$book->valid_at);
		$pdf->SetDrawColor(0,0,71);
		$pdf->SetLineWidth(0.1);
		$pdf->Line(10,63,200,63);
		$pdf->SetXY(10, 66);
		$pdf->SetFont('courier','B',8);
		$pdf->write(0,'ROUTE AND CARGO DETAIL');
		$pdf->SetFont('courier','',8);
		$pdf->SetXY(10, 71);
		$pdf->write(0,'Port Of Loading');
		$pdf->SetXY(35, 71);
		$pdf->write(0,': '.$book->origin_city);
		$pdf->SetXY(10, 74);
		$pdf->write(0,'Port Of Destination');
		$pdf->SetXY(35, 74);
		$pdf->write(0,': '.$book->destination_city);
		$pdf->SetXY(10, 77);
		$pdf->write(0,'Via');
		$pdf->SetXY(35, 77);
		$pdf->write(0,': '.$book->trans_city);
		$pdf->SetXY(85, 71);
		$pdf->write(0,'Movement Type');
		$pdf->SetXY(110, 71);
		$pdf->write(0,': '.strtoupper($book->movement_type));
		$pdf->SetXY(85, 74);
		$pdf->write(0,'Incoterm');
		$pdf->SetXY(110, 74);
		$pdf->write(0,': '.strtoupper($book->incoterm));
		$pdf->SetXY(85, 77);
		$pdf->write(0,'Cargo Type');
		$pdf->SetXY(110, 77);
		$pdf->write(0,': '.strtoupper($book->cargo_type));
		$pdf->SetXY(160, 71);
		$pdf->write(0,'Insurace');
		$pdf->SetXY(180, 71);
		$pdf->write(0,': '.$book->insurance > 0 ? 'Yes' : 'No');
		$pdf->SetXY(160, 74);
		$pdf->write(0,'Trucking');
		$pdf->SetXY(180, 74);
		$pdf->write(0,': '.$book->trucking = 1? 'Yes' : 'No');
		$pdf->SetXY(160, 77);
		$pdf->write(0,'PEB/NPE');
		$pdf->SetXY(180, 77);
		$pdf->write(0,': '.$book->clearance = 1? 'Yes' : 'No');
		$pdf->write(0,'COO');
		$pdf->SetXY(180, 77);
		$pdf->write(0,': '.$book->clearance = 1? 'Yes' : 'No');
		$pdf->SetXY(10, 100);
		$pdf->SetFont('courier','B',8);
		$pdf->write(0,'CHARGES');
		$pdf->SetFont('courier','',8);
		$header = array('QUANTITY', 'PACKAGE TYPE', 'CARGO DESCRIPTION', 'VOLUME','GROSS WEIGHT');
		$detailBook = $this->dbx->get_where("trnstuffingplan_booking_detail", array("booking_id" => $book->booking_code))->result()[0];
		// Data loading
		$data = array($detailBook->quantity, strtoupper($detailBook->package_type), strtoupper($detailBook->description_print), $detailBook->volume, $detailBook->weight);
		$pdf->SetXY(10, 83);
		$pdf->FancyTable($header,$data);
		$header2 = array('ITEM CHARGES', 'QTY', 'RATE', 'TOTAL','CONDITION');
		$data2=$this->dbx->get_where('x_finance_booking_detail',array('booking_id'=>$book->id))->result();
		$total=array_sum(array_column($data2, 'price'));
		$pdf->SetXY(10, 103);
		$pdf->FancyTable2($header2,$data2);
		$pdf->Cell(3, 10, number_format($total, 2), 'B', 0, 'R', 1);
		
		$pdfdoc = $pdf->Output($filename,'F'); 
		// $attachment = chunk_split(base64_encode($pdfdoc));
		// return $pdfdoc;
	}
	
	function tarik_data_selling_price_quot()
	{
   	$data = $this->quotation_model->tarik_data_selling_price_quot($_POST);
	//print_r($this->dbx->last_query());
   	echo json_encode($data);
	}
	
	function print_out_bl()
	{

		$spd=$_GET['spd'];
		$bl_no=$_GET['bl'];
		$cetak=$_GET['cetak'];
		$code=$_GET['code'];
		
		$this->load->model('m_bill_of_ladding');
		$respon = $this->m_bill_of_ladding->collect_data_draft($spd);
		$data = $respon[0];
		//print_r($data);
		//die();
		$this->load->library('FPDF_MC_Table');
		$pdf=new FPDF_MC_Table();
		$pdf->AliasNbPages();

		if($code==1 && $cetak=='FALSE') {
			$pdf->AddPage('P','polos');
		}
		else if($code==1 && $cetak=='TRUE')
		{
			$pdf->AddPage('P','A4');
			$pdf->SetFont('Arial','',8);
		}
		else if($code==2 && $cetak=='FALSE')
		{
			$pdf->AddPage('P','bl');
		}
		else if($code==6 && $cetak=='TRUE')
		{
			$pdf->AddPage('P','fukuyama');
		}
			else if($code==6 && $cetak=='FALSE')
		{
			$pdf->AddPage('P',array(230,320));
		}
		else if($code==7 && $cetak=='TRUE')
		{
			//$pdf->AddPage('P','fukuyama');
			$pdf->AddPage('P',array(210,297));
		}
		else if($code==7 && $cetak=='FALSE')
		{
			//$pdf->AddPage('P',array(230,320));
			$pdf->AddPage('P',array(210,297));
		}
		else if($code==8 && $cetak=='FALSE')
		{
			$pdf->AddPage('P','A4');
			$pdf->SetFont('Arial','',8);
		}
		else if($code==9 && $cetak=='FALSE')
		{
			$pdf->AddPage('P','polos');
		}
		else if($code==10 && $cetak=='FALSE')
		{
			$pdf->AddPage('P','polos');
		} 
		else if($code==11 && $cetak=='FALSE') {
			$pdf->AddPage('P','polos');
		}
		else if($code==11 && $cetak=='TRUE')
		{
			$pdf->AddPage('P','A4');
			$pdf->SetFont('Arial','',8);
		}
		else if($code==12 && $cetak=='TRUE')
		{
			$pdf->AddPage('P','A4');
			$pdf->SetFont('Arial','',8);
		}
		else {
			$pdf->AddPage('P','A4');
			$pdf->SetFont('Arial','',8);
		}

		function convert_to_fpdf_encoding($text) {
			// Hapus karakter invisible
			$text = preg_replace('/[\x{200E}\x{200F}\x{202C}\x{FEFF}]/u', '', $text);

			// Konversi ke ISO-8859-1
			return iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $text);
		}		

		if($code==1)
		{
			if($cetak=='TRUE')
			{
				$pdf->Image(APPPATH.'third_party/img/gateway_new.jpg',5,5,+200);
				$pdf->Watermark('DRAFT');
				$pdf->SetFont('Arial','',8);
				$pdf->SetXY(10, 22);
				$text = $data->note;
				$pdf->MultiCell(89, 3, $text, 0, '', false);
				$pdf->SetXY(152, 23);
				$pdf->SetFont('Arial','B',10);
				$pdf->write(0, str_replace('%20',' ',$bl_no));
				$pdf->SetFont('Arial','',8);
				$pdf->SetXY(10, 48);
				$pdf->MultiCell(90,3,strtoupper(convert_to_fpdf_encoding($data->consignee)),0,'L');
				$pdf->SetXY(10, 74);
				$pdf->MultiCell(90,3, strtoupper(convert_to_fpdf_encoding($data->notify)),0,'L');

				$pdf->SetXY(10, 103);
				$pdf->write(0, $data->pol_bl??$data->origin_city);
				$pdf->SetXY(57, 103);
				$pdf->write(0, $data->pol_bl??$data->origin_city);

			
				$pdf->SetXY(10, 113);
				$pdf->write(0, $data->vessel);
				

				$pdf->SetXY(106, 113);
				$pdf->write(0, $data->pod_bl??$data->destination_city);
				$pdf->SetXY(154, 113);
				$pdf->write(0, $data->pod_bl??$data->destination_city);

                    $pdf->SetXY(113, 74);
					$pdf->MultiCell(90,3,$data->for_delivery,0,'L');
					$pdf->SetXY(108, 78);
					

				$marking_text = $data->marking;

				// Hitung baris yang dibutuhkan untuk cell lebar 39mm
				$attc_marks = $this->getMulticellLineCount($pdf, 39, $marking_text);
								
				$description_of_goods_text = $data->description_of_goods;

				// Hitung baris yang dibutuhkan untuk cell lebar 39mm
				$attc_discription = $this->getMulticellLineCount($pdf, 60, $description_of_goods_text);

				// Cek apakah lebih dari 18 baris
				$pdf->SetFont('Verdana','',10);
				if ($attc_marks > 18 || $attc_discription > 15) {
					$pdf->SetXY(12, 157);
					$pdf->SetFont('Verdana', 'B', 10);
					$pdf->MultiCell(160, 5, 'MARKING AND DESCRIPTION ARE ATTACHED IN THE NEXT PAGE', 0, 'L');
				}else {
					$pdf->SetFont('Arial','',8);
					$pdf->SetXY(10, 128);
					$pdf->MultiCell(39, 3, $marking_text, 0, 'L', false);
					$pdf->SetXY(80, 132);
					$pdf->write(0, 'SAID TO CONTAIN :');
					$pdf->SetXY(80, 135);
					$text = html_entity_decode($data->description_of_goods, ENT_QUOTES, 'UTF-8');

						// Buang karakter non-printable dulu
						$text = preg_replace('/[^\P{C}\n]+/u', '', $text);

						// Konversi dengan ignore
						$text = iconv('UTF-8', 'Windows-1252//IGNORE', $text);
					$pdf->MultiCell(60,3,strtoupper($text),0,'L');
}
	
				$pdf->SetFont('Arial','',8);
		  		$pdf->SetXY(10, 182);
				$pdf->write(0, 'CONTAINER/SEAL/TYPE');
		  		$pdf->SetXY(10, 184);
				$pdf->MultiCell(45, 3, $data->container_no);

            	$pdf->SetXY(56, 130);
				$pdf->MultiCell(20,3, $data->quantity.' '.$data->package_type);
				$pdf->SetXY(56, 194);
				$pdf->MultiCell(120,3, $data->quantity.' ( '.$this->fungsi->num_to_words($data->quantity,'',0,'').' ) '.$data->package_type.' ONLY');
				
				
				if($data->number_of_bl == "EXPRESS RELEASE") 
				{
					// Set posisi
					$pdf->SetXY(99, 187);

					// Warna border dan teks merah
					$pdf->SetDrawColor(255, 0, 0);  // Border merah
					$pdf->SetTextColor(255, 0, 0);  // Teks merah

					// Set font
					$pdf->SetFont('Arial', 'B', 15);

					// Hitung lebar teks
					$text = 'EXPRESS RELEASE';
					$w = $pdf->GetStringWidth($text) + 10; // tambahkan padding
					$h = 10;

					// Gambar border merah
					$pdf->Cell($w, $h, $text, 1, 0, 'C'); // 1 = borde
				}
				$pdf->SetTextColor(0, 0, 0);
				$pdf->SetFont('Arial','',8);
				if($data->shipped_on_board == 1)
				{
					$pdf->SetXY(150, 165);
					$pdf->write(0, 'SHIPPED ON BOARD');
					$pdf->SetXY(150, 168);
					$pdf->write(0, 'FROM '.$data->pol_bl??$data->origin_city);
					$pdf->SetXY(150, 171);
					$pdf->write(0, 'ON '.$data->expect_etd);
					$pdf->SetXY(150, 174);
					$pdf->write(0, 'BY '.$data->vessel);
				} 
				


				
				$pdf->SetXY(158, 135);
				$pdf->write(0, 'GW :');
				$pdf->SetXY(177, 134);
				$pdf->Cell(13,2,$data->weight,0,0,'R',false,'');
				$pdf->SetXY(188, 135);
				$pdf->write(0, ' KGS');

				if(!empty($data->netto)) {
					$pdf->SetXY(158, 140);
					$pdf->write(0,'NW : ');
					$pdf->SetXY(177, 139);
					$pdf->Cell(13,2,$data->netto,0,0,'R',false,'');
					$pdf->SetXY(188, 140);
					$pdf->write(0, ' KGS');
					$pdf->SetXY(158, 145);
					$pdf->write(0,'MEAS : ');
					$pdf->SetXY(177, 144);
					$pdf->Cell(13,2,$data->volume,0,0,'R',false,'');
					$pdf->SetXY(188, 145);
					$pdf->write(0, ' M');
					$pdf->SetXY(191, 144);
					$pdf->SetFont('Arial','',6);
					$pdf->write(0, ' 3');  
				}else{
					$pdf->SetXY(158, 140);
					$pdf->write(0,'MEAS : ');
					$pdf->SetXY(177, 139);
					$pdf->Cell(13,2,$data->volume,0,0,'R',false,'');
					$pdf->SetXY(188, 140);
					$pdf->write(0, ' M');
					$pdf->SetXY(191, 139);
					$pdf->SetFont('Arial','',6);
					$pdf->write(0, ' 3'); 
				}
				$pdf->SetFont('Arial','',8);
				$pdf->SetXY(22, 209);
				$pdf->write(0, 'FREIGHT');
			$x_label = 17;    // Posisi X label
			$y_label = 251;   // Posisi Y label

			// Baris pertama (label)
			$pdf->SetFont('Arial','',8);
			$label = "Type of Movement :";
			$pdf->SetXY($x_label, $y_label);
			$pdf->Write(0, $label);

			// Baris kedua (value)
			$pdf->SetFont('Arial','',8);
			$value = $data->movement_type;

			// Hitung lebar label dan isi
			$label_width = $pdf->GetStringWidth($label);
			$value_width = $pdf->GetStringWidth($value);

			// Hitung posisi X agar teks value center terhadap label
			$x_value = $x_label + ($label_width - $value_width) / 2;
			$y_value = $y_label + 4; // sedikit turun dari baris sebelumnya

			$pdf->SetXY($x_value, $y_value);
			$pdf->Write(0, $value);

				
				if($data->freight == "PREPAID") {
		  			$pdf->SetXY(60, 209);
		  			$pdf->write(0,$data->freight);
				} else {
		  			$pdf->SetXY(88, 209);
		  			$pdf->write(0,$data->freight);
				} 
				
				$pdf->SetXY(54, 251);
				$pdf->write(0,'Payable at');
				
				$pdf->SetXY(55, 255);
				$pdf->write(0,$data->payable);

				if($data->number_of_bl == "3 ORIGINAL") {
		  			$pdf->SetXY(113, 209);
		  			$pdf->MultiCell(30,3,'3(THREE) ORIGINAL',0,'','');
				}

				$pdf->SetXY(140, 248);
				$region = $this->session->userdata('region_id');

				if ($region == 'Medan') {
					$region = 'Belawan';
				}

				$etd = date('d F Y', strtotime($data->expect_etd));

				$pdf->Write(
					0,
					strtoupper($region . ', ' . $etd)
				);

				$pdf->SetXY(125, 267);
				$pdf->write(0,'PT GATEWAY CONTAINER LINE');
				if(!empty($data->as_agent)){
				$pdf->SetXY(125, 269);
				$pdf->MultiCell(60,3,strtoupper($data->as_agent),0,'L');
				}
			}
			else
			{
				$pdf->SetAutoPageBreak(0, 0);
				$pdf->SetMargins(10,5,0,10);
				$pdf->SetXY(0, 17);
				$pdf->SetFont('Verdana','',12);
				$pdf->MultiCell(130, 5, $data['note'],0,'',false);
				$pdf->SetXY(220, 17);
				$pdf->SetFont('Verdana','B',14);
				$pdf->write(0, str_replace('%20',' ',$bl_no));
				$pdf->SetFont('Verdana','',12);
				$pdf->SetXY(0, 57);
				$pdf->MultiCell(130,5,($data['consignee_add']),0,'L');
				$pdf->SetXY(0, 101);
				$pdf->MultiCell(120,5,$data['notify_add'],0,'L');

				$pdf->SetXY(0, 144);
				$pdf->write(0, $port_of->port_of_receive);
				$pdf->SetXY(63, 144);
				$pdf->write(0, $port_of->port_of_loading);

				$connecting = $this->m_print_out_bl->get_connecting($spd,$data['vessel'].' '.$data['voyage']);
				$q = $this->dbx->get_where('tbl_detail',array('detail_id'=>$spd));
				if($connecting )
                                {
               $pdf->SetFont('Verdana','',12);
					$pdf->SetXY(0, 160);
					$pdf->write(0, $connecting->vessel);
					$pdf->SetXY(62, 151);
					if($q->row()->snd_vessel != '' || $q->row()->last_snd_vessel != '')
					{
					$pdf->SetFont('Verdana','',10.5);
					$pdf->write(0, 'Connecting Vessel');
					$pdf->SetFont('Verdana','',12);
					$pdf->SetXY(62, 160);
					$pdf->write(0, $connecting->conn_vessel);
					}
				}  
				$pdf->SetFont('Verdana','',11);
				$pdf->SetXY(141, 160);
				$pdf->write(0, $port_of->port_of_discharge);
				$pdf->SetXY(217, 160);
				$pdf->write(0, $port_of->port_of_delivery);
				$agent = $this->m_print_out_bl->getAgentByCode($data['agent_code']);	
            if($agent != 0 ) {
            	if ($agent['agent_company_remark'] != '')
            	{
						$pdf->SetXY(145, 98);
						$pdf->MULTICELL(140,4.5, $agent['agent_company_remark'],0,'L',false);
						$pdf->SetXY(145, 109);
						$pdf->write(0, $agent['agent_company_name'],0,'L',false);
						$pdf->SetXY(145, 111);
						$pdf->MultiCell(110,5, $agent['agent_company_address'],0,'L',false);
					}
					else
					{
                 	$pdf->SetXY(145, 103);
						$pdf->write(0, $agent['agent_company_name']);
						$pdf->SetXY(145, 105);
						$pdf->MultiCell(110,5, $agent['agent_company_address'],0,'L',false);
             	}
				}
				$pdf->SetFont('Verdana','',12);
				if($attc_marks > 18) {
					$pdf->SetXY(0, 185);
					$pdf->MultiCell(45,5, '"SEE ATTACHMENT LIST"',0,'C',false);
				} else {
					$pdf->SetXY(0, 185);
					$pdf->SetFont('Verdana','',12);
					$pdf->MultiCell(60,5,($data['marking_nos']),0,'L',false);
				}
		  			////double countener /////

				$count = $this->m_print_out_bl->get_count_double($sp);
				if($count->num_rows() > 0 ) {
                     $pdf->SetXY(5, 243);
							$pdf->write(0, 'CONTAINER / SEAL NO');
		  					$pdf->SetXY(0, 245);
		  					$pdf->SetMargins(0, 0);
							$pdf->Ln(2.5);
							foreach($count->result() as $row ) {
							$pdf->MultiCell(115,2.5, strtoupper($row->count_no).' / '. strtoupper($row->seal_no).' / '. strtoupper($row->count_size),0,'L',false);
							$pdf->Ln();
					}
			}
		  		else if ($last_container != '')
		  		{        $pdf->SetXY(0, 274);
		  					$pdf->SetFont('Verdana','',11);
							$pdf->write(0, 'CONTAINER / SEAL NO');
							$pdf->SetXY(0, 278);
							$pdf->MultiCell(85, 5, $last_container.' / '.$data['type_cont_size']);
		  				
		  		}
		  		
				
		  		////double countener /////
		  		
				
				
           if($fcl=='FCL') {

								$pdf->SetXY(60, 185);
								$dbl = $this->afg_m_stuffing_plan->get_double($sp);
								if($dbl->num_rows() > 0 ) {
								$pdf->Ln(1);
								foreach($dbl->result() as $row ) {
								$pdf->MultiCell(80,3, $row->count_num.' '.'X'.' '.$row->count_type,0,"R");
								$pdf->Ln(1);
							}
					   $pdf->SetXY(70, 291);
					   foreach($dbl->result() as $row ) {
														$pdf->Write(0,  $row->count_num.' ( '.$this->fungsi->num_to_words( $row->count_num,'',0,'').' ) '.' '.'X'.' '.$row->count_type.' ');
														}
				////end double countener /////
					   } else{
				        $pdf->SetXY(60, 185);
			            $pdf->MultiCell(25,3, $data['no_pack_qty'].' '.'X'.' '.$data['no_pack_unit']);
			            $pdf->SetXY(60, 291);
						$pdf->MultiCell(120,3, $data['no_pack_qty'].' ( '.$this->fungsi->num_to_words($data['no_pack_qty'],'',0,'').' ) '.' '.'X'.' '.$data['no_pack_unit'].' ONLY');
                           }
                        }else{
				$pdf->SetXY(58, 183);
				$pdf->MultiCell(30,5, $data['no_pack_qty'].' '.$data['no_pack_unit']);
				$pdf->SetXY(61, 290);
				$pdf->MultiCell(130,3, $data['no_pack_qty'].'( '.$this->fungsi->num_to_words($data['no_pack_qty'],'',0,'').')'.''.$data['no_pack_unit'].' ONLY');

                                }
				if($data['express_release'] == "TRUE") {
					$pdf->SetFont('Verdana','B',13);
					$pdf->SetXY(125, 275);
					$pdf->write(0, '" EXPRESS RELEASE "');
				}
				
				if($data['shipped_on_board'] == "TRUE")
{
				$pdf->SetXY(190, 249.5);
					$text  = "SHIPPED ON BOARD\n";
					$text .= "FROM ".$port_of->port_of_loading."\n";
					$text .= "ON ".strtoupper($tanggal)."\n";
					$text .= "BY ".$connecting->vessel;
					// $text .= "\nTO ".$port_of->port_of_discharge; // kalau mau ditampilkan juga
				$pdf->SetFont('Arial','',7);	
					// MultiCell(x width, y height per line, text, border, alignment)
					$pdf->MultiCell(60, 3, $text, 0, 'L');
				$pdf->SetFont('Verdana','',11);
				}

				if($fcl=='FCL') {
					$pdf->SetXY(90, 185);
					$pdf->write(0, 'SHIPPER LOAD, COUNT & SEAL CONTAINER,');
					$pdf->SetXY(90, 190);
					$pdf->write(0, 'SAID TO CONTAIN :');
				}
				else
				{
					$pdf->SetXY(95, 185);
					$pdf->write(0,'SAID TO CONTAIN :');
				}

				if($attc_discription > 15)
				{
					$pdf->SetXY(110, 235);
					$pdf->MultiCell(60,5,'"SEE ATTACHMENT LIST"');
				}
				else
				{
					$pdf->SetXY(95, 195);
					$pdf->SetFont('Verdana','',12);
					$pdf->MultiCell(110,5,mb_convert_encoding($data['desc_goods'], 'ISO-8859-1', 'UTF-8'),0,'L');
				}
				$pdf->SetFont('Verdana','',11);
				$pdf->SetXY(215, 185);
				$pdf->write(0, 'GW :');
				$pdf->SetXY(260, 184);
				$pdf->Cell(13,2,$this->fungsi->pecah3($data['weight'],','),0,0,'R',false,'');
				$pdf->SetXY(270, 185);
				$pdf->write(0, ' KGS');

				if($data['net_weight_tidak_dicetak'] == "FALSE" ) {
					$pdf->SetXY(215, 195);
					$pdf->write(0,'NW : ');
					$pdf->SetXY(260, 194);
					$pdf->Cell(13,2,$this->fungsi->pecah3($data['netto'],','),0,0,'R',false,'');
				
					$pdf->SetXY(270, 195);
					$pdf->write(0, ' KGS');
				}
                                if($data['bl_meas'] != '' ) {
				$pdf->SetXY(215, 205);
				$pdf->write(0,'MEAS : ');
				$pdf->SetXY(260, 204);
				$pdf->Cell(13,2,$this->fungsi->pecah4($data['bl_meas'],','),0,0,'R',false,'');
				$pdf->SetXY(270, 205);
				$pdf->write(0, ' M');
				$pdf->SetXY(273, 203);
				$pdf->SetFont('Arial','',10);
				$pdf->write(0, ' 3');  }

				$pdf->SetFont('Verdana','',12);
				$pdf->SetXY(20, 315);
				$pdf->write(0, 'FREIGHT');
				$pdf->SetXY(5, 322);
				$pdf->write(0,$payable);
            $q = $this->dbx->get_where('tbl_detail',array('detail_id'=>$spd));
				if($q->row()->payable == "AS ARRANGED")
					{
					$pdf->SetXY(5,319);
               $pdf->write(0, 'AS ARRANGED');
					}
					
				$caya = $this->m_print_out_bl->get_caya_caya($spd);
				$pdf->SetXY(17, 365);
				$pdf->SetFont('Arial','',9);
				$pdf->write(0, "Type of Movement :");
				$pdf->SetFont('Arial','',12);
				$pdf->SetXY(22, 369);
				$pdf->write(0, $caya);

				$freight_payable = $data['freight'];
				if($freight_payable == "PREPAID") {
		  			$pdf->SetXY(70, 315);
		  			$pdf->SetFont('Arial','',12);
		  			$pdf->MultiCell(40,3,$freight_payable,'','',false);
				} else {
		  			$pdf->SetXY(110, 315);
		  			$pdf->SetFont('Arial','',12);
		  			$pdf->MultiCell(40,3,$freight_payable,'','',false);
				}

				if($data['cetak_jml_doc_origi'] == "TRUE") {
		  			$pdf->SetXY(158, 315);
		  			$pdf->SetFont('Arial','',12);
		  			$pdf->MultiCell(50,3,'3(THREE) ORIGINAL',0,'','');
				}

				$pdf->SetXY(189, 377);
				$pdf->SetFont('Arial','',12);
				$pdf->MultiCell(73,1,strtoupper($this->session->userdata('region_id').', '.$tanggal),'','',false);
				if ($as_agent ==''){
				$pdf->SetXY(160, -10);
				$pdf->SetFont('Verdana','',12);
				$pdf->write(0,'PT GATEWAY CONTAINER LINE');}
				else{
				$pdf->SetXY(160, 399);
				$pdf->SetFont('Verdana','',12);
				$pdf->write(0,'PT GATEWAY CONTAINER LINE');
				$pdf->SetXY(160, 403);
				$pdf->MultiCell(100,5,strtoupper($as_agent),0,'L');}
			}
		if($attc_discription > 15 || $attc_marks > 18 ) {
			$pdf->AddPage('P','A4');
			$pdf->SetAutoPageBreak(true, 4) ;
			$pdf->SetXY(152, 23);
			$pdf->Image(APPPATH.'third_party/img/logo.png',140,17,+50);
			$pdf->SetFont('Arial','B',10);
			$pdf->SetXY(10, 15);
			$pdf->write(0, 'ATTACHMENT BILL OF LADING');
			$pdf->SetFont('Arial','',9);
			$pdf->SetXY(10, 25);			
			$pdf->write(0, 'BL NO.');
			$pdf->SetXY(40, 25);			
			$pdf->write(0, ':');
			$pdf->SetXY(45, 25);			
			$pdf->write(0, $data->house_bl);
			$pdf->SetXY(10, 30);			
			$pdf->write(0, 'SHIPPER');
			$pdf->SetXY(40, 30);
			$pdf->write(0, ':');
			$pdf->SetXY(45, 30);
			$pdf->Cell(7,0, strtoupper($data->shipper_name));
			$pdf->SetXY(10, 35);			
			$pdf->write(0, 'VESSEL');
			$pdf->SetXY(40, 35);			
			$pdf->write(0, ':');
			$pdf->SetXY(45, 35);			
			$pdf->write(0, $data->vessel);
			
			$pdf->Line(10,45,200,45);
			$pdf->Line(10,46,200,46);
			
	
				$pdf->SetFont('Arial','BU',8);
				$pdf->SetXY(100, 55);			
				$pdf->write(0, 'DESCRIPTION OF GOODS :');
				$pdf->SetFont('Arial','',9);
				$pdf->SetXY(100, 60);
				$text = html_entity_decode($data->description_of_goods, ENT_QUOTES, 'UTF-8');

						// Buang karakter non-printable dulu
						$text = preg_replace('/[^\P{C}\n]+/u', '', $text);

						// Konversi dengan ignore
						$text = iconv('UTF-8', 'Windows-1252//IGNORE', $text);
				$pdf->MultiCell(90,4,strtoupper($text),'','L',false);
			
		
				$pdf->SetFont('Arial','BU',8);
				$pdf->SetXY(10, 55);			
				$pdf->write(0, 'MARK AND NOS  :');
			
				$pdf->SetFont('Arial','',9);
				$pdf->SetXY(10, 60);
				$pdf->MultiCell(75,4,$data->marking,'','L',false);
		
			
			

		}
			$pdf->Output(rand().' Draft ' .$data->house_bl.'.pdf','I'); 
		}
	}
	function getMulticellLineCount($pdf, $width, $text) {
    // Ambil string menjadi array baris (jika mengandung \n)
    $lines = explode("\n", $text);
    $totalLines = 0;

    foreach ($lines as $line) {
        $words = explode(' ', $line);
        $currentLine = '';
        foreach ($words as $word) {
            $testLine = $currentLine . ($currentLine === '' ? '' : ' ') . $word;
            $lineWidth = $pdf->GetStringWidth($testLine);
            if ($lineWidth <= $width) {
                $currentLine = $testLine;
            } else {
                $totalLines++;
                $currentLine = $word;
            }
        }
        if ($currentLine !== '') {
            $totalLines++;
        }
    }

    return $totalLines;
}


}

