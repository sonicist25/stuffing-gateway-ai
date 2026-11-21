<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Printer extends CI_Controller {	
	
	public function __construct() {
		parent::__construct();
		$this->load->library(array('FPDF_MC_Table'));
		$this->load->model('model');
		$this->fungsi->get_define();
	}
	
	function print_job_sheet() {
   	//header('HTTP/1.0 200 OK');
		//header('HTTP/1.1 200 OK');
		//header('Content-Type: application/pdf, UTF-8');
  		//header('Content-Disposition: inline; filename="'.$_GET['form'].'";');
  		//header('Last-Modified: '.gmdate('D, d M Y H:i:s', time()).' GMT');
  		
  		$pdf=new FPDF_MC_Table();
  		
		$job=$_GET['data']['job'];
		$tgl=$_GET['data']['tgl'];
		$kurs=$_GET['data']['kurs'];
		
		$_GET['data']['region_id']=REGID;
		$data=$this->model->get_job_sheet($_GET);
		
		if(count($data)==0) :
			$pdf->AddPage('L',array(216,279));
			$pdf->SetLineWidth(0.1);
			$pdf->SetMargins(5,15);
			$pdf->SetFont('Arial','b',15);
			$pdf->Cell(258,5,'JOB SHEET',0,1,'C',false);
			$pdf->Output('JOB-SHEET-'.$_GET['form'].'.pdf','I');
		endif;
		
		if($data[0]['last_vessel'] != '') :
			$vessel = $data[0]['last_vessel'];
			$vessel_etd = $data[0]['last_vessel_etd'];
			$vessel_eta = $data[0]['last_vessel_eta'];
			$vessel_etd_date = $data[0]['last_vessel_etd_date'];
			$vessel_eta_date = $data[0]['last_vessel_eta_date'];
		else :
			$vessel = $data[0]['feeder_vessel'];
			$vessel_etd = $data[0]['feeder_vessel_etd'];
			$vessel_eta = $data[0]['feeder_vessel_eta'];
			$vessel_etd_date = $data[0]['feeder_vessel_etd_date'];
			$vessel_eta_date = $data[0]['feeder_vessel_eta_date'];
		endif;
		
		/*
		if($job=='Export') :
			$shipper = $data[0]['shipper_name'];
			$consignee = $data[0]['shipper_import'];
		else :
			$shipper = $data[0]['consignee'];
			$consignee = $data[0]['shipper_import'];		
		endif;
		*/
		
		if($data[0]['last_snd_vessel'] !='') :
			$con_vessel = $data[0]['last_snd_vessel'];
			$con_vessel_etd = $data[0]['last_snd_vessel_etd'];
			$con_vessel_eta = $data[0]['last_snd_vessel_eta'];
			$con_vessel_etd_date = $data[0]['last_snd_vessel_etd_date'];
			$con_vessel_eta_date = $data[0]['last_snd_vessel_eta_date'];
		else : 
			$con_vessel = $data[0]['snd_vessel'];
			$con_vessel_etd = $data[0]['snd_vessel_etd'];
			$con_vessel_eta = $data[0]['snd_vessel_eta'];
			$con_vessel_etd_date = $data[0]['snd_vessel_etd_date'];
			$con_vessel_eta_date = $data[0]['snd_vessel_eta_date'];
		endif;
		
		if(!empty($data[0]['kurs'])) : $uang = $this->fungsi->pecah($data[0]['kurs'],','); else : $uang = ""; endif;
		
		$pdf->AddPage('L',array(216,279));
		$pdf->SetLineWidth(0.1);
		$pdf->SetMargins(5,15);
		$pdf->SetFont('Arial','b',15);
		$pdf->Cell(258,5,'JOB SHEET',0,1,'C',false);
		
		$pdf->SetFont('Arial','',8);
		$pdf->Cell(250,3,date('d/m/Y'),0,1,'R',false);
		$p=array(array(30,10,90,30,10,95),5);
		$pdf->Cell($p[0][0],$p[1],'JOB NO',0,0,'L',false);
		$pdf->Cell($p[0][1],$p[1],':',0,0,'C',false);
		$pdf->Cell($p[0][2],$p[1],$data[0]['kode_ref'],0,0,'L',false);
		$pdf->Cell($p[0][3],$p[1],'VESSEL',0,0,'L',false);
		$pdf->Cell($p[0][4],$p[1],':',0,0,'C',false);
		$pdf->Cell($p[0][5],$p[1],$vessel,0,1,'L',false);
		
		$pdf->Cell($p[0][0],$p[1],'DATE',0,0,'L',false);
		$pdf->Cell($p[0][1],$p[1],':',0,0,'C',false);
		$pdf->Cell($p[0][2],$p[1],$tgl,0,0,'L',false);
		$pdf->Cell($p[0][3],$p[1],'ETD '.strtoupper($vessel_etd).' / '. 'ETA '.strtoupper($vessel_eta),0,0,'L',false);
		$pdf->Cell($p[0][4],$p[1],':',0,0,'C',false);
		$pdf->Cell($p[0][5],$p[1],$this->fungsi->jin_date_str_strip($vessel_etd_date).' / '. $this->fungsi->jin_date_str_strip($vessel_eta_date),0,1,'L',false);
		
		$pdf->Cell($p[0][0],$p[1],'SHIPPER',0,0,'L',false);
		$pdf->Cell($p[0][1],$p[1],':',0,0,'C',false);
		$pdf->Cell($p[0][2],$p[1],strtoupper($data[0]['shipper_print']),0,0,'L',false);
		$pdf->Cell($p[0][3],$p[1],'CONN VESEL',0,0,'L',false);
		$pdf->Cell($p[0][4],$p[1],':',0,0,'C',false);
		$pdf->Cell($p[0][5],$p[1],strtoupper($con_vessel),0,1,'L',false);
		
		$pdf->Cell($p[0][0],$p[1],'CONSIGNEE',0,0,'L',false);
		$pdf->Cell($p[0][1],$p[1],':',0,0,'C',false);
		$pdf->Cell($p[0][2],$p[1],substr($data[0]['consignee_note'],0,50),0,0,'L',false);
		$pdf->Cell($p[0][3],$p[1],'ETD '. strtoupper($con_vessel_etd).' / '.'ETA '. strtoupper($con_vessel_eta),0,0,'L',false);
		$pdf->Cell($p[0][4],$p[1],':',0,0,'C',false);
		$pdf->Cell($p[0][5],$p[1],$this->fungsi->jin_date_str_strip($con_vessel_etd_date).' / '.$this->fungsi->jin_date_str_strip($con_vessel_eta_date),0,1,'L',false);
		
		$pdf->Cell($p[0][0],$p[1],'NOTIFY',0,0,'L',false);
		$pdf->Cell($p[0][1],$p[1],':',0,0,'C',false);
		$pdf->Cell($p[0][2],$p[1],strtoupper(substr($data[0]['notify_note'],0,50)),0,0,'L',false);
		$pdf->Cell($p[0][3],$p[1],'DESTINATION',0,0,'L',false);
		$pdf->Cell($p[0][4],$p[1],':',0,0,'C',false);
		$pdf->Cell($p[0][5],$p[1],$data[0]['destination'],0,1,'L',false);
		
		$pdf->Cell($p[0][0],$p[1],'SALES BY',0,0,'L',false);
		$pdf->Cell($p[0][1],$p[1],':',0,0,'C',false);
		$pdf->Cell($p[0][2],$p[1],strtoupper($this->fungsi->get_user_by_id($data[0]['sales'])),0,0,'L',false);
		$pdf->Cell($p[0][3],$p[1],'PORT DISCHARGE',0,0,'L',false);
		$pdf->Cell($p[0][4],$p[1],':',0,0,'C',false);
		$pdf->Cell($p[0][5],$p[1],$data[0]['destination'],0,1,'L',false);
		
		$pdf->Cell($p[0][0],$p[1],'SHIPPING CO',0,0,'L',false);
		$pdf->Cell($p[0][1],$p[1],':',0,0,'C',false);
		$pdf->Cell($p[0][2],$p[1],$data[0]['carrier'],0,0,'L',false);
		$pdf->Cell($p[0][3],$p[1],'CONTAINER',0,0,'L',false);
		$pdf->Cell($p[0][4],$p[1],':',0,0,'C',false);
		$pdf->Cell($p[0][5],$p[1],$data[0]['type_cont_qty'].'  X  '.$data[0]['type_cont_size'],0,1,'L',false);

		$pdf->Cell($p[0][0],$p[1],'WAREHOUSE',0,0,'L',false);
		$pdf->Cell($p[0][1],$p[1],':',0,0,'C',false);
		$pdf->Cell($p[0][2],$p[1],$data[0]['warehouse'],0,0,'L',false);
		$pdf->Cell($p[0][3],$p[1],'CONT.NO/SEAL NO',0,0,'L',false);
		$pdf->Cell($p[0][4],$p[1],':',0,0,'C',false);
		$pdf->Cell($p[0][5],$p[1],$data[0]['cont_seal_no'],0,1,'L',false);

		
		$pdf->Cell($p[0][0],$p[1],'TRUCKING',0,0,'L',false);
		$pdf->Cell($p[0][1],$p[1],':',0,0,'C',false);
		$pdf->Cell($p[0][2],$p[1],$data[0]['trucking_co'],0,0,'L',false);
		$pdf->Cell($p[0][3],$p[1],'BL NUMBER',0,0,'L',false);
		$pdf->Cell($p[0][4],$p[1],':',0,0,'C',false);
		$pdf->Cell($p[0][5],$p[1],$data[0]['hb_l'],0,1,'L',false);
		
		
		$pdf->Cell($p[0][0],$p[1],'DESC. OF GOOD',0,0,'L',false);
		$pdf->Cell($p[0][1],$p[1],':',0,0,'C',false);
		$pdf->Cell($p[0][2],$p[1],strtoupper($data[0]['desc_print']),0,0,'L',false);
		$pdf->Cell($p[0][3],$p[1],'KURS RP.',0,0,'L',false);
		$pdf->Cell($p[0][4],$p[1],':',0,0,'C',false);
		$pdf->Cell($p[0][5],$p[1],$uang,0,1,'L',false);
		
		$pdf->SetMargins(5,15);		
		$pdf->SetFont('Arial','B',10);
		$pdf->Ln();		
		$pdf->Cell(5,5,'INCOME :');
		$pdf->SetFont('Arial','B',8);
		$pdf->Ln();
		$pdf->SetFillColor(230,230,230);
		$pdf->SetTextColor(100,100,100);
		$pdf->Cell(65,6,'DETAIL OF CHARGE','TB','','C',true);
		$pdf->Cell(30,6,'KODE','TB','','C',true);
		$pdf->Cell(20,6,'QUANTITY','TB','','C',true);
		$pdf->Cell(20,6,'PRICE (USD)','TB','','C',true);
		$pdf->Cell(20,6,'TOTAL (USD)','TB','','C',true);
		$pdf->Cell(20,6,'PRICE (RP.)','TB','','C',true);
		$pdf->Cell(20,6,'TOTAL (RP.)','TB','','C',true);
		$pdf->Cell(30,6,'P/C','TB','','C',true);
		$pdf->Cell(23,6,'RECEIVED','TB','','C',true);
		$pdf->Cell(20,6,'INVOICE NO','TB','','C',true);
		$pdf->Ln();		
		$pdf->SetFont('Arial','',8);
		
		$ttl_inc_usd=array();
		$ttl_inc_idr=array();
		$ttl_gbl_inc=0;
		$ttl_gbl_cst=0;
		
		$income = $this->model->get_job_income($_GET['data']['id_sp']);
		if(count($income)>=1) :
			foreach ($income as $row ){
				if($row['currency']=='USD') :
					$cur=array($row['unit_price'],$row['unit_price']*$row['qty'],'','');
				else :
					$cur=array('','',$row['unit_price'],$row['unit_price']*$row['qty']);
				endif;
				if($row['prep_coll']=='PREPAID') :
					$number='';
				else :
					$number='';
				endif;
			
				$pdf->Cell(65,5,$row['item_service'],'','','L',false);
				$pdf->Cell(30,5,$_GET['data']['kode_ref'].$row['num'],'','','L',false);
				$pdf->Cell(20,5,$this->fungsi->pecah4($row['qty'],','),'','','C',false);
				$pdf->Cell(20,5,$this->fungsi->pecah($cur[0],','),'','','R',false);
				$pdf->Cell(20,5,$this->fungsi->pecah($cur[1],','),'','','R',false);
				$pdf->Cell(20,5,$this->fungsi->pecah($cur[2],','),'','','R',false);
				$pdf->Cell(20,5,$this->fungsi->pecah($cur[3],','),'','','R',false);
				$pdf->Cell(30,5,$row['prep_coll'],'','','C',false);
				$pdf->Cell(23,5,'','','','L',false);
				$pdf->Cell(20,5,$number,0,1,'C',false);
				$ttl_inc_usd[]=$cur[1];
				$ttl_inc_idr[]=$cur[3];
			}			
			
			$ttl_gbl_inc = ($kurs * array_sum($ttl_inc_usd)) + array_sum($ttl_inc_idr);
			$pdf->SetFont('Arial','B',8);
			$pdf->Cell(115,6,'TOTAL INCOME','TB','','L',true);
			$pdf->Cell(20,6,'USD','TB','','R',true);
			$pdf->Cell(20,6,$this->fungsi->pecah(array_sum($ttl_inc_usd),','),'TB','','R',true);
			$pdf->Cell(20,6,'RP.','TB','','R',true);
			$pdf->Cell(20,6,$this->fungsi->pecah(array_sum($ttl_inc_idr),','),'TB','','R',true);
			$pdf->Cell(40,6,'= RP.','TB','','R',true);
			$pdf->Cell(25,6,$this->fungsi->pecah($ttl_gbl_inc,','),'TB','','R',true);
			$pdf->Cell(8,6,' ','TB','','C',true);
		endif;
		
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('Arial','B',10);
		$pdf->Ln(10);
		$pdf->Cell(5,5,'COST :');
		$pdf->SetFont('Arial','B',8);
		$pdf->Ln();
		$pdf->SetFillColor(230,230,230);
		$pdf->SetTextColor(100,100,100);
		$pdf->Cell(65,6,'DETAIL OF CHARGE','TB','','C',true);
		$pdf->Cell(30,6,'KODE','TB','','C',true);
		$pdf->Cell(20,6,'QUANTITY','TB','','C',true);
		$pdf->Cell(20,6,'PRICE (USD)','TB','','C',true);
		$pdf->Cell(20,6,'TOTAL (USD)','TB','','C',true);
		$pdf->Cell(20,6,'PRICE (RP.)','TB','','C',true);
		$pdf->Cell(20,6,'TOTAL (RP.)','TB','','C',true);
		$pdf->Cell(30,6,'P/C','TB','','C',true);
		$pdf->Cell(23,6,'PAYABLE TO','TB','','C',true);
		$pdf->Cell(20,6,'NO C. NOTE','TB','','C',true);
		$pdf->Ln();
		
		$ttl_cst_usd1=array();
		$ttl_cst_usd2=array();
		$ttl_cst_usd3=array();
		$ttl_cst_usd4=array();
		
		$ttl_cst_idr1=array();
		$ttl_cst_idr2=array();
		$ttl_cst_idr3=array();
		$ttl_cst_idr4=array();
		$cost = $this->model->get_job_cost($_GET['data']['id_sp']);
		$pdf->SetFont('Arial','',8);
		if(count($cost[1])>=1):
			foreach ($cost[1] as $row ){
				if($row['qty'] < $row['mchgs']):
					$qty = $row['mchgs'];
				endif;
				if($row['currency']=='USD') :
					$cur=array($row['unit_price'],$row['unit_price']*$row['qty'],'','');
				else :
					$cur=array('','',$row['unit_price'],$row['unit_price']*$row['qty']);
				endif;
			
				$pdf->Cell(65,5,$row['item_service'],'','','L',false);
				$pdf->Cell(30,5,$_GET['data']['kode_ref'].$row['num'],'','','L',false);
				$pdf->Cell(20,5,$this->fungsi->pecah4($row['qty'],','),'','','C',false);
				$pdf->Cell(20,5,$this->fungsi->pecah($cur[0],','),'','','R',false);
				$pdf->Cell(20,5,$this->fungsi->pecah($cur[1],','),'','','R',false);
				$pdf->Cell(20,5,$this->fungsi->pecah($cur[2],','),'','','R',false);
				$pdf->Cell(20,5,$this->fungsi->pecah($cur[3],','),'','','R',false);
				$pdf->Cell(30,5,$row['prep_coll'],'','','C',false);
				$pdf->Cell(23,5,'','','','L',false);
				$pdf->Cell(20,5,$number,0,1,'C',false);
				$ttl_cst_usd1[]=$cur[1];
				$ttl_cst_idr1[]=$cur[3];
			}
		endif;
		if(count($cost[2])>=1):
			foreach ($cost[2] as $row ){
				if($row['qty'] < $row['mchgs']):
					$qty = $row['mchgs'];
				endif;
				if($row['currency']=='USD') :
					$cur=array($row['unit_price'],$row['unit_price']*$row['qty'],'','');
				else :
					$cur=array('','',$row['unit_price'],$row['unit_price']*$row['qty']);
				endif;
			
				$pdf->Cell(65,5,' * '.$row['item_service'],'','','L',false);
				$pdf->Cell(30,5,$_GET['data']['kode_ref'].$row['num'],'','','L',false);
				$pdf->Cell(20,5,$this->fungsi->pecah4($row['qty'],','),'','','C',false);
				$pdf->Cell(20,5,$this->fungsi->pecah($cur[0],','),'','','R',false);
				$pdf->Cell(20,5,$this->fungsi->pecah($cur[1],','),'','','R',false);
				$pdf->Cell(20,5,$this->fungsi->pecah($cur[2],','),'','','R',false);
				$pdf->Cell(20,5,$this->fungsi->pecah($cur[3],','),'','','R',false);
				$pdf->Cell(30,5,$row['prep_coll'],'','','C',false);
				$pdf->Cell(23,5,'','','','L',false);
				$pdf->Cell(20,5,$number,0,1,'C',false);
				$ttl_cst_usd2[]=$cur[1];
				$ttl_cst_idr2[]=$cur[3];
			}
		endif;
		if(count($cost[3])>=1):
			foreach ($cost[3] as $row ){
				if($row['qty'] < $row['mchgs']):
					$qty = $row['mchgs'];
				endif;
				if($row['currency']=='USD') :
					$cur=array($row['unit_price'],$row['unit_price']*$row['qty'],'','');
				else :
					$cur=array('','',$row['unit_price'],$row['unit_price']*$row['qty']);
				endif;
			
				$pdf->Cell(65,5,' * '.$row['item_service'],'','','L',false);
				$pdf->Cell(30,5,$_GET['data']['kode_ref'].$row['num'],'','','L',false);
				$pdf->Cell(20,5,$this->fungsi->pecah4($row['qty'],','),'','','C',false);
				$pdf->Cell(20,5,$this->fungsi->pecah($cur[0],','),'','','R',false);
				$pdf->Cell(20,5,$this->fungsi->pecah($cur[1],','),'','','R',false);
				$pdf->Cell(20,5,$this->fungsi->pecah($cur[2],','),'','','R',false);
				$pdf->Cell(20,5,$this->fungsi->pecah($cur[3],','),'','','R',false);
				$pdf->Cell(30,5,$row['prep_coll'],'','','C',false);
				$pdf->Cell(23,5,'','','','L',false);
				$pdf->Cell(20,5,$number,0,1,'C',false);
				$ttl_cst_usd3[]=$cur[1];
				$ttl_cst_idr3[]=$cur[3];
			}
		endif;
		if(count($cost[4])>=1):
			foreach ($cost[4] as $row ){
				if($row['qty'] < $row['mchgs']):
					$qty = $row['mchgs'];
				endif;
				if($row['currency']=='USD') :
					$cur=array($row['unit_price'],$row['unit_price']*$row['qty'],'','');
				else :
					$cur=array('','',$row['unit_price'],$row['unit_price']*$row['qty']);
				endif;
			
				$pdf->Cell(65,5,'<> '.$row['item_service'],'','','L',false);
				$pdf->Cell(30,5,$_GET['data']['kode_ref'].$row['num'],'','','L',false);
				$pdf->Cell(20,5,$this->fungsi->pecah4($row['qty'],','),'','','C',false);
				$pdf->Cell(20,5,$this->fungsi->pecah($cur[0],','),'','','R',false);
				$pdf->Cell(20,5,$this->fungsi->pecah($cur[1],','),'','','R',false);
				$pdf->Cell(20,5,$this->fungsi->pecah($cur[2],','),'','','R',false);
				$pdf->Cell(20,5,$this->fungsi->pecah($cur[3],','),'','','R',false);
				$pdf->Cell(30,5,$row['prep_coll'],'','','C',false);
				$pdf->Cell(23,5,'','','','L',false);
				$pdf->Cell(20,5,$number,0,1,'C',false);
				$ttl_cst_usd4[]=$cur[1];
				$ttl_cst_idr4[]=$cur[3];
			}
		endif;
		$tcu1=array_sum($ttl_cst_usd1);
		$tcu2=array_sum($ttl_cst_usd2);
		$tcu3=array_sum($ttl_cst_usd3);
		$tcu4=array_sum($ttl_cst_usd4);
		
		$tci1=array_sum($ttl_cst_idr1);
		$tci2=array_sum($ttl_cst_idr2);
		$tci3=array_sum($ttl_cst_idr3);
		$tci4=array_sum($ttl_cst_idr4);
		
		$ttl_cst_usd = ($tcu1+$tcu2+$tcu3+$tcu4);
		$ttl_cst_idr = ($tci1+$tci2+$tci3+$tci4);
		$ttl_gbl_cst = ($ttl_cst_usd+$ttl_cst_idr);
		
		$pdf->SetFont('Arial','B',8);
		$pdf->Cell(115,6,'TOTAL COST','TB','','L',true);
		$pdf->Cell(20,6,'USD','TB','','R',true);
		$pdf->Cell(20,6,$this->fungsi->pecah($ttl_cst_usd,','),'TB','','R',true);
		$pdf->Cell(20,6,'RP.','TB','','R',true);
		$pdf->Cell(20,6,$this->fungsi->pecah($ttl_cst_idr,','),'TB','','R',true);
		$pdf->Cell(40,6,'= RP.','TB','','R',true);
		$pdf->Cell(25,6,$this->fungsi->pecah($kurs*$ttl_gbl_cst,','),'TB','','R',true);
		$pdf->Cell(8,6,' ','TB','','C',true);
		
		
		$ttl_dn = $this->model->get_where(array('table'=>TRN_INC_DUA,'fld'=>'price','cond'=>array(
			'id_sp'=>$_GET['data']['id_sp'],'invoice_db'=>'DEBIT NOTE','currency'=>'USD'
		)))->result();
		
		if(count($ttl_dn)>=1) :
			foreach($ttl_dn as $row)
			{
				$dn[] = $row->price;
			}
			$sum_dn = array_sum($dn);
		else :
			$sum_dn = 0;
		endif;
		
		//$dn_no=$this->get_no_dn(array($agent,$kode_ref));
		$dn_no="";

		$pdf->Ln(12);		
		$pdf->Cell(115,5,'DUE TO GATEWAY FROM '. strtoupper($data[0]['agent_penagihan']),'TB','','L',true);
		$pdf->Cell(20,5,'','TB','','C',true);
		$pdf->Cell(20,5,$dn_no,'TB','','C',true);
		$pdf->Cell(20,5,"USD",'TB','','R',true);
		$pdf->Cell(20,5,$this->fungsi->pecah($sum_dn,","),'TB','','R',true);
		$pdf->Cell(40,5,"RP.",'TB','','R',true);
		$pdf->Cell(33,5,$this->fungsi->pecah($kurs*$sum_dn,","),'TB',1,'R',true);
		
		
		$pdf->Ln(5);		
		$pdf->Cell(115,5,'PROFIT SEBELUM PAJAK','TB','','L',true);
		$pdf->Cell(20,5,'USD','TB','','C',true);
		$pdf->Cell(20,5,$this->fungsi->pecah(array_sum($ttl_inc_usd)-($ttl_cst_usd+$sum_dn),","),'TB','','C',true);
		$pdf->Cell(20,5,'RP.','TB','','R',true);
		$pdf->Cell(20,5,$this->fungsi->pecah(array_sum($ttl_inc_idr)-$ttl_cst_idr,","),'TB','','R',true);
		$pdf->Cell(40,5,"= RP.",'TB','','R',true);
		$pdf->Cell(33,5,$this->fungsi->pecah(($ttl_gbl_inc-$ttl_gbl_cst)+($sum_dn*$kurs),","),'TB',1,'R',true);
		
		/*		
		$this->m_rekap_sales->save_profit(array(
			"kode_ref"=>$kode_ref,
			"profit"=>$total_global_income - $total_global_cost + ($ttl_dn*$data['kurs']),
			"income"=>$total_global_income,
			"cost"=>$total_global_cost,
			"profit_usd"=>array_sum($ttl_inc_usd) - $ttl_cost[0] + $ttl_dn,
			"profit_rp"=>array_sum($ttl_inc_idr) - $ttl_cost[1],
			"income_usd"=>array_sum($ttl_inc_usd),
			"income_rp"=>array_sum($ttl_inc_idr),
			"cost_usd"=>$ttl_cost[0],
			"cost_rp"=>$ttl_cost[1],
			"inv_agent"=>$ttl_dn,
			"cost_coloader"=>"",
			"region_id"=>$this->session->userdata('region_id'),
		));
		*/
		
		$pdf->Output('JOB-SHEET-'.$data[0]['kode_ref'].'.pdf','I');
	}
	
}