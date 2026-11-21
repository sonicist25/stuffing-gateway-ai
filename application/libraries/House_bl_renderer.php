<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * House_bl_renderer
 *
 * Reuses the exact rendering body of Afg_c_bill_of_ladding::print_out_bl()
 * but renders into a caller-supplied FPDF_MC_Table instance.
 * It DOES NOT call Output(), so many HBLs can be appended to one PDF.
 */
class House_bl_renderer
{
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();

        $this->CI->load->model(array(
            'user_model',
            'm_bill_of_ladding',
            'm_print_out_bl',
            'afg_m_stuffing_plan',
        ));

        // print_out_bl historically uses $this->dbx directly.
        $this->CI->dbx = $this->CI->m_print_out_bl->dbx;
        $this->CI->fungsi->dbx = $this->CI->m_print_out_bl->dbx;
    }

    /**
     * @return string kode_ref child job rendered
     */
    public function render($pdf, $spd, $bl_no, $sp, $cetak = 'TRUE')
    {
        $spd    = (int)$spd;
        $sp     = (int)$sp;
        $bl_no  = (string)$bl_no;
        $cetak  = strtoupper((string)$cetak) === 'FALSE' ? 'FALSE' : 'TRUE';

        // Reset state to the same baseline as a fresh FPDF instance.
        // Important when different BL templates are appended sequentially.
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(true, 20);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetLineWidth(0.2);

	$this->CI->load->model('m_print_out_bl');
	$kode_ref = $this->CI->m_print_out_bl->get_kode_ref($spd);
	$code = $this->CI->m_print_out_bl->get_data_code($spd);
	$data = $this->CI->m_print_out_bl->get_data_trucking($spd);
	log_message(
    'error',
    'PREALERT_HBL_DEBUG'
    . ' | file=' . __FILE__
    . ' | db=' . $this->CI->m_print_out_bl->dbx->database
    . ' | spd=' . $spd
    . ' | kode_ref=' . ($data['kode_ref'] ?? '')
    . ' | hbl=' . ($data['hbl'] ?? '')
    . ' | shipper_print=' . ($data['shipper_print'] ?? '')
    . ' | note=' . str_replace(
        array("\r", "\n"),
        array('', ' | '),
        (string)($data['note'] ?? '')
    )
);
	//print_r ($data);
	//die();
	$as_agent = $this->CI->m_print_out_bl->get_as_agent($spd);
	$fcl = $this->CI->m_print_out_bl->get_fcl($spd);
	$code_sp = substr($kode_ref,0,15);
	$data_connecting = $this->CI->m_print_out_bl->get_data_sp_by($code_sp);
	$calendar = $this->CI->m_print_out_bl->get_data_calendar($spd);
	$payable = $this->CI->m_print_out_bl->get_data_payable($spd);
	if($calendar != '')
	{
		$tanggal = $this->CI->fungsi->date_to_tanggal3($calendar);
	}
	else if($data_connecting['atd'] !='') 
	{
		$tanggal = $this->CI->fungsi->date_to_tanggal3($data_connecting['atd']);
	}
	else {
		$tanggal = '';
	}
	
	$last_container = $this->CI->m_print_out_bl->get_last_container($spd);
	if($last_container!='') 
	{
		$last_container = $last_container;
	}
	else 
	{
		$last_container = $data['cont_seal_no'];
	}
	if($code==1 && $cetak=='FALSE') {
		$pdf->AddPage('P','polos');
	}
	else if($code==1 && $cetak=='TRUE')
	{
		$pdf->AddPage('P','A4');
	}
	else if($code==2 && $cetak=='FALSE')
	{
		$pdf->AddPage('P','bl');
	}
	else if($code==6 && $cetak=='TRUE')
	{
		$pdf->AddPage('P','fukuyama');
	}
		else if($code==6 && $cetak=='FALSE')
	{
		$pdf->AddPage('P',array(230,320));
	}
	else if($code==7 && $cetak=='TRUE')
	{
		//$pdf->AddPage('P','fukuyama');
		$pdf->AddPage('P',array(210,297));
	}
	else if($code==7 && $cetak=='FALSE')
	{
		//$pdf->AddPage('P',array(230,320));
		$pdf->AddPage('P',array(210,297));
	}
	else if($code==8 && $cetak=='FALSE')
	{
		$pdf->AddPage('P','A4');
		$pdf->SetFont('Arial','',8);
	}
	else if($code==9 && $cetak=='FALSE')
	{
		$pdf->AddPage('P','polos');
	}
	else if($code==10 && $cetak=='FALSE')
	{
		$pdf->AddPage('P','polos');
	} 
	else if($code==11 && $cetak=='FALSE') {
		$pdf->AddPage('P','polos');
	}
	else if($code==11 && $cetak=='TRUE')
	{
		$pdf->AddPage('P','A4');
		$pdf->SetFont('Arial','',8);
	}
	else if($code==12 && $cetak=='TRUE')
	{
		$pdf->AddPage('P','A4');
		$pdf->SetFont('Arial','',8);
	}
	else {
		$pdf->AddPage('P','A4');
		$pdf->SetFont('Arial','',8);
	}
	

	$attc_discription = $this->CI->m_bill_of_ladding->check_attc_discription($spd);
	$attc_marks = $this->CI->m_bill_of_ladding->check_attc_mark($spd);
	$port_of = $this->CI->m_bill_of_ladding->get_port_of($spd);
	

	if($code==1)
	{
		if($cetak=='TRUE')
		{
			$pdf->Image(APPPATH.'third_party/img/gateway_new.jpg',5,5,+200);
			$pdf->SetFont('Arial','',8);
			$pdf->SetXY(15, 22);
			$pdf->MultiCell(89, 3, $data['note'],0,'',false);
			$pdf->SetXY(152, 23);
			$pdf->SetFont('Calibri','B',12);
			$pdf->write(0, str_replace('%20',' ',$bl_no));
			$pdf->SetFont('Arial','',8);
			$pdf->SetXY(15, 48);
			$pdf->MultiCell(90,3,($data['consignee_add']),0,'L');
			$pdf->SetXY(15, 74);
			$pdf->MultiCell(90,3, ($data['notify_add']),0,'L');

			$pdf->SetXY(15, 103);
			$pdf->write(0, $port_of->port_of_receive);
			$pdf->SetXY(57, 103);
			$pdf->write(0, $port_of->port_of_loading);

			$connecting = $this->CI->m_print_out_bl->get_connecting($spd   ,$data['vessel'].' '.$data['voyage']);
			$q = $this->CI->dbx->get_where('tbl_detail',array('detail_id'=>$spd));
			if($connecting) {
				$pdf->SetXY(15, 113);
				$pdf->write(0, $connecting->vessel);
				$pdf->SetXY(57, 109);
				if($q->row()->snd_vessel != '' || $q->row()->last_snd_vessel != '')
				{
				$pdf->write(0, 'Connecting Vessel');
				$pdf->SetXY(57, 113);
				$pdf->write(0, strtoupper($connecting->conn_vessel));  }
			}

			$pdf->SetXY(106, 113);
			$pdf->write(0, $port_of->port_of_discharge);
			$pdf->SetXY(152, 113);
			$pdf->write(0, $port_of->port_of_delivery);

			$agent = $this->CI->m_print_out_bl->getAgentByCode($data['agent_code'],0,'L',false);

			if($agent != 0 ) {
                                  if ($agent['agent_company_remark'] != '')
                                  {
				$pdf->SetXY(108, 75);
				$pdf->MULTICELL(85,2.5, $agent['agent_company_remark']);
				$pdf->SetXY(103, 80.5);
				$pdf->write(0, $agent['agent_company_name']);
				$pdf->SetXY(108, 78);
				$pdf->MultiCell(85,2.5, $agent['agent_company_address'],0,'L',false);
				}
				else
				{
                    $pdf->SetXY(108, 81);
				$pdf->write(0, $agent['agent_company_name']);
				$pdf->SetXY(108, 83);
				$pdf->MultiCell(85,3, $agent['agent_company_address'],0,'L',false);
                                  }
			}

			if ($attc_marks > 18 || $attc_discription > 15) {
				$pdf->SetXY(12, 157);
				$pdf->SetFont('Arial', 'B', 9);
				$pdf->MultiCell(150, 5, 'MARKING AND DESCRIPTION ARE ATTACHED IN THE NEXT PAGE', 0, 'L');
			} else {
				$pdf->SetFont('Arial', '', 8);
				$pdf->SetXY(12, 130);
				$pdf->MultiCell(39,3,($data['marking_nos']),0,'L',false);
				if($fcl=='FCL') {
					$pdf->SetXY(80, 130);
					$pdf->write(0, 'SHIPPER LOAD, COUNT & SEAL CONTAINER,');
					$pdf->SetXY(80, 133);
					$pdf->write(0, 'SAID TO CONTAIN :');
					$pdf->SetXY(80, 136);
					$text = html_entity_decode($data['desc_goods'], ENT_QUOTES, 'UTF-8');

					// Buang karakter non-printable dulu
					$text = preg_replace('/[^\P{C}\n]+/u', '', $text);

					// Konversi dengan ignore
					$text = iconv('UTF-8', 'Windows-1252//IGNORE', $text);

					$pdf->MultiCell(60, 3, $text, 0, 'L');
				}
				else
				{
					$pdf->SetFont('Arial','',8);
					$pdf->SetXY(80, 130);
					$pdf->Write(0,'SAID TO CONTAIN :');
					$pdf->SetXY(80, 133);

					$text = html_entity_decode($data['desc_goods'], ENT_QUOTES, 'UTF-8');

					// Buang karakter non-printable dulu
					$text = preg_replace('/[^\P{C}\n]+/u', '', $text);

					// Konversi dengan ignore
					$text = iconv('UTF-8', 'Windows-1252//IGNORE', $text);

					$pdf->MultiCell(60, 3, $text, 0, 'L');

				}
			}
			////double countener /////
			$pdf->SetFont('Arial', '', 8);
			$count = $this->CI->m_print_out_bl->get_count_double($sp);
			if($count->num_rows() > 0 ) {
			$pdf->SetXY(15, 172);
			  $pdf->write(0, 'CONTAINER / SEAL NO');
			  $pdf->Ln(2.5);
				foreach($count->result() as $row ) {
					$pdf->MultiCell(60,1.5, strtoupper($row->count_no).' / '. strtoupper($row->seal_no).' / '. strtoupper($row->count_size),0,'L',false);
					$pdf->Ln();
				}
			}
	  		else if ($last_container != '')
	  		{  
	  			$pdf->SetXY(15, 182);
			   $pdf->write(0, 'CONTAINER / SEAL NO');
	  			$pdf->SetXY(15, 184);
				$pdf->MultiCell(45, 3, $last_container.' / '.$data['type_cont_size']);
	  		}
	  		
	  	   if($fcl=='FCL') {

             	$pdf->SetXY(56, 131);
             	$dbl = $this->CI->afg_m_stuffing_plan->get_double($sp);
             	if($dbl->num_rows() > 0 ) {
             		$pdf->Ln(1);
					foreach($dbl->result() as $row ) {
						$pdf->MultiCell(60,3, $row->count_num.' '.'X'.' '.$row->count_type,0,"R");
						$pdf->Ln(0.5);
					}

					$pdf->SetXY(56, 191);
					foreach($dbl->result() as $row ) {
				   $pdf->Write(0,  $row->count_num.' ( '.$this->CI->fungsi->num_to_words( $row->count_num,'',0,'').' ) '.' '.'X'.' '.$row->count_type.' ');
					}
			 } else {  
			 		$pdf->SetXY(56, 130);
					$pdf->MultiCell(16,3, $data['no_pack_qty'].' '.'X'.' '.$data['no_pack_unit']);
					$pdf->SetXY(56, 193);
					$pdf->MultiCell(120,3, $data['no_pack_qty'].' ( '.$this->CI->fungsi->num_to_words($data['no_pack_qty'],'',0,'').' ) '.' '.'X'.' '.$data['no_pack_unit'].' ONLY');
       				 }
           	}
     			else
           	{
            	$pdf->SetXY(56, 130);
				$pdf->MultiCell(20,3, $data['no_pack_qty'].' '.$data['no_pack_unit']);
				$pdf->SetXY(56, 194);
				$pdf->MultiCell(120,3, $data['no_pack_qty'].' ( '.$this->CI->fungsi->num_to_words($data['no_pack_qty'],'',0,'').' ) '.$data['no_pack_unit'].' ONLY');
			}
			
			if($data['express_release'] == "TRUE") 
			{
				$pdf->SetXY(99, 187);
				$pdf->SetFont('Arial','B',10);
				$pdf->write(0, '" EXPRESS RELEASE "');
			}
			$pdf->SetFont('Arial','',8);
		if($data['shipped_on_board'] == "TRUE")
{
			$pdf->SetXY(152, 165);
			$text  = "SHIPPED ON BOARD\n";
			$text .= "FROM ".$port_of->port_of_loading."\n";
			$text .= "ON ".strtoupper($tanggal)."\n";
			$text .= "BY ".$connecting->vessel;
			// $text .= "\nTO ".$port_of->port_of_discharge; // kalau mau ditampilkan juga
		$pdf->SetFont('Arial','',7);	
			// MultiCell(x width, y height per line, text, border, alignment)
			$pdf->MultiCell(60, 3, $text, 0, 'L');
		$pdf->SetFont('Arial','',8);
		}


			
			$pdf->SetXY(158, 135);
			$pdf->write(0, 'GW :');
			$pdf->SetXY(177, 134);
			$pdf->Cell(13,2,$this->CI->fungsi->pecah3($data['weight'],','),0,0,'R',false,'');
			$pdf->SetXY(188, 135);
			$pdf->write(0, ' KGS');

			if($data['net_weight_tidak_dicetak'] == "FALSE" ) {
				$pdf->SetXY(158, 140);
				$pdf->write(0,'NW : ');
				$pdf->SetXY(177, 139);
				//$pdf->Cell(13,2,number_format($data['netto'],3,'.'.','),0,0,'R',false,'');
				//$pdf->Cell(13,2,$data['netto'],0,0,'R',false,'');
				$pdf->Cell(13,2,$this->CI->fungsi->pecah3($data['netto'],','),0,0,'R',false,'');
				$pdf->SetXY(188, 140);
				$pdf->write(0, ' KGS');
			}

        if($data['bl_meas'] != '' ) {
			$pdf->SetXY(158, 145);
			$pdf->write(0,'MEAS : ');
			$pdf->SetXY(177, 144);
			$pdf->Cell(13,2,$this->CI->fungsi->pecah4($data['bl_meas'],','),0,0,'R',false,'');
			$pdf->SetXY(188, 145);
			$pdf->write(0, ' M');
			$pdf->SetXY(191, 144);
			$pdf->SetFont('Arial','',6);
			$pdf->write(0, ' 3');  }

			$pdf->SetFont('Arial','',8);
			$pdf->SetXY(22, 209);
			$pdf->write(0, 'FREIGHT');
			$pdf->SetXY(21, 213);
			$pdf->write(0,$payable);
			$q = $this->CI->dbx->get_where('tbl_detail',array('detail_id'=>$spd));
			if($q->row()->payable == 'AS ARRANGED')
				{
				$pdf->SetXY(21, 208);
               $pdf->write(0, 'AS ARRANGED');
				}
			$caya = $this->CI->m_print_out_bl->get_caya_caya($spd);
			$pdf->SetXY(17, 250);
			$pdf->SetFont('Arial','',7);
			$pdf->write(0, "Type of Movement :");
			$pdf->SetFont('Arial','',8);
			$pdf->SetXY(22, 254);
			$pdf->write(0, $caya);

			
			$pdf->SetXY(15, 205);
			$pdf->MultiCell(40,3, $data['freights_charges']);

			$freight_payable = $data['freight'];
			if($freight_payable == "PREPAID") {
	  			$pdf->SetXY(60, 209);
	  			$pdf->write(0,$freight_payable);
			} else {
	  			$pdf->SetXY(88, 209);
	  			$pdf->write(0,$freight_payable);
			}

			if($data['cetak_jml_doc_origi'] == "TRUE") {
	  			$pdf->SetXY(113, 209);
	  			$pdf->MultiCell(30,3,'3(THREE) ORIGINAL',0,'','');
			}

			$pdf->SetXY(140, 248);
			$pdf->Write(0,strtoupper($this->CI->session->userdata('region_id').', '.$tanggal));
			$pdf->SetFont('calibri','B',12);
			$pdf->SetXY(38, 267);
			$pdf->write(0,$data['kode_ref']);
			$pdf->Image("http://localhost/pdf/qr_generator.php?code=" . urlencode($data['kode_ref']), 75, 262, 10, 10, "png");
			$pdf->SetXY(17, 250);
			$pdf->SetFont('Arial','',7);
			$pdf->SetXY(125, 267);
			$pdf->write(0,'PT GATEWAY CONTAINER LINE');
			$pdf->SetXY(125, 269);
			$pdf->MultiCell(60,3,strtoupper($as_agent),0,'L');
		}
		else
		{
			$pdf->SetAutoPageBreak(0, 0);
			$pdf->SetMargins(10,5,0,10);
			$pdf->SetXY(0, 17);
			$pdf->SetFont('Verdana','',12);
			$pdf->MultiCell(130, 5, $data['note'],0,'',false);
			$pdf->SetXY(215, 17);
			$pdf->SetFont('Verdana','B',14);
			$pdf->write(0, str_replace('%20',' ',$bl_no));
			$pdf->SetFont('Verdana','',12);
			$pdf->SetXY(0, 57);
			$pdf->MultiCell(130,5,($data['consignee_add']),0,'L');
			$pdf->SetXY(0, 101);
			$pdf->MultiCell(120,5,$data['notify_add'],0,'L');

			$pdf->SetXY(0, 144);
			$pdf->write(0, $port_of->port_of_receive);
			$pdf->SetXY(63, 144);
			$pdf->write(0, $port_of->port_of_loading);

			$connecting = $this->CI->m_print_out_bl->get_connecting($spd,$data['vessel'].' '.$data['voyage']);
			$q = $this->CI->dbx->get_where('tbl_detail',array('detail_id'=>$spd));
			if($connecting )
                                {
               $pdf->SetFont('Verdana','',12);
				$pdf->SetXY(0, 160);
				$pdf->write(0, $connecting->vessel);
				$pdf->SetXY(62, 151);
				if($q->row()->snd_vessel != '' || $q->row()->last_snd_vessel != '')
				{
				$pdf->SetFont('Verdana','',10.5);
				$pdf->write(0, 'Connecting Vessel');
				$pdf->SetFont('Verdana','',12);
				$pdf->SetXY(62, 160);
				$pdf->write(0, $connecting->conn_vessel);
				}
			}  
			$pdf->SetFont('Verdana','',11);
			$pdf->SetXY(141, 160);
			$pdf->write(0, $port_of->port_of_discharge);
			$pdf->SetXY(217, 160);
			$pdf->write(0, $port_of->port_of_delivery);
			$agent = $this->CI->m_print_out_bl->getAgentByCode($data['agent_code']);	
            if($agent != 0 ) {
            	if ($agent['agent_company_remark'] != '')
            	{
					$pdf->SetXY(145, 98);
					$pdf->MULTICELL(140,4.5, $agent['agent_company_remark'],0,'L',false);
					$pdf->SetXY(145, 109);
					$pdf->write(0, $agent['agent_company_name'],0,'L',false);
					$pdf->SetXY(145, 111);
					$pdf->MultiCell(110,5, $agent['agent_company_address'],0,'L',false);
				}
				else
				{
                 	$pdf->SetXY(145, 103);
					$pdf->write(0, $agent['agent_company_name']);
					$pdf->SetXY(145, 105);
					$pdf->MultiCell(110,5, $agent['agent_company_address'],0,'L',false);
             	}
			}
			$pdf->SetFont('Verdana','',12);
			if ($attc_marks > 18 || $attc_discription > 15) {
				$pdf->SetXY(12, 207);
				$pdf->SetFont('Verdana', 'B', 12);
				$pdf->MultiCell(160, 5, 'MARKING AND DESCRIPTION ARE ATTACHED IN THE NEXT PAGE', 0, 'L');
			} else {
				$pdf->SetXY(0, 185);
				$pdf->SetFont('Verdana','',12);
				$pdf->MultiCell(60,5,($data['marking_nos']),0,'L',false);
				if($fcl=='FCL') {
				$pdf->SetXY(90, 185);
				$pdf->write(0, 'SHIPPER LOAD, COUNT & SEAL CONTAINER,');
				$pdf->SetXY(90, 190);
				$pdf->write(0, 'SAID TO CONTAIN :');
				$pdf->SetXY(90, 195);
				$text = html_entity_decode($data['desc_goods'], ENT_QUOTES, 'UTF-8');

					// Buang karakter non-printable dulu
					$text = preg_replace('/[^\P{C}\n]+/u', '', $text);

					// Konversi dengan ignore
					$text = iconv('UTF-8', 'Windows-1252//IGNORE', $text);

					$pdf->MultiCell(110, 5, $text, 0, 'L');
				}
				else
				{
					$pdf->SetXY(90, 185);
					$pdf->write(0,'SAID TO CONTAIN :');
					$pdf->SetXY(90, 190);
					$text = html_entity_decode($data['desc_goods'], ENT_QUOTES, 'UTF-8');

					// Buang karakter non-printable dulu
					$text = preg_replace('/[^\P{C}\n]+/u', '', $text);

					// Konversi dengan ignore
					$text = iconv('UTF-8', 'Windows-1252//IGNORE', $text);
					$pdf->MultiCell(110, 5, $text, 0, 'L');
				}
				
			}
	  			////double countener /////

			$count = $this->CI->m_print_out_bl->get_count_double($sp);
			if($count->num_rows() > 0 ) {
                     $pdf->SetXY(5, 243);
						$pdf->write(0, 'CONTAINER / SEAL NO');
	  					$pdf->SetXY(0, 245);
	  					$pdf->SetMargins(0, 0);
						$pdf->Ln(2.5);
						foreach($count->result() as $row ) {
						$pdf->MultiCell(115,2.5, strtoupper($row->count_no).' / '. strtoupper($row->seal_no).' / '. strtoupper($row->count_size),0,'L',false);
						$pdf->Ln();
				}
		}
	  		else if ($last_container != '')
	  		{        $pdf->SetXY(0, 274);
	  					$pdf->SetFont('Verdana','',11);
						$pdf->write(0, 'CONTAINER / SEAL NO');
						$pdf->SetXY(0, 278);
						$pdf->MultiCell(85, 5, $last_container.' / '.$data['type_cont_size']);
	  				
	  		}
	  		
			
	  		////double countener /////
	  		
			
			
           if($fcl=='FCL') {

							$pdf->SetXY(57, 185);
							$dbl = $this->CI->afg_m_stuffing_plan->get_double($sp);
							if($dbl->num_rows() > 0 ) {
							$pdf->Ln(1);
							foreach($dbl->result() as $row ) {
							$pdf->MultiCell(80,3, $row->count_num.' '.'X'.' '.$row->count_type,0,"R");
							$pdf->Ln(1);
						}
				   $pdf->SetXY(57, 293);
				   foreach($dbl->result() as $row ) {
													$pdf->Write(0,  $row->count_num.' ( '.$this->CI->fungsi->num_to_words( $row->count_num,'',0,'').' ) '.' '.'X'.' '.$row->count_type.' ');
													}
			////end double countener /////
				   } else{
			        $pdf->SetXY(60, 185);
		            $pdf->MultiCell(25,3, $data['no_pack_qty'].' '.'X'.' '.$data['no_pack_unit']);
		            $pdf->SetXY(60, 291);
					$pdf->MultiCell(120,3, $data['no_pack_qty'].' ( '.$this->CI->fungsi->num_to_words($data['no_pack_qty'],'',0,'').' ) '.' '.'X'.' '.$data['no_pack_unit'].' ONLY');
                           }
                        }else{
			$pdf->SetXY(58, 183);
			$pdf->MultiCell(30,5, $data['no_pack_qty'].' '.$data['no_pack_unit']);
			$pdf->SetXY(61, 290);
			$pdf->MultiCell(130,3, $data['no_pack_qty'].'( '.$this->CI->fungsi->num_to_words($data['no_pack_qty'],'',0,'').')'.''.$data['no_pack_unit'].' ONLY');

                                }
			if($data['express_release'] == "TRUE") {
				$pdf->SetFont('Verdana','B',13);
				$pdf->SetXY(125, 275);
				$pdf->write(0, '" EXPRESS RELEASE "');
			}
			$pdf->SetFont('Verdana','',11);
			if($data['shipped_on_board'] == "TRUE") {
				$pdf->SetXY(190, 249.5);
				$pdf->write(0, 'SHIPPED ON BOARD');
				$pdf->SetXY(190, 254);
				$pdf->write(0, 'FROM '.$port_of->port_of_loading);
				$pdf->SetXY(190, 259);
				$pdf->write(0, 'ON '.strtoupper($tanggal));
				$pdf->SetXY(190, 264);
				$pdf->write(0, 'BY '.$connecting->vessel);
				//$pdf->SetXY(200, 267);
				//$pdf->write(0, 'TO '. $port_of->port_of_discharge);
			}

			$pdf->SetFont('Verdana','',11);
			$pdf->SetXY(215, 185);
			$pdf->write(0, 'GW :');
			$pdf->SetXY(260, 184);
			$pdf->Cell(13,2,$this->CI->fungsi->pecah3($data['weight'],','),0,0,'R',false,'');
			$pdf->SetXY(270, 185);
			$pdf->write(0, ' KGS');

			if($data['net_weight_tidak_dicetak'] == "FALSE" ) {
				$pdf->SetXY(215, 195);
				$pdf->write(0,'NW : ');
				$pdf->SetXY(260, 194);
				$pdf->Cell(13,2,$this->CI->fungsi->pecah3($data['netto'],','),0,0,'R',false,'');
			
				$pdf->SetXY(270, 195);
				$pdf->write(0, ' KGS');
			}
                                if($data['bl_meas'] != '' ) {
			$pdf->SetXY(215, 205);
			$pdf->write(0,'MEAS : ');
			$pdf->SetXY(260, 204);
			$pdf->Cell(13,2,$this->CI->fungsi->pecah4($data['bl_meas'],','),0,0,'R',false,'');
			$pdf->SetXY(270, 205);
			$pdf->write(0, ' M');
			$pdf->SetXY(273, 203);
			$pdf->SetFont('Arial','',10);
			$pdf->write(0, ' 3');  }

			$pdf->SetFont('Verdana','',12);
			$pdf->SetXY(10, 315);
			$pdf->write(0, 'FREIGHT');
			$pdf->SetXY(5, 322);
			$pdf->write(0,$payable);
            $q = $this->CI->dbx->get_where('tbl_detail',array('detail_id'=>$spd));
			if($q->row()->payable == "AS ARRANGED")
				{
				$pdf->SetXY(5,319);
               $pdf->write(0, 'AS ARRANGED');
				}
				
			$caya = $this->CI->m_print_out_bl->get_caya_caya($spd);
			$pdf->SetXY(10, 385);
			$pdf->SetFont('Arial','',9);
			$pdf->write(0, "Type of Movement :");
			$pdf->SetFont('Arial','',12);
			$pdf->SetXY(15, 389);
			$pdf->write(0, $caya);

			$freight_payable = $data['freight'];
			if($freight_payable == "PREPAID") {
	  			$pdf->SetXY(70, 315);
	  			$pdf->SetFont('Arial','',12);
	  			$pdf->MultiCell(40,3,$freight_payable,'','',false);
			} else {
	  			$pdf->SetXY(110, 315);
	  			$pdf->SetFont('Arial','',12);
	  			$pdf->MultiCell(40,3,$freight_payable,'','',false);
			}

			if($data['cetak_jml_doc_origi'] == "TRUE") {
	  			$pdf->SetXY(158, 315);
	  			$pdf->SetFont('Arial','',12);
	  			$pdf->MultiCell(50,3,'3(THREE) ORIGINAL',0,'','');
			}

			$pdf->SetXY(189, 377);
			$pdf->SetFont('Arial','',12);
			$pdf->MultiCell(73,1,strtoupper($this->CI->session->userdata('region_id').', '.$tanggal),'','',false);
			$pdf->SetFont('Arial','B',12);
			$pdf->SetXY(33, -18);
			$pdf->write(0,$data['kode_ref']);
			$pdf->Image("http://localhost/pdf/qr_generator.php?code=" . urlencode($data['kode_ref']), 75, 402, 10, 10, "png");
			if ($as_agent ==''){
			$pdf->SetXY(160, 399);
			$pdf->SetFont('Arial','',12);
			$pdf->write(0,'PT GATEWAY CONTAINER LINE');}
			else{
			$pdf->SetXY(160, 399);
			$pdf->SetFont('Verdana','',12);
			$pdf->write(0,'PT GATEWAY CONTAINER LINE');
			$pdf->SetXY(160, 403);
			$pdf->MultiCell(100,5,strtoupper($as_agent),0,'L');}
		}
	}

	if($code==2)
	{
		if($cetak=='TRUE')
		{
			$pdf->Image(APPPATH.'third_party/img/gcline_new.jpeg',5,10,+200);
			$pdf->SetXY(10, 25);
			$pdf->MultiCell(89, 3, $data['note'],0,'',false);
			$pdf->SetXY(155, 26);
			$pdf->SetFont('Arial','B',10);
			$pdf->write(0, $bl_no);

			$pdf->SetFont('Arial','',8);
			$pdf->SetXY(10, 50);
			$pdf->MultiCell(90,3,$data['consignee_add'],0,'L');
			$pdf->SetXY(10, 77);
			$pdf->SetFont('Arial','',8);
			$pdf->MultiCell(90,3,  $data['notify_add'],0,'L');
			$pdf->SetXY(10, 105);
			$pdf->write(0, $port_of->port_of_receive);
			$pdf->SetXY(55, 105);
			$pdf->write(0, $port_of->port_of_loading);

			$connecting = $this->CI->m_print_out_bl->get_connecting($spd   ,$data['vessel'].' '.$data['voyage']);
			$q = $this->CI->dbx->get_where('tbl_detail',array('detail_id'=>$spd));
			if($connecting) {
				$pdf->SetXY(10, 114);
				$pdf->write(0, $connecting->vessel);
				$pdf->SetXY(55, 110);
				if($q->row()->snd_vessel != '' || $q->row()->last_snd_vessel != '')
				{
				$pdf->write(0, 'Connecting Vessel');
				$pdf->SetXY(52, 114);
				$pdf->write(0, strtoupper($connecting->conn_vessel));  }
			}

			$pdf->SetXY(106, 114);
			$pdf->write(0, $port_of->port_of_discharge);

			$pdf->SetXY(153, 114);
			$pdf->write(0, $port_of->port_of_delivery);

			$agent = $this->CI->m_print_out_bl->getAgentByCode($data['agent_code']);
			if($agent != 0 ) {
				$pdf->SetXY(106, 80);
				$pdf->write(0, $agent['agent_company_name']);
				$pdf->SetXY(106, 83);
				$pdf->MultiCell(90,3, $agent['agent_company_address']);
			}

				if ($attc_marks > 18 || $attc_discription > 15) {
				$pdf->SetXY(12, 157);
				$pdf->SetFont('Arial', 'B', 9);
				$pdf->MultiCell(150, 5, 'MARKING AND DESCRIPTION ARE ATTACHED IN THE NEXT PAGE', 0, 'L');
			} else {
				$pdf->SetFont('Arial', '', 8);
				$pdf->SetXY(12, 130);
				$pdf->MultiCell(39,3,($data['marking_nos']),0,'L',false);
				if($fcl=='FCL') {
					$pdf->SetXY(80, 130);
					$pdf->write(0, 'SHIPPER LOAD, COUNT & SEAL CONTAINER,');
					$pdf->SetXY(80, 133);
					$pdf->write(0, 'SAID TO CONTAIN :');
					$pdf->SetXY(80, 136);
					$pdf->MultiCell(60,3,mb_convert_encoding($data['desc_goods'], 'ISO-8859-1', 'UTF-8'),0,'L');
				}
				else
				{
					$pdf->SetXY(80, 130);
					$pdf->write(0,'SAID TO CONTAIN :');
					$pdf->SetXY(80, 133);
					$pdf->MultiCell(60,3,mb_convert_encoding($data['desc_goods'], 'ISO-8859-1', 'UTF-8'),0,'L');
				}
			}

			$pdf->SetXY(10, 185);
			$pdf->write(0, 'CONTAINER / SEAL NO');
	  		$pdf->SetXY(10, 188);
			$pdf->MultiCell(40, 4, $last_container.' / '.$data['type_cont_size']);

	   	if($fcl=='FCL')
	   	{
				$pdf->SetXY(54, 127);
				$pdf->MultiCell(16,3, $data['no_pack_qty'].' '.'X'.' '.$data['no_pack_unit']);
				$pdf->SetXY(54, 196);
				$pdf->MultiCell(120,3, $data['no_pack_qty'].' ( '.$this->CI->fungsi->num_to_words($data['no_pack_qty'],'',0,'').' ) '.' '.'X'.' '.$data['no_pack_unit'].' ONLY');
      		}
         	else
    			{
				$pdf->SetXY(54, 127);
				$pdf->MultiCell(16,3, $data['no_pack_qty'].' '.$data['no_pack_unit']);
				$pdf->SetXY(54, 196);
				$pdf->MultiCell(120,3, $data['no_pack_qty'].' ( '.$this->CI->fungsi->num_to_words($data['no_pack_qty'],'',0,'').' ) '.$data['no_pack_unit'].' ONLY');
			}
			if($data['express_release'] == "TRUE") {
				$pdf->SetXY(90, 189);
				$pdf->SetFont('Arial','B',12);
				$pdf->write(0, '" EXPRESS RELEASE "');
			}
			$pdf->SetFont('Arial','',8);
		
		if($data['shipped_on_board'] == "TRUE")
			{
				$pdf->SetXY(140, 160);
				$pdf->write(0, 'SHIPPED ON BOARD');
				$pdf->SetXY(140, 163);
				$pdf->write(0, 'FROM '.$port_of->port_of_loading);
				$pdf->SetXY(140, 166);
				$pdf->write(0, 'ON '.strtoupper($tanggal));
				$pdf->SetXY(140, 169);
				$pdf->write(0, 'BY '.$connecting->vessel);
			}
			$pdf->SetXY(153, 130);
			$pdf->write(0, 'GW :');
			$pdf->SetXY(174, 129);
			$pdf->Cell(13,2,$this->CI->fungsi->pecah3($data['weight'],','),0,0,'R',false,'');
			$pdf->SetXY(185, 130);
			$pdf->write(0, ' KGS');

			if($data['net_weight_tidak_dicetak'] == "FALSE" ) {
				$pdf->SetXY(153, 135);
				$pdf->write(0,'NW : ');
				$pdf->SetXY(174, 134);
				$pdf->Cell(13,2,$this->CI->fungsi->pecah3($data['netto'],','),0,0,'R',false,'');
				$pdf->SetXY(185, 135);
				$pdf->write(0, ' KGS');
			}

			$pdf->SetXY(153, 140);
			$pdf->write(0,'MEAS : ');
			$pdf->SetXY(174, 139);
			$pdf->Cell(13,2,$this->CI->fungsi->pecah4($data['bl_meas'],','),0,0,'R',false,'');
			$pdf->SetXY(185, 140);
			$pdf->write(0, ' M');
			$pdf->SetXY(188, 139);
			$pdf->SetFont('Arial','',6);
			$pdf->write(0, ' 3');
		
			$pdf->SetFont('Arial','',8);
			$pdf->SetXY(20, 210);
			$pdf->write(0, 'FREIGHT');

			$pdf->SetFont('Arial','',8);
			$freight_payable = $data['freight'];

			$pdf->SetXY(10, 210);
			$pdf->MultiCell(40,3, $data['freights_charges']);

			if($freight_payable == "PREPAID") {
	  			$pdf->SetXY(60, 210);
            	$pdf->SetFont('Arial','',8);
	  			$pdf->MultiCell(40,3,$freight_payable);
			} else {
	  			$pdf->SetXY(88, 210);
	  			$pdf->SetFont('Arial','',8);
	  			$pdf->MultiCell(40,3,$freight_payable);
			}

			if($data['cetak_jml_doc_origi'] == "TRUE") {
	  			$pdf->SetXY(108, 210);
	  			$pdf->MultiCell(30,3,'3(THREE) ORIGINAL',0,'','');
			}

			$pdf->SetXY(135, 250);
			$pdf->write(0,strtoupper($this->CI->session->userdata('region_id').', '.$tanggal));
			$pdf->SetFont('calibri','B',12);
			$pdf->SetXY(38, 267);
			$pdf->write(0,$data['kode_ref']);
			$pdf->Image("http://localhost/pdf/qr_generator.php?code=" . urlencode($data['kode_ref']), 75, 262, 10, 10, "png");

			$pdf->SetXY(130, 268);
			$pdf->write(0,'PT GATEWAY CONTAINER LINE');
			$pdf->SetXY(130, 272);
			$pdf->write(0,strtoupper($as_agent));
		}
		else
		{
			$pdf->SetAutoPageBreak(0, 0);
			$pdf->SetMargins(10,5,0,10);
			$pdf->SetXY(0, 15);
			$pdf->SetFont('Arial','',12);
			$pdf->MultiCell(130, 5, $data['note'],0,'',false);
			$pdf->SetXY(210, 15);
			$pdf->SetFont('Arial','B',13);
			$pdf->write(0, $bl_no);
			$pdf->SetFont('Arial','',12);
			$pdf->SetXY(0, 54);
			$pdf->MultiCell(120,5,$data['consignee_add'],0,'L');
			$pdf->SetXY(0, 94);
			$pdf->MultiCell(120,5,  $data['notify_add'],0,'L');

			$pdf->SetXY(0, 135);
			$pdf->write(0, $port_of->port_of_receive);
			$pdf->SetXY(63, 135);
			$pdf->write(0, $port_of->port_of_loading);

			$connecting = $this->CI->m_print_out_bl->get_connecting($spd,$data['vessel'].' '.$data['voyage']);
			$q = $this->CI->dbx->get_where('tbl_detail',array('detail_id'=>$spd));
			if($connecting )
                                {
			$pdf->SetFont('Verdana','',12);
				$pdf->SetXY(0, 150);
				$pdf->write(0, $connecting->vessel);
				$pdf->SetXY(62, 141);
				if($q->row()->snd_vessel != '' || $q->row()->last_snd_vessel != '')
				{
				$pdf->SetFont('Verdana','',10.5);
				$pdf->write(0, 'Connecting Vessel');
				$pdf->SetFont('Verdana','',12);
				$pdf->SetXY(62, 150);
				$pdf->write(0, $connecting->conn_vessel);
				}
			}  


			$pdf->SetXY(141, 150);
			$pdf->write(0, $port_of->port_of_discharge);
			$pdf->SetXY(212, 150);
			$pdf->write(0, $port_of->port_of_delivery);

			$agent = $this->CI->m_print_out_bl->getAgentByCode($data['agent_code']);
			if($agent != 0 ) {
				$pdf->SetXY(145, 95);
				$pdf->SetFont('Arial','',12);
				$pdf->write(0, $agent['agent_company_name']);
				$pdf->SetXY(145, 98);
				$pdf->SetFont('Arial','',12);
				$pdf->MultiCell(90,5, $agent['agent_company_address'],0,'L');
			}

			$pdf->SetFont('Verdana','',12);
			if ($attc_marks > 18 || $attc_discription > 15) {
				$pdf->SetXY(12, 207);
				$pdf->SetFont('Verdana', 'B', 12);
				$pdf->MultiCell(160, 5, 'MARKING AND DESCRIPTION ARE ATTACHED IN THE NEXT PAGE', 0, 'L');
				$pdf->SetFont('Verdana','',12);
			} else {
				$pdf->SetXY(0, 185);
				$pdf->SetFont('Verdana','',12);
				$pdf->MultiCell(60,5,($data['marking_nos']),0,'L',false);
				if($fcl=='FCL') {
				$pdf->SetXY(90, 185);
				$pdf->write(0, 'SHIPPER LOAD, COUNT & SEAL CONTAINER,');
				$pdf->SetXY(90, 190);
				$pdf->write(0, 'SAID TO CONTAIN :');
				}
				else
				{
					$pdf->SetXY(90, 185);
					$pdf->write(0,'SAID TO CONTAIN :');
				}
				$pdf->SetXY(90, 190);
				$pdf->MultiCell(110,5,mb_convert_encoding($data['desc_goods'], 'ISO-8859-1', 'UTF-8'),0,'L');
			}

			$pdf->SetXY(0, 250);
			$pdf->write(0, 'CONTAINER / SEAL NO');
	  		$pdf->SetXY(0, 254);
	  		$pdf->SetMargins(0, 0);

	  		
	  		$pdf->SetXY(0, 253);
			$pdf->MultiCell(80, 4, $last_container.' / '.$data['type_cont_size']);


                                if($fcl=='FCL') {
			                $pdf->SetXY(60, 175);
		                 	$pdf->MultiCell(25,3, $data['no_pack_qty'].' '.'X'.' '.$data['no_pack_unit']);
		                  	$pdf->SetXY(60, 275);

			           $pdf->MultiCell(120,3, $data['no_pack_qty'].' ( '.$this->CI->fungsi->num_to_words($data['no_pack_qty'],'',0,'').' ) '.' '.'X'.' '.$data['no_pack_unit'].' ONLY');
                               }
                               else
                               {
			$pdf->SetXY(60, 173);
			$pdf->MultiCell(45,3, $data['no_pack_qty'].' '.$data['no_pack_unit']);

			$pdf->SetXY(63, 275);
			
				$pdf->MultiCell(120,3, $data['no_pack_qty'].'( '.$this->CI->fungsi->num_to_words($data['no_pack_qty'],'',0,'').')'.''.$data['no_pack_unit'].' ONLY');

                                }
			if($data['express_release'] == "TRUE") {
				$pdf->SetXY(135, 265);
				$pdf->write(0, '" EXPRESS RELEASE "');
			}
			if($data['shipped_on_board'] == "TRUE") {
				$pdf->SetXY(200, 250);
				$pdf->write(0, 'SHIPPED ON BOARD');
				$pdf->SetXY(200, 254);
				$pdf->write(0, 'FROM '.$port_of->port_of_loading);
				$pdf->SetXY(200, 258);
				$pdf->write(0, 'ON '.strtoupper($tanggal));
				$pdf->SetXY(200, 263);
				$pdf->write(0, 'BY '.$connecting->vessel);
			}

			$pdf->SetXY(213, 180);
			$pdf->write(0, 'GW :');
			$pdf->SetXY(245, 179);
			$pdf->Cell(13,2,$this->CI->fungsi->pecah3($data['weight'],','),0,0,'R',false,'');
			$pdf->SetXY(255, 180);
			$pdf->write(0, ' KGS');

			if($data['net_weight_tidak_dicetak'] == "FALSE" ) {
				$pdf->SetXY(213, 190);
				$pdf->write(0,'NW : ');
				$pdf->SetXY(245, 189);
				$pdf->Cell(13,2,$this->CI->fungsi->pecah3($data['netto'],','),0,0,'R',false,'');
				$pdf->SetXY(255, 190);
				$pdf->write(0, ' KGS');
			}

			$pdf->SetXY(213, 200);
			$pdf->write(0,'MEAS : ');
			$pdf->SetXY(245, 199);
			$pdf->Cell(13,2,$this->CI->fungsi->pecah4($data['bl_meas'],','),0,0,'R',false,'');
			$pdf->SetXY(255, 200);
			$pdf->write(0, ' M');
			$pdf->SetXY(261, 198);
			$pdf->SetFont('Arial','',10);
			$pdf->write(0, ' 3');

			$pdf->SetFont('Arial','',12);
			$pdf->SetXY(10, 300);
			$pdf->write(0, 'FREIGHT');
            //$pdf->MultiCell(40,3, $data['freights_charges']);


			$freight_payable = $data['freight'];
			if($freight_payable == "PREPAID") {
	  			$pdf->SetXY(70, 300);
	  			$pdf->SetFont('Arial','',12);
	  			$pdf->MultiCell(40,3,$freight_payable,'','',false);
			} else {
	  			$pdf->SetXY(70, 300);
	  			$pdf->SetFont('Arial','',12);
	  			$pdf->MultiCell(40,3,$freight_payable,'','',false);
			}

			if($data['cetak_jml_doc_origi'] == "TRUE") {
	  			$pdf->SetXY(158, 295);
	  			$pdf->SetFont('Arial','',12);
	  			$pdf->MultiCell(50,3,'3(THREE) ORIGINAL',0,'','');
			}

			$pdf->SetXY(185, 358);
			$pdf->SetFont('Arial','',12);
			$pdf->MultiCell(70,1,strtoupper($this->CI->session->userdata('region_id').', '.$tanggal),'','',false);
			$pdf->SetFont('Arial','B',12);
			$pdf->SetXY(33, -18);
			$pdf->write(0,$data['kode_ref']);
			$pdf->Image("http://localhost/pdf/qr_generator.php?code=" . urlencode($data['kode_ref']), 75, 402, 10, 10, "png");
			$pdf->SetXY(150, 375);
			$pdf->SetFont('Arial','',10);
			$pdf->write(0,'PT GATEWAY CONTAINER LINE');
			$pdf->SetXY(150, 380);
			$pdf->write(0,strtoupper($as_agent));
		}
	}


	if($code==3)
	{
		if($cetak=='TRUE')
		{
		 	$pdf->Image(APPPATH.'third_party/img/CTL USA.jpg',0,10,210);
			$pdf->Watermark_BL('DRAFT');
			$pdf->SetXY(147, 42);
			$pdf->SetFont('calibri','B',12);
			$pdf->write(0, $bl_no);
			$pdf->SetFont('calibri','',7);
			$count = $this->CI->m_print_out_bl->get_count_double($sp);
			if($count->num_rows() > 0 ) {
             $pdf->SetXY(5, 203);
				foreach($count->result() as $row ) {
					$pdf->SetX(5);
					$pdf->MultiCell(58,1.5, strtoupper($row->count_no).' / '. strtoupper($row->seal_no).' / '. strtoupper($row->count_size),0,'L',false);
					$pdf->Ln(2.5);
				}

			} else {
				$pdf->SetXY(5, 203);
				$pdf->MultiCell(60, 3.5, $data['cont_seal_no'].' / '.$data['type_cont_size']);
			}
			$pdf->SetXY(105, 50.5);
			$pdf->write(0, $data['kode_ref']);
			$pdf->SetFont('calibri','',8);
			$pdf->SetXY(10, 38);
			$pdf->MultiCell(95, 3.5, $data['note'],0,1,false);
			$pdf->SetXY(10, 63);
			$pdf->MultiCell(120,3.5, strtoupper($data['consignee_add']));
			$pdf->SetXY(10, 83);
			$pdf->MultiCell(120,3.5, strtoupper($data['notify_add']));
			$pdf->SetFont('calibri','',9);
			$pdf->SetXY(106, 68);
			$pdf->write(0, 'PT. GATEWAY CONTAINER LINE');

			$pdf->SetXY(56, 122);
			$pdf->write(0, $port_of->port_of_loading);
			$pdf->SetXY(56, 111);
			$pdf->write(0, $port_of->port_of_receive);

			$connecting = $this->CI->m_print_out_bl->get_connecting($spd,$data['vessel'].$data['voyage']);
			if($connecting) {
				$pdf->SetXY(10, 122);
				$pdf->write(0, $connecting->vessel);
				$pdf->SetXY(10, 112);
				$pdf->write(0, $connecting->conn_vessel);
			}

			$pdf->SetXY(10, 134);
			$pdf->write(0, $port_of->port_of_discharge);

			$pdf->SetXY(56, 134);
			$pdf->write(0, $port_of->port_of_delivery);

			$agent = $this->CI->m_print_out_bl->getAgentByCode($data['agent_code']);
			if($agent != 0 ) {
				$pdf->SetXY(106, 86);
				$pdf->write(0, $agent['agent_company_name'],0,'L',false);
				$pdf->SetXY(106, 88);
				$pdf->MultiCell(90,3.5, $agent['agent_company_address'],0,'L',false);
			}
		if ($attc_marks > 18 || $attc_discription > 15) {
			$pdf->SetXY(12, 157);
			$pdf->SetFont('Arial', 'B', 7);
			$pdf->MultiCell(150, 5, 'MARKING AND DESCRIPTION ARE ATTACHED IN THE NEXT PAGE', 0, 'L');
			} else {
			$pdf->SetFont('calibri','',7);
			$pdf->SetXY(10, 147);
			$pdf->MultiCell(39,3, $data['marking_nos'],0,'L',false);
			if($fcl=='FCL') {
					$pdf->SetXY(80, 147);
					$pdf->write(0, 'SHIPPER LOAD, COUNT & SEAL CONTAINER,');
					$pdf->SetXY(80, 150);
					$pdf->write(0, 'SAID TO CONTAIN :');
					$pdf->SetXY(80, 1153);
					$pdf->MultiCell(60,3,mb_convert_encoding($data['desc_goods'], 'ISO-8859-1', 'UTF-8'),0,'L');
				}
				else
				{
					$pdf->SetXY(80, 147);
					$pdf->write(0,'SAID TO CONTAIN :');
					$pdf->SetXY(80, 150);
					$pdf->MultiCell(60,3,mb_convert_encoding($data['desc_goods'], 'ISO-8859-1', 'UTF-8'),0,'L');
				}
			}
		if($fcl=='FCL')
	   {
			$pdf->SetXY(56, 147);
			$pdf->MultiCell(18,3, $data['no_pack_qty'].' '.'X'.' '.$data['no_pack_unit']);
			$pdf->SetXY(56, 203);
			$pdf->MultiCell(120,3, $data['no_pack_qty'].' ( '.$this->CI->fungsi->num_to_words($data['no_pack_qty'],'',0,'').' ) '.' '.'X'.' '.$data['no_pack_unit'].' ONLY');
		} else {

			$pdf->SetXY(56, 147);
			$pdf->MultiCell(25,3, $data['no_pack_qty'].' '.$data['no_pack_unit']);

			$pdf->SetXY(56, 203);
			$pdf->MultiCell(130,3, $data['no_pack_qty'].' ( '.$this->CI->fungsi->num_to_words($data['no_pack_qty'],'',0,'').' ) '.$data['no_pack_unit'].' ONLY');
		}
		
			if($data['express_release'] == "TRUE") {
				// Set posisi
				$pdf->SetXY(99, 187);

				// Warna border dan teks merah
				$pdf->SetDrawColor(255, 0, 0);  // Border merah
				$pdf->SetTextColor(255, 0, 0);  // Teks merah

				// Set font
				$pdf->SetFont('Arial', 'B', 15);

				// Hitung lebar teks
				$text = 'EXPRESS RELEASE';
				$w = $pdf->GetStringWidth($text) + 10; // tambahkan padding
				$h = 10;

				// Gambar border merah
				$pdf->Cell($w, $h, $text, 1, 0, 'C'); // 1 = borde
			}
			$pdf->SetTextColor(0, 0, 0);
			$pdf->SetFont('calibri','',9);
			if($data['shipped_on_board']== "TRUE") {
				$pdf->SetXY(165, 170);
				$pdf->write(0, 'SHIPPED ON BOARD');
				$pdf->SetXY(165, 173);
				$pdf->write(0, 'FROM '.$data['port_loading']);
				$pdf->SetXY(165, 176);
				$pdf->write(0, 'ON '.strtoupper($tanggal));
				$pdf->SetXY(165, 179);
				$pdf->write(0, 'BY '.$data['vessel']);
			}
			$pdf->SetFont('calibri','',8);
			$pdf->SetXY(165, 148);
			$pdf->write(0,number_format($data['weight'], 3, '.', ',').'KGs');

			$pdf->SetXY(185, 148);
			$pdf->write(0,$this->CI->fungsi->pecah4($data['bl_meas']).'M3',0,0,'R',false,'');
			if($data['net_weight_tidak_dicetak'] == "FALSE" ) {
			$pdf->SetXY(165, 154);
			$pdf->write(0,"NET WEIGHT : ");
			$pdf->SetXY(183, 154);
			$pdf->write(0,number_format($data['netto'], 2, '.', ',')."KGs");
			}
			$pdf->SetFont('calibri','',8);
			$freight_payable = $data['freight'];

			$pdf->SetXY(65, 220);
			$pdf->MultiCell(110,3, $data['freights_charges'],0,1,false);

			if($data['cetak_jml_doc_origi']== "TRUE") {
	  			$pdf->SetXY(165, 210);
	  			$pdf->write(0,'3(THREE) ORIGINAL',0,'','');
			}

			if($freight_payable == "PREPAID") {
	  			$pdf->SetXY(95, 262);
	  			$pdf->write(0,$freight_payable);
			} else {
	  			$pdf->SetXY(105, 270);
	  			$pdf->write(0,$freight_payable);
			}

			$pdf->SetXY(10, 271);
			$pdf->write(0,strtoupper($tanggal));
		}
		else
		{
			if($data['cetak_jml_doc_origi']== "TRUE") {
			$pdf->SetXY(155, 37);
			$pdf->SetFont('calibri','B',12);
			$pdf->write(0, $bl_no);
			$pdf->SetFont('calibri','',10);
			$count = $this->CI->m_print_out_bl->get_count_double($sp);
			if($count->num_rows() > 0 ) {
			$pdf->SetXY(10, 200);
				foreach($count->result() as $row ) {
					$pdf->SetX(155);
					$pdf->MultiCell(58,1.5, strtoupper($row->count_no).' / '. strtoupper($row->seal_no).' / '. strtoupper($row->count_size),0,'L',false);
					$pdf->Ln(2.5);
				}

			} else {
				$pdf->SetXY(10, 200);
				$pdf->MultiCell(60, 3.5, $data['cont_seal_no'].' / '.$data['type_cont_size']);
			}
			$pdf->SetXY(107, 45);
			$pdf->write(0, $data['kode_ref']);
			$pdf->SetFont('calibri','',8);
			$pdf->SetXY(10, 36);
			$pdf->MultiCell(99, 3.5, $data['note'],0,1,false);
			$pdf->SetXY(10, 61);
			$pdf->MultiCell(120,3.5, strtoupper($data['consignee_add']));
			$pdf->SetXY(10, 84);
			$pdf->MultiCell(120,3.5, $data['notify_add']);
			$pdf->SetFont('calibri','',9);
			$pdf->SetXY(107, 67);
			$pdf->write(0, 'PT. GATEWAY CONTAINER LINE');

			$pdf->SetXY(62, 113);
			$pdf->write(0, $port_of->port_of_receive);
			$pdf->SetXY(62, 120);
			$pdf->write(0, $port_of->port_of_loading);
			

			$connecting = $this->CI->m_print_out_bl->get_connecting($spd,$data['vessel'].$data['voyage']);
			if($connecting) {
				$pdf->SetXY(10, 120);
				$pdf->write(0, $connecting->vessel);
				$pdf->SetXY(10, 112);
				$pdf->write(0, $connecting->conn_vessel);
			}

			$pdf->SetXY(10, 128);
			$pdf->write(0, $port_of->port_of_discharge);

			$pdf->SetXY(62, 128);
			$pdf->write(0, $port_of->port_of_delivery);

			$agent = $this->CI->m_print_out_bl->getAgentByCode($data['agent_code']);
			if($agent != 0 ) {
				$pdf->SetXY(107, 86);
				$pdf->write(0, $agent['agent_company_name'],0,'L',false);
				$pdf->SetXY(107, 88);
				$pdf->MultiCell(90,3.5, $agent['agent_company_address'],0,'L',false);
			}
		if ($attc_marks > 18 || $attc_discription > 15) {
			$pdf->SetXY(12, 157);
			$pdf->SetFont('Arial', 'B', 9);
			$pdf->MultiCell(150, 5, 'MARKING AND DESCRIPTION ARE ATTACHED IN THE NEXT PAGE', 0, 'L');
			} else {
			$pdf->SetFont('calibri','',9);
			$pdf->SetXY(10, 142);
			$pdf->MultiCell(39,3, $data['marking_nos'],0,'L',false);
				if($fcl=='FCL') {
					$pdf->SetXY(70, 142);
					$pdf->write(0, 'SHIPPER LOAD, COUNT & SEAL CONTAINER,');
					$pdf->SetXY(70, 145);
					$pdf->write(0, 'SAID TO CONTAIN :');
					$pdf->SetXY(70, 147);
					$pdf->MultiCell(60,3,mb_convert_encoding($data['desc_goods'], 'ISO-8859-1', 'UTF-8'),0,'L');
				}
				else
				{
					$pdf->SetXY(70, 142.5);
					$pdf->write(0,'SAID TO CONTAIN :');
					$pdf->SetXY(70, 144);
					$pdf->MultiCell(60,3,mb_convert_encoding($data['desc_goods'], 'ISO-8859-1', 'UTF-8'),0,'L');
				}
			}
		$pdf->SetFont('calibri','',9);
		if($fcl=='FCL')
	   {
			$pdf->SetXY(50, 142);
			$pdf->MultiCell(18,3, $data['no_pack_qty'].' '.'X'.' '.$data['no_pack_unit']);
			$pdf->SetXY(50, 205);
			$pdf->MultiCell(120,3, $data['no_pack_qty'].' ( '.$this->CI->fungsi->num_to_words($data['no_pack_qty'],'',0,'').' ) '.' '.'X'.' '.$data['no_pack_unit'].' ONLY');
		} else {

			$pdf->SetXY(50, 142);
			$pdf->MultiCell(25,3, $data['no_pack_qty'].' '.$data['no_pack_unit']);

			$pdf->SetXY(50, 205);
			$pdf->MultiCell(130,3, $data['no_pack_qty'].' ( '.$this->CI->fungsi->num_to_words($data['no_pack_qty'],'',0,'').' ) '.$data['no_pack_unit'].' ONLY');
		}
		
			if($data['express_release'] == "TRUE") {
				// Set posisi
				$pdf->SetXY(99, 197);

				// Warna border dan teks merah
				$pdf->SetDrawColor(255, 0, 0);  // Border merah
				$pdf->SetTextColor(255, 0, 0);  // Teks merah

				// Set font
				$pdf->SetFont('Arial', 'B', 15);

				// Hitung lebar teks
				$text = 'EXPRESS RELEASE';
				$w = $pdf->GetStringWidth($text) + 10; // tambahkan padding
				$h = 10;

				// Gambar border merah
				$pdf->Cell($w, $h, $text, 1, 0, 'C'); // 1 = borde
			}
			$pdf->SetTextColor(0, 0, 0);
			$pdf->SetFont('calibri','',9);
			if($data['shipped_on_board']== "TRUE") {
				$pdf->SetXY(165, 170);
				$pdf->write(0, 'CLEAN ON BOARD');
				$pdf->SetXY(165, 173);
				$pdf->write(0, 'FROM '.$data['port_loading']);
				$pdf->SetXY(165, 176);
				$pdf->write(0, 'ON '.strtoupper($tanggal));
				$pdf->SetXY(165, 179);
				$pdf->write(0, 'BY '.$data['vessel']);
			}
			$pdf->SetFont('calibri','',8);
			$pdf->SetXY(163, 147);
			$pdf->write(0,number_format($data['weight'], 3, '.', ',').'KGs');

			$pdf->SetXY(186, 147);
			$pdf->write(0,$this->CI->fungsi->pecah4($data['bl_meas']).'M3',0,0,'R',false,'');
			if($data['net_weight_tidak_dicetak'] == "FALSE" ) {
			$pdf->SetXY(163, 152);
			$pdf->write(0,"NET WEIGHT : ");
			$pdf->SetXY(183, 152);
			$pdf->write(0,number_format($data['netto'], 2, '.', ',')."KGs");
			}
			$pdf->SetFont('calibri','',8);
			$freight_payable = $data['freight'];

			$pdf->SetXY(65, 220);
			$pdf->MultiCell(110,3, $data['freights_charges'],0,1,false);

			if($data['cetak_jml_doc_origi']== "TRUE") {
	  			$pdf->SetXY(165, 213);
	  			$pdf->write(0,'3(THREE) ORIGINAL',0,'','');
			}

			if($freight_payable == "PREPAID") {
	  			$pdf->SetXY(95, 269);
	  			$pdf->write(0,$freight_payable);
			} else {
	  			$pdf->SetXY(105, 274);
	  			$pdf->write(0,$freight_payable);
			}

			$pdf->SetXY(17, 274);
			$pdf->write(0,strtoupper($tanggal));
		}
		else {
			$pdf->SetXY(155, 42);
			$pdf->SetFont('calibri','B',12);
			$pdf->write(0, $bl_no);
			$pdf->SetFont('calibri','',10);
			$count = $this->CI->m_print_out_bl->get_count_double($sp);
			if($count->num_rows() > 0 ) {
			$pdf->SetXY(10, 200);
				foreach($count->result() as $row ) {
					$pdf->SetX(155);
					$pdf->MultiCell(58,1.5, strtoupper($row->count_no).' / '. strtoupper($row->seal_no).' / '. strtoupper($row->count_size),0,'L',false);
					$pdf->Ln(2.5);
				}

			} else {
				$pdf->SetXY(10, 200);
				$pdf->MultiCell(60, 3.5, $data['cont_seal_no'].' / '.$data['type_cont_size']);
			}
			$pdf->SetXY(107, 50);
			$pdf->write(0, $data['kode_ref']);
			$pdf->SetFont('calibri','',8);
			$pdf->SetXY(10, 41);
			$pdf->MultiCell(99, 3.5, $data['note'],0,1,false);
			$pdf->SetXY(10, 65);
			$pdf->MultiCell(120,3.5, strtoupper($data['consignee_add']));
			$pdf->SetXY(10, 84);
			$pdf->MultiCell(120,3.5, $data['notify_add']);
			$pdf->SetFont('calibri','',9);
			$pdf->SetXY(107, 67);
			$pdf->write(0, 'PT. GATEWAY CONTAINER LINE');

			$pdf->SetXY(62, 111);
			$pdf->write(0, $port_of->port_of_receive);
			$pdf->SetXY(62, 122);
			$pdf->write(0, $port_of->port_of_loading);
			

			$connecting = $this->CI->m_print_out_bl->get_connecting($spd,$data['vessel'].$data['voyage']);
			if($connecting) {
				$pdf->SetXY(10, 122);
				$pdf->write(0, $connecting->vessel);
				$pdf->SetXY(10, 112);
				$pdf->write(0, $connecting->conn_vessel);
			}

			$pdf->SetXY(10, 133);
			$pdf->write(0, $port_of->port_of_discharge);

			$pdf->SetXY(62, 133);
			$pdf->write(0, $port_of->port_of_delivery);

			$agent = $this->CI->m_print_out_bl->getAgentByCode($data['agent_code']);
			if($agent != 0 ) {
				$pdf->SetXY(107, 86);
				$pdf->write(0, $agent['agent_company_name'],0,'L',false);
				$pdf->SetXY(107, 88);
				$pdf->MultiCell(90,3.5, $agent['agent_company_address'],0,'L',false);
			}
		if ($attc_marks > 18 || $attc_discription > 15) {
			$pdf->SetXY(12, 157);
			$pdf->SetFont('Arial', 'B', 9);
			$pdf->MultiCell(150, 5, 'MARKING AND DESCRIPTION ARE ATTACHED IN THE NEXT PAGE', 0, 'L');
			} else {
			$pdf->SetFont('calibri','',9);
			$pdf->SetXY(10, 147);
			$pdf->MultiCell(39,3, $data['marking_nos'],0,'L',false);
				if($fcl=='FCL') {
					$pdf->SetXY(85, 147);
					$pdf->write(0, 'SHIPPER LOAD, COUNT & SEAL CONTAINER,');
					$pdf->SetXY(85, 150);
					$pdf->write(0, 'SAID TO CONTAIN :');
					$pdf->SetXY(85, 152);
					$pdf->MultiCell(60,3,mb_convert_encoding($data['desc_goods'], 'ISO-8859-1', 'UTF-8'),0,'L');
				}
				else
				{
					$pdf->SetXY(85, 147.5);
					$pdf->write(0,'SAID TO CONTAIN :');
					$pdf->SetXY(85, 149);
					$pdf->MultiCell(60,3,mb_convert_encoding($data['desc_goods'], 'ISO-8859-1', 'UTF-8'),0,'L');
				}
			}
		$pdf->SetFont('calibri','',9);
		if($fcl=='FCL')
	   {
			$pdf->SetXY(60, 147);
			$pdf->MultiCell(18,3, $data['no_pack_qty'].' '.'X'.' '.$data['no_pack_unit']);
			$pdf->SetXY(60, 205);
			$pdf->MultiCell(120,3, $data['no_pack_qty'].' ( '.$this->CI->fungsi->num_to_words($data['no_pack_qty'],'',0,'').' ) '.' '.'X'.' '.$data['no_pack_unit'].' ONLY');
		} else {

			$pdf->SetXY(60, 147);
			$pdf->MultiCell(25,3, $data['no_pack_qty'].' '.$data['no_pack_unit']);

			$pdf->SetXY(60, 205);
			$pdf->MultiCell(130,3, $data['no_pack_qty'].' ( '.$this->CI->fungsi->num_to_words($data['no_pack_qty'],'',0,'').' ) '.$data['no_pack_unit'].' ONLY');
		}
		
			if($data['express_release'] == "TRUE") {
				// Set posisi
				$pdf->SetXY(99, 197);

				// Warna border dan teks merah
				$pdf->SetDrawColor(255, 0, 0);  // Border merah
				$pdf->SetTextColor(255, 0, 0);  // Teks merah

				// Set font
				$pdf->SetFont('Arial', 'B', 15);

				// Hitung lebar teks
				$text = 'EXPRESS RELEASE';
				$w = $pdf->GetStringWidth($text) + 10; // tambahkan padding
				$h = 10;

				// Gambar border merah
				$pdf->Cell($w, $h, $text, 1, 0, 'C'); // 1 = borde
			}
			$pdf->SetTextColor(0, 0, 0);
			$pdf->SetFont('calibri','',9);
			if($data['shipped_on_board']== "TRUE") {
				$pdf->SetXY(165, 170);
				$pdf->write(0, 'CLEAN ON BOARD');
				$pdf->SetXY(165, 173);
				$pdf->write(0, 'FROM '.$data['port_loading']);
				$pdf->SetXY(165, 176);
				$pdf->write(0, 'ON '.strtoupper($tanggal));
				$pdf->SetXY(165, 179);
				$pdf->write(0, 'BY '.$data['vessel']);
			}
			$pdf->SetFont('calibri','',8);
			$pdf->SetXY(166, 147);
			$pdf->write(0,number_format($data['weight'], 3, '.', ',').'KGs');

			$pdf->SetXY(182, 147);
			$pdf->write(0,$this->CI->fungsi->pecah4($data['bl_meas']).'M3',0,0,'R',false,'');
			if($data['net_weight_tidak_dicetak'] == "FALSE" ) {
			$pdf->SetXY(166, 152);
			$pdf->write(0,"NET WEIGHT : ");
			$pdf->SetXY(183, 152);
			$pdf->write(0,number_format($data['netto'], 2, '.', ',')."KGs");
			}
			$pdf->SetFont('calibri','',8);
			$freight_payable = $data['freight'];

			$pdf->SetXY(65, 220);
			$pdf->MultiCell(110,3, $data['freights_charges'],0,1,false);

			if($data['cetak_jml_doc_origi']== "TRUE") {
	  			$pdf->SetXY(165, 213);
	  			$pdf->write(0,'3(THREE) ORIGINAL',0,'','');
			}

			if($freight_payable == "PREPAID") {
	  			$pdf->SetXY(95, 269);
	  			$pdf->write(0,$freight_payable);
			} else {
	  			$pdf->SetXY(105, 274);
	  			$pdf->write(0,$freight_payable);
			}

			$pdf->SetXY(15, 274);
			$pdf->write(0,strtoupper($tanggal));
		}
		}
}
	
	if($code==4) 
	{
		if($cetak=='TRUE') 
		{  
			$pdf->Image(APPPATH.'third_party/img/CTL LAX.jpg',0,10,210);
			$pdf->Watermark_BL('DRAFT');
			$pdf->SetXY(147, 42);
			$pdf->SetFont('calibri','B',12);
			$pdf->write(0, $bl_no);
			$pdf->SetFont('calibri','',7);
			$count = $this->CI->m_print_out_bl->get_count_double($sp);
			if($count->num_rows() > 0 ) {
             $pdf->SetXY(10, 205);
				foreach($count->result() as $row ) {
					$pdf->SetX(144);
					$pdf->MultiCell(58,1.5, strtoupper($row->count_no).' / '. strtoupper($row->seal_no).' / '. strtoupper($row->count_size),0,'L',false);
					$pdf->Ln(2.5);
				}

			} else {
				$pdf->SetXY(10, 205);
				$pdf->MultiCell(60, 3.5, $data['cont_seal_no'].' / '.$data['type_cont_size']);
			}
			$pdf->SetXY(105, 50.5);
			$pdf->write(0, $data['kode_ref']);
			$pdf->SetFont('calibri','',8);
			$pdf->SetXY(10, 38);
			$pdf->MultiCell(100, 3.5, $data['note'],0,1,false);
			$pdf->SetXY(10, 63);
			$pdf->MultiCell(120,3.5, strtoupper($data['consignee_add']));
			$pdf->SetXY(10, 83);
			$pdf->MultiCell(120,3.5, $data['notify_add']);
			$pdf->SetFont('calibri','',9);
			$pdf->SetXY(106, 68);
			$pdf->write(0, 'PT. GATEWAY CONTAINER LINE');

			$pdf->SetXY(56, 122);
			$pdf->write(0, $port_of->port_of_loading);
			$pdf->SetXY(56, 111);
			$pdf->write(0, $port_of->port_of_receive);

			$connecting = $this->CI->m_print_out_bl->get_connecting($spd,$data['vessel'].$data['voyage']);
			if($connecting) {
				$pdf->SetXY(10, 122);
				$pdf->write(0, $connecting->vessel);
				$pdf->SetXY(10, 112);
				$pdf->write(0, $connecting->conn_vessel);
			}

			$pdf->SetXY(10, 134);
			$pdf->write(0, $port_of->port_of_discharge);

			$pdf->SetXY(56, 134);
			$pdf->write(0, $port_of->port_of_delivery);

			$agent = $this->CI->m_print_out_bl->getAgentByCode($data['agent_code']);
			if($agent != 0 ) {
				$pdf->SetXY(106, 86);
				$pdf->write(0, $agent['agent_company_name'],0,'L',false);
				$pdf->SetXY(106, 88);
				$pdf->MultiCell(90,3.5, $agent['agent_company_address'],0,'L',false);
			}
		if ($attc_marks > 18 || $attc_discription > 15) {
			$pdf->SetXY(12, 157);
			$pdf->SetFont('Arial', 'B', 9);
			$pdf->MultiCell(150, 5, 'MARKING AND DESCRIPTION ARE ATTACHED IN THE NEXT PAGE', 0, 'L');
			} else {
			$pdf->SetFont('calibri','',7);
			$pdf->SetXY(10, 147);
			$pdf->MultiCell(39,3, $data['marking_nos'],0,'L',false);
				if($fcl=='FCL') {
					$pdf->SetXY(80, 147);
					$pdf->write(0, 'SHIPPER LOAD, COUNT & SEAL CONTAINER,');
					$pdf->SetXY(80, 150);
					$pdf->write(0, 'SAID TO CONTAIN :');
					$pdf->SetXY(80, 1153);
					$pdf->MultiCell(60,3,mb_convert_encoding($data['desc_goods'], 'ISO-8859-1', 'UTF-8'),0,'L');
				}
				else
				{
					$pdf->SetXY(80, 147);
					$pdf->write(0,'SAID TO CONTAIN :');
					$pdf->SetXY(80, 150);
					$pdf->MultiCell(60,3,mb_convert_encoding($data['desc_goods'], 'ISO-8859-1', 'UTF-8'),0,'L');
				}
			}
		if($fcl=='FCL')
	   {
			$pdf->SetXY(56, 147);
			$pdf->MultiCell(18,3, $data['no_pack_qty'].' '.'X'.' '.$data['no_pack_unit']);
			$pdf->SetXY(56, 205);
			$pdf->MultiCell(120,3, $data['no_pack_qty'].' ( '.$this->CI->fungsi->num_to_words($data['no_pack_qty'],'',0,'').' ) '.' '.'X'.' '.$data['no_pack_unit'].' ONLY');
		} else {

			$pdf->SetXY(56, 147);
			$pdf->MultiCell(25,3, $data['no_pack_qty'].' '.$data['no_pack_unit']);

			$pdf->SetXY(56, 205);
			$pdf->MultiCell(130,3, $data['no_pack_qty'].' ( '.$this->CI->fungsi->num_to_words($data['no_pack_qty'],'',0,'').' ) '.$data['no_pack_unit'].' ONLY');
		}
		
			if($data['express_release'] == "TRUE") {
				// Set posisi
				$pdf->SetXY(99, 187);

				// Warna border dan teks merah
				$pdf->SetDrawColor(255, 0, 0);  // Border merah
				$pdf->SetTextColor(255, 0, 0);  // Teks merah

				// Set font
				$pdf->SetFont('Arial', 'B', 15);

				// Hitung lebar teks
				$text = 'EXPRESS RELEASE';
				$w = $pdf->GetStringWidth($text) + 10; // tambahkan padding
				$h = 10;

				// Gambar border merah
				$pdf->Cell($w, $h, $text, 1, 0, 'C'); // 1 = borde
			}
			$pdf->SetTextColor(0, 0, 0);
			$pdf->SetFont('calibri','',9);
			if($data['shipped_on_board']== "TRUE") {
				$pdf->SetXY(165, 170);
				$pdf->write(0, 'SHIPPED ON BOARD');
				$pdf->SetXY(165, 173);
				$pdf->write(0, 'FROM '.$data['port_loading']);
				$pdf->SetXY(165, 176);
				$pdf->write(0, 'ON '.strtoupper($tanggal));
				$pdf->SetXY(165, 179);
				$pdf->write(0, 'BY '.$data['vessel']);
			}
			$pdf->SetFont('calibri','',8);
			$pdf->SetXY(165, 148);
			$pdf->write(0,number_format($data['weight'], 3, '.', ',').'KGs');

			$pdf->SetXY(185, 148);
			$pdf->write(0,$this->CI->fungsi->pecah4($data['bl_meas']).'M3',0,0,'R',false,'');
			if($data['net_weight_tidak_dicetak'] == "FALSE" ) {
			$pdf->SetXY(165, 154);
			$pdf->write(0,"NET WEIGHT : ");
			$pdf->SetXY(183, 154);
			$pdf->write(0,number_format($data['netto'], 2, '.', ',')."KGs");
			}
			$pdf->SetFont('calibri','',8);
			$freight_payable = $data['freight'];

			$pdf->SetXY(65, 220);
			$pdf->MultiCell(110,3, $data['freights_charges'],0,1,false);

			if($data['cetak_jml_doc_origi']== "TRUE") {
	  			$pdf->SetXY(165, 210);
	  			$pdf->write(0,'3(THREE) ORIGINAL',0,'','');
			}

			if($freight_payable == "PREPAID") {
	  			$pdf->SetXY(95, 262);
	  			$pdf->write(0,$freight_payable);
			} else {
	  			$pdf->SetXY(105, 272);
	  			$pdf->write(0,$freight_payable);
			}

			$pdf->SetXY(10, 271);
			$pdf->write(0,strtoupper($tanggal));
		}
		else
		{
			if($data['cetak_jml_doc_origi']== "TRUE") {
			$pdf->SetXY(155, 37);
			$pdf->SetFont('calibri','B',12);
			$pdf->write(0, $bl_no);
			$pdf->SetFont('calibri','',10);
			$count = $this->CI->m_print_out_bl->get_count_double($sp);
			if($count->num_rows() > 0 ) {
			$pdf->SetXY(10, 200);
				foreach($count->result() as $row ) {
					$pdf->SetX(155);
					$pdf->MultiCell(58,1.5, strtoupper($row->count_no).' / '. strtoupper($row->seal_no).' / '. strtoupper($row->count_size),0,'L',false);
					$pdf->Ln(2.5);
				}

			} else {
				$pdf->SetXY(10, 200);
				$pdf->MultiCell(60, 3.5, $data['cont_seal_no'].' / '.$data['type_cont_size']);
			}
			$pdf->SetXY(107, 45);
			$pdf->write(0, $data['kode_ref']);
			$pdf->SetFont('calibri','',8);
			$pdf->SetXY(10, 36);
			$pdf->MultiCell(99, 3.5, $data['note'],0,1,false);
			$pdf->SetXY(10, 61);
			$pdf->MultiCell(120,3.5, strtoupper($data['consignee_add']));
			$pdf->SetXY(10, 84);
			$pdf->MultiCell(120,3.5, $data['notify_add']);
			$pdf->SetFont('calibri','',9);
			$pdf->SetXY(107, 67);
			$pdf->write(0, 'PT. GATEWAY CONTAINER LINE');

			$pdf->SetXY(62, 113);
			$pdf->write(0, $port_of->port_of_receive);
			$pdf->SetXY(62, 120);
			$pdf->write(0, $port_of->port_of_loading);
			

			$connecting = $this->CI->m_print_out_bl->get_connecting($spd,$data['vessel'].$data['voyage']);
			if($connecting) {
				$pdf->SetXY(10, 120);
				$pdf->write(0, $connecting->vessel);
				$pdf->SetXY(10, 112);
				$pdf->write(0, $connecting->conn_vessel);
			}

			$pdf->SetXY(10, 128);
			$pdf->write(0, $port_of->port_of_discharge);

			$pdf->SetXY(62, 128);
			$pdf->write(0, $port_of->port_of_delivery);

			$agent = $this->CI->m_print_out_bl->getAgentByCode($data['agent_code']);
			if($agent != 0 ) {
				$pdf->SetXY(107, 86);
				$pdf->write(0, $agent['agent_company_name'],0,'L',false);
				$pdf->SetXY(107, 88);
				$pdf->MultiCell(90,3.5, $agent['agent_company_address'],0,'L',false);
			}
		if ($attc_marks > 18 || $attc_discription > 15) {
			$pdf->SetXY(12, 157);
			$pdf->SetFont('Arial', 'B', 9);
			$pdf->MultiCell(150, 5, 'MARKING AND DESCRIPTION ARE ATTACHED IN THE NEXT PAGE', 0, 'L');
			} else {
			$pdf->SetFont('calibri','',9);
			$pdf->SetXY(10, 142);
			$pdf->MultiCell(39,3, $data['marking_nos'],0,'L',false);
				if($fcl=='FCL') {
					$pdf->SetXY(70, 142);
					$pdf->write(0, 'SHIPPER LOAD, COUNT & SEAL CONTAINER,');
					$pdf->SetXY(70, 145);
					$pdf->write(0, 'SAID TO CONTAIN :');
					$pdf->SetXY(70, 147);
					$pdf->MultiCell(60,3,mb_convert_encoding($data['desc_goods'], 'ISO-8859-1', 'UTF-8'),0,'L');
				}
				else
				{
					$pdf->SetXY(70, 142.5);
					$pdf->write(0,'SAID TO CONTAIN :');
					$pdf->SetXY(70, 144);
					$pdf->MultiCell(60,3,mb_convert_encoding($data['desc_goods'], 'ISO-8859-1', 'UTF-8'),0,'L');
				}
			}
		$pdf->SetFont('calibri','',9);
		if($fcl=='FCL')
	   {
			$pdf->SetXY(50, 142);
			$pdf->MultiCell(18,3, $data['no_pack_qty'].' '.'X'.' '.$data['no_pack_unit']);
			$pdf->SetXY(50, 205);
			$pdf->MultiCell(120,3, $data['no_pack_qty'].' ( '.$this->CI->fungsi->num_to_words($data['no_pack_qty'],'',0,'').' ) '.' '.'X'.' '.$data['no_pack_unit'].' ONLY');
		} else {

			$pdf->SetXY(50, 142);
			$pdf->MultiCell(25,3, $data['no_pack_qty'].' '.$data['no_pack_unit']);

			$pdf->SetXY(50, 205);
			$pdf->MultiCell(130,3, $data['no_pack_qty'].' ( '.$this->CI->fungsi->num_to_words($data['no_pack_qty'],'',0,'').' ) '.$data['no_pack_unit'].' ONLY');
		}
		
			if($data['express_release'] == "TRUE") {
				// Set posisi
				$pdf->SetXY(99, 197);

				// Warna border dan teks merah
				$pdf->SetDrawColor(255, 0, 0);  // Border merah
				$pdf->SetTextColor(255, 0, 0);  // Teks merah

				// Set font
				$pdf->SetFont('Arial', 'B', 15);

				// Hitung lebar teks
				$text = 'EXPRESS RELEASE';
				$w = $pdf->GetStringWidth($text) + 10; // tambahkan padding
				$h = 10;

				// Gambar border merah
				$pdf->Cell($w, $h, $text, 1, 0, 'C'); // 1 = borde
			}
			$pdf->SetTextColor(0, 0, 0);
			$pdf->SetFont('calibri','',9);
			if($data['shipped_on_board']== "TRUE") {
				$pdf->SetXY(165, 170);
				$pdf->write(0, 'CLEAN ON BOARD');
				$pdf->SetXY(165, 173);
				$pdf->write(0, 'FROM '.$data['port_loading']);
				$pdf->SetXY(165, 176);
				$pdf->write(0, 'ON '.strtoupper($tanggal));
				$pdf->SetXY(165, 179);
				$pdf->write(0, 'BY '.$data['vessel']);
			}
			$pdf->SetFont('calibri','',8);
			$pdf->SetXY(163, 147);
			$pdf->write(0,number_format($data['weight'], 3, '.', ',').'KGs');

			$pdf->SetXY(186, 147);
			$pdf->write(0,$this->CI->fungsi->pecah4($data['bl_meas']).'M3',0,0,'R',false,'');
			if($data['net_weight_tidak_dicetak'] == "FALSE" ) {
			$pdf->SetXY(163, 152);
			$pdf->write(0,"NET WEIGHT : ");
			$pdf->SetXY(183, 152);
			$pdf->write(0,number_format($data['netto'], 2, '.', ',')."KGs");
			}
			$pdf->SetFont('calibri','',8);
			$freight_payable = $data['freight'];

			$pdf->SetXY(65, 220);
			$pdf->MultiCell(110,3, $data['freights_charges'],0,1,false);

			if($data['cetak_jml_doc_origi']== "TRUE") {
	  			$pdf->SetXY(165, 213);
	  			$pdf->write(0,'3(THREE) ORIGINAL',0,'','');
			}

			if($freight_payable == "PREPAID") {
	  			$pdf->SetXY(95, 269);
	  			$pdf->write(0,$freight_payable);
			} else {
	  			$pdf->SetXY(105, 274);
	  			$pdf->write(0,$freight_payable);
			}

			$pdf->SetXY(17, 274);
			$pdf->write(0,strtoupper($tanggal));
		}
		else {
			$pdf->SetXY(155, 42);
			$pdf->SetFont('calibri','B',12);
			$pdf->write(0, $bl_no);
			$pdf->SetFont('calibri','',10);
			$count = $this->CI->m_print_out_bl->get_count_double($sp);
			if($count->num_rows() > 0 ) {
			$pdf->SetXY(10, 200);
				foreach($count->result() as $row ) {
					$pdf->SetX(155);
					$pdf->MultiCell(58,1.5, strtoupper($row->count_no).' / '. strtoupper($row->seal_no).' / '. strtoupper($row->count_size),0,'L',false);
					$pdf->Ln(2.5);
				}

			} else {
				$pdf->SetXY(10, 200);
				$pdf->MultiCell(60, 3.5, $data['cont_seal_no'].' / '.$data['type_cont_size']);
			}
			$pdf->SetXY(107, 50);
			$pdf->write(0, $data['kode_ref']);
			$pdf->SetFont('calibri','',8);
			$pdf->SetXY(10, 41);
			$pdf->MultiCell(99, 3.5, $data['note'],0,1,false);
			$pdf->SetXY(10, 65);
			$pdf->MultiCell(120,3.5, strtoupper($data['consignee_add']));
			$pdf->SetXY(10, 84);
			$pdf->MultiCell(120,3.5, $data['notify_add']);
			$pdf->SetFont('calibri','',9);
			$pdf->SetXY(107, 67);
			$pdf->write(0, 'PT. GATEWAY CONTAINER LINE');

			$pdf->SetXY(62, 111);
			$pdf->write(0, $port_of->port_of_receive);
			$pdf->SetXY(62, 122);
			$pdf->write(0, $port_of->port_of_loading);
			

			$connecting = $this->CI->m_print_out_bl->get_connecting($spd,$data['vessel'].$data['voyage']);
			if($connecting) {
				$pdf->SetXY(10, 122);
				$pdf->write(0, $connecting->vessel);
				$pdf->SetXY(10, 112);
				$pdf->write(0, $connecting->conn_vessel);
			}

			$pdf->SetXY(10, 133);
			$pdf->write(0, $port_of->port_of_discharge);

			$pdf->SetXY(62, 133);
			$pdf->write(0, $port_of->port_of_delivery);

			$agent = $this->CI->m_print_out_bl->getAgentByCode($data['agent_code']);
			if($agent != 0 ) {
				$pdf->SetXY(107, 86);
				$pdf->write(0, $agent['agent_company_name'],0,'L',false);
				$pdf->SetXY(107, 88);
				$pdf->MultiCell(90,3.5, $agent['agent_company_address'],0,'L',false);
			}
		if ($attc_marks > 18 || $attc_discription > 15) {
			$pdf->SetXY(12, 157);
			$pdf->SetFont('Arial', 'B', 9);
			$pdf->MultiCell(150, 5, 'MARKING AND DESCRIPTION ARE ATTACHED IN THE NEXT PAGE', 0, 'L');
			} else {
			$pdf->SetFont('calibri','',9);
			$pdf->SetXY(10, 147);
			$pdf->MultiCell(39,3, $data['marking_nos'],0,'L',false);
				if($fcl=='FCL') {
					$pdf->SetXY(85, 147);
					$pdf->write(0, 'SHIPPER LOAD, COUNT & SEAL CONTAINER,');
					$pdf->SetXY(85, 150);
					$pdf->write(0, 'SAID TO CONTAIN :');
					$pdf->SetXY(85, 152);
					$pdf->MultiCell(60,3,mb_convert_encoding($data['desc_goods'], 'ISO-8859-1', 'UTF-8'),0,'L');
				}
				else
				{
					$pdf->SetXY(85, 147.5);
					$pdf->write(0,'SAID TO CONTAIN :');
					$pdf->SetXY(85, 149);
					$pdf->MultiCell(60,3,mb_convert_encoding($data['desc_goods'], 'ISO-8859-1', 'UTF-8'),0,'L');
				}
			}
		$pdf->SetFont('calibri','',9);
		if($fcl=='FCL')
	   {
			$pdf->SetXY(60, 147);
			$pdf->MultiCell(18,3, $data['no_pack_qty'].' '.'X'.' '.$data['no_pack_unit']);
			$pdf->SetXY(60, 205);
			$pdf->MultiCell(120,3, $data['no_pack_qty'].' ( '.$this->CI->fungsi->num_to_words($data['no_pack_qty'],'',0,'').' ) '.' '.'X'.' '.$data['no_pack_unit'].' ONLY');
		} else {

			$pdf->SetXY(60, 147);
			$pdf->MultiCell(25,3, $data['no_pack_qty'].' '.$data['no_pack_unit']);

			$pdf->SetXY(60, 205);
			$pdf->MultiCell(130,3, $data['no_pack_qty'].' ( '.$this->CI->fungsi->num_to_words($data['no_pack_qty'],'',0,'').' ) '.$data['no_pack_unit'].' ONLY');
		}
		
			if($data['express_release'] == "TRUE") {
				// Set posisi
				$pdf->SetXY(99, 197);

				// Warna border dan teks merah
				$pdf->SetDrawColor(255, 0, 0);  // Border merah
				$pdf->SetTextColor(255, 0, 0);  // Teks merah

				// Set font
				$pdf->SetFont('Arial', 'B', 15);

				// Hitung lebar teks
				$text = 'EXPRESS RELEASE';
				$w = $pdf->GetStringWidth($text) + 10; // tambahkan padding
				$h = 10;

				// Gambar border merah
				$pdf->Cell($w, $h, $text, 1, 0, 'C'); // 1 = borde
			}
			$pdf->SetTextColor(0, 0, 0);
			$pdf->SetFont('calibri','',9);
			if($data['shipped_on_board']== "TRUE") {
				$pdf->SetXY(165, 170);
				$pdf->write(0, 'CLEAN ON BOARD');
				$pdf->SetXY(165, 173);
				$pdf->write(0, 'FROM '.$data['port_loading']);
				$pdf->SetXY(165, 176);
				$pdf->write(0, 'ON '.strtoupper($tanggal));
				$pdf->SetXY(165, 179);
				$pdf->write(0, 'BY '.$data['vessel']);
			}
			$pdf->SetFont('calibri','',8);
			$pdf->SetXY(166, 147);
			$pdf->write(0,number_format($data['weight'], 3, '.', ',').'KGs');

			$pdf->SetXY(186, 147);
			$pdf->write(0,$this->CI->fungsi->pecah4($data['bl_meas']).'M3',0,0,'R',false,'');
			if($data['net_weight_tidak_dicetak'] == "FALSE" ) {
			$pdf->SetXY(166, 152);
			$pdf->write(0,"NET WEIGHT : ");
			$pdf->SetXY(183, 152);
			$pdf->write(0,number_format($data['netto'], 2, '.', ',')."KGs");
			}
			$pdf->SetFont('calibri','',8);
			$freight_payable = $data['freight'];

			$pdf->SetXY(65, 220);
			$pdf->MultiCell(110,3, $data['freights_charges'],0,1,false);

			if($data['cetak_jml_doc_origi']== "TRUE") {
	  			$pdf->SetXY(165, 213);
	  			$pdf->write(0,'3(THREE) ORIGINAL',0,'','');
			}

			if($freight_payable == "PREPAID") {
	  			$pdf->SetXY(95, 269);
	  			$pdf->write(0,$freight_payable);
			} else {
	  			$pdf->SetXY(105, 274);
	  			$pdf->write(0,$freight_payable);
			}

			$pdf->SetXY(15, 274);
			$pdf->write(0,strtoupper($tanggal));
		}
		}
}
	
	if($code==5)
	{
		if($cetak=='TRUE') 
		{  
			$pdf->Image(APPPATH.'third_party/img/AIRWAYBILL.jpg',0,0,+200);
			$pdf->SetXY(5, 29);
			$pdf->MultiCell(89, 3, $data['note'],0,1,false);
			$pdf->SetFont('Arial','B',11);
			$pdf->SetXY(161, 8);
			$pdf->write(0, $bl_no);
			$pdf->SetFont('Arial','B',11);
			$pdf->SetXY(8, 8);
			$pdf->write(0,$data['mother_bl'],0,'L');
	
			$pdf->SetFont('Arial','',8);
			$pdf->SetXY(5, 52);
			$pdf->MultiCell(90,3,$data['consignee_add'],0,'L');
			$pdf->SetXY(30, 120);
			$pdf->MultiCell(90,3,  $data['handling_information'],0,'L');
			//$pdf->SetXY(5, 95);
			//$pdf->write(0, $port_of->port_of_receive);
		
			$pdf->SetXY(5, 95);
			$pdf->write(0, $port_of->port_of_loading);
			
			$connecting = $this->CI->m_print_out_bl->get_connecting($spd,$data['vessel'].' '.$data['voyage']);
			$q = $this->CI->dbx->get_where('tbl_detail',array('detail_id'=>$spd));
			if($connecting) {
				$pdf->SetXY(18, 104);
				$pdf->write(0, $connecting->vessel);
				//$pdf->SetXY(58, 145);
				//$pdf->write(0, 'Connecting Vessel');
				//$pdf->SetXY(5, 100);
				//$pdf->write(0, $connecting->conn_vessel);
			}

			$pdf->SetXY(0, 104);
			$pdf->write(0, $data['to_1']);

			$pdf->SetXY(58, 104);
			$pdf->write(0, $data['to_2']);
			
			$pdf->SetXY(68, 104);
			$pdf->write(0, $data['by_2']);
			
			$pdf->SetXY(102, 104);
			$pdf->write(0,$data['currency_declaration']);
			
			$freight_payable = $data['freight'];
				if($freight_payable == "PREPAID") {
	  			$pdf->SetXY(111, 105);
	  			$pdf->write(0,'PP');
	  			$pdf->SetXY(118, 105);
	  			$pdf->write(0,'X');
	  			$pdf->SetXY(128, 105);
	  			$pdf->write(0,'X');
			} else {
	  			$pdf->SetXY(110, 105);
	  			$pdf->write(0,'COL');
  				$pdf->SetXY(123, 105);
	  			$pdf->write(0,'X');
	  			$pdf->SetXY(133, 105);
	  			$pdf->write(0,'X');
		 }
		 if ($data['value_for_carriage']){
			 $pdf->SetXY(150, 105);
			 $pdf->write(0,$data['value_for_carriage']);
	  			
			 $pdf->SetXY(178, 105);
			 $pdf->write(0,$data['value_for_custom']);
			 
		 }else{
				$pdf->SetXY(150, 105);
	  			$pdf->write(0,'N.V.D');
	  			
	  			$pdf->SetXY(178, 105);
	  			$pdf->write(0,'N.C.V');
		 }
			$caya = $this->CI->m_print_out_bl->get_caya_caya($spd);
			$pdf->SetXY(136, 107);
			$pdf->write(0, $caya);
		
			if($attc_marks > 10) {
				$pdf->SetXY(0, 133);
				$pdf->MultiCell(39,3, '"SEE ATTACHMENT LIST"',0,'C',false);
			} else {
                                  if($data['marking_nos'] != 0 )  {
			$pdf->SetXY(5, 155);
			$pdf->SetFont('ARIAL','U');
			$pdf->write(0,'SHIPPING MARKS & NOS');
			$pdf->SetXY(5, 157);
                                $pdf->SetFont('ARIAL','');
			$pdf->MultiCell(27,3, $data['marking_nos'],0,1,false);    } }
			$agent = $this->CI->m_print_out_bl->getAgentByCode($data['agent_code'],0,'L',false);

			if($data['agent_code']) {
                                        $pdf->SetXY(45, 155);
                                        $pdf->SetFont('ARIAL','U');
                                        $pdf->write(0,'DELIVERY AGENT ADDRESS :');
                                        $pdf->SetFont('ARIAL','');
				$pdf->SetXY(45, 158.5);
				$pdf->write(0, $agent['agent_company_name']);
				$pdf->SetXY(45, 160);
				$pdf->MultiCell(60,2.5, $agent['agent_company_address'],0,'L',false);
			}
                      if($fcl=='FCL') {
			                $pdf->SetXY(38, 123);
		                 	$pdf->MultiCell(16,3, $data['no_pack_qty'].' '.'X'.' '.$data['no_pack_unit']);
		                  	$pdf->SetXY(38, 195);

                               }
                               else
                               {
			$pdf->SetXY(5, 146);
			$pdf->MultiCell(20,0,$data['no_pack_qty'] ,0,1,false);

     	                         }

			if($attc_discription > 15)
			{
				$pdf->SetXY(83, 140);
				$pdf->MultiCell(40,3,'"SEE ATTACHMENT LIST"');
			}
			else
			{
			$pdf->SetXY(105, 146);
			$pdf->MultiCell(85,3,$data['desc_goods'],0,1,false);
                                }
			if($data['express_release'] == "TRUE") { 
				$pdf->SetXY(100, 190);
				$pdf->write(0, '" EXPRESS RELEASE "');
			}
	
			if($data['shipped_on_board']== "TRUE") {
				$pdf->SetXY(150, 165);
				$pdf->write(0, 'SHIPPED ON BOARD');
				$pdf->SetXY(150, 168);
				$pdf->write(0, 'FROM '.$data['port_loading']);
				$pdf->SetXY(152, 171);
				$pdf->write(0, 'ON '.$tanggal);
				$pdf->SetXY(152, 174);
				$pdf->write(0, 'BY '.$data['vessel'].' '.$data['voyage']);
			}
	
  
			//$pdf->SetXY(153, 130);
			//$pdf->write(0, 'GW :');
			$pdf->SetXY(10, 145);
			$pdf->Cell(13,2,$this->CI->fungsi->pecah($data['weight'],','),0,0,'R',false,'');
			$pdf->SetXY(24, 146);
			$pdf->write(0, ' KGS');
			$pdf->SetXY(60, 145);
			$pdf->Cell(13,2,$this->CI->fungsi->pecah($data['weight'],','),0,0,'R',false,'');
			$pdf->SetXY(74, 146);
			$pdf->write(0, ' KGS');
							$pdf->SetFont('ARIAL','B');
                                $pdf->SetXY(84, 146);
                                $pdf->write(0, 'AS AGREED');
                                $pdf->SetXY(30, 210);
                                $pdf->write(0, 'AS AGREED');
                                $pdf->SetXY(30, 260);
                                $pdf->write(0, 'AS AGREED');
	
			$pdf->SetFont('Arial','',8);

	  			$pdf->SetXY(137, 88);
	  			$pdf->write(0,$data['acc_information_detail']);
	
			if($data['cetak_jml_doc_origi'] == "TRUE") { 
	  			$pdf->SetXY(93, 215);
	  			$pdf->MultiCell(30,3,'3(THREE) ORIGINAL',0,'','');
			}
			$shipper = $this->CI->user_model->get_shipper_by_id_shipper($data['shipper'],$data['shipper_print'])->shipper_name;
			$pdf->SetXY(100, 236);
			$pdf->setFont ('Arial','B',10);
                                $pdf->Cell(7,0, strtoupper($data['shipper_certification']));
			
	
			$pdf->SetXY(85, 258);
			$pdf->setFont ('Arial','',8);
			$pdf->write(0,strtoupper($tanggal));
                                $pdf->SetXY(155, 258);
			$pdf->write(0,strtoupper($this->CI->session->userdata('region_id')));
			$pdf->SetXY(181, 258);
			$pdf->write(0,strtoupper('CGK/GCL'));
                                $pdf->SetXY(85, 269);    
			$pdf->setFont ('Arial','B',10);
                                $pdf->write(0, strtoupper('PT. GATEWAY CONTAINER LINE'));
		}
		else
		{
					$pdf->SetXY(5, 29);
			$pdf->MultiCell(89, 3, $data['note'],0,1,false);
			$pdf->SetFont('Arial','B',11);
			$pdf->SetXY(161, 8);
			$pdf->write(0, $bl_no);
			$pdf->SetFont('Arial','B',11);
			$pdf->SetXY(8, 8);
			$pdf->write(0,$data['mother_bl'],0,'L');
	
			$pdf->SetFont('Arial','',8);
			$pdf->SetXY(5, 52);
			$pdf->MultiCell(90,3,$data['consignee_add'],0,'L');
			$pdf->SetXY(30, 120);
			$pdf->MultiCell(90,3,  $data['handling_information'],0,'L');
			//$pdf->SetXY(5, 95);
			//$pdf->write(0, $port_of->port_of_receive);
		
			$pdf->SetXY(5, 95);
			$pdf->write(0, $port_of->port_of_loading);
			
			$connecting = $this->CI->m_print_out_bl->get_connecting($spd,$data['vessel'].' '.$data['voyage']);
			$q = $this->CI->dbx->get_where('tbl_detail',array('detail_id'=>$spd));
			if($connecting) {
				$pdf->SetXY(18, 104);
				$pdf->write(0, $connecting->vessel);
				//$pdf->SetXY(58, 145);
				//$pdf->write(0, 'Connecting Vessel');
				//$pdf->SetXY(5, 100);
				//$pdf->write(0, $connecting->conn_vessel);
			}

			$pdf->SetXY(0, 104);
			$pdf->write(0, $data['to_1']);

			$pdf->SetXY(58, 104);
			$pdf->write(0, $data['to_2']);
			
			$pdf->SetXY(68, 104);
			$pdf->write(0, $data['by_2']);
			
			$pdf->SetXY(102, 104);
			$pdf->write(0,$data['currency_declaration']);
			
			$freight_payable = $data['freight'];
				if($freight_payable == "PREPAID") {
	  			$pdf->SetXY(111, 105);
	  			$pdf->write(0,'PP');
	  			$pdf->SetXY(118, 105);
	  			$pdf->write(0,'X');
	  			$pdf->SetXY(128, 105);
	  			$pdf->write(0,'X');
			} else {
	  			$pdf->SetXY(110, 105);
	  			$pdf->write(0,'COL');
  				$pdf->SetXY(123, 105);
	  			$pdf->write(0,'X');
	  			$pdf->SetXY(133, 105);
	  			$pdf->write(0,'X');
		 }
		 if ($data['value_for_carriage']){
			 $pdf->SetXY(150, 105);
			 $pdf->write(0,$data['value_for_carriage']);
	  			
			 $pdf->SetXY(178, 105);
			 $pdf->write(0,$data['value_for_custom']);
			 
		 }else{
				$pdf->SetXY(150, 105);
	  			$pdf->write(0,'N.V.D');
	  			
	  			$pdf->SetXY(178, 105);
	  			$pdf->write(0,'N.C.V');
		 }
			$caya = $this->CI->m_print_out_bl->get_caya_caya($spd);
			$pdf->SetXY(136, 107);
			$pdf->write(0, $caya);
		
			if($attc_marks > 10) {
				$pdf->SetXY(0, 133);
				$pdf->MultiCell(39,3, '"SEE ATTACHMENT LIST"',0,'C',false);
			} else {
                                  if($data['marking_nos'] != 0 )  {
			$pdf->SetXY(5, 155);
			$pdf->SetFont('ARIAL','U');
			$pdf->write(0,'SHIPPING MARKS & NOS');
			$pdf->SetXY(5, 157);
                                $pdf->SetFont('ARIAL','');
			$pdf->MultiCell(27,3, $data['marking_nos'],0,1,false);    } }
			$agent = $this->CI->m_print_out_bl->getAgentByCode($data['agent_code'],0,'L',false);

			if($data['agent_code']) {
                                        $pdf->SetXY(45, 155);
                                        $pdf->SetFont('ARIAL','U');
                                        $pdf->write(0,'DELIVERY AGENT ADDRESS :');
                                        $pdf->SetFont('ARIAL','');
				$pdf->SetXY(45, 158.5);
				$pdf->write(0, $agent['agent_company_name']);
				$pdf->SetXY(45, 160);
				$pdf->MultiCell(60,2.5, $agent['agent_company_address'],0,'L',false);
			}
                      if($fcl=='FCL') {
			                $pdf->SetXY(38, 123);
		                 	$pdf->MultiCell(16,3, $data['no_pack_qty'].' '.'X'.' '.$data['no_pack_unit']);
		                  	$pdf->SetXY(38, 195);

                               }
                               else
                               {
			$pdf->SetXY(5, 146);
			$pdf->MultiCell(20,0,$data['no_pack_qty'] ,0,1,false);

     	                         }

			if($attc_discription > 15)
			{
				$pdf->SetXY(83, 140);
				$pdf->MultiCell(40,3,'"SEE ATTACHMENT LIST"');
			}
			else
			{
			$pdf->SetXY(105, 146);
			$pdf->MultiCell(85,3,$data['desc_goods'],0,1,false);
                                }
			if($data['express_release'] == "TRUE") { 
				$pdf->SetXY(100, 190);
				$pdf->write(0, '" EXPRESS RELEASE "');
			}
	
			if($data['shipped_on_board']== "TRUE") {
				$pdf->SetXY(150, 165);
				$pdf->write(0, 'SHIPPED ON BOARD');
				$pdf->SetXY(150, 168);
				$pdf->write(0, 'FROM '.$data['port_loading']);
				$pdf->SetXY(152, 171);
				$pdf->write(0, 'ON '.$tanggal);
				$pdf->SetXY(152, 174);
				$pdf->write(0, 'BY '.$data['vessel'].' '.$data['voyage']);
			}
	
  
			//$pdf->SetXY(153, 130);
			//$pdf->write(0, 'GW :');
			$pdf->SetXY(10, 145);
			$pdf->Cell(13,2,$this->CI->fungsi->pecah($data['weight'],','),0,0,'R',false,'');
			$pdf->SetXY(24, 146);
			$pdf->write(0, ' KGS');
			$pdf->SetXY(60, 145);
			$pdf->Cell(13,2,$this->CI->fungsi->pecah($data['weight'],','),0,0,'R',false,'');
			$pdf->SetXY(74, 146);
			$pdf->write(0, ' KGS');
							$pdf->SetFont('ARIAL','B');
                                $pdf->SetXY(84, 146);
                                $pdf->write(0, 'AS AGREED');
                                $pdf->SetXY(30, 210);
                                $pdf->write(0, 'AS AGREED');
                                $pdf->SetXY(30, 260);
                                $pdf->write(0, 'AS AGREED');
	
			$pdf->SetFont('Arial','',8);

	  			$pdf->SetXY(137, 88);
	  			$pdf->write(0,$data['acc_information_detail']);
	
			if($data['cetak_jml_doc_origi'] == "TRUE") { 
	  			$pdf->SetXY(93, 215);
	  			$pdf->MultiCell(30,3,'3(THREE) ORIGINAL',0,'','');
			}
			$shipper = $this->CI->user_model->get_shipper_by_id_shipper($data['shipper'],$data['shipper_print'])->shipper_name;
			$pdf->SetXY(100, 236);
			$pdf->setFont ('Arial','B',10);
                                $pdf->Cell(7,0, strtoupper($data['shipper_certification']));
			
	
			$pdf->SetXY(85, 258);
			$pdf->setFont ('Arial','',8);
			$pdf->write(0,strtoupper($tanggal));
                                $pdf->SetXY(155, 258);
			$pdf->write(0,strtoupper($this->CI->session->userdata('region_id')));
			$pdf->SetXY(181, 258);
			$pdf->write(0,strtoupper('CGK/GCL'));
                                $pdf->SetXY(85, 269);    
			$pdf->setFont ('Arial','B',10);
                                $pdf->write(0, strtoupper('PT. GATEWAY CONTAINER LINE'));
		}
	}
	
	if($code==6)
	{
		if($cetak=='TRUE')
		{
			$pdf->Image(APPPATH.'third_party/img/fukuyama.jpg',0,0,+200);
			$pdf->SetFont('Arial','',7);
			$pdf->SetXY(10, 15);
			$pdf->MultiCell(89, 3, $data['note'],0,'',false);
			$pdf->SetXY(150, 16);
			$pdf->SetFont('Arial','B',10);
			$pdf->write(0, str_replace('%20',' ',$bl_no));
			$agent = $this->CI->m_print_out_bl->getAgentByCode($data['agent_code'],0,'L',false);

			if($agent != 0 ) {
                                        $pdf->SetFont('Arial','',7);
				$pdf->SetXY(10, 43);
				$pdf->write(0,$agent['agent_company_name']);
				$pdf->SetXY(10, 45);
				$pdf->MultiCell(90,3, $agent['agent_company_address'],0,'L',false);
			}
			$pdf->SetFont('Arial','',7);
			$pdf->SetXY(10, 66);
			$pdf->MultiCell(90,3,($data['consignee_add']),0,'L');
			//$pdf->SetXY(10, 75);
			//$pdf->SetFont('Arial','',7);
			//$pdf->MultiCell(90,3, ($data['notify_add']),0,'L');

			$pdf->SetXY(10, 91);
			$pdf->write(0, $port_of->port_of_receive);
			$pdf->SetXY(10, 108);
			$pdf->write(0, $port_of->port_of_loading);

			$connecting = $this->CI->m_print_out_bl->get_connecting($spd,$data['vessel'].' '.$data['voyage']);
			$q = $this->CI->dbx->get_where('tbl_detail',array('detail_id'=>$spd));
			if($connecting) {
				$pdf->SetXY(10, 99.5);
				$pdf->write(0, $data['vessel']);
				$pdf->SetXY(57, 99.5);
                                        $pdf->write(0, $data['voyage']);
				if($q->row()->snd_vessel != '')
				{
				$pdf->write(0, 'Connecting Vessel');
				$pdf->SetXY(52, 109);
				$pdf->write(0, strtoupper($connecting->conn_vessel));  }
			}

			$pdf->SetXY(57, 108);
			$pdf->write(0, $port_of->port_of_discharge);
			$pdf->SetXY(107, 108);
			$pdf->write(0, $port_of->port_of_delivery);

			$agent = $this->CI->m_print_out_bl->getAgentByCode($data['agent_code'],0,'L',false);

			if($agent != 0 ) {
				$pdf->SetXY(105, 80);
				$pdf->write(0,$agent['agent_company_name']);
				$pdf->SetXY(105, 83);
				$pdf->MultiCell(90,3, $agent['agent_company_address'],0,'L',false);
			}

			if($attc_marks > 18) {
				$pdf->SetXY(10, 123);
				$pdf->MultiCell(39,3, '"SEE ATTACHMENT LIST"',0,'C',false);
			} else {
				$pdf->SetXY(10, 120);
				$pdf->MultiCell(39,3,($data['marking_nos']),0,'L',false);
			}

			$pdf->SetXY(10, 195);
			$pdf->write(0, 'CONTAINER / SEAL NO');
			
			////double countener /////
			
			$count = $this->CI->m_print_out_bl->get_count_double($sp);
			if($count->num_rows() > 0 ) {
				$pdf->Ln(2.5);
				foreach($count->result() as $row ) {
					$pdf->MultiCell(55,1.5, strtoupper($row->count_no).' / '. strtoupper($row->seal_no).' / '. strtoupper($row->count_size),0,'L',false);
					$pdf->Ln();
				}
			}
	  		else 
	  		{
	  			$pdf->SetXY(10, 196);
				$pdf->MultiCell(45, 3, $last_container.' / '.$data['type_cont_size']);
	  		}
	  		
	  		////double countener /////
	  		
	  		
	  		//$pdf->SetXY(10, 179);
			//$pdf->MultiCell(45, 3, $last_container.' / '.$data['type_cont_size']);
    

	  	        if($fcl=='FCL') {
			                $pdf->SetXY(56, 120);
		                 	$pdf->MultiCell(16,3, $data['no_pack_qty'].' '.'X'.' '.$data['no_pack_unit']);
		                  	$pdf->SetXY(53, 184);

			           $pdf->MultiCell(120,3, $data['no_pack_qty'].' ( '.$this->CI->fungsi->num_to_words($data['no_pack_qty'],'',0,'').' ) '.' '.'X'.' '.$data['no_pack_unit'].' ONLY');
                               }
                               else
                               {
                                $pdf->SetXY(56, 120);
			$pdf->MultiCell(20,3, $data['no_pack_qty'].' '.$data['no_pack_unit']);
			$pdf->SetXY(50, 211);

			$pdf->MultiCell(120,3, $data['no_pack_qty'].' ( '.$this->CI->fungsi->num_to_words($data['no_pack_qty'],'',0,'').' ) '.$data['no_pack_unit'].' ONLY');
			                           }
			if($data['express_release'] == "TRUE") 
			{
				$pdf->SetXY(94, 182);
				$pdf->write(0, '" EXPRESS RELEASE "');
			}
			
			if($data['shipped_on_board'] == "TRUE") 
			{
				$pdf->SetXY(140, 160);
				$pdf->write(0, 'SHIPPED ON BOARD');
				$pdf->SetXY(140, 163);
				$pdf->write(0, 'FROM '.$port_of->port_of_loading);
				$pdf->SetXY(140, 166);
				$pdf->write(0, 'ON '.strtoupper($tanggal));
				$pdf->SetXY(140, 169);
				$pdf->write(0, 'BY '.$connecting->vessel);
			}

			if($fcl=='FCL') {
				$pdf->SetXY(75, 125);
				$pdf->write(0, 'SHIPPER LOAD, COUNT & SEAL');
				$pdf->SetXY(75, 128);
				$pdf->write(0, $data['no_pack_qty'].' '.'X'.' '.$data['no_pack_unit'].' CONTAINER, SAID TO CONTAIN :');
			}
			else
			{
				$pdf->SetXY(75, 121.5);
				$pdf->write(0,'SAID TO CONTAIN :');
			}

			if($attc_discription > 15)
			{
				$pdf->SetXY(90, 133);
				$pdf->MultiCell(40,3,'"SEE ATTACHMENT LIST"');
			}
			else
			{
				$pdf->SetXY(75, 123);
				$pdf->MultiCell(60,3,($data['desc_goods']),0,'L');
			}
			
			////print_r($attc_discription);
			//return false;

			$pdf->SetXY(152, 127);
			$pdf->write(0,$this->CI->fungsi->pecah($data['weight'],','),0,0,'R',false,'');
			//$pdf->Cell(13,2,number_format ($data['weight'], 3, '.', ','),0,0,'R',false,'');
			$pdf->SetXY(162, 127);
			$pdf->write(0, 'KGS');

			if($data['net_weight_tidak_dicetak'] == "FALSE" ) {
				$pdf->SetXY(153, 135);
				$pdf->write(0,'NW : ');
				$pdf->SetXY(172, 134);
				$pdf->Cell(13,2,$this->CI->fungsi->pecah($data['netto'],','),0,0,'R',false,'');
				//$pdf->Cell(13,2,number_format ($data['netto'], 3, '.', ','),0,0,'R',false,'');
				$pdf->SetXY(183, 135);
				$pdf->write(0, ' KGS');
			}

                                if($data['bl_meas'] != '' ) {
			$pdf->SetXY(173, 127);
			$pdf->write(0,$this->CI->fungsi->pecah4($data['bl_meas'],','),0,0,'R',false,'');
			$pdf->SetXY(183,127);
			$pdf->write(0, 'M');
			$pdf->SetXY(185.5, 126);
			$pdf->SetFont('Arial','',6);
			$pdf->write(0,'3');  }

			$pdf->SetFont('Arial','',8);
			$pdf->SetXY(140, 211);
			$pdf->write(0, 'FREIGHT');

			$pdf->SetFont('Arial','',8);
			$pdf->SetXY(10, 200);
			$pdf->MultiCell(40,3, $data['freights_charges']);

			$freight_payable = $data['freight'];
	  			$pdf->SetXY(20, 211);
	  			$pdf->write(0,$freight_payable);


			if($data['cetak_jml_doc_origi'] == "TRUE") {
	  			$pdf->SetXY(108, 198);
	  			$pdf->MultiCell(30,3,'3(THREE) ORIGINALS',0,'','');
			}

			$pdf->SetXY(140, 220);
			$pdf->MultiCell(50,59,strtoupper($this->CI->session->userdata('region_id').', '.$tanggal));

			$pdf->SetXY(130, 279);
			$pdf->write(0,'PT GATEWAY CONTAINER LINE');
			$pdf->SetXY(130, 248);
			$pdf->MultiCell(50,3,strtoupper($as_agent),0,'L');
		}

 else
		{
			
                $pdf->SetAutoPageBreak(true, 0) ;
			$pdf->SetFont('Arial','',8);
			$pdf->SetXY(10, 10);
			$pdf->MultiCell(89, 3, $data['note'],0,'',false);
			$pdf->SetXY(185, 16);
			$pdf->SetFont('Arial','B',11);
			$pdf->write(0, str_replace('%20',' ',$bl_no));
			$agent = $this->CI->m_print_out_bl->getAgentByCode($data['agent_code'],0,'L',false);

			if($agent != 0 ) {
                                        $pdf->SetFont('Arial','',8);
				$pdf->SetXY(10, 43);
				$pdf->write(0,$agent['agent_company_name']);
				$pdf->SetXY(10, 45);
				$pdf->MultiCell(90,3, $agent['agent_company_address'],0,'L',false);
			}
			$pdf->SetFont('Arial','',8);
			$pdf->SetXY(10, 68);
			$pdf->MultiCell(90,3,($data['consignee_add']),0,'L');
			//$pdf->SetXY(10, 75);
			//$pdf->SetFont('Arial','',7);
			//$pdf->MultiCell(90,3, ($data['notify_add']),0,'L');

			$pdf->SetXY(10, 99.5);
			$pdf->write(0, $port_of->port_of_receive);
			$pdf->SetXY(10, 118);
			$pdf->write(0, $port_of->port_of_loading);

			$connecting = $this->CI->m_print_out_bl->get_connecting($spd,$data['vessel'].' '.$data['voyage']);
			$q = $this->CI->dbx->get_where('tbl_detail',array('detail_id'=>$spd));
			if($connecting) {
				$pdf->SetXY(10, 108.5);
				$pdf->write(0, $data['vessel']);
				$pdf->SetXY(67, 108.5);
                                        $pdf->write(0, $data['voyage']);
				if($q->row()->snd_vessel != '')
				{
				$pdf->write(0, 'Connecting Vessel');
				$pdf->SetXY(52, 109);
				$pdf->write(0, strtoupper($connecting->conn_vessel));  }
			}

			$pdf->SetXY(67, 118);
			$pdf->write(0, $port_of->port_of_discharge);
			$pdf->SetXY(130, 118);
			$pdf->write(0, $port_of->port_of_delivery);

			$agent = $this->CI->m_print_out_bl->getAgentByCode($data['agent_code'],0,'L',false);

			if($agent != 0 ) {
				$pdf->SetXY(120, 85);
				$pdf->write(0,$agent['agent_company_name']);
				$pdf->SetXY(120, 87);
				$pdf->MultiCell(90,3, $agent['agent_company_address'],0,'L',false);
			}

			if($attc_marks > 18) {
				$pdf->SetXY(10, 123);
				$pdf->MultiCell(39,3, '"SEE ATTACHMENT LIST"',0,'C',false);
			} else {
				$pdf->SetXY(10, 120);
				$pdf->MultiCell(39,3,($data['marking_nos']),0,'L',false);
			}

			$pdf->SetXY(10, 210);
			$pdf->write(0, 'CONTAINER / SEAL NO');
			
			////double countener /////
			
			$count = $this->CI->m_print_out_bl->get_count_double($sp);
			if($count->num_rows() > 0 ) {
				$pdf->Ln(2.5);
				foreach($count->result() as $row ) {
					$pdf->MultiCell(55,1.5, strtoupper($row->count_no).' / '. strtoupper($row->seal_no).' / '. strtoupper($row->count_size),0,'L',false);
					$pdf->Ln();
				}
			}
	  		else 
	  		{
	  			$pdf->SetXY(10, 212);
				$pdf->MultiCell(45, 3, $last_container.' / '.$data['type_cont_size']);
	  		}
	  		
	  		////double countener /////


	  	        if($fcl=='FCL') {
			                $pdf->SetXY(67, 135);
		                 	$pdf->Write(0, $data['no_pack_qty'].' '.'X'.' '.$data['no_pack_unit']);
		                  	$pdf->SetXY(53, 240);

			           $pdf->Write
                                           (0, $data['no_pack_qty'].' ( '.$this->CI->fungsi->num_to_words($data['no_pack_qty'],'',0,'').' ) '.' '.'X'.' '.$data['no_pack_unit'].' ONLY');
                               }
                               else
                               {
                                $pdf->SetXY(67, 135);
			$pdf->Write(0, $data['no_pack_qty'].' '.$data['no_pack_unit']);
			$pdf->SetXY(50, 240);
			$pdf->Write(0, $data['no_pack_qty'].' ( '.$this->CI->fungsi->num_to_words($data['no_pack_qty'],'',0,'').' ) '.$data['no_pack_unit'].' ONLY');
			                           }
			if($data['express_release'] == "TRUE") 
			{
				$pdf->SetXY(94, 182);
				$pdf->write(0, '" EXPRESS RELEASE "');
			}
			
			if($data['shipped_on_board'] == "TRUE") 
			{
				$pdf->SetXY(160, 210);
				$pdf->write(0, 'SHIPPED ON BOARD');
				$pdf->SetXY(160, 213);
				$pdf->write(0, 'FROM '.$port_of->port_of_loading);
				$pdf->SetXY(160, 216);
				$pdf->write(0, 'ON '.strtoupper($tanggal));
				$pdf->SetXY(160, 219);
				$pdf->write(0, 'BY '.$connecting->vessel);
			}

			if($fcl=='FCL') {
				$pdf->SetXY(90, 135);
				$pdf->write(0, 'SHIPPER LOAD, COUNT & SEAL');
				$pdf->SetXY(90, 138);
				$pdf->write(0, $data['no_pack_qty'].' '.'X'.' '.$data['no_pack_unit'].' CONTAINER, SAID TO CONTAIN :');
			}
			else
			{
				$pdf->SetXY(90, 135);
				$pdf->write(0,'SAID TO CONTAIN :');
			}

			if($attc_discription > 15)
			{
				$pdf->SetXY(105, 138);
				$pdf->MultiCell(40,3,'"SEE ATTACHMENT LIST"');
			}
			else
			{
				$pdf->SetXY(90, 135);
				$pdf->MultiCell(60,3,($data['desc_goods']),0,'L');
			}
			
			////print_r($attc_discription);
			//return false;

			$pdf->SetXY(174, 135);
			$pdf->write(0,$this->CI->fungsi->pecah($data['weight'],','),0,0,'R',false,'');
			//$pdf->Cell(13,2,number_format ($data['weight'], 3, '.', ','),0,0,'R',false,'');
			$pdf->SetXY(186, 135);
			$pdf->write(0, 'KGS');

			if($data['net_weight_tidak_dicetak'] == "FALSE" ) {
				$pdf->SetXY(174, 140);
				$pdf->write(0,'NW : ');
				$pdf->SetXY(200, 140);
				$pdf->Write(0,$this->CI->fungsi->pecah($data['netto'],','),0,0,'R',false,'');
				//$pdf->Cell(13,2,number_format ($data['netto'], 3, '.', ','),0,0,'R',false,'');
				$pdf->SetXY(212,140);
				$pdf->write(0,'KGS');
			}

                                if($data['bl_meas'] != '' ) {
			$pdf->SetXY(203, 135);
			$pdf->write(0,$this->CI->fungsi->pecah4($data['bl_meas'],','),0,0,'R',false,'');
			$pdf->SetXY(214,135);
			$pdf->write(0, 'M');
			$pdf->SetXY(216.5, 134);
			$pdf->SetFont('Arial','',6);
			$pdf->write(0,'3');  }

			$pdf->SetFont('Arial','',8);
			$pdf->SetXY(145, 240);
			$pdf->write(0, 'FREIGHT');

			$pdf->SetFont('Arial','',8);
			$pdf->SetXY(10, 200);
			$pdf->MultiCell(40,3, $data['freights_charges']);

			$freight_payable = $data['freight'];
	  			$pdf->SetXY(158, 240);
	  			$pdf->write(0,$freight_payable);


			if($data['cetak_jml_doc_origi'] == "TRUE") {
	  			$pdf->SetXY(108, 220);
	  			$pdf->MultiCell(30,3,'3(THREE) ORIGINAL',0,'','');
			}

			$pdf->SetXY(145, 285);
			$pdf->write(0,strtoupper($this->CI->session->userdata('region_id').', '.$tanggal));

			$pdf->SetXY(150,318);
			$pdf->write(0,'PT GATEWAY CONTAINER LINE');
			$pdf->SetXY(130, 218);
			$pdf->MultiCell(50,3,strtoupper($as_agent),0,'L');
	}
	 }
	
	if($code==7)
	{
			$pdf->SetAutoPageBreak(0, 0);
			//$pdf->SetMargins(10,5,0,10);
			
			if($cetak=='TRUE')
			{
				$pdf->Image(APPPATH.'third_party/img/fukuyama2.jpg',0,0,211,298);
			}
			
			$pdf->SetFont('Arial','',7);
			$pdf->SetXY(15, 14);
			$pdf->MultiCell(89, 3, $data['note'],0,'',false);
			$pdf->SetXY(158, 17);
			$pdf->SetFont('Arial','B',10);
			$pdf->write(0, str_replace('%20',' ',$bl_no));

			$pdf->SetFont('Arial','',7);
			$pdf->SetXY(15, 40);
			$pdf->MultiCell(90,3,($data['consignee_add']),0,'L');
			$pdf->SetXY(15, 65);
			$pdf->SetFont('Arial','',7);
			$pdf->MultiCell(90,3, ($data['notify_add']),0,'L');
			
			$agent = $this->CI->m_print_out_bl->getAgentByCode($data['agent_code'],0,'L',false);

			if($agent != 0 ) {
				$pdf->SetXY(112, 88);
				$pdf->write(0,$agent['agent_company_name']);
				$pdf->SetXY(112, 90);
				$pdf->MultiCell(120,3, $agent['agent_company_address'],0,'L',false);
			}

			if($attc_marks > 18) {
				$pdf->SetXY(10, 123);
				$pdf->MultiCell(39,3, '"SEE ATTACHMENT LIST"',0,'C',false);
			} else {
				$pdf->SetXY(10, 123);
				$pdf->MultiCell(39,3,($data['marking_nos']),0,'L',false);
			}
			
			$pdf->SetXY(60, 92);
			$pdf->write(0, $port_of->port_of_receive);
			$pdf->SetXY(60, 101);
			$pdf->write(0, $port_of->port_of_loading);

			$connecting = $this->CI->m_print_out_bl->get_connecting($spd,$data['vessel'].' '.$data['voyage']);
			$q = $this->CI->dbx->get_where('tbl_detail',array('detail_id'=>$spd));
			if($connecting) {
				$pdf->SetXY(12, 101);
				$pdf->write(0, $data['vessel']);
				$pdf->SetXY(41, 101);
               $pdf->write(0, $data['voyage']);
				if($q->row()->snd_vessel != '')
				{
					$pdf->SetXY(15, 92);
					$pdf->write(0, strtoupper($connecting->conn_vessel)); 
				}
			}

			$pdf->SetXY(15, 110);
			$pdf->write(0, $port_of->port_of_discharge);
			$pdf->SetXY(60, 110);
			$pdf->write(0, $port_of->port_of_delivery);
			$pdf->SetXY(109, 110);
			$pdf->write(0, $port_of->port_of_delivery);

			

			$pdf->SetXY(10, 171);
			$pdf->write(0, 'CONTAINER / SEAL NO');
			
			$count = $this->CI->m_print_out_bl->get_count_double($sp);
			if($count->num_rows() > 0 ) {
				$pdf->Ln(2.5);
				foreach($count->result() as $row ) {
					$pdf->MultiCell(55,1.5, strtoupper($row->count_no).' / '. strtoupper($row->seal_no).' / '. strtoupper($row->count_size),0,'L',false);
					$pdf->Ln();
				}
			}
	  		else 
	  		{
	  			$pdf->SetXY(10, 173);
				$pdf->MultiCell(45, 3, $last_container.' / '.$data['type_cont_size']);
	  		}
	  		
	  		if($fcl=='FCL') { 
               $dbl = $this->CI->afg_m_stuffing_plan->get_double($sp);
           		if($dbl->num_rows() > 0 ) {
               	$pdf->SetXY(60, 130);
                  $pdf->Ln(1);
                 	foreach($dbl->result() as $row ) 
                 	{
                  	$pdf->MultiCell(60,3, $row->count_num.' '.'X'.' '.$row->count_type,0,"R");
            	    	$pdf->Ln(0.5);
                	}
                 	$pdf->Ln(1);
                	$pdf->SetXY(56, 193);
                	foreach($dbl->result() as $row ) 
                	{
                 		$pdf->Multicell(56,3, $row->count_num.' ( '.$this->CI->fungsi->num_to_words($row->count_num,'',0,'').' ) '.' '.'X'.' '.$row->count_type);
                  	$pdf->SetXY(56, 189);
                	}
           		}
              	else
              	{  
               	$pdf->SetXY(60, 130);
               	$pdf->MultiCell(16,3, $data['no_pack_qty'].' '.'X'.' '.$data['no_pack_unit']);
               	$pdf->SetXY(56, 193);
               	$pdf->MultiCell(120,3, $data['no_pack_qty'].' ( '.$this->CI->fungsi->num_to_words($data['no_pack_qty'],'',0,'').' ) '.' '.'X'.' '.$data['no_pack_unit'].' CONTAINERS ONLY');
             	}
      		}
			else
         	{
           		$pdf->SetXY(60, 123);
				$pdf->MultiCell(20,3, $data['no_pack_qty'].' '.$data['no_pack_unit']);
				$pdf->SetXY(50, 186);
				$pdf->MultiCell(120,3, $data['no_pack_qty'].' ( '.$this->CI->fungsi->num_to_words($data['no_pack_qty'],'',0,'').' ) '.$data['no_pack_unit'].' ONLY');
			}
			
			if($data['express_release'] == "TRUE") 
			{
				$pdf->SetXY(94, 182);
				$pdf->write(0, '" EXPRESS RELEASE "');
			}
			
			if($data['shipped_on_board'] == "TRUE") 
			{
				$pdf->SetXY(140, 170);
				$pdf->write(0, 'SHIPPED ON BOARD');
				$pdf->SetXY(140, 173);
				$pdf->write(0, 'FROM '.$port_of->port_of_loading);
				$pdf->SetXY(140, 176);
				$pdf->write(0, 'ON '.strtoupper($tanggal));
				$pdf->SetXY(140, 179);
				$pdf->write(0, 'BY '.$connecting->vessel);
			}

			if($fcl=='FCL') {
				$pdf->SetXY(80, 125);
				$pdf->write(0, 'SHIPPER LOAD, COUNT & SEAL CONTAINER');
				$pdf->SetXY(80, 128);
				$pdf->write(0,',SAID TO CONTAIN :');
			}
			else
			{
				$pdf->SetXY(80, 124);
				$pdf->write(0,'SAID TO CONTAIN :');
			}

			if($attc_discription > 15)
			{
				$pdf->SetXY(90, 133);
				$pdf->MultiCell(40,3,'"SEE ATTACHMENT LIST"');
			}
			else
			{
				$pdf->SetXY(80, 125);
				$pdf->MultiCell(60,3,($data['desc_goods']),0,'L');
			}
			
			$pdf->SetXY(152, 127);
			$pdf->write(0,$this->CI->fungsi->pecah($data['weight'],','),0,0,'R',false,'');
			$pdf->SetXY(165, 127);
			$pdf->write(0, 'KGS');

			if($data['net_weight_tidak_dicetak'] == "FALSE" ) {
				$pdf->SetXY(153, 135);
				$pdf->write(0,'Nett Weight : ');
				$pdf->SetXY(175, 134);
				$pdf->Cell(13,2,$this->CI->fungsi->pecah($data['netto'],','),0,0,'R',false,'');
				$pdf->SetXY(187, 135);
				$pdf->write(0, ' KGS');
			}

            if($data['bl_meas'] != '' ) {
				$pdf->SetXY(177, 127);
				$pdf->write(0,$this->CI->fungsi->pecah4($data['bl_meas'],','),0,0,'R',false,'');
				$pdf->SetXY(189,127);
				$pdf->write(0, 'M');
				$pdf->SetXY(191.5, 126);
				$pdf->SetFont('Arial','',6);
				$pdf->write(0,'3');  
			}

			$pdf->SetFont('Arial','',8);
			$pdf->SetXY(20, 220);
			$pdf->write(0, 'FREIGHT');

			$pdf->SetFont('Arial','',8);
			$pdf->SetXY(10, 220);
			$pdf->Write(0,$data['freights_charges']);


			$freight_payable = $data['freight'];
	  		$pdf->SetXY(33, 220);
	  		$pdf->write(0,$freight_payable);

			if($data['cetak_jml_doc_origi'] == "TRUE") {
				if($cetak=='TRUE')
				{
					$pdf->SetXY(82, 267);
	  				$pdf->write(0,'3(THREE) ORIGINALS');
				} else {
					$pdf->SetXY(82, 268);
	  				$pdf->write(0,'3(THREE) ORIGINALS');
	  			}
			}
			
			if($cetak=='TRUE')
				{
					$pdf->SetXY(140, 258);
	  				$pdf->write(0,strtoupper($this->CI->session->userdata('region_id').', '.$tanggal));
				} else {
					$pdf->SetXY(140, 259);
					$pdf->write(0,strtoupper($this->CI->session->userdata('region_id').', '.$tanggal));
	  			}
			
			$pdf->SetXY(140, 290);
			$pdf->write(0,'PT GATEWAY CONTAINER LINE');
			$pdf->SetXY(135, 281.5);
			$pdf->MultiCell(55,3,strtoupper($as_agent),0,'C');
			
			
	 }
	
	if($code==8) 
	{
		if($cetak=='TRUE') 
		{  
			$pdf->Image(APPPATH.'third_party/img/bplog.jpg',0,0,+200);
			$pdf->SetXY(10, 10);
			$pdf->MultiCell(89, 3, $data['note'],0,1,false);
			$pdf->SetFont('Arial','B',10);
			$pdf->SetXY(152, 10);
			$pdf->write(0, $bl_no);
	
			$pdf->SetFont('Arial','',8);
			$pdf->SetXY(10, 35);		
			$pdf->MultiCell(90,3,$data['consignee_add'],0,'L');
			$pdf->SetXY(10, 60);
			$pdf->MultiCell(90,3,  $data['notify_add'],0,'L');
			$pdf->SetXY(10, 98);
			$pdf->write(0, $port_of->port_of_receive);
		
			$pdf->SetXY(56, 98);
			$pdf->write(0, $port_of->port_of_loading);
			
			$connecting = $this->CI->m_print_out_bl->get_connecting($spd,$data['vessel'].' '.$data['voyage']);
			$q = $this->CI->dbx->get_where('tbl_detail',array('detail_id'=>$spd));
			if($connecting) {
				$pdf->SetXY(10, 88);
				$pdf->write(0, $connecting->vessel);
				if($q->row()->snd_vessel != '' || $q->row()->last_snd_vessel != '')
				{
				$pdf->SetFont('Arial','',6.5);
				$pdf->SetXY(56, 84);
				$pdf->write(0, 'Connecting Vessel');
				$pdf->SetFont('Arial','',8);
				$pdf->SetXY(58, 88);
				$pdf->write(0, $connecting->conn_vessel);
				}
			}

			$pdf->SetXY(10, 106);
			$pdf->write(0, $port_of->port_of_discharge);
			
			

			$pdf->SetXY(56, 106);
			$pdf->write(0, $port_of->port_of_delivery);
		
			
		
			if($attc_marks > 15) {
				$pdf->SetXY(0, 133);
				$pdf->MultiCell(39,3, '"SEE ATTACHMENT LIST"',0,'C',false);
			} else {
			$pdf->SetXY(15, 123);
			$pdf->MultiCell(27,3.5, $data['marking_nos'],0,1,false);    }


                                $pdf->SetXY(15, 185);
			$pdf->write(0, 'CONTAINER / SEAL NO');
			$count = $this->CI->m_print_out_bl->get_count_double($sp);
			if($count->num_rows() > 0 )
                                {
                                 $pdf->Ln(2.5);
                                 foreach($count->result() as $row ) {
					$pdf->Multicell(60,1.5, strtoupper($row->count_no).' / '. strtoupper($row->seal_no).' / '. strtoupper($row->count_size),0,'L',false);
					$pdf->Ln();
				}
			}
	  		else 
	  		{
	  			$pdf->SetXY(15, 189);
				$pdf->MultiCell(60,1.5, $last_container.' / '.$data['type_cont_size']);
	  		}


                      if($fcl=='FCL') {
			                $pdf->SetXY(58, 123);
		                 	$pdf->MultiCell(16,3, strtoupper($data['no_pack_qty']).' '.'X'.' '.strtoupper($data['no_pack_unit']));
		                  	$pdf->SetXY(38, 199);
		                  	$pdf->Write(0, $data['no_pack_qty'].' ( '.$this->CI->fungsi->num_to_words($data['no_pack_qty'],'',0,'').' ) '.$data['no_pack_unit'].' CONTAINER ONLY');	
                               }
                               else
                               {
							$pdf->SetXY(58, 123);
							$pdf->MultiCell(20,3,strtoupper($data['no_pack_qty']).' '. strtoupper($data['no_pack_unit']) ,0,1,false);
							$pdf->SetXY(38, 199);
							$pdf->Write(0, $data['no_pack_qty'].' ( '.$this->CI->fungsi->num_to_words($data['no_pack_qty'],'',0,'').' ) '.strtoupper($data['no_pack_unit']).' ONLY');

     	                         }
                               if($fcl=='FCL')
                                {
				$pdf->SetXY(83, 125);
				$pdf->write(0, 'SHIPPER LOAD, COUNT & SEAL');
				$pdf->SetXY(83, 128);
				$pdf->write(0, $data['no_pack_qty'].' '.'X'.' '.strtoupper($data['no_pack_unit']).' CONTAINER, SAID TO CONTAIN :');
			}
			else
			{
				$pdf->SetXY(83, 125);
				$pdf->write(0,'SAID TO CONTAIN :');
			}

			if($attc_discription > 15)
			{
				$pdf->SetXY(83, 140);
				$pdf->MultiCell(40,3,'"SEE ATTACHMENT LIST"');
			}
			else
			{
			$pdf->SetXY(83, 130);
			$pdf->MultiCell(75,3,$data['desc_goods'],0,1,false);
                                }
			if($data['express_release'] == "TRUE") { 
				$pdf->SetXY(100, 190);
				$pdf->write(0, '" EXPRESS RELEASE "');
			}
	
			if($data['shipped_on_board']== "TRUE") {
				$pdf->SetXY(150, 165);
				$pdf->write(0, 'SHIPPED ON BOARD');
				$pdf->SetXY(150, 168);
				$pdf->write(0, 'FROM '.$data['port_loading']);
				$pdf->SetXY(150, 171);
				$pdf->write(0, 'ON '.strtoupper($tanggal));
				$pdf->SetXY(150, 172.5);
				$pdf->Multicell(40,3, 'BY '.$data['vessel'].' '.$data['voyage'],0,'L',false);
			}
	
  
			$pdf->SetXY(153, 130);
			$pdf->write(0, 'GW     :');			
			$pdf->SetXY(167, 130);
			$pdf->write(0,$this->CI->fungsi->pecah($data['weight'],','),0,0,'R',false,'');
			$pdf->SetXY(180, 130);
			$pdf->write(0, ' KGS');

			if($data['net_weight_tidak_dicetak']== "FALSE" ) {
				$pdf->SetXY(153, 135);
				$pdf->write(0,'NW     :');
				$pdf->SetXY(167, 135);
				$pdf->write(0,$this->CI->fungsi->pecah($data['netto'],','),0,0,'R',false,'');
				$pdf->SetXY(180, 135);
				$pdf->write(0, ' KGS');
			}
			if($data['bl_meas'] != '' )
			{
			$pdf->SetXY(153, 140);
			$pdf->write(0,'MEAS : ');
			$pdf->SetXY(167, 140);
			$pdf->write(0,$this->CI->fungsi->pecah4($data['bl_meas'],','),0,0,'R',false,'');
			$pdf->SetXY(180, 140);
			$pdf->write(0, ' M');
			$pdf->SetXY(183, 139);
			$pdf->SetFont('Arial','',6);		
			$pdf->write(0, ' 3');
			}	
	
			$pdf->SetFont('Arial','',8);
			$freight_payable = $data['freight'];
	
			if($freight_payable == "PREPAID") {
	  			$pdf->SetXY(10, 230);
	  			$pdf->write(0,'ORIGIN');
	  			$pdf->SetXY(128, 220);
	  			$pdf->write(0,'PREPAID');

			} else {
	  			$pdf->SetXY(10, 230);
	  			$pdf->write(0,'DESTINATION');
	  			$pdf->SetXY(160, 220);
	  			$pdf->write(0,'COLLECT');
			}
	
			if($data['cetak_jml_doc_origi'] == "TRUE") { 
	  			$pdf->SetXY(150, 106);
	  			$pdf->Write(0,'3(THREE) ORIGINAL',0,'','');
			}
	
			$pdf->SetXY(120, 230);
			$pdf->write(0,strtoupper($this->CI->session->userdata('region_id').', '.$tanggal));


			$agent = $this->CI->m_print_out_bl->getAgentByCode($data['agent_code'],0,'L',false);

			if($agent != 0 ) {
				$pdf->SetXY(10,240);
				$pdf->write(0,$agent['agent_company_name']);
				$pdf->SetXY(10,242);
				$pdf->MultiCell(100,3.5, $agent['agent_company_address'],0,'L',false);
			}
			$pdf->SetXY(130, 265);
			$pdf->write(0,'PT. BLUE PACIFIC LOGISTICS');
		}
		else
		{
			$pdf->SetXY(10, 12);
			$pdf->MultiCell(89, 3, $data['note'],0,1,false);
			$pdf->SetFont('Arial','B',10);
			$pdf->SetXY(158, 13);
			$pdf->write(0, $bl_no);
	
			$pdf->SetFont('Arial','',8);
			$pdf->SetXY(10, 40);		
			$pdf->MultiCell(90,3,$data['consignee_add'],0,'L');
			$pdf->SetXY(10, 68);
			$pdf->MultiCell(90,3,  $data['notify_add'],0,'L');
			$pdf->SetXY(10, 106);
			$pdf->write(0, $port_of->port_of_receive);
		
			$pdf->SetXY(56, 106);
			$pdf->write(0, $port_of->port_of_loading);
			
			$connecting = $this->CI->m_print_out_bl->get_connecting($spd,$data['vessel'].'/ '.$data['voyage']);
			$q = $this->CI->dbx->get_where('tbl_detail',array('detail_id'=>$spd));
			if($connecting) {
				$pdf->SetXY(10, 97);
				$pdf->write(0, $connecting->vessel);
				if($q->row()->snd_vessel != '' || $q->row()->last_snd_vessel != '')
				{
				$pdf->SetFont('Arial','',6.5);
				$pdf->SetXY(56, 88);
				$pdf->write(0, 'Connecting Vessel');
				$pdf->SetFont('Arial','',8);
				$pdf->SetXY(58, 92);
				$pdf->write(0, $connecting->conn_vessel);
				}
			}

			$pdf->SetXY(10, 115);
			$pdf->write(0, $port_of->port_of_discharge);


			$pdf->SetXY(56, 115);
			$pdf->write(0, $port_of->port_of_delivery);
		
			if($attc_marks > 15) {
				$pdf->SetXY(0, 137);
				$pdf->MultiCell(39,3, '"SEE ATTACHMENT LIST"',0,'C',false);
			} else {
			$pdf->SetXY(15, 127);
			$pdf->MultiCell(27,3.5, $data['marking_nos'],0,1,false);    }


                                $pdf->SetXY(15, 190);
			$pdf->write(0, 'CONTAINER / SEAL NO');
			$count = $this->CI->m_print_out_bl->get_count_double($sp);
			if($count->num_rows() > 0 )
                                {
                                 $pdf->Ln(2.5);
                                 foreach($count->result() as $row ) {
					$pdf->Multicell(60,1.5, strtoupper($row->count_no).' / '. strtoupper($row->seal_no).' / '. strtoupper($row->count_size),0,'L',false);
					$pdf->Ln();
				}
			}
	  		else 
	  		{
	  			$pdf->SetXY(15, 193);
				$pdf->MultiCell(60,1.5, $last_container.' / '.$data['type_cont_size']);
	  		}


                      if($fcl=='FCL') {
			                $pdf->SetXY(58, 127);
		                 	$pdf->MultiCell(16,3, strtoupper($data['no_pack_qty']).' '.'X'.' '.strtoupper($data['no_pack_unit']));
		                  	$pdf->SetXY(58, 214);
		                  	$pdf->Write(0, $data['no_pack_qty'].' ( '.$this->CI->fungsi->num_to_words($data['no_pack_qty'],'',0,'').' ) '.$data['no_pack_unit'].' CONTAINER ONLY');	
                               }
                               else
                               {
							$pdf->SetXY(58, 127);
							$pdf->MultiCell(20,3,strtoupper($data['no_pack_qty']).' '. strtoupper($data['no_pack_unit']) ,0,1,false);
							$pdf->SetXY(58, 214);
							$pdf->Write(0, $data['no_pack_qty'].' ( '.$this->CI->fungsi->num_to_words($data['no_pack_qty'],'',0,'').' ) '.strtoupper($data['no_pack_unit']).' ONLY');

     	                         }
                               if($fcl=='FCL')
                                {
				$pdf->SetXY(83, 129);
				$pdf->write(0, 'SHIPPER LOAD, COUNT & SEAL');
				$pdf->SetXY(83, 132);
				$pdf->write(0, $data['no_pack_qty'].' '.'X'.' '.strtoupper($data['no_pack_unit']).' CONTAINER, SAID TO CONTAIN :');
			}
			else
			{
				$pdf->SetXY(83, 127);
				$pdf->write(0,'SAID TO CONTAIN :');
			}

			if($attc_discription > 15)
			{
				$pdf->SetXY(83, 140);
				$pdf->MultiCell(40,3,'"SEE ATTACHMENT LIST"');
			}
			else
			{
			$pdf->SetXY(83, 135);
			$pdf->MultiCell(75,3,$data['desc_goods'],0,1,false);
                                }
			if($data['express_release'] == "TRUE") { 
				$pdf->SetXY(100, 190);
				$pdf->write(0, '" EXPRESS RELEASE "');
			}
	
			if($data['shipped_on_board']== "TRUE") {
				$pdf->SetXY(150, 165);
				$pdf->write(0, 'SHIPPED ON BOARD');
				$pdf->SetXY(150, 168);
				$pdf->write(0, 'FROM '.$data['port_loading']);
				$pdf->SetXY(150, 171);
				$pdf->write(0, 'ON '.strtoupper($tanggal));
				$pdf->SetXY(150, 172.5);
				$pdf->Multicell(40,3, 'BY '.$data['vessel'].' '.$data['voyage'],0,'L',false);
			}
	
  
			$pdf->SetXY(153, 130);
			$pdf->write(0, 'GW     :');			
			$pdf->SetXY(167, 130);
			$pdf->write(0,$this->CI->fungsi->pecah($data['weight'],','),0,0,'R',false,'');
			$pdf->SetXY(180, 130);
			$pdf->write(0, ' KGS');

			if($data['net_weight_tidak_dicetak']== "FALSE" ) {
				$pdf->SetXY(153, 135);
				$pdf->write(0,'NW     :');
				$pdf->SetXY(167, 135);
				$pdf->write(0,$this->CI->fungsi->pecah($data['netto'],','),0,0,'R',false,'');
				$pdf->SetXY(180, 135);
				$pdf->write(0, ' KGS');
			}
			if($data['bl_meas'] != '' )
			{
			$pdf->SetXY(153, 140);
			$pdf->write(0,'MEAS : ');
			$pdf->SetXY(167, 140);
			$pdf->write(0,$this->CI->fungsi->pecah4($data['bl_meas'],','),0,0,'R',false,'');
			$pdf->SetXY(180, 140);
			$pdf->write(0, ' M');
			$pdf->SetXY(183, 139);
			$pdf->SetFont('Arial','',6);		
			$pdf->write(0, ' 3');
			}	
	
			$pdf->SetFont('Arial','',8);
			$freight_payable = $data['freight'];
	
			if($freight_payable == "PREPAID") {
	  			$pdf->SetXY(20, 247);
	  			$pdf->write(0,'ORIGIN');
	  			$pdf->SetXY(130, 235);
	  			$pdf->write(0,'PREPAID');

			} else {
	  			$pdf->SetXY(10, 247);
	  			$pdf->write(0,'DESTINATION');
	  			$pdf->SetXY(160, 235);
	  			$pdf->write(0,'COLLECT');
			}
	
			if($data['cetak_jml_doc_origi'] == "TRUE") { 
	  			$pdf->SetXY(155, 115);
	  			$pdf->Write(0,'3(THREE) ORIGINAL',0,'','');
			}
	
			$pdf->SetXY(120, 247);
			$pdf->write(0,strtoupper($this->CI->session->userdata('region_id').', '.$tanggal));


			$agent = $this->CI->m_print_out_bl->getAgentByCode($data['agent_code'],0,'L',false);

			if($agent != 0 ) {
				$pdf->SetXY(10,260);
				$pdf->write(0,$agent['agent_company_name']);
				$pdf->SetXY(10,262);
				$pdf->MultiCell(100,3.5, $agent['agent_company_address'],0,'L',false);
			}
			$pdf->SetXY(130, 276);
			$pdf->write(0,'PT. BLUE PACIFIC LOGISTICS');
						}
	}

	if($code==9)
	{
		if($cetak=='TRUE')
		{
			$pdf->Image(APPPATH.'third_party/img/osline.jpg',0,0,+200);
			$pdf->SetFont('Arial','',7);
			$pdf->SetXY(10, 18);
			$pdf->MultiCell(89, 3, $data['note'],0,'',false);
			$pdf->SetXY(149, 20);
			$pdf->SetFont('Arial','B',10);
			$pdf->write(0, str_replace('%20',' ',$bl_no));
			$pdf->SetFont('Arial','',7);
			$pdf->SetXY(10, 45);
			$pdf->MultiCell(90,3,($data['consignee_add']),0,'L');
			$pdf->SetXY(10, 71);
			$pdf->SetFont('Arial','',7);
			$pdf->MultiCell(90,3, ($data['notify_add']),0,'L');

			$pdf->SetXY(10, 99.5);
			$pdf->write(0, $port_of->port_of_receive);
			$pdf->SetXY(52, 99.5);
			$pdf->write(0, $port_of->port_of_loading);

			$connecting = $this->CI->m_print_out_bl->get_connecting($spd   ,$data['vessel'].' '.$data['voyage']);
			$q = $this->CI->dbx->get_where('tbl_detail',array('detail_id'=>$spd));
			if($connecting) {
				$pdf->SetXY(10, 109);
				$pdf->write(0, $connecting->vessel);
				$pdf->SetXY(52, 105);
				if($q->row()->snd_vessel != '' || $q->row()->last_snd_vessel != '')
				{
				$pdf->write(0, 'Connecting Vessel');
				$pdf->SetXY(52, 109);
				$pdf->write(0, strtoupper($connecting->conn_vessel));  }
			}

			$pdf->SetXY(101, 109);
			$pdf->write(0, $port_of->port_of_discharge);
			$pdf->SetXY(149, 109);
			$pdf->write(0, $port_of->port_of_delivery);

			$agent = $this->CI->m_print_out_bl->getAgentByCode($data['agent_code'],0,'L',false);

			if($agent != 0 ) {
            	if ($agent['agent_company_remark'] != '')
               	{
					$pdf->SetXY(103, 74.5);
					$pdf->MULTICELL(85,2.5, $agent['agent_company_remark']);
              		$pdf->SetXY(103, 80.5);
					$pdf->write(0, $agent['agent_company_name']);
					$pdf->SetXY(103, 82);
					$pdf->MultiCell(85,2.5, $agent['agent_company_address'],0,'L',false);
				}
				else
				{
              		$pdf->SetXY(103, 73);
					$pdf->write(0, $agent['agent_company_name']);
					$pdf->SetXY(103, 75);
					$pdf->MultiCell(85,3, $agent['agent_company_address'],0,'L',false);
            	}
			}

			if($attc_marks > 18) {
				$pdf->SetXY(10, 125);
				$pdf->MultiCell(39,3, '"SEE ATTACHMENT LIST"',0,'C',false);
			} else {
				$pdf->SetXY(10, 125);
				$pdf->MultiCell(39,3,($data['marking_nos']),0,'L',false);
			}


			
			////double countener /////

			$count = $this->CI->m_print_out_bl->get_count_double($sp);
			if($count->num_rows() > 0 ) {
              $pdf->SetXY(10, 167);
			  $pdf->write(0, 'CONTAINER / SEAL NO');
			  $pdf->Ln(2.5);
				foreach($count->result() as $row ) {
					$pdf->MultiCell(55,1.5, strtoupper($row->count_no).' / '. strtoupper($row->seal_no).' / '. strtoupper($row->count_size),0,'L',false);
					$pdf->Ln();
				}
			}
	  		else 
	  		{        $pdf->SetXY(10, 177);
			         $pdf->write(0, 'CONTAINER / SEAL NO');
	  			$pdf->SetXY(10, 179);
				$pdf->MultiCell(45, 3, $last_container.' / '.$data['type_cont_size']);
	  		}
	  		
	  	   if($fcl=='FCL') {

             	$pdf->SetXY(53, 125);
             	$dbl = $this->CI->afg_m_stuffing_plan->get_double($sp);
             	if($dbl->num_rows() > 0 ) {
             		$pdf->Ln(1);
					foreach($dbl->result() as $row ) {
						$pdf->MultiCell(60,3, $row->count_num.' '.'X'.' '.$row->count_type,0,"R");
						$pdf->Ln(0.5);
					}

					$pdf->SetXY(53, 186);
                  foreach($dbl->result() as $row ) {
				   $pdf->Write(0,  $row->count_num.' ( '.$this->CI->fungsi->num_to_words( $row->count_num,'',0,'').' ) '.' '.'X'.' '.$row->count_type.' ');
                  }
			 } else {  
			 		$pdf->SetXY(53, 125);
                  $pdf->MultiCell(16,3, $data['no_pack_qty'].' '.'X'.' '.$data['no_pack_unit']);
					$pdf->SetXY(53, 194);
				   $pdf->MultiCell(120,3, $data['no_pack_qty'].' ( '.$this->CI->fungsi->num_to_words($data['no_pack_qty'],'',0,'').' ) '.' '.'X'.' '.$data['no_pack_unit'].' ONLY');
       				 }
           	}
     			else
           	{
            	$pdf->SetXY(53, 125);
				$pdf->MultiCell(20,3, $data['no_pack_qty'].' '.strtoupper($data['no_pack_unit']));
				$pdf->SetXY(53, 194);
				$pdf->MultiCell(120,3, $data['no_pack_qty'].' ( '.$this->CI->fungsi->num_to_words($data['no_pack_qty'],'',0,'').' ) '.strtoupper($data['no_pack_unit']).' ONLY');
			}
			
			if($data['express_release'] == "TRUE") 
			{
				$pdf->SetXY(94, 182);
				$pdf->write(0, '" EXPRESS RELEASE "');
			}
			
			if($data['shipped_on_board'] == "TRUE")
			{
				$pdf->SetXY(140, 160);
				$pdf->write(0, 'SHIPPED ON BOARD');
				$pdf->SetXY(140, 163);
				$pdf->write(0, 'FROM '.$port_of->port_of_loading);
				$pdf->SetXY(140, 166);
				$pdf->write(0, 'ON '.strtoupper($tanggal));
				$pdf->SetXY(140, 169);
				$pdf->write(0, 'BY '.$connecting->vessel);
			}

			if($fcl=='FCL') {
				$pdf->SetXY(75, 125);
				$pdf->write(0, 'SHIPPER LOAD, COUNT & SEAL CONTAINER,');
				$pdf->SetXY(75, 128);
				$pdf->write(0, 'SAID TO CONTAIN :');
			}
			else
			{
				$pdf->SetXY(75, 127);
				$pdf->write(0,'SAID TO CONTAIN :');
			}

			if($attc_discription > 15)
			{
				$pdf->SetXY(90, 133);
				$pdf->MultiCell(40,3,'"SEE ATTACHMENT LIST"');
			}
			else
			{
				$pdf->SetXY(75, 129);
				$pdf->MultiCell(60,3,($data['desc_goods']),0,'L');
			}
			


			$pdf->SetXY(153, 130);
			$pdf->write(0, 'GW :');
			$pdf->SetXY(177, 129);
			$pdf->Cell(13,2,$this->CI->fungsi->pecah3($data['weight'],','),0,0,'R',false,'');
			$pdf->SetXY(188, 130);
			$pdf->write(0, ' KGS');

			if($data['net_weight_tidak_dicetak'] == "FALSE" ) {
				$pdf->SetXY(153, 135);
				$pdf->write(0,'NW : ');
				$pdf->SetXY(177, 134);
				//$pdf->Cell(13,2,number_format($data['netto'],3,'.'.','),0,0,'R',false,'');
				//$pdf->Cell(13,2,$data['netto'],0,0,'R',false,'');
				$pdf->Cell(13,2,$this->CI->fungsi->pecah3($data['netto'],','),0,0,'R',false,'');
				$pdf->SetXY(188, 135);
				$pdf->write(0, ' KGS');
			}

        if($data['bl_meas'] != '' ) {
			$pdf->SetXY(153, 140);
			$pdf->write(0,'MEAS : ');
			$pdf->SetXY(177, 139);
			$pdf->Cell(13,2,$this->CI->fungsi->pecah4($data['bl_meas'],','),0,0,'R',false,'');
			$pdf->SetXY(188, 140);
			$pdf->write(0, ' M');
			$pdf->SetXY(190, 139);
			$pdf->SetFont('Arial','',6);
			$pdf->write(0, ' 3');  }

			$pdf->SetFont('Arial','',8);
			$pdf->SetXY(20, 210);
			$pdf->write(0, 'FREIGHT');
			$q = $this->CI->dbx->get_where('tbl_detail',array('detail_id'=>$spd));
			if($q->row()->payable == 'AS ARRANGED')
				{
				$pdf->SetXY(16, 203);
               $pdf->write(0, 'AS ARRANGED');
				}

			$pdf->SetFont('Arial','',8);
			$pdf->SetXY(10, 208);
			$pdf->MultiCell(40,3, $data['freights_charges']);

			$freight_payable = $data['freight'];
			if($freight_payable == "PREPAID") {
	  			$pdf->SetXY(55, 208);
	  			$pdf->MultiCell(40,3,$freight_payable);
			} else {
	  			$pdf->SetXY(83, 208);
	  			$pdf->MultiCell(40,3,$freight_payable);
			}

			if($data['cetak_jml_doc_origi'] == "TRUE") {
	  			$pdf->SetXY(108, 208);
	  			$pdf->MultiCell(30,3,'3(THREE) ORIGINAL',0,'','');
			}

			$pdf->SetXY(132, 250);
			if($this->CI->session->userdata('region_id')=='Osline'){
			$region_cetak="JAKARTA";	
			}else if($this->CI->session->userdata('region_id')=='OSLSUB'){
			$region_cetak="SURABAYA";	
			}else if($this->CI->session->userdata('region_id')=='OSLSRG'){
			$region_cetak="SEMARANG";	
			}else if($this->CI->session->userdata('region_id')=='OSLBDG'){
			$region_cetak="BANDUNG";
			}else{
			$region_cetak="JAKARTA";	
			}
			
			$pdf->MultiCell(73,1,strtoupper($region_cetak.' '.$tanggal),'','',false);

			$pdf->SetXY(110, 269);
			$pdf->write(0,'OCEAN SKY LINE');
			$pdf->SetXY(110, 272);
			$pdf->MultiCell(90,3,strtoupper($as_agent),0,'L');
		}
		else
		{
			$pdf->SetAutoPageBreak(0, 0);
			$pdf->SetMargins(10,5,0,10);
			$pdf->SetXY(0, 16);
			$pdf->SetFont('Verdana','',10);
			$pdf->MultiCell(130, 5, $data['note'],0,'',false);
			$pdf->SetXY(215, 16);
			$pdf->SetFont('Verdana','B',15);
			$pdf->write(0, str_replace('%20',' ',$bl_no));
			$pdf->SetFont('Verdana','',10);
			$pdf->SetXY(0, 56);
			$pdf->MultiCell(130,5,($data['consignee_add']),0,'L');
			$pdf->SetXY(0, 98);
			$pdf->MultiCell(120,5, ($data['notify_add']),0,'L');

			$pdf->SetXY(0, 141);
			$pdf->write(0, $port_of->port_of_receive);
			$pdf->SetXY(63, 141);
			$pdf->write(0, $port_of->port_of_loading);

			$connecting = $this->CI->m_print_out_bl->get_connecting($spd,$data['vessel'].' '.$data['voyage']);
			$q = $this->CI->dbx->get_where('tbl_detail',array('detail_id'=>$spd));
			if($connecting )
                                {
               $pdf->SetFont('Verdana','',10.5);
				$pdf->SetXY(0, 156);
				$pdf->write(0, $connecting->vessel);
				$pdf->SetXY(62, 151);
				if($q->row()->snd_vessel != '' || $q->row()->last_snd_vessel != '')
				{
				$pdf->write(0, 'Connecting Vessel');
				$pdf->SetXY(62, 156);
				$pdf->write(0, $connecting->conn_vessel);
				}
			}
                                 
			$pdf->SetXY(141, 156);
			$pdf->write(0, $port_of->port_of_discharge);
			$pdf->SetXY(217, 156);
			$pdf->write(0, $port_of->port_of_delivery);

			$agent = $this->CI->m_print_out_bl->getAgentByCode($data['agent_code']);
			
                                 	if($agent != 0 ) {
                                  if ($agent['agent_company_remark'] != '')
                                  {
				$pdf->SetXY(145, 94);
				$pdf->MULTICELL(115,4.5, $agent['agent_company_remark'],0,'L',false);
                                       $pdf->SetXY(145, 105);
				$pdf->write(0, $agent['agent_company_name'],0,'L',false);
				$pdf->SetXY(145, 107);
				$pdf->MultiCell(110,5, $agent['agent_company_address'],0,'L',false);
				                             }
				                             else
				                             {
                                        $pdf->SetXY(145, 103);
				$pdf->write(0, $agent['agent_company_name']);
				$pdf->SetXY(145, 105);
				$pdf->MultiCell(110,5, $agent['agent_company_address'],0,'L',false);
                                  }
			}
			$pdf->SetFont('Verdana','',10);
			if($attc_marks > 18) {
				$pdf->SetXY(0, 185);
				$pdf->MultiCell(45,5, '"SEE ATTACHMENT LIST"',0,'C',false);
			} else {
				$pdf->SetXY(0, 185);
				$pdf->SetFont('Verdana','',10);
				$pdf->MultiCell(60,5,($data['marking_nos']),0,'L',false);
			}
					  		
	  		////double countener /////

			$count = $this->CI->m_print_out_bl->get_count_double($sp);
			if($count->num_rows() > 0 ) {
                     $pdf->SetXY(0, 254);
						$pdf->write(0, 'CONTAINER / SEAL NO');
	  					$pdf->SetXY(0, 255);
	  					$pdf->SetMargins(0, 0);
						$pdf->Ln(2.5);
						foreach($count->result() as $row ) {
						$pdf->MultiCell(85,2.5, strtoupper($row->count_no).' / '. strtoupper($row->seal_no).' / '. strtoupper($row->count_size),0,'L',false);
						$pdf->Ln();
				}
		}
	  		else 
	  		{        $pdf->SetXY(0, 274);
	  					$pdf->SetFont('Verdana','',11);
						$pdf->write(0, 'CONTAINER / SEAL NO');
						$pdf->SetXY(0, 278);
						$pdf->MultiCell(85, 5, $last_container.' / '.$data['type_cont_size']);
	  				
	  		}
	  		
			
	  		////double countener /////
	  		
			
			
           if($fcl=='FCL') {

                  $pdf->SetXY(60, 185);
             	   $dbl = $this->CI->afg_m_stuffing_plan->get_double($sp);
             	   if($dbl->num_rows() > 0 ) {
             		$pdf->Ln(1);
					foreach($dbl->result() as $row ) {
						$pdf->MultiCell(80,3, $row->count_num.' '.'X'.' '.$row->count_type,0,"R");
						$pdf->Ln(1);
					}

					$pdf->SetXY(70, 286);
                  foreach($dbl->result() as $row ) {
				   $pdf->Write(0,  $row->count_num.' ( '.$this->CI->fungsi->num_to_words( $row->count_num,'',0,'').' ) '.' '.'X'.' '.$row->count_type.' ');
                                                }
				   } else    {
			                $pdf->SetXY(60, 185);
		                 	$pdf->MultiCell(25,3, $data['no_pack_qty'].' '.'X'.' '.$data['no_pack_unit']);
		                  	$pdf->SetXY(60, 286);

			           $pdf->MultiCell(120,3, $data['no_pack_qty'].' ( '.$this->CI->fungsi->num_to_words($data['no_pack_qty'],'',0,'').' ) '.' '.'X'.' '.$data['no_pack_unit'].' ONLY');
                               }
                                }
                               else
                               {
			$pdf->SetXY(60, 183);
			$pdf->MultiCell(30,5, $data['no_pack_qty'].' '.strtoupper($data['no_pack_unit']));

			$pdf->SetXY(63, 286);
			
				$pdf->MultiCell(130,3, $data['no_pack_qty'].'( '.$this->CI->fungsi->num_to_words($data['no_pack_qty'],'',0,'').')'.''.strtoupper($data['no_pack_unit']).' ONLY');

                                }
			if($data['express_release'] == "TRUE") {
				$pdf->SetFont('Verdana','B',13);
				$pdf->SetXY(125, 275);
				$pdf->write(0, '" EXPRESS RELEASE "');
			}
			$pdf->SetFont('Verdana','',11);
			if($data['shipped_on_board'] == "TRUE") {
				$pdf->SetXY(200, 249.5);
				$pdf->write(0, 'SHIPPED ON BOARD');
				$pdf->SetXY(200, 254);
				$pdf->write(0, 'FROM '.$port_of->port_of_loading);
				$pdf->SetXY(200, 259);
				$pdf->write(0, 'ON '.strtoupper($tanggal));
				$pdf->SetXY(200, 264);
				$pdf->write(0, 'BY '.$connecting->vessel);
			}

			if($fcl=='FCL') {
				$pdf->SetXY(90, 185);
				$pdf->write(0, 'SHIPPER LOAD, COUNT & SEAL CONTAINER,');
				$pdf->SetXY(90, 190);
				$pdf->write(0, 'SAID TO CONTAIN :');
			}
			else
			{
				$pdf->SetXY(95, 185);
				$pdf->write(0,'SAID TO CONTAIN :');
			}

			if($attc_discription > 19)
			{
				$pdf->SetXY(110, 235);
				$pdf->MultiCell(60,5,'"SEE ATTACHMENT LIST"');
			}
			else
			{
				$pdf->SetXY(95, 195);
				$pdf->SetFont('Verdana','',10);
				$pdf->MultiCell(110,5,($data['desc_goods']),0,'L');
			}
			$pdf->SetFont('Verdana','',11);
			$pdf->SetXY(215, 185);
			$pdf->write(0, 'GW :');
			$pdf->SetXY(260, 184);
			$pdf->Cell(13,2,$this->CI->fungsi->pecah3($data['weight'],','),0,0,'R',false,'');
			$pdf->SetXY(270, 185);
			$pdf->write(0, ' KGS');

			if($data['net_weight_tidak_dicetak'] == "FALSE" ) {
				$pdf->SetXY(215, 195);
				$pdf->write(0,'NW : ');
				$pdf->SetXY(260, 194);
				$pdf->Cell(13,2,$this->CI->fungsi->pecah3($data['netto'],','),0,0,'R',false,'');
			
				$pdf->SetXY(270, 195);
				$pdf->write(0, ' KGS');
			}
                                if($data['bl_meas'] != '' ) {
			$pdf->SetXY(215, 205);
			$pdf->write(0,'MEAS : ');
			$pdf->SetXY(260, 204);
			$pdf->Cell(13,2,$this->CI->fungsi->pecah4($data['bl_meas'],','),0,0,'R',false,'');
			$pdf->SetXY(270, 205);
			$pdf->write(0, ' M');
			$pdf->SetXY(273, 203);
			$pdf->SetFont('Arial','',10);
			$pdf->write(0, ' 3');  }

			$pdf->SetFont('Verdana','',10);
			$pdf->SetXY(10, 310);
			$pdf->write(0, 'FREIGHT');
            $q = $this->CI->dbx->get_where('tbl_detail',array('detail_id'=>$spd));
			if($q->row()->payable == "AS ARRANGED")
				{
				$pdf->SetXY(5,319);
               $pdf->write(0, 'AS ARRANGED');
				}


			$freight_payable = $data['freight'];
			if($freight_payable == "PREPAID") {
	  			$pdf->SetXY(70, 310);
	  			$pdf->SetFont('Arial','',12);
	  			$pdf->Write(0,$freight_payable);
			} else {
	  			$pdf->SetXY(110, 310);
	  			$pdf->SetFont('Arial','',12);
	  			$pdf->Write(0,$freight_payable);
			}

			if($data['cetak_jml_doc_origi'] == "TRUE") {
	  			$pdf->SetXY(158, 310);
	  			$pdf->SetFont('Arial','',12);
	  			$pdf->Write(0,'3(THREE) ORIGINAL');
			}

			$pdf->SetXY(189, 370);
			$pdf->SetFont('Arial','',12);
			if($this->CI->session->userdata('region_id')=='Osline'){
			$region_cetak="JAKARTA";	
			}else if($this->CI->session->userdata('region_id')=='OSLSUB'){
			$region_cetak="SURABAYA";	
			}else if($this->CI->session->userdata('region_id')=='OSLSRG'){
			$region_cetak="SEMARANG";
			}else if($this->CI->session->userdata('region_id')=='OSLBDG'){
			$region_cetak="BANDUNG";				
			}else{
			$region_cetak="JAKARTA";	
			}
			$pdf->MultiCell(73,1,strtoupper($region_cetak.' '.$tanggal),'','',false);

			$pdf->SetXY(160, 398);
			$pdf->SetFont('Verdana','',10);
			$pdf->write(0,'OCEAN SKY LINE');
			$pdf->SetXY(160, 403);
			$pdf->MultiCell(70,5,strtoupper($as_agent),0,'L');
		}
	}
	if($code==10)
	{
		if($cetak=='TRUE')
		{
			$pdf->Image(APPPATH.'third_party/img/winfast-bl.jpeg',0,0,+200);
			$pdf->SetFont('Arial','',7);
			$pdf->SetXY(10, 12);
			$pdf->MultiCell(89, 3, $data['note'],0,'',false);
			$pdf->SetXY(150, 12);
			$pdf->SetFont('Arial','B',10);
			$pdf->write(0, str_replace('%20',' ',$bl_no));
			$pdf->SetFont('Arial','',7);
			$pdf->SetXY(10, 38);
			$pdf->MultiCell(90,3,($data['consignee_add']),0,'L');
			$pdf->SetXY(10, 65);
			$pdf->SetFont('Arial','',7);
			$pdf->MultiCell(90,3, ($data['notify_add']),0,'L');

			$pdf->SetXY(10, 93);
			$pdf->write(0, $port_of->port_of_receive);
			$pdf->SetXY(52, 93);
			$pdf->write(0, $port_of->port_of_loading);

			$connecting = $this->CI->m_print_out_bl->get_connecting($spd   ,$data['vessel'].' '.$data['voyage']);
			$q = $this->CI->dbx->get_where('tbl_detail',array('detail_id'=>$spd));
			if($connecting) {
				$pdf->SetXY(10, 103);
				$pdf->write(0, $connecting->vessel);
				$pdf->SetXY(52, 103);
				if($q->row()->snd_vessel != '' || $q->row()->last_snd_vessel != '')
				{
				$pdf->write(0, 'Connecting Vessel');
				$pdf->SetXY(52, 103);
				$pdf->write(0, strtoupper($connecting->conn_vessel));  }
			}

			$pdf->SetXY(101, 103);
			$pdf->write(0, $port_of->port_of_discharge);
			$pdf->SetXY(149, 103);
			$pdf->write(0, $port_of->port_of_delivery);

			$agent = $this->CI->m_print_out_bl->getAgentByCode($data['agent_code'],0,'L',false);

			if($agent != 0 ) {
                                  if ($agent['agent_company_remark'] != '')
                                  {
				$pdf->SetXY(103, 74.5);
				$pdf->MULTICELL(85,2.5, $agent['agent_company_remark']);
                                       $pdf->SetXY(103, 80.5);
				$pdf->write(0, $agent['agent_company_name']);
				$pdf->SetXY(103, 65);
				$pdf->MultiCell(85,2.5, $agent['agent_company_address'],0,'L',false);
				                             }
				                             else
				                             {
                   $pdf->SetXY(103, 68);
				$pdf->write(0, $agent['agent_company_name']);
				$pdf->SetXY(103, 70);
				$pdf->MultiCell(85,3, $agent['agent_company_address'],0,'L',false);
                                  }
			}

			if($attc_marks > 18) {
				$pdf->SetXY(5, 123);
				$pdf->MultiCell(39,3, '"SEE ATTACHMENT LIST"',0,'C',false);
			} else {
				$pdf->SetXY(5, 123);
				$pdf->MultiCell(39,3,($data['marking_nos']),0,'L',false);
			}


			
			////double countener /////

			$count = $this->CI->m_print_out_bl->get_count_double($sp);
			if($count->num_rows() > 0 ) {
              $pdf->SetXY(5, 167);
			  $pdf->write(0, 'CONTAINER / SEAL NO');
			  $pdf->Ln(2.5);
				foreach($count->result() as $row ) {
					$pdf->MultiCell(60,1.5, strtoupper($row->count_no).' / '. strtoupper($row->seal_no).' / '. strtoupper($row->count_size),0,'L',false);
					$pdf->Ln();
				}
			}
	  		else if ($last_container != '')
	  		{  
	  			$pdf->SetXY(5, 177);
			   $pdf->write(0, 'CONTAINER / SEAL NO');
	  			$pdf->SetXY(5, 179);
				$pdf->MultiCell(45, 3, $last_container.' / '.$data['type_cont_size']);
	  		}
	  		
	  	   if($fcl=='FCL') {

             	$pdf->SetXY(50, 123);
             	$dbl = $this->CI->afg_m_stuffing_plan->get_double($sp);
             	if($dbl->num_rows() > 4 ) {
             		$pdf->Ln(1);
					foreach($dbl->result() as $row ) {
						$pdf->MultiCell(60,3, $row->count_num.' '.'X'.' '.$row->count_type,0,"R");
						$pdf->Ln(0.5);
					}

					$pdf->SetXY(50, 186);
				foreach($dbl->result() as $row ) {
				   $pdf->Write(0,  $row->count_num.' ( '.$this->CI->fungsi->num_to_words( $row->count_num,'',0,'').' ) '.' '.'X'.' '.$row->count_type.' ');
                  }
			 } else {  
			 		$pdf->SetXY(50, 123);
					$pdf->MultiCell(16,3, $data['no_pack_qty'].' '.'X'.' '.strtoupper($data['no_pack_unit']));
					$pdf->SetXY(53, 189);
				   $pdf->MultiCell(120,3, $data['no_pack_qty'].' ( '.$this->CI->fungsi->num_to_words($data['no_pack_qty'],'',0,'').' ) '.' '.'X'.' '.strtoupper($data['no_pack_unit']).' ONLY');
       				 }
           	}
     			else
           	{
            	$pdf->SetXY(50, 123);
				$pdf->MultiCell(20,3, $data['no_pack_qty'].' '.strtoupper($data['no_pack_unit']));
				$pdf->SetXY(50, 189);
				$pdf->MultiCell(120,3, $data['no_pack_qty'].' ( '.$this->CI->fungsi->num_to_words($data['no_pack_qty'],'',0,'').' ) '.strtoupper($data['no_pack_unit']).' ONLY');
			}
			
			if($data['express_release'] == "TRUE") 
			{
				$pdf->SetXY(94, 182);
				$pdf->write(0, '" EXPRESS RELEASE "');
			}
			
			if($data['shipped_on_board'] == "TRUE")
			{
				$pdf->SetXY(140, 160);
				$pdf->write(0, 'SHIPPED ON BOARD');
				$pdf->SetXY(140, 163);
				$pdf->write(0, 'FROM '.$port_of->port_of_loading);
				$pdf->SetXY(140, 166);
				$pdf->write(0, 'ON '.strtoupper($tanggal));
				$pdf->SetXY(140, 169);
				$pdf->write(0, 'BY '.$connecting->vessel);
			}

			if($fcl=='FCL') {
				$pdf->SetXY(75, 125);
				$pdf->write(0, 'SHIPPER LOAD, COUNT & SEAL CONTAINER,');
				$pdf->SetXY(75, 128);
				$pdf->write(0, 'SAID TO CONTAIN :');
			}
			else
			{
				$pdf->SetXY(75, 125);
				$pdf->write(0,'SAID TO CONTAIN :');
			}

			if($attc_discription > 15)
			{
				$pdf->SetXY(90, 133);
				$pdf->MultiCell(40,3,'"SEE ATTACHMENT LIST"');
			}
			else
			{
				$pdf->SetXY(75, 129);
				$pdf->MultiCell(60,3,($data['desc_goods']),0,'L');
			}
			


			$pdf->SetXY(153, 130);
			$pdf->write(0, 'GW :');
			$pdf->SetXY(175, 129);
			$pdf->Cell(13,2,$this->CI->fungsi->pecah3($data['weight'],','),0,0,'R',false,'');
			$pdf->SetXY(186, 130);
			$pdf->write(0, ' KGS');

			if($data['net_weight_tidak_dicetak'] == "FALSE" ) {
				$pdf->SetXY(153, 135);
				$pdf->write(0,'NW : ');
				$pdf->SetXY(175, 134);
				//$pdf->Cell(13,2,number_format($data['netto'],3,'.'.','),0,0,'R',false,'');
				//$pdf->Cell(13,2,$data['netto'],0,0,'R',false,'');
				$pdf->Cell(13,2,$this->CI->fungsi->pecah3($data['netto'],','),0,0,'R',false,'');
				$pdf->SetXY(186, 135);
				$pdf->write(0, ' KGS');
			}

        if($data['bl_meas'] != '' ) {
			$pdf->SetXY(153, 140);
			$pdf->write(0,'MEAS : ');
			$pdf->SetXY(175, 139);
			$pdf->Cell(13,2,$this->CI->fungsi->pecah4($data['bl_meas'],','),0,0,'R',false,'');
			$pdf->SetXY(186, 140);
			$pdf->write(0, ' M');
			$pdf->SetXY(189, 139);
			$pdf->SetFont('Arial','',6);
			$pdf->write(0, ' 3');  }

			$pdf->SetFont('Arial','',8);
			$pdf->SetXY(18, 203);
			$pdf->MultiCell(40,3, 'FREIGHT');
			$q = $this->CI->dbx->get_where('tbl_detail',array('detail_id'=>$spd));
			if($q->row()->payable == 'AS ARRANGED')
				{
			$pdf->SetXY(16, 203);
			$pdf->MultiCell(40,3, 'AS ARRANGED');
				}

			$pdf->SetFont('Arial','',8);
			$pdf->SetXY(10, 203);
			$pdf->MultiCell(40,3, $data['freights_charges']);

			$freight_payable = $data['freight'];
			if($freight_payable == "PREPAID") {
	  			$pdf->SetXY(55, 203);
	  			$pdf->MultiCell(40,3,$freight_payable);
			} else {
	  			$pdf->SetXY(83, 203);
	  			$pdf->MultiCell(40,3,$freight_payable);
			}

			if($data['cetak_jml_doc_origi'] == "TRUE") {
	  			$pdf->SetXY(108, 203);
	  			$pdf->MultiCell(30,3,'3(THREE) ORIGINAL',0,'','');
			}

			$pdf->SetXY(133, 245);
			$pdf->write(0,'JAKARTA, '.strtoupper($tanggal));

			$pdf->SetXY(115, 264);
			$pdf->write(0,'PT WINFASTSHIPPING INDONESIA');
			$pdf->SetXY(115, 267);
			$pdf->MultiCell(60,3,strtoupper($as_agent),0,'L');
		}
		else
		{
			$pdf->SetAutoPageBreak(0, 0);
			$pdf->SetMargins(10,5,0,10);
			$pdf->SetXY(0, 15);
			$pdf->SetFont('Verdana','',12);
			$pdf->MultiCell(130, 5, $data['note'],0,'',false);
			$pdf->SetXY(220, 14);
			$pdf->SetFont('Verdana','B',12);
			$pdf->write(0, str_replace('%20',' ',$bl_no));
			$pdf->SetFont('Verdana','',12);
			$pdf->SetXY(0, 55);
			$pdf->MultiCell(130,5,($data['consignee_add']),0,'L');
			$pdf->SetXY(0, 96);
			$pdf->MultiCell(120,5, ($data['notify_add']),0,'L');

			$pdf->SetXY(0, 140);
			$pdf->write(0, $port_of->port_of_receive);
			$pdf->SetXY(63, 140);
			$pdf->write(0, $port_of->port_of_loading);

			$connecting = $this->CI->m_print_out_bl->get_connecting($spd,$data['vessel'].' '.$data['voyage']);
			$q = $this->CI->dbx->get_where('tbl_detail',array('detail_id'=>$spd));
			if($connecting )
                                {
               $pdf->SetFont('Verdana','',10.5);
				$pdf->SetXY(0, 156);
				$pdf->write(0, $connecting->vessel);
				$pdf->SetXY(62, 151);
				if($q->row()->snd_vessel != '' || $q->row()->last_snd_vessel != '')
				{
				$pdf->write(0, 'Connecting Vessel');
				$pdf->SetXY(62, 156);
				$pdf->write(0, $connecting->conn_vessel);
				}
			}
                                 
			$pdf->SetXY(143, 155);
			$pdf->write(0, $port_of->port_of_discharge);
			$pdf->SetXY(219, 155);
			$pdf->write(0, $port_of->port_of_delivery);

			$agent = $this->CI->m_print_out_bl->getAgentByCode($data['agent_code']);
			
            if($agent != 0 ) {
            	if ($agent['agent_company_remark'] != '')
            	{
					$pdf->SetXY(145, 98);
					$pdf->MULTICELL(140,4.5, $agent['agent_company_remark'],0,'L',false);
					$pdf->SetXY(145, 109);
					$pdf->write(0, $agent['agent_company_name'],0,'L',false);
					$pdf->SetXY(145, 111);
					$pdf->MultiCell(110,5, $agent['agent_company_address'],0,'L',false);
				}
				else
				{
                 	$pdf->SetXY(145, 103);
					$pdf->write(0, $agent['agent_company_name']);
					$pdf->SetXY(145, 105);
					$pdf->MultiCell(110,5, $agent['agent_company_address'],0,'L',false);
             	}
			}
			$pdf->SetFont('Verdana','',12);
			if($attc_marks > 18) {
				$pdf->SetXY(0, 185);
				$pdf->MultiCell(45,5, '"SEE ATTACHMENT LIST"',0,'C',false);
			} else {
				$pdf->SetXY(0, 185);
				$pdf->SetFont('Verdana','',12);
				$pdf->MultiCell(60,5,($data['marking_nos']),0,'L',false);
			}
	  			////double countener /////

			$count = $this->CI->m_print_out_bl->get_count_double($sp);
			if($count->num_rows() > 0 ) {
                     $pdf->SetXY(0, 254);
						$pdf->write(0, 'CONTAINER / SEAL NO');
	  					$pdf->SetXY(0, 255);
	  					$pdf->SetMargins(0, 0);
						$pdf->Ln(2.5);
						foreach($count->result() as $row ) {
						$pdf->MultiCell(115,2.5, strtoupper($row->count_no).' / '. strtoupper($row->seal_no).' / '. strtoupper($row->count_size),0,'L',false);
						$pdf->Ln();
				}
		}
	  		else if ($last_container != '')
	  		{        $pdf->SetXY(0, 261);
	  					$pdf->SetFont('Verdana','',11);
						$pdf->write(0, 'CONTAINER / SEAL NO');
						$pdf->SetXY(0, 268);
						$pdf->MultiCell(85, 5, $last_container.' / '.$data['type_cont_size']);
	  				
	  		}
	  		
			
	  		////double countener /////
	  		
			
			
           if($fcl=='FCL') {

                  $pdf->SetXY(63, 185);
             	   $dbl = $this->CI->afg_m_stuffing_plan->get_double($sp);
             	   if($dbl->num_rows() > 4 ) {
             		$pdf->Ln(1);
					foreach($dbl->result() as $row ) {
						$pdf->MultiCell(80,3, $row->count_num.' '.'X'.' '.$row->count_type,0,"R");
						$pdf->Ln(1);
					}

					$pdf->SetXY(70, 285);
					foreach($dbl->result() as $row ) {
				   $pdf->Write(0,  $row->count_num.' ( '.$this->CI->fungsi->num_to_words( $row->count_num,'',0,'').' ) '.' '.'X'.' '.$row->count_type.' ');
                                                }
				   } else    {
			                $pdf->SetXY(60, 185);
		                 	$pdf->MultiCell(25,3, $data['no_pack_qty'].' '.'X'.' '.strtoupper($data['no_pack_unit']));
		                  	$pdf->SetXY(60, 285);

			           $pdf->MultiCell(120,3, $data['no_pack_qty'].' ( '.$this->CI->fungsi->num_to_words($data['no_pack_qty'],'',0,'').' ) '.' '.'X'.' '.strtoupper($data['no_pack_unit']).' ONLY');
                               }
                                }
                               else
                               {
			$pdf->SetXY(63, 183);
			$pdf->MultiCell(30,5, $data['no_pack_qty'].' '.strtoupper($data['no_pack_unit']));

			$pdf->SetXY(63, 285);
			
				$pdf->MultiCell(130,3, $data['no_pack_qty'].'( '.$this->CI->fungsi->num_to_words($data['no_pack_qty'],'',0,'').')'.''.strtoupper($data['no_pack_unit']).' ONLY');

                                }
			if($data['express_release'] == "TRUE") {
				$pdf->SetFont('Verdana','B',13);
				$pdf->SetXY(125, 270);
				$pdf->write(0, '" EXPRESS RELEASE "');
			}
			$pdf->SetFont('Verdana','',11);
			if($data['shipped_on_board'] == "TRUE") {
				$pdf->SetXY(200, 249.5);
				$pdf->write(0, 'SHIPPED ON BOARD');
				$pdf->SetXY(200, 254);
				$pdf->write(0, 'FROM '.$port_of->port_of_loading);
				$pdf->SetXY(200, 259);
				$pdf->write(0, 'ON '.strtoupper($tanggal));
				$pdf->SetXY(200, 264);
				$pdf->write(0, 'BY '.$connecting->vessel);
			}

			if($fcl=='FCL') {
				$pdf->SetXY(90, 185);
				$pdf->write(0, 'SHIPPER LOAD, COUNT & SEAL CONTAINER,');
				$pdf->SetXY(90, 190);
				$pdf->write(0, 'SAID TO CONTAIN :');
			}
			else
			{
				$pdf->SetXY(95, 185);
				$pdf->write(0,'SAID TO CONTAIN :');
			}

			if($attc_discription > 19)
			{
				$pdf->SetXY(110, 235);
				$pdf->MultiCell(60,5,'"SEE ATTACHMENT LIST"');
			}
			else
			{
				$pdf->SetXY(95, 195);
				$pdf->SetFont('Verdana','',12);
				$pdf->MultiCell(110,5,($data['desc_goods']),0,'L');
			}
			$pdf->SetFont('Verdana','',11);
			$pdf->SetXY(220, 185);
			$pdf->write(0, 'GW :');
			$pdf->SetXY(260, 184);
			$pdf->Cell(13,2,$this->CI->fungsi->pecah3($data['weight'],','),0,0,'R',false,'');
			$pdf->SetXY(270, 185);
			$pdf->write(0, ' KGS');

			if($data['net_weight_tidak_dicetak'] == "FALSE" ) {
				$pdf->SetXY(220, 195);
				$pdf->write(0,'NW : ');
				$pdf->SetXY(260, 194);
				$pdf->Cell(13,2,$this->CI->fungsi->pecah3($data['netto'],','),0,0,'R',false,'');
			
				$pdf->SetXY(270, 195);
				$pdf->write(0, ' KGS');
			}
                if($data['bl_meas'] != '' ) {
			$pdf->SetXY(220, 205);
			$pdf->write(0,'MEAS : ');
			$pdf->SetXY(260, 204);
			$pdf->Cell(13,2,$this->CI->fungsi->pecah4($data['bl_meas'],','),0,0,'R',false,'');
			$pdf->SetXY(270, 205);
			$pdf->write(0, ' M');
			$pdf->SetXY(273, 203);
			$pdf->SetFont('Arial','',10);
			$pdf->write(0, ' 3');  }

			$pdf->SetFont('Verdana','',12);
			$pdf->SetXY(10, 325);
			$pdf->MultiCell(40,3,'FREIGHT','','',false);
            $q = $this->CI->dbx->get_where('tbl_detail',array('detail_id'=>$spd));
			if($q->row()->payable == "AS ARRANGED")
				{
				$pdf->SetXY(5,319);
               $pdf->write(0, 'AS ARRANGED');
				}


			$freight_payable = $data['freight'];
			if($freight_payable == "PREPAID") {
	  			$pdf->SetXY(70, 315);
	  			$pdf->SetFont('Arial','',12);
	  			$pdf->MultiCell(40,3,$freight_payable,'','',false);
			} else {
	  			$pdf->SetXY(115, 315);
	  			$pdf->SetFont('Arial','',12);
	  			$pdf->MultiCell(40,3,$freight_payable,'','',false);
			}

			if($data['cetak_jml_doc_origi'] == "TRUE") {
	  			$pdf->SetXY(158, 315);
	  			$pdf->SetFont('Arial','',12);
	  			$pdf->MultiCell(50,3,'3(THREE) ORIGINAL',0,'','');
			}

			$pdf->SetXY(189, 368);
			$pdf->SetFont('Arial','',12);
			$pdf->MultiCell(73,1,strtoupper('jakarta'.', '.$tanggal),'','',false);
			if ($as_agent ==''){
			$pdf->SetXY(160, 392);
			$pdf->SetFont('Verdana','',12);
			$pdf->write(0,'PT WINFASTSHIPPING INDONESIA');}
			else{
			$pdf->SetXY(160, 392);
			$pdf->SetFont('Verdana','',12);
			$pdf->write(0,'PT WINFASTSHIPPING INDONESIA');
			$pdf->SetXY(160, 400);
			$pdf->MultiCell(100,5,strtoupper($as_agent),0,'L');}
		}
	}
	if($code==11)
	{
		if($cetak=='TRUE')
		{
			$pdf->Image(APPPATH.'third_party/img/bpline-bl.jpeg',0,0,+200);
			$pdf->SetFont('Arial','',8);
			$pdf->SetXY(10, 17);
			$pdf->MultiCell(89, 3, $data['note'],0,'',false);
			$pdf->SetXY(153, 19);
			$pdf->SetFont('Arial','B',11);
			$pdf->write(0, str_replace('%20',' ',$bl_no));
			$pdf->SetFont('Arial','',8);
			$pdf->SetXY(10, 43);
			$pdf->MultiCell(90,3,($data['consignee_add']),0,'L');
			$pdf->SetXY(10, 68.5);
			$pdf->SetFont('Arial','',8);
			$pdf->MultiCell(90,3, ($data['notify_add']),0,'L');

			$pdf->SetXY(10, 97.5);
			$pdf->write(0, $port_of->port_of_receive);
			$pdf->SetXY(52, 97.5);
			$pdf->write(0, $port_of->port_of_loading);

			$connecting = $this->CI->m_print_out_bl->get_connecting($spd   ,$data['vessel'].' '.$data['voyage']);
			$q = $this->CI->dbx->get_where('tbl_detail',array('detail_id'=>$spd));
			if($connecting) {
				$pdf->SetXY(10, 108);
				$pdf->write(0, $connecting->vessel);
				$pdf->SetXY(52, 105);
				if($q->row()->snd_vessel != '' || $q->row()->last_snd_vessel != '')
				{
				$pdf->write(0, 'Connecting Vessel');
				$pdf->SetXY(52, 108);
				$pdf->write(0, strtoupper($connecting->conn_vessel));  }
			}

			$pdf->SetXY(101, 108);
			$pdf->write(0, $port_of->port_of_discharge);
			$pdf->SetXY(149, 108);
			$pdf->write(0, $port_of->port_of_delivery);

			$agent = $this->CI->m_print_out_bl->getAgentByCode($data['agent_code'],0,'L',false);

			if($agent != 0 ) {
                                  if ($agent['agent_company_remark'] != '')
                                  {
				$pdf->SetXY(103, 66.5);
				$pdf->MULTICELL(85,2.5, $agent['agent_company_remark']);
                                       $pdf->SetXY(103, 80.5);
				$pdf->write(0, $agent['agent_company_name']);
				$pdf->SetXY(103, 75);
				$pdf->MultiCell(85,2.5, $agent['agent_company_address'],0,'L',false);
				}
				else
				{
                    $pdf->SetXY(103, 70);
				$pdf->write(0, $agent['agent_company_name']);
				$pdf->SetXY(103, 72);
				$pdf->MultiCell(85,3, $agent['agent_company_address'],0,'L',false);
                                  }
			}

			if($attc_marks > 18) {
				$pdf->SetXY(10, 123);
				$pdf->MultiCell(39,3, '"SEE ATTACHMENT LIST"',0,'C',false);
			} else {
				$pdf->SetXY(10, 123);
				$pdf->MultiCell(39,3,($data['marking_nos']),0,'L',false);
			}

			////double countener /////

			$count = $this->CI->m_print_out_bl->get_count_double($sp);
			if($count->num_rows() > 0 ) {
                $pdf->SetXY(10, 167);
			$pdf->write(0, 'CONTAINER / SEAL NO');
			$pdf->Ln(2.5);
				foreach($count->result() as $row ) {
					$pdf->MultiCell(60,1.5, strtoupper($row->count_no).' / '. strtoupper($row->seal_no).' / '. strtoupper($row->count_size),0,'L',false);
					$pdf->Ln();
				}
			}
	  		else if ($last_container != '')
	  		{  
	  			$pdf->SetXY(10, 177);
			    $pdf->write(0, 'CONTAINER / SEAL NO');
	  			$pdf->SetXY(10, 179);
				$pdf->MultiCell(45, 3, $last_container.' / '.$data['type_cont_size']);
	  		}
	  		
	  	   if($fcl=='FCL') {

             	$pdf->SetXY(53, 125);
             	$dbl = $this->CI->afg_m_stuffing_plan->get_double($sp);
             	if($dbl->num_rows() > 0 ) {
             		$pdf->Ln(1);
					foreach($dbl->result() as $row ) {
						$pdf->MultiCell(60,3, $row->count_num.' '.'X'.' '.$row->count_type,0,"R");
						$pdf->Ln(0.5);
					}

					$pdf->SetXY(53, 186);
                  foreach($dbl->result() as $row ) {
				   $pdf->Write(0,  $row->count_num.' ( '.$this->CI->fungsi->num_to_words( $row->count_num,'',0,'').' ) '.' '.'X'.' '.$row->count_type.' ');
                  }
			 } else {  
			 $pdf->SetXY(50, 125);
                 $pdf->MultiCell(16,3, $data['no_pack_qty'].' '.'X'.' '.$data['no_pack_unit']);
					$pdf->SetXY(53, 184);
				   $pdf->MultiCell(120,3, $data['no_pack_qty'].' ( '.$this->CI->fungsi->num_to_words($data['no_pack_qty'],'',0,'').' ) '.' '.'X'.' '.$data['no_pack_unit'].' ONLY');
       				 }
           	}
     			else
           	{
            	$pdf->SetXY(50, 124);
				$pdf->MultiCell(20,3, $data['no_pack_qty'].' '.$data['no_pack_unit']);
				$pdf->SetXY(50, 188);
				$pdf->MultiCell(120,3, $data['no_pack_qty'].' ( '.$this->CI->fungsi->num_to_words($data['no_pack_qty'],'',0,'').' ) '.$data['no_pack_unit'].' ONLY');
			}
			
			if($data['express_release'] == "TRUE") 
			{
				$pdf->SetXY(94, 182);
				$pdf->write(0, '" EXPRESS RELEASE "');
			}
			
			if($data['shipped_on_board'] == "TRUE")
			{
				$pdf->SetXY(140, 160);
				$pdf->write(0, 'SHIPPED ON BOARD');
				$pdf->SetXY(140, 163);
				$pdf->write(0, 'FROM '.$port_of->port_of_loading);
				$pdf->SetXY(140, 166);
				$pdf->write(0, 'ON '.strtoupper($tanggal));
				$pdf->SetXY(140, 169);
				$pdf->write(0, 'BY '.$connecting->vessel);
				//$pdf->SetXY(140, 172);
				//$pdf->write(0, 'TO '.$port_of->port_of_discharge);
			}

			if($fcl=='FCL') {
				$pdf->SetXY(75, 125);
				$pdf->write(0, 'SHIPPER LOAD, COUNT & SEAL CONTAINER,');
				$pdf->SetXY(75, 128);
				$pdf->write(0, 'SAID TO CONTAIN :');
			}
			else
			{
				$pdf->SetXY(75, 125);
				$pdf->write(0,'SAID TO CONTAIN :');
			}

			if($attc_discription > 15)
			{
				$pdf->SetXY(90, 133);
				$pdf->MultiCell(40,3,'"SEE ATTACHMENT LIST"');
			}
			else
			{
				$pdf->SetXY(75, 129);
				$pdf->MultiCell(60,3,($data['desc_goods']),0,'L');
			}
			


			$pdf->SetXY(153, 130);
			$pdf->write(0, 'GW :');
			$pdf->SetXY(172, 129);
			$pdf->Cell(13,2,$this->CI->fungsi->pecah3($data['weight'],','),0,0,'R',false,'');
			$pdf->SetXY(183, 130);
			$pdf->write(0, ' KGS');

			if($data['net_weight_tidak_dicetak'] == "FALSE" ) {
				$pdf->SetXY(153, 135);
				$pdf->write(0,'NW : ');
				$pdf->SetXY(172, 134);
				//$pdf->Cell(13,2,number_format($data['netto'],3,'.'.','),0,0,'R',false,'');
				//$pdf->Cell(13,2,$data['netto'],0,0,'R',false,'');
				$pdf->Cell(13,2,$this->CI->fungsi->pecah3($data['netto'],','),0,0,'R',false,'');
				$pdf->SetXY(183, 135);
				$pdf->write(0, ' KGS');
			}

        if($data['bl_meas'] != '' ) {
			$pdf->SetXY(153, 140);
			$pdf->write(0,'MEAS : ');
			$pdf->SetXY(172, 139);
			$pdf->Cell(13,2,$this->CI->fungsi->pecah4($data['bl_meas'],','),0,0,'R',false,'');
			$pdf->SetXY(183, 140);
			$pdf->write(0, ' M');
			$pdf->SetXY(186, 139);
			$pdf->SetFont('Arial','',6);
			$pdf->write(0, ' 3');  }

			$pdf->SetFont('Arial','',8);
			$pdf->SetXY(20, 204);
			$pdf->write(0, 'FREIGHT');
			$pdf->SetXY(10, 210);
			$pdf->write(0,$payable);
			$q = $this->CI->dbx->get_where('tbl_detail',array('detail_id'=>$spd));
			if($q->row()->payable == 'AS ARRANGED')
				{
				$pdf->SetXY(16, 203);
               $pdf->write(0, 'AS ARRANGED');
				}

			$pdf->SetFont('Arial','',8);
			$pdf->SetXY(10, 200);
			$pdf->MultiCell(40,3, $data['freights_charges']);

			$freight_payable = $data['freight'];
			if($freight_payable == "PREPAID") {
	  			$pdf->SetXY(55, 204);
	  			$pdf->write(0,$freight_payable);
			} else {
	  			$pdf->SetXY(83, 204);
	  			$pdf->Write(0,$freight_payable);
			}

			if($data['cetak_jml_doc_origi'] == "TRUE") {
	  			$pdf->SetXY(108, 204);
	  			$pdf->MultiCell(30,3,'3(THREE) ORIGINAL',0,'','');
			}

			$pdf->SetXY(133, 243);
			$pdf->Write(0,strtoupper('JAKARTA, '.$tanggal));

			$pdf->SetXY(110, 261);
			$pdf->write(0,'PT. BANGUN PUTRA PESAKA');
			$pdf->SetXY(120, 257);
			$pdf->MultiCell(60,3,strtoupper($as_agent),0,'L');
		}
		else
		{
			$pdf->SetAutoPageBreak(0, 0);
			$pdf->SetMargins(10,5,0,10);
			$pdf->SetXY(0, 17);
			$pdf->SetFont('Verdana','',12);
			$pdf->MultiCell(130, 5, $data['note'],0,'',false);
			$pdf->SetXY(220, 17);
			$pdf->SetFont('Verdana','B',14);
			$pdf->write(0, str_replace('%20',' ',$bl_no));
			$pdf->SetFont('Verdana','',12);
			$pdf->SetXY(0, 57);
			$pdf->MultiCell(130,5,($data['consignee_add']),0,'L');
			$pdf->SetXY(0, 101);
			$pdf->MultiCell(120,5, ($data['notify_add']),0,'L');

			$pdf->SetXY(0, 144);
			$pdf->write(0, $port_of->port_of_receive);
			$pdf->SetXY(63, 144);
			$pdf->write(0, $port_of->port_of_loading);

			$connecting = $this->CI->m_print_out_bl->get_connecting($spd,$data['vessel'].' '.$data['voyage']);
			$q = $this->CI->dbx->get_where('tbl_detail',array('detail_id'=>$spd));
			if($connecting )
                                {
               $pdf->SetFont('Verdana','',12);
				$pdf->SetXY(0, 160);
				$pdf->write(0, $connecting->vessel);
				$pdf->SetXY(62, 151);
				if($q->row()->snd_vessel != '' || $q->row()->last_snd_vessel != '')
				{
				$pdf->SetFont('Verdana','',10.5);
				$pdf->write(0, 'Connecting Vessel');
				$pdf->SetFont('Verdana','',12);
				$pdf->SetXY(62, 160);
				$pdf->write(0, $connecting->conn_vessel);
				}
			}  
			$pdf->SetFont('Verdana','',11);
			$pdf->SetXY(141, 160);
			$pdf->write(0, $port_of->port_of_discharge);
			$pdf->SetXY(217, 160);
			$pdf->write(0, $port_of->port_of_delivery);
			$agent = $this->CI->m_print_out_bl->getAgentByCode($data['agent_code']);	
            if($agent != 0 ) {
            	if ($agent['agent_company_remark'] != '')
            	{
					$pdf->SetXY(145, 98);
					$pdf->MULTICELL(140,4.5, $agent['agent_company_remark'],0,'L',false);
					$pdf->SetXY(145, 109);
					$pdf->write(0, $agent['agent_company_name'],0,'L',false);
					$pdf->SetXY(145, 111);
					$pdf->MultiCell(110,5, $agent['agent_company_address'],0,'L',false);
				}
				else
				{
                 	$pdf->SetXY(145, 103);
					$pdf->write(0, $agent['agent_company_name']);
					$pdf->SetXY(145, 105);
					$pdf->MultiCell(110,5, $agent['agent_company_address'],0,'L',false);
             	}
			}
			$pdf->SetFont('Verdana','',12);
			if($attc_marks > 18) {
				$pdf->SetXY(0, 185);
				$pdf->MultiCell(45,5, '"SEE ATTACHMENT LIST"',0,'C',false);
			} else {
				$pdf->SetXY(0, 185);
				$pdf->SetFont('Verdana','',12);
				$pdf->MultiCell(60,5,($data['marking_nos']),0,'L',false);
			}
	  			////double countener /////

			$count = $this->CI->m_print_out_bl->get_count_double($sp);
			if($count->num_rows() > 0 ) {
                     $pdf->SetXY(0, 254);
						$pdf->write(0, 'CONTAINER / SEAL NO');
	  					$pdf->SetXY(0, 255);
	  					$pdf->SetMargins(0, 0);
						$pdf->Ln(2.5);
						foreach($count->result() as $row ) {
						$pdf->MultiCell(115,2.5, strtoupper($row->count_no).' / '. strtoupper($row->seal_no).' / '. strtoupper($row->count_size),0,'L',false);
						$pdf->Ln();
				}
		}
	  		else if ($last_container != '')
	  		{        $pdf->SetXY(0, 274);
	  					$pdf->SetFont('Verdana','',11);
						$pdf->write(0, 'CONTAINER / SEAL NO');
						$pdf->SetXY(0, 278);
						$pdf->MultiCell(85, 5, $last_container.' / '.$data['type_cont_size']);
	  				
	  		}
	  		
			
	  		////double countener /////
	  		
			
			
           if($fcl=='FCL') {

							$pdf->SetXY(60, 185);
							$dbl = $this->CI->afg_m_stuffing_plan->get_double($sp);
							if($dbl->num_rows() > 0 ) {
							$pdf->Ln(1);
							foreach($dbl->result() as $row ) {
							$pdf->MultiCell(80,3, $row->count_num.' '.'X'.' '.$row->count_type,0,"R");
							$pdf->Ln(1);
						}
				   $pdf->SetXY(70, 291);
				   foreach($dbl->result() as $row ) {
													$pdf->Write(0,  $row->count_num.' ( '.$this->CI->fungsi->num_to_words( $row->count_num,'',0,'').' ) '.' '.'X'.' '.$row->count_type.' ');
													}
			////end double countener /////
				   } else{
			        $pdf->SetXY(60, 185);
		            $pdf->MultiCell(25,3, $data['no_pack_qty'].' '.'X'.' '.$data['no_pack_unit']);
		            $pdf->SetXY(60, 291);
					$pdf->MultiCell(120,3, $data['no_pack_qty'].' ( '.$this->CI->fungsi->num_to_words($data['no_pack_qty'],'',0,'').' ) '.' '.'X'.' '.$data['no_pack_unit'].' ONLY');
                           }
                        }else{
			$pdf->SetXY(58, 183);
			$pdf->MultiCell(30,5, $data['no_pack_qty'].' '.$data['no_pack_unit']);
			$pdf->SetXY(61, 290);
			$pdf->MultiCell(130,3, $data['no_pack_qty'].'( '.$this->CI->fungsi->num_to_words($data['no_pack_qty'],'',0,'').')'.''.$data['no_pack_unit'].' ONLY');

                                }
			if($data['express_release'] == "TRUE") {
				$pdf->SetFont('Verdana','B',13);
				$pdf->SetXY(125, 275);
				$pdf->write(0, '" EXPRESS RELEASE "');
			}
			$pdf->SetFont('Verdana','',11);
			if($data['shipped_on_board'] == "TRUE") {
				$pdf->SetXY(200, 249.5);
				$pdf->write(0, 'SHIPPED ON BOARD');
				$pdf->SetXY(200, 254);
				$pdf->write(0, 'FROM '.$port_of->port_of_loading);
				$pdf->SetXY(200, 259);
				$pdf->write(0, 'ON '.strtoupper($tanggal));
				$pdf->SetXY(200, 264);
				$pdf->write(0, 'BY '.$connecting->vessel);
				//$pdf->SetXY(200, 267);
				//$pdf->write(0, 'TO '. $port_of->port_of_discharge);
			}

			if($fcl=='FCL') {
				$pdf->SetXY(90, 185);
				$pdf->write(0, 'SHIPPER LOAD, COUNT & SEAL CONTAINER,');
				$pdf->SetXY(90, 190);
				$pdf->write(0, 'SAID TO CONTAIN :');
			}
			else
			{
				$pdf->SetXY(95, 185);
				$pdf->write(0,'SAID TO CONTAIN :');
			}

			if($attc_discription > 15)
			{
				$pdf->SetXY(110, 235);
				$pdf->MultiCell(60,5,'"SEE ATTACHMENT LIST"');
			}
			else
			{
				$pdf->SetXY(95, 195);
				$pdf->SetFont('Verdana','',12);
				$pdf->MultiCell(110,5,($data['desc_goods']),0,'L');
			}
			$pdf->SetFont('Verdana','',11);
			$pdf->SetXY(215, 185);
			$pdf->write(0, 'GW :');
			$pdf->SetXY(260, 184);
			$pdf->Cell(13,2,$this->CI->fungsi->pecah3($data['weight'],','),0,0,'R',false,'');
			$pdf->SetXY(270, 185);
			$pdf->write(0, ' KGS');

			if($data['net_weight_tidak_dicetak'] == "FALSE" ) {
				$pdf->SetXY(215, 195);
				$pdf->write(0,'NW : ');
				$pdf->SetXY(260, 194);
				$pdf->Cell(13,2,$this->CI->fungsi->pecah3($data['netto'],','),0,0,'R',false,'');
			
				$pdf->SetXY(270, 195);
				$pdf->write(0, ' KGS');
			}
                                if($data['bl_meas'] != '' ) {
			$pdf->SetXY(215, 205);
			$pdf->write(0,'MEAS : ');
			$pdf->SetXY(260, 204);
			$pdf->Cell(13,2,$this->CI->fungsi->pecah4($data['bl_meas'],','),0,0,'R',false,'');
			$pdf->SetXY(270, 205);
			$pdf->write(0, ' M');
			$pdf->SetXY(273, 203);
			$pdf->SetFont('Arial','',10);
			$pdf->write(0, ' 3');  }

			$pdf->SetFont('Verdana','',12);
			$pdf->SetXY(20, 315);
			$pdf->write(0, 'FREIGHT');
			$pdf->SetXY(5, 322);
			$pdf->write(0,$payable);
            $q = $this->CI->dbx->get_where('tbl_detail',array('detail_id'=>$spd));
			if($q->row()->payable == "AS ARRANGED")
				{
				$pdf->SetXY(5,319);
               $pdf->write(0, 'AS ARRANGED');
				}


			$freight_payable = $data['freight'];
			if($freight_payable == "PREPAID") {
	  			$pdf->SetXY(70, 315);
	  			$pdf->SetFont('Arial','',12);
	  			$pdf->MultiCell(40,3,$freight_payable,'','',false);
			} else {
	  			$pdf->SetXY(110, 315);
	  			$pdf->SetFont('Arial','',12);
	  			$pdf->MultiCell(40,3,$freight_payable,'','',false);
			}

			if($data['cetak_jml_doc_origi'] == "TRUE") {
	  			$pdf->SetXY(158, 315);
	  			$pdf->SetFont('Arial','',12);
	  			$pdf->MultiCell(50,3,'3(THREE) ORIGINAL',0,'','');
			}

			$pdf->SetXY(189, 377);
			$pdf->SetFont('Arial','',12);
			$pdf->MultiCell(73,1,strtoupper('JAKARTA '.$tanggal),'','',false);
			if ($as_agent ==''){
			$pdf->SetXY(160, -10);
			$pdf->SetFont('Verdana','',12);
			$pdf->write(0,'PT BANGUN PUTRA PESAKA');}
			else{
			$pdf->SetXY(160, 399);
			$pdf->SetFont('Verdana','',12);
			$pdf->write(0,'PT BANGUN PUTRA PESAKA');
			$pdf->SetXY(160, 403);
			$pdf->MultiCell(100,5,strtoupper($as_agent),0,'L');}
		}
	}
	if($code==12)
	{
		if($cetak=='TRUE')
		{
			$pdf->Image(APPPATH.'third_party/img/HNS.jpg',5,5,+200);
			$pdf->SetFont('Arial','',7);
			$pdf->SetXY(16, 22);
			$pdf->MultiCell(89, 3, $data['note'],0,'',false);
			$pdf->SetXY(152, 23);
			$pdf->SetFont('Arial','B',10);
			$pdf->write(0, str_replace('%20',' ',$bl_no));
			$pdf->SetFont('Arial','',7);
			$pdf->SetXY(16, 45);
			$pdf->MultiCell(90,3,($data['consignee_add']),0,'L');
			$pdf->SetXY(16, 69);
			$pdf->MultiCell(90,3, ($data['notify_add']),0,'L');

			$pdf->SetXY(68, 94);
			$pdf->write(0, $port_of->port_of_receive);
			$pdf->SetXY(68, 103);
			$pdf->write(0, $port_of->port_of_loading);

			$connecting = $this->CI->m_print_out_bl->get_connecting($spd   ,$data['vessel'].' '.$data['voyage']);
			$q = $this->CI->dbx->get_where('tbl_detail',array('detail_id'=>$spd));
			if($connecting) {
				$pdf->SetXY(16, 103);
				$pdf->write(0, $connecting->vessel);
				$pdf->SetXY(57, 109);
				if($q->row()->snd_vessel != '' || $q->row()->last_snd_vessel != '')
				{
				$pdf->write(0, 'Connecting Vessel');
				$pdf->SetXY(57, 112);
				$pdf->write(0, strtoupper($connecting->conn_vessel));  }
			}

			$pdf->SetXY(16, 112);
			$pdf->write(0, $port_of->port_of_discharge);
			$pdf->SetXY(76, 112);
			$pdf->write(0, $port_of->port_of_delivery);
			$pdf->SetXY(145, 112);
			$pdf->write(0, $port_of->port_of_delivery);

			$agent = $this->CI->m_print_out_bl->getAgentByCode($data['agent_code'],0,'L',false);

			if($agent != 0 ) {
                                  if ($agent['agent_company_remark'] != '')
                                  {
				$pdf->SetXY(16, 248);
				$pdf->MULTICELL(85,2.5, $agent['agent_company_remark']);
					$pdf->SetXY(16, 248);
				$pdf->write(0, $agent['agent_company_name']);
				$pdf->SetXY(16, 250);
				$pdf->MultiCell(85,2.5, $agent['agent_company_address'],0,'L',false);
				}
				else
				{
                    $pdf->SetXY(16, 248);
				$pdf->write(0, $agent['agent_company_name']);
				$pdf->SetXY(16, 250);
				$pdf->MultiCell(85,3, $agent['agent_company_address'],0,'L',false);
                                  }
			}

			if($attc_marks > 18) {
				$pdf->SetXY(20, 138);
				$pdf->MultiCell(39,3, 'AS PER LIST ATTACHED',0,'C',false);
			} else {
				$pdf->SetXY(16, 123);
				$pdf->MultiCell(39,3,($data['marking_nos']),0,'L',false);
			}
			////double countener /////

			$count = $this->CI->m_print_out_bl->get_count_double($sp);
			if($count->num_rows() > 0 ) {
			$pdf->SetXY(17, 172);
			  $pdf->write(0, 'CONTAINER / SEAL NO');
			  $pdf->Ln(2.5);
				foreach($count->result() as $row ) {
					$pdf->SetX(17);
					$pdf->MultiCell(60,1.5, strtoupper($row->count_no).' / '. strtoupper($row->seal_no).' / '. strtoupper($row->count_size),0,'L',false);
					$pdf->Ln();
				}
			}
	  		else if ($last_container != '')
	  		{  
	  			$pdf->SetXY(17, 187);
			   $pdf->write(0, 'CONTAINER / SEAL NO');
	  			$pdf->SetXY(17, 190);
				$pdf->MultiCell(45, 3, $last_container.' / '.$data['type_cont_size']);
	  		}
	  		
	  	   if($fcl=='FCL') {

             	$pdf->SetXY(56, 131);
             	$dbl = $this->CI->afg_m_stuffing_plan->get_double($sp);
             	if($dbl->num_rows() > 0 ) {
					$pdf->SetXY(56, 200);
					foreach($dbl->result() as $row ) {
				   $pdf->Write(0,  $row->count_num.' ( '.$this->CI->fungsi->num_to_words( $row->count_num,'',0,'').' ) '.' '.'X'.' '.$row->count_type.' ');
					}
			 } else {  
					$pdf->SetXY(56, 200);
					$pdf->MultiCell(120,3, $data['no_pack_qty'].' ( '.$this->CI->fungsi->num_to_words($data['no_pack_qty'],'',0,'').' ) '.' '.'X'.' '.$data['no_pack_unit'].' ONLY');
       				 }
           	}
     			else
           	{
				$pdf->SetXY(56, 200);
				$pdf->MultiCell(120,3, $data['no_pack_qty'].' ( '.$this->CI->fungsi->num_to_words($data['no_pack_qty'],'',0,'').' ) '.$data['no_pack_unit'].' ONLY');
			}
			
			if($data['express_release'] == "TRUE") 
			{
				$pdf->SetXY(95, 187);
				$pdf->SetFont('Arial','B',10);
				$pdf->write(0, '" EXPRESS RELEASE "');
			}
			$pdf->SetFont('Arial','',7);
			if($data['shipped_on_board'] == "TRUE")
			{
				$pdf->SetXY(145, 165);
				$pdf->write(0, 'SHIPPED ON BOARD');
				$pdf->SetXY(145, 168);
				$pdf->write(0, 'FROM '.$port_of->port_of_loading);
				$pdf->SetXY(145, 171);
				$pdf->write(0, 'ON '.strtoupper($tanggal));
				$pdf->SetXY(145, 174);
				$pdf->write(0, 'BY '.$connecting->vessel);
				//$pdf->SetXY(140, 172);
				//$pdf->write(0, 'TO '.$port_of->port_of_discharge);
			}

			if($fcl=='FCL') {
				$pdf->SetXY(80, 130);
				$pdf->write(0, 'SHIPPER LOAD, COUNT & SEAL CONTAINER,');
				$pdf->SetXY(80, 133);
				$pdf->write(0, 'SAID TO CONTAIN :');
				if($attc_discription > 15)
				{
				$pdf->SetXY(95, 138);
				$pdf->MultiCell(40,3,'AS PER LIST ATTACHED');
				}
				else
				{
				$pdf->SetXY(80, 136);
				$pdf->MultiCell(60,3,($data['desc_goods']),0,'L');
				}
			}
			else
			{
				$pdf->SetXY(70, 125);
				$pdf->write(0,'SAID TO CONTAIN :');
				if($attc_discription > 15)
				{
				$pdf->SetXY(95, 138);
				$pdf->MultiCell(40,3,'AS PER LIST ATTACHED');
				}
				else
				{
				$pdf->SetXY(70, 126);
				$pdf->MultiCell(60,3,($data['desc_goods']),0,'L');
				}
			}
			$pdf->SetXY(158, 135);
			$pdf->write(0, 'GW :');
			$pdf->SetXY(177, 134);
			$pdf->Cell(13,2,$this->CI->fungsi->pecah3($data['weight'],','),0,0,'R',false,'');
			$pdf->SetXY(188, 135);
			$pdf->write(0, ' KGS');

			if($data['net_weight_tidak_dicetak'] == "FALSE" ) {
				$pdf->SetXY(158, 140);
				$pdf->write(0,'NW : ');
				$pdf->SetXY(177, 139);
				//$pdf->Cell(13,2,number_format($data['netto'],3,'.'.','),0,0,'R',false,'');
				//$pdf->Cell(13,2,$data['netto'],0,0,'R',false,'');
				$pdf->Cell(13,2,$this->CI->fungsi->pecah3($data['netto'],','),0,0,'R',false,'');
				$pdf->SetXY(188, 140);
				$pdf->write(0, ' KGS');
			}

        if($data['bl_meas'] != '' ) {
			$pdf->SetXY(158, 145);
			$pdf->write(0,'MEAS : ');
			$pdf->SetXY(177, 144);
			$pdf->Cell(13,2,$this->CI->fungsi->pecah4($data['bl_meas'],','),0,0,'R',false,'');
			$pdf->SetXY(188, 145);
			$pdf->write(0, ' M');
			$pdf->SetXY(191, 144);
			$pdf->SetFont('Arial','',6);
			$pdf->write(0, ' 3');  }

			$pdf->SetFont('Arial','',7);
			$pdf->SetXY(22, 214);
			$pdf->write(0, 'FREIGHT');
			$pdf->SetXY(116, 230);
			$pdf->write(0,$payable);
			$q = $this->CI->dbx->get_where('tbl_detail',array('detail_id'=>$spd));
			if($q->row()->payable == 'AS ARRANGED')
				{
				$pdf->SetXY(21, 208);
				$pdf->write(0, 'AS ARRANGED');
				}


			$pdf->SetXY(116, 218);
			$pdf->MultiCell(40,3, $data['freights_charges']);

			$freight_payable = $data['freight'];
			if($freight_payable == "PREPAID") {
	  			$pdf->SetXY(70, 214);
	  			$pdf->write(0,$freight_payable);
			} else {
	  			$pdf->SetXY(88, 214);
	  			$pdf->write(0,$freight_payable);
			}
			$caya = $this->CI->m_print_out_bl->get_caya_caya($spd);
			$pdf->SetXY(116, 214);
			$pdf->write(0, $caya);

			if($data['cetak_jml_doc_origi'] == "TRUE") {
	  			$pdf->SetXY(116, 220);
	  			$pdf->MultiCell(30,3,'3(THREE) ORIGINAL',0,'','');
			}

			$pdf->SetXY(116, 238);
			$pdf->Write(0,strtoupper($this->CI->session->userdata('region_id').', '.$tanggal));

			$pdf->SetXY(123, 248);
			$pdf->write(0,'PT HUMBLE & SINCERE LOGISTICS');
			$pdf->SetXY(123, 251);
			$pdf->write(0,'JL. OTTO ISKANDAR DINATA III No.19 CIPINANG CEMPEDAK');
			$pdf->SetXY(123, 254);
			$pdf->write(0,'JATINEGARA JAKARTA TIMUR, INDONESIA');
			$pdf->SetXY(123, 257);
			$pdf->write(0,'TEL : +62 21 21011593  (Mr. RAMA)');
			$pdf->SetXY(123, 260);
			$pdf->write(0,'EMAIL : rama@hnslines.com');
			$pdf->SetXY(123, 270);
			$pdf->MultiCell(70,3,strtoupper($as_agent),0,'L');
			
		}
		else
		{
			$pdf->SetAutoPageBreak(0, 0);
			$pdf->SetMargins(10,5,0,10);
			$pdf->SetFont('Arial','',9);
			$pdf->SetXY(16, 20);
			$pdf->MultiCell(89, 4, $data['note'],0,'',false);
			$pdf->SetXY(152, 23);
			$pdf->SetFont('Arial','B',12);
			$pdf->write(0, str_replace('%20',' ',$bl_no));
			$pdf->SetFont('Arial','',9);
			$pdf->SetXY(16, 44);
			$pdf->MultiCell(90,4,($data['consignee_add']),0,'L');
			$pdf->SetXY(16, 69);
			$pdf->MultiCell(90,4, ($data['notify_add']),0,'L');

			$pdf->SetXY(68, 98);
			$pdf->write(0, $port_of->port_of_receive);
			$pdf->SetXY(68, 107);
			$pdf->write(0, $port_of->port_of_loading);

			$connecting = $this->CI->m_print_out_bl->get_connecting($spd   ,$data['vessel'].' '.$data['voyage']);
			$q = $this->CI->dbx->get_where('tbl_detail',array('detail_id'=>$spd));
			if($connecting) {
				$pdf->SetXY(16, 107);
				$pdf->write(0, $connecting->vessel);
				$pdf->SetXY(57, 109);
				if($q->row()->snd_vessel != '' || $q->row()->last_snd_vessel != '')
				{
				$pdf->write(0, 'Connecting Vessel');
				$pdf->SetXY(57, 112);
				$pdf->write(0, strtoupper($connecting->conn_vessel));  }
			}

			$pdf->SetXY(16, 116);
			$pdf->write(0, $port_of->port_of_discharge);
			$pdf->SetXY(76, 116);
			$pdf->write(0, $port_of->port_of_delivery);
			$pdf->SetXY(145, 116);
			$pdf->write(0, $port_of->port_of_delivery);

			$agent = $this->CI->m_print_out_bl->getAgentByCode($data['agent_code'],0,'L',false);

			if($agent != 0 ) {
                                  if ($agent['agent_company_remark'] != '')
                                  {
				$pdf->SetXY(16, 268);
				$pdf->MULTICELL(85,2.5, $agent['agent_company_remark']);
					$pdf->SetXY(16, 268);
				$pdf->write(0, $agent['agent_company_name']);
				$pdf->SetXY(16, 270);
				$pdf->MultiCell(85,2.5, $agent['agent_company_address'],0,'L',false);
				}
				else
				{
                    $pdf->SetXY(16, 265);
				$pdf->write(0, $agent['agent_company_name']);
				$pdf->SetXY(16, 267);
				$pdf->MultiCell(85,4, $agent['agent_company_address'],0,'L',false);
                                  }
			}

			if($attc_marks > 18) {
				$pdf->SetXY(20, 138);
				$pdf->MultiCell(39,3, 'AS PER LIST ATTACHED',0,'C',false);
			} else {
				$pdf->SetXY(16, 130);
				$pdf->MultiCell(39,4,($data['marking_nos']),0,'L',false);
			}
			////double countener /////

			$count = $this->CI->m_print_out_bl->get_count_double($sp);
			if($count->num_rows() > 0 ) {
			$pdf->SetXY(17, 182);
			  $pdf->write(0, 'CONTAINER / SEAL NO');
			  $pdf->Ln(2.5);
				foreach($count->result() as $row ) {
					$pdf->SetX(17);
					$pdf->MultiCell(60,1.5, strtoupper($row->count_no).' / '. strtoupper($row->seal_no).' / '. strtoupper($row->count_size),0,'L',false);
					$pdf->Ln();
				}
			}
	  		else if ($last_container != '')
	  		{  
	  			$pdf->SetXY(17, 197);
			   $pdf->write(0, 'CONTAINER / SEAL NO');
	  			$pdf->SetXY(17, 200);
				$pdf->MultiCell(65, 4, $last_container.' / '.$data['type_cont_size']);
	  		}
	  		
	  	   if($fcl=='FCL') {

             	$pdf->SetXY(56, 131);
             	$dbl = $this->CI->afg_m_stuffing_plan->get_double($sp);
             	if($dbl->num_rows() > 0 ) {
					$pdf->SetXY(56, 214);
					foreach($dbl->result() as $row ) {
				   $pdf->Write(0,  $row->count_num.' ( '.$this->CI->fungsi->num_to_words( $row->count_num,'',0,'').' ) '.' '.'X'.' '.$row->count_type.' ');
					}
			 } else {  
					$pdf->SetXY(56, 214);
					$pdf->MultiCell(120,3, $data['no_pack_qty'].' ( '.$this->CI->fungsi->num_to_words($data['no_pack_qty'],'',0,'').' ) '.' '.'X'.' '.$data['no_pack_unit'].' ONLY');
       				 }
           	}
     			else
           	{
				$pdf->SetXY(56, 214);
				$pdf->MultiCell(120,3, $data['no_pack_qty'].' ( '.$this->CI->fungsi->num_to_words($data['no_pack_qty'],'',0,'').' ) '.$data['no_pack_unit'].' ONLY');
			}
			
			if($data['express_release'] == "TRUE") 
			{
				$pdf->SetXY(99, 187);
				$pdf->SetFont('Arial','B',10);
				$pdf->write(0, '" EXPRESS RELEASE "');
			}
			$pdf->SetFont('Arial','',9);
			if($data['shipped_on_board'] == "TRUE")
			{
				$pdf->SetXY(145, 165);
				$pdf->write(0, 'SHIPPED ON BOARD');
				$pdf->SetXY(145, 168);
				$pdf->write(0, 'FROM '.$port_of->port_of_loading);
				$pdf->SetXY(145, 171);
				$pdf->write(0, 'ON '.strtoupper($tanggal));
				$pdf->SetXY(145, 174);
				$pdf->write(0, 'BY '.$connecting->vessel);
				//$pdf->SetXY(140, 172);
				//$pdf->write(0, 'TO '.$port_of->port_of_discharge);
			}

			if($fcl=='FCL') {
				$pdf->SetXY(80, 130);
				$pdf->write(0, 'SHIPPER LOAD, COUNT & SEAL CONTAINER,');
				$pdf->SetXY(80, 133);
				$pdf->write(0, 'SAID TO CONTAIN :');
				if($attc_discription > 15)
				{
				$pdf->SetXY(95, 138);
				$pdf->MultiCell(40,3,'AS PER LIST ATTACHED');
				}
				else
				{
				$pdf->SetXY(80, 136);
				$pdf->MultiCell(60,4,($data['desc_goods']),0,'L');
				}
			}
			else
			{
				$pdf->SetXY(70, 130);
				$pdf->write(0,'SAID TO CONTAIN :');
				if($attc_discription > 15)
				{
				$pdf->SetXY(95, 138);
				$pdf->MultiCell(40,3,'AS PER LIST ATTACHED');
				}
				else
				{
				$pdf->SetXY(70, 131);
				$pdf->MultiCell(60,4,($data['desc_goods']),0,'L');
				}
			}
			$pdf->SetXY(158, 135);
			$pdf->write(0, 'GW :');
			$pdf->SetXY(177, 134);
			$pdf->Cell(13,2,$this->CI->fungsi->pecah3($data['weight'],','),0,0,'R',false,'');
			$pdf->SetXY(188, 135);
			$pdf->write(0, ' KGS');

			if($data['net_weight_tidak_dicetak'] == "FALSE" ) {
				$pdf->SetXY(158, 140);
				$pdf->write(0,'NW : ');
				$pdf->SetXY(177, 139);
				//$pdf->Cell(13,2,number_format($data['netto'],3,'.'.','),0,0,'R',false,'');
				//$pdf->Cell(13,2,$data['netto'],0,0,'R',false,'');
				$pdf->Cell(13,2,$this->CI->fungsi->pecah3($data['netto'],','),0,0,'R',false,'');
				$pdf->SetXY(188, 140);
				$pdf->write(0, ' KGS');
			}

        if($data['bl_meas'] != '' ) {
			$pdf->SetXY(158, 145);
			$pdf->write(0,'MEAS : ');
			$pdf->SetXY(177, 144);
			$pdf->Cell(13,2,$this->CI->fungsi->pecah4($data['bl_meas'],','),0,0,'R',false,'');
			$pdf->SetXY(188, 145);
			$pdf->write(0, ' M');
			$pdf->SetXY(191, 144);
			$pdf->SetFont('Arial','',6);
			$pdf->write(0, '3');  }

			$pdf->SetFont('Arial','',9);
			$pdf->SetXY(18, 226);
			$pdf->write(0, 'FREIGHT');
			$pdf->SetXY(116, 244);
			$pdf->write(0,$payable);
			$q = $this->CI->dbx->get_where('tbl_detail',array('detail_id'=>$spd));
			if($q->row()->payable == 'AS ARRANGED')
				{
				$pdf->SetXY(21, 208);
				$pdf->write(0, 'AS ARRANGED');
				}


			$pdf->SetXY(116, 218);
			$pdf->MultiCell(40,3, $data['freights_charges']);

			$freight_payable = $data['freight'];
			if($freight_payable == "PREPAID") {
	  			$pdf->SetXY(70, 226);
	  			$pdf->write(0,$freight_payable);
			} else {
	  			$pdf->SetXY(88, 226);
	  			$pdf->write(0,$freight_payable);
			}
			$caya = $this->CI->m_print_out_bl->get_caya_caya($spd);
			$pdf->SetXY(116, 226);
			$pdf->write(0, $caya);

			if($data['cetak_jml_doc_origi'] == "TRUE") {
	  			$pdf->SetXY(116, 235);
	  			$pdf->write(0,'3(THREE) ORIGINAL');
			}

			$pdf->SetXY(116, 253);
			$pdf->Write(0,strtoupper($this->CI->session->userdata('region_id').', '.$tanggal));

			$pdf->SetXY(123, 265);
			$pdf->write(0,'PT HUMBLE & SINCERE LOGISTICS');
			$pdf->SetXY(123, 269);
			$pdf->write(0,'JL. OTISTA III No.18 CIPINANG CEMPEDAK');
			$pdf->SetXY(123, 273);
			$pdf->write(0,'JATINEGARA JAKARTA TIMUR, INDONESIA');
			$pdf->SetXY(123, 277);
			$pdf->write(0,'TEL : +62 21 21011593  (Mr. RAMA)');
			$pdf->SetXY(123, 281);
			$pdf->write(0,'EMAIL : rama@hnslines.com');
			$pdf->SetXY(116, 285);
			$pdf->MultiCell(100,5,strtoupper($as_agent),0,'L');
			
			
		}
	}
	/// atachement file ///

	if($attc_discription > 15 || $attc_marks > 18 ) {
		$pdf->AddPage('P','A4');
		$pdf->SetAutoPageBreak(true, 4) ;
		$pdf->SetFont('Arial','B',10);
		$pdf->SetXY(10, 15);
		if ($code==12)
		{
		$pdf->write(0, 'PT HUMBLE & SINCERE LOGISTICS');
		}else{
		if	($this->CI->session->userdata('region_id')== 'Osline' || $this->CI->session->userdata('region_id')== "OSLSRG" || $this->CI->session->userdata('region_id')=="OSLSUB" || $this->CI->session->userdata('region_id')=="OSLBDG" )	
		{$pdf->write(0, 'OCEAN SKY LINE');}else{$pdf->write(0, 'PT. GATEWAY CONTAINER LINE');}	
		}
		$pdf->SetXY(10, 22);			
		$pdf->write(0, 'ATTACHMENT');
		$pdf->SetFont('Arial','',9);
		$pdf->SetXY(10, 30);			
		$pdf->write(0, 'BL NO.');
		$pdf->SetXY(40, 30);			
		$pdf->write(0, ':');
		$pdf->SetXY(45, 30);			
		$pdf->write(0, $bl_no);
		$pdf->SetXY(10, 35);			
		$pdf->write(0, 'SHIPPER');
		$pdf->SetXY(40, 35);
		$pdf->write(0, ':');
		$pdf->SetXY(45, 35);

		//$shipper = $this->CI->user_model->get_shipper_by_id_shipper($data['shipper'],$data['shipper_print'])->shipper_name;
		$shipper = explode("\n", $data['note']);
		if(count($shipper)>0) {
			$shippernya = $shipper[0];
		}
		else 
		{
			$shippernya = "";
		}
		
		$pdf->Cell(7,0, strtoupper($shippernya));

		////print_r($data);
		//return false;
		//$k = array(
			//$data['shipper'],$data['shipper_print']
		//);
		////print_r($k);
		//return false;

		//$pdf->Write(0, strtoupper($shipper));
		$pdf->SetXY(10, 40);			
		$pdf->write(0, 'FEEDER VESSEL');
		$pdf->SetXY(40, 40);			
		$pdf->write(0, ':');
		$pdf->SetXY(45, 40);			
		$pdf->write(0, $connecting->vessel);
		
		$pdf->Line(10,45,200,45);
		$pdf->Line(10,46,200,46);
		
			$pdf->SetFont('Arial','BU',8);
			$pdf->SetXY(100, 55);			
			$pdf->write(0, 'DESCRIPTION OF GOODS :');
			$pdf->SetFont('Arial','',9);
			if($fcl=='FCL') {
				$pdf->SetXY(100, 60);
				$pdf->write(0, 'SHIPPER LOAD, COUNT & SEAL CONTAINER,');
				$pdf->SetXY(100, 64);
				$pdf->write(0, 'SAID TO CONTAIN :');
				$pdf->SetXY(100, 68);
				$text = html_entity_decode($data['desc_goods'], ENT_QUOTES, 'UTF-8');

					// Buang karakter non-printable dulu
					$text = preg_replace('/[^\P{C}\n]+/u', '', $text);

					// Konversi dengan ignore
					$text = iconv('UTF-8', 'Windows-1252//IGNORE', $text);
				$pdf->MultiCell(90,4,($text),'','L',false);
				}
			else
				{
					$pdf->SetXY(100, 60);
					$pdf->write(0,'SAID TO CONTAIN :');
					$pdf->SetXY(100, 64);
					$text = html_entity_decode($data['desc_goods'], ENT_QUOTES, 'UTF-8');

					// Buang karakter non-printable dulu
					$text = preg_replace('/[^\P{C}\n]+/u', '', $text);

					// Konversi dengan ignore
					$text = iconv('UTF-8', 'Windows-1252//IGNORE', $text);
					$pdf->MultiCell(90,4,($text),'','L',false);
				}
			$pdf->SetFont('Arial','BU',8);
			$pdf->SetXY(10, 55);			
			$pdf->write(0, 'MARK AND NOS  :');
		
			$pdf->SetFont('Arial','',9);
			$pdf->SetXY(10, 60);
			$pdf->MultiCell(75,4,$data['marking_nos'],'','L',false);
		
		
		

	}
        return (string)$kode_ref;
    }
}
