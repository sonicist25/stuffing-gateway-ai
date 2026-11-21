<?php // echo ! empty($h1_title) ? '<h1>' . $h1_title . '</h1>': ''; ?>
<style type="text/css">
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
    display: block;
    text-align: center;
    font-weight: bold;
    padding: 5px;
    background: linear-gradient(to top, #FFF100, #FFFFFF);
    border-radius: 5px;
}

.custom-column-header {
    font-weight: bold;
    background: #f4f4f4;
    text-align: center;
}
</style>

<div id="form-main-page" class="main-page">
	<center>
		<table id="table" ></table>
		<div id="pager"></div>
	</center>
</div>

<div id="Form-Destination"></div>

<div id="PRINT"></div>

<script type="text/javascript">
$(document).ready(function () 
{
	var grid = $("#table"), i;
	var user_id = <?php echo $this->session->userdata('user_id'); ?>;
	var editPermission = !(user_id !== 0 && user_id !== 23 &&  user_id !== 211 &&  user_id !== 565);
	var addPermission = !(user_id !== 0 && user_id !== 23 && user_id !== 211 &&  user_id !== 565);
	var delPermission = !(user_id !== 0 && user_id !== 23 && user_id !== 211 &&  user_id !== 565);
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
			{name:'remarks',index:'remarks', align:"left", width:70, editable: true,
				editoptions: { size: 60, dataInit: function (el) { $(el).css('text-transform', 'uppercase'); }}
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
		caption: '<?php echo $table_title ?>',
		editurl:'export_rate/manage_detail',
		cellsubmit:'clientArray',
		pager:'#pager',
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
        var grid = $("#table");
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
		$(".ui-jqgrid-hdiv").hide();
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

function show_table_selling_rate(N){
	var code = $('#table').jqGrid('getCell', N,'code');
	var name = $('#table').jqGrid('getCell', N,'name');
	$( "#Form-Destination" ).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
	$( "#Form-Destination" ).dialog(
	{
		resizable: false,
		height: 430,
		width: 735,
		modal: true,
		title: "COST FEEDER - EXPORT TO : "+name,
		close: function()  {$(this).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>')}
	});
	
	$.ajax({
		type: "POST", 
		url : "<?php echo site_url('pricelist/export_rate/open_form')?>",
		data: { id : N, code:code, name : name },
		success:function(XMLHttpRequest)
		{
			$( "#Form-Destination" ).html(XMLHttpRequest)
		}
	})
}

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