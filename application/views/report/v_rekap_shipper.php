<div id="form-output-pdf"></div>
<link rel="stylesheet" type="text/css" href="<?php echo base_url(); ?>application/third_party/css/rekap_shipper.css">
<div id="ref-wrapper"  class="outset-border rate-guidance"> 
<div id="content-table-container" class="clearfix">
     <table width="100%">
      <tr class="tr-heading">
        <td style="color: #ff00ff;">Sales</td>
        <td>
        <?php 
        if($this->session->userdata('level_id')==2) {
        	echo form_dropdown('dropdown_sales',array(''=>'',$this->session->userdata('user_id')=>$this->session->userdata('fullname')),'','id="dropdown_sales" style="min-width: 250px" onchange="get_shipper()"');
        } else {
        	echo form_dropdown('dropdown_sales',$option,'','id="dropdown_sales" style="min-width: 250px" onchange="get_shipper()"');
        } 
        ?>
        </td>
      </tr>
      <tr class="tr-heading">
        <td style="color: #ff00ff;" >Shipper</td>
        <td id="dropdown">
        		<select size="1" style="width: 250px;">
          		<option value="" id=""></option>
          	</select>
        </td>
      </tr>
      <tr class="tr-heading">
        <td>Company</td>
        <td><input name="" id="shipper_name" type="text" style="width: 243px;" readonly="readonly" />
      </tr>
      <tr class="tr-heading"><td></td><td><textarea name="" id="shipper_address" readonly="readonly" style="min-width: 243px;max-width: 243px;min-height: 90px"></textarea></td></tr>
		<tr class="tr-heading"><td style="border-bottom: 1px solid #999999" colspan="3"></td></tr>
		<tr class="tr-heading"><td style="color: #f00" colspan="3">Periode :</td></tr>
     	<tr class="tr-heading">
        <td>Mulai Tanggal</td>
        <td><input name="" type="text" id="tgl_awal" style="width: 80px"/>&nbsp;&nbsp;S/D : <input name="" type="text" id="tgl_akhir" style="width: 80px"/></td>
    	</tr>
      <tr class="tr-heading"> 
    	<td>Urutan</td>
        <td>
        	<select size="1" id="sidx" style="width: 90px">
          	<option value="kode_ref">JOB</option>
          	<option value="destination">TUJUAN</option>
          </select>
        </td>
		<tr class="tr-heading"><td style="color: #f00" colspan="3"></td></tr>
      </tr>
	</table>
   <hr>
  	</div>
  <div id="topbutton-report">
  	<a href="javascript:void(0)" id="btnPDF" onclick="get_proses('pdf')" style="margin-right: 15px">TO PDF</a>
  	<a href="javascript:void(0)" id="btnEXCEL" onclick="get_proses('excel')" style="margin-right: 15px">TO EXEL</a>
  	<a href="javascript:void(0)" id="btnCLOSE" onclick="close_form()">CLOSE</a>
  </div>
</div>
<script type="text/javascript">
function get_shipper(){
	var sales = $('select[id="dropdown_sales"]').val();
	if(sales == ''){ 
		$('#dropdown').html('<select size="1" style="width: 250px;"><option value="" id=""></option></select>')
		$('#shipper_name').val('')
		$('#shipper_address').html('')
		return false 
	}
	$.ajax({
		type: "POST", 
		url : "<?php echo site_url('report/rekap_shipper/get_shipper')?>",
		data: { sales : sales },
		success:function(response)
		{
			var data = $.parseJSON(response);
			$('#dropdown').html(data.dropdown)
		}
	})
}

function get_shipper_data(){
	var shipper = $('select[id="dropdown_shipper"]').val();
	if(shipper==''){ 
		$('#shipper_name').val('')
		$('#shipper_address').html('')
		return false 
	}
	$.ajax({
		type: "POST", 
		url : "<?php echo site_url('report/rekap_shipper/get_shipper_data')?>",
		data: { shipper : shipper },
		success:function(response)
		{
			var data = $.parseJSON(response);
			$('#shipper_name').val(data.shipper_name)
			$('#shipper_address').html(data.shipper_address)
		}
	})
}

function get_proses(mode){	
	var tgl_awal = $('input[id="tgl_awal"]').val();
	var tgl_akhir = $('input[id="tgl_akhir"]').val();
	var shipper = $('select[id="dropdown_shipper"]').val();
	var sales = $('select[id="dropdown_sales"]').val();
	var sidx	= $('select[id="sidx"]').val();
	if(tgl_awal=='' || tgl_akhir=='' || shipper==''){
		messages("Please Complete request data")
		return false;
	} else {
		$.ajax({
			type: "POST", 
			url : "<?php echo site_url('report/rekap_shipper/get_proses')?>",
			data: { 
				output : mode,
				tgl_awal : tgl_awal,
				tgl_akhir : tgl_akhir,
				shipper : shipper,
				sales : sales,
				sidx : sidx,
			},
			success:function(response)
			{
				var data = $.parseJSON(response);
				if(data.status > 0 ){
					get_output(mode,tgl_awal,tgl_akhir,shipper,sales)
				} else {
					show_messages("Query return to empty data, Output can not be send")
				}
	
			}
		})
	} 
}

function get_output(mode,tgl_awal,tgl_akhir,shipper,sales) {
	var res=serialize({
			tgl_awal : tgl_awal,
			tgl_akhir : tgl_akhir,
			shipper : shipper,
			sales: sales,
	});

	if (mode=='pdf') {
	$("#form-output-pdf" ).dialog({
			autoOpen: true,
			resizable: false,
			height: 650,
			width: 990,
			modal: true,
			title :"PRINT OUT REKAP SHIPPER",
			close: function() {	}
		});
		$('#form-output-pdf').html('<center><object data="<?php echo base_url("report/rekap_shipper/") ?>to_pdf?'+res+'" width="960px" height="600px" type="application/pdf"></center>');
	} else {
		window.open("rekap_shipper/to_excel?"+res, "_self",1,true);
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