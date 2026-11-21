<?php
class C_official_receipt extends CI_Controller{
	
	var $title ='Official Receipt';
	
	public function __construct(){
		parent::__construct();
		$this->fungsi->restrict();
		$this->load->model(array('finance/m_official_receipt','finance/m_finance_proses'));
	}

	function get_table_satu(){
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'id';
	 	$sord = isset($_POST['sord'])?$_POST['sord']:'sord';
	 	
		$start = $batas*$hal - $batas;
		$start = ($start<0)?0:$start;
		$_POST['start']=$start;
		
		$q = $this->m_official_receipt->get_table_satu($_POST,$limit=false);
		$query = $this->m_official_receipt->get_table_satu($_POST,$limit=true);
	 
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
												$row->kode_job,
												$row->no_bukti,
												$row->receive_from,
												$row->select_receive,
												$this->fungsi->get_nama_shipper($row->select_receive),
												$row->date,
												$row->amount,
												$row->payment,
												$row->kurs,
												$this->fungsi->get_id_akhir_official_receipt(),
												);
		    $i++;
		}
		echo json_encode($data);
	}

	function get_content() {
		$data = $this->m_official_receipt->get_content($_POST);
		echo json_encode($data);
	}
	
	function manage_data(){
		$status = $this->m_official_receipt->manage_data($_POST);
		echo json_encode(array("status" => $status,'mode'=>$_POST['oper']));
	}
	
	function select_value(){
		$value = $this->input->post('value');
		if($value == 'AGENT' ) {
			$q = $this->m_finance_proses->getListAgent()->result();
			$data['option']['']=' ';
			foreach ($q as $row)
			{	
				$data['option'][$row->agent_company_code]=$row->agent_company_code;
			}	
		} else if($value == 'COLOADER') {
			$q = $this->m_finance_proses->getListShippco()->result();
			$data['option']['']=' ';
			foreach ($q as $row)
			{	
				$data['option'][$row->shipping_company_code]=$row->shipping_company_code;
			}
		} else if($value == 'SHIPPER' ) {
			$q = $this->m_finance_proses->getListShipper()->result();
			$data['option']['']=' ';
			foreach ($q as $row)
			{	
				$data['option'][$row->shipper_id]=$row->shipper_name;
				$title[]=$row->shipper_name;
			}
		}
		$dropdown = form_dropdown('select_receive_form',$data['option'],'','id="select_receive_form" style="width: 220px;"');
		echo json_encode(array("dropdown" => $dropdown ));
				
	}
	
	function index(){
		$this->fungsi->auth(44,'Official Receipt');
		$data['title'] = $this->title;
		$data['h1_title'] ='INVOICE TO SHIPPER';
		$data['table_title'] ='INVOICE';
		$data['menu_navigation'] ='navigation';
		$data['main_view'] ='finance/v_official_receipt';
		$this->load->view('afg_template', $data);
	}

	function display_table_dua(){
		
	}
	
	function print_data() {
		header('HTTP/1.0 200 OK');
		header('HTTP/1.1 200 OK');
		header('Content-Type: application/pdf, UTF-8');
  		header('Content-Disposition: inline; filename="'.$title.'";');
  		header('Last-Modified: '.gmdate('D, d M Y H:i:s', time()).' GMT');
		
		
		$this->session->set_flashdata($_POST);
		$this->load->library('FPDF_MC_Table');
		$pdf=new FPDF_MC_Table();
		$pdf->AddPage('P','A4');
		$pdf->Image(APPPATH.'third_party/img/logo.png',10,10,+40);	
		$pdf->SetFont('Arial','',12);
		$pdf->SetXY(133, 10);
		$pdf->write(5,'PT GATEWAY CONTAINER LINE');
		$pdf->SetFont('Arial','',8);
		$pdf->SetXY(137, 17);
		$pdf->write(0,'IS PLAZA BUILDING, 3RD FLOOR, ROOM: 303');
		$pdf->SetXY(158, 21);
		$pdf->write(0,'JL. PRAMUKA RAYA KAV. 151');
		$pdf->SetXY(166, 25);
		$pdf->write(0,'JAKARTA TIMUR 13120');
		$pdf->SetXY(143, 29);
		$pdf->write(0,'PHONE : ( 62-21 ) 8591-8669 ( HUNTING )');
		$pdf->SetXY(165, 33);
		$pdf->write(0,'FAX : ( 62-21 ) 8591-8664');
		$pdf->SetXY(165, 36);
		$pdf->write(0,'general@gateway-id.com');		
		$pdf->SetFont('Arial','B',11);
		$pdf->SetXY(87, 45);
		$pdf->write(0,'OFFICIAL RECEIPT');		
		$pdf->Line(10,50,200,50);	
		
		$pdf->Ln(10);
		$pdf->SetFont('Arial','',10);
		$pdf->Cell(40,5,'Received from',0,0,'',false);
		$pdf->Cell(10,5,':',0,0,'C',false);		
		$data_receipt = array('receives'=>$this->session->flashdata('receives'),'code'=>$this->session->flashdata('code'));
		$pdf->Cell(100,5,$this->m_official_receipt->get_data_OF($data_receipt));

		$pdf->Ln(5);
		$pdf->Cell(40,5,'Amount Rp. / US$',0,0,'',false);
		$pdf->Cell(10,5,':',0,0,'C',false);
		$pdf->MultiCell(100,5,$this->session->flashdata('amount'));
		
		$pdf->Ln(5);
		$pdf->Cell(40,5,'In payment of',0,0,'',false);
		$pdf->Cell(10,5,':',0,0,'C',false);
		$pdf->MultiCell(100,5,$this->session->flashdata('payment'));
		
		$pdf->Ln(35);
		$pdf->Cell(140,5,'NOTE :',0,0,'',false);
		$tanggal = $this->fungsi->date_to_tanggal5($this->session->flashdata('date'));
		$pdf->Cell(40,5,$this->session->userdata('region_id').', '.$tanggal,0,0,'',false);
		$pdf->Ln(5);
		$pdf->MultiCell(100,4,'THIS OFFICIAL RECEIPT IS VALID ONLY  AFTER CHEQUE(S) HAD BEEN CLEARED');
		
		$pdf->Ln(10);
		$pdf->MultiCell(100,4,'Exchange rates USD. 1.00   =   RP.  '.$this->session->flashdata('kurs').',00');
		
		$pdf->Output('Invoce Proses','I'); 
	}


}
