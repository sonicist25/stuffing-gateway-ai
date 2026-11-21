<?php echo link_tag('application/third_party/css/booking.css');?>
<?php echo link_tag('application/third_party/css/trn_bill_of_ladding.css');?>

<style>
/* CSS Custom untuk Tabs & Header Container */
.custom-tabs-header {
    display: flex;
    justify-content: space-between; /* Memisahkan tab ke kiri dan button ke kanan */
    align-items: flex-end; /* Menyejajarkan item di bagian bawah (menempel pada garis) */
    border-bottom: 3px solid #378de5;
    margin-bottom: 15px;
    margin-top: 10px;
}

/* Bagian Kiri (Tabs) */
.tab-group {
    display: flex;
}
.custom-tabs-header .tab-link {
    background-color: #f1f1f1;
    border: 1px solid #ccc;
    border-bottom: none;
    outline: none;
    cursor: pointer;
    padding: 10px 25px;
    font-size: 14px;
    font-weight: bold;
    color: #555;
    transition: 0.3s;
    border-radius: 8px 8px 0 0;
    margin-right: 5px;
}
.custom-tabs-header .tab-link:hover {
    background-color: #e5f2ff;
    color: #378de5;
}
.custom-tabs-header .tab-link.active {
    background-color: #378de5;
    color: white;
    border-color: #378de5;
}

/* Bagian Kanan (Action Buttons) */
.action-buttons {
    margin-bottom: 5px; /* Jarak sedikit agar tombol tidak menabrak garis biru */
}
.action-buttons button {
    background-color: #28a745; /* Warna hijau untuk New Booking */
    color: white;
    border: none;
    padding: 8px 15px;
    border-radius: 4px;
    cursor: pointer;
    font-weight: bold;
    font-size: 13px;
    margin-left: 10px;
    box-shadow: 0px 2px 4px rgba(0,0,0,0.1);
}
.action-buttons button#btnDraftBL {
    background-color: #17a2b8; /* Warna biru untuk Cetak Draft */
}
.action-buttons button:hover {
    opacity: 0.85;
}
</style>

<!-- Elemen Header: Tabs di kiri, Buttons di kanan -->
<div class="custom-tabs-header">
    <div class="tab-group">
        <button class="tab-link active" onclick="changeTab('finalize', this)">📅 Belum Masuk Job</button>
        <button class="tab-link" onclick="changeTab('booked', this)">✓ Sudah Masuk Job</button>
    </div>
    
    <div class="action-buttons">
        <button id="btnNewBooking" onclick="add_new_booking()">+ New Booking</button>
        <button id="btnDraftBL" onclick="openBL()">📄 Cetak Draft BL</button>
    </div>
</div>

<div id="FORM_DISPLAY_BOOKING"></div>
<table id="tabel_shipment_request"></table>
<!-- ... (Elemen div lainnya tetap sama) ... -->
	<div id="page-req"></div>
	<div id="Form-Cancel-Booking"></div>
	<div id="Form-Open-Booking"></div>
	<div id="Form-BC"></div>
	<div id="Form-Print-BC"></div>
	<div id="FORM_DISPLAY_BL"></div>
	<div id="agent-insert"></div>
	<div id="print-option"></div>
	<div id="PRINT-FORM-BL"></div>

<!-- TAMBAHKAN BLOK INI DI BAWAH PAGER JQGRID -->
<div id="alert-etd-merah" style="display: none; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 12px; border-radius: 5px; margin-top: 15px; font-size: 14px; font-family: Arial, sans-serif; box-shadow: 0px 2px 4px rgba(0,0,0,0.1);">
    <!-- Teks peringatan akan di-generate otomatis oleh JavaScript -->
</div>
<script type="text/javascript">
/*
 * Dialog PDF mengikuti ukuran layar (mis. laptop 1366 x 768): tidak melebihi jendela browser,
 * PDF mengisi penuh dialog sehingga toolbar viewer (download / print) selalu terlihat.
 * Dialog bisa di-resize; PDF ikut menyesuaikan.
 */
window.dialogSizeForViewport = window.dialogSizeForViewport || function (maxW, maxH) {
    return {
        width:  Math.max(320, Math.min(maxW, $(window).width() - 20)),
        height: Math.max(300, Math.min(maxH, $(window).height() - 20))
    };
};
window.openPdfDialog = window.openPdfDialog || function (selector, title, url, tag) {
    var $d = $(selector);
    var size = dialogSizeForViewport(1000, 820);
    if ($d.hasClass('ui-dialog-content')) {
        $d.dialog('destroy');
    }
    $d.html('').css({ padding: 0, overflow: 'hidden' }).dialog({
        autoOpen: true,
        modal: true,
        resizable: true,
        title: title,
        width: size.width,
        height: size.height,
        position: { my: 'center top', at: 'center top+10', of: window },
        close: function () { $(this).html(''); }
    });
    var src = String(url).replace(/"/g, '&quot;');
    $d.html(tag === 'iframe'
        ? '<iframe src="' + src + '" style="width:100%;height:100%;border:0;display:block;"></iframe>'
        : '<object data="' + src + '" type="application/pdf" style="width:100%;height:100%;display:block;">' +
          '<p style="padding:10px">PDF tidak bisa ditampilkan di sini. <a href="' + src + '" target="_blank">Buka / download PDF</a></p></object>');
};

var currentBookingTab = 'finalize';
$(document).ready(function () {
    var sp_id = $('#spId').val();
    var dest = $('#dropdownCountries').val();
    var mode = $('input[name="shipment_type"]:checked').val();
    var grid = $("#tabel_shipment_request"), i;

    $("#tabel_shipment_request").jqGrid({
        url: window.location.origin + '/pricelist/shipment_booking/displayShipmentRequestFiltered', 
        mtype: "post",  
        datatype: "json",
        postData: {'destination': dest, 'mode': mode},
        colNames: [
            'Booking Code',
            'HBL',
            'Mode',
            'SI Number',
            'ETD',
            'Shipper',
            'Destination',
            'Consol Dest',
            'Consignee',
            'Notify Party',
            'Party',
            'Description of Goods',
            'Weight',
            'Volume',
            'Marking',
            'Incoterm',
            'Stuffing Location',
            'Number of BL',
            'Prepared',

            // hidden, hanya untuk grouping
            'Job Group','Job No','Titipan'
        ],
        colModel: [
            {name: 'real_id', index: 'book.booking_code', width: 140, align: "center", key: true, searchoptions: {sopt: ['cn']}},
            {name: 'house_bl', index: 'book.house_bl', width: 130, align: "center", searchoptions: {sopt: ['cn']}},
            {name: 'mode', index: 'book.mode', width: 50, align: "center", searchoptions: {sopt: ['cn']}},
            {name: 'si_number', index: 'book.si_number', align: "center", searchoptions: {sopt: ['cn']}},
            {name: 'pickup_datetime', index: 'pickup_datetime', width: 80, align: "center", search: false,
             cellattr: function (rowId, cellValue, rawObject, cm, rdata) {
                 var today = new Date();
                 var pickupDate = new Date(cellValue);
                 if (pickupDate < today) {
                     return 'style="background-color: #f2dede; border: 1px solid #d9534f;"';
                 }
                 return '';
             }},
            {name: 'trnstuffingplan_booking_detail.shipper_name', index: 'trnstuffingplan_booking_detail.shipper_name', width: 180, align: "left", searchoptions: {sopt: ['cn']}},
            {name: 'destination_city', index: 'book.destination_city', width: 100, align: "left", searchoptions: {sopt: ['cn']}},
            {name: 'destination', index: 'book.real_dest', width: 100, align: "left", searchoptions: {sopt: ['cn']}},
            {name: 'con_name', index: 'trnstuffingplan_booking_detail.consignee_name', align: "left", hidden: true, searchoptions: {sopt: ['cn']}},
            {name: 'not_name', index: 'trnstuffingplan_booking_detail.notify_name', align: "left", hidden: true, searchoptions: {sopt: ['cn']}},
            {name: 'quantity', index: 'quantity', width: 70, align: "left", search: false},
            {name: 'description_print', index: 'description_print', align: "left", hidden: true, search: false},
            {name: 'weight', index: 'weight', align: "right", width: 50, search: false},
            {name: 'volume', index: 'volume', align: "right", width: 50, search: false, summaryType: 'sum', summaryTpl: 'Total: {0}'},
            {name: 'shipping_mark', index: 'shipping_mark', align: "left", hidden: true, search: false},
            {name: 'incoterm', index: 'incoterm', align: "center", width: 50, search: false},
            {name: 'warehouse', index: 'book.warehouse', align: "center", searchoptions: {sopt: ['cn']}},
            {name: 'number_of_bl', index: 'book.number_of_bl', align: "center", searchoptions: {sopt: ['cn']}},
            {name: 'pic_document', index: 'pic_document', width: 100, align: "left", searchoptions: {sopt: ['cn']}},
            {name: 'job_group', index: 'job_group', hidden: true, search: false},
            // search No Job
            {
                name: 'job_no',
                index: 'book.job_no',
                hidden: true,

                // default false karena pertama kali buka = Belum Masuk Job
                search: false,

                searchoptions: {
                    sopt: ['cn'],
                    searchhidden: true
                }
            },
            // nama cabang pemilik bila titipan coload dari cabang lain: hanya pemilik yang boleh edit / hapus
            {name: 'is_titipan', index: 'is_titipan', hidden: true, search: false}
        ],
        shrinkToFit: false,
        multiselect: true,
        loadonce: false,
        cellEdit: false,
        rowNum: 150,
        width: 960,
        height: 600,
        rowList: [150, 300, 450],
        viewrecords: true,
        rownumbers: true,
        gridview: true,
        modal: true,
        pager: '#page-req',
        caption: "",
        grouping: true,
        groupingView: {
            groupField: ["destination"],
            groupColumnShow: [true],
            groupText: ['<b>{0} - {1} Shipments - Volume {volume} Cbm</b>'],
            groupOrder: ["asc"],
            groupSummary: [false],
            groupCollapse: false
        },
        editurl: window.location.origin + '/afg_c_stuffing_plan/manage_data_request',
        loadComplete: function (data) {

            $("#cb_" + grid[0].id).hide();

            // =========================================================
            // TAB SUDAH MASUK JOB TIDAK PERLU WARNING ETD
            // =========================================================
            if (currentBookingTab === 'booked') {

                $("#alert-etd-merah")
                    .stop(true, true)
                    .hide()
                    .html('');

                return;
            }


            // =========================================================
            // WARNING ETD HANYA UNTUK BELUM MASUK JOB
            // =========================================================
            var rowIds = grid.jqGrid('getDataIDs');
            var countMerah = 0;
            var today = new Date();

            today.setHours(0, 0, 0, 0);

            for (var i = 0; i < rowIds.length; i++) {

                var dateVal = grid.jqGrid(
                    'getCell',
                    rowIds[i],
                    'pickup_datetime'
                );

                if (dateVal && dateVal !== "0000-00-00") {

                    var rowDate = new Date(dateVal);

                    if (!isNaN(rowDate) && rowDate < today) {
                        countMerah++;
                    }
                }
            }


            if (countMerah > 0) {

                $("#alert-etd-merah").html(
                    "⚠️ <b>PERHATIAN:</b> Terdapat " +
                    "<b><span style='font-size:16px;'>" +
                    countMerah +
                    "</span></b> Booking yang sudah melewati batas waktu (ETD). <br>" +
                    "Harap segera lakukan tindakan: " +
                    "<b>Batalkan (Hapus)</b> booking tersebut atau " +
                    "<b>Proses ke Job</b>."
                ).slideDown();

            } else {

                $("#alert-etd-merah").slideUp();
            }
        },
        beforeSelectRow: function (rowshipment_request_id, e) {
            var cbsdis = $("tr#" + rowshipment_request_id + ".jqgrow > td > input.cbox:disabled", grid[0]);
            if (cbsdis.length === 0) {
                return true;
            } else {
                return false;
            }
        },
        onSelectRow: function (rowid, status) {
            var id_booking = grid.jqGrid('getCell', rowid, 'real_id');
            var booking_code = grid.jqGrid('getCell', rowid, 'real_id');
            ready_booking(id_booking, booking_code);
        },
        ondblClickRow: function (rowid) {
            var id_booking = grid.jqGrid('getCell', rowid, 'real_id');
            var booking_code = grid.jqGrid('getCell', rowid, 'real_id');
            if (isTitipanRow(rowid)) {
                showTitipanInfo(rowid, booking_code);
                return;
            }
            edit_booking(id_booking, booking_code);
        },
    }).navGrid('#page-req', {edit: false, add: false, del: false},
    {
        height: "auto", 
        width: "auto",  
        closeAfterEdit: true, 
        reloadAfterEdit : true, 
        modal: true, 
        editCaption: "Edit Data Request", 
        viewPagerButtons: false,
        beforeShowForm: function (frm) {
            var dlgDiv = $("#editmod" + grid[0].id);
            dlgDiv[0].style.top = Math.round($(window).height() / Math.round($(window).height())) + "px";
            dlgDiv[0].style.left = Math.round($(window).width() / 2.8) + "px";
        },
        afterSubmit: function () {
            $('#tabel_shipment_request').jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
            return [true];
        }
    },
    {
        height: "auto", 
        width: 'auto', 
        closeAfterAdd: true, 
        reloadAfterAdd : true, 
        viewPagerButtons: false, 
        modal: true, 
        addCaption: "Add Request Manual"
    },
   {
        modal: true,
        height: "auto",
        width: "auto",
        closeAfterSearch: true,
        searchCaption: "Find Data Shipment Request",

        // supaya pilihan field dibuat ulang setelah pindah tab
        recreateFilter: true
    }, 
    {})
    // =========================================================
    // DELETE AMAN - HANYA TAB BELUM MASUK JOB
    // =========================================================
    .navButtonAdd('#page-req', {
        id: 'btnDeleteBookingSafe',
        caption: '',
        buttonicon: 'ui-icon-trash',
        title: 'Hapus Booking',
        position: 'first',
        onClickButton: function () {
            if (currentBookingTab !== 'finalize') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Delete tidak diizinkan',
                    text: 'Booking yang sudah masuk job tidak boleh dihapus dari Booking List.'
                });
                return;
            }

            var selected = grid.jqGrid('getGridParam', 'selarrrow') || [];

            if (selected.length === 0) {
                Swal.fire({
                    icon: 'info',
                    title: 'Pilih Booking',
                    text: 'Pilih satu booking yang akan dihapus.'
                });
                return;
            }

            if (selected.length > 1) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Pilih satu Booking',
                    text: 'Penghapusan booking harus dilakukan satu per satu.'
                });
                return;
            }

            var rowid = selected[0];
            var booking_code = grid.jqGrid('getCell', rowid, 'real_id');

            if (isTitipanRow(rowid)) {
                showTitipanInfo(rowid, booking_code);
                return;
            }

            if (!booking_code) {
                Swal.fire({
                    icon: 'error',
                    title: 'Booking Code kosong',
                    text: 'Booking code tidak ditemukan pada row yang dipilih.'
                });
                return;
            }

            // Pakai dialog + endpoint delete aman existing:
            // /pricelist/afg_c_booking/cancel_booking
            cancel_booking(booking_code, booking_code);
        }
    });
});

// =========================================================
// TITIPAN COLOAD DARI CABANG LAIN
// Tampil di daftar cabang stuffing untuk dipantau; edit & hapus oleh cabang pemilik.
// =========================================================
function isTitipanRow(rowid) {
    return $.trim(String($("#tabel_shipment_request").jqGrid('getCell', rowid, 'is_titipan') || '')) !== '';
}

function showTitipanInfo(rowid, booking_code) {
    var owner = $.trim(String($("#tabel_shipment_request").jqGrid('getCell', rowid, 'is_titipan') || '')) || 'lain';
    Swal.fire({
        icon: 'info',
        title: 'Titipan coload dari ' + owner,
        html: 'Booking <b>' + $('<span>').text(booking_code).html() + '</b> milik cabang <b>' +
              $('<span>').text(owner).html() + '</b> dan di-stuffing oleh cabang ini.<br><br>' +
              'Perubahan data dan penghapusan dilakukan oleh cabang ' + $('<span>').text(owner).html() + '.'
    });
}

function add_new_booking() {
		$("#FORM_DISPLAY_BOOKING").html('<b style="font-size: 11px; font-style:italic; text-align:center">LOADING...</b>');
		$("#FORM_DISPLAY_BOOKING").dialog({
				autoOpen: true,
				resizable: false,
				height: 'auto',
				width: '100%',
				modal: true,
				title :"SHIPING INTRUCTIONS",
				position: {
						my: "top",    // Posisi dialog
						at: "top",    // Posisi relatif terhadap viewport
						of: window    // Relatif terhadap window
					},
				close: function(){	
					$(this).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
					$("#FORM_DISPLAY_BOOKING").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
				}
		});
		
		$.ajax({
				type: "POST", 
				url : window.location.origin+'/pricelist/afg_c_booking/open_booking_form',
				data: {},
				success:function(XMLHttpRequest)
				{
					$("#FORM_DISPLAY_BOOKING").css('background-color','#fff');
					$("#FORM_DISPLAY_BOOKING").html(XMLHttpRequest)
				},	
				error:function(XMLHttpRequest)
				{
					$('#FORM_DISPLAY_BOOKING').html(XMLHttpRequest.responseText)
				}	
		});
	//}
}
function edit_booking(id_booking,booking_code)
{
	$("#FORM_DISPLAY_BOOKING").html('<b style="font-size: 11px; font-style:italic; text-align:center">LOADING...</b>');
		$("#FORM_DISPLAY_BOOKING").dialog({
				autoOpen: true,
				resizable: true,
				width: '100%',
				modal: true,
				title :"SHIPING INTRUCTIONS",
				position: {
					my: "top",    // Posisi dialog
					at: "top",    // Posisi relatif terhadap viewport
					of: window    // Relatif terhadap window
				},
						close: function(){	
					$(this).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
					//$("#form_display_booking").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
				}
		});
		
		$.ajax({
				type: "POST", 
				url : window.location.origin+'/pricelist/afg_c_booking/collect_data_booking',
				dataType: 'json',
				data: {id_booking:id_booking,booking_code:booking_code},
				success:function(response)
				{
					//console.log(response);
					$('#dropdownPortLoading').val(booking.origin_city);
					
				},	
				error:function(XMLHttpRequest)
				{
					$('#FORM_DISPLAY_BOOKING').html(XMLHttpRequest.responseText)
				}
		});
}
function ready_booking(id_booking, booking_code) {
    // Gunakan jQuery .on('click') alih-alih set attr onclick
    $('#btnOpenBc')
        .off('click') // Buang event listener sebelumnya agar tidak double (penting)
        .on('click', function() {
            open_bc(id_booking, booking_code);
        });

    // Booking yang sudah masuk Job tidak boleh dihapus dari Booking List.
    if (currentBookingTab === 'booked') {
        $('#btnCancelBooking')
            .prop('disabled', true)
            .off('click') // Pastikan tidak bisa diklik
            .attr('title', 'Booking sudah masuk Stuffing Plan. Hapus/keluarkan dari Stuffing Plan melalui flow job, bukan menghapus Booking.');
    } else {
        $('#btnCancelBooking')
            .prop('disabled', false)
            .attr('title', '')
            .off('click') // buang handler lama agar tidak menumpuk
            .on('click', function() {
                cancel_booking(id_booking, booking_code);
            });
    }
}

function cancel_booking(id_booking, booking_code) {
    // Titipan coload dari cabang lain: hapus hanya oleh cabang pemilik (backend juga menolak)
    var grid = $("#tabel_shipment_request");
    var titipanRow = $.grep(grid.jqGrid('getDataIDs'), function (id) {
        return grid.jqGrid('getCell', id, 'real_id') === booking_code && isTitipanRow(id);
    });
    if (titipanRow.length) {
        showTitipanInfo(titipanRow[0], booking_code);
        return false;
    }

    // UI safety. Backend tetap punya guard sendiri.
    if (currentBookingTab === 'booked') {
        Swal.fire({
            icon: 'warning',
            title: 'Booking Sudah Masuk Job',
            text: 'Booking yang sudah masuk Stuffing Plan tidak boleh dihapus dari Booking List.'
        });
        return false;
    }

    // 1. Ambil elemen form dan bersihkan isinya
    let $form = $("#Form-Cancel-Booking");
    $form.empty().css('font-size', '12px');

    // 2. Buat elemen teks konfirmasi
    let messageText = $('<div>').html(
        'Are you sure will cancel booking <br><br><b>' + booking_code + '</b><br><br><hr>'
    );
    
    // 3. Buat tombol NO (Batal)
    let btnNo = $('<input>', {
        type: 'button',
        value: 'NO',
        id: 'button-cancel-detail',
        css: { float: 'right', width: '80px', height: '30px' }
    }).on('click', function() {
        cancel_delete_detail();
    });

    // 4. Buat tombol DELETE dengan membungkus variabel ke dalam fungsi anonim
    let btnDelete = $('<input>', {
        type: 'button',
        value: 'DELETE',
        id: 'button-delete-detail',
        css: { float: 'right', width: '80px', height: '30px', margin: '0 10px 5px 0' }
    }).on('click', function() {
        delete_booking(id_booking, booking_code);
    });

    // 5. Masukkan semua elemen yang sudah dibuat ke dalam form dialog
    $form.append(messageText).append(btnNo).append(btnDelete);

    // 6. Panggil UI Dialog
    $form.dialog({
        autoOpen: true,
        resizable: false,
        top: 100,
        height: 'auto',
        width: 350,
        modal: true,
        title: "Delete Confirm",
        close: function() {
            $(this).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
        }
    });
}
function open_bc(id_booking,booking_code)
{
    // Source saat ini tidak mempunyai handler SEND BC yang aman.
    // Tombol lama salah memanggil delete_booking(), jadi sementara hanya PREVIEW.
    $("#Form-Open-Booking").css('font-size','12px').html(
        'Booking Confirmation of <br><br><b>'+booking_code+'</b>'+
        '<br><br><hr>'+
        '<input type="button" value="PREVIEW BC" id="button-preview-bc" style="float: right; width: 100; height: 30" onclick="cetak_bc('+id_booking+',\''+booking_code+'\')">'
    );

    $("#Form-Open-Booking").dialog({
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
    });
}

function cancel_delete_detail()
{
	$("#Form-Cancel-Booking").dialog('close');
	$("#tabel_shipment_request").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
}


function delete_booking(id_booking, booking_code) {
    // 1. Tutup pop-up dialog konfirmasi
    $("#Form-Cancel-Booking").dialog("close");

    // 2. Lakukan request AJAX ke Controller
    $.ajax({
        // URL sudah diperbaiki ke endpoint asli aplikasi Anda
        url: window.location.origin + '/pricelist/afg_c_booking/cancel_booking', 
        type: 'POST',
        dataType: 'json',
        data: {
            booking: booking_code 
        },
        beforeSend: function() {
            // Tampilkan loading
            Swal.fire({
                title: 'Menghapus...',
                text: 'Mohon tunggu',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
        },
        success: function(response) {
            // Tutup loading spinner secara paksa sebelum memunculkan popup baru
            Swal.close();

            if (response.status == true) {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: 'Booking berhasil dihapus.'
                }).then(() => {
                    // Refresh data tabel jqGrid secara diam-diam tanpa memuat ulang seluruh halaman
                    $("#tabel_shipment_request").trigger("reloadGrid");
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: response.message || 'Terjadi kesalahan sistem.'
                });
            }
        },
        error: function(xhr, status, error) {
            // Tutup loading spinner secara paksa jika terjadi error jaringan/404/500
            Swal.close();
            
            // Ambil pesan server: JSON (normal) atau halaman error HTML (PHP/DB error)
            let res = xhr.responseJSON;
            if (!res && xhr.responseText) {
                try { res = JSON.parse(xhr.responseText); } catch (e) { res = null; }
            }
            let errorMessage = (res && res.message) ? res.message : '';
            if (!errorMessage && xhr.responseText) {
                let txt = $('<div>').html(xhr.responseText).text().replace(/\s+/g, ' ').trim();
                errorMessage = txt.substring(0, 300);
            }
            if (!errorMessage) {
                errorMessage = 'Server tidak merespons (HTTP ' + xhr.status + ').';
            }

            // 409 = ditolak aturan bisnis (sudah masuk job / coload dari Osline-Winfast), bukan error server
            Swal.fire({
                icon: xhr.status === 409 ? 'warning' : 'error',
                title: xhr.status === 409 ? 'Booking tidak bisa dihapus' : 'Error Jaringan / Server',
                text: errorMessage
            });

            console.error("Detail Error:", xhr.status, xhr.responseText);
        }
    });
}

function cetak_bc(id_booking,booking_code)
{
	// Ukuran mengikuti layar (sebelumnya 980 x 660 dengan iframe 955 x 610)
	openPdfDialog('#Form-Print-BC', "Print Out Job Sheet " + booking_code,
		'<?php echo base_url("pricelist/afg_c_booking/cetak_bc") ?>/' + booking_code + '/' + id_booking + '/true', 'iframe');
		
	
}

function openBL() {
  const grid = $("#tabel_shipment_request");
  const selectedRowId = grid.jqGrid('getGridParam', 'selrow'); // Ambil ID baris terpilih

  if (!selectedRowId) {
    alert("Silakan pilih satu baris terlebih dahulu.");
    return;
  }

  const selectedRealId = grid.jqGrid('getCell', selectedRowId, 'real_id'); // Ambil nilai real_id
  const selectedB = grid.jqGrid('getCell', selectedRowId, 'booking_code'); // Ambil nilai real_id
  // Contoh: kirim ke server, tampilkan form, dll.
  //console.log("Selected real_id:", selectedRealId);

  // Contoh pemanggilan fungsi berikutnya
  cetakDraftBL(selectedRealId,selectedB);
}



function cetakDraftBL(selectedRealId,selectedB) {
	//load_file_head('css,'+'application/third_party/css/trn_bill_of_ladding.css');

	var rowid = selectedRealId;
	var code = selectedB;
	
	
		$("#FORM_DISPLAY_BL").html('<b style="font-size: 11px; font-style:italic; text-align:center">LOADING...</b>');
		$("#FORM_DISPLAY_BL").dialog({
				autoOpen: true,
				resizable: true,
				// maks 990 x 700, tapi tidak melebihi layar (laptop 1366 x 768); isi bisa di-scroll
				height: dialogSizeForViewport(990, 700).height,
				width: dialogSizeForViewport(990, 700).width,
				position: { my: 'center top', at: 'center top+10', of: window },
				modal: true,
				title :"PREVIEW DRAFT HBL",
				close: function(){	
					$(this).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
					$("#tabel_shipment_request").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
				}
		});
		var url = window.location.origin+'/afg_c_bill_of_ladding/draft_bl_form';
		var destination = $('#dropdownCountries').val();
		$.ajax({
				async: true,
				type: "POST", 
				url : url,
				data: { rowid },
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

function changeTab(status, element) {

    // Simpan state tab untuk proteksi action button / warning.
    currentBookingTab = status;

    // =========================================================
    // 1. ACTIVE TAB
    // =========================================================
    $('.tab-link').removeClass('active');
    $(element).addClass('active');

    var grid = $("#tabel_shipment_request");
    var gridId = grid[0].id;
    var newUrl = '';

    if (status === 'finalize') {

        newUrl = window.location.origin +
            '/pricelist/shipment_booking/displayShipmentRequestFiltered';

    } else if (status === 'booked') {

        newUrl = window.location.origin +
            '/pricelist/shipment_booking/displayShipmentBookedFiltered';
    }


    // =========================================================
    // 2. TUTUP SEARCH DIALOG JIKA MASIH TERBUKA
    // =========================================================
    var searchDialog = $("#searchmodfbox_" + gridId);

    if (searchDialog.length) {

        // Klik tombol close dialog jqGrid
        searchDialog
            .find(".ui-jqdialog-titlebar-close")
            .trigger("click");
    }


    // =========================================================
    // 3. BERSIHKAN STATE SEARCH TAB SEBELUMNYA
    // =========================================================
    var postData = grid.jqGrid('getGridParam', 'postData') || {};

    delete postData._search;
    delete postData.searchField;
    delete postData.searchString;
    delete postData.searchOper;
    delete postData.filters;


    // Parameter utama tetap dipertahankan
    postData.destination = $('#dropdownCountries').val();
    postData.mode = $('input[name="shipment_type"]:checked').val();


    // =========================================================
    // 4. GANTI ENDPOINT
    // =========================================================
    grid.jqGrid("setGridParam", {

        url: newUrl,
        datatype: 'json',

        page: 1,

        // sangat penting:
        search: false,

        postData: postData

    });


    // =========================================================
    // 5. PASTIKAN BUTTON SEARCH AKTIF
    // =========================================================
    $("#search_" + gridId)
        .removeClass("ui-state-disabled")
        .removeAttr("disabled")
        .prop("disabled", false);


    // =========================================================
    // 6. ATUR GROUPING SESUAI TAB
    // =========================================================
    var groupingView = grid.jqGrid('getGridParam', 'groupingView');

    if (status === 'booked') {

        // Sudah Masuk Job:
        // JOB NO - DESTINATION
        groupingView.groupField = ['job_group'];
        groupingView.groupColumnShow = [false];
        groupingView.groupText = [
            '<b>{0} - {1} Shipments - Volume {volume} Cbm</b>'
        ];
        groupingView.groupOrder = ['asc'];
        groupingView.groupSummary = [false];
        groupingView.groupCollapse = false;

    } else {

        // Belum Masuk Job:
        // tetap DESTINATION seperti sebelumnya
        groupingView.groupField = ['destination'];
        groupingView.groupColumnShow = [true];
        groupingView.groupText = [
            '<b>{0} - {1} Shipments - Volume {volume} Cbm</b>'
        ];
        groupingView.groupOrder = ['asc'];
        groupingView.groupSummary = [false];
        groupingView.groupCollapse = false;
    }

    grid.jqGrid('setGridParam', {
        grouping: true,
        groupingView: groupingView
    });

    // =========================================================
    // SEARCH COLUMN SESUAI TAB
    // =========================================================
    if (status === 'booked') {

        grid.jqGrid('setColProp', 'job_no', {
            search: true
        });

    } else {

        grid.jqGrid('setColProp', 'job_no', {
            search: false
        });
    }

    // =========================================================
    // HAPUS CACHE SEARCH DIALOG
    // supaya field search dibuat ulang
    // =========================================================
    var searchBoxId = "#searchmodfbox_" + gridId;

    if ($(searchBoxId).length) {
        $(searchBoxId).remove();
    }



    // =========================================================
    // 7. PROTEKSI DELETE SESUAI TAB
    // =========================================================
    grid.jqGrid('resetSelection');

    if (status === 'booked') {
        $('#btnCancelBooking')
            .prop('disabled', true)
            .removeAttr('onclick')
            .attr('title', 'Booking sudah masuk Stuffing Plan dan tidak boleh dihapus dari Booking List.');

        // Hide delete icon pada tab Sudah Masuk Job.
        $('#btnDeleteBookingSafe').hide();
    } else {
        // Belum ada row terpilih setelah pindah tab.
        $('#btnCancelBooking')
            .prop('disabled', false)
            .removeAttr('onclick')
            .attr('title', '');

        // Show delete icon hanya pada tab Belum Masuk Job.
        $('#btnDeleteBookingSafe').show();
    }

    // =========================================================
    // 8. RELOAD GRID
    // =========================================================
    grid.trigger("reloadGrid");
}
</script>