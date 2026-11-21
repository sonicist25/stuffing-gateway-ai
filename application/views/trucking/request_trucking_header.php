<style>
table {
  font-family: arial, sans-serif;
  border-collapse: collapse;
  width: 100%;
}

.pac-container {
    background-color: #FFF;
    z-index: 3000;
    position: fixed;
    display: inline-block;
    float: left;
}
.modal{
    z-index: 20;   
}
.modal-backdrop{
    z-index: 10;        
}
td, th {
  border : 1px solid;
  text-align: left;
  padding: 3px;
}

tr:nth-child(even) {
  background-color: #3CBC8D;
}

#kiri
{
font-family: arial, sans-serif;
width:50%;
height:100px;
float:left;
}
#kanan
{
font-family: arial, sans-serif;
width:50%;
height:100px;
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

input
{
  width: 90%;
  padding: 12px 20px;
  margin: 8px 0;
  background-color: #3CBC8D;
  color: black;
}


</style>

	<div id="kiri">
	<table>
		<tr>
			<td>JOB NO</td>
			<td><?= $plan->no_job; ?></td>
		</tr>
		<tr>
			<td>SHIPPER</td>
			<td><?= $plan->shipper; ?></td>
		</tr>
		<tr>
			<td>PARTY</td>
			<td><?= $plan->packaging; ?></td>
		</tr>
		<tr>
			<td>DESTINATION</td>
			<td><?= $plan->destination; ?></td>
		</tr>
		</table>
		</div>
		<div id="kanan">
		<table>
		<tr>
			<td>TYPE :</td>
			<?php if ($plan->shipment_type_multi == 'FCL'){$type = 'FCL';} else {$type = 'LCL';} ?>
			<td><?= $type; ?></td>
		</tr>
		<tr>
			<td>FEEDER NAME :</td>
			<td><?= $plan->feeder_name; ?></td>
		</tr>
		<tr>
			<td>CONTAINER NO :</td>
			<td><?= $plan->container_no; ?></td>
		</tr>
		<tr>
			<td>ETD :</td>
			<td><?= date('d/m/Y', strtotime($plan->etd)); ?></td>
		</tr>
		</table>
		</div>
		<form id="reqtruckData">
			<input type="hidden" value="<?= $plan->id_request; ?>" name="id_request">
			<?php if(is_object($header)): ?>
			<input type="hidden" name="oper" value="add">
			<?php else: ?>
			<input type="hidden" name="oper" value="add">
			<?php endif;?>
			<input type="hidden" value="<?= $plan->no_job; ?>" name="no_job">
			<input type="hidden" value="<?= $plan->id_shipper; ?>" name="id_shipper">
			<input type="hidden" name="sp_detail_id" value="<?= $plan->sp_detail_id; ?>">
			<input type="hidden" value="<?= $plan->id_request; ?>" name="id_request">
			<input type="hidden" value="<?= $plan->hbl_no; ?>" name="hbl_no">
			<input type="hidden" value="<?= $plan->shipper; ?>" name="shipper">
			<input type="hidden" value="<?= $plan->consignee; ?>" name="consignee">
			<input type="hidden" value="<?= $plan->id_agent; ?>" name="agent_code">
			<input type="hidden" value="<?= $plan->destination?>" name="destination">
			<input type="hidden" value="<?= $plan->feeder_name?>" name="feeder_name">
			<input type="hidden" value="<?= $plan->container_no?>" name="container_no">
			<input type="hidden" value="<?= $plan->mbl_no?>" name="mbl_no">
			<input type="hidden" value="<?= $plan->prepared?>" name="prepared">
			<input type="hidden" value="<?= $plan->connecting?>" name="connecting">
			<div id="kiribawah">
				<input type="hidden" name="header_id" value="<?= is_object($header) ? $header->id : '' ?>">
				<div class="field">
					<label class="label">Pickup Date</label>
					<div class="control">
						<input id="pickup_date" name="pickup_date" class="input" type="datetime-local" placeholder="Pilih tanggal pickup">
					</div>
				</div>
				<div class="field">
					<label for="fname">Catatan untuk Driver:</label>
					<input type="text" id="notes" name="remark" value="<?= is_object($header) ? $header->remark : '' ?>"><br>
				</div>
				<?php if ($type == 'LCL') { ?>
				<div class="field">
					<label for="fname">Dimensi:</label>
					<input type="text" id="dimensi" name="dimention" value="<?= is_object($header) ? $header->dimention : '' ?>"><br>
					<small>
						Penulisan dengan cara PxLxT<br>
						Contoh: 300x50x20
					</small>
				</div>
				<?php } ?>
			</div>
			<div id="kananbawah">
				<label for="fname">Pickup Location:</label>
				<input id="inputPickup" type="text" size="50" placeholder="Enter a location" value="<?= is_object($header) ? $header->alamat_pickup : '' ?>" autocomplete="on" runat="server" />  
				<input type="hidden" name="posPickup" id="latlngPickup" value="<?= is_object($header) ? $header->middle_position_1 : '' ?>" />
				<input type="hidden" name="addrPickup" id="pickupAddress" value="<?= is_object($header) ? $header->alamat_pickup : '' ?>" />
				<label for="fname">Drop Location:</label>
				<input id="inputDrop" type="text" size="50" placeholder="Enter a location" autocomplete="on" runat="server" value="<?= is_object($header) ? $header->warehouse : '' ?>"/>  
				<input type="hidden" name="posDrop" id="latlngDrop" value="<?= is_object($header) ? $header->middle_position_2 : '' ?>" />
				<input type="hidden" name="addrDrop" id="dropAddress" value="<?= is_object($header) ? $header->warehouse : '' ?>" />

				<div class="field is-grouped">
					<div class="control">
						<button class="button is-link" type="button" onclick="kirim_data()" >Submit</button>
					</div>
				</div>
			</div>		
		</form>
<script>
<?php if(is_object($header)): ?>
$("#pickup_date").val(new Date('<?= $header->pickup_date ?>').toISOString().slice(0, 16));
<?php else: ?>
	var now = new Date().toISOString().slice(0, 16);
$("#pickup_date").val(now);
<?php endif; ?>

function initialize() {
  var inputPickup = document.getElementById('inputPickup');
  var acPickup = new google.maps.places.Autocomplete(inputPickup);
	acPickup.setComponentRestrictions({
		country: ["id"],
	});
	google.maps.event.addListener(acPickup, 'place_changed', function () {
		$(".pac-container").css("z-index", 2003);
		var place = acPickup.getPlace();
		$('#pickupAddress').val(place.formatted_address);
		$('#latlngPickup').val(place.geometry.location.lat()+", "+place.geometry.location.lng());
	});

	var inputDrop = document.getElementById('inputDrop');
  var acDrop = new google.maps.places.Autocomplete(inputDrop);
	acDrop.setComponentRestrictions({
		country: ["id"],
	});
	google.maps.event.addListener(acDrop, 'place_changed', function () {
		$(".pac-container").css("z-index", 3003);
		var place = acDrop.getPlace();
		$('#dropAddress').val(place.formatted_address);
		$('#latlngDrop').val(place.geometry.location.lat()+", "+place.geometry.location.lng());
	});
}
initialize();
function kirim_data(){
	if($("#pickup_date").val() == ""){
		alert("PILIH PICKUP DATE")
	}else{
		var data = JSON.stringify($("#reqtruckData").serializeArray());
			$.ajax({
			type : "POST",
			url : window.location.origin+'/afg_c_request_trucking/request_trucking_header',
			data: $("#reqtruckData").serialize(),
			success : function(data){
				$("#Req-Trucking").dialog('close');
			}
		})	
	}
}
</script>		