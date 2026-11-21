<?php // echo ! empty($h1_title) ? '<h1>' . $h1_title . '</h1>': ''; ?>

<div id="main-page" style="padding-top: 70px;">
	<center>
		<table id="table" ></table>
	</center>
	<div id="pager"></div>
</div>

<script type="text/javascript">
$(document).ready(function () {
	var grid = $("#table"), i;	
	$("#table").jqGrid(
	{ 
		url:"<?php echo base_url('reference/c_kurs_pajak/show_table')?>",
		mtype : "post",
		datatype: "json",
		colNames:
		[
			'Mulai Tanggal',
			'Sampai Dengan',
			'Kurs',
			'No KMK',
			'Tanggal KMK',
		],
    	colModel:
		[
			{name:'mulai_tgl',index:'mulai_tgl', align:"center", width:100, editable: true, 
				editoptions: {
      			size: 30, maxlengh: 10,
      			dataInit: function(element) {
        				$(element).datepicker({dateFormat: 'dd/mm/yy'})
      			}
    			},
    			formatter: function(el,em,ek){
    				var empty = '//';
    				if(el==empty){
    					return '';	
    				} else {
    					return el;	
    				}
    			}
			},				
			{name:'smp_dgn',index:'smp_dgn', align:"center", width:100, editable: true, 
				editoptions: {
      			size: 30, maxlengh: 10,
      			dataInit: function(element) {
        				$(element).datepicker({dateFormat: 'dd/mm/yy'})
      			}
    			},
    			formatter: function(el,em,ek){
    				var empty = '//';
    				if(el==empty){
    					return '';	
    				} else {
    					return el;	
    				}
    			}
			},
			{name:'kurs',index:'kurs', align:"right", width:100, editable: true,
				editoptions: { 
					dataInit: function (el) 
					{ 
						$(el).attr('title','Hanya boleh memasukan angka'); 
					}
				},
				formatter: 'number',
    			formatoptions: { decimalSeparator: ".", thousandsSeparator: " ", decimalPlaces: 2, defaultValue: '' }
			}, 
			{name:'no_kmk',index:'no_kmk', align:"right", width:100, editable: true,
			
			},
			{name:'tgl_kmk',index:'tgl_kmk', align:"center", width:100, editable: true,
				editoptions: {
      			size: 30, maxlengh: 10,
      			dataInit: function(element) {
        				$(element).datepicker({dateFormat: 'dd/mm/yy'})
      			}
    			},
    			formatter: function(el,em,ek){
    				var empty = '//';
    				if(el==empty){
    					return '';	
    				} else {
    					return el;	
    				}
    			}
			},
		], 
		sortname:'id',
		sortorder:'desc',
		shrinkToFit: false,
  		multiselect:true,
		loadonce : true,
		cellEdit: true,
		rowNum:16,
		width: 600,
		height: 370,
		rowList:[50,100,150,200],
		viewrecords: true,
		rownumbers: true,
		gridview: true,
		modal: true,
		caption:"Table Data Kurs Pajak",
		editurl:ref+'/c_kurs_pajak/manage_data',
		cellsubmit:'clientArray',
		pager:'#pager',
				
		loadComplete: function() {
			$("#cb_"+grid[0].id).hide();
			$(".ui-jqgrid-titlebar-close",$("#table")[0].grid.cDiv).hide();
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
		beforeSaveCell: function(rowid, cellname, value, iRow, iCol) {
			save_tbl_kurs(rowid,cellname,value)
    	},
	})
	grid.navGrid('#pager',{ edit: false, add: false, del: true, refresh: true,},
			{ },
   		{ },
     		{height: "auto", width: "auto",
     			afterSubmit: function () {
    				$('#table').jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
    				return [true];
				}
     		},
      	{modal: true, height: "auto", width: "auto", closeAfterSearch : true, multipleSearch: false},
     		{ }
    )
	grid.navButtonAdd('#pager',{ 
			caption:"", 
			buttonicon:"ui-icon-plus", 
			onClickButton: insert_data, 
			position: "last", 
			title:"Insert New Record", 
			cursor: "pointer"
	})
});

function insert_data(){
	$.ajax
	({
		type:"POST",
		url:"manage_data",
		data:
		{
			oper : 'add',
			kolom : 'id',
			isi	: '',
		},
		success:function(response)
		{
			var data = $.parseJSON(response);
			if(data.mode=='add' || data.status=='1'){
				$("#table").jqGrid("setGridParam", {datatype: 'json',}).trigger("reloadGrid");
    			return [true];
			}
		}
	})
}

function save_tbl_kurs(rowid,cellname,value){
	$.ajax
	({
		type:"POST",
		url:"manage_data",
		data:
		{
			spd : $('#spd_id_cost').val(),
			kode_ref : $( '#kode_ref_cost' ).val(),
			id : rowid,
			kolom : cellname,
			isi : value,
			oper : 'edit',
		},
		success:function(response)
		{
			var data = $.parseJSON(response);
			if(data.status=='1'){
    			return [true];
			}
		}
	})
}
</script>