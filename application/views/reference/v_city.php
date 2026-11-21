<?php // echo ! empty($h1_title) ? '<h1>' . $h1_title . '</h1>': ''; ?>

<div id="main-page" style="padding-top: 70px;">
	<center>
		<table id="table" ></table>
	</center>
	<div id="pager"></div>
</div>

<script type="text/javascript">
$(document).ready(function (){
	var grid = $("#table"), i;	
	$("#table").jqGrid(
	{ 
		url:ref+"/city/getDataCity",
		mtype : "post",
		datatype: "json", 
		colNames:
		[
			'City ID','City Name','Country','Group','','UN CODE','Remarks',
		],
    	colModel:
		[
			{name:'city_id',index:'city_id', align:"center",width:5, editable: true, 
				editoptions:{
					size:"40",
					maxlength:"50",
				},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},				
			{name:'city_name',index:'city_name', align:"left", width:10, editable: true, 
				editoptions:{
					size:"40",
					dataInit: function(element) {
        				$(element).css({ "text-transform": "uppercase"})
      			}
				},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'country_id',index:'country_id', align:"left",width:10, 
				editable: true, 
				edittype:"select", 
				editoptions:{dataUrl:'city/dropdowncauntry'},
				<?php echo 'search: false' ?>,
			},	
			{name:'group_id',index:'group_id', align:"center",width:5, 
				editable: true, 
				edittype:"select",
				formatter:'select', 
				editoptions:{value:"1:1;2:2;3:3", width: 30,},
				<?php echo 'search: false' ?>,
			},
			{name:'buttonCountry',index:'buttonCountry', editable: true, hidden: true, 
				editrules:{edithidden:true},
				edittype:"button",
				editoptions:{
					width: 5,
					height:5,
					title:"Insert new country",
				},
			},		
		
				{name:'origin_id',index:'origin_id', align:"left", width:10, editable: true, 
				editoptions:{
					size:"40",
					maxlength:"50",
				},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'remarks', index: 'remarks', editable: true, edittype:"textarea", hidden:true,
				editoptions:{cols:"40", rows:"2"},
				editrules:{edithidden:true},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
		], 
		sortname: "id_city",
		sortorder: "desc",
  		multiselect:true,
		loadonce : false,
		cellEdit: false,
		rowNum:16,
		width: 630,
		height: 370,
		rowList:[1000,2000,3000,4000],
		viewrecords: true,
		rownumbers: true,
		gridview: true,
		modal: true,
		pager:'#pager',
		editurl:ref+"/city/manage_data",
		caption:"Manage Data City",		
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
		
	}).navGrid('#pager',{ edit: true, add: true, del: true, refresh: false },
			{height: "auto", width: "auto", top: 3, left: 250, closeAfterEdit: true, reloadAfterEdit : true, modal: true, editCaption: "Edit Data City", 
				afterSubmit: function () {
    				$('#table').jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
    				return [true];
				},
				beforeShowForm: function(frm) {
					$('#buttonCountry').val(' + ');
					$('#buttonCountry').attr('onClick','javascript:new_coutry()');
				},
				viewPagerButtons: false,
			},
   		{height: "auto", width: "auto", top: 3, left: 250, closeAfterAdd: true, reloadAfterAdd : true, modal: true, addCaption: "Insert Data City", 
				afterSubmit: function () {
    				$('#table').jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
    				return [true];
				},
				beforeShowForm: function(frm) {
						$('#buttonCountry').val(' + ');
						$('#buttonCountry').attr('onClick','javascript:new_coutry()');
				},
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

function new_coutry(){
	window.open("country", "_self",1,true);
}

</script>