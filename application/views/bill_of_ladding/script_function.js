<script type="text/javascript">
function pindah_job()
{
$.post('afg_c_bill_of_ladding/pindah_job',{kode_ref:$("input[id='textfield']").val(),sp_detail_id:$('#id_form').val()},'json')
	.done(function(d,s,e) {
		var data = $.parseJSON(d);
		console.log(data);
		if (data.lock == 1 || data.posted == 'Posted') {
			Swal.fire({
			icon: 'error',
			title: 'Oops...',
			text: 'Job tidak dapat dipindah!',
			footer: '<a href>Hubungi finance untuk pindah job ini</a>'
			})
		} else {
			$("#form-move").dialog({
				resizable: false,
				height: "auto",
				width: 325,
				modal: true,
				title: "Move Job " + $("input[id='textfield']").val(),
				close: function() 
				{	
					$(this).html("");
				}
			}).html("<div style=\"float: left; width: 150px\">Move to Stuffingplan</div><div style=\"float: left; width: 100px\" id=\"dropdown\"></div>"+
				"<div style=\"clear: both; height: 10px\"></div><div id=\"keterangan\"></div>")
				
			$("#dropdown").html(data.dropdown);
		}
	})

}

function move_job() {
	const kode_ref = $("input[id='textfield']").val();
	const code = $("select[id='kode_ref_opt']").val();
	const sp_detail_id = $('#id_form').val();
	const hbl = $('#bl_no').val();

	$.ajax({
		type: "POST",
		url: "<?php echo base_url('afg_c_bill_of_ladding/move_job')?>",
		data: { code, kode_ref, id: sp_detail_id },
		success: function(response) {
			const dt = $.parseJSON(response);
			const data = dt.num;
			const count = dt.numrows;
			let abjad = '';

			if (count === 0) {
				abjad = String.fromCharCode(65); // 'A'
			} else if (count >= 26) {
				abjad = 'Z' + String.fromCharCode(65 + (count - 26));
			} else {
				const used = ',' + data.join(',') + ',';
				let all = ',';
				for (let i = 0; i <= count; i++) {
					all += String.fromCharCode(65 + i) + ',';
				}
				for (let i = 0; i < all.length; i++) {
					if (!used.includes(all[i]) && all[i] !== ',') {
						abjad = all[i];
						break;
					}
				}
			}

			$("#keterangan").html(
				`Are You Sure move job <br>${kode_ref} to ${code}${abjad}
				<div style="clear: both; margin-top: 10px; margin-bottom: 10px; border-top: 1px solid #999999"></div>
				<div style="float: right">
					<input type="submit" value="MOVE" style="width: 75px" onclick="submit_moving('${abjad}','${code}','${kode_ref}','${sp_detail_id}','${hbl}')">
					<input type="submit" value="CANCEL" style="width: 75px" onclick="form_move_release()">
				</div>`
			);
		}
	});
}

function submit_moving(num, code, kode_ref, id, hbl) {
	$.ajax({
		type: "POST",
		url: "<?php echo base_url('afg_c_bill_of_ladding/submit_moving')?>",
		data: { kode_ref, code, num, id, hbl },
		success: function(response) {
			const data = typeof response === "string" ? $.parseJSON(response) : response;
			form_move_release();
			if (data.status === 1) {
				show_messages(`Move Job ${kode_ref} success to ${data.kode_ref_akhir || (code + num)}`);
			} else {
				Swal.fire({icon:"error", title:"Move Job gagal", text:data.message || "Pindah job gagal."});
				return;
			}
			$('#FORM_DISPLAY_BL').dialog('close');
		}
	});
}

function form_move_release()
{
	$('#form-move').dialog('close');
}

function get_new_num()
{
	var code = $('#txtRefno2').val();
	if (code.substr(4,3)=='300' || code.substr(4,3)=='301' || code.substr(4,3)=='302' ||code.substr(4,3)=='400' ||code.substr(4,3)=='402'||code.substr(4,3)=='600') 
	{
		var grid = $("#tabel_sp_detail_export"), i;
	}
	else 
	{
		var grid = $("#tabel_sp_detail"), i;
	}
	
	var rowid = grid.jqGrid('getDataIDs');
	var rowCount 	 = grid.getGridParam("reccount");
	var rowCount2   = rowCount-26;
	
	if (rowCount==0) 
	{
		return String.fromCharCode(65);
	}
	else if(rowCount >= 26 )
	{
		return 'Z'+String.fromCharCode(65+rowCount2);
	}
	else
	{
		var data = grid.jqGrid('getRowData');
		var num = ',';
  		for (i = 0; i < data.length; i++) 
  		{
    			num += data[i].num+',';
  		}
  		
  		var abjad = ',';
  		for (i = 0; i <= rowCount; i++) 
  		{
    		abjad += String.fromCharCode(65+i)+',';
  		}
  		
  		for (i = 0; i <= abjad.length; i++) 
  		{
    		if (num[i]!=abjad[i]) 
    		{
    			return abjad[i];	
    		}
  		}
  		
	}
}

function get_data_notify()
{
	$.ajax
		({
			type:"POST",
			url:"<?php echo site_url('afg_c_bill_of_ladding/get_data_notify'); ?>",
			data:
			{
				consignee_notify_code : $('#notify').val(),
				sp_detail_id : $('#id_form').val(),
			},
			success:function(response)
			{			
				var data = $.parseJSON(response);
				$('#textarea4').val(data.notify);
			}
	})
}

function get_data_consignee()
{
	$.ajax
		({
			type:"POST",
			url:"<?php echo site_url('afg_c_bill_of_ladding/get_data_consignee'); ?>",
			data:
			{
				consignee_notify_code : $('#consignee').val(),
				sp_detail_id : $('#id_form').val(),
			},
			success:function(response)
			{			
				var data = $.parseJSON(response);
				$('#textarea3').val(data.consignee);
			}
	})
}

/**
 * Sinkronkan Note BL HANYA ketika user memang mengubah data shipper.
 *
 * PENTING:
 * - Jangan panggil fungsi ini saat load form / pindah tab / save biasa.
 * - Nilai awal #textarea2 harus berasal dari trnstuffingplandetail.note,
 *   bukan dibentuk ulang dari master shipper.
 *
 * Dengan begitu membuka BL atau klik DETAIL B/L tidak mengubah Note.
 */
function sync_textarea2()
{
	$('#textarea2').val($('#shipperInformationName').val() + '\n' + $('#textareaShipper').val());
}

function save_front()
{

	var myData = 
	{
		detail_id: $('#id_form').val(),
		location : $('#location').val(),
		trucking_co : $('#trucking_co').val(),
		no_from_shipper:	$('#textfield13').val(),
		manifest_no:	$('#textfield14').val(),

		feeder_vessel	: $('#textfield15').val(),
		feeder_vessel_etd	: $('#textfield16').val(),
		feeder_vessel_eta	: $('#textfield17').val(),
		feeder_vessel_etd_date	: $('#textfield18').val(),
		feeder_vessel_eta_date	: $('#textfield19').val(),
		
		snd_vessel:	$('#textfield20').val(),
		snd_vessel_etd:	$('#textfield21').val(),
		snd_vessel_eta:	$('#textfield22').val(),
		snd_vessel_etd_date:	$('#textfield23').val(),
		snd_vessel_eta_date:	$('#textfield24').val(),
		
		last_vessel:	$('#textfield25').val(),
		last_vessel_etd:	$('#textfield26').val(),
		last_vessel_eta:	$('#textfield27').val(),
		last_vessel_etd_date:	$('#textfield28').val(),
		last_vessel_eta_date:	$('#textfield29').val(),
		
		last_snd_vessel:	$('#textfield30').val(),
		last_snd_vessel_etd:	$('#textfield31').val(),
		last_snd_vessel_eta:	$('#textfield32').val(),
		last_snd_vessel_etd_date:	$('#textfield33').val(),
		last_snd_vessel_eta_date:	$('#textfield34').val(),
				
		last_count : $('#textfield35').val(),
		
		date_do_release : $('#textfield36').val(),
		no_peb : $('#textfield37').val(),
		tanggal_peb : $('#textfield38').val(),
		
		//sales : $('#sales').val(),
		id_shipper : $('#shipper_id').val(),
		shipper_name : $('#shipperInformationName').val(),
		shipper_address : $('#textareaShipper').val(),
		
		mbl : $('#textfield8').val(),
		
		sp_id : $('#spId').val(),
		
	}
	if($('#textfield13').val() !=''){
		$.ajax({
		type: "POST", 
		url : "<?php echo site_url('afg_c_bill_of_ladding/save_front')?>",
		data: myData,
		success:function(XMLHttpRequest)
		{
			var res = null;
			try {
				res = $.parseJSON(XMLHttpRequest);
			} catch (e) {
				res = null; // respons dari model asli tidak selalu JSON, aman diabaikan
			}
			if (res && res.bl_locked) {
				// save_front() hanya dipanggil otomatis saat pindah tab
				// (bukan dari tombol eksplisit), jadi tidak perlu alert -
				// cukup jangan simpan perubahan. Note tidak disentuh oleh save_front().
				return;
			}
		}
	})
	}else{
			alert('Nomor SI tidak boleh kosong');
			return false;
		}
	
}

function save_detail()
{
	// Jangan membentuk ulang Note di sini. #textarea2 adalah nilai BL yang
	// benar-benar sedang ditampilkan; ia hanya berubah saat user mengubah shipper.
	var arg = arguments[0];
	var myData = 
	{
		sp_detail_id: $('#id_form').val(),	
		sp_id : $('#spId').val(),
		
		desc_goods:$('#textarea5').val(),
		note : $('#textarea2').val().toUpperCase(),
		marking_nos  : $('#textarea7').val(),
		
		as_agent : $('#textfield57').val(),		
		backup_date  : $('#textfield55').val(),
		bl_type  : $('#textfield56').val(),
		
		//express_release				: 	$('#express_release').attr('checked'),
		//shipped_on_board				:	$('#shipped_on_board').attr('checked'),
		//net_weight_tidak_dicetak	:	$('#net_weight_tidak_dicetak').attr('checked'),
		//cetak_jml_doc_origi			:	$('#cetak_jml_doc_origi').attr('checked'),
		
		//cy_cy : $('#cy_cy').attr('checked'),
		//cfs_cfs : $('#cfs_cfs').attr('checked'),
		
		payable		: $('#textfield63').val(),
		as_arange  : $('#textarea7').val(),
		
		diff_status_1 : $('#diff_status_1').val(),
		diff_status_2 : $('#diff_status_2').val(),
		hb_l : $('#bl_no').val(),
		
		consignee : $('#consignee').val(),
		consignee_add : $('#textarea3').val(),
		notify : $('#notify').val(),
		notify_add : $('#textarea4').val(),
		
		port_of_loading : $('#textfield44').val().toUpperCase(),
		port_of_discharge : $('#textfield45').val().toUpperCase(),
		port_of_receive : $('#textfield46').val().toUpperCase(),
		port_of_delivery : $('#textfield47').val().toUpperCase(),
		
		netto : $('#textfield59').val().replace(/\s/g,''),
		job_no : $('#textfield').val()
		
	}
	
	$("input[class='checkbox_bill']").each(function() {
		var val=$(this).attr('checked');
		if (val=='checked') { chk = true } else { chk = false}
    	myData[$(this).attr("id")] = chk;
	});
		
	$.ajax({
		type: "POST", 
		url : "<?php echo site_url('afg_c_bill_of_ladding/save_detail')?>",
		data: myData,
		success:function(XMLHttpRequest)
		{
			var res = null;
			try {
				res = $.parseJSON(XMLHttpRequest);
			} catch (e) {
				res = null; // respons dari model asli tidak selalu JSON, aman diabaikan
			}
			if (res && res.bl_locked) {
				// panggilan silent (arg=0, dari CETAK BL / FINAL BL) tidak perlu
				// menginterupsi user dgn alert; hanya tombol SAVE (arg=1) yg tampilkan.
				if (arg == 1) {
					Swal.fire({
						icon: 'error',
						title: 'Tidak bisa disimpan',
						text: res.message || 'BL sudah FINAL'
					});
				}
				return;
			}
			if (arg == 1) {
				Swal.fire({
				  position: 'top-end',
				  icon: 'success',   // gunakan 'icon' bukan 'type'
				  title: 'Your work has been saved',
				  showConfirmButton: false,
				  timer: 1500
				});
			}
		}
	})
}

/*
function update_note()
{
	$('#textarea2').val($('#shipperInformationName').val()+'\n'+$('#textareaShipper').val());
	save_front();
}
*/

$(document).ready(function()
{
	
	$('input[type="text"], textarea').on('input', function() {
		this.value = this.value.toUpperCase();
	});
	collect_data();
	var rowid = $('#id_form').val();
	var isLock = $('#tabel_sp_detail').jqGrid('getCell',rowid,'isLock');

	if(isLock==1){
		 $('#tabs-lading').find('input, textarea, select').attr("disabled", 'disabled');
		  $('#cetak_form_bl').prop("disabled", false);
			}		

	check_bl_status_on_load();

	// Sinkronkan Note hanya karena aksi user pada field shipper.
	// jQuery .val(...) saat collect_data() tidak memicu event input/change,
	// jadi load form tidak akan mengubah Note secara otomatis.
	$('#shipperInformationName, #textareaShipper').on('input change', sync_textarea2);

	$('#tabs-lading .the-tab').hide();
	$('#tabs-lading div:first').show();
	$('#tabs-lading ul li:first').addClass('active');
 
	$('#tabs-lading ul li a').click(function(){
		$('#tabs-lading ul li').removeClass('active');
		$(this).parent().addClass('active');
		var currentTab = $(this).attr('href');
		$('#tabs-lading .the-tab').hide();
		$(currentTab).show();
		return false;
	});
	
	$("#textfield2").datepicker({dateFormat : "dd/mm/yy"});
	$("#textfield18").datepicker({dateFormat : "dd/mm/yy"});
	$("#textfield19").datepicker({dateFormat : "dd/mm/yy"});
	$("#textfield23").datepicker({dateFormat : "dd/mm/yy"});
	$("#textfield24").datepicker({dateFormat : "dd/mm/yy"});
	$("#textfield28").datepicker({dateFormat : "dd/mm/yy"});
	$("#textfield29").datepicker({dateFormat : "dd/mm/yy"});
	$("#textfield33").datepicker({dateFormat : "dd/mm/yy"});
	$("#textfield34").datepicker({dateFormat : "dd/mm/yy"});
	$("#textfield36").datepicker({dateFormat : "dd/mm/yy"});
	$("#textfield38").datepicker({dateFormat : "dd/mm/yy"});
	$("#textfield55").datepicker({dateFormat : "dd/mm/yy"});
	$("#delivery_date").datepicker({dateFormat : "dd/mm/yy"});
	
	$("#textfield59").attr({'onChange':'format_uang(this)','onKeyUp':'format_uang(this)'});
	
	var cls_date = $("input[id='txtClosingDate']").val();
	var date = "<?= date('d/m/Y') ?>";
	
	if (date >= cls_date) {
		$("input[name='btnPindah']").attr("hidden",false);
	}
	else {
		$("input[name='btnPindah']").attr("hidden",false);
	}

})

function edit_detail()
{
		var rowid = arguments[0];
		var kode_ref = $('#tabel_sp_detail').jqGrid('getCell',rowid,'kode_ref');
		
		$("#FORM_DISPLAY_BL").dialog('close');
		
		$("#Form-Input-Detail").css('background-color','#eef').css('font-size','#11px');
		$("#Form-Input-Detail").html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
		$("#Form-Input-Detail").dialog(
		{
			resizable: false,
			height: 510,
			width: 800,
			modal: true,
			title: "Edit manual request stuffing "+kode_ref,
			close: function() 
			{	
				enabled_top_button();
				remove_detail();
				$(this).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
				$("#tabel_sp_detail").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
			}
		});
	
		$.ajax({
			type: "POST", 
			url : "<?php echo base_url('afg_c_stuffing_plan/open_form_detail')?>",
			data: { id : rowid, mode:"edit" },
			success:function(XMLHttpRequest)
			{
				$('#Form-Input-Detail').html(XMLHttpRequest);
			}
		})
}

function set_value_img() 
{
	var value = $("#select3").val();
	var rowid = $('#id_form').val();
	$.ajax({
		type: "POST", 
		url : "<?php echo site_url('afg_c_bill_of_ladding/set_value_img')?>",
		data: { id_bl: rowid, value: value  },
		success:function(data)
		{
			var data = $.parseJSON(data);
		}
	})
}

function collect_data()
{
	var code = $('#txtRefno2').val();
	var rowid = $('#id_form').val();
	 
	if (code.substr(4,3)=='300' || code.substr(4,3)=='301' || code.substr(4,3)=='302' ||code.substr(4,3)=='400'||code.substr(4,3)=='402'||code.substr(4,3)=='600')
	{
		var grid = $("#tabel_sp_detail_export"), i;
		var kode_ref = grid.jqGrid('getCell',rowid,'kode_ref');
		var destination = grid.jqGrid('getCell',rowid,'destination');
		var transhipment = $("#dropdownTranshipmentPort").val();
	}	else {
		var grid = $("#tabel_sp_detail"), i;		
		var kode_ref = grid.jqGrid('getCell',rowid,'kode_ref');
		var destination = grid.jqGrid('getCell',rowid,'destination');
		var transhipment = $("#dropdownTranshipmentPort").val();
	}
	
	$.ajax
		({
			type:"POST",
			url:"<?= base_url('afg_c_bill_of_ladding/collect_data'); ?>",
			data:
			{
				spd : $('#id_form').val(),
				//destination : $('#textfield43').val(),
				destination : destination,
				kode_ref : kode_ref,
				transhipment:transhipment,
			},
			success:function(response)
			{			
				var data = $.parseJSON(response);
				
				var mbl = data.detail["mbl"];
			
				
				var destination = data.stuffing["destination"];  
				if(destination==''){
					var destination = $('#dropdownCountries').val();
				}
				var atd_pol = data.detail["feeder_vessel_etd_date"];
				if (atd_pol ==""){
				var atd_pol=$('#txtATD').val();
				}
				var ata_pol = data.detail["feeder_vessel_eta_date"];
				if (ata_pol ==""){
				var ata_pol = $('#txtATA').val();
				}
				var atd_trans = data.detail["snd_vessel_etd_date"];
				if (atd_trans ==""){
				var atd_trans="";
				}
				var ata_dest = data.detail["snd_vessel_etd_date"];
				if (ata_dest ==""){
				var ata_dest = "";
				}
				if ($('#dropdownTranshipmentPort').val()=='')
				{
				$('#textfield17').val($('#dropdownCountries').val());
				$('#textfield21').val($('#dropdownTranshipmentPort').val());
				$('#textfield18').val(atd_pol);
				$('#textfield19').val(ata_pol);
				}else if ($('#dropdownTranshipmentPort').val()==$('#dropdownCountries').val()){
				$('#textfield17').val($('#dropdownCountries').val());
				$('#textfield21').val($('#dropdownTranshipmentPort').val());
				$('#textfield22').val(destination);
				$('#textfield18').val(atd_pol);
				$('#textfield19').val(ata_pol);
				}else{
				$('#textfield17').val($('#dropdownTranshipmentPort').val());
				$('#textfield21').val($('#dropdownTranshipmentPort').val());
				$('#textfield22').val(destination);
				$('#textfield18').val(atd_pol);
				$('#textfield24').val(ata_pol);
				}
				var port_of_loading = data.detail["port_of_loading"];
				var port_of_discharge = data.detail["port_of_discharge"];
				var port_of_receive = data.detail["port_of_receive"];
				var port_of_delivery = data.detail["port_of_delivery"];
				
				if (port_of_loading == '') { port_of_loading = $('#dropdownPortLoading').val() }
				if (port_of_discharge == '') { port_of_discharge = destination }
				if (port_of_receive == '') { port_of_receive = $('#dropdownPortLoading').val() }
				if (port_of_delivery == '') { port_of_delivery = destination }
				
				if (data.stuffing["no_pack_unit"] != 'false' ) {
					no_of_pack = data.stuffing["no_pack_qty"]+' '+data.stuffing["no_pack_unit"].toUpperCase();
				}
				
				//$('#textfield19').val($('#txtStuffingDate').val());
				
				$('#textfield43').val(destination);
				$('#textfield6').val(destination);
				$('#textfield8').val(mbl);

				if (data.stuffing["hbl"]=="F") {
					$('#bl_no').val(mbl);
				}
				else 
				{
					$('#bl_no').val(data.hb_l);
				}
				
				$('#textfield').val(kode_ref);
				$('#textfield2').val($('#txtETDJKT').val());
				$('#textfield3').val($('#txtKurs').val());	
				$('#textfield5').val($('#txtCarrier').val());
				$('#textfield15').val($('#txtVessel').val()+' '+$('#txtVoyage').val());	
				$('#textfield39').val(kode_ref);	
				$('#textfield48').val($('#txtVessel').val());
				$('#textfield49').val($('#txtVoyage').val());
				$('#textfield52').val($('#txtContSealNumber').val());
				$('#textfield54').val($('#txtETDJKT').val());
				
				$('#textfield7').val(data.stuffing["agent_code"]);
				$('#textarea7').val(data.stuffing["marking_nos"]);	
				$('#textfield58').val(data.stuffing["weight"]);			
				$('#textfield59').val(data.stuffing["netto"]);	
				$('#textfield60').val(data.stuffing["bl_meas"]);
				$('#textfield62').val(data.stuffing["freight"]);
				$('#textfield64').val(data.stuffing["doc"]);
				$('#textarea3').val(data.stuffing["consignee_add"]);
				$('#textarea4').val(data.stuffing["notify_add"]);	
				$('#textarea5').val(data.stuffing["desc_goods"]);
				
				$('#textfield13').val(data.detail["no_from_shipper"]);
				$('#textfield14').val(data.detail["manifest_no"]);
				$('#textfield16').val($('#dropdownPortLoading').val());
				
				$('#textfield20').val(data.detail["snd_vessel"]);
				$('#textfield23').val(atd_trans);	
				$('#textfield25').val(data.detail["last_vessel"]);
				$('#textfield26').val(data.detail["last_vessel_etd"]);
				$('#textfield27').val(data.detail["last_vessel_eta"]);
				$('#textfield28').val(data.detail["last_vessel_etd_date"]);
				$('#textfield29').val(data.detail["last_vessel_eta_date"]);		
				$('#textfield30').val(data.detail["last_snd_vessel"]);
				$('#textfield31').val(data.detail["last_snd_vessel_etd"]);
				$('#textfield32').val(data.detail["last_snd_vessel_eta"]);
				$('#textfield33').val(data.detail["last_snd_vessel_etd_date"]);
				$('#textfield34').val(data.detail["last_snd_vessel_eta_date"]);	
				$('#textfield35').val(data.detail["last_count"]);	
				$('#textfield36').val(data.detail["date_do_release"]);
				$('#textfield37').val(data.detail["no_peb"]);
				$('#textfield38').val(data.detail["tanggal_peb"]);		
				$('#textfield55').val(data.detail["backup_date"]);
				$('#textfield56').val(data.detail["bl_type"]);
				$('#textfield57').val(data.detail["as_agent"]);
				$('#textfield63').val(data.detail["payable"]);
				$('#diff_status_1').val(data.detail["diff_status_1"]);
				$('#diff_status_2').val(data.detail["diff_status_2"]);
				$('#textRequestId').val(data.detail["id_request"]);
				
				$('#shipperInformationName').val(data.detail["shipper_name"]);
				$('#textareaShipper').val(data.detail["shipper_address"]);

				// SOURCE OF TRUTH untuk Note saat membuka BL adalah nilai yang sudah
				// tersimpan di trnstuffingplandetail.note. Jangan generate ulang dari
				// shipper_name + shipper_address hanya karena form dibuka.
				$('#textarea2').val($('#shipperInformationName').val()+'\n'+$('#textareaShipper').val());
							
				$('#textfield44').val(port_of_loading);
				$('#textfield45').val(port_of_discharge);
				$('#textfield46').val(port_of_receive);
				$('#textfield47').val(port_of_delivery);
				$('#textfield61').val(no_of_pack);
				
				if (data.stuffing["express_release"]=="TRUE") {
					$('#express_release').attr('checked',true)
				} else{
				$('#cetak_jml_doc_origi').attr('checked',true)	
				}
				if (data.stuffing["shipped_on_board"]=="TRUE") {
					$('#shipped_on_board').attr('checked',true)
				}
				if (data.stuffing["net_weight_tidak_dicetak"]=="TRUE") {
					$('#net_weight_tidak_dicetak').attr('checked',true)
				}
				if (data.stuffing["cetak_jml_doc_origi"]=="TRUE") {
					$('#cetak_jml_doc_origi').attr('checked',true)
				}
	
				if (data.detail["cy_cy"]=="TRUE") {
					$('#cy_cy').attr('checked',true)
				}
				if (data.detail["cfs_cfs"]=="TRUE") {
					$('#cfs_cfs').attr('checked',true)
				}
			}
		})
}


function get_data_form()
{
	$.ajax
		({
			type:"POST",
			url:"<?php echo site_url('afg_c_bill_of_ladding/get_data_form'); ?>",
			data:
			{
				id_shipper : $('#shipper').val(),
			},
			success:function(response)
			{			
				var data = $.parseJSON(response);
				$('#shipperInformationName').val(data.nama);
				$('#textareaShipper').val(data.alamat);
				sync_textarea2();
			}
	})
}

function nama_agent()
{
	
	$( '#loading-bl' ).ajaxStart(function(){
		$(this).fadeIn();
		$(this).html('<img src="<?php echo base_url("public/images/loader.gif")?>"/>');
		}).ajaxStop(function(){
		$(this).fadeOut();
	});
	
	$("#agent-insert").html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
	
	$("#agent-insert").dialog(
	{
		resizable: false,
		height:320,
		width: 440,
		modal: true,
		title:"SPECIAL INSTRUCTION + AGENT + REMARKS",
		close: function()  {	$(this).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>') }
	})
	
	var rowid = $('#id_form').val();
	var code  = $('#tabel_sp_detail').jqGrid('getCell',rowid,'agent_code')
	$.ajax({
		type: "POST", 
		url : "<?php echo site_url('afg_c_bill_of_ladding/nama_agent')?>",
		data: { code: code },
		success:function(XMLHttpRequest)
		{
			$("#agent-insert").html(XMLHttpRequest)
		}
	})
}

function cetak_bl_form()
{
	save_detail(0);
	$("#print-option").dialog(
	{
		autoOpen: true,
		resizable: false,
		height:150,
		width: 380,
		modal: true,
		title:"PRINT OPTIONS",
		buttons:
		{
			"PRINT": function() 
			{
				if($('#print-opt').val() == 0 )
				{
					$('#print-opt').focus()
				}
				else
				{
					get_bl_data("print");
					$(this).dialog( 'close' )
				}
			},
			"SEND TO SHIPPER": function() 
			{
				if(!$('#print-opt').val())
				{
					$('#print-opt').focus()
				}
				else
				{
					get_bl_data("send");
					$(this).dialog( 'close' )
				}
			}
		},
		close: function() {	 
		}
	});
	$("#print-option").html('<div id="msg-print" style="color:#f00; padding-bottom: 10px; font-size: 10px; font-style: italic"></div>'+
		'Print Option : '+
		'<select id="print-opt" style="font-size: 11px; width: 100px; margin-top: 10px;" name="pilih">'+
		'<option value=""></option><option value="FALSE">Cetak Polos</option>'+
		'<option value="TRUE">Form B L</option></select>'
	);
}

function get_bl_data(type)
{
	var form_id = $( '#id_form' ).val();
	$.ajax
	({
		type: "POST",
		url : "<?php echo base_url('afg_c_bill_of_ladding/get_bl_data')?>",
		data:	{ id_form : form_id },
		success: function(response)
		{
			var data = $.parseJSON(response);
			if(data.status == 1 )
			{
				print_out_bl(type);
			}
			else
			{
			//	manage_bl_data('add')
				alert("DATA ERROR")
			}
		}
	})
}

function print_out_bl(type)
{
	
	var id = $( '#id_form' ).val();
	var FormJob = $('#textfield' ).val();
	var FormBL 	= $('#bl_no').val();
	var FormImg = $('#print-opt').val();
	var ImgBL	= $('#select3').val();
	var grid = $("#tabel_sp_detail"), i;
	var spid = $("#spId").val();
	

	
	if(FormBL == '')
	{
		$('#PRINT-FORM-BL').dialog( { position: "top", title: "BILL OF LADING "+FormBL } );
		$('#PRINT-FORM-BL').html('<center><h1 style="color:#f00;">Empty BL Number, Output cannot be send</h1></center>');		
	}
	else
	{
	 if(type == "print"){
		 $('#PRINT-FORM-BL').dialog( { position: "top", title: "BILL OF LADING "+FormBL, width: "50%", maxWidth: "855px" } ).html('<center><object data="<?php echo base_url("afg_c_bill_of_ladding/print_out_bl?spd='+id+'&bl='+FormBL+'&sp='+spid+'&cetak='+FormImg+'") ?>" width="955px" height="620" type="application/pdf"></center>');

	 	//window.open('<?php echo base_url("afg_c_bill_of_ladding/print_out_bl?email=FALSE&spd='+id+'&bl='+FormBL+'&sp='+spid+'&cetak='+FormImg+'") ?>', '_blank');
	 }else if(type == "send"){
		$.ajax
		({
			type: "GET",
			url : "<?php echo base_url('afg_c_bill_of_ladding/print_out_bl')?>?spd="+id+"&bl="+FormBL+"&sp="+spid+"&cetak="+FormImg+"&email=TRUE",
			success: function(data)
			{
				$('#PRINT-FORM-BL').html("SUCCESS");
			},
			error:function(XMLHttpRequest)
			{
				$('#PRINT-FORM-BL').dialog( { title: "BILL OF LADING ERROR" } );
				$('#PRINT-FORM-BL').html(XMLHttpRequest.responseText)
			}
		})
	 }
	}
}

function save_x_tracking()
{
	$( '#loading-bl' ).ajaxStart(function(){
		$(this).fadeIn();
		$(this).html('<img src="<?php echo base_url("public/images/loader.gif")?>"/>');
		}).ajaxStop(function(){
		$(this).fadeOut();
	});
	var rowid = $('#id_form').val();
	var code = $('#txtRefno2').val();
	if (code.substr(4,3)=='300' || code.substr(4,3)=='301' || code.substr(4,3)=='302' ||code.substr(4,3)=='400' ||code.substr(4,3)=='402'||code.substr(4,3)=='600')
	{
		var grid = $("#tabel_sp_detail_export"), i;
		var bl_no = grid.jqGrid('getCell',rowid,'hb_l');
		var url   =  "<?php echo site_url('afg_c_trucking/manage_tracking')?>";
	}
	else 
	{
		var grid = $("#tabel_sp_detail"), i;
		var bl_no = $('#bl_no').val();
		var connecting = $('#textfield20').val();
		var url   =  "<?php echo site_url('afg_c_trucking_export/manage_tracking_export')?>";
	}
	var data = grid.jqGrid('getRowData');
	var myData = 
	{
			id_shipper: $('#shipperInformation').val(),
			id_request: $('#textRequestId').val(),
			sp_detail_id: $('#id_form').val(),
			hbl_no : bl_no,
			port_of_destination : $('#dropdownCountries').val(),
			feeder_name: $('#textfield15').val(),
			connecting_name: connecting,
			etd: $('#textETDJKT').val(),
			atd: '00/00/0000',
			eta: $('#textETA').val(),
			actual_eta: '00/00/0000',
			destination: $('#dropdownCountries').val(),
			pickup_do: '00/00/0000',
			pickup_cargo: '00/00/0000',
			container_no: $('#textfield52').val(),
			id_agent: $('#dropdownAgent').val(),
			mbl_no: $('#textfield8').val(),
			oper:"add",
	}
	
	$.ajax
	({
		type: "POST",
		url : url,
		data : myData,
		success: function()
		{
			$( '#FORM_DISPLAY_BL' ).fadeOut('slow', function() 
			{
				$("#FORM_DISPLAY_BL").dialog('close');
			});
			show_messages('Data tracking table saved');
		},
		error:function(XMLHttpRequest)
		{
			$( '#FORM_DISPLAY_BL' ).fadeOut('slow', function() 
			{
				$("#FORM_DISPLAY_BL").dialog('close');
			});
			
			var hbl_no = $('#bl_no').val();
			show_messages('Data tracking '+hbl_no+' has been update');
		}
	})
}



// ============================================================
// FINAL BL & HISTORY
// ============================================================

function check_bl_status_on_load()
{
	var spd = $('#id_form').val();
	if (!spd) { return; }

	$.post("<?php echo site_url('afg_c_bill_of_ladding/get_bl_status') ?>", { spd: spd }, function(response) {
		var data = $.parseJSON(response);
		lock_bl_form(data.is_final == 1, data);
	});
}

function lock_bl_form(isFinal, statusData)
{
	if (isFinal) {
		$('#tabs-lading').find('input, textarea, select')
			.not('#btn_bl_history, #btn_batal_final, #btn_delivered')
			.attr('disabled', 'disabled');

		$('#btn_final_bl').hide();
		$('#btn_batal_final').show();
		$('#btn_delivered').show();
		$('#cetak_form_bl').prop('disabled', false);

		if (statusData) {
			$('#bl_final_badge')
				.text('FINAL oleh ' + statusData.finalized_by + ' pada ' + statusData.finalized_at)
				.show();
		}
	} else {
		$('#tabs-lading').find('input, textarea, select').removeAttr('disabled');
		$('#btn_final_bl').show();
		$('#btn_batal_final').hide();
		$('#btn_delivered').hide();
		$('#bl_final_badge').hide();
	}

	if (statusData && statusData.delivered_at) {
		$('#bl_delivered_badge')
			.text('Diserahkan kepada ' + statusData.delivered_to + ' (' + statusData.delivered_via + ') pada ' + statusData.delivered_at)
			.show();
	} else {
		$('#bl_delivered_badge').hide();
	}
}

function final_bl()
{
	var spd = $('#id_form').val();

	Swal.fire({
		icon: 'warning',
		title: 'Final kan BL ini?',
		text: 'Setelah FINAL, semua isian form tidak bisa diubah sampai dibatalkan.',
		showCancelButton: true,
		confirmButtonText: 'Ya, FINAL kan',
		cancelButtonText: 'Batal'
	}).then(function(result) {
		if (result.isConfirmed) {
			save_detail(0); // simpan dulu perubahan terakhir sebelum final
			$.post("<?php echo site_url('afg_c_bill_of_ladding/finalize_bl') ?>", { spd: spd }, function(response) {
				var data = $.parseJSON(response);
				if (data.status == 1) {
					lock_bl_form(true, data.data);
					show_messages('BL berhasil di-FINAL-kan');
				} else {
					Swal.fire('Gagal', 'BL tidak berhasil di-FINAL-kan', 'error');
				}
			});
		}
	});
}

function batal_final_bl()
{
	var spd = $('#id_form').val();

	Swal.fire({
		icon: 'warning',
		title: 'Batalkan Final BL?',
		text: 'Form akan kembali bisa diedit.',
		showCancelButton: true,
		confirmButtonText: 'Ya, Batalkan',
		cancelButtonText: 'Tidak'
	}).then(function(result) {
		if (result.isConfirmed) {
			$.post("<?php echo site_url('afg_c_bill_of_ladding/cancel_final_bl') ?>", { spd: spd }, function(response) {
				var data = $.parseJSON(response);
				if (data.status == 1) {
					lock_bl_form(false, data.data);
					show_messages('Final BL dibatalkan, form bisa diedit kembali');
				} else {
					Swal.fire('Gagal', 'Final BL tidak berhasil dibatalkan', 'error');
				}
			});
		}
	});
}

function show_delivery_form()
{
	$('#delivery_date').val('');
	$('#delivered_to').val('');
	$('#delivered_via').val('');

	$('#bl-delivery-dialog').dialog({
		title: 'Tandai Diserahkan ke Customer',
		width: 380,
		height: 'auto',
		modal: true,
		resizable: false
	});

	// default tanggal hari ini
	$('#delivery_date').val("<?= date('d/m/Y') ?>");
}

function submit_delivery()
{
	var spd = $('#id_form').val();
	var delivery_date = $('#delivery_date').val();
	var delivered_to  = $('#delivered_to').val();
	var delivered_via = $('#delivered_via').val();

	if (!delivery_date || !delivered_to || !delivered_via) {
		Swal.fire('Lengkapi dulu', 'Tanggal, Kepada, dan Via wajib diisi.', 'warning');
		return;
	}

	$.post("<?php echo site_url('afg_c_bill_of_ladding/mark_delivered_bl') ?>", {
		spd: spd,
		delivery_date: delivery_date,
		delivered_to: delivered_to,
		delivered_via: delivered_via
	}, function(response) {
		var data = $.parseJSON(response);
		if (data.status == 1) {
			$('#bl-delivery-dialog').dialog('close');
			lock_bl_form(true, data.data);
			show_messages('BL ditandai sudah diserahkan ke customer');
		} else {
			Swal.fire('Gagal', data.message || 'Tidak berhasil disimpan', 'error');
		}
	});
}

function show_bl_history()
{
	var spd = $('#id_form').val();

	$.post("<?php echo site_url('afg_c_bill_of_ladding/get_bl_history') ?>", { spd: spd }, function(response) {
		var data = $.parseJSON(response);
		render_bl_history(data);
	});
}

function render_bl_history(data)
{
    var s = data.status || {};
    var rows = data.history || [];
    var deliveries = data.deliveries || [];

    var html = `
    <style>

    .bl-history-wrapper{
        font-family: Arial, Helvetica, sans-serif;
        font-size:12px;
    }

    .bl-card{
        background:#f8f9fa;
        border:1px solid #dce3e8;
        border-radius:6px;
        padding:12px;
        margin-bottom:12px;
    }

    .bl-card-title{
        color:#1976d2;
        font-weight:bold;
        margin-bottom:8px;
    }

    .bl-history-table{
        width:100%;
        border-collapse:collapse;
        font-size:11px;
    }

    .bl-history-table th{
        background:#1976d2;
        color:#fff;
        padding:8px;
        border:1px solid #d7d7d7;
        text-align:left;
    }

    .bl-history-table td{
        border:1px solid #d7d7d7;
        padding:6px;
        vertical-align:top;
    }

    .bl-history-table tr:nth-child(even){
        background:#f7f9fc;
    }

    .bl-history-table tr:hover{
        background:#eef6ff;
    }

    .action-badge{
        display:inline-block;
        padding:3px 8px;
        border-radius:12px;
        color:#fff;
        font-size:10px;
        font-weight:bold;
    }

    .badge-created{background:#28a745;}
    .badge-edited{background:#2196f3;}
    .badge-final{background:#ff9800;}
    .badge-cancel{background:#dc3545;}
    .badge-delivered{background:#6f42c1;}

    .value-box{
        max-width:300px;
        max-height:120px;
        overflow:auto;
        white-space:pre-wrap;
        word-break:break-word;
        background:#fafafa;
        border:1px solid #e0e0e0;
        padding:5px;
        border-radius:4px;
    }

    .empty-history{
        text-align:center;
        padding:20px;
        color:#999;
    }

    </style>

    <div class="bl-history-wrapper">
    `;

    html += `
    <div class="bl-card">
        <div class="bl-card-title">📄 Informasi BL</div>

        <table style="width:100%">
            <tr>
                <td width="120"><b>Dibuat Oleh</b></td>
                <td>${s.created_by || '-'}</td>
            </tr>

            <tr>
                <td><b>Tanggal</b></td>
                <td>${s.created_at || '-'}</td>
            </tr>

            <tr>
                <td><b>Final Oleh</b></td>
                <td>${s.finalized_by || '-'}</td>
            </tr>

            <tr>
                <td><b>Tanggal Final</b></td>
                <td>${s.finalized_at || '-'}</td>
            </tr>
        </table>
    </div>
    `;

    if(deliveries.length > 0)
    {
        html += `
        <div class="bl-card">

            <div class="bl-card-title">
                🚚 Riwayat Penyerahan Customer
            </div>

            <table class="bl-history-table">
                <thead>
                    <tr>
                        <th width="120">Tanggal</th>
                        <th>Kepada</th>
                        <th width="120">Via</th>
                        <th width="150">Dicatat Oleh</th>
                    </tr>
                </thead>
                <tbody>
        `;

        $.each(deliveries,function(i,d){

            html += `
                <tr>
                    <td>${d.delivery_date}</td>
                    <td>${d.delivered_to}</td>
                    <td>${d.delivered_via}</td>
                    <td>${d.recorded_by}</td>
                </tr>
            `;

        });

        html += `
                </tbody>
            </table>
        </div>
        `;
    }

    html += `
    <div class="bl-card">

        <div class="bl-card-title">
            📝 Histori Perubahan BL
        </div>

        <table class="bl-history-table">
            <thead>
                <tr>
                    <th width="130">Tanggal</th>
                    <th width="90">Aksi</th>
                    <th width="160">Field</th>
                    <th>Dari</th>
                    <th>Menjadi</th>
                    <th width="140">User</th>
                </tr>
            </thead>
            <tbody>
    `;

    if(rows.length === 0)
    {
        html += `
            <tr>
                <td colspan="6" class="empty-history">
                    Belum ada histori perubahan
                </td>
            </tr>
        `;
    }

    $.each(rows,function(i,row){

        var badgeClass = '';

        switch(row.action)
        {
            case 'created':
                badgeClass = 'badge-created';
                break;

            case 'edited':
                badgeClass = 'badge-edited';
                break;

            case 'finalized':
                badgeClass = 'badge-final';
                break;

            case 'cancel_final':
                badgeClass = 'badge-cancel';
                break;

            case 'delivered':
                badgeClass = 'badge-delivered';
                break;
        }

        var actionLabel = {
            created      :'Dibuat',
            edited       :'Diedit',
            finalized    :'Final',
            cancel_final :'Batal Final',
            delivered    :'Diserahkan'
        }[row.action] || row.action;

        html += `
        <tr>

            <td>${row.performed_at}</td>

            <td>
                <span class="action-badge ${badgeClass}">
                    ${actionLabel}
                </span>
            </td>

            <td>
                ${row.field_label || '-'}
            </td>

            <td>
                <div class="value-box">
                    ${row.old_value || '-'}
                </div>
            </td>

            <td>
                <div class="value-box">
                    ${row.new_value || '-'}
                </div>
            </td>

            <td>
                ${row.performed_by}
            </td>

        </tr>
        `;
    });

    html += `
            </tbody>
        </table>

    </div>
    </div>
    `;

    $('#bl-history-dialog')
    .html(html)
    .dialog({
        title: 'History Bill Of Lading',
        width: 1200,
        height: 700,
        modal: true,
        resizable: true
    });
}

</script>