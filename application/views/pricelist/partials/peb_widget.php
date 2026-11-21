<?php
/**
 * Widget "Dokumen PEB / NPE" - REUSABLE (pasang di view mana pun).
 * ---------------------------------------------------------------------------
 *   <?php $this->load->view('pricelist/partials/peb_widget', array(
 *       'booking_code' => $booking_code,           // wajib
 *       'widget_id'    => 'pebw',                  // opsional, bila >1 widget dalam 1 halaman
 *       'layout'       => 'block',                 // 'block' (lebar penuh) | 'flex' (ikut baris flex)
 *       'readonly'     => false,                   // true = hanya lihat
 *       'on_change'    => 'myCallback',            // opsional: nama fungsi JS(rows) setelah data berubah
 *       'sync_url'     => '',                      // endpoint sinkron ke gudang (default: api/ticket_warehouse/sync_peb); '' = nonaktif
 *       'hint_save'    => false,                   // pengingat "klik SAVE untuk kirim ulang WMS" (default hanya bila sync_url kosong)
 *       'title'        => '...', 'note' => '...',  // opsional
 *   )); ?>
 *
 * Tampilan: form inline (AJU, PEB, NPE, jalur) untuk PEB yang dipilih + tombol Upload PDF di sampingnya.
 * Satu booking boleh punya banyak PEB: daftar PEB tampil sebagai "chip" di bawah form.
 *
 * Kebutuhan halaman: jQuery dan SweetAlert2 (Swal) - sudah ada di aplikasi ini.
 * Endpoint: controller pricelist/peb_document (lihat docblock-nya).
 * Global JS:
 *   PebWidget.get('<widget_id>')  -> {reload(), rows(), flush()}
 *   PebWidget.flushAll([{sync:false}]) -> Promise; menyimpan isian form yang belum tersimpan (panggil sebelum SAVE halaman).
 *                                    Reject(pesan) bila isian tidak valid. {sync:false} = jangan kirim ke gudang
 *                                    (dipakai bila halaman sendiri langsung mengirim ticket setelahnya).
 * Setiap PEB/NPE di-upload, diubah, atau dihapus, widget otomatis memanggil sync_url (api/ticket_warehouse/sync_peb)
 * agar gudang langsung menerima data terbaru; hasilnya tampil sebagai baris status di bawah kotak.
 * Event jQuery: $(document).on('peb:changed', function(e, widgetId, rows){...})
 */
$pw_id      = isset($widget_id) ? preg_replace('/[^A-Za-z0-9_\-]/', '', $widget_id) : 'pebw';
$pw_code    = isset($booking_code) ? (string) $booking_code : '';
$pw_layout  = (isset($layout) && $layout === 'flex') ? 'flex' : 'block';
$pw_ro      = !empty($readonly);
$pw_title   = isset($title) ? $title : '&#128737; PENGAWASAN KEPATUHAN AEO';
$pw_note    = isset($note) ? $note : 'Isi sesuai dokumen resmi, atau upload PDF PEB / NPE agar terisi otomatis. Data ini diperlukan untuk laporan ke BC hanggar';
$pw_onchg   = isset($on_change) ? preg_replace('/[^A-Za-z0-9_\.]/', '', $on_change) : '';
$pw_ep      = isset($endpoint) ? $endpoint : site_url('pricelist/peb_document');
$pw_sync    = isset($sync_url) ? (string) $sync_url : site_url('api/ticket_warehouse/sync_peb');
$pw_hint    = isset($hint_save) ? (bool) $hint_save : ($pw_sync === '');
$pw_assets  = !defined('PEB_WIDGET_ASSETS');
if ($pw_assets) { define('PEB_WIDGET_ASSETS', true); }
?>
<?php if ($pw_assets): ?>
<style>
.pebw { margin: 0; border: 2px solid #1f4e79; border-radius: 8px; background: #f4f8fc; padding: 4px 10px 8px 10px; box-shadow: 0 2px 5px rgba(0,0,0,.1); box-sizing: border-box; min-width: 0; }
.pebw.pebw-flex { flex: 1 1 520px; }
@media (max-width: 1250px) { .pebw.pebw-flex { flex-basis: 100%; } }
.pebw legend { font-weight: bold; color: #fff; background: #1f4e79; padding: 2px 10px; border-radius: 4px; font-size: 12px; letter-spacing: .5px; width: auto; border-bottom: 0; margin-bottom: 4px; }
.pebw .pw-note { font-size: 10.5px; color: #b94a48; margin: 0 0 4px 0; line-height: 1.3; }
.pebw .pw-state { color: #b36b00; font-weight: bold; margin-left: 8px; }
.pebw .pw-main { display: flex; flex-wrap: wrap; gap: 6px 14px; align-items: flex-start; }
.pebw .pw-form { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: 4px 8px; flex: 1 1 420px; max-width: 680px; min-width: 0; }
.pebw .pw-cell { min-width: 0; }
.pebw .pw-span2 { grid-column: span 2; }
.pebw .pw-cell label { display: block; font-size: 11px; font-weight: bold; margin: 0 0 1px 0; color: #1f3a57; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.pebw .pw-cell input, .pebw .pw-cell select { width: 100%; box-sizing: border-box; height: 26px; padding: 2px 5px; font-size: 12px; border: 1px solid #b7c7d9; border-radius: 4px; background: #fff; min-width: 0; margin: 0; }
.pebw .pw-cell input[type=date] { padding: 2px 2px; font-size: 11px; }
.pebw .pw-cell input:disabled, .pebw .pw-cell select:disabled, .pebw .pw-cell input[readonly] { background: #eef2f6; }
.pebw .pw-cell.pw-bad input, .pebw .pw-cell.pw-bad select { border-color: #c0392b; background: #fdecea; }
.pebw .pw-tag { font-weight: normal; color: #6c7a89; font-size: 10px; }
.pebw .pw-side { flex: 0 0 172px; display: flex; flex-direction: column; gap: 4px; padding-top: 14px; }
.pebw .pw-btn { width: 100%; box-sizing: border-box; border: 1px solid #1f4e79; background: #fff; color: #1f4e79; border-radius: 4px; padding: 3px 8px; height: 26px; font-size: 12px; cursor: pointer; line-height: 1.2; white-space: nowrap; }
.pebw .pw-btn:hover { background: #e6eef7; }
.pebw .pw-btn.pw-primary { background: #1f4e79; color: #fff; }
.pebw .pw-btn.pw-primary:hover { background: #17395a; }
.pebw .pw-btn.pw-ok { background: #2e8b57; border-color: #2e8b57; color: #fff; }
.pebw .pw-btn.pw-ok:hover { background: #247045; }
.pebw .pw-icons { display: flex; gap: 3px; min-height: 24px; }
.pebw .pw-ic { border: 1px solid #b7c7d9; background: #fff; border-radius: 4px; width: 28px; height: 24px; padding: 0; cursor: pointer; font-size: 12px; text-decoration: none; color: #1f4e79; display: inline-flex; align-items: center; justify-content: center; }
.pebw .pw-ic:hover { background: #e6eef7; }
.pebw .pw-ic.pw-del { color: #b94a48; }
.pebw .pw-ic.pw-wide { width: auto; padding: 0 5px; font-size: 11px; }
.pebw .pw-extra { font-size: 11px; color: #5b6b7c; margin-top: 4px; line-height: 1.4; }
.pebw .pw-jhint { font-size: 11px; margin-top: 5px; padding: 4px 8px; border-radius: 4px; background: #eef6ee; border: 1px solid #b8dcc0; color: #1d5a33; line-height: 1.5; }
.pebw .pw-jhint button { margin-left: 6px; border: 1px solid #2e8b57; background: #2e8b57; color: #fff; border-radius: 4px; padding: 1px 9px; font-size: 11px; cursor: pointer; }
.pebw .pw-jhint button:hover { background: #247045; }
.pebw .pw-chips { display: flex; flex-wrap: wrap; gap: 5px; align-items: center; margin-top: 6px; padding-top: 5px; border-top: 1px dashed #c9d6e4; }
.pebw .pw-chips-l { font-size: 11px; color: #44586d; }
.pebw .pw-chip { border: 1px solid #b7c7d9; background: #fff; color: #1f3a57; border-radius: 12px; padding: 1px 9px; font-size: 11px; cursor: pointer; line-height: 1.6; }
.pebw .pw-chip:hover { background: #e6eef7; }
.pebw .pw-chip.on { background: #1f4e79; border-color: #1f4e79; color: #fff; }
.pebw .pw-dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; margin-left: 4px; vertical-align: middle; }
.pebw .pw-dot.HIJAU { background: #2e8b57; }
.pebw .pw-dot.MERAH { background: #c0392b; }
.pebw .pw-sum { font-size: 11px; color: #44586d; margin-left: auto; }
.pebw .pw-muted { color: #8a97a5; }
.pebw .pw-wms { font-size: 11px; margin-top: 6px; padding: 3px 8px; border-radius: 4px; line-height: 1.4; }
.pebw .pw-wms.busy { background: #eef2f6; color: #44586d; }
.pebw .pw-wms.ok { background: #eaf6ee; color: #1d5a33; border: 1px solid #a9d8b8; }
.pebw .pw-wms.skip { background: #f1f4f8; color: #5b6b7c; }
.pebw .pw-wms.err { background: #fdecea; color: #8a1f14; border: 1px solid #e6a19a; }
.pebw .pw-wms a { margin-left: 8px; font-weight: bold; cursor: pointer; }
.pebw .pw-drop { outline: 3px dashed #1f4e79; outline-offset: -4px; }
.pebw .pw-lowtag { color: #b36b00; font-weight: bold; }
@media (max-width: 800px) { .pebw .pw-form { grid-template-columns: repeat(2, minmax(0,1fr)); } .pebw .pw-side { flex-basis: 100%; padding-top: 0; } }

/* Isi modal (Swal) */
.pwm { text-align: left; font-size: 12px; max-height: 68vh; overflow: auto; padding-right: 4px; }
.pwm .pwm-grid { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: 6px 10px; margin-bottom: 8px; }
.pwm .pwm-grid.c3 { grid-template-columns: repeat(3, minmax(0,1fr)); }
.pwm .pwm-grid.c2 { grid-template-columns: repeat(2, minmax(0,1fr)); }
.pwm .s2 { grid-column: span 2; }
.pwm label { display: block; font-size: 11px; font-weight: bold; color: #1f3a57; margin: 0 0 1px 0; }
.pwm input, .pwm select, .pwm textarea { width: 100%; box-sizing: border-box; height: 28px; padding: 3px 6px; font-size: 12px; border: 1px solid #b7c7d9; border-radius: 4px; background: #fff; min-width: 0; margin: 0; }
.pwm textarea { height: 60px; }
.pwm input[readonly] { background: #f1f4f8; }
.pwm .pwm-h { font-weight: bold; color: #1f4e79; border-bottom: 1px solid #c9d6e4; margin: 8px 0 5px; padding-bottom: 2px; }
.pwm .pwm-warn { background: #fff4e5; border: 1px solid #f0c27b; color: #7a4b00; border-radius: 5px; padding: 6px 9px; margin-bottom: 6px; }
.pwm .pwm-warn ul { margin: 3px 0 0 16px; padding: 0; }
.pwm .pwm-bad  { background: #fdecea; border-color: #e6a19a; color: #8a1f14; }
.pwm .pwm-ok   { background: #eaf6ee; border: 1px solid #a9d8b8; color: #1d5a33; border-radius: 5px; padding: 5px 9px; margin-bottom: 6px; }
.pwm table { width: 100%; border-collapse: collapse; font-size: 11px; }
.pwm th, .pwm td { border: 1px solid #d5dfea; padding: 2px 5px; text-align: left; vertical-align: top; }
.pwm th { background: #eef3f9; }
.pwm td.r { text-align: right; white-space: nowrap; }
.pwm .pwm-chk { display: flex; gap: 6px; align-items: flex-start; margin: 4px 0; font-weight: normal; }
.pwm .pwm-chk input { width: auto; height: auto; margin-top: 2px; }
@media (max-width: 800px) { .pwm .pwm-grid, .pwm .pwm-grid.c3 { grid-template-columns: repeat(2, minmax(0,1fr)); } }
</style>
<?php endif; ?>

<fieldset class="customs-doc pebw <?php echo $pw_layout === 'flex' ? 'pebw-flex' : ''; ?>" id="<?php echo $pw_id; ?>"
          data-booking="<?php echo html_escape($pw_code); ?>" data-endpoint="<?php echo html_escape($pw_ep); ?>"
          data-readonly="<?php echo $pw_ro ? 1 : 0; ?>" data-onchange="<?php echo html_escape($pw_onchg); ?>"
          data-hintsave="<?php echo $pw_hint ? 1 : 0; ?>" data-sync="<?php echo html_escape($pw_sync); ?>">
  <legend><?php echo $pw_title; ?></legend>
  <div class="pw-note"><?php echo html_escape($pw_note); ?><span class="pw-state"></span></div>
  <div class="pw-main">
    <div class="pw-form">
      <div class="pw-cell pw-span2">
        <label for="<?php echo $pw_id; ?>-aju">Nomor AJU</label>
        <input id="<?php echo $pw_id; ?>-aju" data-k="aju_number" data-label="Nomor AJU" type="text" maxlength="30" autocomplete="off" placeholder="26 digit nomor aju CEISA">
      </div>
      <div class="pw-cell">
        <label for="<?php echo $pw_id; ?>-jalur">Jalur Pemeriksaan <span class="pw-tag">(ke gudang)</span></label>
        <select id="<?php echo $pw_id; ?>-jalur" data-k="jalur" data-label="Jalur Pemeriksaan">
          <option value="">-- Belum ditetapkan --</option>
          <option value="HIJAU">HIJAU</option>
          <option value="MERAH">MERAH (Periksa Fisik)</option>
        </select>
      </div>
      <div class="pw-cell">
        <label for="<?php echo $pw_id; ?>-jalurd">Tgl Penetapan Jalur</label>
        <input id="<?php echo $pw_id; ?>-jalurd" data-k="jalur_date" data-label="Tanggal Penetapan Jalur" type="date">
      </div>
      <div class="pw-cell">
        <label for="<?php echo $pw_id; ?>-peb">Nomor PEB</label>
        <input id="<?php echo $pw_id; ?>-peb" data-k="peb_number" data-label="Nomor PEB" type="text" maxlength="30" autocomplete="off" placeholder="cth: 123456">
      </div>
      <div class="pw-cell">
        <label for="<?php echo $pw_id; ?>-pebd">Tanggal PEB</label>
        <input id="<?php echo $pw_id; ?>-pebd" data-k="peb_date" data-label="Tanggal PEB" type="date">
      </div>
      <div class="pw-cell">
        <label for="<?php echo $pw_id; ?>-npe">Nomor NPE</label>
        <input id="<?php echo $pw_id; ?>-npe" data-k="npe_number" data-label="Nomor NPE" type="text" maxlength="40" autocomplete="off" placeholder="cth: 654321">
      </div>
      <div class="pw-cell">
        <label for="<?php echo $pw_id; ?>-nped">Tanggal NPE</label>
        <input id="<?php echo $pw_id; ?>-nped" data-k="npe_date" data-label="Tanggal NPE" type="date">
      </div>
    </div>
    <div class="pw-side">
      <?php if (!$pw_ro): ?>
      <button type="button" class="pw-btn pw-primary pw-upload" title="Upload PDF PEB, NPE, atau PPB dari CEISA (boleh beberapa file; PEB + NPE/PPB boleh dalam satu file)">&#11014; Upload PEB / NPE / PPB</button>
      <input type="file" class="pw-file" accept="application/pdf,.pdf" multiple style="display:none">
      <button type="button" class="pw-btn pw-new" title="Tambah PEB lain untuk booking ini">&#65291; PEB lain</button>
      <button type="button" class="pw-btn pw-ok pw-save" style="display:none" title="Simpan isian form ke database">&#128190; Simpan PEB</button>
      <?php endif; ?>
      <div class="pw-icons" style="display:none">
        <button type="button" class="pw-ic pw-info" title="Detail lengkap PEB ini (barang, HS Code, nilai, dll)">&#8505;</button>
        <a class="pw-ic pw-pdf-peb" target="_blank" rel="noopener" title="Buka PDF PEB" style="display:none">P</a>
        <a class="pw-ic pw-pdf-npe" target="_blank" rel="noopener" title="Buka PDF NPE" style="display:none">N</a>
        <a class="pw-ic pw-pdf-ppb pw-wide" target="_blank" rel="noopener" title="Buka PDF PPB (pemeriksaan fisik)" style="display:none">PPB</a>
        <?php if (!$pw_ro): ?><button type="button" class="pw-ic pw-del" title="Hapus PEB ini">&#10005;</button><?php endif; ?>
      </div>
    </div>
  </div>
  <div class="pw-jhint" style="display:none"></div>
  <div class="pw-extra" style="display:none"></div>
  <div class="pw-chips" style="display:none"></div>
  <div class="pw-wms" style="display:none"></div>
</fieldset>

<?php if ($pw_assets): ?>
<script>
(function (window, $) {
    'use strict';
    /* Halaman yang memuat view ini lewat AJAX (fragment) akan menjalankan <script> ini berulang kali.
       Skrip hanya dipasang sekali; eksekusi berikutnya cukup memindai widget baru di DOM. */
    if (window.PebWidget) { window.PebWidget.scan(); return; }

    /* ---------------- util ---------------- */
    function esc(s) {
        return String(s === null || s === undefined ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }
    function fdate(d) {
        if (!d || d === '0000-00-00') { return ''; }
        var m = /^(\d{4})-(\d{2})-(\d{2})/.exec(d);
        return m ? m[3] + '-' + m[2] + '-' + m[1] : d;
    }
    function fnum(v, dec) {
        if (v === null || v === undefined || v === '') { return ''; }
        var n = parseFloat(v);
        if (isNaN(n)) { return esc(v); }
        return n.toLocaleString('en-US', { minimumFractionDigits: dec, maximumFractionDigits: dec });
    }
    function val(id) { return $.trim($('#' + id).val() || ''); }
    function parseJson(xhr) {
        try { return typeof xhr === 'string' ? JSON.parse(xhr) : xhr; } catch (e) { return null; }
    }
    function fail(title, text) { return Swal.fire({ icon: 'error', title: title, text: text }); }

    var registry = {};
    var CORE = ['aju_number', 'peb_number', 'peb_date', 'npe_number', 'npe_date', 'jalur', 'jalur_date'];
    var UPPER = { aju_number: 1, peb_number: 1, npe_number: 1 };

    function Inst(el) {
        this.$el = $(el);
        this.id = el.id;
        this.booking = this.$el.attr('data-booking') || '';
        this.ep = (this.$el.attr('data-endpoint') || '').replace(/\/$/, '');
        this.ro = this.$el.attr('data-readonly') === '1';
        this.onchange = this.$el.attr('data-onchange') || '';
        this.hintsave = this.$el.attr('data-hintsave') === '1';
        this.sync = this.$el.attr('data-sync') || '';
        this.syncing = false;     // sedang mengirim ke gudang
        this.syncAgain = false;   // ada perubahan baru saat mengirim -> kirim sekali lagi setelahnya
        this.data = [];
        this.queue = [];
        this.busy = false;
        this.sel = null;          // id PEB yang sedang tampil di form (null = PEB baru)
        this.newMode = false;     // user menekan "PEB lain"
        this.base = {};           // nilai form saat terakhir diisi/disimpan (untuk deteksi perubahan)
        this.bind();
        this.fill(null);
        if (!this.booking) { this.$el.find('.pw-state').text('(simpan booking dulu untuk mengisi PEB)'); }
        this.reload();
    }

    /* POST apa adanya: Promise NATIVE yang SELALU resolve {http, json, text} (juga untuk status HTTP error); reject hanya bila jaringan gagal. */
    function postRaw(url, data, timeout) {
        return new Promise(function (resolve, reject) {
            $.ajax({ type: 'POST', url: url, dataType: 'text', timeout: timeout || 90000, data: data })
                .done(function (txt, st, xhr) { resolve({ http: xhr.status, json: parseJson(txt), text: txt }); })
                .fail(function (xhr, st) {
                    if (xhr && xhr.status) { resolve({ http: xhr.status, json: parseJson(xhr.responseText), text: xhr.responseText }); }
                    else { reject(st === 'timeout' ? 'Server terlalu lama merespons.' : 'Gagal menghubungi server.'); }
                });
        });
    }

    /* Mengembalikan Promise NATIVE (resolve = objek JSON, reject = pesan teks) */
    Inst.prototype.post = function (action, data, files) {
        var opts = { type: 'POST', url: this.ep + '/' + action, dataType: 'text', timeout: 90000, data: data };
        if (files) { opts.processData = false; opts.contentType = false; }
        return new Promise(function (resolve, reject) {
            $.ajax(opts).done(function (txt) {
                var j = parseJson(txt);
                if (!j) { reject('Respon server tidak valid (sesi login mungkin habis).'); } else { resolve(j); }
            }).fail(function (xhr, st) {
                var j = parseJson(xhr.responseText);
                reject(j && j.message ? j.message : (st === 'timeout' ? 'Server terlalu lama merespons.' : 'Gagal menghubungi server.'));
            });
        });
    };

    /* ---------------- form inline ---------------- */
    Inst.prototype.$f = function (k) { return this.$el.find('[data-k="' + k + '"]'); };

    function normVal(k, v) {
        v = $.trim(v === null || v === undefined ? '' : String(v));
        if (v === '0000-00-00') { v = ''; }
        return UPPER[k] ? v.toUpperCase() : v;
    }

    Inst.prototype.readForm = function () {
        var self = this, h = {};
        $.each(CORE, function (i, k) { h[k] = normVal(k, self.$f(k).val()); });
        return h;
    };

    /* Isi form dari baris PEB (atau kosong bila null) dan catat sebagai keadaan "tersimpan". */
    Inst.prototype.fill = function (row) {
        var self = this, base = {};
        $.each(CORE, function (i, k) {
            var v = normVal(k, row ? row[k] : '');
            self.$f(k).val(v);
            base[k] = v;
        });
        this.base = base;
        this.$el.find('.pw-cell').removeClass('pw-bad');
        this.refreshState();
        var ro = this.ro;
        this.$el.find('[data-k]').each(function () { if (ro) { $(this).prop('disabled', true); } });
        // PEB yang punya PPB (pemeriksaan fisik) selalu jalur MERAH: dikunci
        var lock = !!(row && row.ppb_number);
        this.$f('jalur').prop('disabled', ro || lock).attr('title', lock ? 'Jalur MERAH ditetapkan oleh PPB ' + row.ppb_number : '');
    };

    Inst.prototype.dirty = function () {
        var h = this.readForm();
        for (var i = 0; i < CORE.length; i++) { if (h[CORE[i]] !== this.base[CORE[i]]) { return true; } }
        return false;
    };

    Inst.prototype.refreshState = function () {
        if (this.ro) { return; }
        var d = this.dirty() && !!this.booking;
        this.$el.find('.pw-state').text(d ? '● belum disimpan' : (this.booking ? '' : '(simpan booking dulu untuk mengisi PEB)'));
        this.$el.find('.pw-save').toggle(d);
        this.jhint();
    };

    /* Saran HIJAU: ada NPE, tidak ada PPB (pemeriksaan fisik), jalur belum diisi. TIDAK tersimpan otomatis: hanya mengisi form. */
    Inst.prototype.jhint = function () {
        var $h = this.$el.find('.pw-jhint');
        var row = this.sel !== null ? this.rowById(this.sel) : null;
        var show = !this.ro && !!row && !!row.npe_number && !row.ppb_number && !this.$f('jalur').val() && !!this.readForm().npe_number;
        if (!show) { $h.hide().empty(); return; }
        $h.html('&#8505; NPE sudah terbit dan tidak ada PPB (pemeriksaan fisik) pada PEB ini &rarr; biasanya jalur <b>HIJAU</b>.'
            + '<button type="button" class="pw-jset" title="Mengisi Jalur = HIJAU di form (belum tersimpan sampai Anda menyimpan)">Tetapkan HIJAU</button>'
            + '<br><span class="pw-muted">Bila PEB ini diperiksa fisik, upload PDF PPB-nya &mdash; jalur otomatis MERAH.</span>').show();
    };

    Inst.prototype.rowById = function (id) {
        var r = null;
        $.each(this.data, function (i, x) { if (String(x.id) === String(id)) { r = x; } });
        return r;
    };

    /* ---------------- daftar ---------------- */
    Inst.prototype.reload = function () {
        var self = this;
        if (!this.booking) { this.render([]); return; }
        this.post('rows', { booking_code: this.booking }).then(function (r) {
            if (r.status) { self.render(r.rows || []); }
            else { self.$el.find('.pw-extra').html('<span style="color:#b94a48">' + esc(r.message) + '</span>').show(); }
        }, function (m) {
            self.$el.find('.pw-extra').html('<span style="color:#b94a48">' + esc(m) + '</span>').show();
        });
    };

    Inst.prototype.rows = function () { return this.data; };

    /* Tampilkan daftar PEB; pilih $selectId bila diberikan, jika tidak pertahankan pilihan sekarang, jika tidak PEB pertama. */
    Inst.prototype.render = function (rows, selectId) {
        this.data = rows || [];
        var want = (selectId !== undefined && selectId !== null) ? selectId : this.sel;
        var row = (want !== null && !(selectId === undefined && this.newMode)) ? this.rowById(want) : null;
        if (selectId !== undefined && selectId !== null) { this.newMode = false; }
        if (!row && !this.newMode && this.data.length) { row = this.data[0]; }
        this.sel = row ? row.id : null;
        if (row) { this.newMode = false; }
        this.fill(row);
        this.drawExtras();
    };

    Inst.prototype.select = function (id) {
        var row = this.rowById(id);
        if (!row) { return; }
        this.newMode = false;
        this.sel = row.id;
        this.fill(row);
        this.drawExtras();
    };

    /* Chip daftar PEB, ikon aksi, dan ringkasan data dari dokumen untuk PEB yang dipilih. */
    Inst.prototype.drawExtras = function () {
        var self = this, row = this.sel !== null ? this.rowById(this.sel) : null;

        // ikon
        var $ic = this.$el.find('.pw-icons');
        if (row) {
            $ic.show();
            this.$el.find('.pw-pdf-peb').toggle(!!row.has_peb_file).attr('href', this.ep + '/view_file/' + row.id + '/peb');
            this.$el.find('.pw-pdf-npe').toggle(!!row.has_npe_file).attr('href', this.ep + '/view_file/' + row.id + '/npe');
            this.$el.find('.pw-pdf-ppb').toggle(!!row.has_ppb_file).attr('href', this.ep + '/view_file/' + row.id + '/ppb');
        } else { $ic.hide(); }

        // ringkasan dari dokumen
        var $x = this.$el.find('.pw-extra');
        var sub = [];
        if (row) {
            if (row.kategori_ekspor) { sub.push(esc(row.kategori_ekspor.replace(/\s*\(.*$/, ''))); }
            if (row.incoterm) { sub.push('Term ' + esc(row.incoterm)); }
            if (row.valuta_kode && row.nilai_ekspor !== null) { sub.push(esc(row.valuta_kode) + ' ' + fnum(row.nilai_ekspor, 2)); }
            if (row.kemasan_qty) { sub.push(esc(row.kemasan_qty) + ' ' + esc(row.kemasan_jenis || '')); }
            if (row.berat_kotor) { sub.push(fnum(row.berat_kotor, 2) + ' kg'); }
            if (row.hs_codes) { sub.push('HS ' + esc(row.hs_codes)); }
        }
        var ppbTxt = '';
        if (row && row.ppb_number) {
            var pp = ['<b>PPB ' + esc(row.ppb_number) + '</b>' + (row.ppb_date ? ' (' + fdate(row.ppb_date) + ')' : '')];
            if (row.ppb_tgl_periksa) { pp.push('periksa fisik ' + fdate(row.ppb_tgl_periksa) + (row.ppb_kantor_periksa ? ' di ' + esc(row.ppb_kantor_periksa) : '')); }
            if (row.ppb_tgl_stuffing) { pp.push('stuffing ' + fdate(row.ppb_tgl_stuffing) + (row.ppb_tempat_stuffing ? ' / ' + esc(row.ppb_tempat_stuffing) : '')); }
            ppbTxt = '<div><span style="color:#c0392b;font-weight:bold">Jalur MERAH</span> &mdash; ' + pp.join(' &middot; ') + '</div>';
        }
        if (row && (sub.length || ppbTxt || row.peb_confidence === 'low')) {
            $x.html((sub.length ? '<b>Dari dokumen:</b> ' + sub.join(' &middot; ') : '') + ppbTxt
                + (row.peb_confidence === 'low' ? ' <span class="pw-lowtag" title="Dibaca dari scan/OCR atau input manual">&#9888; pastikan sudah dicek dengan dokumen asli</span>' : '')).show();
        } else { $x.hide().empty(); }

        this.jhint();

        // chip
        var $c = this.$el.find('.pw-chips');
        if (!this.data.length) { $c.hide().empty(); return; }
        var html = '<span class="pw-chips-l">PEB booking ini:</span>';
        $.each(this.data, function (i, r) {
            var label = r.peb_number ? 'PEB ' + esc(r.peb_number) : (r.aju_number ? 'AJU …' + esc(String(r.aju_number).slice(-6)) : 'PEB');
            html += '<button type="button" class="pw-chip' + (String(r.id) === String(self.sel) ? ' on' : '') + '" data-id="' + r.id + '" title="AJU ' + esc(r.aju_number || '-') + '">'
                + (i + 1) + '. ' + label + (r.npe_number ? ' ✓NPE' : '')
                + (r.jalur ? '<i class="pw-dot ' + esc(r.jalur) + '" title="Jalur ' + esc(r.jalur) + '"></i>' : '')
                + (r.peb_confidence === 'low' ? ' ⚠' : '') + '</button>';
        });
        if (this.newMode && !this.ro) { html += '<button type="button" class="pw-chip on" disabled>+ Baru</button>'; }
        var withNpe = $.grep(this.data, function (r) { return !!r.npe_number; }).length;
        html += '<span class="pw-sum">' + this.data.length + ' PEB · NPE ' + withNpe + '/' + this.data.length + '</span>';
        $c.html(html).show();
    };

    /* Dipanggil setelah data BERUBAH (bukan saat sekadar memuat daftar) */
    Inst.prototype.changed = function (msg, nosync) {
        if (this.onchange && typeof window[this.onchange] === 'function') { try { window[this.onchange](this.data); } catch (e) {} }
        $(document).trigger('peb:changed', [this.id, this.data]);
        if (window.Swal && Swal.mixin) {
            Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3500, timerProgressBar: true })
                .fire({ icon: 'success', title: (msg || 'Tersimpan.') + (this.hintsave ? ' Klik SAVE untuk mengirim ulang ke WMS.' : '') });
        }
        if (!nosync) { this.queueSync(); }
    };

    /* ---------------- kirim update ke gudang (WMS) ---------------- */
    Inst.prototype.setWms = function (cls, html) {
        this.$el.find('.pw-wms').attr('class', 'pw-wms ' + cls).html(html).show();
    };

    function wmsReason(res) {
        var j = res.json || {}, code = j.message || '', det = '';
        if (j.response && typeof j.response === 'object') { det = j.response.message || j.response.error || ''; }
        else if (typeof j.response === 'string') { det = j.response; }
        var map = {
            WMS_CONNECTION_ERROR: 'tidak bisa terhubung ke server gudang' + (j.error ? ' (' + j.error + ')' : ''),
            WMS_AUTH_FAILED: 'token akses ke gudang ditolak',
            WMS_TICKET_FAILED: 'gudang menolak data' + (j.http_code ? ' (HTTP ' + j.http_code + ')' : ''),
            WMS_OUTBOX_SAVE_FAILED: 'gagal menyimpan antrean kirim di server',
            WMS_OUTBOX_PAYLOAD_INVALID: 'data antrean kirim rusak'
        };
        var txt = map[code] || code || ('HTTP ' + res.http);
        return txt + (det ? ' - ' + String(det).slice(0, 160) : '');
    }

    /* Tampilkan di console (F12) JSON PERSIS yang dikirim ke gudang + jawabannya, supaya tim WMS bisa mencocokkan datanya. */
    function logWms(j, label) {
        try {
            if (!window.console || !j || !j.wms_request) { return; }
            var r = j.wms_request;
            console.log('%c[WMS] ' + (label ? label + ' | ' : '') + r.method + ' ' + r.url + ' -> HTTP ' + (j.http_code || '-') + ' ' + (j.message || ''),
                'font-weight:bold;color:#0a6e3d');
            console.log('[WMS] JSON yang dikirim ke gudang:\n' + JSON.stringify(r.payload, null, 2));
            console.log('[WMS] payload (objek, bisa dibuka/ditelusuri):', r.payload);
            if (typeof j.response !== 'undefined') { console.log('[WMS] jawaban gudang:', j.response); }
        } catch (e) { /* log tidak boleh mengganggu proses */ }
    }

    /* Satu pengiriman pada satu waktu; perubahan yang datang saat mengirim digabung menjadi satu pengiriman lanjutan. */
    Inst.prototype.queueSync = function () {
        var self = this;
        if (!this.sync || !this.booking || this.ro) { return; }
        if (this.syncing) { this.syncAgain = true; return; }
        this.syncing = true;
        this.syncAgain = false;
        this.setWms('busy', '&#9203; Mengirim update PEB/NPE/PPB ke gudang (WMS)&hellip;');
        postRaw(this.sync, { booking_code: this.booking }, 110000).then(function (res) {
            var j = res.json;
            logWms(j, 'update PEB/NPE/PPB ' + (self.booking || ''));
            if (j && j.status && j.skipped) {
                self.setWms('skip', '&#8505; Booking ini belum dikirim ke gudang. PEB/NPE/PPB otomatis ikut terkirim saat booking dikirim.');
            } else if (j && j.status) {
                var d = new Date(), t = ('0' + d.getHours()).slice(-2) + ':' + ('0' + d.getMinutes()).slice(-2);
                self.setWms('ok', '&#10003; Gudang (WMS) sudah menerima update PEB/NPE/PPB &middot; ' + t);
            } else {
                self.setWms('err', '&#9888; Data tersimpan, tetapi gudang belum menerima update: ' + esc(wmsReason(res)) + '<a class="pw-resync">Kirim ulang</a>');
            }
        }, function (m) {
            self.setWms('err', '&#9888; Data tersimpan, tetapi gagal mengirim ke gudang: ' + esc(m) + '<a class="pw-resync">Kirim ulang</a>');
        }).then(function () {
            self.syncing = false;
            if (self.syncAgain) { self.queueSync(); }
        });
    };

    /* ---------------- simpan isian form ---------------- */
    function coreValidate(h) {
        if (!h.aju_number && !h.peb_number) { return ['aju_number', 'Isi minimal Nomor AJU atau Nomor PEB.']; }
        if (h.aju_number && !/^[0-9A-Za-z\-\/]+$/.test(h.aju_number)) { return ['aju_number', 'Nomor AJU hanya boleh huruf/angka tanpa spasi.']; }
        if (!!h.peb_number !== !!h.peb_date) { return [h.peb_number ? 'peb_date' : 'peb_number', 'Nomor PEB dan Tanggal PEB harus diisi berpasangan.']; }
        if (!!h.npe_number !== !!h.npe_date) { return [h.npe_number ? 'npe_date' : 'npe_number', 'Nomor NPE dan Tanggal NPE harus diisi berpasangan.']; }
        if (h.npe_number && !h.peb_number) { return ['npe_number', 'NPE tidak bisa diisi sebelum PEB.']; }
        if (h.peb_date && h.npe_date && h.npe_date < h.peb_date) { return ['npe_date', 'Tanggal NPE tidak boleh lebih awal dari Tanggal PEB.']; }
        if (h.jalur && !h.peb_number) { return ['jalur', 'Jalur pemeriksaan hanya bisa diisi setelah PEB terdaftar.']; }
        if (h.jalur_date && !h.jalur) { return ['jalur', 'Pilih jalur pemeriksaan untuk tanggal penetapan yang diisi.']; }
        if (h.jalur_date && h.peb_date && h.jalur_date < h.peb_date) { return ['jalur_date', 'Tanggal penetapan jalur tidak boleh lebih awal dari Tanggal PEB.']; }
        return null;
    }

    Inst.prototype.mark = function (k) {
        this.$el.find('.pw-cell').removeClass('pw-bad');
        if (k) { var $f = this.$f(k); $f.closest('.pw-cell').addClass('pw-bad'); try { $f.focus(); } catch (e) {} }
    };

    /**
     * Simpan isian form bila ada perubahan. Promise: resolve(true|false = ada yang disimpan), reject(pesan).
     * Dipanggil otomatis sebelum SAVE halaman (PebWidget.flushAll), sebelum pindah PEB, dan sebelum upload.
     */
    Inst.prototype.flush = function (nosync) {
        var self = this;
        return new Promise(function (resolve, reject) {
            if (self.ro || !self.booking || !self.$el.closest('body').length) { resolve(false); return; }
            var h = self.readForm(), any = false;
            $.each(h, function (k, v) { if (v) { any = true; } });
            if (!self.dirty() || (self.sel === null && !any)) { resolve(false); return; }

            if (self.sel !== null) {
                var blanked = [];
                $.each(CORE, function (i, k) { if (self.base[k] && !h[k]) { blanked.push(self.$f(k).attr('data-label')); self.mark(k); } });
                if (blanked.length) {
                    reject('Data yang sudah tersimpan tidak boleh dikosongkan: ' + blanked.join(', ') + '. Kembalikan nilainya, atau hapus PEB dengan tombol ✕.');
                    return;
                }
            }
            var bad = coreValidate(h);
            if (bad) { self.mark(bad[0]); reject(bad[1]); return; }
            self.mark(null);

            var req = self.sel !== null
                ? self.post('update', { id: self.sel, h: h })
                : self.post('add_manual', { booking_code: self.booking, h: h });
            req.then(function (res) {
                if (!res.status && res.code === 'AJU_DUPLICATE') {
                    return Swal.fire({ icon: 'warning', title: 'AJU sudah dipakai booking lain', text: res.message, showCancelButton: true,
                        confirmButtonText: 'Tetap simpan', cancelButtonText: 'Batal' }).then(function (y) {
                        if (!y.isConfirmed) { return { status: false, message: 'Penyimpanan PEB dibatalkan.' }; }
                        return self.post('add_manual', { booking_code: self.booking, h: h, confirm_dup: 1 });
                    });
                }
                return res;
            }).then(function (res) {
                if (!res.status) { reject(res.message || 'Gagal menyimpan PEB.'); return; }
                self.render(res.rows || [], res.id !== undefined ? res.id : self.sel);
                self.changed(res.message, nosync);
                resolve(true);
            }, function (m) { reject(m); });
        });
    };

    /* Jalankan fn setelah isian form (bila ada) tersimpan; bila gagal tampilkan alasannya. */
    Inst.prototype.guard = function (fn) {
        this.flush().then(fn, function (m) { fail('PEB belum bisa disimpan', m); });
    };

    /* ---------------- event ---------------- */
    Inst.prototype.bind = function () {
        var self = this, $el = this.$el;
        $el.on('click', '.pw-upload', function () { $el.find('.pw-file').val('').trigger('click'); });
        $el.on('change', '.pw-file', function () {
            var files = this.files ? Array.prototype.slice.call(this.files) : [];
            $.each(files, function (i, f) { self.queue.push(f); });
            self.next();
        });
        $el.on('click', '.pw-save', function () {
            self.flush().then(function (saved) { if (!saved) { self.refreshState(); } }, function (m) { fail('PEB belum bisa disimpan', m); });
        });
        $el.on('click', '.pw-new', function () {
            self.guard(function () {
                self.newMode = true;
                self.sel = null;
                self.fill(null);
                self.drawExtras();
                self.$f('aju_number').focus();
            });
        });
        $el.on('click', '.pw-chip', function () {
            var id = $(this).attr('data-id');
            if (!id || String(id) === String(self.sel)) { return; }
            self.guard(function () { self.select(id); });
        });
        $el.on('click', '.pw-resync', function (e) { e.preventDefault(); self.queueSync(); });
        $el.on('click', '.pw-jset', function () {
            var row = self.sel !== null ? self.rowById(self.sel) : null;
            self.$f('jalur').val('HIJAU');
            if (!self.$f('jalur_date').val()) { self.$f('jalur_date').val((row && row.npe_date) ? String(row.npe_date).slice(0, 10) : ''); }
            self.refreshState();
        });
        $el.on('click', '.pw-info', function () { if (self.sel !== null) { self.detail(self.sel); } });
        $el.on('click', '.pw-del', function () { if (self.sel !== null) { self.remove(self.sel); } });
        // ketik -> tandai belum tersimpan; Enter tidak boleh men-submit form halaman
        $el.on('input change', '[data-k]', function () {
            var k = $(this).attr('data-k');
            if (UPPER[k] && this.tagName === 'INPUT') {
                var pos = this.selectionStart, up = this.value.toUpperCase();
                if (up !== this.value) { this.value = up; try { this.setSelectionRange(pos, pos); } catch (e) {} }
            }
            $(this).closest('.pw-cell').removeClass('pw-bad');
            self.refreshState();
        });
        $el.on('keydown', 'input[data-k]', function (e) { if (e.which === 13) { e.preventDefault(); $(this).blur(); } });
        if (!this.ro) {
            $el.on('dragover', function (e) { e.preventDefault(); $el.addClass('pw-drop'); });
            $el.on('dragleave drop', function () { $el.removeClass('pw-drop'); });
            $el.on('drop', function (e) {
                e.preventDefault();
                var dt = e.originalEvent && e.originalEvent.dataTransfer;
                if (dt && dt.files) {
                    $.each(Array.prototype.slice.call(dt.files), function (i, f) { self.queue.push(f); });
                    self.next();
                }
            });
        }
    };

    /* ---------------- upload -> pratinjau ---------------- */
    Inst.prototype.next = function () {
        var self = this;
        if (this.busy || !this.queue.length) { return; }
        var f = this.queue.shift();
        this.busy = true;
        var done = function () { self.busy = false; self.next(); };

        if (!this.booking) { fail('Booking belum tersimpan', 'Simpan booking dulu sebelum mengunggah PEB.').then(done); return; }
        if (!/\.pdf$/i.test(f.name)) { fail('File ditolak', f.name + ' bukan PDF.').then(done); return; }

        // isian form yang belum tersimpan disimpan dulu, supaya tidak tertimpa hasil upload
        this.flush().then(function () {
            Swal.fire({ title: 'Membaca dokumen…', html: esc(f.name), allowOutsideClick: false, allowEscapeKey: false, didOpen: function () { Swal.showLoading(); } });
            var fd = new FormData();
            fd.append('file', f);
            fd.append('booking_code', self.booking);
            self.post('parse', fd, true).then(function (r) {
                if (!r.status) { fail('Gagal membaca dokumen', r.message).then(done); return; }
                (r.doc_type === 'NPE' ? self.previewNpe(r) : (r.doc_type === 'PPB' ? self.previewPpb(r) : self.previewPeb(r))).then(done, done);
            }, function (m) { fail('Gagal membaca dokumen', m).then(done); });
        }, function (m) { self.queue = []; fail('Isian PEB di form belum valid', m).then(done); });
    };

    function warnBox(list, bad) {
        if (!list || !list.length) { return ''; }
        var li = '';
        $.each(list, function (i, w) { li += '<li>' + esc(w) + '</li>'; });
        return '<div class="pwm-warn' + (bad ? ' pwm-bad' : '') + '"><b>Perhatian:</b><ul>' + li + '</ul></div>';
    }
    function inp(id, label, v, o) {
        o = o || {};
        return '<div class="' + (o.span ? 's2' : '') + '"><label for="' + id + '">' + esc(label) + '</label>'
            + '<input id="' + id + '" type="' + (o.type || 'text') + '" value="' + esc(v) + '"' + (o.max ? ' maxlength="' + o.max + '"' : '')
            + (o.ro ? ' readonly' : '') + (o.ph ? ' placeholder="' + esc(o.ph) + '"' : '') + (o.up ? ' style="text-transform:uppercase"' : '') + '></div>';
    }
    function jalurSel(id, v, lockTitle) {
        return '<div><label for="' + id + '">Jalur Pemeriksaan <span style="font-weight:normal;color:#6c7a89">(isi bila sudah ada)</span></label>'
            + '<select id="' + id + '"' + (lockTitle ? ' disabled title="' + esc(lockTitle) + '"' : '') + '><option value="">-- Belum ditetapkan --</option>'
            + '<option value="HIJAU"' + (v === 'HIJAU' ? ' selected' : '') + '>HIJAU</option>'
            + '<option value="MERAH"' + (v === 'MERAH' ? ' selected' : '') + '>MERAH (Periksa Fisik)</option></select></div>';
    }
    function chk(id, text) {
        return '<label class="pwm-chk"><input type="checkbox" id="' + id + '"> <span>' + text + '</span></label>';
    }
    function itemsTable(items) {
        if (!items || !items.length) { return '<div class="pw-muted" style="padding:4px">Daftar barang tidak terbaca dari dokumen ini.</div>'; }
        var h = '<table><tr><th>#</th><th>HS Code</th><th>Uraian</th><th>Jumlah</th><th>Berat Bersih</th><th>Kemasan</th><th>Nilai</th></tr>';
        $.each(items, function (i, it) {
            h += '<tr><td>' + esc(it.item_no) + '</td><td>' + esc(it.hs_code) + '</td><td>' + esc(it.uraian)
                + (it.merk || it.kode_barang ? '<br><span class="pw-muted">' + (it.merk ? 'Merk: ' + esc(it.merk) + ' ' : '') + (it.kode_barang ? 'Kode: ' + esc(it.kode_barang) : '') + '</span>' : '')
                + '</td><td class="r">' + fnum(it.qty, 4).replace(/\.?0+$/, '') + ' ' + esc(it.satuan_kode) + '</td><td class="r">' + fnum(it.berat_bersih, 2)
                + '</td><td class="r">' + (it.kemasan_qty ? esc(it.kemasan_qty) + ' ' + esc(it.kemasan_kode) : '') + '</td><td class="r">' + fnum(it.nilai_ekspor, 2) + '</td></tr>';
        });
        return h + '</table>';
    }
    /* Sumber baca: teks PDF, scan (OCR seluruh dokumen), atau campuran (halaman tanpa teks terbaca saja yang di-OCR) */
    function srcLabel(r) {
        var op = r.ocr_pages || [];
        if (r.source === 'ocr' && r.combined && op.length) {
            return 'teks PDF + <b style="color:#b36b00">OCR hal. ' + esc(op.join(', ')) + '</b> <span style="color:#666">(halaman itu tidak punya teks yang bisa dibaca komputer)</span>';
        }
        return r.source === 'ocr' ? '<b style="color:#b36b00">SCAN (OCR)</b>' : 'teks PDF';
    }
    function checksLine(r) {
        var c = r.checks || {}, bad = c.failed || [];
        if (r.source === 'ocr') { return ''; }
        if (!bad.length && (c.passed || []).length) {
            return '<div class="pwm-ok">&#10003; Validasi silang lolos: ' + (c.passed.indexOf('nilai_barang_vs_header') >= 0 ? 'total nilai barang = Jumlah Nilai Ekspor, ' : '')
                + (c.passed.indexOf('berat_barang_vs_header') >= 0 ? 'total berat barang = Berat Bersih, ' : '') + (r.items || []).length + ' jenis barang terbaca.</div>';
        }
        return '';
    }

    /* Ringkasan PPB (dipakai di pratinjau PEB gabungan dan pratinjau PPB) */
    function ppbSummary(h) {
        var a = [];
        if (h.ppb_tgl_periksa) { a.push('periksa fisik ' + fdate(h.ppb_tgl_periksa) + (h.ppb_kantor_periksa ? ' di ' + esc(h.ppb_kantor_periksa) : '')); }
        if (h.ppb_tgl_stuffing) { a.push('stuffing ' + fdate(h.ppb_tgl_stuffing) + (h.ppb_tempat_stuffing ? ' / ' + esc(h.ppb_tempat_stuffing) : '')); }
        if (h.ppb_jml_kontainer !== null && h.ppb_jml_kontainer !== undefined && h.ppb_jml_kontainer !== '') { a.push(esc(h.ppb_jml_kontainer) + ' kontainer'); }
        if (h.ppb_jml_kemasan !== null && h.ppb_jml_kemasan !== undefined && h.ppb_jml_kemasan !== '') { a.push(esc(h.ppb_jml_kemasan) + ' kemasan'); }
        return a.join(' &middot; ');
    }

    Inst.prototype.previewPeb = function (r) {
        var self = this, h = r.header || {}, m = r.match || {}, low = (r.confidence === 'low'), ni = r.npe || {};
        var hasNpe = !!r.npe, hasPpb = !!r.ppb && !!h.ppb_number, comb = !!r.combined && (hasNpe || hasPpb);
        var names = ['PEB'];
        if (hasNpe) { names.push('NPE'); }
        if (hasPpb) { names.push('PPB'); }
        var nameTxt = names.join(' + ');
        var conflict = (m.npe_conflict || m.ppb_conflict) ? true : false;
        var showHij = hasNpe && !hasPpb && !h.jalur;
        var html = '<div class="pwm">'
            + '<div style="margin-bottom:5px"><b>' + esc(r.original_name) + '</b> &middot; <a href="' + esc(this.ep) + '/view_tmp/' + esc(r.token) + '" target="_blank" rel="noopener">buka PDF</a>'
            + ' &middot; dibaca dari ' + srcLabel(r) + '</div>'
            + (comb ? '<div class="pwm-ok">&#10003; File ini berisi <b>' + nameTxt + '</b> (satu AJU). Semuanya sudah dibaca dan akan disimpan sekaligus.'
                + (hasNpe ? '<br>NPE: <b>' + esc(h.npe_number || 'belum terbaca') + '</b>' + (h.npe_date ? ' (' + fdate(h.npe_date) + ')' : '')
                    + (ni.jenis_komoditi ? ' &middot; ' + esc(ni.jenis_komoditi) : '')
                    + (ni.berat_kotor ? ' &middot; berat kotor ' + esc(fnum(ni.berat_kotor, 2)) + ' ' + esc(ni.berat_kotor_satuan || '') : '')
                    + (ni.kemasan_qty ? ' &middot; kemasan ' + esc(ni.kemasan_qty) + ' ' + esc(ni.kemasan_jenis || '') : '') : '')
                + (hasPpb ? '<br>PPB: <b>' + esc(h.ppb_number) + '</b>' + (h.ppb_date ? ' (' + fdate(h.ppb_date) + ')' : '') + ' &middot; ' + ppbSummary(h)
                    + '<br><b style="color:#c0392b">Jalur otomatis MERAH</b> karena ada PPB (pemeriksaan fisik).' : '')
                + '</div>' : '')
            + warnBox(r.warnings, low || m.hbl_mismatch) + checksLine(r)
            + '<div class="pwm-h">Identitas dokumen</div><div class="pwm-grid">'
            + inp('pwf-aju', 'Nomor AJU', h.aju_number, { span: 1, max: 30, up: 1 }) + inp('pwf-peb', 'Nomor PEB', h.peb_number, { max: 30 }) + inp('pwf-pebd', 'Tanggal PEB', h.peb_date, { type: 'date' })
            + inp('pwf-bl', 'HBL (di PEB)', h.bl_number, { max: 100, up: 1 })
            + inp('pwf-npe', 'Nomor NPE', h.npe_number, { max: 40, ph: comb ? 'tidak terbaca - isi manual' : 'diisi nanti / upload NPE' }) + inp('pwf-nped', 'Tanggal NPE', h.npe_date, { type: 'date' })
            + jalurSel('pwf-jalur', h.jalur || '', hasPpb ? 'Jalur MERAH ditetapkan oleh PPB' : '') + inp('pwf-jalurd', 'Tgl Penetapan Jalur', h.jalur_date || '', { type: 'date' })
            + '</div>'
            + (showHij ? '<div class="pwm-ok" style="margin-top:6px">&#8505; NPE ada dan tidak ada PPB (pemeriksaan fisik) &rarr; biasanya jalur <b>HIJAU</b>. <a href="#" id="pwf-hij" style="font-weight:bold">Tetapkan HIJAU</a> <span class="pw-muted">(tidak otomatis; Anda yang memutuskan)</span></div>' : '')
            + '<div class="pwm-h">Transaksi &amp; kemasan (versi PEB)</div><div class="pwm-grid">'
            + inp('pwf-kat', 'Kategori Ekspor', h.kategori_ekspor, { span: 1 }) + inp('pwf-jenis', 'Jenis Ekspor', h.jenis_ekspor)
            + inp('pwf-inco', 'Term (Cara Penyerahan)', h.incoterm, { max: 10, up: 1 }) + inp('pwf-val', 'Valuta', h.valuta_kode, { max: 3, up: 1 })
            + inp('pwf-nilai', 'Nilai Ekspor', h.nilai_ekspor) + inp('pwf-frt', 'Biaya Pengangkutan', h.biaya_pengangkutan)
            + inp('pwf-ins', 'Asuransi', h.asuransi) + inp('pwf-kurs', 'NDPBM (kurs)', h.ndpbm)
            + inp('pwf-kq', 'Jumlah Kemasan', h.kemasan_qty) + inp('pwf-kj', 'Jenis Kemasan', h.kemasan_jenis)
            + inp('pwf-mk', 'Marking / Merek Kemasan', h.kemasan_marking, { span: 1, ph: 'kosong bila tidak ada' }) + inp('pwf-bk', 'Berat Kotor (kg)', h.berat_kotor)
            + inp('pwf-bb', 'Berat Bersih (kg)', h.berat_bersih)
            + '</div>'
            + '<div class="pwm-h">Pengangkutan &amp; para pihak</div><div class="pwm-grid">'
            + inp('pwf-kpl', 'Kapal', h.sarana_nama) + inp('pwf-voy', 'Voyage', h.voyage) + inp('pwf-etd', 'Perkiraan Ekspor', h.tgl_perkiraan_ekspor, { type: 'date' })
            + inp('pwf-pt', 'Pelabuhan Tujuan', h.pelabuhan_tujuan) + inp('pwf-neg', 'Negara Tujuan', h.negara_tujuan)
            + inp('pwf-exp', 'Eksportir', h.eksportir_nama, { span: 1 }) + inp('pwf-buy', 'Pembeli', h.pembeli_nama, { span: 1 })
            + inp('pwf-inv', 'No. Invoice', h.invoice_no) + inp('pwf-pl', 'No. Packing List', h.packing_list_no)
            + '</div>'
            + '<div class="pwm-h">Barang (' + (r.items || []).length + ' jenis)' + (h.hs_codes ? ' &middot; HS: ' + esc(h.hs_codes) : '') + '</div>' + itemsTable(r.items)
            + '<div style="margin-top:8px">'
            + (low ? chk('pwf-clow', '<b>Saya sudah mencocokkan data di atas dengan dokumen asli</b> (hasil baca keyakinan rendah).') : '')
            + (m.hbl_mismatch ? chk('pwf-cmis', '<b>Saya yakin PEB ini milik booking ini</b> walau HBL berbeda (' + esc(m.hbl_doc) + ' vs ' + esc(m.hbl_booking) + ').') : '')
            + (m.duplicate_in && m.duplicate_in.length ? chk('pwf-cdup', '<b>AJU ini memang dipakai juga di booking lain</b> (' + esc(m.duplicate_in.join(', ')) + ').') : '')
            + (conflict ? chk('pwf-rep', '<b>Ganti dokumen lama</b>: '
                + (m.npe_conflict ? 'NPE ' + esc(m.npe_conflict) + ' &rarr; ' + esc(h.npe_number) : '')
                + (m.npe_conflict && m.ppb_conflict ? '; ' : '')
                + (m.ppb_conflict ? 'PPB ' + esc(m.ppb_conflict) + ' &rarr; ' + esc(h.ppb_number) : '')) : '')
            + '</div></div>';

        return Swal.fire({
            title: (m.existing_id ? 'Perbarui ' : 'Pratinjau ') + nameTxt + ' - periksa lalu simpan', html: html, width: 980,
            showCancelButton: true, confirmButtonText: 'Simpan ' + nameTxt, cancelButtonText: 'Batal', allowOutsideClick: false,
            didOpen: function () {
                $('#pwf-hij').on('click', function (e) {
                    e.preventDefault();
                    $('#pwf-jalur').val('HIJAU');
                    if (!val('pwf-jalurd')) { $('#pwf-jalurd').val(val('pwf-nped')); }
                });
            },
            showLoaderOnConfirm: true,
            preConfirm: function () {
                if (!val('pwf-aju') && !val('pwf-peb')) { Swal.showValidationMessage('Isi minimal Nomor AJU atau Nomor PEB.'); return false; }
                if (!!val('pwf-peb') !== !!val('pwf-pebd')) { Swal.showValidationMessage('Nomor PEB dan Tanggal PEB harus berpasangan.'); return false; }
                if (!!val('pwf-npe') !== !!val('pwf-nped')) { Swal.showValidationMessage('Nomor NPE dan Tanggal NPE harus berpasangan.'); return false; }
                if (val('pwf-npe') && !val('pwf-peb')) { Swal.showValidationMessage('NPE tidak bisa diisi tanpa PEB.'); return false; }
                if (val('pwf-pebd') && val('pwf-nped') && val('pwf-nped') < val('pwf-pebd')) { Swal.showValidationMessage('Tanggal NPE tidak boleh lebih awal dari Tanggal PEB.'); return false; }
                if (val('pwf-jalur') && !val('pwf-peb')) { Swal.showValidationMessage('Jalur hanya bisa diisi setelah PEB terdaftar.'); return false; }
                if (low && !$('#pwf-clow').is(':checked')) { Swal.showValidationMessage('Centang konfirmasi bahwa data sudah dicocokkan dengan dokumen asli.'); return false; }
                if (m.hbl_mismatch && !$('#pwf-cmis').is(':checked')) { Swal.showValidationMessage('HBL berbeda: centang konfirmasi atau batalkan.'); return false; }
                if (m.duplicate_in && m.duplicate_in.length && !$('#pwf-cdup').is(':checked')) { Swal.showValidationMessage('AJU sudah ada di booking lain: centang konfirmasi atau batalkan.'); return false; }
                if (conflict && !$('#pwf-rep').is(':checked')) { Swal.showValidationMessage('PEB ini sudah punya NPE/PPB lain: centang "Ganti dokumen lama" atau batalkan.'); return false; }
                var map = { aju_number: 'pwf-aju', peb_number: 'pwf-peb', peb_date: 'pwf-pebd', bl_number: 'pwf-bl', npe_number: 'pwf-npe', npe_date: 'pwf-nped',
                    jalur: 'pwf-jalur', jalur_date: 'pwf-jalurd', kategori_ekspor: 'pwf-kat', jenis_ekspor: 'pwf-jenis', incoterm: 'pwf-inco', valuta_kode: 'pwf-val',
                    nilai_ekspor: 'pwf-nilai', biaya_pengangkutan: 'pwf-frt', asuransi: 'pwf-ins', ndpbm: 'pwf-kurs', kemasan_qty: 'pwf-kq', kemasan_jenis: 'pwf-kj',
                    kemasan_marking: 'pwf-mk', berat_kotor: 'pwf-bk', berat_bersih: 'pwf-bb', sarana_nama: 'pwf-kpl', voyage: 'pwf-voy', tgl_perkiraan_ekspor: 'pwf-etd',
                    pelabuhan_tujuan: 'pwf-pt', negara_tujuan: 'pwf-neg', eksportir_nama: 'pwf-exp', pembeli_nama: 'pwf-buy', invoice_no: 'pwf-inv', packing_list_no: 'pwf-pl' };
                var h2 = {};
                $.each(map, function (k, id) { h2[k] = val(id); });
                var d = { token: r.token, booking_code: self.booking, h: h2,
                    confirm_low: $('#pwf-clow').is(':checked') ? 1 : 0, confirm_mismatch: $('#pwf-cmis').is(':checked') ? 1 : 0, confirm_dup: $('#pwf-cdup').is(':checked') ? 1 : 0,
                    replace: $('#pwf-rep').is(':checked') ? 1 : 0 };
                return self.post('save_peb', d).then(function (res) {
                    if (!res.status) { Swal.showValidationMessage(res.message); return false; }
                    return res;
                }, function (msg) { Swal.showValidationMessage(msg); return false; });
            }
        }).then(function (x) {
            if (x.isConfirmed && x.value && x.value.status) {
                self.render(x.value.rows || [], x.value.id);
                self.changed(x.value.message);
            }
        });
    };

    Inst.prototype.previewNpe = function (r) {
        var self = this, h = r.header || {}, m = r.match || {}, low = (r.confidence === 'low'), t = m.target;
        var html = '<div class="pwm">'
            + '<div style="margin-bottom:5px"><b>' + esc(r.original_name) + '</b> &middot; <a href="' + esc(this.ep) + '/view_tmp/' + esc(r.token) + '" target="_blank" rel="noopener">buka PDF</a>'
            + ' &middot; <b>NPE (Nota Pelayanan Ekspor)</b> &middot; ' + srcLabel(r) + '</div>'
            + warnBox(r.warnings, low || !t)
            + (t ? '<div class="pwm-ok">&#10003; Akan ditempel ke PEB: AJU <b>' + esc(t.aju_number) + '</b> &middot; PEB <b>' + esc(t.peb_number) + '</b>' + (t.peb_date ? ' (' + fdate(t.peb_date) + ')' : '') + '</div>' : '')
            + '<div class="pwm-grid">'
            + inp('pwn-no', 'Nomor NPE', h.npe_number, { span: 1, max: 40, up: 1 }) + inp('pwn-tgl', 'Tanggal NPE', h.npe_date, { type: 'date' })
            + inp('pwn-aju', 'Nomor AJU (untuk mencocokkan PEB)', h.aju_number, { span: 1, max: 30, up: 1 }) + inp('pwn-peb', 'Nomor PEB', h.peb_number)
            + inp('pwn-kom', 'Jenis Komoditi', h.jenis_komoditi, { ro: 1 }) + inp('pwn-kpl', 'Kapal / Voyage', (h.sarana_nama || '') + ' ' + (h.voyage || ''), { ro: 1 })
            + inp('pwn-bk', 'Berat Kotor', (h.berat_kotor || '') + ' ' + (h.berat_kotor_satuan || ''), { ro: 1 }) + inp('pwn-kem', 'Kemasan', (h.kemasan_qty || '') + ' ' + (h.kemasan_jenis || ''), { ro: 1 })
            + '</div>'
            + (low ? chk('pwn-clow', '<b>Saya sudah mencocokkan data di atas dengan dokumen asli</b> (hasil baca keyakinan rendah).') : '')
            + (m.npe_conflict ? chk('pwn-rep', '<b>Ganti NPE lama</b> (' + esc(m.npe_conflict) + ') dengan NPE ini.') : '')
            + '</div>';

        return Swal.fire({
            title: 'Pratinjau NPE', html: html, width: 780, showCancelButton: true, confirmButtonText: 'Simpan NPE', cancelButtonText: 'Batal',
            allowOutsideClick: false, showLoaderOnConfirm: true,
            preConfirm: function () {
                if (!val('pwn-no') || !val('pwn-tgl')) { Swal.showValidationMessage('Nomor dan Tanggal NPE wajib terisi.'); return false; }
                if (low && !$('#pwn-clow').is(':checked')) { Swal.showValidationMessage('Centang konfirmasi bahwa data sudah dicocokkan dengan dokumen asli.'); return false; }
                if (m.npe_conflict && !$('#pwn-rep').is(':checked')) { Swal.showValidationMessage('PEB ini sudah punya NPE lain: centang "Ganti NPE lama" atau batalkan.'); return false; }
                var d = { token: r.token, booking_code: self.booking,
                    h: { npe_number: val('pwn-no'), npe_date: val('pwn-tgl'), aju_number: val('pwn-aju'), peb_number: val('pwn-peb') },
                    confirm_low: $('#pwn-clow').is(':checked') ? 1 : 0, replace: $('#pwn-rep').is(':checked') ? 1 : 0 };
                return self.post('save_npe', d).then(function (res) {
                    if (!res.status) { Swal.showValidationMessage(res.message); return false; }
                    return res;
                }, function (msg) { Swal.showValidationMessage(msg); return false; });
            }
        }).then(function (x) {
            if (x.isConfirmed && x.value && x.value.status) {
                self.render(x.value.rows || [], x.value.id);
                self.changed(x.value.message);
            }
        });
    };

    function nz(v) { return (v === null || v === undefined) ? '' : v; }

    Inst.prototype.previewPpb = function (r) {
        var self = this, h = r.header || {}, m = r.match || {}, low = (r.confidence === 'low'), t = m.target;
        var html = '<div class="pwm">'
            + '<div style="margin-bottom:5px"><b>' + esc(r.original_name) + '</b> &middot; <a href="' + esc(this.ep) + '/view_tmp/' + esc(r.token) + '" target="_blank" rel="noopener">buka PDF</a>'
            + ' &middot; <b>PPB (Pemberitahuan Pemeriksaan Barang)</b> &middot; ' + srcLabel(r) + '</div>'
            + warnBox(r.warnings, low || !t)
            + (t ? '<div class="pwm-ok">&#10003; Akan ditempel ke PEB: AJU <b>' + esc(t.aju_number) + '</b> &middot; PEB <b>' + esc(t.peb_number) + '</b>' + (t.peb_date ? ' (' + fdate(t.peb_date) + ')' : '')
                + '<br><b style="color:#c0392b">Jalur menjadi MERAH</b> (ada pemeriksaan fisik).</div>' : '')
            + '<div class="pwm-grid">'
            + inp('pwp-no', 'Nomor PPB', h.ppb_number, { span: 1, max: 40, up: 1 }) + inp('pwp-tgl', 'Tanggal PPB', h.ppb_date, { type: 'date' })
            + inp('pwp-peb', 'Nomor PEB yang diperiksa (untuk mencocokkan)', h.peb_number, { span: 1, max: 30 }) + inp('pwp-pebd', 'Tanggal PEB', h.peb_date, { type: 'date', ro: 1 })
            + inp('pwp-exp', 'Eksportir', h.eksportir_nama, { span: 1, ro: 1 }) + inp('pwp-ppjk', 'PPJK', h.ppjk_nama, { ro: 1 })
            + inp('pwp-per', 'Tgl / Kantor Pemeriksaan', (h.ppb_tgl_periksa ? fdate(h.ppb_tgl_periksa) : '') + (h.ppb_kantor_periksa ? ' - ' + h.ppb_kantor_periksa : ''), { span: 1, ro: 1 })
            + inp('pwp-lok', 'Lokasi Pemeriksaan', h.ppb_lokasi_periksa, { ro: 1 })
            + inp('pwp-stf', 'Stuffing', (h.ppb_tgl_stuffing ? fdate(h.ppb_tgl_stuffing) : '') + (h.ppb_tempat_stuffing ? ' - ' + h.ppb_tempat_stuffing : ''), { span: 1, ro: 1 })
            + inp('pwp-jml', 'Kontainer / Kemasan', nz(h.ppb_jml_kontainer) + ' kontainer / ' + nz(h.ppb_jml_kemasan) + ' kemasan', { ro: 1 })
            + '</div>'
            + (low ? chk('pwp-clow', '<b>Saya sudah mencocokkan data di atas dengan dokumen asli</b> (hasil baca keyakinan rendah).') : '')
            + (m.ppb_conflict ? chk('pwp-rep', '<b>Ganti PPB lama</b> (' + esc(m.ppb_conflict) + ') dengan PPB ini.') : '')
            + '</div>';

        return Swal.fire({
            title: 'Pratinjau PPB', html: html, width: 780, showCancelButton: true, confirmButtonText: 'Simpan PPB', cancelButtonText: 'Batal',
            allowOutsideClick: false, showLoaderOnConfirm: true,
            preConfirm: function () {
                if (!val('pwp-no') || !val('pwp-tgl')) { Swal.showValidationMessage('Nomor dan Tanggal PPB wajib terisi.'); return false; }
                if (low && !$('#pwp-clow').is(':checked')) { Swal.showValidationMessage('Centang konfirmasi bahwa data sudah dicocokkan dengan dokumen asli.'); return false; }
                if (m.ppb_conflict && !$('#pwp-rep').is(':checked')) { Swal.showValidationMessage('PEB ini sudah punya PPB lain: centang "Ganti PPB lama" atau batalkan.'); return false; }
                var d = { token: r.token, booking_code: self.booking,
                    h: { ppb_number: val('pwp-no'), ppb_date: val('pwp-tgl'), peb_number: val('pwp-peb') },
                    confirm_low: $('#pwp-clow').is(':checked') ? 1 : 0, replace: $('#pwp-rep').is(':checked') ? 1 : 0 };
                return self.post('save_ppb', d).then(function (res) {
                    if (!res.status) { Swal.showValidationMessage(res.message); return false; }
                    return res;
                }, function (msg) { Swal.showValidationMessage(msg); return false; });
            }
        }).then(function (x) {
            if (x.isConfirmed && x.value && x.value.status) {
                self.render(x.value.rows || [], x.value.id);
                self.changed(x.value.message);
            }
        });
    };

    /* ---------------- hapus ---------------- */
    Inst.prototype.remove = function (id) {
        var self = this, row = null;
        $.each(this.data, function (i, r) { if (String(r.id) === String(id)) { row = r; } });
        if (!row) { return; }
        Swal.fire({
            icon: 'warning', title: 'Hapus PEB ini?', width: 520,
            html: '<div style="text-align:left;font-size:12px">AJU <b>' + esc(row.aju_number) + '</b> &middot; PEB <b>' + esc(row.peb_number) + '</b><br>'
                + 'Penghapusan tercatat (siapa, kapan, alasan). Isi alasan:</div>',
            input: 'textarea', inputAttributes: { maxlength: 255 }, inputPlaceholder: 'cth: salah upload, PEB milik booking lain',
            showCancelButton: true, confirmButtonText: 'Hapus', cancelButtonText: 'Batal', confirmButtonColor: '#b94a48', showLoaderOnConfirm: true,
            preConfirm: function (reason) {
                reason = $.trim(reason || '');
                if (reason.length < 5) { Swal.showValidationMessage('Alasan minimal 5 karakter.'); return false; }
                return self.post('remove', { id: id, reason: reason }).then(function (res) {
                    if (!res.status) { Swal.showValidationMessage(res.message); return false; }
                    return res;
                }, function (msg) { Swal.showValidationMessage(msg); return false; });
            }
        }).then(function (x) {
            if (x.isConfirmed && x.value && x.value.status) {
                self.sel = null; self.newMode = false;
                self.render(x.value.rows || []);
                self.changed(x.value.message);
            }
        });
    };

    /* ---------------- detail ---------------- */
    Inst.prototype.detail = function (id) {
        var self = this;
        this.post('detail', { id: id }).then(function (r) {
            if (!r.status) { fail('Gagal', r.message); return; }
            var p = r.peb, kv = function (l, v) { return (v === null || v === undefined || v === '') ? '' : '<div><label>' + esc(l) + '</label><div>' + esc(v) + '</div></div>'; };
            var docs = '';
            if (r.documents && r.documents.length) {
                docs = '<table><tr><th>Jenis</th><th>Nomor</th><th>Tanggal</th></tr>';
                $.each(r.documents, function (i, d) { docs += '<tr><td>' + esc(d.jenis) + '</td><td>' + esc(d.nomor) + '</td><td>' + fdate(d.tanggal) + '</td></tr>'; });
                docs += '</table>';
            }
            var html = '<div class="pwm"><div class="pwm-grid">'
                + kv('AJU', p.aju_number) + kv('Nomor PEB', p.peb_number) + kv('Tanggal PEB', fdate(p.peb_date)) + kv('HBL', p.bl_number)
                + kv('Nomor NPE', p.npe_number) + kv('Tanggal NPE', fdate(p.npe_date)) + kv('Jalur', p.jalur) + kv('Tgl Jalur', fdate(p.jalur_date))
                + kv('Kategori Ekspor', p.kategori_ekspor) + kv('Jenis Ekspor', p.jenis_ekspor) + kv('Cara Perdagangan', p.cara_perdagangan) + kv('Cara Pembayaran', p.cara_pembayaran)
                + kv('Term', p.incoterm) + kv('Valuta', p.valuta_kode) + kv('Nilai Ekspor', fnum(p.nilai_ekspor, 2)) + kv('NDPBM', fnum(p.ndpbm, 2))
                + kv('Biaya Pengangkutan', fnum(p.biaya_pengangkutan, 2)) + kv('Asuransi', fnum(p.asuransi, 2)) + kv('Bea Keluar (Rp)', fnum(p.nilai_bea_keluar, 0)) + kv('PPh 22 Ekspor (Rp)', fnum(p.pph22_ekspor, 0))
                + kv('Kemasan', (p.kemasan_qty || '') + ' ' + (p.kemasan_jenis || '')) + kv('Marking', p.kemasan_marking) + kv('Berat Kotor (kg)', fnum(p.berat_kotor, 4)) + kv('Berat Bersih (kg)', fnum(p.berat_bersih, 4))
                + kv('Kapal / Voyage', ((p.sarana_nama || '') + ' ' + (p.voyage || '')).trim()) + kv('Perkiraan Ekspor', fdate(p.tgl_perkiraan_ekspor)) + kv('Pelabuhan Muat', p.pelabuhan_muat_ekspor) + kv('Pelabuhan Tujuan', p.pelabuhan_tujuan)
                + kv('Negara Tujuan', p.negara_tujuan) + kv('Eksportir', p.eksportir_nama) + kv('Pembeli', p.pembeli_nama) + kv('Penerima', p.penerima_nama)
                + kv('Invoice', p.invoice_no) + kv('Packing List', p.packing_list_no) + kv('Kantor Pabean Ekspor', p.kantor_pabean_ekspor) + kv('Lokasi Pemeriksaan', p.lokasi_pemeriksaan)
                + kv('Jenis Komoditi (NPE)', p.npe_komoditi) + kv('Sumber data', (p.peb_source || '') + (p.peb_confidence ? ' / ' + p.peb_confidence : ''))
                + kv('Diunggah', ((p.peb_uploaded_by || '') + ' ' + (p.peb_uploaded_at || '')).trim())
                + '</div>'
                + '<div class="pwm-h">Barang (' + (r.items || []).length + ')</div>' + itemsTable(r.items)
                + (docs ? '<div class="pwm-h">Dokumen pelengkap pabean</div>' + docs : '')
                + '</div>';
            Swal.fire({ title: 'Detail PEB', html: html, width: 980, confirmButtonText: 'Tutup' });
        }, function (m) { fail('Gagal', m); });
    };

    /* ---------------- registry ---------------- */
    window.PebWidget = {
        get: function (id) { return registry[id] || null; },
        /* Buat instance untuk elemen ini. Bila id yang sama sudah ada tetapi elemennya sudah diganti
           (view dirender ulang lewat AJAX), instance lama dibuang dan dibuat ulang. */
        init: function (el) {
            if (!el || !el.id) { return null; }
            var cur = registry[el.id];
            if (cur && cur.$el[0] === el) { return cur; }
            registry[el.id] = new Inst(el);
            return registry[el.id];
        },
        scan: function () {
            $('.pebw').each(function () { window.PebWidget.init(this); });
        },
        /* Simpan isian semua widget yang masih tampil (panggil sebelum SAVE halaman). Reject(pesan) bila tidak valid. */
        flushAll: function (opts) {
            var ps = [], nosync = !!(opts && opts.sync === false);
            $.each(registry, function (id, inst) { if (inst.$el.closest('body').length) { ps.push(inst.flush(nosync)); } });
            return Promise.all(ps);
        }
    };
    $(function () { window.PebWidget.scan(); });
})(window, window.jQuery);
</script>
<?php else: ?>
<script>
(function () { if (window.PebWidget && window.jQuery) { jQuery(function () { window.PebWidget.scan(); }); } })();
</script>
<?php endif; ?>
