<div id="main-page" style="font-size: 11px">
<div id="msg-job-shit"></div>

	<style type="text/css">
	.tgl {
		width: 70px;
		font-size: 9px;
	}
	#button-save,#button-print,#button-post
	{
		box-shadow:inset 0px 1px 0px 0px #bbdaf7;
		background:linear-gradient(to bottom, #79bbff 5%, #378de5 100%);
		background-color:#79bbff;
		border-radius:6px;
		border:1px solid #84bbf3;
		display:inline-block;
		cursor:pointer;
		color:#ffffff;
		font-family:Arial;
		font-size:11px;
		padding:6px 24px;
		text-decoration:none;
		text-shadow:0px 1px 0px #528ecc;
	} 
	</style>

	<!-- HEADER: TANGGAL, KURS, DAN INFO STATUS -->
	<div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px; width: 100%;">
		
		<!-- Kiri: Input Form -->
		<table style="width: 220px;">
			<tr>
				<td width="80" style="padding-bottom: 5px;">Tgl Cetak</td>
				<td style="padding-bottom: 5px;"><input id="tgl-cetak" style="padding-left:5px; width: 110px;"></td>
			</tr>
			<tr>
				<td>Kurs Rp.</td>
				<td><input id="kursJobSheet" style="padding-left:5px; width: 110px;" readonly="readonly"></td>
			</tr>
		</table>

		<!-- Kanan: Info Posted & Edited/Locked -->
		<div style="width: 350px; text-align: right; font-size: 10px;">
			<?php if($query['memo']!='') { ?>
			<!-- Tampilan Last Action (Lock/Unlock) -->
			<div style="background: #fdf3e9; border: 1px solid #f9cd9f; padding: 4px 8px; border-radius: 4px; margin-bottom: 5px; display: inline-block; text-align: right;">
				<span style="color: #e67e22; font-weight: bold;">📝 Last Action (Lock/Unlock by):</span><br>
				<span style="color: #555;"><?php echo str_replace('<br>', ' ', $query['memo']); ?></span>
			</div>
			<?php } ?>
			
			<div style="clear:both;"></div>

			<?php if($query['tgl_cetak']!='' && $query['tgl_cetak']!='Unposted') { ?>
			<!-- Tampilan Last Posted -->
			<div style="background: #eafaf1; border: 1px solid #a3e4d7; padding: 4px 8px; border-radius: 4px; display: inline-block; text-align: right;">
				<span style="color: #27ae60; font-weight: bold;">✅ Last Posted by:</span><br>
				<span style="color: #555;"><?php echo $created; ?></span>
			</div>
			<?php } ?>
		</div>
	</div>

	<div style="clear: both; height: 5px"></div>

	<!-- KOLOM KIRI : LOCK JOB (ID 1-5) -->
	<div style="float: left; width: 290px;"> 
        <div style="background-color: #ffcccc; border: 1px solid #cc0000; color: #cc0000; padding: 5px; text-align: center; font-weight: bold; font-size: 10px; margin-bottom: 5px; border-radius: 3px;">
            🔒 KOLOM LOCK (CLOSE JOB)
        </div>
		<table width="100%">
		<?php
		$user_id = $this->session->userdata('user_id');
		if ($user_id == 160 || $user_id == 113 || $user_id == 116 ||$user_id == 109 ||$user_id == 169 || $user_id == 110 || $user_id == 53 || $user_id == 34 || $user_id == 0 || $user_id == 120 || $user_id == 454 || $user_id == 522 || $user_id ==615|| $user_id == 60 || $user_id == 107|| $user_id == 542)
		{
		for ($i = 1; $i <= 5; $i++ ) 
		{
			echo '
			<tr>
				<td height="25" style="white-space: nowrap;">
                    <input type="checkbox" id="cs_'.$i.'" style="vertical-align: middle;">
                    <span id="lbl_cs_'.$i.'" style="color:#333; vertical-align: middle; margin-left: 2px;">C/S '.$i.'</span>
                </td>
				<td><input id="tgl_cs'.$i.'" class="tgl" disabled="disabled" style="margin-left: 2px; margin-right: 5px;"></td>
				
				<td align="right" style="white-space: nowrap;">
                    <input type="checkbox" id="acct_'.$i.'" style="vertical-align: middle;">
                    <span id="lbl_acct_'.$i.'" style="color:#333; vertical-align: middle; margin-left: 2px;">ACCT '.$i.'</span>
                </td>
				<td align="right"><input id="tgl_acct'.$i.'" class="tgl" disabled="disabled" style="margin-left: 2px;"></td>
			</tr>';
		}
		}?>
		</table>
	</div>

	<!-- KOLOM KANAN : UNLOCK JOB (ID 6-10) -->
	<div style="float: right; width: 290px;">
        <div style="background-color: #ccffcc; border: 1px solid #006600; color: #006600; padding: 5px; text-align: center; font-weight: bold; font-size: 10px; margin-bottom: 5px; border-radius: 3px;">
            🔓 KOLOM UNLOCK (BUKA JOB)
        </div>
	<table width="100%">
	<?php
	$user_id = $this->session->userdata('user_id');
	if ($user_id == 160 || $user_id == 113 || $user_id == 116 ||$user_id == 109 ||$user_id == 169 || $user_id == 110 || $user_id == 53 || $user_id == 34 || $user_id == 0 || $user_id == 120 || $user_id == 522|| $user_id == 60 || $user_id == 615 || $user_id == 107 || $user_id == 542)
		{
		for ($i = 6; $i <= 10; $i++ ) 
		{
			echo '
			<tr>
				<td height="25" style="white-space: nowrap;">
                    <input type="checkbox" id="cs_'.$i.'" style="vertical-align: middle;">
                    <span id="lbl_cs_'.$i.'" style="color:#333; vertical-align: middle; margin-left: 2px;">C/S '.$i.'</span>
                </td>
				<td><input id="tgl_cs'.$i.'" class="tgl" disabled="disabled" style="margin-left: 2px; margin-right: 5px;"></td>
				
				<td align="right" style="white-space: nowrap;">
                    <input type="checkbox" id="acct_'.$i.'" style="vertical-align: middle;">
                    <span id="lbl_acct_'.$i.'" style="color:#333; vertical-align: middle; margin-left: 2px;">ACCT '.$i.'</span>
                </td>
				<td align="right"><input id="tgl_acct'.$i.'" class="tgl" disabled="disabled" style="margin-left: 2px;"></td>
			</tr>';
		}
		}
	?>
	</table>
	</div>
	<div style="clear: both"></div>

	<?php
	// FIX LOCK_JOB: banner status lock_job ASLI dari DB (bukan dari centang C/S / ACCT).
	// Jumlah buka ulang = jumlah centang di kolom UNLOCK (C/S 6-10 + ACCT 6-10).
	$__reopen_count = 0;
	for ($__i = 6; $__i <= 10; $__i++) {
		if (($query['cs_'.$__i] ?? '') == 'TRUE')   $__reopen_count++;
		if (($query['acct_'.$__i] ?? '') == 'TRUE') $__reopen_count++;
	}
	?>
	<input type="hidden" id="reopen-count-db" value="<?php echo (int)$__reopen_count; ?>">
	<div id="lock-status-banner" style="display:none; margin-top:6px; padding:5px 8px; border-radius:4px; font-size:10px;">
		<span id="lock-status-text"></span>
	</div>

	<hr>
	<input type="hidden" id="test-form">
	<input type="hidden" value="<?php echo $kode_ref ?>" id="kode_ref_form">
	<input type="hidden" value="<?php echo $id ?>" id="idJob">

	<input type="button" id="button-save" style="width: 100px; height: 30px; float: right; margin-left: 10px;" value=" SAVE DATA " onclick="form_close()">
	<input type="button" id="button-print" style="width: 100px; height: 30px; float: right;" value=" PRINT " onclick="print_job_sheet()">
	<input type="button" id="button-print" style="width: 100px; height: 30px; float: left;" value=" POST " onclick="get_proses_post()">
</div>

<script type="text/javascript">
$(document).ready(function (){
	var kurs_job = $('#txtKurs').val();
	var date = '<?= date("d")."-".date("m")."-".date("Y") ?>';
	$( '#tgl-cetak' ).datepicker({dateFormat : "dd-mm-yy"}).val(date);	
	$( '#kursJobSheet' ).val(gubahUang(kurs_job));
<?php

	if($query['cs_1']=='FALSE'){
		echo '$( "#cs_1" ).attr("checked",false).attr("disabled", false);';
	}
	
	if($query['acct_1']=='FALSE'){
		echo '$( "#acct_1" ).attr("checked",false).attr("disabled", false);';
	}
	
    // LOOP KOLOM KIRI (ID 1-5) - FUNGSI LOCK
	for ($i = 1; $i <= 5; $i++ ) 
	{
		$m = $i+5;
		$chkcs = $query['cs_'.$i.''];
		$chkacct = $query['acct_'.$i.''];
		
   	if( $chkcs == 'TRUE' ){
     		echo '$( "#cs_'.$i.'" ).attr("checked",true).attr("disabled", true);';
     		echo '$( "#lbl_cs_'.$i.'" ).html("🔒 C/S '.$i.'").css({"color":"#cc0000", "font-weight":"bold"});'; 
     		echo '$( "#cs_'.$m.'" ).attr("disabled", false);';
     		echo '$( "#tgl_cs'.$i.'" ).val("'.$query['tgl_cs_'.$i].'");';	
   	}
   	
   	if( $chkacct == 'TRUE' ){
     		echo '$( "#acct_'.$i.'" ).attr("checked",true).attr("disabled", true);'; 
     		echo '$( "#lbl_acct_'.$i.'" ).html("🔒 ACCT '.$i.'").css({"color":"#cc0000", "font-weight":"bold"});'; 
     	 	echo '$( "#acct_'.$m.'" ).attr("disabled", false);';
     	 	echo '$( "#tgl_acct'.$i.'" ).val("'.$query['tgl_acct_'.$i].'");';
   	}
   	
   	echo '
   	$( "#cs_'.$i.'" ).live("change", function(e)
		{
			var date = "'.date('d-m-Y').'";
			$( "#cs_'.$i.'" ).attr("disabled", true);
			$( "#lbl_cs_'.$i.'" ).html("🔒 C/S '.$i.'").css({"color":"#cc0000", "font-weight":"bold"});
			$( "#tgl_cs'.$i.'" ).val(date);
			save_change("'.$i.'", "cs")
		})
		$( "#acct_'.$i.'" ).live("change", function(e)
		{
			var date = "'.date('d-m-Y').'";
			$( "#acct_'.$i.'" ).attr("disabled", true);
			$( "#lbl_acct_'.$i.'" ).html("🔒 ACCT '.$i.'").css({"color":"#cc0000", "font-weight":"bold"});
			$( "#tgl_acct'.$i.'" ).val(date);
			save_change("'.$i.'", "acct")
		})
		';
   }
   
   // LOOP KOLOM KANAN (ID 6-10) - FUNGSI UNLOCK
   for ($i = 6; $i <= 10; $i++ ) 
	{
		$n = $i-5; // FIX TYPO LOGIKA KOLOM KANAN
		$chechked = $query['cs_'.$i.''];
		
   	if( $chechked == 'TRUE'){
     		echo '$( "#cs_'.$i.'" ).attr("checked",true).attr("disabled", true);';
     		echo '$( "#lbl_cs_'.$i.'" ).html("🔓 C/S '.$i.'").css({"color":"#006600", "font-weight":"bold"});';
			if($query['cs_'.$n.''] == 'TRUE'){
				echo '$( "#cs_'.$n.'" ).attr("disabled", true);';
			}	
			if($query['cs_'.$n.''] == 'FALSE'){
				echo '$( "#cs_'.$n.'" ).attr("disabled", false);';
			}		
     		echo '$( "#tgl_cs'.$i.'" ).val("'.$query['tgl_cs_'.$i].'");';
   	}
   	
   	$chechkedacct = $query['acct_'.$i.''];
   	if( $chechkedacct == 'TRUE'){
     		echo '$( "#acct_'.$i.'" ).attr("checked",true).attr("disabled", true);';
     		echo '$( "#lbl_acct_'.$i.'" ).html("🔓 ACCT '.$i.'").css({"color":"#006600", "font-weight":"bold"});';
     		if($query['acct_'.$n.''] == 'TRUE'){
				echo '$( "#acct_'.$n.'" ).attr("disabled", true);';
			}	
			if($query['acct_'.$n.''] == 'FALSE'){
				echo '$( "#acct_'.$n.'" ).attr("disabled", false);';
			}
     		echo '$( "#tgl_acct'.$i.'" ).val("'.$query['tgl_acct_'.$i].'");';
   	}
   	
   	echo '
   	$( "#cs_'.$i.'" ).live("change", function(e)
		{
			var date = "'.date('d-m-Y').'";
			$( "#cs_'.$i.'" ).attr("disabled", true);
			$( "#lbl_cs_'.$i.'" ).html("🔓 C/S '.$i.'").css({"color":"#006600", "font-weight":"bold"});
			$( "#tgl_cs'.$i.'" ).val(date);
			save_change("'.$i.'", "cs")
		})
		$( "#acct_'.$i.'" ).live("change", function(e)
		{
			var date = "'.date('d-m-Y').'";
			$( "#acct_'.$i.'" ).attr("disabled", true);
			$( "#lbl_acct_'.$i.'" ).html("🔓 ACCT '.$i.'").css({"color":"#006600", "font-weight":"bold"});
			$( "#tgl_acct'.$i.'" ).val(date);
			save_change("'.$i.'", "acct")
		})
		';
   }  
echo '
})';
?>

// =====================================================================
// FIX LOCK_JOB: cek status lock_job asli dari DB setiap form dibuka/diubah.
// Buka ulang tetap lewat checklist kolom UNLOCK (kanan) supaya tercatat
// siapa yang membuka dan berapa kali.
// =====================================================================
function reopen_count(){
	// Kalau checkbox ada di layar, hitung dari layar (ikut update setelah dicentang)
	var n = 0, found = false;
	for (var i = 6; i <= 10; i++) {
		$.each(['cs','acct'], function(_, t){
			var $c = $('#' + t + '_' + i);
			if ($c.length) { found = true; if ($c.is(':checked')) n++; }
		});
	}
	return found ? n : parseInt($('#reopen-count-db').val() || '0', 10);
}

function next_unlock_slots(){
	// Slot UNLOCK pertama yang masih kosong untuk masing-masing jalur (C/S dan ACCT)
	var res = [];
	$.each(['acct','cs'], function(_, t){
		for (var i = 6; i <= 10; i++) {
			var $c = $('#' + t + '_' + i);
			if ($c.length && !$c.is(':checked')) { res.push({type:t, idx:i, $el:$c}); break; }
		}
	});
	return res;
}

function clear_unlock_highlight(){
	for (var i = 6; i <= 10; i++) {
		$('#lbl_cs_' + i + ', #lbl_acct_' + i).removeClass('unlock-next').css({'background':'', 'padding':'', 'border-radius':''});
	}
}

function refresh_lock_status(){
	$.ajax({
		type: 'POST',
		url : "<?php echo site_url('afg_c_job_sheet/check_lock')?>",
		data: { id: $('#idJob').val(), kode_ref: $('#kode_ref_form').val() },
		dataType: 'json',
		success: function(res){
			var lj = (res && res.lock_job !== undefined && res.lock_job !== null) ? String(res.lock_job) : '';
			var $b = $('#lock-status-banner'), $t = $('#lock-status-text');
			var cnt = reopen_count();
			var cntText = cnt > 0 ? ' &middot; sudah dibuka ' + cnt + 'x' : '';
			clear_unlock_highlight();

			if (lj === '1') {
				var slots = next_unlock_slots(), hint = '';
				if (slots.length) {
					var names = [];
					$.each(slots, function(_, s){
						s.$el.attr('disabled', false); // pastikan slot berikutnya bisa dicentang
						$('#lbl_' + s.type + '_' + s.idx).addClass('unlock-next').css({'background':'#fff59d', 'padding':'0 3px', 'border-radius':'3px'});
						names.push((s.type === 'acct' ? 'ACCT ' : 'C/S ') + s.idx);
					});
					hint = ' &mdash; centang <b>' + names.join('</b> atau <b>') + '</b> di kolom UNLOCK untuk membuka lagi.';
				} else if ($('#acct_6').length || $('#cs_6').length) {
					hint = ' &mdash; slot UNLOCK sudah habis (5x C/S &amp; 5x ACCT). Hubungi IT.';
				}
				$b.css({background:'#fdecea', border:'1px solid #e57373', color:'#b71c1c'}).show();
				$t.html('<b>Status DB: TERKUNCI (lock_job = 1)</b>' + cntText + hint);
			} else if (lj === '2') {
				$b.css({background:'#eafaf1', border:'1px solid #a3e4d7', color:'#1e8449'}).show();
				$t.html('<b>Status DB: TERBUKA (lock_job = 2, bypass rule 90 hari)</b>' + cntText);
			} else {
				$b.hide();
			}
		}
	});
}

$(document).ready(function(){ refresh_lock_status(); });

function save_change(id, item){
	
	// Jika ID > 5 (Kolom Kanan / Unlock)
	if(id > 5 ){
		var mode = '2'; // Status 2 (Bypass) untuk menghindari blokade rule 90 Hari
	}
	else
	{
		var mode = '1'; // Lock biasa
	}
	
	$.ajax({
		type: "POST", 
		url : "<?php echo site_url('afg_c_job_sheet/manage_data_form')?>",
		data: 
		{
			id : id, 
			item : item,
			kode_ref: $('#kode_ref_form').val(),
			spd : $('#idJob').val(),   // sp_detail_id: baris yang sama dengan yang dibaca check_lock
			mode : mode,               // info saja; server menentukan mode sendiri dari id
		},
		dataType: 'json',
		success:function(response)
		{
			if (!response || response.status != '1') {
				var m = (response && response.message) ? response.message : 'Status lock_job tidak tersimpan.';
				if (typeof Swal !== 'undefined') { Swal.fire('Gagal', m, 'error'); } else { show_messages(m); }
				refresh_lock_status();
				return;
			}

			// 1. RELOAD KEDUA JENIS GRID (Import & Export)
			if ($("#tabel_sp_detail").length) {
				$("#tabel_sp_detail").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
			}
			if ($("#tabel_sp_detail_export").length) {
				$("#tabel_sp_detail_export").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
			}

			// 1b. Cek ulang status lock_job asli dari DB
			refresh_lock_status();

			// 2. MUNCULKAN NOTIFIKASI SUKSES (Auto-close)
			var statusText = (mode === '1') ? 'Job berhasil di-LOCK (Terkunci)' : 'Job berhasil di-UNLOCK (Terbuka)';
			
			if (typeof Swal !== 'undefined') {
				Swal.fire({
					title: 'Berhasil',
					text: statusText,
					icon: 'success',
					timer: 1500,
					showConfirmButton: false
				});
			} else {
				show_messages(statusText);
			}
		},
		error:function(XMLHttpRequest)
		{
			var m = 'Gagal menyimpan status ke server';
			try { m = $.parseJSON(XMLHttpRequest.responseText).message || m; } catch(e){}
			if (typeof Swal !== 'undefined') {
				Swal.fire('Error', m, 'error');
			} else {
				show_messages(m);
			}
			refresh_lock_status();
		}
	})
}

function form_close()
{
	$( "#Form-Job-Sheet" ).fadeOut('slow', function() 
	{
		$(this).dialog('close');
	})
}

function formatUang(input) 
{
    var num = input.value.replace(/\,/g,'');
    if(!isNaN(num)){
    	if(num.indexOf('.') > -1){
    		num = num.split('.');
    		num[0] = num[0].toString().split('').reverse().join('').replace(/(?=\d*\.?)(\d{3})/g,'$1,').split('').reverse().join('').replace(/^[\,]/,'');
    		if(num[1].length > 2){
				show_messages('Only 4 decimal allowed');
    			num[1] = num[1].substring(0,num[1].length-1);
    		} 
    		input.value = num[0]+'.'+num[1];
    		perkalian_global(input); 
    	} else{ 
    	input.value = num.toString().split('').reverse().join('').replace(/(?=\d*\.?)(\d{3})/g,'$1,').split('').reverse().join('').replace(/^[\,]/,'') };
    	perkalian_global(input); 
    }
    else
    { 
		show_messages('Only numeric allowed / Hanya diperbolehkan memasukan angka');
    	input.value = input.value.substring(0,input.value.length-1);
    	perkalian_global(input) 
    }
}


function print_job_sheet(){
	var id	= $( '#idJob' ).val();
	var kode_ref = $( '#kode_ref_form' ).val();
	var tanggal  = $( '#tgl-cetak' ).val();
	var kurs    = $( '#kursJobSheet' ).val();
	
	if(tanggal=="")
	{
		$( '#tgl-cetak' ).css('border','1px solid #f00');
		show_messages('Tanggal Cetak can not empty value');
	} 
	else if (kurs=="") {
		$("input[id='kursJobSheet']").css('border','1px solid #f00');
		show_messages('Kurs can not empty value');
	}
	else
	{
		var datetgl = tanggal.substring(6,tanggal.length)+tanggal.substring(3,tanggal.length-5)+tanggal.substring(0,tanggal.length-8);
		var code = $( '#kode_ref_form' ).val();
		if (code.substr(4,3)=='300'|| code.substr(4,3)=='301'|| code.substr(4,3)=='302'|| code.substr(4,3)=='400'||code.substr(4,3)== '402'||code.substr(4,3)=='600') 
		{
		var grid = $("#tabel_sp_detail_export"), i;
		}
		else 
		{
		var grid = $("#tabel_sp_detail"), i;
		}	
		var rowid = grid.jqGrid('getGridParam', 'selrow');
		var destination_job = grid.jqGrid('getCell',rowid,'destination');
		if(destination_job === undefined || destination_job==='' || destination_job === ' '){
		var dest = "XX";
		}else{
	    var dest = destination_job;
		}
		var dataJob = 
		{
			id	: id,
			tanggal	: tanggal,
			etd_jkt	: $('#txtETDJKT').val(),
			destination	: dest,
		}
		
		$( "#Form-Print-Job" ).css('background-color', '#eee');
		$( "#Form-Print-Job" ).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
		$( "#Form-Print-Job" ).dialog(
		{
			position:'top',
			autoOpen: true,
			resizable: false,
			height: 660,
			width: 980,
			modal: true,
			title: "Print Out Job Sheet",
			close: function() 
			{	
				$(this).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
				$('#Form-Job-Sheet').dialog("close")
			}
		});
		
		$('#Form-Print-Job').dialog({title:"Print Out Job Sheet "+kode_ref,}).html('<center><iframe src="<?php echo base_url("afg_c_job_sheet/print_jobsheet") ?>/'+datetgl+'/'+kode_ref+'/'+id+'/'+true+'/'+dest+'" width=" 955px" height="610"></iframe></center>');
		
	}
}

function gubahUang(num){
	if(num.indexOf('.') > -1){
		num = num.split('.');
 		num[0] = num[0].toString().split('').reverse().join('').replace(/(?=\d*\.?)(\d{3})/g,'$1,').split('').reverse().join('').replace(/^[\,]/,'');
		return num[0];
	}
	else
	{
		return num.toString().split('').reverse().join('').replace(/(?=\d*\.?)(\d{3})/g,'$1,').split('').reverse().join('').replace(/^[\,]/,'');
	}
}

function get_proses_post(){	
	var sp_detail_id	= $( '#idJob' ).val();
	var kode_ref = $( '#kode_ref_form' ).val();
	var region_id = '<?php echo $this->session->userdata('region_id'); ?>';
	if (region_id == 'Jakarta'){
		$.ajax({
			type: "POST", 
			url : "<?php echo site_url('finance/c_post_data_acct_per_job/proses_post')?>",
			data: { 
				sp_detail_id,
				region : 'Jakarta',
				kode_ref,
			},
			success:function(response)
			{
				swal("Berhasil")
			},
			error: function(response){
				var data = $.parseJSON(response.responseText);
				if (!!data) {
				swal(data.message)}else{
				swal("Error, no conection to server!")
				}
			}
		})
		}else{
		swal("Hanya untuk Gateway Jakarta");
		}	 
}

</script>