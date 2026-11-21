<style>
	#table-listing tr.row-expiring-soon > td {
		background-color: #fdecea !important;
	}
	#table-listing tr.row-expiring-soon:hover > td {
		background-color: #fbd5d1 !important;
	}
</style>
<script type="text/javascript">

$(document).ready(function()
{
	$('#tabs-lading .the-tab').hide();
	$('#tabs-lading div:first').show();
	$('#tabs-lading ul li:first').addClass('active');
   $('#tabs-lading ul li a').click(function(){
		$('#tabs-lading ul li').removeClass('active');
		$(this).parent().addClass('active');
		var currentTab = $(this).attr('href');
		$('#tabs-lading .the-tab').hide();
		$(currentTab).show();
		return false;
	});	
	// #dateForm / #validForm sekarang murni tampilan ringkasan (read-only),
	// jadi datepicker di sini tidak diperlukan lagi.
	disabled_all();
	$("#table-selling").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
	get_data('last');	
	table_listing();
	$(".ui-jqgrid-titlebar-close",$("#table-listing")[0].grid.cDiv).hide();
});


function copy_data()
{
	var id = $("#idSelling").val();
	var ref_no = $('#refNo').val();
	$("#form_copy").html('Are you sure want to copy this data ?');
	$("#form_copy").dialog(
	{
		autoOpen: true,
		resizable: false,
		top:10,
		height: "auto",
		width: "auto",
		modal: true,
		title: "Copy Data Confirm",
		buttons : 
		{  
			"COPY": function()
			{
				$.ajax
				({
					type: "POST",
					url : "<?php echo base_url('pricelist/selling_rate/copy_data')?>/",
					data: { id : id, refNo: ref_no, oper:'copy' },
					success: function(response)
					{
						var data = $.parseJSON(response);
						show_messages('Data Copied Success');
						get_data('last');	
						$("#table-listing").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
					},
					error: function()
					{ 
						show_messages('Data can\'t copy');
						$(this).dialog('close');
					}
				});
				$(this).dialog('close');	
			},
			"CANCEL": function()
			{
				$(this).dialog('close');
			}
		},
		close: function()  {	 }
	});
}

function get_data(input)
{
	disabled_all()
	if(input=='prev'){
		$("#btnNext").attr('disabled',false)
	}
	if(input=='next'){
		$("#btnPrev").attr('disabled',false)
	}
	
	$( '#loading-selling' ).ajaxStart(function(){
		$(this).fadeIn();
		$(this).html('<img src="<?php echo base_url("public/images/loader.gif")?>"/>');
		}).ajaxStop(function(){
		$(this).fadeOut();
	});
	
	$.ajax
	({
			type:"POST",
			url:"<?php echo base_url('pricelist/selling_rate/get_selling') ?>",
			data:
			{ 
				idSelling : $( '#idSelling' ).val(), 
				shipper_id : $( '#shipperAdd' ).val(), 
				mode : input 
			},
			success:function(data)
			{
				var data = $.parseJSON(data);
				
				$( '#idSelling' ).val(data.aktiv_id);
				$( '#refNo' ).val(data.ref_no);
				$( '#dateForm' ).val(data.tanggal);
				$( '#validForm' ).val(data.valid);
				$( '#fromForm' ).val(data.dari).attr('disabled',true);
				$( '#toForm' ).val(data.kepada);
				$( '#attnForm' ).val(data.attn);		
				$( '#kursForm' ).val(data.kurs_selling);
				$( '#remarkForm' ).val(data.remarks);
	
				checkRadioBtn(data.of_emkl);

				if(data.aktiv_id==data.max_id){
					$("#btnNext").attr('disabled',true)
				}
				if(data.aktiv_id==data.min_id){
					$("#btnPrev").attr('disabled',true)
				}
				
				$( '#table-selling').jqGrid('setGridParam', { postData: {  id_selling : $( '#idSelling' ).val() } } ).trigger("reloadGrid");
				table_selling();
				$( '#tables').jqGrid('setGridParam', {  } ).trigger("reloadGrid");
				buying_rate();
				$(".ui-jqgrid-titlebar-close",$("#table-selling")[0].grid.cDiv).hide();
				var hasSelection = ($("#idSelling").val() != "");
				$("#del_table-selling").attr('hidden', !hasSelection);
				$("#btnInsertSelling").toggle(hasSelection);
				$("input[id=btnCetak]").attr('disabled', !hasSelection);
				$("input[id=btnCopy]").attr('disabled', !hasSelection);
			},
	})
}

function checkRadioBtn(N)
{
   var radioLength = $('input[name="emkl_type"]').length;
   for (var i = 0; i < radioLength; i++) 
	{
    	if ($('input[name="emkl_type"]')[i].value == N) 
		{
			$('input[name="emkl_type"]')[i].checked = true;
        	break;
    	}
    }
}

// ------------------------------------------------------------------
// CATATAN PERUBAHAN: tombol New/Edit/Delete/Save di toolbar header
// sudah DIHAPUS. Tambah/ubah/hapus data selling sekarang sepenuhnya
// dilakukan lewat grid "Data Selling" (lihat table_listing()), yang
// sudah punya tombol Add/Edit/Delete bawaan jqGrid.
//
// Panel header (No Ref, Tanggal, Valid, Kepada, UP, Kurs, Remarks, dst)
// sekarang murni ringkasan READ-ONLY dari baris yang dipilih/dobel-klik
// di grid "Data Selling" atau lewat Next/Prev.
//
// disabled_all()/enabled_all() dipertahankan namanya (sebagai fungsi
// sederhana, bukan lagi dihapus total) supaya tidak error kalau ada
// pemanggilan lama dari file template header yang belum ikut di-review
// (file tersebut tidak ikut diunggah). Jika template sudah dibersihkan
// dari tombol New/Edit/Delete/Save, kedua fungsi ini boleh dihapus.
// ------------------------------------------------------------------
function disabled_all()
{
	$( '#shipperId, #shipperAdd, #refNo, #dateForm, #validForm, #toForm, #attnForm, #kursForm, #remarkForm, #fromForm, #emkl_type_of, #emkl_type_misc' )
		.attr('disabled', true);
	$( '#btnNext, #btnPrev' ).attr('disabled', false);
	$( '#btnCetak' ).attr('onClick','print_data()').val('CETAK');
}

function enabled_all()
{
	// Deprecated: mode "edit inline header" sudah tidak dipakai lagi.
	// Dibiarkan sebagai alias supaya pemanggilan lama tidak error.
	disabled_all();
}

function table_selling()
{
	var grid = $("#table-selling"), i;	
	grid.jqGrid({ 
		url:"<?php echo base_url('pricelist/selling_rate/get_table_selling')?>",
		mtype : "post",
		datatype: "json", 
		postData:  {  
			id_selling	: $("input[id='idSelling']").val(),
		},
		colNames:
		[
			'OF/MISC',
			'Item of Service',
			'P/C',
			'Set/M3',
			'Destination',
			'Location',
			'Type',
			'Container',
			'Currency',
			'Sales Comission',
			'Discount',
			'Price',
			'Base Rate',
			'MChgs',
			'Remaks',
			'Rate < RGL',
			'Approval',
			'',
		],
    	colModel:
		[
			{name:'of_emkl',index:'of_emkl', align:"center", width:50, 
				editable: false, 
				hidden: true
			},
			{name:'nama_service',index:'nama_service', align:"left", width:150, 
				editable: true, 
				edittype:"select",
				editoptions:
				{
					dataUrl:'<?php echo base_url("afg_c_dropdown/dropdownItemService") ?>',
					dataInit: function (el) 
					{ 
						$(el).css({'max-width':'150px'}); 
					},
				},
				searchoptions: {
					searchhidden: true,
					sopt:['cn','eq'],
          	},
			},
			{name:'kondisi',index:'kondisi', align:"center", width:50, editable: true, 
				edittype:"select",
				formatter:'select', 
				editoptions:
				{
					value:"PREPAID:PREPAID;COLLECT:COLLECT",
					dataInit: function (el) 
					{ 
						$(el).css({'max-width':'50px'}); 
					},
				},
				searchoptions: {
					searchhidden: true,
					sopt:['cn','eq'],
          	},
			},
			{name:'set_m3',index:'set_m3', align:"center", width:50, editable: true, 
				edittype:"select",formatter:'select', 
				editoptions:
				{
					value:" : ;SET:SET;M3:M3", 
					dataInit: function (el) 
					{ 
						$(el).css({'max-width':'50px'}); 
					},
				},
				searchoptions: {
					searchhidden: true,
					sopt:['cn','eq'],
          	},
			},
			{name:'destination',index:'destination', align:"left", width:100, editable: true, 
				editoptions:{
				dataInit: function (elem) {
					myAutocomplete(elem, "/afg_c_dropdown/dropdown_port");
					},
				},
				searchoptions: {
					searchhidden: true,
					sopt:['cn','eq'],
          	},
			},
			{name:'location',index:'location', align:"left", width:100, editable: false, 
				hidden: true,
				edittype:"select",  
				editoptions:
				{
					dataUrl:'<?php echo base_url("afg_c_dropdown/dropdownStuffinglocation")?>',
					dataInit: function (el) 
					{ 
						$(el).css('max-width','100px');  
					}
				},
				searchoptions: {
					searchhidden: true,
					sopt:['cn','eq'],
          	},
			},
			{name:'type',index:'type', align:"center", width:50, editable: true, 
				edittype:"select",
				editoptions:
				{
					value:" : ;FCL:FCL;LCL:LCL;AIR FREIGHT:AIR FREIGHT",
					dataInit: function (el) 
					{ 
						$(el).css('max-width','50px');  
					}
				},
				searchoptions: {
					searchhidden: true,
					sopt:['cn','eq'],
          	},
			},
			{name:'container',index:'container', align:"left", width:70, editable: true, 
				edittype:"select",
				editoptions:
				{
					dataUrl:'<?php echo base_url("afg_c_dropdown/dropdownContainer") ?>',
					dataInit: function (el) 
					{ 
						$(el).css('max-width','70px');  
					}
				},
				searchoptions: {
					searchhidden: true,
					sopt:['cn','eq'],
          	},
			},
			{name:'currency',index:'currency', align:"center", width:70, editable: true, 
				edittype:"select", 
				editoptions:
				{
					value:"USD:USD;RP.:RP.",
					dataInit: function (el) 
					{ 
						$(el).css({'min-width':'70px'}); 
					},
				},
				searchoptions: {
					searchhidden: true,
					sopt:['cn','eq'],
          	},
			},
			{name:'sales_comission',index:'sales_comission', align:"right", width:100, editable: true, 
				formatter: 'number',
    			formatoptions: { decimalSeparator: ".", thousandsSeparator: " ", decimalPlaces: 2, defaultValue: '' },
    			editoptions: { 
    				dataInit: function (el) 
					{ 
						$(el).attr({'onKeyup':'format_number(this,2)','onChange':'format_number(this,2)','title':'Hanya boleh memasukan angka'}); 
						$(el).css({'min-width':'100px'}); 
					}
				},
				searchoptions: {
					searchhidden: true,
					sopt:['cn','eq'],
          	},
			},
			{name:'discount',index:'discount', align:"right", width:70, editable: true,
				formatter: 'number',
    			formatoptions: { decimalSeparator: ".", thousandsSeparator: " ", decimalPlaces: 2, defaultValue: '' },
    			editoptions: { 
    				dataInit: function (el) 
					{ 
						$(el).attr({'onKeyup':'format_number(this,2)','onChange':'format_number(this,2)','title':'Hanya boleh memasukan angka'}); 
						$(el).css({'min-width':'70px'});
					},
				},
				searchoptions: {
					searchhidden: true,
					sopt:['cn','eq'],
          	},
			},
			{name:'price',index:'price', align:"right", width:70, editable: true, 
				formatter: 'number',
    			formatoptions: { decimalSeparator: ".", thousandsSeparator: " ", decimalPlaces: 2, defaultValue: '' },
    			editoptions: { 
    				dataInit: function (el) 
					{ 
						$(el).attr({'onKeyup':'format_number(this,2)','onChange':'format_number(this,2)','title':'Hanya boleh memasukan angka'}); 
						$(el).css({'min-width':'70px'});
					},
				},
				searchoptions: {
					searchhidden: true,
					sopt:['cn','eq'],
          	},
			},
			{name:'base_rate',index:'base_rate', align:"left", width:70, editable: false, 
				hidden: true, 
				edittype:"select",  
				editoptions:
				{
					dataUrl:'<?php echo base_url("afg_c_dropdown/dropdownBaserate")?>',
					dataInit: function (el) 
					{ 
						$(el).css({'min-width':'70px'});
					},
				},
				searchoptions: {
					searchhidden: true,
					sopt:['cn','eq'],
          	},
			},
			{name:'mchgs',index:'mchgs', align:"right", width:50, 
				editable: true, 
				editoptions:{
					dataInit: function (el) { 
						$(el).attr({'onKeyup':'format_number(this,2)','onChange':'format_number(this,2)','title':'Hanya boleh memasukan angka'}); 
						$(el).css({'min-width':'50px','text-transform':'uppercase'});
					},
					dataEvents: 
					[{  
						type: 'keydown', 
            		fn: function(e) { 
                		var key = e.charCode || e.keyCode;
                		if (key == 13)//enter
                		{
                    		$("#btnAddDua").click();
                		}
            		}
            	}]
				},
				formatter: 'number',
    			formatoptions: { decimalSeparator: ".", thousandsSeparator: " ", decimalPlaces: 0, defaultValue: '' }, 	
			},
			{name:'remarks',index:'remarks', align:"left", width:50, 
				editable: false, 
				hidden: true, 
				editoptions: { 
					dataInit: function (el) 
					{ 
						$(el).css({'min-width':'50px'});
					},
				}
			},
			{name:'rate_rgl',index:'rate_rgl', align:"center", width:40, 
				editable: false, 
				hidden: true, 
				edittype:"checkbox", 
				editoptions:{
					value:"1:0",
					dataInit: function (el) 
					{ 
						$(el).css({'min-width':'70px'});
					},
				}, 
				formatter: "checkbox",
			},
			{name:'approval',index:'approval', align:"left", width:50, 
				editable: false, 
				hidden: true, 
				editoptions: { 
					dataInit: function (el) 
					{ 
						$(el).css({'min-width':'50px'});
					},
				}
			},
			{name:'space',index:'space', align:"left", width:30,},
		], 
		sortname: "id",
		sortorder: "asc",
      loadonce : false,
		shrinkToFit: false,
  		viewrecords: true,
      multiselect:true, 
	//	modal: true,
		rownumbers: true,
		rowNum: 5,
	//	top: 20,
		width: 1000,
		height: 150,
		rowList:[5,10,15],
		gridview: true,
		caption: false,
		cellEdit: true,
		editurl:'selling_rate/manage_data',
		cellsubmit:'clientArray',
		pager:'#page-selling',
		
		gridComplete : function()
    	{
    		//$("#btnInsertSeling").children(".ui-pg-div").width("133px");
    	},
			
		loadComplete: function(data) {
			$("#cb_"+grid[0].id).hide();
			var ids = grid.jqGrid('getDataIDs');
			if (ids.length>=1) {
    			grid.jqGrid('editCell',ids.length,3,true);
    		}
		},
    	/*
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
		*/
		afterSaveCell: function(rowid, cellname, value, iRow, iCol) {
			saving_data(rowid, cellname, value, iRow, iCol);
			if(event.keyCode==13) 
			{
				grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
			}
    	},
    	
    	afterAddRow: function(rowid, cellname, value, iRow, iCol){
			//alert("")
    	},
    	
	})
	
	grid.navGrid('#page-selling',{ edit: false, add: false, del: true, refresh: true, <?php echo 'search: false,' ?> },
		{},
		{},
		{},
		{},
		{}
	)
	
	grid.navButtonAdd('#page-selling',{ 
			caption:"",
			id:"btnInsertSelling", 
			buttonicon:"ui-icon-plus", 
			onClickButton: insert_data, 
			title:"Insert Data Selling Price", 
			cursor: "pointer",
			id:"btnAddDua",
	})
	
//	grid.jqGrid('GridUnload');
	/*
	grid.inlineNav('#page-selling',{ 
		edit: false, 
		editicon: "ui-icon-pencil", 
		add: true, 
		addicon:"ui-icon-plus", 
		save: false, 
		saveicon:"ui-icon-disk", 
		cancel: false, 
		cancelicon:"ui-icon-cancel",
   	addParams : 
   	{
   		useFormatter : true,
   		position :"last",
   	},
   	editParams : {
   		useFormatter : true,
   	}
	})
	*/
}

function saving_data(rowid, cellname, value, iRow, iCol)
{
	var grid = $("#table-selling"), i;
	var myData = grid.getRowData(rowid);
	$.ajax
	({
		type	:"POST",
		url	:window.location.origin+'/pricelist/selling_rate/saving_data',
		data	:
		{
			id		 : rowid,
			myData : myData,
		},
		success:function(response)
		{
			var data = $.parseJSON(response);
		}
	})
}

function insert_data() {
	var grid = $("#table-selling"), i;
	$.ajax
	({
		type	:"POST",
		url	:window.location.origin+'/pricelist/selling_rate/insert_data',
		data	:
		{
			id_selling : $("input[id='idSelling']").val(),
		},
		success:function(response)
		{
			var data = $.parseJSON(response);
			if(data.status==1){
				var dataSatu = [{'id':data.id}];
				grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
			}
		}
	})
}

function save_data_table(rowid, cellname, value, iRow, iCol)
{

	if(rowid.substr(0,1)== 'j'){
		var oper = 'add';
	}
	else
	{
		var oper = 'edit';	
	}
	
	if(cellname=='sales_comission' || cellname=='discount' || cellname=='price'){
		value = value.replace(/\ /g,'');
	}
	
	emkl = $("#table-selling").jqGrid('getCell',rowid,'of_emkl')
	
	var DataDetail = 
	{
		oper : oper,
		id : rowid,
		id_selling : $("input[id='idSelling']").val(),
		kolom	: cellname,
		isi	: value,
		cek	: iRow,
		ceking	: $("input[id='codeItemService']").val(),
		of_emkl : emkl,
	}
	
	$.ajax({
		type: "POST", 
		url : "<?php echo base_url('pricelist/selling_rate/manage_data')?>",
		data: DataDetail,
		success:function(data)
		{
			$("#table-selling").jqGrid("setGridParam",  { datatype: 'json', } ).trigger("reloadGrid");
		}
	})

}

// Isi dropdown "Dari" (kolom di table_listing) secara manual dari
// get_sales_options_json(), karena endpoint itu balikin JSON murni
// (bukan HTML <select> siap pakai seperti yang diharapkan mekanisme
// dataUrl bawaan jqGrid). Dipakai oleh beforeShowForm ADD & EDIT.
//
// idSelling  : diisi kalau mode Edit (record sudah ada) -- endpoint akan
//              cari kode_shipper dari a_selling berdasarkan ini.
// kodeShipper: diisi kalau mode Add (record baru, belum ada id_selling) --
//              fallback langsung ke kode_shipper customer yang aktif.
// selectCurrentName: nama (teks) yang lagi kepilih saat ini (dari cell
//              grid), dipakai buat preselect opsi yang cocok saat Edit.
function populateDariDropdown($form, idSelling, kodeShipper, selectCurrentName)
{
	var $select = $form.find("#dari");
	$select.empty().append('<option value="">Memuat...</option>');

	var reqParams = { id_selling: idSelling || '', kode_shipper: kodeShipper || '' };

	$.ajax({
		type: 'POST',
		url: "<?php echo base_url('pricelist/selling_rate/get_sales_options_json')?>",
		data: reqParams,
		dataType: 'json',
		success: function (data) {
			// DIAGNOSTIK SEMENTARA: buka Console (F12) buat lihat persis
			// parameter yang dikirim & respons mentah dari server. Hapus log
			// ini kalau sudah tidak dibutuhkan lagi.
			console.log('[populateDariDropdown] request:', reqParams, '| response:', data);

			$select.empty();
			if (data && data.length > 0) {
				$.each(data, function (i, item) {
					$select.append($('<option></option>').val(item.user_id).text(item.fullname));
				});
				if (selectCurrentName) {
					// Endpoint balikin user_id (angka), sedangkan grid cuma
					// punya NAMA hasil resolve -- jadi dicocokkan lewat teks,
					// bukan value.
					$select.find('option').each(function () {
						if ($(this).text() === selectCurrentName) {
							$select.val($(this).val());
							return false;
						}
					});
				}
			} else {
				$select.append('<option value="">(Tidak ada sales terdaftar untuk customer ini)</option>');
			}
		},
		error: function (xhr) {
			console.log('[populateDariDropdown] ERROR:', reqParams, xhr.responseText);
			$select.empty().append('<option value="">Gagal memuat data sales</option>');
		}
	});
}

function table_listing()
{
	var grid = $("#table-listing"), i;

	grid.jqGrid(
	{
		url	:"<?php echo base_url('pricelist/selling_rate/get_table_listing')?>",
		mtype : "post",
		datatype: "json",
		postData: {
			shipper_id : $('#shipperAdd').val(),
		},
		colNames:
		[
			'ID','','Ref No','Tanggal','Valid','Kepada','UP','Type','Dari','Remarks',''
		],
    	colModel:
		[
			{name:'id_selling',index:'id_selling', align:"center", width:50, editable:false, hidden:true, key:true},
			// kode_shipper dikirim tersembunyi supaya baris baru otomatis terikat ke
			// customer yang sedang aktif di tab ini (tidak bisa diketik bebas oleh user).
			{name:'kode_shipper',index:'kode_shipper', hidden:true,
				editable:true,
				editrules:{edithidden:true},
				editoptions:{
					// edithidden:true wajib supaya elemen inputnya ADA di DOM form
					// (dibutuhkan supaya JS auto-fill bisa mengisinya), tapi barisnya
					// sendiri kita sembunyikan total dari tampilan -- sebelumnya baris
					// ini muncul sebagai kotak kosong tanpa label di atas "Ref No".
					dataInit: function (el) { $(el).closest('tr').hide(); }
				}
			},
			{name:'ref_no',index:'ref_no', align:"center", width:90,
				editable:true,
				editrules:{required:true, edithidden:true},
				editoptions:{ size:20 }
			},
			{name:'tanggal',index:'tanggal', align:"center", width:90,
				editable:true,
				editrules:{required:true, date:true, edithidden:true},
				editoptions:{
					size:20,
					dataInit:function (el) {
						$(el).datepicker({ dateFormat:'yy-mm-dd', changeMonth:true, changeYear:true, showButtonPanel:true });
					}
				}
			},
			{name:'valid',index:'valid_raw', align:"center", width:90,
				// index sengaja diarahkan ke 'valid_raw' (Ymd mentah), BUKAN 'valid'
				// sendiri -- soalnya nilai yang tampil di 'valid' sudah diformat jadi
				// dd-mm-yyyy oleh jin_date_str(), yang kalau diurutkan sebagai teks
				// biasa hasilnya SALAH secara kronologis (tahun ada di belakang).
				editable:true,
				editrules:{required:true, date:true, edithidden:true},
				editoptions:{
					size:20,
					dataInit:function (el) {
						$(el).datepicker({ dateFormat:'yy-mm-dd', changeMonth:true, changeYear:true, showButtonPanel:true });
					}
				}
			},
			{name:'kepada',index:'kepada', align:"left", width:260,
				editable:true,
				editrules:{required:true},
				editoptions:{ size:40, dataInit:function (el) { $(el).css('text-transform','uppercase'); } }
			},
			{name:'attn',index:'attn', align:"left", width:150,
				editable:true,
				editoptions:{ size:30, dataInit:function (el) { $(el).css('text-transform','uppercase'); } }
			},
			{name:'of_emkl',index:'of_emkl', align:"center", width:120,
				formatter:of_emkl,
				editable:true,
				edittype:'select',
				editoptions:{ value:"0:FREIGHT CHARGES;1:MISCELLANEOUS" }
			},
			{name:'dari',index:'dari', align:"center", width:110,
				// PENTING: get_sales_options_json() mengembalikan JSON murni
				// (bukan HTML <select>), jadi TIDAK bisa pakai mekanisme
				// dataUrl bawaan jqGrid (itu mengharapkan responsnya sudah
				// berupa markup <select> jadi). Opsinya diisi manual lewat
				// populateDariDropdown() di beforeShowForm (ADD & EDIT).
				editable: true,
				edittype: "select",
				editoptions: {
					value: ":Memuat..."
				}
			},
			// NOTE: kolom "kurs_selling" DIHAPUS -- setelah controller/model
			// aslinya diupload, ternyata kolom itu tidak ada di tabel a_selling
			// sama sekali (lihat a_selling.sql), dan manage_data_selling() di
			// controller juga sudah tidak pernah mengirimkannya ke database.
			{name:'remarks',index:'remarks', align:"left", width:160,
				editable:true,
				editoptions:{ size:60, dataInit:function (el) { $(el).css('text-transform','uppercase'); } }
			},
			// Tanggal valid MENTAH (format Ymd, tanpa format tampilan) --
			// khusus dipakai rowattr() di bawah untuk hitung H-7. Kolom
			// "valid" yang tampil di atas SENGAJA tidak diutak-atik supaya
			// tidak mengganggu alur edit/simpan tanggal yang sudah jalan.
			{name:'valid_raw',index:'valid_raw', hidden:true, editable:false},
		],
		rowNum: 10000,
		autoencode: true,
		loadonce: true,
		sortable: true,
		// Urut default: tanggal Valid TERBARU paling atas. Pakai 'valid_raw'
		// (Ymd mentah) supaya urutannya benar secara kronologis -- 'valid'
		// yang tampil sudah diformat dd-mm-yyyy, kalau itu yang diurutkan
		// hasilnya salah (diurutkan sebagai teks biasa, bukan tanggal).
		sortname: "valid_raw",
		sortorder: "desc",
		width: 1080,
		height: 400,
		rowList:[40,80,120],
		caption: 'Data Selling',
		// Warnai baris kalau harga (kolom "Valid") sudah/mau expired (<= 7 hari).
		// Warnai baris kalau harga (kolom "Valid") MASIH VALID tapi mau
		// expired dalam <= 7 hari. Yang SUDAH lewat (diffDays negatif)
		// sengaja TIDAK diwarnai lagi -- grid ini menampilkan semua surat
		// termasuk yang sudah expired (buat riwayat), jadi perlu batas bawah
		// eksplisit di sini (beda dengan grid utama yang query-nya sendiri
		// sudah otomatis cuma ambil valid >= hari ini).
		rowattr: function (rowData) {
			if (!rowData.valid_raw) return {};
			var today = new Date();
			var validDate = new Date(
				rowData.valid_raw.substring(0,4),
				rowData.valid_raw.substring(4,6)-1,
				rowData.valid_raw.substring(6,8)
			);
			var diffDays = Math.ceil((validDate - today) / (1000*60*60*24));
			if (diffDays >= 0 && diffDays <= 7) {
				return { "class": "row-expiring-soon" };
			}
			return {};
		},
		editurl: "<?php echo base_url('pricelist/selling_rate/manage_data_selling')?>",
		pager:'#page-sellisting',

		loadComplete: function () {
			var $self = $(this);
			if ($self.jqGrid("getGridParam", "datatype") === "json") {
				setTimeout(function () {
					$self.trigger("reloadGrid"); // Perbaikan sorting sisi client
				}, 50);
			}
		},

		ondblClickRow: function(rowid, e){
			$('#idSelling').val(rowid);
			get_data('pilih');
			$('#tabs-lading ul li a[href="#tab-2"]').trigger('click');
		}
	}).navGrid('#page-sellisting',
		{ edit: true, add: true, del: true, search: true, refresh: true },
		{ // EDIT
			width: 480, height:"auto", closeAfterEdit: true, reloadAfterEdit: true, modal: true,
			editCaption: "Edit Data Selling",
			beforeShowForm: function (form) {
				var $form = $(form);
				var selRowId = $('#table-listing').jqGrid('getGridParam', 'selrow');
				var currentDariName = selRowId ? $('#table-listing').jqGrid('getCell', selRowId, 'dari') : '';
				populateDariDropdown($form, $('#idSelling').val(), '', currentDariName);
			},
			afterSubmit: function () {
				$('#table-listing').jqGrid("setGridParam", {datatype:'json'}).trigger("reloadGrid");
				return [true, ''];
			}
		},
		{ // ADD
			width: 480, height:"auto", closeAfterAdd: true, reloadAfterAdd: true, modal: true,
			addCaption: "Tambah Data Selling",
			beforeShowForm: function (form) {
				var $form = $(form);

				// Customer di form Add ini SELALU customer yang modalnya sedang
				// terbuka (bukan dipilih ulang di sini) -- pencarian customer
				// baru ada di grid utama $("#table"), lihat v_selling_rate.php.
				var currentShipperCode = $('#shipperId').val() || '';
				var currentShipperName = $('#shipperAdd').val() || '';

				// Tanggal default = hari ini (format harus cocok dengan datepicker
				// kolom ini: 'yy-mm-dd').
				var today = new Date();
				var todayStr = today.getFullYear() + '-'
					+ String(today.getMonth()+1).padStart(2,'0') + '-'
					+ String(today.getDate()).padStart(2,'0');
				$form.find("#tanggal").val(todayStr);

				// "Kepada" dikunci ke customer yang sedang aktif -- read-only,
				// tidak bisa diganti dari form ini.
				var $kepada = $form.find("#kepada");
				$kepada.val(currentShipperName).attr('readonly', true).css('background-color', '#eee');
				$form.find("#kode_shipper").val(currentShipperCode);

				// Ref No digenerate otomatis & dikunci read-only (selalu unik,
				// tidak mengikuti urutan penomoran per-shipper yang lama --
				// lihat generate_unique_ref_no() di server).
				var $refNo = $form.find("#ref_no");
				var $attn  = $form.find("#attn");
				$refNo.val('...').attr('readonly', true).css('background-color', '#eee');

				// Ambil data resmi (nama shipper, UP dari cp_name+cp_email
				// mstshipper, dan ref_no baru) dari server berdasarkan kode
				// shipper yang sedang aktif.
				$.ajax({
					type: 'POST',
					url: "<?php echo base_url('pricelist/selling_rate/get_shipper_context')?>",
					data: { shipper_id: currentShipperCode },
					dataType: 'json',
					success: function (res) {
						if (!res || res.status != 1) {
							show_messages('Gagal mengambil data customer.');
							$refNo.val('');
							return;
						}
						$kepada.val(res.shipper_name);
						$refNo.val(res.ref_no);
						if (res.attn) { $attn.val(res.attn); }

						// "Dari" (sales): pakai shipper_id yang SAMA dengan yang
						// baru saja dikonfirmasi valid oleh get_shipper_context()
						// di atas (bukan #shipperId mentah-mentah) -- supaya
						// query pencarian sales-nya pasti mengacu ke baris
						// mstshipper yang sama persis dengan yang dipakai untuk
						// resolve nama/UP di atas.
						populateDariDropdown($form, '', res.shipper_id, '');
					},
					error: function () {
						show_messages('Gagal mengambil data customer.');
						$refNo.val('');
					}
				});
			},
			afterSubmit: function () {
				$('#table-listing').jqGrid("setGridParam", {datatype:'json'}).trigger("reloadGrid");
				return [true, ''];
			}
		},
		{ // DELETE
			width: "auto", height:"auto", closeAfterDelete: true, reloadAfterDelete: true, modal: true,
			delCaption: "Hapus Data",
			msg: "Yakin ingin menghapus data selling ini beserta seluruh baris harganya?",
			afterSubmit: function () {
				$('#table-listing').jqGrid("setGridParam", {datatype:'json'}).trigger("reloadGrid");
				return [true, ''];
			}
		},
		{ multipleSearch: false }, // SEARCH
		{} // VIEW
	);

	// Tombol Cetak di toolbar grid "Data Selling" -- manggil print_data() yang
	// sudah ada (sama seperti $('#btnCetak').attr('onClick','print_data()').val('CETAK')
	// di toolbar header lama), supaya cetak tetap bisa dilakukan langsung dari tab ini.
	grid.navButtonAdd('#page-sellisting', {
		caption: "Cetak",
		buttonicon: "ui-icon-print",
		title: "Cetak Selling Rate",
		onClickButton: function () {
			if (!$('#idSelling').val()) {
				show_messages('Pilih (dobel klik) salah satu baris dulu sebelum mencetak.');
				return;
			}
			print_data();
		}
	});

	// Tombol Copy -- duplikat data selling terpilih (header + semua baris
	// harganya) jadi entri baru dengan ref_no baru. Fungsi copy_data() sudah
	// ada dari sebelumnya (masih dipakai), cuma dulu triggernya lewat tombol
	// di toolbar header lama yang sekarang sudah dihapus.
	grid.navButtonAdd('#page-sellisting', {
		caption: "Copy",
		buttonicon: "ui-icon-copy",
		title: "Copy Selling Rate",
		onClickButton: function () {
			if (!$('#idSelling').val()) {
				show_messages('Pilih (dobel klik) salah satu baris dulu sebelum copy.');
				return;
			}
			copy_data();
		}
	});
}
function of_emkl (cellvalue, options, rowObject)
		{
			if (cellvalue == 1)
			return "MISCELLANEOUS";
			else if (cellvalue == 0)
			return "FREIGHT CHARGES";
		}

function exit_data()
{
	$('#Form-Destination').dialog('close')
}

// ------------------------------------------------------------------
// DIHAPUS: new_data(), edit_data(), delete_data(), save_data().
// Semua tambah/ubah/hapus "Data Selling" sekarang lewat grid
// #table-listing (fungsi table_listing()), yang punya form
// Add/Edit/Delete bawaan jqGrid ke endpoint manage_data_selling.
// Tombol #btnNew, #btnEdit, dan #btnDelete (dulu rangkap jadi SAVE)
// perlu dihapus juga dari file template header (file tersebut tidak
// ikut diunggah, jadi mohon dihapus manual di sana).
// ------------------------------------------------------------------

function cancel()
{
	// Dipertahankan sebagai fungsi refresh sederhana untuk kompatibilitas
	// jika masih dipanggil dari tombol lama di template header.
	get_data('pilih');
}

function next_data()
{
	get_data('next');
}

function prev_data()
{
	get_data('prev');
}

function formatUang(input){
	var num = input.value.replace(/\ /g,'');
	if(!isNaN(num)){
   	if(num.indexOf('.') > -1){
    		num = num.split('.');
    		num[0] = num[0].toString().split('').reverse().join('').replace(/(?=\d*\.?)(\d{3})/g,'$1 ').split('').reverse().join('').replace(/^[\ ]/,'');
    		if(num[1].length > 2){
				var message = 'Only 2 ( two ) desimal allowed';
				show_messages(message);
    			num[1] = num[1].substring(0,num[1].length-1);
    		} input.value = num[0]+'.'+num[1];
    	} else{ input.value = num.toString().split('').reverse().join('').replace(/(?=\d*\.?)(\d{3})/g,'$1 ').split('').reverse().join('').replace(/^[\ ]/,'') };
	}
	else
	{ 
		var message = 'Only numeric allowed';
		show_messages(message);
    	input.value = input.value.substring(0,input.value.length-1); 
	}
}

function gubahUang(num){
	if(num.indexOf('.') > -1){
		num = num.split('.');
 		num[0] = num[0].toString().split('').reverse().join('').replace(/(?=\d*\.?)(\d{3})/g,'$1 ').split('').reverse().join('').replace(/^[\ ]/,'');
		return num[0];
	}
	return num.toString().split('').reverse().join('').replace(/(?=\d*\.?)(\d{3})/g,'$1 ').split('').reverse().join('').replace(/^[\ ]/,'');
}

if(!window.location.origin) {
	window.location.origin=window.location.protocol+'//'+window.location.hostname+(window.location.port+':'+window.location.port)
}

serialize = function(obj, prefix) {
  	var str = [], p;
  	for(p in obj) {
    	if (obj.hasOwnProperty(p)) {
			var k = prefix ? prefix + "[" + p + "]" : p, v = obj[p];
			str.push((v !== null && typeof v === "object") ?
        	serialize(v, k) :
        	encodeURIComponent(k) + "=" + encodeURIComponent(v));
    	}
  	}
  	return str.join("&");
}

function print_data()
{
	var data = {};
	var to = $('#shipperAdd').val();
	var from = $('#fromForm').val();
	var tanggal = $('#dateForm').val();
	var valid = $('#validForm').val();
	var attn = $('#attnForm').val();
	var shipper = $('#shipperId').val();
	var remarks = $('#remarkForm').val();
	var id	 = $("input[id='idSelling']").val();
	
	data.to = to;
	data.from = from;
	data.date = tanggal;
	data.valid = valid;
	data.attn = attn;
	data.shipper = shipper;
	data.remarks = remarks;
	data.id = id;
	
	var url=serialize(data)
	
   if(tanggal == '/ /' && valid == '/ /'){
   	var message = 'Please insert [ DATE ] [ VALID ] first';
		show_messages(message);
   	return false;
   }
   else
   {
   	var datetgl = tanggal.substring(6,tanggal.length)+tanggal.substring(3,tanggal.length-5)+tanggal.substring(0,tanggal.length-8); 
   	var datevalid = valid.substring(6,valid.length)+valid.substring(3,valid.length-5)+valid.substring(0,valid.length-8);
		var date  = datetgl+datevalid;
		
		$("#PRINT" ).dialog({
			position: "top",
			autoOpen: true,
			resizable: true,
			height: 'auto',
			width: 980,
			modal: true,
			title: "SELLING PRICE TO "+to,
			buttons : {  },
			close: function()  {$(this).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>')}
		});
		
		$('#PRINT').html('<object id="form-data" data="selling_rate/print_data?'+url+'" width=" 950px" height="600" type="application/pdf" \>');
	
	
	}
}

function show_quotation() {
	var data = [];
	var to = $('#shipperAdd').val();
	var from = $('#fromForm').val();
	var tanggal = $('#dateForm').val();
	var valid = $('#validForm').val();
	var attn = $('#attnForm').val();
	var shipper = $('#shipperId').val();
	var remarks = $('#remarkForm').val();
	var id	 = $("input[id='idSelling']").val();
	data.to = to;
	data.from = from;
	data.date = tanggal;
	data.valid = valid;
	data.attn = attn;
	data.shipper = shipper;
	data.remarks = remarks;
	data.id = id;
	var url=serialize(data);
	$.ajax({
		async: true,
		type: "POST", 
		url : window.location.origin+'/pricelist/selling_rate/open_form_quotation',
		data:{url},
		success:function(XMLHttpRequest)
		{
			$( "#form-report" ).dialog(
				{
					autoOpen: true,
					resizable: false,
					height: 160,
					width: 320,
					modal: true,
					title :"CETAK QUOTATION",
					close: function() {	}
			});
			$( "#form-report" ).html(XMLHttpRequest);
		}
	})
}

function myAutocomplete(elem, url) {
        $(elem).autocomplete({
        minLength: 3,
        source: url,
        autoFocus: true,
        scroll: true,
        select: function( event, ui ) { $(this).focus() }
    }).focus(function() {
        $(this).autocomplete("search", "");
    }).live("blur", function(event) {
        var autocomplete = $(this).data("autocomplete");
        var matcher = new RegExp("^" + $.ui.autocomplete.escapeRegex($(this).val()) + "$", "i");
        autocomplete.widget().children(".ui-menu-item").each(function() 
          {
            var item = $(this).data("item.autocomplete");
            if (matcher.test(item.label || item.value || item)) {
                //There was a match, lets stop checking
                autocomplete.selectedItem = item;
                return;
            }
        });

        if (autocomplete.selectedItem) {
            autocomplete._trigger("select", event, {
                item: autocomplete.selectedItem
            });
        //there was no match, clear the input
        } else {
            $(this).val('');
        }
        });
}

function handleKodeRefClick(rowId) {
			$('#idSelling').val(rowId);
    		get_data('pilih');
    		$('#tabs-lading ul li:last a').trigger('click');
}


function buying_rate() 
{
	var grid = $("#tables"), i;
	var user_id = <?php echo $this->session->userdata('user_id'); ?>;
	var editPermission = !(user_id !== 0 && user_id !== 23);
	var addPermission = !(user_id !== 0 && user_id !== 23);
	var delPermission = !(user_id !== 0 && user_id !== 23);
	grid.jqGrid(
	{ 
		url:"<?php echo base_url('pricelist/export_rate/get_table_data')?>",
		mtype : "post",
		datatype: "json", 	
		colNames:
		[
			'DESTINATION','SHIPPING LINE','SCAC','20FT','40FT','40HC','LCL','Min','VALID UNTIL','FREE TIME DND', 'REMARK'
		],
    	colModel:
		[
			{name:'destination', index:'destination', search: true, search: true, searchoptions:{sopt:['cn'], dataUrl:"/afg_c_dropdown/dropdown_port",searchhidden: true},
				editable: true,
				editrules: {required: true, edithidden: true},
				editoptions:{
				dataInit: function (elem) {
					var dd_des = "/afg_c_dropdown/dropdown_port";
					myAutocomplete(elem, dd_des);
					},
				},
			},
			{name:'carier',index:'carier', align:"left", width:70,
				editable: true, 
				editoptions:{
				dataInit: function (elem) {
					var dd_ship = "/afg_c_dropdown/dropdown_shippco";
					myAutocomplete(elem, dd_ship);
					},
				},
			},
			{name:'scac',index:'scac', align:"left", width:30, editable: true,
				editable: true, 
				editoptions:{
				dataInit: function (elem) {
					var dd_ship = "/afg_c_dropdown/dropdown_scac";
					myAutocomplete(elem, dd_ship);
					},
				},
			},
			{ 
            name: "duapuluh", 
            index: "duapuluh", 
            width:25, 
            align: "right", 
            editable: true, 
            formatter: "currency", 
            formatoptions: { 
              prefix: '$', 
              decimalSeparator: '.', 
              thousandsSeparator: ',', 
              decimalPlaces: 2 
            },
            editrules: {
              number: true,
              custom: true,
              custom_func: function(value, colname) {
                // Remove currency symbols and thousand separators
                var cleanValue = value.replace(/[\$,]/g, '');
                // Trim any whitespace
                cleanValue = cleanValue.trim();
                if (cleanValue === "" || isNaN(parseFloat(cleanValue))) {
                  return [false, colname + ": Please enter a valid monetary amount."];
                }
                return [true, ""];
              }
            }
          },
		  	{ 
            name: "empatpuluh", 
            index: "empatpuluh", 
            width: 25, 
            align: "right", 
            editable: true, 
            formatter: "currency", 
            formatoptions: { 
              prefix: '$', 
              decimalSeparator: '.', 
              thousandsSeparator: ',', 
              decimalPlaces: 2 
            },
            editrules: {
              number: true,
              custom: true,
              custom_func: function(value, colname) {
                // Remove currency symbols and thousand separators
                var cleanValue = value.replace(/[\$,]/g, '');
                // Trim any whitespace
                cleanValue = cleanValue.trim();
                if (cleanValue === "" || isNaN(parseFloat(cleanValue))) {
                  return [false, colname + ": Please enter a valid monetary amount."];
                }
                return [true, ""];
              }
            }
          },
		  	{ 
            name: "empatpuluhhc", 
            index: "empatpuluhhc", 
            width: 25, 
            align: "right", 
            editable: true, 
            formatter: "currency", 
            formatoptions: { 
              prefix: '$', 
              decimalSeparator: '.', 
              thousandsSeparator: ',', 
              decimalPlaces: 2 
            },
            editrules: {
              number: true,
              custom: true,
              custom_func: function(value, colname) {
                // Remove currency symbols and thousand separators
                var cleanValue = value.replace(/[\$,]/g, '');
                // Trim any whitespace
                cleanValue = cleanValue.trim();
                if (cleanValue === "" || isNaN(parseFloat(cleanValue))) {
                  return [false, colname + ": Please enter a valid monetary amount."];
                }
                return [true, ""];
              }
            }
          },
		  	{ 
            name: "lcl", 
            index: "lcl", 
            width: 25, 
            align: "right", 
            editable: true, 
            formatter: "currency", 
            formatoptions: { 
              prefix: '$', 
              decimalSeparator: '.', 
              thousandsSeparator: ',', 
              decimalPlaces: 2 
            },
            editrules: {
              number: true,
              custom: true,
              custom_func: function(value, colname) {
                // Remove currency symbols and thousand separators
                var cleanValue = value.replace(/[\$,]/g, '');
                // Trim any whitespace
                cleanValue = cleanValue.trim();
                if (cleanValue === "" || isNaN(parseFloat(cleanValue))) {
                  return [false, colname + ": Please enter a valid monetary amount."];
                }
                return [true, ""];
              }
            }
          },
		  {name:'mchgs',index:'mchgs', align:"right", width:20, 
				editable: true, 
				editoptions:{
					dataInit: function (el) { 
						$(el).attr({'onKeyup':'format_number(this,2)','onChange':'format_number(this,2)','title':'Hanya boleh memasukan angka'}); 
						$(el).css({'min-width':'50px','text-transform':'uppercase'});
					},
					dataEvents: 
					[{  
						type: 'keydown', 
            		fn: function(e) { 
                		var key = e.charCode || e.keyCode;
                		if (key == 13)//enter
                		{
                    		$("#btnAddDua").click();
                		}
            		}
            	}]
				},
				formatter: 'number',
    			formatoptions: { 
					suffix: ' Cbm', 
					decimalSeparator: '.', 
					thousandsSeparator: ',', 
					decimalPlaces: 0
				}, 	
			},
		  { 
            name: "valid", 
            index: "valid", 
            width: 100,
			hidden: false,		
            editable: true, 
            editrules: {
              required: true,
              date: true,
			  edithidden: true
            },
            editoptions: {
              size: 20,
              dataInit: function (element) {
                // Initialize jQuery UI datepicker with dd-mm-yyyy format.
                $(element).datepicker({
                  dateFormat: 'yy-mm-dd', // For jQuery UI, 'dd-mm-yy' outputs date as dd-mm-yyyy.
                  changeMonth: true,
                  changeYear: true,
                  showButtonPanel: true
                });
              }
            }
          },
		  {name:'condition',index:'condition', align:"right", width:40,
				editable: true, 
				editoptions:{
					dataInit: function (el) { 
						$(el).attr({'title':'Hanya boleh memasukan angka'}); 
					},
					dataEvents: 
					[{  
						type: 'keydown', 
            		fn: function(e) { 
                		var key = e.charCode || e.keyCode;
                		if (key == 13)//enter
                		{
                    		$("#btnAddDua").click();
                		}
            		}
            	}]
				},
				formatter: 'number',
    			formatoptions: { 
					suffix: ' Days',
					decimalPlaces: 0 // Ensures no decimal values
				}, 	
			},
			{name:'remarks',index:'remarks', align:"left", width:230, editable: true,
				editoptions: { size: 230, dataInit: function (el) { $(el).css('text-transform', 'uppercase'); }}
			},
		], 
		sortname: "destination",
		sortorder:'',
  		multiselect:true,
		loadonce : false,
		cellEdit: false,
		rowNum:20,
		width: 1480,
		height: 'auto',
		rowList:[20,40,60,80,100],
		viewrecords: true,
		rownumbers: true,
		gridview: true,
		modal: true,
		caption: '',
		editurl:'export_rate/manage_detail',
		cellsubmit:'clientArray',
		pager:'#pagers',
		grouping: true,
		groupingView: {
			groupField: ["destination", "valid"], // Group by destination and valid
			groupColumnShow: [false, false], // Hide grouped columns
			groupOrder: ["asc", "asc"], // Sort groups in ascending order
			groupSummary: [false, false], // Disable group summary
			groupCollapse: false, // Do not collapse groups initially
			groupDataSorted: true, // Ensure grouped data remains sorted
			groupText: [
				'<span class="group-header">{0}</span>',
				'<span class="group-subheader">Valid until {0}</span>'
			]
		},	
		loadComplete: function() {
        var grid = $("#tables");
		grid.find("th[aria-labelledby='destination']").hide();
        // Iterate through rows to collect "valid" column data
        var validDataMap = {};
        grid.find("tr.jqgrow").each(function () {
            var rowId = $(this).attr("id");
            var rowData = grid.jqGrid("getRowData", rowId);
            validDataMap[rowData.destination] = rowData.valid; // Map destination to valid date
        });

        // Update group headers dynamically
        $(".jqgroup").each(function () {
            var groupText = $(this).text();
            var destination = groupText.split(" - ")[0]; // Extract destination name
            if (validDataMap[destination]) {
                $(this).find(".group-valid").text(validDataMap[destination]);
            }
        });
		$(".jqgroup:contains('Valid until')").each(function() {
        // Add column headers only under "Valid" groups
        var columnRow = $("<tr class='custom-column-header'></tr>");
        var columnNames = [
            "","SHIPPING LINE", "SCAC", "20FT", "40FT", "40HC", "LCL", "Min", "FREE DND", "REMARK"
        ];

        columnNames.forEach(function(col) {
            columnRow.append("<td style='font-weight:bold; text-align:center;'>" + col + "</td>");
        });

			$(this).after(columnRow);
		});

		// Hide standard column headers
		 grid.closest(".ui-jqgrid").find(".ui-jqgrid-hdiv").hide();
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
		
	}).navGrid('#pagers',{ edit: editPermission, add: addPermission, del: delPermission }, 
    { height: "auto", width: "auto", top: 3, left: 250, closeAfterEdit: true, reloadAfterEdit: true, modal: false, 
      editCaption: "Edit Data",
      afterSubmit: function () {
          $("#grid").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
          return [true];
      }
    }, 
    { height: "auto", width: "auto", top: 3, left: 250, closeAfterAdd: true, reloadAfterAdd: true, modal: false, 
      addCaption: "Insert Data",
      afterSubmit: function () {
          $("#grid").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
          return [true];
      }
    }, 
    { height: "auto", width: "auto" }, 
    { modal: true, height: "auto", width: "auto", closeAfterSearch: true, multipleSearch: false }, 
    {}
	);

};

</script>


