<div id="form-input"></div>
<div id="form-output-pdf"></div>
<div id="f_query">

</div>
<div>
		<div id="content-table-container" class="clearfix">
		<form action="">
    	<table width="100%">
      <tr class="tr-heading">
        <td width="80" height="30">TANGGAL</td>
         <td width="20" align="center">:</td>
        <td><input name="" type="text" id="tgl_awal" style="width: 90px" onchange="get_commission_data(this)"></td>
        <td align="right">S/D</td>
        <td><input name="" type="text" id="tgl_akhir" style="float: right; width: 90px" onchange="get_commission_data(this)"></td>
		</tr>
      <tr class="tr-heading">
        <td height="30">SHIPPER</td>
        <td width="20" align="center">:</td>
        <td colspan="3" id="dropdown">
        	<?php echo form_dropdown('dropdown_shipper',$option,'','id="dropdown_shipper" style="min-width: 250px;max-width:250px"'); ?>
        </td>
      </tr>
    </table>
  		<hr>
  	</div>
  <div id="topbutton-report">
  	<a href="javascript:void(0)" id="btnPDF" onclick="get_proses('pdf')" style="margin-right: 15px">TO PDF</a>
  	<a href="javascript:void(0)" id="btnEXCEL" onclick="get_proses('excel')" style="margin-right: 15px">TO EXEL</a>
  	<a href="javascript:void(0)" id="btnCLOSE" onclick="close_form()">CLOSE</a>
  </div>
	</form>
</div>

<script type="text/javascript">

function close_form()
{
	$( "#form-report" ).dialog('close');
}

function get_commission_data(input){
	var tgl_awal = $('input[id="tgl_awal"]').val() 
	var tgl_akhir = $('input[id="tgl_akhir"]').val()
	$.ajax({
		type: "POST", 
		url : window.location.origin+"/report/discount_rebate_shipper/get_commission_data",
		data: { tgl_awal: tgl_awal,  tgl_akhir: tgl_akhir },
		success:function(response)
		{
			var data = $.parseJSON(response);
			$('#dropdown').html(data.dropdown)
		}
	})
}

function get_proses(mode){	
	var tgl_awal = $('input[id="tgl_awal"]').val();
	var tgl_akhir = $('input[id="tgl_akhir"]').val();
	var shipper = $('select[id="dropdown_shipper"]').val();
	
	if(tgl_awal=='' || tgl_akhir=='' || shipper==''){
		show_messages("Please Complite request data")
		return false;
	} else {
		var res=serialize({
			tgl_awal : tgl_awal,
			tgl_akhir : tgl_akhir,
			shipper : shipper,
		});
		if (mode=='pdf') {
			$('#Print-Data').dialog({
				autoOpen: true,
				resizable: false,
				height: 650,
				width: 990,
				modal: true,
				title :"PRINT OUT DISCOUNT REBATE SHIPPER",
				close: function() {	}
			})
			.html('<center><object data="<?php echo base_url("report/discount_rebate_shipper/to_pdf?") ?>'+res+'" width="960px" height="600px" type="application/pdf"></center>');
		} else {
			window.open("discount_rebate_shipper/to_excel?"+res, "_self",1,true);
		}

	}
}

function get_output(mode,tgl_awal,tgl_akhir) {
	$.ajax({
		type: "POST", 
		url : "<?php echo site_url('report/discount_rebate_shipper/')?>/to_"+mode,
		data: { tgl_awal: tgl_awal,tgl_akhir: tgl_akhir},
		success:function(XMLHttpRequest)
		{
			if (mode=='pdf') {
				$( "#form-output-pdf" ).dialog(
				{
					autoOpen: true,
					resizable: false,
					height: 650,
					width: 990,
					modal: true,
					title :"PRINT OUT DISCOUNT REBATE SHIPPER",
					close: function() {	}
				});
				$('#form-output-pdf').html('<center><iframe src="<?php echo base_url("report/discount_rebate_shipper/") ?>/to_'+mode+'" width="960px" height="600"></iframe></center>');
			} else {
				window.open("discount_rebate_shipper/to_"+mode, "_self",1,true);
			}
		}
	})	
}

function messages(char){
	$('#messages').html('<div style="font-size: 12px; color: #f33; border: 1px solid #f33; padding: 15px">'+char+'</div>');
   $('#messages').fadeIn('slow').delay(2000).fadeOut('slow');
}

function cek_proses(mode){	
	var tgl_awal = $('input[id="tgl_awal"]').val();
	var tgl_akhir = $('input[id="tgl_akhir"]').val();
	var shipper = $('select[id="dropdown_shipper"]').val();
	
	if(tgl_awal=='' || tgl_akhir=='' || shipper==''){
		messages("Please Complite request data")
		return false;
	} else {
		$.ajax({
			type: "POST", 
			url : "<?php echo site_url('report/discount_rebate_shipper/get_proses')?>",
			data: { 
				output : mode,
				tgl_awal : tgl_awal,
				tgl_akhir : tgl_akhir,
				shipper : shipper,
			},
			success:function(response)
			{
				var data = $.parseJSON(response);
				$('#messages').html(data.query+'<br>'+data.status+'<br>'+data.result)
			}
		})
	} 
}
</script>