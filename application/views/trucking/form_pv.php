
<div id="main-page" style="font-size: 11px;">
	<div style="float: left; width: 320px; vertical-align: middle;">
		<div style="float: left; width: 90px; font-weight: bold; padding-top: 5px;">JOB NUMBER</div>
			<div style="float: left; width: 150px">
				<input type="text" id="kode_ref_cost" disabled="disabled" style="height:25px;" value="<?php echo $kode_ref ?>">
				<input id="spd_id_cost" value="<?php echo $id ?>" type="hidden">
			</div>
		</div>
	</div>
	<input type="button" style="width: 180; height: 30; float:right;" value="Print Voucher" onclick="print_data()">
	<div style="clear:both; height: 20px"></div>
	<div>
		<table id="table-pv"></table>
		<div id="pager-pv"></div>
	</div>
	</div>
<script type="text/javascript" >
$(document).ready(function(){
	table_pv();
})

if(!window.location.origin){
	window.location.origin=window.location.protocol+'//'+window.location.hostname+(window.location.port+':'+window.location.port)
}

function refresh_table(){
	$("#table-cost").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
	$("#table-discount").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
	$("#table-sc").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
}

function tarik_cost(){
	
	var spd_id = $('#spd_id_cost').val();
	var kode_ref = $( '#kode_ref_cost' ).val();
	var kode_refnya = kode_ref.substr(4,3);
	if (kode_refnya == '300'||kode_refnya == '600'||kode_refnya == '400' ) {
		var gridnya = $("#tabel_sp_detail_export"), i;	
		var destination = gridnya.jqGrid('getCell', spd_id,'destination');	
		var shipper = gridnya.jqGrid('getCell', spd_id,'note').replace(/\ /g,'').replace(/\./g,'').substr(0,10);
	}
	else 
	{
		var gridnya = $("#tabel_sp_detail"), i;
		var destination = gridnya.jqGrid('getCell', spd_id,'destination');	
		var shipper = gridnya.jqGrid('getCell', spd_id,'shipper');
	}
	
	var fcl = $('select[id="dropdownTypeOfTransaction"]').val();
	
	if(fcl =='FCL' || fcl == 'SF IMPORT FCL'){
		var qty = gridnya.jqGrid('getCell', spd_id,'no_pack_qty');
	}
	else 
	{
		var qty = gridnya.jqGrid('getCell', spd_id,'bl_meas');
	}
	
	$.ajax
	({
		type: "POST",
		url : window.location.origin+'/afg_c_payment_voucher/tarik_cost',
		data:  
		{ 
			spd 			: spd_id,
			kode_ref 	: $( '#kode_ref_cost' ).val(),
			shipper_id 	: gridnya.jqGrid('getCell', spd_id,'shipper'),
			//agent 		: gridnya.jqGrid('getCell', spd_id,'agent_code'),
			agent 		: $('select[id="dropdownAgent"]').val(),
			sales 		: gridnya.jqGrid('getCell', spd_id,'sales'),
			destination : destination,
			qty 			: qty,
			type 			: $('select[id="dropdownTypeOfTransaction"]').val(),
		},
		success: function(response)
		{
			$("#table-pv").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
		},
		error:function(XMLHttpRequest)
		{
			return false;
		}
	})
}


function table_pv(){
	var kode_ref = $( '#kode_ref_cost' ).val();
	var spd_id = $('#spd_id_cost').val();
	var kode_refnya = kode_ref.substr(4,3);
	if (kode_refnya == '300'||kode_refnya == '600'||kode_refnya == '400') {
		var gridnya = $("#tabel_sp_detail_export"), i;
		//var gridnya = $("#tabel_sp_detail"), i;	
		var q =  gridnya.jqGrid('getCell', spd_id,'bl_meas');
		var sales = gridnya.jqGrid('getCell', spd_id,'sales');
		var w = gridnya.jqGrid('getCell', spd_id,'weight');
		var wg = w/1000;
		if(wg>=q){
			qty = wg;
		} else {
			qty = q;
		}
	}
	else 
	{
		var gridnya = $("#tabel_sp_detail"), i;
		var q = gridnya.jqGrid('getCell', spd_id,'bl_meas');
		var sales = gridnya.jqGrid('getCell', spd_id,'sales');
		var w = gridnya.jqGrid('getCell', spd_id,'weight');
		var wg = w/1000;
		if(wg>=q){
			qty = wg;
		} else {
			qty = q;
		}
	}
	
	//console.log({w,wg,gridnya,kode_refnya,spd_id})
	
	var grid = $("#table-pv"), i;	
	var spd_id = $('#spd_id_cost').val();
	function currencycheck(value, colname) {
			if (value == "") 
				return [false,"Please enter currency"];
			else 
				return [true,""];
			}
	var destination = gridnya.jqGrid('getCell', spd_id,'destination');	
	if(destination ==''){
		var destination = $('#dropdownCountries').val();
	}
	grid.jqGrid(
	{ 
		url : window.location.origin+'/afg_c_payment_voucher/tampil_table_pv',
		mtype : "post",
		datatype: "json",
		sortname: "id",
		sortorder: "asc",
		postData: 
		{ 
			spd 			: spd_id,
			kode_ref 	: $( '#kode_ref_cost' ).val(),
			shipper_id 	: gridnya.jqGrid('getCell', spd_id,'shipper'),
			sales 		: sales,
			destination : destination,
			//qty : gridnya.jqGrid('getCell', spd_id,'bl_meas'),
			qty : qty,
		},
		colNames:
		[
			'',
			'Code#',
			'Item Service',
			'Quantity', 
			'Currency',
			'Price/Unit',
			'Price',		
			'Prep/Coll',
			'Payable To', 
			'Remarks', 
			'Grup',
		],
    	colModel:
		[
			{name:'spd',index:'spd', align:"center", width:40, editable: true, hidden: true},
			
			{name:'code_service',index:'code_service', align:"left", width:80, editable: true,
				edittype:"select",
				editoptions:{
						dataUrl : window.location.origin+'/afg_c_dropdown/dropdownService',
						dataInit: function (el) 
						{ 
							$(el).css({'min-width':'80px','max-width':'80px'}); 
						},
						/*
						dataEvents: 
						[{  type: 'focus',
                  	fn: function(e) {
                       	on_edit(true);
                   	}
               	}],
                */
                dataEvents: [{  
               	type: 'change',
                  fn: function(e) {
                  	//var rowid = grid.jqGrid('getGridParam', 'selrow');
                		//var code = grid.jqGrid('getCell',rowid,'code_service');
                       	$.ajax
								({
									type:"POST",
									url : window.location.origin+'/afg_c_dropdown/get_item_service_nya',
									data: { typeservice_code: this.value },
									success:function(response)
									{
										var data = $.parseJSON(response);
										var rowid = grid.jqGrid('getGridParam', 'selrow');
										grid.jqGrid('setCell',rowid,'item_service',data.typeservice_name);
									},
								})
                  }
                }]
				},
			},
			{name:'item_service',index:'item_service', align:"left", width:100, editable: true, 
				editoptions:{
					dataInit: function(element) {
        				$(element).css({ "text-transform": "uppercase"})
      			},
      			/*
      			dataEvents: 
					[{  type: 'focus',
                  	fn: function(e) {
                       	on_edit(true);
                   	}
               }]
              
               dataEvents: [{  
               	type: 'focus',
                  fn: function(e) {
                  	var rowid = grid.jqGrid('getGridParam', 'selrow');
                		var code = grid.jqGrid('getCell',rowid,'code_service');
                       	$.ajax
								({
									type:"POST",
									url : window.location.origin+'/afg_c_dropdown/get_item_service_nya',
									data: { typeservice_code: code },
									success:function(response)
									{
										var data = $.parseJSON(response);
										var rowid = grid.jqGrid('getGridParam', 'selrow');
										grid.jqGrid('setCell',rowid,'item_service',data.typeservice_name);
									},
								})
                  }
                }]
                */
				},
				cellattr: function(rowId, cellValue, rawObject, cm, rdata) {
					return 'style="text-transform: uppercase"';
				}
			},
			{name:'qty',index:'qty', align:"center", width:60, 
				editable: true, 
				editoptions: {
    				dataInit : function (e) {
						$(e).css({'max-width':'60px','min-width':'60px',"text-align":"center"})
        				$(e).attr({ "onChange":"format_number(this,4)","onKeyUp":"format_number(this,4)"});
					},
					dataEvents: 
					[{  type: 'focus',
                  	fn: function(e) {
                       	on_edit(true);
                   	}
               }]
				},
				formatter: 'number',
    			formatoptions: { decimalSeparator: ".", thousandsSeparator: " ", decimalPlaces: 4, defaultValue: '' }
			},
			{name:'currency',index:'currency', align:"center", width:70, editable: true, 
				edittype:"select",
				formatter:'select', 
				editoptions:{
					value:" : ;USD:USD;RP.:RP.",
					dataInit : function (e) {
						$(e).css({'max-width':'70px','min-width':'70px',"text-align":"center"})
					},
					dataEvents: 
					[{  type: 'focus',
                  	fn: function(e) {
                       	on_edit(true);
                   	}
               }]
				},
				editrules:{ required: true}
			},
			{name:'unit_price',index:'unit_price', align:"right", width:60, editable: true, 
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
				},
				formatter: 'number',
    			formatoptions: { decimalSeparator: ".", thousandsSeparator: " ", decimalPlaces: 2, defaultValue: '' }
			},
			{name:'price',index:'price', align:"right", width:70,
				editoptions:{
					disabled: true,
				},
				formatter: "number",
    			formatoptions: { decimalSeparator: ".", thousandsSeparator: " ", decimalPlaces: 2, defaultValue: '' },
			},			
			{name:'prep_coll',index:'prep_coll', align:"center", width:70, editable: true, 
				 edittype:"select",
				 formatter:'select', 
				 editoptions:{
				 	value:" : ;COLLECT:COLLECT;PREPAID:PREPAID",
				 	dataInit : function (e) {
						$(e).css({'max-width':'70px','min-width':'70px',"text-align":"center"})
					},
				 	dataEvents: 
					[{  type: 'focus',
                  	fn: function(e) {
                  		on_edit(true);
                       	var rowid = grid.jqGrid('getGridParam', 'selrow');
                       	var qty = grid.jqGrid('getCell',rowid,'qty');
                       	var unit_price = grid.jqGrid('getCell',rowid,'unit_price');
                        grid.jqGrid('setCell',rowid,'price',eval(qty) * eval(unit_price))
                   	}
               }]
				 }
			},
			{name:'payable_to',index:'payable_to', align:"left", width:80, editable: true, 
			//	edittype:"select",
			//	editoptions:{dataUrl:'afg_c_dropdown/dropdownPayable'}				
			//	editoptions: { 
			//		dataInit: function(el,em,kol) 
			//		{ 
			//			$(el).attr('onKeyup','live_search(this)').css('text-transform','uppercase'); 
					//	$(el).autocomplete({
					//		source: availableTags
					//	})
			//		}
			//	},
				//edittype:"select",
				editoptions:{
						//dataUrl : window.location.origin+'/afg_c_dropdown/dropdown_shipper/shipper_code',
					dataInit: function (el) { 
						$(el).css('text-transform','uppercase'); 
					}
				},
				cellattr: function(rowId, cellValue, rawObject, cm, rdata) {
    				return 'style="text-transform: uppercase"';
				},
			},
			{name:'remark',index:'remark', align:"left", width:70, editable: true, 
				edittype:"textarea",
				editoptions:{
					cols:"40", rows:"1",
					dataInit: function (el) 
					{ 
						$(el).css('max-width','70px'); 
					},
				}
			},
			{name:'Grup',index:'Grup', align:"center", width:40, editable: true,
				editoptions:{
					dataInit: function (el) { 
						$(el).css('text-transform','uppercase'); 
					},
					dataEvents: 
					[{  
						type: 'keydown', 
            		fn: function(e) { 
                		var key = e.charCode || e.keyCode;
                		if (key == 13)//enter
                		{
                    		$("#btnAddCost").click();
                		}
            		}
            	}]
				},
				formatter: function(el,em,ek){
					if(el==null){
						return ""
					} else {
						return el.toUpperCase()
					}
				}
			},
		], 
		shrinkToFit: false,
		loadonce:false,
  		viewrecords: true,
		multiselect:true, 
		modal: true,
		rownumbers: true,
		rowNum: 15,
		gridview: true,
		width: 830,
		height: 235,
		rowList:[15,30,45],
		caption:false,
		editurl : window.location.origin+'/afg_c_cost/manage_table_satu',
		cellEdit:true,
		cellsubmit:'clientArray',
		pager:'#pager-pv',

	/*
		afterSaveCell: function(rowid, cellname, value, iRow, iCol) {
			save_tbl_satu(rowid,cellname,value)
    	}, 
   */
   
    	afterSaveCell: function(rowid, cellname, value, iRow, iCol) {
			saving_cost(rowid, cellname, value, iRow, iCol);
			if(event.keyCode==13) 
			{
				grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
			}
    	},
    	   			
		loadComplete: function(data) {
			$("#cb_"+grid[0].id).hide();
			on_edit(false)
			var ids = grid.jqGrid('getDataIDs');
			if (ids.length>=1) {
    			grid.jqGrid('editCell',ids.length,3,true);
    		}
    	},   		
	})
	grid.jqGrid('navGrid','#pager-tbl-cost', { edit: false, add: false, del: true, refresh: true, search: false},
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
	
	grid.navButtonAdd('#pager-tbl-cost',{ 
			caption:"", 
			buttonicon:"ui-icon-plus", 
			onClickButton: insert_data_cost, 
			position: "last", 
			title:"Insert Data Table Cost", 
			cursor: "pointer",
			id:"btnAddCost",
	})
	
	grid.navButtonAdd('#pager-tbl-cost',{ 
			caption:"", 
			buttonicon:"ui-icon-disk", 
			onClickButton: tarik_cost, 
			position: "last", 
			title:"Tarik Data Table Cost", 
			cursor: "pointer"
	})
	
}


function on_edit(ch){
	if (ch==true) {
		$("tr > td[title=\"Insert Data Table Cost\"]").attr("hidden",true);
	} else {
		$("tr > td[title=\"Insert Data Table Cost\"]").attr("hidden",false);
	}	
}

function insert_data_cost(){
	var grid = $("#table-cost"), i;	
	$.ajax
	({
		type:"POST",
		url : window.location.origin+'/afg_c_cost/manage_table_satu',
		data:
		{
			spd : $('#spd_id_cost').val(),
			kode_ref : $( '#kode_ref_cost' ).val(),
			oper : 'add',
		},
		success:function(response)
		{
			var data = $.parseJSON(response);
			grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
    		return [true];
		}
	})
}

function save_tbl_satu(rowid,cellname,value){
	var grid = $("#table-pv"), i;
	var price = grid.jqGrid('getCell', rowid,'price')
	var qty = grid.jqGrid('getCell', rowid,'qty')
	$.ajax
	({
		type:"POST",
		url : window.location.origin+'/afg_c_cost/manage_table_satu',
		data:
		{
			spd : $('#spd_id_cost').val(),
			kode_ref : $( '#kode_ref_cost' ).val(),
			qty : qty,
			price : price,
			id : rowid,
			kolom : cellname,
			isi : value,
			oper : 'edit',
		},
		success:function(response)
		{
			var data = $.parseJSON(response);
			if(data.field=='code_service'){
					grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
			}
			if(data.field=='qty'||data.field=='unit_price'){
					grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
			}
    		return [true];
		}
	})
}


	function print_data(){
	
	$( "#Form-Print-PV" ).css('background-color', '#eee');
	$( "#Form-Print-PV" ).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
	$("#Form-Print-PV").dialog(
	{
		position: 'top',
		autoOpen: true, 
		resizable: false, 
		modal: true,
		width: 990,
		height:"auto",
		title: "PAYMENT REQUEST ",
		close: function() 
		{	
			
		}
	});

	
	$('#Form-Print-PV').html('<center><object data="'+window.location.origin+'/afg_c_payment_voucher/print_data'+'" width="960px" height="600px" type="application/pdf"></center>');

}



</script>

















