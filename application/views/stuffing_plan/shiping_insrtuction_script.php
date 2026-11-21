<script type="text/javascript">
function table_tracking()
{
	$( "#Form-Trucking" ).dialog(
	{
		autoOpen: true,
		resizable: false,
		height:'auto',
		width:'auto',
		modal: true,
		title:"Tracking Table",
	});
	
	var grid = $("#table-trucking"), i;
	$("#table-trucking").jqGrid(
	{
		url:"<?php echo base_url('afg_c_tracking/get_tracking')?>",
		mtype : "post", 
		datatype: "json",
		colNames:
		[
			'HBL NO',
			'PORT of DESTINATION',
			'FEEDER NAME',
			'ETD',
			'ETA',
			'ACTUAL ETA',
			'SHIPPER',
			'CONSIGNEE',
			'DESTINATION',
			'PICKUP D/O',
			'CONTAINER NO',
			'REMARK',
			''
		],
		colModel:
		[
			{name:'hbl_no',index:'hbl_no', width:80, align:"left",
				editable: true,
				editoptions:{
					size:"20",
					maxlength:"50"
				},
			},
			{name:'port_of_destination',index:'port_of_destination', width:80, align:"left",
				editable: true,
				editoptions:{
					size:"20",
					maxlength:"50"
				},
			},
			{name:'feeder_name',index:'feeder_name', width:80, align:"left",
				editable: true,
				editoptions:{
					size:"20",
					maxlength:"50"
				},
			},
			{name:'etd',index:'etd', width:80, align:"center",
				editable: true,
				editoptions: {
      			size: 20,
      			maxlengh: 10,
      			dataInit: function(element) {
        			$(element).datepicker({dateFormat: 'dd/mm/yy'})
      			}
    			},
			},
			{name:'eta',index:'eta', width:80, align:"center",
				editable: true,
				editoptions: {
      			size: 20,
      			maxlengh: 10,
      			dataInit: function(element) {
        				$(element).datepicker({dateFormat: 'dd/mm/yy'})
      			}
    			},
			},
			{name:'actual_eta',index:'actual_eta', width:80, align:"center",
				editable: true,
				editoptions: {
      			size: 20,
      			maxlengh: 10,
      			dataInit: function(element) {
        			$(element).datepicker({dateFormat: 'dd/mm/yy'})
      			}
    			},
			},
			{name:'shipper',index:'shipper', width:80, align:"left",
				editable: true,
				editoptions:{
					size:"20",
					maxlength:"50"
				},
			},
			{name:'consignee',index:'consignee', width:80, align:"left",
				editable: true,
				editoptions:{
					size:"20",
					maxlength:"50"
				},
			},
			{name:'destination',index:'destination', width:80, align:"left",
				editable: true,
				editoptions:{
					size:"20",
					maxlength:"50"
				},
			},
			{name:'pickup_do',index:'pickup_do', width:80, align:"left",
				editable: true,
				editoptions:{
					size:"20",
					maxlength:"50"
				},
			},
			{name:'container_no',index:'container_no', width:80, align:"left",
				editable: true,
				editoptions:{
					size:"20",
					maxlength:"50"
				},
			},
			{name:'remarks',index:'remarks', width:80, align:"left",
				editable: true,
				editoptions:{
					size:"20",
					maxlength:"50"
				},
			},
			{width:1},
		],
		multiselect:true,
		loadonce : true,
		rowNum:100,
		width: 890,
		height: 380,
		shrinkToFit: false,
		rowList:[10,20,30,40,50,60,70,80,90,100],
		viewrecords: true,
		rownumbers: true,
		gridview: true,
		pager: '#page-trucking',
		caption:"",	
		editurl:'afg_c_tracking/manage_tracking',
						
			loadComplete: function() {
				$("#cb_"+grid[0].id).hide();
				$(".ui-jqgrid-titlebar-close",$("#table-trucking")[0].grid.cDiv).hide();
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
		}).navGrid('#page-trucking', { edit:true, add:true, del: true },
		
			{	
				height: "auto", 
				width: "auto", 	
				closeAfterEdit: true, 
				reloadAfterEdit : true, 
				modal: true, 
				editCaption: "Edit Data Trucking",
				recreateForm: false,
				viewPagerButtons: false,
				beforeShowForm: function(frm) {
					
				},
				afterSubmit: function () {
    					$('#table-trucking').jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
    					return [true];
				}     		
			},			
   		{
   			height: "auto", 
   			width: "auto",  
   			closeAfterAdd: true, 
   			reloadAfterAdd : true, 
   			modal: true, 
   			addCaption: "Add Data Trucking",
   			beforeShowForm: function(frm) {
					
				},
				afterSubmit: function () {
    					$('#table-trucking').jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
    					return [true];
				}		
   		},
     		{height: "auto", width: "auto"},
      	{modal: true, height: "auto", width: "auto",multipleSearch : false, closeAfterSearch : true, searchCaption: "Fine Data ",},
     		{}
		);
}

function shiping_instruction()
{
	$( "#Insert-Form" ).dialog(
	{
		autoOpen: true,
		resizable: false,
		height:'auto',
		width:'auto',
		modal: true,
		title:"SHIPING INSTRUCTION TO SHIPP.CO",
	});
	var div = document.createElement('div', 'class="testing"');
	var input = document.createElement('input', 'type="text"');
}
</script>