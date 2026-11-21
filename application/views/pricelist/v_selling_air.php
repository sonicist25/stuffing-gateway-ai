<div id="form-main-page" class="main-page">
	<center>
		<table id="table" ></table>
		<div id="pager"></div>
	</center>
</div>
<div id="Form-Destination"></div>
<div id="PRINT"></div>

<script type="text/javascript">
$(document).ready(function () 
{
	var grid = $("#table"), i;	
	grid.jqGrid(
	{ 
		url:"<?php echo base_url('pricelist/air_selling_rate/get_selling_air')?>",
		mtype : "post",
		datatype: "json", 	
		colNames:
		[
			'Customer Code','Customer Name','',
		],
    	colModel:
		[
			{name:'shipper_id',index:'shipper_id', align:"left", width:210, editable: true,
				editoptions: { size: 40, dataInit: function (el) { $(el).css('text-transform', 'uppercase'); }},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'shipper_name',index:'shipper_name', align:"left", width:390, editable: true,
				editoptions: { size: 40, dataInit: function (el) { $(el).css('text-transform', 'uppercase'); }},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'user_id',index:'user_id', align:"center", width:120, editable: true, hidden:true,
				formatter: function(el,em,ek){
					if(el=='Administrator'){
						return "";
					} else {
						return el;
					}
				}
			},
		],
		sortname: "shipper_name",
		sortorder: "asc",
		shrinkToFit:false, 
  		multiselect:true,
		loadonce : false,
		cellEdit: false,
		rowNum:16,
		width: 630,
		height: 475,
		rowList:[16,32,48],
		viewrecords: true,
		rownumbers: true,
		gridview: true,
		modal: true,
		caption: 'Selling Rate Customer (hanya menampilkan data selling yang masih valid) ?>',
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
		
		ondblClickRow: function(rowid, e){
			var selected = $("tr.jqgrow > td > input.cbox:checked", grid[0]);						
			selected[rowid] = status;
			show_table_selling_rate(rowid);	
			$("#table-destination").jqGrid("setGridParam", {datatype: 'json', postData:{ rateguidance_id:rowid }}).trigger("reloadGrid");	
			$( "#btnPrint" ).html('<div id="topbutton" style="float:right"><a href="#" onClick="print_data('+rowid+')">PRINT OUT</a></div>'); 			
		}
		
	}).navGrid('#pager',{ edit: false, add: false, del: false,},
			{height: "auto", width: "auto", top: 3, left: 250, closeAfterEdit: true, reloadAfterEdit : true, modal: false, editCaption: "Edit Data",
				afterSubmit: function () {
    				grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
    				return [true];
				}
			}, 
   		{height: "auto", width: "auto", top: 3, left: 250, closeAfterAdd: true, reloadAfterAdd : true, modal: false, addCaption: "Insert Data",
   			afterSubmit: function () {
    				grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
    				return [true];
				}
   		},
     		{height: "auto", width: "auto"},
      	{modal: true, height: "auto", width: "auto", closeAfterSearch : true, multipleSearch: false,},
     		{ }
    );
});

function show_table_selling_rate()
{
	var N = arguments[0];
	var shipper_id = $('#table').jqGrid('getCell', N,'shipper_id');
	var shipper_name = $('#table').jqGrid('getCell', N,'shipper_name');
	$( "#Form-Destination" ).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
	$( "#Form-Destination" ).dialog(
	{
		autoOpen: true,
		resizable: false,
		height: 600,
		width: '1080',
		modal: true,
		title: "SELLING PRICE TOP : "+shipper_name,
		close: function()  {$(this).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>')}
	});
	
	$.ajax({
		type: "POST", 
		url : "<?php echo site_url('pricelist/air_selling_rate/open_form_selling')?>",
		data: { id: N, shipper_id : shipper_id, shipper_name : shipper_name },
		success:function(XMLHttpRequest)
		{
			$("#Form-Destination").html(XMLHttpRequest)
		}
	})
}

</script>