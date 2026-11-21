<style>
table {
  font-family: arial, sans-serif;
  border-collapse: collapse;
  width: 100%;
}

td, th {
  border: 1px solid #dddddd;
  text-align: left;
  padding: 3px;
}

tr:nth-child(even) {
  background-color: #dddddd;
}

#kiri
{
font-family: arial, sans-serif;
width:40%;
height:100px;
float:left;
}
#kanan
{
font-family: arial, sans-serif;
width:60%;
height:100px;
float:right;
}
#kiribawah
{
font-family: arial, sans-serif;
width:40%;
height:100px;
float:left;
}

#kananbawah
{
font-family: arial, sans-serif;
width:60%;
height:100px;
float:right;
}
.button {
background-color: #4CAF50; /* Green */
border: none;
color: white;
padding: 15px 32px;
text-align: center;
text-decoration: none;
display: inline-block;
font-size: 16px;
margin: 4px 2px;
cursor: pointer;
background-color: white;
color: black;
border: 5px solid #008CBA;
}
</style>

<?php
// ===== Helper (dideklarasikan di atas supaya bisa dipakai di seluruh view) =====
if (!function_exists('tracking_json_value')) {
    function tracking_json_value($json, $key, $default = '')
    {
        if ($json === null || $json === '') {
            return $default;
        }

        $arr = json_decode($json, true);

        if (!is_array($arr)) {
            return $default;
        }

        return isset($arr[$key]) && $arr[$key] !== null ? $arr[$key] : $default;
    }
}

if (!function_exists('e')) {
    function e($val)
    {
        return htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8');
    }
}

$is_direct      = isset($is_direct) ? (int)$is_direct : 0;
$connected_flag = isset($connected) ? (int)$connected : 0;
$connecting     = isset($connecting) ? $connecting : '';
$consol_destination = isset($consol_destination) ? $consol_destination : '';

$connecting_label = trim((string)$connecting);
if ($connecting_label === '') {
    $connecting_label = trim((string)$consol_destination);
}
if ($connecting_label === '') {
    $connecting_label = 'Transhipment Port';
}

// Label tujuan akhir: destination dari DB, fallback ke POD dari grid
$destination_label = trim((string)$destination);
if ($destination_label === '' && !empty($pod)) {
    $destination_label = trim((string)$pod);
}
if ($destination_label === '') {
    $destination_label = 'Port of Discharge';
}

// Tampilkan blok transhipment hanya jika BUKAN direct
$show_transhipment = !$is_direct && ($connected_flag !== 0 || trim((string)$connecting) !== '');
?>
<style>
.route-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: bold;
    letter-spacing: .3px;
    color: #ffffff;
}
.route-badge.direct { background: #15803d; }
.route-badge.transhipment { background: #b45309; }
</style>

	<div id="kiri">
	<table>
		<tr>
			<td>JOB NO</td>
			<td><?= e($no_job); ?></td>
		</tr>
		<tr>
			<td>SHIPPER</td>
			<td><?= e($shipper); ?></td>
		</tr>
		<tr>
			<td>FEEDER NAME :</td>
			<td><?= e($feeder_name); ?></td>
		</tr>
		<tr>
			<td>DESTINATION</td>
			<td><?= e($destination); ?></td>
		</tr>
		<tr>
			<td>ROUTE</td>
			<td>
				<?php if ($show_transhipment) { ?>
					<span class="route-badge transhipment">TRANSHIPMENT VIA <?= e($connecting_label) ?></span>
				<?php } else { ?>
					<span class="route-badge direct">DIRECT</span>
				<?php } ?>
			</td>
		</tr>
		</table>
		</div>
		<div id="kanan">
		<table>
		<tr>
			<td>HBL NO :</td>
			<td><?= e($hbl_no); ?></td>
		</tr>
		<tr>
			<td>CONTAINER NO :</td>
			<td><?= e($container_no); ?></td>
		</tr>
		<tr>
			<td>ETD :</td>
			<td><?= e($etd); ?></td>
		</tr>
		<tr>
			<td>ETA :</td>
			<td><?= e($eta); ?></td>
		</tr>
		<tr>
			<td>CONSOL DESTINATION :</td>
			<td><?= e($consol_destination); ?></td>
		</tr>
		</table>
		</div>
		<div id="kiribawah">
		<?php

$pickup_cargo_val = tracking_json_value($pickup_cargo, 'Pickup Cargo');

$dpol_atd_val     = tracking_json_value($dpol, 'Actual Time of Departure (ATD)', !empty($atd_pol) ? date('d/m/Y', strtotime($atd_pol)) : '');
$dpol_vessel_val  = tracking_json_value($dpol, 'Vessel Name', $feeder_name);

$atp_arrival_val  = tracking_json_value($atp, 'Arrival at Transhipment Port');
$atp_port_val     = tracking_json_value($atp, 'Transhipment Port', $connecting_label);

$dft_depart_val   = tracking_json_value($dft, 'Departure from Transhipment Port');
$dft_container_val = tracking_json_value($dft, 'Container No', $container_no);

$atd_ata_val      = tracking_json_value($atd, 'Actual Time of Arrival (ATA)', !empty($ata_des) ? date('d/m/Y', strtotime($ata_des)) : '');
$atd_vessel_val   = tracking_json_value($atd, 'Vessel/Voyage');

$pickup_do_val    = tracking_json_value($pickup_do, 'Pickup DO');
$cargo_del_val    = tracking_json_value($cargo_delivery, 'Cargo delivered');
?>
<form id="truckData">
			<input type="hidden" name="oper" value="add">
			<input type="hidden" value="<?= e($no_job); ?>" name="no_job">
			<input type="hidden" value="<?= e($id_shipper); ?>" name="id_shipper">
			<input type="hidden" name="sp_detail_id" value="<?= e($sp_detail_id); ?>">
			<input type="hidden" value="<?= e($id_request); ?>" name="id_request">
			<input type="hidden" value="<?= e($packaging); ?>" name="packaging">
			<input type="hidden" value="<?= e($hbl_no); ?>" name="hbl_no">
			<input type="hidden" value="<?= e($shipper); ?>" name="shipper">
			<input type="hidden" value="<?= e($consignee); ?>" name="consignee">
			<input type="hidden" value="<?= e($id_agent); ?>" name="agent_code">
			<input type="hidden" value="<?= e($destination) ?>" name="destination">
			<input type="hidden" value="<?= e($feeder_name) ?>" name="feeder_name">
			<input type="hidden" value="<?= e($container_no) ?>" name="container_no">
			<input type="hidden" value="<?= e($mbl_no) ?>" name="mbl_no">
			<input type="hidden" value="<?= e($prepared) ?>" name="prepared">
			<input type="hidden" value="<?= $is_direct ? '' : e($connecting) ?>" name="connecting">
			<input type="hidden" value="<?= e($etd) ?>" name="etd">
			<input type="hidden" value="<?= e($eta) ?>" name="eta">
			<input type="hidden" value="<?= e($consol_destination) ?>" name="consol_destination" id="consol_destination">
			<input type="hidden" value="<?= $is_direct ?>" name="is_direct" id="is_direct">
	<style>
.tracking-card {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    margin: 12px 0;
    background: #ffffff;
    border: 1px solid #d8dee9;
    border-radius: 8px;
    overflow: hidden;
    font-family: Arial, sans-serif;
}

.tracking-card th {
    background: #1f3c88;
    color: #ffffff;
    padding: 8px 10px;
    font-size: 12px;
    text-align: left;
    letter-spacing: .2px;
}

.tracking-card td {
    padding: 7px 10px;
    border-top: 1px solid #e5e7eb;
    background: #f8fafc;
}

.tracking-card tr:nth-child(even) td {
    background: #eef2f7;
}

.tracking-label {
    width: 48%;
    font-size: 12px;
    color: #1f2937;
    font-weight: bold;
}

.tracking-input {
    width: 95%;
    height: 25px;
    padding: 4px 7px;
    border: 1px solid #b8c0cc;
    border-radius: 4px;
    background: #ffffff;
    color: #111827;
    font-size: 12px;
    box-sizing: border-box;
}

.tracking-input[readonly] {
    background: #f3f4f6;
    color: #374151;
}

.tracking-section-hidden {
    display: none;
}

.button-save-tracking {
    min-width: 110px;
    height: 38px;
    border: 0;
    border-radius: 6px;
    background: #1f3c88;
    color: #ffffff;
    font-weight: bold;
    cursor: pointer;
    margin-top: 8px;
}

.button-save-tracking:hover {
    background: #172f6a;
}
</style>

<table id="pcCon" class="tracking-card export">
    <thead>
        <tr>
            <th colspan="2">Pickup Cargo from Shipper</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="tracking-label">
                <input type="hidden" name="pigo_attr_1" value="Pickup Cargo">
                Pickup Cargo
            </td>
            <td>
                <input type="text" name="pigo_val_1" id="pigo1" class="tracking-input js-datetime" placeholder="Pilih tanggal" value="<?= e($pickup_cargo_val) ?>">
            </td>
        </tr>
    </tbody>
</table>

<table id="dpolCon" class="tracking-card export">
    <thead>
        <tr>
            <th colspan="2">Departed from Port Of Loading</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="tracking-label">
                <input type="hidden" name="dpol_attr_1" value="Actual Time of Departure (ATD)">
                Actual Time of Departure (ATD)
            </td>
            <td>
                <input type="text" name="dpol_val_1" id="dpol1" class="tracking-input js-datetime" placeholder="Pilih tanggal" value="<?= e($dpol_atd_val) ?>">
            </td>
        </tr>
        <tr>
            <td class="tracking-label">
                <input type="hidden" name="dpol_attr_2" value="Vessel Name">
                Vessel Name
            </td>
            <td>
                <input type="text" name="dpol_val_2" class="tracking-input" placeholder="Vessel Name" value="<?= e($dpol_vessel_val) ?>">
            </td>
        </tr>
    </tbody>
</table>

<?php if ($show_transhipment) { ?>
<table id="atpCon" class="tracking-card export">
    <thead>
        <tr>
            <th colspan="2">Arrived at <?= e($connecting_label) ?></th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="tracking-label">
                <input type="hidden" name="atp_attr_1" value="Arrival at Transhipment Port">
                Arrival at Transhipment Port
            </td>
            <td>
                <input type="text" name="atp_val_1" id="atp1" class="tracking-input js-datetime" placeholder="Pilih tanggal" value="<?= e($atp_arrival_val) ?>">
            </td>
        </tr>
        <tr>
            <td class="tracking-label">
                <input type="hidden" name="atp_attr_2" value="Transhipment Port">
                Transhipment Port
            </td>
            <td>
                <input type="text" name="atp_val_2" class="tracking-input" placeholder="Transhipment Port" value="<?= e($atp_port_val) ?>">
            </td>
        </tr>
    </tbody>
</table>

<table id="dtpCon" class="tracking-card connect">
    <thead>
        <tr>
            <th colspan="2">Departed from <?= e($connecting_label) ?></th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="tracking-label">
                <input type="hidden" name="dtp_attr_1" value="Departure from Transhipment Port">
                Departure from Transhipment Port
            </td>
            <td>
                <input type="text" name="dtp_val_1" id="dtp1" class="tracking-input js-datetime" placeholder="Isi hanya jika consol MCC" value="<?= e($dft_depart_val) ?>">
            </td>
        </tr>
        <tr>
            <td class="tracking-label">
                <input type="hidden" name="dtp_attr_2" value="Container No">
                Container No
            </td>
            <td>
                <input type="text" name="dtp_val_2" class="tracking-input" placeholder="Container No Transhipment" value="<?= e($dft_container_val) ?>">
            </td>
        </tr>
    </tbody>
</table>
<?php } ?>

<table id="atdCon" class="tracking-card connect">
    <thead>
        <tr>
            <th colspan="2">Arrived at <?= e($destination_label) ?></th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="tracking-label">
                <input type="hidden" name="atd_attr_1" value="Actual Time of Arrival (ATA)">
                Actual Time of Arrival (ATA)
            </td>
            <td>
                <input type="text" name="atd_val_1" id="atd1" class="tracking-input js-datetime" placeholder="Pilih tanggal" value="<?= e($atd_ata_val) ?>">
            </td>
        </tr>
        <tr>
            <td class="tracking-label">
                <input type="hidden" name="atd_attr_2" value="Vessel/Voyage">
                Vessel/Voyage
            </td>
            <td>
                <input type="text" name="atd_val_2" class="tracking-input" placeholder="Vessel / Voyage" value="<?= e($atd_vessel_val) ?>">
            </td>
        </tr>
    </tbody>
</table>

<table id="pdoCon" class="tracking-card">
    <thead>
        <tr>
            <th colspan="2">Pickup DO</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="tracking-label">
                <input type="hidden" name="pdo_attr_1" value="Pickup DO">
                Pickup DO
            </td>
            <td>
                <input type="text" name="pdo_val_1" id="pdo1" class="tracking-input js-datetime" placeholder="Pilih tanggal" value="<?= e($pickup_do_val) ?>">
            </td>
        </tr>
    </tbody>
</table>

<table id="cdCon" class="tracking-card">
    <thead>
        <tr>
            <th colspan="2">Cargo Release</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="tracking-label">
                <input type="hidden" name="cd_attr_1" value="Cargo delivered">
                Cargo delivered
            </td>
            <td>
                <input type="text" name="cd_val_1" id="cd1" class="tracking-input js-datetime" placeholder="Pilih tanggal" value="<?= e($cargo_del_val) ?>">
            </td>
        </tr>
    </tbody>
</table>

<button class="button-save-tracking" onclick="kirim()" type="button">KIRIM</button>
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
<script>
var gagal = parseInt(<?= json_encode($gagal ?? 0) ?>, 10) || 0;
var tracking = parseInt(<?= json_encode($tracked ?? 0) ?>, 10) || 0;
var connected = parseInt(<?= json_encode($connected ?? 0) ?>, 10) || 0;

var connectingName = <?= json_encode($connecting ?? '') ?>;
var ga_ada = <?= json_encode($ga_ada ?? '') ?>;

ga_ada = String(ga_ada || '').trim();

console.log({
    gagal: gagal,
    tracking: tracking,
    connected: connected,
    connectingName: connectingName,
    ga_ada: ga_ada
});

var isSuccess = ga_ada.toUpperCase() === 'SUCCESS';

if (isSuccess && connected === 0) {
    $('.export').hide();
    $('.connect').hide();
} else if (isSuccess && connected !== 0) {
    $('.export').hide();
    $('.connect').show();
} else {
    $('.export').show();
    $('.connect').show();
}

$(function () {
    $(".js-datetime").datetimepicker({
        dateFormat: "dd/mm/yy",
        timeFormat: "HH:mm:ss"
    });
});

function kirim() {
    console.log($("#truckData").serializeArray());

    $.ajax({
        type: "POST",
        url: window.location.origin + "/afg_c_trucking/manage_tracking_export",
        data: $("#truckData").serialize(),
        dataType: "json",
        success: function(res) {
            Swal.fire({
                title: "Data Submitted!",
                text: "Tracking export berhasil disimpan.",
                icon: "success",
                timer: 1500,
                showConfirmButton: false
            });
        },
        error: function(xhr) {
            console.error(xhr.responseText);
            Swal.fire({
                title: "Failed",
                text: "Gagal menyimpan tracking export.",
                icon: "error"
            });
        }
    });
}

</script>