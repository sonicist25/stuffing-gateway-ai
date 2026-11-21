<div id="form-main-page" class="main-page">
	<center>
		<table id="table" ></table>
	</center>
	<div id="pager"></div>	
	<div style="clear: both"></div>
</div>

<div id="Form-Destination">
	<div id="btnPrint"></div>
	<div style="clear: both"></div>
	<center>
		<table id="table-destination" ></table>
	</center>
	<div id="pager-destination"></div>
</div>

<div id="PRINT"></div>

<script type="text/javascript">
$(document).ready(function () {
	var grid = $("#table"), i;	
	grid.jqGrid(
	{ 
		url:"<?php echo base_url('pricelist/rate_guidance/get_rate_guidance')?>",
		mtype : "post",
		datatype: "json",
		sortname: "id",
		sortorder: "desc",
		colNames:
		[
			'Rate Guidance Name','',
		],
    	colModel:
		[
			{name:'name',index:'name', align:"left", width:540, editable: true,
				editoptions: { size: 40, dataInit: function (el) { $(el).css('text-transform', 'uppercase'); }}
			},
			{name:'user',index:'user', align:"left", width:50, editable: true, hidden: true},
		], 
		shrinkToFit	: false,
		loadonce	:false,
  		viewrecords: true,
      multiselect:true, 
		modal: true,
		rownumbers: true,
		rowNum: 15,
		top: 20,
		width: 630,
		height: 370,
		rowList:[15,30,45],
		gridview: true,
		caption: '<?php echo $table_title ?>',
		editurl:'rate_guidance/manage_data',
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
			$("#table-destination").jqGrid("setGridParam", {datatype: 'json', postData:{ rate_id: rowid }}).trigger("reloadGrid");
			show_table_destination(rowid);	
			$( "#btnPrint" ).html('<?= form_submit(array("value"=>"PRINT OUT","onclick"=>"print_data('+rowid+')","style"=>"float: right")) ?>'); 			
		}
		
	}).navGrid('#pager',{ edit: true, add: true, del: true,},
			{height: "auto", width: "auto", top: 3, left: 250, viewPagerButtons: false, closeAfterEdit: true, reloadAfterEdit : true, modal: false, editCaption: "Edit Data <?php echo $table_title ?>",
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

function show_table_destination(rate_id){
	var name = $('#table').jqGrid('getCell', rate_id,'name');
	
	$( "#Form-Destination" ).dialog({
		autoOpen: true,
		resizable: false,
		height: 470,
		width: 670,
		modal: true,
		title: "RATE GUID LINE FOR : "+name,
		buttons:  { },
		close: function()  {	 }
	});
	
	var grid = $("#table-destination"), i;	
	grid.jqGrid(
	{ 
		url:"<?php echo base_url('pricelist/rate_guidance/get_rate_guidance_destination')?>",
		mtype : "post",
		datatype: "json",
		datatype: "json",
		sortname: "id",
		sortorder: "desc",
		postData:{ rate_id: rate_id },
		colNames:
		[
			'','Destination','Rate','Remarks'
		],
    	colModel:
		[
			{name:'rate_id',index:'rate_id', align:"left", width:50, editable: true, hidden: true,},
			{name:'destination',index:'destination', align:"center", width:240,
				 editable: true, edittype:"select", editoptions:{dataUrl:'<?php echo base_url("afg_c_dropdown/dropdownDestination")?>'}
			},
			{name:'rate',index:'rate', align:"right", width:90, editable: true,
				formatter: 'number',
    			formatoptions: { decimalSeparator: ",", thousandsSeparator: " ", decimalPlaces: 0, defaultValue: '' },
    			editoptions: { size: 40, dataInit: function (el) 
				{ 
					$(el).attr('onKeyup','formatUang(this)').attr('onChange','formatUang(this)').attr('title','Hanya boleh memasukan angka'); }
				},	
			},
			{name:'remaks',index:'remaks', align:"left", width:200, editable: true, 
				edittype:"textarea", editoptions:{cols:"40", rows:"3"}
			}
		], 
  		shrinkToFit	: false,
		loadonce	:false,
  		viewrecords: true,
      multiselect:true, 
		modal: true,
		rownumbers: true,
		rowNum: 15,
		top: 20,
		width: 630,
		height: 300,
		rowList:[15,30,45],
		gridview: true,
		editurl:'<?= base_url("pricelist/rate_guidance/manage_data_destination") ?>',
	//	cellsubmit:'clientArray',
		pager:'#pager-destination',
				
		loadComplete: function(data) {
			$("#cb_"+grid[0].id).hide();
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
	}).navGrid('#pager-destination',{ edit: true, add: true, del: true,},
			{
				height: "auto", 
				width: "auto", 
				closeAfterEdit: true, 
				reloadAfterEdit : true, 
				modal: true, 
				editCaption: "Edit Data <?php echo $table_title ?>",
				viewPagerButtons: false,
				beforeShowForm: function(frm) {
					var rowid = grid.getGridParam('selrow');
					var value = grid.jqGrid('getCell',rowid,'rate');
   				$('#rate').val(gubahUang(value));
				},
				afterSubmit: function () {
					grid.jqGrid("setGridParam", {datatype: 'json', postData:{ rate_id: rate_id }}).trigger("reloadGrid");
    				return [true];
    			}
			}, 
   		{
   			height: "auto", 
   			width: "auto", 
   			closeAfterAdd: true, 
   			reloadAfterAdd : true, 
   			modal: true, 
   			addCaption: "Insert Data <?php echo $table_title ?>",
   			beforeShowForm: function(frm) {
   			//	$('#rate_id').val(rate_id);
				},
				beforeSubmit: function(postData,FormId) {
   				postData.rate_id=rate_id;
   				return [true];
				},
				afterSubmit: function () {
    				grid.jqGrid("setGridParam", {datatype: 'json', postData:{ rate_id: rate_id }}).trigger("reloadGrid");
    				return [true];
				},				
   		},
     		{modal: true, height: "auto", width: "auto"},
      	{modal: true, height: "auto", width: "auto", closeAfterSearch : true, multipleSearch: false,},
     		{ }
    );
}

function print_data (id){
	$.ajax
		({
			type: "POST",
			url : "<?php echo base_url('pricelist/rate_guidance/print_data')?>/"+id,
			data:  { id : id },
			success: function(data)
			{
				$( "#PRINT" ).dialog(
				{
					autoOpen: true,
					resizable: false,
					top:10,
					height: 600,
					width: 990,
					modal: true,
					title: "OUTPUT DATA",
					buttons : {  },
					close: function()  {	 }
				});
				$('#PRINT').html('<iframe src="<?php echo base_url("pricelist/rate_guidance/print_data") ?>/'+id+'" width=" 960px" height="700"></iframe>');
			}
	})
}

function formatUang(input) {
	var num = input.value.replace(/\ /g,'');
    if(!isNaN(num)){
    	if(num.indexOf(',') > -1){
    		num = num.split(',');
    		num[0] = num[0].toString().split('').reverse().join('').replace(/(?=\d*\,?)(\d{3})/g,'$1 ').split('').reverse().join('').replace(/^[\ ]/,'');
    		if(num[1].length > 2){
				var message = 'Hanya dapat memasukan 2 ( dua ) digit desimal';
				show_messages(message);
    			num[1] = num[1].substring(0,num[1].length-1);
    		} input.value = num[0]+','+num[1];
    	} else{ input.value = num.toString().split('').reverse().join('').replace(/(?=\d*\,?)(\d{3})/g,'$1 ').split('').reverse().join('').replace(/^[\ ]/,'') };
    }
    else
    { 
		var message = 'Only numeric allowed / Hanya diperbolehkan memasukan angka';
		show_messages(message);
    	input.value = input.value.substring(0,input.value.length-1); 
    }
}

function gubahUang(num) {
	if(num.indexOf('.') > -1){
		num = num.split('.');
 		num[0] = num[0].toString().split('').reverse().join('').replace(/(?=\d*\.?)(\d{3})/g,'$1,').split('').reverse().join('').replace(/^[\ ]/,'');
		return num[0];
	} else {
		return num;
	}
}

</script>