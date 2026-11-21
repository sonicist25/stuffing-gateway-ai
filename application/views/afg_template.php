<?php
define('REGID',$this->session->userdata('region_id'));
$company = 'G C L';
$lang = $this->config->item('language');
$charset = $this->config->item('charset');
$programer = 'Sony Bahtiar';
$nowtahun =  '2025';
$nexttahun =  date('Y')+1;
$tahun =  $nowtahun.'-'.$nexttahun;
if(isset($style)=='') $style = $this->uri->segment(1);
if(isset($script)=='') $script = $this->uri->segment(1);
$restrict = $this->session->userdata('restrict');
$meta = array(
	array('name' => 'Content-type', 'content' => 'text/html; charset='.$charset.'','type' => 'http-equiv'),
	array('name' => 'googlebot', 'content' => 'all,index,follow'),
	array('name' => 'robots', 'content' => 'all,index,follow'),
	array('name' => 'allow-search', 'content' => 'yes'),        
	array('name' => 'generator', 'content' => 'CodeIgniter : '.CI_VERSION),
	array('name' => 'author', 'content' => $programer),
	array('name' => 'copyright', 'content' => $company.' | '.$tahun),        
	array('name' => 'description', 'content' => ""),
	array('name' => 'keywords', 'content' => ""),             
); 
$link_tag = array(
	array('href'=>'favicon.ico', 'rel'=>'shortcut icon', 'type'=>'image/ico'),
	array('href'=>'application/third_party/css/nreset.css', 'rel'=>'stylesheet', 'type'=>'text/css','media'=>'screen'),         
	array('href'=>'application/third_party/css/pro_dropdown.css', 'rel'=>'stylesheet', 'type'=>'text/css', 'media'=>'screen'),         
	array('href'=>'application/third_party/css/jquery.searchFilter.css', 'rel'=>'stylesheet', 'type'=>'text/css', 'media'=>'screen'),
	array('href'=>'application/third_party/css/ui.jqgrid.css', 'rel'=>'stylesheet', 'type'=>'text/css', 'media'=>'screen'), 
	array('href'=>'application/third_party/jquery/jquery-ui.css', 'rel'=>'stylesheet', 'type'=>'text/css', 'media'=>'screen'), 
	array('href'=>'application/third_party/css/nprogress.css', 'rel'=>'stylesheet', 'type'=>'text/css', 'media'=>'screen'),					       
	array('href'=>'application/third_party/css/style.css', 'rel'=>'stylesheet', 'type'=>'text/css', 'media'=>'screen'),                   
	array('href'=>'application/third_party/css/'.$style.'.css?v='.substr(time(),7,3), 'rel'=>'stylesheet', 'type'=>'text/css', 'media'=>'screen'), 
	array('href'=>'application/third_party/select2/dist/css/select2.min.css', 'rel'=>'stylesheet', 'type'=>'text/css', 'media'=>'screen'),         
	array('href'=>'application/third_party/css/jquery-ui-timepicker-addon.css', 'rel'=>'stylesheet', 'type'=>'text/css', 'media'=>'screen'),         
	array('href'=>'application/third_party/css/sales_target.css', 'rel'=>'stylesheet', 'type'=>'text/css', 'media'=>'screen'),         
);	
$script_tag = array(
	array('src'=>'application/third_party/js/jquery-1.7.2.min.js'),
	array('src'=>'application/third_party/js/jquery-ui-1.8.23.min.js'),
	array('src'=>'application/third_party/js/jquery.jqGrid-4.4.3.min.js'),
	array('src'=>'application/third_party/js/grid.locale-en.js'),	
	array('src'=>'application/third_party/js/nprogress.js'),
	array('src'=>'application/third_party/js/stuHover.js'),
	array('src'=>'application/third_party/select2/dist/js/select2.min.js'),
	array('src'=>'application/third_party/js/moment.js'),
	array('src'=>'application/third_party/js/combodate.js'),
	array('src'=>'application/third_party/js/application.js?v='.substr(time(),7,3)),
	array('src'=>'application/third_party/js/'.$script.'.js?v='.substr(time(),7,3)),
	array('src'=>'application/third_party/js/jquery-ui-timepicker-addon.js'),
	array('src'=>'application/third_party/js/jquery-ui-sliderAccess.js'),
	'window.DC_LANGUAGE_CODES = { fallback:\'eng\', language:\'eng\' }',
);
echo 
doctype('html5').
html($this->config->item('language')).
head().meta($meta).
title('GCL :: '.strtoupper($title));

for($i=0;$i<count($link_tag);$i++) {
	echo link_tag($link_tag[$i]);
}
for($i=0;$i<count($script_tag);$i++) {
	echo script_tag($script_tag[$i]);
}

/*tambahan*/

if(isset($top_style)) :
	for($i=0;$i<count($top_style);$i++) {
		echo link_tag($top_style[$i]);
	}
endif;
if(isset($top_script)) :
	for($i=0;$i<count($top_script);$i++) {
		echo script_tag($top_script[$i]);
	}
endif;

/* end_tambahan */
?>
<!-- SweetAlert2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/postscribe/2.0.8/postscribe.min.js"></script>
<link rel="stylesheet" href="<?= base_url('application/third_party/css/sales_target.css') ?>">
</head>
<body id = "<?= isset($title) ? $title : '';?>">
	<div id="progress"></div>
	<!-- ========================================== -->
    <!-- TAMBAHAN: PANEL TIMELINE FLOATING (KIRI) -->
    <!-- ========================================== -->
    <div id="gcl-timeline-panel" class="gcl-floating-timeline" style="display:none;">
        <div class="tl-header">
            <h3>MASTER TIMELINE</h3>
            <span id="tl-kode-ref">Pilih Job...</span>
        </div>
        
        <!-- Rekap Muatan -->
        <div class="tl-cargo-summary">
            <div class="summary-box genco">
                <span class="label">GENCO</span>
                <span class="count" id="tl-count-genco">0</span>
            </div>
            <div class="summary-box dg">
                <span class="label">DG</span>
                <span class="count" id="tl-count-dg">0</span>
            </div>
        </div>

        <div id="timeline-master-content" class="tl-body">
            <div style="text-align:center; padding: 20px; color: #888; font-size: 11px;">
                Silakan pilih (klik) data Master Job untuk melihat riwayat timeline.
            </div>
        </div>
    </div>
    <!-- ========================================== -->
	<div id="wrapper">
      <div id = "header">
            <div id="menu">
                  <?php $this->load->view('navigation');?>
            </div>
      </div>
      <div id="main" class="clearfix">
      	<?php $this->load->view($main_view); ?>
      	<div class="clearfix"></div>
      	<div id="message_error"></div>
      </div>
	</div>
	<div id="Form-Dialog"></div>
	<div id="Print-Data"></div>
	<div id="form-stuffing"></div>
	<div id="form_list_stuffing_plan">
	<center>
		<table id="tabel_stuffing_plan"></table>
	</center>
	<div id="page-browse"></div>
</div>
<script type="text/javascript">
	var USERID='<?= $this->session->userdata("user_id") ?>';
	var REGID='<?= $this->session->userdata("region_id") ?>';
	var app='<?= base_url("application/") ?>';
	var pub='<?= base_url("pub/")?>';
	var ref='<?= base_url("reference/")?>';
	var printer='<?= base_url("printer/") ?>';
	var tmppub='<?= base_url("application/third_party/") ?>';
	var restrict = '<?= $restrict ?>';
	var time=new Date();
</script>
<?php 
/*tambahan script bawah*/
if(isset($bottom_script)) :
	for($i=0;$i<count($bottom_script);$i++) {
		echo script_tag($bottom_script[$i]);
	}
endif;
/* end */
?>
</body>
</html>