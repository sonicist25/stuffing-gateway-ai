<style type="text/css">
.ui-jqgrid-bdiv{ min-height: 300px; }
.ui-jqgrid-titlebar-close{ visibility: hidden}
</style>

<div style="margin-top: 20px;margin-left: 80px">
CEK POSITION BERDASARKAN IP : 
<input id="ip_addr" type="text" style="border-radius:4px; width:150px; margin-right: 10px; height: 20px" />
<button style="width: 60px" onclick="check_ip()">CEK</button>
</div>
<div id="ip_show" style="margin-top: 20px;margin-left: 50px"></div>
<div style="clear: both; height: 20px"></div>
<div style="">
<center>
	<table id="act_log"></table>
</center>
	<div id="page-inv" style="font-size: 12px"></div>
</div>

<script type="text/javascript" >
$(document).ready(function () {
	get_table_actifity();
});

function get_user(id) {
	if (id==0) {
		return 'ADMINISTRATOR';
	}
	var udata=<?=$user?>;
	var user=udata[id];
	if (user==undefined) {
		return '';
	} else {
		return user;
	}
}
</script>
