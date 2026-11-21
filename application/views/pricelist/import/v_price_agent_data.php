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
  		<li><a href="#tab-2">LISTING DATA</a></li>
  		<!--
  		<li><a href="#tab-3">NOMOR URUT</a></li>
	  	-->
	</ul>	
   <div id="tab-1" class="the-tab">
		<div class="tab-border" style="float: left; width: 260px; height: 130px;">
			<table width="100%" style="margin: 10px">
				<tr><td width="80">Ref No</td><td>: <input type="text" id="refNo" disabled="disabled"></td></tr>
				<tr><td>Date</td><td>: <input type="text" style=" " id="dateForm" disabled="disabled"></td></tr>
				<tr><td>Valid</td><td>: <input type="text" style=" " id="validForm" disabled="disabled"></td></tr>
			</table>
		</div>
    	<div class="tab-border" style="float:right; width: 380px; height: 70px;">
			<table width="95%" style="margin: 10px">
				<tr>
					<td>SHIPPING CO. INFORMATION </td>
					<td>: <input type="text" style="float: right; font-weight: bold;" value="<?php echo $code ?>" id="codeForm" disabled="disabled"></td>
				</tr>
				<tr><td colspan="2"><input type="text" style="font-weight: bold; width: 100%" value="<?php echo $name ?>" id="nameForm" disabled="disabled"></td></tr>
			</table>
		</div>		
		<div class="border" style="float:right; width: 380px; height: 70px; margin-top: 15px;">
					<input type="button" value=" NEW " style="width: 60; height: 30" id="btnNew" onclick="new_data()">
					<input type="button" value=" EDIT " style="width: 60; height: 30" id="btnEdit" onclick="edit_data()">
					<input type="button" value=" PREV " style="width: 60; height: 30" id="btnPrev" onclick="get_data('prev')">
					<input type="button" value=" NEXT " style="width: 60; height: 30" id="btnNext" onclick="get_data('next')">
					<input type="button" value=" DELETE " style="width: 60; height: 30" id="btnDelete" onclick="del_data()">
					<input type="button" value="EXIT" style="width: 60; height: 30" id="btnExit">
		</div>
		
		<div style="clear: both"></div>
		<table id="table-satu-agent" ></table>
		<div id="pager-table-satu-agent"></div>
		
	</div>
	
	<div id="tab-2" class="the-tab">


	</div>
</div>

<?php include "v_price_agent_data_script.php" ?>