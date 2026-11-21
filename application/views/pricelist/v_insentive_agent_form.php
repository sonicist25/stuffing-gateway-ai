
		<table style="margin-bottom: 10px;">
			<tr>
				<td height="30" width="90">Date</td>
				<td><input type="text" id="dateForm"></td>
			</tr>
			<tr>
				<td height="30">Kode Agent</td>
				<td>
				<?php 
				$agent = $this->agent_company_model->getListAgent()->result();
				$data['option_agent'][ ]=' ';
				foreach ($agent as $row)
				{	
					$data['option_agent'][$row->agent_company_code]=$row->agent_company_code;
				}		
				echo form_dropdown('dropdownAgent',$data['option_agent'],' ','id="dropdownAgent" style="width: 150px;"');
				?>
				<input type="hidden" id="idInsentive" style="width: 50px">
				<input type="hidden" id="operForm" style="width: 50px">
				</td>
			</tr>
		</table>

		<table id="table-detail"></table>
		<div id="page-detail" style="font-size: 11px;"></div>
		<div style="clear: both; height: 20px;"></div>
		<hr>
		
		<div style="float:right; font-size: 11px; margin-right: 20px">
			<input type="button" class="custom" value=" NEW " style="width: 60; height: 30" id="btnNew" onclick="new_insentive()">
			<input type="button" class="custom" value=" EDIT " style="width: 60; height: 30" id="btnEdit" onclick="edit_insentive()">
			<input type="button" class="custom" value=" PREV " style="width: 60; height: 30" id="btnPrev" onclick="get_insentive('prev');">
			<input type="button" class="custom" value=" NEXT " style="width: 60; height: 30" id="btnNext" onclick="get_insentive('next');">		
			<input type="button" class="custom" value="DELETE" style="width: 60; height: 30" id="btnDelete">
			<input type="button" class="custom" value="BROWSE" style="width: 70; height: 30" id="btnBrowse" onclick="browse_insentive()">
			
			<input type="button" value="EXIT" style="width: 70; height: 30; margin-left: 30" id="btnExit" onclick="exit_insentive()">
		</div>
		
<script type="text/javascript">
$(document).ready(function () {
	browse_insentive()
	//get_insentive('last');
	//table_detail();
});

function exit_insentive(){
	$( "#FORM-INTENSIVE" ).fadeOut('slow', function(){
		$(this).dialog('close')	
	})
}



function table_detail()
{
	var grid = $('#table-detail'), i;
	grid.jqGrid({
		url:"<?php echo base_url('pricelist/insentive_agent/table_detail')?>", 
		mtype : "post",  
		datatype: "json",
		postData:{ id_insentive : $( '#idInsentive' ).val() },
		colNames:
		[ 
			'Containers',
			'Destination',
			'Amount',
			'Currency',
		],
		colModel:
		[
			{name:'countainer',index:'countainer', width:100, align:"center", editable: true,
				edittype:"select", editoptions:{dataUrl:'<?php echo base_url("afg_c_dropdown/dropdownContainer")?>'}
			},
			{name:'destination',index:'destination', width:200, align:"left", editable: true,
				edittype:"select", editoptions:{width: 30, dataUrl:'<?php echo base_url("afg_c_dropdown/dropdownDestination")?>'}
			},
			{name:'amount',index:'amount', width:100, align:"right", editable: true,
				formatter: 'number',
    			formatoptions: { decimalSeparator: ",", thousandsSeparator: " ", decimalPlaces: 2, defaultValue: ' ' },
    			editoptions: {
    				dataInit: function (el) 
					{ 
						$(el).attr('onKeyup','formatUang(this)').attr('onChange','formatUang(this)').attr('title','Hanya boleh memasukan angka'); 
					}
				},	
			},
			{name:'currency',index:'currency', align:"center", width:100, editable: true, 
				edittype:"select",formatter:'select', editoptions:{value:" : ;USD:USD;RP.:RP."}
			},
		],
		sortname: "id",
		sortorder: "desc",
		loadonce : false,
		shrinkToFit: false,
		multiselect:true, 
		modal: true,
		rownumbers: true,
		rowNum:5,
		width: 580,
		height: 170,
		rowList:[10,20,30],
		viewrecords: true,
		rownumbers: true,
		gridview: true,
		cellEdit:true,
		cellsubmit:'clientArray',
		pager: '#page-detail',
		caption:false,
		editurl:'insentive_agent/manage_detail',
						
		loadComplete: function(data) {
			$("#cb_"+grid[0].id).hide();
			$(".ui-jqgrid-titlebar-close",$("#table-detail")[0].grid.cDiv).hide();
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
		beforeSaveCell: function(rowid, cellname, value, iRow, iCol) {
			update_table(rowid,cellname,value)
    	},			
	})
	grid.navGrid('#page-detail', { add: false, edit: false, del: true, refresh: false, _search: false },{},{},{})
	grid.navButtonAdd('#page-detail',{ 
			caption:" ", 
			buttonicon:"ui-icon-plus", 
			onClickButton: insert_table, 
			position: "first", 
			title:"Insert new recordd", 
			cursor: "pointer"
	})
}

function insert_table()
{
	$.ajax
	({
		type: "POST",
		url : "<?php echo base_url('pricelist/insentive_agent/manage_detail')?>",
		data : { 
			oper : 'add',
			id		: '',
			id_insentive : $("input[id='idInsentive']").val(),
			countainer : '',
			destination : '',
			amount: '',
			currency: '',
		},
		success: function(data)
		{
			$('#table-detail').jqGrid("setGridParam", { datatype: 'json', sortname: "id", sortorder: "asc", }).trigger("reloadGrid");
		}
	})
}

function update_table(rowid,cellname,value)
{
	if(cellname == 'amount'){
		value = value.replace(/\ /g,'')
	}
	$.ajax
	({
		type:"POST",
		url : "<?php echo base_url('pricelist/insentive_agent/manage_detail')?>",
		data:
		{
			id : rowid,
			kolom : cellname,
			isi : value,
			oper : 'edit',
		},
		success:function(response)
		{
			var data = $.parseJSON(response);
			$('#table-detail').jqGrid("setGridParam", { datatype: 'json', sortname: "id", sortorder: "asc", }).trigger("reloadGrid");
    		return [true];
		}
	})
}

function isi(value, colname)
{
	if (value != '') 
		return [true,""];
   else 
   	$( 'refNo' ).focus();
   	return [false,"Please insert \"RefNo\", \"Date\", \"Kode Agent\" First"];
}

function disabled_all()
{
	$( '#btnNew' ).attr('disabled',false);
	$( '#btnEdit' ).attr('disabled',false);
	$( '#btnNext' ).attr('disabled',false);
	$( '#btnPrev' ).attr('disabled',false);
	$( '#btnBrowse' ).attr('onClick','browse_insentive()').val('BROWSE');
	$( '#btnDelete' ).attr('onClick','delete_insentive_agent()').val('DELETE');
}

function enabled_all()
{
	$( '#refNo' ).attr('disabled',false);
	$( '#dateForm' ).attr('disabled',false);
	$( '#dropdownAgent' ).attr('disabled',false);
	
	$( '#btnNew' ).attr('disabled',true);
	$( '#btnEdit' ).attr('disabled',true);
	$( '#btnNext' ).attr('disabled',true);
	$( '#btnPrev' ).attr('disabled',true);
	$( '#btnBrowse' ).attr('onClick','save_insentive()').val('SAVE');
	$( '#btnDelete' ).attr('onClick','cancel("new")').val('CANCEL');
}

function editor(input)
{
	if(input == 'true')
	{
		$( '#add_table-detail' ).attr('hidden', true);
		$( '#edit_table-detail' ).attr('hidden', true);
		$( '#del_table-detail' ).attr('hidden', true);
	}
	else
	{
		$( '#add_table-detail' ).attr('hidden', false);
		$( '#edit_table-detail' ).attr('hidden', false);
		$( '#del_table-detail' ).attr('hidden', false);
	}	
}

function browse_insentive()
{
	$( "#FORM-BROWSE" ).dialog(
	{
		autoOpen: true,
		resizable: false,
		height: 350,
		width: 580,
		modal: true,
		title :"BROWSE DATA",
		close: function() {

		}
	});
	table_browse()
}

/*
function table_browse()
{
	var grid = $("#table-browse"), i;		   			
	grid.jqGrid(
	{
		url:"<?php echo base_url('pricelist/insentive_agent/table_browse')?>", 
		mtype : "post",  
		_search: true, 
		datatype: "json",  
		colNames:
		[ 
			'Date',
			'Agent',	
		],
		colModel:
		[
			{name:'date',index:'date', width:200, align:"center"},
			{name:'agent_code',index:'agent_code', width:200, align:"left"},
		],
			loadonce: true,
			modal: true,
			rownumbers: true,
			rowNum:10,
			width: 550,
			height: 240,
			rowList:[10,20,30],
			viewrecords: true,
			rownumbers: true,
			gridview: true,
			pager: '#page-browse',
			caption:"",
						
			loadComplete: function() {
				$("#cb_"+grid[0].id).hide();
				$(".ui-jqgrid-titlebar-close",$("#table-browse")[0].grid.cDiv).hide();
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
    				$( '#idInsentive' ).val(rowid);
    				get_insentive('pilih');
    				$( "#FORM-BROWSE" ).dialog( 'close' );
    				selected.attr("checked", false);
    			}
        		else
        		{
        			   			
        		}
			},				
		}).navGrid('#page-browse', { edit: false, add: false, del: false,},
			{ },
   		{ },
     		{ },
      	{closeAfterSearch : true, captions: "Search Data", Find:"Cari"}, 
     		{ }
		);
}
*/

function form_close()
{
	$( "#FORM-INTENSIVE" ).dialog('close');
}

function cancel(input)
{
	if(input == 'edit'){
		get_insentive('pilih');	
	} else {
		get_insentive('last');
	}
}

function new_insentive()
{
	enabled_all();
	$( '#dateForm' ).val('').datepicker({dateFormat : "dd/mm/yy"});
	$( '#dropdownAgent' ).val('');
	$( '#idInsentive' ).val('');
	$( '#operForm' ).val('add');
	$('#table-detail').jqGrid("clearGridData", true);
}

function edit_insentive()
{
	enabled_all()
	$( '#dateForm' ).datepicker({dateFormat : "dd/mm/yy"});
	$( '#operForm' ).val('edit');
}

function delete_insentive_agent()
{
	$( '#delete-confirm' ).fadeIn('slow', function(){
		$(this).dialog(
		{
			resizable: false,
			height: 'auto',
			width: 280,
			modal: true,
			title :"Delete Confirm",
			buttons: 
			{
				"DELETE": function() 
				{
					$.ajax
					({
						type: "POST",
						url : "<?php echo base_url('pricelist/insentive_agent/manage_data')?>",
						data : { id : $( '#idInsentive' ).val(), oper: 'del' },
						success: function(data)
						{
							var data = $.parseJSON(data);
							if(data.status == 1 )
							{
								get_insentive('last');
							}
							else
							{
								get_insentive('pilih');
							};
							$( '#delete-confirm' ).dialog('close');
						},
						error:function()
						{
							show_messages('Data can\'t be deleted');
							return false;
						}
					}); 
				},
				"CANCEL": function() 
				{
					$( this ).dialog( "close" ); 
				}
			},
			close: function() {	}
		}).css('font-size','11px').html('Are You sure will delete this data ?')
	})
}

function save_insentive()
{
	var ref_no 		= $( '#refNo' ).val();
	var date	 		= $( '#dateForm' ).val();
	var agent_code	= $( '#dropdownAgent' ).val();
	var myData = 
	{
		id 			: $( '#idInsentive' ).val(),
		oper 			: $( '#operForm' ).val(),
		ref_no 		: ref_no,
		date	 		: date,
		agent_code	: agent_code,
	}
	if (ref_no == '')
	{
		show_messages('Input \"RefNo\" Empty Value');
		$('#refNo').focus();
		return false;
	}
	else if (date == '')
	{
		show_messages('Input \"Date\" Empty Value');
		$( '#dateForm' ).focus();
		return false;
	}
	else if (agent_code == '0')
	{
		show_messages('Input \"Kode Agent\" Empty Value');
		$( '#dropdownAgent' ).focus();
		return false;
	}
	else
	{
		$.ajax
		({
			type: "POST",
			url : "<?php echo base_url('pricelist/insentive_agent/manage_data')?>",
			data : myData,
			success: function(data)
			{
				var data = $.parseJSON(data);
				if(data.status == 1 )
				{
					show_messages(data.messages);
					if(data.oper=='edit'){
						get_insentive('pilih');
					} else {
						get_insentive('last');
					}
				}
				else
				{
					show_messages(data.messages);
					return false;
				};
			},
			error:function()
			{
				show_messages('ERROR ACCOURED');
				return false;
			}
		});
	}
}

function formatUang(input) 
{
	var num = input.value.replace(/\ /g,'');
	if(!isNaN(num)){
   	if(num.indexOf('.') > -1){
    		num = num.split('.');
    		num[0] = num[0].toString().split('').reverse().join('').replace(/(?=\d*\.?)(\d{3})/g,'$1 ').split('').reverse().join('').replace(/^[\ ]/,'');
    		if(num[1].length > 2){
				show_messages('Only 2 ( two ) desimal allowed');
    			num[1] = num[1].substring(0,num[1].length-1);
    		} input.value = num[0]+'.'+num[1];
    	} else{ input.value = num.toString().split('').reverse().join('').replace(/(?=\d*\.?)(\d{3})/g,'$1 ').split('').reverse().join('').replace(/^[\ ]/,'') };
	}
	else
	{ 
		show_messages('Only numeric allowed');
    	input.value = input.value.substring(0,input.value.length-1); 
	}
}

function gubahUang(num) 
{
	if(num.indexOf('.') > -1){
		num = num.split('.');
 		num[0] = num[0].toString().split('').reverse().join('').replace(/(?=\d*\.?)(\d{3})/g,'$1,').split('').reverse().join('').replace(/^[\,]/,'');
		return num[0];
	}
}

</script>