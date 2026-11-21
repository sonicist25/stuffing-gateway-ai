<script src="<?php echo base_url('public/jqgrid/js/jquery.min.js'); ?>"></script>

<script type="text/javascript">
function click_data(){
	var nm_bln = new Array('Januari','Febuari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember');
	var tahun = new Date().getFullYear();	
	var bulan = new Date().getMonth();	
	var tgl  =  new Date().getDate();
	$('#html_s').html(tahun+' / '+nm_bln[bulan]+' / '+tgl).css({'border':'1px solid #f00'});
}
</script>

<div id="html_s" style="min-height: 200px"></div>
<?= form_submit(array('value'=>'submit','onclick'=>'click_data()','style'=>'margin-left: 200px; height: 30px; cursor: pointer'));