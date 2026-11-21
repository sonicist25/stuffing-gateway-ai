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
         Schedule Via Import Management
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
                      <form role="form"  action="<?php echo base_url() ?>/schedule/schedulesVia/scheduleViaListingImport" method="post" >


                        <div class="col-md-1">
                            <div class="form-group">
                              <label>Month</label>
                              <input type="text" id="month" data-format="MM" data-template="MMM" name="month_via" value="<?php echo $this->session->userdata('month_via'); ?>">
                            </div>
                          </div>
                        <div class="col-md-1">
                            <div class="form-group">
                              <label>year</label>
                              <input type="text"  id="year" data-format="YYYY" data-template="YYYY" name="year_via" value="<?php echo $this->session->userdata('year_via'); ?>">
                            </div>
                          </div>
                          <div class="col-md-1">
                          <div class="form-group">
                            <label>&nbsp;</label>
                            <br/>
                            <input type="submit" class="btn btn-primary" value="Cari">
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
                  <?php if(empty($scheduleViaRecordsImport)) { ?>
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
                    <div class="panel-heading text-center">Schedule Via Import</div>
                    <div class="panel-body">
                    <div class="row">
                        <div class="col-md-12">
                          <!-- START CUSTOM TABS -->
                          <h2 class="page-header bold">
                            <?php $month_via = $this->session->userdata('month_via');
                            if($month_via == 01){
                              echo 'Januari';
                            }elseif($month_via == '02'){
                              echo 'Februari';
                            }elseif($month_via == '03'){
                              echo 'Maret';
                            }elseif($month_via == '04'){
                              echo 'April';
                            }elseif($month_via == '05'){
                              echo 'Mei';
                            }elseif($month_via == '06'){
                              echo 'Juni';
                            }elseif($month_via == '07'){
                              echo 'Juli';
                            }elseif($month_via == '08'){
                              echo 'Agustus';
                            }elseif($month_via == '09'){
                              echo 'September';
                            }elseif($month_via == 10){
                              echo 'Oktober';
                            }elseif($month_via == 11){
                              echo 'November';
                            }elseif($month_via == 12){
                              echo 'Desember';
                            }

                          echo ' '. $this->session->userdata('year'); ?> </h2>

                          <div class="row">
                            <div class="col-md-12">
                              <!-- Custom Tabs -->
                              <div class="nav-tabs-custom">
                                <ul class="nav nav-tabs">
                                  <?php
                                  if(!empty($scheduleViaRecordsImport))
                                  {

                                  foreach($scheduleViaRecordsImport as $byCity)
                                  if (!empty($byCity->schedule_via_listing)){
                                  { ?>
                                  <li><a href="#tab_<?php echo $byCity->id_city ?>" data-toggle="tab"><?php echo $byCity->city_name ?></a></li>
                                <?php } } }?>
                                </ul>
                                <div class="tab-content">
                                  <?php
                                  if(!empty($scheduleViaRecordsImport))
                                  {

                                  foreach ($scheduleViaRecordsImport as $city) { ?>
                                  <div class="tab-pane" id="tab_<?php echo $city->id_city ?>">
                                    <table id="scheduleVia" class="table table-bordered table-striped">
                                      <thead>
                                        <tr>
                                          <th>Vessel</th>
                                         <th>Voy Vessel</th>
                                         <th>Cut Off</th>
                                         <th>Etd <?php echo $this->session->userdata('city_code'); ?></th>
                                         <th>Connecting Port</th>
                                         <th>Eta JKT </th>
                                          <th width="5%" class="text-center">action</th>
                                        </tr>
                                      </thead>
                                      <tbody>
                                        <?php
                                          if(!empty($city->schedule_via_listing))
                                          {

                                              foreach($city->schedule_via_listing as $record)
                                              {

                                        ?>
                                        <script>
                                        $(document).ready(function() {
                                          $('#vessel<?php echo $record->id; ?>').editable({
                                            type: 'text',
                                            name: 'vessel',
                                            url: window.location.origin+ '/schedule/ScheduleVia/updateScheduleViaImport',
                                            title: 'Enter Vessel'
                                          });

                                          $('#voy_vessel<?php echo $record->id; ?>').editable({
                                            type: 'text',
                                            name: 'voy_vessel',
                                             url: window.location.origin+ '/schedule/ScheduleVia/updateScheduleViaImport',
                                            title: 'Enter Vessel'
                                          });

                                            $('#stf_cls<?php echo $record->id; ?>').editable({
                                                type: 'date',
                                                name: 'stf_cls',
                                                 url: window.location.origin+ '/schedule/ScheduleVia/updateScheduleViaImport',
                                                format: 'yyyy-mm-dd',
                                                viewformat: 'dd M',
                                                datepicker: {
                                                  weekStart: 1,
                                                }
                                            });


                                            $('#etd_jkt<?php echo $record->id; ?>').editable({
                                              type: 'date',
                                              name: 'etd_jkt',
                                               url: window.location.origin+ '/schedule/ScheduleVia/updateScheduleViaImport',
                                              format: 'yyyy-mm-dd',
                                              viewformat: 'dd M',
                                              datepicker: {
                                              weekStart: 1,
                                              }

                                            });

                                            $('#connecting<?php echo $record->id; ?>').editable({
                                              type: 'text',
                                              name: 'connecting',
                                               url: window.location.origin+ '/schedule/ScheduleVia/updateScheduleViaImport',
                                              title: 'Enter Connecting'
                                            });


                                            $('#voy_con<?php echo $record->id; ?>').editable({
                                              type: 'text',
                                              name: 'voy_con',
                                               url: window.location.origin+ '/schedule/ScheduleVia/updateScheduleViaImport',
                                              title: 'Enter Voy Connecting'
                                            });

                                            $('#eta<?php echo $record->id; ?>').editable({
                                              type: 'date',
                                              name: 'eta',
                                               url: window.location.origin+ '/schedule/ScheduleVia/updateScheduleViaImport',
                                              format: 'yyyy-mm-dd',
                                              viewformat: 'dd M',
                                              datepicker: {
                                              weekStart: 1,
                                              }

                                            });


                                            $('#etd_con<?php echo $record->id; ?>').editable({
                                              type: 'date',
                                              name: 'etd_con',
                                               url: window.location.origin+ '/schedule/ScheduleVia/updateScheduleViaImport',
                                              format: 'yyyy-mm-dd',
                                              viewformat: 'dd M',
                                              datepicker: {
                                              weekStart: 1,
                                              }

                                            });

                                            $('#etd_city_con_id<?php echo $record->id; ?>').editable({
                                                source: window.location.origin+ '/schedule/ScheduleVia/getCities',
                                                name: 'etd_city_con_id',
                                                 url: window.location.origin+ '/schedule/ScheduleVia/updateScheduleViaImport',

                                            });

                                        });
                                        </script>
                                        <tr>
                                          <td><a href="#" data-name="vessel" id="vessel<?php echo $record->id; ?>" data-pk="<?php echo $record->id ?>"><?php echo $record->vessel ?></a></td>
                                          <td><a href="#" id="voy_vessel<?php echo $record->id; ?>" data-pk="<?php echo $record->id ?>"><?php echo $record->voy_vessel ?></a></td>
                                          <td><a href="#" id="stf_cls<?php echo $record->id; ?>"  data-pk="<?php echo $record->id ?>" data-value="<?php echo $record->cut_off ?>"></a></td>
                                          <td><a href="#" id="etd_jkt<?php echo $record->id; ?>" data-pk="<?php echo $record->id ?>" data-value="<?php echo $record->etd ?>"></a></td>
                                          <td><a href="#" id="connecting<?php echo $record->id; ?>" data-pk="<?php echo $record->id ?>"><?php echo $record->eta_con ?></a></td>
                                          <td><a href="#" id="voy_con<?php echo $record->id; ?>" data-pk="<?php echo $record->id ?>"><?php echo $record->eta_jkt ?></a></td>
                                          <td><a href="#" id="etd_city_con_id<?php echo $record->id; ?>" data-type="select" data-pk="<?php echo $record->id ?>" data-value="<?php echo $record->etd_city_con_id ?>"><?php echo $record->city_name ?></a></td>
                                          <td><a href="#" id="eta<?php echo $record->id; ?>" data-pk="<?php echo $record->id ?>" data-value="<?php echo $record->city_id ?>"></a></td>

                                          <td class="text-center">
                                              <a class="btn btn-sm btn-danger deleteScheduleVia" href="#" data-id="<?php echo $record->id; ?>" title="Delete"><i class="fa fa-trash"></i></a>
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
    <form role="form" id="scheduleVia" action="<?php echo base_url() ?>schedule/ScheduleVia/createScheduleViaImport" method="post" >
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">New Data Schedule Via Import</h4>
      </div>
      <div class="modal-body">

          <div class="box">
            <div class="box-header with-border">
              <h3 class="box-title">
                <div class="row">
                  <div class="col-md-4">
                    <input type="hidden" name="month" value="<?php echo $this->session->userdata('month'); ?>">
                    <input type="hidden" name="year" value="<?php echo $this->session->userdata('year'); ?>">
                    <input type="text" value="<?php
                    $month_via = $this->session->userdata('month');
                    if($month_via == 1){
                      echo 'Januari';
                    }elseif($month_via == 2){
                      echo 'Februari';
                    }elseif($month_via == 3){
                      echo 'Maret';
                    }elseif($month_via == 4){
                      echo 'April';
                    }elseif($month_via == 5){
                      echo 'Mei';
                    }elseif($month_via == 6){
                      echo 'Juni';
                    }elseif($month_via == 7){
                      echo 'Juli';
                    }elseif($month_via == 8){
                      echo 'Agustus';
                    }elseif($month_via == 9){
                      echo 'September';
                    }elseif($month_via == 10){
                      echo 'Oktober';
                    }elseif($month_via == 11){
                      echo 'November';
                    }elseif($month_via == 12){
                      echo 'Desember';
                    }
                     ?>"  disabled class="no-border bold">
                  </div>
                  <div class="col-md-4">
                    <input type="text" value="<?php echo $this->session->userdata('year'); ?>" disabled class="no-border bold" >
                  </div>
                </div>
              </h3>
            </div>
            <div class="box-body">
              <div class="row">
                <div class="col-xs-6">
                  <label> Vessel</label>
                  <input type="text" class="form-control" name="vessel">
                </div>
                <div class="col-xs-6">
                  <label> Voy Vessel</label>
                  <input type="text" class="form-control" name="voy_vessel">
                </div>
              </div>

              <div class="row">
                <div class="col-xs-6">
                  <label> STF / CLS</label>
                  <input type="text" class="form-control datepicker"  name="stf_cls" readonly="readonly">
                </div>
                <div class="col-xs-6">
                  <label> ETD</label>
                  <input type="text" class="form-control datepicker"  name="etd_jkt" readonly="readonly">
                </div>

              </div>

              <div class="row">
                <div class="col-xs-6">
                  <label> Connecting</label>
                  <input type="text" class="form-control" name="connecting">
                </div>
                <div class="col-xs-6">
                  <label> Voy Connecting</label>
                  <input type="text" class="form-control" name="voy_con">
                </div>
              </div>

              <div class="row">
                <div class="col-xs-6">
                  <label> ETD Connecting</label>
                  <input type="text" class="form-control datepicker" name="etd_con" readonly="readonly">
                </div>
                <div class="col-xs-6">
                  <label> ETD City Connecting</label>
                  <select class="form-control select2" style="width: 100%;" name="etd_city_con_id">
                    <?php
                      if(!empty($cities))
                      {
                          foreach($cities as $record)
                          {
                    ?>
                    <option
                      value="<?php echo $record->id_city ?>"><?php echo $record->city_name ?></option>
                    <?php
                        }
                    }
                    ?>
                  </select>
                </div>
              </div>
              <div class="row">
                <div class="col-xs-6">
                  <label> ETA  JAKARTA</label>
                  <input type="text" class="form-control datepicker" name="eta" readonly="readonly">
                </div>
                <div class="col-xs-6">
                  <label> ETA City</label>

                  <select class="form-control select2" style="width: 100%;" name="eta_city_id">
                    <?php
                      if(!empty($cities))
                      {
                          foreach($cities as $record)
                          {
                    ?>
                    <option
                      value="<?php echo $record->id_city ?>"><?php echo $record->city_name ?></option>
                    <?php
                        }
                    }
                    ?>
                  </select>
                </div>
              </div>
            </div>
            <!-- /.box-body -->


          </div>
          <!-- /.box -->

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Close</button>
        <input type="submit" class="btn btn-primary" value="Simpan" />
      </div>
    </div>
    <!-- /.modal-content -->
    </form>
  </div>
  <!-- /.modal-dialog -->
</div>
<!-- /.modal -->
<script type="text/javascript" src="<?php echo base_url(); ?>application/third_party/js/scheduleVia.js" charset="utf-8"></script>
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

      })

      $('.select2').select2()


      $('.datepicker').datepicker({
        autoclose: true,
        format: 'yyyy-mm-dd',
        viewformat: 'dd M',
        maxDate: "+1m"
      }).val();


    </script>

