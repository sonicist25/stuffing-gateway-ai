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
		url:"<?php echo base_url('reference/vendor/getDataVendor')?>",
		mtype : "post",
		datatype: "json",
		colNames:
		[
			'CODE','NAME', 'ADDRESS','POSITION',
		],
    	colModel:
		[
			{name:'vendor_code',index:'vendor_code', align:"left", 
				width:20, 
				editable: true, 
				editoptions:{
					size:"40"
				},
				<?php echo 'search: true' ?>,
				stype:"text",
				searchoptions: {
					searchhidden: true,
					sopt:['cn','eq'],
          	}
			},				
			{name:'vendor_name',index:'vendor_name', align:"left",
			 	width:20, 
				editable: true, 
				editoptions:{
					size:"40"
				},
				<?php echo 'search: true' ?>,
				stype:"text",
				searchoptions: {
					searchhidden: true,
					sopt:['cn','eq'],
          	}
			},
			{name:'vendor_address',index:'vendor_address', 
				width:50, 
				editable: true, 
				edittype:"textarea",
				editoptions:{
					cols:"40", rows:"5",
				},
				<?php echo 'search: true' ?>,
				stype:"text",
				searchoptions: {
					searchhidden: true,
					sopt:['cn','eq'],
          	}
			},
			{name:'type',index:'type', 
				width:20, 
				editable: true, 
				edittype:"text",
				editoptions:{
					size:"40"
				},
				<?php echo 'search: true' ?>,
				stype:"text",
				searchoptions: {
					searchhidden: true,
					sopt:['cn','eq'],
          	}
			},
		], 
		sortname: "vendor_id",
		sortorder: "desc",
  		loadonce:false,
  		viewrecords: true,
		multiselect:true, 
		modal: true,
		rownumbers: true,
		rowNum: 16,
		//top: 20,
		width: 830,
		height: 370,
		rowList:[15,30,45],
		gridview: true,
		caption:"Vendor Data Table",
		editurl:ref+'/vendor/manage_data',
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
		
	}).navGrid('#pager',{ edit: true, add: true, del: true, refresh: false},
			{height: "auto", width: "auto", 
				closeAfterEdit: true, 
				reloadAfterEdit : true, 
				modal: true, 
				editCaption: "Edit Data",
				viewPagerButtons: false,
				afterSubmit: function(response,postdata) {
					var data = $.parseJSON(response.responseText);
					var curPage = grid.getGridParam('page');
					grid.jqGrid('setGridParam',{ datatype: 'json' }).trigger('reloadGrid',[{page:curPage}]);
					return [true];
				}
			}, 
   		{height: "auto", width: "auto", 
   			closeAfterAdd: true, 
   			reloadAfterAdd : true, 
   			modal: true, 
   			addCaption: "Insert Data",
			afterComplete: function(response, postdata){
				if (response.responseText =='-1'){
				swal('Duplicate record');
				}
			 },
   			afterSubmit: function () {
    				$('#table').jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
    				return [true];
				}
   		},
     		{height: "auto", width: "auto"},
      	{modal: true, height: "auto", width: "auto", closeAfterSearch : true, multipleSearch: false,},
     		{ }
    );
});
</script>