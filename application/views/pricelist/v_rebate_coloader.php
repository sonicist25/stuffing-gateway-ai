<style type="text/css">
#FORM-INTENSIVE {
	float: left;
	margin: 80px 0 0 200px;
	height : 405px;
	width: 570px;
	border: 1px solid #999; 
	padding-top: 10px; 
	padding-left: 10px;	
	background-color: #eeeeee;
	border-radius: 4px 4px 4px 4px;
	-webkit-box-shadow: 0 8px 6px -6px #333;
	-moz-box-shadow: 0 8px 6px -6px #333;
	box-shadow: 0 8px 6px -6px #333;
}
</style>
<div id="FORM-INTENSIVE"></div>
<div id="FORM-BROWSE">
	<table id="table-browse"></table>
	<div id="page-browse" style="font-size: 11px;"></div>
</div>

<div id="delete-confirm"></div>

<script type="text/javascript">
$(document).ready(function () 
{
	$.ajax({
		type: "POST", 
		url : "<?php echo base_url('pricelist/rebate_coloader/open_form')?>",
		data: { },
		success:function(XMLHttpRequest)
		{
			$( "#FORM-INTENSIVE" ).html(XMLHttpRequest)
		}
	});
});
</script>