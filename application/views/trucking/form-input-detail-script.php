<script type="text/javascript">
$(document).ready(function () 
{
	
	var mode = $('#operForm').val();
	var rowid = $('#idForm').val();
	
	if(mode=='edit')
	{
		var grid = $('#tabel_sp_detail'),i;
		$('#idRequestForm').val(grid.jqGrid('getCell',rowid,'id_request'));		
		$('#numForm').val(grid.jqGrid('getCell',rowid,'num'));
		$('#kodeRefForm').val(grid.jqGrid('getCell',rowid,'kode_ref'));
		$('#isLockForm').val(grid.jqGrid('getCell',rowid,'isLock'));		
		$('#wgctnForm').val(grid.jqGrid('getCell',rowid,'wg_ctn'));
		//alert(grid.jqGrid('getCell',rowid,'sales'));
		$('#shipperForm').val(grid.jqGrid('getCell',rowid,'shipper'));
		$('#noteForm').val(grid.jqGrid('getCell',rowid,'note'));
		$('#consigneeForm').val(grid.jqGrid('getCell',rowid,'consignee'));
		$('#notifyForm').val(grid.jqGrid('getCell',rowid,'notify'));
		$('#descForm').val(grid.jqGrid('getCell',rowid,'desc_goods'));	
		$('#freightschargesForm').val(grid.jqGrid('getCell',rowid,'freights_charges'));
		$('#freightForm').val(grid.jqGrid('getCell',rowid,'freight'));		
		$('#qtyForm').val(grid.jqGrid('getCell',rowid,'no_pack_qty'));
		$('#packForm').val(grid.jqGrid('getCell',rowid,'no_pack_unit'));				
		$('#weightForm').val(grid.jqGrid('getCell',rowid,'weight'));
		$('#shipmeasForm').val(grid.jqGrid('getCell',rowid,'ship_meas'));
		$('#whmeasForm').val(grid.jqGrid('getCell',rowid,'wh_meas'));		
		$('#chgsForm').val(grid.jqGrid('getCell',rowid,'chgs_weight'));
		$('#costmeasForm').val(grid.jqGrid('getCell',rowid,'cost_meas'));
		$('#whagentForm').val(grid.jqGrid('getCell',rowid,'wh_agent'));
		$('#blmeasForm').val(grid.jqGrid('getCell',rowid,'bl_meas'));
		$('#dropdownDestinationDetail').val(grid.jqGrid('getCell',rowid,'destination'));
		$('#docForm').val(grid.jqGrid('getCell',rowid,'doc'));		
		$('#hblForm').val(grid.jqGrid('getCell',rowid,'hb_l'));
		$('#shippingmarksForm').val(grid.jqGrid('getCell',rowid,'shipping_mark'));
		$('#dropdownAgentDetail').val(grid.jqGrid('getCell',rowid,'agent_code'));				
		$('#coForm').val(grid.jqGrid('getCell',rowid,'c_o'));
		$('#csForm').val(grid.jqGrid('getCell',rowid,'c_s'));			
		$('#statussellingForm').val(grid.jqGrid('getCell',rowid,'status_selling'));			
	//	$('#isnForm').val(grid.jqGrid('getCell',rowid,'isN')),
		$('#optcostForm').val(grid.jqGrid('getCell',rowid,'isOptCost')),	
		$('#spotrateForm').val(grid.jqGrid('getCell',rowid,'isSpotRate')),		
		$('#express_release').val(grid.jqGrid('getCell',rowid,'express_release'));	
		$('#shipped_on_board').val(grid.jqGrid('getCell',rowid,'shipped_on_board')),
		$('#net_weight_tidak_dicetak').val(grid.jqGrid('getCell',rowid,'net_weight_tidak_dicetak')),	
		$('#cetak_jml_doc_origi').val(grid.jqGrid('getCell',rowid,'cetak_jml_doc_origi')),		
		$('#sp-detail-akhir').val(grid.jqGrid('getCell',rowid,'sp_id_detail_akhir'));
		$('#dropdownShippingDetail').val(grid.jqGrid('getCell',rowid,'shipp_co'));
		
		checkedcheck();
		checkRadioBtn(grid.jqGrid('getCell',rowid,'isN'))

		var sp_detail_akhir = $('#sp-detail-akhir').val();
		var sp_detail_id = $('#idForm').val();
		var tanggal='<?php echo date("Ymd") ?>'; 
		
		var txtClosingDate = $("input[id='txtETDJKT']").val();				
		var ClosingDate = txtClosingDate.substring(6,txtClosingDate.length)+txtClosingDate.substring(3,txtClosingDate.length-5)+txtClosingDate.substring(0,txtClosingDate.length-8);		
		var locked = grid.jqGrid('getCell',rowid,'isLock')	;
		
		if( tanggal < ClosingDate && locked == 1 )
		{
			$('#btnUnlock').html('<input type="button" value=" UNLOCK REQUEST " style="width: 150; height: 30; margin-top: 15px; margin-bottom: 15px" onclick="unlock_request()">');
		}
		
		if( sp_detail_akhir ==  sp_detail_id && tanggal < ClosingDate)
		{
			$('#btnBatal').html('<input type="button" value=" BATAL PROSES " style="width: 150; height: 30; margin-top: 15px; margin-bottom: 15px" onclick="batal_proses()">');
			$('#btnPindah').html('<input type="button" value=" PINDAH JOB " style="width: 150; height: 30; margin-top: 15px; margin-bottom: 15px" onclick="pindah_job()">');
		}
	}
	else if(mode=='add')
	{
		var rowCount 	 = $("#tabel_sp_detail").getGridParam("reccount");
		var rowCount2   = rowCount-26;
		if(rowCount == 26 || rowCount > 26)
		{
			var abjad = 'Z'+String.fromCharCode(65+rowCount2);
		}
		else
		{
			var abjad = String.fromCharCode(65+rowCount);
		}
		$('#kodeRefForm').val($('#txtRefno2').val()+abjad);
		$('#numForm').val(abjad);
		$('#isLockForm').val(1);
	}
	
	var code = $("input[id='kodeRefForm']").val().substr(4,3);
	if(code=='300' ||code=='301' || code=='302' ||code=='400' ||code=='402' ||code=='600'){
		$("#shipper_td").html('Consignee');
		$("#consignee_td").html('Shipper');
	} else {
		$("#shipper_td").html('Shipper');
		$("#consignee_td").html('Consignee');
	}
});

function checkRadioBtn(N)
{
   var radioLength = $('input[name="isnForm"]').length;
   for (var i = 0; i < radioLength; i++) 
	{
    	if ($('input[name="isnForm"]')[i].value == N) 
		{
			$('input[name="isnForm"]')[i].checked = true;
        	break;
    	}
   }
}

function saveing_detail()
{
	$ ('#loading-save').html('<div class="msg-save">LOADING...</div>');	
	/*
	var selected_shipment_type = new Array();
	$("input[name='shipment_type[]']:checked").each(function() { selected_shipment_type.push($(this).val()); });
	*/
	
	var dataDetail =
	{
		id						: $('#idForm').val(),
		oper					: $('#operForm').val(),
		isLock				: $('#isLockForm').val(),
		num					: $('#numForm').val(),
		code 					: $('#txtRefno2').val(),
		kode_ref 			: $('#kodeRefForm').val(),	
		sales					: $('#salesForm').val(),		
		sp_id					: $('#spId').val(),
		id_request 			: $('#idRequestForm').val(),
		wg_ctn 				: $('#wgctnForm').val(),
		shipper 				: $('#shipperForm').val(), 	
		no_pack_qty 		: $('#qtyForm').val(),
		no_pack_unit 		: $('#packForm').val(),
		desc_goods 			: $('#descForm').val(),
		weight				: $('#weightForm').val(),
		ship_meas 			: $('#shipmeasForm').val(),
		wh_meas				: $('#whmeasForm').val(),
		chgs_weight			: $('#chgsForm').val(),
		cost_meas			: $('#costmeasForm').val(),
		wh_agent				: $('#whagentForm').val(),	
		bl_meas				: $('#blmeasForm').val(),
		destination			: $('#dropdownDestinationDetail').val(),
		doc					: $('#docForm').val(),
		hb_l					: $('#hblForm').val(),
		agent_code			: $('#dropdownAgentDetail').val(),
		freight				: $('#freightForm').val(),
		freights_charges 	: $('#freightschargesForm').val(),
		note					: $('#noteForm').val(),
		consignee			: $('#consigneeForm').val(),
		notify				: $('#notifyForm').val(),
		c_o					: $('#coForm').val(),
		c_s					: $('#csForm').val(),
		shipping_mark		: $('#shippingmarksForm').val(),
		status_selling		: $('#statussellingForm').val(),
		isN					: $("input[name='isnForm']:checked").val(),
		isOptCost			: $('#optcostForm').attr('checked'),	
		isSpotRate			: $('#spotrateForm').attr('checked'),		
		express_release	: $('#express_release').attr('checked'),
		shipped_on_board	: $('#shipped_on_board').attr('checked'),
		net_weight_tidak_dicetak	:	$('#net_weight_tidak_dicetak').attr('checked'),
		cetak_jml_doc_origi	:	$('#cetak_jml_doc_origi').attr('checked'),
		shipp_co				:  $('#dropdownShippingDetail').val(),
	}
	if($( '#salesForm' ).val() == '')
	{
		show_messages('Sales must not Empty Value');
		$ ('#loading-save').html('');
		$( '#salesForm' ).focus();
		return false;
	}
	if($( '#txtRefno2' ).val() == '')
	{
		show_messages('This isn\'t stuffing active, Please close this form and create stuffing first');
		$ ('#loading-save').html('');
		return false;
	}
	else
	{
		$.ajax
		({
			type:"POST",
			url:"<?php echo site_url('afg_c_stuffing_plan/manage_table'); ?>",
			data: dataDetail,
			success:function(response)
			{	
				$('#Form-Input-Detail').dialog('close');
			},
			error:function(response)
			{	
				show_messages('Error saving data');
				return false;		
			},
		})		
	}
}

function checkedcheck()
{
	if($('#isnForm').val()== "Yes" )
	{
		$('#isnForm').attr('checked',true)
	}
	else
	{
		$('#isnForm').attr('checked',false)
	}
	if($('#optcostForm').val()== "Yes" )
	{
		$('#optcostForm').attr('checked',true)
	}
	else
	{
		$('#optcostForm').attr('checked',false)
	}
	if($('#spotrateForm').val()== "Yes" )
	{
		$('#spotrateForm').attr('checked',true)
	}
	else
	{
		$('#spotrateForm').attr('checked',false)
	}
	
	if($('#express_release').val()== "Yes" )
	{
		$('#express_release').attr('checked',true)
	}
	else
	{
		$('#express_release').attr('checked',false)
	}
	if($('#shipped_on_board').val()== "Yes" )
	{
		$('#shipped_on_board').attr('checked',true)
	}
	else
	{
		$('#shipped_on_board').attr('checked',false)
	}
	if($('#net_weight_tidak_dicetak').val()== "Yes" )
	{
		$('#net_weight_tidak_dicetak').attr('checked',true)
	}
	else
	{
		$('#net_weight_tidak_dicetak').attr('checked',false)
	}
	if($('#cetak_jml_doc_origi').val()== "Yes" )
	{
		$('#cetak_jml_doc_origi').attr('checked',true)
	}
	else
	{
		$('#cetak_jml_doc_origi').attr('checked',false)
	}
}

function save_optcostchk()
{
	if($('#optcostForm').attr('checked',true))
	{
		return 1;
	}
	else
	{
		return 0;
	}
}

function save_spotratechk()
{
	if($('#spotrateForm').attr('checked',true))
	{
		return 1;
	}
	else
	{
		return 0;
	}
}

$(function()
{
	$("#shipperForm").live("change",function()
	{
		var shipper_id = $("#shipperForm").val();
		$('#noteForm').val('LOADING...').css('font-weight','bold').css('font-style','italic')
		$.ajax
		({
			type:"POST",
			url:"<?php echo site_url('afg_c_stuffing_plan/select_shipper_code'); ?>",
			data:
			{
				shipper_id: shipper_id,
			},
			success:function(response)
			{	
				var data = $.parseJSON(response);
				$('#noteForm').css('font-weight','normal').css('font-style','').val(data.nama+'\n'+data.alamat);	
			}
		})
	})
	
	$("#consigneeSelectForm").live("change",function()
	{
		var consignee_id = $("#consigneeSelectForm").val();
		$('#consigneeForm').val('LOADING...').css('font-weight','bold').css('font-style','italic')
		$.ajax
		({
			type:"POST",
			url:"<?php echo site_url('afg_c_stuffing_plan/select_consignee_code'); ?>",
			data:
			{
				consignee_id: consignee_id,
			},
			success:function(response)
			{	
				var data = $.parseJSON(response);
				$('#consigneeForm').css('font-weight','normal').css('font-style','').val(data.nama+'\n'+data.alamat);	
			}
		})
	})
	
	$("#notifySelectForm").live("change",function()
	{
		var notify_id = $("#notifySelectForm").val();
		$('#notifyForm').val('LOADING...').css('font-weight','bold').css('font-style','italic')
		$.ajax
		({
			type:"POST",
			url:"<?php echo site_url('afg_c_stuffing_plan/select_notify_code'); ?>",
			data:
			{
				notify_id: notify_id,
			},
			success:function(response)
			{	
				var data = $.parseJSON(response);
				$('#notifyForm').css('font-weight','normal').css('font-style','').val(data.nama+'\n'+data.alamat);	
			}
		})
	})
	
	
})

function form_close()
{
	$('#Form-Input-Detail').fadeOut('slow', function() 
	{
		$(this).dialog('close');
	})
}

function batal_proses()
{
	var id = $('#idForm').val();
	var id_request = $('#tabel_sp_detail').jqGrid('getCell',id,'id_request'); 
	$.ajax
	({
		type:"POST",
		url:"<?php echo site_url('afg_c_stuffing_plan/batal_proses'); ?>",
		data:
		{
				id: id, id_request: id_request
		},
		success:function(response)
		{	
			var data = $.parseJSON(response);
			if(data.status=1)
			{
				form_close();
			}
			else
			{
				return false;	
			}
		}
	})
}

function pindah_job()
{
	
	var kode_job = $('#txtRefno2').val();
	var sp_detail_id = $('#idForm').val();
	$('#cData').trigger('click');
	$.ajax
	({
		type:"POST",
		url:"<?php echo site_url('afg_c_stuffing_plan/select_kode_ref'); ?>",
		data: { id : sp_detail_id, ref_no : kode_job },
		success:function(response)
		{
			var data = $.parseJSON(response);
			if(data.status==0){
				show_messages('No stuffingplan activ #');
			} else {
				var dropdownnya = data.dropdown;
				
				$( "#Insert-Form" ).fadeIn('slow', function(){
					$( this ).css('font-size','11px').css('margin','10px');
					$( this ).dialog({
						autoOpen: true,
						resizable: true,
						height: 'auto',
						width: 300,
						modal: true,
						title: "Pindah Job",
						buttons:
						{
							"OK": function() 
							{
								var sp = $( '#dropdownKodeRef' ).val();
								if(sp == 0 )
								{
									$('#inputan').html("<font style=\"color: #f00; font-size: 10px; font-style: italic;\">Silahkan select kolom kode ref</font><br >"+dropdownnya)
								}
								else
								{
									paste_job(sp,sp_detail_id);
									$( this ).dialog( 'close' );
								}
							},
							"BATAL": function() 
							{
							$( this ).dialog( 'close' );
							}
						},
						close: function()  {	 }
					}).html(dropdownnya)
				})
			}
		}
	})
}

function paste_job(sp_id,sp_detail_id)
{
	$.ajax
	({
		type:"POST",
		url:"<?php echo site_url('afg_c_stuffing_plan/move_job'); ?>",
		data: { sp : sp_id, spd : sp_detail_id},
		success:function(response)
		{			
			var data = $.parseJSON(response);
			if(data.status == 1 )
			{
				$( '#Form-Input-Detail' ).fadeOut('slow', function() 
				{
						$( this ).dialog('close');	
						$( '#spId' ).val(sp_id);
						getStuffingPlan('pilih');
						$("#tabel_sp_detail").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
				});
			}
			else
			{
				return true;
			}
			
		},
		error:function(XMLHttpRequest) { }
	});
}

function open_attachement(mode,input)
{
	var spd = $('#idForm').val();
	
	if(mode=='desc'){
		var value = $('#tabel_sp_detail').jqGrid('getCell',spd,'desc_attc');
	}
	if(mode=='marks'){
		var value = $('#tabel_sp_detail').jqGrid('getCell',spd,'marks_attc');
	}
	
	$( "#Form-Atachement" ).dialog(
	{
		autoOpen: true,
		resizable: false,
		height: 'auto',
		width: 430,
		modal: true,
		title: input.toUpperCase()+" Attachement Data "+mode.toUpperCase(),
		close: function() 
		{	
			enabled_top_button();
			$(this).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
		}
	});
	
	if(input=='add'){
		$( '#Form-Atachement' ).html('<input id="mode-attc-form" type="hidden" value="'+mode+'"><textarea id="attc-form" style="font-size: 11px; min-width: 400px; max-width: 400px; border-radius: 2px; border: 1px solid #999" rows="10">'+
			'</textarea>'+
			'<hr>'+
			'<input onclick="save_attc()" type="button" value="SUBMIT" style="font-size: 10px; width: 80px; height: 30px; margin-bottom: 11px; float: right; margin-left: 15px">'+
			'<input onclick="close_attc()" type="button" value="CLOSE" style="font-size: 10px; width: 80px; height: 30px; margin-bottom: 11px; float: right">');
	}
	if(input=='edit'){
		
		$( '#Form-Atachement' ).html('<input id="mode-attc-form" type="hidden" value="'+mode+'"><textarea id="attc-form" style="font-size: 11px; min-width: 400px; max-width: 400px; border-radius: 2px; border: 1px solid #999" rows="10">'+value+
			'</textarea>'+
			'<hr>'+
			'<input onclick="save_attc()" type="button" value="SUBMIT" style="font-size: 10px; width: 80px; height: 30px; margin-bottom: 11px; float: right; margin-left: 15px">'+
			'<input onclick="close_attc()" type="button" value="CLOSE" style="font-size: 10px; width: 80px; height: 30px; margin-bottom: 11px; float: right">');
	}
}

function close_attc()
{
	$( "#Form-Atachement" ).fadeOut('slow', function() 
	{
		$(this).dialog('close');
	})
}

function save_attc()
{
	$.ajax
	({
		type: "POST",
		url : "<?php echo base_url('afg_c_stuffing_plan/update_attc')?>",
		data:  
		{ 
			kode_ref: $('#kodeRefForm').val(),
			mode: $('#mode-attc-form').val(),
			value: $('#attc-form').val(),
		},
		success: function(data)
		{
			$( "#Form-Atachement" ).fadeOut('slow', function() 
			{
				$(this).dialog('close');
				$("#tabel_sp_detail").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
			})
		},
		error:function(XMLHttpRequest)
		{
			alert("ERROR")
		}
	})
}

function unlock_request()
{
	$.ajax
		({
			type:"POST",
			url:"<?php echo site_url('afg_c_stuffing_plan/unlock_request'); ?>",
			data: { id: $('#idForm').val(), id_request : $('#idRequestForm').val()},
			success:function(response)
			{	
				$('#Form-Input-Detail').dialog('close');
			},
			error:function(response)
			{	
				show_messages('Error Unlock Request');
				return false;		
			},
		})
}

</script>
	