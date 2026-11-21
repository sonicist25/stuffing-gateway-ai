<script>
/*
 * Dialog PDF mengikuti ukuran layar (mis. laptop 1366 x 768): tidak melebihi jendela browser,
 * PDF mengisi penuh dialog sehingga toolbar viewer (download / print) selalu terlihat.
 * Dialog bisa di-resize; PDF ikut menyesuaikan.
 */
window.dialogSizeForViewport = window.dialogSizeForViewport || function (maxW, maxH) {
    return {
        width:  Math.max(320, Math.min(maxW, $(window).width() - 20)),
        height: Math.max(300, Math.min(maxH, $(window).height() - 20))
    };
};
window.openPdfDialog = window.openPdfDialog || function (selector, title, url, tag) {
    var $d = $(selector);
    var size = dialogSizeForViewport(1000, 820);
    if ($d.hasClass('ui-dialog-content')) {
        $d.dialog('destroy');
    }
    $d.html('').css({ padding: 0, overflow: 'hidden' }).dialog({
        autoOpen: true,
        modal: true,
        resizable: true,
        title: title,
        width: size.width,
        height: size.height,
        position: { my: 'center top', at: 'center top+10', of: window },
        close: function () { $(this).html(''); }
    });
    var src = String(url).replace(/"/g, '&quot;');
    $d.html(tag === 'iframe'
        ? '<iframe src="' + src + '" style="width:100%;height:100%;border:0;display:block;"></iframe>'
        : '<object data="' + src + '" type="application/pdf" style="width:100%;height:100%;display:block;">' +
          '<p style="padding:10px">PDF tidak bisa ditampilkan di sini. <a href="' + src + '" target="_blank">Buka / download PDF</a></p></object>');
};

function save_detail()
{
	var myData = 
	{
		sp_detail_id      : $('#id_form').val(),	
		payable		      : $('#textfield63').val(),
		shipped_on_board  : $('#shipped_on_board').is(':checked') ? 1 : 0, // perbaikan di sini
		pol_bl            : $('#textfield44').val().toUpperCase(),
		tp_bl             : $('#textfield45').val().toUpperCase(),
		pod_bl            : $('#textfield46').val().toUpperCase(),
		movement_type     : $('input[name="movement_type"]:checked').val(),
		as_agent    	  : $('#textfield57').val(),
		container_no      : $('#textfield52').val(),
		note			  : $('#textarea2').val(),
		consignee		  : $('#textarea3').val(),
		notify			  : $('#textarea4').val(),
		marking			  : $('#textarea7').val(),
		desc_of_goods	  : $('#textarea5').val(),
		as_agent	  	  : $('#textfield57').val(),
	};

	// return jqXHR supaya cetak BL bisa menunggu simpan selesai
	return $.ajax({
		type: "POST",
		url : "<?php echo site_url('afg_c_bill_of_ladding/save_detail_draft')?>",
		data: myData,
		success: function(response)
		{
			var data = $.parseJSON(response);
			console.log(data);
			/* Swal.fire({
			  position: 'top-end',
			  icon: 'success',
			  title: 'Draft BL Berhasil Disimpan',
			  showConfirmButton: false,
			  timer: 1500
			}); */
		}
	});
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
	var rowid = $('#id_form').val();
	//console.log(rowid);
	collect_data_draft(rowid);

})



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

function collect_data_draft(rowid)
{
	
	$.ajax
		({
			type:"POST",
			url:"<?= base_url('afg_c_bill_of_ladding/collect_data_draft'); ?>",
			data:
			{
				rowid
			},
			success:function(response)
			{			
				var respon = $.parseJSON(response);
				//console.log(data.origin_city);
				let data= respon[0];
				if (data.trans_city=='')
				{
				$('#textfield17').val(data.pod_bl??data.destination_city);
				$('#textfield21').val(data.trans_city);
				$('#textfield18').val(data.expect_etd);
				$('#textfield19').val(data.eta);
				}else if (data.trans_city==data.destination_city){
				$('#textfield17').val(data.destination_city);
				$('#textfield21').val(data.trans_city);
				$('#textfield22').val(data.destination_city);
				$('#textfield18').val(data.expect_etd);
				$('#textfield19').val(data.eta);
				}else{
				$('#textfield17').val(data.trans_city);
				$('#textfield21').val(data.trans_city);
				$('#textfield22').val(data.destination_city);
				$('#textfield18').val(data.expect_etd);
				$('#textfield24').val(data.eta);
				}
			
				//$('#textfield19').val($('#txtStuffingDate').val());
				
				$('#textfield43').val(data.destination_city);
				$('#textfield6').val(data.destination_city);
				$('#textfield8').val(data.house_bl);
				$('#bl_no').val(data.house_bl);
				
				$('#textfield39').val(data.booking_code);
				$('#textfield2').val(data.expect_etd);
				$('#textfield15').val(data.vessel);	
				
				$('#textarea2').val(
				  (!data.note || data.note.trim() === "") ? (data.shipper_name + "\n" + data.shipper_address) : data.note
				);
				$('#textarea3').val(data.consignee);
				$('#textarea4').val(data.notify);
				$('#textarea5').val(data.description_of_goods);
				
				$('#textarea7').val(data.marking);
							
				$('#textfield44').val(data.pol_bl??data.origin_city);
				$('#textfield45').val(data.trans_city);
				$('#textfield46').val(data.destination_city);
				$('#textfield47').val(data.destination_city);
				$('#textfield48').val(data.vessel);
				$('#textfield49').val(data.voyage);
				$('#textfield52').val(data.container_no);
				$('#textfield54').val(data.expect_etd);
				$('#textfield56').val(data.number_of_bl);
				$('#textfield57').val(data.as_agent);
				$('#textfield58').val(data.weight);
				$('#textfield59').val(data.netto);
				$('#textfield60').val(data.volume);
				$('#textfield61').val(data.quantity+" "+data.package_type );
				$('#textfield62').val(data.freight );
				if (data.shipped_on_board==1) {
					$('#shipped_on_board').attr('checked',true)
				}
				if (data.freight=="PREPAID") {
				$('#textfield63').val(data.payable??"ORIGIN" );
				}else{
				$('#textfield63').val(data.payable??"DESTINATION" );	
				}
				if (data.number_of_bl=="EXPRESS RELEASE") {
					$('#express_release').attr('checked',true)
				} else{
				$('#cetak_jml_doc_origi').attr('checked',true)	
				}
				$('input[name="movement_type"]').each(function() {
					  if ($(this).val().toLowerCase() === data.movement_type.toLowerCase()) {
						$(this).prop('checked', true);
					  }
					});
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
		title:"DELIVERY AGENT",
		close: function()  {	$(this).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>') }
	})
	
	var rowid = $('#id_form').val();
	$.ajax({
		type: "POST", 
		url : "<?php echo site_url('afg_c_bill_of_ladding/nama_agent_draft')?>",
		data: { rowid },
		success:function(XMLHttpRequest)
		{
			$("#agent-insert").html(XMLHttpRequest)
		}
	})
}

/*
 * CETAK BL: simpan draft, lalu langsung buka modal Draft BL.
 * Pilihan "Cetak Polos" dihapus (dulu lewat dialog PRINT OPTIONS).
 */
function cetak_bl_form()
{
	var done = function () { get_bl_data("print"); };
	var req  = save_detail(0);
	if (req && typeof req.always === 'function') {
		req.always(done);   // cetak setelah simpan selesai, agar PDF memuat perubahan terakhir
	} else {
		done();
	}
}

function get_bl_data(type)
{
	var rowid = $( '#id_form' ).val();
	$.ajax
	({
		type: "POST",
		url : "<?php echo base_url('afg_c_bill_of_ladding/collect_data_draft')?>",
		data:	{ rowid },
		success: function(response)
		{
			var respon = $.parseJSON(response);
				//console.log(data.origin_city);
			let data= respon[0];
			if(data.booking_code == rowid )
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
	var FormImg = 'TRUE';   // selalu Draft BL (opsi Cetak Polos dihapus)
	var ImgBL	= $('#select3').val();

	
	if(FormBL == '')
	{
		$('#PRINT-FORM-BL').dialog( { position: "top", title: "BILL OF LADING "+FormBL } );
		$('#PRINT-FORM-BL').html('<center><h1 style="color:#f00;">Empty BL Number, Output cannot be send</h1></center>');		
	}
	else
	{
	 if(type == "print"){
		 // Ukuran mengikuti layar; sebelumnya lebar 50% + PDF 955px sehingga toolbar download terpotong di layar 1366px
		 openPdfDialog('#PRINT-FORM-BL', "BILL OF LADING "+FormBL,
			"<?php echo base_url('pricelist/afg_c_booking/print_out_bl') ?>?spd="+encodeURIComponent(id)+"&bl="+encodeURIComponent(FormBL)+"&code="+encodeURIComponent(ImgBL)+"&cetak="+FormImg, 'object');

	 }
	}
}


</script>