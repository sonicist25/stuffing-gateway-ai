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
        <td style="color: red; width: 180px">Agent</td>
        <td style="color: red; width: 20px">:</td>
        <td width="230px">
        <?php 
        
        echo form_dropdown('dropdown_agent',$option,'','id="dropdown_agent" style="min-width: 220px; max-width: 220px"'); 
        ?>
        </td>
       </tr>
      
      <tr class="tr-heading">
        <td style="color: red; width: 180px">Periode Bulan</td>
        <td style="color: red; width: 20px">:</td>
        <td><input name="bulan" type="text" id="bulan" style="width: 70px" />
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
	var bulan = $('input[id="bulan"]').val();
	var agent = $('select[id="dropdown_agent"]').val();
	
	if(agent==''){
		show_messages("Please Complete required data")
		return false;
	} else {
		$.ajax({
			type: "POST", 
			url : "<?php echo site_url('report/rekap_agent/get_proses')?>",
			data: { 
				output : mode,
				bulan : bulan,
				agent : agent,
			},
			success:function(response)
			{
				var data = $.parseJSON(response);
				if(data.status > 0 ){
					get_output(mode,bulan,agent)
				} else {
					show_messages("Query return to empty data, Output can not be send")
				}
			}
		})
	} 
}

function get_output(mode,bulan,agent) {
	
	var res=serialize({
			bulan : bulan,
			agent : agent,
	});
	
	if (mode=='pdf') {
		$("#form-output-pdf" ).dialog({
			autoOpen: true,
			resizable: false,
			height: 650,
			width: 990,
			modal: true,
			title :"PRINT OUT REKAP AGENT",
			close: function() {	}
		}).html('<center><object data="<?= base_url("report/rekap_agent/") ?>to_pdf?'+res+'" width="960px" height="600px" type="application/pdf"></center>');
	} else {
		window.open("rekap_agent/to_excel?"+res, "_self",0,true);
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