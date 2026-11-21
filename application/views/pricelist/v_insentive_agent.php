<div id="FORM-INTENSIVE">
<div id="FORM-BROWSE">
	<table id="table-browse"></table>
	<div id="page-browse" style="font-size: 11px;"></div>
</div>
</div>
<div id="delete-confirm"></div>

<div id="FORM-INPUT">
<!--
	<div id="frm-ins">
		<div style="float: left; width: 80px">AGENT CODE</div>
		<div style="float: left; width: 10px">:</div>
		<div style="float: left; width: 100px"><input type="text" id="agent" readonly="readonly"></div>
		<div style="clear: both; height: 5px"></div>
		<div style="float: left; width: 80px">DATE</div>
		<div style="float: left; width: 10px">:</div>
		<div style="float: left; width: 100px"><input type="text" id="tanggal" readonly="readonly"></div>
		<div style="clear: both; height: 5px"></div>
	<table id="table-detail"></table>
	<div id="page-detail"></div>
	</div>
-->
</div>

<?php echo form_input(array('type'=>'hidden','id'=>'idInsentive', 'name'=>'idInsentive')) ?>

<script type="text/javascript">

$(document).ready(function () 
{
	$("#frm-ins").attr("hidden",true);
	
	$( '#FORM-INTENSIVE' ).fadeIn('slow', function(){
		$(this)
		.css('font-size','11px')
		.css('margin', '10px')
		.dialog({
			resizable: false,
			modal: true,
			width: 620,
			height: 430,
			title: 'INTENSIVE FROM AGENT',	
		})	
	})
	
	var grid = $("#table-browse"), i;		   			
	grid.jqGrid(
	{
		url:"<?php echo base_url('pricelist/insentive_agent/table_browse')?>", 
		mtype : "post",  
		sortname: "id",
		sortorder: "asc", 
		datatype: "json",  
		colNames:
		[ 
			'Date',
			'Agent',	
			'Detail',	
		],
		colModel:
		[
			{name:'date',index:'date', width:200, align:"center",
				editable: true,
				editoptions:{
					size:"20",
					dataInit: function(element) {
        				$(element).datepicker({dateFormat : "dd/mm/yy"})
      			}
				},
				stype:"text",
				searchoptions:{ 
					sopt:['eq','lt','gt'],
					dataInit: function(element) {
        				$(element).datepicker({dateFormat : "dd/mm/yy"});
      			}  
				}
			},
			{name:'agent_code',index:'agent_code', width:200, align:"left",
				editable: true,
				edittype:"select",
				editoptions:{ 
					dataUrl:window.location.origin+'/afg_c_dropdown/dropdownAgent',
					dataInit : function (e) {
						$(e).css('min-width','150px')
					}
				},
			},
			{name:'detail',index:'detail', width:200, align:"center",edittable: false,
				formatter: function(cellVal,options,rowObject) {
					return '<input style="height:20px; width: 80px; margin: 2px; color: #33f; cursor: pointer" type="button" value="DETAIL" onclick="select_detail('+cellVal+')" />';
				},
			},
		],
		multiselect: true,
		shrinktofit: false,
		loadonce : false,
		modal: true,
		rownumbers: true,
		rowNum:15,
		width: 580,
		height: 320,
		rowList:[15,30,45],
		viewrecords: true,
		rownumbers: true,
		gridview: true,
		pager: '#page-browse',
		caption:"",
		cellEdit:true,
		cellsubmit:'clientArray',
		editurl:'insentive_agent/manage_data',
		
		afterSaveCell: function(rowid, cellname, value, iRow, iCol) {
			update_table_browse(rowid,cellname,value)
    	},
						
		loadComplete: function() {
			$("#cb_"+grid[0].id).hide();
			$(".ui-jqgrid-titlebar-close",$("#table-browse")[0].grid.cDiv).hide();
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
	.navGrid('#page-browse', 
		{ edit: false, add: false, del: true },
		{ },
   	{ },
   	{ },
   	{closeAfterSearch : true, captions: "Search Data", Find:"Cari"}, 
   	{ }
	)
	grid.navButtonAdd('#page-browse',{ 
		caption:" ", 
		buttonicon:"ui-icon-plus", 
		onClickButton: insert_table_browse, 
		position: "first", 
		title:"Insert new recordd", 
		cursor: "pointer"
	})
	
});

function insert_table_browse()
{
	$.ajax
	({
		type: "POST",
		url : "<?php echo base_url('pricelist/insentive_agent/manage_data')?>",
		data : { 
			oper : 'add',
			agent_code : '',
			date : '',
		},
		success: function(data)
		{
			$('#table-browse').jqGrid("setGridParam", { datatype: 'json', sortname: "id", sortorder: "asc", }).trigger("reloadGrid");
		}
	})
}

function update_table_browse(rowid,cellname,value)
{
	$.ajax
	({
		type:"POST",
		url : "<?php echo base_url('pricelist/insentive_agent/manage_data')?>",
		data:
		{
			id : rowid,
			kolom : cellname,
			isi : value,
			oper : 'edit_rows',
		},
		success:function(response)
		{
			var data = $.parseJSON(response);
			$('#table-browse').jqGrid("setGridParam", { datatype: 'json', sortname: "id", sortorder: "asc", }).trigger("reloadGrid");
    		return [true];
		}
	})
}

function select_detail(rowid) 
{
	$("#frm-ins").attr("hidden",false);
	$( '#FORM-INPUT' ).fadeIn('slow', function(){
		$(this)
		.css('font-size','11px')
		.css('margin', '10px')
		.dialog({
			resizable: false,
			modal: true,
			width: 640,
			height: 450,
			title: 'DETAIL INTENSIVE FROM AGENT '+$('#table-browse').jqGrid('getCell',rowid,'agent_code'),	
			recreateForm: true,
			close: function () {

			}
		})
	})
	$( '#FORM-INPUT' ).html('<div style="float: left; width: 80px">AGENT CODE</div>'+
			'<div style="float: left; width: 10px">:</div>'+
			'<div style="float: left; width: 100px"><input type="text" id="agent" readonly="readonly"></div>'+
			'<div style="clear: both; height: 5px"></div>'+
			'<div style="float: left; width: 80px">AGREEMENT DATE</div>'+
			'<div style="float: left; width: 10px">:</div>'+
			'<div style="float: left; width: 100px"><input type="text" id="tanggal" readonly="readonly"></div>'+
			'<div style="clear: both; height: 5px"></div>'+
			'<table id="table-detail"></table>'+
			'<div id="page-detail"></div>')
		
	$('#agent').val($('#table-browse').jqGrid('getCell',rowid,'agent_code'));
	$('#tanggal').val($('#table-browse').jqGrid('getCell',rowid,'date'));
	$('#idInsentive').val(rowid);
	$('#table-detail').jqGrid("setGridParam", { datatype: 'json', sortname: "id", sortorder: "asc", postData :{id_insentive: rowid}}).trigger("reloadGrid");
	table_detail()
}

function table_detail()
{
	var grid = $('#table-detail'), i;
	grid.jqGrid({
		url:"<?php echo base_url('pricelist/insentive_agent/table_detail')?>", 
		mtype : "post",  
		datatype: "json",
		postData:{ id_insentive : $('#idInsentive').val() },
		colNames:
		[ 
			'Container',
			'Destination',
			'Amount',
			'Currency',
		],
		colModel:
		[
			{name:'countainer',index:'countainer', width:100, align:"center", editable: true,
				edittype:"select", editoptions:{dataUrl:'<?php echo base_url("afg_c_dropdown/dropdownContainer")?>'}
			},
			{name:'destination',index:'destination', width:200, align:"left", editable: true,
				editoptions:{
					dataInit: function (elem) {
					myAutocomplete(elem, "/afg_c_dropdown/dropdown_port");
					},
				}
			},
			{name:'amount',index:'amount', width:100, align:"right", editable: true,
				formatter: 'number',
    			formatoptions: { decimalSeparator: ",", thousandsSeparator: " ", decimalPlaces: 2, defaultValue: ' ' },
    			editoptions: {
    				dataInit: function (el) 
					{ 
						$(el).attr('onKeyup','format_uang(this)').attr('onChange','format_uang(this)').attr('title','Hanya boleh memasukan angka'); 
					}
				},	
			},
			{name:'currency',index:'currency', align:"center", width:100, editable: true, 
				edittype:"select",formatter:'select', editoptions:{value:" : ;USD:USD;RP.:RP."}
			},
		],
		sortname: "id",
		sortorder: "asc",
		loadonce : false,
		shrinkToFit: false,
		multiselect:true, 
		modal: true,
		rownumbers: true,
		rowNum:10,
		width: 580,
		height: 270,
		rowList:[10,20,30],
		viewrecords: true,
		rownumbers: true,
		gridview: true,
		cellEdit:true,
		cellsubmit:'clientArray',
		pager: 'page-detail',
		caption:false,
		editurl:'insentive_agent/manage_detail',
						
		loadComplete: function(data) {
			$("#cb_"+grid[0].id).hide();
			$(".ui-jqgrid-titlebar-close",$("#table-detail")[0].grid.cDiv).hide();
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
		beforeSaveCell: function(rowid, cellname, value, iRow, iCol) {
			update_table(rowid,cellname,value)
    	},			
	})
	grid.navGrid('#page-detail', { add: false, edit: false, del: true, refresh: false, _search: false },{},{},{})
	grid.navButtonAdd('#page-detail',{ 
		caption:'', 
		buttonicon:"ui-icon-plus", 
		onClickButton: insert_table, 
		position: "last", 
		title:"Insert new record", 
		cursor: "pointer"
	})
}

function insert_table()
{
	$.ajax
	({
		type: "POST",
		url : "<?php echo base_url('pricelist/insentive_agent/manage_detail')?>",
		data : { 
			oper : 'add',
			id		: '',
			id_insentive : $("input[id='idInsentive']").val(),
			countainer : '',
			destination : '',
			amount: '',
			currency: '',
		},
		success: function(data)
		{
			$('#table-detail').jqGrid("setGridParam", { datatype: 'json', sortname: "id", sortorder: "asc", }).trigger("reloadGrid");
		}
	})
}

function update_table(rowid,cellname,value)
{
	if(cellname == 'amount'){
		value = value.replace(/\ /g,'')
	}
	$.ajax
	({
		type:"POST",
		url : "<?php echo base_url('pricelist/insentive_agent/manage_detail')?>",
		data:
		{
			id : rowid,
			kolom : cellname,
			isi : value,
			oper : 'edit',
		},
		success:function(response)
		{
			var data = $.parseJSON(response);
			$('#table-detail').jqGrid("setGridParam", { datatype: 'json', sortname: "id", sortorder: "asc", }).trigger("reloadGrid");
    		return [true];
		}
	})
}

function isi(value, colname)
{
	if (value != '') 
		return [true,""];
   else 
   	$( 'refNo' ).focus();
   	return [false,"Please insert \"RefNo\", \"Date\", \"Kode Agent\" First"];
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
</script>