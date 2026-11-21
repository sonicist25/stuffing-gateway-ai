<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Edit Stuffing Plan</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <style>
    /* RESET & DASAR */
    :root {
      --bg-light: #f8fafc;
      --card-bg: rgba(255, 255, 255, 0.85);
      --glass-border: rgba(255, 255, 255, 0.4);
      --border-light: #e2e8f0;
      --text-main: #0f172a;
      --text-muted: #64748b;
      --input-bg: rgba(255, 255, 255, 0.9);
      --warning-accent: #f59e0b;
      --warning-hover: #d97706;
      --danger: #ef4444;
      --success-bg: #dcfce7;
      --success-text: #166534;
      --danger-bg: #fee2e2;
      --danger-text: #991b1b;
      --shadow-sm: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
      --shadow-lg: 0 20px 40px -10px rgba(15, 23, 42, 0.1);
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
      background-color: var(--bg-light);
      background-image: radial-gradient(at 0% 0%, hsla(38, 100%, 74%, 0.15) 0px, transparent 50%),
                        radial-gradient(at 100% 100%, hsla(210, 100%, 80%, 0.15) 0px, transparent 50%);
      background-attachment: fixed;
      color: var(--text-main);
      min-height: 100vh;
      padding: 20px;
    }
	
	.modal-container {
	  animation: fadeInUp 0.4s ease-out;
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      background: var(--card-bg) !important;
      border: 1px solid var(--glass-border) !important;
      box-shadow: var(--shadow-lg) !important;
      max-width: 1180px !important;
      margin: 20px auto !important;
      padding: 30px !important;
      border-radius: 16px !important;
	}

	@keyframes fadeInUp {
	  from { opacity: 0; transform: translateY(15px); }
	  to { opacity: 1; transform: translateY(0); }
	}
	
    .modal-title {
      font-size: 24px !important;
      font-weight: 800 !important;
      margin-bottom: 25px !important;
      color: var(--text-main) !important;
      letter-spacing: 0.3px;
      border-bottom: 1px solid var(--border-light);
      padding-bottom: 15px;
    }

    .form-group-row {
      display: grid !important;
      grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
      gap: 16px !important;
      margin-bottom: 16px !important;
    }

    .form-group {
      min-width: 0 !important;
      max-width: none !important;
      width: 100% !important;
      display: flex;
      flex-direction: column;
    }

    .form-group label {
      font-size: 11px !important;
      font-weight: 700 !important;
      color: var(--text-muted) !important;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 8px;
    }

    .form-group input,
    .form-group select {
      background: var(--input-bg) !important;
      color: var(--text-main) !important;
      border: 1px solid var(--border-light) !important;
      padding: 8px 12px !important;
      font-size: 13px !important;
      border-radius: 8px !important;
      min-height: 38px !important;
      height: 38px !important;
      transition: all 0.2s ease;
      width: 100%;
      box-shadow: inset 0 1px 2px rgba(0,0,0,0.02);
    }

    .form-group input:focus,
    .form-group select:focus {
      border-color: var(--warning-accent) !important;
      box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.15) !important;
      outline: none;
    }

    .form-group input:disabled,
    .form-group select:disabled {
        background: #f1f5f9 !important;
        color: #94a3b8 !important;
        cursor: not-allowed;
        box-shadow: none;
    }

    .btn-submit, .btn-primary {
      background: linear-gradient(135deg, var(--warning-accent), var(--warning-hover)) !important;
      color: white !important;
      padding: 12px 28px !important;
      font-size: 14px !important;
      font-weight: 700 !important;
      border: none !important;
      border-radius: 8px !important;
      cursor: pointer;
      transition: all 0.2s ease !important;
      box-shadow: 0 4px 12px rgba(217, 119, 6, 0.25);
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .btn-submit:hover, .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(217, 119, 6, 0.35);
    }

    hr {
        border: 0;
        height: 1px;
        background: var(--border-light);
        margin: 25px 0 !important;
    }

    /* Validator */
    .validator {
        font-size: 11px;
        color: var(--danger);
        margin-top: 5px;
        font-weight: 600;
    }

    /* Mode edit */
    .modal-container.edit-mode {
      position: relative;
      border-top: 4px solid var(--warning-accent) !important;
    }

    .modal-container.edit-mode::before {
      content: "EDIT MODE";
      position: absolute;
      top: -16px;
      left: 24px;
      background: linear-gradient(135deg, var(--warning-accent), var(--warning-hover));
      color: #fff;
      font-size: 11px;
      font-weight: 800;
      padding: 6px 14px;
      border-radius: 6px;
      box-shadow: 0 4px 10px rgba(217, 119, 6, 0.25);
      letter-spacing: 0.5px;
    }

    /* Select2 Light Mode Override */
    .select2-container--default .select2-selection--single {
        background-color: var(--input-bg) !important;
        border: 1px solid var(--border-light) !important;
        height: 38px !important;
        min-height: 38px !important;
        border-radius: 8px !important;
        display: flex;
        align-items: center;
        box-shadow: inset 0 1px 2px rgba(0,0,0,0.02);
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: var(--text-main) !important;
        line-height: normal !important;
        padding-left: 12px !important;
        font-size: 13px !important;
        font-weight: 500;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px !important;
        top: 1px !important;
        right: 8px !important;
    }
    
    .select2-container--default .select2-selection--single .select2-selection__arrow b {
        border-color: var(--text-muted) transparent transparent transparent !important;
    }

    .select2-dropdown {
        background-color: #ffffff !important;
        border: 1px solid var(--border-light) !important;
        box-shadow: var(--shadow-lg);
        border-radius: 8px;
        overflow: hidden;
    }

    .select2-search--dropdown .select2-search__field {
        background-color: var(--bg-light) !important;
        border: 1px solid var(--border-light) !important;
        color: var(--text-main) !important;
        border-radius: 6px;
        padding: 6px 12px;
    }

    .select2-container--default .select2-results__option {
        color: var(--text-main);
        padding: 8px 12px;
        font-size: 13px;
    }

    .select2-container--default .select2-results__option[aria-selected=true] {
        background-color: #f8fafc !important;
    }

    .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: var(--warning-accent) !important;
        color: #ffffff !important;
    }

    .select2-container--default.select2-container--focus .select2-selection--single,
    .select2-container--default.select2-container--open .select2-selection--single {
        border-color: var(--warning-accent) !important;
        box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.15) !important;
    }

    .custom-select2-item .item-code {
        font-size: 11px;
        color: var(--text-muted);
        margin-top: 2px;
        display: inline-block;
    }
    .select2-results__option--highlighted .custom-select2-item .item-code {
        color: #fffbeb;
    }

    .select2-container {
        width: 100% !important;
    }

    /* Badges */
    .dg-pill {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      margin-top: 8px;
      padding: 6px 12px;
      border-radius: 6px;
      font-size: 11px;
      font-weight: 800;
      background: var(--success-bg);
      color: var(--success-text);
      border: 1px solid rgba(22, 101, 52, 0.2);
    }
    .dg-pill.is-dg { 
        background: var(--danger-bg); 
        color: var(--danger-text); 
        border: 1px solid rgba(153, 27, 27, 0.2);
    }

    /* FIX: Schedule Section Toolbar Width */
    #scheduleSelectionSection {
      background: #fffbeb; 
      border: 1px solid #fde68a;
      border-radius: 12px;
      padding: 24px;
      margin: 24px 0;
      box-shadow: inset 0 2px 4px rgba(245, 158, 11, 0.05);
    }

    #scheduleSelectionSection h2 {
      font-size: 16px !important;
      margin: 0 0 16px !important;
      font-weight: 800 !important;
      color: #92400e !important;
    }

    .toolbar-search .search-form {
      display: grid !important;
      grid-template-columns: 1fr 1fr 1fr auto !important;
      gap: 16px !important;
      width: 100% !important;
      align-items: end; /* Align items to the bottom so the button matches input fields */
    }

    /* Responsive */
    @media (max-width: 1100px) {
      .form-group-row { grid-template-columns: repeat(2, minmax(0, 1fr)) !important; }
      .toolbar-search .search-form { grid-template-columns: repeat(2, minmax(0, 1fr)) !important; }
    }
    @media (max-width: 700px) {
      .form-group-row, .toolbar-search .search-form { grid-template-columns: 1fr !important; }
    }
  </style>
</head>

<body>

<div class="modal-container edit-mode">
  <h2 class="modal-title">Edit Stuffing Plan</h2>

  <!-- ROW 1 -->
  <div class="form-group-row">
    <div class="form-group">
      <label for="dateInput">Tanggal Job</label>
      <input type="date" id="dateInput">
    </div>
    <div class="form-group">
        <label for="TypeOfTransaction">Type of Transaction</label>
        <select id="TypeOfTransaction" name="TypeOfTransaction" class="form-control" onchange="renderFormFields()">
            <option value=""></option>
            <option value="FCL">FCL EXPORT</option>
            <option value="LCL">LCL EXPORT</option>
            <option value="COLOAD">COLOAD EXPORT</option>
            <option value="AIR FREIGHT">AIR FREIGHT EXPORT</option>
            <option value="AIR FREIGHT IMPORT">AIR FREIGHT IMPORT</option>
            <option value="SF IMPORT FCL">FCL IMPORT</option>
            <option value="SF IMPORT LCL">LCL IMPORT</option>
            <option value="COLOAD IMPORT">COLOAD IMPORT</option>
            <option value="MESCELLENEOUS">MISCELLANEOUS</option>
            <option value="DOMESTICS">LCL DOMESTICS</option>
            <option value="FCL DOMESTICS">FCL DOMESTICS</option>
            <option value="OTHER DOMESTICS">OTHER DOMESTICS</option>
            <option value="CUSTOM">CUSTOM EXPORT</option>
            <option value="CUSTOM IMPORT">CUSTOM IMPORT</option>
            <option value="TRUCKING ONLY">TRUCKING ONLY</option>
            <option value="TRUCKING VENDOR">TRUCKING VENDOR</option>
            <option value="TRUCKING CUSTOMER">TRUCKING CUSTOMER</option>
            <option value="TRUCKING EXTERNAL">TRUCKING EXTERNAL</option>
            <option value="PROFIT SHARE PPJK">PROFIT SHARE PPJK</option>
            <option value="WAREHOUSE">WAREHOUSE</option>
        </select>
    </div>
  </div>
  
  <!-- Dynamic Fields Container -->
  <div id="formFields" class="DynamicForm" style="display: none;">

    <!-- ROW 3 -->
    <div class="form-group-row">
        <div class="form-group">
            <label for="liner_d">KODE PELAYARAN</label>
            <select id="liner_d" name="liner_d" class="form-control">
                <option value="">Select Region</option>
            </select>
        </div>

        <div class="form-group">
            <label for="scac_d">KODE SCAC</label>
            <input id="scac_d" name="scac_d" type="text" class="form-control" value="" disabled>
        </div>

        <div class="form-group">
            <label for="mbl_d">MBL PELAYARAN</label>
            <input id="mbl_d" name="mbl_d" type="text" class="form-control" value="">
            <span id="validator2" class="validator">Jika diisi MBL NVOCC/COLOADER, tracking harus diisi manual</span>
        </div>

        <div class="form-group">
            <label for="cargo_type_d">TIPE CARGO</label>
            <select id="cargo_type_d" name="cargo_type_d" class="form-control">
                <option value="GENERAL">GENCO</option>
                <option value="DG">DG</option>
            </select>
            <input type="hidden" id="is_dg_d" name="is_dg_d" value="0">
            <span id="dg_badge" class="dg-pill">GENCO</span>
        </div>
    </div>
    
    <div class="form-group-row">
        <div class="form-group">
            <label for="container_d">NOMOR CONTAINER</label>
            <input id="container_d" name="container_d" type="text" class="form-control" value="" onkeyup="checkValid(this)" maxlength="11"/>
            <span id="validator1" class="validator"></span>
        </div>

        <div class="form-group">
            <label for="seal_d">NOMOR SEAL</label>
            <input type="text" id="seal_d" value="">
        </div>

        <div class="form-group">
            <label for="jml_container_d">JUMLAH CONTAINER</label>
            <input type="number" id="jml_container_d" value="1" min="1" max="999">
        </div>

        <div class="form-group">
            <label for="type_container_d">TYPE CONTAINER</label>
            <select id="type_container_d" name="type_container_d" class="form-control">
                <option value=""></option>
            </select>
        </div>
    </div>
    
    <div class="form-group-row">
        <div class="form-group">
            <label for="kurs_d">KURS PAJAK</label>
            <input id="kurs_d" name="kurs_d" type="text" class="form-control" oninput="filterAngka(this)">
        </div>
        
        <div class="form-group">
            <label for="kurs_jualan">KURS SELLING</label>
            <input id="kurs_jualan" name="kurs_jualan" type="text" class="form-control" oninput="filterAngka(this)">
        </div>

        <div class="form-group">
            <label for="agent_d">KODE AGENT</label>
            <select id="agent_d" name="agent_d" class="form-control">
                <option value=""></option>
            </select>
            <span id="validator3" class="validator"></span>
        </div>
    </div>

    <div class="form-group-row">
        <div class="form-group" style="grid-column: 1 / -1;">
            <label for="dg_details_d">DG DETAILS / REMARKS</label>
            <input id="dg_details_d" name="dg_details_d" type="text" class="form-control" value="" placeholder="">
        </div>
    </div>

    <div id="scheduleSelectionSection">
        <h2>Pilih Schedule</h2>
        <!-- FIX: Dikeluarkan dari form-group-row agar membentang 100% sesuai grid form -->
        <div class="toolbar-search">
            <form method="post" id="searchForm" class="search-form">
                <div class="form-group">
                    <label for="region_id_d">Region</label>
                    <select id="region_id_d" name="region_id_d">
                        <option value="">Select Region</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="city_identifier">City ID</label>
                    <select id="city_identifier" name="city_identifier">
                        <option value="">Select City ID / ETA City ID</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="etd_jkt_d">ETD JKT</label>
                    <select id="etd_jkt_d" name="etd_jkt_d">
                        <option value="">Select ETD JKT</option>
                    </select>
                </div>
                <div class="form-group" style="justify-content: flex-end;">
                    <input type="submit" value="Search" class="btn btn-primary" style="height: 38px; width: 100%;">
                </div>
            </form>
        </div>
    </div>

    <!-- ROW 4 -->
    <div class="form-group-row">
        <div class="form-group">
            <label for="pol_job">ORIGIN</label>
            <input id="pol_job" type="text" disabled value="">
        </div>
        <div class="form-group">
            <label for="pot_job">TRANSHIPMENT PORT</label>
            <input id="pot_job" type="text" disabled value="">
        </div>
        <div class="form-group">
            <label for="pod_job">DESTINATION</label>
            <input id="pod_job" type="text" disabled value="">
        </div>
    </div>

    <!-- ROW 5 -->
    <div class="form-group-row">
        <div class="form-group">
            <label for="closing">WAKTU CLOSING</label>
            <input id="closing" type="datetime-local" disabled>
        </div>
        <div class="form-group">
            <label for="etd_pol">ETD</label>
            <input id="etd_pol" type="date" disabled>
        </div>
        <div class="form-group">
            <label for="eta_des">ETA</label>
            <input id="eta_des" type="date" disabled>
        </div>
    </div>
    
    <div class="form-group-row">
        <div class="form-group">
            <label for="stuffing_d">TANGGAL STUFFING</label>
            <input id="stuffing_d" type="date"/>
        </div>
        <div class="form-group">
            <label for="atd_pol">ATD</label>
            <input id="atd_pol" type="date"/>
        </div>
        <div class="form-group">
            <label for="ata_des">ATA</label>
            <input id="ata_des" type="date"/>
        </div>
    </div>

    <!-- ROW 6 -->
    <div class="form-group-row">
        <div class="form-group">
            <label for="warehouse_d">LOKASI STUFFING</label>
            <select id="warehouse_d" name="warehouse_d" class="form-control">
                <option value=""></option>
            </select>
        </div>
        <div class="form-group">
            <label for="vessel_d">VESSEL</label>
            <input id="vessel_d" type="text" disabled value="">
        </div>
        <div class="form-group">
            <label for="voyage_d">VOYAGE</label>
            <input id="voyage_d" type="text" disabled value="">
        </div>
    </div>

    <input type="hidden" id="schedule_id_data" name="schedule_id_data">
    <input type="hidden" id="route_d_data" name="route_d_data">
  </div>
  
  <!-- Submit Button -->
  <div style="text-align: right; margin-top: 30px;">
    <button type="button" id="btnSaveJob" class="btn-submit">Update Job</button>
  </div>
</div>

<script>
function formatDateToDatetimeLocal(dateStr) {
    if (!dateStr) return '';
    return `${dateStr}T00:00`;
}

function convertDate(jsonDate) {
    if (!jsonDate) return '';
    let parts = jsonDate.split("/"); 
    let formattedDate = `${parts[2]}-${parts[1]}-${parts[0]}`; 
    return formattedDate;
}

$(document).ready(function() {
    $('#cargo_type_d').select2({ placeholder: 'Cargo Type', allowClear: false });
    
    // Sinkronisasi badge DG
    $('#cargo_type_d').on('change', function () {
        const isDg = String($(this).val()).toUpperCase() === 'DG';
        $('#is_dg_d').val(isDg ? '1' : '0');
        $('#dg_badge').text(isDg ? 'DG' : 'GENCO').toggleClass('is-dg', isDg);
    });
	
	$.ajax({
        async: true,
        type: "POST",
        url: window.location.origin + '/afg_c_stuffing_plan/get_data_edit_stuffing_plan',
        data: {
            sp_id: $('#spId').val(),
            kode_ref: $("#txtRefno2").val(),
            mode: $('#dropdownTypeOfTransaction').val()
        },
        success: function(response) {
            var data = $.parseJSON(response);
			// Ambil data dari JSON
			var closingDate = data.closing_date_time; // "2025-12-31"
			var closingTime = data.closing_time;      // "00:00"

			// Gabungkan jadi format datetime-local
			var closingValue = closingDate + "T" + closingTime;

            $('#dateInput').val(convertDate(data.tanggal_job));
            $('#mbl_d').val(data.mother_bl);
            $('#stuffing_d').val(data.stuffing_date);
            // Set ke input
			$('#closing').val(closingValue);
            $('#etd_pol').val(data.etd_jkt);
            $('#eta_des').val(data.eta);
            $('#atd_pol').val(data.atd);
            $('#ata_des').val(data.ata);
            $('#container_d').val(data.container_no);
            $('#seal_d').val(data.seal_no);
            $('#jml_container_d').val(data.type_cont_qty);
            $('#kurs_d').val(data.kurs);
            $('#kurs_jualan').val(data.kurs_selling);
            $('#pol_job').val(data.port_loading);
            $('#pot_job').val(data.transhipment_port);
            $('#pod_job').val(data.destination);

            $('#TypeOfTransaction').val(data.type_of_trans).trigger('change'); 

            if (data.vessel && data.voyage) {
                $('#vessel_d').val(data.vessel);
                $('#voyage_d').val(data.voyage);
            }
			
			// ==========================================
			// TAMBAHKAN KODE INI DI BAWAHNYA
			// ==========================================
			// Pastikan hidden input terisi data lama agar tidak ter-overwrite jadi kosong
			$('#schedule_id_data').val(data.schedule_id || '');
			$('#route_d_data').val(data.schedule_type || ''); 
			// ==========================================
            
            // Set data DG/GENCO dari database jika tersedia
			let isDgVal = String(data.is_dg || '0');
			let cargoTypeVal = (isDgVal === '1' || String(data.cargo_type).toUpperCase() === 'DG') ? 'DG' : 'GENERAL';

			$('#cargo_type_d').val(cargoTypeVal).trigger('change');
			$('#is_dg_d').val(isDgVal);

			// --- MULAI LOGIKA PENGGABUNGAN DG DETAILS & INSTRUKSI STUFFING ---
			let dgDetailsAsli = data.dg_details || '';
			let instruksiGudang = data.instruksi_stuffing || '';

			let finalDgDetails = dgDetailsAsli;

			// Jika instruksi gudang ada isinya, gabungkan
			if (instruksiGudang.trim() !== '') {
				// Hilangkan enter (\n) agar rapi di dalam <input type="text">
				let cleanInstruksi = instruksiGudang.replace(/\r?\n|\r/g, ' / ');
				
				if (finalDgDetails.trim() !== '') {
					finalDgDetails += ' | INSTRUKSI: ' + cleanInstruksi;
				} else {
					finalDgDetails = 'INSTRUKSI: ' + cleanInstruksi;
				}
			}

			// Masukkan ke dalam input text
			$('#dg_details_d').val(finalDgDetails);
			// --- SELESAI ---
            
            var container1 = document.getElementById('container_d');
            var validator1 = document.getElementById('validator1');
            
            function getValidatorMessage(validator, containerNo){
                var isvalid     = validator.isValid(containerNo);
                var valMsgs     = validator.getErrorMessages();
                var valMsgsTxt  = valMsgs.join(', ');
                msg = isvalid;
                if (msg == false) {
                    msg+= ' : ' + valMsgsTxt + '.';
                    if(valMsgs.indexOf('The check digit does not match') != -1){
                        msg+= ' Check digit must be ' + validator.createCheckDigit(containerNo.substr(0,10)) + '.';
                    }
                }
                return msg;
            }
            
            function checkValid(txt) {
                if(txt.value != ''){                    
                    var validator = new ContainerValidator();
                    validator1.innerHTML = getValidatorMessage(validator, txt.value);
                }else{
                    validator1.innerHTML = 'Please enter Container Number';
                }
            }
            checkValid(container1);
			
            $.get("<?= base_url('afg_c_dropdown/dropdown_liner') ?>", function(dropdownData) {
                const carrierList = JSON.parse(dropdownData);
                var linerData = {}; 
                
                carrierList.forEach(function(item) {
                    $('#liner_d').append(`<option value="${item.code}">${item.code}</option>`);
                    linerData[item.code] = item.scac; 
                });

                if ($('#liner_d').find(`option[value="${data.carrier}"]`).length === 0) {
                    $('#liner_d').append(`<option value="${data.carrier}">${data.carrier}</option>`);
                    linerData[data.carrier] = data.scac; 
                }
                
                $('#liner_d').off('change').on('change', function () {
                    const selectedCode = $(this).val();
                    const scac = linerData[selectedCode] || '';
                    $('#scac_d').val(scac);
                });

                $('#liner_d').val(data.carrier).trigger('change');
                
                if(data.scac) {
                     $('#scac_d').val(data.scac);
                }
            });

            let agentData = {}; 

            $.get("<?= base_url('afg_c_dropdown/dropdown_agent_d') ?>", function(response) {
                const agents = JSON.parse(response);

                agents.forEach(function(agent) {
                    $('#agent_d').append(`<option value="${agent.agent_company_code}" data-name="${agent.agent_company_name}">${agent.agent_company_code}</option>`);
                    
                    agentData[agent.agent_company_code] = {
                        name: agent.agent_company_name,
                        city: agent.agent_company_city
                    };
                });

                const selectedAgent = data.agen_penagihan;

                if ($('#agent_d').find(`option[value="${selectedAgent}"]`).length === 0) {
                    $('#agent_d').append(`<option value="${selectedAgent}" data-name="">${selectedAgent}</option>`);
                }

                $('#agent_d').val(selectedAgent).trigger('change');

                if (agentData[selectedAgent]) {
                    const info = agentData[selectedAgent];
                    $('#validator3').text(`${info.name} - ${info.city}`);
                }
            });
			
            if ($('#warehouse_d').find(`option[value="${data.warehouse}"]`).length === 0) {
                $('#warehouse_d').append(`<option value="${data.warehouse}">${data.warehouse}</option>`);
            }
            
            $('#warehouse_d').val(data.warehouse).trigger('change');
            
            if ($('#type_container_d').find(`option[value="${data.type_cont_size}"]`).length === 0) {
                $('#type_container_d').append(`<option value="${data.type_cont_size}">${data.type_cont_size}</option>`);
            }
            
            $('#type_container_d').val(data.type_cont_size).trigger('change');
            $('.modal-title').text('Detail Job '+data.kode_ref);
        }
    });

    $('#region_id_d').select2({ placeholder: 'Select POL', allowClear: false });
    $('#city_identifier').select2({ placeholder: 'Select POD', allowClear: false });
    $('#etd_jkt_d').select2({ placeholder: 'Select ETD', allowClear: false });
    $('#type_container_d').select2({ placeholder: 'Select Container', allowClear: false });
    $('#warehouse_d').select2({ placeholder: 'Select Warehouse', allowClear: false });
	
    function formatAgentDropdown(item) {
        if (!item.id) {
            return item.text;
        }
        var agentName = $(item.element).data('name') || '';
        var $container = $(
            '<div class="custom-select2-item">' + 
                '<span class="item-name" style="font-weight: 700;">' + item.text + '</span><br>' + 
                '<span class="item-code">' + agentName + '</span>' + 
            '</div>'
        );
        return $container;
    }

    function formatAgentSelection(item) {
        return item.text;
    }

    $('#agent_d').select2({
        placeholder: 'Select Agent',
        allowClear: false,
        templateResult: formatAgentDropdown,    
        templateSelection: formatAgentSelection 
    });

    let linerData = {}; 

    $('#liner_d').select2({
        placeholder: 'Select Liner',
        allowClear: false,
        width: '100%'
    });
	
    let agentData = {}; 

    $.get("<?= base_url('afg_c_dropdown/dropdown_agent_d') ?>", function(data) {
        const agents = JSON.parse(data);
        agents.forEach(function(agent) {
            $('#agent_d').append(`<option value="${agent.agent_company_code}">${agent.agent_company_code}</option>`);
            agentData[agent.agent_company_code] = {
                name: agent.agent_company_name,
                city: agent.agent_company_city
            };
        });
    });
	
    $('#agent_d').on('change', function () {
        const selectedCode = $(this).val();
        const validator = $('#validator3');
        
        if (selectedCode && agentData[selectedCode]) {
            const info = agentData[selectedCode];
            validator.text(`${info.name} - ${info.city}`);
        } else {
            validator.text('');
        }
    });
	
    $.get("<?= base_url('afg_c_dropdown/dropdown_warehouse_d') ?>", function(data) {
        const warehouse_d = JSON.parse(data);
        warehouse_d.forEach(function(item) {
            $('#warehouse_d').append(`<option value="${item.warehouse_code}">${item.warehouse_code}</option>`);
        });
    });

    $.get("<?= base_url('afg_c_dropdown/dropdown_container_lcl') ?>", function(data) {
        const type_cont_d = JSON.parse(data);
        type_cont_d.forEach(function(item) {
            $('#type_container_d').append(`<option value="${item}">${item.toUpperCase()}</option>`);
        });
    });
	
    $.get("<?= base_url('afg_c_dropdown/get_region_options') ?>", function(data) {
        const regions = JSON.parse(data);
        regions.forEach(function(item) {
            $('#region_id_d').append(`<option value="${item}">${item.toUpperCase()}</option>`);
        });
    });

    $('#region_id_d').on('change', function() {
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
        let region = $('#region_id_d').val();
        let city = $('#city_identifier').val();

        if (region && city) {
            $.get("<?= base_url('afg_c_dropdown/get_etd_options') ?>", { region_id: region, city_identifier: city }, function(data) {
                let options = JSON.parse(data);
                $('#etd_jkt_d').empty().append(`<option value="">Select ETD JKT</option>`);
                options.forEach(function(item) {
                    $('#etd_jkt_d').append(`<option value="${item}">${item}</option>`);
                });
            });
        }
    }

    $('#region_id_d, #city_identifier').on('change', loadEtdOptions);
    
    $('#liner_d').on('change', function () {
        const selectedCode = $(this).val();
        const scac = linerData[selectedCode] || '';
        $('#scac_d').val(scac);
    });
});

function escapeScheduleHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function formatScheduleDate(value) {
    if (!value || value === '0000-00-00' || value === '0000-00-00 00:00:00') {
        return '-';
    }

    const dateOnly = String(value).substring(0, 10);
    const parts = dateOnly.split('-');
    if (parts.length === 3) {
        return `${parts[2]}/${parts[1]}/${parts[0]}`;
    }

    return value;
}

function applyScheduleToStuffingPlan(data) {
    if (!data) return;

    const cityId = data.eta_city_id ?? data.city_id ?? '';
    const hubCities = [
        'HONG KONG',
        'BUSAN (EX PUSAN)',
        'SINGAPORE',
        'PORT KELANG',
        'WESTPORT/PORT KLANG',
        'JEBEL ALI',
        'KOBE',
        'TOKYO'
    ];

    let transId = '';
    if (hubCities.includes(String(cityId).toUpperCase())) {
        transId = cityId;
    } else {
        transId = data.etd_city_con_id ?? '';
    }

    const closingDateFormatted = data.stf_cls ? data.stf_cls : '';

    $('#etd_pol').val(data.etd_jkt ?? '');
    $('#eta_des').val(data.eta ?? '');
    $('#warehouse_d').val(data.stuffing_location ?? '').trigger('change');
    $('#closing').val(closingDateFormatted);

    let route_d = 'DIRECT';
    if (hubCities.includes(String(cityId).toUpperCase())) {
        route_d = 'TRANSSHIPMENT';
    } else if (data.route_type === 'VIA') {
        route_d = 'VIA';
    }

    $('#route_d').val(route_d);

    const portLoadingMap = {
        'Jakarta': 'TANJUNG PRIOK',
        'Surabaya': 'SURABAYA (PTT)',
        'Semarang': 'SEMARANG (PTT)',
        'Medan': 'BELAWAN'
    };

    const region = data.region_id ?? '';
    const portLoadingValue = portLoadingMap[region] ?? '';

    $('#pol_job').val(portLoadingValue).attr('disabled', true);
    $('#pot_job').val(transId).attr('disabled', true);
    $('#pod_job').val(cityId).attr('disabled', true);

    $('#vessel_d').val(data.vessel ?? '');
    $('#voyage_d').val(data.voy ?? data.voy_vessel ?? '');
    $('#route_d_data').val(data.route_type ?? '');
    $('#schedule_id_data').val(data.id ?? '');
}

function showSchedulePicker(schedules) {
    const rows = schedules.map(function(data, index) {
        const scheduleId = escapeScheduleHtml(data.id ?? '-');
        const vessel = escapeScheduleHtml(data.vessel || '-');
        const voyage = escapeScheduleHtml(data.voy ?? data.voy_vessel ?? '-');
        const carrier = escapeScheduleHtml(data.carrier || '-');
        const etd = escapeScheduleHtml(formatScheduleDate(data.etd_jkt));
        const eta = escapeScheduleHtml(formatScheduleDate(data.eta));
        const stuffingDate = escapeScheduleHtml(formatScheduleDate(data.stuffing_date));
        const location = escapeScheduleHtml(data.stuffing_location || '-');

        return `
            <label class="schedule-picker-row" style="
                display:grid;
                grid-template-columns:36px 70px minmax(150px,1.4fr) minmax(85px,.8fr) 95px 95px 105px minmax(120px,1fr);
                gap:8px;
                align-items:center;
                padding:11px 10px;
                border-bottom:1px solid #e5e7eb;
                cursor:pointer;
                text-align:left;
                font-size:12px;
                background:#fff;
            ">
                <span style="text-align:center;">
                    <input type="radio" name="schedule_picker" value="${index}" style="cursor:pointer;">
                </span>
                <strong>${scheduleId}</strong>
                <span><strong>${vessel}</strong><br><small style="color:#64748b;">${carrier}</small></span>
                <strong>${voyage}</strong>
                <span>${etd}</span>
                <span>${eta}</span>
                <span>${stuffingDate}</span>
                <span>${location}</span>
            </label>`;
    }).join('');

    const html = `
        <div style="overflow:auto; border:1px solid #e5e7eb; border-radius:8px; max-height:420px;">
            <div style="
                display:grid;
                grid-template-columns:36px 70px minmax(150px,1.4fr) minmax(85px,.8fr) 95px 95px 105px minmax(120px,1fr);
                gap:8px;
                align-items:center;
                padding:10px;
                background:#f8fafc;
                border-bottom:1px solid #cbd5e1;
                font-size:11px;
                font-weight:800;
                color:#475569;
                text-align:left;
                min-width:840px;
            ">
                <span></span>
                <span>ID</span>
                <span>VESSEL / CARRIER</span>
                <span>VOYAGE</span>
                <span>ETD</span>
                <span>ETA</span>
                <span>STUFFING</span>
                <span>LOCATION</span>
            </div>
            <div style="min-width:840px;">${rows}</div>
        </div>
        <div style="margin-top:10px; font-size:12px; color:#64748b; text-align:left;">
            Ditemukan <strong>${schedules.length}</strong> schedule dengan filter yang sama. Pilih berdasarkan vessel/voyage yang akan dipakai.
        </div>`;

    Swal.fire({
        icon: 'info',
        title: 'Pilih Schedule',
        html: html,
        width: 980,
        showCancelButton: true,
        confirmButtonText: 'Pakai Schedule',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#f59e0b',
        focusConfirm: false,
        didOpen: function() {
            const popup = Swal.getPopup();

            $(popup).on('click', '.schedule-picker-row', function(e) {
                if (!$(e.target).is('input[type="radio"]')) {
                    $(this).find('input[type="radio"]').prop('checked', true);
                }

                $(popup).find('.schedule-picker-row').css('background', '#fff');
                $(this).css('background', '#fffbeb');
            });
        },
        preConfirm: function() {
            const selected = $('input[name="schedule_picker"]:checked').val();
            if (typeof selected === 'undefined') {
                Swal.showValidationMessage('Silakan pilih Vessel / Voyage terlebih dahulu.');
                return false;
            }
            return Number(selected);
        }
    }).then(function(result) {
        if (result.isConfirmed && Number.isInteger(result.value)) {
            applyScheduleToStuffingPlan(schedules[result.value]);
        }
    });
}

$('#searchForm').on('submit', function(e) {
    e.preventDefault();

    let formData = {
        region_id: $('#region_id_d').val(),
        city_identifier: $('#city_identifier').val(),
        etd_jkt: $('#etd_jkt_d').val()
    };

    $.ajax({
        url: '<?= base_url('afg_c_dropdown/search_json') ?>',
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if (!Array.isArray(response) || response.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Schedule Tidak Ditemukan',
                    text: 'Tidak ada schedule yang sesuai dengan Region, City dan ETD yang dipilih.',
                    confirmButtonColor: '#f59e0b'
                });
                return;
            }

            if (response.length === 1) {
                applyScheduleToStuffingPlan(response[0]);
                return;
            }

            showSchedulePicker(response);
        },
        error: function(xhr) {
            console.error('Search schedule error:', xhr.responseText);
            Swal.fire({
                icon: 'error',
                title: 'Gagal Mencari Schedule',
                text: 'Terjadi kesalahan saat mengambil data schedule.',
                confirmButtonColor: '#ef4444'
            });
        }
    });
});

function renderFormFields() {
    const selectedValue = document.getElementById("TypeOfTransaction").value;
    const toolbarSearchDiv = document.querySelector(".DynamicForm");
    if (toolbarSearchDiv) {
        if (selectedValue === "LCL") {
            toolbarSearchDiv.style.display = "block";
        } else {
            toolbarSearchDiv.style.display = "none";
        }
    }
}

document.addEventListener("DOMContentLoaded", function () {
    renderFormFields(); 
});

var container1 = document.getElementById('container_d');
var validator1 = document.getElementById('validator1');

function getValidatorMessage(validator, containerNo){
    var isvalid     = validator.isValid(containerNo);
    var valMsgs     = validator.getErrorMessages();
    var valMsgsTxt  = valMsgs.join(', ');
    msg = isvalid;
    if (msg == false) {
        msg+= ' : ' + valMsgsTxt + '.';
        if(valMsgs.indexOf('The check digit does not match') != -1){
            msg+= ' Check digit must be ' + validator.createCheckDigit(containerNo.substr(0,10)) + '.';
        }
    }
    return msg;
}

function checkValid(txt) {
    if(txt.value != ''){                    
        var validator = new ContainerValidator();
        validator1.innerHTML = getValidatorMessage(validator, txt.value);
    }else{
        validator1.innerHTML = 'Please enter Container Number';
    }
}
checkValid(container1);
  
function ContainerValidator()  {
    var STR_PAD_LEFT = 'STR_PAD_LEFT';
    this.alphabetNumerical = {
        'A' : 10, 'B' : 12, 'C' : 13, 'D' : 14, 'E' : 15, 'F' : 16, 'G' : 17, 'H' : 18, 'I' : 19,
        'J' : 20, 'K' : 21, 'L' : 23, 'M' : 24, 'N' : 25, 'O' : 26, 'P' : 27, 'Q' : 28, 'R' : 29,
        'S' : 30, 'T' : 31, 'U' : 32, 'V' : 34, 'W' : 35, 'X' : 36, 'Y' : 37, 'Z' : 38
    };
    this.pattern = /^([A-Z]{3})(U|J|Z)(\d{6})(\d)$/;
    this.patternWithoutCheckDigit = /^([A-Z]{3})(U|J|Z)(\d{6})$/;
    this.errorMessages = [];
    this.ownerCode = [];
    this.productGroupCode;
    this.registrationDigit = [];
    this.checkDigit;
    this.containerNumber;

    this.isValid = function(containerNumber) {
        valid = this.validate(containerNumber);
        if (this.empty(this.errorMessages)) {
            return "Nomor Container Valid";
        }
        return false;
    }

    this.validate = function(containerNumber) {
        matches = [];
        if (!this.empty(containerNumber) && this.is_string(containerNumber)) {
            matches = this.identify(containerNumber);
            if (this.count(matches) !== 5) {
                this.errorMessages.push('The container number is invalid');
            } else {
                checkDigit = this.buildCheckDigit(matches);
                if (this.checkDigit != checkDigit) {
                    this.errorMessages.push('The check digit does not match');
                    matches = [];
                }
            }
        } else {
            this.errorMessages = {0:'The container number must be a string'};
        }
        return matches;
    }

    this.getErrorMessages = function() { return this.errorMessages; }
    this.getOwnerCode = function() {
        if (this.empty(this.ownerCode)) this.errorMessages.push('You must call validate or isValid first');
        return this.ownerCode;
    }
    this.getProductGroupCode = function() {
        if (this.empty(this.productGroupCode)) this.errorMessages.push('You must call validate or isValid first');
        return this.productGroupCode;
    }
    this.getRegistrationDigit = function() {
        if (this.empty(this.registrationDigit)) this.errorMessages.push('You must call validate or isValid first');
        return this.registrationDigit;
    }
    this.getCheckDigit = function() {
        if (this.empty(this.checkDigit)) this.errorMessages.push('You must call validate or isValid first');
        return this.checkDigit;
    }

    this.generate = function(ownerCode, productGroupCode, from, to) {
        from = typeof from !== 'undefined' ? from : 0;
        to = typeof to !== 'undefined' ? to : 999999;
        alphabetCode = this.strtoupper(ownerCode + productGroupCode);
        containers_no = [];
        if (this.is_string(alphabetCode) && this.strlen(ownerCode) === 3 && this.strlen(productGroupCode) === 1) {
            containers_no = [];
            current_container_no = '';
            current_container_check_digit = '';
            if ((from >= 0) && (to < 1000000) && ((to - from) > 0)) {
                for(var i = from; i <= to; i++) {
                    current_container_no = alphabetCode + this.str_pad(i, 6, '0', STR_PAD_LEFT);
                    current_container_check_digit = this.createCheckDigit(current_container_no);
                    if (current_container_check_digit < 0) {
                        this.errorMessages.push('Error generating container number at number ' + i);
                        return containers_no;
                    }
                    containers_no[i] = current_container_no + current_container_check_digit;
                }
            } else {
                this.errorMessages.push('Invalid number to generate, minimal is 0 and maximal is 999999');
            }
        } else {
            this.errorMessages.push('Invalid owner code or product group code');
        }
        return containers_no;
    }

    this.createCheckDigit = function(containerNumber) {
        checkDigit = -1;
        if (!this.empty(containerNumber) && this.is_string(containerNumber)) {
            matches = this.identify( containerNumber, true );
            if (this.count(matches) !== 4 || (matches[4])) {
                this.errorMessages.push('Invalid container number');
            } else {
                checkDigit = this.buildCheckDigit(matches);
                if (checkDigit < 0) {
                    this.errorMessages.push('Invalid container number');
                }
            }
        } else {
            this.errorMessages.push('Container number must be a string');
        }
        return checkDigit;
    }

    this.clearErrors = function() { this.errorMessages = []; }

    this.buildCheckDigit = function(matches) {
        if ((matches[1])) this.ownerCode = this.str_split(matches[1]);
        if ((matches[2])) this.productGroupCode = matches[2];
        if ((matches[3])) this.registrationDigit = this.str_split(matches[3]);
        if ((matches[4])) this.checkDigit = matches[4];
        
        numericalOwnerCode = [];
        for(var i = 0; i < this.count(this.ownerCode); i++) {
            numericalOwnerCode[i] = this.alphabetNumerical[this.ownerCode[i]];
        }
        numericalOwnerCode.push(this.alphabetNumerical[this.productGroupCode]);

        numericalCode = this.array_merge(numericalOwnerCode, this.registrationDigit);
        sumDigit = 0;
        for(var i = 0; i < this.count(numericalCode); i++) {
            sumDigit += numericalCode[i] * Math.pow(2, i);
        }

        sumDigitDiff = Math.floor(sumDigit / 11) * 11;
        checkDigit = sumDigit - sumDigitDiff;
        return (checkDigit == 10) ? 0 : checkDigit;
    }

    this.identify = function(containerNumber, withoutCheckDigit) {
        withoutCheckDigit = typeof withoutCheckDigit !== 'undefined' ? withoutCheckDigit : false;
        this.clearErrors();
        if (withoutCheckDigit) {
            matches = this.preg_match(this.patternWithoutCheckDigit, this.strtoupper(containerNumber));
        } else {
            matches = this.preg_match(this.pattern, this.strtoupper(containerNumber));
        }
        return matches;
    }
    
    this.is_string = function(param){ return typeof param == 'string' ? true : false; }
    this.preg_match = function(pattern, string){ var regex = new RegExp(pattern); return regex.exec(string); }
    this.strtoupper = function(string){ return string.toUpperCase(); }
    this.count = function(array){ if (array == null) { return 0; }else{ return array.length; } }
    this.strlen = function(string){ return string.length; }
    
    this.str_split = function(string, split_length) {
        if (split_length == null) split_length = 1;
        if (string == null || split_length < 1) return false;
        string += '';
        var chunks = [], pos = 0, len = string.length;
        while (pos < len) {
          chunks.push(string.slice(pos, pos += split_length));
        }
        return chunks;
    }
    
    this.str_pad = function(input, pad_length, pad_string, pad_type) {
        var half = '', pad_to_go;
        var str_pad_repeater = function(s, len) {
          var collect = '', i;
          while (collect.length < len) { collect += s; }
          collect = collect.substr(0, len);
          return collect;
        };
        input += '';
        pad_string = pad_string !== undefined ? pad_string : ' ';
        if (pad_type !== 'STR_PAD_LEFT' && pad_type !== 'STR_PAD_RIGHT' && pad_type !== 'STR_PAD_BOTH') {
          pad_type = 'STR_PAD_RIGHT';
        }
        if ((pad_to_go = pad_length - input.length) > 0) {
          if (pad_type === 'STR_PAD_LEFT') {
            input = str_pad_repeater(pad_string, pad_to_go) + input;
          } else if (pad_type === 'STR_PAD_RIGHT') {
            input = input + str_pad_repeater(pad_string, pad_to_go);
          } else if (pad_type === 'STR_PAD_BOTH') {
            half = str_pad_repeater(pad_string, Math.ceil(pad_to_go / 2));
            input = half + input + half;
            input = input.substr(0, pad_length);
          }
        }
        return input;
    }
    
    this.array_merge = function() {
        var args = Array.prototype.slice.call(arguments), argl = args.length, arg, retObj = {},
          k = '', argil = 0, j = 0, i = 0, ct = 0, toStr = Object.prototype.toString, retArr = true;
        for (i = 0; i < argl; i++) {
          if (toStr.call(args[i]) !== '[object Array]') { retArr = false; break; }
        }
        if (retArr) {
            retArr = [];
            for (i = 0; i < argl; i++) { retArr = retArr.concat(args[i]); }
            return retArr;
        }
        for (i = 0, ct = 0; i < argl; i++) {
          arg = args[i];
          if (toStr.call(arg) === '[object Array]') {
            for (j = 0, argil = arg.length; j < argil; j++) { retObj[ct++] = arg[j]; }
          } else {
            for (k in arg) {
              if (arg.hasOwnProperty(k)) {
                if (parseInt(k, 10) + '' === k) { retObj[ct++] = arg[k]; } 
                else { retObj[k] = arg[k]; }
              }
            }
          }
        }
        return retObj;
    }
    
    this.empty = function(mixed_var) {
        var undef, key, i, len;
        var emptyValues = [undef, null, false, 0, '', '0'];
        for (i = 0, len = emptyValues.length; i < len; i++) {
          if (mixed_var === emptyValues[i]) { return true; }
        }
        if (typeof mixed_var === 'object') {
            for (key in mixed_var) { return false; }
            return true;
        }
        return false;
    }
}

function filterAngka(input) {
    input.value = input.value.replace(/\D/g, ''); 
}

function formatYYYYMMDD(dateString) {
    if (!dateString) return '';
    return String(dateString).replace(/-/g, '');
}

$('#btnSaveJob').off('click').on('click', function () {
    let typeOfTrans = $('#TypeOfTransaction').val();

    if (typeOfTrans === 'LCL') {
        if (!$('#etd_pol').val() || !$('#eta_des').val() || !$('#pol_job').val() || !$('#pod_job').val()) {
            Swal.fire({
                icon: 'warning',
                title: 'Data Belum Lengkap!',
                text: 'Untuk transaksi LCL, Schedule tidak boleh kosong.',
                confirmButtonColor: '#f59e0b'
            });
            return false;
        }
    }

    const excludedCarriers = [
        "LPIJKT", "SUNGGONG", "CTP", "GANINDO", "KERRY", 
        "LPI SRG", "TRESNA", "MONTER", "COLLYER", 
        "SETIA SAMUDRA", "BLESSINDO", "JAMESON", "SLL"
    ];

    const mother_bl = $('#mbl_d').val();
    const scac = $('#scac_d').val();
    const carrier = $('#liner_d').val();
    const warehouse = $('#warehouse_d').val();
    const kode_ref = $('#txtRefno2').val();
    const cek = 100; 

    const isExcluded = excludedCarriers.some(c => carrier.includes(c));
    
    if (mother_bl !== '' || scac !== "AUTO" || !isExcluded) {
        if (scac === "") {
            Swal.fire('Nama liner dan SCAC harus isi dahulu');
            return false;           
        } else if (warehouse === "") {
            Swal.fire('Warehouse harus isi dahulu');
            return false;           
        }

        $.ajax({
            type: "POST", 
            url : '<?= base_url("afg_c_stuffing_plan/proses_gocomet") ?>',
            data: { mother_bl, scac, kode_ref, cek },
            success: function(response) {
                console.log("Gocomet Status: Shipment berhasil di track");
            },
            error: function(response){
                console.error("Gocomet Error: " + response.responseText);
            }
        });
    }

    let $btn = $(this);
    let originalText = $btn.text();
    $btn.text('Saving Data...').prop('disabled', true);

    let rawDate = $('#dateInput').val();
    let closingVal = $('#closing').val();
    let closingDateTime = '';
    let closingTime = '00:00';

    if (closingVal) {
        let splitClosing = closingVal.split('T');
        closingDateTime = splitClosing[0];
        closingTime = splitClosing[1] || '00:00';
    }

    let postData = {
        mode: 'edit',
        sp_id: $('#spId').val(),
        kode_ref: kode_ref,
        pesan: 'Data berhasil diupdated',
        tanggal_job: formatYYYYMMDD(rawDate),
        stuffing_date: formatYYYYMMDD($('#stuffing_d').val()),
        closing_date_time: formatYYYYMMDD(closingDateTime),
        etd_jkt: formatYYYYMMDD($('#etd_pol').val()),
        eta: formatYYYYMMDD($('#eta_des').val()),
        type_of_trans: typeOfTrans,
        warehouse: warehouse,
        carrier: carrier,
        scac: scac,
        mother_bl: mother_bl,
        vessel: $('#vessel_d').val(),
        voyage: $('#voyage_d').val(),
        closing_time: closingTime,
        port_loading: $('#pol_job').val(),
        transhipment_port: $('#pot_job').val(),
        destination: $('#pod_job').val(),
        cont_seal_no: $('#container_d').val() + ($('#seal_d').val() ? ' / ' + $('#seal_d').val() : ''),
        type_cont_qty: $('#jml_container_d').val(),
        type_cont_size: $('#type_container_d').val(),
        agen_penagihan: $('#agent_d').val(),
        kurs: $('#kurs_d').val(),
        shipment_type_multi: 'Consol',
        atd: formatYYYYMMDD($('#atd_pol').val()),
        ata: formatYYYYMMDD($('#ata_des').val()),
        isBatal: 0,
        kurs_selling: $('#kurs_jualan').val(),
        schedule_id: $('#schedule_id_data').val(),
        route_type: $('#route_d_data').val(),
        cargo_type: $('#cargo_type_d').val() || 'GENERAL',
        is_dg: $('#is_dg_d').val() || 0,
        dg_details: $('#dg_details_d').val() || ''
    };

    $.ajax({
		async: true,
		url: '<?= base_url("afg_c_stuffing_plan/submitStuffingPlan") ?>', //[cite: 2]
		type: 'POST', //[cite: 2]
		data: postData, //[cite: 2]
		cache: false, // (Opsi Tambahan) Pastikan browser tidak memuat dari cache
		success: function (responseSubmit) {
			// 1. Tutup modal form edit
			try { $('#FORM_NEW_JOB').modal('hide'); } catch (e) {} //[cite: 2]
			try { $('#FORM_NEW_JOB').dialog('close'); } catch (e) {} //[cite: 2]

			// 2. Refresh tabel master stuffing plan
			$('#tabel_stuffing_plan').jqGrid("setGridParam", { datatype: 'json' }).trigger("reloadGrid"); //[cite: 2]

			// 3. (TAMBAHAN) Refresh tabel detail agar data child di background ikut terupdate
			$('#tabel_sp_detail').jqGrid("setGridParam", { datatype: 'json' }).trigger("reloadGrid");
			$('#tabel_sp_detail_export').jqGrid("setGridParam", { datatype: 'json' }).trigger("reloadGrid");

			// 4. Tampilkan pesan alert sukses
			success_saving('edit'); //[cite: 2]

			// 5. Refresh form utama di background dengan memanggil ulang datanya
			getStuffingPlan('pilih'); //[cite: 2]

			// 6. Kembalikan teks dan fungsi tombol
			$btn.text(originalText).prop('disabled', false); //[cite: 2]
		},
			error: function (XMLHttpRequest) {
            $btn.text(originalText).prop('disabled', false);
            $("#Insert-Form").dialog({
                title: "ERROR SUBMIT STUFFINGPLAN",
                buttons: {
                    "OK": function () {
                        $(this).dialog("close");
                        getStuffingPlan('pilih');
                        if (typeof enabled_top_button === "function") {
                            enabled_top_button();
                        }
                    }
                }
            });
            $('#Insert-Form').html(XMLHttpRequest.responseText);
        }
    });
});

</script>
</body>
</html>