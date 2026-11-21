<?php
class Afg_c_pre_alert extends CI_Controller {
	var $title = 'Stuffing Plan';
	
	public function __construct()
	{
		parent::__construct();
		$this->fungsi->restrict();
		$this->load->model(array(
			'user_model',
			'afg_m_stuffing_plan',
			'afg_m_prealert',
			'reference/type_service_model',
			'report/m_rekap_sales'
		));
		$this->fungsi->dbx = $this->afg_m_prealert->dbx;		
		$this->dbx = $this->afg_m_prealert->dbx;		
	}
	
	function show_pre_alert($kdr)
	{
		$giusd[] = "";
		$girp[] = "";
		$ttl_inc_usd= array();
		$ttl_inc_rp= array();

		$region = $this->session->userdata('region_id');
		
		$this->load->model(array('afg_m_stuffing_plan',"afg_m_job_sheet"));
		
		$this->load->library('FPDF_MC_Table');
		$pdf=new FPDF_MC_Table();	
		
		$data = $this->afg_m_prealert->get_data_spd($kdr);
		if (empty($data)) {
			show_error('Data job sheet tidak ditemukan.', 404);
			return;
		}

		// Satu query untuk seluruh mapping service, bukan query per baris charge.
		$serviceMap = $this->type_service_model->getServiceCodeMap();
		for($i=0;$i<count($data);$i++) {
			$pdf->Addpage('P','A4');
			$pdf->SetLineWidth(0.1);
			$pdf->SetFont('Arial','b',15);
			$pdf->SetXY(90, 10);
			$pdf->write(0,'JOB SHEET');
		
			$pdf->SetFont('Arial','',8);
			$pdf->SetXY(175, 10);
			$pdf->write(0, date('d/m/Y'));

			$pdf->SetFont('Arial','',8);
			$pdf->SetXY(5, 25);
			$pdf->write(0,'JOB NO');
			$pdf->SetXY(30, 25);
			$pdf->write(0,':');
			$pdf->SetXY(35, 25);
			$pdf->write(0,$data[$i]["kdr"]);
		
			$pdf->SetXY(5, 29);
			$pdf->write(0,'DATE');
			$pdf->SetXY(30, 29);
			$pdf->write(0,':');
			$pdf->SetXY(35, 29);
			$pdf->write(0,date("d/m/Y"));
			
			$imp = substr($data[$i]["kdr"],4,3);
			if($imp=='300' || $imp=='301' || $imp=='302' || $imp=='400' ||$imp=='402'|| $imp=='600') {
				$shipper = substr($data[$i]['consignee'],0,25);
			}
			else 
			{
				$shipper = $data[$i]['shipper_print'];
			}
			
			$pdf->SetXY(5, 33);
			$pdf->write(0,'SHIPPER');
			$pdf->SetXY(30, 33);
			$pdf->write(0,':');
			$pdf->SetXY(35, 33);
			$pdf->write(0,strtoupper($shipper));
			
			$pdf->SetXY(5, 37);
			$pdf->write(0,'CONSIGNEE');
			$pdf->SetXY(30, 37);
			$pdf->write(0,':');
			$pdf->SetXY(35, 37);
		
			if($imp=='300' || $imp=='301' || $imp=='302' || $imp=='400' ||$imp=='402'|| $imp=='600') {
				$consignee = $data[$i]['shipper_import'];
			}
			else 
			{
				$consignee = strtoupper($data[$i]['consignee']?? '');
			}
		
			$pdf->Write(0,substr($consignee,0,30));
			
			$pdf->SetXY(5, 41);
			$pdf->write(0,'NOTIFY');
			$pdf->SetXY(30, 41);
			$pdf->write(0,':');
			$pdf->SetXY(35, 41);
			$notify = strtoupper($data[$i]['notify_add']??'');
			$pdf->Write(0,substr($notify, 0,30));
		
			$pdf->SetXY(5, 45);
			$pdf->write(0,'SALES BY');
			$pdf->SetXY(30, 45);
			$pdf->write(0,':');
			$pdf->SetXY(35, 45);
			$salesName = trim((string)($data[$i]['sales_name'] ?? ''));
			if ($salesName === '') {
				$salesName = (string)$this->fungsi->get_user_by_id($data[$i]['sales']);
			}
			$pdf->write(0, strtoupper($salesName));
			
			$pdf->SetXY(5, 49);
			$pdf->write(0,'SHIPPING CO');
			$pdf->SetXY(30, 49);
			$pdf->write(0,':');
			$pdf->SetXY(35, 49);
			$pdf->Write(0,substr($data[$i]['carrier'],0,45));
			
			$pdf->SetXY(5, 53);
			$pdf->write(0,'WAREHOUSE');
			$pdf->SetXY(30, 53);
			$pdf->write(0,':');
			$pdf->SetXY(35, 53);
			$pdf->write(0, $data[$i]['warehouse']);
			
			$detail = $this->afg_m_job_sheet->get_detail_job_for_pdf($data[$i]["spd"]);
			
			$pdf->SetXY(5, 57);
			$pdf->write(0,'TRUCKING');
			$pdf->SetXY(30, 57);
			$pdf->write(0,':');
			$pdf->SetXY(35, 57);
			$pdf->write(0, strtoupper((string)($detail->trucking_name ?? '')));
		
			$pdf->SetXY(5, 61);
			$pdf->write(0,'DESC. OF GOOD');
			$pdf->SetXY(30, 61);
			$pdf->write(0,':');
		
			$pdf->SetFont('Arial','',8);
			$pdf->SetXY(120, 25);
			$pdf->write(0,'VESEL');
			$pdf->SetXY(150, 25);
			$pdf->write(0,':');
			$pdf->SetXY(155, 25);
			
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
			
			$pdf->write(0,$vessel ??  $data[$i]['vessel']. ' ' . $data[$i]['voyage']);		
			$pdf->SetXY(120, 29);
			$pdf->write(0,'ETD / ETA ');
			$pdf->SetXY(150, 29);
			$pdf->write(0,':');
			$pdf->SetXY(155, 29);
			$pdf->write(0,$this->fungsi->jin_date_str_strip($vessel_etd_date ?? $data[$i]['etd']).' / '. $this->fungsi->jin_date_str_strip($vessel_eta_date ?? $data[$i]['eta']) );
			
			if($detail->last_snd_vessel !='') 
			{
				$con_vessel = $detail->last_snd_vessel;
				$con_vessel_etd = $detail->last_snd_vessel_etd;
				$con_vessel_eta = $detail->last_snd_vessel_eta;
				$con_vessel_etd_date = $detail->last_snd_vessel_etd_date;
				$con_vessel_eta_date = $detail->last_snd_vessel_eta_date;
			}
			else 
			{
				$con_vessel = $detail->snd_vessel;
				$con_vessel_etd = $detail->snd_vessel_etd;
				$con_vessel_eta = $detail->snd_vessel_eta;
				$con_vessel_etd_date = $detail->snd_vessel_etd_date;
				$con_vessel_eta_date = $detail->snd_vessel_eta_date;
			}
			
			$pdf->SetXY(120, 33);
			$pdf->write(0,'CONN VESEL');
			$pdf->SetXY(150, 33);
			$pdf->write(0,':');
			$pdf->SetXY(155, 33);			
			$pdf->write(0, strtoupper($con_vessel ?? ''));				
			$pdf->SetXY(120, 37);
			$pdf->write(0,'ETD '. strtoupper($con_vessel_etd ?? '').' / '.'ETA '. strtoupper($con_vessel_eta ?? ''));
			$pdf->SetXY(150, 37);
			$pdf->write(0,':');
			$pdf->SetXY(155, 37);
			$pdf->write(0,$this->fungsi->jin_date_str_strip($con_vessel_etd_date).' / '.$this->fungsi->jin_date_str_strip($con_vessel_eta_date) );
			
			$pdf->SetXY(120, 41);
		$pdf->write(0,'DESTINATION');
		$pdf->SetXY(150, 41);
		$pdf->write(0,':');
		$pdf->SetXY(155, 41);
		$pdf->write(0,$data[$i]['destination']);
		
		$pdf->SetXY(120, 45);
		$pdf->write(0,'PORT DISCHARGE');
		$pdf->SetXY(150, 45);
		$pdf->write(0,':');
		$pdf->SetXY(155, 45);
		$pdf->write(0,substr($data[$i]['destination'],0,21));
		
		$pdf->SetXY(120, 49);
		$pdf->write(0,'CONTAINER');
		$pdf->SetXY(150, 49);
		$pdf->write(0,':');
		$pdf->SetXY(155, 49);
		$pdf->write(0,$data[$i]['type_cont_qty'].'  X  '.$data[$i]['type_cont_size']);
		
		$pdf->SetXY(120, 53);
		$pdf->write(0,'CONT.NO/SEAL NO');
		$pdf->SetXY(150, 53);
		$pdf->write(0,':');
		$pdf->SetXY(155, 53);
		$pdf->write(0,$data[$i]['cont_seal_no']);
		
		if($imp=='300' || $imp=='600' || $imp=='400') 
		{
			$bl_no = $this->afg_m_job_sheet->get_hb_l($data[$i]["kdr"]);
		}
		else 
		{
			$bl_no = $data[$i]['hb_l'];
		}
		
		$pdf->SetXY(120, 57);
		$pdf->write(0,'BL NUMBER');
		$pdf->SetXY(150, 57);
		$pdf->write(0,':');
		$pdf->SetXY(155, 57);
		$pdf->write(0,$bl_no);
		
		$pdf->SetXY(120, 61);
		$pdf->write(0,'KURS RP.');
		$pdf->SetXY(150, 61);
		$pdf->write(0,':');
		$pdf->SetXY(155, 61);
		$kurs = floatval($data[$i]['kurs']);
		
		$desc= strtoupper($data[$i]['desc_print']);
		
		if(!empty($kurs)) 
		{
			$uang = $this->fungsi->pecah($kurs,',');
		}
		else 
		{
			$uang = "";
		}
		$pdf->write(0,$uang);
		
		$pdf->Ln(5);
		$pdf->SetXY(35, 59);
		$pdf->MultiCell(80,4,$desc,0,1,0);
		
		$pdf->SetMargins(5,15);	
		$pdf->SetFont('Arial','B',8);
		$pdf->Ln();		
		$pdf->Cell(5,5,'INCOME :');
		$pdf->SetFont('Arial','B',7);
		$pdf->Ln();
		$pdf->SetFillColor(230,230,230);
		$pdf->SetTextColor(100,100,100);
		$pdf->Cell(40,5,'DETAIL OF CHARGE','TB','','C',true);
		$pdf->Cell(15,5,'KODE','TB','','C',true);
		$pdf->Cell(19,5,'QUANTITY','TB','','C',true);
		$pdf->Cell(19,5,'PRICE (USD)','TB','','C',true);
		$pdf->Cell(19,5,'TOTAL (USD)','TB','','C',true);
		$pdf->Cell(19,5,'PRICE (RP.)','TB','','C',true);
		$pdf->Cell(19,5,'TOTAL (RP.)','TB','','C',true);
		$pdf->Cell(10,5,'P/C','TB','','C',true);
		$pdf->Cell(19,5,'RECEIVED','TB','','C',true);
		$pdf->Cell(19,5,'INVOICE NO','TB','','C',true);
		$pdf->Ln();
		
		$pdf->SetFont('Arial','',7);
		$ttl_inc_usd = array();
		$ttl_inc_idr = array();
		$iusd3 = array();
		$crp3_income = array();
		$cusd0 = array(); $crp0 = array();
		$cusd1 = array(); $crp1 = array();
		$cusd2 = array(); $crp2 = array();
		$cusd3 = array(); $crp3 = array();

		// agent_code sudah ikut query utama; fallback hanya untuk data lama yang kosong.
		$agent = trim((string)($data[$i]['agent_code'] ?? ''));
		if ($agent === '') {
			$agent = (string)$this->afg_m_stuffing_plan->get_agent($data[$i]['spd']);
		}

		// Semua dataset halaman diload satu kali.
		$income    = $this->afg_m_job_sheet->get_income_job_sheet($data[$i]['kdr']);
		$gblincome = $this->afg_m_job_sheet->get_income_global($kdr);
		$cost0     = $this->afg_m_job_sheet->get_cost_job_sheet('inf_cost_tbl_satu', $data[$i]['kdr']);
		$cost1     = $this->afg_m_job_sheet->get_cost_job_sheet('inf_cost_refund_discount', $data[$i]['kdr']);
		$cost2     = $this->afg_m_job_sheet->get_cost_job_sheet('inf_cost_refund_sc', $data[$i]['kdr']);
		$cost3     = $this->afg_m_job_sheet->get_cost_job_sheet('x_glbcost', $data[$i]['kdr']);

		// Global allocation dihitung hanya sekali per halaman, lalu dipakai ulang
		// oleh income, cost, total income dan total cost.
		$globalQty = $this->afg_m_stuffing_plan->get_qty_gbl_cost($data[$i]['kdr'], 'USD');
		$qty_1_display = $this->fungsi->pecah4($globalQty[0], ',');
		$qty_total_display = $this->fungsi->pecah4($globalQty[1], ',');
		$qty_1 = (float)$qty_1_display;
		$qty_total = (float)$qty_total_display;
		$htg = ($qty_total != 0.0) ? ($qty_1 / $qty_total) : 0.0;

		// Semua Credit Note yang dibutuhkan halaman ini diambil dalam satu query.
		$cnItems = array();
		foreach (array($gblincome, $cost0, $cost1, $cost2, $cost3) as $rows) {
			foreach ((array)$rows as $cnRow) {
				if (!empty($cnRow->item_service)) $cnItems[] = $cnRow->item_service;
			}
		}
		$cnMap = $this->afg_m_job_sheet->get_cn_map_for_pdf($agent, $data[$i]['kdr'], $cnItems);
		$dnNumber = $this->afg_m_job_sheet->get_dn_for_pdf($agent, $data[$i]['kdr']);

		foreach ($income as $row) {
			$code_service = $serviceMap[strtoupper(trim((string)$row->item_service))] ?? null;
			$qty = $row->qty;
			if ($row->qty < $row->mchgs) {
				$qty = $row->mchgs;
			}

			if ($row->currency == 'USD') {
				$p_i = array(floatval($row->unit_price), floatval($row->unit_price) * floatval($qty), '', '');
				$ttl_inc_usd[] = floatval($row->unit_price) * floatval($qty);
			} else {
				$p_i = array('', '', $row->unit_price, $row->price);
				$ttl_inc_idr[] = floatval($row->unit_price) * floatval($qty);
			}

			// Kode lama mencari invoice/DN di sini, tetapi variabel $nomor tidak pernah
			// dicetak ke PDF. Query tersebut sengaja dihapus karena tidak memengaruhi output.
			$pdf->Cell(
				40,
				5,
				!empty($row->item_service) ? substr($row->item_service, 0, 20) : '',
				'',
				'',
				'L',
				false
			);
			$pdf->Cell(15,5,$code_service,'','','L',false);
			$pdf->Cell(19,5,$this->fungsi->pecah4($row->qty,','),'','','C',false);
			$pdf->Cell(19,5,$this->fungsi->pecah($p_i[0],','),'','','R',false);
			$pdf->Cell(19,5,$this->fungsi->pecah($p_i[1],','),'','','R',false);
			$pdf->Cell(19,5,$this->fungsi->pecah($p_i[2],','),'','','R',false);
			$pdf->Cell(19,5,$this->fungsi->pecah($p_i[3],','),'','','R',false);
			$pdf->Cell(10,5,substr($row->prep_coll,0,1),'','','C',false);
			$pdf->Cell(19,5,'','','','C',false);
			$pdf->Cell(19,5,$row->ship_co ?? '-',0,1,'C',false);
		}

		foreach ($gblincome as $row)
		{
			$code_service = $serviceMap[strtoupper(trim((string)$row->item_service))] ?? null;
			$ttl = (float)$row->price * $htg;

			if ($row->currency == 'USD') {
				$pc3 = array($row->price, $ttl, '', '');
				$iusd3[] = (float)$row->price * $htg;
			} else {
				$pc3 = array('', '', $row->unit_price, $ttl);
				$crp3_income[] = (float)$row->price * $htg;
			}

			$pdf->Cell(40,5,strtoupper(substr('<> '.$row->item_service,0,20)),'','','L',false);
			$pdf->Cell(15,5,$code_service,'','','L',false);
			$pdf->Cell(19,5,$qty_1_display.' / '.$this->fungsi->pecah($qty_total_display),'','','C',false);
			$pdf->Cell(19,5,$this->fungsi->pecah($pc3[0],','),'','','R',false);
			$pdf->Cell(19,5,$this->fungsi->pecah($pc3[1],','),'','','R',false);
			$pdf->Cell(19,5,$this->fungsi->pecah($pc3[2],','),'','','R',false);
			$pdf->Cell(19,5,$this->fungsi->pecah($pc3[3],','),'','','R',false);
			$pdf->Cell(10,5,substr($row->prep_coll,0,1),'','','C',false);
			$pdf->Cell(19,5,$row->payeble_to,'','','L',false);
			$pdf->Cell(19,5,$cnMap[(string)$row->item_service] ?? '',0,1,'C',false);
		}

		// Fungsi total sudah dioptimasi menjadi aggregate query dan menerima ratio
		// yang sudah dihitung di atas sehingga tidak query qty/global allocation lagi.
		$total_income = $this->afg_m_stuffing_plan->get_total_income($data[$i]['kdr'], $htg);
		$ttl_i_usd = floatval($total_income[0]) + floatval($total_income[2]);
		$ttl_i_rp  = floatval($total_income[1]) + floatval($total_income[3]);
		$conversi = floatval($data[$i]['kurs']) * floatval($ttl_i_usd);
		$total_global_income = floatval($conversi) + floatval($ttl_i_rp);
		
		$pdf->SetFont('Arial','B',7);
		$pdf->Cell(40,5,'TOTAL INCOME','TB','','L',true);
		$pdf->Cell(15,5,'','TB','','C',true);
		$pdf->Cell(19,5,'','TB','','C',true);
		$pdf->Cell(19,5,'USD','TB','','R',true);
		$pdf->Cell(19,5,$this->fungsi->pecah($ttl_i_usd,','),'TB','','R',true);
		$pdf->Cell(19,5,'RP.','TB','','R',true);
		$pdf->Cell(19,5,$this->fungsi->pecah($ttl_i_rp,','),'TB','','R',true);
		$pdf->Cell(10,5,'=','TB','','C',true);
		$pdf->Cell(10,5,'RP.','TB','','R',true);
		$pdf->Cell(25,5,$this->fungsi->pecah($total_global_income,','),'TB','','R',true);
		$pdf->Cell(3,5,' ','TB','','C',true);
		
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('Arial','B',8);
		$pdf->Ln(10);
		$pdf->Cell(5,5,'COST :');
		$pdf->SetFont('Arial','B',7);
		$pdf->Ln();
		$pdf->SetFillColor(230,230,230);
		$pdf->SetTextColor(100,100,100);
		$pdf->Cell(40,5,'DETAIL OF CHARGE','TB','','C',true);
		$pdf->Cell(15,5,'KODE','TB','','C',true);
		$pdf->Cell(19,5,'QUANTITY','TB','','C',true);
		$pdf->Cell(19,5,'PRICE (USD)','TB','','C',true);
		$pdf->Cell(19,5,'TOTAL (USD)','TB','','C',true);
		$pdf->Cell(19,5,'PRICE (RP.)','TB','','C',true);
		$pdf->Cell(19,5,'TOTAL (RP.)','TB','','C',true);
		$pdf->Cell(10,5,'P/C','TB','','C',true);
		$pdf->Cell(19,5,'PAYABLE TO','TB','','C',true);
		$pdf->Cell(19,5,'NO C. NOTE','TB','','C',true);
		$pdf->Ln();		
		$pdf->SetFont('Arial','',7);
		// $cost0 sudah diload sekali di atas.
		foreach ($cost0 as $row ) {
			$code_service0 = $serviceMap[strtoupper(trim((string)$row->item_service))] ?? null;
			
			$qty0 = $row->qty;
			if($row->currency=='USD') {
				$pc0 = array(floatval($row->unit_price), floatval($row->unit_price) * floatval($qty0),'','');
				$cusd0[]= floatval($row->unit_price) * floatval($qty0);
			} else {
				$pc0 = array('','',floatval($row->unit_price),floatval($row->unit_price) * floatval($qty0));
				$crp0[]= floatval($row->unit_price) * floatval($qty0);
			}
			
			$pdf->Cell(40,5,substr($row->item_service,0,20),'','','L',false);
			$pdf->Cell(15,5,$code_service0,'','','L',false);
			$pdf->Cell(19,5,$this->fungsi->pecah4($row->qty,','),'','','C',false);			
			$pdf->Cell(19,5,$this->fungsi->pecah($pc0[0],','),'','','R',false);
			$pdf->Cell(19,5,$this->fungsi->pecah($pc0[1],','),'','','R',false);		
			$pdf->Cell(19,5,$this->fungsi->pecah($pc0[2],','),'','','R',false);			
			$pdf->Cell(19,5,$this->fungsi->pecah($pc0[3],','),'','','R',false);							
			$pdf->Cell(10,5,substr($row->prep_coll,0,1),'','','C',false);
			$pdf->Cell(19,5,strtoupper($row->payable_to??''),'','','L',false);
			$pdf->Cell(19,5,$cnMap[(string)$row->item_service] ?? '',0,1,'C',false);
		}
		
		// $cost1 sudah diload sekali di atas.
		foreach ($cost1 as $row ) {
			$code_service1 = $serviceMap[strtoupper(trim((string)$row->item_service))] ?? null;
			
			$qty1 = $row->qty;
		
			if($row->currency=='USD') {
				$pc1 = array($row->unit_price,floatval($row->unit_price) * floatval($qty1),'','');
				$cusd1[]= floatval($row->unit_price) * floatval($qty1);
			} else {
				$pc1 = array('','',$row->unit_price, floatval($row->unit_price)  * floatval($qty1));
				$crp1[]= floatval($row->unit_price) * floatval($qty1);
			}
			
			$pdf->Cell(40,5,substr("* ".$row->item_service,0,20),'','','L',false);
			$pdf->Cell(15,5,$code_service1,'','','L',false);
			$pdf->Cell(19,5,$this->fungsi->pecah4($row->qty,','),'','','C',false);			
			$pdf->Cell(19,5,$this->fungsi->pecah($pc1[0],','),'','','R',false);
			$pdf->Cell(19,5,$this->fungsi->pecah($pc1[1],','),'','','R',false);		
			$pdf->Cell(19,5,$this->fungsi->pecah($pc1[2],','),'','','R',false);			
			$pdf->Cell(19,5,$this->fungsi->pecah($pc1[3],','),'','','R',false);							
			$pdf->Cell(10,5,substr($row->prep_coll,0,1),'','','C',false);
			$pdf->Cell(19,5,$row->payable_to,'','','L',false);
			$pdf->Cell(19,5,$cnMap[(string)$row->item_service] ?? '',0,1,'C',false);
		}
		
		// $cost2 sudah diload sekali di atas.
		foreach ($cost2 as $row ) 
		{
			$code_service = $serviceMap[strtoupper(trim((string)$row->item_service))] ?? null;
			$invoice = '';
			$qty = $row->qty;

			if($row->currency=='USD') {
				$pc2 = array($row->unit_price, floatval($row->unit_price) * floatval($qty),'','');
				$cusd2[]= floatval($row->unit_price) * floatval($qty);
			} else {
				$pc2 = array('','',$row->unit_price, floatval($row->unit_price) * floatval($qty));
				$crp2[]= floatval($row->unit_price) * floatval($qty);
			}
			
			$pdf->Cell(40,5,substr("* ". $row->item_service,0,20),'','','L',false);
			$pdf->Cell(15,5,$code_service,'','','L',false);
			$pdf->Cell(19,5,$this->fungsi->pecah4($row->qty,','),'','','C',false);			
			$pdf->Cell(19,5,$this->fungsi->pecah($pc2[0],','),'','','R',false);
			$pdf->Cell(19,5,$this->fungsi->pecah($pc2[1],','),'','','R',false);		
			$pdf->Cell(19,5,$this->fungsi->pecah($pc2[2],','),'','','R',false);			
			$pdf->Cell(19,5,$this->fungsi->pecah($pc2[3],','),'','','R',false);							
			$pdf->Cell(10,5,substr($row->prep_coll,0,1),'','','C',false);
			$pdf->Cell(19,5,$row->payable_to,'','','L',false);
			$pdf->Cell(19,5,$cnMap[(string)$row->item_service] ?? '',0,1,'C',false);
		}
		// $cost3, $crp3 dan $cusd3 sudah disiapkan di atas.
		foreach ($cost3 as $row ) 
		{
			$code_service = $serviceMap[strtoupper(trim((string)$row->item_service))] ?? null;
			$invoice = '';
			$ttl = (float)$row->price * $htg;
			if($row->currency=='USD') {
				$pc3= array(floatval($row->price), floatval($ttl),'','');
				$cusd3[]=floatval($row->price) * floatval($htg);
			} else {
				$pc3 = array('','',floatval($row->price), floatval($ttl));
				$crp3[]=floatval($row->price) * floatval($htg);
			}

			$pdf->Cell(40,5,strtoupper(substr("<> ". $row->item_service,0,20)),'','','L',false);
			$pdf->Cell(15,5,$code_service,'','','L',false);
			$pdf->Cell(19,5,$qty_1_display.' / '.$qty_total_display,'','','C',false);
			$pdf->Cell(19,5,$this->fungsi->pecah($pc3[0],','),'','','R',false);
			$pdf->Cell(19,5,$this->fungsi->pecah($pc3[1],','),'','','R',false);		
			$pdf->Cell(19,5,$this->fungsi->pecah($pc3[2],','),'','','R',false);			
			$pdf->Cell(19,5,$this->fungsi->pecah($pc3[3],','),'','','R',false);							
			$pdf->Cell(10,5,substr($row->prep_coll,0,1),'','','C',false);
			$pdf->Cell(19,5,$row->payeble_to,'','','L',false);
			$pdf->Cell(19,5,$cnMap[(string)$row->item_service] ?? '',0,1,'C',false);
		}

		$total_cost = $this->afg_m_stuffing_plan->get_total_cost($data[$i]['kdr'], $htg);
		$convt = floatval($data[$i]['kurs']) * floatval($total_cost[0]);
        $total_global_cost = floatval($convt) + floatval($total_cost[1]);

		$pdf->SetFont('Arial','B',7);
		$pdf->Cell(40,5,'TOTAL COST','TB','','L',true);
		$pdf->Cell(15,5,'','TB','','C',true);
		$pdf->Cell(19,5,'','TB','','C',true);
		$pdf->Cell(19,5,'USD','TB','','R',true);
		$pdf->Cell(19,5,$this->fungsi->pecah($total_cost[0],','),'TB','','R',true);
		$pdf->Cell(19,5,'RP.','TB','','R',true);
		$pdf->Cell(19,5,$this->fungsi->pecah($total_cost[1],','),'TB','','R',true);
		$pdf->Cell(10,5,'=','TB','','C',true);
		$pdf->Cell(10,5,'RP.','TB','','R',true);
		$pdf->Cell(28,5,$this->fungsi->pecah($total_global_cost,','),'TB',1,'R',true);


		$pdf->Ln(2);		
		$ttl = $this->afg_m_stuffing_plan->ttl_debit_note($data[$i]["spd"]);
		
		$pdf->Cell(40,5,'DUE TO GATEWAY FROM '. strtoupper($agent??''),'TB','','L',true);
		$pdf->Cell(15,5,'','TB','','C',true);
		$pdf->Cell(19,5,$dnNumber,'TB','','C',true);
		$pdf->Cell(19,5,"USD",'TB','','R',true);
		$pdf->Cell(19,5,$this->fungsi->pecah($ttl,","),'TB','','R',true);
		$pdf->Cell(19,5,"",'TB','','R',true);
		$pdf->Cell(19,5,"",'TB','','R',true);
		$pdf->Cell(10,5,"",'TB','','C',true);
		$pdf->Cell(10,5,"RP.",'TB','','R',true);
		$pdf->Cell(28,5,$this->fungsi->pecah(floatval($ttl)*floatval($kurs),","),'TB',1,'R',true);

		$pdf->Ln(2);
		$pdf->SetFont('Arial','B',8);
		$pdf->Cell(40,8,'PROFIT JOB','TB','','L',true);
		$pdf->Cell(15,8,'','TB','','C',true);
		$pdf->Cell(19,8,'','TB','','C',true);
		$pdf->Cell(19,8,'USD','TB','','R',true);
		$pdf->Cell(19,8,$this->fungsi->pecah(floatval($ttl_i_usd) - $total_cost[0] + floatval($ttl),","),'TB','','R',true);
		$pdf->Cell(19,8,'RP.','TB','','R',true);
		$pdf->Cell(19,8,$this->fungsi->pecah(floatval($ttl_i_rp) - floatval($total_cost[1]),","),'TB','','R',true);
		$pdf->Cell(10,8,'=','TB','','C',true);
		$pdf->Cell(10,8,'RP.','TB','','R',true);
		$pdf->Cell(28,8,$this->fungsi->pecah($total_global_income - $total_global_cost + (floatval($ttl)*floatval($kurs)),','),'TB',1,'R',true);
		
			$this->m_rekap_sales->save_profit(array(
			"kode_ref"=>$data[$i]["kdr"],
			"profit"=>$total_global_income - $total_global_cost + (floatval($ttl)*floatval($kurs)),
			"income"=>$total_global_income + (floatval($ttl)*floatval($kurs)),
			"cost"=>$total_global_cost,
			"profit_usd"=>array_sum($ttl_inc_usd) - $total_cost[0] + $ttl,
			"profit_rp"=>array_sum($ttl_inc_idr) - $total_cost[1],
			"income_usd"=>array_sum($ttl_inc_usd),
			"income_rp"=>array_sum($ttl_inc_idr),
			"cost_usd"=>$total_cost[0],
			"cost_rp"=>$total_cost[1],
			"global_cost"=>array_sum($crp3),
			"global_cost_usd"=>array_sum($cusd3),
			"inv_agent"=>$ttl,
			"cost_coloader"=>"",
			"region_id"=>$this->session->userdata('region_id'),
			"shipper_name"=>$data[$i]['shipper_print'],
			"sales"=>$salesName,
			"destination"=>$data[$i]['destination'],
			"volume"=>$data[$i]['bl_meas'],
			"weight"=>$data[$i]['weight'],
			"sales_id"=>$data[$i]['sales'],
	
		));
		
		

		}
		
		$pdf->Output('JOB-SHEET-'.$kdr.'.pdf','I');
	}
	
	function get_no_invoice($kode_ref,$item_service) 
	{
		$data = array($kode_ref,$item_service);
		$value=$this->afg_m_job_sheet->get_no_invoice($data);
		return $value;
	}
	
	function get_no_dn($data)
	{
		$data[1] = substr($data[1], 0, 15);
		$q = $this->dbx->get_where("x_finance_item",array(
			"kepada"=>$data[0],
			"kode_ref"=>$data[1],
			"jenis"=>"DEBIT NOTE",
		));
		if($q->num_rows()>0)
		{
			$query = $this->dbx->get_where("x_finance",array("id"=>$q->row()->number));
			if($query->num_rows()>0)
			{
				return $query->row()->number;
			}
			else 
			{
				return "";
			}
		}
		else 
		{
			return "";
		}
	}
	
	function get_no_cn($data)
	{
		$data[2] = substr($data[2], 0, 15);
		$q = $this->dbx->get_where("x_finance_item",array(
			"kepada"=>$data[0],
			"item"=>$data[1],
			"kode_ref"=>$data[2],
			"jenis"=>"CREDIT NOTE",
		));
		if($q->num_rows()>0)
		{
			$query = $this->dbx->get_where("x_finance",array("id"=>$q->row()->number));
			if($query->num_rows()>0)
			{
				return $query->row()->number;
			}
			else 
			{
				return "";
			}
		}
		else 
		{
			return "";
		}
	}
}

/* END 

quantitly incomne tidak ketarik

*/