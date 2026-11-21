<?php // echo ! empty($h1_title) ? '<h1>' . $h1_title . '</h1>': ''; ?>
<style type="text/css">
.container {
          position: relative; /* Dibutuhkan agar div-geser bisa diposisikan relatif terhadapnya */
}
.div-geser {
          position: absolute;
          right: 300px;
          left: -130px;
}
.group-header {
	color: white;
    display: block;
    text-align: center;
    font-weight: bold;
    padding: 10px 0; /* Add vertical padding */
    background: linear-gradient(to bottom, #3b7495, #224197);
    border-radius: 5px;
}
.group-subheader {
	color: white;
    display: block;
    text-align: center;
    font-weight: bold;
    padding: 5px;
    background: linear-gradient(to top, #224197, #FFFFFF);
    border-radius: 5px;
}

.group-subheaderadd {
    display: block;
    text-align: left;
    font-weight: bold;
    padding: 5px;
    background: #f4f4f4;
    border-radius: 5px;
}

.custom-column-header {
    font-weight: bold;
    background: #FFF100;
    text-align: center;
}
</style>

<div id="form-main-page" class="container">
	<div class="div-geser">
		<table id="table" ></table>
		<div id="pager"></div>
	</div>	
</div>

<div id="Form-Destination"></div>

<div id="PRINT"></div>

<script type="text/javascript">
$(document).ready(function () 
{
	var grid = $("#table"), i;
	var user_id = <?php echo $this->session->userdata('user_id'); ?>;
	var editPermission = !(user_id !== 0 && user_id !== 211 && user_id !== 260 && user_id !== 566);
	var addPermission = !(user_id !== 0 && user_id !== 211 && user_id !== 260 && user_id !== 566);
	var delPermission = !(user_id !== 0 && user_id !== 211 && user_id !== 260 && user_id !== 566);
	grid.jqGrid(
	{ 
		url:"<?php echo base_url('pricelist/air_freight_feeder_rate/get_table_data')?>",
		mtype : "post",
		datatype: "json", 	
		colNames:
		[
			'AIRLINE','','SHC','COMMODITY','ORIGIN','','DESTINATION','','M','N','45KGs','100KGs','300KGs','500KGs','1000KGs','VALID UNTIL', 'REMARK'
		],
    	colModel:
		[
			{name:'airline', index:'airline', search: true, searchoptions:{sopt:['cn'], dataUrl:"/afg_c_dropdown/airline",searchhidden: true},
				editable: true,
				editrules: {required: true, edithidden: true},
				editoptions:{
				size: 30, 	
				dataInit: function (elem) {
					var dd_des = "/afg_c_dropdown/airline";
					myAutocomplete(elem, dd_des);
					
					$(elem).on("autocompleteselect", function(event, ui) {
					var airlineName = ui.item.value;
					$.ajax({
					  url: '/afg_c_dropdown/airline_code',
					  method: 'GET',
					  data: { name: airlineName },
					  success: function(response) {
						var json = JSON.parse(response);
						$("input[name='airline_code']").val(json.IATA);
					  }
					});
				  });
					},
				},
			},
			{name:'airline_code', index:'airline_code', editable: true, hidden : true,
			 formoptions: { rowpos: 1, colpos: 2 },
			 editoptions: { size: 10, readonly: "readonly" }, 
				editrules: {
				  required: false,
				  edithidden: true
				}
			},
			{name:'shc',index:'shc', align:"left", width:70,
				hidden: false,		
				editable: true, 
				editrules: {
				  required: false,
				  edithidden: true
				},
				editoptions: { size: 30, dataInit: function (el) { $(el).css('text-transform', 'uppercase'); }}
			},
			{name:'commodity',index:'commodity', align:"left", width:70,
				hidden: false,		
				editable: true, 
				editrules: {
				  required: false,
				  edithidden: true
				},
				editoptions: { size: 30, dataInit: function (el) { $(el).css('text-transform', 'uppercase'); }}
			},
			{name:'origin', index:'origin', align:"left", width : 200, searchoptions:{sopt:['cn']},
			  editable: true, 
			  editoptions:{
				size: 30,  
				dataInit: function(elem) {
				  var dd_ship = "/afg_c_dropdown/dropdown_airport";
				  myAutocomplete(elem, dd_ship);

				  $(elem).on("autocompleteselect", function(event, ui) {
					var originName = ui.item.value;
					$.ajax({
					  url: '/afg_c_dropdown/get_airport_code',
					  method: 'GET',
					  data: { name: originName },
					  success: function(response) {
						var json = JSON.parse(response);
						$("input[name='origin_code']").val(json.iata_code);
					  }
					});
				  });
				}
			  }
			},
			{name:'origin_code', index:'origin_code', editable: true, hidden : true,
			 formoptions: { rowpos: 5, colpos: 2 },
			 editoptions: { size: 10, readonly: "readonly" }, 
				editrules: {
				  required: false,
				  edithidden: true
				}
			},
			{name:'destination',index:'destination', align:"left", width : 200, searchoptions:{sopt:['cn']},
				editable: true,	
				formoptions: { rowpos: 6, colpos: 1 },
				editoptions:{
				size: 30,	
				dataInit: function (elem) {
					var dd_ship = "/afg_c_dropdown/dropdown_airport";
					myAutocomplete(elem, dd_ship);
					
					$(elem).on("autocompleteselect", function(event, ui) {
					var destinationName = ui.item.value;
					$.ajax({
					  url: '/afg_c_dropdown/get_airport_code',
					  method: 'GET',
					  data: { name: destinationName },
					  success: function(response) {
						var json = JSON.parse(response);
						$("input[name='destination_code']").val(json.iata_code);
					  }
					});
				  });
					},
				},
			},
			{name:'destination_code', index:'destination_code', editable: true, hidden : true,
			 formoptions: { rowpos: 6, colpos: 2 },
			 editoptions: { size: 10, readonly: "readonly" }, 
				editrules: {
				  required: false,
				  edithidden: true
				}
			},
			{ 
            name: "minimum", 
            index: "minimum", 
            width:80, 
            align: "right", 
            editable: true, 
            formatter: "currency", 
            formatoptions: { 
              prefix: 'Rp', 
              decimalSeparator: '.', 
              thousandsSeparator: ',', 
              decimalPlaces: 2 
            },
           editrules: {
			  number: false,
			  custom: true,
			  custom_func: function(value, colname) {
				if (value === "" || value === null) return [true, ""];
				var cleanValue = value.replace(/[^\d.-]/g, '').trim();
				if (isNaN(parseFloat(cleanValue))) {
				  return [false, colname + ": Please enter a valid monetary amount or leave it empty."];
				}
				return [true, ""];
			  }
			}
          },
		  	{ 
            name: "dasar", 
            index: "dasar", 
            width: 80, 
            align: "right", 
            editable: true, 
            formatter: "currency", 
            formatoptions: { 
              prefix: 'Rp', 
              decimalSeparator: '.', 
              thousandsSeparator: ',', 
              decimalPlaces: 2 
            },
            editrules: {
			  number: false,
			  custom: true,
			  custom_func: function(value, colname) {
				if (value === "" || value === null) return [true, ""];
				var cleanValue = value.replace(/[^\d.-]/g, '').trim();
				if (isNaN(parseFloat(cleanValue))) {
				  return [false, colname + ": Please enter a valid monetary amount or leave it empty."];
				}
				return [true, ""];
			  }
			}
          },
		  	{ 
            name: "empatlima", 
            index: "empatlima", 
            width: 80, 
            align: "right", 
            editable: true, 
            formatter: "currency", 
            formatoptions: { 
              prefix: 'Rp', 
              decimalSeparator: '.', 
              thousandsSeparator: ',', 
              decimalPlaces: 2 
            },
            editrules: {
			  number: false,
			  custom: true,
			  custom_func: function(value, colname) {
				if (value === "" || value === null) return [true, ""];
				var cleanValue = value.replace(/[^\d.-]/g, '').trim();
				if (isNaN(parseFloat(cleanValue))) {
				  return [false, colname + ": Please enter a valid monetary amount or leave it empty."];
				}
				return [true, ""];
			  }
			}
          },
		  	{ 
            name: "seratus", 
            index: "seratus", 
            width: 80, 
            align: "right", 
            editable: true, 
            formatter: "currency", 
            formatoptions: { 
              prefix: 'Rp', 
              decimalSeparator: '.', 
              thousandsSeparator: ',', 
              decimalPlaces: 2 
            },
            editrules: {
			  number: false,
			  custom: true,
			  custom_func: function(value, colname) {
				if (value === "" || value === null) return [true, ""];
				var cleanValue = value.replace(/[^\d.-]/g, '').trim();
				if (isNaN(parseFloat(cleanValue))) {
				  return [false, colname + ": Please enter a valid monetary amount or leave it empty."];
				}
				return [true, ""];
			  }
			}
          },
		  	{ 
            name: "tigaratus", 
            index: "tigaratus", 
            width: 80, 
            align: "right", 
            editable: true, 
            formatter: "currency", 
            formatoptions: { 
              prefix: 'Rp', 
              decimalSeparator: '.', 
              thousandsSeparator: ',', 
              decimalPlaces: 2 
            },
            editrules: {
			  number: false,
			  custom: true,
			  custom_func: function(value, colname) {
				if (value === "" || value === null) return [true, ""];
				var cleanValue = value.replace(/[^\d.-]/g, '').trim();
				if (isNaN(parseFloat(cleanValue))) {
				  return [false, colname + ": Please enter a valid monetary amount or leave it empty."];
				}
				return [true, ""];
			  }
			}
          },
		  	{ 
            name: "limaratus", 
            index: "limaratus", 
            width: 80, 
            align: "right", 
            editable: true, 
            formatter: "currency", 
            formatoptions: { 
              prefix: 'Rp', 
              decimalSeparator: '.', 
              thousandsSeparator: ',', 
              decimalPlaces: 2 
            },
            editrules: {
			  number: false,
			  custom: true,
			  custom_func: function(value, colname) {
				if (value === "" || value === null) return [true, ""];
				var cleanValue = value.replace(/[^\d.-]/g, '').trim();
				if (isNaN(parseFloat(cleanValue))) {
				  return [false, colname + ": Please enter a valid monetary amount or leave it empty."];
				}
				return [true, ""];
			  }
			}
          },
		  	{ 
            name: "seribu", 
            index: "seribu", 
            width: 80, 
            align: "right", 
            editable: true, 
            formatter: "currency", 
            formatoptions: { 
              prefix: 'Rp', 
              decimalSeparator: '.', 
              thousandsSeparator: ',', 
              decimalPlaces: 2 
            },
            editrules: {
			  number: false,
			  custom: true,
			  custom_func: function(value, colname) {
				if (value === "" || value === null) return [true, ""];
				var cleanValue = value.replace(/[^\d.-]/g, '').trim();
				if (isNaN(parseFloat(cleanValue))) {
				  return [false, colname + ": Please enter a valid monetary amount or leave it empty."];
				}
				return [true, ""];
			  }
			}
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
			{name:'remarks',index:'remarks', align:"left", width:200, editable: true,
				edittype:"textarea",
				editoptions:{
					cols:"20", rows:"6",
					dataInit: function (el) { $(el).css('text-transform', 'uppercase'); },
					},
			},
		], 
		sortname: "airline",
		sortorder:'',
  		multiselect:true,
		loadonce : false,
		cellEdit: false,
		rowNum:20,
		width: '100%',
		height: 'auto',
		rowList:[20,40,60,80,100],
		viewrecords: true,
		rownumbers: true,
		gridview: true,
		modal: true,
		caption: '<?php echo $table_title ?>',
		editurl:'air_freight_feeder_rate/manage_detail',
		cellsubmit:'clientArray',
		pager:'#pager',
		grouping: true,
		groupingView: {
			groupField: ["airline","valid", "shc", "commodity"], // Group by destination and valid
			groupColumnShow: [false, false, false, false], // Hide grouped columns
			groupOrder: ["asc", "asc"], // Sort groups in ascending order
			groupSummary: [false, false, false, false], // Disable group summary
			groupCollapse: false, // Do not collapse groups initially
			groupDataSorted: true, // Ensure grouped data remains sorted
			groupText: [
				'<span class="group-header">{0}</span>',
				'<span class="group-subheader">Valid until {0}</span>',
				'<span class="group-subheaderadd">SHC : {0}</span>',
				'<span class="group-subheaderadd">COMMODITY : {0}</span>'
			]
		},	
		loadComplete: function() {
        var grid = $("#table");
		grid.find("th[aria-labelledby='airline']").hide();
        // Iterate through rows to collect "valid" column data
        var validDataMap = {};
        grid.find("tr.jqgrow").each(function () {
            var rowId = $(this).attr("id");
            var rowData = grid.jqGrid("getRowData", rowId);
            validDataMap[rowData.airline] = rowData.valid; // Map destination to valid date
        });

		$(".jqgroup:contains('COMMODITY')").each(function() {
        // Add column headers only under "Valid" groups
        var columnRow = $("<tr class='custom-column-header'></tr>");
        var columnNames = [
            "","ORIGIN", "DESTINATION", "M", "N", "45KGs", "100KGs", "300KGs", "500Kgs"," 1000Kgs", "REMARK"
        ];

        columnNames.forEach(function(col) {
            columnRow.append("<td style='font-weight:bold; text-align:center;'>" + col + "</td>");
        });

			$(this).after(columnRow);
		});

		// Hide standard column headers
		$(".ui-jqgrid-hdiv").hide();
		$(".group-header, .group-subheader, .group-subheaderadd").each(function() {
        const text = $(this).text().trim();
        if (text === "" || text.includes(":") && text.endsWith(":")) {
            $(this).closest("tr.jqgroup").hide(); // Sembunyikan baris grup
        }
		});
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
		
	}).navGrid('#pager',{ edit: editPermission, add: addPermission, del: delPermission }, 
    { height: "auto", width: "auto", top: 3, left: 250, closeAfterEdit: true, reloadAfterEdit: true, modal: false, 
      editCaption: "Edit Data <?php echo $table_title; ?>",
      afterSubmit: function () {
          $("#grid").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
          return [true];
      }
    }, 
    { height: "auto", width: "auto", top: 3, left: 250, closeAfterAdd: true, reloadAfterAdd: true, modal: false, 
      addCaption: "Insert Data <?php echo $table_title; ?>",
      afterSubmit: function () {
          $("#grid").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
          return [true];
      }
    }, 
    { height: "auto", width: "auto" }, 
    { modal: true, height: "auto", width: "auto", closeAfterSearch: true, multipleSearch: false }, 
    {}
	);

});


function print_data (id){
	$( "#PRINT" ).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
	$( "#PRINT" ).dialog(
	{
		position:'top',
		autoOpen: true,
		resizable: true,
		height: 'auto',
		width: 'auto',
		modal: true,
		title: "OUTPUT",
		buttons : {  },
		close: function()  {$(this).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>')}
	});
	$.ajax
		({
			type: "POST",
			url : "<?php echo base_url('pricelist/selling_rate/print_data')?>/"+id,
			data:  { id : id },
			success: function(data)
			{
				$('#PRINT').html('<iframe src="<?php echo base_url("pricelist/selling_rate/print_data") ?>/'+id+'" width=" 960px" height="700"></iframe>');
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
        });
}
</script>