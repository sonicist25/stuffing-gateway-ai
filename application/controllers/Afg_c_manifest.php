<?php
class Afg_c_manifest extends CI_Controller {
	var $title = 'Stuffing Plan';
	
	public function __construct()
	{
		parent::__construct();
		$this->fungsi->restrict();
		$this->load->model(array(
			'user_model',
			'afg_m_stuffing_plan',
			'afg_m_manifest',
		));
		$this->fungsi->dbx = $this->afg_m_manifest->dbx;
		$this->dbx = $this->afg_m_manifest->dbx;		
	}
	
			
	function create_data ()
    {
        $id = $this->input->get('id');
		$data = $this->afg_m_manifest->create_data($id);
		$remove = array("\n", "\r\n", "\r",'/\s+/','\s',"<p>", "</p>", "<h1>", "</h1>");
		
		
		foreach($data as $key => $row)
		{			
			$re = $this->dbx->query("SELECT COUNT(id) AS jumlah FROM count_double WHERE id_sp = '$id'")->row();
			if($re->jumlah==0){
				$Countainer=explode("/",$row['Container']);
				unset($row['Container']);
				if(count($Countainer)>=2 ) {
					$row["Container1"]=$Countainer[0];
					$row["Seal1"]=$Countainer[1];
				} else {
					$row["Container1"]='';
					$row["Seal1"]='';
				}
				
				$ER = $row['express_release'];
				$CO = $row['cetak_jml_doc_origi'];
				if($ER=='TRUE'){
					$row['er'] = 'TelexRelease';
				}elseif($CO=='TRUE'){
					$row['er'] = 'OriginalBL';
				}else{
					$row['er'] = 'Shipped On Board';
				}

				$SH = $row['shipment_type_multi'];
				if($SH<>'Coload'){
					$row['st'] = 'Forwarder';
				}else{
					$row['st'] = 'Coloader';
				}

				$cy = $row['cy_cy'];
				$cfs = $row['cfs_cfs'];
				$diff = $row['diff_status_1'];
				if($cy=='TRUE'){
					$row['modefrom'] = 'CY';
				}elseif($cfs=='TRUE'){
					$row['modefrom'] = 'CFS';
				}elseif($diff<>''){
					$row['modefrom'] = $row['diff_status_1'];
				}else{
					$row['modefrom'] = "nothing";
				}

				$rs = $this->dbx->query("SELECT COUNT(num) AS jum FROM trnstuffingplandetail WHERE sp_id = '$id'")->row();
	        	$i = $rs->jum;

	        	$rs2 = $this->dbx->query("SELECT COUNT(id) AS jum FROM x_finance_item WHERE bl_no = '$row[hb_l]'")->row();
	        	$j = $rs2->jum;
			}else{
				$row["Container1"]='';
				$row["Seal1"]='';
				echo "<meta http-equiv='refresh' content='0; url=".base_url()."afg_c_stuffing_plan'>";
			}
			$q[]=str_replace($remove," ",$row);
			
		}
        // Load XML writer library
        $this->load->library('Xml_writer');
        
        // Initiate class
        $xml = new Xml_writer();                
	    $xml->setRootName('seajobfile');
	    $xml->initiate();
	    
	    foreach($q as $row)
		{
			$tanggal = date("Y-m-d", strtotime($row['closing_date_time']));
			$tanggal2 = date("Y-m-d", strtotime($row['stuffing_date']));
			$tanggal3 = date("Y-m-d", strtotime($row['etd_jkt']));
			$tanggal4 = date("Y-m-d", strtotime($row['feeder_vessel_eta_date']));
			$tanggal5 = date("Y-m-d", strtotime($row['etd_jkt']));
			

	    	$xml->addNode('vessel', $row['ves']);	        	       
	    	$xml->addNode('voyage', $row['voyage']);
	    	$xml->startBranch('liner');
		        $xml->addNode('name', $row['carrier']);		        
	        $xml->endBranch();
	        $xml->addNode('cfsclosingdate', $tanggal);
	        $xml->addNode('cyclosingdate', $tanggal2);
	        $xml->addNode('etddate', $tanggal3);
	        $xml->addNode('etadate', $tanggal4);
	        $xml->startBranch('loadingport');
		        $xml->addNode('code', $row['city_id']);		        
		        $xml->addNode('name', $row['port_loading']);
		        $xml->startBranch('countrycode');
			        $xml->addNode('code', $row['country']);		        
			        $xml->addNode('name', $row['country_id']);		        		        		        
				     $xml->endBranch();	
				     $xml->addNode('uncode', $row['origin_id']);        
	        $xml->endBranch();
	        $xml->startBranch('dischargeport');
		        $xml->addNode('code', $row['city_id2']);		        
		        $xml->addNode('name', $row['destination']);
		        $xml->startBranch('countrycode');
			        $xml->addNode('code', $row['country2']);		        
			        $xml->addNode('name', $row['country_id2']);		        	        		        
				     $xml->endBranch();	 	        
	        $xml->endBranch();
	        $xml->startBranch('loadingportagent');
		        $xml->addNode('name', $row['agent_company_name']);		        
		        $xml->addNode('address', $row['agent_company_address']);		        
		        $xml->addNode('tel', "");		        
		        $xml->addNode('fax', "");		        
	        $xml->endBranch();
	        $xml->startBranch('handlingagent');
		        $xml->addNode('name', $row['agent_company_name2']);		        
		        $xml->addNode('address', $row['agent_company_address2']);		        
		        $xml->addNode('tel', "");		        
		        $xml->addNode('fax', "");		        
	        $xml->endBranch();
	        $xml->startBranch('container', array('id' => '1'));
		        $xml->addNode('containerno', $row['Container1']);
		        $xml->addNode('sealno', $row['Seal1']);
		        $xml->addNode('size', $row['type_cont_size']);
		        $xml->addNode('type', "");
		        $xml->addNode('linersono', "");
		        $xml->addNode('coload', "False");
	        $xml->endBranch();
	        
	        if ($i>0)
			{	
				$rx = $this->dbx->query("SELECT 					
					trnstuffingplandetail.num AS num,																					 															
					trnstuffingplandetail.hb_l AS hb_l,
					trnstuffingplan.port_loading AS port_loading,
					tableName.country_id AS country_id,
					tableName.city_id AS origin_id,
					tableName.country_id AS country,
					trnstuffingplandetail.destination AS destination,
					trnstuffingplandetail.shipper_print AS shipper_print,
					trnstuffingplandetail.note AS note,
					trnstuffingplandetail.consignee AS consignee,
					trnstuffingplandetail.consignee_add AS consignee_add,	
					trnstuffingplandetail.notify_add AS notify_add,
					trnstuffingplandetail.express_release AS express_release,
					trnstuffingplandetail.cetak_jml_doc_origi AS cetak_jml_doc_origi,
					trnstuffingplan.shipment_type_multi AS shipment_type_multi,
					trnstuffingplandetail.freight AS freight,
					trnstuffingplandetail.weight AS weight,
					trnstuffingplandetail.bl_meas AS bl_meas,
					trnstuffingplandetail.no_pack_qty AS qty,
					trnstuffingplandetail.no_pack_unit AS unit,
					tbl_detail.cy_cy AS cy_cy,
					tbl_detail.cfs_cfs AS cfs_cfs,
					tbl_detail.diff_status_1 AS diff_status_1,
					trnstuffingplandetail.marking_nos AS marking_nos,
					trnstuffingplandetail.no_of_pack AS no_of_pack,
					trnstuffingplandetail.desc_goods AS desc_goods,
					tbl_detail.port_of_receive AS port_of_receive,
					tbl_detail.port_of_loading AS port_of_loading,
					tbl_detail.port_of_discharge AS port_of_discharge,
					tbl_detail.port_of_delivery AS port_of_delivery,
					tbl_detail.payable AS payable,
					tbl_detail.shipper_name AS shipper_name,
					tbl_detail.shipper_address AS shipper_address,
					trnstuffingplandetail.agent_code AS agent_code,
					trnstuffingplan.region_id AS region_id,
					x_finance_item.code AS code,
					x_finance_item.item AS item,
					x_finance_item.price AS price,
					x_finance_item.pre_col AS prep_col
				FROM trnstuffingplandetail 
					INNER JOIN trnstuffingplan ON trnstuffingplan.sp_id = trnstuffingplandetail.sp_id
					LEFT JOIN tableName ON tableName.name = trnstuffingplandetail.destination
					INNER JOIN tbl_detail ON tbl_detail.detail_id = trnstuffingplandetail.sp_detail_id
               LEFT JOIN x_finance_item ON x_finance_item.kode_ref = trnstuffingplan.kode_ref
				WHERE trnstuffingplandetail.sp_id = '$id' GROUP BY trnstuffingplandetail.hb_l ORDER BY trnstuffingplandetail.num");
			    
				$a=1;
				foreach ($rx->result() as $rg) {

				$xml->startBranch('billoflading', array('id' => $a));
			        $xml->addNode('blno', $rg->hb_l);
			        $xml->startBranch('destination');
				        $xml->addNode('code', $rg->origin_id);		        
				        $xml->addNode('name', $rg->destination);		        
				        $xml->startBranch('countrycode');
					        $xml->addNode('code', $rg->country);		        
					        $xml->addNode('name', $rg->country_id);		        	        		        
				        $xml->endBranch();
				        $xml->addNode('uncode', $rg->origin_id);	
			        $xml->endBranch();
			        $xml->startBranch('shipper');
			        	$xml->startBranch('code');
					        $xml->addNode('name', $rg->shipper_name);		        
					        $xml->addNode('address', $rg->shipper_address);		        
					        $xml->addNode('tel', '');		        
					        $xml->addNode('fax', "");		        
				        $xml->endBranch();
				        $xml->addNode('details', $rg->note);		        				        
			        $xml->endBranch();
			        $xml->startBranch('consignee');
			        	$xml->startBranch('code');
					        $xml->addNode('name', $rg->consignee);		        
					        $xml->addNode('address', "");		        
					        $xml->addNode('tel', "");		        
					        $xml->addNode('fax', "");		        
				        $xml->endBranch();
				        $xml->addNode('details', $rg->consignee_add);		        				        
			        $xml->endBranch();
			        $xml->startBranch('notifyparty');			        	
				        $xml->addNode('details', $rg->notify_add);		        				        
			        $xml->endBranch();
			        $xml->addNode('releasemode', $row['er']);		        				        
			        $xml->addNode('issueby', $row['st']);		        				        
			        $xml->addNode('ofppcc', $rg->freight);		        				        
			        $xml->addNode('servicemodefrom', $row['modefrom']);		        				        
			        $xml->addNode('servicemodeto', $row['modefrom']);		        				        
			        $xml->addNode('marksandnos', $rg->marking_nos);		        				        
			        $xml->addNode('noofpkgs', $rg->no_of_pack);		        				        
			        $xml->addNode('descofgoods', $rg->desc_goods);		        				        
			        $xml->addNode('freehand', "TRUE");		        				        
			        $xml->addNode('receiptplace', $rg->port_of_receive);		        				        
			        $xml->addNode('loadingportname', $rg->port_of_loading);		        				        
			        $xml->addNode('dischargeportname', $rg->port_of_discharge);		        				        
			        $xml->addNode('deliveryplace', $rg->port_of_delivery);		        				        
			        $xml->addNode('payableat', $rg->region_id);
			        $xml->startBranch('handlingagent');
				        $xml->addNode('name', $row['agent_company_name']);		        
				        $xml->addNode('address', $row['agent_company_address']);		        
				        $xml->addNode('tel', "");		        
				        $xml->addNode('fax', "");		        
			        $xml->endBranch();		        				        			        
			        $xml->addNode('issueplace', $row['region_id']);		        				        
			        $xml->addNode('issuedata', $tanggal5);		        				        
			        $xml->addNode('onboarddate', $tanggal5);		        				        
			        $xml->addNode('noofcopies', "3");		        				        
			        $xml->addNode('totalinwords', $rg->qty.' '.'('.$this->fungsi->num_to_words($rg->qty,'',0,'').')'.$rg->unit.' '.'ONLY');		        				        
			        $xml->addNode('printcbm', "TRUE");		        				        
			        $xml->addNode('printkgs', "TRUE");		        				        
			        $xml->addNode('printcontainercbmkgs', "TRUE");		        				        
			        $xml->addNode('printcontainertype', "TRUE");
			        
			        if($j>0){
			        	$rs2 = $this->dbx->query("SELECT * FROM x_finance_item WHERE bl_no = '$rg->hb_l' AND (jenis = 'CREDIT NOTE' OR jenis = 'DEBIT NOTE')");
			        	$b = 1;
			        	foreach ($rs2->result() as $k) {
			        		$xml->startBranch('incomecost', array('lineno' => $b));
					        	$xml->startBranch('charge');
							        $xml->addNode('code', $k->code);		        
							        $xml->addNode('name', $k->item);		        					        
						        $xml->endBranch();
						        $xml->addNode('calculatemethod', "Total");
						        $xml->startBranch('currency');
							        $xml->addNode('code', "USD");		        
							        $xml->addNode('name', "US DOLLAR");		        					        
						        $xml->endBranch();
						        $xml->addNode('rate', $k->price);		        				        		       
						        $xml->addNode('ppcc', $k->pre_col);		        				        		       
					        $xml->endBranch();
					    	$b++;
			        	}
			        }
			    

		        $xml->endBranch();
				$a++;
						}
			}
			if($i>0){
			        	$rs = $this->dbx->query("SELECT * FROM trnstuffingplandetail WHERE trnstuffingplandetail.sp_id = '$id' ORDER BY trnstuffingplandetail.num");
			        	$b = 1;
			        	foreach ($rs->result() as $rk) {
			        	$xml->startBranch('blcontainer', array('blid' => $b,'containerid' => "1"));
					$xml->addNode('cfs', "TRUE");
					$xml->startBranch('pkgs');
				        $xml->addNode('pkgs',$rk->no_pack_qty);		        
				        $xml->addNode('unit',$rk->no_pack_unit);		        					        
			        $xml->endBranch();
					$xml->addNode('kgs',$rk->weight);
					$xml->addNode('cbm',$rk->bl_meas);
					$xml->addNode('print', "TRUE");
				$xml->endBranch();
				$b++;
			}
			}
        }
        // Pass the XML to the view
        $data = array();
        $data['xml'] = $xml->getXml(FALSE);
        $this->load->view('xml_metafest', $data);
    }
	
	
	
	function show_manifest($kode_ref)
	{
		
		$q = $this->afg_m_stuffing_plan->show_manifest($kode_ref);
		
		$this->load->library('FPDF_MC_Table');
		$pdf=new FPDF_MC_Table();	
			
		$pdf->SetMargins(5,5);
		$pdf->AliasNbPages();
		$pdf->setDataHeader(array(
			'report'=>'12',
			'vessel'=>$q[0]->vessel.' '.$q[0]->voyage,
			'etd_jkt'=>$this->fungsi->jin_date_str_strip($q[0]->etd_jkt),
			'cont_seal_no'=>$q[0]->cont_seal_no,
			'port_loading'=>$q[0]->port_loading,
			'transhipment_port'=>$q[0]->transhipment_port,
			'destination'=>$q[0]->destination,
		));
		$pdf->AddPage('L','A4');
		$pdf->SetFont('Arial','',8.5); 
		$pdf->SetWidths(array(10,30,60,40,70,30,25,20));
		$pdf->SetAligns(array('C','C','L','L','L','L','L','C'));
		$ttl_gw = array();
		$ttl_nw = array();
		$ttl_ms = array();
		$i=1;
		////print_r($q[0]);
		foreach($q[1] as $row ) 
		{
			$hbl = $row->hb_l;
			
			//if($row->hbl=="F") {
				//$hbl = $q[0]->mother_bl;
			//}
			
			$pdf->Row(array(
				$i,
				$hbl,
				strtoupper($row->note. htmlentities("\n\n").$row->consignee. htmlentities("\n").$row->consignee_add. htmlentities("\n\n").$row->notify. htmlentities("\n").$row->notify_add),
				strtoupper($row->marking_nos),
				strtoupper($row->desc_goods),
				strtoupper($row->destination),
				'GW : '. htmlentities("\n ").$this->fungsi->pecah3($row->weight,',').' KGS'.htmlentities("\n\n").
				'NW : '. htmlentities("\n ").$this->fungsi->pecah3($row->netto,',').' KGS'. htmlentities("\n\n").
				'MEAS : '. htmlentities("\n ").$this->fungsi->pecah4($row->bl_meas,',').' M3'. htmlentities("\n\n"),
				$row->freight,
			));
			$i++;
			$ttl_gw[] = $row->weight;
			$ttl_nw[] = $row->netto;
			$ttl_ms[] = $row->bl_meas;
			$ttl_pkgs[] = $row->no_pack_qty;
		}
		$pdf->ln(1);
		$pdf->Cell(210,5,'','TLR',0,'L');
		$pdf->Cell(30,5,'TOTAL GW : ','TL',0,'R');
		$pdf->Cell(25,5,$this->fungsi->pecah3(array_sum($ttl_gw),','),'T',0,'R');
		$pdf->Cell(20,5,'','TR',1,'R');

		$pdf->Cell(210,5,'','L',0,'L');
		$pdf->Cell(30,5,'TOTAL NW : ','L',0,'R');
		$pdf->Cell(25,5, $this->fungsi->pecah3(array_sum($ttl_nw),','),0,0,'R');
		$pdf->Cell(20,5,'','R',1,'R');

		$pdf->Cell(210,5,'','L',0,'L');
		$pdf->Cell(30,5,'TOTAL MEAS : ','L',0,'R');
		$pdf->Cell(25,5,$this->fungsi->pecah4(array_sum($ttl_ms),','),'0',0,'R');
		$pdf->Cell(20,5,'','R',1,'R');

		$pdf->Cell(210,5,'','LBR',0,'L');
		$pdf->Cell(30,5,'TOTAL PKGS : ','LB',0,'R');
		$pdf->Cell(25,5,array_sum($ttl_pkgs),'B',0,'R');
		$pdf->Cell(20,5,'','RB',1,'R');

		$pdf->Output(md5(time()).'.pdf','I');

	}
	
	function show_excel($kode_ref) {	
		$reg_id = $this->session->userdata('region_id');
		$q = $this->afg_m_stuffing_plan->show_manifest($kode_ref);
		header("Cache-Control: no-cache, no-store, must-revalidate");  
		header("Content-Type: application/vnd.ms-excel");  
		header("Content-Disposition: attachment; filename=Manifest.xls");
 		echo link_tag('public/theme/css/report.css', 'stylesheet', 'text/css').
		'<table summary="" width="100%" align="center">
		<tr><td class="space"></td><td class="judul" td align="center" width="400" colspan="10">MANIFEST PT. GATEWAY INDONESIA</td></tr>
	   <tr><td class="space"></td><td class="title" td align="center" width="400" colspan="10">LCL CONTAINER MANIFEST</td></tr>
	   <tr>
			<td class="space"></td>
			<td align="left" width="200"  colspan="5">VESSEL / VOYAGE        : '.$q[0]->vessel.' / '.$q[0]->voyage.'</td>
			<td align="left" width="200"  colspan="5">CARRIER        : '.$q[0]->carrier.'</td>
		</tr>
		 <tr>
			<td class="space"></td>
			<td align="left" width="200"  colspan="5">PORT OF LOADING : '.$q[0]->port_loading.'</td>
			<td align="left" width="200"  colspan="5">PORT OF UNLOADING : '.$q[0]->destination.'</td>
		</tr>
		 <tr>
			<td class="space"></td>
			<td align="left" width="200" colspan="5">ETD : '.$this->fungsi->jin_date_str_strip($q[0]->etd_jkt).'</td>
			<td align="left" width="200" colspan="5">MBL NO : '.$q[0]->mother_bl.'</td>
		</tr>
		 <tr>
			<td class="space"></td>
			<td align="left" width="200" colspan="5">CONTAINER / SEAL NO: '.$q[0]->cont_seal_no.'</td>
			<td align="left" width="200" colspan="5">TOTAL PACKAGING : </td>
		</tr>
		<tr>
			<td class="space"></td>
			<td align="left" width="200" colspan="5">TOTAL WEIGHT :</td>
			<td align="left" width="200" colspan="5">TOTAL VOLUME :</td>
		</tr>
		<tr>
			<td class="space"></td>
			<td align="center" width="50" class="header">NO</td>
			<td align="center" width="100" class="header">BL NO</td>
			<td align="center" width="120" class="header">FINAL DESTINATION</td>
			<td align="center" width="150" class="header">SHIPPER</td>
			<td align="center" width="150" class="header">CONSIGNEE</td>
			<td align="center" width="150" class="header">NOTIFY</td>
			<td align="center" width="150" class="header">MARK</td>
			<td align="center" width="350" class="header">DESCRIPTION OF GOOD</td>
			<td align="center" width="80" class="header">KGS</td>
			<td align="center" width="80" class="header">CBM</td>
			<td align="center" width="80" class="header">QTY</td>
			<td align="center" width="80" class="header">UNIT</td>
			<td align="center" width="150" class="header">REMARK</td>
		</tr>
		<tr><td style="border:none; height: 5px" colspan="11"></td></tr>';
		$i=1;	
		$n=0;
		foreach($q[1] as $row ) {
		$ttl_gw[] = $row->weight;
			$ttl_nw[] = $row->netto;
			$ttl_ms[] = $row->bl_meas;
			$ttl_pkgs[] = $row->no_pack_qty;
			$hbl = $row->hb_l;
			
			//if($row->hbl=="F") {
				//$hbl = $q[0]->mother_bl;
				//}
			echo 
			'<tr>
				<td class="space"></td>
				<td align="center">'.$i.'</td>
				<td align="center">'.$hbl.'</td>
				<td align="center">'.strtoupper($row->destination).'</td>
				<td>'.strtoupper($row->note).'</td>
				<td>'.strtoupper($row->consignee_add).'</td>
				<td>'.strtoupper($row->notify_add).'</td>
				<td>'.strtoupper($row->marking_nos).'</td>
				<td>'.strtoupper($row->desc_goods).'</td>
				<td align="center">'.$row->weight.'</td>
				<td align="center">'.$row->bl_meas.'</td>
				<td align="center">'.$row->no_pack_qty.'</td>
				<td align="center">'.$row->no_pack_unit.'</td>
			<td ></td>
			</tr>';
			$i++;$n++;
		}
		
		echo '<tr><td style="border:none; height: 5px" colspan="11"></td></tr>
         <tr>
			<td class="space"></td>
			<td align="center" width="1090" class="header" colspan="8">TOTAL</td>
			<td align="center" width="80" class="header">'.$this->fungsi->pecah3(array_sum($ttl_gw),',').'</td>
			<td align="center" width="80" class="header">'.$this->fungsi->pecah3(array_sum($ttl_ms),',').'</td>
			<td align="center" width="80" class="header">'.array_sum($ttl_pkgs).'</td>
			<td align="right" width="80" class="header"></td>
			<td align="right" width="150" class="header"></td>
		</tr>';

		echo '</table>';
		exit();
	}
	
function create_data_d()
    {
        $id = $this->input->get('id');
		//print_r($id);
		//die();
		$data = $this->afg_m_manifest->create_data($id);
		//print_r($this->dbx->last_query());
		//die();
		$remove = array("\n", "\r\n", "\r",'/\s+/','\s',"<p>", "</p>", "<h1>", "</h1>");
		
		
		foreach($data as $key => $row)
		{			
			$re = $this->dbx->query("SELECT COUNT(id) AS jumlah FROM count_double WHERE id_sp = '$id'")->row();
			if($re->jumlah==0){
				$Countainer=explode("/",$row['Container']);
				unset($row['Container']);
				if(count($Countainer)>=2 ) {
					$row["Container1"]=$Countainer[0];
					$row["Seal1"]=$Countainer[1];
				} else {
					$row["Container1"]='';
					$row["Seal1"]='';
				}
				
				$ER = $row['express_release'];
				$CO = $row['cetak_jml_doc_origi'];
				if($ER=='TRUE'){
					$row['er'] = 'TelexRelease';
				}elseif($CO=='TRUE'){
					$row['er'] = 'OriginalBL';
				}else{
					$row['er'] = 'Shipped On Board';
				}

				$SH = $row['shipment_type_multi'];
				if($SH<>'Coload'){
					$row['st'] = 'Forwarder';
				}else{
					$row['st'] = 'Coloader';
				}

				$cy = $row['cy_cy'];
				$cfs = $row['cfs_cfs'];
				$diff = $row['diff_status_1'];
				if($cy=='TRUE'){
					$row['modefrom'] = 'CY';
				}elseif($cfs=='TRUE'){
					$row['modefrom'] = 'CFS';
				}elseif($diff<>''){
					$row['modefrom'] = $row['diff_status_1'];
				}else{
					$row['modefrom'] = "nothing";
				}

				$rs = $this->dbx->query("SELECT COUNT(num) AS jum FROM trnstuffingplandetail WHERE sp_id = '$id'")->row();
	        	$i = $rs->jum;

	        	$rs2 = $this->dbx->query("SELECT COUNT(id) AS jum FROM x_finance_item WHERE bl_no = '$row[hb_l]'")->row();
	        	$j = $rs2->jum;
			}else{
				$row["Container1"]='';
				$row["Seal1"]='';
				echo "<meta http-equiv='refresh' content='0; url=".base_url()."afg_c_stuffing_plan'>";
			}
			$q[]=str_replace($remove," ",$row);
			
		}
        // Load XML writer library
        $this->load->library('xml_writer');
        
        // Initiate class
        $xml = new Xml_writer();                
	    $xml->setRootName('Data');
	    $xml->initiate();
	    
	foreach($q as $row)
	{
			$tanggal = date("Y-m-d", strtotime($row['closing_date_time']));
			$tanggal1 = date("Ymd", strtotime($row['tanggal_job']));
			$tanggal2 = date("Y-m-d", strtotime($row['stuffing_date']));
			$tanggal3 = date("Ymd", strtotime($row['etd_jkt']));
			$tanggal4 = date("Y-m-d", strtotime($row['feeder_vessel_eta_date']));
			$tanggal5 = date("Y-m-d", strtotime($row['etd_jkt']));
			
	$xml->startBranch('Manifest');
			$xml->addNode('ManifestDate', $tanggal1);
			$xml->addNode('Reference','');
			$xml->addNode('ProjectCode','');
			$xml->addNode('SailingDate', $tanggal3);
	    	$xml->addNode('Vessel', $row['ves']);	        	       
	    	$xml->addNode('Voyage', $row['voyage']);
	    	$xml->addNode('OverseasAgent', 'PT. GATEWAY CONTAINER LINE');
	    	$xml->startBranch('BLs');
		if ($i>0)
		{	
		$rx = $this->dbx->query("
					SELECT 					
						trnstuffingplandetail.num AS num,																					 															
						trnstuffingplandetail.hb_l AS hb_l,
						tableName.city_id AS city_id,	
						trnstuffingplan.port_loading AS port_loading,
						trnstuffingplan.mother_bl AS mbl,
						tableName.country_id AS country_id,
						tableName.code AS origin_id,
						tableName.country_id AS country,
						trnstuffingplandetail.destination AS destination,
						trnstuffingplandetail.shipper_print AS shipper_print,
						mstshipper.cp_phone AS cp_phone,
						trnstuffingplandetail.note AS note,
						trnstuffingplandetail.consignee AS consignee,
						trnstuffingplandetail.consignee_add AS consignee_add,	
						trnstuffingplandetail.notify_add AS notify_add,
						trnstuffingplandetail.express_release AS express_release,
						trnstuffingplandetail.cetak_jml_doc_origi AS cetak_jml_doc_origi,
						trnstuffingplan.shipment_type_multi AS shipment_type_multi,
						trnstuffingplandetail.freight AS freight,
						trnstuffingplandetail.weight AS weight,
						trnstuffingplandetail.netto AS netto,
						trnstuffingplandetail.bl_meas AS bl_meas,
						trnstuffingplandetail.no_pack_qty AS qty,
						trnstuffingplandetail.no_pack_unit AS unit,
						trnstuffingplandetail.incoterms AS incoterm,
						tbl_detail.cy_cy AS cy_cy,
						tbl_detail.cfs_cfs AS cfs_cfs,
						tbl_detail.diff_status_1 AS diff_status_1,
						trnstuffingplandetail.marking_nos AS marking_nos,
						trnstuffingplandetail.no_of_pack AS no_of_pack,
						trnstuffingplandetail.desc_goods AS desc_goods,
						trnstuffingplandetail.desc_print AS desc_print,
						tbl_detail.port_of_receive AS port_of_receive,
						tbl_detail.port_of_loading AS port_of_loading,
						tbl_detail.port_of_discharge AS port_of_discharge,
						tbl_detail.port_of_delivery AS port_of_delivery,
						tbl_detail.shipper_name AS shipper_name,
						tbl_detail.shipper_address AS shipper_address,
						tbl_detail.payable AS payable,
						trnstuffingplandetail.agent_code AS agent_code,
						trnstuffingplan.region_id AS region_id,
						x_finance_item.code AS code,
						x_finance_item.item AS item,
						x_finance_item.price AS price,
						x_finance_item.pre_col AS prep_col
					FROM trnstuffingplandetail 
						INNER JOIN trnstuffingplan ON trnstuffingplan.sp_id = trnstuffingplandetail.sp_id
						LEFT JOIN tableName ON tableName.name = trnstuffingplandetail.destination
						INNER JOIN tbl_detail ON tbl_detail.detail_id = trnstuffingplandetail.sp_detail_id
						LEFT JOIN stuffing_master.mstshipper AS mstshipper
							ON mstshipper.shipper_name = tbl_detail.shipper_name					
						LEFT JOIN x_finance_item ON x_finance_item.kode_ref = trnstuffingplan.kode_ref
					WHERE trnstuffingplandetail.sp_id = '$id'
					GROUP BY trnstuffingplandetail.hb_l
					ORDER BY trnstuffingplandetail.num
				");

				$a=1;
				foreach ($rx->result() as $rg) {

				$xml->startBranch('BL');
			        $xml->addNode('BLNo', $rg->hb_l);
			        $xml->addNode('MBLNo', $rg->mbl);
			        $xml->addNode('BLDate', $tanggal3);
			        $xml->addNode('GoodsLCNo','');
			        $xml->addNode('FPNo','');
			        $xml->addNode('Weight', $rg->weight);
			        $xml->addNode('NetWeight', $rg->netto);
			        $xml->addNode('Volume', $rg->bl_meas);
			        $xml->addNode('WeightUnit', "KGS");
			        $xml->addNode('VolumeUnit', "CBM");
			        $xml->addNode('Term',$rg->incoterm);
			        $xml->addNode('LoadPort',"IDTPP");
			        $xml->addNode('DiscPort',$rg->origin_id);
			        $xml->addNode('Origin',"IDTPP");
			        $xml->addNode('FinalDest',$rg->origin_id);
					if($rg->cfs_cfs == 'TRUE'){
			        $xml->addNode('LDTerm','CFSCFS');
					}else{
					$xml->addNode('LDTerm','CYCY');
					}
					if($rg->freight == 'PREPAID'){
					$xml->addNode('Payment','P');
					}else{
					$xml->addNode('Payment','C');	
					}
			        $xml->startBranch('Shipper');
				        $xml->addNode('ShpperCode'," ");		        
				        $xml->addNode('ShipperName', $rg->shipper_name);		        
				        $xml->addNode('ShipperAddress', $rg->shipper_address);		        
				        $xml->addNode('ShipperTel', ' ');		        
				        $xml->addNode('ShipperFax', ' ');		        
			        $xml->endBranch();
					$xml->addNode('Consignee', $rg->consignee_add);		        				        
					$xml->addNode('NotifyAddress', $rg->notify_add);
					$xml->addNode('Marks', $rg->marking_nos);
					$xml->addNode('Description', $rg->desc_goods);					
					$xml->addNode('Commodity', $rg->desc_print);
					$xml->addNode('HSCode', " ");
					$xml->startBranch('FreeData');
				        $xml->addNode('POFDFreeDays', 0);		        
				        $xml->addNode('POLFreeDay',0);		        
			        $xml->endBranch();					
			        $xml->startBranch('ContainerData');
						$xml->startBranch('Container');
							$xml->addNode('ContainerType', $row['type_cont_size']);		        
							$xml->addNode('ContainerNo', $row['Container1']);
							$xml->addNode('SealNo', $row['Seal1']);
							$xml->addNode('APart','');
							$xml->addNode('NW', $rg->netto);
							$xml->addNode('GW', $rg->weight);
							$xml->addNode('QTY',$rg->qty);
							$xml->addNode('PackageUnit',trim($rg->unit));
							$xml->addNode('CBM',$rg->bl_meas);
						 $xml->endBranch();
			        $xml->endBranch();
				$xml->endBranch();					
					$a++;
						}
				$xml->endBranch();
			}
		}
		$xml->endBranch();
		// Pass the XML to the view
        $data = array();
        $data['xml'] = $xml->getXml(FALSE);
        $this->load->view('xml_metafest', $data);
	}
	

}