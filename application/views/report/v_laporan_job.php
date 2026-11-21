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
<div id="ref-wrapper"  class="outset-border rate-guidance" style="width: 350px;">
<div id="content-table-container" class="clearfix">
	<table width="100%">
 		<tr class="tr-heading">
  			<td style="color: blue;" width="100">NAMA TEAM</td>
        	<td>
        		<select name="nama_team" size="1" style="width: 200px;">
          		<option value="www"></option>
          	</select>
        </td>
      </tr>
      <tr class="tr-heading">
        <td style="color: blue;">PREPARED BY</td>
        <td>
        		<select name="prepared_by" size="1" style="width: 200px;">
          		<option value=""></option>
          	</select>
        </td>
     	</tr>
   	<tr class="tr-heading">
        <td style="color: blue;">JOB</td>
        <td>
        		<select name="prepared_by" size="1" style="width: 200px;">
          		<option value=""></option>
          	</select>
        </td>
    	</tr>
      <tr class="tr-heading">
        <td> Periode Tgl </td>
        <td>
        		<input name="" type="text" style="width: 80px;" id="tgl_awal" />&nbsp;&nbsp;s/d : 
        		<input name="" type="text" style="width: 80px;" id="tgl_akhir" /></td>
      </tr>
	</table>
  		<hr>
  	</div>
  <div id="topbutton-report">
  	<a href="javascript:void(0)" id="btnPDF" onclick="to_pdf()">PROSES</a>
  	<a href="javascript:void(0)" id="btnEXCEL" onclick="to_exel()">TO EXEL</a>
  	<a href="javascript:void(0)" id="btnCLOSE" onclick="close_form()">CLOSE</a>
  </div>
	</form>
</div>
<script type="text/javascript">
$(document).ready(function () 
{
	$( "#tgl_awal" ).datepicker({dateFormat : "dd/mm/yy"});
	$( "#tgl_akhir" ).datepicker({dateFormat : "dd/mm/yy"});
});

function close_form()
{
	$( "#form-report" ).dialog('close');
}

function to_pdf()
{	
	$.ajax({
		type: "POST", 
		url : "<?php echo site_url('report/sales_commission/print_out')?>",
		data: { },
		success:function(XMLHttpRequest)
		{
			$( "#form-output" ).dialog(
				{
					autoOpen: true,
					resizable: false,
					height: 'auto',
					width: 'auto',
					modal: true,
					title :"PRINT OUT LAPORAN JOB COLOAD IMPORT",
					close: function() {	}
			});
			$('#form-output').html('<iframe src="<?php echo base_url("report/sales_commission/cetak_job_sheet") ?>" width="960px" height="700"></iframe>');
		}
	})
}
function to_exel()
{	
	window.open("sales_commission/to_excel", "_self",1,true);
}
</script>