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
		url:"<?php echo base_url('reference/stuffing_location/getDatastuffing_location')?>",
		mtype : "post",
		datatype: "json",
		postData: { searchField:'stuffing_location_id', searchOper:'eq', searchString: 'N' }, 
		colNames:
		[
			'Stuffing Location Code','Stuffing Location Company', 'Stuffing Location Address',
		],
    	colModel:
		[
			{name:'warehouse_code',index:'warehouse_code', align:"center",width:20, editable: true, 
				editoptions:{size:"20",maxlength:"20"},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'warehouse_name',index:'warehouse_name', align:"center",width:40, editable: true, 
				editoptions:{size:"40",maxlength:"50"},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},				
			{name:'warehouse_address',index:'warehouse_address', align:"left", width:100, editable: true, 
				edittype:"textarea",
				editoptions:{cols:"100", rows:"2"}
			},
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
		caption:"Stuffing Location Data Table",
		editurl:ref+'/stuffing_location/manage_data',
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
			{height: "auto", width: "auto", top: 3, left: 250, closeAfterEdit: true, reloadAfterEdit : true, modal: true, editCaption: "Edit Data Stuffing Location",
				afterSubmit: function () {
    				$('#table').jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
    				return [true];
				}
			}, // edit options
   		{height: "auto", width: "auto", top: 3, left: 250, closeAfterAdd: true, reloadAfterAdd : true, modal: true, addCaption: "Insert Data Stuffing Location",
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