<?php // echo ! empty($h1_title) ? '<h1>' . $h1_title . '</h1>': ''; ?>

<div id="main-page" style="padding-top: 70px;">
	<center>
		<table id="table" ></table>
	</center>
	<div id="pager"></div>
</div>

<script type="text/javascript">
$(document).ready(function () {
	var grid = $("#table"), i;	
	$("#table").jqGrid(
	{ 
		url:"<?php echo base_url('reference/port_destination/getDatarefportdestination')?>",
		mtype : "post",
		datatype: "json", 
		colNames:
		[
			'Port Code', 'Port Name','Country','UN code','Remarks',
		],
    	colModel:
		[
			{name:'portdestination_code',index:'portdestination_code', align:"center",width:40, editable: true, editoptions:{size:"40",maxlength:"50"},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},				
			{name:'portdestination_name',index:'portdestination_name', align:"left", width:50, editable: true, editoptions:{size:"40",maxlength:"50"},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'portdestination_country',index:'portdestination_country', width:50, editable: true,
				edittype:"select",
				editoptions:
				{
					dataUrl:'dropdownCountry'
				},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
				{name:'uncode',index:'uncode', align:"left", width:50, editable: true, editoptions:{size:"40",maxlength:"50"},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'portdestination_remark',index:'portdestination_remark', width:145, editable: true, edittype:"textarea", editoptions:{ cols:"40", rows:"5"},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
		], 
		shirnkToFit:false,
  		multiselect:false,
		loadonce : true,
		cellEdit: false,
		viewrecords: true,
		rownumbers: true,
		gridview: true,
		modal: true,
		rowNum:16,
		width: 670,
		height: 370,
		rowList:[1000,1500,2000,2500,3000,3500,4000,4500,5000],
		caption:"Port Destination Data Table",
		editurl:ref+'/port_destination/manage_data',
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
		
	}).navGrid('#pager',{ edit: false, add: false, del: false,},
			{height: "auto", width: "auto", top: 3, left: 250, closeAfterEdit: true, reloadAfterEdit : true, modal: true, editCaption: "Edit Data Port Destination",
				viewPagerButtons: false,
				afterSubmit: function () {
    				$('#table').jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
    				return [true];
				}
			},
   		{height: "auto", width: "auto", top: 3, left: 250, closeAfterAdd: true, reloadAfterAdd : true, modal: true, addCaption: "Insert Data Port Destination",
   			afterSubmit: function () {
    				$('#table').jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
    				return [true];
				}
   		},
     		{height: "auto", width: "auto"},
      	{
      		modal: true, 
      		height: "auto", 
      		width: "auto", 
      		closeAfterSearch : true, 
      		multipleSearch: false,
      		/*
      		beforeSubmit: function(postData)
      		{
      			
      		}
      		*/
      	},
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