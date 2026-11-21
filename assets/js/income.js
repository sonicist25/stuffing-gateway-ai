<script type="text/javascript">
function update_sales()
{
	sales : $('#sales').val(),
	$.ajax
	({
		type	:"POST",
		url	:window.location.origin+'/afg_c_income/update_sales',
		data	:
		{
					sp_id : $('#spd_id_income').val(),
					kode_ref : $('#kode_ref_income').val(),
					sales : $('#sales').val(),
		},
		success:function(response)
		{
			var data = $.parseJSON(response);
			if(data.status==1){
			swal({
				position: 'top-end',
				title: "Updated!",
				timer: 1000
				});					
			}
		}
	})
}
$(function () {
	var tarik_insentiv_per_doc = $('#TRK-DOC').attr('checked');
	if(tarik_insentiv_per_doc=='checked')
		{var tarik_insentiv_per_doc = 'true';}
		else
		{var tarik_insentiv_per_doc = 'false';}
	var intensiv_tidak_diproses = $('#TDK-PROSES').attr('checked');
	if(intensiv_tidak_diproses=='checked')
		{var intensiv_tidak_diproses = 'true';}
		else
		{var intensiv_tidak_diproses = 'false';}
	$("#kurs").blur(function(){
	kurs : $('#kurs').val(),
	
	$.ajax
	({
		type:"POST",
		url : window.location.origin+'/afg_c_income/manage_header_income',
		data:
			{
			kode_ref : $('#kode_ref_income').val(),
			tarik_insentiv_per_doc: tarik_insentiv_per_doc,
			intensiv_tidak_diproses: intensiv_tidak_diproses,
			kurs:$( '#kurs' ).val(),
			},
		});
	});
	$('#TDK-PROSES').val('<?= $Intensiv_tidak_diproses ?>');
	$('#TRK-DOC').val('<?= $Tarik_insentiv_per_Doc ?>');	
	
	if($('#TDK-PROSES').val()=='TRUE'){
		$('#TDK-PROSES').attr('checked',true);
		$('#TRK-DOC').attr('disabled',true);
		$('#pager-frm-agent_left').attr('hidden',true);
		$('#pager-frm-coloader_left').attr('hidden',true);	
	}
	else
	{
		$('#TDK-PROSES').attr('checked',false);
		$('#TRK-DOC').attr('disabled',false);
		$('#pager-frm-agent_left').attr('hidden',false);
		$('#pager-frm-coloader_left').attr('hidden',false);
	}
		
	if($('#TRK-DOC').val()=='TRUE'){
		$('#TRK-DOC').attr('checked',true);		
	}
	else
	{
		$('#TRK-DOC').attr('checked',false);
	}
	
	
	$("#TDK-PROSES").live("change",function(){
		var mode = $("#TDK-PROSES").attr('checked');
		var spd_id = $('#spd_id_income').val();
		if (mode=='checked') {mode = true;} else {mode = false;}
		if(mode==true){
			$.ajax
			({
				type:"POST",
				url:"<?php echo site_url('afg_c_income/clear_agent_coloader'); ?>",
				data:
				{
					kode_ref : $('#kode_ref_income').val(),
					id_sd : spd_id,
				},
				success:function(response)
				{
					var data = $.parseJSON(response);
					$('#TRK-DOC').attr('disabled',true);
					$("#table-frm-agent").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
					$('#pager-frm-agent_left').attr('hidden',true);
					$("#table-frm-coloader").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
					$('#pager-frm-coloader_left').attr('hidden',true);
					save_data();
				}
			})
		} 
		else 
		{
			$('#TRK-DOC').attr('disabled',false);
			$('#pager-frm-agent_left').attr('hidden',false);
			$('#pager-frm-coloader_left').attr('hidden',false);
			save_data();
		}	
	})
	
	$("#TRK-DOC").live("change",function(){
		var mode = $("#TRK-DOC").attr('checked');
		var spd_id = $('#spd_id_income').val();
		if (mode=='checked') {mode = true;} else {mode = false;}
		if(mode==true)
		{
			$.ajax
			({
				type:"POST",
				url:"<?php echo site_url('afg_c_income/update_agent_coloader'); ?>",
				data:
				{
					id_sd : spd_id,
					kode_ref : $('#kode_ref_income').val(),
					qty:	'1.0000',
				},
				success:function(response)
				{
					var data = $.parseJSON(response);
					$("#table-frm-agent").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");	
					save_data();					
				}
			})
		} 
		else 
		{
			$.ajax
			({
				type:"POST",
				url:"<?php echo site_url('afg_c_income/update_agent_coloader'); ?>",
				data:
				{
					id_sd : spd_id,
					kode_ref : $('#kode_ref_income').val(),
					qty : $("#tabel_sp_detail").jqGrid('getCell', spd_id,'bl_meas'),
				},
				success:function(response)
				{
					var data = $.parseJSON(response);
					$("#table-frm-agent").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
					$("#table-frm-coloader").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
					save_data();
				}
			})
		}	
	})
	
	table_from_selling();	
	table_from_agent();
	table_from_coloader();
})

if(!window.location.origin)
{
	window.location.origin=window.location.protocol+'//'+window.location.hostname+(window.location.port+':'+window.location.port)
}

function table_from_selling()
{
	var grid = $("#table-frm-selling"), i;	
	var kode_ref = $('#kode_ref_income').val();
	var spd_id = $('#spd_id_income').val();
	var kode_refnya = kode_ref.substr(4,3);
	if (kode_refnya == '300'||kode_refnya == '301'||kode_refnya == '302'||kode_refnya == '600'||kode_refnya == '800'||kode_refnya == '400') {
		var gridnya = $("#tabel_sp_detail"), i;	
		var destination = gridnya.jqGrid('getCell', spd_id,'destination');	
		var shipper = gridnya.jqGrid('getCell', spd_id,'shipper_print');
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
		var qty = gridnya.jqGrid('getCell', spd_id,'weight');
	}
	
	var lock = $('#lock').val();
	if (lock==1) {
		get_cell_edit = false;
		get_cell_del = false;
	} else {
		get_cell_edit = true;
		get_cell_del = true;
	}

	grid.jqGrid({ 
		url	:	window.location.origin+'/afg_c_income/tampil_selling_price',
		mtype : "post",
		datatype: "json",
		sortname: "id",
		sortorder: "asc",
		postData: { 
			spd : spd_id,
			kode_ref : $('#kode_ref_income').val(),
			shipper_id : $("#tabel_sp_detail").jqGrid('getCell', spd_id,'shipper'),
			sales : $('#sales').val(),
			destination : destination,
			qty : qty,
		},
		ajaxSelectOptions: {type: "GET"}, 
		colNames:
		[
			'',
			'kode_ref',
			'mchgs',
			'valid',
			'Container',
			'Code#',
			'Item Service',
			'Quantity', 
			'Price/Unit',
			'Price',
			'Currency',
			'Prep/Coll',
			'Receive From', 
			'Remarks', 
			'Agent#', 
			'Ship Co',
			'Grup',
			'PPN',
			'INV / DB',
			'Ctk Rt Cfm ',
			'Created By',
			'Created At',
			'Updated By',
			'Updated At',
		],
    	colModel:
		[
			{name:'sp_detail_id',index:'sp_detail_id', align:"left", width:30, editable: true, hidden: true,

			},
			{name:'kode_ref',index:'kode_ref', align:"left", width:100, editable: true, hidden: true,

			},
			{name:'mchgs', index:'mchgs', align:"left", width:10, editable: true, hidden: true},
			
			{name:'valid', index:'valid', align:"left", width:10, editable: true, hidden: true},
			
			{name:'container',index:'container', align:"center", width:60, 
				editable: true, 
				edittype:"select",
				editoptions:{
					dataUrl	: window.location.origin+'/afg_c_dropdown/dropdownContainer',
					dataInit: function (el) 
					{ 
						$(el).css({'max-width':'60px'}); 
					},
					dataEvents: 
					[{  type: 'change',
                  	fn: function(e) {
                       	on_edit(true);
                   	}
               }]
				},
			},
			{name:'code_service',index:'code_service', align:"left", width:50, editable: true, hidden: false,
				editrules:{edithidden:true}, 
				edittype:"select",
				editoptions:{
					dataUrl:window.location.origin+'/afg_c_dropdown/dropdownService',
					dataInit: function (el) 
					{ 
						$(el).css({'min-width':'45px','max-width':'45px'}); 
					},
					dataEvents: [
                  {  type: 'change',
                  	fn: function(e) {
                  		on_edit(true);
                       	var val = this.value;
                       	$.ajax
								({
									type:"POST",
									url:window.location.origin+'/afg_c_dropdown/item_service_nya',
									data:
									{
										code_service: val,
									},
									success:function(response)
									{
										var data = $.parseJSON(response);
										var rowid = grid.jqGrid('getGridParam', 'selrow');
										grid.jqGrid('setCell',rowid,'item_service',data);	
									},
								})
                      }
                   }
                ]
				}
			},
			{name:'item_service',index:'item_service', align:"left", width:150, editable: true,
				editoptions:{
					dataInit: function(element) {
        				$(element).css({ "text-transform": "uppercase"})
      			},
      			dataEvents: 
					[{  type: 'focus',
                  	fn: function(e) {
                       	on_edit(true);
                   	}
               }]
				},
				cellattr: function(rowId, cellValue, rawObject, cm, rdata) {
					return 'style="text-transform: uppercase"';
				}
			},
			{name:'qty',index:'qty', align:"center", width:60, 
				editable: true, 
				formatter : 'number',
				formatoptions: { decimalSeparator: ".", thousandsSeparator: " ", decimalPlaces: 4, defaultValue: '' },
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
			{name:'price',index:'price', align:"right", width:70, 
				editoptions:{
					disabled: true,
				},
				formatter: "number",
    			formatoptions: { decimalSeparator: ".", thousandsSeparator: " ", decimalPlaces: 2, defaultValue: '' },
			},
			{name:'currency',index:'currency', align:"center", width:50, editable: true, 
				edittype:"select",
				formatter:'select', 
				editoptions:{
					value:" : ;USD:USD;RP.:RP.",
					dataInit: function (el) 
					{ 
						$(el).css({'min-width':'50px','max-width':'50px'}); 
					},
					dataEvents: 
					[{  type: 'focus',
                  	fn: function(e) {
                  		on_edit(true);
                       	var rowid = grid.jqGrid('getGridParam', 'selrow');
                       	var qty = grid.jqGrid('getCell',rowid,'qty');
                       	var unit_price = grid.jqGrid('getCell',rowid,'unit_price');
                       	var price = eval(qty) * eval(unit_price);
                        grid.jqGrid('setCell',rowid,'price',price);
                        $.ajax
								({
									type : "POST",
									url  : window.location.origin+'/afg_c_income/update_price',
									data : { id : rowid, price : price, table:"inf_income_selling_price"},
									success:function(response)
									{
										var data = $.parseJSON(response);
										if (data.status==1) {
										}
										else 
										{
											show_messages("No Kode Ref "+dPost["kode_ref"]+" and item = "+dPost["item"]+" finance_item table");
										}
									}
								})
                   	}
               }]
				}
			},
			{name:'prep_coll',index:'prep_coll', align:"center", width:55, 
				 editable: true, 
				 edittype:"select",
				 formatter:'select', 
				 editoptions:{
				 	value:" : ;COLLECT:COLLECT;PREPAID:PREPAID",
				 	dataInit : function (el) {
						$(el).css({'min-width':'45px','max-width':'45px'}); 
					},
					dataEvents: [
                  {  type: 'change',
                  	fn: function(e) {
                  		on_edit(true);
                       	if(this.value==='COLLECT'){
                       		var rowid = grid.jqGrid('getGridParam', 'selrow');
                       		grid.jqGrid('setCell',rowid,'invoice_db','DEBIT NOTE'); 
                       	} else {
                       		var rowid = grid.jqGrid('getGridParam', 'selrow');
                       		grid.jqGrid('setCell',rowid,'invoice_db','INVOICE');
                       	}
                      }
                   }
                ]
				 }
			},
			{name:'receive_from',index:'receive_from', align:"left", width:80, editable: true, 
				editoptions:{
					dataInit: function (el) 
					{ 
						$(el).css('text-transform','uppercase'); 
					},
					dataEvents: 
					[{  type: 'focus',
                  	fn: function(e) {
                       	on_edit(true);
                   	}
               }]
				},
				cellattr: function(rowId, cellValue, rawObject, cm, rdata) {
    				return 'style="text-transform: uppercase"';
				},
			},
			{name:'remark',index:'remark', align:"left", width:80, editable: true, 
				editoptions:{
					dataInit: function (el) 
					{ 
						$(el).css('text-transform','uppercase'); 
					},
					dataEvents: 
					[{  type: 'focus',
                  	fn: function(e) {
                       	on_edit(true);
                   	}
               }]
				},
				cellattr: function(rowId, cellValue, rawObject, cm, rdata) {
    				return 'style="text-transform: uppercase"';
				},
			},
			{name:'agent',index:'agent', align:"left", width:80, editable: true, 
				edittype:"select",
				editoptions:{
					dataUrl:window.location.origin+'/afg_c_dropdown/dropdownAgent',
					dataInit: function (el) 
					{ 
						$(el).css({'min-width':'80px','max-width':'80px'});  
					},
					dataEvents: 
					[{  type: 'change',
                  	fn: function(e) {
                       	on_edit(true);
                   	}
               }]
				},
				cellattr: function(rowId, cellValue, rawObject, cm, rdata) {
    				return 'style="text-transform: uppercase"';
				},
			},
			{name:'ship_co',index:'ship_co', align:"left", width:80, editable: true, 
				edittype:"select",
				editoptions:{
					dataUrl:window.location.origin+'/afg_c_dropdown/dropdownShipCo',
					dataInit: function (el) 
					{ 
						$(el).css({'min-width':'80px','max-width':'80px'});  
					},
					dataEvents: 
					[{  type: 'change',
                  	fn: function(e) {
                       	on_edit(true);
                   	}
               }]
				},
				cellattr: function(rowId, cellValue, rawObject, cm, rdata) {
    				return 'style="text-transform: uppercase"';
				},
			},
			{name:'g',index:'g', align:"center", width:40, editable: true,
				editoptions:{
					dataInit: function (el) { 
						$(el).css('text-transform','uppercase'); 
					},
					dataEvents: 
					[{  type: 'focus',
                  	fn: function(e) {
                       	on_edit(true);
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
			{name:'ppn',index:'ppn', align:"center", width:50, 
				editable: true, 
				edittype:"select",
				editoptions:{
					value:" : ;1%:1%;10%:10%",
					dataInit: function (el) 
					{ 
						$(el).css({'min-width':'50px','max-width':'50px'}); 
						 
					},
					dataEvents: 
					[{ type	: 'change',
                  fn  : function(e) 
                  {
                  	on_edit(true);
                  	var rowid = grid.jqGrid('getGridParam', 'selrow')
                  	var dPost = 
                  	{
								id	 		: rowid,
								kode_ref : $("#kode_ref_income").val(),
								item		: grid.jqGrid('getCell',rowid,'item_service'),
								ppn		: grid.jqGrid('getCell',rowid,'ppn')
							}
                  	$.ajax
							({
								type : "POST",
								url  : window.location.origin+'/afg_c_income/update_ppn',
								data : dPost,
								success:function(response)
								{
									var data = $.parseJSON(response);
									if (data.status==1) {
										//show_messages("PPN Updated")
									}	
									else 
									{
										//show_messages("No Kode Ref "+dPost["kode_ref"]+" and item = "+dPost["item"]+" finance_item table");
									}
								},
							})
                  }
               }],
				},
				formatter:'select',
			},
			{name:'invoice_db',index:'invoice_db', align:"center", width:60, editable: true, 
				edittype:"select",
				editoptions:{
					value:" : ;INVOICE:INVOICE;DEBIT NOTE:DEBIT NOTE",
					dataInit: function (el) 
					{ 
						$(el).css({'min-width':'50px','max-width':'50px'}); 
						 
					},
					dataEvents: 
					[{  type: 'change',
                  	fn: function(e) {
                       	on_edit(true);
                       	//alert("")
                   	}
               }]
				},
				formatter:'select', 
			},
			{name:'cetak_rt_cfm',index:'cetak_rt_cfm', align:"center", width:60, editable: true, 
				edittype:"checkbox", 
				editoptions:{
					value:"TRUE:FALSE",
					dataEvents: 
					[{  
						type: 'keydown', 
            		fn: function(e,n,v) { 
                		var key = e.charCode || e.keyCode;
                		if (key == 13)//enter
                		{
                    		$("#btnAddNewIncome").click();
                		}
            		}
            	}]
				},
				formatter: "checkbox",
				formatoptions: {
					disabled : true
				},
				
			},
			{name:'created_by',index:'created_by', width:200, hidden: false,},
  			{name:'created_at',index:'update_at', width:200, hidden: false,},
  			{name:'updated_by',index:'updated_by', width:200, hidden: false,},
  			{name:'updated_at',index:'updated_at', width:200, hidden: false,},
		], 
		width	: 1273,
		height	: 'auto',
		shrinkToFit	: false,
		loadonce	:false,
  		viewrecords	: true,
		multiselect:true,
		modal: true,
		rownumbers: true,
		rowNum: 1000,
		gridview: true,
		rowList:[-0],
		caption:"Get From Selling Price",
		editurl	:	window.location.origin+'/afg_c_income/manage_income',
		cellEdit:true,
		cellsubmit:'clientArray',
		pager:'#pager-frm-selling',
		   
      afterSaveCell: function(rowid, cellname, value, iRow, iCol) {
			saving_income(rowid, cellname, value, iRow, iCol);
			if(event.keyCode===13) 
			{
				grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
			}
    	},
		
		loadComplete: function(data) {
			on_edit(false)
			$("#cb_"+grid[0].id).hide();
			$(".ui-jqgrid-titlebar-close",$("#table-frm-selling")[0].grid.cDiv).hide();
			
			var ids = grid.jqGrid('getDataIDs');
			for (var i = 0; i < ids.length; i++) {
   			 var rowId = ids[i];
    		}
    		//$("input#jqg_table-frm-selling_"+rowId).focus()
    		
    		if (ids.length>=1) {
    			grid.jqGrid('editCell',ids.length,6,true);
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
    			//cbs.attr("disabled", true);  
    			//selected.attr("disabled", false);
    			on_edit(true) 		
     		}
        	else
        	{
        		var cbs = $("tr.jqgrow > td > input.cbox:disabled", grid[0]);  		
    			//cbs.attr("disabled", false); 
    			on_edit(false)      			
        	}
		},
	})
	
	grid.jqGrid('navGrid','#pager-frm-selling', { edit: false, add: false, del: true, refresh: true, <?php echo 'search: false' ?>},{ },{ },
     	{	
     	
     		height: "auto", width: "auto",
     			serializeDelData: function (postdata) {
     				return {
    					id: postdata.id, 
    					oper : postdata.oper, 
    					kolom : grid.jqGrid('getCell',postdata.id,'code_service'),
    					kode_ref : $('#kode_ref_income').val(),
    				};
				},
     			afterSubmit: function () {
    				grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
    				return [true];
				},
		},
      { },
     	{ }
	)
	grid.jqGrid('navButtonAdd','#pager-frm-selling',{ 
			caption:"", 
			buttonicon:"ui-icon-plus", 
			onClickButton: insert_data_selling_price, 
			position: "last", 
			title:"Insert Data Selling Price", 
			cursor: "pointer",
			id:"btnAddNewIncome",
	})
	
	grid.navButtonAdd('#pager-frm-selling',{ 
			caption:"", 
			buttonicon:"ui-icon-disk", 
			onClickButton: tarik_data_selling_price, 
			position: "last", 
			title:"Tarik Data Selling Price", 
			cursor: "pointer",
			id:"btnTrkSelling",
	})
	
	grid.navButtonAdd('#pager-frm-selling',{ 
			caption:"", 
			buttonicon:"ui-icon-newwin", 
			onClickButton: tarik_data_pajak, 
			position: "last", 
			title:"Tarik Data Pajak", 
			cursor: "pointer",
			id:"btnTrkPjk",
	})
 	var div = $(".ui-jqgrid-bdiv");
 	var nx = div.length-1;
 	var divx = div[nx];
 	$(divx).css({'min-height':'100px'})
 	//console.log(divx)
}


function tarik_data_pajak(){
	var grid = $("#table-frm-selling"), i;	
	var myData = grid.jqGrid('getRowData');
	if (myData.length>=1) {
		$.post(window.location.origin+'/afg_c_income/tarik_data_pajak' ,  {myData},
			function(response) {
			 var data = $.parseJSON(response);
			 grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
		})
	}else {
		show_messages("Selling Price Income Empty")
	}
	
	/*
	
	//alert(group.length)
	
	/*
	for(var i=0;i<num.length;i++) {
   		grid.jqGrid('addRowData', i,[{"g":num[i]}],'last');
  	}
	
	
	//var myRow = [{"g"]
	if(num.length>0) {
		for(var i=0;i<num.length;i++) {
   		grid.jqGrid('addRowData', i,[{"g":num[i]}],'last');
  		}
 	}
	
	/*
	$.post(window.location.origin+'/afg_c_income/tarik_data_pajak' ,  {"id":num},
		function(response)
		{
			var data = $.parseJSON(response);
			//grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
		}
	)
	
	/*
	var grid = $("#table-frm-selling"), i;	
	var id = $('#spd_id_income').val();
	var kode_ref = $('#kode_ref_income').val();	
	
	$.post(window.location.origin+'/afg_c_income/tarik_data_pajak' ,  {"id":id,'kode_ref':kode_ref},
		function(response)
		{
			var data = $.parseJSON(response);
			grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
		}
	)
	*/
}

function insert_table(ppn,kode_ref) {
	$.post(window.location.origin+'/afg_c_income/insert_table' ,  {"ppn":ppn,"kode_ref":kode_ref},
		function(response)
		{
			
		}
	)
}

function insert_table_sepuluh(ppn,kode_ref) {
	$.post(window.location.origin+'/afg_c_income/insert_table_sepuluh' ,  {"ppn":ppn,"kode_ref":kode_ref},
		function(response)
		{
			
		}
	)
}

function on_edit(ch)
{
	/*
	if (ch==true) {
		$("tr > td[title=\"Insert Data Selling Price\"]").attr("hidden",true);
	} else {
		$("tr > td[title=\"Insert Data Selling Price\"]").attr("hidden",false);
	}	
	*/
}

function saving_income(rowid, cellname, value, iRow, iCol)
{
	var grid = $("#table-frm-selling"), i;	
	var myData = grid.getRowData(rowid);
	console.log(myData);
	console.log(grid)
	console.log(rowid);
	$.ajax
	({
		type	:"POST",
		url	:window.location.origin+'/afg_c_income/saving_income',
		data	:
		{
			id		 : rowid,
			myData  : myData,
		},
		success:function(response)
		{
			var data = $.parseJSON(response);
		}
	})
}

function update_data_selling_price()
{
	var grid = $("#table-frm-selling"), i;	
	var myData = grid.jqGrid('getRowData');
	var id = grid.jqGrid('getDataIDs');
	$.ajax
	({
		type	:"POST",
		url	:window.location.origin+'/afg_c_income/insert_data_selling',
		data	:
		{
			myData : myData,
			id		 : id,
		},
		success:function(response)
		{
			var data = $.parseJSON(response);
			if(data.status==1){
				grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
			}
		}
	})
}

function insert_data_selling_price()
{
	var grid = $("#table-frm-selling"), i;
	$.ajax
	({
		type	:"POST",
		url	:window.location.origin+'/afg_c_income/insert_data_selling_price',
		data	:
		{
			sp_detail_id : $('#spd_id_income').val(),
			kode_ref : $('#kode_ref_income').val(),
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

function tarik_data_selling_price()
{
	
	var grid = $("#table-frm-selling"), i;	
	var spd_id = $('#spd_id_income').val();
	var kode_ref = $('#kode_ref_income').val();
	var kurs = $('#kurs').val();
	var kode_refnya = kode_ref.substr(4,3);
	if (kode_refnya == '300'||kode_refnya == '301'||kode_refnya == '302'||kode_refnya == '600'||kode_refnya == '800'||kode_refnya == '400') {
		var gridnya = $("#tabel_sp_detail_export"), i;	
		var destination = gridnya.jqGrid('getCell', spd_id,'destination');	
		//var shipper = gridnya.jqGrid('getCell', spd_id,'shipper_import_code');
		var shipper = gridnya.jqGrid('getCell', spd_id,'shipper');
		var sales = $('#sales').val();
	}
	else 
	{
		var gridnya = $("#tabel_sp_detail"), i;
		var destination = gridnya.jqGrid('getCell', spd_id,'destination');	
		var shipper = gridnya.jqGrid('getCell', spd_id,'shipper');
		var sales = $('#sales').val();
	}
	
	if(destination ==''){
		var destination = $('#dropdownCountries').val();
	}
	
	var fcl = $('select[id="dropdownTypeOfTransaction"]').val(); 
	var berat = (gridnya.jqGrid('getCell', spd_id,'weight')*0.001);
	if(fcl =='FCL' || fcl == 'SF IMPORT FCL'){
		var qty = gridnya.jqGrid('getCell', spd_id,'no_pack_qty');
	}
	else if ( berat >= gridnya.jqGrid('getCell', spd_id,'bl_meas'))
	{
		var qty = berat;
	}
	else
	{
          var qty = gridnya.jqGrid('getCell', spd_id,'bl_meas');
    }
	
	
	/*tambahan*/
	
	//if (kode_refnya == '300'||kode_refnya == '600'||kode_refnya == '800') sales = get_id(sales,udata);
	
	
	$.ajax
	({
		type: "POST",
		url : window.location.origin+'/afg_c_income/tarik_data_selling_price',
		data:  
		{ 
			spd : spd_id,
			kode_ref : kode_ref,
			shipper_id : shipper,
			sales : sales,
			destination : destination,
			valid : $("#txtClosingDate").val(),
			qty : qty,
			container : $("#txtTOC2").val(),
			type : fcl,
			kurs : kurs,
		},
		success: function(XMLHttpRequest)
		{
			$("#table-frm-selling").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
		},
		error:function(XMLHttpRequest)
		{
			return false;
		}
	})
}

function save_cell_selling_perice(rowid,cellname,value)
{
	var grid = $("#table-frm-selling"), i;
	$.ajax
	({
		type	:	"POST",
		url	:	window.location.origin+'/afg_c_income/manage_income',
		data:
		{
			spd : $('#spd_id_income').val(),
			kode_ref : $('#kode_ref_income').val(),
			id : rowid,
			qty : grid.jqGrid('getCell', rowid,'qty'),
			mchgs : grid.jqGrid('getCell', rowid,'mchgs'),
			agent: grid.jqGrid('getCell', rowid,'agent'),
			unit_price: grid.jqGrid('getCell', rowid,'unit_price'),
			kolom : cellname,
			isi : value,
			oper : 'edit',
		},
		success:function(response)
		{
			var data = $.parseJSON(response);

		}
	})
}

function table_from_agent()
{
	var spd_id = $('#spd_id_income').val();	
	var grid = $("#table-frm-agent"), i;
	var code = 	$('#txtRefno2').val();
	if (code.substr(4,3)=='300'||code.substr(4,3)=='301'||code.substr(4,3)=='302'||code.substr(4,3)=='400'||code.substr(4,3)=='600'||code.substr(4,3)=='800') 
	{
		var gridnya = $("#tabel_sp_detail_export"), i;
	}
	else 
	{
		var gridnya = $("#tabel_sp_detail"), i;
	}
	
	var trk_doc = $('#TRK-DOC').attr('checked');
	
	if (trk_doc=='checked') { 
		trk_doc=true; 
		qty = '1.0000';
	} else {
		trk_doc=false;
		qty = gridnya.jqGrid('getCell', spd_id,'bl_meas')
	};
			
	var post = {};
	post.spd = spd_id;
	post.kode_ref = $('#kode_ref_income').val();
	post.agent_code = gridnya.jqGrid('getCell', spd_id,'agent_code');
	post.qty = qty;
	post.trk_doc = trk_doc;
	
	//console.log(post);
	
	grid.jqGrid(
	{ 
		url	:window.location.origin+'/afg_c_income/get_from_agent',
		mtype : "post",
		datatype: "json",
		postData: post,
		sortname: "id",
      sortorder: "asc",
		colNames:
		[
			'',
			'',
			'Item Service',
			'Container',
			'Currency',
			'Quantity',
			'Price/Unit', 
			'Price', 
			'Kode Agent', 
			'INV / DB',
			'Grup',
			'Created By',
			'Created At',
			'Updated By',
			'Updated At',			
		],
    	colModel:
		[
			{name:'sp_detail_id',index:'sp_detail_id', align:"left", width:30, editable: true, hidden: true,},
			{name:'kode_ref',index:'kode_ref', align:"left", width:100, editable: true, hidden: true,},
			{name:'item_service',index:'item_service', align:"center", width:170, editable: true, 
				edittype:"select",
				formatter:'select', 
				editoptions:{value:" : ;REBATE AGENT:REBATE AGENT;REBATE AGENT VOLUME:REBATE AGENT VOLUME;REBATE AGENT SET:REBATE AGENT SET"}
			},
			{name:'container',index:'container', align:"center", width:60,
				editable: true, 
				edittype:"select",
				editoptions:{
					dataUrl	: window.location.origin+'/afg_c_dropdown/dropdownContainer',
					dataInit: function (el) 
					{ 
						$(el).css({'max-width':'60px'}); 
					},
					dataEvents: 
					[{  type: 'change',
                  	fn: function(e) {
                       	on_edit(true);
                   	}
               }]
				},hidden: true,
			},
			{name:'currency',index:'currency', align:"center", width:90, editable: true, 
				edittype:"select",formatter:'select', editoptions:{
					value:"USD:USD;RP.:RP."
				}
			},
			{name:'qty',index:'qty', align:"center", width:100, editable: true, 
				editoptions: { 
					dataInit: function (el) 
					{ 
						$(el).attr('title','Hanya boleh memasukan angka'); 
					}
				},
				formatter: 'number',
    			formatoptions: { decimalSeparator: ".", thousandsSeparator: ",", decimalPlaces: 4, defaultValue: ' ' }
			},
			{name:'unit_price',index:'unit_price', align:"right", width:100, editable: true, 
				editoptions: { 
					width: 30, 
					dataInit: function (el) 
					{ 
						$(el).attr('title','Hanya boleh memasukan angka'); 
					}
				},
				formatter: 'number',
    			formatoptions: { decimalSeparator: ".", thousandsSeparator: ",", decimalPlaces: 2, defaultValue: ' ' }
			},
			{name:'price',index:'price', align:"right", width:100, editable: false,
			/* 
				editoptions: { 
					disabled:true,
					dataInit: function (el) 
					{ 
						$(el).attr('title','Hanya boleh memasukan angka'); 
					}
				},
				editrules:{ custom:true, custom_func: numberFormat },
				formatter: "number",
    			formatoptions: { decimalSeparator: ".", thousandsSeparator: ",", decimalPlaces: 2, defaultValue: ' ' }
    		*/
    			editoptions:{
					disabled: true,
				},
				formatter: "number",
    			formatoptions: { decimalSeparator: ".", thousandsSeparator: " ", decimalPlaces: 2, defaultValue: '' },
			},
			{name:'agent',index:'agent', align:"center", width:120, editable: true, 
				edittype:"select",
				editoptions:{ 
					dataUrl: window.location.origin+'/afg_c_dropdown/dropdownAgent',
					dataInit: function (el) 
					{ 
						$(el).css({'min-width':'120px','max-width':'120px'}); 
					},
					cellattr: function(rowId, cellValue, rawObject, cm, rdata) {
    					return 'style="text-transform: uppercase"';
					},
					dataEvents: 
					[{  type: 'focus',
                  	fn: function(e) {
                  		on_edit(true);
                       	var rowid = grid.jqGrid('getGridParam', 'selrow');
                       	var qty = grid.jqGrid('getCell',rowid,'qty');
                       	var unit_price = grid.jqGrid('getCell',rowid,'unit_price');
                       	var price = eval(qty) * eval(unit_price);
                        grid.jqGrid('setCell',rowid,'price',price);
                        $.ajax
								({
									type : "POST",
									url  : window.location.origin+'/afg_c_income/update_price',
									data : { id : rowid, price : price ,table : "inf_income_from_agent"},
									success:function(response)
									{
										var data = $.parseJSON(response);
										if (data.status==1) {
										}
										else 
										{
											//show_messages("No Kode Ref "+dPost["kode_ref"]+" and item = "+dPost["item"]+" finance_item table");
										}
									}
								})
                   	}
               }]
				},
			},
			{name:'invoice_db',index:'invoice_db', align:"center", width:100, editable: true, 
				edittype:"select",
				formatter:'select', 
				editoptions:{value:" : ;INVOICE:INVOICE;DEBIT NOTE:DEBIT NOTE"}
			},
			{name:'g',index:'g', align:"center", width:50, editable: true, 
				editoptions:{
					dataInit: function (el) 
					{ 
						$(el).css({'text-transform':'uppercase','min-width':'50px','max-width':'50px'});
					},
					dataEvents: 
					[{  
						type: 'keydown', 
            		fn: function(e) { 
                		var key = e.charCode || e.keyCode;
                		if (key == 13)//enter
                		{
                    		$("#btnAddNewAgent").click();
								grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
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
			{name:'created_by',index:'created_by', width:200, hidden: false,},
  			{name:'created_at',index:'update_at', width:200, hidden: false,},
  			{name:'updated_by',index:'updated_by', width:200, hidden: false,},
  			{name:'updated_at',index:'updated_at', width:200, hidden: false,},
		], 
		multiselect:true,
		shrinkToFit:false,
		loadonce:false,
  		viewrecords: true,
		modal: true,
		rownumbers: true,
		rowNum: 100,
		width: 1273,
		height: 'auto',
		rowList:[-0],
		gridview: true,
		cellEdit:true,
		caption:"Get Intensiv From Agent",
		editurl	:	window.location.origin+'/afg_c_income/manage_from_agent',
		cellEdit:true,
		cellsubmit:'clientArray',
		pager:'#pager-frm-agent',
		
		afterSaveCell: function(rowid, cellname, value, iRow, iCol) {
			saving_income(rowid, cellname, value, iRow, iCol);
			save_cell_from_agen(rowid, cellname, value, iRow, iCol);
			if(event.keyCode===13) 
			{
				//$("#btnAddNewAgent").click();
				grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
			}
    	},
    			
		loadComplete: function(data) {
			$("#cb_"+grid[0].id).hide();
			$(".ui-jqgrid-titlebar-close",$("#table-frm-agent")[0].grid.cDiv).hide();
			var ids = grid.jqGrid('getDataIDs');
			for (var i = 0; i < ids.length; i++) {
   			 	var rowId = ids[i];
    		}
			if (ids.length>=1) {
    			grid.jqGrid('editCell',ids.length,4,true);
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
    			//cbs.attr("disabled", true);  
    			//selected.attr("disabled", false);   			
     		}
        	else
        	{
        		var cbs = $("tr.jqgrow > td > input.cbox:disabled", grid[0]);  		
    			//cbs.attr("disabled", false);       			
        	}
		},	
	})
	.navGrid('#pager-frm-agent',{ edit: false, add: false, del: true, refresh: true, <?php echo 'search: false' ?>,},{ }, { },
     	{
     		height: "auto", 
     		width: "auto",
     		serializeDelData: function (postdata) {
     			return {
    				id: postdata.id, 
    				oper: postdata.oper, 
    				kolom : '',
    			};
     		},
     		afterSubmit: function(){
    		grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
    			return [true];
			}
     	},{ },{ }
	)
	grid.navButtonAdd('#pager-frm-agent',{ 
			caption:"", 
			buttonicon:"ui-icon-plus", 
			onClickButton: insert_data_insentiv_agent, 
			position: "last", 
			title:"Insert Data Table From Agent", 
			cursor: "pointer",
			id:"btnAddNewAgent",
	})
	grid.navButtonAdd('#pager-frm-agent',{ 
			caption:"",
			buttonicon:"ui-icon-disk", 
			onClickButton: tarik_data_insentiv_agent, 
			position: "last", 
			title:"Tarik Data Insentive From Agent", 
			cursor: "pointer"
	})
	
	var div = $(".ui-jqgrid-bdiv");
 	var nx = div.length-1;
 	var divx = div[nx];
 	$(divx).css({'min-height':'100px'})
}

function save_cell_from_agen(rowid,cellname,value)
{
	var grid = $("#table-frm-agent"), i;
	var myData = grid.getRowData(rowid);
	$.ajax
	({
		type	:"POST",
		url	:window.location.origin+'/afg_c_income/saving_agent',
		data	: {
			id		 : rowid,
			myData : myData,
		},
		success:function(response)
		{
			var data = $.parseJSON(response);
		}
	})
}

function insert_data_insentiv_agent()
{
	var spd_id = $('#spd_id_income').val();	
	var grid = $("#table-frm-agent"), i;
	var code = 	$('#txtRefno2').val();
	if (code.substr(4,3)=='300'||code.substr(4,3)=='301'||code.substr(4,3)=='302'||code.substr(4,3)=='400'||code.substr(4,3)=='600'||code.substr(4,3)=='800')
	{
		var gridnya = $("#tabel_sp_detail_export"), i;
	}
	else 
	{
		var gridnya = $("#tabel_sp_detail"), i;
		
	}
	
	$.ajax
	({
		type	:"POST",
		url	:window.location.origin+'/afg_c_income/manage_from_agent',
		data:
		{
			agent : gridnya.jqGrid('getCell', spd_id,'agent_code'),
			sp_detail_id : $('#spd_id_income').val(),
			kode_ref : $('#kode_ref_income').val(),
			hb_l : gridnya.jqGrid('getCell', spd_id,'hb_l'),
			oper : 'add',
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

function tarik_data_insentiv_agent()
{
	var grid = $("#table-frm-selling"), i;	
	var spd_id = $('#spd_id_income').val();
	
	var code = $('#txtRefno2').val();
	if (code.substr(4,3)=='300'||code.substr(4,3)=='301'||code.substr(4,3)=='302'||code.substr(4,3)=='400'||code.substr(4,3)=='600'||code.substr(4,3)=='800')
	{
		var gridnya = $("#tabel_sp_detail_export"), i;
	}
	else 
	{
		var gridnya = $("#tabel_sp_detail"), i;
	}
	
	var destination = gridnya.jqGrid('getCell', spd_id,'destination');		
	if(destination ==''){
		var destination = $('#dropdownCountries').val();
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
		url : window.location.origin+'/afg_c_income/tarik_data_insentiv_agent',
		data:  
		{ 
			spd : spd_id,
			kode_ref : $('#kode_ref_income').val(),
			shipper_id : gridnya.jqGrid('getCell', spd_id,'shipper'),
			destination : destination,
			qty : qty,
			hb_l : gridnya.jqGrid('getCell', spd_id,'hb_l'),
			agent : $('select[id="dropdownAgent"]').val(),
			countainer : $("#txtTOC2").val(),
		},
		success: function(response)
		{
			$("#table-frm-agent").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
		},
		error:function(XMLHttpRequest)
		{
			return false;
		}
	})
}



function tarik_data_coloader()
{
	var grid = $("#table-frm-selling"), i;	
	var spd_id = $('#spd_id_income').val();
	
	var coloader = $("#txtCarrier").val();
	var destination = $("#tabel_sp_detail").jqGrid('getCell', spd_id,'destination');	
	
	$.ajax
	({
		type: "POST",
		url : window.location.origin+'/afg_c_income/tarik_data_coloader',
		data:  
		{ 
			spd : spd_id,
			kode_ref : $('#kode_ref_income').val(),
			shipp_co : coloader,
			destination : destination,
			qty : $("#tabel_sp_detail").jqGrid('getCell', spd_id,'bl_meas'),
			countainer : $("#txtTOC2").val(),
		},
		success: function(response)
		{
			$("#table-frm-coloader").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
		},
		error:function(XMLHttpRequest)
		{
			return false;
		}
	})
}

function save_data()
{
	var tdk_proses = $('#TDK-PROSES').attr('checked');
	var trk_doc = $('#TRK-DOC').attr('checked');
	
	if (tdk_proses=='checked') {tdk_proses = true;} else {tdk_proses = false;}
	if (trk_doc=='checked') {trk_doc = true;} else {trk_doc = false;}
	
	
	$.ajax ({
		type	:"POST",
		url	:window.location.origin+'/afg_c_income/manage_header_income',
		data:
		{
			kode_ref : $('#kode_ref_income').val(),
			tarik_insentiv_per_doc: trk_doc,
			intensiv_tidak_diproses: tdk_proses,
			kurs:$( '#kurs' ).val(),
		},
		success:function(response)
		{
			var data = $.parseJSON(response);
			if(data.status==1){
				swal('Data updated');
			}
		}
	})
}

function table_from_coloader(){
	var spd_id = $('#spd_id_income').val();	
	var grid = $("#table-frm-coloader"), i;
	var code = 	$('#txtRefno2').val();
	if (code.substr(4,3)=='300'||code.substr(4,3)=='301'||code.substr(4,3)=='302'||code.substr(4,3)=='400'||code.substr(4,3)=='600'||code.substr(4,3)=='800')
	{
		var gridnya = $("#tabel_sp_detail_export"), i;
	}
	else 
	{
		var gridnya = $("#tabel_sp_detail"), i;
		
	}
	
	grid.jqGrid(
	{ 
		url	:window.location.origin+'/afg_c_income/get_from_coloader',
		mtype : "post",
		datatype: "json", 
		postData: 
		{ 
			kode_ref : $('#kode_ref_income').val(),
			qty : gridnya.jqGrid('getCell', $('#spd_id_income').val(),'bl_meas'),
			trk_doc : $('#TRK-DOC').val(),
		},
		sortname: "id",
      sortorder: "asc",
		colNames:
		[
			'',
			'',
			'Container',
			'Currency',
			'Quantity',
			'Price/Unit', 
			'Price', 
			'INV / DB',
			'Grup',
			'',
			'Created By',
			'Created At',
			'Updated By',
			'Updated At',
		],
    	colModel:
		[
			{name:'kode_ref',index:'kode_ref', align:"left", width:10, editable: true, hidden: true,},
			{name:'mchgs',index:'mchgs', align:"left", width:10, editable: true, hidden: true,},
			{name:'container',index:'container', align:"center", width:100, editable: true,
				edittype:"select",
				editoptions:{
					dataUrl: window.location.origin+'/afg_c_dropdown/dropdownContainer',
					dataInit: function (el) 
					{ 
						$(el).css({'min-width':'100px','max-width':'100px'}); 
					},
				}
			},
			{name:'currency',index:'currency', align:"center", width:80, editable: true,
				edittype:"select",
				formatter:'select', 
				editoptions:{
					value:" : ;USD:USD;RP.:RP.",
					dataInit: function (el) { 
						$(el).css({'min-width':'80px','max-width':'80px'}); 
					},
				}
			},
			{name:'qty',index:'qty', align:"center", width:80, editable: true, 
				editoptions: { 
					dataInit: function (el) 
					{ 
						$(el).attr('title','Hanya boleh memasukan angka'); 
					}
				},
				formatter: 'number',
    			formatoptions: { decimalSeparator: ".", thousandsSeparator: ",", decimalPlaces: 4, defaultValue: ' ' }
			},
			{name:'unit_price',index:'unit_price', align:"right", width:80, editable: true, 
				editoptions: { 
					dataInit: function (el) { 
						$(el).attr({'title':'Hanya boleh memasukan angka'});
						$(el).css({'min-width':'80px','max-width':'80px'});  
					},
				},
				formatter: 'number',
    			formatoptions: { decimalSeparator: ".", thousandsSeparator: ",", decimalPlaces: 2, defaultValue: ' ' }
			},
			{name:'price',index:'price', align:"right", width:80, editable: false, 
				editoptions: { 
					dataInit: function (el) 
					{ 
						$(el).attr('title','Hanya boleh memasukan angka').attr('disabled',true); 
						$(el).css({'min-width':'80px','max-width':'80px'});
					},
				},
				formatter: 'number',
    			formatoptions: { decimalSeparator: ".", thousandsSeparator: ",", decimalPlaces: 2, defaultValue: ' ' }
			},
			{name:'invoice_db',index:'invoice_db', align:"center", width:100, editable: true, 
				edittype:"select",
				formatter:'select', 
				editoptions:{
					value:" : ;INVOICE:INVOICE;DEBIT NOTE:DEBIT NOTE",
					dataInit: function (el) { 
						$(el).attr({'min-width':'100px','max-width':'100px'});
					},
					
					dataEvents: 
					[{  type: 'focus',
                  	fn: function(e) {
                  		on_edit(true);
                       	var rowid = grid.jqGrid('getGridParam', 'selrow');
                       	var qty = grid.jqGrid('getCell',rowid,'qty');
                       	var unit_price = grid.jqGrid('getCell',rowid,'unit_price');
                       	var price = eval(qty) * eval(unit_price);
                        grid.jqGrid('setCell',rowid,'price',price);
                        $.ajax
								({
									type : "POST",
									url  : window.location.origin+'/afg_c_income/update_price',
									data : { id : rowid, price : price ,table : "inf_income_from_coloader"},
									success:function(response)
									{
										var data = $.parseJSON(response);
										if (data.status==1) {
											
										}
										else 
										{
											//show_messages("No Kode Ref "+dPost["kode_ref"]+" and item = "+dPost["item"]+" finance_item table");
										}
									}
								})
                   	}
               }]
               
					}
			},
			{name:'g',index:'g', align:"center", width:50, editable: true, 
				editoptions:{
					dataInit: function (el) { 
						$(el).css({'text-transform':'uppercase','min-width':'50px','max-width':'50px'});
					},
					dataEvents: 
					[{  
						type: 'keydown', 
            		fn: function(e) { 
                		var key = e.charCode || e.keyCode;
                		if (key == 13)//enter
                		{
                    		$("#btnAddNewColoader").click();
								//grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
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
			{name:'sp_detail_id',index:'sp_detail_id', align:"left", width:10, editable: false, hidden: true,},
			{name:'created_by',index:'created_by', width:200, hidden: false,},
  			{name:'created_at',index:'update_at', width:200, hidden: false,},
  			{name:'updated_by',index:'updated_by', width:200, hidden: false,},
  			{name:'updated_at',index:'updated_at', width:200, hidden: false,},
		],
		multiselect:true,
		shrinkToFit:false,
		loadonce:false,
  		viewrecords: true,
		modal: true,
		rownumbers: true,
		rowNum: 100,
		width: 1273,
		height: 'auto',
		rowList:[-0],
		gridview: true,
		caption:"Get Rebate From Coloader",
		editurl	:	window.location.origin+'/afg_c_income/manage_from_coloader',
		cellEdit:true,
		cellsubmit:'clientArray',
		pager:'#pager-frm-coloader',
		
		afterSaveCell: function(rowid, cellname, value, iRow, iCol) {
			save_cell_from_coloader(rowid,cellname,value)
			if(event.keyCode===13) 
			{
				//$("#btnAddNewColoader").click();
				grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
			}
    	},
				
		loadComplete: function(data) {
			$("#cb_"+grid[0].id).hide();
			var ids = grid.jqGrid('getDataIDs');
			for (var i = 0; i < ids.length; i++) {
   			 	var rowId = ids[i];
    		}
			if (ids.length>=1) {
    			grid.jqGrid('editCell',ids.length,4,true);
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
    			//cbs.attr("disabled", true);  
    			//selected.attr("disabled", false);   			
     		}
        	else
        	{
        		var cbs = $("tr.jqgrow > td > input.cbox:disabled", grid[0]);  		
    			//cbs.attr("disabled", false);       			
        	}
		},
		
	})
	.navGrid('#pager-frm-coloader',{ edit: false, add: false, del: true, refresh: true, <?php echo 'search: false' ?>,},
		{ }, 
   	{ },
     	{
     		height: "auto", 
     		width: "auto",
     		serializeDelData: function (postdata) {
     			return {
    				id: postdata.id, 
    				oper: postdata.oper, 
    				kolom : '',
    			};
     		},
     		afterSubmit: function(){
    		grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
    			return [true];
			}
     	},
      { },
     	{ }	
	)
	.navButtonAdd('#pager-frm-coloader',{ 
			caption:"", 
			buttonicon:"ui-icon-plus", 
			onClickButton: insert_data_coloader, 
			position: "last", 
			title:"Insert Data Rebate From Coloader", 
			cursor: "pointer",
			id:"btnAddNewColoader",
	})
	.navButtonAdd('#pager-frm-coloader',{ 
			caption:"", 
			buttonicon:"ui-icon-disk", 
			onClickButton: tarik_data_coloader, 
			position: "last", 
			title:"Tarik Data Rebate From Coloader", 
			cursor: "pointer"
	})
	var div = $(".ui-jqgrid-bdiv");
 	var nx = div.length-1;
 	var divx = div[nx];
 	$(divx).css({'min-height':'100px'})
}

function save_cell_from_coloader(rowid,cellname,value){
	var grid = $("#table-frm-coloader"), i;
	var myData = grid.getRowData(rowid);

	$.ajax
	({
		type	:	"POST",
		url	:	window.location.origin+'/afg_c_income/saving_coloader',
		data	: {
			id		 : rowid,
			myData : myData,
		},
		success:function(response)
		{
			var data = $.parseJSON(response);
			/*
			if(data.status==1){
				if(data.kolom=='qty' || data.kolom=='unit_price'){
					$("#table-frm-coloader").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
				}
				grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
    			return [true];
			}
			*/
		}
	})
}

function insert_data_coloader(){
	var grid = $("#table-frm-coloader"), i;
	$.ajax
	({
		type	:"POST",
		url	:window.location.origin+'/afg_c_income/manage_from_coloader',
		data	:
		{
			kode_ref : $('#kode_ref_income').val(),
			sp_detail_id : $('#spd_id_income').val(),
			oper : 'add',
		},
		success:function(response)
		{
			var data = $.parseJSON(response);
			if(data.status==1){
				//var dataSatu = [{'id':data.id}];
				//grid.jqGrid('addRowData',i,dataSatu,'last');
				grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
			}
		}
	})
}

function edit_detail_sp(){
		var rowid = arguments[0];
		var kode_ref = $('#tabel_sp_detail').jqGrid('getCell',rowid,'kode_ref');
		
		$( "#Form-Income" ).dialog('close');
		
		$( "#Form-Input-Detail" ).css('background-color','#eef').css('font-size','#11px');
		$( "#Form-Input-Detail" ).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
		$( "#Form-Input-Detail" ).dialog(
		{
			resizable: false,
			height: 510,
			width: 800,
			modal: true,
			title: "Edit manual request stuffing "+kode_ref,
			close: function() 
			{	
				enabled_top_button();
				remove_detail();
				$(this).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
				$("#tabel_sp_detail").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
			}
		});
	
		$.ajax({
			type: "POST", 
			url : window.location.origin+'/afg_c_stuffing_plan/  _detail',
			data: { id : rowid, mode:"edit" },
			success:function(XMLHttpRequest)
			{
				$('#Form-Input-Detail').html(XMLHttpRequest);
			}
		})
}

function array_unique(inputArr) {
  var key = '',
    tmp_arr2 = {},
    val = '';

  var __array_search = function(needle, haystack) {
    var fkey = '';
    for (fkey in haystack) {
      if (haystack.hasOwnProperty(fkey)) {
        if ((haystack[fkey] + '') === (needle + '')) {
          return fkey;
        }
      }
    }
    return false;
  };

  for (key in inputArr) {
    if (inputArr.hasOwnProperty(key)) {
      val = inputArr[key];
      if (false === __array_search(val, tmp_arr2)) {
        tmp_arr2[key] = val;
      }
    }
  }

  return tmp_arr2;
}

function get_proses_post(){	
	var kode_ref = $('#kode_ref_income').val();
	
		$.ajax({
			type: "POST", 
			url : "<?php echo site_url('finance/c_post_data_acct_per_job/proses_post')?>",
			data: { 
				region : 'Jakarta',
				kode_ref,
			},
			success:function(response)
			{
				swal("Berhasil")
			},
			error: function(response){
				var data = $.parseJSON(response.responseText);
				swal(data.message)
			}
		})
	 
}
</script>