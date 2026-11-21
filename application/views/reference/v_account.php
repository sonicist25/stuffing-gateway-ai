<?php echo ! empty($h1_title) ? '<h1>' . $h1_title . '</h1>': ''; ?>

<div id="main-page" style="padding-top: 70px;">
	<center>
		<table id="table" ></table>
	</center>
	<div id="pager"></div>
</div>

<script type="text/javascript">
$(document).ready(function () 
{
	var grid = $("#table"), i;	
	$("#table").jqGrid(
	{ 
		url:ref+"account/getDatarefAccount",
		mtype : "post",
		datatype: "json",
		postData: { searchField:'id', searchOper:'eq', searchString: 'N' }, 
		colNames:
		[
			'NPWP','Nama','Alamat','Blok','Nomor','RT','RW','Kecamatan','Kelurahan','Kabupaten','Propinsi','Kodepos','Telepon','Email','Contact Person','By'
		],
    	colModel:
		[			
			{name:'tax_id',index:'tax_id', align:"left",width:120, editable: true, editoptions:{size:"45",maxlength:"45"}},	
			{name:'account_name',index:'account_name', width:280, editable: true, editoptions:{size:"45",maxlength:"150"}},
			{name:'account_address',index:'account_address',width:280, editable: true, edittype:"textarea",editoptions:{cols:"40", rows:"5"}},	
			{name:'blok',index:'blok', width:30, align:'left',editable: true },	
			{name:'nomor',index:'nomor', width:30, align:'left',editable: true },	
			{name:'rt',index:'rt', width:30, align:'left',editable: true },	
			{name:'rw',index:'rw', width:30, align:'left',editable: true },	
			{name:'kecamatan',index:'kecamatan', width:100, align:'left',editable: true },	
			{name:'kelurahan',index:'kelurahan', width:100, align:'left',editable: true },	
			{name:'kabupaten',index:'kabupaten', width:100, align:'left',editable: true },	
			{name:'propinsi',index:'propinsi', width:100, align:'left',editable: true },	
			{name:'kodepos',index:'kodepos', width:100, align:'left',editable: true },	
			{name:'account_phone',index:'account_phone',width:170, editable: true, editoptions:{size:"45",maxlength:"45"}},		
			{name:'account_email',index:'account_email',width:170, editable: true, edittype:"textarea",editoptions:{cols:"40", rows:"5"}},			
			{name:'account_cp',index:'account_cp', width:170, editable: true, editoptions:{size:"40",maxlength:"50"}},
			{name:'created_by',index:'account_cp', width:170, editable: true, editoptions:{size:"40",maxlength:"50"}},
		],
  		multiselect:true,
		loadonce : true,
		cellEdit: false,
		hiddengrid : false,
		shrinkToFit: false,
		rowNum:16,
		width: 930,
		height: 370,
		rowList:[10,20,30],
		viewrecords: true,
		rownumbers: true,
		gridview: true,
		modal: true,
		pager:'#pager',
		caption:"Management Account",
		editurl:ref+"account/manage_data",
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
		
	}).navGrid('#pager',{ edit: true, add: true, del: true,},
			{height: "auto", width: "auto", top: 3, left: 250, closeAfterEdit: true, reloadAfterEdit : true, modal: true, editCaption: "Edit Data Company",
				afterSubmit: function () {
    				$('#table').jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
    				return [true];
				}
			},
   		{height: "auto", width: "auto", top: 3, left: 250, closeAfterAdd: true, reloadAfterAdd : true, modal: true, addCaption: "Insert Data Company",
   			afterSubmit: function () {
    				$('#table').jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
    				return [true];
				}
   		},
     		{height: "auto", width: "auto"},
      	{modal: true, height: "auto", width: "auto", closeAfterSearch : true, multipleSearch: false},
     		{ }
    );
});
$.extend($.jgrid.search, {
         width: 410,
   		height: "auto",
   		position:"center",
			autoOpen: true,
   		closeAfterSearch : true, 
   		modal:true, 
   		recreateForm:true,
   		caption:"Find Job",
                afterRedraw: function (p) {
                    var $form = $(this), formId = this.id, // fbox_list
                        bindKeydown = function () {
                            $form.find("td.data>.input-elm").keydown(function (e) {
                                if (e.which === $.ui.keyCode.ENTER) {
                                    $(e.target).change();
                                    $("#" + $.jgrid.jqID(formId) + "_search").click();
                                }
                            });
                        },
                        oldOnChange = p.onChange,
                        myOnChange = function (param) {
                            var $input = $form.find("td.data>.input-elm"), events;
                            oldOnChange.call(this, param);
                            if ($input.length > 0) {
                                events = $input.data("events");
                                if (events && !events.keydown) {
                                    bindKeydown();
                                }
                            }
                        };
                    p.onChange = myOnChange;
                    bindKeydown.call(this);
                }
     });
</script>