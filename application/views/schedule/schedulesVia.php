<?php
/**
 * View: application/views/schedule/schedulesVia.php
 * Modernized mengikuti pola schedulesDirect:
 * - inline edit dihapus
 * - add/edit memakai 1 modal form
 * - save via AJAX endpoint ScheduleVia/saveForm
 * - edit load data via ScheduleVia/getFormData
 * - optional upload DO via ScheduleVia/extractDo
 * - city/liner pakai Select2 AJAX, tidak preload master besar
 */

$baseUrl      = base_url();
$currentMonth = $this->session->userdata('month_via');
$currentYear  = $this->session->userdata('db');

if (empty($currentMonth)) {
    $currentMonth = date('m');
}

$monthNames = array(
    '01' => 'Januari',
    '02' => 'Februari',
    '03' => 'Maret',
    '04' => 'April',
    '05' => 'Mei',
    '06' => 'Juni',
    '07' => 'Juli',
    '08' => 'Agustus',
    '09' => 'September',
    '10' => 'Oktober',
    '11' => 'November',
    '12' => 'Desember',
);

$currentMonthKey  = str_pad((int) $currentMonth, 2, '0', STR_PAD_LEFT);
$currentMonthName = isset($monthNames[$currentMonthKey]) ? $monthNames[$currentMonthKey] : $currentMonthKey;
?>

<link rel="stylesheet" type="text/css" href="<?php echo $baseUrl . 'assets/css/bootstrap.css'; ?>">
<link rel="stylesheet" type="text/css" href="<?php echo $baseUrl . 'assets/css/bootstrap-datepicker.css'; ?>">
<link rel="stylesheet" type="text/css" href="<?php echo $baseUrl . 'assets/css/jquery.dataTables.css'; ?>">
<link rel="stylesheet" type="text/css" href="<?php echo $baseUrl . 'assets/css/select2.min.css'; ?>">
<link rel="stylesheet" type="text/css" href="<?php echo $baseUrl . 'assets/css/dataTables.bootstrap.min.css'; ?>">
<link rel="stylesheet" type="text/css" href="<?php echo $baseUrl . 'assets/css/font-awesome.css'; ?>">
<link rel="stylesheet" type="text/css" href="<?php echo $baseUrl . 'assets/css/ionicons.css'; ?>">
<link rel="stylesheet" type="text/css" href="<?php echo $baseUrl . 'application/third_party/css/schedule.css'; ?>">

<script type="text/javascript" src="<?php echo $baseUrl . 'assets/js/jquery.js'; ?>"></script>
<script type="text/javascript" src="<?php echo $baseUrl . 'assets/js/combodate.js'; ?>"></script>
<script type="text/javascript" src="<?php echo $baseUrl . 'assets/js/moment.js'; ?>"></script>
<script type="text/javascript" src="<?php echo $baseUrl . 'assets/js/bootstrap.js'; ?>"></script>
<script type="text/javascript" src="<?php echo $baseUrl . 'assets/js/bootstrap-datepicker.js'; ?>"></script>
<script type="text/javascript" src="<?php echo $baseUrl . 'assets/js/jquery.dataTables.js'; ?>"></script>
<script type="text/javascript" src="<?php echo $baseUrl . 'assets/js/select2.full.min.js'; ?>"></script>
<script type="text/javascript" src="<?php echo $baseUrl . 'assets/js/dataTables.bootstrap.min.js'; ?>"></script>

<style type="text/css">
.schedule-page { font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; }
.schedule-page .box, .schedule-page .panel { border-radius:12px; border:none; box-shadow:0 4px 6px -1px rgba(0,0,0,.05), 0 2px 4px -1px rgba(0,0,0,.03); }
.schedule-page .panel-heading { font-weight:700; border-radius:12px 12px 0 0; text-transform:uppercase; letter-spacing:.5px; font-size:13px; }
.schedule-page .table > thead > tr > th { white-space:nowrap; vertical-align:middle; background:#f1f5f9; color:#475569; font-size:12px; text-transform:uppercase; letter-spacing:.5px; border-bottom:2px solid #e2e8f0; }
.schedule-page .table > tbody > tr > td { vertical-align:middle; white-space:nowrap; font-size:13px; color:#334155; }
.schedule-page .btn-action { margin:0 2px; border-radius:6px; }
.schedule-page .empty-state { padding:40px 25px; text-align:center; color:#64748b; background:#f8fafc; border:2px dashed #cbd5e1; border-radius:12px; font-size:15px; }
#modal-default input[type="text"], #modal-default textarea, #modal-default .select2-selection__rendered { text-transform:uppercase; }
#modal-default .modal-content { border-radius:16px; border:none; box-shadow:0 25px 50px -12px rgba(0,0,0,.25); overflow:hidden; }
#modal-default .modal-header { background:linear-gradient(135deg, #7c2d12 0%, #ea580c 100%); color:#fff; border-bottom:none; padding:20px 24px; }
#modal-default .modal-title { font-weight:700; font-size:18px; letter-spacing:.5px; }
#modal-default .close { color:#fff; opacity:.9; text-shadow:none; }
#modal-default .modal-body { padding:24px 32px; background:#f8fafc; max-height:calc(100vh - 160px); overflow-y:auto; }
#modal-default .modal-footer { background:#fff; border-top:1px solid #e2e8f0; padding:16px 32px; border-radius:0 0 16px 16px; }
#modal-default label { font-weight:600; color:#475569; font-size:12px; text-transform:uppercase; letter-spacing:.5px; margin-bottom:8px; }
#modal-default .form-control { height:44px; border-radius:8px; border:1px solid #cbd5e1; color:#1e293b; box-shadow:0 1px 2px rgba(0,0,0,.05); font-size:13px; transition:all .2s ease; }
#modal-default textarea.form-control { height:auto; min-height:90px; resize:vertical; }
#modal-default .form-control:focus { border-color:#f97316; box-shadow:0 0 0 3px rgba(249,115,22,.15); outline:none; }
#modal-default .input-group-addon { min-width:44px; border-color:#cbd5e1; background:#f1f5f9; color:#64748b; border-radius:8px 0 0 8px; border-right:none; }
#modal-default .input-group .form-control { border-radius:0 8px 8px 0; border-left:none; }
#modal-default .select2-container { width:100% !important; }
#modal-default .select2-container--default .select2-selection--single { height:44px; border:1px solid #cbd5e1; border-radius:8px; display:flex; align-items:center; box-shadow:0 1px 2px rgba(0,0,0,.05); }
#modal-default .input-group .select2-container--default .select2-selection--single { border-radius:0 8px 8px 0; border-left:none; }
#modal-default .select2-container--default .select2-selection--single .select2-selection__rendered { line-height:42px; color:#1e293b; font-size:13px; }
#modal-default .select2-container--default .select2-selection--single .select2-selection__arrow { height:42px; }
#modal-default .row-spacing { margin-bottom:20px; }
#modal-default .section-title { display:flex; align-items:center; gap:12px; margin:10px 0 20px; color:#0f172a; font-weight:700; font-size:14px; text-transform:uppercase; letter-spacing:.5px; }
#modal-default .section-title:after { content:""; flex:1; height:1px; background:#e2e8f0; }
#modal-default .help-block { margin-bottom:0; color:#64748b; font-size:12px; margin-top:6px; }
.mode-toggle-wrapper { display:flex; align-items:center; justify-content:center; background:#fff; padding:12px 24px; border-radius:12px; margin-bottom:24px; box-shadow:0 1px 3px rgba(0,0,0,.1); border:1px solid #e2e8f0; gap:16px; }
.toggle-label { font-size:14px; font-weight:600; color:#64748b; cursor:pointer; transition:color .3s; }
.toggle-label.active { color:#ea580c; }
.switch { position:relative; display:inline-block; width:52px; height:28px; }
.switch input { opacity:0; width:0; height:0; }
.slider { position:absolute; cursor:pointer; top:0; left:0; right:0; bottom:0; background-color:#cbd5e1; transition:.4s; border-radius:34px; }
.slider:before { position:absolute; content:""; height:20px; width:20px; left:4px; bottom:4px; background-color:white; transition:.4s; border-radius:50%; box-shadow:0 2px 4px rgba(0,0,0,.2); }
input:checked + .slider { background-color:#f97316; }
input:checked + .slider:before { transform:translateX(24px); }
.advanced-field { display:none; }
#modal-default .ocr-alert { display:none; margin-bottom:20px; border-radius:8px; }
#bookingPanel { display:none; margin-top:16px; }
#sendBcWrapper { display:none; padding:16px; background:#fff7ed; border:1px solid #fed7aa; border-radius:8px; color:#9a3412; }
.missing-field { border-color:#f97316 !important; background:#fff7ed !important; }
.loading-overlay { display:none; position:absolute; z-index:50; top:0; right:0; bottom:0; left:0; background:rgba(255,255,255,.85); backdrop-filter:blur(2px); align-items:center; justify-content:center; font-weight:700; color:#ea580c; font-size:16px; border-radius:16px; }
.label-success { background-color:#10b981; }
</style>

<div class="content-wrapper schedule-page">
  <section class="content-header">
    <h1>Schedule Via Management <small>Add, Edit, Delete</small></h1>
  </section>

  <section class="content">
    <?php if ($this->session->flashdata('success')) { ?>
      <div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><?php echo html_escape($this->session->flashdata('success')); ?></div>
    <?php } ?>
    <?php if ($this->session->flashdata('error')) { ?>
      <div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><?php echo html_escape($this->session->flashdata('error')); ?></div>
    <?php } ?>
    <div id="pageAlert" class="alert" style="display:none;"></div>

    <div class="box box-warning">
      <div class="box-body">
        <div class="panel panel-warning">
          <div class="panel-heading">Filter Data</div>
          <div class="panel-body">
            <form role="form" action="<?php echo $baseUrl; ?>schedule/ScheduleVia/scheduleViaListing" method="post">
              <div class="row">
                <div class="col-md-1"><div class="form-group"><label>Month</label><input type="text" id="month" data-format="MM" data-template="MMM" name="month_via" value="<?php echo html_escape($currentMonthKey); ?>"></div></div>
                <div class="col-md-1"><div class="form-group"><label>&nbsp;</label><br><button class="btn btn-warning" type="submit" style="border-radius:6px;"><i class="fa fa-search"></i> Cari</button></div></div>
                <div class="col-md-10 text-right"><div class="form-group"><label>&nbsp;</label><br><button type="button" class="btn btn-warning" id="btnAddSchedule" style="border-radius:6px;"><i class="fa fa-plus"></i> Add New Schedule Via</button></div></div>
              </div>
            </form>
          </div>
        </div>

        <div class="panel panel-default" style="border-color:#e2e8f0;">
          <div class="panel-heading text-center" style="background:#f8fafc; color:#1e293b; border-bottom:1px solid #e2e8f0;">Schedule Via Listings</div>
          <div class="panel-body">
            <h2 class="page-header bold text-center" style="margin-top:0; color:#0f172a;"><?php echo html_escape($currentMonthName . ' ' . $currentYear); ?></h2>
            <?php
            $hasAnyListing = false;
            if (!empty($scheduleViaRecords)) {
                foreach ($scheduleViaRecords as $tmpCity) {
                    if (!empty($tmpCity->schedule_via_listing)) { $hasAnyListing = true; break; }
                }
            }
            ?>

            <?php if (!$hasAnyListing) { ?>
              <div class="empty-state"><i class="fa fa-info-circle fa-2x" style="margin-bottom:12px; display:block;"></i>Tidak ada Schedule Via untuk periode ini.</div>
            <?php } else { ?>
              <div class="nav-tabs-custom" style="box-shadow:none; border-radius:8px; overflow:hidden; border:1px solid #e2e8f0;">
                <ul class="nav nav-tabs" style="background:#f1f5f9; border-bottom:1px solid #e2e8f0;">
                  <?php $tabIndex = 0; foreach ($scheduleViaRecords as $byCity) { if (empty($byCity->schedule_via_listing)) continue; $tabIndex++; ?>
                    <li class="<?php echo ($tabIndex === 1) ? 'active' : ''; ?>"><a href="#tab_<?php echo html_escape($byCity->url_friendly); ?>" data-toggle="tab" style="font-weight:600; color:#475569;"><?php echo html_escape($byCity->eta_city_id); ?></a></li>
                  <?php } ?>
                </ul>

                <div class="tab-content" style="padding:20px;">
                  <?php $paneIndex = 0; foreach ($scheduleViaRecords as $city) { if (empty($city->schedule_via_listing)) continue; $paneIndex++; ?>
                    <div class="tab-pane <?php echo ($paneIndex === 1) ? 'active' : ''; ?>" id="tab_<?php echo html_escape($city->url_friendly); ?>">
                      <div class="table-responsive">
                        <table class="table table-hover schedule-via-table">
							  <thead>
								<tr>
								  <th>1st Vessel</th>
								  <th>1st Voy</th>
								  <th>Stuffing</th>
								  <th>Closing</th>
								  <th>ETD JKT</th>
								  <th>2nd Vessel</th>
								  <th>2nd Voy</th>
								  <th>ETD Trans</th>
								  <th>Transit Port</th>
								  <th>ETA Dest</th>
								  <th>Destination</th>

								  <th>Booking No</th>
								  <th>Container</th>
								  <th>Cargo Type</th>

								  <th>Carrier</th>
								  <th>Warehouse</th>
								  <th width="95" class="text-center">Action</th>
								</tr>
							  </thead>

							  <tbody>
								<?php foreach ($city->schedule_via_listing as $record) { ?>

								  <?php
									$containerDisplay = '';

									if (isset($record->container_type) && trim((string) $record->container_type) !== '') {
										$ct = trim((string) $record->container_type);

										$decoded = json_decode($ct, true);
										if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
											$containerDisplay = implode(', ', array_filter($decoded));
										} else {
											$containerDisplay = $ct;
										}
									}

							

									$cargoType = '';
									if (isset($record->cargo_type) && trim((string) $record->cargo_type) !== '') {
										$cargoType = strtoupper(trim((string) $record->cargo_type));
									}

									$isDg = false;
									if (isset($record->is_dg) && (string) $record->is_dg === '1') {
										$isDg = true;
									}

									if ($cargoType === 'DG') {
										$isDg = true;
									}
								  ?>

								  <tr>
									<td><?php echo html_escape(isset($record->vessel) ? $record->vessel : ''); ?></td>
									<td><?php echo html_escape(isset($record->voy_vessel) ? $record->voy_vessel : ''); ?></td>
									<td><?php echo html_escape(isset($record->stuffing_date) ? $record->stuffing_date : ''); ?></td>
									<td><?php echo html_escape(isset($record->stf_cls) ? $record->stf_cls : ''); ?></td>
									<td><?php echo html_escape(isset($record->etd_jkt) ? $record->etd_jkt : ''); ?></td>

									<td><?php echo html_escape(isset($record->connecting) ? $record->connecting : ''); ?></td>
									<td><?php echo html_escape(isset($record->voy_con) ? $record->voy_con : ''); ?></td>
									<td><?php echo html_escape(isset($record->etd_con) ? $record->etd_con : ''); ?></td>
									<td><?php echo html_escape(isset($record->etd_city_con_id) ? $record->etd_city_con_id : ''); ?></td>
									<td><?php echo html_escape(isset($record->eta) ? $record->eta : ''); ?></td>
									<td><?php echo html_escape(isset($record->eta_city_id) ? $record->eta_city_id : ''); ?></td>

									<td><?php echo html_escape(isset($record->booking_number) ? $record->booking_number : ''); ?></td>
									<td><?php echo html_escape($containerDisplay); ?></td>

									<td>
									  <?php if ($isDg) { ?>
										<span class="label label-danger">DG</span>
									  <?php } else { ?>
										<span class="label label-success">GENCO</span>
									  <?php } ?>
									</td>

									<td>
									  <?php echo html_escape(isset($record->liner_id) && trim($record->liner_id) !== '' ? $record->liner_id : 'Empty'); ?>
									</td>

									<td>
									  <?php echo html_escape(isset($record->stuffing_location) && trim($record->stuffing_location) !== '' ? $record->stuffing_location : 'Empty'); ?>
									</td>

									<td class="text-center">
									  <button type="button"
											  class="btn btn-sm btn-primary btn-action btn-edit-schedule"
											  data-id="<?php echo (int) $record->id; ?>"
											  title="Edit Schedule">
										<i class="fa fa-pencil"></i>
									  </button>

									  <button type="button"
											  class="btn btn-sm btn-danger btn-action deleteScheduleVia"
											  data-id="<?php echo (int) $record->id; ?>"
											  title="Delete">
										<i class="fa fa-trash"></i>
									  </button>
									</td>
								  </tr>
								<?php } ?>
							  </tbody>
</table>
                      </div>
                    </div>
                  <?php } ?>
                </div>
              </div>
            <?php } ?>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<div class="modal fade" id="modal-default" tabindex="-1" role="dialog" aria-labelledby="modalLabel" aria-hidden="true" data-backdrop="static">
  <div class="modal-dialog modal-lg">
    <form role="form" id="scheduleViaForm" method="post" enctype="multipart/form-data" autocomplete="off">
      <div class="modal-content">
        <div class="loading-overlay" id="scheduleModalLoading"><span><i class="fa fa-circle-o-notch fa-spin fa-2x" style="margin-bottom:8px; display:block;"></i> Processing...</span></div>
        <div class="modal-header"><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button><h4 class="modal-title"><i class="fa fa-random"></i> <span id="formTitle">New Schedule Via</span></h4></div>
        <div class="modal-body">
          <div class="mode-toggle-wrapper"><span class="toggle-label active" id="labelAdvanced">AI Mode</span><label class="switch"><input type="checkbox" id="formModeToggle"><span class="slider"></span></label><span class="toggle-label" id="labelBasic">Basic Mode</span></div>
          <div id="formAlert" class="alert ocr-alert"></div>

          <input type="hidden" name="id" id="schedule_id" value="">
          <input type="hidden" name="year" id="form_year" value="<?php echo html_escape($currentYear); ?>">
          <input type="hidden" name="do_raw_text" id="do_raw_text" value="">
          <input type="hidden" name="do_extracted_json" id="do_extracted_json" value="{}">
          <input type="hidden" name="do_file_token" id="do_file_token" value="">

          <div class="section-title advanced-field"><i class="fa fa-file-text-o"></i> Delivery Order Pelayaran</div>
          <div class="row row-spacing advanced-field">
            <div class="col-md-6"><div class="form-group"><label>Upload DO Pelayaran</label><input type="file" class="form-control" name="do_file" id="scheduleFormDoFile" accept=".pdf,.png,.jpg,.jpeg,.tif,.tiff" style="padding-top:9px;"><p class="help-block">PDF akan direview oleh AI untuk autofill.</p></div></div>
            <div class="col-md-3"><div class="form-group"><label>&nbsp;</label><button type="button" class="btn btn-warning btn-block" id="btnExtractDo" style="height:44px; border-radius:8px; font-weight:bold;"><i class="fa fa-magic"></i> Extract DO</button></div></div>
            <div class="col-md-3"><div class="form-group"><label>AI Status</label><input type="text" class="form-control" id="ocr_status" value="Belum extract" readonly style="background:#f1f5f9; font-weight:600;"></div></div>
          </div>

          <div class="row row-spacing advanced-field">
            <div class="col-md-4"><div class="form-group"><label>Booking No / DO No / BL</label><input type="text" class="form-control" name="booking_number" id="booking_number"></div></div>
            <div class="col-md-4"><div class="form-group"><label>SI Number</label><input type="text" class="form-control" name="si_no" id="si_no"></div></div>
            <div class="col-md-4"><div class="form-group"><label>DO / Booking Date</label><input type="text" class="form-control datepicker" name="do_date" id="do_date" readonly></div></div>
          </div>

          <div class="section-title"><i class="fa fa-map-marker"></i> Route & Carrier</div>
          <div class="row row-spacing">
            <div class="col-md-4"><div class="form-group"><label>Schedule Period <span class="text-danger">*</span></label><select name="month_via" id="form_month" class="form-control" required><?php foreach ($monthNames as $monthNumber => $monthName) { ?><option value="<?php echo html_escape($monthNumber); ?>" <?php echo ($monthNumber === $currentMonthKey) ? 'selected' : ''; ?>><?php echo html_escape($monthName . ' ' . $currentYear); ?></option><?php } ?></select></div></div>
            <div class="col-md-4"><div class="form-group"><label>Port of Destination <span class="text-danger">*</span></label><select class="form-control select2-city" name="eta_city_id" id="eta_city_id" required></select></div></div>
            <div class="col-md-4"><div class="form-group"><label>Carrier / Liner <span class="text-danger">*</span></label><div class="input-group"><span class="input-group-addon"><i class="fa fa-building-o"></i></span><select class="form-control select2-liner" name="liner_id" id="liner_id" required></select></div></div></div>
          </div>

          <div class="row row-spacing advanced-field">
            <div class="col-md-3"><div class="form-group"><label>POL</label><input type="text" class="form-control" name="pol" id="pol" placeholder="JAKARTA"></div></div>
            <div class="col-md-3"><div class="form-group"><label>POD from DO</label><input type="text" class="form-control" name="pod" id="pod"></div></div>
            <div class="col-md-3"><div class="form-group"><label>Terminal</label><input type="text" class="form-control" name="terminal" id="terminal"></div></div>
            <div class="col-md-3"><div class="form-group"><label>Transit Terminal</label><input type="text" class="form-control" name="transit_terminal" id="transit_terminal"></div></div>
          </div>

          <div class="section-title"><i class="fa fa-ship"></i> First Leg</div>
          <div class="row row-spacing">
            <div class="col-md-3"><div class="form-group"><label>1st Vessel <span class="text-danger">*</span></label><input type="text" class="form-control" name="vessel" id="vessel" required></div></div>
            <div class="col-md-3"><div class="form-group"><label>1st Voyage <span class="text-danger">*</span></label><input type="text" class="form-control" name="voy_vessel" id="voy_vessel" required></div></div>
            <div class="col-md-3"><div class="form-group"><label>ETD Jakarta <span class="text-danger">*</span></label><div class="input-group date"><span class="input-group-addon"><i class="fa fa-calendar"></i></span><input type="text" class="form-control datepicker" name="etd_jkt" id="etd_jkt" readonly required></div></div></div>
            <div class="col-md-3"><div class="form-group"><label>Closing <span class="text-danger">*</span></label><div class="input-group date"><span class="input-group-addon"><i class="fa fa-calendar-times-o"></i></span><input type="text" class="form-control datepicker" name="stf_cls" id="stf_cls" readonly required></div></div></div>
          </div>

          <div class="section-title"><i class="fa fa-exchange"></i> Transshipment Leg</div>
          <div class="row row-spacing">
            <div class="col-md-3"><div class="form-group"><label>2nd Vessel <span class="text-danger">*</span></label><input type="text" class="form-control" name="connecting" id="connecting" required></div></div>
            <div class="col-md-3"><div class="form-group"><label>2nd Voyage <span class="text-danger">*</span></label><input type="text" class="form-control" name="voy_con" id="voy_con" required></div></div>
            <div class="col-md-3"><div class="form-group"><label>ETD Transit <span class="text-danger">*</span></label><div class="input-group date"><span class="input-group-addon"><i class="fa fa-calendar"></i></span><input type="text" class="form-control datepicker" name="etd_con" id="etd_con" readonly required></div></div></div>
            <div class="col-md-3"><div class="form-group"><label>Port of Transit <span class="text-danger">*</span></label><select class="form-control select2-city" name="etd_city_con_id" id="etd_city_con_id" required></select></div></div>
          </div>

          <div class="section-title"><i class="fa fa-calendar-check-o"></i> Destination & Stuffing</div>
          <div class="row row-spacing">
            <div class="col-md-3"><div class="form-group"><label>ETA Destination <span class="text-danger">*</span></label><div class="input-group date"><span class="input-group-addon"><i class="fa fa-calendar-check-o"></i></span><input type="text" class="form-control datepicker" name="eta" id="eta" readonly required></div></div></div>
            <div class="col-md-3"><div class="form-group"><label>Stuffing Date <span class="text-danger">*</span></label><div class="input-group date"><span class="input-group-addon"><i class="fa fa-cubes"></i></span><input type="text" class="form-control datepicker" name="stuffing_date" id="stuffing_date" readonly required></div></div></div>
            <div class="col-md-6"><div class="form-group"><label>Stuffing Location <span class="text-danger">*</span></label><div class="input-group"><span class="input-group-addon"><i class="fa fa-map-marker"></i></span><select class="form-control select2-warehouse" name="stuffing_location" id="stuffing_location" required></select></div></div></div>
          </div>

          <div class="row row-spacing advanced-field">
            <div class="col-md-3"><div class="form-group"><label>Open Stack</label><input type="text" class="form-control" name="open_stack_at" id="open_stack_at"></div></div>
            <div class="col-md-3"><div class="form-group"><label>Cargo Closing</label><input type="text" class="form-control" name="cargo_closing_at" id="cargo_closing_at"></div></div>
            <div class="col-md-3"><div class="form-group"><label>Doc / SI Closing</label><input type="text" class="form-control" name="doc_closing_at" id="doc_closing_at"></div></div>
            <div class="col-md-3"><div class="form-group"><label>VGM Cutoff</label><input type="text" class="form-control" name="vgm_cutoff_at" id="vgm_cutoff_at"></div></div>
          </div>

          <div class="row row-spacing advanced-field">
            <div class="col-md-3"><div class="form-group"><label>Container Type</label><input type="text" class="form-control" name="container_type" id="container_type"></div></div>
            <div class="col-md-3"><div class="form-group"><label>Container Qty</label><input type="text" class="form-control" name="container_qty" id="container_qty"></div></div>
            <div class="col-md-3"><div class="form-group"><label>Cargo Type</label><input type="text" class="form-control" name="cargo_type" id="cargo_type"></div></div>
            <div class="col-md-3"><div class="form-group"><label>DG</label><select class="form-control" name="is_dg" id="is_dg"><option value="0">NO</option><option value="1">YES</option></select></div></div>
          </div>

          <div class="row row-spacing advanced-field">
            <div class="col-md-6"><div class="form-group"><label>Commodity</label><input type="text" class="form-control" name="commodity" id="commodity"></div></div>
            <div class="col-md-3"><div class="form-group"><label>Gross Weight</label><input type="text" class="form-control" name="gross_weight" id="gross_weight"></div></div>
            <div class="col-md-3"><div class="form-group"><label>DG Details</label><input type="text" class="form-control" name="dg_details" id="dg_details"></div></div>
          </div>
          <input type="hidden" name="final_destination" id="final_destination">
          <input type="hidden" name="transit_port" id="transit_port">
          <input type="hidden" name="pickup_depot" id="pickup_depot">
          <input type="hidden" name="full_return_cy" id="full_return_cy">
          <input type="hidden" name="empty_pickup_at" id="empty_pickup_at">
          <input type="hidden" name="si_cutoff_at" id="si_cutoff_at">
          <input type="hidden" name="remarks" id="remarks">
          <input type="hidden" name="schedule_kind_hint" id="schedule_kind_hint" value="VIA">

          <div id="bookingPanel" class="advanced-field">
            <div class="section-title"><i class="fa fa-list"></i> Booking terkait schedule ini</div>
            <div class="table-responsive"><table class="table table-bordered">
			<thead>
                  <tr>
					<th>HBL</th>
                    <th>Cbm</th>
                    <th>Destination</th>
                    <th>Party</th>
                    <th>Shipper</th>
                    <th>Status</th>
                  </tr>
                </thead>
			<tbody id="bookingListBody"><tr><td colspan="5" class="text-center text-muted">Belum ada data booking.</td></tr></tbody></table></div>
            <div id="sendBcWrapper"><label style="text-transform:none; letter-spacing:0; margin-bottom:0; font-size:13px;"><input type="checkbox" name="send_bc_update" id="send_bc_update" value="1" style="margin-right:6px;"> Kirim update Booking Confirmation terbaru ke shipper setelah schedule disimpan.</label><p class="help-block">Perhatian, semua BC dan job terkait schedule ini otomatis terupdate sesuai dengan data di atas.</p></div>
          </div>

          <div class="advanced-field"><div class="section-title"><i class="fa fa-align-left"></i> Raw OCR Preview</div><textarea id="raw_ocr_preview" class="form-control" readonly style="background:#f1f5f9; font-family:monospace;"></textarea></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-default pull-left" data-dismiss="modal"><i class="fa fa-times"></i> Close</button><button type="submit" class="btn btn-warning" id="btnSaveSchedule"><i class="fa fa-save"></i> Save Schedule Via</button></div>
      </div>
    </form>
  </div>
</div>

<script type="text/javascript">
var baseURL = '<?= site_url() ?>';

(function ($) {
  'use strict';

  var endpoints = {
    get: '<?php echo $baseUrl; ?>schedule/ScheduleVia/getFormData',
    save: '<?php echo $baseUrl; ?>schedule/ScheduleVia/saveForm',
    extract: '<?php echo $baseUrl; ?>schedule/ScheduleVia/extractDo',
    remove: '<?php echo $baseUrl; ?>schedule/ScheduleVia/deleteScheduleVia'
  };

  var modal = $('#modal-default');
  var form = $('#scheduleViaForm');

  $('#formModeToggle').on('change', function () {
    if ($(this).is(':checked')) {
      $('.advanced-field').slideUp(300);
      $('#labelBasic').addClass('active');
      $('#labelAdvanced').removeClass('active');
    } else {
      $('.advanced-field').slideDown(300);
      $('#labelAdvanced').addClass('active');
      $('#labelBasic').removeClass('active');
    }
  });

  function escapeHtml(value) {
    return String(value == null ? '' : value).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
  }

  function showPageAlert(type, message) {
    var el = $('#pageAlert');
    el.removeClass('alert-success alert-danger alert-warning alert-info').addClass('alert-' + type).html(message).show();
    $('html, body').animate({ scrollTop: el.offset().top - 80 }, 250);
  }

  function showFormAlert(type, message) {
    $('#formAlert').removeClass('alert-success alert-danger alert-warning alert-info').addClass('alert-' + type).html(message).show();
  }

  function hideFormAlert() { $('#formAlert').hide().html(''); }

  function setLoading(loading) {
    if (loading) {
      $('#scheduleModalLoading').css('display', 'flex');
      $('#btnSaveSchedule, #btnExtractDo').prop('disabled', true);
    } else {
      $('#scheduleModalLoading').hide();
      $('#btnSaveSchedule, #btnExtractDo').prop('disabled', false);
    }
  }

  function setSelectValue(selector, id, text) {
    var el = $(selector);
    if (id == null || id === '') { el.val(null).trigger('change'); return; }
    text = text || id;
    var option = new Option(String(text).toUpperCase(), String(id).toUpperCase(), true, true);
    el.append(option).trigger('change');
  }

  function clearSelect(selector) { $(selector).val(null).trigger('change'); }

  function resetForm() {
    form[0].reset();
    form.find('.missing-field').removeClass('missing-field');
    $('#schedule_id').val('');
    $('#formTitle').text('New Schedule Via');
    $('#do_raw_text, #do_extracted_json, #do_file_token, #raw_ocr_preview').val('');
    $('#ocr_status').val('Belum extract');
    $('#bookingPanel').hide();
    $('#bookingListBody').html('<tr><td colspan="5" class="text-center text-muted">Belum ada data booking.</td></tr>');
    $('#sendBcWrapper').hide();
    $('#send_bc_update').prop('checked', false);
    hideFormAlert();
    clearSelect('#eta_city_id'); clearSelect('#liner_id'); clearSelect('#etd_city_con_id'); clearSelect('#stuffing_location');
    $('#form_month').val('<?php echo html_escape($currentMonthKey); ?>');
    $('#form_year').val('<?php echo html_escape($currentYear); ?>');
    $('#schedule_kind_hint').val('VIA');
    $('#formModeToggle').prop('checked', false).trigger('change');
  }

  function initMonthFilter() {
    if ($.fn.combodate && $('#month').length) {
      $('#month').combodate({ value: '<?php echo html_escape($currentMonthKey); ?>', customClass: 'form-control' });
    }
  }

  function initDatepicker() {
    $('.datepicker').datepicker({ autoclose: true, format: 'yyyy-mm-dd', todayHighlight: true });
  }

  function initDataTable() {
    if ($.fn.DataTable) {
      $('.schedule-via-table').DataTable({ pageLength: 25, ordering: true, autoWidth: false });
    }
  }

  function initSelect2() {
    $('.select2-city').select2({
      width: '100%', dropdownParent: modal, placeholder: 'PILIH PORT', minimumInputLength: 2,
      ajax: {
        url: baseURL + 'schedule/ScheduleVia/searchDestinations', dataType: 'json', delay: 300,
        data: function (params) { return { q: (params.term || '').toUpperCase() }; },
        processResults: function (data) { return { results: data.results || [] }; }
      }
    });

    $('#liner_id').select2({
      width: '100%', dropdownParent: modal, placeholder: 'PILIH CARRIER / LINER', minimumInputLength: 1,
      ajax: {
        url: baseURL + 'schedule/ScheduleVia/searchLiners', dataType: 'json', delay: 300,
        data: function (params) { return { q: (params.term || '').toUpperCase() }; },
        processResults: function (data) {
          return { results: $.map(data.results || [], function (obj) { return { id: obj.id, text: obj.text, name: obj.text || '' }; }) };
        }
      },
      escapeMarkup: function (markup) { return markup; },
      templateResult: function (repo) { if (repo.loading) return repo.text; return '<div style="padding:4px 0;"><div style="font-weight:600;color:#1e293b;">' + escapeHtml(repo.id || '-') + '</div><div style="font-size:12px;color:#64748b;">' + escapeHtml(repo.name || '') + '</div></div>'; },
      templateSelection: function (repo) { return repo.id || repo.text || ''; }
    });

    $('#stuffing_location').select2({
      width: '100%', dropdownParent: modal, placeholder: 'Search warehouse...', minimumInputLength: 1,
      ajax: {
        url: '<?php echo $baseUrl; ?>afg_c_dropdown/dropdown_warehouse_d', type: 'POST', dataType: 'json', delay: 250,
        data: function (params) { return { q: params.term }; },
        processResults: function (data) {
          return { results: $.map(data || [], function (obj) { return { id: obj.warehouse_code, text: obj.warehouse_code, name: obj.warehouse_name || '' }; }) };
        }
      },
      escapeMarkup: function (markup) { return markup; },
      templateResult: function (repo) { if (repo.loading) return repo.text; return '<div style="padding:4px 0;"><div style="font-weight:600;color:#1e293b;">' + escapeHtml(repo.id || repo.text || '-') + '</div><div style="font-size:12px;color:#64748b;">' + escapeHtml(repo.name || '') + '</div></div>'; },
      templateSelection: function (repo) { return repo.id || repo.text || ''; }
    });
  }

  function openNewForm() { resetForm(); $('#formTitle').text('New Schedule Via'); modal.modal('show'); }

  function renderBookings(bookings) {
  bookings = bookings || [];

  if (bookings.length === 0) {
    $('#bookingListBody').html('<tr><td colspan="6" class="text-center text-muted" style="padding: 16px;">Belum ada booking terkait schedule ini.</td></tr>');
    $('#sendBcWrapper').hide();
    return;
  }

  var html = '';
  let totalVolume = 0;
  let totalQuantity = 0;
  let totalRows = bookings.length;

  $.each(bookings, function (_, b) {
    let statusLabel = '';
    let statusClass = '';

    if (b.status === 'finalize') {
      statusLabel = 'Booking';
      statusClass = 'label label-success';
    } else if (b.status === 'booked') {
      statusLabel = 'Consolidated';
      statusClass = 'label label-warning';
    } else {
      statusLabel = b.status || '';
      statusClass = 'label label-default';
    }

    totalVolume += parseFloat(b.volume || 0);
    totalQuantity += parseInt(b.quantity || 0);

    html += '<tr>' +
      '<td style="font-size:12px;">' + escapeHtml(b.house_bl || b.si_number || '') + '</td>' +
      '<td style="font-size:12px; text-align:right;">' + escapeHtml(b.volume || '-') + '</td>' +
      '<td style="font-size:12px;">' + escapeHtml(b.destination_city || '') + '</td>' +
      '<td style="font-size:12px;">' + escapeHtml(b.quantity + ' ' + b.package_type) + '</td>' +
      '<td style="font-size:12px;">' + escapeHtml(b.forwarder_name || b.shipper_id || '') + '</td>' +
      '<td style="font-size:12px;"><span class="' + statusClass + '">' + escapeHtml(statusLabel) + '</span></td>' +
    '</tr>';
  });

  // baris total dengan highlight
  html += '<tr style="font-weight:bold; background:#e6f7ff;">' +
    '<td style="text-align:right;">TOTAL</td>' +
    '<td style="text-align:right;">' + totalVolume.toFixed(2) + '</td>' +
    '<td></td>' +
    '<td>' + totalQuantity + ' PACKAGES</td>' +
    '<td>' + totalRows + ' POS</td>' +
    '<td></td>' +
  '</tr>';

  $('#bookingListBody').html(html);
  $('#sendBcWrapper').show();
}


  function fillForm(schedule) {
    schedule = schedule || {};
    $('#schedule_id').val(schedule.id || '');
    $('#vessel').val(schedule.vessel || '');
    $('#voy_vessel').val(schedule.voy_vessel || '');
    $('#stf_cls').val(schedule.stf_cls || '');
    $('#etd_jkt').val(schedule.etd_jkt || '');
    $('#stuffing_date').val(schedule.stuffing_date || '');
    $('#connecting').val(schedule.connecting || '');
    $('#voy_con').val(schedule.voy_con || '');
    $('#etd_con').val(schedule.etd_con || '');
    $('#eta').val(schedule.eta || '');

    $.each(['booking_number','si_no','do_date','pol','pod','terminal','transit_terminal','container_type_raw','container_qty','cargo_type','is_dg','commodity','gross_weight','dg_details','final_destination','transit_port','pickup_depot','full_return_cy','empty_pickup_at','si_cutoff_at','remarks','schedule_kind_hint'], function (_, key) {
      if ($('#' + key).length) $('#' + key).val(schedule[key] || (key === 'schedule_kind_hint' ? 'VIA' : ''));
    });
    $.each(['open_stack_at','cargo_closing_at','doc_closing_at','vgm_cutoff_at'], function (_, key) { $('#' + key).val(schedule[key] || ''); });

    setSelectValue('#eta_city_id', schedule.eta_city_id || '', schedule.eta_city_id || '');
    setSelectValue('#etd_city_con_id', schedule.etd_city_con_id || '', schedule.etd_city_con_id || '');
    setSelectValue('#liner_id', schedule.liner_id || '', schedule.liner_id || '');
    setSelectValue('#stuffing_location', schedule.stuffing_location || '', schedule.stuffing_location || '');
    $('#formTitle').text('Edit Schedule Via #' + (schedule.id || ''));
  }

  function openEditForm(id) {
    resetForm(); setLoading(true); modal.modal('show');
    $.ajax({ url: endpoints.get, type: 'GET', dataType: 'json', data: { id: id } })
      .done(function (res) { if (!res || !res.status) { showFormAlert('danger', escapeHtml((res && res.message) || 'Gagal load data schedule.')); return; } fillForm(res.data.schedule || {}); renderBookings(res.data.bookings || []); $('#bookingPanel').show(); })
      .fail(function (xhr) { showFormAlert('danger', 'Gagal load data. HTTP ' + xhr.status); })
      .always(function () { setLoading(false); });
  }

  function applyOcrFields(fields, missingFields) {
		fields = fields || {};
		missingFields = missingFields || [];

		// Booking / DO
		$('#booking_number').val(fields.booking_number || '');
		$('#si_no').val(fields.si_no || '');
		$('#do_date').val(fields.do_date || '');

		// Container
		$('#container_type').val(fields.container_type || '');
		$('#container_qty').val(fields.container_qty || '');
		$('#container_type_raw').val(fields.container_type_raw || '');

		// Route
		$('#pol').val(fields.pol || '');
		$('#pod').val(fields.pod || '');
		$('#final_destination').val(fields.final_destination || '');
		$('#transit_port').val(fields.transit_port || '');
		$('#transit_terminal').val(fields.transit_terminal || '');
		$('#terminal').val(fields.terminal || '');

		// VIA core destination select
		if (fields.eta_city_id || fields.final_destination || fields.pod) {
			var etaCity = fields.eta_city_id || fields.final_destination || fields.pod;
			setAjaxSelectValue($('#eta_city_id'), etaCity, etaCity);
		}

		if (fields.etd_city_con_id || fields.transit_port) {
			var transitCity = fields.etd_city_con_id || fields.transit_port;
			setAjaxSelectValue($('#etd_city_con_id'), transitCity, transitCity);
		}

		// Carrier / Liner
		if (fields.liner_id) {
			setAjaxSelectValue($('#liner_id'), fields.liner_id, fields.liner_id);
		}

		// First vessel
		$('#vessel').val(fields.vessel || '');

		if (fields.voy_vessel || fields.voy) {
			$('#voy_vessel').val(fields.voy_vessel || fields.voy);
		}

		// Second vessel
		if (fields.connecting || fields.second_vessel) {
			$('#connecting').val(fields.connecting || fields.second_vessel);
		}

		if (fields.voy_con || fields.second_voy) {
			$('#voy_con').val(fields.voy_con || fields.second_voy);
		}

		// Dates
		$('#etd_jkt').val(fields.etd_jkt || '');
		$('#eta').val(fields.eta || '');
		$('#etd_con').val(fields.etd_con || '');
		$('#stf_cls').val(fields.stf_cls || '');
		$('#stuffing_date').val(fields.stuffing_date || '');

		// Cutoff / stack
		$('#open_stack_at').val(fields.open_stack_at || '');
		$('#cargo_closing_at').val(fields.cargo_closing_at || '');
		$('#doc_closing_at').val(fields.doc_closing_at || '');
		$('#vgm_cutoff_at').val(fields.vgm_cutoff_at || '');
		$('#si_cutoff_at').val(fields.si_cutoff_at || '');

		// Other DO fields
		$('#pickup_depot').val(fields.pickup_depot || '');
		$('#full_return_cy').val(fields.full_return_cy || '');
		$('#empty_pickup_at').val(fields.empty_pickup_at || '');
		$('#commodity').val(fields.commodity || '');
		$('#gross_weight').val(fields.gross_weight || '');
		$('#cargo_type').val(fields.cargo_type || '');
		$('#dg_details').val(fields.dg_details || '');
		$('#remarks').val(fields.remarks || '');

		if (fields.is_dg === '1' || fields.is_dg === 1 || fields.cargo_type === 'DG') {
			$('#is_dg').val('1');
			$('#cargo_type').val('DG');
		} else {
			$('#is_dg').val('0');
			if (!fields.cargo_type) {
				$('#cargo_type').val('GENERAL');
			}
		}

		// Tandai field missing
		$('.missing-field').removeClass('missing-field');

		missingFields.forEach(function (fieldName) {
			$('#' + fieldName).addClass('missing-field');
		});

		// Uppercase tampilan/value text form
		upperCaseScheduleForm($('#scheduleViaForm'));
	}
	
	function setAjaxSelectValue($select, value, text) {
		value = (value || '').toString().trim().toUpperCase();
		text  = (text || value).toString().trim().toUpperCase();

		if (!value) {
			$select.val(null).trigger('change');
			return;
		}

		var option = new Option(text, value, true, true);
		$select.append(option).trigger('change');
	}

  function extractDo() {
		var fileInput = $('#scheduleFormDoFile')[0];

		if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
			showFormAlert('warning', 'Pilih file DO terlebih dahulu.');
			return;
		}

		var fd = new FormData();
		fd.append('do_file', fileInput.files[0]);

		setLoading(true);
		hideFormAlert();
		$('#ocr_status').val('Extracting...');

		$.ajax({
			url: endpoints.extract,
			type: 'POST',
			dataType: 'json',
			data: fd,
			processData: false,
			contentType: false
		})
		.done(function (res) {
			if (!res || !res.status) {
				$('#ocr_status').val('Gagal OCR');
				showFormAlert('danger', escapeHtml((res && res.message) || 'OCR gagal.'));
				return;
			}

			var fields = res.fields || {};
			var missing = res.missing_fields || [];

			$('#ocr_status').val('OCR selesai');
			$('#do_raw_text').val(res.raw_text || '');
			$('#do_extracted_json').val(JSON.stringify(fields));
			$('#do_file_token').val(res.file_token || '');
			$('#raw_ocr_preview').val(res.raw_text || '');

			applyOcrFields(fields, missing);

			if (missing.length > 0) {
				showFormAlert(
					'warning',
					'OCR selesai, tapi field berikut belum terbaca: <b>' +
					escapeHtml(missing.join(', ')) +
					'</b>. Lengkapi manual sebelum save.'
				);
			} else {
				showFormAlert('success', 'OCR selesai. Silakan review data sebelum save.');
			}
		})
		.fail(function (xhr) {
			$('#ocr_status').val('Gagal OCR');
			showFormAlert('danger', 'OCR gagal. HTTP ' + xhr.status + '.');
		})
		.always(function () {
			setLoading(false);
		});
	}
	
	function upperCaseScheduleForm($form) {
		$form.find('input[type="text"], textarea').each(function () {
			var $el = $(this);
			var name = $el.attr('name') || '';

			if (
				name === 'do_raw_text' ||
				name === 'do_extracted_json' ||
				name === 'do_file_token'
			) {
				return;
			}

			var val = $el.val();

			if (typeof val === 'string' && val !== '') {
				$el.val(val.toUpperCase());
			}
		});
	}

  function saveForm(e) {
    e.preventDefault();
    if (!form[0].checkValidity()) { showFormAlert('warning', 'Lengkapi field wajib terlebih dahulu.'); return; }
    upperCaseScheduleForm(form);
    var fd = new FormData(form[0]);
    setLoading(true); hideFormAlert();
    $.ajax({ url: endpoints.save, type: 'POST', dataType: 'json', data: fd, processData: false, contentType: false })
      .done(function (res) { if (!res || !res.status) { showFormAlert('danger', escapeHtml((res && res.message) || 'Save gagal.')); return; } modal.modal('hide'); var msg = escapeHtml(res.message || 'Schedule berhasil disimpan.'); if (res.booking_count && parseInt(res.booking_count, 10) > 0) msg += '<br>Booking terkait: <b>' + parseInt(res.booking_count, 10) + '</b>'; showPageAlert('success', msg); setTimeout(function () { window.location.reload(); }, 900); })
      .fail(function (xhr) { var msg = 'Save gagal. HTTP ' + xhr.status; if (xhr.responseJSON && xhr.responseJSON.message) msg += ': ' + xhr.responseJSON.message; showFormAlert('danger', escapeHtml(msg)); })
      .always(function () { setLoading(false); });
  }

  function deleteSchedule(id, btn) {
    if (!confirm('Are you sure to delete this Schedule Via?')) return;
    btn.prop('disabled', true);
    $.ajax({ url: endpoints.remove, type: 'POST', dataType: 'json', data: { id: id } })
      .done(function (res) { if (res && res.status === true) { btn.closest('tr').remove(); showPageAlert('success', 'Schedule Via successfully deleted.'); } else { showPageAlert('danger', escapeHtml((res && res.message) || 'Schedule Via deletion failed.')); } })
      .fail(function (xhr) { showPageAlert('danger', 'Delete gagal. HTTP ' + xhr.status); })
      .always(function () { btn.prop('disabled', false); });
  }

  function bindEvents() {
    $('#btnAddSchedule').on('click', openNewForm);
    $(document).on('click', '.btn-edit-schedule', function () { openEditForm($(this).data('id')); });
    $(document).on('click', '.deleteScheduleVia', function () { deleteSchedule($(this).data('id'), $(this)); });
    $('#btnExtractDo').on('click', extractDo);
    form.on('submit', saveForm);
    modal.on('hidden.bs.modal', resetForm);
  }

  $(function () {
    var windowURL = window.location.href;
    var pageURL = windowURL.substring(0, windowURL.lastIndexOf('/'));
    $('a[href="' + pageURL + '"]').addClass('active').parent().addClass('active');
    $('a[href="' + windowURL + '"]').addClass('active').parent().addClass('active');
    initMonthFilter(); initDatepicker(); initSelect2(); initDataTable(); bindEvents();
  });
})(jQuery);

function upperCaseScheduleForm($form) {
  $form.find('input[type="text"], textarea').each(function () {
    var $el = $(this); var name = $el.attr('name') || '';
    if (name === 'do_raw_text' || name === 'do_extracted_json' || name === 'do_file_token') return;
    var val = $el.val(); if (typeof val === 'string' && val !== '') $el.val(val.toUpperCase());
  });
  $form.find('select').each(function () {
    var $el = $(this); var val = $el.val();
    if (typeof val === 'string' && val !== '') {
      var upper = val.toUpperCase();
      if ($el.find('option[value="' + upper.replace(/"/g, '\\"') + '"]').length === 0) $el.append(new Option(upper, upper, true, true));
      $el.val(upper).trigger('change');
    }
  });
}

$(document).on('input', '#modal-default input[type="text"], #modal-default textarea', function () {
  var name = $(this).attr('name') || '';
  if (name === 'do_raw_text' || name === 'do_extracted_json' || name === 'do_file_token') return;
  var start = this.selectionStart; var end = this.selectionEnd;
  this.value = this.value.toUpperCase();
  if (typeof this.setSelectionRange === 'function') this.setSelectionRange(start, end);
});
</script>
