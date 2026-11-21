<div id="form-output-pdf"></div>
<style type="text/css">
table {
	padding: 10px;
}
td {
	border: none;	
	vertical-align: middle;
	padding: 3px;
}
</style>
<div id="ref-wrapper"  class="outset-border rate-guidance" style="width: 300px;"> 
	<div id="content-table-container" class="clearfix" style="width: 300px;">
     <table width="100%" align="center">
      <tr class="tr-heading">
        	<td>Team</td>
        	<td> 
        		<select size="1" style="width: 150px;">
        			<option value=""></option>
          	</select>
        </td>
      </tr>
      <tr class="tr-heading">
        	<td>Bulan</td>
        	<td> <input name="bulan" type="text" value="" id="bulan" style="width: 150px;" /></td>
      </tr>
      <tr class="tr-heading">
     		<td colspan="2"><input name="" type="text" style="width: 290px; margin-top: 10px" /></td>
     	</tr>
	</table>
   <hr>
  	</div>
  <div id="topbutton-report">
  	<a href="javascript:void(0)" id="btnPDF" onclick="get_proses('pdf')">TO PDF</a>
  	<a href="javascript:void(0)" id="btnEXCEL" onclick="get_proses('excel')">TO EXEL</a>
  	<a href="javascript:void(0)" id="btnCLOSE" onclick="close_form()">CLOSE</a>
  </div>
</div>
<script type="text/javascript">
function close_form()
{
	$( "#form-report" ).dialog('close');
}

function get_proses(mode){	
	var bulan = $('input[id="bulan"]').val();

	if(bulan==''){
		messages("Please Complite request data")
		return false;
	} else {
		$.ajax({
			type: "POST", 
			url : "<?php echo site_url('report/listing_job/get_proses')?>",
			data: { 
				output : mode,
				bulan: bulan,
			},
			success:function(response)
			{
				var data = $.parseJSON(response);
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
		url : "<?php echo site_url('report/listing_job/')?>/to_"+mode,
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
					title :"PRINT OUT LISTING JOB BLUM DIBUAT INVOICE",
					close: function() {	}
				});
				$('#form-output-pdf').html('<center><iframe src="<?php echo base_url("report/listing_job/") ?>/to_'+mode+'" width="960px" height="600"></iframe></center>');
			} else {
				window.open("listing_job/to_"+mode, "_self",1,true);
			}
		}
	})	
}

function messages(char){
	$('#messages').html('<div style="font-size: 12px; color: #f33; border: 1px solid #f33; padding: 15px">'+char+'</div>');
   $('#messages').fadeIn('slow').delay(2000).fadeOut('slow');
}
</script>