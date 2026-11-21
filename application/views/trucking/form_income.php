<link rel="stylesheet" type="text/css" href="<?php echo base_url(); ?>application/third_party/css/income.css">
<script type="text/javascript" src="<?php echo base_url().'assets/js/income.js'?>">
 var base_url = "<?php echo base_url(); ?>";
</script>
<div id="msg-income"></div>
<div id="main-page" style="font-size: 11px;">

<div class="grid-container">
	<div style ="margin-right:30px;">
	<input id="kode_ref_income" disabled="disabled" value="<?php echo $kode_ref ?>" type="hidden">
	<input id="spd_id_income" value="<?php echo $id ?>" type="hidden">
	<input id="lock" value="<?php echo $lock ?>" type="hidden">
	<input id="shipper_id" value="<?php echo $shipper ?>" type="hidden">
	Sales : 
		<?php
				$select = $this->afg_m_stuffing_plan->get_sales_by_sp_id($id);
				$sales = $this->user_model->get_data_sales();
				$data['option_sales']['']='';
				if($sales->num_rows() > 0 ) {
				foreach ($sales->result() as $row)
				{	
					$data['option_sales'][$row->user_id] = $row->fullname;
				}
				echo form_dropdown('sales', $data['option_sales'], $select, 'id="sales" onchange="update_sales()" style="width: 100%; height: 20; border-radius: 2px; border: 1px solid #999;"');
			}
			else 
			{
				echo form_dropdown('sales', $data['option_sales'], '', 'id="sales" onchange="update_sales()" style="width: 100%; height: 20; border-radius: 2px; border: 1px solid #999;"');
			}
      ?>
	</div>
	<div style ="margin-right:30px;">
		Quotation No. :  <select id="select_quot" name="select_quot"  style="width: 100%;" onchange="get_quot_detail()">
					 <option></option>
                    </select>
        <div id="income-quotation-lifecycle" style="margin-top:4px;min-height:18px;"></div>
	</div>
	<div style ="margin-right:30px;">
		Kurs : <br><input type="text" id="kurs" style="font-size:12px; height :22px; border-radius: 3px;" value="<?php echo $kurs ?>">
	</div>
	<div>
		Term : <br><input type="text" id="incoterms" style="font-size:12px; height :22px; border-radius: 3px;" value="<?php echo $incoterms ?>">
	</div>
	<div style="margin-right:30px;">
		<input type="hidden" id="income_posted_at" value="">
	</div>
	<div>
		<input type="button" id ="button-post" value=" POST " onclick="get_proses_post()">
	</div>
</div>
<div id="button" style="float:right;"></div>
<div style="clear:both; height: 10px;"></div>
<table id="table-frm-selling" ></table>
<div id="pager-frm-selling"></div>
<div style="clear:both; height: 10px;"></div>
<table id="table-frm-agent" ></table>
<div id="pager-frm-agent"></div>
<div style="clear:both; height: 10px;"></div>
<table id="table-frm-coloader" ></table>
<div id="pager-frm-coloader"></div>	
</div>
<div style="clear:both; height: 10px;"></div>
<div class="grid-container">
	<div>
		Last Posted : <span id="income_last_posted_bottom"><?php echo htmlspecialchars((string)$created, ENT_QUOTES, 'UTF-8'); ?></span>
	</div>
	<div>
		Last Edited : <span id="income_last_edited_bottom"><?php echo htmlspecialchars((string)$last_update, ENT_QUOTES, 'UTF-8'); ?></span>
	</div>
	
</div>

<script type="text/javascript">
/* ===========================
   SweetAlert2 helpers
=========================== */
const Toast = typeof Swal !== 'undefined' ? Swal.mixin({
  toast: true,
  position: 'top-end',
  showConfirmButton: false,
  timer: 1500,
  timerProgressBar: true
}) : null;

const INCOME_REGION_ID = <?php echo json_encode((string)$this->session->userdata('region_id')); ?>;

function _incomeIsJakarta() {
  return String(INCOME_REGION_ID || '').toLowerCase() === 'jakarta';
}

/* ===========================
   Update Sales (dengan konfirmasi tarik ulang)
=========================== */
function update_sales() {
  const sp_id    = $('#spd_id_income').val();
  const kode_ref = $('#kode_ref_income').val();
  const salesVal = $('#sales').val();
  const shipper  = $('#shipper_id').val();

  // Validasi: jangan kirim kalau sales kosong
  if (!salesVal || salesVal.trim() === '') {
    if (typeof Swal !== 'undefined' && Swal.fire) {
      Swal.fire({
        title: 'Sales belum dipilih',
        text: 'Silakan pilih sales terlebih dahulu sebelum menyimpan.',
        icon: 'warning'
      });
    } else {
      alert('Sales belum dipilih. Pilih dulu sebelum update.');
    }
    return; // stop eksekusi
  }

  $.ajax({
    type: "POST",
    url : base_url + '/afg_c_income/update_sales',
    data: {
      sp_id   : sp_id,
      kode_ref: kode_ref,
      sales   : salesVal,
      shipper : shipper,
    },
    success: function (response) {
      let data;
      try { data = $.parseJSON(response); } catch(e){ data = {status:0}; }

      if (data.status == 1) {
        // Sales berubah => quotation yang eligible juga harus dihitung ulang.
        if (typeof load_quotation_options === 'function') load_quotation_options();

        // SweetAlert2
        if (typeof Swal !== 'undefined' && Swal.fire) {
          Swal.fire({
            title: 'Sales diperbarui',
            text: 'Apakah Anda juga ingin tarik ulang data income?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, tarik ulang',
            cancelButtonText: 'Tidak'
          }).then((res) => {
            if (res.isConfirmed) {
              tarik_data_selling_price();
              if (kode_ref.substr(4,3) === '102') {
                tarik_data_coloader();
              }
              Swal.fire({title:'Data income ditarik ulang', icon:'success', timer:1200, showConfirmButton:false});
            } else {
              Swal.fire({title:'Sales diperbarui', icon:'success', timer:1000, showConfirmButton:false});
            }
          });
        } else {
          if (confirm('Apakah Anda juga ingin tarik ulang data income?')) {
            tarik_data_selling_price();
            if (kode_ref.substr(4,3) === '102') {
              tarik_data_coloader();
            }
          }
        }
      } else {
        if (typeof Swal !== 'undefined' && Swal.fire) {
          Swal.fire({title:'Gagal update sales', icon:'error'});
        } else {
          alert('Gagal update sales');
        }
      }
    },
    error: function() {
      if (typeof Swal !== 'undefined' && Swal.fire) {
        Swal.fire({title:'Error koneksi server', icon:'error'});
      } else {
        alert('Error koneksi server');
      }
    }
  });
}


/* ===========================
   Init, handler checkbox, dll
=========================== */
$(function () {
	var tarik_insentiv_per_doc = $('#TRK-DOC').attr('checked');
	if(tarik_insentiv_per_doc=='checked')
		{var tarik_insentiv_per_doc = 'true';}
		else
		{var tarik_insentiv_per_doc = 'false';}
	var intensiv_tidak_diproses = $('#TDK-PROSES').attr('checked');
	if(intensiv_tidak_diproses=='checked')
		{var intensiv_tidak_diproses = 'true';}
		else
		{var intensiv_tidak_diproses = 'false';}
	$("#kurs").blur(function(){
	kurs : $('#kurs').val(),
	
	$.ajax
	({
		type:"POST",
		url : base_url+'/afg_c_income/manage_header_income',
		data:
			{
			kode_ref : $('#kode_ref_income').val(),
			tarik_insentiv_per_doc: tarik_insentiv_per_doc,
			intensiv_tidak_diproses: intensiv_tidak_diproses,
			kurs:$( '#kurs' ).val(),
			},
		});
	});
	$('#TDK-PROSES').val('<?= $Intensiv_tidak_diproses ?>');
	$('#TRK-DOC').val('<?= $Tarik_insentiv_per_Doc ?>');	
	
	if($('#TDK-PROSES').val()=='TRUE'){
		$('#TDK-PROSES').attr('checked',true);
		$('#TRK-DOC').attr('disabled',true);
		$('#pager-frm-agent_left').attr('hidden',true);
		$('#pager-frm-coloader_left').attr('hidden',true);	
	}
	else
	{
		$('#TDK-PROSES').attr('checked',false);
		$('#TRK-DOC').attr('disabled',false);
		$('#pager-frm-agent_left').attr('hidden',false);
		$('#pager-frm-coloader_left').attr('hidden',false);
	}
		
	if($('#TRK-DOC').val()=='TRUE'){
		$('#TRK-DOC').attr('checked',true);		
	}
	else
	{
		$('#TRK-DOC').attr('checked',false);
	}
	
	
	$("#TDK-PROSES").live("change",function(){
		var mode = $("#TDK-PROSES").attr('checked');
		var spd_id = $('#spd_id_income').val();
		if (mode=='checked') {mode = true;} else {mode = false;}
		if(mode==true){
			$.ajax
			({
				type:"POST",
				url:"<?php echo site_url('afg_c_income/clear_agent_coloader'); ?>",
				data:
				{
					kode_ref : $('#kode_ref_income').val(),
					id_sd : spd_id,
				},
				success:function(response)
				{
					var data = $.parseJSON(response);
					$('#TRK-DOC').attr('disabled',true);
					$("#table-frm-agent").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
					$('#pager-frm-agent_left').attr('hidden',true);
					$("#table-frm-coloader").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
					$('#pager-frm-coloader_left').attr('hidden',true);
					save_data();
				}
			})
		} 
		else 
		{
			$('#TRK-DOC').attr('disabled',false);
			$('#pager-frm-agent_left').attr('hidden',false);
			$('#pager-frm-coloader_left').attr('hidden',false);
			save_data();
		}	
	})
	
	$("#TRK-DOC").live("change",function(){
		var mode = $("#TRK-DOC").attr('checked');
		var spd_id = $('#spd_id_income').val();
		if (mode=='checked') {mode = true;} else {mode = false;}
		if(mode==true)
		{
			$.ajax
			({
				type:"POST",
				url:"<?php echo site_url('afg_c_income/update_agent_coloader'); ?>",
				data:
				{
					id_sd : spd_id,
					kode_ref : $('#kode_ref_income').val(),
					qty:	'1.0000',
				},
				success:function(response)
				{
					var data = $.parseJSON(response);
					$("#table-frm-agent").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");	
					save_data();					
				}
			})
		} 
		else 
		{
			$.ajax
			({
				type:"POST",
				url:"<?php echo site_url('afg_c_income/update_agent_coloader'); ?>",
				data:
				{
					id_sd : spd_id,
					kode_ref : $('#kode_ref_income').val(),
					qty : $("#tabel_sp_detail").jqGrid('getCell', spd_id,'bl_meas'),
				},
				success:function(response)
				{
					var data = $.parseJSON(response);
					$("#table-frm-agent").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
					$("#table-frm-coloader").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
					save_data();
				}
			})
		}	
	})
	
	table_from_selling();	
	table_from_agent();

	// Isi Quotation No. setelah context jobsheet/grid siap.
	setTimeout(function(){
		if (typeof load_quotation_options === 'function') load_quotation_options();
		if (typeof refresh_quotation_link_state === 'function') refresh_quotation_link_state();
	}, 300);
	$(document).off('change.incomeQuotation', '#dropdownCountries')
		.on('change.incomeQuotation', '#dropdownCountries', function(){
			if (typeof load_quotation_options === 'function') load_quotation_options();
		});
	var code = 	$('#txtRefno2').val();
	if (code.substr(4,3)=='102'||code.substr(4,3)=='302'){
	table_from_coloader();
	}
})

if(!base_url)
{
	base_url=window.location.protocol+'//'+window.location.hostname+(window.location.port+':'+window.location.port)
}


/* ===========================
   Inline Edit Helpers (improved)
=========================== */
const __incomeSaveTimers = {};
const __incomePendingFns = {};
function _debounce(key, fn, wait = 250){
  __incomePendingFns[key] = fn;
  try { clearTimeout(__incomeSaveTimers[key]); } catch(e) {}
  __incomeSaveTimers[key] = setTimeout(function(){
    delete __incomePendingFns[key];
    fn();
  }, wait);
}

// Paksa jalankan semua ajax save yang masih nunggu debounce (dipanggil manual
// dari luar, misalnya sebelum pindah tab) + commit cell yang lagi aktif diedit.
// PATCH: mengatasi data hilang saat user ganti tab / klik tombol lain
// sebelum debounce sempat jalan.
window.incomeFlushAllGrids = function () {
  try { jqGridSaveActiveCell('#table-frm-selling'); } catch (e) {}
  try { jqGridSaveActiveCell('#table-frm-agent'); } catch (e) {}
  try { jqGridSaveActiveCell('#table-frm-coloader'); } catch (e) {}

  Object.keys(__incomeSaveTimers).forEach(function (key) {
    clearTimeout(__incomeSaveTimers[key]);
    var fn = __incomePendingFns[key];
    if (fn) {
      delete __incomePendingFns[key];
      try { fn(); } catch (e) { console.error(e); }
    }
  });
};

// Jaring pengaman: kalau user beneran nutup tab browser / reload / pindah
// halaman, sisa data yang belum sempat ke-debounce tetap dipaksa terkirim.
window.addEventListener('beforeunload', function () {
  window.incomeFlushAllGrids();
});
document.addEventListener('visibilitychange', function () {
  if (document.hidden) window.incomeFlushAllGrids();
});

// PATCH: helper formatter supaya qty/unit_price/price tidak menampilkan
// "0.00" / "0.0000" bawaan saat nilainya kosong atau nol.
// Value tetap tersimpan 0 di database (lihat beforeSaveCell), cuma
// TAMPILANNYA saja yang dikosongkan.
//
// PENTING: saat formatter berupa FUNCTION (bukan string 'number'), jqGrid
// TIDAK mengirim formatoptions langsung di parameter kedua — yang dikirim
// adalah {rowId, colModel, gid, pos}. formatoptions kolom (decimalPlaces,
// thousandsSeparator, decimalSeparator) harus diambil dari opts.colModel.
// Ini yang kemarin bikin qty kehilangan 4 desimal & unit price/price
// kehilangan pemisah ribuan.
function numOrBlank(cellval, opts) {
  var fmtOpts = (opts && opts.colModel && opts.colModel.formatoptions) || {};
  var cleaned = String(cellval == null ? '' : cellval).replace(/,/g, '');
  var n = parseFloat(cleaned);
  if (cellval === '' || cellval == null || isNaN(n) || n === 0) return '';
  return $.fmatter.util.NumberFormat(n, fmtOpts);
}

// Dipakai di editoptions.dataInit kolom qty/unit_price supaya textbox
// edit langsung kosong (bukan "0.00") kalau nilainya nol, jadi user
// tidak perlu hapus dulu sebelum ngetik.
function clearZeroOnEdit(el) {
  if (!el.value || parseFloat(String(el.value).replace(/,/g, '')) === 0) {
    el.value = '';
  }
}

function _parseGridNumber(v){
  if (v === null || v === undefined) return 0;
  const s = String(v).replace(/,/g,'').trim();
  const n = parseFloat(s);
  return isNaN(n) ? 0 : n;
}

function _toastWarn(msg){
  if (typeof Toast !== 'undefined' && Toast.fire) {
    Toast.fire({ icon:'warning', title: msg });
  } else {
    alert(msg);
  }
}

function _ensureCurrency(v){
  const s = String(v || '').trim();
  return s ? s : 'USD';
}

function _recalcPrice(grid, rowid, tableName){
  const qty = _parseGridNumber(grid.jqGrid('getCell', rowid, 'qty'));
  const unitPrice = _parseGridNumber(grid.jqGrid('getCell', rowid, 'unit_price'));
  const price = qty * unitPrice;

  grid.jqGrid('setCell', rowid, 'price', price);

  $.ajax({
    type : 'POST',
    url  : base_url + '/afg_c_income/update_price',
    dataType: 'json',
    data : { id: rowid, price: price, table: tableName },
    global: false
  }).done(function(res){
    var selector = tableName === 'inf_income_from_agent' ? '#table-frm-agent' :
                   (tableName === 'inf_income_from_coloader' ? '#table-frm-coloader' : '#table-frm-selling');
    handleIncomeMutationResponse(res, selector);
  }).fail(function(xhr){
    var selector = tableName === 'inf_income_from_agent' ? '#table-frm-agent' :
                   (tableName === 'inf_income_from_coloader' ? '#table-frm-coloader' : '#table-frm-selling');
    var res = null;
    try { res = JSON.parse(xhr.responseText); } catch(e) {}
    handleIncomeMutationResponse(res || {status:0,error:'Gagal validasi billing'}, selector);
  });

  return price;
}

/* ===========================
   Table Selling (tidak diubah, hanya alert tetap)
=========================== */
function table_from_selling()
{
	var grid = $("#table-frm-selling"), i;	
	var kode_ref = $('#kode_ref_income').val();
	var spd_id = $('#spd_id_income').val();
	var kode_refnya = kode_ref.substr(4,3);
	if (kode_refnya == '300'||kode_refnya == '301'||kode_refnya == '302'||kode_refnya == '600'||kode_refnya == '400'||kode_refnya == '402') {
		var gridnya = $("#tabel_sp_detail"), i;	
		var destination = gridnya.jqGrid('getCell', spd_id,'destination');	
		var shipper = gridnya.jqGrid('getCell', spd_id,'shipper');
	}
	else 
	{
		var gridnya = $("#tabel_sp_detail"), i;
		var destination = gridnya.jqGrid('getCell', spd_id,'destination');	
		var shipper = gridnya.jqGrid('getCell', spd_id,'shipper');
	}
	
	var fcl = $('select[id="dropdownTypeOfTransaction"]').val();
	
	if(fcl =='FCL' || fcl == 'SF IMPORT FCL'){
		var qty = gridnya.jqGrid('getCell', spd_id,'no_pack_qty');
	}
	else
	{
		var qty = gridnya.jqGrid('getCell', spd_id,'weight');
	}
	
	var lock = $('#lock').val();
	if (lock==1) {
		get_cell_edit = false;
		get_cell_del = false;
	} else {
		get_cell_edit = true;
		get_cell_del = true;
	}

	grid.jqGrid({ 
		url	:	base_url+'/afg_c_income/tampil_selling_price',
		mtype : "post",
		datatype: "json",
		sortname: "id",
		sortorder: "asc",
		postData: { 
			spd : spd_id,
			kode_ref : $('#kode_ref_income').val(),
			shipper_id : $("#tabel_sp_detail").jqGrid('getCell', spd_id,'shipper'),
			sales : $('#sales').val(),
			destination : destination,
			qty : qty,
		},
		ajaxSelectOptions: {type: "GET"}, 
		colNames:
		[
			'',
			'kode_ref',
			'mchgs',
			'valid',
			'Container',
			'Code#',
			'Item Service',
			'Quantity', 
			'Price/Unit',
			'Price',
			'Currency',
			'Prep/Coll',
			'Receive From', 
			'Remarks', 
			'Agent#', 
			'Grup',
			'PPN',
			'INV / DB',
			'Inv No',
			'Ctk Rt Cfm ',
			'Created By',
			'Created At',
			'Updated By',
			'Updated At',
			''
		],
    	colModel:
		[
			{name:'sp_detail_id',index:'sp_detail_id', align:"left", width:30, editable: true, hidden: true, },
			{name:'kode_ref',index:'kode_ref', align:"left", width:100, editable: true, hidden: true, },
			{name:'mchgs', index:'mchgs', align:"left", width:10, editable: true, hidden: true},
			{name:'valid', index:'valid', align:"left", width:10, editable: true, hidden: true},
			{name:'container',index:'container', align:"center", width:60, 
				editable: true, 
				edittype:"select",
				editoptions:{
					dataUrl	: base_url+'/afg_c_dropdown/dropdownContainer',
					dataInit: function (el) { $(el).css({'max-width':'60px'}); },
					dataEvents: [{type:'change', fn:function(){ on_edit(true); }}]
				},
			},
			{name:'code_service',index:'code_service', align:"left", width:50, editable: true, hidden: false,
				editrules:{edithidden:true}, 
				edittype:"select",
				editoptions:{
					dataUrl:base_url+'/afg_c_dropdown/dropdownService',
					dataInit: function (el) { $(el).css({'min-width':'45px','max-width':'45px'}); },
					dataEvents: [{
					  type: 'change',
					  fn: function () {
						on_edit(true);

						var code  = this.value;
						var rowid = $(this).closest('tr.jqgrow').attr('id');

						$.ajax({
						  type: "POST",
						  url: base_url + '/afg_c_dropdown/item_service_nya',
						  data: { code_service: code },
						  success: function (res) {
							var item = $.parseJSON(res) || '';

							// update grid display
							grid.jqGrid('setCell', rowid, 'item_service', item);

							// ✅ simpan hanya 2 field ini (setelah item sudah ada)
							_debounce('sell:svc:' + rowid, function () {
							  ajaxSaveIncomeCell({
								table: 'inf_income_selling_price',
								id: rowid,
								kode_ref: $('#kode_ref_income').val(),
								changes: { code_service: code, item_service: item }
							  });
							}, 150);
						  }
						});
					  }
					}]

				}
			},
			{name:'item_service',index:'item_service', align:"left", width:150, editable: true,
				editoptions:{
					dataInit: function(element) { $(element).css({ "text-transform": "uppercase"}) },
      			dataEvents: [{type:'change',fn:function(){ on_edit(true); }}]
				},
				cellattr: function(){ return 'style="text-transform: uppercase"'; }
			},
			{name:'qty',index:'qty', align:"center", width:60, editable: true, 
				formatter : numOrBlank,
				formatoptions: { decimalSeparator: ".", thousandsSeparator: " ", decimalPlaces: 4, defaultValue: '' },
				editoptions: {
				  dataInit: function (e) {
					clearZeroOnEdit(e);
					$(e).css({ 'max-width': '60px', 'min-width': '60px', 'text-align': 'center' });
				  },
				  dataEvents: [
					{
					  type: 'keyup',
					  fn: function (e) {
						format_number(this, 4);
						on_edit(true);
					  }
					},
					{
					  type: 'change',
					  fn: function (e) {
						format_number(this, 4);
					  }
					}
				  ]
				}
			},
			{
			  name: 'unit_price',
			  index: 'unit_price',
			  align: 'right',
			  width: 60,
			  editable: true,
			  formatter: numOrBlank,
			  formatoptions: {
				decimalSeparator: '.',
				thousandsSeparator: ',',
				decimalPlaces: 2,
				defaultValue: ''
			  },
			  editoptions: {
				dataInit: function (e) {
				  clearZeroOnEdit(e);
				  $(e).css({
					'max-width': '60px',
					'min-width': '60px',
					'text-align': 'center'
				  });
				},
				dataEvents: [
				  {
					type: 'keyup',
					fn: function () {
					  // Format angka saat diketik
					  format_number(this, 2);

					  // Trigger logika edit
					  on_edit(true);

					  // Ambil data dari grid
					  var rowid = grid.jqGrid('getGridParam', 'selrow');
					  var qty = parseFloat(grid.jqGrid('getCell', rowid, 'qty') || 0);
					  var unit_price = parseFloat(this.value || 0);
					  var price = qty * unit_price;

					  // Update kolom price
					  grid.jqGrid('setCell', rowid, 'price', price.toFixed(2));

					  
					}
				  },
				  {
					type: 'change',
					fn: function () {
					  format_number(this, 2);
					}
				  }
				]
			  }
			},
						{name:'price',index:'price', align:"right", width:70, 
				editoptions:{ disabled: true },
				formatter: numOrBlank,
    			formatoptions: { decimalSeparator: ".", thousandsSeparator: ",", decimalPlaces: 2, defaultValue: '' },
			},
			{name:'currency',index:'currency', align:"center", width:50, editable: true, 
				edittype:"select", formatter:'select', 
				editoptions:{
					value:" : ;RP.:RP.;USD:USD",
					dataInit: function (el) { 
						$(el).css({'min-width':'50px','max-width':'50px'}); 
						if (!$(el).val() || String($(el).val()).trim()==='') $(el).val('USD');
					},
					dataEvents: [{
						type:'change',
						fn:function(){
							on_edit(true);
							if (!this.value || String(this.value).trim()==='') this.value = 'USD';
						}
               }]
				}
			},
			{name:'prep_coll',index:'prep_coll', align:"center", width:55, editable: true, edittype:"select", formatter:'select', 
				editoptions:{
				 	value:" : ;PREPAID:PREPAID;COLLECT:COLLECT",
				 	dataInit : function (el) { $(el).css({'min-width':'45px','max-width':'45px'}); },
					dataEvents: [{
						type:'change',
						fn:function(){
							on_edit(true);
							var rowid = grid.jqGrid('getGridParam', 'selrow');
							grid.jqGrid('setCell',rowid,'invoice_db', (this.value==='COLLECT' ? 'DEBIT NOTE':'INVOICE'));
						}
               }]
				 }
			},
			{name:'receive_from',index:'receive_from', align:"left", width:80, editable: true, 
				editoptions:{ dataInit: el => $(el).css('text-transform','uppercase'), dataEvents: [{type:'keyup',fn:()=>on_edit(true)},{type:'change',fn:()=>on_edit(true)}] },
				cellattr: ()=>'style="text-transform: uppercase"',
			},
			{name:'remark',index:'remark', align:"left", width:80, editable: true, 
				editoptions:{ dataInit: el => $(el).css('text-transform','uppercase'), dataEvents: [{type:'keyup',fn:()=>on_edit(true)},{type:'change',fn:()=>on_edit(true)}] },
				cellattr: ()=>'style="text-transform: uppercase"',
			},
			{name:'agent', index:'agent', width:120, editable:true,
			  edittype:"text",
			  editoptions:{
				dataInit: function(el){
				  $(el).autocomplete({
					source: function(request, response){
					  $.ajax({
						url: base_url+'/afg_c_dropdown/dropdownAgentAjax',
						dataType: 'json',
						data: { q: request.term },
						success: function(data){
						  response($.map(data, function(row){
							return { label: row.agent_company_code, value: row.agent_company_code };
						  }));
						}
					  });
					},
					minLength: 2
				  });
				}
			  }
			},
			{name:'g',index:'g', align:"center", width:40, editable: true,
				editoptions:{ dataInit: el => $(el).css('text-transform','uppercase'), dataEvents: [{type:'keyup',fn:()=>on_edit(true)},{type:'change',fn:()=>on_edit(true)}] },
				formatter: function(el){ return el? String(el).toUpperCase() : "" }
			},
			{name:'ppn',index:'ppn', align:"center", width:50, editable: true, edittype:"select",
				editoptions:{
					value:" : ;1.1%:1.1%;1.2%:1.2%;12%:12%",
					dataInit: el => $(el).css({'min-width':'50px','max-width':'50px'}),
					dataEvents: [{
                  type:'change',
                  fn:function(){
                  	on_edit(true);
                  	var rowid = grid.jqGrid('getGridParam', 'selrow')
					// PPN disimpan oleh afterSaveCell ke source Income.
					// Jangan update x_finance_item langsung; invoice dibangun ulang saat re-post.
                  }
               }],
				},
				formatter:'select',
			},
			{name:'invoice_db',index:'invoice_db', align:"center", width:60, editable: false, hidden: false,},
			{name:'ship_co',index:'ship_co', align:"left", width:80, editable: false, editoptions:{disabled: true}, },
			{name:'cetak_rt_cfm',index:'cetak_rt_cfm', width:200, hidden: true,},
			{name:'created_by',index:'created_by', width:200, hidden: false, editoptions:{disabled: true},},
  			{name:'created_at',index:'update_at', width:200, hidden: false, editoptions:{disabled: true},},
  			{name:'updated_by',index:'updated_by', width:200, hidden: false, editoptions:{disabled: true},},
  			{name:'updated_at',index:'updated_at', width:200, hidden: false, editoptions:{disabled: true},},
			{name:'payment',index:'payment', width:200, hidden: true,},
		], 
		width	: 1273,
		height	: 'auto',
		shrinkToFit	: false,
		loadonce	:false,
  		viewrecords	: true,
		multiselect:true,
		modal: true,
		footerrow: false,
		rownumbers: true,
		rowNum: 1000,
		gridview: true,
		rowList:[-0],
		caption:"Get From Selling Price",
		editurl	:	base_url+'/afg_c_income/manage_income',
		cellEdit:true,
		cellsubmit:'clientArray',
		pager:'#pager-frm-selling',
		   
   
        beforeSaveCell: function(rowid, cellname, value, iRow, iCol) {
			if (cellname === 'currency') {
				var v = String(value || '').trim();
				if (!v) {
					_toastWarn('Currency wajib diisi');
					return grid.jqGrid('getCell', rowid, 'currency') || 'USD';
				}
				return v;
			}
			if (cellname === 'qty' || cellname === 'unit_price') {
				var cleaned = String(value || '').replace(/,/g,'').trim();
				if (cleaned === '') return '0';
				if (isNaN(Number(cleaned))) {
					_toastWarn(cellname + ' harus angka');
					return grid.jqGrid('getCell', rowid, cellname);
				}
				return cleaned;
			}
			return value;
		},
	    afterSaveCell: function (rowid, cellname, value, iRow, iCol) {
			try {
				on_edit(true);
				markJobsheetDirty($('#kode_ref_income').val());

				// Normalisasi basic (hindari HTML nyelip)
				if (typeof value === 'string') value = value.replace(/<[^>]*>/g,'').trim();
				
				// Jangan autosave di sini untuk code_service
				if (cellname === 'code_service') {
					setTimeout(function(){
						focusNextEditableCell(grid, iRow, iCol + 1);
					}, 0);
					return;
				}

				// ==============================================================
				// BLOK 1: KHUSUS QTY & UNIT PRICE (Kirim 3 Field Sekaligus)
				// ==============================================================
				if (cellname === 'qty' || cellname === 'unit_price') {
					var qty = String(grid.jqGrid('getCell', rowid, 'qty') || '0').replace(/[, ]/g,'');
					var up  = String(grid.jqGrid('getCell', rowid, 'unit_price') || '0').replace(/[, ]/g,'');

					qty = parseFloat(qty || 0);
					up  = parseFloat(up || 0);

					var price = qty * up;

					// Update UI (Layar)
					grid.jqGrid('setCell', rowid, 'price', price.toFixed(2));

					// Kirim ke Backend (Kirim ketiganya agar DB update sempurna)
					_debounce('sell:' + rowid, function () {
						ajaxSaveIncomeCell({
							table: 'inf_income_selling_price',
							id: rowid,
							kode_ref: $('#kode_ref_income').val(),
							changes: { qty: qty, unit_price: up, price: price }
						});
					}, 250);

					return;
				}

				// ==============================================================
				// BLOK 2: KHUSUS PREP/COLL (Kirim 2 Field)
				// ==============================================================
				if (cellname === 'prep_coll') {
					var inv = (value === 'COLLECT') ? 'DEBIT NOTE' : 'INVOICE';
					grid.jqGrid('setCell', rowid, 'invoice_db', inv);

					_debounce('sell:' + rowid, function () {
						ajaxSaveIncomeCell({
							table: 'inf_income_selling_price',
							id: rowid,
							kode_ref: $('#kode_ref_income').val(),
							changes: { prep_coll: value, invoice_db: inv }
						});
					}, 250);
					
					return;
				}

				// ==============================================================
				// BLOK 3: DEFAULT (OPSI 2 - HANYA KIRIM 1 FIELD YANG DIEDIT SAJA)
				// Ini menyelamatkan kita dari bug TAB ke kolom QTY yang terbaca 0
				// ==============================================================
				_debounce('sell:' + rowid, function () {
					var partialChanges = {};
					partialChanges[cellname] = value; // Cuma isi dengan field yang barusan diketik

					ajaxSaveIncomeCell({
						table: 'inf_income_selling_price',
						id: rowid,
						kode_ref: $('#kode_ref_income').val(),
						changes: partialChanges
					});
				}, 250);

				// Pindah fokus cell
				setTimeout(function () {
					focusNextEditableCell($("#table-frm-selling"), iRow, iCol + 1);
				}, 0);

			} catch (e) {
				console.error(e);
			}
		},
		loadComplete: function() {
			// PATCH: total dihitung dari DATA MENTAH grid (getRowData), bukan dari
			// teks yang sudah diformat via getCol(...,'sum'). getCol(...,'sum')
			// mem-parseFloat teks tampilan yang sudah ada pemisah ribuannya
			// (mis. "4,000,000.00") sehingga hanya kebaca "4" -> total jadi salah.
			// Total ditampilkan sebagai BAR TERPISAH (bukan ikut lebar kolom grid),
			// berisi 2 kolom saja: Total IDR & Total USD, ditaruh tepat di atas pager.
			var rows = grid.jqGrid('getRowData');
			var totalIDR = 0, totalUSD = 0;
			rows.forEach(function (row) {
				var raw = String(row.price == null ? '' : row.price).replace(/,/g, '');
				var n = parseFloat(raw);
				if (isNaN(n)) return;
				var cur = String(row.currency || '').trim().toUpperCase();
				if (cur.indexOf('USD') === 0) {
					totalUSD += n;
				} else {
					totalIDR += n;
				}
			});

			var fmt = function (v) {
				return v.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
			};

			// Bikin bar total sekali saja (kalau sudah ada, tinggal update isinya
			// setiap loadComplete dipanggil ulang - jadi nggak numpuk elemen baru).
			var $totalBar = $('#income-selling-total-bar');
			if ($totalBar.length === 0) {
				$totalBar = $(
					'<div id="income-selling-total-bar" style="display:flex;width:100%;box-sizing:border-box;' +
					'border:1px solid #9eb8d9;border-top:none;background:#eaf1fb;font-weight:bold;font-size:12px;">' +
						'<div class="income-total-idr" style="flex:1 1 50%;text-align:center;padding:6px 4px;border-right:1px solid #9eb8d9;"></div>' +
						'<div class="income-total-usd" style="flex:1 1 50%;text-align:center;padding:6px 4px;"></div>' +
					'</div>'
				);
				$('#pager-frm-selling').before($totalBar);
			}
			$totalBar.find('.income-total-idr').text('Total IDR: ' + fmt(totalIDR));
			$totalBar.find('.income-total-usd').text('Total USD: ' + fmt(totalUSD));

			on_edit(false)
			$("#cb_"+grid[0].id).hide();
			$(".ui-jqgrid-titlebar-close",$("#table-frm-selling")[0].grid.cDiv).hide();
			var ids = grid.jqGrid('getDataIDs');
    		if (ids.length>=1) { grid.jqGrid('editCell',ids.length,6,true); }
    	},	
    	
    	
    	beforeSelectRow: function(rowid) {
			var lock = grid.jqGrid('getCell', rowid, 'payment');
			var cols = ['container','code_service','item_service','qty','unit_price','currency','prep_coll','receive_from','remark','agent','g','invoice_db'];

			// ambil user_id dari PHP session
			var userId = <?php echo (int)$this->session->userdata('user_id'); ?>;

			cols.forEach(function(c){
				// jika userId 107 atau 0, tetap editable walau paid
				var editable = (lock !== 'paid' || userId === 107 || userId === 0);
				$(grid).jqGrid('setColProp', c, { editable: editable });
			});

			var cbsdis = $("tr#"+rowid+".jqgrow > td > input.cbox:disabled", grid[0]);
			var gridId = $.jgrid.jqID(this.id);

			if (lock === 'paid' && !(userId === 107 || userId === 0)) {
				$("#edit_" + gridId).addClass('ui-state-disabled');
				$("#del_" + gridId).addClass('ui-state-disabled');
			} else {
				$("#edit_" + gridId).removeClass('ui-state-disabled');
				$("#del_" + gridId).removeClass('ui-state-disabled');
			}

			return cbsdis.length === 0;
		},

		onSelectRow: function(rowid, status){
			var selected = $("tr.jqgrow > td > input.cbox:checked", grid[0]);
			selected[rowid] = status;
			if(status){ on_edit(true) } else { on_edit(false) }

			var gridId = $.jgrid.jqID(this.id);
			var lock = $(this).jqGrid('getCell', rowid, 'payment');
			var userId = <?php echo (int)$this->session->userdata('user_id'); ?>;

			if (lock === 'paid' && !(userId === 107 || userId === 0)) {
				$("#edit_" + gridId).addClass('ui-state-disabled');
				$("#del_" + gridId).addClass('ui-state-disabled');
			} else {
				$("#edit_" + gridId).removeClass('ui-state-disabled');
				$("#del_" + gridId).removeClass('ui-state-disabled');
			}
		},
	})
	
	grid.jqGrid('navGrid','#pager-frm-selling', { edit: false, add: false, del: true, refresh: true, <?php echo 'search: false' ?>},{ },{ },
     	{	
     		height: "auto", width: "auto",
     			serializeDelData: function (postdata) {
     				return {
    					id: postdata.id, 
    					oper : postdata.oper, 
    					kolom : grid.jqGrid('getCell',postdata.id,'code_service'),
    					kode_ref : $('#kode_ref_income').val(),
    				};
				},
     			afterSubmit: function () {
    				grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
    				return [true];
				},
		},
      { },
     	{ }
	)
	grid.jqGrid('navButtonAdd','#pager-frm-selling',{
	  caption:"",
	  buttonicon:"ui-icon-plus",
	  onClickButton: function() {
		  const salesVal = $('#sales').val();
		  if (!salesVal || String(salesVal).trim() === '') {
			if (typeof Swal !== 'undefined' && Swal.fire) {
			  Swal.fire({
				title: 'Sales belum dipilih',
				text: 'Silakan pilih sales terlebih dahulu sebelum input income.',
				icon: 'warning'
			  });
			} else {
			  alert('Sales belum dipilih. Pilih dulu sebelum update.');
			}
			return; // stop eksekusi
		  }

		  // kalau lolos validasi
		  insert_data_selling_price();
		},
	  position: "last",
	  title:"Insert Data Selling Price",
	  cursor: "pointer",
	  id:"btnAddNewIncome",
	});
	
	grid.navButtonAdd('#pager-frm-selling',{ 
			caption:"", 
			buttonicon:"ui-icon-disk", 
			onClickButton: tarik_data_selling_price, 
			position: "last", 
			title:"Tarik Data Selling Price", 
			cursor: "pointer",
			id:"btnTrkSelling",
	})
	var user = <?= $this->session->userdata('user_id');?>;
	if (user === 0){
	 grid.navButtonAdd('#pager-frm-selling',{ 
			caption:"", 
			buttonicon:"ui-icon-newwin", 
			onClickButton: tarik_data_pajak, 
			position: "last", 
			title:"Tarik Data Pajak", 
			cursor: "pointer",
			id:"btnTrkPjk",
	})
	}
 	var div = $(".ui-jqgrid-bdiv");
 	var nx = div.length-1;
 	var divx = div[nx];
 	$(divx).css({'min-height':'100px'})
}

function _computeInvoiceDb(prepColl){
  return (String(prepColl).toUpperCase() === 'COLLECT') ? 'DEBIT NOTE' : 'INVOICE';
}

function pick(obj, keys) {
  const out = {};
  keys.forEach(k => {
    if (obj[k] !== undefined) out[k] = obj[k];
  });
  return out;
}

// buang kemungkinan HTML <input ...> yang kadang nyasar dari formatter lama
function stripHtml(v) {
  if (v == null) return '';
  return String(v).replace(/<[^>]*>/g, '').trim();
}

// normalisasi angka dari jqGrid (yang kadang ada koma/spasi)
function normNum(v) {
  if (v == null || v === '') return '0';
  return String(v).replace(/,/g, '').replace(/\s/g, '').trim();
}

// ambil semua field row yang valid untuk inf_income_selling_price
function getIncomeRowPayload(grid, rowid) {
  const row = grid.jqGrid('getRowData', rowid);

  // whitelist sesuai kolom DB (sesuaikan kalau ada kolom lain)
  const keys = [
    'sp_detail_id','kode_ref','container','code_service','item_service',
    'qty','mchgs','unit_price','price','currency','prep_coll',
    'receive_from','remark','agent','ship_co','payment','g','ppn',
    'invoice_db','cetak_rt_cfm','ditarik','region_id'
  ];

  const payload = pick(row, keys);

  // bersihkan field text (hindari kejadian <input ...> nyasar)
  payload.container  = stripHtml(payload.container);
  payload.code_service  = stripHtml(payload.code_service);
  payload.item_service  = stripHtml(payload.item_service);
  payload.currency  = stripHtml(payload.currency);
  payload.prep_coll  = stripHtml(payload.prep_coll);
  payload.receive_from  = stripHtml(payload.receive_from);
  payload.remark        = stripHtml(payload.remark);
  payload.agent         = stripHtml(payload.agent);
  payload.g             = stripHtml(payload.g);
  payload.ppn            = stripHtml(payload.ppn);

  // normalisasi numeric
  payload.qty        = normNum(payload.qty);
  payload.unit_price = normNum(payload.unit_price);
  payload.price      = normNum(payload.price);

  // safety: currency jangan kosong
  if (!payload.currency || !String(payload.currency).trim()) payload.currency = 'USD';

  return payload;
}


function handleIncomeMutationResponse(res, gridSelector) {
  if (!res || Number(res.status) !== 1) {
    var msg = (res && (res.error || res.message)) || 'Perubahan income gagal disimpan';
    if (typeof Swal !== 'undefined' && Swal.fire) {
      Swal.fire('Tidak dapat disimpan', msg, 'error');
    } else {
      alert(msg);
    }
    if (gridSelector) {
      $(gridSelector).jqGrid('setGridParam', {datatype:'json'}).trigger('reloadGrid');
    }
    return false;
  }

  if (res.auto_unbilled) {
    var oldNo = res.old_invoice_no ? (' dari ' + res.old_invoice_no) : '';
    if (Toast && Toast.fire) {
      Toast.fire({icon:'info', title:'Income otomatis UNBILLED' + oldNo});
    }
  }
  return true;
}

function ajaxSaveIncomeCell(payload) {
  return $.ajax({
    url: base_url + '/afg_c_income/saving_income',
    type: 'POST',
    dataType: 'json',
    data: payload,
    global: false,
    async: true
  }).done(function(res){
    handleIncomeMutationResponse(res, '#table-frm-selling');
  }).fail(function(xhr){
    var res = null;
    try { res = JSON.parse(xhr.responseText); } catch(e) {}
    handleIncomeMutationResponse(res || {status:0, error:'ERP tidak dapat memvalidasi status billing'}, '#table-frm-selling');
  });
}

// cari kolom editable berikutnya (skip hidden / non-editable)
function focusNextEditableCell(grid, iRow, startCol) {
  try {
    var cm = grid.jqGrid('getGridParam', 'colModel');
    for (var c = startCol; c < cm.length; c++) {
      if (!cm[c].hidden && cm[c].editable) {
        grid.jqGrid('editCell', iRow, c, true);
        return true;
      }
    }
  } catch(e) {}
  return false;
}


/* ===========================
   Pajak / helper lain (tetap)
=========================== */
function tarik_data_pajak(){
	var grid = $("#table-frm-selling"), i;	
	var myData = grid.jqGrid('getRowData');
	if (myData.length>=1) {
		$.post(base_url+'/afg_c_income/tarik_data_pajak' ,  {myData},
			function(response) {
			 var data = $.parseJSON(response);
			 grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
		})
	}else {
		if (Toast) Toast.fire({icon:'info', title:'Selling Price Income Empty'});
	}
}

function tarik_data_pajak_coloader(){
	var grid = $("#table-frm-coloader"), i;	
	var myData = grid.jqGrid('getRowData');
	if (myData.length>=1) {
		$.post(base_url+'/afg_c_income/tarik_data_pajak_coloader' ,  {myData},
			function(response) {
			 var data = $.parseJSON(response);
			 grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
		})
	}else {
		if (Toast) Toast.fire({icon:'info', title:'Insentive data is empty'});
	}
}

function insert_table(ppn,kode_ref) {
	$.post(base_url+'/afg_c_income/insert_table' ,  {"ppn":ppn,"kode_ref":kode_ref});
}

function on_edit(ch){}

/* ===========================
   Saving rows (tetap)
=========================== */
function jqGridSaveActiveCell(gridSelector) {
  var $g = $(gridSelector);
  if (!$g.length) return;
  try {
    var iRow = $g.jqGrid('getGridParam', 'iRow');
    var iCol = $g.jqGrid('getGridParam', 'iCol');
    if (iRow != null && iCol != null) $g.jqGrid('saveCell', iRow, iCol);
  } catch (e) {}
}

function _stripHtml(v){
  return String(v == null ? '' : v).replace(/<[^>]*>/g,'').trim();
}

function saving_income(rowid, cellname, value){
  var grid = $("#table-frm-selling");

  // ✅ kunci utama: commit editor aktif (hindari <input ...> masuk DB)
  jqGridSaveActiveCell('#table-frm-selling');

  // ambil data row “lebih mentah” kalau tersedia
  var myData = null;
  try { myData = grid.jqGrid('getLocalRow', rowid); } catch(e) { myData = null; }
  if (!myData) myData = grid.jqGrid('getRowData', rowid);

  // buang HTML yang nyangkut
  ['container','code_service','item_service','qty','unit_price','price','currency',
   'prep_coll','receive_from','remark','agent','g','ppn','invoice_db'
  ].forEach(function(k){
    if (typeof myData[k] !== 'undefined') myData[k] = _stripHtml(myData[k]);
  });

  // normalisasi angka
  ['qty','unit_price','price'].forEach(function(k){
    if (typeof myData[k] !== 'undefined') {
      myData[k] = String(myData[k]).replace(/,/g,'').replace(/\s+/g,'').trim();
      if (myData[k] === '') myData[k] = '0';
    }
  });

  if (!myData.currency) myData.currency = 'USD';

  var kode_ref = $('#kode_ref_income').val();

  $.ajax({
    type:"POST",
    url : base_url+'/afg_c_income/saving_income',
    data:{ id: rowid, myData: myData, kode_ref: kode_ref }
  });
}


function update_data_selling_price(){
	var grid = $("#table-frm-selling"), i;	
	var myData = grid.jqGrid('getRowData');
	var id = grid.jqGrid('getDataIDs');
	$.ajax({
		type:"POST",
		url	:base_url+'/afg_c_income/insert_data_selling',
		data:{ myData:myData, id:id },
		success:function(response){
			var data = $.parseJSON(response);
			if(data.status==1){
				grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
			}
		}
	})
}

function insert_data_selling_price(){
	var grid = $("#table-frm-selling"), i;
	$.ajax({
		type:"POST",
		url	:base_url+'/afg_c_income/insert_data_selling_price',
		data:{ sp_detail_id:$('#spd_id_income').val(), kode_ref:$('#kode_ref_income').val() },
		success:function(response){
			var data = $.parseJSON(response);
			if(data.status==1){
				var dataSatu = [{'id':data.id}];
				grid.jqGrid('addRowData',i,dataSatu,'last');
				grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
			}
		}
	})
}

/* ===========================
   Tarik Selling Price (tetap)
=========================== */
function tarik_data_selling_price(){
	var grid = $("#table-frm-selling"), i;	
	var spd_id = $('#spd_id_income').val();
	var kode_ref = $('#kode_ref_income').val();
	var kurs = $('#kurs').val();
	var kode_refnya = kode_ref.substr(4,3);
	if (kode_refnya == '300'||kode_refnya == '301'||kode_refnya == '302'||kode_refnya == '600'||kode_refnya == '400'||kode_refnya == '402') {
		var gridnya = $("#tabel_sp_detail_export"), i;	
		var destination = gridnya.jqGrid('getCell', spd_id,'destination');	
		var shipper = gridnya.jqGrid('getCell', spd_id,'shipper');
		var sales = $('#sales').val();
	}
	else 
	{
		var gridnya = $("#tabel_sp_detail"), i;
		var destination = gridnya.jqGrid('getCell', spd_id,'destination');	
		var shipper = gridnya.jqGrid('getCell', spd_id,'shipper');
		var sales = $('#sales').val();
	}
	
	if(destination ==''){
		var destination = $('#dropdownCountries').val();
	}
	var shipment = $("input[name='shipment_type']:checked").val();
	var fcl = $('select[id="dropdownTypeOfTransaction"]').val(); 
	var berat = (gridnya.jqGrid('getCell', spd_id,'weight')*0.001);
	var qty;
	if(fcl =='FCL' || fcl == 'SF IMPORT FCL' || shipment == 'FCL'){
		qty = gridnya.jqGrid('getCell', spd_id,'no_pack_qty');
	}
	else if ( berat >= gridnya.jqGrid('getCell', spd_id,'bl_meas'))
	{
		qty = berat;
	}
	else
	{
    qty = gridnya.jqGrid('getCell', spd_id,'bl_meas');
  }
	
	$.ajax({
		type: "POST",
		url : base_url+'afg_c_income/tarik_data_selling_price',
		data: { 
			spd: spd_id,
			kode_ref: kode_ref,
			shipper_id: shipper,
			sales: sales,
			destination: destination,
			valid: $("#txtETDJKT").val(),
			shipment: shipment,
			qty: qty,
			container: $("#txtTOC2").val(),
			type: fcl,
			kurs: kurs,
		},
		success: function(){
			$("#table-frm-selling").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
		},
		error:function(){ return false; }
	})
}

function save_cell_selling_perice(rowid,cellname,value){
	var grid = $("#table-frm-selling"), i;
	$.ajax({
		type:"POST",
		url	:base_url+'/afg_c_income/manage_income',
		data:{
			spd : $('#spd_id_income').val(),
			kode_ref : $('#kode_ref_income').val(),
			id : rowid,
			qty : grid.jqGrid('getCell', rowid,'qty'),
			mchgs : grid.jqGrid('getCell', rowid,'mchgs'),
			agent: grid.jqGrid('getCell', rowid,'agent'),
			unit_price: grid.jqGrid('getCell', rowid,'unit_price'),
			kolom : cellname,
			isi : value,
			oper : 'edit',
		}
	})
}

/* ===========================
   Table From Agent (tidak diubah alert)
=========================== */
function table_from_agent()
{
	var spd_id = $('#spd_id_income').val();	
	var grid = $("#table-frm-agent"), i;
	var code = 	$('#txtRefno2').val();
	if (code.substr(4,3)=='300'||code.substr(4,3)=='301'||code.substr(4,3)=='302'||code.substr(4,3)=='400'||code.substr(4,3)=='402'||code.substr(4,3)=='600') 
	{
		var gridnya = $("#tabel_sp_detail_export"), i;
	}
	else 
	{
		var gridnya = $("#tabel_sp_detail"), i;
	}
	
	var trk_doc = $('#TRK-DOC').attr('checked');
	
	if (trk_doc=='checked') { 
		trk_doc=true; 
		qty = '1.0000';
	} else {
		trk_doc=false;
		qty = gridnya.jqGrid('getCell', spd_id,'bl_meas')
	};
			
	var post = {};
	post.spd = spd_id;
	post.kode_ref = $('#kode_ref_income').val();
	post.agent_code = gridnya.jqGrid('getCell', spd_id,'agent_code');
	post.qty = qty;
	post.trk_doc = trk_doc;
	var __agentRefocusLock = false;
	grid.jqGrid(
	{ 
		url	:base_url+'/afg_c_income/get_from_agent',
		mtype : "post",
		datatype: "json",
		postData: post,
		sortname: "id",
      sortorder: "asc",
		colNames:
		[
			'',
			'',
			'Item Service',
			'Container',
			'Currency',
			'Quantity',
			'Price/Unit', 
			'Price', 
			'Kode Agent', 
			'INV / DB',
			'Inv No.',
			'Grup',
			'Created By',
			'Created At',
			'Updated By',
			'Updated At',
			''			
		],
    	colModel:
		[
			{name:'sp_detail_id',index:'sp_detail_id', align:"left", width:30, editable: true, hidden: true,},
			{name:'kode_ref',index:'kode_ref', align:"left", width:100, editable: true, hidden: true,},
			{name:'item_service',index:'item_service', align:"center", width:170, editable: true, 
				edittype:"select",
				formatter:'select', 
				editoptions:{value:" : ;REBATE AGENT VOLUME:REBATE AGENT VOLUME;REBATE AGENT LSS:REBATE AGENT LSS;REBATE AGENT SET:REBATE AGENT SET"}
			},
			{name:'container',index:'container', align:"center", width:60,
				editable: true, edittype:"select",
				editoptions:{
					dataUrl	: base_url+'/afg_c_dropdown/dropdownContainer',
					dataInit: function (el) { $(el).css({'max-width':'60px'}); },
					dataEvents: [{type:'change',fn:function(){ on_edit(true); }}]
				},hidden: true,
			},
			{name:'currency',index:'currency', align:"center", width:90, editable: true, 
				edittype:"select",formatter:'select', editoptions:{ value:" : ;USD:USD;RP.:RP.", dataInit: function(el){ $(el).css({'min-width':'90px','max-width':'90px'}); if(!$(el).val()||String($(el).val()).trim()==='') $(el).val('USD'); }, dataEvents:[{type:'change',fn:function(){ on_edit(true); if(!this.value||String(this.value).trim()==='') this.value='USD'; }}] }
			},
			{name:'qty',index:'qty', align:"center", width:100, editable: true, 
				editoptions: { dataInit: function (el) { clearZeroOnEdit(el); $(el).attr('title','Hanya boleh memasukan angka'); } },
				formatter: numOrBlank,
    			formatoptions: { decimalSeparator: ".", thousandsSeparator: ",", decimalPlaces: 4, defaultValue: '' }
			},
			{name:'unit_price',index:'unit_price', align:"right", width:100, editable: true, 
				editoptions: { width: 30, dataInit: function (el) { clearZeroOnEdit(el); $(el).attr('title','Hanya boleh memasukan angka'); } },
				formatter: numOrBlank,
    			formatoptions: { decimalSeparator: ".", thousandsSeparator: ",", decimalPlaces: 2, defaultValue: '' },
					dataEvents: [
					{
						type:'keyup',
						fn:function(){
							// preview recalculation while typing (no AJAX)
							on_edit(true);
							format_number(this, 2);
							var rowid = grid.jqGrid('getGridParam', 'selrow');
							var qty = _parseGridNumber(grid.jqGrid('getCell', rowid, 'qty'));
							var unit_price = _parseGridNumber(this.value);
							var price = qty * unit_price;
							grid.jqGrid('setCell', rowid, 'price', price);
						}
					},
					{
						type:'change',
						fn:function(){ on_edit(true); }
					}
				]
			},
			{name:'price',index:'price', align:"right", width:100, editable: false,
    			editoptions:{ disabled: true },
				formatter: numOrBlank,
    			formatoptions: { decimalSeparator: ".", thousandsSeparator: ",", decimalPlaces: 2, defaultValue: '' },
			},
			{name:'agent', index:'agent', align:"center", width:120, editable:true,
			  edittype:"text",
			  editoptions:{
				dataInit: function(el){
				  $(el).autocomplete({
					source: function(request, response){
					  $.ajax({
						url: base_url+'/afg_c_dropdown/dropdownAgentAjax',
						dataType: 'json',
						data: { q: request.term },
						success: function(data){
						  response($.map(data, function(row){
							return { label: row.agent_company_code, value: row.agent_company_code };
						  }));
						}
					  });
					},
					minLength: 2
				  });
				}
			  }
			},
			{
			  name:'invoice_db', index:'invoice_db', align:"center", width:100,
			  editable:true,
			  edittype:"select",
			  formatter:'select',
			  editoptions:{
				value:" : ;DEBIT NOTE:DEBIT NOTE;INVOICE:INVOICE"
			  },
			  formatoptions:{               // ✅ penting untuk jqGrid 4.4.3
				value:"DEBIT NOTE:DEBIT NOTE;INVOICE:INVOICE"
			  }
			},

			{name:'dn_no',index:'dn_no',align:"center", width:100, hidden: false,},
			{name:'g',index:'g', align:"center", width:50, editable: true, 
				editoptions:{
					dataInit: function (el) { $(el).css({'text-transform':'uppercase','min-width':'50px','max-width':'50px'}); },
					dataEvents: [{ type:'keydown', fn: function(e){ if ((e.charCode||e.keyCode) == 13){ $("#btnAddNewAgent").click(); grid.jqGrid("setGridParam",{datatype:'json'}).trigger("reloadGrid"); } } }]
				},
				formatter: function(el){ return el? String(el).toUpperCase() : "" }
			},
			{name:'created_by',index:'created_by', width:200, hidden: false,},
  			{name:'created_at',index:'update_at', width:200, hidden: false,},
  			{name:'updated_by',index:'updated_by', width:200, hidden: false,},
  			{name:'updated_at',index:'updated_at', width:200, hidden: false,},
  			{name:'payment',index:'payment', width:200, hidden: true,},
		], 
		multiselect:true,
		shrinkToFit:false,
		loadonce:false,
  		viewrecords: true,
		modal: true,
		rownumbers: true,
		rowNum: 100,
		width: 1273,
		height: 'auto',
		rowList:[-0],
		gridview: true,
		cellEdit:true,
		caption:"Get Intensiv From Agent",
		editurl	:	base_url+'/afg_c_income/manage_from_agent',
		cellEdit:true,
		cellsubmit:'clientArray',
		pager:'#pager-frm-agent',
		
		beforeSaveCell: function(rowid, cellname, value, iRow, iCol) {
			if (cellname === 'currency') {
				var v = String(value || '').trim();
				if (!v) {
					_toastWarn('Currency wajib diisi');
					return grid.jqGrid('getCell', rowid, 'currency') || 'USD';
				}
				return v;
			}
			if (cellname === 'qty' || cellname === 'unit_price') {
				var cleaned = String(value || '').replace(/,/g,'').trim();
				if (cleaned === '') return '0';
				if (isNaN(Number(cleaned))) {
					_toastWarn(cellname + ' harus angka');
					return grid.jqGrid('getCell', rowid, cellname);
				}
				return cleaned;
			}
			return value;
		},

		// guard biar setCell/editCell tidak memicu loop
		

		afterSaveCell: function(rowid, cellname, value, iRow, iCol) {
		  try {
			if (__agentRefocusLock) return;

			// hitung price kalau perlu
			if (cellname === 'qty' || cellname === 'unit_price') {
			  _recalcPrice(grid, rowid, 'inf_income_from_agent');
			}

			// ambil payload row (pakai getRowData, tapi invoice_db kita amankan)
			var row = grid.jqGrid('getRowData', rowid);

			// IMPORTANT: invoice_db jangan ambil dari rowData kalau formatter select bikin blank
			// pakai value kalau memang kolomnya invoice_db
			if (cellname === 'invoice_db') row.invoice_db = value;

			// kalau invoice_db masih kosong, ambil dari cell HTML (kadang rowData kosong)
			if (!row.invoice_db || String(row.invoice_db).trim() === '') {
			  row.invoice_db = _stripHtml(grid.jqGrid('getCell', rowid, 'invoice_db'));
			}

			// bersihkan html + angka
			['item_service','container','currency','agent','invoice_db','dn_no','g'].forEach(function(k){
			  if (row[k] != null) row[k] = _stripHtml(row[k]);
			});
			['qty','unit_price','price'].forEach(function(k){
			  if (row[k] != null) row[k] = String(row[k]).replace(/,/g,'').replace(/\s/g,'').trim() || '0';
			});

			// simpan (debounce)
			_debounce('agent:' + rowid, function(){
			  $.ajax({
				type: "POST",
				url : base_url + "/afg_c_income/saving_agent",
				dataType: 'json',
				data: { id: rowid, myData: row },
				global: false
			  }).done(function(res){
				handleIncomeMutationResponse(res, '#table-frm-agent');
			  }).fail(function(xhr){
				var res=null; try { res=JSON.parse(xhr.responseText); } catch(e) {}
				handleIncomeMutationResponse(res || {status:0,error:'Gagal validasi billing'}, '#table-frm-agent');
			  });
			}, 250);

			// restore fokus ke kolom kanan
			setTimeout(function(){
			  __agentRefocusLock = true;
			  focusNextEditableCell(grid, iRow, iCol + 1);
			  __agentRefocusLock = false;
			}, 0);

		  } catch(e) { console.error(e); }
		},
    			
		loadComplete: function() {
			$("#cb_"+grid[0].id).hide();
			$(".ui-jqgrid-titlebar-close",$("#table-frm-agent")[0].grid.cDiv).hide();
			var ids = grid.jqGrid('getDataIDs');
			if (ids.length>=1) { grid.jqGrid('editCell',ids.length,4,true); }
    	},	
    	beforeSelectRow: function(rowid) {
			var lock =  grid.jqGrid('getCell',rowid,'payment');
			var gridId = $.jgrid.jqID(this.id);
			if (lock == 'paid'){
				['item_service','currency','qty','unit_price','agent','invoice_db','dn_no','group','created_by','created_at','updated_at','updated_by'].forEach(c=>{
					$(this).jqGrid('setColProp', c, { editable:false });
				});
				$("#edit_" + gridId).addClass('ui-state-disabled');
				$("#del_" + gridId).addClass('ui-state-disabled');
			}else{
				['item_service','currency','qty','unit_price','agent','invoice_db','dn_no','group','created_by','created_at','updated_at','updated_by'].forEach(c=>{
					$(this).jqGrid('setColProp', c, { editable:true });
				});
				$("#edit_" + gridId).removeClass('ui-state-disabled');
				$("#del_" + gridId).removeClass('ui-state-disabled');
			}
        var cbsdis = $("tr#"+rowid+".jqgrow > td > input.cbox:disabled", grid[0]);
        return cbsdis.length === 0;
    	},		
		onSelectRow: function(rowid, status){
			var gridId = $.jgrid.jqID(this.id);
        if ($(this).jqGrid('getCell', rowid, 'payment') === 'paid') {
            $("#edit_" + gridId).addClass('ui-state-disabled');
            $("#del_" + gridId).addClass('ui-state-disabled');
        } else {
            $("#edit_" + gridId).removeClass('ui-state-disabled');
            $("#del_" + gridId).removeClass('ui-state-disabled');
        }
			var selected = $("tr.jqgrow > td > input.cbox:checked", grid[0]);		
			selected[rowid] = status;
		},	
	})
	.navGrid('#pager-frm-agent',{ edit: false, add: false, del: true, refresh: true, <?php echo 'search: false' ?>,},{ }, { },
     	{
     		height: "auto", 
     		width: "auto",
     		serializeDelData: function (postdata) {
     			return { id: postdata.id, oper: postdata.oper, kolom : '' };
     		},
     		afterSubmit: function(){
    			grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
    			return [true];
			}
     	},{ },{ }
	)
	grid.navButtonAdd('#pager-frm-agent',{ 
			caption:"", 
			buttonicon:"ui-icon-plus", 
			onClickButton: insert_data_insentiv_agent, 
			position: "last", 
			title:"Insert Data Table From Agent", 
			cursor: "pointer",
			id:"btnAddNewAgent",
	})
	grid.navButtonAdd('#pager-frm-agent',{ 
			caption:"",
			buttonicon:"ui-icon-disk", 
			onClickButton: tarik_data_insentiv_agent, 
			position: "last", 
			title:"Tarik Data Insentive From Agent", 
			cursor: "pointer"
	})
	
	var div = $(".ui-jqgrid-bdiv");
 	var nx = div.length-1;
 	var divx = div[nx];
 	$(divx).css({'min-height':'100px'})
}

function save_cell_from_agen(rowid, myData){
  $.ajax({
    type:"POST",
    url : base_url + '/afg_c_income/saving_agent',
    data:{ id: rowid, myData: myData },
    global:false
  });
}


function insert_data_insentiv_agent(){
	var spd_id = $('#spd_id_income').val();	
	var grid = $("#table-frm-agent"), i;
	var code = 	$('#txtRefno2').val();
	if (code.substr(4,3)=='300'||code.substr(4,3)=='301'||code.substr(4,3)=='302'||code.substr(4,3)=='400'||code.substr(4,3)=='402'||code.substr(4,3)=='600'){
		var gridnya = $("#tabel_sp_detail_export"), i;
	}else{
		var gridnya = $("#tabel_sp_detail"), i;
	}
	
	$.ajax({
		type:"POST",
		url	:base_url+'/afg_c_income/manage_from_agent',
		data:{
			agent : gridnya.jqGrid('getCell', spd_id,'agent_code'),
			sp_detail_id : $('#spd_id_income').val(),
			kode_ref : $('#kode_ref_income').val(),
			hb_l : gridnya.jqGrid('getCell', spd_id,'hb_l'),
			oper : 'add',
		},
		success:function(response){
			var data = $.parseJSON(response);
			if(data.status==1){
				var dataSatu = [{'id':data.id}];
				grid.jqGrid('addRowData',i,dataSatu,'last');
				grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
			}
		}
	})
}

function tarik_data_insentiv_agent(){
	var grid = $("#table-frm-selling"), i;	
	var spd_id = $('#spd_id_income').val();
	
	var code = $('#txtRefno2').val();
	if (code.substr(4,3)=='300'||code.substr(4,3)=='301'||code.substr(4,3)=='302'||code.substr(4,3)=='400'||code.substr(4,3)=='402'||code.substr(4,3)=='600'){
		var gridnya = $("#tabel_sp_detail_export"), i;
	}else{
		var gridnya = $("#tabel_sp_detail"), i;
	}
	
	var destination = gridnya.jqGrid('getCell', spd_id,'destination');		
	if(destination ==''){ destination = $('#dropdownCountries').val(); }
	
	var fcl = $('select[id="dropdownTypeOfTransaction"]').val();
	var qty = (fcl =='FCL' || fcl == 'SF IMPORT FCL') ? gridnya.jqGrid('getCell', spd_id,'no_pack_qty') : gridnya.jqGrid('getCell', spd_id,'bl_meas');

	$.ajax({
		type: "POST",
		url : base_url+'/afg_c_income/tarik_data_insentiv_agent',
		data: { 
			spd : spd_id,
			kode_ref : $('#kode_ref_income').val(),
			shipper_id : gridnya.jqGrid('getCell', spd_id,'shipper'),
			destination : destination,
			qty : qty,
			hb_l : gridnya.jqGrid('getCell', spd_id,'hb_l'),
			agent : $('select[id="dropdownAgent"]').val(),
			countainer : $("#txtTOC2").val(),
		},
		success: function(){
			$("#table-frm-agent").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
		},
		error:function(){ return false; }
	})
}

/* ===========================
   Coloader (tidak diubah, hanya alert)
=========================== */
function tarik_data_coloader(){
	var spd_id = $('#spd_id_income').val();
	var coloader = $("#txtCarrier").val();
	var destination = $("#tabel_sp_detail").jqGrid('getCell', spd_id,'destination');	
	
	$.ajax({
		type: "POST",
		url : base_url+'/afg_c_income/tarik_data_coloader',
		data: { 
			spd : spd_id,
			kode_ref : $('#kode_ref_income').val(),
			shipp_co : coloader,
			destination : destination,
			qty : $("#tabel_sp_detail").jqGrid('getCell', spd_id,'bl_meas'),
			countainer : $("#txtTOC2").val(),
		},
		success: function(){
			$("#table-frm-coloader").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
		},
		error:function(){ return false; }
	})
}

/* ===========================
   Save header (Swal v2)
=========================== */
function save_data(){
	var tdk_proses = $('#TDK-PROSES').is(':checked');
	var trk_doc    = $('#TRK-DOC').is(':checked');
	
	$.ajax ({
		type:"POST",
		url	:base_url+'/afg_c_income/manage_header_income',
		data:{
			kode_ref : $('#kode_ref_income').val(),
			tarik_insentiv_per_doc: trk_doc,
			intensiv_tidak_diproses: tdk_proses,
			kurs: $('#kurs').val(),
		},
		success:function(response){
			var data = {};
			try { data = $.parseJSON(response); } catch(e){}
			if(data.status==1){
				if (Toast) Toast.fire({icon:'success', title:'Data updated'}); 
				else Swal.fire('Data updated');
			}
		}
	})
}

/* ===========================
   Table From Coloader (tetap)
=========================== */
function table_from_coloader(){
	var spd_id = $('#spd_id_income').val();	
	var grid = $("#table-frm-coloader"), i;
	var code = 	$('#txtRefno2').val();
	if (code.substr(4,3)=='300'||code.substr(4,3)=='301'||code.substr(4,3)=='302'||code.substr(4,3)=='400'||code.substr(4,3)=='402'||code.substr(4,3)=='600')
	{
		var gridnya = $("#tabel_sp_detail_export"), i;
	}
	else 
	{
		var gridnya = $("#tabel_sp_detail"), i;
		
	}
	
	grid.jqGrid(
	{ 
		url	:base_url+'/afg_c_income/get_from_coloader',
		mtype : "post",
		datatype: "json", 
		postData: { 
			kode_ref : $('#kode_ref_income').val(),
			qty : gridnya.jqGrid('getCell', $('#spd_id_income').val(),'bl_meas'),
			trk_doc : $('#TRK-DOC').val(),
		},
		sortname: "id",
      sortorder: "asc",
		colNames:
		[
			'',
			'',
			'Container',
			'Code',
			'Item',
			'Currency',
			'Quantity',
			'Price/Unit', 
			'Price', 
			'INV / DB',
			'Grup',
			'PPN',
			'',
			'Created By',
			'Created At',
			'Updated By',
			'Updated At',
		],
    	colModel:
		[
			{name:'kode_ref',index:'kode_ref', align:"left", width:10, editable: true, hidden: true,},
			{name:'mchgs',index:'mchgs', align:"left", width:10, editable: true, hidden: true,},
			{name:'container',index:'container', align:"center", width:100, editable: true, hidden: true,
				edittype:"select",
				editoptions:{ dataUrl: base_url+'/afg_c_dropdown/dropdownContainer',
					dataInit: function (el) { $(el).css({'min-width':'100px','max-width':'100px'}); },
				}
			},
			{name:'code_service',index:'code_service', align:"left", width:50, editable: true, hidden: false,
				editrules:{edithidden:true}, 
				edittype:"select",
				editoptions:{
					dataUrl:base_url+'/afg_c_dropdown/dropdownService',
					dataInit: function (el) { $(el).css({'min-width':'45px','max-width':'45px'}); },
					dataEvents: [{
                  	type:'change',
                  	fn:function(){
                       	var val = this.value;
                       	$.ajax({
									type:"POST",
									url:base_url+'/afg_c_dropdown/item_service_nya',
									data:{ code_service: val },
									success:function(response){
										var data = $.parseJSON(response);
										var rowid = grid.jqGrid('getGridParam', 'selrow');
										grid.jqGrid('setCell',rowid,'item_service',data);	
									},
								})
                      }
                   }]
				}
			},
			{name:'item_service',index:'item_service', align:"center", width:170, editable: true, edittype:"select", formatter:'select', 
				editoptions:{value:"INSENTIVE FROM COLOADER:INSENTIVE FROM COLOADER"},
			},
			{name:'currency',index:'currency', align:"center", width:80, editable: true, edittype:"select", formatter:'select', 
				editoptions:{ value:" : ;USD:USD;RP.:RP.", dataInit: el => { $(el).css({'min-width':'80px','max-width':'80px'}); if(!$(el).val()||String($(el).val()).trim()==='') $(el).val('USD'); }, dataEvents:[{type:'change',fn:function(){ on_edit(true); if(!this.value||String(this.value).trim()==='') this.value='USD'; }}] }
			},
			{name:'qty',index:'qty', align:"center", width:80, editable: true, 
				editoptions: { dataInit: el => { clearZeroOnEdit(el); $(el).attr('title','Hanya boleh memasukan angka'); } },
				formatter: numOrBlank,
    			formatoptions: { decimalSeparator: ".", thousandsSeparator: ",", decimalPlaces: 4, defaultValue: '' }
			},
			{name:'unit_price',index:'unit_price', align:"right", width:80, editable: true, 
				editoptions: { 
					dataInit: el => { clearZeroOnEdit(el); $(el).attr({'title':'Hanya boleh memasukan angka'}); $(el).css({'min-width':'80px','max-width':'80px'}); },
					dataEvents: [
					{
						type:'keyup',
						fn:function(){
							// preview recalculation while typing (no AJAX)
							on_edit(true);
							format_number(this, 2);
							var rowid = grid.jqGrid('getGridParam', 'selrow');
							var qty = _parseGridNumber(grid.jqGrid('getCell', rowid, 'qty'));
							var unit_price = _parseGridNumber(this.value);
							var price = qty * unit_price;
							grid.jqGrid('setCell', rowid, 'price', price);
						}
					},
					{
						type:'change',
						fn:function(){ on_edit(true); }
					}
				]
				},
				formatter: numOrBlank,
    			formatoptions: { decimalSeparator: ".", thousandsSeparator: ",", decimalPlaces: 2, defaultValue: '' }
			},
			{name:'price',index:'price', align:"right", width:80, editable: false, 
				editoptions: { dataInit: el => { $(el).attr('title','Hanya boleh memasukan angka').attr('disabled',true); $(el).css({'min-width':'80px','max-width':'80px'}); } },
				formatter: numOrBlank,
    			formatoptions: { decimalSeparator: ".", thousandsSeparator: ",", decimalPlaces: 2, defaultValue: '' }
			},
			{name:'invoice_db',index:'invoice_db', align:"center", width:100, editable: true, edittype:"select", formatter:'select', 
				editoptions:{
					value:" : ;INVOICE:INVOICE;DEBIT NOTE:DEBIT NOTE",
					dataInit: el => $(el).attr({'min-width':'100px','max-width':'100px'}),
				}
			},
			{name:'g',index:'g', align:"center", width:50, editable: true, 
				editoptions:{
					dataInit: el => $(el).css({'text-transform':'uppercase','min-width':'50px','max-width':'50px'}),
					dataEvents: [{ type:'keydown', fn: function(e){ if ((e.charCode||e.keyCode)==13){ $("#btnAddNewColoader").click(); } } }]
				},
				formatter: function(el){ return el? String(el).toUpperCase() : "" }
			},
			{name:'ppn',index:'ppn', align:"center", width:50, editable: true, edittype:"select",
				editoptions:{
					value:" : ;1.1%:1.1%;12%:12%",
					dataInit: el => $(el).css({'min-width':'50px','max-width':'50px'}),
					dataEvents: [{
                  type:'change',
                  fn:function(){
                  	var rowid = grid.jqGrid('getGridParam', 'selrow')
					// PPN disimpan oleh afterSaveCell ke source Income.
					// Jangan update x_finance_item langsung; invoice dibangun ulang saat re-post.
                  }
               }],
				},
				formatter:'select',
			},
			{name:'sp_detail_id',index:'sp_detail_id', align:"left", width:10, editable: false, hidden: true,},
			{name:'created_by',index:'created_by', width:200, hidden: false,},
  			{name:'created_at',index:'update_at', width:200, hidden: false,},
  			{name:'updated_by',index:'updated_by', width:200, hidden: false,},
  			{name:'updated_at',index:'updated_at', width:200, hidden: false,},
		],
		multiselect:true,
		shrinkToFit:false,
		loadonce:false,
  		viewrecords: true,
		modal: true,
		rownumbers: true,
		rowNum: 100,
		width: 1273,
		height: 'auto',
		rowList:[-0],
		gridview: true,
		caption:"Get Rebate From Coloader",
		editurl	:	base_url+'/afg_c_income/manage_from_coloader',
		cellEdit:true,
		cellsubmit:'clientArray',
		pager:'#pager-frm-coloader',
		
		beforeSaveCell: function(rowid, cellname, value, iRow, iCol) {
			if (cellname === 'currency') {
				var v = String(value || '').trim();
				if (!v) {
					_toastWarn('Currency wajib diisi');
					return grid.jqGrid('getCell', rowid, 'currency') || 'USD';
				}
				return v;
			}
			if (cellname === 'qty' || cellname === 'unit_price') {
				var cleaned = String(value || '').replace(/,/g,'').trim();
				if (cleaned === '') return '0';
				if (isNaN(Number(cleaned))) {
					_toastWarn(cellname + ' harus angka');
					return grid.jqGrid('getCell', rowid, cellname);
				}
				return cleaned;
			}
			return value;
		},

		afterSaveCell: function(rowid, cellname, value) {
			try {
				if (cellname === 'qty' || cellname === 'unit_price') {
					_recalcPrice(grid, rowid, 'inf_income_from_coloader');
				}
				if (cellname === 'currency') {
					var cur = _ensureCurrency(value);
					if (cur !== value) grid.jqGrid('setCell', rowid, 'currency', cur);
				}
				_debounce('coloader:' + rowid, function(){
					save_cell_from_coloader(rowid, cellname, value);
				}, 250);
			} catch(e) { console.error(e); }
    	},
				
		loadComplete: function() {
			$("#cb_"+grid[0].id).hide();
			var ids = grid.jqGrid('getDataIDs');
			if (ids.length>=1) { grid.jqGrid('editCell',ids.length,4,true); }
    	},
    	
    	beforeSelectRow: function(rowid) {
        var cbsdis = $("tr#"+rowid+".jqgrow > td > input.cbox:disabled", grid[0]);
        return cbsdis.length === 0;
    	},		
		onSelectRow: function(rowid, status){
			var selected = $("tr.jqgrow > td > input.cbox:checked", grid[0]);		
			selected[rowid] = status;
		},
		
	})
	.navGrid('#pager-frm-coloader',{ edit: false, add: false, del: true, refresh: true, <?php echo 'search: false' ?>,},
		{ }, 
   	{ },
     	{
     		height: "auto", 
     		width: "auto",
     		serializeDelData: function (postdata) {
     			return { id: postdata.id, oper: postdata.oper, kolom : '' };
     		},
     		afterSubmit: function(){
    			grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
    			return [true];
			}
     	},
      { },
     	{ }	
	)
	.navButtonAdd('#pager-frm-coloader',{ 
			caption:"", 
			buttonicon:"ui-icon-plus", 
			onClickButton: insert_data_coloader, 
			position: "last", 
			title:"Insert Data Rebate From Coloader", 
			cursor: "pointer",
			id:"btnAddNewColoader",
	})
	.navButtonAdd('#pager-frm-coloader',{ 
			caption:"", 
			buttonicon:"ui-icon-disk", 
			onClickButton: tarik_data_coloader, 
			position: "last", 
			title:"Tarik Data Rebate From Coloader", 
			cursor: "pointer"
	})
	var div = $(".ui-jqgrid-bdiv");
 	var nx = div.length-1;
 	var divx = div[nx];
 	$(divx).css({'min-height':'100px'})
}

function save_cell_from_coloader(rowid,cellname,value){
	var grid = $("#table-frm-coloader"), i;
	var myData = grid.getRowData(rowid);
	$.ajax({
		type:'POST',
		url:base_url+'/afg_c_income/saving_coloader',
		dataType:'json',
		data:{ id:rowid, myData:myData },
		global:false
	}).done(function(res){
		handleIncomeMutationResponse(res, '#table-frm-coloader');
	}).fail(function(xhr){
		var res=null; try { res=JSON.parse(xhr.responseText); } catch(e) {}
		handleIncomeMutationResponse(res || {status:0,error:'Gagal validasi billing'}, '#table-frm-coloader');
	});
}

function insert_data_coloader(){
	var grid = $("#table-frm-coloader"), i;
	$.ajax({
		type:"POST",
		url	:base_url+'/afg_c_income/manage_from_coloader',
		data:{ kode_ref:$('#kode_ref_income').val(), sp_detail_id:$('#spd_id_income').val(), oper:'add' },
		success:function(response){
			var data = $.parseJSON(response);
			if(data.status==1){
				grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
			}
		}
	})
}

/* ===========================
   Edit detail sp (tetap)
=========================== */
function edit_detail_sp(){
		var rowid = arguments[0];
		var kode_ref = $('#tabel_sp_detail').jqGrid('getCell',rowid,'kode_ref');
		$( "#Form-Income" ).dialog('close');
		$( "#Form-Input-Detail" ).css('background-color','#eef').css('font-size','#11px');
		$( "#Form-Input-Detail" ).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
		$( "#Form-Input-Detail" ).dialog(
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
			url : base_url+'/afg_c_stuffing_plan/open_form_detail',
			data: { id : rowid, mode:"edit" },
			success:function(XMLHttpRequest)
			{
				$('#Form-Input-Detail').html(XMLHttpRequest);
			}
		})
}

/* ===========================
   Utils
=========================== */
function array_unique(inputArr) {
  var key = '', tmp_arr2 = {}, val = '';
  var __array_search = function(needle, haystack) {
    var fkey = '';
    for (fkey in haystack) {
      if (haystack.hasOwnProperty(fkey)) {
        if ((haystack[fkey] + '') === (needle + '')) return fkey;
      }
    }
    return false;
  };
  for (key in inputArr) {
    if (inputArr.hasOwnProperty(key)) {
      val = inputArr[key];
      if (false === __array_search(val, tmp_arr2)) tmp_arr2[key] = val;
    }
  }
  return tmp_arr2;
}

/* ===========================
   Posting + update Last Posted tanpa refresh form
=========================== */
function _incomeFormatDateTime(dateObj) {
	function pad(value) {
		return String(value).padStart(2, '0');
	}

	return dateObj.getFullYear() + '-' +
		pad(dateObj.getMonth() + 1) + '-' +
		pad(dateObj.getDate()) + ' ' +
		pad(dateObj.getHours()) + ':' +
		pad(dateObj.getMinutes()) + ':' +
		pad(dateObj.getSeconds());
}

function _incomeNormalizePostResponse(response) {
	var data = response;

	// Kompatibel dengan response lama yang masih berupa JSON string.
	if (typeof data === 'string') {
		try {
			data = JSON.parse(data);
		} catch (e) {
			return { raw_response: response };
		}
	}

	return data || {};
}

// HTTP 2xx yang masuk ke callback success dianggap berhasil,
// kecuali payload secara eksplisit menyatakan gagal.
function _incomePostResponseIsFailure(data) {
	data = data || {};

	var status = data.status;
	var success = data.success;

	if (
		status === false || status === 0 || status === '0' ||
		String(status || '').toLowerCase() === 'false' ||
		String(status || '').toLowerCase() === 'error' ||
		success === false || success === 0 || success === '0'
	) {
		return true;
	}

	return false;
}

// Pesan error API: prioritaskan validasi server, bukan wrapper generik.
function _incomePostErrorMessage(data, fallbackMessage) {
    function clean(value) {
        if (typeof value !== 'string') return '';
        value = value.trim();
        if (!value || /^\s*<(?:!doctype|html|head|body)\b/i.test(value)) return '';
        return value.split(/(?:\\r|\\n|\r|\n)*Traceback\s*\(most recent call last\)/i)[0]
            .replace(/^\w+(?:Error|Exception)\(u?['"]/, '')
            .replace(/['"]\s*,?\s*\)\s*$/, '')
            .replace(/(?:\\r|\\n|\r|\n)+$/, '').trim();
    }
    function collect(value, depth) {
        if (depth > 8 || value == null) return [];
        if (typeof value === 'string') {
            try { return collect(JSON.parse(value), depth + 1); } catch (e) {}
            if (/^\s*[\[{]/.test(value)) return [];
            var message = clean(value);
            return message ? [message] : [];
        }
        if (Array.isArray(value)) {
            return value.reduce(function (all, item) {
                return all.concat(collect(item, depth + 1));
            }, []);
        }
        if (typeof value === 'object') return collect(value.message, depth + 1);
        return [];
    }
    function unique(messages) {
        return messages.filter(function (message, i) {
            return messages.indexOf(message) === i;
        }).join('\n');
    }
    function extract(value, depth) {
        if (depth > 8 || value == null) return '';
        if (typeof value === 'string') {
            try { return extract(JSON.parse(value), depth + 1); } catch (e) {}
            return clean(value);
        }
        if (typeof value !== 'object') return '';
        var messages = collect(value._server_messages, 0);
        if (messages.length) return unique(messages);
        var nested = ['remote', 'remote_response', 'response', 'data'];
        for (var i = 0; i < nested.length; i++) {
            var detail = extract(value[nested[i]], depth + 1);
            if (detail) return detail;
        }
        messages = collect(value.message, 0);
        if (!messages.length) messages = collect(value.error, 0);
        return unique(messages);
    }
    return extract(data, 0) || fallbackMessage;
}

function _incomePostResponseMessage(data, fallbackText) {
	data = data || {};

	// Gunakan hanya message berbentuk string.
	if (typeof data.message === 'string' && $.trim(data.message) !== '') {
		return data.message;
	}

	if (
		data.data &&
		typeof data.data.message === 'string' &&
		$.trim(data.data.message) !== ''
	) {
		return data.data.message;
	}

	// Response ERP saat ini berbentuk { message: { ...dokumen ERP... } }.
	// Object tersebut adalah data hasil posting, bukan pesan error.
	return fallbackText;
}

function _incomeRefreshTables() {
	['#table-frm-selling', '#table-frm-agent', '#table-frm-coloader'].forEach(function(selector) {
		var grid = $(selector);
		if (grid.length && grid[0].grid) {
			grid.jqGrid('setGridParam', { datatype: 'json' }).trigger('reloadGrid');
		}
	});
}

function _incomeUpdateLastPosted(postedData) {
	postedData = postedData || {};

	var postedBy = postedData.posted_by || postedData.created_by || '';
	var postedAt = postedData.posted_at || postedData.created_at || '';
	var postedText = postedData.last_posted_text || '';

	/*
	 * Fallback untuk response API lama yang belum mengirim posted_by/posted_at.
	 * Response helper yang baru tetap menjadi sumber utama agar waktunya sama
	 * persis dengan waktu yang tersimpan di SQL.
	 */
	if (!postedBy) {
		postedBy = <?php echo json_encode((string)$this->session->userdata('fullname')); ?>;
	}
	if (!postedAt) {
		postedAt = _incomeFormatDateTime(new Date());
	}
	if (!postedText) {
		postedText = $.trim(postedBy + ' ' + postedAt);
	}

	$('#income_last_posted').val(postedText);
	$('#income_last_posted_bottom').text(postedText);
	$('#income_posted_at').val(postedAt);
}

function _incomePostJakarta(kode_ref, sp_detail_id, kurs) {
    Swal.fire({
        title: 'Memproses Posting...',
        text: 'Mohon tunggu sebentar',
        allowEscapeKey: false,
        allowOutsideClick: false,
        didOpen: function() {
            Swal.showLoading();
        }
    });

    $.ajax({
        type: 'POST',
        url: "<?php echo site_url('finance/c_post_data_acct_per_job/proses_post')?>",
        dataType: 'json',
        data: {
            region: 'Jakarta',
            kode_ref: kode_ref,
            sp_detail_id: sp_detail_id,
            kurs: kurs
        },
        beforeSend: function() {
            $('#button-post').prop('disabled', true).val('POSTING...');
        },
        success: function(response) {
            var data = _incomeNormalizePostResponse(response);

            if (_incomePostResponseIsFailure(data)) {
                Swal.fire({
                    title: 'Posting Gagal',
                    text: _incomePostErrorMessage(data, 'Response posting menyatakan proses gagal.'),
                    icon: 'error'
                });
                return;
            }

            var postedData = data.data || {};
            _incomeUpdateLastPosted(postedData);
            _incomeRefreshTables();

            Swal.fire({
                title: 'Berhasil',
                text: _incomePostResponseMessage(data, 'Data income sudah diposting ke ERP.'),
                icon: 'success'
            });
        },
        error: function(xhr) {
            var data = xhr.responseJSON || null;

            if (!data && xhr.responseText) {
                try { data = JSON.parse(xhr.responseText); } catch (e) { data = null; }
            }

            Swal.fire({
                title: 'Posting Gagal',
                text: _incomePostErrorMessage(data, 'Terjadi kesalahan pada server.'),
                icon: 'error'
            });
        },
        complete: function() {
            $('#button-post').prop('disabled', false).val(' POST ');
        }
    });
}

function _incomePostNonJakartaRevenueOrder(kode_ref, sp_detail_id, kurs) {
    $.ajax({
        type: 'POST',
        url: base_url + '/afg_c_income/post_revenue_order',
        dataType: 'json',
        data: {
            kode_ref: kode_ref,
            sp_detail_id: sp_detail_id,
            kurs: kurs,
            carrier: $('#txtCarrier').val() || '',
            sales_id: $('#sales').val() || '',
            shipper_code: $('#shipper_id').val() || ''
        },
        beforeSend: function() {
            $('#button-post').prop('disabled', true).val('POSTING...');
        },
        success: function(res) {
            if (!res || !res.status) {
                Swal.fire({title: 'Posting Gagal', text: _incomePostErrorMessage(res, 'Revenue Order gagal diposting'), icon: 'error'});
                return;
            }

            var ro = res.data || {};
            var no = ro.order_no ? (' - ' + ro.order_no) : '';
            _incomeUpdateLastPosted(ro);
            _incomeRefreshTables();
            Swal.fire('Berhasil', 'Revenue Order tersimpan' + no, 'success');
        },
        error: function(xhr) {
            var data = xhr.responseJSON || _incomeNormalizePostResponse(xhr.responseText);
            Swal.fire({
                title: 'Posting Gagal',
                text: _incomePostErrorMessage(data, 'Gagal terhubung ke ERP'),
                icon: 'error'
            });
        },
        complete: function() {
            $('#button-post').prop('disabled', false).val(' POST ');
        }
    });
}

function get_proses_post() {
    var kode_ref = $('#kode_ref_income').val();
    var region_id = $.trim(<?php echo json_encode((string)$this->session->userdata('region_id')); ?>);
    var sp_detail_id = $('#spd_id_income').val();
    var kurs = $('#kurs').val();

    if (!kode_ref) {
        Swal.fire({ title: 'Validasi gagal', text: 'Kode Ref tidak ditemukan.', icon: 'warning' });
        return;
    }

    if (!region_id) {
        Swal.fire({ title: 'Validasi gagal', text: 'Region session tidak ditemukan.', icon: 'warning' });
        return;
    }

    if (!kurs || parseFloat(String(kurs).replace(/[, ]/g, '')) <= 0) {
        Swal.fire({
            title: 'Validasi gagal',
            text: 'Nilai kurs tidak boleh kosong atau nol.',
            icon: 'warning'
        });
        return;
    }

    // Pastikan cell yang sedang diedit sudah tersimpan sebelum POST.
    if (window.incomeFlushAllGrids) {
        window.incomeFlushAllGrids();
    } else {
        jqGridSaveActiveCell('#table-frm-selling');
        jqGridSaveActiveCell('#table-frm-agent');
        jqGridSaveActiveCell('#table-frm-coloader');
    }

    // Jakarta TETAP memakai flow existing/legacy.
    if (String(region_id).toLowerCase() === 'jakarta') {
        _incomePostJakarta(kode_ref, sp_detail_id, kurs);
        return;
    }

    // Semua region selain Jakarta memakai Revenue Order ERP baru.
    _incomePostNonJakartaRevenueOrder(kode_ref, sp_detail_id, kurs);
}

/* ===========================
   Quotation CRM/Jasaweb Adapter
=========================== */
function _incomeIsoDate(v) {
    v = String(v || '').trim();
    if (!v) return '';
    if (/^\d{4}-\d{2}-\d{2}$/.test(v)) return v;
    var m = v.match(/^(\d{2})[\/\-](\d{2})[\/\-](\d{4})$/);
    return m ? (m[3] + '-' + m[2] + '-' + m[1]) : v;
}

function _incomeJobsheetGrid(spdId) {
    var selectors = ['#tabel_sp_detail_export', '#tabel_sp_detail'];
    for (var i = 0; i < selectors.length; i++) {
        var $g = $(selectors[i]);
        if (!$g.length) continue;
        try {
            if ($g.jqGrid('getInd', spdId, false)) return $g;
        } catch (e) {}
    }
    return $('#tabel_sp_detail');
}

function getIncomeQuotationContext() {
    var spdId = $('#spd_id_income').val();
    var $grid = _incomeJobsheetGrid(spdId);
    var destination = '';
    var shipperName = '';

    try {
        destination = $grid.jqGrid('getCell', spdId, 'destination') || '';
        shipperName = $grid.jqGrid('getCell', spdId, 'shipper_print') ||
                      $grid.jqGrid('getCell', spdId, 'shipper') || '';
    } catch (e) {}

    if (!destination) destination = $('#dropdownCountries').val() || '';

    return {
        shipper_id : $('#shipper_id').val() || '',
        shipper    : shipperName,
        destination: destination,
        sales_id   : $('#sales').val() || '',
        sales_name : ($('#sales option:selected').text() || '').trim(),
        valid_date : _incomeIsoDate($('#txtETDJKT').val() || '')
    };
}

function load_quotation_options() {
    var ctx = getIncomeQuotationContext();
    var $select = $('#select_quot');
    if (!$select.length) return;

    var current = $select.val();
    $select.empty().append($('<option>', { value:'', text:'-- Select Quotation --' }));

    if (!ctx.sales_id || (!ctx.shipper_id && !ctx.shipper) || !ctx.destination) {
        $select.append($('<option>', {
            value:'',
            text:'Sales / Shipper / Destination belum lengkap',
            disabled:true
        }));
        return;
    }

    $.ajax({
        url: base_url + '/afg_c_income/get_list_quotation',
        type: 'GET',
        dataType: 'json',
        data: ctx,
        global: false
    }).done(function(res) {
        var rows = (res && Array.isArray(res.data)) ? res.data : [];
        var source = String((res && res.source) || '').toUpperCase();

        rows.forEach(function(row) {
            var no = row.quote_no || row.name || '';
            if (!no) return;

            var label = no;
            var parts = [];
            if (source === 'CRM' || String(row.source || '').toUpperCase() === 'CRM') {
                if (row.destination) parts.push(row.destination);
            } else {
                if (row.customer_name || row.party_name) parts.push(row.customer_name || row.party_name);
            }
            if (row.status) parts.push(row.status);
            if (row.valid_till) parts.push('Valid ' + row.valid_till);
            if (parts.length) label += ' | ' + parts.join(' | ');

            $select.append($('<option>', { value:no, text:label }));
        });

        if (current && $select.find('option[value="' + String(current).replace(/"/g,'\\"') + '"]').length) {
            $select.val(current);
        }

        if (!rows.length) {
            $select.append($('<option>', {
                value:'',
                text:'Tidak ada quotation yang sesuai',
                disabled:true
            }));
        }
    }).fail(function(xhr) {
        console.error('Gagal load quotation', xhr.responseText || xhr.statusText);
        $select.append($('<option>', { value:'', text:'Gagal memuat quotation', disabled:true }));
    });
}

function _incomeHtmlEscape(v) {
    return String(v == null ? '' : v)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function _incomeMoney(v, decimals) {
    var n = Number(v || 0);
    if (!isFinite(n)) n = 0;
    return n.toLocaleString('en-US', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    });
}

function _incomeBillableQuoteItems(q) {
    return (Array.isArray(q.items) ? q.items : []).filter(function(item) {
        var basis = String(item.pricing_basis || 'FIXED').toUpperCase();
        var include = item.include_in_total == null ? 1 : Number(item.include_in_total);
        return include !== 0 && ['COLLECT','EXCLUDED','INFO'].indexOf(basis) === -1;
    });
}

function _incomeQuotationPreviewHtml(q) {
    var items = _incomeBillableQuoteItems(q);
    var totals = {};
    var rows = '';

    items.forEach(function(item) {
        var cur = String(item.currency || q.currency || 'IDR').toUpperCase();
        if (cur === 'RP' || cur === 'RP.' || cur === 'RUPIAH') cur = 'IDR';

        var qty = Number(item.qty || 0);
        var unit = Number(item.rate != null ? item.rate : item.unit_price || 0);
        var amount = Number(item.amount || 0);
        if (!amount && qty && unit) amount = qty * unit;

        totals[cur] = (totals[cur] || 0) + amount;

        var desc = item.item_name || item.description || item.charge_code || item.item_code || '-';
        var basis = String(item.pricing_basis || 'FIXED').replace(/_/g, ' ');

        rows += '<tr>' +
            '<td style="text-align:left;padding:5px;border-bottom:1px solid #e5e7eb;">' +
                '<b>' + _incomeHtmlEscape(desc) + '</b>' +
                (basis !== 'FIXED' ? '<div style="font-size:10px;color:#64748b;">' + _incomeHtmlEscape(basis) + '</div>' : '') +
            '</td>' +
            '<td style="text-align:right;padding:5px;border-bottom:1px solid #e5e7eb;">' + _incomeMoney(qty, 4) + '</td>' +
            '<td style="text-align:center;padding:5px;border-bottom:1px solid #e5e7eb;">' + _incomeHtmlEscape(cur) + '</td>' +
            '<td style="text-align:right;padding:5px;border-bottom:1px solid #e5e7eb;">' + _incomeMoney(unit, cur === 'IDR' ? 0 : 2) + '</td>' +
            '<td style="text-align:right;padding:5px;border-bottom:1px solid #e5e7eb;">' + _incomeMoney(amount, cur === 'IDR' ? 0 : 2) + '</td>' +
        '</tr>';
    });

    var totalHtml = Object.keys(totals).sort().map(function(cur) {
        return '<div style="font-weight:bold;margin-top:4px;">Total ' + _incomeHtmlEscape(cur) +
            ': ' + _incomeMoney(totals[cur], cur === 'IDR' ? 0 : 2) + '</div>';
    }).join('');

    return '<div style="text-align:left;font-size:12px;">' +
        '<div style="margin-bottom:8px;">' +
            '<b>' + _incomeHtmlEscape(q.name || q.quote_no || '') + '</b>' +
            (q.customer_name ? ' &nbsp;•&nbsp; ' + _incomeHtmlEscape(q.customer_name) : '') +
            (q.destination ? ' &nbsp;•&nbsp; ' + _incomeHtmlEscape(q.destination) : '') +
        '</div>' +
        '<div style="max-height:330px;overflow:auto;border:1px solid #e5e7eb;border-radius:4px;">' +
        '<table style="width:100%;border-collapse:collapse;font-size:11px;">' +
            '<thead><tr style="background:#f8fafc;">' +
                '<th style="text-align:left;padding:6px;">Charge</th>' +
                '<th style="text-align:right;padding:6px;">Qty</th>' +
                '<th style="text-align:center;padding:6px;">Curr</th>' +
                '<th style="text-align:right;padding:6px;">Unit Price</th>' +
                '<th style="text-align:right;padding:6px;">Amount</th>' +
            '</tr></thead><tbody>' + rows + '</tbody>' +
        '</table></div>' +
        '<div style="text-align:right;margin-top:8px;">' + totalHtml + '</div>' +
    '</div>';
}

function _incomeAcceptCrmQuotation(q, ctx, quot_no) {
    var postData = $.extend({}, ctx, {
        quot_no: quot_no,
        sp_id: $('#spd_id_income').val(),
        kode_ref: $('#kode_ref_income').val(),
        kurs: $('#kurs').val() || ''
    });

    Swal.fire({
        title: 'Gunakan quotation ini?',
        html: _incomeQuotationPreviewHtml(q),
        icon: 'question',
        width: 850,
        showCancelButton: true,
        confirmButtonText: 'Gunakan Quotation',
        cancelButtonText: 'Batal',
        focusCancel: true
    }).then(function(result) {
        if (!result.isConfirmed) {
            $('#select_quot').val('');
            return;
        }

        Swal.fire({
            title: 'Memproses quotation...',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: function(){ Swal.showLoading(); }
        });

        $.ajax({
            url: base_url + '/afg_c_income/insert_quotation_detail',
            type: 'POST',
            dataType: 'json',
            data: postData
        }).done(function(res) {
            if (!res || Number(res.status) !== 1) {
                Swal.fire({title:'Gagal', text:(res && res.message) || 'Gagal menggunakan quotation', icon:'error'});
                return;
            }

            $('#table-frm-selling').jqGrid('setGridParam', {datatype:'json'}).trigger('reloadGrid');
            refresh_quotation_link_state();

            Swal.fire({
                title: 'Quotation digunakan',
                text: res.message || 'Harga quotation sudah masuk ke Income.',
                icon: 'success'
            });
        }).fail(function(xhr) {
            var res = xhr.responseJSON || null;
            if (!res && xhr.responseText) {
                try { res = JSON.parse(xhr.responseText); } catch(e) {}
            }
            Swal.fire({
                title:'Gagal',
                text:(res && res.message) || 'Gagal menggunakan quotation',
                icon:'error'
            });
        });
    });
}

function _incomeImportJakartaQuotation(jsonData, quot_no) {
    $.ajax({
        url: base_url + '/afg_c_income/insert_quotation_detail',
        type: 'POST',
        dataType: 'json',
        data: {
            jsonData: JSON.stringify(jsonData),
            sp_id: $('#spd_id_income').val(),
            kode_ref: $('#kode_ref_income').val(),
            quot_no: quot_no
        }
    }).done(function(res) {
        if (!res || Number(res.status) !== 1) {
            Swal.fire({title:'Gagal', text:(res && res.message) || 'Gagal memuat quotation', icon:'error'});
            return;
        }
        $('#table-frm-selling').jqGrid('setGridParam', {datatype:'json'}).trigger('reloadGrid');
        if (Toast) Toast.fire({
            icon: res.already_loaded ? 'info' : 'success',
            title: res.message || 'Quotation dimuat'
        });
    }).fail(function(xhr) {
        var res = null;
        try { res = JSON.parse(xhr.responseText); } catch(e) {}
        Swal.fire({title:'Gagal', text:(res && res.message) || 'Gagal memuat data quotation', icon:'error'});
    });
}

function get_quot_detail() {
    var quot_no = $('#select_quot').val();
    if (!quot_no) return;

    var ctx = getIncomeQuotationContext();
    var params = $.extend({}, ctx, { quot_no: quot_no });

    $.getJSON(base_url + '/afg_c_income/get_list_quotation_detail/', params)
        .done(function(jsonData) {
            if (!jsonData || !jsonData.data) {
                Swal.fire({title:'Quotation tidak valid', icon:'warning'});
                return;
            }

            var q = jsonData.data;
            var source = String(q.source || jsonData.source || '').toUpperCase();
            var validTill = q.valid_till || '';
            var newdate = ctx.valid_date || '';

            if (validTill && newdate && validTill < newdate) {
                Swal.fire({title:'Quotation sudah expired', icon:'warning'});
                $('#select_quot').val('');
                return;
            }

            // Jakarta tetap memakai validasi detail legacy sebagai safety net.
            if (source !== 'CRM') {
                var salesText = ($('#sales option:selected').text() || '').trim();
                if (q.customer_name && ctx.shipper && q.customer_name != ctx.shipper) {
                    Swal.fire({title:'Quotation tidak sesuai', text:'Quotation bukan untuk ' + ctx.shipper, icon:'warning'});
                    $('#select_quot').val('');
                    return;
                }
                if (q.owner && salesText && q.owner != salesText) {
                    Swal.fire({title:'Sales mismatch', text:'Quotation bukan dari ' + salesText, icon:'warning'});
                    $('#select_quot').val('');
                    return;
                }
            }

            if (!Array.isArray(q.items) || q.items.length === 0) {
                Swal.fire({title:'Quotation kosong', text:'Quotation tidak memiliki item yang dapat dimuat.', icon:'warning'});
                $('#select_quot').val('');
                return;
            }

            if (source === 'CRM' && !_incomeIsJakarta()) {
                if (_incomeBillableQuoteItems(q).length === 0) {
                    Swal.fire({title:'Quotation kosong', text:'Tidak ada item billable pada quotation ini.', icon:'warning'});
                    $('#select_quot').val('');
                    return;
                }
                _incomeAcceptCrmQuotation(q, ctx, quot_no);
                return;
            }

            // Jakarta: behavior import tetap existing; perubahan Jakarta hanya pre-filter list.
            _incomeImportJakartaQuotation(jsonData, quot_no);
        })
        .fail(function(jqXHR) {
            var msg = 'Gagal mengambil detail quotation';
            try {
                var x = JSON.parse(jqXHR.responseText);
                if (x.error) msg = x.error;
            } catch(e) {}
            Swal.fire({title:'Error', text:msg, icon:'error'});
            $('#select_quot').val('');
        });
}

function _incomeRenderQuotationState(active, data) {
    var $box = $('#income-quotation-lifecycle');
    var $select = $('#select_quot');
    if (!$box.length) return;

    if (_incomeIsJakarta()) {
        $select.prop('disabled', false).removeData('quote-active');
        $('#sales').prop('disabled', false);
        $box.empty();
        return;
    }

    if (!active || !data) {
        $select.prop('disabled', false).removeData('quote-active');
        $('#sales').prop('disabled', false);
        $box.html('<span style="color:#64748b;font-size:10px;">Belum ada quotation aktif</span>');
        return;
    }

    $select.data('quote-active', 1).prop('disabled', true);
    $('#sales').prop('disabled', true);
    $box.html(
        '<span style="display:inline-block;background:#dcfce7;color:#166534;padding:2px 6px;border-radius:10px;font-size:10px;font-weight:bold;">' +
            'Aktif: ' + _incomeHtmlEscape(data.quote_no || '') +
        '</span> ' +
        '<button type="button" id="btn-cancel-income-quotation" ' +
            'style="font-size:10px;padding:2px 6px;margin-left:4px;cursor:pointer;" ' +
            'onclick="cancel_current_quotation()">Batalkan Quotation</button>'
    );
}

function refresh_quotation_link_state() {
    if (_incomeIsJakarta()) {
        _incomeRenderQuotationState(false, null);
        return $.Deferred().resolve().promise();
    }

    return $.ajax({
        url: base_url + '/afg_c_income/quotation_link_state',
        type: 'GET',
        dataType: 'json',
        global: false,
        data: {
            sp_id: $('#spd_id_income').val(),
            kode_ref: $('#kode_ref_income').val()
        }
    }).done(function(res) {
        if (res && res.active) {
            _incomeRenderQuotationState(true, res.data || {});
        } else {
            _incomeRenderQuotationState(false, null);
        }
    }).fail(function() {
        _incomeRenderQuotationState(false, null);
    });
}

function cancel_current_quotation() {
    Swal.fire({
        title: 'Batalkan quotation?',
        text: 'Income dari quotation akan dihapus dan status quotation/opportunity dikembalikan ke kondisi sebelumnya.',
        icon: 'warning',
        input: 'text',
        inputLabel: 'Alasan pembatalan (opsional)',
        inputPlaceholder: 'Contoh: customer revisi harga',
        showCancelButton: true,
        confirmButtonText: 'Ya, Batalkan',
        cancelButtonText: 'Tidak'
    }).then(function(result) {
        if (!result.isConfirmed) return;

        Swal.fire({
            title:'Membatalkan quotation...',
            allowOutsideClick:false,
            allowEscapeKey:false,
            didOpen:function(){ Swal.showLoading(); }
        });

        $.ajax({
            url: base_url + '/afg_c_income/cancel_quotation_import',
            type: 'POST',
            dataType: 'json',
            data: {
                sp_id: $('#spd_id_income').val(),
                kode_ref: $('#kode_ref_income').val(),
                reason: result.value || ''
            }
        }).done(function(res) {
            $('#table-frm-selling').jqGrid('setGridParam', {datatype:'json'}).trigger('reloadGrid');
            $('#select_quot').val('');
            refresh_quotation_link_state();
            load_quotation_options();

            Swal.fire({
                title:'Quotation dibatalkan',
                text:res.message || 'Status sudah dikembalikan.',
                icon:'success'
            });
        }).fail(function(xhr) {
            var res = xhr.responseJSON || null;
            if (!res && xhr.responseText) {
                try { res = JSON.parse(xhr.responseText); } catch(e) {}
            }

            var msg = (res && res.message) || 'Gagal membatalkan quotation.';
            if (res && res.error === 'PAID_INCOME_DELETE_NOT_ALLOWED') {
                msg = 'Quotation tidak dapat dibatalkan karena Income sudah dibayar.';
            }
            refresh_quotation_link_state();
            Swal.fire({title:'Tidak dapat dibatalkan', text:msg, icon:'error'});
        });
    });
}

// ====== JOBSHEET RECALC ON CLOSE (universal) ======
(function () {
  if (window.__jobsheetRecalcInstalled) return;
  window.__jobsheetRecalcInstalled = true;

  function safeSaveActiveCell(gridSel) {
    try {
      var $g = $(gridSel);
      if ($g.length === 0) return;

      var iRow = $g.jqGrid('getGridParam', 'iRow');
      var iCol = $g.jqGrid('getGridParam', 'iCol');
      if (iRow && iCol) $g.jqGrid('saveCell', iRow, iCol);
    } catch (e) {}
  }

  function debounce(fn, wait) {
    var t = null;
    return function () {
      var args = arguments, ctx = this;
      clearTimeout(t);
      t = setTimeout(function () { fn.apply(ctx, args); }, wait || 250);
    };
  }

  // panggil 1x saja walau close-trigger multiple
  var recalcOnceDebounced = debounce(function (payload) {
    $.ajax({
      type: "POST",
      url: base_url + "/afg_rekap/recalc_profit",
      data: payload
    });
  }, 400);

  // expose global function biar tiap form gampang panggil
  window.jobsheetRecalcOnClose = function (opts) {
    // opts: { kode_ref_sel:'#kode_ref_income', spd_sel:'#spd_id_income', grids:['#table-income', ...] }
    var kode_ref = $(opts.kode_ref_sel).val();
    var spd = $(opts.spd_sel).val();

    // save active edit cell di semua grid yang ada di form tsb
    (opts.grids || []).forEach(safeSaveActiveCell);

    if (!kode_ref) return;

    recalcOnceDebounced({
      kode_ref: kode_ref,
      spd: spd || ''
    });
  };
})();

</script>
