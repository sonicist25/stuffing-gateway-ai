$(document).ready(function() {
    // -----------------------------------------
    // Setup Global AJAX Error Handling
    // -----------------------------------------
    $.ajaxSetup({
        error: function(jqXHR, textStatus, errorThrown) {
            if (jqXHR.status === 401) {
                Swal.fire({
                    title: 'Sesi Berakhir',
                    text: 'Sesi login Anda telah habis karena tidak ada aktivitas. Silakan login kembali.',
                    icon: 'warning',
                    confirmButtonText: 'OK',
                    allowOutsideClick: false,
                    allowEscapeKey: false
                }).then((result) => {
                    window.location.href = BASE_URL; 
                });
            } else if (jqXHR.status === 500) {
                console.error('Terjadi kesalahan pada server (500).');
            }
        }
    });

    // -----------------------------------------
    // Inisialisasi Select2
    // -----------------------------------------
    $("#destination_id").select2({
        placeholder: 'PILIH POD',
        allowClear: false,
        dropdownParent: $("#dialog_form_freight"), 
        ajax: {
            url: BASE_URL + "afg_c_dropdown/fetch_dropdown_port_pricing", 
            type: 'GET', 
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return { q: params.term || '' }; 
            },
            processResults: function (data) {
                return { results: data };
            }
        }
    });

    $("#origin_id").select2({
        placeholder: 'PILIH POL', 
        allowClear: false,
        dropdownParent: $("#dialog_form_freight"),
        ajax: {
            url: BASE_URL + "afg_c_dropdown/fetch_dropdown_port_pricing", 
            type: 'GET',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return { q: params.term || '' };
            },
            processResults: function (data) {
                return {
                    results: $.map(data, function (item) {
                        return { id: item.id, text: item.text };
                    })
                };
            }
        }
    });

    $("#liner_name").select2({
        placeholder: 'PILIH CARRIER',
        allowClear: false,
        dropdownParent: $("#dialog_form_freight"),
        ajax: {
            url: BASE_URL + "afg_c_dropdown/dropdown_liner_select2",
            type: 'GET',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return { q: params.term || '' };
            },
            processResults: function (data) {
                return {
                    results: $.map(data, function (item) {
                        return { id: item.code, text: item.code };
                    })
                };
            }
        }
    });

    // -----------------------------------------
    // Setup jqGrid
    // -----------------------------------------
    function debounce(fn, delay) {
        var timer = null;
        return function() {
            var context = this, args = arguments;
            clearTimeout(timer);
            timer = setTimeout(function() {
                fn.apply(context, args);
            }, delay);
        };
    }

    function applyGlobalRouteFilter(value) {
        var $grid = $("#grid_freight");
        if (!value || value.trim() === "") {
            $grid.jqGrid('setGridParam', { postData: { filters: "" } });
        } else {
            var filters = {
                groupOp: "AND",
                rules: [ { field: "route", op: "cn", data: value } ]
            };
            $grid.jqGrid('setGridParam', { postData: { filters: JSON.stringify(filters) } });
        }
        $grid.trigger("reloadGrid", [{ page: 1 }]);
    }

    // VARIABEL GLOBAL PENANDA LIST YANG AKTIF (DEFAULT FCL)
    var currentViewType = 'FCL'; 

    $("#grid_freight").jqGrid({
        url: 'export_feeder_rate/get_grid_data',
        datatype: "json",
        mtype: "POST",
        postData: { 
            view_type: function() { return currentViewType; } // Selalu kirim status view saat ini ke PHP
        }, 
        width: 'auto', autowidth: true, shrinkToFit: true,
        colNames: [
            'ID', 'Route (POL ➝ POD)', 'Liner', 'Cargo', 
            'FCL Expired', 'FCL Transit', "20'", "40'", "40'HC",
            'LCL Expired', "LCL Dir", "Via SIN", "Via BSN", "Via HKG", "Via NSA", "Via CMB", "Via JEA"
        ],
        colModel: [
            {name: 'id', index: 'r.id', width: 50, align: 'center', hidden: true},
            {name: 'route', index: 'route', width: 280, search: true, searchoptions: { sopt: ['cn','eq'] }},
            {name: 'liner_name', index: 'r.liner_name', width: 100},
            {name: 'cargo_type', index: 'r.cargo_type', width: 80, align: 'center'},
            // Kolom FCL (Default Tampil)
            {name: 'fcl_validity', index: 'r.fcl_validity_to', width: 90, align: 'center'},
            {name: 'fcl_transit_time', index: 'r.fcl_transit_time', width: 90, align: 'center'},
            {name: 'total_20', index: 'total_20', width: 65, align: 'right'},
            {name: 'total_40', index: 'total_40', width: 65, align: 'right'},
            {name: 'total_40hc', index: 'total_40hc', width: 65, align: 'right'},
            // Kolom LCL (Default Sembunyi)
            {name: 'lcl_validity', index: 'r.lcl_validity_to', width: 90, align: 'center', hidden: true},
            {name: 'lcl_direct', index: 'lcl_direct', width: 60, align: 'right', hidden: true},
            {name: 'lcl_sin', index: 'lcl_sin', width: 60, align: 'right', hidden: true},
            {name: 'lcl_busan', index: 'lcl_busan', width: 60, align: 'right', hidden: true},
            {name: 'lcl_hkg', index: 'lcl_hkg', width: 60, align: 'right', hidden: true},
            {name: 'lcl_nhava', index: 'lcl_nhava', width: 60, align: 'right', hidden: true},
            {name: 'lcl_colombo', index: 'lcl_colombo', width: 60, align: 'right', hidden: true},
            {name: 'lcl_jebel_ali', index: 'lcl_jebel_ali', width: 60, align: 'right', hidden: true}
        ],
        rowNum: 20, rowList: [20, 50, 100],
        pager: '#pager_freight', sortname: 'route', viewrecords: true, sortorder: "asc",
        width: 1200, height: 550, rownumbers: true, caption: "Data Freight Pricing", multiselect: true, grouping: true,
        groupingView: {
            groupField: ['route'], groupColumnShow: [false],
            groupText: ['<b><i class="fa fa-route" style="color:#e74c3c; margin-right:4px;"></i> {0}</b> <span style="font-weight:normal; color:#666;">({1} Liner)</span>'],
            groupCollapse: false, groupOrder: ['asc']
        },
        rowattr: function (rowData) {
            var valDateStr = currentViewType === 'FCL' ? rowData.fcl_validity : rowData.lcl_validity;
            if (valDateStr && valDateStr !== '-') {
                var validDate = new Date(valDateStr);
                var today = new Date();
                today.setHours(0, 0, 0, 0); validDate.setHours(0, 0, 0, 0);
                var diffDays = Math.ceil((validDate.getTime() - today.getTime()) / (1000 * 60 * 60 * 24));
                if (diffDays >= 0 && diffDays <= 5) return {"class": "row-expiring-soon"};
                else if (diffDays < 0) return {"class": "row-expired"};
            }
            return {};
        },
        loadComplete: function() {
            $("#grid_freight").jqGrid('hideCol', 'route');
            var colors = ['#e8f4f8', '#fdf3e7', '#eafaf1', '#fceef3', '#f4f1fa'];
            var borderColors = ['#bce0ed', '#fae0c2', '#bdf0d4', '#f5c6cb', '#d6c8f5'];
            var index = 0;
            $(".jqgroup").each(function() {
                var colorIndex = index % colors.length;
                $(this).find("td").css({ "background-color": colors[colorIndex], "border-bottom": "2px solid " + borderColors[colorIndex], "color": "#333" });
                index++;
            });
        }
    });

    // -----------------------------------------
    // PERBAIKAN 1: GRID RESIZE STABILITY
    // -----------------------------------------
    // Mencegah grid hancur saat window browser di-resize
    $(window).on('resize', function() {
        var $grid = $("#grid_freight");
        if($grid.length > 0) {
            $grid.jqGrid('setGridWidth', $grid.closest('.ui-jqgrid').parent().width());
        }
    });

    // EVENT LISTENER TOMBOL SWITCHER
    $('.view-btn').click(function() {
        $('.view-btn').removeClass('active').css({'background':'#f9f9f9', 'color':'#555'});
        $(this).addClass('active').css({'background':'#00b09b', 'color':'#fff'});
        
        currentViewType = $(this).data('type');
        var $grid = $("#grid_freight");

        if (currentViewType === 'FCL') {
            $grid.jqGrid('showCol', ['fcl_validity', 'fcl_transit_time', 'total_20', 'total_40', 'total_40hc']);
            $grid.jqGrid('hideCol', ['lcl_validity', 'lcl_direct', 'lcl_sin', 'lcl_busan', 'lcl_hkg', 'lcl_nhava', 'lcl_colombo', 'lcl_jebel_ali']);
        } else {
            $grid.jqGrid('hideCol', ['fcl_validity', 'fcl_transit_time', 'total_20', 'total_40', 'total_40hc']);
            $grid.jqGrid('showCol', ['lcl_validity', 'lcl_direct', 'lcl_sin', 'lcl_busan', 'lcl_hkg', 'lcl_nhava', 'lcl_colombo', 'lcl_jebel_ali']);
        }

        // Paksa grid menghitung ulang lebar layar agar tidak error
        var parentWidth = $grid.closest('.ui-jqgrid').parent().width();
        $grid.jqGrid('setGridWidth', parentWidth);

        $grid.trigger("reloadGrid", [{ page: 1 }]);
    });


    // -----------------------------------------
    // PERBAIKAN 2: HAPUS PROMPT EDIT/TAMBAH
    // -----------------------------------------
    // ACTION TAMBAH (LANGSUNG MEMBUKA FORM SESUAI TABEL YANG AKTIF)
    $("#btn_add").click(function() {
        $("#id").val('');
        document.getElementById("form_freight").reset();
        $("#tbody_fcl").empty(); $("#tbody_lcl").empty();
        
        var opt = new Option('TANJUNG PRIOK, ID (IDTPP)', 37223, true, true);
        $('#origin_id').empty().append(opt).trigger('change');
        
        // 1. BUKA DIALOG DULU (Penting agar UI me-render ukurannya)
        $("#dialog_form_freight").dialog("open");

        // 2. TIMEOUT 50ms untuk mengatur Tab tanpa bentrok tampilan
        setTimeout(function() {
            if (currentViewType === 'FCL') {
                $("#rate_type").val("FCL");
                $("#wrapper_fcl").show();
                $("#wrapper_lcl").hide();
                addFclRow();
            } else {
                $("#rate_type").val("LCL");
                $("#wrapper_fcl").hide();
                $("#wrapper_lcl").show();
                addLclRow();
            }
        }, 50);
    });

    // ACTION EDIT (LANGSUNG MEMBUKA FORM SESUAI TABEL YANG AKTIF)
    $("#btn_edit").click(function() {
        var id = $("#grid_freight").jqGrid('getGridParam', 'selrow');
        if (!id) { 
            Swal.fire('Peringatan', 'Pilih baris yang akan diedit!', 'warning'); 
            return; 
        }

        $.post(BASE_URL + 'pricelist/export_feeder_rate/get_data_edit', {id: id}, function(data) {
            var res = JSON.parse(data);
            
            $("#id").val(res.header.id);
            $("#cargo_type").val(res.header.cargo_type);
            
            // Set Select2 Origin, Dest, Liner
            if (res.header.destination_id) {
                var destOption = new Option(res.header.destination_name, res.header.destination_id, true, true);
                $("#destination_id").empty().append(destOption).trigger('change');
            }
            if (res.header.origin_id && res.header.origin_name) {
                var originOption = new Option(res.header.origin_name, res.header.origin_id, true, true);
                $("#origin_id").empty().append(originOption).trigger('change');
            }
            if (res.header.liner_name) {
                var linerOption = new Option(res.header.liner_name, res.header.liner_name, true, true);
                $("#liner_name").empty().append(linerOption).trigger('change');
            }

            // 1. BUKA DIALOG DULU (Penting agar UI me-render ukurannya)
            $("#dialog_form_freight").dialog("open");

            // 2. TENTUKAN ISI FORM BERDASARKAN CURRENT VIEW TYPE
            setTimeout(function() {
                if (currentViewType === 'FCL') {
                    $("#rate_type").val("FCL");
                    $("#wrapper_fcl").show();
                    $("#wrapper_lcl").hide();

                    $("#fcl_validity_from").val(res.header.fcl_validity_from);
                    $("#fcl_validity_to").val(res.header.fcl_validity_to);
                    $("#fcl_free_time").val(res.header.fcl_free_time);
                    $("#fcl_transit_time").val(res.header.fcl_transit_time); 
                    $("#fcl_include_rate").val(res.header.fcl_include_rate);
                    $("#fcl_exclude_rate").val(res.header.fcl_exclude_rate);
                    $("#fcl_remarks").val(res.header.fcl_remarks);

                    $("#tbody_fcl").empty();
                    if(res.fcl_details && res.fcl_details.length > 0) {
                        $.each(res.fcl_details, function(i, v) {
                            let grpName = (v.group_id == 1) ? 'Freight Components' : (v.group_id == 2 ? 'Inland Moda' : 'Subject To');
                            addFclRow(grpName, v.component_name, v.unit_basis, v.price_20, v.price_40, v.price_40hc);
                        });
                    } else {
                        addFclRow(); 
                    }
                } 
                else {
                    $("#rate_type").val("LCL");
                    $("#wrapper_fcl").hide();
                    $("#wrapper_lcl").show();

                    $("#lcl_validity_from").val(res.header.lcl_validity_from);
                    $("#lcl_validity_to").val(res.header.lcl_validity_to);
                    $("#lcl_include_rate").val(res.header.lcl_include_rate);
                    $("#lcl_exclude_rate").val(res.header.lcl_exclude_rate);
                    $("#lcl_remarks").val(res.header.lcl_remarks);

                    $('.chk-priority').prop('checked', false); 
                    $("#lcl_direct, #lcl_sin, #lcl_busan, #lcl_hkg, #lcl_nhava, #lcl_colombo, #lcl_jebel_ali").val('');
                    $("#lcl_transit_direct, #lcl_transit_sin, #lcl_transit_busan, #lcl_transit_hkg, #lcl_transit_nhava, #lcl_transit_colombo, #lcl_transit_jebel_ali").val('');

                    let dataLcl = res.lcl_routes || res.lcl; 
                    if (dataLcl) {
                        $.each(dataLcl, function(i, v) {
                            let type = v.type_name;
                            let is_prio = (v.is_priority == 1 || v.is_priority == '1'); 
                            if(type == 'Direct Consol') { $("#lcl_direct").val(v.price); $("#lcl_transit_direct").val(v.transit_time); if(is_prio) $('input[name="lcl_priority[]"][value="direct_consol"]').prop('checked', true); }
                            if(type == 'Via SIN') { $("#lcl_sin").val(v.price); $("#lcl_transit_sin").val(v.transit_time); if(is_prio) $('input[name="lcl_priority[]"][value="via_sin"]').prop('checked', true); }
                            if(type == 'Via Busan') { $("#lcl_busan").val(v.price); $("#lcl_transit_busan").val(v.transit_time); if(is_prio) $('input[name="lcl_priority[]"][value="via_busan"]').prop('checked', true); }
                            if(type == 'Via HKG') { $("#lcl_hkg").val(v.price); $("#lcl_transit_hkg").val(v.transit_time); if(is_prio) $('input[name="lcl_priority[]"][value="via_hkg"]').prop('checked', true); }
                            if(type == 'Via Nhava Sheva') { $("#lcl_nhava").val(v.price); $("#lcl_transit_nhava").val(v.transit_time); if(is_prio) $('input[name="lcl_priority[]"][value="via_nhava"]').prop('checked', true); }
                            if(type == 'Via Colombo') { $("#lcl_colombo").val(v.price); $("#lcl_transit_colombo").val(v.transit_time); if(is_prio) $('input[name="lcl_priority[]"][value="via_colombo"]').prop('checked', true); }
                            if(type == 'Via Jebel Ali') { $("#lcl_jebel_ali").val(v.price); $("#lcl_transit_jebel_ali").val(v.transit_time); if(is_prio) $('input[name="lcl_priority[]"][value="via_jebel_ali"]').prop('checked', true); }
                        });
                    }

                    $("#tbody_lcl").empty();
                    if(res.lcl_details && res.lcl_details.length > 0) {
                        $.each(res.lcl_details, function(i, v) {
                            addLclRow(v.lcl_group, v.service_name, v.unit_basis, v.price_wm);
                        });
                    } else {
                        addLclRow(); 
                    }
                }
            }, 50);
        });
    });

    // =========================================
    // LAIN-LAIN
    // =========================================
    $("#global_search_btn").on('click', function() {
        applyGlobalRouteFilter($("#global_search_input").val());
    });

    $("#global_clear_btn").on('click', function() {
        $("#global_search_input").val('');
        applyGlobalRouteFilter('');
    });

    $("#global_search_input").on('input', debounce(function() {
        applyGlobalRouteFilter($(this).val());
    }, 400));

    $("#dialog_form_freight").dialog({
        autoOpen: false, width: 1100, height: 700, modal: true,
        buttons: {
            "Simpan": function() { saveData(); },
            "Batal": function() { $(this).dialog("close"); }
        },
        close: function() {
            document.getElementById("form_freight").reset();
            $("#origin_id").val('').trigger('change'); 
            $("#destination_id").val('').trigger('change');
            
            $("#tbody_fcl").empty();
            $("#tbody_lcl").empty();
            
            $("#lcl_direct, #lcl_sin, #lcl_busan, #lcl_hkg, #lcl_nhava, #lcl_colombo, #lcl_jebel_ali").val('');
            $("#lcl_transit_direct, #lcl_transit_sin, #lcl_transit_busan, #lcl_transit_hkg, #lcl_transit_nhava, #lcl_transit_colombo, #lcl_transit_jebel_ali").val('');
            $('input[name="lcl_priority[]"]').prop('checked', false);
        }
    });

    $("#btn_add_fcl_row").button().click(function(e) { e.preventDefault(); addFclRow(); });
    $("#btn_add_lcl_row").button().click(function(e) { e.preventDefault(); addLclRow(); });

    $(document).on('click', '.btn-remove-row', function() {
        $(this).closest('tr').remove();
    });

    $(document).on('change', '.chk-priority', function() {
        if ($('.chk-priority:checked').length > 2) {
            this.checked = false;
            Swal.fire('Info', 'Maksimal hanya boleh memilih 2 prioritas rute LCL.', 'info');
        }
    });

    // Action Hapus
    $("#btn_delete").click(function() {
        var id = $("#grid_freight").jqGrid('getGridParam', 'selrow');
        if (id) {
            Swal.fire({
                title: 'Apakah anda yakin?',
                text: "Data yang dihapus tidak dapat dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Hapus!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('export_feeder_rate/delete', {id: id}, function(data) {
                        var res = JSON.parse(data);
                        if(res.status == 'success') {
                            $("#grid_freight").trigger("reloadGrid");
                            Swal.fire('Terhapus!', res.msg, 'success');
                        }
                    });
                }
            });
        } else {
            Swal.fire('Peringatan', 'Pilih baris yang akan dihapus!', 'warning');
        }
    });
});

// =========================================
// FUNGSI SIMPAN
// =========================================
function saveData() {
    if($("#liner_name").val() == "") {
        Swal.fire('Error', 'Liner Name harus diisi!', 'error');
        return;
    }

    var rateType = $("#rate_type").val();
    
    if (rateType === 'LCL') {
        let isLclFilled = false;
        $('input[name^="lcl["]').each(function() {
            if($(this).val() !== '') isLclFilled = true;
        });
        if (isLclFilled && $('.chk-priority:checked').length < 1) {
            Swal.fire('Peringatan', 'Minimal pilih 1 rute prioritas untuk LCL!', 'warning');
            return; 
        }
    }

    var formData = $("#form_freight").serialize();
    $.ajax({
        url: 'export_feeder_rate/save',
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(res) {
            if(res.status == 'success') {
                $("#dialog_form_freight").dialog("close");
                $("#grid_freight").trigger("reloadGrid");
                Swal.fire('Sukses', res.msg, 'success');
            } else {
                Swal.fire('Error', res.msg, 'error');
            }
        }
    });
}

function addFclRow(group_val = 'Freight Components', name = '', unit_val = 'Per Container', p20 = '', p40 = '', p40hc = '') {
    let selGFreight = group_val == 'Freight Components' ? 'selected' : '';
    let selGInland  = group_val == 'Inland Moda' ? 'selected' : '';
    let selGSubject = group_val == 'Subject To' ? 'selected' : '';

    let selUCont  = unit_val == 'Per Container' ? 'selected' : '';
    let selUDoc   = unit_val == 'Per Document' ? 'selected' : '';
    let selUBL    = unit_val == 'Per Cbm' ? 'selected' : '';
    let selUShip  = unit_val == 'Per Shipment' ? 'selected' : '';
    let selUDay = unit_val == 'Per Day' ? 'selected' : '';

    var tr = `
    <tr>
        <td>
            <select name="fcl_group[]" class="modern-input">
                <option value="Freight Components" ${selGFreight}>Freight Components</option>
                <option value="Inland Moda" ${selGInland}>Inland Moda Cost Only</option>
                <option value="Subject To" ${selGSubject}>Subject To</option>
            </select>
        </td>
        <td>
            <input type="text" name="fcl_service_name[]" value="${name}" 
                   class="modern-input autocomplete-component" 
                   placeholder="Nama Komponen..." required>
        </td>
        <td>
            <select name="fcl_unit[]" class="modern-input">
                <option value="Per Container" ${selUCont}>Per Container</option>
                <option value="Per Document" ${selUDoc}>Per Document</option>
                <option value="Per Cbm" ${selUBL}>Per Cbm</option>
                <option value="Per Shipment" ${selUShip}>Per Shipment</option>
                <option value="Per Day" ${selUDay}>Per Day</option>
            </select>
        </td>
        <td align="center"><input type="number" step="0.01" name="fcl_price_20[]" value="${p20}" class="modern-input" style="text-align:right;"></td>
        <td align="center"><input type="number" step="0.01" name="fcl_price_40[]" value="${p40}" class="modern-input" style="text-align:right;"></td>
        <td align="center"><input type="number" step="0.01" name="fcl_price_40hc[]" value="${p40hc}" class="modern-input" style="text-align:right;"></td>
        <td align="center">
             <button type="button" class="btn-remove-row ui-button ui-widget ui-state-default ui-corner-all ui-button-text-icon-primary" style="padding:4px 8px; color:#dc3545;">
                        <i class="fa-solid fa-trash"></i>
                    </button>
        </td>
    </tr>`;
    
    var $newRow = $(tr);
    $("#tbody_fcl").append($newRow);
    initAutocomplete($newRow.find('.autocomplete-component'));
}

function addLclRow(group_val = 'Origin', name = '', unit_val = 'Per CBM', price_wm = '') {
    let selGOrg  = group_val == 'Origin' ? 'selected' : '';
    let selGFre  = group_val == 'Freight' ? 'selected' : '';
    let selGDest = group_val == 'Destination' ? 'selected' : '';

    let selUWM   = unit_val == 'W/M' ? 'selected' : '';
    let selUCBM  = unit_val == 'Per CBM' ? 'selected' : '';
    let selUTon  = unit_val == 'Per Kgs' ? 'selected' : '';
    let selUBL   = unit_val == 'Per B/L' ? 'selected' : '';
    let selUShip = unit_val == 'Per Shipment' ? 'selected' : '';
    let selUAll  = unit_val == 'ALL IN' ? 'selected' : '';

    var tr = `
    <tr>
        <td>
            <select name="lcl_group[]" class="modern-input">
                <option value="Origin" ${selGOrg}>Origin Charges</option>
                <option value="Freight" ${selGFre}>Freight Charges</option>
                <option value="Destination" ${selGDest}>Destination Charges</option>
            </select>
        </td>
        <td>
            <input type="text" name="lcl_service_name[]" value="${name}" 
                   class="modern-input autocomplete-component" 
                   placeholder="Nama Komponen LCL..." required>
        </td>
        <td>
            <select name="lcl_unit[]" class="modern-input">
                <option value="ALL IN" ${selUAll}>All In</option>    
                <option value="Per CBM" ${selUCBM}>Per CBM</option>
                <option value="Per Kgs" ${selUTon}>Per Kgs</option>
                <option value="Per B/L" ${selUBL}>Per B/L</option>
                <option value="Per Shipment" ${selUShip}>Per Shipment</option>
            </select>
        </td>
        <td align="center"><input type="number" step="0.01" name="lcl_price_wm[]" value="${price_wm}" class="modern-input" style="text-align:right;"></td>
        <td align="center">
            <button type="button" class="btn-remove-row ui-button ui-widget ui-state-default ui-corner-all ui-button-text-icon-primary" style="padding:4px 8px; color:#dc3545;">
                        <i class="fa-solid fa-trash"></i>
                    </button>
        </td>
    </tr>`;
    
    var $newRow = $(tr);
    $("#tbody_lcl").append($newRow);
    initAutocomplete($newRow.find('.autocomplete-component'));
}

function initAutocomplete(element) {
    element.autocomplete({
        source: function(request, response) {
            $.ajax({
                url: BASE_URL + "afg_c_dropdown/item_service_auto",
                type: "GET",
                dataType: "json",
                data: { term: request.term },
                success: function(data) { response(data); }
            });
        },
        minLength: 2,
        select: function(event, ui) {
            $(this).val(ui.item.value);
            return false;
        }
    });
}