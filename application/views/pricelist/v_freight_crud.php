<script>
    var BASE_URL = '<?= site_url() ?>';
</script>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
    /* 1. Tabel Form Utama */
    .modern-form-table { border-collapse: collapse; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 13px; color: #333; width: 100%; }
    .modern-form-table td { padding: 8px 12px; vertical-align: middle; }
    .modern-form-table td:nth-child(odd) { font-weight: 600; color: #555; background-color: #f9f9f9; border-right: 3px solid #fff; width: 15%; }

    /* 2. Input & Select Standar */
    .modern-input { width: 100%; height: 34px !important; border: 1px solid #ccc; padding: 6px 10px !important; box-shadow: inset 0 1px 2px rgba(0,0,0,0.05); transition: border-color 0.2s; box-sizing: border-box !important; border-radius: 4px; font-family: inherit; font-size: 13px; color: #333; background-color: #fff; }
    textarea.modern-input { height: 60px; resize: none; }
    .modern-input:focus { border-color: #00b09b; outline: none; box-shadow: 0 0 5px rgba(0, 176, 155, 0.3); }

    /* 3. Modifikasi Tampilan Select2 */
    .select2-container--default .select2-selection--single { height: 34px !important; border: 1px solid #ccc !important; border-radius: 4px !important; box-shadow: inset 0 1px 2px rgba(0,0,0,0.05); display: flex; align-items: center; background-color: #fff; }
    .select2-container--default .select2-selection--single .select2-selection__rendered { padding-left: 10px !important; font-size: 13px; color: #333 !important; line-height: normal !important; }
    .select2-container--default .select2-selection--single .select2-selection__arrow { height: 32px !important; }
    .select2-container--default.select2-container--open .select2-selection--single { border-color: #00b09b !important; }

    /* 4. Input Custom (Harga LCL Wrapper) */
    .price-wrapper { display: flex; align-items: center; background: #e8f4f8; border: 1px solid #bce0ed; border-radius: 4px; padding-left: 10px; height: 34px; width: 100%; box-sizing: border-box; overflow: hidden; }
    .price-wrapper span { font-weight: bold; color: #0b6285; }
    .price-wrapper input { border: none; background: transparent; padding: 6px 10px; width: 100%; height: 100%; outline: none; font-weight: bold; color: #0b6285; box-sizing: border-box; }
    .lcl-route-box { background:#fff; padding:10px; border:1px solid #bce0ed; border-radius:6px; min-width:140px; flex: 1; box-shadow:0 1px 3px rgba(0,0,0,0.05); }

    /* 5. Tabel Rincian FCL & LCL */
    .tbl_components { border-collapse: collapse; margin-top: 5px; width: 100%; }
    .tbl_components th { background: #2c3e50; color: #fff; padding: 10px; font-weight: 600; text-align: left; font-size: 13px; }
    .tbl_components td { padding: 8px; border-bottom: 1px solid #eee; }
    .tbl_components .modern-input { height: 32px; } 
    
    .btn-add-modern { background: #00b09b; color: white; border: none; padding: 6px 14px; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 12px; transition: 0.2s; }
    .btn-add-modern:hover { background: #008a79; box-shadow: 0 2px 4px rgba(0,0,0,0.2); }
    
    tr.jqgroup > td { background-color: #d1ecf1 !important; color: #0c5460 !important; font-size: 13px !important; border-bottom: 2px solid #bee5eb !important; }
    tr.jqgroup > td .ui-icon { filter: invert(20%) sepia(90%) saturate(1500%) hue-rotate(170deg) brightness(90%) contrast(90%); }
    
    .row-expiring-soon td { background-color: rgba(255, 193, 7, 0.15) !important; color: #ffc107 !important; }
    .row-expired td { background-color: rgba(220, 53, 69, 0.15) !important; color: #dc3545 !important; }

    /* Custom jQuery UI Tabs untuk Tema Modern */
    #tabs_freight { padding: 0; border: none; background: transparent; margin-top: 15px; }
    #tabs_freight .ui-tabs-nav { background: transparent; border: none; border-bottom: 2px solid #ddd; border-radius: 0; padding: 0; }
    #tabs_freight .ui-tabs-nav li { border: none; background: transparent; margin-bottom: -2px; transition: 0.2s; }
    #tabs_freight .ui-tabs-nav li a { color: #777; font-weight: bold; padding: 10px 20px; font-family: 'Segoe UI', sans-serif; font-size: 14px; }
    #tabs_freight .ui-tabs-nav li.ui-tabs-active { border-bottom: 3px solid #00b09b; }
    #tabs_freight .ui-tabs-nav li.ui-tabs-active a { color: #00b09b; }
    #tabs_freight .ui-tabs-panel { padding: 15px 0 0 0; }
	
	#grid-wrapper { position: relative; }

	/* pastikan parent grid-wrapper { position: relative; } */
	#info-warna {
	  position: absolute;
	  left: 12px;               /* sesuaikan jarak dari kiri container */
	  bottom: 12px;             /* sesuaikan jarak dari bawah container */
	  z-index: 999;
	  background: rgba(0,0,0,0.04);
	  border: 1px solid #ddd;
	  border-radius: 6px;
	  padding: 6px 12px;
	  box-shadow: 0 6px 18px rgba(0,0,0,0.06);
	  max-width: calc(100% - 24px);
	  overflow: hidden;
	}

	/* Buat satu baris memanjang */
	.info-inner {
	  display: flex;
	  flex-wrap: nowrap;        /* pastikan 1 baris */
	  gap: 18px;
	  align-items: center;
	  white-space: nowrap;      /* mencegah wrap */
	  overflow: hidden;
	  text-overflow: ellipsis;  /* potong teks jika terlalu panjang */
	  font-family: "Segoe UI", sans-serif;
	  font-size: 12px;
	  color: #333;
	  font-weight: 600;
	}

	/* styling elemen */
	.info-title { display:flex; align-items:center; gap:8px; color:#555; font-weight:700; }
	.info-item { display:flex; align-items:center; gap:8px; color:inherit; font-weight:600; }
	.swatch { display:inline-block; width:14px; height:14px; border-radius:3px; border:1px solid rgba(0,0,0,0.06); flex: 0 0 auto; }
	.swatch-warning { background-color: rgba(255,193,7,0.2); border-color:#ffc107; }
	.swatch-expired { background-color: rgba(220,53,69,0.2); border-color:#dc3545; }

	/* label teks agar tidak memecah baris, tapi tetap terpotong bila sempit */
	.label { display:inline-block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width: 220px; }

	/* responsif: kecilkan gap dan padding pada layar sempit */
	@media (max-width: 640px) {
	  #info-warna { left: 8px; bottom: 8px; padding: 6px 8px; }
	  .info-inner { gap: 10px; font-size: 11px; }
	  .label { max-width: 120px; }
	}

	/* styling ikon di dalam input */
	#global-search { font-family: "Segoe UI", sans-serif; }
	.search-icon {
	  position: absolute;
	  left: 10px;
	  top: 50%;
	  transform: translateY(-50%);
	  color: #666;
	  font-size: 14px;
	  pointer-events: none; /* ikon tidak menghalangi klik input */
	}
	#global_search_input {
	  border: 1px solid #ccc;
	  border-radius: 4px;
	  height: 34px;
	  box-sizing: border-box;
	}
	#global_search_input:focus { outline: none; border-color: #7aa7d9; box-shadow: 0 0 0 3px rgba(122,167,217,0.12); }
</style>

<div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 10px; flex-wrap: wrap; gap: 10px;">
    <div>
        <button id="btn_add" class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-icon-primary"><span class="ui-button-icon-primary ui-icon ui-icon-plus"></span><span class="ui-button-text">Tambah Data</span></button>
        <button id="btn_edit" class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-icon-primary"><span class="ui-button-icon-primary ui-icon ui-icon-pencil"></span><span class="ui-button-text">Edit</span></button>
        <button id="btn_delete" class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-icon-primary"><span class="ui-button-icon-primary ui-icon ui-icon-trash"></span><span class="ui-button-text">Hapus</span></button>
    </div>
	
	<div style="display:flex; gap:15px; align-items:center;">
        <div id="view_toggle" style="display:inline-flex; border: 1px solid #ccc; border-radius: 6px; overflow: hidden; height:34px;">
            <button type="button" class="view-btn active" data-type="FCL" style="padding: 0 15px; border:none; background:#00b09b; color:#fff; font-weight:bold; cursor:pointer; outline:none;"><i class="fa-solid fa-box"></i> FCL List</button>
            <button type="button" class="view-btn" data-type="LCL" style="padding: 0 15px; border:none; background:#f9f9f9; color:#555; font-weight:bold; cursor:pointer; outline:none;"><i class="fa-solid fa-boxes-stacked"></i> LCL List</button>
        </div>

        <div id="global-search" style="position: relative; display: inline-block;">
            <i class="fa-solid fa-magnifying-glass search-icon" aria-hidden="true"></i>
            <input type="text" id="global_search_input" placeholder="Cari Destination .." style="width:320px; padding:6px 10px 6px 34px;">
        </div>
	</div>
</div>

<table id="grid_freight"></table>
<div id="pager_freight"></div>


<div id="dialog_form_freight" title="Form Freight Pricing" style="display:none;">
    <form id="form_freight">
        <input type="hidden" name="id" id="id">
        <input type="hidden" name="rate_type" id="rate_type"> <div style="border: 1px solid #ddd; border-radius: 6px; overflow: hidden;">
            <table width="100%" class="modern-form-table">
                <tr>
                    <td width="15%">Origin (POL)</td>
                    <td width="35%"><select name="origin_id" id="origin_id" style="width: 100%;"></select></td>
                    <td width="15%">Destinasi (POD)</td>
                    <td width="35%"><select name="destination_id" id="destination_id" style="width: 100%;"></select></td>
                </tr>
                <tr>
                    <td>Liner Name</td>
                    <td><select name="liner_name" id="liner_name" style="width: 100%;"></select></td>
                    <td>Cargo Type</td>
                    <td>
                        <select name="cargo_type" id="cargo_type" class="modern-input" style="width: 100%;">
                            <option value="GENCO">General Cargo</option>
                            <option value="DG">Dangerous Goods</option>
                            <option value="REEFER">Reefer</option>
                            <option value="BREAKBULK">Breakbulk</option>
                            <option value="PERISHABLE">Perishable goods</option>
                            <option value="PERSONAL">Personal Effect</option>
                        </select>
                    </td>
                </tr>
            </table>
        </div>

        <div id="wrapper_fcl" style="display:none; margin-top: 15px;">
            <h4 style="color:#2c3e50; margin-bottom:12px; font-weight:bold; border-bottom: 2px solid #ddd; padding-bottom: 6px;">
                <i class="fa-solid fa-box text-info"></i> FCL PRICING DATA
            </h4>
            
            <div style="border: 1px solid #ddd; border-radius: 6px; overflow: hidden; margin-bottom: 15px;">
                <table width="100%" class="modern-form-table">
                    <tr>
                        <td width="15%">Validity From</td>
                        <td width="35%"><input type="date" name="fcl_validity_from" id="fcl_validity_from" class="modern-input ui-corner-all"></td>
                        <td width="15%">Validity To</td>
                        <td width="35%"><input type="date" name="fcl_validity_to" id="fcl_validity_to" class="modern-input ui-corner-all"></td>
                    </tr>
                    <tr>
                        <td>Free Time</td>
                        <td><input type="text" name="fcl_free_time" id="fcl_free_time" class="modern-input" placeholder="e.g. 14 Days"></td>
                        <td>Transit Time</td>
                        <td><input type="text" name="fcl_transit_time" id="fcl_transit_time" class="modern-input" placeholder="e.g. 3 Days (JKT - KINABALU)"></td>
                    </tr>
                    <tr>
                        <td>Include Rate</td>
                        <td><textarea name="fcl_include_rate" id="fcl_include_rate" class="modern-input ui-corner-all"></textarea></td>
                        <td>Exclude Rate</td>
                        <td><textarea name="fcl_exclude_rate" id="fcl_exclude_rate" class="modern-input ui-corner-all"></textarea></td>
                    </tr>
                    <tr>
                        <td>Remarks</td>
                        <td colspan="3"><input type="text" name="fcl_remarks" id="fcl_remarks" class="modern-input" placeholder="Catatan khusus FCL..."></td>
                    </tr>
                </table>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <b style="color:#34495e; font-size:14px;"><i class="ui-icon ui-icon-calculator" style="display:inline-block; vertical-align:middle;"></i> Rincian Biaya FCL (20', 40', 40'HC)</b> 
                <button type="button" id="btn_add_fcl_row" class="btn-add-modern">+ Tambah FCL Charge</button>
            </div>
            
            <table id="tbl_components_fcl" class="tbl_components">
                <thead>
                    <tr>
                        <th width="16%">Grup (FCL)</th>
                        <th width="24%">Service Name</th>
                        <th width="14%">Unit / Basis</th> <th width="12%" style="text-align:center;">20'</th>
                        <th width="12%" style="text-align:center;">40'</th>
                        <th width="12%" style="text-align:center;">40'HC</th>
                        <th width="10%" style="text-align:center;">Aksi</th>
                    </tr>
                </thead>
                <tbody id="tbody_fcl"></tbody>
            </table>
        </div>

        <div id="wrapper_lcl" style="display:none; margin-top: 15px;">
            <h4 style="color:#e67e22; margin-bottom:12px; font-weight:bold; border-bottom: 2px solid #ddd; padding-bottom: 6px;">
                <i class="fa-solid fa-boxes-stacked text-warning"></i> LCL PRICING DATA
            </h4>

            <div style="border: 1px solid #ddd; border-radius: 6px; overflow: hidden; margin-bottom: 15px;">
                <table width="100%" class="modern-form-table">
                    <tr>
                        <td width="15%" style="color:#e67e22;">LCL Validity From</td>
                        <td width="35%"><input type="date" name="lcl_validity_from" id="lcl_validity_from" class="modern-input ui-corner-all"></td>
                        <td width="15%" style="color:#e67e22;">LCL Validity To</td>
                        <td width="35%"><input type="date" name="lcl_validity_to" id="lcl_validity_to" class="modern-input ui-corner-all"></td>
                    </tr>
                </table>
            </div>

            <div style="border: 1px solid #ddd; border-radius: 6px; overflow: hidden; margin-bottom: 15px;">
                <table width="100%" class="modern-form-table">
                    <tr>
                        <td width="15%">LCL Routing</td>
                        <td colspan="3">
                            <div style="display: flex; flex-wrap: wrap; gap: 10px; background: #f0f7f9; padding: 10px; border-radius: 8px;">
                                <div class="lcl-route-box">
                                    <label style="font-size: 11px; font-weight:bold; display: block; margin-bottom:8px;"><input type="checkbox" class="chk-priority" name="lcl_priority[]" value="direct_consol"> Direct Consol</label>
                                    <div class="price-wrapper" style="margin-bottom:6px;"><input type="number" step="0.01" name="lcl[direct_consol]" id="lcl_direct" placeholder="Price"></div>
                                    <input type="text" name="lcl_transit[direct_consol]" id="lcl_transit_direct" class="modern-input" style="height:28px!important; font-size:11px;" placeholder="Transit Time">
                                </div>
                                <div class="lcl-route-box">
                                    <label style="font-size: 11px; font-weight:bold; display: block; margin-bottom:8px;"><input type="checkbox" class="chk-priority" name="lcl_priority[]" value="via_sin"> Via SIN</label>
                                    <div class="price-wrapper" style="margin-bottom:6px;"><input type="number" step="0.01" name="lcl[via_sin]" id="lcl_sin" placeholder="Price"></div>
                                    <input type="text" name="lcl_transit[via_sin]" id="lcl_transit_sin" class="modern-input" style="height:28px!important; font-size:11px;" placeholder="Transit Time">
                                </div>
                                <div class="lcl-route-box">
                                    <label style="font-size: 11px; font-weight:bold; display: block; margin-bottom:8px;"><input type="checkbox" class="chk-priority" name="lcl_priority[]" value="via_busan"> Via Busan</label>
                                    <div class="price-wrapper" style="margin-bottom:6px;"><input type="number" step="0.01" name="lcl[via_busan]" id="lcl_busan" placeholder="Price"></div>
                                    <input type="text" name="lcl_transit[via_busan]" id="lcl_transit_busan" class="modern-input" style="height:28px!important; font-size:11px;" placeholder="Transit Time">
                                </div>
                                <div class="lcl-route-box">
                                    <label style="font-size: 11px; font-weight:bold; display: block; margin-bottom:8px;"><input type="checkbox" class="chk-priority" name="lcl_priority[]" value="via_hkg"> Via HKG</label>
                                    <div class="price-wrapper" style="margin-bottom:6px;"><input type="number" step="0.01" name="lcl[via_hkg]" id="lcl_hkg" placeholder="Price"></div>
                                    <input type="text" name="lcl_transit[via_hkg]" id="lcl_transit_hkg" class="modern-input" style="height:28px!important; font-size:11px;" placeholder="Transit Time">
                                </div>
                                <div class="lcl-route-box">
                                    <label style="font-size: 11px; font-weight:bold; display: block; margin-bottom:8px;"><input type="checkbox" class="chk-priority" name="lcl_priority[]" value="via_nhava"> Via Nhava</label>
                                    <div class="price-wrapper" style="margin-bottom:6px;"><input type="number" step="0.01" name="lcl[via_nhava]" id="lcl_nhava" placeholder="Price"></div>
                                    <input type="text" name="lcl_transit[via_nhava]" id="lcl_transit_nhava" class="modern-input" style="height:28px!important; font-size:11px;" placeholder="Transit Time">
                                </div>
                                <div class="lcl-route-box">
                                    <label style="font-size: 11px; font-weight:bold; display: block; margin-bottom:8px;"><input type="checkbox" class="chk-priority" name="lcl_priority[]" value="via_colombo"> Via Colombo</label>
                                    <div class="price-wrapper" style="margin-bottom:6px;"><input type="number" step="0.01" name="lcl[via_colombo]" id="lcl_colombo" placeholder="Price"></div>
                                    <input type="text" name="lcl_transit[via_colombo]" id="lcl_transit_colombo" class="modern-input" style="height:28px!important; font-size:11px;" placeholder="Transit Time">
                                </div>
                                <div class="lcl-route-box">
                                    <label style="font-size: 11px; font-weight:bold; display: block; margin-bottom:8px;"><input type="checkbox" class="chk-priority" name="lcl_priority[]" value="via_jebel_ali"> Via Jebel Ali</label>
                                    <div class="price-wrapper" style="margin-bottom:6px;"><input type="number" step="0.01" name="lcl[via_jebel_ali]" id="lcl_jebel_ali" placeholder="Price"></div>
                                    <input type="text" name="lcl_transit[via_jebel_ali]" id="lcl_transit_jebel_ali" class="modern-input" style="height:28px!important; font-size:11px;" placeholder="Transit Time">
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>Include Rate (LCL)</td>
                        <td><textarea name="lcl_include_rate" id="lcl_include_rate" class="modern-input ui-corner-all"></textarea></td>
                        <td>Exclude Rate (LCL)</td>
                        <td><textarea name="lcl_exclude_rate" id="lcl_exclude_rate" class="modern-input ui-corner-all"></textarea></td>
                    </tr>
                    <tr>
                        <td>Remarks (LCL)</td>
                        <td colspan="3"><input type="text" name="lcl_remarks" id="lcl_remarks" class="modern-input" placeholder="Catatan khusus LCL..."></td>
                    </tr>
                </table>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <b style="color:#e67e22; font-size:14px;"><i class="ui-icon ui-icon-calculator" style="display:inline-block; vertical-align:middle;"></i> Rincian Biaya LCL (Origin, Freight, Dest)</b> 
                <button type="button" id="btn_add_lcl_row" class="btn-add-modern" style="background:#e67e22;">+ Tambah LCL Charge</button>
            </div>
            
            <table id="tbl_components_lcl" class="tbl_components">
                <thead>
                    <tr style="background:#d35400;">
                        <th width="18%">Grup (LCL)</th>
                        <th width="32%">Service Name</th>
                        <th width="20%">Unit / Basis</th> <th width="20%" style="text-align:center;">Price</th>
                        <th width="10%" style="text-align:center;">Aksi</th>
                    </tr>
                </thead>
                <tbody id="tbody_lcl"></tbody>
            </table>
        </div>
    </form>
</div>
 <!-- INFO WARNA -->
<!-- letakkan ini di dalam container grid (grid-wrapper) -->
<div id="info-warna" class="info-warna">
  <div class="info-inner">
    <span class="info-title"><i class="fa-solid fa-circle-info" aria-hidden="true"></i> INFO WARNA:</span>
    <div class="info-item"><span class="swatch swatch-warning"></span><span class="label">Expiring (≤ 5 Hari)</span></div>
    <div class="info-item"><span class="swatch swatch-expired"></span><span class="label">Expired</span></div>
  </div>
</div>


<script>
 
	

$(document).ready(function() {
	
	 if ($("#tabs_freight").length) {
            $("#tabs_freight").tabs();
        }

});

    // PERHATIAN: 
    // Di file JavaScript Anda yang me-render Baris Komponen (btn_add_row sebelumnya), 
    // Anda harus memecahnya menjadi dua fungsi:
    // 1. Event untuk btn_add_fcl_row (menghasilkan select group FCL dan 3 kolom harga)
    //    -> Option Group: Freight Components, Inland Moda, Subject To
    // 2. Event untuk btn_add_lcl_row (menghasilkan select group LCL dan 1 kolom harga)
    //    -> Option Group: Origin, Freight, Destination
</script>