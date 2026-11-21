<div id="ref-wrapper"  class="outset-border rate-guidance" style="width: 270px"> 
<form action="">
<div id="content-table-container" class="clearfix">
    <table width="100%">
      <tr class="tr-heading">
        <td height="30">BULAN</td>
        <td><input name="bulan" type="text" style="width: 130px;" placeholder="Kosong = semua"></td>
      </tr>
      <tr class="tr-heading">
        <td height="30">KODE&nbsp;JOB</td>
        <td>
        <select size="1" style="width: 230px;" name="kode_job">
          <option value="">ALL JOB</option>
          <option value="100">LCL EXPORT (100)</option>
          <option value="101">FCL EXPORT (101)</option>
          <option value="102">COLOAD EXPORT (102)</option>
          <option value="200">AIR FREIGHT EXPORT (200)</option>
          <option value="600">AIR FREIGHT IMPORT (600)</option>
          <option value="300">LCL IMPORT (300)</option>
          <option value="301">FCL IMPORT (301)</option>
          <option value="302">COLOAD IMPORT (302)</option>
          <option value="400">CUSTOM IMPORT (400)</option>
          <option value="401">CUSTOM EXPORT (401)</option>
          <option value="402">PROFIT SHARE PPJK (402)</option>
          <option value="500">MISCELLANEOUS (500)</option>
          <option value="700">TRUCKING ONLY (700)</option>
          <option value="701">TRUCKING VENDOR (701)</option>
          <option value="702">TRUCKING CUSTOMER (702)</option>
          <option value="703">TRUCKING EXTERNAL (703)</option>
          <option value="900">DOMESTICS (900)</option>
          <option value="901">FCL DOMESTICS (901)</option>
          <option value="902">OTHER DOMESTICS (902)</option>
        </select>
        </td>
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
</form>

<script type="text/javascript" >
function close_form()
{
	$( "#form-report" ).dialog('close');
}

function get_proses(mode){
	var bln = $.trim($('input[name="bulan"]').val());
	var bulan = "";

	// Bulan optional. Jika kosong, report mengambil semua periode.
	if (bln !== "") {
		bulan = bln.substr(3,4) + bln.substr(0,2);
	}

	$.ajax({
		type: "POST", 
		url : "<?php echo site_url('report/job_sheet/get_proses')?>",
		data: { 
			output : mode,
			bulan : bulan,
			kode_ref : $('select[name="kode_job"]').val()
		},
		success:function(response)
		{
			var data = $.parseJSON(response);
			if(data.status == 1 ){
				get_output(mode, data.bulan);
			} else {
				messages("Query return to empty data, Output can not be send");
			}
		}
	});
}

function get_output(mode,bulan){
	$.ajax({
		type: "POST", 
		url : "<?php echo site_url('report/job_sheet/')?>to_"+mode,
		data: { ajax : 1, bulan: bulan },
		success:function(XMLHttpRequest)
		{
			if (mode=='pdf') {
				$( "#form-output" ).dialog(
				{
					autoOpen: true,
					resizable: false,
					height: 650,
					width: 990,
					modal: true,
					title :"PRINT OUT JOB SHEET",
					close: function() {	}
				});
				$('#form-output').html('<center><iframe src="<?php echo base_url("report/job_sheet/") ?>to_'+mode+'" width="960px" height="600"></iframe></center>');
			} else {
				window.open("job_sheet/to_"+mode, "_self",1,true);
			}
		}
	})	
}

function messages(char){
	$('#messages').html('<div style="font-size: 12px; color: #f33; border: 1px solid #f33; padding: 15px">'+char+'</div>');
   $('#messages').fadeIn('slow').delay(2000).fadeOut('slow');
}

</script>

