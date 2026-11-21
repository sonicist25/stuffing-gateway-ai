<div id="ref-wrapper"  class="outset-border rate-guidance" style="width: 250px"> 
<form action="">
<div id="acct-data" class="clearfix">
    <table width="100%">
      <tr class="tr-heading">
        <td height="30">BULAN : </td>
        <td><input name="bulan" type="text" id="bulan" style="width: 130px;" onchange="get_kode_ref_data(this)"></td>
      </tr>
	    <tr class="tr-heading">
        <td>KODE REFF :</td>
        <td colspan="3" id="dropdown">
					<?php if(isset($option)) { ?>
        	<?php echo form_dropdown('dropdown_kode_ref',$option,'','id="dropdown_kode_ref" style="min-width: 200px"'); ?>
					<?php } ?>
        </td>
      </tr>
    </table>
	<hr>
  </div>
  <div id="topbutton-report">
  	<a href="javascript:void(0)" id="btnPOST" onclick="get_proses_post()">POST JOBSHEET</a>
  	<a href="javascript:void(0)" id="btnCLOSE" onclick="close_form()">CLOSE</a>
  </div>
</form>
</div>

<script type="text/javascript" >
function close_form()
{
	$( "#acct-data" ).dialog('close');
}

function get_proses_post(){	
	var bln = $('input[name="bulan"]').val();
	if(bln==''){
		$('input[name="bulan"]').focus();
		return false;
	} else {
		$.ajax({
			type: "POST", 
			url : "<?php echo site_url('afg_c_job_sheet/proses_post')?>",
			data: { 
				region : 'Jakarta',
				kode_ref : $('select[name="dropdown_kode_ref"]').val(),
			},
			success:function(response)
			{
				messages("Berhasil", 1)
			},
			error: function(response){
				var data = $.parseJSON(response.responseText);
				messages(data.message, 0)
			}
		})
	} 
}

function get_kode_ref_data(input){
	var bln = $('input[name="bulan"]').val();
	if(bln){
		$.ajax({
			type: "POST", 
			url : "<?php echo site_url('finance/c_post_data_acct_per_job/get_kode_ref')?>",
			data: {bln},
			success:function(response)
			{
				var data = $.parseJSON(response);
				$('#dropdown').html(data.dropdown)
				$("#dropdown_kode_ref").select2()
			}
		})
	}
}


function messages(char, code){
	if(code == 1){
		$('#messages').html('<div style="font-size: 12px; color: #000; border: 1px solid #31eb56; padding: 15px">'+char+'</div>');
	}else{
		$('#messages').html('<div style="font-size: 12px; color: #f33; border: 1px solid #f33; padding: 15px">'+char+'</div>');
	}
}
</script>

