<script type="text/javascript">


function table_detail()
{
	var grid = $("#table-satu"), i;	
	grid.jqGrid(
	{ 
		url:"<?php echo base_url('pricelist/export_cost_coloader/table_detail')?>",
		mtype : "post",
		datatype: "json", 
		postData: 
		{ 
			id_data : $( '#id' ).val(),
		},
		colNames:
		[
			'ITEM OF SERVICE','CURRENCY','CONDITION','DESTINATION','','TYPE','PRICE','REMARKS','MCHGS',''
		],
    	colModel:
		[
			{name:'item_of_service',index:'item_of_service', align:"left", width:220, 
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
			{name:'currency',index:'currency', align:"center", width:60, 
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
			{name:'condition',index:'condition', align:"left", width:60, 
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
			{name:'destination',index:'destination', align:"left", width:100, 
				editable: true,
				edittype:"select", 
				editoptions:
				{
					dataUrl:'<?php echo base_url("afg_c_dropdown/dropdownCities") ?>',
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
			{name:'emkl',index:'emkl', align:"center", width:30, 
				editable: true,
				edittype:"select", 
				editoptions:
				{
					value:" : ;OF:OF;MISC:MISC",
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
			{name:'type',index:'type', align:"center", width:60, editable: true, 
				edittype:"select",
				editoptions:
				{
					value:" : ;FCL:FCL;LCL:LCL;AIR FREIGHT:AIR FREIGHT", width: 100,
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
			{name:'price',index:'price', align:"right", width:60, 
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
			{name:'remarks',index:'remarks', align:"left", width:150,
				editable: true,
				editoptions:
				{
					dataInit: function (el) 
					{ 
						$(el).css({'min-width':'250px','max-width':'250px'});
					},
				},
			},
			{name:'mchgs',index:'mchgs', align:"left", width:50,
				editable: true,
				editoptions:
				{
					dataInit: function (el) 
					{ 
						$(el).css({'min-width':'250px','max-width':'250px'});
					},
				},
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
		editurl:'export_cost_coloader/manage_detail',
		cellsubmit:'clientArray',
		pager:'#page-satu',
		
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
	}).navGrid('#page-satu',{ edit: true, add: true, del: true,},
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



$(document).ready(function(){
	get_data('last');
	$('#date').datepicker({dateFormat: 'dd/mm/yy'});
	/*
	disabled_all()
	get_data('last');	
	table_satu()
	$(".ui-jqgrid-titlebar-close",$("#table-satu")[0].grid.cDiv).hide();
	$('#dateForm').datepicker({dateFormat: 'dd/mm/yy'});
	$('#btnDelete').attr('href','#');
	$("#table-satu").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
	*/
});

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
			url:"<?php echo base_url('pricelist/export_cost_coloader/get_data') ?>",
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

function new_data()
{
	$.ajax
	({
			type:"POST",
			url:"<?php echo base_url('pricelist/export_cost_coloader/new_data') ?>",
			data:{ id : $('#id').val(), shipping_co: $('#shipping_co').val()},
			success:function(data)
			{
				var data = $.parseJSON(data);
				$( '#ref_no' ).val(data.ref_no);
			},
	})

	$( '#id' ).val('');
	$( '#date' ).val('');
	$( '#oper' ).val('add');
	
	$( '#btnDelete' ).attr('onClick','cancel("add")').val('CANCEL');
	$( '#table-satu-agent' ).jqGrid("clearGridData", true);
	enabled_all();
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
			url : "<?php echo base_url('pricelist/export_cost_coloader/manage_header')?>",
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

/*

function table_satu()
{
	
	var refNo = $( '#refNo' ).val();
	var shipper = $( '#shipperId' ).val();
	
	var grid = $("#table-satu"), i;	
	grid.jqGrid(
	{ 
		url:"<?php echo base_url('pricelist/export_feeder_rate/get_table_satu')?>",
		mtype : "post",
		datatype: "json", 
		postData: 
		{  
			ref_no: refNo,  
			shipper_id: shipper 
		},
		colNames:
		[
			'',
			'',
			'',
			'Item of Service',
			'Currency',
			'Container',
			'Condition',
			'Destination',
			'Price',
			'Remarks'
		],
    	colModel:
		[
			{name:'shipper_id',index:'shipper_id', align:"left", width:35, editable: true, hidden: true},
			{name:'ref_no',index:'ref_no', align:"left", width:35, editable: true, hidden: true},
			{name:'tanggal',index:'ref_no', align:"left", width:35, editable: true, hidden:true},
			{name:'item_of_service',index:'item_of_service', align:"left", width:230, editable: true, 
				edittype:"select",
				editoptions:
				{
					dataUrl:'<?php echo base_url("afg_c_dropdown/dropdownItemService") ?>'
				}
			},
			{name:'currency',index:'currency', align:"center", width:70, editable: true, 
				edittype:"select", 
				editoptions:
				{
					value:" : ;USD:USD;RP.:RP.", 
				}
			},
			{name:'container',index:'container', align:"left", width:80, editable: true, 
				edittype:"select",
				editoptions:
				{
					dataUrl:'<?php echo base_url("afg_c_dropdown/dropdownContainer") ?>'
				}
			},
			{name:'condition',index:'condition', align:"left", width:80, editable: true, 
				edittype:"select",formatter:'select', 
				editoptions:
				{
					value:" : ;COLLECT:COLLET;PREPAID:PREPAID",
				}
			},
			{name:'destination',index:'destination', align:"left", width:100, editable: true, 
				edittype:"select", 
				editoptions:
				{
					dataUrl:'selling_rate/dropdownCities'
				}
			},
			{name:'price',index:'price', align:"right", width:80, editable: true, 
				formatter: 'number',
    			formatoptions: { decimalSeparator: ".", thousandsSeparator: ",", decimalPlaces: 2, defaultValue: '0.00' },
    			editoptions: { size: 40, dataInit: function (el) 
				{ 
					$(el).attr('onKeyup','formatUang(this)').attr('onChange','formatUang(this)').attr('title','Hanya boleh memasukan angka'); }
				},
			},
			{name:'remarks',index:'remarks', align:"left", width:250, editable: true, 
				edittype:"textarea", 
				editoptions:
				{
					rows:2,
				}
			},
		], 
		shrinkToFit: false,
  		viewrecords: true,
      multiselect:true, 
		modal: true,
		rownumbers: true,
		rowNum: 16,
		top: 20,
		width: 703,
		height: 230,
		rowList:[10,20,30,40,50,60],
		gridview: true,
		cellEdit: true,
		caption:"Table Detail",
		editurl:'selling_rate/manage_data',
		cellsubmit:'clientArray',
		pager:'#page-table-satu',
				
		loadComplete: function() {
			$("#cb_"+grid[0].id).hide();
			
			disabled_all()
			
			if ($("#refNo").val()=="") {
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
			$(".ui-jqgrid-titlebar-close",$("#table-satu-agent")[0].grid.cDiv).hide();
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
		
		ondblClickRow: function(rowid, e){
			var selected = $("tr.jqgrow > td > input.cbox:checked", grid[0]);						
			selected[rowid] = status;
			show_table_destination(rowid);	
			$("#table-destination").jqGrid("setGridParam", {datatype: 'json', postData:{ rateguidance_id:rowid }}).trigger("reloadGrid");	
			$( "#btnPrint" ).html('<div id="topbutton" style="float:right"><a href="#" onClick="print_data('+rowid+')">PRINT OUT</a></div>'); 			
		}
		
	})
		grid.jqGrid('navGrid','#page-table-satu', { edit: false, add: false, del: true, refresh: false,},
		{ }, 
   	{ },
     	{
     		height: "auto", 
     		width: "auto",
     		afterSubmit: function(){
    		grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
    			return [true];
			}
     	},
      { },
     	{ }
	)
	grid.inlineNav('#page-table-satu',
		{ 
			edit: false,
   		editicon: "ui-icon-pencil",
   		add: true,
   		addicon:"ui-icon-plus",
   		save: false,
   		saveicon:"ui-icon-disk",
   		cancel: false,
   		cancelicon:"ui-icon-cancel",
   		addParams : 
   		{
   			useFormatter : true, 
   		},
   		editParams : {}
		}
	)
}

function disabled_all()
{

	$( '#btnNew' ).attr('disabled',false);
	$( '#btnEdit' ).attr('disabled',false);
	$( '#btnNext' ).attr('disabled',false);
	$( '#btnPrev' ).attr('disabled',false);
	$( '#btnSave' ).attr('disabled',true);
	$( '#btnDelete' ).attr('onClick','delete_data()').val('DELETE');
	$( '#btnCetak' ).attr('disabled',false);
	
	$( '#refNo' ).attr('disabled',true);
	$( '#dateForm' ).attr('disabled',true);
	$( '#codeForm' ).attr('disabled',true);
	$( '#nameForm' ).attr('disabled',true);
	$( '#dropdownPortLoading' ).attr('disabled',true);
	$( '#dropdownTransipmentPort' ).attr('disabled',true);
}

function enabled_all()
{
	$( '#btnNew' ).attr('disabled',true);
	$( '#btnEdit' ).attr('disabled',true);
	$( '#btnNext' ).attr('disabled',true);
	$( '#btnPrev' ).attr('disabled',true);
	$( '#btnSave' ).attr('disabled',false);
	$( '#btnCetak' ).attr('disabled',true);
	
	$( '#refNo' ).attr('disabled',false);
	$( '#dateForm' ).attr('disabled',false);
	$( '#codeForm' ).attr('disabled',false);
	$( '#nameForm' ).attr('disabled',false);
	$( '#dropdownPortLoading' ).attr('disabled',false);
	$( '#dropdownTransipmentPort' ).attr('disabled',false);
}

function editor(input)
{
	
	if(input == 'true')
	{
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
}

function delete_selling()
{
	var id = $('#idSellingRate').val();
	var ref_no = $('#refNo').val();
	$('#message-selling').html('Are you sure want to delete this data ?');
	$( "#message-selling" ).dialog(
	{
		autoOpen: true,
		resizable: true,
		top:10,
		height: 150,
		width: 'auto',
		modal: true,
		title: "Delete Confirm",
		buttons : 
		{  
			"DELETE": function()
			{
				$.ajax
				({
					type: "POST",
					url : "<?php echo base_url('pricelist/selling_rate/manages_data')?>/",
					data: { id : id, refNo: ref_no, oper:'del' },
					success: function(data)
					{
						var message = '<div id="content-msg">Deleted Success</div>';
						show_msg(message);
					},
					error: function()
					{ 
						var message = '<div id="content-msg">Data can\'t deleted</div>';
						show_msg(message);
						$(this).dialog('close');
					}
				});
				$(this).dialog('close');	
			},
			"CANCEL": function()
			{
				$(this).dialog('close');
			}
		},
		close: function()  {	 }
	});
}

function show_msg(char){
	$('#msg-selling').html(char);
   $('#msg-selling').fadeIn('slow').delay(2000).fadeOut('slow');
}

function table_listing(){
	var grid = $("#table-listing"), i;	
	grid.jqGrid(
	{ 
		url:"<?php echo base_url('pricelist/selling_rate/get_table_listing')?>",
		mtype : "post",
		datatype: "json", 
		postData: 
		{ 
			shipper_id : $('#shipperId').val(),
		},
		colNames:
		[
			'Ref No','Tanggal','Valid','Kepada','Attn','Dari'
		],
    	colModel:
		[
			{name:'ref_no',index:'ref_no', align:"left", width:5, editable: true,},
			{name:'tanggal',index:'tanggal', align:"left", width:5, editable: true,},
			{name:'valid',index:'valid', align:"left", width:5, editable: true,},
			{name:'kepada',index:'kepada', align:"left", width:5, editable: true,},
			{name:'attn',index:'attn', align:"left", width:5, editable: true,},
			{name:'dari',index:'dari', align:"left", width:5, editable: true,},
		], 
  		viewrecords: true,
      multiselect:true, 
		modal: true,
		rownumbers: true,
		rowNum: 16,
		top: 20,
		width: 670,
		height: 370,
		rowList:[10,20,30,40,50,60],
		gridview: true,
		caption: ' ',
		editurl:'manage_data',
		cellsubmit:'clientArray',
		pager:'#page-listing',
		
		loadComplete: function() {
			$("#cb_"+grid[0].id).hide();
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
    			$('#idSelling').val(rowid);
    			get_data('pilih');
    			$('#tab-1').show();
    			$('#tab-2').hide();
    		}
        	else
        	{
        		var cbs = $("tr.jqgrow > td > input.cbox:disabled", grid[0]);  		
    			cbs.attr("disabled", false);       			
        	}
		},
	}).navGrid('#page-listing',{ edit: false, add: false, del: false,})
}

function table_urut(){
	var grid = $("#table-urut"), i;	
	grid.jqGrid(
	{ 
		url:"<?php echo base_url('pricelist/selling_rate/get_table_urut')?>",
		mtype : "post",
		datatype: "json", 
		colNames:
		[
			'No Urut','Kode Shipper','No Bukti','O/F or MISC'
		],
    	colModel:
		[
			{name:'no_urut',index:'no_urut', align:"left", width:5, editable: true,},
			{name:'kode_shipper',index:'kode_shipper', align:"left", width:5, editable: true,},
			{name:'no_bukti',index:'no_bukti', align:"left", width:5, editable: true,},
			{name:'of_or_misc',index:'of_or_misc', align:"left", width:5, editable: true,},
		], 
  		viewrecords: true,
      multiselect:false, 
		modal: true,
		rownumbers: true,
		rowNum: 16,
		top: 20,
		width: 670,
		height: 370,
		rowList:[10,20,30,40,50,60],
		gridview: true,
		caption: ' ',
		editurl:'manage_data',
		cellsubmit:'clientArray',
		pager:'#page-urut',
	}).navGrid('#page-urut',{ edit: false, add: false, del: false,})
}

function exit_data(){
	$('#Form-Destination').dialog('close')
}

function cancel(input){
	if(input == 'add')
	{
		get_data('last');
		
	}
	else
	{
		get_data('pilih');
	}
	disabled_all();
}

function new_data(){
	

	var rowCount  = $("#table-listing").getGridParam("reccount");
	if( rowCount > 0 )
	{
		var lastSelRow = [];
		var rowid = $("#table-listing").jqGrid("setSelection", lastSelRow, true)
		var newRef  = '<?php echo date("y")."-".date("m")."-"?>'+rowid;
	}	
	else
	{
		var newRef  = '<?php echo date("y")."-".date("m")."-"?>'+'0001';
	}
	
	enabled_all();
	$( '#btnDelete' ).attr('onClick','cancel("add")').val('CANCEL');
	$( '#idSelling' ).val('');
	$( '#operForm' ).val('add');
	$( '#table-satu' ).jqGrid("clearGridData", true);
	$( '#idSelling' ).val('');
	$( '#refNo' ).val(newRef);
	$( '#dateForm' ).val('');
	$( '#validForm' ).val('');
	$( '#fromForm' ).val('');
	$( '#toForm' ).val('');
	$( '#attnForm' ).val('');
	$( '#containerForm' ).val('');
	$( '#remarkForm' ).val('');
	$( '#emkl_type' ).val('');
}

function edit_data(){
	enabled_all()
	$( '#btnDelete' ).attr('onClick','cancel("edit")').val('CANCEL');
	$( '#operForm' ).val('edit');
}

function delete_data(){
	$( '#delete-confirm' ).html('Delete this data')
	$( '#delete-confirm' ).dialog(
	{
		autoOpen: true,
		resizable: false,
		height: 'auto',
		width: 'auto',
		modal: true,
		title :"Delete Confirm",
		buttons: 
		{
			"DELETE": function() 
			{
				$.ajax
				({
					type: "POST",
					url : "<?php echo base_url('pricelist/selling_rate/manage_data_selling')?>",
					data : { id : $( '#idSelling' ).val(), oper: 'del' },
					success: function(data)
					{
						var data = $.parseJSON(data);
						if(data.status == 1 )
						{
							get_data('last');
							$('#table-listing').jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
						}
						else
						{
							get_data('pilih');
						};
						$( '#delete-confirm' ).dialog('close');
					},
					error:function()
					{
						var message = '<div id="content-msg">Data can\'t be deleted</div>';
						show_msg(message);
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

function next_data(){
	get_data('next');
}

function prev_data(){
	get_data('prev');
}

function save_data(){
	var myData = 
	{
		id 					: $('#id').val(),
		oper 					: $('#oper').val(),
		shipping_co			: $('#shipping_co').val(),
		port_loading		: $('select#port_loading').val(),
		port_loading		: $('select#port_loading').val(),
	}
	var tanggal = $( '#dateForm' ).val();
	var valid   = $( '#validForm' ).val();
	if (tanggal == '' || valid == '')
	{
		var message = '<div id="content-msg">Input \"Date / Valid\" Empty Value</div>';
		show_msg(message);
		return false;
	}
	else
	{
		$.ajax
		({
			type: "POST",
			url : "<?php echo base_url('pricelist/selling_rate/manage_data_selling')?>",
			data : myData,
			success: function(data)
			{
				var data = $.parseJSON(data);
				if(data.status == 1 )
				{
					get_data('last');
					$('#table-listing').jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
				}
				else
				{
					get_data('pilih');
				};
			},
			error:function()
			{
				var message = '<div id="content-msg">ERROR ACCOURED</div>';
				show_msg(message);
				get_data('pilih');
			}
		});
	}
}



function checkRadioBtn(N){
	var ctr = 0;
   var radioLength = $('input[name="emkl_type"]').length;
   for (var i = 0; i < radioLength; i++) 
	{
    	if ($('input[name="emkl_type"]')[i].value == N) 
		{
			$('input[name="emkl_type"]')[i].checked = true;
        	break;
    	}
    }
}

function formatUang(input){
	var num = input.value.replace(/\,/g,'');
	if(!isNaN(num)){
   	if(num.indexOf('.') > -1){
    		num = num.split('.');
    		num[0] = num[0].toString().split('').reverse().join('').replace(/(?=\d*\.?)(\d{3})/g,'$1,').split('').reverse().join('').replace(/^[\,]/,'');
    		if(num[1].length > 2){
				var message = '<div id="content-msg">Only 2 ( two ) desimal allowed</div>';
				show_msg(message);
    			num[1] = num[1].substring(0,num[1].length-1);
    		} input.value = num[0]+'.'+num[1];
    	} else{ input.value = num.toString().split('').reverse().join('').replace(/(?=\d*\.?)(\d{3})/g,'$1,').split('').reverse().join('').replace(/^[\,]/,'') };
	}
	else
	{ 
		var message = '<div id="content-msg">Only numeric allowed</div>';
		show_msg(message);
    	input.value = input.value.substring(0,input.value.length-1); 
	}
}

function gubahUang(num) {
	if(num.indexOf('.') > -1){
		num = num.split('.');
 		num[0] = num[0].toString().split('').reverse().join('').replace(/(?=\d*\.?)(\d{3})/g,'$1,').split('').reverse().join('').replace(/^[\,]/,'');
		return num[0];
	}
}
*/
</script>