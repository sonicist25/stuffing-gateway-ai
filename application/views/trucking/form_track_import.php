<style>
table {
  font-family: arial, sans-serif;
  border-collapse: collapse;
  width: 100%;
}

td, th {
  border: 1px solid #dddddd;
  text-align: left;
  padding: 10px;
}

tr:nth-child(even) {
  background-color: #dddddd;
}

#kiri
{
font-family: arial, sans-serif;
font-size:12px;
width:50%;
height:150px;
float:left;
}
#kanan
{
font-family: arial, sans-serif;
font-size:12px;
width:50%;
height:150px;
float:right;
}

#kiribawah
{
font-family: arial, sans-serif;
width:50%;
height:100px;
float:left;
}

#kananbawah
{
font-family: arial, sans-serif;
width:50%;
height:100px;
float:right;
}

.button {
 background-color: #008CBA;
  border: none;
  color: white;
  padding: 15px 32px;
  text-align: center;
  text-decoration: none;
  display: inline-block;
  font-size: 16px;
  margin: 4px 2px;
  cursor: pointer;
}

</style>
</head>

<div id="kiri">
<table>
  <tr>
    <th>JOB NO</th>
    <td><?= $no_job; ?></td> 
  </tr>
  <tr>
    <td>HBL NO </td>
    <td><?= !empty($hbl_agent) ? $hbl_agent . "/" . $hbl_no : $hbl_no; ?></td>
   </tr>
  <tr>
      <td>CONSIGNEE</td>
    <td><?= $shipper; ?></td>
  </tr>
  <tr>
    <td>WAREHOUSE</td>
    <td><?= $warehouse; ?></td>
  </tr>
  </table>
  </div>
<div id="kanan">
<table>
  <tr>
    <td>AGENT</td>
    <td><?= $id_agent; ?></td>
  </tr>
    <td>DESTINATION</td>
    <td><?= $destination; ?></td>
  </tr>
   <tr>
    <td>FEEDER NAME </td>
    <td><?= $feeder_name; ?></td>
   </tr>
   <tr>
    <td>CONTAINER NO </td>
    <td><?= $container_no; ?></td>
   </tr>
   </table>
</div>
<div id="kiribawah" style="padding-top: 50px;">
   <form id="truckData">
   <table>
   <tr>
    <td>ETD :</td>
    <td><input type="text" size="30" id="etd" value="<?=$etd; ?>" name="etd" role="textbox" class="FormElement ui-widget-content ui-corner-all"/></td>
	<input type="hidden" value="<?= $id_request; ?>" name="id_request">
	<input type="hidden" value="<?= $warehouse; ?>" name="warehouse">
	<input type="hidden" name="oper" value="add">
	<input type="hidden" value="<?= $no_job; ?>" name="no_job">
	<input type="hidden" value="<?= $hbl_no; ?>" name="hbl_no">
	<input type="hidden" value="<?= $hbl_agent; ?>" name="hbl_agent">
	<input type="hidden" value="<?= $shipper; ?>" name="shipper">
	<input type="hidden" value="<?= $id_shipper; ?>" name="id_shipper">
	<input type="hidden" value="<?= $consignee; ?>" name="consignee">
	<input type="hidden" value="<?= $id_agent; ?>" name="id_agent">
	<input type="hidden" value="<?= $destination?>" name="destination">
	<input type="hidden" value="<?= $feeder_name?>" name="feeder_name">
	<input type="hidden" value="<?= $container_no?>" name="container_no">
	<input type="hidden" value="<?= $mbl_no?>" name="mbl_no">
	<input type="hidden" value="<?= $sp_detail_id?>" name="sp_detail_id">
	<input type="hidden" value="<?= $prepared?>" name="prepared">
   </tr>
   <tr>
    <td>ATD :</td>
    <?php if($atd != "") {?>
    <td><input type="text" size="30" id="atd" value="<?= $atd; ?>" placeholder="Actual Time Departure" name="atd" role="textbox" class="FormElement ui-widget-content ui-corner-all"/></td>
    <?php }else{ ?>
    <td><input type="text" size="30" id="atd" placeholder="Actual Time Departure" name="atd" role="textbox" class="FormElement ui-widget-content ui-corner-all"/></td>
    <?php } ?>
   </tr>
   <tr>
    <td>ETA :</td>
    <td><input type="text" size="30" readonly id="hbl_no" value="<?= $eta; ?>" name="eta" role="textbox" class="FormElement ui-widget-content ui-corner-all"/></td>
   </tr>
   <tr>
    <td>ATA :</td>
    <?php if($ata != "") {?>
      <td><input type="text" size="30" id="ata" placeholder="Actual Time Arrival" name="ata" value="<?= $ata; ?>" role="textbox" class="FormElement ui-widget-content ui-corner-all"/></td>
    <?php }else{ ?>
      <td><input type="text" size="30" id="ata" placeholder="Actual Time Arrival" name="ata" role="textbox" class="FormElement ui-widget-content ui-corner-all"/></td>
    <?php } ?>
   </tr>
     <tr>
    <td>STRIPPING (UNLOAD) DATE:</td>
    <?php if($ata != "") {?>
    <td><input type="text" size="30" id="stripping_date" placeholder="Stripping Date" name="stripping_date" value="<?= $stripping_date; ?>" role="textbox" class="FormElement ui-widget-content ui-corner-all"/></td>
    <?php }else{ ?>
    <td><input type="text" size="30" id="stripping_date" placeholder="Stripping Date" name="stripping_date" role="textbox" class="FormElement ui-widget-content ui-corner-all"/></td>
    <?php } ?>
   </tr>
   <tr>
    <td>PICKUP D/O :</td>
    <?php if($ata != "") {?>
    <td><input type="text" size="30" id="pickup_do" placeholder="Pickup DO" name="pickup_do" value="<?= $pickup_do; ?>" role="textbox" class="FormElement ui-widget-content ui-corner-all"/></td>
    <?php }else{ ?>
    <td><input type="text" size="30" id="pickup_do" placeholder="Pickup DO" name="pickup_do" role="textbox" class="FormElement ui-widget-content ui-corner-all"/></td>
    <?php } ?>
   </tr>
   <tr>
    <td>PICKUP CARGO :</td>
    <?php if($ata != "") {?>
    <td><input type="text" size="30" id="pickup_cargo" placeholder="Pickup Cargo" name="pickup_cargo" value="<?= $pickup_cargo; ?>" role="textbox" class="FormElement ui-widget-content ui-corner-all"/></td>
    <?php }else{ ?>
    <td><input type="text" size="30" id="pickup_cargo" placeholder="Pickup Cargo" name="pickup_cargo" role="textbox" class="FormElement ui-widget-content ui-corner-all"/></td>
    <?php } ?>
   </tr>
   <tr>
    <td>REMARKS :</td>
    <td><textarea name="remarks" style="width:190px;" role="textbox" class="FormElement ui-widget-content ui-corner-all" placeholder="Catatan"><?= $remarks ?></textarea></td>
   </tr>
</table>
	<button onclick="kirim()" class="button" type="button">UPDATE</button>
</form>
</div>
<div id="kananbawah">
<script type="application/x-javascript">
  addEventListener("load", function() {
    setTimeout(hideURLbar, 0);
  }, false);

  function hideURLbar() {
    window.scrollTo(0, 1);
  }
</script>
<link rel="stylesheet" href="<?php echo base_url('assets/css/gateway-cl.css'); ?>">
<div class="page_content container">
  <section class="">
    <div class="panel panel-default mt2">
      <div class="panel-body">
        <table width="100%" id="gatewaycl_tracking">
        <tr>
            <td colspan="4" id="gatewaycl_tracking_error"><?php echo $message; ?></td>
        </tr>
        <?php echo $tracking_result; ?>
    </table>
	  </div>
	</div>
</section>
</div>

</div>

<script>
$(function () {
	$("#eta").datepicker({dateFormat : "dd/mm/yy"});
	$("#ata").datepicker({dateFormat : "dd/mm/yy"});
	$("#etd").datepicker({dateFormat : "dd/mm/yy"});
	$("#atd").datepicker({dateFormat : "dd/mm/yy"});
	$("#pickup_do").datepicker({dateFormat : "dd/mm/yy"});
	$("#pickup_cargo").datepicker({dateFormat : "dd/mm/yy"});
})
function kirim(){
	var data = JSON.stringify($("#truckData").serializeArray());
	//console.log(data);
  $.ajax({
	type : "POST",
	url : window.location.origin+'/afg_c_trucking/manage_tracking_import',
	data: $("#truckData").serialize(),
	success : function(data){
		$("#Form-Trucking").dialog('close');
	}
  })	
}
</script>