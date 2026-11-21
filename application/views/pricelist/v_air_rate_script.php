<script type="text/javascript">

$(document).ready(function()
{
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
	$('#dateForm').datepicker({dateFormat: 'dd/mm/yy'});
	$('#validForm').datepicker({dateFormat: 'dd/mm/yy'});
	$('#btnDelete').attr('href','#');
	//$("#table-selling").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
	get_data('last');	
	table_listing();
	$(".ui-jqgrid-titlebar-close",$("#table-listing")[0].grid.cDiv).hide();
});


function copy_data()
{
	var id = $("#idSelling").val();
	var ref_no = $('#refNo').val();
	$("#form_copy").html('Are you sure want to copy this data ?');
	$("#form_copy").dialog(
	{
		autoOpen: true,
		resizable: false,
		top:10,
		height: "auto",
		width: "auto",
		modal: true,
		title: "Copy Data Confirm",
		buttons : 
		{  
			"COPY": function()
			{
				$.ajax
				({
					type: "POST",
					url : "<?php echo base_url('pricelist/selling_rate/copy_data')?>/",
					data: { id : id, refNo: ref_no, oper:'copy' },
					success: function(response)
					{
						var data = $.parseJSON(response);
						show_messages('Data Copied Success');
						get_data('last');	
						$("#table-listing").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
					},
					error: function()
					{ 
						show_messages('Data can\'t copy');
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

function get_data(input)
{
	disabled_all()
	if(input=='prev'){
		$("#btnNext").attr('disabled',false)
	}
	if(input=='next'){
		$("#btnPrev").attr('disabled',false)
	}
	
	$( '#loading-selling' ).ajaxStart(function(){
		$(this).fadeIn();
		$(this).html('<img src="<?php echo base_url("public/images/loader.gif")?>"/>');
		}).ajaxStop(function(){
		$(this).fadeOut();
	});
	
	$.ajax
	({
			type:"POST",
			url:"<?php echo base_url('pricelist/air_selling_rate/get_selling') ?>",
			data:
			{ 
				idSelling : $( '#idSelling' ).val(), 
				shipper_id : $( '#shipperId' ).val(), 
				mode : input 
			},
			success:function(data)
			{
				var data = $.parseJSON(data);
				
				$( '#idSelling' ).val(data.aktiv_id);
				$( '#refNo' ).val(data.ref_no);
				$( '#dateForm' ).val(data.tanggal);
				$( '#validForm' ).val(data.valid);
				$( '#fromForm' ).val(data.dari).attr('disabled',true);
				$( '#toForm' ).val(data.kepada);
				$( '#attnForm' ).val(data.attn);		
				$( '#kursForm' ).val(data.kurs_selling);
				$( '#remarkForm' ).val(data.remarks);
	
				checkRadioBtn(data.of_emkl);

				if(data.aktiv_id==data.max_id){
					$("#btnNext").attr('disabled',true)
				}
				if(data.aktiv_id==data.min_id){
					$("#btnPrev").attr('disabled',true)
				}
				if (data.of_emkl == 1){
				$("#air-freight").attr('hidden',true);
				$("#misc").attr('hidden',false);
				$( '#table-selling-misc').jqGrid('setGridParam', { postData: {  id_selling : $( '#idSelling' ).val() } } ).trigger("reloadGrid");
				table_selling();
				$(".ui-jqgrid-titlebar-close",$("#table-selling-misc")[0].grid.cDiv).hide();
				if($("#idSelling").val()!=""){
					$("#del_table-selling").attr('hidden',false);
					$("#btnInsertSelling").show();
					$("input[id=btnEdit]").attr('disabled',false)	
					$("input[id=btnCetak]").attr('disabled',false)	
					$("input[id=btnDelete]").attr('disabled',false)	
					$("input[id=btnCopy]").attr('disabled',false)	
				}
				if($("#idSelling").val()==""){
					$("#del_table-selling").attr('hidden',true);
					$("#btnInsertSelling").hide();
					$("#page-selling").hide();	
					$("input[id=btnEdit]").attr('disabled',true)	
					$("input[id=btnCetak]").attr('disabled',true)	
					$("input[id=btnDelete]").attr('disabled',true)	
					$("input[id=btnCopy]").attr('disabled',true)	
				}
				}else{
				$("#air-freight").attr('hidden',false);
				$("#misc").attr('hidden',true);
				//$( '#table-selling').jqGrid('setGridParam', { postData: {  id_selling : $( '#idSelling' ).val() } } ).trigger("reloadGrid");
				table_selling();
				//$(".ui-jqgrid-titlebar-close",$("#table-selling")[0].grid.cDiv).hide();
				if($("#idSelling").val()!=""){
					$("#del_table-selling").attr('hidden',false);
					$("#btnInsertSelling").show();
					$("input[id=btnEdit]").attr('disabled',false)	
					$("input[id=btnCetak]").attr('disabled',false)	
					$("input[id=btnDelete]").attr('disabled',false)	
					$("input[id=btnCopy]").attr('disabled',false)	
				}
				if($("#idSelling").val()==""){
					$("#del_table-selling").attr('hidden',true);
					$("#btnInsertSelling").hide();
					$("#page-selling").hide();	
					$("input[id=btnEdit]").attr('disabled',true)	
					$("input[id=btnCetak]").attr('disabled',true)	
					$("input[id=btnDelete]").attr('disabled',true)	
					$("input[id=btnCopy]").attr('disabled',true)	
				}
				}
			},
	})
}

function checkRadioBtn(N)
{
   var radioLength = $('input[name="emkl_type"]').length;
   for (var i = 0; i < radioLength; i++) 
	{
    	if ($('input[name="emkl_type"]')[i].value == N) 
		{
			$('input[name="emkl_type"]')[i].checked = true;
        	break;
    	}
    }
}

function disabled_all()
{

	$( '#btnNew' ).attr('disabled',false);
	$( '#btnEdit' ).attr('disabled',false);
	$( '#btnNext' ).attr('disabled',false);
	$( '#btnPrev' ).attr('disabled',false);
	$( '#btnSave' ).attr('disabled',true);
	$( '#btnDelete' ).attr('onClick','delete_data()').val('DELETE');
	$( '#btnCetak' ).attr('onClick','print_data()').val('CETAK');	
	$( '#shipperId' ).attr('disabled',true);
	$( '#shipperAdd' ).attr('disabled',true);
	$( '#refNo' ).attr('disabled',true);
	$( '#dateForm' ).attr('disabled',true);
	$( '#validForm' ).attr('disabled',true);
	$( '#toForm' ).attr('disabled',true);
	$( '#attnForm' ).attr('disabled',true);
	$( '#kursForm' ).attr('disabled',true);
	$( '#remarkForm' ).attr('disabled',true);
	$( '#emkl_type_of' ).attr('disabled',true);
	$( '#emkl_type_misc' ).attr('disabled',true);
	$("#page-selling").show();
	
}

function enabled_all()
{
	$( '#btnNew' ).attr('disabled',true);
	$( '#btnEdit' ).attr('disabled',true);
	$( '#btnNext' ).attr('disabled',true);
	$( '#btnPrev' ).attr('disabled',true);
	$( '#dateForm' ).attr('disabled',false);
	$( '#validForm' ).attr('disabled',false);
	$( '#toForm' ).attr('disabled',false);
	$( '#attnForm' ).attr('disabled',false);
	$( '#kursForm' ).attr('disabled',false);
	$( '#remarkForm' ).attr('disabled',false);
	$( '#emkl_type_of' ).attr('disabled',false);
	$( '#emkl_type_misc' ).attr('disabled',false);
	$( '#del_table-selling' ).attr('hidden',true);
	$( '#table-selling_iladd' ).attr('hidden',true);
	$("#page-selling").hide();	
	<?php 
	if($user_level == 0 || $user_level== 1  || $user_level== 2) {
		echo "$( '#fromForm' ).attr('disabled',false);";
	}
	else
	{
		echo "$( '#fromForm' ).attr('disabled',true);";
	}
	?>
}

function delete_selling()
{
	var id = $('#idSellingRate').val();
	var ref_no = $('#refNo').val();
	$('#message-selling').html('Are you sure want to delete this data ?');
	$( "#message-selling" ).dialog(
	{
		autoOpen: true,
		resizable: true,
		top:10,
		height:"auto",
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
					url : "<?php echo base_url('pricelist/selling_rate/manages_data')?>/",
					data: { id : id, refNo: ref_no, oper:'del' },
					success: function(data)
					{
						var message = 'Deleted Success';
						show_messages(message);
					},
					error: function()
					{ 
						var message = 'Data can\'t deleted';
						show_messages(message);
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

function table_selling()
{
	if($("input[name='emkl_type']:checked").val()){
	var of_misc = document.querySelector('input[name="emkl_type"]:checked').value;
	}else{
	var of_misc = 0;	
	}
	console.log (of_misc);
	if (of_misc==0) {
	var grid = $("#table-selling"), i;	
	grid.jqGrid({ 
		url:"<?php echo base_url('pricelist/air_selling_rate/get_table_air_selling')?>",
		mtype : "post",
		datatype: "json", 
		postData:  {  
			id_selling	: $("input[id='idSelling']").val(),
		},
		colNames:
		[
			'OF/MISC',
			'Air Line',
			'Item Name',
			'Destination',
			'UoM',
			'Cur.',
			/* 'M',
			'N', */
			'45',
			'100',
			'300',
			'500',
			'1000',
			'2000',
			'3000',
			'5000',
			'Refund',
			'Condition',
			'',
		],
    	colModel:
		[
			{name:'of_emkl',index:'of_emkl', align:"center", width:50, 
				editable: false, 
				hidden: true
			},
			{name:'air_line',index:'air_line', align:"left", width:150, 
				editable: true,hidden: false,
				editoptions:{
					dataInit: function (elem) {
					myAutocomplete(elem, "/afg_c_dropdown/ref_airline");
					}
				},
          	},
			{name:'nama_service',index:'nama_service', align:"left", width:150, 
				editable: true, 
				edittype:"select",
				editoptions:
				{
					dataUrl:'<?php echo base_url("afg_c_dropdown/dropdownItemService") ?>',
					dataInit: function (el) 
					{ 
						$(el).css({'max-width':'150px'}); 
					},
				},
				searchoptions: {
					searchhidden: true,
					sopt:['cn','eq'],
          	},
			},
			{name:'destination',index:'destination', align:"left", width:100, editable: true, 
				editoptions:{
					dataInit: function (elem) {
					myAutocomplete(elem, "/afg_c_dropdown/ref_airport");
					$(elem).css({'text-transform':'uppercase'});
					}
				},
          	},
				{name:'type',index:'type', align:"center", width:50, editable: true, 
				edittype:"select",formatter:'select', 
				editoptions:
				{
					value:" : ;SET:SET;KGs:KGs", 
					dataInit: function (el) 
					{ 
						$(el).css({'max-width':'50px'}); 
					},
				},
				searchoptions: {
					searchhidden: true,
					sopt:['cn','eq'],
          	},
			},
			{name:'currency',index:'currency', align:"center", width:70, editable: true, 
				edittype:"select", 
				editoptions:
				{
					value:"USD:USD;RP.:RP.",
					dataInit: function (el) 
					{ 
						$(el).css({'min-width':'70px'}); 
					},
				},
				searchoptions: {
					searchhidden: true,
					sopt:['cn','eq'],
          	},
			},
	
			{name:'empatlima',index:'empatlima', align:"right", width:50, 
				editable: true, 
				editoptions:{
					dataInit: function (el) { 
						$(el).attr({'onKeyup':'format_number(this,2)','onChange':'format_number(this,2)','title':'Hanya boleh memasukan angka'}); 
						$(el).css({'min-width':'50px','text-transform':'uppercase'});
					},
					dataEvents: 
					[{  
						type: 'keydown', 
            		fn: function(e) { 
                		var key = e.charCode || e.keyCode;
                		if (key == 13)//enter
                		{
                    		$("#btnAddDua").click();
                		}
            		}
            	}]
				},
				formatter: 'number',
    			formatoptions: { decimalSeparator: ".", thousandsSeparator: " ", decimalPlaces: 0, defaultValue: '' }, 	
			},
			{name:'seratus',index:'seratus', align:"right", width:50, 
				editable: true, 
				editoptions:{
					dataInit: function (el) { 
						$(el).attr({'onKeyup':'format_number(this,2)','onChange':'format_number(this,2)','title':'Hanya boleh memasukan angka'}); 
						$(el).css({'min-width':'50px','text-transform':'uppercase'});
					},
					dataEvents: 
					[{  
						type: 'keydown', 
            		fn: function(e) { 
                		var key = e.charCode || e.keyCode;
                		if (key == 13)//enter
                		{
                    		$("#btnAddDua").click();
                		}
            		}
            	}]
				},
				formatter: 'number',
    			formatoptions: { decimalSeparator: ".", thousandsSeparator: " ", decimalPlaces: 0, defaultValue: '' }, 	
			},
			{name:'tigaratus',index:'tigaratus', align:"right", width:50, 
				editable: true, 
				editoptions:{
					dataInit: function (el) { 
						$(el).attr({'onKeyup':'format_number(this,2)','onChange':'format_number(this,2)','title':'Hanya boleh memasukan angka'}); 
						$(el).css({'min-width':'50px','text-transform':'uppercase'});
					},
					dataEvents: 
					[{  
						type: 'keydown', 
            		fn: function(e) { 
                		var key = e.charCode || e.keyCode;
                		if (key == 13)//enter
                		{
                    		$("#btnAddDua").click();
                		}
            		}
            	}]
				},
				formatter: 'number',
    			formatoptions: { decimalSeparator: ".", thousandsSeparator: " ", decimalPlaces: 0, defaultValue: '' }, 	
			},
			{name:'limaratus',index:'limaratus', align:"right", width:50, 
				editable: true, 
				editoptions:{
					dataInit: function (el) { 
						$(el).attr({'onKeyup':'format_number(this,2)','onChange':'format_number(this,2)','title':'Hanya boleh memasukan angka'}); 
						$(el).css({'min-width':'50px','text-transform':'uppercase'});
					},
					dataEvents: 
					[{  
						type: 'keydown', 
            		fn: function(e) { 
                		var key = e.charCode || e.keyCode;
                		if (key == 13)//enter
                		{
                    		$("#btnAddDua").click();
                		}
            		}
            	}]
				},
				formatter: 'number',
    			formatoptions: { decimalSeparator: ".", thousandsSeparator: " ", decimalPlaces: 0, defaultValue: '' }, 	
			},
			{name:'seribu',index:'seribu', align:"right", width:50, 
				editable: true, 
				editoptions:{
					dataInit: function (el) { 
						$(el).attr({'onKeyup':'format_number(this,2)','onChange':'format_number(this,2)','title':'Hanya boleh memasukan angka'}); 
						$(el).css({'min-width':'50px','text-transform':'uppercase'});
					},
					dataEvents: 
					[{  
						type: 'keydown', 
            		fn: function(e) { 
                		var key = e.charCode || e.keyCode;
                		if (key == 13)//enter
                		{
                    		$("#btnAddDua").click();
                		}
            		}
            	}]
				},
				formatter: 'number',
    			formatoptions: { decimalSeparator: ".", thousandsSeparator: " ", decimalPlaces: 0, defaultValue: '' }, 	
			},
			{name:'duaribu',index:'duaribu', align:"right", width:50, 
				editable: true, 
				editoptions:{
					dataInit: function (el) { 
						$(el).attr({'onKeyup':'format_number(this,2)','onChange':'format_number(this,2)','title':'Hanya boleh memasukan angka'}); 
						$(el).css({'min-width':'50px','text-transform':'uppercase'});
					},
					dataEvents: 
					[{  
						type: 'keydown', 
            		fn: function(e) { 
                		var key = e.charCode || e.keyCode;
                		if (key == 13)//enter
                		{
                    		$("#btnAddDua").click();
                		}
            		}
            	}]
				},
				formatter: 'number',
    			formatoptions: { decimalSeparator: ".", thousandsSeparator: " ", decimalPlaces: 0, defaultValue: '' }, 	
			},
			{name:'tigaribu',index:'tigaribu', align:"right", width:50, 
				editable: true, 
				editoptions:{
					dataInit: function (el) { 
						$(el).attr({'onKeyup':'format_number(this,2)','onChange':'format_number(this,2)','title':'Hanya boleh memasukan angka'}); 
						$(el).css({'min-width':'50px','text-transform':'uppercase'});
					},
					dataEvents: 
					[{  
						type: 'keydown', 
            		fn: function(e) { 
                		var key = e.charCode || e.keyCode;
                		if (key == 13)//enter
                		{
                    		$("#btnAddDua").click();
                		}
            		}
            	}]
				},
				formatter: 'number',
    			formatoptions: { decimalSeparator: ".", thousandsSeparator: " ", decimalPlaces: 0, defaultValue: '' }, 	
			},
			{name:'limaribu',index:'limaribu', align:"right", width:50, 
				editable: true, 
				editoptions:{
					dataInit: function (el) { 
						$(el).attr({'onKeyup':'format_number(this,2)','onChange':'format_number(this,2)','title':'Hanya boleh memasukan angka'}); 
						$(el).css({'min-width':'50px','text-transform':'uppercase'});
					},
					dataEvents: 
					[{  
						type: 'keydown', 
            		fn: function(e) { 
                		var key = e.charCode || e.keyCode;
                		if (key == 13)//enter
                		{
                    		$("#btnAddDua").click();
                		}
            		}
            	}]
				},
				formatter: 'number',
    			formatoptions: { decimalSeparator: ".", thousandsSeparator: " ", decimalPlaces: 0, defaultValue: '' }, 	
			},
			{name:'rebate',index:'rebate', align:"left", width:50, 
				editable: true, 
				hidden: false, 
				editoptions: { 
					dataInit: function (el) 
					{ 
						$(el).css({'min-width':'50px'});
					},
				}
			},
			{name:'invoice_db',index:'invoice_db', align:"left", width:50, 
				editable: true, 
				hidden: false, 
				edittype:"select",
				formatter:'select', 
				editoptions:
				{
					value:"PREPAID:PREPAID;COLLECT:COLLECT",
					dataInit: function (el) 
					{ 
						$(el).css({'max-width':'50px'}); 
					},
				},
				searchoptions: {
					searchhidden: true,
					sopt:['cn','eq'],
          	},
			},
			{name:'space',index:'space', align:"left", width:30,},
		], 
		sortname: "id",
		sortorder: "asc",
      loadonce : false,
		shrinkToFit: false,
  		viewrecords: true,
      multiselect:true, 
	//	modal: true,
		rownumbers: true,
		rowNum: 5,
	//	top: 20,
		width: 1000,
		height: 150,
		rowList:[5,10,15],
		gridview: true,
		caption: false,
		cellEdit: true,
		editurl:'air_selling_rate/manage_data',
		cellsubmit:'clientArray',
		pager:'#page-selling',
		
		gridComplete : function()
    	{
    		//$("#btnInsertSeling").children(".ui-pg-div").width("133px");
    	},
			
		loadComplete: function(data) {
			$("#cb_"+grid[0].id).hide();
			var ids = grid.jqGrid('getDataIDs');
			if (ids.length>=1) {
    			grid.jqGrid('editCell',ids.length,3,true);
    		}
		},
    	/*
    	beforeSelectRow: function(rowid, e) {
		var lock =  grid.jqGrid('getCell',rowid,'of_emkl');
					if(lock == '1'){
					$(this).jqGrid('setColProp','air_line', { editable:false });
					$(this).jqGrid('setColProp','type', { editable:false });
					$(this).jqGrid('setColProp','empatlima', { editable:false });
					$(this).jqGrid('setColProp','seratus', { editable:false });
					$(this).jqGrid('setColProp','tigaratus', { editable:false });
					$(this).jqGrid('setColProp','limaratus', { editable:false });
					$(this).jqGrid('setColProp','seribu', { editable:false });
					$(this).jqGrid('setColProp','duaribu', { editable:false });
					$(this).jqGrid('setColProp','tigaribu', { editable:false });
					$(this).jqGrid('setColProp','limaribu', { editable:false });
					$(this).jqGrid('setColProp','rebate', { editable:false });
					}
        var cbsdis = $("tr#"+rowid+".jqgrow > td > input.cbox:disabled", grid[0]);
        if (cbsdis.length === 0) {
            return true; 
        		} else {
            return false; 
        }
    	},		
    	/*
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
		*/
		afterSaveCell: function(rowid, cellname, value, iRow, iCol) {
			saving_data(rowid, cellname, value, iRow, iCol);
			if(event.keyCode==13) 
			{
				grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
			}
    	},
    	
    	afterAddRow: function(rowid, cellname, value, iRow, iCol){
			//alert("")
    	},
    	
	})
	
	grid.navGrid('#page-selling',{ edit: false, add: false, del: true, refresh: true, <?php echo 'search: false,' ?> },
		{},
		{},
		{},
		{},
		{}
	)
	
	grid.navButtonAdd('#page-selling',{ 
			caption:"",
			id:"btnInsertSelling", 
			buttonicon:"ui-icon-plus", 
			onClickButton: insert_data, 
			title:"Insert Data Selling Price", 
			cursor: "pointer",
			id:"btnAddDua",
	})
}else{
	var grid = $("#table-selling-misc"), i;	
	grid.jqGrid({ 
		url:"<?php echo base_url('pricelist/air_selling_rate/get_table_air_selling')?>",
		mtype : "post",
		datatype: "json", 
		postData:  {  
			id_selling	: $("input[id='idSelling']").val(),
		},
		colNames:
		[
			'OF/MISC',
			'Item of Service',
			'P/C',
			'Set/M3',
			'Destination',
			'Currency',
			'Sales Comission',
			'Price',
			'Remarks',
			'',
		],
    	colModel:
		[
			{name:'of_emkl',index:'of_emkl', align:"center", width:50, 
				editable: false, 
				hidden: true
			},
			{name:'nama_service',index:'nama_service', align:"left", width:150, 
				editable: true, 
				edittype:"select",
				editoptions:
				{
					dataUrl:'<?php echo base_url("afg_c_dropdown/dropdownItemService") ?>',
					dataInit: function (el) 
					{ 
						$(el).css({'max-width':'150px'}); 
					},
				},
				searchoptions: {
					searchhidden: true,
					sopt:['cn','eq'],
          	},
			},
			{name:'invoice_db',index:'invoice_db', align:"center", width:50, editable: true, 
				edittype:"select",
				formatter:'select', 
				editoptions:
				{
					value:"PREPAID:PREPAID;COLLECT:COLLECT",
					dataInit: function (el) 
					{ 
						$(el).css({'max-width':'50px'}); 
					},
				},
				searchoptions: {
					searchhidden: true,
					sopt:['cn','eq'],
          	},
			},
			{name:'type',index:'type', align:"center", width:50, editable: true, 
				edittype:"select",formatter:'select', 
				editoptions:
				{
					value:"SET:SET;KGs:KGs", 
					dataInit: function (el) 
					{ 
						$(el).css({'max-width':'50px'}); 
					},
				},
				searchoptions: {
					searchhidden: true,
					sopt:['cn','eq'],
          	},
			},
			{name:'destination',index:'destination', align:"left", width:150, editable: true, 
				edittype:"select", 
				editoptions:
				{
					dataUrl:'<?php echo base_url("afg_c_dropdown/dropdownCities") ?>',
					dataInit: function (el) 
					{ 
						$(el).css({'max-width':'150px'});  
					}
				},
				searchoptions: {
					searchhidden: true,
					sopt:['cn','eq'],
          	},
			},
			{name:'currency',index:'currency', align:"center", width:70, editable: true, 
				edittype:"select", 
				editoptions:
				{
					value:"USD:USD;RP.:RP.",
					dataInit: function (el) 
					{ 
						$(el).css({'min-width':'70px'}); 
					},
				},
				searchoptions: {
					searchhidden: true,
					sopt:['cn','eq'],
          	},
			},
			{name:'rebate',index:'rebate', align:"right", width:100, editable: true, 
				formatter: 'number',
    			formatoptions: { decimalSeparator: ".", thousandsSeparator: " ", decimalPlaces: 2, defaultValue: '' },
    			editoptions: { 
    				dataInit: function (el) 
					{ 
						$(el).attr({'onKeyup':'format_number(this,2)','onChange':'format_number(this,2)','title':'Hanya boleh memasukan angka'}); 
						$(el).css({'min-width':'100px'}); 
					}
				},
				searchoptions: {
					searchhidden: true,
					sopt:['cn','eq'],
          	},
			},
			{name:'price',index:'price', align:"right", width:70, editable: true, 
				formatter: 'number',
    			formatoptions: { decimalSeparator: ".", thousandsSeparator: " ", decimalPlaces: 2, defaultValue: '' },
    			editoptions: { 
    				dataInit: function (el) 
					{ 
						$(el).attr({'onKeyup':'format_number(this,2)','onChange':'format_number(this,2)','title':'Hanya boleh memasukan angka'}); 
						$(el).css({'min-width':'70px'});
					},
				},
				searchoptions: {
					searchhidden: true,
					sopt:['cn','eq'],
          	},
			},
			{name:'remarks',index:'remarks', align:"left", width:250, 
				editable: false, 
				editoptions: { 
					dataInit: function (el) 
					{ 
						$(el).css({'min-width':'50px'});
					},
				}
			},
			{name:'space',index:'space', align:"left", width:30,},
		], 
		sortname: "id",
		sortorder: "asc",
      loadonce : false,
		shrinkToFit: false,
  		viewrecords: true,
      multiselect:true, 
	//	modal: true,
		rownumbers: true,
		rowNum: 5,
	//	top: 20,
		width: 1000,
		height: 150,
		rowList:[5,10,15],
		gridview: true,
		caption: false,
		cellEdit: true,
		editurl:'selling_rate/manage_data',
		cellsubmit:'clientArray',
		pager:'#page-selling-misc',
		
		gridComplete : function()
    	{
    		//$("#btnInsertSeling").children(".ui-pg-div").width("133px");
    	},
			
		loadComplete: function(data) {
			$("#cb_"+grid[0].id).hide();
			var ids = grid.jqGrid('getDataIDs');
			if (ids.length>=1) {
    			grid.jqGrid('editCell',ids.length,3,true);
    		}
		},
    	/*
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
		*/
		afterSaveCell: function(rowid, cellname, value, iRow, iCol) {
			saving_data(rowid, cellname, value, iRow, iCol);
			if(event.keyCode==13) 
			{
				grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
			}
    	},
    	
    	afterAddRow: function(rowid, cellname, value, iRow, iCol){
			//alert("")
    	},
    	
	})
	
	grid.navGrid('#page-selling-misc',{ edit: false, add: false, del: true, refresh: true, <?php echo 'search: false,' ?> },
		{},
		{},
		{},
		{},
		{}
	)
	
	grid.navButtonAdd('#page-selling-misc',{ 
			caption:"",
			id:"btnInsertSelling", 
			buttonicon:"ui-icon-plus", 
			onClickButton: insert_data, 
			title:"Insert Data Selling Price", 
			cursor: "pointer",
			id:"btnAddDua",
	})
	
//	grid.jqGrid('GridUnload');
	/*
	grid.inlineNav('#page-selling',{ 
		edit: false, 
		editicon: "ui-icon-pencil", 
		add: true, 
		addicon:"ui-icon-plus", 
		save: false, 
		saveicon:"ui-icon-disk", 
		cancel: false, 
		cancelicon:"ui-icon-cancel",
   	addParams : 
   	{
   		useFormatter : true,
   		position :"last",
   	},
   	editParams : {
   		useFormatter : true,
   	}
	})
	*/
}
}

function saving_data(rowid, cellname, value, iRow, iCol)
{
	let of_misc = document.querySelector('input[name="emkl_type"]:checked').value;
	if (of_misc==0) {
	var grid = $("#table-selling"), i;
	}else{
	var grid = $("#table-selling-misc"), i;
	}
	var myData = grid.getRowData(rowid);
	$.ajax
	({
		type	:"POST",
		url	:window.location.origin+'/pricelist/air_selling_rate/manage_data',
		data	:
		{
			oper : 'edit',
			id		 : rowid,
			myData : myData,
		},
		success:function(response)
		{
			var data = $.parseJSON(response);
		}
	})
}

function insert_data() {
	let of_misc = document.querySelector('input[name="emkl_type"]:checked').value;
	if (of_misc==0) {
	var grid = $("#table-selling"), i;
	}else{
	var grid = $("#table-selling-misc"), i;	
	}
	$.ajax
	({
		type	:"POST",
		url	:window.location.origin+'/pricelist/air_selling_rate/manage_data',
		data	:
		{
			oper : 'add',
			id_selling : $("input[id='idSelling']").val(),
			of_emkl : document.querySelector('input[name="emkl_type"]:checked').value,
		},
		success:function(response)
		{
			var data = $.parseJSON(response);
			if(data.status==1){
				var dataSatu = [{'id':data.id}];
				grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
			}
		}
	})
}

function save_data_table(rowid, cellname, value, iRow, iCol)
{

	if(rowid.substr(0,1)== 'j'){
		var oper = 'add';
	}
	else
	{
		var oper = 'edit';	
	}
	
	if(cellname=='sales_comission' || cellname=='discount' || cellname=='price'){
		value = value.replace(/\ /g,'');
	}
	
	emkl = $("#table-selling").jqGrid('getCell',rowid,'of_emkl')
	
	var DataDetail = 
	{
		oper : oper,
		id : rowid,
		id_selling : $("input[id='idSelling']").val(),
		kolom	: cellname,
		isi	: value,
		cek	: iRow,
		ceking	: $("input[id='codeItemService']").val(),
		of_emkl : emkl,
	}
	
	$.ajax({
		type: "POST", 
		url : "<?php echo base_url('pricelist/selling_rate/manage_data')?>",
		data: DataDetail,
		success:function(data)
		{
			$("#table-selling").jqGrid("setGridParam",  { datatype: 'json', } ).trigger("reloadGrid");
		}
	})

}

function table_listing()
{
	var grid = $("#table-listing"), i;	
	grid.jqGrid(
	{ 
		url	:"<?php echo base_url('pricelist/air_selling_rate/get_table_listing')?>",
		mtype : "post",
		datatype: "json", 
		postData: { 
			shipper_id : $('#shipperId').val(),
		},
		colNames:
		[
			'','Ref No','Tanggal','Valid','Kepada','UP','Type','Sales'
		],
    	colModel:
		[
			{name:'id_selling',index:'id_selling', align:"center", width:60, editable: true, hidden : true},
			{name:'ref_no',index:'ref_no', align:"center", width:60, editable: true,},
			{name:'tanggal',index:'tanggal', align:"center", width:60, editable: true,},
			{name:'valid',index:'valid', align:"center", width:60, editable: true,},
			{name:'kepada',index:'kepada', align:"left", width:340, editable: true,},
			{name:'attn',index:'attn', align:"left", width:200, editable: true,},
			{name:'of_emkl',index:'of_emkl', align:"left", width:200, editable: true,formatter:of_emkl},
			{name:'dari',index:'dari', align:"left", width:200, editable: true,},
		], 
		rowNum: 10000,
		autoencode: true,
		loadonce: true,
		sortable: true,
		sortname: "id_selling",
		sortorder: "desc",
		width: 1000,
		height: 400,
		rowList:[40,80,120],
		caption: false,
		editurl:'manage_data',
		cellsubmit:'clientArray',
		pager:'#page-sellisting',
		
		loadComplete: function () {
        var $self = $(this);
        if ($self.jqGrid("getGridParam", "datatype") === "json") {
            setTimeout(function () {
                $self.trigger("reloadGrid"); // Call to fix client-side sorting
				}, 50);
			}
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
		ondblClickRow: function(rowid, e){
			var selected = $("tr.jqgrow > td > input.cbox:checked", grid[0]);						
			selected[rowid] = status;
			$('#idSelling').val(rowid);
    		get_data('pilih');
    		$('#tabs-lading ul li:first a').trigger('click');	 			
		}
	}).navGrid('#page-sellisting',{ edit: false, add: false, del: false,},
	{height: "auto", width: "auto"},
      	{modal: true, height: "auto", width: "auto", closeAfterSearch : true, multipleSearch: false,},
     		{ }
    );

}
function of_emkl (cellvalue, options, rowObject)
		{
			if (cellvalue == 1)
			return "MISCELLANEOUS";
			else if (cellvalue == 0)
			return "FREIGHT CHARGES";
		}

function exit_data()
{
	$('#Form-Destination').dialog('close')
}

function cancel(input)
{
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

function new_data()
{
	var rowCount  = $("#table-listing").getGridParam("reccount");
	if( rowCount > 0 )
	{
		$.strPad = function(i,l,s) {
			var o = i.toString();
			if (!s) { s = '0'; }
				while (o.length < l) {
				o = s + o;
			}
			return o;
		}
		var nom = eval(rowCount)+1;
		var newRefNo =  $.strPad( nom , 4, '0');		
		var newRef   = '<?php echo date("y")."-".date("m")."-"?>'+newRefNo;
	}	
	else
	{
		var newRef  = '<?php echo date("y")."-".date("m")."-"?>'+'0001';
	}
	
	enabled_all();
	$( '#btnDelete' ).val('SAVE').attr('onClick','save_data()').attr('disabled',false);
	$( '#btnCetak' ).val('CANCEL').attr('onClick','cancel("add")').attr('disabled',false);
	$( '#idSelling' ).val('');
	$( '#operForm' ).val('add');
	$( '#table-selling' ).jqGrid("clearGridData", true);
	$( '#idSelling' ).val('');
	$( '#refNo' ).val(newRef);
	$( '#dateForm' ).val('');
	$( '#validForm' ).val('');
	$( '#fromForm' ).val('');
	$( '#toForm' ).val($('#shipperAdd').val());
	$( '#attnForm' ).val('');
	$( '#kursForm' ).val('');
	$( '#remarkForm' ).val('');
	$( '#emkl_type' ).val('');
	$( '#dropdownType' ).val('');
}

function edit_data()
{
	enabled_all()
	$( '#btnDelete' ).val('SAVE').attr('onClick','save_data()');
	$( '#btnCetak' ).val('CANCEL').attr('onClick','cancel("add")');
	$( '#operForm' ).val('edit');
}

function delete_data()
{
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
					url : "<?php echo base_url('pricelist/air_selling_rate/manage_data_selling')?>",
					data : { id : $( '#idSelling' ).val(), oper: 'del' },
					success: function(data)
					{
						var data = $.parseJSON(data);
						if(data.status == 1 )
						{
							get_data('last');
							$('#table-listing').jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
						}
						else
						{
							get_data('pilih');
						};
						$( '#delete-confirm' ).dialog('close');
					},
					error:function()
					{
						var message = 'Data can\'t be deleted';
						show_messages(message);
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

function next_data()
{
	get_data('next');
}

function prev_data()
{
	get_data('prev');
}

function save_data()
{
	var myData = 
	{
		id 					: $( '#idSelling' ).val(),
		oper 					: $( '#operForm' ).val(),
		kode_shipper		: $( '#shipperId' ).val(),
		of_emkl           : $( "input[name='emkl_type']:checked").val(),
		ref_no				: $( '#refNo' ).val(),
		tanggal				: $( '#dateForm' ).val(),
		valid					: $( '#validForm' ).val(),
		dari					: $( '#fromForm' ).val(),
		kepada				: $( '#toForm' ).val(),
		attn					: $( '#attnForm' ).val(),		
		kurs_selling		: $( '#kursForm' ).val(),
		remarks  			: $( '#remarkForm' ).val(),
	}
	
	var valid   = $( '#validForm' ).val();
	if ( valid == '' )
	{
		var message = 'Valid Can Not be Empty Value';
		swal(message);
		return false;
	}
	else
	{
		$.ajax
		({
			type: "POST",
			url : "<?php echo base_url('pricelist/air_selling_rate/manage_data_selling')?>",
			data : myData,
			success: function(data)
			{
				var data = $.parseJSON(data);
				if(data.status == 1 )
				{
					if(data.mode=='edit'){
						get_data('pilih');		
					} else {
						get_data('last');
					}
					$('#table-listing').jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
				}
				else
				{
					return false;
				}
			},
			error:function()
			{
				var message = 'ERROR ACCOURED';
				show_messages(message);
				get_data('pilih');
			}
		});
	}
}

function formatUang(input){
	var num = input.value.replace(/\ /g,'');
	if(!isNaN(num)){
   	if(num.indexOf('.') > -1){
    		num = num.split('.');
    		num[0] = num[0].toString().split('').reverse().join('').replace(/(?=\d*\.?)(\d{3})/g,'$1 ').split('').reverse().join('').replace(/^[\ ]/,'');
    		if(num[1].length > 2){
				var message = 'Only 2 ( two ) desimal allowed';
				show_messages(message);
    			num[1] = num[1].substring(0,num[1].length-1);
    		} input.value = num[0]+'.'+num[1];
    	} else{ input.value = num.toString().split('').reverse().join('').replace(/(?=\d*\.?)(\d{3})/g,'$1 ').split('').reverse().join('').replace(/^[\ ]/,'') };
	}
	else
	{ 
		var message = 'Only numeric allowed';
		show_messages(message);
    	input.value = input.value.substring(0,input.value.length-1); 
	}
}

function gubahUang(num){
	if(num.indexOf('.') > -1){
		num = num.split('.');
 		num[0] = num[0].toString().split('').reverse().join('').replace(/(?=\d*\.?)(\d{3})/g,'$1 ').split('').reverse().join('').replace(/^[\ ]/,'');
		return num[0];
	}
	return num.toString().split('').reverse().join('').replace(/(?=\d*\.?)(\d{3})/g,'$1 ').split('').reverse().join('').replace(/^[\ ]/,'');
}

if(!window.location.origin) {
	window.location.origin=window.location.protocol+'//'+window.location.hostname+(window.location.port+':'+window.location.port)
}

serialize = function(obj, prefix) {
  	var str = [], p;
  	for(p in obj) {
    	if (obj.hasOwnProperty(p)) {
			var k = prefix ? prefix + "[" + p + "]" : p, v = obj[p];
			str.push((v !== null && typeof v === "object") ?
        	serialize(v, k) :
        	encodeURIComponent(k) + "=" + encodeURIComponent(v));
    	}
  	}
  	return str.join("&");
}

function print_data()
{
	var data = {};
	var to = $('#shipperAdd').val();
	var from = $('#fromForm').val();
	var tanggal = $('#dateForm').val();
	var valid = $('#validForm').val();
	var attn = $('#attnForm').val();
	var shipper = $('#shipperId').val();
	var remarks = $('#remarkForm').val();
	var id	 = $("input[id='idSelling']").val();
	
	data.to = to;
	data.from = from;
	data.date = tanggal;
	data.valid = valid;
	data.attn = attn;
	data.shipper = shipper;
	data.remarks = remarks;
	data.id = id;
	
	var url=serialize(data)
	
   if(tanggal == '/ /' && valid == '/ /'){
   	var message = 'Please insert [ DATE ] [ VALID ] first';
		show_messages(message);
   	return false;
   }
   else
   {
   	var datetgl = tanggal.substring(6,tanggal.length)+tanggal.substring(3,tanggal.length-5)+tanggal.substring(0,tanggal.length-8); 
   	var datevalid = valid.substring(6,valid.length)+valid.substring(3,valid.length-5)+valid.substring(0,valid.length-8);
		var date  = datetgl+datevalid;
		
		$("#PRINT" ).dialog({
			position: "top",
			autoOpen: true,
			resizable: true,
			height: 'auto',
			width: 980,
			modal: true,
			title: "SELLING PRICE TO "+to,
			buttons : {  },
			close: function()  {$(this).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>')}
		});
		
		$('#PRINT').html('<object id="form-data" data="air_selling_rate/print_data?'+url+'" width=" 950px" height="600" type="application/pdf" \>');
	
	
	}
}

function myAutocomplete(elem, url) {
        $(elem).autocomplete({
        minLength: 3,
        source: url,
        autoFocus: true,
        scroll: true,
        select: function( event, ui ) { $(this).focus() }
    }).focus(function() {
        $(this).autocomplete("search", "");
    }).live("blur", function(event) {
        var autocomplete = $(this).data("autocomplete");
        var matcher = new RegExp("^" + $.ui.autocomplete.escapeRegex($(this).val()) + "$", "i");
        autocomplete.widget().children(".ui-menu-item").each(function() 
          {
            var item = $(this).data("item.autocomplete");
            if (matcher.test(item.label || item.value || item)) {
                //There was a match, lets stop checking
                autocomplete.selectedItem = item;
                return;
            }
        });

        if (autocomplete.selectedItem) {
            autocomplete._trigger("select", event, {
                item: autocomplete.selectedItem
            });
        //there was no match, clear the input
        } else {
            $(this).val('');
        }
        });
}


</script>