<style type="text/css">
table {
	padding: 10px;
}
td {

}
</style>

<div id="messages"></div>
<div id="form-reporty" class="main-reporty"></div>

<div id="form-report" style="font-size: 11px"></div>
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
		
	var p = '01' ;	
	var dt = new Date();
	var n = month[currentTime.getMonth()];
	var day = dt.getDate().toString().padStart(2, "0");
	var year = currentTime.getFullYear()
	
	if(mode=='now'){
		return(day + "/" + n + "/" + year);
	} else {
		return (day + "/" + p + "/" + year);
	}
}
</script>
<?php 
$uri2 = $this->uri->segment(2);
if($uri2 == 'job_sheet') { ?>
<script type="text/javascript" >
$(document).ready(function () 
{	
	$.ajax({
		type: "POST", 
		url : "<?php echo base_url('report/job_sheet/open_form')?>",
		data: { ajax : 1 },
		success:function(XMLHttpRequest)
		{
			$( "#form-report" ).dialog(
				{
					autoOpen: true,
					resizable: false,
					height: 150,
					width: 'auto',
					modal: true,
					title :"TOTAL JOB PERBULAN",
			});
			$( "#form-report" ).html(XMLHttpRequest);
			$('input[name="bulan"]').datepicker({dateFormat : "mm-yy"});
			$('.ui-dialog-titlebar-close').attr('hidden',true);
		}
	})
});
</script>
<?php } 
if($uri2 == 'sales_commission') { ?>
<script type="text/javascript" >
$(document).ready(function () 
{	
	$.ajax({
		type: "POST", 
		url : "<?php echo base_url('report/sales_commission/open_form')?>",
		data: { 
			tgl_awal : tanggal('prev'),
			tgl_akhir : tanggal('now'),
		},
		success:function(XMLHttpRequest)
		{
			$( "#form-report" ).dialog(
			{
					autoOpen: true,
					resizable: false,
					height: 'auto',
					width: 450,
					modal: true,
					title :"SALES COMMISSION",
			});
			$( "#form-report" ).html(XMLHttpRequest);
			$( "#tgl_awal" ).datepicker({dateFormat : "dd/mm/yy"}).val(tanggal('prev'));
			$( "#tgl_akhir" ).datepicker({dateFormat : "dd/mm/yy"}).val(tanggal('now'));
			$('.ui-dialog-titlebar-close').attr('hidden',true);
		}
	})
});
</script>
<?php }
if($uri2 == 'discount_rebate_shipper') { ?>
<script type="text/javascript" >
$(document).ready(function () 
{	
	$.ajax({
		type: "POST", 
		url : "<?php echo base_url('report/discount_rebate_shipper/open_form')?>",
		data: {
			tgl_awal : tanggal('prev'),
			tgl_akhir : tanggal('now'),
		},
		success:function(XMLHttpRequest)
		{
			$( "#form-report" ).dialog(
				{
					autoOpen: true,
					resizable: false,
					height: 'auto',
					width: 350,
					modal: true,
					title :"Rekap Discount / Rebate Shipper",
					close: function() {	}
			});
			$( "#form-report" ).html(XMLHttpRequest);
			$( "#tgl_awal" ).datepicker({dateFormat : "dd/mm/yy"}).val(tanggal('prev'));
			$( "#tgl_akhir" ).datepicker({dateFormat : "dd/mm/yy"}).val(tanggal('now'));
			$('.ui-dialog-titlebar-close').attr('hidden',true);
		}
	})
});
</script>
<?php } 
if($uri2 == 'rekap_sales') { ?>
<script type="text/javascript" >
$(function () {	
	$.ajax({
		type: "POST", 
		url : "<?php echo base_url('report/rekap_sales/open_form')?>",
		data: { },
		success:function(XMLHttpRequest)
		{
			$("#form-report" ).dialog({
					autoOpen: true,
					resizable: false,
					height: 155,
					width: 470,
					modal: true,
					title :"LAPORAN PER SALES",
					close: function() {	}
			});
			$("#form-report").html(XMLHttpRequest)
			$("#tgl_awal").datepicker({dateFormat : "dd/mm/yy"}).val(tanggal('prev'));
			$("#tgl_akhir").datepicker({dateFormat : "dd/mm/yy"}).val(tanggal('now'));
			$('.ui-dialog-titlebar-close').attr('hidden',true);
		}
	})
});
</script>
<?php } 
if($uri2 == 'rekap_shipper') { ?>
<script type="text/javascript" >
$(function () {	
	$.ajax({
		type: "POST", 
		url : "<?php echo base_url('report/rekap_shipper/open_form')?>",
		data: { },
		success:function(XMLHttpRequest){
			$("#form-report").dialog({
					autoOpen: true,
					resizable: false,
					height: 330,
					width: 390,
					//position:['center',80],
					modal: true,
					title :"LAPORAN SHIPMENT PER SHIPPER",
					close: function() {	}
			});
			$("#form-report").html(XMLHttpRequest)
			$("#tgl_awal").datepicker({dateFormat : "dd/mm/yy"}).val(tanggal('prev'));
			$("#tgl_akhir").datepicker({dateFormat : "dd/mm/yy"}).val(tanggal('now'));
			$('.ui-dialog-titlebar-close').attr('hidden',true);
		}
	})
});
</script>

<?php } 
if($uri2 == 'rekap_consignee') { ?>
<script type="text/javascript" >
$(function () {	
	$.ajax({
		type: "POST", 
		url : "<?php echo base_url('report/rekap_consignee/open_form')?>",
		data: { },
		success:function(XMLHttpRequest){
			$("#form-report").dialog({
					autoOpen: true,
					resizable: false,
					height: 'auto',
					width: 520,
					position:['center',80],
					modal: true,
					title :"LAPORAN PER CONSIGNEE",
					close: function() {	}
			});
			$("#form-report").html(XMLHttpRequest)
			$("#tgl_awal").datepicker({dateFormat : "dd/mm/yy"}).val(tanggal('prev'));
			$("#tgl_akhir").datepicker({dateFormat : "dd/mm/yy"}).val(tanggal('now'));
			$('.ui-dialog-titlebar-close').attr('hidden',true);
		}
	})
});
</script>
<?php } 
if($uri2 == 'rekap_agent') { ?>
<script type="text/javascript" >
$(function () {	
	$.ajax({
		type: "POST", 
		url : "<?php echo base_url('report/rekap_agent/open_form')?>",
		data: { },
		success:function(XMLHttpRequest){
			$("#form-report").dialog({
					autoOpen: true,
					resizable: false,
					height: 'auto',
					width: 520,
					position:['center',80],
					modal: true,
					title :"LAPORAN PER AGENT",
					close: function() {	}
			});
			$("#form-report").html(XMLHttpRequest)
			$( "#bulan" ).datepicker({dateFormat : "mm/yy"}).val('<?php echo date("m/Y") ?>');
			$('.ui-dialog-titlebar-close').attr('hidden',true);
		}
	})
});
</script>
<?php }
if($uri2 == 'rekap_jobsheet') { ?>
<script type="text/javascript" >
$(document).ready(function () 
{	
	$.ajax({
		type: "POST", 
		url : "<?php echo base_url('report/rekap_jobsheet/open_form')?>",
		data: { },
		success:function(XMLHttpRequest)
		{
			$( "#form-report" ).dialog(
				{
					autoOpen: true,
					resizable: false,
					height: 110,
					width: 320,
					modal: true,
					title :"(RekBulan)<?php echo nbs(8) ?>REKAP JOBSHEET",
					close: function() {	}
			});
			$( "#form-report" ).html(XMLHttpRequest);
			$( "#tanggal" ).datepicker({dateFormat : "mm/yy"}).val('<?php echo date("m/Y") ?>');
			$('.ui-dialog-titlebar-close').attr('hidden',true);
		}
	})
});
</script>
<?php } 
if($uri2 == 'analisa_job') { ?>
<script type="text/javascript" >
$(document).ready(function () 
{	
	$.ajax({
		type: "POST", 
		url : "<?php echo base_url('report/analisa_job/open_form')?>",
		data: { },
		success:function(XMLHttpRequest)
		{
			$( "#form-report" ).dialog(
				{
					autoOpen: true,
					resizable: false,
					height: 190,
					width: 340,
					modal: true,
					title :"ANALISA JOB",
					close: function() {	}
			});
			$( "#form-report" ).html(XMLHttpRequest)
			$( "#bulan" ).datepicker({dateFormat : "mm/yy"}).val('<?php echo date("m/Y") ?>');
			$('.ui-dialog-titlebar-close').attr('hidden',true);
		}
	})
});
</script>
<?php } 
if($uri2 == 'listing_job') { ?>
<script type="text/javascript" >
$(document).ready(function () 
{	
	$.ajax({
		type: "POST", 
		url : "<?php echo base_url('report/listing_job/open_form')?>",
		data: { },
		success:function(XMLHttpRequest)
		{
			$( "#form-report" ).dialog(
				{
					autoOpen: true,
					resizable: false,
					height: 200,
					width: 540,
					modal: true,
					title :"<h1>LISTING INCOME YG BLM DITARIK KE INVOICE    </h1>",
					close: function() {	}
			});
			$( "#form-report" ).html(XMLHttpRequest);
			$( "#bulan" ).datepicker({dateFormat : "mm/yy"}).val('<?php echo date("m/Y") ?>');
			$('.ui-dialog-titlebar-close').attr('hidden',true);
		}
	})
});
</script>
<?php } 
if($uri2 == 'list_unposting') { ?>
<script type="text/javascript" >
$(document).ready(function () 
{	
	$.ajax({
		type: "POST", 
		url : "<?php echo base_url('report/list_unposting/open_form')?>",
		data: { },
		success:function(XMLHttpRequest)
		{
			$( "#form-report" ).dialog(
				{
					autoOpen: true,
					resizable: false,
					height: 130,
					width: 340,
					modal: true,
					title :"<h1>LISTING JOB YG BLM DI POST</h1>",
					close: function() {	}
			});
			$( "#form-report" ).html(XMLHttpRequest);
			$( "#bulan" ).datepicker({dateFormat : "mm/yy"}).val('<?php echo date("m/Y") ?>');
			$('.ui-dialog-titlebar-close').attr('hidden',true);
		}
	})
});
</script>
<?php } 
if($uri2 == 'list_inv_unposting') { ?>
<script type="text/javascript" >
$(document).ready(function () 
{	
	$.ajax({
		type: "POST", 
		url : "<?php echo base_url('report/list_inv_unposting/open_form')?>",
		data: { },
		success:function(XMLHttpRequest)
		{
			$( "#form-report" ).dialog(
				{
					autoOpen: true,
					resizable: false,
					height: 130,
					width: 340,
					modal: true,
					title :"<h1>LISTING INVOICE YG BLM DI POST</h1>",
					close: function() {	}
			});
			$( "#form-report" ).html(XMLHttpRequest);
			$( "#bulan" ).datepicker({dateFormat : "mm/yy"}).val('<?php echo date("m/Y") ?>');
			$('.ui-dialog-titlebar-close').attr('hidden',true);
		}
	})
});
</script>
<?php }
if($uri2 == 'laporan_per_departement') { ?>
<script type="text/javascript" >
$(document).ready(function () 
{	
	$.ajax({
		type: "POST", 
		url : "<?php echo base_url('report/laporan_per_departement/open_form')?>",
		data: { },
		success:function(XMLHttpRequest)
		{
			$( "#form-report" ).dialog(
				{
					autoOpen: true,
					resizable: false,
					height: 'auto',
					width: 320,
					modal: true,
					title :"<h1>LAPORAN PER DEPARTEMENT    </h1>",
					close: function() {	}
			});
			$( "#form-report" ).html(XMLHttpRequest);
			$( "#bulan" ).datepicker({dateFormat : "mm/yy"}).val('<?php echo date("m/Y") ?>');
			$('.ui-dialog-titlebar-close').attr('hidden',true);
		}
	})
});
</script>
<?php } 
if($uri2 == 'laporan_job') { ?>
<script type="text/javascript" >
$(document).ready(function () 
{	
	$.ajax({
		type: "POST", 
		url : "<?php echo base_url('report/laporan_job/open_form')?>",
		data: { },
		success:function(XMLHttpRequest)
		{
			$( "#form-report" ).dialog(
				{
					autoOpen: true,
					resizable: false,
					height: 'auto',
					width: 380,
					modal: true,
					title :"<h1>LAPORAN PER DEPARTEMENT    </h1>",
					close: function() {	}
			});
			$( "#form-report" ).html(XMLHttpRequest);
			$( "#bulan" ).datepicker({dateFormat : "mm/yy"}).val('<?php echo date("m/Y") ?>');
			$('.ui-dialog-titlebar-close').attr('hidden',true);
		}
	})
});
</script>
<?php }
if($uri2 == 'laporan_shipment') { ?>
<script type="text/javascript" >
$(document).ready(function () 
{	
	$.ajax({
		type: "POST", 
		url : "<?php echo base_url('report/laporan_shipment/open_form')?>",
		data: { },
		success:function(XMLHttpRequest)
		{
			$( "#form-report" ).dialog(
				{
					autoOpen: true,
					resizable: false,
					height: 160,
					width: 320,
					modal: true,
					title :"LAPORAN LR PER JOB",
					close: function() {	}
			});
			$( "#form-report" ).html(XMLHttpRequest);
			$( "#tanggal" ).datepicker({dateFormat : "mm/yy"}).val('<?php echo date("m/Y") ?>');
			$('.ui-dialog-titlebar-close').attr('hidden',true);
		}
	})
});
</script>
<?php } 
if($uri2 == 'listing_invoice') { ?>
<script type="text/javascript" >
$(document).ready(function () 
{	
	$.ajax({
		type: "POST", 
		url : "<?php echo base_url('report/listing_invoice/open_form')?>",
		data: { },
		success:function(XMLHttpRequest)
		{
			$( "#form-report" ).dialog(
				{
					autoOpen: true,
					resizable: false,
					height: 130,
					width: 340,
					modal: true,
					title :"<h1>LISTING TOTAL INVOICE</h1>",
					close: function() {	}
			});
			$( "#form-report" ).html(XMLHttpRequest);
			$( "#bulan" ).datepicker({dateFormat : "mm/yy"}).val('<?php echo date("m/Y") ?>');
			$('.ui-dialog-titlebar-close').attr('hidden',true);
		}
	})
});
</script>
<?php } 
?>