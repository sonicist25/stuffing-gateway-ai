<?php
class Afg_c_job_sheet extends CI_Controller
{
	var $title = 'Jobsheet';
	public function __construct()
	{
		parent::__construct();
		$this->fungsi->restrict();
		$this->load->model(array(
			'afg_m_cities',
			'afg_m_job_sheet',
			'afg_m_cost',
			'user_model',
			'm_bill_of_ladding',
			'afg_m_stuffing_plan',
			'afg_m_income',
			'reference/type_service_model',
			'reference/agent_company_model',
			'afg_m_stuffing_plan',
			'report/m_rekap_sales',
		));
		$this->load->library('Fungsi');

        // Inject koneksi database aktif ke library Fungsi
        $this->fungsi->dbx = $this->afg_m_stuffing_plan->dbx;
        $this->dbx = $this->afg_m_stuffing_plan->dbx;
	}
	
	function index()
	{
		exit();
	}
	
	function check_lock() {
		$id = $this->input->post('id'); // Get the specific row ID
		$kode_ref = $this->input->post('kode_ref'); // Keep as fallback

		$this->dbx->select('lock_job');
		$this->dbx->from('trnstuffingplandetail');
		
		if (!empty($id)) {
			$this->dbx->where('sp_detail_id', $id);
		} else {
			$this->dbx->where('kode_ref', $kode_ref);
		}

		$q = $this->dbx->get();
		$lock = ($q->num_rows() > 0) ? $q->row()->lock_job : '0';
		echo json_encode(array('lock_job' => $lock));
	}
	
	function get_no_invoice($kode_ref,$item_service) 
	{
		$data = array($kode_ref,$item_service);
		$value=$this->afg_m_job_sheet->get_no_invoice($data);
		return $value;
	}

	function open_form_jobsheet() {
		
		$data['id']= $this->input->post('id');
		$data['kode_ref']= $this->input->post('kode_ref');
		$data['query']=$this->afg_m_job_sheet->get_job_form($data['kode_ref']);
		$row = $this->afg_m_income->check_data_income($data['kode_ref'],$data['id']);
		$data['created']=$row['created_by'].' '.$row['created_at'];
		$this->load->view('trucking/form_job_sheet',$data);		
	}
	
	function print_jobsheet($tanggal,$kode_ref,$spd,$destination_jobsheet) 
	{
		$giusd[] = "";
		$girp[] = "";
		$this->load->model('afg_m_stuffing_plan');
		$this->load->library('FPDF_MC_Table');
		
		$pdf=new FPDF_MC_Table();
		$pdf->AddPage('P','A4');
		
		$pdf->SetLineWidth(0.1);
		
		$region = $this->session->userdata('region_id');
		
		$data =  $this->afg_m_job_sheet->get_sp_spd_by($kode_ref,$spd);
		$destination = $data['destination']??'';
		$final_des = $destination_jobsheet;
		$dest = $this->afg_m_cities->get_city_id($destination);
		$agent = $this->afg_m_stuffing_plan->get_agent($spd);
		
		$bl_no = $data['hb_l'];
		
		if(substr($kode_ref,4,3)=="300" || substr($kode_ref,4,3)=="301" ||substr($kode_ref,4,3)=="302" ||substr($kode_ref,4,3)=="400" ||substr($kode_ref,4,3)=="600" || substr($kode_ref,4,3)=="800") 
		{
			$destination = $data['port_loading'];
			$bl_no = $this->afg_m_job_sheet->get_hb_l($kode_ref);
		}
		
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
		$pdf->write(0,$kode_ref);
		
		$pdf->SetXY(5, 29);
		$pdf->write(0,'DATE');
		$pdf->SetXY(30, 29);
		$pdf->write(0,':');
		$pdf->SetXY(35, 29);
		
		$tanggalnya = substr($tanggal,6,2).'/'.substr($tanggal,4,2).'/'.substr($tanggal,0,4);
		$pdf->write(0,$tanggalnya);
		
		
		if(substr($kode_ref,4,3)=="300" || substr($kode_ref,4,3)=="301" ||substr($kode_ref,4,3)=="302" ||substr($kode_ref,4,3)=="400" ||substr($kode_ref,4,3)=="600")  {
			$shipper = substr($data['consignee'],0,25);
		}
		else 
		{
			$shipper = $data['shipper_print'];
		}
		$imp= substr($kode_ref,4,3);		
		$pdf->SetXY(5, 33);
		if($imp=='300' || $imp=='301' ||$imp=='302' ||$imp=='400' ||$imp=='402'||$imp=='600') {
		$pdf->write(0,'CUSTOMER');
		}else{
		$pdf->write(0,'SHIPPER');
		}
		$pdf->SetXY(30, 33);
		$pdf->write(0,':');
		$pdf->SetXY(35, 33);
		$pdf->write(0,strtoupper($data['shipper_print']));
		
		$pdf->SetXY(5, 37);
		$pdf->write(0,'CONSIGNEE');
		$pdf->SetXY(30, 37);
		$pdf->write(0,':');
		$pdf->SetXY(35, 37);
	
		if($imp=='300' || $imp=='301' ||$imp=='302' ||$imp=='400' ||$imp=='402'||$imp=='600') {
			$consignee = $data['shipper_import'];
		}
		else 
		{
			 ($data['consignee'] !== null) ? $consignee = strtoupper($data['consignee']):$consignee = '';
		}
		
		$pdf->Write(0,substr($consignee,0,30));
		
		$pdf->SetXY(5, 41);
		$pdf->write(0,'NOTIFY');
		$pdf->SetXY(30, 41);
		$pdf->write(0,':');
		$pdf->SetXY(35, 41);
		$notify = $data['notify_add'] !== null ? $notify = strtoupper($data['notify_add']):$notify = '';
		$pdf->Write(0,substr($notify, 0,12));
		
		$pdf->SetXY(120, 45);;
		$pdf->write(0,'SALES BY');
		$pdf->SetXY(150, 45);
		$pdf->write(0,':');
		$pdf->SetXY(155, 45);
		
		$pdf->write(0,strtoupper($this->fungsi->get_user_by_id($data['sales'])));

			$pdf->SetXY(5, 49);
			$pdf->write(0,'CARRIER');
			$pdf->SetXY(30, 49);
			$pdf->write(0,':');
			$pdf->SetXY(35, 49);
			$pdf->Write(0,substr($data['carrier'],0,45));
		
			$detail = $this->afg_m_job_sheet->get_detail_job($spd);
		
			$pdf->SetXY(5, 53);
			$pdf->write(0,'WAREHOUSE');
			$pdf->SetXY(30, 53);
			$pdf->write(0,':');
			$pdf->SetXY(35, 53);
			$pdf->write(0, $data['warehouse']);
		
			$pdf->SetXY(5, 57);
			$pdf->write(0,'TRUCKING');
			$pdf->SetXY(30, 57);
			$pdf->write(0,':');
			$pdf->SetXY(35, 57);
			$pdf->write(0,$this->afg_m_job_sheet->get_trucking($detail->trucking_co));
		
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
		
			if(!empty($detail->last_vessel)) 
			{
				$vessel = $detail->last_vessel;
				$vessel_etd = $detail->last_vessel_etd;
				$vessel_eta = $detail->last_vessel_eta;
				$vessel_etd_date = $detail->last_vessel_etd_date;
				$vessel_eta_date = $detail->last_vessel_eta_date;
			}
			else if (!empty($detail->feeder_vessel))
			{
				$vessel = $detail->feeder_vessel;
				$vessel_etd = $detail->feeder_vessel_etd;
				$vessel_eta = $detail->feeder_vessel_eta;
				$vessel_etd_date = $detail->feeder_vessel_etd_date;
				$vessel_eta_date = $detail->feeder_vessel_eta_date;
			}
			else
			{
			$vessel = $data['vessel'].' '.$data['voyage'];
			$vessel_etd = $detail->feeder_vessel_etd;
			$vessel_etd_date =	$data['etd_jkt'];
			$vessel_eta_date = $data['eta'];
			}
		
		
		$pdf->write(0,$vessel);		
		$pdf->SetXY(120, 29);
		$pdf->write(0,'ETD / ETA');
		$pdf->SetXY(150, 29);
		$pdf->write(0,':');
		$pdf->SetXY(155, 29);
		$pdf->write(0,$this->fungsi->jin_date_str_strip($vessel_etd_date).' / '. $this->fungsi->jin_date_str_strip($vessel_eta_date) );

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
		if ($con_vessel !== null){
			$pdf->write(0, strtoupper($con_vessel));
		}				
		$pdf->SetXY(120, 37);
		$pdf->write(0,'ETD / ETA ');
		$pdf->SetXY(150, 37);
		$pdf->write(0,':');
		$pdf->SetXY(155, 37);
		$pdf->write(0,$this->fungsi->jin_date_str_strip($con_vessel_etd_date).' / '.$this->fungsi->jin_date_str_strip($con_vessel_eta_date) );
		
		$pdf->SetXY(120, 41);
		$pdf->write(0,'PORT DISCHARGE');
		$pdf->SetXY(150, 41);
		$pdf->write(0,':');
		$pdf->SetXY(155, 41);
		$pdf->write(0,substr($data['destination'],0,21));
		
		if($imp=='300' || $imp=='301' ||$imp=='302' ||$imp=='400' ||$imp=='402'||$imp=='600') {
			$pdf->SetXY(5, 45);
			$pdf->write(0,'ORIGIN');
		}
		else 
		{
			$pdf->SetXY(5, 45);
			$pdf->write(0,'DESTINATION');
		}
		$pdf->SetXY(30, 45);
		$pdf->write(0,':');
		$pdf->SetXY(35, 45);
		$pdf->write(0,$destination);
		
		$dbl = $this->afg_m_stuffing_plan->get_double($data['sp_id']);
        if($dbl->num_rows() > 0 ) {
				$pdf->SetXY(120, 49);
				$pdf->write(0,'CONTAINER');
				$pdf->SetXY(150, 49);
				$pdf->write(0,':');
				$pdf->SetXY(155, 49);
						foreach($dbl->result() as $row ) {
							$pdf->write(0, $row->count_num.' '.'X'.' '.$row->count_type.', ');
						}
		}else{
		$pdf->SetXY(120, 49);
		$pdf->write(0,'CONTAINER');
		$pdf->SetXY(150, 49);
		$pdf->write(0,':');
		$pdf->SetXY(155, 49);
		$pdf->write(0,$data['type_cont_qty'].'  X  '.$data['type_cont_size']);
		}
		$pdf->SetXY(120, 53);
		$pdf->write(0,'CONT.NO/SEAL NO');
		$pdf->SetXY(150, 53);
		$pdf->write(0,':');
		$pdf->SetXY(155, 53);
		$pdf->write(0,$data['cont_seal_no']);
		
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
		$kurs = floatval($data['kurs']);
		if(!empty($kurs)) {
			$uang = $this->fungsi->pecah($kurs,',');
		}
		else {
			$uang = "";
		}
		$pdf->write(0,$uang);
		
		$pdf->Ln(5);
		$pdf->SetXY(35, 59);
		$pdf->MultiCell(80,4,strtoupper($data['desc_print']??''),0,1,0);
		
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
		
		$ttl_inc_usd=array();
		$ttl_inc_idr=array();
		$iusd=array();
		$irp=array();
		$income = $this->afg_m_job_sheet->get_income_job_sheet($kode_ref);
		foreach ($income as $row ){
			$code_service = $this->type_service_model->getDataCodeService($row->item_service);
			$qty = $row->qty;
			if($row->currency=='USD') {
				$p_i = array(floatval($row->unit_price), floatval($row->unit_price) * floatval($qty),'','');
				//$ttl_inc_usd[]=$row1->unit_price * $qty;
			} else {
				$p_i = array('','',floatval($row->unit_price),floatval( $row->unit_price) * floatval($qty));
				//$ttl_inc_rp[]=$row1->unit_price * $qty;
			}
			
			$pdf->Cell(40,5,strtoupper(substr($row->item_service,0,25)),'','','L',false);
			$pdf->Cell(15,5,$code_service,'','','L',false);
			$pdf->Cell(19,5,$this->fungsi->pecah4($qty,','),'','','C',false);			
			$pdf->Cell(19,5,$this->fungsi->pecah($p_i[0],','),'','','R',false);
			$pdf->Cell(19,5,$this->fungsi->pecah($p_i[1],','),'','','R',false);		
			$pdf->Cell(19,5,$this->fungsi->pecah($p_i[2],','),'','','R',false);			
			$pdf->Cell(19,5,$this->fungsi->pecah($p_i[3],','),'','','R',false);							
			$pdf->Cell(10,5,substr($row->prep_coll,0,1),'','','C',false);
			$pdf->Cell(19,5,'','','','C',false);
			$pdf->Cell(19,5,$row->ship_co??"",0,1,'C',false);
			$iusd[] = $p_i[1];
			$irp[] = $p_i[3];
		}
		$gblincome = $this->afg_m_job_sheet->get_income_global($data['code']);	
		foreach ($gblincome as $row ) 
		{
			$code_service = $this->type_service_model->getDataCodeService($row->item_service);
			$invoice = '';
			$qty = $this->afg_m_stuffing_plan->get_qty_gbl_cost($kode_ref,'USD');
			$qty_1 =  $this->fungsi->pecah4($qty[0],',');
			$qty_total =  $this->fungsi->pecah4($qty[1],',');
			$htn = floatval($qty_1) / $qty_total;
			$htg = $htn;
			$ttl = $row->price * $htg;
			if($row->currency=='USD') {
				$pc3= array($row->price, $ttl,'','');
				$cusd3[]=$row->price * $htg;
			} else {
				$pc3 = array('','',$row->price, $ttl);
				$crp3[]=$row->price * $htg;
			}

			$pdf->Cell(40,5,strtoupper(substr("<> ". $row->item_service,0,25)),'','','L',false);
			$pdf->Cell(15,5,$code_service,'','','L',false);
			$pdf->Cell(19,5,$qty_1.' / '.$this->fungsi->pecah($qty_total),'','','C',false);
			$pdf->Cell(19,5,$this->fungsi->pecah($pc3[0],','),'','','R',false);
			$pdf->Cell(19,5,$this->fungsi->pecah($pc3[1],','),'','','R',false);		
			$pdf->Cell(19,5,$this->fungsi->pecah($pc3[2],','),'','','R',false);			
			$pdf->Cell(19,5,$this->fungsi->pecah($pc3[3],','),'','','R',false);							
			$pdf->Cell(10,5,substr($row->prep_coll,0,1),'','','C',false);
			$pdf->Cell(19,5,$row->payeble_to,'','','L',false);
			$pdf->Cell(19,5,$row->dn_no,0,1,'C',false);
			$giusd[] = $pc3[1];
			$girp[] = $pc3[3];
		}

		
		$incusd=array_sum($iusd);
		$incrp=array_sum($irp);
		$gincusd=array_sum($giusd);
		$gincrp=array_sum($girp);
		
		$ttl_i_usd = $incusd + $gincusd;
		$ttl_i_rp = $incrp + $gincrp;
		$conversi = floatval($data['kurs'])* floatval($ttl_i_usd);
		$total_global_income = $conversi + $ttl_i_rp;
		
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
		
		$cost0 = $this->afg_m_job_sheet->get_cost_job_sheet('inf_cost_tbl_satu',$kode_ref);
		
		foreach ($cost0 as $row ) {
			$code_service0 = $this->type_service_model->getDataCodeService($row->item_service);
			
			$qty0 = $row->qty;
			if($row->currency=='USD') {
				$pc0 = array($row->unit_price, floatval($row->price),'','');
				$cusd0[]= floatval($row->price);
			} else {
				$pc0 = array('','',floatval($row->unit_price), floatval($row->price));
				$crp0[]= floatval($row->price);
			}
			
			$pdf->Cell(40,5,substr($row->item_service,0,25),'','','L',false);
			$pdf->Cell(15,5,$code_service0,'','','L',false);
			$pdf->Cell(19,5,$this->fungsi->pecah4($row->qty,','),'','','C',false);			
			$pdf->Cell(19,5,$this->fungsi->pecah($pc0[0],','),'','','R',false);
			$pdf->Cell(19,5,$this->fungsi->pecah($pc0[1],','),'','','R',false);		
			$pdf->Cell(19,5,$this->fungsi->pecah($pc0[2],','),'','','R',false);			
			$pdf->Cell(19,5,$this->fungsi->pecah($pc0[3],','),'','','R',false);							
			$pdf->Cell(10,5,substr($row->prep_coll,0,1),'','','C',false);
			$pdf->Cell(19,5,strtoupper($row->payable_to??''),'','','L',false);
			$pdf->Cell(19,5,$this->afg_m_job_sheet->get_no_cn(array($agent,$row->item_service,$kode_ref)),0,1,'C',false);
		}
		

		
		$cost1 = $this->afg_m_job_sheet->get_cost_job_sheet('inf_cost_refund_discount',$kode_ref);	
		foreach ($cost1 as $row ) {
			$code_service1 = $this->type_service_model->getDataCodeService($row->item_service);
			
			$qty1 = $row->qty;
			if($row->currency=='USD') {
				$pc1 = array($row->unit_price, $row->unit_price * $qty1,'','');
				$cusd1[]= $row->unit_price * $qty1;
			} else {
				$pc1 = array('','',floatval($row->unit_price), floatval($row->unit_price)  * floatval($qty1));
				$crp1[]= floatval($row->unit_price) * floatval($qty1);
			}
			
			$pdf->Cell(40,5,substr("* ".$row->item_service,0,25),'','','L',false);
			$pdf->Cell(15,5,$code_service1,'','','L',false);
			$pdf->Cell(19,5,$this->fungsi->pecah4($row->qty,','),'','','C',false);			
			$pdf->Cell(19,5,$this->fungsi->pecah($pc1[0],','),'','','R',false);
			$pdf->Cell(19,5,$this->fungsi->pecah($pc1[1],','),'','','R',false);		
			$pdf->Cell(19,5,$this->fungsi->pecah($pc1[2],','),'','','R',false);			
			$pdf->Cell(19,5,$this->fungsi->pecah($pc1[3],','),'','','R',false);							
			$pdf->Cell(10,5,substr($row->prep_coll,0,1),'','','C',false);
			$pdf->Cell(19,5,$row->payable_to,'','','L',false);
			$pdf->Cell(19,5,$this->afg_m_job_sheet->get_no_cn(array($agent,$row->item_service,$kode_ref)),0,1,'C',false);
		}
		
$cost2 = $this->afg_m_job_sheet->get_cost_job_sheet('inf_cost_refund_sc',$kode_ref);	
		foreach ($cost2 as $row ) 
		{
			$code_service = $this->type_service_model->getDataCodeService($row->item_service);
			$invoice = '';
			$qty = $row->qty;
			if($row->currency=='USD') {
				$pc2 = array(floatval($row->unit_price), floatval($row->price),'','');
				$cusd2[]= floatval($row->price);
			} else {
				$pc2 = array('','',floatval($row->unit_price), floatval($row->price));
				$crp2[]= floatval($row->price);
			}
			
			$pdf->Cell(40,5,substr("* ". $row->item_service,0,25),'','','L',false);
			$pdf->Cell(15,5,$code_service,'','','L',false);
			$pdf->Cell(19,5,$this->fungsi->pecah4($row->qty,','),'','','C',false);			
			$pdf->Cell(19,5,$this->fungsi->pecah($pc2[0],','),'','','R',false);
			$pdf->Cell(19,5,$this->fungsi->pecah($pc2[1],','),'','','R',false);		
			$pdf->Cell(19,5,$this->fungsi->pecah($pc2[2],','),'','','R',false);			
			$pdf->Cell(19,5,$this->fungsi->pecah($pc2[3],','),'','','R',false);							
			$pdf->Cell(10,5,substr($row->prep_coll,0,1),'','','C',false);
			$pdf->Cell(19,5,$row->payable_to,'','','L',false);
			$pdf->Cell(19,5,$this->afg_m_job_sheet->get_no_cn(array($agent,$row->item_service,$kode_ref)),0,1,'C',false);
		}
		$crp3=array();
		$cusd3=array();
		$cost3 = $this->afg_m_job_sheet->get_cost_job_sheet('x_glbcost',$kode_ref);	
		foreach ($cost3 as $row ) 
		{
			$code_service = $this->type_service_model->getDataCodeService($row->item_service);
			$invoice = '';
			$qty = $this->afg_m_stuffing_plan->get_qty_gbl_cost($kode_ref,'USD');
			//print_r($this->dbx->last_query());
			//die();
			$qty_1 =  $this->fungsi->pecah4($qty[0],',');
			$qty_total =  $this->fungsi->pecah4($qty[1],',');
			$htn = floatval($qty_1) / floatval($qty_total);
			$ttl = $row->price * $htn;
			if($row->currency=='USD') {
				$pc3= array($row->price, $ttl,'','');
				$cusd3[]=$row->price * $htn;
			} else {
				$pc3 = array('','',$row->price, $ttl);
				$crp3[]=$row->price * $htn;
			}

			$pdf->Cell(40,5,strtoupper(substr("<> ". $row->item_service,0,25)),'','','L',false);
			$pdf->Cell(15,5,$code_service,'','','L',false);
			$pdf->Cell(19,5,$qty_1.' / '.$this->fungsi->pecah($qty_total),'','','C',false);
			$pdf->Cell(19,5,$this->fungsi->pecah($pc3[0],','),'','','R',false);
			$pdf->Cell(19,5,$this->fungsi->pecah($pc3[1],','),'','','R',false);		
			$pdf->Cell(19,5,$this->fungsi->pecah($pc3[2],','),'','','R',false);			
			$pdf->Cell(19,5,$this->fungsi->pecah($pc3[3],','),'','','R',false);							
			$pdf->Cell(10,5,substr($row->prep_coll,0,1),'','','C',false);
			$pdf->Cell(19,5,$row->payeble_to,'','','L',false);
			$pdf->Cell(19,5,$this->afg_m_job_sheet->get_no_cn(array($agent,$row->item_service,$kode_ref)),0,1,'C',false);
			//print_r($kode_ref);
		}

		
		$ttl_cost = $this->afg_m_stuffing_plan->get_total_cost($kode_ref);
		$total_global_cost = (floatval($data['kurs']) * floatval($ttl_cost[0])) + floatval($ttl_cost[1]);
      
		$pdf->SetFont('Arial','B',7);
		$pdf->Cell(40,5,'TOTAL COST','TB','','L',true);
		$pdf->Cell(15,5,'','TB','','C',true);
		$pdf->Cell(19,5,'','TB','','C',true);
		$pdf->Cell(19,5,'USD','TB','','R',true);
		$pdf->Cell(19,5,$this->fungsi->pecah($ttl_cost[0],','),'TB','','R',true);
		$pdf->Cell(19,5,'RP.','TB','','R',true);
		$pdf->Cell(19,5,$this->fungsi->pecah($ttl_cost[1],','),'TB','','R',true);
		$pdf->Cell(10,5,'=','TB','','C',true);
		$pdf->Cell(10,5,'RP.','TB','','R',true);
		$pdf->Cell(28,5,$this->fungsi->pecah($total_global_cost,','),'TB',1,'R',true);
		
		$pdf->Ln(2);		
		$ttl_dn = $this->afg_m_stuffing_plan->ttl_debit_note($spd); 
		$pdf->Cell(40,5,'DUE TO '. strtoupper($this->session->userdata('region_id')),'TB','','L',true);
		$pdf->Cell(15,5,'','TB','','C',true);
		$pdf->Cell(19,5,$this->afg_m_job_sheet->get_no_dn(array($agent,$kode_ref,'')),'TB','','C',true);
		$pdf->Cell(19,5,"USD",'TB','','R',true);
		$pdf->Cell(19,5,$this->fungsi->pecah($ttl_dn,","),'TB','','R',true);
		$pdf->Cell(19,5,"",'TB','','R',true);
		$pdf->Cell(19,5,"",'TB','','R',true);
		$pdf->Cell(10,5,"",'TB','','C',true);
		$pdf->Cell(10,5,"RP.",'TB','','R',true);
		$pdf->Cell(28,5,$this->fungsi->pecah($ttl_dn*floatval($data['kurs']),","),'TB',1,'R',true);
		
		$pdf->Ln(2);
		$pdf->SetFont('Arial','B',8);
		$pdf->Cell(40,8,'PROFIT JOB','TB','','L',true);
		$pdf->Cell(15,8,'','TB','','C',true);
		$pdf->Cell(19,8,'','TB','','C',true);
		$pdf->Cell(19,8,'USD','TB','','R',true);
		$pdf->Cell(19,8,$this->fungsi->pecah($ttl_i_usd- $ttl_cost[0] + $ttl_dn,","),'TB','','R',true);
		$pdf->Cell(19,8,'RP.','TB','','R',true);
		$pdf->Cell(19,8,$this->fungsi->pecah($ttl_i_rp - $ttl_cost[1],","),'TB','','R',true);
		$pdf->Cell(10,8,'=','TB','','C',true);
		$pdf->Cell(10,8,'RP.','TB','','R',true);
		$pdf->Cell(28,8,$this->fungsi->pecah($total_global_income - $total_global_cost + ($ttl_dn*floatval($data['kurs'])),','),'TB',1,'R',true);
		
		//echo json_encode($ttl_i_rp);
		
		
		
		$this->m_rekap_sales->save_profit([
			"kode_ref"       =>$kode_ref,
			"profit"         => $total_global_income - $total_global_cost + ($ttl_dn * $data['kurs']),
			"income"         => $total_global_income + ($ttl_dn * $data['kurs']),
			"cost"           => $total_global_cost,
			"profit_usd"     => $ttl_i_usd - $ttl_cost[0] + $ttl_dn,
			"profit_rp"      => $ttl_i_rp - $ttl_cost[1],
			"income_usd"     => $ttl_i_usd,
			"income_rp"      => $ttl_i_rp,
			"cost_usd"       => $ttl_cost[0],
			"cost_rp"        => $ttl_cost[1],
			"global_cost"    => array_sum($crp3),
			"global_cost_usd"=> array_sum($cusd3),
			"inv_agent"      => $ttl_dn,
			"cost_coloader"  => "",
			"region_id"      => $this->session->userdata('region_id'),
			"shipper_name"   => $data['shipper_print'],
			"sales"          => $this->fungsi->get_user_by_id($data['sales']),
			"destination"    => $data['destination'],
			"volume"         => $data['bl_meas'],
			"weight"         => $data['weight'],
			"sales_id"       => $data['sales'],
		]);
		
		
		
		$pdf->Output('JOB-SHEET-'.$kode_ref.'.pdf','I');
	}
	
	/*
	 * FIX LOCK_JOB
	 * Daftar user disamakan dengan form_job_sheet.php (kolom LOCK & UNLOCK).
	 */
	private $lock_users   = array(160,113,116,109,169,110,53,34,0,120,454,522,615,60,107,542);
	private $unlock_users = array(160,113,116,109,169,110,53,34,0,120,522,60,615,107,542);

	private function current_user_in(array $allowed)
	{
		$uid = $this->session->userdata('user_id');
		// Jangan pakai == longgar: session kosong (null / '') akan dianggap user 0.
		if ($uid === null || $uid === '' || !is_numeric($uid)) return false;
		return in_array((int)$uid, $allowed, true);
	}

	/*
	 * Tulis lock_job ke baris yang SAMA dengan yang dibaca check_lock()
	 * (check_lock membaca by sp_detail_id). Dulu update hanya by kode_ref:
	 * kalau kode_ref yang dikirim form beda sedikit dengan isi kolom DB,
	 * update kena 0 baris tapi tetap dijawab "berhasil".
	 */
	private function write_lock_job($spd, $kode_ref, $value)
	{
		$spd = (int)$spd;
		$this->dbx->set('lock_job', (string)$value);
		if ($spd > 0) {
			$this->dbx->where('sp_detail_id', $spd);
		} else {
			$this->dbx->where('kode_ref', $kode_ref);
		}
		$this->dbx->update('trnstuffingplandetail');

		// Baca ulang nilai akhirnya
		$this->dbx->select('lock_job');
		if ($spd > 0) {
			$this->dbx->where('sp_detail_id', $spd);
		} else {
			$this->dbx->where('kode_ref', $kode_ref);
		}
		$row = $this->dbx->get('trnstuffingplandetail')->row_array();
		return isset($row['lock_job']) ? (string)$row['lock_job'] : null;
	}

	function manage_data_form()
	{
		$id = (int)$this->input->post('id');            // nomor checkbox 1..10
		$item = $this->input->post('item');
		$kode_ref = trim((string)$this->input->post('kode_ref'));
		$spd = (int)$this->input->post('spd');          // sp_detail_id (baru)
		$region = $this->session->userdata("region_id");

		if (!in_array($item, array('cs', 'acct'), true) || $id < 1 || $id > 10 || $kode_ref === '') {
			$this->output->set_status_header(422);
			echo json_encode(array('status'=>'0', 'message'=>'Parameter tidak valid.'));
			return;
		}

		// Mode ditentukan server, bukan dipercaya dari browser:
		// 1-5 = LOCK (lock_job 1), 6-10 = UNLOCK / bypass 90 hari (lock_job 2)
		$mode = ($id > 5) ? '2' : '1';

		if (!$this->current_user_in($mode === '2' ? $this->unlock_users : $this->lock_users)) {
			$this->output->set_status_header(403);
			echo json_encode(array('status'=>'0', 'message'=>'Anda tidak punya akses untuk '.($mode === '2' ? 'unlock' : 'lock').' job.'));
			return;
		}

		// 1. Catat ke tabel riwayat form (yang memunculkan ceklis/warna)
		$this->dbx->where('kode_ref',$kode_ref);
		$this->dbx->where('region_id',$region);
		$this->dbx->update(
			'a_job_form',
			array(
				$item.'_'.$id =>'TRUE',
				'tgl_'.$item.'_'.$id =>date('d-m-Y'),
				'memo'=> $this->session->userdata('fullname').'<br> at '. date('d-m-Y').' '. date('H:i:s'),
			)
		);

		// 2. Update lock_job lalu VERIFIKASI hasilnya
		$now = $this->write_lock_job($spd, $kode_ref, $mode);

		if ($now !== $mode) {
			log_message('error', 'manage_data_form: lock_job tidak tersimpan. kode_ref='.$kode_ref.' spd='.$spd.' mode='.$mode.' now='.var_export($now, true));
			$this->output->set_status_header(409);
			echo json_encode(array('status'=>'0', 'lock_job'=>$now,
				'message'=>'Status di form tercatat, tapi lock_job di trnstuffingplandetail TIDAK berubah (data detail tidak ditemukan).'));
			return;
		}

		// 3. Riwayat: siapa yang lock/unlock, slot mana, dan buka ke berapa kali
		$reopen_count = 0;
		$jf = $this->dbx->get_where('a_job_form', array('kode_ref'=>$kode_ref, 'region_id'=>$region))->row_array();
		if ($jf) {
			for ($i = 6; $i <= 10; $i++) {
				if (($jf['cs_'.$i] ?? '') == 'TRUE')   $reopen_count++;
				if (($jf['acct_'.$i] ?? '') == 'TRUE') $reopen_count++;
			}
		}
		if (function_exists('catat_log')) {
			$slot = strtoupper($item === 'cs' ? 'C/S' : 'ACCT').' '.$id;
			$msg  = ($mode === '2')
				? 'BUKA job '.$kode_ref.' via '.$slot.' (buka ke-'.$reopen_count.')'
				: 'LOCK job '.$kode_ref.' via '.$slot;
			catat_log($msg, 'Job Sheet');
		}
		
		echo json_encode(array('status'=>'1', 'lock_job'=>$now, 'reopen_count'=>$reopen_count));
	}

	function update_data_form()
	{
		$kode_ref = $this->input->post('kode_ref');
		$kurs = $this->input->post('kurs');
		$date = $this->input->post('tanggal');
		$region = $this->session->userdata("region_id");
		$this->dbx->where('kode_ref',$kode_ref);
		$this->dbx->update(
			'a_job_form', 
			array(
				'kurs'=>$kurs,
				'tgl_cetak' => $date, 
			)
		);
		if($this->dbx->affected_rows() > 0) {
			echo json_encode(array('status'=>'1'));
		} else {
			echo json_encode(array('status'=>'0'));
		}
	}
	
	function proses_post()
	{
		$data['kode_ref'] = $_POST['kode_ref'];
		body_jasaweb_jobsheet($data);
	}

}

?>