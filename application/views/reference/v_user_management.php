<div id="Menu-User"></div>
<div id="Form-Input-User"></div>
<div id="Form-Delete-User"></div>
<div id="Access-User"></div>
<div style="height: 30px;"></div>

<div id="tabs">
	<ul id="tabs-menu">
	<?php 
		$user_level = $this->session->userdata('level_id');
		if($user_level == 0 || $user_level == 1 || $user_level == 2|| $user_level == 4) {
	?>
		<li><a href="#tab-1">CUSTOMERS LIST</a></li>
		<li><a href="#tab-2">USER ADMIN</a></li>
		<?php } ?>
	</ul>
	<?php 
		$user_id = $this->session->userdata('user_id');
		$region_id = $this->session->userdata('region_id');
	if($region_id != 'Jakarta'){
	?>
	<div id="tab-1" class="the-tab">
		<center>
		<table id="tabel_user_booking"></table>
		<div id="pagination_user"></div>
		</center>
	</div>
	<?php
	}else{
		?>
	<div id="tab-1" class="the-tab">
		<center>
		<table id="tabel_user_booking"></table>
		<div id="pagination_user"></div>
		</center>
	</div>
	<?php }?>
	<div id="tab-2" class="the-tab">
		<div id="topbutton-user" style="float: right; margin-bottom: 20; margin-right: 170px; font-size: 11px;">
			<?php 
			if($user_level == 0 || $user_level == 1) { ?>
			<input type="button" id="btnNewUser" value="NEW" style="width: 80; height: 25" onclick="new_admin()">
			<?php } ?>
			<input type="button" id="btnEditUser" value="EDIT" style="width: 80; height: 25">
			<?php if($user_level == 0 || $user_level == 1) { ?>
			<!--
			<input type="button" id="btnCopyUser" value="COPY" style="width: 80; height: 25">
			-->
			<input type="button" id="btnDeleteUser" value="DELETE" style="width: 80; height: 25">
			<?php } ?>
		</div>
		<div style="clear:both"></div>
		<center>
			<table id="user-admin"></table>
			<div id="page-user-admin"></div>
		</center>		
   </div>
   <!--/end TAB1-->	
</div>

<div id="form-input"></div>

<div id="Insert-Form"></div>

<script type="text/javascript">
$(document).ready(function()
{
	load_file_head('css',tmppub+'css/ref_user_management.css?v='+time)
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
	generateTabelAdmin();
	
	$(".ui-jqgrid-titlebar-close",$("#tabel_user_booking")[0].grid.cDiv).hide();
	$(".ui-jqgrid-titlebar-close",$("#user-admin")[0].grid.cDiv).hide();
	
});
	
function generateTabel()
{
	var grid = $("#tabel_user_booking"), i;
	$("#tabel_user_booking").GridUnload();
	$("#tabel_user_booking").jqGrid(
	{ 
		url:"<?php echo base_url('reference/c_user_management/getDataUserBooking')?>", 
		mtype : "post", 
		datatype: "json",
		sortname: "id_shipper",
		sortorder: "desc",
		ajaxGridOptions: {cache: false},
		colNames:['Upload Registration Form','Upload CCF Form','Upload NPWP','is Consignee Import?','Username','Password','Shipper Name','Address','NPWP Name','NPWP Number', 'NPWP Address','NIB','Contact Person','Phone','Emails (comma-separated)','Region Id', 'E-Service', 'Sales Person','','','Created By','Created Date','','Accounts','Term of Payment', 'Customer Type'],
    	colModel:
		[
			{
			name: 'upload_file',
			index: 'upload_file',
			width: 200,
			editable: true,
			formoptions: { rowpos: 1, colpos: 1 },
			edittype: 'file',  // Define this field as a file input
			editoptions: {
				title: 'Select a file',
			accept: '.pdf',  // Specify acceptable file types if needed
			dataInit: function (element) {
            $(element).on("change", function () {
                uploadAndProcessCRF(element);
            });
			}
			},
			editrules: { edithidden: true },  // Optional: show only in Add/Edit dialogs
			hidden: true,  // Hide in grid view but show in the form
			},
			{
			name: 'upload_ccf',
			index: 'upload_ccf',
			width: 200,
			editable: true,
			formoptions: { rowpos: 2, colpos: 1 },
			edittype: 'file',  // Define this field as a file input
			editoptions: {
				title: 'Select a file',
			accept: '.jpg,.png,.pdf',  // Specify acceptable file types if needed
			dataInit: function (element) {
            $(element).on("change", function () {
                uploadAndProcessCcf(element);
            });
			}
			},
			editrules: { edithidden: true },  // Optional: show only in Add/Edit dialogs
			hidden: true,  // Hide in grid view but show in the form
			},
			{
			name: 'upload_npwp',
			index: 'upload_npwp',
			width: 200,
			editable: true,
			formoptions: { rowpos: 2, colpos: 2 },
			edittype: 'file',  // Define this field as a file input
			editoptions: {
				title: 'Select a file',
			accept: '.jpg,.png,.pdf',  // Specify acceptable file types if needed
			dataInit: function (element) {
            $(element).on("change", function () {
                uploadAndProcessNpwp(element);
            });
			}
			},
			editrules: { edithidden: true },  // Optional: show only in Add/Edit dialogs
			hidden: true,  // Hide in grid view but show in the form
			},
			{ name: 'consignee', index: 'consignee', 
			editable:true, formoptions: { rowpos: 1, colpos: 2 },edittype:'checkbox', editoptions: { value:"True:False"}, 
			formatter: "checkbox", formatoptions: {disabled : false},hidden:true,
			},
			{name:'shipper_id',index:'shipper_id', width:180, 
				editable: true,
				hidden:false,
				formoptions: { rowpos: 3, colpos: 1 },
				editoptions:{size:"40",
					dataInit: function (el) { $(el).css('text-transform', 'uppercase'); }
					},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'password',index:'password', width:5, hidden:true, <?php echo 'search: false' ?>,
				editable: false, 
				editoptions:{size:"40"},
			},
			{name:'shipper_name',index:'shipper_name', width:290, 
				editable: true,
				formoptions: { rowpos: 4, colpos: 1},
				editrules: {required: true, edithidden: true},
				editoptions:{size:"40",
					dataInit: function (el) { $(el).css('text-transform', 'uppercase'); },
					dataEvents: 
					[{
					   type: 'focusout',
					   fn: function(e) {
						  var rowid = grid.getGridParam('selrow');
						  var account = $('#shipper_name').val();
						  if (account.substr(0, 2) == 'PT' || account.substr(0, 2) == 'CV') {
							 var m = account.split('.')[1];                                
							 $('#account').val(m); 
						  } else {
							 var l = account.split(',')[0];                                
							 var m = l.split('.')[0];
							 $('#account').val(m);
						  }
					   }
					}]
					},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'shipper_address',index:'shipper_address', width:150, hidden: true,
				editable: true,
				formoptions: { rowpos: 5, colpos: 1},				
				editrules: {required: true, edithidden: true},
				edittype:"textarea",
				editoptions:{cols:"30", rows:"6",
					dataInit: function (el) { $(el).css('text-transform', 'uppercase'); },
					},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'shipper_npwp_name',index:'shipper_npwp_name', width:290, hidden: true,
				editable: true,
				formoptions: { rowpos: 3, colpos: 2},
				editrules: {required: true, edithidden: true},
				editoptions:{size:"40",
					dataInit: function (el) { $(el).css('text-transform', 'uppercase'); },
					},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'shipper_npwp',index:'shipper_npwp', width:150, hidden: true,
				editable: true,
				editrules: {required: true, edithidden: true},
				formoptions: { rowpos: 4, colpos: 2},				
				editoptions:{
					size:"40",
					},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			}, 
			{name:'npwp_address',index:'npwp_address', width:150, hidden: true,
				editable: true,
				formoptions: { rowpos: 5, colpos: 2},				
				editrules: {required: true, edithidden: true},
				edittype:"textarea",
				editoptions:{cols:"30", rows:"6",
					dataInit: function (el) { $(el).css('text-transform', 'uppercase'); },
					},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'nib',index:'nib', width:100, hidden: true,
				editable: true, 
				formoptions: { rowpos: 6, colpos: 2},
				editrules: { edithidden: true},
				editoptions:{size:"40"},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'cp_name',index:'cp_name', width:90, hidden: true,
				editable: true,
				formoptions: { rowpos: 6, colpos: 1},				
				editrules: { edithidden: true},
				editoptions:{size:"40"},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'cp_phone',index:'cp_phone', width:90, hidden: true,
				editable: true,
				formoptions: { rowpos: 7, colpos: 1},				
				editrules: { edithidden: true},
				editoptions:{size:"40"},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name: 'cp_email', index: 'cp_email', width: 100, hidden: true,
				editable: true,
				formoptions: { rowpos: 8, colpos: 1 },
				editrules: {
					edithidden: true,
					custom: true,
					custom_func: validateMultipleEmails // Custom function for validation
				},
				edittype: "textarea",
				editoptions: { cols: "30", rows: "6" },
				stype: "text",
				searchoptions: { sopt: ['cn', 'eq'] }
			}, 
			{name:'region_id',index:'region_id', align: 'center', width:100,hidden : true,
				editable: true,
				stype:"text",  
				editoptions:{  
					readonly: "readonly",
					defaultValue: '<?php echo $this->session->userdata('region_id'); ?>'}
			},
			{name:'isActive',index:'isActive', width:100, align:'center', hidden : true, editable: true, 
			                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           	edittype:"select",
				editoptions:{value:"No:No;Yes:Yes"},
				stype:"select",
				searchoptions:{ 
					sopt:['eq'],
					value:':;Yes:Yes;No:No',
				}
			},	
			{name:'sales',index:'sales', width:100,editable: true,
				editrules: {required: true},
				formoptions: { rowpos: 8, colpos: 2},
				edittype:"select",  
				editoptions:{
				searchoptions:{ 
					sopt:['cn'],
				},
				value: {},
				dataUrl:'<?php echo base_url("afg_c_dropdown/dropdownSales") ?>',
				 dataInit: function(element) {
                                $(element).select2({
									multiple: true,
                                    width: '70%',
                                });
				 },
				}
			},
			{name:'oldAktiv',index:'oldAktiv', width:50, editable: true, hidden: true},
			{name:'password_no_encript',index:'password_no_encript', width:50, hidden: true},
			{name:'created_by',index:'created_by', width:100, editable: false, hidden: false},
			{name:'created_at',index:'created_at', width:100, editable: false, hidden: false},
			{name:'sales_code',index:'sales_code', width:100, editable: false, hidden: true},
			{name:'account',index:'account', width:100, 
				editable: true,
				hidden: true,
				editrules: {required: true, edithidden: true},
				formoptions: { rowpos: 9, colpos: 1},
				editoptions:{size:"40",
					dataInit: function (el) { $(el).css('text-transform', 'uppercase'); }
					},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
					}
			},
				{name:'payment',index:'payment', width:100, 
				editable: true,
				hidden: true,
				editrules: {required: true, edithidden: true},
				formoptions: { rowpos: 7, colpos: 2},
				edittype: "select",
				editoptions:{value:"CASH:CASH;CREDIT:CREDIT"},
				stype:"select",
				searchoptions:{ 
					sopt:['cn','eq'],
					}
			},
				{name:'type',index:'type', width:100, 
				editable: true,
				hidden: true,
				editrules: {required: true, edithidden: true},
				formoptions: { rowpos: 9, colpos: 2},
				edittype: "select",
				editoptions: {
					value: "DIRECT SHIPPER:DIRECT SHIPPER;FREIGHT FORWARDER:FREIGHT FORWARDER"
					},
				stype: "select",
				searchoptions: {
					sopt: ['eq'], // Filtering options
						value: "DIRECT SHIPPER:DIRECT SHIPPER;FREIGHT FORWARDER:FREIGHT FORWARDER"
					}
			},
      ],
      shrinkToFit:false,
		multiselect:true,
		loadonce : false,
		cellEdit: false,
		rowNum:15,
		width: 920,
		height: 450,
		rowList:[15,30,45,60],
		viewrecords: true,
		rownumbers: true,
		gridview: true,
		modal: true,
		
		caption:"Customer Gateway <?php echo $this->session->userdata('region_id') ?>",
		editurl:ref+"c_user_management/manage_user_booking",
		cellsubmit:"clientArray",
		pager:'#pagination_user',
		
		loadComplete: function() {
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
		
	})
	.jqGrid('navGrid','#pagination_user', 
		<?php
		if ( $user_id == 0 || $user_id == 546) {
			echo "{ edit: true, add: true, del: true , refresh: false, }";
		}
		else
		{
			echo "{ edit:false, add:false, del: false, refresh: false, }";
		}
		?>
		,
 		{
 			height: "auto", width: 1200, 
 			closeAfterEdit: true, 
 			reloadAfterEdit : true, 
 			modal: true, 
 			recreateForm: true, /* WAJIB: form dibangun ulang tiap dibuka agar struktur tab tidak dobel */
 			onclickSubmit: function (params, postdata) {
 				// Field tab "INFO TAMBAHAN" ikut dikirim bersama form utama
 				// dan disimpan oleh updateDataUserBooking() di model.
 				return {
 					credit_limit:     $('#x_credit_limit').val(),
 					credit_term_days: $('#x_credit_term_days').val(),
 					npwp_16digit:     $('#x_npwp_16digit').val(),
 					customs_id:       $('#x_customs_id').val()
 				};
 			},
 			editCaption: "Edit Data Customer",
			position:['center',10],
 			viewPagerButtons: false,
			afterSubmit: function (response,postData) {
   			var data = $.parseJSON(response.responseText);
    		/* 	grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
    			return [true]; */
				if (data.status=='-1') {
					message("Edit Failed");
					return [false];
				} else {
					grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
					return [true];
				}
    		/*
    			if (data.status=='1'&&data.send_mail=='1') {
					alert("send")
				} else {
					grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
					return [true];
				}
			*/
			},
			beforeShowForm: function(frm) {	
				var rowid = grid.getGridParam('selrow');
				
				// 1. Ambil data dari row yang dipilih
				var oldPassword = grid.jqGrid('getCell', rowid, 'password_no_encript');
				var accountName = grid.jqGrid('getCell', rowid, 'shipper_name');
				var salesIdsString = grid.jqGrid('getCell', rowid, 'sales_code'); 

				// 2. Set field standar
				$('#password').val(oldPassword);
				$('#shipper_name', frm).attr('readonly', 'readonly');
				$('#shipper_id', frm).attr('readonly', 'readonly');
				$('#account', frm).attr('readonly', 'readonly');

				// 3. Logika penentuan nama Account (trimming)
				if (accountName) {
					var m = "";
					if (accountName.substr(0, 2) == 'PT') {
						m = accountName.split('.')[1] || "";
					} else {
						var l = accountName.split(',')[0];
						m = l.split('.')[0] || "";
					}
					$('#account').val(m.trim());
				}

				// 4. FIX UNTUK SELECT2 MULTIPLE
				// Gunakan setTimeout agar Select2 punya waktu untuk inisialisasi/load dataUrl
				setTimeout(function() {
					if (salesIdsString) {
						// Pastikan tidak ada spasi di antara koma jika data dari DB seperti "1, 2, 3"
						var selectedArray = salesIdsString.replace(/\s/g, '').split(",");
						
						// Set value ke element select
						$('#sales', frm).val(selectedArray).trigger('change');
					}
				}, 300); // delay 300ms

				// 5. Bangun tab: DATA CUSTOMER | ALAMAT KIRIM | INFO TAMBAHAN
				buildCustomerTabs(frm, rowid);
			},
		}, 
 		{height: "auto", width: 900, closeAfterAdd: true, reloadAfterAdd : true, modal: true, addCaption: "  Data User Booking Online",
   	afterSubmit: function (response,postData) {
		var data = $.parseJSON(response.responseText);
    		if (data.status=='-1') {
					swal({
						icon: "error",
						title: "Oops...",
						text: "Data exist!",
						footer: '<a href="#">Why do I have this issue?</a>'
					});
					return [false];
				} else {
					grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
					return [true];
				}
			},
	beforeShowForm: function(frm){
		$('#shipper_name', frm).removeAttr('readonly');
	    $('#shipper_id', frm).removeAttr('readonly');
	},
   }, 
   {height: "auto", width: "auto",
  		afterSubmit: function () {
  			grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
    		return [true];
			},
  	},
   	{height: "auto", width: "auto", closeAfterSearch : true,}, 
   	{height: "auto", width: 700, modal: true} 
    );
}

function generateTabelAdmin()
{
	var grid = $("#user-admin"), i;
	grid.jqGrid(
	{ 
		url:"<?php echo base_url('reference/c_user_management/getDataUserAdmin')?>", 
		mtype : "post", 
		datatype: "json",
		colNames:
		[
			'Username',
			'Password',
			'Full Name',
			'Email',
			'Privilage',
			'Region',
			' ', 
			' ',
			' ',
		],
    	colModel:
		[
			{name:'username',index:'username', width:15, 
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				},
				editable: true,
				editoptions:{size:"40"}
			},
			{name:'password',index:'password', width:5, hidden:true, 
				editable: true, 
				editoptions:{size:"40"}
			},
			{name:'fullname',index:'fullname', width:25, 
				editable: true, 
				editoptions:{size:"40"},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'email',index:'email', width:40, 
				editable: true, 
				edittype:"textarea",
				editoptions:{cols:"40", rows:"3"},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'user_level',index:'user_level', width:30, 
				<?php echo 'search: false' ?>,
				editable: true, 
				editoptions:{size:"40"}
			},
			{name:'region_id',index:'region_id', width:30,<?php echo 'search: false' ?>,}, 
			{name:'user_menu',index:'user_menu', align: 'center', width:20, hidden:true,
				<?php echo 'search: false' ?>,
			},
			{name:'password_encript',index:'password_encript', width:20, hidden:true,},
			{name:'level',index:'level', width:20, hidden:false,}
      ],
      sortname: "user_id",
		sortorder: "desc",
		multiselect:true,
		loadonce : false,
		cellEdit: false,
		rowNum:12,
		width: 630,
		height: 270,
		rowList:[25,50,75,100],
		viewrecords: true,
		rownumbers: true,
		gridview: true,
		modal: true,
		caption:"User Admin Gateway <?php echo $this->session->userdata('region_id') ?>",
		editurl:"manage_user_admin",
		cellsubmit:"clientArray",
		pager:'#page-user-admin',
		
		loadComplete: function() {
			$("#cb_"+grid[0].id).hide();
			$( '#btnNewUser' ).attr('disabled',false).attr('onClick','new_admin()');
			$( '#btnEditUser' ).attr('disabled',true);
			$( '#btnCopyUser' ).attr('disabled',true);
			$( '#btnDeleteUser' ).attr('disabled',true);
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
    			$( '#btnNewUser' ).attr('disabled',true);
    			$( '#btnEditUser' ).attr('disabled',false).attr('onClick','edit_admin('+rowid+')');
    			$( '#btnCopyUser' ).attr('disabled',false).attr('onClick','copy_admin('+rowid+')');
    			$( '#btnDeleteUser' ).attr('disabled',false).attr('onClick','delete_admin('+rowid+')');
			}
        	else
        	{
        		var cbs = $("tr.jqgrow > td > input.cbox:disabled", grid[0]);  		
    			cbs.attr("disabled", false);   
    			$( '#btnNewUser' ).attr('disabled',false).attr('onClick','new_admin()');
    			$( '#btnEditUser' ).attr('disabled',true);
    			$( '#btnCopyUser' ).attr('disabled',true);
    			$( '#btnDeleteUser' ).attr('disabled',true);   			
    		}
		},
		
	})
	grid.jqGrid('navGrid','#page-user-admin',
			{view:false, edit:false, add:false, del:false,}
	);
}

function new_admin()
{
	$( '#Form-Input-User' ).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
	$( "#Form-Input-User" ).dialog(
	{
	//	position: 'top',
		autoOpen: true,
		resizable: true,
		height: 320,
		width: 350,
		modal: true,
		title: "ADD DATA USER ADMIN",
		close: function() 
		{	
			$(this).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
			$("#user-admin").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
		}
	});
	
	$.ajax({
		type: "POST", 
		url : "<?php echo site_url('reference/c_user_management/open_form')?>",
		data: { id : '', mode: 'add'},
		success:function(XMLHttpRequest)
		{
			$('#Form-Input-User').html(XMLHttpRequest)
		}
	})
}

function edit_admin(rowid)
{
	$( '#Form-Input-User' ).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
	$( "#Form-Input-User" ).dialog(
	{
	//	position: 'top',
		autoOpen: true,
		resizable: true,
		height: 350,
		width: 350,
		modal: true,
		title: "EDIT DATA USER ADMIN",
		close: function() 
		{	
			$(this).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
			$("#user-admin").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
		}
	});
	
	$.ajax({
		type: "POST", 
		url : "<?php echo site_url('reference/c_user_management/open_form')?>",
		data: { id : rowid, mode: 'edit'},
		success:function(XMLHttpRequest)
		{
			$('#Form-Input-User').html(XMLHttpRequest)
		}
	})
}

function copy_admin(rowid)
{
	$( '#Form-Input-User' ).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
	$( "#Form-Input-User" ).dialog(
	{
		autoOpen: true,
		resizable: true,
		height: 320,
		width: 350,
		modal: true,
		title: "COPY DATA USER ADMIN",
		close: function() 
		{	
			$(this).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
			$("#user-admin").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
		}
	});
	
	$.ajax({
		type: "POST", 
		url : "<?php echo site_url('reference/c_user_management/open_form')?>",
		data: { id : rowid, mode: 'add'},
		success:function(XMLHttpRequest)
		{
			$('#Form-Input-User').html(XMLHttpRequest)
		}
	})
}

function delete_admin(rowid)
{
	$( "#Form-Delete-User" ).dialog(
			{
				autoOpen: true,
				resizable: true,
				height: 150,
				width: 250,
				modal: true,
				title: "Delete Confirm",
				buttons:  
				{	 
					"DELETE": function()  
					{	 
						$.ajax({
							type: "POST", 
							url : "<?php echo site_url('reference/c_user_management/manage_user_admin')?>",
							data: 
							{ 
								id : rowid,
								oper: 'del'
							},
							success:function(response)
							{
								var data = $.parseJSON(response);
								if(data.status == 1){
									$( '#Form-Delete-User' ).dialog('close');
									$("#user-admin").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
								}
							}
						})
					},
					"CANCEL": function()  
					{	 
						$( this ).dialog('close')
					}
				},
				close: function()  {	 }
			});
	$('#Form-Delete-User').html('Delete this user?')
}
function uploadAndProcessFile(fileInput) {
    let file = fileInput.files[0];
    
    if (file) {
        let formData = new FormData();
        formData.append("file", file);

        // Send the file to the server for OCR processing
        $.ajax({
            url: "<?php echo site_url('reference/c_user_management/process_file')?>",  // Backend PHP script for handling the file
			type: "POST",
			data: formData,
			contentType: false,
			processData: false,
			success: function(data) {
				// Access the OCR-extracted text
				let ocrText = data.content;
				const datas = {
				companyName: (ocrText.match(/Name of Company\s+(.+)/i) || [])[1]?.trim(),
				officeAddress: (ocrText.match(/Office Address\s+(.+)/i) || [])[1]?.trim(),
				officeTelephone: (ocrText.match(/Office Telephone Number\s+([\d-]+)/i) || [])[1]?.trim(),
				nib: (ocrText.match(/NIB Company\s+([\d]+)/i) || [])[1]?.trim(),
				npwp: (ocrText.match(/NPWP Company\s+([\d.-]+)/i) || [])[1]?.trim(),
				contactPersonEmail: (ocrText.match(/Contact Person Email\s+([\w@.]+)/i) || [])[1]?.trim(),
				contactPersonName: (ocrText.match(/Name of Contact Person For Invoicing\s+(.+)/i) || [])[1]?.trim(),
				invoicingEmail: (ocrText.match(/Invoicing Correspondence Email\s+([\w@.]+)/i) || [])[1]?.trim(),
				};

				// Log extracted information
				console.log(datas);
				populateFormWithData(datas);

        // Now use `extractedText` to populate fields in jqGrid or other UI elements
		},
    error: function() {
        alert("File upload or processing failed.");
    }
        });
    }
}	

function uploadAndProcessCRF(fileInput) {
    let file = fileInput.files[0];
    
    if (file) {
        let formData = new FormData();
        formData.append("file", file);

        // Send the file to the server for OCR processing
        $.ajax({
            url: "<?php echo site_url('reference/c_user_management/proses_crf')?>",  // Backend PHP script for handling the file
			type: "POST",
			data: formData,
			contentType: false,
			processData: false,
			success: function(data) {
				// Access the OCR-extracted text
				let ocrText = data.content;
				const lines = ocrText.split("\n").map(line => line.trim()).filter(line => line);

            const result = {};
            let lastKey = null;

            lines.forEach(line => {
                if (line.includes(":")) {
                    const [key, value] = line.split(":").map(item => item.trim());
                    result[key] = value || ""; // Assign value or an empty string
                    lastKey = key; // Update the last key
                } else if (lastKey) {
                    // Append the current line to the last key's value
                    result[lastKey] = (result[lastKey] + " " + line).trim();
                }
            });
			populateFormWithData(result);

        // Now use `extractedText` to populate fields in jqGrid or other UI elements
		},
    error: function() {
        alert("File upload or processing failed.");
    }
        });
    }
}

function uploadAndProcessCcf(fileInput) {
    let file = fileInput.files[0];
    
    if (file) {
        let formData = new FormData();
        formData.append("file", file);

        // Send the file to the server for OCR processing
        $.ajax({
            url: "<?php echo site_url('reference/c_user_management/proses_ccf')?>",  // Backend PHP script for handling the file
			type: "POST",
			data: formData,
			contentType: false,
			processData: false,
			success: function(data) {
				// Access the OCR-extracted text
				let ocrText = data.content;
				/* const datas = {
				companyName: (ocrText.match(/Name\s+(.+)/i) || [])[1]?.trim(),
				officeAddress: (ocrText.match(/Address\s+(.+)/i) || [])[1]?.trim(),
				officeTelephone: (ocrText.match(/Telp\s+([\d-]+)/i) || [])[1]?.trim(),
				contactPersonEmail: (ocrText.match(/Authorized Applicant Email\s+([\w@.]+)/i) || [])[1]?.trim(),
				contactPersonName: (ocrText.match(/Authorized Applicant Name\s+(.+)/i) || [])[1]?.trim(),
				invoicingEmail: (ocrText.match(/Email\s+([\w@.]+)/i) || [])[1]?.trim(),
				}; */

				// Log extracted information
				console.log(datas);
				//populateFormWithData(datas);

        // Now use `extractedText` to populate fields in jqGrid or other UI elements
		},
    error: function() {
        alert("File upload or processing failed.");
    }
        });
    }
}	

function uploadAndProcessNpwp(fileInput) {
    let file = fileInput.files[0];
    
    if (file) {
        let formData = new FormData();
        formData.append("file", file);

        // Send the file to the server for OCR processing
        $.ajax({
            url: "<?php echo site_url('reference/c_user_management/proses_npwp')?>",  // Backend PHP script for handling the file
		  type: "POST",
		  data: formData,
		  contentType: false,
		  processData: false,
	  success: function(data) {
        // Use the parsed JSON data to populate your jqGrid or other fields
		let extractedText = data.content;
		populateNpwpWithData(extractedText);
	  },
	  error: function() {
        alert("File upload or processing failed.");
	  }
	  });
    }
}

function populateFormWithData(data) {
	var account = data["Cust Name"];
				if (account.substr(0, 2) == 'PT')
				{
				var m = account.split('.')[1];								
				$('#account').val(m.trim()).trigger('change'); 
				}else{
				var l = account.split(',')[0];								
				var m = l.split('.')[0];								
				$('#account').val(m.trim()).trigger('change');	
				}
	$("#shipper_id").val(data["Cust Nick Name"]);
    $("#shipper_name").val(data["Cust Name"]+", "+data["Business suffixes"]);
    $("#shipper_address").val(data["Cust Address"]);
    $("#cp_name").val(data["Contact Person"]);
	$("#cp_email").val(data["Email"]);
    $("#cp_phone").val(data["Phone Number"]);
    $("#shipper_npwp_name").val(data["NPWP Name"]);
    $("#shipper_npwp").val(data["NPWP No"]);
    $("#npwp_address").val(data["NPWP Address"]);
    $("#nib").val(data.nib);
    $("#sales").val(data.SALESMAN);
    // Trigger jqGrid to recognize the changes, if needed
    $("#tabel_user_booking").trigger("reloadGrid");
}
function populateNpwpWithData(extractedText) {
// Extract NPWP Number
const npwpMatch = extractedText.match(/\d{2}\.\d{3}\.\d{3}\.\d-\d{3}\.\d{3}/);
const npwpNumberMatch = extractedText.match(/NPWP16\s*:\s*([\d\s]+)/);
const npwpNumber = npwpNumberMatch ? npwpNumberMatch[1].replace(/\s+/g, '') : npwpMatch; // Remove spaces for consistency

// Extract NPWP Name
const companyNameMatch = extractedText.match(/^[A-Z .\-\d]+$/m);
const npwpNameMatch = extractedText.match(/\b[A-Z\s\-]+(?=\n\nNPWP)/);
const npwpName = npwpNameMatch ? npwpNameMatch[0].trim() : companyNameMatch;

// Extract NPWP Address
const addressStartIndex = extractedText.indexOf(npwpName) + npwpName.length;
const address = extractedText.slice(addressStartIndex).trim().replace(/\n+/g, ' ');
const addressRegex = /(?<=\bNPWP.*\n)([\s\S]*?)(?=Tanggal Terdaftar|$)/;
const npwpAddressMatch = extractedText.match(addressRegex);
const npwpAddress = npwpAddressMatch ? npwpAddressMatch[0].trim() : address;
console.log(extractedText);
    $("#shipper_npwp_name").val(npwpName);
    $("#npwp_address").val(npwpAddress);
    $("#shipper_npwp").val(npwpNumber);
    // Trigger jqGrid to recognize the changes, if needed
    $("#tabel_user_booking").trigger("reloadGrid");
}
function validateMultipleEmails(value, colname) {
    if (!value) return [true, ""]; // Allow empty values

    let emailArray = value.split(",").map(email => email.trim());
    let emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;

    let invalidEmails = emailArray.filter(email => !emailPattern.test(email));
    
    if (invalidEmails.length > 0) {
        return [false, colname + ": Invalid emails - " + invalidEmails.join(", ")];
    }
    return [true, ""];
}

/* ============================================================
   TAB MODAL EDIT CUSTOMER:
   DATA CUSTOMER | ALAMAT KIRIM (mstshipper_address) | INFO TAMBAHAN
   ============================================================ */
var urlGetAddr  = "<?php echo site_url('reference/c_user_management/get_shipper_addresses')?>";
var urlSaveAddr = "<?php echo site_url('reference/c_user_management/save_shipper_address')?>";
var urlDelAddr  = "<?php echo site_url('reference/c_user_management/delete_shipper_address')?>";
var urlWilayah  = "<?php echo site_url('reference/c_user_management/autocomplete_wilayah')?>";
var urlGetExtra = "<?php echo site_url('reference/c_user_management/get_shipper_extra')?>";
var _addrCache  = [];

function buildCustomerTabs(frm, idShipper) {
	var $tbl = $('#TblGrid_tabel_user_booking');
	if ($tbl.length === 0) return;
	if ($('#custEditTabs').length > 0) return; // pengaman (recreateForm sudah aktif)

	injectCustTabCss();

	// Tab 1: bungkus tabel form standar bawaan jqGrid
	$tbl.wrap('<div id="cust-tab-data" class="cust-tab-pane"></div>');
	$('#cust-tab-data').wrap('<div id="custEditTabs"></div>');
	var $wrap = $('#custEditTabs');

	$wrap.prepend(
		'<div id="custTabBtns">' +
		'<button type="button" class="cust-tab-btn active" data-tab="cust-tab-data">DATA CUSTOMER</button>' +
		'<button type="button" class="cust-tab-btn" data-tab="cust-tab-addr">ALAMAT KIRIM</button>' +
		'<button type="button" class="cust-tab-btn" data-tab="cust-tab-info">INFO TAMBAHAN</button>' +
		'</div>');

	$wrap.append(addrPaneHtml());
	$wrap.append(infoPaneHtml());

	$('.cust-tab-pane', $wrap).hide();
	$('#cust-tab-data').show();

	$('#custTabBtns .cust-tab-btn').on('click', function () {
		$('#custTabBtns .cust-tab-btn').removeClass('active');
		$(this).addClass('active');
		$('.cust-tab-pane', $wrap).hide();
		$('#' + $(this).data('tab')).show();
	});

	$('#addr_id_shipper').val(idShipper);
	loadShipperAddresses(idShipper);
	loadShipperExtra(idShipper);
	initWilayahAutocomplete();
	$('#btnSaveAddr').on('click', saveShipperAddress);
	$('#btnResetAddr').on('click', resetAddrForm);
	$('#addr_lat, #addr_lng').on('change paste', function () {
		var el = this;
		setTimeout(function () { handleCoordInput(el); }, 0); // tunggu isi paste masuk
	});
}

function injectCustTabCss() {
	if ($('#custTabCss').length > 0) return;
	$('head').append(
		'<style id="custTabCss">' +
		'#custTabBtns{margin:0 0 10px 0;border-bottom:2px solid #2779aa;}' +
		'.cust-tab-btn{background:#eee;border:1px solid #ccc;border-bottom:none;padding:5px 14px;cursor:pointer;font-size:11px;margin-right:2px;}' +
		'.cust-tab-btn.active{background:#2779aa;color:#fff;font-weight:bold;}' +
		'.addr-form-tbl td{font-size:11px;padding:3px 6px;vertical-align:top;}' +
		'.addr-list-tbl{border-collapse:collapse;width:100%;}' +
		'.addr-list-tbl th,.addr-list-tbl td{border:1px solid #ccc;font-size:11px;padding:4px 6px;text-align:left;}' +
		'.addr-list-tbl th{background:#2779aa;color:#fff;}' +
		'.ui-autocomplete{max-height:220px;overflow-y:auto;overflow-x:hidden;font-size:11px;z-index:99999;}' +
		'</style>');
}

function addrPaneHtml() {
	return '' +
	'<div id="cust-tab-addr" class="cust-tab-pane">' +
	'  <input type="hidden" id="addr_id_shipper"><input type="hidden" id="addr_id_address">' +
	'  <input type="hidden" id="addr_village_id"><input type="hidden" id="addr_district_id"><input type="hidden" id="addr_regency_id">' +
	'  <table class="addr-form-tbl">' +
	'    <tr><td>Tipe Alamat</td><td><select id="addr_type">' +
	'        <option value="PICKUP">PICKUP</option><option value="DROP">DROP</option>' +
	'        <option value="WAREHOUSE">WAREHOUSE / GUDANG</option><option value="MAIN">ALAMAT UTAMA</option>' +
	'        <option value="BILLING">BILLING</option><option value="NPWP">NPWP</option><option value="HBL">HBL</option></select></td>' +
	'      <td>Label Lokasi</td><td><input type="text" id="addr_label" size="32" placeholder="cth: GUDANG CIKARANG" style="text-transform:uppercase"></td></tr>' +
	'    <tr><td>Alamat (jalan/blok/no)</td><td><textarea id="addr_address" cols="34" rows="3" style="text-transform:uppercase"></textarea></td>' +
	'      <td>Kel / Kec / Kota</td><td><input type="text" id="addr_wilayah" size="42" placeholder="ketik min. 3 huruf, cth: PEUSAR">' +
	'        <div id="addr_wilayah_selected" style="font-size:10px;color:#2779aa;margin-top:3px;"></div></td></tr>' +
	'    <tr><td>Kode Pos</td><td><input type="text" id="addr_postal" size="10"></td>' +
	'      <td>Jadikan Default (per tipe)?</td><td><input type="checkbox" id="addr_is_default"> Ya</td></tr>' +
	'    <tr><td>Nama PIC Lokasi</td><td><input type="text" id="addr_pic_name" size="30" style="text-transform:uppercase"></td>' +
	'      <td>Telp PIC</td><td><input type="text" id="addr_pic_phone" size="20"></td></tr>' +
	'    <tr><td>Catatan Operasional</td><td colspan="3"><input type="text" id="addr_notes" size="80" placeholder="cth: MAKS TRUK CDD, JAM TERIMA 08.00-16.00" style="text-transform:uppercase"></td></tr>' +
	'    <tr><td>Latitude</td><td><input type="text" id="addr_lat" size="16" placeholder="cth: -6.3052417">' +
	'        <div style="font-size:10px;color:#888;margin-top:2px;">Bisa tempel langsung "-6.30, 107.28" atau link Google Maps di sini</div></td>' +
	'      <td>Longitude</td><td><input type="text" id="addr_lng" size="16" placeholder="cth: 107.2891350"> ' +
	'        <a href="#" id="addr_map_link" onclick="openAddrMap(); return false;" style="font-size:11px;">&#128205; Lihat / Cari di Google Maps</a></td></tr>' +
	'    <tr><td></td><td colspan="3">' +
	'      <input type="button" id="btnSaveAddr" value="SIMPAN ALAMAT">' +
	'      <input type="button" id="btnResetAddr" value="RESET">' +
	'      <span style="font-size:10px;color:#888;margin-left:8px;">Alamat tersimpan langsung saat tombol ini ditekan (tidak menunggu Submit form utama)</span>' +
	'    </td></tr>' +
	'  </table>' +
	'  <div style="margin-top:8px;"><table id="addrListTbl" class="addr-list-tbl">' +
	'    <thead><tr><th>Tipe</th><th>Label</th><th>Alamat</th><th>Wilayah</th><th>Koordinat</th><th>PIC</th><th>Default</th><th>Aksi</th></tr></thead>' +
	'    <tbody><tr><td colspan="8">Memuat...</td></tr></tbody></table></div>' +
	'</div>';
}

function infoPaneHtml() {
	return '' +
	'<div id="cust-tab-info" class="cust-tab-pane">' +
	'  <table class="addr-form-tbl">' +
	'    <tr><td>Credit Limit (IDR)</td><td><input type="text" id="x_credit_limit" size="22"></td></tr>' +
	'    <tr><td>Term of Payment (hari)</td><td><input type="number" id="x_credit_term_days" min="0" step="1" style="width:80px"> <i style="font-size:10px;color:#888">cth: 30 / 45 / 60 &mdash; relevan utk payment CREDIT</i></td></tr>' +
	'    <tr><td>NPWP 16 Digit (Coretax)</td><td><input type="text" id="x_npwp_16digit" size="22" maxlength="16"></td></tr>' +
	'    <tr><td>ID Kepabeanan / Akses BC</td><td><input type="text" id="x_customs_id" size="32"></td></tr>' +
	'  </table>' +
	'  <div style="font-size:10px;color:#888;margin-top:6px;">Field pada tab ini akan tersimpan saat tombol <b>Submit</b> form utama ditekan.</div>' +
	'</div>';
}

function loadShipperExtra(idShipper) {
	$.post(urlGetExtra, { id_shipper: idShipper }, function (d) {
		$('#x_credit_limit').val(d.credit_limit || '');
		$('#x_credit_term_days').val(d.credit_term_days || '');
		$('#x_npwp_16digit').val(d.npwp_16digit || '');
		$('#x_customs_id').val(d.customs_id || '');
	}, 'json');
}

function loadShipperAddresses(idShipper) {
	$.post(urlGetAddr, { id_shipper: idShipper }, function (d) {
		var $tb = $('#addrListTbl tbody').empty();
		_addrCache = d.rows || [];
		if (_addrCache.length === 0) {
			$tb.append('<tr><td colspan="8" style="text-align:center;color:#888">Belum ada alamat kirim tersimpan</td></tr>');
			return;
		}
		$.each(_addrCache, function (i, r) {
			var wilayah = (r.village_name ? r.village_name + ', ' : '') +
			              (r.district_name ? 'KEC. ' + r.district_name + ', ' : '') +
			              (r.regency_name || '');
			var pic = (r.pic_name || '') + (r.pic_phone ? ' / ' + r.pic_phone : '');
			$tb.append('<tr>' +
				'<td>' + r.address_type + '</td>' +
				'<td>' + (r.label || '') + '</td>' +
				'<td>' + r.address + (r.notes ? '<br><i style="color:#888">' + r.notes + '</i>' : '') + '</td>' +
				'<td>' + wilayah + (r.postal_code ? ' ' + r.postal_code : '') + '</td>' +
				'<td style="white-space:nowrap">' + (hasCoord(r)
					? '<a href="https://www.google.com/maps?q=' + r.latitude + ',' + r.longitude + '" target="_blank">' +
					  parseFloat(r.latitude) + ', ' + parseFloat(r.longitude) + '</a>'
					: '<span style="color:#c00">belum ada</span>') + '</td>' +
				'<td>' + pic + '</td>' +
				'<td style="text-align:center">' + (r.is_default == 1 ? '&#10004;' : '') + '</td>' +
				'<td><a href="#" onclick="editShipperAddress(' + r.id_address + '); return false;">Edit</a> | ' +
				'<a href="#" onclick="deleteShipperAddress(' + r.id_address + '); return false;">Hapus</a></td>' +
				'</tr>');
		});
	}, 'json');
}

function initWilayahAutocomplete() {
	if (!$.fn.autocomplete) { return; } // jQuery UI autocomplete tidak tersedia
	$('#addr_wilayah').autocomplete({
		minLength: 3,
		delay: 400,
		source: function (req, resp) {
			$.getJSON(urlWilayah, { term: req.term }, function (data) {
				resp($.map(data, function (it) {
					return { label: it.label + '  [' + it.level + ']', value: it.label, item: it };
				}));
			});
		},
		select: function (e, ui) {
			var it = ui.item.item;
			$('#addr_village_id').val(it.village_id || '');
			$('#addr_district_id').val(it.district_id || '');
			$('#addr_regency_id').val(it.regency_id || '');
			$('#addr_wilayah_selected').text('Terpilih: ' + it.label);
		}
	});
}

function saveShipperAddress() {
	if ($('#addr_address').val().trim() === '') { alert('Alamat wajib diisi'); return; }
	if ($('#addr_regency_id').val() === '') { alert('Pilih wilayah (kelurahan/kecamatan/kota) dari daftar autocomplete terlebih dahulu'); return; }
	var lat = $('#addr_lat').val().trim(), lng = $('#addr_lng').val().trim();
	if ((lat === '') !== (lng === '')) { alert('Latitude dan Longitude harus diisi berpasangan (atau kosongkan keduanya)'); return; }
	if (lat !== '') {
		var fLat = parseFloat(lat), fLng = parseFloat(lng);
		if (isNaN(fLat) || isNaN(fLng) || Math.abs(fLat) > 90 || Math.abs(fLng) > 180) {
			alert('Format koordinat tidak valid.\nLatitude -90 s/d 90, Longitude -180 s/d 180'); return;
		}
		// Indonesia kira-kira lat -11..6, lng 95..141. Di luar itu biasanya lat/lng tertukar.
		if (fLat < -11 || fLat > 6 || fLng < 95 || fLng > 141) {
			if (!confirm('Koordinat berada di luar wilayah Indonesia (mungkin latitude/longitude tertukar).\nTetap simpan?')) return;
		}
	}
	$.post(urlSaveAddr, {
		id_address:   $('#addr_id_address').val(),
		id_shipper:   $('#addr_id_shipper').val(),
		address_type: $('#addr_type').val(),
		label:        $('#addr_label').val(),
		address:      $('#addr_address').val(),
		village_id:   $('#addr_village_id').val(),
		district_id:  $('#addr_district_id').val(),
		regency_id:   $('#addr_regency_id').val(),
		postal_code:  $('#addr_postal').val(),
		pic_name:     $('#addr_pic_name').val(),
		pic_phone:    $('#addr_pic_phone').val(),
		notes:        $('#addr_notes').val(),
		latitude:     lat,
		longitude:    lng,
		is_default:   $('#addr_is_default').is(':checked') ? 1 : 0
	}, function (d) {
		if (d.status == 1) {
			resetAddrForm();
			loadShipperAddresses($('#addr_id_shipper').val());
		} else {
			alert(d.message || 'Gagal menyimpan alamat');
		}
	}, 'json');
}

function editShipperAddress(idAddress) {
	for (var i = 0; i < _addrCache.length; i++) {
		if (_addrCache[i].id_address == idAddress) {
			var r = _addrCache[i];
			$('#addr_id_address').val(r.id_address);
			$('#addr_type').val(r.address_type);
			$('#addr_label').val(r.label || '');
			$('#addr_address').val(r.address);
			$('#addr_village_id').val(r.village_id || '');
			$('#addr_district_id').val(r.district_id || '');
			$('#addr_regency_id').val(r.regency_id);
			$('#addr_postal').val(r.postal_code || '');
			$('#addr_pic_name').val(r.pic_name || '');
			$('#addr_pic_phone').val(r.pic_phone || '');
			$('#addr_notes').val(r.notes || '');
			$('#addr_lat').val(r.latitude  !== null && r.latitude  !== undefined ? parseFloat(r.latitude)  : '');
			$('#addr_lng').val(r.longitude !== null && r.longitude !== undefined ? parseFloat(r.longitude) : '');
			$('#addr_is_default').prop('checked', r.is_default == 1);
			var wilayah = (r.village_name ? r.village_name + ', ' : '') +
			              (r.district_name ? 'KEC. ' + r.district_name + ', ' : '') +
			              (r.regency_name || '');
			$('#addr_wilayah').val('');
			$('#addr_wilayah_selected').text('Terpilih: ' + wilayah);
			$('#btnSaveAddr').val('UPDATE ALAMAT');
			break;
		}
	}
}

function deleteShipperAddress(idAddress) {
	if (!confirm('Hapus alamat ini?')) return;
	$.post(urlDelAddr, { id_address: idAddress }, function (d) {
		loadShipperAddresses($('#addr_id_shipper').val());
	}, 'json');
}

function resetAddrForm() {
	$('#addr_id_address,#addr_village_id,#addr_district_id,#addr_regency_id').val('');
	$('#addr_label,#addr_address,#addr_postal,#addr_pic_name,#addr_pic_phone,#addr_notes,#addr_wilayah,#addr_lat,#addr_lng').val('');
	$('#addr_is_default').prop('checked', false);
	$('#addr_wilayah_selected').text('');
	$('#addr_type').val('PICKUP');
	$('#btnSaveAddr').val('SIMPAN ALAMAT');
}

/* ---------- Helper koordinat alamat ---------- */
function hasCoord(r) {
	return r.latitude !== null && r.latitude !== undefined && r.latitude !== '' &&
	       r.longitude !== null && r.longitude !== undefined && r.longitude !== '';
}

/*
 * Parser input koordinat. Mendukung:
 *  - "-6.3052, 107.2891"  (salin dari Google Maps: klik kanan titik -> klik angka koordinat)
 *  - link Google Maps: .../@-6.3052,107.2891,17z  atau  ?q=-6.3052,107.2891
 *  - desimal koma pada satu field: "-6,3052" -> "-6.3052"
 */
function parseCoordText(txt) {
	txt = (txt || '').trim();
	var m = txt.match(/@(-?\d+\.\d+),\s*(-?\d+\.\d+)/) ||
	        txt.match(/[?&](?:q|query|ll|destination)=(-?\d+\.\d+)(?:,|%2C)\s*(-?\d+\.\d+)/i) ||
	        txt.match(/^(-?\d+\.\d+)\s*[,;\s]\s*(-?\d+\.\d+)$/);
	return m ? { lat: m[1], lng: m[2] } : null;
}

function handleCoordInput(el) {
	var v = $(el).val();
	var pair = parseCoordText(v);
	if (pair) {
		$('#addr_lat').val(pair.lat);
		$('#addr_lng').val(pair.lng);
		return;
	}
	v = v.trim();
	if (/^-?\d+,\d+$/.test(v)) $(el).val(v.replace(',', '.')); // desimal koma
}

function openAddrMap() {
	var lat = $('#addr_lat').val().trim(), lng = $('#addr_lng').val().trim();
	var url;
	if (lat !== '' && lng !== '') {
		url = 'https://www.google.com/maps?q=' + encodeURIComponent(lat + ',' + lng);
	} else {
		// belum ada koordinat: cari berdasarkan teks alamat + wilayah terpilih
		var q = ($('#addr_address').val() + ' ' +
		         $('#addr_wilayah_selected').text().replace('Terpilih:', '')).replace(/\s+/g, ' ').trim();
		if (q === '') { alert('Isi alamat atau koordinat terlebih dahulu'); return; }
		url = 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(q);
	}
	window.open(url, '_blank');
}
</script> 