<div id="main-page" style="font-size: 11px;">
  <input type="hidden" id="sp_id" disabled="disabled" style="height:25px;" value="<?php echo $id ?>">
  <input id="destination" value="<?php echo $destination ?>" type="hidden">
  <input id="mode" value="<?php echo $mode ?>" type="hidden">
  <input id="trans_city" value="<?php echo $trans?>" type="hidden">
  <input id="schedule_id" value="<?php echo $schedule_id?>" type="hidden">

  <table id="tabel_shipment_request"></table>
  <div id="page-req"></div>
</div>

<style>
/* Pastikan semua sel di baris expired ikut diwarnai */
.ui-jqgrid-btable tr.rowExpired td {
  background-color: #ff9999 !important;
}
</style>

<script type="text/javascript">
$(function () {
  generateTabelShipment();
});

function parseDateFlexible(str) {
  if (!str) return null;
  var s = String(str).trim();
  s = s.split(" ")[0].split("T")[0]; // buang jam jika ada
  var sep = s.indexOf("/") !== -1 ? "/" : "-";
  var parts = s.split(sep);

  if (parts[0].length === 4) {
    return new Date(parts[0], parts[1]-1, parts[2]); // YYYY-MM-DD
  }
  if (parts[2] && parts[2].length === 4) {
    return new Date(parts[2], parts[1]-1, parts[0]); // DD-MM-YYYY
  }
  var dt = new Date(s);
  return isNaN(dt.getTime()) ? null : dt;
}

function normalizeJobNum(v) {
  return $.trim(String(v || '')).toUpperCase();
}

function getNextAvailableJobNum(detailGrid, reservedNums) {
  var used = {};
  reservedNums = reservedNums || [];

  // Ambil huruf yang sudah ada di grid detail
  var ids = detailGrid.jqGrid('getDataIDs') || [];
  for (var i = 0; i < ids.length; i++) {
    var n = normalizeJobNum(detailGrid.jqGrid('getCell', ids[i], 'num'));
    if (n !== '') {
      used[n] = true;
    }
  }

  // Huruf yang sudah dialokasikan dalam proses insert batch saat ini
  for (var r = 0; r < reservedNums.length; r++) {
    var rn = normalizeJobNum(reservedNums[r]);
    if (rn !== '') {
      used[rn] = true;
    }
  }

  // A sampai Z
  for (var a = 0; a < 26; a++) {
    var code = String.fromCharCode(65 + a);
    if (!used[code]) {
      return code;
    }
  }

  // Ikuti pola lama Anda: ZA, ZB, ZC, dst
  for (var b = 0; b < 26; b++) {
    var code2 = 'Z' + String.fromCharCode(65 + b);
    if (!used[code2]) {
      return code2;
    }
  }

  // Fallback kalau sudah sangat banyak
  var idx = 0;
  while (true) {
    var fallback = 'Z' + idx;
    if (!used[fallback]) {
      return fallback;
    }
    idx++;
  }
}

function generateTabelShipment() {
  var sp_id = $('#sp_id').val();
  var dest = $('#destination').val();
  var mode = $('#mode').val();
  var trans_city = $('#trans_city').val();
  var schedule_id = $('#schedule_id').val();
  var grid = $("#tabel_shipment_request");

  grid.jqGrid({
    url: window.location.origin + '/afg_c_shipment_request/displayShipmentRequestFiltered',
    mtype: "post",
    datatype: "json",
    postData: { destination: dest, mode: mode, trans: trans_city,schedule_id : schedule_id },
    sortname: "book.created_at",
    sortorder: "desc",
    colNames: [
      'No Booking (BC)',
      'HBL Number',
      'SI Number',
      'Cargo Type',
      'ETD',
      'Booked by',
      'Shipper',
      'Destination',
      'Party',
      'Commodities',
      'Create By',
      'Cbm',
      'Weight',
      'Marking',
      'Incoterm',
      'BL Type',
    ],
    colModel: [
      { name: 'real_id', index: 'real_id', width: 140, align: "left", search: true, searchoptions: { sopt: ['cn'] } },
      { name: 'house_bl', index: 'house_bl', width: 120, align: "left", search: true, searchoptions: { sopt: ['cn'] } },
      { name: 'si_number', index: 'si_number', width: 100, align: "left", search: true, searchoptions: { sopt: ['cn'] } },
      { name: 'cargo_type', index: 'cargo_type', width: 80, align: "center", search: true, searchoptions: { sopt: ['cn'] } },
      { name: 'expect_etd', index: 'expect_etd', width: 80, align: "center", editable: true, search: false },
      { name: 'forwarder_name', index: 'forwarder_name', width: 130, align: "left", hidden: true },
      { name: 'shipper_name', index: 'trnstuffingplan_booking_detail.shipper_name', width: 160, align: "left", search: true, searchoptions: { sopt: ['cn'] } },
      { name: 'destination_city', index: 'destination_city', width: 100, align: "left", search: true, searchoptions: { sopt: ['cn'] } },
      { name: 'quantity', index: 'quantity', width: 80, align: "left", editable: true, search: false },
      { name: 'description_print', index: 'description_print', align: "left", editable: true, search: false },
      { name: 'pic_document', index: 'pic_document', width: 100, align: "left", search: false, summaryType: '', summaryTpl: 'Total Volume' },
      { name: 'volume', index: 'volume', width: 80, align: "center", editable: true, search: false, summaryType: 'sum', summaryTpl: '{0} Cbm' },
      { name: 'weight', index: 'weight', width: 80, align: "center", editable: true, search: false },
      { name: 'marking', index: 'marking', align: "left", hidden: true, search: false, edittype: "textarea" },
      { name: 'incoterm', index: 'incoterm', width: 80, align: "center", editable: true, search: false },
      { name: 'number_of_bl', index: 'number_of_bl', width: 80, align: "center", editable: true, search: false },
    ],
    shrinkToFit: false,
    multiselect: true,
    loadonce: false,
    cellEdit: false,
    rowNum: 15,
    width: 925,
    height: 370,
    rowList: [15, 30, 45],
    viewrecords: true,
    rownumbers: true,
    gridview: true,
    pager: '#page-req',
    caption: "",
    
    jsonReader: {
      repeatitems: true,
      cell: "cell",
      id: "id"
    },

    loadComplete: function (data) {
      var today = new Date(); today.setHours(0,0,0,0);

      if (data && Array.isArray(data.rows)) {
        for (var i = 0; i < data.rows.length; i++) {
          var rowId = data.rows[i].id;
          // PERHATIAN: Posisi ETD bergeser ke cell index ke 4
          var etdStr = data.rows[i].cell[4]; 
          var etdDate = parseDateFlexible(etdStr);

          if (etdDate && etdDate < today) {
            $("#tabel_shipment_request").jqGrid('setRowData', rowId, {}, 'rowExpired');
            $("#" + $.jgrid.jqID(rowId)).attr("title","⚠ ETD sudah lewat dari hari ini");
          }
        }
      }
      $("#cb_" + grid[0].id).hide();
    },

    grouping: true,
    groupingView: {
      groupField: ["expect_etd"],
      groupColumnShow: [true],
      groupText: ["<b>ETD: {0} - Volume {volume} Cbm</b>"],
      groupCollapse: false,
      groupOrder: ["asc"],
      groupSummary: false,
      showSummaryOnHide: true
    }

  }).navGrid('#page-req', { edit: false, add: false, del: false, refresh: false, search: false })
    .navButtonAdd('#page-req', {
	  caption: "Add to stuffing plan",
	  buttonicon: "ui-icon-plus",

	  onClickButton: function () {

		/*
		 * =========================================================
		 * ANTI DOUBLE CLICK
		 * =========================================================
		 */
		if (window.shipmentRequestBatchBusy === true) {
		  return;
		}


		var selIds = grid.jqGrid(
		  "getGridParam",
		  "selarrrow"
		);


		if (!selIds || selIds.length === 0) {

		  Swal.fire({
			icon: 'warning',
			title: 'Belum ada booking dipilih',
			text: 'Pilih shipment request terlebih dahulu.'
		  });

		  return;
		}


		/*
		 * =========================================================
		 * DATA UMUM STUFFING PLAN
		 * =========================================================
		 */
		var code        = $.trim($("#txtRefno2").val() || '');
		var etd         = $.trim($("#txtETDJKT").val() || '');
		var closing     = $.trim($('#txtClosingDate').val() || '');
		var eta         = $.trim($('#txtETA').val() || '');

		var destination = $.trim($('#dropdownCountries').val() || '');
		var trans       = $.trim($('#dropdownTranshipmentPort').val() || '');

		var sp_id       = $.trim($("#spId").val() || '');

		var vesselName  = $.trim($('#txtVessel').val() || '');
		var voyage      = $.trim($('#txtVoyage').val() || '');

		var vessel = $.trim(
		  vesselName + ' ' + voyage
		);

		var wh_id       = $.trim($('#dropdownWarehouse').val() || '');
		var agent       = $.trim($('#dropdownAgent').val() || '');
		var mbl         = $.trim($("#txtMotherBL").val() || '');
		var container   = $.trim($("#txtContSealNumber").val() || '');


		/*
		 * =========================================================
		 * VALIDASI FRONTEND
		 * =========================================================
		 */
		if (!sp_id || parseInt(sp_id, 10) <= 0) {

		  Swal.fire({
			icon: 'error',
			title: 'Stuffing plan tidak valid',
			text: 'SP ID tidak ditemukan.'
		  });

		  return;
		}


		if (!code) {

		  Swal.fire({
			icon: 'error',
			title: 'Job number kosong',
			text: 'Job number harus tersedia sebelum booking ditambahkan.'
		  });

		  return;
		}


		/*
		 * =========================================================
		 * BUILD BATCH ITEMS
		 *
		 * Tidak kirim $.ajax di dalam loop lagi.
		 * =========================================================
		 */
		var detailGrid = $("#tabel_sp_detail");
		var reservedNums = [];
		
		/*
		 * Snapshot row stuffing detail SEBELUM proses add.
		 * Dipakai untuk menghapus row transient jika request gagal.
		 */
		var detailRowIdsBefore = (
		  detailGrid.jqGrid('getDataIDs') || []
		).slice();

		function cleanupNewDetailRows() {

		  var currentIds =
			detailGrid.jqGrid('getDataIDs') || [];

		  var beforeMap = {};

		  for (var i = 0; i < detailRowIdsBefore.length; i++) {
			beforeMap[String(detailRowIdsBefore[i])] = true;
		  }

		  for (var j = 0; j < currentIds.length; j++) {

			var rowId = String(currentIds[j]);

			/*
			 * Row tidak ada sebelum Add diklik
			 * berarti row dibuat selama proses Add.
			 */
			if (!beforeMap[rowId]) {

			  detailGrid.jqGrid(
				'delRowData',
				currentIds[j]
			  );
			}
		  }
		}

		var items = [];


		for (var i = 0; i < selIds.length; i++) {

		  var bookingId = $.trim(
			grid.jqGrid(
			  "getCell",
			  selIds[i],
			  "real_id"
			) || ''
		  );


		  if (!bookingId) {
			continue;
		  }


		  /*
		   * Cari suffix kosong.
		   *
		   * A, B, C ... Z, ZA, ZB ...
		   */
		  var num = getNextAvailableJobNum(
			detailGrid,
			reservedNums
		  );


		  /*
		   * Tandai supaya item berikutnya tidak memakai suffix sama.
		   */
		  reservedNums.push(num);


		  var kode_ref = code + num;


		  items.push({
			booking_id: bookingId,

			code: code,
			kode_ref: kode_ref,
			num: num,

			sp_id: sp_id,

			agent: agent,
			warehouse_id: wh_id,

			etd: etd,
			closing: closing,
			eta: eta,

			dest: destination,
			trans: trans,

			vessel: vessel,

			mbl: mbl,
			container: container
		  });
		}


		if (items.length === 0) {

		  Swal.fire({
			icon: 'error',
			title: 'Booking tidak valid',
			text: 'Tidak ada booking valid yang dapat diproses.'
		  });

		  return;
		}


		/*
		 * =========================================================
		 * ESCAPE UNTUK SWEETALERT HTML
		 * =========================================================
		 */
		function escapeHtml(value) {

		  return $('<div>')
			.text(value == null ? '' : String(value))
			.html();
		}


		/*
		 * =========================================================
		 * PROCESSING STATE
		 * =========================================================
		 */
		window.shipmentRequestBatchBusy = true;


		Swal.fire({
		  title: 'Memproses booking',
		  html:
			'Sedang memasukkan <b>'
			+ items.length
			+ '</b> booking ke stuffing plan...',
		  allowOutsideClick: false,
		  allowEscapeKey: false,
		  showConfirmButton: false,

		  didOpen: function () {
			Swal.showLoading();
		  }
		});


		/*
		 * =========================================================
		 * SINGLE AJAX REQUEST
		 * =========================================================
		 */
		$.ajax({

		  type: 'POST',

		  url:
			window.location.origin
			+ '/afg_c_shipment_request/kirim_ke_detail_trn',

		  dataType: 'json',

		  data: {
			items: JSON.stringify(items)
		  },


		  /*
		   * =======================================================
		   * SUCCESS HTTP
		   * =======================================================
		   */
		  success: function (response) {

		  if (
			!response
			|| !response.summary
			|| !Array.isArray(response.results)
		  ) {

			cleanupNewDetailRows();

			Swal.fire({
			  icon: 'error',
			  title: 'Response tidak valid',
			  text: 'Server mengembalikan response yang tidak dikenali.'
			});

			return;
		  }

		  var summary = response.summary;
		  var results = response.results;


		  /*
		   * =====================================================
		   * SEMUA GAGAL
		   *
		   * Tidak boleh ada row baru tersisa.
		   * =====================================================
		   */
		  if (parseInt(summary.success, 10) === 0) {

			cleanupNewDetailRows();
		  }


		  /*
		   * =====================================================
		   * ADA YANG BERHASIL
		   *
		   * Buang row transient terlebih dahulu,
		   * kemudian reload dari database supaya hanya data
		   * yang benar-benar COMMIT yang muncul.
		   * =====================================================
		   */
		  if (parseInt(summary.success, 10) > 0) {

			cleanupNewDetailRows();

			getStuffingPlan('pilih');

			/*
			 * Booking yang sudah berubah menjadi booked
			 * akan hilang dari shipment request.
			 */
			grid.trigger('reloadGrid');
		  }



			/*
			 * =====================================================
			 * BUILD RESULT HTML
			 * =====================================================
			 */
			var successRows = [];
			var failedRows = [];
			var warningRows = [];


			for (var r = 0; r < results.length; r++) {

			  var result = results[r] || {};

			  var bookingId = escapeHtml(
				result.booking_id || '-'
			  );

			  var message = escapeHtml(
				result.message || ''
			  );


			  if (parseInt(result.status, 10) === 1) {

				if (result.already_exists) {

				  successRows.push(
					'<li>'
					+ '<b>'
					+ bookingId
					+ '</b>'
					+ ' — sudah ada, tidak diinsert ulang: ' + escapeHtml(result.job_no || '-')
					+ '</li>'
				  );

				} else {

				  successRows.push(
					'<li>'
					+ '<b>'
					+ bookingId
					+ '</b>'
					+ ' — berhasil: ' + escapeHtml(result.job_no || '-')
					+ '</li>'
				  );
				}


				/*
				 * Database sukses tetapi email gagal
				 * tidak dianggap kegagalan booking.
				 */
				if (result.warning) {
                  warningRows.push('<li><b>' + bookingId + '</b> — ' + escapeHtml(result.warning) + '</li>');
                }
                if (
				  result.email
				  && parseInt(result.email.status, 10) === 0
				) {

				  warningRows.push(
					'<li>'
					+ '<b>'
					+ bookingId
					+ '</b>'
					+ ' — '
					+ escapeHtml(result.email.message)
					+ '</li>'
				  );
				}

			  } else {

				failedRows.push(
				  '<li>'
				  + '<b>'
				  + bookingId
				  + '</b>'
				  + ' — '
				  + message
                  + (result.error ? '<br><small>' + escapeHtml(result.stage || '') + ': ' + escapeHtml(result.error) + '</small>' : '')
				  + '</li>'
				);
			  }
			}


			var resultHtml = '';


			resultHtml +=
			  '<div style="text-align:left;">'
			  + '<div style="margin-bottom:10px;">'
			  + '<b>Total:</b> '
			  + summary.total
			  + ' &nbsp; | &nbsp; '
			  + '<b>Berhasil:</b> '
			  + summary.success
			  + ' &nbsp; | &nbsp; '
			  + '<b>Gagal:</b> '
			  + summary.failed
			  + '</div>';


			if (successRows.length > 0) {

			  resultHtml +=
				'<div style="margin-top:10px;">'
				+ '<b>Berhasil</b>'
				+ '<ul style="margin-top:5px;">'
				+ successRows.join('')
				+ '</ul>'
				+ '</div>';
			}


			if (failedRows.length > 0) {

			  resultHtml +=
				'<div style="margin-top:10px;">'
				+ '<b>Gagal</b>'
				+ '<ul style="margin-top:5px;">'
				+ failedRows.join('')
				+ '</ul>'
				+ '</div>';
			}


			if (warningRows.length > 0) {

			  resultHtml +=
				'<div style="margin-top:10px;">'
				+ '<b>Warning</b>'
				+ '<ul style="margin-top:5px;">'
				+ warningRows.join('')
				+ '</ul>'
				+ '</div>';
			}


			resultHtml += '</div>';


			/*
			 * =====================================================
			 * SEMUA SUKSES
			 * =====================================================
			 */
			if (parseInt(summary.failed, 10) === 0) {

			  $("#dialog_shipment_request")
				.dialog('close');


			  Swal.fire({
				icon: 'success',
				title: 'Booking berhasil diproses',
				html: resultHtml,
				width: 650
			  });

			  return;
			}


			/*
			 * =====================================================
			 * PARTIAL SUCCESS
			 *
			 * Dialog source tidak ditutup agar user bisa lihat
			 * booking yang masih gagal.
			 * =====================================================
			 */
			if (
			  parseInt(summary.success, 10) > 0
			  && parseInt(summary.failed, 10) > 0
			) {

			  Swal.fire({
				icon: 'warning',
				title: 'Sebagian booking gagal',
				html: resultHtml,
				width: 650
			  });

			  return;
			}


			/*
			 * =====================================================
			 * SEMUA GAGAL
			 * =====================================================
			 */
			Swal.fire({
			  icon: 'error',
			  title: 'Booking gagal diproses',
			  html: resultHtml,
			  width: 650
			});
		  },


		  /*
		   * =======================================================
		   * HTTP / NETWORK ERROR
		   * =======================================================
		   */
		  error: function (xhr) {

			var message =
			  'Terjadi kesalahan komunikasi dengan server.';


			if (
			  xhr.responseJSON
			  && xhr.responseJSON.message
			) {

			  message = xhr.responseJSON.message;

			} else if (xhr.responseText) {

			  try {

				var parsed = JSON.parse(
				  xhr.responseText
				);

				if (parsed.message) {
				  message = parsed.message;
				}

			  } catch (e) {

				/*
				 * Jangan tampilkan raw PHP/SQL error ke user.
				 * Detail sudah masuk server log.
				 */
			  }
			}


			Swal.fire({
			  icon: 'error',
			  title: 'Gagal memproses booking',
			  text: message
			});
		  },


		  /*
		   * =======================================================
		   * RELEASE DOUBLE CLICK LOCK
		   * =======================================================
		   */
		  complete: function () {

			window.shipmentRequestBatchBusy = false;
		  }
		});
	  }
	});
	
	// TAMBAHKAN FILTER TOOLBAR DI SINI
  grid.jqGrid('filterToolbar', { 
    stringResult: true, 
    searchOnEnter: false, 
    defaultSearch: "cn" // cn = contain
  });
}
</script>