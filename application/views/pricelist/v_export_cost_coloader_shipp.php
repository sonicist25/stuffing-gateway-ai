<style type="text/css">
.tab-border {
	border-top: 1px solid #999; 
	border-left: 1px solid #999; 
	border-bottom: 3px solid #999;
	border-right: 3px solid #999;
	background-color: #eee;
}
</style>

<div style="font-size: 11px">
		<div class="tab-border" style="float: left; width: 320px; height: 150px;">
			<table style="margin: 10px; width: 300px">
				<tr>
					<td style="width: 30px">Ref No</td>
					<td style="width: 10px">:</td>
					<td style="width: 70px"><input type="text" id="ref_no" disabled="disabled" style="width: 97%"></td>
				</tr>
				<tr>
					<td style="width: 30px; padding-top: 5px">DATE</td>
					<td style="width: 10px; padding-top: 5px">:</td>
					<td style="width: 70px; padding-top: 5px"><input type="text" id="date" disabled="disabled" style="width: 97%"></td>
				</tr>
				<tr>
					<td style="width: 30px; padding-top: 5px"></td>
					<td style="width: 10px; padding-top: 5px"></td>
					<td style="width: 70px; padding-top: 5px">
						<input type="hidden" id="id" disabled="disabled" style="width: 30px">
						<input type="hidden" id="oper" disabled="disabled" style="width: 30px">
					</td>
				</tr>
			</table>
		</div>

		<div class="tab-border" style="float:right; width: 370px; height: 70px;">
			<table style="margin: 10px; width: 360px">
				<tr>
					<td>SHIPPING CO. INFORMATION </td>
					<td>:</td>
					<td><input type="text" style="float: right; font-weight: bold; width: 150px" value="<?php echo $code ?>" id="shipping_co" disabled="disabled"></td>
				</tr>
				<tr><td colspan="3" style="padding-top: 5px"><input type="text" style="font-weight: bold; width: 99%" value="<?php echo $name ?>" id="nameForm" disabled="disabled"></td></tr>
			</table>
		</div>
		
		<div class="tab-border" style="float:right; width: 370px; height: 70px; margin-top: 5px">
			<table style="margin: 10px">
				<tr>
					<td>
					<input type="button" value=" NEW " style="width: 60; height: 30" id="btnNew" onclick="new_data()">
					<input type="button" value=" EDIT " style="width: 60; height: 30" id="btnEdit" onclick="edit_data()">
					<input type="button" value=" PREV " style="width: 60; height: 30" id="btnPrev" onclick="get_data('prev')">
					<input type="button" value=" NEXT " style="width: 60; height: 30" id="btnNext" onclick="get_data('next')">
					<input type="button" value=" SAVE " style="width: 60; height: 30" id="btnSave" onclick="save_data()">
					<input type="button" value="DELETE" style="width: 60; height: 30" id="btnDelete">
					<input type="button" value="CETAK" style="width: 60; height: 30" id="btnCetak">	
					</td>
				</tr>
			</table>
		</div>
		
		<div style="clear: both; height: 5px"></div>
		
		<div >
			<table id="table-satu"></table>
			<div id="page-satu"></div>
		</div>
		<div style="clear: both; height: 10px"></div>
</div>

<?php  include "v_export_cost_coloader_script.php" ?>