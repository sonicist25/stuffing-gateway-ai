<style type="text/css">
#btn_nama_agen,#btnTracking,#btnPindah,#cetak_form_bl,#btn_save_detail
{
	box-shadow:inset 0px 1px 0px 0px #bbdaf7;
	background:linear-gradient(to bottom, #79bbff 5%, #378de5 100%);
	background-color:#79bbff;
	border-radius:6px;
	border:1px solid #84bbf3;
	display:inline-block;
	cursor:pointer;
	color:#ffffff;
	font-family:Arial;
	font-size:11px;
	padding:6px 24px;
	text-decoration:none;
	text-shadow:0px 1px 0px #528ecc;
}

#btn_nama_agen,#btnTracking,#btnPindah,#cetak_form_bl,#btn_save_detail:hover {
	background:linear-gradient(to bottom, #378de5 5%, #79bbff 100%);
	background-color:#378de5;
}
</style>
<div id="msg"></div>
<div id="form-move"></div>
<div id="loading-bl" style="float: right;"></div>

<div id="tabs-lading">
	<ul id="tabs-menu">
		<li><a href="#tab-1">FRONT AWB</a></li>
  		<li><a href="#tab-2" onclick="save_front()">DETAIL AWB</a></li>
	</ul>

   <div id="tab-1" class="the-tab" style="height: 570px;">
		<div class="tableft-lading" style="float: left; width: 325px;">		
		<div class="form_bck">			
			<div style="float: left; width: 60px;">Job No</div>
			<div style="float: left; width: 235px;">
				<?= form_input(array(
						'id'=>'job_number',
						'disabled'=>'disabled',
						'style'=>'width: 100%'
				)) ?>
			</div>
			<div style="clear: both; height: 2px"></div>			
			<div style="float: left; width: 60px;">Date</div>
			<div style="float: left; width: 110px;">
				<?= form_input(array(
						'id'=>'awb_date',
						'style'=>'width: 80%'
				)) ?>
			</div>			
			<div style="float: left; width: 45px;">Kurs</div>
			<div style="float: left; width: 80px;">
				<?= form_input(array(
						'id'=>'kurs',
						'style'=>'width: 100%'
				)) ?>
			</div>
			<div style="clear: both; height: 2px"></div>

			<div style="float: left; width: 60px;">Sales</div>
			<!--
			<div style="float: left; width: 235px;" id="sales_Form_BL">
			-->
			<div style="float: left; width: 235px;">
				<?php
				$select = $this->afg_m_stuffing_plan->get_sales_by_sp_id($id);
				//$sales = $this->dbx->get_where('mstadmin',array('level <='=>2,'username !='=>'root','region_id'=>$this->session->userdata('region_id')));
				$sales = $this->user_model->get_data_sales();
				$data['option_sales']['']='';
				if($sales->num_rows() > 0 ) {
				foreach ($sales->result() as $row)
				{	
					$data['option_sales'][$row->user_id] = $row->fullname;
				}
				echo form_dropdown('sales', $data['option_sales'], $select, 'id="sales" style="width: 235; height: 20; border-radius: 2px; border: 1px solid #999;" disabled="disabled" ');
			}
			else 
			{
				echo form_dropdown('sales', $data['option_sales'], '', 'id="sales" style="width: 235; height: 20; border-radius: 2px; border: 1px solid #999;" disabled="disabled" ');
			}
      ?>
			
			</div>
			<div style="clear: both; height: 2px;"></div>
		</div>		
		<div style="clear: both; height: 2px;"></div>			
		<div class="form_bck">
			<div style="float: left; width: 120px;">Airline</div>
			<div style="float: left;">
				<?= form_input(array(
						'id'=>'airline',
						'style'=>'width: 100%',
						'disabled'=>'disabled',
				)) ?>
			</div>
			<div style="clear: both; height: 2px"></div>			
			<div style="float: left; width: 120px;">Departure</div>
			<div style="float: left;">
				<?= form_input(array(
						'id'=>'departure',
						'style'=>'width: 100%',
				)) ?>
			</div>
			<div style="clear: both; height: 2px"></div>			
			<div style="float: left; width: 120px;">Issuing Carrier Agent</div>
			<div style="float: left;">
				<?= form_input(array(
						'id'=>'agent',
						'style'=>'width: 100%',
						'disabled'=>'disabled',
				)) ?>
			</div>
			<div style="clear: both; height: 2px"></div>			
			<div style="float: left; width: 120px;">MAWB</div>
			<div style="float: left;">
				<?= form_input(array(
						'id'=>'mbl',
						'style'=>'width: 100%',
				)) ?>
			</div>
			<div style="clear: both; height: 2px"></div>			
		</div>		
		<div style="clear: both; height: 2px"></div>
		<div class="form_bck">
			<div style="float: left; width: 80px;">Location</div>
			<div style="float: left; width: 210px;">
				<?php
				$select = $this->stuffing_location_model->location_selected($id);
				$location = $this->stuffing_location_model->getStuffing_location()->result();
				$data['option_location']['']=' ';
				foreach ($location as $row)
				{	
					$data['option_location'][$row->stuffing_location_id]=$row->stuffing_location_company;
				}		
				echo form_dropdown('location',$data['option_location'],$select,'id="location" style="width: 100%; height: 20;"');
				?>
			</div>
			<div style="clear: both; height: 2px"></div>

			<div style="float: left; width: 80px;">Trucking Co</div>
			<div style="float: left; width: 210px;">
				<?php
				$select = $this->stuffing_location_model->trucking_co_selected($id);
				$trucking = $this->stuffing_location_model->getStuffing_trucking()->result();
				$data['trucking']['']='';
				foreach ($trucking as $row)
				{	
					$data['trucking'][$row->trucking_company_id]=$row->trucking_company_name;
				}		
				echo form_dropdown('trucking_co',$data['trucking'],$select,'id="trucking_co" style="width: 100%; height: 20; border-radius: 2px; border: 1px solid #999;"');
				?>
			</div>
			<div style="clear: both; height: 2px"></div>
		</div>
		
		<div style="clear: both; height: 2px"></div>
		
		<div class="form_bck">
			<div style="float: left; width: 130px;">Shipper Information</div>
			<div style="float: left; width: 160px;">
				<?php 
				$select_shipper = $this->stuffing_location_model->shipper_selected_id($id);
				$shipper = $this->user_model->get_data_shipper();
  				$data['shipper']['']='';
    			if($shipper->num_rows() > 0 ) 
    			{
					foreach ($shipper->result() as $row)
					{	
						$data['shipper'][$row->id_shipper] = $row->shipper_id;
					}
					echo form_dropdown('shipper', $data['shipper'],$select_shipper, 'id="shipper" style="width: 100%" onchange="get_data_form()"');
				}
				?>
			</div>
			<div style="clear: both; height: 2px"></div>
			<div style="float: left; width: 290px;">
					<?= form_input(array(
						'id'=>'shipperInformationName',
						'style'=>'width: 100%; text-transform: uppercase',
				)) ?>
			</div>
			<div style="clear: both; height: 2px"></div>
			<div style="float: left; width: 290px;">
					<?= form_textarea(array(
						'id'=>'textareaShipper',
						'name'=>'textareaShipper',
						'style'=>'min-width: 100%; max-width: 100%; min-height: 60px; max-height: 60px; text-transform: uppercase',
				)) ?>
			</div>
			
			<div style="clear: both; height: 15px"></div>
				
			<div style="float: left; width: 290px;">S/I no from Shipper/Exportir</div>
			<div style="clear: both; height: 1px"></div>
			<div style="float: left; width: 290px;">
				<?= form_textarea(array(
						'id'=>'si_shipper',
						'name'=>'si_shipper',
						'style'=>'min-width: 100%; max-width: 100%; min-height: 40px; max-height: 40px; text-transform: uppercase',
						'rules'=>'required',
				)) ?>
			</div>
			<div style="clear: both; height: 2px"></div>
			<div style="float: left; width: 290px;">Manifest No.</div>
			<div style="clear: both; height: 1px"></div>
			<div style="float: left; width: 290px;">
				<?= form_input(array(
						'id'=>'manifest_no',
						'style'=>'width: 100%',
					//	'disabled'=>'disabled',
				)) ?>
			</div>
			<div style="clear: both; height: 2px"></div>
		</div>
		</div>
	
		<div class="tabright-lading" style="float: left; width: 490px; margin-left: 10px">
			<div class="form_bck">	
				<div style="float: left; width: 40px;">TO</div>
				<div style="float: left; width: 50px;">
					<?= form_input(array(
						'id'=>'textfieldto1',
						'name'=>'textfieldto1',
						'style'=>'width: 100%',
					)) ?>
				</div>
				<div style="float: left; width: 130px; margin-left: 30px">BY FIRST CARRIER</div>
				<div style="float: left; width: 200px;">
					<?= form_input(array(
						'id'=>'by_first',
						'name'=>'by_first',
						'disabled'=>'disabled',
						'style'=>'width: 100%'
					)) ?>
				</div>
				<div style="clear: both; height: 5px"></div>	
				<div style="float: left; width: 40px;">TO</div>
				<div style="float: left; width: 50px;">
					<?= form_input(array(
						'id'=>'textfieldto2',
						'name'=>'textfieldto2',
						'style'=>'width: 100%',
					)) ?>
				</div>
				<div style="float: left; width: 40px; margin-left: 30px">BY</div>
				<div style="float: left; width: 50px;">
					<?= form_input(array(
						'id'=>'textfieldby2',
						'name'=>'textfieldby2',
						'style'=>'width: 100%'
					)) ?>
				</div>	
				<div style="float: left; width: 40px;margin-left: 30px">TO</div>
				<div style="float: left; width: 50px;">
					<?= form_input(array(
						'id'=>'textfieldto3',
						'name'=>'textfieldto3',
						'style'=>'width: 100%',
					)) ?>
				</div>
				<div style="float: left; width: 40px; margin-left: 30px">BY</div>
				<div style="float: left; width: 50px;">
					<?= form_input(array(
						'id'=>'textfieldby3',
						'name'=>'textfieldby3',
						'style'=>'width: 100%'
					)) ?>
				</div>
				<div style="clear: both; height: 5px"></div>	
				<div style="float: left; width: 100px; ">FLIGHT/DATE</div>
				<div style="float: left; width: 90px;">
					<?= form_input(array(
						'id'=>'flight_1',
						'name'=>'flight_1',
						'style'=>'width: 100%'
					)) ?>
				</div>
				<div style="float: left; width: 100px; margin-left: 30px">FLIGHT/DATE</div>
				<div style="float: left; width: 90px;">
					<?= form_input(array(
						'id'=>'flight_2',
						'name'=>'flight_2',
						'style'=>'width: 100%'
					)) ?>
				</div>
				<div style="clear: both; height: 3px"></div>	
				<div style="float: left; width: 90px;">DESTINATION</div>
				<div style="float: left; width: 360px;">
					<?= form_input(array(
						'id'=>'destination',
						'name'=>'destination',
						'style'=>'width: 100%'
					)) ?>
				</div>
				<div style="clear: both; height: 2px"></div>
			</div>
			<div style="clear: both; height: 2px"></div>	
			<div class="form_bck">
				<div style="clear: both; height: 2px"></div>
				<div style="float: left; width: 290px;">HANDLING INFORMATION</div>
				<div style="clear: both; height: 2px"></div>
				<div style="float: left; width: 460px;">
				<?= form_textarea(array(
						'id'=>'textfieldhandling',
						'name'=>'textfieldhandling',
						'style'=>'min-width: 100%; max-width: 100%; min-height: 40px; max-height: 40px; text-transform: uppercase',
					//	'disabled'=>'disabled',
				)); ?>
				</div>				
				<div style="clear: both; height: 2px"></div>
			</div>
			
			
			<div style="clear: both; height: 2px"></div>
			
			<div class="form_bck">
				<div style="clear: both; height: 2px"></div>
				<div style="float: left; width: 290px;">ACCOUNTING INFORMATION</div>
				<div style="clear: both; height: 2px"></div>
				<div style="float: left; width: 460px;">
				<?= form_textarea(array(
						'id'=>'textfieldacct',
						'name'=>'textfieldacct',
						'style'=>'min-width: 100%; max-width: 100%; min-height: 40px; max-height: 40px; text-transform: uppercase',
					//	'disabled'=>'disabled',
				)); ?>
				</div>				
				<div style="clear: both; height: 3px"></div>				
			</div>
			<div style="clear: both; height: 7px"></div>
			<div class="form_bck">
				<div style="float: left; width: 120px;">CURRENCY</div>
				<div style="float: left; width: 50px;">
					<?= form_input(array(
						'id'=>'textfieldcurrency',
						'name'=>'textfieldcurrency',
						'style'=>'width: 100%',
					)) ?>
				</div>
				<div style="float: left; width: 50px; margin-left: 100px">CHCG</div>
				<div style="float: left; width: 50px;">
					<?= form_input(array(
						'id'=>'textfieldchcg',
						'name'=>'textfieldchcg',
						'style'=>'width: 100%'
					)) ?>
				</div>
				<div style="clear: both; height: 5px"></div>	
				<div style="float: left; width: 120px;">VALUE FOR CARRIAGE</div>
				<div style="float: left; width: 100px;">
					<?= form_input(array(
						'id'=>'textfieldvaluecariage',
						'name'=>'textfieldvaluecariage',
						'style'=>'width: 100%',
					)) ?>
				</div>
				<div style="float: left; width: 70px; margin-left: 30px">WT/VAL</div>
				<div style="float: left; width: 50px;">
					<?= form_input(array(
						'id'=>'textfieldwt',
						'name'=>'textfieldwt',
						'style'=>'width: 100%'
					)) ?>
				</div>	
					<div style="float: left; width: 50px;margin-left: 30px;">
					<?php 
            		$q_select = $this->dbx->get_where('tbl_detail',array('detail_id'=>$_POST['spd']));
            		if($q_select->num_rows() > 0) {
            			$selected_prepcol_wt = $q_select->row()->wt_prep_col;           			
            		} else {
							$selected_prepcol_wt = 1;           		
            		}
            		$option_form = array(
            		'PPD'  	=> 'PPD',
                  	'COL'		=> 'COL',
                	);
						echo form_dropdown('select_prepcoll_wt',$option_form,$selected_prepcol_wt,'id="select_prepcoll_wt" style="width: 100%;"');		
					?>
				</div>
				<div style="clear: both; height: 5px"></div>	
				<div style="float: left; width: 120px;">VALUE FOR CUSTOMS</div>
				<div style="float: left; width: 100px;">
					<?= form_input(array(
						'id'=>'textfieldvaluecustom',
						'name'=>'textfieldvaluecustom',
						'style'=>'width: 100%',
					)) ?>
				</div>
				<div style="float: left; width: 70px; margin-left: 30px">OTHER</div>
				<div style="float: left; width: 50px;">
					<?= form_input(array(
						'id'=>'textfieldother',
						'name'=>'textfieldother',
						'style'=>'width: 100%'
					)) ?>
				</div>	
				<div style="float: left; width: 50px;margin-left: 30px;">
					<?php 
            		$q_select = $this->dbx->get_where('tbl_detail',array('detail_id'=>$_POST['spd']));
            		if($q_select->num_rows() > 0) {
            			$selected_other = $q_select->row()->other_prep_col;           			
            		} else {
							$selected_other = 1;           		
            		}
            		$option_form = array(
            		'PPD'  	=> 'PPD',
                  	'COL'		=> 'COL',
                	);
						echo form_dropdown('select_prepcoll_other',$option_form,$selected_other,'id="select_prepcoll_other" style="width: 100%;"');		
					?>
				</div>
				<div style="clear: both; height: 2px"></div>				
			</div>
			<div style="clear: both; height: 5px"></div>	
			<div style="float: left; width: 150px; color: #f90000">Date of DO Realease</div>
			<div style="float: left; width: 165px;">
				<?= form_input(array(
						'id'=>'textfield36',
						'name'=>'textfield36',
						'style'=>'width: 100%'
				)) ?>
			</div>
			<div style="clear: both; height: 2px"></div>	
			<div style="float: left; width: 150px; color: #f90000">No. PEB</div>
			<div style="float: left; width: 165px;">
				<?= form_input(array(
						'id'=>'textfield37',
						'name'=>'textfield37',
						'style'=>'width: 100%'
				)) ?>
			</div>
			<div style="clear: both; height: 2px"></div>	
			<div style="float: left; width: 150px; color: #f90000">Tanggal PEB</div>
			<div style="float: left; width: 165px;">
				<?= form_input(array(
						'id'=>'textfield38',
						'name'=>'textfield38',
						'style'=>'width: 100%'
				)) ?>
			</div>
  		</div>
	</div>

   <div id="tab-2" class="the-tab" style="height: 570px;">
		<div class="tabtri-lading" style="float: left; width: 250px">
			<div style="float: left; width: 80px; font-weight: bold">JOB No</div>
			<div style="float: left; width: 160px;">
			<?= form_input(array(
						'id'=>'job_number_detail',
						'name'=>'job_number_detail',
						'disabled'=>'disabled',
						'style'=>'width: 100%',
				)) ?>
			</div>
			<div style="clear: both; height: 2px"></div>
			<div style="float: left; width: 80px; font-weight: bold">B/L No</div>
			<div style="float: left; width: 160px;">
			<?= form_input(array(
						'id'=>'bl_no',
						'name'=>'bl_no',
						'disabled'=>'disabled',
						'style'=>'width: 100%',
				)) ?>
			</div>
			<div style="clear: both; height: 2px"></div>
			<div style="float: left; width: 100px; color: #f900df">EXPORTIR</div>
			<div style="clear: both; height: 2px"></div>
			<div style="float: left; width: 245px;">
			<?= form_textarea(array(
						'id'=>'textarea2',
						'name'=>'textarea2',
						'disabled'=>'disabled',
						'style'=>'min-width: 98%; max-width: 98%; min-height: 75px; max-height: 75px; text-transform: uppercase',
				)) ?>
			</div>
			<div style="clear: both; height: 3px"></div>
			<div style="float: left; width: 80px; color: #9322ff">CONSIGNEE</div>
			<div style="float: left; width: 160px;">
			<?php
			$select = $this->afg_m_detail->consignee_selected($id);
			$consignee = $this->consignee_notify_model->getListConsignee()->result();
			$data['consignee']['']=' ';
			foreach ($consignee as $row)
			{	
				$data['consignee'][$row->consignee_notify_code] = $row->consignee_notify_code;
			}
			echo form_dropdown('consignee', $data['consignee'],$select, 'id="consignee" style="width: 100%" onchange="get_data_consignee()"');
			
			/*
			form_input(array(
						'id'=>'textfield390',
						'name'=>'textfield390',
						'style'=>'width: 100%',
				)) 
			*/
			?>
			</div>
			<div style="clear: both; height: 2px"></div>
			<div style="float: left; width: 245px;">
			<?= form_textarea(array(
						'id'=>'textarea3',
						'name'=>'textarea3',
						'style'=>'min-width: 98%; max-width: 98%; min-height: 75px; max-height: 75px; text-transform: uppercase',
				)) ?>
			</div>
			<div style="clear: both; height: 5px"></div>
			<div style="float: left; width: 200px;  font-weight: bold">NATURE AND QUANTITY</div>
			<div style="clear: both; height: 3px"></div>
			<div style="float: left; width: 400px;">
			<?= form_textarea(array(
						'id'=>'textarea5',
						'name'=>'textarea5',
						'style'=>'min-width: 245px; max-width: 245px; min-height: 280px; max-height: 280px;',
				)) ?>
			</div>
		</div>
		 	  
   	<div class="tabtri-lading" style="float: left; width: 300px; height: 120px; margin-left: 10px;">	
   		<div class="form_bck">
				<div style="clear: both; height: 1px"></div>
				<div style="float: left; width: 100px;">DEPARTURE</div>
				<div style="float: left; width: 170px;">
					<?= form_input(array(
						'id'=>'textfield44',
						'name'=>'textfield44',
						'style'=>'width: 100%; text-transform: uppercase'
					)) ?>
				</div>
				<div style="clear: both; height: 1px"></div>
				<div style="float: left; width: 100px;">DESTINATION</div>
				<div style="float: left; width: 170px;">
					<?= form_input(array(
						'id'=>'textfield43',
						'name'=>'textfield43',
						'disabled'=>'disabled',
						'style'=>'width: 100%'
					)) ?>
				</div>
				<div style="clear: both; height: 1px"></div>
				<div style="float: left; width: 100px;">DISCHARGE</div>
				<div style="float: left; width: 170px;">
					<?= form_input(array(
						'id'=>'textfield45',
						'name'=>'textfield45',
						'style'=>'width: 100%; text-transform: uppercase'
					)) ?>
				</div>
				<div style="clear: both; height: 1px"></div>
				<div style="float: left; width: 100px;">RECIEVE</div>
				<div style="float: left; width: 170px;">
					<?= form_input(array(
						'id'=>'textfield46',
						'name'=>'textfield46',
						'style'=>'width: 100%; text-transform: uppercase'
					)) ?>
				</div>
				<div style="clear: both; height: 1px"></div>
				<div style="float: left; width: 100px;">DELIVERY</div>
				<div style="float: left; width: 170px;">
					<?= form_input(array(
						'id'=>'textfield47',
						'name'=>'textfield47',
						'style'=>'width: 100%; text-transform: uppercase'
					)) ?>
				</div>
				<div style="clear: both; height: 1px"></div>
				<div style="float: left; width: 100px;">FLIGHT</div>
				<div style="float: left; width: 170px;">
					<?= form_input(array(
						'id'=>'textfield48',
						'name'=>'textfield48',
						'style'=>'width: 100%',
						'readonly'=>'readonly'
					)) ?>
				</div>
				<div style="clear: both; height: 1px"></div>
				<div style="float: left; width: 100px;">IMO</div>
				<div style="float: left; width: 170px;">
					<?= form_input(array(
						'id'=>'textfield49',
						'name'=>'textfield49',
						'style'=>'width: 100%',
						'readonly'=>'readonly'
					)) ?>
				</div>
				<div style="clear: both; height: 1px"></div>
				<div style="float: left; width: 100px;">LOCAL VESEL</div>
				<div style="float: left; width: 170px;">
					<?= form_input(array(
						'id'=>'textfield50',
						'name'=>'textfield50',
						'style'=>'width: 100%',
						'readonly'=>'readonly'
					)) ?>
				</div>
				<div style="clear: both; height: 1px"></div>
				<div style="float: left; width: 100px;">CONNECT. AT</div>
				<div style="float: left; width: 170px;">
					<?= form_input(array(
						'id'=>'textfield51',
						'name'=>'textfield51',
						'style'=>'width: 100%',
						'readonly'=>'readonly'
					)) ?>
				</div>
				<div style="clear: both; height: 1px"></div>				
   		<div style="clear: both; height: 2px"></div>
   		</div>
		<div style="clear: both; height: 7px"></div>
   		<div style="float: left; width: 130px">
   			<div style="font-weight: bold;">MARKING & NOS</div>
   			<div style="clear: both; height: 2px"></div>
   			<div style="float: left; width: 300px;">
					<?= form_textarea(array(
						'id'=>'textarea7',
						'name'=>'textarea7',
						'style'=>'min-width: 98%; max-width: 98%; min-height: 140px; max-height: 140px;',
					)) ?>
				</div>
				<div style="clear: both; height: 1px"></div>
   		</div>
		<div style="clear: both; height: 25px"></div>
   		<div style="float: left; width: 580px">
   			<div style="font-weight: bold;">Shipper's certification</div>
   			<div style="clear: both; height: 2px"></div>
   			<div style="float: left; width: 580px;">
					<?= form_textarea(array(
						'id'=>'shipper_certification',
						'name'=>'shipper_certification',
						'style'=>'min-width: 98%; max-width: 98%; min-height: 20px; max-height: 20px;',
					)) ?>
				</div>
				<div style="clear: both; height: 1px"></div>
   		</div>
    	</div>
    	<div class="tabtri-lading" style="margin-left: 20px; width: 250px">
			<div style="width: 250px;">
    		<?php echo form_submit(array(
    			'id'=>'btn_nama_agen',
    			'name'=>'btn_nama_agen',
    			'onclick'=>'nama_agent()',
    			'value'=>'Edit Nama Agent',
    			'style'=>'width: 250px')
    			) 
    		?> 
    		</div>
    		<div style="float: right; width: 120px;">  		
    		<?php echo form_submit(array(
    			'id'=>'btn_save_detail',
    			'name'=>'btn_save_detail',
    			'onclick'=>'save_detail_awb()',
    			'value'=>'SAVE',
    			'style'=>'width: 120px')
    			) 
    		?>    		
    		</div>
    		<div style="float: left; width: 120px">
    		<?= form_submit(array('id'=>'cetak_form_bl','name'=>'cetak_form_bl','onclick'=>'cetak_bl_form()','value'=>'CETAK BL','style'=>'width: 100%')) ?>
    		</div>
			</div>
    	<div class="tabtri-lading" style="margin-left: 20px; width: 250px">
    		<div class="form_bck">
    			<div style="float: left; width: 100px;">FORM B/L</div>
					<div style="float: left; width: 120px;">
					<?php 
            		$q_select = $this->dbx->get_where('x_form_bl',array('id_bl'=>$_POST['spd']));
            		if($q_select->num_rows() > 0) {
            			$selected_form = $q_select->row()->value;           			
            		} else {
							$selected_form = '';           		
            		}
            		$option_form = array(
            		'1'  	=> 'GATEWAY',
                  	'2'		=> 'GC_Line',
                  	'3'  	=> 'CTL_USA',
                  	'4' 	=> 'MILGRAM',
                  	'5'     => 'AIRWAYBILL',
                  	'6'     => 'FUKUYAMA',
                    '7'     => 'FUKUYAMA 2',
                    '8'     => 'BP LOGISTIC',
                    '9'     => 'OSLINE',
					'10'     => 'WINFAST',
					'11'     => 'BPLINE',
                	);
						echo form_dropdown('select3',$option_form,$selected_form,'id="select3" style="width: 100%;" onchange="set_value_img()"');		
					?>
					</div>
				<div style="clear: both; height: 2px"></div>
				<div style="float: left; width: 100px; color: #f90099">B/L DATE</div>
				<div style="float: left; width: 120px;">
					<?= form_input(array(
						'id'=>'textfield54',
						'name'=>'textfield54',
						'disabled'=>'disabled',
						'style'=>'width: 100%'
					)) ?>
				</div>
				<div style="clear: both; height: 2px"></div>	
				<div style="float: left; width: 100px; color: #f90099">Backup Date</div>
				<div style="float: left; width: 120px;">
					<?= form_input(array(
						'id'=>'textfield55',
						'name'=>'textfield55',
						'style'=>'width: 100%'
					)) ?>
				</div>
				<div style="clear: both; height: 2px"></div>
				<div style="float: left; width: 100px; color: #f90099">B/L Type</div>
				<div style="float: left; width: 120px;">
					<?= form_input(array(
						'id'=>'textfield56',
						'name'=>'textfield56',
						'style'=>'width: 100%'
					)) ?>
				</div>
				<div style="clear: both; height: 2px"></div>
				<div style="float: left; width: 100px; color: #f90099">AS Agent</div>
				<div style="float: left; width: 120px;">
					<?= form_input(array(
						'id'=>'textfield57',
						'name'=>'textfield57',
						'style'=>'width: 100%'
					)) ?>
				</div>
				<div style="clear: both; height: 2px"></div>
			</div>
    		<div style="clear: both; height: 5px"></div>
   	<div style="clear: both; height: 7px"></div>
   	<div class="form_bck">
		<div style="float: left; width: 100px; color: #f90099">Pieces</div>
			<div style="float: left; width: 120px;">
					<?= form_input(array(
						'id'=>'textfieldpieces',
						'name'=>'textfieldpieces',
						'style'=>'width: 100%',
					)) ?>
			</div>
		<div style="clear: both; height: 4px"></div>
   		<div style="float: left; width: 100px; color: #f90099">Gross (K)</div>
			<div style="float: left; width: 120px;">
					<?= form_input(array(
						'id'=>'textfield58',
						'name'=>'textfield58',
						'style'=>'width: 100%'
					)) ?>
			</div>
			<div style="clear: both; height: 4px"></div>
			<div style="float: left; width: 100px; color: #f90099">Rate Class</div>
			<div style="float: left; width: 120px;">
					<?php 
            		$q_select = $this->dbx->get_where('trnstuffingplandetail',array('sp_detail_id'=>$_POST['spd']));
					//print_r($this->dbx->last_query());
					//die();
            		if($q_select->num_rows() > 0) {
            			$selected_form = $q_select->row()->rate_class;           			
            		} else {
							$selected_form = "";           		
            		}
            		$option_form = array(
					''  	=> '',
            		'M'  	=> 'MINIMUM',
                  	'N'		=> 'NORMAL',
                  	'Q'  	=> 'QUANTITATIVE'
                	);
						echo form_dropdown('textfieldrateclass',$option_form,$selected_form,'id="textfieldrateclass" style="width: 100%;"');		
					?>
			</div>
		<div style="clear: both; height: 4px"></div>
		<div style="float: left; width: 100px; color: #f90099">Item Number</div>
			<div style="float: left; width: 120px;">
					<?= form_input(array(
						'id'=>'textfielditemno',
						'name'=>'textfielditemno',
						'style'=>'width: 100%',
					)) ?>
			</div>
		<div style="clear: both; height: 4px"></div>
		<div style="float: left; width: 100px;">Chargeable weight</div>
			<div style="float: left; width: 120px;">
					<?= form_input(array(
						'id'=>'textfieldchargeweight',
						'name'=>'textfieldchargeweight',
						'style'=>'width: 100%',
					)) ?>
			</div>
		<div style="clear: both; height: 4px"></div>
		<div style="float: left; width: 100px;">Rate/Charge</div>
			<div style="float: left; width: 120px;">
					<?= form_input(array(
						'id'=>'textfieldratepercharge',
						'name'=>'textfieldratepercharge',
						'style'=>'width: 100%',
					)) ?>
			</div>
		<div style="clear: both; height: 4px"></div>
		<div style="float: left; width: 100px;">Total</div>
			<div style="float: left; width: 120px;">
					<?= form_input(array(
						'id'=>'textfieldtotal',
						'name'=>'textfieldtotal',
						'style'=>'width: 100%',
					)) ?>
			</div>
		<div style="clear: both; height: 2px"></div>
   	</div>		
			<div style="clear: both; height: 2px"></div>
   	</div>	
   	</div>   	
	</div>
	
	<input type="hidden" id="id_form" value="<?php echo $id ?>">
	<input type="hidden" id="shipper_id">
	<input type="hidden" id="job">
	<input type="hidden" id="total" >
   <input type="hidden" id="pack">
	<input type="hidden" id="shipping_mark">
	<input type="hidden" id="freights_charges">
	<input type="hidden" id="txtDestination_id" readonly="readonly">
	<input type="hidden" id="textRequestId" readonly="readonly">
</div>
<?php include "script_function_awb.php" ?>