<!--

<div class="fix clearfix"></div>
<div id="loading" style="float: right; width: 100px; margin-right: 30px; margin-top: 10px;" ></div>

<div style="float: left; width: 700px; height: 30px; font-size: 11px; margin-left:20px; margin-top: 20px;">	 	 
	 <input type="button" id="btnSave" value="SAVE" style="width: 70px; height: 30px" onclick="save_stuff()" >
	 <input type="button" id="btnReset" value="RESET" style="width: 70; height: 30" onclick="_stuff()">    
</div>

<div style="clear: both"></div>
<div id="form_input" class="main-work">	
	<div style="float:left; width: 365px;">
		<div style="clear: both"></div>
		<div class="left_1">Kode Ref</div>
		<div class="left_2">:</div>
		<div class="left_3">
		<?php 
		$kode = $this->afg_m_stuffing_plan->ambil_kode()->result();
			$data['option_kode']['']=' ';
			foreach ($kode as $row) {	
				$data['option_kode'][$row->kode_ref]=$row->kode_ref;
			}
			echo  form_dropdown('dd_kode',$data['option_kode'],'','id="dd_kode" style="width: 170px;"');
		?>
		</div>
	    
	    <div style="clear: both"></div>
		<div class="left_1">Type of Transportation</div>
		<div class="left_2">:</div>
		<div class="left_3">
		<?php 
		$type = $this->afg_m_stuffing_plan->ambil_kode2()->result();
			$data['option_type']['']=' ';
			foreach ($type as $row) {	
				$data['option_type'][$row->type_of_trans]=$row->type_of_trans;
			}
			echo  form_dropdown('dd_type',$data['option_type'],'','id="dd_type" style="width: 170px;"');
		?>
		</div>

		<div style="clear: both"></div>
		<div class="left_1">Shipper</div>
		<div class="left_2">:</div>
		<div class="left_3">
		<?php 
		$shipper = $this->afg_m_stuffing_plan->ambil_kode()->result();
			$data['option_shipper']['']=' ';
			foreach ($shipper as $row) {	
				$data['option_shipper'][$row->shipper_print]=$row->shipper_print;
			}
			echo  form_dropdown('dd_shipper',$data['option_shipper'],'','id="dd_shipper" style="width: 170px;"');
		?>
		</div>

		<div style="clear: both"></div>
		<div class="left_1">Consignee</div>
		<div class="left_2">:</div>
		<div class="left_3">
		<?php 
		$consignee = $this->afg_m_stuffing_plan->ambil_kode()->result();
			$data['option_consignee']['']=' ';
			foreach ($consignee as $row) {	
				$data['option_consignee'][$row->consignee]=$row->consignee;
			}
			echo  form_dropdown('dd_consignee',$data['option_consignee'],'','id="dd_consignee" style="width: 170px;"');
		?>
		</div>

		<div style="clear: both"></div>
		<div class="left_1">AWB/BL</div>
		<div class="left_2">:</div>
		<div class="left_3">
		<?php 
		$awb = $this->afg_m_stuffing_plan->ambil_kode2()->result();
			$data['option_awb']['']=' ';
			foreach ($awb as $row) {	
				$data['option_awb'][$row->mother_bl]=$row->mother_bl;
			}
			echo  form_dropdown('dd_awb',$data['option_awb'],'','id="dd_awb" style="width: 170px;"');
		?>
		</div>


		<div style="clear: both"></div>
		<div class="left_1">HAWB/HBL</div>
		<div class="left_2">:</div>
		<div class="left_3">
		<?php 
		$hawb = $this->afg_m_stuffing_plan->ambil_kode()->result();
			$data['option_hawb']['']=' ';
			foreach ($hawb as $row) {	
				$data['option_hawb'][$row->hbl]=$row->hbl;
			}
			echo  form_dropdown('dd_hawb',$data['option_hawb'],'','id="dd_hawb" style="width: 80px;"');
		?>
		</div>

		<div style="clear: both"></div>
		<div class="left_1">ETD</div>
		<div class="left_2">:</div>
		<div class="left_3">
		<?php 
		$etd = $this->afg_m_stuffing_plan->ambil_kode2()->result();
			$data['option_etd']['']=' ';
			foreach ($etd as $row) {	
				$data['option_etd'][$row->etd_jkt]=$row->etd_jkt;
			}
			echo  form_dropdown('dd_etd',$data['option_etd'],'','id="dd_etd" style="width: 120px;"');
		?>
		</div>

	    <div style="clear: both"></div>
		<div class="left_1">ATD</div>
		<div class="left_2">:</div>
		<div class="left_3">
		<?php 
			$txtATD = array(
				'id'=>'txtATD',
				'name'=>'txtATD',					
				'style'=>'width: 80px',
			);
			echo form_input($txtATD);
		?>
		</div>	

		<div style="clear: both"></div>
		<div class="left_1">ETA Port of Discharge</div>
		<div class="left_2">:</div>
		<div class="left_3">
			<?php 
			$txtETA = array(
				'id'=>'txtETA',
				'name'=>'txtETA',					
				'style'=>'width: 80px',
			);
			echo form_input($txtETA);
		?>
		</div>	

		<div style="clear: both"></div>
		<div class="left_1">ATA Port of Discharge</div>
		<div class="left_2">:</div>
		<div class="left_3">
			<?php 
			$txtATA = array(
				'id'=>'txtATA',
				'name'=>'txtATA',					
				'style'=>'width: 80px',
			);
			echo form_input($txtATA);
		?>
		</div>	

		<div style="clear: both"></div>
		<div class="left_1">AJU#</div>
		<div class="left_2">:</div>
		<div class="left_3">
		<?php 		
		$txtAirLine = array(
			'id'=>'txtAirLine',
			'name'=>'txtAirLine',			
			'style'=>'width: 170px',
		);
		echo form_input($txtAirLine);			
		?>
		</div>	

		<div style="clear: both"></div>
		<div class="left_1">Pre-Alert Received</div>
		<div class="left_2">:</div>
		<div class="left_3">
			<?php 
			$txtPre = array(
				'id'=>'txtPre',
				'name'=>'txtPre',					
				'style'=>'width: 80px',
			);
			echo form_input($txtPre);
		?>
		</div>	
		

	</div>
	<div style="float: left;">     

		<div class="left_1">Draft PIB</div>
		<div class="left_2">:</div>
		<div class="left_3">
		<?php 
			$txtClosingDate = array(
				'id'=>'txtClosingDate',
				'name'=>'txtClosingDate',					
				'style'=>'width: 80px',
			);
			echo form_input($txtClosingDate);
		?>
		</div>	

		<div style="clear: both"></div>
		<div class="left_1">PIB to Bank</div>
		<div class="left_2">:</div>
		<div class="left_3">
			<?php 
			$txtPIBto = array(
				'id'=>'txtPIBto',
				'name'=>'txtPIBto',					
				'style'=>'width: 80px',
			);
			echo form_input($txtPIBto);
		?>
		</div>	

		<div style="clear: both"></div>
		<div class="left_1">PIB from Bank</div>
		<div class="left_2">:</div>
		<div class="left_3">
			<?php 
			$txtPIBfrom = array(
				'id'=>'txtPIBfrom',
				'name'=>'txtPIBfrom',					
				'style'=>'width: 80px',
			);
			echo form_input($txtPIBfrom);
		?>
		</div>	

		<div style="clear: both"></div>		     
      	<div class="left_1">Actual EDI Transfer</div>
		<div class="left_2">:</div>
		<div class="left_3" style="width: 80px">
		<?php 
			$txtEDI = array(
				'id'=>'txtEDI',
				'name'=>'txtEDI',					
				'style'=>'width: 80px',
			);
			echo form_input($txtEDI);
		?>
        </div>
        	
    	<div class="left_3" style="margin-left: 10px">
		<?php 
			$txtEDITime = array(
				'id'=>'txtEDITime',
				'name'=>'txtEDITime',				
				'style'=>'width: 80px',
				'onclick'=>'format_waktu(this)',
			);
			echo form_input($txtEDITime);
		?>
    	</div>        

        <div style="clear: both"></div>
		<div class="left_1">RED Lane/Exception</div>
		<div class="left_2">:</div>
		<div class="left_3">
			<?php 
			$txtRED = array(
				'id'=>'txtRED',
				'name'=>'txtRED',					
				'style'=>'width: 80px',
			);
			echo form_input($txtRED);
		?>
		</div>	

		<div style="clear: both"></div>
		<div class="left_1">Inspection Date</div>
		<div class="left_2">:</div>
		<div class="left_3">
			<?php 
			$txtIns = array(
				'id'=>'txtIns',
				'name'=>'txtIns',					
				'style'=>'width: 80px',
			);
			echo form_input($txtIns);
		?>
		</div>

		<div style="clear: both"></div>
		<div class="left_1">SPBB</div>
		<div class="left_2">:</div>
		<div class="left_3">
			<?php 
			$txtSPPB = array(
				'id'=>'txtSPPB',
				'name'=>'txtSPPB',					
				'style'=>'width: 80px',
			);
			echo form_input($txtSPPB);
		?>
		</div>

		<div style="clear: both"></div>
		<div class="left_1">Actual Delivery/Handover</div>
		<div class="left_2">:</div>
		<div class="left_3">
			<?php 
			$txtActual = array(
				'id'=>'txtActual',
				'name'=>'txtActual',					
				'style'=>'width: 80px',
			);
			echo form_input($txtActual);
		?>
		</div>

	</div>
</div>

-->