<?php
class C_faktur_pajak extends CI_Controller{
	
	var $title ='Faktur Pajak';

	public function __construct(){
		parent::__construct();
		$this->fungsi->restrict();
		$this->load->model(array('finance/m_faktur_pajak','finance/m_finance_proses'));
	}
		
	function print_data($id)
	{	
		$usd = false;
		$rp = false;
		$ttlnya = "";
	
		$data = $this->m_faktur_pajak->print_data($id);
		
		$this->load->library(array('fpdf','FPDF_MC_Table','fungsi'));
		$pdf=new FPDF_MC_Table();	
		$pdf->FPDF('P','mm','pajak');
		$pdf->AddPage();
		$pdf->SetFont('Arial','',9);
		$pdf->SetAutoPageBreak(true, 0) ;
		$pdf->SetMargins(5,0);
		$pdf->SetXY(120, 10);
		
		$pdf->write(0, 'LAMPIRAN IB');
		$pdf->SetXY(120, 14);
		$pdf->write(0, 'PERATURAN DIREKTUR JENDRAL PAJAK');
		$pdf->SetXY(120, 18);
		$pdf->write(0, 'NOMOR');
		$pdf->SetXY(137, 18);
		$pdf->write(0, ': PER-24/PJ/2012');
		$pdf->SetXY(120, 22);
		$pdf->write(0, 'TENTANG');
		$pdf->SetXY(137, 22);
		$pdf->write(0, ': BENTUK, UKURAN, TATA CARA');
		$pdf->SetXY(125, 26);
		$pdf->write(0, '  PENGISIAN KETERANGAN, PROSEDUR');
		$pdf->SetXY(125, 30);
		$pdf->write(0, '  PEMBERITAHUAN DALAM RANGKA PEMBUATAN,');
		$pdf->SetXY(125, 34);
		$pdf->write(0, '  TATA CARA PEMBETULAN ATAU PENGGANTIAN,');
		$pdf->SetXY(125, 38);
		$pdf->write(0, '  TATA CARA PEMBATALAN FAKTUR PAJAK');

		$pdf->SetFont('Arial','',8);
		$pdf->SetXY(130, 42);
		$pdf->write(0, '  Lembar ke-1:Untuk Pembeli BKP/Penerima JKP');
		$pdf->SetXY(140, 45);
		$pdf->write(0, '  Sebagai bukti Pajak Masukan');
		
		$pdf->Ln(1);
		$pdf->SetFont('Arial','B',14);
		$pdf->Cell(205, 10, "FAKTUR PAJAK",0,1,'C',false);
		
		$pdf->SetFont('Arial','',10);	
		
		$pdf->Cell(205,8, "Kode dan Nomor Seri Faktur Pajak : ". $data[0]->no_seri,1,1,'L',false);
		$pdf->Cell(205,2, "",'LR',1,'L',false);
		$pdf->Cell(42,6, "Pengusaha Kena Pajak",'L',0,'L',false);
		$pdf->Cell(5,6, ":",0,0,'L',false);
		$pdf->Cell(158,6, "",'R',1,'L',false);
		$pdf->Cell(42,6, "Nama",'L',0,'L',false);
		$pdf->Cell(5,6, ":",0,0,'L',false);
		$pdf->SetFont('Arial','B',10);
		
		$pdf->Cell(158,6, "PT. GATEWAY CONTAINER LINE",'R',1,'L',false);
		$pdf->SetFont('Arial','',10);
		$pdf->Cell(42,6, "Alamat",'L',0,'L',false);
		$pdf->Cell(5,6, ":",0,0,'L',false);
		$pdf->Cell(158,6, "GEDUNG ASKRIDA TOWER LT.3 R.303 JL. PRAMUKA RAYA UTAN KAYU UTARA MATRAMAN",'R',1,'L',false);
		$pdf->Cell(42,6, "",'L',0,'L',false);
		$pdf->Cell(5,6, "",0,0,'L',false);
		$pdf->Cell(158,6, "JAKARTA TIMUR DKI JAKARTA",'R',1,'L',false);
		$pdf->Cell(42,6, "N P W P",'L',0,'L',false);
		$pdf->Cell(5,6, ":",0,0,'L',false);
		$pdf->Cell(158,6, "02.932.610.5-001.000",'R',1,'L',false);
		
		$pdf->Cell(205,2, "",'LR',1,'L',false);
		
		$pdf->Cell(205,8, "Pembeli Barang Kena Pajak / Penerima Jasa Kena Pajak",'LTR',1,'L',false);
		
		$pdf->Cell(42,6, "Nama",'L',0,'L',false);
		$pdf->Cell(5,6, ":",0,0,'L',false);
		$pdf->SetFont('Arial','B',10);
		$pdf->Cell(158,6, substr($this->m_faktur_pajak->get_perusahaan($data[0]->shipper)->name,0,80),'R',1,'L',false);
		$pdf->SetFont('Arial','',10);
		
		$alamat = $this->m_faktur_pajak->get_perusahaan($data[0]->shipper)->address;
		$npwp = $this->m_faktur_pajak->get_perusahaan($data[0]->shipper)->npwp;
		
		$lenght_add = strlen($alamat);
		if($lenght_add > 80 ) {
			$alamat1 = substr($alamat,0,72);
			$alamat2 = str_replace($alamat1,'',$alamat);
		} else {
			$alamat1 = $alamat;
			$alamat2 = '';
		}
		
		$pdf->Cell(42,6, "Alamat",'L',0,'L',false);
		$pdf->Cell(5,6, ":",0,0,'L',false);
		$pdf->Cell(158,6,$alamat1,'R',1,'L',false);
		$pdf->Cell(42,6, "",'L',0,'L',false);
		$pdf->Cell(5,6, "",0,0,'L',false);
		$pdf->Cell(158,6, $alamat2,'R',1,'L',false);
		
		$pdf->Cell(42,6, "N P W P",'L',0,'L',false);
		$pdf->Cell(5,6, ":",0,0,'L',false);
		$pdf->Cell(158,6, $npwp,'R',1,'L',false);
		$pdf->Cell(205,2, "",'LR',1,'L',false);
		
		$pdf->Cell(10,5, "No",'LTR',0,'C',false);
		$pdf->Cell(100,5, "Nama Barang Kena Pajak /",'LTR',0,'C',false);
		$pdf->Cell(95,5, "Harga Jual / Penggantian / Uang Muka / Termin",'LTR',1,'C',false);
		$pdf->Cell(10,5, "Urut",'LB',0,'C',false);
		$pdf->Cell(100,5, "Jasa Kena Pajak",'LB',0,'C',false);
		$pdf->Cell(47.5,5, "Valas *)",'LBT',0,'C',false);
		$pdf->Cell(47.5,5, "Rp",1,1,'C',false);
		
		$i=1;
		foreach($data[1] as $row ) {
			$total = $row->price;
			if($row->currency=='USD'){
				$total_usd = $total;
				$total_rp = '';
				$usd = true;
				$rp = false;
			} else {
				$total_usd = '';
				$total_rp = $total;
				$usd = false;
				$rp = true;
			}

			$pdf->Cell(10,5, $i,'LR',0,'C',false);
			$pdf->Cell(100,5, strtoupper($row->nama_jasa),'R',0,'L',false);
			$pdf->Cell(47.5,5, $this->fungsi->pecah($total_usd,','),'R',0,'R',false);
			$pdf->Cell(47.5,5, $this->fungsi->pecah($total_rp,','),'R',1,'R',false);
			$i++;
			
			$ttlnya_usd[] = $row->price;
			$ttlnya_rp[] = $row->price;
			$ttlnya[] = $row->price;
		}
		

			$pdf->Cell(10,5, "",'LR',0,'C',false);
			$pdf->Cell(100,5, "INV. NO. ".$data[0]->no_invoice,'R',0,'L',false);
			$pdf->Cell(47.5,5, "",'R',0,'R',false);
			$pdf->Cell(47.5,5, "",'R',1,'R',false);
			
			$pdf->Cell(10,5, "",'LR',0,'C',false);
			$pdf->Cell(100,5, "TANGGAL ".$data[0]->date_invoice,'R',0,'L',false);
			$pdf->Cell(47.5,5, "",'R',0,'R',false);
			$pdf->Cell(47.5,5, "",'R',1,'R',false);
		if($data[0]->seri==10) {
		if($usd==true) {
			$ttlnya_usd = array_sum($ttlnya_usd);
			$ttlnya_rp = '';
			$ttlnya = array_sum($ttlnya);
			$prosen_usd = $ttlnya / $data[0]->seri;
			$prosen_rp = '';
		} elseif($rp==true) {
			$ttlnya_usd = '';
			$ttlnya_rp = array_sum($ttlnya_rp);
			$ttlnya = array_sum($ttlnya);
			$prosen_usd = '';
			$prosen_rp = $ttlnya / $data[0]->seri;
		} else {
			$ttlnya_usd = '';
			$ttlnya_rp = '';
			$prosen_usd = '';
			$prosen_rp = '';
		}
		
		}
		else
		{
                   if($usd==true) {
			$ttlnya_usd = (array_sum($ttlnya_usd));
			$ttlnya_rp = '';
			$ttlnya = (array_sum($ttlnya) * 0.1);
			$prosen_usd = $ttlnya * 0.1;
			$prosen_rp = '';
		} elseif($rp==true) {
			$ttlnya_usd = '';
			$ttlnya_rp = array_sum($ttlnya_rp);
			$ttlnya = (array_sum($ttlnya)) * 0.1;
			$prosen_usd = '';
			$prosen_rp = $ttlnya  * 0.1;
		} else {
			$ttlnya_usd = '';
			$ttlnya_rp = '';
			$prosen_usd = '';
			$prosen_rp = '';
		}

  }
		$pdf->Cell(10,7, "",'LBR',0,'C',false);
		$pdf->Cell(100,7, "",'BR',0,'C',false);
		$pdf->Cell(47.5,7, "",'BR',0,'C',false);
		$pdf->Cell(47.5,7, "",'BR',1,'C',false);

		$pdf->Cell(6,5, "",'LB',0,'C',false);
		
		//$pdf->Cell(104,5, "Harga Jual/ Penggantian / Uang Muka / Termin **)",'BR',0,'L',false);

		$strikeout_y_start = $pdf->GetY();
		$strikeout_x = $pdf->getX()+1;
		$strikeText = "Harga Jual";
		$pdf->Cell(18, 5, $strikeText,'B',0,'L',false);

		$strikeout_y_end = $pdf->GetY();
		$strikeout_y = $strikeout_y_start+2.5;
		$strikeCount = 0;
		for ($strikeout_y; $strikeout_y < $strikeout_y_end - 4; $strikeout_y+=4) {
    		$strikeCount++;
    		$pdf->Line($strikeout_x, $strikeout_y, $strikeout_x + 180, $strikeout_y);
		}
		$width = $pdf->GetStringWidth($strikeText);
		$width = $width - $strikeCount*180;
		$pdf->line($strikeout_x, $strikeout_y, $strikeout_x+$width, $strikeout_y);
		
		
		$pdf->Cell(25,5, "/ Penggantian /",'B',0,'L',false);
		
		
		$strikeout_y_start_2 = $pdf->GetY();
		$strikeout_x_2 = $pdf->getX()+1;
		$strikeText_2 = "Uang Muka / Termin *)";
		$pdf->Cell(61, 5, $strikeText_2,'BR',0,'L',false);
		$strikeout_y_end_2 = $pdf->GetY();
		$strikeout_y_2 = $strikeout_y_start_2+2.5;
		$strikeCount_2 = 0;
		for ($strikeout_y_2; $strikeout_y_2 < $strikeout_y_end_2 - 4; $strikeout_y_2+=4) {
    		$strikeCount_2++;
    		$pdf->Line($strikeout_x_2, $strikeout_y_2, $strikeout_x_2 + 180, $strikeout_y_2);
		}
		$width_2 = $pdf->GetStringWidth($strikeText_2);
		$width_2 = $width_2 - $strikeCount_2*180;
		$pdf->line($strikeout_x_2, $strikeout_y, $strikeout_x_2+$width_2, $strikeout_y_2);

	   $pdf->Cell(47.5,5, $this->fungsi->pecah($ttlnya_usd,','),'BR',0,'R',false);
		$pdf->Cell(47.5,5, $this->fungsi->pecah($ttlnya_rp,','),'BR',1,'R',false);
		
		$pdf->Cell(6,5, "",'LB',0,'C',false);
		$pdf->Cell(104,5, "Dikurangi Potongan Harga",'BR',0,'L',false);
		$pdf->Cell(47.5,5, "",'BR',0,'C',false);
		$pdf->Cell(47.5,5, "",'BR',1,'C',false);
		
		$pdf->Cell(6,5, "",'LB',0,'C',false);
		$pdf->Cell(104,5, "Dikurangi Uang Muka yang telah diterima",'BR',0,'L',false);
		$pdf->Cell(47.5,5, "",'BR',0,'C',false);
		$pdf->Cell(47.5,5, "",'BR',1,'C',false);
		
		if($data[0]->seri==10) 
		{
			$dsr = "Dasar Pengenaan Pajak";
		}
		else 
		{
                        $pdf->SetFont('Arial','',8);
			$dsr = "Dasar Pengenaan Pajak = 10% X Jumlah Setelah Dikurangi Uang Muka";
		}
		if($data[0]->currency == "USD" )
		{
         $t_rp = $this->fungsi->pecah($ttlnya * $data[0]->kurs,",");
			$p_rp = $this->fungsi->pecah($prosen_usd * $data[0]->kurs,",");
		}
		else 
		{
			$t_rp = $this->fungsi->pecah($ttlnya_rp,',');
			$p_rp = $this->fungsi->pecah($prosen_rp,',');
		}

		$pdf->Cell(6,5, "",'LB',0,'C',false);
		$pdf->Cell(104,5, $dsr,'BR',0,'L',false);
                if($data[0]->currency == "USD" )
                {
		$pdf->SetFont('Arial','',10);
		$pdf->Cell(47.5,5, $this->fungsi->pecah($ttlnya,','),'BR',0,'R',false);
		$pdf->Cell(47.5,5, $t_rp,'BR',1,'R',false);
		              }
		              else
		              {
                                	$pdf->SetFont('Arial','',10);
		$pdf->Cell(47.5,5,'','BR',0,'R',false);
		$pdf->Cell(47.5,5, $t_rp,'BR',1,'R',false);
		              }

		$pdf->Cell(6,5, "",'LB',0,'C',false);
		//$pdf->Cell(104,5, "PPN = ".$data[0]->seri."% X Dasar Pengenaan Pajak",'BR',0,'L',false);	
		$pdf->Cell(104,5, "PPN = 10 % X Dasar Pengenaan Pajak",'BR',0,'L',false);	
		$pdf->Cell(47.5,5, $this->fungsi->pecah($prosen_usd,','),'BR',0,'R',false);
		$pdf->Cell(47.5,5, $p_rp,'BR',1,'R',false);
		
		$pdf->Cell(6,6, "",'L',0,'C',false);	
		$pdf->Cell(104,6, "Pajak Penjualan Atas Barang Mewah",0,0,'L',false);
		//$pdf->Cell(95,6, "Jakarta , ".$data[0]->date,'R',1,'C',false);
		$pdf->Cell(95,6, "JAKARTA"." , ".strtoupper($this->fungsi->date_to_tanggal6($data[0]->date)),'R',1,'C',false);
		
		$pdf->Cell(4,6, "",'L',0,'C',false);
		$pdf->Cell(15,6, "TARIF",1,0,'C',false);	
		$pdf->Cell(40,6, "DPP",1,0,'C',false);	
		$pdf->Cell(40,6, "PPn BN",1,0,'C',false);	
		$pdf->Cell(106,6, "",'R',1,'C',false);
		
		$pdf->Cell(4,6, "",'L',0,'C',false);
		$pdf->Cell(15,6, ".........%",1,0,'C',false);	
		$pdf->Cell(40,6, "RP ............................",1,0,'C',false);	
		$pdf->Cell(40,6, "RP ............................",1,0,'C',false);		
		$pdf->Cell(106,6, "",'R',1,'C',false);
		
		$pdf->Cell(4,6, "",'L',0,'C',false);
		$pdf->Cell(15,6, ".........%",1,0,'C',false);	
		$pdf->Cell(40,6, "RP ............................",1,0,'C',false);	
		$pdf->Cell(40,6, "RP ............................",1,0,'C',false);		
		$pdf->Cell(106,6, "",'R',1,'C',false);
		
		$pdf->Cell(4,6, "",'L',0,'C',false);
		$pdf->Cell(15,6, ".........%",1,0,'C',false);	
		$pdf->Cell(40,6, "RP ............................",1,0,'C',false);	
		$pdf->Cell(40,6, "RP ............................",1,0,'C',false);		
		$pdf->Cell(106,6, "",'R',1,'C',false);
		
		$pdf->Cell(4,6, "",'L',0,'C',false);
		$pdf->Cell(15,6, ".........%",1,0,'C',false);	
		$pdf->Cell(40,6, "RP ............................",1,0,'C',false);	
		$pdf->Cell(40,6, "RP ............................",1,0,'C',false);		
		$pdf->Cell(106,6, "",'R',1,'C',false);
		
		$pdf->Cell(4,6, "",'L',0,'C',false);
		$pdf->Cell(55,6, "JUMLAH",1,0,'C',false);	
		$pdf->Cell(40,6, "RP ............................",1,0,'C',false);		
		$pdf->Cell(106,6, "",'R',1,'C',false);
		
		$pdf->Cell(4,5, "",'L',0,'C',false);
		$pdf->Cell(105,5, "KURS RP. ".$this->fungsi->pecah($data[0]->kurs,',') ." / USD. 1.00",0,0,'L',false);
		
		$pdf->SetFont('Arial','U',10);	
		$pdf->Cell(96,5, $data[0]->tanda_tangan,'R',1,'C',false);		
		
		$pdf->SetFont('Arial','',10);
		$pdf->Cell(4,5, "",'LB',0,'C',false);
		$pdf->Cell(105,5, "KMK NO. ".$data[0]->no_kmk ."  TGL : ".$data[0]->date_kmk,'B',0,'L',false);	
		$pdf->Cell(96,5, "",'BR',1,'C',false);
		$pdf->Ln(2);
		$pdf->SetFont('Arial','',9);
		$pdf->Cell(195,3.5, "*) Diisi apabila penyerahan menggunakan mata uang asing, dan apabila dilakukan penggantian/pembetulan Faktur Pajak",0,1,'L',false);
		$pdf->Cell(195,3.5, "   maka kurs yang digunakan adalah kurs pada tanggal pertama kali Faktur Pajak dibuat",0,1,'L',false);
		$pdf->Cell(195,3.5, "**) Coret yang tidak perlu",0,1,'L',false);
		
		
		$pdf->AddPage('P','pajak');
		$pdf->SetAutoPageBreak(true, 0) ;
		$pdf->SetFont('Arial','',9);
		//$pdf->SetMargins(5,0);
		$pdf->SetXY(120, 10);
		$pdf->write(0, 'LAMPIRAN IB');
		$pdf->SetXY(120, 14);
		$pdf->write(0, 'PERATURAN DIREKTUR JENDRAL PAJAK');
		$pdf->SetXY(120, 18);
		$pdf->write(0, 'NOMOR');
		$pdf->SetXY(137, 18);
		$pdf->write(0, ': PER-24/PJ/2012');
		$pdf->SetXY(120, 22);
		$pdf->write(0, 'TENTANG');
		$pdf->SetXY(137, 22);
		$pdf->write(0, ': BENTUK, UKURAN, TATA CARA');
		$pdf->SetXY(125, 26);
		$pdf->write(0, '  PENGISIAN KETERANGAN, PROSEDUR');
		$pdf->SetXY(125, 30);
		$pdf->write(0, '  PEMBERITAHUAN DALAM RANGKA PEMBUATAN,');
		$pdf->SetXY(125, 34);
		$pdf->write(0, '  TATA CARA PEMBETULAN ATAU PENGGANTIAN,');
		$pdf->SetXY(125, 38);
		$pdf->write(0, '  TATA CARA PEMBATALAN FAKTUR PAJAK');

		$pdf->SetFont('Arial','',8);
		$pdf->SetXY(130, 42);
		$pdf->write(0, '  Lembar ke-2:Untuk Penjual BKP/Pemberi JKP');
		$pdf->SetXY(140, 45);
		$pdf->write(0, '  Sebagai Bukti Pajak Keluaran');
		
		$pdf->Ln(1);
		$pdf->SetFont('Arial','B',14);
		$pdf->Cell(205, 10, "FAKTUR PAJAK",0,1,'C',false);
		
		$pdf->SetFont('Arial','',10);		
		$pdf->Cell(205,8, "Kode dan Nomor Seri Faktur Pajak : ". $data[0]->no_seri,1,1,'L',false);
		$pdf->Cell(205,2, "",'LR',1,'L',false);
		$pdf->Cell(42,6, "Pengusaha Kena Pajak",'L',0,'L',false);
		$pdf->Cell(5,6, ":",0,0,'L',false);
		$pdf->Cell(158,6, "",'R',1,'L',false);
		$pdf->Cell(42,6, "Nama",'L',0,'L',false);
		$pdf->Cell(5,6, ":",0,0,'L',false);
		$pdf->SetFont('Arial','B',10);
		
		$pdf->Cell(158,6, "PT. GATEWAY CONTAINER LINE",'R',1,'L',false);
		$pdf->SetFont('Arial','',10);
		$pdf->Cell(42,6, "Alamat",'L',0,'L',false);
		$pdf->Cell(5,6, ":",0,0,'L',false);
		$pdf->Cell(158,6, "GEDUNG ASKRIDA TOWER LT.3 R.303 JL. PRAMUKA RAYA UTAN KAYU UTARA MATRAMAN",'R',1,'L',false);
		$pdf->Cell(42,6, "",'L',0,'L',false);
		$pdf->Cell(5,6, "",0,0,'L',false);
		$pdf->Cell(158,6, "JAKARTA TIMUR DKI JAKARTA",'R',1,'L',false);
		$pdf->Cell(42,6, "N P W P",'L',0,'L',false);
		$pdf->Cell(5,6, ":",0,0,'L',false);
		$pdf->Cell(158,6, "02.932.610.5-001.000",'R',1,'L',false);
		
		$pdf->Cell(205,2, "",'LR',1,'L',false);
		
		$pdf->Cell(205,8, "Pembeli Barang Kena Pajak / Penerima Jasa Kena Pajak",'LTR',1,'L',false);
		
		$pdf->Cell(42,6, "Nama",'L',0,'L',false);
		$pdf->Cell(5,6, ":",0,0,'L',false);
		$pdf->SetFont('Arial','B',10);
		$pdf->Cell(158,6, substr($this->m_faktur_pajak->get_perusahaan($data[0]->shipper)->name,0,80),'R',1,'L',false);
		$pdf->SetFont('Arial','',10);
		
		//$alamat = $this->m_faktur_pajak->get_perusahaan($data[0]->shipper)->address;
		//$npwp = $this->m_faktur_pajak->get_perusahaan($data[0]->shipper)->npwp;
		
		
		$lenght_add = strlen($alamat);
		if($lenght_add > 80 ) {
			$alamat1 = substr($alamat,0,70);
			$alamat2 = str_replace($alamat1,'',$alamat);
		} else {
			$alamat1 = $alamat;
			$alamat2 = '';
		}
		
		$pdf->Cell(42,6, "Alamat",'L',0,'L',false);
		$pdf->Cell(5,6, ":",0,0,'L',false);
		$pdf->Cell(158,6,$alamat1,'R',1,'L',false);
		$pdf->Cell(42,6, "",'L',0,'L',false);
		$pdf->Cell(5,6, "",0,0,'L',false);
		$pdf->Cell(158,6, $alamat2,'R',1,'L',false);
		
		$pdf->Cell(42,6, "N P W P",'L',0,'L',false);
		$pdf->Cell(5,6, ":",0,0,'L',false);
		$pdf->Cell(158,6, $npwp,'R',1,'L',false);
		$pdf->Cell(205,2, "",'LR',1,'L',false);
		
		$pdf->Cell(10,5, "No",'LTR',0,'C',false);
		$pdf->Cell(100,5, "Nama Barang Kena Pajak /",'LTR',0,'C',false);
		$pdf->Cell(95,5, "Harga Jual / Penggantian / Uang Muka / Termin",'LTR',1,'C',false);
		$pdf->Cell(10,5, "Urut",'LB',0,'C',false);
		$pdf->Cell(100,5, "Jasa Kena Pajak",'LB',0,'C',false);
		$pdf->Cell(47.5,5, "Valas *)",'LBT',0,'C',false);
		$pdf->Cell(47.5,5, "Rp",1,1,'C',false);
		
		$i=1;
		foreach($data[1] as $row ) {
			$total = $row->price;
			if($row->currency=='USD'){
				$total_usd = $total;
				$total_rp = '';
				$usd = true;
				$rp = false;
			} else {
				$total_usd = '';
				$total_rp = $total;
				$usd = false;
				$rp = true;
			}

			$pdf->Cell(10,5, $i,'LR',0,'C',false);
			$pdf->Cell(100,5, strtoupper($row->nama_jasa),'R',0,'L',false);
			$pdf->Cell(47.5,5, $this->fungsi->pecah($total_usd,','),'R',0,'R',false);
			$pdf->Cell(47.5,5, $this->fungsi->pecah($total_rp,','),'R',1,'R',false);
			$i++;
			
		}
		
			$pdf->Cell(10,5, "",'LR',0,'C',false);
			$pdf->Cell(100,5, "INV. NO. ".$data[0]->no_invoice,'R',0,'L',false);
			$pdf->Cell(47.5,5, "",'R',0,'R',false);
			$pdf->Cell(47.5,5, "",'R',1,'R',false);
			
			$pdf->Cell(10,5, "",'LR',0,'C',false);
			$pdf->Cell(100,5, "TANGGAL ".$data[0]->date_invoice,'R',0,'L',false);
			$pdf->Cell(47.5,5, "",'R',0,'R',false);
			$pdf->Cell(47.5,5, "",'R',1,'R',false);
		
		$pdf->Cell(10,7, "",'LBR',0,'C',false);
		$pdf->Cell(100,7, "",'BR',0,'C',false);
		$pdf->Cell(47.5,7, "",'BR',0,'C',false);
		$pdf->Cell(47.5,7, "",'BR',1,'C',false);
			
		$pdf->Cell(6,5, "",'LB',0,'C',false);
		//$pdf->Cell(104,5, "Harga Jual/ Penggantian / Uang Muka / Termin **)",'BR',0,'L',false);
		
		$strikeout_y_start = $pdf->GetY();
		$strikeout_x = $pdf->getX()+1;
		$strikeText = "Harga Jual";
		$pdf->Cell(18, 5, $strikeText,'B',0,'L',false);
		
		$strikeout_y_end = $pdf->GetY();
		$strikeout_y = $strikeout_y_start+2.5;
		$strikeCount = 0;
		for ($strikeout_y; $strikeout_y < $strikeout_y_end - 4; $strikeout_y+=4) {
    		$strikeCount++;
    		$pdf->Line($strikeout_x, $strikeout_y, $strikeout_x + 180, $strikeout_y);
		}
		$width = $pdf->GetStringWidth($strikeText);
		$width = $width - $strikeCount*180;
		$pdf->line($strikeout_x, $strikeout_y, $strikeout_x+$width, $strikeout_y);
		
		
		$pdf->Cell(25,5, "/ Penggantian /",'B',0,'L',false);
		
		
		$strikeout_y_start_2 = $pdf->GetY();
		$strikeout_x_2 = $pdf->getX()+1;
		$strikeText_2 = "Uang Muka / Termin *)";
		$pdf->Cell(61, 5, $strikeText_2,'BR',0,'L',false);
		$strikeout_y_end_2 = $pdf->GetY();
		$strikeout_y_2 = $strikeout_y_start_2+2.5;
		$strikeCount_2 = 0;
		for ($strikeout_y_2; $strikeout_y_2 < $strikeout_y_end_2 - 4; $strikeout_y_2+=4) {
    		$strikeCount_2++;
    		$pdf->Line($strikeout_x_2, $strikeout_y_2, $strikeout_x_2 + 180, $strikeout_y_2);
		}
		$width_2 = $pdf->GetStringWidth($strikeText_2);
		$width_2 = $width_2 - $strikeCount_2*180;
		$pdf->line($strikeout_x_2, $strikeout_y, $strikeout_x_2+$width_2, $strikeout_y_2);
		
	 	$pdf->Cell(47.5,5, $this->fungsi->pecah($ttlnya_usd,','),'BR',0,'R',false);
		$pdf->Cell(47.5,5, $this->fungsi->pecah($ttlnya_rp,','),'BR',1,'R',false);
		
		$pdf->Cell(6,5, "",'LB',0,'C',false);
		$pdf->Cell(104,5, "Dikurangi Potongan Harga",'BR',0,'L',false);
		$pdf->Cell(47.5,5, "",'BR',0,'C',false);
		$pdf->Cell(47.5,5, "",'BR',1,'C',false);
		
		$pdf->Cell(6,5, "",'LB',0,'C',false);
		$pdf->Cell(104,5, "Dikurangi Uang Muka yang telah diterima",'BR',0,'L',false);
		$pdf->Cell(47.5,5, "",'BR',0,'C',false);
		$pdf->Cell(47.5,5, "",'BR',1,'C',false);
		
		if($data[0]->seri==10) 
		{
			$dsr = "Dasar Pengenaan Pajak";
		}
		else 
		{
			$pdf->SetFont('Arial','',8);
                        $dsr = "Dasar Pengenaan Pajak = 10% X Jumlah Setelah Dikurangi Uang Muka";
		}
		
		if($data[0]->currency == "USD" )
		{
			$t_rp = $this->fungsi->pecah($ttlnya * $data[0]->kurs,",");
			$p_rp = $this->fungsi->pecah($prosen_usd * $data[0]->kurs,",");
		}
		else 
		{
			$t_rp = $this->fungsi->pecah($ttlnya_rp,',');
			$p_rp = $this->fungsi->pecah($prosen_rp,',');
		}
		$pdf->Cell(6,5, "",'LB',0,'C',false);
		$pdf->Cell(104,5, $dsr,'BR',0,'L',false);
		if($data[0]->currency == "USD" )
                {
		$pdf->SetFont('Arial','',10);
		$pdf->Cell(47.5,5, $this->fungsi->pecah($ttlnya,','),'BR',0,'R',false);
		$pdf->Cell(47.5,5, $t_rp,'BR',1,'R',false);
		              }
		              else
		              {
                                	$pdf->SetFont('Arial','',10);
		$pdf->Cell(47.5,5,'','BR',0,'R',false);
		$pdf->Cell(47.5,5, $t_rp,'BR',1,'R',false);
		              }
		
		$pdf->Cell(6,5, "",'LB',0,'C',false);
		$pdf->Cell(104,5, "PPN = 10 % X Dasar Pengenaan Pajak",'BR',0,'L',false);	
		$pdf->Cell(47.5,5, $this->fungsi->pecah($prosen_usd,','),'BR',0,'R',false);
		$pdf->Cell(47.5,5, $p_rp,'BR',1,'R',false);
		
		$pdf->Cell(6,6, "",'L',0,'C',false);	
		$pdf->Cell(104,6, "Pajak Penjualan Atas Barang Mewah",0,0,'L',false);
		$pdf->Cell(95,6,"JAKARTA"." , ".strtoupper($this->fungsi->date_to_tanggal6($data[0]->date)),'R',1,'C',false);
		
		$pdf->Cell(4,6, "",'L',0,'C',false);
		$pdf->Cell(15,6, "TARIF",1,0,'C',false);	
		$pdf->Cell(40,6, "DPP",1,0,'C',false);	
		$pdf->Cell(40,6, "PPn BN",1,0,'C',false);	
		$pdf->Cell(106,6, "",'R',1,'C',false);
		
		$pdf->Cell(4,6, "",'L',0,'C',false);
		$pdf->Cell(15,6, ".........%",1,0,'C',false);	
		$pdf->Cell(40,6, "RP ............................",1,0,'C',false);	
		$pdf->Cell(40,6, "RP ............................",1,0,'C',false);		
		$pdf->Cell(106,6, "",'R',1,'C',false);
		
		$pdf->Cell(4,6, "",'L',0,'C',false);
		$pdf->Cell(15,6, ".........%",1,0,'C',false);	
		$pdf->Cell(40,6, "RP ............................",1,0,'C',false);	
		$pdf->Cell(40,6, "RP ............................",1,0,'C',false);		
		$pdf->Cell(106,6, "",'R',1,'C',false);
		
		$pdf->Cell(4,6, "",'L',0,'C',false);
		$pdf->Cell(15,6, ".........%",1,0,'C',false);	
		$pdf->Cell(40,6, "RP ............................",1,0,'C',false);	
		$pdf->Cell(40,6, "RP ............................",1,0,'C',false);		
		$pdf->Cell(106,6, "",'R',1,'C',false);
		
		$pdf->Cell(4,6, "",'L',0,'C',false);
		$pdf->Cell(15,6, ".........%",1,0,'C',false);	
		$pdf->Cell(40,6, "RP ............................",1,0,'C',false);	
		$pdf->Cell(40,6, "RP ............................",1,0,'C',false);		
		$pdf->Cell(106,6, "",'R',1,'C',false);
		
		$pdf->Cell(4,6, "",'L',0,'C',false);
		$pdf->Cell(55,6, "JUMLAH",1,0,'C',false);	
		$pdf->Cell(40,6, "RP ............................",1,0,'C',false);		
		$pdf->Cell(106,6, "",'R',1,'C',false);
		
		$pdf->Cell(4,5, "",'L',0,'C',false);
		$pdf->Cell(105,5, "KURS RP. ".$this->fungsi->pecah($data[0]->kurs,' ') ." / USD. 1.00",0,0,'L',false);
		
		$pdf->SetFont('Arial','U',10);	
		$pdf->Cell(96,5, $data[0]->tanda_tangan,'R',1,'C',false);		
		
		$pdf->SetFont('Arial','',10);
		$pdf->Cell(4,5, "",'LB',0,'C',false);
		$pdf->Cell(105,5, "KMK NO. ".$data[0]->no_kmk ."  TGL : ".$data[0]->date_kmk,'B',0,'L',false);	
		$pdf->Cell(96,5, "",'BR',1,'C',false);
		$pdf->Ln(1);
		$pdf->SetFont('Arial','',9);
		$pdf->Cell(195,3.5, "*) Diisi apabila penyerahan menggunakan mata uang asing, dan apabila dilakukan penggantian/pembetulan Faktur Pajak",0,1,'L',false);
		$pdf->Cell(195,3.5, "   maka kurs yang digunakan adalah kurs pada tanggal pertama kali Faktur Pajak dibuat",0,1,'L',false);
		$pdf->Cell(195,3.5, "**) Coret yang tidak perlu",0,1,'L',false);
		
		$pdf->Output("FAKTUR PAJAK",'I');
	   
	   
	}
	
	function sepuluh(){
		$this->fungsi->auth(45,'Faktur Pajak 10');
		$this->setTampilan();
	}

	function empat_puluh(){
		$this->fungsi->auth(46,'Faktur Pajak 40');
		$this->setTampilan();
	}
	
	function get_table_satu(){
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'id';
	 	$sord = isset($_POST['sord'])?$_POST['sord']:'sord';

	 	$start = $batas*$hal - $batas;
		$start = ($start<0)?0:$start;
		$_POST['start']=$start;
		
		$q = $this->m_faktur_pajak->get_table_satu($_POST,$limit=false);
		$query = $this->m_faktur_pajak->get_table_satu($_POST,$limit=true);
	 
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
												$row->seri,
												$row->no_seri,
												$row->date,
												$this->fungsi->get_nama_shipper($row->shipper),
												$row->no_invoice,										
												$row->date_invoice,
												$row->no_kmk,
												$row->date_kmk,
												$row->kurs,
												$row->desimal,
												$row->tanda_tangan,
												$row->cfp,
												);
		    $i++;
		}
		echo json_encode($data);
	}
	
	function get_content() {
		$data = $this->m_faktur_pajak->get_content($_POST);
		echo json_encode($data);
	}

	function get_faktur_pajak(){
		$data = $this->m_faktur_pajak->get_faktur_pajak($_POST);
		echo json_encode($data);
	}
	
	function insert_shipper_data(){
		$data = $this->m_faktur_pajak->insert_shipper_data($_POST);
		echo json_encode($data);
	}
	
	function dropdown_no_inv() {
		$no_inv = $this->m_faktur_pajak->get_list_invoice()->result();
		$data['option']['']=' ';
		foreach ($no_inv as $row)
		{	
			$data['option'][$row->number]=$row->number;
		}		
		return form_dropdown('no_invoice_form',$data['option'],'','id="no_invoice_form"');
	}
		
	function setTampilan(){
		$data['title'] = $this->title;
		$data['menu_navigation'] ='navigation';
		$data['main_view'] ='finance/v_faktur_pajak';
		$data['option_inv']= $this->dropdown_no_inv();
		$this->load->view('afg_template', $data);
	}
	
	function manage_data(){
		$status = $this->m_faktur_pajak->manage_data($_POST);
		echo json_encode(array("status" => $status,'mode'=>$_POST['oper']));
	}
	
	function manage_data_dua(){
		$status = $this->m_faktur_pajak->manage_data_dua($_POST);
		echo json_encode(array("status" => $status,'mode'=>$_POST['oper']));
	}
	
	function get_table_dua(){
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'id';
	 	$sord = isset($_POST['sord'])?$_POST['sord']:'sord';
	 	
	 	$start = $batas*$hal - $batas;
		$start = ($start<0)?0:$start;
		$_POST['start']=$start;
		
		if(isset($_POST['id_fp'])) {
			$_POST['id_fp'] = $_POST['id_fp'];
		} else {
			$_POST['id_fp']='';
		}
	
		$q = $this->m_faktur_pajak->get_table_dua($_POST,$limit=false);
		$query = $this->m_faktur_pajak->get_table_dua($_POST,$limit=true);
	 
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
												$row->nama_jasa,
												$row->currency,
												//$row->jumlah * $row->unit_price,
												$row->price,
										);
		    $i++;
		}
		echo json_encode($data);
	}

	function tarik_data(){
		$value = $this->m_faktur_pajak->tarik_data($_POST);
		echo json_encode($value);
	}
	
	function test(){
		echo $this->fungsi->pecah('7500.3',' ');
	}
	
	function get_same_number()
	{
		$data = $this->m_faktur_pajak->get_same_number();
		echo '<table>';
		$i=1;
		foreach($data->result() as $row)
		{
			echo 
			'<tr>'.
				'<td style="border: 1px solid #999; width : 40px; text-align: center;">'.$i.'</td>'.
				'<td style="border: 1px solid #999; width : 90px; text-align: left;">'.$row->no.'</td>'.
				'<td style="border: 1px solid #999; width : 90px; text-align: left;">'.$this->fungsi->jin_date_str($row->date).'</td>'.
				'<td style="border: 1px solid #999; width : 140px; text-align: left;">'.$row->job.'</td>'.
				'<td style="border: 1px solid #999; width : 140px; text-align: left;">'.$row->jenis.'</td>'.
			'</tr>';
			$i++;
		}
		echo '<table>';
	}
}


/*
Lembar ke-2:Untuk Penjual BKP/Pemberi JKP
		Sebagai Bukti Pajak Keluaran
*/