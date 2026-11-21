<script type="text/javascript">
function SelectShipmentRequest(s){
	$( '#dialog_shipment_request' ).fadeIn('slow', function() 
	{
		$( "#dialog_shipment_request" ).dialog(
		{
			autoOpen: true,
			resizable: false, 
			title: "Shipment Request Table",
			height:470,
			width:930,
			modal:true,
			close: function() 
			{	
				enabled_top_button();
				cancel_instan_accsess();
			}		
		});
		var sp_id = $('#spId').val();  
		$('#tabel_shipment_request').jqGrid('setGridParam').trigger("reloadGrid");
		generateTabelShipment(s);
	})
}

function generateTabelShipment(s){
	var sp_id = $('#spId').val();
	var grid = $("#tabel_shipment_request"), i;
	$("#tabel_shipment_request").jqGrid(
	{
		url:"<?php echo base_url('afg_c_shipment_request/displayShipmentRequestFiltered') ?>", 
		mtype : "post",  
		datatype: "json",
		sortname: "shipment_request_id",
		sortorder: "desc",  
		colNames:
		[
			'Subscrible',
			'Shiper',
			'Consignee',
			'Notify Party',
			'Container No',
			'Container Size',
			'Shipping Unit',
			'Total',
			'Package',
			'Description',
			'Gross Weight',
			'Netto Weight',
			'Measurement',
			'Express Release',
			'Shipped On Board',
			'Net Weight tdk Cetak',
			'Cetak doc Origi',
			'Freight Charges',
			'Freight Payable',
			'',
			'',
			'',
			'',
			'',
		],
		colModel:
		[
			{name:'shipper_id',index:'shipper_id', width:100, align:"left", editable: true, editoptions:{size:"40", maxlength:"50", readonly:'readonly'}},
			{name:'note',index:'note', align:"left", width:100, editable: true, edittype:"textarea",editoptions:{cols:"40", rows:"2"}},
			{name:'consignee',index:'consignee', align:"left", editable: true, edittype:"textarea",editoptions:{cols:"40", rows:"2"}},
			{name:'notify_party',index:'notify_party', align:"left", editable: true, edittype:"textarea",editoptions:{cols:"40", rows:"2"}},
			{name:'container_no',index:'container_no', align:"left",editable: true, editoptions:{size:"40", maxlength:"50"}},
			{name:'container_size',index:'container_size', align:"left",editable: true, editoptions:{size:"40", maxlength:"50"}},
			{name:'shipping_unit',index:'shipping_unit', align:"left",editable: true, editoptions:{size:"40",maxlength:"50"}},
			{name:'total',index:'total', align:"center",editable: true, editoptions:{size:"40",maxlength:"50"},
				formatter: 'number',
    			formatoptions: { decimalSeparator: ".", thousandsSeparator: " ", decimalPlaces: 0, defaultValue: '' }
			},
			{name:'pack',index:'pack', align:"left", editable: true, editoptions:{size:"40", maxlength:"50"}},
			{name:'description',index:'description', align:"left", editable: true, edittype:"textarea",editoptions:{cols:"40", rows:"2"}},
			{name:'gross_weight',index:'gross_weight', align:"center", editable: true, editoptions:{size:"40",maxlength:"50"},
				formatter: 'number',
    			formatoptions: { decimalSeparator: ".", thousandsSeparator: " ", decimalPlaces: 4, defaultValue: '' }
			},
			{name:'netto_weight',index:'netto_weight',  align:"center", editable: true, editoptions:{size:"40",maxlength:"50"},
				formatter: 'number',
    			formatoptions: { decimalSeparator: ".", thousandsSeparator: " ", decimalPlaces: 4, defaultValue: '' }
			},
			{name:'measurement',index:'measurement',  align:"center", editable: true, editoptions:{size:"40",maxlength:"50"},
				formatter: 'number',
    			formatoptions: { decimalSeparator: ".", thousandsSeparator: " ", decimalPlaces: 4, defaultValue: '' }
			},
			{name:'express_release', index:'express_release', editable: true, width: 40, align: 'center',
				formatter: "checkbox",
				edittype:"checkbox", 
				editoptions:{value:"1:0"},		
			},
			{name:'shipped_on_board', index:'shipped_on_board', editable: true, width: 40, align: 'center',
				formatter: "checkbox",
				edittype:"checkbox", 
				editoptions:{value:"1:0"},		
			},
			{name:'net_weight_tidak_dicetak', index:'net_weight_tidak_dicetak', editable: true, width: 40, align: 'center',
				formatter: "checkbox",
				edittype:"checkbox", 
				editoptions:{value:"1:0"},		
			},
			{name:'cetak_jml_doc_origi', index:'cetak_jml_doc_origi', editable: true, width: 40, align: 'center',
				formatter: "checkbox",
				edittype:"checkbox", 
				editoptions:{value:"1:0"},		
			},
			{name:'freights_charges', index:'freights_charges', width:60, editable: true, edittype:"textarea", editoptions:{cols:"40", rows:"2"}},			
			{name:'freight_payable',index:'freight_payable', width:120, align:"center", editable: true, edittype:"select",formatter:'select', editoptions:{value:"COLLECT:COLLET;PREPAID:PREPAID", width: 100,}},
			{name:'is_processed',index:'is_processed', width:10, align:"center", hidden:false},
			{name:'stf_cls',index:'stf_cls', width:10, align:"center", hidden:true},
			{name:'sp_id',index:'sp_id', width:10, align:"center", hidden:false},
			{name:'export_id',index:'export_id', width:10, align:"left",hidden:false},
			{name:'isLock',index:'isLock', width:10, align:"center", hidden:false},
		],
		shrinkToFit: false,
		multiselect:false,
	//	loadonce : false,
		cellEdit: false,
		rowNum:15,
		width: 900,
		height: 370,
		rowList:[15,30,45],
		viewrecords: true,
		rownumbers: true,
		gridview: true,
		modal: true,
		pager: '#page-req',
		caption:"",		
		editurl:'afg_c_stuffing_plan/manage_data_request',
		
		loadComplete: function (data) {
    		$("#cb_"+grid[0].id).hide();
    		var rowCount  = grid.getGridParam("reccount");
    		if ( rowCount > 0 ) {
        		for (var i = 0 ; i < data.rows.length; i++) {
            	if (data.rows[i].cell[23] == '0') {
            		$("tr#"+(data.rows[i].id)+".jqgrow > td", grid[0]).css('color','#11f');
            	}
        		}
    		}
   	},
    	
    	beforeSelectRow: function(rowshipment_request_id, e) {
        var cbsdis = $("tr#"+rowshipment_request_id+".jqgrow > td > input.cbox:disabled", grid[0]);
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
		
		ondblClickRow: function(rowid){
			var selected = $("tr.jqgrow > td > input.cbox:checked", grid[0]);		
			selected[rowid] = status;							
			selected.removeAttr("checked");
			to_table_shipment(rowid);
			
			/*								
			var koderef = $( '#txtRefno2' ).val();		
			if(koderef == "")
			{
				$( "#Insert-Form" ).dialog(
				{
					autoOpen: true,
					resizable: true,
					height: 'auto',
					width: 'auto',
					modal: true,
					title: "Messages",
					buttons: 
					{
						"OK": function() 
						{
							$( this ).dialog( "close" );
							$( '#dialog_shipment_request' ).fadeOut('slow', function() 
							{
								$( "#dialog_shipment_request" ).dialog('close');
							});
							return true;
						},
					},
					close: function() 
					{	
						enabled_top_button()
					}
				});
				$('#inputan').html("Kode RefNo masih kosong,<br>Silahkan anda edit stuffing untuk melanjutkan proses")
			}
			else
			{
				to_table_shipment(rowid);
			}
			*/
		},
		
	}).navGrid('#page-req',{ edit:false, add:false, del:false },
	{
			height: "auto", 
			width: "auto",  
			closeAfterEdit: true, 
			reloadAfterEdit : true, 
			modal: true, 
			editCaption: "Edit Data Request", 
			viewPagerButtons: false,
			/*
			beforeShowForm: function(frm) {
				$('#shipment_type').html('<input class="shipment_type" name="shipment_type[]" type="checkbox" value="1" checked="chacked"> Express Release')
			},
			*/
			afterSubmit: function () 
			{
    			$('#tabel_shipment_request').jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
    			return [true];
			}
		},
   	{
			height: "auto", 
			width: 'auto', 
			closeAfterAdd: true, 
			reloadAfterAdd : true, 
			viewPagerButtons: false,
			modal: true, 
			addCaption: "Add Request Manual", 
		},
     	{height: "auto", width: "auto"},
      {modal: true, height: "auto", width: "auto", closeAfterSearch : true, searchCaption: "Fine Data Shippment Request",},
     	{ } 
	)
}
/*
function createexceltextbox(cellvalue, options, rowObject) 
{ 
	return "<input type='text' value='" + cellvalue + "' style='width:100%' abc='xyz'  />";
}

function check_shipment(cellvalue, options, rowObject)
{
	return '<input class="shipment_type" name="shipment_type[]" type="checkbox" value="1" checked="chacked"> Express Release';
}
*/

function to_table_shipment(Na)
{
	var rowCount 					= $("#tabel_sp_detail").getGridParam("reccount");
	var num 							= String.fromCharCode(65+rowCount);
	var rowCount2   				= rowCount-26;
	if(rowCount == 26 || rowCount > 26)
	{
		var num = 'Z'+String.fromCharCode(65+rowCount2);
	}
	var kode_ref 					= $("#txtRefno2").val()+''+num;
	var code 						= $("#txtRefno2").val();
	var shipment_request_id 	= Na;
	var wg_ctn 						= "";
	var shipper_id 				= $('#tabel_shipment_request').jqGrid('getCell',Na,'shipper_id');
	var no_pack_qty  				= $('#tabel_shipment_request').jqGrid('getCell',Na,'total');
	var no_pack_unit  			= $('#tabel_shipment_request').jqGrid('getCell',Na,'pack');
	var desc_goods 				= $('#tabel_shipment_request').jqGrid('getCell',Na,'description');
	var weight 						= $('#tabel_shipment_request').jqGrid('getCell',Na,'gross_weight');
	var ship_meas 					= $('#tabel_shipment_request').jqGrid('getCell',Na,'netto_weight');
	var wh_meas						= $('#tabel_shipment_request').jqGrid('getCell',Na,'measurement');
	var chgs_weight 				= "";
	var cost_meas	 				= "";
	var wh_agent 					= "";
	var bl_meas		 				= $('#tabel_shipment_request').jqGrid('getCell',Na,'measurement');
	var destination				= $('#dropdownCountries').val();
	var doc 							= "";
	var hb_l	 						= "";
	var agent_code 				= "";
	var freight		 				= $('#tabel_shipment_request').jqGrid('getCell',Na,'freight_payable');
	var freights_charges			= $('#tabel_shipment_request').jqGrid('getCell',Na,'freights_charges');
	var c_o							= $('#tabel_shipment_request').jqGrid('getCell',Na,'container_no');
	var c_s							= $('#tabel_shipment_request').jqGrid('getCell',Na,'container_size');
	var shipping_mark				= $('#tabel_shipment_request').jqGrid('getCell',Na,'shipping_unit');
	var note			 				= $('#tabel_shipment_request').jqGrid('getCell',Na,'note');
	var consignee			 		= $('#tabel_shipment_request').jqGrid('getCell',Na,'consignee');
	var notify			 			= $('#tabel_shipment_request').jqGrid('getCell',Na,'notify_party');
	var express_release				= $('#tabel_shipment_request').jqGrid('getCell',Na,'express_release');
	var shipped_on_board				= $('#tabel_shipment_request').jqGrid('getCell',Na,'shipped_on_board');
	var net_weight_tidak_dicetak	= $('#tabel_shipment_request').jqGrid('getCell',Na,'net_weight_tidak_dicetak');
	var cetak_jml_doc_origi			= $('#tabel_shipment_request').jqGrid('getCell',Na,'cetak_jml_doc_origi');
	var isLock							= $('#tabel_shipment_request').jqGrid('getCell',Na,'isLock');
	var id_stuff 						= $('#tabel_shipment_request').jqGrid('getCell',Na,'sp_id');
	
	$.ajax
		({
			type: "POST",
			url : "<?php echo site_url('afg_c_stuffing_plan/manage_table')?>",
			data: 
			{
				sp_id				: id_stuff,
				kode_ref			: kode_ref,
				id_request 		: shipment_request_id,
				wg_ctn 			: wg_ctn,
				shipper 			: shipper_id, 	
				no_pack_qty 	: no_pack_qty,
				no_pack_unit 	: no_pack_unit,
				desc_goods 		: desc_goods,
				weight			: weight,
				ship_meas 		: ship_meas,
				wh_meas			: wh_meas,
				chgs_weight		: chgs_weight,
				cost_meas		: cost_meas,
				wh_agent			: wh_agent,	
				bl_meas			: bl_meas,
				destination		: destination,
				doc				: doc,
				hb_l				: hb_l,
				agent_code		: agent_code,
				freight			: freight,
				freights_charges : freights_charges,
				c_o				: c_o,
				c_s				: c_s,
				shipping_mark	: shipping_mark,
				isN				: 'FALSE',
				isOptCost		: 'FALSE',
				status_selling	: '',
				isSpotRate		: 'FALSE',
				express_release 				: express_release,
				shipped_on_board 				: shipped_on_board,
				net_weight_tidak_dicetak 	: net_weight_tidak_dicetak,
				cetak_jml_doc_origi 			: cetak_jml_doc_origi,				
				note				: note,
				consignee		: consignee,
				notify			: notify,
				num				: num,
				isLock			: isLock,
				code  			: code,
				oper				: "add",
			},
			success: function(response)
			{
				var data = $.parseJSON(response);
				if(data.status == 1)
				{
					$( '#dialog_shipment_request' ).fadeOut('slow', function() 
					{
						$( "#dialog_shipment_request" ).dialog('close');
						update_shipment_request(Na);	
					});
					$("#tabel_sp_detail").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");	
				}
				else
				{
					return false;
				}
			},
			error:function(XMLHttpRequest)
			{
				$( "#Error-Message" ).dialog(
				{
					autoOpen: true,
					resizable: true,
					height: 340,
					width: 650,
					modal: true,
					title: "INSERT TABLE ERROR",
					close: function() 
					{	
						
					}
				});
				$('#content-error').html(XMLHttpRequest.responseText)
			},
		}) 
}

function update_shipment_request(rowid)
{
	$.ajax
	({
		type: "POST",
		url : "<?php echo site_url('afg_c_shipment_request/hide_shipment_request')?>",
		data: { id : rowid },
		success: function(data)
		{		
			enabled_top_button();
			cek_request($('#spId').val());
		},
		error:function(XMLHttpRequest)
		{
			enabled_top_button();
		}
	}) 
}
</script>