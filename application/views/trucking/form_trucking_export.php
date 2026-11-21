<div id="main-page">
	<center>
		<table id="table" ></table>
	</center>
	<div id="pager" style="font-size: 11px"></div>
</div>

<script type="text/javascript">
$(document).ready(function () 
{
	var grid = $("#table"), i;	
	$("#table").jqGrid(
	{ 
		url:"<?php echo base_url('afg_c_trucking_export/get_tracking_export')?>",
		mtype : "post",
		datatype: "json", 
		colNames:
		[
			'',
			'',
			'',
			'',
			'HBL NO',
			'PORT of DESTINATION', 
			'FEEDER NAME', 
			'CONNECTING NAME',
			'ETD', 
			'ATD', 
			'ETA', 
			'ATA',
			'SHIPPER',
			'CONSIGNEE', 
			'DESTINATION', 
			'PICKUP D/O', 
			'PICKUP CARGO',
			'CONTAINER NO',
			'REMARKS',
			'',
		],
    	colModel:
		[
			{name:'id_shipper',index:'id_shipper', align:"left", width:10, editable: true, 
				hidden: true,
			},
			{name:'id_request',index:'id_request', align:"left", width:10, editable: true, 
				hidden: true,
			},
			{name:'id_agent',index:'id_agent', align:"left", width:10, editable: true, 
				hidden: true,
			},
			{name:'mbl_no',index:'mbl_no', align:"left", width:10, editable: true, 
				hidden: true,
			},
			{name:'hbl_no',index:'hbl_no', align:"center", width:90, editable: true, 
				editoptions:
				{
					size:"30",
					disabled:true,
				},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'port_of_destination',index:'port_of_destination', align:"left", width:150, editable: true, 
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'feeder_name',index:'feeder_name', align:"left", width:170, editable: true, 
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'connecting_name',index:'connecting_name', align:"left", width:170, editable: true, 
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'etd',index:'etd', align:"center", width:90, editable: true, 
				editoptions: {
      			size: 30, maxlengh: 10,
      			dataInit: function(element) {
        				$(element).datepicker({dateFormat: 'dd/mm/yy'})
      			}
    			},
    			stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'atd',index:'atd', align:"center", width:90, editable: true, 
				editoptions: {
      			size: 30, maxlengh: 10,
      			dataInit: function(element) {
        				$(element).datepicker({dateFormat: 'dd/mm/yy'})
      			}
    			},
    			stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'eta',index:'eta', align:"center", width:90, editable: true, 
				editoptions: {
      			size: 30, maxlengh: 10,
      			dataInit: function(element) {
        				$(element).datepicker({dateFormat: 'dd/mm/yy'})
      			}
    			},
    			stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'actual_eta',index:'actual_eta', align:"center", width:90, editable: true, 
				editoptions: {
      			size: 30, maxlengh: 10,
      			dataInit: function(element) {
        				$(element).datepicker({dateFormat: 'dd/mm/yy'})
      			}
    			},
    			stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'shipper',index:'shipper', align:"left", width:200, editable: true, 
				edittype:"textarea",
				editoptions:
				{
					disabled:true,
					cols:"30", 
					rows:"2"
				},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'consignee',index:'consignee', align:"left", width:200, editable: true, 
				edittype:"textarea",
				editoptions:
				{
					disabled:true,
					cols:"30", 
					rows:"2"
				},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'destination',index:'destination', align:"left", width:100, editable: true, 
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'pickup_do',index:'pickup_do', align:"left", width:100, editable: true, 
				editoptions:
				{
				size: 30, maxlengh: 10,
      			dataInit: function(element) {
        				$(element).datepicker({dateFormat: 'dd/mm/yy'})
        			}
				},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'pickup_cargo',index:'pickup_cargo', align:"left", width:100, editable: true, 
				editoptions:
				{
				size: 30, maxlengh: 10,
      			dataInit: function(element) {
        				$(element).datepicker({dateFormat: 'dd/mm/yy'})
        			}
				},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'container_no',index:'container_no', align:"left", width:100, editable: true, 
				editoptions:
				{
					size:"30",
				},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'remarks',index:'remarks', align:"left", width:200, editable: true, 
				edittype:"textarea",
				editoptions:
				{
					cols:"30", 
					rows:"2",
				},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'sp_detail_id',index:'sp_detail_id', align:"left", width:20, editable: true, hidden: true}
		], 
		sortname: "id_tracking",
		sortorder: "desc",
		shrinkToFit: false,
		loadonce:true,
  		viewrecords: true,
        multiselect:true,
		modal: true,
		rowNum: 500,
		top: 20,
		height : ($(window).height() - 150),
        width : ($(window).width() - 400),
        scroll : true, 
		rowList:[500,1000,1500,2000,2500],
		gridview: true,
		autoencode: true,
	//	caption:" ",
		editurl:'afg_c_trucking_export/manage_tracking_export',
	//	cellsubmit:'clientArray',
		pager:'#pager',
				
		loadComplete: function(data) {
		//	$("#cb_"+grid[0].id).hide();
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
		
	}).navGrid('#pager',{ edit: true, add: false, del: true,},
			{height: "auto", width: "auto", top: 3, left: 250, closeAfterEdit: true, reloadAfterEdit : true, modal: true, editCaption: "Edit Data Tracking Table",
				viewPagerButtons: false,
				afterSubmit: function () {
    				$('#table').jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
    				return [true];
				},
				beforeShowForm: function(frm) {	
					$( '#hbl_no' ).attr('disabled',true).css('color','#49f');
					/*
					$( '#port_of_destination' ).attr('disabled',true).css('color','#49f');
					$( '#etd' ).attr('disabled',true).css('color','#49f');
					$( '#eta' ).attr('disabled',true).css('color','#49f');
					$( '#feeder_name' ).attr('disabled',true).css('color','#49f');
					$( '#shipper' ).attr('disabled',true).css('color','#49f');
					$( '#consignee' ).attr('disabled',true).css('color','#49f');
					$( '#container_no' ).attr('disabled',true).css('color','#49f');
					$( '#destination' ).attr('disabled',true).css('color','#49f');
					*/
				}
			}, 
   		{ },
     		{height: "auto", width: "auto"},
      	{modal: true, height: "auto", width: "auto", closeAfterSearch : true, multipleSearch: false,},
     		{ }
    );
});
</script>