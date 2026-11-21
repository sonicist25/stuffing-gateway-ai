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
#movement-options label {
    margin-right: 3px; /* jarak horizontal antar checkbox */
    margin-bottom: 1px;  /* jarak vertical antar baris */
    display: inline-block;
	font-size:9px;
  }

</style>
<div id="msg"></div>
<div id="form-move"></div>
<div id="loading-bl" style="float: right;"></div>
   <div id="tab-2" class="the-tab" style="height: 570px;">
		<div class="tabtri-lading" style="float: left; width: 370px">
			<div style="float: left; width: 150px; font-weight: bold">BOOKING CODE</div>
			<div style="float: left; width: 160px;">
			<?= form_input(array(
						'id'=>'textfield39',
						'name'=>'textfield39',
						'disabled'=>'disabled',
						'style'=>'width: 100%',
				)) ?>
			</div>
			<div style="clear: both; height: 2px"></div>
			<div style="float: left; width: 150px; font-weight: bold">HBL NUMBER</div>
			<div style="float: left; width: 160px;">
			<?= form_input(array(
						'id'=>'bl_no',
						'name'=>'bl_no',
						'disabled'=>'disabled',
						'style'=>'width: 100%',
				)) ?>
			</div>
			<div style="clear: both; height: 2px"></div>
			<div style="float: left; width: 100px; color: #9322ff">SHIPPER</div>
			<div style="clear: both; height: 2px"></div>
			<div style="float: left; width: 345px;">
			<?= form_textarea(array(
						'id'=>'textarea2',
						'name'=>'textarea2',
						'style'=>'min-width: 98%; max-width: 98%; min-height: 75px; max-height: 75px; text-transform: uppercase',
				)) ?>
			</div>
			<div style="clear: both; height: 3px"></div>
			<div style="float: left; width: 80px; color: #9322ff">CONSIGNEE</div>
			<div style="float: left; width: 345px;">
			<?= form_textarea(array(
						'id'=>'textarea3',
						'name'=>'textarea3',
						'style'=>'min-width: 98%; max-width: 98%; min-height: 75px; max-height: 75px; text-transform: uppercase',
				)) ?>
			</div>
			<div style="clear: both; height: 3px"></div>
			<div style="float: left; width: 150px; color: #9322ff">NOTIFY PARTY</div>
			<div style="clear: both; height: 2px"></div>
			<div style="float: left; width: 345px;">
			<?= form_textarea(array(
						'id'=>'textarea4',
						'name'=>'textarea4',
						'style'=>'min-width: 98%; max-width: 98%; min-height: 85px; max-height: 85px; text-transform: uppercase',
				)) ?>
			</div>
			
			<div style="clear: both; height: 5px"></div>
			<div style="float: left; width: 200px;  font-weight: bold">MARK AND NOS</div>
			<div style="clear: both; height: 2px"></div>
			<div style="clear: both; height: 3px"></div>
			<div style="float: left; width: 345px;">
			<?= form_textarea(array(
						'id'=>'textarea7',
						'name'=>'textarea7',
						'style'=>'min-width: 345px; max-width: 345px; min-height: 250px; max-height: 250px; text-transform: uppercase',
				)) ?>
			</div>
			<?php  
			/*
  			if($attc_discription != '') {
  				echo '<a id="move_discription" href="javascript:void(0)" style="text-decoration: none; color: #f33; float: right" onclick="move_discription('.$id.',\'1\')">Delete attachement</a>';
  			} else {
  				echo '<a id="move_discription" href="javascript:void(0)" style="text-decoration: none; color: #f33; float: right" onclick="move_discription('.$id.',\'0\')">Create attachement</a>';					
  			}
  			*/
  			?> 
		</div>
		 	  
   	<div class="tabtri-lading" style="float: left; width: 300px; height: 120px; margin-left: 10px;">
	Port Info
   		<div class="form_bck">
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
				<div style="float: left; width: 100px;">LOADING</div>
				<div style="float: left; width: 170px;">
					<?= form_input(array(
						'id'=>'textfield44',
						'name'=>'textfield44',
						'style'=>'width: 100%; text-transform: uppercase'
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
				<div style="float: left; width: 100px;">DESTINATION</div>
				<div style="float: left; width: 170px;">
					<?= form_input(array(
						'id'=>'textfield43',
						'name'=>'textfield43',
						'style'=>'width: 100%'
					)) ?>
				</div>
				<div style="clear: both; height: 2px"></div>
			</div>
			<div style="clear: both; height: 2px"></div>
			Vessel Information
			<div style="clear: both; height: 2px"></div>
			<div class="form_bck">
				<div style="clear: both; height: 1px"></div>
				<div style="float: left; width: 100px;">VESSEL</div>
				<div style="float: left; width: 170px;">
					<?= form_input(array(
						'id'=>'textfield48',
						'name'=>'textfield48',
						'style'=>'width: 100%',
						'readonly'=>'readonly'
					)) ?>
				</div>
				<div style="clear: both; height: 1px"></div>
				<div style="float: left; width: 100px;">CONNECTING</div>
				<div style="float: left; width: 170px;">
					<?= form_input(array(
						'id'=>'textfield49',
						'name'=>'textfield49',
						'style'=>'width: 100%',
						'readonly'=>'readonly'
					)) ?>
				</div>
				<div style="clear: both; height: 1px"></div>
				<div style="float: left; width: 100px;">CONTAINER/SEAL/TYPE</div>
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
		<div style="clear: both; height: 2px"></div>
		<div style="float: left; width: 180px">
   			<div style="font-weight: bold;">DESCRIPTION OF GOODS</div>
   			<div style="clear: both; height: 1px"></div>
   			<div style="float: left; width: 300px;">
					<?= form_textarea(array(
						'id'=>'textarea5',
						'name'=>'textarea5',
						'style'=>'min-width: 98%; max-width: 98%; min-height: 290px; max-height: 290px;; text-transform: uppercase',
					)) ?>
				</div>
   		</div>
   		<div style="clear: both; height: 20px"></div>
   		<div style="float: left; width: 560px">
   			<div style="font-weight: bold;">Type of Service</div>
			<div style="clear: both; height: 5px"></div>
		<div id="movement-options">
		  <label><input type="radio" name="movement_type" value="port to port"> Port to Port</label>
		  <label><input type="radio" name="movement_type" value="door to door"> Door to Door</label>
		  <label><input type="radio" name="movement_type" value="door to port"> Door to Port</label>
		  <label><input type="radio" name="movement_type" value="port to door"> Port to Door</label>
		  <label><input type="radio" name="movement_type" value="CY/CY"> CY/CY</label>
		  <label><input type="radio" name="movement_type" value="CY/CFS"> CY/CFS</label>
		  <label><input type="radio" name="movement_type" value="CFS/CY"> CFS/CY</label>
		  <label><input type="radio" name="movement_type" value="CFS/CFS"> CFS/CFS</label>
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
    			'onclick'=>'save_detail()',
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
			</div>
    		<div style="clear: both; height: 5px"></div>
    		<div class="form_bck">	
    	
   			<div>
   			<?php 
   				$shipped_on_board = array('id'=>'shipped_on_board','name'=>'shipped_on_board','value'=>'shipped_on_board','class'=>'checkbox_bill');
   				echo form_checkbox($shipped_on_board).' Shipped On Board';
   			?>
   			</div>
   		<div style="clear: both; height: 2px"></div>
   		</div>
   	
   	<div style="clear: both; height: 7px"></div>
   	<div class="form_bck">
   		<div style="float: left; width: 100px; color: #f90099">Gross (KGs)</div>
			<div style="float: left; width: 120px;">
					<?= form_input(array(
						'id'=>'textfield58',
						'name'=>'textfield58',
						'style'=>'width: 100%',
						'readonly'=>'readonly'
					)) ?>
			</div>
		<div style="clear: both; height: 3px"></div>
		<div style="float: left; width: 100px; color: #f90099">Netto (KGs)</div>
			<div style="float: left; width: 120px;">
					<?= form_input(array(
						'id'=>'textfield59',
						'name'=>'textfield59',
						'style'=>'width: 100%',
					)) ?>
			</div>
		<div style="clear: both; height: 3px"></div>
		<div style="float: left; width: 100px;">Measure (M<sup>3</sup>)</div>
			<div style="float: left; width: 120px;">
					<?= form_input(array(
						'id'=>'textfield60',
						'name'=>'textfield60',
						'style'=>'width: 100%',
						'readonly'=>'readonly'
					)) ?>
			</div>
		<div style="clear: both; height: 2px"></div>
		<div style="float: left; width: 100px;">Total of Packs</div>
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
   		<div style="clear: both; height: 1px"></div>	
   		<div style="float: left; width: 280px;">
				<div style="float: left; width: 80px;">Freight</div>
					<div style="float: left; width: 130px;">
					<?= form_input(array(
						'id'=>'textfield62',
						'name'=>'textfield62',
						'style'=>'width: 100%',
						'disabled'=>'disabled'
					)) ?>
					</div>
				<div style="clear: both; height: 2px"></div>
				<div style="float: left; width: 80px;">Payable At</div>
					<div style="float: left; width: 130px;">
					<?= form_input(array(
						'id'=>'textfield63',
						'name'=>'textfield63',
						'style'=>'width: 100%'
					)) ?>
					</div>
				<div style="clear: both; height: 2px"></div>	
			</div>
			<div style="clear: both; height: 2px"></div>
	
	</div>	
   	<div style="clear: both; height: 2px"></div>
   	<b>BL SIGNATURE</b>	   		
   	<div style="clear: both; height: 2px"></div>	
	<div class="form_bck">		
			<div style="clear: both; height: 2px"></div>
			<div style="clear: both; height: 2px"></div>
				<div style="float: left; width: 100px; color: #f90099">AS AGENT OF THE BLA BLA</div>
				<div style="float: left; width: 120px;">
					<?= form_input(array(
						'id'=>'textfield57',
						'name'=>'textfield57',
						'style'=>'width: 100%'
					)) ?>
				</div>
		<div style="clear: both; height: 2px"></div>
   	</div>	
   	</div>   	
	</div>
	
	<input type="hidden" id="id_form" value="<?php echo $booking_code ?>">
	<input type="hidden" id="shipper_id">
	<input type="hidden" id="job">
	<input type="hidden" id="total" >
   <input type="hidden" id="pack">
	<input type="hidden" id="shipping_mark">
	<input type="hidden" id="freights_charges">
	<input type="hidden" id="txtDestination_id" readonly="readonly">
	<input type="hidden" id="textRequestId" readonly="readonly">
</div>
<?php include "draft_script_function.php" ?>