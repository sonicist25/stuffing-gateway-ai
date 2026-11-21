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
		<input type="text" value="<?php echo $job_no; ?>" id="job_no" disabled="disabled">
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
		<table id="table-global-cost"></table>
		<div id="page-global-cost"></div>
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
	$( "#Form-Global-Cost" ).dialog( 'close' )
}

function open_form_editor(mode,rowid){
	$( "#Form-Input-GlbCost" ).fadeIn('slow', function()
	{
		$(this).dialog({
			autoOpen: true,
			resizable: false,
			height: 330,
			width: 290,
			modal: true,
			title: mode.toUpperCase()+" DATA GLOBAL COST",
			close: function(){}
		})
	});

	$( "#loading-global-cost" ).ajaxStart(function(){
		$(this).fadeIn();
		$(this).html('<img src="'+base_url+'/public/images/loader.gif"/>');
		}).ajaxStop(function(){
		$(this).fadeOut();
	});
	
	$.ajax({
		type: "POST", 
		url : base_url+'/afg_c_cost/open_form_global_edit',
		data: 
		{ 
			id: rowid, 
			oper: mode, 
		},
		success:function(XMLHttpRequest)
		{
			$( "#Input-GlbCost" ).html(XMLHttpRequest)
		//	$( "#dropdownContainer" ).html(XMLHttpRequest)
		//	$( "#dropdownService" ).html(XMLHttpRequest)
		}
	})
}

function close_form(){
	$( '#Form-Input-GlbCost' ).fadeOut('slow', function() 
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
	var grid = $("#table-global-cost"), i;		
	var update 	= grid.jqGrid('getCell',rowid,'updated_at');
	var create 	= grid.jqGrid('getCell',rowid,'created_at');
	var payable = grid.jqGrid('getCell',rowid,'payable_to');
	var post = $('#customer').val();
	var user = <?= $this->session->userdata('user_id');?>;
	if (user == 0 || user == 160 || user == 110 || user == 107 || user == 109){
		$( "#confirm" ).html('Are You sure Delete this data ?')
	$( "#Form-delete-GlbCost" ).dialog(
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
	}else{
	if (update < post || create < post) {
		Swal.fire ({
			icon: 'error',
			title: 'Hapus data gagal',
			text: 'Item kemungkinan sudah dibayar',
			footer: '<a href="">Hubungi Accounting untuk menghapus data</a>'
			});
	} else {
	$( "#confirm" ).html('Are You sure Delete this data ?')
	$( "#Form-delete-GlbCost" ).dialog(
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
	}
}

function delete_row(input){
	$.ajax({
		type: "POST", 
		url : base_url+'/afg_c_cost/manage_global',
		data: { id:input, oper:'del' },
		success:function(XMLHttpRequest)
		{
			$("#table-global-cost").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
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
	var unit_price 	= $('#Price_Unit').val().replace(/\ /g,'');
//	var price 			= $('#PriceGlobal	').val().replace(/\ /g,'');
	var prep_coll 		= $('#Prepaid_Collect').val();
	var payeble_to 	= $('#Payeble').val();
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
		qty 				:	qty,
		container		:	container,
		currency			:	currency,
		unit_price		:	unit_price,
//		price				:	price,
		prep_coll		:	prep_coll,
		payeble_to		:	payeble_to,
		group 			:	group,	
		oper				: 	input,
		id					: 	id,
	}
	
	//alert(dataGlobal['payeble_to']);
	//return false;
	
	$.ajax({
		type: "POST", 
		url : base_url+'/afg_c_cost/manage_global',
		data: dataGlobal,
		success:function(XMLHttpRequest)
		{
			$('#Form-Input-GlbCost').dialog('close');
			$("#table-global-cost").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
		}
	})
}

function refresh_data(){
	$("#table-global-cost").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
}

function table_global(){
	var grid = $("#table-global-cost"), i;	
	grid.jqGrid({ 
		url 	: base_url+'/afg_c_cost/get_global_cost',
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
			'Payeble to', 
			'Group', 
			'Created By',
			'Created At',
			'Updated By',
			'Updated At',
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
				  editoptions: {
					dataInit: function (elem) {
					myAutocomplete(elem, "afg_c_dropdown/dropdownPayable");
					},
				  }
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
			},
			{name:'created_by',index:'created_by', width:200, hidden: false,},
  			{name:'created_at',index:'update_at', width:200, hidden: false,},
  			{name:'updated_by',index:'updated_by', width:200, hidden: false,},
  			{name:'updated_at',index:'updated_at', width:200, hidden: false,},
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
		pager:'#page-global-cost',
				
		loadComplete: function(data) {
			$("#cb_"+grid[0].id).hide();
			$(".ui-jqgrid-titlebar-close",$("#table-global-cost")[0].grid.cDiv).hide();
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
		
	}).navGrid('#page-global-cost',{ edit: false, add: false, del: false, refresh: false, <?= 'search: false' ?>});
}

function show_msg(char){
	$('#msg-global-cost').html(char);
   $('#msg-global-cost').fadeIn('slow').delay(2000).fadeOut('slow');
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

        if (autocomplete.selectedItem) {
            autocomplete._trigger("select", event, {
                item: autocomplete.selectedItem
            });
        //there was no match, clear the input
        } else {
            $(this).val('');
        }
        });
}
    
function get_proses_post(){	
	var kode_ref = $('#job_no').val();
	var region_id = '<?php echo $this->session->userdata('region_id'); ?>';
	if (region_id == 'Jakarta'){
		$.ajax({
			type: "POST", 
			url : "<?php echo site_url('finance/c_post_data_acct_per_job/proses_post_global_cost')?>",
			data: { 
				region : 'Jakarta',
				kode_ref,
			},
			success:function(response)
			{
				Swal.fire("Berhasil")
			},
			error: function(response){
				var data = $.parseJSON(response.responseText);
				if (!!data) {
				Swal.fire(data.message)}else{
				Swal.fire("Error, no conection to server!")
				}
			}
		})
	 }else{
		Swal.fire("Hanya untuk Gateway Jakarta");
		}	
}

</script>

