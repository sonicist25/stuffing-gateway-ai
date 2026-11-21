$(document).ready(function() {

    // 1. INIT SELECT2 DENGAN FITUR TAGS (BISA KETIK BEBAS)
    $("#destination_name").select2({
        tags: true, // KUNCI UTAMA: Mengizinkan input text custom yang tidak ada di list
        placeholder: 'Ketik rute baru atau pilih yang ada...',
        allowClear: true,
        dropdownParent: $("#dialog_form_trucking"),
        ajax: {
            url: BASE_URL + "afg_c_dropdown/trucking_destinations",
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

    // 2. INIT JQGRID
    $("#grid_trucking").jqGrid({
        url: BASE_URL + 'pricelist/trucking/get_grid_data',
        datatype: "json",
        mtype: "POST",
        width: 'auto', autowidth: true, shrinkToFit: true,
        colNames: ['ID', 'Tujuan', 'CDD', 'FUSO', 'Wingbox', "20'", "40'"],
        colModel: [
            {name: 'id', index: 'id', hidden: true},
            {name: 'destination_name', index: 'destination_name', width: 250, search: true, searchoptions: {sopt:['cn']}},
            {name: 'cdd_price', index: 'cdd_price', width: 100, align: 'right'},
            {name: 'fuso_price', index: 'fuso_price', width: 100, align: 'right'},
            {name: 'wb_price', index: 'wb_price', width: 100, align: 'right'},
            {name: 'price_20', index: 'price_20', width: 100, align: 'right'},
            {name: 'price_40', index: 'price_40', width: 100, align: 'right'}
        ],
        rowNum: 20, rowList: [20, 50, 100],
        pager: '#pager_trucking', sortname: 'destination_name', viewrecords: true, sortorder: "asc",
        height: 500, rownumbers: true, caption: "Inland / Trucking Rates Database"
    });

    // Global Search
    var timer;
    $("#global_search_input").on('input', function() {
        var val = $(this).val();
        clearTimeout(timer);
        timer = setTimeout(function() {
            var filters = val ? { groupOp: "AND", rules: [ { field: "destination_name", op: "cn", data: val } ] } : "";
            $("#grid_trucking").jqGrid('setGridParam', { postData: { filters: JSON.stringify(filters) } }).trigger("reloadGrid", [{ page: 1 }]);
        }, 400);
    });

    // 3. DIALOG & CRUD ACTION
    $("#dialog_form_trucking").dialog({
        autoOpen: false, width: 600, modal: true,
        buttons: {
            "Simpan": function() {
                if(!$('#destination_name').val()) { Swal.fire('Error', 'Tujuan harus diisi!', 'error'); return; }
                
                $.post(BASE_URL + 'pricelist/trucking/save', $("#form_trucking").serialize(), function(res) {
                    var data = JSON.parse(res);
                    if(data.status === 'success') {
                        $("#dialog_form_trucking").dialog("close");
                        $("#grid_trucking").trigger("reloadGrid");
                        Swal.fire('Sukses', data.msg, 'success');
                    } else {
                        Swal.fire('Error', data.msg, 'error');
                    }
                });
            },
            "Batal": function() { $(this).dialog("close"); }
        }
    });

    // 2. SESUAIKAN TOMBOL ADD
    $("#btn_add").click(function() {
        $("#form_trucking")[0].reset();
        $("#id").val('');
        $('#destination_name').empty().trigger('change');
        $("#dialog_form_trucking").dialog("open");
    });

    // 3. SESUAIKAN TOMBOL EDIT
   $("#btn_edit").click(function() {
        var id = $("#grid_trucking").jqGrid('getGridParam', 'selrow');
        if (!id) { Swal.fire('Peringatan', 'Pilih baris yang akan diedit!', 'warning'); return; }
        
        $.post(BASE_URL + 'pricelist/trucking/get_data_edit', {id: id}, function(res) {
            var data = JSON.parse(res);
            $("#id").val(data.id);
            
            // Filter nilai: Jika 0 atau null, setel form menjadi kosong ('')
            $("#cdd_price").val(data.cdd_price == 0 ? '' : data.cdd_price);
            $("#fuso_price").val(data.fuso_price == 0 ? '' : data.fuso_price);
            $("#wb_price").val(data.wb_price == 0 ? '' : data.wb_price);
            $("#price_20").val(data.price_20 == 0 ? '' : data.price_20);
            $("#price_40").val(data.price_40 == 0 ? '' : data.price_40);
            
            // Set Select2 Option Text
            var opt = new Option(data.destination_name, data.destination_name, true, true);
            $('#destination_name').empty().append(opt).trigger('change');
            
            $("#dialog_form_trucking").dialog("open");
        });
    });

    $("#btn_delete").click(function() {
        var id = $("#grid_trucking").jqGrid('getGridParam', 'selrow');
        if (!id) { Swal.fire('Peringatan', 'Pilih baris yang akan dihapus!', 'warning'); return; }
        
        Swal.fire({
            title: 'Hapus Rute?', text: "Data trucking ini akan dihapus permanen!", icon: 'warning',
            showCancelButton: true, confirmButtonText: 'Ya, Hapus!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post(BASE_URL + 'pricelist/trucking/delete', {id: id}, function(res) {
                    $("#grid_trucking").trigger("reloadGrid");
                    Swal.fire('Terhapus!', 'Data berhasil dihapus.', 'success');
                });
            }
        });
    });
});