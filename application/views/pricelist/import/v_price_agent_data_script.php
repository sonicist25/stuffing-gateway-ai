<script type="text/javascript">
$(document).ready(function(){
	$('#tabs-lading .the-tab').hide();
	$('#tabs-lading div:first').show();
	$('#tabs-lading ul li:first').addClass('active');
 
	$('#tabs-lading ul li a').click(function(){
		$('#tabs-lading ul li').removeClass('active');
		$(this).parent().addClass('active');
		var currentTab = $(this).attr('href');
		$('#tabs-lading .the-tab').hide();
		$(currentTab).show();
		return false;
	});
	
	disabled_all();
	
	get_data('last');	
		
	table_satu_agent();
	
	$(".ui-jqgrid-titlebar-close",$("#table-satu-agent")[0].grid.cDiv).hide();

	$('#dateForm').datepicker({dateFormat: 'dd/mm/yy'});	
	$('#validForm').datepicker({dateFormat: 'dd/mm/yy'});	
	$('#btnDelete').attr('href','#');	
	$("#table-satu-agent").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
});

function disabled_all(){

	$( '#btnNew' ).attr('disabled',false);
	$( '#btnEdit' ).attr('disabled',false);
	$( '#btnNext' ).attr('disabled',false);
	$( '#btnPrev' ).attr('disabled',false);
	$( '#btnSave' ).attr('disabled',true);
	$( '#btnDelete' ).attr('onClick','delete_data()').val('DELETE');
	$( '#btnExit' ).attr('onClick','exit_data()').val('EXIT');
	editor('true');
	
	$( '#refNo' ).attr('disabled',true);
	$( '#dateForm' ).attr('disabled',true);
	$( '#validForm' ).attr('disabled',true);
}

function enabled_all(){
	$( '#btnNew' ).attr('disabled',true);
	$( '#btnEdit' ).attr('disabled',true);
	$( '#btnNext' ).attr('disabled',true);
	$( '#btnPrev' ).attr('disabled',true);
	$( '#btnSave' ).attr('disabled',false);
	$( '#btnExit' ).attr('disabled',false).val(' SAVE ').attr('onClick','save_data()');
	editor('false');
	
	$( '#refNo' ).attr('disabled',false);
	$( '#dateForm' ).attr('disabled',false);
	$( '#validForm' ).attr('disabled',false);
}

function editor(input){
	
	if(input == 'true')
	{
		$( '#add_table-satu-agent' ).attr('hidden', true);
		$( '#edit_table-satu-agent' ).attr('hidden', true);
		$( '#del_table-satu-agent' ).attr('hidden', true);
	}
	else
	{
		$( '#add_table-satu-agent' ).attr('hidden', false);
		$( '#edit_table-satu-agent' ).attr('hidden', false);
		$( '#del_table-satu-agent' ).attr('hidden', false);
	}	
}

function delete_selling(){
	var id = $('#idSellingRate').val();
	var ref_no = $('#refNo').val();
	$('#message-selling').html('Are you sure want to delete this data ?');
	$( "#message-selling" ).dialog(
	{
		autoOpen: true,
		resizable: true,
		top:10,
		height: 150,
		width: 'auto',
		modal: true,
		title: "Delete Confirm",
		buttons : 
		{  
			"DELETE": function()
			{
				$.ajax
				({
					type: "POST",
					url : "<?php echo base_url('pricelist/export_cost_price_agent/manages_data')?>/",
					data: { id : id, refNo: ref_no, oper:'del' },
					success: function(data)
					{
						var message = '<div id="content-msg">Deleted Success</div>';
						show_msg(message);
					},
					error: function()
					{ 
						var message = '<div id="content-msg">Data can\'t deleted</div>';
						show_msg(message);
						$(this).dialog('close');
					}
				});
				$(this).dialog('close');	
			},
			"CANCEL": function()
			{
				$(this).dialog('close');
			}
		},
		close: function()  {	 }
	});
}

function show_msg(char){
	$('#msg-selling').html(char);
   $('#msg-selling').fadeIn('slow').delay(2000).fadeOut('slow');
}

function table_satu_agent(){
	var grid = $("#table-satu-agent"), i;	
	grid.jqGrid(
	{ 
		url:"<?php echo base_url('pricelist/export_cost_price_agent/get_table_listing')?>",
		mtype : "post",
		datatype: "json", 
		postData: 
		{ 
			shipper_id : $('#shipperId').val(),
		},
		colNames:
		[
			'Service','Shipper','Port Loading','Destination','P/C','20\'','40\'','40\'HC','Min Chgs','H.Ton','H.Cbm'
		],
    	colModel:
		[
			{name:'service',index:'service', align:"left", width:50, editable: true,},
			{name:'tanggal',index:'tanggal', align:"left", width:50, editable: true,},
			{name:'valid',index:'valid', align:"left", width:50, editable: true,},
			{name:'kepada',index:'kepada', align:"left", width:50, editable: true,},
			{name:'attn',index:'attn', align:"left", width:50, editable: true,},
			{name:'dari',index:'dari', align:"left", width:50, editable: true,},
			{name:'ref_no',index:'ref_no', align:"left", width:50, editable: true,},
			{name:'tanggal',index:'tanggal', align:"left", width:50, editable: true,},
			{name:'valid',index:'valid', align:"left", width:50, editable: true,},
			{name:'kepada',index:'kepada', align:"left", width:50, editable: true,},
			{name:'attn',index:'attn', align:"left", width:50, editable: true,},
		], 
		shrinkToFit: false,
  		viewrecords: true,
      multiselect:true, 
		modal: true,
		rownumbers: true,
		rowNum: 16,
		top: 20,
		width: 670,
		height: 220,
		rowList:[10,20,30,40,50,60],
		gridview: true,
		caption: ' ',
		editurl:'manage_data',
		cellsubmit:'clientArray',
		pager:'#pager-table-satu-agent',
		
		loadComplete: function() {
			$("#cb_"+grid[0].id).hide();
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
    			$('#idSelling').val(rowid);
    			get_data('pilih');
    			$('#tab-1').show();
    			$('#tab-2').hide();
    		}
        	else
        	{
        		var cbs = $("tr.jqgrow > td > input.cbox:disabled", grid[0]);  		
    			cbs.attr("disabled", false);       			
        	}
		},
	}).navGrid('#pager-table-satu-agent',{ edit: false, add: false, del: false,})
}

function table_urut(){
	var grid = $("#table-urut"), i;	
	grid.jqGrid(
	{ 
		url:"<?php echo base_url('pricelist/export_cost_price_agent/get_table_urut')?>",
		mtype : "post",
		datatype: "json", 
		colNames:
		[
			'No Urut','Kode Shipper','No Bukti','O/F or MISC'
		],
    	colModel:
		[
			{name:'no_urut',index:'no_urut', align:"left", width:5, editable: true,},
			{name:'kode_shipper',index:'kode_shipper', align:"left", width:5, editable: true,},
			{name:'no_bukti',index:'no_bukti', align:"left", width:5, editable: true,},
			{name:'of_or_misc',index:'of_or_misc', align:"left", width:5, editable: true,},
		], 
  		viewrecords: true,
      multiselect:false, 
		modal: true,
		rownumbers: true,
		rowNum: 16,
		top: 20,
		width: 670,
		height: 370,
		rowList:[10,20,30,40,50,60],
		gridview: true,
		caption: ' ',
		editurl:'manage_data',
		cellsubmit:'clientArray',
		pager:'#page-urut',
	}).navGrid('#page-urut',{ edit: true, add: true, del: true,})
}

function exit_data(){
	$('#Form-Destination').dialog('close')
}

function cancel(input){
	if(input == 'add')
	{
		get_data('last');
		
	}
	else
	{
		get_data('pilih');
	}
	disabled_all();
}

function new_data(){
	

	var rowCount  = $("#table-satu-agent").getGridParam("reccount");
	if( rowCount > 0 )
	{
		var lastSelRow = [];
		var rowid = $("#table-satu-agent").jqGrid("setSelection", lastSelRow, true)
		var newRef  = '<?php echo date("y")."-".date("m")."-"?>'+rowid;
	}	
	else
	{
		var newRef  = '<?php echo date("y")."-".date("m")."-"?>'+'0001';
	}
	
	enabled_all();
	$( '#btnDelete' ).attr('onClick','cancel("add")').val('CANCEL');
	$( '#idSelling' ).val('');
	$( '#operForm' ).val('add');
	$( '#table-satu-agent' ).jqGrid("clearGridData", true);
	$( '#idSelling' ).val('');
	$( '#refNo' ).val(newRef);
	$( '#dateForm' ).val('');
	$( '#validForm' ).val('');
	$( '#fromForm' ).val('');
	$( '#toForm' ).val('');
	$( '#attnForm' ).val('');
	$( '#containerForm' ).val('');
	$( '#remarkForm' ).val('');
	$( '#emkl_type' ).val('');
}

function edit_data(){
	enabled_all()
	$( '#btnDelete' ).attr('onClick','cancel("edit")').val('CANCEL');
	$( '#operForm' ).val('edit');
}

function delete_data(){
	$( '#delete-confirm' ).html('Delete this data')
	$( '#delete-confirm' ).dialog(
	{
		autoOpen: true,
		resizable: false,
		height: 'auto',
		width: 'auto',
		modal: true,
		title :"Delete Confirm",
		buttons: 
		{
			"DELETE": function() 
			{
				$.ajax
				({
					type: "POST",
					url : "<?php echo base_url('pricelist/export_cost_price_agent/manage_data_selling')?>",
					data : { id : $( '#idSelling' ).val(), oper: 'del' },
					success: function(data)
					{
						var data = $.parseJSON(data);
						if(data.status == 1 )
						{
							get_data('last');
							$('#table-satu-agent').jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
						}
						else
						{
							get_data('pilih');
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

function save_data(){
	var myData = 
	{
		id 					: $( '#idSelling' ).val(),
		oper 					: $( '#operForm' ).val(),
		kode_shipper		: $( '#shipperId' ).val(),
		of_emkl           : $("input[name='emkl_type']:checked").val(),
		ref_no				: $( '#refNo' ).val(),
		tanggal				: $( '#dateForm' ).val(),
		valid					: $( '#validForm' ).val(),
		dari					: $( '#fromForm' ).val(),
		kepada				: $( '#toForm' ).val(),
		attn					: $( '#attnForm' ).val(),		
		peti_kemas			: $( '#containerForm' ).val(),
		kodetruco			: $( '#remarkForm' ).val(),
	}
	var tanggal = $( '#dateForm' ).val();
	var valid   = $( '#validForm' ).val();
	if (tanggal == '' || valid == '')
	{
		var message = '<div id="content-msg">Input \"Date / Valid\" Empty Value</div>';
		show_msg(message);
		return false;
	}
	else
	{
		$.ajax
		({
			type: "POST",
			url : "<?php echo base_url('pricelist/export_cost_price_agent/manage_data_selling')?>",
			data : myData,
			success: function(data)
			{
				var data = $.parseJSON(data);
				if(data.status == 1 )
				{
					get_data('last');
					$('#table-satu-agent').jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
				}
				else
				{
					get_data('pilih');
				};
			},
			error:function()
			{
				var message = '<div id="content-msg">ERROR ACCOURED</div>';
				show_msg(message);
				get_data('pilih');
			}
		});
	}
}

function get_data(input){
	$( '#loading-selling' ).ajaxStart(function(){
		$(this).fadeIn();
		$(this).html('<img src="<?php echo base_url("public/images/loader.gif")?>"/>');
		}).ajaxStop(function(){
		$(this).fadeOut();
	});
	
	$.ajax
	({
			type:"POST",
			url:"<?php echo base_url('pricelist/export_cost_price_agent/get_data') ?>",
			data:{ idSelling : $( '#idSelling' ).val(), shipper_id : $( '#shipperId' ).val(), mode : input },
			success:function(data)
			{
				var data = $.parseJSON(data);
				$( '#idSelling' ).val(data.idSelling);
				$( '#refNo' ).val(data.refNo);
				$( '#dateForm' ).val(data.dateForm);
				$( '#validForm' ).val(data.validForm);
				$( '#fromForm' ).val(data.fromForm);
				$( '#toForm' ).val(data.toForm);
				$( '#attnForm' ).val(data.attnForm);		
				$( '#containerForm' ).val(data.containerForm);
				$( '#remarkForm' ).val(data.remarkForm);	

				$( '#table-satu-agent').jqGrid('setGridParam', { postData: {  ref_no: data.refNo } } ).trigger("reloadGrid");
				$(".ui-jqgrid-titlebar-close",$("#table-satu-agent")[0].grid.cDiv).hide();		
			},
	})
}

function formatUang(input) {
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

function gubahUang(num) {
	if(num.indexOf('.') > -1){
		num = num.split('.');
 		num[0] = num[0].toString().split('').reverse().join('').replace(/(?=\d*\.?)(\d{3})/g,'$1,').split('').reverse().join('').replace(/^[\,]/,'');
		return num[0];
	}
}

</script>