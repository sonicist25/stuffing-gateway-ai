<style type="text/css">
.voucher {
	text-transform: uppercase;
}
td {

}
</style>

<div id="form-browse">
	<table id="table-dua"></table>
	<div id="page-dua"></div>
</div>
<div id="discount-voucher"></div>
<div id="msg-voucher"></div>
<div id="out-discount"></div>

<input type="hidden" id="id_aktif" name="id_aktif"> 
<input type="hidden" id="oper" name="oper" style="float: right; width: 50;">

<div style="float: right; margin-top: 30px; margin-right: 70px">
	<input type="button" id="btnNew" value="NEW" style="width: 70; height: 30" onclick="new_data()">
	<input type="button" id="btnEdit" value="EDIT" style="width: 70; height: 30" onclick="edit_data()">
	<input type="button" id="btnDelete" value="DELETE" style="width: 70; height: 30">
	<input type="button" id="btnBrowse" value="BROWSE" style="width: 70; height: 30" onclick="browse_data()">
 	<input type="button" id="btnPrev" value="PREV" style="width: 70; height: 30" onclick="get_content('prev')">
	<input type="button" id="btnNext" value="NEXT" style="width: 70; height: 30" onclick="get_content('next')">
	<!--
 	<input type="button" id="btnExit" value="EXIT" style="float: right; width: 70; height: 30" onclick="close_form()">
 	-->
 		<input type="button" id="btnPrint" value="PRINT" style="width: 70; height: 30; margin-left: 30" onclick="print_data()" disabled="disabled">
</div>

<div id="form_input" class="main-work" style="float: left; width: 760px; height: 420px; margin-left: 80px; padding: 20px">
		<h2>Discount Voucher</h2>
    	<table style="margin-top: 10px" width="755">
    		<tr>
    			<td height="30">Job. Ref. No</td><td id="td_job_no"></td>
    			<td width="20"></td>
    			<td>Group</td><td><input style="width: 50px" class="voucher" id="group_form" disabled="disabled">
    			<label style="padding-left: 70px;">Date</label>
    			<input style=" float: right; width: 80px" class="voucher" id="date_form" disabled="disabled"></td>
    		</tr>
    		<tr>
    			<td height="30">Payable To.</td><td><input style="width: 300px" class="voucher" id="payable_form" disabled="disabled"></td>
    			<td width="20"></td>
    			<td>Our Inv No.</td><td><input style="width: 250px" class="voucher" id="inv_form" disabled="disabled"></td>
    		</tr>
    		<tr>
    			<td height="30">B/L No.</td><td><input style="width: 300px" class="voucher" id="bl_form" disabled="disabled"></td>
    			<td width="20"></td>
    			<td>Destination</td><td><input style="width: 250px" class="voucher" id="destination_form" disabled="disabled"></td>
    		</tr>
    	</table>
		
	<div style="clear: both; height: 10px"></div>

	<table id="table-satu"></table>
	<div id="page-satu"></div>

</div>

<script type="text/javascript">
$(document).ready(function () {	
	get_table_dua();
	$("#td_job_no").html('<input style="width: 300px" id="job_ref_no" disabled="disabled">');
	$(".ui-dialog-titlebar-close").attr('hidden',true);
	$( "#date_form" ).datepicker({dateFormat : "dd/mm/yy"});
	$("#form-browse").attr('hidden',true);
})

function disabled_input(){
	$("input[id='inv_form']").attr('disabled', true);
	$("input[id='date_form']").attr('disabled', true);
	$("input[id='group_form']").attr('disabled', true);
	$("input[id='bl_form']").attr('disabled', true);
	$("input[id='destination_form']").attr('disabled', true);
	$("input[id='payable_form']").attr('disabled', true);
	$("#td_job_no").html('<input style="width: 300px" id="job_ref_no" disabled="disabled">');
}

function get_table_dua(){
	var grid = $("#table-dua"), i;
	var Data = { id: '',cek: ''}
	grid.jqGrid( {
      url:"<?php echo base_url('finance/c_discount_voucher/get_table_dua')?>",
  		mtype : "post",
  		datatype: "json",
   	postData: Data,
   	sortname: 'id',
   	sortorder:'desc',
   	colNames:['Kode Job','Group','Date','BL/ No','Payable To','No Invoice','Destination'], 
  		colModel:
   	[
			{name:'kode_job',index:'kode_job', width:120, align:"center",
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			} ,
			{name:'group',index:'group', width:40, align:"center",
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'date',index:'date', width:70, align:"center",
				stype:"text",
				searchoptions:{ 
					sopt:['eq','lt','gt'],
					dataInit: function(element) {
        				$(element).datepicker({dateFormat: 'yymmdd'})
      			}
				}
			},	
			{name:'bl_no',index:'bl_no', width:90, align:"center",
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},	
			{name:'payable',index:'payable', width:150, align:"left",
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},	
			{name:'no_invoice',index:'no_invoice', width:170, align:"left",
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},	
			{name:'destination',index:'destination', width:120, align:"left",
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},	
		],
		shrinkToFit: false,
		multiselect:false,
		loadonce : false,
		cellEdit: false,
		rowNum:20,
		width: 855,
		height: 450,
		rowList:[30,60,90],
		viewrecords: true,
		rownumbers: true,
		gridview: true,
		modal: true,
      caption:false,
		pager: '#page-dua',

		loadComplete: function() {
			$("#cb_"+grid[0].id).hide();
			$(".ui-jqgrid-titlebar-close",$("#table-dua")[0].grid.cDiv).hide();
			var rowid = grid.getDataIDs();
			var id = rowid.shift();
			$('input[id="id_aktif"]').val(id);
			get_content('pilih');
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
		ondblClickRow: function(rowid,status){
			$( '#form-browse' ).fadeOut('slow', function() 
			{
				$('input[id="id_aktif"]').val(rowid);
				get_content('pilih');
				$( "#form-browse" ).dialog('close');
			});
		}		
   }).jqGrid('navGrid', '#page-dua', {edit: false, add: false,  del: false,},
   	{ },
   	{ },
   	{ },
   	{ modal: true, height: "auto", width: "auto", closeAfterSearch : true, caption: "Fine Data ..",},
   	{ }
   );
}

function get_content(mode){
	$.ajax
	({
		type:"POST",
		url:"<?php echo site_url('finance/c_discount_voucher/get_content'); ?>",
		data:{ id : $('input[id="id_aktif"]').val(), mode : mode },
		success:function(response)
		{
			var data = $.parseJSON(response);
			$("input[id='inv_form']").val(data.no_invoice);
			$("input[id='date_form']").val(data.date);
			$("input[id='group_form']").val(data.group);
			$("input[id='bl_form']").val(data.bl_no);
			$("input[id='destination_form']").val(data.destination);
			$("input[id='payable_form']").val(data.payable);
			$("#td_job_no").html('<input style="width: 300px" id="job_ref_no" value="'+data.kode_job+'" disabled="disabled">');
					
			$('input[id="id_aktif"]').val(data.id);
			$('input[id="btnPrint"]').attr('disabled',false);	
			
			if(data.id==data.id_first){
				$('input[id="btnPrev"]').attr('disabled',true);
				$('input[id="btnNext"]').attr('disabled',false);
			} else if(data.id==data.id_last){
				$('input[id="btnNext"]').attr('disabled',true)
				$('input[id="btnPrev"]').attr('disabled',false)
			} else {
				$('input[id="btnNext"]').attr('disabled',false)
				$('input[id="btnPrev"]').attr('disabled',false)
			}		
			get_table_satu()		
			$('#table-satu').jqGrid('setGridParam',{datatype: 'json', postData: { id_xdv : data.id }}).trigger("reloadGrid");
		}
	})
}

function get_table_satu(){
	var grid = $("#table-satu"), i;
	grid.jqGrid( {
      url:"<?php echo base_url('finance/c_discount_voucher/get_table_satu')?>",
  		mtype : "post",
  		datatype: "json",
   	postData: { id_xdv : $('input[id="id_aktif"]').val()},
   	sortname: 'id',
   	sortorder:'asc',
   	colNames:['Item Service','Currency','Amount'], 
  		colModel:
   	[
			{name:'item',index:'item', width:450, align:"left"} ,
			{name:'currency',index:'currency', width:100, align:"center"},
			{name:'amount',index:'amount', width:100, align:"center"},	
		],
//		shrinkToFit: false,
		multiselect:true,
		loadonce : false,
		cellEdit: true,
		rowNum:10,
		width: 755,
		height: 245,
		rowList:[10,20,30],
		viewrecords: true,
		rownumbers: true,
		gridview: true,
		modal: true,
      caption:false,
		pager: '#page-satu',

		loadComplete: function() {
			$("#cb_"+grid[0].id).hide();
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
   }).jqGrid('navGrid', '#page-satu', {edit: false, add: false,  del: false, refresh : false, <?php echo 'search : false' ?>},
   	{ },
   	{ },
   	{ },
   	{ modal: true, height: "auto", width: "auto", closeAfterSearch : true, caption: "Fine Data ..",},
   	{ }
   );

}

function new_data(){
	$("input[name='oper']").val('add');
	$("input[id='btnNew']").attr('disabled',true);
	$("input[id='btnEdit']").attr('disabled',true);
	$("input[id='btnPrev']").attr('disabled',true);
	$("input[id='btnNext']").attr('disabled',true);
	$("input[id='btnPrint']").attr('disabled',true);
	$("input[id='btnDelete']").val('CANCEL').attr('onClick','cancel_data(\'add\')');
	$("input[id='btnBrowse']").val('SAVE').attr('onClick','save_data()');
	$("#date_form").attr('disabled',false).val('');;
	$("input[class='voucher']").attr('disabled',false).val('');
	$("#table-satu").jqGrid('clearGridData');
	$.ajax
	({
		type:"POST",
		url:"<?php echo site_url('finance/c_discount_voucher/get_kode_job'); ?>",
		data: { oper: 'add' },
		success:function(response)
		{
			var data = $.parseJSON(response);
			$("#td_job_no").html(data.dropdown_kode);
		}
	})
}

function tarik_data_discount(){
	$.ajax
	({
		type:"POST",
		url:"<?php echo site_url('finance/c_discount_voucher/tarik_data_discount'); ?>",
		data: { kode_ref: $("select[id='job_ref_no']").val() },
		success:function(response)
		{
			var data = $.parseJSON(response);
			$("input[id='inv_form']").val(data.no_invoice);
			$("input[id='date_form']").val(data.date);
			$("input[id='group_form']").val(data.g);
			$("input[id='bl_form']").val(data.bl_no);
			$("input[id='destination_form']").val(data.destionation);
			$("input[id='payable_form']").val(data.payable_to);
			tarik_table_satu()
		}
	})
}

function tarik_table_satu(){
	$("#table-satu").jqGrid('clearGridData');
	var id = $('input[id="id_aktif"]').val();
	var kode_ref = $("select[id='job_ref_no']").val();
	$.ajax
	({
		type:"POST",
		url:"<?php echo site_url('finance/c_discount_voucher/tarik_table_satu'); ?>",
		data: { kode_ref: kode_ref, id : id },
		success:function(response)
		{
			var data = $.parseJSON(response);
			for (var i = 0; i < data.rows.length; i++) {
				$("#table-satu").addRowData(data.rows[i].id,data.rows[i]);
        	}
		}
	})
}

function edit_data(){
	var kode = $('#job_ref_no').val();
	$("input[name='oper']").val('edit');
	$("input[id='btnNew']").attr('disabled',true);
	$("input[id='btnEdit']").attr('disabled',true);
	$("input[id='btnPrev']").attr('disabled',true);
	$("input[id='btnNext']").attr('disabled',true);
	$("input[id='btnPrint']").attr('disabled',true);
	$("input[id='btnDelete']").val('CANCEL').attr('onClick','cancel_data(\'edit\')');
	$("input[id='btnBrowse']").val('SAVE').attr('onClick','save_data()');
	$("#job_ref_no").attr('disabled',false);
	$( "#date_form" ).attr('disabled',false);
	$("input[class='voucher']").attr('disabled',false);
	$("#td_job_no").html('<select style="width: 300px" class="voucher" id="job_ref_no"><option value="'+kode+'">'+kode+'</option></select>');
}

function cancel_data(mode){
	disabled_input()
	$("input[id='btnNew']").attr('disabled',false);
	$("input[id='btnEdit']").attr('disabled',false);
	$("input[id='btnPrev']").attr('disabled',false);
	$("input[id='btnNext']").attr('disabled',false);
	$("input[id='btnDelete']").val('DELETE').attr('onClick','del_data()');
	$("input[id='btnBrowse']").val('BROWSE').attr('onClick','browse_data()');
	get_content('pilih');
}

function save_data(){
	
	if($('#date_form').val()==''){
		show_messages('<div id="content-msg">Please complite data</div>');
		$('#date_form').focus()
		return false;
	} else {
		
		var DataPost =	
		{
			oper : $("input[name='oper']").val(),
			kode_job : $('#job_ref_no').val(),
			group	: $('#group_form').val(),
			date : $('#date_form').val(),
			payable : $('#payable_form').val(),
			no_invoice : $('#inv_form').val(),
			bl_no : $('#bl_form').val(),
			destination : $('#destination_form').val(),
			id : $('#id_aktif').val(),
		}
		$.ajax
		({
			type:"POST",
			url:"<?php echo site_url('finance/c_discount_voucher/manage_data'); ?>",
			data: DataPost,
			success:function(data)
			{
				var data = $.parseJSON(data);
				if(data.oper=='add'){
					save_table_satu()
				}
				if(data.oper=='edit'){
					show_messages('<div id="content-msg">Update data success</div>');
					get_content('pilih');
				}
				if(data.oper=='del'){
					$('#table-dua').jqGrid('setGridParam',{datatype: 'json',}).trigger("reloadGrid");
					show_messages('<div id="content-msg">Data deleted</div>');
				}
				cancel_data(data.oper);
			}
		})
	}
}

function save_table_satu(){
	var rows = $("#table-satu").jqGrid('getRowData')
	var DataPostTable =	
		{
			oper : 'add',
			rows : rows,
		}
	$.ajax
	({
		type:"POST",
		url:"<?php echo site_url('finance/c_discount_voucher/save_table_satu'); ?>",
		data: DataPostTable,
		success:function(data)
		{
			$('#table-dua').jqGrid('setGridParam',{datatype: 'json',}).trigger("reloadGrid");
			show_messages('<div id="content-msg">Insert data success</div>');
		}
	})
}

function close_form(){
	$("#discount-voucher").fadeOut('slow',function(){$(this).dialog('close')});
}

function browse_data(){
	$("#form-browse").dialog({
			modal:true,
			height:570,
			width: 900,
			title: "BROWSE DATA DISCOUNT VOUCHER",
			resizable: false,
	}).css('font-size','11px').css('margin','10px');
	get_table_dua()	
}

function print_data(){
	var id = $('input[id="id_aktif"]').val();
	if(id==''){
		show_show_messagess('Tidak dapat mencetak tanpa form active');
		return false;
	}
	$( "#out-discount" ).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
	$( "#out-discount" ).dialog(
	{
		autoOpen: true,
		resizable: false,
		height: 640,
		width: 990,
		modal: true,
		title: "PRINT OUT DATA",
		buttons : {  },
		close: function()  
		{	 
			$(this).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
		}
	});
	
	$.ajax
	({
			type: "POST",
			url : "<?php echo base_url('finance/c_discount_voucher/print_data')?>/"+id,
			data:  {id: id},
			success: function(data)
			{
				$('#out-discount').html('<iframe src="<?php echo base_url("finance/c_discount_voucher/print_data") ?>/'+id+'" width="960px" height="600"></iframe>');
			},
			error:function(XMLHttpRequest)
			{

				$('#out-discount').html(XMLHttpRequest.responseText)
			}
	})
}

function del_data(){
	$("input[name='oper']").val('del');
}

</script>


<?php 

/*
	var Post = new Array();
	$(".voucher").each(function() {Post.push($(this).val());});
	var Data =	
	{
		oper : $("input[name='oper']").val(),
		post : Post,
	}
	$.ajax
	({
		type:"POST",
		url:"<?php echo site_url('finance/c_discount_voucher/manage_data'); ?>",
		data: Data,
		success:function(data)
		{
			var data = $.parseJSON(data);
			if(data.oper=='add'){
				show_messages('<div id="content-msg">Insert data success</div>');
			}
			if(data.oper=='edit'){
				show_messages('<div id="content-msg">Update data success</div>');
			}
			if(data.oper=='del'){
				show_messages('<div id="content-msg">Data deleted</div>');
			}
			cancel_data(data.oper);
		}
	})


function get_data(mode){
	if(mode=='next'){
		$("input[id='btnPrev']").attr('disabled',false);
	}
	if(mode=='prev'){
		$("input[id='btnNext']").attr('disabled',false);
	}
	
	$.ajax
	({
		type:"POST",
		url:"<?php echo site_url('finance/c_discount_voucher/get_data'); ?>",
		data: { id: $("input[id='id_form']").val(), mode: mode },
		success:function(data)
		{
			var data = $.parseJSON(data);
			$("input[id='id_form']").val(data.id).attr('disabled',true);
			$("input[id='inv_form']").val(data.no_invoice).attr('disabled',true);
			$("input[id='date_form']").val(data.date).attr('disabled',true);
			$("input[id='group_form']").val(data.group).attr('disabled',true);
			$("input[id='bl_form']").val(data.bl_no).attr('disabled',true);
			$("input[id='destination_form']").val(data.destination).attr('disabled',true);
			$("input[id='payable_form']").val(data.payable).attr('disabled',true);
			$("#td_job_no").html('<input style="width: 300px" id="job_ref_no" value="'+data.kode_job+'" disabled="disabled">');
			if(data.id==data.first_id){
				$("input[id='btnPrev']").attr('disabled',true);
			}
			if(data.id==data.last_id){
				$("input[id='btnNext']").attr('disabled',true);
			}
			$("input[id='btnDelete']").val('DELETE').attr('onClick','del_data('+data.id+')');
			$("input[id='btnBrowse']").val('BROWSE').attr('onClick','browse_data()');	
		}
	})
	
	}
	'<div><div id="msg-voucher"></div>'+
		'<input type="button" id="btnNew" value="NEW" style="width: 70; height: 30" onclick="new_data()">'+
    	'<input type="button" id="btnEdit" value="EDIT" style="width: 70; height: 30" onclick="edit_data()">'+
    	'<input type="button" id="btnDelete" value="DELETE" style="width: 70; height: 30" onclick="del_data()">'+
    	'<input type="button" id="btnBrowse" value="BROWSE" style="width: 70; height: 30" onclick="browse_data()">'+
    	'<input type="button" id="btnPrev" value="PREV" style="width: 70; height: 30" onclick="get_data(\'prev\')">'+
    	'<input type="button" id="btnNext" value="NEXT" style="width: 70; height: 30" onclick="get_data(\'next\')">'+
    	'<input type="button" id="btnExit" value="EXIT" style="float: right; width: 70; height: 30" onclick="close_form()">'+
    	'<input type="hidden" id="oper" name="oper" style="float: right; width: 50;">'+
    	'<input type="hidden" class="voucher" id="id_form" style="float: right; width: 50;">'+
    	'</div>'+
    	'<div id="main-form-voucher">'+
    	'<table>'+
    		'<tr>'+
    			'<td>Job. Ref. No</td><td id="td_job_no"></td>'+
    			'<td width="50"></td>'+
    			'<td>Group</td><td><input style="width: 50px" class="voucher" id="group_form">'+
    			'<label style="padding-left: 30px; padding-right: 10px">Date</label>'+
    			'<input style=" float: right; width: 80px" class="voucher" id="date_form"></td>'+
    		'</tr>'+
    		'<tr>'+
    			'<td>Payable To.</td><td><input style="width: 300px" class="voucher" id="payable_form"></td>'+
    			'<td width="50"></td>'+
    			'<td>Our Inv No.</td><td><input style="width: 200px" class="voucher" id="inv_form"></td>'+
    		'</tr>'+
    		'<tr>'+
    			'<td>B/L No.</td><td><input style="width: 300px" class="voucher" id="bl_form"></td>'+
    			'<td width="50"></td>'+
    			'<td>Destination</td><td><input style="width: 200px" class="voucher" id="destination_form"></td>'+
    		'</tr>'+
    	'</table>'+
    	'</div><div style="clear: both"></div><center><table id="table-satu"></table><div id="page-satu"></div></center>'

		
			var dataTarik = 
			/*
			[
				{
					id : data.id,
					item : data.item,
					currency : data.currency,
					amount: data.amount,
				}
			];
			

*/

?>













