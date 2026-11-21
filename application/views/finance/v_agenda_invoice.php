<style type="text/css">
 #lbl_invoice {
 	font-size: 11px;
 	vertical-align: top;	
 	padding-right: 50px;
 }
 #lbl_cn {
 	font-size: 11px;
 	vertical-align: top;	
 	padding-right: 50px;
 }
 #kode_form {
	min-width: 300px;
	max-width: 300px;
 }
</style>

<div id="form-agenda" style="font-size: 11px"></div>
<div id="data-form" style="font-size: 11px"></div>
<div id="data-pdf" style="font-size: 11px"></div>

<input id="query_<?php echo $this->session->userdata('user_id') ?>" type="hidden" >

<script type="text/javascript">
$(function (){
	$("#form-agenda").fadeIn('slow',function(){
		$(this).css('font-size','11px').css('margin','10px');
		$(this).dialog({
			modal:true,
			height:280,
			width: 480,
			title: "AGENDA INVOICE",
			resizable: false,
		});
		$(this).html('<input type="checkbox" name="invoice_type" table="inv_to" colum="Invoice" id="chk_invoice" onchange="value_chkbox($(this).attr(\'colum\'),\'chk_cn\')"> <label id="lbl_invoice" onclick="set_checked(\'chk_invoice\',\'chk_cn\')">INVOICE / DEBIT NOTE</label>'+
    	'<input type="checkbox" name="invoice_type" table="cn_to" colum="Credite Note" id="chk_cn" onchange="value_chkbox($(this).attr(\'colum\'),\'chk_invoice\')"> <label id="lbl_cn" onclick="set_checked(\'chk_cn\',\'chk_invoice\')">CREDIT NOTE</label>'+
    	'<table style="margin-top: 30px; margin-bottom: 30px">'+
		'<tr>'+
			'<td width="120" height="30">Mulai</td>'+
			'<td height="30"><input id="mulai_tgl_form" style="width: 80"></td>'+
			'<td height="30"></td>'+
			'<td height="30">Sampai</td>'+
			'<td height="30"><input id="smp_dgn_form" style="width: 80"></td>'+
		'</tr>'+
		'<tr>'+
			'<td height="30">INVOICE TO</td>'+
			'<td colspan="4" height="30">'+
				'<select id="invoice_to" style="width: 150" onchange="select_value()">'+
					'<option value=""></option>'+
					'<option value="Agent">AGENT</option>'+
					'<option value="Coloader">COLOADER</option>'+
					'<option value="Shipper">SHIPPER</option>'+
					'<option value="Consignee">CONSIGNEE</option>'+
				'</select>'+
			'</td>'+
		'</tr>'+
		'<tr><td>KODE</td><td id="select_kode" colspan="4"></td></tr>'+
	'</table><hr><input type="button" value="PROSES" style="float: right; width: 80; height: 30" onclick="proses()">')	
	$('#mulai_tgl_form').datepicker({dateFormat: 'dd/mm/yy'});
	$('#smp_dgn_form').datepicker({dateFormat: 'dd/mm/yy'});
	$('#select_kode').html('<select id="kode_form">'+
					'<option></option>'+
					'</select>');	
	})
})

function select_value()
{
	var value = $('#invoice_to').val().toUpperCase();
	$.ajax
	({
		type:"POST",
		url:"<?php echo site_url('finance/c_agenda_invoice/select_value'); ?>",
		data: { value: value },
		success:function(data)
		{
			var data = $.parseJSON(data);
			$('#select_kode').html(data.dropdown);
		}
	})
}

function set_checked(input,char)
{
	var chk = $("#"+input).attr('checked');
	if(chk==true){
		$("#"+input).attr('checked',false);		
	} else {
		$("#"+input).attr('checked',true);
	}
	value_chkbox($("#"+input).attr('colum'),char)
}

function value_chkbox(input,char)
{
	var chk = $("input[name='invoice_type']:checked");
	$("#"+char).attr('checked',false);	
	return chk.value=input;
}

function colum_name()
{
	return $("input[name='invoice_type']:checked").attr('colum');
}

function colum_shipper(){
	return $("select[id='invoice_to']").val();
}

function proses() {

	var chk_inv = $('#chk_invoice').attr('checked');
	var chk_cn = $('#chk_cn').attr('checked');
	var mulai_tgl_form = $('#mulai_tgl_form').val();
	var smp_dgn_form = $('#smp_dgn_form').val();
	var value = $('#invoice_to').val().toLowerCase();
	var kepada = $('#invoice_to').val().toUpperCase();	
	var kode = $('#kode_form').val();
	var table = $("input[name='invoice_type']:checked").attr('table');
	
	var myData = {};
	$("input[name='invoice_type']").each(function() {
		var val=$(this).attr('checked');
		if (val=='checked') { chk = true } else { chk = false}
    	myData[$(this).attr("id")] = chk;
	});
	
	if(myData.chk_invoice == false && myData.chk_cn == false ) { show_messages('Pilih salah satu checkbox INVOICE DEBIT NOTE atau CREDIT NOTE'); return; }
	else if(myData.chk_invoice ==true && myData.chk_cn == true) { show_messages('Pilih salah satu checkbox INVOICE DEBIT NOTE atau CREDIT NOTE'); return; }
	else if(mulai_tgl_form=='' || smp_dgn_form=='') { show_messages('Tanggal Can\'t be empty value'); return; }
	else
	{	
	
		if(myData.chk_invoice==true){
			var jenis = '1';	
		}
		if(myData.chk_cn==true){
			var jenis = '2';	
		}

		var Data = {};
		Data.jenis = jenis;
		Data.from_date = mulai_tgl_form;
		Data.to_date = smp_dgn_form;
		Data.kepada = kepada;
		Data.kode = kode;
		
		//console.log(Data);
		//return;

		$('#data-form').fadeIn('slow', function(data) {
			$(this).dialog(
			{
				autoOpen: true,
				resizable: false,
				height: 'auto',
				position: ['center',5],
				width: 990,
				modal: true,
				title: "Data Agenda",
				close: function(){
					$(this).html('');
				}
			});
			$(this).html('<table id="table-satu"></table>'+
							'<div id="page-satu" style="font-size: 11px"></div>'+
							'<div id="button-data"></div>');		
			table_satu(Data);
			$('#button-data')
			.html('<hr><input type="button" value="CETAK PDF" onclick="cetak_data(\'pdf\')" style="float: right; width: 100; height: 25; font-size: 11px; margin-right: 20px">'+
			'<input type="button" value="CETAK EXCEL" onclick="cetak_data(\'excel\')" style="float: right; width: 100; height: 25; font-size: 11px; margin-right: 20px">');
		})
	}
}
 
function table_satu(Data) {
	var grid = $("#table-satu"), i;
	grid.jqGrid( {
      url:"<?php echo base_url('finance/c_agenda_invoice/display_table_satu')?>",
  		mtype : "post",
  		datatype: "json",
   	postData: Data,
   	sortname: 'date',
   	sortorder:'asc',
   	colNames:['Job No', colum_name()+' No','Date','BL/ No',colum_shipper(),'Tanggal Bayar','USD','RP','Remarks'], 
  		colModel:
   	[
			{name:'job_no',index:'job_no', width:110, align:"center",
				searchoptions: {
					searchhidden: true,
					sopt:['cn','eq'],
          	},
			} ,
			{name:'colum',index:'colum', width:80, align:"center",
				searchoptions: {
					searchhidden: true,
					sopt:['cn','eq'],
          	},
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
			{name:'bl_no',index:'bl_no', width:90, align:"center", search: false,
				searchoptions: {
					searchhidden: true,
					sopt:['cn','eq'],
          	},			
			},	
			{name:'shipper',index:'shipper', width:205, align:"shipper",
				searchoptions: {
					searchhidden: true,
					sopt:['cn','eq'],
          	},},	
			{name:'tgl_bayar',index:'tgl_bayar', width:90, align:"center",
				searchoptions: {
					searchhidden: true,
					sopt:['cn','eq'],
          	},
			},	
			{name:'usd',index:'usd', width:100, align:"right", search: false,
				formatter: 'number',
    			formatoptions: { decimalSeparator: ".", thousandsSeparator: ",", decimalPlaces: 2, defaultValue: '' },
    			searchoptions: {
					searchhidden: true,
					sopt:['cn','eq'],
          	},
			},	
			{name:'rp',index:'rp', width:100, align:"right", search: false,
				formatter: 'number',
    			formatoptions: { decimalSeparator: ".", thousandsSeparator: ",", decimalPlaces: 2, defaultValue: '' },
				searchoptions: {
					searchhidden: true,
					sopt:['cn','eq'],
          	},
			},		
			{name:'remarks',index:'remarks', width:200, align:"left",hidden: true},
		],
		shrinkToFit: false,
		multiselect:false,
		loadonce : false,
		cellEdit: true,
		rowNum:20,
		width: 950,
		height: 'auto',
		rowList:[20,40,80],
		viewrecords: true,
		rownumbers: true,
		gridview: true,
		modal: true,
      caption:false,
      cellsubmit:'clientArray',
		pager: '#page-satu',

		loadComplete: function(data) {
			$("#cb_"+grid[0].id).hide();
			$(".ui-jqgrid-titlebar-close",$("#table-satu")[0].grid.cDiv).hide();
			create_query(data.last_query);
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
		/*
		beforeSaveCell: function(rowid, cellname, value, iRow, iCol) {
			update_table_satu(rowid,cellname,value);
    	},
    	*/
   }).jqGrid('navGrid', '#page-satu', {edit: false, add: false,  del: false,},
   	{ },
   	{ },
   	{ },
   	{ modal: true, height: "auto", width: 400, closeAfterSearch : true, caption: "Fine Data ..",},
   	{ }
   );

}

function update_table_satu()
{
	$.ajax
	({
		type:"POST",
		url:"<?php echo site_url('c_agenda_invoice/manage_tbl_satu'); ?>",
		data:
		{
			id: arguments[0],
			kolom : arguments[1],
			isi	: arguments[2]
		},
		success:function(response)
		{
			var data = $.parseJSON(response);
			if(data.status==1){
    			return [true];
			}
		}
	})
}

function reload_data(status,chk)
{
	if(status==true){
		$('#table-satu').jqGrid('setGridParam', {datatype: 'json'}).trigger('reloadGrid');
	}
}

function cetak_data(mode)
{
	var res=serialize({
			mulai : $('#mulai_tgl_form').val(),
			akhir : $('#smp_dgn_form').val()
	});
			if(mode=='pdf'){	
			$('#data-pdf').fadeIn('slow', function() 
				{
					$(this).dialog({
						title: "Print Out Data Agenda",
						close: function(){
							$(this).html('');
						}
					})			
					$(this).html('<center><iframe src="<?php echo base_url("finance/c_agenda_invoice/print_data_to_pdf?") ?>'+res+'" width=" 960px" height="600"></iframe></center>')
				})
			} else {
				window.open("c_agenda_invoice/print_data_to_excel?"+res, "_self",1,true);
			}	
}

function create_query(){
	$('input[id="query_<?php echo $this->session->userdata("user_id") ?>"]').val(arguments[0]);
}

</script>


















