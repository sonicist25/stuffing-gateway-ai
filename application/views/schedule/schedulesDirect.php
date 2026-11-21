<?php
/**
 * View: application/views/schedule/schedulesDirect.php
 *
 * Refactor:
 * - Inline edit bootstrap-editable dihapus.
 * - Add/Edit memakai 1 modal form.
 * - Upload DO pelayaran + OCR offline via endpoint ScheduleDirect/extractDo.
 * - Edit load data schedule + booking terkait via endpoint ScheduleDirect/getFormData.
 * - Save create/update via endpoint ScheduleDirect/saveForm.
 * - Basic/Advanced mode toggle added. Upload DO hidden in Basic Mode.
 *
 * Catatan:
 * Controller/model harus sudah ditambah patch method:
 * - ScheduleDirect::extractDo()
 * - ScheduleDirect::getFormData()
 * - ScheduleDirect::saveForm()
 * - ScheduleDirect_model::getBookingsByScheduleId()
 * - ScheduleDirect_model::saveScheduleDirectForm()
 */

$baseUrl      = base_url();
$currentMonth = $this->session->userdata('month');
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

$currentMonthKey = str_pad((int) $currentMonth, 2, '0', STR_PAD_LEFT);
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
#bookingListBody td {
  font-size: 12px;   /* atau 11px sesuai kebutuhan */
}

  /* --- Modern UI Enhancements --- */
  .schedule-page {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
  }
  .schedule-page .box,
  .schedule-page .panel {
      border-radius: 12px;
      border: none;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
  }

  .schedule-page .panel-heading {
      font-weight: 700;
      border-radius: 12px 12px 0 0;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      font-size: 13px;
  }

  .schedule-page .table > thead > tr > th {
      white-space: nowrap;
      vertical-align: middle;
      background: #f1f5f9;
      color: #475569;
      font-size: 12px;
      text-transform: uppercase;
      letter-spacing: .5px;
      border-bottom: 2px solid #e2e8f0;
  }

  .schedule-page .table > tbody > tr > td {
      vertical-align: middle;
      white-space: nowrap;
      font-size: 13px;
      color: #334155;
  }

  .schedule-page .btn-action {
      margin: 0 2px;
      border-radius: 6px;
  }

  .schedule-page .empty-state {
      padding: 40px 25px;
      text-align: center;
      color: #64748b;
      background: #f8fafc;
      border: 2px dashed #cbd5e1;
      border-radius: 12px;
      font-size: 15px;
  }

  /* Modal Enhancements */
  #modal-default input[type="text"],
  #modal-default textarea,
  #modal-default .select2-selection__rendered {
      text-transform: uppercase;
  }

  #modal-default .modal-content {
      border-radius: 16px;
      border: none;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
      overflow: hidden;
  }

  #modal-default .modal-header {
      background: linear-gradient(135deg, #1e40af 0%, #2563eb 100%);
      color: #fff;
      border-bottom: none;
      padding: 20px 24px;
  }

  #modal-default .modal-title {
      font-weight: 700;
      font-size: 18px;
      letter-spacing: 0.5px;
  }

  #modal-default .close {
      color: #fff;
      opacity: .9;
      text-shadow: none;
  }

  #modal-default .modal-body {
      padding: 24px 32px;
      background: #f8fafc;
      max-height: calc(100vh - 160px);
      overflow-y: auto;
  }

  #modal-default .modal-footer {
      background: #ffffff;
      border-top: 1px solid #e2e8f0;
      padding: 16px 32px;
      border-radius: 0 0 16px 16px;
  }

  #modal-default label {
      font-weight: 600;
      color: #475569;
      font-size: 12px;
      text-transform: uppercase;
      letter-spacing: .5px;
      margin-bottom: 8px;
  }

  #modal-default .form-control {
      height: 44px;
      border-radius: 8px;
      border: 1px solid #cbd5e1;
      color: #1e293b;
      box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
      font-size: 13px;
      transition: all 0.2s ease;
  }

  #modal-default textarea.form-control {
      height: auto;
      min-height: 90px;
      resize: vertical;
  }

  #modal-default .form-control:focus {
      border-color: #3b82f6;
      box-shadow: 0 0 0 3px rgba(59, 130, 246, .15);
      outline: none;
  }

  #modal-default .input-group-addon {
      min-width: 44px;
      border-color: #cbd5e1;
      background: #f1f5f9;
      color: #64748b;
      border-radius: 8px 0 0 8px;
      border-right: none;
  }

  #modal-default .input-group .form-control {
      border-radius: 0 8px 8px 0;
      border-left: none;
  }

  #modal-default .select2-container {
      width: 100% !important;
  }

  #modal-default .select2-container--default .select2-selection--single {
      height: 44px;
      border: 1px solid #cbd5e1;
      border-radius: 8px;
      display: flex;
      align-items: center;
      box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
  }

  #modal-default .input-group .select2-container--default .select2-selection--single {
      border-radius: 0 8px 8px 0;
      border-left: none;
  }

  #modal-default .select2-container--default .select2-selection--single .select2-selection__rendered {
      line-height: 42px;
      color: #1e293b;
      font-size: 13px;
  }

  #modal-default .select2-container--default .select2-selection--single .select2-selection__arrow {
      height: 42px;
  }

  #modal-default .row-spacing {
      margin-bottom: 20px;
  }

  #modal-default .section-title {
      display: flex;
      align-items: center;
      gap: 12px;
      margin: 10px 0 20px;
      color: #0f172a;
      font-weight: 700;
      font-size: 14px;
      text-transform: uppercase;
      letter-spacing: .5px;
  }

  #modal-default .section-title:after {
      content: "";
      flex: 1;
      height: 1px;
      background: #e2e8f0;
  }

  #modal-default .help-block {
      margin-bottom: 0;
      color: #64748b;
      font-size: 12px;
      margin-top: 6px;
  }

  /* --- Form Toggle Slider --- */
  .mode-toggle-wrapper {
      display: flex;
      align-items: center;
      justify-content: center;
      background: #ffffff;
      padding: 12px 24px;
      border-radius: 12px;
      margin-bottom: 24px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.1);
      border: 1px solid #e2e8f0;
      gap: 16px;
  }
  
  .toggle-label {
      font-size: 14px;
      font-weight: 600;
      color: #64748b;
      cursor: pointer;
      transition: color 0.3s;
  }
  
  .toggle-label.active {
      color: #1e40af;
  }

  .switch {
      position: relative;
      display: inline-block;
      width: 52px;
      height: 28px;
  }

  .switch input { 
      opacity: 0;
      width: 0;
      height: 0;
  }

  .slider {
      position: absolute;
      cursor: pointer;
      top: 0; left: 0; right: 0; bottom: 0;
      background-color: #cbd5e1;
      transition: .4s;
      border-radius: 34px;
  }

  .slider:before {
      position: absolute;
      content: "";
      height: 20px;
      width: 20px;
      left: 4px;
      bottom: 4px;
      background-color: white;
      transition: .4s;
      border-radius: 50%;
      box-shadow: 0 2px 4px rgba(0,0,0,0.2);
  }

  input:checked + .slider {
      background-color: #3b82f6;
  }

  input:checked + .slider:before {
      transform: translateX(24px);
  }

  /* Advanced Field Toggle Class */
  .advanced-field {
      display: none; /* Hidden by default in Basic Mode */
  }

  /* Utils */
  #modal-default .ocr-alert {
      display: none;
      margin-bottom: 20px;
      border-radius: 8px;
  }

  #bookingPanel {
      display: none;
      margin-top: 16px;
  }

  #bookingPanel .table {
      margin-bottom: 12px;
      background: #fff;
      border-radius: 8px;
      overflow: hidden;
  }

  #sendBcWrapper {
      display: none;
      padding: 16px;
      background: #fff7ed;
      border: 1px solid #fed7aa;
      border-radius: 8px;
      color: #9a3412;
  }

  .missing-field {
      border-color: #f97316 !important;
      background: #fff7ed !important;
  }

  .loading-overlay {
      display: none;
      position: absolute;
      z-index: 50;
      top: 0; right: 0; bottom: 0; left: 0;
      background: rgba(255, 255, 255, .85);
      backdrop-filter: blur(2px);
      align-items: center;
      justify-content: center;
      font-weight: 700;
      color: #2563eb;
      font-size: 16px;
      border-radius: 16px;
  }

  .label-success {
      background-color: #10b981;
  }
</style>

<div class="content-wrapper schedule-page">
  <section class="content-header">
    <h1>
      Schedule Direct Management
      <small>Add, Edit, Delete</small>
    </h1>
  </section>

  <section class="content">
    <?php if ($this->session->flashdata('success')) { ?>
      <div class="alert alert-success alert-dismissible">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <?php echo html_escape($this->session->flashdata('success')); ?>
      </div>
    <?php } ?>

    <?php if ($this->session->flashdata('error')) { ?>
      <div class="alert alert-danger alert-dismissible">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <?php echo html_escape($this->session->flashdata('error')); ?>
      </div>
    <?php } ?>

    <div id="pageAlert" class="alert" style="display:none;"></div>

    <div class="box box-info">
      <div class="box-body">

        <div class="panel panel-info">
          <div class="panel-heading">Filter Data</div>
          <div class="panel-body">
            <form role="form" action="<?php echo $baseUrl; ?>schedule/ScheduleDirect/scheduleDirectListing" method="post">
              <div class="row">
                <div class="col-md-1">
                  <div class="form-group">
                    <label>Month</label>
                    <input type="text"
                           id="month"
                           data-format="MM"
                           data-template="MMM"
                           name="month"
                           value="<?php echo html_escape($currentMonthKey); ?>">
                  </div>
                </div>

                <div class="col-md-1">
                  <div class="form-group">
                    <label>&nbsp;</label><br>
                    <button class="btn btn-primary" type="submit" style="border-radius: 6px;">
                      <i class="fa fa-search"></i> Cari
                    </button>
                  </div>
                </div>

                <div class="col-md-10 text-right">
                  <div class="form-group">
                    <label>&nbsp;</label><br>
                    <button type="button" class="btn btn-primary" id="btnAddSchedule" style="border-radius: 6px; background: #2563eb; border: none;">
                      <i class="fa fa-plus"></i> Add New Schedule
                    </button>
                  </div>
                </div>
              </div>
            </form>
          </div>
        </div>

        <div class="panel panel-default" style="border-color: #e2e8f0;">
          <div class="panel-heading text-center" style="background: #f8fafc; color: #1e293b; border-bottom: 1px solid #e2e8f0;">
            Schedule Direct Listings
          </div>
          <div class="panel-body">
            <h2 class="page-header bold text-center" style="margin-top: 0; color: #0f172a;">
              <?php echo html_escape($currentMonthName . ' ' . $currentYear); ?>
            </h2>

            <?php
            $hasAnyListing = false;
            if (!empty($scheduleDirectRecords)) {
                foreach ($scheduleDirectRecords as $tmpCity) {
                    if (!empty($tmpCity->schedule_direct_listing)) {
                        $hasAnyListing = true;
                        break;
                    }
                }
            }
            ?>

            <?php if (!$hasAnyListing) { ?>
              <div class="empty-state">
                <i class="fa fa-info-circle fa-2x" style="margin-bottom: 12px; display: block;"></i>
                Tidak ada Schedule Direct untuk periode ini.
              </div>
            <?php } else { ?>

              <div class="nav-tabs-custom" style="box-shadow: none; border-radius: 8px; overflow: hidden; border: 1px solid #e2e8f0;">
                <ul class="nav nav-tabs" style="background: #f1f5f9; border-bottom: 1px solid #e2e8f0;">
                  <?php
                  $tabIndex = 0;
                  foreach ($scheduleDirectRecords as $byCity) {
                      if (empty($byCity->schedule_direct_listing)) {
                          continue;
                      }
                      $tabIndex++;
                      $activeClass = ($tabIndex === 1) ? 'active' : '';
                  ?>
                    <li class="<?php echo $activeClass; ?>">
                      <a href="#tab_<?php echo html_escape($byCity->url_friendly); ?>" data-toggle="tab" style="font-weight: 600; color: #475569;">
                        <?php echo html_escape($byCity->city_id); ?>
                      </a>
                    </li>
                  <?php } ?>
                </ul>

                <div class="tab-content" style="padding: 20px;">
                  <?php
                  $paneIndex = 0;
                  foreach ($scheduleDirectRecords as $city) {
                      if (empty($city->schedule_direct_listing)) {
                          continue;
                      }
                      $paneIndex++;
                      $activePaneClass = ($paneIndex === 1) ? 'active' : '';
                  ?>
                    <div class="tab-pane <?php echo $activePaneClass; ?>" id="tab_<?php echo html_escape($city->url_friendly); ?>">
                      <div class="table-responsive">
                        <table class="table table-hover schedule-direct-table">
                          <thead>
                            <tr>
                              <th>Vessel</th>
                              <th>Voy</th>
                              <th>Stuffing</th>
                              <th>Closing</th>
                              <th>ETD</th>
                              <th>ETA</th>
                              <th>Carrier</th>
                              <th>Warehouse</th>
                              <th>Booking / DO</th>
                              <th>Container</th>
                              <th>POL</th>
                              <th>DG</th>
                              <th width="95" class="text-center">Action</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php foreach ($city->schedule_direct_listing as $record) { ?>
                              <tr>
                                <td><?php echo html_escape(isset($record->vessel) ? $record->vessel : ''); ?></td>
                                <td><?php echo html_escape(isset($record->voy) ? $record->voy : ''); ?></td>
                                <td><?php echo html_escape(isset($record->stuffing_date) ? $record->stuffing_date : ''); ?></td>
                                <td><?php echo html_escape(isset($record->stf_cls) ? $record->stf_cls : ''); ?></td>
                                <td><?php echo html_escape(isset($record->etd_jkt) ? $record->etd_jkt : ''); ?></td>
                                <td><?php echo html_escape(isset($record->eta) ? $record->eta : ''); ?></td>
                                <td><?php echo html_escape(isset($record->carrier) && trim($record->carrier) !== '' ? $record->carrier : 'Empty'); ?></td>
                                <td><?php echo html_escape(isset($record->stuffing_location) && trim($record->stuffing_location) !== '' ? $record->stuffing_location : 'Empty'); ?></td>
                                <td><?php echo html_escape(isset($record->booking_number) ? $record->booking_number : ''); ?></td>
                                <td><?php echo html_escape(isset($record->container_type) ? $record->container_type : ''); ?></td>
                                <td><?php echo html_escape(isset($record->pol) ? $record->pol : ''); ?></td>
                                <td><?php echo !empty($record->is_dg) ? '<span class="label label-danger">DG</span>' : '<span class="label label-success">GENCO</span>'; ?></td>
                                <td class="text-center">
                                  <button type="button"
                                          class="btn btn-sm btn-primary btn-action btn-edit-schedule"
                                          data-id="<?php echo (int) $record->id; ?>"
                                          title="Edit Schedule">
                                    <i class="fa fa-pencil"></i>
                                  </button>
                                  <button type="button"
                                          class="btn btn-sm btn-danger btn-action deleteScheduleDirect"
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

<!-- Modal Form -->
<div class="modal fade" id="modal-default" tabindex="-1" role="dialog" aria-labelledby="modalLabel" aria-hidden="true" data-backdrop="static">
  <div class="modal-dialog modal-lg">
    <form role="form"
          id="scheduleDirectForm"
          method="post"
          enctype="multipart/form-data"
          autocomplete="off">

      <div class="modal-content">
        <div class="loading-overlay" id="scheduleModalLoading">
          <span><i class="fa fa-circle-o-notch fa-spin fa-2x" style="margin-bottom: 8px; display: block;"></i> Processing...</span>
        </div>

        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
          <h4 class="modal-title" id="modalLabel">
            <i class="fa fa-ship"></i> <span id="formTitle">New Schedule Direct</span>
          </h4>
        </div>

        <div class="modal-body">
          
          <!-- Basic / Advanced Toggle -->
          <div class="mode-toggle-wrapper">
              <span class="toggle-label active" id="labelAdvanced">AI Mode</span>
              <label class="switch">
                  <input type="checkbox" id="formModeToggle">
                  <span class="slider"></span>
              </label>
              <span class="toggle-label" id="labelBasic">Basic Mode</span>
          </div>

          <div id="formAlert" class="alert ocr-alert"></div>

          <input type="hidden" name="id" id="schedule_id" value="">
          <input type="hidden" name="year" id="form_year" value="<?php echo html_escape($currentYear); ?>">
          <input type="hidden" name="do_raw_text" id="do_raw_text" value="">
          <input type="hidden" name="do_extracted_json" id="do_extracted_json" value="">
          <input type="hidden" name="do_file_token" id="do_file_token" value="">

          <div class="section-title advanced-field">
            <i class="fa fa-file-text-o"></i> Delivery Order Pelayaran
          </div>

          <!-- Advanced Row: Upload & AI -->
          <div class="row row-spacing advanced-field">
            <div class="col-md-6">
              <div class="form-group">
                <label>Upload DO Pelayaran</label>
                <input type="file"
                       class="form-control"
                       name="do_file"
                       id="scheduleFormDoFile"
                       accept=".pdf,.png,.jpg,.jpeg,.tif,.tiff" style="padding-top: 9px;">
                <p class="help-block">
                  PDF akan direview oleh AI untuk autofill.
                </p>
              </div>
            </div>

            <div class="col-md-3">
              <div class="form-group">
                <label>&nbsp;</label>
                <button type="button" class="btn btn-warning btn-block" id="btnExtractDo" style="height: 44px; border-radius: 8px; font-weight: bold;">
                  <i class="fa fa-magic"></i> Extract DO
                </button>
              </div>
            </div>

            <div class="col-md-3">
              <div class="form-group">
                <label>AI Status</label>
                <input type="text" class="form-control" id="ocr_status" value="Belum extract" readonly style="background: #f1f5f9; font-weight: 600;">
              </div>
            </div>
          </div>

          <!-- Advanced Row: Booking/DO specifics -->
          <div class="row row-spacing advanced-field">
            <div class="col-md-4">
              <div class="form-group">
                <label>Booking No / DO No / BL</label>
                <input type="text" class="form-control" name="booking_number" id="booking_number" placeholder="Booking / DO No">
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>SI Number</label>
                <input type="text" class="form-control" name="si_no" id="si_no" placeholder="SI / BL / Waybill No">
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>DO / Booking Date</label>
                <input type="text" class="form-control datepicker" name="do_date" id="do_date" readonly placeholder="YYYY-MM-DD">
              </div>
            </div>
          </div>

          <!-- Advanced Row: Containers -->
          <div class="row row-spacing advanced-field">
            <div class="col-md-3">
              <div class="form-group">
                <label>Container Type</label>
                <input type="text" class="form-control" name="container_type" id="container_type" placeholder="40HC x1 / 20DC x1">
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label>Container Qty</label>
                <input type="text" class="form-control" name="container_qty" id="container_qty" placeholder="1 / 2 / 3">
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Raw Container Text</label>
                <input type="text" class="form-control" name="container_type_raw" id="container_type_raw" placeholder="Original text from DO">
              </div>
            </div>
          </div>

          <div class="section-title">
            <i class="fa fa-map-marker"></i> Route & Carrier
          </div>

          <!-- Basic Row: Core Route Data -->
          <div class="row row-spacing">
            <div class="col-md-4">
              <div class="form-group">
                <label>Schedule Period <span class="text-danger">*</span></label>
                <select name="month" id="form_month" class="form-control" required>
                  <?php foreach ($monthNames as $monthNumber => $monthName) { ?>
                    <option value="<?php echo html_escape($monthNumber); ?>" <?php echo ($monthNumber === $currentMonthKey) ? 'selected' : ''; ?>>
                      <?php echo html_escape($monthName . ' ' . $currentYear); ?>
                    </option>
                  <?php } ?>
                </select>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Port of Destination <span class="text-danger">*</span></label>
                <select class="form-control select2-city" name="city_id" id="city_id" required></select>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Carrier / Liner <span class="text-danger">*</span></label>
                <div class="input-group">
                  <span class="input-group-addon"><i class="fa fa-building-o"></i></span>
                  <select class="form-control select2-liner" name="liner_id" id="liner_id" required></select>
                </div>
              </div>
            </div>
          </div>

          <!-- Advanced Row: Specific Route Data -->
          <div class="row row-spacing advanced-field">
            <div class="col-md-3">
              <div class="form-group">
                <label>POL</label>
                <input type="text" class="form-control" name="pol" id="pol" placeholder="JAKARTA">
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label>POD from DO</label>
                <input type="text" class="form-control" name="pod" id="pod" placeholder="Destination from DO">
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label>Transit Port</label>
                <input type="text" class="form-control" name="transit_port" id="transit_port" placeholder="If any">
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label>Terminal / Berthing</label>
                <input type="text" class="form-control" name="terminal" id="terminal" placeholder="JICT / NPCT1 / T300">
              </div>
            </div>
          </div>

          <!-- Hidden core fields kept for logic -->
          <div style="display:none;">
             <input type="text" name="final_destination" id="final_destination">
             <input type="text" name="transit_terminal" id="transit_terminal">
          </div>

          <div class="section-title">
            <i class="fa fa-ship"></i> Vessel Schedule
          </div>

          <!-- Basic Row: Core Vessel Schedule -->
          <div class="row row-spacing">
            <div class="col-md-3">
              <div class="form-group">
                <label>Vessel <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="vessel" id="vessel" placeholder="Enter vessel name" required>
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label>Voyage <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="voy" id="voy" placeholder="Enter voyage number" required>
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label>ETD Origin <span class="text-danger">*</span></label>
                <div class="input-group date">
                  <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                  <input type="text" class="form-control datepicker" name="etd_jkt" id="etd_jkt" readonly placeholder="Select ETD" required>
                </div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label>ETA Destination <span class="text-danger">*</span></label>
                <div class="input-group date">
                  <span class="input-group-addon"><i class="fa fa-calendar-check-o"></i></span>
                  <input type="text" class="form-control datepicker" name="eta" id="eta" readonly placeholder="Select ETA" required>
                </div>
              </div>
            </div>
          </div>

          <!-- Advanced Row: Cutoffs & Open Stack -->
          <div class="row row-spacing advanced-field">
            <div class="col-md-3">
              <div class="form-group">
                <label>Open Stack</label>
                <input type="text" class="form-control" name="open_stack_at" id="open_stack_at" placeholder="YYYY-MM-DD HH:MM:SS">
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label>Cargo Closing</label>
                <input type="text" class="form-control" name="cargo_closing_at" id="cargo_closing_at" placeholder="YYYY-MM-DD HH:MM:SS">
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label>Doc / SI Closing</label>
                <input type="text" class="form-control" name="doc_closing_at" id="doc_closing_at" placeholder="YYYY-MM-DD HH:MM:SS">
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label>VGM Cutoff</label>
                <input type="text" class="form-control" name="vgm_cutoff_at" id="vgm_cutoff_at" placeholder="YYYY-MM-DD HH:MM:SS">
              </div>
            </div>
          </div>

          <!-- Advanced Row: Empty & Pickup -->
          <div class="row row-spacing advanced-field">
            <div class="col-md-3">
              <div class="form-group">
                <label>SI Cutoff</label>
                <input type="text" class="form-control" name="si_cutoff_at" id="si_cutoff_at" placeholder="YYYY-MM-DD HH:MM:SS">
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label>Empty Pickup Date</label>
                <input type="text" class="form-control" name="empty_pickup_at" id="empty_pickup_at" placeholder="YYYY-MM-DD HH:MM:SS">
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label>Pickup Depot</label>
                <input type="text" class="form-control" name="pickup_depot" id="pickup_depot" placeholder="Depot / Empty Pickup CY">
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label>Full Return CY</label>
                <input type="text" class="form-control" name="full_return_cy" id="full_return_cy" placeholder="Full return CY / terminal">
              </div>
            </div>
          </div>
          
          <div class="section-title">
            <i class="fa fa-building"></i> Stuffing Plan
          </div>

          <!-- Basic Row: Core Stuffing Data -->
          <div class="row row-spacing">
            <div class="col-md-4">
              <div class="form-group">
                <label>Stuffing Location / Warehouse <span class="text-danger">*</span></label>
                <div class="input-group">
                  <span class="input-group-addon"><i class="fa fa-building-o"></i></span>
                  <select class="form-control select2-warehouse" name="stuffing_location" id="stuffing_location" required></select>
                </div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Stuffing Date <span class="text-danger">*</span></label>
                <div class="input-group date">
                  <span class="input-group-addon"><i class="fa fa-cubes"></i></span>
                  <input type="text" class="form-control datepicker" name="stuffing_date" id="stuffing_date" readonly placeholder="Select stuffing date" required>
                </div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Closing Date <span class="text-danger">*</span></label>
                <div class="input-group date">
                  <span class="input-group-addon"><i class="fa fa-calendar-times-o"></i></span>
                  <input type="text" class="form-control datepicker" name="stf_cls" id="stf_cls" readonly placeholder="Select closing date" required>
                </div>
              </div>
            </div>
          </div>

          <!-- Advanced Section: Cargo & DG -->
          <div class="advanced-field">
            <div class="section-title">
              <i class="fa fa-exclamation-triangle"></i> Cargo & Dangerous Goods
            </div>

            <div class="row row-spacing">
              <div class="col-md-4">
                <div class="form-group">
                  <label>Commodity</label>
                  <input type="text" class="form-control" name="commodity" id="commodity" placeholder="Commodity from DO">
                </div>
              </div>
              <div class="col-md-2">
                <div class="form-group">
                  <label>Gross Weight</label>
                  <input type="text" class="form-control" name="gross_weight" id="gross_weight" placeholder="18,000 KGS">
                </div>
              </div>
              <div class="col-md-2">
                <div class="form-group">
                  <label>Cargo Type</label>
                  <select class="form-control" name="cargo_type" id="cargo_type">
                    <option value="GENERAL">GENERAL</option>
                    <option value="DG">DG</option>
                    <option value="REEFER">REEFER</option>
                    <option value="OOG">OOG</option>
                  </select>
                </div>
              </div>
              <div class="col-md-2">
                <div class="form-group">
                  <label>DG?</label>
                  <select class="form-control" name="is_dg" id="is_dg">
                    <option value="0">No</option>
                    <option value="1">Yes</option>
                  </select>
                </div>
              </div>
              <div class="col-md-2">
                <div class="form-group">
                  <label>Schedule Type</label>
                  <input type="text" class="form-control" name="schedule_kind_hint" id="schedule_kind_hint" readonly placeholder="DIRECT/VIA">
                </div>
              </div>
            </div>

            <div class="row row-spacing">
              <div class="col-md-6">
                <div class="form-group">
                  <label>DG Details</label>
                  <textarea class="form-control" name="dg_details" id="dg_details" rows="2" placeholder="UN / IMO / Class / DG remarks"></textarea>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label>Remarks</label>
                  <textarea class="form-control" name="remarks" id="remarks" rows="2" placeholder="Remarks from DO"></textarea>
                </div>
              </div>
            </div>
          </div>

          <!-- Dynamic Panel -->
          <div id="bookingPanel">
            <div class="section-title">
              <i class="fa fa-list"></i> BC Terkait Schedule Ini
            </div>
            <div class="table-responsive">
              <table class="table table-bordered table-condensed">
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
                <tbody id="bookingListBody">
                  <tr>
                    <td colspan="5" class="text-center text-muted" style="padding: 16px;">Belum ada data booking.</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div id="sendBcWrapper">
              <label style="text-transform:none; letter-spacing:0; margin-bottom:0; font-size: 13px;">
                <input type="checkbox" name="send_bc_update" id="send_bc_update" value="1" style="margin-right: 6px;">
                Kirim update Booking Confirmation terbaru ke shipper setelah schedule disimpan.
              </label>
              <p class="help-block">
               Perhatian, semua BC dan job terkait schedule ini otomatis terupdate sesuai dengan data diatas
              </p>
            </div>
          </div>

          <div class="advanced-field">
            <div class="section-title">
              <i class="fa fa-align-left"></i> Raw OCR Preview
            </div>
            <textarea id="raw_ocr_preview" class="form-control" readonly placeholder="Hasil OCR akan tampil di sini setelah Extract DO." style="background: #f1f5f9; font-family: monospace;"></textarea>
          </div>

        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-default pull-left" data-dismiss="modal" style="border-radius: 8px; font-weight: 600;">
            <i class="fa fa-times"></i> Close
          </button>
          <button type="submit" class="btn btn-primary" id="btnSaveSchedule" style="border-radius: 8px; font-weight: 600; padding: 8px 24px;">
            <i class="fa fa-save"></i> Save Schedule
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<script type="text/javascript">
var baseURL = '<?= site_url() ?>';


(function ($) {
  'use strict';

  var endpoints = {
    get: '<?php echo $baseUrl; ?>schedule/ScheduleDirect/getFormData',
    save: '<?php echo $baseUrl; ?>schedule/ScheduleDirect/saveForm',
    extract: '<?php echo $baseUrl; ?>schedule/ScheduleDirect/extractDo',
    remove: '<?php echo $baseUrl; ?>schedule/ScheduleDirect/deleteScheduleDirect'
  };

  var modal = $('#modal-default');
  var form = $('#scheduleDirectForm');

  // Form Toggle Logic
  $('#formModeToggle').on('change', function() {
      if($(this).is(':checked')) {
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
    return String(value == null ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function showPageAlert(type, message) {
    var el = $('#pageAlert');
    el.removeClass('alert-success alert-danger alert-warning alert-info')
      .addClass('alert-' + type)
      .html(message)
      .show();

    $('html, body').animate({ scrollTop: el.offset().top - 80 }, 250);
  }

  function showFormAlert(type, message) {
    $('#formAlert')
      .removeClass('alert-success alert-danger alert-warning alert-info')
      .addClass('alert-' + type)
      .html(message)
      .show();
  }

  function hideFormAlert() {
    $('#formAlert').hide().html('');
  }

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
    if (id == null || id === '') {
      el.val(null).trigger('change');
      return;
    }

    text = text || id;

    var existingOption = el.find('option[value="' + String(id).replace(/"/g, '\\"') + '"]');
    if (existingOption.length === 0) {
      var option = new Option(text, id, true, true);
      el.append(option);
    }

    el.val(id).trigger('change');
  }

  function clearSelect(selector) {
    $(selector).val(null).trigger('change');
  }

  function resetForm() {
    form[0].reset();
    form.find('.missing-field').removeClass('missing-field');

    $('#schedule_id').val('');
    $('#formTitle').text('New Schedule Direct');
    $('#do_raw_text').val('');
    $('#do_extracted_json').val('');
    $('#raw_ocr_preview').val('');
    $('#ocr_status').val('Belum extract');
    $('#bookingPanel').hide();
    $('#bookingListBody').html('<tr><td colspan="5" class="text-center text-muted">Belum ada data booking.</td></tr>');
    $('#sendBcWrapper').hide();
    $('#send_bc_update').prop('checked', false);
    hideFormAlert();

    clearSelect('#city_id');
    clearSelect('#liner_id');
    clearSelect('#stuffing_location');

    $('#form_month').val('<?php echo html_escape($currentMonthKey); ?>');
    $('#form_year').val('<?php echo html_escape($currentYear); ?>');

    // Reset toggle to basic on open
    $('#formModeToggle').prop('checked', false).trigger('change');
  }

  function initMonthFilter() {
    if ($.fn.combodate && $('#month').length) {
      $('#month').combodate({
        value: '<?php echo html_escape($currentMonthKey); ?>',
        customClass: 'form-control'
      });
    }
  }

  function initDatepicker() {
    $('.datepicker').datepicker({
      autoclose: true,
      format: 'yyyy-mm-dd',
      todayHighlight: true
    });
  }

  function initDataTable() {
    if ($.fn.DataTable) {
      $('.schedule-direct-table').DataTable({
        pageLength: 25,
        ordering: true,
        autoWidth: false
      });
    }
  }

  function initSelect2() {
	 
	 $('#city_id').select2({
			width: '100%',
			dropdownParent: $('#modal-default'),
			placeholder: 'PILIH PORT OF DESTINATION',
			minimumInputLength: 2,
			ajax: {
				url: baseURL + 'schedule/ScheduleDirect/searchDestinations',
				dataType: 'json',
				delay: 300,
				data: function (params) {
					return { q: (params.term || '').toUpperCase() };
				},
				processResults: function (data) {
					return { results: data.results || [] };
				}
			}
		});

		$('#liner_id').select2({
			width: '100%',
			dropdownParent: $('#modal-default'),
			placeholder: 'PILIH CARRIER / LINER',
			minimumInputLength: 1,
			ajax: {
				url: baseURL + 'schedule/ScheduleDirect/searchLiners',
				dataType: 'json',
				delay: 300,
				data: function (params) {
					return { q: (params.term || '').toUpperCase() };
				},
				 processResults: function (data) {
					  return {
						results: $.map(data.results || [], function (obj) {
						  return {
							id: obj.id,
							text: obj.text,
							name: obj.text || ''
						  };
						})
					  };
					}

		  },
		  escapeMarkup: function (markup) { return markup; },
		  templateResult: function (repo) {
		  if (repo.loading) return repo.text;
		  return '<div style="padding:4px 0;">' +
				   '<div style="font-weight:600;color:#1e293b;">' + escapeHtml(repo.id || '-') + '</div>' +
				   '<div style="font-size:12px;color:#64748b;">' + escapeHtml(repo.name || '') + '</div>' +
				 '</div>';
		},
		templateSelection: function (repo) {
		  return repo.id || repo.text|| '';
		}
			
		});
    

    $('.select2-warehouse').select2({
      dropdownParent: modal,
      placeholder: 'Search warehouse...',
      minimumInputLength: 1,
      ajax: {
        url: '<?php echo $baseUrl; ?>afg_c_dropdown/dropdown_warehouse_a',
        type: 'POST',
        dataType: 'json',
        delay: 250,
        data: function (params) {
          return { q: params.term };
        },
        processResults: function (data) {
          return {
            results: $.map(data || [], function (obj) {
              return {
                id: obj.warehouse_code,
                text: obj.warehouse_code,
                name: obj.warehouse_name || ''
              };
            })
          };
        }
      },
      escapeMarkup: function (markup) { return markup; },
      templateResult: function (repo) {
        if (repo.loading) return repo.text;
        return '<div style="padding:4px 0;">' +
                 '<div style="font-weight:600;color:#1e293b;">' + escapeHtml(repo.id || repo.text || '-') + '</div>' +
                 '<div style="font-size:12px;color:#64748b;">' + escapeHtml(repo.name || '') + '</div>' +
               '</div>';
      },
      templateSelection: function (repo) {
        return repo.id || repo.text || '';
      }
    });

    
  }

  function openNewForm() {
    resetForm();
    $('#formTitle').text('New Schedule Direct');
    modal.modal('show');
  }

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
    $('#booking_number').val(schedule.booking_number || '');
    $('#container_type').val(schedule.container_type || '');
    $('#si_no').val(schedule.si_no || '');
    $('#do_date').val(schedule.do_date || '');
    $('#pol').val(schedule.pol || '');
    $('#pod').val(schedule.pod || '');
    $('#final_destination').val(schedule.final_destination || '');
    $('#transit_port').val(schedule.transit_port || '');
    $('#transit_terminal').val(schedule.transit_terminal || '');
    $('#terminal').val(schedule.terminal || '');
    $('#open_stack_at').val(schedule.open_stack_at || '');
    $('#cargo_closing_at').val(schedule.cargo_closing_at || '');
    $('#doc_closing_at').val(schedule.doc_closing_at || '');
    $('#vgm_cutoff_at').val(schedule.vgm_cutoff_at || '');
    $('#si_cutoff_at').val(schedule.si_cutoff_at || '');
    $('#empty_pickup_at').val(schedule.empty_pickup_at || '');
    $('#pickup_depot').val(schedule.pickup_depot || '');
    $('#full_return_cy').val(schedule.full_return_cy || '');
    $('#container_qty').val(schedule.container_qty || '');
    $('#container_type_raw').val(schedule.container_type_raw || '');
    $('#commodity').val(schedule.commodity || '');
    $('#gross_weight').val(schedule.gross_weight || '');
    $('#cargo_type').val(schedule.cargo_type || '');
    $('#is_dg').val(schedule.is_dg || '');
    $('#dg_details').val(schedule.dg_details || '');
    $('#remarks').val(schedule.remarks || '');
    $('#schedule_kind_hint').val(schedule.schedule_kind_hint || '');
    $('#vessel').val(schedule.vessel || '');
    $('#voy').val(schedule.voy || '');
    $('#stuffing_date').val(schedule.stuffing_date || '');
    $('#stf_cls').val(schedule.stf_cls || '');
    $('#etd_jkt').val(schedule.etd_jkt || '');
    $('#eta').val(schedule.eta || '');

    if (schedule.etd_jkt && schedule.etd_jkt.length >= 7) {
      $('#form_month').val(schedule.etd_jkt.substring(5, 7));
    }

    setSelectValue('#city_id', schedule.city_id, schedule.city_id);
    setSelectValue('#liner_id', schedule.carrier || schedule.liner_id, schedule.carrier || schedule.liner_id);
    setSelectValue('#stuffing_location', schedule.stuffing_location, schedule.stuffing_location);

    if (schedule.do_raw_text) {
      $('#do_raw_text').val(schedule.do_raw_text);
      $('#raw_ocr_preview').val(schedule.do_raw_text);
      $('#ocr_status').val('OCR pernah tersimpan');
    }

    if (schedule.do_extracted_json) {
      $('#do_extracted_json').val(schedule.do_extracted_json);
    }
  }

  function openEditForm(id) {
    resetForm();
    $('#formTitle').text('Edit Schedule Direct');
    $('#bookingPanel').show();
    modal.modal('show');
    setLoading(true);

    $.ajax({
      url: endpoints.get,
      type: 'GET',
      dataType: 'json',
      data: { id: id }
    }).done(function (res) {
      if (!res || !res.status) {
        showFormAlert('danger', escapeHtml((res && res.message) || 'Gagal mengambil data schedule.'));
        return;
      }

      fillForm(res.data.schedule);
      renderBookings(res.data.bookings || []);
    }).fail(function (xhr) {
      showFormAlert('danger', 'Gagal mengambil data schedule. HTTP ' + xhr.status);
    }).always(function () {
      setLoading(false);
    });
  }
  
  function setMasterSelect($select, value) {
		value = (value || '').toString().trim().toUpperCase();

		if (!value) {
			$select.val('').trigger('change');
			return;
		}

		var matchedValue = '';

		$select.find('option').each(function () {
			var optValue = ($(this).val() || '').toString().trim().toUpperCase();
			var optText  = ($(this).text() || '').toString().trim().toUpperCase();

			if (
				optValue === value ||
				optText === value ||
				optText.indexOf(value) >= 0 ||
				value.indexOf(optValue) >= 0
			) {
				matchedValue = $(this).val();
				return false;
			}
		});

		if (matchedValue) {
			$select.val(matchedValue).trigger('change');
		} else {
			$select.val('').trigger('change');
			console.warn('Master option tidak ditemukan:', value);
		}
	}

  function applyOcrFields(fields, missingFields) {
    fields = fields || {};
    missingFields = missingFields || [];

    form.find('.missing-field').removeClass('missing-field');

    if (fields.booking_number) $('#booking_number').val(fields.booking_number);
    if (fields.container_type) $('#container_type').val(fields.container_type);
    if (fields.si_no !== undefined) $('#si_no').val(fields.si_no);
    if (fields.do_date !== undefined) $('#do_date').val(fields.do_date);
    if (fields.pol !== undefined) $('#pol').val(fields.pol);
    if (fields.pod !== undefined) $('#pod').val(fields.pod);
    if (fields.final_destination !== undefined) $('#final_destination').val(fields.final_destination);
    if (fields.transit_port !== undefined) $('#transit_port').val(fields.transit_port);
    if (fields.transit_terminal !== undefined) $('#transit_terminal').val(fields.transit_terminal);
    if (fields.terminal !== undefined) $('#terminal').val(fields.terminal);
    if (fields.open_stack_at !== undefined) $('#open_stack_at').val(fields.open_stack_at);
    if (fields.cargo_closing_at !== undefined) $('#cargo_closing_at').val(fields.cargo_closing_at);
    if (fields.doc_closing_at !== undefined) $('#doc_closing_at').val(fields.doc_closing_at);
    if (fields.vgm_cutoff_at !== undefined) $('#vgm_cutoff_at').val(fields.vgm_cutoff_at);
    if (fields.si_cutoff_at !== undefined) $('#si_cutoff_at').val(fields.si_cutoff_at);
    if (fields.empty_pickup_at !== undefined) $('#empty_pickup_at').val(fields.empty_pickup_at);
    if (fields.pickup_depot !== undefined) $('#pickup_depot').val(fields.pickup_depot);
    if (fields.full_return_cy !== undefined) $('#full_return_cy').val(fields.full_return_cy);
    if (fields.container_qty !== undefined) $('#container_qty').val(fields.container_qty);
    if (fields.container_type_raw !== undefined) $('#container_type_raw').val(fields.container_type_raw);
    if (fields.commodity !== undefined) $('#commodity').val(fields.commodity);
    if (fields.gross_weight !== undefined) $('#gross_weight').val(fields.gross_weight);
    if (fields.cargo_type !== undefined) $('#cargo_type').val(fields.cargo_type);
    if (fields.is_dg !== undefined) $('#is_dg').val(fields.is_dg);
    if (fields.dg_details !== undefined) $('#dg_details').val(fields.dg_details);
    if (fields.remarks !== undefined) $('#remarks').val(fields.remarks);
    if (fields.schedule_kind_hint !== undefined) $('#schedule_kind_hint').val(fields.schedule_kind_hint);
    if (fields.vessel) $('#vessel').val(fields.vessel);
    if (fields.voy) $('#voy').val(fields.voy);
    if (fields.stuffing_date) $('#stuffing_date').val(fields.stuffing_date);
    if (fields.stf_cls) $('#stf_cls').val(fields.stf_cls);
    if (fields.etd_jkt) $('#etd_jkt').val(fields.etd_jkt);
    if (fields.eta) $('#eta').val(fields.eta);

    if (fields.liner_id) {
      setSelectValue('#liner_id', fields.liner_id, fields.liner_id);
    }

	  if (fields.pod) {
		setMasterSelect($('#city_id'), fields.pod);
	}

    $.each(missingFields, function (_, name) {
      $('[name="' + name + '"]').addClass('missing-field');
    });
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
    }).done(function (res) {
      if (!res || !res.status) {
        $('#ocr_status').val('Gagal OCR');
        showFormAlert('danger', escapeHtml((res && res.message) || 'OCR gagal.'));
        return;
      }

      $('#ocr_status').val('OCR selesai');
      $('#do_raw_text').val(res.raw_text || '');
      $('#do_extracted_json').val(JSON.stringify(res.fields || {}));
      $('#do_file_token').val(res.file_token || '');
      $('#raw_ocr_preview').val(res.raw_text || '');

      applyOcrFields(res.fields || {}, res.missing_fields || []);

      var missing = res.missing_fields || [];
      if (missing.length > 0) {
        showFormAlert('warning', 'OCR selesai, tapi field berikut belum terbaca: <b>' + escapeHtml(missing.join(', ')) + '</b>. Lengkapi manual sebelum save.');
      } else {
        showFormAlert('success', 'OCR selesai. Silakan review data sebelum save.');
      }
    }).fail(function (xhr) {
      $('#ocr_status').val('Gagal OCR');
      showFormAlert('danger', 'OCR gagal. HTTP ' + xhr.status + '. Pastikan endpoint extractDo dan Tesseract sudah siap.');
    }).always(function () {
      setLoading(false);
    });
  }

  function saveForm(e) {
    e.preventDefault();

    if (!form[0].checkValidity()) {
      showFormAlert('warning', 'Lengkapi field wajib terlebih dahulu.');
      return;
    }

    var fd = new FormData(form[0]);

    setLoading(true);
    hideFormAlert();

    $.ajax({
      url: endpoints.save,
      type: 'POST',
      dataType: 'json',
      data: fd,
      processData: false,
      contentType: false
    }).done(function (res) {
      if (!res || !res.status) {
        showFormAlert('danger', escapeHtml((res && res.message) || 'Save gagal.'));
        return;
      }

      modal.modal('hide');

      var msg = escapeHtml(res.message || 'Schedule berhasil disimpan.');
      if (res.booking_count && parseInt(res.booking_count, 10) > 0) {
        msg += '<br>Booking terkait: <b>' + parseInt(res.booking_count, 10) + '</b>';
      }

      showPageAlert('success', msg);

      setTimeout(function () {
        window.location.reload();
      }, 900);
    }).fail(function (xhr) {
      var msg = 'Save gagal. HTTP ' + xhr.status;
      if (xhr.responseJSON && xhr.responseJSON.message) {
        msg += ': ' + xhr.responseJSON.message;
      }
      showFormAlert('danger', escapeHtml(msg));
    }).always(function () {
      setLoading(false);
    });
  }

  function deleteSchedule(id, btn) {
    if (!confirm('Are you sure to delete this Schedule Direct?')) {
      return;
    }

    btn.prop('disabled', true);

    $.ajax({
      url: endpoints.remove,
      type: 'POST',
      dataType: 'json',
      data: { id: id }
    }).done(function (res) {
      if (res && res.status === true) {
        btn.closest('tr').remove();
        showPageAlert('success', 'Schedule Direct successfully deleted.');
      } else {
        showPageAlert('danger', escapeHtml((res && res.message) || 'Schedule Direct deletion failed.'));
      }
    }).fail(function (xhr) {
      showPageAlert('danger', 'Delete gagal. HTTP ' + xhr.status);
    }).always(function () {
      btn.prop('disabled', false);
    });
  }

  function bindEvents() {
    $('#btnAddSchedule').on('click', function () {
      openNewForm();
    });

    $(document).on('click', '.btn-edit-schedule', function () {
      openEditForm($(this).data('id'));
    });

    $(document).on('click', '.deleteScheduleDirect', function () {
      deleteSchedule($(this).data('id'), $(this));
    });

    $('#btnExtractDo').on('click', extractDo);

    form.on('submit', saveForm);

    modal.on('hidden.bs.modal', function () {
      resetForm();
    });
  }

  $(function () {
    var windowURL = window.location.href;
    var pageURL = windowURL.substring(0, windowURL.lastIndexOf('/'));
    var x = $('a[href="' + pageURL + '"]');
    x.addClass('active');
    x.parent().addClass('active');
    var y = $('a[href="' + windowURL + '"]');
    y.addClass('active');
    y.parent().addClass('active');

    initMonthFilter();
    initDatepicker();
    initSelect2();
    initDataTable();
    bindEvents();
  });

})(jQuery);

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

    $form.find('select').each(function () {
        var $el = $(this);
        var val = $el.val();

        if (typeof val === 'string' && val !== '') {
            var upper = val.toUpperCase();
            if ($el.find('option[value="' + upper.replace(/"/g, '\\"') + '"]').length === 0) {
                $el.append(new Option(upper, upper, true, true));
            }
            $el.val(upper).trigger('change');
        }
    });
}

$(document).on('input', '#modal-default input[type="text"], #modal-default textarea', function () {
    var name = $(this).attr('name') || '';

    if (
        name === 'do_raw_text' ||
        name === 'do_extracted_json' ||
        name === 'do_file_token'
    ) {
        return;
    }

    var start = this.selectionStart;
    var end   = this.selectionEnd;

    this.value = this.value.toUpperCase();

    if (typeof this.setSelectionRange === 'function') {
        this.setSelectionRange(start, end);
    }
});

$(document).on('submit', '#scheduleDirectForm', function () {
    upperCaseScheduleForm($(this));
});
</script>