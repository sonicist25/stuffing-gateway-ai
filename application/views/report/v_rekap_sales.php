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
        <td style="color: red; width: 180px"><?= $this->session->userdata('level_id');?>Sales</td>
        <td style="color: red; width: 20px">:</td>
        <td width="230px">
        <?php 
        if($this->session->userdata('level_id')==2) {
        	$sls=$this->session->userdata('user_id');
        	$dsb='readonly="readonly"';
        } else {
        	$sls='';
        	$dsb='';
        }
        echo form_dropdown('dropdown_sales',$option,$sls,'id="dropdown_sales" style="min-width: 220px; max-width: 220px" '.$dsb.' '); 
        ?>
        </td>
       </tr>
      <tr class="tr-heading">
      	<td style="color: #f00">Periode</td>
      	<td style="color: #f00">:</td>
      </tr>
      <tr class="tr-heading">
        <td>Tanggal</td>
        <td></td>
        <td><input name="" type="text" id="tgl_awal" style="width: 70px" /> S/D <input name="" type="text" id="tgl_akhir" style="width: 70px" />
        </td>
      </tr>
    </table>
   <hr>
  	</div>
  <div id="topbutton-report">
  	<a href="javascript:void(0)" id="btnPDF" onclick="get_proses('pdf')" style="margin-right: 20px">TO PDF</a>
  	<a href="javascript:void(0)" id="btnEXCEL" onclick="get_proses('excel')" style="margin-right: 20px">TO EXEL (ACCT)</a>
  	<a href="javascript:void(0)" id="btnSALES" onclick="get_proses('sales')" style="margin-right: 20px">REPORT SALES</a>
  	<a href="javascript:void(0)" id="btnCLOSE" onclick="close_form()" style="align:right">CLOSE</a>
  </div>
</div>
<script type="text/javascript">
function get_proses(mode){
   var level = "<?= $this->session->userdata('level_id') ?>";
	var tgl_awal = $('input[id="tgl_awal"]').val();
	var tgl_akhir = $('input[id="tgl_akhir"]').val();
	if(level == 0 || level == 1 || level == 3){
	var sales = $('select[id="dropdown_sales"]').val(); }
	else  { var sales = "<?= $this->session->userdata('user_id') ?>";}
	
	if(tgl_awal=='' || tgl_akhir=='' || sales==''){
		show_messages("Please Complete required data")
		return false;
	} else {
		$.ajax({
			type: "POST", 
			url : "<?php echo site_url('report/rekap_sales/get_proses')?>",
			data: { 
				output : mode,
				tgl_awal : tgl_awal,
				tgl_akhir : tgl_akhir,
				sales : sales,
			},
			success:function(response)
			{
				var data = $.parseJSON(response);
				if(data.status > 0 ){
					get_output(mode,tgl_awal,tgl_akhir,sales)
				} else {
					show_messages("Query return to empty data, Output can not be send")
				}
			}
		})
	} 
}

function get_output(mode,tgl_awal,tgl_akhir,sales) {
	
	var res=serialize({
			tgl_awal : tgl_awal,
			tgl_akhir : tgl_akhir,
			sales : sales,
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
		}).html('<center><object data="<?= base_url("report/rekap_sales/") ?>to_pdf?'+res+'" width="960px" height="600px" type="application/pdf"></center>');
	} else if (mode=='excel') {
		window.open("rekap_sales/to_excel?"+res, "_self",0,true);
	}else{
		window.open("rekap_sales/to_sales?"+res, "_self",0,true);	
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