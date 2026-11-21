<div id="main-page" style="font-size: 11px; width: 750px;">
<style type="text/css">
.attch {
		color: #99f;
		font-size: 10px;
		text-decoration: none;
		font-style: italic;
	}
.attch :hover {
		color: #F00;
		font-size: 10px;
		text-decoration: none;
		font-style: italic;
	}
.detail {
	border-radius: 4px;
	border: 1px solid #999;
	width: 200px;
	padding-left: 5px;
}
.detail-dua {
	border-radius: 2px;
	border: 1px solid #999;
	width: 110px;
	height: 20px;
	padding-right: 5px;
	text-align: right;
	text-transform: uppercase;
}
textarea {
	margin-bottom: 5px;
	text-transform: uppercase;
}
.shipment {
	padding-left: 10px;
}
#loading-save {
	position: absolute;
	top: 200px;
	left: 400px; 
}
.msg-save {
	font-size: 11px;
	font-style: italic;
	font-weight: bolder;
	background-color: #fff;
	border: 2px solid #999;
	padding: 10px;
}
</style>
<div id="loading-save"></div>
<div style="clear: both;"></div>
<div style="float: left; width: 350px;">
<table>
	<tr>
		<td height="30">Sales</td>
		<td>
		<?php
		$select = $this->afg_m_stuffing_plan->get_sales_by_sp_id($id);
		$sales = $this->dbx->get_where('mstadmin',array('level <='=>2,'username !='=>'root','region_id'=>$this->session->userdata('region_id')));
		$data['option_sales']['']=' ';
		if($sales->num_rows() > 0 ) {
			foreach ($sales->result() as $row)
			{	
				$data['option_sales'][$row->user_id] = $row->fullname;
			}
			echo form_dropdown('salesForm', $data['option_sales'], $select, 'id="salesForm" style="width: 200; height: 20; border-radius: 2px; border: 1px solid #999;"');
		}
		else 
		{
			echo form_dropdown('salesForm', $data['option_sales'], '', 'id="salesForm" style="width: 200; height: 20; border-radius: 2px; border: 1px solid #999;"');
		}
      ?>
      </td>
	</tr>
	<tr>
		<td id="shipper_td"  height="30"></td>
		<td>
		<?php
		$agent = $this->user_model->get_all_shipper()->result();
		$data['option_shipper']['']=' ';
		foreach ($agent as $row)
		{	
			$data['option_shipper'][$row->shipper_id]=$row->shipper_id;
		}		
		echo form_dropdown('shipperForm',$data['option_shipper'],'','id="shipperForm" style="width: 200; height: 20; border-radius: 2px; border: 1px solid #999;"');
		?>
		</td>
	</tr>
	<tr>
		<td  height="30"></td>
		<td><textarea id="noteForm" style="max-width: 200px; min-width: 200px;" rows="3" class="detail"></textarea></td>
	</tr>
	<tr>
		<td id="consignee_td"  height="30"></td>
		<td>
		<?php
		$consignee = $this->consignee_notify_model->getListConsignee()->result();
		$data['option_consignee']['']=' ';
		foreach ($consignee as $row)
		{	
			$data['option_consignee'][$row->consignee_notify_code]=$row->consignee_notify_name;
		}		
		echo form_dropdown('consigneeSelectForm',$data['option_consignee'],'','id="consigneeSelectForm" style="width: 200; height: 20; border-radius: 2px; border: 1px solid #999;"');
		?>
		</td>
	</tr>
	<tr>
		<td  height="30"></td>
		<td>
			<textarea id="consigneeForm" style="max-width: 200px; min-width: 200px;" rows="3" class="detail"></textarea>
		</td>
	</tr>
	<tr>
		<td  height="30">Notify Party</td>
		<td>
		<?php		
		echo form_dropdown('notifySelectForm',$data['option_consignee'],'','id="notifySelectForm" style="width: 200; height: 20; border-radius: 2px; border: 1px solid #999;"');
		?>
		</td>
	</tr>
	<tr>
		<td  height="30"></td>
		<td><textarea id="notifyForm" style="max-width: 200px; min-width: 200px;" rows="3" class="detail"></textarea></td>
	</tr>
	
	<tr>
		<td style="vertical-align: top"  height="30">Discription of Goods <br> 
		<!--
		<a href="javascript:void(0)" class="attch" onclick="open_attachement('desc','<?php echo $mode ?>')">attachement</a>
		-->
		</td>
		<td><textarea id="descForm" style="max-width: 200px; min-width: 200px;" rows="3" class="detail"></textarea></td>
	</tr>
	
	<tr>
		<td style="vertical-align: top"  height="30">Freight Detail</td>
		<td><textarea id="freightschargesForm" style="max-width: 200px; min-width: 200px;" rows="3" class="detail"></textarea></td>
	</tr>
	<tr>
		<td style="vertical-align: top"  height="30">Marking<br> 
		<!--
		<a href="javascript:void(0)" class="attch" onclick="open_attachement('marks','<?php echo $mode ?>')">attachement</a>
		-->
		</td>
		<td><textarea id="coForm" style="max-width: 200px; min-width: 200px;" rows="3" class="detail"></textarea></td>
	</tr>
	<tr>
		<td>No of Pkgs</td>
		<!--
		<td>
		<?php
		$shipp_co = $this->shipping_company_model->getListCompany()->result();
		$data['option_shipp_co']['']='';
		foreach ($shipp_co as $row)
		{	
			$data['option_shipp_co'][$row->shipping_company_code]=$row->shipping_company_name;
		}		
		echo form_dropdown('selectShippingCompany',$data['option_shipp_co'],'','id="selectShippingCompany" style="width: 200; height: 20; border-radius: 2px; border: 1px solid #999;"');
		?>
		</td>
	</tr>
	<tr>
		<td style="vertical-align: top"></td>
		-->
		<td><textarea id="shippingmarksForm" style="max-width: 200px; min-width: 200px;" rows="3" class="detail"></textarea></td>
	</tr>
</table>
</div>
<div style="float: left; width: 220px;">
<table>
	<tr>
		<td  height="25">Quantity</td>
		<td><input id="qtyForm" class="detail-dua"></td>
	</tr>
	<tr>
		<td  height="25">Packaging</td>
		<td><input id="packForm" class="detail-dua"></td>
	</tr>
	
	<tr>
		<td  height="25">Gross Weight</td>
		<td><input id="weightForm" class="detail-dua"></td>
	</tr>
	<tr>
		<td  height="25">Nett Weight</td>
		<td><input id="shipmeasForm" class="detail-dua"></td>
	</tr>
	<tr>
		<td  height="25">B/L Meas</td>
		<td><input id="whmeasForm" class="detail-dua"></td>
	</tr>
	<tr>
		<td  height="25">CHGS Weight</td>
		<td><input id="chgsForm" class="detail-dua"></td>
	</tr>
	<tr>
		<td  height="25">Cost Meas</td>
		<td><input id="costmeasForm" class="detail-dua"></td>
	</tr>
	<tr>
		<td  height="25">WH Agent</td>
		<td><input id="whagentForm" class="detail-dua"></td>
	</tr>
	<tr>
		<td  height="25">WH Meas</td>
		<td><input id="blmeasForm" class="detail-dua"></td>
	</tr>
	<tr>
		<td  height="25">Destination</td>
		<td>
		<?php
	/*	$destination = $this->port_destination_model->getListDestination()->result();
		$data['option_destination']['']=' ';
		foreach ($destination as $row)
		{	
			$data['option_destination'][$row->portdestination_name]=$row->portdestination_name;
		}	
	*/
		$destination = $this->afg_m_cities->getListCitiesDrop()->result();
		$data['option_destination']['']=' ';
		foreach ($destination as $row)
		{	
			$data['option_destination'][$row->city_name]=$row->city_name;
		}
		echo form_dropdown('dropdownDestinationDetail',$data['option_destination'],'','id="dropdownDestinationDetail" style="width: 110; height: 20; border-radius: 2px; border: 1px solid #999;"');
		?></td>
	</tr>
	<tr>
		<td  height="25">Doc</td>
		<td><input id="docForm" class="detail-dua"></td>
	</tr>
	<tr>
		<td  height="25">HB-L</td>
		<td><input id="hblForm" class="detail-dua"></td>
	</tr>
	<tr>
		<td  height="25">C/S</td>
		<td><input id="csForm" class="detail-dua"></td>
	</tr>
	<tr>
		<td  height="25">Agent Code</td>
		<td>
		<?php
		$agent = $this->agent_company_model->getListAgent()->result();
		$data['option_agent']['']=' ';
		foreach ($agent as $row)
		{	
			$data['option_agent'][$row->agent_company_code]=$row->agent_company_code;
		}		
		echo form_dropdown('dropdownAgentDetail',$data['option_agent'],'','id="dropdownAgentDetail" style="width: 110; height: 20; border-radius: 2px; border: 1px solid #999;"');
		?>
		</td>
	</tr>
	<tr>
		<td  height="25">Coloader</td>
		<td>
		<?php
		$shipp = $this->shipping_company_model->getListCompany()->result();
		$data['option_company']['']=' ';
		foreach ($shipp as $row)
		{	
			$data['option_company'][$row->shipping_company_code]=$row->shipping_company_code;
		}		
		echo form_dropdown('dropdownShippingDetail',$data['option_company'],'','id="dropdownShippingDetail" style="width: 110; height: 20; border-radius: 2px; border: 1px solid #999;"');
		?>
		</td>
	</tr>
	<tr>
		<td  height="25">FREIGHT</td>
		<td>
			<select id="freightForm" style="width: 110; height: 20; border-radius: 2px; border: 1px solid #999;">
			 	<option value=" "></option>
				<option value="PREPAID">PREPAID</option>
				<option value="COLLECT">COLLECT</option>
			</select>
		</td>
	</tr>
	<tr>
		<td  height="25">Payable At</td>
		<td>
			<select id="statussellingForm" style="width: 110; height: 20; border-radius: 2px; border: 1px solid #999;">
			 	<option value=""></option>
			 	<option value="ORIGIN">ORIGIN</option>
				<option value="DESTINATION">DESTINATION</option>
			</select>
		</td>
	</tr>
</table>
</div>
<div style="float: left; width: 180px;">
<table>

	<tr>
		<td colspan="2"  height="25">Type Of Movement</td>
	<tr>
		<td height="20"><input type="radio" id="isnForm" name="isnForm" value="CY / CY"></td>
		<td>CY / CY</td>
	</tr>
	<tr>
		<td height="20"><input type="radio" id="isnForm" name="isnForm" value="CFS / CFS"></td>
		<td>CFS / CFS</td>
	</tr>
	<tr>
		<td height="20"><input type="radio" id="isnForm" name="isnForm" value="CY / CFS"></td>
		<td>CY / CFS</td>
	</tr>
	<tr>
		<td height="20"><input type="radio" id="isnForm" name="isnForm" value="CFS / CY"></td>
		<td>CFS / CY</td>
	</tr>
	
<!--
	<tr>
		<td>Opt Cost</td>
		<td><input type="checkbox" id="optcostForm"></td>
	</tr>
	<tr>
		<td>Spot Rate</td>
		<td><input type="checkbox" id="spotrateForm"></td>
	</tr>
	<tr>
	-->
		<td colspan="2" height="20">Shipment Type &nbsp;</td>
	</tr>
	<tr>
		<td colspan="2"  class="shipment" height="20"><input type="checkbox" id="express_release"> Express Release</td>
	</tr>
	<tr>
		<td colspan="2"  class="shipment" height="20"><input type="checkbox" id="shipped_on_board"> Shipped On Board</td>
	</tr>
	<tr>
		<td colspan="2"  class="shipment" height="20"><input type="checkbox" id="net_weight_tidak_dicetak"> Net Weight tidak dicetak</td>
	</tr>
	<tr>
		<td colspan="2"  class="shipment" height="20"><input type="checkbox" id="cetak_jml_doc_origi"> Cetak jml doc origi</td>
	</tr>
	<tr>
		<td colspan="2">
			<input type="hidden" id="operForm" value="<?php echo $mode ?>">
			<input type="hidden" id="idForm" value="<?php echo $id ?>">
			<input type="hidden" id="sp-detail-akhir">
			<input type="hidden" id="idRequestForm">
			<input type="hidden" id="kodeRefForm">
			<input type="hidden" id="isLockForm">
			<input type="hidden" id="numForm">
			<input type="hidden" id="wgctnForm">
			<input type="hidden" id="coForm">
		</td>
	</tr>
	<tr>
		<td colspan="2" align="center" id="btnUnlock"></td>
	</tr>
	<tr>
		<td colspan="2" align="center" id="btnBatal"></td>
	</tr>
	<tr>
		<td colspan="2" align="center" id="btnPindah"></td>
	</tr>
	<tr><td colspan="2"></td></tr>
	<tr>
		<td colspan="2" align="center" style="text-align: center">
			<input type="button" value=" SAVE " style="width: 80; height: 30; margin-top: 15px; margin-bottom: 15px" onclick="save_detail()">
			<input type="button" value=" CANCEL " style="width: 80; height: 30; margin-top: 15px; margin-bottom: 15px" onclick="form_close()">
		</td>
	</tr>
</table>
</div>
</div>
<div style="clear: both; height: 20px"></div>
<?php include "form-input-detail-script.php" ?>