<?php
require('fpdf.php');

class AlphaPDF extends FPDF
{
	protected $extgstates = array();

	// alpha: real value from 0 (transparent) to 1 (opaque)
	// bm:    blend mode, one of the following:
	//          Normal, Multiply, Screen, Overlay, Darken, Lighten, ColorDodge, ColorBurn,
	//          HardLight, SoftLight, Difference, Exclusion, Hue, Saturation, Color, Luminosity
	function SetAlpha($alpha, $bm='Normal')
	{
		// set alpha for stroking (CA) and non-stroking (ca) operations
		$gs = $this->AddExtGState(array('ca'=>$alpha, 'CA'=>$alpha, 'BM'=>'/'.$bm));
		$this->SetExtGState($gs);
	}

	function AddExtGState($parms)
	{
		$n = count($this->extgstates)+1;
		$this->extgstates[$n]['parms'] = $parms;
		return $n;
	}

	function SetExtGState($gs)
	{
		$this->_out(sprintf('/GS%d gs', $gs));
	}

	function _enddoc()
	{
		if(!empty($this->extgstates) && $this->PDFVersion<'1.4')
			$this->PDFVersion='1.4';
		parent::_enddoc();
	}

	function _putextgstates()
	{
		for ($i = 1; $i <= count($this->extgstates); $i++)
		{
			$this->_newobj();
			$this->extgstates[$i]['n'] = $this->n;
			$this->_put('<</Type /ExtGState');
			$parms = $this->extgstates[$i]['parms'];
			$this->_put(sprintf('/ca %.3F', $parms['ca']));
			$this->_put(sprintf('/CA %.3F', $parms['CA']));
			$this->_put('/BM '.$parms['BM']);
			$this->_put('>>');
			$this->_put('endobj');
		}
	}

	function _putresourcedict()
	{
		parent::_putresourcedict();
		$this->_put('/ExtGState <<');
		foreach($this->extgstates as $k=>$extgstate)
			$this->_put('/GS'.$k.' '.$extgstate['n'].' 0 R');
		$this->_put('>>');
	}

	function _putresources()
	{
		$this->_putextgstates();
		parent::_putresources();
	}
		// Colored table
	function FancyTable($header, $data)
	{
     // Colors, line width and bold font
    $this->SetFillColor(255,255,255);
    $this->SetTextColor(0,0,0);
    $this->SetDrawColor(0, 0, 255, 0);
    $this->SetLineWidth(0);
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
}
?>
