<div id="msg"></div>
<div id="output"></div>
			
<div class="fix clearfix"></div>
<div style="float: left; width: 700px; height: 30px; font-size: 11px; margin-left:200px; margin-top: 50px;">
	 <input type="button" id="btnNew" value="NEW" style="width: 70; height: 30" onclick="new_data()">
    <input type="button" id="btnEdit" value="EDIT" style="width: 70; height: 30" onclick="edit_data()">
    <input type="button" id="btnDelete" value="DELETE" style="width: 70; height: 30" onclick="del_data()">
    <input type="button" id="btnBrowse" value="BROWSE" style="width: 70; height: 30" onclick="browse_data()">
    <input type="button" id="btnPrev" value="PREV" style="width: 70; height: 30" onclick="get_content('prev')">
    <input type="button" id="btnNext" value="NEXT" style="width: 70; height: 30" onclick="get_content('next')">
    <input type="button" id="btnCetak" value="PRINT" style="width: 70; height: 30; margin-left: 50px" onclick="print_data()">
</div>
<style type="text/css">
td{
/*	border: 1px solid #999; */
}
</style>
<div id="loading" style="float: right; width: 100px; margin-right: 30px; margin-top: 10px;" ></div>
<?php 
if($this->uri->segment(3)=='sepuluh') {
	$no_seri = '010.';
	$seri = '10';	
} else {
	$no_seri = '040.';
	$seri = '40';
}
?>
<div id="form_input" class="main-work" style="float: left; width: 550px; height: 380px; margin-left: 200px">
	<table style="width: 550px">
		<tr>
			<td width="120" height="30">Nomer SERI <?php echo $no_seri ?></td>
			<td colspan="3">
				<input id="no_seri_form" style="text-transform: uppercase; width:180px">
				<?php 
				$cur = array("USD"=>"USD","RP."=>"RP.");
				echo form_dropdown("currency",$cur,"","id='currency' style='float: right; min-width: 120px; margin-right: 10px' disabled='disabled'");
				?>
			</td>
			<td width="20"></td>
		</tr>
		<tr>
			<td height="30">Tanggal</td>
			<td><input id="date_form" style="width: 80px;"  onchange="get_faktur_pajak()"></td>
			<td align="right">Kurs</td>
			<td><input id="kurs_form" style="width: 80px; text-align: right; margin-left: 10px" onchange="gubahUang(this)" onkeyup="gubahUang(this)"></td>
			<td></td>
		</tr>
		<tr>
			<td height="30">No KMK</td>
			<td><input id="no_kmk_form" style="width: 180px;"></td>
			<td align="right">Tanggal KMK</td>
			<td><input id="date_kmk_form" style="width: 80px; text-align: right; margin-left: 10px"></td>
			<td></td>
		</tr>
		
		<tr>
			<td height="30">No Invoice</td>
			<td>
			<?php 
			 echo $option_inv 
			?>
			</td>
			<td align="right">Tanggal Invoice</td>
			<td><input id="date_invoice_form" style="width: 80px; text-align: right; margin-left: 10px"></td>
			<td></td>
		</tr>
		
		<tr>
			<td height="30">Shipper</td>
			<td colspan="2"><input id="shipper_form" style="width: 100%;"></td>
			<td align="center" colspan="2"><input type="checkbox" id="desimal_form"> <label>Desimal</label></td>
		</tr>
		
		<tr>
			<td height="30">Tanda Tangan</td>
			<td><input id="tanda_tangan_form" style="width: 180px;"></td>
			<td align="right">Com F. Pajak</td>
			<td><input id="cfp_form" style="width: 80px; text-align: right; margin-left: 10px"></td>
			<td></td>
		</tr>
		
		<tr>
			<td colspan="5">
			<input id="id_aktif" style="width: 50px" type="hidden">
			<input id="oper_form" style="width: 50px" type="hidden">
			<input id="kode_ref_form" style="width: 250px" type="hidden">
			</td>
		</tr>
	</table>
	
	<table id="table-dua"></table>
	<div id="page-detail" style="font-size: 11px"></div>
</div>

<div style="clear: both"></div>

<div id="browse-form">
<table id="table-satu"></table>
<div id="page-satu" style="font-size: 11px"></div>
</div>

<div id="delete-form"></div>

<script type="text/javascript">
$(document).ready(function () {
	get_table_satu();	
	get_table_dua()
	$('#opt_receive_form').html('<select id="date_invoice_form" style="width: 100%;">'+
					'<option></option>'+
					'</select>');
	$('#date_form').datepicker({dateFormat: 'dd/mm/yy'});
	$('#date_kmk_form').datepicker({dateFormat: 'dd/mm/yy'});
	$('#date_invoice_form').datepicker({dateFormat: 'dd/mm/yy'});	
	
	enabled_all();
	$('#no_invoice_form').live('change', function(){
		insert_shipper_data()
	})
	$('#browse-form').attr('hidden',true);
	
})

function browse_data(){
	$( '#browse-form' ).dialog(
	{
		position:'center',
		autoOpen: true,
		resizable: false,
		height: 'auto',
		width: 980,
		modal: true,
		title: "List Data",
	})
	$("#table-satu").jqGrid('setGridParam',{ datatype: 'json', postData: {seri: '<?php echo $seri ?>'}}).trigger('reloadGrid');
	get_table_satu();
}

function get_table_satu(){
	var grid = $("#table-satu"), i;
	grid.jqGrid( {
      url:"<?php echo base_url('finance/c_faktur_pajak/get_table_satu')?>",
  		mtype : "post",
  		datatype: "json",
  		postData: {seri: '<?php echo $seri ?>'},
  		sortname: "id",
		sortorder: "desc",
   	colNames:['', 'No Seri','Tanggal','Shipper','No Invoice','Date Invoice','No kmk','Tgl kmk','','','','',''], 
  		colModel:
   	[
			{name:'seri',index:'seri', width:20, align:"center",hidden:true} ,
			{name:'no_seri',index:'no_seri', width:200, align:"left"},
			{name:'date',index:'date', width:80, align:"center"},	
			{name:'shipper',index:'shipper', width:150, align:"left",hidden: true},	
			{name:'no_invoice',index:'no_invoice', width:200, align:"left"},	
			{name:'date_invoice',index:'date_invoice', width:80, align:"center"},	
			{name:'no_kmk',index:'no_kmk', width:200, align:"left"},	
			{name:'date_kmk',index:'date_kmk', width:80, align:"center"},	
			{name:'kurs',index:'kurs', width:80, align:"right",hidden: true,},	
			{name:'desimal',index:'desimal', width:80, align:"right",hidden: true},
			{name:'tanda_tangan',index:'tanda_tangan', width:80, align:"right",hidden: true},
			{name:'cfp',index:'cfp', width:80, align:"right",hidden: true},
			{name:'last_id',index:'last_id', width:20, align:"left",hidden: true},
		],
		shrinkToFit: false,
		multiselect:true,
		loadonce : false,
		cellEdit: false,
		rowNum:20,
		width: 950,
		height: 525,
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
			$( '#browse-form' ).fadeOut('slow', function() 
			{
				$('input[id="id_aktif"]').val(rowid);
				get_content('pilih');
				$( "#browse-form" ).dialog('close');
			});
		}
		
   }).jqGrid('navGrid', '#page-satu', {edit: false, add: false,  del: false,},
   	{ },
   	{ },
   	{ },
   	{ modal: true, height: "auto", width: "auto", closeAfterSearch : true, caption: "Find Data ..",},
   	{ }
   );

}

function get_content(mode){
	$.ajax
	({
		type:"POST",
		url:"<?php echo site_url('finance/c_faktur_pajak/get_content'); ?>",
		data:{ id : $('input[id="id_aktif"]').val(), seri: '<?php echo $seri ?>', mode : mode },
		success:function(response)
		{
			var data = $.parseJSON(response);
			var checked = data.desimal;
			$('#no_seri_form').val(data.no_seri);
			$('#no_invoice_form').val(data.no_invoice);
			$('#date_form').val(data.date);
			$('#date_invoice_form').val(data.date_invoice);
			$('#no_kmk_form').val(data.no_kmk);
			$('#date_kmk_form').val(data.date_kmk);
			$('#kurs_form').val(data.kurs);
			$('#tanda_tangan_form').val(data.tanda_tangan);
			$('#cfp_form').val(data.cfp);
			$('#shipper_form').val(data.shipper);
			$('#kode_ref_form').val(data.kode_ref);
			$('#currency').val(data.currency);
			
			$("#table-dua").jqGrid("setGridParam", { mtype : "post", datatype: 'json', postData: { id_fp : data.id }, sortname: "id", sortorder: "asc",}).trigger("reloadGrid");
			
			if(data.desimal=='true'){
				$('#desimal_form').attr('checked',true);
			} else {
				$('#desimal_form').attr('checked',false);
			}
			
			$('input[id="id_aktif"]').val(data.id);		
			if(data.id==data.id_first){
				$('input[id="btnNext"]').attr('disabled',false);
				$('input[id="btnPrev"]').attr('disabled',true);
			} else if(data.id==data.id_last){
				$('input[id="btnNext"]').attr('disabled',true);
				$('input[id="btnPrev"]').attr('disabled',false);
			} else if(data.id== '' ){
				$('input[id="btnNext"]').attr('disabled',true);
				$('input[id="btnPrev"]').attr('disabled',true);
			} else {
				$('input[id="btnNext"]').attr('disabled',false);
				$('input[id="btnPrev"]').attr('disabled',false);
			}
		}
	})
}

function insert_shipper_data(){
	var no_inv = $("select[id='no_invoice_form']").val();
	var shipper = $("input[id='shipper_form']").val();
	var tgl_inv = $("input[id='date_invoice_form']").val();
//	if(no_inv != '' && shipper == '' && tgl_inv == ''){
	if(no_inv != '' ){
		$.ajax
		({
			type: "POST",
			url : "<?php echo base_url('finance/c_faktur_pajak/insert_shipper_data')?>",
			data:  
			{ 
				no_inv	: 	no_inv,
			},
			success: function(response)
			{
				var data = $.parseJSON(response)
				$("input[id='kode_ref_form']").val(data.kode_ref);
				$("input[id='shipper_form']").val(data.shipper);
				$("input[id='date_invoice_form']").val(data.tgl_inv);
				$("input[id='cfp_form']").val(data.shipper);
				if(data.messages!='')
					show_messages('data.messages');
			},
			error:function(XMLHttpRequest)
			{

			}
		})
	}
}

function new_data(){
	disabled_all();
	$('#oper_form').val('add');
	$('#id_form').val(eval($('#last_id_form').val()) + eval(1));
	$('#kode_job_form').val('');
	$('#no_seri_form').val('<?php echo $no_seri ?>');
	$('#no_invoice_form').val('');
	$('#date_invoice_form').val('');
	$('#date_form').val('');
	$('#no_kmk_form').val('');
	$('#date_kmk_form').val('');
	$('#kurs_form').val('');
	$('#shipper_form').val('');
	$('#cfp_form').val('');
	$('#tanda_tangan_form').val('');
	$('#desimal_form').attr('checked',false);
	$('#table-dua').jqGrid("clearGridData", true);
}

function edit_data(){
	$('#oper_form').val('edit');
	disabled_all();
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
		id : $('input[id="id_aktif"]').val(),
		seri	: '<?php echo $seri ?>',
		no_seri	: $('#no_seri_form').val().toUpperCase(),
		no_invoice	: $('#no_invoice_form').val().toUpperCase(),
		date_invoice	: $('#date_invoice_form').val(),
		date	: $('#date_form').val(),
		no_kmk	: $('#no_kmk_form').val().toUpperCase(),
		date_kmk	: $('#date_kmk_form').val(),
		kurs	: $('#kurs_form').val().replace(/\ /g,''),	
		shipper	: $('#shipper_form').val(),	
		desimal	: $('#desimal_form').attr('checked'),	
		tanda_tangan	: $('#tanda_tangan_form').val().toUpperCase(),	
		cfp	: $('#cfp_form').val().toUpperCase(),
		region_id : '<?php echo $this->session->userdata("region_id") ?>',
		kode_ref : $('input[id="kode_ref_form"]').val(),
		currency : $('select[id="currency"]').val(),
	}
	$.ajax
	({
		type:"POST",
		url:"<?php echo site_url('finance/c_faktur_pajak/manage_data'); ?>",
		data: Data,
		success:function(data)
		{
			var data = $.parseJSON(data);
			enabled_all();
			/*
			if(data.mode=='del')
				get_content('last');
				show_messages('Data has been deleted');
				$('#delete-form').dialog('close');
			if(data.mode=='add')
				get_content('last');
				show_messages('Insert new data success');
			if(data.mode=='edit')
				get_content('pilih');
				show_messages('Data updated');
			*/
		}
	})
}

function cancel_data(){
	enabled_all();
	get_content('pilih');
}

function get_table_dua(){
	var grid = $("#table-dua"), i;
	grid.jqGrid( {
      url:"<?php echo base_url('finance/c_faktur_pajak/get_table_dua')?>",
  		mtype : "post",
  		datatype: "json",
  		sortname: "id",
		sortorder: "asc",
   	colNames:['Nama Barang / Jasa','Curreny', 'Jumlah',], 
  		colModel:
   	[
			{name:'nama_jasa',index:'nama_jasa', width:300, align:"left"} ,
			{name:'currency',index:'currency', width:60, align:"center"} ,
			{name:'jumlah',index:'jumlah', width:70, align:"right",
				formatter: 'number',
    			formatoptions: { decimalSeparator: ".", thousandsSeparator: " ", decimalPlaces: 2, defaultValue: ''}
			},	
		],
		shrinkToFit: false,
		multiselect:true,
		loadonce : false,
		cellEdit: false,
		rowNum:10,
		width: 525,
		height: 125,
		rowList:[10,20,30],
		viewrecords: true,
		rownumbers: true,
		gridview: true,
		modal: true,
      caption:false,
		pager: '#page-detail',
		editurl:'manage_data_dua',

		loadComplete: function() {
			$("#cb_"+grid[0].id).hide();
			$(".ui-jqgrid-titlebar-close",$("#table-dua")[0].grid.cDiv).hide();
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
    			//getInvoice(rowid);	  		
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
		
   })
   grid.jqGrid('navGrid', '#page-detail', {edit: false, add: false,  del: true, refresh: false, <?php echo 'search: false' ?>},
   	{ },
   	{ },
   	{ },
   	{ modal: true, height: "auto", width: "auto", closeAfterSearch : true, caption: "Fine Data ..",},
   	{ }
   )
	grid.navButtonAdd('#page-detail',{ 
			caption:"", 
			buttonicon:"ui-icon-disk", 
			onClickButton: tarik_data, 
			position: "last", 
			title:"Tarik data faktur pajak", 
			cursor: "pointer"
	})
}

function enabled_all(){
	$( '#btnNew' ).attr('disabled',false);
	$( '#btnEdit' ).attr('disabled',false);
	$( '#btnNext' ).attr('disabled',false);
	$( '#btnPrev' ).attr('disabled',false);
	$( '#btnDelete' ).attr('disabled',false).val('DELETE').attr('onClick','del_data()');
	$( '#btnBrowse' ).attr('disabled',false).val('BROWSE').attr('onClick','browse_data()');
	
	$('#no_seri_form').attr('disabled',true);
	$('#no_invoice_form').attr('disabled',true);
	$('#date_invoice_form').attr('disabled',true);
	$('#date_form').attr('disabled',true);
	$('#no_kmk_form').attr('disabled',true);
	$('#date_kmk_form').attr('disabled',true);
	$('#kurs_form').attr('disabled',true);	
	$('#shipper_form').attr('disabled',true);	
	$('#desimal_form').attr('disabled',true);	
	$('#tanda_tangan_form').attr('disabled',true);	
	$('#cfp_form').attr('disabled',true);	
	$('#currency').attr('disabled',true);	
}

function disabled_all(){
	$( '#btnNew' ).attr('disabled',true);
	$( '#btnEdit' ).attr('disabled',true);
	$( '#btnNext' ).attr('disabled',true);
	$( '#btnPrev' ).attr('disabled',true);
	$( '#btnDelete' ).attr('disabled',false).val('CANCEL').attr('onClick','cancel_data()');
	$( '#btnBrowse' ).attr('disabled',false).val('SAVE').attr('onClick','save_data()');

	$('#no_seri_form').attr('disabled',false);
	$('#no_invoice_form').attr('disabled',false);
	$('#date_invoice_form').attr('disabled',false);
	$('#date_form').attr('disabled',false);
	$('#no_kmk_form').attr('disabled',false);
	$('#date_kmk_form').attr('disabled',false);
	$('#kurs_form').attr('disabled',false);
	$('#shipper_form').attr('disabled',false);
	$('#desimal_form').attr('disabled',false);
	$('#tanda_tangan_form').attr('disabled',false);	
	$('#cfp_form').attr('disabled',false);
	$('#currency').attr('disabled',false);
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
	$( "#output" ).dialog({
		autoOpen: true,
		resizable: false,
		height: 640,
		width: 990,
		modal: true,
		title: "FAKTUR PAJAK",
		buttons : {  },
		close: function()  
		{	 
			$(this).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
		}
	});
	$( "#output-2" ).dialog({
		autoOpen: true,
		resizable: false,
		height: 640,
		width: 990,
		modal: true,
		title: "FAKTUR PAJAK",
		buttons : {  },
		close: function()  
		{	 
			$(this).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
		}
	});
	var id_fp = $('input[id="id_aktif"]').val();
	$.ajax
	({
			type: "POST",
			url : "<?php echo base_url('finance/c_faktur_pajak/print_data')?>/"+id_fp,
			data:  
			{ 
				receives			:	$("select[id='receive_from_form']").val(),
				code				:	$("select[id='select_receive_form']").val(),
				amount	 		: 	$("textarea[id='amount_form']").val(),
				payment	 		: 	$("textarea[id='payment_form']").val(),
				date	 			: 	$("input[id='date_form']").val(),
				kurs	 			: 	$("input[id='kurs_form']").val(),
				id					:  id_fp,
			},
			success: function(data)
			{
				$('#output').html('<iframe src="<?php echo base_url("finance/c_faktur_pajak/print_data") ?>/'+id_fp+'" width="960px" height="600"></iframe>');			
			},
			error:function(XMLHttpRequest)
			{

				$('#output').html(XMLHttpRequest.responseText)
			}
	})
}

function get_faktur_pajak(){
	$.ajax
	({
			type: "POST",
			url : "<?php echo base_url('finance/c_faktur_pajak/get_faktur_pajak')?>",
			data:  
			{ 
				date	 			: 	$("input[id='date_form']").val(),
			},
			success: function(response)
			{
				var data = $.parseJSON(response)
				$("input[id='kurs_form']").val(data.kurs);
				$("input[id='no_kmk_form']").val(data.no_kmk);
				$("input[id='date_kmk_form']").val(data.tgl_kmk);
				if(data.messages!='')
					show_messages('<div id="content-msg">'+data.messages+'</div>');
			},
			error:function(XMLHttpRequest)
			{

			}
	})
}

function tarik_data(){
	var id_fp = $('input[id="id_aktif"]').val();
	var shipper = $('input[id="shipper_form"]').val();
	var kode_ref = $('input[id="kode_ref_form"]').val();
	var currency = $('select[id="currency"]').val();
	var number = $('select[id="no_invoice_form"]').val();
	
	if (id_fp == '' ) {
		alert('Silahkan save data dulu');
		return false;
	}
	
	$.ajax
	({
		type: "POST",
		url : "<?php echo base_url('finance/c_faktur_pajak/tarik_data')?>",
		data:  
		{ 
			kode_ref 	: kode_ref,
			shipper  	: shipper,
			currency  	: currency,
			number  		: number,
			id_fp : id_fp,
		},
		success: function(XMLHttpRequest)
		{
			$("#table-dua").jqGrid("setGridParam", { datatype: 'json' }).trigger("reloadGrid");
		},
		error:function(XMLHttpRequest)
		{
			return false;
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

function get_data_content(rowid){
	var grid = $("#table-satu"), i;
	var job_id = grid.jqGrid('getCell',rowid,'job_id');
	var lastId  = $('#last_id_form').val(grid.jqGrid('getCell', rowid,'last_id'));
	
	idawal = $( '#id_awal' ).val();
	idakhir = $( '#id_akhir' ).val();
	
	if(rowid==idawal)
	$('#btnPrev').attr('disabled',true);
	if(rowid==idakhir)
	$('#btnNext').attr('disabled',true);
	
	$('#id_form').val(rowid)
	$('#no_seri_form').val(grid.jqGrid('getCell', rowid,'no_seri'));
	$('#no_invoice_form').val(grid.jqGrid('getCell', rowid,'no_invoice'));
	$('#date_form').val(grid.jqGrid('getCell', rowid,'date'));
	$('#date_invoice_form').val(grid.jqGrid('getCell', rowid,'date_invoice'));
	$('#no_kmk_form').val(grid.jqGrid('getCell', rowid,'no_kmk'));
	$('#date_kmk_form').val(grid.jqGrid('getCell', rowid,'date_kmk'));
	$('#kurs_form').val(rubahUang(grid.jqGrid('getCell', rowid,'kurs')));	
	$('#desimal_form').attr('checked',grid.jqGrid('getCell', rowid,'kurs'));
	$('#tanda_tangan_form').val(grid.jqGrid('getCell', rowid,'tanda_tangan'));
	$('#cfp_form').val(grid.jqGrid('getCell', rowid,'cfp'));
	$('#shipper_form').val(grid.jqGrid('getCell', rowid,'shipper'));
	
	if(rowid==''){
		$('#no_seri_form').val('');
		$('#no_invoice_form').val('');
		$('#date_form').val('');
		$('#date_invoice_form').val('');
		$('#no_kmk_form').val('');
		$('#date_kmk_form').val('');
		$('#kurs_form').val('');
		$('#desimal_form').val('');
		$('#tanda_tangan_form').val('');
		$('#cfp_form').val('');
		$('#shipper_form').val('');
	}
}

*/
?>















