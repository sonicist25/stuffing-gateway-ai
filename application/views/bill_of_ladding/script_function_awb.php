<script type="text/javascript">
function pindah_job()
{
$.post('afg_c_bill_of_ladding/pindah_job',{kode_ref:$("input[id='textfield']").val()},'json')
	.done(function(d,s,e) {
		var data = $.parseJSON(d);
		if (data.lock == 1 ) {
			Swal.fire({
			type: 'error',
			title: 'Oops...',
			text: 'Job sudah dibuat invoice, tidak dapat dipindah!',
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

function move_job()
{
	var kode_ref = $("input[id='textfield']").val()
	var code = $("select[id='kode_ref_opt']").val();
	var sp_detail_id =$('#id_form').val();
	$.ajax({
		type: "POST", 
		url : "<?php echo base_url('afg_c_bill_of_ladding/move_job')?>",
		data: { code : code, kode_ref: kode_ref, id : sp_detail_id },
		success:function(response)
		{			
			var dt = $.parseJSON(response);
			var number = dt.num;
			var rowCount = dt.numrows;
			
			var count=dt.numrows;
			var count2=count-26;
			var data=dt.num;
			
			if (count==0){
				var abjad = String.fromCharCode(65)
			}
			else if(count >=26){
				var abjad = 'Z'+String.fromCharCode(65+count2)
			}
			else {
				var num = ',';
  				for (i=0;i<data.length;i++){
  					num += data[i]+','
  				}
  			
  			
  				var abjad = ',';
  				for (x=0;x<=count;x++){
  					abjad += String.fromCharCode(65+x)+',';
  				}
  			
  				for (y=0;y<= abjad.length;y++){
  					if (num[y]!=abjad[y]){
  						var abjad = abjad[y];
  					}
  				}
  			}
  			
  			console.log(abjad)
			
	
  			
  			$("#keterangan").html("Are You Sure move job <br>"+kode_ref+" to "+code+""+abjad+
  			"<div style=\"clear: both; margin-top: 10px; margin-bottom: 10px; border-top: 1px solid #999999\"></div><div style=\"float: right\">"+
  			"<input type=\"submit\" value=\"MOVE\" style=\"width: 75px\" onclick=\"submit_moving('"+abjad+"','"+code+"','"+kode_ref+"','"+sp_detail_id+"')\">"+
  			"<input type=\"submit\" value=\"CANCEL\" style=\"width: 75px\" onclick=\"form_move_release()\"></div>")
		}
   })
}

function submit_moving()
{
	var kode_ref = arguments[2];
	var code = arguments[1];
	var num = arguments[0];
	var id = arguments[3];
	$.ajax({
		type: "POST", 
		url : "<?php echo base_url('afg_c_bill_of_ladding/submit_moving')?>",
		data: { kode_ref : arguments[2], code : arguments[1], num : arguments[0], id : arguments[3]},
		success:function(response)
		{			
			var data = $.parseJSON(response);
			if (data.status==1) {
				form_move_release();
				show_messages("Move Job "+kode_ref+" success to "+code+num);
			}
			else {
				form_move_release();
				show_messages("Move Job "+kode_ref+" Fail");
				
			}
			$('#FORM_DISPLAY_BL').dialog('close');
		}
   })
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

function save_front()
{
	var myData = 
	{
		detail_id: $('#id_form').val(),
		location : $('#location').val(),
		trucking_co : $('#trucking_co').val(),
		no_from_shipper:	$('#si_shipper').val(),
		manifest_no:	$('#textfield14').val(),

		departure	: $('#departure').val(),
		mbl	: $('#mbl').val(),
		feeder_vessel	: $('#by_first').val(),
		feeder_vessel_etd	: $('#textfieldto1').val(),
		
		snd_vessel:	$('#textfieldby2').val(),
		snd_vessel_etd:	$('#textfieldto2').val(),
		
		
		last_vessel:	$('#textfieldby3').val(),
		last_vessel_etd:	$('#textfieldto3').val(),

		
		last_vessel_etd_date:	$('#flight_1').val(),
		last_vessel_eta_date:	$('#flight_2').val(),
		
		destination:	$('#destination').val(),
		handling_information:	$('#textfieldhandling').val(),
		acc_information_detail:	$('#textfieldacct').val(),
		currency_declaration:	$('#textfieldcurrency').val(),
		chcg:	$('#textfieldchcg').val(),
				
		value_for_carriage : $('#textfieldvaluecariage').val(),
		value_for_custom : $('#textfieldvaluecustom').val(),
		wt_val : $('#textfieldwt').val(),
		wt_prep_col : $('#select_prepcoll_wt').val(),
		other : $('#textfieldother').val(),
		other_prep_col : $('#select_prepcoll_other').val(),
		
		date_do_release : $('#textfield36').val(),
		no_peb : $('#textfield37').val(),
		tanggal_peb : $('#textfield38').val(),
		
		//sales : $('#sales').val(),
		//shipper : $('#shipper').val(),
		shipper_name : $('#shipperInformationName').val(),
		shipper_address : $('#textareaShipper').val(),
		mbl : $('#mbl').val(),
		
		sp_id : $('#spId').val(),
		
	}
	if($('#textfield13').val() !=''){
		$.ajax({
		type: "POST", 
		url : "<?php echo site_url('afg_c_bill_of_ladding/save_front_awb')?>",
		data: myData,
		success:function(XMLHttpRequest)
		{
			$('#textarea2').val($('#shipperInformationName').val()+'\n'+$('#textareaShipper').val());
		}
	})
	}else{
			alert('Nomor SI tidak boleh kosong');
			return false;
		}
	
}

function save_detail_awb()
{
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
		
		piece : $('#textfieldpieces').val(),
		weight : $('#textfield58').val(),
		rate_class: $('#textfieldrateclass').val(),
		chgs_weight: $('#textfieldchargeweight').val(),
		rate_per_charges: $('#textfieldratepercharge').val(),
		item_number: $('#textfielditemno').val(),
		total: $('#textfieldtotal').val(),
		shipper_certification: $('#shipper_certification').val(),
		
	}
	
	$("input[class='checkbox_bill']").each(function() {
		var val=$(this).attr('checked');
		if (val=='checked') { chk = true } else { chk = false}
    	myData[$(this).attr("id")] = chk;
	});
		
	$.ajax({
		type: "POST", 
		url : "<?php echo site_url('afg_c_bill_of_ladding/save_detail_awb')?>",
		data: myData,
		success:function(XMLHttpRequest)
		{
				Swal.fire({
				position: 'top-end',
				type: 'success',
				title: 'Your work has been saved',
				showConfirmButton: false,
				timer: 1500
				})
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
	collect_data();
	var rowid = $('#id_form').val();
	var isLock = $('#tabel_sp_detail').jqGrid('getCell',rowid,'isLock');

	if(isLock==1){
		 $('#tabs-lading').find('input, textarea, select').attr("disabled", 'disabled');
		  $('#cetak_form_bl').prop("disabled", false);
			}		
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

function move_discription()
{
	var spd = arguments[0];
	var oper = arguments[1];
		$.ajax
		({
			type:"POST",
			url:"<?php echo site_url('afg_c_bill_of_ladding/move_discription'); ?>",
			data:
			{
				sp_detail_id : spd, oper: oper,
			},
			success:function(response)
			{			
				var data = $.parseJSON(response);
				if(data.oper=='1'){
					$('a[id="move_discription"]').attr('onClick','move_discription('+spd+',\'0\')').html('Create attachement');
				} else {
					$('a[id="move_discription"]').attr('onClick','move_discription('+spd+',\'1\')').html('Delete attachement')
				}
			}
	})
}

function move_marks()
{
	var spd = arguments[0];
	var oper = arguments[1];
	$.ajax
		({
			type:"POST",
			url:"<?php echo site_url('afg_c_bill_of_ladding/move_marks'); ?>",
			data:
			{
				sp_detail_id : spd, oper: oper,
			},
			success:function(response)
			{			
				var data = $.parseJSON(response);
				if(data.oper=='1'){
					$('a[id="move_marks"]').attr('onClick','move_marks('+spd+',\'0\')').html('Create attachement');
				} else {
					$('a[id="move_marks"]').attr('onClick','move_marks('+spd+',\'1\')').html('Delete attachement')
				}
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
		var departure = $('#dropdownPortLoading').val();
		var destination = $('#dropdownCountries').val();
		var transhipment = $("#dropdownTranshipmentPort").val();
	}	else {
		var grid = $("#tabel_sp_detail"), i;		
		var kode_ref = $("#txtRefno2").val()+grid.jqGrid('getCell',rowid,'num');
		var departure = $('#dropdownPortLoading').val();
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
				departure : departure,
				destination : destination,
				kode_ref : kode_ref,
				transhipment:transhipment,
			},
			success:function(response)
			{			
				var data = $.parseJSON(response);
				var mbl = data.detail["mbl"];
				
				if (mbl=="") {
					var mbl = $('#txtMotherBL').val()
				}
				if (code.substr(4,3)=='300' || code.substr(4,3)=='301' || code.substr(4,3)=='302' ||code.substr(4,3)=='400'||code.substr(4,3)=='402'||code.substr(4,3)=='600'){
					var destination = $('#dropdownCountries').val();
				}else{
				var destination = data.stuffing["destination"];  
				}
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
				if (data.transhipment != "")
				{
				$('#textfieldto1').val(data.transhipment);
				$('#textfieldto2').val(data.destination);
				}else{
				$('#textfieldto1').val(data.destination);
				}
				
				if (data.detail["port_of_loading"]) 
				{ var port_of_loading = data.detail["port_of_loading"];}
				else
				{var port_of_loading = $('#dropdownPortLoading').val();}
				var port_of_discharge = data.detail["port_of_discharge"];
				var port_of_receive = data.detail["port_of_receive"];
				var port_of_delivery = data.detail["port_of_delivery"];
				
				
				if (port_of_discharge == '') { port_of_discharge = destination }
				if (port_of_receive == '') { port_of_receive = $('#dropdownPortLoading').val() }
				if (port_of_delivery == '') { port_of_delivery = destination }
				
				
				no_of_pack = data.stuffing["no_pack_qty"]+ " " +data.stuffing["no_pack_unit"].toUpperCase();
				
				
				//$('#textfield19').val($('#txtStuffingDate').val());
				
				$('#destination').val(destination);
				$('#departure').val(port_of_loading);
				$('#mbl').val(mbl);

				if (data.stuffing["hbl"]=="F") {
					$('#bl_no').val(mbl);
				}
				else 
				{
					$('#bl_no').val(data.hb_l);
				}
				
				$('#job_number').val(kode_ref);
				$('#job_number_detail').val(kode_ref);
				$('#awb_date').val($('#txtETDJKT').val());
				$('#kurs').val($('#txtKurs').val());	
				$('#airline').val($('#txtCarrier').val());
				$('#agent').val($('#dropdownAgent').val());
				$('#by_first').val($('#txtVessel').val()+' '+$('#txtVoyage').val());	
				$('#textfield48').val($('#txtVessel').val());
				$('#textfield49').val($('#txtVoyage').val());
				$('#textfield52').val($('#txtContSealNumber').val());
				$('#textfield54').val($('#txtETDJKT').val());
				
				$('#agent').val(data.stuffing["agent_code"]);
				$('#textarea7').val(data.stuffing["marking_nos"]);	
				$('#textfield58').val(data.stuffing["weight"]);			
				$('#textfieldpieces').val(data.stuffing["piece"]);	
				$('#textfieldrateclass').val(data.stuffing["rate_class"]);
				if(data.detail["acc_information_detail"]){
				$('#textfieldacct').val(data.detail["acc_information_detail"]);
				}else{
				$('#textfieldacct').val("FREIGHT "+data.stuffing["freight"]);
				}
				$('#textfieldrateclass').val(data.stuffing["rate_class"]);
				$('#textfielditemno').val(data.stuffing["item_number"]);
				$('#textfieldchargeweight').val(data.stuffing["chgs_weight"]);
				$('#textfieldratepercharge').val(data.stuffing["rate_per_charges"]);
				$('#textfieldtotal').val(data.stuffing["total"]);
				$('#textarea3').val(data.stuffing["consignee_add"]);
				$('#textfieldhandling').val(data.detail["handling_information"]);	
				$('#textarea5').val(data.stuffing["desc_goods"]);
				$('#textfieldcurrency').val(data.detail["currency_declaration"]);
				$('#textfieldchcg').val(data.detail["chcg"]);
				if(data.detail["value_for_carriage"]){
				$('#textfieldvaluecariage').val(data.detail["value_for_carriage"]);
				}else{
				$('#textfieldvaluecariage').val("N.V.D");
				}
				$('#textfieldwt').val(data.detail["wt_val"]);
				$('#select_prepcoll_wt').val(data.detail["wt_prep_col"]);
				if(data.detail["value_for_custom"]){
				$('#textfieldvaluecustom').val(data.detail["value_for_custom"]);
				}else{
				$('#textfieldvaluecustom').val("N.C.V");
				}
				$('#textfieldother').val(data.detail["other"]);
				$('#select_prepcoll_other').val(data.detail["other_prep_col"]);
				$('#si_shipper').val(data.detail["no_from_shipper"]);
				$('#textfield14').val(data.detail["manifest_no"]);
				$('#textfield16').val($('#dropdownPortLoading').val());
				
				$('#textfield20').val(destination);
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
				$('#shipper_certification').val(data.detail["shipper_name"]);			
				$('#textfield44').val(port_of_loading);
				$('#textfield45').val(port_of_discharge);
				$('#textfield46').val(port_of_receive);
				$('#textfield47').val(port_of_delivery);
				$('#textfield61').val(no_of_pack);
				
				if (data.stuffing["express_release"]=="TRUE") {
					$('#express_release').attr('checked',true)
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
	save_detail_awb(0);
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
		 $('#PRINT-FORM-BL').dialog( { position: "top", title: "BILL OF LADING "+FormBL, width: "80%", maxWidth: "855px" } ).html('<center><object data="<?php echo base_url("afg_c_bill_of_ladding/print_out_bl?spd='+id+'&bl='+FormBL+'&sp='+spid+'&cetak='+FormImg+'") ?>" width="955px" height="620" type="application/pdf"></center>');

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





</script>