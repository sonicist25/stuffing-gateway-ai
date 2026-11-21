<?php 

function print_jobsheeet($tanggal,$kode_ref,$print){
		
		$totalnya_usd_cost_all = "";
		$totalnya_rp_cost_all = "";
		$total_global_cost = "";
		$kali_agent = "";
		$totalnya_usd = "";
		$totalnya_rp = "";
		$total_global_income = "";
		
		$hasil_usd = "";
		$hasil_rp = "";
		$hasil_usd_cost = "";
		$hasil_rp_cost = "";
		
		$invoice = '';
		$invoice_cost = "";
		$invoice_cost_4 = '';
		
		$con_vessel = "";
		$con_voyage = "";
		$con_etd = "";
		$con_eta = "";
		
		$tuc_1 ='';
		$tuc_2 ='';
		$tuc_3 ='';
		$tuc_4 ='';
		
		$trc_1 = '';
		$trc_2 = '';
		$trc_3 = '';
		$trc_4 = '';
		
		$hasil_usd = '';
		$hasil_rp = '';
		
		$price_usd = '';
		$total_usd = '';
		$ttl_usd = '';
		$price_rp = '';
		$total_rp = '';	
		$ttl_rp	= '';
		
		$price_usd_cost_4 = '';
		$total_usd_cost_4 = '';
		$ttl_cost_usd_4 = '';
		$price_rp_cost_4 = '';
		$total_rp_cost_4 = '';	
		$ttl_rp_cost_4	= '';
		
		$hasil_usd_cost = '';
		$hasil_rp_cost = '';
		
		$uang = '';
		
		$this->load->library(array('fpdf','fungsi'));		
		$this->fpdf->FPDF('P','mm','A4');
		$this->fpdf->AddPage();
		$this->fpdf->SetLineWidth(0.1);
		
		$data =  $this->m_bill_of_ladding->get_sp_spd_by($kode_ref);

		$region = $this->session->userdata('region_id');
		$destination = $data['destination'];
		$dest = $this->afg_m_cities->get_city_id($destination);
		
		$bl_no = substr($region, 0, 1).$dest.'-GCL'. substr($kode_ref, 11, 6);
		
		$this->fpdf->SetFont('Arial','b',10);
		$this->fpdf->SetXY(5, 15);
		$this->fpdf->write(0,'PREVIEW MODE');
		
		$this->fpdf->SetFont('Arial','b',10);
		$this->fpdf->SetXY(90, 10);
		$this->fpdf->write(0,'JOB SHEET');
		
		$this->fpdf->SetFont('Arial','',7);
		$this->fpdf->SetXY(175, 10);
		$this->fpdf->write(0, date('d-m-Y'));
		
		$this->fpdf->SetFont('Arial','',8);
		$this->fpdf->SetXY(5, 25);
		$this->fpdf->write(0,'JOB NO');
		$this->fpdf->SetXY(30, 25);
		$this->fpdf->write(0,':');
		$this->fpdf->SetXY(35, 25);
		$this->fpdf->write(0,$kode_ref);
		
		$this->fpdf->SetXY(5, 29);
		$this->fpdf->write(0,'DATE');
		$this->fpdf->SetXY(30, 29);
		$this->fpdf->write(0,':');
		$this->fpdf->SetXY(35, 29);
		$tanggalnya = substr($tanggal,6,2).'-'.substr($tanggal,4,2).'-'.substr($tanggal,0,4);
		$this->fpdf->write(0,$tanggalnya);
		
		$shipper = substr($this->fungsi->get_nama_shipper($data['shipper']),0,45);
		$imp = substr($kode_ref,0,5);
		if($imp=='GCL-3') {
			$shipper = $data['shipper_import'];
		}
				
		$this->fpdf->SetXY(5, 33);
		$this->fpdf->write(0,'SHIPPER');
		$this->fpdf->SetXY(30, 33);
		$this->fpdf->write(0,':');
		$this->fpdf->SetXY(35, 33);
		$this->fpdf->write(0,$shipper);
		
		$this->fpdf->SetXY(5, 37);
		$this->fpdf->write(0,'Q.Q');
		$this->fpdf->SetXY(30, 37);
		$this->fpdf->write(0,':');
		$this->fpdf->SetXY(35, 37);
		$this->fpdf->write(0,' ');
		
		$this->fpdf->SetXY(5, 41);
		$this->fpdf->write(0,'CONSIGNEE');
		$this->fpdf->SetXY(30, 41);
		$this->fpdf->write(0,':');
		$this->fpdf->SetXY(35, 38);
		$consignee = strtoupper($data['consignee']);
		$this->fpdf->Cell(70,5,substr($consignee, 0,45));
		
		$this->fpdf->SetXY(5, 45);
		$this->fpdf->write(0,'NOTIFY');
		$this->fpdf->SetXY(30, 45);
		$this->fpdf->write(0,':');
		$this->fpdf->SetXY(35, 43);
		$notify = strtoupper($data['notify']);
		$this->fpdf->Cell(70,5,substr($notify, 0,45));
		
		$this->fpdf->SetXY(5, 49);
		$this->fpdf->write(0,'SALES BY');
		$this->fpdf->SetXY(30, 49);
		$this->fpdf->write(0,':');
		$this->fpdf->SetXY(35, 49);
		$this->fpdf->write(0,$this->fungsi->get_user_by_id($data['sales']));
		
		$this->fpdf->SetXY(5, 53);
		$this->fpdf->write(0,'SHIPPING CO');
		$this->fpdf->SetXY(30, 53);
		$this->fpdf->write(0,':');
		$this->fpdf->SetXY(35, 53);
		$this->fpdf->Cell(70,5,substr($data['shipp_co'],0,45));
		
		$this->fpdf->SetXY(5, 57);
		$this->fpdf->write(0,'LOCATION');
		$this->fpdf->SetXY(30, 57);
		$this->fpdf->write(0,':');
		$this->fpdf->SetXY(35, 57);
		$this->fpdf->write(0,' ' );
		
		$this->fpdf->SetXY(5, 61);
		$this->fpdf->write(0,'TRUCKING');
		$this->fpdf->SetXY(30, 61);
		$this->fpdf->write(0,':');
		$this->fpdf->SetXY(35, 61);
		$this->fpdf->write(0,' ');
		
		$this->fpdf->SetXY(5, 65);
		$this->fpdf->write(0,'DESC. OF GOOD');
		$this->fpdf->SetXY(30, 65);
		$this->fpdf->write(0,':');
		
		$this->fpdf->SetFont('Arial','',8);
		$this->fpdf->SetXY(120, 25);
		$this->fpdf->write(0,'VESEL');
		$this->fpdf->SetXY(150, 25);
		$this->fpdf->write(0,':');
		$this->fpdf->SetXY(155, 25);
		$this->fpdf->write(0,$data['vessel'].' '.$data['voyage']);
		
		$this->fpdf->SetXY(120, 29);
		$this->fpdf->write(0,'ETD JKT');
		$this->fpdf->SetXY(135, 29);
		$this->fpdf->write(0,':');		
		$this->fpdf->SetXY(140, 29);
		$tgl_etd = $data['etd_jkt'];
		$tgl_etd_2 = substr($tgl_etd,6,2).'-'.substr($tgl_etd,4,2).'-'.substr($tgl_etd,0,4);
		$this->fpdf->write(0,$tgl_etd_2);
		
		$this->fpdf->SetXY(160, 29);
		$this->fpdf->write(0,'ETA '.$this->afg_m_cities->get_city_id($data['destination']));
		$this->fpdf->SetXY(175, 29);
		$this->fpdf->write(0,':');		
		$this->fpdf->SetXY(180, 29);
		
		$tgl_eta = $data['etd_jkt'];
		$tgl_eta_2 = substr($tgl_eta,6,2).'-'.substr($tgl_eta,4,2).'-'.substr($tgl_eta,0,4);
		$this->fpdf->write(0,$tgl_eta_2);
		
		if($data['transhipment_port']!='') {
			$conecting = $this->m_bill_of_ladding->getConnTable($data['sp_id']);
			if($conecting!=0) {
				$con_vessel = $conecting['connecting_vesel'];
				$con_voyage = $conecting['connecting_voyage'];
				$con_etd = $conecting['connecting_etd'];
				$con_eta = $conecting['connecting_eta'];
			}
		}
		
		$this->fpdf->SetXY(120, 33);
		$this->fpdf->write(0,'CONN VESEL');
		$this->fpdf->SetXY(150, 33);
		$this->fpdf->write(0,':');
		$this->fpdf->SetXY(155, 33);	
		$this->fpdf->write(0, $con_vessel.' '.$con_voyage);
				
		$this->fpdf->SetXY(120, 37);
		$this->fpdf->write(0,'ETD ');
		$this->fpdf->SetXY(135, 37);
		$this->fpdf->write(0,':');		
		$this->fpdf->SetXY(140, 37);
		
		$tgl_con_etd = substr($con_etd,6,2).'-'. substr($con_etd,4,2).'-'. substr($con_etd,0,4);
		$this->fpdf->write(0,$tgl_con_etd);
		
		
		$this->fpdf->SetXY(160, 37);
		$this->fpdf->write(0,'ETA '.'  ');
		$this->fpdf->SetXY(175, 37);
		$this->fpdf->write(0,':');		
		$this->fpdf->SetXY(180, 37);
		
		$tgl_con_eta = substr($con_eta,6,2).'-'. substr($con_eta,4,2).'-'. substr($con_eta,0,4);
		$this->fpdf->write(0,$tgl_con_eta);
		
		$this->fpdf->SetXY(120, 41);
		$this->fpdf->write(0,'DESTINATION');
		$this->fpdf->SetXY(150, 41);
		$this->fpdf->write(0,':');
		$this->fpdf->SetXY(155, 41);
		$this->fpdf->write(0,$data['destination']);
		
		$this->fpdf->SetXY(120, 45);
		$this->fpdf->write(0,'PORT DISCHARGE');
		$this->fpdf->SetXY(150, 45);
		$this->fpdf->write(0,':');
		$this->fpdf->SetXY(155, 45);
		$this->fpdf->write(0,$data['destination']);
		
		$this->fpdf->SetXY(120, 49);
		$this->fpdf->write(0,'CONTAINER');
		$this->fpdf->SetXY(150, 49);
		$this->fpdf->write(0,':');
		$this->fpdf->SetXY(155, 49);
		$this->fpdf->write(0,$data['type_cont_qty'].'  X  '.$data['type_cont_size']);
		
		$this->fpdf->SetXY(120, 53);
		$this->fpdf->write(0,'CONT.NO/SEAL NO');
		$this->fpdf->SetXY(150, 53);
		$this->fpdf->write(0,':');
		$this->fpdf->SetXY(155, 53);
		$this->fpdf->write(0,$data['cont_seal_no']);
		
		$this->fpdf->SetXY(120, 57);
		$this->fpdf->write(0,'BL NUMBER');
		$this->fpdf->SetXY(150, 57);
		$this->fpdf->write(0,':');
		$this->fpdf->SetXY(155, 57);
		$this->fpdf->write(0,$bl_no);

		$this->fpdf->SetXY(120, 61);
		$this->fpdf->write(0,'KURS RP.');
		$this->fpdf->SetXY(150, 61);
		$this->fpdf->write(0,':');
		$this->fpdf->SetXY(155, 61);
		$kurs = $data['kurs'];
		if(!empty($kurs)) {
			$uang = $this->fungsi->pecah($kurs,' ');
		}
		$this->fpdf->write(0,$uang);
		$this->fpdf->Ln(5);
		$this->fpdf->SetXY(35, 63);
		$this->fpdf->MultiCell(80,4,$data['desc_goods'],0,1,0);
		$this->fpdf->SetMargins(5,15);
		$this->fpdf->SetFont('Arial','B',8);
		$this->fpdf->Ln();		
		$this->fpdf->Cell(5,5,'INCOME :');
		$this->fpdf->SetFont('Arial','B',7);
		$this->fpdf->Ln();
		$this->fpdf->SetFillColor(230,230,230);
		$this->fpdf->SetTextColor(100,100,100);
		$this->fpdf->Cell(40,5,'DETAIL OF CHARGE','TB','','C',true);
		$this->fpdf->Cell(15,5,'KODE','TB','','C',true);
		$this->fpdf->Cell(19,5,'QUANTITY','TB','','C',true);
		$this->fpdf->Cell(19,5,'PRICE (USD)','TB','','C',true);
		$this->fpdf->Cell(19,5,'TOTAL (USD)','TB','','C',true);
		$this->fpdf->Cell(19,5,'PRICE (RP.)','TB','','C',true);
		$this->fpdf->Cell(19,5,'TOTAL (RP.)','TB','','C',true);
		$this->fpdf->Cell(10,5,'P/C','TB','','C',true);
		$this->fpdf->Cell(19,5,'RECEIVED','TB','','C',true);
		$this->fpdf->Cell(19,5,'INVOICE NO','TB','','C',true);
		$this->fpdf->Ln();		
		$this->fpdf->SetFont('Arial','',7);
		
		$data_income = $this->afg_m_job_sheet->get_income_pdf($kode_ref);
		if($data_income) {
			foreach ($data_income->result_array() as $row ) {
				$mchgs = $row['mchgs'];
				$quantity = $row['qty'];
				if($quantity=="") {
					$quantity=$data['bl_meas'];
				}
				if($data['bl_meas'] < $mchgs ) {
					$kali = $mchgs * $row['unit_price'];
				} else {
					$kali = $quantity * $row['unit_price'];
				}
				
				$currency = $row['currency'];
				$unit_price = $row['unit_price'];
			
				if($currency == 'USD') {
					$ttl_usd = $kali;	
					$price_usd = $unit_price;
					$total_usd = $ttl_usd;
					$price_rp = '';
					$total_rp = '';	
					$ttl_rp	= '';		
				} else if($currency == 'RP.') {
					$ttl_rp = $kali;
					$price_rp = $unit_price;
					$total_rp = $ttl_rp;
					$price_usd = '';
					$total_usd = '';
					$ttl_usd = '';				
				}			
			
				$code_service = $this->type_service_model->getDataCodeService($row['item_service']);
				$this->fpdf->Cell(40,5,substr($row['item_service'],0,20),'','','L',false);
				$this->fpdf->Cell(15,5,$code_service,'','','C',false);
				$this->fpdf->Cell(19,5,number_format($quantity,4,'.',' '),'','','C',false);			
				$this->fpdf->Cell(19,5,$this->fungsi->pecah($price_usd,' '),'','','R',false);
				$this->fpdf->Cell(19,5,$this->fungsi->pecah($total_usd,' '),'','','R',false);		
				$this->fpdf->Cell(19,5,$this->fungsi->pecah($price_rp,' '),'','','R',false);			
				$this->fpdf->Cell(19,5,$this->fungsi->pecah($total_rp,' '),'','','R',false);							
				$this->fpdf->Cell(10,5,substr($row['prep_coll'],0,1),'','','C',false);
				$this->fpdf->Cell(19,5,$row['receive_from'],'','','C',false);
				$this->fpdf->Cell(19,5,$invoice,'','','C',false);
				$this->fpdf->Ln();
			
				$totalnya_usd[] = $ttl_usd;
				$totalnya_rp[] = $ttl_rp;
			}
			
			if(!empty($totalnya_usd)) {
				$hasil_usd = array_sum($totalnya_usd);
			}
		
			if(!empty($totalnya_rp)) {
				$hasil_rp = array_sum($totalnya_rp);
			}

			$this->fpdf->SetFont('Arial','B',7);
			$this->fpdf->Cell(40,5,'TOTAL INCOME','TB','','L',true);
			$this->fpdf->Cell(15,5,'','TB','','C',true);
			$this->fpdf->Cell(19,5,'','TB','','C',true);
			$this->fpdf->Cell(19,5,'USD','TB','','R',true);
			$this->fpdf->Cell(19,5,$this->fungsi->pecah($hasil_usd,' '),'TB','','R',true);
			$this->fpdf->Cell(19,5,'RP.','TB','','R',true);
			$this->fpdf->Cell(19,5,$this->fungsi->pecah($hasil_rp,' '),'TB','','R',true);
			$this->fpdf->Cell(10,5,'=','TB','','C',true);
			$this->fpdf->Cell(10,5,'RP.','TB','','R',true);
		
			$conversi = $data['kurs'] * $hasil_usd;
			$total_global_income = $conversi + $hasil_rp;
			
			$this->fpdf->Cell(25,5,$this->fungsi->pecah($total_global_income,' '),'TB','','R',true);
			$this->fpdf->Cell(3,5,' ','TB','','C',true);
		}
		
		$this->fpdf->SetTextColor(0,0,0);
		$this->fpdf->SetFont('Arial','B',8);
		$this->fpdf->Ln(10);		
		$this->fpdf->Cell(5,5,'COST :');
		
		$this->fpdf->SetFont('Arial','B',7);
		$this->fpdf->Ln();
		$this->fpdf->SetFillColor(230,230,230);
		$this->fpdf->SetTextColor(100,100,100);
		
		$this->fpdf->Cell(40,5,'DETAIL OF CHARGE','TB','','C',true);
		$this->fpdf->Cell(15,5,'KODE','TB','','C',true);
		$this->fpdf->Cell(19,5,'QUANTITY','TB','','C',true);
		$this->fpdf->Cell(19,5,'PRICE (USD)','TB','','C',true);
		$this->fpdf->Cell(19,5,'TOTAL (USD)','TB','','C',true);
		$this->fpdf->Cell(19,5,'PRICE (RP.)','TB','','C',true);
		$this->fpdf->Cell(19,5,'TOTAL (RP.)','TB','','C',true);
		$this->fpdf->Cell(10,5,'P/C','TB','','C',true);
		$this->fpdf->Cell(19,5,'PAYABLE TO','TB','','C',true);
		$this->fpdf->Cell(19,5,'REMARKS BK','TB','','C',true);
		$this->fpdf->Ln();		
		$this->fpdf->SetFont('Arial','',7);
		
		$data_cost = $this->afg_m_cost->get_cost_pdf($kode_ref);
		if($data_cost) {
			foreach ($data_cost->result_array() as $row_cost ){
				$qty_cost = $row_cost['qty'];
				if($qty_cost=="") {
					$qty_cost=$data['bl_meas'];
				}
					
				$curr_cost = $row_cost['currency'];
				$unit_price_cost = $row_cost['unit_price'];
			
				if($curr_cost == 'USD') {
					$ttl_cost_usd = $qty_cost * $unit_price_cost;	
					$price_usd_cost = $unit_price_cost;
					$total_usd_cost = $ttl_cost_usd;
					$price_rp_cost = '';
					$total_rp_cost = '';	
					$ttl_rp_cost	= '';		
				} else if($curr_cost == 'RP.') {
					$ttl_rp_cost = $qty_cost * $unit_price_cost;
					$price_rp_cost = $unit_price_cost;
					$total_rp_cost = $ttl_rp_cost;
					$price_usd_cost = '';
					$total_usd_cost = '';
					$ttl_cost_usd = '';				
				} else {
					$price_usd_cost = '';
					$total_usd_cost = '';
					$ttl_cost_usd = '';
					$price_rp_cost = '';
					$total_rp_cost = '';	
					$ttl_rp_cost	= '';
				}			
			
				$code_service_cost = $this->type_service_model->getDataCodeService($row_cost['item_service']);
				$this->fpdf->Cell(40,5,substr($row_cost['item_service'],0,20),'','','L',false);
				$this->fpdf->Cell(15,5,$code_service_cost,'','','C',false);
				$this->fpdf->Cell(19,5,$this->fungsi->pecah4($qty_cost,' '),'','','C',false);			
				$this->fpdf->Cell(19,5,$this->fungsi->pecah($price_usd_cost,' '),'','','R',false);
				$this->fpdf->Cell(19,5,$this->fungsi->pecah($total_usd_cost,' '),'','','R',false);		
				$this->fpdf->Cell(19,5,$this->fungsi->pecah($price_rp_cost,' '),'','','R',false);			
				$this->fpdf->Cell(19,5,$this->fungsi->pecah($total_rp_cost,' '),'','','R',false);							
				$this->fpdf->Cell(10,5,substr($row_cost['prep_coll'],0,1),'','','C',false);
				$this->fpdf->Cell(19,5,$row_cost['payable_to'],'','','L',false);
				$this->fpdf->Cell(19,5,$invoice_cost,'','','C',false);
				$this->fpdf->Ln();
			
				$totalnya_usd_cost[] = $ttl_cost_usd;
				$totalnya_rp_cost[] = $ttl_rp_cost;
			}
		
			$data_cost_2 = $this->afg_m_cost->get_cost_pdf_2($kode_ref);
			if($data_cost_2) {
				foreach ($data_cost_2->result_array() as $row_cost_2 ) 
				{
					$qty_cost_2 = $row_cost_2['qty'];
					if($qty_cost_2=="") {
						$qty_cost_2=$data['bl_meas'];
					}
					
					$curr_cost_2 = $row_cost_2['currency'];
					$unit_price_cost_2 = $row_cost_2['unit_price'];
			
					if($curr_cost_2 == 'USD') {
						$ttl_cost_usd_2 = $qty_cost_2 * $unit_price_cost_2;	
						$price_usd_cost_2 = $unit_price_cost_2;
						$total_usd_cost_2 = $ttl_cost_usd_2;
						$price_rp_cost_2 = '';
						$total_rp_cost_2 = '';	
						$ttl_rp_cost_2	= '';		
					} else if($curr_cost_2 == 'RP.') {
						$ttl_rp_cost_2 = $qty_cost_2 * $unit_price_cost_2;
						$price_rp_cost_2 = $unit_price_cost_2;
						$total_rp_cost_2 = $ttl_rp_cost_2;
						$price_usd_cost_2 = '';
						$total_usd_cost_2 = '';
						$ttl_cost_usd_2 = '';				
					} else {
						$price_usd_cost_2 = '';
						$total_usd_cost_2 = '';
						$ttl_cost_usd_2 = '';
						$price_rp_cost_2 = '';
						$total_rp_cost_2 = '';	
						$ttl_rp_cost_2	= '';
					}
			
					$invoice_cost_2 = '';
			
					$code_service_cost_2 = $this->type_service_model->getDataCodeService($row_cost_2['item_service']);
					$this->fpdf->Cell(40,5,substr("* ". $row_cost_2['item_service'],0,20),'','','L',false);
					$this->fpdf->Cell(15,5,$code_service_cost_2,'','','C',false);
					$this->fpdf->Cell(19,5,$this->fungsi->pecah4($qty_cost_2,' '),'','','C',false);			
					$this->fpdf->Cell(19,5,$this->fungsi->pecah($price_usd_cost_2,' '),'','','R',false);
					$this->fpdf->Cell(19,5,$this->fungsi->pecah($total_usd_cost_2,' '),'','','R',false);		
					$this->fpdf->Cell(19,5,$this->fungsi->pecah($price_rp_cost_2,' '),'','','R',false);			
					$this->fpdf->Cell(19,5,$this->fungsi->pecah($total_rp_cost_2,' '),'','','R',false);							
					$this->fpdf->Cell(10,5,substr($row_cost_2['prep_coll'],0,1),'','','C',false);
					$this->fpdf->Cell(19,5,$row_cost_2['payable_to'],'','','L',false);
					$this->fpdf->Cell(19,5,$invoice_cost_2,'','','C',false);
					$this->fpdf->Ln();
					
					$totalnya_usd_cost_2[] = $ttl_cost_usd_2;
					$totalnya_rp_cost_2[] = $ttl_rp_cost_2;
				}
		
			}
		
			$data_cost_3 = $this->afg_m_cost->get_cost_pdf_3($kode_ref);
			if($data_cost_3) {
				foreach ($data_cost_3->result_array() as $row_cost_3 ) 
				{
					$qty_cost_3 = $row_cost_3['qty'];
					if($qty_cost_3=="") {
						$qty_cost_3=$data['bl_meas'];
					}
					
					$curr_cost_3 = $row_cost_3['currency'];
					$unit_price_cost_3 = $row_cost_3['unit_price'];
			
					if($curr_cost_3 == 'USD') {
						$ttl_cost_usd_3 = $qty_cost_3 * $unit_price_cost_3;	
						$price_usd_cost_3 = $unit_price_cost_3;
						$total_usd_cost_3 = $ttl_cost_usd_3;
						$price_rp_cost_3 = '';
						$total_rp_cost_3 = '';	
						$ttl_rp_cost_3	= '';		
					} else if($curr_cost_3 == 'RP.') {
						$ttl_rp_cost_3 = $qty_cost_3 * $unit_price_cost_3;
						$price_rp_cost_3 = $unit_price_cost_3;
						$total_rp_cost_3 = $ttl_rp_cost_3;
						$price_usd_cost_3 = '';
						$total_usd_cost_3 = '';
						$ttl_cost_usd_3 = '';				
					} else {
						$price_usd_cost_3 = '';
						$total_usd_cost_3 = '';
						$ttl_cost_usd_3 = '';
						$price_rp_cost_3 = '';
						$total_rp_cost_3 = '';	
						$ttl_rp_cost_3	= '';
					}
			

					$invoice_cost_3 = '';
			
					$code_service_cost_3 = $this->type_service_model->getDataCodeService($row_cost_3['item_service']);
					$this->fpdf->Cell(40,5,substr("* ". $row_cost_3['item_service'],0,20),'','','L',false);
					$this->fpdf->Cell(15,5,$code_service_cost_3,'','','C',false);
					$this->fpdf->Cell(19,5,$this->fungsi->pecah4($qty_cost_3,' '),'','','C',false);			
					$this->fpdf->Cell(19,5,$this->fungsi->pecah($price_usd_cost_3,' '),'','','R',false);
					$this->fpdf->Cell(19,5,$this->fungsi->pecah($total_usd_cost_3,' '),'','','R',false);		
					$this->fpdf->Cell(19,5,$this->fungsi->pecah($price_rp_cost_3,' '),'','','R',false);			
					$this->fpdf->Cell(19,5,$this->fungsi->pecah($total_rp_cost_3,' '),'','','R',false);							
					$this->fpdf->Cell(10,5,substr($row_cost_3['prep_coll'],0,1),'','','C',false);
					$this->fpdf->Cell(19,5,$row_cost_3['payable_to'],'','','L',false);
					$this->fpdf->Cell(19,5,$invoice_cost_3,'','','C',false);
					$this->fpdf->Ln();
					
					$totalnya_usd_cost_3[] = $ttl_cost_usd_3;
					$totalnya_rp_cost_3[] = $ttl_rp_cost_3;
				}
		
			}
		
			$data_cost_4 = $this->afg_m_cost->get_cost_pdf_4($kode_ref);
			if($data_cost_4) {
				$qtygbl = $this->afg_m_cost->get_cost_pdf_5($kode_ref);
					foreach ($qtygbl as $row){
						$hasil[]=$row['bl_meas'];
					}
				$nya = array_sum($hasil);
				foreach ($data_cost_4->result_array() as $row_cost_4 ) 
				{
					$qty_cost_4 = substr($data['bl_meas'], 0,4).' / '.$nya;
					$curr_cost_4 = $row_cost_4['currency'];
					$unit_price_cost_4 = $row_cost_4['unit_price'];
					if($curr_cost_4 == 'USD') {
						$ttl_cost_usd_4 = $qty_cost_4 * $unit_price_cost_4;	
						$price_usd_cost_4 = $unit_price_cost_4;
						$total_usd_cost_4 = $ttl_cost_usd_4;
						$price_rp_cost_4 = '';
						$total_rp_cost_4 = '';	
						$ttl_rp_cost_4	= '';		
					} else if($curr_cost_4 == 'RP.') {
						$ttl_rp_cost_4 = $qty_cost_4 * $unit_price_cost_4;
						$price_rp_cost_4 = $unit_price_cost_4;
						$total_rp_cost_4 = $ttl_rp_cost_4;
						$price_usd_cost_4 = '';
						$total_usd_cost_4 = '';
						$ttl_cost_usd_4 = '';				
					}
					
					$code_service_cost_4 = $this->type_service_model->getDataCodeService($row_cost_4['item_service']);
					$this->fpdf->Cell(40,5,substr("<> ". $row_cost_4['item_service'],0,20),'','','L',false);
					$this->fpdf->Cell(15,5,$code_service_cost_4,'','','C',false);
			//		$this->fpdf->Cell(19,5,$this->fungsi->pecah4($qty_cost_4,' '),'','','C',false);			
					$this->fpdf->Cell(19,5,$qty_cost_4,'','','C',false);			
					$this->fpdf->Cell(19,5,$this->fungsi->pecah($price_usd_cost_4,' '),'','','R',false);
					$this->fpdf->Cell(19,5,$this->fungsi->pecah($total_usd_cost_4,' '),'','','R',false);		
					$this->fpdf->Cell(19,5,$this->fungsi->pecah($price_rp_cost_4,' '),'','','R',false);			
					$this->fpdf->Cell(19,5,$this->fungsi->pecah($total_rp_cost_4,' '),'','','R',false);							
					$this->fpdf->Cell(10,5,substr($row_cost_4['prep_coll'],0,1),'','','C',false);
					$this->fpdf->Cell(19,5,$row_cost_4['payeble_to'],'','','L',false);
					$this->fpdf->Cell(19,5,$invoice_cost_4,'','','C',false);
					$this->fpdf->Ln();
					
					$totalnya_usd_cost_4[] = $ttl_cost_usd_4;
					$totalnya_rp_cost_4[] = $ttl_rp_cost_4;
				}
		
			}
		
						
			if(!empty($totalnya_usd_cost)) {		
				$tuc_1 = array_sum($totalnya_usd_cost);
			}
			if(!empty($totalnya_usd_cost_2)) {		
				$tuc_2 = array_sum($totalnya_usd_cost_2);
			}
			if(!empty($totalnya_usd_cost_3)) {		
				$tuc_3 = array_sum($totalnya_usd_cost_3);
			}
			if(!empty($totalnya_usd_cost_4)) {		
				$tuc_4 = array_sum($totalnya_usd_cost_4);
			}
		
			if(!empty($totalnya_rp_cost)) {
				$trc_1 = array_sum($totalnya_rp_cost);
			}
			if(!empty($totalnya_rp_cost_2)) {		
				$trc_2 = array_sum($totalnya_rp_cost_2);
			}	
			if(!empty($totalnya_rp_cost_3)) {		
				$trc_3 = array_sum($totalnya_rp_cost_3);
			}			
			if(!empty($totalnya_rp_cost_4)) {		
				$trc_4 = array_sum($totalnya_rp_cost_4);
			}
			
			$totalnya_usd_cost_all = $tuc_1 + $tuc_2 + $tuc_3 + $tuc_4;
			$totalnya_rp_cost_all = $trc_1 + $trc_2 + $trc_3 + $trc_4;
			
			if(!empty($totalnya_usd_cost_all)) {
				$hasil_usd_cost = $totalnya_usd_cost_all;
			}
		
			if(!empty($totalnya_rp_cost_all)) {
				$hasil_rp_cost = $totalnya_rp_cost_all;
			}
			$this->fpdf->SetFont('Arial','B',7);
			$this->fpdf->Cell(40,5,'TOTAL COST','TB','','L',true);
			$this->fpdf->Cell(15,5,'','TB','','C',true);
			$this->fpdf->Cell(19,5,'','TB','','C',true);
			$this->fpdf->Cell(19,5,'USD','TB','','R',true);
			$this->fpdf->Cell(19,5,$this->fungsi->pecah($hasil_usd_cost,' '),'TB','','R',true);
			$this->fpdf->Cell(19,5,'RP.','TB','','R',true);
			$this->fpdf->Cell(19,5,$this->fungsi->pecah($hasil_rp_cost,' '),'TB','','R',true);
			$this->fpdf->Cell(10,5,'=','TB','','C',true);
			$this->fpdf->Cell(10,5,'RP.','TB','','R',true);
			$conversi_cost = $data['kurs'] * $hasil_usd_cost;
			$total_global_cost = $conversi_cost + $hasil_rp_cost;
			$this->fpdf->Cell(28,5,$this->fungsi->pecah($total_global_cost,' '),'TB',1,'R',true);			
		}
			
		$duo_to_gcl = $this->dbx->get_where('inf_income_from_agent',array('kode_ref'=>$kode_ref));		
		if($duo_to_gcl->num_rows() > 0 ) {
			$row_dtg = $duo_to_gcl->row_array();
			
			$qty_dtg = $row_dtg['qty']; 
			if($row_dtg['qty'] == '' ) {
				$qty_dtg = $this->dbx->get_where('trnstuffingplandetail',array('kode_ref'=>$kode_ref))->row()->bl_meas; 
			}
			
			$cur_dtg = $row_dtg['currency'];
			if($cur_dtg == 'USD') {
				$price_dtg_usd = $row_dtg['unit_price']; 
				$ttl_price_usd = $price_dtg_usd * $qty_dtg;
				$price_dtg_rp = ''; 
				$ttl_price_rp = '';
			} else {
				$price_dtg_rp = $row_dtg['unit_price'];
				$ttl_price_rp = $price_dtg_rp * $qty_dtg;
				$ttl_price_usd = '';
				$price_dtg_usd = '';
			}
		
			$dtg_to_rp = 	$ttl_price_usd * $kurs;
			if(!empty($dtg_to_rp)){
				$dtg_to_rp = $dtg_to_rp;
			}
			
		} else {
			$qty_dtg = '';	
			$price_dtg_usd = '';
			$ttl_price_usd = '';
			$price_dtg_rp = '';
			$ttl_price_rp = '';
			$dtg_to_rp = '';
		}
	
		$this->fpdf->Ln(10);
	
		$this->fpdf->Cell(55,5,'DUE TO GCL-(INTENSIVE)','TB','','L',true);
		$this->fpdf->Cell(19,5,$this->fungsi->pecah4($qty_dtg,' '),'TB','','C',true);
		$this->fpdf->Cell(19,5,$this->fungsi->pecah($price_dtg_usd,' '),'TB','','R',true);
		$this->fpdf->Cell(19,5,$this->fungsi->pecah($ttl_price_usd,' '),'TB','','R',true);
		$this->fpdf->Cell(19,5,$this->fungsi->pecah($price_dtg_rp,' '),'TB','','R',true);
		$this->fpdf->Cell(19,5,$this->fungsi->pecah($ttl_price_rp,' '),'TB','','R',true);
		$this->fpdf->Cell(10,5,'=','TB','','C',true);
		$this->fpdf->Cell(10,5,'RP.','TB','','R',true);
		$this->fpdf->Cell(25,5,$this->fungsi->pecah($dtg_to_rp,' '),'TB','','R',true);	
		$this->fpdf->Cell(3,5,' ','TB','','C',true);
		
		if(!empty($totalnya_usd) || !empty($totalnya_rp)) {
			$totalnya_usd = array_sum($totalnya_usd);
			$totalnya_rp = array_sum($totalnya_rp);
		}
	
		$profi_sbl_pjk_usd = (  $totalnya_usd - $totalnya_usd_cost_all) + $ttl_price_usd;
		$profi_sbl_pjk_rp = ( $totalnya_rp - $totalnya_rp_cost_all) + $ttl_price_rp;
		
		if(!empty($profi_sbl_pjk_usd) || !empty($profi_sbl_pjk_usd)) {
			$profi_sbl_pjk_usd = $profi_sbl_pjk_usd;
			$profi_sbl_pjk_rp = $profi_sbl_pjk_rp;
		}
	
		$ttl_profi_sbl_pjk = ($total_global_income - $total_global_cost) + $dtg_to_rp;
		if(!empty($ttl_profi_sbl_pjk)) {
			$ttl_profi_sbl_pjk = $ttl_profi_sbl_pjk;
		}
	
		$this->fpdf->Ln(5);
		$this->fpdf->Cell(5,3,'','','','L',false);
		$this->fpdf->Ln(5);
		$this->fpdf->Cell(55,5,'PROFIT SEBELUM PAJAK','TB','','L',true);
		$this->fpdf->Cell(19,5,'','TB','','C',true);
		$this->fpdf->Cell(19,5,'USD','TB','','R',true);
		$this->fpdf->Cell(19,5,$this->fungsi->pecah($profi_sbl_pjk_usd,' '),'TB','','R',true);
		$this->fpdf->Cell(19,5,'RP.','TB','','R',true);
		$this->fpdf->Cell(19,5,$this->fungsi->pecah($profi_sbl_pjk_rp,' '),'TB','','R',true);
		$this->fpdf->Cell(10,5,'=','TB','','C',true);
		$this->fpdf->Cell(10,5,'RP.','TB','','R',true);
		$this->fpdf->Cell(25,5,$this->fungsi->pecah($ttl_profi_sbl_pjk,' '),'TB','','R',true);	
		$this->fpdf->Cell(3,5,' ','TB','','C',true);
		
		
		$this->fpdf->Ln(5);
		$this->fpdf->Cell(5,3,'','','','L',false);
		$this->fpdf->Ln(5);
		$this->fpdf->Cell(198,5,'AGENT : '.$this->agent_company_model->getAgentCompanyByCode($data['agent_code']),0,'','L',false);
		$this->fpdf->Ln();	

		$this->fpdf->Cell(124,5,'','T',0,'L',true);
		$this->fpdf->Cell(38,5,'BALANCE TO','T',0,'C',true);	
		$this->fpdf->Cell(36,5,'','T',0,'L',true);	
		$this->fpdf->Ln();
		$this->fpdf->Cell(55,5,'DETAIL OF CHARGE','B','','L',true);
		$this->fpdf->Cell(19,5,'','B','','C',true);
		$this->fpdf->Cell(25,5,'INCOME','B','','C',true);
		$this->fpdf->Cell(25,5,'COST','B','','C',true);				
		$this->fpdf->Cell(19,5,'ASIA','B','','C',true);
		$this->fpdf->Cell(19,5,'AGENT','B','','C',true);
		$this->fpdf->Cell(36,5,'FREIGHT LIST NO.','B',0,'C',true);
		
		$this->fpdf->Ln(5);
		$this->fpdf->Cell(5,3,'','','','L',false);
		$this->fpdf->Ln();
		
		$q_agent = $this->afg_m_job_sheet->get_detail_agent($data['agent_code']);
		foreach ($q_agent->result_array() as $row_q_agent ) 
		{
			if($data['bl_meas'] < $mchgs ) {
				$kali_agent = $mchgs * $row_q_agent['unit_price'];
			} else {
				$kali_agent = $quantity * $row_q_agent['unit_price'];
			}
			$this->fpdf->Cell(55,5,'DUE TO '.$this->agent_company_model->getAgentCompanyByCode($data['agent_code']),'','','L',false);
			$this->fpdf->Cell(19,5,'','','','C',false);
			$this->fpdf->Cell(25,5,'','','','C',false);
			$this->fpdf->Cell(25,5, $this->fungsi->pecah4($kali_agent,' '),'','','C',false);				
			$this->fpdf->Cell(19,5,'','','','C',false);
			$this->fpdf->Cell(19,5,'','','','C',false);
			$this->fpdf->Cell(36,5,'','',0,'C',false);
			$this->fpdf->Ln();
		}
	
		if(!empty($ttl_price_usd)) {
			$dou_to = $ttl_price_usd;
		} else {
			$dou_to = $ttl_price_rp;
		}
	
		$this->fpdf->Cell(55,5,'DUE TO GCL','B','','L',false);
		$this->fpdf->Cell(19,5,'','B','','C',false);
		$this->fpdf->Cell(25,5,$this->fungsi->pecah($dou_to,' '),'B','','C',false);
		$this->fpdf->Cell(25,5,'','B','','C',false);				
		$this->fpdf->Cell(19,5,'','B','','C',false);
		$this->fpdf->Cell(19,5,'','B','','C',false);
		$this->fpdf->Cell(36,5,'','B',0,'C',false);
		$this->fpdf->Ln(15);
		
		$this->fpdf->Cell(70,5,'PRINT BY : '.$this->session->userdata('fullname'),' ','','L',false);
		$this->fpdf->Cell(70,5,'OPERATION / CUSTOMER SERVICE :',' ','','L',false);				
		$this->fpdf->Cell(70,5,'ACCOUNTING :',' ','','L',false);
		$this->fpdf->Ln();
		if($print==true) {
			$this->fpdf->Output('JOB-SHEET-'.$kode_ref,'I');
		} else {
			$data_jobsheet = array(
			'kode_ref'=>$kode_ref,
			'profit'=>$ttl_profi_sbl_pjk,
			'income'=>$total_global_income,
			'cost'=>$total_global_cost,
			'profit_usd'=>$profi_sbl_pjk_usd,
			'profit_rp'=>$profi_sbl_pjk_rp,
			'income_usd'=>$hasil_usd,
			'income_rp'=>$hasil_rp,
			'cost_usd'=>$hasil_usd_cost,
			'cost_rp'=>$hasil_rp_cost,
			'inv_agent'=>$kali_agent,
			'cost_coloader'=>'',
			);
			$this->afg_m_job_sheet->save_job_sheet($data_jobsheet);
		}
	}
