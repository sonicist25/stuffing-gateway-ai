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
		url : ref+"/type_container/getDatareftypecontainer",
		mtype : "post",
		datatype : "json",
		sortname : "typecontainer_id",
		sortorder : "desc",
		colNames :
		[
			'Type Container Name',
		],
    	colModel:
		[				
			{name:'typecontainer_name',index:'typecontainer_name', align:"left", width:20, 
				editable: true, 
				editoptions: { size: 40, dataInit: function (el) { $(el).css('text-transform', 'uppercase'); }},
				editrules:{ custom:true, custom_func: not_empty },	
				searchoptions:{ 
					sopt:['cn','eq'],
				}		
			},
		
		],
		shirnkToFit:false,
  		multiselect:true,
		loadonce : false,
		cellEdit: false,
		rowNum:16,
		width: 560,
		height: 370,
		rowList:[10,20,30],
		viewrecords: true,
		rownumbers: true,
		gridview: true,
		modal: true,
		caption: "Type Container Data Table",
		editurl: ref+"/type_container/manage_data",
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
			{height: "auto", width: "auto", top: 3, left: 250, closeAfterEdit: true, reloadAfterEdit : true, modal: true, editCaption: "Edit Data type_container",
				viewPagerButtons: false,
				afterSubmit: function () {
    				$('#table').jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
    				return [true];
				}
			}, // edit options
   		{height: "auto", width: "auto", top: 3, left: 250, closeAfterAdd: true, reloadAfterAdd : true, modal: true, addCaption: "Insert Data type_container",
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