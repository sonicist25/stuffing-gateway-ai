<?php // echo ! empty($h1_title) ? '<h1>' . $h1_title . '</h1>': ''; ?>

<div id="form-main-page" class="main-page">
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
		url:"<?php echo base_url('pricelist/export_feeder_rate/get_export_feeder_rate')?>",
		mtype : "post",
		datatype: "json", 	
		colNames:
		[
			'Shipping Company Id','Shipping Company Name','',
		],
    	colModel:
		[
			{name:'code',index:'code', align:"left", width:30, editable: true,
				editoptions: { size: 40, dataInit: function (el) { $(el).css('text-transform', 'uppercase'); }}
			},
			{name:'name',index:'name', align:"left", width:70, editable: true,
				editoptions: { size: 40, dataInit: function (el) { $(el).css('text-transform', 'uppercase'); }}
			},
			{name:'user_id',index:'user_id', align:"left", width:50, editable: true, hidden: true},
		], 
  		multiselect:true,
		loadonce : true,
		cellEdit: false,
		rowNum:16,
		width: 630,
		height: 370,
		rowList:[100,200,300,400,500],
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
			show_table_cost_coloader(rowid);
				
		//	$("#table-destination").jqGrid("setGridParam", {datatype: 'json', postData:{ rateguidance_id:rowid }}).trigger("reloadGrid");	
		//	$( "#btnPrint" ).html('<div id="topbutton" style="float:right"><a href="#" onClick="print_data('+rowid+')">PRINT OUT</a></div>'); 			
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

function show_table_cost_coloader(N){
	var code = $('#table').jqGrid('getCell', N,'code');
	var name = $('#table').jqGrid('getCell', N,'name');
	$( "#Form-Destination").css('background-image', 'url("../public/images/bg-form.png")');
	$( "#Form-Destination" ).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
	$( "#Form-Destination" ).dialog(
	{
		resizable: false,
		height: 530,
		width: 735,
		modal: true,
		title: "COST FEEDER - EXPORT TO : "+name,
		close: function()  {$(this).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>')}
	});
	
	$.ajax({
		type: "POST", 
		url : "<?php echo site_url('pricelist/export_cost_coloader/open_form')?>",
		data: { id : N, code:code, name : name },
		success:function(XMLHttpRequest)
		{
			$( "#Form-Destination" ).html(XMLHttpRequest)
		}
	})
}

/*
function print_data (id){
	$( "#PRINT" ).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
	$( "#PRINT" ).dialog(
	{
		position:'top',
		autoOpen: true,
		resizable: true,
		height: 'auto',
		width: 'auto',
		modal: true,
		title: "OUTPUT",
		buttons : {  },
		close: function()  {$(this).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>')}
	});
	$.ajax
		({
			type: "POST",
			url : "<?php echo base_url('pricelist/export_cost_coloader/print_data')?>/"+id,
			data:  { id : id },
			success: function(data)
			{
				$('#PRINT').html('<iframe src="<?php echo base_url("pricelist/selling_rate/print_data") ?>/'+id+'" width=" 960px" height="700"></iframe>');
			}
	})
}
*/
</script>