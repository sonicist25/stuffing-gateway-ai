<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Job Details</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <style>
    /* RESET & DASAR */
    :root {
      --bg-main: #f0f4f8; 
      --glass-bg: #ffffff;
      --glass-border: #e2e8f0;
      --glass-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
      --text-main: #1e293b;
      --text-muted: #64748b;
      --input-bg: #ffffff;
      --input-border: #cbd5e1;
      --accent-blue: #0ea5e9;
      --accent-hover: #0284c7;
      --danger: #ef4444;
      --success-bg: #dcfce7;
      --success-text: #166534;
      --danger-bg: #fee2e2;
      --danger-text: #991b1b;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
      background: var(--bg-main);
      color: var(--text-main);
      min-height: 100vh;
      padding: 20px;
    }
	
	.modal-container {
	  animation: fadeInUp 0.4s ease-out;
      background: var(--glass-bg) !important;
      border: 1px solid var(--glass-border) !important;
      box-shadow: var(--glass-shadow) !important;
      max-width: 1180px !important;
      margin: 10px auto !important;
      padding: 30px !important;
      border-radius: 16px !important;
	}

	@keyframes fadeInUp {
	  from { opacity: 0; transform: translateY(15px); }
	  to { opacity: 1; transform: translateY(0); }
	}
	
    .modal-title {
      font-size: 24px !important;
      font-weight: 700 !important;
      margin-bottom: 25px !important;
      color: var(--text-main) !important;
      letter-spacing: 0.2px;
      border-bottom: 2px solid #f1f5f9;
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
      background: transparent;
      border: none;
      padding: 0;
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
    .form-group select,
    .static-field {
      background: var(--input-bg) !important;
      color: var(--text-main) !important;
      border: 1px solid var(--input-border) !important;
      padding: 8px 12px !important;
      font-size: 13px !important;
      border-radius: 8px !important;
      min-height: 38px !important;
      height: 38px !important;
      transition: all 0.2s ease;
      width: 100%;
    }

    .form-group input:focus,
    .form-group select:focus {
      border-color: var(--accent-blue) !important;
      box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.15) !important;
      outline: none;
    }

    .form-group input:disabled,
    .form-group select:disabled {
        background: #f8fafc !important;
        color: #94a3b8 !important;
        border-color: #e2e8f0 !important;
        cursor: not-allowed;
    }

    .static-field {
      display: flex;
      align-items: center;
      line-height: 1.4;
      background: #f8fafc !important;
      color: var(--text-main) !important;
    }

    .btn-submit, .btn-primary {
      background: linear-gradient(135deg, var(--accent-blue), var(--accent-hover)) !important;
      color: white !important;
      padding: 12px 24px !important;
      font-size: 14px !important;
      font-weight: 600 !important;
      border: none !important;
      border-radius: 8px !important;
      cursor: pointer;
      transition: all 0.3s ease !important;
      box-shadow: 0 4px 10px rgba(2, 132, 199, 0.2);
    }

    .btn-submit:hover, .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 14px rgba(2, 132, 199, 0.3);
    }

    hr {
        border: 0;
        height: 1px;
        background: #e2e8f0;
        margin: 25px 0 !important;
    }

    /* Validator */
    .validator {
        font-size: 11px;
        color: var(--danger);
        margin-top: 4px;
        font-weight: 500;
    }

    /* Mode create */
    .modal-container.create-mode {
      position: relative;
      border-top: 4px solid var(--accent-blue) !important;
    }

    .modal-container.create-mode::before {
      content: "CREATE MODE";
      position: absolute;
      top: -16px;
      left: 20px;
      background: var(--accent-blue);
      color: #fff;
      font-size: 11px;
      font-weight: 700;
      padding: 6px 14px;
      border-radius: 20px;
      box-shadow: 0 4px 10px rgba(14, 165, 233, 0.3);
      letter-spacing: 0.5px;
    }

    /* --- SELECT2 LIGHT MODE OVERRIDES --- */
    /* PATCH: Paksa Select2 agar mengambil lebar 100% dari kolom flex/grid */
    .select2-container {
        width: 100% !important;
    }

    .select2-container--default .select2-selection--single {
        background-color: var(--input-bg) !important;
        border: 1px solid var(--input-border) !important;
        height: 38px !important;
        min-height: 38px !important;
        border-radius: 8px !important;
        display: flex;
        align-items: center;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: var(--text-main) !important;
        line-height: normal !important;
        padding-left: 12px !important;
        padding-right: 24px !important; /* Spasi untuk panah */
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px !important;
        top: 1px !important;
        right: 8px !important;
    }
    
    .select2-container--default .select2-selection--single .select2-selection__arrow b {
        border-color: #94a3b8 transparent transparent transparent !important;
    }

    .select2-dropdown {
        background-color: #ffffff !important;
        border: 1px solid var(--input-border) !important;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        border-radius: 8px;
        overflow: hidden;
        z-index: 9999;
    }

    .select2-search--dropdown .select2-search__field {
        background-color: #f8fafc !important;
        border: 1px solid var(--input-border) !important;
        color: var(--text-main) !important;
        border-radius: 4px;
        padding: 6px;
    }

    .select2-container--default .select2-results__option {
        color: var(--text-main);
        padding: 8px 12px;
        font-size: 13px;
    }

    .select2-container--default .select2-results__option[aria-selected=true] {
        background-color: #f1f5f9 !important;
    }

    .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: var(--accent-blue) !important;
        color: #ffffff !important;
    }

    .select2-container--default.select2-container--focus .select2-selection--single,
    .select2-container--default.select2-container--open .select2-selection--single {
        border-color: var(--accent-blue) !important;
        box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.15) !important;
    }

    .custom-select2-item .item-code {
        font-size: 11px;
        color: var(--text-muted);
    }
    .select2-results__option--highlighted .custom-select2-item .item-code {
        color: #e0f2fe; /* Warna terang agar kontras saat di-hover biru */
    }
    /* ------------------------------------ */

    /* Schedule Selection Section */
    #scheduleSelectionSection {
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 12px;
      padding: 20px;
      margin: 20px 0;
    }
    #scheduleSelectionSection hr { display: none; }
    #scheduleSelectionSection h2 {
      font-size: 16px !important;
      margin: 0 0 16px !important;
      font-weight: 700 !important;
      color: var(--text-main) !important;
    }

    .toolbar-search .search-form {
      display: grid !important;
      /* Grid diperbaiki agar proporsional */
      grid-template-columns: minmax(200px, 1fr) minmax(200px, 1fr) minmax(200px, 1fr) auto !important;
      gap: 16px !important;
      align-items: end;
    }

    .schedule-auto-card {
      display: none;
      margin: 16px 0 24px;
      padding: 20px;
      background: #ffffff;
      border: 1px solid var(--input-border);
      border-radius: 12px;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }
    .schedule-auto-grid {
      display: grid;
      grid-template-columns: repeat(5, minmax(0, 1fr));
      gap: 16px;
    }
    .schedule-auto-item {
      border: 1px solid #f1f5f9;
      border-radius: 8px;
      padding: 12px;
      background: #f8fafc;
    }
    .schedule-auto-label {
      display: block;
      font-size: 10px;
      font-weight: 700;
      text-transform: uppercase;
      color: var(--text-muted);
      letter-spacing: 0.5px;
      margin-bottom: 6px;
    }
    .schedule-auto-value {
      display: block;
      font-size: 13px;
      font-weight: 700;
      color: var(--text-main);
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
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
      font-weight: 700;
      background: var(--success-bg);
      color: var(--success-text);
      border: 1px solid rgba(22, 101, 52, 0.1);
    }
    .dg-pill.is-dg { 
        background: var(--danger-bg); 
        color: var(--danger-text); 
        border: 1px solid rgba(153, 27, 27, 0.1);
    }

    /* Responsive adjustments */
    @media (max-width: 1100px) {
      .form-group-row, .schedule-auto-grid { grid-template-columns: repeat(2, minmax(0, 1fr)) !important; }
      .toolbar-search .search-form { grid-template-columns: repeat(2, minmax(0, 1fr)) !important; }
    }
    @media (max-width: 700px) {
      .form-group-row, .schedule-auto-grid, .toolbar-search .search-form { grid-template-columns: 1fr !important; }
    }
  </style>
</head>

<body>

<div class="modal-container">
  <h2 class="modal-title">Job Details</h2>

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
            <span id="validator2" class="validator">Jika MBL NVOCC/COLOADER, isi tracking manual</span>
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
            <label for="kurs_jualan">KURS JUAL</label>
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
        <div class="toolbar-search">
            <form method="post" id="searchForm" class="search-form">
                <div class="form-group">
                    <select id="region_id_d" name="region_id_d">
                        <option value="">Select Region</option>
                    </select>
                </div>
                <div class="form-group">
                    <select id="city_identifier" name="city_identifier">
                        <option value="">Select City ID / ETA City ID</option>
                    </select>
                </div>
                <div class="form-group">
                    <select id="etd_jkt_d" name="etd_jkt_d">
                        <option value="">Select ETD JKT</option>
                    </select>
                </div>
                <div class="form-group">
                    <input type="submit" value="Search" class="btn btn-primary" style="height: 38px; padding: 0 24px;">
                </div>
            </form>
        </div>
    </div>

    <div id="scheduleAutoCard" class="schedule-auto-card">
        <div class="schedule-auto-grid">
            <div class="schedule-auto-item">
                <span class="schedule-auto-label">MBL / Booking</span>
                <span id="schedule_mbl_view" class="schedule-auto-value">-</span>
            </div>
            <div class="schedule-auto-item">
                <span class="schedule-auto-label">Carrier</span>
                <span id="schedule_carrier_view" class="schedule-auto-value">-</span>
            </div>
            <div class="schedule-auto-item">
                <span class="schedule-auto-label">SCAC</span>
                <span id="schedule_scac_view" class="schedule-auto-value">-</span>
            </div>
            <div class="schedule-auto-item">
                <span class="schedule-auto-label">Container</span>
                <span id="schedule_container_view" class="schedule-auto-value">-</span>
            </div>
            <div class="schedule-auto-item">
                <span class="schedule-auto-label">Cargo</span>
                <span id="schedule_cargo_view" class="schedule-auto-value">GENCO</span>
            </div>
        </div>
    </div>

    <!-- ROW 4 -->
    <div class="form-group-row">
        <div class="form-group">
            <label for="pol_job">ORIGIN</label>
            <select id="pol_job" name="pol_job" class="form-control" disabled>
                <option value="">Select Origin</option>
            </select>
        </div>
        <div class="form-group">
            <label for="pot_job">TRANSHIPMENT PORT</label>
            <select id="pot_job" name="pot_job" class="form-control" disabled>
                <option value="">Select POT</option>
            </select>
        </div>
        <div class="form-group">
            <label for="pod_job">DESTINATION</label>
            <select id="pod_job" name="pod_job" class="form-control" disabled>
                <option value="">Select Destination</option>
            </select>
        </div>
    </div>

    <!-- ROW 5 -->
    <div class="form-group-row">
        <div class="form-group">
        <label for="stuffing_date">STUFFING DATE</label>
        <div id="stuffing_date_d_view" class="form-control static-field">-</div>
        <input id="stuffing_date_d" name="stuffing_date_d" type="date" class="form-control" style="display: none;" value="">
        </div>

        <div class="form-group">
        <label for="closing">WAKTU CLOSING</label>
        <div id="closing_view" class="form-control static-field">-</div>
        <input id="closing" name="closing" type="datetime-local" class="form-control" style="display: none;" value="">
        </div>

        <div class="form-group">
        <label for="etd_pol">ETD</label>
        <div id="etd_pol_view" class="form-control static-field">-</div>
        <input id="etd_pol" name="etd_pol" type="date" class="form-control" style="display: none;" value="">
        </div>

        <div class="form-group">
        <label for="eta_des">ETA</label>
        <div id="eta_des_view" class="form-control static-field">-</div>
        <input id="eta_des" name="eta_des" type="date" class="form-control" style="display: none;" value="">
        </div>
    </div>

    <!-- ROW 6 -->
    <div class="form-group-row">
        <div class="form-group">
        <label for="route_d">ROUTE TYPE</label>
        <select id="route_d" name="route_d" class="form-control" disabled>
            <option value=""></option>
            <option value="DIRECT">DIRECT</option>
            <option value="TRANSSHIPMENT">TRANSSHIPMENT</option>
            <option value="VIA">VIA</option> 
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
        
        <div class="form-group">
        <label for="warehouse_d">LOKASI STUFFING</label>
        <select id="warehouse_d" name="warehouse_d" class="form-control" disabled>
            <option value="">Select Location</option>
        </select>
        </div>
    </div>

    <input type="hidden" id="schedule_id_data" name="schedule_id_data">
    <input type="hidden" id="route_d_data" name="route_d_data">
    <input type="hidden" id="schedule_booking_number" name="schedule_booking_number">
    <input type="hidden" id="schedule_carrier" name="schedule_carrier">
    <input type="hidden" id="schedule_scac" name="schedule_scac">
    <input type="hidden" id="schedule_container_type" name="schedule_container_type">
    <input type="hidden" id="schedule_container_qty" name="schedule_container_qty">
    <input type="hidden" id="schedule_container_type_raw" name="schedule_container_type_raw">
    <input type="hidden" id="schedule_cargo_type" name="schedule_cargo_type">
    <input type="hidden" id="schedule_is_dg" name="schedule_is_dg">
    <input type="hidden" id="schedule_dg_details" name="schedule_dg_details">
  </div>
  
  <!-- Submit Button -->
  <div style="text-align: right; margin-top: 30px;">
    <button type="button" id="btnSaveJob" class="btn-submit">Create Job</button>
  </div>
</div>

<script>
var linerData = {};
var agentData = {};
function formatDateToDatetimeLocal(dateStr) {
    if (!dateStr) return '';
    return `${dateStr}T00:00`;
}

function normalizeDateOnly(dateStr) {
    if (!dateStr) return '';
    return String(dateStr).trim().substring(0, 10);
}

function normalizeDateTime(dateStr) {
    if (!dateStr) return '';
    return String(dateStr).trim().substring(0, 16).replace('T', ' ');
}

function setSelect2Value(selector, value, text) {
    const $el = $(selector);
    value = (value || '').toString().trim().toUpperCase();
    text  = (text || value).toString().trim().toUpperCase();

    if (!value) {
        $el.val(null).trigger('change');
        return;
    }

    if ($el.find("option[value='" + value.replace(/'/g, "\'") + "']").length) {
        $el.val(value).trigger('change');
    } else {
        const newOption = new Option(text, value, true, true);
        $el.append(newOption).trigger('change');
    }
}

function parseScheduleContainer(value) {
    let raw = (value || '').toString().trim();
    if (!raw) return '';

    try {
        const decoded = JSON.parse(raw);
        if (Array.isArray(decoded) && decoded.length > 0) {
            raw = decoded.filter(Boolean).join(', ');
        }
    } catch (e) {}

    raw = raw.toUpperCase();
    raw = raw.replace(/^\d+\s*X\s*/i, '');
    raw = raw.replace(/\s*X\s*\d+$/i, '');
    raw = raw.replace(/\s*-\s*\d+$/i, '');

    if (/^20\s*'?\s*(DC|GP|DRY|FT)?$/i.test(raw)) return '20 FT';
    if (/^40\s*'?\s*(HC|HQ|H|HIGH\s*CUBE|HI\s*CUBE)$/i.test(raw)) return '40 HC';
    if (/^40\s*'?\s*(DC|GP|DRY|FT)?$/i.test(raw)) return '40 FT';

    return raw;
}

function applyScheduleAutoFields(data) {
    data = data || {};

    const bookingNo = (data.booking_number || data.mother_bl || data.schedule_booking_number || '').toString().trim().toUpperCase();
    const carrier   = (data.carrier || data.liner_id || data.schedule_carrier || '').toString().trim().toUpperCase();
    const scac      = (data.scac || data.schedule_scac || linerData[carrier] || '').toString().trim().toUpperCase();

    const isDg = String(data.is_dg || data.schedule_is_dg || '') === '1' ||
                 String(data.cargo_type || data.schedule_cargo_type || '').toUpperCase() === 'DG';
    const cargoType = isDg ? 'DG' : 'GENERAL';
    const dgDetails = (data.dg_details || data.schedule_dg_details || '').toString().trim().toUpperCase();

    const containerType = parseScheduleContainer(data.container_type || data.schedule_container_type || data.type_cont_size || '');
    const containerQty  = (data.container_qty || data.schedule_container_qty || data.type_cont_qty || '').toString().trim();
    const containerRaw  = (data.container_type_raw || data.schedule_container_type_raw || '').toString().trim().toUpperCase();

    if (carrier) setSelect2Value('#liner_d', carrier, carrier);
    $('#scac_d').val(scac);
    $('#mbl_d').val(bookingNo);

    if (containerType) setSelect2Value('#type_container_d', containerType, containerType);
    if (containerQty) $('#jml_container_d').val(containerQty);

    $('#cargo_type_d').val(cargoType).trigger('change');
    $('#is_dg_d').val(isDg ? '1' : '0');
    $('#dg_details_d').val(dgDetails);
    $('#dg_badge').text(cargoType === 'DG' ? 'DG' : 'GENCO').toggleClass('is-dg', cargoType === 'DG');

    $('#schedule_booking_number').val(bookingNo);
    $('#schedule_carrier').val(carrier);
    $('#schedule_scac').val(scac);
    $('#schedule_container_type').val(containerType);
    $('#schedule_container_qty').val(containerQty);
    $('#schedule_container_type_raw').val(containerRaw);
    $('#schedule_cargo_type').val(cargoType);
    $('#schedule_is_dg').val(isDg ? '1' : '0');
    $('#schedule_dg_details').val(dgDetails);

    $('#schedule_mbl_view').text(bookingNo || '-');
    $('#schedule_carrier_view').text(carrier || '-');
    $('#schedule_scac_view').text(scac || '-');
    $('#schedule_container_view').text((containerType || '-') + (containerQty ? ' / QTY ' + containerQty : ''));
    $('#schedule_cargo_view').text(cargoType);
    $('#scheduleAutoCard').show();
}

$(document).ready(function() {
    const today = new Date().toISOString().split('T')[0];
    document.getElementById("dateInput").value = today;

    document.getElementById("TypeOfTransaction").value = "LCL";
	renderFormFields();
	document.querySelector('.modal-container').classList.add('create-mode');
    
    $('#region_id_d').select2({ placeholder: 'Select POL', allowClear: false });
    $('#city_identifier').select2({ placeholder: 'Select POD', allowClear: false });
    $('#etd_jkt_d').select2({ placeholder: 'Select ETD', allowClear: false });
    $('#type_container_d').select2({ placeholder: 'Select Container', allowClear: false });
    $('#cargo_type_d').select2({ placeholder: 'Cargo Type', allowClear: false });
    
    $('#cargo_type_d').on('change', function () {
        const isDg = String($(this).val()).toUpperCase() === 'DG';
        $('#is_dg_d').val(isDg ? '1' : '0');
        $('#dg_badge').text(isDg ? 'DG' : 'GENCO').toggleClass('is-dg', isDg);
    });
	
    function formatCustomDropdown(item) {
        if (!item.id) {
            return item.text;
        }
        var itemName = $(item.element).data('name') || '';
        var $container = $(
            '<div class="custom-select2-item">' + 
                '<span class="item-name" style="color: var(--text-main); font-weight: 600;">' + item.text + '</span><br>' + 
                '<span class="item-code">' + itemName + '</span>' + 
            '</div>'
        );
        return $container;
    }

    function formatCustomSelection(item) {
        return item.text;
    }

	$('#agent_d').select2({
		placeholder: 'Select Agent',
		allowClear: false,
		templateResult: formatCustomDropdown,    
		templateSelection: formatCustomSelection 
	});

	$('#liner_d').select2({
		placeholder: 'Select Liner',
		allowClear: false,
		width: '100%',
		templateResult: formatCustomDropdown,    
		templateSelection: formatCustomSelection 
	});

	$.get("<?= base_url('afg_c_dropdown/dropdown_agent_d') ?>", function(data) {
	  const agents = JSON.parse(data);
	  agents.forEach(function(agent) {
		$('#agent_d').append(`<option value="${agent.agent_company_code}" data-name="${agent.agent_company_name}">${agent.agent_company_code}</option>`);
		agentData[agent.agent_company_code] = {
		  name: agent.agent_company_name,
		  city: agent.agent_company_city
		};
	  });
	});
	
	$.get("<?= base_url('afg_c_dropdown/dropdown_liner') ?>", function(data) {
	  const carrierList = JSON.parse(data);
	  carrierList.forEach(function(item) {
		$('#liner_d').append(`<option value="${item.code}" data-name="${item.name || ''}">${item.code}</option>`);
		linerData[item.code] = item.scac; 
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
	
    $('#warehouse_d').select2({
        placeholder: 'Search Location...',
        allowClear: false,
        ajax: {
            url: "<?= base_url('afg_c_dropdown/dropdown_warehouse_a') ?>",
            type: 'POST',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return { q: params.term };
            },
            processResults: function (data) {
                return {
                    results: $.map(data, function(obj) {
                        return { id: obj.warehouse_code, text: obj.warehouse_code };
                    })
                };
            }
        }
    });
	
	function initRouteSelect2(selector, placeholder) {
		$(selector).select2({
			placeholder: placeholder,
			allowClear: false,
			width: '100%',
			ajax: {
				url: "<?= base_url('afg_c_stuffing_plan/fetch_dropdown_data') ?>",
				type: 'GET',
				dataType: 'json',
				delay: 250,
				data: function (params) {
					return { q: params.term };
				},
				processResults: function (data) {
					return { results: data }; 
				}
			}
		});
	}

	initRouteSelect2('#pol_job', 'Search Origin...');
	initRouteSelect2('#pot_job', 'Search Transhipment');
	initRouteSelect2('#pod_job', 'Search Destination...');
});

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
            if (response.length > 0) {
                const data = response[0];
                applyScheduleAutoFields(data);
                let transId; 

                const cityId = data.eta_city_id ?? data.city_id;
                if (cityId === 'HONG KONG' || cityId === 'BUSAN (EX PUSAN)' || cityId === 'SINGAPORE' || 
                    cityId === 'PORT KELANG' || cityId === 'WESTPORT/PORT KLANG'|| cityId === "JEBEL ALI" || cityId ==="KOBE" || cityId === "TOKYO") {
                    transId = cityId;	
                } else {
                    transId = data.etd_city_con_id ?? '';
                }

                const con_ves = data.connecting ?? '';
                const con_voy = data.voy_con ?? '';
                let con_ves_voy = '';
                if (con_ves) {
                    con_ves_voy = con_ves + ' ' + con_voy;
                }
                const closingDateFormatted = normalizeDateOnly(data.stf_cls);
                const stuffingDate = normalizeDateOnly(data.stuffing_date);

                const etdDate      = normalizeDateOnly(data.etd_jkt);
                const etaDate      = normalizeDateOnly(data.eta);
                
                $('#stuffing_date_d').val(stuffingDate);
                $('#stuffing_date_d_view').text(stuffingDate || '-');

                $('#closing').val(closingDateFormatted);
                $('#closing_view').text(closingDateFormatted || '-');

                $('#etd_pol').val(etdDate);
                $('#etd_pol_view').text(etdDate || '-');

                $('#eta_des').val(etaDate);
                $('#eta_des_view').text(etaDate || '-');

                let route_d; 
                if (cityId === 'HONG KONG' || cityId === 'BUSAN' || cityId === 'SINGAPORE' || 
                    cityId === 'PORT KELANG' || cityId === 'WESTPORT/PORT KLANG') {
                    route_d = "TRANSSHIPMENT";	
                } else if (data.route_type === "VIA"){
                    route_d = "VIA";
                } else{
                    route_d = "DIRECT";
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

                setSelect2Value('#pol_job', portLoadingValue);
                setSelect2Value('#pot_job', transId);
                setSelect2Value('#pod_job', cityId);

                $('#pol_job, #pot_job, #pod_job').prop('disabled', true);

                $('#vessel_d').val(data.vessel);
                $('#voyage_d').val(data.voy);
                $('#route_d_data').val(data.route_type ?? '');
                $('#schedule_id_data').val(data.id ?? '');
                
                let locationValue = data.stuffing_location ?? '';
                if (locationValue) {
                    if ($('#warehouse_d').find("option[value='" + locationValue + "']").length) {
                        $('#warehouse_d').val(locationValue).trigger('change');
                    } else {
                        let newOption = new Option(locationValue, locationValue, true, true);
                        $('#warehouse_d').append(newOption).trigger('change');
                    }
                } else {
                    $('#warehouse_d').val(null).trigger('change');
                }
            } else {
                console.warn("Data kosong. Tidak ada form yang diisi.");
            }
        }
    });
});

function renderFormFields() {
    const selectedValue = document.getElementById("TypeOfTransaction").value;
    const toolbarSearchDiv = document.querySelector(".DynamicForm");
    const scheduleSection = document.getElementById("scheduleSelectionSection");
    
    const disabledInputs = [
        'scac_d', 'pol_job', 'pot_job', 'pod_job', 
        'route_d', 'vessel_d', 'voyage_d', 'warehouse_d'
    ];

    if (toolbarSearchDiv && (selectedValue === "LCL" || selectedValue === "COLOAD")) {
        toolbarSearchDiv.style.display = "block";
        
        if (selectedValue === "COLOAD") {
            if (scheduleSection) scheduleSection.style.display = "none";
            disabledInputs.forEach(id => {
                $('#' + id).prop('disabled', false);
            });
            toggleDateEditable(true);
        } else {
            if (scheduleSection) scheduleSection.style.display = "block";
            disabledInputs.forEach(id => {
                $('#' + id).prop('disabled', true);
            });
            toggleDateEditable(false);
        }
    }
}

function toggleDateEditable(isEditable) {
    const dateFields = ['stuffing_date_d', 'closing', 'etd_pol', 'eta_des'];
    
    dateFields.forEach(id => {
        let inputEl = document.getElementById(id);
        let viewEl = document.getElementById(id + '_view');
        
        if (inputEl && viewEl) {
            if (isEditable) {
                viewEl.style.display = "none";
                inputEl.type = (id === 'closing') ? "datetime-local" : "date";
                inputEl.classList.add('form-control');
                inputEl.style.display = "block";
            } else {
                viewEl.style.display = "flex";
                inputEl.type = "hidden";
                inputEl.classList.remove('form-control');
                inputEl.style.display = "none";
            }
        }
    });
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
        'A':10,'B':12,'C':13,'D':14,'E':15,'F':16,'G':17,'H':18,'I':19,
        'J':20,'K':21,'L':23,'M':24,'N':25,'O':26,'P':27,'Q':28,'R':29,
        'S':30,'T':31,'U':32,'V':34,'W':35,'X':36,'Y':37,'Z':38
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
    return dateString.replace(/-/g, '');
}

$('#btnSaveJob').off('click').on('click', function() {
    let typeOfTrans = $('#TypeOfTransaction').val();
    
    if (typeOfTrans === 'LCL') {
        if (!$('#etd_pol').val() || !$('#eta_des').val() || !$('#pol_job').val() || !$('#pod_job').val()) {
            Swal.fire({
                icon: 'warning',
                title: 'Data Belum Lengkap!',
                text: 'Untuk transaksi LCL, Schedule tidak boleh kosong.',
                confirmButtonColor: '#0ea5e9'
            });
            return false;
        }
    }

    let $btn = $(this);
    let originalText = $btn.text();
    $btn.text('Generating Code...').prop('disabled', true);

    let rawDate = $('#dateInput').val();
    let dateArr = rawDate.split('-');
    let tanggalSpnum = dateArr[2] + '/' + dateArr[1] + '/' + dateArr[0]; 

    $.ajax({
        url: '<?= base_url("afg_c_stuffing_plan/spnum") ?>',
        type: 'POST',
        data: { tipe: typeOfTrans, bulan: dateArr[1], tahun: dateArr[0], tanggal: tanggalSpnum },
        dataType: 'json',
        success: function(responseSpnum) {
            let kodeRef = responseSpnum.kode_ref || responseSpnum.refno;
            let idSp    = responseSpnum.sp_id || responseSpnum.id; 

            $btn.text('Saving Data...');

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
                sp_id: idSp,                  
                kode_ref: kodeRef, 
                pesan: 'Data berhasil ditambahin', 
                
                tanggal_job: formatYYYYMMDD(rawDate),
                stuffing_date: formatYYYYMMDD($('#stuffing_date_d').val()), 
                closing_date_time: formatYYYYMMDD(closingDateTime),
                etd_jkt: formatYYYYMMDD($('#etd_pol').val()),
                eta: formatYYYYMMDD($('#eta_des').val()),
                
                type_of_trans: typeOfTrans, 
                warehouse: $('#warehouse_d').val(),
                carrier: $('#liner_d').val() || $('#schedule_carrier').val(),
                scac: $('#scac_d').val() || $('#schedule_scac').val(),
                mother_bl: $('#mbl_d').val() || $('#schedule_booking_number').val(),
                vessel: $('#vessel_d').val(),
                voyage: $('#voyage_d').val(),
                closing_time: closingTime,
                port_loading: $('#pol_job').val(),
                transhipment_port: $('#pot_job').val(),
                destination: $('#pod_job').val(),
                cont_seal_no: $('#container_d').val() + ($('#seal_d').val() ? ' / ' + $('#seal_d').val() : ''),
                type_cont_qty: $('#jml_container_d').val() || $('#schedule_container_qty').val(),
                type_cont_size: $('#type_container_d').val() || $('#schedule_container_type').val(),
                agen_penagihan: $('#agent_d').val(),
                kurs: $('#kurs_d').val(),
                shipment_type_multi: 'Consol', 
                atd: '', ata: '', isBatal: 0, kurs_selling: $('#kurs_jualan').val(),
				schedule_id: $('#schedule_id_data').val(), 
                route_type: $('#route_d_data').val(),
                schedule_booking_number: $('#schedule_booking_number').val(),
                schedule_container_type: $('#schedule_container_type').val(),
                schedule_container_qty: $('#schedule_container_qty').val(),
                schedule_container_type_raw: $('#schedule_container_type_raw').val(),
                cargo_type: $('#cargo_type_d').val() || $('#schedule_cargo_type').val() || 'GENERAL',
                is_dg: $('#is_dg_d').val() || $('#schedule_is_dg').val() || 0,
                dg_details: $('#dg_details_d').val() || $('#schedule_dg_details').val(),
            };

            $.ajax({
                async: true,
                url: '<?= base_url("afg_c_stuffing_plan/submitStuffingPlan") ?>', 
                type: 'POST',
                data: postData,
                success: function(responseSubmit) {
                    try { $('#FORM_NEW_JOB').modal('hide'); } catch(e) {}
                    try { $('#FORM_NEW_JOB').dialog('close'); } catch(e) {}

                    $('#tabel_stuffing_plan').jqGrid("setGridParam", { datatype: 'json' }).trigger("reloadGrid");

                    success_saving('edit');
					getStuffingPlan('create');

                    $btn.text(originalText).prop('disabled', false);
                },
                error: function(XMLHttpRequest) {
                    $btn.text(originalText).prop('disabled', false);

                    $("#Insert-Form").dialog({
                        title: "ERROR SUBMIT STUFFINGPLAN",
                        buttons: {
                            "OK": function() {
                                $(this).dialog("close");
                                getStuffingPlan('pilih');
                                if (typeof enabled_top_button === "function") {
                                    enabled_top_button();
                                }
                            }
                        },
                        close: function() {}
                    });
                    $('#Insert-Form').html(XMLHttpRequest.responseText);
                }
            });
        },
        error: function(xhr, status, error) {
            console.error("Error spnum:", xhr.responseText);
            Swal.fire({
                icon: 'error',
                title: 'Gagal Generate Kode',
                text: 'Terjadi kesalahan saat memanggil fungsi spnum().',
                confirmButtonColor: '#d33'
            });
            $btn.text(originalText).prop('disabled', false);
        }
    });
});
</script>
</body>
</html>