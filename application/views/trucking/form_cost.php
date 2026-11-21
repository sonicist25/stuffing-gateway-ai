<style type="text/css">
.grid-container {
  display: grid;
  grid-template-columns: auto auto auto auto auto auto auto;
  grid-gap: 10px;
  background-color: #2196F3;
  padding: 10px;
  align-items: center;
  border-radius: 5px;
}

.grid-container > div {
  background-color: linear-gradient(to bottom, #378de5 5%, #79bbff 100%);
  border: none;
  text-align: left;
  font-size: 12px; 
  border-radius: 10px;
}
#button-post,#button-save
	{
	box-shadow:inset 0px 1px 0px 0px #bbdaf7;
	background:linear-gradient(to bottom, #79bbff 5%, #378de5 100%);
	background-color:#79bbff;
	border-radius:6px;
	border:1px solid #84bbf3;
	display:inline-block;
	cursor:pointer;
	color:#ffffff;
	font-family:Arial;
	font-size:11px;
	padding:6px 24px;
	text-decoration:none;
	text-shadow:0px 1px 0px #528ecc;
	}

</style>
<!-- SweetAlert2 -->
<div id="main-page" style="font-size: 11px;">
<div class="grid-container">
	<div>
		Last Posted : <input type="text" id="customer" disabled="disabled" size="30" value="<?php echo $created ?>">
				<input id="kode_ref_cost" disabled="disabled" style="height:25px;" value="<?php echo $kode_ref ?>" type="hidden">
				<input id="post" disabled="disabled" style="height:25px;" value="<?php echo $post ?>" type="hidden">
				<input id="spd_id_cost" value="<?php echo $id ?>" type="hidden">
	</div>	
	<div>
		Last Updated : <input type="text" id="last_edited" disabled="disabled" size="30" value="<?php echo $last_updated ?>">
	</div>	
	<div>
	Kurs : <input type="text" id="kurs_cost"  value="<?php echo $kurs ?>">
	</div>
	<div>
		<input type="button" id ="button-post" value=" POST " onclick="get_proses_post()">
	</div>
	<div>
	<input type="button" id ="button-save" value="SAVE" onclick="save_data()">
	</div>
	<div>
	<input type="checkbox" style="vertical-align: middle;" id="RC-TDK-PROSES"> 
	<label for="RC-TDK-PROSES"  style="vertical-align: middle;">RC tidak diproses</label>
	</div>
	</div>
	<div style="clear:both; height: 20px"></div>
	<div>
		<table id="table-cost"></table>
		<div id="pager-tbl-cost"></div>
	</div>
	<div style="clear: both; height: 20px"></div>
	<div>
		<table id="table-discount"></table>
		<div id="pager-tbl-discount"></div>
	</div>
	<div style="clear: both; height: 20px"></div>
	<div>
		<table id="table-sc"></table>
		<div id="pager-tbl-sc"></div>
	</div>
	
</div>
<script type="text/javascript" >
var base_url = "<?php echo base_url(); ?>";
$(document).ready(function(){
	table_cost();
	table_refund_discount();
	table_refund_sc();
})

function refresh_table(){
	$("#table-cost").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
	$("#table-discount").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
	$("#table-sc").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
}

function tarik_cost(){
	
	var spd_id = $('#spd_id_cost').val();
	var kode_ref = $( '#kode_ref_cost' ).val();
	var kode_refnya = kode_ref.substr(4,3);
	if (kode_refnya == '300'||kode_refnya == '301'||kode_refnya == '302'||kode_refnya == '400'||kode_refnya == '402'||kode_refnya == '600') {
		var gridnya = $("#tabel_sp_detail_export"), i;	
		var destination = gridnya.jqGrid('getCell', spd_id,'destination');	
		var shipper = gridnya.jqGrid('getCell', spd_id,'note').replace(/\ /g,'').replace(/\./g,'').substr(0,10);
	}
	else 
	{
		var gridnya = $("#tabel_sp_detail"), i;
		var destination = gridnya.jqGrid('getCell', spd_id,'destination');	
		var shipper = gridnya.jqGrid('getCell', spd_id,'shipper');
	}
	
	var fcl = $('select[id="dropdownTypeOfTransaction"]').val();
	
	if(fcl =='FCL' || fcl == 'SF IMPORT FCL'){
		var qty = gridnya.jqGrid('getCell', spd_id,'no_pack_qty');
	}
	else 
	{
		var qty = gridnya.jqGrid('getCell', spd_id,'bl_meas');
	}
	
	$.ajax
	({
		type: "POST",
		url : base_url+'/afg_c_cost/tarik_cost',
		data:  
		{ 
			spd 			: spd_id,
			kode_ref 	: $( '#kode_ref_cost' ).val(),
			shipper_id 	: gridnya.jqGrid('getCell', spd_id,'shipper'),
			//agent 		: gridnya.jqGrid('getCell', spd_id,'agent_code'),
			agent 		: $('select[id="dropdownAgent"]').val(),
			sales 		: gridnya.jqGrid('getCell', spd_id,'sales'),
			destination : destination,
			qty 			: qty,
			type 			: $('select[id="dropdownTypeOfTransaction"]').val(),
		},
		success: function(response)
		{
			$("#table-cost").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
		},
		error:function(XMLHttpRequest)
		{
			return false;
		}
	})
}

function tarik_data_refund(table){
	var grid = $("#table-frm-selling"), i;	
	var spd_id = $('#spd_id_cost').val();
	var destination = $("#tabel_sp_detail").jqGrid('getCell', spd_id,'destination');	
	if(destination ==''){
		var destination = $('#dropdownCountries').val();
	}
	var fcl = $('select[id="dropdownTypeOfTransaction"]').val();
	var shipment = $("input[name='shipment_type']:checked").val();
	if(fcl =='FCL' || fcl == 'SF IMPORT FCL' || shipment == 'FCL'){
		var qty = $("#tabel_sp_detail").jqGrid('getCell', spd_id,'no_pack_qty');
	}
	else 
	{
		var qty = $("#tabel_sp_detail").jqGrid('getCell', spd_id,'bl_meas');
	}
	$.ajax
	({
		type: "POST",
		url : base_url+'/afg_c_cost/tarik_data_refund',
		data:  
		{ 
			spd : spd_id,
			kode_ref : $( '#kode_ref_cost' ).val(),
			shipper_id : $("#tabel_sp_detail").jqGrid('getCell', spd_id,'shipper'),
			sales : $("#tabel_sp_detail").jqGrid('getCell', spd_id,'sales'),
			destination : destination,
			qty : qty,
			type : $('select[id="dropdownTypeOfTransaction"]').val(),
			valid : $("#txtETDJKT").val(),
			container : $("select#txtTOC2").val(),
            type : fcl,	
			shipment : shipment,
			table: table,
		},
		success: function(response)
		{
			if(table=='inf_cost_refund_discount'){
				$("#table-discount").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
			} else {
				$("#table-sc").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
			}
		},
		error:function(XMLHttpRequest)
		{
			return false;
		}
	})
}

function tarik_data_refund_sc(){
	tarik_data_refund('inf_cost_refund_sc')
}

function tarik_data_refund_discount(){
	tarik_data_refund('inf_cost_refund_discount')
}
function parseNumber(num) {
    if (!num) return 0;
    num = ("" + num).replace(/,/g, '').replace(/\s/g, '');
    return parseFloat(num) || 0;
}	
function table_cost(){
	var kode_ref = $( '#kode_ref_cost' ).val();
	var spd_id = $('#spd_id_cost').val();
	var kode_refnya = kode_ref.substr(4,3);
	if (kode_refnya == '300'||kode_refnya == '301'||kode_refnya == '302'||kode_refnya == '400'||kode_refnya == '402'||kode_refnya == '600') {
		var gridnya = $("#tabel_sp_detail_export"), i;
		//var gridnya = $("#tabel_sp_detail"), i;	
		var q =  gridnya.jqGrid('getCell', spd_id,'bl_meas');
		var sales = gridnya.jqGrid('getCell', spd_id,'sales');
		var w = gridnya.jqGrid('getCell', spd_id,'weight');
		var wg = w/1000;
		if(wg>=q){
			qty = wg;
		} else {
			qty = q;
		}
	}
	else 
	{
		var gridnya = $("#tabel_sp_detail"), i;
		var q = gridnya.jqGrid('getCell', spd_id,'bl_meas');
		var sales = gridnya.jqGrid('getCell', spd_id,'sales');
		var w = gridnya.jqGrid('getCell', spd_id,'weight');
		var wg = w/1000;
		if(wg>=q){
			qty = wg;
		} else {
			qty = q;
		}
	}
	
	//console.log({w,wg,gridnya,kode_refnya,spd_id})
	
	var grid = $("#table-cost"), i;	
	var spd_id = $('#spd_id_cost').val();
	function currencycheck(value, colname) {
			if (value == "") 
				return [false,"Please enter currency"];
			else 
				return [true,""];
			}
	var destination = gridnya.jqGrid('getCell', spd_id,'destination');	
	if(destination ==''){
		var destination = $('#dropdownCountries').val();
	}
	grid.jqGrid(
	{ 
		url : base_url+'/afg_c_cost/tampil_table_satu',
		mtype : "post",
		datatype: "json",
		sortname: "id",
		sortorder: "asc",
		postData: 
		{ 
			spd 			: spd_id,
			kode_ref 	: $( '#kode_ref_cost' ).val(),
			shipper_id 	: gridnya.jqGrid('getCell', spd_id,'shipper'),
			sales 		: sales,
			destination : destination,
			//qty : gridnya.jqGrid('getCell', spd_id,'bl_meas'),
			qty : qty,
		},
		colNames:
		[
			'',
			'Code#',
			'Item Service',
			'Quantity', 
			'Currency',
			'Price/Unit',
			'Price',		
			'Prep/Coll',
			'Payable To', 
			'Remarks', 
			'Grup',
			'Status',
			'PPh 23',
			'Created By',
			'Created At',
			'Updated By',
			'Updated At',
		],
    	colModel:
		[
			{name:'spd',index:'spd', align:"center", width:40, editable: true, hidden: true},
			
			{name:'code_service',index:'code_service', align:"left", width:80, editable: true,
				edittype:"select",
				editoptions:{
						dataUrl : base_url+'/afg_c_dropdown/dropdownService',
						dataInit: function (el) 
						{ 
							$(el).css({'min-width':'80px','max-width':'80px'}); 
						},
						/*
						dataEvents: 
						[{  type: 'focus',
                  	fn: function(e) {
                       	on_edit(true);
                   	}
               	}],
                */
                dataEvents: [{  
               	type: 'change',
                  fn: function(e) {
                  	//var rowid = grid.jqGrid('getGridParam', 'selrow');
                		//var code = grid.jqGrid('getCell',rowid,'code_service');
                       	$.ajax
								({
									type:"POST",
									url : base_url+'/afg_c_dropdown/get_item_service_nya',
									data: { typeservice_code: this.value },
									success:function(response)
									{
										var data = $.parseJSON(response);
										var rowid = grid.jqGrid('getGridParam', 'selrow');
										grid.jqGrid('setCell',rowid,'item_service',data.typeservice_name);
									},
								})
                  }
                }]
				},
			},
			{name:'item_service',index:'item_service', align:"left", width:200, editable: true, 
				editoptions:{
					dataInit: function(element) {
        				$(element).css({ "text-transform": "uppercase"})
      			},
      			
				},
				cellattr: function(rowId, cellValue, rawObject, cm, rdata) {
					return 'style="text-transform: uppercase"';
				}
			},
			{name:'qty', index:'qty', align:"center", width:60, editable: true, 
                        editoptions: {
                            dataInit: function (e) {
                                $(e).css({'max-width':'60px', 'min-width':'60px', "text-align":"center"});
                                $(e).on('keyup', function() {
                                    var rowid = $(this).closest('tr').attr('id'); 
                                    updatePrice(rowid);
                                });
                            }
                        },
                        formatter: 'number',
                        formatoptions: { decimalSeparator: ".", thousandsSeparator: " ", decimalPlaces: 4, defaultValue: '' }
                    },
			{name:'currency', index:'currency', align:"center", width:70, editable: true, edittype:"select",
                        formatter:'select', 
                        editoptions:{
                            value:" : ;USD:USD;RP.:RP.",
                            dataInit: function (e) {
                                $(e).css({'max-width':'70px', 'min-width':'70px', "text-align":"center"});
                            }
                        }
                    },
			{name:'unit_price', index:'unit_price', align:"right", width:60, editable: true, 
                        editoptions: { 
                            dataInit: function (e) {
                                $(e).css({'max-width':'60px', 'min-width':'60px', "text-align":"center"});
								$(e).on('keyup', function() {
                                    var rowid = $(this).closest('tr').attr('id'); 
                                    updatePrice(rowid);
                                });
                            }
                        },
                        formatter: 'number',
                        formatoptions: { decimalSeparator: ".", thousandsSeparator: ",", decimalPlaces: 2, defaultValue: '' }
                    },
			{name:'price', index:'price', align:"right", width:70, editable: false, 
                        formatter: "number",
                        formatoptions: { decimalSeparator: ".", thousandsSeparator: ",", decimalPlaces: 2, defaultValue: '' }
                    },			
			{name:'prep_coll',index:'prep_coll', align:"center", width:70, editable: true, 
				 edittype:"select",
				 formatter:'select', 
				 editoptions:{
				 	value:" : ;COLLECT:COLLECT;PREPAID:PREPAID",
				 	dataInit : function (e) {
						$(e).css({'max-width':'70px','min-width':'70px',"text-align":"center"})
					},
				 }
			},
			{name:'payable_to',index:'payable_to', align:"left", width:120, editable: true, 
				edittype: "select",
				editoptions: {
					value: { "": "" }, // Dummy untuk mencegah error init jqGrid
					dataInit: function(element) {
						var $elem = $(element);
						// Secara dinamis mencari grid (table) tempat elemen ini berada
						var grid = $elem.closest('table.ui-jqgrid-btable'); 
						var rowid = $elem.closest('tr').attr('id');
						var isTabPressed = false;
						
						$elem.select2({
							placeholder: '',
							width: '100%',
							selectOnClose: true, 
							ajax: {
								url: base_url + '/afg_c_dropdown/dropdownPayable',
								dataType: 'json',
								delay: 250,
								data: function (params) { return { term: params.term }; },
								processResults: function (data) {
									return {
										results: $.map(data, function(item) {
											return { id: item, text: item };
										})
									};
								},
								cache: true
							}
						});

						// Otomatis buka select2 ketika cell aktif
						setTimeout(function() { $elem.select2('open'); }, 50);

						// Sisipkan option ke DOM agar jqGrid menyimpan nilainya
						$elem.on('select2:select', function (e) {
							var data = e.params.data;
							if ($elem.find("option[value='" + data.id + "']").length === 0) {
								$elem.append(new Option(data.text, data.id, true, true));
							}
							$elem.val(data.id);
						});

						// Tangkap tombol Tab khusus untuk cell ini
						$elem.on('select2:open', function() {
							var searchField = document.querySelector('.select2-search__field');
							if (searchField) {
								searchField.focus();
								// GUNAKAN NAMESPACE '.tabCatch' agar fungsi panah bawaan Select2 tidak terhapus
								$(searchField).off('keydown.tabCatch').on('keydown.tabCatch', function(e) {
									if (e.keyCode === 9 || e.key === 'Tab') {
										e.preventDefault();
										isTabPressed = true;
										$elem.select2('close');
									}
								});
							}
						});

						// Eksekusi trigger penyimpanan saat Select2 ditutup
						$elem.on('select2:close', function() {
							setTimeout(function() {
								var ev = jQuery.Event("keydown");
								ev.keyCode = 13; // Simulasikan Enter agar jqGrid native save jalan
								$elem.trigger(ev);

								if (isTabPressed) {
									setTimeout(function() {
										var iRow = grid.jqGrid('getInd', rowid);
										var colModel = grid.jqGrid('getGridParam', 'colModel');
										var remarkColIndex = colModel.findIndex(c => c.name === 'remark');
										
										if (remarkColIndex !== -1) {
											grid.jqGrid("editCell", iRow, remarkColIndex, true);
										}
										isTabPressed = false; 
									}, 100);
								}
							}, 50);
						});
					}
				}
			},
			{name:'remark',index:'remark', align:"left", width:70, editable: true, 
				edittype:"textarea",
				editoptions:{
					cols:"40", rows:"1",
					dataInit: function (el) 
					{ 
						$(el).css('max-width','70px'); 
					},
				}
			},
			{name:'Grup',index:'Grup', align:"center", width:40, editable: true,
				editoptions:{
					dataInit: function (el) { 
						$(el).css('text-transform','uppercase'); 
					},
					dataEvents: 
					[{  
						type: 'keydown', 
            		fn: function(e) { 
                		var key = e.charCode || e.keyCode;
                		if (key == 13 || key == 9)//enter
                		{
                    		$("#btnAddCost").click();
                		}
            		}
            	}]
				},
				formatter: function(el,em,ek){
					if(el==null){
						return ""
					} else {
						return el.toUpperCase()
					}
				}
			},
			{name:'status',index:'status', width:50, hidden: false,
				formatter: function(cellvalue, options, rowObject) {
						if (cellvalue == 'paid')
							return '<img src="'+base_url+'/public/images/green-dot-icon.png"/>' +' '+ cellvalue;
						else if (cellvalue == 'unpaid')
							return '<img src="'+base_url+'/public/images/blue-dot-icon.png"/>' +' '+ cellvalue;
						else if (cellvalue == 'overdue')
							return '<img src="'+base_url+'/public/images/red-dot-icon.png"/>' +' '+ cellvalue;
						else
							return "";
				}
			},
			{
				name: 'ppn', 
				index: 'ppn', 
				align: "center", 
				width: 60, 
				editable: false, // <-- UBAH KE FALSE
				formatter: "checkbox",
				formatoptions: { disabled: false }, // Biarkan user bisa klik langsung
				defaultValue: false
			},
			{name:'created_by',index:'created_by', width:200, hidden: false,},
  			{name:'created_at',index:'update_at', width:200, hidden: false,},
  			{name:'updated_by',index:'updated_by', width:200, hidden: false,},
  			{name:'updated_at',index:'updated_at', width:200, hidden: false,},
		], 
		shrinkToFit: false,
		loadonce:false,
  		viewrecords: true,
		multiselect:true, 
		modal: true,
		rownumbers: true,
		rowNum: 15,
		gridview: true,
		width: 1257,
		height: 195,
		rowList:[15,30,45],
		caption:false,
		editurl : base_url+'/afg_c_cost/manage_table_satu',
		cellEdit:true,
		cellsubmit:'clientArray',
		pager:'#pager-tbl-cost',

		afterSaveCell: function(rowid, cellname, value, iRow, iCol) {
			var grid = $("#table-cost");

			console.log("afterSaveCell fired:", rowid, cellname, value); // DEBUG

			// =========== 1. SIMPAN VALUE CELL YANG DIUBAH ===========
			try {
				if (typeof saving_cost === "function") {
					saving_cost(rowid, cellname, value, iRow, iCol);
				} else {
					console.error("saving_cost() is NOT defined!");
				}
			} catch (e) {
				console.error("Error saving_cost():", e);
			}

			// =========== 2. KHUSUS QTY / UNIT PRICE: HITUNG PRICE & SAVE ===========
			if (cellname === "qty" || cellname === "unit_price") {
				try {
					var rowData = grid.jqGrid("getRowData", rowid);
					var qty = parseNumber(rowData.qty);
					var unitPrice = parseNumber(rowData.unit_price);
					var price = (qty * unitPrice).toFixed(2);

					// update tampilan grid
					grid.jqGrid("setCell", rowid, "price", price);

					// simpan ke database
					if (typeof saving_cost === "function") {
						saving_cost(rowid, "price", price, iRow, iCol);
					}
				} catch (e) {
					console.error("Error calculate price:", e);
				}
			}

			// =========== 3. HILANGKAN event.keyCode (INI YANG BIKIN ERROR) ===========
			// Kalau mau reload, reload langsung TANPA event
			// grid.trigger("reloadGrid");   // Optional, jika mau reload otomatis
		},

    	   			
		loadComplete: function(data) {
			$("#cb_"+grid[0].id).hide();
			on_edit(false);
    	},
		beforeSelectRow: function(rowid, e) {
		var lock =  grid.jqGrid('getCell',rowid,'status');
					if(lock == '<img src="https://gateway-cl.com/public/images/green-dot-icon.png"> paid'){
					$(this).jqGrid('setColProp','spd', { editable:false });
					$(this).jqGrid('setColProp','code_service', { editable:false });
					$(this).jqGrid('setColProp','item_service', { editable:false });
					$(this).jqGrid('setColProp','qty', { editable:false });
					$(this).jqGrid('setColProp','unit_price', { editable:false });
					//$(this).jqGrid('setColProp','price', { editable:false });
					$(this).jqGrid('setColProp','currency', { editable:false });
					$(this).jqGrid('setColProp','prep_coll', { editable:false });
					$(this).jqGrid('setColProp','payable_to', { editable:false });
					$(this).jqGrid('setColProp','remark', { editable:false });
					$(this).jqGrid('setColProp','grup', { editable:false });
					}else{
					$(this).jqGrid('setColProp','spd', { editable:true });
					$(this).jqGrid('setColProp','code_service', { editable:true });
					$(this).jqGrid('setColProp','item_service', { editable:true });
					$(this).jqGrid('setColProp','qty', { editable:true });
					$(this).jqGrid('setColProp','unit_price', { editable:true });
					//$(this).jqGrid('setColProp','price', { editable:true });
					$(this).jqGrid('setColProp','currency', { editable:true });
					$(this).jqGrid('setColProp','prep_coll', { editable:true });
					$(this).jqGrid('setColProp','payable_to', { editable:true });
					$(this).jqGrid('setColProp','remark', { editable:true });
					$(this).jqGrid('setColProp','grup', { editable:true });
					}
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
    			//cbs.attr("disabled", true);  
    			//selected.attr("disabled", false);
    			on_edit(true) 		
     		}
        	else
        	{
        		var cbs = $("tr.jqgrow > td > input.cbox:disabled", grid[0]);  		
    			//cbs.attr("disabled", false); 
    			on_edit(false)      			
        	}
			var gridId = $.jgrid.jqID(this.id);
        // test 'lock' column for some value like 'yes'
			var user = <?= $this->session->userdata('user_id');?>;
			console.log ($(this).jqGrid('getCell', rowid, 'status'));
			if ($(this).jqGrid('getCell', rowid, 'status') === '<img src="https://gateway-cl.com/public/images/green-dot-icon.png"> paid') {
            // disable the "Edit" and "Delete" buttons of the navigator
            $("#edit_" + gridId).addClass('ui-state-disabled');
            $("#del_" + gridId).addClass('ui-state-disabled');
			} else {
            // enable the "Edit" and "Delete" buttons of the navigator
            $("#edit_" + gridId).removeClass('ui-state-disabled');
            $("#del_" + gridId).removeClass('ui-state-disabled');
			}
		},		
	})
	var user = <?= $this->session->userdata('user_id');?>;
	if (user == 0 || user == 160 || user == 110 || user == 107 || user == 109){
	grid.jqGrid('navGrid','#pager-tbl-cost', { edit: false, add: false, del: true, refresh: true, search: false},
		{ }, 
   	{ },
     	{
     		height: "auto", 
     		width: "auto",
     		afterSubmit: function(){
    		grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
    			return [true];
			}
     	},
      { },
     	{ }
	)
	}else{
	grid.jqGrid('navGrid','#pager-tbl-cost', { edit: false, add: false, del: false, refresh: true, search: false},
		{ }, 
   	{ },
     	{
     		height: "auto", 
     		width: "auto",
     		afterSubmit: function(){
    		grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
    			return [true];
			}
     	},
      { },
     	{ }
	)
	}
	grid.navButtonAdd('#pager-tbl-cost',{ 
			caption:"", 
			buttonicon:"ui-icon-plus", 
			onClickButton: insert_data_cost, 
			position: "last", 
			title:"Insert Data Cost", 
			cursor: "pointer",
			id:"btnAddCost",
	})
	/*
	grid.navButtonAdd('#pager-tbl-cost',{ 
			caption:"", 
			buttonicon:"ui-icon-arrowthickstop-1-e", 
			onClickButton: tarik_cost, 
			position: "last", 
			title:"Tarik Data Cost", 
			cursor: "pointer"
	})
	*/
	grid.navButtonAdd('#pager-tbl-cost',{ 
			caption:"", 
			buttonicon:"ui-icon-trash", 
			onClickButton: delete_cost, 
			position: "last", 
			title:"Delete cost", 
			cursor: "pointer"
	})
	
}

function updatePrice(rowid) {
				var grid = $("#table-cost"), i;
                // Get the input field values if they are being edited
                var qty = getEditedCellValue(rowid, 'qty');
                var unit_price = getEditedCellValue(rowid, 'unit_price');

                var price = (qty * unit_price).toFixed(2);

                // Update the 'price' column
                grid.jqGrid('setCell', rowid, 'price', price);
            }

function getEditedCellValue(rowid, colname) {
	var grid = $("#table-cost"), i;
                var inputField = $("tr#"+rowid+" input[name='"+colname+"']");
                if (inputField.length > 0) {
                    return parseFloat(inputField.val()) || 0;
                } else {
                    return parseFloat(grid.jqGrid('getCell', rowid, colname)) || 0;
                }
            }

function saving_cost(rowid, cellname, value, iRow, iCol){
	var grid = $("#table-cost"), i;		
	var myData = grid.getRowData(rowid);
	$.ajax
	({
		type	:"POST",
		url	:base_url+'/afg_c_cost/saving_cost',
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

function on_edit(ch){
	if (ch==true) {
		$("tr > td[title=\"Insert Data Table Cost\"]").attr("hidden",true);
	} else {
		$("tr > td[title=\"Insert Data Table Cost\"]").attr("hidden",false);
	}	
}
function delete_cost(){
	var grid = $("#table-cost");	
	var i, rowid = grid.jqGrid('getGridParam','selarrrow'), n, rowData;
	for (i = 0, n = rowid.length; i < n; i++) {
	var update 	= $("#table-cost").jqGrid('getCell',rowid[i],'updated_at');
	var create 	= $("#table-cost").jqGrid('getCell',rowid[i],'created_at');
	var payable = $("#table-cost").jqGrid('getCell',rowid[i],'payable_to');
	}
	var post = $('#post').val();
	if (payable == "PAJAK")
	{
	Swal.fire({
			title: "Anda yakin?",
			text: "Baris yang sudah dihapus tidak bisa dikembalikan!",
			type: "warning",
			showCancelButton: true,
			confirmButtonColor: '#DD6B55',
			confirmButtonText: 'Ya, Hapus!',
			cancelButtonText: "Tidak, Batalkan!"
			}).then(
			function(isConfirm){ 
			if (isConfirm.value){
			$.ajax
			({
			type:"POST",
			url : base_url+'/afg_c_cost/manage_table_satu',
			data:
			{
			id : rowid.join(),
			oper : 'del',
			},
			success:function(response)
			{
			var data = $.parseJSON(response);
			grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
    		return [true];
			}
			})
			} else {
			return false;
			}
			});	
	}else{
	if (update < post || create < post) {
		Swal.fire ({
			icon: 'error',
			title: 'Hapus data gagal',
			text: 'Item kemungkinan sudah dibayar',
			footer: '<a href="">Hubungi team ERP untuk menghapus data</a>'
			});
	} else {
		Swal.fire({
			title: "Anda yakin?",
			text: "Baris yang sudah dihapus tidak bisa dikembalikan!",
			type: "warning",
			showCancelButton: true,
			confirmButtonColor: '#DD6B55',
			confirmButtonText: 'Ya, Hapus!',
			cancelButtonText: "Tidak, Batalkan!"
			}).then(
			function(isConfirm){ 
			if (isConfirm.value){
			$.ajax
			({
			type:"POST",
			url : base_url+'/afg_c_cost/manage_table_satu',
			data:
			{
			id : rowid.join(),
			oper : 'del',
			},
			success:function(response)
			{
			var data = $.parseJSON(response);
			grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
    		return [true];
			}
			})
			} else {
			return false;
			}
			});	
	   }
	}	
}

function insert_data_cost(){
	var grid = $("#table-cost"), i;	
	$.ajax
	({
		type:"POST",
		url : base_url+'/afg_c_cost/manage_table_satu',
		data:
		{
			spd : $('#spd_id_cost').val(),
			kode_ref : $( '#kode_ref_cost' ).val(),
			oper : 'add',
		},
		success:function(response)
		{
			var data = $.parseJSON(response);
			grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
    		return [true];
		}
	})
}

function save_tbl_satu(rowid, cellname, value) {
    const grid = $("#table-cost");

    const payload = {
        spd: $('#spd_id_cost').val(),
        kode_ref: $('#kode_ref_cost').val(),
        qty: grid.jqGrid('getCell', rowid, 'qty'),
        price: grid.jqGrid('getCell', rowid, 'price'),
        id: rowid,
        kolom: cellname,
        isi: value,
        oper: 'edit'
    };

    $.post(base_url + '/afg_c_cost/manage_table_satu', payload)
        .done(response => {
            const data = JSON.parse(response);

            const fieldsTriggerReload = ['code_service', 'qty', 'unit_price'];

            if (fieldsTriggerReload.includes(data.field)) {
                grid.jqGrid("setGridParam", { datatype: 'json' }).trigger("reloadGrid");
            }
        })
        .fail(() => {
            console.error("Gagal menyimpan data ke server");
        });
}



function table_refund_discount(){
	var grid = $("#table-discount"), i;	
	var spd_id = $('#spd_id_cost').val();
	var destination = $("#tabel_sp_detail").jqGrid('getCell', spd_id,'destination');	
	if(destination ==''){
		var destination = $('#dropdownCountries').val();
	}
	
	grid.jqGrid({ 
		url 	: base_url+'/afg_c_cost/tampil_data_table',
		mtype : "post",
		datatype: "json",
		sortname: "id",
		sortorder: "asc",
		postData: 
		{ 
			table   : 'inf_cost_refund_discount',
			spd : spd_id,
			kode_ref : $( '#kode_ref_cost' ).val(),
			shipper_id : $("#tabel_sp_detail").jqGrid('getCell', spd_id,'shipper'),
			sales : $("#tabel_sp_detail").jqGrid('getCell', spd_id,'sales'),
			destination : destination,
			qty : $("#tabel_sp_detail").jqGrid('getCell', spd_id,'bl_meas'),
		},
		colNames:
		[
			'Code#',
			'Item Service',
			'Quantity', 
			'Currency',
			'Price/Unit',
			'Price',		
			'Prep/Coll',
			'Payable To', 
			'Remarks', 
			'Grup',
			'Status',
			'PPh 23',
			'Created By',
			'Created At',
			'Updated By',
			'Updated At',
		],
    	colModel:
		[
			{name:'code_service',index:'code_service', align:"left", width:80, editable: true,
				edittype:"select",
				editoptions:{
					dataUrl : base_url+'/afg_c_dropdown/dropdownService',
					dataInit: function (el) { 
						$(el).css('max-width','80px'); 
					},
					dataEvents: [{  
               	type: 'change',
                  fn: function(e) {
                  	//var rowid = grid.jqGrid('getGridParam', 'selrow');
                		//var code = grid.jqGrid('getCell',rowid,'code_service');
                       	$.ajax
								({
									type:"POST",
									url : base_url+'/afg_c_dropdown/get_item_service_nya',
									data: { typeservice_code: this.value },
									success:function(response)
									{
										var data = $.parseJSON(response);
										var rowid = grid.jqGrid('getGridParam', 'selrow');
										grid.jqGrid('setCell',rowid,'item_service',data.typeservice_name);
									},
								})
                  }
                }]
				}
			},
			{name:'item_service',index:'item_service', align:"left", width:200, editable: true, 
				editoptions:{
					//disabled: true,
					dataInit: function (el) { 
						$(el).css('max-width','100px'); 
					},
				}
			},
			{name:'qty',index:'qty', align:"center", width:60, 
				editable: true, 
				editoptions: {
					dataInit: function (el) { 
						$(el).attr('title','Hanya boleh memasukan angka').css({'text-align':'center','max-width':'60'}); 
					}
				},
				formatter: 'number',
    			formatoptions: { decimalSeparator: ".", thousandsSeparator: " ", decimalPlaces: 4, defaultValue: '' }
			},
			{name:'currency',index:'currency', align:"center", width:70, editable: true, 
				edittype:"select",formatter:'select', 
				editoptions:{
					value:" : ;USD:USD;RP.:RP.",
					dataInit: function (el) 
					{ 
						$(el).css({'max-widt':'70'}); 
					}
				},
			},
			{name:'unit_price',index:'unit_price', align:"right", width:60, editable: true, 
				editoptions: {
					dataInit: function (el) { 
						$(el).attr('title','Hanya boleh memasukan angka').css({'text-align':'center','max-width':'60'}); 
					}
				},
				formatter: 'number',
    			formatoptions: { decimalSeparator: ".", thousandsSeparator: " ", decimalPlaces: 2, defaultValue: '' }
			},
			{name:'price',index:'price', align:"right", width:70, editable: false, 
				editoptions: { 
					disabled:true,
					dataInit: function (el) { 
						$(el).attr('title','Hanya boleh memasukan angka').css({'max-widt':'70'}); 
					}
				},
				formatter: 'number',
    			formatoptions: { decimalSeparator: ".", thousandsSeparator: " ", decimalPlaces: 2, defaultValue: '' }
			},		
			{name:'prep_coll',index:'prep_coll', align:"center", width:80, editable: true, 
				 edittype:"select",
				 formatter:'select', 
				 editoptions:{
				 	value:" : ;COLLECT:COLLECT;PREPAID:PREPAID",
				 	dataInit: function (el) { 
						$(el).css({'max-width':'80'}); 
					},
				 	dataEvents: 
					[{  type: 'focus',
                  	fn: function(e) {
                  		on_edit(true);
                       	var rowid = grid.jqGrid('getGridParam', 'selrow');
                       	var qty = grid.jqGrid('getCell',rowid,'qty');
                       	var unit_price = grid.jqGrid('getCell',rowid,'unit_price');
                        grid.jqGrid('setCell',rowid,'price',eval(qty) * eval(unit_price))
                   	}
               }]
				}
			},
			{name:'payable_to',index:'payable_to', align:"left", width:120, editable: true, 
				edittype: "select",
				editoptions: {
					value: { "": "" }, // Dummy untuk mencegah error init jqGrid
					dataInit: function(element) {
						var $elem = $(element);
						// Secara dinamis mencari grid (table) tempat elemen ini berada
						var grid = $elem.closest('table.ui-jqgrid-btable'); 
						var rowid = $elem.closest('tr').attr('id');
						var isTabPressed = false;
						
						$elem.select2({
							placeholder: '',
							width: '100%',
							selectOnClose: true, 
							ajax: {
								url: base_url + '/afg_c_dropdown/dropdownPayable',
								dataType: 'json',
								delay: 250,
								data: function (params) { return { term: params.term }; },
								processResults: function (data) {
									return {
										results: $.map(data, function(item) {
											return { id: item, text: item };
										})
									};
								},
								cache: true
							}
						});

						// Otomatis buka select2 ketika cell aktif
						setTimeout(function() { $elem.select2('open'); }, 50);

						// Sisipkan option ke DOM agar jqGrid menyimpan nilainya
						$elem.on('select2:select', function (e) {
							var data = e.params.data;
							if ($elem.find("option[value='" + data.id + "']").length === 0) {
								$elem.append(new Option(data.text, data.id, true, true));
							}
							$elem.val(data.id);
						});

						// Tangkap tombol Tab khusus untuk cell ini
						$elem.on('select2:open', function() {
							var searchField = document.querySelector('.select2-search__field');
							if (searchField) {
								searchField.focus();
								$(searchField).off('keydown').on('keydown', function(e) {
									if (e.keyCode === 9 || e.key === 'Tab') {
										e.preventDefault();
										isTabPressed = true;
										$elem.select2('close');
									}
								});
							}
						});

						// Eksekusi trigger penyimpanan saat Select2 ditutup
						$elem.on('select2:close', function() {
							setTimeout(function() {
								var ev = jQuery.Event("keydown");
								ev.keyCode = 13; // Simulasikan Enter agar jqGrid native save jalan
								$elem.trigger(ev);

								if (isTabPressed) {
									setTimeout(function() {
										var iRow = grid.jqGrid('getInd', rowid);
										var colModel = grid.jqGrid('getGridParam', 'colModel');
										var remarkColIndex = colModel.findIndex(c => c.name === 'remark');
										
										if (remarkColIndex !== -1) {
											grid.jqGrid("editCell", iRow, remarkColIndex, true);
										}
										isTabPressed = false; 
									}, 100);
								}
							}, 50);
						});
					}
				}
			},
			{name:'remark',index:'remark', align:"left", width:70, editable: true, 
				edittype:"textarea",
				editoptions:{
					cols:"40", rows:"1",
					dataInit: function (el) 
					{ 
						$(el).css('max-width','70'); 
					}
				}
			},
			{name:'g',index:'g', align:"center", width:40, editable: true,
				editoptions:{
					dataInit: function (el) { 
						$(el).css('text-transform','uppercase'); 
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
				formatter: function(el,em,ek){
					if(el==null){
						return ""
					} else {
						return el.toUpperCase()
					}
				}
			},
			{name:'status',index:'status', width:50, hidden: false,
				formatter: function(cellvalue, options, rowObject) {
						if (cellvalue == 'paid')
							return '<img src="'+base_url+'/public/images/green-dot-icon.png"/>' +' '+ cellvalue;
						else if (cellvalue == 'unpaid')
							return '<img src="'+base_url+'/public/images/blue-dot-icon.png"/>' +' '+ cellvalue;
						else if (cellvalue == 'overdue')
							return '<img src="'+base_url+'/public/images/red-dot-icon.png"/>' +' '+ cellvalue;
						else
							return "";
				}
			},
			{
				name: 'ppn', 
				index: 'ppn', 
				align: "center", 
				width: 60, 
				editable: false, // <-- UBAH KE FALSE
				formatter: "checkbox",
				formatoptions: { disabled: false }, // Biarkan user bisa klik langsung
				defaultValue: false
			},
			{name:'created_by',index:'created_by', width:200, hidden: false,},
  			{name:'created_at',index:'update_at', width:200, hidden: false,},
  			{name:'updated_by',index:'updated_by', width:200, hidden: false,},
  			{name:'updated_at',index:'updated_at', width:200, hidden: false,},
		], 
		shrinkToFit: false,
		loadonce:false,
  		viewrecords: true,
		multiselect:true, 
		modal: true,
		rownumbers: true,
		rowNum: 2,
		gridview: true,
		width: 1257,
		height: 85,
		rowList:[3,6,9],
		caption:"Refund Customer",
		editurl : base_url+'/afg_c_cost/manage_data_refund',
		cellEdit:true,
		cellsubmit:'clientArray',
		pager:'#pager-tbl-discount',
		
		afterSaveCell: function (rowid, cellname, value, iRow, iCol) {
			var grid = $("#table-discount");

			console.log("afterSaveCell (discount) fired:", rowid, cellname, value); // DEBUG

			// ========== 1. SIMPAN VALUE CELL YANG DIUBAH ==========
			try {
				if (typeof save_tbl_discount === "function") {
					save_tbl_discount(rowid, cellname, value, "edit");
				} else {
					console.error("save_tbl_discount() is NOT defined");
				}
			} catch (e) {
				console.error("Error saving discount:", e);
			}

			// ========== 2. KHUSUS QTY / UNIT PRICE: HITUNG PRICE & SAVE ==========
			if (cellname === "qty" || cellname === "unit_price") {
				try {
					var rowData = grid.jqGrid("getRowData", rowid);
					var qty       = parseNumber(rowData.qty);
					var unitPrice = parseNumber(rowData.unit_price);
					var price     = (qty * unitPrice).toFixed(2);

					// Update tampilan grid
					grid.jqGrid("setCell", rowid, "price", price);

					// Simpan harga ke database
					if (typeof save_tbl_discount === "function") {
						save_tbl_discount(rowid, "price", price, "edit");
					}
				} catch (err) {
					console.error("Error calculating price:", err);
				}
			}

			// ========== 3. JANGAN GUNAKAN event.keyCode (BIKIN ERROR) ==========
			// Reload jika perlu
			// grid.trigger("reloadGrid");
		},
	
		loadComplete: function(data) {
			$("#cb_"+grid[0].id).hide();
    	}, 
	beforeSelectRow: function(rowid, e) {
		var lock =  grid.jqGrid('getCell',rowid,'status');
					if(lock == '<img src="https://gateway-cl.com/public/images/green-dot-icon.png"> paid'){
					$(this).jqGrid('setColProp','spd', { editable:false });
					$(this).jqGrid('setColProp','code_service', { editable:false });
					$(this).jqGrid('setColProp','item_service', { editable:false });
					$(this).jqGrid('setColProp','qty', { editable:false });
					$(this).jqGrid('setColProp','unit_price', { editable:false });
					//$(this).jqGrid('setColProp','price', { editable:false });
					$(this).jqGrid('setColProp','currency', { editable:false });
					$(this).jqGrid('setColProp','prep_coll', { editable:false });
					$(this).jqGrid('setColProp','payable_to', { editable:false });
					$(this).jqGrid('setColProp','remark', { editable:false });
					$(this).jqGrid('setColProp','grup', { editable:false });
					}else{
					$(this).jqGrid('setColProp','spd', { editable:true });
					$(this).jqGrid('setColProp','code_service', { editable:true });
					$(this).jqGrid('setColProp','item_service', { editable:true });
					$(this).jqGrid('setColProp','qty', { editable:true });
					$(this).jqGrid('setColProp','unit_price', { editable:true });
					//$(this).jqGrid('setColProp','price', { editable:true });
					$(this).jqGrid('setColProp','currency', { editable:true });
					$(this).jqGrid('setColProp','prep_coll', { editable:true });
					$(this).jqGrid('setColProp','payable_to', { editable:true });
					$(this).jqGrid('setColProp','remark', { editable:true });
					$(this).jqGrid('setColProp','grup', { editable:true });
					}
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
    			//cbs.attr("disabled", true);  
    			//selected.attr("disabled", false);
    			on_edit(true) 		
     		}
        	else
        	{
        		var cbs = $("tr.jqgrow > td > input.cbox:disabled", grid[0]);  		
    			//cbs.attr("disabled", false); 
    			on_edit(false)      			
        	}
			var gridId = $.jgrid.jqID(this.id);
        // test 'lock' column for some value like 'yes'
			var user = <?= $this->session->userdata('user_id');?>;
			console.log ($(this).jqGrid('getCell', rowid, 'status'));
			if ($(this).jqGrid('getCell', rowid, 'status') === '<img src="https://gateway-cl.com/public/images/green-dot-icon.png"> paid') {
            // disable the "Edit" and "Delete" buttons of the navigator
            $("#edit_" + gridId).addClass('ui-state-disabled');
            $("#del_" + gridId).addClass('ui-state-disabled');
			} else {
            // enable the "Edit" and "Delete" buttons of the navigator
            $("#edit_" + gridId).removeClass('ui-state-disabled');
            $("#del_" + gridId).removeClass('ui-state-disabled');
			}
		},			
	})
	var user = <?= $this->session->userdata('user_id');?>;
	if (user == 0 || user == 160 || user == 110 || user == 107 || user == 109){
	grid.jqGrid('navGrid','#pager-tbl-discount', { edit: false, add: false, del: true, refresh: true, search: false},
		{ }, 
   	{ },
     	{
     		height: "auto", 
     		width: "auto",
     		mtype: "POST",
     		serializeDelData: function (postdata) {
     			return {
    				id: postdata.id, 
    				oper: postdata.oper, 
    				table : 'inf_cost_refund_discount',
    			};
			},
     		afterSubmit: function(){
    		grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
    			return [true];
			}
     	},
      { },
     	{ }
	)
	}else{
	grid.jqGrid('navGrid','#pager-tbl-discount', { edit: false, add: false, del: false, refresh: true, search: false},
		{ }, 
   	{ },
     	{
     		height: "auto", 
     		width: "auto",
     		afterSubmit: function(){
    		grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
    			return [true];
			}
     	},
      { },
     	{ }
	)
	}
	grid.navButtonAdd('#pager-tbl-discount',{ 
			caption:"", 
			buttonicon:"ui-icon-plus", 
			onClickButton: insert_data_refund_discount, 
			position: "last", 
			title:"Insert Data Table Refund", 
			cursor: "pointer",
			id:"btnAddDua"
	})
	grid.navButtonAdd('#pager-tbl-discount',{ 
			caption:"", 
			buttonicon:"ui-icon-disk", 
			onClickButton: tarik_data_refund_discount, 
			position: "last", 
			title:"Tarik Data Refund", 
			cursor: "pointer"
	})
	grid.navButtonAdd('#pager-tbl-discount',{ 
			caption:"", 
			buttonicon:"ui-icon-trash", 
			onClickButton: delete_discount, 
			position: "last", 
			title:"Delete cost", 
			cursor: "pointer"
	})
}

function insert_data_refund_discount(){
	var grid = $("#table-discount"), i;	
	$.ajax
	({
		type:"POST",
		url : base_url+'/afg_c_cost/manage_data_refund',
		data:
		{
			sp_detail_id : $('#spd_id_cost').val(),
			kode_ref : $( '#kode_ref_cost' ).val(),
			table : 'inf_cost_refund_discount',
			id	:'',
			price : '',
			oper : 'add',
		},
		success:function(response)
		{
			var data = $.parseJSON(response);
			grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
    		return [true];
		}
	})
}

function save_tbl_discount(rowid,cellname,value,oper){
	var grid = $("#table-discount"), i;	
	var price = grid.jqGrid('getCell', rowid,'price');
	var qty = grid.jqGrid('getCell', rowid,'qty');
	var unit_price = grid.jqGrid('getCell', rowid,'unit_price');
	$.ajax
	({
		type:"POST",
		url : base_url+'/afg_c_cost/manage_data_refund',
		data:
		{
			spd : $('#spd_id_cost').val(),
			kode_ref : $( '#kode_ref_cost' ).val(),
			id : rowid,
			table : 'inf_cost_refund_discount',
			kolom : cellname,
			isi : value,
			price : qty * unit_price,
			oper : oper,
		},
		success:function(response)
		{
			var data = $.parseJSON(response);
			/*
			if(data.mode=='add' || data.status=='1'){
				$("#table-discount").jqGrid("setGridParam", 
				{
					datatype: 'json',
					sortname: "id",
      			sortorder: "asc",
      		}).trigger("reloadGrid");
    			return [true];
			}
			*/
		}
	})
}
function delete_discount(){
	var grid = $("#table-discount"), i;	
	var i, rowid = grid.jqGrid('getGridParam','selarrrow'), n, rowData;
	for (i = 0, n = rowid.length; i < n; i++) {
	var update 	= $("#table-discount").jqGrid('getCell',rowid[i],'updated_at');
	var create 	= $("#table-discount").jqGrid('getCell',rowid[i],'created_at');
	var payable = $("#table-discount").jqGrid('getCell',rowid[i],'payable_to');
	}
	var post = $('#post').val();
	if (payable == "PAJAK")
	{
	Swal.fire({
			title: "Anda yakin?",
			text: "Baris yang sudah dihapus tidak bisa dikembalikan!",
			icon: "warning",
			showCancelButton: true,
			confirmButtonColor: '#DD6B55',
			confirmButtonText: 'Ya, Hapus!',
			cancelButtonText: "Tidak, Batalkan!"
			}).then(
			function(isConfirm){ 
			if (isConfirm.value){
			$.ajax
			({
			type:"POST",
			url : base_url+'/afg_c_cost/manage_data_refund',
			data:
			{
			id : rowid.join(),
			oper : 'del',
			table : 'inf_cost_refund_discount',
			},
			success:function(response)
			{
			var data = $.parseJSON(response);
			grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
    		return [true];
			}
			})
			} else {
			return false;
			}
			});	
	}else{
	if (update < post || create < post) {
		Swal.fire ({
			icon: 'error',
			title: 'Hapus data gagal',
			text: 'Item kemungkinan sudah dibayar',
			footer: '<a href="">Hubungi Accounting untuk menghapus data</a>'
			});
	} else {
		Swal.fire({
			title: "Anda yakin?",
			text: "Baris yang sudah dihapus tidak bisa dikembalikan!",
			icon: "warning",
			showCancelButton: true,
			confirmButtonColor: '#DD6B55',
			confirmButtonText: 'Ya, Hapus!',
			cancelButtonText: "Tidak, Batalkan!"
			}).then(
			function(isConfirm){ 
			if (isConfirm.value){
			$.ajax
			({
			type:"POST",
			url : base_url+'/afg_c_cost/manage_data_refund',
			data:
			{
			id : rowid.join(),
			oper : 'del',
			table : 'inf_cost_refund_discount',
			},
			success:function(response)
			{
			var data = $.parseJSON(response);
			grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
    		return [true];
			}
			})
			} else {
			return false;
			}
			});	
	   }
	}	
}

function table_refund_sc(){
	
	var grid = $("#table-sc"), i;	
	var spd_id = $('#spd_id_cost').val();
	var destination = $("#tabel_sp_detail").jqGrid('getCell', spd_id,'destination');	
	if(destination ==''){
		var destination = $('#dropdownCountries').val();
	}
	
	grid.jqGrid({ 
		url : base_url+'/afg_c_cost/tampil_data_table',
		mtype : "post",
		datatype: "json",
		sortname: "id",
		sortorder: "asc",
		postData: 
		{ 
			table 	: 'inf_cost_refund_sc',
			kode_ref : $( '#kode_ref_cost' ).val(),
			spd : spd_id,
		},
		colNames:
		[
			'Code#',
			'Item Service',
			'Quantity', 
			'Currency',
			'Price/Unit',
			'Price',		
			'Prep/Coll',
			'Payable To', 
			'Remarks', 
			'Grup',
			'Status',
			'PPh 23',
			'Created By',
			'Created At',
			'Updated By',
			'Updated At',
		],
    	colModel:
		[
			{name:'code_service',index:'code_service', align:"left", width:80, editable: true,
				edittype:"select",
				editoptions:{
					dataUrl : base_url+'/afg_c_dropdown/dropdownService',
					dataInit: function (el) { 
						$(el).css('max-width','80px'); 
					},
					dataEvents: [{  
               	type: 'change',
                  fn: function(e) {
                  	//var rowid = grid.jqGrid('getGridParam', 'selrow');
                		//var code = grid.jqGrid('getCell',rowid,'code_service');
                       	$.ajax
								({
									type:"POST",
									url : base_url+'/afg_c_dropdown/get_item_service_nya',
									data: { typeservice_code: this.value },
									success:function(response)
									{
										var data = $.parseJSON(response);
										var rowid = grid.jqGrid('getGridParam', 'selrow');
										grid.jqGrid('setCell',rowid,'item_service',data.typeservice_name);
									},
								})
                  }
                }]
				}
			},
			{name:'item_service',index:'item_service', align:"left", width:200, editable: true, 
				editoptions:{
					//disabled: true,
					dataInit: function (el) { 
						$(el).css('max-width','100'); 
					},
				}
			},
			{name:'qty',index:'qty', align:"center", width:60, 
				editable: true, 
				editoptions: {
					dataInit: function (el) { 
						$(el).attr('title','Hanya boleh memasukan angka').css({'text-align':'center','max-width':'60'}); 
					}
				},
				formatter: 'number',
    			formatoptions: { decimalSeparator: ".", thousandsSeparator: " ", decimalPlaces: 4, defaultValue: '' }
			},
			{name:'currency',index:'currency', align:"center", width:70, editable: true, 
				edittype:"select",
				formatter:'select',
				editoptions:{
					value:" : ;USD:USD;RP.:RP.",
					dataInit: function (el) { 
						$(el).css({'max-width':'70'});
					}
				},
			},
			{name:'unit_price',index:'unit_price', align:"right", width:60, editable: true, 
				editoptions: {
				dataInit: function (el) { 
						$(el).attr('title','Hanya boleh memasukan angka').css({'max-width':'60'});
					}
				},
				formatter: 'number',
    			formatoptions: { decimalSeparator: ".", thousandsSeparator: " ", decimalPlaces: 2, defaultValue: '' }
			},
			{name:'price',index:'price', align:"right", width:70,
				editable:false, 
				editoptions: { 
					disabled:true,
					dataInit: function (el) { 
						$(el).attr('title','Hanya boleh memasukan angka'); 
					}
				},
				formatter: 'number',
    			formatoptions: { decimalSeparator: ".", thousandsSeparator: " ", decimalPlaces: 2, defaultValue: '' }
			},			
			{name:'prep_coll',index:'prep_coll', align:"center", width:80, editable: true, 
				 edittype:"select",formatter:'select', 
				 editoptions:{
				 	value:" : ;COLLECT:COLLECT;PREPAID:PREPAID",
				 	dataInit: function (el) { 
						$(el).css({'max-width':'80'});
					},
					dataEvents: 
					[{  type: 'focus',
                  	fn: function(e) {
                  		on_edit(true);
                       	var rowid = grid.jqGrid('getGridParam', 'selrow');
                       	var qty = grid.jqGrid('getCell',rowid,'qty');
                       	var unit_price = grid.jqGrid('getCell',rowid,'unit_price');
                        grid.jqGrid('setCell',rowid,'price',eval(qty) * eval(unit_price))
                   	}
               }]
				 }
			},
			{name:'payable_to',index:'payable_to', align:"left", width:120, editable: true, 
				edittype: "select",
				editoptions: {
					value: { "": "" }, // Dummy untuk mencegah error init jqGrid
					dataInit: function(element) {
						var $elem = $(element);
						// Secara dinamis mencari grid (table) tempat elemen ini berada
						var grid = $elem.closest('table.ui-jqgrid-btable'); 
						var rowid = $elem.closest('tr').attr('id');
						var isTabPressed = false;
						
						$elem.select2({
							placeholder: '',
							width: '100%',
							selectOnClose: true, 
							ajax: {
								url: base_url + '/afg_c_dropdown/dropdownPayable',
								dataType: 'json',
								delay: 250,
								data: function (params) { return { term: params.term }; },
								processResults: function (data) {
									return {
										results: $.map(data, function(item) {
											return { id: item, text: item };
										})
									};
								},
								cache: true
							}
						});

						// Otomatis buka select2 ketika cell aktif
						setTimeout(function() { $elem.select2('open'); }, 50);

						// Sisipkan option ke DOM agar jqGrid menyimpan nilainya
						$elem.on('select2:select', function (e) {
							var data = e.params.data;
							if ($elem.find("option[value='" + data.id + "']").length === 0) {
								$elem.append(new Option(data.text, data.id, true, true));
							}
							$elem.val(data.id);
						});

						// Tangkap tombol Tab khusus untuk cell ini
						$elem.on('select2:open', function() {
							var searchField = document.querySelector('.select2-search__field');
							if (searchField) {
								searchField.focus();
								$(searchField).off('keydown').on('keydown', function(e) {
									if (e.keyCode === 9 || e.key === 'Tab') {
										e.preventDefault();
										isTabPressed = true;
										$elem.select2('close');
									}
								});
							}
						});

						// Eksekusi trigger penyimpanan saat Select2 ditutup
						$elem.on('select2:close', function() {
							setTimeout(function() {
								var ev = jQuery.Event("keydown");
								ev.keyCode = 13; // Simulasikan Enter agar jqGrid native save jalan
								$elem.trigger(ev);

								if (isTabPressed) {
									setTimeout(function() {
										var iRow = grid.jqGrid('getInd', rowid);
										var colModel = grid.jqGrid('getGridParam', 'colModel');
										var remarkColIndex = colModel.findIndex(c => c.name === 'remark');
										
										if (remarkColIndex !== -1) {
											grid.jqGrid("editCell", iRow, remarkColIndex, true);
										}
										isTabPressed = false; 
									}, 100);
								}
							}, 50);
						});
					}
				}
			},
			{name:'remark',index:'remark', align:"left", width:70, editable: true, 
				edittype:"textarea",
				editoptions:{
					cols:"40", rows:"1",
					dataInit: function (el) 
					{ 
						$(el).css('max-width','70'); 
					}
				}
			},
			{name:'g',index:'g', align:"center", width:40, editable: true,
				editoptions:{
					dataInit: function (el) { 
						$(el).css('text-transform','uppercase');
					},
					dataEvents: 
					[{  
						type: 'keydown', 
            		fn: function(e) { 
                		var key = e.charCode || e.keyCode;
                		if (key == 13)//enter
                		{
                    		$("#btnAddTiga").click();
                		}
            		}
            	}]
				},
				formatter: function(el,em,ek){
					if(el==null){
						return ""
					} else {
						return el.toUpperCase()
					}
				}
			},
			{name:'status',index:'status', width:50, hidden: false,
				formatter: function(cellvalue, options, rowObject) {
						if (cellvalue == 'paid')
							return '<img src="'+base_url+'/public/images/green-dot-icon.png"/>' +' '+ cellvalue;
						else if (cellvalue == 'unpaid')
							return '<img src="'+base_url+'/public/images/blue-dot-icon.png"/>' +' '+ cellvalue;
						else if (cellvalue == 'overdue')
							return '<img src="'+base_url+'/public/images/red-dot-icon.png"/>' +' '+ cellvalue;
						else
							return "";
				}
			},
			{
				name: 'ppn', 
				index: 'ppn', 
				align: "center", 
				width: 60, 
				editable: false, // <-- UBAH KE FALSE
				formatter: "checkbox",
				formatoptions: { disabled: false }, // Biarkan user bisa klik langsung
				defaultValue: false
			},
			{name:'created_by',index:'created_by', width:200, hidden: false,},
  			{name:'created_at',index:'update_at', width:200, hidden: false,},
  			{name:'updated_by',index:'updated_by', width:200, hidden: false,},
  			{name:'updated_at',index:'updated_at', width:200, hidden: false,},
		], 
		shrinkToFit: false,
		loadonce:false,
  		viewrecords: true,
		multiselect:true, 
		modal: true,
		rownumbers: true,
		rowNum: 3,
		gridview: true,
		width: 1257,
		height: 90,
		rowList:[3,6,9],
		caption:"Sales Discount",
		editurl : base_url+'/afg_c_cost/manage_data_refund',
		cellEdit:true,
		cellsubmit:'clientArray',
		pager:'#pager-tbl-sc',

		afterSaveCell: function(rowid, cellname, value, iRow, iCol) {
			var grid = $("#table-sc");

			console.log("afterSaveCell SC:", rowid, cellname, value); // DEBUG

			// ========== 1. Simpan cell yang diedit ==========
			try {
				if (typeof save_tbl_sc === "function") {
					save_tbl_sc(rowid, cellname, value, "edit");
				} else {
					console.error("save_tbl_sc() is NOT defined!");
				}
			} catch (e) {
				console.error("Error save_tbl_sc():", e);
			}

			// ========== 2. Jika QTY/U.PRICE berubah → hitung ulang PRICE ==========
			if (cellname === "qty" || cellname === "unit_price") {
				try {
					var rowData = grid.jqGrid("getRowData", rowid);
					var qty       = parseNumber(rowData.qty);
					var unitPrice = parseNumber(rowData.unit_price);
					var price     = (qty * unitPrice).toFixed(2);

					// update tampilan grid
					grid.jqGrid("setCell", rowid, "price", price);

					// simpan price ke database
					if (typeof save_tbl_sc === "function") {
						save_tbl_sc(rowid, "price", price, "edit");
					}
				} catch (err) {
					console.error("Error calculate price:", err);
				}
			}

			// ========== 3. Hilangkan event.keyCode (error) ==========
			// Kalau mau reload otomatis:
			// grid.trigger("reloadGrid");
		},

    			
		loadComplete: function(data) {
			$("#cb_"+grid[0].id).hide();
    	},
    	
	})
	grid.jqGrid('navGrid','#pager-tbl-sc', { edit: false, add: false, del: true, refresh: true, search: false },
		{ }, 
   	{ },
     	{
     		height: "auto", 
     		width: "auto",
     		mtype: "POST",
     		serializeDelData: function (postdata) {
     			return {
    				id: postdata.id, 
    				oper: postdata.oper, 
    				table : 'inf_cost_refund_sc',
    			};
			},
     		afterSubmit: function(){
    		grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
    			return [true];
			}
     	},
      { },
     	{ }
	)
	grid.navButtonAdd('#pager-tbl-sc',{ 
			caption:"", 
			buttonicon:"ui-icon-plus", 
			onClickButton: insert_data_refund_sc, 
			position: "last", 
			title:"Insert Data Table Refund", 
			cursor: "pointer",
			id:"btnAddTiga",
	})
	grid.navButtonAdd('#pager-tbl-sc',{ 
			caption:"", 
			buttonicon:"ui-icon-disk", 
			onClickButton: tarik_data_refund_sc, 
			position: "last", 
			title:"Tarik Data Sales Comision", 
			cursor: "pointer"
	})
}

function insert_data_refund_sc(){
	$.ajax
	({
		type:"POST",
		url : base_url+'/afg_c_cost/manage_data_refund',
		data:
		{
			sp_detail_id : $('#spd_id_cost').val(),
			kode_ref : $( '#kode_ref_cost' ).val(),
			table : 'inf_cost_refund_sc',
			id	:'',
			price : '',
			oper : 'add',
		},
		success:function(response)
		{
			var data = $.parseJSON(response);
			$("#table-sc").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
    		return [true];
		}
	})
}

function save_tbl_sc(rowid,cellname,value,oper){
	var grid = $("#table-sc"), i;	
	var qty = grid.jqGrid('getCell', rowid,'qty');
	var unit_price = grid.jqGrid('getCell', rowid,'unit_price');
	$.ajax
	({
		type:"POST",
		url : base_url+'/afg_c_cost/manage_data_refund',
		data:
		{
			spd : $('#spd_id_cost').val(),
			kode_ref : $( '#kode_ref_cost' ).val(),
			id : rowid,
			table : 'inf_cost_refund_sc',
			kolom : cellname,
			isi : value,
			price : unit_price * qty,
			oper : oper,
		},
		success:function(response)
		{
			var data = $.parseJSON(response);
			
		}
	})
}

$('#RC-TDK-PROSES').live("change",function(){
	var rc_tdk_proses = $('#RC-TDK-PROSES').attr('checked');
	var spd_id = $('#spd_id_income').val();
	if (rc_tdk_proses=='checked') {rc_tdk_proses = true;} else {rc_tdk_proses = false;}
	if(rc_tdk_proses==true){
	$.ajax
	({
		type:"POST",
		url : base_url+'/afg_c_cost/clear_refund',
		data:
		{
			kode_ref : $( '#kode_ref_cost' ).val(),
			rc_tdk_proses: rc_tdk_proses,
		},
		success:function(response)
		{
					var data = $.parseJSON(response);
					$('#TRK-DOC').attr('disabled',true);
					$("#table-discount").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
					$('#pager-table-discount_left').attr('hidden',true);
					$("#table-sc").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
					$('#pager-table-sc_left').attr('hidden',true);
				}
			})
		} 
		else 
		{
			$('#TRK-DOC').attr('disabled',false);
			$('#pager-table-discount_left').attr('hidden',false);
			$('#pager-table-sc_left').attr('hidden',false);
		}	
	})
	
	function save_data(){
	var rc_tdk_proses = $('#RC-TDK-PROSES').attr('checked');
	if(rc_tdk_proses=='checked')
		{var rc_tdk_proses = 'true';}
		else
		{var rc_tdk_proses = 'false';}
	$.ajax
	({
		type:"POST",
		url : base_url+'/afg_c_cost/manage_header_cost',
		data:
		{
			kode_ref : $( '#kode_ref_cost' ).val(),
			rc_tdk_proses: rc_tdk_proses,
			kurs:$( '#kurs_cost' ).val(),
		},
		success:function(response)
			{
			var data = $.parseJSON(response);
			if(data.status==1){
				Swal.fire('Data updated');
			}
		}
	})
}

function myAutocomplete(elem, url) {
        $(elem).autocomplete({
        minLength: 3,
		allowClear: true,
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


function _costNormalizePostResponse(response) {
    var data = response;

    // Helper lama kadang mengirim JSON yang masih dibungkus sebagai string.
    for (var i = 0; i < 2 && typeof data === 'string'; i++) {
        var value = $.trim(data);

        if (value === '') {
            return {};
        }

        try {
            data = JSON.parse(value);
        } catch (e) {
            return { raw_response: value };
        }
    }

    return (data && typeof data === 'object') ? data : {};
}

function _costPostResponseIsFailure(data) {
    if (!data || typeof data !== 'object') {
        return false;
    }

    var status = data.status;

    if (
        status === false ||
        status === 0 ||
        status === '0' ||
        String(status).toLowerCase() === 'false' ||
        String(status).toLowerCase() === 'error' ||
        String(status).toLowerCase() === 'failed' ||
        String(status).toLowerCase() === 'fail'
    ) {
        return true;
    }

    if (
        data.success === false ||
        data.success === 0 ||
        data.success === '0' ||
        String(data.success).toLowerCase() === 'false'
    ) {
        return true;
    }

    return false;
}

// Pesan error API: prioritaskan validasi server, bukan wrapper generik.
function _costPostErrorMessage(data, fallbackMessage) {
    function clean(value) {
        if (typeof value !== 'string') return '';
        value = value.trim();
        if (!value || /^\s*<(?:!doctype|html|head|body)\b/i.test(value)) return '';
        return value.split(/(?:\\r|\\n|\r|\n)*Traceback\s*\(most recent call last\)/i)[0]
            .replace(/^\w+(?:Error|Exception)\(u?['"]/, '')
            .replace(/['"]\s*,?\s*\)\s*$/, '')
            .replace(/(?:\\r|\\n|\r|\n)+$/, '').trim();
    }
    function collect(value, depth) {
        if (depth > 8 || value == null) return [];
        if (typeof value === 'string') {
            try { return collect(JSON.parse(value), depth + 1); } catch (e) {}
            if (/^\s*[\[{]/.test(value)) return [];
            var message = clean(value);
            return message ? [message] : [];
        }
        if (Array.isArray(value)) {
            return value.reduce(function (all, item) {
                return all.concat(collect(item, depth + 1));
            }, []);
        }
        if (typeof value === 'object') return collect(value.message, depth + 1);
        return [];
    }
    function unique(messages) {
        return messages.filter(function (message, i) {
            return messages.indexOf(message) === i;
        }).join('\n');
    }
    function extract(value, depth) {
        if (depth > 8 || value == null) return '';
        if (typeof value === 'string') {
            try { return extract(JSON.parse(value), depth + 1); } catch (e) {}
            return clean(value);
        }
        if (typeof value !== 'object') return '';
        var messages = collect(value._server_messages, 0);
        if (messages.length) return unique(messages);
        var nested = ['remote', 'remote_response', 'response', 'data'];
        for (var i = 0; i < nested.length; i++) {
            var detail = extract(value[nested[i]], depth + 1);
            if (detail) return detail;
        }
        messages = collect(value.message, 0);
        if (!messages.length) messages = collect(value.error, 0);
        return unique(messages);
    }
    return extract(data, 0) || fallbackMessage;
}

function _costPostResponseMessage(data, fallbackMessage) {
    if (!data || typeof data !== 'object') {
        return fallbackMessage;
    }

    // Jangan kirim object langsung ke SweetAlert karena akan menjadi [object Object].
    if (typeof data.message === 'string' && $.trim(data.message) !== '') {
        return data.message;
    }

    if (
        data.data &&
        typeof data.data.message === 'string' &&
        $.trim(data.data.message) !== ''
    ) {
        return data.data.message;
    }

    if (typeof data.error === 'string' && $.trim(data.error) !== '') {
        return data.error;
    }

    return fallbackMessage;
}

function _costCurrentDateTime() {
    var date = new Date();

    function pad(value) {
        return value < 10 ? '0' + value : String(value);
    }

    return date.getFullYear() + '-' +
        pad(date.getMonth() + 1) + '-' +
        pad(date.getDate()) + ' ' +
        pad(date.getHours()) + ':' +
        pad(date.getMinutes()) + ':' +
        pad(date.getSeconds());
}

function _costUpdateLastPosted(data) {
    data = data || {};

    // Response baru dapat menaruh metadata pada data,
    // sedangkan response lama langsung berisi object ERP.
    var postedData = (
        data.data &&
        typeof data.data === 'object' &&
        !Array.isArray(data.data)
    ) ? data.data : data;

    var postedBy =
        postedData.posted_by ||
        postedData.created_by ||
        <?php echo json_encode((string) $this->session->userdata('fullname')); ?> ||
        '';

    var postedAt =
        postedData.posted_at ||
        postedData.created_at ||
        _costCurrentDateTime();

    var postedText =
        postedData.last_posted_text ||
        $.trim(postedBy + ' ' + postedAt);

    $('#customer').val(postedText);
    $('#post').val(postedAt);
}

function get_proses_post() {
    var kode_ref     = $('#kode_ref_cost').val();
    var region_id    = '<?php echo $this->session->userdata('region_id'); ?>';
    var sp_detail_id = $('#spd_id_cost').val();
    var kurs         = $('#kurs_cost').val();

    // Routing region dilakukan di curl_helper.php pada server.

    // 2. Validasi Input Kurs
    if (!kurs || parseFloat(kurs) <= 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Validasi Gagal',
            text: 'Nilai Kurs tidak boleh kosong atau nol!'
        });
        return;
    }

    // 3. Ambil data baris dari jqGrid
    var gridCost     = $('#table-cost').jqGrid('getRowData');
    var gridDiscount = $('#table-discount').jqGrid('getRowData');
    var gridSc       = $('#table-sc').jqGrid('getRowData');

    if (
        gridCost.length === 0 &&
        gridDiscount.length === 0 &&
        gridSc.length === 0
    ) {
        Swal.fire({
            icon: 'info',
            title: 'Data Kosong',
            text: 'Tidak ada data cost, discount, atau SC untuk diposting.'
        });
        return;
    }

    // 4. Validasi setiap baris tabel Cost
    for (var i = 0; i < gridCost.length; i++) {
        var row         = gridCost[i];
        var rowNum      = i + 1;
        var itemService = $.trim(row.item_service || '');
        var currency    = $.trim(row.currency || '').replace(/&nbsp;/g, '');
        var payableTo   = $.trim(row.payable_to || '');
        var qty         = parseNumber(row.qty);
        var unitPrice   = parseNumber(row.unit_price);

        if (
            !itemService ||
            qty <= 0 ||
            unitPrice <= 0 ||
            !currency ||
            currency === ':' ||
            !payableTo
        ) {
            Swal.fire({
                icon: 'error',
                title: 'Data Belum Lengkap',
                text:
                    'Mohon lengkapi (Item Service, Qty, Price/Unit, Currency, ' +
                    'dan Payable To) pada baris ke-' + rowNum +
                    ' sebelum memposting data.'
            });
            return;
        }
    }

    Swal.fire({
        title: 'Memproses Posting...',
        text: 'Mohon tunggu sebentar',
        allowEscapeKey: false,
        allowOutsideClick: false,
        didOpen: function () {
            Swal.showLoading();
        }
    });

    $.ajax({
        type: 'POST',
        url: "<?php echo site_url('finance/c_post_data_acct_per_job/proses_post')?>",
        dataType: 'json',
        data: {
            region: region_id,
            kode_ref: kode_ref,
            sp_detail_id: sp_detail_id,
            kurs: kurs
        },

        beforeSend: function () {
            $('#button-post')
                .prop('disabled', true)
                .val('POSTING...');
        },

        success: function (response) {
            var data = _costNormalizePostResponse(response);

            /*
             * Endpoint lama mengembalikan object ERP seperti:
             * {"message":{"Sales Order":{...},"Journal Entry":{...}}}
             * Tanpa status:true. Karena callback success berarti HTTP 2xx,
             * response tersebut tetap dianggap berhasil.
             */
            if (_costPostResponseIsFailure(data)) {
                Swal.fire({
                    icon: 'error',
                    title: 'Posting Gagal',
                    text: _costPostErrorMessage(
                        data,
                        'Response server menyatakan proses posting gagal.'
                    )
                });
                return;
            }

            _costUpdateLastPosted(data);
            refresh_table();

            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: _costPostResponseMessage(
                    data,
                    'Data cost sudah diposting ke AP, menunggu proses pembayaran.'
                ),
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'OK'
            });
        },

        error: function (xhr) {
            var data = {};

            if (xhr.responseJSON) {
                data = _costNormalizePostResponse(xhr.responseJSON);
            } else if (xhr.responseText) {
                data = _costNormalizePostResponse(xhr.responseText);
            }

            Swal.fire({
                icon: 'error',
                title: 'Posting Gagal',
                text: _costPostErrorMessage(
                    data,
                    'Terjadi kesalahan saat menghubungi server.'
                )
            });
        },

        complete: function () {
            $('#button-post')
                .prop('disabled', false)
                .val(' POST ');
        }
    });
}

// util: cek kolom payable_to kosong di satu grid
function hasEmptyPayable(gridSel) {
  var grid = $(gridSel);
  var ids = grid.jqGrid('getDataIDs') || [];
  var found = false;

  // akhiri edit cell yang masih aktif (kalau ada)
  try { grid.jqGrid('saveCell', grid.jqGrid('getGridParam','iRow'), grid.jqGrid('getGridParam','iCol')); } catch(e){}

  for (var i = 0; i < ids.length; i++) {
    var id = ids[i];

    // ambil nilai payable_to (bersihkan dari HTML)
    var raw = grid.jqGrid('getCell', id, 'payable_to');
    var payable = $.trim($('<div>').html(raw == null ? '' : raw).text());

    if (!payable) {
      // optional: highlight sel kosong
      grid.jqGrid('setCell', id, 'payable_to', '', { background: '#ffecec' });
      found = true;
    }
  }
  return found;
}

// blokir penutupan modal bila ada payable_to kosong
$('#Form-Cost').on('hide.bs.modal', function (e) {
  // cek ketiga grid
  var emptyInAny =
    hasEmptyPayable('#table-cost') ||
    hasEmptyPayable('#table-discount') ||
    hasEmptyPayable('#table-sc');

  if (emptyInAny) {
    e.preventDefault();
    e.stopImmediatePropagation();
    Swal.fire({
      title: 'Lengkapi data',
      text: 'Masih ada kolom "Payable To" yang kosong. Mohon lengkapi dulu dan post ulang.',
      icon: 'warning'
    });
    return false;
  }
});

// Trigger simpan otomatis ketika PPH 23 dicentang/dihapus
$(document).on('change', '#table-cost input[type="checkbox"]', function(e) {
    var $td = $(this).closest('td');
    
    // Abaikan jika yang diklik adalah checkbox bawaan jqGrid untuk multi-select row
    if ($td.attr('aria-describedby') === 'table-cost_cb') {
        return; 
    }

    var rowid = $(this).closest('tr').attr('id');
    var grid = $("#table-cost");
    var colModel = grid.jqGrid('getGridParam', 'colModel');
    var colName = colModel[$td.index()].name;
    
    // Jika checkbox berada di kolom PPH 23 (nama id di jqgrid: 'ppn')
    if (colName === 'ppn') {
        var isChecked = $(this).is(':checked') ? 'true' : 'false';
        // Memanfaatkan fungsi existing untuk langsung tembak ajax ke database
        save_tbl_satu(rowid, colName, isChecked);
    }
});

// Trigger simpan otomatis ketika PPH 23 dicentang/dihapus
$(document).on('change', '#table-discount input[type="checkbox"]', function(e) {
    var $td = $(this).closest('td');
    
    // Abaikan jika yang diklik adalah checkbox bawaan jqGrid untuk multi-select row
    if ($td.attr('aria-describedby') === 'table-discount_cb') {
        return; 
    }

    var rowid = $(this).closest('tr').attr('id');
    var grid = $("#table-discount");
    var colModel = grid.jqGrid('getGridParam', 'colModel');
    var colName = colModel[$td.index()].name;
    
    // Jika checkbox berada di kolom PPH 23 (nama id di jqgrid: 'ppn')
    if (colName === 'ppn') {
        var isChecked = $(this).is(':checked') ? 'true' : 'false';
        // Memanfaatkan fungsi existing untuk langsung tembak ajax ke database
        save_tbl_satu(rowid, colName, isChecked);
    }
});

// Trigger simpan otomatis ketika PPH 23 dicentang/dihapus
$(document).on('change', '#table-sc input[type="checkbox"]', function(e) {
    var $td = $(this).closest('td');
    
    // Abaikan jika yang diklik adalah checkbox bawaan jqGrid untuk multi-select row
    if ($td.attr('aria-describedby') === 'table-sc_cb') {
        return; 
    }

    var rowid = $(this).closest('tr').attr('id');
    var grid = $("#table-sc");
    var colModel = grid.jqGrid('getGridParam', 'colModel');
    var colName = colModel[$td.index()].name;
    
    // Jika checkbox berada di kolom PPH 23 (nama id di jqgrid: 'ppn')
    if (colName === 'ppn') {
        var isChecked = $(this).is(':checked') ? 'true' : 'false';
        // Memanfaatkan fungsi existing untuk langsung tembak ajax ke database
        save_tbl_satu(rowid, colName, isChecked);
    }
});

</script>

















