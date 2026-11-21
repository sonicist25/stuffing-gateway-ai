<div id="form-output-pdf"></div>
<style type="text/css">
table {
	padding: 10px;
}
td {

}
</style>
<div id="ref-wrapper"  class="outset-border rate-guidance" style="width: 300px;"> 
	<div id="content-table-container" class="clearfix">
		<table >
     		<tr class="tr-heading">
        		<td width="150">Rekap Job Untuk Bulan</td>
        		<td> <input name="" type="text" id="tanggal" style="width: 60px;" /></td>
        	</tr>
     </table>
   <hr>
  	</div>
  <div id="topbutton-report">
  	<a href="javascript:void(0)" id="btnPDF" onclick="get_proses('pdf')" style="margin-right: 20px">TO PDF</a>
  	<a href="javascript:void(0)" id="btnEXCEL" onclick="get_proses('excel')" style="margin-right: 20px">TO EXEL</a>
  	<a href="javascript:void(0)" id="btnCLOSE" onclick="close_form()">CLOSE</a>
  </div>
</div>
<script type="text/javascript">
function close_form()
{
	$( "#form-report" ).dialog('close');
}

function get_proses(mode){	
	var bulan = $('input[id="tanggal"]').val();
	if(bulan==''){
		messages("Please complete requested data")
		return false;
	} else {
		$.ajax({
			type: "POST", 
			url : "<?php echo site_url('report/rekap_jobsheet/get_proses')?>",
			data: { 
				output : mode,
				bulan : bulan,
			},
			success:function(response)
			{
				var data = $.parseJSON(response);

			//	$('#messages').html(data.query+'<br>'+data.status+'<br>'+data.result)

				if(data.status > 0 ){
					get_output(mode,bulan)
				} else {
					messages("Query return to empty data, Output can not be send")
				}
		
			}
		})
	} 
}

function get_output(mode,bulan){
	$.ajax({
		type: "POST", 
		url : "<?php echo site_url('report/rekap_jobsheet/')?>to_"+mode,
		data: { bulan : bulan },
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
					title :"PRINT OUT REKAP JOB SHEET",
					close: function() {	}
				});
				$('#form-output-pdf').html('<center><iframe src="<?php echo base_url("report/rekap_jobsheet/") ?>to_'+mode+'" width="960px" height="600"></iframe></center>');
			} else {
				window.open("rekap_jobsheet/to_"+mode, "_self",1,true);
			}
		}
	})	
}

function messages(char){
	$('#messages').html('<div style="font-size: 12px; color: #f33; border: 1px solid #f33; padding: 15px">'+char+'</div>');
   $('#messages').fadeIn('slow').delay(2000).fadeOut('slow');
}

</script>