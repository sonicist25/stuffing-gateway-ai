<?php
class C_refund_voucher extends CI_Controller{
	
	var $title ='Refund Voucher';
	
	public function __construct(){
		parent::__construct();
		$this->fungsi->restrict();
		$this->load->model(array('finance/m_refund_voucher','finance/m_inv_to_shipper','finance/m_discount_voucher'));
	}
	
	function index(){
		$this->fungsi->auth(49,'Refund Voucher');
		$data['title'] = $this->title;
		$data['table_title'] ='INVOICE';
		$data['menu_navigation'] ='navigation';
		$data['main_view'] ='finance/v_refund_voucher';
		$this->load->view('afg_template', $data);
	}
	
	function print_data($id){
		$data = $this->m_discount_voucher->print_data('3');
		
		$this->load->library(array('fpdf','FPDF_MC_Table','fungsi'));
		$pdf=new FPDF_MC_Table();	
		$pdf->FPDF('P','mm','A4');	
		$pdf->AliasNbPages();
		$pdf->AddPage();
		
		$pdf->SetFont('Arial','',10);
    	$pdf->Write(0,'PT. GATEWAY CONTAINER LINE');
   	$pdf->Ln(4);
    	$pdf->SetFont('Arial','B',10);
    	$pdf->Write(0,strtoupper($this->session->userdata('region_id')));
    	$pdf->Ln(4);
    	$pdf->Cell(190,15,'REFOUND',0,1,'C','');
    	$pdf->SetFont('Arial','',10);
    	
    	$date = $data[0]->date;
    	if(!empty($date)) {
    		$tgl = substr($date,6,2);
    		$bln = substr($date,4,2);
    		$thn = substr($date,0,4);
    		$date = $tgl."-".$bln."-".$thn;
    	}
    	$pdf->Cell(30,5,'Job Ref.N0',0,0,'L','');
    	$pdf->Cell(90,5,':  '.$data[0]->kode_job,0,0,'L','');
    	$pdf->Cell(30,5,'Date',0,0,'L','');
    	$pdf->Cell(40,5,':  '.$date,0,1,'L','');	
    	
    	$pdf->Cell(30,5,'Payable To',0,0,'L','');
    	$pdf->Cell(90,5,':  '.$data[0]->payable,0,0,'L','');
    	$pdf->Cell(30,5,'B/L No',0,0,'L','');
    	$pdf->Cell(40,5,':  '.$data[0]->bl_no,0,1,'L','');
    	
    	$pdf->Cell(30,5,'Our Inv.No.',0,0,'L','');
    	$pdf->Cell(90,5,':  '. str_replace(',',' ',$data[0]->no_invoice),0,0,'L','');
    	$pdf->Cell(30,5,'Destination',0,0,'L','');
    	$pdf->Cell(40,5,':  '.$data[0]->destination,0,1,'L','');
    	
    	$pdf->SetFont('Arial','B',10);   
		$pdf->SetFillColor(230,230,230);
		$pdf->SetTextColor(100,100,100);
    	$pdf->Ln(4);
    	
    	$pdf->Cell(10,8,'NO.','TBL',0,'C',true);
    	$pdf->Cell(100,8,'DESCRIPTION','TBL',0,'C',true);
    	$pdf->Cell(40,8,'AMOUNT RP.','TBL',0,'C',true);
    	$pdf->Cell(40,8,'AMOUNT USD $','TBLR',1,'C',true);
    	
    	$pdf->SetFont('Arial','',8);
		$pdf->SetWidths(array(10,100,40,40));
		$pdf->SetAligns(array('C','L','C','C'));
		$i=1;	
		$n=0;
		$pdf->Ln(1);
    	foreach($data[1] as $row ) {
    		if($row['currency']=='USD'){
				$price_usd = $row['amount'];
				$price_rp = "";    		
    		} else {
    			$price_usd = "";
				$price_rp = $row['amount'];
    		}
			$ttl_usd[] = $price_usd;
			$ttl_rp[] = $price_rp;
			
    		if(!empty($ttl_usd) || !empty($ttl_rp)) {
				$ttlnya_usd = array_sum($ttl_usd);
				$ttlnya_rp = $this->fungsi->pecah(array_sum($ttl_rp),' ');
    		} else {
    			$ttlnya_usd = '';
				$ttlnya_rp = '';
    		}
    	
    		if(!empty($price_rp)) {
    			$price_rp = $this->fungsi->pecah($price_rp,' ');
    		}
    		
    		$html = htmlspecialchars("\n\n");
    		$item = str_replace('<br>', $html, $row['item']);
			$pdf->Row(array(
				$i,
				$item."".$html,
				$price_usd,
				$price_rp,
			));	
			$i++;
			$n++;
		}	
		$pdf->SetFont('Arial','BI',8);   
		$pdf->SetFillColor(230,230,230);
		$pdf->SetTextColor(100,100,100);
		$pdf->Ln(1);
		$pdf->Cell(110,8,'TOTAL.','TBL',0,'C',true);
    	$pdf->Cell(40,8,$ttlnya_usd,'TBL',0,'C',true);
    	$pdf->Cell(40,8,$ttlnya_rp,'TBLR',1,'C',true);
		
		$pdf->Cell(63,20,'Marketing / C.Service',0,0,'C',false);
    	$pdf->Cell(63,20,'Accounting',0,0,'C',false);
    	$pdf->Cell(63,20,'Approved',0,1,'C',false);
    	$pdf->Cell(63,20,'( _____________________ )',0,0,'C',false);
		$pdf->Cell(63,20,'( _____________________ )',0,0,'C',false);
		$pdf->Cell(63,20,'( _____________________ )',0,0,'C',false);
		
		$pdf->Output('DISCOUNT VOUCHER '.$data[0]->kode_job,'I');
	}

	function get_table_dua(){
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
	 	$sord = isset($_POST['sord'])?$_POST['sord']:'';
	 		 	
		$start = $batas*$hal - $batas;
		$start = ($start<0)?0:$start;
		$_POST['start']=$start;	
		
		$q = $this->m_refund_voucher->get_table_dua($_POST,$limit=false);
		$query = $this->m_refund_voucher->get_table_dua($_POST,$limit=true);
		
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
	//	$data->last_query = $this->dbx->last_query();
		$i=0;
		foreach($query as $row) {
		   $data->rows[$i]['id']=$row->id;
			$data->rows[$i]['cell']=array(
												$row->kode_job,
												$row->group,
												$this->fungsi->jin_date_str($row->date),
												$row->bl_no,
												$row->payable,
												$row->no_invoice,
												$row->destination,
												);
		    $i++;
		}
		echo json_encode($data);
	}

	function get_content() {
		$data = $this->m_refund_voucher->get_content($_POST);
		echo json_encode($data);
	}

	function manage_data(){
		$status = $this->m_refund_voucher->manage_data($_POST);
		echo json_encode(array('status'=>$status,'oper'=>$_POST['oper'])); 
	}
	
	function get_data(){
		$data = $this->m_refund_voucher->get_data($_POST);
		$data->last_id = $this->m_refund_voucher->get_last_id();
		$data->first_id = $this->m_refund_voucher->get_first_id();
		$data->date = $this->fungsi->jin_date_str($data->date);
		echo json_encode($data); 
	}
	
	function get_kode_job() {
		$kode_job = $this->m_refund_voucher->get_kode_job()->result();
		
		/*
		if($_POST['oper']=='edit') {
			$option_kode[$code]=$code;
		}
		*/
		
		if($_POST['oper']=='add'){
			$option_kode[]='';
			$option_inv[]='';
		}
		
		foreach($kode_job as $row){
         $option_kode[$row->kode_ref]= $row->kode_ref;
      }
//      foreach($invoice as $row){
//         $option_inv[]= '';
//      }
		$dropdown_kode = form_dropdown('job_ref_no',$option_kode,'','id="job_ref_no" style="width: 300px;" onchange="tarik_data_refund()"');
//		$dropdown_inv = form_dropdown('job_ref_no',$option_inv,'','id="job_ref_no" style="width: 300px;"');
		echo json_encode(array(
			'dropdown_kode'=>$dropdown_kode,
//			'dropdown_inv'=>$dropdown_inv,
		));
	}
	
	function tarik_data_refund(){
		$data = $this->m_refund_voucher->tarik_data_refund($_POST)->row();
		$data->destionation = $this->get_destination($data->kode_ref);
		$data->country_id = $this->fungsi->get_country_id($data->destionation);
		$data->bl_no = $this->get_bl_no($data->kode_ref);
		$data->no_invoice = $this->get_no_invoice($data->kode_ref);
		echo json_encode($data);
	}
	
	function tarik_table_satu(){
		$data = $this->m_refund_voucher->tarik_table_satu($_POST);
		echo json_encode($data);
	}
	
	function get_destination($kode_ref){
		$data = $this->m_inv_to_shipper->get_destination($kode_ref);
		return $data;
	}
	
	function get_no_invoice($kode_ref){
		$data = $this->m_refund_voucher->get_no_invoice($kode_ref);
		return $data;
	}
	
	function get_bl_no($kode_ref){
		$data = $this->m_refund_voucher->get_bl_no($kode_ref);
		return $data;
	}
	
	function get_table_satu() {
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
	 	$sord = isset($_POST['sord'])?$_POST['sord']:'';
	 		 	
		$start = $batas*$hal - $batas;
		$start = ($start<0)?0:$start;
		$_POST['start']=$start;	
		
		$q = $this->m_refund_voucher->get_table_satu($_POST,$limit=false);
		$query = $this->m_refund_voucher->get_table_satu($_POST,$limit=true);
		
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
												$row->item,
												$row->currency,
												$row->amount,
												);
		    $i++;
		}
		echo json_encode($data);
		
	}
	
	function save_table_satu(){
		$data = $this->m_refund_voucher->save_table_satu($_POST);
		echo json_encode($data);
	}
	
	
	
	
	
}










