<?php // echo ! empty($h1_title) ? '<h1>' . $h1_title . '</h1>': ''; ?>

<div id="main-page" style="padding-top: 70px;">
	<center>
		<table id="table" ></table>
	</center>
	<div id="pager"></div>
</div>

<div id="con-sales"></div>

<script type="text/javascript">
$(document).ready(function () {
	var grid = $("#table"), i;	
	$("#table").jqGrid(
	{ 
		url:"<?php echo base_url('reference/shipper_exportir/getDatarefshipperexportir')?>",
		mtype : "post",
		datatype: "json",
		ajaxSelectOptions: {type: "GET"},
		colNames:
		[
			'CODE',
			'NAME', 
			'ADDRESS', 
			'NO NPWP', 
		],
    	colModel:
		[
			{name:'shipper_id',index:'shipper_id', align:"left",width:25,
				editable: true, 
				edittype:"select",
				editoptions:{
						dataUrl:'<?= base_url("afg_c_dropdown/dropdown_shipper/shipper_code") ?>',
						disabled: true,
				},
				<?php echo 'search: true' ?>,
				stype:"select",
				searchoptions: {
					searchhidden: true,
					sopt:['eq'],
					dataUrl:'<?= base_url("afg_c_dropdown/dropdown_shipper_search/shipper_code") ?>',
               buildSelect:function(response){
               	var obj = $.parseJSON(response);
               	return obj;
          		}
          	}
			},				
			{name:'shipper_name',index:'shipper_name', align:"left", width:50,
				editable: true, 
				edittype:"select",
				editoptions:{
						dataUrl:'<?= base_url("afg_c_dropdown/dropdown_shipper/shipper_name") ?>',
						disabled: true,
				},
				<?php echo 'search: true' ?>,
				stype:"select",
				searchoptions: {
					searchhidden: true,
					sopt:['eq'],
					dataUrl:'<?= base_url("afg_c_dropdown/dropdown_shipper_search/shipper_name") ?>',
               buildSelect:function(response){
               	var obj = $.parseJSON(response);
               	return obj;
          		}
          	}
			},
			{name:'shipper_address',index:'shipper_address', align:"left", width:50,
				editable: true,
				edittype:"textarea",
				editoptions:{
					cols:"40", rows:"1",
					dataInit: function (el) { 
						$(el).css({'max-width':'400px','min-width':'300px','min-height':'50px'}).attr('disabled','disabled'); 
					},
					editoptions:{
						disabled: true,
					},
				},
				<?php echo 'search: true' ?>,
				stype:"text",
				searchoptions: {
					searchhidden: true,
					sopt:['cn','eq'],
          	}
			},
			{name:'shipper_npwp',index:'shipper_npwp', align:"left", width:30,
				editable: true,
				edittype:"text",
				editoptions:{
					dataInit: function (el) { 
						$(el).css({'max-width':'400px','min-width':'300px','text-transform':'uppercase'}); 
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
		sortname: "shipper_id",
		sortorder: "asc",
		multiselect:true,
		loadonce : false,
		cellEdit: false,
		rowNum:15,
		width: 830,
		height: 370,
		rowList:[15,30,45],
		viewrecords: true,
		rownumbers: true,
		gridview: true,
		modal: true,
		caption:"Shipper Exportir Data Table",
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
    			var shipper = grid.jqGrid('getCell',rowid,'shipper_exportir_code'); 
    			open_con_sales(shipper);			
     		}
        	else
        	{
        		var cbs = $("tr.jqgrow > td > input.cbox:disabled", grid[0]);  		
    			cbs.attr("disabled", false);       			
        	}
		},
		
	}).navGrid('#pager',{ edit: true, add: false, del: false, refresh:false },
			{height: "auto", width: "auto", viewPagerButtons: false, closeAfterEdit: true,
				afterSubmit: function(response,postdata) {
					var data = $.parseJSON(response.responseText);
					var curPage = grid.getGridParam('page');
					grid.jqGrid('setGridParam',{ datatype: 'json' }).trigger('reloadGrid',[{page:curPage}]);
					return [true];
				}
			}, 
   		{height: "auto", width: "auto", },
     		{height: "auto", width: "auto", },
      	{modal: true, height: "auto", width: "auto", closeAfterSearch : true, multipleSearch: false,},
     		{ }
	);
});

function open_con_sales(shipper){
	
	
	/*
	$( "#con-sales" ).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
	$( "#con-sales" ).dialog(
	{
		autoOpen: true,
		resizable: false,
		top:100,
		height: 'auto',
		width: 460,
		modal: true,
		title: "DATA SALES for SHIPPER "+shipper,
		close: function() 
		{	
			enabled_top_button();
			$(this).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
		}
	});
	
	$.ajax({
		type: "POST", 
		url : "<?php echo site_url('afg_c_trucking/open_trucking_table')?>",
		success:function(XMLHttpRequest)
		{
			$('#con-sales').html(XMLHttpRequest)
		},
		error:function(XMLHttpRequest)
		{
			$('#con-sales').html(XMLHttpRequest)
		}
	})
	*/
}

</script>