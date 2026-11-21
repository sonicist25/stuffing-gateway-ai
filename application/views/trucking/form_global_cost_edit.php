<div id="msg-global-cost"></div>
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
		$data['option_container'][]='';
		foreach ($countainer as $row)
		{	
			$data['option_container'][$row->typecontainer_name]=$row->typecontainer_name;
		}		
		echo form_dropdown('dropdownContainer',$data['option_container'],' ','id="dropdownContainer" style="width: 150px;"');
		?>
		</td>
	</tr>
	<tr>
		<td height="25">Currency</td>
		<td>
			<select id="Currency" style="width: 150px;">
				<option value="0"> </option>
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
		<td height="25">Prepaid / Collect</td>
		<td>
			<select id="Prepaid_Collect" style="width: 150px;">
				<option value="0"> </option>
				<option value="PREPAID">PREPAID</option>
				<option value="COLLECT">COLLECT</option>
			</select>
		</td>
	</tr>
	<tr>
		<td height="25">Payable to</td>
		<td>
			<select id="Payeble" style="width: 150px;"></select>
		</td>
	</tr>

	<tr>
		<td height="25">Group</td>
		<td><input type="text" id="Group" style="width: 150px; text-transform: uppercase"></td>
	</tr>
</table>
<hr>
</div>
<div id="loading-global-cost" style="float: left"></div>
<div style="float: right; font-size: 11px; margin-bottom: 10px;">
	<input type="button" value="SAVE" style="width: 60; height: 25" id="btnSAVE">
	<input type="button" value="CANCEL" style="width: 60; height: 25" id="btnCANCEL" onclick="close_form()">
</div>

<script type="text/javascript">
$(document).ready(function () {
    // Ganti autocomplete dengan Select2
    $('#Payeble').select2({
        placeholder: 'Pilih Payable...',
        minimumInputLength: 1,
        ajax: {
            url: "<?php echo site_url('afg_c_dropdown/dropdownPayable');?>",
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    term: params.term // parameter pencarian
                };
            },
            processResults: function (data) {
                // pastikan data dari controller berbentuk array JSON
                return {
                    results: $.map(data, function (item) {
                        return {
                            id: item,   // value yang akan disimpan
                            text: item  // label yang ditampilkan
                        }
                    })
                };
            },
            cache: true
        }
    });

    var rowid = $('#idNya').val();
    var oper = $('#oper').val();
    if(oper =='edit')
    {
        $('#codeService').val($("#table-global-cost").jqGrid('getCell',rowid,'code_service'));
        $('#ItemService').val($("#table-global-cost").jqGrid('getCell',rowid,'item_service'));
        $('#Quantity').val($("#table-global-cost").jqGrid('getCell',rowid,'qty'));
        $('#dropdownContainer').val($("#table-global-cost").jqGrid('getCell',rowid,'container'));
        $('#Currency').val($("#table-global-cost").jqGrid('getCell',rowid,'currency'));
        $('#Price_Unit').val($("#table-global-cost").jqGrid('getCell',rowid,'unit_price'));
        $('#PriceGlobal').val($("#table-global-cost").jqGrid('getCell',rowid,'price'));
        $('#Prepaid_Collect').val($("#table-global-cost").jqGrid('getCell',rowid,'prep_coll'));

        // set value Select2 saat edit
        var payebleVal = $("#table-global-cost").jqGrid('getCell',rowid,'payeble_to');
        if(payebleVal){
            var option = new Option(payebleVal, payebleVal, true, true);
            $('#Payeble').append(option).trigger('change');
        }

        $('#Group').val($("#table-global-cost").jqGrid('getCell',rowid,'group'));
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
        $('#Payeble').val('').trigger('change');
        $('#Group').val('');
        $('#btnSAVE').attr('onClick','save_data("add")');
    }

    $('#codeService').on("change", function(e){
        $.ajax({
            type:"POST",
            url:window.location.origin+'/afg_c_dropdown/item_service_nya',
            data:{ code_service: $('#codeService').val() },
            success:function(response){
                var data = $.parseJSON(response);
                $('#ItemService').val(data);
            },
        })
    });
});


function close_form(){
	$( '#Form-Input-GlbCost' ).fadeOut('slow', function() 
	{
		$(this).dialog('close');
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