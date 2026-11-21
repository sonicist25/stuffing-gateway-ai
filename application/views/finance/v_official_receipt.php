<div id="msg"></div>
<div id="output"></div>
<div id="form-table-satu">
		<center>
		<table id="table_satu"></table>
		<div id="page-satu" style="font-size: 11px"></div>
		</center>
		<div style="height: 15px"></div>
</div>
<div id="delete-form"></div>
<div class="fix clearfix"></div>
<div style="float: left; width: 700px; height: 30px; font-size: 11px; margin-left:200px; margin-top: 100px;">
	 <input type="button" id="btnNew" value="NEW" style="width: 70; height: 30" onclick="new_data()">
    <input type="button" id="btnEdit" value="EDIT" style="width: 70; height: 30" onclick="edit_data()">
    <input type="button" id="btnDelete" value="DELETE" style="width: 70; height: 30" onclick="del_data()">
    <input type="button" id="btnBrowse" value="BROWSE" style="width: 70; height: 30" onclick="browse_data()">
    <input type="button" id="btnPrev" value="PREV" style="width: 70; height: 30" onclick="get_content('prev')">
    <input type="button" id="btnNext" value="NEXT" style="width: 70; height: 30" onclick="get_content('next')">
    <input type="button" id="btnCetak" value="PRINT" style="width: 70; height: 30; margin-left: 50px" onclick="print_data()">
</div>

<div id="loading" style="float: right; width: 100px; margin-right: 30px; margin-top: 10px;" ></div>

<div id="form_input" class="main-work" style="float: left; width: 560px; margin-left: 200px">
	<table>
		<tr>
			<td height="30">Kode Job</td>
			<td>
				<select id="kode_job_form">
					<option></option>
					<option value="100">100</option>
					<option value="200">200</option>
					<option value="300">300</option>
					<option value="400">400</option>
					<option value="500">500</option>
				</select>
			</td>
			<td width="30"></td>
			<td>No Bukti</td>
			<td><input id="no_bukti_form" style="text-transform: uppercase; margin-left: 5px"></td>
		</tr>
		<tr>
			<td  height="30">Receive from</td>
			<td>
				<select id="receive_from_form" style="width: 150px;" onchange="select_value()">
					<option></option>
					<option value="SHIPPER">SHIPPER</option>
					<option value="COLOADER">COLOADER</option>
					<option value="AGENT">AGENT</option>
				</select>
			</td>
			<td width="30" height="30"></td>
			<td colspan="2" id="opt_receive_form"></td>
		</tr>
		<tr><td height="30">Date</td><td colspan="4"><input id="date_form" style="width: 150px;"></td></tr>
		<tr>
			<td style="vertical-align: top">Amount RP / USD</td>
			<td colspan="4"><textarea id="amount_form" style="text-transform: uppercase; min-width: 410px; max-width: 410px" rows="5"></textarea></td>
		</tr>
		<tr>
			<td style="vertical-align: top">In Payment of</td>
			<td colspan="4"><textarea id="payment_form" style="text-transform: uppercase; min-width: 410px; max-width: 410px; margin-top: 5px" rows="5"></textarea></td>
		</tr>
		<tr><td height="30">Kurs</td><td colspan="4"><input id="kurs_form" style="margin-top: 10px;width: 150px; text-align: right" onchange="gubahUang(this)" onkeyup="gubahUang(this)"></td></tr>
		<tr>
			<td colspan="5">
			<input id="id_aktif" style="width: 50px" type="hidden">
			<input id="oper_form" style="width: 50px" type="hidden">
			</td>
		</tr>
	</table>
</div>

<div style="float: right; width: 250px; margin-top: 20px; margin-right: 40px;">
<table id="table-dua"></table>
<div id="page-dua" style="font-size: 11px"></div>
</div>

<script type="text/javascript" >
$(document).ready(function () {
	get_table_satu()
	$('#opt_receive_form').html('<select id="select_receive_form" style="width: 100%;">'+
					'<option></option>'+
					'</select>');
	$('#date_form').datepicker({dateFormat: 'dd/mm/yy'});
	enabled_all();
	$('#form-table-satu').attr('hidden',true);
})

function browse_data(){
	$( "#form-table-satu" ).css('background-image', 'url("../public/images/bg-form.png")');
	$( "#form-table-satu" ).dialog(
	{
		position: "top",
		autoOpen: true,
		resizable: false,
		height: 'auto',
		width: 990,
		modal: true,
		title: "List Data",
	})
}

function get_table_satu(){
	var grid = $("#table_satu"), i;
	grid.jqGrid( {
      url:"<?php echo base_url('finance/c_official_receipt/get_table_satu')?>",
  		mtype : "post",
  		datatype: "json",
  		sortname: "id",
		sortorder: "desc",
   	colNames:['Kode Job', 'No Bukti','Receive From','','receive','Date','Amunt','In Payment of','Kurs',''], 
  		colModel:
   	[
			{name:'kode_job',index:'kode_job', width:60, align:"center",
				stype: 'select',
            searchoptions: { 
            	sopt:['eq'],
            	value: ":;100:100;200:200;300:300;400:400;500:500",
            }
			},
			{name:'no_bukti',index:'no_bukti', width:90, align:"center",
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'receive_from',index:'receive_from', width:150, align:"left",
				stype: 'select',
            searchoptions: { 
            	sopt:['eq'],
            	value: ":;SHIPPER:SHIPPER;COLOADER:COLOADER;AGENT:AGENT",
            }
			},	
			{name:'select_receive_id',index:'select_receive_id', width:15, align:"left",hidden: true},	
			{name:'select_receive',index:'select_receive', width:150, align:"left",
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},	
			{name:'date',index:'date', width:80, align:"center",
				stype:"text",
				searchoptions:{ 
					sopt:['eq','lt','gt'],
					dataInit: function(element) {
        				$(element).datepicker({dateFormat: 'dd/mm/yy'})
      			}
				}
			},	
			{name:'amount',index:'amount', width:200, align:"left",
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},	
			{name:'payment',index:'payment', width:200, align:"left",
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},	
			{name:'kurs',index:'kurs', width:80, align:"right",
				formatter: 'number',
    			formatoptions: { decimalSeparator: ".", thousandsSeparator: " ", decimalPlaces: 0, defaultValue: '',},
    			stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},	
			{name:'last_id',index:'last_id', width:20, align:"left",hidden: true},
		],
		shrinkToFit: false,
		multiselect:false,
		loadonce : false,
		cellEdit: false,
		rowNum:25,
		width: 950,
		height: 575,
		rowList:[10,20,30],
		viewrecords: true,
		rownumbers: true,
		gridview: true,
		modal: true,
      caption:false,
		pager: '#page-satu',

		loadComplete: function() {
			$("#cb_"+grid[0].id).hide();
			$(".ui-jqgrid-titlebar-close",$("#table_satu")[0].grid.cDiv).hide();
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
			$( '#form-table-satu' ).fadeOut('slow', function() 
			{
				$('input[id="id_aktif"]').val(rowid);
				get_content('pilih');
				$( "#form-table-satu" ).dialog('close');
			});
		}
		
   }).jqGrid('navGrid', '#page-satu', {edit: false, add: false,  del: false,},
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
		url:"<?php echo site_url('finance/c_official_receipt/get_content'); ?>",
		data:{ id : $('input[id="id_aktif"]').val(), mode : mode },
		success:function(response)
		{
			var data = $.parseJSON(response);
			
			$('#kode_job_form').val(data.kode_job);
			$('#no_bukti_form').val(data.no_bukti);
			$('#receive_from_form').val(data.receive_from);
			$('#date_form').val(data.date);
			$('#amount_form').val(data.amount);
			$('#payment_form').val(data.payment);
			$('#kurs_form').val(data.kurs);	
			$('#opt_receive_form').html('<select id="select_receive_form" style="width: 220px;" disabled="disabled">'+
					'<option value="'+data.select_receive+'">'+data.select_receive+'</option>'+
					'</select>');
			$('input[id="id_aktif"]').val(data.id);		
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
		}
	})
}

function del_data(){
	$( '#delete-form' ).fadeIn('slow', function() 
	{
		$(this).dialog(
		{
			position:'center',
			autoOpen: true,
			resizable: false,
			height: 'auto',
			width: 250,
			modal: true,
			title: "Delete Confirm",
		})
		$('#oper_form').val('del');
		$(this).html('<div style="margin: 20px 20px 30px 20px; font-size: 11px">Are you sure delete this data ?</div><hr>'+
		'<input type="button" value="DELETE" style="width: 80; height: 25; font-size: 11px; float: left" onclick="save_data()">'+
		'<input type="button" value="CANCEL" style="width: 80; height: 25; font-size: 11px; float: right" onclick="$(\'#delete-form\').dialog(\'close\')">')
	})
}

function save_data(){
	var Data = 
	{
		oper : $('#oper_form').val(),
		id : $('#id_aktif').val(),
		kode_job : $('#kode_job_form').val(),
		no_bukti : $('#no_bukti_form').val().toUpperCase(),
		receive_from : $('#receive_from_form').val(),
		select_receive: $('#select_receive_form').val(),
		date : $('#date_form').val(),
		amount : $('#amount_form').val().toUpperCase(),
		payment : $('#payment_form').val().toUpperCase(),
		kurs : $('#kurs_form').val().replace(/\ /g,''),
		region_id : '<?php echo $this->session->userdata("region_id") ?>',
	}
	$.ajax
	({
		type:"POST",
		url:"<?php echo site_url('finance/c_official_receipt/manage_data'); ?>",
		data: Data,
		success:function(data)
		{
			var data = $.parseJSON(data);
			$( '#delete-form' ).dialog('close');
			$('#table-satu').jqGrid('setGridParam',{ datatype: "json", }).trigger("reloadGrid");
			enabled_all();
			if(data.mode=='del')
				get_content('last');
				show_messages('<div id="content-msg">Data has been deleted</div>');
			if(data.mode=='add')
				get_content('last');
				show_messages('<div id="content-msg">Insert new data success</div>');
			if(data.mode=='edit')
				get_content('pilih');
				show_messages('<div id="content-msg">Data updated</div>');
		}
	})
}

function cancel_data(){
	enabled_all();
	get_content('pilih');
}

function new_data(){
	disabled_all();
	$('#oper_form').val('add');
	$('#id_form').val(eval($('#last_id_form').val()) + eval(1));
	$('#kode_job_form').val('');
	$('#no_bukti_form').val('');
	$('#receive_from_form').val('');
	$('#date_form').val('');
	$('#amount_form').val('');
	$('#payment_form').val('');
	$('#kurs_form').val('');
	$('#opt_receive_form').html('<select id="select_receive_form" style="width: 100%;">'+
					'<option></option>'+
					'</select>');
}

function edit_data(){
	$('#oper_form').val('edit');
	disabled_all();
}

function select_value(){
	var value = $('#receive_from_form').val();
	$.ajax
	({
		type:"POST",
		url:"<?php echo site_url('finance/c_official_receipt/select_value'); ?>",
		data: { value: value },
		success:function(data)
		{
			var data = $.parseJSON(data);
			$('#opt_receive_form').html(data.dropdown);
		}
	})
}

function table_dua(){
	var grid = $("#table-dua"), i;
	grid.jqGrid( {
      url:"<?php echo base_url('finance/c_offcial_receipt/display_table_dua')?>",
  		mtype : "post",
  		datatype: "json",
  		sortname: "sp_id",
		sortorder: "desc",
   	colNames:['Job No', 'Job Date','Shipper'], 
  		colModel:
   	[
			{name:'kode_ref',index:'kode_ref', width:120, align:"center"} ,
			{name:'job_date',index:'job_date', width:90, align:"center"},
			{name:'note',index:'note', width:150, align:"left"},	
		],
		shrinkToFit: false,
		multiselect:true,
		loadonce : true,
		cellEdit: false,
		rowNum:10,
		width: 265,
		height: 405,
		rowList:[10,20,30],
	//	viewrecords: true,
		rownumbers: true,
		gridview: true,
		modal: true,
      caption:false,
		pager: '#page-dua',

		loadComplete: function() {
			$("#cb_"+grid[0].id).hide();
			$(".ui-jqgrid-titlebar-close",$("#table-dua")[0].grid.cDiv).hide();
			
			var akhir = grid.getGridParam("reccount");
			var rowid = grid.getDataIDs();
			var id = rowid.shift()
    		getInvoice(id);    				
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
    			getInvoice(rowid);	  		
    			$('#btnNew').attr('disabled',false);
    			$('#btnEdit').attr('disabled',true);
     		}
        	else
        	{
        		var cbs = $("tr.jqgrow > td > input.cbox:disabled", grid[0]);  		
    			cbs.attr("disabled", false);  
    			$('#btnNew').attr('disabled',true); 			
        	}
		}
		
   }).jqGrid('navGrid', '#page-dua', {edit: false, add: false,  del: false,},
   	{ },
   	{ },
   	{ },
   	{ modal: true, height: "auto", width: "auto", closeAfterSearch : true, caption: "Fine Data ..",},
   	{ }
   );

}

function enabled_all(){
	$( '#btnNew' ).attr('disabled',false);
	$( '#btnEdit' ).attr('disabled',false);
	$( '#btnNext' ).attr('disabled',false);
	$( '#btnPrev' ).attr('disabled',false);
	$( '#btnDelete' ).attr('disabled',false).val('DELETE').attr('onClick','del_data()');
	$( '#btnBrowse' ).attr('disabled',false).val('BROWSE').attr('onClick','browse_data()');
	
	$('#kode_job_form').attr('disabled',true);
	$('#no_bukti_form').attr('disabled',true);
	$('#receive_from_form').attr('disabled',true);
	$('#select_receive_form').attr('disabled',true);
	$('#date_form').attr('disabled',true);
	$('#amount_form').attr('disabled',true);
	$('#payment_form').attr('disabled',true);
	$('#kurs_form').attr('disabled',true);	
}

function disabled_all(){
	$( '#btnNew' ).attr('disabled',true);
	$( '#btnEdit' ).attr('disabled',true);
	$( '#btnNext' ).attr('disabled',true);
	$( '#btnPrev' ).attr('disabled',true);
	$( '#btnDelete' ).attr('disabled',false).val('CANCEL').attr('onClick','cancel_data()');
	$( '#btnBrowse' ).attr('disabled',false).val('SAVE').attr('onClick','save_data()');
	
	$('#kode_job_form').attr('disabled',false);
	$('#no_bukti_form').attr('disabled',false);
	$('#receive_from_form').attr('disabled',false);
	$('#select_receive_form').attr('disabled',false);
	$('#date_form').attr('disabled',false);
	$('#amount_form').attr('disabled',false);
	$('#payment_form').attr('disabled',false);
	$('#kurs_form').attr('disabled',false);
}

function rubahUang(num) {
	return num.toString().split('').reverse().join('').replace(/(?=\d*\.?)(\d{3})/g,'$1 ').split('').reverse().join('').replace(/^[\ ]/,'')
}

function gubahUang(input) {
    var num = input.value.replace(/\ /g,'');
    if(!isNaN(num)){
    	if(num.indexOf('.') > -1){
    		num = num.split('.');
    		num[0] = num[0].toString().split('').reverse().join('').replace(/(?=\d*\.?)(\d{3})/g,'$1 ').split('').reverse().join('').replace(/^[\ ]/,'');
    		if(num[1].length > 0){
				show_messages('<div id="content-msg">Desimal tidak diperbolehkan</div>');
    			num[1] = num[1].substring(0,num[1].length-2);
    		} 
    		input.value = num[0]+'.'+num[1];
    	} else { 
    	input.value = num.toString().split('').reverse().join('').replace(/(?=\d*\.?)(\d{3})/g,'$1 ').split('').reverse().join('').replace(/^[\ ]/,'') };
    }
    else
    { 
		show_messages('<div id="content-msg">Hanya diperbolehkan memasukan angka</div>');
    	input.value = input.value.substring(0,input.value.length-1); 
    }
}

function print_data(){

	$( "#output" ).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
	$( "#output" ).dialog(
	{
		autoOpen: true,
		resizable: false,
		height: 640,
		width: 990,
		modal: true,
		title: "OFFICIAL RECEIPT",
		buttons : {  },
		close: function()  
		{	 
			$(this).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
		}
	});
	
	$.ajax
	({
			type: "POST",
			url : "<?php echo base_url('finance/c_official_receipt/print_data')?>",
			data:  
			{ 
				receives			:	$("select[id='receive_from_form']").val(),
				code				:	$("select[id='select_receive_form']").val(),
				amount	 		: 	$("textarea[id='amount_form']").val(),
				payment	 		: 	$("textarea[id='payment_form']").val(),
				date	 			: 	$("input[id='date_form']").val(),
				kurs	 			: 	$("input[id='kurs_form']").val(),
			},
			success: function(data)
			{
				$('#output').html('<iframe src="<?php echo base_url("finance/c_official_receipt/print_data") ?>" width="960px" height="600"></iframe>');
			},
			error:function(XMLHttpRequest)
			{

				$('#output').html(XMLHttpRequest.responseText)
			}
	})
}

</script>

<?php

/*
			
			<!--
			<input id="id_awal" style="width: 50px" type="text">
			<input id="id_akhir" style="width: 50px" type="text">
			<input id="last_id_form" style="width: 50px" type="text">
			-->
function get_data(input){
	
	curPage = $("#table-satu").getGridParam('page');
	var nextPage = eval(curPage) + eval(1);
	var prevPage = eval(curPage) - eval(1);	
	if(input=='next'){
		$('#btnPrev').attr('disabled',false);
		rowid = eval($( '#id_form' ).val()) - eval(1);
		idakhir = eval($( '#id_akhir' ).val()) - eval(1);	
		if(rowid==idakhir){						
			$("#table-satu").jqGrid().trigger('reloadGrid', [{ page: nextPage }]);
		}
				
	} 
	else if(input=='prev'){
		$('#btnNext').attr('disabled',false);
		rowid = eval($( '#id_form' ).val()) + eval(1);
		idawal = eval($( '#id_awal' ).val()) + eval(1);	
		if(rowid==idawal){						
			$("#table-satu").jqGrid().trigger('reloadGrid', [{ page: prevPage }]);
		}
	}
	else if(input=='pilih'){
		rowid = $( '#id_form' ).val();					
		$('#table-satu').jqGrid('setGridParam', {datatype: 'json'}).trigger('reloadGrid', [{ page: curPage }]);
	}
	get_data_content(rowid);
}

	var grid = $("#table-satu"), i;
	var job_id = grid.jqGrid('getCell',rowid,'job_id');
	var lastId  = $('#last_id_form').val(grid.jqGrid('getCell', rowid,'last_id'));
	
	idawal = $( '#id_awal' ).val();
	idakhir = $( '#id_akhir' ).val();
	
	if(rowid==idawal)
	$('#btnPrev').attr('disabled',true);
	if(rowid==idakhir)
	$('#btnNext').attr('disabled',true);
	
	$('#id_form').val(rowid);
	$('#kode_job_form').val(grid.jqGrid('getCell', rowid,'kode_job'));
	$('#no_bukti_form').val(grid.jqGrid('getCell', rowid,'no_bukti'));
	$('#receive_from_form').val(grid.jqGrid('getCell', rowid,'receive_from'));
	$('#date_form').val(grid.jqGrid('getCell', rowid,'date'));
	$('#amount_form').val(grid.jqGrid('getCell', rowid,'amount'));
	$('#payment_form').val(grid.jqGrid('getCell', rowid,'payment'));
	$('#kurs_form').val(rubahUang(grid.jqGrid('getCell', rowid,'kurs')));	
	$('#opt_receive_form').html('<select id="select_receive_form" style="width: 220px;" disabled="disabled">'+
					'<option value="'+grid.jqGrid('getCell', rowid,'select_receive_id')+'">'+
					grid.jqGrid('getCell', rowid,'select_receive')+
					'</option>'+
					'</select>');
	if(rowid==''){
		$('#kode_job_form').val('');
		$('#no_bukti_form').val('');
		$('#receive_from_form').val('');
		$('#date_form').val('');
		$('#amount_form').val('');
		$('#payment_form').val('');
		$('#kurs_form').val('');	
		$('#opt_receive_form').html('<select id="select_receive_form" style="width: 220px;" disabled="disabled">'+
					'<option value=" ">'+
					'</option>'+
					'</select>');
	}
*/

?> 
















