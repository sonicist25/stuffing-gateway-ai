<div id="form-main-page" class="main-page">
<input type="button" id="button-print" style="width: 100px; height: 30px; float: right;" value=" PRINT " onclick="print_quotation()">
</div>
<div id="Form-Destination"></div>
<div id="PRINT"></div>
<script type = "text/javascript">
function print_quotation()
{		
		$( "#PRINT" ).css('background-color', '#eee');
		$( "#PRINT" ).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
		$("#PRINT" ).dialog({
			position: "top",
			autoOpen: true,
			resizable: true,
			height: 'auto',
			width: 980,
			modal: true,
			title: "SELLING PRICE TO ",
			close: function() {	}
		});
		
		$('#PRINT').html('<object id="form-data" data="selling_rate/print_data?<?=$_POST['url']?>" width=" 950px" height="600" type="application/pdf" \>');
	
	
	}
</script>
