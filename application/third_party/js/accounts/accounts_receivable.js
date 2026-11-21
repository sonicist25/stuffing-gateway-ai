var table = 'TBL_ACC_RCV';

$(function () {
	get_table()
})

get_table = function() {
	var grid = $("#table"), i;	
	grid.jqGrid({ 
		url: 'accounts/get_table_receivable',
		postData:{},
		mtype : "post",
		datatype: "json", 
		colNames:
		[
			'Tanggal Jurnal',
			'Type',
			'No. Jurnal',
			'No. INV',
			'Customer',
			'No. JOB',
			'Curr',
			'Total Inv',
			'Grand Total',
			'Jumlah USD',
			'Jumlah RP',
			'Overdue USD',
			'Overdue RP',
			'Lunas',
			'Nomor'
		],
    	colModel:
		[
			{name:'tanggal_byr',index:'tanggal_byr', align:"left", width:90, align:"center",
				editable: true,
				 editoptions:{size:20, 
                  dataInit:function(el){ 
				  $(el).datepicker({dateFormat:'yyyy-mm-dd'}); }
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
			{name: 'no_inv', index: 'no_inv', align: "left", width: 155, editable: true,
			stype: "text",
			searchoptions: {
				sopt: ['cn', 'eq']
			},
			edittype: "select",
			editoptions: {
				value: {},
				dataInit: function(element) {
					$(element).select2({
						placeholder: '',
						minimumInputLength:3,
						multiple: true,
						width: '100%',
						ajax: {
							url: window.location.origin + '/afg_c_dropdown/dropdownInvoiceAR',
							dataType: 'json',
							delay: 250,
							data: function (params) {
								return {
									term: params.term, // search term
								};
							},
                    processResults: function (data) {
                        return {
                            results: $.map(data, function(item) {
                                return {
									id: item.id,
                                   text: item.number,
								   job_no: item.job_no,
								   curr: item.currency,
								   total: item.grand_total,
								   kepada: item.kepada,
								   code: item.kepada_kode,
                                };
                                        })
                                    };
                                },
                                cache: false
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
                                    return item.job_no;
                                }).join(',');
                                var total = selectedItems.map(function(item) {
                                    return item.total;
                                }).join(',');
								var currency = data.curr == "USD" ? "USD" : "IDR";
								var totalSum = selectedItems.reduce(function(sum, item) {
                                    return sum + parseFloat(item.total);
                                }, 0);
								var values = selectedItems.map(function(item) {
									return item.text;
								}).join(',');
								//console.log(values);
									var val = data.text;
									var kpd = data.kepada;
									var code = data.code;
									updateshipper(val,kpd,code,values,jobNumbers,currency,total,totalSum.toFixed(2));
								}
							return data.text;
                            }
                        });
                    },
					}
				},
			{name:'pay_to',index:'pay_to', align:"left",width:155, editable: false, 
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],  
				}
			},
			{name:'no_job',index:'no_job', align:"left",width:155,
				editable: true,  
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],  
				},
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
				name:'ttl_rp',
				index:'ttl_rp', 
				align:"right",
				width:100, 
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
				editable: false,
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
				name:'jumlah_usd',
				index:'jumlah_usd', 
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
				name:'jumlah_rp',
				index:'jumlah_rp', 
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
				},
				editoptions:{
					
					dataEvents: 
					[{  type: 'focus',
                  	fn: function(e) {
                       	var rowid = grid.jqGrid('getGridParam', 'selrow');
                       	var qty = grid.jqGrid('getCell',rowid,'total_tagihan');
                       	var unit_price = grid.jqGrid('getCell',rowid,'jumlah_rp');
                       	var price = eval(qty) - eval(unit_price);
                        grid.jqGrid('setCell',rowid,'saldo_rp',price);
                   	}
					}]
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
			{name:'nomor',index:'nomor', align:"left",width:155, editable: false, hidden : true, 
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],  
				}
			},
		], 
		sortname: "tanggal_byr",
		sortorder: "desc",
		shrinkToFit: true,
  		multiselect:true,
		multiPageSelection: true, 
		loadonce : true,
		rowNum:50,
		width: 1350,
		height: 'auto',
		rowList:[50,100,150],
		viewrecords: true,
		rownumbers: true,
		gridview: true,
		modal: true,
		pager:'#footer',
		editurl :'accounts/manage_data',
		cellEdit:true,
		cellsubmit:'clientArray',
		caption:"Accounts Receivable",	
		
		loadComplete: function() {
			$(".ui-jqgrid-titlebar-close",$("#table")[0].grid.cDiv).hide();
    	},
    	
    	beforeSelectRow: function(rowid, e) {
		var lock =  grid.jqGrid('getCell',rowid,'currency');
					if(lock == 'IDR'){
					var prefik = "Rp";
					$(this).jqGrid('setColProp','jumlah_usd', { editable:false });
					$(this).jqGrid('setColProp','saldo_usd', { editable:false });
					$(this).jqGrid('setColProp','jumlah_rp', { editable:true });
					$(this).jqGrid('setColProp','saldo_rp', { editable:true });					
					}else{
					var prefik = "$";
					$(this).jqGrid('setColProp','jumlah_rp', {editable:false });
					$(this).jqGrid('setColProp','saldo_rp', { editable:false });
					$(this).jqGrid('setColProp','jumlah_usd', { editable:true});
					$(this).jqGrid('setColProp','saldo_usd', { editable:true });
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
     		}
        	else
        	{
        		var cbs = $("tr.jqgrow > td > input.cbox:disabled", grid[0]);  			
        	}
		},
		
		afterSaveCell: function(rowid, cellname, value, iRow, iCol) { 
			var curr =  grid.jqGrid('getCell',rowid,'currency');
			if (curr == 'IDR'){
			var qty = grid.jqGrid('getCell',rowid,'total_tagihan');
			var unit_price = grid.jqGrid('getCell',rowid,'jumlah_rp');
			var price = eval(qty) - eval(unit_price);
			grid.jqGrid('setCell',rowid,'saldo_rp',price);
			price > 0 ? grid.jqGrid('setCell',rowid,'pelunasan',"No"):grid.jqGrid('setCell',rowid,'pelunasan',"Yes");
			}else{
			var qty = grid.jqGrid('getCell',rowid,'total_tagihan');
			var unit_price = grid.jqGrid('getCell',rowid,'jumlah_usd');
			var price = eval(qty) - eval(unit_price);
			grid.jqGrid('setCell',rowid,'saldo_usd',price);
			price > 0 ? grid.jqGrid('setCell',rowid,'pelunasan',"No"):grid.jqGrid('setCell',rowid,'pelunasan',"Yes");		
			}
			save_data(rowid, cellname, value, iRow, iCol);
			if(event.keyCode===13) 
			{
				grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
			}
    	},
		afterEditCell: function(rowid, cellname, value, iRow, iCol) { 
         /* save_data(rowid, cellname, value, iRow, iCol);
			if(event.keyCode===13) 
			{
				grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
			}*/
        } 
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
			position: "first", 
			title:"Insert Data", 
			cursor: "pointer",
			id:"btnAddData",
	}),
	grid.navButtonAdd('#footer',{ 
        caption:"",
        buttonicon:"ui-icon-circle-arrow-s",
        onClickButton: download_excel,
        position: "last",
        title:"Download Excel",
        cursor: "pointer"
    });
	$("#table").trigger("reloadGrid", [{current:true}]);
	grid.jqGrid('filterToolbar', { stringResult: false, searchOnEnter: false, defaultSearch: "cn" });
};

save_data = function(rowid, cellname, value, iRow, iCol) {
    var grid = $("#table");
    var mydata = grid.getRowData(rowid);
    var data = {
        oper: 'edit',
        id: rowid,
        mydata: mydata
    };
    $.post('accounts/manage_data', data, 'json')
    .done(function(d, s, e) {
        var responseData = $.parseJSON(d);
        if (responseData.status == 1) {
            grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
        }
    });
};

insert_data = function() {
	var grid = $("#table"), i;
	$.post('accounts/manage_data',{oper:'add',table:table,id:'', bulan: $('#tanggal').val()},'json')
	.done(function(d,s,e) {
		var data = $.parseJSON(d);
		if (data.status==1) {
			grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
		}
	})
}

update_data = function() {
	var grid = $("#table"), i;
	var where={};
	where.tanggal = $('#tanggal').val();
	$.get('accounts/update_data',{oper:'add',table:table,id:'', whr: where},'json')
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

function allEqual(arr) {
  return new Set(arr).size == 1;
}

updateshipper=function(val,kpd,code,values,jobNumbers,currency,total,totalSum){
	//console.log(val);
	var grid = $("#table"), i;
	var rowid = grid.jqGrid('getGridParam', 'selrow');
	var myarray =  [];
	myarray.push(grid.jqGrid('getCell',rowid,'pay_to'));
    $.ajax
	({
		type:"POST",
		url:window.location.origin+'/finance/accounts/update_row_shipper',
		data:
		{
			inv: val,kpd:kpd,code:code
		},
		success:function(response)
		{
			var data = $.parseJSON(response);
			if (myarray != ''){
			myarray.push(data);
			}
			var samagak = allEqual( myarray ) ;// true
			console.log(myarray);
			if(samagak == true){
			grid.jqGrid('setCell',rowid,'pay_to',data);	
			grid.jqGrid('setCell',rowid,'nomor',values);
			grid.jqGrid('setCell', rowid, 'no_job', jobNumbers);
			grid.jqGrid('setCell', rowid, 'currency', currency);
			grid.jqGrid('setCell', rowid, 'ttl_rp', total);
			grid.jqGrid('setCell', rowid, 'total_tagihan', totalSum);			
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
function download_excel() {
	var grid = $("#table");	
	var number="sell";
	var selIds = grid.jqGrid("getGridParam", "selarrrow"), i, n;
	var arrStr = encodeURIComponent(selIds);
	//console.log(selIds);
	if (arrStr .length === 0){
	var number="no";
	var i, rowid = grid.jqGrid('getGridParam','_index'), n, rowData;
	var id = JSON.stringify(rowid);
	var arrStr = encodeURIComponent(id);
	//console.log(id);	
	}
	$('#form-excel-ar').html('<div style="margin-bottom: 10px">'+
	'<center>'+
	'<iframe id="frame_invoice" src="'+window.location.origin+'/finance/Accounts/download_excel/'+arrStr+'/'+number+'" width="310px" height="60"></iframe>'+
	'</center></div>');
}
function myfunction (tipe) {
	var grid = $("#table"), i;
	var rowid = grid.jqGrid('getGridParam', 'selrow');
	var tgl= grid.jqGrid('getCell',rowid,'tanggal_byr');
	var values = tipe+'-IN-'+tgl.replace(/-/g, '')+'-'+rowid;
	grid.jqGrid('setCell', rowid, 'id_fin', values);
}