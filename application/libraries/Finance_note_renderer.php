<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Finance_note_renderer
 *
 * Renderer extracted from C_finance_process::print_out_new().
 * It appends one DN/CN to an existing FPDF_MC_Table and never calls Output()/exit.
 */
class Finance_note_renderer
{
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->model(array(
            'finance/m_finance_proses',
            'finance/m_inv_to_shipper',
        ));
        $this->CI->dbx = $this->CI->m_finance_proses->dbx;
        $this->CI->fungsi->dbx = $this->CI->m_finance_proses->dbx;
    }

    public function render($pdf, $finance, array $context = array())
    {
        if (!$finance) {
            throw new InvalidArgumentException('Finance document is required');
        }

        // x_finance_item.number berisi ID INTERNAL x_finance.id,
        // BUKAN nomor dokumen yang terlihat (mis. IN/2608825).
        // Finance Process::print_out_new() juga menerima x_finance.id sebagai "id".
        $id     = (int)($finance->id ?? 0);
        $jenis  = strtoupper(trim((string)($finance->jenis ?? '')));
        $no_doc = trim((string)($finance->number ?? ''));

        if ($id <= 0) {
            throw new InvalidArgumentException(
                'ID internal x_finance tidak tersedia untuk DN/CN '.$no_doc
            );
        }

        $dateStr  = $this->formatDate((string)$finance->date);
        $dateObj  = DateTime::createFromFormat('d-m-Y', $dateStr);
        $intlDate = $dateObj ? $dateObj->format('d-F-Y') : $dateStr;

        $toName  = (string)($context['to_name'] ?? $finance->kepada_kode);
        $toTaxId = (string)($context['to_tax_id'] ?? '');
        $toTel   = (string)($context['to_tel'] ?? '');
        $toEmail = (string)($context['to_email'] ?? '');

        $vessel      = (string)($context['vessel'] ?? '');
        $etd         = (string)($context['etd'] ?? '');
        $eta         = (string)($context['eta'] ?? '');
        $origin      = (string)($context['origin'] ?? '');
        $destination = (string)($context['destination'] ?? '');
        $term        = ($jenis != 'INVOICE') ? 'COLLECT' : 'PREPAID';

        $manifest  = (string)($context['manifest'] ?? '');
        $container = (string)($context['container'] ?? '');
        $mbl       = (string)($context['mbl'] ?? '');
        $hbl       = 'SEE DETAIL OF CHARGES';

        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetLineWidth(0.2);
        $pdf->AddPage('P','A4');

    $region  = $this->CI->session->userdata('region_id');
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
    $items = $this->CI->m_finance_proses->tampil_data([
        'table'  => 'x_finance_item',
        'number' => $id  // foreign key -> x_finance.id
    ]);

    // Jangan pernah menghasilkan PDF DN kosong secara diam-diam.
    // Jika tidak ada detail, hentikan pembuatan Pre Alert sebelum audit download.
    $itemRows = ($items && is_object($items) && method_exists($items, 'result'))
        ? $items->result()
        : [];
    if (empty($itemRows)) {
        log_message('error', '[PRE_ALERT_FINANCE_EMPTY] x_finance.id='.$id
            .' document='.$no_doc.' jenis='.$jenis);
        throw new RuntimeException(
            'Tidak ada x_finance_item untuk x_finance.id='.$id.' ('.$no_doc.')'
        );
    }

    // Konsisten dengan C_finance_process::print_out_new():
    // CTLHPH memiliki nama item khusus pada DN/CN.
    // Kode agent harus berasal dari header x_finance dokumen ini,
    // bukan hanya pilihan agent pada form Pre Alert.
    $agent_code = strtoupper(trim((string)($finance->kepada_kode ?? '')));

    $ocean = [];
    $rebate = [];
    foreach ($itemRows as $row) {
        $nm = strtoupper((string)$row->item);

        // Kategori ditentukan SEBELUM mengganti nama yang akan dicetak.
        // Ini menjaga item REBATE tetap di section REBATE.
        $is_rebate = (strpos($nm, 'REBATE') !== false);

        // Hanya ubah label cetak, tidak melakukan UPDATE x_finance_item.
        if ($agent_code === 'CTLHPH' && in_array($jenis, ['DEBIT NOTE', 'CREDIT NOTE'], true)) {
            if ($nm === 'REBATE AGENT VOLUME') {
                $row->item = 'FREIGHT COST SHARING /CBM';
            } elseif ($nm === 'REBATE AGENT SET') {
                $row->item = 'FREIGHT COST SHARING /DOC';
            }
        }

        if ($is_rebate) $rebate[] = $row;
        else $ocean[] = $row;
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

			$unit = $cur.' '.$this->CI->fungsi->pecah($r->unit_price, ',');
			$amt  = $cur.' '.$this->CI->fungsi->pecah($r->price, ',');

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

	$jumlah_idr = $this->CI->m_inv_to_shipper->get_ttl_db_note($id,'RP.');
	$jumlah_usd = $this->CI->m_inv_to_shipper->get_ttl_db_note($id,'USD');

	if(!empty($jumlah_usd)) $ttl_usd = $this->CI->fungsi->pecah(array_sum($jumlah_usd),',');
	if(!empty($jumlah_idr)) $ttl_idr = $this->CI->fungsi->pecah(array_sum($jumlah_idr),',');

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
require_once(FCPATH.'pdf/phpqrcode/qrlib.php');

// pakai URL internal + token (jangan expose API key)
$token  = $this->makeQrToken($no_doc);
$qrText = 'https://gclid.cloud/verify/invoice?number='.rawurlencode($no_doc).'&t='.$token;

// folder temp
$tmpDir = FCPATH.'pdf/tmp_qr/';
if (!is_dir($tmpDir)) @mkdir($tmpDir, 0775, true);

$safeId = preg_replace('/[^A-Za-z0-9_-]+/', '_', (string)$id);
$qrPath = $tmpDir.'dn_'.$safeId.'_'.time().'.png';

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


    }

    protected function formatDate($raw)
    {
        $raw = trim((string)$raw);
        if ($raw === '') return date('d-m-Y');

        if (preg_match('/^\d{8}$/', $raw)) {
            $d = DateTime::createFromFormat('Ymd', $raw);
            if ($d) return $d->format('d-m-Y');
        }

        foreach (array('Y-m-d', 'd-m-Y', 'd/m/Y') as $fmt) {
            $d = DateTime::createFromFormat($fmt, $raw);
            if ($d) return $d->format('d-m-Y');
        }

        return $raw;
    }

    protected function makeQrToken($docNo)
    {
        $secret = (string)$this->CI->config->item('qr_secret');
        return hash_hmac('sha256', (string)$docNo, $secret);
    }
}
