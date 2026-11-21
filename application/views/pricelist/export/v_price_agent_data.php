<style type="text/css">
.tab-border {
	border-top: 1px solid #999; 
	border-left: 1px solid #999; 
	border-bottom: 3px solid #999;
	border-right: 3px solid #999;
	background-color: #eee;
}
</style>
<div id="delete-confirm"></div>
<div id="tabs-lading">
	<ul id="tabs-menu">
		<li><a href="#tab-1" shape="">COST PRICE AGENT</a></li>
  		<li><a href="#tab-2" onclick="listing_data()">LISTING DATA</a></li>
	</ul>	
   <div id="tab-1" class="the-tab" style="min-width: 520px; min-height: 440px">
		<div class="tab-border" style="float: left; width: 280px; height: 130px;">
			<table width="100%" style="margin: 10px">
				<tr>
					<td style="width: 80px; margin-top: 5px">Ref No</td>
					<td style="width: 10px; margin-top: 5px">:</td> 
					<td style="width: 80px; margin-top: 5px"><input type="text" id="ref_no" disabled="disabled"></td>
				</tr>
				<tr>
					<td style="width: 80px; padding-top: 5px">Date</td>
					<td style="width: 10px; padding-top: 5px">:</td>
					<td style="width: 80px; padding-top: 5px"><input type="text" id="date" disabled="disabled"></td>
				</tr>
				<tr>
					<td style="width: 80px; padding-top: 5px">Valid</td>
					<td style="width: 10px; padding-top: 5px">:</td>
					<td style="width: 80px; padding-top: 5px"><input type="text" id="valid" disabled="disabled"></td>
				</tr>
				<tr>					
					<td style="width: 80px; padding-top: 5px"><input type="hidden" style="width: 70; height: 30" id="oper"></td>
					<td style="width: 10px; padding-top: 5px"></td>
					<td style="width: 80px; padding-top: 5px"><input type="hidden" style="width: 70; height: 30" id="id"></td>		
				</tr>
			</table>
		</div>
    	<div class="tab-border" style="float:right; width: 380px; height: 70px;">
			<table width="95%" style="margin: 10px">
				<tr>
					<td>AGENT INFORMATION </td>
					<td>:</td>
					<td><input type="text" style="float: right; font-weight: bold;" value="<?php echo $code ?>" id="agent" disabled="disabled"></td>
				</tr>
				<tr><td colspan="3"><input type="text" style="font-weight: bold; width: 98%; margin-top: 5px" value="<?php echo $name ?>" id="name" disabled="disabled"></td></tr>
			</table>
		</div>		
		<div class="border" style="float:right; width: 390px; height: 60px; margin-top: 15px; margin-left: 10px;">
		
					<input type="button" value=" NEW " style="margin-left: 10px; width: 65; height: 30" id="btnNew" onclick="new_data()">
					<input type="button" value=" EDIT " style="width: 65; height: 30" id="btnEdit" onclick="edit_data()">
					<input type="button" value=" PREV " style="width: 65; height: 30" id="btnPrev" onclick="get_data('prev')">
					<input type="button" value=" NEXT " style="width: 65; height: 30" id="btnNext" onclick="get_data('next')">
					<input type="button" value=" DELETE " style="width: 65; height: 30" id="btnDelete" onclick="del_data()">
					
					<input type="button" value="EXIT" style="width: 70; height: 30" id="btnExit">		
						
		</div>
		
		<div style="clear: both"></div>
		<table id="table-satu-agent" ></table>
		<div id="pager-table-satu-agent"></div>
		
	</div>
	
	<div id="tab-2" class="the-tab"style="min-width: 520px; min-height: 440px">
		<table id="table-urut"></table>
		<div id="page-urut"></div>

	</div>
</div>

<?php include "v_price_agent_data_script.php" ?>