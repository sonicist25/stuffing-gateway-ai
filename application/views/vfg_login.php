<?php if (!defined('BASEPATH')) exit(include 'index.html');
$company = "G C L";
$lang = $this->config->item('language');
$charset = $this->config->item('charset');
$programer = 'Sony Bahtiar';
$nowtahun =  '2022';
$nexttahun =  date('Y')+1;
$tahun =  $nowtahun.'-'.$nexttahun;
echo doctype('html5');
?>
<html lang="<?php echo $lang ?>">
<head>
<title><?php echo $company ?> :: APLIKASI</title>
<?php 
$meta = array(
        array('name' => 'Content-type', 'content' => 'text/html; charset='.$charset.'','type' => 'http-equiv'),
        array('name' => 'googlebot', 'content' => 'all,index,follow'),
        array('name' => 'robots', 'content' => 'all,index,follow'),
        array('name' => 'allow-search', 'content' => 'yes'),        
        array('name' => 'generator', 'content' => 'CodeIgniter : '.CI_VERSION),
        array('name' => 'author', 'content' => $programer),
        array('name' => 'copyright', 'content' => $company.' | '.$tahun),        
        array('name' => 'description', 'content' => ''),
        array('name' => 'keywords', 'content' => ''),             
    );   
echo 
meta($meta).
link_tag('public/images/favicon.ico', 'shortcut icon', 'image/ico').
link_tag('public/theme/css/style-login.css', 'stylesheet', 'text/css' ,'', 'screen');
?>
<script type="text/javascript" src="<?php echo base_url('public/js/contentslider.js') ?>"></script>
<script src="<?php echo base_url('public/jqgrid/js/jquery.min.js'); ?>"></script>

<script type="text/javascript">
    var base_url = "<?php echo base_url(); ?>";
$(document).ready(function (){
	var margine  = $(window).height()-420;
	//$(".headline").css({"margin":margine/2+"px auto","width":$(window).width()-420+"px"});
	$(".headline").css({"margin":margine/2+"px auto","width":"920px"});
	$(".headline_wrap").css({"width":$(".headline").width()+"px"});
	$(".login").css({"left":$(".headline").width()-245+"px"});
})

if(!window.location.origin){
	window.location.origin=window.location.protocol+'//'+window.location.hostname+(window.location.port+':'+window.location.port)
}

function login() {
	if(event.keyCode===13) 
	{
		process_login();
	}
}

function process_login(){
	
	$.ajax({
		type: "POST", 
		url : window.location.origin+'/cfg_login/select_databases',
		data: { db : $('select[name="tahun"]').val()},
		success:function(data)
		{
			var data = $.parseJSON(data);
			if (data.db!='') {
				
				$.ajax({
				type: "POST", 
				url : window.location.origin+'/cfg_login/process_login',
				data: { 
				username : $('input[name="username"]').val(),
				password : $('input[name="password"]').val(),
				word : $('input[name="word"]').val(),
				db : $('select[name="tahun"]').val(),
				},
				success:function(data)
				{
					var data = $.parseJSON(data);
					if(data.status==0 || data.status==1){
					$('div[id="err_username"]').fadeIn('slow', function(){
						$(this).html(data.err_username)
					}).delay(3000).fadeOut('slow', function(){
						$(this).html('')
					})
					$('div[id="err_password"]').fadeIn('slow', function(){
						$(this).html(data.err_password)
					}).delay(3000).fadeOut('slow', function(){
						$(this).html('')
					})
					
					$('div[id="err_word"]').fadeIn('slow', function(){
						$(this).html(data.err_word)
					}).delay(3000).fadeOut('slow', function(){
						$(this).html('')
					})
					} 
					if(data.status==2){
					window.open('afg_c_stuffing_plan','_self',1,true);
					}
		 		}
		 		})
			} else {
				alert("please select databases year")
			}	
		}
	})
}

function recreate_captcha(){
	$.ajax({
		type: "POST", 
		url : window.location.origin+'/cfg_login/recreate_captcha',
		data: {  },
		success:function(data)
		{
			var data = $.parseJSON(data);
			$('div[id="cap_image"]').html(data.image_cap)
		} 
	})
}

function select_databases(){
	$.ajax({
		type: "POST", 
		url : window.location.origin+'/cfg_login/select_databases',
		data: { db : $('select[name="tahun"]').val()},
		success:function(data)
		{
			var data = $.parseJSON(data);
		} 
	})
}

</script>

</head>
<html>
<body class="login-page" onkeypress="login()">

<div class="headline">
<div class="headline_wrap clearfix">
  <div id="slider2" class="sliderwrapper ">
<div class="contentdiv" style="padding:0;">
    <img src="<?php echo base_url('public/images/img1.jpg') ?>" alt="img" />
</div>
<div class="contentdiv" style="padding:0;">
    <img src="<?php echo base_url('public/images/img2.jpg') ?>" alt="img"/>
</div>
<div class="contentdiv" style="padding:0;">
    <img src="<?php echo base_url('public/images/img3.jpg') ?>" alt="img" />
</div>
</div>

<div id="login" class="login">
	
	<div class="username"><?= form_label('Tahun','tahun') ?></div>
	<div class="dot_space"><?= form_label(':',':') ?></div>
	<div class="input_username">
		<?php echo form_dropdown('tahun',
			array(
			''=>'',
			'2015'=>'2015',
			'2016'=>'2016',
			'2017'=>'2017',
			'2018'=>'2018',
			'2019'=>'2019',
			), date('Y'),'id="tahun" class="input" onchange="select_databases()"'); ?>
	</div>

	<div class="clear"></div>
   <div class="username"><?= form_label('Username','username') ?></div>
	<div class="dot_space"><?= form_label(':',':') ?></div>
	<div class="input_username">
		<?= form_input(array('id'=>'username','name'=>'username','class'=>'input','value'=> set_value('username'))) ?>
	</div>
	<div id="err_username"></div>
		
	<div class="clear"></div>
	<div class="password"><?= form_label('Password','password') ?></div>
	<div class="dot_space"><?= form_label(':',':') ?></div>	
	<div class="input_password">
		<?= form_password(array('id'=>'password','name'=>'password','class'=>'input','value'=> set_value('password'))) ?>
	</div>
	<div id="err_password"></div>	
			
 <div class="clear"></div>
	<div class="captcha"></div>
	<div class="dot_space"></div>
	<div class="input_captcha">
		
	</div>
	<div id="err_word"></div>

	<div id="cap_image"></div>
	<div class="btnLogin"><?= form_submit(array('value'=>'Log In','class'=>'submit','onclick'=>'process_login()')) ?></div>
	
	<div style="clear: both;"></div>
	<div onclick="recreate_captcha()" class="create"></div>			
	<div style="clear: both; height: 10px; border-bottom: 1px solid #999;"></div>
	<div class="power">powered by : <a href="http://gateway-id.com" target="_blank">IT DIVISION GCL</a></div>
	<div style="clear: both"></div>
	<div class="power">WEB SERVER PHP version : <?= phpversion() ?></div>
	<div style="clear: both"></div>
	<div class="power">MYSQL SERVER version : </div>
</div>

<div id="paginate-slider2" class="pagination">
            <a href="#" class="toc">1</a>
            <a href="#" class="toc anotherclass">2</a>
            <a href="#" class="toc">3</a>
            <a href="#" class="toc anotherclass">4</a>
            <a href="#" class="toc "></a>    
</div>

<script type="text/javascript">   
    featuredcontentslider.init({
        id: "slider2", 
        contentsource: ["inline", ""], 
        toc: "markup", 
        revealtype: "mouseover", 
        nextprev: ["Previous", "Next"],  
        enablefade: [true, 0.05], 
        autorotate: [true, 3000],  
        onChange: function(previndex, curindex){ 
        }
    })
 </script>
</div>
</div>
</body>
</html>