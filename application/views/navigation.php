<?php
$CI =& get_instance();

if (!isset($CI->access_control)) {
    $CI->load->library('access_control');
}

$level = $CI->session->userdata('level_id');
$db    = $CI->session->userdata('db');

if ($db == '') {
    $db = date('Y');
}

for ($i = 2015; $i <= 2026; $i++) {
    $opt_thn[$i] = $i;
}

$region = $CI->region_model->get_data();
foreach ($region as $row) {
    $data['option_region'][$row->region_name] = strtoupper($row->region_name);
}

// ========================================================
// TAMBAHAN: LOGIKA AMBIL DATA PROFIL & CEK EXPIRED PASSWORD
// ========================================================
$user_id = $CI->session->userdata('user_id');
$user_data = $CI->db->get_where('mstadmin', ['user_id' => $user_id])->row();

$pass_warning = false;
$pass_msg = "";
$is_expired = false;

// Pastikan kolom last_password_change sudah ada di database
if ($user_data && isset($user_data->last_password_change)) {
    $last_change = new DateTime($user_data->last_password_change);
    $today = new DateTime();
    $diff = $today->diff($last_change)->days;
    $days_until_expired = 90 - $diff; // Set 3 bulan = 90 hari

    if ($days_until_expired <= 0) {
        $pass_warning = true;
        $is_expired = true;
        $pass_msg = "Password Expired! Wajib diganti.";
    } elseif ($days_until_expired <= 14) { // Warning H-14
        $pass_warning = true;
        $pass_msg = "Password kedaluwarsa dalam $days_until_expired hari.";
    }
}
// ========================================================

// ========================================================
// TAMBAHAN: NOTIFIKASI SELLING RATE MAU EXPIRED (H-7), TAPI MASIH VALID
// KHUSUS user level 2 (Sales), dan HANYA untuk shipper yang sales-nya
// adalah user yang sedang login sendiri.
// ========================================================
$selling_warning        = false;
$selling_expiring_count = 0;
$selling_notif_list     = array();

if ($level == 2) {
    // Gunakan koneksi DB sesuai tahun aktif (pola yang sama dipakai di semua model)
    if ($db == '' || !is_numeric($db)) {
        $dbSelling = $CI->load->database('master', TRUE);
    } else {
        $dbSelling = $CI->load->database(switch_db($db), TRUE);
    }

    if ($dbSelling->table_exists('a_selling')) {
        $today   = date('Ymd');
        $plus7   = date('Ymd', strtotime('+7 days'));
        $sales_user_id = (int) $user_id;

        // PENTING: di-GROUP per kode_shipper (1 baris = 1 customer, pakai
        // tanggal valid TERURGENT milik customer itu) -- 1 customer dengan
        // beberapa surat yang sama-sama mau expired cuma dihitung 1x.
        $dbSelling->select('kode_shipper, MIN(valid) as valid, MAX(ref_no) as ref_no', false);
        // Hanya yang MASIH valid (belum lewat) TAPI mau expired dalam 7 hari.
        // Yang sudah expired (valid < hari ini) SENGAJA tidak diikutkan lagi.
        $dbSelling->where('valid >=', $today);
        $dbSelling->where('valid <=', $plus7);
        // a_selling.kode_shipper == mstshipper.shipper_id (kode), dan
        // shipper_sales.id_shipper == mstshipper.id_shipper (numerik) --
        // pola join yang sama dengan apply_shipper_visibility() /
        // dropdown_shipper() di Selling_rate_model.php.
        $dbSelling->where(
            "kode_shipper IN (SELECT mstshipper.shipper_id FROM mstshipper WHERE mstshipper.id_shipper IN (SELECT id_shipper FROM shipper_sales WHERE sales_id = $sales_user_id))",
            null,
            false
        );
        $dbSelling->group_by('kode_shipper');
        $dbSelling->order_by('valid', 'asc');
        $dbSelling->limit(50);
        $expiring_result = $dbSelling->get('a_selling')->result();

        foreach ($expiring_result as $row) {
            $selling_notif_list[] = $row;
        }
        $selling_expiring_count = count($selling_notif_list);

        if ($selling_expiring_count > 0) {
            $selling_warning = true;
        }
    }
}
$selling_notif_total = $selling_expiring_count;
// CATATAN: angka ini bisa tetap lebih besar dari jumlah baris yang di-highlight
// di grid "Selling Rate Customer" ($("#table")) kalau ada customer lain yang
// belum sempat dicek manual -- tapi harusnya sudah dekat, karena sekarang
// kriterianya SAMA PERSIS dengan highlight di grid: valid >= hari ini DAN
// valid <= hari ini + 7 hari.

$selling_notif_title = '';
if ($selling_warning) {
    $lines = array();
    foreach (array_slice($selling_notif_list, 0, 8) as $row) {
        $status  = 'H-' . (new DateTime())->diff(DateTime::createFromFormat('Ymd', $row->valid))->days;
        $lines[] = $row->kode_shipper . ' (' . $row->ref_no . ') - ' . $status;
    }
    if (count($selling_notif_list) > 8) {
        $lines[] = '+ ' . (count($selling_notif_list) - 8) . ' lainnya...';
    }
    $selling_notif_title = implode("\n", $lines);
}
// ========================================================
?>

<style>
	/* TAMBAHAN: CSS untuk Notifikasi Balon & Modal Profil */
	.notif-balloon {
		background-color: #e74c3c;
		color: white;
		padding: 1px 6px;
		border-radius: 50%;
		font-size: 0.85em;
		font-weight: bold;
		display: inline-block;
		vertical-align: middle; /* Menjaga balon sejajar dengan teks */
		margin-left: 1px; /* Jarak balon dari teks nama */
		box-shadow: 0 0 5px rgba(231, 76, 60, 0.5);
		animation: pulse 1.5s infinite;
	}
	@keyframes pulse {
		0% { transform: scale(1); }
		50% { transform: scale(1.1); }
		100% { transform: scale(1); }
	}
	/* Simple Modal Styling */
	.profile-modal { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }
	.profile-modal-content { background-color: #fefefe; margin: 10% auto; padding: 20px; border: 1px solid #888; width: 60%; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.2); }
	.close-modal { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
	.close-modal:hover { color: black; }
</style>
<ul id="nav">
	<li class="top"><a href="javascript:void(0)nogo2" id="file" class="top_link"><span class="down">File</span></a>
            <ul class="sub">
            	
                  <li><a href="javascript:void(0)nogo3" class="fly">Type of</a>
                        <ul>
                              <li><a href="<?php echo base_url().'reference/type_container'; ?>">Container</a></li>
                              <li><a href="<?php echo base_url().'reference/type_service'; ?>">Service</a></li>
                        </ul>
                  </li>
                  <li class="mid"><a href="javascript:void(0)nogo7" class="fly">Port of</a>
                        <ul>
                              <li><a href="<?php echo base_url().'reference/port_destination'; ?>">Destination</a></li>
                              <li><a href="<?php echo base_url().'reference/port_loading'; ?>">Loading</a></li>
                        </ul>
                  </li>
                  <li><a href="<?php echo base_url().'reference/country'; ?>">Country</a></li>
				  <li><a href="<?php echo base_url().'reference/type_incoterms'; ?>">Incoterms</a></li>
                  <li><a href="<?php echo base_url().'reference/city'; ?>">City</a></li>
                  <li><a href="<?php echo base_url().'reference/stuffing_location'; ?>">Stuffing Location</a></li>
                  <li><a href="<?php echo base_url().'reference/trucking_company'; ?>">Trucking Vendor</a></li>
                  <li><a href="<?php echo base_url().'reference/shipping_company'; ?>">Ship Co / Coloader</a></li>
                  <li><a href="<?php echo base_url().'reference/agent_company'; ?>">Agent Company</a></li>
                  <li style="border-bottom:1px outset;"><a href="<?php echo base_url().'reference/warehouse'; ?>">Warehouse</a></li>
                 <!--  <li><a href="<?php echo base_url().'reference/shipper_exportir'; ?>">Shipper Export</a></li> -->
				  <li><a href="<?php echo base_url().'reference/consignee_import'; ?>">Consignee Import</a></li>
                  <li style="border-bottom:1px outset;"><a href="<?php echo base_url().'reference/consignee_notify'; ?>">Consignee Export</a></li>
                  <li><a href="<?php echo base_url().'reference/sales'; ?>">Sales Person</a></li>
                  <li><a href="<?php echo base_url().'reference/salesman_target'; ?>">Sales Target</a></li>
                  <li style="border-bottom:1px outset;"><a href="<?php echo base_url().'reference/sales_target'; ?>">Company Target</a></li>
                  <!-- <li style="border-bottom:1px outset;"><a href="<?php echo base_url().'reference/shipper_import'; ?>">Shipper Import</a></li>-->
                  <li><a href="<?php echo base_url().'reference/c_user_management'; ?>">Customer</a></li>
				  <?php if($this->session->userdata('user_level')==0 || $this->session->userdata('user_id')==107 ) { ?>
                  <li style="border-bottom:1px outset;"><a href="<?php echo base_url().'reference/account'; ?>">NPWP</a></li>
                  <li style="border-bottom:1px outset;"><a href="<?php echo base_url().'reference/vendor'; ?>">Payable Vendor</a></li>
				   <?php } ?>
					<!--<li><a href="<?php echo base_url().'reference/c_kurs_pajak'; ?>">Kurs Pajak</a></li>-->
				   <?php
                  if($this->session->userdata('user_level')==0) { ?>
                  <li><a href="<?php echo base_url().'finance/c_list_invoice'; ?>" target="blank">List Invoice</a></li>
				   <li><a href="<?php echo base_url().'Afg_c_delete_job'; ?>" target="blank">List Delete Job</a></li>
                  <!--<li><a href="<?php echo base_url().'application/actifity'; ?>" target="blank">Log Actifity</a></li>-->
                  <?php } 
				  $allowed_users = [0, 565, 23]; // daftar user_id yang diizinkan
				  ?>
           </ul>
      </li>
	  
      <li class="top"><a href="javascript:void(0)" id="price" class="top_link"><span class="down">Price List
				<?php if ($selling_warning): ?>
					<span class="notif-balloon" title="<?php echo htmlspecialchars($selling_notif_title); ?>">
						<?php echo $selling_notif_total; ?>
					</span>
				<?php endif; ?>
			</span></a>
            <ul class="sub">
                <li>
					<a href="<?php echo base_url().'pricelist/selling_rate'; ?>">
						Selling Rate Export
						<?php if ($selling_warning): ?>
							<span class="notif-balloon" title="<?php echo htmlspecialchars($selling_notif_title); ?>">!</span>
						<?php endif; ?>
					</a>
				</li>
				<li><a href="<?php echo base_url().'pricelist/import_selling_rate'; ?>">Selling Rate Import</a></li>
				<li style="border-bottom:1px  outset;"><a href="<?php echo base_url().'pricelist/air_selling_rate'; ?>">Selling Rate Export Air</a></li>
                <li style="border-bottom:1px  outset;"><a href="<?php echo base_url().'pricelist/insentive_agent'; ?>">Insentive from Agent</a></li>
				<li style="border-bottom:1px  outset;"><a href="<?php echo base_url().'pricelist/rebate_coloader'; ?>">Rebate form Coloader</a></li>
				<li style="border-bottom:1px  outset;"><a href="<?php echo base_url().'pricelist/import_rates'; ?>">Import Rates Agent</a></li>
	<?php if (in_array($user_id, $allowed_users)): ?>	
				<li><a href="<?php echo base_url().'pricelist/export_feeder_rate'; ?>">Buying Rate Carrier</a></li>
				<li><a href="<?php echo base_url().'pricelist/transhipment'; ?>">Transhipment Rate Agent</a></li>
				<li><a href="<?php echo base_url().'pricelist/trucking'; ?>">Buying Rate Trucking</a></li>
				<li><a href="<?php echo base_url().'pricelist/clearance'; ?>">Buying Rate Customs</a></li>
				<li style="border-bottom:1px  outset;"><a href="<?php echo base_url().'pricelist/air_freight_feeder_rate'; ?>">Buying Rate Airline</a></li>
	<?php endif; ?>		
				<li><a href="<?php echo base_url().'pricelist/freight'; ?>">Pricing Data</a></li>
            </ul>
      </li>
	  <?php if ($CI->access_control->can_menu('schedule')): ?>
	    <li class="top"><a href="javascript:void(0)" id="schedule" class="top_link"><span class="down">Schedule</span></a>
            <ul class="sub">
                <li><a href="<?php echo base_url().'schedule/ScheduleDirect/scheduleDirectListing'; ?>">Schedule Direct Export</a></li>
                <li><a href="<?php echo base_url().'Transhipment/scheduleTranshipmentListing'; ?>">Schedule Transhipment</a></li>
				<li style="border-bottom:1px  outset;"><a href="<?php echo base_url().'schedule/ScheduleVia/scheduleViaListing'; ?>">Schedule Via Export</a></li>
				<li><a href="<?php echo base_url().'schedule/ScheduleDirect/scheduleDirectListingImport'; ?>">Schedule Direct Import</a></li>
                <li><a href="<?php echo base_url().'schedule/ScheduleVia/scheduleViaListingImport'; ?>">Schedule Via Import</a></li>
            </ul>
      </li>
	  <?php endif; ?>
	  <?php if ($CI->access_control->can_menu('booking')): ?>
	  <li class="top"><a href="javascript:void(0)" id="booking" class="top_link"><span class="down">Booking</span></a>
            <ul class="sub">
				  <li><a href="<?php echo base_url().'pricelist/shipment_booking'; ?>">Booking List</a></li>
                  <li><a href="<?php echo base_url().'pricelist/afg_c_booking'; ?>">Requested Quote</a></li>
            </ul>
      </li>
	  <?php endif; ?>
	  <?php if ($CI->access_control->can_menu('transaction')): ?>
      <li class="top"><a href="javascript:void(0)" id="transaction" class="top_link"><span class="down">Transaction</span></a>
            <ul class="sub">
                  <li><?php echo anchor('afg_c_stuffing_plan', 'Stuffing Plan');?></li> 
				  <?php if ($CI->access_control->can_menu('do_import')): ?>				  
				  <li><?php echo anchor('Manifest', 'DO Import');?></li>
				  <?php endif; ?>
            </ul>
      </li>
	  <?php endif; ?>
	  <?php if ($CI->access_control->can_menu('finance')): ?>
     <li class="top"><a href="javascript:void(0)" id="finance" class="top_link"><span class="down">Finance</span></a>
         <ul class="sub">
						<li><a href="<?php echo base_url().'finance/c_inv_to_shipper'; ?>">Invoice to Shipper</a></li>
                  <li><a href="<?php echo base_url().'finance/c_finance_process'; ?>">Finance Process</a></li>
                  <li><a href="<?php echo base_url().'finance/c_cn_to_shipper'; ?>">Credit Note to Shipper</a></li>
                  <li style="border-bottom:1px  outset;"><a href="<?php echo base_url().'finance/c_cn_to_consignee'; ?>">Credit Note to Consignee</a></li>
                  <li style="border-bottom:1px  outset;"><a href="<?php echo base_url().'finance/c_proforma_invoice'; ?>">Proforma Invoice</a></li>
				  <li style="border-bottom:1px  outset;"><a href="<?php echo base_url().'finance/c_agenda_invoice'; ?>">Agenda Invoice</a></li>
     
        </ul>
      </li> 
	  <?php endif; ?>
 		<li class="top"><a href="javascript:void(0)" id="report" class="top_link"><span class="down">Report</span></a>
            <ul class="sub">
				  <li><a href="<?php echo base_url().'report/job_sheet'; ?>">Jumlah Job Per Type Service</a></li>
				  <li><a href="<?php echo base_url().'report/laporan_shipment'; ?>">RL Job Induk Perbulan</a></li>
				  <li style="border-bottom:1px  outset;"><a href="<?php echo base_url().'report/rekap_jobsheet'; ?>">RL Anak Job Perbulan</a></li>
                  <li><a href="<?php echo base_url().'report/sales_commission'; ?>">Sales Commission</a></li>
                  <li style="border-bottom:1px  outset;"><a href="<?php echo base_url().'report/discount_rebate_shipper'; ?>">Discount/Rebate Shipper</a></li>
                  <li><a href="<?php echo base_url().'report/rekap_sales'; ?>">Rekap / Sales</a></li>
                  <li style="border-bottom:1px;"><a href="<?php echo base_url().'report/rekap_shipper'; ?>">Rekap / Customer</a></li>
				  <!--<li style="border-bottom:1px  outset;"><a href="<?php echo base_url().'report/rekap_consignee'; ?>">Rekap / Consignee</a></li>-->
                  <li style="border-bottom:1px  outset;"><a href="<?php echo base_url().'report/rekap_agent'; ?>">Rekap / Agent</a></li>
                  <li><a href="<?php echo base_url().'report/listing_job'; ?>">List item blm buat Inv.</a></li>
                  <li><a href="<?php echo base_url().'report/list_unposting'; ?>">List Job blm diposting</a></li>
                  <li style="border-bottom:1px  outset;"><a href="<?php echo base_url().'report/list_inv_unposting'; ?>">List Inv blm diposting</a></li>
                  <li><a href="<?php echo base_url().'report/listing_invoice'; ?>">List Total Invoice</a></li>
            </ul>
      </li>
	  <li class="top"><a href="<?php echo base_url().'SupportTicket'; ?>" id="help" class="top_link"><span class="down">Help</span></a></li>
      <!-- ======================================================== -->
    <!-- MODIFIKASI: MENU PROFILE DENGAN NOTIFIKASI BALON         -->
    <!-- ======================================================== -->
	<li class="top" style="float: right; font-size:0.9em">
		<a href="<?= base_url('reference/c_user_management'); ?>" class="top_link">
			<span class="down">
				<!-- Icon User Modern (SVG) sebagai pengganti teks "Log as" -->
				<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="#555" viewBox="0 0 16 16" style="vertical-align: text-bottom; margin-right: 4px;">
					<path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
					<path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8zm8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1z"/>
				</svg>
				
				<font id="usr" style="color:blue"><?= $this->session->userdata('fullname'); ?></font>
				
				<!-- Icon Lonceng Notifikasi -->
				<?php if(isset($pass_warning) && $pass_warning): ?>
					<span class="notif-balloon" title="<?= $pass_msg; ?>" style="padding: 3px 6px; border-radius: 6px;">
						<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="white" viewBox="0 0 16 16" style="vertical-align: middle; margin-bottom: 1px;">
							<path d="M8 16a2 2 0 0 0 2-2H6a2 2 0 0 0 2 2zM8 1.918l-.797.161A4.002 4.002 0 0 0 4 6c0 .628-.134 2.197-.459 3.742-.16.767-.376 1.566-.663 2.258h10.244c-.287-.692-.502-1.49-.663-2.258C12.134 8.197 12 6.628 12 6a4.002 4.002 0 0 0-3.203-3.92L8 1.917zM14.22 12c.223.447.481.801.78 1H1c.299-.199.557-.553.78-1C2.68 10.2 3 6.88 3 6c0-2.42 1.72-4.44 4.005-4.901a1 1 0 1 1 1.99 0A5.002 5.002 0 0 1 13 6c0 .88.32 4.2 1.22 6z"/>
						</svg>
					</span>
				<?php endif; ?>
			</span>
		</a>
		<ul class="sub">
            <li>
                <a href="javascript:profile()">
                    <span class="down" style="color:blue">Profile</span>
                </a>
            </li>
            <li>
                <!-- Notif dibuat sebaris agar tidak menabrak Log Out -->
                <a href="javascript:change_password()">
                    Change Password 
                    <?php if(isset($pass_warning) && $pass_warning): ?>
                        <span style="color:red; font-weight:bold; font-size:0.9em;" title="<?= $pass_msg; ?>">&nbsp;[!]</span>
                    <?php endif; ?>
                </a>
            </li>
            <li><a href="javascript:process_logout()">Log Out</a></li>
        </ul>
	</li>
		
	<li class="top" style="float: right; font-size:0.9em">
		<a href="javascript:void(0)" class="top_link">
			<span class="down"> 
			<?php echo 'DB Year : '.form_dropdown('combo_tahun',$opt_thn,$db, 'id="combo_tahun" onchange="select_db()" onmouseover="active_select(this,\'in\')" onmouseout="active_select(this,\'out\')"');?>
			</span>
		</a>
	</li>
		
	<li class="top" style="float: right; font-size:0.9em">
		<a href="javascript:void(0)" class="top_link">
			<span class="down"> 
            <?php 
            if($level == 0 || $level == 5) :
                $disabled="";
            else :
                $disabled='disabled="disabled"';
            endif;
            echo 'Region : '.form_dropdown('region',$data['option_region'],REGID,'id="region" onchange="select_region()" onmouseover="active_select(this,\'in\')" onmouseout="active_select(this,\'out\')" '.$disabled);
            ?>
			</span>
		</a>
	</li>
</ul>
<div class="clearfix"></div>

<?php
$restrictMsg = $CI->session->flashdata('restrict');
if (!empty($restrictMsg)) {
    $CI->session->unset_userdata('restrict');
}
?>
<?php if (!empty($restrictMsg)): ?>
<script>
$(document).ready(function () {
    Swal.fire({
        icon: 'warning',
        title: 'Anda tidak memiliki akses menu ini.',
        text: <?= json_encode($restrictMsg) ?>,
        confirmButtonText: 'Mengerti'
    });
});
</script>
<?php endif; ?>
<?php
$masa_kerja_text = '-';
$badge = 'Cadet';
$badge_icon = base_url('assets/images/cadet.png');

if (!empty($user_data->join_date)) {
    $joinDate = new DateTime($user_data->join_date);
    $today = new DateTime();
    $diff = $today->diff($joinDate);

    // Teks lengkap tahun, bulan, hari
    $masa_kerja_text = "{$diff->y} tahun {$diff->m} bulan {$diff->d} hari";

    // Tentukan badge
    if ($diff->y >= 13) {
        $badge = 'Admiral';
    } elseif ($diff->y >= 10) {
        $badge = 'Captain';
    } elseif ($diff->y >= 7) {
        $badge = 'Chief Officer';
    } elseif ($diff->y >= 5) {
        $badge = 'Navigator';
    } elseif ($diff->y >= 2) {
        $badge = 'Sailor';
    } else {
        $badge = 'Cadet';
    }

    $badge_icon = base_url('assets/images/' . strtolower(str_replace(' ', '_', $badge)) . '.png');
}
?>



<!-- ======================================================== -->
<!-- TAMBAHAN: TEMPLATE HTML UNTUK VIEW PROFIL (MODAL)        -->
<!-- ======================================================== -->
<div id="myProfileModal" class="profile-modal">
    <div class="profile-modal-content">
        <span class="close-modal" onclick="closeProfileModal()">&times;</span>
       <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; border-bottom: 2px solid #eee; padding-bottom: 15px;">
            <div style="display: flex; align-items: center;">
                <img src="<?= base_url('assets/images/' . ($user_data->foto_profil ?? 'default.png')); ?>" 
                    style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 2px solid #3498db; margin-right: 20px;">
                <div>
                    <h2 style="margin: 0; color: #333;"><?= $user_data->fullname ?? '-'; ?></h2>
                    <p style="margin: 5px 0 0 0; color: #777; font-size: 1.1em;">
                        <?= $user_data->role_name ?? 'Staff'; ?> - <?= $user_data->region_id ?? '-'; ?>
                    </p>
                </div>
            </div>

            <!-- Badge di kanan atas -->
            <div style="display: flex; align-items: center;">
                <img src="<?= $badge_icon; ?>" alt="<?= $badge; ?>" 
                    style="width: 100px; height: 100px; object-fit: contain; margin-right: 10px;">
                <div>
                    <span style="font-weight: bold; color: #3498db; font-size: 1.1em;"><?= $badge; ?></span><br>
                    <span style="color: #777; font-size: 0.5em;"><?= $masa_kerja_text; ?></span>
                </div>
            </div>
        </div>


        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
            <div style="background: #f9f9f9; padding: 15px; border-radius: 8px;">
                <h4 style="margin-top: 0; border-bottom: 1px solid #ddd; padding-bottom: 5px;">Informasi Karyawan</h4>
                <p><strong>NIK:</strong> <br> <?= $user_data->nik ?? '-'; ?></p>
                <p><strong>Email:</strong> <br> <?= $user_data->email ?? '-'; ?></p>
                <p><strong>No. Handphone:</strong> <br> <?= $user_data->no_hp ?? '-'; ?></p>
                <p><strong>Tanggal Masuk:</strong> <br> <?= isset($user_data->join_date) ? date('d F Y', strtotime($user_data->join_date)) : '-'; ?></p>
            </div>

            <div style="background: #f9f9f9; padding: 15px; border-radius: 8px;">
                <h4 style="margin-top: 0; border-bottom: 1px solid #ddd; padding-bottom: 5px;">Kinerja & Tugas</h4>
                <p><strong>Job Desk:</strong> <br> <?= nl2br($user_data->job_desk ?? '-'); ?></p>
                
                <div style="margin-top: 15px;">
                    <strong>Skor KPI:</strong>
                    <div style="display: flex; align-items: center; margin-top: 5px;">
                        <h3 style="margin: 0; color: <?= (isset($user_data->kpi_score) && $user_data->kpi_score >= 80) ? 'green' : 'orange'; ?>;">
                            <?= $user_data->kpi_score ?? '0.00'; ?>
                        </h3>
                        <span style="font-size: 0.8em; margin-left: 5px; color: #777;">/ 100</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Sesuaikan fungsi javascript:profile() milik Anda sebelumnya 
// agar sekarang membuka modal ini
function profile() {
    document.getElementById("myProfileModal").style.display = "block";
}

function closeProfileModal() {
    document.getElementById("myProfileModal").style.display = "none";
}

// Menutup modal jika klik di luar area konten
window.onclick = function(event) {
    var modal = document.getElementById("myProfileModal");
    if (event.target == modal) {
        modal.style.display = "none";
    }
}
</script>