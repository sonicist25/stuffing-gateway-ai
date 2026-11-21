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
		url: ref+"/type_service/getDatareftypeservice",
		mtype : "post",
		datatype: "json", 
		colNames:
		[
			'Code','Name', 'KENA PPN',
		],
    	colModel:
		[
			{name:'typeservice_code',index:'typeservice_code', align:"center",width:20, editable: true, 
				editoptions: { size: 40, dataInit: function (el) { $(el).css('text-transform', 'uppercase'); }},
				editrules:{ custom:true, custom_func: not_empty },
			},				
			{name:'typeservice_name',index:'typeservice_name', align:"left", width:60, editable: true, 
				editoptions: { size: 40, dataInit: function (el) { $(el).css('text-transform', 'uppercase'); }},
				editrules:{ custom:true, custom_func: not_empty },
			},
			{name:'typeservice_ppn',index:'typeservice_ppn', align:"center", width:20, editable: true, 
				edittype:"select", formatter:'select', 
				editoptions:{ value:" : ;T:TIDAK;Y:YA", },
				editrules:{ custom:true, custom_func: not_empty },
			},
		],
		sortname: 'typeservice_id',
   	sortorder:'desc',
		shirnkToFit:false,
  		multiselect:true,
		loadonce : false,
		cellEdit: false,
		rowNum:16,
		width: 630,
		height: 360,
		rowList:[100,200,300,400,500],
		viewrecords: true,
		rownumbers: true,
		gridview: true,
		modal: true,
		caption:"Type Service Data Table",
		editurl:ref+"/type_service/manage_data",
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
		
	}).navGrid('#pager',{ edit: true, add: true, del: true,},
			{height: "auto", width: "auto", top: 3, left: 250, 
				viewPagerButtons: false,
				closeAfterEdit: true, 
				reloadAfterEdit : true, 
				modal: true, 
				editCaption: "Edit Data Type Service",
				afterSubmit: function () {
    				$('#table').jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
    				return [true];
				}
			},
   		{height: "auto", width: "auto", top: 3, left: 250, closeAfterAdd: true, reloadAfterAdd : true, modal: true, addCaption: "Insert Data Type Service",
   			afterSubmit: function () {
    				$('#table').jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
    				return [true];
				}
   		},
     		{height: "auto", width: "auto"},
      	{modal: true, height: "auto", width: "auto", closeAfterSearch : true, multipleSearch: false},
     		{ }
    );
});

function not_empty(value, colname) {
	var isi = value;
   if (value == ""){
		return [false, colname+" Can't be empty Value"];
   	$( '#no_pack_qty' ).focus();
	} else if (value != "") {
		return [true,""];
   }
}
</script>