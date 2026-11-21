<div id="form-invoice"></div>
<div id="out-invoice"></div>
<div id="PRINT-PROCESS"></div>

<input style="width: 55px;" readonly="readonly" id="id_aktif" name="id_aktif" type="hidden">

<div id="form-table-empat">
	<table id="table_empat"></table>
	<div id="pager-empat" style="font-size: 11px"></div>
</div>

<div id="msg-checked" style="font-size: 11px; margin: 10px"></div>

<div style="min-height: 60px">

<div style="float: right; margin-top: 20px; margin-right: 55px;">
	 <input type="button" value=" NEW " id="btnNew" style="width: 70; height: 30" disabled="disabled"> 
	 <input type="button" value=" EDIT " id="btnEdit" style="width: 70; height: 30" disabled="disabled"> 
</div>

<div style="clear: both; height: 10px"></div>

<table>
	<tr>
		<td>
			<input readonly="readonly" disabled="disabled" id="cont_seal_noForm" style="width: 100px" type="hidden">
			<input readonly="readonly" disabled="disabled" id="mother_blForm" style="width: 100px" type="hidden">
			<input readonly="readonly" disabled="disabled" id="eta_destinationForm" style="width: 100px" type="hidden">
		</td>
	</tr>
</table>

</div>

<div id="form-inv-to-shipper" class="main-inv-to-shipper">
	<div class="form_bck" style="float: left; width: 395px; margin-left: 50px;">
		<table summary="" style="margin-bottom: 5px;" width="100%">
			<tr>
				<td width="110px" style="border: none;">Job No</td><td style="border: none;">
					<input type="text" disabled="disabled" id="kode_refForm" class="text-input" style="width: 270px; margin-bottom: 5px;">
				</td>
			</tr>
			<tr>
				<td style="border: none;">Stuffing Date </td><td style="border: none;">
					<input type="text" disabled="disabled" id="stuffing_dateForm" class="text-input" style="margin-bottom: 5px;">&nbsp;&nbsp;&nbsp;
				</td>
			</tr>
			<tr>
				<td style="border: none;">ETD JKT </td><td style="border: none;">
					<input type="text" disabled="disabled" id="etd_jktForm" class="text-input" style="margin-bottom: 5px;">
				</td>
			</tr>
			<tr>
				<td style="border: none;">Vessel </td><td style="border: none;">
					<input type="text" disabled="disabled" id="vesselForm" class="text-input" style="width: 270px; margin-bottom: 5px;">
				</td>
			</tr>
		</table>
	</div>

	<div class="form_bck" style="float: left; width: 375px; margin-left: 30px;">
		<table summary="" style="margin-bottom: 5px;" width="100%">
			<tr>
				<td width="160">Port of Loading</td><td>: 
					<input type="text" disabled="disabled" id="port_loadingForm" class="text-input" style="margin-bottom: 5px;">
				</td>
			</tr>
			<tr>
				<td>Port of Discharge</td><td>: 
					<input type="text" disabled="disabled" id="port_dischargeForm" class="text-input" style="margin-bottom: 5px;">&nbsp;&nbsp;&nbsp;
				</td>
			</tr>
			<tr>
				<td>Destination</td><td>: 
					<input type="text" disabled="disabled" id="destinationForm" class="text-input" style="margin-bottom: 5px;">
				</td>
			</tr>
			<tr>
				<td>Voyage</td><td>: 
					<input type="text" disabled="disabled" id="voyageForm" class="text-input" style="margin-bottom: 5px;">
				</td>
			</tr>
		</table>
		
	</div>
	
	<div style="clear: both; height: 20px"></div>

	<div style="float: left; width: 180px; margin-left: 50px;">
		<table id="table_dua"></table>
		<div id="pager-2"></div>
	</div>
	
	<div style="float: left; width: 530px; margin-right: 100px;">
		<table id="table_tiga"></table>
		<div id="pager-3"></div>
	</div>
</div>

<?php include "v_finance_proses_script.php"; 
