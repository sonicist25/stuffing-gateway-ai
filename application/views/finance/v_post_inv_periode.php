<div id="ref-wrapper"  class="outset-border rate-guidance" style="width: 250px"> 
<form action="">
<div id="acct-inv" class="clearfix">
    <table width="100%">
      <tr class="tr-heading">
        <td height="30">BULAN : </td>
        <td><input name="bulan" type="text" id="bulan" style="width: 130px;"></td>
      </tr>
    </table>
	<hr>
  </div>
  <div id="topbutton-report">
  	<a href="javascript:void(0)" id="btnPOST" onclick="get_post_inv()">POST INVOICE</a>
  	<a href="javascript:void(0)" id="btnCLOSE" onclick="close_form()">CLOSE</a>
  </div>
</form>
</div>

<script type="text/javascript" >
function close_form()
{
	$( "#acct-inv" ).dialog('close');
}

function get_post_inv(){	
	var bln = $('input[name="bulan"]').val();
	if(bln==''){
		$('input[name="bulan"]').focus();
		return false;
	} else {
		$.ajax({
			type: "POST", 
			url : "<?php echo site_url('finance/c_post_data_inv/proses_post')?>",
			data: { 
				bln : bln,
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


function messages(char, code){
	if(code == 1){
		$('#messages').html('<div style="font-size: 12px; color: #000; border: 1px solid #31eb56; padding: 15px">'+char+'</div>');
	}else{
		$('#messages').html('<div style="font-size: 12px; color: #f33; border: 1px solid #f33; padding: 15px">'+char+'</div>');
	}
}

</script>

