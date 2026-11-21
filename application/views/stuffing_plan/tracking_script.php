<script type="text/javascript">
function openBL(rowid){
	var code = $('#tabel_sp_detail').jqGrid('getCell',rowid,'kode_ref');
	if(<?php echo 'code' ?>.substr(4,3) == 300 || <?php echo 'code' ?>.substr(4,3) == 301 || <?php echo 'code' ?>.substr(4,3) == 302)
	{
		window.open("afg_c_do_impor", "_self",1,true);
	}
	else
	{
		var sales = $('#tabel_sp_detail').jqGrid('getCell',rowid,'sales');
		if(sales==0){
			var message = '<div id="content-msg">[Sales] is empty value, Please insert data sales first in Table Stuffingplan Detail</div>';
			show_msg(message);
			return false;
		}
		else
		{
			$( "#FORM_DISPLAY_BL" ).html('<b style="font-size: 11px; font-style:italic; text-align:center">LOADING...</b>');
			$( "#FORM_DISPLAY_BL" ).dialog(
			{
				autoOpen: true,
				resizable: false,
				height: 600,
				width: 730,
				modal: true,
				title :"BILL OF LADING",
				close: function() 
				{	
					cancel_instan_accsess();
					enabled_top_button();
					$(this).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
				}
			});
		
			$.ajax({
				type: "POST", 
				url : "<?php echo site_url('afg_c_bill_of_ladding/open_bl_form')?>",
				data: { spd: rowid, kode_ref : code },
				success:function(XMLHttpRequest)
				{
					$( "#FORM_DISPLAY_BL" ).css('background-color','#fff');
					$( "#FORM_DISPLAY_BL" ).html(XMLHttpRequest)
				},	
				error:function(XMLHttpRequest)
				{
					$('#FORM_DISPLAY_BL').html(XMLHttpRequest.responseText)
				}	
			});
		}
	}
}

function table_tracking(){
	$( "#Form-Trucking" ).css('background-image', 'url("public/images/bg-form.png")');
	$( "#Form-Trucking" ).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
	$( "#Form-Trucking" ).dialog(
	{
		autoOpen: true,
		resizable: false,
		height: 470,
		width: 980,
		modal: true,
		title: "TABLE TRACKING",
		close: function() 
		{	
			enabled_top_button();
			cancel_instan_accsess();
			$(this).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
		}
	});
	
	$.ajax({
		type: "POST", 
		url : "<?php echo site_url('afg_c_trucking/open_trucking_table')?>",
		success:function(XMLHttpRequest)
		{
			$('#Form-Trucking').html(XMLHttpRequest)
		}
	})
}

function show_income(id){
	var code = $('#tabel_sp_detail').jqGrid('getCell',id,'kode_ref');
	var shipper = $('#tabel_sp_detail').jqGrid('getCell',id,'shipper');
	var sales = $('#tabel_sp_detail').jqGrid('getCell',id,'sales');
	var agent = $('#tabel_sp_detail').jqGrid('getCell',id,'agent_code');
	var lock_job = $('#tabel_sp_detail').jqGrid('getCell',id,'lock_job');

	$( "#Form-Income" ).css('background-image', 'url("public/images/bg-form.png")');
	$( "#Form-Income" ).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
	$( "#Form-Income" ).dialog(
	{
			resizable: false,
			height: 640,
			width: 860,
			modal: true,
			title: "INFORMASI INCOME",
			close: function() 
			{	
				enabled_top_button();
				$(this).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
				$("#tabel_sp_detail").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
			}
	});
	
	
	$.ajax({
		type: "POST", 
		url : "<?php echo site_url('afg_c_income/open_form')?>",
		data: 
		{ 
			rowid: id, 
			kode_ref: code, 
		},
		success:function(XMLHttpRequest)
		{
			$( "#Form-Income" ).dialog(
			{
				autoOpen: true,
				resizable: false,
				width: 860,
			}).html(XMLHttpRequest)
		}
	})
}

function show_cost(){
	var id = arguments[0];
	var grid = $('#tabel_sp_detail'), i;
	var lock_job = grid.jqGrid('getCell',id,'lock_job');
	var code = grid.jqGrid('getCell',id,'kode_ref');

	if(lock_job == '1'){
		show_messages('This JOB has been lock Please unlock the Job at Job Sheet Form');
		return [false,''];
	}
	
	$( "#Form-Cost" ).css('background-image', 'url("public/images/bg-form.png")');
	$( "#Form-Cost" ).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
	$( "#Form-Cost" ).dialog(
	{
		resizable: false,
		height: 650,
		width: 860,
		modal: true,
		title: "COST INFORMATION",
		close: function() 
		{	
				enabled_top_button();
				$(this).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
		}
	});
		
	$.ajax({
		type: "POST", 
		url : "<?php echo site_url('afg_c_cost/open_form')?>",
		data: { rowid: id, kode_ref: code },
		success:function(XMLHttpRequest)
		{
			$('#Form-Cost').html(XMLHttpRequest)
		}
	})
}

function show_global_cost(id){
	var lock_job = $('#tabel_sp_detail').jqGrid('getCell',id,'lock_job');
	var refNo = $('#txtRefno2').val();
	if(lock_job== 1){
		show_messages('This JOB has been lock Please unlock the Job at Job Sheet Form');
		return false;
	}
	$( "#Form-Global-Cost" ).css('background-image', 'url("public/images/bg-form.png")');
	$( "#Form-Global-Cost" ).html('<img src="<?php echo base_url("public/images/loader.gif")?>"/>');
	$( "#Form-Global-Cost" ).dialog(
	{
		resizable: false,
		height: 340,
		width: 860,
		modal: true,
		title: "GENERAL COST LCL",
		close: function() 
		{	
			enabled_top_button();
			$(this).html('<img src="<?php echo base_url("public/images/loader.gif")?>"/>');
		}
	});
	$.ajax({
		type: "POST", 
		url : "<?php echo site_url('afg_c_cost/open_form_global')?>",
		data: 
		{ 
			ref_no : refNo,
			id : id,
		},
		success:function(XMLHttpRequest)
		{
			$('#Form-Global-Cost').html(XMLHttpRequest);
		}
	})
}

function show_job_sheet(id){
	$( "#Form-Job-Sheet" ).css('background-image', 'url("public/images/bg-form.png")');
	$( "#Form-Job-Sheet" ).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
	$( "#Form-Job-Sheet" ).dialog(
	{
		resizable: false,
		top:100,
		height: 285,
		width: 600,
		modal: true,
		title: "JOB SHEET ",
		close: function() 
		{	
			enabled_top_button();
			remove_detail();
			$(this).html('<img src="<?php echo base_url("public/images/loader.gif")?>"/>');
			$("#tabel_sp_detail").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
		}
	});
	
	var code = $('#tabel_sp_detail').jqGrid('getCell',id,'kode_ref');
	$.ajax({
		type: "POST", 
		url : "<?php echo site_url('afg_c_job_sheet/open_form_jobsheet')?>",
		data:{ 
			id : id,
			kode_ref : code 
		},
		success:function(XMLHttpRequest)
		{
			$('#Form-Job-Sheet').html(XMLHttpRequest);
		}
	})
}

function add_detail(){
	var level = "<?= $this->session->userdata('level_id') ?>";
	if(level != 0 && level != 4 ){
		show_messages('You can\'t add new detail this stuffingplan data');
		return [false,'']
	}
	$( "#Form-Input-Detail" ).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
	$( "#Form-Input-Detail" ).dialog({
		resizable: false,
		height: 510,
		width: 800,
		modal: true,
		title: "Add manual request stuffing "+$('#txtRefno2').val(),
		close: function() 
		{	
					enabled_top_button();
					remove_detail();
					$("#tabel_sp_detail").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
		}
	})
	$( "#Form-Input-Detail" ).css('background-image', 'url("public/images/bg-form.png")')
	$.ajax({
		type: "POST", 
		url : "<?php echo site_url('afg_c_stuffing_plan/open_form_detail')?>",
		data: { id : "", mode:"add" },
		success:function(XMLHttpRequest)
		{
			$('#Form-Input-Detail').html(XMLHttpRequest);
		}
	})
}

function edit_detail(rowid){
	var level = "<?php echo $this->session->userdata('level_id') ?>";
	var kode_ref = $('#tabel_sp_detail').jqGrid('getCell',rowid,'kode_ref');
	if(level != 0 && level != 4 ){
		show_messages('You can\'t edit detail this stuffingplan data');
		return[false,'']
	} 
	$( "#Form-Input-Detail" ).css('background-color','#eef').css('font-size','#11px');
	$( "#Form-Input-Detail" ).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
	$( "#Form-Input-Detail" ).dialog(
	{
		resizable: false,
		height: 510,
		width: 800,
		modal: true,
		title: "Edit manual request stuffing "+kode_ref,
		close: function() 
		{	
			enabled_top_button();
			remove_detail();
			$(this).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
			$("#tabel_sp_detail").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
		}
	});
	
	$.ajax({
		type: "POST", 
		url : "<?php echo site_url('afg_c_stuffing_plan/open_form_detail')?>",
		data: { id : rowid, mode:"edit" },
		success:function(XMLHttpRequest)
		{
				$('#Form-Input-Detail').html(XMLHttpRequest);
		}
	})
}

function del_detail(rowid){
	var level = "<?php echo $this->session->userdata('level_id') ?>";
	var kode_ref = $('#tabel_sp_detail').jqGrid('getCell',rowid,'kode_ref');
	if(level != 0 && level != 4 ){
		show_messages('You can\'t delete detail this stuffingplan data');
		return[false,'']
	}
	$( "#Form-Input-Detail" ).css('font-size','12').html('Are you sure will delete stuffingplan <br><br><b>'+kode_ref+'</b>'+
   '<br><br><hr><input type="button"value="CANCEL" id="button-cancel-detail" style="float: right; width: 80; height: 30" onclick="cancel_delete_detail()">'+
   '<input type="button"value="DELETE" id="button-delete-detail" style="float: right; width: 80; height: 30; margin: 0 10px 5px 0" onclick="delete_sp_detail('+rowid+')">');
	$( "#Form-Input-Detail" ).dialog(
	{
		autoOpen: true,
		resizable: false,
		top:100,
		height: 'auto',
		width: 280,
		modal: true,
		title: "Delete Confirm",
		close: function() 
		{	
			enabled_top_button();
			remove_detail();
			$(this).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
		}
	})
}

function cancel_delete_detail(){
	$( "#Form-Input-Detail" ).dialog('close');
}

function delete_sp_detail(idnya){
	$.ajax({
		type: "POST", 
		url : "<?php echo site_url('afg_c_stuffing_plan/manage_table')?>",
		data: { id : idnya, oper:"del" },
		success:function(response)
		{
			enabled_top_button();
			remove_detail();
			$(this).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
			$( "#Form-Input-Detail" ).dialog('close');
			$("#tabel_sp_detail").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
		}
	})
}

</script>