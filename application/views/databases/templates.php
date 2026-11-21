<?php if (!defined('BASEPATH')) exit(include 'index.html');
$company = 'PT ISI';
$lang = $this->config->item('language');
$charset = $this->config->item('charset');
$programer = 'Jaya Teknologi Indonesia';
$nowtahun =  '2008';
$nexttahun =  date('Y')+1;
$tahun =  $nowtahun.'-'.$nexttahun;
echo doctype('html5');
?>
<html lang="<?php echo $lang ?>">
<head>
<title><?php echo $company ?> :: <?php echo isset($title) ? $title : '';?></title>
<?php 
$meta = array(
        array('name' => 'Content-type', 'content' => 'text/html; charset='.$charset.'','type' => 'http-equiv'),
        array('name' => 'googlebot', 'content' => 'all,index,follow'),
        array('name' => 'robots', 'content' => 'all,index,follow'),
        array('name' => 'allow-search', 'content' => 'yes'),        
        array('name' => 'generator', 'content' => 'bluefish 2.0.3 | CodeIgniter 2.1.0 '),
        array('name' => 'author', 'content' => $programer),
        array('name' => 'copyright', 'content' => $company.' | '.$tahun),        
        array('name' => 'description', 'content' => ''),
        array('name' => 'keywords', 'content' => ''),             
    );   
echo 
meta($meta)
. link_tag('favicon.ico', 'shortcut icon', 'image/ico')
. link_tag('public/theme/css/reset.css', 'stylesheet', 'text/css')
. link_tag('public/theme/css/style.css', 'stylesheet', 'text/css')
. link_tag('public/theme/pro_dropdown/pro_dropdown.css', 'stylesheet', 'text/css')

. link_tag('pub/jQgrid/css/jquery-ui.css', 'stylesheet', 'text/css')
. link_tag('pub/jQgrid/css/ui.jqgrid.css', 'stylesheet', 'text/css')

//. link_tag('public/jqgrid/css/ui.jqgrid.css', 'stylesheet', 'text/css')

. link_tag('public/jqgrid/css/jquery.searchFilter.css', 'stylesheet', 'text/css');
?>

<script src="<?php echo base_url('public/theme/pro_dropdown/stuHover.js')?>" type="text/javascript"></script>

<script src="<?php echo base_url('pub/jQgrid/jquery.js'); ?>"></script>
<script src="<?php echo base_url('pub/jQgrid/jquery-ui.min.js'); ?>" type="text/javascript"></script>
<script src="<?php echo base_url('pub/jQgrid/grid.locale-en.js'); ?>" type="text/javascript"></script>
<script type="text/javascript">
        $.jgrid.no_legacy_api = true;
        $.jgrid.useJSON = true;
</script>
<script src="<?php echo base_url('pub/jQgrid/jquery.jqGrid.src.js'); ?>" type="text/javascript"></script>


<script type="text/javascript">
$(function() {
	$( '#loading' ).ajaxStart(function(){
			$(this).fadeIn();
			$(this).html('<img src="<?php echo base_url("public/images/loader.gif")?>"/>');
		}).ajaxStop(function(){
			$(this).fadeOut();
	});
<?php
/* 
		$restrict = $this->session->flashdata('restrict');
		echo ! empty($restrict) ? 
		"show_messages('You are not authorize to use ".$restrict." Application');": '';
*/
?>	
})

function show_messages(char){
	var form = $('#ERROR-USER'), i;
	form.fadeIn('slow', function () {
		form.dialog({
			modal: false,
			resizable: true,
			position:'center',
			height: 70,
			title : 'Messages',
		})
		form.html(char);
		form.css('max-height','70px');
		form.css('color','#f33');
	})
	.delay(3000)
	.fadeOut('slow',
		function () {
			form.dialog('close');
		}
	);
}

function show_nota(file){
	var form = $( "#form-nota" ),i;
	var dialogWidth = 'auto';
	var lebar = ($(window).width() / 2);
	var tinggi = ($(window).height() /2);
	form.dialog({
		resizable: true,
		modal: true,
		width: lebar,
		height: tinggi,
		title : "File "+file,
	});
	form.html('<img src="../../public/images/upload/'+file+'" />')
}

function format_uang(input){
    var num = input.value.replace(/\ /g,'');
    if(!isNaN(num)){
    	if(num.indexOf(',') > -1){
    		num = num.split(',');
    		num[0] = num[0].toString().split('').reverse().join('').replace(/(?=\d*\,?)(\d{3})/g,'$1 ').split('').reverse().join('').replace(/^[\ ]/,'');
    		if(num[1].length > 2){
				var message = 'Hanya dapat memasukan 2 ( dua ) digit desimal';
				show_messages(message);
    			num[1] = num[1].substring(0,num[1].length-1);
    		} input.value = num[0]+','+num[1];
    	} else{ input.value = num.toString().split('').reverse().join('').replace(/(?=\d*\,?)(\d{3})/g,'$1 ').split('').reverse().join('').replace(/^[\ ]/,'') };
    }
    else
    { 
		var message = 'Only numeric allowed / Hanya diperbolehkan memasukan angka';
		show_messages(message);
    	input.value = input.value.substring(0,input.value.length-1); 
    }
}

function get_uang(input) {
	return input.toString().split('').reverse().join('').replace(/(?=\d*\.?)(\d{3})/g,'$1 ').split('').reverse().join('').replace(/^[\ ]/,'')
}

function help_file(use){
	var title = use.toUpperCase();
	var file = use.toLowerCase().replace(/\ /g,'_');
	$( "#help_file" ).dialog(
	{
		modal: false,
		autoOpen: true,
		resizable: false,
		position:['top','left'],
		width: 370,
		height: 'auto',
		title:'PANDUAN '+title,
		close: function () {
			$( "#help_file" ).html('');
		//	$(".ui-dialog-titlebar").show()
		}
	});
//	$(".ui-dialog-titlebar").hide()
	$( "#help_file" ).css({'mix-width':'360px','margin':'5px','padding':'10px','border':'1px solid #999','font-size':'11px'})
	$( "#help_file" ).load('<?php echo base_url("file/help/")?>/'+file+'.html');
}
<?php 
echo "
function delete_data_table(table){	
	var bln = $('select[id=\"combo_month\"]').val();
	var thn = $('select[id=\"combo_year\"]').val();
	var prj = $('select[id=\"combo_project\"]').val();

	$.ajax({
		type: \"POST\", 
		url : \"". base_url('kelola/app_log/delete_data_table/') ."\",
		data: { 
			id_project : prj,
			tanggal : thn+''+bln,
			table: table,
	   },
		success:function(response)
		{
			var data = $.parseJSON(response);
			if(table=='rekapitulasi_in'){
				var grid = $(\"#tabel_satu\"), i;
			} else if(table=='rekapitulasi_out'){
				var grid = $(\"#tabel_dua\"), i;
			} else {
				var grid = $(\"#tabel\"), i;
			}
			var curPage = grid.getGridParam(\"page\");
			grid.jqGrid(\"setGridParam\", {datatype: \"json\"}).trigger(\"reloadGrid\", [{ page: curPage }]);
		}
	})
}";
?>
</script>

<style type="text/css">
	.help {
		vertical-align: top;
		font-size: 12px;
	}
	a {
		text-decoration: none;
	}
	a:hover{
		color: #33f;
		text-decoration: none;
	}
</style>
</head>
<html>
<body id = "<?php echo isset($title) ? $title : '';?>">
	<div id="ERROR-USER"></div>
	<div id="progress"></div>
	<div id="form-nota"></div>
	<div id="wrapper">
	<div id="help_file"></div>
      <div id = "header">
      	<div style="float: left; margin-left: 20px; margin-top: 10px">
			<img src="<?php echo base_url('pub/images/logo.png');?>" width="55" height="55">
			</div>
         <div id="menu">
             <?php $this->load->view($menu_navigation);?>
         </div>
      </div>
      <div id="main" class="clearfix">
      	<?php $this->load->view($main_view); ?>
      </div>
      <div class="clearfix"></div>
	</div>
</body>
</html>