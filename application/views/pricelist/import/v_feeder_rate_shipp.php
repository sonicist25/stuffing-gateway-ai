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
			<table width="100%" style="margin: 10px">
				<tr><td width="80">Ref No</td><td>: <input type="text" id="refNo" disabled="disabled"></td></tr>
				<tr><td>Date</td><td>: <input type="text" style=" " id="dateForm" disabled="disabled"></td></tr>
			</table>
		</div>

		<div class="tab-border" style="float:right; width: 370px; height: 70px;">
			<table width="95%" style="margin: 10px">
				<tr>
					<td>SHIPPING CO. INFORMATION </td>
					<td>: <input type="text" style="float: right; font-weight: bold;" value="<?php echo $code ?>" id="codeForm" disabled="disabled"></td>
				</tr>
				<tr><td colspan="2"><input type="text" style="font-weight: bold; width: 100%" value="<?php echo $name ?>" id="nameForm" disabled="disabled"></td></tr>
			</table>
		</div>
		
		<div class="tab-border" style="float:right; width: 370px; height: 70px; margin-top: 5px">
			<table width="95%" style="margin: 10px">
				<tr>
					<td width="120">PORT OF LADDING</td>
					<td>
					<?php 
               	$countries = $this->afg_m_cities->getListCitiesDrop()->result();
						$data['option_countries']['']=' ';
						foreach ($countries as $row)
						{	
							$data['option_countries'][$row->city_name]=$row->city_name;
						}
						echo  form_dropdown('dropdownPortLoading',$data['option_countries'],'','id="dropdownPortLoading" disabled="disabled" style="width: 210px;"');
					?>
					</td>
				</tr>
				<tr>
					<td>TRANSHIPMENT PORT</td>
					<td>
					<?php
					echo  form_dropdown('dropdownTransipmentPort',$data['option_countries'],'','id="dropdownTransipmentPort" disabled="disabled" style="width: 210px;"');
					?>
					</td>
				</tr>
			</table>
		</div>
		
		<div style="clear: both; height: 5px"></div>
		
		<div >
			<table id="table-satu"></table>
			<div id="page-table-satu"></div>
		</div>
		
		<div id="topbutton-2" style="float: right; margin: 15px 0 0 20px;  font-size: 11px">
    		<input type="button" value="EXIT" style="width: 60; height: 30; margin-left: 20" id="btnExit" onclick="exit_data()">
    	</div>
    	
    	<div id="topbutton-2" style="float: right; margin: 15px 0 0 0; font-size: 11px">
    		<input type="button" value=" NEW " style="width: 60; height: 30" id="btnNew" onclick="new_data()">
			<input type="button" value=" EDIT " style="width: 60; height: 30" id="btnEdit" onclick="edit_data()">
			<input type="button" value=" PREV " style="width: 60; height: 30" id="btnPrev" onclick="prev_data()">
			<input type="button" value=" NEXT " style="width: 60; height: 30" id="btnNext" onclick="next_data()">
			<input type="button" value=" SAVE " style="width: 60; height: 30" id="btnSave" onclick="save_data()">
			<input type="button" value="DELETE" style="width: 60; height: 30" id="btnDelete">
			<input type="button" value="CETAK" style="width: 60; height: 30" id="btnCetak">
    	</div>
    	
</div>

<?php include "v_feeder_rate_script.php" ?>