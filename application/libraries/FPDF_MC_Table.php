<?php 
require('fpdf.php');

class FPDF_MC_Table extends FPDF {
	
	var $widths;
	var $aligns;
	public $data;
	
  function getNumLines($w, $h, $txt) {
        $cw = &$this->CurrentFont['cw'];
        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', $txt);
        $nb = strlen($s);
        if ($nb > 0 && $s[$nb - 1] == "\n") $nb--;
        $sep = -1; $i = 0; $j = 0; $l = 0; $nl = 1;

        while ($i < $nb) {
            $c = $s[$i];
            if ($c == "\n") { $i++; $sep = -1; $j = $i; $l = 0; $nl++; continue; }
            if ($c == ' ') $sep = $i;
            $l += $cw[$c] ?? 0;
            if ($l > $wmax) {
                if ($sep == -1) { if ($i == $j) $i++; }
                else $i = $sep + 1;
                $sep = -1; $j = $i; $l = 0; $nl++;
            } else $i++;
        }
        return $nl;
    }



	
	function SetWidths($w)
	{
    	//Set the array of column widths
    	$this->widths=$w;
	}

	function SetAligns($a)
	{
    	//Set the array of column alignments
    	$this->aligns=$a;
	}
	
	function pecah($uang,$delimiter='.'){
		if($uang == '' || $uang == 0 ){
			$rupiah = '';
			return $rupiah;
		}
		$neg = false;
		if($uang<0){
			$neg = true;
			$uang = abs($uang);
		}
		$rupiah = number_format($uang,2,'.',$delimiter);
		
		if($neg){
			$rupiah = '- '.$rupiah;
		}
		return $rupiah;
	}
	
	function RowCollor($data)
	{
		
	}

function Row($data)
{
    // Tentukan tinggi minimum
    $minHeight = 10; // Ganti dengan tinggi minimum yang diinginkan

    // Hitung tinggi baris
    $nb = 0;
    for ($i = 0; $i < count($data); $i++) {
        $nb = max($nb, $this->NbLines($this->widths[$i], $data[$i]));
    }
    $h = 5 * $nb;

    // Pastikan tinggi tidak kurang dari tinggi minimum
    $h = max($h, $minHeight);

    // Periksa apakah perlu melakukan pemisahan halaman
    $this->CheckPageBreak($h);

    // Gambar sel-sel dari baris
    for ($i = 0; $i < count($data); $i++) {
        $w = $this->widths[$i];
        $a = isset($this->aligns[$i]) ? $this->aligns[$i] : 'C'; // Ubah default ke 'C' untuk center
        // Simpan posisi saat ini
        $x = $this->GetX();
        $y = $this->GetY();
        // Gambar border
        $this->Rect($x, $y, $w, $h);
        
        // Hitung posisi Y untuk teks agar berada di tengah
        $textHeight = $this->GetStringHeight($w, $data[$i]);
        $textY = $y + ($h - $textHeight) / 2; // Pindahkan ke tengah

        // Cetak teks
        $this->SetXY($x, $textY);
        $this->MultiCell($w, 5, $data[$i], 0, $a);
        
        // Pindahkan posisi ke kanan dari sel
        $this->SetXY($x + $w, $y);
    }
    // Pindah ke baris berikutnya
    $this->Ln($h);
}

// Tambahkan fungsi untuk menghitung tinggi string
function GetStringHeight($width, $text)
{
    // Hitung tinggi string berdasarkan lebar dan teks
    $lines = $this->NbLines($width, $text);
    return 5 * $lines; // Sesuaikan dengan tinggi baris yang digunakan
}


	function CheckPageBreak($h)
	{
    	//If the height h would cause an overflow, add a new page immediately
    	if($this->GetY()+$h>$this->PageBreakTrigger) $this->AddPage($this->CurOrientation,$this->CurPageSize);
	}

	function NbLines($w, $txt)
	{
    	//Computes the number of lines a MultiCell of width w will take
    	$cw=&$this->CurrentFont['cw'];
    	if($w==0)
        $w=$this->w-$this->rMargin-$this->x;
    	$wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
    	$s=str_replace("\r", '', $txt ?? '');
    	$nb=strlen($s);
    	if($nb>0 and $s[$nb-1]=="\n")
        $nb--;
    	$sep=-1;
    	$i=0;
    	$j=0;
    	$l=0;
    	$nl=1;
    	while($i<$nb)
    	{
        	$c=$s[$i];
        	if($c=="\n")
        	{
            $i++;
            $sep=-1;
            $j=$i;
            $l=0;
            $nl++;
            continue;
        	}
        	if($c==' ')
            $sep=$i;
        	$l+=$cw[$c];
        	if($l>$wmax)
        	{
            if($sep==-1)
            {
                if($i==$j)
                    $i++;
            }
            else
                $i=$sep+1;
            $sep=-1;
            $j=$i;
            $l=0;
            $nl++;
        	}
        	else
            $i++;
    	}
    	return $nl;
	}
	
	function Header() 
	{
	if(is_array($this->data)):	
		if($this->data['report']=='agenda_invoice') 
		{
   		$this->SetFont('Arial','B',18);
    		$this->Cell(30,10,'AGENDA INVOICE & CN',0,0,'L');
    		$this->Ln(5);
    		$this->SetFont('Arial','B',12);
    		$this->Cell(30,10,'PERIODE :'.$this->data['dari'].' S/D '.$this->data['sampai'],0,0,'L');
    		$this->Ln();    	
      		$this->SetFont('Arial','B',8);   
			$this->SetFillColor(230,230,230);
			$this->SetTextColor(100,100,100);
			$this->SetXY(10, 25);
			$this->MultiCell(10,8,'No',1,'C',true);
			$this->SetXY(20, 25);
			$this->MultiCell(40,8,'JOB NO',1,'C',true);
			$this->SetXY(60, 25);
			$this->MultiCell(25,8,'NO.REF',1,'C',true);
			$this->SetXY(85, 25);
			$this->MultiCell(20,8,'TGL REF',1,'C',true);
			$this->SetXY(105, 25);
			$this->MultiCell(15,8,'CURR',1,'C',true);
			$this->SetXY(120, 25);
			$this->MultiCell(40,8,'BL NO.',1,'C',true);
			$this->SetXY(160, 25);
			$this->MultiCell(117,8,'RECEIVED FROM',1,'C',true);
			$this->SetXY(277, 25);
			$this->MultiCell(30,8,'AMOUNT',1,'C',true);
			$this->SetXY(307, 25);
			$this->MultiCell(30,8,'PAID',1,'C',true);
    		$this->Ln(1);
    	}
    	else if($this->data['report']=='report_job_sheet') 
    	{
   		    $this->SetFont('Arial','B',14);
    		$this->Cell(30,10,'TOTAL JOB PERBULAN',0,0,'L');
    		$this->Ln(5);
    		$this->SetFont('Arial','B',12);
    		$this->Cell(30,10,'PERIODE : '.$this->data['bulan'],0,0,'L');
    		$this->Ln();    	
      	    $this->SetFont('Arial','B',8);   
			$this->SetFillColor(230,230,230);
			$this->SetTextColor(100,100,100);		
			$this->SetXY(10, 25);
			$this->MultiCell(10,8,'No',1,'C',true);
			$this->SetXY(20, 25);
			$this->MultiCell(40,8,'REF NO',1,'C',true);
			$this->SetXY(60, 25);
			$this->MultiCell(25,8,'SHIPPER',1,'C',true);
			$this->SetXY(85, 25);
			$this->MultiCell(20,8,'PACKAGING',1,'C',true);
			$this->SetXY(105, 25);
			$this->MultiCell(20,8,'VOLUME',1,'C',true);
			$this->SetXY(125, 25);
			$this->MultiCell(20,8,'CONTAINER',1,'C',true);
			$this->SetXY(145, 25);
			$this->MultiCell(30,8,'POL / PO',1,'C',true);
			$this->SetXY(175, 25);
			$this->MultiCell(30,8,'CARRIER',1,'C',true);
			$this->SetXY(205, 25);
			$this->MultiCell(25,8,'H.BL / M.BL',1,'C',true);
			$this->SetXY(230, 25);
			$this->MultiCell(70,8,'SCHEDULE DETAIL',1,'C',true);
			$this->SetXY(300, 25);
			$this->MultiCell(45,8,'SEAL NO / CONT NO',1,'C',true);
    		$this->Ln(1);
    	}
		else if($this->data['report']=='3') 
		{
			$this->SetFont('Arial','B',12);
    		$this->Cell(30,10,'PT. GATEWAY CONTAINER LINE',0,0,'L');
    		$this->Ln(4);
    		$this->SetFont('Arial','',8);
    		$this->Cell(30,10,'REKAP SALES COMMISSION',0,0,'L');
    		$this->Ln(4);
    		$this->Cell(30,10,'TANGGAL : '.$this->data['tgl_awal'].' S/D '.$this->data['tgl_akhir'],0,0,'L');
    		$this->SetFont('Arial','B',8);
    		$this->Ln(4);
    		$this->Cell(30,10,'SHIPPER : '.$this->data['shipper'],0,0,'L');
    		$this->Ln();    	
      	$this->SetFont('Arial','B',8);   
			$this->SetFillColor(230,230,230);
			$this->SetTextColor(100,100,100);		
			$this->SetXY(10, 30);
			$this->MultiCell(10,6,'No',1,'C',true);
			$this->SetXY(20, 30);
			$this->MultiCell(35,6,'JOB NO',1,'C',true);
			$this->SetXY(55, 30);
			$this->MultiCell(35,6,'DESTINATION',1,'C',true);
			$this->SetXY(90, 30);
			$this->MultiCell(15,6,'QTY',1,'C',true);
			$this->SetXY(105, 30);
			$this->MultiCell(15,6,'MCHG',1,'C',true);
			$this->SetXY(120, 30);
			$this->MultiCell(30,6,'PRICE',1,'C',true);
			$this->SetXY(150, 30);
			$this->MultiCell(20,6,'CURRENCY',1,'C',true);
			$this->SetXY(170, 30);
			$this->MultiCell(30,6,'COMMISSION',1,'C',true);
    		$this->Ln(1);
		}
		else if($this->data['report']=='4') 
		{
			$this->SetFont('Arial','B',12);
    		$this->Cell(30,10,'PT. GATEWAY CONTAINER LINE',0,0,'L');
    		$this->Ln(4);
    		$this->SetFont('Arial','',8);
    		$this->Cell(30,10,'REKAP DISCOUNT / REBATE SHIPPER',0,0,'L');
    		$this->Ln(4);
    		$this->Cell(30,10,'TANGGAL : '.$this->data['tgl_awal'].' S/D '.$this->data['tgl_akhir'],0,0,'L');
    		$this->SetFont('Arial','B',8);
    		$this->Ln(4);
    		$this->Cell(30,10,'SHIPPER : '.$this->data['shipper'],0,0,'L');
    		$this->Ln();    	
      	$this->SetFont('Arial','B',8);   
			$this->SetFillColor(230,230,230);
			$this->SetTextColor(100,100,100);		
			$this->SetXY(10, 30);
			$this->MultiCell(10,6,'No',1,'C',true);
			$this->SetXY(20, 30);
			$this->MultiCell(35,6,'JOB NO',1,'C',true);
			$this->SetXY(55, 30);
			$this->MultiCell(35,6,'DESTINATION',1,'C',true);
			$this->SetXY(90, 30);
			$this->MultiCell(15,6,'QTY',1,'C',true);
			$this->SetXY(105, 30);
			$this->MultiCell(15,6,'MCHG',1,'C',true);
			$this->SetXY(120, 30);
			$this->MultiCell(30,6,'PRICE',1,'C',true);
			$this->SetXY(150, 30);
			$this->MultiCell(20,6,'CURRENCY',1,'C',true);
			$this->SetXY(170, 30);
			$this->MultiCell(30,6,'COMMISSION',1,'C',true);
    		$this->Ln(1);
		}
		else if($this->data['report']=='5') 
		{
			$this->SetFont('Arial','B',12);
    		$this->Cell(30,10,'PT. GATEWAY CONTAINER LINE',0,0,'L');
    		$this->Ln(4);
    		$this->SetFont('Arial','',8);
    		$this->Cell(30,10,'LAPORAN PER  SALESMAN',0,0,'L');
    		$this->Ln(4);
    		$this->Cell(30,10,'TANGGAL : '.$this->data['tgl_awal'].' S/D '.$this->data['tgl_akhir'],0,0,'L');
    		$this->SetFont('Arial','B',8);
    		$this->Ln(4);
    		$this->Cell(30,10,'SALES : '.$this->data['sales'],0,0,'L');
    		$this->Ln();    	
      	$this->SetFont('Arial','B',8);   
			$this->SetFillColor(230,230,230);
			$this->SetTextColor(100,100,100);		
			$this->SetXY(10, 30);
			$this->MultiCell(10,6,'No',1,'C',true);
			$this->SetXY(20, 30);
			$this->MultiCell(35,6,'JOB NO',1,'C',true);
			$this->SetXY(55, 30);
			$this->MultiCell(25,6,'TGL JOB',1,'C',true);
			$this->SetXY(80, 30);
			$this->MultiCell(25,6,'BL NO',1,'C',true);
			$this->SetXY(105, 30);
			$this->MultiCell(35,6,'VESEL',1,'C',true);
			$this->SetXY(140, 30);
			$this->MultiCell(30,6,'DESTINATION',1,'C',true);
			$this->SetXY(170, 30);
			$this->MultiCell(100,6,'DESCRIPTION OF GOOD',1,'C',true);
			$this->SetXY(270, 30);
			$this->MultiCell(30,6,'SHIPPER',1,'C',true);
			$this->SetXY(300, 30);
			$this->MultiCell(15,6,'MEAS',1,'C',true);
			$this->SetXY(315, 30);
			$this->MultiCell(30,6,'PROFIT',1,'C',true);
    		$this->Ln(1);
		}
		else if($this->data['report']=='6') 
		{
			$this->SetFont('Arial','B',12);
    		$this->Cell(30,10,'PT. GATEWAY CONTAINER LINE',0,0,'L');
    		$this->Ln(4);
    		$this->SetFont('Arial','',8);
    		$this->Cell(30,10,'LAPORAN PER  SHIPPER',0,0,'L');
    		$this->Ln(4);
    		$this->Cell(30,10,'TANGGAL : '.$this->data['tgl_awal'].' S/D '.$this->data['tgl_akhir'],0,0,'L');
    		$this->SetFont('Arial','B',8);
    		$this->Ln(4);
    		$this->Cell(30,10,'EXPORTIR : '.$this->data['sales_id'],0,0,'L');
    		$this->Ln();    	
      	$this->SetFont('Arial','B',8);   
			$this->SetFillColor(230,230,230);
			$this->SetTextColor(100,100,100);		
			$this->SetXY(10, 30);
			$this->MultiCell(10,6,'No',1,'C',true);
			$this->SetXY(20, 30);
			$this->MultiCell(35,6,'JOB NO',1,'C',true);
			$this->SetXY(55, 30);
			$this->MultiCell(25,6,'TGL JOB',1,'C',true);
			$this->SetXY(80, 30);
			$this->MultiCell(25,6,'BL NO',1,'C',true);
			$this->SetXY(105, 30);
			$this->MultiCell(35,6,'VESEL',1,'C',true);
			$this->SetXY(140, 30);
			$this->MultiCell(30,6,'DESTINATION',1,'C',true);
			$this->SetXY(170, 30);
			$this->MultiCell(100,6,'DESCRIPTION OF GOOD',1,'C',true);
			$this->SetXY(270, 30);
			$this->MultiCell(30,6,'SHIPPER',1,'C',true);
			$this->SetXY(300, 30);
			$this->MultiCell(15,6,'MEAS',1,'C',true);
			$this->SetXY(315, 30);
			$this->MultiCell(30,6,'PROFIT',1,'C',true);
    		$this->Ln(1);
		}
		else if($this->data['report']=='7') 
		{
			$this->SetFont('Arial','B',12);
    		$this->Cell(30,10,'PT. GATEWAY CONTAINER LINE',0,0,'L');
    		$this->Ln(4);
    		$this->SetFont('Arial','',8);
    		$this->Cell(30,10,'REKAP JOBSHEET PERIODE : '.$this->data['periode'],0,0,'L');
    		$this->Ln();    	
			$this->SetFont('Arial','B',8);   
			$this->SetFillColor(230,230,230);
			$this->SetTextColor(100,100,100);		
			$this->SetXY(10, 22);
			$this->MultiCell(10,6,'No',1,'C',true);
			$this->SetXY(20, 22);
			$this->MultiCell(32,6,'JOB NO #',1,'C',true);
			$this->SetXY(52, 22);
			$this->MultiCell(68,6,'SHIPPER/CONSIGNEE',1,'C',true);
			$this->SetXY(120, 22);
			$this->MultiCell(25,6,'INCOME',1,'C',true);
			$this->SetXY(145, 22);
			$this->MultiCell(25,6,'COST',1,'C',true);
			$this->SetXY(170, 22);
			$this->MultiCell(25,6,'PROFIT / LOST',1,'C',true);
    		$this->Ln(1);
		}
		else if($this->data['report']=='8') 
		{
			$this->SetFont('Arial','B',12);
    		$this->Cell(30,10,'ANALISA JOB',0,0,'L');
    		$this->Ln(4);
    		$this->SetFont('Arial','',8);
    		$this->Cell(30,10,'PERIODE : '.$this->data['periode'],0,0,'L');
    		$this->Ln();
			$this->Cell(340,5,'PAGE :'.$this->PageNo().'/{nb}',0,0,'R',false);
			$this->Ln();
			$this->SetFont('Arial','B',8);   
			$this->SetFillColor(230,230,230);
			$this->SetTextColor(100,100,100);		
			$this->SetXY(10, 22);
			$this->MultiCell(10,6,'No',1,'C',true);
			$this->SetXY(20, 22);
			$this->MultiCell(100,6,'SHIPPER / CONSIGNEE',1,'C',true);
			$this->SetXY(120, 22);
			$this->MultiCell(30,6,'JUMLAH BOOKING',1,'C',true);
			$this->SetXY(150, 22);
			$this->MultiCell(45,6,'INCOME',1,'C',true);
			$this->SetXY(195, 22);
			$this->MultiCell(45,6,'COST',1,'C',true);
			$this->SetXY(240, 22);
			$this->MultiCell(45,6,'PROFIT',1,'C',true);
		}
		else if($this->data['report']=='9') 
		{
			$this->SetFont('Arial','B',12);
    		$this->Cell(30,10,'LISTING JOB YG BELUM DI BUAT INVOICE',0,0,'L');
    		$this->Ln(4);
    		$this->SetFont('Arial','',8);
    		$this->Cell(30,10,'PERIODE : '.$this->data['periode'],0,0,'L');
    		$this->Ln();    	
      	$this->SetFont('Arial','B',8);   
			$this->SetFillColor(230,230,230);
			$this->SetTextColor(100,100,100);		
			$this->SetXY(10, 22);
			$this->MultiCell(10,6,'No',1,'C',true);
			$this->SetXY(20, 22);
			$this->MultiCell(30,6,'JOB NO #',1,'C',true);
			/* $this->SetXY(50, 22);
			$this->MultiCell(20,6,'KODE',1,'C',true); */
			$this->SetXY(50, 22);
			$this->MultiCell(35,6,'NAMA SERVICE',1,'C',true);
			$this->SetXY(85, 22);
			$this->MultiCell(10,6,'P/C',1,'C',true);
			$this->SetXY(95, 22);
			$this->MultiCell(70,6,'TAGIHAN KE',1,'C',true);
			$this->SetXY(165, 22);
			$this->MultiCell(10,6,'MU',1,'C',true);
			$this->SetXY(175, 22);
			$this->MultiCell(25,6,'JUMLAH',1,'C',true);
    		$this->Ln(1);
		}
		else if($this->data['report']=='10')
		{
			$this->Line(8,9,345,9);
			$this->SetFont('Arial','B',8);   
			$this->SetFillColor(230,230,230);
			$this->SetTextColor(100,100,100);
			$this->SetXY(8, 10);
			$this->MultiCell(10,10,'No','TBL','C',true);
			$this->SetXY(18, 10);
			$this->MultiCell(45,10,'NAME OF SHIPPER','TBL','C',true);
			$this->SetXY(73, 10);
			$this->MultiCell(25,5,'NO OF','TL','C',true);
			$this->SetXY(73, 15);
			$this->MultiCell(25,5,'PACKAGES','BL','C',true);
			$this->SetXY(98, 10);
			$this->MultiCell(45,5,'DISCRIPTION OF','TL','C',true);
			$this->SetXY(98, 15);
			$this->MultiCell(45,5,'GOODS','BL','C',true);
			$this->SetXY(143, 10);
			$this->MultiCell(20,10,'GW','TBL','C',true);
			$this->SetXY(163, 10);
			$this->MultiCell(20,10,'NW','TBL','C',true);
			$this->SetXY(183, 10);
			$this->MultiCell(20,5,'SHIPPER','TL','C',true);
			$this->SetXY(183, 15);
			$this->MultiCell(20,5,'MEAS','BL','C',true);
			$this->SetXY(203, 10);
			$this->MultiCell(20,5,'W/HOUSE','TL','C',true);
			$this->SetXY(203, 15);
			$this->MultiCell(20,5,'MEAS','BL','C',true);
			$this->SetXY(223, 10);
			$this->MultiCell(20,5,'B/L','TL','C',true);
			$this->SetXY(223, 15);
			$this->MultiCell(20,5,'MEAS','BL','C',true);
			$this->SetXY(243, 10);
			$this->MultiCell(40,10,'DESTINATION','TBL','C',true);
			$this->SetXY(283, 10);
			$this->MultiCell(62,10,'SHIPPING MARKS','TBLR','C',true);
		}
		else if($this->data['report']=='11')
		{
			$this->SetFont('Arial','B',7);
			$this->SetFillColor(230,230,230);
			$this->SetTextColor(100,100,100);
			$this->SetXY(5, 40);
			$this->MultiCell(7,8,'No',1,'C',true);
			$this->SetXY(12, 40);
			$this->MultiCell(23,8,'B/L NO',1,'C',true);
			$this->SetXY(35, 40);
			$this->MultiCell(45,8,'SHIPPER',1,'C',true);
			$this->SetXY(80, 40);
			$this->MultiCell(14,8,'MEAS',1,'C',true);
			$this->SetXY(94, 40);
			$this->MultiCell(14,8,'KURS',1,'C',true);
			$this->SetXY(108, 40);
			$this->MultiCell(16,4,'INVOICE TO AGENT',1,'C',true);
			$this->SetXY(124, 40);
			$this->MultiCell(16,4,'CR NOTE TO AGENT',1,'C',true);
				
			$this->SetXY(140, 40);
			$this->MultiCell(38,4,'INCOME',1,'C',true);
			$this->SetXY(140, 44);
			$this->MultiCell(16,4,'USD',1,'C',true);
			$this->SetXY(156, 44);
			$this->MultiCell(22,4,'IDR',1,'C',true);	
			
			$this->SetXY(178, 40);
			$this->MultiCell(38,4,'COST',1,'C',true);
			$this->SetXY(178, 44);
			$this->MultiCell(16,4,'USD',1,'C',true);
			$this->SetXY(194, 44);
			$this->MultiCell(22,4,'IDR',1,'C',true);
		
			$this->SetXY(216, 40);
			$this->MultiCell(38,4,'PROFIT / LOST',1,'C',true);
			$this->SetXY(216, 44);
			$this->MultiCell(16,4,'USD',1,'C',true);
			$this->SetXY(232, 44);
			$this->MultiCell(22,4,'IDR',1,'C',true);	
			$this->SetXY(254, 40);
			$this->MultiCell(20,8,'TOTAL',1,'C',true);
			$this->Ln(1);
		}
		else if($this->data['report']=='12')
		{
			$var = '';
			$this->SetFont('Arial','B',12);
    		$this->Cell(286,5,'CARGO MANIFEST',0,1,'C');
    		$this->SetFont('Arial','',11);
    		$this->Cell(286,3,'PT. GATEWAY CONTAINER LINE',0,1,'C');
    		$this->SetFont('Arial','',10);
    		
    		$this->Cell(35,5,'NAME  OF  VESSEL',0,0,'L');
    		$this->Cell(4,5,':',0,0,'L');
    		$this->Cell(170,5,$this->data['vessel'],0,0,'L');
    		$this->Cell(30,5,'Port of Loading',0,0,'L');
    		$this->Cell(4,5,':',0,0,'L');
    		$this->Cell(38,5,$this->data['port_loading'],0,1,'L');
    		
    		$this->Cell(35,5,'E.T.D. JKT',0,0,'L');
    		$this->Cell(4,5,':',0,0,'L');
    		$this->Cell(170,5,$this->data['etd_jkt'],0,0,'L');
    		$this->Cell(30,5,'Port of Discharge',0,0,'L');
    		$this->Cell(4,5,':',0,0,'L');
    		$this->Cell(38,5,$this->data['transhipment_port'],0,1,'L');
    		
    		$this->Cell(35,5,'CONT. / SEAL',0,0,'L');
    		$this->Cell(4,5,':',0,0,'L');
    		$this->Cell(170,5,$this->data['cont_seal_no'],0,0,'L');
    		$this->Cell(30,5,'Port of Destination',0,0,'L');
    		$this->Cell(4,5,':',0,0,'L');
    		$this->Cell(38,5,$this->data['destination'],0,1,'L');
    		$this->line(5,30,290,30);
    		//$this->Ln(2.5);
    		$this->SetXY(5, 31);
			$this->MultiCell(10,10,'No','TBL','C',false);
			$this->SetXY(15, 31);
			$this->MultiCell(30,10,'H BL NO.','TBL','C',false);
			$this->SetXY(45, 31);
			$this->MultiCell(60,5,'SHIPER CONSIGNEE NOTIFY PARTY','TBL','C',false);
			$this->SetXY(105, 31);
			$this->MultiCell(40,10,'MARKING','TBL','C',false);
			$this->SetXY(145, 31);
			$this->MultiCell(70,10,'DESCRIPTION OF GOODS','TBL','C',false);
			$this->SetXY(215, 31);
			$this->MultiCell(30,10,'DESTINATION','TBL','C',false);
			$this->SetXY(245, 31);
			$this->MultiCell(25,5,'GW NW MEAS','TBL','C',false);
			$this->SetXY(270, 31);
			$this->MultiCell(20,5,'FREIGHT CHARGES','TBRL','C',false);
		}
		
		else if($this->data['report']=='13') 
		{
			$this->SetFont('Arial','B',12);
    		$this->Cell(30,10,'PT. GATEWAY CONTAINER LINE',0,0,'L');
    		$this->Ln(4);
    		$this->SetFont('Arial','',8);
    		$this->Cell(30,10,'LAPORAN PER CONSIGNEE',0,0,'L');
    		$this->Ln(4);
    		$this->Cell(30,10,'TANGGAL : '.$this->data['tgl_awal'].' S/D '.$this->data['tgl_akhir'],0,0,'L');
    		$this->SetFont('Arial','B',8);
    		$this->Ln(4);
    		$this->Cell(30,10,'CONSIGNEE: '.$this->data['consignee'],0,0,'L');
    		$this->Ln();    	
      	$this->SetFont('Arial','B',8);   
			$this->SetFillColor(230,230,230);
			$this->SetTextColor(100,100,100);		
			$this->SetXY(10, 30);
			$this->MultiCell(10,6,'No',1,'C',true);
			$this->SetXY(20, 30);
			$this->MultiCell(35,6,'JOB NO',1,'C',true);
			$this->SetXY(55, 30);
			$this->MultiCell(25,6,'TGL JOB',1,'C',true);
			$this->SetXY(80, 30);
			$this->MultiCell(25,6,'BL NO',1,'C',true);
			$this->SetXY(105, 30);
			$this->MultiCell(35,6,'VESEL',1,'C',true);
			$this->SetXY(140, 30);
			$this->MultiCell(30,6,'DESTINATION',1,'C',true);
			$this->SetXY(170, 30);
			$this->MultiCell(100,6,'DESCRIPTION OF GOOD',1,'C',true);
			$this->SetXY(270, 30);
			$this->MultiCell(30,6,'SHIPPER',1,'C',true);
			$this->SetXY(300, 30);
			$this->MultiCell(15,6,'MEAS',1,'C',true);
			$this->SetXY(315, 30);
			$this->MultiCell(30,6,'PROFIT',1,'C',true);
    		$this->Ln(1);
		}
		else if($this->data['report']=='14') 
		{
			$this->SetFont('Arial','B',12);
    		$this->Cell(30,10,'PT. GATEWAY CONTAINER LINE',0,0,'L');
    		$this->Ln(4);
    		$this->SetFont('Arial','',8);
    		$this->Cell(30,10,'LAPORAN RL PER JOB: '.$this->data['periode'],0,0,'L');
    		$this->Ln();    	
			$this->SetFont('Arial','B',8);   
			$this->SetFillColor(230,230,230);
			$this->SetTextColor(100,100,100);		
			$this->SetXY(10, 22);
			$this->MultiCell(10,6,'No',1,'C',true);
			$this->SetXY(20, 22);
			$this->MultiCell(27,6,'JOB NO #',1,'C',true);
			$this->SetXY(47, 22);
			$this->MultiCell(20,6,'ETD/ETA',1,'C',true);
			$this->SetXY(67, 22);
			$this->MultiCell(30,6,'DESTINATION',1,'C',true);
			$this->SetXY(97, 22);
			$this->MultiCell(15,6,'KURS',1,'C',true);
			$this->SetXY(112, 22);
			$this->MultiCell(15,6,'TYPE',1,'C',true);
			$this->SetXY(127, 22);
			$this->MultiCell(15,6,'INC USD',1,'C',true);
			$this->SetXY(142, 22);
			$this->MultiCell(30,6,'INCOME IDR',1,'C',true);
			$this->SetXY(172, 22);
			$this->MultiCell(25,6,'TOTAL INCOME',1,'C',true);
			$this->SetXY(197, 22);
			$this->MultiCell(15,6,'USD',1,'C',true);
			$this->SetXY(212, 22);
			$this->MultiCell(30,6,'COST IDR',1,'C',true);
			$this->SetXY(242, 22);
			$this->MultiCell(25,6,'TOTAL COST',1,'C',true);
			$this->SetXY(267, 22);
			$this->MultiCell(24,6,'PROFIT/LOSE',1,'C',true);
    		$this->Ln(1);
		} 
		else if($this->data['report']=='15') 
		{
			$this->SetFont('Arial','B',12);
    		$this->Cell(30,10,'PT. GATEWAY CONTAINER LINE',0,0,'L');
    		$this->Ln(4);
    		$this->SetFont('Arial','',8);
    		$this->Cell(30,10,'LAPORAN AGENT : '.$this->data['agent'].'               PERIODE: '.strtoupper($this->data['periode']),0,0,'L');
    		$this->Ln();    	
			$this->SetFont('Arial','B',8);   
			$this->SetFillColor(230,230,230);
			$this->SetTextColor(100,100,100);		
			$this->SetXY(10, 22);
			$this->MultiCell(8,6,'No',1,'C',true);
			$this->SetXY(18, 22);
			$this->MultiCell(27,6,'JOB NO #',1,'C',true);
			$this->SetXY(45, 22);
			$this->MultiCell(35,6,'DESTINATION',1,'C',true);
			$this->SetXY(80, 22);
			$this->MultiCell(15,6,'VOLUME',1,'C',true);
			$this->SetXY(95, 22);
			$this->MultiCell(15,6,'NOM',1,'C',true);
			$this->SetXY(110, 22);
			$this->MultiCell(15,6,'FREE',1,'C',true);
			$this->SetXY(125, 22);
			$this->MultiCell(25,6,'INCOME IDR',1,'C',true);
			$this->SetXY(150, 22);
			$this->MultiCell(20,6,'INC USD',1,'C',true);
			$this->SetXY(170, 22);
			$this->MultiCell(25,6,'TOTAL INCOME',1,'C',true);
			$this->SetXY(195, 22);
			$this->MultiCell(25,6,'COST IDR',1,'C',true);
			$this->SetXY(220, 22);
			$this->MultiCell(20,6,'COST USD',1,'C',true);
			$this->SetXY(240, 22);
			$this->MultiCell(25,6,'TOTAL COST',1,'C',true);
			$this->SetXY(265, 22);
			$this->MultiCell(24,6,'PROFIT/LOSE',1,'C',true);
    		$this->Ln(1);
		} 
		else if($this->data['report']=='e_invoice') 
		{
			//Put the watermark
			$this->SetFont('Arial','B',50);
			$this->SetTextColor(255,192,203);
			$this->TextWithRotation(70,180,'ORIGINAL',35);
		}
		else if($this->data['report']=='e_invoice_kredit') 
		{
			//Put the watermark
			$this->SetFont('Arial','B',50);
			$this->SetTextColor(255,192,203);
			$this->TextWithRotation(90,200,'C O P Y',35);
		}
		else if($this->data['report']=='Invoice Draft') 
		{
			//Put the watermark
			$this->SetFont('Arial','B',50);
			$this->SetTextColor(255,192,203);
			$this->TextWithRotation(90,200,'D R A F T',35);
		}
		else if($this->data['report']=='e_do') 
		{
			//Put the watermark
			$this->SetFont('Arial','B',50);
			$this->SetTextColor(255,192,203);
			$this->TextWithRotation(70,180,'ORIGINAL',35);
		}
		else if($this->data['report']=='selling_rate') 
		{
			$this->Image(APPPATH.'third_party/img/quote.jpg',5,5,+200);
		}
		else if($this->data['report']=='proforma_invoice') 
		{
		$CI =& get_instance();
		$region = $CI->session->userdata('region_id');
		if ($region=='Jakarta'){
		$this->Image(APPPATH.'third_party/img/logo.png',150,8,+50);
		$this->SetFont('verdana','',12);
		$this->SetXY(10, 10);
		$this->write(5,'PT. GATEWAY CONTAINER LINE');
		$this->SetFont('verdana','',7);
		$this->SetXY(10, 17);
		$this->write(0,'JL. OTISTA III RT. 001 RW. 001 NO. 18');
		$this->SetXY(10, 20);
		$this->write(0,'CIPINANG CEMPEDAK JATINEGARA');
		$this->SetXY(10, 23);
		$this->write(0,'JAKARTA TIMUR 13340');
		$this->SetXY(10, 26);
		$this->write(0,'PHONE : ( 62-21 ) 8591-8669 ( HUNTING )');
		$this->SetXY(10, 29);
		$this->write(0,'FAX : ( 62-21 ) 8591-8664');
		$this->SetFont('verdana','',8);
		$this->SetXY(10, 32);
		$this->write(0,'general@gateway-id.com');
		} else if ($region=='Surabaya')
		{
			$this->Image(APPPATH.'third_party/img/logo.png',150,8,+50);
		$this->SetFont('arial','',12);
		$this->SetXY(130, 10);
		$this->write(5,'PT. GATEWAY CONTAINER LINE');
		$this->SetFont('arial','',7);
		$this->SetXY(170, 17);
		$this->write(0,'JL. DONOREJO NO. 54 ');
		$this->SetXY(182.5, 20);
		$this->write(0,'SIMOKERTO');
		$this->SetXY(175.5, 23);
		$this->write(0,'SURABAYA 60141 ');
		$this->SetXY(153, 26);
		$this->write(0,'PHONE :  (031)3765610 / 3765624 ');
		$this->SetXY(169, 29);
		$this->write(0,'FAX : ( 031 ) 3765620');
		$this->SetFont('arial','',8);
		$this->SetXY(161, 32);
		$this->write(0,'mgtsby@gateway-id.com');
		} else if ($region=='Cikarang')
		{
			$this->Image(APPPATH.'third_party/img/logo.png',150,8,+50);
		$this->SetFont('arial','',12);
		$this->SetXY(130, 10);
		$this->write(5,'PT. GATEWAY CONTAINER LINE');
		$this->SetFont('arial','',7);
		$this->SetXY(172, 17);
		$this->write(0,'JL. INDUSTRI PASIR');
		$this->SetXY(173, 20);
		$this->write(0,'GOMBONG NO.114');
		$this->SetXY(173.5, 23);
		$this->write(0,'CIKARANG UTARA ');
		$this->SetXY(166, 26);
		$this->write(0,'PHONE :(021)89100449');
		$this->SetXY(170, 29);
		$this->write(0,'FAX :(021)89100449');
		$this->SetFont('arial','',8);
		$this->SetXY(161, 32);
		$this->write(0,'lisbeth@gateway-id.com');
		}
		 else if ($region=='BPLINE')
		{
		$this->Image(APPPATH.'third_party/img/bpline.png',150,8,+50);
		$this->SetFont('arial','',12);
		$this->SetXY(130, 10);
		$this->write(5,'PT. BANGUN PUTRA PESAKA');
		$this->SetFont('arial','',7);
		$this->SetXY(172, 17);
		$this->write(0,'JL. TIPAR CAKUNG NO 52');
		$this->SetXY(173, 20);
		$this->write(0,'SUKAPURA, CILINCING');
		$this->SetXY(173.5, 23);
		$this->write(0,'JAKARTA UTARA 14140');
		$this->SetXY(166, 26);
		$this->write(0,'PHONE (021) 44833450');
		}
		$this->Ln(80);
			//Put the watermark
			$this->line(10,53,200,53);	
			$this->line(10,79,200,79);
			$this->line(10,103,200,103);
			$this->line(10,110,200,110);
			$this->line(90,103,90,213);
			$this->line(115,103,115,213);
			$this->line(161,103,161,213);//Garis Ammount
			//$this->line(10,230,200,213);
			$this->line(130,103,130,213);
			//$this->line(151,240,151,213);//GARIS HORISONTAL TOTAL
			//$this->line(200,240,151,240);
			//$this->line(200,280,135,280);
			$this->line(10,53,10,213);
			$this->line(200,53,200,213);
		}
		else if($this->data['report']=='Unposting') 
		{
			$this->SetFont('Arial','B',12);
    		$this->Cell(30,10,'LISTING JOB YG BELUM DIPOSTING',0,0,'L');
    		$this->Ln(4);
    		$this->SetFont('Arial','',8);
    		$this->Cell(30,10,'PERIODE : '.$this->data['periode'],0,0,'L');
    		$this->Ln();    	
      	$this->SetFont('Arial','B',8);   
			$this->SetFillColor(230,230,230);
			$this->SetTextColor(100,100,100);		
			$this->SetXY(10, 22);
			$this->MultiCell(10,6,'No',1,'C',true);
			$this->SetXY(20, 22);
			$this->MultiCell(30,6,'JOB NO #',1,'C',true);
			$this->SetXY(50, 22);
			$this->MultiCell(50,6,'PIC',1,'C',true);
			$this->SetXY(100, 22);
			$this->MultiCell(55,6,'STATUS',1,'C',true);
    		$this->Ln(1);
		}
		else if($this->data['report']=='inv_unposting') 
		{
			$this->SetFont('Arial','B',12);
    		$this->Cell(30,10,'LISTING INVOICE YG BELUM DIPOSTING',0,0,'L');
    		$this->Ln(4);
    		$this->SetFont('Arial','',8);
    		$this->Cell(30,10,'PERIODE : '.$this->data['periode'],0,0,'L');
    		$this->Ln();    	
      	$this->SetFont('Arial','B',8);   
			$this->SetFillColor(230,230,230);
			$this->SetTextColor(100,100,100);		
			$this->SetXY(10, 22);
			$this->MultiCell(10,6,'No',1,'C',true);
			$this->SetXY(20, 22);
			$this->MultiCell(30,6,'JOB NO #',1,'C',true);
			$this->SetXY(50, 22);
			$this->MultiCell(50,6,'PIC',1,'C',true);
			$this->SetXY(100, 22);
			$this->MultiCell(55,6,'STATUS',1,'C',true);
    		$this->Ln(1);
		}
		else if($this->data['report']=='inv_total') 
		{
			$this->SetFont('Arial','B',12);
    		$this->Cell(30,10,'LISTING TOTAL INVOICE',0,0,'L');
    		$this->Ln(4);
    		$this->SetFont('Arial','',8);
    		$this->Cell(30,10,'PERIODE : '.$this->data['periode'],0,0,'L');
    		$this->Ln();    	
			$this->SetFont('Arial','B',8);   
			$this->SetFillColor(230,230,230);
			$this->SetTextColor(100,100,100);		
			$this->SetXY(10, 22);
			$this->MultiCell(10,6,'No',1,'C',true);
			$this->SetXY(20, 22);
			$this->MultiCell(30,6,'JOB NO #',1,'C',true);
			$this->SetXY(50, 22);
			$this->MultiCell(17,6,'ETD/ETA',1,'C',true);
			$this->SetXY(67, 22);
			$this->MultiCell(21,6,'INV NO.',1,'C',true);
			$this->SetXY(88, 22);
			$this->MultiCell(17,6,'CREATED',1,'C',true);
			$this->SetXY(105, 22);
			$this->MultiCell(13,6,'PAY',1,'C',true);
			$this->SetXY(118, 22);
			$this->MultiCell(9,6,'AGE',1,'C',true);
			$this->SetXY(127, 22);
			$this->MultiCell(58,6,'BILLED TO',1,'C',true);
			$this->SetXY(185, 22);
			$this->MultiCell(20,6,'STATUS',1,'C',true);
    		$this->Ln(1);
		}
	endif;
	}

	function Footer() {
		if(is_array($this->data)){
			if($this->data['report']=='agenda_invoice' || 
				$this->data['report']=='report_job_sheet' || 
				$this->data['report']=='3' || 
				$this->data['report']=='4' || 
				$this->data['report']=='5' || 
				$this->data['report']=='6' || 
				$this->data['report']=='9' ) {
				$this->SetY(-15);
					$this->SetFont('Arial','I',9);
					$this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'R');
				} else if ($this->data['report']=='e_invoice') {
				$this->SetY(-10);
					$this->SetFont('Arial','I',8);
					$this->write(0,'This is a computer-generated document. No signature is required.');	
			} else if ($this->data['report']=='e_invoice_kredit') {
				$this->SetY(-10);
					$this->SetFont('Arial','I',8);
					$this->write(0,'This is a computer-generated document. No signature is required.');	
			} else if ($this->data['report']=='e_do') {
				$this->SetY(-10);
					$this->SetFont('Arial','I',8);
					$this->write(0,'This is a computer-generated document. No signature is required.');	
			} else if ($this->data['report']=='invoice_winfast') {
				$this->SetY(-10);
					$this->SetFont('Arial','I',8);
					$this->write(0,'Bukti Potong PPH 23 2% mohon diemail ke acct@winfast.id');
			} else if ($this->data['report']=='invoice_bandung' || $this->data['report']=='invoice') {
				$this->SetY(-7);
					$this->SetFont('Arial','I',6);
					$this->write(0,'Revisi faktur pajak hanya bisa dilakukan di bulan berjalan dan selambat-lambatnya sampai tanggal 10 bulan berikutnya.');
			} 
		}else{
        // Posisi 1.5 cm dari bawah
        $this->SetY(-10);
        $this->SetFont('Arial', 'I', 8);
        // Page number
        $this->Cell(0, 10, 'Page '.$this->PageNo().'/{nb}', 0, 0, 'C');
		}
	}
	
	function TextWithRotation($x, $y, $txt, $txt_angle, $font_angle=0)
{
    $font_angle+=90+$txt_angle;
    $txt_angle*=M_PI/180;
    $font_angle*=M_PI/180;

    $txt_dx=cos($txt_angle);
    $txt_dy=sin($txt_angle);
    $font_dx=cos($font_angle);
    $font_dy=sin($font_angle);

    $s=sprintf('BT %.2F %.2F %.2F %.2F %.2F %.2F Tm (%s) Tj ET',$txt_dx,$txt_dy,$font_dx,$font_dy,$x*$this->k,($this->h-$y)*$this->k,$this->_escape($txt));
    if ($this->ColorFlag)
        $s='q '.$this->TextColor.' '.$s.' Q';
    $this->_out($s);
}
	
	
	function GetStringWidth_JustifiedLastLineWidth($s, $lineWidth){
        //Get width of a string in the current font
        $s=(string)$s;
        $words = split(' ',$s);
        $cw=&$this->CurrentFont['cw'];
        $w=0;
        $spaceWidth = $this->GetStringWidth(' ');

        for($i=0, $wordsCount = count($words); $i<$wordsCount; $i++){
            $w += $this->GetStringWidth($words[$i]) + $spaceWidth;
            if ($w > $lineWidth) {
                $i--;
            }
            if ($w >= $lineWidth) {
                $w = 0;
            }
        }
        return $w;
   }
	
	function setDataHeader($input){
    	$this->data = $input;
	}
	// Load data
function LoadData($file)
{
    // Read file lines
    $lines = file($file);
    $data = array();
    foreach($lines as $line)
        $data[] = explode(';',trim($line));
    return $data;
}

// Simple table
function BasicTable($header, $data)
{
    // Header
    foreach($header as $col)
        $this->Cell(40,7,$col,1);
    $this->Ln();
    // Data
    foreach($data as $row)
    {
        foreach($row as $col)
            $this->Cell(40,6,$col,1);
        $this->Ln();
    }
}

// Better table
function ImprovedTable($header, $data)
{
    // Column widths
    $w = array(40, 35, 40, 45);
    // Header
    for($i=0;$i<count($header);$i++)
        $this->Cell($w[$i],7,$header[$i],1,0,'C');
    $this->Ln();
    // Data
    foreach($data as $row)
    {
        $this->Cell($w[0],6,$row[0],'LR');
        $this->Cell($w[1],6,$row[1],'LR');
        $this->Cell($w[2],6,number_format($row[2]),'LR',0,'R');
        $this->Cell($w[3],6,number_format($row[3]),'LR',0,'R');
        $this->Ln();
    }
    // Closing line
    $this->Cell(array_sum($w),0,'','T');
}

// Colored table
function FancyTable($header, $data)
{
     // Colors, line width and bold font
    $this->SetFillColor(0,0,99);
    $this->SetTextColor(255);
    $this->SetDrawColor(222,208,17);
    $this->SetLineWidth(.3);
    $this->SetFont('Arial','B',8);
    // Header
    $w = array(20,30,80,30,30);
    for($i=0;$i<count($header);$i++)
    $this->Cell($w[$i],5,$header[$i],1,0,'C',true);
    $this->Ln();
    // Color and font restoration
    $this->SetFillColor(224,235,255);
    $this->SetTextColor(0);
    $this->SetFont('');
    // Data
    $fill = false;
	for($i=0;$i<count($data);$i++)
	$this->Cell($w[$i],5,$data[$i],'LR',0,'L',$fill);
	$this->Ln();
    $fill = !$fill;
    // Closing line
    $this->Cell(array_sum($w),0,'','T');
}
function FancyTable2($header2, $data2)
{
    // Colors, line width and bold font
      $this->SetFillColor(0,0,99);
    $this->SetTextColor(255);
    $this->SetDrawColor(222,208,17);
    $this->SetLineWidth(.3);
    $this->SetFont('Arial','B',8);
    // Header
    $w = array(40,20, 40, 45,45);
    for($i=0;$i<count($header2);$i++)
    $this->Cell($w[$i],5,$header2[$i],1,0,'C',true);
    $this->Ln();
    // Color and font restoration
    $this->SetFillColor(224,235,255);
    $this->SetTextColor(0);
    $this->SetFont('');
    // Data
    $fill = false;
    foreach($data2 as $row)
    {
        $this->Cell($w[0],6,$row->item,'LR',0,'L',$fill);
        $this->Cell($w[1],6,$row->qty,'LR',0,'L',$fill);
        $this->Cell($w[2],6,$row->unit_price,'LR',0,'R',$fill);
        $this->Cell($w[3],6,$row->price,'LR',0,'R',$fill);
		$this->Cell($w[4],6,$row->kondisi,'LR',0,'R',$fill);
        $this->Ln();
        $fill = !$fill;
    }
    // Closing line
    $this->Cell(array_sum($w),0,'','T');
}
function FancyTable3($headerselling, $dataselling)
{
    // Colors, line width and bold font
    $this->SetFillColor(0,0,99);
    $this->SetTextColor(255);
    $this->SetDrawColor(222,208,17);
    $this->SetLineWidth(.3);
    $this->SetFont('Arial','B',8);
    // Header
    $w = array(70,15,15,32,38,20);
    for($i=0;$i<count($headerselling);$i++)
    $this->Cell($w[$i],5,$headerselling[$i],1,0,'C',true);
    $this->Ln();
    // Color and font restoration
    $this->SetFillColor(224,235,255);
    $this->SetTextColor(0);
    $this->SetFont('');
    // Data
    $fill = false;
    foreach($dataselling as $row)
    {
        $this->Cell($w[0],6,$row->nama_service,'',0,'L',$fill);
        $this->Cell($w[1],6,$row->container,'',0,'C',$fill);
        $this->Cell($w[2],6,$row->type,'',0,'C',$fill);
        $this->Cell($w[3],6,$row->destination,'',0,'L',$fill);
		$this->Cell($w[4],6,$row->currency." ".$this->pecah($row->price,','),'',0,'R',$fill);
		$this->Cell($w[5],6,$row->kondisi,'',0,'C',$fill);
        $this->Ln();
        $fill = !$fill;
    }
    // Closing line
    $this->Cell(array_sum($w),0,'','T');
}
function drawTextBox($strText, $w, $h, $align='L', $valign='T', $border=false)
{
	$xi=$this->GetX();
	$yi=$this->GetY();
	
	$hrow=$this->FontSize;
	$textrows=$this->drawRows($w,$hrow,$strText,0,$align,0,0,0);
	$maxrows=floor($h/$this->FontSize);
	$rows=min($textrows,$maxrows);

	$dy=0;
	if (strtoupper($valign)=='M')
		$dy=($h-$rows*$this->FontSize)/2;
	if (strtoupper($valign)=='B')
		$dy=$h-$rows*$this->FontSize;

	$this->SetY($yi+$dy);
	$this->SetX($xi);

	$this->drawRows($w,$hrow,$strText,0,$align,false,$rows,1);

	if ($border)
		$this->Rect($xi,$yi,$w,$h);
}

function drawRows($w, $h, $txt, $border=0, $align='J', $fill=false, $maxline=0, $prn=0)
{
	$cw=&$this->CurrentFont['cw'];
	if($w==0)
		$w=$this->w-$this->rMargin-$this->x;
	$wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
	$s=str_replace("\r",'',$txt);
	$nb=strlen($s);
	if($nb>0 && $s[$nb-1]=="\n")
		$nb--;
	$b=0;
	if($border)
	{
		if($border==1)
		{
			$border='LTRB';
			$b='LRT';
			$b2='LR';
		}
		else
		{
			$b2='';
			if(is_int(strpos($border,'L')))
				$b2.='L';
			if(is_int(strpos($border,'R')))
				$b2.='R';
			$b=is_int(strpos($border,'T')) ? $b2.'T' : $b2;
		}
	}
	$sep=-1;
	$i=0;
	$j=0;
	$l=0;
	$ns=0;
	$nl=1;
	while($i<$nb)
	{
		//Get next character
		$c=$s[$i];
		if($c=="\n")
		{
			//Explicit line break
			if($this->ws>0)
			{
				$this->ws=0;
				if ($prn==1) $this->_out('0 Tw');
			}
			if ($prn==1) {
				$this->Cell($w,$h,substr($s,$j,$i-$j),$b,2,$align,$fill);
			}
			$i++;
			$sep=-1;
			$j=$i;
			$l=0;
			$ns=0;
			$nl++;
			if($border && $nl==2)
				$b=$b2;
			if ( $maxline && $nl > $maxline )
				return substr($s,$i);
			continue;
		}
		if($c==' ')
		{
			$sep=$i;
			$ls=$l;
			$ns++;
		}
		$l+=$cw[$c];
		if($l>$wmax)
		{
			//Automatic line break
			if($sep==-1)
			{
				if($i==$j)
					$i++;
				if($this->ws>0)
				{
					$this->ws=0;
					if ($prn==1) $this->_out('0 Tw');
				}
				if ($prn==1) {
					$this->Cell($w,$h,substr($s,$j,$i-$j),$b,2,$align,$fill);
				}
			}
			else
			{
				if($align=='J')
				{
					$this->ws=($ns>1) ? ($wmax-$ls)/1000*$this->FontSize/($ns-1) : 0;
					if ($prn==1) $this->_out(sprintf('%.3F Tw',$this->ws*$this->k));
				}
				if ($prn==1){
					$this->Cell($w,$h,substr($s,$j,$sep-$j),$b,2,$align,$fill);
				}
				$i=$sep+1;
			}
			$sep=-1;
			$j=$i;
			$l=0;
			$ns=0;
			$nl++;
			if($border && $nl==2)
				$b=$b2;
			if ( $maxline && $nl > $maxline )
				return substr($s,$i);
		}
		else
			$i++;
	}
	//Last chunk
	if($this->ws>0)
	{
		$this->ws=0;
		if ($prn==1) $this->_out('0 Tw');
	}
	if($border && is_int(strpos($border,'B')))
		$b.='B';
	if ($prn==1) {
		$this->Cell($w,$h,substr($s,$j,$i-$j),$b,2,$align,$fill);
	}
	$this->x=$this->lMargin;
	return $nl;
}
 protected $angle = 0;

    function Rotate($angle, $x = -1, $y = -1) {
        if ($x == -1) $x = $this->x;
        if ($y == -1) $y = $this->y;
        if ($this->angle != 0) $this->_out('Q');
        $this->angle = $angle;
        if ($angle != 0) {
            $angle *= M_PI / 180;
            $c = cos($angle);
            $s = sin($angle);
            $cx = $x * $this->k;
            $cy = ($this->h - $y) * $this->k;
            $this->_out(sprintf('q %.5F %.5F %.5F %.5F %.2F %.2F cm', $c, $s, -$s, $c, $cx, $cy));
        }
    }

    function _endpage() {
        if ($this->angle != 0) {
            $this->angle = 0;
            $this->_out('Q');
        }
        parent::_endpage();
    }

    function Watermark($text) {
        // Simpan posisi saat ini
        $this->SetTextColor(245,245,245); // Abu-abu terang
        $this->SetFont('Arial', 'B', 50);
        $this->Rotate(45, 145, 288); // Rotasi di tengah halaman A4 (210x297mm)
        $this->Text(35, 190, $text); // Posisi teks (disesuaikan manual)
        $this->Rotate(0); // Reset rotasi
        $this->SetTextColor(0, 0, 0); // Kembalikan ke hitam
    }
	
function Watermark_BL($text) {
    // Warna merah terang (soft)
    $this->SetTextColor(255, 220, 220); // Merah terang agar tidak mengganggu teks hitam

    // Font besar untuk watermark
    $this->SetFont('Arial', 'B', 50);

    // Rotasi watermark
    $this->Rotate(45, 145, 288); // Rotasi di tengah halaman

    // Turunkan posisi watermark 50 poin ke bawah
    $this->Text(35, 210, $text); // Awalnya 190 → sekarang 240

    // Reset rotasi dan warna
    $this->Rotate(0);
    $this->SetTextColor(0, 0, 0); // Kembalikan ke hitam
}

 function RoundedRect($x, $y, $w, $h, $r, $style='')
    {
        $k  = $this->k;
        $hp = $this->h;

        if($style=='F') $op='f';
        elseif($style=='FD' || $style=='DF') $op='B';
        else $op='S';

        $MyArc = 4/3 * (sqrt(2) - 1);

        $this->_out(sprintf('%.2f %.2f m', ($x+$r)*$k, ($hp-$y)*$k ));

        // kanan atas
        $this->_out(sprintf('%.2f %.2f l', ($x+$w-$r)*$k, ($hp-$y)*$k ));
        $this->_Arc($x+$w-$r+$r*$MyArc, $y, $x+$w, $y+$r-$r*$MyArc, $x+$w, $y+$r);

        // kanan bawah
        $this->_out(sprintf('%.2f %.2f l', ($x+$w)*$k, ($hp-($y+$h-$r))*$k ));
        $this->_Arc($x+$w, $y+$h-$r+$r*$MyArc, $x+$w-$r+$r*$MyArc, $y+$h, $x+$w-$r, $y+$h);

        // kiri bawah
        $this->_out(sprintf('%.2f %.2f l', ($x+$r)*$k, ($hp-($y+$h))*$k ));
        $this->_Arc($x+$r-$r*$MyArc, $y+$h, $x, $y+$h-$r+$r*$MyArc, $x, $y+$h-$r);

        // kiri atas
        $this->_out(sprintf('%.2f %.2f l', $x*$k, ($hp-($y+$r))*$k ));
        $this->_Arc($x, $y+$r-$r*$MyArc, $x+$r-$r*$MyArc, $y, $x+$r, $y);

        $this->_out($op);
    }

    // Helper untuk menggambar kurva Bezier
    function _Arc($x1, $y1, $x2, $y2, $x3, $y3)
    {
        $h = $this->h;
        $this->_out(sprintf('%.2f %.2f %.2f %.2f %.2f %.2f c',
            $x1*$this->k, ($h-$y1)*$this->k,
            $x2*$this->k, ($h-$y2)*$this->k,
            $x3*$this->k, ($h-$y3)*$this->k));
    }


}

?>