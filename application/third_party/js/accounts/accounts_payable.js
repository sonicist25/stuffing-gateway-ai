var table = 'TBL_ACC_PAY';

$(function () {
	get_table()
})

get_table = function() {
	var grid = $("#table"), i;	
	grid.jqGrid({ 
		url: 'accounts/get_table',
		mtype : "get",
		postData:{},
		datatype: "json", 
		colNames:
		[
			'Tanggal',
			'Type',
			'No. Jurnal',
			'Job',
			'Pay to',
			'',
			'Jumlah',
			'Jumlah Bayar',
			'Saldo USD',
			'Saldo IDR',
			'Pelunasan',
			'',
			'',
			''
		],
    	colModel:
		[
			{name:'tanggal',index:'tanggal', width:90, align:"center",
				editable: true,
				 editoptions:{size:20, 
                  dataInit:function(el){ 
				  $(el).datepicker({dateFormat:'yy-mm-dd'}); }
                  },
    			stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],  
				},
			},
			{name:'type',index:'type', align:"center", width:70, editable: true,
				//formatter: "checkbox",
				edittype:"select",
				editoptions:{
					value:" : ;Bank:Bank;Cash:Cash",
					dataInit: function(element) {
        				$(element).css({ "text-align": "center", "max-width":"70" })
					},
					dataEvents: [
					{ type: 'change', fn: function(e) {myfunction($(this).val());
					console.log("value", $(this).val(), $(this).find("option:selected").val());
					} },

					],
				},
			},
			{
             name:'id_fin',index:'id_fin', sortable: false, filterable: false, editable: false,
			},
			{name: 'no_job', index: 'no_job', align: "left", width: 355, editable: true,
			stype: "text",
			searchoptions: {
				sopt: ['cn', 'eq']
			},
			edittype: "select",
			editoptions: {
				value: {},
				dataInit: function(element) {
					  // Initialize Select2
					$(element).select2({
					placeholder: '',
					multiple: true,
					width: '100%',
					ajax: {
					url:  window.location.origin + '/afg_c_dropdown/dropdownJobAP', // Update with your URL
					dataType: 'json',
					delay: 250,
						data: function (params) {
						return {
                        q: params.term // Query parameter
						};
					},
					processResults: function (data) {
                    // Parse the results into the format expected by Select2
                    return {
                        results: $.map(data, function(item) {
                            return {
                                id: item.kode_ref + '-' + item.item_service,
                                text: item.kode_ref + ' - ' + item.item_service,
								payable_to : item.payable_to,
								status : item.status,
								price : item.price,
								currency: item.currency,
								kode_ref: item.id,
                                items:item.table_name,
								};
							})
						};
					},
                cache: true
				},
				     // Update the 'no_job' column when an item is selected
				templateResult: function(data) {
                                return $('<span>' + data.text + '</span>');
                            },
                         templateSelection: function(data) {
                            var rowId = grid.jqGrid('getGridParam', 'selrow');
                            if (rowId) {
                                var selectedItems = $(element).select2('data');
                                var jobNumbers = selectedItems.map(function(item) {
                                    return data.text;
                                }).join(',');
                                var price = selectedItems.map(function(item) {
                                    return item.price;
                                }).join(',');
								var totalSum = selectedItems.reduce(function(sum, item) {
                                    return sum + parseFloat(item.price);
                                }, 0);
								var values = selectedItems.map(function(item) {
									return item.kode_ref;
								}).join(',');
								var services = selectedItems.map(function(item) {
									return item.items;
								}).join(',');
								var payable_to = data.payable_to;
								var currency = data.currency == "USD" ? "USD" : "IDR";
								updatepayable(payable_to,values,services,jobNumbers,currency,price,totalSum.toFixed(2));
								}
							return data.text;
                            }
				});
                    },
					}
			},
			{name:'pay_to',index:'pay_to', align:"left",width:155, editable: true, 
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],  
				}
			},
			{
				name:'currency',
				index:'currency', 
				align:"center",
				width:25, 
				editable: false,
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],  
				},
			},
			{
				name:'total_tagihan',
				index:'total_tagihan', 
				align:"right",
				width:100, 
				editable: true,
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],  
				},
				formatter: "number",
    			formatoptions: { 
					decimalSeparator: ".", 
					thousandsSeparator: " ", 
					decimalPlaces: 2, 
					defaultValue: ''
				},
			},
				{
				name:'total_bayar',
				index:'total_bayar', 
				align:"right",
				width:100, 
				editable: true,
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],  
				},
				formatter: "number",
    			formatoptions: { 
					decimalSeparator: ".", 
					thousandsSeparator: " ", 
					decimalPlaces: 2, 
					defaultValue: ''
				},
			},
			{
				name:'saldo_usd',
				index:'saldo_usd', 
				align:"right",
				width:100, 
				editable: true,
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],  
				},
				formatter: "number",
    			formatoptions: { 
					decimalSeparator: ".", 
					thousandsSeparator: ",", 
					decimalPlaces: 2, 
					defaultValue: ''
				},
			},
			{
				name:'saldo_rp',
				index:'saldo_rp', 
				align:"right",
				width:100, 
				editable: true,
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],  
				},
				formatter: "number",
    			formatoptions: { 
					decimalSeparator: ".", 
					thousandsSeparator: ",", 
					decimalPlaces: 2, 
					defaultValue: ''
				}
			},
			{
			name:'pelunasan',
			index:'pelunasan', 
			align:"center",
			width: 70, align: "center",
            formatter: "checkbox", formatoptions: { disabled: false},
            edittype: "checkbox", editoptions: {value: "Yes:No", defaultValue: "No"},
            stype: "select", searchoptions: { sopt: ["eq", "ne"], 
                value: ":Any;true:Yes;false:No" } 
			},
			{name:'nomor_usd',index:'nomor_usd', align:"left",width:155, editable: false, hidden : true,
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],  
				}
			},
			{name:'kode_ref',index:'kode_ref', align:"left",width:155, editable: false, hidden : true, 
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],  
				}
			},
			{name:'item',index:'item', align:"left",width:155, editable: false, hidden : true, 
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],  
				}
			},
		], 
		sortname: "id",
		sortorder: "desc",
		shrinkToFit: true,
  		multiselect:true,
		loadonce : false,
		cellEdit: false,
		rowNum:15,
		width: 1350,
		height: 'auto',
		rowList:[50,100,150],
		viewrecords: true,
		rownumbers: true,
		gridview: true,
		modal: true,
		pager:'#footer',
		editurl :'accounts/manage_data_pay',
		cellEdit:true,
		cellsubmit:'clientArray',
		caption:"Accounts Payable",	
		
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
			var kode_ref = grid.jqGrid('getCell',rowid,'job_no');	
			selected[rowid] = status;
			if(status)
			{ 
				var cbs = $("tr.jqgrow > td > input.cbox", grid[0]);
     		}
        	else
        	{
        		var cbs = $("tr.jqgrow > td > input.cbox:disabled", grid[0]);  			
        	}
		},
		
		afterSaveCell: function(rowid, cellname, value, iRow, iCol) { 
			var curr =  grid.jqGrid('getCell',rowid,'currency');
			var rowIds = grid.jqGrid('getDataIDs');
			var currentRowIndex = rowIds.indexOf(rowid);
			 if (currentRowIndex > 0) { // Check if there is a previous row
				var previousRowId = rowIds[currentRowIndex - 1];
			 }
			if (curr == 'IDR'){
			var qty = grid.jqGrid('getCell',rowid,'total_tagihan');
			var unit_price = grid.jqGrid('getCell',rowid,'total_bayar');
			var price = eval(qty) - eval(unit_price);
			var previousRowData = grid.jqGrid('getCell', previousRowId,'saldo_rp');
			var previousRowDataUSD = grid.jqGrid('getCell', previousRowId,'saldo_usd');
			var saldo = eval(previousRowData) + eval(unit_price);
			grid.jqGrid('setCell',rowid,'saldo_rp',saldo);
			grid.jqGrid('setCell',rowid,'saldo_usd',previousRowDataUSD);
			price > 0 ? grid.jqGrid('setCell',rowid,'pelunasan',"No"):grid.jqGrid('setCell',rowid,'pelunasan',"Yes");
			}else{
			var qty = grid.jqGrid('getCell',rowid,'total_tagihan');
			var unit_price = grid.jqGrid('getCell',rowid,'total_bayar');
			var price = eval(qty) - eval(unit_price);
			var previousRowDataUSD = grid.jqGrid('getCell', previousRowId,'saldo_usd')
			var previousRowData = grid.jqGrid('getCell', previousRowId,'saldo_rp');
			var saldo = eval(previousRowDataUSD) + eval(unit_price);
			grid.jqGrid('setCell',rowid,'saldo_usd',saldo);
			grid.jqGrid('setCell',rowid,'saldo_rp',previousRowData);
			price > 0 ? grid.jqGrid('setCell',rowid,'pelunasan',"No"):grid.jqGrid('setCell',rowid,'pelunasan',"Yes");		
			}
			save_data(rowid, cellname, value, iRow, iCol);
			if(event.keyCode===13) 
			{
				grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
			}
    	},
	})
	grid.navGrid('#footer',{ edit: false, add: false, del: true,},
		{},{},{
			autoOpen: true,
			modal:true,
			height: "auto", 
			width: "auto",
			serializeDelData: function (data) {
				return {
					oper:data.oper,
					table:table,
					id:data.id
				};
			},
			afterSubmit: function () {
				grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
				return [true];
			}
		},{
			modal: true, 
			height: "auto", 
			width: "auto", 
			closeAfterSearch : true, 
			multipleSearch: false, 
			caption: "Search Data"
		}
	
	);
	grid.navButtonAdd('#footer',{ 
			caption:"", 
			buttonicon:"ui-icon-plus", 
			onClickButton: insert_data, 
			position: "last", 
			title:"Insert Data", 
			cursor: "pointer",
			id:"btnAddData",
	})

	var div = $(".ui-jqgrid-bdiv");
 	var nx = div.length-1;
 	var divx = div[nx];
 	$(divx).css({'min-height':'300px'});
	grid.jqGrid('filterToolbar', { stringResult: false, searchOnEnter: false, defaultSearch: "cn" });
};

save_data = function(rowid, cellname, value, iRow, iCol) {
    var grid = $("#table");
	var rowid = grid.jqGrid('getGridParam', 'selrow');
	//console.log(rowid);
    var mydata = grid.getRowData(rowid);
    var data = {
        oper: 'edit',
        id: rowid,
		table : table,
        mydata: mydata
    };
    $.post('accounts/manage_data_pay', data, 'json')
    .done(function(d, s, e) {
        var responseData = $.parseJSON(d);
        if (responseData.status == 1) {
            grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
        }
    });
}

insert_data = function() {
	var grid = $("#table"), i;
	$.post('accounts/manage_data_pay',{oper:'add',table:table,id:'', bulan: $('#tanggal').val()},'json')
	.done(function(d,s,e) {
		var data = $.parseJSON(d);
		if (data.status==1) {
			grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
		}
	})
}

jin_date_sql = function( cellvalue ) {
	var date = '';
	if(cellvalue != undefined ) {
		date = cellvalue.substr(6,4) + cellvalue.substr(3,2) + cellvalue.substr(0,2)
	}
	return date;
}

jin_date_str = function( cellvalue, options, rowObject ) {
	var date = '';
	if(cellvalue != undefined ) {
		date = cellvalue.substr(6,2) + '/' + cellvalue.substr(4,2)+ '/' + cellvalue.substr(0,4)
	}
	return date;
}
jin_date_sql = function( cellvalue ) {
	var date = '';
	if(cellvalue != undefined ) {
		date = cellvalue.substr(6,4) + cellvalue.substr(3,2) + cellvalue.substr(0,2)
	}
	return date;
}

jin_date_str = function( cellvalue, options, rowObject ) {
	var date = '';
	if(cellvalue != undefined ) {
		date = cellvalue.substr(6,2) + '/' + cellvalue.substr(4,2)+ '/' + cellvalue.substr(0,4)
	}
	return date;
}
function myfunction (tipe) {
	var grid = $("#table"), i;
	var rowid = grid.jqGrid('getGridParam', 'selrow');
	var tgl= grid.jqGrid('getCell',rowid,'tanggal');
	var values = tipe+'-'+tgl.replace(/-/g, '')+'-'+rowid;
	grid.jqGrid('setCell', rowid, 'id_fin', values);
}
function allEqual(arr) {
  return new Set(arr).size == 1;
}
function updatepayable(payable_to,values,services,jobNumbers,currency,prices,totalSum){
	//console.log(val);
	var grid = $("#table"), i;
	var rowid = grid.jqGrid('getGridParam', 'selrow');
	var myarray =  [];
	myarray.push(grid.jqGrid('getCell',rowid,'pay_to'));
    $.ajax
	({
		type:"POST",
		url:window.location.origin+'/finance/accounts/update_payable',
		data:
		{
			kepada: payable_to
		},
		success:function(response)
		{
			var data = $.parseJSON(response);
			if (myarray != ''){
			myarray.push(data);
			}
			var samagak = allEqual( myarray ) ;// true
			//console.log(currency);
			if(samagak == true){
			grid.jqGrid('setCell',rowid,'pay_to',data);
			grid.jqGrid('setCell',rowid,'nomor_usd',jobNumbers);
			grid.jqGrid('setCell', rowid, 'currency', currency);
			grid.jqGrid('setCell', rowid, 'total_tagihan', totalSum);			
			grid.jqGrid('setCell', rowid, 'kode_ref', values);			
			grid.jqGrid('setCell', rowid, 'item', services);			
			}else{
			swal({
			icon: 'error',
			title: 'Oops...',
			text: 'Tidak bisa multi payment untuk customer berbeda!',
			footer: '<a href="">Multi payment hanya untuk 1 akun customer</a>'
			});
			grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
			}
		},
	})
}