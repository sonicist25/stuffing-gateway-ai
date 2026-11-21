<?php // echo ! empty($h1_title) ? '<h1>' . $h1_title . '</h1>': ''; ?>

<div id="main-page" style="padding-top: 70px;">
	
</div>

<div id="tabs" style="margin-left:30px">
	<ul id="tabs-menu">
		<li><a href="#tab-1">AGENT TRANSHIPMENT</a></li>
		<li><a href="#tab-2">AGENT PENAGIHAN</a></li>
	</ul>
	<div id="tab-1" class="the-tab">
		<table id="table"></table>
		<div id="pagertrans"></div>
	</div>
	<div id="tab-2" class="the-tab">
		
		<div style="clear:both"></div>
		<table id="agent"></table>
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
			'CODE','NAME', 'CITY', 'ADDRESS', 'REMARKS','TAX ID','PENAGIHAN',
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
					dataInit: function (el) { $(el).css('text-transform', 'uppercase'); }
				},
				<?php echo 'search: true' ?>,
				stype:"text",
				searchoptions: {
					searchhidden: true,
					sopt:['cn','eq'],
          	}
			},
			{
			  name: 'agent_company_city',
			  index: 'agent_company_city',
			  align: "left",
			  width: 30,
			  editable: true,
			  edittype: "select",
			  editoptions: {
				value: ":", // titik dua = option kosong, memaksa jqGrid render <select>
				dataInit: function (elem) {
				  $(elem).select2({
					width: '100%',
					placeholder: "Pilih kota",
					allowClear: true,
					dropdownParent: $(".ui-jqdialog"), // container form jqGrid
					ajax: {
					  url: "/afg_c_stuffing_plan/fetch_dropdown_data",
					  type: "GET",
					  dataType: 'json',
					  delay: 250,
					  data: function (params) {
						return { q: params.term || "" };
					  },
					  processResults: function (data) {
						// Pastikan response dari server: [{id:1, text:"Jakarta"}, ...]
						console.log("Response Select2:", data);
						return { results: data };
					  },
					  cache: true
					},
					minimumInputLength: 1 // mulai cari setelah ketik 1 huruf
				  });
				}
			  },
			  stype: "text",
			  searchoptions: {
				searchhidden: true,
				sopt: ['cn', 'eq']
			  }
			},
			{name:'agent_company_address',index:'agent_company_address', 
				width:50, 
				editable: true,
				hidden: true, // 👈 ini menyembunyikan dari grid
				edittype:"textarea",
				editoptions:{
					cols:"40", rows:"5",
					dataInit: function (el) { $(el).css('text-transform', 'uppercase'); }
				},
				editrules: {required: false, edithidden: true},
				stype:"text",
				searchoptions: {
					searchhidden: true,
					sopt:['cn','eq'],
          	}
			},
			{name:'agent_company_remark',index:'agent_company_remark', align:"left", 
				width:50, 
				  editable: true,
					hidden: true, // 👈 ini menyembunyikan dari grid 
				edittype:"textarea",
				editoptions:{
					cols:"40", rows:"3",
				},
				editrules: {required: false, edithidden: true},
				stype:"text",
				searchoptions: {
					searchhidden: true,
					sopt:['cn','eq'],
          	}
			},
			{name:'agent_company_npwp',index:'agent_company_npwp', align:"left", width:30, 
				  editable: true,
				hidden: true, // 👈 ini menyembunyikan dari grid
				edittype:"text",
				editoptions:{
					size:"40",
					dataInit: function (el) { 
						$(el).css({'text-transform':'uppercase'}); 
					},
				},
				editrules: {required: false, edithidden: true},
				stype:"text",
				searchoptions: {
					searchhidden: true,
					sopt:['cn','eq'],
				}
			},
			{name:'penagihan',index:'penagihan', align:"center", width:20, editable: true, 
				edittype:"select", 
				editoptions:
				{
					value:"NO:NO;YES:YES",
					dataInit: function (el) 
					{ 
						$(el).css({'min-width':'30px'}); 
					},
				},
				searchoptions: {
					searchhidden: true,
					sopt:['cn','eq'],
				},
			}
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
		//caption:"Agent Company Data Table",
		editurl: ref+'/agent_company/manage_data',
		cellsubmit:'clientArray',
		pager:'#pagertrans',
				
		loadComplete: function() {
			$("#cb_"+grid[0].id).hide();
			//$(".ui-jqgrid-titlebar-close",$("#table")[0].grid.cDiv).hide();
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
	}).navGrid('#pagertrans',{ edit: true, add: true, del: true, refresh: false},
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
				},
				afterShowForm: function(formid) {
				  function waitForSelect(retry = 0) {
					const $select = $("select[name='agent_company_city']", formid);

					if ($select.length === 0) {
					  if (retry < 10) {
						setTimeout(() => waitForSelect(retry + 1), 100); // coba lagi setelah 100ms
					  } else {
						console.warn("Dropdown agent_company_city tidak ditemukan setelah 1 detik.");
					  }
					  return;
					}

					// Jika sudah ditemukan, isi dan inisialisasi Select2
					// Hapus $.ajax(...) sebelumnya, dan ganti langsung dengan ini:
					if ($select.hasClass("select2-hidden-accessible")) {
						$select.select2("destroy");
					}

					$select.select2({
						width: '100%',
						placeholder: "Pilih kota",
						allowClear: true,
						dropdownParent: $(formid), // Menjaga fokus di dalam modal jqGrid
						ajax: {
							url: "/afg_c_stuffing_plan/fetch_dropdown_data",
							dataType: 'json',
							delay: 250, // Memberi jeda 250ms setelah mengetik agar tidak membebani server
							data: function (params) {
								return {
									q: params.term // <--- INI YANG MENGIRIM PARAMETER 'q' KE CONTROLLER
								};
							},
							processResults: function (data) {
								// Select2 mengharapkan format { results: array_data }
								// Asumsi response dari controllermu adalah array json: [{id: 1, text: 'Jakarta'}, ...]
								 console.log("Response dari server:", data);
								return {
									results: data 
								};
							},
							cache: true
						},
						minimumInputLength: 3 // Opsional: Baru mulai mencari setelah mengetik 1 huruf
					});
				  }

				  waitForSelect(); // mulai polling
				}
			},
   		{
			height: "auto", width: "auto", 
   			closeAfterAdd: true, 
   			reloadAfterAdd : true, 
   			modal: true, 
   			addCaption: "Insert Data Agent Company",
   			afterSubmit: function () {
    				$('#table').jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
    				return [true];
				}
   		},
     	{
			height: "auto", 
			width: "auto",
		},
      	{modal: true, height: "auto", width: "auto", closeAfterSearch : true, multipleSearch: false,},
     	{ }
    );
};

function generateAgent() {
	var grid = $("#agent"), i;	
	$("#agent").jqGrid(
	{ 
		url: ref+"agent_company/getDataagent_penagihan",
		mtype : "post",
		datatype: "json",
		colNames:
		[
			'CODE','NAME', 'CITY', 'ADDRESS',
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
		], 
		sortname: "id",
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
		//caption:"Agent Company Data Table",
		editurl: ref+'/agent_company/manage_data',
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
		
	}).navGrid('#pageragent',{ edit: true, add: true, del: true, refresh: true},
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