<table id="count_table"></table>
<div id="count_table_page"></div>

<script type="text/javascript">
$(document).ready(function (){
	var grid = $("#count_table"), i;
	grid.jqGrid(
	{
		url:"<?php echo base_url('afg_c_stuffing_plan/show_table_container')?>",
		mtype : "post", 
		datatype: "json",
		sortname: "sp_id",
		sortorder: "desc",
		postData:{ sp_id: <?php echo $sp_id ?>},
		colNames:
		[
			'',
			'Container Size',
			'Container No',
			'Seal No',
		],
		colModel:
		[
			{name:'id_sp',index:'id_sp', width:165, align:"left", editable: true, hidden: true},
			{name:'count_size',index:'count_size', width:165, align:"left", editable: true,
				edittype:"select",
				editoptions:
				{
					dataUrl:'<?php echo base_url("afg_c_dropdown/dropdownContainer") ?>',
					dataInit: function (el) 
					{ 
						$(el).css('width','150px');  
					}
				}
			},
			{name:'count_no',index:'count_no', width:165, align:"left", editable: true,
				editoptions:
				{
					dataInit: function (el) 
					{ 
						$(el).css('width','150px').css('text-transform','uppercase');  
					}
				}
			},
			{name:'seal_no',index:'seal_no', width:165, align:"left", editable: true,
				editoptions:
				{
					dataInit: function (el) 
					{ 
						$(el).css('width','150px').css('text-transform','uppercase');  
					}
				}
			},
		],
		multiselect:true,
		loadonce : false,
		rowNum:5,
		width: 530,
		height: 100,
		shrinkToFit: true,
		rowList:[5,10,15],
		viewrecords: true,
		rownumbers: false,
		gridview: true,
		pager: '#count_table_page',
		editurl:'afg_c_stuffing_plan/tambah_container_manage',
		caption:false,	
			
		loadComplete: function () {
    		$("#cb_"+grid[0].id).hide();
      },
		
	} 
	).navGrid('#count_table_page', {edit:true, add:true, del:true, refresh: false, <?php echo 'search: false' ?>},
		{
			modal:true,
			closeAfterEdit: true,
			beforeSubmit: function(frm) {
					var count_size = $("#count_size").val();
					var count_no = $("#count_no").val();
					var seal_no = $("#seal_no").val();
					if (count_size == '' || count_no == '' || seal_no == '') {
						return[false,'complite request data'];
					} else {
						return[true,'']
					}
			},
			afterSubmit: function (response) {
    				var data = $.parseJSON(response.responseText);
   				if(data.status == '1'){
   					$("#txtContSealNumber").val('');
    					grid.jqGrid("setGridParam", {datatype: 'json'}).trigger('reloadGrid');
    					return [true];
    				} else {
    					grid.jqGrid("setGridParam", {datatype: 'json'}).trigger('reloadGrid');
    					return [true];
    				}
			},
		},
   	{ 
   		modal: true,
   		beforeShowForm: function () {
   			$('#id_sp').val(<?php echo $sp_id ?>)
   		},
   		closeAfterAdd: true,
   		beforeSubmit: function(frm) {
					var count_size = $("#count_size").val();
					var count_no = $("#count_no").val();
					var seal_no = $("#seal_no").val();
					if (count_size == '' || count_no == '' || seal_no == '') {
						return[false,'complite request data'];
					} else {
						return[true,'']
					}
			},
   		afterSubmit: function (response) {
   				var data = $.parseJSON(response.responseText);
   				if(data.status == '1'){
   					$("#txtContSealNumber").val('');
    					grid.jqGrid("setGridParam", {datatype: 'json'}).trigger('reloadGrid');
    					return [true];
    				} else {
    					grid.jqGrid("setGridParam", {datatype: 'json'}).trigger('reloadGrid');
    					return [true];
    				}
			},
   	},
   	{ },
   	{ },
    	{ }
	)
})
</script>
