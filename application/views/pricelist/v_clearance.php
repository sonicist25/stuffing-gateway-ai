<!-- AREA TOMBOL AKSI & JQGRID -->
<div class="row mb-3">
    <div class="col-md-12">
        <button type="button" class="btn btn-sm btn-primary" id="btn_add">
            <i class="fa fa-plus"></i> Tambah Tarif
        </button>
        <button type="button" class="btn btn-sm btn-warning" id="btn_edit">
            <i class="fa fa-edit"></i> Edit
        </button>
        <button type="button" class="btn btn-sm btn-danger" id="btn_delete">
            <i class="fa fa-trash"></i> Hapus
        </button>
    </div>
</div>

<div class="table-responsive">
    <table id="grid_clearance"></table>
    <div id="pager_clearance"></div>
</div>

<!-- ======================================================= -->
<!-- AREA MODAL FORM CLEARANCE TARIFF                        -->
<!-- ======================================================= -->
<style>
    /* Standarisasi Layout Grid 2 Kolom */
    .ts-grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-bottom: 8px; }
    .ts-form-col { display: flex; flex-direction: column; width: 100%; }
    .ts-form-col label { font-size: 10px; font-weight: bold; margin-bottom: 3px; color: #555; text-transform: uppercase; letter-spacing: 0.3px; }
    
    .ts-form-control { padding: 6px 10px; border: 1px solid #ccc; border-radius: 5px; font-size: 12px; width: 100% !important; box-sizing: border-box; height: 36px !important; }
    .ts-form-control:focus { border-color: #3498db; outline: none; box-shadow: 0 0 4px rgba(52, 152, 219, 0.3); }
    .ts-form-control:read-only { background: #f1f2f6; color: #7f8c8d; cursor: not-allowed; }
    
    .ts-full-width { grid-column: span 2; }
    .ts-section-title { font-size: 11px; font-weight: bold; color: #e67e22; border-bottom: 1px solid #f1f2f6; margin: 14px 0 8px; padding-bottom: 3px; text-transform: uppercase; }
</style>

<div id="dialog_form_clearance" title="Form Customs Clearance Rates" style="display:none;">
    <form id="form_clearance">
        <input type="hidden" name="id" id="clr_id">
        
        <div class="ts-section-title">Component Detail</div>
        <div class="ts-grid-2">
            <div class="ts-form-col">
                <label>Service Type</label>
                <select name="service_type" id="clr_service_type" class="ts-form-control">
					<option value="EXP_LCL">Export LCL</option>
					<option value="EXP_FCL">Export FCL</option>
					<option value="IMP_LCL">Import LCL</option>
					<option value="IMP_FCL">Import FCL</option>
					<option value="FASILITAS">Fasilitas Lainnya</option>
				</select>
            </div>
            <div class="ts-form-col">
                <label>At Cost / Receipt Based?</label>
                <select name="is_at_cost" id="clr_is_at_cost" class="ts-form-control">
                    <option value="N">Fixed Price (No)</option>
                    <option value="Y">As Per Receipt (Yes)</option>
                </select>
            </div>
        </div>

        <div class="ts-grid-2">
            <div class="ts-form-col ts-full-width">
                <label>Component Name</label>
                <input type="text" name="component_name" id="clr_component_name" class="ts-form-control" placeholder="e.g. Handling Fee, PIB Fee">
            </div>
        </div>

        <!-- Baris Fixed Price akan otomatis hilang jika "As Per Receipt" dipilih -->
        <div class="ts-grid-2" id="wrap_fixed_price">
            <div class="ts-form-col">
                <label>Price (IDR)</label>
                <input type="number" step="1" name="price_idr" id="clr_price_idr" class="ts-form-control" placeholder="0">
            </div>
            <div class="ts-form-col">
                <label>Unit Basis</label>
                <input type="text" name="unit_basis" id="clr_unit_basis" class="ts-form-control" placeholder="e.g. 0 s/d 5 cbm, HBL, Cntr">
            </div>
        </div>

        <div class="ts-grid-2">
            <div class="ts-form-col ts-full-width">
                <label>Remarks / Notes</label>
                <textarea name="remarks" id="clr_remarks" class="ts-form-control" style="height: auto;" rows="2" placeholder="e.g. As per receipt, for customs purpose only..."></textarea>
            </div>
        </div>
    </form>
</div>

<!-- ======================================================= -->
<!-- JAVASCRIPT LOGIC                                        -->
<!-- ======================================================= -->
<script>
var BASE_URL = "<?php echo base_url(); ?>";
$(document).ready(function() {
    // 1. Inisialisasi jqGrid
    $("#grid_clearance").jqGrid({
        url: BASE_URL + 'pricelist/clearance/get_grid_data',
        datatype: "json", 
        mtype: "POST", 
        autowidth: true, 
        height: 450, 
        rownumbers: true,
        jsonReader: { repeatitems: false }, // Mencegah error jqgrid pada format API Key-Value
        colNames: ['ID', 'Service', 'Component', 'Price (IDR)', 'Unit', 'At Cost?', 'Remarks'],
        colModel: [
            {name: 'id', hidden: true},
            { name: 'service_type', width: 100, align: 'center',
			  formatter: function(cell) { return (cell || '').toString().toUpperCase(); } },
			{ name: 'component_name', width: 200,
			  formatter: function(cell) { return (cell || '').toString().toUpperCase(); } },
			{ name: 'price_idr', width: 120, align: 'right',
			  formatter: 'currency', formatoptions: {decimalSeparator:",", thousandsSeparator: ".", decimalPlaces: 0} },
			{ name: 'unit_basis', width: 120, align: 'center',
			  formatter: function(cell) { return (cell || '').toString().toUpperCase(); } },
			{ name: 'is_at_cost', width: 80, align: 'center',
			  formatter: function(cell) { 
				  return (cell || '').toString().toUpperCase() === 'Y' 
					  ? '<span style="color:red;font-weight:bold;">YES</span>' 
					  : 'NO'; 
			  }},
			{ name: 'remarks', width: 250,
			  formatter: function(cell) { return (cell || '').toString().toUpperCase(); } }
        ],
        rowNum: 50, 
        rowList: [50, 100], 
        pager: '#pager_clearance', 
        sortname: 'service_type', 
        viewrecords: true
    });

    // 2. Setup jQuery UI Dialog Modal
    $("#dialog_form_clearance").dialog({
        autoOpen: false, 
        width: 600, 
        modal: true,
        buttons: {
            "Simpan": function() {
                $.post(BASE_URL + 'pricelist/clearance/save', $("#form_clearance").serialize(), function(res) {
                    $("#dialog_form_clearance").dialog("close");
                    $("#grid_clearance").trigger("reloadGrid");
                    Swal.fire('Sukses', 'Data berhasil disimpan', 'success');
                }, 'json').fail(function() {
                    Swal.fire('Error', 'Terjadi kesalahan saat menyimpan data', 'error');
                });
            },
            "Batal": function() { 
                $(this).dialog("close"); 
            }
        }
    });

    // 3. Animasi Dinamis "At Cost"
    // Menyembunyikan form Price dan Unit jika diset ke "As Per Receipt"
    $('#clr_is_at_cost').on('change', function() {
        if($(this).val() === 'Y') {
            $('#wrap_fixed_price').hide();
            $('#clr_price_idr').val(0); // Set otomatis ke 0 jika as per receipt
        } else {
            $('#wrap_fixed_price').show();
        }
    });

    // =======================================================
    // 4. ACTION BUTTONS TRIGGER
    // =======================================================

    // Tombol Tambah Data
    $("#btn_add").click(function() {
        $("#form_clearance")[0].reset(); 
        $("#clr_id").val('');
        $('#clr_is_at_cost').trigger('change'); // Pastikan UI direset sesuai dropdown default
        $("#dialog_form_clearance").dialog("open");
    });

    // Tombol Edit Data
    $("#btn_edit").click(function() {
        var id = $("#grid_clearance").jqGrid('getGridParam', 'selrow');
        if(!id) { 
            Swal.fire('Info', 'Silakan pilih data yang ingin diedit terlebih dahulu!', 'info'); 
            return; 
        }
        
        // Ambil rincian data ke server
        $.post(BASE_URL + 'pricelist/clearance/get_data_edit', {id: id}, function(res) {
            var d = JSON.parse(res);
            
            // Masukkan data ke input modal
            $("#clr_id").val(d.id);
            $("#clr_service_type").val(d.service_type);
            $("#clr_is_at_cost").val(d.is_at_cost).trigger('change'); // Otomatis trigger animasi sembunyi form
            $("#clr_component_name").val(d.component_name);
            $("#clr_price_idr").val(d.price_idr);
            $("#clr_unit_basis").val(d.unit_basis);
            $("#clr_remarks").val(d.remarks);
            
            $("#dialog_form_clearance").dialog("open");
        });
    });

    // Tombol Delete Data
    $("#btn_delete").click(function() {
        var id = $("#grid_clearance").jqGrid('getGridParam', 'selrow');
        if(!id) {
            Swal.fire('Info', 'Silakan pilih data yang ingin dihapus!', 'info'); 
            return; 
        }
        
        Swal.fire({
            title: 'Hapus Data Tarif?',
            text: "Data yang dihapus tidak dapat dikembalikan!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Ya, Hapus!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post(BASE_URL + 'pricelist/clearance/delete', {id: id}, function() {
                    $("#grid_clearance").trigger("reloadGrid");
                    Swal.fire('Terhapus!', 'Data berhasil dihapus.', 'success');
                });
            }
        });
    });

});
</script>