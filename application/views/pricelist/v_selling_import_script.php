<script type="text/javascript">
$(document).ready(function(){
	$('#tabs-lading .the-tab').hide();
	$('#tabs-lading div:first').show();
	$('#tabs-lading ul li:first').addClass('active');
 
	$('#tabs-lading ul li a').click(function(){
		$('#tabs-lading ul li').removeClass('active');
		$(this).parent().addClass('active');
		var currentTab = $(this).attr('href');
		$('#tabs-lading .the-tab').hide();
		$(currentTab).show();
		return false;
	});
	get_data_form('last');
	disabled_all();
	table_listing();
});

function cancel_data(){
	var mode = $("input[id='operForm']").val();
	if(mode=='del'){
		$("#delete-form").fadeOut('slow', function(){
			$(this).dialog('close');
			get_data_form('pilih');
		})
	} else if(mode=='edit') {
		get_data_form('pilih')	
	} else {
		get_data_form('last')	
	}
}

function delete_data(){
	$("input[id='operForm']").val('del');
	$("#delete-form").fadeIn('slow', function(){
		$(this).dialog({
			resizable: false,
			modal:true,
			width: 300,
			height:140,
			title: 'Delete Confirm',
		//	close: function(){
			//	$(this).html('');
		//	}
		})
		.css('margin','10px')
		.css('font-size','11px')
		$(this)
		.html('Are you sure want to delete this data ? <hr>'+
		'<input type="button" style="width: 80; height: 30" value="DELETE" onclick="save_data()">'+
		'<input type="button" style="float: right; width: 80; height: 30" value="CANCEL" onclick="cancel_data()">')	
	})
}

function save_data()
{
	$.ajax
	({
			type:"POST",
			url:"<?php echo base_url('pricelist/import_selling_rate/manage_data_form') ?>",
			data:{ 
				id 					: $("input[id='idForm']").val(), 
				oper 					: $("input[id='operForm']").val(), 
				ref_no 				: $("input[id='refNo']").val().replace('IMP-<?php echo date("y") ?>-',''), 
				consignee_id 		: $("input[id='consigneeImportId']").val(), 
				sales_id 			: $("select[id='salesImport']").val(), 
				date		 			: $("input[id='date']").val(), 
				valid		 			: $("input[id='valid']").val(), 
				region_id 			: '<?php echo $this->session->userdata("region_id") ?>', 
			},
			success:function(data)
			{
				var data = $.parseJSON(data);
				if(data.oper=='edit'){
					get_data_form('pilih')	
				} else if(data.oper=='del'){
					$("#delete-form").fadeOut('slow', function(){
						$(this).dialog('close');
						get_data_form('last');
					})
				} else {
					get_data_form('last');
				}
				$('#table-listing').jqGrid('setGridParam', {postData: {consignee_id: $("input[id='consigneeImportId']").val()}}).trigger("reloadGrid");
				table_listing();		
			},
	})
}

function get_data_form(input)
{
	disabled_all();
	$.ajax
	({
			type:"POST",
			url:"<?php echo base_url('pricelist/import_selling_rate/get_data_form') ?>",
			data:{ 
				id 				: $("input[id='idForm']").val(), 
				consignee_id 	: $("input[id='consigneeImportId']").val(), 
				sales_id		 	: $("input[id='salesImport']").val(), 
				mode : input 
			},
			success:function(data)
			{
				var data = $.parseJSON(data);
				$( '#refNo' ).val(get_refno(data.ref_no,'aktif'));
				$( '#idForm' ).val(data.id);	
				$( '#idFirst' ).val(data.first_id);	
				$( '#idLast' ).val(data.last_id);	
				$( '#salesImport' ).val(data.sales_id).attr('disabled',true);
				$( '#date' ).val(data.date).attr('disabled',true);
				$( '#valid' ).val(data.valid).attr('disabled',true);
				$( '#LastRefNo' ).val(data.last_ref_no);
				
				if(data.id==data.last_id){
					$("input[id='btnNext']").attr('disabled',true);
				}
				if(data.id==data.first_id){
					$("input[id='btnPrev']").attr('disabled',true);
				}
				if(data.id==''){
					$("input[id='btnEdit']").attr('disabled',true);
				}		
				$('#table-detail').jqGrid('setGridParam', {  postData: {  id: data.id  }  } ).trigger("reloadGrid");
				table_detail();		
			},
	})
}

function get_refno()
{
	var rowCount  = arguments[0];
	var mode = arguments[1];
	var th = '<?php echo date("y") ?>';
	if( rowCount > 0 ) {
		$.strPad = function(i,l,s) {
			var o = i.toString();
			if (!s) { s = '0'; }
				while (o.length < l) {
				o = s + o;
			}
			return o;
		}
		if(mode=='aktif'){
			var nom = eval(rowCount);
			var RefNo =  $.strPad( nom , 5, '0');		
			return  'IMP-'+th+'-'+RefNo;	
		}
		if(mode=='new'){
			var nom = eval(rowCount)+ eval(1);
			var newRefNo =  $.strPad( nom , 5, '0');		
			return  'IMP-'+th+'-'+newRefNo;	
		}
	} else {
		return  'IMP-'+th+'-'+'00001';
	}
}

function disabled_all()
{
	$( '#btnNew' ).attr('disabled',false);
	$( '#btnEdit' ).attr('disabled',false);
	$( '#btnNext' ).attr('disabled',false);
	$( '#btnPrev' ).attr('disabled',false);
	$( '#btnBrowse' ).attr('disabled',false);
	$( '#btnSave' ).attr('disabled',true);
	$( '#btnDelete' ).attr('onClick','delete_data()').val('DELETE');
	$("#page-detail").show();
}

function enabled_all()
{
	$( '#salesImport' ).attr('disabled',false);
	$( '#date' ).attr('disabled',false).datepicker({dateFormat : "dd/mm/yy"})
	$( '#valid' ).attr('disabled',false).datepicker({dateFormat : "dd/mm/yy"})
	
	$( '#btnNew' ).attr('disabled',true);
	$( '#btnEdit' ).attr('disabled',true);
	$( '#btnNext' ).attr('disabled',true);
	$( '#btnPrev' ).attr('disabled',true);
	$( '#btnBrowse' ).attr('disabled',true);
	$( '#btnSave' ).attr('disabled',false);
	$("#page-detail").hide();
}

function new_data()
{
	enabled_all();
	$("input[id='idForm']").val('')
	$("input[id='operForm']").val('add');
	$("input[id='refNo']").val(get_refno($("input[id='LastRefNo']").val(),'new'));
	$( '#salesImport' ).val('');
	$( '#btnDelete' ).attr('onClick','cancel_data()').val('CANCEL');
}

function edit_data()
{
	enabled_all();
	$("input[id='operForm']").val('edit');
	$( '#btnDelete' ).attr('onClick','cancel_data()').val('CANCEL');
}

function table_detail()
{
	var grid = $("#table-detail"), i;	
	grid.jqGrid(
	{ 
		url:"<?php echo base_url('pricelist/import_selling_rate/get_table_detail')?>",
		mtype : "post",
		datatype: "json", 
		postData: 
		{ 
			id			: $("input[id='idForm']").val(),
		},
		colNames:
		[
			'Service',
			'Port of Loading',
			'P/C',
			'Type',
			'Container',
			'Currency',
			'Unit Price',
			'Min charge',
		],
    	colModel:
		[
			{name:'service',index:'service', align:"left", width:150, editable: true, 
				edittype:"select",
				editoptions:{
					dataUrl:'../afg_c_dropdown/dropdownItemService',
					dataInit: function (el) 
					{ 
						$(el).css({'max-width':'150px'}); 
					},
					dataEvents: 
					[{  type: 'focus',
                  	fn: function(e) {
                       	on_edit(true);
                   	}
               }]
				}
			},
			{name:'port_loading',index:'port_loading', align:"left", width:100, editable: true, 
				editoptions:{
					dataInit: function (elem) {
					myAutocomplete(elem, "/afg_c_dropdown/dropdown_port");
					},
				}
			},
			{name:'pre_col',index:'pre_col', align:"center", width:80, editable: true, 
				edittype:"select",
				editoptions:
				{
					value:" : ;COLLECT:COLLET;PREPAID:PREPAID",
					dataInit: function (el) 
					{ 
						$(el).css({'max-width':'80px'}); 
					},
					dataEvents: 
					[{  type: 'focus',
                  	fn: function(e) {
                       	on_edit(true);
                   	}
               }]
				}
			},
			{name:'type',index:'set_m3', align:"center", width:100, editable: true, 
				edittype:"select", 
				editoptions:
				{
					value:"SF IMPORT FCL:SF IMPORT FCL;SF IMPORT LCL: SF IMPORT LCL",
					dataInit: function (el) 
					{ 
						$(el).css({'max-width':'100px'}); 
					},
					dataEvents: 
					[{  type: 'focus',
                  	fn: function(e) {
                       	on_edit(true);
                   	}
               }]
				}
			},
			{name:'container',index:'container', align:"center", width:80, editable: true, 
				edittype:"select",
				editoptions:{
					dataUrl:'../afg_c_dropdown/dropdownContainer',
					dataInit: function (el) 
					{ 
						$(el).css({'max-width':'80px'}); 
					},
					dataEvents: 
					[{  type: 'focus',
                  	fn: function(e) {
                       	on_edit(true);
                   	}
               }]
				}
			},
			{name:'currency',index:'currency', align:"center", width:50, editable: true, 
				edittype:"select",
				formatter:'select', 
				editoptions:{
					value:" : ;USD:USD;RP.:RP.",
					dataInit: function (el) 
					{ 
						$(el).css({'max-width':'50px'}); 
					},
					dataEvents: 
					[{  type: 'focus',
                  	fn: function(e) {
                       	on_edit(true);
                   	}
               }]
            }
			},
			{name:'unit_price',index:'unit_price', align:"right", width:60, editable: true, 
				formatter : 'number',
				formatoptions: { decimalSeparator: ".", thousandsSeparator: " ", decimalPlaces: 2, defaultValue: '' },
				editoptions: {
    				dataInit : function (e) {
						$(e).css({'max-width':'60px','min-width':'60px',"text-align":"center"})
        				$(e).attr({ "onChange":"format_number(this,2)","onKeyUp":"format_number(this,2)"});
					},
					dataEvents: 
					[{  type: 'focus',
                  	fn: function(e) {
                       	on_edit(true);
                   	}
               }]
				}
			},
			{name:'mchgs',index:'mchgs', align:"center", width:60, editable: true, 
				formatter : 'number',
				formatoptions: { decimalSeparator: ".", thousandsSeparator: " ", decimalPlaces: 0, defaultValue: '' },
				editoptions: {
    				dataInit : function (e) {
						$(e).css({'max-width':'60px','min-width':'60px',"text-align":"center"})
        				$(e).attr({ "onChange":"format_number(this,0)","onKeyUp":"format_number(this,0)"});
					},
					dataEvents: 
					[{  type: 'focus',
                  	fn: function(e) {
                       	on_edit(true);
                   	}
               }]
				}
			},
		],
		sortname: "id",
		sortorder: "asc",
		loadonce: true,
		shrinkToFit: false, 
  		viewrecords: true,
        multiselect:true, 
		modal: true,
		rownumbers: true,
		rowNum: 15,
		top: 20,
		width: 835,
		height: 220,
		rowList:[15,30,45,60,75,90,105,120],
		gridview: true,
		caption: false,
		cellEdit: true,
		editurl:'import_selling_rate/manage_data_detail',
		cellsubmit:'clientArray',
		pager:'#page-detail',
				
		loadComplete: function() {
			$("#cb_"+grid[0].id).hide();
			var rowCount  = $('#table-listing').getGridParam("reccount");
			if( rowCount == 0 )
			{
				on_edit(true)
			} else {
				on_edit(false)
			}
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
     		}
        	else
        	{
        		var cbs = $("tr.jqgrow > td > input.cbox:disabled", grid[0]);  			
        	}
		},
		afterSaveCell: function(rowid, cellname, value, iRow, iCol) {
			save_data_detail(rowid, cellname, value, iRow, iCol)
			if(event.keyCode==13) 
			{
				grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
			}
    	},	
	})
	grid.navGrid('#page-detail',{ edit: false, add: false, del: true, search: false, refresh: true },{ },{ }, 
   		{
   			height: "auto", 
   			width: "auto", top: 3, left: 250, 
   			modal: true, 
   			caption: "Delete Confirm",
   			afterSubmit: function () {
    				grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
    				return [true];
				}
   		},
     		{height: "auto", width: "auto"},
     		{ }
    )
    grid.navButtonAdd('#page-detail',{ 
			caption:"", 
			buttonicon:"ui-icon-plus", 
			onClickButton: insert_new_data, 
			position: "last", 
			title:"Insert New Data", 
			cursor: "pointer",
			id : "insert_new_data",
	})
    /*
    grid.inlineNav('#page-detail',{ 
			edit: false,
   		add: true,
   		addicon:"ui-icon-plus",
   		save: false,
   		cancel: false,
   		addParams : 
   		{
   			useFormatter : true,
   			position :"last",
   		},
		}
	)
	*/
}

function on_edit(ch)
{
	if (ch==true) {
		$("#insert_new_data").attr("hidden",true);
		$("#del_table-detail").attr('hidden',true);
	} else {
		$("#insert_new_data").attr("hidden",false);
		$("#del_table-detail").attr('hidden',false);
				
	}	
}

function insert_new_data()
{
	var grid = $("#table-detail"), i;
	$.ajax
	({
		type	:"POST",
		url	:window.location.origin+'/pricelist/import_selling_rate/insert_new_data',
		data	:
		{
			data_id : $("input[id='idForm']").val(),
		},
		success:function(response)
		{
			var data = $.parseJSON(response);
			if(data.status==1){
				var dataSatu = [{'id':data.id}];
				grid.jqGrid('addRowData',i,dataSatu,'last');
				grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
			}
		}
	})
}

function save_data_detail(rowid, cellname, value, iRow, iCol)
{
	var grid = $("#table-detail"), i;	
	var myData = grid.getRowData(rowid);
	$.ajax
	({
		type	:"POST",
		url	:window.location.origin+'/pricelist/import_selling_rate/save_data_detail',
		data	:
		{
			id		 : rowid,
			myData : myData,
		},
		success:function(response)
		{
			var data = $.parseJSON(response);
		}
	})
	/*
	if(rowid.substr(0,1)== 'j'){
		var oper = 'add';
	}
	else
	{
		var oper = 'edit';	
	}
	
	if(cellname=='unit_price' || cellname=='mchgs'){
		value = value.replace(/\ /g,'');
	}
	
	var DataDetail = 
	{
		oper : oper,
		id : rowid,
		data_id : $("input[id='idForm']").val(),
		kolom		: cellname,
		isi		: value,
	}
	
	$.ajax({
		type: "POST", 
		url : "<?php echo base_url('pricelist/import_selling_rate/manage_data_detail')?>",
		data: DataDetail,
		success:function(data)
		{
			$("#table-detail").jqGrid("setGridParam",  { datatype: 'json', } ).trigger("reloadGrid");
			return true;
		}
	})
	*/
}

function table_listing()
{
	var grid = $("#table-listing"), i;	
	grid.jqGrid(
	{ 
		url:"<?php echo base_url('pricelist/import_selling_rate/get_table_listing')?>",
		mtype : "post",
		datatype: "json",  
		postData: 
		{ 
			consignee_id: $("input[id='consigneeImportId']").val(),
		},
		colNames:
		[
			'Ref No','Consignee','Sales',
		],
    	colModel:
		[
			{name:'ref_no',index:'ref_no', align:"left", width:5, editable: true,},
			{name:'consignee',index:'consignee', align:"left", width:5, editable: true,},
			{name:'sales',index:'sales', align:"left", width:5, editable: true,},
		], 
		hiddengrid: false,
  		viewrecords: true,
      multiselect:true, 
		modal: true,
		rownumbers: true,
		rowNum: 16,
		top: 20,
		width: 835,
		height: 370,
		rowList:[10,20,30,40,50,60],
		gridview: true,
		caption: false,
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
			$("input[id='idForm']").val(rowid);
			get_data_form('pilih');
			$('#tabs-lading ul li:first a').trigger('click');	 			
		}
	}).navGrid('#page-listing',{ edit: false, add: false, del: false,})
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










