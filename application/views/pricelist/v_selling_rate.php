<style>
	#table tr.row-expiring-soon > td {
		background-color: #fdecea !important;
	}
	#table tr.row-expiring-soon:hover > td {
		background-color: #fbd5d1 !important;
	}
	.selling-legend {
		display: flex;
		align-items: center;
		gap: 6px;
		font-size: 12px;
		color: #555;
	}
	.selling-legend .swatch {
		display: inline-block;
		width: 14px;
		height: 14px;
		background-color: #fdecea;
		border: 1px solid #e74c3c;
		border-radius: 2px;
	}
</style>
<div id="form-main-page" class="main-page">
	<center>
		<table id="table" ></table>
		<div id="pager"></div>
		<div class="selling-legend" style="width:750px; margin-top:6px;">
			<span class="swatch"></span>
			<span>Baris berwarna merah = harga masih valid tapi akan expired dalam ≤ 7 hari. Segera update dengan harga terbaru.</span>
		</div>
	</center>
</div>
<div id="Form-Destination"></div>
<div id="PRINT"></div>

<script type="text/javascript">
$(document).ready(function ()
{
	var grid = $("#table"), i;
	grid.jqGrid(
	{
		url:"<?php echo base_url('pricelist/selling_rate/get_selling_rate')?>",
		mtype : "post",
		datatype: "json",
		// TODO (server): endpoint get_selling_rate() WAJIB memfilter customer yang
		// hanya punya data selling (a_selling) dengan valid >= tanggal hari ini,
		// misal via INNER JOIN ke a_selling ON a_selling.kode_shipper = shipper_name
		// AND a_selling.valid >= CURDATE(). Flag di bawah dikirim agar server tahu
		// request ini butuh hasil yang sudah difilter "hanya yang valid".
		postData: {
			only_valid_selling: 1
		},
		colNames:
		[
			'Customer Code','Customer Name','Sales','Valid Terdekat'
		],
    	colModel:
		[
			{name:'shipper_id',index:'shipper_id', align:"left", width:170, editable: true, hidden:true,
				editoptions: { size: 40, dataInit: function (el) { $(el).css('text-transform', 'uppercase'); }},
				stype:"text",
				searchoptions:{
					sopt:['cn','eq'],
				}
			},
			{name:'shipper_name',index:'shipper_name', align:"left", width:330, editable: true,
				editoptions: { size: 40, dataInit: function (el) { $(el).css('text-transform', 'uppercase'); }},
				stype:"text",
				searchoptions:{
					sopt:['cn','eq'],
				}
			},
			{
				name: 'sales_names',
				index: 'sales_names',
				align: "center",
				width: 110,
				editable: false,
				hidden: false,
				formatter: function(el) {
					// "Administrator" adalah nilai default sistem, bukan nama sales sungguhan
					if (!el || el === 'Administrator') {
						return "";
					}
					return el;
				}
			},
			{
				// Kolom opsional: tanggal valid terdekat milik customer ini.
				// Jika field 'valid_terdekat' belum dikirim dari server, kolom akan kosong
				// tanpa error (graceful fallback) sampai backend menambahkannya.
				// Highlight visual sekarang di level BARIS (lihat rowattr di bawah),
				// jadi kolom ini cukup tampilkan tanggalnya polos saja.
				name: 'valid_terdekat',
				index: 'valid_terdekat',
				align: "center",
				width: 110,
				editable: false,
				formatter: function(cellvalue) {
					if (!cellvalue) return "";
					return cellvalue.substring(6,8)+'-'+cellvalue.substring(4,6)+'-'+cellvalue.substring(0,4);
				}
			},
		],
		sortname: "shipper_name",
		sortorder: "asc",
		// Warnai seluruh baris kalau valid_terdekat sudah/mau expired (≤ 7 hari).
		rowattr: function (rowData) {
			if (!rowData.valid_terdekat) return {};
			var today = new Date();
			var validDate = new Date(
				rowData.valid_terdekat.substring(0,4),
				rowData.valid_terdekat.substring(4,6)-1,
				rowData.valid_terdekat.substring(6,8)
			);
			var diffDays = Math.ceil((validDate - today) / (1000*60*60*24));
			if (diffDays <= 7) {
				return { "class": "row-expiring-soon" };
			}
			return {};
		},
		shrinkToFit:false,
  		multiselect:true,
		loadonce : false,
		cellEdit: false,
		rowNum:16,
		width: 750,
		height: 475,
		rowList:[16,32,48],
		viewrecords: true,
		rownumbers: true,
		gridview: true,
		modal: true,
		caption: 'Selling Rate Customer — hanya menampilkan customer dengan minimal 1 data selling rate yang masih valid.',
		cellsubmit:'clientArray',
		pager:'#pager',

		loadComplete: function() {
			$("#cb_"+grid[0].id).hide();
			$(".ui-jqgrid-titlebar-close",$("#table")[0].grid.cDiv).hide();
    	},

    	beforeSelectRow: function(rowid, e) {
	        var cbsdis = $("tr#"+rowid+".jqgrow > td > input.cbox:disabled", grid[0]);
	        return cbsdis.length === 0;
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

		ondblClickRow: function(rowid, e){
			show_table_selling_rate(rowid);
		}

	}).navGrid('#pager', { edit: false, add: false, del: false, search: true, refresh: true });

	// Tombol "+" custom: buka pencarian shipper (termasuk yang BELUM punya
	// data selling valid, makanya belum tampil di list ini), lalu langsung
	// buka modal "SELLING PRICE TO" yang sama seperti dobel klik baris biasa.
	grid.navButtonAdd('#pager', {
		caption: "",
		buttonicon: "ui-icon-plus",
		title: "Buat Selling Rate untuk Customer Baru",
		onClickButton: function () {
			open_new_customer_search();
		}
	});
});

function open_new_customer_search()
{
	var html = '<div style="padding:6px 10px;">'
		+ '<label for="newCustomerSearch">Cari nama customer:</label><br/>'
		+ '<input type="text" id="newCustomerSearch" style="width:100%; margin-top:6px;" placeholder="Ketik min. 2 huruf..." />'
		+ '</div>';
	$("#Form-Destination").html(html);
	$("#Form-Destination").dialog({
		autoOpen: true,
		resizable: false,
		height: 160,
		width: 420,
		modal: true,
		title: "Customer Baru",
		close: function () { $(this).html(''); }
	});

	$("#newCustomerSearch").autocomplete({
		minLength: 2,
		source: "<?php echo base_url('pricelist/selling_rate/dropdown_shipper')?>",
		select: function (event, ui) {
			$("#Form-Destination").dialog('close');
			show_table_selling_rate_new(ui.item.shipper_id, ui.item.value);
			return false;
		}
	}).focus();
}

// Buka modal "SELLING PRICE TO" untuk shipper yang dipilih dari pencarian
// customer baru (bukan dari baris grid, karena shipper ini belum tampil
// di list -- belum punya data selling valid sama sekali).
function show_table_selling_rate_new(shipper_id, shipper_name)
{
	$( "#Form-Destination" ).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
	$( "#Form-Destination" ).dialog(
	{
		autoOpen: true,
		resizable: false,
		height: 575,
		width: 1080,
		modal: true,
		title: "SELLING PRICE TO : "+shipper_name,
		close: function()  {$(this).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>')}
	});

	$.ajax({
		type: "POST",
		url : "<?php echo site_url('pricelist/selling_rate/open_form_selling')?>",
		data: { id: '', shipper_id : shipper_id, shipper_name : shipper_name },
		success:function(XMLHttpRequest)
		{
			$("#Form-Destination").html(XMLHttpRequest)
		}
	})
}

function show_table_selling_rate()
{
	var N = arguments[0];
	var shipper_id = $('#table').jqGrid('getCell', N,'shipper_id');
	var shipper_name = $('#table').jqGrid('getCell', N,'shipper_name');
	$( "#Form-Destination" ).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
	$( "#Form-Destination" ).dialog(
	{
		autoOpen: true,
		resizable: false,
		height: 575,
		width: 1080,
		modal: true,
		title: "SELLING PRICE TO : "+shipper_name,
		close: function()  {$(this).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>')}
	});

	$.ajax({
		type: "POST",
		url : "<?php echo site_url('pricelist/selling_rate/open_form_selling')?>",
		data: { id: N, shipper_id : shipper_id, shipper_name : shipper_name },
		success:function(XMLHttpRequest)
		{
			$("#Form-Destination").html(XMLHttpRequest)
		}
	})
}
</script>