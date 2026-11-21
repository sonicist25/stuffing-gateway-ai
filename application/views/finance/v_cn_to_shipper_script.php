<script type="text/javascript" >

$(function () {
	get_table_satu();
	$( '#dropdownJob').html(': <select id="jobNoForm" style="width: 200px;" class="text-input" disabled="disabled">'+
            					'<option value=""></option>'+
									'</select>');
	$('#tglForm').datepicker({dateFormat: 'dd/mm/yy'});
	$('#form-table-satu').attr('hidden',true);
});

function browse_cn()
{
	$( "#form-table-satu" ).css('background-image', 'url("../public/images/bg-form.png")');
	$( "#form-table-satu" ).dialog(
	{
		position: "top",
		autoOpen: true,
		resizable: false,
		height: 'auto',
		width: 800,
		modal: true,
		title: "CREDIT NOTE TO SHIPPER",
	});
}

function get_table_satu()
{
	var grid = $("#table_satu"), i;
	grid.jqGrid( {
      url:window.location.origin+'/finance/c_cn_to_shipper/get_table_satu',
  		mtype : "post",
  		datatype: "json",
  		sortname: "id",
		sortorder: "desc",
   	colNames:['Nomor CN', 'Tanggal','Job No','Shipper Code','Shipper Name','','','','',''], 
  		colModel:
   	[
			{name:'number',index:'number', width:80, align:"center"} ,
			{name:'date',index:'date', width:70, align:"center"},
			{name:'job_no',index:'job_no', width:120, align:"center"},
			{name:'kepada_kode',index:'kepada_kode', width:150, align:"left",},
			{name:'kepada_nama',index:'kepada_nama', width:240, align:"left",},
			{name:'kepada_alamat',index:'kepada_alamat', width:150, hidden:true},	
			{name:'group',index:'group', width:150, hidden:true},	
			{name:'se_exportir',index:'se_exportir', width:150, hidden:true},	
			{name:'first_id',index:'first_id', width:50, hidden:true},	
			{name:'last_id',index:'last_id', width:50, hidden:true},	
		],
		shrinkToFit: false,
		multiselect:true,
		loadonce : false,
		cellEdit: false,
		rowNum:25,
		width: 765,
		height: 552,
		rowList:[1,2,3,4,5],
		viewrecords: true,
		rownumbers: true,
		gridview: true,
		modal: true,
      caption: false,
		pager: '#pager-satu',

		loadComplete: function() {
			$("#cb_"+grid[0].id).hide();
			$(".ui-jqgrid-titlebar-close",$("#table_satu")[0].grid.cDiv).hide(); 
			var rowid = grid.getDataIDs();
			var id = rowid.shift();
			$('input[id="id_aktif"]').val(id);
			get_content('pilih');			
    	},
    	ondblClickRow: function(rowid,status){
			$( '#form-table-satu' ).fadeOut('slow', function() 
			{
				$('input[id="id_aktif"]').val(rowid);
				get_content('pilih');
				$( "#form-table-satu" ).dialog('close');
			});
		}
   }).jqGrid('navGrid', '#pager-satu', {edit: false, add: false,  del: false,},
   	{ },
   	{ },
   	{ },
   	{ modal: true, height: "auto", width: "auto", multipleSearch : false, closeAfterSearch : true, caption: "Fine Data ..",},
   	{ }
   );
}

function get_content(mode)
{
	$.ajax
	({
		type:"POST",
		url:window.location.origin+'/finance/c_cn_to_shipper/get_content',
		data:{ id : $('input[id="id_aktif"]').val(), mode : mode },
		success:function(response)
		{
			var data = $.parseJSON(response);
			
			$( '#dropdownJob').html(': <select id="jobNoForm" style="width: 200px;" class="text-input" disabled="disabled">'+
            					'<option value="'+data.job_no+'">'+data.job_no+'</option>'+
									'</select>');
			$( "#groupFormSE" ).val(data.group);
			$( "#cnForm" ).val(data.number);
			$( "#tglForm" ).val(data.date);
			$( "#shipperForm" ).val(data.kepada_kode);
			$( "#shipperName" ).val(data.kepada_nama);
			$( "#shipperAddress" ).val(data.kepada_alamat);
			$( "#seForm" ).val(data.se_exportir);
			
			$('input[id="id_aktif"]').val(data.id);
			
			$("#table_dua").jqGrid('setGridParam',{ datatype: "json", postData:{  number: data.id, }}).trigger("reloadGrid")
			get_table_dua();
					
			if(data.id==data.id_first){
				$('input[id="btnPrev"]').attr('disabled',true);
				$('input[id="btnNext"]').attr('disabled',false);
			} else if(data.id==data.id_last){
				$('input[id="btnNext"]').attr('disabled',true)
				$('input[id="btnPrev"]').attr('disabled',false)
			} else {
				$('input[id="btnNext"]').attr('disabled',false)
				$('input[id="btnPrev"]').attr('disabled',false)
			}
			
			$( "#btntambah" ).attr('hidden',false);
			$( "#btntarik" ).attr('hidden',false);		
		}
	})
}

function get_table_dua()
{
	var number = $('input[id="id_aktif"]').val();
	var grid = $("#table_dua"), i;
	grid.jqGrid( {
      url:window.location.origin+'/finance/c_cn_to_shipper/get_table_dua',
  		mtype : "post",
  		datatype: "json",
  		postData:{ 
  			number: number,
  		},
  		sortname: "id",
		sortorder: "asc",
   	colNames:['Service', 'Qty','Currency','Unit Price','Price','P / C'], 
  		colModel:
   	[
			{name:'item',index:'item', width:270, align:"left",
				editable: true, 
				edittype:"text",
				editoptions:{
					dataInit: function(element) {
        				$(element).css({ "text-transform": "uppercase"})
      			},
				},
				cellattr: function(rowId, cellValue, rawObject, cm, rdata) {
					return 'style="text-transform: uppercase"';
				}
			} ,
			{name:'qty',index:'qty', width:90, align:"center",
				editable: true, 
				formatter : 'number',
				formatoptions: { decimalSeparator: ".", thousandsSeparator: " ", decimalPlaces: 4, defaultValue: '' },
				editoptions: {
    				dataInit : function (e) {
						$(e).css({'max-width':'90px','min-width':'90px',"text-align":"center"})
        				$(e).attr({ "onChange":"format_number(this,4)","onKeyUp":"format_number(this,4)"});
					},
					dataEvents: 
					[{  type: 'focus',
                  	fn: function(e) {
                       	//on_edit(true);
                   	}
               }]
				}
			},
			{name:'currency',index:'currency', width:90, align:"center",
				editable: true, 
				edittype:"select",
				formatter:'select', 
				editoptions:{
					value:" : ;USD:USD;RP.:RP.",
					dataInit: function (el) 
					{ 
						$(el).css({'min-width':'90px','max-width':'90px'}); 
					},
				}
			},
			{name:'unit_price',index:'unit_price', width:90, align:"right",
				editable: true, 
				edittype:"text",
				formatter : 'number',
				formatoptions: { decimalSeparator: ".", thousandsSeparator: " ", decimalPlaces: 2, defaultValue: '' },
				editoptions: {
    				dataInit : function (e) {
						$(e).css({'max-width':'90px','min-width':'90px',"text-align":"center"})
        				$(e).attr({ "onChange":"format_number(this,2)","onKeyUp":"format_number(this,2)"});
					},
					dataEvents: 
					[{  type: 'focus',
                  	fn: function(e) {
                       	//on_edit(true);
                   	}
               }]
				}
			},
			{name:'price',index:'price', width:90, align:"right",
				editoptions:{
					disabled: true,
				},
				formatter: "number",
    			formatoptions: { decimalSeparator: ".", thousandsSeparator: " ", decimalPlaces: 2, defaultValue: '' },
			},
			{name:'pre_col',index:'pre_col', width:100, align:"center",
				 editable: true, 
				 edittype:"select",
				 formatter:'select', 
				 editoptions:{
				 	value:" : ;COLLECT:COLLECT;PREPAID:PREPAID",
				 	dataInit : function (el) {
						$(el).css({'min-width':'100px','max-width':'100px'}); 
					},
					dataEvents: 
					[{  type: 'focus',
                  	fn: function(e) {
                  		var rowid = grid.jqGrid('getGridParam', 'selrow');
                       	var qty = grid.jqGrid('getCell',rowid,'qty');
                       	var unit_price = grid.jqGrid('getCell',rowid,'unit_price');
                       	var price = eval(qty) * eval(unit_price);
                        grid.jqGrid('setCell',rowid,'price',price);
                        
                        $.ajax
								({
									type : "POST",
									url  : window.location.origin+'/finance/c_cn_to_shipper/update_price',
									data : { id : rowid, price : price },
									success:function(response)
									{
										var data = $.parseJSON(response);
										if (data.status==1) {
											//show_messages("PPN Updated")
										}	
										else 
										{
											//show_messages("No Kode Ref "+dPost["kode_ref"]+" and item = "+dPost["item"]+" finance_item table");
										}
									}
								})
                   	}
               }]
				 }
			},		
		],
		shrinkToFit: false,
		multiselect:true,
		loadonce : false,
		cellEdit:true,
		cellsubmit:'clientArray',
		rowNum:10,
		width: 820,
		height: 190,
		rowList:[10,20,30],
		viewrecords: true,
		rownumbers: true,
		gridview: true,
		modal: true,
      caption:"Credite Note to Shippper",
		pager: '#pager-dua',
		editurl	:	window.location.origin+'/finance/c_cn_to_shipper/manage_data_detail',
		
		
		loadComplete: function() {
			$("#cb_"+grid[0].id).hide(); 	
			$(".ui-jqgrid-titlebar-close",$("#table_dua")[0].grid.cDiv).hide();	
    	},	
    	
    	afterSaveCell: function(rowid, cellname, value, iRow, iCol) {
			saving_cn(rowid, cellname, value, iRow, iCol);
			if(event.keyCode==13) 
			{
				grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
			}
    	},	
		
		
   }).jqGrid('navGrid', '#pager-dua', {edit: false, add: false,  del: true, <?php echo 'search: false,' ?>},
   	{ },
   	{ },
   	{ },
   	{ modal: true, height: "auto", width: "auto", multipleSearch : false, closeAfterSearch : true, caption: "Fine Data ..",},
   	{ }
   );
   grid.jqGrid('navButtonAdd','#pager-dua',{ 
			caption:"", 
			buttonicon:"ui-icon-plus", 
			onClickButton: insert_data, 
			position: "last", 
			title:"Insert Data CN to Shipper", 
			cursor: "pointer",
			id:'btntambah',
	})
	
	grid.navButtonAdd('#pager-dua',{ 
			caption:"", 
			buttonicon:"ui-icon-disk", 
			onClickButton: tarik_data, 
			position: "last", 
			title:"Tarik Data CN to Shipper", 
			cursor: "pointer",
			id:'btntarik',
	})

}

function insert_data()
{
	$.ajax({
		type: "POST", 
		url : window.location.origin+'/finance/c_cn_to_shipper/manage_data_detail',
		data: 
		{
			number: $('input[id="id_aktif"]').val(),
			oper	: 'add',
		},
		success: function(data)
		{
			var data = $.parseJSON(data);
			$("#table_dua").jqGrid('setGridParam',{ datatype: "json",}).trigger("reloadGrid")
		},
		error:function(XMLHttpRequest)
		{
			//$('#dropdownJob').html(XMLHttpRequest.responseText)
		}	
	})
}

function tarik_data()
{
	$.ajax({
		type: "POST", 
		url : window.location.origin+'/finance/c_cn_to_shipper/tarik_data_detail',
		data: 
		{
			number 	   	: $('input[id="id_aktif"]').val(),
			kode_ref 		: $( "#jobNoForm" ).val(),
			shipper  		: $( "#shipperForm" ).val(),
		},
		success: function(data)
		{
			var data = $.parseJSON(data);
			$("#table_dua").jqGrid('setGridParam',{ datatype: "json",}).trigger("reloadGrid")
		},
		error:function(XMLHttpRequest)
		{
			//$('#dropdownJob').html(XMLHttpRequest.responseText)
		}	
	})
}

function saving_cn(rowid, cellname, value, iRow, iCol)
{
	var grid = $("#table_dua"), i;	
	var myData = grid.getRowData(rowid);
	$.ajax
	({
		type	:"POST",
		url	:window.location.origin+'/finance/c_cn_to_shipper/saving_cn',
		data	:
		{
			id		 : rowid,
			myData : myData,
		},
		success:function(response)
		{
			var data = $.parseJSON(response);
		}
	})
}


function refresh_cn()
{
	$("#table_satu").jqGrid('setGridParam',{ datatype: "json", }).trigger("reloadGrid");
	$("#table_dua").jqGrid('setGridParam',{ datatype: "json", }).trigger("reloadGrid");
}

function new_cn()
{
	$( '#dropdownJob').html(': <select id="jobNoForm" style="width: 200px;" class="text-input" disabled="disabled">'+
            					'<option value=""></option>'+
									'</select>');
	$( "#operForm" ).val('add');
	$( "#groupFormSE" ).val('');
	$( "#tglForm" ).val('');
	$( "#shipperForm" ).val('');
	$( "#shipperName" ).val('');
	$( "#shipperAddress" ).val('');
	$( "#seForm" ).val('');	
	$( "#cnForm" ).val('');
	
	$( "#btntambah" ).attr('hidden',true);
	$( "#btntarik" ).attr('hidden',true);
	
	$("#table_dua").jqGrid("clearGridData", true);
	
	$.ajax({
		type: "POST", 
		url : window.location.origin+'/finance/c_cn_to_shipper/dropdown_job_no',
		data: 
		{
			job_no	:'',
			oper		: 'add',
		},
		success: function(data)
		{
			var data = $.parseJSON(data);
			var new_no = <?php echo $this->fungsi->get_new_num_inv('a')?>;
			var no_akhir = new_no;			
			if(no_akhir > 0){
				$.strPad = function(i,l,s) {
					var o = i.toString();
					if (!s) { s = '0'; }
						while (o.length < l) {
						o = s + o;
					}
					return o;
				}
				var nomer = eval(no_akhir);
				var nomer_akhir = $.strPad( nomer.toString() , 5, '0') ;
			}
			var year = '<?php echo substr($this->session->userdata('db'),2,2) ?>';
			var region_id = '<?php echo $this->session->userdata("region_id") ?>';
			if(region_id == 'Cikarang'){
				$("#cnForm").val('INC/'+year+nomer_akhir);
			}else if(region_id == 'Semarang'){
				$("#cnForm").val('SMINF/'+year+nomer_akhir);
			}else{
			$("#cnForm").val('IN/'+year+nomer_akhir);
			}
			$('#jobNoForm').select2({
					placeholder: 'Select a job number',
					allowClear: true,
					width: '100%',
					minimumInputLength: 3,  // Minimum characters before search
					ajax: {
						url: window.location.origin + '/finance/c_cn_to_shipper/dropdown_job_no',
						type: 'GET',
						dataType: 'json',
						delay: 250,  // Delay to reduce requests
						data: function(params) {
							return {
								q: params.term  // User input
							};
						},
						processResults: function(data) {
							// Check if data.results exists and is an array
							if (data && data.results && data.results.length) {
								return {
									results: data.results
								};
							} else {
								return {
									results: []
								};
							}
						},
						cache: true
					}
				});
		}
	});
	$('#jobNoForm').on('change', function() {
    var selectedJobNo = $(this).val();  // Get selected job number
    if (selectedJobNo) {
        get_nama_alamat(selectedJobNo);  // Call get_nama_alamat function
    }
	});
	disabled_false();
	button_true();
}

function edit_cn()
{
	var job_no = $('#jobNoForm').val();
	$( "#operForm" ).val('edit');
	disabled_false();	
	button_true();
	$.ajax({
		type: "POST", 
		url : window.location.origin+'/finance/c_cn_to_shipper/dropdown_job_no',
		data: 
		{
			job_no: job_no,
			oper	: 'edit',
		},
		success: function(data)
		{
			var data = $.parseJSON(data);
			$('#dropdownJob').html(data.dropdown)
		},
		error:function(XMLHttpRequest)
		{
			$('#dropdownJob').html(XMLHttpRequest.responseText)
		}	
	})
}

function cancel_cn()
{
	button_false();
	disabled_true();	
	get_content('pilih');
}

function save_cn()
{
	var year = new Date().getFullYear().toString().substring(2, 4);
	var job  = $("select[id='jobNoForm']").val();
	if(job=='0'){
		$("select[id='jobNoForm']").focus();
		show_messages('<div id="content-msg">Please complite request data</div>');
	} 
	else if ($('#tglForm').val()==''){
		$("input[id='tglForm']").focus();
		show_messages('<div id="content-msg">Tanggal Can\'t Empty Value</div>');
	}
	else {
		$.ajax({
			type: "POST", 
			url : window.location.origin+'/finance/c_cn_to_shipper/manage_data',
			data: 
			{
				id						:	$('#id_aktif').val(),
				oper					:	$('#operForm').val(),
				job_no				:	$('#jobNoForm').val(),
				jenis					:	'CREDIT NOTE',
				number				:	$('#cnForm').val(),
				date					:	$('#tglForm').val().substr(6,4)+$('#tglForm').val().substr(3,2)+$('#tglForm').val().substr(0,2),
				group					:	$('#groupFormSE').val().toUpperCase(),
				kepada			   :	'<?php echo $kepada ?>',
				kepada_kode			:	$('#shipperForm').val(),
				se_exportir			:	$('#seForm').val(),
				region_id			:	'<?php echo $this->session->userdata("region_id") ?>',
			},
			success: function(data)
			{
				var data = $.parseJSON(data);
				$("#table_satu").jqGrid('setGridParam',{ datatype: "json", }).trigger("reloadGrid");
				$("#table_dua").jqGrid('setGridParam',{ datatype: "json", }).trigger("reloadGrid");
				disabled_true();
				button_false();
				if(data.oper=='add'){
					get_content('last');
					show_messages('<div id="content-msg">Insert new data CN success</div>');
				} 
				else if(data.oper=='edit'){
					get_content('pilih');
					show_messages('<div id="content-msg">Update data CN success</div>');
				}
			},
			error:function(XMLHttpRequest)
			{
				$('#dropdownTD').html(XMLHttpRequest.responseText)
			}	
		})
	}
}

function disabled_false()
{
	$( "#jobNoForm" ).attr('disabled',false);
	$( "#groupFormSE" ).attr('disabled',false);
	$( "#tglForm" ).attr('disabled',false);
	$( "#shipperForm" ).attr('disabled',false);
	$( "#shipperName" ).attr('disabled',false);
	$( "#shipperAddress" ).attr('disabled',false);
	$( "#seForm" ).attr('disabled',false);
}

function disabled_true()
{
	$( "#jobNoForm" ).attr('disabled',true);
	$( "#groupFormSE" ).attr('disabled',true);
	$( "#cnForm" ).attr('disabled',true);
	$( "#tglForm" ).attr('disabled',true);
	$( "#shipperForm" ).attr('disabled',true);
	$( "#shipperName" ).attr('disabled',true);
	$( "#shipperAddress" ).attr('disabled',true);
	$( "#seForm" ).attr('disabled',true);
	$( "#operForm" ).val('');
}

function button_false()
{
	$( "#btnNew" ).attr('disabled',false);
	$( "#btnEdit" ).attr('disabled',false);
	$( "#btnPrev" ).attr('disabled',false);
	$( "#btnNext" ).attr('disabled',false);
	$( "#btnDel" ).val('DELETE').attr('onClick','delete_cn()');
	$( "#btnPrint" ).val('PRINT').attr('onClick','print_cn()');
	$( "#btnBrowse" ).attr('disabled',false).val('BROWSE').attr('onClick','browse_cn()');
	$( "#btnRefresh" ).attr('disabled',false);
}

function button_true()
{
	$( "#btnNew" ).attr('disabled',true);
	$( "#btnEdit" ).attr('disabled',true);
	$( "#btnPrev" ).attr('disabled',true);
	$( "#btnNext" ).attr('disabled',true);
	$( "#btnDel" ).val('CANCEL').attr('onClick','cancel_cn()');
	$( "#btnPrint" ).val('SAVE').attr('onClick','save_cn()');
	$( "#btnBrowse" ).attr('disabled',false).val('CANCEL').attr('onClick','cancel_cn()');
	$( "#btnRefresh" ).attr('disabled',true);
}

function get_nama_alamat()
{
	
	$("#jobNoForm").live("change",function()
	{
	$.ajax
		({
			type:"POST",
			url:window.location.origin+'/finance/c_cn_to_shipper/get_nama_alamat',
			data:
			{
				job_no : $("#jobNoForm").val(),
			},
			success:function(response)
			{
				var data = $.parseJSON(response);
				$('#shipperForm').val(data[0].shipper_id);
				$('#shipperName').val(data[0].shipper_name);
				$('#shipperAddress').val(data[0].shipper_address);
			}
		})
	})	
}

function print_cn()
{
	
	$( "#out_cn" ).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
	$( "#out_cn" ).dialog(
	{ 
		autoOpen: true,
		resizable: false,
		height: 'auto',
		width: 960,
		modal: true,
		position:['center',0],
		title: "Finance Proses",
		close: function()  {	$(this).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>') }
	});
	
	var url = serialize({
		number : $('input[id="id_aktif"]').val(),
		jenis	: 'CREDIT NOTE',
		kepada : $("input[id='shipperName']").val()+'\n'+$("textarea[id='shipperAddress']").val(),
		no_invoice : $('input[id="cnForm"]').val(),
		date	: $('input[id="tglForm"]').val().replace(/\//g,'-'),
		job	: $('#jobNoForm').val(),
		region_id : '<?php echo $this->session->userdata("region_id") ?>',
	})
	
	$('#out_cn').html('<center><object data="'+window.location.origin+'/finance/c_cn_to_shipper/print_out?'+url+'" width=" 960px" height="600px" type="application/pdf"></center>');
	
	/*
		$.ajax
		({
			type: "POST",
			url : window.location.origin+'/finance/c_cn_to_shipper/print_out',
			data: dataPrint,
			success: function(data)
			{
				$('#out_cn').dialog( { title: "Finance Proses" } );
				$('#out_cn').html('<center><iframe src="<?php echo base_url("finance/c_cn_to_shipper/print_out") ?>" width=" 960px" height="600"></iframe></center>');
			},
			error:function(XMLHttpRequest)
			{
				$('#out_cn').dialog( { title: "Finance Proses Error" } );
				$('#out_cn').html(XMLHttpRequest.responseText)
			}
		})
		
		*/
}

</script>

<?php 

/*
function get_data(input){
	curPage = $("#table_satu").getGridParam('page');
	var nextPage = eval(curPage) + eval(1);
	var prevPage = eval(curPage) - eval(1);	
	if(input=='next'){
		$('#btnPrev').attr('disabled',false);
		rowid = eval($( '#id_aktif' ).val()) - eval(1);
		idakhir = eval($( '#id_akhir' ).val()) - eval(1);	
		if(rowid==idakhir){						
			$("#table_satu").jqGrid().trigger('reloadGrid', [{ page: nextPage }]);
		}	
	} 
	else if(input=='prev'){
		$('#btnNext').attr('disabled',false);
		rowid = eval($( '#id_aktif' ).val()) + eval(1);
		idawal = eval($( '#id_awal' ).val()) + eval(1);	
		if(rowid==idawal){						
			$("#table_satu").jqGrid().trigger('reloadGrid', [{ page: prevPage }]);
		}
	}
	else if(input=='pilih'){
		rowid = $( '#idForm' ).val();					
		$("#table_satu").jqGrid('setGridParam', {datatype: 'json'}).trigger('reloadGrid', [{ page: curPage }]);
	}
	get_content(rowid);
}

function get_content(rowid){
	var grid = $("#table_satu"), i;
	var job = grid.jqGrid('getCell',rowid,'job_no');	
	var firstid = grid.jqGrid('getCell',rowid,'first_id')
	var lastid = grid.jqGrid('getCell',rowid,'last_id');
	
	if(rowid==lastid){
		$('#btnPrev').attr('disabled',true);
	}
	
	if(rowid==firstid){
		$('#btnNext').attr('disabled',true);
	}	
	
	if(job==false){
		$( '#dropdownJob').html(': <select id="jobNoForm" style="width: 200px;" class="text-input" disabled="disabled">'+
            					'<option value=""></option>'+
									'</select>');
		$( "#groupFormSE" ).val('');
		$( "#cnForm" ).val('');
		$( "#tglForm" ).val('');
		$( "#shipperForm" ).val('');
		$( "#shipperName" ).val('');
		$( "#shipperAddress" ).val('');
		$( "#seForm" ).val('');
	} else {
		$( "#id_aktif" ).val(rowid);
		$( '#dropdownJob').html(': <select id="jobNoForm" style="width: 200px;" class="text-input" disabled="disabled">'+
            					'<option value="'+job+'">'+job+'</option>'+
									'</select>');
		$( "#groupFormSE" ).val(grid.jqGrid('getCell',rowid,'group'));
		$( "#cnForm" ).val(grid.jqGrid('getCell',rowid,'number'));
		$( "#tglForm" ).val(grid.jqGrid('getCell',rowid,'date'));
		$( "#shipperForm" ).val(grid.jqGrid('getCell',rowid,'kepada_kode'));
		$( "#shipperName" ).val(grid.jqGrid('getCell',rowid,'kepada_nama'));
		$( "#shipperAddress" ).val(grid.jqGrid('getCell',rowid,'kepada_alamat'));
		$( "#seForm" ).val(grid.jqGrid('getCell',rowid,'se_exportir'));		
		
		$('#id_first').val(grid.jqGrid('getCell',rowid,'first_id'));
		$('#id_last').val(grid.jqGrid('getCell',rowid,'last_id'));
	}
	
	get_table_dua()
}

function delete_cn(){
	$.ajax({
		type: "POST", 
		url : "<?php echo site_url('finance/c_cn_to_shipper/manage_data')?>",
		data: 
		{
			id:$('#id_aktif').val(),
			cn:$('#cnForm').val(),
			kode_ref:$('select[id="jobNoForm"]').val(),
			oper:'del',
		},
		success: function(data)
		{
			var data = $.parseJSON(data);
			if(data.status > 0 ){
				$("#table_dua").jqGrid('setGridParam',{ datatype: "json", }).trigger("reloadGrid");
				$("#table_satu").jqGrid('setGridParam',{ datatype: "json", }).trigger("reloadGrid");
				show_messages('<div id="content-msg">CN has been deleted</div>');
			}			
		},
		error:function(XMLHttpRequest)
		{
			$('#dropdownTD').html(XMLHttpRequest.responseText)
		}	
	})
}

	/*
	var grid = $("#table_satu"), i;
	var job = grid.jqGrid('getCell',rowid,'job_no');	
	var firstid = grid.jqGrid('getCell',rowid,'first_id')
	var lastid = grid.jqGrid('getCell',rowid,'last_id');
	
	if(rowid==lastid){
		$('#btnPrev').attr('disabled',true);
	}
	
	if(rowid==firstid){
		$('#btnNext').attr('disabled',true);
	}	
	
	if(job==false){
		$( '#dropdownJob').html(': <select id="jobNoForm" style="width: 200px;" class="text-input" disabled="disabled">'+
            					'<option value=""></option>'+
									'</select>');
		$( "#groupFormSE" ).val('');
		$( "#cnForm" ).val('');
		$( "#tglForm" ).val('');
		$( "#shipperForm" ).val('');
		$( "#shipperName" ).val('');
		$( "#shipperAddress" ).val('');
		$( "#seForm" ).val('');
	} else {
		$( "#id_aktif" ).val(rowid);
		$( '#dropdownJob').html(': <select id="jobNoForm" style="width: 200px;" class="text-input" disabled="disabled">'+
            					'<option value="'+job+'">'+job+'</option>'+
									'</select>');
		$( "#groupFormSE" ).val(grid.jqGrid('getCell',rowid,'group'));
		$( "#cnForm" ).val(grid.jqGrid('getCell',rowid,'number'));
		$( "#tglForm" ).val(grid.jqGrid('getCell',rowid,'date'));
		$( "#shipperForm" ).val(grid.jqGrid('getCell',rowid,'kepada_kode'));
		$( "#shipperName" ).val(grid.jqGrid('getCell',rowid,'kepada_nama'));
		$( "#shipperAddress" ).val(grid.jqGrid('getCell',rowid,'kepada_alamat'));
		$( "#seForm" ).val(grid.jqGrid('getCell',rowid,'se_exportir'));		
		
		$('#id_first').val(grid.jqGrid('getCell',rowid,'first_id'));
		$('#id_last').val(grid.jqGrid('getCell',rowid,'last_id'));
	}
	
	get_table_dua()
*/ 

?>