<script>
    var BASE_URL = '<?= site_url() ?>';
</script>

<div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 10px; gap: 10px;">
    <div>
        <button id="btn_add" class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-icon-primary"><span class="ui-button-icon-primary ui-icon ui-icon-plus"></span><span class="ui-button-text">Tambah Data</span></button>
        <button id="btn_edit" class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-icon-primary"><span class="ui-button-icon-primary ui-icon ui-icon-pencil"></span><span class="ui-button-text">Edit</span></button>
        <button id="btn_delete" class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-icon-primary"><span class="ui-button-icon-primary ui-icon ui-icon-trash"></span><span class="ui-button-text">Hapus</span></button>
    </div>
    <div id="global-search" style="position: relative;">
        <i class="fa-solid fa-magnifying-glass search-icon" style="position:absolute; left:10px; top:8px; color:#999;"></i>
        <input type="text" id="global_search_input" placeholder="Cari Rute/Tujuan .." style="width:320px; padding:6px 10px 6px 34px; border-radius:4px; border:1px solid #ccc;">
    </div>
</div>

<table id="grid_trucking"></table>
<div id="pager_trucking"></div>

<div id="dialog_form_trucking" title="Form Inland / Trucking Rates" style="display:none;">
    <form id="form_trucking">
        <input type="hidden" name="id" id="id">
        <input type="hidden" name="destination_name" id="destination_name_text"> <table width="100%" style="border-collapse: separate; border-spacing: 0 10px;">
            <tr>
                <td width="20%"><b>Tujuan / Wilayah</b></td>
                <td>
                    <select name="destination_name" id="destination_name" style="width: 100%;"></select>
                </td>
            </tr>
            <tr>
                <td><b>CDD</b></td>
                <td><input type="number" name="cdd_price" id="cdd_price" class="form-control" style="width:90%; padding:6px; border:1px solid #ccc;"></td>
            </tr>
            <tr>
                <td><b>FUSO</b></td>
                <td><input type="number" name="fuso_price" id="fuso_price" class="form-control" style="width:90%; padding:6px; border:1px solid #ccc;"></td>
            </tr>
            <tr>
                <td><b>Wingbox</b></td>
                <td><input type="number" name="wb_price" id="wb_price" class="form-control" style="width:90%; padding:6px; border:1px solid #ccc;"></td>
            </tr>
            <tr>
                <td><b>20'</b></td>
                <td><input type="number" name="price_20" id="price_20" class="form-control" style="width:90%; padding:6px; border:1px solid #ccc;"></td>
            </tr>
            <tr>
                <td><b>40'</b></td>
                <td><input type="number" name="price_40" id="price_40" class="form-control" style="width:90%; padding:6px; border:1px solid #ccc;"></td>
            </tr>
        </table>
        <small style="color:#e67e22;"><i>* Asumsi tarif berlaku Origin/Destinasi Tanjung Priok.</i></small>
    </form>
</div>