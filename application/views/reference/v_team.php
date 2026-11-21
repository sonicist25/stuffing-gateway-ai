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
		url:"<?php echo base_url('reference/team/getDatarefteam')?>",
		mtype : "post",
		datatype: "json",
		postData: { searchField:'team_id', searchOper:'eq', searchString: 'N' }, 
		colNames:
		[
			'Name', 'Anggota','Aktiv'
		],
    	colModel:
		[
			{name:'nama_team',index:'nama_team', width:40, 
				editable: true, 
				editoptions:{size:"40"}
			},				
			{name:'anggota',index:'anggota', width:40, 
				editable: true, 
				editoptions:{size:"40"}
			},
			{name:'aktiv',index:'aktiv', width:20, align: 'center', 
				editable: true, 
				edittype:"select",
				formatter:'select', 
				editoptions:
				{
					value:"Y:YA;T:TIDAK"
				}
			},
		],
		sortname: 'id',
   	sortorder:'desc', 
  		multiselect:true,
		loadonce : false,
		cellEdit: false,
		rowNum:16,
		width: 630,
		height: 370,
		rowList:[100,200,300,400,500],
		viewrecords: true,
		rownumbers: true,
		gridview: true,
		modal: true,
		caption:"Team Data Table",
		editurl:'manage_data',
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
			{height: "auto", width: "auto", top: 3, left: 250, closeAfterEdit: true, reloadAfterEdit : true, modal: true, editCaption: "Edit Data team", 
				viewPagerButtons: false,
				afterSubmit: function () {
    				$('#table').jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
    				return [true];
				}
			},
   		{height: "auto", width: "auto", top: 3, left: 250, closeAfterAdd: true, reloadAfterAdd : true, modal: true, addCaption: "Insert Data team", 
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