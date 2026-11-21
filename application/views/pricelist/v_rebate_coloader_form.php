<?php echo '<h2>' . $title . '</h2>'; ?>
<hr>
<div id="msg"></div>
		<table style="margin-bottom: 10px; font-size: 11px;">
			<tr>
				<td width="80" height="25">RefNo</td>
				<td>
					<input type="text" id="refNo">
					<input type="hidden" id="idInsentive">
					<input type="hidden" id="operForm">
				</td>
			</tr>
			<tr>
				<td height="25">Date</td>
				<td><input type="text" id="dateForm"></td>
			</tr>
		<tr>
		  <td height="25">Kode Shipco</td>
		  <td>
			<select id="dropdownAgent" name="dropdownAgent" style="width: 140px;"></select>
		  </td>
		</tr>
		</table>

		<table id="table-detail"></table>
		<div id="page-detail" style="font-size: 11px;"></div>
		<hr>
		<div style="float:right; font-size: 11px; margin-right: 20px">
			<input type="button" class="custom" value=" NEW " style="width: 60; height: 30" id="btnNew" onclick="new_rebate_coloader()">
			<input type="button" class="custom" value=" EDIT " style="width: 60; height: 30" id="btnEdit" onclick="edit_rebate_coloader()">
			<input type="button" class="custom" value=" NEXT " style="width: 60; height: 30" id="btnNext" onclick="next_rebate_coloader()">
			<input type="button" class="custom" value=" PREV " style="width: 60; height: 30" id="btnPrev" onclick="prev_rebate_coloader()">
			<input type="button" class="custom" value=" SAVE " style="width: 60; height: 30" id="btnSave" onclick="save_rebate_coloader()">
			<input type="button" class="custom" value="DELETE" style="width: 60; height: 30" id="btnDelete">
			<input type="button" class="custom" value="BROWSE" style="width: 70; height: 30" id="btnBrowse" onclick="browse_rebate_coloader()">
		</div>
		
<script type="text/javascript">
$(document).ready(function () {
	get_rebate('last');
	  $('#dropdownAgent').select2({
		placeholder: ' - Shipp Co - ',
		allowClear: true,
		ajax: {
		  url: '<?= base_url("pricelist/rebate_coloader/ajax_company_list") ?>',
		  dataType: 'json',
		  delay: 250,
		  data: function (params) {
			return { term: params.term };
		  },
		  processResults: function (data) {
			return { results: data };
		  },
		  cache: true
		}
	  });
});

function get_rebate(input){
	$.ajax
	({
			type:"POST",
			url:"<?php echo base_url('pricelist/rebate_coloader/get_rebate') ?>",
			data:{ idRebate : $( '#idInsentive' ).val(), mode : input },
			success:function(data)
			{
				var data = $.parseJSON(data);
				$( '#idInsentive' ).val(data.idRebate);
				$( '#refNo' ).val(data.ref_no);
				$( '#dateForm' ).val(data.date);
				$( '#dropdownAgent' ).val(data.code_shipco);	
				$('#table-detail').jqGrid('setGridParam', {  postData: {  ref_no: data.ref_no  }  } ).trigger("reloadGrid");
				table_detail();			
			},
	})
}

function table_detail(){
	var ref_no = $( '#refNo' ).val()
	var grid = $('#table-detail'), i;
	grid.jqGrid(
	{
		url:"<?php echo base_url('pricelist/rebate_coloader/table_detail')?>", 
		mtype : "post",  
		datatype: "json",
		_search: true,
		postData:{ ref_no: ref_no },
		colNames:
		[ 
			'',
			'Destination',
			'Freight',
			'Amount',
			'Minimum',
			'',	
		],
		colModel:
		[
			{name:'ref_no',index:'ref_no', width:5, align:"left", hidden: true, editable: true,
				editrules:{ custom:true, custom_func: isi },
			},
			{name:'destination',index:'destination', width:5, align:"left", editable: true,
				edittype:"select", editoptions:{width: 50, dataUrl:'<?php echo base_url("afg_c_dropdown/dropdownDestination")?>'}
			},
			{name:'freight',index:'freight', width:5, align:"left", editable: true,
				edittype:"select",formatter:'select', editoptions:{value:"COLLECT:COLLET;PREPAID:PREPAID", width: 50,}
			},
			{name:'amount',index:'amount', width:5, align:"right", editable: true,
				formatter: 'number',
    			formatoptions: { decimalSeparator: ",", thousandsSeparator: ".", decimalPlaces: 2, defaultValue: '0,00' },
    			editoptions: { size: 30, dataInit: function (el) 
				{ 
					$(el).attr('onKeyup','formatUang(this)').attr('onChange','formatUang(this)').attr('title','Hanya boleh memasukan angka'); }
				},	
			},
			{name:'min_rebate',index:'min_rebate', width:3, align:"center", editable: true,
				formatter: 'number',
    			formatoptions: { decimalSeparator: ",", thousandsSeparator: ".", decimalPlaces: 0, defaultValue: '0' },
    			editoptions: { size: 30, dataInit: function (el) 
				{ 
					$(el).attr('onKeyup','formatUang(this)').attr('onChange','formatUang(this)').attr('title','Hanya boleh memasukan angka'); }
				},	
			},
			{width: 1, }
		],
			multiselect:true, 
			modal: true,
			rownumbers: true,
			rowNum:10,
			width: 550,
			height: 150,
			rowList:[10,20,30],
			viewrecords: true,
			rownumbers: true,
			gridview: true,
			pager: '#page-detail',
			caption:"",
			editurl:'rebate_coloader/manage_detail',
						
			loadComplete: function(data) {
				$("#cb_"+grid[0].id).hide();
				$(".ui-jqgrid-titlebar-close",$("#table-detail")[0].grid.cDiv).hide();
				disabled_all();
    		},
    	
    		beforeSelectRow: function(rowid, e) {
        		var cbsdis = $("tr#"+rowid+".jqgrow > td > input.cbox:disabled", grid[0]);
        		if (cbsdis.length === 0) {
            	return true; 
        			} else {
            	return false; 
       	 	}
    		},	
    			
			onSelectRow: function(rowid, status){
			var selected = $("tr.jqgrow > td > input.cbox:checked", grid[0]);		
			selected[rowid] = status;
			if(status)
				{ 
					var cbs = $("tr.jqgrow > td > input.cbox", grid[0]);  			
    				cbs.attr("disabled", true);  
    				selected.attr("disabled", false);  	
    			}
        		else
        		{
        			var cbs = $("tr.jqgrow > td > input.cbox:disabled", grid[0]);  		
    				cbs.attr("disabled", false);    			
        		}
			},				
	}).navGrid('#page-detail', { add: true, edit: true, del: true },	
		{	
				height: "auto", 
				width: 310, 	
				closeAfterEdit: true, 
				reloadAfterEdit : true, 
				modal: true, 
				editCaption: "Edit Data Rebate Coloader",
				recreateForm: false,
				viewPagerButtons: false,
				
				beforeShowForm: function(frm) {
					var rowid = grid.getGridParam('selrow');
					var value = grid.jqGrid('getCell',rowid,'amount');
					$( '#amount' ).val(gubahUang(value));
				},
				
				afterSubmit: function(frm) {
					grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
    				return [true];
    			} 
			},		
   		{
   			height: "auto", 
   			width: 310,  
   			closeAfterAdd: true, 
   			reloadAfterAdd : true, 
   			modal: true, 
   			addCaption:  "Add Data Rebate Coloader",
   			
   			beforeShowForm: function(frm) {
   				var ref_no = $( '#refNo' ).val();
   				if(ref_no == '' )
   				{
						return [false,"Please insert RefNo Data First D"];
   				}
   				else
   				{
   					$( '#ref_no' ).val(ref_no);
   					return [true,""];
   				}
				},
				
				afterSubmit: function () {
					grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
    				return [true];
    			}  		
   		},
     		{height: "auto", width: "auto"},
      	{modal: false, height: "auto", width: "auto", closeAfterSearch : true,},
     		{}
		);
}

function isi(value, colname){
	if (value != '') 
		return [true,""];
   else 
   	$( 'refNo' ).focus();
   	return [false,"Please insert \"RefNo\", \"Date\", \"Kode Shippco\" First"];
}

function disabled_all(){
	$( '#refNo' ).attr('disabled',true);
	$( '#dateForm' ).attr('disabled',true);
	$( '#dropdownAgent' ).attr('disabled',true);
	
	$( '#btnNew' ).attr('disabled',false);
	$( '#btnEdit' ).attr('disabled',false);
	$( '#btnNext' ).attr('disabled',false);
	$( '#btnPrev' ).attr('disabled',false);
	$( '#btnBrowse' ).attr('disabled',false);
	$( '#btnSave' ).attr('disabled',true);
	$( '#btnDelete' ).attr('onClick','delete_rebate_coloader()').val('DELETE');
	editor('true');
}

function enabled_all(){
	$( '#refNo' ).attr('disabled',false);
	$( '#dateForm' ).attr('disabled',false);
	$( '#dropdownAgent' ).attr('disabled',false);
	
	$( '#btnNew' ).attr('disabled',true);
	$( '#btnEdit' ).attr('disabled',true);
	$( '#btnNext' ).attr('disabled',true);
	$( '#btnPrev' ).attr('disabled',true);
	$( '#btnBrowse' ).attr('disabled',true);
	$( '#btnSave' ).attr('disabled',false);
	editor('false');
}

function editor(input){
	if(input == 'true')
	{
		$( '#add_table-detail' ).attr('hidden', true);
		$( '#edit_table-detail' ).attr('hidden', true);
		$( '#del_table-detail' ).attr('hidden', true);
	}
	else
	{
		$( '#add_table-detail' ).attr('hidden', false);
		$( '#edit_table-detail' ).attr('hidden', false);
		$( '#del_table-detail' ).attr('hidden', false);
	}	
}

function browse_rebate_coloader(){
	$( "#FORM-BROWSE" ).dialog(
	{
		autoOpen: true,
		resizable: false,
		height: 'auto',
		width: 580,
		modal: true,
		title :"BROWSE DATA",
		close: function() {	}
	});
	table_browse()
}

function table_browse(){
	var grid = $("#table-browse"), i;		   			
	grid.jqGrid(
	{
		url:"<?php echo base_url('pricelist/rebate_coloader/table_browse')?>", 
		mtype : "post",  
		_search: true, 
		datatype: "json",  
		colNames:
		[ 
			'Ref No',
			'Date',
			'Shippco #',	
			'Rebate',
			'Minimum',
		],
		colModel:
		[
			{name:'ref_no',index:'ref_no', width:20, align:"left"},
			{name:'date',index:'date', width:15, align:"center"},
			{name:'shippco',index:'shippco', width:20, align:"left"},
			{name:'rebate',index:'rebate', width:20, align:"left"},
			{name:'minimum',index:'minimum', width:5, align:"center"},
		],
			loadonce: true,
			modal: true,
			rownumbers: true,
			rowNum:10,
			width: 550,
			height: 240,
			rowList:[100,200,300,400,500],
			viewrecords: true,
			rownumbers: true,
			gridview: true,
			pager: '#page-browse',
			caption:"",
						
			loadComplete: function() {
				$("#cb_"+grid[0].id).hide();
				$(".ui-jqgrid-titlebar-close",$("#table-browse")[0].grid.cDiv).hide();
    		},
    	
    		beforeSelectRow: function(rowid, e) {
        		var cbsdis = $("tr#"+rowid+".jqgrow > td > input.cbox:disabled", grid[0]);
        		if (cbsdis.length === 0) {
            	return true; 
        			} else {
            	return false; 
       	 	}
    		},	
    			
			onSelectRow: function(rowid, status){
			var selected = $("tr.jqgrow > td > input.cbox:checked", grid[0]);		
			selected[rowid] = status;
			if(status)
				{ 
    				$( '#idInsentive' ).val(rowid);
    				get_rebate('pilih');
    				$( "#FORM-BROWSE" ).dialog( 'close' );
    				selected.attr("checked", false);
    			}
        		else
        		{
        			   			
        		}
			},				
		}).navGrid('#page-browse', 
		{ edit: false, add:false, del: false },
		{},
		{},
		{},
		{closeAfterSearch:true, caption: 'Cari data'}
	);
}

function form_close(){
	$( "#FORM-INTENSIVE" ).dialog('close');
}

function cancel(input){
	if(input == 'new')
	{
		get_rebate('last');
		
	}
	else
	{
		get_rebate('pilih');
	}
	disabled_all();
}

function new_rebate_coloader(){
	enabled_all();
	$( '#btnDelete' ).attr('onClick','cancel("new")').val('CANCEL');
	$( '#refNo' ).val('');
	$( '#dateForm' ).val('').datepicker({dateFormat : "dd/mm/yy"});
	$( '#dropdownAgent' ).val('');
	$( '#idInsentive' ).val('');
	$( '#operForm' ).val('add');
	$('#table-detail').jqGrid("clearGridData", true);
}

function edit_rebate_coloader(){
	enabled_all()
	$( '#btnDelete' ).attr('onClick','cancel("edit")').val('CANCEL');
	$( '#dateForm' ).datepicker({dateFormat : "dd/mm/yy"});
	$( '#operForm' ).val('edit');
}

function delete_rebate_coloader(){
	$( '#delete-confirm' ).dialog(
	{
		autoOpen: true,
		resizable: false,
		height: 'auto',
		width: 280,
		modal: true,
		title :"Delete Confirm",
		buttons: 
		{
			"DELETE": function() 
			{
				$.ajax
				({
					type: "POST",
					url : "<?php echo base_url('pricelist/rebate_coloader/manage_data')?>",
					data : { id : $( '#idInsentive' ).val(), oper: 'del' },
					success: function(data)
					{
						var data = $.parseJSON(data);
						if(data.status == 1 )
						{
							get_rebate('last');
						}
						else
						{
							get_rebate('pilih');
						};
						$( '#delete-confirm' ).dialog('close');
					},
					error:function()
					{
						var message = '<div id="content-msg">Data can\'t be deleted</div>';
						show_msg(message);
						return false;
					}
				}); 
			},
			"CANCEL": function() 
			{
				$( this ).dialog( "close" ); 
			}
		},
		close: function() {	}
	});
}

function next_rebate_coloader(){
	get_rebate('next');
}

function prev_rebate_coloader(){
	get_rebate('prev');
}

function save_rebate_coloader(){
	var ref_no 		= $( '#refNo' ).val();
	var date	 		= $( '#dateForm' ).val();
	var agent_code	= $( '#dropdownAgent' ).val();
	var myData = 
	{
		id 			: $( '#idInsentive' ).val(),
		oper 			: $( '#operForm' ).val(),
		ref_no 		: ref_no,
		date	 		: date,
		code_shipco	: agent_code,
	}
	if (ref_no == '')
	{
		var message = '<div id="content-msg">Input \"RefNo\" Empty Value</div>';
		show_msg(message);
		$('#refNo').focus();
		return false;
	}
	else if (date == '')
	{
		var message = '<div id="content-msg">Input \"Date\" Empty Value</div>';
		show_msg(message);
		$( '#dateForm' ).focus();
		return false;
	}
	else if (agent_code == '0')
	{
		var message = '<div id="content-msg">Input \"Kode ShipCo\" Empty Value</div>';
		show_msg(message);
		$( '#dropdownAgent' ).focus();
		return false;
	}
	else
	{
		$.ajax
		({
			type: "POST",
			url : "<?php echo base_url('pricelist/rebate_coloader/manage_data')?>",
			data : myData,
			success: function(data)
			{
				var data = $.parseJSON(data);
				if(data.status == 1 )
				{
					get_rebate('last');
				}
				else
				{
					get_rebate('pilih');
				};
			},
			error:function()
			{
				var message = '<div id="content-msg">ERROR ACCOURED</div>';
				show_msg(message);
				return false;
			}
		});
	}
}

function formatUang(input){
	var num = input.value.replace(/\,/g,'');
	if(!isNaN(num)){
   	if(num.indexOf('.') > -1){
    		num = num.split('.');
    		num[0] = num[0].toString().split('').reverse().join('').replace(/(?=\d*\.?)(\d{3})/g,'$1,').split('').reverse().join('').replace(/^[\,]/,'');
    		if(num[1].length > 2){
				var message = '<div id="content-msg">Only 2 ( two ) desimal allowed</div>';
				show_msg(message);
    			num[1] = num[1].substring(0,num[1].length-1);
    		} input.value = num[0]+'.'+num[1];
    	} else{ input.value = num.toString().split('').reverse().join('').replace(/(?=\d*\.?)(\d{3})/g,'$1,').split('').reverse().join('').replace(/^[\,]/,'') };
	}
	else
	{ 
		var message = '<div id="content-msg">Only numeric allowed</div>';
		show_msg(message);
    	input.value = input.value.substring(0,input.value.length-1); 
	}
}

function gubahUang(num){
	if(num.indexOf('.') > -1){
		num = num.split('.');
 		num[0] = num[0].toString().split('').reverse().join('').replace(/(?=\d*\.?)(\d{3})/g,'$1,').split('').reverse().join('').replace(/^[\,]/,'');
		return num[0];
	}
}

function show_msg(char){
	$('#msg').html(char);
   $('#msg').fadeIn('slow').delay(2000).fadeOut('slow');
}

</script>