<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Peb_model
 * ---------------------------------------------------------------------------
 * Penyimpanan dokumen PEB / NPE untuk sebuah booking code (1 booking : N PEB).
 *
 *  - Tabel : trnstuffingplan_booking_peb (header per PEB)
 *            trnstuffingplan_booking_peb_item (barang per PEB)
 *  - Kolom aju/peb/npe/jalur di trnstuffingplan_booking_detail dijaga sebagai
 *    RINGKASAN (otomatis lewat sync_summary) supaya kode lama tetap jalan.
 *  - Aturan "tidak boleh dikosongkan": nilai tersimpan tidak pernah ditimpa
 *    dengan nilai kosong. Penghapusan PEB hanya lewat soft_delete() + alasan.
 *
 * Model ini TIDAK mengurus file/upload; itu tugas controller Peb_document.
 */
class Peb_model extends CI_Model
{
    const T_PEB  = 'trnstuffingplan_booking_peb';
    const T_ITEM = 'trnstuffingplan_booking_peb_item';

    public $dbx;

    /** Definisi kolom header: tipe & batas. s=string, d=date, n=decimal, i=int, e=enum */
    protected $fields = array(
        'aju_number' => array('s', 30, 'upper'), 'peb_number' => array('s', 30, 'upper'), 'peb_date' => array('d'),
        'npe_number' => array('s', 40, 'upper'), 'npe_date' => array('d'), 'npe_komoditi' => array('s', 80),
        'npe_berat_kotor' => array('n'), 'npe_kemasan_jenis' => array('s', 30), 'npe_kemasan_qty' => array('i'),
        'jalur' => array('e', array('HIJAU', 'MERAH')), 'jalur_date' => array('d'),
        'kantor_pabean_muat' => array('s', 100), 'kantor_pabean_ekspor' => array('s', 100), 'jenis_ekspor' => array('s', 60),
        'kategori_ekspor' => array('s', 150), 'cara_perdagangan' => array('s', 60), 'cara_pembayaran' => array('s', 80),
        'eksportir_id' => array('s', 30), 'eksportir_nama' => array('s', 150), 'pemilik_nama' => array('s', 150),
        'ppjk_nama' => array('s', 150), 'pembeli_nama' => array('s', 200), 'pembeli_negara' => array('s', 60),
        'penerima_nama' => array('s', 200), 'penerima_negara' => array('s', 60),
        'cara_pengangkutan' => array('s', 30), 'sarana_bendera' => array('s', 10), 'sarana_nama' => array('s', 100),
        'voyage' => array('s', 30), 'tgl_perkiraan_ekspor' => array('d'), 'pelabuhan_muat_asal' => array('s', 80),
        'pelabuhan_muat_ekspor' => array('s', 80), 'pelabuhan_tujuan' => array('s', 80), 'negara_tujuan' => array('s', 60),
        'invoice_no' => array('s', 100), 'invoice_date' => array('d'), 'packing_list_no' => array('s', 100),
        'packing_list_date' => array('d'), 'bl_number' => array('s', 100, 'upper'), 'bl_date' => array('d'),
        'lokasi_pemeriksaan' => array('s', 100), 'kantor_pemeriksaan' => array('s', 100),
        'incoterm' => array('s', 10, 'upper'), 'bank_devisa' => array('s', 100), 'valuta_kode' => array('s', 3, 'upper'),
        'valuta_nama' => array('s', 40), 'nilai_ekspor' => array('n'), 'biaya_pengangkutan' => array('n'),
        'asuransi' => array('n'), 'nilai_maklon' => array('n'), 'ndpbm' => array('n'), 'nilai_bea_keluar' => array('n'),
        'pph22_ekspor' => array('n'), 'dana_perkebunan' => array('n'),
        'jumlah_peti_kemas' => array('i'), 'kemasan_qty' => array('i'), 'kemasan_jenis' => array('s', 100),
        'kemasan_marking' => array('s', 255), 'berat_kotor' => array('n'), 'berat_bersih' => array('n'),
        'jumlah_jenis_barang' => array('i'), 'hs_codes' => array('s', 255),
        // PPB (Pemberitahuan Pemeriksaan Barang) = pemeriksaan fisik -> jalur MERAH. Kolom dibuat oleh migration_peb_ppb.sql
        'ppb_number' => array('s', 40, 'upper'), 'ppb_date' => array('d'), 'ppb_tgl_periksa' => array('d'),
        'ppb_kantor_periksa' => array('s', 100), 'ppb_lokasi_periksa' => array('s', 200), 'ppb_petugas_eksportir' => array('s', 100),
        'ppb_tgl_stuffing' => array('d'), 'ppb_tempat_stuffing' => array('s', 150),
        'ppb_jml_kontainer' => array('i'), 'ppb_jml_kemasan' => array('i'),
    );

    /** Kolom yang boleh diubah user lewat form pratinjau/edit (sisanya hanya dari hasil baca dokumen) */
    protected $user_editable = array(
        'aju_number', 'peb_number', 'peb_date', 'npe_number', 'npe_date', 'jalur', 'jalur_date',
        'kategori_ekspor', 'jenis_ekspor', 'incoterm', 'valuta_kode', 'nilai_ekspor', 'biaya_pengangkutan', 'asuransi',
        'ndpbm', 'kemasan_qty', 'kemasan_jenis', 'kemasan_marking', 'berat_kotor', 'berat_bersih',
        'bl_number', 'sarana_nama', 'voyage', 'pelabuhan_tujuan', 'negara_tujuan', 'eksportir_nama', 'pembeli_nama',
        'invoice_no', 'packing_list_no', 'tgl_perkiraan_ekspor',
    );

    protected $item_fields = array(
        'item_no' => array('i'), 'hs_code' => array('s', 12), 'uraian' => array('s', 2000), 'merk' => array('s', 150),
        'tipe' => array('s', 150), 'ukuran' => array('s', 150), 'kode_barang' => array('s', 150),
        'jenis_ekspor' => array('s', 40), 'perizinan' => array('s', 255), 'he' => array('s', 20), 'bk' => array('s', 20),
        'qty' => array('n'), 'satuan' => array('s', 80), 'satuan_kode' => array('s', 6), 'berat_bersih' => array('n'),
        'kemasan_qty' => array('i'), 'kemasan_kode' => array('s', 6), 'negara_asal' => array('s', 60),
        'negara_asal_kode' => array('s', 2), 'daerah_asal' => array('s', 80), 'daerah_asal_kode' => array('s', 6),
        'nilai_ekspor' => array('n'),
    );

    /** Kolom yang boleh diisi/diubah dari form (sisanya hanya dari hasil baca dokumen). */
    public function is_user_editable($field)
    {
        return in_array($field, $this->user_editable, true);
    }

    public function __construct()
    {
        parent::__construct();
        $tahun = $this->session->userdata('db');
        if ($tahun == '' || !is_numeric($tahun)) {
            $this->dbx = $this->load->database('master', TRUE);
        } else {
            $this->dbx = $this->load->database(switch_db($tahun), TRUE);
        }
    }

    /* =====================================================================
     *  SKEMA
     * ===================================================================== */

    /** @return array ['ok'=>bool, 'database'=>string, 'missing'=>array] */
    public function schema_status()
    {
        $missing = array();
        foreach (array(self::T_PEB, self::T_ITEM) as $t) {
            if (!$this->dbx->table_exists($t)) { $missing[] = $t; }
        }
        return array('ok' => !$missing, 'database' => $this->dbx->database, 'missing' => $missing);
    }

    /** Kolom PPB (migration_peb_ppb.sql) sudah ada? */
    public function ppb_ready()
    {
        return $this->dbx->field_exists('ppb_number', self::T_PEB);
    }

    public function ppb_message()
    {
        return $this->ppb_ready() ? '' : 'Kolom PPB belum ada di database ' . $this->dbx->database
            . '. Jalankan migration_peb_ppb.sql di database tahun tersebut.';
    }

    public function schema_message()
    {
        $s = $this->schema_status();
        if ($s['ok']) { return ''; }
        return 'Tabel PEB belum ada di database ' . $s['database'] . ' (' . implode(', ', $s['missing'])
            . '). Jalankan migration_peb_documents.sql di database tahun tersebut.';
    }

    /* =====================================================================
     *  BACA
     * ===================================================================== */

    public function find_booking($booking_code)
    {
        return $this->dbx->select('booking_code, house_bl, status, si_number')
            ->get_where('trnstuffingplan_booking', array('booking_code' => $booking_code))->row_array();
    }

    public function find_booking_by_hbl($hbl)
    {
        $hbl = trim((string) $hbl);
        if ($hbl === '') { return null; }
        return $this->dbx->select('booking_code, house_bl, status')
            ->get_where('trnstuffingplan_booking', array('house_bl' => $hbl))->row_array();
    }

    public function get($id)
    {
        return $this->dbx->get_where(self::T_PEB, array('id' => (int) $id))->row_array();
    }

    /** Daftar PEB aktif sebuah booking (tanpa kolom berat seperti docs_json). */
    public function list_by_booking($booking_code)
    {
        $rows = $this->dbx->query(
            'SELECT p.*, (SELECT COUNT(*) FROM ' . self::T_ITEM . ' i WHERE i.peb_id = p.id) AS item_count
             FROM ' . self::T_PEB . ' p
             WHERE p.booking_code = ? AND p.deleted_at IS NULL
             ORDER BY p.id ASC', array($booking_code))->result_array();
        foreach ($rows as &$r) {
            $r['has_peb_file'] = !empty($r['peb_file']) ? 1 : 0;
            $r['has_npe_file'] = !empty($r['npe_file']) ? 1 : 0;
            $r['has_ppb_file'] = !empty($r['ppb_file']) ? 1 : 0;
            unset($r['docs_json'], $r['peb_warnings']);
        }
        return $rows;
    }

    /**
     * Nomor PEB + NPE (PEB aktif) untuk BANYAK booking sekaligus - satu query. Dipakai cetak Stuffing Plan.
     * Dibaca langsung dari tabel PEB (bukan salinan di tabel stuffing plan), jadi PEB/NPE yang baru di-upload
     * otomatis ikut tercetak, dan booking dengan banyak PEB tampil semuanya.
     *
     * @param  array $codes daftar booking_code
     * @return array booking_code => list of array(aju_number, peb_number, peb_date, npe_number, npe_date), urut id.
     *               Tabel PEB belum dimigrasi / daftar kosong -> array kosong (cetak tetap jalan, kolom kosong).
     */
    public function numbers_by_bookings(array $codes)
    {
        $codes = array_values(array_unique(array_filter(array_map('trim', array_map('strval', $codes)), 'strlen')));
        $out = array();
        if (!$codes || !$this->dbx->table_exists(self::T_PEB)) { return $out; }
        foreach (array_chunk($codes, 300) as $chunk) {
            $rows = $this->dbx->query(
                'SELECT booking_code, aju_number, peb_number, peb_date, npe_number, npe_date
                 FROM ' . self::T_PEB . '
                 WHERE booking_code IN (' . implode(',', array_fill(0, count($chunk), '?')) . ') AND deleted_at IS NULL
                 ORDER BY booking_code, id ASC', $chunk)->result_array();
            foreach ($rows as $r) { $out[$r['booking_code']][] = $r; }
        }
        return $out;
    }

    public function items($peb_id)
    {
        return $this->dbx->order_by('item_no', 'ASC')->order_by('id', 'ASC')
            ->get_where(self::T_ITEM, array('peb_id' => (int) $peb_id))->result_array();
    }

    /** Booking lain (selain $exclude) yang sudah memakai AJU yang sama. */
    public function other_bookings_with_aju($aju, $exclude_code)
    {
        $aju = strtoupper(trim((string) $aju));
        if ($aju === '') { return array(); }
        $q = $this->dbx->select('booking_code')->distinct()
            ->where('aju_number', $aju)->where('deleted_at IS NULL', null, false)
            ->where('booking_code <>', $exclude_code)->get(self::T_PEB)->result_array();
        return array_map(function ($r) { return $r['booking_code']; }, $q);
    }

    /** Cari baris PEB aktif di booking ini berdasarkan AJU (utama) atau Nomor PEB. */
    public function find_active_in_booking($booking_code, $aju, $peb_number = '')
    {
        $aju = strtoupper(trim((string) $aju));
        $peb_number = strtoupper(trim((string) $peb_number));
        if ($aju !== '') {
            $r = $this->dbx->where('booking_code', $booking_code)->where('aju_number', $aju)
                ->where('deleted_at IS NULL', null, false)->get(self::T_PEB)->row_array();
            if ($r) { return $r; }
        }
        if ($peb_number !== '') {
            $r = $this->dbx->where('booking_code', $booking_code)->where('peb_number', $peb_number)
                ->where('deleted_at IS NULL', null, false)->get(self::T_PEB)->row_array();
            if ($r) { return $r; }
        }
        return null;
    }

    /** Apakah file (sha256) yang sama sudah pernah diunggah untuk booking ini? */
    public function find_by_file_hash($booking_code, $hash, $type = 'peb')
    {
        $col = ($type === 'npe') ? 'npe_file_hash' : 'peb_file_hash';
        return $this->dbx->where('booking_code', $booking_code)->where($col, $hash)
            ->where('deleted_at IS NULL', null, false)->get(self::T_PEB)->row_array();
    }

    /* =====================================================================
     *  SIMPAN PEB
     * ===================================================================== */

    /**
     * Simpan hasil baca PEB (atau input manual) sebagai satu baris PEB.
     * Bila di booking ini sudah ada PEB dengan AJU/Nomor PEB sama -> diperbarui
     * (nilai kosong tidak menimpa nilai lama).
     *
     * @param array $header  field header (bebas; hanya kolom dikenal yang dipakai)
     * @param array $items   daftar barang (boleh kosong => item lama dipertahankan)
     * @param array $meta    user, source, confidence, warnings(array), file, file_name, file_hash, documents(array);
     *                       file gabungan PEB+NPE: npe_file (nama file yang sama), replace_npe (izinkan mengganti NPE lama)
     * @return array status 1/0, id, action, message
     */
    public function save_peb($booking_code, array $header, array $items, array $meta)
    {
        if (($m = $this->schema_message()) !== '') { return $this->err($m); }
        if (!$this->find_booking($booking_code)) { return $this->err('Booking ' . $booking_code . ' tidak ditemukan di database ' . $this->dbx->database . '.'); }

        $row = $this->normalize($header, $this->fields);
        if (empty($row['aju_number']) && empty($row['peb_number'])) {
            return $this->err('Minimal AJU atau Nomor PEB harus terisi.');
        }
        $now  = date('Y-m-d H:i:s');
        $user = $this->user($meta);

        $meta_cols = array(
            'peb_source'     => isset($meta['source']) ? $meta['source'] : 'manual',
            'peb_confidence' => isset($meta['confidence']) ? $meta['confidence'] : null,
            'peb_warnings'   => !empty($meta['warnings']) ? substr(implode("\n", (array) $meta['warnings']), 0, 4000) : null,
            'peb_file'       => isset($meta['file']) ? $meta['file'] : null,
            'peb_file_name'  => isset($meta['file_name']) ? substr($meta['file_name'], 0, 255) : null,
            'peb_file_hash'  => isset($meta['file_hash']) ? $meta['file_hash'] : null,
            'docs_json'      => !empty($meta['documents']) ? json_encode($meta['documents'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
        );
        if (!empty($meta_cols['peb_file'])) {
            $meta_cols['peb_uploaded_by'] = $user;
            $meta_cols['peb_uploaded_at'] = $now;
        }

        $existing = $this->find_active_in_booking($booking_code, isset($row['aju_number']) ? $row['aju_number'] : '', isset($row['peb_number']) ? $row['peb_number'] : '');

        // File gabungan PEB + NPE: PDF yang sama juga dicatat sebagai file NPE (satu transaksi dengan PEB-nya).
        // Hanya bila Nomor & Tanggal NPE terisi; kalau dikosongkan user, file tidak dicatat sebagai NPE.
        if (!empty($meta['npe_file']) && !empty($row['npe_number'])) {
            if (empty($row['npe_date'])) { return $this->err('Nomor NPE dan Tanggal NPE harus terisi berpasangan.'); }
            if ($existing && !empty($existing['npe_number']) && strtoupper($existing['npe_number']) !== strtoupper($row['npe_number'])
                && empty($meta['replace_npe'])) {
                return array('status' => 0, 'code' => 'NPE_CONFLICT', 'existing' => $existing['npe_number'],
                    'message' => 'PEB ini sudah punya NPE ' . $existing['npe_number'] . '. Ganti dengan ' . $row['npe_number'] . '?');
            }
            $meta_cols['npe_source']      = isset($meta['source']) ? $meta['source'] : 'manual';
            $meta_cols['npe_file']        = $meta['npe_file'];
            $meta_cols['npe_file_name']   = isset($meta['file_name']) ? substr($meta['file_name'], 0, 255) : null;
            $meta_cols['npe_file_hash']   = isset($meta['file_hash']) ? $meta['file_hash'] : null;
            $meta_cols['npe_uploaded_by'] = $user;
            $meta_cols['npe_uploaded_at'] = $now;
        }

        // PPB (pemeriksaan fisik): jalur MERAH, tanggal penetapan = tanggal PPB. PDF-nya (bila ada) dicatat sebagai file PPB.
        if (!empty($row['ppb_number'])) {
            if (($m = $this->ppb_message()) !== '') { return $this->err($m); }
            if (empty($row['ppb_date'])) { return $this->err('Nomor PPB dan Tanggal PPB harus terisi berpasangan.'); }
            if ($existing && !empty($existing['ppb_number']) && strtoupper($existing['ppb_number']) !== strtoupper($row['ppb_number'])
                && empty($meta['replace_ppb'])) {
                return array('status' => 0, 'code' => 'PPB_CONFLICT', 'existing' => $existing['ppb_number'],
                    'message' => 'PEB ini sudah punya PPB ' . $existing['ppb_number'] . '. Ganti dengan ' . $row['ppb_number'] . '?');
            }
            $row['jalur'] = 'MERAH';
            if (empty($row['jalur_date']) || !$existing || $existing['jalur'] !== 'MERAH') { $row['jalur_date'] = $row['ppb_date']; }
            if (!empty($meta['ppb_file'])) {
                $meta_cols['ppb_source']      = isset($meta['source']) ? $meta['source'] : 'manual';
                $meta_cols['ppb_file']        = $meta['ppb_file'];
                $meta_cols['ppb_file_name']   = isset($meta['file_name']) ? substr($meta['file_name'], 0, 255) : null;
                $meta_cols['ppb_file_hash']   = isset($meta['file_hash']) ? $meta['file_hash'] : null;
                $meta_cols['ppb_uploaded_by'] = $user;
                $meta_cols['ppb_uploaded_at'] = $now;
            }
        }

        $this->dbx->trans_start();
        if ($existing) {
            $upd = array();
            foreach (array_merge($row, $meta_cols) as $k => $v) {           // anti-blank: hanya nilai terisi
                if ($v !== null && $v !== '') { $upd[$k] = $v; }
            }
            $upd['updated_at'] = $now;
            $upd['updated_by'] = $user;
            $this->dbx->where('id', $existing['id'])->update(self::T_PEB, $upd);
            $id = (int) $existing['id'];
            $action = 'updated';
        } else {
            $ins = array_merge($row, $meta_cols);
            $ins['booking_code'] = $booking_code;
            $ins['created_at'] = $now;
            $ins['updated_at'] = $now;
            $ins['updated_by'] = $user;
            $this->dbx->insert(self::T_PEB, $ins);
            $id = (int) $this->dbx->insert_id();
            $action = 'inserted';
        }

        if ($id > 0 && $items) {
            $this->dbx->where('peb_id', $id)->delete(self::T_ITEM);
            $batch = array();
            foreach ($items as $it) {
                $r = $this->normalize($it, $this->item_fields);
                if (empty($r['hs_code']) && empty($r['uraian'])) { continue; }
                $r['peb_id'] = $id;
                $batch[] = $r;
            }
            if ($batch) { $this->insert_batch_uniform(self::T_ITEM, $batch); }
        }
        if ($id > 0) { $this->sync_summary($booking_code); }
        $this->dbx->trans_complete();

        if ($this->dbx->trans_status() === false || $id <= 0) {
            $e = $this->dbx->error();
            return $this->err('Gagal menyimpan PEB ke database ' . $this->dbx->database
                . (!empty($e['message']) ? ': ' . $e['message'] : '.'));
        }
        return array('status' => 1, 'id' => $id, 'action' => $action, 'database' => $this->dbx->database);
    }

    /* =====================================================================
     *  NPE
     * ===================================================================== */

    /**
     * Cari baris PEB yang cocok untuk sebuah NPE: AJU -> Nomor PEB.
     * @return array|null
     */
    public function match_npe_target($booking_code, array $npe_header)
    {
        return $this->find_active_in_booking(
            $booking_code,
            isset($npe_header['aju_number']) ? $npe_header['aju_number'] : '',
            isset($npe_header['peb_number']) ? $npe_header['peb_number'] : ''
        );
    }

    /**
     * Tempelkan NPE ke PEB yang sudah ada.
     * @param array $opts replace=>bool  izinkan mengganti NPE lama yang berbeda
     */
    public function attach_npe($booking_code, array $npe, array $meta, array $opts = array())
    {
        if (($m = $this->schema_message()) !== '') { return $this->err($m); }
        $target = $this->match_npe_target($booking_code, $npe);
        if (!$target) {
            return array('status' => 0, 'code' => 'PEB_NOT_FOUND',
                'message' => 'PEB dengan AJU ' . (isset($npe['aju_number']) ? $npe['aju_number'] : '-') . ' / Nomor PEB '
                    . (isset($npe['peb_number']) ? $npe['peb_number'] : '-') . ' belum ada di booking ini. Upload PEB-nya dulu, baru NPE.');
        }
        $no = isset($npe['npe_number']) ? strtoupper(trim((string) $npe['npe_number'])) : '';
        $dt = $this->norm_date(isset($npe['npe_date']) ? $npe['npe_date'] : null);
        if ($no === '' || $dt === null) {
            return $this->err('Nomor NPE dan Tanggal NPE wajib terisi.');
        }
        if (!empty($target['npe_number']) && strtoupper($target['npe_number']) !== $no && empty($opts['replace'])) {
            return array('status' => 0, 'code' => 'NPE_CONFLICT',
                'message' => 'PEB ini sudah punya NPE ' . $target['npe_number'] . '. Ganti dengan ' . $no . '?',
                'existing' => $target['npe_number']);
        }

        $now  = date('Y-m-d H:i:s');
        $user = $this->user($meta);
        $upd = array(
            'npe_number' => $no, 'npe_date' => $dt, 'updated_at' => $now, 'updated_by' => $user,
        );
        $opt = array(
            'npe_komoditi'      => $this->norm_str(isset($npe['jenis_komoditi']) ? $npe['jenis_komoditi'] : null, 80),
            'npe_berat_kotor'   => $this->norm_num(isset($npe['berat_kotor']) ? $npe['berat_kotor'] : null),
            'npe_kemasan_jenis' => $this->norm_str(isset($npe['kemasan_jenis']) ? $npe['kemasan_jenis'] : null, 30),
            'npe_kemasan_qty'   => $this->norm_int(isset($npe['kemasan_qty']) ? $npe['kemasan_qty'] : null),
            'npe_source'        => isset($meta['source']) ? $meta['source'] : 'manual',
            'npe_file'          => isset($meta['file']) ? $meta['file'] : null,
            'npe_file_name'     => isset($meta['file_name']) ? substr($meta['file_name'], 0, 255) : null,
            'npe_file_hash'     => isset($meta['file_hash']) ? $meta['file_hash'] : null,
        );
        foreach ($opt as $k => $v) { if ($v !== null && $v !== '') { $upd[$k] = $v; } }
        if (!empty($upd['npe_file'])) { $upd['npe_uploaded_by'] = $user; $upd['npe_uploaded_at'] = $now; }

        $this->dbx->trans_start();
        $this->dbx->where('id', $target['id'])->update(self::T_PEB, $upd);
        $this->sync_summary($booking_code);
        $this->dbx->trans_complete();
        if ($this->dbx->trans_status() === false) {
            $e = $this->dbx->error();
            return $this->err('Gagal menyimpan NPE ke database ' . $this->dbx->database . (!empty($e['message']) ? ': ' . $e['message'] : '.'));
        }
        return array('status' => 1, 'id' => (int) $target['id'], 'database' => $this->dbx->database);
    }

    /**
     * Cari baris PEB yang cocok untuk sebuah PPB. PPB tidak memuat AJU, hanya "PEB nomor ... tanggal ...".
     * @return array|null
     */
    public function match_ppb_target($booking_code, array $ppb_header)
    {
        $no = isset($ppb_header['peb_number']) ? trim((string) $ppb_header['peb_number']) : '';
        if ($no === '') { return null; }
        return $this->find_active_in_booking($booking_code, '', $no);
    }

    /**
     * Tempelkan PPB ke PEB yang sudah ada. PPB = pemeriksaan fisik -> jalur MERAH, tgl penetapan = tgl PPB.
     * @param array $opts replace=>bool  izinkan mengganti PPB lama yang berbeda
     */
    public function attach_ppb($booking_code, array $ppb, array $meta, array $opts = array())
    {
        if (($m = $this->schema_message()) !== '') { return $this->err($m); }
        if (($m = $this->ppb_message()) !== '') { return $this->err($m); }
        $target = $this->match_ppb_target($booking_code, $ppb);
        if (!$target) {
            return array('status' => 0, 'code' => 'PEB_NOT_FOUND',
                'message' => 'PEB nomor ' . (isset($ppb['peb_number']) && $ppb['peb_number'] !== '' ? $ppb['peb_number'] : '-')
                    . ' belum ada di booking ini. Upload PEB-nya dulu, baru PPB.');
        }
        $norm = $this->normalize($ppb, $this->fields);
        $no = isset($norm['ppb_number']) ? $norm['ppb_number'] : '';
        if ($no === '' || empty($norm['ppb_date'])) {
            return $this->err('Nomor PPB dan Tanggal PPB wajib terisi.');
        }
        if (!empty($target['ppb_number']) && strtoupper($target['ppb_number']) !== strtoupper($no) && empty($opts['replace'])) {
            return array('status' => 0, 'code' => 'PPB_CONFLICT',
                'message' => 'PEB ini sudah punya PPB ' . $target['ppb_number'] . '. Ganti dengan ' . $no . '?',
                'existing' => $target['ppb_number']);
        }

        $now  = date('Y-m-d H:i:s');
        $user = $this->user($meta);
        $upd = array('updated_at' => $now, 'updated_by' => $user, 'jalur' => 'MERAH');
        foreach ($norm as $k => $v) {
            if (strpos($k, 'ppb_') === 0 && $v !== null && $v !== '') { $upd[$k] = $v; }
        }
        // tanggal penetapan jalur: tanggal PPB, kecuali jalur sudah MERAH dengan tanggal yang diisi
        if ($target['jalur'] !== 'MERAH' || empty($target['jalur_date'])) { $upd['jalur_date'] = $norm['ppb_date']; }
        if (!empty($meta['file'])) {
            $upd['ppb_source']      = isset($meta['source']) ? $meta['source'] : 'manual';
            $upd['ppb_file']        = $meta['file'];
            $upd['ppb_file_name']   = isset($meta['file_name']) ? substr($meta['file_name'], 0, 255) : null;
            $upd['ppb_file_hash']   = isset($meta['file_hash']) ? $meta['file_hash'] : null;
            $upd['ppb_uploaded_by'] = $user;
            $upd['ppb_uploaded_at'] = $now;
        }

        $this->dbx->trans_start();
        $this->dbx->where('id', $target['id'])->update(self::T_PEB, $upd);
        $this->sync_summary($booking_code);
        $this->dbx->trans_complete();
        if ($this->dbx->trans_status() === false) {
            $e = $this->dbx->error();
            return $this->err('Gagal menyimpan PPB ke database ' . $this->dbx->database . (!empty($e['message']) ? ': ' . $e['message'] : '.'));
        }
        return array('status' => 1, 'id' => (int) $target['id'], 'database' => $this->dbx->database,
            'jalur_before' => $target['jalur']);
    }

    /* =====================================================================
     *  UBAH MANUAL (NPE / jalur / koreksi) & HAPUS
     * ===================================================================== */

    /**
     * Ubah field tertentu pada satu PEB. Nilai kosong DIABAIKAN (tidak mengosongkan data).
     * Hanya kolom di $user_editable.
     */
    public function update_fields($id, array $in, $user = '')
    {
        if (($m = $this->schema_message()) !== '') { return $this->err($m); }
        $row = $this->get($id);
        if (!$row || !empty($row['deleted_at'])) { return $this->err('Data PEB tidak ditemukan.'); }

        $allowed = array();
        foreach ($this->user_editable as $k) { if (array_key_exists($k, $in)) { $allowed[$k] = $in[$k]; } }
        $norm = $this->normalize($allowed, $this->fields);
        $upd = array();
        foreach ($norm as $k => $v) {
            if ($v !== null && $v !== '' && (string) $v !== (string) $row[$k]) { $upd[$k] = $v; }
        }
        if (!$upd) { return array('status' => 1, 'id' => (int) $id, 'changed' => 0); }

        // PEB yang punya PPB (pemeriksaan fisik) selalu jalur MERAH.
        if (isset($upd['jalur']) && $upd['jalur'] !== 'MERAH' && !empty($row['ppb_number'])) {
            return $this->err('PEB ini punya PPB ' . $row['ppb_number'] . ' (pemeriksaan fisik), jalurnya harus MERAH.');
        }

        // Tidak boleh bentrok dengan PEB aktif lain pada booking yang sama.
        foreach (array('aju_number', 'peb_number') as $k) {
            if (isset($upd[$k])) {
                $dupe = $this->dbx->where('booking_code', $row['booking_code'])->where($k, $upd[$k])
                    ->where('id <>', (int) $id)->where('deleted_at IS NULL', null, false)->get(self::T_PEB)->row_array();
                if ($dupe) { return $this->err(($k === 'aju_number' ? 'AJU ' : 'Nomor PEB ') . $upd[$k] . ' sudah dipakai PEB lain di booking ini.'); }
            }
        }
        $upd['updated_at'] = date('Y-m-d H:i:s');
        $upd['updated_by'] = $user !== '' ? $user : $this->user(array());

        $this->dbx->trans_start();
        $this->dbx->where('id', (int) $id)->update(self::T_PEB, $upd);
        $this->sync_summary($row['booking_code']);
        $this->dbx->trans_complete();
        if ($this->dbx->trans_status() === false) {
            $e = $this->dbx->error();
            return $this->err('Gagal menyimpan perubahan ke database ' . $this->dbx->database . (!empty($e['message']) ? ': ' . $e['message'] : '.'));
        }
        return array('status' => 1, 'id' => (int) $id, 'changed' => count($upd) - 2, 'booking_code' => $row['booking_code']);
    }

    public function soft_delete($id, $reason, $user = '')
    {
        if (($m = $this->schema_message()) !== '') { return $this->err($m); }
        $reason = trim((string) $reason);
        if (strlen($reason) < 5) { return $this->err('Alasan penghapusan wajib diisi (minimal 5 karakter).'); }
        $row = $this->get($id);
        if (!$row || !empty($row['deleted_at'])) { return $this->err('Data PEB tidak ditemukan.'); }

        $this->dbx->trans_start();
        $this->dbx->where('id', (int) $id)->update(self::T_PEB, array(
            'deleted_at' => date('Y-m-d H:i:s'), 'deleted_by' => $user !== '' ? $user : $this->user(array()),
            'delete_reason' => substr($reason, 0, 255),
        ));
        $this->sync_summary($row['booking_code']);
        $this->dbx->trans_complete();
        if ($this->dbx->trans_status() === false) {
            $e = $this->dbx->error();
            return $this->err('Gagal menghapus PEB di database ' . $this->dbx->database . (!empty($e['message']) ? ': ' . $e['message'] : '.'));
        }
        return array('status' => 1, 'booking_code' => $row['booking_code']);
    }

    /* =====================================================================
     *  RINGKASAN KE trnstuffingplan_booking_detail
     * ===================================================================== */

    /**
     * Isi kolom aju/peb/npe/jalur di detail (ringkasan untuk modul lama) dari PEB aktif.
     * AJU, PEB dan NPE selalu diambil dari SATU PEB yang sama agar pasangannya tidak tertukar:
     * PEB yang datanya paling lengkap (punya NPE > punya PEB > punya AJU), seri -> id terkecil.
     * Daftar lengkap semua PEB tetap tersedia lewat ticket_list()/print_lines().
     * Tidak membuat baris detail baru; bila detail belum ada, dilewati.
     */
    public function sync_summary($booking_code)
    {
        if (!$this->dbx->field_exists('aju_number', 'trnstuffingplan_booking_detail')) { return false; }
        $rows = $this->dbx->where('booking_code', $booking_code)->where('deleted_at IS NULL', null, false)
            ->order_by('id', 'ASC')->get(self::T_PEB)->result_array();

        $sum = array('aju_number' => null, 'peb_number' => null, 'peb_date' => null,
            'npe_number' => null, 'npe_date' => null, 'jalur' => null, 'jalur_date' => null);
        $primary = null; $best = -1;
        foreach ($rows as $r) {
            $score = (!empty($r['npe_number']) ? 4 : 0) + (!empty($r['peb_number']) ? 2 : 0) + (!empty($r['aju_number']) ? 1 : 0);
            if ($score > $best) { $best = $score; $primary = $r; }
        }
        if ($primary !== null) {
            if (!empty($primary['aju_number'])) { $sum['aju_number'] = $primary['aju_number']; }
            if (!empty($primary['peb_number'])) { $sum['peb_number'] = $primary['peb_number']; $sum['peb_date'] = $primary['peb_date']; }
            if (!empty($primary['npe_number'])) { $sum['npe_number'] = $primary['npe_number']; $sum['npe_date'] = $primary['npe_date']; }
        }
        // Jalur ringkasan: MERAH bila ada satu saja yang merah (paling "berat"), selain itu HIJAU.
        foreach ($rows as $r) {
            if ($r['jalur'] === 'MERAH') { $sum['jalur'] = 'MERAH'; $sum['jalur_date'] = $r['jalur_date']; break; }
            if ($r['jalur'] === 'HIJAU' && $sum['jalur'] === null) { $sum['jalur'] = 'HIJAU'; $sum['jalur_date'] = $r['jalur_date']; }
        }
        if (isset($sum['npe_number'])) { $sum['npe_number'] = substr($sum['npe_number'], 0, 30); }   // kolom detail varchar(30)

        $sum['updated_at'] = date('Y-m-d H:i:s');
        $this->dbx->where('booking_id', $booking_code)->update('trnstuffingplan_booking_detail', $sum);
        return true;
    }

    /* =====================================================================
     *  KELUARAN UNTUK MODUL LAIN (WMS, cetak BC)
     * ===================================================================== */

    /** Daftar PEB ringkas untuk payload ticket WMS. */
    public function ticket_list($booking_code)
    {
        if (!$this->schema_status()['ok']) { return array(); }
        $out = array();
        foreach ($this->list_by_booking($booking_code) as $r) {
            $out[] = array(
                'aju_number'       => (string) $r['aju_number'],
                'peb_number'       => (string) $r['peb_number'],
                'peb_date'         => (string) $r['peb_date'],
                'npe_number'       => (string) $r['npe_number'],
                'npe_date'         => (string) $r['npe_date'],
                'jalur'            => (string) $r['jalur'],
                'jalur_date'       => (string) $r['jalur_date'],
                'kategori_ekspor'  => (string) $r['kategori_ekspor'],
                'incoterm'         => (string) $r['incoterm'],
                'valuta'           => (string) $r['valuta_kode'],
                'nilai_ekspor'     => $r['nilai_ekspor'] !== null ? (float) $r['nilai_ekspor'] : null,
                'ndpbm'            => $r['ndpbm'] !== null ? (float) $r['ndpbm'] : null,
                'kemasan_qty'      => $r['kemasan_qty'] !== null ? (int) $r['kemasan_qty'] : null,
                'kemasan_jenis'    => (string) $r['kemasan_jenis'],
                'kemasan_marking'  => (string) $r['kemasan_marking'],
                'berat_kotor'      => $r['berat_kotor'] !== null ? (float) $r['berat_kotor'] : null,
                'berat_bersih'     => $r['berat_bersih'] !== null ? (float) $r['berat_bersih'] : null,
                'hs_codes'         => (string) $r['hs_codes'],
                'bl_number'        => (string) $r['bl_number'],
            );
        }
        return $out;
    }

    /**
     * Teks untuk dicetak di BC: daftar "NOMOR / dd-mm-YYYY" per PEB.
     * @return array ['aju'=>[..], 'peb'=>[..], 'npe'=>[..]]
     */
    public function print_lines($booking_code)
    {
        $out = array('aju' => array(), 'peb' => array(), 'npe' => array());
        if (!$this->schema_status()['ok']) { return $out; }
        $fmt = function ($no, $date) {
            if ($no === null || $no === '') { return null; }
            $d = ($date && $date !== '0000-00-00') ? date('d-m-Y', strtotime($date)) : '';
            return $d !== '' ? $no . ' / ' . $d : $no;
        };
        foreach ($this->list_by_booking($booking_code) as $r) {
            if (!empty($r['aju_number'])) { $out['aju'][] = $r['aju_number']; }
            if (($l = $fmt($r['peb_number'], $r['peb_date'])) !== null) { $out['peb'][] = $l; }
            if (($l = $fmt($r['npe_number'], $r['npe_date'])) !== null) { $out['npe'][] = $l; }
        }
        return $out;
    }

    /* =====================================================================
     *  UTIL
     * ===================================================================== */

    protected function err($msg)
    {
        return array('status' => 0, 'message' => $msg);
    }

    protected function user($meta)
    {
        if (!empty($meta['user'])) { return substr((string) $meta['user'], 0, 100); }
        $u = $this->session->userdata('fullname');
        return $u ? substr((string) $u, 0, 100) : 'system';
    }

    protected function normalize(array $in, array $defs)
    {
        $out = array();
        foreach ($defs as $k => $d) {
            if (!array_key_exists($k, $in)) { continue; }
            $v = $in[$k];
            switch ($d[0]) {
                case 's':
                    $v = $this->norm_str($v, $d[1]);
                    if ($v !== null && isset($d[2]) && $d[2] === 'upper') { $v = strtoupper($v); }
                    break;
                case 'd': $v = $this->norm_date($v); break;
                case 'n': $v = $this->norm_num($v); break;
                case 'i': $v = $this->norm_int($v); break;
                case 'e':
                    $v = strtoupper(trim((string) $v));
                    $v = in_array($v, $d[1], true) ? $v : null;
                    break;
            }
            $out[$k] = $v;
        }
        return $out;
    }

    protected function norm_str($v, $max)
    {
        if ($v === null) { return null; }
        $v = trim(preg_replace('/\s+/', ' ', (string) $v));
        if ($v === '' || $v === '-') { return null; }
        return function_exists('mb_substr') ? mb_substr($v, 0, $max, 'UTF-8') : substr($v, 0, $max);
    }

    protected function norm_date($v)
    {
        $v = trim((string) $v);
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $v, $m) && checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            return $m[1] . '-' . $m[2] . '-' . $m[3];
        }
        if (preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $v, $m) && checkdate((int) $m[2], (int) $m[1], (int) $m[3])) {
            return $m[3] . '-' . $m[2] . '-' . $m[1];
        }
        return null;
    }

    protected function norm_num($v)
    {
        if ($v === null) { return null; }
        $v = str_replace(array(',', ' '), '', (string) $v);
        return preg_match('/^-?\d+(\.\d+)?$/', $v) ? $v : null;
    }

    protected function norm_int($v)
    {
        if ($v === null || $v === '') { return null; }
        $v = str_replace(array(',', ' '), '', (string) $v);
        return preg_match('/^-?\d+$/', $v) ? (int) $v : null;
    }

    /** insert_batch CI3 butuh semua baris punya kolom yang sama. */
    protected function insert_batch_uniform($table, array $rows)
    {
        $keys = array();
        foreach ($rows as $r) { foreach ($r as $k => $_) { $keys[$k] = true; } }
        foreach ($rows as &$r) {
            foreach ($keys as $k => $_) { if (!array_key_exists($k, $r)) { $r[$k] = null; } }
        }
        unset($r);
        return $this->dbx->insert_batch($table, $rows);
    }
}
