<script type="text/javascript" >
$(document).ready(function () 
{
    $('#invoice_date').datepicker({dateFormat: 'dd-mm-yy'});

    $('#btnNew').prop('disabled', true);

    $('#btnEdit')
        .prop('disabled', true)
        .removeAttr('onclick')
        .prop('onclick', null)
        .off('click');

    get_table_dua();
});

function get_table_dua()
{
	var grid = $("#table_dua"), i;
	grid.jqGrid( {
      url:"<?php echo base_url('finance/c_finance_process/get_table_dua')?>",
  		mtype : "post",
  		datatype: "json",
		colNames:['Job No','','',''], 
  		colModel:
   	[
			{name:'kode_ref',index:'kode_ref', width:120, align:"left",
				cellattr: function(rowId, cellValue, rawObject, cm, rdata) {
					if(rawObject[3]=='0'){
    					return 'class="Blue"';
    				}
				},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],  
				}
			},
			{name:'agen_penagihan',index:'agen_penagihan', width:10, align:"left", hidden:true},
			{name:'trk_data',index:'trk_data', width:10, align:"left", hidden:true},
			{name:'blue',index:'blue', width:10, align:"left", hidden:true},
		],
		sortname:'kode_ref',
		sortorder:'desc',
		shrinkToFit: false,
		multiselect:true,
		loadonce : false,
		cellEdit: false,
		rowNum:10,
		width: 850,
		height: 260,
		rowList:[10,20,30],
		viewrecords: true,
		rownumbers: true,
		gridview: true,
		modal: true,
		hiddengrid: false,
      caption:"Job No #",
		pager: '#pager-2',

		loadComplete: function() {
			$("#cb_"+grid[0].id).hide();
			$(".ui-jqgrid-titlebar-close",$("#table_dua")[0].grid.cDiv).hide();	
			var rowid = grid.getDataIDs();
			var id = rowid.shift()
    		get_content(id); 		  		
    	},

    	beforeSelectRow: function(rowid, e) {
    		var cbs = $("tr.jqgrow > td > input.cbox:checked", grid[0]);
    		cbs.removeAttr("checked");
    		var cbsdis = $("tr#"+rowid+".jqgrow > td > input.cbox:disabled", grid[0]);
        	if (cbsdis.length === 0) {
        		cbsdis.removeAttr("checked")
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
				//var cbs = $("tr.jqgrow > td > input.cbox:checked", grid[0]);  
				//cbs.removeAttr("checked"); 
				selected.attr("checked", true);  
    			
    			$('#btnNew').attr('disabled',false).attr('onClick','new_proses('+rowid+')');
    			$('#btnEdit').attr('disabled',true);
    			get_content(rowid);
    			//alert(rowid)
     		}
        	else
        	{
        		//var cbs = $("tr.jqgrow > td > input.cbox:disabled", grid[0]);  
        		//cbs.attr("disabled", false); 
        		$('#btnNew').attr('disabled',true); 
    			$('#btnEdit').attr('disabled',true);			
        	}
		}
		
   }).jqGrid('navGrid', '#pager-2', {edit: false, add: false,  del: false,},
   	{ },
   	{ },
   	{ },
   	{ modal: true, height: 'auto', width: 340, closeAfterSearch : true, caption: "Search Job ..",},
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
   		caption:"Search Job",
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

function get_content(rowid)
{
	$("input[id='id_aktif']").val(rowid);
	
	var grid = $("#table_dua"), i;
	var kode_ref = grid.jqGrid('getCell',rowid,'kode_ref');
	if( kode_ref == false ){
		$("input[id='kode_refForm']").val('');
	}	
	else 
	{
		$("input[id='kode_refForm']").val(kode_ref);
	}
	
	$('#table_tiga').jqGrid('setGridParam', 
	{ 
		datatype: "json", 
		mtype : "post",  
		postData: { 
			table: 'x_finance', 
			job_no : kode_ref, 
		}
	}).trigger("reloadGrid");
						
	get_table_tiga();
	
	$.ajax({
		type: "POST", 
		url : "<?php echo site_url('finance/c_finance_process/get_content')?>",
		data: 
		{
			kode_ref : kode_ref, 
		},
		success:function(response)
		{
			var data = $.parseJSON(response);
			var std = data.stuffing_date;
			var etd = data.etd_jkt;
			var eta = data.eta;
			
			if(std==null){
				$("input[id='stuffing_dateForm']").val('');
			} else {
				$("input[id='stuffing_dateForm']").val(std.substr(6,2)+'/'+std.substr(4,2)+'/'+std.substr(0,4));
			}
			if(etd==null){
				$("input[id='etd_jktForm']").val('');
			} else {
				$("input[id='etd_jktForm']").val(etd.substr(6,2)+'/'+etd.substr(4,2)+'/'+etd.substr(0,4));
			}
			if(eta==null){
				$("input[id='eta_destinationForm']").val('');
			} else {
				$("input[id='eta_destinationForm']").val(eta.substr(6,2)+'/'+eta.substr(4,2)+'/'+eta.substr(0,4));
			}
			
			$("input[id='vesselForm']").val(data.vessel);
			$("input[id='port_loadingForm']").val(data.port_loading);
			$("input[id='port_dischargeForm']").val(data.destination);
			
			$("input[id='destinationForm']").val(data.destination);
			$("input[id='voyageForm']").val(data.voyage);
			$("input[id='mother_blForm']").val(data.mother_bl);
			$("input[id='cont_seal_noForm']").val(data.cont_seal_no);
			
		}	
	})
}

function get_table_tiga()
{
	var kode_ref = $('#kode_refForm').val();
	var grid = $("#table_tiga"), i;
	grid.jqGrid( {
      url:"<?php echo base_url('finance/c_finance_process/get_table_tiga')?>",
  		mtype : "post",
  		datatype: "json",
  		postData: { table: 'x_finance', job_no : kode_ref },
   	colNames:['DN/CN No','Date','','Bill To','G','','Jenis','','Status','Payment','Creator'], 
  		colModel:
   	[
			{name:'number',index:'number', width:100, align:"center"},
			{name:'date',index:'date', width:80, align:"center"},
			{name:'kepada',index:'kepada', width:100, align:"center", hidden: true},
			{name:'kepada_kode',index:'kepada_kode', width:100, align:"center"},
			{name:'group',index:'group', width:20, align:"center",
				formatter: function(el,em,ek){
					if(el==0){
						return '';
					} else {
						return el;
					}
				}
			},										
			{name:'currency',index:'currency', width:90, align:"center", hidden: true},					
			{name:'jenis',index:'jenis', width:90, align:"center", hidden: false},					
			{name:'ppn',index:'ppn', width:90, align:"center", hidden: true},					
			{name:'kurs',index:'kurs', width:90, align:"center", hidden: false},					
			{name:'status',index:'status', width:90, align:"center", hidden: false,
			formatter: function(cellvalue, options, rowObject) {
						if (cellvalue == 'paid')
							return '<img src="'+window.location.origin+'/public/images/green-dot-icon.png"/>' + cellvalue;
						else if (cellvalue == 'unpaid')
							return '<img src="'+window.location.origin+'/public/images/blue-dot-icon.png"/>' + cellvalue;
						else
							return "";
				}
			},
			{name:'created_by',index:'created_by', width:70, align:"center"},			
		],
		shrinkToFit: false,
		multiselect:true,
		loadonce : true,
		cellEdit: false,
		rowNum:50,
		width: 670,
		height: 260,
		rowList:[10,20,30],
		viewrecords: true,
		rownumbers: true,
		gridview: true,
		modal: true,
      caption:"Tabel Data",
	//	pager: '#pager-3',

		loadComplete: function() {
			$("#cb_"+grid[0].id).hide();
			$(".ui-jqgrid-titlebar-close",$("#table_tiga")[0].grid.cDiv).hide();
    	},

    	beforeSelectRow: function(rowid, e) {
    		var cbs = $("tr.jqgrow > td > input.cbox:checked", grid[0]);
    		cbs.removeAttr("checked");
        	var cbsdis = $("tr#"+rowid+".jqgrow > td > input.cbox:disabled", grid[0]);
        	if (cbsdis.length === 0) {
            return true; 
        		} else {
            return false; 
        	}
    	},		
    	
		onSelectRow: function(rowid, status) {

			var selected = $("tr.jqgrow > td > input.cbox:checked", grid[0]);
			selected[rowid] = status;

			var ppn = $('#table_tiga').jqGrid('getCell', rowid, 'ppn');

			console.log('EDIT SELECT:', {
				rowid: rowid,
				ppn: ppn,
				status: status
			});

			/*
			 * WAJIB buang inline onclick lama.
			 * .off() saja tidak menghapus onclick="..."
			 */
			$('#btnEdit')
				.removeAttr('onclick')
				.prop('onclick', null)
				.off('click')
				.off('click.editProses');

			if (status) {

				$('#btnNew').prop('disabled', true);

				$('#btnEdit')
					.prop('disabled', false)
					.on('click.editProses', function (e) {

						e.preventDefault();

						edit_proses(
							parseInt(rowid, 10),
							String(ppn || '')
						);

						return false;
					});

				var tbl = $("table#table_dua > tr.jqgrow > td > input.cbox:checked");
				tbl.prop('checked', false);

			} else {

				$('#btnNew').prop('disabled', true);

				$('#btnEdit')
					.prop('disabled', true)
					.removeAttr('onclick')
					.prop('onclick', null)
					.off('click')
					.off('click.editProses');
			}
		}
		
   }).jqGrid('navGrid', '#pager', {edit: false, add: false,  del: false,},
   	{ },
   	{ },
   	{ modal: true, height: "auto", width: "auto", closeAfterSearch : true, caption: "Fine Data ..",},
   	{ }
   );

}

function open_form()
{
	var invoice_no = $('#invoice_no').val();
	$( '#no_invoice' ).attr('readonly','readonly');
	$( '#no_invoice' ).val(invoice_no);
}

function open_form_invoice()
{
	$.ajax({
		type: "POST", 
		url : "<?php echo site_url('finance/c_finance_process/open_form_invoice')?>",
		data: {
			job_no : arguments[0],
			inv_no : arguments[1], 
			oper : arguments[2]
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

function new_proses()
{

	$( "#form-invoice" ).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
	$( "#form-invoice" ).dialog(
	{
	//	position: 'top',
		autoOpen: true,
		resizable: false,
		width: 350,
		height: 'auto',
		modal: true,
		title :"PROSES DEBIT NOTE, INVOICE, CREDIT NOTE",
		close: function() 
		{	
			$(this).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
		}
	});
	
	var rowid = arguments[0];
	var kode_ref = $('#table_dua').jqGrid('getCell',rowid,'kode_ref');
	var agent = $('#table_dua').jqGrid('getCell',rowid,'agen_penagihan');
	
	$.ajax({
		type: "POST", 
		url : "<?php echo site_url('finance/c_finance_process/open_form_invoice')?>",
		data: 
		{
			job_no 		: kode_ref, 
			agent 		: agent, 
			oper			: 'add',
		},
		success:function(XMLHttpRequest)
		{
			$( "#form-invoice" ).html(XMLHttpRequest)
		},	
		error:function(XMLHttpRequest)
		{
			$('#form-invoice').html(XMLHttpRequest.responseText)
		}	
	})
	
}

function edit_proses(rowid,ppn)
{
	
	$( "#form-invoice" ).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
	$( "#form-invoice" ).dialog(
	{
		position:'center',
		autoOpen: true,
		resizable: false,
		width: 855,		
		height: 580,
		modal: true,
		title :"DETAIL OF FINANCE PROSES",
		close: function() 
		{
			$(this).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
		}
	});
	
	$.ajax({
		type: "POST", 
		url : "<?php echo site_url('finance/c_finance_process/open_form_invoice')?>",
		data: 
		{ 
			id 	: rowid,
			ppn 	: ppn,
			oper	: 'edit',
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


</script>


