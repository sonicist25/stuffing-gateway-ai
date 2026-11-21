<?php
$paid_revision_allowed_user_ids = array(0, 107);
$can_paid_invoice_revision = in_array(
    (int)$this->session->userdata('user_id'),
    $paid_revision_allowed_user_ids,
    true
);
?>
<style type="txt/css">
#btnCetak,#btnExit,#btnPost,#btnRevision{
	box-shadow:inset 0px 1px 0px 0px #bbdaf7;
	background:linear-gradient(to bottom, #79bbff 5%, #378de5 100%);
	background-color:#79bbff;
	border-radius:6px;
	border:1px solid #84bbf3;
	display:inline-block;
	cursor:pointer;
	color:#000000;
	font-family:Arial;
	font-size:11px;
	padding:6px 24px;
	text-decoration:none;
	text-shadow:0px 1px 0px #528ecc;
	text-align: center
	
	}	
#btnCetak:hover,#btnExit:hover,#btnPost:hover,#btnRevision:hover{
  background:linear-gradient(to bottom, #378de5 5%, #79bbff 100%);
	background-color:#378de5;
 }
#page_tiga {
	font-size: 10px;
}
#groupForm {
	text-transform: uppercase;
}

</style>
<input type="hidden" id="kurs-forms" name="kurs-forms" value="<?php echo isset($kurs) ? $kurs : '17000' ?>">
<div style="font-size: 11px; padding: 10px; background-color: #eef" class="ui-corner-all ui-widget">
		<table class="ref-edit-table" width="100%">     
				<tr>	
                  <td height="25">TYPE</td>
                  <td>	<input id="kepadaForm" style="width: 100px;" class="text-input" readonly="readonly">	
            			<font style="margin-left: 30px">Jenis : </font><input id="jenisForm" style="width: 100px;" readonly="readonly">
            			<font style="margin-left: 30px">Group : </font><input id="groupForm" style="width: 20px;">
            			<font style="margin-left: 30px">PPN : <input type="checkbox" id="ppnForm" name="ppn" style="width: 10px; margin-bottom: 5px;">
                  </td>
                  <td colspan="5"></td>
            </tr>
            
            <tr>	
            		<td height="25">TO</td>
                  <td id="dropdownTD" readonly="readonly"></td>
                  <td colspan="4"></td>
            </tr>
            
            <tr>	
            		<td height="25"></td>
                  <td colspan="2" ><input id="namaForm" style="width: 100%;" readonly="readonly"></td>
                  <td></td>
                  <td width="100" align="right">MBL No.</td>
                  <td><?= form_input(array("id"=>"oblForm","style"=>"margin-left: 5px")) ?></td>
            </tr>
           
            <tr>	
            		<td height="25"></td>
                  <td rowspan="3" colspan="2"><textarea id="addressForm" name="addressForm" style="min-width: 500px; max-width: 500px; min-height: 60px; max-height: 60px" readonly="readonly"></textarea></td>
                  <td></td>
                  <td align="right" style="vertical-align: top">HBL No.</td>
                  <td style="vertical-align: top"><?= form_input(array("id"=>"Form","style"=>"margin-left: 5px")) ?></td>
            </tr>          
            <tr>	
            		<td colspan="4" height="25"></td>
                  <td align="right" style="vertical-align: top">Container No.</td>
                  <td style="vertical-align: top"><?= form_input(array("id"=>"containerForm","style"=>"margin-left: 5px")) ?></td>
            </tr>
            <tr>	
            		<td colspan="4" height="25"></td>
                  <td align="right" style="vertical-align: top">Job No.</td>
                  <td style="vertical-align: top"><?= form_input(array("id"=>"manifestForm","style"=>"margin-left: 5px")) ?></td>
            </tr>
            
            <tr>	
            		<td height="25">Currency</td>
            		<td colspan="5">
            			<select id="currencyForm" style="width: 60px;" class="text-input">
            				<option value=""></option>
            				<option value="USD">USD</option>
            				<option value="RP.">Rp.</option>
            			</select> 
            			<font style="margin-left: 30px">Vessel</font>
            			<input id="vesselFormEdit" style="width:200px;">
            			<font style="margin-left: 30px">Destination</font>
            			<input id="destinationFormEdit" style="width: 200px;">
            		</td>
            </tr>
            
            <tr>	
            		<td height="25">Ref No</td>
            		<td colspan="5">
            			<input id="refnoForm" disabled="disabled"> 
            			<font style="margin-left: 30px">Date</font>
            			<input id="dateForm" style="width: 100px;">
            			<font style="margin-left: 30px">ETD</font>
            			<input id="etdForm" style="width: 100px;">
            			<font style="margin-left: 30px">ETA</font>
            			<input id="etaForm" style="width: 100px;">
            			<input id="port_loadingFormEdit" style="width: 100px;" type="hidden">
            			<input id="idForm" style="width: 100px;" type="hidden">
            		</td>
            </tr>
      </table>
</div>
<div style="clear: both; height: 20px"></div>
<div>
	<table id="table-satu"></table>
	<div id="page-satu"></div>
</div>

<div style="clear: both; height: 10px;"></div>

<hr>
<div style="float: left; font-size: 11px; width: 200px" id="msg"></div>
<div style="font-size: 11px; margin-bottom: 10px">
	<input type="button" id = "btnCetak" value=" CETAK " style="width: 70; height: 30; float: right;" onclick="print_data()"> 
	<input type="button" id = "btnExit" value=" EXIT " style="width: 70; height: 30; float: right;" onclick="exit_data()">
	<input type="button" id = "btnPost" value=" POST " style="width: 70; height: 30; float: left;" onclick="get_proses_post()">
	<?php if ($can_paid_invoice_revision): ?>
	<input type="button" id = "btnRevision" value=" REVISI PAID " style="width: 100px; height: 30; float: left; margin-left:5px;" onclick="finance_open_paid_revision()">
	<?php endif; ?>
</div>

<script type="text/javascript" >
var CAN_PAID_INVOICE_REVISION = <?= $can_paid_invoice_revision ? 'true' : 'false' ?>;
$(document).ready(function () {
	var rowid = '<?php echo $this->input->post("id") ?>';
	var grid  = $('#table_tiga'),i;
	var shipper = grid.jqGrid('getCell',rowid,'kepada_kode');
	let ppn = grid.jqGrid('getCell', rowid, 'ppn');
  
	$('#ppnForm').prop('checked', ppn == 1);
	$('#kepadaForm').val(grid.jqGrid('getCell',rowid,'kepada'));
	$('#dropdownTD').html('<select id="nameForm" style="width: 200px;" class="text-input">'+
            					'<option value="'+shipper+'">'+shipper+'</option>'+
								'</select>');
	$('#groupForm').val(grid.jqGrid('getCell',rowid,'group'));
	$('#jenisForm').val(grid.jqGrid('getCell',rowid,'jenis'));
	$('#currencyForm').val(grid.jqGrid('getCell',rowid,'currency'));	
	$('#refnoForm').val(grid.jqGrid('getCell',rowid,'number'));
	$('#dateForm').val(grid.jqGrid('getCell',rowid,'date')).datepicker({dateFormat: 'dd/mm/yy'});
	
	$('#destinationFormEdit').val($('#destinationForm').val());
	$('#port_loadingFormEdit').val($('#port_loadingForm').val());
	$('#vesselFormEdit').val($('#vesselForm').val()+'  '+$('#voyageForm').val());
	$('#etdForm').val($('#etd_jktForm').val()).datepicker({dateFormat: 'dd/mm/yy'});	
	$('#etaForm').val($('#eta_destinationForm').val()).datepicker({dateFormat: 'dd/mm/yy'});	
	$('#containerForm').val($('#cont_seal_noForm').val());
	
	$("input[id='manifestForm']").val($('#kode_refForm').val());
	$("input[id='oblForm']").val($('#mother_blForm').val());
	$("input[id='idForm']").val(rowid);
	
	get_value(rowid);
	//tarik_data();
	table_satu();
})
var x = document.getElementById("btnPost");
if($('#jenisForm').val() == "CREDIT NOTE") {
	 x.disabled = true;
} else {
    x.disabled = false
}
function table_satu(){
	var year = new Date().getFullYear().toString().substring(2, 4);
	var number = '<?php echo $this->input->post("id") ?>';
	var kode_ref = $('#kode_refForm').val();
	var grid = $("#table-satu"), i;	
	grid.jqGrid({ 
		url:"<?php echo base_url('finance/c_finance_process/tampil_table_satu')?>",
		mtype : "post",
		datatype: "json",
		sortname: "bl_no",
		sortorder: "asc",
		postData: 
		{ 
			table  	: 'x_finance_item',
			kode_ref : kode_ref,
			number 	: number,
		},
		colNames:
		[
			'B/L No #',
			'Code',
			'Item of Service',
			'M3', 
			'Price/Unit',
			'Amount',
		],
    	colModel:
		[
			{name:'bl_no',index:'bl_no', align:"left", width:110, editable: true,},
			{name:'code_service',index:'code_service', align:"left", width:120, editable: true,

			},
			{name:'item_service',index:'item_service', align:"left", width:240, editable: true, 

			},
			{name:'m3',index:'m3', align:"center", width:80, editable: true, 
				formatter: 'number',
    			formatoptions: { decimalSeparator: ".", thousandsSeparator: " ", decimalPlaces: 4, defaultValue: '' }
			},
			{name:'unit_price',index:'unit_price', align:"right", width:80, editable: true, 
				formatter: 'number',
    			formatoptions: { decimalSeparator: ".", thousandsSeparator: " ", decimalPlaces: 2, defaultValue: '' }
			},
			{name:'amount',index:'amount', align:"right", width:80, editable: true, 
				formatter: 'number',
    			formatoptions: { decimalSeparator: ".", thousandsSeparator: " ", decimalPlaces: 2, defaultValue: '' }
			},
		], 
		shrinkToFit: false,
		loadonce:true,
  		viewrecords: true,
      multiselect:true, 
		modal: true,
		rownumbers: true,
		rowNum: 15,
		gridview: true,
		width: 825,
		height: 160,
		rowList:[15,30,45],
		caption:false,
		editurl:'c_finance_process/manage_table_satu',
		cellEdit:true,
		cellsubmit:'clientArray',
		pager:'#page-satu',

		beforeSaveCell: function(rowid, cellname, value, iRow, iCol) {
			save_tbl_satu(rowid,cellname,value)
    	},
    			
		loadComplete: function(data) {
			$("#cb_"+grid[0].id).hide();
    	},
    	
    	beforeSelectRow: function(rowid, e) {
        var cbsdis = $("tr#"+rowid+".jqgrow > td > input.cbox:disabled", grid[0]);
        if (cbsdis.length === 0) {
            return true; 
        		} else {
            return false; 
        }
    	},		
		onSelectRow: function(rowid, status){
			var selected = $("tr.jqgrow > td > input.cbox:checked", grid[0]);		
			selected[rowid] = status;
			if(status)
			{ 
				var cbs = $("tr.jqgrow > td > input.cbox", grid[0]);  			
    			//cbs.attr("disabled", true);  
    			//selected.attr("disabled", false);   
    			on_edit(true)			
     		}
        	else
        	{
        		var cbs = $("tr.jqgrow > td > input.cbox:disabled", grid[0]);  		
    			//cbs.attr("disabled", false);  
    			on_edit(true)     			
        	}
		},
		
	})
	grid.jqGrid('navGrid','#page-satu', { edit: false, add: false, del: true, refresh: false, <?php echo 'search: false' ?>},
		{ },
   	{ },
   	{height: "auto", width: "auto", closeAfterSubmit: true, reloadAftersubmit : true, modal: true,
   		afterSubmit: function () {
    			grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
    			return [true];
			}
   	},
   	{ },
   	{ }
	)
	grid.navButtonAdd('#page-satu',{ 
			caption:'', 
			buttonicon:"ui-icon-disk", 
			onClickButton: tarik_data, 
			position: "last", 
			title:"Tarik Data Detail", 
			cursor: "pointer"
	})
}


function finance_open_paid_revision()
{
	if (!CAN_PAID_INVOICE_REVISION) {
		Swal.fire({
			icon: 'error',
			title: 'Akses ditolak',
			text: 'Anda tidak memiliki hak untuk membatalkan status PAID dan membuka revisi invoice.'
		});
		return;
	}

	var finance_id = parseInt($('#idForm').val() || 0, 10);
	var invoice_no = $.trim($('#refnoForm').val() || '');

	if (!finance_id) {
		Swal.fire('Tidak bisa revisi', 'Finance ID tidak tersedia.', 'error');
		return;
	}

	Swal.fire({
		icon: 'warning',
		title: 'Buka revisi dokumen PAID?',
		html:
			'Status <b>PAID</b> akan dibatalkan menjadi <b>UNPAID</b>.<br>' +
			'Snapshot lama disimpan ke revision log. Riwayat payment/GL tidak dihapus.',
		input: 'textarea',
		inputLabel: 'Alasan revisi / renegosiasi',
		inputPlaceholder: 'Contoh: renegosiasi charge setelah paid',
		showCancelButton: true,
		confirmButtonText: 'Batalkan PAID & Buka Revisi',
		cancelButtonText: 'Batal',
		preConfirm: function(value) {
			value = $.trim(value || '');
			if (value.length < 5) {
				Swal.showValidationMessage('Alasan revisi minimal 5 karakter.');
				return false;
			}
			return value;
		}
	}).then(function(result) {
		if (!result.isConfirmed) return;

		$.ajax({
			type: 'POST',
			url: "<?php echo site_url('finance/c_finance_process/reopen_paid_invoice_revision')?>",
			dataType: 'json',
			data: {
				finance_id: finance_id,
				invoice_no: invoice_no,
				reason: result.value
			},
			success: function(res) {
				if (res && parseInt(res.status || 0, 10) === 1) {
					Swal.fire(
						'Revisi dibuka',
						'Status sekarang UNPAID. Revision #' + (res.revision_no || '-') + '. Silakan Tarik Data / edit detail.',
						'success'
					);
					return;
				}

				Swal.fire(
					'Gagal membuka revisi',
					(res && (res.message || res.msg)) || 'REVISION_OPEN_FAILED',
					'error'
				);
			},
			error: function(xhr) {
				var res = xhr.responseJSON || null;
				if (!res && xhr.responseText) {
					try { res = JSON.parse(xhr.responseText); } catch (e) {}
				}
				Swal.fire(
					'Gagal membuka revisi',
					(res && (res.message || res.msg)) || xhr.responseText || 'REVISION_OPEN_FAILED',
					'error'
				);
			}
		});
	});
}


function tarik_data(){
	DataTarik = 
	{
		id : '<?php echo $this->input->post("id") ?>',
		job_no : $('#kode_refForm').val(),
		agent  : $('#nameForm').val(),
		group  : $('#groupForm').val(),
		jenis  : $('#jenisForm').val(),
		kepada : $('#kepadaForm').val(),
		number: $("#refnoForm").val(),
		ppn: '<?php echo $this->input->post("ppn") ?>',
		kurs: $("#kurs-forms").val(),
		destination : $("#destinationFormEdit").val(),
		/*
		 * PATCH (multi-tab shard safety): tahun database di-"bekukan" saat form
		 * ini dirender. Kalau user membuka tab lain dan session db pindah tahun,
		 * request dari form ini tetap menulis ke shard tahun yang benar
		 * (model membaca db_year dari POST sebelum fallback ke session).
		 */
		db_year: '<?php echo (int)$this->session->userdata('db'); ?>',
	}
	$.ajax
	({
		type:"POST",
		url:"<?php echo site_url('finance/c_finance_process/tarik_data_table_satu'); ?>",
		data: DataTarik,
		success:function(response)
		{
			var data = $.parseJSON(response);
			$("#table-satu").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
		}
	})
}

function exit_data(){
	$( "#form-invoice" ).fadeOut('slow', function() 
	{
		$(this).dialog('close');
	})
}

function select_value(){
	var value = $( '#kepadaForm' ).val();
	$('#nameForm').val('loading...');
	$.ajax({
		type: "POST", 
		url : "<?php echo site_url('finance/c_finance_process/dropdown')?>",
		data: {value : value},
		success: function(data)
		{
			var data = $.parseJSON(data);
			$('#dropdownTD').html(data.dropdown);
			get_nama_alamat();	
		},
		error:function(XMLHttpRequest)
		{
			$('#dropdownTD').html(XMLHttpRequest.responseText)
		}	
	})
}

function get_value(rowid){
	$.ajax({
		type: "POST", 
		url : "<?php echo site_url('finance/c_finance_process/get_value')?>",
		data: 
		{
			jenis		 	: $("input[id='jenisForm']").val(),
			kepada	  	: $("input[id='kepadaForm']").val(),
			code			: $("select[id='nameForm']").val(),
			id 			: $("input[id='id_aktif']").val(),
			oper			: 'edit',
		},
		success: function(data)
		{
			var data = $.parseJSON(data);
			$("input[id='namaForm']").val(data.nama);
			$("textarea[id='addressForm']").html(data.alamat);
		}	
	})
}

function get_nama_alamat(){

	/*
	 * PATCH:
	 * 1. .die() dulu sebelum .live() — dulu setiap kali select_value() dipanggil,
	 *    handler menumpuk, satu kali change bisa memicu beberapa request.
	 * 2. Step 1 (onchange): request TANPA confirm -> server hanya lookup
	 *    nama & alamat, TIDAK menulis apa pun ke x_finance.
	 * 3. Step 2: tampilkan dialog konfirmasi. Hanya jika user setuju,
	 *    kirim request kedua dengan confirm:1 -> server baru menyimpan
	 *    kepada & kepada_kode (dikunci number + region_id + audit trail).
	 * Ini yang dulu menyebabkan kepada/kepada_kode "berubah sendiri":
	 * sekadar mengklik dropdown sudah menimpa data permanen.
	 */
	$("#nameForm").die("change").live("change",function()
	{
		var kepada = $("#kepadaForm").val();
		var code   = $("#nameForm").val();
		var number = $("#refnoForm").val();
		var dbYear = '<?php echo (int)$this->session->userdata('db'); ?>';

		if (code === '' ) return;

		// STEP 1: lookup read-only
		$.ajax({
			type:"POST",
			url:"<?php echo site_url('finance/c_finance_process/get_nama_alamat'); ?>",
			data:{
				kepada : kepada,
				code   : code,
				number : number,
				db_year: dbYear
				// tanpa confirm -> tidak ada penulisan ke database
			},
			success:function(response)
			{
				var data = $.parseJSON(response);
				if (data.status != 1) {
					$('#msg').html('<font color="red">Kode ' + code + ' tidak ditemukan.</font>');
					return;
				}

				$('#namaForm').val(data.nama);
				$('#addressForm').val(data.alamat);

				// STEP 2: simpan hanya jika dikonfirmasi eksplisit
				var teks = 'Simpan perubahan Bill To menjadi:\n\n' +
							kepada + ' - ' + code + '\n' + data.nama +
							'\n\nuntuk dokumen ' + number + ' ?';
				var lanjut = (typeof Swal === 'undefined') ? confirm(teks) : null;

				var doSave = function(){
					$.ajax({
						type:"POST",
						url:"<?php echo site_url('finance/c_finance_process/get_nama_alamat'); ?>",
						data:{
							kepada : kepada,
							code   : code,
							number : number,
							confirm: 1,
							db_year: dbYear
						},
						success:function(resp2){
							var d2 = $.parseJSON(resp2);
							if (d2.saved == 1) {
								$('#kepadaForm').val(kepada);
								$('#msg').html('<font color="green">Bill To tersimpan.</font>');
								var kode_ref = $('#kode_refForm').val();
								$("#table_tiga").jqGrid('setGridParam',{datatype:'json',
									postData:{ table:'x_finance', job_no:kode_ref }
								}).trigger('reloadGrid');
							} else {
								$('#msg').html('<font color="red">Gagal menyimpan Bill To (dokumen tidak ditemukan di region/tahun ini).</font>');
							}
						}
					});
				};

				if (lanjut === null) {
					Swal.fire({
						title: 'Simpan Bill To?',
						text: kepada + ' - ' + code + ' (' + data.nama + ') untuk ' + number,
						icon: 'question',
						showCancelButton: true,
						confirmButtonText: 'Simpan',
						cancelButtonText: 'Batal'
					}).then(function(r){ if (r.isConfirmed) doSave(); });
				} else if (lanjut === true) {
					doSave();
				}
				// user membatalkan -> tampilan nama/alamat tetap terisi,
				// tapi database TIDAK berubah.
			}
		})
	})
}

function print_data(){
    $("#PRINT-PROCESS").html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
    $("#PRINT-PROCESS").dialog({
        autoOpen: true,
        resizable: false,
        height: 'auto',
        width: 960,
        modal: true,
        position:['center',0],
        title: "Finance Proses",
        close: function()  {
            $(this).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>')
        }
    });

    var url = serialize({
        jenis       : $("input[id='jenisForm']").val(),
        no_invoice  : $("input[id='refnoForm']").val(),
        kepada      : $("input[id='namaForm']").val()+'\n'+$("textarea[name='addressForm']").val(),
        vessel      : $("input[id='vesselFormEdit']").val(),
        etd         : $("input[id='etdForm']").val().replace(/\//g,'-'),
        eta         : $("input[id='etaForm']").val().replace(/\//g,'-'),
        origin      : $("input[id='port_loadingFormEdit']").val(),
        destination : $("input[id='destinationFormEdit']").val(),
        date        : $("input[id='dateForm']").val().replace(/\//g,'-'),
        manifest    : $("input[id='manifestForm']").val(),
        container   : $("input[id='containerForm']").val(),
        obl         : $("input[id='oblForm']").val(),
        id          : $("input[id='idForm']").val(),
    });

    // ambil nilai session dari PHP
    var dbYear = <?php echo (int)$this->session->userdata('db'); ?>;

    // tentukan endpoint berdasarkan nilai session
    var endpoint = (dbYear >= 2026) 
        ? '/finance/c_finance_process/print_out_new?' 
        : '/finance/c_finance_process/print_out?';

    $('#PRINT-PROCESS').html(
        '<center><object data="'+window.location.origin+endpoint+url+'" width="960px" height="600px" type="application/pdf"></object></center>'
    );
}


function finance_process_post_success_info(response)
{
    if (!response || typeof response !== 'object') {
        return null;
    }

    /*
     * Response normal backend:
     * status = true / 1 / "1"
     */
    if (
        response.status === true ||
        response.status === 1 ||
        response.status === '1'
    ) {
        return {
            message: (typeof response.message === 'string' && $.trim(response.message) !== '')
                ? response.message
                : 'Invoice/DN berhasil diposting ke ERP.',
            erp_invoice_no: response.erp_invoice_no || response.invoice_no || ''
        };
    }

    /*
     * Response legacy Jakarta / ERPNext:
     * {
     *   message: {
     *     "Sales Invoice": {
     *        name: "INFP/...",
     *        docstatus: 1
     *     }
     *   },
     *   exc: "...debug trace..."
     * }
     *
     * Field exc TIDAK otomatis berarti gagal. Kalau Sales Invoice sudah
     * docstatus=1, transaksi sudah submitted dan harus dianggap sukses.
     */
    var salesInvoice = null;

    if (
        response.message &&
        typeof response.message === 'object' &&
        response.message['Sales Invoice']
    ) {
        salesInvoice = response.message['Sales Invoice'];
    }

    if (
        salesInvoice &&
        salesInvoice.name &&
        parseInt(salesInvoice.docstatus, 10) === 1
    ) {
        return {
            message: 'Invoice berhasil diposting ke ERP.',
            erp_invoice_no: salesInvoice.name
        };
    }

    return null;
}

function finance_process_post_error_message(data, fallback)
{
    fallback = fallback || 'Terjadi kesalahan pada server.';

    if (!data) {
        return fallback;
    }

    if (typeof data.message === 'string' && $.trim(data.message) !== '') {
        return data.message;
    }

    if (data.message && typeof data.message === 'object') {
        if (typeof data.message.message === 'string' && $.trim(data.message.message) !== '') {
            return data.message.message;
        }
        if (typeof data.message.error === 'string' && $.trim(data.message.error) !== '') {
            return data.message.error;
        }
    }

    if (data._server_messages) {
        try {
            var serverMessages = (typeof data._server_messages === 'string')
                ? JSON.parse(data._server_messages)
                : data._server_messages;

            if (Array.isArray(serverMessages) && serverMessages.length > 0) {
                var first = serverMessages[0];

                if (typeof first === 'string') {
                    try {
                        first = JSON.parse(first);
                    } catch (e) {
                        return first;
                    }
                }

                if (first && typeof first.message === 'string' && $.trim(first.message) !== '') {
                    return first.message;
                }
            }
        } catch (e) {}
    }

    if (typeof data.error === 'string' && $.trim(data.error) !== '') {
        return data.error;
    }

    if (typeof data.exception === 'string' && $.trim(data.exception) !== '') {
        return data.exception;
    }

    /* Jangan pernah menghasilkan [object Object]. */
    try {
        var jsonText = JSON.stringify(data, null, 2);
        if (jsonText && jsonText !== '{}') {
            return jsonText;
        }
    } catch (e) {}

    return fallback;
}

function finance_process_show_post_success(info)
{
    var message = info.message || 'Invoice/DN berhasil diposting ke ERP.';

    if (info.erp_invoice_no) {
        message += '\nERP Invoice: ' + info.erp_invoice_no;
    }

    Swal.fire({
        icon: 'success',
        title: 'Berhasil',
        text: message
    });
}

function get_proses_post(){
    var finance_id = parseInt($('#idForm').val() || 0, 10);
    var invoice_no = $.trim($('#refnoForm').val() || '');

    if (!finance_id && !invoice_no) {
        Swal.fire({
            icon: 'warning',
            title: 'Validasi gagal',
            text: 'ID finance / nomor invoice tidak tersedia.'
        });
        return;
    }

    Swal.fire({
        title: 'Memproses Posting...',
        text: 'Mohon tunggu sebentar',
        allowEscapeKey: false,
        allowOutsideClick: false,
        didOpen: function () {
            Swal.showLoading();
        }
    });

    $.ajax({
        type: 'POST',
        url: "<?php echo site_url('finance/c_finance_process/post_invoice_debit_note_table_satu')?>",
        dataType: 'json',
        data: {
            id: finance_id,
            invoice_no: invoice_no
        },
        success: function(response) {
            var successInfo = finance_process_post_success_info(response);

            if (successInfo) {
                finance_process_show_post_success(successInfo);
                return;
            }

            Swal.fire({
                icon: 'error',
                title: 'Posting Gagal',
                text: finance_process_post_error_message(response, 'POST_FAILED')
            });
        },
        error: function(xhr) {
            var data = xhr.responseJSON || null;

            if (!data && xhr.responseText) {
                try {
                    data = JSON.parse(xhr.responseText);
                } catch (e) {
                    data = null;
                }
            }

            /*
             * Upstream legacy kadang memberi HTTP error walaupun Sales Invoice
             * sudah submitted. Periksa isi body terlebih dahulu.
             */
            var successInfo = finance_process_post_success_info(data);

            if (successInfo) {
                finance_process_show_post_success(successInfo);
                return;
            }

            Swal.fire({
                icon: 'error',
                title: 'Posting Gagal',
                text: finance_process_post_error_message(
                    data,
                    xhr.responseText || 'Terjadi kesalahan pada server.'
                )
            });
        }
    });
}



</script>

