<?php // echo ! empty($h1_title) ? '<h1>' . $h1_title . '</h1>': ''; ?>

<div id="main-page" style="padding-top: 70px;">
	
</div>

<div id="tabs">
	<ul id="tabs-menu">
	<?php 
		$user_level = $this->session->userdata('level_id');
		if($user_level == 0 || $user_level == 1 || $user_level == 2|| $user_level == 4) {
	?>
		<li><a href="#tab-1">AGENT TRANSHIPMENT</a></li>
		<li><a href="#tab-2">AGENT PENAGIHAN</a></li>
		<?php } ?>
	</ul>
	<?php 
		$user_level = $this->session->userdata('level_id');
		if($user_level == 0 || $user_level == 1 || $user_level == 2|| $user_level == 4) {
	?>
	<div id="tab-1" class="the-tab">
		<center>
		<table id="table"></table>
		</center>
		<div id="pager"></div>
	</div>
<?php }  ?>
	<div id="tab-2" class="the-tab">
		
		<div style="clear:both"></div>
		<center>
			<table id="agent"></table>
		</center>	
		<div id="pageragent"></div>		
   </div>
   <!--/end TAB1-->	
</div>

<script type="text/javascript">
$(document).ready(function()
{
	load_file_head('css',tmppub+'css/agent_management.css?v='+time)
	$('#tabs .the-tab').hide();
	$('#tabs div:first').show();
	$('#tabs ul li:first').addClass('active');
 	$('#tabs ul li a').click(function(){
		$('#tabs ul li').removeClass('active');
		$(this).parent().addClass('active');
		var currentTab = $(this).attr('href');
		$('#tabs .the-tab').hide();
		$(currentTab).show();
		return false;
	});	
	
	generateTabel();
	generateAgent();
	
	
	$(".ui-jqgrid-titlebar-close",$("#table")[0].grid.cDiv).hide();
	$(".ui-jqgrid-titlebar-close",$("#agent")[0].grid.cDiv).hide();
	
});
function generateTabel() {
	var grid = $("#table"), i;	
	$("#table").jqGrid(
	{ 
		url: ref+"/agent_company/getDataagent_company",
		mtype : "post",
		datatype: "json",
		colNames:
		[
			'CODE','NAME', 'CITY', 'ADDRESS', 'REMARKS','TAX ID',
		],
    	colModel:
		[
			{name:'agent_company_code',index:'agent_company_code', align:"left", 
				width:20, 
				editable: true, 
				editoptions:{
					size:"40"
				},
				<?php echo 'search: true' ?>,
				stype:"text",
				searchoptions: {
					searchhidden: true,
					sopt:['cn','eq'],
          	}
			},
			{name:'agent_company_name',index:'agent_company_name',
				width:50, 
				editable: true, 
				editoptions:{
					size:"40",
				},
				<?php echo 'search: true' ?>,
				stype:"text",
				searchoptions: {
					searchhidden: true,
					sopt:['cn','eq'],
          	}
			},
			{name:'agent_company_city',index:'agent_company_city', align:"left", 
				width:20, 
				editable: true, 
				editoptions:{
					size:"40",
				},
				<?php echo 'search: true' ?>,
				stype:"text",
				searchoptions: {
					searchhidden: true,
					sopt:['cn','eq'],
          	}
			},
			{name:'agent_company_address',index:'agent_company_address', 
				width:50, 
				editable: true, 
				edittype:"textarea",
				editoptions:{
					cols:"40", rows:"5",
				},
				<?php echo 'search: true' ?>,
				stype:"text",
				searchoptions: {
					searchhidden: true,
					sopt:['cn','eq'],
          	}
			},
			{name:'agent_company_remark',index:'agent_company_remark', align:"left", 
				width:50, 
				editable: true, 
				edittype:"textarea",
				editoptions:{
					cols:"40", rows:"3",
				},
				<?php echo 'search: true' ?>,
				stype:"text",
				searchoptions: {
					searchhidden: true,
					sopt:['cn','eq'],
          	}
			},
			{name:'agent_company_npwp',index:'agent_company_npwp', align:"left", width:30, 
				editable: true,
				edittype:"text",
				editoptions:{
					size:"40",
					dataInit: function (el) { 
						$(el).css({'text-transform':'uppercase'}); 
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
		sortname: "agent_company_id",
		sortorder: "desc",
		loadonce:false,
  		viewrecords: true,
        multiselect:true, 
		modal: true,
		rownumbers: true,
		rowNum: 15,
		top: 20,
		width: 830,
		height: 370,
		rowList:[15,30,45],
		gridview: true,
		caption:"Agent Company Data Table",
		editurl: ref+'/agent_company/manage_data',
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
		
	}).navGrid('#pager',{ edit: true, add: true, del: true, refresh: false},
			{height: "auto", width: "auto", 
				closeAfterEdit: true, 
				reloadAfterEdit : true, 
				modal: true, 
				editCaption: "Edit Data Agent Company",
				viewPagerButtons: false,
				afterSubmit: function(response,postdata) {
					var data = $.parseJSON(response.responseText);
					var curPage = grid.getGridParam('page');
					grid.jqGrid('setGridParam',{ datatype: 'json' }).trigger('reloadGrid',[{page:curPage}]);
					return [true];
				}
			}, 
   		{height: "auto", width: "auto", 
   			closeAfterAdd: true, 
   			reloadAfterAdd : true, 
   			modal: true, 
   			addCaption: "Insert Data Agent Company",
   			afterSubmit: function () {
    				$('#table').jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
    				return [true];
				}
   		},
     		{height: "auto", width: "auto"},
      	{modal: true, height: "auto", width: "auto", closeAfterSearch : true, multipleSearch: false,},
     		{ }
    );
};

function generateAgent() {
	var grid = $("#agent"), i;	
	$("#agent").jqGrid(
	{ 
		url: ref+"/agent_company/getDataagent_penagihan",
		mtype : "post",
		datatype: "json",
		colNames:
		[
			'CODE','NAME', 'CITY', 'ADDRESS', 'REMARKS','TAX ID',
		],
    	colModel:
		[
			{name:'agent_company_code',index:'agent_company_code', align:"left", 
				width:20, 
				editable: true, 
				editoptions:{
					size:"40"
				},
				<?php echo 'search: true' ?>,
				stype:"text",
				searchoptions: {
					searchhidden: true,
					sopt:['cn','eq'],
          	}
			},
			{name:'agent_company_name',index:'agent_company_name',
				width:50, 
				editable: true, 
				editoptions:{
					size:"40",
				},
				<?php echo 'search: true' ?>,
				stype:"text",
				searchoptions: {
					searchhidden: true,
					sopt:['cn','eq'],
          	}
			},
			{name:'agent_company_city',index:'agent_company_city', align:"left", 
				width:20, 
				editable: true, 
				editoptions:{
					size:"40",
				},
				<?php echo 'search: true' ?>,
				stype:"text",
				searchoptions: {
					searchhidden: true,
					sopt:['cn','eq'],
          	}
			},
			{name:'agent_company_address',index:'agent_company_address', 
				width:50, 
				editable: true, 
				edittype:"textarea",
				editoptions:{
					cols:"40", rows:"5",
				},
				<?php echo 'search: true' ?>,
				stype:"text",
				searchoptions: {
					searchhidden: true,
					sopt:['cn','eq'],
          	}
			},
			{name:'agent_company_remark',index:'agent_company_remark', align:"left", 
				width:50, 
				editable: true, 
				edittype:"textarea",
				editoptions:{
					cols:"40", rows:"3",
				},
				<?php echo 'search: true' ?>,
				stype:"text",
				searchoptions: {
					searchhidden: true,
					sopt:['cn','eq'],
          	}
			},
			{name:'agent_company_npwp',index:'agent_company_npwp', align:"left", width:30, 
				editable: true,
				edittype:"text",
				editoptions:{
					size:"40",
					dataInit: function (el) { 
						$(el).css({'text-transform':'uppercase'}); 
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
		sortname: "agent_company_id",
		sortorder: "desc",
		loadonce:false,
  		viewrecords: true,
      multiselect:true, 
		modal: true,
		rownumbers: true,
		rowNum: 15,
		top: 5,
		width: 880,
		height: 370,
		rowList:[15,30,45],
		gridview: true,
		caption:"Agent Company Data Table",
		editurl: ref+'/agent_company/manage_data_penagihan',
		cellsubmit:'clientArray',
		pager:'#pageragent',
				
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
		
	}).navGrid('#pageragent',{ edit: true, add: true, del: true, refresh: false},
			{height: "auto", width: "auto", 
				closeAfterEdit: true, 
				reloadAfterEdit : true, 
				modal: true, 
				editCaption: "Edit Data Agent Company",
				viewPagerButtons: false,
				afterSubmit: function(response,postdata) {
					var data = $.parseJSON(response.responseText);
					var curPage = grid.getGridParam('page');
					grid.jqGrid('setGridParam',{ datatype: 'json' }).trigger('reloadGrid',[{page:curPage}]);
					return [true];
				}
			}, 
   		{height: "auto", width: "auto", 
   			closeAfterAdd: true, 
   			reloadAfterAdd : true, 
   			modal: true, 
   			addCaption: "Insert Data Agent Company",
   			afterSubmit: function () {
    				$('#table').jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
    				return [true];
				}
   		},
     		{height: "auto", width: "auto"},
      	{modal: true, height: "auto", width: "auto", closeAfterSearch : true, multipleSearch: false,},
     		{ }
    );
};
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