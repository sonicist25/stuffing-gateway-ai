<script type="text/javascript" >
$(document).ready(function () {
	$('#form-table-empat').attr('hidden',true);
	$('#invoice_date').datepicker({dateFormat: 'dd-mm-yy'});
	get_table_dua();
});

function get_table_dua() {
	var grid = $("#table_dua"), i;
	grid.jqGrid( {
      url:"<?php echo base_url('finance/c_proforma_invoice/get_table_dua')?>",
  		mtype : "post",
  		datatype: "json",
  		postData: {id_akhir : $("input[name='id_akhir']").val()},
  		sortname: "kode_ref",
		sortorder: "desc",
   	colNames:['Job No', 'Job Date','Shipper / Consignee','Destination','Shiping Co','Feeder Vesel','','','','','','','','','',''], 
  		colModel:
   	[
			{name:'trnstuffingplandetail.kode_ref',index:'trnstuffingplandetail.kode_ref', width:120, align:"center",
				cellattr: function(rowId, cellValue, rawObject, cm, rdata) {
					if(rawObject[14]=='0'){
    					return 'class="Blue"';
    				}
				},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			} ,
			{name:'stuffing_date',index:'stuffing_date', width:70, align:"center",
				stype:"text",
				searchoptions:{ 
					sopt:['eq','lt','gt'],
					dataInit: function(element) {
        				$(element).datepicker({dateFormat: 'yymmdd'})
      			}
				},
				cellattr: function(rowId, cellValue, rawObject, cm, rdata) {
					if(rawObject[14]=='0'){
    					return 'class="Blue"';
    				}
				},
			},
			{name:'note',index:'note', width:185, align:"left",
				formatter: function( cellvalue, options, rowObject ){
					var kode_ref = rowObject[0];
					if(kode_ref.substr(4,3) == '300'|| kode_ref.substr(4,3) == '301'|| kode_ref.substr(4,3) == '302'|| kode_ref.substr(4,3) == '400'||kode_ref.substr(4,3) == '402'||kode_ref.substr(4,3) == '600'){
						return rowObject[11];
					} else {
						return rowObject[8];
					}
					
				},
			
				cellattr: function(rowId, cellValue, rawObject, cm, rdata) {
					if(rawObject[14]=='0'){
    					return 'class="Blue"';
    				}
				},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'trnstuffingplandetail.destination',index:'trnstuffingplandetail.destination', width:100, align:"left", <?php echo 'search: false,' ?>
				cellattr: function(rowId, cellValue, rawObject, cm, rdata) {
					if(rawObject[14]=='0'){
    					return 'class="Blue"';
    				}
				},
			},
			{name:'carrier',index:'carrier', width:100, align:"left",
				cellattr: function(rowId, cellValue, rawObject, cm, rdata) {
					if(rawObject[14]=='0'){
    					return 'class="Blue"';
    				}
				},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'vessel',index:'vessel', width:150, align:"left",
				cellattr: function(rowId, cellValue, rawObject, cm, rdata) {
					if(rawObject[14]=='0'){
    					return 'class="Blue"';
    				}
				},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},						
			{name:'kurs',index:'kurs', width:10, align:"left", hidden: true},
			{name:'sales',index:'sales', width:10, align:"left", hidden: true},
			{name:'id_shipper',index:'id_shipper', width:10, align:"left",hidden: true},
			{name:'nama_shipper',index:'nama_shipper', width:10, align:"left", hidden: true},
			{name:'alamat_shipper',index:'alamat_shipper', width:10, align:"left", hidden: true},
			{name:'code_consignee',index:'code_consignee', width:10, align:"left", hidden: true},
			{name:'nama_consignee',index:'nama_consignee', width:10, align:"left", hidden: true},
			{name:'alamat_consignee',index:'alamat_consignee', width:10, align:"left", hidden: true},
			{name:'trk_inv',index:'trk_inv', width:10, align:"left", hidden: true},
			{name:'region_id',index:'region_id', width:70, align:"left", },
		],
		shrinkToFit: false,
		multiselect:true,
		loadonce : false,
		cellEdit: false,
		rowNum:6,
		width: 835,
		height: 155,
		rowList:[6,12,18,24],
		viewrecords: true,
		rownumbers: true,
		gridview: true,
		modal: true,
      caption:"Tabel Job",
		pager: '#pager-2',

		loadComplete: function() {
			$("#cb_"+grid[0].id).hide();
			$(".ui-jqgrid-titlebar-close",$("#table_dua")[0].grid.cDiv).hide();	
			
			var rowid = grid.getDataIDs();
			var id = rowid.shift()
    		getInvoice(id);				
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
    			$('#btnNew').attr({'hidden':false,'disabled':false,'onClick':'new_invoice('+rowid+')'});	
    			$('#btnEdit').attr('hidden',true);
				getInvoice(rowid); 
     		}
        	else
        	{
        		var cbs = $("tr.jqgrow > td > input.cbox:disabled", grid[0]);  		
    			cbs.attr("disabled", false);  
    			$('#btnEdit').attr({'hidden':false,'disabled':true})
				$('#btnNew').attr({'hidden':false,'disabled':true})
        	}
		}
		
   })
   grid.jqGrid('navGrid', '#pager-2', {edit: false, add: false,  del: false, refresh: true}, { }, { }, { },
   	{ 
   		modal: true, 
   		height: "auto", 
   		width: 420, 
   		closeAfterSearch : true, 
   		caption: "Find Job ..",
   	}, 
   	{ }
   );
   $.extend($.jgrid.search, {
         width: 410,
   		height: "auto",
   		position:"center",
			autoOpen: true,
   		closeAfterSearch : true, 
   		modal:true, 
   		recreateForm:true,
   		caption:"Find Job",
                afterRedraw: function (p) {
                    var $form = $(this), formId = this.id, // fbox_list
                        bindKeydown = function () {
                            $form.find("td.data>.input-elm").keydown(function (e) {
                                if (e.which === $.ui.keyCode.ENTER) {
                                    $(e.target).change();
                                    $("#" + $.jgrid.jqID(formId) + "_search").click();
                                }
                            });
                        },
                        oldOnChange = p.onChange,
                        myOnChange = function (param) {
                            var $input = $form.find("td.data>.input-elm"), events;
                            oldOnChange.call(this, param);
                            if ($input.length > 0) {
                                events = $input.data("events");
                                if (events && !events.keydown) {
                                    bindKeydown();
                                }
                            }
                        };
                    p.onChange = myOnChange;
                    bindKeydown.call(this);
                }
     });

}

function getInvoice(rowid)
{
	var grid = $("#table_dua"), i;
	var kode_ref =  grid.jqGrid('getCell',rowid,'trnstuffingplandetail.kode_ref');
	
	if(kode_ref == false){
		$("input[id='NOMER_JOB']").val('')
		$('#NOMER_JOB').val('');
		$('#TANGGAL_JOB').val('');
		$('#KURS').val('');
		$('#SALESMAN').val('');
		$('#KOSTUMER').val('');
		$('#KET_KOSTUMER').val('');
		$('#ALAMAT_KOSTUMER').val('');
	} else {
		$('#NOMER_JOB').val(kode_ref);
		$('#TANGGAL_JOB').val(grid.jqGrid('getCell',rowid,'stuffing_date'));
		$('#KURS').val(grid.jqGrid('getCell',rowid,'kurs'));
		$('#SALESMAN').val(grid.jqGrid('getCell',rowid,'sales'));
		$('#destinationForm').val(grid.jqGrid('getCell',rowid,'destination'));
		
		if(kode_ref.substr(4,3) == '300'|| kode_ref.substr(4,3) == '301'|| kode_ref.substr(4,3) == '302'|| kode_ref.substr(4,3) == '400'||kode_ref.substr(4,3) == '402'||kode_ref.substr(4,3) == '600'){
			$('#KOSTUMER').val(grid.jqGrid('getCell',rowid,'code_consignee'));
			$('#KET_KOSTUMER').val(grid.jqGrid('getCell',rowid,'nama_consignee'));
			$('#ALAMAT_KOSTUMER').val(grid.jqGrid('getCell',rowid,'alamat_consignee'));
		} else {
			$('#KOSTUMER').val(grid.jqGrid('getCell',rowid,'id_shipper'));
			$('#KET_KOSTUMER').val(grid.jqGrid('getCell',rowid,'nama_shipper'));
			$('#ALAMAT_KOSTUMER').val(grid.jqGrid('getCell',rowid,'alamat_shipper'));
		}
	}
	
	$('#table_satu').jqGrid('setGridParam', 
	{ 
		datatype: "json", 
		loadonce: true, 
		_search: true, 
		postData: {  job_no : kode_ref, } 
	}).trigger("reloadGrid");	
			
	get_table_satu(kode_ref);
}

function get_table_satu()
{
	var grid = $("#table_satu"), i;
	grid.jqGrid({
		url:"<?php echo base_url('finance/c_proforma_invoice/get_table_satu')?>",
		mtype : "post",  
		postData: 
		{  
			job_no : arguments[0], 
			costomer : $("input[name='costumer_id']").val(),
		}, 
		datatype: "json",
		colNames:['','JENIS','Inv No', 'Date','Group','Kepada'],
		colModel:
		[
			{name:'job_no',index:'job_no', width:5, align:"center",hidden: true},
			{name:'jenis',index:'jenis', width:20, align:"center",hidden: false},
			{name:'inv_no',index:'inv_no', width:75, align:"center"},
			{name:'date',index:'date', width:70, align:"center"},
			{name:'group',index:'group', width:45, align:"center",
				formatter: function(el,em,ek){
					if(el.toUpperCase()=="NULL"){
					 return '';
					}
					return el.toUpperCase();
				}
			},
			{name:'kepada',index:'kepada', width:130, align:"left",
				formatter: function(el,em,ek){
					return el.toUpperCase()
				}
			},
		],
		sortname: "id",
		sortorder: "asc",
		shrinkToFit: false,
		multiselect:true,
		loadonce : false,
		cellEdit: false,
		rowNum:6,
		width: 385,
		height: 67,
		rowList:[6,12,18],
		pager: '#page-1',
		hiddengrid:false,
		caption:false,
		loadComplete: function() {
			$("#cb_"+grid[0].id).hide();
			$(".ui-jqgrid-titlebar-close",$("#table_satu")[0].grid.cDiv).hide();
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
    			$('#btnNew').attr('hidden',true);    			   			
				$('#btnEdit').attr({'hidden':false,'disabled':false,'onClick':'edit_invoice('+rowid+')'});
     		}
        	else
        	{
        		var cbs = $("tr.jqgrow > td > input.cbox:disabled", grid[0]);  		
    			cbs.attr("disabled", false); 
 				$('#btnEdit').attr({'hidden':false,'disabled':true})
				$('#btnNew').attr({'hidden':false,'disabled':true})	    			
        	}
		}
		
	}).navGrid('#page-1',{edit:false,add:false,del:false, refresh: false, <?php echo 'search: false' ?> });
}

function open_form()
{
	var invoice_no = $('#invoice_no').val();
	$('#no_invoice').attr('readonly','readonly');
	$('#no_invoice').val(invoice_no);
}

function new_invoice()
{
	var spd = arguments[0];
	var job_no = $("#table_dua").jqGrid('getCell',spd,'trnstuffingplandetail.kode_ref');
	$( "#form-invoice" ).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
	$( "#form-invoice" ).dialog(
	{
		autoOpen: true,
		resizable: false,
		height: 530,
		width: 785,
		modal: true,
		title :"INVOICE",
		close: function() 
		{	
			$(this).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
		}
	});
	
	$.ajax
	({
		type:"POST",
		url:"<?php echo site_url('finance/c_proforma_invoice/get_last_inv_no'); ?>",
		data: 
		{
			job_no : job_no,
		},
		success:function(data)
		{
			var data = $.parseJSON(data);
			var new_no = data.new_no;
			open_form_invoice(job_no,new_no,'add',spd);
		}
	})
}

function open_form_invoice()
{
	$.ajax({
		type: "POST", 
		url : "<?php echo site_url('finance/c_proforma_invoice/open_form_invoice')?>",
		data: 
		{
			job_no : arguments[0],
			inv_no : arguments[1], 
			oper 	 : arguments[2],
			spd	 : arguments[3],
		},
		success:function(XMLHttpRequest)
		{
			$( "#form-invoice" ).html(XMLHttpRequest)
		},	
		error:function(XMLHttpRequest)
		{
			$('#form-invoice').html(XMLHttpRequest.responseText)
		}	
	});
}

function edit_invoice(rowid)
{
	
	$( "#form-invoice" ).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
	$( "#form-invoice" ).dialog(
	{
		autoOpen: true,
		resizable: false,
		height: 580,
		width: 795,
		modal: true,
		title :"INVOICE",
		close: function() 
		{	
			$(this).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
		}
	});
	
	var grid = $('#table_satu'),i;
	var group  	= grid.jqGrid('getCell',rowid,'group');
	var date  	= grid.jqGrid('getCell',rowid,'date');
	var jenis  	= grid.jqGrid('getCell',rowid,'jenis');
	var inv_no  = grid.jqGrid('getCell',rowid,'inv_no');
	
	$.ajax({
		type: "POST", 
		url : "<?php echo site_url('finance/c_proforma_invoice/open_form_invoice')?>",
		data: 
		{ 
			id 		: rowid,
			job_no 	: $("input[id='NOMER_JOB']").val(),
			group 	: group,
			date		: date,
			jenis 	: jenis,
			inv_no	: inv_no,
			oper		: 'edit',
		},
		success:function(XMLHttpRequest)
		{
			$( "#form-invoice" ).html(XMLHttpRequest)
		},	
		error:function(XMLHttpRequest)
		{
			$( "#form-invoice" ).html(XMLHttpRequest.responseText)
		}	
	});
}



</script>