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
		url:"<?php echo base_url('reference/country/getdatacountry')?>",
		mtype : "post",
		datatype: "json", 
		colNames:
		[
			'Country ID','Country Name',
		],
    	colModel:
		[
			{name:'country_id',index:'country_code', align:"center",width:10, editable: true, editoptions:{size:"40",maxlength:"50"}},				
			{name:'country_name',index:'country_name', align:"left", width:20, editable: true, editoptions:{size:"40",maxlength:"50"}},
		], 
  		multiselect:true,
		loadonce : true,
		cellEdit: false,
		rowNum:16,
		width: 630,
		height: 370,
		rowList:[100,200,300,400],
		viewrecords: true,
		rownumbers: true,
		gridview: true,
		modal: true,
		pager:'#pager',
		editurl:ref+'/country/manage_data',
		caption:"Manage Data Country",		
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
			{height: "auto", width: "auto", top: 3, left: 250, closeAfterEdit: true, reloadAfterEdit : true, modal: true, editCaption: "Edit Data Country", 
				afterSubmit: function () {
    				$('#table').jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
    				return [true];
				},
				viewPagerButtons: false,
			},
   		{height: "auto", width: "auto", top: 3, left: 250, closeAfterAdd: true, reloadAfterAdd : true, modal: true, addCaption: "Insert Data Country", 
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