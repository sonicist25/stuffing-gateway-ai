// Variable global untuk merekam tombol keyboard terakhir
window.lastKeyCodePressed = null;

/*
 * FIX LOCK_JOB: lock_job hanya boleh diubah oleh rule 90 hari / form Job Sheet
 * (afg_c_job_sheet). Kolom hidden 'lock_job' di grid adalah SNAPSHOT saat grid
 * di-load, jadi jangan pernah ikut dikirim ke afg_c_stuffing_plan/saving_data.
 * Kalau ikut terkirim, nilai lama ('1') akan menimpa hasil unlock ('2').
 */
function sp_strip_lock_fields(myData) {
    if (myData && typeof myData === 'object') {
        delete myData.lock_job;
    }
    return myData;
}

$(document)
    .off('keydown.lastKeyTracker')
    .on('keydown.lastKeyTracker', function(e) {
        window.lastKeyCodePressed = e.which || e.keyCode;
    });

// Satu-satunya handler ADD NEW DETAIL.
// Action selalu dihitung dari job yang sedang dibuka agar tidak mewarisi state job lain.
function gclDetailActionFromJob() {
    var code = $.trim($('#txtRefno2').val() || '');
    var matched = code.match(/^[^-]+-(\d{3})/);
    var jobType = matched ? matched[1] : code.substr(4, 3);

    if (jobType === '100' || jobType === '102') return 'select';
    if (jobType === '101') return 'save-track';
    return 'save';
}

$(document)
    .off('click.gclAddDetail', '#btnAddDetail')
    .on('click.gclAddDetail', '#btnAddDetail', function (e) {
        e.preventDefault();

        var code = $.trim($('#txtRefno2').val() || '');
        var spId = $.trim($('#spId').val() || '');
        var action = gclDetailActionFromJob();

        if (!code || !spId) {
            Swal.fire({ icon: 'warning', title: 'Pilih Job', text: 'Buka job terlebih dahulu.' });
            return false;
        }

        var etd = $('#txtETDJKT').val();
        var eta = $('#txtETA').val();
        var port = $('#dropdownPortLoading').val();
        var country = $('#dropdownCountries').val();
        if (!etd || !eta || !port || !country) {
            Swal.fire({
                icon: 'error',
                title: 'Data Belum Lengkap',
                text: 'Harap isi ETD, ETA, Port Loading, dan Destination terlebih dahulu!'
            });
            return false;
        }

        // -100/-102: pilih SI. Vessel/Voyage/Coloader tidak wajib untuk membuka daftar.
        if (action === 'select') {
            SelectShipmentRequest();
            return false;
        }

        // -101: simpan detail dengan validasi pelayaran & kontainer.
        if (action === 'save-track') {
            if (!$('#txtVessel').val() || !$('#txtVoyage').val() || !$('#txtCarrier').val()) {
                Swal.fire({
                    icon: 'error', title: 'Data Pengiriman Belum Lengkap',
                    text: 'Harap lengkapi Vessel, Voyage, Carrier'
                });
                return false;
            }
            if (!$('#txtContSealNumber').val()) {
                Swal.fire({
                    icon: 'error', title: 'Data Container Belum Lengkap',
                    text: 'Harap isi Container/Seal Number!'
                });
                return false;
            }
        }

        save_new_detail();
        return false;
    });

function myFunction(data) {
    var tabel_name = $('#txtRefno2').val() || '';

    // contoh format diasumsikan: ABCD10125001
    // index 4-6  : kode (3 digit)
    // index 7-8  : tahun (2 digit, misal '25' -> 2025)
    if (tabel_name.length < 9) {
        // refno tidak valid, jangan lanjut
        return;
    }

    var cek_code_str = tabel_name.substr(4, 3);           // "101", "200", dst
    var cek_code = parseInt(cek_code_str, 10);            // 101, 200, ...
    var tahun_2digit = parseInt(tabel_name.substr(7, 2), 10); // 25, 26, ...
    var cek_tahun = 2000 + tahun_2digit;                  // 2025, 2026, ...

    var cek_periode = tahun_2digit; // kalau masih mau dipakai

    // ====== SET LABEL SESUAI KODE ======
    if (cek_code === 200 || cek_code === 600) {
        document.querySelector("#warehouse").innerHTML = "Warehouse";
        document.querySelector("#carriers").innerHTML = "Air Line";
        document.querySelector("#scac").innerHTML = "IATA Code";
        document.querySelector("#mbl").innerHTML = "MAWB";
        document.querySelector("#vessels").innerHTML = "Flight No.";
        document.querySelector("#voyage").innerHTML = "Aircraft Type";
        document.querySelector("#stuffing_date").innerHTML = "Loading Date";
        document.querySelector("#pol").innerHTML = "Airport of Loading";
        document.querySelector("#trans").innerHTML = "Airport of Trans";
        document.querySelector("#pod").innerHTML = "Airport of Destination";

        var selectedValue = data ? data.carrier : null;

        $('select#txtCarrier').empty();

        $("#txtCarrier").each(function() {
            obj = ail;
            $.each(obj, function(key, value) {
                $('select#txtCarrier').append(
                    $('<option></option>').attr('value', key).text(value)
                );
            });
        });

        if (selectedValue) {
            $('#txtCarrier').val(selectedValue);
        }

    } else if (cek_code === 102 || cek_code === 302) {
        document.querySelector("#warehouse").innerHTML = "Warehouse";
        document.querySelector("#carriers").innerHTML = "Coloader";
        document.querySelector("#scac").innerHTML = "SCAC";
        document.querySelector("#mbl").innerHTML = "Shipping Line MBL";
        document.querySelector("#vessels").innerHTML = "Vessel";
        document.querySelector("#voyage").innerHTML = "Voyage";
        document.querySelector("#stuffing_date").innerHTML = "Stuffing Date";
        document.querySelector("#pol").innerHTML = "Port of Loading";
        document.querySelector("#trans").innerHTML = "Port of Trans";
        document.querySelector("#pod").innerHTML = "Port of Destination";

        var selectedValue = data ? data.carrier : null;

        $('select#txtCarrier').empty();
        $('select#txtCarrier').append($('<option>', {
            value: '',
            text: ''
        }));

        $("#txtCarrier").each(function() {
            obj = cri;
            $.each(obj, function(key, value) {
                $('select#txtCarrier').append(
                    $('<option></option>').attr('value', key).text(value)
                );
            });
        });

        if (selectedValue) {
            $('#txtCarrier').val(selectedValue);
        }

    } else if (cek_code === 101 || cek_code === 301 || cek_code === 401) {
        document.querySelector("#warehouse").innerHTML = "Terminal Petikemas";
        document.querySelector("#carriers").innerHTML = "Shipping Line";
        document.querySelector("#scac").innerHTML = "SCAC";
        document.querySelector("#mbl").innerHTML = "Shipping Line MBL";
        document.querySelector("#vessels").innerHTML = "Vessel";
        document.querySelector("#voyage").innerHTML = "Voyage";
        document.querySelector("#stuffing_date").innerHTML = "Loading/Unloading Date";
        document.querySelector("#pol").innerHTML = "Port of Loading";
        document.querySelector("#trans").innerHTML = "Port of Trans";
        document.querySelector("#pod").innerHTML = "Port of Destination";

        var selectedValue = data ? data.carrier : null;

        $('select#txtCarrier').empty();
        $('select#txtCarrier').append($('<option>', {
            value: '',
            text: ''
        }));

        $("#txtCarrier").each(function() {
            obj = cri;
            $.each(obj, function(key, value) {
                $('select#txtCarrier').append(
                    $('<option></option>').attr('value', key).text(value)
                );
            });
        });

        if (selectedValue) {
            $('#txtCarrier').val(selectedValue);
        }

    } else if (cek_code === 300 || cek_code === 400) {
        document.querySelector("#warehouse").innerHTML = "Warehouse";
        document.querySelector("#carriers").innerHTML = "Shipping Line";
        document.querySelector("#scac").innerHTML = "SCAC";
        document.querySelector("#mbl").innerHTML = "Shipping Line MBL";
        document.querySelector("#vessels").innerHTML = "Vessel";
        document.querySelector("#voyage").innerHTML = "Voyage";
        document.querySelector("#stuffing_date").innerHTML = "Stripping Date";
        document.querySelector("#pol").innerHTML = "Port of Loading";
        document.querySelector("#trans").innerHTML = "Port of Trans";
        document.querySelector("#pod").innerHTML = "Port of Destination";

        var selectedValue = data ? data.carrier : null;

        $('select#txtCarrier').empty();
        $('select#txtCarrier').append($('<option>', {
            value: '',
            text: ''
        }));

        $("#txtCarrier").each(function() {
            obj = cri;
            $.each(obj, function(key, value) {
                $('select#txtCarrier').append(
                    $('<option></option>').attr('value', key).text(value)
                );
            });
        });

        if (selectedValue) {
            $('#txtCarrier').val(selectedValue);
        }

    } else {
        document.querySelector("#warehouse").innerHTML = "Warehouse";
        document.querySelector("#carriers").innerHTML = "Shipping Line";
        document.querySelector("#scac").innerHTML = "SCAC";
        document.querySelector("#mbl").innerHTML = "Shipping Line MBL";
        document.querySelector("#vessels").innerHTML = "Vessel";
        document.querySelector("#voyage").innerHTML = "Voyage";
        document.querySelector("#stuffing_date").innerHTML = "Stuffing Date";
        document.querySelector("#pol").innerHTML = "Port of Loading";
        document.querySelector("#trans").innerHTML = "Port of Trans";
        document.querySelector("#pod").innerHTML = "Port of Destination";

        var selectedValue = data ? data.carrier : null;

        $('select#txtCarrier').empty();
        $('select#txtCarrier').append($('<option>', {
            value: '',
            text: ''
        }));

        $("#txtCarrier").each(function() {
            obj = cal;
            $.each(obj, function(key, value) {
                $('select#txtCarrier').append(
                    $('<option></option>').attr('value', key).text(value)
                );
            });
        });

        if (selectedValue) {
            $('#txtCarrier').val(selectedValue);
        }
    }

    // ====== SET PORT DROPDOWN BERDASARKAN TAHUN & KODE ======

    // Siapkan default values untuk select2
    var defaultValues;
    if (data) {
        defaultValues = {
            dropdownPortloading:     { id: data.port_loading,      text: data.port_loading },
            dropdownTranshipmentPort:{ id: data.transhipment_port, text: data.transhipment_port },
            dropdownCountries:       { id: data.destination,       text: data.destination }
        };
    } else {
        defaultValues = {
            dropdownPortloading:     { id: '', text: '' },
            dropdownTranshipmentPort:{ id: '', text: '' },
            dropdownCountries:       { id: '', text: '' }
        };
    }

    // 1) AIR baru (kode 200 / 600) dengan tahun >= 2025
    if (cek_tahun >= 2025 && (cek_code === 200 || cek_code === 600)) {

        $('#dropdownPortLoading').select2({
            placeholder: 'Select a port',
            width: '178px',
            ajax: {
                url: '/afg_c_stuffing_plan/fetch_dropdown_data_air',
                dataType: 'json',
                delay: 250,
                data: function(params) { return { q: params.term }; },
                processResults: function(data) { return { results: data }; }
            }
        }).append(
            new Option(defaultValues.dropdownPortloading.text, defaultValues.dropdownPortloading.id, true, true)
        ).trigger('change');

        $('#dropdownTranshipmentPort').select2({
            placeholder: '',
            width: '178px',
            ajax: {
                url: '/afg_c_stuffing_plan/fetch_dropdown_data_air',
                dataType: 'json',
                delay: 250,
                data: function(params) { return { q: params.term }; },
                processResults: function(data) { return { results: data }; }
            }
        }).append(
            new Option(defaultValues.dropdownTranshipmentPort.text, defaultValues.dropdownTranshipmentPort.id, true, true)
        ).trigger('change');

        $('#dropdownCountries').select2({
            placeholder: 'Select a country',
            width: '178px',
            ajax: {
                url: '/afg_c_stuffing_plan/fetch_dropdown_data_air',
                dataType: 'json',
                delay: 250,
                data: function(params) { return { q: params.term }; },
                processResults: function(data) { return { results: data }; }
            }
        }).append(
            new Option(defaultValues.dropdownCountries.text, defaultValues.dropdownCountries.id, true, true)
        ).trigger('change');

    // 2) OCEAN baru (100/101/102/300/301/302/400/401/402) tahun >= 2025
    } else if (
        cek_tahun >= 2025 &&
        [100,101,102,300,301,302,400,401,402].indexOf(cek_code) !== -1
    ) {

        $('#dropdownPortLoading').select2({
            placeholder: '',
            width: '178px',
            ajax: {
                url: '/afg_c_stuffing_plan/fetch_dropdown_data',
                dataType: 'json',
                delay: 250,
                data: function(params) { return { q: params.term }; },
                processResults: function(data) { return { results: data }; }
            }
        }).append(
            new Option(defaultValues.dropdownPortloading.text, defaultValues.dropdownPortloading.id, true, true)
        ).trigger('change');

        $('#dropdownTranshipmentPort').select2({
            placeholder: '',
            allowClear: true,
            width: '178px',
            ajax: {
                url: '/afg_c_stuffing_plan/fetch_dropdown_data',
                dataType: 'json',
                delay: 250,
                data: function(params) { return { q: params.term }; },
                processResults: function(data) { return { results: data }; }
            }
        }).append(
            new Option(defaultValues.dropdownTranshipmentPort.text, defaultValues.dropdownTranshipmentPort.id, true, true)
        ).trigger('change');

        $('#dropdownCountries').select2({
            placeholder: '',
            width: '178px',
            ajax: {
                url: '/afg_c_stuffing_plan/fetch_dropdown_data',
                dataType: 'json',
                delay: 250,
                data: function(params) { return { q: params.term }; },
                processResults: function(data) { return { results: data }; }
            }
        }).append(
            new Option(defaultValues.dropdownCountries.text, defaultValues.dropdownCountries.id, true, true)
        ).trigger('change');

    // 3) DATA JADUL (tahun < 2025) → pakai endpoint jadul
    } else {

        $('#dropdownPortLoading').select2({
            placeholder: 'Select a port',
            width: '178px',
            ajax: {
                url: '/afg_c_stuffing_plan/dropdown_port_jadul',
                dataType: 'json',
                delay: 250,
                data: function(params) { return { q: params.term }; },
                processResults: function(data) { return { results: data }; }
            }
        }).append(
            new Option(defaultValues.dropdownPortloading.text, defaultValues.dropdownPortloading.id, true, true)
        ).trigger('change');

        $('#dropdownTranshipmentPort').select2({
            placeholder: '',
            width: '178px',
            ajax: {
                url: '/afg_c_stuffing_plan/dropdown_port_jadul',
                dataType: 'json',
                delay: 250,
                data: function(params) { return { q: params.term }; },
                processResults: function(data) { return { results: data }; }
            }
        }).append(
            new Option(defaultValues.dropdownTranshipmentPort.text, defaultValues.dropdownTranshipmentPort.id, true, true)
        ).trigger('change');

        $('#dropdownCountries').select2({
            placeholder: 'Select a country',
            width: '178px',
            ajax: {
                url: '/afg_c_stuffing_plan/dropdown_port_jadul',
                dataType: 'json',
                delay: 250,
                data: function(params) { return { q: params.term }; },
                processResults: function(data) { return { results: data }; }
            }
        }).append(
            new Option(defaultValues.dropdownCountries.text, defaultValues.dropdownCountries.id, true, true)
        ).trigger('change');

        // Re-init carrier pakai cri kalau branch ini dipakai
        var selectedValueCarrier = data ? data.carrier : null;
        $('select#txtCarrier').empty();
        $('select#txtCarrier').append($('<option>', {
            value: '',
            text: ''
        }));

        $("#txtCarrier").each(function() {
            obj = cri;
            $.each(obj, function(key, value) {
                $('select#txtCarrier').append(
                    $('<option>', { value: key, text: value })
                );
            });
        });

        if (selectedValueCarrier) {
            $('#txtCarrier').val(selectedValueCarrier);
        }
    }
}

var getColumnIndexByName = function(grid, columnName) {
    var cm = grid.jqGrid('getGridParam', 'colModel'), i, l;
    for (i = 1, l = cm.length; i < l; i += 1) {
        if (cm[i].name === columnName) {
            return i; // return the index
        }
    }
    return -1;
}; 

update_row_shipper=function(grid,val){
	var rowid = grid.jqGrid('getGridParam', 'selrow');
    $.ajax
	({
		type:"POST",
		url:base_url+'/afg_c_dropdown/update_row_shipper',
		data:
		{
			code: val,kodeRef:grid.jqGrid('getCell',rowid,'kode_ref'),
		},
		success:function(response)
		{
			var data = $.parseJSON(response);
			grid.jqGrid('setCell',rowid,'shipper',data.shipper);		
		},
	})
}


/* ============================================================
 * GCL Cargo Composition: satu job -100/-300, dihitung server-side
 * dari semua trnstuffingplandetail berdasarkan sales.
 * ============================================================ */
var gclCargoMixState = { xhr:null, seq:0, data:null, job:'' };

function gclCargoJobType(code) {
    var m = String(code || '').match(/^[^-]+-(100|300)/i);
    return m ? (m[1] === '100' ? 'export' : 'import') : null;
}

function gclCargoCurrentJob() {
    return { code: $.trim($('#txtRefno2').val() || ''), spid: $.trim($('#spId').val() || '') };
}

/* Sinkronkan lebar panel dengan form di kolom kiri. Tidak perlu margin
 * vertikal atau mengikuti tinggi panel tombol di kanan. */
function gclCargoAlignPanel() {
    var $panel = $('#gcl-cargo-mix'), $form = $('#form_input');
    if (!$panel.length || !$form.length || !$form[0].getBoundingClientRect) return;
    var rect = $form[0].getBoundingClientRect();
    var $column = $('#gcl-stuffing-left-column');
    var offset = 0;
    if ($column.length) {
        var colRect = $column[0].getBoundingClientRect();
        offset = Math.max(0, rect.left - colRect.left);
    }
    if (rect.width > 0) {
        $panel.css({ width: rect.width + 'px', marginLeft: offset + 'px', marginTop: '0px' });
    }
}
$(window).off('resize.gclCargoMix').on('resize.gclCargoMix', gclCargoAlignPanel);

/* Hanya job -100: heading WG/CTN diganti, tanpa mengubah data atau kolom SQL. */
function gclCargoSetWarehouseColumn(code) {
    var $grid = $('#tabel_sp_detail');
    var isConsolExport = gclCargoJobType(code) === 'export';
    $grid.toggleClass('gcl-cargo-receive-mode', isConsolExport);
    if ($grid.length && $grid[0].grid) {
        try {
            $grid.jqGrid('setLabel', 'wg_ctn', isConsolExport ? 'WAREHOUSE' : 'WG/CTN');
        } catch(e) { if (window.console) console.warn('Cargo warehouse header:',e); }
    }
}

function gclCargoResetPanel() {
    gclCargoMixState.seq++;
    if (gclCargoMixState.xhr && gclCargoMixState.xhr.readyState !== 4) {
        gclCargoMixState.xhr.abort();
    }
    gclCargoMixState.xhr = null;
    gclCargoMixState.data = null;
    gclCargoMixState.job = '';
    $('#gcl-cargo-mix').hide();
    $('#tabel_sp_detail tr.jqgrow').removeClass('gcl-cargo-wh-arrived gcl-cargo-wh-pending')
        .find('td[aria-describedby="tabel_sp_detail_wg_ctn"]').removeAttr('title aria-label')
        .find('.gcl-wh-tally-link').remove();
    gclCargoSetWarehouseColumn(null);
}

function gclCargoPreparePanel(code) {
    gclCargoResetPanel();
    var type = gclCargoJobType(code);
    if (!type) return;
    gclCargoSetWarehouseColumn(code);
    $('#gcl-mix-agent').text('Agent: -').removeClass('is-on-blue');
    $('#gcl-mix-gateway').text('Gateway: -').removeClass('is-on-blue');
    $('#gcl-mix-bar-agent, #gcl-mix-bar-gateway').css('width', '0%');
    gclCargoAlignPanel();
    $('#gcl-cargo-mix').show();
}

function gclCargoApplyWarehouseMarks() {
    var $grid = $('#tabel_sp_detail');
    $grid.find('tr.jqgrow').removeClass('gcl-cargo-wh-arrived gcl-cargo-wh-pending')
        .find('td[aria-describedby="tabel_sp_detail_wg_ctn"]').removeAttr('title aria-label')
        .find('.gcl-wh-tally-link').remove();
    var summary = gclCargoMixState.data;
    var job = gclCargoCurrentJob();
    gclCargoSetWarehouseColumn(job.code);
    if (!summary || summary.job_type !== 'export' || job.code !== summary.job_no) return;
    var ids = (summary.warehouse && summary.warehouse.arrived_detail_ids) || [];
    var dates = (summary.warehouse && summary.warehouse.arrived_detail_dates) || {};
    var arrived = {};
    $.each(ids, function(_, id) { arrived[String(id)] = true; });
    $grid.find('tr.jqgrow').each(function () {
        var id = String(this.id || '');
        var isArrived = !!arrived[id];
        $(this).addClass(isArrived ? 'gcl-cargo-wh-arrived' : 'gcl-cargo-wh-pending');
        var $cell = $(this).find('td[aria-describedby="tabel_sp_detail_wg_ctn"]')
            .attr('title', isArrived ? ('Diterima di gudang: ' + (dates[id] || 'tanggal tercatat')) : 'Belum tercatat diterima di gudang')
            .attr('aria-label', isArrived ? 'Received in warehouse' : 'Not received in warehouse');

        // Tally sheet per anak job; jangan pakai sp_id atau selrow.
        // Ikon hanya untuk cargo inbound dan user yang punya akses dokumen.
        if (isArrived && /^[1-9][0-9]*$/.test(id) &&
            $('#export').attr('data-can-tally-sheet') === '1') {
            $('<a>', {
                'class': 'gcl-wh-tally-link',
                href: gclTallySheetUrl(id),
                target: '_blank',
                rel: 'noopener noreferrer',
                title: 'Buka Tally Sheet PDF',
                'aria-label': 'Buka Tally Sheet PDF untuk detail ' + id
            }).on('click dblclick mousedown', function(e) {
                // Biarkan aksi default link, tetapi jangan aktifkan edit/select jqGrid.
                e.stopPropagation();
            }).appendTo($cell);
        }
    });
}

function gclCargoLoadSummary() {
    var job = gclCargoCurrentJob(), type = gclCargoJobType(job.code);
    if (!type || !job.spid || !$('#gcl-cargo-mix').length) {
        gclCargoResetPanel();
        return;
    }
    var requestKey = job.spid + '|' + job.code;
    if (gclCargoMixState.job !== requestKey) {
        gclCargoPreparePanel(job.code);
        gclCargoMixState.job = requestKey;
    }
    gclCargoAlignPanel();
    gclCargoSetWarehouseColumn(job.code);
    var seq = ++gclCargoMixState.seq;
    if (gclCargoMixState.xhr && gclCargoMixState.xhr.readyState !== 4) {
        gclCargoMixState.xhr.abort();
    }
    gclCargoMixState.xhr = $.ajax({
        url: base_url + '/afg_c_stuffing_plan/cargo_composition',
        type: 'POST', dataType: 'json',
        data: { sp_id: job.spid, kode_ref: job.code },
        success: function (response) {
            if (seq !== gclCargoMixState.seq ||
                gclCargoCurrentJob().code !== job.code ||
                gclCargoCurrentJob().spid !== job.spid) return;
            if (!response || response.status != 1 || !response.data) {
                gclCargoMixState.data = null;
                $('#gcl-mix-agent').text('Komposisi belum tersedia');
                $('#gcl-mix-gateway').empty();
                return;
            }
            var s = response.data;
            gclCargoMixState.data = s;
            // Persentase bar tetap berdasarkan JUMLAH anak job, bukan CBM.
            var fmt = function(p) { return Number(p || 0).toFixed(1).replace(/\.0$/, '') + '%'; };
            var fmtCbm = function(cbm) { return Number(cbm || 0).toFixed(3) + ' Cbm'; };
            var pctAgent = Math.max(0, Math.min(100, Number(s.agent_pct) || 0));
            var pctGateway = Math.max(0, Math.min(100, Number(s.gateway_pct) || 0));
            var cbmAgentPct = Number(s.agent_cbm_pct) || 0;
            var cbmGatewayPct = Number(s.gateway_cbm_pct) || 0;
            var agentText = s.agent_label + ' (Agent): ' + s.agent + ' (' + fmtCbm(s.agent_cbm) + ') • ' + fmt(pctAgent);
            var gatewayText = s.gateway_label + ' (Gateway): ' + s.gateway + ' (' + fmtCbm(s.gateway_cbm) + ') • ' + fmt(pctGateway);
            $('#gcl-mix-bar-agent').css('width', pctAgent + '%');
            $('#gcl-mix-bar-gateway').css('width', pctGateway + '%');
            $('#gcl-mix-agent').text(agentText)
                .attr('title', agentText + ' • share CBM: ' + fmt(cbmAgentPct))
                .toggleClass('is-on-blue', pctAgent > 30);
            $('#gcl-mix-gateway').text(gatewayText)
                .attr('title', gatewayText + ' • share CBM: ' + fmt(cbmGatewayPct))
                .toggleClass('is-on-blue', pctGateway < 30 && pctAgent > 50);
            $('#gcl-cargo-mix').attr('aria-label', agentText + '; ' + gatewayText);
            gclCargoApplyWarehouseMarks();
        },
        error: function(xhr, status) {
            if (status !== 'abort' && seq === gclCargoMixState.seq) {
                gclCargoMixState.data = null;
                $('#gcl-mix-agent').text('Gagal mengambil komposisi');
                $('#gcl-mix-gateway').empty();
            }
        }
    });
}

getStuffingPlan=function(modeInput) {
	//console.log($("#txtPelayaran").val());
	var cek = parseInt($("#txtRefno2").val().substr(4,3));
	$('#REQUEST').fadeOut('slow');
	$.ajax
		({
			async: true,
			type:"POST",
			url:base_url+'/afg_c_stuffing_plan/get_stuffing_plan',
			data:{ sp_id : $('#spId').val(), kode_ref : $("#txtRefno2").val(), mode : modeInput, cek : cek },
			success:function(response)
			{
				
				let data;
				console.log(data);
				try {
					data = $.parseJSON(response);
				} catch (e) {
					Swal.fire("Gagal parsing response dari server");
					console.error("Response tidak valid JSON:", response);
					return;
				}

				if (!data || typeof data !== 'object') {
					Swal.fire("Response kosong atau tidak sesuai format");
					console.warn("Data tidak valid:", data);
					return;
				}

				if (!data.kode_ref) {
					Swal.fire("Belum ada data");
					console.warn("Isi data:", data);
					return;
				}

				var filter = $('#spId').val();
				var tabel_name = data.kode_ref;
				// Asumsi variabel 'data' adalah hasil parsing JSON Anda saat edit
				var agentCode = data.agen_penagihan; // Hasilnya: "CHAHKG"

				if (agentCode && agentCode !== "") {
					// Karena JSON Anda hanya mengirimkan kode ("CHAHKG"), kita gunakan kode sebagai teks tampilannya
					var agentText = agentCode; 
					
					// Jika dari backend Anda bisa mengirimkan nama lengkapnya, Anda bisa menggantinya:
					// var agentText = agentCode + " - " + data.agen_penagihan_nama; 

					// 1. Buat elemen <option> baru (Format: text, value, defaultSelected, selected)
					var newOption = new Option(agentText, agentCode, true, true);
					
					// 2. Masukkan ke dalam dropdown dan trigger update Select2 UI
					$('#dropdownAgent').append(newOption).trigger('change');
				} else {
					// Jika data kosong, pastikan Select2 dikosongkan
					$('#dropdownAgent').val(null).trigger('change');
				}
				$('#spId').val(data.sp_id);
                gclCargoPreparePanel(data.kode_ref);
				$('#dropdownRefNo').val(data.kode_ref1);
				$('#combo_year').val('20'+data.kode_ref2);
				$('#combo_month').val(data.tanggal_job);
				$('#txtRefno2').val(data.kode_ref);
				
				setTimeout(update_stuffing_photos_button_label, 300); // reset label (centang lama sudah tidak relevan)
				$('#txtStuffingDate').val(data.stuffing_date);
				$('#txtMotherBL').val(data.mother_bl);
				$('#txtVessel').val(data.vessel);
				$('#txtVoyage').val(data.voyage);
				$('#txtClosingDate').val(data.closing_date_time);
				$('#txtClosingTime').val(data.closing_time);
				$('#txtETDJKT').val(data.etd_jkt);
				$('#txtATD').val(data.atd);
				$('#txtETA').val(data.eta);
				$('#txtATA').val(data.ata);
				$('#txtContSealNumber').val(data.cont_seal_no);
				$('#txtTOC1').val(data.type_cont_qty);
				$('#dropdownTypeOfTransaction').val(data.type_of_trans);
				$('#dropdownWarehouse').val(data.warehouse);
				$('#txtCarrier').val(data.carrier);
				$('#txtPelayaran').val(data.scac);
				$('#dropdownPortLoading').val(data.port_loading);
				$('#dropdownTranshipmentPort').val(data.transhipment_port);
				$('#dropdownCountries').val(data.destination);
				$('#txtTOC2').val(data.type_cont_size);
				$('#txtKurs').val(data.kurs);
				$('#txtSelling').val(data.kurs_selling);
				$('#txtStatus').val(data.posted);
				$('#txtAirLine').val(data.air_line);
				$('#txtMode').val('');	
				$('#firstId').val(data.first_id);	
				$('#lastId').val(data.last_id);	
				$('#scheduleId').val(data.schedule_id);
				myFunction(data);							
				checkRadioBtn(data.shipment_type_multi);	
				
				if(data.kode_ref.substr(4,3)== '300' || data.kode_ref.substr(4,3)== '301' || data.kode_ref.substr(4,3)== '302' ||data.kode_ref.substr(4,3)== '600'||data.kode_ref.substr(4,3)== '400'||data.kode_ref.substr(4,3)== '402'){
					$("#export").attr('hidden',true);
					$("#import").attr('hidden',false);
					var grid = $("#tabel_sp_detail_export"), i;
					grid.jqGrid('setGridParam',{datatype: 'json', postData:{code: data.kode_ref,}}).trigger("reloadGrid");
					getTabelStuffingPlanDetail('import');
				} else {
					// Reset/sembunyikan timeline kanan saat ganti master job
					// =========================================================
					$("#import").attr('hidden',true);
					$("#export").attr('hidden',false);
					var grid = $("#tabel_sp_detail"), i;
					grid.jqGrid('setGridParam',{datatype: 'json', postData:{code: data.kode_ref,}}).trigger("reloadGrid");
					getTabelStuffingPlanDetail('export');
					if(data.kode_ref.substr(4,3)== '100'){
					//get_booking();
					load_timeline_data(data.kode_ref);
					$('#gcl-right-timeline').hide(); 
					} 
				}
				
				$("input[id='btnNext']").attr('hidden',false)
				$("input[id='btnPrev']").attr('hidden',false)
				
				if(data.last_id==data.sp_id){
					$("input[id='btnPrev']").attr('hidden',true)
				}
				
				if(data.first_id==data.sp_id){
					$("input[id='btnNext']").attr('hidden',true)
				}
			},
			error:function(XMLHttpRequest)
			{
				Swal.fire("Query not excuted")		
			}
		})
}

edit_all=function() {
	$('#btnNew').attr('hidden',true);
	$('#btnEdit').attr('hidden',true);
	$('#btnDelete').val('SAVE').attr('onClick','button_save()');
	$('#btnBrowse').val('CANCEL').attr('onClick','cancel_stuff()');
	$('#btnPrev').attr('hidden',true);
	$('#btnNext').attr('hidden',true);
	
	//$('#combo_year').attr('disabled',false);
	$('#combo_month').attr('disabled',true);
	$('#dropdownRefNo').attr('disabled',false);
	$('#txtRefno2').attr('disabled',true);
	$('#dropdownTypeOfTransaction').attr('disabled',true);
	$('#txtStuffingDate').attr('disabled',false);
	$('#dropdownWarehouse').attr('disabled',false);
	$('#txtCarrier').attr('disabled',false);
	$('#txtPelayaran').attr('disabled',false);
	$('#txtMotherBL').attr('disabled',false);
	$('#txtVessel').attr('disabled',false);
	$('#txtVoyage').attr('disabled',false);
	$('#txtClosingDate').attr('disabled',false);
	$('#txtClosingTime').attr('disabled',false);
	$('#txtETDJKT').attr('disabled',false);
	$('#txtATD').attr('disabled',false);
	$('#txtETA').attr('disabled',false);
	$('#txtATA').attr('disabled',false);
	$('#txtContSealNumber').attr('disabled',false);
	$('#txtTOC1').attr('disabled',false);
	$('#txtTOC2').attr('disabled',false);
	$('#txtKurs').attr('disabled',false);
	$('#txtSelling').attr('disabled',false);
	$('#txtAirLine').attr('disabled',false);
	$('#dropdownPortLoading').attr('disabled',false);
	$('#dropdownTranshipmentPort').attr('disabled',false);
	$('#dropdownCountries').attr('disabled',false);
	
	var val_agn = $('#dropdownAgent').val();
	$('#dropdownAgent').attr('disabled',false).html('<option value="'+val_agn+'">'+val_agn+'</option>');
	
	$.each(agn_png, function(key, value) {
		$('#dropdownAgent').append($('<option></option>').attr('value',key).text(value))
	});
	$('#dropdownTranshipmentPort'). autocomplete({
        source: base_url+'/afg_c_dropdown/dropdown_port',
    });

	
	
	$('#tambahWarehouse').attr('disabled',false);
	$('#tambahPortLoading').attr('disabled',false);
	$('#tambahTranshipmentPort').attr('disabled',false);
	$('#tambahDestination').attr('disabled',false);
	$('#tambahAgent').attr('disabled',false);
	$('#tambahCount').attr('disabled',false);
	$('#tambahCountSeal').attr('disabled',false);
		
	$('#shipment-1').attr('disabled',false);
	$('#shipment-2').attr('disabled',false);
	$('#shipment-3').attr('disabled',false);
	$('#shipment-4').attr('disabled',false);	
	$('#shipment-5').attr('disabled',false);	
					
	$('#form_input').attr('style','background-color:#ddd');
	$('#form_input').removeAttr('title');
		
	$('#REQUEST').fadeOut();	
}





enabled_all=function() {
	$('#btnNew').attr('hidden',true);
	$('#btnEdit').attr('hidden',true);
	$('#btnDelete').val('SAVE').attr('onClick','button_save()');
	$('#btnBrowse').val('CANCEL').attr('onClick','cancel_stuff()');
	$('#btnPrev').attr('hidden',true);
	$('#btnNext').attr('hidden',true);
	
	//$('#combo_year').attr('disabled',false);
	$('#combo_month').attr('disabled',false);
	$('#dropdownRefNo').attr('disabled',false);
	$('#txtRefno2').attr('disabled',false);
	$('#dropdownTypeOfTransaction').attr('disabled',false);
	$('#txtStuffingDate').attr('disabled',false);
	$('#dropdownWarehouse').attr('disabled',false);
	$('#txtCarrier').attr('disabled',false);
	$('#txtPelayaran').attr('disabled',false);
	$('#txtMotherBL').attr('disabled',false);
	$('#txtVessel').attr('disabled',false);
	$('#txtVoyage').attr('disabled',false);
	$('#txtClosingDate').attr('disabled',false);
	$('#txtClosingTime').attr('disabled',false);
	$('#txtETDJKT').attr('disabled',false);
	$('#txtATD').attr('disabled',false);
	$('#txtETA').attr('disabled',false);
	$('#txtATA').attr('disabled',false);
	$('#txtContSealNumber').attr('disabled',false);
	$('#txtTOC1').attr('disabled',false);
	$('#txtTOC2').attr('disabled',false);
	$('#txtKurs').attr('disabled',false);
	$('#txtSelling').attr('disabled',false);
	$('#txtAirLine').attr('disabled',false);
	$('#dropdownPortLoading').attr('disabled',false);
	$('#dropdownTranshipmentPort').attr('disabled',false);
	$('#dropdownCountries').attr('disabled',false);
	
	var val_agn = $('#dropdownAgent').val();
	$('#dropdownAgent').attr('disabled',false).html('<option value="'+val_agn+'">'+val_agn+'</option>');
	
	$.each(agn_png, function(key, value) {
		$('#dropdownAgent').append($('<option></option>').attr('value',key).text(value))
	})
	
	
	
	$('#tambahWarehouse').attr('disabled',false);
	$('#tambahPortLoading').attr('disabled',false);
	$('#tambahTranshipmentPort').attr('disabled',false);
	$('#tambahDestination').attr('disabled',false);
	$('#tambahAgent').attr('disabled',false);
	$('#tambahCount').attr('disabled',false);
	$('#tambahCountSeal').attr('disabled',false);
		
	$('#shipment-1').attr('disabled',false);
	$('#shipment-2').attr('disabled',false);
	$('#shipment-3').attr('disabled',false);
	$('#shipment-4').attr('disabled',false);	
	$('#shipment-5').attr('disabled',false);	
					
	$('#form_input').attr('style','background-color:#ddd');
	$('#form_input').removeAttr('title');
		
	$('#REQUEST').fadeOut();	
}

function sp_detail_grid_type($grid) {
    return ($grid.attr('id') === 'tabel_sp_detail_export') ? 'import' : 'export';
}

function sp_detail_show_save_toast(type, xhr) {
    if (type === 'success') {
        Swal.fire({
            toast: true,
            position: 'bottom-end',
            showConfirmButton: false,
            timer: 1200,
            icon: 'success',
            title: 'Tersimpan!'
        });
        return;
    }

    Swal.fire({
        toast: true,
        position: 'bottom-end',
        showConfirmButton: false,
        timer: 2500,
        icon: 'error',
        title: 'Gagal simpan'
    });

    if (xhr && xhr.responseText) {
        console.log('saving_data error:', xhr.responseText);
    }
}

function sp_detail_commit_active_cell($grid) {
    if (!$grid || !$grid.length || !$grid[0].p) return;

    var p = $grid[0].p;

    /*
     * jqGrid cellEdit menyimpan posisi cell aktif di p.iRow dan p.iCol.
     * Ini lebih stabil daripada menebak rowIndex/cellIndex dari DOM ketika Enter ditekan.
     */
    if (p.iRow !== undefined && p.iRow !== null && p.iCol !== undefined && p.iCol !== null) {
        try {
            $grid.jqGrid('saveCell', p.iRow, p.iCol);
            return;
        } catch (e1) {
            console.log('saveCell by p.iRow/p.iCol gagal:', e1);
        }
    }

    // Fallback lama: tutup cell aktif.
    try {
        $grid.jqGrid('editCell', 0, 0, false);
    } catch (e2) {
        console.log('editCell close fallback gagal:', e2);
    }
}

function sp_detail_save_row($grid, rowid, showToast) {
    if (!$grid || !$grid.length || !rowid) return;

    var lockKey = 'spSaving_' + rowid;

    // Anti double-save ketika Enter + event jqGrid lain hampir bersamaan.
    if ($grid.data(lockKey)) return;
    $grid.data(lockKey, true);

    setTimeout(function() {
        var myData = sp_strip_lock_fields($grid.jqGrid('getRowData', rowid));
        var typeMode = sp_detail_grid_type($grid);

        $.ajax({
            type: 'POST',
            url: base_url + 'afg_c_stuffing_plan/saving_data',
            data: {
                id: rowid,
                myData: myData,
                type: typeMode
            },
            success: function() {
                if (showToast !== false) {
                    sp_detail_show_save_toast('success');
                }
            },
            error: function(xhr) {
                sp_detail_show_save_toast('error', xhr);
            },
            complete: function() {
                setTimeout(function() {
                    $grid.removeData(lockKey);
                }, 250);
            }
        });
    }, 120);
}

function bindEnterSaveForGrid(gridSelector) {
    var $grid = $(gridSelector);
    if (!$grid.length) return;

    window.__spDetailGridEnterBound = window.__spDetailGridEnterBound || {};

    var nsKey = gridSelector.replace(/[^a-zA-Z0-9_]/g, '_');
    if (window.__spDetailGridEnterBound[nsKey]) return;

    window.__spDetailGridEnterBound[nsKey] = true;

    /*
     * Pakai native capture listener.
     * Alasannya: jqGrid kadang menangkap Enter lebih dulu, sehingga delegated jQuery keydown
     * di table tidak sempat jalan. Capture listener jalan sebelum handler jqGrid.
     */
    document.addEventListener('keydown', function(e) {
        var key = e.which || e.keyCode;
        if (key !== 13) return;

        var target = e.target || e.srcElement;
        var $target = $(target);

        // Select2 punya flow sendiri: Enter memilih item, lalu save di select2:close.
        if ($target.hasClass('select2-search__field') || $target.closest('.select2-container').length) {
            return;
        }

        var $currentGrid = $(gridSelector);
        if (!$currentGrid.length) return;

        // Enter harus berasal dari dalam grid ini.
        if (!$target.closest(gridSelector).length) return;

        var $tr = $target.closest('tr.jqgrow');
        if (!$tr.length) return;

        var rowid = $tr.attr('id');
        if (!rowid) return;

        // Hanya save kalau row sedang dipilih/dicentang.
        var selectedRows = $currentGrid.jqGrid('getGridParam', 'selarrrow') || [];
        if ($.inArray(rowid, selectedRows) === -1) {
            return;
        }

        e.preventDefault();
        e.stopPropagation();
        if (typeof e.stopImmediatePropagation === 'function') {
            e.stopImmediatePropagation();
        }

        // Jangan biarkan afterSaveCell ikut membaca Enter dan double AJAX.
        window.lastKeyCodePressed = null;

        sp_detail_commit_active_cell($currentGrid);
        sp_detail_save_row($currentGrid, rowid, true);
    }, true);
}

// =========================================================================
// 1. FUNGSI GLOBAL PEMBERSIH HTML & PENYIMPAN DATABASE (ANTI-CORRUPT)
// =========================================================================
window.saveRowToDatabase = function(grid, rowid) {
    var myData = sp_strip_lock_fields(grid.jqGrid('getRowData', rowid));
    var cleanData = {};
    
    // Looping untuk membersihkan tag HTML bawaan jqGrid
    $.each(myData, function(key, val) {
        if (typeof val === 'string' && val.indexOf('<') > -1) {
            try {
                var $html = $($.parseHTML(val));
                if ($html.is('input[type="checkbox"]')) {
                    cleanData[key] = $html.is(':checked') ? '1' : '0'; // Ambil nilai checkbox
                } else if ($html.is('select') || $html.is('input')) {
                    cleanData[key] = $html.val(); // Ambil isi ketikan/pilihan
                } else if ($html.length > 0) {
                    cleanData[key] = $html.text();
                } else {
                    cleanData[key] = val; 
                }
            } catch(e) { cleanData[key] = val; }
        } else {
            cleanData[key] = val;
        }
    });

    var typeMode = (grid.attr('id') === 'tabel_sp_detail_export') ? 'import' : 'export';
    
    $.ajax({
        type: "POST",
        url: base_url + 'afg_c_stuffing_plan/saving_data',
        data: { id: rowid, myData: cleanData, type: typeMode },
        success: function() {
            Swal.fire({ toast: true, position: 'bottom-end', showConfirmButton: false, timer: 1500, icon: 'success', title: 'Tersimpan!' });
        }
    });
};

// =========================================================================
// 2. MASTER ENTER CATCHER UNTUK INPUT BIASA (QTY, WEIGHT, DLL)
// =========================================================================
$(document).off('keydown.gridSave').on('keydown.gridSave', '.editable', function(e) {
    if (e.which === 13) {
        // Biarkan jqGrid mengubah input kembali menjadi teks secara alami
        var $tr = $(this).closest('tr');
        var grid = $tr.closest('table');
        var rowid = $tr.attr('id');
        
        setTimeout(function() {
            window.saveRowToDatabase(grid, rowid);
        }, 100); 
    }
});
/* =====================================================================
 * TOTAL DI BAWAH GRID DETAIL  (POS / QTY / WEIGHT / SHIP MEAS / W/H MEAS / BL MEAS)
 * ---------------------------------------------------------------------
 * Bukan footerrow bawaan jqGrid, melainkan satu baris ramping tepat di bawah grid. Dihitung dari isi sel yang tampil,
 * jadi otomatis ikut berubah saat sel diedit, grid dimuat ulang, atau row ditambah/dihapus (dipantau MutationObserver).
 * Dipasang lewat SpTotals.bind('<id tabel>', data) di loadComplete #tabel_sp_detail dan #tabel_sp_detail_export.
 * ===================================================================== */
var SpTotals = (function () {
    var FIELDS = [
        { k: 'no_pack_qty', label: 'QTY',       min: 0, max: 0, unit: 'packages' },
        { k: 'weight',      label: 'WEIGHT',    min: 3, max: 3, unit: 'kg' },
        { k: 'ship_meas',   label: 'SHIP MEAS', min: 3, max: 4, unit: 'm³' },
        { k: 'wh_meas',     label: 'W/H MEAS',  min: 3, max: 4, unit: 'm³' },
        { k: 'bl_meas',     label: 'BL MEAS',   min: 3, max: 4, unit: 'm³' }
    ];
    var state = {};                  // id tabel -> { $panel, timer }

    function injectCss() {
        if (document.getElementById('sp-totals-css')) { return; }
        var css = [
            '.sp-tot{box-sizing:border-box;width:920px;max-width:100%;margin:4px auto 0;display:flex;flex-wrap:wrap;align-items:stretch;background:#fff;',
            'border:1px solid #d6dbe1;border-left:4px solid #FFCA33;border-radius:3px;text-align:left;}',
            '.sp-tot-head{flex:0 0 auto;padding:3px 10px;background:#f6f7f9;border-right:1px solid #e3e6ea;display:flex;align-items:center;gap:7px;white-space:nowrap;}',
            '.sp-tot-title{font-size:11px;font-weight:bold;letter-spacing:1px;color:#1f3a57;}',
            '.sp-tot-rows{font-size:11px;color:#5b6b7c;}',
            '.sp-tot-cell{flex:1 1 auto;padding:3px 12px;border-right:1px solid #eceff2;display:flex;align-items:baseline;gap:6px;white-space:nowrap;}',
            '.sp-tot-cell:last-child{border-right:0;}',
            '.sp-tot-lbl{font-size:9.5px;font-weight:bold;letter-spacing:.5px;color:#7a8793;text-transform:uppercase;}',
            '.sp-tot-val{font-size:13px;font-weight:bold;color:#1f3a57;font-variant-numeric:tabular-nums;line-height:20px;}',
            '.sp-tot-unit{font-size:10px;font-weight:normal;color:#7a8793;margin-left:3px;}',
            '@media (max-width:760px){.sp-tot-head{flex:1 0 100%;border-right:0;border-bottom:1px solid #e3e6ea;}.sp-tot-cell{flex:1 1 140px;}}'
        ].join('');
        $('<style id="sp-totals-css"></style>').text(css).appendTo('head');
    }

    /* "2 553.7800" / "1,516.000" / "" -> angka */
    function num(txt) {
        var n = parseFloat(String(txt == null ? '' : txt).replace(/[\s,]/g, ''));
        return isNaN(n) ? 0 : n;
    }

    function fmt(n, f) {
        n = Math.round(n * 10000) / 10000;           // buang sisa pecahan floating point
        return n.toLocaleString('en-US', { minimumFractionDigits: f.min, maximumFractionDigits: f.max });
    }

    /* Nilai sel: kalau sedang diedit (ada input) pakai isi input. */
    function cellVal($tr, gid, k) {
        var $td = $tr.children('td[aria-describedby="' + gid + '_' + k + '"]');
        var $in = $td.find('input,select,textarea');
        return $in.length ? $in.val() : $td.text();
    }

    /* Tampilan sel dibulatkan formatter (mis. tabel import: GW & SHIP MEAS 2 desimal), sedangkan DB menyimpan lebih teliti
       (dan cetak stuffing plan memakai nilai DB). Karena itu total memakai nilai asli dari respons server selama sel
       tidak diubah user; sel yang sudah diedit memakai angka yang tampil. */
    function rowVal(gid, st, $tr, k) {
        var shown = num(cellVal($tr, gid, k));
        var raw = st.raw[$tr.attr('id')];
        if (raw && raw[k] != null && raw[k] !== '') {
            var r = num(raw[k]), dp = st.dp[k], p = (dp == null) ? null : Math.pow(10, dp);
            var same = (p == null) ? Math.abs(r - shown) < 1e-9 : Math.abs(Math.round(r * p) / p - shown) < 1e-9;
            if (same) { return r; }
        }
        return shown;
    }

    /* Simpan nilai asli tiap row dari respons server (cell[] berurutan sesuai colModel, tanpa kolom cb/rn/subgrid). */
    function captureRaw(gid, st, data) {
        st.raw = {};
        var $grid = $('#' + gid), cm = $grid.jqGrid('getGridParam', 'colModel') || [], names = [];
        $.each(cm, function (i, c) { if (c.name !== 'cb' && c.name !== 'rn' && c.name !== 'subgrid') { names.push(c.name); } });
        $.each(FIELDS, function (i, f) {
            var c = $.grep(cm, function (x) { return x.name === f.k; })[0];
            st.dp[f.k] = (c && c.formatoptions && c.formatoptions.decimalPlaces != null) ? parseInt(c.formatoptions.decimalPlaces, 10) : null;
        });
        if (!data || !$.isArray(data.rows)) { return; }
        $.each(data.rows, function (i, r) {
            if (!r || r.id == null || !$.isArray(r.cell)) { return; }
            var o = {};
            $.each(FIELDS, function (j, f) { var ix = $.inArray(f.k, names); if (ix > -1) { o[f.k] = r.cell[ix]; } });
            st.raw[String(r.id)] = o;
        });
    }

    function build(gid) {
        var cells = '<div class="sp-tot-head"><span class="sp-tot-title">TOTAL</span><span class="sp-tot-rows" data-r="rows"></span></div>';
        $.each(FIELDS, function (i, f) {
            cells += '<div class="sp-tot-cell">' +
                '<span class="sp-tot-lbl">' + f.label + '</span>' +
                '<span class="sp-tot-val"><span data-r="' + f.k + '">0</span>' + (f.unit ? '<span class="sp-tot-unit">' + f.unit + '</span>' : '') + '</span>' +
                '</div>';
        });
        return $('<div class="sp-tot" id="sp-tot-' + gid + '" style="display:none" aria-live="polite">' + cells + '</div>');
    }

    function refresh(gid) {
        var st = state[gid];
        if (!st) { return; }
        var $grid = $('#' + gid), sums = {}, rows = 0;
        $.each(FIELDS, function (i, f) { sums[f.k] = 0; });

        $grid.find('tr.jqgrow').each(function () {
            var $tr = $(this);
            rows++;
            $.each(FIELDS, function (i, f) { sums[f.k] += rowVal(gid, st, $tr, f.k); });
        });

        st.$panel.toggle(rows > 0);
        st.$panel.css('width', ($('#gbox_' + gid).outerWidth() || 920) + 'px');
        if (!rows) { return; }

        st.$panel.find('[data-r="rows"]').text(rows + ' pos');
        $.each(FIELDS, function (i, f) { st.$panel.find('[data-r="' + f.k + '"]').text(fmt(sums[f.k], f)); });
    }

    function schedule(gid) {
        var st = state[gid];
        clearTimeout(st.timer);
        st.timer = setTimeout(function () { refresh(gid); }, 80);
    }

    return {
        /* Panggil dari loadComplete grid. Aman dipanggil berulang; panel dibuat sekali. */
        bind: function (gid, data) {
            try {
                if (!state[gid]) {
                    var $gbox = $('#gbox_' + gid);
                    if (!$gbox.length) { return; }
                    injectCss();
                    var $panel = build(gid);
                    $gbox.after($panel);
                    state[gid] = { $panel: $panel, timer: null, raw: {}, dp: {} };
                    if (window.MutationObserver) {
                        new MutationObserver(function () { schedule(gid); })
                            .observe(document.getElementById(gid), { childList: true, subtree: true, characterData: true });
                    }
                }
                captureRaw(gid, state[gid], data);
                refresh(gid);
            } catch (e) {
                if (window.console) { console.warn('SpTotals:', e); }
            }
        },
        refresh: refresh
    };
})();

/* update sales name */
getTabelStuffingPlanDetail=function(type) {
    var sp_id = $('#spId').val();
    var code  = $('#txtRefno2').val();
    if (type=='export') {
        var grid = $("#tabel_sp_detail"), i;
        grid.jqGrid(
        { 
            url:base_url+'/afg_c_stuffing_plan/get_stuffing_plan_detail',
            mtype : "post",
            postData:{code:code},
            datatype: "json", 
            colNames:
            [
            '', (gclCargoJobType(code) === 'export' ? 'RECEIVED IN WAREHOUSE' : 'WG/CTN'), 'SHIPPER', 'QTY', 'PACKAGING', 'DESCRIPTION OF GOODS',
            'WEIGHT', 'SHIP MEAS', 'W/H MEAS', 'W/H AGENT', 'BL MEAS', 'DESTINATION',
            'PEB', 'HB/L', 'AGENT CODE', 'FREIGHT', 'C/O', 'SHIPPING MARKS',
            '', '', '', 'BL NO', 'LOCK', 'NB', 'INCOTERM', 'HS CODE',
            ],
            colModel:
            [
            {name:'num',index:'num', width:20, align: "center", editable: false,
                cellattr: function(rowId, cellValue, rawObject, cm, rdata) {
                    if(rawObject[23] !== null && rawObject[23] !== '' ){
                        return 'class="Red"';
                    }else if (rawObject[22] == '0'){
                        return 'class="Blue"';
                    }
                }
            }, 
            {name:'wg_ctn',index:'wg_ctn', align:"center", width:124, editable: false,
                formatter:numFormat,
                // Anchor hanya dekorasi UI; getCell/getRowData tetap nilai numerik lama.
                unformat: function(cellvalue, options, cell) {
                    return $.trim($(cell).text());
                },
                cellattr: function(rowId, cellValue, rawObject, cm, rdata) {
                    if(rawObject[22]=='0'){ return 'class="Blue"'; }
                }
            },
            {name:'shipper_print',index:'shipper_print', width:250, editable: false, 
                edittype: "select",
                editoptions: {
                    value: { "": "" }, 
                    dataInit: function (element) {
                        var $elem = $(element);
                        var grid = $elem.closest('table.ui-jqgrid-btable');
                        var rowid = $elem.closest('tr').attr('id');
                        var isTabPressed = false;
						var isEnterPressed = false; // 1. TAMBAH VARIABEL INI
                        
                        $elem.select2({
                            placeholder: '', width: '100%', selectOnClose: true,
                            ajax: {
                                url: base_url + '/afg_c_dropdown/dropdown_shipper_print',
                                dataType: 'json', delay: 250,
                                data: function (params) { return { term: params.term }; },
                                processResults: function (data) {
                                    return { results: $.map(data, function(item) { return { id: item, text: item }; }) };
                                },
                                cache: true
                            }
                        });

                        setTimeout(function() { $elem.select2('open'); }, 50);

                        var isMouseSelected = false; // Tambahkan ini di bawah var isTabPressed

						$elem.on('select2:select', function (e) {
							var data = e.params.data;
							if ($elem.find("option[value='" + data.id + "']").length === 0) {
								$elem.append(new Option(data.text, data.id, true, true));
							}
							$elem.val(data.id);
							
							// Hanya jika kolom ini adalah shipper, jalankan trigger ini
							if ($elem.attr('name') === 'shipper_print') {
								update_row_shipper(grid, data.id);
							}
							isMouseSelected = true; // Tandai jika user mengklik pakai Mouse/Enter
						});

						$elem.on('select2:open', function() {
							var searchField = document.querySelector('.select2-search__field');
							if (searchField) {
								searchField.focus();
								$(searchField).off('keydown.tabCatch').on('keydown.tabCatch', function(e) {
									if (e.keyCode === 9 || e.key === 'Tab') {
										e.preventDefault(); 
										isTabPressed = true; 
										$elem.select2('close');
									} else if (e.keyCode === 13 || e.key === 'Enter') {
										isEnterPressed = true;
									}
								});
							}
						});

						$elem.on('select2:close', function() {
							var tr = $elem.closest('tr')[0];
							var td = $elem.closest('td')[0];
							var iRow = tr ? tr.rowIndex : null;
							var iCol = td ? td.cellIndex : null;

							setTimeout(function() {
								if (iRow !== null && iCol !== null) {
									try { grid.jqGrid("saveCell", iRow, iCol); } catch(e) {}
								}

								// JIKA DITEKAN TAB -> Pindah Kolom
								if (isTabPressed) {
									setTimeout(function() {
										var colModel = grid.jqGrid('getGridParam', 'colModel');
										var currentName = $elem.attr('name'); 
										var currIndex = colModel.findIndex(c => c.name === currentName);
										var nextColIndex = -1;
										for (var i = currIndex + 1; i < colModel.length; i++) {
											if (colModel[i].editable === true && colModel[i].hidden !== true) { 
												nextColIndex = i; break; 
											}
										}
										if (nextColIndex !== -1 && iRow !== null) { 
											grid.jqGrid("editCell", iRow, nextColIndex, true); 
										}
										isTabPressed = false; 
									}, 50);
								}
								
								// JIKA DITEKAN ENTER ATAU DIKLIK MOUSE -> SAVE KE DATABASE
								else if (isEnterPressed || isMouseSelected) {
									setTimeout(function() {
										window.saveRowToDatabase(grid, rowid);
										isEnterPressed = false;
										isMouseSelected = false;
									}, 100); 
								}
							}, 50);
						});
                    }
                },
                cellattr: function(rowId, cellValue, rawObject, cm, rdata) {
                    var statusIndex = (rawObject.length > 30) ? 37 : 22;
                    if(rawObject[statusIndex] == '0'){ return 'class="Blue"'; }
                }
            },
            {name:'no_pack_qty',index:'no_pack_qty', align:"center", width:30, editable: false, hidden: false,
                formatter: 'number',
                formatoptions: { decimalSeparator: ".", thousandsSeparator: " ", decimalPlaces: 0, defaultValue: '' },
                cellattr: function(rowId, cellValue, rawObject, cm, rdata) {
                    if(rawObject[22]=='0'){ return 'class="Blue"'; }
                }   
            },
            {name:'no_pack_unit', index:'no_pack_unit', width:80, editable: false,
                editoptions:{ dataInit: function(element) { $(element).css({ "text-transform": "uppercase","max-width":"80px"}) } },
                cellattr: function(rowId, cellValue, rawObject, cm, rdata) {
                    if(rawObject[22]=='0'){ return 'class="Blue"'; }
                }
            },
            {name:'desc_print',index:'desc_print', width:300, editable: false, 
                editoptions:{ dataInit: function(element) { $(element).css({ "text-transform": "uppercase","max-width":"300px"}) } },
                cellattr: function(rowId, cellValue, rawObject, cm, rdata) {
                    if(rawObject[22]=='0'){ return 'class="Blue" style="text-transform: uppercase"'; } else { return 'style="text-transform: uppercase"'; }
                }
            },
            {name:'weight',index:'weight', width:70, align:"center", editable: false,
                editoptions:{
                    dataInit: function(element) {
                        $(element).css({ "text-align": "center","max-width":"70px"}).attr({ "onChange":"format_number(this,3)","onKeyUp":"format_number(this,3)"});
                },
                }, 
                formatter: 'number', formatoptions: { decimalSeparator: ".", thousandsSeparator: " ", decimalPlaces: 3, defaultValue: '' },
                cellattr: function(rowId, cellValue, rawObject, cm, rdata) {
                    if(rawObject[22]=='0'){ return 'class="Blue"'; }
                }
            },
            {name:'ship_meas',index:'ship_meas', width:70, align:"center", editable: false,
                editoptions:{
                    dataInit: function(element) {
                        $(element).css({ "text-align": "center","max-width":"70px"}).attr({ "onChange":"format_number(this,4)","onKeyUp":"format_number(this,4)"});
                },
                }, 
                formatter: 'number', formatoptions: { decimalSeparator: ".", thousandsSeparator: " ", decimalPlaces: 4, defaultValue: '' },
                cellattr: function(rowId, cellValue, rawObject, cm, rdata) {
                    if(rawObject[22]=='0'){ return 'class="Blue"'; }
                }
            },
            {name:'wh_meas',index:'wh_meas', width:70, align:"center", editable: false,
                editoptions:{
					dataInit:function(element){

						$(element)
						.css({
							"text-align":"center",
							"max-width":"70px"
						})
						.attr({
							"onChange":"format_number(this,4)",
							"onKeyUp":"format_number(this,4)"
						});

						$(element).on('keydown', function(e){

							if(e.keyCode == 9){ // TAB
								e.preventDefault();

								var rowid = $(this).closest("tr.jqgrow").attr("id");
								var grid = $("#tabel_sp_detail");

								var cm = grid.jqGrid('getGridParam','colModel');

								var blIndex = cm.findIndex(function(col){
									return col.name === 'bl_meas';
								});

								setTimeout(function(){
									grid.jqGrid("editCell",
										$("#" + rowid)[0].rowIndex,
										blIndex,
										true
									);
								},10);
							}
						});
					}
				},
                formatter: 'number', formatoptions: { decimalSeparator: ".", thousandsSeparator: " ", decimalPlaces: 4, defaultValue: '' },
                cellattr: function(rowId, cellValue, rawObject, cm, rdata) {
                    if(rawObject[22]=='0'){ return 'class="Blue"'; }
                }
            },          
            {name:'wh_agent',index:'wh_agent', width:70, align:"center", editable: false, hidden: true,
                editoptions:{
                    dataInit: function(element) {
                        $(element).css({ "text-align": "center","max-width":"70px"}).attr({ "onChange":"format_number(this,4)","onKeyUp":"format_number(this,4)"});
                },
                }, 
                formatter: 'number', formatoptions: { decimalSeparator: ".", thousandsSeparator: " ", decimalPlaces: 4, defaultValue: '' },
                cellattr: function(rowId, cellValue, rawObject, cm, rdata) {
                    if(rawObject[22]=='0'){ return 'class="Blue"'; }
                }
            },
            {name:'bl_meas',index:'bl_meas', width:70, align:"center", editable: false, 
                editoptions:{
                    dataInit: function(element) {
                        $(element).css({ "text-align": "center","max-width":"70px"}).attr({ "onChange":"format_number(this,4)","onKeyUp":"format_number(this,4)"});
                },
                },
                formatter: 'number', formatoptions: { decimalSeparator: ".", thousandsSeparator: " ", decimalPlaces: 4, defaultValue: '' },
                cellattr: function(rowId, cellValue, rawObject, cm, rdata) {
                    if(rawObject[22]=='0'){ return 'class="Blue"'; }
                }
            },          
            {name:'destination',index:'destination', width:90, align:"LEFT", editable: false, 
                edittype: "select",
                editoptions: {
                    value: { "": "" },
                    dataInit: function (element) {
                        var $elem = $(element);
                        var grid = $elem.closest('table.ui-jqgrid-btable');
                        var rowid = $elem.closest('tr').attr('id');
                        var isTabPressed = false;
						var isEnterPressed = false; // 1. TAMBAH VARIABEL INI
                        
                        var rowData = grid.jqGrid('getRowData', rowid);
                        var kodeRef = rowData.kode_ref || $('#txtRefno2').val(); 
                        var dd_des = "/afg_c_dropdown/dropdown_port_jadul";
                        
                        if (kodeRef) {
                            var cekMode = kodeRef.substr(4,1);
                            if (cekMode == '1' || cekMode == '3' || cekMode == '4') { dd_des = "/afg_c_dropdown/dropdown_port"; } 
                            else if (cekMode == '2' || cekMode == '6') { dd_des = "/afg_c_dropdown/dropdown_airport"; }
                        }
                        
                        $elem.select2({
                            placeholder: '', width: '100%', selectOnClose: true,
                            ajax: {
                                url: base_url + dd_des, dataType: 'json', delay: 250,
                                data: function (params) { return { term: params.term }; },
                                processResults: function (data) { return { results: $.map(data, function(item) { return { id: item, text: item }; }) }; },
                                cache: true
                            }
                        });

                        setTimeout(function() { $elem.select2('open'); }, 50);

                        var isMouseSelected = false; // Tambahkan ini di bawah var isTabPressed

						$elem.on('select2:select', function (e) {
							var data = e.params.data;
							if ($elem.find("option[value='" + data.id + "']").length === 0) {
								$elem.append(new Option(data.text, data.id, true, true));
							}
							$elem.val(data.id);
							
							// Hanya jika kolom ini adalah shipper, jalankan trigger ini
							if ($elem.attr('name') === 'shipper_print') {
								update_row_shipper(grid, data.id);
							}
							isMouseSelected = true; // Tandai jika user mengklik pakai Mouse/Enter
						});

						$elem.on('select2:open', function() {
							var searchField = document.querySelector('.select2-search__field');
							if (searchField) {
								searchField.focus();
								$(searchField).off('keydown.tabCatch').on('keydown.tabCatch', function(e) {
									if (e.keyCode === 9 || e.key === 'Tab') {
										e.preventDefault(); 
										isTabPressed = true; 
										$elem.select2('close');
									} else if (e.keyCode === 13 || e.key === 'Enter') {
										isEnterPressed = true;
									}
								});
							}
						});

						$elem.on('select2:close', function() {
							var tr = $elem.closest('tr')[0];
							var td = $elem.closest('td')[0];
							var iRow = tr ? tr.rowIndex : null;
							var iCol = td ? td.cellIndex : null;

							setTimeout(function() {
								if (iRow !== null && iCol !== null) {
									try { grid.jqGrid("saveCell", iRow, iCol); } catch(e) {}
								}

								// JIKA DITEKAN TAB -> Pindah Kolom
								if (isTabPressed) {
									setTimeout(function() {
										var colModel = grid.jqGrid('getGridParam', 'colModel');
										var currentName = $elem.attr('name'); 
										var currIndex = colModel.findIndex(c => c.name === currentName);
										var nextColIndex = -1;
										for (var i = currIndex + 1; i < colModel.length; i++) {
											if (colModel[i].editable === true && colModel[i].hidden !== true) { 
												nextColIndex = i; break; 
											}
										}
										if (nextColIndex !== -1 && iRow !== null) { 
											grid.jqGrid("editCell", iRow, nextColIndex, true); 
										}
										isTabPressed = false; 
									}, 50);
								}
								
								// JIKA DITEKAN ENTER ATAU DIKLIK MOUSE -> SAVE KE DATABASE
								else if (isEnterPressed || isMouseSelected) {
									setTimeout(function() {
										window.saveRowToDatabase(grid, rowid);
										isEnterPressed = false;
										isMouseSelected = false;
									}, 100); 
								}
							}, 50);
						});
                    }
                },
                cellattr: function(rowId, cellValue, rawObject, cm, rdata) {
                    var statusIndex = (rawObject.length > 30) ? 37 : 22;
                    if(rawObject[statusIndex] == '0'){ return 'class="Blue"'; }
                }
            },
            {name:'doc',index:'doc', width:50, align:"center", editable: false,
                cellattr: function(rowId, cellValue, rawObject, cm, rdata) {
                    if(rawObject[22]=='0'){ return 'class="Blue"'; }
                },
                edittype:"select",
                editoptions:{
                    value:" : ;Y:Y;N:N",
                    dataInit: function(element) { $(element).css({ "text-align": "center","max-width":"30px","text-transform":"uppercase"}) },
                },
            },
            {name:'hbl',index:'hbl', width:50, align:"center", editable: false,
                edittype:"select",
                editoptions:{
                    value:" : ;T:T;F:F",
                    dataInit: function(element) { $(element).css({ "text-align": "center","max-width":"50px","text-transform":"uppercase"}) },
                },
                cellattr: function(rowId, cellValue, rawObject, cm, rdata) {
                    if(rawObject[22]=='0'){ return 'class="Blue"'; }
                }
            },
            {name:'agent_code',index:'agent_code', width:80, align:"left", editable: false, 
                edittype:"select",
                editoptions:{
                    dataUrl:base_url+'/afg_c_dropdown/dropdownAgent',
                    dataInit: function(element) { $(element).css({ "text-align": "left","max-width":"80px","text-transform":"uppercase"}) },
                },
                cellattr: function(rowId, cellValue, rawObject, cm, rdata) {
                    if(rawObject[22]=='0'){ return 'class="Blue"'; }
                }
            },
            {name:'freight',index:'freight', width:60, align:"center", editable: false,
                edittype:"select",
                editoptions:{
                    value:" : ;PREPAID:PREPAID;COLLECT:COLLECT",
                    dataInit: function(element) { $(element).css({ "text-align": "center","max-width":"60px","text-transform":"uppercase"}) },
                },
                formatter:'select',
                cellattr: function(rowId, cellValue, rawObject, cm, rdata) {
                    if(rawObject[22]=='0'){ return 'class="Blue"'; }
                }
            },          
            {name:'c_o',index:'c_o', width:30, align:"center", editable: false,
                cellattr: function(rowId, cellValue, rawObject, cm, rdata) {
                    if(rawObject[22]=='0'){ return 'class="Blue"'; }
                },
                edittype:"select",
                editoptions:{
                    value:" : ;Y:Y;N:N",
                    dataInit: function(element) { $(element).css({ "text-align": "center","max-width":"30px","text-transform":"uppercase"}) },
                },
            },
            {name:'shipping_mark',index:'shipping_mark', width:150, align:"left", editable: false,
                editoptions:{
                    dataInit: function(element) { $(element).css({ "text-align": "left","max-width":"150px","text-transform":"uppercase"}) },
                },
                cellattr: function(rowId, cellValue, rawObject, cm, rdata) {
                    if(rawObject[22]=='0'){ return 'class="Blue" style="text-transform: uppercase"'; } else { return 'style="text-transform: uppercase"'; }
                }
            },
            {name:'kode_ref',index:'kode_ref', width:10, hidden: true,},
            {name:'shipper',index:'shipper', width:10, hidden: true,},
            {name:'lock_job',index:'lock_job', width:30, hidden: true}, 
            {name:'hb_l',index:'hb_l', width:90, align:"center", editable: false,
                editoptions:{
                    dataInit: function(element) { $(element).css({ "text-transform": "uppercase", "max-width":"90" }) },
                },
                cellattr: function(rowId, cellValue, rawObject, cm, rdata) {
                    if(rawObject[22]=='0'){ return 'class="Blue" style="text-transform: uppercase"'; } else { return 'style="text-transform: uppercase"'; }
                }
            },
            {name:'isLock', index: 'isLock',width:40, editable: false, hidden: false, align:"center",   
            edittype:"checkbox", editoptions:{value: "1:0"},
            formatter:"checkbox",formatoptions:{disabled:false}
            },
            {name:'backup_date',index:'backup_date', width:30, hidden: true},                       
            {name:'incoterms',index:'incoterms', width:80, hidden: false,
                cellattr: function(rowId, cellValue, rawObject, cm, rdata) {
                    if(rawObject[23] !== null && rawObject[23] !== '' ){ return 'class="Red"'; }
                    else if (rawObject[22] == '0'){ return 'class="Blue"'; }
                }
            },
            {
				name:'hs_code',
				index:'hs_code',
				width:100,
				align:'left',
				editable:false,

				editoptions:{
					dataInit:function(element) {
						$(element).css({
							"text-transform":"uppercase",
							"max-width":"100px"
						});
					}
				},

				cellattr:function(rowId, cellValue, rawObject, cm, rdata) {
					if(rawObject[22] == '0') {
						return 'class="Blue" style="text-transform: uppercase"';
					}

					return 'style="text-transform: uppercase"';
				}
			},                      
        ], 
        viewrecords: true, multiselect: true, hiddengrid : false, shrinkToFit: false,
        modal: false, width: 920, height: 200, rowNum: 100, rowList:[10,20,30,40,50],
        gridview: true, caption:"Table Stuffingplan Detail", cellEdit:true,
        cellsubmit:'clientArray', pager:'#pagination_sp',
            
        afterSaveCell: function(rowid, cellname, value, iRow, iCol) {
		},
							
        loadComplete: function (data) {
			bindEnterSaveForGrid('#tabel_sp_detail');
			SpTotals.bind('tabel_sp_detail', data);
            gclCargoLoadSummary();
            var iCol = getColumnIndexByName($(this), 'isLock'), rows = this.rows, i, c = rows.length;
            for (i = 1; i < c; i += 1) {
                var rowid = rows[i].id;
                $(rows[i]).addClass('row-readonly'); // Memasang class langsung ke DOM
                
                var $checkboxLock = $("input", rows[i].cells[iCol]);
                $checkboxLock.prop('disabled', true); // Matikan Lock by default

                $checkboxLock.click(function (e) {
                    var clickedRowId = $(e.target).closest('tr').attr('id'); 
                    $('#tabel_sp_detail').jqGrid("editCell", 0, 0, false);
                    
                    if (clickedRowId != null){
                        var myData = sp_strip_lock_fields(grid.getRowData(clickedRowId));
                        $.ajax({
                            type: "POST",
                            url: base_url + 'afg_c_stuffing_plan/saving_data',
                            data: { id: clickedRowId, myData: myData, type: 'export' },
                            success: function (response) {
                                var data = $.parseJSON(response);
                                var isChecked = $(e.target).is(':checked');
                                Swal.fire('Anak job sudah di ' + (isChecked ? 'lock' : 'unlock'));
                                $('#tabel_sp_detail').trigger('reloadGrid');
                            },
                            error: function (response) {
                                alert("Please select a row to edit.");
                                $('#tabel_sp_detail').trigger('reloadGrid');
                            }
                        });
                    } else {
                        alert("Please select a row to edit.");
                        $('#tabel_sp_detail').trigger('reloadGrid');
                    }
                });
            }
            $("#cb_" + grid[0].id).hide();
            disable_bl_button(sp_id);
        },

        beforeSelectRow: function(rowid, e) {
            var lock = grid.jqGrid('getCell', rowid, 'isLock');
            var selRows = grid.jqGrid('getGridParam', 'selarrrow');
            var isChecked = ($.inArray(rowid, selRows) > -1);

            var cols = [
				'shipper_print', 'no_pack_qty', 'no_pack_unit', 'desc_print', 
				'weight', 'ship_meas', 'wh_meas', 'wh_agent', 'bl_meas', 'cost_meas',
				'destination', 'doc', 'hbl', 'hb_l', 'agent_code', 'freight', 'c_o', 
				'shipping_mark', 'incoterms', 'hs_code'
			];

            if(lock == '1' || !isChecked) {
                for(var i = 0; i < cols.length; i++) { $(this).jqGrid('setColProp', cols[i], { editable: false }); }
            } else {
                for(var i = 0; i < cols.length; i++) { $(this).jqGrid('setColProp', cols[i], { editable: true }); }
            }

            var cbsdis = $("tr#" + rowid + ".jqgrow > td > input.cbox:disabled", grid[0]);
            if (cbsdis.length === 0) { return true; } else { return false; }
        },

        onSelectRow: function(rowid, status){
			var grid = $(this);
    
			// ==========================================
			// 1. BLOK ANTI MULTI-SELECT
			// ==========================================
			if (status) {
				var selRows = grid.jqGrid('getGridParam', 'selarrrow').slice();
				for (var k = 0; k < selRows.length; k++) {
					if (selRows[k] !== rowid) {
						// Hilangkan centang baris lain (Otomatis akan memicu Save Opsi 1 untuk baris tersebut)
						grid.jqGrid('setSelection', selRows[k], false);
					}
				}
			}
			// ==========================================
            var selected = $("tr.jqgrow > td > input.cbox:checked", grid[0]);       
            selected[rowid] = status;
            
            var cols = [
				'shipper_print', 'no_pack_qty', 'no_pack_unit', 'desc_print', 
				'weight', 'ship_meas', 'wh_meas', 'wh_agent', 'bl_meas', 'cost_meas',
				'destination', 'doc', 'hbl', 'hb_l', 'agent_code', 'freight', 'c_o', 
				'shipping_mark', 'incoterms', 'hs_code'
			];

            var iColLock = getColumnIndexByName($(this), 'isLock');
            var tr = grid[0].rows.namedItem(rowid);

            if(status) { 
                var lock = grid.jqGrid('getCell', rowid, 'isLock');
                if (lock != '1') {
                    $("#" + rowid).removeClass('row-readonly'); // Hidupkan warna
                    for(var i = 0; i < cols.length; i++) { $(this).jqGrid('setColProp', cols[i], { editable: true }); }
                }
                $("td:eq(" + iColLock + ") input", tr).prop('disabled', false);

                disabled_top_button();
                var kode_ref = grid.jqGrid('getCell', rowid, 'kode_ref');
                ready_detail(rowid, kode_ref);     
            } else {
                $("#" + rowid).addClass('row-readonly'); // Matikan warna
                for(var i = 0; i < cols.length; i++) { $(this).jqGrid('setColProp', cols[i], { editable: false }); }
                $("td:eq(" + iColLock + ") input", tr).prop('disabled', true);

                enabled_top_button(); 
                remove_detail(); 

                // PATCH OPSI 1: Save massal ke server saat baris UNCHECK
                // Tutup cell edit yang masih aktif jika ada, supaya valuenya masuk ke data
                try { $('#tabel_sp_detail').jqGrid("editCell", 0, 0, false); } catch(e){}
                
                var myData = sp_strip_lock_fields(grid.jqGrid('getRowData', rowid));
                $.ajax({
                    type: "POST",
                    url: base_url + 'afg_c_stuffing_plan/saving_data',
                    data: { id: rowid, myData: myData, type: 'export' },
                    success: function () { gclCargoLoadSummary(); }
                });           
            }
        },
        
        }).navGrid('#pagination_sp',{ edit: false, add: false, del: false, search: false },
            { height: 'auto', width: 'auto', closeAfterEdit: true, reloadAfterEdit : true, viewPagerButtons: false, modal: true, editCaption: "Edit Data Stuffing", 
                afterSubmit: function () {
                    grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
                    return [true];
                },
                beforeShowForm: function(frm) {
                    $( '#num' ).attr('hidden',true);
                    var rowid = grid.getGridParam('selrow');
                    var rowCount = grid.getGridParam("reccount")-1;
                    var num = String.fromCharCode(65+rowCount);
                    var abjad = grid.jqGrid('getCell',rowid,'num');
                    if ( num == abjad ) { }
                }
            },
        { height: 'auto', width: 'auto', closeAfterAdd: true, reloadAfterAdd : true, viewPagerButtons: false, modal: true, addCaption: "Add Manual Request Stuffing",
                beforeShowForm: function(frm) {
                    $( '#buttonPindahJob' ).attr('hidden',false);
                    var stuf_id = $("#spId").val();
                    var code =  $("#txtRefno2").val();
                    $( '#sp_id' ).val(stuf_id);
                    $( '#code' ).val(code);
                    
                    var rowCount = $("#tabel_sp_detail").getGridParam("reccount");
                    var num = String.fromCharCode(65+rowCount);
                    var rowCount2 = rowCount-26;
                    if(rowCount >= 26) { num = 'Z'+String.fromCharCode(65+rowCount2); }
                    var kode_ref = $("#txtRefno2").val();
					// kalau num kosong/undefined, default ke "A"
					if (!num) {
						num = "A";
					}
                    $( '#num' ).val(num);
                    $( '#num' ).attr('hidden',true);    
                    $( '#kode_ref' ).val(kode_ref+''+num);
                }, 
                afterSubmit: function () {
                    $('#tabel_sp_detail').jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
                    return [true];
                }
        },
            {height: "auto", width: 280},
        {closeAfterSearch : true,}, 
            { } 
        );
   }
    else {
        // =============== BAGIAN IMPORT / EXPORT (ELSE) ===============
        var grid = $("#tabel_sp_detail_export"), i;
        grid.jqGrid(
        { 
            url:base_url+'/afg_c_stuffing_plan/get_stuffing_plan_detail_export',
            mtype : "post",
            postData:{code:code},
            datatype: "json", 
            colNames:
            [
                '', 'WG/CTN', 'CUSTOMER', 'CONSIGNEE', 'QTY', 'PACKAGING', 'DESCRIPTION OF GOODS',
                'WEIGHT', 'SHIP MEAS', 'W/H MEAS', 'W/H AGENT', 'COST MEAS', 'BL MEAS', 'POL',
                'HBL Agent', 'HB/L', 'TANGGAL BL', 'HS CODE', 'SHIPPING MARK', 'INCOTERM', 'N', 'TRUCO CODE', 'C/O', 'OVERTIME', 'FREIGHT',
                '','','','','','','','','','','','','LOCK',
            ],
            colModel:
            [
            {name:'num',index:'num', width:20, align: "center", editable: false,
                cellattr: function(rowId, cellValue, rawObject, cm, rdata) { if(rawObject[37]=='0'){ return 'class="Blue"'; } }
            }, 
            {name:'wg_ctn',index:'wg_ctn', align:"center", width:50, editable: false,
                formatter:numFormat, cellattr: function(rowId, cellValue, rawObject, cm, rdata) { if(rawObject[37]=='0'){ return 'class="Blue"'; } }
            },
            {name:'shipper_print',index:'shipper_print', width:250, editable: false, 
                edittype: "select",
                editoptions: {
                    value: { "": "" },
                    dataInit: function (element) {
                        var $elem = $(element);
                        var grid = $elem.closest('table.ui-jqgrid-btable');
                        var rowid = $elem.closest('tr').attr('id');
                        var isTabPressed = false;
						var isEnterPressed = false; // 1. TAMBAH VARIABEL INI
                        
                        $elem.select2({
                            placeholder: '', width: '100%', selectOnClose: true,
                            ajax: {
                                url: base_url + '/afg_c_dropdown/dropdown_shipper_print',
                                dataType: 'json', delay: 250,
                                data: function (params) { return { term: params.term }; },
                                processResults: function (data) { return { results: $.map(data, function(item) { return { id: item, text: item }; }) }; },
                                cache: true
                            }
                        });

                        setTimeout(function() { $elem.select2('open'); }, 50);

                        var isMouseSelected = false; // Tambahkan ini di bawah var isTabPressed

						$elem.on('select2:select', function (e) {
							var data = e.params.data;
							if ($elem.find("option[value='" + data.id + "']").length === 0) {
								$elem.append(new Option(data.text, data.id, true, true));
							}
							$elem.val(data.id);
							
							// Hanya jika kolom ini adalah shipper, jalankan trigger ini
							if ($elem.attr('name') === 'shipper_print') {
								update_row_shipper(grid, data.id);
							}
							isMouseSelected = true; // Tandai jika user mengklik pakai Mouse/Enter
						});

						$elem.on('select2:open', function() {
							var searchField = document.querySelector('.select2-search__field');
							if (searchField) {
								searchField.focus();
								$(searchField).off('keydown.tabCatch').on('keydown.tabCatch', function(e) {
									if (e.keyCode === 9 || e.key === 'Tab') {
										e.preventDefault(); 
										isTabPressed = true; 
										$elem.select2('close');
									} else if (e.keyCode === 13 || e.key === 'Enter') {
										isEnterPressed = true;
									}
								});
							}
						});

						$elem.on('select2:close', function() {
							var tr = $elem.closest('tr')[0];
							var td = $elem.closest('td')[0];
							var iRow = tr ? tr.rowIndex : null;
							var iCol = td ? td.cellIndex : null;

							setTimeout(function() {
								if (iRow !== null && iCol !== null) {
									try { grid.jqGrid("saveCell", iRow, iCol); } catch(e) {}
								}

								// JIKA DITEKAN TAB -> Pindah Kolom
								if (isTabPressed) {
									setTimeout(function() {
										var colModel = grid.jqGrid('getGridParam', 'colModel');
										var currentName = $elem.attr('name'); 
										var currIndex = colModel.findIndex(c => c.name === currentName);
										var nextColIndex = -1;
										for (var i = currIndex + 1; i < colModel.length; i++) {
											if (colModel[i].editable === true && colModel[i].hidden !== true) { 
												nextColIndex = i; break; 
											}
										}
										if (nextColIndex !== -1 && iRow !== null) { 
											grid.jqGrid("editCell", iRow, nextColIndex, true); 
										}
										isTabPressed = false; 
									}, 50);
								}
								
								// JIKA DITEKAN ENTER ATAU DIKLIK MOUSE -> SAVE KE DATABASE
								else if (isEnterPressed || isMouseSelected) {
									setTimeout(function() {
										window.saveRowToDatabase(grid, rowid);
										isEnterPressed = false;
										isMouseSelected = false;
									}, 100); 
								}
							}, 50);
						});
                    }
                },
                cellattr: function(rowId, cellValue, rawObject, cm, rdata) {
                    var statusIndex = (rawObject.length > 30) ? 37 : 22;
                    if(rawObject[statusIndex] == '0'){ return 'class="Blue"'; }
                }
            },
            {name:'shipper_import',index:'shipper_import', width:250, editable: false, 
                edittype: "select",
                editoptions: {
                    value: { "": "" },
                    dataInit: function (element) {
                        var $elem = $(element);
                        var isTabPressed = false;
						var isEnterPressed = false; // 1. TAMBAH VARIABEL INI
                        
                        $elem.select2({
                            placeholder: '', width: '100%', selectOnClose: true,
                            ajax: {
                                url: base_url + '/afg_c_dropdown/dropdown_shipper_import_print',
                                dataType: 'json', delay: 250,
                                data: function (params) { return { term: params.term }; },
                                processResults: function (data) { return { results: $.map(data, function(item) { return { id: item, text: item }; }) }; },
                                cache: true
                            }
                        });

                        setTimeout(function() { $elem.select2('open'); }, 50);

                        var isMouseSelected = false; // Tambahkan ini di bawah var isTabPressed

						$elem.on('select2:select', function (e) {
							var data = e.params.data;
							if ($elem.find("option[value='" + data.id + "']").length === 0) {
								$elem.append(new Option(data.text, data.id, true, true));
							}
							$elem.val(data.id);
							
							// Hanya jika kolom ini adalah shipper, jalankan trigger ini
							if ($elem.attr('name') === 'shipper_print') {
								update_row_shipper(grid, data.id);
							}
							isMouseSelected = true; // Tandai jika user mengklik pakai Mouse/Enter
						});

						$elem.on('select2:open', function() {
							var searchField = document.querySelector('.select2-search__field');
							if (searchField) {
								searchField.focus();
								$(searchField).off('keydown.tabCatch').on('keydown.tabCatch', function(e) {
									if (e.keyCode === 9 || e.key === 'Tab') {
										e.preventDefault(); 
										isTabPressed = true; 
										$elem.select2('close');
									} else if (e.keyCode === 13 || e.key === 'Enter') {
										isEnterPressed = true;
									}
								});
							}
						});

						$elem.on('select2:close', function() {
							var tr = $elem.closest('tr')[0];
							var td = $elem.closest('td')[0];
							var iRow = tr ? tr.rowIndex : null;
							var iCol = td ? td.cellIndex : null;

							setTimeout(function() {
								if (iRow !== null && iCol !== null) {
									try { grid.jqGrid("saveCell", iRow, iCol); } catch(e) {}
								}

								// JIKA DITEKAN TAB -> Pindah Kolom
								if (isTabPressed) {
									setTimeout(function() {
										var colModel = grid.jqGrid('getGridParam', 'colModel');
										var currentName = $elem.attr('name'); 
										var currIndex = colModel.findIndex(c => c.name === currentName);
										var nextColIndex = -1;
										for (var i = currIndex + 1; i < colModel.length; i++) {
											if (colModel[i].editable === true && colModel[i].hidden !== true) { 
												nextColIndex = i; break; 
											}
										}
										if (nextColIndex !== -1 && iRow !== null) { 
											grid.jqGrid("editCell", iRow, nextColIndex, true); 
										}
										isTabPressed = false; 
									}, 50);
								}
								
								// JIKA DITEKAN ENTER ATAU DIKLIK MOUSE -> SAVE KE DATABASE
								else if (isEnterPressed || isMouseSelected) {
									setTimeout(function() {
										window.saveRowToDatabase(grid, rowid);
										isEnterPressed = false;
										isMouseSelected = false;
									}, 100); 
								}
							}, 50);
						});
                    }
                },
                cellattr: function(rowId, cellValue, rawObject, cm, rdata) { if(rawObject[37] == '0'){ return 'class="Blue"'; } }
            },
            {name:'no_pack_qty',index:'no_pack_qty', align:"center", width:30, editable: false, hidden: false,
                formatter: 'number', formatoptions: { decimalSeparator: ".", thousandsSeparator: " ", decimalPlaces: 0, defaultValue: '' },
                cellattr: function(rowId, cellValue, rawObject, cm, rdata) { if(rawObject[37]=='0'){ return 'class="Blue"'; } }
            },
            {name:'no_pack_unit', index:'no_pack_unit', width:80, editable: false,
                editoptions:{ dataInit: function(element) { $(element).css({ "text-transform": "uppercase","max-width":"80"}) } },
                cellattr: function(rowId, cellValue, rawObject, cm, rdata) {
                    if(rawObject[37]=='0'){ return 'class="Blue" style="text-transform: uppercase"'; } else { return 'style="text-transform: uppercase"'; }
                }
            },
            {name:'desc_print',index:'desc_print', width:300, editable: false, 
                editoptions:{ dataInit: function(element) { $(element).css({ "text-transform": "uppercase","max-width":"300"}) } },
                cellattr: function(rowId, cellValue, rawObject, cm, rdata) {
                    if(rawObject[37]=='0'){ return 'class="Blue" style="text-transform: uppercase"'; } else { return 'style="text-transform: uppercase"'; }
                }
            },
            {name:'weight',index:'weight', width:70, align:"center", editable: false,
                editoptions:{ dataInit: function(element) { $(element).css({ "text-align": "center", "max-width":"70" }).attr({ "onChange":"format_number(this,2)","onKeyUp":"format_number(this,2)"}); } }, 
                formatter: 'number', formatoptions: { decimalSeparator: ".", thousandsSeparator: " ", decimalPlaces: 2, defaultValue: '' },
                cellattr: function(rowId, cellValue, rawObject, cm, rdata) { if(rawObject[37]=='0'){ return 'class="Blue"'; } }
            },
            {name:'ship_meas',index:'ship_meas', width:70, align:"center", editable: false,
                editoptions:{ dataInit: function(element) { $(element).css({ "text-align": "center", "max-width":"70" }).attr({ "onChange":"format_number(this,2)","onKeyUp":"format_number(this,2)"}); } }, 
                formatter: 'number', formatoptions: { decimalSeparator: ".", thousandsSeparator: " ", decimalPlaces: 2, defaultValue: '' },
                cellattr: function(rowId, cellValue, rawObject, cm, rdata) { if(rawObject[37]=='0'){ return 'class="Blue"'; } }
            },
            {name:'wh_meas',index:'wh_meas', width:70, align:"center", editable: false,
               editoptions:{
				dataInit:function(element){

					$(element)
					.css({
						"text-align":"center",
						"max-width":"70px"
					})
					.attr({
						"onChange":"format_number(this,4)",
						"onKeyUp":"format_number(this,4)"
					});

					$(element).on('keydown', function(e){

						if(e.keyCode == 9){ // TAB
							e.preventDefault();

							var rowid = $(this).closest("tr.jqgrow").attr("id");
							var grid = $("#tabel_sp_detail");

							var cm = grid.jqGrid('getGridParam','colModel');

							var blIndex = cm.findIndex(function(col){
								return col.name === 'bl_meas';
							});

							setTimeout(function(){
								grid.jqGrid("editCell",
									$("#" + rowid)[0].rowIndex,
									blIndex,
									true
								);
							},10);
						}
					});
				},
				},
                formatter: 'number', formatoptions: { decimalSeparator: ".", thousandsSeparator: " ", decimalPlaces: 4, defaultValue: '' },
                cellattr: function(rowId, cellValue, rawObject, cm, rdata) { if(rawObject[37]=='0'){ return 'class="Blue"'; } }
            },
            {name:'wh_agent',index:'wh_agent', width:70, align:"center", editable: false, hidden: true,
                editoptions:{ dataInit: function(element) { $(element).css({ "text-align": "center", "max-width":"70" }).attr({ "onChange":"format_number(this,4)","onKeyUp":"format_number(this,4)"}); } }, 
                formatter: 'number', formatoptions: { decimalSeparator: ".", thousandsSeparator: " ", decimalPlaces: 4, defaultValue: '' },
                cellattr: function(rowId, cellValue, rawObject, cm, rdata) { if(rawObject[37]=='0'){ return 'class="Blue"'; } }
            },
            {name:'cost_meas',index:'cost_meas', width:70, align:"center", hidden:true,
                editoptions:{ dataInit: function(element) { $(element).css({ "text-align": "center" }).attr({ "onChange":"format_uang(this)","onKeyUp":"format_uang(this)"}); } },
                formatter: 'number', formatoptions: { decimalSeparator: ".", thousandsSeparator: " ", decimalPlaces: 4, defaultValue: '' },
                cellattr: function(rowId, cellValue, rawObject, cm, rdata) { if(rawObject[37]=='0'){ return 'class="Blue"'; } }
            },  
            {name:'bl_meas',index:'bl_meas', width:70, align:"center", editable: false, 
                editoptions:{ dataInit: function(element) { $(element).css({ "text-align": "center", "max-width":"70" }).attr({ "onChange":"format_number(this,4)","onKeyUp":"format_number(this,4)"}); } },
                formatter: 'number', formatoptions: { decimalSeparator: ".", thousandsSeparator: " ", decimalPlaces: 4, defaultValue: '' },
                cellattr: function(rowId, cellValue, rawObject, cm, rdata) { if(rawObject[37]=='0'){ return 'class="Blue"'; } }
            },          
            {name:'destination',index:'destination', width:90, align:"LEFT", editable: false,
                edittype: "select",
                editoptions: {
                    value: { "": "" },
                    dataInit: function (element) {
                        var $elem = $(element);
                        var grid = $elem.closest('table.ui-jqgrid-btable');
                        var rowid = $elem.closest('tr').attr('id');
                        var isTabPressed = false;
						var isEnterPressed = false;
                        
                        var rowData = grid.jqGrid('getRowData', rowid);
                        var kodeRef = rowData.kode_ref || $('#txtRefno2').val(); 
                        var dd_des = "/afg_c_dropdown/dropdown_port_jadul";
                        
                        if (kodeRef) {
                            var cekMode = kodeRef.substr(4,1);
                            if (cekMode == '1' || cekMode == '3' || cekMode == '4') { dd_des = "/afg_c_dropdown/dropdown_port"; } 
                            else if (cekMode == '2' || cekMode == '6') { dd_des = "/afg_c_dropdown/dropdown_airport"; }
                        }
                        
                        $elem.select2({
                            placeholder: '', width: '100%', selectOnClose: true,
                            ajax: {
                                url: base_url + dd_des, dataType: 'json', delay: 250,
                                data: function (params) { return { term: params.term }; },
                                processResults: function (data) { return { results: $.map(data, function(item) { return { id: item, text: item }; }) }; },
                                cache: true
                            }
                        });

                        setTimeout(function() { $elem.select2('open'); }, 50);

                        var isMouseSelected = false; // Tambahkan ini di bawah var isTabPressed

						$elem.on('select2:select', function (e) {
							var data = e.params.data;
							if ($elem.find("option[value='" + data.id + "']").length === 0) {
								$elem.append(new Option(data.text, data.id, true, true));
							}
							$elem.val(data.id);
							
							// Hanya jika kolom ini adalah shipper, jalankan trigger ini
							if ($elem.attr('name') === 'shipper_print') {
								update_row_shipper(grid, data.id);
							}
							isMouseSelected = true; // Tandai jika user mengklik pakai Mouse/Enter
						});

						$elem.on('select2:open', function() {
							var searchField = document.querySelector('.select2-search__field');
							if (searchField) {
								searchField.focus();
								$(searchField).off('keydown.tabCatch').on('keydown.tabCatch', function(e) {
									if (e.keyCode === 9 || e.key === 'Tab') {
										e.preventDefault(); 
										isTabPressed = true; 
										$elem.select2('close');
									} else if (e.keyCode === 13 || e.key === 'Enter') {
										isEnterPressed = true;
									}
								});
							}
						});

						$elem.on('select2:close', function() {
							var tr = $elem.closest('tr')[0];
							var td = $elem.closest('td')[0];
							var iRow = tr ? tr.rowIndex : null;
							var iCol = td ? td.cellIndex : null;

							setTimeout(function() {
								if (iRow !== null && iCol !== null) {
									try { grid.jqGrid("saveCell", iRow, iCol); } catch(e) {}
								}

								// JIKA DITEKAN TAB -> Pindah Kolom
								if (isTabPressed) {
									setTimeout(function() {
										var colModel = grid.jqGrid('getGridParam', 'colModel');
										var currentName = $elem.attr('name'); 
										var currIndex = colModel.findIndex(c => c.name === currentName);
										var nextColIndex = -1;
										for (var i = currIndex + 1; i < colModel.length; i++) {
											if (colModel[i].editable === true && colModel[i].hidden !== true) { 
												nextColIndex = i; break; 
											}
										}
										if (nextColIndex !== -1 && iRow !== null) { 
											grid.jqGrid("editCell", iRow, nextColIndex, true); 
										}
										isTabPressed = false; 
									}, 50);
								}
								
								// JIKA DITEKAN ENTER ATAU DIKLIK MOUSE -> SAVE KE DATABASE
								else if (isEnterPressed || isMouseSelected) {
									setTimeout(function() {
										window.saveRowToDatabase(grid, rowid);
										isEnterPressed = false;
										isMouseSelected = false;
									}, 100); 
								}
							}, 50);
						});
                    }
                },
                cellattr: function(rowId, cellValue, rawObject, cm, rdata) {
                    var statusIndex = (rawObject.length > 30) ? 37 : 22;
                    if(rawObject[statusIndex] == '0'){ return 'class="Blue"'; }
                }
            },
            {name:'doc',index:'doc', width:100, align:"center", editable: false,
                editoptions:{ dataInit: function(element) { $(element).css({ "text-align": "center", "text-transform": "uppercase", "max-width":"50" }) } },
                cellattr: function(rowId, cellValue, rawObject, cm, rdata) {
                    if(rawObject[37]=='0'){ return 'class="Blue" style="text-transform: uppercase"'; } else { return 'style="text-transform: uppercase"'; }
                }
            },
            {name:'hb_l',index:'hb_l', width:90, align:"center", editable: false,
                editoptions:{ dataInit: function(element) { $(element).css({ "text-transform": "uppercase", "max-width":"90" }) } },
                cellattr: function(rowId, cellValue, rawObject, cm, rdata) {
                    if(rawObject[37]=='0'){ return 'class="Blue" style="text-transform: uppercase"'; } else { return 'style="text-transform: uppercase"'; }
                }
            },
            {name:'agent_code',index:'agent_code', width:100, align:"left", editable: false,
                editoptions:{ dataInit: function(element) { $(element).css({ "text-transform": "uppercase", "max-width":"90" }) } },
                cellattr: function(rowId, cellValue, rawObject, cm, rdata) {
                    if(rawObject[37]=='0'){ return 'class="Blue" style="text-transform: uppercase"'; } else { return 'style="text-transform: uppercase"'; }
                }
            },
            {name:'hs_code',index:'hs_code', width:100, align:"left", editable: false,
                editoptions:{ dataInit: function(element) { $(element).css({ "text-transform": "uppercase", "max-width":"90" }) } },
                cellattr: function(rowId, cellValue, rawObject, cm, rdata) {
                    if(rawObject[37]=='0'){ return 'class="Blue" style="text-transform: uppercase"'; } else { return 'style="text-transform: uppercase"'; }
                }
            },              
            {name:'shipping_mark',index:'shipping_mark', width:110, align:"left", editable: false,
                editoptions:{ dataInit: function(element) { $(element).css({ "text-transform": "uppercase", "max-width":"110" }) } },
                cellattr: function(rowId, cellValue, rawObject, cm, rdata) {
                    if(rawObject[37]=='0'){ return 'class="Blue" style="text-transform: uppercase"'; } else { return 'style="text-transform: uppercase"'; }
                }
            },
            {name:'incoterms',index:'incoterms', width:80, align:"left", editable: false, edittype:"select",
                editoptions:{
                    dataUrl:base_url+'/afg_c_dropdown/dropdownIncoterms',
                    dataInit: function(element) { $(element).css({ "text-align": "left","max-width":"80px","text-transform":"uppercase"}) },
                },
                cellattr: function(rowId, cellValue, rawObject, cm, rdata) { if(rawObject[37]=='0'){ return 'class="Blue"'; } }
            },
            {name:'isN',index:'isN', width:40, align:"center", editable: false, hidden:true, formatter: "checkbox", edittype:"select", editoptions:{ value:" : ;Yes:Y;No:N", width: 30 } },
            {name:'trucking_co',index:'trucking_co', width:100, align:"left", editable: false, hidden:true, edittype:"select",
                editoptions:{
                    dataUrl:base_url+'/afg_c_dropdown/dropdown_trucking',
                     dataInit: function (elem) { $(elem).select2({ width: "100%" }); setTimeout(function() { $(".select2-container").width("100"); }, 0);},
                },
                cellattr: function(rowId, cellValue, rawObject, cm, rdata) { if(rawObject[37]=='0'){ return 'class="Blue"'; } },
                formater: function(rowId, cellValue, rawObject, cm, rdata) { if(cellValue == 0){ return ''; } else { return rowId; } }
            },
            {name:'c_o',index:'c_o', width:50, align:"center", editable: false, hidden:true,
                cellattr: function(rowId, cellValue, rawObject, cm, rdata) { if(rawObject[0]=='0'){ return 'class="Blue"'; } },
                edittype:"select", editoptions:{ value:" : ;Y:Y;N:N", dataInit: function(element) { $(element).css({ "text-align": "center", "max-width":"50" }) } },
            },
            {name:'overtime',index:'overtime', align:"center", width:70, editable: false, hidden:true, edittype:"select", editoptions:{ value:" : ;Yes:Y;No:N", dataInit: function(element) { $(element).css({ "text-align": "center", "max-width":"70" }) } } },
            {name:'freight',index:'freight', width:100, align:"center", editable: false, hidden:true, edittype:"select",
                editoptions:{ value:" : ;PREPAID:PREPAID;COLLECT:COLLECT", dataInit: function(element) { $(element).css({ "max-width":"100" }) } }, formatter:'select',
                cellattr: function(rowId, cellValue, rawObject, cm, rdata) { if(rawObject[0]=='0'){ return 'class="Blue"'; } }
            },  
            {name:'kode_ref',index:'kode_ref', width:200, hidden: true,},
            {name:'desc_good',index:'desc_good', width:200, hidden: true,},
            {name:'shipping_marks',index:'shipping_marks', width:200, hidden: true,},
            {name:'notify',index:'notify', width:200, hidden: true,},
            {name:'notify_add',index:'notify_add', width:200, hidden: true,},
            {name:'consignee',index:'consignee', width:200, hidden: true,},
            {name:'consignee_add',index:'consignee_add', width:200, hidden: true,},
            {name:'shipper_import_code',index:'shipper_import_code', width:200, hidden: true,},
            {name:'shipper_import_add',index:'shipper_import_add', width:200, hidden: true,},
            {name:'note',index:'note', width:200, hidden: true},
            {name:'lock_job',index:'lock_job', width:30, hidden: true},
            {name:'shipper',index:'shipper', width:200, hidden: true,},
            {name:'isLock', index: 'isLock',width:40, align:"center", editable: false, hidden: false,   
            edittype:"checkbox", editoptions:{value: "1:0"}, formatter:"checkbox",formatoptions:{disabled:false}
            },
        ], 
        viewrecords: true, multiselect: true, hiddengrid : false, shrinkToFit: false,
        modal: false, width: 920, height: 200, rowNum: 100, rowList:[10,20,30,40,50],
        gridview: true, caption:"Table Stuffingplan Detail", cellEdit:true,
        cellsubmit:'clientArray', pager:'#pagination_sp_export',
            
       afterSaveCell: function(rowid, cellname, value, iRow, iCol) {
		},
            
        loadComplete: function (data) {
			bindEnterSaveForGrid('#tabel_sp_detail_export');
			SpTotals.bind('tabel_sp_detail_export', data);
            gclCargoLoadSummary();
            var iCol = getColumnIndexByName ($(this), 'isLock'), rows = this.rows, i, c = rows.length;
            for (i = 1; i < c; i += 1) {
                var rowid = rows[i].id;
                $("#" + rowid).addClass('row-readonly'); // Jadikan read-only by default

                var $checkboxLock = $("input", rows[i].cells[iCol]);
                $checkboxLock.prop('disabled', true);

                $checkboxLock.click(function (e) {
                    var clickedRowId = $(e.target).closest('tr').attr('id'); 
                    $('#tabel_sp_detail_export').jqGrid("editCell", 0, 0, false);
                    
                    if (clickedRowId != null){
                        var myData = sp_strip_lock_fields(grid.getRowData(clickedRowId));
                        $.ajax({
                            type: "POST",
                            url: base_url + 'afg_c_stuffing_plan/saving_data',
                            data: { id: clickedRowId, myData: myData, type: 'import' },
                            success: function (response) {
                                var data = $.parseJSON(response);
                                var isChecked = $(e.target).is(':checked');
                                Swal.fire('Anak job sudah di ' + (isChecked ? 'lock' : 'unlock'));
                                $('#tabel_sp_detail_export').trigger('reloadGrid');
                            }
                        });
                    }
                });
            }
            $("#cb_" + grid[0].id).hide();
            disable_bl_button(sp_id); 
        },

        beforeSelectRow: function(rowid, e) {
            var lock = grid.jqGrid('getCell', rowid, 'isLock');
            var selRows = grid.jqGrid('getGridParam', 'selarrrow');
            var isChecked = ($.inArray(rowid, selRows) > -1);
            
            var cols = [
                'shipper_print', 'shipper_import', 'no_pack_qty', 'no_pack_unit', 
                'desc_print', 'weight', 'ship_meas', 'wh_meas', 'wh_agent', 'bl_meas', 
                'destination', 'doc', 'hb_l', 'agent_code', 'hs_code', 'shipping_mark', 
                'incoterms'
            ];

            if(lock == '1' || !isChecked) {
                for(var i = 0; i < cols.length; i++) { $(this).jqGrid('setColProp', cols[i], { editable: false }); }
            } else {
                for(var i = 0; i < cols.length; i++) { $(this).jqGrid('setColProp', cols[i], { editable: true }); }
            }

            var cbsdis = $("tr#" + rowid + ".jqgrow > td > input.cbox:disabled", grid[0]);
            if (cbsdis.length === 0) { return true; } else { return false; }
        },

        onSelectRow: function(rowid, status){
			var grid = $(this);
    
			// ==========================================
			// 1. BLOK ANTI MULTI-SELECT
			// ==========================================
			if (status) {
				var selRows = grid.jqGrid('getGridParam', 'selarrrow').slice();
				for (var k = 0; k < selRows.length; k++) {
					if (selRows[k] !== rowid) {
						// Hilangkan centang baris lain (Otomatis akan memicu Save Opsi 1 untuk baris tersebut)
						grid.jqGrid('setSelection', selRows[k], false);
					}
				}
			}
			// ==========================================
            var selected = $("tr.jqgrow > td > input.cbox:checked", grid[0]);       
            selected[rowid] = status;
            
            var cols = [
                'shipper_print', 'shipper_import', 'no_pack_qty', 'no_pack_unit', 
                'desc_print', 'weight', 'ship_meas', 'wh_meas', 'wh_agent', 'bl_meas', 
                'destination', 'doc', 'hb_l', 'agent_code', 'hs_code', 'shipping_mark', 
                'incoterms', 'sales'
            ];

            var iColLock = getColumnIndexByName($(this), 'isLock');
            var tr = grid[0].rows.namedItem(rowid);

            if(status) { 
                var lock = grid.jqGrid('getCell', rowid, 'isLock');
                
                if (lock != '1') {
                    $("#" + rowid).removeClass('row-readonly');
                    for(var i = 0; i < cols.length; i++) { $(this).jqGrid('setColProp', cols[i], { editable: true }); }
                }

                $("td:eq(" + iColLock + ") input", tr).prop('disabled', false);

                var cbs = $("tr.jqgrow > td > input.cbox", grid[0]);            
                cbs.attr("disabled", true);  
                selected.attr("disabled", false); 
                disabled_top_button();
                var kode_ref = grid.jqGrid('getCell', rowid, 'kode_ref');
                ready_detail(rowid, kode_ref);
                        
            } else {
                $("#" + rowid).addClass('row-readonly');
                for(var i = 0; i < cols.length; i++) { $(this).jqGrid('setColProp', cols[i], { editable: false }); }

                $("td:eq(" + iColLock + ") input", tr).prop('disabled', true);

                var cbs = $("tr.jqgrow > td > input.cbox:disabled", grid[0]);       
                cbs.attr("disabled", false);   
                enabled_top_button(); 
                remove_detail(); 

                // PATCH OPSI 1: Save massal ke server saat baris UNCHECK
                // Tutup cell edit yang masih aktif jika ada, supaya valuenya masuk ke data
                try { $('#tabel_sp_detail_export').jqGrid("editCell", 0, 0, false); } catch(e){}
                
                var myData = sp_strip_lock_fields(grid.jqGrid('getRowData', rowid));
                $.ajax({
                    type: "POST",
                    url: base_url + 'afg_c_stuffing_plan/saving_data',
                    data: { id: rowid, myData: myData, type: 'import' },
                    success: function () { gclCargoLoadSummary(); }
                });           
            }
        },
        
        }).navGrid('#pagination_sp_export',{ edit: false, add: false, del: false, search: false }, { }, { }, { }, { }, { } );
   }
}

/* update delete stuffing detail yg sdh ada invoice */
del_detail=function(rowid,code) {
$.post(base_url+'/afg_c_income/kunci_job',{rowid: rowid,kode_ref: code},'json')
	.done(function(d,s,e) {
		var datar = $.parseJSON(d);		
		if (datar.status==1) {
			Swal.fire({
			  type: "error",
			  title: "Oops...",
			  text: "Anak job tidak bisa dihapus!",
			  footer: '<a href="#">Karena sudah di posting ke finance, hubungi team finance untuk menghapus data</a>'
			});
			return;
		} else {

	$.post(base_url+'/afg_c_income/lock_job',{rowid: rowid,kode_ref: code},'json')
	.done(function(d,s,e) {
		var data = $.parseJSON(d);		
		if (data.status==1) {
			Swal.fire('job sdh ada invoice')
			return;
		} else {
			//console.log(datar);
			$("#Form-Input-Detail").css('font-size','12px').html('Are you sure will delete job detail <br><br><b>'+code+'</b>'+
			'<br><br><hr><input type="button"value="CANCEL" id="button-cancel-detail" style="float: right; width: 80; height: 30" onclick="cancel_delete_detail()">'+
			'<input type="button"value="DELETE" id="button-delete-detail" style="float: right; width: 80; height: 30; margin: 0 10px 5px 0" onclick="delete_sp_detail('+rowid+')">');
			$("#Form-Input-Detail").dialog(
			{
				autoOpen: true,
				resizable: false,
				top:100,
				height: 'auto',
				width: 350,
				modal: true,
				title: "Delete Confirm",
				close: function() 
				{	
					enabled_top_button();
					remove_detail();
					$(this).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
				}
			})
			
		}
	})
		}
	})
}


/*end update */

$(function () {
	
	
	$("#txtClosingDate").datepicker({dateFormat : "dd/mm/yy"});
	$("#combo_month").datepicker({
    dateFormat: "dd/mm/yy"
	});
	$("#txtETDJKT").datepicker({dateFormat : "dd/mm/yy"});
	$("#txtATD").datepicker({dateFormat : "dd/mm/yy"});
	$("#txtETA").datepicker({dateFormat : "dd/mm/yy"});
	$("#txtATA").datepicker({dateFormat : "dd/mm/yy"});
	$("#txtStuffingDate").datepicker({dateFormat : "dd/mm/yy"});
	$("#btnStuffingPlan").css({'cursor':'pointer','background-color':'#dadada','color':'#000000'}).attr({'onMouseOut':'hover_out(this)','onMouseOver':'hover_in(this)'});
	$("#btnUploadManifestBC").css({'cursor':'pointer','background-color':'#dadada','color':'#000000'}).attr({'onMouseOut':'hover_out(this)','onMouseOver':'hover_in(this)'});
	$("#btnManifest").css({'cursor':'pointer','background-color':'#dadada','color':'#000000'}).attr({'onMouseOut':'hover_out(this)','onMouseOver':'hover_in(this)'});
	$('#btnBL-6').attr({'onClick':'show_global_cost()','onMouseOut':'hover_out(this)','onMouseOver':'hover_in(this)'}).css({'cursor':'pointer','background-color':'#dadada','color':'#000000'}); 
	$('#btnGI').attr({'onClick':'show_global_income()','onMouseOut':'hover_out(this)','onMouseOver':'hover_in(this)'}).css({'cursor':'pointer','background-color':'#dadada','color':'#000000'}); 
	$("#tombol-1").attr({'onClick':'SelectShipmentRequest()','onMouseOut':'hover_out(this)','onMouseOver':'hover_in(this)'}).css({'cursor':'pointer','background-color':'#dadada','color':'#000000'});
	$('#btnBL-13').attr({'onClick':'show_schedule()','onMouseOut':'hover_out(this)','onMouseOver':'hover_in(this)'}).css({'cursor':'pointer','background-color':'#dadada','color':'#000000'});
	$('#btnBL-17').attr({'onClick':'print_rekap()','onMouseOut':'hover_out(this)','onMouseOver':'hover_in(this)'}).css({'cursor':'pointer','background-color':'#dadada','color':'#000000'});
	$("#btnSI").css({'cursor':'pointer','background-color':'#dadada','color':'#000000'}).attr({'onMouseOut':'hover_out(this)','onMouseOver':'hover_in(this)'});
	$("#btnPreAlertAgent").css({'cursor':'pointer','background-color':'#dadada','color':'#000000'}).attr({
		'onClick':'open_pre_alert_agent()',
		'onMouseOut':'hover_out(this)',
		'onMouseOver':'hover_in(this)'
	});
	$("#btnStuffingPhotos").css({'cursor':'pointer','background-color':'#dadada','color':'#000000'}).attr({
		'onMouseOut':'hover_out(this)',
		'onMouseOver':'hover_in(this)'
	});
	// 1. Berikan styling saja, hapus penugasan fungsi inline (onClick)
	enabled_top_button();
	getStuffingPlan('last');
	//$('#spId').val('46'); 
	//getStuffingPlan('pilih');

	var previous;
	var year = new Date().getFullYear();	
	$('#combo_year').focus(function () {
		previous = this.value;
	}).live('change', function () {
		var get_year = $("#combo_year").val();
		$.ajax({
			async: true,
			type: "POST", 
			url : base_url+"/afg_c_dropdown/get_dropdown_year",
			data: {
				prev_year : previous,
				get_year : get_year,
			},
			success:function(response)
			{
				$("#div_combo_year").html(response)
			}
		})
	})
});

function post_container() {
	var mbl = $('#txtMotherBL').val();
	var container = $('#txtContSealNumber').val();
	var scac = $('#txtPelayaran').val();
	var job_no = $('#txtRefno2').val();
		
	if (mbl == '' || container =='' || scac =='') 
	{
	show_messages('No Container dan atau Master BL dan atau SCAC  harus isi dahulu');
	}else if((scac == 'REGU' && mbl.includes("JKS") == false) || (scac == 'SIKU' && mbl.includes("SSL")== false) || (scac == 'EGLV' && mbl.includes("08")== false )|| (scac == 'ESPU' && mbl.includes("ID")== false)){
	Swal.fire('Nomor Master BL tidak sesuai, masukan MBL dari shipping line sesuai dengan SCAC yang dipilih, jika tidak ada MBL shipping line silakan isi table tracking secara manual ');	
	}
	else 
	{
		$.ajax({
			type: "POST", 
			url : base_url+"/Afg_c_stuffing_plan/proses_post",
			data: { 
				mbl,
				container,
				scac,
				job_no,
			},
			success:function(response)
			{
				Swal.fire("Berhasil")
			},
			error: function(response){
				var data = response.responseText;
				Swal.fire(data)
				
			}
		})
	}
}

function get_booking() {
    var sp_id = $('#spId').val();
    var destination = $('#dropdownCountries').val();
    var mode = $('input[name="shipment_type"]:checked').val();
    var trans = $('#dropdownTranshipmentPort').val();
    var schedule_id = $('#scheduleId').val(); // ambil dari view
    var _search = 'false';
    var sidx= 'book.created_at';
    var sord= 'asc';
    var rows= 150;
    var page= 1;
    $.ajax({
        type: "POST", 
        url : base_url+"/afg_c_shipment_request/displayShipmentRequestFiltered",
        data: { 
            destination,
            mode,
            trans,
            sp_id,
            schedule_id,   // kirim ke server
            _search,
            sidx,
            sord,
            rows,
            page,
        },
        success:function(response){
            var k=JSON.parse(response);
            if (k['records']>0){
                Swal.fire('You have '+k['records']+' Shipping Intrution to process to this job');
            }
        }
    })
}



edit_stuff=function() {
	$('#txtMode').val('edit');	
	edit_all();
}

disabled_all=function() {
	$('#btnNew').attr('hidden',false);
	$('#btnEdit').attr('hidden',false);
	$('#btnDelete').val('DELETE').attr('onClick','del_stuff()');
	$('#btnBrowse').val('BROWSE').attr('onClick','browse_stuff()');
	$('#btnPrev').attr('hidden',false);
	$('#btnNext').attr('hidden',false);
	$('#combo_year').attr('disabled',true);
	$('#combo_month').attr('disabled',true);
	$('#dropdownRefNo').attr('disabled',true);
	$('#txtRefno2').attr('disabled',true);
	$('#dropdownTypeOfTransaction').attr('disabled',true);
	$('#txtStuffingDate').attr('disabled',true);
	$('#dropdownWarehouse').attr('disabled',true);
	$('#txtCarrier').attr('disabled',true);
	$('#txtPelayaran').attr('disabled',true);
	$('#txtMotherBL').attr('disabled',true);
	$('#txtVessel').attr('disabled',true);
	$('#txtVoyage').attr('disabled',true);
	$('#txtClosingDate').attr('disabled',true);
	$('#txtClosingTime').attr('disabled',true);
	$('#txtETDJKT').attr('disabled',true);
	$('#txtATD').attr('disabled',true);
	$('#txtETA').attr('disabled',true);
	$('#txtATA').attr('disabled',true);
	$('#txtContSealNumber').attr('disabled',true);
	$('#txtTOC1').attr('disabled',true);
	$('#txtTOC2').attr('disabled',true);
	$('#txtKurs').attr('disabled',true);
	$('#txtSelling').attr('disabled',true);
	$('#txtAirLine').attr('disabled',true);
	$('#dropdownPortLoading').attr('disabled',true);
	$('#dropdownTranshipmentPort').attr('disabled',true);
	$('#dropdownCountries').attr('disabled',true);
	
	$('#dropdownAgent').attr('disabled',true);
		
	$('#tambahWarehouse').attr('disabled',true);
	$('#tambahPortLoading').attr('disabled',true);
	$('#tambahTranshipmentPort').attr('disabled',true);
	$('#tambahDestination').attr('disabled',true);
	$('#tambahAgent').attr('disabled',true);
		
	$('#shipment-1').attr('disabled',true);
	$('#shipment-2').attr('disabled',true);
	$('#shipment-3').attr('disabled',true);
	$('#shipment-4').attr('disabled',true);	
	$('#shipment-5').attr('disabled',true);	
	
	$('#REQUEST').fadeOut();
}

function show_schedule() {
	$('#open-schedule').dialog({ modal: true, title: "MONTHLY SCHEDULE",width: 420, height: 145, resizable: false });
	$('#open-schedule').html('<div style="margin-bottom: 10px; font-size: .85em">'+
	'<input onclick="schedule_direct()" type="button" value="DIRECT EXPORT" style="width: 180px; height: 40px; color: blue;">'+
	'<input onclick="schedule_via()" type="button" value="TRANS EXPORT" style="width: 180px; height: 40px; color: blue;">'+
	'</div><div style="margin-bottom: 10px; font-size: .85em">'+
	'<input onclick="schedule_direct_import()" type="button" value="DIRECT IMPORT" style="width: 180px; height: 40px;">'+
	'<input onclick="schedule_via_import()" type="button" value="TRANS IMPORT" style="width: 180px; height: 40px;"></div>');
}
function schedule_direct() {
	window.open(base_url+"/schedule/ScheduleDirect/scheduleDirectListing", "_self",1,true);
}
function schedule_via() {
	window.open(base_url+"/schedule/ScheduleVia/scheduleViaListing", "_self",1,true);
}
function schedule_direct_import() {
	window.open(base_url+"/schedule/ScheduleDirect/scheduleDirectListingImport", "_self",1,true);
}
function schedule_via_import() {
	window.open(base_url+"/schedule/ScheduleVia/scheduleViaListingImport", "_self",1,true);
}
function open_pre_alert_agent() {
    var job_no = $.trim($('#txtRefno2').val() || '');

    if (job_no === '') {
        Swal.fire('Pilih Job terlebih dahulu');
        return false;
    }

    $('#pre_alert_agent').html(
        '<div style="padding:25px;text-align:center;font-size:12px;">Loading Pre Alert Agent...</div>'
    );

    $('#pre_alert_agent').dialog({
        autoOpen: true,
        modal: true,
        title: 'PRE ALERT AGENT - ' + job_no,
        width: 1160,
        height: 720,
        resizable: false,
        position: { my: 'center top', at: 'center top+20', of: window },
        close: function() {
            $(this).html('');
        }
    });

    $.ajax({
        type: 'POST',
        url: base_url + '/afg_c_pre_alert_agent/open_form',
        data: { job_no: job_no },
        success: function(response) {
            $('#pre_alert_agent').html(response);
        },
        error: function(xhr) {
            $('#pre_alert_agent').html(
                '<div style="padding:20px;color:#b71c1c;">' +
                '<b>Gagal membuka Pre Alert Agent.</b><br>' +
                $('<div/>').text(xhr.responseText || 'Unknown error').html() +
                '</div>'
            );
        }
    });
}

function show_pre_alert() {
	var kode_ref = $('#txtRefno2').val()
	$('#pre_alert').dialog({ modal: true, title: "VIEW ALL JOB SHEET", width: 990, height: 640, resizable: false });
	$('#pre_alert').html('<center><object data="'+base_url+'/afg_c_pre_alert/show_pre_alert/'+kode_ref+'" width="970px" height="600" type="application/pdf"></center>');

}

function update_shipp_co() {
	var shipp_co = $("select#txtCarrier").val();
	var code = $("input#txtRefno2").val();
	$.ajax
	({
		async: true,
		type:	"POST",
		url:	base_url+"/afg_c_stuffing_plan/update_shipco",
		data: {code:code,shipp_co:shipp_co},
		success:function(response)
		{	
			var data = $.parseJSON(response);
		},
		error:function(response)
		{	
			show_messages('Error saving data');
			return false;		
		},
	})
}
function update_pelayaran() {
	var scac = $("select#txtPelayaran").val();
	var code = $("input#txtRefno2").val();
	//console.log (scac);
	$.ajax
	({
		async: true,
		type:	"POST",
		url:	base_url+"/afg_c_stuffing_plan/update_pelayaran",
		data: {nama:code,scac:scac},
		success:function(response)
		{	
			var data = $.parseJSON(response);
		},
		error:function(response)
		{	
			show_messages('Error saving data');
			return false;		
		},
	})
}

function get_new_num() {
	var code = $('#txtRefno2').val();
	if (code.substr(4,3)=='300'|| code.substr(4,3)=='301'|| code.substr(4,3)=='302'|| code.substr(4,3)=='400'||code.substr(4,3)== '402'||code.substr(4,3)=='600') 
	{
		var grid = $("#tabel_sp_detail_export"), i;
	}
	else 
	{
		var grid = $("#tabel_sp_detail"), i;
	}
	
	var rowid = grid.jqGrid('getDataIDs');
	var rowCount 	 = grid.getGridParam("reccount");
	var rowCount2   = rowCount-26;
	
	if (rowCount==0) 
	{
		return String.fromCharCode(65);
	}
	else if(rowCount >= 26 )
	{
		return 'Z'+String.fromCharCode(65+rowCount2);
	}
	else
	{
		var data = grid.jqGrid('getRowData');
		var num = ',';
  		for (i = 0; i < data.length; i++) 
  		{
    			num += data[i].num+',';
  		}
  		
  		var abjad = ',';
  		for (i = 0; i <= rowCount; i++) 
  		{
    		abjad += String.fromCharCode(65+i)+',';
  		}
  		
  		for (i = 0; i <= abjad.length; i++) 
  		{
    		if (num[i]!=abjad[i]) 
    		{
    			return abjad[i];	
    		}
  		}
  		
	}
}

// Tambahkan variabel global kecil di luar fungsi untuk mencegah double click
var isSavingNewDetail = false;

function save_new_detail() {
    // KUNCI: Cegah eksekusi jika sedang proses simpan
    if (isSavingNewDetail) return;
    isSavingNewDetail = true;

    var code = $('#txtRefno2').val();
    var grid;

    if (code.substr(4,3) == '300' || code.substr(4,3) == '301' || code.substr(4,3) == '302' || code.substr(4,3) == '400' || code.substr(4,3) == '402' || code.substr(4,3) == '600') {
        grid = $("#tabel_sp_detail_export");
    } else {
        grid = $("#tabel_sp_detail");
    }
    
    var abjad = get_new_num();
    
    const etd = $("#txtETDJKT").val();
    const eta = $("#txtETA").val();
    const port = $("#dropdownPortLoading").val();
    const destination = $("#dropdownCountries").val();
    const vessel = $("#txtVessel").val();
    const voyage = $("#txtVoyage").val();
    const carrier = $("#txtCarrier").val();
    const motherBL = $("#txtMotherBL").val();
    const contSeal = $("#txtContSealNumber").val();
    
    var dataDetail = {
        sp_id       : $('#spId').val(),
        code        : code,
        kode_ref    : code + abjad,
        num         : abjad,
        shipp_co    : $("select#txtCarrier").val(),
        isLock      : 0,
        etd         : etd,
        eta         : eta,
        port        : port,
        destination : destination,
        vessel      : vessel,
        voyage      : voyage,
        carrier     : carrier,
        motherBL    : motherBL,
        contSeal    : contSeal
    };
    
    $.ajax({
        async: true,
        type: "POST",
        url: base_url + "afg_c_stuffing_plan/manage_table_detail",
        data: dataDetail,
        success: function(response) {   
            var data = $.parseJSON(response);
            
            // Asumsi: Backend mengembalikan ID row yang baru dibuat (misal data.new_id)
            // Jika backend Anda menggunakan nama lain (misal data.id), ubah variabel di bawah ini.
            var newlyInsertedId = data.new_id; 

            // Sisipkan perintah satu kali saat Grid selesai loading
            grid.jqGrid('setGridParam', {
                datatype: 'json', 
                postData: { code: code },
                loadComplete: function() {
                    // 1. Pastikan ID baris baru ditemukan
                    if (newlyInsertedId) {
                        
                        // 2. Centang baris tersebut (Select Row)
                        grid.jqGrid('setSelection', newlyInsertedId, true);
                        
                        // 3. Cari index baris (iRow)
                        var ind = grid.jqGrid('getInd', newlyInsertedId);
                        
                        // 4. Cari index kolom (iCol) untuk 'shipper_print'
                        var colModel = grid.jqGrid('getGridParam', 'colModel');
                        var colIndex = -1;
                        for (var i = 0; i < colModel.length; i++) {
                            // Sesuaikan nama field 'shipper_print' dengan colModel Anda
                            if (colModel[i].name === 'shipper_print') {
                                colIndex = i;
                                break;
                            }
                        }
                        
                        // 5. Masuk ke edit mode dan focus ke cell tersebut
                        if (ind !== false && colIndex !== -1) {
                            setTimeout(function() {
                                grid.jqGrid('editCell', ind, colIndex, true);
                            }, 100); // Jeda tipis agar jqGrid selesai menggambar centang
                        }
                    }

                    // 6. Hapus fungsi loadComplete ini agar tidak terpanggil lagi di reload berikutnya
                    grid.setGridParam({ loadComplete: function() {} });
                }
            }).trigger("reloadGrid");

            // BUKA KUNCI setelah sukses
            isSavingNewDetail = false;
        },
        error: function(response) { 
            show_messages('Error saving data');
            
            // BUKA KUNCI jika terjadi error
            isSavingNewDetail = false;
            return false;       
        }
    });
}

function format_waktu(input)  {
	d = new Date();
	datetext = d.toTimeString();
	datetext = datetext.split(' ')[0];
	$('#'+input['id']).val(datetext);
}

function show_manifest() {
    var kode_ref = $('#txtRefno2').val();
    
    $('#open-manifest').dialog({ 
        modal: true, 
        title: "DATA MANIFEST", 
        width: 700, 
        height: 180, 
        resizable: false 
    });

    $('#open-manifest').html(`
        <div style="margin-bottom: 10px; font-size: .85em; text-align: center;">
            <h2>Select a format to download:</h2>
			<br>
			<br>
            <div class="button-row">
                <input onclick="manifest_xml()" type="button" value="EDI BENKEL" class="manifest-btn benkel">
                <input onclick="manifest_xml_d()" type="button" value="EDI DYNAMIC" class="manifest-btn dynamic">
                <input onclick="manifest_xml_d()" type="button" value="EDI CHARTERLINK" class="manifest-btn charterlink">
            </div>
            <div class="button-row">
                <input onclick="manifest_excel()" type="button" value="EXCEL" class="manifest-btn excel">
                <input onclick="manifest_pdf()" type="button" value="PDF" class="manifest-btn pdf">
            </div>
        </div>
        <p id="demo"></p>
    `);

    // Add CSS styles using jQuery
    $('.button-row').css({
        'display': 'flex',
        'justify-content': 'center',
        'gap': '10px',  // Adds space between buttons
        'margin-bottom': '10px'
    });

    $('.manifest-btn').css({
        'width': '140px',
        'height': '40px',
        'border': 'none',
        'color': 'white',
        'font-size': '14px',
        'cursor': 'pointer',
        'border-radius': '5px'
    });

    $('.benkel').css('background-color', '#007bff'); // Blue
    $('.dynamic').css('background-color', '#28a745'); // Green
    $('.charterlink').css('background-color', '#ffc107'); // Yellow
    $('.excel').css('background-color', '#17a2b8'); // Teal
    $('.pdf').css('background-color', '#dc3545'); // Red
}


function manifest_xml() {
	
	window.open(base_url+"/afg_c_manifest/create_data?id="+$('#spId').val(),"_self",1,true);
}

function manifest_xml_d() {
	
	window.open(base_url+"/afg_c_manifest/create_data_d?id="+$('#spId').val(),"_self",1,true);
}

function manifest_pdf() {
	var kode_ref = $('#txtRefno2').val();
	$('#form-manifest').dialog({ modal: true, title: "MANIFEST", width: 990, height: 660, resizable: false });
	$('#form-manifest').html('<div style="margin-bottom: 10px">'+
	'<center>'+
	'<iframe id="frame_manifest" src="'+base_url+'/afg_c_manifest/show_manifest/'+kode_ref+'" width="970px" height="600"></iframe>'+
	'</center></div>');
}

function manifest_excel() {
	var kode_ref = $('#txtRefno2').val();
	$('#form-manifest').dialog({ modal: true, title: "MANIFEST", width: 330, height: 65, resizable: false });
	$('#form-manifest').html('<div style="margin-bottom: 10px">'+
	'<center>'+'<input onclick="window.alert" value="DOWNLOADING EXCEL FILE">'+
	'</center>'+
	'<center>'+
	'<iframe id="frame_manifest" src="'+base_url+'/afg_c_manifest/show_excel/'+kode_ref+'" width="310px" height="60"></iframe>'+
	'</center></div>');
}

function tambahWarehouse() {
	window.open(base_url+"/reference/warehouse", "_self",1,true);
}

function tambahPortLoading()
{
	window.open(base_url+"/reference/port_loading", "_self",1,true);
}

function tambahTranshipmentPort()
{
	window.open(base_url+"/reference/city", "_self",1,true);
}

function tambahDestination()
{
	window.open(base_url+"/reference/city", "_self",1,true);
}

function tambahAgent()
{
	window.open(base_url+"/reference/agent_company", "_self",1,true);
}

function tambahCountSeal() 
{
	/*if ($("#txtTOC1").val()=="" || $("#txtTOC2").val()=="") {
		show_messages("Silahkan isi type of conterner di stuffing terlebih dulu");
		return false;
	}
	*/
	$("#tambah-count-seal").dialog( 
	{
		resizable: false,
		height: 280,
		width: 550,
		modal: true,
		title: "NUM OF COUNT",
		close: function() {	

		}
	});
	$("#tambah-count-seal").html('<div>'+
	'<table id="table-countainer-seal"></table>'+
	'<div id="page-countainer-seal"></div>');
	
	var spid = $("#spId").val();
	$("#table-countainer-seal").jqGrid("setGridParam", { datatype: 'json', postData: { id_sp : $("#spId").val()}}).trigger("reloadGrid");
	table_countainer_seal(spid)
}

function table_countainer_seal()
{
	var spid = $("#spId").val();	
	var grid = $("#table-countainer-seal"), i;	
	grid.jqGrid(
	{
		url:base_url+'/afg_c_stuffing_plan/tambah_countainer_seal', 
		mtype : "post",
		datatype: "json",   
	   postData: { id_sp : spid }, 
		colNames:
		[ 
			'Countainer Number',
			'Countainer Type',		
		],
		colModel:
		[
			{name:'count_num',index:'count_num', width:30, align:"center",
				editable: true,
				editoptions:{
					dataInit: function(element) {
        				$(element).css({ "text-transform": "uppercase"})
      			}
				},
				cellattr: function(rowId, cellValue, rawObject, cm, rdata) {
					return 'style="text-transform: uppercase"';
				}
			},
			{name:'count_type',index:'count_type', width:70, align:"center",
				editable: true,
				edittype:"select",
				editoptions:{ 
					dataUrl:base_url+'/afg_c_dropdown/dropdownContainer',
					dataInit : function (e) {
						$(e).css('min-width','80px')
					}
				},
				stype:"text",
				searchoptions:{ 
					sopt:['eq','lt','gt'],
				}
			},
			
		],
		sortname: "id",
		sortorder: "asc",
		multiselect: true,
		shrinktofit: false,
		loadonce : false,
		modal: true,
		rownumbers: true,
		rowNum:15,
		width: 520,
		height: 180,
		rowList:[15,30,45],
		viewrecords: true,
		rownumbers: true,
		gridview: true,
		pager: '#page-countainer-seal',
		caption:false,
		cellEdit:true,
		cellsubmit:'clientArray',
		editurl:'afg_c_stuffing_plan/tambah_container_manage_seal',
		
		beforeSaveCell: function(rowid, cellname, value, iRow, iCol) {
			update_table_container_seal(rowid,cellname,value)
    	},
						
		loadComplete: function() {
			$("#cb_"+grid[0].id).hide();
			$(".ui-jqgrid-titlebar-close",$("#table-countainer-seal")[0].grid.cDiv).hide();
    	},
    	
    	beforeSelectRow: function(rowid, e) {
        		var cbsdis = $("tr#"+rowid+".jqgrow > td > input.cbox:disabled", grid[0]);
        		if (cbsdis.length === 0) {
            	return true; 
        			} else {
            	return false; 
       	 	}
    	},	
    			
		onSelectRow: function(rowid, status){
			var selected = $("tr.jqgrow > td > input.cbox:checked", grid[0]);		
			selected[rowid] = status;
			if(status)
			{ 
				var cbs = $("tr.jqgrow > td > input.cbox", grid[0]);  			
    			cbs.attr("disabled", true);  
    			selected.attr("disabled", true);
    		}
        	else
        	{
        		var cbs = $("tr.jqgrow > td > input.cbox:disabled", grid[0]);  		
    			cbs.attr("disabled", false);       			
        	}
		},				
	})
	grid.navGrid('#page-countainer-seal', 
		{ edit: false, add: false, del: true },
		{ },
   	{ },
   	{ },
   	{closeAfterSearch : true, captions: "Search Data", Find:"Cari"}, 
   	{ }
	)
	grid.navButtonAdd('#page-countainer-seal',{ 
		caption:" ", 
		buttonicon:"ui-icon-plus", 
		onClickButton: insert_table_countainer_seal, 
		position: "last", 
		title:"Insert new record", 
		cursor: "pointer"
	})
}


function insert_table_countainer_seal()
{
	var grid = $("#table-countainer-seal"), i;
	var count = grid.getGridParam("reccount");
	
	$.ajax({
		async: true,
		type: "POST", 
		url : base_url+'/afg_c_stuffing_plan/tambah_container_manage_seal',
		data: { 
			id_sp : $("#spId").val(),
			count_num : "",
			count_type : "",
			oper	: 'add',
		},
		success:function(data)
		{
			var data = $.parseJSON(data);
			$("#table-countainer-seal").jqGrid("setGridParam", { datatype: 'json',}).trigger("reloadGrid");
		}	
	})
}

function update_table_container_seal(rowid,cellname,value)
{
	$.ajax
	({
		traditional: true,
		type:"POST",
		url : base_url+'/afg_c_stuffing_plan/tambah_container_manage_seal',
		data:
		{
			id : $("#table-countainer-seal").jqGrid ('getGridParam', 'selrow'),
			kolom : cellname,
			isi : value,
			oper : 'edit-inline',
		},
		success:function(response)
		{
			var data = $.parseJSON(response);
			$("#table-countainer-seal").jqGrid("setGridParam", { datatype: 'json',}).trigger("reloadGrid");
    		return [true];
		}
	})
}
function getCellValue(rowId, cellId) {
    var cell = jQuery('#' + rowId + '_' + cellId);        
    var val = cell.val();
    return val;
}
function executeGlobalSave () {

       var gridData = jQuery("#table-countainer-seal").getRowData();
        var postData = JSON.stringify(gridData);
        console.log("JSON serialized jqGrid data:\n" + postData);
        $.ajax({
            type: "POST",
            url: base_url+'/afg_c_stuffing_plan/edit_container_manage_seal',
            data : postData,
            dataType:"json",
            contentType: "application/json; charset=utf-8",
            success: function(response, textStatus, xhr) {
                console.log("success");
            },
            error: function(xhr, textStatus, errorThrown) {
                console.log("error");
            }
        });

}
function edit_table_container_seal()
{
	var id = $("#table-countainer-seal").jqGrid ('getGridParam', 'selrow');
	var jumlah = getCellValue(id, 'count_num');
	var type = getCellValue(id, 'count_type');
	$.ajax
	({
		traditional: true,
		type:"POST",
		url : base_url+'/afg_c_stuffing_plan/tambah_container_manage_seal',
		data:
		{
			id : $("#table-countainer-seal").jqGrid ('getGridParam', 'selrow'),
			jumlah : jumlah,
			tipe : type,
			oper : 'edit-inline',
		},
		success:function(response)
		{
			var data = $.parseJSON(response);
			$("#table-countainer-seal").jqGrid("setGridParam", { datatype: 'json',}).trigger("reloadGrid");
    		return [true];
		}
	})
}
	
function tambahCount() 
{
	if ($("#txtContSealNumber").val()=="") {
		show_messages("Silahkan isi nomer conterner di stuffing terlebih dulu");
		return false;
	}
	$("#tambah-count").dialog( 
	{
		resizable: false,
		height: 280,
		width: 570,
		modal: true,
		title: "CONTAINER & SEAL NUMBER",
		close: function() {	

		}
	});
	$("#tambah-count").html('<div>'+
	'<table id="table-countainer"></table>'+
	'<div id="page-countainer"></div>');
	
	var spid = $("#spId").val();
	$("#table-countainer").jqGrid("setGridParam", { datatype: 'json', postData: { id_sp : $("#spId").val()}}).trigger("reloadGrid");
	table_countainer(spid)
}
	
function table_countainer()
{
	var spid = $("#spId").val();	
	var grid = $("#table-countainer"), i;	
	grid.jqGrid(
	{
		url:base_url+'/afg_c_stuffing_plan/tambah_countainer', 
		mtype : "post",
		datatype: "json",   
	   postData: { id_sp : spid }, 
		colNames:
		[ 
			'Countainer',
			'Countainer No',	
			'Seal No',	
		],
		colModel:
		[
			{name:'count_size',index:'count_size', width:50, align:"center",
				editable: true,
				edittype:"select",
				editoptions:{ 
					dataUrl:base_url+'/afg_c_dropdown/dropdownContainer',
					dataInit : function (e) {
						$(e).css('min-width','80px')
					}
				},
				stype:"text",
				searchoptions:{ 
					sopt:['eq','lt','gt'],
				}
			},
			{name:'count_no',index:'count_no', width:100, align:"left",
				editable: true,
				editoptions:{
					dataInit: function(element) {
        				$(element).css({ "text-transform": "uppercase"})
      			}
				},
				cellattr: function(rowId, cellValue, rawObject, cm, rdata) {
					return 'style="text-transform: uppercase"';
				}
			},
			{name:'seal_no',index:'seal_no', width:100, align:"left",
				editable: true,
				editoptions:{
					dataInit: function(element) {
        				$(element).css({ "text-transform": "uppercase"})
      			}
				},
				cellattr: function(rowId, cellValue, rawObject, cm, rdata) {
					return 'style="text-transform: uppercase"';
				}
			},
		],
		sortname: "id",
		sortorder: "asc",
		multiselect: true,
		shrinktofit: true,
		loadonce : false,
		modal: true,
		rownumbers: true,
		rowNum:15,
		width: 540,
		height: 180,
		rowList:[15,30,45],
		viewrecords: true,
		rownumbers: true,
		gridview: true,
		pager: '#page-countainer',
		caption:false,
		cellEdit:true,
		cellsubmit:'clientArray',
		editurl:'afg_c_stuffing_plan/tambah_container_manage',
		
		beforeSaveCell: function(rowid, cellname, value, iRow, iCol) {
			update_table_container(rowid,cellname,value)
    	},
						
		loadComplete: function() {
			$("#cb_"+grid[0].id).hide();
			$(".ui-jqgrid-titlebar-close",$("#table-countainer")[0].grid.cDiv).hide();
    	},
    	
    	beforeSelectRow: function(rowid, e) {
        		var cbsdis = $("tr#"+rowid+".jqgrow > td > input.cbox:disabled", grid[0]);
        		if (cbsdis.length === 0) {
            	return true; 
        			} else {
            	return false; 
       	 	}
    	},	
    			
		onSelectRow: function(rowid, status){
			var selected = $("tr.jqgrow > td > input.cbox:checked", grid[0]);		
			selected[rowid] = status;
			if(status)
			{ 
				var cbs = $("tr.jqgrow > td > input.cbox", grid[0]);  			
    			cbs.attr("disabled", true);  
    			selected.attr("disabled", false);
    		}
        	else
        	{
        		var cbs = $("tr.jqgrow > td > input.cbox:disabled", grid[0]);  		
    			cbs.attr("disabled", false);       			
        	}
		},				
	})
	grid.navGrid('#page-countainer', 
		{ edit: false, add: false, del: true },
		{ },
   	{ },
   	{ },
   	{closeAfterSearch : true, captions: "Search Data", Find:"Cari"}, 
   	{ }
	)
	grid.navButtonAdd('#page-countainer',{ 
		caption:" ", 
		buttonicon:"ui-icon-plus", 
		onClickButton: insert_table_countainer, 
		position: "last", 
		title:"Insert new record", 
		cursor: "pointer"
	})
}

function insert_table_countainer()
{
	var grid = $("#table-countainer"), i;
	var count = grid.getGridParam("reccount");
	var countainer = $("#txtContSealNumber").val().split("/");
	
	if (count === 0 ) {
		count_size = $("#txtTOC2").val();
		count_no = countainer[0].replace(" ","");
		seal_no = countainer[1].replace(" ","");
	}
	else {
		count_size = "";
		count_no = "";
		seal_no = "";
	}
	$.ajax({
		async: true,
		type: "POST", 
		url : base_url+'/afg_c_stuffing_plan/tambah_container_manage',
		data: { 
			id_sp : $("#spId").val(),
			count_size : count_size,
			count_no : count_no,
			seal_no : seal_no,
			oper	: 'add',
		},
		success:function(data)
		{
			var data = $.parseJSON(data);
			$("#table-countainer").jqGrid("setGridParam", { datatype: 'json',}).trigger("reloadGrid");
		}	
	})
}

function update_table_container(rowid,cellname,value)
{
	$.ajax
	({
		async: true,
		type:"POST",
		url : base_url+'/afg_c_stuffing_plan/tambah_container_manage',
		data:
		{
			id : rowid,
			kolom : cellname,
			isi : value,
			oper : 'edit-line',
		},
		success:function(response)
		{
			var data = $.parseJSON(response);
			$("#table-countainer").jqGrid("setGridParam", { datatype: 'json',}).trigger("reloadGrid");
    		return [true];
		}
	})
}

function change_region()
{
	$.ajax({
			async: true,
			type: "POST", 
			url : base_url+'/afg_c_stuffing_plan/change_region',
			data: { 
				region_id : $('select[name="region_id"]').val(),
			},
			success:function(data)
			{
				var data = $.parseJSON(data);
				window.open(base_url+'/afg_c_stuffing_plan/','_self',1,true);
			}	
	})
}

function enabled_top_button()
{
	$('#btnNew').attr('hidden',false);
	$('#btnEdit').attr('hidden',false);
	$('#btnDelete').attr('hidden',false);
	$('#btnBrowse').attr('hidden',false);
	$('#btnPrev').attr('hidden',false);
	$('#btnNext').attr('hidden',false);
		
	$('#form_input').removeAttr('style');
}

function disabled_top_button()
{
	$('#btnNew').attr('hidden',true);
	$('#btnEdit').attr('hidden',true);
	$('#btnDelete').attr('hidden',true);
	$('#btnBrowse').attr('hidden',true);
	$('#btnPrev').attr('hidden',true);
	$('#btnNext').attr('hidden',true);		
}

function disable_bl_button(rowid, kode_ref) {
    var actionType = gclDetailActionFromJob();

    $('#btnAddDetail')
        .attr('data-action', actionType)
        .removeAttr('onclick') // Jangan pernah pasang onclick inline di tombol ini.
        .css({
            'cursor': 'pointer',
            'color': '#000',
            'background-color': '#dadada',
            '-webkit-box-shadow': '0 6px 2px -6px #333',
            '-moz-box-shadow': '0 6px 2px -6px #333',
            'box-shadow': '0 6px 2px -6px #333'
        });
}

function open_bc_depan(rowid,kode_ref)
{
	$("#Form-Open-Booking-Depan").css('font-size','12px').html('Booking Confirmation of <br><br><b>'+kode_ref+'</b>'+
			'<br><br><hr><input type="button"value="PREVIEW BC" id="button-preview-bc" style="float: right; width: 80; height: 30" onclick="cetak_bc_depan('+rowid+',\''+kode_ref+'\')">'+
			'<input type="button"value="SEND BC TO SHIPPER" id="button-send-bc" style="float: right; width: 80; height: 30; margin: 0 10px 5px 0" onclick="send_bc_depan('+rowid+',\''+kode_ref+'\')">');
			$("#Form-Open-Booking-Depan").dialog(
			{
				autoOpen: true,
				resizable: false,
				top:100,
				height: 'auto',
				width: 350,
				modal: true,
				title: "Booking Confirmation",
				close: function() 
				{	
					
					$(this).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
				}
			})
			
		}


function cetak_bc_depan(rowid,kode_ref)
{
	$("#Form-BC-Depan" ).css('background-color', '#eee');
	$( "#Form-BC-Depan" ).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
	$( "#Form-BC-Depan" ).dialog(
		{
			position:'top',
			autoOpen: true,
			resizable: false,
			height: 660,
			width: 980,
			modal: true,
			title: "Print Out Booking Confirmation",
			close: function() 
			{	
				$(this).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
				$('#Form-BC-Depan').dialog("close")
			}
		});
		
		$('#Form-BC-Depan').dialog({title:"Booking Confirmation "+kode_ref,}).html('<center><object data="'+base_url+'/afg_c_stuffing_plan/cetak_bc_depan/'+kode_ref+'/'+rowid+'" width="955px" height="610px" type="application/pdf"></center>');
		
	
}

function instan_accsess()
{
	remove_detail()
	$('#btnBL-12').attr({'onClick':'NewSchedule()','onMouseOut':'hover_out(this)','onMouseOver':'hover_in(this)'}).css({'cursor':'pointer','background-color':'#dadada','color':'#000000'});
	$('#btnBL-11').attr({'onClick':'browse_schedule()','onMouseOut':'hover_out(this)','onMouseOver':'hover_in(this)'}).css({'cursor':'pointer','background-color':'#dadada','color':'#000000'});
}

function cancel_instan_accsess()
{	
	$('#btnBL-12').attr({'onClick':'','style':'','onMouseOver':'','onMouseOut':''}).css('display','block');
	$('#btnBL-11').attr({'onClick':'','style':'','onMouseOver':'','onMouseOut':''}).css('display','block');

}

function ready_detail(rowid,kode_ref)
{
	$('#btnBL-1').attr({'onClick':'openBL('+rowid+',\''+kode_ref+'\')','onMouseOut':'hover_out(this)','onMouseOver':'hover_in(this)'}).css({'cursor':'pointer','background-color':'#dadada','color':'#000000'});
	$('#btnBL-4').attr({'onClick':'show_income('+rowid+')','onMouseOut':'hover_out(this)','onMouseOver':'hover_in(this)'}).css({'cursor':'pointer','background-color':'#dadada','color':'#000000'});
	$('#btnBL-5').attr({'onClick':'show_cost('+rowid+')','onMouseOut':'hover_out(this)','onMouseOver':'hover_in(this)'}).css({'cursor':'pointer','background-color':'#dadada','color':'#000000'}); 
	$('#btnBL-15').attr({'onClick':'table_tracking('+rowid+',\''+kode_ref+'\')','onMouseOut':'hover_out(this)','onMouseOver':'hover_in(this)'}).css({'cursor':'pointer','background-color':'#dadada','color':'#000000'});
	$('#btnBL_16').attr({
    'onClick': 'window.location.href="/pricelist/shipment_booking";',
    'onMouseOut': 'hover_out(this)',
    'onMouseOver': 'hover_in(this)'
	}).css({
    'cursor': 'pointer',
    'background-color': '#dadada',
    'color': '#000000'
	});
	$('#btnBL-7').attr({'onClick':'show_job_sheet('+rowid+',\''+kode_ref+'\')','onMouseOut':'hover_out(this)','onMouseOver':'hover_in(this)'}).css({'cursor':'pointer','background-color':'#dadada','color':'#000000'});
	$('#btnDelDetail').attr({'onClick':'del_detail('+rowid+',\''+kode_ref+'\')','onMouseOut':'hover_out(this)','onMouseOver':'hover_in(this)'}).css({'cursor':'pointer','background-color':'#dadada','color':'#000000'});
	$('#btnAddDetail').removeAttr('onclick').attr({'style':'','onMouseOver':'','onMouseOut':''});
	$('#btnBL-14').attr({'onClick':'request_truck('+rowid+',\''+kode_ref+'\')','onMouseOut':'hover_out(this)','onMouseOver':'hover_in(this)'}).css({'cursor':'pointer','background-color':'#dadada','color':'#000000'});
	$('#btnTallySheet')
		.addClass('detail-pdf-active')
		.attr({
			'onclick': 'open_tally_sheet_pdf('+rowid+')',
			'onMouseOut':'hover_out(this)',
			'onMouseOver':'hover_in(this)',
			'title':'Open Tally Sheet PDF'
		})
		.css({
			'cursor':'pointer',
			'background-color':'#dadada',
			'color':'#000000'
		});

	$('#btnPeb')
		.addClass('detail-pdf-active')
		.attr({
			'onclick': 'open_peb_editor('+rowid+', '+JSON.stringify(kode_ref)+')',
			'onMouseOut':'hover_out(this)',
			'onMouseOver':'hover_in(this)',
			'title':'Isi / upload PEB, NPE, PPB untuk row ini'
		})
		.css({
			'cursor':'pointer',
			'background-color':'#dadada',
			'color':'#000000'
		});

	// Restore Upload Tracking: aktif hanya saat row detail dipilih.
	$('#bulkexport')
		.addClass('detail-row-active')
		.attr({
			'onclick': 'open_bulk_tracking('+rowid+', '+JSON.stringify(kode_ref)+')',
			'onMouseOut':'hover_out(this)',
			'onMouseOver':'hover_in(this)',
			'title':'Upload Tracking untuk '+kode_ref
		})
		.css({
			'cursor':'pointer',
			'background-color':'#dadada',
			'color':'#000000'
		});
	cancel_instan_accsess();
}
function gclTallySheetUrl(rowid) {
    var root = String(base_url || '').replace(/\/+$/, '');
    return root + '/afg_c_stuffing_plan/tally_sheet_pdf/' + encodeURIComponent(rowid) + '?template=default';
}

function open_tally_sheet_pdf(rowid) {
	rowid = String(rowid == null ? '' : rowid);
	if (!/^[1-9][0-9]*$/.test(rowid)) return false;
	window.open(gclTallySheetUrl(rowid), '_blank', 'noopener,noreferrer');
	return false;
}

/**
 * Tombol PEB: editor PEB / NPE / PPB (upload PDF CEISA atau isi manual) untuk row detail yang dipilih.
 * Isinya fragmen widget dari afg_c_stuffing_plan/peb_editor/{sp_detail_id}; data tersimpan per booking_code
 * (sama dengan Edit Booking), otomatis diteruskan ke gudang, dan dibaca langsung oleh cetak Stuffing Plan.
 * (Pengambilan PDF PEB dari Jarvis lewat open_peb_pdf() sudah tidak dipakai tombol ini.)
 */
var pebEditorReq = 0;
function open_peb_editor(rowid, kode_ref) {
	rowid = parseInt(rowid, 10);
	if (!rowid) return false;

	var root = String(base_url || '').replace(/\/+$/, '');
	var req  = ++pebEditorReq;
	$('#modal_peb_title').text('PEB / NPE / PPB' + (kode_ref ? ' \u2014 ' + kode_ref : ''));
	$('#modal_peb_body').html('<div class="pwsp-msg">Memuat editor PEB&hellip;</div>');
	$('#modal_peb').fadeIn(150);

	$.ajax({
		url: root + '/afg_c_stuffing_plan/peb_editor/' + encodeURIComponent(rowid),
		type: 'GET',
		dataType: 'html',
		cache: false
	}).done(function (html) {
		if (req !== pebEditorReq) return;          // modal sudah ditutup / dibuka untuk row lain
		$('#modal_peb_body').html(html);           // .html() ikut menjalankan <script> widget
	}).fail(function (xhr) {
		if (req !== pebEditorReq) return;
		$('#modal_peb_body').html('<div class="pwsp-msg">Gagal memuat editor PEB (HTTP ' + xhr.status + '). Coba lagi.</div>');
	});
	return false;
}

/**
 * Tutup editor PEB. Isian form yang belum tersimpan disimpan dulu (tidak hilang diam-diam). Bila ada yang baru
 * tersimpan, modal tetap terbuka sebentar supaya status pengiriman ke gudang terlihat; klik Tutup sekali lagi.
 */
function close_peb_editor() {
	var hide = function () {
		pebEditorReq++;
		$('#modal_peb').fadeOut(150, function () { $('#modal_peb_body').empty(); });
	};
	if (!window.PebWidget) { hide(); return false; }
	window.PebWidget.flushAll().then(function (res) {
		var saved = false;
		$.each(res || [], function (i, x) { if (x === true) { saved = true; } });
		if (saved) {
			Swal.fire({ toast: true, position: 'bottom-end', showConfirmButton: false, timer: 2500, icon: 'success', title: 'PEB tersimpan. Klik Tutup lagi untuk keluar.' });
		} else {
			hide();
		}
	}, function (msg) {
		Swal.fire({ icon: 'warning', title: 'PEB belum bisa disimpan', text: msg });
	});
	return false;
}

function open_bulk_tracking(rowid, kode_ref) {
	$('#bulk_rowid').val(rowid);
	$('#bulk_kode_ref').val(kode_ref);

	$('#bulk_format').val('HK');
	$('#bulk_file').val('');
	$('#bulk_upload_msg').html('');
	window.previewData = null;

	$('#modal_bulk_tracking').fadeIn(200);
}

function close_bulk_tracking() {
	$('#modal_bulk_tracking').fadeOut(200);
}
function remove_detail()
{
	$('#btnBL-1').attr({'onClick':'','style':'','onMouseOver':'','onMouseOut':''});
	$('#btnBL-4').attr({'onClick':'','style':'','onMouseOver':'','onMouseOut':''});
	$('#btnBL-5').attr({'onClick':'','style':'','onMouseOver':'','onMouseOut':''});
	$('#btnBL-15').attr({'onClick':'','style':'','onMouseOver':'','onMouseOut':''});
	$('#btnBL_16').attr({'onClick':'','style':'','onMouseOver':'','onMouseOut':''});
	$('#btnBL-14').attr({'onClick':'','style':'','onMouseOver':'','onMouseOut':''});
	$('#btnBL-7').attr({'onClick':'','style':'','onMouseOver':'','onMouseOut':''});
	$('#btnDelDetail').attr({'onClick':'','style':'','onMouseOver':'','onMouseOut':''});
	$('#btnTallySheet')
		.removeClass('detail-pdf-active')
		.attr({'onClick':'','style':'','onMouseOver':'','onMouseOut':'','title':'Pilih row detail terlebih dahulu'});
	$('#btnPeb')
		.removeClass('detail-pdf-active')
		.attr({'onClick':'','style':'','onMouseOver':'','onMouseOut':'','title':'Pilih row detail terlebih dahulu'});
	$('#bulkexport')
		.removeClass('detail-row-active')
		.attr({'onClick':'','style':'','onMouseOver':'','onMouseOut':'','title':'Pilih row detail terlebih dahulu'});
	// Reset ke router tunggal; jangan menambahkan inline onclick yang dapat membuat row manual.
	disable_bl_button($('#spId').val());
}


function enabled_new()
{
	cancel_instan_accsess();			
	$('#txtStuffingDate').attr('disabled',false);
	$('#txtStuffingDate').attr('style','border: 1px solid #f00; width: 150px');
	$('#txtVessel').attr('disabled',false);
	$('#txtVessel').attr('style','border: 1px solid #f00; width: 150px');
	$('#txtVoyage').attr('disabled',false);
	$('#txtVoyage').attr('style','border: 1px solid #f00; width: 150px');		
	$('#txtClosingDate').attr('disabled',false);
	$('#txtClosingDate').attr('style','border: 1px solid #f00; width: 150px');
	$('#txtClosingTime').attr('disabled',false);
	$('#txtClosingTime').attr('style','border: 1px solid #f00; width: 150px');
	$('#txtETDJKT').attr('disabled',false);
	$('#txtETDJKT').attr('style','border: 1px solid #f00; width: 150px');
	$('#dropdownPortLoading').attr('disabled',false);
	$('#dropdownPortLoading').attr('style','border: 1px solid #f00; width: 110px');
	$('#dropdownTranshipmentPort').attr('disabled',false);
	$('#dropdownTranshipmentPort').attr('style','border: 1px solid #f00; width: 110px');
	$('#dropdownCountries').attr('disabled',false);
	$('#dropdownCountries').attr('style','border: 1px solid #f00; width: 110px');
	
		
	$('#form_input').attr('style','background-color:#ddd');
	$('#form_input').removeAttr('title');
	
	$('#btnNew').attr('hidden',true);
	$('#btnEdit').attr('hidden',true);
	$('#btnDelete').val('SAVE').attr('onClick','button_save()');
	$('#btnBrowse').val('CANCEL').attr('onClick','cancel_stuff()');
	$('#btnPrev').attr('hidden',true);
	$('#btnNext').attr('hidden',true);
}

function disabled_new()
{
		$('#txtStuffingDate').attr('disabled',true);
		$('#txtVessel').attr('disabled',true);
		$('#txtVoyage').attr('disabled',true);
		$('#txtClosingDate').attr('disabled',true);
		$('#txtClosingTime').attr('disabled',true);
		$('#txtETDJKT').attr('disabled',true);
		$('#dropdownPortLoading').attr('disabled',true);
		$('#dropdownTranshipmentPort').attr('disabled',true);
		$('#dropdownCountries').attr('disabled',true);		
		$('#txtStuffingDate').attr('style','width: 150px');		
		$('#txtVessel').attr('style','width: 150px');
		$('#txtVoyage').attr('style','width: 150px');				
		$('#txtClosingDate').attr('style','width: 150px');		
		$('#txtETDJKT').attr('style','width: 150px');		
		$('#dropdownPortLoading').attr('style','width: 110px');		
		$('#dropdownTranshipmentPort').attr('style','width: 110px');		
		$('#dropdownCountries').attr('style','width: 110px');
		
		$('#form_input').removeAttr('style');
		
		var sp_id = $('#spId').val();
		disable_bl_button(sp_id)
}


function hover_in(input)
{
	var id = input['id'];
	$('#'+id+'').css({'border':'1px solid #f044ff','font-weight':'bold'});
}

function hover_out(input)
{
	var id = input['id'];
	$('#'+id+'').css({'border':'1px solid #D3D3D3','font-weight':'normal'});
}

function check_value(inp) 
{
	alert(inp.value);
}

function saving_data(rowid, cellname, value, iRow, iCol, type) {
    var code = $('#txtRefno2').val();
    var grid;
    
    if (code.substr(4,3)=='300'|| code.substr(4,3)=='301'|| code.substr(4,3)=='302'|| code.substr(4,3)=='400'||code.substr(4,3)== '402'||code.substr(4,3)=='600') {
        grid = $("#tabel_sp_detail_export");
    } else {
        grid = $("#tabel_sp_detail");
    }
    
    // 1. PAKSA TUTUP CELL YANG SEDANG DIEDIT SEBELUM AMBIL DATA
    try {
        var activeRow = grid.jqGrid('getGridParam', 'iRow');
        var activeCol = grid.jqGrid('getGridParam', 'iCol');
        if (activeRow != null && activeCol != null) {
            grid.jqGrid('saveCell', activeRow, activeCol);
        }
    } catch(err) {
        console.log("Gagal saveCell otomatis", err);
    }

    // 2. BARU KITA TARIK DATANYA
    var myData = grid.jqGrid('getRowData', rowid);
    
    // 3. PEMBERSIH DARURAT (Mencegah tag HTML bocor ke backend)
    $.each(myData, function(key, val) {
        if (typeof val === 'string' && val.match(/<[^>]*>/)) {
            // Jika terdeteksi tag HTML, coba ambil value aslinya
            var extractedValue = $(val).val();
            
            // Jika berhasil diekstrak (contoh dari <select> atau <input>), gunakan value itu
            if (extractedValue !== undefined && extractedValue !== null) {
                myData[key] = extractedValue;
            } else {
                // Jika gagal, setidaknya hapus tag HTML-nya agar tidak error di database
                myData[key] = val.replace(/<[^>]*>?/gm, '').trim();
            }
        }
    });

    // --- BUANG FIELD YANG TIDAK ADA DI DATABASE ---
    delete myData.backup_date; 
    delete myData.lock_job; // <--- TAMBAHKAN BARIS INI
    delete myData.isLock;   // <--- TAMBAHKAN BARIS INI
    // ----------------------------------------------
    
    // Lanjut tembak AJAX seperti biasa
    $.ajax({
        type    : "POST",
        url     : base_url + 'afg_c_stuffing_plan/saving_data',
        data    : {
            id      : rowid,
            myData  : myData,
            type    : type,
        },
        success: function(response) {
            var data = $.parseJSON(response);
            // console.log("Berhasil disimpan", data);
        }
    });
}

function save_data_stuffingplan_detail(rowid,cellname,value)
{
	var code = $('#txtRefno2').val();
	if (code.substr(4,3)=='300'|| code.substr(4,3)=='301'|| code.substr(4,3)=='302'|| code.substr(4,3)=='400'||code.substr(4,3)== '402'||code.substr(4,3)=='600') 
	{
		var grid = $("#tabel_sp_detail_export"), i;
	}
	else 
	{
		var grid = $("#tabel_sp_detail"), i;
	}
	$.ajax
	({
		async: true,
		type:"POST",
		url : base_url+'/afg_c_stuffing_plan/save_data_stuffingplan_detail',
		data:
		{
			sp_detail_id : rowid,
			cellname : cellname,
			value : value,
		},
		success:function(response)
		{
			var data = $.parseJSON(response);
			grid.jqGrid('setGridParam',{datatype: 'json', postData:{code: data.kode_ref,}}).trigger("reloadGrid");
		}
	})
}

function checkRadioBtn(N)
{
   var radioLength = $('input[name="shipment_type"]').length;
   for (var i = 0; i < radioLength; i++) 
	{
    	if ($('input[name="shipment_type"]')[i].value == N) 
		{
			$('input[name="shipment_type"]')[i].checked = true;
        	break;
    	}
   }
}

function checkSales(value, colname)
{
if (value == '') 
   return [false,"Option Sales mustn\'t empty value"];
else 
   return [true,""];
}

function numFormat( cellvalue, options, rowObject )
{
	var weight = (eval(rowObject[8]));
	var qty = rowObject[1];
	var input = ''+weight / qty+'';
	if(!isNaN(input) && input!=Infinity){
		var num = input.replace(/\,/g,'');
		var n = num.indexOf('.');
		if( n > -1){
			return (eval(num.substring(0,n)) + eval(1))
   	} 
   	else 
   	{
    		return input;
   	}
   } 
   else 
   {
   	return "";
   }  
}

function stuffing_plan()
{
	$("#Form-Stuffing").dialog(
	{
		resizable: false,
		height: 90,
		width: 470,
		modal: true,
		title: "STUFFING",
		close: function() {	
			$('.ui-dialog-titlebar-close').attr('hidden',false);
			$(this).html('<img src="'+base_url+'/public/images/loader.gif"/>');
			remove_detail();
			enabled_top_button();
			$("#tabel_sp_detail").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
		}
	});
	$('.ui-dialog-titlebar-close').attr('hidden',true);
	$("#Form-Stuffing").html('<center><div style="width: 420">'+
		'<input type="button" value="Instruksi Stuffing" style="height: 30px; width: 140px; margin-right: 20px" onclick="instruksi_stuffing()">'+
		'<input type="button" value="Cetak" style="height: 30px; width: 90px; margin-right: 20px" onclick="cetak_stuffing()">'+
		//'<input type="button" value="Cetak" style="height: 30px; width: 90px; margin-right: 20px" onclick="edit_data_stuffing()">'+
		//'<input type="button" value="Proses Cetak" style="height: 30px; width: 90px; margin-right: 20px" onclick="cetak_stuffing()">'+
		'<input type="button" value="EXIT" style="height: 30px; width: 90px; margin-right: 20px" onclick="$(\'#Form-Stuffing\').dialog(\'close\')"></div></center>')
}

/* function edit_data_stuffing() 
{
	$('.ui-dialog-titlebar-close').attr('hidden',false);
	$('#form-cetak-stuffing').dialog({ modal: true, title: "PRINTOUT - STUFFINGPLAN", width: 990, height: 440, resizable: false })
	$('#form-cetak-stuffing').html('<div id="form-cetak-stuffing"><center><table id="tabel-cetak-stuffing"></table><div id="page-cetak-stuf"></div></center></div>');
	var grid = $("#tabel-cetak-stuffing"), i;
	grid.jqGrid(
	{
		url:	base_url+'/afg_c_stuffing_plan/get_cetak_stuffing',
		mtype : "post", 
		datatype: "json",
		postData: { kode_ref : $('#txtRefno2').val()},
		sortname: "sp_detail_id",
		sortorder: "asc",
		colNames:
		[
			'Kode Ref',
			'Shipper',			
			'Description of Goods',
		],
		colModel:
		[
			{name:'kode_ref',index:'kode_ref', width:100, align:"left", editable:false,
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'shipper_print',index:'shipper_print', width:300, align:"left", editable: true,
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'desc_print',index:'desc_print', width:480, align:"left", editable: true,
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
		],
		multiselect:false,
		loadonce : false,
		rowNum:25,
		width: 950,
		height: 340,
		shrinkToFit: false,
		rowList:[25,50,75,100],
		viewrecords: true,
		rownumbers: true,
		gridview: true,
		pager: '#page-cetak-stuf',
		caption:false,	
		cellEdit:true,
		cellsubmit:'clientArray',
		editurl : base_url+'/afg_c_stuffingplan/mng_edit_data_stuffing',
		
		afterSaveCell: function(rowid, cellname, value, iRow, iCol) {
			save_edit_data_stuffing(rowid,cellname,value)
    	},
			
		loadComplete: function () {
    		$("#cb_"+grid[0].id).hide();
      },
		
		ondblClickRow: function(rowid){											
			$( '#form_list_stuffing_plan' ).slideUp('medium', function() {$(this).dialog('close');});
			get_stuffing_table(rowid);
		},
		
	})
	.navGrid('#page-cetak-stuf', {edit:false, add:false, del:false},
		{ },
   	{ },
   	{ },
   	{height: "auto", width: "auto", closeAfterSearch : false, modal:false, caption:"Search Data Stuffingplani"},
    	{ }
	)
	.navButtonAdd('#page-cetak-stuf',{ 
			caption:"", 
			buttonicon:"ui-icon-print", 
			onClickButton: cetak_stuffing, 
			position: "last", 
			title:"Tarik Data Refund", 
			cursor: "pointer"
	})	
}

function save_edit_data_stuffing(rowid,cellname,value)
{
	$.ajax
	({
		async: true,
		type:"POST",
		url : base_url+'/afg_c_stuffing_plan/save_edit_data_stuffing/',
		data:
		{
			sp_detail_id : rowid,
			cellname : cellname,
			value : value,
		},
		success:function(response)
		{
			var data = $.parseJSON(response);
			return [true];
		}
	})
}
 */
function cetak_stuffing()
{
	var ref = $('input[id="txtRefno2"]').val();
	if(ref==''){ 
		show_messages('Kode Ref Empty')
	} else {
	
		$.ajax
		({
			async: true,
			type: "POST",
			url : base_url+'/afg_c_stuffing_plan/cetak_stuffing/'+ref,
			data: { },
			success: function(response)
			{
			if (
				(
					$('#dropdownWarehouse').val() == 'GCL WAREHOUSE' ||
					$('#dropdownWarehouse').val() == 'BANDUNG GCL WAREHOUSE' ||
					$('#dropdownWarehouse').val() == 'SARIADI SUB'
				) &&
				ref.split('-')[1]?.startsWith('100')
			) {
				booking_stuffing(ref);
			}
				$('#Form-Stuffing').dialog('close');
				$('#form-print-stuffing').dialog({ modal: true, title: "STUFFING", width: 990, height: 640, resizable: false })
				$('#form-print-stuffing').html('<center><iframe src="'+base_url+'/afg_c_stuffing_plan/cetak_stuffing/'+ref+'" width="955px" height="620"></iframe></center>');
			}
		})
	}
}

function instruksi_stuffing()
{
	var sp_id = $('input[id="spId"]').val();
	$.ajax
	({
		async: true,
		type: "POST",
		url : base_url+'/afg_c_stuffing_plan/instruksi_stuffing',
		data: { sp_id : sp_id },
		success: function(response)
		{
			var data = $.parseJSON(response);
			var form = $('#Form-Instruksi-Stuffing'), i;
			form.dialog({
			resizable: false,
			height: 'auto',
			width: 465,
			position:['center',100],
			modal: true,
			title: "INSTRUKSI STUFFING",
			close: function() {	

				}
			});
			$('.ui-dialog-titlebar-close').attr('hidden',true);
			form.html('<div><table>'+
			'<tr><td>To</td><td>:</td><td><input id="to_instruksi" type="text" value="'+data.to+'" style="height: 25px; width: 220px; margin-bottom: 5px; border: 1px solid #999"></td></tr>'+
			'<tr><td>Instruksi Stuffing</td><td>:</td><td><textarea id="to_instruksi_stuffing" style="min-width:320px; max-width: 320px; min-height: 100px; max-height: 100px; border: 1px solid #999">'+data.instruksi_stuffing+'</textarea></td></tr>'+
			'</table><hr>'+
			'<input type="button" value="EXIT" style="float: right; height: 30px; width: 120px; margin-right: 20px" onclick="close_form_stuffing()"></div>')
		}
	});
}

function close_form_stuffing() 
{
	var sp_id = $('input[id="spId"]').val();
	$.ajax
	({
		async: true,
		type: "POST",
		url : base_url+'/afg_c_stuffing_plan/close_form_stuffing',
		data: 
		{ 
			sp_id : sp_id,
			to : $('input[id="to_instruksi"]').val(),
			instruksi_stuffing : $('textarea[id="to_instruksi_stuffing"]').val(),
		},
		success: function(response)
		{
			$('#Form-Instruksi-Stuffing').dialog('close');
		}
	})
}

function new_stuff()
{
    gclCargoResetPanel();
	$('#txtMode').val('add');
	$('#dropdownRefNo').val("");
	$('#combo_year').val(time.getFullYear());
	$('#combo_month').val('');
	$('#txtRefno2').val('');
	$('#dropdownTypeOfTransaction').val(0);
	$('#txtStuffingDate').val('');
	$('#dropdownWarehouse').val(0);
	$('#txtCarrier').val('');
	$('#txtPelayaran').val('');
	$('#txtMotherBL').val('');
	$('#txtVessel').val('');
	$('#txtVoyage').val('');
	$('#txtClosingDate').val('');
	$('#txtClosingTime').val('');
	$('#txtETDJKT').val('');
	$('#txtContSealNumber').val('');
	$('#txtTOC1').val('');
	$('#txtTOC2').val('');
	$('#txtKurs').val('');
	$('#txtAirLine').val('');
	$('#dropdownPortLoading').val('');
	$('#dropdownTranshipmentPort').val('');
	$('#dropdownCountries').val('');
	$('#dropdownAgent').val('');	
	enabled_all();
	
	$("#tabel_sp_detail_export").jqGrid("clearGridData", true);
	$("#tabel_sp_detail").jqGrid("clearGridData", true);
}

function get_ref_no() {
	var year = new Date().getFullYear().toString().substr(-2);
	var bulan = $('#combo_month').val();
	var type = $('#dropdownTypeOfTransaction').val();
	var periode = bulan.substr(3,2);
	var periode_tahun = bulan.substr(6,4);
		
	if (bulan == '' || type =='') 
	{
			show_messages('Tahun, Bulan & Job harus di isi dahulu');
	}
	else if ( periode_tahun !== tahun )
	{
		show_messages('Periode Tahun Salah!');
	}
	else 
	{
		$.ajax
			({
				async: true,
				type : "POST",
				url  : base_url+'/afg_c_stuffing_plan/spnum',
				data:
				{
					tanggal : bulan,
					bulan : periode,
					tahun : year,
					tipe: type,
				},
				success:function(response)
				{
					var data = $.parseJSON(response);
					$("#txtRefno2").val(data.refno);
					$("#spId").val(data.id);
					$("#txtMode").val("edit");
					myFunction();
				},
				error:function(XMLHttpRequest)
				{
					show_messages("ERROR DUPLICATE KODE REF")
				}
			});
			$("#combo_month").attr('readonly','true');
			$("#dropdownTypeOfTransaction").attr('readonly','true');
			return false;
	}
}


function cek_bulan() {
  var bulan = $('#combo_month').val();	
  var month = new Array();
  month[0] = "01";
  month[1] = "02";
  month[2] = "03";
  month[3] = "04";
  month[4] = "05";
  month[5] = "06";
  month[6] = "07";
  month[7] = "08";
  month[8] = "09";
  month[9] = "10";
  month[10] = "11";
  month[11] = "12";

  var d = new Date();
  var n = month[d.getMonth()];
  const monthNames = ["January", "February", "March", "April", "May", "June",
  "July", "August", "September", "October", "November", "December"];
	if (bulan != n) 
	{
			Swal.fire('It is ' +  monthNames[d.getMonth()] + ' now');
				
	}
	
}

function cancel_stuff()
{
	var mode = $('#txtMode').val();
	enabled_top_button();
	if(mode=='add')
	{
		getStuffingPlan('last');
		enabled_top_button();
		disabled_all();
		$('#tambahCount').attr('disabled',true);
		$('#tambahCountSeal').attr('disabled',true);
	}
	if(mode=='add_schedule')
	{
		getStuffingPlan('last');
		enabled_top_button();
		disabled_all();
		disabled_new();
	}
	if(mode=='edit')
	{
		$('#txtMode').val('');
		getStuffingPlan('pilih');
		enabled_top_button();
		disabled_all();
		$('#tambahCount').attr('disabled',true);
		$('#tambahCountSeal').attr('disabled',true);
	}
}

function del_stuff()
{
    var stuf = $('#txtRefno2').val();
    var stuf_id = $('#spId').val();

    if (stuf == '') {
        $("#Insert-Form").html('Are you sure going to delete this schedule data ?');
        return;
    }

    $.post(base_url + '/afg_c_income/kosong_job', {rowid: stuf_id, kode_ref: stuf}, 'json')
    .done(function(d) {
        var data = $.parseJSON(d);
        if (data.status == 1) {
            Swal.fire({
                type: "error",
                title: "Oops...",
                text: "Job tidak bisa dihapus!",
                footer: '<a href="#">Anak job harus kosong</a>'
            });
            return;
        }

        $("#Insert-Form").html(
            'Are you sure going to delete all data Job <b>' + stuf + '</b>, this action cannot be undone' +
            '<br><br><label for="alasan"> Alasan hapus : </label>' +
            '<input type="text" id="fname" name="fname" value="" size="58"><br>'
        );

        $("#Insert-Form").dialog({
            autoOpen: true,
            resizable: false,
            height: 'auto',
            width: 'auto',
            modal: true,
            hide: 'Slide',
            title: "Are You sure? You Need to be Sure!",
            buttons: {
                "Delete": function() {
                    var alasan = document.getElementById("fname").value;
                    if (alasan == "") {
                        Swal.fire("Alasan delete harus diisi");
                        return false;
                    }

                    console.group('=== WMS STUFFING DELETE ===');
                    console.log('Job:', stuf);

                    // 1. Hapus di WMS lebih dulu.
                    $.ajax({
                        type: 'POST',
                        url: base_url + '/api/ticket_warehouse/delete_stuffing',
                        dataType: 'json',
                        data: {kode_ref: stuf},
                        timeout: 35000,

                        success: function(wms) {
                            console.log('WMS delete response:', wms);

                            if (!wms || !(wms.status === true || wms.status === 1 || wms.status === '1')) {
                                console.groupEnd();
                                Swal.fire(
                                    'Delete WMS gagal',
                                    (wms && (wms.response && wms.response.message ? wms.response.message : wms.message)) || 'Data lokal tidak dihapus.',
                                    'error'
                                );
                                return;
                            }

                            // 2. WMS sukses / sudah 404 -> baru hapus data lokal existing.
                            $.ajax({
                                async: true,
                                type: 'POST',
                                url: base_url + '/afg_c_stuffing_plan/submitStuffingPlan',
                                data: {
                                    sp_id: stuf_id,
                                    kode_ref: stuf,
                                    mode: 'del',
                                    pesan: alasan
                                },
                                success: function(msg) {
                                    console.log('Local delete response:', msg);
                                    console.groupEnd();
                                    $("#Insert-Form").dialog('close');
                                    getStuffingPlan('last');
                                    enabled_top_button();
                                    $('#tabel_stuffing_plan').jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
                                    show_messages('Delete ' + stuf + ' Success');
                                },
                                error: function(XMLHttpRequest) {
                                    console.error('WMS sudah terhapus tetapi local delete gagal:', XMLHttpRequest.responseText);
                                    console.groupEnd();
                                    Swal.fire(
                                        'Local delete gagal',
                                        'Data WMS sudah terhapus, tetapi data Gateway gagal dihapus. Cek log/server sebelum mengulang.',
                                        'error'
                                    );
                                    getStuffingPlan('pilih');
                                    enabled_top_button();
                                }
                            });
                        },

                        error: function(xhr) {
                            console.error('WMS delete HTTP error:', xhr.responseText);
                            console.groupEnd();
                            var msg = 'Data lokal tidak dihapus karena DELETE WMS gagal.';
                            try {
                                var e = JSON.parse(xhr.responseText);
                                msg = (e.response && e.response.message) ? e.response.message : (e.message || msg);
                            } catch (ignore) {}
                            Swal.fire('Delete WMS gagal', msg, 'error');
                        }
                    });
                },
                Cancel: function() {
                    $(this).dialog('close');
                }
            },
            close: function() {
                enabled_top_button();
                $(this).html('');
            }
        });
    });
}

/*
function enabledRefNo()
{
	$("#dropdownRefNo").live("change", function(e)
	{
		//var d=new Date();
		var tahun = $('#combo_year').val();
		var bulan = $('#combo_month').val();
		var type = $('#dropdownRefNo').val();
		
		if (tahun == '' || bulan == '' || type =='') 
		{
			show_messages('Tahun & Bulan & Job harus di isi dahulu');
			$('#combo_year').val('');
			$('#combo_month').val('');
			$('#dropdownRefNo').val('');	
			return false;
		}
		
		$.ajax
		({
			type : "POST",
			url  : base_url+'/afg_c_stuffing_plan/spnum',
			data:
				{
					//bulan: d.getMonth()+1,
					//tahun: d.getFullYear()
					bulan : bulan,
					tahun : tahun,
					tipe: type,
				},
			success:function(response)
			{
				var data = $.parseJSON(response);
				$("#spId").val(data.id);
				$('#txtRefno2').val(data.refno);			
			},
			error:function(XMLHttpRequest)
			{
				show_messages("ERROR DUPLICATE KODE REF")
				getStuffingPlan('last');
			}
		})
	});
}
*/

function button_save()
{
	if($('#txtMode').val()=='edit'){
		if($('#txtRefno2').val() == '' || $('#txtETDJKT').val() =='' || $('#dropdownCountries').val() ==''){
			show_messages("Please input ETD date /Destination first!");
			return false
		}else if($('#dropdownTypeOfTransaction').val()=='LCL' && $('#dropdownWarehouse').val() ==''){
			show_messages("Please input warehouse first!");
			return false
		}else {
			save_stuffing('edit')
			console.log ($('#dropdownTypeOfTransaction').val());
		}
	}
}

async function save_stuffing(mode) {
    const form_data = {
        sp_id: $('#spId').val(),
        kode_ref: $('#txtRefno2').val(),
        type_of_trans: $('#dropdownTypeOfTransaction').val(),
        tanggal_job: $('#combo_month').val(),
        stuffing_date: $('#txtStuffingDate').val(),
        warehouse: $('#dropdownWarehouse').val(),
        carrier: $('#txtCarrier').val(),
        scac: $('#txtPelayaran').val(),
        mother_bl: $('#txtMotherBL').val(),
        shipment_type_multi: $("input[name='shipment_type']:checked").val(),
        vessel: $('#txtVessel').val(),
        voyage: $('#txtVoyage').val(),
        closing_date_time: $('#txtClosingDate').val(),
        closing_time: $('#txtClosingTime').val(),
        etd_jkt: $('#txtETDJKT').val(),
        atd: $('#txtATD').val(),
        eta: $('#txtETA').val(),
        ata: $('#txtATA').val(),
        port_loading: $('#dropdownPortLoading').val(),
        transhipment_port: $('#dropdownTranshipmentPort').val(),
        destination: $('#dropdownCountries').val(),
        cont_seal_no: $('#txtContSealNumber').val(),
        type_cont_qty: $('#txtTOC1').val(),
        type_cont_size: $('#txtTOC2').val(),
        agen_penagihan: $('#dropdownAgent').val(),
        isBatal: 'N',
        kurs: $('#txtKurs').val(),
        kurs_selling: $('#txtSelling').val(),
        air_line: $('#txtAirLine').val(),
        mode: $('#txtMode').val(),
        ajax: 1
    };

    /*
     * GoComet tidak lagi dipanggil dari browser.
     *
     * Backend akan:
     * 1. menyimpan Stuffing Plan;
     * 2. memeriksa perubahan Mother BL;
     * 3. menentukan ADD, UPDATE, atau SKIP GoComet;
     * 4. menyimpan id_gocomet berdasarkan sp_id.
     */

    const formFields = [
        '#dropdownRefNo',
        '#combo_year',
        '#combo_month',
        '#txtRefno2',
        '#dropdownTypeOfTransaction',
        '#txtStuffingDate',
        '#dropdownWarehouse',
        '#txtCarrier',
        '#txtPelayaran',
        '#txtMotherBL',
        '#txtVessel',
        '#txtVoyage',
        '#txtClosingDate',
        '#txtClosingTime',
        '#txtETDJKT',
        '#txtATD',
        '#txtETA',
        '#txtATA',
        '#txtContSealNumber',
        '#txtTOC1',
        '#txtTOC2',
        '#txtKurs',
        '#txtSelling',
        '#txtAirLine',
        '#dropdownPortLoading',
        '#dropdownTranshipmentPort',
        '#dropdownCountries',
        '#dropdownAgent',
        '#tambahWarehouse',
        '#tambahPortLoading',
        '#tambahTranshipmentPort',
        '#tambahDestination',
        '#tambahAgent',
        '#shipment-1',
        '#shipment-2',
        '#shipment-3',
        '#shipment-4',
        '#shipment-5'
    ];

    $(formFields.join(', ')).attr('disabled', true);

    $.ajax({
        async: true,
        type: 'POST',
        url: base_url + '/afg_c_stuffing_plan/submitStuffingPlan',
        data: form_data,

        success: function(response) {
            $('#tabel_stuffing_plan')
                .jqGrid('setGridParam', {
                    datatype: 'json'
                })
                .trigger('reloadGrid');

            if (mode === 'add') {
                success_saving('add');
                getStuffingPlan('last');

            } else if (mode === 'edit') {
                success_saving('edit');
                getStuffingPlan('pilih');

            } else if (mode === 'copy') {
                success_saving('copy');
                getStuffingPlan('pilih');
            }
        },

        error: function(XMLHttpRequest) {
            // Aktifkan kembali form agar user dapat memperbaiki data.
            $(formFields.join(', ')).attr('disabled', false);

            $('#Insert-Form').html(
                XMLHttpRequest.responseText ||
                'Gagal menyimpan Stuffing Plan.'
            );

            $('#Insert-Form').dialog({
                title: 'ERROR SUBMIT STUFFINGPLAN',

                buttons: {
                    OK: function() {
                        $(this).dialog('close');
                        getStuffingPlan('last');
                        enabled_top_button();
                    }
                },

                close: function() {}
            });
        }
    });
}

// Helper untuk validasi agar tidak duplikasi
function show_validation_error(msg) {
    show_messages(msg);
    $('#txtMode').val('edit');
    edit_all();
    return false;
}

function success_saving(mode)
{	
	
	if(mode=='add'){
		show_messages('Create stuffingplan success') 
	}
	if(mode=='edit'){
		show_messages('Update stuffingplan success')
	}
	if(mode=='copy'){
		Swal.fire('Coppy Stuffingplan success')
	}
	$('#txtMode').val('');
	$('#btnDelete').val('DELETE').attr('onClick','del_stuff()');
	$('#btnBrowse').val('BROWSE').attr('onClick','browse_stuff()');
	enabled_top_button();
	
	$('#tambahCount').attr('disabled',true);
	$('#tambahCountSeal').attr('disabled',true);
	
}

function browse_stuff()
{
	$('#form_list_stuffing_plan').dialog({
		position:"center",
		autoOpen: true,
		recreateForm:true, 
		resizable: false, 
		modal: true,
		width: 1170,
		height:'auto',
		position:['center',30],
		title: "BROWSE STUFFING PLAN",
		close: function () {
			//$(this).html("");
		},
	})
	$('.ui-dialog-titlebar-close').attr('hidden',false);
	$('#form_list_stuffing_plan').slideDown('medium');	
	list_stuffing()
}
	
function list_stuffing()
{
	var grid = $("#tabel_stuffing_plan"), i;
	grid.jqGrid({
		url:	base_url+'afg_c_stuffing_plan/getTabelStuffingPlan',
		mtype : "post", 
		datatype: "json",
		sortname: "kode_ref",
		sortorder: "desc",
		ajaxSelectOptions: {type: "GET"},
		colNames:
		[
			'SP ID',
			'Ref No',			
			'Job Date',
			'ETD',
			'Vessel / Voyage',
			'Carrier',
			'Master BL',
			'Port Loading',
			'Destination',
			'Container No',
			'Posted',
			'Prepared',
			'House BL'
			
		],
		colModel:
		[
			{name:'sp_id',index:'sp_id', width:10, align:"left",hidden:true},
			{name:'kode_ref',index:'trnstuffingplan.kode_ref', width:100, align:"left",
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				},
			},
			{name:'tanggal_job',index:'tanggal_job', width:70, align:"center", formatter: emptyDate,
				stype:"text",
				searchoptions:{ 
					sopt:['eq','lt','gt'],
					dataInit: function(element) {
        				$(element).datepicker({dateFormat: 'yymmdd'})
      			}
				}
			},
			{name:'etd_jkt',index:'etd_jkt', width:70, align:"center", formatter: emptyDate,
				stype:"text",
				searchoptions:{ 
					sopt:['eq','lt','gt'],
					dataInit: function(element) {
        				$(element).datepicker({dateFormat: 'yymmdd'})
      			}
				}
			},
			{name:'vessel',index:'vessel', width:90, align:"left", 
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'carrier',index:'carrier', width:90, align:"left",
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'mother_bl',index:'mother_bl', width:110, align:"left",
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'port_loading',index:'port_loading', width:110, align:"left",
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'destination',index:'trnstuffingplan.destination', width:110, align:"left",
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				},
				 formatter: function (cellValue, options, rowObject) {
				// Generate a clickable link that closes the dialog or container
				return `<a href="javascript:void(0);" onclick="handleKodeRefClick('${options.rowId}', '${cellValue}')">${cellValue}</a>`;
				 }
			},
			{name:'cont_seal_no',index:'cont_seal_no', width:110, align:"left",
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'hide',index:'hide', width:110, align:"left",
				formatter: function(cellvalue, options, rowObject) {
						if (cellvalue == 'Perfect')
							return '<img src="'+base_url+'/public/images/medal.png"/> ' + cellvalue;
						else
							return cellvalue;
				},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				},
			},
			{name:'prepared',index:'prepared', width:100, align:"left", 
				cellattr: function(rowId, cellValue, rawObject, cm, rdata) {
    				return 'class="Blue"';
				},
				stype:"select",
				searchoptions: {
					searchhidden: true,
					sopt:['eq'],
					dataUrl: base_url+'/afg_c_dropdown/dropdown_prepared',
               buildSelect:function(response){
               	var obj = $.parseJSON(response);
               	return obj;
          		}
          	}
			},
		{
			name: 'hb_l',
			index: 'trnstuffingplandetail.hb_l',
			hidden: false,
			hidedlg: true,       // <-- menyembunyikan dari "Column Chooser" (tapi tetap bisa dicari)
			search: true,
			stype: "text",
			searchoptions: { 
				sopt: ['cn', 'eq'] 
			}
		}		
		],
		multiselect:false,
		loadonce : false,
		rowNum:15,
		width: 1140,
		height: 'auto',
		shrinkToFit: false,
		rowList:[15,30,45],
		viewrecords: true,
		rownumbers: true,
		gridview: true,
		pager: '#page-browse',
		caption:false,	
			
		loadComplete: function () {
    		$("#cb_"+grid[0].id).hide();
      },
		
		ondblClickRow: function(rowid){											
			$('#form_list_stuffing_plan').slideUp('medium', function() {$(this).dialog('close');});
			var kode_ref = grid.jqGrid('getCell',rowid,'kode_ref');
			get_stuffing_table(rowid,kode_ref);
		},
		
	}).navGrid('#page-browse', {edit:false, add:false, del:false, search: true},
		{ },
   	{ },
   	{ },
   	{
   		width: 380,
   		height: "auto",
   		zIndex:1234,
		autoOpen: true,
   		closeAfterSearch : true, 
   		modal:true, 
   		recreateForm:true,
   		caption:"Search Data Stuffingplan",
   	},
    	{ }
	);	
	search_extend(grid);
}

function handleKodeRefClick(rowId, kodeRef) {
    // Call the get_stuffing_table function
    get_stuffing_table(rowId, kodeRef);

    // Close the dialog or container
    $('#form_list_stuffing_plan').slideUp('medium', function() {
        $(this).dialog('close'); // If using jQuery UI dialog
    });
}


function emptyDate(el,em,ek)
{
	var empty = '//';
	if(el==empty){
		return '';
	} else {
		return el;
	}
}

function get_stuffing_table(id,kode_ref)
{
	$('#spId').val(id); 
	$('#txtRefno2').val(kode_ref); 
	getStuffingPlan('pilih');
}

function prev_stuff()
{
	getStuffingPlan('prev');
	$("input[id='btnNext']").attr('disabled',false)
}

function next_stuff()
{
	getStuffingPlan('next');
	$("input[id='btnPrev']").attr('disabled',false)
}

/*
function copy_stuff()
{
	disabled_top_button();
	$("#Insert-Form").html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
	$("#Insert-Form").dialog({
		autoOpen: true,
		resizable: true,
		height: 'auto',
		width: 300,
		modal: true,
		title: "Coppy Job Confirn",
		close: function(){	
			enabled_top_button();
			$( this ).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
		}
	});
	
	$('#txtMode').val('copy');
	var d = new Date();
	$.ajax
	({
		type	:"POST",
		url	:base_url+'/afg_c_stuffing_plan/spnum',
		data	: {
			tipe: $('#dropdownRefNo').val(),
			bulan: d.getMonth()+1,
			tahun: d.getFullYear()
		},
		success:function(response) {
			var data = $.parseJSON(response);
			$('#txtRefno2').val(data.refno);
			$('#spId').val(data.id);
			paste_stuff();
		},
		error:function(XMLHttpRequest)
		{
			$("#Insert-Form").html('Error kode :<br>'+ XMLHttpRequest.responseText)			
		}
	});
}

function paste_stuff()
{
	$("#Insert-Form").html('Are you sure will copy this stuffingplan ?')
	$("#Insert-Form").dialog({
		width: 300,
		height:'auto',
		buttons:{
			"  OK   ": function(){
				$( this ).dialog( "close");
				save_stuffing('copy');
			},
			"CANCEL": function() 
			{
				$( this ).dialog( "close");
				getStuffingPlan('last');
				enabled_top_button();
				cancel_instan_accsess();
			}
		}
	});
}
*/

function print_rekap()
{
	job_no = $('#txtRefno2').val();
	agent = $('#dropdownAgent').val();
	vessel = $('#txtVessel').val();
	voyage = $('#txtVoyage').val();
	cont = $('#txtTOC2').val();
	cont_no = $('#txtContSealNumber').val().replace('/','XX');
	kurs = $('#txtKurs').val();
	stuffing_date = $('#txtStuffingDate').val();
	dst = $('#dropdownCountries').val();
	destination = dst.replace(",","--");
	
	$('#Print-Rekap').dialog(
	{
		position: 'top',
		autoOpen: true, 
		resizable: false, 
		modal: true,
		width: 990,
		height:"auto",
		title: "PRINT REKAP "+ job_no,
		close: function() 
		{	
			cancel_instan_accsess();
		}
	});
	var url =serialize({
			job_no : job_no,
			agent : agent,
			vessel : vessel,
			voyage : voyage,
			cont : cont,
			cont_no : cont_no,
			kurs : kurs,
			destination : destination,
			stuffing_date: stuffing_date,
	});
	
	$('#Print-Rekap').html('<center><object data="'+base_url+'/afg_c_stuffing_plan/print_rekap?'+url+'" width="960px" height="600px" type="application/pdf"></center>');

}

function openBL() {
	load_file_head('css',tmppub+'/css/trn_bill_of_ladding.css');

	var rowid = arguments[0];
	var code = arguments[1];
	
	
		$("#FORM_DISPLAY_BL").html('<b style="font-size: 11px; font-style:italic; text-align:center">LOADING...</b>');
		$("#FORM_DISPLAY_BL").dialog({
				autoOpen: true,
				resizable: false,
				height: 800,
				width: 930,
				modal: true,
				title :"BILL OF LADING",
				close: function(){	
					cancel_instan_accsess();
					enabled_top_button();
					remove_detail();
					$(this).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
					$("#tabel_sp_detail").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
				}
		});
		var airfreight = code.substr(3, 2);
		if(airfreight=='-2' || airfreight=='-6'){
		var url = base_url+'/afg_c_bill_of_ladding/open_awb_form';
		}else{
		var url = base_url+'/afg_c_bill_of_ladding/open_bl_form';
		}
		var destination = $('#dropdownCountries').val();
		$.ajax({
				async: true,
				type: "POST", 
				url : url,
				data: { spd: rowid, kode_ref : code,airfreight : airfreight, destination : destination },
				success:function(XMLHttpRequest)
				{
					$("#FORM_DISPLAY_BL").css('background-color','#fff');
					$("#FORM_DISPLAY_BL").html(XMLHttpRequest)
				},	
				error:function(XMLHttpRequest)
				{
					$('#FORM_DISPLAY_BL').html(XMLHttpRequest.responseText)
				}	
		});
	//}
}

function parseDdMmYyyy(s) {
  if (typeof s !== 'string' || !s.trim()) return null; // kosong

  const m = s.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
  if (!m) return null; // format tidak cocok

  const dd = +m[1], mm = +m[2], yyyy = +m[3];
  const d = new Date(yyyy, mm - 1, dd);
  // validasi rollover (31/11 dsb)
  if (d.getFullYear() !== yyyy || d.getMonth() !== mm - 1 || d.getDate() !== dd) return null;
  return d; // Date object (lokal)
}


function table_tracking(rowId, kodeRef)
{
	// $("#Form-Trucking").css('background-image', 'url("public/images/bg-form.png")');
	$("#Form-Trucking").html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
	$("#Form-Trucking").dialog(
	{
		autoOpen: true,
		resizable: false,
		height: 750,
		width: 1280,
		modal: true,
		title: "TABLE TRACKING",
		close: function() 
		{	
			enabled_top_button();
			cancel_instan_accsess();
			$(this).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
		}
	});
	var grid = $("#tabel_sp_detail"), i;
	var cek = parseInt(kodeRef.substr(4,3));
	var etd = $('#txtETDJKT').val();
	var atd = $('#txtATD').val();
	var eta = $('#txtETA').val();
	var ata = $('#txtATA').val();
	var tracked = $('#txtStatus').val();
	var job_no = $('#txtRefno2').val();
	var bl = grid.jqGrid('getCell',rowId,'hb_l');
	var bl_agent = grid.jqGrid('getCell',rowId,'doc');
	var warehouse = $('#dropdownWarehouse').val()??'';
	// contoh pakai:
	const pad = n => String(n).padStart(2, '0');
	const toMysql = d => `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
	const input = $('#txtStuffingDate').val();
	var scac = $('#txtPelayaran').val();
	var consol_destination = $('#dropdownCountries').val();
	var connecting= $('#dropdownTranshipmentPort').val();
	var shipper = grid.jqGrid('getCell',rowId,'shipper_print');
	var container = $('#txtContSealNumber').val();
	var pod = grid.jqGrid('getCell',rowId,'destination');

	// getCell bisa mengembalikan HTML kalau baris sedang inline-edit -> ambil teksnya saja
	pod = $('<div>').html(pod || '').text();

	// Normalisasi nama port untuk perbandingan (case-insensitive, spasi ganda dirapikan)
	var normPort = function (v) {
		return String(v || '').replace(/\s+/g, ' ').trim().toUpperCase();
	};

	// POD = Consol Destination  -> shipment DIRECT (tidak ada transhipment)
	var is_direct = (normPort(pod) !== '' && normPort(pod) === normPort(consol_destination)) ? 1 : 0;
	if (is_direct) {
		connecting = '';
	}

	if(cek == 100 || cek == 101 || cek == 102 || cek == 200 || cek == 401 || cek == 500 || cek == 700 || cek == 900 ){
		$.ajax({
			async: true,
			type: "POST",
			url : base_url+'/afg_c_trucking/open_trucking_table_export',
			data : {kodeRef : kodeRef, rowId : rowId, etd:etd, atd:atd, eta:eta, ata:ata, tracked : tracked, job_no : job_no,bl:bl, shipper:shipper, scac : scac,consol_destination : consol_destination, connecting : connecting, container : container, pod : pod, is_direct : is_direct },
			success:function(XMLHttpRequest)
			{
				$('#Form-Trucking').html(XMLHttpRequest)
			}
		})
	}else{
		$.ajax({
			async: true,
			type: "POST", 
			url : base_url+'/afg_c_trucking/open_trucking_table_import',
			data : {kodeRef : kodeRef, rowId : rowId, etd:etd,atd:atd, eta:eta, ata:ata, tracked : tracked, job_no : job_no, stripping_date : input, warehouse : warehouse, bl_agent : bl_agent},
			success:function(XMLHttpRequest)
			{
				$('#Form-Trucking').html(XMLHttpRequest)
			}
		})
	}
}

function request_truck(rowId, kodeRef)
{
	//$("#Req-Trucking").css('background-image', 'url("public/images/bg-form.png")');
	var cek = parseInt(kodeRef.substr(4,1));
	
	$.ajax({
		async: true,
		type: "POST", 
		url : base_url+'/afg_c_request_trucking/request_trucking',
		data : {kodeRef : kodeRef, rowId : rowId},
		success:function(XMLHttpRequest)
		{
			// if(XMLHttpRequest == 'ada'){
			// 	alert("Sudah Ada")
			// }else{
				$("#Req-Trucking").html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
				$("#Req-Trucking").dialog(
				{
					autoOpen: true,
					resizable: false,
					height: 400,
					width: 1280,
					modal: true,
					title: "Request Trucking",
					close: function() 
					{	
						enabled_top_button();
						cancel_instan_accsess();
						$(this).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
					}
				});
				$('#Req-Trucking').html(XMLHttpRequest)
			// }
		}
	})
}

function checketa(value, colname) 
{
if (value == '00/00/0000') 
   return [false,"Silahkan isi kolom ETA terlebih dahulu"];
else 
   return [true,""];
}

function show_connecting_table()
{
	var idexp = arguments[0];
	var destination = $('#tabel_schedule').jqGrid('getCell',idexp,'final_destination_city_id');
	$("#tabel_connect").dialog( {
		autoOpen: true,
		resizable: false,
		height: 'auto',
		width: 'auto',
		modal: true,
		title:"Connecting Table Expot to "+ destination,
	});
	$("#tabel_connecting").jqGrid("setGridParam", {datatype: 'json', postData: { exp_id : idexp }}).trigger("reloadGrid");
	generateTableConnecting(idexp)	 
}

function generateTableConnecting(idexp)
{
	var destination = $('#dropdownCountries').val();
	var grid = $("#tabel_connecting"), i;
	$("#tabel_connecting").jqGrid(
		{
		url	:	base_url+'/afg_c_export/displayConnectingTable',
		mtype : "post", 
		datatype: "json",
		sortname: "id",
		sortorder: "desc",
		ajaxSelectOptions: {type: "GET"},	
		postData: { exp_id : idexp },	
		colNames:
		[
			'Export Id',
			'Connecting City',
			'Connecting ETA',
			'Connecting Vesel', 
			'Connecting Voyage', 
			'Connecting ETD',
		],
		colModel:
		[
			{name:'exp_id',index:'exp_id', width:1, align:'left',editable: true, hidden: true, },		
			{name:'connecting_city',index:'connecting_city', width:25,
				editable: true, 
				edittype:"select",
				editoptions:
				{
					size: 1, maxlengh: 50,
					dataUrl:base_url+'/afg_c_export/dropdownCity'
				},
			},
			{name:'connecting_eta',index:'connecting_eta', width:25, align:"center",
				editable: true,
				editoptions: {
      			size: 30, maxlengh: 10,
      			dataInit: function(element) {
        			$(element).datepicker({dateFormat: 'dd/mm/yy'})
      			}
    			},
			},
			{name:'connecting_vesel',index:'connecting_vesel', width:25, align:'left', editable: true,
				editoptions: { size:"30", dataInit: function (el) { $(el).css('text-transform', 'uppercase'); }}
			},
			{name:'connecting_voyage',index:'connecting_voyage', width:25, align:'left', editable: true,
				editoptions: { size:"30", dataInit: function (el) { $(el).css('text-transform', 'uppercase'); }}
			},
			{name:'connecting_etd',index:'connecting_etd', width:25, align:"center",
				editable: true,
				editoptions: {
      			size: 30, maxlengh: 10,
      			dataInit: function(element) {
        			$(element).datepicker({dateFormat: 'dd/mm/yy'})
      			}
    			},
			},
		],
		multiselect:true,
		loadonce : false,
		rowNum:3,
		width: 500,
		height: 100,
		rowList:[3,6,9],
		rownumbers: true,
		gridview: true,
		modal: true,
   	caption:'',
   	editurl: base_url+'/afg_c_export/manage_data_connecting',
		cellsubmit:"clientArray",
		pager: '#page-connect',
		
		loadComplete: function() {
			$("#cb_"+grid[0].id).hide();
			$(".ui-jqgrid-titlebar-close",$("#tabel_connecting")[0].grid.cDiv).hide();
    	},
    	
    	beforeSelectRow: function(rowid, e) {
      	var cbsdis = $("tr#"+rowid+".jqgrow > td > input.cbox:disabled", grid[0]);
      	if (cbsdis.length === 0) {
      		return true; 
        		} else {
        		return false; 
       	}
    	},	
    			
		onSelectRow: function(rowid, status){
			var selected = $("tr.jqgrow > td > input.cbox:checked", grid[0]);		
			selected[rowid] = status;
			if(status)
				{ 
					var cbs = $("tr.jqgrow > td > input.cbox", grid[0]);  			
    				cbs.attr("disabled", true);  
    				selected.attr("disabled", false);  	
    			}
        		else
        		{
        			var cbs = $("tr.jqgrow > td > input.cbox:disabled", grid[0]);  		
    				cbs.attr("disabled", false);    			
        		}
		},
		
		}).navGrid('#page-connect',{view:false, edit:true, add:true, del:true, resizable: false,},
   	{
       	height: "auto", 
     		width: "auto", 
     		recreateForm: true,
     		closeAfterEdit: true, 
      	reloadAfterEdit : true, 
      	resizable: false, 
      	modal: true,
      	editCaption:"Edit Data Connecting Export",
      	viewPagerButtons: false,
      		beforeSubmit: function (postData,formId) {
					postData.exp_id=idexp;
					return [true]
				},
				afterSubmit: function () {
    				$('#tabel_connecting').jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
    				return [true];
				}
   	},
   	{
     		height: "auto", 
     		width: "auto", 
     		recreateForm: true,
     		closeAfterAdd: true, 
     		resizable: false,
     		modal: true,
			addCaption:"Add Data Connecting Export",
				beforeShowForm: function() {
					$('#id_export' ).val(destination);
					$('#id_sp').val(spId);
				},
				beforeSubmit: function (postData,formId) {
					postData.exp_id=idexp;
					return [true]
				},
				afterSubmit: function () {
    				$('#tabel_connecting').jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
    				return [true];
				}
  		},
   	{height: "auto", width: 280, modal: true},
			{closeAfterSearch : true, modal: true},
   	{}
    );
}

//// SHIPMENT_REQUEST ///
function SelectShipmentRequest()
{
    var sp_id = $('#spId').val();
    var dest = $('#dropdownCountries').val();
    var mode = $('input[name="shipment_type"]:checked').val();
    var trans = $('#dropdownTranshipmentPort').val();
    var agent = $('#dropdownAgent').val();

    // Ambil schedule_id dengan fallback
    var schedule_id = $('#scheduleId').val();

    // Debug dulu, ini penting untuk cek apakah element dan value benar
    console.log('SelectShipmentRequest debug:', {
        scheduleIdElementFound: $('#scheduleId').length,
        schedule_id: schedule_id,
        sp_id: sp_id,
        dest: dest,
        mode: mode,
        trans: trans
    });

    // Kalau element tidak ada, kasih warning jelas
    if ($('#scheduleId').length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'scheduleId tidak ditemukan',
            text: 'Input hidden dengan id="scheduleId" belum ada di halaman.'
        });
        return false;
    }

    // Kalau value kosong, tetap kirim string kosong agar key tetap muncul di payload
    schedule_id = schedule_id || '';

    $("#dialog_shipment_request").css({
        'background-image': 'url("' + base_url + '/public/images/bg-form.png")'
    });

    $("#dialog_shipment_request").dialog({
        resizable: false,
        height: 'auto',
        width: 960,
        modal: true,
        position: ['center', 10],
        title: "LIST SHIPPING INTRUCTION",
        close: function() {
            enabled_top_button();
        }
    });

    $.ajax({
        type: "POST",
        url: base_url + '/afg_c_shipment_request/open_form',
        data: {
            rowid: sp_id,
            destination: dest,
            mode: mode,
            trans: trans,
            schedule_id: schedule_id
        },
        success: function(XMLHttpRequest) {
            $("#dialog_shipment_request").dialog().html(XMLHttpRequest);
        }
    });
}

function to_table_shipment(Na)
{
	var rowCount 					= $("#tabel_sp_detail").getGridParam("reccount");
	var num 							= String.fromCharCode(65+rowCount);
	var rowCount2   				= rowCount-26;
	if(rowCount == 26 || rowCount > 26)
	{
		var num = 'Z'+String.fromCharCode(65+rowCount2);
	}
	var kode_ref 					= $("#txtRefno2").val()+''+num;
	var code 						= $("#txtRefno2").val();
	var shipment_request_id 	= Na;
	var wg_ctn 						= "";
	var shipper_id 				= $('#tabel_shipment_request').jqGrid('getCell',Na,'shipper_id');
	var no_pack_qty  				= $('#tabel_shipment_request').jqGrid('getCell',Na,'total');
	var no_pack_unit  			= $('#tabel_shipment_request').jqGrid('getCell',Na,'pack');
	var desc_goods 				= $('#tabel_shipment_request').jqGrid('getCell',Na,'description');
	var weight 						= $('#tabel_shipment_request').jqGrid('getCell',Na,'gross_weight');
	var ship_meas 					= $('#tabel_shipment_request').jqGrid('getCell',Na,'netto_weight');
	var wh_meas						= $('#tabel_shipment_request').jqGrid('getCell',Na,'measurement');
	var chgs_weight 				= "";
	var cost_meas	 				= "";
	var wh_agent 					= "";
	var bl_meas		 				= $('#tabel_shipment_request').jqGrid('getCell',Na,'measurement');
	var destination				= $('#dropdownCountries').val();
	var doc 							= "";
	var hb_l	 						= "";
	var agent_code 				= "";
	var freight		 				= $('#tabel_shipment_request').jqGrid('getCell',Na,'freight_payable');
	var freights_charges			= $('#tabel_shipment_request').jqGrid('getCell',Na,'freights_charges');
	var c_o							= $('#tabel_shipment_request').jqGrid('getCell',Na,'container_no');
	var c_s							= $('#tabel_shipment_request').jqGrid('getCell',Na,'container_size');
	var shipping_mark				= $('#tabel_shipment_request').jqGrid('getCell',Na,'shipping_unit');
	var note			 				= $('#tabel_shipment_request').jqGrid('getCell',Na,'note');
	var consignee			 		= $('#tabel_shipment_request').jqGrid('getCell',Na,'consignee');
	var notify			 			= $('#tabel_shipment_request').jqGrid('getCell',Na,'notify_party');
	var express_release				= $('#tabel_shipment_request').jqGrid('getCell',Na,'express_release');
	var shipped_on_board				= $('#tabel_shipment_request').jqGrid('getCell',Na,'shipped_on_board');
	var net_weight_tidak_dicetak	= $('#tabel_shipment_request').jqGrid('getCell',Na,'net_weight_tidak_dicetak');
	var cetak_jml_doc_origi			= $('#tabel_shipment_request').jqGrid('getCell',Na,'cetak_jml_doc_origi');
	var isLock							= $('#tabel_shipment_request').jqGrid('getCell',Na,'isLock');
	var id_stuff 						= $('#tabel_shipment_request').jqGrid('getCell',Na,'sp_id');
	
	$.ajax
		({
			async: true,
			type: "POST",
			url : base_url+'/afg_c_stuffing_plan/manage_table',
			data: 
			{
				sp_id				: id_stuff,
				kode_ref			: kode_ref,
				id_request 		: shipment_request_id,
				wg_ctn 			: wg_ctn,
				shipper 			: shipper_id, 	
				no_pack_qty 	: no_pack_qty,
				no_pack_unit 	: no_pack_unit,
				desc_goods 		: desc_goods,
				weight			: weight,
				ship_meas 		: ship_meas,
				wh_meas			: wh_meas,
				chgs_weight		: chgs_weight,
				cost_meas		: cost_meas,
				wh_agent			: wh_agent,	
				bl_meas			: bl_meas,
				destination		: destination,
				doc				: doc,
				hb_l				: hb_l,
				agent_code		: agent_code,
				freight			: freight,
				freights_charges : freights_charges,
				c_o				: c_o,
				c_s				: c_s,
				shipping_mark	: shipping_mark,
				isN				: 'FALSE',
				isOptCost		: 'FALSE',
				status_selling	: '',
				isSpotRate		: 'FALSE',
				express_release 				: express_release,
				shipped_on_board 				: shipped_on_board,
				net_weight_tidak_dicetak 	: net_weight_tidak_dicetak,
				cetak_jml_doc_origi 			: cetak_jml_doc_origi,				
				note				: note,
				consignee		: consignee,
				notify			: notify,
				num				: num,
				isLock			: isLock,
				code  			: code,
				oper				: "add",
			},
			success: function(response)
			{
				var data = $.parseJSON(response);
				if(data.status == 1)
				{
					$( '#dialog_shipment_request' ).fadeOut('slow', function() 
					{
						$("#dialog_shipment_request").dialog('close');
						update_shipment_request(Na);	
					});
					$("#tabel_sp_detail").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");	
				}
				else
				{
					return false;
				}
			},
			error:function(XMLHttpRequest)
			{
				$("#Error-Message").dialog(
				{
					autoOpen: true,
					resizable: true,
					height: 340,
					width: 650,
					modal: true,
					title: "INSERT TABLE ERROR",
					close: function() 
					{	
						
					}
				});
				$('#content-error').html(XMLHttpRequest.responseText)
			},
		}) 
}

function update_shipment_request(rowid)
{
	$.ajax
	({
		
		async: true,
		type: "POST",
		url : base_url+'/afg_c_shipment_request/hide_shipment_request',
		data: { id : rowid },
		success: function(data)
		{		
			enabled_top_button();
		//	cek_request($('#spId').val());
		},
		error:function(XMLHttpRequest)
		{
			enabled_top_button();
		}
	}) 
}

/// SHIPMENT_DETAIL ///
function add_detail()
{

	$("#Form-Input-Detail").html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
	$("#Form-Input-Detail").dialog({
		resizable: false,
		height: 510,
		width: 800,
		modal: true,
		title: "Add manual request stuffing "+$('#txtRefno2').val(),
		close: function() {	
			enabled_top_button();
			remove_detail();
			$("#tabel_sp_detail").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
		}
	})
	$("#Form-Input-Detail").css('background-image', 'url("public/images/bg-form.png")')
	$.ajax({
		async: true,
		type: "POST", 
		url : base_url+'/afg_c_stuffing_plan/open_form_detail',
		data: { id : "", mode:"add" },
		success:function(XMLHttpRequest)
		{
			$('#Form-Input-Detail').html(XMLHttpRequest);
		}
	})
}

function edit_detail(rowid)
{
	var kode_ref = $('#tabel_sp_detail').jqGrid('getCell',rowid,'kode_ref');
	
	$("#Form-Input-Detail").css('background-color','#eef').css('font-size','#11px');
	$("#Form-Input-Detail").html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
	$("#Form-Input-Detail").dialog(
	{
		resizable: false,
		height: 510,
		width: 800,
		modal: true,
		title: "Edit manual request stuffing "+kode_ref,
		close: function() 
		{	
			enabled_top_button();
			remove_detail();
			$(this).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
			$("#tabel_sp_detail").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
		}
	});
	
	$.ajax({
		async: true,
		type: "POST", 
		url : base_url+'/afg_c_stuffing_plan/open_form_detail',
		data: { id : rowid, mode: 'edit'},
		success:function(XMLHttpRequest){
				$('#Form-Input-Detail').html(XMLHttpRequest);
		}
	})
}

function cancel_delete_detail()
{
	$("#Form-Input-Detail").dialog('close');
	$("#tabel_sp_detail").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
}

function delete_sp_detail(idnya)
{
	var code = $('#txtRefno2').val();
	if (code.substr(4,3)=='300'|| code.substr(4,3)=='301'|| code.substr(4,3)=='302'|| code.substr(4,3)=='400'||code.substr(4,3)== '402'||code.substr(4,3)=='600') 
	{
		var grid = $("#tabel_sp_detail_export"), i;
	}
	else 
	{
		var grid = $("#tabel_sp_detail"), i;
	}
	var kode = grid.jqGrid('getCell',idnya,'kode_ref');
	
	$.ajax({
		async: true,
		type: "POST", 
		url : base_url+'/afg_c_stuffing_plan/manage_table',
		data: { id : idnya, oper:"del", kode: kode},
		success:function(response)
        {
            var result;
            try { result = typeof response === 'string' ? JSON.parse(response) : response; }
            catch (e) { result = null; }
            if (!result || Number(result.status) !== 1) {
                enabled_top_button();
                grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
                Swal.fire({icon:'error', title:'Delete Detail gagal', text:(result && result.message) || 'Detail tidak dihapus. Periksa log server.'});
                return;
            }
            enabled_top_button();
			remove_detail();
			$(this).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
			$("#Form-Input-Detail").dialog('close');
			//$("#tabel_sp_detail").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
			grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
		},
		error : function(response){
			var data = $.parseJSON(response.responseText);
			show_messages(data.message)
		}
	})
}


function show_income(id) {
    var code = $('#txtRefno2').val();
    var kurs = $('#txtSelling').val();
    var kurs_pajak = $('#txtKurs').val();
    var grid;

    if (code.substr(4,3)=='300'|| code.substr(4,3)=='301'|| code.substr(4,3)=='302'|| code.substr(4,3)=='400'||code.substr(4,3)== '402'||code.substr(4,3)=='600') {
        grid = $("#tabel_sp_detail_export");
    } else {
        grid = $("#tabel_sp_detail");
    }
    
    var shipper = grid.jqGrid('getCell',id,'shipper');
    var agent = grid.jqGrid('getCell',id,'agent_code');
    var kode_ref = grid.jqGrid('getCell',id,'kode_ref');
    var incoterms = grid.jqGrid('getCell',id,'incoterms');
    var customer = grid.jqGrid('getCell',id,'shipper_print');
    
    // ABAIKAN NILAI GRID, CEK LANGSUNG KE DATABASE MENGGUNAKAN ID
    $.ajax({
        type: 'POST',
        url: base_url + '/afg_c_job_sheet/check_lock',
        data: { id: id, kode_ref: kode_ref }, 
        dataType: 'json',
        success: function(res) {
            var lock_job = res.lock_job; 

            if(lock_job == '1') {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ title: 'Akses Ditolak (Terkunci)', text: 'Job sudah di close.', icon: 'error' });
                } else {
                    show_messages('Job sudah ditutup');
                }
                return;
            }
            
            if(level == '2'){
                show_messages('Hubungi CS untuk merubah data');
                return;
            }
            
            // Jika Lolos, lanjutkan eksekusi asli
            $.ajax({
                async: true,
                type: "POST", 
                url : base_url+'/afg_c_income/lock_job',
                data: { rowid: id, kode_ref: kode_ref },
                success: function(response) {
                    var data = null;
                    try { data = $.parseJSON(response); } catch (e) { return; }
                    if (!data || typeof data.status === "undefined") return;

                    if (data.status == 1) {
                        Swal.fire({ type: 'error', title: 'Oops...', text: 'Invoice sudah dicetak' });
                    } else {
                        $("#Form-Income").css({'background-image':'url("'+base_url+'/public/images/bg-form.png")'});
                        $("#Form-Income").dialog({
                            resizable: false, height: 'auto', width: 1300, modal: true, position:['center',10],
                            title: "INFORMASI INCOME - "+ kode_ref + " - "+customer,
                            close: function () {
                                enabled_top_button();
                                jqGridSaveActiveCell('#table_frm_selling');
                                jqGridSaveActiveCell('#table-frm-agent');
                                jqGridSaveActiveCell('#table-frm-coloader');
                                setTimeout(() => {
                                    triggerJobsheetRecalc({
                                        kode_ref: kode_ref, spd: id, kurs: kurs, kurs_pajak: kurs_pajak, 
                                        shipper_name: customer, destination: grid.jqGrid('getCell', id, 'destination'),
                                        volume: grid.jqGrid('getCell', id, 'bl_meas'), weight: grid.jqGrid('getCell', id, 'weight')
                                    }, true); 
                                }, 500);
                            }
                        });
                    
                        $.ajax({
                            async: true, type: "POST", url : base_url+'/afg_c_income/open_form',
                            data:{ rowid: id, kode_ref: kode_ref, lock:data.status, kurs:kurs, customer:customer, shipper:shipper, incoterms:incoterms },
                            success:function(XMLHttpRequest){
                                $("#Form-Income").dialog().html(XMLHttpRequest)
                            }
                        });
                    } 
                }
            });
        }
    });
}

function show_cost(id) {
    var dateStr = $('#combo_month').val();
    var now = Date.now();
    var ninetyDaysInMs = 90 * 24 * 60 * 60 * 1000;
    var parts = dateStr.split('/');
    var targetDate = new Date(parts[2], parts[1] - 1, parts[0]).getTime();
    var diff = Math.abs(targetDate - now);

    var code = $('#txtRefno2').val();
    var kurs = $('#txtKurs').val();
    var grid;

    if (code.substr(4, 3) == '300' || code.substr(4, 3) == '301' || code.substr(4, 3) == '302' || code.substr(4, 3) == '400' || code.substr(4, 3) == '402' || code.substr(4, 3) == '600') {
        grid = $("#tabel_sp_detail_export");
    } else {
        grid = $("#tabel_sp_detail");
    }
    
    var kode_ref = grid.jqGrid('getCell', id, 'kode_ref');
    var customer = grid.jqGrid('getCell', id, 'shipper_print');

    // ABAIKAN NILAI GRID, CEK LANGSUNG KE DATABASE MENGGUNAKAN ID
    $.ajax({
        type: 'POST',
        url: base_url + '/afg_c_job_sheet/check_lock',
        data: { id: id, kode_ref: kode_ref }, 
        dataType: 'json',
        success: function(res) {
            var lock_job = res.lock_job; 
            
            var isLocked = false;
            var lockReason = "";

            if (lock_job == '1') {
                isLocked = true;
                lockReason = "Job sudah di close.";
            } else if (level == '2') {
                isLocked = true;
                lockReason = "Akses terbatas (Level 2). Hubungi CS untuk merubah data.";
            } else if (lock_job != '2' && (level == '1' || level == '2' || level == '4') && (diff > ninetyDaysInMs)) {
                isLocked = true;
                lockReason = "Data telah melewati batas 90 hari. Hubungi ACCT.";
            }

            if (isLocked) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ title: 'Akses Ditolak (Terkunci)', text: lockReason, icon: 'error' });
                } else {
                    show_messages(lockReason);
                }
                return; 
            }

            // Jika Lolos, buka form Cost
            $("#Form-Cost").css('background-image', 'url("' + base_url + '/public/images/bg-form.png")');
            $("#Form-Cost").html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
            $("#Form-Cost").dialog({
                resizable: false, height: 650, width: 1300, modal: true,
                title: "COST INFORMATION - " + kode_ref + " - " + customer,
                beforeClose: function () {
                    var emptyInAny = hasEmptyPayable('#table-cost') || hasEmptyPayable('#table-discount') || hasEmptyPayable('#table-sc');
                    if (emptyInAny) {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({ title: 'Lengkapi data', text: 'Masih ada kolom "Payable To" yang kosong.', icon: 'warning' });
                        } else {
                            show_messages('Masih ada kolom "Payable To" yang kosong.');
                        }
                        return false; 
                    }
                    return true;
                },
                close: function () {
                    enabled_top_button();
                    var kode_ref_cost = $('#kode_ref_cost').val();
                    var spd = $('#spd_id_cost').val();
                    var kurs_val = $('#txtKurs').val(); 
                    jqGridSaveActiveCell('#table-cost');
                    jqGridSaveActiveCell('#table-discount');
                    jqGridSaveActiveCell('#table-sc');
                    setTimeout(function () {
                        triggerJobsheetRecalc({ kode_ref: kode_ref_cost, spd: spd, kurs: kurs_val }, true);
                    }, 500);
                }
            });

            $.ajax({
                async: true, type: "POST", url: base_url + '/afg_c_cost/open_form',
                data: { rowid: id, kode_ref: kode_ref, customer: customer, kurs: kurs },
                success: function (XMLHttpRequest) {
                    $('#Form-Cost').html(XMLHttpRequest);
                }
            });
        }
    });
}

function show_global_cost(id) {
    var code = $('#txtRefno2').val();
    var grid;

    if (code.substr(4,3)=='300'|| code.substr(4,3)=='301'|| code.substr(4,3)=='302'|| code.substr(4,3)=='400'||code.substr(4,3)== '402'||code.substr(4,3)=='600') {
        grid = $("#tabel_sp_detail_export");
    } else {
        grid = $("#tabel_sp_detail");
    }
    
    var kode_ref = grid.jqGrid('getCell', id, 'kode_ref');
    var refNo = $('#txtRefno2').val();

    // ABAIKAN NILAI GRID, CEK LANGSUNG KE DATABASE MENGGUNAKAN ID
    $.ajax({
        type: 'POST',
        url: base_url + '/afg_c_job_sheet/check_lock',
        data: { id: id, kode_ref: kode_ref }, 
        dataType: 'json',
        success: function(res) {
            var lock_job = res.lock_job; 

            if(lock_job == '1') {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ title: 'Akses Ditolak (Terkunci)', text: 'Job sudah di close.', icon: 'error' });
                } else {
                    show_messages('Job sudah ditutup');
                }
                return; 
            }

            $("#Form-Global-Cost").css('background-image', 'url("'+base_url+'/public/images/bg-form.png")');
            $("#Form-Global-Cost").html('<img src="'+base_url+'/public/images/loader.gif"/>');
            $("#Form-Global-Cost").dialog({
                resizable: false,
                height: 340,
                width: 960,
                modal: true,
                title: "GLOBAL COST LCL CONSOL",
                close: function () {
                    enabled_top_button();
                    jqGridSaveActiveCell('#table-global-cost');
                    var refNoClose = $('#txtRefno2').val();
                    var kurs = $('#txtKurs').val() || '';
                    setTimeout(function(){
                        triggerJobsheetRecalcPrefix(refNoClose, kurs);
                    }, 500);
                }
            });

            $.ajax({
                async: true,
                type: "POST", 
                url : base_url+'/afg_c_cost/open_form_global',
                data: { 
                    ref_no : refNo,
                    id : id,
                },
                success:function(XMLHttpRequest) {
                    $('#Form-Global-Cost').html(XMLHttpRequest);
                }
            });
        }
    });
}

function show_global_income(id) {
    var code = $('#txtRefno2').val();
    var grid;

    if (code.substr(4,3)=='300'|| code.substr(4,3)=='301'|| code.substr(4,3)=='302'|| code.substr(4,3)=='400'||code.substr(4,3)== '402'||code.substr(4,3)=='600') {
        grid = $("#tabel_sp_detail_export");
    } else {
        grid = $("#tabel_sp_detail");
    }
    
    var kode_ref = grid.jqGrid('getCell', id, 'kode_ref');
    var refNo = $('#txtRefno2').val();

    // ABAIKAN NILAI GRID, CEK LANGSUNG KE DATABASE MENGGUNAKAN ID
    $.ajax({
        type: 'POST',
        url: base_url + '/afg_c_job_sheet/check_lock',
        data: { id: id, kode_ref: kode_ref }, 
        dataType: 'json',
        success: function(res) {
            var lock_job = res.lock_job; 

            if(lock_job == '1') {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ title: 'Akses Ditolak (Terkunci)', text: 'Job sudah di close.', icon: 'error' });
                } else {
                    show_messages('Job sudah ditutup');
                }
                return; 
            }

            $("#Form-Global-Income").css('background-image', 'url("'+base_url+'/public/images/bg-form.png")');
            $("#Form-Global-Income").html('<img src="'+base_url+'/public/images/loader.gif"/>');
            $("#Form-Global-Income").dialog({
                resizable: false,
                height: 340,
                width: 960,
                modal: true,
                title: "GLOBAL INCOME LCL CONSOL",
                close: function () {
                    enabled_top_button();
                    jqGridSaveActiveCell('#table-global-income'); 
                    var refNoClose = $('#txtRefno2').val();
                    var kurs = $('#txtKurs').val() || '';
                    setTimeout(function(){
                        triggerJobsheetRecalcPrefix(refNoClose, kurs);
                    }, 500);
                }
            });

            $.ajax({
                async: true,
                type: "POST", 
                url : base_url+'/afg_c_income/open_form_global',
                data: { 
                    ref_no : refNo,
                    id : id,
                },
                success:function(XMLHttpRequest) {
                    $('#Form-Global-Income').html(XMLHttpRequest);
                }
            });
        }
    });
}

function show_job_sheet(id,code) {
		
	var kurs  = $('#txtKurs').val();
	var job_no = $('#txtRefno2').val();
	if (kurs=="") {
		show_messages('Kurs can not empty value');
		return false;
	}
	
	$("#Form-Job-Sheet").css('background-image', 'url("'+base_url+'/public/images/bg-form.png")');
	$("#Form-Job-Sheet").html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
	$("#Form-Job-Sheet").dialog({
		resizable: false,
		top:100,
		height: 350,
		width: 680,
		modal: true,
		title: "JOB SHEET ",
		close: function() 
		{	
			enabled_top_button();
			remove_detail();
			$(this).html('<img src="'+base_url+'/public/images/loader.gif"/>');
			$("#tabel_sp_detail").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
			$("#tabel_sp_detail_export").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
		}
	});
	
	$.ajax({
		async: true,
		type: "POST", 
		url : base_url+'/afg_c_job_sheet/open_form_jobsheet',
		data:{ 
			id : id,
			kode_ref : code, 
			job_no : job_no
		},
		success:function(XMLHttpRequest)
		{
			$('#Form-Job-Sheet').html(XMLHttpRequest);
		}
	})
}

function booking_stuffing(ref)
{
    console.group('=== WMS STUFFING DEBUG ===');
    console.log('1) Browser -> Gateway kode_ref:', ref);
    console.groupEnd();

    $.ajax({
        type: "POST",
        url: base_url + '/api/ticket_warehouse/post_stuffing',
        data: {
            kode_ref: ref
        },
        dataType: 'json',
        timeout: 35000,

        success: function(data)
        {
            console.group('=== WMS STUFFING SUCCESS DEBUG ===');
            console.log('kode_ref:', ref);
            console.log('debug_version:', data ? data.debug_version : null);
            console.log('debug_fill_count:', data ? data.debug_fill_count : null);
            console.log('wms_method:', data ? data.wms_method : null);
            console.log('2) Gateway -> WMS FULL payload:', data ? data.debug_payload : null);

            if (data && data.debug_payload) {
                var dbgHeader = data.debug_payload.header || data.debug_payload;
                console.log('Header/root payload:', {
                    job_no: data.kode_ref || data.debug_payload.job_no,
                    stuffing_date: dbgHeader.stuffing_date,
                    etd: dbgHeader.etd,
                    closing_date: dbgHeader.closing_date,
                    container_size: dbgHeader.container_size,
                    container_number: dbgHeader.container_number,
                    seal_number: dbgHeader.seal_number,
                    vessel: dbgHeader.vessel,
                    destination: dbgHeader.destination,
                    agent: dbgHeader.agent,
                    instruction: dbgHeader.instruction,
                    stuffing_location: dbgHeader.stuffing_location,
                    stuffing_owner: data.debug_payload.stuffing_owner
                });

                console.log(
                    'Jumlah stuffing_fills yang dikirim:',
                    Array.isArray(data.debug_payload.stuffing_fills) ? data.debug_payload.stuffing_fills.length : 0
                );

                if (Array.isArray(data.debug_payload.stuffing_fills)) {
                    console.table(data.debug_payload.stuffing_fills);
                }
            }

            console.log('3) WMS response:', data ? data.response : null);
            console.log('4) Data yang disimpan WMS:', data && data.response ? data.response.data : null);
            console.log('Full Gateway response:', data);
            console.groupEnd();

            if (
                data &&
                (
                    data.status === true ||
                    data.status === 1 ||
                    data.status === '1'
                )
            ) {
                var wmsMessage = 'Data berhasil dikirim ke gudang.';

                if (data.response && data.response.message) {
                    wmsMessage = data.response.message;
                }

                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: wmsMessage,
                    timer: 1800,
                    showConfirmButton: false
                });
                return;
            }

            var errorMessage = 'GAGAL KIRIM DATA KE GUDANG!';

            if (data && data.message) {
                errorMessage += '\n' + data.message;
            }

            if (data && data.response && data.response.message) {
                errorMessage += '\n' + data.response.message;
            }

            console.error('WMS Stuffing rejected:', data);

            Swal.fire({
                icon: 'error',
                title: 'Oh noes!',
                text: errorMessage
            });
        },

        error: function(xhr, textStatus, errorThrown)
        {
            var apiResponse = xhr.responseJSON || null;

            console.group('=== WMS STUFFING ERROR DEBUG ===');
            console.log('kode_ref:', ref);
            console.log('HTTP status:', xhr.status);
            console.log('textStatus:', textStatus);
            console.log('errorThrown:', errorThrown);
            console.log('2) Gateway -> WMS FULL payload:', apiResponse ? apiResponse.debug_payload : null);

            if (apiResponse && apiResponse.debug_payload) {
                console.log('Header/root payload:', {
                    job_no: apiResponse.debug_payload.job_no,
                    stuffing_date: apiResponse.debug_payload.header ? apiResponse.debug_payload.header.stuffing_date : undefined,
                    etd: apiResponse.debug_payload.header ? apiResponse.debug_payload.header.etd : undefined,
                    closing_date: apiResponse.debug_payload.header ? apiResponse.debug_payload.header.closing_date : undefined,
                    container_size: apiResponse.debug_payload.header ? apiResponse.debug_payload.header.container_size : undefined,
                    container_number: apiResponse.debug_payload.header ? apiResponse.debug_payload.header.container_number : undefined,
                    seal_number: apiResponse.debug_payload.header ? apiResponse.debug_payload.header.seal_number : undefined,
                    vessel: apiResponse.debug_payload.header ? apiResponse.debug_payload.header.vessel : undefined,
                    destination: apiResponse.debug_payload.header ? apiResponse.debug_payload.header.destination : undefined,
                    agent: apiResponse.debug_payload.header ? apiResponse.debug_payload.header.agent : undefined,
                    instruction: apiResponse.debug_payload.header ? apiResponse.debug_payload.header.instruction : undefined,
                    stuffing_location: apiResponse.debug_payload.header ? apiResponse.debug_payload.header.stuffing_location : undefined,
                    stuffing_owner: apiResponse.debug_payload.stuffing_owner
                });

                console.log(
                    'Jumlah stuffing_fills yang dikirim:',
                    Array.isArray(apiResponse.debug_payload.stuffing_fills) ? apiResponse.debug_payload.stuffing_fills.length : 0
                );

                if (Array.isArray(apiResponse.debug_payload.stuffing_fills)) {
                    console.table(apiResponse.debug_payload.stuffing_fills);
                }
            }

            console.log('3) WMS response:', apiResponse ? apiResponse.response : xhr.responseText);
            console.log('Full Gateway error response:', apiResponse || xhr.responseText);
            console.groupEnd();

            var message = 'GAGAL KIRIM DATA KE GUDANG!';

            if (apiResponse && apiResponse.message) {
                message = apiResponse.message;
            }

            if (apiResponse && apiResponse.response && apiResponse.response.message) {
                message += '\n' + apiResponse.response.message;
            }

            Swal.fire({
                icon: 'error',
                title: 'Oh noes!',
                text: message
            });
        }
    });
}

function myAutocomplete(elem, url) {
        $(elem).autocomplete({
        minLength: 3,
        source: url,
        autoFocus: true,
        scroll: true,
        select: function( event, ui ) { $(this).focus() }
    }).focus(function() {
        $(this).autocomplete("search", "");
    }).live("blur", function(event) {
        var autocomplete = $(this).data("autocomplete");
        var matcher = new RegExp("^" + $.ui.autocomplete.escapeRegex($(this).val()) + "$", "i");
        autocomplete.widget().children(".ui-menu-item").each(function() 
          {
            var item = $(this).data("item.autocomplete");
            if (matcher.test(item.label || item.value || item)) {
                //There was a match, lets stop checking
                autocomplete.selectedItem = item;
                return;
            }
        });
        });
}

// ===== Jobsheet Recalc on Close (GLOBAL) =====
window.__jobsheetDirty = window.__jobsheetDirty || {}; // per kode_ref

function markJobsheetDirty(kode_ref) {
  if (!kode_ref) return;
  window.__jobsheetDirty[kode_ref] = true;
}

function clearJobsheetDirty(kode_ref) {
  if (!kode_ref) return;
  delete window.__jobsheetDirty[kode_ref];
}

function isJobsheetDirty(kode_ref) {
  return !!window.__jobsheetDirty[kode_ref];
}

function jqGridSaveActiveCell(gridSel) {
    var g = $(gridSel);
    if (!g.length) return;
    
    try {
        // Cari elemen input (text/angka), select (dropdown), atau textarea yang sedang aktif diketik
        var activeElement = g.find("input[role='textbox']:visible, select:visible, textarea:visible");
        
        // Jika ada sel yang sedang terbuka, kita tembakkan simulasi
        if (activeElement.length > 0) {
            // 1. Tembakkan simulasi Enter (keyCode 13)
            var eEnter = $.Event("keydown");
            eEnter.keyCode = 13; 
            activeElement.trigger(eEnter);
            
            // 2. Tembakkan simulasi Tab (keyCode 9) sebagai pengaman
            var eTab = $.Event("keydown");
            eTab.keyCode = 9; 
            activeElement.trigger(eTab);
            
            // 3. [TAMBAHAN] Simulasi Klik Mouse di luar sel (Blur)
            // Ini memaksa kursor keluar dari inputan (sama efeknya seperti user klik di tempat lain)
            activeElement.blur();
            
            // 4. [TAMBAHAN] Trigger klik pada grid itu sendiri untuk memicu event save bawaan jqGrid
            g.trigger("click");
        }
    } catch(e) {
        console.error("Gagal save cell pada tabel " + gridSel + ": ", e);
    }
}

function triggerJobsheetRecalc(payload, force) {
  if (!payload || !payload.kode_ref) return;

  // kalau tidak force, tetap pakai dirty
  if (!force && !isJobsheetDirty(payload.kode_ref)) return;

  clearJobsheetDirty(payload.kode_ref);

  $.ajax({
    type: "POST",
    url: base_url + "/Afg_rekap/recalc_profit",
    data: payload
  });
}

function triggerJobsheetRecalcPrefix(refNo, kurs) {
  return $.ajax({
    url: base_url + '/Afg_rekap/recalc_profit_prefix',
    type: 'POST',
    dataType: 'json',
    data: { ref_no: refNo, kurs: kurs }
  });
}

// =========================================================================
// PENGAMAN 1: AUTO-SAVE SAAT KLIK AREA KOSONG / LUAR TABEL
// =========================================================================
// =========================================================================
// SIMPAN MANUAL SAAT TOMBOL DI MENU BILL OF LADING DIKLIK
// =========================================================================

// =========================================================================
// SIMPAN MANUAL SAAT TOMBOL DI MENU BILL OF LADING DIKLIK (EVENT DELEGATION)
// =========================================================================

// =========================================================================
// SIMPAN MANUAL SAAT TOMBOL DI MENU BILL OF LADING DIKLIK
// =========================================================================

$(document).on('click', '#bill-of-ladding div[id]', function(e) {
    var grids = ["#tabel_sp_detail", "#tabel_sp_detail_export"];
    
    grids.forEach(function(gridId) {
        var grid = $(gridId);
        
        // Hanya proses grid yang ada dan tampil di layar
        if (grid.length && grid.is(':visible')) {
            
            // Langkah 1: Paksa tutup dan simpan cell yang mungkin masih aktif diketik
            try {
                var iRow = grid.jqGrid('getGridParam', 'iRow');
                var iCol = grid.jqGrid('getGridParam', 'iCol');
                if (iRow != null && iCol != null) {
                    grid.jqGrid('saveCell', iRow, iCol);
                }
            } catch(err) {}
            
            // Langkah 2: Proses penyimpanan via AJAX untuk SEMUA row yang tercentang
            var selRows = grid.jqGrid('getGridParam', 'selarrrow');
            
            if (selRows && selRows.length > 0) {
                var rowsToProcess = selRows.slice(); // Copy array
                
                // --- PERBAIKAN: TENTUKAN TIPE OTOMATIS DARI NAMA GRID ---
                // Jika tabel yang sedang diproses adalah tabel export, maka set type = 'export'
                // Jika tidak, set string kosong (atau 'import', sesuaikan dengan backend Anda)
                var currentType = (gridId === "#tabel_sp_detail_export") ? 'import' : 'export';
                
                for (var i = 0; i < rowsToProcess.length; i++) {
                    var rowid = rowsToProcess[i];
                    
                    // --- PERBAIKAN: Masukkan currentType alih-alih 'button_save' ---
                    saving_data(rowid, '', '', null, null, currentType);
                }
            }
        }
    });
});

// =========================================================================
// PENGAMAN 2: CEGAH USER MENUTUP TAB / REFRESH HALAMAN SAAT EDITING
// =========================================================================
window.addEventListener('beforeunload', function (e) {
    var gridExp = $("#tabel_sp_detail_export");
    var gridDet = $("#tabel_sp_detail");
    
    var isEditingExport = false;
    var isEditingDetail = false;

    // Gunakan try-catch untuk mencegah error "Cannot read property 'length' of undefined"
    // yang bisa mematikan event beforeunload secara diam-diam.
    try {
        if (gridExp.length) {
            var selExp = gridExp.jqGrid('getGridParam', 'selarrrow');
            if (selExp && selExp.length > 0) isEditingExport = true;
        }
        if (gridDet.length) {
            var selDet = gridDet.jqGrid('getGridParam', 'selarrrow');
            if (selDet && selDet.length > 0) isEditingDetail = true;
        }
    } catch (err) {
        // Abaikan jika grid belum terbentuk sempurna
    }
    
    if (isEditingExport || isEditingDetail) {
        // Standar wajib untuk browser modern (Chrome, Firefox, Safari)
        e.preventDefault(); 
        
        // Return value wajib diisi (string kosong sudah cukup, karena pesan custom akan diabaikan browser)
        e.returnValue = ''; 
        return ''; 
    }
});

// Ganti selector menjadi 'li a' untuk menangkap klik pada menu navigasi Anda
$(document).on('click', 'li a', function(e) {
    var gridExp = $("#tabel_sp_detail_export");
    var gridDet = $("#tabel_sp_detail");
    
    var isEditing = false;
    
    try {
        if (gridExp.length && gridExp.jqGrid('getGridParam', 'selarrrow').length > 0) isEditing = true;
        if (gridDet.length && gridDet.jqGrid('getGridParam', 'selarrrow').length > 0) isEditing = true;
    } catch (err) {}
    
    if (isEditing) {
        // Cegah link menu terbuka
        e.preventDefault(); 
        
        // Simpan tujuan link URL-nya dari atribut href pada tag <a>
        var targetUrl = $(this).attr('href'); 
        
        // Pastikan href bukan "#" (link kosong) atau undefined sebelum memunculkan alert
        if (targetUrl && targetUrl !== "#") {
            // Tampilkan pesan custom dengan SweetAlert
            Swal.fire({
                title: 'Tabel Masih Diedit!',
                text: 'Anda memiliki data yang masih belum tersimpan. Silakan simpan (enter) sebelum pindah menu.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Abaikan perubahan data',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Jika user memaksa pindah, arahkan ke URL menu tujuan
                    window.location.href = targetUrl;
                }
            });
        }
    }
});

$(document).ajaxError(function(event, xhr) {
    if (xhr.status === 403) {
        let message = 'Anda tidak memiliki akses untuk melakukan aksi ini.';

        try {
            const res = JSON.parse(xhr.responseText);
            if (res.message) {
                message = res.message;
            }
        } catch (e) {}

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                title: 'Anda tidak memiliki akses menu ini.',
                text: message,
                confirmButtonText: 'Mengerti'
            });
        } else {
            alert(message);
        }

        return false;
    }
});

// ====================================================================
// FUNGSI UNTUK MENARIK DATA TIMELINE MASTER JOB (KIRI) DENGAN FRAMEWORK TETAP
// ====================================================================
function load_timeline_data(kode_ref) {
    if (!kode_ref || kode_ref === '') {
        $('#gcl-left-timeline').hide();
        return;
    }
    
    // Jangan munculkan panel dulu, biarkan disembunyikan sampai server merespon

    $.ajax({
        url: base_url + '/afg_c_stuffing_plan/get_timeline_data',
        type: 'POST',
        data: { kode_ref: kode_ref },
        success: function(response) {
            var res = $.parseJSON(response);

            if(res && res.status) {
                // CEK APAKAH INI DATA MASTER JOB LAMA TANPA AUDIT
                if (res.has_data === false) {
                    $('#gcl-left-timeline').hide(); // Sembunyikan sepenuhnya
                    return;
                }

                // Jika ada data, baru munculkan panelnya
                $('#gcl-left-timeline').fadeIn();
				$('#handle-left').fadeIn(); // Tampilkan tombol panah
                $('[id="tl-kode-ref"]').text(kode_ref);
                $('.tl-cargo-summary').hide(); 

                var html = '';
                
                $.each(res.timeline, function(index, ev) {
                    var icon = '';
                    var bgClass = '';
                    var titleColor = '';
                    var metaHtml = '';

                    // 1. Jika langkah ini SUDAH SELESAI
                    // 1. Jika langkah ini SUDAH SELESAI / TERCATAT
                    if (ev.is_completed) {
                        // Cek apakah di dalam deskripsi terdapat tanda Peringatan Masa Depan
                        if (ev.desc.indexOf('⚠️') !== -1) {
                            icon = '⚠️';
                            bgClass = 'background: #ff4d4d; color: #fff; box-shadow: 0 0 8px #ff4d4d;'; // Warna Merah Terang
                            titleColor = 'color: #ff4d4d;';
                        } else {
                            icon = '✓';
                            bgClass = 'background: #28a745; color: #fff;'; // Hijau Normal
                            titleColor = 'color: #fff;';
                        }
                        metaHtml = '<div style="color: #FFCA33; font-size: 9.5px; margin: 2px 0;">' + ev.date + ' • Dikonfirmasi oleh: <b>' + ev.confirmed_by + '</b></div>';
                    }
                    // 2. Jika langkah ini SEDANG DIKERJAKAN (Current)
                    else if (ev.is_current) {
                        icon = '⏳';
                        bgClass = 'background: #FFCA33; color: #00245E; box-shadow: 0 0 5px #FFCA33;';
                        titleColor = 'color: #FFCA33;';
                        metaHtml = '<div style="color: #FFCA33; font-size: 9.5px; margin: 2px 0; font-style: italic;">Menunggu tindakan...</div>';
                    } 
                    // 3. Jika langkah ini BELUM DIKERJAKAN (Pending)
                    else {
                        icon = '';
                        bgClass = 'background: transparent; border: 1px solid rgba(255,255,255,0.3);';
                        titleColor = 'color: rgba(255,255,255,0.4);';
                        metaHtml = '';
                    }

                    // Teks deskripsi agar redup jika pending
                    var descColor = (ev.is_completed || ev.is_current) ? 'color: rgba(255,255,255,0.8);' : 'color: rgba(255,255,255,0.3);';

                    html += '<div style="position: relative; margin-bottom: 20px;">' +
                                '<div style="position: absolute; left: -21px; top: 0px; width: 14px; height: 14px; border-radius: 50%; text-align: center; line-height: 14px; font-size: 9px; font-weight: bold; ' + bgClass + '">' + icon + '</div>' +
                                '<div style="font-weight: bold; ' + titleColor + '">' + ev.title + '</div>' +
                                metaHtml +
                                '<div style="' + descColor + ' line-height: 1.3; margin-top: 3px;">' + ev.desc + '</div>' +
                            '</div>';
                });
                
                $('[id="timeline-master-content"]').html(html);
            } else {
                $('#gcl-left-timeline').hide();
            }
        },
        error: function() {
            $('#gcl-left-timeline').hide();
        }
    });
}

// ====================================================================
// FUNGSI UNTUK MENARIK DATA TIMELINE ANAK JOB (KANAN)
// ====================================================================
function load_house_timeline_data(kode_ref_anak, shipper_name) {
    if (!kode_ref_anak || kode_ref_anak === '') {
        $('#gcl-right-timeline').hide();
        return;
    }

    $('#gcl-right-timeline').fadeIn();
    
    $('[id="tl-hbl-ref"]').html(kode_ref_anak + '<br><span style="font-size:10px; font-weight:normal;">' + shipper_name + '</span>');
    $('[id="timeline-house-content"]').html('<div style="color:rgba(255,255,255,0.7); text-align:right;">Memuat data...</div>');

    $.ajax({
        url: base_url + '/afg_c_stuffing_plan/get_house_timeline_data',
        type: 'POST',
        data: { no_job: kode_ref_anak },
        success: function(response) {
            // Parsing string ke JSON Object
            var res = $.parseJSON(response);

            if(res && res.status) {
                var html = '';
                if(res.events && res.events.length > 0) {
                    $.each(res.events, function(index, ev) {
                        var icon = index === 0 ? '⏳' : '✓';
                        var bgClass = index === 0 ? 'background: #FFCA33; color: #00245E; box-shadow: 0 0 5px #FFCA33;' : 'background: #28a745; color: #fff;';
                        var titleColor = index === 0 ? 'color: #FFCA33;' : 'color: #fff;';

                        html += '<div style="position: relative; margin-bottom: 20px;">' +
                                    '<div style="position: absolute; right: -21px; top: 0px; width: 14px; height: 14px; border-radius: 50%; text-align: center; line-height: 14px; font-size: 9px; font-weight: bold; ' + bgClass + '">' + icon + '</div>' +
                                    '<div style="font-weight: bold; ' + titleColor + '">' + ev.event_title + '</div>' +
                                    '<div style="color: #FFCA33; font-size: 10px; margin: 2px 0;">' + ev.event_date_formatted + '</div>' +
                                    '<div style="color: rgba(255,255,255,0.8); line-height: 1.3;">' + ev.remarks + '</div>' +
                                '</div>';
                    });
                } else {
                    html = '<div style="color:rgba(255,255,255,0.7); text-align:right;">Belum ada riwayat tercatat.</div>';
                }
                $('[id="timeline-house-content"]').html(html);
            }
        },
        error: function() {
            $('[id="timeline-house-content"]').html('<div style="color:rgba(255,255,255,0.7); text-align:right;">Koneksi terputus.</div>');
        }
    });
}

// Taruh tombolnya di dekat #txtContSealNumber di view form job (v_job_edit / v_job_edit_fcl dst),
// misalnya: <button type="button" onclick="open_stuffing_photos()">📷 Stuffing Photos</button>

/** Cari grid detail (customer/house) yang BENAR-BENAR aktif untuk job yang sedang dibuka:
 *  harus ada di DOM, kelihatan di layar, dan sungguhan sudah diinisialisasi sebagai jqGrid. */
function get_active_stuffing_detail_grid() {
	var candidates = ['#tabel_sp_detail_export', '#tabel_sp_detail'];

	// Prioritas 1: yang visible DAN sudah jadi jqGrid beneran
	for (var i = 0; i < candidates.length; i++) {
		var $g = $(candidates[i]);
		if ($g.length && $g.is(':visible') && $g.jqGrid('getGridParam', 'selarrrow') !== undefined) {
			return $g;
		}
	}
	// Prioritas 2 (fallback): yang penting sudah jadi jqGrid beneran, walau lagi tidak kelihatan
	for (var i = 0; i < candidates.length; i++) {
		var $g = $(candidates[i]);
		if ($g.length && $g.jqGrid('getGridParam', 'selarrrow') !== undefined) {
			return $g;
		}
	}
	return null;
}

/** Ganti label tombol Stuffing Photos: "FOTO STUFFING" kalau tidak ada row dicentang,
 *  "FOTO CARGO" kalau ada row customer yang dicentang. */
function update_stuffing_photos_button_label() {
	var detailGrid   = get_active_stuffing_detail_grid();
	var selectedRows = detailGrid ? (detailGrid.jqGrid('getGridParam', 'selarrrow') || []) : [];
	var label = selectedRows.length > 0 ? '📷 FOTO CARGO' : '📷 FOTO STUFFING';
	$('#btnStuffingPhotos').text(label);
}

// Pantau klik checkbox di KEDUA kemungkinan grid detail (row checkbox class "cbox",
// dan checkbox "select all" di header, id-nya "cb_<gridId>"). Delegated ke document
// supaya tetap jalan walau grid-nya baru dibuat/di-reload belakangan.
$(document).on('click', '#tabel_sp_detail .cbox, #tabel_sp_detail_export .cbox, [id^="cb_tabel_sp_detail"]', function () {
	// jqGrid update selarrrow SETELAH event click asli, kasih jeda kecil biar keburu ke-update
	setTimeout(update_stuffing_photos_button_label, 50);
});

$(document).ready(function () {
	update_stuffing_photos_button_label();
});

// ======= SEMENTARA: tombol test manual, skip logika grid, buat isolasi masalah =======
// Taruh tombol ini di sebelah tombol Stuffing Photos yang asli, contoh:
// <button type="button" onclick="open_stuffing_photos_manual_test()">🧪 Test Manual</button>
function open_stuffing_photos_manual_test() {
	var cont_seal_no = $('#txtContSealNumber').val();
	var tanggal       = $('#txtStuffingDate').val();
	var warehouse     = $('#dropdownWarehouse').val();

	if (!cont_seal_no || !tanggal || !warehouse) {
		Swal.fire('Container, tanggal stuffing, dan warehouse harus terisi dulu.');
		return;
	}

	var customer_letter = prompt('Ketik huruf customer manual (kosongkan utk folder container saja):', 'A');
	if (customer_letter === null) return; // user klik cancel

	do_locate_stuffing_photos(warehouse, tanggal, cont_seal_no, customer_letter.trim());
}
// ======= akhir bagian sementara =======

function open_stuffing_photos() {
    var cont_seal_no = $('#txtContSealNumber').val();
    var tanggal       = $('#txtStuffingDate').val();   // format dd/mm/yyyy, sesuai datepicker-nya
    var warehouse     = $('#dropdownWarehouse').val();

    if (!cont_seal_no || !tanggal || !warehouse) {
        Swal.fire('Container, tanggal stuffing, dan warehouse harus terisi dulu.');
        return;
    }

    // Kalau ada row detail (house/customer) yang DICENTANG -> ambil kode hurufnya (kolom `num`)
	// Jangan tebak grid mana yang aktif dari kode ref (ternyata tidak selalu cocok dengan
	// grid yang benar-benar di-render). Cek LANGSUNG: grid yang kelihatan di layar DAN
	// sudah sungguhan jadi jqGrid (getGridParam tidak undefined).
	var detailGrid = get_active_stuffing_detail_grid();

	var customer_letter = '';
	var selectedDetailRows = [];
	if (detailGrid) {
		selectedDetailRows = detailGrid.jqGrid('getGridParam', 'selarrrow') || [];
	}
	if (selectedDetailRows.length > 0) {
		customer_letter = detailGrid.jqGrid('getCell', selectedDetailRows[0], 'num');
	}

	console.log('grid dipakai:', detailGrid ? detailGrid.attr('id') : '(tidak ketemu)', 'selected:', selectedDetailRows, 'letter:', customer_letter);

	do_locate_stuffing_photos(warehouse, tanggal, cont_seal_no, customer_letter);
}

function do_locate_stuffing_photos(warehouse, tanggal, cont_seal_no, customer_letter) {

	$.ajax({
		type: 'POST',
		url: base_url + '/stuffing/locate',
		dataType: 'json',
		data: {
			warehouse: warehouse,
			tanggal: tanggal,
			cont_seal_no: cont_seal_no,
			customer_letter: customer_letter   // ganti dari 'customer' ke 'customer_letter'
		},
		success: function (res) {
			if (res.error) { Swal.fire('Gagal', res.error, 'error'); return; }
			if (res.date_note) {
				Swal.fire({
					icon: 'warning',
					title: 'Tanggal Stuffing Tidak Sesuai',
					text: res.date_note,
					confirmButtonText: 'Mengerti'
				});
			}
			render_stuffing_photos_dialog(res);
		},
		error: function (jqXHR) {
			// Server tetap balas JSON meski status HTTP-nya bukan 200 (400/404/dst),
			// jadi baca pesan aslinya dulu sebelum jatuh ke pesan generik.
			var msg = 'Gagal menghubungi server.';
			try {
				var body = jqXHR.responseJSON || JSON.parse(jqXHR.responseText);
				if (body && body.error) msg = body.error;
			} catch (e) { /* biarkan pesan generik kalau body-nya bukan JSON */ }
			Swal.fire('Gagal', msg, 'error');
		}
	});
}

// Ganti render_stuffing_photos_dialog() yang lama dengan versi ini.
// open_stuffing_photos() TIDAK berubah, tetap panggil render_stuffing_photos_dialog(res).

function render_stuffing_photos_dialog(res) {
    // CSS cukup di-inject sekali (aman dipanggil berkali-kali, browser abaikan duplikat id)
    if (!document.getElementById('stuffing-photo-style')) {
        $('<style id="stuffing-photo-style">')
            .text(`
                .sp-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
                    gap: 12px;
                    padding: 4px 2px 12px;
                }
                .sp-card {
                    border: 1px solid #e0e0e0;
                    border-radius: 8px;
                    background: #fff;
                    overflow: hidden;
                    text-align: center;
                    transition: box-shadow .15s, transform .15s;
                }
                .sp-card:hover { box-shadow: 0 3px 10px rgba(0,0,0,.18); transform: translateY(-2px); }
                .sp-thumb {
                    width: 100%;
                    height: 110px;
                    object-fit: cover;
                    display: block;
                    cursor: zoom-in;
                    background: #f2f2f2;
                }
                .sp-icon {
                    width: 100%;
                    height: 110px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 42px;
                    background: #f7f7f7;
                }
                .sp-name {
                    font-size: 11px;
                    padding: 6px 6px 8px;
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    color: #333;
                }
                .sp-name a { color: inherit; text-decoration: none; }
                .sp-empty { padding: 20px; text-align: center; color: #777; }
                .sp-lightbox {
                    position: fixed; inset: 0; background: rgba(0,0,0,.85);
                    display: flex; align-items: center; justify-content: center;
                    z-index: 99999; cursor: zoom-out;
                }
                .sp-lightbox img {
                    max-width: 92vw; max-height: 92vh;
                    box-shadow: 0 4px 30px rgba(0,0,0,.5);
                    border-radius: 4px;
                }
            `)
            .appendTo('head');
    }

    var files = res.files || [];
    var folders = res.folders || [];

    var iconFor = function (name) {
        var ext = (name.split('.').pop() || '').toLowerCase();
        if (ext === 'pdf') return '📕';
        if (['doc', 'docx'].indexOf(ext) !== -1) return '📘';
        if (['xls', 'xlsx', 'csv'].indexOf(ext) !== -1) return '📗';
        if (ext === 'db') return '🗄️';
        return '📄';
    };

    var html = '';
    if (res.date_note) {
        html += '<div style="background:#fff3cd;border:1px solid #ffe08a;color:#7a5c00;'
            + 'padding:8px 12px;border-radius:6px;margin-bottom:10px;font-size:12px;">'
            + '⚠️ ' + res.date_note + '</div>';
    }
    html += '<div class="sp-grid">';

    files.forEach(function (f) {
        var url = base_url + '/stuffing/download'
            + '?warehouse=' + encodeURIComponent(res.warehouse)
            + '&p=' + encodeURIComponent(f.rel_path);
        var isImage = /\.(jpe?g|png|gif|webp)$/i.test(f.name);

        html += '<div class="sp-card">';
        if (isImage) {
            html += '<img class="sp-thumb" src="' + url + '" loading="lazy" onclick="sp_open_lightbox(\'' + url.replace(/'/g, "\\'") + '\')">';
        } else {
            html += '<a href="' + url + '" target="_blank"><div class="sp-icon">' + iconFor(f.name) + '</div></a>';
        }
        html += '<div class="sp-name" title="' + f.name + '"><a href="' + url + '" target="_blank">' + f.name + '</a></div>';
        html += '</div>';
    });

    html += '</div>';

    if (files.length === 0) {
        html += '<div class="sp-empty">Tidak ada file di folder ini' +
            (folders.length ? '. Sub-folder yang ada: ' + folders.map(function (x) { return x.name; }).join(', ') : '') +
            '.</div>';
    }

    $('<div></div>').html(html).dialog({
        title: 'Stuffing Photos — ' + res.resolved_path,
        width: 760,
        height: 560,
        modal: true,
        resizable: true,
        close: function () { $(this).remove(); }
    });
}

// Lightbox sederhana: klik thumbnail -> tampil full size, klik lagi/ESC -> tutup.
function sp_open_lightbox(url) {
    var $box = $('<div class="sp-lightbox"></div>').append($('<img>').attr('src', url));
    $box.on('click', function () { $box.remove(); });
    $(document).one('keydown.spLightbox', function (e) {
        if (e.key === 'Escape') { $box.remove(); }
    });
    $('body').append($box);
}