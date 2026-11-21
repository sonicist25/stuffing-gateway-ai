<input  id="id_aktif" disabled="disabled" style="width: 50" type="hidden">

<input  id="operForm" disabled="disabled" type="hidden">
<input  id="tmpJobNoForm" disabled="disabled" type="hidden">

<div id="msg-cn"></div>
<div id="out_cn"></div>

<div id="form-table-satu">
		<center>
		<table id="table_satu"></table>
		<div id="pager-satu" style="font-size: 11px"></div>
		</center>
		<div style="height: 15px"></div>
</div>

<style type="text/css">
.text-input {
	text-transform: uppercase;
}
</style>
<div style="min-height: 60px">
<div id="topbutton-" style="float: right; margin-top: 20px; margin-right: 80px; margin-bottom: 10px;">
	 <input type="button" value=" NEW " id="btnNew" style="width: 70; height: 30" onclick="new_cn()"> 
	 <input type="button" value=" EDIT " id="btnEdit" style="width: 70; height: 30" onclick="edit_cn()"> 
	 <input type="button" value=" PREV " id="btnPrev" style="width: 70; height: 30" onclick="get_content('prev')">
	 <input type="button" value=" NEXT " id="btnNext" style="width: 70; height: 30" onclick="get_content('next')">
	 <input type="button" value=" PRINT " id="btnPrint" style="width: 70; height: 30" onclick="print_cn()">
	 <input type="button" value="BROWSE" id="btnBrowse" style="width: 90; height: 30" onclick="browse_cn()">
	 <input type="button" value="REFRESH" id="btnRefresh" style="width: 90; height: 30" onclick="refresh_cn()">
</div>
</div>
<div id="form-inv-to-shipper" class="main-inv-to-shipper" style="height: 500">
	<div id="left-invoice" style="background-color : #ddf; float: left; width: 360px; height: 150px; padding: 20px; margin-left: 70px; margin-top: 10px" class="ui-corner-all ui-widget">
		<table>
			<tr>
				<td width="120" height="30">Job No</td>
				<td style="border: none;" id="dropdownJob"></td>
			</tr>
			<tr>
				<td height="30">Group </td>
				<td style="border: none;">: 
					<input type="text" style="width: 120px;" disabled="disabled" id="groupFormSE" class="text-input">
				</td>
			<tr>
				<td height="30">Credit Note </td>
				<td style="border: none;">: 
					<input type="text" disabled="disabled" id="cnForm" class="text-input" style="width: 200px">
				</td>
			</tr>
			<tr>
				<td height="30">Tanggal </td>
				<td style="border: none;">: 
					<input type="text" disabled="disabled" id="tglForm" class="text-input" style="width: 200px">
				</td>
			</tr>
		</table>
	</div>

	<div id="right-invoice" style="background-color : #ddf; float: right; width: 360px; height: 150px; padding: 20px; margin-right: 70px;  margin-top: 10px" class="ui-corner-all ui-widget">
		<table>
			<tr>
				<td style="border: none;" width="150">Kepada</td>
				<td style="border: none;">: <input type="text" style="float: right;margin-bottom: 10px" disabled="disabled" id="shipperForm" class="text-input">
				</td>
			</tr>
			<tr>
				<td style="border: none;" colspan="2">
					<input type="text"  style=" float: right; width: 355px;margin-bottom: 10px" disabled="disabled" id="shipperName" class="text-input">
				</td>
			</tr>
			<tr>
				<td style="border: none;" colspan="2">
					<textarea rows="4" style=" float: right; min-width: 355px; max-width: 355px; min-height: 50px; max-height: 60px; margin-bottom: 10px" disabled="disabled" id="shipperAddress" class="text-input"></textarea>
				</td>
			</tr>
			<tr>
				<td style="border: none;">SE Exportir</td>
				<td style="border: none;">: <input type="text" style="float: right;" disabled="disabled" id="seForm" class="text-input">
				</td>
			</tr>
	</table>
	</div>
	
	<div style="clear: both; height: 30px"></div>
	
	<div>
		<center>
		<table id="table_dua"></table>
		<div id="pager-dua"></div>
		</center>
	</div>
	
</div>

<div id="form-invoice"></div>
<div id="out-invoice"></div>

<?php include "v_cn_to_consignee_script.php"; ?>
