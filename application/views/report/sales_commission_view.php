<div id="form-input"></div>
<div id="form-output"></div>
<div id="f_query">


</div>
<div>
		<div id="content-table-container" class="clearfix">
		<form action="">
    	<table width="100%">
      <tr class="tr-heading">
        <td width="100">TANGGAL</td>
         <td>:</td>
        <td><input name="" type="text" id="tgl_awal" style="width: 90" onchange="get_commission_data(this)"></td>
        <td align="right">S/D</td>
        <td><input name="" type="text" id="tgl_akhir" style="float: right; width: 90" onchange="get_commission_data(this)"></td>
		</tr>
      <tr class="tr-heading">
        <td>SHIPPER</td>
        <td>:</td>
        <td colspan="3" id="dropdown">
        	<?php echo form_dropdown('dropdown_shipper',$option,'','id="dropdown_shipper" style="min-width: 200px"'); ?>
        </td>
      </tr>
      <tr class="tr-heading">
       	<td colspan="2"></td>
       	<td colspan="3"><input name="" type="text" id="" style="width: 100%"></td>
       </tr>
    </table>
  		<hr>
  	</div>
  <div id="topbutton-report">
  	<a href="javascript:void(0)" id="btnPDF" onclick="get_proses('pdf')">TO PDF</a>
  	<a href="javascript:void(0)" id="btnEXCEL" onclick="get_proses('excel')">TO EXEL</a>
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
		url : "<?php echo site_url('report/sales_commission/get_commission_data')?>",
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
		messages("Please Complite request data")
		return false;
	} else {
		$.ajax({
			type: "POST", 
			url : "<?php echo site_url('report/sales_commission/get_proses')?>",
			data: { 
				output : mode,
				tgl_awal : tgl_awal,
				tgl_akhir : tgl_akhir,
				shipper : shipper,
			},
			success:function(response)
			{
				var data = $.parseJSON(response);
				if(data.status > 0 ){
					get_output(mode,tgl_awal,tgl_akhir)
				} else {
					messages("Query return to empty data, Output can not be send")
				}
			}
		})
	} 
}

function get_output(mode,tgl_awal,tgl_akhir){
	$.ajax({
		type: "POST", 
		url : "<?php echo site_url('report/sales_commission/')?>/to_"+mode,
		data: { tgl_awal: tgl_awal,tgl_akhir: tgl_akhir},
		success:function(XMLHttpRequest)
		{
			window.open("sales_commission/to_"+mode, "_self",1,true);
		}
	})	
}

function messages(char){
	$('#messages').html('<div style="font-size: 12px; color: #f33; border: 1px solid #f33; padding: 15px">'+char+'</div>');
   $('#messages').fadeIn('slow').delay(2000).fadeOut('slow');
}
</script>