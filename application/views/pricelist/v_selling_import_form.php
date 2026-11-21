<style type="text/css">
input {
	margin-top: 5px;
}
td {
	vertical-align: middle;
}
.tableft-1 {
	border-top: 3px solid #999; 
	border-left: 3px solid #999; 
	border-bottom: 1px solid #999;
	border-right: 1px solid #999;
	height: 100px;
}
.tableft-2 {
	float: left;
	width: 260px;
	border-top: 3px solid #999; 
	border-left: 3px solid #999; 
	border-bottom: 1px solid #999;
	border-right: 1px solid #999;
	margin: 10px 0 2px 2px;
	padding: 10px;
	height: 40px;
	vertical-align: middle;
}
.tableft-3 {
	float: left;
	width: 348px;
	border-top: 3px solid #999; 
	border-left: 3px solid #999; 
	border-bottom: 1px solid #999;
	border-right: 1px solid #999;
	margin: 0 2px 2px 0;
	padding: 10px;
	height: 80px;
	vertical-align: middle;
}
.tabright {
	float: right;
	width: 255px;
	border-top: 3px solid #999; 
	border-left: 3px solid #999; 
	border-bottom: 1px solid #999;
	border-right: 1px solid #999;
	margin: 10px 0 10px 0;
	padding: 0 10px 0 10px;
	height: 170px;
	vertical-align: middle;
}
#content-msg{
	position: absolute;
	top: 3px;
	left: 20px;
	height: 20px;
	padding: 5px 20px 0 20px;
	color: #fff; 
	background-color: #f0a;
	text-align: center;
	vertical-align: middle;
	font-size:11px; 
	font-style: italic;
   border: 1px solid #999999;
   font-weight: normal;
   -moz-box-shadow: 0 2px 3px rgba(0, 0, 0, 0.3);
	-webkit-box-shadow: 0 2px 3px rgba(0, 0, 0, 0.3);
	box-shadow: 0 2px 3px rgba(0, 0, 0, 0.3);
	border-radius: 6px;
 }
</style>
<div id="delete-form"></div>
<div id="msg-import"></div>
<div id="message-selling"></div>
<div id="tabs-lading">
	<ul id="tabs-menu">
		<li><a href="#tab-1" shape="">INCOME</a></li>
  		<li><a href="#tab-2">LISTING DATA</a></li>
	</ul>	
   <div id="tab-1" class="the-tab">  
   <div style="float:left; font-size: 11px; margin: 0 0 10px 0">
			<input type="button" value=" NEW " style="width: 60; height: 30" id="btnNew" onclick="new_data()">
			<input type="button" value=" EDIT " style="width: 60; height: 30" id="btnEdit" onclick="edit_data()">
			<input type="button" value=" PREV " style="width: 60; height: 30" id="btnPrev" onclick="get_data_form('prev')">			
			<input type="button" value=" NEXT " style="width: 60; height: 30" id="btnNext" onclick="get_data_form('next')">
			<input type="button" value=" SAVE " style="width: 60; height: 30" id="btnSave" onclick="save_data()">
			<input type="button" value="DELETE" style="width: 60; height: 30" id="btnDelete">
			
			<input type="hidden" id="consigneeImportId" disabled="disabled" value="<?php echo $_POST['consignee_id'] ?>" style="width: 25px">
			<input type="hidden" id="idForm" disabled="disabled" style="width: 25px">
			<input type="hidden" id="idFirst" disabled="disabled" style="width: 25px">
			<input type="hidden" id="idLast" disabled="disabled" style="width: 25px">
			<input type="hidden" id="operForm" disabled="disabled" style="width: 25px">
			<input type="hidden" id="LastRefNo" disabled="disabled" style="width: 25px">
			
			
		</div>
		<div style="clear: both"></div>		
		<div class="tableft-1" style="padding-top: 15px; padding-left: 15px">
		<table style="width: 100%">
			<tr>
				<td style="width: 50px; vertcal-align: midlle">REF NO.</td>
				<td style="width: 50px"><input type="text" id="refNo" disabled="disabled"></td>
				<td style="width: 50px"></td>
				<td>SALES</td>
				<?php 
					$region = $this->session->userdata('region_id');
					$this->dbx->join('mstadmin','mstadmin.user_id=refsales.user_id','left');
		                        $this->dbx->order_by("mstadmin.fullname","asc");
		                        $sales = $this->dbx->get_where('refsales',array('mstadmin.region_id'=>$this->session->userdata('region_id')));
	                                $data['sales']['']=' ';
					foreach ($sales->result() as $row)
					{	
						$data['sales'][$row->user_id] = strtoupper($row->fullname);
					}
					echo "<td>".form_dropdown('sales', $data['sales'], '', 'id="salesImport" name="salesImport" style="width: 250px"')."</td>";
           		?>
			</tr>
			<tr>
				<td>DATE</td>
				<td><?php echo form_input(array("id"=>"date","name"=>"date","style"=>"","disabled"=>"disabled")) ?></td>
				<td></td>
				<td>CONSIGNEE CODE</td>
				<td><input type="text" id="consigneeImportCode" style="width: 250px" disabled="disabled" value="<?php echo $_POST['consignee_code'] ?>"></td>
			</tr>
			<tr>
				<td>VALID</td>
				<td><?php echo form_input(array("id"=>"valid","name"=>"valid","style"=>"","disabled"=>"disabled")) ?></td>
				<td></td>
				<td>CONSIGNEE NAME</td>
				<td><input type="text" id="consigneeImportName" style="width: 250px" disabled="disabled" value="<?php echo $_POST['consignee_name'] ?>"></td>
			</tr>
		</table>
   	</div>	
    	<div style="clear: both; height: 10px;"></div>
    	<div >
    		<table id="table-detail"></table>
    		<div id="page-detail"></div>
    	</div>
	</div>
	<div id="tab-2" class="the-tab">
		<div class="tableft-lading">
			<table id="table-listing"></table>
			<div id="page-listing"></div>
		</div>
	</div>
</div>
<?php include "v_selling_import_script.php" ?>
