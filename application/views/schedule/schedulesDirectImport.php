<link rel="stylesheet" type="text/css" href="<?php echo base_url().'assets/css/bootstrap.css'?>">
<link rel="stylesheet" type="text/css" href="<?php echo base_url().'assets/css/bootstrap-datepicker.css'?>">
<link rel="stylesheet" type="text/css" href="<?php echo base_url().'assets/css/jquery.dataTables.css'?>">
<link rel="stylesheet" type="text/css" href="<?php echo base_url().'assets/css/bootstrap-editable.css'?>">
<link rel="stylesheet" type="text/css" href="<?php echo base_url().'assets/css/select2.min.css'?>">
<link rel="stylesheet" type="text/css" href="<?php echo base_url().'assets/css/dataTables.bootstrap.min.css'?>">
<link rel="stylesheet" type="text/css" href="<?php echo base_url().'assets/css/font-awesome.css'?>">
<link rel="stylesheet" type="text/css" href="<?php echo base_url().'assets/css/ionicons.css'?>">
<link rel="stylesheet" type="text/css" href="<?php echo base_url().'application/third_party/css/schedule.css'?>">
<script type="text/javascript" src="<?php echo base_url().'assets/js/jquery.js'?>"></script>
<script type='text/javascript' src="<?php echo base_url().'assets/js/combodate.js'?>"></script>
<script type='text/javascript' src="<?php echo base_url().'assets/js/moment.js'?>"></script>
<script type="text/javascript" src="<?php echo base_url().'assets/js/bootstrap.js'?>"></script>
<script type="text/javascript" src="<?php echo base_url().'assets/js/bootstrap-datepicker.js'?>"></script>
<script type="text/javascript" src="<?php echo base_url().'assets/js/jquery.dataTables.js'?>"></script>
<script type="text/javascript" src="<?php echo base_url().'assets/js/ckeditor.js'?>"></script>
<script type="text/javascript" src="<?php echo base_url().'assets/js/bootstrap-editable.js'?>"></script>
<script type="text/javascript" src="<?php echo base_url().'assets/js/select2.full.min.js'?>"></script>
<script type="text/javascript" src="<?php echo base_url().'assets/js/dataTables.bootstrap.min.js'?>"></script>
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>
         Schedule Direct Import Management
        <small>Add, Edit, Delete</small>
      </h1>
    </section>
    <section class="content">
        <div class="row">
            <div class="col-xs-12 text-right">
                <div class="form-group">

                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-xs-12">
              <div class="box box-info">
                <div class="box-header">
                </div>
                <div class="box-body">
                  <div class="panel panel-info">
                    <div class="panel-heading">Filter Data</div>
                    <div class="panel-body">
                    <div class="row">
                      <form role="form"  action="<?php echo base_url() ?>schedule/ScheduleDirect/scheduleDirectListingImport" method="post" >
                        <div class="col-md-1">
                            <div class="form-group">
                              <label>Month</label>
                              <input type="text" id="month" data-format="MM" data-template="MMM" name="month" value="">
                            </div>
                          </div>
                          <div class="col-md-1">
                          <div class="form-group">
                            <label>&nbsp;&nbsp&nbsp&nbsp&nbsp</label>
                            <br/>
                            <button class="btn btn-primary" type="submit">Cari</button>
                          </div>
                          </div>
                          <div class="col-md-9 text-right">
                              <div class="form-group">
                                <label>&nbsp;</label>
                                <br/>
                                <a data-toggle="modal" title="Add this item" class="mod btn btn-primary" href="#modal-default">Add New</a>
                              </div>
                          </div>
                        </div>

                        </form>
                        </div>
                      </div>
					<!--
                  <?php if(empty($scheduleDirectImportRecords)) { ?>
                  <div class="box-body">
                    <div class="alert alert-danger alert-dismissible">
                      <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                      <h4><i class="icon fa fa-ban"></i> Not Data Result</h4>
                    </div>
                  </div>
                <?php }else{ ?>
                  <div class="box-body">
                    <div class="alert alert-info alert-dismissible">
                      <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                      <h4><i class="icon fa fa-info"></i> Data Result</h4>

                    </div>
                  </div>
                  <?php } ?>-->
                  <div class="panel panel-danger">
                    <div class="panel-heading text-center">Schedule Direct Import</div>
                    <div class="panel-body">
                    <div class="row">
                        <div class="col-md-12">
                          <!-- START CUSTOM TABS -->
                          <h2 class="page-header bold">
                            <?php $month = $this->session->userdata('month');
                          if($month == 01){
                            echo 'Januari';
                          }elseif($month == '02'){
                            echo 'Februari';
                          }elseif($month == '03'){
                            echo 'Maret';
                          }elseif($month == '04'){
                            echo 'April';
                          }elseif($month == '05'){
                            echo 'Mei';
                          }elseif($month == '06'){
                            echo 'Juni';
                          }elseif($month == '07'){
                            echo 'Juli';
                          }elseif($month == '08'){
                            echo 'Agustus';
                          }elseif($month == '09'){
                            echo 'September';
                          }elseif($month == 10){
                            echo 'Oktober';
                          }elseif($month == 11){
                            echo 'November';
                          }elseif($month == 12){
                            echo 'Desember';
                          }

                          echo ' '. $this->session->userdata('db'); ?> </h2>

                          <div class="row">
                            <div class="col-md-12">
                              <!-- Custom Tabs -->
                              <div class="nav-tabs-custom">
                                <ul class="nav nav-tabs">
                                  <?php
                                  if(!empty($scheduleDirectRecordsImport))
                                  {
	
                                  foreach($scheduleDirectRecordsImport as $byCity)
                                  {
                                    if (!empty($byCity->schedule_direct_listing)){
                                    $url = preg_replace('/[^a-zA-Z0-9_-]/', '', $byCity->url_friendly);
                                    ?>
                                  <li><a href="#tab_<?php echo $url ?>" data-toggle="tab"><?php echo $byCity->city_id ?></a></li>
                                <?php } } }
								else{
								echo "";}?>
                                </ul>
                                <div class="tab-content">
                                  <?php
                                  if(!empty($scheduleDirectRecordsImport))
                                  {

                                  foreach ($scheduleDirectRecordsImport as $city) { 
                                  $cityurl = preg_replace('/[^a-zA-Z0-9_-]/', '', $city->url_friendly);?>
                                  <div class="tab-pane
                                  " id="tab_<?php echo $cityurl ?>">
                                    <table id="scheduleDirect" class="table table-bordered table-striped">
                                      <thead>
                                        <tr>
                                          <th>Vessel</th>
                                          <th>Voy</th>
                                          <th>CUT OFF</th>
                                          <th>ETD</th>
                                          <th>ETA <?php echo $this->session->userdata('city_code'); ?></th>
                                          <th width="5%" class="text-center">action</th>
                                        </tr>
                                      </thead>
                                      <tbody>
                                        <?php
                                          if(!empty($city->schedule_direct_listing))
                                          {	
                                              foreach($city->schedule_direct_listing as $record)
                                              {
                                        ?>
                                        <script>
                                        $(document).ready(function() {

                                          $('#vessel<?php echo $record->id; ?>').editable({
                                            type: 'text',
                                            name: 'vessel',
                                            pk: $(this).data('pk'),
                                            url: window.location.origin+ '/schedule/ScheduleDirect/updateScheduleDirectImport',
                                            title: 'Enter Vessel'
                                          });


                                          $('#voy<?php echo $record->id; ?>').editable({
                                            type: 'text',
                                            name: 'voy',
                                            pk: $(this).data('pk'),
                                            url:window.location.origin+'/schedule/ScheduleDirect/updateScheduleDirectImport',
                                            title: 'Enter Voy'
                                          });

                                          $('#etd_jkt<?php echo $record->id; ?>').editable({
                                            type: 'date',
                                            name: 'etd',
                                            url: window.location.origin+'/schedule/ScheduleDirect/updateScheduleDirectImport',
                                            format: 'yyyy-mm-dd',
                                            viewformat: 'dd M',
                                            datepicker: {
                                            weekStart: 1,
                                            }

                                          });
                                            $('#stf_cls<?php echo $record->id; ?>').editable({
                                              type: 'date',
                                              name: 'cut_off',
                                              url: window.location.origin+'/schedule/ScheduleDirect/updateScheduleDirectImport',
                                              format: 'yyyy-mm-dd',
                                              viewformat: 'dd M',
                                              datepicker: {
                                              weekStart: 1,
                                              }


                                            });

                                            $('#eta<?php echo $record->id; ?>').editable({
                                              type: 'date',
                                              name: 'eta_jkt',
                                              url: window.location.origin+'/schedule/ScheduleDirect/updateScheduleDirectImport',
                                              format: 'yyyy-mm-dd',
                                              viewformat: 'dd M',
                                              datepicker: {
                                              weekStart: 1,
                                              }

                                            });
                                        });
                                        </script>
                                        <tr>
                                          <td><a href="#" id="vessel<?php echo $record->id; ?>" data-pk="<?php echo $record->id ?>"><?php echo $record->vessel ?></a></td>
                                          <td><a href="#" id="voy<?php echo $record->id; ?>" data-pk="<?php echo $record->id ?>"><?php echo $record->voy ?></a></td>
                                          <td><a href="#" id="stf_cls<?php echo $record->id; ?>"  data-pk="<?php echo $record->id ?>" data-value="<?php echo $record->cut_off ?>"></a></td>
                                          <td><a href="#" id="etd_jkt<?php echo $record->id; ?>" data-pk="<?php echo $record->id ?>" data-value="<?php echo $record->etd ?>"></a></td>
                                          <td><a href="#" id="eta<?php echo $record->id; ?>" data-pk="<?php echo $record->id ?>" data-value="<?php echo $record->eta_jkt ?>"></a></td>
                                          <td class="text-center">
                                              <a class="btn btn-sm btn-danger deleteScheduleDirectImport" href="#" data-id="<?php echo $record->id; ?>" title="Delete"><i class="fa fa-trash"></i></a>
                                          </td>
                                        </tr>
                                        <?php

                                            }

                                        }
                                        ?>
                                      </tbody>
                                    </table>
                                  </div>
                                  <!-- /.tab-pane -->
                                  <?php  } }?>
                                </div>
                                <!-- /.tab-content -->
                              </div>
                              <!-- nav-tabs-custom -->
                            </div>
                            <!-- /.col -->
                          </div>
                          <!-- /.row -->
                          <!-- END CUSTOM TABS -->

                          </div>
                        </div>
                      </div>
                  </div>
                </div>
                <!-- /.box-body -->
              </div>
              <!-- /.box -->

            </div>
        </div>

    </section>
</div>

<div class="modal fade" id="modal-default">
  <div class="modal-dialog">
    <form role="form" id="scheduleDirect" action="<?php echo base_url() ?>schedule/ScheduleDirect/createScheduleDirectImport" method="post" >
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">New Data Schedule Direct</h4>
      </div>
      <div class="modal-body">

          <div class="box">
            <div class="box-header with-border">
              <h3 class="box-title">
                <div class="row">
                  <div class="col-md-4">
                    <input type="hidden" name="year" value="<?php echo $this->session->userdata('db'); ?>">
                      <select name="month" class="form-control">
                    <?php
                    $month = $this->session->userdata('month'); // Retrieve the current month
                    $months = [
                        1 => 'Januari',
                        2 => 'Februari',
                        3 => 'Maret',
                        4 => 'April',
                        5 => 'Mei',
                        6 => 'Juni',
                        7 => 'Juli',
                        8 => 'Agustus',
                        9 => 'September',
                        10 => 'Oktober',
                        11 => 'November',
                        12 => 'Desember',
                    ];

                    foreach ($months as $key => $value) {
                        // Add 'selected' attribute if the month matches the session value
                        $selected = ($key == $month) ? 'selected' : '';
                        echo "<option value=\"$key\" $selected>$value</option>";
                    }
                    ?>
                    </select>
                  </div>
                  <div class="col-md-4">
                    <input type="text" value="<?php echo $this->session->userdata('db'); ?>" disabled class="no-border bold" >
                  </div>
                </div>
              </h3>
            </div>
            <div class="box-body">
              <div class="row">
                <div class="col-xs-6">
                  <div class="form-group">
                    <label>Origin City</label>
                    <select class="form-control select2" name="city_id" style="width: 100%;">
                   </select>
                   </div>
                </div>
                <div class="col-xs-6">
                  <label> Vessel</label>
                  <input type="text" class="form-control" name="vessel">
                </div>
              </div>
              <hr/>
              <div class="row">
                <div class="col-xs-6">
                  <label> Voyage</label>
                  <input type="text" class="form-control" name="voy">
                </div>
                <div class="col-xs-6">
                  <label> Closing Date</label>
                  <input type="text" class="form-control datepicker"  name="stf_cls" readonly="readonly">
                </div>

              </div>
              <hr/>
              <div class="row">

                <div class="col-xs-6">
                  <label> ETD Origin</label>
                  <input type="text" class="form-control datepicker"  name="etd_jkt" readonly="readonly">
                </div>
                <div class="col-xs-6">
                  <label> ETA Destination</label>
                  <input type="text" class="form-control datepicker"  name="eta" id="eta" readonly="readonly">
                </div>
              </div>
            </div>
            <!-- /.box-body -->

          </div>
          <!-- /.box -->

      </div>
      <div class="modal-footer">
        <button class="btn btn-default pull-left" data-dismiss="modal">Close</button>
		<button class="btn btn-primary" type="submit">Save</button>
      </div>
    </div>
    <!-- /.modal-content -->
    </form>
  </div>
  <!-- /.modal-dialog -->
</div>
<!-- /.modal -->
<script type="text/javascript" src="<?php echo base_url(); ?>application/third_party/js/scheduleDirectImport.js" charset="utf-8"></script>
 <script type="text/javascript">
        var windowURL = window.location.href;
        pageURL = windowURL.substring(0, windowURL.lastIndexOf('/'));
        var x= $('a[href="'+pageURL+'"]');
            x.addClass('active');
            x.parent().addClass('active');
        var y= $('a[href="'+windowURL+'"]');
            y.addClass('active');
            y.parent().addClass('active');
     
      $(function () {
        $('.datatable').DataTable();
        $("[name='city_id']").select2({
        placeholder: 'Search for a city',
        minimumInputLength: 3,
        ajax: {
            url: '/afg_c_stuffing_plan/fetch_dropdown_data',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term // Search term
                };
            },
            processResults: function (data) {
                return {
                    results: data
                };
            }
        }
    });

      })



      $('.datepicker').datepicker({
        autoclose: true,
        format: 'yyyy-mm-dd',
        viewformat: 'dd M',
        maxDate: "+1m"
      }).val();


    </script>