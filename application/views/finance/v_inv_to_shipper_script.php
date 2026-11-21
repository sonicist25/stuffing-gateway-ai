<style type="text/css">

.ui-jqgrid-btable .BlueRow td {
    background-color: #cce5ff !important;
}
.ui-jqgrid-btable .YellowRow td {
    background-color: #fff3cd !important;
}

</style>
<script type="text/javascript" >
var base_url = "<?php echo base_url(); ?>";
$(function () {
	$('#form-table-empat').attr('hidden',true);
	$('#invoice_date').datepicker({dateFormat: 'dd-mm-yy'});
	get_table_dua();
});


function get_address(rowid) {
	var val = $('select#KOSTUMER').val();
	$.get('c_inv_to_shipper/get_address',{shipper_id:val},'json')
	.done(function(d,s,e) {
		var data = $.parseJSON(d);		
		if (data.status==1) {
			//console.log(data.data[0].)
			$('#KET_KOSTUMER').val(data.data[0].shipper_name);
			$('#ALAMAT_KOSTUMER').val(data.data[0].shipper_address);
		} else {
			var grid = $("#table_dua"), i;
			$('#KET_KOSTUMER').val(grid.jqGrid('getCell',rowid,'nama_consignee'));
			$('#ALAMAT_KOSTUMER').val(grid.jqGrid('getCell',rowid,'alamat_consignee'));
		}
	})
	.error(function (d,s,e) {
		//alert(d)
	})

}

function get_table_dua() {
    var grid = $("#table_dua");

    grid.jqGrid({
        url : base_url+"/finance/c_inv_to_shipper/get_table_dua",
        mtype : "post",
        datatype: "json",
        postData: {id_akhir : $("input[name='id_akhir']").val()},
        sortname: "kode_ref",
        sortorder: "desc",
        colNames:['Job No', 'Job Date','Shipper / Consignee','Destination','Shiping Co','Feeder Vesel','Kurs',
                  'Sales','shipper code','shipper Name','Shipper Address','Consignee code','Consignee Name','Consignee Address','','','Shipper Code','','BL No.'],
        colModel:[
            {
				name:'trnstuffingplandetail.kode_ref',
				index:'trnstuffingplandetail.kode_ref',
				width:120, align:"center",
				stype:"text",
				searchoptions:{ sopt:['cn','eq'] },
				cellattr: function(rowId, cellValue, rawObject) {
					if(rawObject[14]=='0'){ return 'class="Blue"'; }
				}
			},
			{
				name:'tanggal_job',
				index:'tanggal_job',
				width:70, align:"center",
				stype:"text",
				searchoptions:{
					sopt:['eq','lt','gt'],
					dataInit: function(element) {
						$(element).datepicker({dateFormat: 'yymmdd'});
					}
				},
				cellattr: function(rowId, cellValue, rawObject) {
					if(rawObject[14]=='0'){ return 'class="Blue"'; }
				}
			},
			{
				name:'note',
				index:'note',
				width:185, align:"left",
				stype:"text",
				searchoptions:{ sopt:['cn','eq'] },
				cellattr: function(rowId, cellValue, rawObject) {
					if(rawObject[14]=='0'){ return 'class="Blue"'; }
				}
			},
			{
				name:'trnstuffingplandetail.destination',
				index:'trnstuffingplandetail.destination',
				width:100, align:"left",
				search:false, // tidak bisa di-search
				cellattr: function(rowId, cellValue, rawObject) {
					if(rawObject[14]=='0'){ return 'class="Blue"'; }
				}
			},
			{
				name:'carrier',
				index:'carrier',
				width:100, align:"left",
				stype:"text",
				searchoptions:{ sopt:['cn','eq'] },
				cellattr: function(rowId, cellValue, rawObject) {
					if(rawObject[14]=='0'){ return 'class="Blue"'; }
				}
			},
			{
				name:'vessel',
				index:'vessel',
				width:150, align:"left",
				stype:"text",
				searchoptions:{ sopt:['cn','eq'] },
				cellattr: function(rowId, cellValue, rawObject) {
					if(rawObject[14]=='0'){ return 'class="Blue"'; }
				}
			},
            {name:'kurs',index:'kurs', width:100, align:"left", hidden: true},
            {name:'sales',index:'sales', width:100, align:"left", hidden: true},
            {name:'id_shipper',index:'id_shipper', width:100, align:"left",hidden: true},
            {name:'nama_shipper',index:'nama_shipper', width:200, align:"left", hidden: true},
            {name:'alamat_shipper',index:'alamat_shipper', width:300, align:"left", hidden: true},
            {name:'code_consignee',index:'code_consignee', width:200, align:"left", hidden: true},
            {name:'nama_consignee',index:'nama_consignee', width:200, align:"left", hidden: true},
            {name:'alamat_consignee',index:'alamat_consignee', width:300, align:"left", hidden: true},
            {name:'trk_inv',index:'trk_inv', width:10, align:"left", hidden: true},
            {name:'region_id',index:'region_id', width:70, align:"left", hidden: true},
            {name:'shipper_code',index:'shipper_code', width:170, align:"left", hidden: true},
            {name:'incoterms',index:'incoterms', width:170, align:"left", hidden: true},
			 {
				name:'house_bl',
				index:'house_bl',
				width:150, align:"left",
				stype:"text",
				searchoptions:{ sopt:['cn','eq'] },
				cellattr: function(rowId, cellValue, rawObject) {
					if(rawObject[14]=='0'){ return 'class="Blue"'; }
				}
			},
        ],
        shrinkToFit: false,
        multiselect:true,
        loadonce : false,
        cellEdit: false,
        rowNum:10,
        width: 930,
        height: 'auto',
        rowList:[10,50,100,150],
        viewrecords: true,
        rownumbers: true,
        gridview: true,
        modal: true,
        caption:"Tabel Job",
        pager: '#pager-2',

        // Highlight row sesuai nilai trk_inv
		loadComplete: function() {
			$("#cb_"+grid[0].id).hide();
			$(".ui-jqgrid-titlebar-close",$("#table_dua")[0].grid.cDiv).hide();	

			var ids = grid.getDataIDs();
			for (var i = 0; i < ids.length; i++) {
				var rowData = grid.jqGrid('getRowData', ids[i]); 
				var trk = rowData.trk_inv; // hidden col tetap ada di object
				$("#" + ids[i])
						.removeClass("BlueRow YellowRow");

					if (trk === "0") {
						$("#" + ids[i]).addClass("BlueRow");
					} else if (trk === "2") {
						$("#" + ids[i]).addClass("YellowRow");
					}
			}

			var rowid = grid.getDataIDs();
			var id = rowid.shift();
			getInvoice(id);				
		},

        beforeSelectRow: function(rowid, e) {
            var cbsdis = $("tr#"+rowid+".jqgrow > td > input.cbox:disabled", grid[0]);
            return cbsdis.length === 0;
        },

        onSelectRow: function(rowid, status){
            var selected = $("tr.jqgrow > td > input.cbox:checked", grid[0]);		
            selected[rowid] = status;
            if(status) { 
                var cbs = $("tr.jqgrow > td > input.cbox", grid[0]);  			
                cbs.attr("disabled", true);  
                selected.attr("disabled", false);    	   					  		
                $('#btnNew').attr({'hidden':false,'disabled':false,'onClick':'new_invoice('+rowid+')'});	
                $('#btnEdit').attr('hidden',true);
                getInvoice(rowid); 
            } else {
                var cbs = $("tr.jqgrow > td > input.cbox:disabled", grid[0]);  		
                cbs.attr("disabled", false);  
                $('#btnEdit').attr({'hidden':false,'disabled':true});
                $('#btnNew').attr({'hidden':false,'disabled':true});
            }
        }
    });

    grid.jqGrid('navGrid', '#pager-2', {edit: false, add: false,  del: false, refresh: true}, {}, {}, {},
        {modal: true, height: "auto", width: 420, closeAfterSearch : true, caption: "Find Job .."}, {}
    );

    search_extend(grid);        
    var div = $(".ui-jqgrid-bdiv");
    var nx = div.length-1;
    var divx = div[nx];
    $(divx).css({'min-height':'100px'});
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
		$('#TANGGAL_JOB').val(grid.jqGrid('getCell',rowid,'tanggal_job'));
		$('#KURS').val(grid.jqGrid('getCell',rowid,'kurs'));
		$('#SALESMAN').val(grid.jqGrid('getCell',rowid,'sales'));
		$('#destinationForm').val(grid.jqGrid('getCell',rowid,'destination'));
		
		var shipper = grid.jqGrid('getCell',rowid,'id_shipper');
		var consignee = grid.jqGrid('getCell',rowid,'code_consignee');
		var costomer = grid.jqGrid('getCell',rowid,'shipper_code');
		
		if(kode_ref.substr(4,3) == '300'||kode_ref.substr(4,3) == '301'||kode_ref.substr(4,3) == '302'||kode_ref.substr(4,3) == '400'||kode_ref.substr(4,3) == '402'||kode_ref.substr(4,3) == '600'){
			$('select#KOSTUMER').html('<option value="' + consignee + '">' + consignee + '</option><option value="' + costomer + '">' + costomer + '</option>').attr('OnChange','get_address(' + rowid + ')');
			$('#KET_KOSTUMER').val(grid.jqGrid('getCell',rowid,'nama_consignee'));
			$('#ALAMAT_KOSTUMER').val(grid.jqGrid('getCell',rowid,'alamat_consignee'));
		} else {
			//$('select#KOSTUMER').val(shipper);
			$('select#KOSTUMER').html('<option value="' + shipper + '">' + shipper + '</option>');
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
		url : base_url+"/finance/c_inv_to_shipper/get_table_satu",
		mtype : "post",  
		postData: 
		{  
			job_no : arguments[0], 
			costomer : $("input[name='costumer_id']").val(),
		}, 
		datatype: "json",
		colNames:['','','Inv No', 'Date','Bill to','Group','Status','Payment','Created by','Term','Total'],
		colModel:
		[
			{name:'job_no',index:'job_no', width:5, align:"center",hidden: true},
			{name:'jenis',index:'jenis', width:5, align:"center",hidden: true},
			{name:'inv_no',index:'inv_no', width:75, align:"center"},
			{name:'date',index:'date', width:70, align:"center"},
			{name:'kepada',index:'kepada', width:200, align:"left"},
			{name:'group',index:'group', width:50, align:"center",
				formatter: function(el,em,ek){
					if(el.toUpperCase()=="NULL"){
					 return '';
					}
					return el.toUpperCase();
				}
			},
			{name:'kurs',index:'kurs', width:60, align:"center"},
			{name:'status',index:'status', width:100, align:"center",
					formatter: function(cellvalue, options, rowObject) {
						if (cellvalue == 'paid')
							return '<img src="'+base_url+'/public/images/green-dot-icon.png"/>' + cellvalue;
						else if (cellvalue == 'unpaid')
							return '<img src="'+base_url+'/public/images/blue-dot-icon.png"/>' + cellvalue;
						else if (cellvalue == 'overdue')
							return '<img src="'+base_url+'/public/images/red-dot-icon.png"/>' + cellvalue;
						else
							return "";
				}
			},
			{name:'created_by',index:'created_by', width:100, align:"center"},	
			{name:'incoterms',index:'incoterms', width:70, align:"center"},	
			{name:'grand_total', index:'grand_total', width:100, align:"center",
				formatter:'currency',
				formatoptions: {
					thousandsSeparator: ",",
					decimalSeparator: ".",
					decimalPlaces: 0,
					prefix: "Rp "
				}
			},	
		],
		sortname: "id",
		sortorder: "asc",
		shrinkToFit	: false,
		loadonce	:false,
  		viewrecords	: true,
		multiselect:true,
		rowNum:3,
		width: 888,
		height: 'auto',
		gridview: true,
		rowList:[-0],
		modal: true,
		caption:'List Invoice',
		pager: '#page-1',
		
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
	
	var div = $(".ui-jqgrid-bdiv");
 	var nx = div.length-2;
 	var divx = div[nx];
 	$(divx).css({'min-height':'67px'});
}

function open_form()
{
	var invoice_no = $('#invoice_no').val();
	$( '#no_invoice' ).attr('readonly','readonly');
	$( '#no_invoice' ).val(invoice_no);
}

function new_invoice()
{
	var spd = arguments[0];
	var job_no = $("#table_dua").jqGrid('getCell',spd,'trnstuffingplandetail.kode_ref');
	var incoterms = $("#table_dua").jqGrid('getCell',spd,'incoterms');
	$("#form-invoice" ).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
	$("#form-invoice" ).dialog(
	{
		autoOpen: true,
		resizable: false,
		height: 'auto',
		width: 1135,
		modal: true,
		position : ['center',30],
		title :"INVOICE",
		close: function() 
		{	
			$(this).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
		}
	});
	
	$.ajax
	({
		type:"POST",
		url : base_url+"/finance/c_inv_to_shipper/get_last_inv_no",
		data: 
		{
			job_no : job_no,
		},
		success:function(data)
		{
			var data = $.parseJSON(data);
			var new_no = data.new_no;
			open_form_invoice(job_no,new_no,'add',spd,incoterms);
		}
	})
}

function open_form_invoice()
{
	$.ajax({
		type: "POST", 
		url : base_url+"/finance/c_inv_to_shipper/open_form_invoice",
		data: 
		{
			job_no : arguments[0],
			inv_no : arguments[1], 
			oper 	 : arguments[2],
			spd	 : arguments[3],
			incoterms	 : arguments[4],
		},
		success:function(XMLHttpRequest)
		{
			$("#form-invoice").html(XMLHttpRequest)
		},	
		error:function(XMLHttpRequest)
		{
			$('#form-invoice').html(XMLHttpRequest.responseText)
		}	
	});
}

function edit_invoice(rowid) {
	
	$("#form-invoice").html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
	$("#form-invoice").dialog(
	{
		autoOpen: true,
		resizable: false,
		height: 'auto',
		width: 1135,
		modal: true,
		position : ['center',30],
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
	var status  = grid.jqGrid('getCell',rowid,'kurs');
	var incoterms = grid.jqGrid('getCell',rowid,'incoterms');
	
	$.ajax({
		type: "POST", 
		url : base_url+"/finance/c_inv_to_shipper/open_form_invoice",
		data: 
		{ 
			id 		: rowid,
			job_no 	: $("input[id='NOMER_JOB']").val(),
			group 	: group,
			date	: date,
			jenis 	: jenis,
			inv_no	: inv_no,
			status	: status,
			incoterms : incoterms,
			oper	: 'edit',
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
