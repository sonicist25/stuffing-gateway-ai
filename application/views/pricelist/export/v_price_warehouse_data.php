<style type="text/css">
.tab-border {
	border-top: 1px solid #999; 
	border-left: 1px solid #999; 
	border-bottom: 3px solid #999;
	border-right: 3px solid #999;
	background-color: #eee;
}
</style>

<div style="font-size: 11px; width: 710px">
		<div style="margin-top: 10px; margin-bottom: 15px;">
					<input type="button" value=" NEW " style="width: 60; height: 30" id="btnNew" onclick="new_data()">
					<input type="button" value=" EDIT " style="width: 60; height: 30" id="btnEdit" onclick="edit_data()">
					<input type="button" value=" PREV " style="width: 60; height: 30" id="btnPrev" onclick="get_data('prev')">
					<input type="button" value=" NEXT " style="width: 60; height: 30" id="btnNext" onclick="get_data('next')">
					<input type="button" value=" SAVE " style="width: 60; height: 30" id="btnSave" onclick="save_data()">
					<input type="button" value="DELETE" style="width: 60; height: 30" id="btnDelete">
					<input type="button" value="CETAK" style="width: 60; height: 30" id="btnCetak">	
					
					<input type="button" value="EXIT" style="width: 60; height: 30; margin-left: 20" id="btnExit" onclick="exit_data()">
		</div>
		
		<div class="tab-border" style="float: left; width: 320px; height: 70px;">
			<table style="margin: 10px; width: 300px">
				<tr>
					<td style="width: 30px">Ref No</td>
					<td style="width: 10px">:</td>
					<td style="width: 70px"><input type="text" id="ref_no" disabled="disabled" style="width: 98%"></td>
				</tr>
				<tr>
					<td style="width: 30px; padding-top: 5px">DATE</td>
					<td style="width: 10px; padding-top: 5px">:</td>
					<td style="width: 70px; padding-top: 5px"><input type="text" id="date" disabled="disabled" style="width: 98%"></td>
				</tr>
				<tr>
					<td style="width: 30px; padding-top: 5px"></td>
					<td style="width: 10px; padding-top: 5px"></td>
					<td style="width: 70px; padding-top: 5px">
						<input type="hidden" id="id" disabled="disabled" style="width: 30px">
						<input type="hidden" id="oper" disabled="disabled" style="width: 30px">
					</td>
				</tr>
			</table>
		</div>

		<div class="tab-border" style="float:right; width: 370px; height: 70px;">
			<table style="margin: 10px; width: 360px">
				<tr>
					<td>WAREHOUSE CO. INFORMATION </td>
					<td>:</td>
					<td><input type="text" style="float: right; font-weight: bold; width: 150px" value="<?php echo $code ?>" id="shipping_co" disabled="disabled"></td>
				</tr>
				<tr><td colspan="3" style="padding-top: 5px"><input type="text" style="font-weight: bold; width: 98%" value="<?php echo $name ?>" id="nameForm" disabled="disabled"></td></tr>
			</table>
		</div>
		
		<div style="clear: both; height: 10px"></div>
		
		<div >
			<table id="table-satu"></table>
			<div id="page-table"></div>
		</div>
		
</div>

<script type="text/javascript">

function new_data()
{
	$.ajax
	({
			type:"POST",
			url:"<?php echo base_url('pricelist/export_cost_price_warehouse/new_data') ?>",
			data:{ id : $('#id').val(), shipping_co: $('#shipping_co').val()},
			success:function(data)
			{
				var data = $.parseJSON(data);
				$( '#ref_no' ).val(data.ref_no);
			},
	})

	$( '#id' ).val('');
	$( '#date' ).val('');
	$( '#valid' ).val('');
	$( '#oper' ).val('add');
	
	$( '#btnDelete' ).attr('onClick','cancel("add")').val('CANCEL');
	$( '#table-satu-agent' ).jqGrid("clearGridData", true);
	enabled_all();
}

function get_data(input)
{
	$( '#loading-selling' ).ajaxStart(function(){
		$(this).fadeIn();
		$(this).html('<img src="<?php echo base_url("public/images/loader.gif")?>"/>');
		}).ajaxStop(function(){
		$(this).fadeOut();
	});
	
	$.ajax
	({
			type:"POST",
			url:"<?php echo base_url('pricelist/export_cost_price_warehouse/get_data') ?>",
			data:{ id : $( '#id' ).val(), oper : input, shipping_co: $( '#shipping_co' ).val()},
			success:function(data)
			{
				var data = $.parseJSON(data);
				$( '#id' ).val(data.id);
				$( '#ref_no' ).val(data.ref_no);
				$( '#date' ).val(data.date);
				$( '#shipping_co' ).val(data.shipping_co);
				
				if (data.ref_no=="") 
				{
					$("#btnEdit").attr("hidden",true);
				}	
				
				if (data.id_first==data.id) {
					$("#btnPrev").attr("hidden",true);
				}	else {
					$("#btnPrev").attr("hidden",false);
				}
				
				if (data.id_last==data.id) {
					$("#btnNext").attr("hidden",true);
				}	else {
					$("#btnNext").attr("hidden",false);
				}
				disabled_all()
				$("#table-satu").jqGrid("setGridParam", {datatype: 'json',postData:{id_data : $( '#id' ).val(),}}).trigger("reloadGrid");
				table_detail();
			},
	})		
}

function save_data()
{
	var myData = 
	{
		id 			: $('#id').val(),
		oper 			: $('#oper').val(),
		ref_no		: $('#ref_no' ).val(),
		date        : $("#date").val(),
		shipping_co : $( '#shipping_co' ).val()
	}
	
	if ($( '#date' ).val() == '')
	{
		show_messages('Input \" Date \" Empty Value');
		return false;
	}
	else
	{
		$.ajax
		({
			type: "POST",
			url : "<?php echo base_url('pricelist/export_cost_price_warehouse/manage_header')?>",
			data : myData,
			success: function(response)
			{
				var data = $.parseJSON(response);
				if(data.status == 1 || data.oper=="add")
				{
					get_data('last');
				}
				else if(data.status == 1 || data.oper=="edit")
				{
					get_data('pilih');
				}
				else if(data.status == 1 || data.oper=="del")
				{
					get_data('last');
					$('#delete-confirm').dialog('close');
				}
				else
				{
					get_data('pilih');
				};
			},
			error:function()
			{
				var message = '<div id="content-msg">ERROR ACCOURED</div>';
				show_messages(message);
				get_data('pilih');
			}
		});
	}
}

$(document).ready(function()
{
	disabled_all();
	$('#date').datepicker({dateFormat: 'dd/mm/yy'});	
	$('#valid').datepicker({dateFormat: 'dd/mm/yy'});	
	$('#btnDelete').attr('href','#');	
	$('#btnExit').hide();	
	get_data('last');
		
});

function disabled_all()
{

	$( '#btnNew' ).attr('disabled',false);
	$( '#btnEdit' ).attr('disabled',false);
	$( '#btnNext' ).attr('disabled',false);
	$( '#btnPrev' ).attr('disabled',false);
	$( '#btnSave' ).attr('disabled',true);
	$( '#btnDelete' ).attr('onClick','delete_data()').val('DELETE');
	$( '#btnExit' ).hide().attr('onClick','exit_data()').val('EXIT');
	
	$( '#ref_no' ).attr('disabled',true);
	$( '#date' ).attr('disabled',true);
	$( '#valid' ).attr('disabled',true);
}

function enabled_all()
{
	$( '#btnNew' ).attr('disabled',true);
	$( '#btnEdit' ).attr('disabled',true);
	$( '#btnNext' ).attr('disabled',true);
	$( '#btnPrev' ).attr('disabled',true);
	$( '#btnSave' ).attr('disabled',false);
	$( '#date' ).attr('disabled',false);
}

function cancel()
{
	disabled_all();
	var input = $("#oper").val();
	if(input == 'add')
	{
		get_data('last');
		
	}
	else
	{
		get_data('pilih');
	}
}

function table_detail()
{
	var grid = $("#table-satu"), i;	
	grid.jqGrid(
	{ 
		url:"<?php echo base_url('pricelist/export_cost_price_warehouse/table_detail')?>",
		mtype : "post",
		datatype: "json", 
		postData: 
		{ 
			id_data : $( '#id' ).val(),
		},
		colNames:
		[
			'ITEM OF SERVICE','CURRENCY','CONDITION','CONTAINER','PRICE',''
		],
    	colModel:
		[
			{name:'item_of_service',index:'item_of_service', align:"left", width:170, 
				editable: true,
				edittype:"select",
				editoptions:
				{
					dataUrl:'<?php echo base_url("afg_c_dropdown/dropdownItemService") ?>',
					dataInit: function (el) 
					{ 
						$(el).css({'min-width':'250px','max-width':'250px'});
					},
				},
				searchoptions: {
					searchhidden: true,
					sopt:['cn','eq'],
          	},
			},
			{name:'currency',index:'currency', align:"left", width:110, 
				editable: true,
				edittype:"select", 
				editoptions:
				{
					value:" : ;USD:USD;RP.:RP.", width: 100,
					dataInit: function (el) 
					{ 
						$(el).css({'min-width':'250px','max-width':'250px'});
					},
				},
				searchoptions: {
					searchhidden: true,
					sopt:['cn','eq'],
          	},
			},
			{name:'condition',index:'condition', align:"left", width:110, 
				editable: true,
				edittype:"select",
				formatter:'select', 
				editoptions:
				{
					value:" : ;PREPAID:PREPAID;COLLECT:COLLECT",
					dataInit: function (el) 
					{ 
						$(el).css({'min-width':'250px','max-width':'250px'});
					},
				},
				searchoptions: {
					searchhidden: true,
					sopt:['cn','eq'],
          	},
			},
			{name:'container',index:'container', align:"left", width:110, 
				editable: true,
				edittype:"select",
				editoptions:
				{
					dataUrl:'<?php echo base_url("afg_c_dropdown/dropdownContainer") ?>',
					dataInit: function (el) 
					{ 
						$(el).css({'min-width':'250px','max-width':'250px'}); 
					}
				},
				searchoptions: {
					searchhidden: true,
					sopt:['cn','eq'],
          	},
			},
			{name:'price',index:'price', align:"right", width:110, 
				editable: true,
				formatter: 'number',
    			formatoptions: { decimalSeparator: ".", thousandsSeparator: ",", decimalPlaces: 2, defaultValue: '0.00' },
    			editoptions: { 
    				dataInit: function (el) 
					{ 
						$(el).css({'min-width':'250px','max-width':'250px','text-align':'right'}).attr({'onKeyup':'format_uang(this)','onChange':'format_uang(this)','title':'Hanya boleh memasukan angka'})
					},
				},
				cellattr: function(rowId, cellValue, rawObject, cm, rdata) {
					return 'style="padding-right: 3px"';
				}
			},
			{name:'id_data',index:'id_data', align:"left", width:50, editable: true,hidden:true},
		], 
		sortname: "id",
		sortorder: "asc",
		shrinkToFit: false,
  		viewrecords: true,
      multiselect:true, 
		modal: true,
		rownumbers: true,
		rowNum: 10,
		top: 20,
		width: 710,
		height: 265,
		rowList:[10,20,30],
		gridview: true,
		caption: ' ',
		editurl:'export_cost_price_warehouse/manage_detail',
		cellsubmit:'clientArray',
		pager:'#page-table',
		
		loadComplete: function() {
			$("#cb_"+grid[0].id).hide();
			if ($("#ref_no").val()=="") {
				$( '#add_table-satu' ).attr('hidden', true);
				$( '#edit_table-satu' ).attr('hidden', true);
				$( '#del_table-satu' ).attr('hidden', true);
			}
			else
			{
				$( '#add_table-satu' ).attr('hidden', false);
				$( '#edit_table-satu' ).attr('hidden', false);
				$( '#del_table-satu' ).attr('hidden', false);
			}
			$(".ui-jqgrid-titlebar-close",$("#table-satu")[0].grid.cDiv).hide();
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
	}).navGrid('#page-table',{ edit: true, add: true, del: true,},
	{
		height: "auto", 
		width: "auto", 	
		closeAfterEdit: true, 
		reloadAfterEdit : true, 
		modal: true, 
		editCaption: "Edit Data",
		recreateForm: false,
		viewPagerButtons: false,
		beforeShowForm: function(frm) 
		{
			var dlgDiv = $("#editmod" + grid[0].id);
         dlgDiv[0].style.top = Math.round($(window).height()/20) + "px";
         dlgDiv[0].style.left = Math.round($(window).width()/2.5) + "px";  		
		},
		afterShowForm: function (frm) {
			$("#id_data").val($("#id").val())
		},
		beforeSubmit:function (postData,formId) 
		{
			return [true,""]
		},
		afterSubmit: function () 
		{
			grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
			return [true,""]
		} 
	},
	{
		height: "auto", 
  		width: "auto",  
   	closeAfterAdd: true, 
   	reloadAfterAdd : true, 
   	modal: true, 
  		addCaption: "Add Data",
  		beforeShowForm: function(frm) 
  		{
  			var dlgDiv = $("#editmod" + grid[0].id);
         dlgDiv[0].style.top = Math.round($(window).height()/20) + "px";
         dlgDiv[0].style.left = Math.round($(window).width()/2.5) + "px";
		},

		afterShowForm: function (frm) {
			$("#id_data").val($("#id").val())
		},

		beforeSubmit:function (postData,formId) 
		{
			return [true,""]
		},
		afterSubmit: function () 
		{
			grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
			return [true,""]
		}   		
	})
}

function edit_data()
{
	enabled_all()
	$( '#btnDelete' ).attr('onClick','cancel("edit")').val('CANCEL');
	$( '#oper' ).val('edit');
}

function delete_data()
{
	$( '#delete-confirm' ).html('Delete this data')
	$( '#delete-confirm' ).dialog(
	{
		autoOpen: true,
		resizable: false,
		height: 'auto',
		width: 200,
		modal: true,
		title :"Delete Confirm",
		buttons: 
		{
			"DELETE": function() 
			{
				$.ajax
				({
					type: "POST",
					url : "<?php echo base_url('pricelist/export_cost_price_warehouse/manage_header')?>",
					data : { id : $( '#id' ).val(), oper: 'del' },
					success: function(data)
					{
						var data = $.parseJSON(response);
						if(data.status == 1 || data.oper=="add")
						{
							get_data('last');
						}
						else if(data.status == 1 || data.oper=="edit")
						{
							get_data('pilih');
						}
						else if(data.status == 1 || data.oper=="del")
						{
							get_data('last');
							$('#delete-confirm').dialog('close');
						}
						else
						{
							get_data('pilih');
						};
					},
					error:function()
					{
						show_messages("Data can\'t be update");
						return false;
					}
				}); 
			},
			"CANCEL": function() 
			{
				$( this ).dialog( "close" ); 
			}
		},
		close: function() {	}
	});
}

/*

function table_listing()
{
	var grid = $("#table-urut"), i;	
	grid.jqGrid(
	{ 
		url:"<?php echo base_url('pricelist/export_cost_price_warehouse/table_listing')?>",
		mtype : "post",
		datatype: "json",
		postData: 
		{ 
			agent : $( '#agent' ).val(),
		}, 
		colNames:
		[
			'N0 REF','AGENT','DATE','VALID'
		],
    	colModel:
		[
			{name:'ref_no',index:'ref_no', align:"left", width:5, editable: true,},
			{name:'agent',index:'agent', align:"left", width:5, editable: true,},
			{name:'date',index:'date', align:"left", width:5, editable: true,},
			{name:'valid',index:'valid', align:"left", width:5, editable: true,},
		], 
		sortname: "id",
		sortorder: "asc",
  		viewrecords: true,
      multiselect:false, 
		modal: true,
		rownumbers: true,
		rowNum: 16,
		top: 20,
		width: 685,
		height: 415,
		rowList:[10,20,30],
		gridview: true,
		caption: ' ',
		editurl:'manage_data',
		cellsubmit:'clientArray',
		pager:'#page-urut',
		loadComplete: function() {
			$("#cb_"+grid[0].id).hide();
			$(".ui-jqgrid-titlebar-close",$("#table-urut")[0].grid.cDiv).hide();
		},
	}).navGrid('#page-urut',{ edit: true, add: true, del: true,})
}




function listing_data() 
{
	table_listing();
	$("#table-urut").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
}

function exit_data()
{
	$('#Form-Destination').dialog('close')
}
*/
</script>