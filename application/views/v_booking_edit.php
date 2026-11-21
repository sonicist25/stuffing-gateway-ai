<style type="text/css">
#btn_nama_agen,#btnTracking,#btnPindah,#cetak_form_bl,#btn_edit_booking,#btn_print_bc
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

* {
  box-sizing: border-box;
}

/* Create three equal columns that floats next to each other */
.column {
  float: left;
  width: 33.33%;
  padding: 10px;
  height: 750px;
}

/* Clear floats after the columns */
.row:after {
  content: "";
  display: table;
  clear: both;
}
.vertical { 
            border-left: 2px solid blue; 
            height: 600px; 
            position:absolute; 
            left: 50%; 
        }
		
textarea#textareaShipper, textarea#textareaConsignee, textarea#textareaNotify {
	width: 400px;
	height: 120px;
	border: 3px solid #cccccc;
	padding: 5px;
	font-family: Tahoma, sans-serif;
	background-position: bottom right;
	background-repeat: no-repeat;
}
textarea#textareaDog, textarea#textareaMarking, textarea#textareaRemark {
	width: 400px;
	height: 25px;
	border: 3px solid #cccccc;
	padding: 5px;
	font-family: Tahoma, sans-serif;
	background-position: bottom right;
	background-repeat: no-repeat;
}
input[type="text"], input[type="datetime-local"] {
	height: 26px; /* match Select2 default height */
	padding: 6px 12px;
	font-size: 14px;
	line-height: 1.42857143;
	border: 1px solid #ccc;
	border-radius: 4px;
	box-sizing: border-box;
}
input[type="form"] {
  border:  3px solid #cccccc;
  padding: 5px;
}
.dropdown {
  border:  3px solid #cccccc;
  background-image: url(bg.gif);
  padding: 5px;
}
#TypeTransaction, #textMovementtype, #dropdownPortdestination, #dropdownPortLoading, #txtETDJKT, #txtETA, input#inputPickup, input#inputDrop, #dimention {
  width : 400px;
  height :25px;
  border:  3px solid #cccccc;
  padding: 5px;
}
#txtETDJKT, #txtETA { border: none; }

.reveal-if-active {
  opacity: 0;
  max-height: 0;
  overflow: hidden;
   width : 170px;
}
input[type="radio"]:checked ~ .reveal-if-active,
input[type="checkbox"]:checked ~ .reveal-if-active {
  opacity: 1;
  max-height: 100px;
  overflow: visible;
}

.rTable {
   display: block;
   width: 100%;
}
.rTableHeading, .rTableBody, .rTableFoot, .rTableRow{
   clear: both;
}
.rTableHead, .rTableFoot, .rTableHeadET{
   background-color: #DDD;
   font-weight: bold;
}
.rTableCell, .rTableHead {
   border: 1px solid #999999;
   float: left;
   height: 28px;
   overflow: hidden;
   padding: 3px 1.8%;
   width: 33.2%;
}

.rTableET, .rTableHeadET {
   border: 1px solid #999999;
   float: left;
   height: 30px;
   overflow: hidden;
   padding: 3px 1.8%;
   width: 50%;
}
.rTable:after {
   visibility: hidden;
   display: block;
   font-size: 0;
   content: " ";
   clear: both;
   height: 0;
}
.pac-container {
    background-color: #FFF;
    z-index: 3000;
    position: fixed;
    display: inline-block;
    float: left;
}
.modal{ z-index: 20; }
.modal-backdrop{ z-index: 10; }

.custom-select2-item .item-code {
	font-size: 11px;
	color: #888;
}
.select2-results__option--highlighted .custom-select2-item .item-code {
	color: #ffffff;
}

/* Custom Styles For Schedule Search */
.form-field {
    padding: 5px 8px;
    font-size: 13px;
    border: 1px solid #ccc;
    border-radius: 4px;
}
.btn {
    padding: 6px 12px;
    border: none;
    background-color: #007bff;
    color: #fff;
    border-radius: 4px;
    cursor: pointer;
}
.btn:hover { background-color: #0056b3; }
.swal-custom {
    background-color: #007bff !important;
    color: white !important;
    border: none;
    border-radius: 6px;
    padding: 8px 12px;
    font-size: 14px;
    cursor: pointer;
    flex: 1 1 45%;
    text-align: center;
}
.swal-custom small {
    display: block;
    font-size: 11px;
    color: #f1f1f1;
    margin-top: 4px;
}
</style>

<?php $data = $this->booking_model->collect_data_booking($_POST['id_booking'],$_POST['booking_code']);?>

<div style="display: flex; gap: 15px; align-items: stretch; flex-wrap: wrap; margin-bottom: 10px;">
  <div class="toolbar-search"
       style="flex: 0 1 auto; background-color: #e5f2ff; padding: 10px 10px; border-radius: 8px; border: 1px dashed #007bff; box-shadow: 0 2px 5px rgba(0,0,0,0.1); display: flex; flex-direction: column; justify-content: space-between;">
    <label for="searchForm" style="font-weight: bold; color: #0056b3; display: block; margin-bottom: 8px;">
      📅 Ubah Schedule Rute:
    </label>
    <form method="post" id="searchForm" style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
      <select id="region_id" name="region_id" class="form-field" style="flex: 0 0 150px; padding: 6px; border-radius: 5px; border: 1px solid #ccc;">
        <option value="">Select Region</option>
      </select>

      <select id="city_identifier" name="city_identifier" class="form-field" style="flex: 0 0 200px; padding: 6px; border-radius: 5px; border: 1px solid #ccc;">
        <option value="">Select City ID / ETA City ID</option>
      </select>

      <select id="etd_jkt" name="etd_jkt" class="form-field" style="flex: 0 0 150px; padding: 6px; border-radius: 5px; border: 1px solid #ccc;">
        <option value="">Select ETD JKT</option>
      </select>

      <input type="submit" value="Search Schedule" class="btn btn-primary"
             style="padding: 6px 15px; border-radius: 5px; background: #007bff; color: #fff; border: none; cursor: pointer;">
    </form>
  </div>

  <!-- ================= PENGAWASAN KEPATUHAN AEO : PEB / NPE (multi-PEB, upload PDF) ================= -->
  <?php $this->load->view('pricelist/partials/peb_widget', array(
      'booking_code' => $data['booking']->booking_code,
      'widget_id'    => 'pebw',
      'layout'       => 'flex',
      // sync_url default = api/ticket_warehouse/sync_peb: setiap PEB/NPE berubah, gudang (WMS) otomatis diperbarui
  )); ?>
  <!-- ================================================================================================= -->
</div>

<div class="row">
  <div class="column" style="background-color:#aaa;">
			<label for="TypeTransaction">Type of Shipment:</label><br>
			<div class="select" style="float: left; width: 400px;">
			  <select id="TypeTransaction_display" style="width: 100%; height: 26px; padding: 2px 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px;" disabled>
				<option value="LCL" selected="selected">OCEAN LCL</option>
				<option value="FCL">OCEAN FCL</option>
				<option value="AIR">AIR FREIGHT</option>
				<option value="TRUCKING">TRUCKING</option>
			  </select>
			  <!-- hidden input harus punya id="TypeTransaction" -->
			 <input type="hidden" name="TypeTransaction" id="TypeTransaction" value="LCL">
			</div>

			<div style="clear: both; height: 2px"></div>
				<label for="textMovementtype">Movement Type:</label><br>
				<div style="float: left; width: 120px;"> 
				<select name="textMovementtype" id="textMovementtype" style="width: 100%; height: 26px; padding: 2px 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px;" >
				<option value="Port to Port"<?php if($data['booking']->movement_type=="Port to Port") echo 'selected="selected"'; ?>>Port to Port</option>
				<option value="Port to Door"<?php if($data['booking']->movement_type=="Port to Door") echo 'selected="selected"'; ?>>Port to Door</option>
				<option value="Door to Door"<?php if($data['booking']->movement_type=="Door to Door") echo 'selected="selected"'; ?>>Door to Door</option>
				<option value="Door to Port"<?php if($data['booking']->movement_type=="Door to Port") echo 'selected="selected"'; ?>>Door to Port</option>
				</select>
				</div>
		<div style="clear: both; height: 2px"></div>
			<label for="booking_number">Booking Number:</label><br>
			<div style="clear: both; height: 5px"></div>
			<div style="float: left; width: 400px;">
				<?= form_input(array(
						'id'=>'booking_number',
						'style'=>'width: 100%',
						'value'=>$data['booking']->booking_code,
						'readonly'=>'readonly'
				)) ?>
			</div>
		<div style="clear: both; height: 2px"></div>
			<label for="textfieldSI">SI Number From Shipper:</label><br>
			<div style="clear: both; height: 5px"></div>
			<div style="float: left; width: 400px;">
				<?= form_input(array(
						'id'=>'textfieldSI',
						'style'=>'width: 100%',
						'value'=>$data['booking']->si_number,
				)) ?>
			</div>
				<div style="clear: both; height: 5px"></div>
			<label for="shipper">Shipper Detail:</label><br>
<div style="float: left; width: 400px;">
   <select id="shipper" name="shipper_name_input" class="form-control" style="width: 100%;">
    <?php if(!empty($data['detail']->shipper_name)): ?>
        <option value="<?php echo $data['detail']->shipper_name; ?>" selected="selected">
            <?php echo $data['detail']->shipper_name; ?>
        </option>
    <?php endif; ?>
</select>

<input type="hidden" id="idshipper" name="idshipper" value="<?php echo $data['booking']->shipper_id; ?>">
</div>
			<div style="clear: both; height: 2px"></div>
			<div style="float: left; width: 400px;">
			<textarea name="textareaShipper" id="textareaShipper"><?php echo empty($data['detail']->shipper_address)?"":$data['detail']->shipper_address ;?></textarea>
			</div>
			<div style="clear: both; height: 10px"></div>
			<label for="contactshipper">Shipper Contact Person:</label><br>
			<div style="clear: both; height: 5px"></div>
			<div style="float: left; width: 400px;">
				<?= form_input(array(
						'id'=>'contactshipper',
						'style'=>'width: 100%',
						'value' => isset($data['detail']->cp_name) ? $data['detail']->cp_name : '',
				)) ?>
			</div>
			<div style="clear: both; height: 10px"></div>
			<label for="contactemail">Shipper Contact Email(s) (Pisah dengan koma jika lebih dari 1):</label><br>
			<div style="clear: both; height: 5px"></div>
			<div style="float: left; width: 400px;">
				<?= form_input(array(
						'id' => 'contactemail',
						'style' => 'width: 100%',
						'value' => isset($data['detail']->cp_email) ? $data['detail']->cp_email : '',
				)) ?>
			</div>
			<div id="emailError" style="margin-top: 5px;"></div> 
				<div style="clear: both; height: 10px"></div>
			<label for="consignee">Consignee Detail:</label><br>
			<div style="float: left; width: 400px;">
			<input id="consignee" type="text" name="consignee" style="width:100%" value="<?php echo $data['detail']->consignee_name;?>">
			</div>
			<div style="clear: both; height: 2px"></div>
			<div style="float: left; width: 400px;">
			<textarea name="textareaConsignee" id="textareaConsignee"><?php echo empty($data['detail']->consignee)?"":$data['detail']->consignee ;?></textarea>
			</div>
			</div>
  <div class="column" style="background-color:#bbb;">
			<div style="clear: both; height: 5px"></div>
			<label for="notify">Notify Detail:</label><br>
			<div style="float: left; width: 400px;">
			<input id="notify" type="text" name="notify" style="width:100%" value="<?php echo empty($data['detail']->notify_name)?"":$data['detail']->notify_name;?>">
			</div>
			<div style="clear: both; height: 2px"></div>
			<div style="float: left; width: 400px;">
			<textarea name="textareaNotify" id="textareaNotify"><?php echo empty($data['detail']->notify)?"":$data['detail']->notify ;?></textarea>
			</div>
			<div style="clear: both; height: 5px"></div>
			<label for="textareamarkingNos">Marking And No:</label><br>
			<div style="float: left; width: 400px;">
			<textarea name="textareamarkingNos" id="textareamarkingNos"; style="width:100%"><?php echo empty($data['detail']->marking)?"":$data['detail']->marking;?></textarea>
			</div>
			<div style="clear: both; height: 5px"></div>
			<label for="textareadesc">Description of Goods:</label><br>
			<div style="float: left; width: 400px;">
			<textarea name="textareadesc" id="textareadesc" style="width:100%; height:120px;"><?php echo empty($data['detail']->description_of_goods)?"":$data['detail']->description_of_goods ;?></textarea>
			</div>
			<div style="clear: both; height: 5px"></div>
			<label for="textQty">Quantity:</label>
			<label for="textPackaging" style="margin-left:50px;">Packaging:</label> <br>
				<div style="float: left;">
						<?= form_input(array(
						'id'=>'textQty',
						'name'=>'textQty',
						'style'=>'width: 25%',
					    'value'=>empty($data['detail']->quantity)?"":$data['detail']->quantity,
					)) ?>
					
					<?= form_input(array(
						'id'=>'textPackaging',
						'name'=>'textPackaging',
						'style'=>'width: 75%, height:25px',
						'value'=>empty($data['detail']->package_type)?"":$data['detail']->package_type,
					)) ?>
			</div>
			<div style="clear: both; height: 5px"></div>
			<div class="rTable" >
				<div class="rTableRow">
				<div class="rTableHead"><strong>Weight (Kgs)</strong></div>
				<div class="rTableHead"><span style="font-weight: bold;">Nett Weight (Kgs)</span></div>
				<div class="rTableHead"><strong>Measurement (M<sup>3</sup>)</strong></div>
				</div>
				<div class="rTableRow">
				<div class="rTableCell"><?= form_input(array(
						'id'=>'textweight',
						'name'=>'textweight',
						'type'=>'text',
						'style'=>'width: 100%',
						'value'=>empty($data['detail']->weight)?"":$data['detail']->weight,
					)) ?></div>
				<div class="rTableCell"><?= form_input(array(
						'id'=>'textnetto',
						'name'=>'textnetto',
						'type'=>'text',
						'style'=>'width: 100%',
						'value'=>empty($data['detail']->netto)?"":$data['detail']->netto,
					)) ?></div>
				<div class="rTableCell"><?= form_input(array(
						'id'=>'textmeas',
						'name'=>'textmeaas',
						'type'=>'text',
						'style'=>'width: 100%',
						'value'=>empty($data['detail']->volume)?"":$data['detail']->volume,
					)) ?></div>
				</div>
				</div>
		
			<div style="clear: both; height: 5px"></div>
			<label for="textareaDog">Commodities:</label><br>
			<div style="float: left;">
			<?= form_textarea(array(
						'id'=>'textareaDog',
						'name'=>'textareaDog',
						'value'=>empty($data['detail']->description_print)?"":$data['detail']->description_print,
				)) ?>
			</div>
			<div style="clear: both; height: 5px"></div>
   			<label for="textareaMarking">Marking:</label><br>
   			<div style="float: left;">
					<?= form_textarea(array(
						'id'=>'textareaMarking',
						'name'=>'textareaMarking',
						'value'=>empty($data['detail']->shipping_mark)?"":$data['detail']->shipping_mark,
					)) ?>
				</div>
			<div style="clear: both; height: 5px;"></div>
			<!-- CARGO TYPE -->
				<label for="cargo_type">Cargo Type:</label><br>
				<div style="float: left; width: 400px;">
					<!-- Tambahan height: 26px dan border radius agar seragam dengan input text -->
					<select id="cargo_type" name="cargo_type" style="width: 100%; height: 26px; padding: 2px 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px;" onchange="toggleDGClass(this.value)">
						<option value="GeneralCargo" <?= (isset($data['booking']->cargo_type) && $data['booking']->cargo_type == 'GeneralCargo') ? 'selected' : '' ?>>General Cargo</option>
						<option value="DG" <?= (isset($data['booking']->cargo_type) && $data['booking']->cargo_type == 'DG') ? 'selected' : '' ?>>Dangerous Goods (DG)</option>
					</select>
				</div>
				<div style="clear: both; height: 5px;"></div>

				<!-- DG CLASS -->
				<div id="dg_class_wrapper" style="display: <?= (isset($data['booking']->cargo_type) && $data['booking']->cargo_type == 'DG') ? 'block' : 'none' ?>;">
					<label for="dg_class">DG Class:</label><br>
					<div style="float: left; width: 400px;">
						<input type="text" id="dg_class" name="dg_class" style="width: 100%;" value="<?= isset($data['detail']->dg_class) ? $data['detail']->dg_class : '' ?>">
					</div>
					<div style="clear: both; height: 5px;"></div>
				</div>

				<!-- HS CODE -->
				<label for="hs_code">HS Code:</label><br>
				<div style="float: left; width: 400px;">
					<input type="text" id="hs_code" name="hs_code" style="width: 100%;" value="<?= isset($data['detail']->hs_code) ? $data['detail']->hs_code : '' ?>">
				</div>
				<div style="clear: both; height: 5px;"></div>
		</div>
  <div class="column" style="background-color:#ccc;">
  <div class="form-group">
				<label for="route_type_text">HBL Number</label>
				<input type="text" id="hbl_draft" name="hbl_draft" readonly style="width: 100%; background-color: #eee; border: 1px solid #ccc; padding: 6px;" value="<?php echo($data['booking']->house_bl);?>">
		</div>
		<div style="clear: both; height: 5px"></div>
		<div class="form-group">
				<label for="route_type_text">Route Type</label>
				<input type="text" id="route_type_text" name="route_type_text" readonly style="width: 100%; background-color: #eee; border: 1px solid #ccc; padding: 6px;" value="<?php echo isset($data['booking']->route_type) ? $data['booking']->route_type : ''; ?>">
				<input type="hidden" id="route_type" name="route_type" value="<?php echo isset($data['booking']->route_type) ? $data['booking']->route_type : ''; ?>">
		</div>
			<div style="clear: both; height: 5px"></div>
				<div class="rTable" >
				<div class="rTableRow">
					<div class="rTableRow">
						<div class="rTableHeadET"><strong id="label_etd">ETD</strong></div>
						<div class="rTableHeadET"><strong id="label_eta">ETA</strong></div>
				</div>
				</div>
				<div class="rTableRow">
				<div class="rTableET">
						<?= form_input(array(
						'id'=>'txtETDJKT',
						'name'=>'txtETDJKT',
						'type'=>'date',
						'style'=>'width: 100%; border-width:0px; border:none;',
						'value'=>empty($data['booking']->expect_etd)?"":$data['booking']->expect_etd,
					)) ?></div>
				<div class="rTableET">
						<?= form_input(array(
						'id'=>'txtETA',
						'name'=>'txtETA',
						'type'=>'date',
						'style'=>'width: 100%; border-width:0px; border:none;',
						'value'=>empty($data['booking']->eta)?"":$data['booking']->eta,
					)) ?></div>
			</div>
		</div>
		<div style="clear: both; height: 5px"></div>
				<div class="rTable" >
				<div class="rTableRow">
				<div class="rTableHeadET"><strong>Closing Doc</strong></div>
				<div class="rTableHeadET"><strong>Stuffing Date</strong></div>
				</div>
				<div class="rTableRow">
				<div class="rTableET">
						<?= form_input(array(
						'id'=>'txtClosingDoc',
						'name'=>'txtClosingDoc',
						'type'=>'datetime-local',
						'style'=>'width: 100%; border-width:0px; border:none;',
						'value'=>$data['booking']->closing_date ,
					)) ?></div>
				<div class="rTableET">
						<?= form_input(array(
						'id'=>'txtClosingCar',
						'name'=>'txtClosingCar',
						'type'=>'datetime-local',
						'style'=>'width: 100%; border-width:0px; border:none;',
						'value'=>$data['booking']->closing_cargo,
					)) ?></div>
			</div>
		</div>
		<div style="clear: both; height: 5px"></div>
		<label for="dropdownPortLoading">Port of Loading :</label>
		<label for="code_pol" style="margin-left:225px;">Kode POL:</label> <br>
		<div style="display: flex; gap: 120px;">
			<div style="float: left; width: 220px;">
				<select id="dropdownPortLoading" name="dropdownPortLoading" style="width:100%" disabled>
					<?php if (!empty($data['booking']->origin_city)): ?>
						<option value="<?php echo $data['booking']->origin_city; ?>" selected="selected">
							<?php echo $data['booking']->origin_city; ?>
						</option>
					<?php endif; ?>
				</select>
			</div>
			<div style="width: 100px;">
				<input id="code_pol" type="text" name="code_pol" value="<?= isset($data['booking']->pol_code) ? $data['booking']->pol_code : '' ?>" style="width:100%" disabled/>
			</div>
		</div>
		<div style="clear: both; height: 5px"></div>

		<label for="dropdownPortTrans">Port of Transhipment :</label>
		<label for="code_pot" style="margin-left: 206px;">Kode POT:</label> <br>
		<div style="display: flex; gap: 120px;">
			<div style="float: left; width: 220px;">
				<select id="dropdownPortTrans" name="dropdownPortTrans" style="width:100%" disabled>
					<?php if (!empty($data['booking']->trans_city)): ?>
						<option value="<?php echo $data['booking']->trans_city; ?>" selected="selected">
							<?php echo $data['booking']->trans_city; ?>
						</option>
					<?php endif; ?>
				</select>
			</div>
			<div style="width: 100px;">
				<input id="code_pot" type="text" name="code_pot" value="<?= isset($data['booking']->pot_code) ? $data['booking']->pot_code : '' ?>" style="width:100%" disabled/>
			</div>
		</div>
		<div style="clear: both; height: 5px"></div>

		<label for="dropdownPortdestination">Port of Destination:</label>
		<label for="code_pod" style="margin-left: 220px;">Kode POD:</label> <br>
		<div style="display: flex; gap: 120px;">
			<div style="float: left; width: 220px;">
				<select id="dropdownPortdestination" name="dropdownPortdestination" style="width:100%" disabled>
					<?php if (!empty($data['booking']->destination_city)): ?>
						<option value="<?php echo $data['booking']->destination_city; ?>" selected="selected">
							<?php echo $data['booking']->destination_city; ?>
						</option>
					<?php endif; ?>
				</select>
			</div>
			<div style="width: 100px;">
				<input id="code_pod" type="text" name="code_pod" value="<?= isset($data['booking']->pod_code) ? $data['booking']->pod_code : '' ?>" style="width:100%" disabled/>
			</div>
		</div>
		<div style="clear: both; height: 15px"></div>
		<label for="vessel">Vessel:</label>  
		<label for="voyage" style="margin-left: 160px;">Connecting Vessel:</label><br>

		<div style="display: flex; gap: 10px;">
			<div style="width: 190px;">
				<input id="vessel" type="text" name="vessel" style="width:100%" value="<?php echo $data['booking']->vessel;?>" disabled/>
			</div>
			<div style="width: 190px;">
				<input id="voyage" type="text" name="voyage" style="width:100%" value="<?php echo $data['booking']->voyage;?>"/>
			</div>
		</div>

		<div style="clear: both; height: 15px"></div>	
		<label for="warehouse">Warehouse:</label>  
		<label for="wh_arrival" style="margin-left: 125px;">Estimasi Waktu Tiba di Gudang: <span style="color:#d9534f;" title="Wajib diisi">*</span></label><br>
		<div style="display: flex; gap: 10px;">
			<div style="width: 190px;">
				<input id="warehouse" type="text" name="warehouse" style="width:100%" value="<?php echo $data['booking']->warehouse;?>"/>
			</div>
			<div style="width: 190px;">
				<?php
					// datetime-local hanya menerima format "Y-m-d\TH:i"; format DB "Y-m-d H:i:s" akan tampil KOSONG
					$wh_raw = isset($data['detail']->wh_arrival) ? trim((string)$data['detail']->wh_arrival) : '';
					$wh_val = ($wh_raw !== '' && strpos($wh_raw, '0000-00-00') !== 0 && strtotime($wh_raw)) ? date('Y-m-d\TH:i', strtotime($wh_raw)) : '';
				?>
				<input type="datetime-local" id="wh_arrival" name="wh_arrival" style="width:100%" required value="<?php echo $wh_val; ?>"/>
			</div>
		</div>
		<div style="clear: both; height: 15px"></div>	
		<label for="do_number">No. FO booking truck (Mceasy):</label>  
		<label for="sales" style="margin-left: 10px;">Sales:</label><br>
		<div style="display: flex; gap: 10px;">
			<div style="width: 190px;">
				<input id="do_number" type="text" name="do_number" style="width:100%" value="<?php echo isset($data['detail']->do_number) ? $data['detail']->do_number : '';?>"/>
			</div>
			<div style="width: 160px;">
				<input id="sales_name" type="text" name="sales_name" style="width:100%" value="<?php echo $data['sales'];?>"/>
			</div>
		</div>


		<div style="clear: both; height: 5px"></div>
<div class="rTable">
  <div class="rTableRow">
    <div class="rTableHead"><strong>FREIGHT</strong></div>
    <div class="rTableHead"><strong>INCOTERMS</strong></div>
    <div class="rTableHead"><strong>BL TYPE</strong></div>
  </div>
  <div class="rTableRow">
    <div class="rTableCell">
	  <select name="textfieldFreight" id="textfieldFreight" style="border:none;">
		<option value="PREPAID" <?= ($data['booking']->freight == 'PREPAID') ? 'selected' : '' ?>>PREPAID</option>
		<option value="COLLECT" <?= ($data['booking']->freight == 'COLLECT') ? 'selected' : '' ?>>COLLECT</option>
	  </select>
	</div>
    <div class="rTableCell">
      <select name="textfieldIncoterm" id="textfieldIncoterm" style="border:none;">
        <option value="EXWORK"<?php if($data['booking']->incoterm=="EXWORK") echo 'selected="selected"'; ?>>EXW (Ex Works)</option>
        <option value="FCA"<?php if($data['booking']->incoterm=="FCA") echo 'selected="selected"'; ?>>FCA (Free Carrier)</option>
        <option value="FOB"<?php if($data['booking']->incoterm=="FOB") echo 'selected="selected"'; ?>>FOB (Free On Board)</option>
        <option value="CFR"<?php if($data['booking']->incoterm=="CFR") echo 'selected="selected"'; ?>>CFR (Cost and Freight)</option>
        <option value="CIF"<?php if($data['booking']->incoterm=="CIF") echo 'selected="selected"'; ?>>CIF (Cost, Insurance & Freight)</option>
        <option value="DAP"<?php if($data['booking']->incoterm=="DAP") echo 'selected="selected"'; ?>>DAP (Delivered At Place)</option>
        <option value="DPU"<?php if($data['booking']->incoterm=="DDU") echo 'selected="selected"'; ?>>DPU (Delivered At Place Unloaded)</option>
        <option value="DDP"<?php if($data['booking']->incoterm=="DDP") echo 'selected="selected"'; ?>>DDP (Delivered Duty Paid)</option>
      </select>
    </div>
    <div class="rTableCell">
      <select name="BLtype" id="BLtype" style="border:none;">
        <option value="3 ORIGINAL">3 ORIGINAL</option>
        <option value="EXPRESS RELEASE">EXPRESS RELEASE</option>
      </select>
    </div>
  </div>
</div>

			<div style="clear: both; height: 10px"></div>
			<label for="textareaNote" style="color: #b94a48; font-weight: bold;">
  ⚠️ Shipper Instruction / Note (Untuk Team):
			</label><br>

			<div style="display: flex; align-items: flex-start; gap: 15px;">
			  <div style="flex: 1;">
				<textarea name="textareaNote" id="textareaNote"
				  style="width: 100%; height: 80px; border: 3px solid #f0ad4e; background-color: #fcf8e3; padding: 5px; font-family: Tahoma, sans-serif;"
				  placeholder="Instruksi khusus dari shipper..."><?php echo($data['detail']->shipper_note);?></textarea>
			  </div>

			  <div style="display: flex; flex-direction: column; gap: 10px; width: 120px;">
				<?php echo form_submit(array(
				  'id'=>'btn_edit_booking',
				  'name'=>'btn_edit_booking',
				  'onclick'=>'return save_edit_booking()',
				  'value'=>'SAVE',
				  'style'=>'width: 100%; padding: 8px; border-radius: 5px; background: #007bff; color: #fff; border: none; cursor: pointer;'
				)) ?>

				<?php echo form_submit(array(
				  'id'=>'btn_print_bc',
				  'name'=>'btn_print_bc',
				  'onclick'=>'print_bc()',
				  'value'=>'Print BC',
				  'style'=>'width: 100%; padding: 8px; border-radius: 5px; background: #6c757d; color: #fff; border: none; cursor: pointer;'
				)) ?>
			  </div>
			</div>

			<div style="clear: both; height: 30px"></div>
			
			<input type="hidden" id="shipper_id" value="<?php echo $data['booking']->shipper_id; ?>">
			<input type="hidden" id="sales" value="<?php echo $data['booking']->sales; ?>">
			<input type="hidden" id="consignee_id" value="<?php echo $data['booking']->consignee_id; ?>">
			<input type="hidden" id="notify_id" value="<?php echo $data['booking']->notify_id; ?>">
			<input type="hidden" id="id" value="<?php echo $_POST['id_booking']; ?>">
			<input type="hidden" id="schedule_id" value="<?php echo isset($data['booking']->schedule_id) ? $data['booking']->schedule_id : ''; ?>">
			<input type="hidden" id="uploaded_pdf_name" name="uploaded_pdf_name" value="<?= isset($data['booking']->file_other) ? $data['booking']->file_other : ''; ?>">
			</div>
			<div id="Form-BC-Depan"></div>
			<div id="Form-BC-Depan-Print"></div>
			<div style="clear: both; height: 20px;"></div>

<div id="toggle_preview_wrapper" style="display: none; margin-bottom: 10px; text-align: center;">
    <button type="button" id="btn_toggle_preview" class="btn" style="background-color: #17a2b8; padding: 8px 20px; font-weight: bold; font-size: 14px;">
        👀 Buka / Tutup PDF Preview
    </button>
</div>

<div id="pdf_preview_container" style="display: none; width: 100%; border: 2px solid #007bff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 8px rgba(0,0,0,0.1); background: #fff; margin-bottom: 30px;">
    <div style="background: #007bff; color: white; padding: 10px 15px; font-weight: bold; display: flex; justify-content: space-between; align-items: center;">
        <span>📄 Preview Dokumen SI</span>
    </div>
    <iframe id="pdf_viewer" style="width: 100%; height: 800px; border: none;"></iframe>
</div>

<script type='text/javascript'>

function setbg(color) {
		document.getElementById("textareaShipper").style.backgroundColor = color;
}

var bookingStatus =
    "<?php echo isset($data['booking']->status)
        ? $data['booking']->status
        : ''; ?>";


function applyBookedReadonly()
{
    if (bookingStatus !== 'booked') {
        return;
    }

    /*
     * Disable semua field booking,
     * KECUALI field yang ada di Search Schedule.
     */
    $('input, select, textarea')
        .not('#searchForm input, #searchForm select, #searchForm textarea')
        .prop('disabled', true);


    /*
     * Search Schedule tetap aktif
     */
    $('#searchForm input, #searchForm select, #searchForm textarea')
        .prop('disabled', false);


    /*
     * PEB / NPE tetap bisa diisi saat BOOKED
     * (dokumen pabean umumnya terbit setelah booking dikonfirmasi).
     * Hanya input/select di dalam widget (.customs-doc); tombolnya memang tidak ikut dinonaktifkan.
     */
    $('.customs-doc input, .customs-doc select')
        .prop('disabled', false);


    /*
     * Tombol search tetap tampil & aktif
     */
    $('#searchForm input[type="submit"]')
        .show()
        .prop('disabled', false);


    /*
     * Label tetap SAVE (fungsinya: simpan PEB/NPE + kirim ulang WMS)
     */
    $('#btn_edit_booking')
        .prop('disabled', false)
        .val('SAVE');


    /*
     * Print tetap aktif
     */
    $('#btn_print_bc')
        .prop('disabled', false);


    $('#btn_toggle_preview')
        .prop('disabled', false);
}

$(document).ready(function() {

    /*
     * jalankan setelah handler lainnya selesai
     * agar dropdown VIA/HUB tidak terbuka lagi.
     */
    setTimeout(applyBookedReadonly, 0);
    setTimeout(applyBookedReadonly, 150);
});

function formatDateToDatetimeLocal(dateStr) {
  if (!dateStr || dateStr === "0000-00-00") return '';
  const date = new Date(dateStr);
  if (isNaN(date)) return '';
  return date.toISOString().slice(0, 16);
}

function updateLabels() {
    let polText = $("#dropdownPortLoading").val();
    let podText = $("#dropdownPortdestination").val();

    if (polText) {
        $("#label_etd").text("ETD " + polText);
    } else {
        $("#label_etd").text("ETD");
    }

    if (podText) {
        $("#label_eta").text("ETA " + podText);
    } else {
        $("#label_eta").text("ETA");
    }
}

$(function() {
    // 1. Setup Form Schedule Search Toolbars
    $('#region_id').select2({ placeholder: 'Select POL', allowClear: true });
    $('#city_identifier').select2({ placeholder: 'Select POD', allowClear: true });
    $('#etd_jkt').select2({ placeholder: 'Select ETD', allowClear: true });

    $.get("<?= base_url('afg_c_dropdown/get_region_options') ?>", function(data) {
        const regions = JSON.parse(data);
        regions.forEach(function(item) {
            $('#region_id').append(`<option value="${item}">${item}</option>`);
        });
    });

    $('#region_id').on('change', function() {
        let region = $(this).val();
        $.get("<?= base_url('afg_c_dropdown/get_city_options') ?>", { region_id: region }, function(data) {
            const cities = JSON.parse(data);
            $('#city_identifier').empty(); 
            Object.values(cities).forEach(function(item) {
                $('#city_identifier').append(`<option value="${item}">${item}</option>`);
            });
        });
    });

    function loadEtdOptions() {
        let region = $('#region_id').val();
        let city = $('#city_identifier').val();
        if (region && city) {
            $.get("<?= base_url('afg_c_dropdown/get_etd_options') ?>", { region_id: region, city_identifier: city }, function(data) {
                let options = JSON.parse(data);
                $('#etd_jkt').empty().append(`<option value="">Select ETD JKT</option>`);
                options.forEach(function(item) {
                    $('#etd_jkt').append(`<option value="${item}">${item}</option>`);
                });
            });
        }
    }

    $('#region_id, #city_identifier').on('change', loadEtdOptions);

    $('#searchForm').on('submit', function(e) {
        e.preventDefault();
        const formData = $(this).serialize();

        $.ajax({
          url: '<?= base_url('afg_c_dropdown/search_json') ?>',
          type: 'POST',
          data: formData,
          dataType: 'json',
          success: function(response) {
            if (response.length > 1) {
              window.scheduleResponse = response;
              let htmlChoices = response.map((data, i) => {
                const voyage = data.voy_vessel ?? data.voy ?? '';
                const eta = data.eta ?? '';
                const vessel = data.vessel ?? '';
                const trans = data.connecting ? ` → ${data.connecting}` : '';
                return `
                  <button class="swal2-styled swal-custom" onclick="chooseSchedule(${i})">
                    ${vessel} ${voyage}${trans}<br>
                    <small>ETA: ${eta}</small>
                  </button>`;
              }).join('');

              Swal.fire({
                title: 'Pilih Schedule',
                html: `<div style="display: flex; flex-wrap: wrap; gap: 10px;">${htmlChoices}</div>`,
                showConfirmButton: false,
                width: 700
              });
            } else if (response.length === 1) {
              fillForm(response[0]);
            } else {
              Swal.fire('Tidak ada data ditemukan');
            }
          }
        });
    });

    // 2. Select2 For Ports (If un-disabled dynamically later)
    $('#dropdownPortdestination').on('select2:select', function (e) {
        var selectedItem = e.params.data;
        if (selectedItem && selectedItem.code) {
            $('#code_pod').val(selectedItem.code);
        } else {
            $('#code_pod').val('');
        }
    });

    $('#dropdownPortdestination').on('select2:unselect', function (e) {
        $('#code_pod').val('');
    });
	
	$('#dropdownPortLoading, #dropdownPortTrans, #dropdownPortdestination').on('change', function () {
		var portName = $(this).val();
        var targetCode = '';
        
        if (this.id === 'dropdownPortLoading') targetCode = '#code_pol';
        if (this.id === 'dropdownPortTrans') targetCode = '#code_pot';
        if (this.id === 'dropdownPortdestination') targetCode = '#code_pod';

		$.ajax({
			url: '<?= base_url('afg_c_dropdown/get_port_code') ?>',
			method: 'POST',
			data: { name: portName },
			dataType: 'json',
			success: function(response) {
				if (response.code) {
					$(targetCode).val(response.code);
				} else {
					$(targetCode).val('');
				}
			}
		});
	});
	
    $('#dropdownPortLoading, #dropdownPortdestination').on('change', function() {
        updateLabels();
    });
	
	// --- CEK STATUS SAAT HALAMAN EDIT DIMUAT ---
    var currentRouteType = $('#route_type').val();
    var currentPod = $('#dropdownPortdestination').val();
    var currentPot = $('#dropdownPortTrans').val();
    var safePod = (currentPod || '').toUpperCase();
    var safePot = (currentPot || '').toUpperCase();

    // Pastikan terkunci di awal
    $('#dropdownPortdestination').prop('disabled', true);
    $('#dropdownPortTrans').prop('disabled', true);

    // 1. Jika rute VIA, form Port Transhipment wajib dibuka
    if (currentRouteType === "VIA") {
        $('#dropdownPortTrans').prop('disabled', false);
    }

    // 2. Baik VIA maupun DIRECT, jika POD adalah HUB, buka formnya
    if (["SINGAPORE", "HONG KONG", "BUSAN (EX PUSAN)","JEBEL ALI", "KOBE", "TOKYO","JAWAHARLAL NEHRU (NHAVA SHEVA)", "COLOMBO","CHENNAI (EX MADRAS)"].includes(safePod)) {
        $('#dropdownPortdestination').prop('disabled', false);
        $('#dropdownPortTrans').prop('disabled', false);
    }else if (["SINGAPORE", "HONG KONG", "BUSAN (EX PUSAN)","JEBEL ALI", "KOBE", "TOKYO","JAWAHARLAL NEHRU (NHAVA SHEVA)", "COLOMBO","CHENNAI (EX MADRAS)"].includes(safePot)) {
        $('#dropdownPortdestination').prop('disabled', false);
        $('#dropdownPortTrans').prop('disabled', false);
    }
    // -------------------------------------------

    // 3. Panggil pengecekan dokumen otomatis
    checkExistingPDF();
    updateLabels();

    function checkExistingPDF() {
        var existingPdf = $('#uploaded_pdf_name').val();
        if (existingPdf && existingPdf.trim() !== "") {
            $('#btn_close_preview').on('click', function() {
                $('#pdf_preview_container').slideUp(); 
            });
            $('#btn_toggle_preview').on('click', function() {
                $('#pdf_preview_container').slideToggle();
            });
            
            var pdfUrl = "<?= base_url('application/third_party/file/si_documents/') ?>" + existingPdf;
            $('#pdf_viewer').attr('src', pdfUrl);
            $('#toggle_preview_wrapper').show();
            $('#pdf_preview_container').show();
        }
    }
});

function chooseSchedule(index) {
    const data = window.scheduleResponse[index];
    Swal.close();
    fillForm(data);
}

function fillForm(data) {
    const cityId = data.eta_city_id ?? data.city_id;
    const safeCityId = (cityId || '').toUpperCase(); // Amankan dari huruf kecil/besar
    let transId = '';
    
    // 1. Kunci kembali secara default setiap kali user pilih schedule baru
    $('#dropdownPortdestination').prop('disabled', true);
    $('#dropdownPortTrans').prop('disabled', true);

    // 2. Jika rutenya VIA, buka Port Transhipment dan set transId-nya
    if (data.route_type === "VIA") {
        transId = data.etd_city_con_id ?? '';
        $('#dropdownPortTrans').prop('disabled', false);
    }

    // 3. Baik rutenya VIA maupun DIRECT, jika POD adalah HUB, buka formnya
    if (["SINGAPORE", "HONG KONG", "BUSAN (EX PUSAN)","JEBEL ALI", "KOBE", "TOKYO","JAWAHARLAL NEHRU (NHAVA SHEVA)", "COLOMBO","CHENNAI (EX MADRAS)"].includes(safeCityId)) {
        $('#dropdownPortdestination').prop('disabled', false);
        $('#dropdownPortTrans').prop('disabled', false);
        
        // Jika statusnya bukan VIA (Direct), jadikan city_id sebagai transId defaultnya
        if (data.route_type !== "VIA") {
            transId = data.city_id;
        }
    }

    const conVesVoy = (data.connecting ?? '') + ' ' + (data.voy_con ?? '');
    const voyage = data.voy_vessel ?? data.voy ?? '';
    const closingDateFormatted = formatDateToDatetimeLocal(data.stf_cls);
    const stuffingDateFormatted = formatDateToDatetimeLocal(data.stuffing_date);
    const portLoadingMap = {
      'Jakarta': 'TANJUNG PRIOK',
      'Bandung': 'TANJUNG PRIOK',
      'Surabaya': 'TANJUNG PERAK',
      'Semarang': 'TANJUNG EMAS',
      'Medan': 'BELAWAN'
    };
    const region = data.region_id ?? '';
    const portLoadingValue = portLoadingMap[region] ?? '';

    $('#txtETDJKT').val(data.etd_jkt ?? '');
    $('#txtETA').val(data.eta ?? '');
    $('#txtClosingDoc').val(closingDateFormatted);
    $('#txtClosingCar').val(stuffingDateFormatted);
    $('#route_type, #route_type_text').val(data.route_type ?? '');
    $('#vessel').val(`${data.vessel ?? ''} ${voyage}`);
    $('#voyage').val(conVesVoy);
    $('#schedule_id').val(data.id ?? '');
    $('#warehouse').val(data.stuffing_location ?? '');

    if (portLoadingValue) {
      $('#dropdownPortLoading').empty().append(new Option(portLoadingValue, portLoadingValue, true, true)).trigger('change');
    }
    $('#dropdownPortTrans').empty().append(new Option(transId, transId, true, true)).trigger('change');
    $('#dropdownPortdestination').empty().append(new Option(cityId, cityId, true, true)).trigger('change');
      
    updateLabels();
}

function get_data_form()
{
	$.ajax
		({
			type:"POST",
			url:"<?php echo site_url('pricelist/shipment_booking/get_data_form'); ?>",
			data:
			{
				shipper_name : $('#shipper').val(),
			},
			success:function(response)
			{			
				var data = $.parseJSON(response);
				$('#shipper').val(data.nama);
				$('#textareaShipper').val(data.alamat);
				$('#shipper_id').val(data.id);
				$('#contactshipper').val(data.cp_name);
				$('#contactemail').val(data.cp_email);
				$('#sales').val(data.sales);
			}
	})
}

function get_sales_name() {
	$.ajax({
		type:"POST",
		url:"<?php echo site_url('pricelist/shipment_booking/get_sales_name'); ?>",
		data: { sales_id : $('#sales').val() },
		success:function(response) {			
			var data = $.parseJSON(response);
			$('#sales_name').val(data.nama_sales);
		}
	});
}

function get_data_consignee() {
	$.ajax({
		type:"POST",
		url:"<?php echo site_url('pricelist/afg_c_booking/get_data_consignee'); ?>",
		data: { consignee_notify_name : $('#consignee').val() },
		success:function(response) {			
			var data = $.parseJSON(response);
			$('#textareaConsignee').val(data.consignee);
			$('#consignee_id').val(data.id);
		}
	});
}

function get_data_notify() {
	$.ajax({
		type:"POST",
		url:"<?php echo site_url('pricelist/afg_c_booking/get_data_notify'); ?>",
		data: { consignee_notify_name : $('#notify').val() },
		success:function(response) {			
			var data = $.parseJSON(response);
			$('#textareaNotify').val(data.notify);
			$('#notify_id').val(data.id);
		}
	});
}

function validateEmails() {
    let emailInput = document.getElementById("contactemail");
    let emailValue = emailInput.value.trim();
    let errorDiv = document.getElementById("emailError");

    if (!emailValue) {
        errorDiv.innerText = "";
        return true;
    }

    let emailArray = emailValue.split(",").map(email => email.trim());
    let emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
    let invalidEmails = emailArray.filter(email => email && !emailPattern.test(email));

    if (invalidEmails.length > 0) {
        errorDiv.innerText = "Invalid emails: " + invalidEmails.join(", ");
        return false;
    } else {
        errorDiv.innerText = "";
        return true;
    }
}

/* =====================================================================
 * PEB / NPE
 * Dikelola widget (views/pricelist/partials/peb_widget.php): form inline
 * AJU/PEB/NPE/Jalur + tombol Upload PDF. Data disimpan ke tabel PEB lewat
 * controller pricelist/peb_document (1 booking boleh banyak PEB), BUKAN lewat
 * save_edit_booking(). Isian form yang belum tersimpan otomatis disimpan
 * (PebWidget.flushAll) sebelum SAVE / kirim ulang WMS / cetak BC; bila isian
 * tidak valid, proses dihentikan dengan pesan. Saat ticket WMS dikirim,
 * server mengambil data PEB langsung dari database.
 * Setiap PEB/NPE di-upload / diubah / dihapus, widget otomatis memanggil
 * api/ticket_warehouse/sync_peb sehingga gudang (WMS) langsung diperbarui
 * (status tampil di bawah kotak PEB, dengan tombol "Kirim ulang" bila gagal).
 * ===================================================================== */

function withPebFlush(next, skipWmsSync)
{
    if (!window.PebWidget || !PebWidget.flushAll) {
        next();
        return;
    }
    // skipWmsSync = true bila halaman sendiri langsung mengirim ticket ke WMS setelah ini (SAVE / kirim ulang).
    PebWidget.flushAll({ sync: !skipWmsSync }).then(function () {
        next();
    }, function (msg) {
        Swal.fire({ icon: 'error', title: 'PEB / NPE belum bisa disimpan', text: msg });
    });
}

function save_edit_booking()
{
    // Simpan dulu isian PEB/NPE di form (bila ada), baru lanjut.
    if (!window.__pebFlushing) {
        withPebFlush(function () {
            window.__pebFlushing = true;
            try { save_edit_booking(); } finally { window.__pebFlushing = false; }
        }, true);
        return false;
    }

    // BOOKED tidak boleh mengubah data lokal.
    // Tombol tetap berlabel SAVE, fungsinya: kirim ulang ke WMS
    // (PEB/NPE sudah tersimpan lewat widget, dikirim sebagai bagian ticket).
    if (String(bookingStatus).toLowerCase() === 'booked') {
        resend_booked_to_wms();
        return false;
    }

    if (
        !validateVolume() ||
        !validateEmails() ||
        !validateField('#textfieldSI', 'Please enter Nomor SI.') ||
        !validateField('#shipper', 'Please enter Shipper name.') ||
        !validateSelect2Required(
            '#dropdownPortdestination',
            'Please select a valid Destination port.'
        ) ||
        !validateField('#txtETDJKT', 'Please enter ETD Jakarta.') ||
        !validateField('#textQty', 'Please enter Quantity.') ||
        !validateField('#textPackaging', 'Please enter Packaging.') ||
        !validateField('#textmeas', 'Please enter Volume.') ||
        !validateField('#warehouse', 'Form warehouse tidak boleh kosong.') ||
        !validateField('#wh_arrival', 'Estimasi Waktu Tiba di Gudang wajib diisi (tanggal & jam).')
    ) {
        return false;
    }

    var myData = {
        id: $('#id').val(),
        booking_code: $('#booking_number').val(),
        si_number: $('#textfieldSI').val(),
        mode: $('#TypeTransaction').val(),
        movement_type: $('#textMovementtype').val(),
        incoterm: $('#textfieldIncoterm').val(),
        origin_city: $('#dropdownPortLoading').val(),
        destination_city: $('#dropdownPortdestination').val(),
        trans_city: $('#dropdownPortTrans').val(),
        pol_code: $('#code_pol').val(),
        pot_code: $('#code_pot').val(),
        pod_code: $('#code_pod').val(),
        sales: $('#sales').val(),
        shipper_id: $('#shipper_id').val(),
        shipper_name: $('#shipper').val(),
        shipper_address: $('#textareaShipper').val(),
        consignee_name: $('#consignee').val(),
        consignee_id: $('#consignee_id').val(),
        consignee: $('#textareaConsignee').val(),
        notify_name: $('#notify').val(),
        notify_id: $('#notify_id').val(),
        notify: $('#textareaNotify').val(),
        trucking: $('#choice-trucking').val(),
        peb: $('#peb').val(),
        co: $('#co').val(),
        freight: $('#textfieldFreight').val(),
        number_of_bl: $('#BLtype').val(),
        expect_etd: $('#txtETDJKT').val(),
        eta: $('#txtETA').val(),
        closing_doc: $('#txtClosingDoc').val(),
        closing_car: $('#txtClosingCar').val(),
        quantity: $('#textQty').val(),
        package_type: $('#textPackaging').val(),
        volume: $('#textmeas').val(),
        weight: $('#textweight').val(),
        netto: $('#textnetto').val(),
        shipping_mark: $('#textareaMarking').val(),
        description_print: $('#textareaDog').val(),
        long_mark: $('#textareamarkingNos').val(),
        long_description: $('#textareadesc').val(),
        pickup_date: $('#pickup_date').val(),
        dimention: $('#dimensi').val(),
        pickup: $('#latlngPickup').val(),
        pickup_address: $('#pickupAddress').val(),
        drop: $('#latlngDrop').val(),
        drop_address: $('#dropAddress').val(),
        remark: $('#textareaRemark').val(),
        vessel: $('#vessel').val(),
        voyage: $('#voyage').val(),
        warehouse: $('#warehouse').val(),
        cp_name: $('#contactshipper').val(),
        cp_email: $('#contactemail').val(),
        do_number: $('#do_number').val(),
        wh_arrival: $('#wh_arrival').val(),
        schedule_id: $('#schedule_id').val(),
        route_type: $('#route_type').val(),
        cargo_type: $('#cargo_type').val(),
        dg_class: $('#dg_class').val(),
        hs_code: $('#hs_code').val()
    };

    var chk = false;
    $("input[class='checkbox_bill']").each(function() {
        var val = $(this).attr('checked');
        chk = (val === 'checked');
        myData[$(this).attr('id')] = chk;
    });

    var warehouse = String($('#warehouse').val() || '');
    var syncWms = (
        warehouse.indexOf('GCL') !== -1 ||
        warehouse === 'SARIADI SUB'
    );

    $('#btn_edit_booking')
        .prop('disabled', true)
        .val('SAVING...');

    $.ajax({
        type: 'POST',
        url: "<?php echo site_url('pricelist/shipment_booking/edit_booking')?>",
        data: myData,

        success: function(response) {
            var data;

            try {
                data = (typeof response === 'string')
                    ? $.parseJSON(response)
                    : response;
            } catch (e) {
                console.error('Invalid edit_booking response:', response);

                $('#btn_edit_booking')
                    .prop('disabled', false)
                    .val('SAVE');

                Swal.fire(
                    'Error',
                    'Response edit booking tidak valid.',
                    'error'
                );
                return;
            }

            if (!data || !(parseInt(data.status, 10) > 0)) {
                $('#btn_edit_booking')
                    .prop('disabled', false)
                    .val('SAVE');

                Swal.fire({
                    icon: 'error',
                    title: 'Gagal menyimpan perubahan',
                    text: (data && data.message)
                        ? data.message
                        : 'Edit booking gagal disimpan.'
                });
                return;
            }

            var bookingCode = data.booking_code || myData.booking_code;

            if (bookingCode) {
                $('#booking_number').val(bookingCode);
            }

            // Preserve existing business rule: only these warehouse values
            // are synchronized on a normal (non-booked) edit.
            if (syncWms) {
                $('#btn_edit_booking')
                    .prop('disabled', true)
                    .val('SENDING TO WMS...');

                booking_warehouse(bookingCode)
                    .done(function(wmsResponse) {

                        if (!isWmsSuccess(wmsResponse)) {
                            console.error('WMS edit rejected:', wmsResponse);

                            $('#btn_edit_booking')
                                .prop('disabled', false)
                                .val('SAVE');

                            Swal.fire({
                                icon: 'error',
                                title: 'Edit tersimpan, WMS gagal',
                                text: wmsResponse.message ||
                                    'Perubahan lokal tersimpan tetapi Warehouse belum menerima update.'
                            });
                            return;
                        }

                        finishEditBookingSuccess();
                    })
                    .fail(function(xhr) {
                        console.error('WMS edit failed:', xhr);

                        $('#btn_edit_booking')
                            .prop('disabled', false)
                            .val('SAVE');

                        var message = 'Perubahan lokal tersimpan tetapi Warehouse gagal menerima update.';

                        if (
                            xhr &&
                            xhr.responseJSON &&
                            xhr.responseJSON.message
                        ) {
                            message = xhr.responseJSON.message;
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'Edit tersimpan, WMS gagal',
                            text: message
                        });
                    });

                return;
            }

            finishEditBookingSuccess();
        },

        error: function(xhr) {
            console.error('edit_booking AJAX failed:', xhr);

            $('#btn_edit_booking')
                .prop('disabled', false)
                .val('SAVE');

            Swal.fire({
                icon: 'error',
                title: 'Terjadi kesalahan sistem',
                text: 'Gagal menghubungi server saat mengedit booking.'
            });
        }
    });

    return false;
}

function finishEditBookingSuccess()
{
    Swal.fire({
        icon: 'success',
        title: 'Your work has been saved',
        showConfirmButton: false,
        timer: 1200
    });

    $('#FORM_DISPLAY_BOOKING').dialog('close');
    $('#tabel_shipment_request')
        .jqGrid('setGridParam', { datatype: 'json' })
        .trigger('reloadGrid');
}

function resend_booked_to_wms()
{
    if (!window.__pebFlushing) {
        withPebFlush(function () {
            window.__pebFlushing = true;
            try { resend_booked_to_wms(); } finally { window.__pebFlushing = false; }
        }, true);
        return false;
    }

    var bookingCode = $('#booking_number').val();

    if (!bookingCode) {
        Swal.fire(
            'Error',
            'Booking Code kosong.',
            'error'
        );
        return false;
    }

    // PEB/NPE sudah tersimpan langsung lewat widget; server mengambilnya
    // dari database saat ticket dikirim, jadi cukup kirim ulang ticket.
    sendBookedTicketToWms(bookingCode);

    return false;
}

function sendBookedTicketToWms(bookingCode)
{
    $('#btn_edit_booking')
        .prop('disabled', true)
        .val('SENDING...');

    booking_warehouse(bookingCode)
        .done(function(response) {

            if (!isWmsSuccess(response)) {
                console.error('WMS resend rejected:', response);

                Swal.fire({
                    icon: 'error',
                    title: 'WMS gagal',
                    text: response.message ||
                        'WMS tidak mengonfirmasi keberhasilan.'
                });
                return;
            }

            Swal.fire({
                icon: 'success',
                title: 'Berhasil dikirim ke WMS',
                html:
                    'Booking <b>' + bookingCode +
                    '</b> berhasil dikirim ulang.'
            });
        })
        .fail(function(xhr) {
            console.error('WMS resend failed:', xhr);

            var message = 'Warehouse gagal menerima data.';

            if (
                xhr &&
                xhr.responseJSON &&
                xhr.responseJSON.message
            ) {
                message = xhr.responseJSON.message;
            }

            Swal.fire({
                icon: 'error',
                title: 'Gagal kirim ke WMS',
                text: message
            });
        })
        .always(function() {
            $('#btn_edit_booking')
                .prop('disabled', false)
                .val('SAVE');
        });
}

function isEmpty(value) {
    return !value || value.trim() === "" || value === "null" || value === "undefined";
}

function validateField(id, message) {
    const val = $(id).val();
    if (isEmpty(val)) {
        $(id).focus();
        Swal.fire("Oh noes!", message, "error");
        return false;
    }
    return true;
}

function validateSelect2Required(id, message) {
    const val = $(id).val();
    if (isEmpty(val)) {
        $(id).select2('open');
        Swal.fire("Oh noes!", message, "error");
        return false;
    }
    return true;
}

function validateVolume() {
    let value = $("#textmeas").val();
    let regex = /^(?:\d{1,2}|\d{1,2}\.\d{1,4})$/;
    if (!regex.test(value) || parseFloat(value) > 50) {
        Swal.fire("Invalid input! Volume terlalu besar untuk LCL.", "error");
        return false;
    }
    return true;
}

$('#choice-trucking, #peb, #co').on('change', function(){
   this.value = this.checked ? 1 : 0;
}).change();

var FormStuff = {
  init: function() {
    this.applyConditionalRequired();
    this.bindUIActions();
  },
  bindUIActions: function() {
    $("input[type='radio'], input[type='checkbox']").on("change", this.applyConditionalRequired);
  },
  applyConditionalRequired: function() {
    $(".require-if-active").each(function() {
      var el = $(this);
      if ($(el.data("require-pair")).is(":checked")) {
        el.prop("required", true);
      } else {
        el.prop("required", false);
      }
    });
  }
};
FormStuff.init();

$(document).ready(function() {
    $('#shipper').select2({
        placeholder: "Ketik nama shipper...",
        minimumInputLength: 1,
        allowClear: true,
        ajax: {
            url: "<?php echo site_url('afg_c_dropdown/dropdown_shipper_with_id')?>",
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return { term: params.term };
            },
            processResults: function (data) {
                return { results: data };
            },
            cache: true
        },
        templateResult: formatRepo,
        templateSelection: formatRepoSelection 
    });

    function formatRepo (repo) {
        if (repo.loading) return repo.text;
        var $container = $(
            "<div class='select2-result-repository clearfix'>" +
                "<div class='select2-result-repository__title' style='font-weight:bold;'></div>" +
                "<div class='select2-result-repository__id' style='font-size:11px; color:#888;'></div>" +
            "</div>"
        );
        $container.find(".select2-result-repository__title").text(repo.text);
        $container.find(".select2-result-repository__id").text("ID: " + repo.id);
        return $container;
    }

    function formatRepoSelection (repo) { return repo.text || repo.id; }

    $('#shipper').on('select2:select', function (e) {
        var data = e.params.data;
        $('#idshipper').val(data.real_id); 
        if (typeof get_data_form === "function") { get_data_form(); }
    });
});
		
$(function(){
		var data = "<?php echo site_url('afg_c_dropdown/dropdown_consignee_with_id')?>";
		$("#notify").autocomplete({
			source: data,
			select: function(event, ui){
			event.preventDefault();
            $("#notify").val(ui.item.label);
            $('#idnotify').val(ui.item.value); 
			onchange = get_data_notify();
			}
		});
});
	
var defaultValues = {
    dropdownPortloading: { id: "<?php echo $data['booking']->origin_city;?>", text: "<?php echo $data['booking']->origin_city;?>" },
    dropdownTranshipmentPort: { id: '<?php echo $data['booking']->trans_city;?>', text: '<?php echo $data['booking']->trans_city;?>' },
    dropdownCountries: { id: '<?php echo $data['booking']->destination_city;?>', text: '<?php echo $data['booking']->destination_city;?>' }
};

$(function() {
    function formatDropdown(item) {
        if (!item.id) { return item.text; }
        return $(
            '<div class="custom-select2-item">' + 
                '<span class="item-name">' + item.text + '</span><br>' + 
                '<span class="item-code">' + item.code + '</span>' + 
            '</div>'
        );
    }
    function formatSelection(item) { return item.text; }

    var sharedAjaxConfig = {
        url: '/afg_c_stuffing_plan/fetch_dropdown_data',
        dataType: 'json',
        delay: 250,
        data: function (params) { return { q: params.term }; },
        processResults: function (data) { return { results: data }; }
    };

    $('#dropdownPortdestination, #dropdownPortLoading, #dropdownPortTrans').select2({
        placeholder: '',
        width: '228px',
        ajax: sharedAjaxConfig,
        templateResult: formatDropdown,
        templateSelection: formatSelection
    });
});

$(function(){
		var data = "<?php echo site_url('afg_c_dropdown/dropdown_warehouse_with_code')?>";
		$("#warehouse").autocomplete({
			source: data,
			select: function(event, ui){
			    event.preventDefault();
                $("#warehouse").val(ui.item.label);
                $('#warehousecode').val(ui.item.value); 
			}
		});
});

function print_bc() {
	// Cetak membaca PEB/NPE dari database, jadi simpan dulu isian form yang belum tersimpan.
	if (!window.__pebFlushing) {
		withPebFlush(function () {
			window.__pebFlushing = true;
			try { print_bc(); } finally { window.__pebFlushing = false; }
		});
		return false;
	}
	var data = {};
		data.si = encodeURI($('#textfieldSI').val());
		data.booking_number = $('#booking_number').val();
		data.closing_date = $('#txtClosingDoc').val();
		data.stuffing_date = $('#txtClosingCar').val();
		data.type=$('#TypeTransaction').val();
		data.movement=$('#textMovementtype').val();
		data.incoterm=$('#textfieldIncoterm').val()
		data.pol=$('#dropdownPortLoading').val();
		data.pod=$('#dropdownPortdestination').val();
		data.trans=$('#dropdownPortTrans').val();
		data.ship_id=$('#shipper_id').val();
		data.shipper=$('#shipper').val();
		data.note=$('#textareaShipper').val();
		data.consignee=$('#consignee').val();
		data.con_id=$('#consignee_id').val();
		data.con_add=$('#textareaConsignee').val();
		data.not=$('#textareaNotify').val();
		data.truck=$('#choice-trucking').val();
		data.freight=$('#textfieldFreight').val();
		data.bl=$('#BLtype').val();
		data.etd=$('#txtETDJKT').val();
		data.eta=$('#txtETA').val();
		data.qty=$('#textQty').val();
		data.pack=$('#textPackaging').val();
		data.meas=$('#textmeas').val();
		data.weight=$('#textweight').val();
		data.netto=$('#textnetto').val();
		data.mark=$('#textareaMarking').val();
		data.dog=$('#textareaDog').val();
		data.long_description=$('#textareadesc').val();
		data.long_mark=$('#textareamarkingNos').val();
		data.remark=$('#textareaRemark').val();
		data.vessel=$('#vessel').val();
		data.voyage=$('#voyage').val();
		data.warehouse=$('#warehouse').val();
		data.house_bl=$('#hbl_draft').val();
		data.wh_arrival= $('#wh_arrival').val();
		data.cargo_type = $('#cargo_type').val();
		data.dg_class = $('#dg_class').val();
		data.hs_code = $('#hs_code').val();
		data.pod_code=$('#code_pod').val();
		data.pol_code=$('#code_pol').val();
		var url=serialize(data);

	$( "#Form-BC-Depan-Print" ).dialog({
			position:'top',
			autoOpen: true,
			resizable: false,
			height: 660,
			width: 980,
			modal: true,
			title: "Print Out Booking Confirmation",
			close: function() {	
				$(this).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
			}
		}).html('<object data="'+window.location.origin+'/pricelist/afg_c_booking/cetak_bc?'+url+'" width="960px" height="600px" type="application/pdf">');
}

$('input[type=text]').on('input', function () {
    var value = $(this).val().replace(/'/g, '').replace(/"/g, '');
});

function isWmsSuccess(data)
{
    if (data === true) {
        return true;
    }

    if (!data) {
        return false;
    }

    if (typeof data === 'object') {
        if (typeof data.status !== 'undefined') {
            return (
                data.status === true ||
                data.status === 1 ||
                data.status === '1' ||
                String(data.status).toLowerCase() === 'success'
            );
        }

        if (typeof data.success !== 'undefined') {
            return (
                data.success === true ||
                data.success === 1 ||
                data.success === '1'
            );
        }

        if (data.error) {
            return false;
        }

        // Compatibility with legacy endpoint responses that only return data.
        return true;
    }

    return false;
}


/**
 * Tampilkan di console browser (F12) JSON PERSIS yang dikirim ke gudang (WMS),
 * beserta method/URL dan jawaban gudang, supaya tim WMS bisa mencocokkan datanya.
 * Sumber: field "wms_request" pada respons api/ticket_warehouse/post_ticket.
 */
function logWmsPayload(res, label)
{
    try {
        if (!window.console || !res || !res.wms_request) { return; }
        var r = res.wms_request;
        console.log(
            '%c[WMS] ' + (label ? label + ' | ' : '') + r.method + ' ' + r.url +
            ' -> HTTP ' + (res.http_code || '-') + ' ' + (res.message || ''),
            'font-weight:bold;color:#0a6e3d'
        );
        console.log('[WMS] JSON yang dikirim ke gudang:\n' + JSON.stringify(r.payload, null, 2));
        console.log('[WMS] payload (objek, bisa dibuka/ditelusuri):', r.payload);
        if (typeof res.response !== 'undefined') { console.log('[WMS] jawaban gudang:', res.response); }
    } catch (e) { /* log tidak boleh mengganggu proses SAVE */ }
}

function booking_warehouse(bookingCode)
{
    bookingCode =
        bookingCode ||
        $('#booking_number').val();


    if (!bookingCode) {

        var deferred =
            $.Deferred();

        deferred.reject({
            responseJSON: {
                status: false,
                message: 'BOOKING_CODE_EMPTY'
            }
        });

        return deferred.promise();
    }


    var myData = {

        booking_code:
            bookingCode,

        si_number:
            $('#textfieldSI').val(),

        mode:
            $('#TypeTransaction').val(),

        movement_type:
            $('#textMovementtype').val(),

        incoterm:
            $('#textfieldIncoterm').val(),

        origin_city:
            $('#dropdownPortLoading').val(),

        destination_city:
            $('#dropdownPortdestination').val(),

        trans_city:
            $('#dropdownPortTrans').val(),

        shipper_id:
            $('#shipper_id').val(),

        sales:
            $('#sales').val(),

        shipper_name:
            $('#shipper').val(),

        shipper_address:
            $('#textareaShipper').val(),

        consignee_name:
            $('#consignee').val(),

        consignee_id:
            $('#consignee_id').val(),

        consignee:
            $('#textareaConsignee').val(),

        notify_name:
            $('#notify').val(),

        notify_id:
            $('#notify_id').val(),

        notify:
            $('#textareaNotify').val(),

        freight:
            $('#textfieldFreight').val(),

        number_of_bl:
            $('#BLtype').val(),

        expect_etd:
            $('#txtETDJKT').val(),

        eta:
            $('#txtETA').val(),

        closing_doc:
            $('#txtClosingDoc').val(),

        closing_car:
            $('#txtClosingCar').val(),

        quantity:
            $('#textQty').val(),

        package_type:
            $('#textPackaging').val(),

        volume:
            $('#textmeas').val(),

        weight:
            $('#textweight').val(),

        netto:
            $('#textnetto').val(),

        marking:
            $('#textareamarkingNos').val(),

        shipping_mark:
            $('#textareaMarking').val(),

        description_print:
            $('#textareaDog').val(),

        desc_of_goods:
            $('#textareadesc').val(),

        vessel:
            $('#vessel').val(),

        voyage:
            $('#voyage').val(),

        warehouse:
            $('#warehouse').val(),

        cp_name:
            $('#contactshipper').val(),

        cp_email:
            $('#contactemail').val(),

        do_number:
            $('#do_number').val(),

        wh_arrival:
            $('#wh_arrival').val(),

        schedule_id:
            $('#schedule_id').val(),

        route_type:
            $('#route_type').val(),

        cargo_type:
            $('#cargo_type').val(),

        dg_class:
            $('#dg_class').val(),

        hs_code:
            $('#hs_code').val()
    };


    var wmsReq = $.ajax({

        type: 'POST',

        url:
            "<?php echo site_url(
                'api/ticket_warehouse/post_ticket'
            )?>",

        data:
            myData,

        dataType:
            'json',

        timeout:
            35000
    });

    // Log JSON yang dikirim ke gudang (berhasil maupun gagal) ke console browser.
    wmsReq.done(function(res) {
        logWmsPayload(res, 'booking ' + bookingCode);
    }).fail(function(xhr) {
        logWmsPayload(xhr && xhr.responseJSON, 'booking ' + bookingCode + ' (GAGAL)');
    });

    return wmsReq;
}

function toggleDGClass(val) {
    if(val === 'DG') {
        document.getElementById('dg_class_wrapper').style.display = 'block';
    } else {
        document.getElementById('dg_class_wrapper').style.display = 'none';
        document.getElementById('dg_class').value = '';
    }
}
</script>