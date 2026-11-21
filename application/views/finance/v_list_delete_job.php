
<div style="margin-top: 30px; margin-left: 30px">
	<table id="table_delete"></table>
	<div id="page-delete" style="font-size: 11px"></div>
</div>

<script type="text/javascript" >
$(document).ready(function () {
	get_table_delete();
});


function get_table_delete()
{
	var grid = $("#table_delete"), i;
	grid.jqGrid( {
      url:"<?php echo base_url('Afg_c_delete_job/get_table_delete')?>",
  		mtype : "post",
  		datatype: "json",
  		sortname: "number",
		sortorder: "asc",
   	colNames:['No Job','Deleted by','Alasan delete'], 
  		colModel:
   	[
			{name:'kode_ref',index:'kode_ref', width:130, align:"left",
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'deleted_by',index:'deleted_by', width:170, align:"left",
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'pesan',index:'pesan', width:550, align:"left",
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
		
		],
		shrinkToFit: false,
		multiselect:true,
		loadonce : false,
		cellEdit: false,
		rowNum:20,
		width: 900,
		height: 505,
		rowList:[20,40,60,80,100],
		viewrecords: true,
		rownumbers: true,
		gridview: true,
		modal: true,
      caption:"Tabel List Deleted Job",
      editurl	:	window.location.origin+'Afg_c_delete_job/get_table_delete',
		pager: '#page-delete',

		loadComplete: function() {
			$("#cb_"+grid[0].id).hide();
			$(".ui-jqgrid-titlebar-close",$("#table_delete")[0].grid.cDiv).hide();					
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
    			$('#btnNew').attr({'hidden':false,'disabled':false,'onClick':'new_invoice('+rowid+')'});	
    			$('#btnEdit').attr('hidden',true);
     		}
        	else
        	{
        		var cbs = $("tr.jqgrow > td > input.cbox:disabled", grid[0]);  		
    			cbs.attr("disabled", false);  
    			$('#btnEdit').attr({'hidden':false,'disabled':true})
				$('#btnNew').attr({'hidden':false,'disabled':true})
        	}
		}
		
   })
   grid.jqGrid('navGrid', '#page-delete', {edit: false,
										add: false,  
										del: false,									
										refresh: true,
										"deltext": "delete",}, 
										{ }, { }, { },
   	{ 
   		modal: true, 
   		height: "auto", 
   		width: 380, 
   		closeAfterSearch : true, 
   		caption: "Find Job Number",
   	}, 
   	{ }
   );

}

</script>
