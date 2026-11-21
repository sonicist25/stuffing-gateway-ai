<meta charset="UTF-8">
<style type="text/css">
/* Tambahan agar semua input, textarea, select, dan div menjadi uppercase */
input, textarea, select, .the-tab div {
	text-transform: uppercase !important;
}

#btn_nama_agen,#btnTracking,#btnPindah,#cetak_form_bl,#btn_save_detail,
#btn_bl_history,#btn_final_bl,#btn_batal_final,#btn_delivered
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
	text-transform: uppercase; /* Tambahan */
}

#btn_nama_agen,#btnTracking,#btnPindah,#cetak_form_bl,#btn_save_detail:hover {
	background:linear-gradient(to bottom, #378de5 5%, #79bbff 100%);
	background-color:#378de5;
}

#btn_bl_history:hover { background:linear-gradient(to bottom, #378de5 5%, #79bbff 100%); background-color:#378de5; }

/* warna berbeda khusus supaya aksinya jelas, tapi bentuk/shadow/padding tetap sama */
#btn_final_bl {
	background:linear-gradient(to bottom, #ff9999 5%, #e53935 100%);
	background-color:#e53935;
	border-color:#e57373;
	text-shadow:0px 1px 0px #b71c1c;
}
#btn_final_bl:hover { background:linear-gradient(to bottom, #e53935 5%, #ff9999 100%); background-color:#ff9999; }

#btn_delivered {
	background:linear-gradient(to bottom, #8ce196 5%, #2e7d32 100%);
	background-color:#2e7d32;
	border-color:#66bb6a;
	text-shadow:0px 1px 0px #1b5e20;
}
#btn_delivered:hover { background:linear-gradient(to bottom, #2e7d32 5%, #8ce196 100%); background-color:#8ce196; }
</style>
<div id="msg"></div>
<div id="form-move"></div>
<div id="loading-bl" style="float: right;"></div>
<div id="bl-history-dialog" style="display:none"></div>
<div id="bl-delivery-dialog" style="display:none">
	<div style="font-size: 11px;">
		<div style="margin-bottom: 6px;">
			<div style="width: 110px; float: left;">Tanggal Diserahkan</div>
			<input type="text" id="delivery_date" style="width: 140px;" placeholder="dd/mm/yyyy" readonly="readonly">
		</div>
		<div style="clear: both; height: 6px"></div>
		<div style="margin-bottom: 6px;">
			<div style="width: 110px; float: left;">Diserahkan Kepada</div>
			<input type="text" id="delivered_to" style="width: 220px;" placeholder="Nama penerima / perusahaan">
		</div>
		<div style="clear: both; height: 6px"></div>
		<div style="margin-bottom: 6px;">
			<div style="width: 110px; float: left;">Via</div>
			<select id="delivered_via" style="width: 140px;">
				<option value="">-- pilih --</option>
				<option value="Messenger">Messenger</option>
				<option value="Courier">Courier</option>
				<option value="Email">Email</option>
			</select>
		</div>
		<div style="clear: both; height: 10px"></div>
		<div style="text-align: right;">
			<input type="button" value="SIMPAN" onclick="submit_delivery()" style="width: 90px;">
		</div>
	</div>
</div>

<div id="tabs-lading">
	<ul id="tabs-menu">
		<li><a href="#tab-1">FRONT B/L</a></li>
  		<li><a href="#tab-2" onclick="save_front()">DETAIL B/L</a></li>
	</ul>

   <div id="tab-1" class="the-tab" style="height: 570px;">
		<div class="tableft-lading" style="float: left; width: 325px;">		
		<div class="form_bck">			
			<div style="float: left; width: 60px;">Job No</div>
			<div style="float: left; width: 235px;">
				<?= form_input(array(
						'id'=>'textfield',
						'disabled'=>'disabled',
						'style'=>'width: 100%'
				)) ?>
			</div>
			<div style="clear: both; height: 2px"></div>			
			<div style="float: left; width: 60px;">Date</div>
			<div style="float: left; width: 110px;">
				<?= form_input(array(
						'id'=>'textfield2',
						'style'=>'width: 80%'
				)) ?>
			</div>			
			<div style="float: left; width: 45px;">Kurs</div>
			<div style="float: left; width: 80px;">
				<?= form_input(array(
						'id'=>'textfield3',
						'style'=>'width: 100%'
				)) ?>
			</div>
			<div style="clear: both; height: 2px"></div>

			<div style="float: left; width: 60px;">Sales</div>
			<div style="float: left; width: 235px;">
				<?php
				$select = $this->afg_m_stuffing_plan->get_sales_by_sp_id($id);
				$sales_name = '';
				$sales = $this->user_model->get_data_sales();
				foreach ($sales->result() as $row) {
					if ($row->user_id == $select) {
						$sales_name = $row->fullname;
						break;
					}
				}
				?>
				<input type="text" value="<?= $sales_name; ?>" readonly
					   style="width: 100%; height:20px; border-radius:2px; border:1px solid #999; background:#f9f9f9;">

			
			</div>
			<div style="clear: both; height: 2px;"></div>
		</div>		
		<div style="clear: both; height: 2px;"></div>			
		<div class="form_bck">
			<div style="float: left; width: 120px;">Liner</div>
			<div style="float: left;">
				<?= form_input(array(
						'id'=>'textfield5',
						'style'=>'width: 100%',
						'disabled'=>'disabled',
				)) ?>
			</div>
			<div style="clear: both; height: 2px"></div>			
			<div style="float: left; width: 120px;">Destination</div>
			<div style="float: left;">
				<?= form_input(array(
						'id'=>'textfield6',
						'style'=>'width: 100%',
						'disabled'=>'disabled',
				)) ?>
			</div>
			<div style="clear: both; height: 2px"></div>			
			<div style="float: left; width: 120px;">Code Agent</div>
			<div style="float: left;">
				<?= form_input(array(
						'id'=>'textfield7',
						'style'=>'width: 100%',
						'disabled'=>'disabled',
				)) ?>
			</div>
			<div style="clear: both; height: 2px"></div>			
			<div style="float: left; width: 120px;">Master B/L</div>
			<div style="float: left;">
				<?= form_input(array(
						'id'=>'textfield8',
						'style'=>'width: 100%',
				)) ?>
			</div>
			<div style="clear: both; height: 2px"></div>			
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
			<div style="float: left; width: 270px;">
					<?= form_input(array(
						'id'=>'shipperInformationName',
						'style'=>'width: 100%; text-transform: uppercase',
				)) ?>
			</div>
			<div style="clear: both; height: 2px"></div>
			<div style="float: left; width: 270px;">
					<?= form_textarea(array(
						'id'=>'textareaShipper',
						'name'=>'textareaShipper',
						'style'=>'min-width: 100%; max-width: 100%; min-height: 60px; max-height: 60px; text-transform: uppercase',
				)) ?>
			</div>
			
			<div style="clear: both; height: 15px"></div>
				
			<div style="float: left; width: 270px;">S/I no from Shipper/Exportir</div>
			<div style="clear: both; height: 1px"></div>
			<div style="float: left; width: 270px;">
				<?= form_textarea(array(
						'id'=>'textfield13',
						'name'=>'textfield13',
						'style'=>'min-width: 100%; max-width: 100%; min-height: 40px; max-height: 40px; text-transform: uppercase',
						'rules'=>'required',
				)) ?>
			</div>
			<div style="clear: both; height: 2px"></div>
			<div style="float: left; width: 270px;">Jobsheet Status.</div>
			<div style="clear: both; height: 1px"></div>
			<div style="float: left; width: 270px;">
				<?= form_input(array(
						'id'=>'textfield14',
						'style'=>'width: 100%',
					//	'disabled'=>'disabled',
				)) ?>
			</div>
			<div style="clear: both; height: 2px"></div>
		</div>
		</div>
		</div>
		<div class="tabright-lading" style="float: left; width: 330px; margin-left: 10px">
			<div class="form_bck">			
				<div style="float: left; width: 290px;">FEEDER VESSEL</div>
				<div style="clear: both; height: 2px"></div>
				<div style="float: left; width: 300px;">
					<?= form_input(array(
						'id'=>'textfield15',
						'name'=>'textfield15',
						'disabled'=>'disabled',
						'style'=>'width: 100%'
					)) ?>
				</div>
				<div style="clear: both; height: 2px"></div>	
				<div style="float: left; width: 50px;">FROM</div>
				<div style="float: left; width: 90px;">
					<?= form_input(array(
						'id'=>'textfield16',
						'name'=>'textfield16',
						'style'=>'width: 100%',
					)) ?>
				</div>
				<div style="float: left; width: 40px; margin-left: 30px">TO</div>
				<div style="float: left; width: 90px;">
					<?= form_input(array(
						'id'=>'textfield17',
						'name'=>'textfield17',
						'style'=>'width: 100%'
					)) ?>
				</div>
				<div style="clear: both; height: 2px"></div>	
				<div style="float: left; width: 50px; ">ATD</div>
				<div style="float: left; width: 90px;">
					<?= form_input(array(
						'id'=>'textfield18',
						'name'=>'textfield18',
						'style'=>'width: 100%'
					)) ?>
				</div>
				<div style="float: left; width: 40px; margin-left: 30px">ATA</div>
				<div style="float: left; width: 90px;">
					<?= form_input(array(
						'id'=>'textfield19',
						'name'=>'textfield19',
						'style'=>'width: 100%'
					)) ?>
				</div>
				<div style="clear: both; height: 2px"></div>
			</div>
			<div style="clear: both; height: 2px"></div>	
			<div class="form_bck">
				<div style="float: left; width: 90px;">2ND VESSEL</div>
				<div style="float: left; width: 210px;">
					<?= form_input(array(
						'id'=>'textfield20',
						'name'=>'textfield20',
						'style'=>'width: 100%'
					)) ?>
				</div>
				<div style="clear: both; height: 2px"></div>				
				<div style="float: left; width: 50px;">FROM</div>
				<div style="float: left; width: 90px;">
					<?= form_input(array(
						'id'=>'textfield21',
						'name'=>'textfield21',
						'style'=>'width: 100%'
					)) ?>
				</div>
				<div style="float: left; width: 40px; margin-left: 30px">TO</div>
				<div style="float: left; width: 90px;">
					<?= form_input(array(
						'id'=>'textfield22',
						'name'=>'textfield22',
						'style'=>'width: 100%'
					)) ?>
				</div>
				<div style="clear: both; height: 2px"></div>	
				<div style="float: left; width: 50px;">ATD</div>
				<div style="float: left; width: 90px;">
					<?= form_input(array(
						'id'=>'textfield23',
						'name'=>'textfield23',
						'style'=>'width: 100%'
					)) ?>
				</div>
				<div style="float: left; width: 40px; margin-left: 30px">ATA</div>
				<div style="float: left; width: 90px;">
					<?= form_input(array(
						'id'=>'textfield24',
						'name'=>'textfield24',
						'style'=>'width: 100%'
					)) ?>
				</div>				
				<div style="clear: both; height: 2px"></div>
			</div>
			
			<div style="clear: both; height: 2px"></div>
			
			<div class="form_bck">
				<div style="float: left; width: 100px;">LAST VESSEL</div>
				<div style="float: left; width: 200px;">
					<?= form_input(array(
						'id'=>'textfield25',
						'name'=>'textfield25',
						'style'=>'width: 100%'
					)) ?>
				</div>
				<div style="clear: both; height: 2px"></div>				
				<div style="float: left; width: 50px;">ETD</div>
				<div style="float: left; width: 90px;">
					<?= form_input(array(
						'id'=>'textfield26',
						'name'=>'textfield26',
						'style'=>'width: 100%'
					)) ?>
				</div>
				<div style="float: left; width: 40px; margin-left: 30px">ETA</div>
				<div style="float: left; width: 90px;">
					<?= form_input(array(
						'id'=>'textfield27',
						'name'=>'textfield27',
						'style'=>'width: 100%'
					)) ?>
				</div>
				<div style="clear: both; height: 2px"></div>	
				<div style="float: left; width: 50px;">DATE</div>
				<div style="float: left; width: 120px;">
					<?= form_input(array(
						'id'=>'textfield28',
						'name'=>'textfield28',
						'style'=>'width: 100%'
					)) ?>
				</div>
				<div style="float: left; width: 120px; margin-left: 10px">
					<?= form_input(array(
						'id'=>'textfield29',
						'name'=>'textfield29',
						'style'=>'width: 100%'
					)) ?>
				</div>				
				<div style="clear: both; height: 2px"></div>
			</div>
			
			
			<div style="clear: both; height: 2px"></div>
			
			<div class="form_bck">
				<div style="float: left; width: 130px;">LAST 2ND VESSEL</div>
				<div style="float: left; width: 170px;">
					<?= form_input(array(
						'id'=>'textfield30',
						'name'=>'textfield30',
						'style'=>'width: 100%'
					)) ?>
				</div>
				<div style="clear: both; height: 2px"></div>				
				<div style="float: left; width: 50px;">ETD</div>
				<div style="float: left; width: 90px;">
					<?= form_input(array(
						'id'=>'textfield31',
						'name'=>'textfield31',
						'style'=>'width: 100%'
					)) ?>
				</div>
				<div style="float: left; width: 40px; margin-left: 30px">ETA</div>
				<div style="float: left; width: 90px;">
					<?= form_input(array(
						'id'=>'textfield32',
						'name'=>'textfield32',
						'style'=>'width: 100%'
					)) ?>
				</div>
				<div style="clear: both; height: 2px"></div>	
				<div style="float: left; width: 50px;">DATE</div>
				<div style="float: left; width: 120px;">
					<?= form_input(array(
						'id'=>'textfield33',
						'name'=>'textfield33',
						'style'=>'width: 100%'
					)) ?>
				</div>
				<div style="float: left; width: 120px; margin-left: 10px">
					<?= form_input(array(
						'id'=>'textfield34',
						'name'=>'textfield34',
						'style'=>'width: 100%'
					)) ?>
				</div>				
				<div style="clear: both; height: 4px"></div>
				<div style="float: left; width: 100px;">LAST CONT</div>
				<div style="float: left; width: 200px;">
					<?= form_input(array(
						'id'=>'textfield35',
						'name'=>'textfield35',
						'style'=>'width: 100%'
					)) ?>
				</div>
				<div style="clear: both; height: 2px"></div>				
			</div>
			<div style="clear: both; height: 5px"></div>	
			<div style="clear: both; height: 4px"></div>
			<div id="bl_final_badge" style="display:none; color:#e53935; font-weight:bold; font-size: 11px;"></div>
			<div id="bl_delivered_badge" style="display:none; color:#2e7d32; font-weight:bold; font-size: 11px;"></div>
  		</div>
		
		<div style="float: right; width: 120px; margin-right:20px">
			
			
			<?php echo form_submit(array(
				'value'=>'PINDAH JOB',
				'onclick'=>'pindah_job()',
				'class'=>'btn_ladding',
				'style'=>'width: 140px',
				'id'=>'btnPindah',
				'name'=>'btnPindah',
			)) 
			?>
			
			
			
			<?php 
			/*
			echo form_submit(array(
				'value'=>'EDIT STUFFING',
				'onclick'=>'edit_detail(\''.$id.'\')',
				'class'=>'btn_ladding',
				'style'=>'width: 140px',
			)) 
			
			$('#btnPindah').html('<input type="button" value=" PINDAH JOB " style="width: 150; height: 30; margin-top: 15px; margin-bottom: 15px" onclick="pindah_job()">');
			
			*/
			?>
		</div>
	</div>

   <div id="tab-2" class="the-tab" style="height: 700px;">
		<div class="tabtri-lading" style="float: left; width: 250px">
			<div style="float: left; width: 80px; font-weight: bold">JOB No</div>
			<div style="float: left; width: 160px;">
			<?= form_input(array(
						'id'=>'textfield39',
						'name'=>'textfield39',
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
			<div style="float: left; width: 100px; color: #f900df">SHIPPER</div>
			<div style="clear: both; height: 2px"></div>
			<div style="float: left; width: 245px;">
			<?= form_textarea(array(
						'id'=>'textarea2',
						'name'=>'textarea2',
						'disabled'=>'disabled',
						'style'=>'min-width: 98%; max-width: 98%; min-height: 95px; max-height: 75px; text-transform: uppercase',
				)) ?>
			</div>
			<div style="clear: both; height: 10px"></div>
			<div style="float: left; width: 80px; color: #9322ff">CONSIGNEE</div>
			<div style="float: left; width: 160px;">
			</div>
			<div style="clear: both; height: 2px"></div>
			<div style="float: left; width: 245px;">
			<?= form_textarea(array(
						'id'=>'textarea3',
						'name'=>'textarea3',
						'style'=>'min-width: 98%; max-width: 98%; min-height: 95px; max-height: 75px; text-transform: uppercase',
				)) ?>
			</div>
			<div style="clear: both; height: 10px"></div>
			<div style="float: left; width: 80px; color: #9322ff">NOTIFY PARTY</div>
			<div style="float: left; width: 160px;">
			</div>
			<div style="clear: both; height: 2px"></div>
			<div style="float: left; width: 245px;">
			<?= form_textarea(array(
						'id'=>'textarea4',
						'name'=>'textarea4',
						'style'=>'min-width: 98%; max-width: 98%; min-height: 85px; max-height: 85px; text-transform: uppercase',
				)) ?>
			</div>
			
			<div style="clear: both; height: 10px"></div>
			<div style="float: left; width: 200px;  font-weight: bold">DESCRIPTION OF GOODS</div>
			<div style="clear: both; height: 2px"></div>
			<div style="float: left; width: 205px;">
			<?= form_textarea(array(
						'id'=>'textarea5',
						'name'=>'textarea5',
						'style'=>'min-width: 303px; max-width: 293px; min-height: 250px; max-height: 350px; text-transform: uppercase',
				)) ?>
			</div>
		</div>
		 	  
   	<div class="tabtri-lading" style="float: left; width: 300px; height: 800px; margin-left: 10px;">	
   		<div class="form_bck">
				<div style="clear: both; height: 3px"></div>
				<div style="float: left; width: 100px;">DESTINATION</div>
				<div style="float: left; width: 170px;">
					<?= form_input(array(
						'id'=>'textfield43',
						'name'=>'textfield43',
						'disabled'=>'disabled',
						'style'=>'width: 100%'
					)) ?>
				</div>
				<div style="clear: both; height: 3px"></div>
				<div style="float: left; width: 100px;">LOADING</div>
				<div style="float: left; width: 170px;">
					<?= form_input(array(
						'id'=>'textfield44',
						'name'=>'textfield44',
						'style'=>'width: 100%; text-transform: uppercase'
					)) ?>
				</div>
				<div style="clear: both; height: 3px"></div>
				<div style="float: left; width: 100px;">DISCHARGE</div>
				<div style="float: left; width: 170px;">
					<?= form_input(array(
						'id'=>'textfield45',
						'name'=>'textfield45',
						'style'=>'width: 100%; text-transform: uppercase'
					)) ?>
				</div>
				<div style="clear: both; height: 3px"></div>
				<div style="float: left; width: 100px;">RECIEVE</div>
				<div style="float: left; width: 170px;">
					<?= form_input(array(
						'id'=>'textfield46',
						'name'=>'textfield46',
						'style'=>'width: 100%; text-transform: uppercase'
					)) ?>
				</div>
				<div style="clear: both; height: 3px"></div>
				<div style="float: left; width: 100px;">DELIVERY</div>
				<div style="float: left; width: 170px;">
					<?= form_input(array(
						'id'=>'textfield47',
						'name'=>'textfield47',
						'style'=>'width: 100%; text-transform: uppercase'
					)) ?>
				</div>
				<div style="clear: both; height: 3px"></div>
				<div style="float: left; width: 100px;">VESSEL</div>
				<div style="float: left; width: 170px;">
					<?= form_input(array(
						'id'=>'textfield48',
						'name'=>'textfield48',
						'style'=>'width: 100%',
						'readonly'=>'readonly'
					)) ?>
				</div>
				<div style="clear: both; height: 3px"></div>
				<div style="float: left; width: 100px;">VOYAGE</div>
				<div style="float: left; width: 170px;">
					<?= form_input(array(
						'id'=>'textfield49',
						'name'=>'textfield49',
						'style'=>'width: 100%',
						'readonly'=>'readonly'
					)) ?>
				</div>
				<div style="clear: both; height: 3px"></div>
				<div style="float: left; width: 100px;">LOCAL VESEL</div>
				<div style="float: left; width: 170px;">
					<?= form_input(array(
						'id'=>'textfield50',
						'name'=>'textfield50',
						'style'=>'width: 100%',
						'readonly'=>'readonly'
					)) ?>
				</div>
				<div style="clear: both; height: 3px"></div>
				<div style="float: left; width: 100px;">CONNECT. AT</div>
				<div style="float: left; width: 170px;">
					<?= form_input(array(
						'id'=>'textfield51',
						'name'=>'textfield51',
						'style'=>'width: 100%',
						'readonly'=>'readonly'
					)) ?>
				</div>
				<div style="clear: both; height: 3px"></div>
				<div style="float: left; width: 100px;">CONT NO</div>
				<div style="float: left; width: 170px;">
					<?= form_input(array(
						'id'=>'textfield52',
						'name'=>'textfield52',
						'style'=>'width: 100%',
						'readonly'=>'readonly'
					)) ?>
				</div>				
   		<div style="clear: both; height: 2px"></div>
   		</div>
   		<div style="clear: both; height: 20px"></div>
   		<div style="float: left; width: 100px">
   			<div style="font-weight: bold;">Movement type</div>
   			<div style="clear: both; height: 5px" ></div>
   			<div style="font-size: 11px">
   			<?php 
   				$cy_cy = array('id'=>'cy_cy','name'=>'cy_cy','value'=>'cy_cy','class'=>'checkbox_bill');
   				echo form_checkbox($cy_cy).' CY/CY';
   			?>
   			</div>
   			<div style="clear: both"></div>
   			<div style="font-size: 11px">
   			<?php 
   				$cfs_cfs = array('id'=>'cfs_cfs','name'=>'cfs_cfs','value'=>'cfs_cfs','class'=>'checkbox_bill');
   				echo form_checkbox($cfs_cfs).' CFS/CFS';
   			?>
   			</div>
   			<div style="clear: both; height: 10px"></div>
   			<div style="font-weight: bold;">Other Movement</div>
   			<div style="clear: both; height: 5px"></div>
   			<div style="float: left; width: 40px"><?= form_input(array('style'=>'width: 40px','id'=>'diff_status_1')) ?></div>
			<div style="float: center; width: 10px">to</div>
   			<div style="float: left; width: 40px;"><?= form_input(array('style'=>'width: 40px','id'=>'diff_status_2')) ?></div>
   		</div>
   		<div style="float: left; width: 190px">
   			<div style="font-weight: bold;">MARKING & CONTAINER NOS</div>
   			<div style="clear: both; height: 2px"></div>
   			<div style="float: left; width: 185px;">
					<?= form_textarea(array(
						'id'=>'textarea7',
						'name'=>'textarea7',
						'style'=>'min-width: 98%; max-width: 98%; min-height: 353px; max-height: 390px;; text-transform: uppercase',
					)) ?>
				</div>
				<div style="clear: both; height: 1px"></div>
				<div style="float: left; width: 2px;">
  				</div>
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
    			'onclick'=>'save_detail(1)',
    			'value'=>'SAVE',
    			'style'=>'width: 120px')
    			) 
    		?>    		
    		</div>
    		<div style="float: left; width: 120px">
    		<?= form_submit(array('id'=>'cetak_form_bl','name'=>'cetak_form_bl','onclick'=>'cetak_bl_form()','value'=>'CETAK BL','style'=>'width: 100%')) ?>
    		</div>
			<div style="clear: both; height: 8px"></div>
			<div style="float: left; width: 118px">
				<?= form_button(array(
					'id'=>'btn_bl_history',
					'name'=>'btn_bl_history',
					'onclick'=>'show_bl_history()',
					'content'=>'HISTORY',
					'style'=>'width: 100%; padding: 6px 0;')
				) ?>
			</div>
			<div style="float: right; width: 118px">
				<?= form_submit(array(
					'id'=>'btn_final_bl',
					'name'=>'btn_final_bl',
					'onclick'=>'final_bl(); return false;',
					'value'=>'FINAL BL',
					'style'=>'width: 100%; padding: 6px 0;')
				) ?>
				<?= form_submit(array(
					'id'=>'btn_batal_final',
					'name'=>'btn_batal_final',
					'onclick'=>'batal_final_bl(); return false;',
					'value'=>'BATAL FINAL',
					'style'=>'width: 100%; padding: 6px 0; display:none;')
				) ?>
			</div>
			<div style="clear: both; height: 6px"></div>
			<div style="float: left; width: 250px">
				<?= form_submit(array(
					'id'=>'btn_delivered',
					'name'=>'btn_delivered',
					'onclick'=>'show_delivery_form(); return false;',
					'value'=>'TANDAI DISERAHKAN KE CUSTOMER',
					'style'=>'width: 100%; padding: 6px 0; display:none;')
				) ?>
			</div>
			<div style="clear: both; height: 6px"></div>
    		<div class="form_bck">
    			<div style="float: left; width: 100px;">FORM B/L</div>
					<div style="float: left; width: 120px;">
						<?= form_dropdown('select3', $option_form, $selected_form, 'id="select3" style="width: 100%;" onchange="set_value_img()"'); ?>
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
    		<div class="form_bck">	
    			<div>
    			<?php
				$express_release = array('id'=>'express_release','name'=>'express_release','value'=>'express_release','class'=>'checkbox_bill');	
   				echo form_checkbox($express_release).' Express Release BL';
   			?>
   			</div>
   			<div>
   			<?php 
   				$shipped_on_board = array('id'=>'shipped_on_board','name'=>'shipped_on_board','value'=>'shipped_on_board','class'=>'checkbox_bill');
   				echo form_checkbox($shipped_on_board).' Shipped On Board';
   			?>
   			</div>
   		<div>
    		<?php 
   			$net_weight_tidak_dicetak = array('id'=>'net_weight_tidak_dicetak','name'=>'net_weight_tidak_dicetak','value'=>'net_weight_tidak_dicetak','class'=>'checkbox_bill');
   			echo form_checkbox($net_weight_tidak_dicetak).' Net Weight tidak dicetak';
   		?>
   		</div>
   		<div>
   		<?php 
   			$cetak_jml_doc_origi = array('id'=>'cetak_jml_doc_origi','name'=>'cetak_jml_doc_origi','value'=>'cetak_jml_doc_origi','class'=>'checkbox_bill');
   			echo form_checkbox($cetak_jml_doc_origi).' Original BL';
   		?>
   		</div>
   		<div style="clear: both; height: 2px"></div>
   		</div>
   	
   	<div style="clear: both; height: 7px"></div>
   	<div class="form_bck">
   		<div style="float: left; width: 100px; color: #f90099">Gross Weight (KGs)</div>
			<div style="float: left; width: 120px;">
					<?= form_input(array(
						'id'=>'textfield58',
						'name'=>'textfield58',
						'style'=>'width: 100%',
						'readonly'=>'readonly'
					)) ?>
			</div>
		<div style="clear: both; height: 3px"></div>
		<div style="float: left; width: 100px; color: #f90099">Nett weight (KGs)</div>
			<div style="float: left; width: 120px;">
					<?= form_input(array(
						'id'=>'textfield59',
						'name'=>'textfield59',
						'style'=>'width: 100%',
					)) ?>
			</div>
		<div style="clear: both; height: 3px"></div>
		<div style="float: left; width: 100px;">Measurement (M<sup>3</sup>)</div>
			<div style="float: left; width: 120px;">
					<?= form_input(array(
						'id'=>'textfield60',
						'name'=>'textfield60',
						'style'=>'width: 100%',
						'readonly'=>'readonly'
					)) ?>
			</div>
		<div style="clear: both; height: 2px"></div>
		<div style="float: left; width: 100px;">Party</div>
			<div style="float: left; width: 120px;">
					<?= form_input(array(
						'id'=>'textfield61',
						'name'=>'textfield61',
						'style'=>'width: 100%',
						'readonly'=>'readonly'
					)) ?>
			</div>
		<div style="clear: both; height: 2px"></div>
   	</div>
   	<div style="clear: both; height: 2px"></div>
   	<b>FREIGHT</b>	   		
   	<div style="clear: both; height: 2px"></div>
   	<div class="form_bck">
   		<div style="float: right; margin-right: 15px">As Arange</div>
   		<div style="clear: both; height: 1px"></div>	
   		<div style="float: left; width: 130px;">
				<div style="float: left; width: 60px;">Freight</div>
					<div style="float: left; width: 70px;">
					<?= form_input(array(
						'id'=>'textfield62',
						'name'=>'textfield62',
						'style'=>'width: 100%',
						'disabled'=>'disabled'
					)) ?>
					</div>
				<div style="clear: both; height: 2px"></div>
				<div style="float: left; width: 60px;">Payable</div>
					<div style="float: left; width: 70px;">
					<?= form_input(array(
						'id'=>'textfield63',
						'name'=>'textfield63',
						'style'=>'width: 100%'
					)) ?>
					</div>
				<div style="clear: both; height: 2px"></div>	
			</div>
			<div style="float: right">
				<?php echo form_textarea(array('id'=>'textarea8','name'=>'textarea8','style'=>'max-width: 80px; min-width: 80px; min-height: 42px; max-height: 42px;')) ?>
			</div>
			<div style="clear: both; height: 2px"></div>
	
			<div style="float: left; width: 60px;">DOC/CFS</div>
					<div style="float: left; width: 160px;">
					<?= form_input(array(
						'id'=>'textfield64',
						'name'=>'textfield64',
						'style'=>'width: 100%'
					)) ?>
					</div>
			
			<div style="clear: both; height: 2px"></div>
   	</div>	
   	</div>   	
	</div>
	
	<input type="hidden" id="id_form" value="<?php echo $id ?>">
	<input type="hidden" id="shipper_id" value="<?php echo $shipper_id ?>">
	<input type="hidden" id="job">
	<input type="hidden" id="total">
	<input type="hidden" id="pack">
	<input type="hidden" id="shipping_mark">
	<input type="hidden" id="freights_charges">
	<input type="hidden" id="txtDestination_id" readonly="readonly">
	<input type="hidden" id="textRequestId" readonly="readonly">
</div>
<?php include "script_function.js" ?>