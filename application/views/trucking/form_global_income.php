<style type="text/css">
.grid-container {
  display: grid;
  grid-template-columns: auto auto auto auto auto auto;
  grid-gap: 10px;
  background-color: #2196F3;
  padding: 10px;
  align-items: center;
  border-radius: 5px;
}
#button-post
	{
	box-shadow:inset 0px 1px 0px 0px #bbdaf7;
	background:linear-gradient(to bottom, #79bbff 5%, #378de5 100%);
	background-color:#79bbff;
	border-radius:6px;
	border:1px solid #84bbf3;
	display:inline-block;
	cursor:pointer;
	color:#ffffff;
	font-family:Arial;
	font-size:11px;
	padding:6px 24px;
	text-decoration:none;
	text-shadow:0px 1px 0px #528ecc;
	}
#button a{
	float: right;
	margin:0 3px 0 0;
	border-radius: 4px 4px 4px 4px;
   border: 1px solid #D3D3D3;
   color: #555555;
   font-weight: normal;
	font-size:11px;
	text-decoration:none;
	width: 40px;
	text-align: center;
	background-color: #eee;
	vertical-align: middle;
	}	
#button a:hover {
   border: 1px solid #999999;
   color: #212121;
   font-weight: normal;
   background-color: #ddd;
   -moz-box-shadow: 0 2px 3px rgba(0, 0, 0, 0.3);
	-webkit-box-shadow: 0 2px 3px rgba(0, 0, 0, 0.3);
	box-shadow: 0 2px 3px rgba(0, 0, 0, 0.3);
 }
.load{
	font-weight: bold;
 	font-size: 11px;
 	font-style: italic;
}
</style>

<div id="main-page" style="font-size: 11px;">
<div class="grid-container">
	<div>JOB NUMBER</div>
	<div>
		<input type="text" value="<?php echo $job_no; ?>" id="job_no" disabled="disabled" style="height:30px; font-weight: bold; padding-left: 5px;">
	</div>
	<div>
		Last Posted by/at : <input type="text" id="customer" disabled="disabled" size="50" value="<?php echo $created ?>">
	</div>
	<div>
		<input type="button" id ="button-post" value=" POST " onclick="get_proses_post()">
	</div>
</div>
	<div style="clear: both; height: 10px;"></div>
	
	<div style="float: left; width: 735px">
		<table id="table-global-income"></table>
		<div id="page-global-income"></div>
	</div>
	
	<div style="float: left; width: 80px; margin-left: 5px;">
		<input type="button" value=" NEW " style="width: 90px; height: 30px; margin-bottom: 15px; margin-left:5px;" id="btnNewData">
		<input type="button" value=" EDIT " style="width: 90px; height: 30px; margin-bottom: 15px; margin-left:5px;" id="btnEditData">
		<input type="button" value="REFRESH" style="width: 90px; height: 30px; margin-bottom: 15px; margin-left:5px;" id="btnRefresh" onclick="refresh_data()">
		<input type="button" value="DELETE" style="width: 90px; height: 30px; margin-bottom: 15px; margin-left:5px;" id="btnDeleteData">
		<input type="button" value=" EXIT " style="width: 90px; height: 30px; margin-bottom: 15px; margin-left:5px;" id="btnExitData" onclick="exit_data()">
	</div>	
</div>

<script type="text/javascript">
var base_url = "<?php echo base_url(); ?>";
$(document).ready(function () {
	table_global();
	enabled_new();
})

function enabled_edit(rowid){
	$( '#btnEditData' ).attr('disabled', false).attr('onClick', 'open_form_editor(\'edit\','+rowid+')');
	$( '#btnDeleteData' ).attr('disabled', false).attr('onClick', 'delete_data('+rowid+')');
	$( '#btnNewData' ).attr('disabled', true);
//	$( '#btnExitData' ).attr('disabled', true);
}

function enabled_new(){
	$( '#btnEditData' ).attr('disabled', true);
	$( '#btnDeleteData' ).attr('disabled', true);
	$( '#btnNewData' ).attr('disabled', false).attr('onClick', 'open_form_editor(\'add\',\'\')');
}

function exit_data(){
	$( "#Form-Global-Income" ).dialog( 'close' )
}

function open_form_editor(mode,rowid){
	$( "#Form-Input-GlbIncome" ).fadeIn('slow', function()
	{
		$(this).dialog({
			autoOpen: true,
			resizable: false,
			height: 330,
			width: 290,
			modal: true,
			title: mode.toUpperCase()+"DATA GLOBAL INCOME",
			close: function(){}
		})
	});

	$( "#loading-global-income" ).ajaxStart(function(){
		$(this).fadeIn();
		$(this).html('<img src="'+base_url+'/public/images/loader.gif"/>');
		}).ajaxStop(function(){
		$(this).fadeOut();
	});
	
	$.ajax({
		type: "POST", 
		url : base_url+'/afg_c_income/open_form_global_edit',
		data: 
		{ 
			id: rowid, 
			oper: mode, 
		},
		success:function(XMLHttpRequest)
		{
			$( "#Input-GlbIncome" ).html(XMLHttpRequest)
		//	$( "#dropdownContainer" ).html(XMLHttpRequest)
		//	$( "#dropdownService" ).html(XMLHttpRequest)
		}
	})
}

function close_form(){
	$( '#Form-Input-GlbIncome' ).fadeOut('slow', function() 
	{
		$(this).dialog('close');
	})
}

function clear_data(){
	$('#codeService').val('');
	$('#ItemService').val('');
	$('#Quantity').val('');
	$('#dropdownContainer').val('');
	$('#Currency').val('');
	$('#Price_Unit').val('');
	$('#Price').val('');
	$('#Prepaid_Collect').val('');
	$('#Payeble').val('');
	$('#Group').val('');
}

function delete_data(rowid){
	$( "#confirm" ).html('Are You sure Delete this data ?')
	$( "#Form-delete-GlbIncome" ).dialog(
	{
		autoOpen: true,
		resizable: false,
		height: 100,
		width: 240,
		modal: true,
		title: "Delete Confirm",
		buttons:
		{
			"Delete":function()
			{
				$(this).dialog('close');
				delete_row(rowid);
			},
			"Cancel":function()
			{
				$(this).dialog('close');
			}
		},
		close: function()  {	 }
	});
}

function delete_row(input){
	$.ajax({
		type: "POST", 
		url : base_url+'/afg_c_income/manage_global',
		data: { id:input, oper:'del' },
		success:function(XMLHttpRequest)
		{
			var data = {};
			try { data = $.parseJSON(XMLHttpRequest); } catch(e) {}
			if (data.status == 0) {
				Swal.fire('Tidak dapat dihapus', data.error || data.message || 'Income sudah memiliki pembayaran', 'error');
			}
			$("#table-global-income").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
		}
	})
}

function save_data(input){
	
	var job_no 			= $('#job_no').val();
	var code_service 	= $('#codeService').val();
	var item_service 	= $('#ItemService').val();
	var qty 				= $('#Quantity').val().replace(/\ /g,'');
	var container 		= $('#dropdownContainer').val();
	var currency 		= $('#Currency').val();
	var unit_price 		= $('#Price_Unit').val().replace(/\ /g,'');
	var prep_coll 		= $('#Prepaid_Collect').val();
	var payeble_to 		= $('select[id="Payable"]').val();
	var group 			= $('#Group').val();
	var id 				= $('#idNya').val();
	
	if(code_service=='0' || qty=='' || unit_price==''){
		show_messages('Please complite request data');
		return false;
	}
	
	var dataGlobal = 
	{
		job_no			:	job_no,
		code_service	:	code_service,
		item_service	:	item_service,
		qty 			:	qty,
		container		:	container,
		currency		:	currency,
		unit_price		:	unit_price,
		prep_coll		:	prep_coll,
		payeble_to		:	payeble_to,
		group 			:	group,	
		oper			: 	input,
		id				: 	id,
	}
	
	
	$.ajax({
		type: "POST", 
		url : base_url+'/afg_c_income/manage_global',
		data: dataGlobal,
		success:function(XMLHttpRequest)
		{
			var data = {};
			try { data = $.parseJSON(XMLHttpRequest); } catch(e) {}
			if (data.status == 0) {
				Swal.fire('Tidak dapat disimpan', data.error || data.message || 'Perubahan ditolak', 'error');
				$("#table-global-income").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
				return;
			}
			$('#Form-Input-GlbIncome').dialog('close');
			$("#table-global-income").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
			if (data.auto_unbilled) Swal.fire('Income direvisi', 'Item otomatis dikembalikan ke UNBILLED', 'info');
		}
	})
}

function refresh_data(){
	$("#table-global-income").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
}

function table_global(){
	var grid = $("#table-global-income"), i;	
	grid.jqGrid({ 
		url 	: base_url+'/afg_c_income/get_global_income',
		mtype : "post",
		datatype: "json",
		postData: { job_no: $( '#job_no' ).val() },
		sortname: "id",
		sortorder: "asc",
		colNames:
		[
			'',
			'Code#',
			'Item Service',
			'Quantity',
			'Container',
			'Currency', 
			'Price/Unit', 
			'Price',
			'Prep/Coll',
			'Receive From', 
			'Group', 
		],
    	colModel:
		[
			{name:'job_no',index:'job_no', align:"left", width:10, editable: true, hidden: true,
				editoptions:{size:"30"}
			},
			{name:'code_service',index:'code_service', align:"left", width:100, editable: true, 
				edittype:"select",
				editoptions:{dataUrl : base_url+'/afg_c_dropdown/dropdownService'}
			},
			{name:'item_service',index:'item_service', align:"left", width:250, editable: true, 
				editoptions:{size:"30"}
			},
			{name:'qty',index:'qty', align:"center", width:70, editable: true, 
				formatter: 'number',
    			formatoptions: { decimalSeparator: ".", thousandsSeparator: " ", decimalPlaces: 4, defaultValue: '' }
			},
			{name:'container',index:'container', align:"center", width:70, editable: true, 
				edittype:"select",
				editoptions:{dataUrl : base_url+'/afg_c_dropdown/dropdownContainer'},
				formatter: function(el,em,ek){
					if (el==0) {
						return " "
					} else {
						return el;
					}
				},
			},
			{name:'currency',index:'currency', align:"center", width:70, editable: true, 
				edittype:"select",formatter:'select', editoptions:{value:"USD:USD;RP.:RP."}
			},
			{name:'unit_price',index:'unit_price', align:"right", width:70, editable: true, 
				formatter: 'number',
    			formatoptions: { decimalSeparator: ".", thousandsSeparator: " ", decimalPlaces: 2, defaultValue: '' }
			},
			{name:'price',index:'price', align:"right", width:70, editable: true, 
			//	formatter: function(el,em,ek){
			//		return ek[3] * ek[6];
			//	},
				formatter: 'number',
    			formatoptions: { decimalSeparator: ".", thousandsSeparator: " ", decimalPlaces: 2, defaultValue: '' }
			},
			{name:'prep_coll',index:'prep_coll', align:"center", width:70, editable: true, 
				 edittype:"select",formatter:'select', editoptions:{value:" : ;COLLECT:COLLECT;PREPAID:PREPAID"}
			},
			{name:'payeble_to',index:'payeble_to', align:"left", width:100, editable: true, 
				editoptions:{size:"30"}
			},
			{name:'group',index:'group', align:"center", width:50, editable: true, 
				editoptions:{
					size:"30",
					dataInit: function (el) 
					{ 
						$(el).css('text-transform','uppercase'); 
					}
				},
				formatter: function(el,em,ek){
					if(el==null){
						return ""
					} else {
						return el.toUpperCase()
					}
				}
			},,
		],
		shrinkToFit: false, 
		loadonce:false,
  		viewrecords: true,
      multiselect:true, 
		modal: true,
		rownumbers: true,
		rowNum: 7,
		top: 20,
		width: 730,
		height: 200,
		rowList:[7,14,21],
		gridview: true,
		editurl : base_url+'/afg_c_income/manage_income',
		cellsubmit:'clientArray',
		pager:'#page-global-income',
				
		loadComplete: function(data) {
			$("#cb_"+grid[0].id).hide();
			$(".ui-jqgrid-titlebar-close",$("#table-global-income")[0].grid.cDiv).hide();
			enabled_new()
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
    			enabled_edit(rowid); 			
     		}
        	else
        	{
        		var cbs = $("tr.jqgrow > td > input.cbox:disabled", grid[0]);  		
    			cbs.attr("disabled", false);
    			enabled_new();       			
        	}
		},
		
	}).navGrid('#page-global-income',{ edit: false, add: false, del: false, refresh: false, <?= 'search: false' ?>});
}

function show_msg(char){
	$('#msg-global-income').html(char);
   $('#msg-global-income').fadeIn('slow').delay(2000).fadeOut('slow');
}
function _globalIncomePostJakarta(job_no) {
    $.ajax({
        type: 'POST',
        url: "<?php echo site_url('finance/c_post_data_acct_per_job/proses_post_global_income')?>",
        data: {
            region: 'Jakarta',
            kode_ref: job_no
        },
        beforeSend: function() {
            $('#button-post').prop('disabled', true).val('POSTING...');
        },
        success: function() {
            Swal.fire('Berhasil', 'Global Income Jakarta berhasil diposting.', 'success');
        },
        error: function(xhr) {
            var msg = 'Error, no connection to server!';
            try {
                var data = JSON.parse(xhr.responseText);
                msg = data.message || msg;
            } catch(e) {}
            Swal.fire('Gagal', msg, 'error');
        },
        complete: function() {
            $('#button-post').prop('disabled', false).val(' POST ');
        }
    });
}

function _globalIncomePostNonJakartaRevenueOrder(job_no) {
    $.ajax({
        type: 'POST',
        url: base_url + '/afg_c_income/post_global_revenue_order',
        dataType: 'json',
        data: { job_no: job_no },
        beforeSend: function() {
            $('#button-post').prop('disabled', true).val('POSTING...');
        },
        success: function(res) {
            if (!res || !res.status) {
                Swal.fire('Gagal', (res && (res.error || res.message)) || 'Global Revenue Order gagal diposting', 'error');
                return;
            }
            var ro = res.data || {};
            Swal.fire('Berhasil', 'Global Revenue Order tersimpan' + (ro.order_no ? ' - ' + ro.order_no : ''), 'success');
        },
        error: function(xhr) {
            var msg = 'Gagal terhubung ke ERP';
            try {
                var d = JSON.parse(xhr.responseText);
                msg = d.error || d.message || msg;
            } catch(e) {}
            Swal.fire('Gagal', msg, 'error');
        },
        complete: function() {
            $('#button-post').prop('disabled', false).val(' POST ');
        }
    });
}

function get_proses_post() {
    var job_no = $('#job_no').val();
    var region_id = $.trim(<?php echo json_encode((string)$this->session->userdata('region_id')); ?>);

    if (!job_no) {
        Swal.fire('Validasi gagal', 'Job No tidak ditemukan.', 'warning');
        return;
    }

    if (!region_id) {
        Swal.fire('Validasi gagal', 'Region session tidak ditemukan.', 'warning');
        return;
    }

    // Jakarta TETAP memakai flow Global Income existing.
    if (String(region_id).toLowerCase() === 'jakarta') {
        _globalIncomePostJakarta(job_no);
        return;
    }

    // Semua region selain Jakarta memakai Revenue Order ERP baru.
    _globalIncomePostNonJakartaRevenueOrder(job_no);
}

</script>

