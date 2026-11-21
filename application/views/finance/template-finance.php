<style type="text/css">
table {
	padding: 10px;
}
td {

}
</style>

<div id="messages"></div>
<div id="form-reporty" class="main-reporty"></div>

<div id="acct-data" style="font-size: 11px"></div>
<div id="form-output"></div>

<div id="acct-inv" style="font-size: 11px"></div>
<div id="form-output"></div>

<input type="hidden" id="q<?php echo  $this->session->userdata('user_id') ?>" name="q<?php echo  $this->session->userdata('user_id') ?>">

<script type="text/javascript" >
function tanggal(mode){
	var currentTime = new Date()
	var month=new Array();
		month[0]="01";
		month[1]="02";
		month[2]="03";
		month[3]="04";
		month[4]="05";
		month[5]="06";
		month[6]="07";
		month[7]="08";
		month[8]="09";
		month[9]="10";
		month[10]="11";
		month[11]="12";
		
	var p = month[currentTime.getMonth()] - eval(1) ;	
	
	if(p.length!=2){
		var p = "0"+p;
	}
	var n = month[currentTime.getMonth()];
	var day = currentTime.getDate()
	var year = currentTime.getFullYear().toString().substr(-2)
	
	if(mode=='now'){
		return(year+p);
	} else {
		return (year+p);
	}
}
</script>
<?php 
$uri2 = $this->uri->segment(2);
if($uri2 == 'c_post_data_acct') { ?>
<script type="text/javascript" >
$(document).ready(function () 
{	
	$.ajax({
		type: "POST", 
		url : "<?php echo base_url('finance/c_post_data_acct/open_form')?>",
		data: { ajax : 1 },
		success:function(XMLHttpRequest)
		{
			$( "#acct-data" ).dialog(
				{
					autoOpen: true,
					resizable: false,
					height: 'auto',
					width: 'auto',
					modal: true,
					title :"POST DATA JOBSHEET",
			});
			$( "#acct-data" ).html(XMLHttpRequest);
			$('input[name="bulan"]').datepicker({dateFormat : "ymm"});
			$('.ui-dialog-titlebar-close').attr('hidden',true);
		}
	})
});
</script>
<?php } ?>

<?php 
$uri2 = $this->uri->segment(2);
if($uri2 == 'c_post_data_acct_per_job') { ?>
<script type="text/javascript" >
$(document).ready(function () 
{	
	$.ajax({
		type: "POST", 
		url : "<?php echo base_url('finance/c_post_data_acct_per_job/open_form')?>",
		data: { },
		success:function(XMLHttpRequest)
		{
			$( "#acct-data" ).dialog(
				{
					autoOpen: true,
					resizable: false,
					height: 'auto',
					width: 'auto',
					modal: true,
					title :"POST DATA JOBSHEET PER JOB",
			});
			$( "#acct-data" ).html(XMLHttpRequest);
			$('input[name="bulan"]').datepicker({dateFormat : "ymm"});
			$('.ui-dialog-titlebar-close').attr('hidden',true);
		}
	})
});
</script>
<?php } ?>

<?php 
$uri2 = $this->uri->segment(2);
if($uri2 == 'c_post_data_inv') { ?>
<script type="text/javascript" >
$(document).ready(function () 
{	
	$.ajax({
		type: "POST", 
		url : "<?php echo base_url('finance/c_post_data_inv/open_form')?>",
		data: { },
		success:function(XMLHttpRequest)
		{
			$( "#acct-inv" ).dialog(
				{
					autoOpen: true,
					resizable: false,
					height: 'auto',
					width: 'auto',
					modal: true,
					title :"POST INVOICE PER PERIODE",
			});
			$( "#acct-inv" ).html(XMLHttpRequest);
			$('input[name="bulan"]').datepicker({dateFormat : "ymm"});
			$('.ui-dialog-titlebar-close').attr('hidden',true);
		}
	})
});
</script>
<?php } ?>

<?php 
$uri2 = $this->uri->segment(2);
if($uri2 == 'c_post_data_acct_per_inv') {?>
<script type="text/javascript" >
$(document).ready(function () 
{	
	$.ajax({
		type: "POST", 
		url : "<?php echo base_url('finance/c_post_data_acct_per_inv/open_form')?>",
		data: { },
		success:function(XMLHttpRequest)
		{
			$( "#acct-inv" ).dialog(
				{  
					autoOpen: true,
					resizable: false,
					height: 'auto',
					width: 'auto',
					modal: true,
					title :"POST INVOICE PER NOMOR",
			});
			$( "#acct-inv" ).html(XMLHttpRequest);
			$('input[name="bulan"]').datepicker({dateFormat : "ymm"});
			$('.ui-dialog-titlebar-close').attr('hidden',true);
		}
	})
});
</script>
<?php } ?>