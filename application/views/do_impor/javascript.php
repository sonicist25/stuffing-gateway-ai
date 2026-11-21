<?php
	$user_id = $this->session->userdata('user_id');
?>
<script src="<?php echo base_url('public/file/ajaxfileupload.js') ?>" type="text/javascript"></script>
<script type="text/javascript">
$(function () {
	var grid = $("#table-do-impor"), i;	
	grid.jqGrid({ 
		url: window.location.origin+'/afg_c_do_impor/getDataImpor',
		mtype : "post",
		datatype: "json", 
		colNames:
		[
			'Job No','BL NO','Tanggal DO','Consignee','From','Vessel', 'Container', 'Arrive Date','Warehouse','Desc Of Goods','WG','MS','No Of Pack','CON','Notes','SP',
		],
    	colModel:
		[
			{name:'job_no',index:'job_no', align:"left",width:100,
				editable: true, 
				edittype:"select", 
				editoptions:{
					dataUrl: window.location.origin+'/afg_c_do_impor/dropdownjobno',
					dataInit : function (e) { 
						$(e).css({'text-transform':'uppercase','min-width':'895px','max-width':'895px'}) 
					},
					dataEvents: [
                  {  type: 'change',
                  	fn: function(e) {
                       	dropdown_job_number(this.value)
                      }
                   }
                ]
				},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],  
				}
			},	
			{name:'bl_no',index:'bl_no', align:"center",width:80, editable: true, 
				editoptions: {
					dataInit: function (e) { 
						$(e).css({'text-transform':'uppercase','min-width':'895px','max-width':'895px'}) 
					}
				},
				editrules:{
					edithidden:true
				},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],  
				}
			},				
			{name:'tanggal_do',index:'tanggal_do', align:"left", width:70, align:"center",
				editable: true,
				editoptions: {
      			maxlengh: 10,
      			dataInit: function(e) {
        				$(e).datepicker({dateFormat: 'dd/mm/yy'})
        				$(e).css({'text-transform':'uppercase','min-width':'895px','max-width':'895px'}) 
      			}
    			},
    			stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],  
				}
			},
			{name:'consignee',index:'consignee', align:"left",width:200, editable: true, 
				edittype:"textarea",
				editoptions: {  
					rows:"6",
					dataInit: function (e) { 
						$(e).css({'text-transform':'uppercase','min-width':'895px','max-width':'895px'}) 
					}
				},
				cellattr: function(rowId, cellValue, rawObject, cm, rdata) {
					//return 'title="'+cellValue+rawObject[15]+'"';
				},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],  
				}
			},
			{name:'from',index:'from', align:"left",width:100, editable: true, 
				editoptions: {
					dataInit: function (e) { 
						$(e).css({'text-transform':'uppercase','min-width':'895px','max-width':'895px'}) 
					}
				},
				editrules:{
					edithidden:true
				},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],  
				}
			},
			{name:'vessel',index:'vessel', align:"left",width:120, editable: true, 
				editoptions: {
					dataInit: function (e) { 
						$(e).css({'text-transform':'uppercase','min-width':'895px','max-width':'895px'}) 
					}
				},
				editrules:{
					edithidden:true
				},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],  
				}
			},
			{name:'containeer',index:'containeer', align:"left",width:140, editable: true, 
				editoptions: {
					dataInit: function(e) {
        				$(e).css({'text-transform':'uppercase','min-width':'895px','max-width':'895px'}) 
      			}
				},
				editrules:{
					edithidden:true
				},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],  
				}
			},
			{name:'arrive_date',index:'arrive_date', align:"center",width:70, editable: true, hidden:true, 
				editrules:{
					edithidden:true,
				},
				editoptions: {
      			dataInit: function(e) {
        				$(e).datepicker({dateFormat: 'dd/mm/yy'})
        				$(e).css({'text-transform':'uppercase','min-width':'895px','max-width':'895px'}) 
      			}
    			},
    			stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],  
				}
			},
			{name:'warehouse',index:'warehouse', align:"left",width:100, editable: true, hidden:false, 
				edittype:"select", 
				editoptions: {
					dataUrl: window.location.origin+'/afg_c_dropdown/dropdownWarehouse',
					dataInit: function(e) {
        				$(e).css({'text-transform':'uppercase','min-width':'895px','max-width':'895px'}) 
      			}
				},
				editrules:{
					edithidden:true,
				},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],  
				}
			},
			{name:'description',index:'description', align:"left",width:100, editable: true, hidden:true,
				edittype:"textarea",
				editoptions: {
					rows:"6",
					dataInit: function(e) {
        				$(e).css({'text-transform':'uppercase','min-width':'895px','max-width':'895px'}) 
      			}
				},
				editrules:{
					edithidden:true
				}
			},
			{name:'wg',index:'wg', align:"left",width:100, editable: true, hidden:true,
				editoptions: {
					dataInit: function(e) {
        				$(e).css({'text-transform':'uppercase','min-width':'895px','max-width':'895px'}) 
      			}
				},
				editrules:{
					edithidden:true
				}
			},

			{name:'ms',index:'ms', align:"left",width:100, editable: true, hidden:false,
				editoptions: {
					dataInit: function(e) {
        				$(e).css({'text-transform':'uppercase','min-width':'895px','max-width':'895px'}) 
      			}
				},
				editrules:{
					edithidden:true
				}
			},
			{name:'no_of_pack',index:'no_pack_pack', align:"left",width:100, editable: true, hidden:false,
				editoptions: {
					dataInit: function(e) {
						$(e).css({'text-transform':'uppercase','min-width':'895px','max-width':'895px'})
      			}
				},
				editrules:{
					edithidden:true
				}
			},
			{name:'con',index:'con', align:"left",width:100, editable: false, hidden:true,},
			//{name:'bl_no',index:'bl_no', align:"center",width:80, editable: true, hidden:true,},
			{name:'notes',index:'notes', align:"left",width:100, editable: true, hidden:true,
				edittype:"textarea",
				editoptions: {
					rows:"6",
					dataInit: function(e) {
        				$(e).css({'text-transform':'uppercase','min-width':'895px','max-width':'895px'}) 
      			}
				},
				editrules:{
					edithidden:true
				}
			},
			{name:'sp_detail_id',index:'sp_detail_id', align:"left",width:100, editable: true, hidden:true,},
			
		], 
		sortname: "do_id",
		sortorder: "desc",
		shrinkToFit: false,
  		multiselect:true,
		loadonce : false,
		cellEdit: false,
		rowNum:50,
		width: 845,
		height: 390,
		rowList:[50,100,150,200,250],
		viewrecords: true,
		rownumbers: true,
		gridview: true,
		modal: true,
		pager:'#page-do-impor',
		editurl:window.location.origin+'/afg_c_do_impor/manage_data',
		caption:"Tabel DO Import",		
		loadComplete: function() {
			$("#cb_"+grid[0].id).hide();
			$(".ui-jqgrid-titlebar-close",$("#table-do-impor")[0].grid.cDiv).hide();
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
			var kode_ref = grid.jqGrid('getCell',rowid,'job_no');	
			selected[rowid] = status;
			if(status)
			{ 
				var cbs = $("tr.jqgrow > td > input.cbox", grid[0]);  			
    			cbs.attr("disabled", true);  
    			selected.attr("disabled", false);  
    			$( '#btnPrint' ).attr('onClick','print_import('+rowid+')');	
    			$( '#btnPrint' ).css('background-color','#999');
    			$('input[id="job_no_del"]').val(kode_ref);
     		}
        	else
        	{
        		var cbs = $("tr.jqgrow > td > input.cbox:disabled", grid[0]);  		
    			cbs.attr("disabled", false);
    			$( '#btnPrint' ).removeAttr('onClick');	
    			$( '#btnPrint' ).css('background-color','');  
    			$('input[id="job_no_del"]').val('');    			
        	}
		},
		
	}).navGrid('#page-do-impor',{ edit: true, add: true, del: true,},
			{
				height: "auto", 
				width: "auto",  
				closeAfterEdit: true, 
				reloadAfterEdit : true, 
				modal: true, 
				editCaption: "Edit Data Import", 
				recreateForm: true,
				viewPagerButtons: false,
				onInitializeForm: function(formId) {
					$(".FormGrid select[name='opt_job_no']").attr('disabled',true)
				},
				beforeShowForm: function(frm) {
					$("#opt_job").attr('disabled',true);	
					var dlgDiv = $("#editmod" + grid[0].id);
					dlgDiv[0].style.top = 30 + "px";
               dlgDiv[0].style.left = Math.round($(window).width()/6.5) + "px";
					var rowid = grid.getGridParam('selrow');
					var job = grid.jqGrid('getCell',rowid,'job_no');
					$("tr#tr_job_no > td.DataTD").html("<select id=\"job_no\" style=\"min-width: 895px\" disabled=\"disabled\"><option value="+job+">"+job+"</option></select>")
					
					var con = grid.jqGrid('getCell', rowid,'consignee');
					var con_add = grid.jqGrid('getCell', rowid,'con');
					$("textarea[name=\"consignee\"]").val(con+con_add)
				},
				beforeSubmit: function (postData) {
					postData['job_no']=$("select#job_no").val();
    				return [true];
				},
				afterSubmit: function () {
    				$("#table-do-impor").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
    				return [true];
				},
			},
   		{
   			height: "auto", 
   			width: "auto", 
   			closeAfterAdd: true, 
   			reloadAfterAdd : true, 
   			modal: true, 
   			addCaption: "Insert Data Import", 
				recreateForm: true,
				viewPagerButtons: true,
				beforeShowForm: function(frm) {
					var dlgDiv = $("#editmod" + grid[0].id);
               dlgDiv[0].style.top = 30 + "px";
               dlgDiv[0].style.left = Math.round($(window).width()/6.5) + "px";	
				},
				afterSubmit: function () {
    				$("#table-do-impor").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
    				return [true];
				},
   		},
     		{
     			height: "auto", width: "auto",
     			serializeDelData: function (postdata) {
     				return {
    					id: postdata.id, 
    					oper: postdata.oper, 
    					kode_ref : $('input[id="job_no_del"]').val(),
    				};
				},
     			afterSubmit: function () {
    				$("#table-do-impor").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
    				return [true];
				},
     		},
      	{
      		modal: true, 
      		height: "auto", 
      		width: "auto", 
      		closeAfterSearch : true, 
      		multipleSearch: false
      	},
     		{ 
     			afterSubmit: function () {
    				$("#table-do-impor").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
    				return [true];
				},
     		}
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
   		caption:"Search Data DO",
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
     
function dropdown_job_number(job_no) {
	$.ajax
	({
		type:"POST",
		url:window.location.origin+'/afg_c_do_impor/getDataImporByJobNo',
		data: { no_job : job_no },
		success:function(response)
		{
			var data = $.parseJSON(response);
			if(data.shipper_import != ''){
					get_code_shipper_import(data.shipper_import,data.shipper_import_add)
			}
			$('#do_id').val(data.do_id);
			$('#bl_no').val(data.hb_l);
			$('#tanggal_do').val(data.tanggal_do).attr('disabled',false);
			$('#consignee').val(data.shipper_import+'\n'+data.shipper_import_add).attr('disabled',false);
			$('#from').val(data.port_loading).attr('disabled',false);
			$('#vessel').val(data.vessel).attr('disabled',false);
			$('#arrive_date').val(data.arrive_date).attr('disabled',false);
			$('#containeer').val(data.cont_seal_no).attr('disabled',false);
			$('#warehouse').val(data.warehouse).attr('disabled',false);
			$('#notes').val(data.shipping_mark);
			$('#description').val(data.desc_goods);
			$('#wg').val(data.weight);
			$('#ms').val(data.ship_meas);
			$('#sp_detail_id').val(data.sp_detail_id);
			$('#no_of_pack').val(data.no_pack_qty+" "+data.no_pack_unit);
			$('#mode').val('add');
		}
	})
}
	
function get_code_shipper_import()
{
	var code = arguments[0].toUpperCase().split(" ",5);
	if (code.length <= 1) {
		code = ["PT.",code]
	}
	
	if (code[0].substring(0,2)=="PT") {
		var code_cs = code[1];
	}
	else if (code[0].substring(0,3)=="PT.") {
		var code_cs = code[1];
	}  
	else if (code[0].substring(0,3)=="PT ") {
		var code_cs = code[1];
	}  
	else if (code[0].substring(0,3)=="PT,") {
		var code_cs = code[1];
	} 
	else 
	{
		var code_cs = code[0];
	}
	
	var Data =
	{
		job			: $("select[id='job_no']").val(),
		code_cs		: code_cs,
		nama_cs 	: arguments[0].toUpperCase(),
		alamat_cs 	: arguments[1].toUpperCase(),
	}
	
	$.ajax ({
		type:"POST",
		url:window.location.origin+'/afg_c_do_impor/get_code_shipper_import',
		data: Data,
		success:function(response)
		{
			var data = $.parseJSON(response);
			if(data.status == 0 ){
			save_consignee(job,code_cs,nama_cs,alamat_cs);	
			} else {
				return true;
			}
		}
	})
}

function save_consignee()
{
	var DataSave =
	{
		job							: job,
		consignee_import_code		: code_cs,
		consignee_import_name 		: nama_cs,
		consignee_import_address 	: alamat_cs,
	}
	$.ajax
	({
		type:"POST",
		url:window.location.origin+'/afg_c_do_impor/insert_consignee',
		data: DataSave,
		success:function(response)
		{
			})
		}
	})
}

function print_import(N)
{
	$( "#Output-Import" ).dialog(
	{
		position:'top',
		autoOpen: true,
		resizable: false,
		height:650,
		width: 980,
		modal: true,
		title:"DELIVERY ORDER",
		buttons: { },
		close: function() { }
	});
	$("#Output-Import").html('<center><iframe src="'+window.location.origin+'/afg_c_do_impor/print_out/'+N+'" width=" 955px" height="600"></iframe></center>')

}

function show_file() {
	var grid = $("#tabel"), i;	
   grid.jqGrid({
		url: window.location.origin+'/afg_c_do_impor/show_file', 
		mtype : "post",  
		datatype: "json", 
		colNames:
		[ 
			'Pilih File',
			'Keterangan',
			'Upload by',
			'Tgl Upload',	
			'',		
		],
		colModel:
		[
			{name:'nama_file',index:'nama_file', width:40, align:"center", 
				editable: true, 
				hidden: true,
				edittype:"file",
				editrules:{
					edithidden:true
				},
				editoptions:{
					title:"Upload File",
					dataInit: function (elm) {
						$(elm).css({
							'cursor':'pointer',
							'border':'none',
							'width':'300px',
							'font-size':'9px'
						})
					},
					enctype: "multipart/form-data",
				},
			},
			{name:'file_name',index:'file_name', width:250, align:"left",
				editable: true,
				editoptions:{
					size: "29",
					dataInit: function(element) {
						
      			}
				},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'upload_by',index:'upload_by', width:100, align:"left", 
				stype:"select",
				searchoptions: {
					sopt:['eq'],
					dataUrl: window.location.origin+'/dropdown/dropdown_user/'+false,
               buildSelect:function(response){
               	var obj = $.parseJSON(response);
               	return obj;
          		}
          	}
			},
			{name:'tgl_upload',index:'tgl_upload', width:70, align:"center", 
				editable: true,
				editoptions:{
					size: "29",
					dataInit: function(element) {
        				$(element).datepicker({dateFormat : "dd/mm/yy"})
      			}
				},
				stype:"text",
				searchoptions:{ 
					sopt:['eq','lt','gt'],
					dataInit: function(element) {
        				$(element).datepicker({dateFormat : "dd/mm/yy"});
      			}  
				}
			},
			{name:'file',index:'file', width:100, align:"center", hidden: true}
		],
		sortname: "id",
		sortorder: "desc",
		shrinkToFit: true,
		multiselect:true,
		loadonce : false,
		cellEdit: false,
		rowNum:10,
		width: 500,
		height: 222,
		rowList:[10,20,30],
		viewrecords: true,
		rownumbers: true,
		gridview: true,
		modal: true,
		pager: '#page-upload',
//		caption:"DATA FILE UPLOAD",
		editurl: window.location.origin+'/afg_c_do_impor/manage_file',
						
			loadComplete: function(data) {
				$("#cb_"+grid[0].id).hide();
				$(".ui-jqgrid-titlebar-close",$("#tabel")[0].grid.cDiv).hide();
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
					var user_id = grid.jqGrid('getCell',rowid,'upload_by');
					var cbs = $("tr.jqgrow > td > input.cbox", grid[0]);  			
					var last_id = $("#last_id").val(); 
					cbs.attr("disabled", true);  
    				selected.attr("disabled", false);
    				var file = grid.jqGrid('getCell',rowid,'file');
    				$('#filenya').val(file);
					get_nomer_master(rowid);
    			}
        		else
        		{
        			var cbs = $("tr.jqgrow > td > input.cbox:disabled", grid[0]);  		
    				cbs.attr("disabled", false); 
    				$('#dropdown_m').html('<select id="dropdown_no_master" style="min-width: 163px" onclick="show_messages(\'Silahkan pilih file .mdb terlebih dulu\')"><option></option></select>');		
        		}
			},	
			ondblClickRow: function(rowid){	
				
			},			
		}).navGrid('#page-upload', { edit: true, del: true, add: true, refresh: false },
		{
				height: "auto", 
				width: "auto", 	
				closeAfterEdit: true, 
				reloadAfterEdit : true, 
				modal: true, 
				editCaption: "Edit Data",
				recreateForm: false,
				viewPagerButtons: false,
				beforeShowForm: function(frm) {
					$('#akses_file').val('Pilih User')
				}, 
				beforeSubmit:function () {
					
					var input, file;
    				input = document.getElementById('nama_file');
    				file = input.files[0];
    				
    				if($('#file_name').val()=='') {
    					$('#file_name').focus();
    					return [false, 'Nama file tidak boleh dikosongkan']
    				}
    				else if($('#upload_date').val()=='') {
    					$('#upload_date').focus();
    					return [false, 'Tanggal upload tidak boleh dikosongkan']
    				}
    				else if($('#user_akses').val()=='') {
    					$('#akses_file').focus();
    					return [false, 'Akses File tidak boleh dikosongkan']
    				}
    				else {
    					if(!file){
    						return [true, '']
    					} else if(file && file.size > 50000000){
    						return [false, 'Batas max file size 50 Mb, File anda terlalu besar']
    					} else {
    						$('#nf').val(file.name);
    						$('#fs').val(file.size);
    						return [true,'']
    					}
    				}
				},
				afterSubmit:function (response) {
					var data = $.parseJSON(response.responseText);
					if($("#nama_file").val() != ""){
						ajaxFileUpload(data.id,'edit');
					}
					return [data.success,""];
				}    		
		},			
   	{
   			height: "auto", 
   			width: "auto",  
   			closeAfterAdd: true, 
   			reloadAfterAdd : true, 
   			modal: true, 
   			addCaption: "Add File",
   			beforeShowForm: function(frm) {

				}, 
				beforeSubmit:function (frm) {
					var input, file;
    				input = document.getElementById('nama_file');
    				file = input.files[0];
    				
    				if(!file){
    					input.focus();
    					return [false, 'Silahkan pilih file sebelum Submit']
    				}
    				else if($('#file_name').val()=='') {
    					$('#file_name').focus();
    					return [false, 'Keterangan tidak boleh dikosongkan']
    				}
    				else if($('#tgl_upload').val()=='') {
    					$('#upload_date').focus();
    					return [false, 'Tanggal upload tidak boleh dikosongkan']
    				}
    				else 
    				{
    					var file_ext = file.name.split('.');
    					var n = file_ext.length-1;
    					
    					if (file.size > 50000000) return [false,'batas max file size 50 Mb.'];
    					else if (file_ext.length==1) return [false,'extentention file tdk terbaca, set file name : *.mdb'];
    					else if (file_ext[n].toLowerCase() != 'mdb') return [false,'extentention harus type .mdb'];
						else return [true,''];
    					
    				}
				},
				afterSubmit: function(response,postData) {
					var input, file;
    				input = document.getElementById('nama_file');
    				file = input.files[0];
    				var data = $.parseJSON(response.responseText);
    				var id = data.id;
    				var oper = data.oper;
					ajaxFileUpload(id,oper,file);
					return [true,""];
				}  		
   	},
     	{
     			height: "auto", width: "auto",
     			afterSubmit: function () {
					grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
					return [true];
				}
     	},
      {
      		modal: true, 
      		height: "auto", 
      		width: "auto",
      		multipleSearch : false, 
      		closeAfterSearch : true, 
      		caption: "Find Data File",
      },
     	{ }
	)
}

function ajaxFileUpload(id,oper,file) {
	/*
	$('#progress').dialog({
		modal: true,
		autoOpen: true,
		resizable: false,
		width: 380,
		height: 105,
		position:['center',50],
		close: function () {
			$(this).html('<font style="color: #F33">File Unable to Upload</font></br><font>Name : ' + file.name +
					'<br>Size : '+ file.size +' byte</font><br><img src="'+window.location.origin+'/public/images/progress_bar_done.gif" height="13px" width="370px" style="margin-top: 5px">')
			//$(".ui-dialog-titlebar").show();
		},
		open: function () {
			$(this).html('<font style="color: #33f">Progress Upload ....</font></br><font>Name : '+ file.name +
				'<br>Size : ' + file.size +
				' byte</font><br><img src="'+window.location.origin+'/public/images/progressbar.gif" height="13px" width="370px" style="margin-top: 5px">')
			//$(".ui-dialog-titlebar").hide();
		}
	});
	*/
	
	show_messages_upload('upload succes');
	return;
	
	var post = {};
	post.id = id;
	post.oper = oper;
	$.ajaxFileUpload ({
		url: window.location.origin+'/afg_c_do_impor/ajaxFileUpload',
		secureuri:false,
		fileElementId:'nama_file',
		type: 'POST',
		dataType: 'json',
		data: post,
		success: function (data)
		{
			if(data.error != '') {
				show_messages_upload(data.error)
				return
			} else {
				show_messages_upload('upload succes')
			}
			show_messages_upload('upload succes')
		},
		error: function (data, status, e)
		{
			$('#progress').html(show_messages(e));
		}
	})
}

show_messages_upload=function (chr) {
	$('#msg-upload').fadeIn('slow', function(){
		$('#msg-upload').html(chr.toUpperCase()).css({'position':'absolute','top':'10px','text-align':'right','color':'red','font-size':'1.5em'})
	}).delay(3000).fadeOut('slow',function () {
		$('#msg-upload').html('')
	})
}

function upload_file()
{
	$("#form-upload").dialog({
		position:'center',
		autoOpen: true,
		resizable: false,
		height:650,
		width: 980,
		modal: true,
		title:"MANIFEST DATA INSERT",
		buttons: { },
		close: function() { }
	}).html('<div id="msg-upload"></div><div id="div-file-1" class="the-tab" style="float: left; width: 400px">'+
		'<table id="tabel"></table>'+
		'<div id="page-upload"></div>'+
		'</div>'+
		'<div id="div-file-2" class="ui-corner-all ui-widget" style="float: right; width: 390px; border: 1px solid #999; min-height: 230px; padding: 15px; font-size: 11px">'+
		'<table>'+
			'<tr>'+
				'<td width="100" height="30">NO Master BL </td>'+
				'<td width="30" align="center">:</td>'+
				'<td id="dropdown_m">'+
					'<select id="dropdown_no_master" style="min-width: 163px" onclick="show_messages(\'Silahkan pilih file .mdb terlebih dulu\')">'+
						'<option></option>'+
					'</select>'+
				'</td>'+
				'<td rowspan="8" align="right" width="100">'+
					'<input type="button" name="btnInject" value="INJECT" id="btnInject" class="custom" onclick="inject_db()" style="width: 70; height: 180; margin-top: 30"  />'+
					'<input type="hidden" name="nodaftar" value="" id="nodaftar"  />'+
					'<input type="hidden" name="filenya" value="" id="filenya"  />'+
					'<input type="hidden" name="cont_seal" value="" id="cont_seal"  />'+
					'<input type="hidden" name="port_loading" value="" id="port_loading"  />'+
					'<input type="hidden" name="transhipment_port" value="" id="transhipment_port"  />'+
					'<input type="hidden" name="type_cont_qty" value="" id="type_cont_qty"  />'+
					'<input type="hidden" name="type_cont_size" value="" id="type_cont_size"  />'+
				'</td>'+
			'</tr>'+
			'<tr><td width="100" height="30">Tgl Master BL </td><td width="30" align="center">:</td><td><input type="text" value="" id="tgl_master"  /></td></tr>'+
			'<tr><td width="100" height="30">Manifest </td><td width="30" align="center">:</td><td><select id="jnsmanifest" style="min-width: 163px"><option></option><option value="Inward">Inward</option><option value="Outward">Outward</option></select></td></tr>'+
			'<tr><td width="100" height="30">Group </td><td width="30" align="center">:</td><td id="kdgrup"><select id="dropdown_grup" style="min-width: 163px"><option></option></select></td></tr>'+
			'<tr><td width="100" height="30">Kapal </td><td width="30" align="center">:</td><td><input type="text" value="" id="vessel"  /></td></tr>'+
			'<tr><td width="100" height="30">No Voyage </td><td width="30" align="center">:</td><td><input type="text" value="" id="voyage"  /></td></tr>'+
			'<tr><td width="100" height="30">Tgl Tiba </td><td width="30" align="center">:</td><td><input type="text" value="" id="arrivingdate"  /></td></tr>'+
			'<tr><td width="100" height="30">Jam Tiba </td><td width="30" align="center">:</td><td><input type="text" value="" id="arrivingtime"  /></td></tr>'+
		'</table>'+	
		'</div>'+
		'<div style="clear:both; height: 20px;"></div>'+
		'<div id="div-file-3" class="the-tab">'+
		'<table id="tabel_detail"></table>'+
		'<div id="page_detail"></div>'+
		'</div>'+
		'<div id="content_result"></div>');
	$("#tabel").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");	
	show_file()
	get_data_detail()
}

function get_nomer_master(id) {
	$.ajax
	({
		type: "POST",
		url : "<?= base_url('afg_c_do_impor/get_nomer_master') ?>",
		data: { file: $('input[id="filenya"]').val()},
		success: function(response) 
		{
			var data = $.parseJSON(response);
			$('#dropdown_m').html(data.dropdown);
		}
	})
}

function get_data_header()
{
	var val = $("#dropdown_no_master").val();
	var file = $("#filenya").val();
	$.ajax
	({
		type: "POST",
		url : "<?= base_url('afg_c_do_impor/get_data_header') ?>",
		data: { val: val,file: file},
		success: function(response) 
		{
			var data = $.parseJSON(response);
			$('#tgl_master').val(data.tgl_master);
			$('select#jnsmanifest').val(data.jnsmanifest);
			$('#kdgrup').html(data.kdgrup);
			$('#vessel').val(data.vessel);
			$('#voyage').val(data.voyage);
			$('#arrivingdate').val(data.arrivingdate);
			$('#arrivingtime').val(data.arrivingtime);
			$('#nodaftar').val(data.nodaftar);
			$('#cont_seal').val(data.cont_seal);
			$("#tabel_detail").jqGrid('setGridParam',{datatype: 'json', postData:{nodaftar: data.nodaftar, file: $("#filenya").val()}}).trigger("reloadGrid");
			get_data_detail()
		}
	})
}

function get_data_detail() {
	var nodaftar = $("#nodaftar").val();
	var file = $("#filenya").val();
	var grid = $("#tabel_detail"), i;	
   grid.jqGrid(
	{
		url:"<?= base_url('afg_c_do_impor/get_data_detail')?>", 
		mtype : "post",  
		datatype: "json", 
		colNames:
		[
			'No Pos', 
			'No BL',
			'Mother Vessel',
			'Tanggal B/L',
			'Pelabuhan Asal',	
			'Sebelumnya',
			'Bongkar',
			'Akhir',
			'Shipper',
			'Shipper Adress',
			'Consignee',
			'Consignee Adress',
			'Notify',
			'Notify Adress',
			'Mark',
			'Bruto',
			'Volume',
			'Jml Kms',
			'Satuan',
			'Cont Qty',
			'No Cont.',
			'Size',
			'Type',
			'Seal',			
			'Desc of Good',	
		],
		colModel:
		[
			{name:'25',index:'25', width:50, align:"center",
				editable: true,
				editoptions:{
					size: "30", 
					dataInit: function(element) {
						
      			}
				},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'1',index:'1', width:100, align:"left",
				editable: true,
				editoptions:{
					size: "30", 
					dataInit: function(element) {
						
      			}
				},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'2',index:'2', width:115, align:"left",
				editable: true,
				editoptions:{
					size: "30", 
					dataInit: function(element) {
						
      			}
				},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'3',index:'3', width:115, align:"center", 
				editable: true,
				editoptions:{
					size: "30", 
					dataInit: function(element) {
						
      			}
				},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'4',index:'4', width:100, align:"left", 
				editable: true,
				editoptions:{
					size: "30", 
					dataInit: function(element) {
						
      			}
				},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'5',index:'5', width:100, align:"left", 
				editable: true,
				editoptions:{
					size: "30", 
					dataInit: function(element) {
						
      			}
				},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'6',index:'6', width:100, align:"left", 
				editable: true,
				editoptions:{
					size: "30", 
					dataInit: function(element) {
						
      			}
				},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'7',index:'7', width:100, align:"left", 
				editable: true,
				editoptions:{
					size: "30", 
					dataInit: function(element) {
						
      			}
				},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'8',index:'8', width:220, align:"left", 
				editable: true,
				editoptions:{
					size: "30", 
					dataInit: function(element) {
						
      			}
				},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'9',index:'9', width:220, align:"left", 
				editable: true,
				edittype:"textarea",
				editoptions:{
					cols:"31",
					rows:"2",
				},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'10',index:'10', width:220, align:"left", 
				editable: true,
				editoptions:{
					size: "30", 
					dataInit: function(element) {
						
      			}
				},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'11',index:'11', width:220, align:"left", 
				editable: true,
				edittype:"textarea",
				editoptions:{
					cols:"31",
					rows:"2",
				},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'12',index:'12', width:220, align:"left", 
				editable: true,
				editoptions:{
					size: "30", 
					dataInit: function(element) {
						
      			}
				},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'13',index:'13', width:220, align:"left", 
				editable: true,
				edittype:"textarea",
				editoptions:{
					cols:"31",
					rows:"2",
				},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'14',index:'14', width:220, align:"left", 
				editable: true,
				edittype:"textarea",
				editoptions:{
					cols:"31",
					rows:"2",
				},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'15',index:'15', width:50, align:"right", 
				editable: true,
				editoptions:{
					size: "30", 
					dataInit: function(element) {
						
      			}
				},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'16',index:'16', width:50, align:"right", 
				editable: true,
				editoptions:{
					size: "30", 
					dataInit: function(element) {
						
      			}
				},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'17',index:'17', width:50, align:"center", 
				editable: true,
				editoptions:{
					size: "30", 
					dataInit: function(element) {
						
      			}
				},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'18',index:'18', width:50, align:"center",
				editable: true,
				editoptions:{
					size: "30", 
					dataInit: function(element) {
						
      			}
				},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'19',index:'19', width:55, align:"center",
				editable: true,
				editoptions:{
					size: "30", 
					dataInit: function(element) {
						
      			}
				},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'20',index:'20', width:80, align:"center",
				editable: true,
				editoptions:{
					size: "30", 
					dataInit: function(element) {
						
      			}
				},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'21',index:'21', width:50, align:"center",
				editable: true,
				editoptions:{
					size: "30", 
					dataInit: function(element) {
						
      			}
				},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'22',index:'22', width:50, align:"center",
				editable: true,
				editoptions:{
					size: "30", 
					dataInit: function(element) {
						
      			}
				},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'23',index:'23', width:80, align:"left",
				editable: true,
				editoptions:{
					size: "30", 
					dataInit: function(element) {
						
      			}
				},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'24',index:'24', width:200, align:"left",
				editable: true,
				editoptions:{
					size: "30", 
					dataInit: function(element) {
						
      			}
				},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
		],
		sortname: "id",
		sortorder: "desc",
		shrinkToFit: false,
		multiselect:true,
		loadonce : true,
		cellEdit: false,
		rowNum:50,
		width: 950,
		height: 250,
		rowList:[10,20,30],
		viewrecords: true,
		rownumbers: true,
		gridview: true,
		modal: true,
		pager: '#page_detail',
		editUrl: 'clientArray',
      cellSubmit: 'clientArray',
//		caption:"DATA FILE UPLOAD",
						
			loadComplete: function(data) {
				$("#cb_"+grid[0].id).hide();
				$(".ui-jqgrid-titlebar-close",$("#tabel")[0].grid.cDiv).hide();
			},
			
			gridComplete: function(data) {
				var ids = grid.jqGrid('getDataIDs');
				for (var i = 0; i < ids.length; i++) {
   			 	var rowId = ids[i];
    				var rowData = grid.jqGrid ('getRowData', rowId);
    			//	console.log(rowData.Phrase);
    			//	console.log(rowId);
    			// var port_loading = grid.jqGrid ('getRowData', rowId);
    			// var transhipment_port = grid.jqGrid ('getRowData', rowId);
    			$('#port_loading').val(grid.jqGrid('getCell',ids[1],'6'));
				$('#transhipment_port').val(grid.jqGrid('getCell',ids[1],'7'));
				$('#type_cont_qty').val(grid.jqGrid('getCell',ids[1],'21'));
				$('#type_cont_size').val(grid.jqGrid('getCell',ids[1],'23')+grid.jqGrid('getCell',ids[1],'24'));
				}
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
					var user_id = grid.jqGrid('getCell',rowid,'upload_by');
					var cbs = $("tr.jqgrow > td > input.cbox", grid[0]);  			
					var last_id = $("#last_id").val(); 
					cbs.attr("disabled", true);  
    				selected.attr("disabled", false);

    			}
        		else
        		{
        			var cbs = $("tr.jqgrow > td > input.cbox:disabled", grid[0]);  		
    				cbs.attr("disabled", false); 		
        		}
			},	
			ondblClickRow: function(rowid){	
				
			},			
		}).navGrid('#page_detail', { edit: false, del: false, add: false, refresh: false },
		{ },			
   	{ },
     	{ },
      {
      		modal: true, 
      		height: "auto", 
      		width: "auto",
      		multipleSearch : false, 
      		closeAfterSearch : true, 
      		caption: "Find Data File",
      },
     	{ }
	)
}

function inject_db() {
	var allData = 
	{
    	gridData: $("#tabel_detail").jqGrid('getGridParam','data'),
    	formData: $("#tabel_detail").serialize()
	};
	var data_post = 
	{
		mother_bl : $('#dropdown_no_master').val(),
		vessel : $('#vessel').val(),
		voyage : $('#voyage').val(),
		etd_jkt : $('#arrivingdate').val().replace(/\-/g,'').substr(0,8),
		closing_date_time : $('#tgl_master').val().replace(/\-/g,'').substr(0,8),
		cont_seal_no : $('#cont_seal').val(),
		port_loading : $('#port_loading').val(),
		transhipment_port : $('#transhipment_port').val(),
		type_cont_qty : $('#type_cont_qty').val(),
		type_cont_size : $('#type_cont_size').val(),
		destination : 'TANJUNG PRIOK',
		prepared : '<?= $this->session->userdata("user_id") ?>',
		bulan_job : $('#arrivingdate').val().replace(/\-/g,'').substr(5,6),
		detail : $("#tabel_detail").jqGrid('getGridParam','data'),
	}
	
	if($('#dropdown_no_master').val()==''){
		show_messages("Silahkan pilih No Master BL terlebih dulu")
		return false;
	}
	
	$.ajax
	({
		type: "POST",
		url : "<?= base_url('afg_c_do_impor/inject_db') ?>",
		data: data_post,
		success: function(response) 
		{
			var data = $.parseJSON(response);
			if(data.status==1){
				show_messages('Data stuffing create '+data.kode_ref)
			}
		}
	})
}



</script>