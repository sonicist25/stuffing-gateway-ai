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
	$("#table").jqGrid({ 
		url:"<?php echo base_url('reference/shipping_company/getDatarefshippingcompany')?>",
		mtype : "post",
		datatype: "json", 
		colNames:
		[
			'CODE','NAME','TYPE','CP','ADDRESS', 'REMARKS','NPWP'
		],
    	colModel:
		[	
			{name:'shipping_company_code',index:'shipping_company_code', align:"left", width : 40,
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
			{name:'shipping_company_name',index:'shipping_company_name', align:"left", width : 80,
				editable: true, 
				editoptions:{
					size:"50"
				},
				<?php echo 'search: true' ?>,
				stype:"text",
				searchoptions: {
					searchhidden: true,
					sopt:['cn','eq'],
          	}
			},
			{ 
            name: 'type_carrier',
            text: 'Type Carrier',
            width: 50,
            editable: true,
            edittype: 'select',
            editoptions: {
                value: {
                    "SHIPPING LINE": "SHIPPING LINE",
                    "NVOCC": "NVOCC",
                    "COLOADER": "COLOADER"
                },
                defaultValue: "SHIPPING LINE"
            },
            searchoptions: {
                value: ":All;SHIPPING LINE:SHIPPING LINE;NVOCC:NVOCC;COLOADER:COLOADER"
            },
            formatter: 'select'
			},
			{name:'shipping_company_cp',index:'shipping_company_cp', width : 30,
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
			{name:'shipping_company_address',index:'shipping_company_address', align:"left", width : 50,
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
			{name:'shipping_company_remark',index:'shipping_company_remark', width : 50,
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
			{name:'shipping_company_npwp',index:'shipping_company_npwp', align:"left", width:30, 
				editable: true,
				edittype:"text",
				editoptions:{
					size:"40",
					dataInit: function (el) { 
						$(el).css({'text-transform':'uppercase'}); 
					},
				},
				<?php echo 'search: true' ?>,
				stype:"text",
				searchoptions: {
					searchhidden: true,
					sopt:['cn','eq'],
          	}
			},
		],
  		sortname: "shipping_company_id",
		sortorder: "desc",
		loadonce:false,
  		viewrecords: true,
      multiselect:true,
		rowNum:15,
		width: 930,
		height: 470,
		rowList:[15,30,45],
		viewrecords: true,
		rownumbers: true,
		gridview: true,
		modal: true,
		caption:"Shipping Company Data Table",
		editurl:ref+'/shipping_company/manage_data',
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
				editCaption: "Edit Data Shipping Company",
				viewPagerButtons: false,
				afterSubmit: function(response,postdata) {
					var data = $.parseJSON(response.responseText);
					var curPage = grid.getGridParam('page');
					grid.jqGrid('setGridParam',{ datatype: 'json' }).trigger('reloadGrid',[{page:curPage}]);
					return [true];
				}
			}, // edit options
   		{height: "auto", width: "auto", 
   			closeAfterAdd: true, 
   			reloadAfterAdd : true, 
   			modal: true, 
   			addCaption: "Insert Data Shipping Company",
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