<!DOCTYPE html>
<html>
<head>
	<title>Search Schedule</title>
    <style>
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

    .btn:hover {
        background-color: #0056b3;
    }
      .search-form {
			background-color: #ffffff;
			padding: 20px;
			margin-bottom: 30px;
			border-radius: 8px;
			box-shadow: 0px 2px 8px rgba(0,0,0,0.1);
			width: 100%;
			max-width: 600px;
			margin-left: auto;
			margin-right: auto;
		}

		.search-form h2 {
			margin-bottom: 20px;
			font-size: 22px;
			text-align: center;
			color: #333;
		}

		.search-form .form-group {
			margin-bottom: 15px;
		}

		.search-form label {
			font-weight: bold;
			display: block;
			margin-bottom: 5px;
		}

		.search-form input[type="text"],
		.search-form input[type="submit"] {
			width: 100%;
			padding: 8px;
			font-size: 14px;
			border-radius: 4px;
			border: 1px solid #ccc;
		}

		.search-form input[type="submit"] {
			background-color: #007bff;
			color: white;
			cursor: pointer;
		}

		.search-form input[type="submit"]:hover {
			background-color: #0056b3;
		}

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
        }

        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }

        th {
            background-color: #f8f8f8;
        }

        .no-results {
            text-align: center;
            padding: 20px;
            font-style: italic;
            color: #777;
        }
		
		#btn_nama_agen,#btnTracking,#btnPindah,#cetak_form_bl,#btn_edit_booking,#btn_print_bc,#btn_save_booking
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
		.mandatory {
			border: 2px solid blue !important;
			background-color: #ffecec;
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
		  height: 700px; /* Should be removed. Only for demonstration */
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
				
		textarea#textareaShipper {
			width: 400px;
			height: 120px;
			border: 3px solid #cccccc;
			padding: 5px;
			font-family: Tahoma, sans-serif;
			background-position: bottom right;
			background-repeat: no-repeat;
		}
		textarea#textareaConsignee {
			width: 400px;
			height: 120px;
			border: 3px solid #cccccc;
			padding: 5px;
			font-family: Tahoma, sans-serif;
			
			background-position: bottom right;
			background-repeat: no-repeat;
		}
		textarea#textareaNotify {
			width: 400px;
			height: 120px;
			border: 3px solid #cccccc;
			padding: 5px;
			font-family: Tahoma, sans-serif;
			background-position: bottom right;
			background-repeat: no-repeat;
		}
		textarea#textareaDog {
			width: 400px;
			height: 25px;
			border: 3px solid #cccccc;
			padding: 5px;
			font-family: Tahoma, sans-serif;
			background-position: bottom right;
			background-repeat: no-repeat;
		}
		textarea#textareaMarking {
			width: 400px;
			height: 25px;
			border: 3px solid #cccccc;
			padding: 5px;
			font-family: Tahoma, sans-serif;
			background-position: bottom right;
			background-repeat: no-repeat;
		}
		textarea#textareaRemark {
			width: 400px;
			height: 25px;
			border: 3px solid #cccccc;
			padding: 5px;
			font-family: Tahoma, sans-serif;
			background-position: bottom right;
			background-repeat: no-repeat;
		}
		input[type="text"] {
			height: 26px; /* match Select2 default height */
			padding: 6px 12px;
			font-size: 14px;
			line-height: 1.42857143;
			border: 1px solid #ccc;
			border-radius: 4px;
			box-sizing: border-box;
		}
		input[type="datetime-local"] {
			height: 26px;
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
		  background-image: url(bg.gif)
		  padding: 5px;
		}
		#TypeTransaction {
		  width : 400px;
		  height :25px;
		  border:  3px solid #cccccc;
		  background-image: url(bg.gif)
		  padding: 5px;
		}
		#textMovementtype {
		  width : 400px;
		  height :25px;
		  border:  3px solid #cccccc;
		  background-image: url(bg.gif)
		  padding: 5px;
		}
		#textMovementtype {
		  width : 400px;
		  height :25px;
		  border:  3px solid #cccccc;
		  background-image: url(bg.gif)
		  padding: 5px;
		}
		#dropdownPortdestination {
		  width : 400px;
		  height :25px;
		  border:  3px solid #cccccc;
		  background-image: url(bg.gif)
		  padding: 5px;
		}
		#dropdownPortLoading {
		  width : 400px;
		  height :25px;
		  border:  3px solid #cccccc;
		  background-image: url(bg.gif)
		  padding: 5px;
		}
		#txtETDJKT { 
		  width : 400px;
		  height :25px;
		}
		input#inputPickup,#inputDrop,#dimention{
			height: 25px;
			width: 400px;
		}
		#txtETA { 
		  width : 400px;
		  height :25px;
		}
		.reveal-if-active {
		  opacity: 0;
		  max-height: 0;
		  overflow: hidden;
		   width : 170px;
		}
		input[type="radio"]:checked ~ .reveal-if-active,
		input[type="checkbox"]:checked ~ .reveal-if-active {
		  opacity: 1;
		  max-height: 100px; /* little bit of a magic number :( */
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
		.modal{
			z-index: 20;   
		}
		.modal-backdrop{
			z-index: 10;        
		}
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
  
  /* Tampilan normal (saat tidak di-hover) */
	.custom-select2-item .item-code {
		font-size: 11px;
		color: #888; /* Warna abu-abu */
	}

	/* Tampilan saat di-hover / di-highlight oleh Select2 */
	.select2-results__option--highlighted .custom-select2-item .item-code {
		color: #ffffff; /* Berubah jadi putih agar kontras dengan background biru */
	}

</style>
</head>
<body>  
  <div style="display: flex; gap: 20px; align-items: stretch;">

  <!-- Search Toolbar -->
  <div class="toolbar-search"
       style="flex: 1; background-color: #e5f2ff; padding: 15px 20px; border-radius: 8px; border: 1px dashed #007bff; box-shadow: 0 2px 5px rgba(0,0,0,0.1); display: flex; flex-direction: column; justify-content: space-between;  margin-bottom: 10px;">
    <label for="searchForm" style="font-weight: bold; color: #0056b3; display: block; margin-bottom: 8px;">
      📅 Pilih Schedule:
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

      <input type="submit" value="Search" class="btn btn-primary"
             style="padding: 6px 15px; border-radius: 5px; background: #007bff; color: #fff; border: none; cursor: pointer;">
    </form>
  </div>

  <!-- PDF Upload Toolbar -->
  <div class="toolbar-search"
       style="flex: 1; background-color: #e5f2ff; padding: 15px 20px; border-radius: 8px; border: 1px dashed #007bff; box-shadow: 0 2px 5px rgba(0,0,0,0.1); display: flex; flex-direction: column; justify-content: space-between; margin-bottom: 10px;">
    <label for="pdf_upload" style="font-weight: bold; color: #0056b3; display: block; margin-bottom: 8px;">
      🤖 Auto-fill Form by AI (Upload SI atau PEB):
    </label>
    <div style="display: flex; align-items: center; gap: 10px;">
      <input type="file" id="pdf_upload" accept="application/pdf" class="form-field"
             style="flex: 0 0 300px; padding: 6px; border-radius: 5px; border: 1px solid #ccc; background: #fff;">
      <button type="button" id="btn_extract_pdf" class="btn btn-primary"
              style="padding: 6px 15px; border-radius: 5px; background: #007bff; color: #fff; border: none; cursor: pointer;">
        Extract Data
      </button>
      <span id="extract_status" style="font-size: 12px; font-style: italic; color: #333;"></span>
    </div>
  </div>

</div>


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
				<input type="hidden" name="TypeTransaction" id="TypeTransaction" value="LCL">
			</div>

			<div style="clear: both; height: 2px"></div>
				<label for="textMovementtype">Movement Type:</label><br>
				<div style="float: left; width: 120px;"> 
				<select name="textMovementtype" id="textMovementtype">
				<option value="CFS/CFS">CFS/CFS</option>
				<option value="CY/CY">CY/CY</option>
				<option value="CY/CFS">CY/CFS</option>
				<option value="CFS/CY">CFS/CY</option>
				<option value="Port to Port">Port to Port</option>
				<option value="Port to Door">Port to Door</option>
				<option value="Door to Door">Door to Door</option>
				<option value="Door to Port">Door to Port</option>
				</select>
				</div>
				<div style="clear: both; height: 2px"></div>
				<label for="textPlan">Stuffing Plan:</label><br>
				<div style="float: left; width: 120px;"> 
				<select name="textPlan" id="textPlan">
				<option value="Consol">Consol</option>
				<option value="Coload">Coload</option>
				<option value="FCL">FCL</option>
				<option value="Air">Air</option>
				</select>
		</div>
	
		<div id="additionalDropdownContainer" style="float: left; margin-left: 10px;">
		</div>
		<div style="clear: both; height: 2px"></div>
			<label for="booking_number">Booking Number:</label><br>
			<div style="clear: both; height: 5px"></div>
			<div style="float: left; width: 400px;">
				<?= form_input(array(
					'id' => 'booking_number',
					'style' => 'width: 100%',
					'value' => isset($booking_number) ? $booking_number : '',
					'readonly' => 'readonly'
				)) ?>
			</div>
		<div style="clear: both; height: 2px"></div>
			<label for="textfieldSI">SI Number From Shipper:</label><br>
			<div style="clear: both; height: 5px"></div>
			<div style="float: left; width: 400px;">
				<?= form_input(array(
						'id'=>'textfieldSI',
						'style'=>'width: 100%',
				)) ?>
			</div>
				<div style="clear: both; height: 5px"></div>
			<label for="shipper">Shipper Detail:</label><br>
			<div style="float: left; width: 400px;">
				<select id="shipper" name="shipper" class="form-control" style="width: 100%;">
					<option value="">-- Cari Nama Shipper --</option>
				</select>
				<input type="hidden" id="idshipper" name="idshipper">
			</div>
			<div style="clear: both; height: 2px"></div>
			<div style="float: left; width: 400px;">
			<textarea name="textareaShipper" id="textareaShipper" style="setbg('#e5fff3');" onblur="setbg('white');"></textarea>
			</div>
			<div style="clear: both; height: 10px"></div>
			<label for="contactshipper">Shipper Contact Person:</label><br>
			<div style="clear: both; height: 5px"></div>
			<div style="float: left; width: 400px;">
				<?= form_input(array(
						'id'=>'contactshipper',
						'style'=>'width: 100%',
				)) ?>
			</div>
			<div style="clear: both; height: 10px"></div>
			<label for="contactemail">Shipper Contact Email(s) (Pisah dengan koma jika lebih dari 1):</label><br>
			<div style="clear: both; height: 5px"></div>
			<div style="float: left; width: 400px;">
				<?= form_input(array(
						'id' => 'contactemail',
						'style' => 'width: 100%',
				)) ?>
			</div>
			<div id="emailError" style="margin-top: 5px;"></div> <!-- Error Message Here -->
				<div style="clear: both; height: 10px"></div>
			<label for="consignee">Consignee Detail:</label><br>
			<div style="float: left; width: 400px;">
			<input id="consignee" type="text" name="consignee" style="width:100%"/>
			</div>
			<div style="clear: both; height: 2px"></div>
			<div style="float: left; width: 400px;">
			<textarea name="textareaConsignee" id="textareaConsignee" style="setbg('#e5fff3');" onblur="setbg('white')"></textarea>
			</div>
			<div style="clear: both; height: 5px"></div>
</div>
<div class="column" style="background-color:#bbb;">
			<label for="notify">Notify Detail:</label><br>
			<div style="float: left; width: 400px;">
			<input id="notify" type="text" name="notify" style="width:100%"/>
			</div>
			<div style="clear: both; height: 2px"></div>
			<div style="float: left; width: 400px;">
			<textarea name="textareaNotify" id="textareaNotify"></textarea>
			</div>
			<div style="clear: both; height: 5px"></div>
			<label for="textareamarkingNos">Marking And No:</label><br>
			<div style="float: left; width: 400px;">
			<textarea name="textareamarkingNos" id="textareamarkingNos"; style="width:100%"></textarea>
			</div>
			<div style="clear: both; height: 5px"></div>
			<label for="textareadesc">Description of Goods:</label><br>
			<div style="float: left; width: 400px;">
			<textarea name="textareadesc" id="textareadesc" style="width:100%; height:120px;";></textarea>
			</div>
			<div style="clear: both; height: 5px"></div>
			<label for="textQty">Quantity:</label>
			<label for="textPackaging" style="margin-left:50px;">Packaging:</label> <br>
			<div style="float: left;">
						<?= form_input(array(
						'id'=>'textQty',
						'name'=>'textQty',
						'style'=>'width: 25%',
					)) ?>
					
					<?= form_input(array(
						'id'=>'textPackaging',
						'name'=>'textPackaging',
						'style'=>'width: 75%, height:25px',
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
					)) ?></div>
				<div class="rTableCell"><?= form_input(array(
						'id'=>'textnetto',
						'name'=>'textnetto',
						'type'=>'text',
						'style'=>'width: 100%',
					)) ?></div>
				<div class="rTableCell"><?= form_input(array(
						'id'=>'textmeas',
						'name'=>'textmeas',
						'type'=>'text',
						'style'=>'width: 100%',
					)) ?></div>
				</div>
				</div>
		
			<div style="clear: both; height: 5px"></div>
			<label for="textareaDog">Commodities:</label><br>
			<div style="float: left;">
			<?= form_textarea(array(
						'id'=>'textareaDog',
						'name'=>'textareaDog',
				)) ?>
			</div>
			<div style="clear: both; height: 5px"></div>
   			<label for="textareaMarking">Marking:</label><br>
   			<div style="float: left;">
					<?= form_textarea(array(
						'id'=>'textareaMarking',
						'name'=>'textareaMarking',
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
				<label for="route_type_text">Route Type</label>
				<input type="text" id="route_type_text" name="route_type_text" readonly style="width: 100%; background-color: #eee; border: 1px solid #ccc; padding: 6px;">
				<input type="hidden" id="route_type" name="route_type">
		</div>
			<div style="clear: both; height: 5px"></div>
				<div class="rTable" >
				<div class="rTableRow">
					<div class="rTableHeadET"><strong id="label_etd">ETD</strong></div>
					<div class="rTableHeadET"><strong id="label_eta">ETA</strong></div>
				</div>
				<div class="rTableRow">
				<div class="rTableET">
						<?= form_input(array(
						'id'=>'txtETDJKT',
						'name'=>'txtETDJKT',
						'type'=>'date',
						'style'=>'width: 100%; border-width:0px; border:none;',
					)) ?></div>
				<div class="rTableET">
						<?= form_input(array(
						'id'=>'txtETA',
						'name'=>'txtETA',
						'type'=>'date',
						'style'=>'width: 100%; border-width:0px; border:none;',
					)) ?></div>
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
					)) ?></div>
				<div class="rTableET">
						<?= form_input(array(
						'id'=>'txtClosingCar',
						'name'=>'txtClosingCar',
						'type'=>'datetime-local',
						'style'=>'width: 100%; border-width:0px; border:none;',
					)) ?></div>
				</div>
		</div>
		<div style="clear: both; height: 5px"></div>
		<label for="dropdownPortLoading">Port of Loading :</label>
		<label for="code_pod"  style="margin-left:220px;">Kode POL:</label> <br>
		<div style="display: flex; gap: 120px;">
				<div style="float: left; width: 220px;">
				<select id="dropdownPortLoading" type="text" name="dropdownPortLoading" style="width:100%" disabled/></select>
				</div>
				<div style="width: 100px; ">
				<input id="code_pol" type="text" name="code_pol" style="width:100%" disabled/>
			</div>
		</div>
		<div style="clear: both; height: 5px"></div>
		<label for="dropdownPortTrans">Port of Transhipment ( Non direct consol only) :</label>
		<label for="code_pod"  style="margin-left: 35px;">Kode POT:</label> <br>
		<div style="display: flex; gap: 120px;">
				<div style="float: left; width: 220px;">
				<select id="dropdownPortTrans" type="text" name="dropdownPortTrans" style="width:100%" disabled/></select>
				</div>
				<div style="width: 100px; ">
				<input id="code_pot" type="text" name="code_pot" style="width:100%" disabled/>
			</div>
		</div>
		<div style="clear: both; height: 5px"></div>
		<label for="dropdownPortdestination">Port of Destination:</label>
		<label for="code_pod"  style="margin-left: 220px;">Kode POD:</label> <br>
		<div style="display: flex; gap: 120px;">
				<div style="float: left; width: 220px;">
				<select id="dropdownPortdestination" type="text" name="dropdownPortdestination" style="width:100%" disabled/></select>
				</div>
				<div style="width: 100px; ">
				<input id="code_pod" type="text" name="code_pod" style="width:100%" disabled/>
			</div>
		</div>
			<div style="clear: both; height: 15px"></div>
		<label for="vessel">Vessel:</label>  
		<label for="voyage" style="margin-left: 160px;">Connecting Vessel:</label><br>

		<div style="display: flex; gap: 10px;">
			<div style="width: 190px;">
				<input id="vessel" type="text" name="vessel" style="width:100%" disabled/>
			</div>
			<div style="width: 190px;">
				<input id="voyage" type="text" name="voyage" style="width:100%"/>
			</div>
		</div>

		<div style="clear: both; height: 15px"></div>	
		<label for="warehouse">Warehouse:</label>  
		<label for="wh_arrival" style="margin-left: 125px;">Estimasi Waktu Tiba di Gudang: <span style="color:#d9534f;" title="Wajib diisi">*</span></label><br>
		<div style="display: flex; gap: 10px;">
			<div style="width: 190px;">
				<input id="warehouse" type="text" name="warehouse" style="width:100%"/>
			</div>
			<div style="width: 190px;">
				<input type="datetime-local" id="wh_arrival" name="wh_arrival" style="width:100%" required/>
			</div>
		</div>
		<div style="clear: both; height: 15px"></div>	
		<label for="do_number">No. FO booking truck (Mceasy):</label>  
		<label for="sales_name" style="margin-left: 10px;">Sales:</label><br>
		<div style="display: flex; gap: 10px;">
			<div style="width: 190px;">
				<input id="do_number" type="text" name="do_number" style="width:100%"/>
			</div>
			<div style="width: 160px;">
				<input id="sales_name" type="text" name="sales_name" style="width:100%"/>
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
					<option value="PREPAID">PREPAID</option>
					<option value="COLLECT">COLLECT</option>
				  </select>
				</div>
				<div class="rTableCell">
				  <select name="textfieldIncoterm" id="textfieldIncoterm" style="border:none;">
					<option value="EXWORK">EXW (Ex Works)</option>
					<option value="FCA">FCA (Free Carrier)</option>
					<option value="FOB">FOB (Free On Board)</option>
					<option value="CFR">CFR (Cost and Freight)</option>
					<option value="CIF">CIF (Cost, Insurance & Freight)</option>
					<option value="DAP">DAP (Delivered At Place)</option>
					<option value="DPU">DPU (Delivered At Place Unloaded)</option>
					<option value="DDP">DDP (Delivered Duty Paid)</option>
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

<div style="display: flex; align-items: flex-start; gap: 10px;">
  <div style="flex: 1;">
    <textarea name="textareaNote" id="textareaNote"
      style="width: 100%; height: 80px; border: 3px solid #f0ad4e; background-color: #fcf8e3; padding: 5px; font-family: Tahoma, sans-serif;"
      placeholder="Instruksi khusus dari shipper..."></textarea>
  </div>

  <div style="width: 120px; display: flex; align-items: center;">
    <?php echo form_submit(array(
      'id'=>'btn_save_booking',
      'name'=>'btn_save_booking',
      'onclick'=>'return save_booking();',
      'value'=>'SAVE',
      'style'=>'width: 100%; padding: 8px 12px; border-radius: 5px; background: #007bff; color: #fff; border: none; cursor: pointer;'
    )) ?>
  </div>
</div>

			</div>
			
			<div style="clear: both; height: 30px"></div>
			
			<input type="hidden" id="shipper_id">
			<input type="hidden" id="sales">
			<input type="hidden" id="consignee_id">
			<input type="hidden" id="notify_id">
			<input type="hidden" id="id">
			<input type="hidden" id="schedule_id">
			<input type="hidden" id="uploaded_pdf_name" name="uploaded_pdf_name">
			</div>
			<div id="Form-BC-Depan"></div>
			</div> 
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
</body>
</html>
<script type="text/javascript">
function formatDateToDatetimeLocal(dateStr) {
      if (!dateStr || dateStr === "0000-00-00") return '';
      const date = new Date(dateStr);
      if (isNaN(date)) return ''; 
      return date.toISOString().slice(0, 16);
  }

  // === LETAKKAN UPDATE LABELS DI SINI SECARA GLOBAL ===
  function updateLabels() {
      let polText = $('#dropdownPortLoading').val();
      let podText = $('#dropdownPortdestination').val();

      if (polText && polText !== '') {
          $("#label_etd").text("ETD " + polText);
      } else {
          $("#label_etd").text("ETD");
      }

      if (podText && podText !== '') {
          $("#label_eta").text("ETA " + podText);
      } else {
          $("#label_eta").text("ETA");
      }
  }

$(document).ready(function() {
    // Init Select2
    $('#region_id').select2({ placeholder: 'Select POL', allowClear: true });
    $('#city_identifier').select2({ placeholder: 'Select POD', allowClear: true });
    $('#etd_jkt').select2({ placeholder: 'Select ETD', allowClear: true });
		
    // Load region_id options
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
				$('#city_identifier').empty(); // clear dulu
				Object.values(cities).forEach(function(item) {
					$('#city_identifier').append(`<option value="${item}">${item}</option>`);
				});
			});
		});


    // Load etd_jkt based on region_id + city_identifier
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
	// PASANG SEKALI SAJA SAAT AWAL
		
	// Gantikan blok $('#dropdownPortdestination').on('change', ...) dengan ini:

$('#dropdownPortdestination').on('select2:select', function (e) {
    // e.params.data berisi seluruh object JSON dari item yang Anda klik,
    // yang direturn oleh /afg_c_stuffing_plan/fetch_dropdown_data
    var selectedItem = e.params.data;

    // Karena object item sudah memiliki properti 'code' (seperti di formatDropdown),
    // kita bisa langsung memasukkannya ke input #code_pod
    if (selectedItem && selectedItem.code) {
        $('#code_pod').val(selectedItem.code);
    } else {
        $('#code_pod').val('');
    }
});

// Opsional: Kosongkan #code_pod jika pilihan dihapus (jika Anda memakai fitur allowClear)
$('#dropdownPortdestination').on('select2:unselect', function (e) {
    $('#code_pod').val('');
});
	
	$('#dropdownPortLoading').on('change', function () {
		var portName = $(this).val();

		$.ajax({
			url: '<?= base_url('afg_c_dropdown/get_port_code') ?>',
			method: 'POST',
			data: { name: portName },
			dataType: 'json',
			success: function(response) {
				if (response.code) {
					$('#code_pol').val(response.code);
				} else {
					$('#code_pol').val('');
				}
			},
			error: function() {
				alert('Gagal mengambil data dari server.');
			}
		});
	});
	$('#dropdownPortTrans').on('change', function () {
		var portName = $(this).val();

		$.ajax({
			url: '<?= base_url('afg_c_dropdown/get_port_code') ?>',
			method: 'POST',
			data: { name: portName },
			dataType: 'json',
			success: function(response) {
				if (response.code) {
					$('#code_pot').val(response.code);
				} else {
					$('#code_pot').val('');
				}
			},
			error: function() {
				alert('Gagal mengambil data dari server.');
			}
		});
	});
	
	$('#dropdownPortdestination').on('change', function () {
		var portName = $(this).val();

		$.ajax({
			url: '<?= base_url('afg_c_dropdown/get_port_code') ?>',
			method: 'POST',
			data: { name: portName },
			dataType: 'json',
			success: function(response) {
				if (response.code) {
					$('#code_pod').val(response.code);
				} else {
					$('#code_pod').val('');
				}
			},
			error: function() {
				alert('Gagal mengambil data dari server.');
			}
		});
	});
	
	// Pastikan letaknya di dalam $(document).ready()
    $('#dropdownPortLoading, #dropdownPortdestination').on('change', function() {
        updateLabels();
    });

});


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
      $('#dropdownPortLoading').empty()
        .append(new Option(portLoadingValue, portLoadingValue, true, true)).trigger('change');
    }
    $('#dropdownPortTrans').empty()
      .append(new Option(transId, transId, true, true)).trigger('change');
    $('#dropdownPortdestination').empty()
      .append(new Option(cityId, cityId, true, true)).trigger('change');
	  
	  // === TAMBAHKAN PEMANGGILAN INI ===
    updateLabels();
  }

  function formatDateToDatetimeLocal(dateStr) {
  if (!dateStr || dateStr === "0000-00-00") return '';
  const date = new Date(dateStr);
  if (isNaN(date)) return ''; // cek apakah date valid
  return date.toISOString().slice(0, 16);
}





function setbg(color)
{
document.getElementById("textareaShipper").style.background=color
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
function get_sales_name()
{
	$.ajax
		({
			type:"POST",
			url:"<?php echo site_url('pricelist/shipment_booking/get_sales_name'); ?>",
			data:
			{
				sales_id : $('#sales').val(),
			},
			success:function(response)
			{			
				var data = $.parseJSON(response);
				$('#sales_name').val(data.nama_sales);
			}
	})
}
function get_data_consignee()
{
	$.ajax
		({
			type:"POST",
			url:"<?php echo site_url('pricelist/afg_c_booking/get_data_consignee'); ?>",
			data:
			{
				consignee_notify_name : $('#consignee').val(),
			},
			success:function(response)
			{			
				var data = $.parseJSON(response);
				$('#textareaConsignee').val(data.consignee);
				$('#consignee_id').val(data.id);
			}
	})
}
function get_data_notify()
{
	$.ajax
		({
			type:"POST",
			url:"<?php echo site_url('pricelist/afg_c_booking/get_data_notify'); ?>",
			data:
			{
				consignee_notify_name : $('#notify').val(),
			},
			success:function(response)
			{			
				var data = $.parseJSON(response);
				$('#textareaNotify').val(data.notify);
				$('#notify_id').val(data.id);
			}
	})
}

$('#choice-trucking').on('change', function(){
   this.value = this.checked ? 1 : 0;
   // alert(this.value);
}).change();
$('#peb').on('change', function(){
   this.value = this.checked ? 1 : 0;
   // alert(this.value);
}).change();
$('#co').on('change', function(){
   this.value = this.checked ? 1 : 0;
   // alert(this.value);
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

$(document).ready(function () {
    let mandatoryFields = [
        "#textfieldSI",
        "#shipper",
        "#contactemail",
        "#textQty",
        "#textPackaging",
        "#textmeas",
        "#txtETDJKT",
        "#dropdownPortdestination",
        "#warehouse"
    ];

    // Apply the mandatory class
    mandatoryFields.forEach(function (field) {
        $(field).addClass("mandatory");
    });
});
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
                return {
                    term: params.term // Mengirim teks pencarian ke controller
                };
            },
            processResults: function (data) {
                return {
                    results: data
                };
            },
            cache: true
        },
        // Mengatur tampilan di dalam daftar dropdown
        templateResult: formatRepo,
        // Mengatur tampilan setelah dipilih
        templateSelection: formatRepoSelection 
    });

    function formatRepo (repo) {
        if (repo.loading) {
            return repo.text;
        }

        // Kamu bisa kustomisasi HTML di sini
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

    function formatRepoSelection (repo) {
        return repo.text || repo.id;
    }

    // Event saat data dipilih
    $('#shipper').on('select2:select', function (e) {
        var data = e.params.data;
        $('#idshipper').val(data.real_id);
        
        // Memanggil fungsi onchange lama kamu
        if (typeof get_data_form === "function") { 
            get_data_form(); 
        }
    });
});

$(function(){
		var data = "<?php echo site_url('afg_c_dropdown/dropdown_consignee_with_id')?>";
		$("#consignee").autocomplete({
			source: data,
			select: function(event, ui){
			event.preventDefault();
            $("#consignee").val(ui.item.label);
            $('#idconsignee').val(ui.item.value); 
			var nama = ui.item.label;
			var id = ui.item.value;
			onchange = get_data_consignee();
			}
		});});
		
$(function(){
		var data = "<?php echo site_url('afg_c_dropdown/dropdown_consignee_with_id')?>";
		$("#notify").autocomplete({
			source: data,
			select: function(event, ui){
			event.preventDefault();
            $("#notify").val(ui.item.label);
            $('#idnotify').val(ui.item.value); 
			var nama = ui.item.label;
			var id = ui.item.value;
			onchange = get_data_notify();
			}
		});});

$(function() {
    
    // 1. Definisikan fungsi format tampilan
    function formatDropdown(item) {
        if (!item.id) { return item.text; }
        return $(
            '<div class="custom-select2-item">' + 
                '<span class="item-name">' + item.text + '</span><br>' + 
                '<span class="item-code">' + item.code + '</span>' + 
            '</div>'
        );
    }

    function formatSelection(item) {
        return item.text; 
    }

    // 2. Buat konfigurasi AJAX dasar yang dipakai bersama
    var sharedAjaxConfig = {
        url: '/afg_c_stuffing_plan/fetch_dropdown_data',
        dataType: 'json',
        delay: 250,
        data: function (params) {
            return { q: params.term }; // Parameter pencarian
        },
        processResults: function (data) {
            return { results: data }; // Format hasil dari PHP
        }
    };

    // 3. Terapkan ke masing-masing Dropdown dengan pengaturan spesifiknya

    // --- Dropdown 1: Port Destination ---
    $('#dropdownPortdestination').select2({
        placeholder: '',
        width: '228px',
        ajax: sharedAjaxConfig,
        templateResult: formatDropdown,
        templateSelection: formatSelection
    });

    // --- Dropdown 2: Port Loading ---
    $('#dropdownPortLoading').select2({
        placeholder: '',
        width: '228px',
        ajax: sharedAjaxConfig,
        templateResult: formatDropdown,
        templateSelection: formatSelection
    });

    // --- Dropdown 3: Port Trans ---
    $('#dropdownPortTrans').select2({
        placeholder: '',
        width: '228px',
        allowClear: true,           // Hanya ada di Port Trans
        minimumInputLength: 3,      // Hanya ada di Port Trans
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
			var nama = ui.item.label;
			var id = ui.item.value;
			}
		});});

function isWmsSuccess(data) {
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
                data.status === "1" ||
                String(data.status).toLowerCase() === "success"
            );
        }

        if (typeof data.success !== 'undefined') {
            return (
                data.success === true ||
                data.success === 1 ||
                data.success === "1"
            );
        }

        if (data.error) {
            return false;
        }

        // Compatibility jika API lama hanya return object data
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
            $('#hs_code').val(),

        // Pengawasan BC Hanggar: di form NEW belum ada input-nya,
        // jadi terkirim kosong. Jika nanti input ditambahkan
        // (mis. dari AI autofill PEB), otomatis ikut terkirim.
        aju_number:       $.trim($('#aju_number').val() || '').toUpperCase(),
        peb_number:       $.trim($('#peb_number').val() || '').toUpperCase(),
        peb_date:         $('#peb_date').val() || '',
        npe_number:       $.trim($('#npe_number').val() || '').toUpperCase(),
        npe_date:         $('#npe_date').val() || '',
        jalur:            $('#jalur').val() || '',
        jalur_date:       $('#jalur_date').val() || '',
        karantina_status: $('#karantina_status').val() || '',
        karantina_number: $.trim($('#karantina_number').val() || '').toUpperCase(),
        karantina_date:   $('#karantina_date').val() || ''
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

document.addEventListener("DOMContentLoaded", function() {
    let emailInput = document.getElementById("contactemail");

    emailInput.addEventListener("blur", function() {
        validateEmails();
    });
});

function validateEmails() {
    let emailInput = document.getElementById("contactemail");
    let emailValue = emailInput.value.trim();
    let errorDiv = document.getElementById("emailError");

    if (!emailValue) {
        errorDiv.innerText = ""; // Clear error message if empty
        return true;
    }

    let emailArray = emailValue.split(",").map(email => email.trim());
    let emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
    let invalidEmails = emailArray.filter(email => email && !emailPattern.test(email));

    if (invalidEmails.length > 0) {
        errorDiv.innerText = "Invalid emails: " + invalidEmails.join(", ");
        return false; // Return false to prevent submission
    } else {
        errorDiv.innerText = ""; // Clear error message if valid
        return true; // Return true to allow form submission
    }
}

function openCreatedBookingEdit(booking)
{
    if (!booking || !booking.booking_id || !booking.booking_code) {
        $('#btn_save_booking')
            .prop('disabled', true)
            .val('BOOKING CREATED');

        Swal.fire({
            icon: 'warning',
            title: 'Booking tersimpan',
            text: 'WMS berhasil, tetapi data untuk membuka mode Edit tidak lengkap. Silakan buka booking dari daftar. Jangan membuat BC baru dari form ini.'
        });
        return;
    }

    $('#btn_save_booking')
        .prop('disabled', true)
        .val('WMS SUCCESS');

    $('#btn_save_booking')
        .data('wms-retry', 0)
        .removeData('booking-code');

    window.savedBookingAfterCreate = null;

    $('#tabel_shipment_request')
        .jqGrid('setGridParam', { datatype: 'json' })
        .trigger('reloadGrid');

    $('#FORM_DISPLAY_BOOKING').html(
        '<div style="text-align:center; padding:50px;">' +
        '<b>LOADING EDIT MODE...</b>' +
        '</div>'
    );

    $.ajax({
        type: 'POST',
        url: "<?php echo site_url('pricelist/afg_c_booking/collect_data_booking')?>",
        data: {
            id_booking: booking.booking_id,
            booking_code: booking.booking_code
        },
        success: function(htmlResponse) {
            $('#FORM_DISPLAY_BOOKING').html(htmlResponse);
        },
        error: function(xhr) {
            console.error('Failed opening edit form:', xhr.responseText);

            $('#btn_save_booking')
                .prop('disabled', true)
                .val('BOOKING CREATED');

            Swal.fire({
                icon: 'warning',
                title: 'Booking & WMS berhasil',
                text: 'Mode Edit gagal dibuka. Silakan buka kembali booking dari daftar. Jangan klik SAVE/create ulang.'
            });
        }
    });
}

function setCreateWmsRetry(booking, response)
{
    window.savedBookingAfterCreate = booking;

    // Tombol TETAP "SAVE". Mode retry ditandai lewat data('wms-retry'),
    // sehingga klik SAVE berikutnya = UPDATE BC yang sama + kirim ulang WMS
    // (bukan create BC baru -> tidak ada BC dobel).
    $('#btn_save_booking')
        .prop('disabled', false)
        .val('SAVE')
        .data('wms-retry', 1)
        .data('booking-code', booking.booking_code);

    var message = 'Data belum berhasil masuk ke Warehouse.';

    if (response && response.message) {
        message = response.message;
    } else if (
        response &&
        response.responseJSON &&
        response.responseJSON.message
    ) {
        message = response.responseJSON.message;
    }

    Swal.fire({
        icon: 'error',
        title: 'BC tersimpan, tetapi WMS gagal',
        html:
            'Booking <b>' + booking.booking_code + '</b> sudah tersimpan di Gateway.<br><br>' +
            message + '<br><br>' +
            'Perbaiki data bila perlu, lalu klik <b>SAVE</b> lagi.<br>' +
            '<small>SAVE berikutnya akan memperbarui booking yang sama (tidak membuat BC baru) dan mengirim ulang ke WMS.</small>'
    });
}

function resaveCreatedBooking(myData)
{
    var bookingCode  = $('#btn_save_booking').data('booking-code');
    var retryBooking = window.savedBookingAfterCreate;

    if (!bookingCode) {
        Swal.fire('Error', 'Booking Code untuk save ulang tidak ditemukan. Buka booking dari daftar.', 'error');
        return false;
    }

    // Fallback bila object booking tidak tersedia tetapi code masih ada.
    if (!retryBooking) {
        retryBooking = { booking_id: $('#id').val(), booking_code: bookingCode };
    }

    // edit_booking memakai nama field yang sedikit berbeda dari save_booking
    var editData = $.extend({}, myData, {
        id:               bookingCode,
        booking_code:     bookingCode,
        long_mark:        myData.marking,
        long_description: myData.desc_of_goods
    });

    $('#booking_number').val(bookingCode);

    $('#btn_save_booking')
        .prop('disabled', true)
        .val('SAVING...');

    $.ajax({
        type: 'POST',
        url: "<?php echo site_url('pricelist/shipment_booking/edit_booking')?>",
        data: editData,

        success: function(response) {
            var data;
            try {
                data = (typeof response === 'string') ? $.parseJSON(response) : response;
            } catch (e) {
                data = null;
            }

            if (!data || !(parseInt(data.status, 10) > 0)) {
                // Tetap di mode save ulang, tombol tetap SAVE
                $('#btn_save_booking').prop('disabled', false).val('SAVE');
                Swal.fire({
                    icon: 'error',
                    title: 'Perubahan gagal disimpan',
                    text: (data && data.message) ? data.message : 'Update booking gagal. Silakan klik SAVE lagi.'
                });
                return;
            }

            $('#btn_save_booking')
                .prop('disabled', true)
                .val('SENDING TO WMS...');

            booking_warehouse(bookingCode)
                .done(function(wmsResponse) {
                    if (!isWmsSuccess(wmsResponse)) {
                        console.error('WMS resave rejected:', wmsResponse);
                        setCreateWmsRetry(retryBooking, wmsResponse);
                        return;
                    }

                    Swal.fire({
                        icon: 'success',
                        title: 'Booking berhasil',
                        text: 'Perubahan tersimpan dan berhasil dikirim ke WMS.',
                        showConfirmButton: false,
                        timer: 1400
                    });

                    setTimeout(function() {
                        openCreatedBookingEdit(retryBooking);
                    }, 900);
                })
                .fail(function(xhr) {
                    console.error('WMS resave failed:', xhr);
                    setCreateWmsRetry(retryBooking, xhr);
                });
        },

        error: function(xhr) {
            console.error('resave edit_booking failed:', xhr);
            // Update bersifat idempotent (booking yang sama), jadi aman untuk SAVE lagi.
            $('#btn_save_booking').prop('disabled', false).val('SAVE');
            Swal.fire({
                icon: 'error',
                title: 'Gagal menghubungi server',
                text: 'Perubahan belum tersimpan. Silakan klik SAVE lagi.'
            });
        }
    });

    return false;
}

function save_booking()
{
    // ============================================================
    // VALIDATION BEFORE LOCAL CREATE
    // ============================================================
    if (
        !validateVolume() ||
        !validateEmails() ||
        !validateField('#textfieldSI', 'Please enter Nomor SI.') ||
        !validateField('#shipper', 'Please enter Shipper name.') ||
        !validateSelect2Required('#dropdownPortdestination', 'Please select a valid Destination port.') ||
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
        shipper_id: $('#shipper_id').val(),
        sales: $('#sales').val(),
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
        marking: $('#textareamarkingNos').val(),
        shipping_mark: $('#textareaMarking').val(),
        netto: $('#textnetto').val(),
        description_print: $('#textareaDog').val(),
        desc_of_goods: $('#textareadesc').val(),
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
        no_urut: $('#id').val(),
        code_pod: $('#code_pod').val(),
        shipper_note: $('#textareaNote').val(),
        file_other: $('#uploaded_pdf_name').val(),
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

    // ============================================================
    // SAVE ULANG setelah WMS gagal:
    // BC lokal SUDAH dibuat -> update BC yang sama, lalu kirim ulang WMS.
    // ============================================================
    if ($('#btn_save_booking').data('wms-retry') == 1) {
        return resaveCreatedBooking(myData);
    }

    $('#btn_save_booking')
        .prop('disabled', true)
        .val('SAVING...');

    $.ajax({
        type: 'POST',
        url: "<?php echo site_url('pricelist/shipment_booking/save_booking')?>",
        data: myData,

        success: function(response) {
            var data;

            try {
                data = (typeof response === 'string')
                    ? $.parseJSON(response)
                    : response;
            } catch (e) {
                console.error('Invalid save_booking response:', response);

                $('#btn_save_booking')
                    .prop('disabled', false)
                    .val('SAVE');

                Swal.fire(
                    'Error',
                    'Response save booking tidak valid.',
                    'error'
                );
                return;
            }

            if (!data || !(parseInt(data.status, 10) > 0)) {
                $('#btn_save_booking')
                    .prop('disabled', false)
                    .val('SAVE');

                Swal.fire({
                    icon: 'error',
                    title: 'Gagal menyimpan BC',
                    text: (data && data.message)
                        ? data.message
                        : 'Booking gagal disimpan.'
                });
                return;
            }

            // booking_code dibuat backend. Jangan pernah post WMS sebelum code ini ada.
            if (!data.booking_code) {
                $('#btn_save_booking')
                    .prop('disabled', true)
                    .val('CHECK BOOKING');

                Swal.fire({
                    icon: 'error',
                    title: 'Booking Code kosong',
                    text: 'BC kemungkinan sudah dibuat tetapi server tidak mengembalikan booking_code. Jangan create ulang; cek booking dari daftar.'
                });
                return;
            }

            $('#booking_number').val(data.booking_code);
            window.savedBookingAfterCreate = data;

            $('#btn_save_booking')
                .prop('disabled', true)
                .val('SENDING TO WMS...');

            // WMS WAJIB untuk create. Tidak lagi bergantung nama warehouse.
            booking_warehouse(data.booking_code)
                .done(function(wmsResponse) {

                    if (!isWmsSuccess(wmsResponse)) {
                        console.error('WMS rejected:', wmsResponse);
                        setCreateWmsRetry(data, wmsResponse);
                        return;
                    }

                    Swal.fire({
                        icon: 'success',
                        title: 'Booking berhasil',
                        text: 'BC berhasil disimpan dan berhasil dikirim ke WMS.',
                        showConfirmButton: false,
                        timer: 1400
                    });

                    setTimeout(function() {
                        openCreatedBookingEdit(data);
                    }, 900);
                })
                .fail(function(xhr) {
                    console.error('WMS failed:', xhr);
                    setCreateWmsRetry(data, xhr);
                });
        },

        error: function(xhr) {
            console.error('save_booking AJAX failed:', xhr);

            // Status create menjadi ambigu: request mungkin sudah diproses server
            // walaupun response tidak sampai ke browser. Jangan izinkan create ulang
            // dari form yang sama karena berisiko menghasilkan BC duplikat.
            $('#btn_save_booking')
                .prop('disabled', true)
                .val('CHECK BOOKING');

            Swal.fire({
                icon: 'error',
                title: 'Status booking belum pasti',
                text: 'Koneksi ke server terputus saat proses save. Cek daftar booking terlebih dahulu sebelum mencoba create ulang.'
            });
        }
    });

    // Prevent native form submit/reload.
    return false;
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
    if (!regex.test(value) || parseFloat(value) > 60) {
        Swal.fire("Invalid input! Volume terlalu besar untuk LCL.", "error");
        return false;
    }
    return true;
}

document.getElementById('textPlan').addEventListener('change', function() {
    var selectedValue = this.value; // Get the selected value
    var container = document.getElementById('additionalDropdownContainer');

    // Clear the container first
    container.innerHTML = '';

    // If "Coload" is selected, add the new dropdown and label
    if (selectedValue === 'Coload') {
        // Create the label
        var label = document.createElement('label');
        label.htmlFor = 'additionalDropdown'; // Associate label with the dropdown
        label.textContent = 'Coloader:'; // Label text

        // Create the new dropdown
        var newDropdown = document.createElement('select');
        newDropdown.name = 'additionalDropdown';
        newDropdown.id = 'additionalDropdown';

        // Add options to the new dropdown
        var options = ['GATEWAY JAKARTA', 'GATEWAY BANDUNG', 'GATEWAY SURAYABA', 'GATEWAY SEMARANG','GATEWAY BELAWAN','OSLINE JAKARTA', 'BPLINES', 'HNS', 'XUNLAITONG', 'OTHER COLOADER'];
        options.forEach(function(option) {
            var opt = document.createElement('option');
            opt.value = option;
            opt.text = option;
            newDropdown.appendChild(opt);
        });

        // Append the label and dropdown to the container
        container.appendChild(label);
        container.appendChild(newDropdown);
   }
});

$(document).ready(function() {

    // 1. Event listener untuk tombol tutup (di dalam header preview)
    $('#btn_close_preview').on('click', function() {
        $('#pdf_preview_container').slideUp(); 
        // Note: src iframe TIDAK dihapus, agar file tetap ada di memori browser
    });

    // 2. Event listener untuk tombol Buka/Tutup utama
    $('#btn_toggle_preview').on('click', function() {
        $('#pdf_preview_container').slideToggle();
    });

    // 3. Event listener untuk tombol extract
    // 3. Event listener untuk tombol extract
    $('#btn_extract_pdf').on('click', function() {
        var fileInput = document.getElementById('pdf_upload');
        
        if (fileInput.files.length === 0) {
            Swal.fire("Warning", "Silakan pilih file PDF Shipping Instruction terlebih dahulu!", "warning");
            return;
        }

        var file = fileInput.files[0];
        
        // Load PDF ke Iframe (Buka preview di awal agar user bisa lihat dokumennya saat loading)
        var fileURL = URL.createObjectURL(file);
        $('#pdf_viewer').attr('src', fileURL);
        $('#toggle_preview_wrapper').show();
        $('#pdf_preview_container').slideDown();

        var formData = new FormData();
        formData.append('file', file);

        // Update UI status text
        $('#extract_status').text('⏳ Mengekstrak & menyimpan data...');
        $('#btn_extract_pdf').prop('disabled', true).text('Processing...');

        // === TAMBAHAN BARU: Kunci Layar dengan SweetAlert Loading ===
        Swal.fire({
            title: 'Mengekstrak Data...',
            html: 'Mohon tunggu, AI sedang membaca dokumen Anda.',
            allowOutsideClick: false, // Mencegah user klik area luar
            allowEscapeKey: false,    // Mencegah user tekan tombol ESC
            didOpen: () => {
                Swal.showLoading();   // Memunculkan animasi spinner
            }
        });

        // AJAX KE JARVIS (EKSTRAKSI)
        $.ajax({
            url: 'https://api-gw.jar-vis.com/jarvis-py/shipping-instruction/extract',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                $('#extract_status').text('✅ Ekstraksi berhasil!');
                $('#btn_extract_pdf').prop('disabled', false).text('Extract Data');

                var data = typeof response === "string" ? JSON.parse(response) : response;

                if (data && data.status === true) {
                    fillFormFromJarvis(data); // Di dalam fungsi ini sudah ada Swal.fire('success') yang otomatis menutup loading di atas

                    // === PINDAHAN KODE: Scroll Layar DILAKUKAN SETELAH BERHASIL ===
                   /*  $('html, body').animate({
                        scrollTop: $("#toggle_preview_wrapper").offset().top - 50
                    }, 500); */

                } else {
                    Swal.fire("Error", "Gagal mengekstrak data. Pastikan format PDF sesuai.", "error");
                }
            },
            error: function() {
                $('#extract_status').text('❌ Ekstraksi gagal!');
                $('#btn_extract_pdf').prop('disabled', false).text('Extract Data');
                Swal.fire("Error", "Terjadi kesalahan saat menghubungi server Jarvis.", "error");
            }
        });

        // AJAX KE BACKEND UNTUK SAVE FILE LOKAL
        // AJAX KE BACKEND UNTUK SAVE FILE LOKAL
        $.ajax({
            url: "<?= site_url('pricelist/shipment_booking/upload_si_document') ?>", 
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                var res = typeof response === "string" ? JSON.parse(response) : response;
                if(res.status === true) {
                    // Simpan nama file baru ke input hidden
                    $('#uploaded_pdf_name').val(res.file_name);
                    console.log("PDF diamankan dengan nama: " + res.file_name);
                } else {
                    console.error("Gagal simpan file: " + res.error);
                }
            },
            error: function() {
                console.error("Gagal menghubungi server upload.");
            }
        });
    });
   // Fungsi mapping dari JSON Jarvis ke Element Form
function fillFormFromJarvis(data) {
    var booking = data.booking || {};
    var detail = data.detail || {};

    // Helper untuk uppercase
    function toUpper(val) {
        return val ? val.toString().toUpperCase() : '';
    }

    // Mapping Data Header/Booking
     $('#textfieldSI').val(booking.si_number || booking.booking_code);
    if (booking.mode) $('#TypeTransaction').val(toUpper(booking.mode)).trigger('change');
    if (booking.incoterm) $('#textfieldIncoterm').val(toUpper(booking.incoterm)).trigger('change');
    if (booking.number_of_bl) $('#BLtype').val(toUpper(booking.number_of_bl)).trigger('change');
    if (booking.freight) $('#textfieldFreight').val(toUpper(booking.freight)).trigger('change');

    // Mapping Data Consignee & Notify
    $('#consignee').val(toUpper(detail.consignee_name));
    $('#textareaConsignee').val(toUpper(detail.consignee));
    $('#notify').val(toUpper(detail.notify_name));
    $('#textareaNotify').val(toUpper(detail.notify));

    // Mapping Data Kuantitas & Dimensi
    $('#textQty').val(toUpper(detail.quantity));
    $('#textPackaging').val(toUpper(detail.package_type));
    $('#textweight').val(toUpper(detail.weight));
    $('#textnetto').val(toUpper(detail.netto));
    $('#textmeas').val(toUpper(detail.volume));

    // Mapping Data Barang & Markings
    $('#textareamarkingNos').val(toUpper(detail.marking));
    $('#textareaMarking').val(toUpper(detail.shipping_mark));
    $('#textareadesc').val(toUpper(detail.description_of_goods));
    $('#textareaDog').val(toUpper(detail.description_print));
    $('#textareaNote').val(toUpper(detail.note));

    // Jika ingin mapping shipper dengan Select2 AJAX (opsional)
    /*
    if (detail.shipper_name) {
        var newShipper = new Option(toUpper(detail.shipper_name), toUpper(detail.shipper_name), true, true);
        $('#shipper').append(newShipper).trigger('change');
    }
    $('#textareaShipper').val(toUpper(detail.shipper_address));
    $('#contactshipper').val(toUpper(detail.cp_name));
    $('#contactemail').val(toUpper(detail.cp_email));
    */

    // Mapping Port Loading & Destination (opsional)
    /*
    if (booking.origin_city) {
        var newPol = new Option(toUpper(booking.origin_city), toUpper(booking.origin_city), true, true);
        $('#dropdownPortLoading').append(newPol).trigger('change');
    }
    if (booking.destination_city) {
        var newPod = new Option(toUpper(booking.destination_city), toUpper(booking.destination_city), true, true);
        $('#dropdownPortdestination').append(newPod).trigger('change');
    }
    if (booking.pol_code) $('#code_pol').val(toUpper(booking.pol_code));
    if (booking.pod_code) $('#code_pod').val(toUpper(booking.pod_code));
    */

    // Notifikasi sukses
    Swal.fire({
        icon: 'success',
        title: 'Auto-fill Selesai!',
        text: 'Data dari dokumen berhasil dimasukkan ke dalam form).',
        timer: 2500,
        showConfirmButton: false
    });
}
});

function toggleDGClass(val) {
    if(val === 'DG') {
        document.getElementById('dg_class_wrapper').style.display = 'block';
    } else {
        document.getElementById('dg_class_wrapper').style.display = 'none';
        document.getElementById('dg_class').value = '';
    }
}
</script>
