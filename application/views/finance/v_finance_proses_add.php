<style type="txt/css">
#topbutton a{
	float: right;
	margin:1px;
	border-radius: 4px 4px 4px 4px;
   border: 1px solid #D3D3D3;
   color: #444444;
   font-weight: normal;
	padding:10px 7px;
	font-size:12px;
	text-decoration:none;
	background-color: #999;
	
	}	
#topbutton a:hover{
   border: 1px solid #999999;
   color: #212121;
   font-weight: normal;
   -moz-box-shadow: 0 2px 3px rgba(0, 0, 0, 0.3);
	-webkit-box-shadow: 0 2px 3px rgba(0, 0, 0, 0.3);
	box-shadow: 0 2px 3px rgba(0, 0, 0, 0.3);
 }
#page_tiga {
	font-size: 10px;
}
#groupForm {
	text-transform: uppercase;
}
</style>

<div style="font-size: 11px">
		<table class="ref-add-table">
				<tr><td width="100"> No.</td><td><input type="text" id="invoice_no" name="invoice_no" disabled="disabled" style="width: 200px; margin-bottom: 5px;"></td></tr>
            <tr>
					<td>Jenis</td>
					<td><select id="jenisForm" name="jenisForm" style="width: 200px; margin-bottom: 5px;" class="text-input">
								<option value=""></option>
            				<option value="DEBIT NOTE">DEBIT NOTE</option>
            				<option value="CREDIT NOTE">CREDIT NOTE</option>
            				<option value="INVOICE">INVOICE</option>
            		</select>
					</td>
            </tr>
            
				<tr>	
                  <td>Kepada</td>
                  <td><select id="kepadaForm" style="width: 200px; margin-bottom: 5px;" class="text-input" name="opt_kepada" onchange="select_value()">
                  		<option value=""></option>
            				<option value="AGENT">AGENT</option>
            				<option value="SHIPCO">SHIPCO</option>
            				<option value="COLOADER">COLOADER</option>
            				<option value="WAREHOUSE">WAREHOUSE</option>
							<option value="TRUCKING">TRUCKING</option>
							<option value="CONSIGNEE">CONSIGNEE</option>
							<option value="SHIPPER">SHIPPER</option>
            				</select>
                  </td>
            </tr>
            
            <tr>	
            		<td></td>
                  <td id="dropdownTD"></td>
            </tr>
            
				<tr>
						<td>Mata Uang</td>
                  <td><select id="currencyForm" name="opt_currency" style="width: 100px; margin-bottom: 5px; margin-bottom: 5px;" class="text-input">
            				<option value="USD">USD</option>
            				<option value="RP.">Rp.</option>
            			 </select>
            		</td>
            </tr>
            <tr>
					<td>Group</td>
                  <td><input type="text" id="groupForm" name="groupForm" style="width: 100px; margin-bottom: 5px;"></td>
            </tr>
            <tr>
						<td>Date</td>
						<td><input name="date_input" id="date_input" value="<?php echo date('d/m/Y') ?>" style="width: 100px; margin-bottom: 5px;"></td>
            </tr>  
			<tr>
						<td>PPN 11%</td>
						<td><input type="checkbox" id="ppn" style="width: 10px; margin-bottom: 5px;"></td>
            </tr>
      </table>			
</div>
<input type="hidden" id="jobForm" value="<?php echo $job_no ?>" style="width: 50px" readonly="readonly">		
<input type="hidden" id="namaForm" value="<?php echo $agent_name ?>" style="width: 50px" readonly="readonly">	
<input type="hidden" id="addressForm" value="<?php echo $agent_add ?>" style="width: 50px" readonly="readonly">	
<input type="hidden" id="id-add" value="<?php echo $id ?>"  style="width: 50px" readonly="readonly">
<div style="clear: both; height: 10px;"></div>
<hr>
<div style="float: left; font-size: 10px; width: 200px" id="msg"></div>

<div style="float: right; font-size: 11px">
	<input type="button" value=" PROSES " id="btnProses" style="width: 70; height: 30" onclick="save_data()"> 
	<input type="button" value=" CANCEL " id="btnCancel" style="width: 70; height: 30" onclick="cancel_data()">
</div>

<script type="text/javascript" >

$(function () {	
	$('#date_input').datepicker({dateFormat: 'dd/mm/yy'});
	$("input[id='chk-lock']").attr('title','On checked it\'s mean will delete '+ $("input[id='jobForm']").val()+' From table Job No #');
	var agent = '<?php echo $agent ?>';
	var new_no = '<?php echo $new_no ?>';
	var region_id = '<?php echo $this->session->userdata("region_id") ?>';
	
	$('#dropdownTD').html('<select id="nameForm" name="nameForm" style="width: 200px; margin-bottom: 5px" class="text-input">'+
            					'<option value="'+agent+'">'+agent+'</option>'+
									'</select>');
	if(new_no > 0){
		$.strPad = function(i,l,s) {
			var o = i.toString();
			if (!s) { s = '0'; }
				while (o.length < l) {
				o = s + o;
			}
			return o;
		}
		var numInv = eval(new_no);
		var numInvAkhir = $.strPad(numInv , 5, '0');
	}

	var year = $("input[id='jobForm']").val().substr(7,2);
	
	var job  = $("input[id='jobForm']").val().substr(4,3);
	
	if(region_id=="Cikarang") {
			ino = "INC/"
	}
	else if(region_id=="Surabaya") {
			ino = "SYINF/"
	}
	else if(region_id=="Semarang") {
			ino = "SMINF/"
	}
	else if(region_id=="Bandung") {
			ino = "BINF/"
	}
	else if(region_id=="Medan") {
			ino = "MINF/"
	}
	else 
	{
		ino = "IN/"
	}
		
	$("#invoice_no").val(ino+year+numInvAkhir);
	
})

function cancel_data(){
	$( "#form-invoice").fadeOut('slow', function(){
		$(this).dialog('close');
	})
}

function save_data(){
	var year = new Date().getFullYear().toString().substring(2, 4);
	var tanggal = $("input[name='date_input']").val();
	let isChecked = $('#ppn').is(':checked') ? 1 : 0;
	var Data = 
	{
		table 				: 'x_finance',
		job_no				: $("input[id='jobForm']").val(),
		number				: $("input[name='invoice_no']").val(),
		jenis					: $("select[name='jenisForm']").val(),
		kepada				: $("select[name='opt_kepada']").val(),
		kepada_kode			: $("select[name='nameForm']").val(),
		date					: tanggal.substr(6,4)+tanggal.substr(3,2)+tanggal.substr(0,2),
		currency 			: $("select[name='opt_currency']").val(),
		group					: $("input[id='groupForm']").val(),
		region_id			: '<?php echo $this->session->userdata("region_id") ?>',
		id						: $("input[id='id-add']").val(),
		fin_proses			: '1',
		ppn					: isChecked,
		oper					: 'add',
	}
	
	if(Data['jenis'] == '' || Data['type'] == '' || Data['kepada_kode'] == '' || Data['currency'] == ''){
		$('#msg').fadeIn('slow').delay(2000).fadeOut('slow').html('<font color="red">Please fill the form input</font>');
		return false;
	} else {
		$.ajax({
		type: "POST", 
		url : "<?php echo site_url('finance/c_finance_process/manage_data')?>",
		data: Data,
		success: function(response) {
			var data = JSON.parse(response);
			
			if (data.status === "duplicate") {
				$('#msg').fadeIn('slow').delay(2000).fadeOut('slow').html('<font color="red">Invoice number already exists.</font>');
			} else {
				$("#form-invoice").fadeOut('slow', function() {
					$(this).dialog('close');
				});
				$("#table_empat").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
				$("#table_tiga").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
			}
		},
		error:function(XMLHttpRequest)
		{
			$("#form-invoice").html(XMLHttpRequest.responseText)
		}	
	})
	}
}

function select_value(){
	var value = $('#kepadaForm' ).val();
	$('#nameForm').val('loading...');
	$.ajax({
		type: "POST", 
		url : "<?php echo site_url('finance/c_finance_process/dropdown')?>",
		data: 
		{
			value : value
		},
		success: function(data)
		{
			var data = $.parseJSON(data);
			$('#dropdownTD').html(data.dropdown);
			$('#optionGroup').html(data.group);
			get_nama_alamat();	
		},
		error:function(XMLHttpRequest)
		{
			$('#dropdownTD').html(XMLHttpRequest.responseText)
		}	
	})
}

function get_nama_alamat(){
	var kepada = $("#kepadaForm").val();
	var code   = $("#nameForm").val();
	if (code === '') return;

	$.ajax({
		type:"POST",
		url:"<?php echo site_url('finance/c_finance_process/get_nama_alamat'); ?>",
		data:{ kepada: kepada, code: code },   // tanpa number & confirm -> murni lookup
		success:function(response){
			var data = $.parseJSON(response);
			if (data.status == 1) {
				$('#namaForm').val(data.nama);
				$('#addressForm').val(data.alamat);
			} else {
				$('#namaForm').val('');
				$('#addressForm').val('');
			}
		}
	});
}

function lock_data(){
	var checked = $("input[id='chk-lock']").attr('checked');
	if(checked==true){
		$( "#msg-checked").fadeIn('slow', function(){
			$(this).dialog({
				autoOpen: true,
				resizable: false,
				height: 170,
				width: 250,
				modal: true,
				title: "Message Lock Job",
			}).html('<?php echo $this->session->userdata("fullname") ?><br>Are you sure want to remove job <br><b>'+$("input[id='jobForm']").val()+'</b><br> from table Job No #?'+
			'<hr>'+
			'<input type="button" value="YES" onclick="lock_job()" style="float: left; width: 80; height: 25">'+			
			'<input type="button" value="CANCEL" onclick="cancel_lock()" style="float: right; width: 80; height: 25">')			
		})
	}
}

function cancel_lock(){
	$( "#msg-checked").fadeOut('slow', function(){
		$(this).dialog('close')
	})
}

function lock_job(){
	$.ajax
	({
		type:"POST",
		url:"<?php echo site_url('finance/c_finance_process/lock_job'); ?>",
		data: {kode_ref: $("input[id='jobForm']").val()},
		success:function(response)
		{
			var data = $.parseJSON(response);
			if(data.status==true)
			$( "#msg-checked").fadeOut('slow', function(){
				$(this).dialog('close');
				$("#form-invoice").dialog('close');
				$("#table_dua").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
			})
		}
	})
}

</script>

