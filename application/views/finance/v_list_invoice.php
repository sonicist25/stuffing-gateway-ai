<div style="margin-top: 30px; margin-left: 30px;">
	<table id="table_inv"></table>
	<div id="page-inv" style="font-size: 11px;"></div>
</div>

<div id="form-excel-invoice"></div>
<div id="form-xml-invoice"></div>

<div style="margin-top: 30px; text-align: center;">
	<table style="margin: 0 auto;">
		<tr>
			<td height="30">Masukkan nomor awal faktur</td>
			<td style="padding: 0 10px;">:</td>
			<td>
				<?= form_input([
					'id'    => 'no-faktur',
					'name'  => 'no-faktur',
					'style' => 'width: 215px;'
				]) ?>
			</td>
		</tr>
	</table>
</div>

<script type="text/javascript">
var BASE_URL = <?= json_encode(rtrim(base_url(), '/')); ?>;
var CURRENT_USER_ID = <?= (int) $this->session->userdata('user_id'); ?>;

$(document).ready(function () {
	get_table_invoice();
});

/**
 * Mendapatkan nilai fin_proses.
 *
 * Mendukung response jqGrid berbentuk:
 * 1. Array/repeatitems = true.
 * 2. Object/repeatitems = false.
 */
function getFinProses(rawObject) {
	if ($.isArray(rawObject)) {
		return rawObject[12];
	}

	if (rawObject && typeof rawObject === 'object') {
		return rawObject.fin_proses;
	}

	return null;
}

/**
 * Memberikan class pada cell invoice yang sudah memiliki faktur pajak.
 */
function getFpCellAttribute(rowId, cellValue, rawObject) {
	if (String(getFinProses(rawObject)) === '9') {
		return 'class="Blue"';
	}

	return '';
}

/**
 * Formatter status pembayaran.
 */
function statusFormatter(cellValue) {
	var status = String(cellValue || '').toLowerCase();
	var iconUrl = '';

	if (status === 'paid') {
		iconUrl = BASE_URL + '/public/images/green-dot-icon.png';
	} else if (status === 'unpaid') {
		iconUrl = BASE_URL + '/public/images/blue-dot-icon.png';
	} else if (status === 'overdue') {
		iconUrl = BASE_URL + '/public/images/red-dot-icon.png';
	} else {
		return '';
	}

	return '<img src="' + iconUrl + '" alt="" style="margin-right:4px;">' + status;
}

/**
 * Formatter tarif PPN.
 */
function tarifPpnFormatter(cellValue) {
	var value = parseFloat(cellValue);

	if (isNaN(value) || value === 0) {
		return '-';
	}

	if (Math.abs(value - 0.011) < 0.000001) {
		return '1,1%';
	}

	if (Math.abs(value - 0.012) < 0.000001) {
		return '1,2%';
	}

	if (Math.abs(value - 0.110) < 0.000001) {
		return '11%';
	}

	return (value * 100)
		.toFixed(2)
		.replace('.', ',') + '%';
}

/**
 * Membuat filter server-side.
 *
 * Filter number contains custom dipertahankan seperti postData lama.
 *
 * mode:
 * - semua
 * - sudah
 * - belum
 */
function applyFpFilter(mode) {
	var grid = $('#table_inv');
	var postData;

	if (mode === 'sudah') {
		postData = {
			searchField: 'fin_proses',
			searchString: '9',
			searchOper: 'eq',
			filters: ''
		};
	} else if (mode === 'belum') {
		postData = {
			searchField: 'fin_proses',
			searchString: '9',
			searchOper: 'ne',
			filters: ''
		};
	} else {
		/*
		 * Kembalikan ke request awal seperti kode lama.
		 */
		postData = {
			searchField: 'number',
			searchString: 'custom',
			searchOper: 'cn',
			filters: ''
		};
	}

	grid.jqGrid('setGridParam', {
		search: mode !== 'semua',
		page: 1,
		postData: postData
	}).trigger('reloadGrid');
}


function get_table_invoice() {
	var grid = $('#table_inv');

	grid.jqGrid({
		url: BASE_URL + '/finance/c_list_invoice/get_table_inv',
		mtype: 'POST',
		datatype: 'json',

		postData: {
			searchField: 'number',
			searchString: 'custom',
			searchOper: 'cn'
		},

		sortname: 'date',
		sortorder: 'desc',

		colNames: [
			'No Invoice',
			'Group',
			'Tanggal',
			'Job Number',
			'Kepada',
			'Kepada Kode',
			'Bill To',
			'Posted',
			'Status',
			'Aging',
			'Curr',
			'Amount',
			'Download Faktur',
			'Nilai Pajak',
			'Tarif Pajak'
		],

		colModel: [
			{
				name: 'x_finance.number',
				index: 'x_finance.number',
				width: 90,
				align: 'left',
				cellattr: getFpCellAttribute,
				stype: 'text',
				searchoptions: {
					sopt: ['cn', 'eq']
				}
			},
			{
				name: 'group',
				index: 'group',
				width: 40,
				align: 'center',
				hidden: true,
				cellattr: getFpCellAttribute,
				stype: 'text',
				searchoptions: {
					sopt: ['cn', 'eq'],
					dataInit: function (element) {
						$(element).css({
							'text-align': 'center',
							'max-width': '40px',
							'text-transform': 'uppercase'
						});
					}
				}
			},
			{
				name: 'date',
				index: 'date',
				width: 80,
				align: 'center',
				cellattr: getFpCellAttribute,
				stype: 'text',
				searchoptions: {
					sopt: ['eq', 'lt', 'gt'],
					dataInit: function (element) {
						$(element).datepicker({
							dateFormat: 'yymmdd'
						});
					}
				}
			},
			{
				name: 'job_no',
				index: 'job_no',
				width: 120,
				align: 'left',
				cellattr: getFpCellAttribute,
				stype: 'text',
				searchoptions: {
					sopt: ['cn', 'eq']
				}
			},
			{
				name: 'kepada',
				index: 'kepada',
				width: 120,
				align: 'left',
				hidden: true,
				cellattr: getFpCellAttribute,
				stype: 'text',
				searchoptions: {
					sopt: ['cn', 'eq']
				}
			},
			{
				name: 'kepada_kode',
				index: 'kepada_kode',
				width: 150,
				align: 'left',
				hidden: true,
				cellattr: getFpCellAttribute,
				stype: 'text',
				searchoptions: {
					sopt: ['cn', 'eq']
				}
			},
			{
				name: 'shipper_name',
				index: 'shipper_name',
				width: 190,
				align: 'left',
				cellattr: getFpCellAttribute,
				stype: 'text',
				searchoptions: {
					sopt: ['cn', 'eq']
				}
			},
			{
				name: 'kurs',
				index: 'kurs',
				width: 50,
				align: 'left',
				cellattr: getFpCellAttribute,
				stype: 'text',
				searchoptions: {
					sopt: ['cn', 'eq']
				}
			},
			{
				name: 'status',
				index: 'status',
				width: 70,
				align: 'left',
				hidden: false,
				formatter: statusFormatter,
				cellattr: getFpCellAttribute,
				stype: 'text',
				searchoptions: {
					sopt: ['cn', 'eq']
				}
			},
			{
				name: 'aging',
				index: 'aging',
				width: 50,
				align: 'right',
				cellattr: getFpCellAttribute,
				stype: 'text',
				searchoptions: {
					sopt: ['cn', 'eq']
				}
			},
			{
				name: 'currency',
				index: 'currency',
				width: 45,
				align: 'left',
				hidden: false,
				cellattr: getFpCellAttribute,
				stype: 'text',
				searchoptions: {
					sopt: ['cn', 'eq']
				}
			},
			{
				name: 'grand_total',
				index: 'grand_total',
				width: 100,
				align: 'right',
				hidden: false,
				formatter: 'currency',
				formatoptions: {
					prefix: '',
					suffix: '',
					thousandsSeparator: ','
				},
				cellattr: getFpCellAttribute,
				stype: 'text',
				searchoptions: {
					sopt: ['cn', 'eq']
				}
			},
			{
				name: 'fin_proses',
				index: 'x_finance.fin_proses',
				width: 50,
				align: 'center',
				hidden: true,
				stype: 'select',
				searchoptions: {
					sopt: ['eq', 'ne'],
					value: ':All;9:Sudah Faktur Pajak;0:Belum Faktur Pajak'
				},
				cellattr: function (rowId, cellValue, rawObject) {
					if (String(getFinProses(rawObject)) === '9') {
						return 'class="yellow"';
					}

					return '';
				}
			},
			{
				name: 'nilai_ppn',
				index: 'nilai_ppn',
				width: 90,
				align: 'right',
				hidden: false,
				formatter: 'currency',
				formatoptions: {
					prefix: '',
					suffix: '',
					thousandsSeparator: ','
				},
				cellattr: getFpCellAttribute,
				stype: 'text',
				searchoptions: {
					sopt: ['cn', 'eq']
				}
			},
			{
				name: 'tarif_ppn',
				index: 'tarif_ppn',
				width: 60,
				align: 'right',
				hidden: false,
				formatter: tarifPpnFormatter,
				cellattr: getFpCellAttribute,
				stype: 'text',
				searchoptions: {
					sopt: ['cn', 'eq']
				}
			}
		],

		shrinkToFit: false,
		loadonce: false,
		viewrecords: true,

		multiselect: true,
		multiPageSelection: true,
		cellEdit: false,
		modal: true,
		rownumbers: true,
		gridview: true,
		scroll: true,

		rowNum: 20,
		rowList: [20, 40, 60, 80, 100, 1000],

		width: 900,
		height: 505,

		caption: 'Tabel List Invoice',

		editurl: BASE_URL + '/finance/c_list_invoice/manage_data',
		pager: '#page-inv',

		loadComplete: function () {
			$('#cb_' + this.id).hide();

			if (this.grid && this.grid.cDiv) {
				$('.ui-jqgrid-titlebar-close', this.grid.cDiv).hide();
			}
		},

		beforeSelectRow: function (rowId) {
			var escapedRowId = $.jgrid.jqID(rowId);

			var disabledCheckbox = $(
				'tr#' + escapedRowId + '.jqgrow > td > input.cbox:disabled',
				this
			);

			return disabledCheckbox.length === 0;
		},

		onSelectRow: function (rowId) {
			var gridId = $.jgrid.jqID(this.id);
			var postedStatus = $(this).jqGrid(
				'getCell',
				rowId,
				'kurs'
			);

			if (
				postedStatus === 'Posted' &&
				CURRENT_USER_ID !== 0 &&
				CURRENT_USER_ID !== 107
			) {
				$('#edit_' + gridId).addClass('ui-state-disabled');
				$('#del_' + gridId).addClass('ui-state-disabled');
			} else {
				$('#edit_' + gridId).removeClass('ui-state-disabled');
				$('#del_' + gridId).removeClass('ui-state-disabled');
			}
		}
	});

	grid.jqGrid(
		'navGrid',
		'#page-inv',
		{
			edit: false,
			add: false,
			del: true,
			search: true,
			refresh: true,
			deltext: ''
		},
		{},
		{},
		{},
		{
			modal: true,
			height: 'auto',
			width: 380,
			closeAfterSearch: true,
			caption: 'Find Invoice ...'
		},
		{}
	);

	/*
	 * Download CSV.
	 */
	grid.jqGrid('navButtonAdd', '#page-inv', {
		caption: '',
		buttonicon: 'ui-icon-circle-arrow-s',
		onClickButton: download_csv,
		position: 'last',
		title: 'Download CSV',
		cursor: 'pointer'
	});

	/*
	 * Download XML.
	 */
	grid.jqGrid('navButtonAdd', '#page-inv', {
		caption: '',
		buttonicon: 'ui-icon-script',
		onClickButton: download_xml,
		position: 'last',
		title: 'Download XML',
		cursor: 'pointer'
	});

	/*
	 * Tampilkan semua invoice custom,
	 * tanpa filter fin_proses.
	 */
	grid.jqGrid('navButtonAdd', '#page-inv', {
		caption: '',
		buttonicon: 'ui-icon-refresh',
		onClickButton: function () {
			applyFpFilter('semua');
		},
		position: 'last',
		title: 'Tampilkan semua status Faktur Pajak',
		cursor: 'pointer'
	});

	/*
	 * Filter invoice yang sudah memiliki Faktur Pajak.
	 */
	grid.jqGrid('navButtonAdd', '#page-inv', {
		caption: 'Sudah FP',
		buttonicon: 'ui-icon-check',
		onClickButton: function () {
			applyFpFilter('sudah');
		},
		position: 'last',
		title: 'Tampilkan invoice yang sudah Faktur Pajak',
		cursor: 'pointer'
	});

	/*
	 * Filter invoice yang belum memiliki Faktur Pajak.
	 */
	grid.jqGrid('navButtonAdd', '#page-inv', {
		caption: 'Belum FP',
		buttonicon: 'ui-icon-close',
		onClickButton: function () {
			applyFpFilter('belum');
		},
		position: 'last',
		title: 'Tampilkan invoice yang belum Faktur Pajak',
		cursor: 'pointer'
	});

	/*
	 * Tidak ada pemanggilan:
	 *
	 * grid.jqGrid('filterToolbar', ...);
	 * grid[0].triggerToolbar();
	 */
}

/**
 * Mengambil row ID invoice yang dipilih.
 */
function getSelectedInvoiceRows() {
	return $('#table_inv').jqGrid(
		'getGridParam',
		'selarrrow'
	) || [];
}

/**
 * Mengambil nomor invoice berdasarkan row yang dipilih.
 */
function getSelectedInvoiceNumbers(rowIds) {
	var invoiceNumbers = [];

	$.each(rowIds, function (index, rowId) {
		var invoiceNumber = $('#table_inv').jqGrid(
			'getCell',
			rowId,
			'x_finance.number'
		);

		invoiceNumbers.push(invoiceNumber);
	});

	return invoiceNumbers;
}

function download_csv() {
	var noFaktur = $.trim($('#no-faktur').val());
	var rowIds = getSelectedInvoiceRows();

	if (noFaktur === '') {
		Swal.fire({
			icon: 'error',
			title: 'Oops...',
			text: 'No Faktur Awal harus diisi!',
			footer: '<a href="">Cek di e-Faktur?</a>'
		});

		return;
	}

	if (rowIds.length === 0) {
		Swal.fire({
			icon: 'warning',
			title: 'Invoice belum dipilih',
			text: 'Pilih minimal satu invoice terlebih dahulu.'
		});

		return;
	}

	var encodedRowIds = encodeURIComponent(rowIds.join(','));

	var url = BASE_URL +
		'/finance/c_list_invoice/download_csv/' +
		encodeURIComponent(noFaktur) +
		'/' +
		encodedRowIds;

	$('#form-excel-invoice').html(
		'<div style="margin-bottom:10px;">' +
			'<div style="text-align:center;">' +
				'<iframe ' +
					'id="frame_invoice" ' +
					'src="' + url + '" ' +
					'width="310" ' +
					'height="60" ' +
					'frameborder="0">' +
				'</iframe>' +
			'</div>' +
		'</div>'
	);
}

function download_xml() {
	var rowIds = getSelectedInvoiceRows();

	if (rowIds.length === 0) {
		Swal.fire({
			icon: 'warning',
			title: 'Invoice belum dipilih',
			text: 'Pilih minimal satu invoice terlebih dahulu.'
		});

		return;
	}

	/*
	 * Controller lama menerima ID row.
	 * Jangan masukkan nomor invoice ke URL karena dapat mengandung "/".
	 */
	var encodedRowIds = encodeURIComponent(rowIds.join(','));

	var url = BASE_URL +
		'/finance/c_list_invoice/generate_xml/' +
		encodedRowIds +
		'/' +
		encodedRowIds;

	console.log('Generate XML URL:', url);

	$('#form-xml-invoice').html(
		'<div style="margin-bottom:10px;">' +
			'<div style="text-align:center;">' +
				'<iframe ' +
					'id="frame_xml" ' +
					'src="' + url + '" ' +
					'width="310" ' +
					'height="60" ' +
					'frameborder="0">' +
				'</iframe>' +
			'</div>' +
		'</div>'
	);
}
</script>