<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Peb_document
 * ---------------------------------------------------------------------------
 * Endpoint upload & baca dokumen PEB / NPE (PDF). REUSABLE: tidak terikat ke
 * satu halaman. View mana pun cukup memuat widget
 * (views/pricelist/partials/peb_widget.php) atau memanggil endpoint ini:
 *
 *   POST pricelist/peb_document/parse       file, [booking_code]   -> pratinjau hasil baca (PEB, NPE, PPB, atau gabungannya dalam satu file; otomatis)
 *   POST pricelist/peb_document/save_peb    token, booking_code, h[...], [replace=1]   -> simpan PEB (+ NPE bila file gabungan)
 *   POST pricelist/peb_document/save_npe    token, booking_code, h[...]   -> tempel NPE ke PEB yang cocok (via AJU)
 *   POST pricelist/peb_document/save_ppb    token, booking_code, h[...]   -> tempel PPB ke PEB yang cocok (via nomor PEB); jalur jadi MERAH
 *   POST pricelist/peb_document/add_manual  booking_code, h[...]          -> input manual tanpa PDF
 *   POST pricelist/peb_document/rows        booking_code                  -> daftar PEB booking
 *   POST pricelist/peb_document/detail      id                            -> 1 PEB + barang + dokumen pelengkap
 *   POST pricelist/peb_document/update      id, h[...]                    -> ubah NPE/jalur/koreksi (tidak boleh dikosongkan)
 *   POST pricelist/peb_document/remove      id, reason                    -> hapus (soft delete, alasan wajib)
 *   GET  pricelist/peb_document/view_file/ID/peb|npe|ppb                  -> tampilkan PDF tersimpan
 *   GET  pricelist/peb_document/view_tmp/TOKEN                            -> tampilkan PDF yang baru diunggah (pratinjau)
 *   GET  pricelist/peb_document/diagnose[?warmup=1]                       -> cek server (poppler, tesseract, izin folder, batas PHP)
 *
 * Semua respons JSON: {status:true|false, message:'...', ...}
 *
 * Penguraian PDF ada di application/libraries/Peb_parser.php (tanpa dependensi
 * CI), penyimpanan di application/models/pricelist/Peb_model.php.
 */
class Peb_document extends CI_Controller
{
    const MAX_BYTES = 10485760;   // 10 MB

    protected $dir;
    protected $tmp;

    public function __construct()
    {
        parent::__construct();
        $this->load->library('fungsi');
        $this->fungsi->restrict();
        $this->load->model('pricelist/peb_model');
        $this->load->library('peb_parser');

        $this->dir = APPPATH . 'third_party/file/peb_documents/';
        $this->tmp = $this->dir . 'tmp/';
    }

    /* =====================================================================
     *  1. BACA PDF -> PRATINJAU
     * ===================================================================== */

    public function parse()
    {
        // Lepas kunci sesi supaya request lain dari halaman yang sama tidak menunggu proses baca PDF,
        // dan beri waktu cukup (di Windows batas 30 detik dihitung jam dinding).
        if (function_exists('session_write_close')) { @session_write_close(); }
        @set_time_limit(120);

        $code = trim((string) $this->input->post('booking_code'));
        $booking = null;
        if ($code !== '') {
            if (($m = $this->peb_model->schema_message()) !== '') { return $this->out(array('status' => false, 'message' => $m)); }
            $booking = $this->peb_model->find_booking($code);
            if (!$booking) {
                return $this->out(array('status' => false, 'message' => 'Booking ' . $code . ' tidak ditemukan.'));
            }
        }

        $f = $this->validate_upload();
        if (is_string($f)) { return $this->out(array('status' => false, 'message' => $f)); }

        if (!$this->ensure_dir($this->tmp)) {
            return $this->out(array('status' => false, 'message' => 'Folder penyimpanan sementara tidak bisa dibuat/ditulis: ' . $this->tmp));
        }
        $this->cleanup_tmp();

        $token = bin2hex(random_bytes(16));
        $path  = $this->tmp . $token . '.pdf';
        if (!move_uploaded_file($f['tmp_name'], $path)) {
            return $this->out(array('status' => false, 'message' => 'Gagal menyimpan file unggahan.'));
        }
        @chmod($path, 0640);

        $res = $this->peb_parser->parse_file($path, array('original_name' => $f['name']));
        if (empty($res['ok'])) {
            @unlink($path);
            return $this->out(array('status' => false, 'message' => $res['error']));
        }

        if (($res['doc_type'] === 'PPB' || !empty($res['ppb'])) && ($m = $this->peb_model->ppb_message()) !== '') {
            @unlink($path);
            return $this->out(array('status' => false, 'message' => $m));
        }

        $hash = hash_file('sha256', $path);
        $type = $res['doc_type'];
        $H    = $res['header'];
        $warn = $res['warnings'];
        $match = array('booking_code' => $code);

        if ($type === 'PEB') {
            $hbl = isset($H['bl_number']) ? $H['bl_number'] : '';
            $match['hbl_doc']        = $hbl;
            $match['hbl_from_name']  = (isset($H['bl_number_source']) && $H['bl_number_source'] === 'filename');
            if ($booking) {
                $match['hbl_booking']  = (string) $booking['house_bl'];
                $match['hbl_mismatch'] = $hbl !== '' && !empty($booking['house_bl']) && $this->norm_hbl($hbl) !== $this->norm_hbl($booking['house_bl']);
                if ($match['hbl_mismatch']) {
                    $warn[] = 'HBL pada PEB (' . $hbl . ') BERBEDA dengan HBL booking ini (' . $booking['house_bl'] . '). Pastikan PEB ini milik booking yang benar.';
                }
                $ex = $this->peb_model->find_active_in_booking($code, isset($H['aju_number']) ? $H['aju_number'] : '', isset($H['peb_number']) ? $H['peb_number'] : '');
                $match['existing_id'] = $ex ? (int) $ex['id'] : null;
                if ($ex) { $warn[] = 'PEB ini sudah tercatat di booking ini; menyimpan akan MEMPERBARUI data yang ada (nilai kosong tidak menimpa).'; }
                if (!empty($res['combined']) && $ex && !empty($ex['npe_number']) && !empty($H['npe_number'])
                    && strtoupper($ex['npe_number']) !== strtoupper($H['npe_number'])) {
                    $match['npe_conflict'] = $ex['npe_number'];
                    $warn[] = 'PEB ini sudah punya NPE ' . $ex['npe_number'] . '. Menyimpan file ini akan menggantinya dengan ' . $H['npe_number'] . ' (perlu dicentang).';
                }
                if (!empty($res['ppb']) && $ex && !empty($ex['ppb_number']) && !empty($H['ppb_number'])
                    && strtoupper($ex['ppb_number']) !== strtoupper($H['ppb_number'])) {
                    $match['ppb_conflict'] = $ex['ppb_number'];
                    $warn[] = 'PEB ini sudah punya PPB ' . $ex['ppb_number'] . '. Menyimpan file ini akan menggantinya dengan ' . $H['ppb_number'] . ' (perlu dicentang).';
                }
                if (!empty($res['ppb']) && $ex && $ex['jalur'] === 'HIJAU') {
                    $warn[] = 'PEB ini tercatat jalur HIJAU, tetapi file ini memuat PPB (pemeriksaan fisik). Jalur akan menjadi MERAH.';
                }
                $others = $this->peb_model->other_bookings_with_aju(isset($H['aju_number']) ? $H['aju_number'] : '', $code);
                $match['duplicate_in'] = $others;
                if ($others) { $warn[] = 'AJU ini sudah dipakai di booking lain: ' . implode(', ', $others) . '.'; }
            } elseif ($hbl !== '') {
                $b = $this->peb_model->find_booking_by_hbl($hbl);
                $match['suggest_booking'] = $b ? $b['booking_code'] : null;
            }
        } elseif ($type === 'PPB') {
            if (!$booking) {
                @unlink($path);
                return $this->out(array('status' => false, 'message' => 'PPB harus diunggah dari booking tertentu (booking_code wajib).'));
            }
            $target = $this->peb_model->match_ppb_target($code, $H);
            $match['target'] = $target ? array(
                'id' => (int) $target['id'], 'aju_number' => $target['aju_number'], 'peb_number' => $target['peb_number'],
                'peb_date' => $target['peb_date'], 'ppb_number' => isset($target['ppb_number']) ? $target['ppb_number'] : null,
                'jalur' => $target['jalur'],
            ) : null;
            if (!$target) {
                $warn[] = 'PEB nomor ' . ($H['peb_number'] !== '' ? $H['peb_number'] : '-') . ' (yang diperiksa pada PPB ini) belum ada di booking ini. Upload PEB-nya dulu.';
            } else {
                if (!empty($target['ppb_number']) && strtoupper($target['ppb_number']) !== strtoupper($H['ppb_number'])) {
                    $match['ppb_conflict'] = $target['ppb_number'];
                    $warn[] = 'PEB ini sudah punya PPB ' . $target['ppb_number'] . '. Menyimpan akan menggantinya.';
                }
                if (!empty($H['peb_date']) && !empty($target['peb_date']) && $H['peb_date'] !== substr($target['peb_date'], 0, 10)) {
                    $warn[] = 'Tanggal PEB pada PPB (' . $H['peb_date'] . ') berbeda dengan PEB tersimpan (' . $target['peb_date'] . '). Pastikan PPB ini untuk PEB yang benar.';
                }
                if ($H['ppb_jml_kemasan'] !== null && $target['kemasan_qty'] !== null && (int) $H['ppb_jml_kemasan'] !== (int) $target['kemasan_qty']) {
                    $warn[] = 'Jumlah kemasan pada PPB (' . $H['ppb_jml_kemasan'] . ') berbeda dengan PEB (' . $target['kemasan_qty'] . ').';
                }
                if ($target['jalur'] === 'HIJAU') {
                    $warn[] = 'PEB ini tercatat jalur HIJAU. Dengan adanya PPB (pemeriksaan fisik) jalurnya akan menjadi MERAH.';
                }
            }
        } else { // NPE
            if (!$booking) {
                @unlink($path);
                return $this->out(array('status' => false, 'message' => 'NPE harus diunggah dari booking tertentu (booking_code wajib).'));
            }
            $target = $this->peb_model->match_npe_target($code, $H);
            $match['target'] = $target ? array(
                'id' => (int) $target['id'], 'aju_number' => $target['aju_number'], 'peb_number' => $target['peb_number'],
                'peb_date' => $target['peb_date'], 'npe_number' => $target['npe_number'],
            ) : null;
            if (!$target) {
                $warn[] = 'PEB untuk NPE ini (AJU ' . $H['aju_number'] . ' / PEB ' . $H['peb_number'] . ') belum ada di booking ini. Upload PEB-nya dulu.';
            } else {
                if (!empty($target['npe_number']) && strtoupper($target['npe_number']) !== strtoupper($H['npe_number'])) {
                    $match['npe_conflict'] = $target['npe_number'];
                    $warn[] = 'PEB ini sudah punya NPE ' . $target['npe_number'] . '. Menyimpan akan menggantinya.';
                }
                // Silang data NPE vs PEB
                if ($H['berat_kotor'] !== null && $target['berat_kotor'] !== null && abs((float) $H['berat_kotor'] - (float) $target['berat_kotor']) > 0.01) {
                    $warn[] = 'Berat kotor NPE (' . $H['berat_kotor'] . ') berbeda dengan PEB (' . $target['berat_kotor'] . ').';
                }
                if ($H['kemasan_qty'] !== null && $target['kemasan_qty'] !== null && (int) $H['kemasan_qty'] !== (int) $target['kemasan_qty']) {
                    $warn[] = 'Jumlah kemasan NPE (' . $H['kemasan_qty'] . ') berbeda dengan PEB (' . $target['kemasan_qty'] . ').';
                }
                if (!empty($H['peb_number']) && !empty($target['peb_number']) && $H['peb_number'] !== $target['peb_number']) {
                    $warn[] = 'Nomor PEB pada NPE (' . $H['peb_number'] . ') berbeda dengan PEB tersimpan (' . $target['peb_number'] . ').';
                }
                if (!empty($H['voyage']) && !empty($target['voyage']) && strtoupper($H['voyage']) !== strtoupper($target['voyage'])) {
                    $warn[] = 'Voyage NPE (' . $H['voyage'] . ') berbeda dengan PEB (' . $target['voyage'] . ').';
                }
            }
        }

        // File gabungan PEB + NPE (satu AJU): dibaca sebagai PEB lengkap dengan NPE-nya.
        $combined = !empty($res['combined']);
        $match['combined'] = $combined;
        $npe_info = null;
        if ($combined && !empty($res['npe'])) {
            $N = $res['npe']['header'];
            $npe_info = array(
                'npe_number' => $H['npe_number'], 'npe_date' => $H['npe_date'], 'jenis_komoditi' => isset($N['jenis_komoditi']) ? $N['jenis_komoditi'] : '',
                'berat_kotor' => $N['berat_kotor'], 'berat_kotor_satuan' => isset($N['berat_kotor_satuan']) ? $N['berat_kotor_satuan'] : '',
                'kemasan_qty' => $N['kemasan_qty'], 'kemasan_jenis' => isset($N['kemasan_jenis']) ? $N['kemasan_jenis'] : '',
                'pages' => isset($res['combined_pages']) ? $res['combined_pages'] : null,
            );
        }

        $ppb_info = null;
        if (!empty($res['ppb'])) {
            $ppb_info = array();
            foreach ($H as $k => $v) { if (strpos($k, 'ppb_') === 0) { $ppb_info[$k] = $v; } }
        } elseif ($type === 'PPB') {
            $ppb_info = $H;
        }

        $cache = array(
            'doc_type' => $type, 'original_name' => $f['name'], 'hash' => $hash, 'booking_code' => $code,
            'source' => $res['source'], 'confidence' => $res['confidence'], 'header' => $H,
            'items' => $res['items'], 'packages' => $res['packages'], 'documents' => $res['documents'],
            'warnings' => $warn, 'created' => time(), 'combined' => $combined,
        );
        file_put_contents($this->tmp . $token . '.json', json_encode($cache, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);

        return $this->out(array(
            'status' => true, 'token' => $token, 'doc_type' => $type, 'original_name' => $f['name'],
            'source' => $res['source'], 'confidence' => $res['confidence'], 'header' => $H,
            'packages' => $res['packages'], 'items' => $res['items'], 'documents' => $res['documents'],
            'checks' => $res['checks'], 'warnings' => $warn, 'match' => $match,
            'combined' => $combined, 'npe' => $npe_info, 'ppb' => $ppb_info,
            'ocr_pages' => isset($res['ocr_pages']) ? $res['ocr_pages'] : array(),
            'same_file_before' => ($code !== '' && $this->peb_model->find_by_file_hash($code, $hash, strtolower($type))) ? true : false,
        ));
    }

    /* =====================================================================
     *  2. SIMPAN
     * ===================================================================== */

    public function save_peb()
    {
        $c = $this->load_cache('PEB');
        if (is_string($c)) { return $this->out(array('status' => false, 'message' => $c)); }

        $code = trim((string) $this->input->post('booking_code'));
        $booking = $this->guard_booking($code);
        if (is_string($booking)) { return $this->out(array('status' => false, 'message' => $booking)); }

        $H = $c['header'];
        $over = (array) $this->input->post('h');
        foreach (array_keys($over) as $k) {
            if (!$this->is_editable($k)) { continue; }
            $v = is_array($over[$k]) ? '' : trim((string) $over[$k]);
            if ($v !== '') { $H[$k] = $v; }            // anti-blank: kosong tidak menimpa hasil baca
        }

        // --- Pengaman ---
        $hbl = isset($H['bl_number']) ? $H['bl_number'] : '';
        if ($hbl !== '' && !empty($booking['house_bl']) && $this->norm_hbl($hbl) !== $this->norm_hbl($booking['house_bl'])
            && !$this->flag('confirm_mismatch')) {
            return $this->out(array('status' => false, 'code' => 'HBL_MISMATCH',
                'message' => 'HBL pada PEB (' . $hbl . ') berbeda dengan HBL booking (' . $booking['house_bl'] . ').'));
        }
        $others = $this->peb_model->other_bookings_with_aju(isset($H['aju_number']) ? $H['aju_number'] : '', $code);
        if ($others && !$this->flag('confirm_dup')) {
            return $this->out(array('status' => false, 'code' => 'AJU_DUPLICATE',
                'message' => 'AJU ' . $H['aju_number'] . ' sudah dipakai di booking lain: ' . implode(', ', $others) . '.'));
        }
        if ($c['confidence'] === 'low' && !$this->flag('confirm_low')) {
            return $this->out(array('status' => false, 'code' => 'LOW_CONFIDENCE',
                'message' => 'Hasil baca dokumen berkeyakinan rendah. Cocokkan dengan dokumen asli, lalu centang konfirmasi.'));
        }

        // --- file permanen ---
        $src = $this->tmp . $c['token'] . '.pdf';
        if (!$this->ensure_dir($this->dir)) { return $this->out(array('status' => false, 'message' => 'Folder penyimpanan tidak bisa dibuat/ditulis: ' . $this->dir)); }
        $final = 'peb_' . substr($c['hash'], 0, 16) . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(3)) . '.pdf';   // akhiran acak: dua simpan di detik yang sama tidak saling menimpa
        if (!@copy($src, $this->dir . $final)) {
            return $this->out(array('status' => false, 'message' => 'Gagal menyimpan file PDF ke folder dokumen.'));
        }
        @chmod($this->dir . $final, 0640);

        // File gabungan PEB + NPE: PDF yang sama dicatat juga sebagai file NPE (bila Nomor & Tanggal NPE terisi).
        $combined = !empty($c['combined']) && !empty($H['npe_number']);
        $with_ppb = !empty($c['combined']) && !empty($H['ppb_number']);
        $meta = array(
            'user' => $this->session->userdata('fullname'), 'source' => $c['source'], 'confidence' => $c['confidence'],
            'warnings' => $c['warnings'], 'file' => $final, 'file_name' => $c['original_name'], 'file_hash' => $c['hash'],
            'documents' => $c['documents'],
        );
        if ($combined) {
            $meta['npe_file']    = $final;
            $meta['replace_npe'] = $this->flag('replace');
        }
        if ($with_ppb) {
            $meta['ppb_file']    = $final;
            $meta['replace_ppb'] = $this->flag('replace');
        }
        $res = $this->peb_model->save_peb($code, $H, $c['items'], $meta);
        if (empty($res['status'])) {
            @unlink($this->dir . $final);
            return $this->out(array('status' => false, 'code' => isset($res['code']) ? $res['code'] : null,
                'message' => $res['message'], 'existing' => isset($res['existing']) ? $res['existing'] : null));
        }
        @unlink($src); @unlink($this->tmp . $c['token'] . '.json');
        $names = array('PEB');
        if ($combined) { $names[] = 'NPE'; }
        if ($with_ppb) { $names[] = 'PPB'; }
        $what = count($names) > 1 ? implode(', ', array_slice($names, 0, -1)) . ' dan ' . end($names) : $names[0];
        return $this->out(array('status' => true, 'id' => $res['id'], 'action' => $res['action'],
            'message' => $what . ($res['action'] === 'inserted' ? ' tersimpan.' : ' diperbarui.'),
            'rows' => $this->peb_model->list_by_booking($code)));
    }

    public function save_npe()
    {
        $c = $this->load_cache('NPE');
        if (is_string($c)) { return $this->out(array('status' => false, 'message' => $c)); }
        $code = trim((string) $this->input->post('booking_code'));
        $booking = $this->guard_booking($code);
        if (is_string($booking)) { return $this->out(array('status' => false, 'message' => $booking)); }

        $H = $c['header'];
        foreach ((array) $this->input->post('h') as $k => $v) {
            if (in_array($k, array('aju_number', 'peb_number', 'npe_number', 'npe_date'), true) && !is_array($v) && trim($v) !== '') {
                $H[$k] = trim($v);
            }
        }
        if ($c['confidence'] === 'low' && !$this->flag('confirm_low')) {
            return $this->out(array('status' => false, 'code' => 'LOW_CONFIDENCE',
                'message' => 'Hasil baca dokumen berkeyakinan rendah. Cocokkan dengan dokumen asli, lalu centang konfirmasi.'));
        }

        $src = $this->tmp . $c['token'] . '.pdf';
        if (!$this->ensure_dir($this->dir)) { return $this->out(array('status' => false, 'message' => 'Folder penyimpanan tidak bisa dibuat/ditulis: ' . $this->dir)); }
        $final = 'npe_' . substr($c['hash'], 0, 16) . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(3)) . '.pdf';   // akhiran acak: dua simpan di detik yang sama tidak saling menimpa
        if (!@copy($src, $this->dir . $final)) {
            return $this->out(array('status' => false, 'message' => 'Gagal menyimpan file PDF ke folder dokumen.'));
        }
        @chmod($this->dir . $final, 0640);

        $res = $this->peb_model->attach_npe($code, $H, array(
            'user' => $this->session->userdata('fullname'), 'source' => $c['source'],
            'file' => $final, 'file_name' => $c['original_name'], 'file_hash' => $c['hash'],
        ), array('replace' => $this->flag('replace')));
        if (empty($res['status'])) {
            @unlink($this->dir . $final);
            return $this->out(array('status' => false, 'code' => isset($res['code']) ? $res['code'] : null,
                'message' => $res['message'], 'existing' => isset($res['existing']) ? $res['existing'] : null));
        }
        @unlink($src); @unlink($this->tmp . $c['token'] . '.json');
        return $this->out(array('status' => true, 'id' => $res['id'], 'message' => 'NPE tersimpan pada PEB terkait.',
            'rows' => $this->peb_model->list_by_booking($code)));
    }

    public function save_ppb()
    {
        $c = $this->load_cache('PPB');
        if (is_string($c)) { return $this->out(array('status' => false, 'message' => $c)); }
        $code = trim((string) $this->input->post('booking_code'));
        $booking = $this->guard_booking($code);
        if (is_string($booking)) { return $this->out(array('status' => false, 'message' => $booking)); }

        $H = $c['header'];
        foreach ((array) $this->input->post('h') as $k => $v) {
            if (in_array($k, array('ppb_number', 'ppb_date', 'peb_number'), true) && !is_array($v) && trim($v) !== '') {
                $H[$k] = trim($v);
            }
        }
        if ($c['confidence'] === 'low' && !$this->flag('confirm_low')) {
            return $this->out(array('status' => false, 'code' => 'LOW_CONFIDENCE',
                'message' => 'Hasil baca dokumen berkeyakinan rendah. Cocokkan dengan dokumen asli, lalu centang konfirmasi.'));
        }

        $src = $this->tmp . $c['token'] . '.pdf';
        if (!$this->ensure_dir($this->dir)) { return $this->out(array('status' => false, 'message' => 'Folder penyimpanan tidak bisa dibuat/ditulis: ' . $this->dir)); }
        $final = 'ppb_' . substr($c['hash'], 0, 16) . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(3)) . '.pdf';
        if (!@copy($src, $this->dir . $final)) {
            return $this->out(array('status' => false, 'message' => 'Gagal menyimpan file PDF ke folder dokumen.'));
        }
        @chmod($this->dir . $final, 0640);

        $res = $this->peb_model->attach_ppb($code, $H, array(
            'user' => $this->session->userdata('fullname'), 'source' => $c['source'],
            'file' => $final, 'file_name' => $c['original_name'], 'file_hash' => $c['hash'],
        ), array('replace' => $this->flag('replace')));
        if (empty($res['status'])) {
            @unlink($this->dir . $final);
            return $this->out(array('status' => false, 'code' => isset($res['code']) ? $res['code'] : null,
                'message' => $res['message'], 'existing' => isset($res['existing']) ? $res['existing'] : null));
        }
        @unlink($src); @unlink($this->tmp . $c['token'] . '.json');
        $msg = 'PPB tersimpan pada PEB terkait; jalur MERAH.';
        if (!empty($res['jalur_before']) && $res['jalur_before'] !== 'MERAH') { $msg = 'PPB tersimpan; jalur diubah dari ' . $res['jalur_before'] . ' menjadi MERAH.'; }
        return $this->out(array('status' => true, 'id' => $res['id'], 'message' => $msg,
            'rows' => $this->peb_model->list_by_booking($code)));
    }

    public function add_manual()
    {
        $code = trim((string) $this->input->post('booking_code'));
        $booking = $this->guard_booking($code);
        if (is_string($booking)) { return $this->out(array('status' => false, 'message' => $booking)); }

        $H = array();
        foreach ((array) $this->input->post('h') as $k => $v) {
            if ($this->is_editable($k) && !is_array($v)) { $H[$k] = trim($v); }
        }
        $others = $this->peb_model->other_bookings_with_aju(isset($H['aju_number']) ? $H['aju_number'] : '', $code);
        if ($others && !$this->flag('confirm_dup')) {
            return $this->out(array('status' => false, 'code' => 'AJU_DUPLICATE',
                'message' => 'AJU ' . $H['aju_number'] . ' sudah dipakai di booking lain: ' . implode(', ', $others) . '.'));
        }
        $res = $this->peb_model->save_peb($code, $H, array(), array(
            'user' => $this->session->userdata('fullname'), 'source' => 'manual'));
        if (empty($res['status'])) { return $this->out(array('status' => false, 'message' => $res['message'])); }
        return $this->out(array('status' => true, 'id' => $res['id'], 'message' => 'Data PEB tersimpan.',
            'rows' => $this->peb_model->list_by_booking($code)));
    }

    /* =====================================================================
     *  3. BACA / UBAH / HAPUS
     * ===================================================================== */

    public function rows()
    {
        $code = trim((string) $this->input->post('booking_code'));
        if ($code === '') { return $this->out(array('status' => false, 'message' => 'booking_code wajib.')); }
        if (($m = $this->peb_model->schema_message()) !== '') { return $this->out(array('status' => false, 'message' => $m)); }
        return $this->out(array('status' => true, 'rows' => $this->peb_model->list_by_booking($code)));
    }

    public function detail()
    {
        $id = (int) $this->input->post_get('id');
        $r = $this->peb_model->get($id);
        if (!$r || !empty($r['deleted_at'])) { return $this->out(array('status' => false, 'message' => 'Data PEB tidak ditemukan.')); }
        $docs = !empty($r['docs_json']) ? json_decode($r['docs_json'], true) : array();
        unset($r['docs_json']);
        return $this->out(array('status' => true, 'peb' => $r, 'items' => $this->peb_model->items($id), 'documents' => $docs ?: array()));
    }

    public function update()
    {
        $id = (int) $this->input->post('id');
        $in = array();
        foreach ((array) $this->input->post('h') as $k => $v) { if (!is_array($v)) { $in[$k] = $v; } }
        $res = $this->peb_model->update_fields($id, $in, (string) $this->session->userdata('fullname'));
        if (empty($res['status'])) { return $this->out(array('status' => false, 'message' => $res['message'])); }
        $row = $this->peb_model->get($id);
        return $this->out(array('status' => true, 'message' => $res['changed'] ? 'Perubahan tersimpan.' : 'Tidak ada perubahan.',
            'rows' => $this->peb_model->list_by_booking($row['booking_code'])));
    }

    public function remove()
    {
        $id = (int) $this->input->post('id');
        $row = $this->peb_model->get($id);
        $res = $this->peb_model->soft_delete($id, (string) $this->input->post('reason'), (string) $this->session->userdata('fullname'));
        if (empty($res['status'])) { return $this->out(array('status' => false, 'message' => $res['message'])); }
        return $this->out(array('status' => true, 'message' => 'PEB dihapus (tercatat di riwayat).',
            'rows' => $this->peb_model->list_by_booking($row['booking_code'])));
    }

    /* =====================================================================
     *  DIAGNOSA (untuk pemasangan / bila pembacaan PDF gagal atau lambat)
     * ===================================================================== */

    /**
     * GET pricelist/peb_document/diagnose            -> cek alat, izin folder, batas PHP
     * GET pricelist/peb_document/diagnose?warmup=1   -> beri waktu hingga 150 detik untuk uji baca pertama
     *                                                   (membangun cache font poppler/fontconfig)
     */
    public function diagnose()
    {
        if (function_exists('session_write_close')) { @session_write_close(); }
        @set_time_limit(240);
        $warm = ((string) $this->input->get('warmup') === '1');

        $dis = array_filter(array_map('trim', explode(',', (string) ini_get('disable_functions'))));
        $dirs = array();
        foreach (array('peb_documents' => $this->dir, 'peb_documents/tmp' => $this->tmp, 'temp_sistem' => sys_get_temp_dir()) as $k => $d) {
            $dirs[$k] = array('path' => $d, 'ada' => is_dir($d), 'bisa_ditulis' => is_dir($d) ? is_writable($d) : is_writable(dirname(rtrim($d, '/\\'))));
        }
        $info = array(
            'php' => PHP_VERSION, 'os' => PHP_OS_FAMILY, 'sapi' => PHP_SAPI,
            'user_proses' => function_exists('posix_geteuid') && function_exists('posix_getpwuid')
                ? (($u = posix_getpwuid(posix_geteuid())) ? $u['name'] : '?') : get_current_user(),
            'proc_open_aktif' => function_exists('proc_open') && !in_array('proc_open', $dis, true),
            'disable_functions' => implode(',', $dis),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'), 'post_max_size' => ini_get('post_max_size'),
            'memory_limit' => ini_get('memory_limit'),
            'folder' => $dirs,
            'tabel_peb' => $this->peb_model->schema_status(),
        );
        $info['pembaca_pdf'] = $this->peb_parser->diagnose($warm);

        $t = $info['pembaca_pdf'];
        $masalah = array();
        if (!$info['proc_open_aktif']) { $masalah[] = 'proc_open dinonaktifkan di php.ini (disable_functions).'; }
        if (empty($t['tools']['pdftotext']['found'])) { $masalah[] = 'pdftotext tidak ditemukan (install poppler-utils).'; }
        if (!empty($t['selftest_pdftotext']['timed_out'])) { $masalah[] = 'pdftotext sangat lambat/menggantung saat uji baca. Jalankan ulang dengan ?warmup=1 sekali; bila tetap gagal, folder HOME/cache tidak bisa ditulis atau fontconfig bermasalah.'; }
        elseif (empty($t['selftest_pdftotext']['ok'])) { $masalah[] = 'uji baca pdftotext gagal (lihat stderr).'; }
        elseif ($t['selftest_pdftotext']['secs'] > 8) { $masalah[] = 'uji baca pdftotext lambat (' . $t['selftest_pdftotext']['secs'] . ' dtk); jalankan ?warmup=1 lalu ulangi, harusnya di bawah 1 dtk.'; }
        $to_bytes = function ($v) {
            $v = trim((string) $v); $n = (float) $v; $u = strtolower(substr($v, -1));
            return $u === 'g' ? $n * 1073741824 : ($u === 'm' ? $n * 1048576 : ($u === 'k' ? $n * 1024 : $n));
        };
        if ($to_bytes($info['upload_max_filesize']) < self::MAX_BYTES || $to_bytes($info['post_max_size']) < self::MAX_BYTES) {
            $masalah[] = 'upload_max_filesize / post_max_size di php.ini lebih kecil dari 10M: PDF besar akan ditolak server (naikkan ke minimal 10M).';
        }
        if (empty($t['tools']['tesseract']['found'])) { $masalah[] = '(opsional) tesseract tidak ada: PDF hasil scan tidak bisa dibaca otomatis.'; }
        if (empty($t['home_writable'])) { $masalah[] = 'folder cache ' . $t['home_dir'] . ' tidak bisa ditulis.'; }
        if (!$info['tabel_peb']['ok']) { $masalah[] = 'tabel PEB belum ada: jalankan migration_peb_documents.sql di database tahun ini.'; }
        else {
            $info['kolom_ppb'] = $this->peb_model->ppb_ready();
            if (!$info['kolom_ppb']) { $masalah[] = 'kolom PPB belum ada: jalankan migration_peb_ppb.sql di database tahun ini (dibutuhkan untuk membaca PPB / jalur MERAH otomatis).'; }
        }
        $info['kesimpulan'] = $masalah ? $masalah : array('OK: semua pemeriksaan lolos.');

        $this->output->set_content_type('application/json')
            ->set_output(json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /* =====================================================================
     *  4. TAMPILKAN PDF
     * ===================================================================== */

    public function view_file($id = 0, $type = 'peb')
    {
        $r = $this->peb_model->get((int) $id);
        $col = ($type === 'npe') ? 'npe_file' : (($type === 'ppb') ? 'ppb_file' : 'peb_file');
        $nm  = ($type === 'npe') ? 'npe_file_name' : (($type === 'ppb') ? 'ppb_file_name' : 'peb_file_name');
        if (!$r || empty($r[$col]) || !preg_match('/^[A-Za-z0-9_\-]+\.pdf$/', $r[$col])) { show_404(); return; }
        $this->stream_pdf($this->dir . $r[$col], (string) $r[$nm]);
    }

    public function view_tmp($token = '')
    {
        if (!preg_match('/^[a-f0-9]{32}$/', (string) $token)) { show_404(); return; }
        $this->stream_pdf($this->tmp . $token . '.pdf', 'pratinjau.pdf');
    }

    /* =====================================================================
     *  INTERNAL
     * ===================================================================== */

    protected function stream_pdf($path, $name)
    {
        if (!is_file($path)) { show_404(); return; }
        $name = preg_replace('/[^A-Za-z0-9._\- ]/', '_', $name !== '' ? $name : 'dokumen.pdf');
        $this->output->set_content_type('application/pdf')
            ->set_header('Content-Disposition: inline; filename="' . $name . '"')
            ->set_header('X-Content-Type-Options: nosniff')
            ->set_header('Content-Length: ' . filesize($path))
            ->set_output(file_get_contents($path));
    }

    protected function out(array $payload)
    {
        $this->output->set_content_type('application/json')
            ->set_output(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR));
    }

    protected function flag($name)
    {
        return (string) $this->input->post($name) === '1';
    }

    protected function is_editable($k)
    {
        return $this->peb_model->is_user_editable($k);
    }

    /** Pastikan tabel ada & booking ada. Mengembalikan baris booking atau string pesan error. */
    protected function guard_booking($code)
    {
        if ($code === '') { return 'booking_code wajib.'; }
        if (($m = $this->peb_model->schema_message()) !== '') { return $m; }
        $b = $this->peb_model->find_booking($code);
        return $b ? $b : 'Booking ' . $code . ' tidak ditemukan di database ' . $this->peb_model->dbx->database . '.';
    }

    protected function norm_hbl($s)
    {
        return preg_replace('/[^A-Z0-9]/', '', strtoupper((string) $s));
    }

    /** @return array|string  cache pratinjau (dengan 'token') atau pesan error */
    protected function load_cache($expect_type)
    {
        $token = (string) $this->input->post('token');
        if (!preg_match('/^[a-f0-9]{32}$/', $token)) { return 'Token pratinjau tidak valid. Upload ulang file.'; }
        $file = $this->tmp . $token . '.json';
        if (!is_file($file) || !is_file($this->tmp . $token . '.pdf')) { return 'Pratinjau sudah kedaluwarsa. Upload ulang file.'; }
        $c = json_decode((string) file_get_contents($file), true);
        if (!is_array($c) || !isset($c['doc_type'])) { return 'Data pratinjau rusak. Upload ulang file.'; }
        if ($c['doc_type'] !== $expect_type) {
            return 'Dokumen ini terbaca sebagai ' . $c['doc_type'] . ', bukan ' . $expect_type . '.';
        }
        $c['token'] = $token;
        return $c;
    }

    /** @return array|string  $_FILES['file'] yang valid atau pesan error */
    protected function validate_upload()
    {
        if (!isset($_FILES['file'])) { return 'File tidak ditemukan pada permintaan.'; }
        $f = $_FILES['file'];
        if ($f['error'] !== UPLOAD_ERR_OK) {
            if ($f['error'] === UPLOAD_ERR_INI_SIZE || $f['error'] === UPLOAD_ERR_FORM_SIZE) {
                return 'Ukuran file melebihi batas upload server (upload_max_filesize ' . ini_get('upload_max_filesize') . ').';
            }
            return 'Upload gagal (kode ' . (int) $f['error'] . ').';
        }
        if ($f['size'] > self::MAX_BYTES) { return 'Ukuran file maksimal 10 MB.'; }
        if (strtolower(pathinfo($f['name'], PATHINFO_EXTENSION)) !== 'pdf') { return 'Hanya file PDF yang diterima.'; }
        if (function_exists('finfo_open')) {
            $fi = finfo_open(FILEINFO_MIME_TYPE);
            $mime = $fi ? finfo_file($fi, $f['tmp_name']) : '';
            if ($fi) { finfo_close($fi); }
            if ($mime !== 'application/pdf') { return 'Isi file bukan PDF.'; }
        }
        return $f;
    }

    protected function ensure_dir($d)
    {
        if (!is_dir($d) && !@mkdir($d, 0750, true)) { return false; }
        return is_writable($d);
    }

    /** Hapus pratinjau yang ditinggalkan > 24 jam. */
    protected function cleanup_tmp()
    {
        foreach ((array) glob($this->tmp . '*') as $f) {
            if (is_file($f) && filemtime($f) < time() - 86400) { @unlink($f); }
        }
    }
}
