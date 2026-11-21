<?php
/**
 * Guard shipper coloader di form booking (dipakai v_booking_form & v_booking_edit).
 *   $cg_original = null (BC baru) | array('shipper_id'=>..., 'state'=>coloader_state())  (edit)
 * Aturan sebenarnya ada di server (Booking_model + libraries/Coloader_guard.php);
 * bagian ini hanya memberi tahu lebih awal.
 */
$cg_original = isset($cg_original) ? $cg_original : null;
?>
<style>
  .cg-box { clear: both; margin-top: 6px; padding: 8px 10px; border-radius: 4px; font-size: 12px; line-height: 1.45; }
  .cg-box b { display: block; margin-bottom: 2px; }
  .cg-block { background: #fdecea; border: 1px solid #f1b0ab; color: #a12622; }
  .cg-info  { background: #fff8e1; border: 1px solid #f3d27a; color: #6d5100; }
  .cg-api   { background: #e8f4fd; border: 1px solid #b6dcf7; color: #0b5394; }
  #coloaderBcWrap { clear: both; margin-top: 6px; }
  #coloaderBcWrap label { font-weight: bold; font-size: 12px; margin-bottom: 2px; }
  #coloader_bc { width: 100%; text-transform: uppercase; font-family: monospace; }
</style>
<script type="text/javascript">
var CG_ORIGINAL = <?php echo json_encode($cg_original); ?>;
var COLOADER_GUARD = { mode: '' };

function cgShow(s) {
    s = s || { mode: '' };
    COLOADER_GUARD = s;

    var $box  = $('#coloaderGuardBox');
    var $wrap = $('#coloaderBcWrap');
    $box.removeClass('cg-block cg-info cg-api').empty().hide();
    $wrap.hide();

    // Booking hasil coload cabang Gateway lain: BC asal dikelola cabang asal
    if (s.link_source === 'branch') {
        $box.addClass('cg-api')
            .append($('<b>').text('Booking coload dari ' + s.link_company))
            .append(document.createTextNode('BC asal ' + s.link_bc + '. Data cargo, jadwal, dan PEB/NPE diteruskan dari BC asal; ' +
                    'perubahan dan penghapusan dilakukan oleh ' + s.link_company + '.'))
            .show();
        return;
    }
    // Booking dikirim sistem coloader lewat API: BC asal dikelola di sana
    if (s.link_source === 'api') {
        $box.addClass('cg-api')
            .append($('<b>').text('Booking otomatis dari ' + (s.link_company || s.company)))
            .append(document.createTextNode('BC asal ' + s.link_bc + '. Perubahan dan penghapusan dilakukan dari sistem ' +
                    (s.link_company || s.company) + '.'))
            .show();
        return;
    }
    if (!s.mode) {
        return;
    }
    if (s.blocking) {
        $box.addClass('cg-block')
            .append($('<b>').text('BC tidak bisa dibuat dengan shipper ini'))
            .append(document.createTextNode(s.message || ''))
            .show();
        return;
    }
    if (s.mode === 'manual') {
        $box.addClass('cg-info').text(s.message || '').show();
        $('#coloader_bc').attr('placeholder', s.prefix ? s.prefix + '...' : 'Nomor BC asal');
        if (s.link_bc && !$.trim($('#coloader_bc').val())) {
            $('#coloader_bc').val(s.link_bc);
        }
        $('#coloaderBcReq').toggle(!!s.bcRequired);
        $wrap.show();
        return;
    }
    // auto / branch pada booking lama: hanya catatan, tetap boleh disimpan
    $box.addClass('cg-info')
        .append($('<b>').text('Catatan'))
        .append(document.createTextNode((s.message || '') + ' Booking lama ini tetap bisa disimpan selama shipper tidak diganti.'))
        .show();
}

function cgStateFromItem(d) {
    var mode = (d && d.coloader_mode) || '';
    return {
        mode:       mode,
        company:    (d && d.coloader_company) || '',
        prefix:     (d && d.coloader_prefix) || '',
        message:    (d && d.coloader_message) || '',
        blocking:   mode === 'auto' || mode === 'branch',
        bcRequired: mode === 'manual'
    };
}

/** Dipanggil dari select2:select shipper */
function cgOnShipperSelect(d) {
    var id = parseInt(d && d.real_id, 10) || 0;
    if (CG_ORIGINAL) {
        if (CG_ORIGINAL.state && (CG_ORIGINAL.state.link_source === 'api' || CG_ORIGINAL.state.link_source === 'branch')) {
            return cgShow(CG_ORIGINAL.state);       // dikelola sistem coloader
        }
        if (id === parseInt(CG_ORIGINAL.shipper_id, 10)) {
            return cgShow(CG_ORIGINAL.state);       // kembali ke shipper semula
        }
    }
    var s = cgStateFromItem(d);
    if (s.mode !== 'manual') {
        $('#coloader_bc').val('');
    }
    cgShow(s);
}

function cgOnShipperClear() {
    $('#coloader_bc').val('');
    cgShow(CG_ORIGINAL && CG_ORIGINAL.state && (CG_ORIGINAL.state.link_source === 'api' || CG_ORIGINAL.state.link_source === 'branch') ? CG_ORIGINAL.state : { mode: '' });
}

/** Dipanggil di awal save. false = jangan kirim. */
function cgCheckBeforeSave() {
    var s = COLOADER_GUARD || {};
    if (s.blocking) {
        Swal.fire({ icon: 'error', title: 'BC tidak bisa dibuat dengan shipper ini', text: s.message || '' });
        return false;
    }
    if (s.mode === 'manual' && s.bcRequired && $('#coloaderBcWrap').is(':visible') && !$.trim($('#coloader_bc').val())) {
        Swal.fire({ icon: 'error', title: 'BC asal wajib diisi', text: s.message || '' });
        $('#coloader_bc').focus();
        return false;
    }
    return true;
}

/** Tambahkan coloader_bc ke payload hanya bila kolomnya tampil. */
function cgPayload(obj) {
    if ($('#coloaderBcWrap').is(':visible')) {
        obj.coloader_bc = $.trim($('#coloader_bc').val()).toUpperCase().replace(/\s+/g, '');
    }
    return obj;
}

/** Response server ditolak karena coloader: tampilkan kotak / kolom BC asal. */
function cgFromResponse(data) {
    if (!data || !data.coloader) {
        return;
    }
    var mode = data.coloader.mode || '';
    cgShow({
        mode:       mode,
        company:    data.coloader.company_name || '',
        prefix:     data.coloader.bc_prefix || '',
        message:    data.message || '',
        blocking:   mode === 'auto' || mode === 'branch',
        bcRequired: mode === 'manual'
    });
    if (mode === 'manual') {
        $('#coloader_bc').focus();
    }
}

$(function () {
    if (CG_ORIGINAL && CG_ORIGINAL.state) {
        cgShow(CG_ORIGINAL.state);
    }
    $('#shipper').on('select2:clear select2:unselect', cgOnShipperClear);
});
</script>
