<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/themes/smoothness/jquery-ui.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/free-jqgrid/4.15.5/css/ui.jqgrid.min.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />

<style>
    .ui-jqgrid { font-size: 13px; }
    .grid-wrapper { width: 100%; overflow-x: auto; overflow-y: hidden; padding-bottom: 5px; margin-top: 15px; }
    .ui-jqgrid .ui-jqgrid-bdiv { overflow-x: auto !important; }
    .ui-jqgrid .ui-jqgrid-hdiv { overflow-x: hidden !important; }
    .ui-jqgrid-titlebar { background: #1e293b; color: white; }
    .select2-container--default .select2-selection--single { height: 28px; padding: 2px; border: 1px solid #ccc; border-radius: 3px; }
    .select2-container--default .select2-selection--single .select2-selection__arrow { height: 26px; }
    .select2-container--open { z-index: 9999999 !important; }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div id="rate-tabs">
                <ul>
                    <li><a href="#tab-fcl">1. FCL Import Rates</a></li>
                    <li><a href="#tab-lcl">2. LCL Import Rates</a></li>
                    <li><a href="#tab-local">3. Dest. Local Charges</a></li>
                    <li><a href="#tab-origin">4. Origin Charges</a></li>
                    <li><a href="#tab-rebate">5. Agent Rebates</a></li>
                </ul>
                <div id="tab-fcl" class="grid-wrapper"><table id="grid_fcl"></table><div id="pager_fcl"></div></div>
                <div id="tab-lcl" class="grid-wrapper"><table id="grid_lcl"></table><div id="pager_lcl"></div></div>
                <div id="tab-local" class="grid-wrapper"><table id="grid_local"></table><div id="pager_local"></div></div>
                <div id="tab-origin" class="grid-wrapper"><table id="grid_origin"></table><div id="pager_origin"></div></div>
                <div id="tab-rebate" class="grid-wrapper"><table id="grid_rebate"></table><div id="pager_rebate"></div></div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/jquery-ui.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/free-jqgrid/4.15.5/jquery.jqgrid.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

<script>
$(document).ready(function () {
    var initialGridWidth = $("#rate-tabs").width() - 40; 
    if (initialGridWidth < 800) initialGridWidth = 1000;

    $("#rate-tabs").tabs({
        activate: function(event, ui) {
            var newWidth = $("#rate-tabs").width() - 40;
            var gridId = ui.newPanel.find("table.ui-jqgrid-btable").attr('id');
            if (gridId) {
                var shrink = (gridId === 'grid_fcl'); 
                $("#" + gridId).jqGrid('setGridWidth', newWidth, shrink);
            }
        }
    });

    var portDropdownUrl = '<?= base_url("pricelist/import_rates/get_port_select2") ?>';
    var agentDropdownUrl = '<?= base_url("pricelist/import_rates/get_agent_select2") ?>';
    var carrierDropdownUrl = '<?= base_url("pricelist/import_rates/get_carrier_select2") ?>'; // Endpoint SCAC Baru
    var agentOriginDropdownUrl = '<?= base_url("pricelist/import_rates/get_agent_origin_select2") ?>';

    var select2Config = function(url, placeholderText) {
        return {
            value: { "": placeholderText }, 
            dataInit: function (elem) {
                setTimeout(function () {
                    var $dialog = $(elem).closest('.ui-jqdialog');
                    var parentElem = $dialog.length > 0 ? $dialog : $(document.body);
                    $(elem).select2({
                        ajax: { url: url, dataType: 'json', delay: 250, data: function (params) { return { q: params.term }; }, processResults: function (data) { return { results: data }; } },
                        minimumInputLength: 2, width: '100%', placeholder: placeholderText, dropdownParent: parentElem
                    });
                }, 150);
            }
        };
    };

    var datepickerOptions = { dataInit: function (element) { $(element).datepicker({ dateFormat: 'yy-mm-dd', changeMonth: true, changeYear: true }); } };

    $.ui.dialog.prototype._allowInteraction = function (e) {
        return !!$(e.target).closest('.ui-jqdialog, .select2-drop, .select2-container, .select2-dropdown, .ui-datepicker').length || this._super(e);
    };

    var formOptions = {
        closeAfterEdit: true, closeAfterAdd: true, reloadAfterSubmit: true, recreateForm: true, width: 450,
        beforeShowForm: function (form) {
            var selRowId = $(this).jqGrid('getGridParam', 'selrow'); 
            if (selRowId) {
                var rowData = $(this).jqGrid('getRowData', selRowId);
                
                // Set POL
                var pol = $.jgrid.stripHtml(rowData.pol_display); 
                if (pol && pol !== '&#160;' && pol !== '&nbsp;') {
                    if ($("#pol_display", form).find("option[value='" + pol + "']").length === 0) { $("#pol_display", form).append(new Option(pol, pol, true, true)); }
                    $("#pol_display", form).val(pol).trigger('change');
                }
                
                // Set Agent
                var agent = $.jgrid.stripHtml(rowData.agent_display);
                if (agent && agent !== '&#160;' && agent !== '&nbsp;') {
                    if ($("#agent_display", form).find("option[value='" + agent + "']").length === 0) { $("#agent_display", form).append(new Option(agent, agent, true, true)); }
                    $("#agent_display", form).val(agent).trigger('change');
                }

                // Set Carrier (Khusus FCL)
                var carrier = $.jgrid.stripHtml(rowData.carrier_display);
                if (carrier && carrier !== '&#160;' && carrier !== '&nbsp;') {
                    if ($("#carrier_display", form).find("option[value='" + carrier + "']").length === 0) { $("#carrier_display", form).append(new Option(carrier, carrier, true, true)); }
                    $("#carrier_display", form).val(carrier).trigger('change');
                }
            }
        }
    };

    // ==========================================
    // 1. FCL GRID 
    // ==========================================
    $("#grid_fcl").jqGrid({
        url: '<?= base_url("pricelist/import_rates/get_fcl") ?>', datatype: "json",
        colModel: [
            { label: 'ID', name: 'id', key: true, hidden: true },
            { label: 'POL', name: 'pol_display', editable: true, edittype: 'select', editoptions: select2Config(portDropdownUrl, '-- Ketik Port --'), width: 200 },
            
            // Kolom Carrier diganti jadi SCAC Select2
            { label: 'Carrier', name: 'carrier_display', editable: true, edittype: 'select', editoptions: select2Config(carrierDropdownUrl, '-- Ketik Carrier/SCAC --'), width: 180 },
            
            { label: 'Agent', name: 'agent_display', editable: true, edittype: 'select', editoptions: select2Config(agentDropdownUrl, '-- Ketik Agen --'), width: 180 },
            { label: 'Price 20', name: 'price_20', editable: true, width: 80, align: 'right', formatter: 'currency', formatoptions: {prefix: '$ '} },
            { label: 'Price 40', name: 'price_40', editable: true, width: 80, align: 'right', formatter: 'currency', formatoptions: {prefix: '$ '} },
            { label: 'Price 40HQ', name: 'price_40hq', editable: true, width: 80, align: 'right', formatter: 'currency', formatoptions: {prefix: '$ '} },
            { label: 'Validity', name: 'period_month', editable: true, editoptions: datepickerOptions, width: 100, align: 'center', formatter: 'date', formatoptions: { srcformat: 'Y-m-d', newformat: 'd-M-Y' } }
        ],
        loadonce: true, viewrecords: true, width: initialGridWidth, shrinkToFit: true, height: 400, rowNum: 20,
        pager: "#pager_fcl", editurl: '<?= base_url("pricelist/import_rates/crud_fcl") ?>'
    });
    $("#grid_fcl").jqGrid('navGrid', '#pager_fcl', { edit: true, add: true, del: true }, formOptions, formOptions, { reloadAfterSubmit: true });

    // ==========================================
    // 2. LCL GRID (Horizontal Scroll)
    // ==========================================
    $("#grid_lcl").jqGrid({
        url: '<?= base_url("pricelist/import_rates/get_lcl") ?>', datatype: "json",
        colModel: [
            { label: 'ID', name: 'id', key: true, hidden: true },
            { label: 'Notes/Ref', name: 'pol_display_raw', editable: true, hidden:true, width: 160 },
            { label: 'POL', name: 'pol_display', editable: true, edittype: 'select', editoptions: select2Config(portDropdownUrl, '-- Ketik Port --'), width: 220 },
            { label: 'Agent', name: 'agent_display', editable: true, edittype: 'select', editoptions: select2Config(agentDropdownUrl, '-- Ketik Agen --'), width: 180 },
            { label: 'O/F', name: 'of_per_cbm', editable: true, width: 100, align: 'right', formatter: 'currency', formatoptions: {prefix: '$ '} },
            { label: 'LSS', name: 'lss_per_cbm', editable: true, width: 100, align: 'right', formatter: 'currency', formatoptions: {prefix: '$ '} },
            { label: 'GRI', name: 'gri_per_cbm', editable: true, width: 100, align: 'right', formatter: 'currency', formatoptions: {prefix: '$ '} },
            { label: 'PSS', name: 'pss_per_cbm', editable: true, width: 100, align: 'right', formatter: 'currency', formatoptions: {prefix: '$ '} },
            { label: 'EFS', name: 'efs_per_cbm', editable: true, width: 100, align: 'right', formatter: 'currency', formatoptions: {prefix: '$ '} },
            { label: 'FCS', name: 'fcs_per_cbm', editable: true, width: 100, align: 'right', formatter: 'currency', formatoptions: {prefix: '$ '} },
            { label: 'ETS', name: 'ets_per_cbm', editable: true, width: 100, align: 'right', formatter: 'currency', formatoptions: {prefix: '$ '} },
            { label: 'Validity', name: 'period_month', editable: true, editoptions: datepickerOptions, width: 120, align: 'center', formatter: 'date', formatoptions: { srcformat: 'Y-m-d', newformat: 'd-M-Y' } }
        ],
        loadonce: true, viewrecords: true, width: initialGridWidth, shrinkToFit: false, height: 400, rowNum: 20,
        pager: "#pager_lcl", editurl: '<?= base_url("pricelist/import_rates/crud_lcl") ?>'
    });
    $("#grid_lcl").jqGrid('navGrid', '#pager_lcl', { edit: true, add: true, del: true }, formOptions, formOptions, { reloadAfterSubmit: true });

    // ==========================================
    // 3. DESTINATION LOCAL CHARGE GRID 
    // ==========================================
    var formOptionsAgentChild = $.extend({}, formOptions, {
        beforeShowForm: function (form) {
            var selRowId = $(this).jqGrid('getGridParam', 'selrow'); 
            if (selRowId) {
                var rowOriginal = $(this).jqGrid('getLocalRow', selRowId);
                var parentId = rowOriginal.agent_id;
                var parentText = (rowOriginal.origin_name || 'UNKNOWN') + " - " + (rowOriginal.agent_name || 'UNKNOWN');
                if (parentId && parentText) {
                    if ($("#agent_id", form).find("option[value='" + parentId + "']").length === 0) {
                        $("#agent_id", form).append(new Option(parentText, parentId, true, true));
                    }
                    $("#agent_id", form).val(parentId).trigger('change');
                }
            }
        }
    });

    $("#grid_local").jqGrid({
        url: '<?= base_url("pricelist/import_rates/get_local_charge") ?>', datatype: "json",
        colModel: [
            { label: 'ID', name: 'id', key: true, hidden: true },
            { label: 'Link Origin & Agent', name: 'agent_id', editable: true, edittype: 'select', editoptions: select2Config(agentOriginDropdownUrl, '-- Ketik Origin atau Agen --'), hidden: true, editrules: { edithidden: true } },
            { label: 'Origin', name: 'origin_name', editable: false, width: 180 },
            { label: 'Agent', name: 'agent_name', editable: false, width: 180 },
            { label: 'Item Name', name: 'item_name', editable: true, width: 180 },
            { label: 'Coloader Curr', name: 'coloader_currency', editable: true, width: 90, align: 'center' },
            { label: 'Coloader Amt', name: 'coloader_amount', editable: true, width: 110, align: 'right', formatter: 'number' },
            { label: 'Direct Curr', name: 'direct_currency', editable: true, width: 90, align: 'center' },
            { label: 'Direct Amt', name: 'direct_amount', editable: true, width: 110, align: 'right', formatter: 'number' }
        ],
        loadonce: true, viewrecords: true, width: initialGridWidth, shrinkToFit: false, height: 400, rowNum: 20,
        pager: "#pager_local", editurl: '<?= base_url("pricelist/import_rates/crud_local_charge") ?>'
    });
    $("#grid_local").jqGrid('navGrid', '#pager_local', { edit: true, add: true, del: true }, formOptionsAgentChild, formOptionsAgentChild, { reloadAfterSubmit: true });

    // ==========================================
    // 4. ORIGIN EXTRA CHARGE GRID 
    // ==========================================
    $("#grid_origin").jqGrid({
        url: '<?= base_url("pricelist/import_rates/get_origin_charge") ?>', datatype: "json",
        colModel: [
            { label: 'ID', name: 'id', key: true, hidden: true },
            { label: 'Link Origin & Agent', name: 'agent_id', editable: true, edittype: 'select', editoptions: select2Config(agentOriginDropdownUrl, '-- Ketik Origin atau Agen --'), hidden: true, editrules: { edithidden: true } },
            { label: 'Origin', name: 'origin_name', editable: false, width: 150 },
            { label: 'Agent', name: 'agent_name', editable: false, width: 150 },
            { label: 'Charge Group', name: 'charge_group', editable: true, edittype: 'select', editoptions: {value: "ORIGIN LOCAL CHARGE:ORIGIN LOCAL CHARGE;EXWORKS:EXWORKS;GENERAL:GENERAL;DIRECT:DIRECT"}, width: 160 },
            { label: 'Item Name', name: 'item_name', editable: true, width: 180 },
            { label: 'Curr', name: 'currency', editable: true, width: 70, align: 'center' },
            { label: 'Amount', name: 'amount', editable: true, width: 100, align: 'right', formatter: 'number' },
            { label: 'Raw Text', name: 'raw_text', editable: true, width: 280 }
        ],
        loadonce: true, viewrecords: true, width: initialGridWidth, shrinkToFit: false, height: 400, rowNum: 20,
        pager: "#pager_origin", editurl: '<?= base_url("pricelist/import_rates/crud_origin_charge") ?>'
    });
    $("#grid_origin").jqGrid('navGrid', '#pager_origin', { edit: true, add: true, del: true }, formOptionsAgentChild, formOptionsAgentChild, { reloadAfterSubmit: true });

    // ==========================================
    // 5. AGENT REBATES MASTER GRID 
    // ==========================================
    $("#grid_rebate").jqGrid({
        url: '<?= base_url("pricelist/import_rates/get_agent_rebate") ?>', datatype: "json",
        colModel: [
            { label: 'Agent ID', name: 'id', key: true, hidden: true },
            { label: 'Origin Name', name: 'origin_name', editable: true, width: 180 },
            { label: 'Agent Name', name: 'agent_name', editable: true, width: 180 },
            { label: 'Rebate Nominate (Teks)', name: 'rebate_nominate_text', editable: true, width: 220 },
            { label: 'Nominate Amt', name: 'rebate_nominate_amount', editable: true, width: 120, align: 'right', formatter: 'number' },
            { label: 'Rebate Freehand (Teks)', name: 'rebate_freehand_text', editable: true, width: 220 },
            { label: 'Freehand Amt', name: 'rebate_freehand_amount', editable: true, width: 120, align: 'right', formatter: 'number' }
        ],
        loadonce: true, viewrecords: true, width: initialGridWidth, shrinkToFit: false, height: 400, rowNum: 20,
        pager: "#pager_rebate", editurl: '<?= base_url("pricelist/import_rates/crud_agent_rebate") ?>'
    });
    $("#grid_rebate").jqGrid('navGrid', '#pager_rebate', { edit: true, add: true, del: true }, formOptions, formOptions, { reloadAfterSubmit: true });

    $(window).on('resize', function() {
        var newWidth = $("#rate-tabs").width() - 40;
        $("#grid_fcl").jqGrid('setGridWidth', newWidth, true);
        $("#grid_lcl").jqGrid('setGridWidth', newWidth, false);
        $("#grid_local").jqGrid('setGridWidth', newWidth, false);
        $("#grid_origin").jqGrid('setGridWidth', newWidth, false);
        $("#grid_rebate").jqGrid('setGridWidth', newWidth, false);
    });
});
</script>