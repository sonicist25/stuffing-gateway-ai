<div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 10px; gap: 10px;">
    <div>
        <button id="btn_add" class="btn btn-sm btn-primary"><i class="fa fa-plus"></i> Tambah Data</button>
        <button id="btn_edit" class="btn btn-sm btn-warning"><i class="fa fa-pencil"></i> Edit</button>
        <button id="btn_delete" class="btn btn-sm btn-danger"><i class="fa fa-trash"></i> Hapus</button>
    </div>
    <div>
        <select id="filter_type" class="form-control" style="display:inline-block; width:150px;">
            <option value="">Semua Tipe</option>
            <option value="VIA SIN">Via SIN</option>
            <option value="VIA HKG">Via HKG</option>
        </select>
        <input type="text" id="global_search" placeholder="Cari Tujuan/Negara.." class="form-control" style="display:inline-block; width:200px;">
    </div>
</div>

<table id="grid_ts"></table>
<div id="pager_ts"></div>

<!-- MODAL FORM -->
<style>
    /* CSS Grid 2 Kolom Mutlak untuk Menjamin Lebar Kotak 100% Sama Rata */
    .ts-grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-bottom: 8px; }
    
    .ts-form-col { display: flex; flex-direction: column; width: 100%; }
    .ts-form-col label { font-size: 10px; font-weight: bold; margin-bottom: 3px; color: #555; text-transform: uppercase; letter-spacing: 0.3px; }
    
    /* Standarisasi Tinggi, Padding, dan Border Seluruh Input/Select */
    .ts-form-control { padding: 6px 10px; border: 1px solid #ccc; border-radius: 5px; font-size: 12px; width: 100% !important; box-sizing: border-box; height: 36px !important; }
    .ts-form-control:focus { border-color: #3498db; outline: none; box-shadow: 0 0 4px rgba(52, 152, 219, 0.3); }
    .ts-form-control:read-only { background: #f1f2f6; color: #7f8c8d; cursor: not-allowed; }
    
    .ts-full-width { grid-column: span 2; }
    
    .ts-section-title { font-size: 11px; font-weight: bold; color: #e67e22; border-bottom: 1px solid #f1f2f6; margin: 14px 0 8px; padding-bottom: 3px; text-transform: uppercase; }
    .ts-section-title:first-child { margin-top: 0; }

    /* Penyesuaian Select2 agar ukurannya seragam dengan input biasa */
    .select2-container { width: 100% !important; }
    .select2-container .select2-selection--single { height: 36px !important; border: 1px solid #ccc !important; border-radius: 5px !important; display: flex; align-items: center; }
    .select2-container--default .select2-selection--single .select2-selection__arrow { height: 34px !important; }
</style>

<div id="dialog_form_ts" title="Form Transhipment Rates" style="display:none;">
    <form id="form_ts">
        <input type="hidden" name="id" id="id">
        
        <!-- SUB 1: ROUTE -->
        <div class="ts-section-title">Route</div>
        <div class="ts-grid-2">
            <div class="ts-form-col">
                <label>Tipe (Hub)</label>
                <select name="ts_type" id="ts_type" class="ts-form-control">
                    <option value="VIA SIN">VIA SINGAPORE (SIN)</option>
                    <option value="VIA HKG">VIA HONG KONG (HKG)</option>
                </select>
            </div>
            <div></div>
        </div>
        
        <!-- Baris 1: Via Port & Negara Via Port -->
        <div class="ts-grid-2">
            <div class="ts-form-col">
                <label>Via Port (Lanjutan)</label>
                <select name="via_port" id="via_port" class="ts-form-control"></select>
            </div>
            <div class="ts-form-col" id="wrap_via_country">
                <label>Negara Via Port</label>
                <input type="text" id="via_country" class="ts-form-control" placeholder="Auto" readonly>
            </div>
        </div>

        <div class="ts-grid-2">
            <div class="ts-form-col">
                <label>Tujuan (Destination)</label>
                <select name="destination" id="destination" class="ts-form-control"></select>
            </div>
            <div class="ts-form-col">
                <label>Negara Tujuan</label>
                <input type="text" name="country" id="country" class="ts-form-control" placeholder="Auto" readonly>
            </div>
        </div>

        <!-- Baris Khusus 1st TT (Muncul KHUSUS jika Via Port kosong / Direct) -->
        <div class="ts-grid-2" id="wrap_tt_1st_pod_row" style="display: none;">
            <div class="ts-form-col">
                <label>1st Transit Time (Hari)</label>
                <input type="number" name="transit_time_1st" id="transit_time_1st_pod" class="ts-form-control" placeholder="Hari">
            </div>
            <div></div>
        </div>
		<!-- Baris 2: 1st TT & 2nd TT (Muncul jika Via Port ada / Bukan Direct) -->
        <div class="ts-grid-2" id="wrap_trans_time_row" style="display: none;">
            <div class="ts-form-col" id="wrap_tt_1st">
                <label>1st Transit Time (Hari)</label>
                <input type="number" name="transit_time_1st" id="transit_time_1st_trans" class="ts-form-control" placeholder="Hari">
            </div>
            <div class="ts-form-col" id="wrap_tt_2nd">
                <label>2nd Transit Time (Hari)</label>
                <input type="number" name="transit_time_2nd" id="transit_time_2nd" class="ts-form-control" placeholder="Hari">
            </div>
        </div>

        <!-- SUB 4: TERM OF SERVICE (Total 6 elemen genap: 3 baris x 2 kolom) -->
        <div class="ts-section-title">Term of Service</div>
        <div class="ts-grid-2">
            <div class="ts-form-col">
                <label>Tarif / W/M ($)</label>
                <input type="number" step="0.01" name="freight_wm" id="freight_wm" class="ts-form-control" placeholder="0.00">
            </div>
            <div class="ts-form-col">
                <label>Minimal (CBM)</label>
                <input type="number" step="0.1" name="min_cbm" id="min_cbm" class="ts-form-control" placeholder="1">
            </div>
            <div class="ts-form-col">
                <label>Frequency</label>
                <input type="text" name="frequency" id="frequency" class="ts-form-control" placeholder="WEEKLY">
            </div>
            <div class="ts-form-col">
                <label>Fumigation</label>
                <select name="fumigation" id="fumigation" class="ts-form-control">
                    <option value="-">-</option>
                    <option value="Yes">Yes</option>
                    <option value="No">No</option>
                </select>
            </div>
            <div class="ts-form-col ts-full-width">
                <label>Remarks / Notes</label>
                <textarea name="remarks" id="remarks" class="ts-form-control" style="height: auto;" rows="1" placeholder="Catatan tambahan..."></textarea>
            </div>
        </div>
        
        <!-- SUB 5: SURCHARGES & DG (Total 6 elemen genap: 3 baris x 2 kolom) -->
        <div class="ts-section-title">Surcharges & DG (US DOllar)</div>
        <div class="ts-grid-2">
            <div class="ts-form-col">
                <label>GRI</label>
                <input type="number" step="0.01" name="surcharge_gri" id="surcharge_gri" class="ts-form-control" placeholder="">
            </div>
            <div class="ts-form-col">
                <label>DDC</label>
                <input type="number" step="0.01" name="surcharge_ddc" id="surcharge_ddc" class="ts-form-control" placeholder="">
            </div>
            <div class="ts-form-col">
                <label>AMS/AFR</label>
                <input type="text" name="surcharge_ams" id="surcharge_ams" class="ts-form-control" placeholder="">
            </div>
            <div class="ts-form-col">
                <label>DG (Group 3)</label>
                <input type="text" name="dg_class3" id="dg_class3" class="ts-form-control" placeholder="">
            </div>
            <div class="ts-form-col">
                <label>DG (Group 2)</label>
                <input type="text" name="dg_class2" id="dg_class2" class="ts-form-control" placeholder="">
            </div>
            <div></div>
        </div>
    </form>
</div>
<script type='text/javascript'>
var BASE_URL = "<?php echo base_url(); ?>";
</script>