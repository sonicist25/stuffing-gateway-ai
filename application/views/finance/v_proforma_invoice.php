<div id="form-table-empat">
	<table id="table_empat"></table>
	<div id="pager-empat" style="font-size: 11px"></div>
</div>

	<div id="form-inv-to-shipper" class="main-inv-to-shipper" style="padding-top: 50px; padding-left: 50px;">
		<div class="form_bck" style="float: left; width: 395px">
			<div style="float: left; width: 80px">Job No</div> 
			<div style="float: left; width: 10px">:</div> 
			<div style="float: left; width: 300px">
				<input type="text" name="job_no" value="" disabled="disabled" id="NOMER_JOB" class="text-input" style="width: 100%">
			</div>
			<div style="clear: both; height: 2px"></div>			
			<div style="float: left; width: 80px">Date</div>
			<div style="float: left; width: 10px">:</div> 
			<div style="float: left; width: 140px">
				<input type="text" name="date" style="width: 90px;" disabled="disabled" id="TANGGAL_JOB" class="text-input">&nbsp;&nbsp;&nbsp;
			</div>
			<div style="float: left; width: 50px">Kurs</div>
			<div style="float: left; width: 15px">:</div>
			<div style="float: left; width: 80px">
					<input type="text" style="width: 95px;" disabled="disabled" id="KURS" name="KURS" class="text-input"></td></tr>
			</div>			
			<div style="clear: both; height: 2px"></div>	
			<div style="float: left; width: 80px">Sales</div>
			<div style="float: left; width: 10px">:</div>
			<div style="float: left; width: 300px"> 
					<input type="text" name="salesman" disabled="disabled" id="SALESMAN" class="text-input" style="width: 100%">
			</div>
			<div style="clear: both; height: 13px"></div>	
			<table id="table_satu"></table>
			<div id="page-1"></div>
			<div style="clear: both; height: 2px"></div>
		</div>		
		<div style="float: right; margin-right: 65px">
			<?= form_submit(array('value'=>'NEW','id'=>'btnNew','disabled'=>'disabled','style'=>'width: 80px')) ?>
			<?= form_submit(array('value'=>'EDIT','id'=>'btnEdit','disabled'=>'disabled','style'=>'width: 80px')) ?>
		</div>			
		<div class="form_bck" style="float: left; margin-left: 20px; width: 375px">
			<div style="float: left; width: 160px">Costumer Information</div>
			<div style="float: left; width: 10px">:</div>
			<div style="float: left; width: 200px"> 
				<input type="text" disabled="disabled" id="KOSTUMER" name="costumer_id" class="text-input" style="width: 100%">
			</div>
			<div style="clear: both; height: 2px"></div>
			<div style="float: left; width: 370px">
				<input type="text"  style="width: 100%" disabled="disabled" id="KET_KOSTUMER" class="text-input">
			</div>
			<div style="clear: both; height: 2px"></div>
			<div style="float: left; width: 370px">
				<textarea rows="9" style="min-height: 110px; min-width: 100%; max-width: 100%; border: 1px solid #999" disabled="disabled" id="ALAMAT_KOSTUMER" class="text-input"></textarea>
			</div>
		</div>		
	<div style="clear: both; height: 10px"></div>
	<table id="table_dua"></table>
	<div id="pager-2"></div>
</div>
<div id="form-invoice"></div>
<div id="out-invoice"></div>
<?php include "v_proforma_invoice_script.php"; ?>
