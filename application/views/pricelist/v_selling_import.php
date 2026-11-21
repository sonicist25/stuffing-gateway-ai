<div id="form-main-page" class="main-page" style="margin-top:70px">
	<center>
		<table id="table" ></table>
		<div id="pager"></div>
	</center>
</div>

<div id="Form-Destination"></div>
<div id="PRINT"></div>

<script type="text/javascript">
$(document).ready(function () {
	var grid = $("#table"), i;	
	grid.jqGrid(
	{ 
		url:"<?php echo base_url('pricelist/import_selling_rate/get_data_consignee')?>",
		mtype : "post",
		datatype: "json", 	
		colNames:
		[
			'CODE','NAMA',
		],
    	colModel:
		[
			{name:'consignee_import_code',index:'shipper_id', align:"left", width:30, editable: true,
				editoptions: { size: 40, dataInit: function (el) { $(el).css('text-transform', 'uppercase'); }},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'consignee_import_name',index:'shipper_name', align:"left", width:70, editable: true,
				editoptions: { size: 90, dataInit: function (el) { $(el).css('text-transform', 'uppercase'); }},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
		], 
		sortname: "shipper_name",
		sortorder: "asc",
  		multiselect:true,
		loadonce : false,
		cellEdit: false,
		rowNum:15,
		width: 630,
		height: 375,
		rowList:[15,30,45],
		viewrecords: true,
		rownumbers: true,
		gridview: true,
		modal: true,
		caption: '<?php echo $table_title ?>',
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
			show_data(rowid);		
		}
		
	}).navGrid('#pager',{ edit: false, add: false, del: false,},
			{height: "auto", width: "auto", top: 3, left: 250, closeAfterEdit: true, reloadAfterEdit : true, modal: false, editCaption: "Edit Data <?php echo $table_title ?>",
				afterSubmit: function () {
    				grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
    				return [true];
				}
			}, 
   		{height: "auto", width: "auto", top: 3, left: 250, closeAfterAdd: true, reloadAfterAdd : true, modal: false, addCaption: "Insert Data <?php echo $table_title ?>",
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

function show_data(int){
	$.ajax({
		type: "POST", 
		url : "<?php echo site_url('pricelist/import_selling_rate/open_form_selling')?>",
		data: { 
			consignee_id: int, 
			consignee_code : $('#table').jqGrid('getCell', int,'consignee_import_code'),
			consignee_name : $('#table').jqGrid('getCell', int,'consignee_import_name'), 
		},
		success:function(XMLHttpRequest)
		{
			$( "#Form-Destination" ).dialog(
				{
					resizable: false,
					height: 570,
					width: 900,
					modal: true,
					title: "SELLING IMPORT",
					close: function()  {	 }
			});
			$( "#Form-Destination" ).html(XMLHttpRequest)
		}
	})
}

function print_data (id){
	$.ajax
		({
			type: "POST",
			url : "<?php echo base_url('pricelist/selling_rate/print_data')?>/"+id,
			data:  { id : id },
			success: function(data)
			{
				$( "#PRINT" ).dialog(
				{
					autoOpen: true,
					resizable: true,
					top:10,
					height: 'auto',
					width: 'auto',
					modal: true,
					title: "OUTPUT",
					buttons : {  },
					close: function()  {	 }
				});
				$('#PRINT').html('<iframe src="<?php echo base_url("pricelist/selling_rate/print_data") ?>/'+id+'" width=" 960px" height="700"></iframe>');
			}
	})
}

</script>