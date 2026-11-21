<?php // echo ! empty($h1_title) ? '<h1>' . $h1_title . '</h1>': ''; ?>

<div id="main-page" style="padding-top: 70px;">
	<center>
		<table id="table" ></table>
	</center>
	<div id="pager"></div>
</div>

<script type="text/javascript">
$(document).ready(function () 
{
	var grid = $("#table"), i;	
	$("#table").jqGrid(
	{ 
		url:"<?php echo base_url('reference/shipper_import/getDatarefshipperimport')?>",
		mtype : "post",
		datatype: "json",
		postData: { searchField:'shipper_import_id', searchOper:'eq', searchString: 'N' }, 
		colNames:
		[
			'Code','Shipper Import Name', 'Shipper Import Address',
		],
    	colModel:
		[
			{name:'shipper_import_code',index:'shipper_import_code', align:"left",width:20, editable: true, editoptions:{size:"40",maxlength:"50"}},				
			{name:'shipper_import_name',index:'shipper_import_name', align:"left", width:40, editable: true, editoptions:{size:"40",maxlength:"50"}},
			{name:'shipper_import_address',index:'shipper_import_address', width:100, editable: true, edittype:"textarea",editoptions:{cols:"40", rows:"5"}},
		],
		multiselect:true,
		loadonce : true,
		cellEdit: false,
		rowNum:16,
		width: 630,
		height: 370,
		rowList:[10,20,30],
		viewrecords: true,
		rownumbers: true,
		gridview: true,
		modal: true,
		caption:"Shipper Import Data Table",
		editurl:ref+'shipper_import/manage_data',
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
			{height: "auto", width: "auto", top: 3, left: 250, closeAfterEdit: true, reloadAfterEdit : true, modal: true, editCaption: "Edit Data shipper_import",
				afterSubmit: function () {
    				$('#table').jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
    				return [true];
				}
			},
   		{height: "auto", width: "auto", top: 3, left: 250, closeAfterAdd: true, reloadAfterAdd : true, modal: true, addCaption: "Insert Data shipper_import",
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
</script>