$(document).ready(function() {
    // 1. Inisialisasi jqGrid
    $("#grid_ts").jqGrid({
        url: BASE_URL + 'pricelist/transhipment/get_grid_data',
        datatype: "json", 
        mtype: "POST", 
        autowidth: true, 
        height: 500, 
        rownumbers: true,
        colNames: ['ID', 'Type', 'Destination', 'Country', 'Via Port', 'Rate / WM', '1st TT', '2nd TT', 'DG3', 'DG2'],
        colModel: [
            {name: 'id', hidden: true},
            {name: 'ts_type', width: 80, align: 'center'},
            {name: 'destination', width: 150},
            {name: 'country', width: 120},
            {name: 'via_port', width: 120},
            {name: 'freight_wm', width: 90, align: 'right'},
            {name: 'transit_time_1st', width: 70, align: 'center'},
            {name: 'transit_time_2nd', width: 70, align: 'center'},
            {name: 'dg_class3', width: 80, align: 'center'},
            {name: 'dg_class2', width: 80, align: 'center'}
        ],
        rowNum: 50, 
        rowList: [50, 100, 200], 
        pager: '#pager_ts', 
        sortname: 'destination', 
        viewrecords: true
    });

    // 2. Fitur Filter Tab & Cari Global
    function reloadGrid() {
        var tipe = $("#filter_type").val();
        var teks = $("#global_search").val();
        var rules = [];
        if(tipe) rules.push({field: "ts_type", op: "eq", data: tipe});
        if(teks) rules.push({field: "destination", op: "cn", data: teks});
        
        var filters = rules.length > 0 ? {groupOp: "AND", rules: rules} : "";
        $("#grid_ts").jqGrid('setGridParam', {postData: {filters: JSON.stringify(filters)}, page:1}).trigger("reloadGrid");
    }
    $("#filter_type, #global_search").on('change keyup', reloadGrid);

    // 3. Konfigurasi Modal Dialog jQuery UI
    $("#dialog_form_ts").dialog({
        autoOpen: false, 
        width: 750, 
        modal: true,
        buttons: {
            "Simpan": function() {
                $.post(BASE_URL + 'pricelist/transhipment/save', $("#form_ts").serialize(), function(res) {
                    $("#dialog_form_ts").dialog("close");
                    $("#grid_ts").trigger("reloadGrid");
                    Swal.fire('Sukses', 'Data berhasil disimpan', 'success');
                }, 'json').fail(function() {
                    Swal.fire('Error', 'Terjadi kesalahan pada server', 'error');
                });
            },
            "Batal": function() { 
                $(this).dialog("close"); 
            }
        }
    });

    // 4. Inisialisasi Select2 untuk Port (Destination & Via Port)
    var select2Options = {
        dropdownParent: $("#dialog_form_ts"),
        placeholder: 'Ketik nama port / kota...',
        allowClear: true,
        ajax: {
            url: BASE_URL + 'pricelist/transhipment/get_ports',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return { q: params.term || '' };
            },
            processResults: function (data) {
                return { results: data };
            }
        }
    };

    $("#destination").select2(select2Options);
    $("#via_port").select2(select2Options);

    // 5. Auto-fill Country (Destination & Via Port)
    $('#destination').on('select2:select', function (e) {
        $('#country').val(e.params.data.country_id);
    });
    $('#via_port').on('select2:select', function (e) {
        // Mengisi otomatis teks Negara Via Port saat port dipilih
        $('#via_country').val(e.params.data.country_id);
    });

    // 6. Sinkronisasi Nilai 1st Transit Time antar dua input form
    $('#transit_time_1st_trans').on('input', function() {
        $('#transit_time_1st_pod').val($(this).val());
    });
    $('#transit_time_1st_pod').on('input', function() {
        $('#transit_time_1st_trans').val($(this).val());
    });

    // 7. Fungsi Utama Pengatur Layout Form Berdasarkan Via Port
    function adjustTransitTimeLayout(viaVal) {
        if (viaVal && viaVal.toUpperCase() !== 'DIRECT' && viaVal.trim() !== '') {
            // Jika ada Via Port (Bukan Direct): Tampilkan baris transhipment lanjutan (Negara via, 1st & 2nd TT)
            $('#wrap_trans_time_row').show();
            $('#wrap_tt_1st_pod_row').hide();
        } else {
            // Jika Direct atau Kosong: Tampilkan 1st TT di Final POD, sembunyikan baris transhipment lanjutan
            $('#wrap_trans_time_row').hide();
            $('#via_country, #transit_time_2nd, #transit_time_1st_trans').val('');
            $('#wrap_tt_1st_pod_row').show();
        }
    }

    // Event saat Via Port diubah secara manual
    $('#via_port').on('change', function() {
        adjustTransitTimeLayout($(this).val());
    });

    // 8. Tombol Tambah (Add Data)
    $("#btn_add").click(function() {
        $("#form_ts")[0].reset(); 
        $("#id").val('');
        
        // Reset Select2 dan input terkait
        $('#destination').empty().trigger('change');
        $('#via_port').empty().trigger('change');
        $('#country, #via_country').val('');
        
        // Default awal anggap Direct (kosong)
        adjustTransitTimeLayout('');
        
        $("#dialog_form_ts").dialog("open");
    });

    // 9. Tombol Edit (Modify Data)
    $("#btn_edit").click(function() {
        var id = $("#grid_ts").jqGrid('getGridParam', 'selrow');
        if(!id) { 
            Swal.fire('Error', 'Pilih data yang ingin diedit!', 'error'); 
            return; 
        }
        
        $.post(BASE_URL + 'pricelist/transhipment/get_data_edit', {id: id}, function(res) {
            var d = JSON.parse(res);
            
            // Masukkan data ke masing-masing elemen input berdasarkan ID/Name
            $.each(d, function(key, val) { 
                $("#" + key).val(val); 
            });
            
            // Sinkronkan nilai 1st transit time ke dua input form
            $('#transit_time_1st_trans, #transit_time_1st_pod').val(d.transit_time_1st);
            
            // Kosongkan via_country text (karena tidak disimpan di tabel utama)
            $("#via_country").val('');

            // Set Select2 Option untuk Destination
            if (d.destination) {
                var optDest = new Option(d.destination, d.destination, true, true);
                $('#destination').empty().append(optDest).trigger('change');
            } else {
                $('#destination').empty().trigger('change');
            }
            
            // Set Select2 Option untuk Via Port & Sesuaikan Layout Form-nya
            if (d.via_port) {
                var optVia = new Option(d.via_port, d.via_port, true, true);
                $('#via_port').empty().append(optVia).trigger('change');
                adjustTransitTimeLayout(d.via_port);
            } else {
                $('#via_port').empty().trigger('change');
                adjustTransitTimeLayout('');
            }
            
            $("#dialog_form_ts").dialog("open");
        });
    });

    // 10. Tombol Hapus (Delete Data)
    $("#btn_delete").click(function() {
        var id = $("#grid_ts").jqGrid('getGridParam', 'selrow');
        if(!id) {
            Swal.fire('Error', 'Pilih data yang ingin dihapus!', 'error');
            return;
        }
        
        Swal.fire({
            title: 'Hapus Data?', 
            text: "Data yang dihapus tidak dapat dikembalikan!", 
            icon: 'warning', 
            showCancelButton: true,
            confirmButtonText: 'Ya, Hapus!'
        }).then((r) => {
            if(r.isConfirmed) {
                $.post(BASE_URL + 'pricelist/transhipment/delete', {id: id}, function() {
                    $("#grid_ts").trigger("reloadGrid");
                    Swal.fire('Terhapus!', 'Data berhasil dihapus.', 'success');
                });
            }
        });
    });
});