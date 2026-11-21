<?php // echo ! empty($h1_title) ? '<h1>' . $h1_title . '</h1>': ''; ?>

<div id="main-page" style="padding-top: 70px;">
	<center>
		<table id="table" ></table>
	</center>
	<div id="pager"></div>
</div>

<script type="text/javascript">
$(function () {
	var grid = $("#table"), i;	
	$("#table").jqGrid({ 
		url: "<?php echo base_url('reference/consignee_import/getDatarefconsigneeimport')?>",
		mtype : "post",
		datatype: "json",
		sortname: "consignee_import_id",
		sortorder: "desc",
		ajaxGridOptions: {cache: false},
		postData: { 
			searchField:'consignee_import_id', searchOper:'eq', searchString: 'N'
		}, 
		colNames:
		[
			'Code','Name', 'Address','Fowarder',
		],
    	colModel:
		[
			{name:'consignee_import_code',index:'consignee_import_code', align:"left",width:50, editable: true, 
         	editoptions:{size:"40",maxlength:"50"},
        		searchoptions: {
					searchhidden: true,
					sopt:['cn','eq'],
          	},
       	},
			{name:'consignee_import_name',index:'consignee_import_name', align:"left", width:120, 
         	searchoptions: {
					searchhidden: true,
					sopt:['cn','eq'],
         	},
         	editable: true, editoptions:{size:"40",maxlength:"50"}
        	},
			{name:'consignee_import_address',index:'consignee_import_address', width:180, 
         	searchoptions: {
					searchhidden: true,
					sopt:['cn','eq'],
          	},
        		editable: true, 
        		edittype:"textarea",
        		editoptions:{
        			dataInit: function(element) {
        				$(element).css({ "text-transform": "uppercase","min-height":"60px","min-width":"340px","max-width":"340px"})
      			}
        		}
        },
        {name:'fowarder',index:'fowarder', width:70, 
         	searchoptions: {
					searchhidden: true,
					sopt:['cn','eq'],
          	},
        		editable: true, 
        		edittype:"select",
				editoptions:{
					dataUrl:window.location.origin+'/afg_c_dropdown/dropdown_shipper_print',
					dataInit: function(element) {
        				$(element).css({ "text-transform": "uppercase","max-width":"350px"})
      			}
				},
        },
		], 
  		multiselect:true,
		loadonce : true,
		cellEdit: false,
		rowNum:16,
		width: 930,
		height: 'auto',
		rowList:[100,200,300,400,500,600,700,800,900,1000,1500],
		viewrecords: true,
		rownumbers: true,
		gridview: true,
		modal: true,
		caption:"Consignee Import Data Table",
		editurl:ref+'/consignee_import/manage_data',
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
    			//cbs.attr("disabled", true);  
    			//selected.attr("disabled", false);  
    			on_edit(true);    			
     		}
        	else
        	{
        		var cbs = $("tr.jqgrow > td > input.cbox:disabled", grid[0]);  		
    			//cbs.attr("disabled", false); 
    			on_edit(true);      			
        	}
		},
		
	})
	grid.navGrid('#pager',{ edit: true, add: true, del: true,},
			{height: "auto", width: "auto", viewPagerButtons: false, closeAfterEdit: true, reloadAfterEdit : true, modal: true, editCaption: "Edit Data Consignee Import", 
				afterSubmit: function () {
    				$('#table').jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
    				return [true];
				}
			},
   		{height: "auto", width: "auto", closeAfterAdd: true, reloadAfterAdd : true, modal: true, addCaption: "Insert Data Consignee Import", 
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
on_edit=function () {
	
}
</script>