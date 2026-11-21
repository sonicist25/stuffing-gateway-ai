<div id="msg-global-income"></div>
<input type="hidden" id="idNya" value="<?php echo $id ?>">
<input type="hidden" id="oper" value="<?php echo $oper ?>">
<div id="from-editor">
<table summary="" style="font-size: 11px;">
	<tr>
		<td width="100" height="25">Code Service</td>
		<td>
		<?php 
		$agent = $this->type_service_model->getListService()->result();
		$data['option_service'][0]=' ';
		foreach ($agent as $row)
		{	
			$data['option_service'][$row->typeservice_code]=$row->typeservice_code;
		}		
		echo form_dropdown('codeService',$data['option_service'],'','id="codeService" style="width: 150px;"') 
		?>
		</td>
	</tr>
	<tr>
		<td height="25">Item Service</td>
		<td><input type="text" id="ItemService" style="width: 150px;" readonly="readonly"></td>
	</tr>
	<tr>
		<td height="25">Quantity</td>
		<td><input type="text" id="Quantity" style="width: 150px;" onchange="formatAngka(this)" onkeyup="formatAngka(this)"></td>
	</tr>
	<tr>
		<td height="25">Container</td>
		<td>
		<?php 
		$countainer = $this->type_container_model->getListContainer()->result();
		$qselect = $this->dbx->get_where("x_glbincome",array('id'=>$_POST['id']));
		if($qselect->num_rows() >0 ) {
			$select = $qselect->row()->container;
		} else {
			$select = "";
		}
		$data['option_container'][]='';
		foreach ($countainer as $row)
		{	
			$data['option_container'][$row->typecontainer_name]=$row->typecontainer_name;
		}		
		echo form_dropdown('dropdownContainer',$data['option_container'],$select,'id="dropdownContainer" style="width: 150px;"');
		?>
		</td>
	</tr>
	<tr>
		<td height="25">Currency</td>
		<td>
			<select id="Currency" style="width: 150px;">
				<option value="USD">USD</option>
				<option value="RP.">RP.</option>
			</select>
		</td>
	</tr>
	<tr>
		<td height="25">Price / Unit</td>
		<td><input type="text" id="Price_Unit" style="width: 150px;" onchange="formatUang(this)" onkeyup="formatUang(this)"></td>
	</tr>
	<tr>
		<td height="25">Term</td>
		<td>
			<select id="Prepaid_Collect" style="width: 150px;">
				<option value="0"> </option>
				<option value="PREPAID">PREPAID</option>
				<option value="COLLECT">COLLECT</option>
			</select>
		</td>
	</tr>
	<tr>
		<td height="25">Receive From Agent</td>
		<td>
		<?php 
		$qselect = $this->dbx->get_where("x_glbincome",array('id'=>$_POST['id']));
		if($qselect->num_rows() >0 ) {
			$select = $qselect->row()->payeble_to;
		} else {
			$select = "";
		}
		$payable = $this->afg_m_income->get_data_agent_global();
		$dt['payable'][]='';
		foreach ($payable as $row)
		{
			$dt['payable'][$row['payable']] = $row['payable'];
		}		
		echo form_dropdown('Payable',$dt['payable'],$select,'id="Payable" style="width: 150px;"');
		?>
		<!--
			<input type="text" id="Payeble" style="width: 150px;">
		-->	
		</td>
	</tr>
	<tr>
		<td height="25">Group</td>
		<td><input type="text" id="Group" style="width: 150px; text-transform: uppercase"></td>
	</tr>
</table>
<hr>
</div>
<div id="loading-global-income style="float: left"></div>
<div style="float: right; font-size: 11px; margin-bottom: 10px;">
	<input type="button" value="SAVE" style="width: 60; height: 25" id="btnSAVE">
	<input type="button" value="CANCEL" style="width: 60; height: 25" id="btnCANCEL" onclick="close_form()">
</div>

<script type="text/javascript">
$(document).ready(function () {
	var base_url = "<?php echo base_url(); ?>";
	var rowid = $('#idNya').val();
	var oper = $('#oper').val();
	if(oper =='edit')
	{
		$('#codeService').val($("#table-global-income").jqGrid('getCell',rowid,'code_service'));
		$('#ItemService').val($("#table-global-income").jqGrid('getCell',rowid,'item_service'));
		$('#Quantity').val($("#table-global-income").jqGrid('getCell',rowid,'qty'));
		$('#dropdownContainer').val($("#table-global-income").jqGrid('getCell',rowid,'container'));
		$('#Currency').val($("#table-global-income").jqGrid('getCell',rowid,'currency'));
		$('#Price_Unit').val($("#table-global-income").jqGrid('getCell',rowid,'unit_price'));
		$('#PriceGlobal').val($("#table-global-income").jqGrid('getCell',rowid,'price'));
		$('#Prepaid_Collect').val($("#table-global-income").jqGrid('getCell',rowid,'prep_coll'));
		$('#Payeble').val($("#table-global-income").jqGrid('getCell',rowid,'payeble_to'));
		$('#Group').val($("#table-global-income").jqGrid('getCell',rowid,'group'));
		$('#btnSAVE').attr('onClick','save_data("edit")');
	}
	else
	{
		$('#codeService').val('');
		$('#ItemService').val('');
		$('#Quantity').val('');
		$('#dropdownContainer').val('');
		$('#Currency').val('');
		$('#Price_Unit').val('');
		$('#PriceGlobal').val('');
		$('#Prepaid_Collect').val('');
		$('#Payeble').val('');
		$('#Group').val('');
		$('#btnSAVE').attr('onClick','save_data("add")');
	}
	
	$( '#codeService').live("change", function(e)
	{
		$.ajax
		({
			type:"POST",
			url:base_url+'/afg_c_dropdown/item_service_nya',
			data:
				{
					code_service: $('#codeService').val(),
				},
			success:function(response)
			{
				var data = $.parseJSON(response);
				$('#ItemService').val(data);
			},
		})
	});
})

function close_form(){
	$( '#Form-Input-GlbIncome' ).fadeOut('slow', function() 
	{
		$(this).dialog('close');
	})
}

function save_data(input){
	
	var job_no 			= $('#job_no').val();
	var code_service 	= $('#codeService').val();
	var item_service 	= $('#ItemService').val();
	var qty 				= $('#Quantity').val().replace(/\ /g,'');
	var container 		= $('#dropdownContainer').val();
	var currency 		= $('#Currency').val();
	var unit_price 		= $('#Price_Unit').val().replace(/\ /g,'');
	var prep_coll 		= $('#Prepaid_Collect').val();
	var payeble_to 		= $('select[id="Payable"]').val();
	var group 			= $('#Group').val();
	var id 				= $('#idNya').val();
	
	if(code_service=='0' || qty=='' || unit_price==''){
		show_messages('Please complite request data');
		return false;
	}
	
	var dataGlobal = 
	{
		job_no			:	job_no,
		code_service	:	code_service,
		item_service	:	item_service,
		qty 			:	qty,
		container		:	container,
		currency		:	currency,
		unit_price		:	unit_price,
		prep_coll		:	prep_coll,
		payeble_to		:	payeble_to,
		group 			:	group,	
		oper			: 	input,
		id				: 	id,
	}
	
	
	$.ajax({
		type: "POST", 
		url : base_url+'/afg_c_income/manage_global',
		data: dataGlobal,
		success:function(XMLHttpRequest)
		{
			$('#Form-Input-GlbIncome').dialog('close');
			$("#table-global-income").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
		}
	})
}

function formatUang(input) {
    var num = input.value.replace(/\ /g,'');
    if(!isNaN(num)){
    	if(num.indexOf('.') > -1){
    		num = num.split('.');
    		num[0] = num[0].toString().split('').reverse().join('').replace(/(?=\d*\.?)(\d{3})/g,'$1 ').split('').reverse().join('').replace(/^[\ ]/,'');
    		if(num[1].length > 2){
				show_messages('Only 4 decimal allowed');
    			num[1] = num[1].substring(0,num[1].length-1);
    		} 
    		input.value = num[0]+'.'+num[1];
    		perkalian_global(input); 
    	} else{ 
    	input.value = num.toString().split('').reverse().join('').replace(/(?=\d*\.?)(\d{3})/g,'$1 ').split('').reverse().join('').replace(/^[\ ]/,'') };
    }
    else
    { 
		show_messages('Only numeric allowed / Hanya diperbolehkan memasukan angka');
    	input.value = input.value.substring(0,input.value.length-1);
    }
}

function formatAngka(input) {
    var num = input.value.replace(/\ /g,'');
    if(!isNaN(num)){
    	if(num.indexOf('.') > -1){
    		num = num.split('.');
    		num[0] = num[0].toString().split('').reverse().join('').replace(/(?=\d*\.?)(\d{3})/g,'$1 ').split('').reverse().join('').replace(/^[\ ]/,'');
    		if(num[1].length > 4){
				show_messages('Only 4 decimal allowed');
    			num[1] = num[1].substring(0,num[1].length-1);
    		} 
    		input.value = num[0]+'.'+num[1];
    	} else { 
    	input.value = num.toString().split('').reverse().join('').replace(/(?=\d*\.?)(\d{3})/g,'$1 ').split('').reverse().join('').replace(/^[\ ]/,'') };
    }
    else
    { 
		show_messages('Only numeric allowed / Hanya diperbolehkan memasukan angka');
    	input.value = input.value.substring(0,input.value.length-1);
    }
}
</script>