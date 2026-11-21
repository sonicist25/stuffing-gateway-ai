<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Peb_parser
 * ---------------------------------------------------------------------------
 * Pembaca dokumen PEB (BC 3.0 / CEISA) dan NPE (Nota Pelayanan Ekspor) dalam
 * bentuk PDF. Jenis dokumen dikenali otomatis (lihat hasil ['doc_type']).
 * Jenis dokumen: PEB, NPE (Nota Pelayanan Ekspor), PPB (Pemberitahuan Pemeriksaan Barang = pemeriksaan fisik).
 * Satu file boleh memuat PEB + NPE dan/atau PPB dengan AJU yang sama (urutan halaman bebas):
 * hasilnya doc_type 'PEB' dengan ['combined'=>true], header kolom NPE dan PPB terisi, ['npe'] dan ['ppb']
 * berisi detail masing-masing; adanya PPB membuat jalur = MERAH. File berisi >1 AJU ditolak dengan pesan.
 *
 *  - Library ini TIDAK bergantung pada database, session, maupun view. Bisa
 *    dipakai dari controller/model/CLI mana pun:
 *
 *        $this->load->library('peb_parser');
 *        $res = $this->peb_parser->parse_file($path, ['original_name' => $nama]);
 *        if ($res['ok']) { $res['header'], $res['items'], $res['documents'] ... }
 *
 *  - Strategi ekstraksi:
 *      1. Lapisan teks PDF  -> `pdftotext -layout` (poppler-utils). Akurat 100%
 *         untuk PDF hasil cetak CEISA (bukan scan).
 *      2. Fallback OCR      -> `pdftoppm` + `tesseract`, HANYA bila PDF tidak
 *         punya lapisan teks (hasil scan). Hasil OCR selalu ditandai
 *         confidence = 'low' dan field kuncinya wajib dicek manual oleh user.
 *
 *  - Setiap hasil dilengkapi `checks` (jumlah nilai barang = total header,
 *    berat, jumlah kemasan, format AJU) dan `warnings` agar UI bisa memberi
 *    tahu user bila ada yang janggal.
 *
 * Kebutuhan server: poppler-utils (pdftotext). Opsional untuk scan:
 * tesseract-ocr. Fungsi PHP proc_open harus aktif.
 */
class Peb_parser
{
    const PARSER_VERSION = '1.0';

    protected $cfg = array(
        'pdftotext'     => 'pdftotext',
        'pdftoppm'      => 'pdftoppm',
        'tesseract'     => 'tesseract',
        'ocr'           => true,   // izinkan fallback OCR untuk PDF scan
        'ocr_dpi'       => 300,
        'ocr_max_pages' => 1,   // halaman 1 memuat seluruh header PEB/NPE
        'ocr_gap_pages' => 3,   // maks. halaman yang di-OCR bila hanya SEBAGIAN halaman PDF yang teksnya tidak terbaca
        'timeout'       => 40,     // detik maksimum per perintah eksternal
        'total_timeout' => 50,     // detik maksimum untuk SEMUA langkah satu dokumen (harus < timeout browser)
        'max_bytes'     => 15728640, // 15 MB
        'home'          => '',     // folder HOME/cache untuk poppler & fontconfig (default: folder temp sistem)
    );

    /** @var float|null batas waktu mutlak (microtime) untuk dokumen yang sedang diproses */
    protected $deadline = null;

    public function __construct($config = array())
    {
        if (is_array($config)) {
            $this->cfg = array_merge($this->cfg, $config);
        }
    }

    /* =====================================================================
     *  PUBLIC API
     * ===================================================================== */

    /**
     * Ekstrak + parse sebuah file PDF PEB.
     *
     * @param string $pdf_path path file PDF
     * @param array  $opts     original_name => nama file asli (dipakai untuk menebak HBL)
     * @return array  ['ok'=>bool, 'error'=>string] bila gagal; bila sukses lihat parse_text()
     */
    public function parse_file($pdf_path, $opts = array())
    {
        $this->deadline = microtime(true) + (float) $this->cfg['total_timeout'];
        try {
            $ex = $this->extract_text($pdf_path);
        } finally {
            $this->deadline = null;
        }
        if (!$ex['ok']) {
            return array('ok' => false, 'error' => $ex['error']);
        }

        $ocr_pages = isset($ex['ocr_pages']) ? $ex['ocr_pages'] : array();
        $res = $this->parse_text($ex['text'], $ex['source'], $ocr_pages);
        $res['extract'] = array(
            'source'  => $ex['source'],
            'pages'   => isset($ex['pages']) ? $ex['pages'] : null,
            'chars'   => strlen($ex['text']),
            'ocr_pages' => $ocr_pages,
        );
        if ($ocr_pages) { $res['ocr_pages'] = $ocr_pages; }

        $name = isset($opts['original_name']) ? (string) $opts['original_name'] : basename($pdf_path);
        $res['hbl_from_filename'] = $this->hbl_from_filename($name);

        // Bila B/L tidak tercantum di dokumen, pakai tebakan dari nama file (hanya sebagai petunjuk).
        if ($res['ok'] && empty($res['header']['bl_number']) && $res['hbl_from_filename'] !== '') {
            $res['header']['bl_number']        = $res['hbl_from_filename'];
            $res['header']['bl_number_source'] = 'filename';
        }
        return $res;
    }

    /**
     * Ambil teks dari PDF. Mengembalikan ['ok','text','source'=>'text'|'ocr','pages','error'].
     */
    public function extract_text($pdf_path)
    {
        if (!is_file($pdf_path) || !is_readable($pdf_path)) {
            return $this->fail('File PDF tidak ditemukan / tidak bisa dibaca.');
        }
        if (filesize($pdf_path) > $this->cfg['max_bytes']) {
            return $this->fail('Ukuran file terlalu besar (maks ' . round($this->cfg['max_bytes'] / 1048576) . ' MB).');
        }
        $fh = @fopen($pdf_path, 'rb');
        $head = $fh ? fread($fh, 5) : '';
        if ($fh) { fclose($fh); }
        if ($head !== '%PDF-') {
            return $this->fail('File bukan PDF yang valid.');
        }
        if (!function_exists('proc_open')) {
            return $this->fail('Fungsi proc_open dinonaktifkan di server PHP, ekstraksi PDF tidak bisa dijalankan.');
        }

        $r = $this->run(array($this->cfg['pdftotext'], '-layout', '-enc', 'UTF-8', $pdf_path, '-'));
        if (!$r['spawned']) {
            return $this->fail('pdftotext tidak ditemukan di server. Install poppler-utils (apt install poppler-utils) '
                . 'atau isi config "pdftotext" dengan path lengkapnya.');
        }
        if ($r['timed_out']) {
            return $this->fail('pdftotext tidak selesai dalam ' . round($r['secs']) . ' detik dan dihentikan. '
                . 'Penyebab umum: cache font (fontconfig) belum bisa ditulis oleh user web server. '
                . 'Buka pricelist/peb_document/diagnose?warmup=1 untuk memeriksa dan menghangatkan cache, '
                . 'lalu coba lagi. Sementara itu PEB bisa diisi manual.');
        }
        $text = ($r['code'] === 0) ? $r['out'] : '';
        $pages = $text === '' ? 0 : substr_count($text, "\f");

        if ($this->detect_doc_type($text) !== null) {
            // Ada halaman yang lapisan teksnya rusak/kosong (mis. PEB hasil "print to PDF" dengan font Type 3 tanpa pemetaan
            // karakter, atau halaman scan di tengah PDF teks)? Halaman itu saja yang di-OCR; halaman lain tetap dari teks.
            if ($this->cfg['ocr'] && strpos($text, "\f") !== false) {
                $gap = $this->find_gap_pages($pdf_path, $text);
                if ($gap) {
                    return $this->fill_gaps_with_ocr($pdf_path, $text, $gap);
                }
            }
            return array('ok' => true, 'text' => $text, 'source' => 'text', 'pages' => $pages);
        }

        // Tidak ada lapisan teks PEB -> kemungkinan scan.
        if (!$this->cfg['ocr']) {
            return $this->fail('PDF tidak memiliki lapisan teks (hasil scan) dan OCR dinonaktifkan.');
        }
        return $this->ocr_text($pdf_path);
    }

    /**
     * Parse teks hasil ekstraksi (pdftotext -layout atau OCR).
     *
     * @param string $text      teks (halaman dipisah "\f")
     * @param string $source    'text' | 'ocr'
     * @param array  $ocr_pages nomor halaman (mulai 1) yang teksnya berasal dari OCR walaupun $source = 'text'
     * @param string $ocr_why   'scan' (PDF hasil scan) | 'gap' (teks PDF rusak/kosong pada sebagian halaman); hanya untuk teks peringatan
     */
    public function parse_text($text, $source = 'text', $ocr_pages = array(), $ocr_why = 'scan')
    {
        // File gabungan: PEB + NPE (satu AJU) dalam satu PDF -> dibaca sebagai satu PEB lengkap dengan NPE-nya.
        if ($source !== 'ocr') {
            $split = $this->split_pages((string) $text, (array) $ocr_pages);
            if ($split !== null) {
                if (isset($split['error'])) {
                    return array('ok' => false, 'error' => $split['error']);
                }
                $parsed = array();
                foreach ($split['parts'] as $kind => $part_text) {
                    // Dokumen yang halaman judulnya berasal dari OCR dibaca dengan pembaca OCR (keyakinan rendah).
                    $first = isset($split['pages'][strtolower($kind)][0]) ? $split['pages'][strtolower($kind)][0] : 0;
                    $from_ocr = in_array($first, (array) $ocr_pages, true);
                    $r = $this->parse_text($part_text, $from_ocr ? 'ocr' : $source, array(), $from_ocr ? 'gap' : 'scan');
                    if (empty($r['ok'])) {
                        return array('ok' => false, 'error' => 'File berisi beberapa dokumen (' . implode(' + ', array_keys($split['parts']))
                            . '), tetapi ' . $kind . ' tidak terbaca: ' . $r['error'] . '. Upload sebagai file terpisah.');
                    }
                    $parsed[$kind] = $r;
                }
                return $this->merge_docs($parsed['PEB'], isset($parsed['NPE']) ? $parsed['NPE'] : null, isset($parsed['PPB']) ? $parsed['PPB'] : null, $split['pages']);
            }
        }

        $text = str_replace(array("\r\n", "\r", "\f"), "\n", (string) $text);
        $lines = explode("\n", $text);

        $type = $this->detect_doc_type($text, $source === 'ocr');
        if ($type === null) {
            return array('ok' => false, 'error' => 'Dokumen ini tidak dikenali sebagai PEB (BC 3.0) maupun NPE (Nota Pelayanan Ekspor).');
        }
        if ($source === 'ocr') {
            if ($type === 'PPB') {
                return array('ok' => false, 'error' => 'PPB hasil scan belum bisa dibaca otomatis. Isi Jalur = MERAH secara manual.');
            }
            return $this->parse_ocr_lenient($type, $text, $ocr_why);
        }
        if ($type === 'NPE') {
            return $this->parse_npe($lines, $text, $source);
        }
        if ($type === 'PPB') {
            return $this->parse_ppb($lines, $text, $source);
        }

        $cells = $this->build_cells($lines);
        $H = array();
        $warn = array();

        // ---- Identitas dokumen -------------------------------------------------
        $H['aju_number'] = $this->upper($this->clean_id($this->cell_val($cells, '/^Nomor Pengajuan$/i')));
        if ($H['aju_number'] === '' && preg_match('/Nomor Pengajuan[^0-9A-Z]{0,20}([0-9]{6}[A-Z0-9]{6}[0-9]{14})/', $text, $m)) {
            $H['aju_number'] = $m[1];
        }

        $nopeb = $this->cell_find($cells, '/^1\.\s*Nomor Pendaftaran$/i');
        $H['peb_number'] = $nopeb ? preg_replace('/\D/', '', $nopeb['value']) : '';
        $H['peb_date']   = null;
        if ($nopeb) {
            foreach ($cells as $c) {
                if ($c['line'] > $nopeb['line'] && $c['line'] <= $nopeb['line'] + 3
                    && preg_match('/^Tanggal$/i', $c['label']) && $this->dmy($c['value'])) {
                    $H['peb_date'] = $this->dmy($c['value']);
                    break;
                }
            }
        }

        $H['kantor_pabean_muat']   = $this->cell_val($cells, '/^1\.\s*Kantor Pabean Pemuatan$/i');
        $H['kantor_pabean_ekspor'] = $this->cell_val($cells, '/^2\.\s*Kantor Pabean Ekspor$/i');
        $H['jenis_ekspor']         = $this->cell_val($cells, '/^B\.\s*JENIS EKSPOR$/i');
        $H['kategori_ekspor']      = $this->cell_val($cells, '/^C\.\s*KATEGORI EKSPOR$/i');
        $H['cara_perdagangan']     = $this->cell_val($cells, '/^D\.\s*CARA PERDAGANGAN$/i');
        $H['cara_pembayaran']      = $this->cell_val($cells, '/^E\.\s*CARA PEMBAYARAN$/i');

        // ---- Para pihak --------------------------------------------------------
        $H['eksportir_id']      = preg_replace('/\D/', '', $this->cell_val($cells, '/^1\.\s*Identitas$/i'));
        $H['eksportir_nama']    = $this->cell_val($cells, '/^2\.\s*Nama$/i');
        $H['pemilik_nama']      = $this->cell_val($cells, '/^6\.\s*Nama$/i');
        $H['ppjk_nama']         = $this->cell_val($cells, '/^9\.\s*Nama$/i');
        $H['pembeli_nama']      = $this->cell_val($cells, '/^15\.\s*Nama$/i');
        $H['pembeli_negara']    = $this->cell_val($cells, '/^17\.\s*Negara$/i');
        $H['penerima_nama']     = $this->cell_val($cells, '/^18\.\s*Nama$/i');
        $H['penerima_negara']   = $this->cell_val($cells, '/^20\.\s*Negara$/i');

        // ---- Pengangkutan & pelabuhan -----------------------------------------
        $H['cara_pengangkutan'] = $this->cell_val($cells, '/^21\.\s*Cara Pengangkutan$/i');
        $sarana = $this->cell_find($cells, '/^22\.\s*Nama\s*&\s*Bendera Sarana Pengangkut$/i');
        $H['sarana_bendera'] = $sarana ? $sarana['value'] : '';
        $H['sarana_nama']    = '';
        if ($sarana) {
            for ($i = $sarana['line'] + 1; $i <= $sarana['line'] + 3 && $i < count($lines); $i++) {
                if (trim($lines[$i]) === '') { continue; }
                $cs = $this->line_cells($lines[$i]);
                if ($cs && !preg_match('/^\d{1,2}\.\s/', $cs[0]['text'])) {
                    $H['sarana_nama'] = trim($cs[0]['text']);
                }
                break;
            }
        }
        $H['voyage']              = $this->cell_val($cells, '/^23\.\s*No\.\s*Pengangkut/i');
        $H['tgl_perkiraan_ekspor'] = $this->dmy($this->cell_val($cells, '/^24\.\s*Tanggal Perkiraan Ekspor$/i'));
        $H['pelabuhan_muat_asal']  = $this->cell_val($cells, '/^25\.\s*Pelabuhan Muat Asal$/i');
        $H['pelabuhan_muat_ekspor'] = $this->cell_val($cells, '/^26\.\s*Pelabuhan Muat Ekspor$/i');
        $H['pelabuhan_tujuan']     = $this->cell_val($cells, '/^28\.\s*Pelabuhan Tujuan$/i');
        $H['negara_tujuan']        = $this->cell_val($cells, '/^29\.\s*Negara Tujuan Ekspor$/i');

        // ---- Dokumen pelengkap (halaman 1) ------------------------------------
        list($H['invoice_no'], $H['invoice_date'])            = $this->doc_no_tgl($text, '30\.\s*No\s*&\s*Tgl Invoice');
        list($H['packing_list_no'], $H['packing_list_date'])  = $this->doc_no_tgl($text, '31\.\s*No\s*&\s*Tgl Packing List');
        $H['dok_lainnya']          = $this->cell_val($cells, '/^32\.\s*Jenis,\s*No\s*&\s*Tgl Dok\.\s*Lainnya$/i');
        $H['lokasi_pemeriksaan']   = $this->cell_val($cells, '/^33\.\s*Lokasi Pemeriksaan$/i');
        $H['kantor_pemeriksaan']   = $this->cell_val($cells, '/^34\.\s*Kantor Pabean Pemeriksaan$/i');

        // ---- Transaksi ---------------------------------------------------------
        $H['incoterm']   = $this->upper($this->cell_val($cells, '/^35\.\s*Cara Penyerahan Barang$/i'));
        $H['bank_devisa'] = $this->cell_val($cells, '/^36\.\s*Bank Devisa Hasil Ekspor$/i');
        $val = $this->cell_val($cells, '/^37\.\s*Jenis Valuta$/i');
        $H['valuta_nama'] = '';
        $H['valuta_kode'] = '';
        if (preg_match('/^(.*?)\s*\(([A-Z]{3})\)\s*$/', $val, $m)) {
            $H['valuta_nama'] = trim($m[1]);
            $H['valuta_kode'] = $m[2];
        } elseif (preg_match('/^[A-Z]{3}$/', $val)) {
            $H['valuta_kode'] = $val;
        }
        $H['nilai_ekspor']        = $this->num($this->cell_val($cells, '/^38\.\s*Jumlah Nilai Ekspor$/i'));
        $H['biaya_pengangkutan']  = $this->num($this->cell_val($cells, '/^39\.\s*Biaya Pengangkutan$/i'));
        $H['asuransi']            = $this->num($this->cell_val($cells, '/^40\.\s*Asuransi/i'));
        $H['nilai_maklon']        = $this->num($this->cell_val($cells, '/^41\.\s*Nilai Maklon/i'));
        $H['jumlah_peti_kemas']   = (int) preg_replace('/\D/', '', $this->cell_val($cells, '/^42\.\s*Jumlah Peti Kemas$/i'));
        $H['berat_kotor']         = $this->num($this->cell_val($cells, '/^45\.\s*Berat Kotor/i'));
        $H['berat_bersih']        = $this->num($this->cell_val($cells, '/^46\.\s*Berat Bersih/i'));
        $H['ndpbm']               = $this->num($this->cell_val($cells, '/^55\.\s*Nilai Tukar Mata Uang$/i'));
        $H['nilai_bea_keluar']    = $this->num($this->cell_val($cells, '/^56\.\s*Nilai Bea Keluar$/i'));
        $H['pph22_ekspor']        = $this->num($this->cell_val($cells, '/^57\.\s*PPh Pasal 22 Ekspor$/i'));
        $H['dana_perkebunan']     = $this->num($this->cell_val($cells, '/^58\.\s*Dana Perkebunan$/i'));

        // ---- Kemasan (field 44: "<jumlah> <jenis>/ <merek>") ----------------
        $packages = $this->parse_packages($lines);
        $H['kemasan_qty']     = 0;
        $jenis = array();
        $mark  = array();
        foreach ($packages as $p) {
            $H['kemasan_qty'] += $p['qty'];
            $jenis[] = $p['jenis'];
            if ($p['marking'] !== null && $p['marking'] !== '') { $mark[] = $p['marking']; }
        }
        $H['kemasan_jenis']   = implode('; ', array_unique($jenis));
        $H['kemasan_marking'] = $mark ? implode('; ', array_unique($mark)) : null;

        // ---- Barang ------------------------------------------------------------
        $items = $this->parse_items($lines);
        $H['jumlah_jenis_barang'] = count($items);
        if (preg_match('/(\d+)\s+Jenis barang\.?\s*Lihat lembar lanjutan/i', $text, $m)) {
            $H['jumlah_jenis_barang_dokumen'] = (int) $m[1];
        } else {
            $H['jumlah_jenis_barang_dokumen'] = count($items);
        }
        $hs = array();
        foreach ($items as $it) { $hs[$it['hs_code']] = true; }
        $H['hs_codes'] = implode(', ', array_keys($hs));

        // ---- Dokumen pelengkap (lembar lanjutan) -------------------------------
        $docs = $this->parse_documents($lines);
        $H['bl_number'] = '';
        $H['bl_date']   = null;
        foreach ($docs as $d) {
            if (preg_match('/^B\s*\/\s*L$|BILL OF LADING/i', $d['jenis'])) {
                $H['bl_number'] = $this->upper($d['nomor']);
                $H['bl_date']   = $d['tanggal'];
                break;
            }
        }

        // ---- NPE / jalur: TIDAK ada pada format PEB CEISA yang dicontohkan -----
        // Hanya dibaca bila suatu saat tercetak (belum pernah terverifikasi dgn sampel nyata).
        $H['npe_number'] = null; $H['npe_date'] = null; $H['jalur'] = null;
        if (preg_match('/\bNomor\s+NPE\s*:\s*([A-Z0-9\/\.\-]{4,40})/i', $text, $m)) { $H['npe_number'] = $this->upper($m[1]); }
        if (preg_match('/\bTanggal\s+NPE\s*:\s*(\d{2}-\d{2}-\d{4})/i', $text, $m))  { $H['npe_date'] = $this->dmy($m[1]); }
        if (preg_match('/\b(?:Jalur|Penjaluran)\s*:\s*(HIJAU|MERAH)\b/i', $text, $m)) { $H['jalur'] = $this->upper($m[1]); }

        // ---- Validasi silang ----------------------------------------------------
        $checks = $this->run_checks($H, $items, $source, $warn);

        // ---- Confidence ---------------------------------------------------------
        $confidence = 'high';
        if ($source === 'ocr') {
            $confidence = 'low';
            $warn[] = 'Dokumen ini hasil SCAN dan dibaca dengan OCR. OCR bisa salah baca angka (mis. 9 terbaca 3). '
                    . 'Cocokkan AJU, nomor/tanggal PEB, nilai, dan berat dengan dokumen asli sebelum menyimpan.';
        } elseif (!empty($checks['failed'])) {
            $confidence = 'medium';
        }
        if ($H['aju_number'] === '' || $H['peb_number'] === '' || empty($H['peb_date'])) {
            $confidence = 'low';
            $warn[] = 'AJU / Nomor PEB / Tanggal PEB tidak terbaca lengkap. Isi atau perbaiki manual.';
        }

        return array(
            'ok'          => true,
            'doc_type'    => 'PEB',
            'parser'      => self::PARSER_VERSION,
            'source'      => $source,
            'confidence'  => $confidence,
            'header'      => $H,
            'packages'    => $packages,
            'items'       => $items,
            'documents'   => $docs,
            'checks'      => $checks,
            'warnings'    => $warn,
        );
    }

    /**
     * Pecah teks per halaman bila satu file memuat lebih dari satu jenis dokumen (PEB, NPE, PPB).
     *
     * @return array|null null  = bukan file gabungan (hanya satu jenis dokumen) -> proses seperti biasa
     *                    ['error'=>..]   = berisi >1 AJU berbeda (beberapa dokumen digabung) atau tanpa PEB
     *                    ['parts'=>['PEB'=>teks,'NPE'=>teks,'PPB'=>teks], 'pages'=>['peb'=>[..],'npe'=>[..],'ppb'=>[..]]]
     */
    protected function split_pages($text, array $ocr_pages = array())
    {
        if (strpos($text, "\f") === false) { return null; }
        $pages = explode("\f", str_replace(array("\r\n", "\r"), "\n", $text));
        $kind = array();
        $ajus = array();
        $prev = null;
        foreach ($pages as $i => $p) {
            if (trim($p) === '') { $kind[$i] = null; continue; }
            $is_ocr = in_array($i + 1, $ocr_pages, true);
            $k = $this->page_kind($p, $is_ocr);
            if ($k === null) { $k = $prev; }          // halaman tanpa judul ikut halaman sebelumnya
            $kind[$i] = $k;
            if ($k !== null) { $prev = $k; }
            // AJU hasil OCR tidak dipakai untuk menolak file (satu salah baca tidak boleh menggagalkan upload);
            // ketidakcocokan AJU PEB vs NPE tetap diperiksa di merge_docs() sebagai peringatan.
            if (!$is_ocr && preg_match('/Nomor\s+Pengajuan\s*:\s*([0-9]{6}[A-Z0-9]{6}[0-9]{14})/', $p, $m)) { $ajus[$m[1]] = true; }
        }
        if (count($ajus) > 1) {
            return array('error' => 'File ini berisi lebih dari satu dokumen dengan AJU berbeda (' . implode(', ', array_keys($ajus))
                . '). Pisahkan menjadi satu file per PEB. PEB, NPE, dan PPB dengan AJU yang sama boleh digabung dalam satu file.');
        }
        $has = array_values(array_unique(array_filter($kind)));
        if (count($has) < 2) { return null; }
        if (!in_array('PEB', $has, true)) {
            return array('error' => 'File ini berisi ' . implode(' dan ', $has) . ' tanpa dokumen PEB. Upload masing-masing sebagai file terpisah '
                . '(PEB-nya harus sudah ada di booking).');
        }

        $parts = array();
        $pg = array('peb' => array(), 'npe' => array(), 'ppb' => array());
        foreach ($pages as $i => $p) {
            if ($kind[$i] === null) { continue; }
            $parts[$kind[$i]][] = $p;
            $pg[strtolower($kind[$i])][] = $i + 1;
        }
        foreach ($parts as $k => $arr) { $parts[$k] = implode("\f", $arr); }
        return array('parts' => $parts, 'pages' => $pg);
    }

    /**
     * Gabungkan hasil baca PEB (+ NPE dan/atau PPB) dari satu file menjadi satu hasil PEB lengkap,
     * lalu cocokkan silang dokumennya (nomor/tanggal PEB, berat kotor, jumlah kemasan, voyage).
     * PPB berarti ada pemeriksaan fisik -> jalur MERAH, tanggal penetapan = tanggal PPB.
     */
    protected function merge_docs(array $peb, $npe, $ppb, array $pages)
    {
        $H = $peb['header'];
        $warn = $peb['warnings'];
        $checks = $peb['checks'];
        $fail = array();
        $cmp = 0;

        if ($npe) {
            $N = $npe['header'];
            $H['npe_number']        = ($N['npe_number'] !== '') ? $N['npe_number'] : null;
            $H['npe_date']          = $N['npe_date'];
            $H['npe_komoditi']      = $N['jenis_komoditi'];
            $H['npe_berat_kotor']   = $N['berat_kotor'];
            $H['npe_kemasan_jenis'] = $N['kemasan_jenis'];
            $H['npe_kemasan_qty']   = $N['kemasan_qty'];

            foreach ($npe['warnings'] as $w) {
                // Peringatan "AJU / Nomor NPE / Tanggal NPE tidak terbaca" sudah diwakili pemeriksaan di bawah
                // (AJU NPE tidak diperlukan: halaman NPE berada satu file dengan PEB-nya).
                if (strpos($w, 'AJU / Nomor NPE / Tanggal NPE') === 0) { continue; }
                $warn[] = 'NPE: ' . $w;
            }
            if ($N['aju_number'] !== '' && $H['aju_number'] !== '') {
                $cmp++;
                if ($N['aju_number'] !== $H['aju_number']) { $fail[] = 'npe_aju_vs_peb'; $warn[] = 'AJU pada NPE (' . $N['aju_number'] . ') berbeda dengan AJU PEB (' . $H['aju_number'] . ').'; }
            }
            if ($N['peb_number'] !== '' && $H['peb_number'] !== '') {
                $cmp++;
                if ($N['peb_number'] !== $H['peb_number']) { $fail[] = 'npe_nomor_peb_vs_peb'; $warn[] = 'Nomor PEB pada NPE (' . $N['peb_number'] . ') berbeda dengan Nomor PEB di dokumen PEB (' . $H['peb_number'] . ').'; }
            }
            if (!empty($N['peb_date']) && !empty($H['peb_date'])) {
                $cmp++;
                if ($N['peb_date'] !== $H['peb_date']) { $fail[] = 'npe_tgl_peb_vs_peb'; $warn[] = 'Tanggal PEB pada NPE (' . $N['peb_date'] . ') berbeda dengan Tanggal PEB (' . $H['peb_date'] . ').'; }
            }
            if ($N['berat_kotor'] !== null && !empty($H['berat_kotor'])) {
                $cmp++;
                if (abs((float) $N['berat_kotor'] - (float) $H['berat_kotor']) > 0.01) { $fail[] = 'npe_berat_vs_peb'; $warn[] = 'Berat kotor NPE (' . $N['berat_kotor'] . ') berbeda dengan PEB (' . $H['berat_kotor'] . ').'; }
            }
            if ($N['kemasan_qty'] !== null && !empty($H['kemasan_qty'])) {
                $cmp++;
                if ((int) $N['kemasan_qty'] !== (int) $H['kemasan_qty']) { $fail[] = 'npe_kemasan_vs_peb'; $warn[] = 'Jumlah kemasan NPE (' . $N['kemasan_qty'] . ') berbeda dengan PEB (' . $H['kemasan_qty'] . ').'; }
            }
            if ($N['voyage'] !== '' && !empty($H['voyage'])) {
                $cmp++;
                if (strtoupper($N['voyage']) !== strtoupper($H['voyage'])) { $fail[] = 'npe_voyage_vs_peb'; $warn[] = 'Voyage NPE (' . $N['voyage'] . ') berbeda dengan PEB (' . $H['voyage'] . ').'; }
            }
            if ($H['npe_number'] === null || empty($H['npe_date'])) {
                $warn[] = 'Halaman NPE ditemukan, tetapi Nomor/Tanggal NPE tidak terbaca lengkap. Isi atau perbaiki manual di pratinjau.';
                $fail[] = 'npe_terbaca';
            }
            // PEB yang dibaca dengan OCR hanya memuat data kunci; kolom yang kosong dilengkapi dari NPE (teks, lebih andal).
            if ($peb['source'] === 'ocr') {
                foreach (array('sarana_nama', 'voyage', 'tgl_perkiraan_ekspor', 'pelabuhan_muat_asal', 'pelabuhan_muat_ekspor',
                    'eksportir_id', 'eksportir_nama', 'ppjk_nama', 'kantor_pabean_muat') as $k) {
                    if ((!isset($H[$k]) || $H[$k] === '' || $H[$k] === null) && isset($N[$k]) && $N[$k] !== '' && $N[$k] !== null) { $H[$k] = $N[$k]; }
                }
            }
            foreach ($npe['checks']['passed'] as $c) { $checks['passed'][] = $c; }
            foreach ($npe['checks']['failed'] as $c) { $checks['failed'][] = $c; }
        }

        if ($ppb) {
            $P = $ppb['header'];
            foreach ($P as $k => $v) {
                if (strpos($k, 'ppb_') === 0) { $H[$k] = $v; }
            }
            foreach ($ppb['warnings'] as $w) { $warn[] = 'PPB: ' . $w; }
            if ($P['peb_number'] !== '' && $H['peb_number'] !== '') {
                $cmp++;
                if ($P['peb_number'] !== $H['peb_number']) { $fail[] = 'ppb_nomor_peb_vs_peb'; $warn[] = 'Nomor PEB pada PPB (' . $P['peb_number'] . ') berbeda dengan Nomor PEB di dokumen PEB (' . $H['peb_number'] . ').'; }
            }
            if (!empty($P['peb_date']) && !empty($H['peb_date'])) {
                $cmp++;
                if ($P['peb_date'] !== $H['peb_date']) { $fail[] = 'ppb_tgl_peb_vs_peb'; $warn[] = 'Tanggal PEB pada PPB (' . $P['peb_date'] . ') berbeda dengan Tanggal PEB (' . $H['peb_date'] . ').'; }
            }
            if ($P['ppb_jml_kemasan'] !== null && !empty($H['kemasan_qty'])) {
                $cmp++;
                if ((int) $P['ppb_jml_kemasan'] !== (int) $H['kemasan_qty']) { $fail[] = 'ppb_kemasan_vs_peb'; $warn[] = 'Jumlah kemasan PPB (' . $P['ppb_jml_kemasan'] . ') berbeda dengan PEB (' . $H['kemasan_qty'] . ').'; }
            }
            if ($H['ppb_number'] === '' || empty($H['ppb_date'])) {
                $warn[] = 'Halaman PPB ditemukan, tetapi Nomor/Tanggal PPB tidak terbaca lengkap. Isi atau perbaiki manual di pratinjau.';
                $fail[] = 'ppb_terbaca';
            } else {
                // PPB = ada pemeriksaan fisik -> jalur MERAH, tanggal penetapan = tanggal PPB.
                $H['jalur'] = 'MERAH';
                $H['jalur_date'] = $H['ppb_date'];
            }
            foreach ($ppb['checks']['passed'] as $c) { $checks['passed'][] = $c; }
            foreach ($ppb['checks']['failed'] as $c) { $checks['failed'][] = $c; }
        }

        $checks['failed'] = array_merge($checks['failed'], $fail);
        if ($cmp > 0 && !$fail) { $checks['passed'][] = 'dokumen_cocok_peb'; }
        $checks['passed'] = array_values(array_unique($checks['passed']));
        $checks['failed'] = array_values(array_unique($checks['failed']));

        $confidence = $peb['confidence'];
        if ($confidence === 'high' && $fail) { $confidence = 'medium'; }
        // Ada bagian yang dibaca dengan OCR (PEB, NPE, atau PPB) -> seluruh hasil ditandai OCR dan keyakinan rendah,
        // supaya pengguna wajib mencocokkan dengan dokumen asli. Halaman mana yang di-OCR ada di 'ocr_pages'.
        if (($npe && $npe['source'] === 'ocr') || ($ppb && $ppb['source'] === 'ocr')) {
            $peb['source'] = 'ocr';
            $confidence = 'low';
        }

        $peb['header']     = $H;
        $peb['warnings']   = $warn;
        $peb['checks']     = $checks;
        $peb['confidence'] = $confidence;
        $peb['combined']   = true;
        $peb['combined_pages'] = $pages;
        $peb['npe'] = $npe ? array('confidence' => $npe['confidence'], 'header' => $npe['header'], 'checks' => $npe['checks'], 'warnings' => $npe['warnings']) : null;
        $peb['ppb'] = $ppb ? array('confidence' => $ppb['confidence'], 'header' => $ppb['header'], 'checks' => $ppb['checks'], 'warnings' => $ppb['warnings']) : null;
        return $peb;
    }

    /**
     * Tebak nomor HBL dari nama file, mis. "PEB_JMYPKG-GCL2614958.pdf" => "JMYPKG-GCL2614958".
     */
    public function hbl_from_filename($name)
    {
        if (preg_match('/([A-Z]{4,8}-[A-Z]{2,5}\d{5,})/i', (string) $name, $m)) {
            return strtoupper($m[1]);
        }
        return '';
    }

    /* =====================================================================
     *  EKSTRAKSI (pdftotext / OCR)
     * ===================================================================== */

    protected function ocr_text($pdf_path)
    {
        $dir = $this->make_tmp_dir();
        if ($dir === null) {
            return $this->fail('Tidak bisa membuat folder sementara untuk OCR.');
        }
        try {
            $r = $this->run(array($this->cfg['pdftoppm'], '-r', (string) $this->cfg['ocr_dpi'], '-png',
                '-l', (string) $this->cfg['ocr_max_pages'], $pdf_path, $dir . '/p'));
            if (!$r['spawned']) {
                return $this->fail('PDF ini hasil scan, tetapi pdftoppm tidak ditemukan sehingga OCR tidak bisa dijalankan.');
            }
            if ($r['timed_out']) {
                return $this->fail('PDF ini hasil scan dan perenderan halaman untuk OCR terlalu lama (' . round($r['secs']) . ' detik). Isi PEB secara manual.');
            }
            $imgs = glob($dir . '/p-*.png');
            sort($imgs);
            if (!$imgs) {
                return $this->fail('Gagal merender halaman PDF untuk OCR.');
            }
            $all = '';
            foreach ($imgs as $img) {
                $o = $this->run(array($this->cfg['tesseract'], $img, 'stdout', '-l', 'eng', '--psm', '6',
                    '-c', 'preserve_interword_spaces=1'), array('OMP_THREAD_LIMIT' => '1'));
                if (!$o['spawned']) {
                    return $this->fail('PDF ini hasil scan, tetapi tesseract-ocr tidak terpasang di server sehingga tidak bisa dibaca otomatis. '
                        . 'Isi data PEB secara manual atau minta file PDF asli dari CEISA (bukan scan).');
                }
                if ($o['timed_out']) {
                    return $this->fail('PDF ini hasil scan dan OCR tidak selesai dalam ' . round($o['secs'])
                        . ' detik (server terlalu lambat untuk OCR). Isi PEB secara manual atau unggah PDF asli dari CEISA (bukan scan).');
                }
                $all .= $o['out'] . "\n\f\n";
            }
            return array('ok' => true, 'text' => $all, 'source' => 'ocr', 'pages' => count($imgs));
        } finally {
            foreach ((array) glob($dir . '/*') as $f) { @unlink($f); }
            @rmdir($dir);
        }
    }

    /**
     * Jenis dokumen dari judul halaman. $ocr = true: judul hasil OCR yang boleh salah baca beberapa huruf.
     * @return string|null 'PEB' | 'NPE' | 'PPB' | null
     */
    protected function page_kind($page, $ocr = false)
    {
        if (preg_match('/NOTA\s+PELAYANAN\s+EKSPOR/i', $page))              { return 'NPE'; }
        if (preg_match('/PEMBERITAHUAN\s+PEMERIKSAAN\s+BARANG/i', $page))   { return 'PPB'; }
        if (preg_match('/PEMBERITAHUAN\s+EKSPOR\s+BARANG/i', $page))        { return 'PEB'; }
        if ($ocr) {
            if (preg_match('/PELAYANAN\s+EKSPOR|NOTA\s+PELAY/i', $page))    { return 'NPE'; }
            if (preg_match('/PEMERIKSAAN\s+BARANG/i', $page))                { return 'PPB'; }
            if (preg_match('/PEMBER\w{3,10}\s+EKSPOR|EKSPOR\s+BARANG|\bBC\s*3[.,]\s?0\b/i', $page)) { return 'PEB'; }
        }
        return null;
    }

    /** @return string|null 'PEB' | 'NPE' | 'PPB' | null */
    public function detect_doc_type($text, $ocr = false)
    {
        $text = (string) $text;
        if (strlen(trim($text)) < 300) { return null; }
        return $this->page_kind($text, $ocr);
    }

    /* ---------------------------------------------------------------------
     *  Halaman yang teksnya tidak terbaca di tengah PDF (OCR per halaman)
     * --------------------------------------------------------------------- */

    /**
     * Halaman dianggap terbaca bila memuat cukup kata baku formulir CEISA. Teks rusak (font tanpa pemetaan karakter)
     * berisi angka/simbol acak dan hampir tidak punya kata utuh.
     */
    protected function page_readable($page)
    {
        static $words = array('nomor', 'tanggal', 'kantor', 'pengajuan', 'pemberitahuan', 'dokumen', 'nama', 'alamat', 'ekspor',
            'barang', 'jumlah', 'berat', 'jenis', 'nota', 'pelayanan', 'lembar', 'pabean', 'identitas', 'negara', 'kemasan',
            'pelabuhan', 'invoice', 'pemeriksaan');
        if (!preg_match_all('/\b[a-z]{3,}\b/i', (string) $page, $m)) { return false; }
        $hit = array();
        foreach ($m[0] as $w) {
            $w = strtolower($w);
            if (in_array($w, $words, true)) { $hit[$w] = true; }
        }
        return count($hit) >= 3;
    }

    /** Nomor halaman (mulai 1) yang teks bacanya rusak/kosong tetapi isinya ada (bukan halaman putih). */
    protected function find_gap_pages($pdf_path, $text)
    {
        $pages = explode("\f", str_replace(array("\r\n", "\r"), "\n", (string) $text));
        if ($pages && trim((string) end($pages)) === '') { array_pop($pages); }   // pdftotext menutup halaman terakhir dengan \f
        $gap = array();
        foreach ($pages as $i => $p) {
            $n = $i + 1;
            if ($this->page_readable($p)) { continue; }
            $len = strlen(preg_replace('/\s+/', '', $p));
            if ($len >= 100 || $this->page_has_ink($pdf_path, $n)) { $gap[] = $n; }   // teks banyak tapi rusak, atau tanpa teks namun ada gambar/tulisan
        }
        return $gap;
    }

    /** Apakah halaman memuat sesuatu yang tergambar (render kecil, abu-abu)? Halaman putih tidak perlu di-OCR. */
    protected function page_has_ink($pdf_path, $n)
    {
        $dir = $this->make_tmp_dir();
        if ($dir === null) { return false; }
        try {
            $r = $this->run(array($this->cfg['pdftoppm'], '-r', '40', '-gray', '-f', (string) $n, '-l', (string) $n, '-singlefile', $pdf_path, $dir . '/i'), array(), 15);
            $f = $dir . '/i.pgm';
            if (!$r['spawned'] || !is_file($f)) { return false; }
            $d = (string) file_get_contents($f);
            if (!preg_match('/^P5\s+(\d+)\s+(\d+)\s+(\d+)\s/', $d, $m)) { return false; }
            $px = substr($d, strlen($m[0]));
            if ($px === '' || $px === false) { return false; }
            $light = strlen(preg_replace('/[\x00-\xE5]/', '', $px));    // piksel hampir putih
            return (strlen($px) - $light) >= 20;
        } finally {
            foreach ((array) glob($dir . '/*') as $f) { @unlink($f); }
            @rmdir($dir);
        }
    }

    /**
     * OCR hanya untuk halaman $gap, halaman lain tetap memakai teks PDF.
     * @return array ['ok','text','source'=>'text','pages','ocr_pages'] atau ['ok'=>false,'error']
     */
    protected function fill_gaps_with_ocr($pdf_path, $text, array $gap)
    {
        $pages = explode("\f", str_replace(array("\r\n", "\r"), "\n", (string) $text));
        $trail = (trim((string) end($pages)) === '');
        if ($trail) { array_pop($pages); }

        $todo = array_slice($gap, 0, max(1, (int) $this->cfg['ocr_gap_pages']));
        $label = implode(', ', $gap);
        $dir = $this->make_tmp_dir();
        if ($dir === null) {
            return $this->fail('Tidak bisa membuat folder sementara untuk OCR.');
        }
        $done = array();
        try {
            foreach ($todo as $n) {
                $r = $this->run(array($this->cfg['pdftoppm'], '-r', (string) $this->cfg['ocr_dpi'], '-png', '-f', (string) $n, '-l', (string) $n,
                    $pdf_path, $dir . '/g'));
                $imgs = $r['spawned'] ? glob($dir . '/g-*.png') : array();
                if (!$r['spawned'] || $r['timed_out'] || !$imgs) {
                    return $this->fail($this->gap_message($label, $r['timed_out'] ? 'perenderan halaman terlalu lama' : 'halaman tidak bisa dirender (pdftoppm)'));
                }
                $o = $this->run(array($this->cfg['tesseract'], $imgs[0], 'stdout', '-l', 'eng', '--psm', '6',
                    '-c', 'preserve_interword_spaces=1'), array('OMP_THREAD_LIMIT' => '1'));
                foreach ($imgs as $im) { @unlink($im); }
                if (!$o['spawned']) {
                    return $this->fail($this->gap_message($label, 'tesseract-ocr tidak terpasang di server'));
                }
                if ($o['timed_out']) {
                    return $this->fail($this->gap_message($label, 'OCR tidak selesai dalam ' . round($o['secs']) . ' detik'));
                }
                $pages[$n - 1] = $o['out'];
                $done[] = $n;
            }
        } finally {
            foreach ((array) glob($dir . '/*') as $f) { @unlink($f); }
            @rmdir($dir);
        }
        // Halaman rusak yang tidak sempat di-OCR (melewati batas) dikosongkan, supaya teks sampahnya tidak ikut terbaca.
        foreach ($gap as $n) {
            if (!in_array($n, $done, true)) { $pages[$n - 1] = ''; }
        }
        // OCR harus mengenali minimal satu halaman sebagai dokumen PEB/NPE/PPB; bila tidak, hasilnya tidak berguna.
        $known = false;
        foreach ($done as $n) {
            if ($this->page_kind($pages[$n - 1], true) !== null) { $known = true; break; }
        }
        if (!$known) {
            return $this->fail($this->gap_message($label, 'hasil OCR tidak dikenali sebagai dokumen PEB/NPE'));
        }
        $out = implode("\f", $pages) . "\f";
        return array('ok' => true, 'text' => $out, 'source' => 'text', 'pages' => count($pages), 'ocr_pages' => $done);
    }

    protected function gap_message($pages_label, $reason)
    {
        return 'Halaman ' . $pages_label . ' pada PDF ini tidak punya teks yang bisa dibaca komputer (biasanya PEB hasil "print to PDF" / cetak ulang), '
            . 'dan OCR-nya gagal: ' . $reason . '. Gunakan PDF asli dari CEISA atau isi data PEB secara manual.';
    }

    protected function make_tmp_dir()
    {
        $base = rtrim(sys_get_temp_dir(), '/\\');
        for ($i = 0; $i < 5; $i++) {
            $d = $base . '/peb_' . bin2hex(random_bytes(6));
            if (@mkdir($d, 0700)) { return $d; }
        }
        return null;
    }

    /** Sisa detik dari batas waktu dokumen ini (null = tanpa batas). */
    protected function time_left()
    {
        return $this->deadline === null ? null : max(0.0, $this->deadline - microtime(true));
    }

    /** Folder HOME/cache yang pasti bisa ditulis, supaya poppler/fontconfig tidak membangun ulang cache font di setiap proses. */
    protected function home_dir()
    {
        $d = $this->cfg['home'] !== '' ? rtrim($this->cfg['home'], '/\\') : rtrim(sys_get_temp_dir(), '/\\') . '/peb_home';
        if (!is_dir($d)) { @mkdir($d, 0700, true); }
        return $d;
    }

    /**
     * Jalankan perintah eksternal TANPA shell (argumen array => tidak bisa disisipi perintah).
     *
     * Output ditulis ke file sementara (bukan pipe) sehingga tidak ada risiko buntu karena buffer pipe
     * penuh dan berjalan sama di Linux maupun Windows; proses dipantau dengan polling dan DIBUNUH bila
     * melewati batas waktu (per perintah, dan sisa waktu dokumen).
     *
     * @return array ['spawned','code','out','err','timed_out','secs']
     */
    protected function run(array $argv, array $env = array(), $timeout = null)
    {
        $res = array('spawned' => false, 'code' => -1, 'out' => '', 'err' => '', 'timed_out' => false, 'secs' => 0.0);
        $limit = $timeout !== null ? (float) $timeout : (float) $this->cfg['timeout'];
        $left = $this->time_left();
        if ($left !== null) { $limit = min($limit, max(3.0, $left)); }

        $tmp  = rtrim(sys_get_temp_dir(), '/\\');
        $outf = tempnam($tmp, 'pebo');
        $errf = tempnam($tmp, 'pebe');
        if ($outf === false || $errf === false) { return $res; }

        $home = $this->home_dir();
        $envp = array_merge(is_array(getenv()) ? getenv() : array(), array(
            'HOME' => $home, 'XDG_CACHE_HOME' => $home . '/cache', 'XDG_CONFIG_HOME' => $home . '/config',
        ), $env);
        foreach (array('cache', 'config') as $sub) { if (!is_dir($home . '/' . $sub)) { @mkdir($home . '/' . $sub, 0700, true); } }

        $desc = array(0 => array('pipe', 'r'), 1 => array('file', $outf, 'w'), 2 => array('file', $errf, 'w'));
        $proc = @proc_open($argv, $desc, $pipes, null, $envp, array('bypass_shell' => true));
        if (!is_resource($proc)) {
            @unlink($outf); @unlink($errf);
            return $res;
        }
        if (isset($pipes[0]) && is_resource($pipes[0])) { fclose($pipes[0]); }   // stdin langsung EOF

        $start = microtime(true);
        $exit = null;
        while (true) {
            $st = proc_get_status($proc);
            if (!$st['running']) { $exit = $st['exitcode']; break; }
            if (microtime(true) - $start > $limit) {
                proc_terminate($proc, 9);
                $res['timed_out'] = true;
                break;
            }
            usleep(25000);
        }
        $closed = proc_close($proc);
        if ($exit === null || $exit < 0) { $exit = $closed; }

        $res['secs']    = microtime(true) - $start;
        $res['code']    = (int) $exit;
        $res['out']     = (string) @file_get_contents($outf, false, null, 0, 20971520);
        $res['err']     = (string) @file_get_contents($errf, false, null, 0, 65536);
        $res['spawned'] = ($exit !== 127);
        @unlink($outf); @unlink($errf);
        return $res;
    }

    /* =====================================================================
     *  DIAGNOSA SERVER
     * ===================================================================== */

    /**
     * Periksa alat bantu (versi + lama jalan) dan uji baca PDF kecil. Dipakai halaman
     * pricelist/peb_document/diagnose. $warmup=true memberi waktu lebih lama (cache font pertama).
     */
    public function diagnose($warmup = false)
    {
        $out = array('tools' => array());
        $tools = array('pdftotext' => '-v', 'pdftoppm' => '-v', 'tesseract' => '--version');
        foreach ($tools as $key => $flag) {
            $r = $this->run(array($this->cfg[$key], $flag), array(), 15);
            $line = trim(strtok($r['out'] !== '' ? $r['out'] : $r['err'], "\n"));
            $out['tools'][$key] = array(
                'path' => $this->cfg[$key], 'found' => $r['spawned'], 'timed_out' => $r['timed_out'],
                'secs' => round($r['secs'], 2), 'version' => $line,
            );
        }
        // Uji baca PDF kecil (font standar non-embedded => memicu inisialisasi font seperti PDF CEISA).
        $pdf = $this->tiny_pdf('PEMBERITAHUAN EKSPOR BARANG SELFTEST');
        $f = tempnam(sys_get_temp_dir(), 'pebt');
        file_put_contents($f, $pdf);
        $r = $this->run(array($this->cfg['pdftotext'], '-layout', '-enc', 'UTF-8', $f, '-'), array(), $warmup ? 150 : 25);
        @unlink($f);
        $out['selftest_pdftotext'] = array(
            'ok' => ($r['spawned'] && !$r['timed_out'] && strpos($r['out'], 'SELFTEST') !== false),
            'timed_out' => $r['timed_out'], 'secs' => round($r['secs'], 2), 'exit_code' => $r['code'],
            'stderr' => substr(trim($r['err']), 0, 300),
        );
        $out['home_dir'] = $this->home_dir();
        $out['home_writable'] = is_writable($this->home_dir());
        return $out;
    }

    /** PDF 1 halaman minimal (teks Helvetica standar) untuk uji. */
    protected function tiny_pdf($text)
    {
        $stream = "BT /F1 12 Tf 72 720 Td (" . str_replace(array('\\', '(', ')'), array('\\\\', '\\(', '\\)'), $text) . ") Tj ET";
        $objs = array(
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
            "<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        );
        $pdf = "%PDF-1.4\n";
        $off = array();
        foreach ($objs as $i => $o) {
            $off[$i + 1] = strlen($pdf);
            $pdf .= ($i + 1) . " 0 obj\n" . $o . "\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objs) + 1) . "\n0000000000 65535 f \n";
        foreach ($off as $o) { $pdf .= sprintf("%010d 00000 n \n", $o); }
        $pdf .= "trailer\n<< /Size " . (count($objs) + 1) . " /Root 1 0 R >>\nstartxref\n" . $xref . "\n%%EOF\n";
        return $pdf;
    }

    protected function fail($msg)
    {
        return array('ok' => false, 'error' => $msg);
    }

    /* =====================================================================
     *  PARSER: sel "label : nilai"
     * ===================================================================== */

    /**
     * Pecah satu baris layout menjadi sel2 yang dipisah >=3 spasi, lalu gabungkan
     * potongan ": nilai" ke label di sebelah kirinya.
     * @return array [['pos'=>int,'text'=>string], ...]
     */
    protected function line_cells($line)
    {
        $line = rtrim($line);
        if ($line === '') { return array(); }
        $parts = preg_split('/ {3,}/', $line, -1, PREG_SPLIT_OFFSET_CAPTURE | PREG_SPLIT_NO_EMPTY);
        $out = array();
        foreach ($parts as $p) {
            $t = $p[0];
            $pos = $p[1];
            $trim = ltrim($t);
            $pos += strlen($t) - strlen($trim);
            $t = rtrim($trim);
            if ($t === '') { continue; }
            if ($t[0] === ':' && $out) {
                $out[count($out) - 1]['text'] .= ' ' . $t;
            } else {
                $out[] = array('pos' => $pos, 'text' => $t);
            }
        }
        return $out;
    }

    protected function build_cells(array $lines)
    {
        $cells = array();
        foreach ($lines as $i => $line) {
            foreach ($this->line_cells($line) as $c) {
                if (preg_match('/^(.*?)\s*:\s*(.*)$/', $c['text'], $m)) {
                    $cells[] = array('label' => trim($m[1]), 'value' => trim($m[2]), 'line' => $i, 'pos' => $c['pos']);
                }
            }
        }
        return $cells;
    }

    protected function cell_find(array $cells, $labelRegex)
    {
        foreach ($cells as $c) {
            if (preg_match($labelRegex, $c['label'])) { return $c; }
        }
        return null;
    }

    protected function cell_val(array $cells, $labelRegex)
    {
        $c = $this->cell_find($cells, $labelRegex);
        if (!$c) { return ''; }
        $v = trim($c['value']);
        return ($v === '-' ) ? '' : $v;
    }

    protected function doc_no_tgl($text, $labelRegex)
    {
        if (preg_match('/' . $labelRegex . '\s*:\s*(?:No\.?\s*)?(.+?)\s+tgl\.?\s*(\d{2}-\d{2}-\d{4})/i', $text, $m)) {
            return array(trim($m[1]), $this->dmy($m[2]));
        }
        return array('', null);
    }

    /* =====================================================================
     *  PARSER: hasil OCR (scan) - hanya field kunci, selalu confidence 'low'
     * ===================================================================== */

    protected function parse_ocr_lenient($type, $text, $why = 'scan')
    {
        $warn = array();
        $H = ($type === 'NPE') ? $this->empty_npe_header() : $this->empty_peb_header();

        // AJU: 26 karakter (6 digit kantor + 6 alnum + tanggal 8 digit + 6 digit urut)
        $re = '/\b(\d{6}[A-Z0-9]{6}20\d{2}(?:0[1-9]|1[0-2])(?:0[1-9]|[12]\d|3[01])\d{6})\b/';
        if (preg_match($re, $text, $m)) {
            $H['aju_number'] = $m[1];
        } elseif (preg_match('/Nomor\s+Pengajuan[^A-Z0-9\n]{0,80}([A-Z0-9]{26})\b/', $text, $m)) {
            $map = array('O' => '0', 'Q' => '0', 'D' => '0', 'I' => '1', 'L' => '1', 'S' => '5', 'B' => '8', 'Z' => '2', 'G' => '6');
            $t = $m[1];
            $fixed = strtr(substr($t, 0, 6), $map) . substr($t, 6, 6) . strtr(substr($t, 12), $map);
            if (preg_match($re, $fixed)) {
                $H['aju_number'] = $fixed;
                $warn[] = 'AJU diperbaiki otomatis dari hasil OCR; pastikan sama dengan dokumen asli.';
            }
        }

        if ($type === 'NPE') {
            if (preg_match('/Nomor\s*[:;]\s*(\d{5,8}\s*\/\s*[A-Z]{1,4}\s*\/\s*[A-Z0-9. ]{2,14}?\s*\/\s*\d{4})/', $text, $m)) {
                $H['npe_number'] = strtoupper(preg_replace('/\s+/', '', $m[1]));
                if (preg_match('/' . preg_quote($m[1], '/') . '.{0,80}?(\d{2}-\d{2}-\d{4})/s', $text, $d)) { $H['npe_date'] = $this->dmy($d[1]); }
            }
            if (preg_match('/Nomor\s+Pend\w*\s+PEB[^\n]{0,70}?\b(\d{6})\b/i', $text, $m)) { $H['peb_number'] = $m[1]; }
            $conf = 'low';
            $warn[] = ($why === 'gap')
                ? 'Halaman NPE pada file ini tidak punya teks yang bisa dibaca komputer, sehingga dibaca dengan OCR. Cocokkan semua angka dengan dokumen asli sebelum menyimpan.'
                : 'Dokumen NPE ini hasil SCAN (OCR). Cocokkan semua angka dengan dokumen asli sebelum menyimpan.';
            if ($H['aju_number'] === '' || $H['npe_number'] === '' || empty($H['npe_date'])) {
                $warn[] = 'AJU / Nomor NPE / Tanggal NPE tidak terbaca lengkap. Isi atau perbaiki manual.';
            }
            return array('ok' => true, 'doc_type' => 'NPE', 'parser' => self::PARSER_VERSION, 'source' => 'ocr',
                'confidence' => $conf, 'header' => $H, 'packages' => array(), 'items' => array(), 'documents' => array(),
                'checks' => array('passed' => array(), 'failed' => array('ocr')), 'warnings' => $warn);
        }

        if (preg_match('/Nomor\s+Pend\w*[^\n]{0,70}?\b(\d{6})\b/i', $text, $m, PREG_OFFSET_CAPTURE)) {
            $H['peb_number'] = $m[1][0];
            if (preg_match('/(\d{2}-\d{2}-\d{4})/', substr($text, $m[1][1], 600), $d)) { $H['peb_date'] = $this->dmy($d[1]); }
        }
        if (preg_match('/KATEGORI\s+EKSPOR[^A-Za-z0-9\n]{0,60}([A-Z][A-Z \/\(\)]{2,60})/', $text, $m)) { $H['kategori_ekspor'] = trim($m[1]); }
        if (preg_match('/Pen\w+an\s+Bara\w*[^A-Za-z0-9\n]{0,60}(CIF|CFR|CNF|FOB|EXW|FCA|CPT|CIP|DAP|DPU|DDP|FAS|CNI)\b/', $text, $m)) { $H['incoterm'] = $m[1]; }
        if (preg_match('/Valuta[^A-Za-z0-9\n]{0,60}([A-Z ]{2,25})\(([A-Z]{3})\)/', $text, $m)) { $H['valuta_nama'] = trim($m[1]); $H['valuta_kode'] = $m[2]; }
        if (preg_match('/Jumlah\s+Nilai\s+Ekspor[^\n]{0,70}?\b(\d[\d,]*\.\d{2})\b/', $text, $m)) { $H['nilai_ekspor'] = $this->num($m[1]); }
        if (preg_match('/Berat\s+Kotor[^\n]{0,50}?\b(\d[\d,]*\.\d+)\b/', $text, $m)) { $H['berat_kotor'] = $this->num($m[1]); }
        if (preg_match('/Berat\s+Bersih[^\n]{0,50}?\b(\d[\d,]*\.\d+)\b/', $text, $m)) { $H['berat_bersih'] = $this->num($m[1]); }
        if (preg_match('/Nilai\s+Tukar\s+Mata\s+Uang[^\n]{0,70}?Rp\s*(\d[\d,]*\.\d+)/', $text, $m)) { $H['ndpbm'] = $this->num($m[1]); }
        if (preg_match('/Merek\s+Kemasan\W{0,6}\s*(\d[\d,]*)\s+([A-Z][A-Z ,\-\/]+?)\s*[\/!|]\s*(.*)$/mi', $text, $m)) {
            $H['kemasan_qty']   = (int) str_replace(',', '', $m[1]);
            $H['kemasan_jenis'] = trim($m[2]);
            $mk = trim($m[3]);
            $H['kemasan_marking'] = ($mk === '' || $mk === '-') ? null : $mk;
        } elseif (preg_match('/^\W*(\d[\d,]*)\s+([A-Z][A-Z ,\-]+?)\s*[\/!|]\s*(.*)$/m', $text, $m)) {
            $H['kemasan_qty']   = (int) str_replace(',', '', $m[1]);
            $H['kemasan_jenis'] = trim($m[2]);
            $mk = trim($m[3]);
            $H['kemasan_marking'] = ($mk === '' || $mk === '-') ? null : $mk;
        } elseif (preg_match('/Merek\s+Kemasan[^\n]*\n[^\n]*?\b(\d[\d,]*)\s+([A-Z][A-Z ,\-]{1,30}?)\s*[\/!|]\s*([^\n]*)/', $text, $m)) {
            $H['kemasan_qty']   = (int) str_replace(',', '', $m[1]);
            $H['kemasan_jenis'] = trim($m[2]);
            $mk = trim($m[3]);
            $H['kemasan_marking'] = ($mk === '' || $mk === '-') ? null : $mk;
        }
        if (preg_match('/Invoice[^A-Za-z0-9\n]{0,40}No\.?\s*([A-Z0-9][A-Z0-9\.\/\-]{2,30})/i', $text, $m)) { $H['invoice_no'] = $m[1]; }

        // Data pelayaran / pelabuhan (berguna untuk mencocokkan booking). Nilai diambil sampai akhir kolom (>=3 spasi atau akhir baris).
        $col = function ($labelRe) use ($text) {
            return preg_match('/' . $labelRe . '[^A-Za-z0-9\n]{0,40}([A-Z0-9][A-Z0-9 .,\-\/]{1,50}?)(?=[ \t]{3,}|[ \t]*$)/m', $text, $mm) ? trim($mm[1]) : '';
        };
        if (preg_match('/No\.\s*Pengangkut[^\n:]*:\s*([A-Z0-9][A-Z0-9\-\/]{1,15})\b/', $text, $m)) { $H['voyage'] = $m[1]; }
        if (preg_match('/Tanggal\s+Perkiraan\s+Ekspor[^\n:]*:\s*(\d{2}-\d{2}-\d{4})/', $text, $m)) { $H['tgl_perkiraan_ekspor'] = $this->dmy($m[1]); }
        $H['pelabuhan_muat_asal']   = $col('Pelabuhan\s+Muat\s+Asal');
        $H['pelabuhan_muat_ekspor'] = $col('Pelabuhan\s+Muat\s+Ekspor');
        $H['pelabuhan_tujuan']      = $col('Pelabuhan\s+Tujuan');
        $H['negara_tujuan']         = $col('Negara\s+Tujuan\s+Ekspor');

        $warn[] = ($why === 'gap'
                ? 'Halaman PEB pada file ini tidak punya teks yang bisa dibaca komputer (biasanya PEB hasil "print to PDF" / cetak ulang), sehingga dibaca dengan OCR. '
                : 'Dokumen ini hasil SCAN dan dibaca dengan OCR. ')
              . 'OCR bisa salah baca angka (mis. 9 terbaca 3). '
              . 'Hanya data kunci yang dicoba dibaca; daftar barang (HS Code, dsb) TIDAK dibaca. Cocokkan semuanya dengan dokumen asli.';
        if ($H['aju_number'] === '' || $H['peb_number'] === '' || empty($H['peb_date'])) {
            $warn[] = 'AJU / Nomor PEB / Tanggal PEB tidak terbaca lengkap. Isi atau perbaiki manual.';
        }
        return array('ok' => true, 'doc_type' => 'PEB', 'parser' => self::PARSER_VERSION, 'source' => 'ocr',
            'confidence' => 'low', 'header' => $H, 'packages' => array(), 'items' => array(), 'documents' => array(),
            'checks' => array('passed' => array(), 'failed' => array('ocr', 'item_kosong')), 'warnings' => $warn);
    }

    protected function empty_peb_header()
    {
        $keys = array('aju_number','peb_number','kantor_pabean_muat','kantor_pabean_ekspor','jenis_ekspor','kategori_ekspor',
            'cara_perdagangan','cara_pembayaran','eksportir_id','eksportir_nama','pemilik_nama','ppjk_nama','pembeli_nama',
            'pembeli_negara','penerima_nama','penerima_negara','cara_pengangkutan','sarana_bendera','sarana_nama','voyage',
            'pelabuhan_muat_asal','pelabuhan_muat_ekspor','pelabuhan_tujuan','negara_tujuan','invoice_no','packing_list_no',
            'dok_lainnya','lokasi_pemeriksaan','kantor_pemeriksaan','incoterm','bank_devisa','valuta_nama','valuta_kode',
            'kemasan_jenis','hs_codes','bl_number');
        $H = array();
        foreach ($keys as $k) { $H[$k] = ''; }
        foreach (array('peb_date','tgl_perkiraan_ekspor','invoice_date','packing_list_date','bl_date','nilai_ekspor','biaya_pengangkutan',
            'asuransi','nilai_maklon','berat_kotor','berat_bersih','ndpbm','nilai_bea_keluar','pph22_ekspor','dana_perkebunan',
            'kemasan_marking','npe_number','npe_date','jalur') as $k) { $H[$k] = null; }
        $H['jumlah_peti_kemas'] = 0; $H['kemasan_qty'] = 0; $H['jumlah_jenis_barang'] = 0; $H['jumlah_jenis_barang_dokumen'] = 0;
        return $H;
    }

    protected function empty_npe_header()
    {
        $H = array();
        foreach (array('aju_number','npe_number','peb_number','jenis_komoditi','kantor_pabean_muat','eksportir_id','eksportir_nama',
            'ppjk_nama','sarana_nama','voyage','pelabuhan_muat_asal','pelabuhan_muat_ekspor','berat_kotor_satuan','kemasan_jenis') as $k) { $H[$k] = ''; }
        foreach (array('npe_date','peb_date','tgl_perkiraan_ekspor','berat_kotor','kemasan_qty') as $k) { $H[$k] = null; }
        return $H;
    }

    /* =====================================================================
     *  PARSER: NPE (Nota Pelayanan Ekspor)
     * ===================================================================== */

    protected function parse_npe(array $lines, $text, $source)
    {
        $cells = $this->build_cells($lines);
        $H = array();
        $warn = array();

        $H['aju_number'] = $this->upper($this->clean_id($this->cell_val($cells, '/^Nomor Pengajuan$/i')));
        if ($H['aju_number'] === '' && preg_match('/Nomor Pengajuan[^0-9A-Z]{0,20}([0-9]{6}[A-Z0-9]{6}[0-9]{14})/', $text, $m)) {
            $H['aju_number'] = $m[1];
        }

        // "Nomor : 639160/PM/KPU.1/2026      Tanggal : 08-10-2026"
        $H['npe_number'] = ''; $H['npe_date'] = null;
        $no = $this->cell_find($cells, '/^Nomor$/i');
        if ($no) {
            $H['npe_number'] = $this->upper($no['value']);
            foreach ($cells as $c) {
                if ($c['line'] === $no['line'] && preg_match('/^Tanggal$/i', $c['label']) && $this->dmy($c['value'])) {
                    $H['npe_date'] = $this->dmy($c['value']); break;
                }
            }
        }
        // "Nomor Pendaftaran PEB : 639236      Tanggal : 08-10-2026"
        $H['peb_number'] = ''; $H['peb_date'] = null;
        $pb = $this->cell_find($cells, '/^Nomor Pendaftaran PEB$/i');
        if ($pb) {
            $H['peb_number'] = preg_replace('/\D/', '', $pb['value']);
            foreach ($cells as $c) {
                if ($c['line'] === $pb['line'] && preg_match('/^Tanggal$/i', $c['label']) && $this->dmy($c['value'])) {
                    $H['peb_date'] = $this->dmy($c['value']); break;
                }
            }
        }
        $H['jenis_komoditi']     = $this->cell_val($cells, '/^Jenis Komoditi$/i');
        $H['kantor_pabean_muat'] = $this->cell_val($cells, '/^1\.\s*NAMA KANTOR PABEAN PEMUATAN$/i');

        $exp = $this->cell_val($cells, '/^2\.\s*NPWP\/NAMA EKSPORTIR$/i');
        $H['eksportir_id'] = ''; $H['eksportir_nama'] = $exp;
        if (preg_match('/^(\d+)\s*\/\s*(.+)$/', $exp, $m)) { $H['eksportir_id'] = $m[1]; $H['eksportir_nama'] = trim($m[2]); }
        $ppjk = $this->cell_val($cells, '/^3\.\s*NPWP\/NAMA PPJK$/i');
        $H['ppjk_nama'] = $ppjk;
        if (preg_match('/^(\d+)\s*\/\s*(.+)$/', $ppjk, $m)) { $H['ppjk_nama'] = trim($m[2]); }

        $H['sarana_nama'] = ''; $H['voyage'] = '';
        if (preg_match('/^\s*(?:4\.\s*SARANA PENGANGKUT\s*:\s*)?a\.\s*Nama\s*:\s*(.+?)\s*$/mi', $text, $m)) { $H['sarana_nama'] = trim($m[1]); }
        if (preg_match('/b\.\s*Voyage\/Flight\/Nopol\s*:\s*(.+?)\s*$/mi', $text, $m))                     { $H['voyage'] = trim($m[1]); }
        $H['tgl_perkiraan_ekspor'] = $this->dmy($this->cell_val($cells, '/^5\.\s*TANGGAL PERKIRAAN EKSPOR$/i'));

        $H['pelabuhan_muat_asal'] = ''; $H['pelabuhan_muat_ekspor'] = '';
        if (preg_match('/a\.\s*Pelabuhan Muat Asal\s*:\s*(.+?)(?=\s{3,}|$)/mi', $text, $m))  { $H['pelabuhan_muat_asal'] = trim($m[1]); }
        if (preg_match('/b\.\s*Pelabuhan[^:\n]*Muat\s*:\s*(.+?)(?=\s{3,}|$)/mi', $text, $m))  { $H['pelabuhan_muat_ekspor'] = trim($m[1]); }

        $bk = $this->cell_val($cells, '/^7\.\s*BERAT KOTOR$/i');
        $H['berat_kotor'] = null; $H['berat_kotor_satuan'] = '';
        if (preg_match('/^([\d,]+(?:\.\d+)?)\s*([A-Z]{2,4})?$/', $bk, $m)) {
            $H['berat_kotor'] = $this->num($m[1]);
            $H['berat_kotor_satuan'] = isset($m[2]) ? $m[2] : '';
        }

        $H['kemasan_jenis'] = ''; $H['kemasan_qty'] = null;
        if (preg_match('/a\.\s*Jenis\/Merek\s*:\s*(\S.*?)\s*$/mi', $text, $m)) { $H['kemasan_jenis'] = trim($m[1]); }
        if (preg_match('/b\.\s*Jumlah\s*:\s*(\d[\d,]*)/i', $text, $m))          { $H['kemasan_qty'] = (int) str_replace(',', '', $m[1]); }

        // ---- validasi ----
        $c = array('passed' => array(), 'failed' => array());
        if ($H['aju_number'] !== '') {
            if (preg_match('/^\d{6}[A-Z0-9]{6}\d{14}$/', $H['aju_number'])) { $c['passed'][] = 'format_aju'; }
            else { $c['failed'][] = 'format_aju'; $warn[] = 'Format Nomor Pengajuan (AJU) tidak lazim: ' . $H['aju_number']; }
        }
        if ($H['npe_number'] !== '' && preg_match('/^\d{5,8}\/[A-Z]{1,4}\/[A-Z0-9.]+\/\d{4}$/', $H['npe_number'])) { $c['passed'][] = 'format_npe'; }
        elseif ($H['npe_number'] !== '') { $c['failed'][] = 'format_npe'; $warn[] = 'Format Nomor NPE tidak lazim: ' . $H['npe_number']; }

        $confidence = 'high';
        if ($source === 'ocr') {
            $confidence = 'low';
            $warn[] = 'Dokumen ini hasil SCAN dan dibaca dengan OCR. Cocokkan Nomor/Tanggal NPE dan AJU dengan dokumen asli sebelum menyimpan.';
        }
        if ($H['aju_number'] === '' || $H['npe_number'] === '' || empty($H['npe_date'])) {
            $confidence = 'low';
            $warn[] = 'AJU / Nomor NPE / Tanggal NPE tidak terbaca lengkap. Isi atau perbaiki manual.';
        }

        return array(
            'ok'         => true,
            'doc_type'   => 'NPE',
            'parser'     => self::PARSER_VERSION,
            'source'     => $source,
            'confidence' => $confidence,
            'header'     => $H,
            'packages'   => array(), 'items' => array(), 'documents' => array(),
            'checks'     => $c,
            'warnings'   => $warn,
        );
    }

    /* =====================================================================
     *  PARSER: PPB (Pemberitahuan Pemeriksaan Barang)
     *  Terbit untuk PEB yang harus diperiksa fisik (jalur MERAH). Tidak memuat
     *  Nomor Pengajuan; PEB dirujuk lewat "PEB nomor : ... tanggal ...".
     * ===================================================================== */

    protected function parse_ppb(array $lines, $text, $source)
    {
        $warn = array();
        $H = array(
            'aju_number' => '', 'ppb_number' => '', 'ppb_date' => null, 'peb_number' => '', 'peb_date' => null,
            'eksportir_id' => '', 'eksportir_nama' => '', 'ppjk_nama' => '',
            'ppb_tgl_periksa' => null, 'ppb_kantor_periksa' => '', 'ppb_lokasi_periksa' => '', 'ppb_petugas_eksportir' => '',
            'ppb_tgl_stuffing' => null, 'ppb_tempat_stuffing' => '', 'ppb_jml_kontainer' => null, 'ppb_jml_kemasan' => null,
        );

        // "Nomor : 002237/PPB/KPU.1/2026          Tanggal : 18-09-2026"
        if (preg_match('/Nomor\s*:\s*([0-9A-Z\.\-]+\/PPB\/[A-Z0-9\.\-]+\/\d{4})\s+Tanggal\s*:\s*(\d{2}-\d{2}-\d{4})/i', $text, $m)) {
            $H['ppb_number'] = $this->upper($m[1]);
            $H['ppb_date']   = $this->dmy($m[2]);
        } elseif (preg_match('/Nomor\s*:\s*([0-9A-Z\.\-]+\/PPB\/[A-Z0-9\.\-]+\/\d{4})/i', $text, $m)) {
            $H['ppb_number'] = $this->upper($m[1]);
        }
        // "...diberitahukan dengan PEB nomor : 593759 tanggal 18-09-2026, harus dilakukan"
        if (preg_match('/PEB\s+nomor\s*:?\s*(\d{3,})\s+tanggal\s*:?\s*(\d{2}-\d{2}-\d{4})/i', $text, $m)) {
            $H['peb_number'] = $m[1];
            $H['peb_date']   = $this->dmy($m[2]);
        } elseif (preg_match('/PEB\s+nomor\s*:?\s*(\d{3,})/i', $text, $m)) {
            $H['peb_number'] = $m[1];
        }

        // Para pihak (blok "EKSPORTIR" dan "PPJK")
        foreach ($lines as $i => $l) {
            if (preg_match('/^\s*EKSPORTIR\s*$/', $l) || preg_match('/^\s*PPJK\s*$/', $l)) {
                $isExp = (strpos($l, 'EKSPORTIR') !== false);
                for ($k = $i + 1; $k <= $i + 6 && $k < count($lines); $k++) {
                    if ($isExp && preg_match('/^\s*-\s*NPWP\s*:\s*(\d+)/', $lines[$k], $m)) { $H['eksportir_id'] = $m[1]; }
                    if (preg_match('/^\s*-\s*Nama\s*:\s*(.+?)\s*$/', $lines[$k], $m)) {
                        if ($isExp) { $H['eksportir_nama'] = trim($m[1]); } else { $H['ppjk_nama'] = trim($m[1]); }
                        break;
                    }
                }
            }
        }

        // Rincian pemeriksaan (a. - f.)
        $get = function ($re) use ($lines) {
            foreach ($lines as $i => $l) {
                if (preg_match($re, $l, $m)) {
                    $v = trim($m[1]);
                    // nilai yang terpotong ke baris berikutnya (indentasi dalam, bukan butir "x.")
                    $n = isset($lines[$i + 1]) ? $lines[$i + 1] : '';
                    if ($n !== '' && trim($n) !== '' && !preg_match('/^\s*[a-f]\.\s/', $n) && preg_match('/^\s{12,}\S/', $n)) {
                        $v .= ' ' . trim($n);
                    }
                    return $v;
                }
            }
            return '';
        };
        $H['ppb_tgl_periksa']        = $this->dmy($get('/^\s*a\.\s*Tanggal\s*:\s*(\d{2}-\d{2}-\d{4})\s*$/i'));
        $H['ppb_kantor_periksa']     = $get('/^\s*b\.\s*Kantor pabean pemeriksaan\s*:\s*(.+?)\s*$/i');
        $H['ppb_lokasi_periksa']     = $get('/^\s*c\.\s*Lokasi dan nomor telepon\s*:\s*(.+?)\s*$/i');
        $H['ppb_petugas_eksportir']  = $get('/^\s*d\.\s*Nama petugas eksportir\s*:\s*(.+?)\s*$/i');
        $stf = $get('/^\s*e\.\s*Tanggal dan tempat stuffing\s*:\s*(.+?)\s*$/i');
        if ($stf !== '') {
            if (preg_match('/^(\d{2}-\d{2}-\d{4})\s*\/\s*(.*)$/', $stf, $m)) {
                $H['ppb_tgl_stuffing'] = $this->dmy($m[1]);
                $H['ppb_tempat_stuffing'] = trim($m[2]);
            } else {
                $H['ppb_tempat_stuffing'] = $stf;
            }
        }
        $jml = $get('/^\s*f\.\s*Jumlah peti kemas\s*\/\s*kemasan\s*:\s*(.+?)\s*$/i');
        if (preg_match('/kontainer\s*:\s*(\d+)/i', $jml, $m)) { $H['ppb_jml_kontainer'] = (int) $m[1]; }
        if (preg_match('/kemasan\s*:\s*(\d+)/i', $jml, $m))   { $H['ppb_jml_kemasan'] = (int) $m[1]; }

        // ---- validasi ----
        $c = array('passed' => array(), 'failed' => array());
        if ($H['ppb_number'] !== '' && preg_match('/^\d{4,8}\/PPB\/[A-Z0-9.]+\/\d{4}$/', $H['ppb_number'])) { $c['passed'][] = 'format_ppb'; }
        elseif ($H['ppb_number'] !== '') { $c['failed'][] = 'format_ppb'; $warn[] = 'Format Nomor PPB tidak lazim: ' . $H['ppb_number']; }

        $confidence = 'high';
        if ($H['ppb_number'] === '' || empty($H['ppb_date']) || $H['peb_number'] === '') {
            $confidence = 'low';
            $warn[] = 'Nomor/Tanggal PPB atau Nomor PEB yang diperiksa tidak terbaca lengkap. Isi atau perbaiki manual.';
        }

        return array(
            'ok'         => true,
            'doc_type'   => 'PPB',
            'parser'     => self::PARSER_VERSION,
            'source'     => $source,
            'confidence' => $confidence,
            'header'     => $H,
            'packages'   => array(), 'items' => array(), 'documents' => array(),
            'checks'     => $c,
            'warnings'   => $warn,
        );
    }

    /* =====================================================================
     *  PARSER: kemasan (field 44)
     * ===================================================================== */

    protected function parse_packages(array $lines)
    {
        $out = array();
        $start = null;
        foreach ($lines as $i => $l) {
            if (preg_match('/44\.\s*Jenis,\s*Jumlah dan Merek Kemasan/i', $l)) { $start = $i; break; }
        }
        if ($start === null) { return $out; }
        for ($i = $start + 1; $i < min($start + 8, count($lines)); $i++) {
            $l = $lines[$i];
            if (preg_match('/DATA BARANG EKSPOR|45\.\s*Berat Kotor/i', $l)) { break; }
            foreach ($this->line_cells($l) as $c) {
                if (preg_match('/^(\d[\d,]*)\s+(.+?)\/(?:\s+(.*))?$/', $c['text'], $m)) {
                    $mk = isset($m[3]) ? trim($m[3]) : '';
                    $out[] = array(
                        'qty'     => (int) str_replace(',', '', $m[1]),
                        'jenis'   => trim($m[2]),
                        'marking' => ($mk === '' || $mk === '-') ? null : $mk,
                    );
                }
            }
        }
        return $out;
    }

    /* =====================================================================
     *  PARSER: barang
     * ===================================================================== */

    protected function parse_items(array $lines)
    {
        $items = array();
        $n = count($lines);
        $starts = array();
        for ($i = 0; $i < $n; $i++) {
            if (preg_match('/^\s*(\d{1,3})\s{2,}-\s*(\d{4,10})\b/', $lines[$i])) {
                $starts[] = $i;
            }
        }
        foreach ($starts as $k => $s) {
            $end = $n;
            for ($j = $s + 1; $j < $n; $j++) {
                if (in_array($j, $starts, true) || $this->is_item_terminator($lines[$j])) { $end = $j; break; }
            }
            $it = $this->parse_item_block(array_slice($lines, $s, $end - $s));
            if ($it) { $items[] = $it; }
        }
        return $items;
    }

    protected function is_item_terminator($l)
    {
        return preg_match('/^\s*55\.\s*Nilai Tukar|Eksportir\/PPJK|^\s*[A-Z][A-Z .]+,\s*\d{2}-\d{2}-\d{4}\s*$|LEMBAR LANJUTAN|Halaman ke-|Jenis barang\.?\s*Lihat lembar|^\s*G\.\s*TANDA TANGAN/i', $l) === 1;
    }

    protected function parse_item_block(array $blk)
    {
        $first = $blk[0];
        $segs = $this->raw_segments($first);

        $pos = array('hs' => null, 'izin' => null, 'he' => null, 'qty' => null, 'neg' => null, 'val' => null);
        $itemNo = null; $hs = null; $nilai = null; $he = null;
        foreach ($segs as $idx => $sg) {
            $t = $sg['text'];
            if ($itemNo === null && preg_match('/^\d{1,3}$/', $t)) { $itemNo = (int) $t; continue; }
            if ($pos['hs'] === null && preg_match('/^-\s*(\d{4,10})$/', $t, $m)) { $pos['hs'] = $sg['pos']; $hs = $m[1]; continue; }
            if ($pos['hs'] !== null && $pos['he'] === null && !preg_match('/^HE:/i', $t)) { $pos['izin'] = $sg['pos']; continue; }
            if (preg_match('/^HE:\s*(\d+)/i', $t, $m)) { $pos['he'] = $sg['pos']; $he = $m[1]; continue; }
            if ($pos['qty'] === null && $pos['he'] !== null && preg_match('/^-\s*[\d,]+(?:\.\d+)?\s*\S/', $t)) { $pos['qty'] = $sg['pos']; continue; }
            if ($pos['neg'] === null && $pos['qty'] !== null && preg_match('/^-\s*.+\([A-Z]{2}\)$/', $t)) { $pos['neg'] = $sg['pos']; continue; }
            if ($pos['neg'] !== null && preg_match('/^[\d,]+(?:\.\d+)?$/', $t)) { $nilai = $this->num($t); $pos['val'] = $sg['pos']; continue; }
        }
        if ($hs === null || $pos['he'] === null || $pos['qty'] === null) {
            return null;
        }
        if ($itemNo === null && preg_match('/^\s*(\d{1,3})\s/', $first, $m)) { $itemNo = (int) $m[1]; }

        $slice = function ($line, $from, $to) {
            if ($from === null) { return ''; }
            $from = max(0, $from - 1);
            if (strlen($line) <= $from) { return ''; }
            $s = ($to === null) ? substr($line, $from) : substr($line, $from, max(0, $to - 1 - $from));
            return trim($s);
        };

        $uraianEnd = $pos['izin'] !== null ? $pos['izin'] : $pos['he'];
        $uraianLines = array(); $izinLines = array(); $heLines = array(); $qtyLines = array(); $negLines = array();
        foreach ($blk as $ln) {
            $u = $slice($ln, $pos['hs'], $uraianEnd);
            if ($u !== '') { $uraianLines[] = $u; }
            if ($pos['izin'] !== null) {
                $z = $slice($ln, $pos['izin'], $pos['he']);
                if ($z !== '') { $izinLines[] = $z; }
            }
            $h = $slice($ln, $pos['he'], $pos['qty']);
            if ($h !== '') { $heLines[] = $h; }
            $q = $slice($ln, $pos['qty'], $pos['neg']);
            if ($q !== '') { $qtyLines[] = $q; }
            if ($pos['neg'] !== null) {
                $ng = $slice($ln, $pos['neg'], $pos['val']);
                if ($ng !== '') { $negLines[] = $ng; }
            }
        }

        // HS pada baris pertama uraian bukan bagian uraian.
        if ($uraianLines && preg_match('/^-\s*\d{4,10}$/', $uraianLines[0])) { array_shift($uraianLines); }

        // Jenis ekspor = baris terakhir "- EKSPOR BIASA".
        $jenisEkspor = null;
        if ($uraianLines && preg_match('/^-\s+([A-Z][A-Z \-]{4,})$/', end($uraianLines), $m)) {
            $jenisEkspor = trim($m[1]);
            array_pop($uraianLines);
        }
        $uraian = '';
        foreach ($uraianLines as $ul) {
            if ($uraian === '') { $uraian = $ul; continue; }
            $uraian .= (substr($uraian, -1) === '-' ? '' : ' ') . $ul;
        }
        $uraian = preg_replace('/^-\s*/', '', $uraian);

        $deskripsi = $uraian; $merk = null; $tipe = null; $ukuran = null; $kode = null;
        if (preg_match('/^(.*?),\s*Merk\s*:\s*(.*?),\s*Tipe\s*:\s*(.*?),\s*Ukuran\s*:\s*(.*?)\s*,\s*Kode\s*Barang\s*:\s*(.*)$/s', $uraian, $m)) {
            $deskripsi = trim($m[1]);
            $merk   = $this->dash_null($m[2]);
            $tipe   = $this->dash_null($m[3]);
            $ukuran = $this->dash_null($m[4]);
            $kode   = $this->dash_null($m[5]);
        }

        // Jumlah / satuan / berat / kemasan
        $qty = null; $satuan = ''; $berat = null; $kemQty = null; $kemKode = null;
        foreach ($qtyLines as $idx => $ql) {
            if ($qty === null && preg_match('/^-\s*([\d,]+(?:\.\d+)?)\s*(.*)$/', $ql, $m) && !preg_match('/^-\s*[\d,.]+\s*Kg$/i', $ql)) {
                $qty = $this->num($m[1]); $satuan = trim($m[2]); continue;
            }
            if (preg_match('/^-\s*([\d,]+(?:\.\d+)?)\s*Kg$/i', $ql, $m)) { $berat = $this->num($m[1]); continue; }
            if (preg_match('/^-\s*Kemasan\s*:\s*(\d+)\s*([A-Z0-9]{2})/i', $ql, $m)) { $kemQty = (int) $m[1]; $kemKode = strtoupper($m[2]); continue; }
            $satuan = trim($satuan . ' ' . $ql);
        }
        $satuanKode = '';
        if (preg_match_all('/\(([A-Z0-9]{2,4})\)/', $satuan, $mm)) { $satuanKode = end($mm[1]); }

        $negara = null; $negaraKode = null; $daerah = null; $daerahKode = null;
        foreach ($negLines as $nl) {
            if (preg_match('/^-\s*(.+?)\s*\(([A-Z]{2})\)$/', $nl, $m) && $negara === null) { $negara = trim($m[1]); $negaraKode = $m[2]; continue; }
            if (preg_match('/^-\s*(.+?)\s*\((\d{3,5})\)$/', $nl, $m)) { $daerah = trim($m[1]); $daerahKode = $m[2]; }
        }
        $bk = null;
        foreach ($heLines as $hl) { if (preg_match('/^BK:\s*(\d+)/i', $hl, $m)) { $bk = $m[1]; } }

        $izin = $izinLines ? preg_replace('/^-\s*/', '', implode(' ', $izinLines)) : null;

        return array(
            'item_no'       => $itemNo,
            'hs_code'       => $hs,
            'uraian'        => $deskripsi,
            'merk'          => $merk,
            'tipe'          => $tipe,
            'ukuran'        => $ukuran,
            'kode_barang'   => $kode,
            'jenis_ekspor'  => $jenisEkspor,
            'perizinan'     => $izin,
            'he'            => $he,
            'bk'            => $bk,
            'qty'           => $qty,
            'satuan'        => $satuan,
            'satuan_kode'   => $satuanKode,
            'berat_bersih'  => $berat,
            'kemasan_qty'   => $kemQty,
            'kemasan_kode'  => $kemKode,
            'negara_asal'   => $negara,
            'negara_asal_kode' => $negaraKode,
            'daerah_asal'   => $daerah,
            'daerah_asal_kode' => $daerahKode,
            'nilai_ekspor'  => $nilai,
        );
    }

    /** Pecah baris mentah jadi segmen (>=3 spasi) lengkap dengan posisi; TANPA menggabung ':' */
    protected function raw_segments($line)
    {
        $parts = preg_split('/ {3,}/', rtrim($line), -1, PREG_SPLIT_OFFSET_CAPTURE | PREG_SPLIT_NO_EMPTY);
        $out = array();
        foreach ($parts as $p) {
            $trim = ltrim($p[0]);
            $t = rtrim($trim);
            if ($t === '') { continue; }
            $out[] = array('pos' => $p[1] + strlen($p[0]) - strlen($trim), 'text' => $t);
        }
        return $out;
    }

    /* =====================================================================
     *  PARSER: dokumen pelengkap (lembar lanjutan)
     * ===================================================================== */

    protected function parse_documents(array $lines)
    {
        $docs = array();
        $n = count($lines);
        for ($i = 0; $i < $n; $i++) {
            if (!preg_match('/^\s*No\.\s+Jenis Dokumen\s+Nomor Dokumen\s+Tanggal/i', $lines[$i])) { continue; }
            $last = null;
            for ($j = $i + 1; $j < $n; $j++) {
                $l = $lines[$j];
                if (trim($l) === '') { continue; }
                if (preg_match('/Eksportir\/PPJK|^\s*[A-Z][A-Z .]+,\s*\d{2}-\d{2}-\d{4}\s*$|LEMBAR LANJUTAN/i', $l)) { break; }
                if (preg_match('/^\s*(\d{1,2})\s{2,}(.+?)\s{2,}(\S.*?)\s{2,}(\d{2}-\d{2}-\d{4})(?:\s{2,}(.*))?\s*$/', $l, $m)) {
                    $docs[] = array(
                        'no'       => (int) $m[1],
                        'jenis'    => trim($m[2]),
                        'nomor'    => trim($m[3]),
                        'tanggal'  => $this->dmy($m[4]),
                        'kantor'   => isset($m[5]) ? trim($m[5]) : '',
                    );
                    $last = count($docs) - 1;
                } elseif ($last !== null && preg_match('/^\s{6,}(\S.*)$/', $l, $m)) {
                    // jenis dokumen yang terpotong ke baris berikut
                    $docs[$last]['jenis'] .= (substr($docs[$last]['jenis'], -1) === '-' ? '' : ' ') . trim($m[1]);
                }
            }
            $i = $j;
        }
        return $docs;
    }

    /* =====================================================================
     *  VALIDASI SILANG
     * ===================================================================== */

    protected function run_checks(array $H, array $items, $source, array &$warn)
    {
        $c = array('passed' => array(), 'failed' => array());

        if ($H['aju_number'] !== '') {
            if (preg_match('/^\d{6}[A-Z0-9]{6}\d{14}$/', $H['aju_number'])) { $c['passed'][] = 'format_aju'; }
            else { $c['failed'][] = 'format_aju'; $warn[] = 'Format Nomor Pengajuan (AJU) tidak lazim (bukan 26 karakter): ' . $H['aju_number']; }
        }
        if ($H['peb_number'] !== '') {
            if (preg_match('/^\d{6}$/', $H['peb_number'])) { $c['passed'][] = 'format_peb'; }
            else { $c['failed'][] = 'format_peb'; $warn[] = 'Nomor PEB tidak 6 digit: ' . $H['peb_number']; }
        }

        if ($items) {
            $sumNilai = 0.0; $sumBerat = 0.0; $hasBerat = false;
            foreach ($items as $it) {
                $sumNilai += (float) $it['nilai_ekspor'];
                if ($it['berat_bersih'] !== null) { $sumBerat += (float) $it['berat_bersih']; $hasBerat = true; }
            }
            $c['sum_nilai_barang']  = round($sumNilai, 2);
            $c['sum_berat_barang']  = round($sumBerat, 4);

            if ($H['nilai_ekspor'] !== null) {
                if (abs($sumNilai - (float) $H['nilai_ekspor']) <= max(0.05, 0.0001 * (float) $H['nilai_ekspor'])) { $c['passed'][] = 'nilai_barang_vs_header'; }
                else { $c['failed'][] = 'nilai_barang_vs_header';
                       $warn[] = 'Total nilai barang (' . number_format($sumNilai, 2) . ') tidak sama dengan Jumlah Nilai Ekspor header (' . $H['nilai_ekspor'] . ').'; }
            }
            if ($H['berat_bersih'] !== null && $hasBerat) {
                if (abs($sumBerat - (float) $H['berat_bersih']) <= 0.05) { $c['passed'][] = 'berat_barang_vs_header'; }
                else { $c['failed'][] = 'berat_barang_vs_header';
                       $warn[] = 'Total berat bersih barang (' . $sumBerat . ') tidak sama dengan Berat Bersih header (' . $H['berat_bersih'] . ').'; }
            }
            if ($H['jumlah_jenis_barang_dokumen'] === count($items)) { $c['passed'][] = 'jumlah_item'; }
            else { $c['failed'][] = 'jumlah_item';
                   $warn[] = 'Dokumen menyebut ' . $H['jumlah_jenis_barang_dokumen'] . ' jenis barang, terbaca ' . count($items) . '. Daftar barang mungkin tidak lengkap.'; }
        } else {
            $c['failed'][] = 'item_kosong';
            $warn[] = ($source === 'ocr')
                ? 'Daftar barang (HS Code, uraian, nilai per item) tidak dapat dibaca dari dokumen scan.'
                : 'Daftar barang tidak terbaca dari dokumen.';
        }
        return $c;
    }

    /* =====================================================================
     *  UTIL
     * ===================================================================== */

    protected function upper($s) { return strtoupper(trim((string) $s)); }

    protected function clean_id($s) { return preg_replace('/[^A-Za-z0-9]/', '', (string) $s); }

    protected function dash_null($s)
    {
        $s = trim((string) $s);
        return ($s === '' || $s === '-') ? null : $s;
    }

    /** "dd-mm-yyyy" -> "yyyy-mm-dd" (null bila tidak valid) */
    protected function dmy($s)
    {
        if (preg_match('/(\d{2})-(\d{2})-(\d{4})/', (string) $s, $m) && checkdate((int) $m[2], (int) $m[1], (int) $m[3])) {
            return $m[3] . '-' . $m[2] . '-' . $m[1];
        }
        return null;
    }

    /** "1,760,413.16" / "Rp 17,594.00" / "Rp. 0" -> "1760413.16" (string desimal, null bila bukan angka) */
    protected function num($s)
    {
        $s = trim(preg_replace('/^(Rp\.?|IDR)\s*/i', '', (string) $s));
        $s = str_replace(',', '', $s);
        return preg_match('/^\d+(\.\d+)?$/', $s) ? $s : null;
    }
}
