<style type="text/css">
#btnNewBooking,#btnEditBooking
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
#btnNewBooking:hover,#btnEditBooking:hover {
	background:linear-gradient(to bottom, #378de5 5%, #79bbff 100%);
	background-color:#378de5;
}
</style>

<?php // echo ! empty($h1_title) ? '<h1>' . $h1_title . '</h1>': ''; ?>
		<div id="Form-Pricing-Quote"></div> 
		<div id="FORM_DISPLAY_BOOKING"></div>
		<table id="form-harga"></table>
		<div id="pager"></div>
		<div style="float:right; margin-bottom: 10px; font-size: 11px;">
			<input type="hidden" id="btnShowFinanceQuote" value=" INPUT PRICE " style="width: 60; height: 30">
			<input type="hidden" value=" EXIT " style="width: 60; height: 30" id="btnExit" onclick="form_close()">			
		</div>	
<script type="text/javascript">

function siapinBahan(bookingCode){
	$('#btnShowFinanceQuote').attr({'onClick':'showFinanceDialogQuote("'+bookingCode+'")'}).css({'cursor':'pointer','background-color':'#dadada','color':'#000000'});
}

function showFinanceDialogQuote(bookingCode)
{
	
	// $("#Form-Trucking").css('background-image', 'url("public/images/bg-form.png")');
	$("#Form-Pricing-Quote").html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
	$("#Form-Pricing-Quote").dialog(
	{
		autoOpen: true,
		resizable: false,
		height: 750,
		width: 1280,
		modal: true,
		title: "QUOTE PRICING",
		close: function() 
		{	
			$(this).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
		}
	});
	
	$.ajax({
		type: "POST", 
		url : window.location.origin+'/pricelist/afg_c_booking/open_quote_pricing',
		data : {bookingCode : bookingCode},
		success:function(XMLHttpRequest)
		{
			$('#Form-Pricing-Quote').html(XMLHttpRequest)
		}
	})
}

function add_new_booking() {
	load_file_head('css',tmppub+'/css/add_new_booking.css');
	
	
		$("#FORM_DISPLAY_BOOKING").html('<b style="font-size: 11px; font-style:italic; text-align:center">LOADING...</b>');
		$("#FORM_DISPLAY_BOOKING").dialog({
				autoOpen: true,
				resizable: false,
				height: 700,
				width: 890,
				modal: true,
				title :"SHIPING INTRUCTIONS",
				close: function(){	
					$(this).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
					$("#form_display_booking").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
				}
		});
		
		$.ajax({
				type: "POST", 
				url : window.location.origin+'/pricelist/afg_c_booking/open_booking_form',
				data: {},
				success:function(XMLHttpRequest)
				{
					$("#FORM_DISPLAY_BOOKING").css('background-color','#fff');
					$("#FORM_DISPLAY_BOOKING").html(XMLHttpRequest)
				},	
				error:function(XMLHttpRequest)
				{
					$('#FORM_DISPLAY_BOOKING').html(XMLHttpRequest.responseText)
				}	
		});
	//}
}

$(document).ready(function () 
{
	var grid = $("#form-harga"), i;	
	$("#form-harga").jqGrid(		
	{ 
		url:"<?php echo base_url('pricelist/afg_c_booking/getDataQuotation')?>",
		mtype : "post",
		datatype: "json",
		colNames:
		[
			'No. Booking','Type','Mode','Movement','Incoterm','Shipper Name', 'POL','Destination', 'Created_at',
		],
    	colModel:
		[
			{name:'booking_code',index:'booking_code', align:"left", 
				width:40, 
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
			{name:'type_of_booking',index:'type_of_booking', align:"left",
			 	width:15, 
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
			{name:'mode',index:'mode', 
				width:20, 
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
			{name:'movement_type',index:'movement_type', 
				width:20, 
				editable: true, 
				edittype:"text",
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
			{name:'incoterm',index:'incoterm', 
				width:20, 
				editable: true, 
				edittype:"text",
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
			{name:'shipper_id',index:'shipper_id', 
				width:20, 
				editable: true, 
				edittype:"text",
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
			{name:'origin_city',index:'origin_city', 
				width:20, 
				editable: true, 
				edittype:"text",
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
			{name:'destination_city',index:'destination_city', 
				width:20, 
				editable: true, 
				edittype:"text",
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
			{name:'created_at',index:'created_at', align:"left",
			 	width:30, 
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
		], 
		sortname: "created_at",
		sortorder: "desc",
  		loadonce:false,
  		viewrecords: true,
         multiselect:true, 
		modal: true,
		rownumbers: true,
		rowNum: 16,
		//top: 20,
		width: 960,
		height: 470,
		rowList:[15,30,45],
		gridview: true,
		caption:"Request Quotation Table",
		editurl:ref+'/pricelist/afg_c_booking/manage_data',
		cellsubmit:'clientArray',
		pager:'#pager',				
		loadComplete: function() {
			$("#cb_"+grid[0].id).hide();
			$(".ui-jqgrid-titlebar-close",$("#form-harga")[0].grid.cDiv).hide();
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
			console.log(selected[rowid]);
			var kode_booking = grid.jqGrid('getCell',rowid,'booking_code');
			siapinBahan(kode_booking);	
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
		
		ondblClickRow: function(rowid){	
			var kode_booking = grid.jqGrid('getCell',rowid,'booking_code');
			showFinanceDialogQuote(kode_booking);
		},
		
	}).navGrid('#pager',{ edit: false, add: true, del: false, refresh: false},
			{height: "auto", width: "auto", 
				closeAfterEdit: true, 
				reloadAfterEdit : true, 
				modal: true, 
				editCaption: "Edit Data",
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
   			addCaption: "Insert Data",
   			afterSubmit: function () {
    				$('#table').jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
    				return [true];
				}
   		},
     		{height: "auto", width: "auto"},
      	{modal: true, height: "auto", width: "auto", closeAfterSearch : true, multipleSearch: false,},
     		{ }
    );
});

</script>