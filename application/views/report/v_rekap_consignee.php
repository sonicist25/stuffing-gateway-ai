<div id="form-output-pdf"></div>
<style type="text/css">
table {
	padding: 10px;
}
td {
	vertical-align: middle;
	padding: 3px;
}
</style>
<div id="ref-wrapper"  class="outset-border rate-guidance"> 
	<div id="content-table-container" class="clearfix">
     <table width="100%" class="tr-heading">
      <tr class="tr-heading">
        <td style="color: red; width: 180px">Consignee</td>
        <td style="color: red; width: 20px">:</td>
        <td width="230px">
        <?php 
        
        echo form_dropdown('dropdown_consignee',$option,'','id="dropdown_consignee" style="min-width: 220px; max-width: 220px"'); 
        ?>
        </td>
       </tr>
      
      <tr class="tr-heading">
        <td style="color: red; width: 180px">Tanggal</td>
        <td style="color: red; width: 20px">:</td>
        <td><input name="" type="text" id="tgl_awal" style="width: 70px" /> S/D <input name="" type="text" id="tgl_akhir" style="width: 70px" />
        </td>
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
function get_proses(mode){
	var tgl_awal = $('input[id="tgl_awal"]').val();
	var tgl_akhir = $('input[id="tgl_akhir"]').val();
	var consignee = $('select[id="dropdown_consignee"]').val();
	
	if(tgl_awal=='' || tgl_akhir=='' || consignee==''){
		show_messages("Please Complete required data")
		return false;
	} else {
		$.ajax({
			type: "POST", 
			url : "<?php echo site_url('report/rekap_consignee/get_proses')?>",
			data: { 
				output : mode,
				tgl_awal : tgl_awal,
				tgl_akhir : tgl_akhir,
				consignee : consignee,
			},
			success:function(response)
			{
				var data = $.parseJSON(response);
				if(data.status > 0 ){
					get_output(mode,tgl_awal,tgl_akhir,consignee)
				} else {
					show_messages("Query return to empty data, Output can not be send")
				}
			}
		})
	} 
}

function get_output(mode,tgl_awal,tgl_akhir,consignee) {
	
	var res=serialize({
			tgl_awal : tgl_awal,
			tgl_akhir : tgl_akhir,
			consignee : consignee,
	});
	
	if (mode=='pdf') {
		$("#form-output-pdf" ).dialog({
			autoOpen: true,
			resizable: false,
			height: 650,
			width: 990,
			modal: true,
			title :"PRINT OUT REKAP SALES",
			close: function() {	}
		}).html('<center><object data="<?= base_url("report/rekap_consignee/") ?>to_pdf?'+res+'" width="960px" height="600px" type="application/pdf"></center>');
	} else {
		window.open("rekap_consignee/to_excel?"+res, "_self",0,true);
	}
}

function messages(char){
	$('#messages').html('<div style="font-size: 12px; color: #f33; border: 1px solid #f33; padding: 15px">'+char+'</div>');
   $('#messages').fadeIn('slow').delay(2000).fadeOut('slow');
}

function close_form(){
	$( "#form-report" ).dialog('close');
}


</script>