<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Error bisnis yang dibawa sampai ke controller dengan kode & HTTP status.
 */
class Booking_exception extends Exception
{
    public $error_code;
    public $http_status;
    public $errors;

    public function __construct($error_code, $message, $http_status = 400, array $errors = [])
    {
        parent::__construct($message);
        $this->error_code  = $error_code;
        $this->http_status = $http_status;
        $this->errors      = $errors;
    }
}

#[\AllowDynamicProperties]
class M_booking_v2 extends CI_Model
{
    const T_BOOKING          = 'trnstuffingplan_booking';
    const T_DETAIL           = 'trnstuffingplan_booking_detail';
    const T_LINK             = 'trnbooking_link';
    const T_COLOADER         = 'mstcoloader_company';
    const T_COLOADER_SHIPPER = 'mstcoloader_shipper';
    // Master port. Nama tabel memang 'tableName' dan dipakai di banyak modul, jangan diganti.
    const T_PORT             = 'tableName';

    const MYSQL_DUPLICATE    = 1062;

    // Nomor BC Gateway = isi QR di dokumen BC: "GTW-" + uniqid() huruf besar.
    const BC_PREFIX          = 'GTW-';
    const BC_PATTERN         = '/^GTW-[0-9A-F]{13}$/';

    public static function new_booking_code()
    {
        return self::BC_PREFIX . strtoupper(uniqid());
    }

    public static function is_gateway_bc($code)
    {
        return (bool) preg_match(self::BC_PATTERN, strtoupper(trim((string) $code)));
    }

    public $dbx;

    public function __construct()
    {
        parent::__construct();
        $this->dbx = $this->load->database('master', TRUE);
    }

    /* =================================================================
     * MASTER
     * ================================================================= */

    public function get_shipper($idShipper)
    {
        return $this->dbx
            ->get_where('mstshipper', ['id_shipper' => (int) $idShipper])
            ->row_array();
    }

    /** Coloader yang diwakili oleh id_shipper ini (biasanya 0 atau 1). */
    public function get_coloaders_for_shipper($idShipper)
    {
        return $this->dbx
            ->select('c.company_code, c.company_name, c.bc_pattern, cs.require_link')
            ->from(self::T_COLOADER_SHIPPER . ' cs')
            ->join(self::T_COLOADER . ' c', 'c.company_code = cs.company_code', 'inner')
            ->where('cs.id_shipper', (int) $idShipper)
            ->where('c.is_active', 1)
            ->get()
            ->result_array();
    }

    /**
     * Autentikasi partner server-to-server (sister company).
     * @return array|null bentuk sama dengan hasil Customer_auth::authenticate()
     */
    /** Alasan penolakan partner terakhir (untuk pesan 401 yang bisa didiagnosis). */
    public $partner_error = '';

    public function authenticate_partner($code, $key)
    {
        $this->partner_error = '';
        if ($code === '' || strlen($key) < 20) {
            $this->partner_error = 'X-Partner-Key kosong atau terlalu pendek.';
            return null;
        }
        $q = $this->dbx->get_where(self::T_COLOADER, ['company_code' => $code, 'is_active' => 1]);
        if ($q === false) {
            $this->partner_error = 'Tabel mstcoloader_company belum ada di database Gateway (jalankan 001_booking_link.sql).';
            return null;
        }
        $c = $q->row_array();
        if (!$c) {
            $this->partner_error = "Kode partner {$code} tidak terdaftar / tidak aktif di mstcoloader_company.";
            return null;
        }

        // Utama: key polos di config (booking_secrets.php / SetEnv PARTNER_KEY_<KODE>).
        // Cadangan: hash di kolom api_key_hash (bila migration 002 dipakai).
        $keys       = (array) $this->config->item('partner_keys', 'booking_api');
        $configured = isset($keys[$code]) ? (string) $keys[$code] : '';
        if ($configured !== '') {
            $valid = hash_equals($configured, (string) $key);
            $this->partner_error = $valid ? '' : "Key tidak cocok dengan PARTNER_KEY_{$code} di Gateway.";
        } elseif (!empty($c['api_key_hash'])) {
            $valid = password_verify($key, $c['api_key_hash']);
            $this->partner_error = $valid ? '' : "Key tidak cocok dengan api_key_hash {$code} di Gateway.";
        } else {
            $valid = false;
            $this->partner_error = "PARTNER_KEY_{$code} belum terbaca di Gateway: isi config/booking_secrets.php dan pastikan config/booking_api.php versi terbaru.";
        }
        if (!$valid) {
            return null;
        }

        $access = $this->dbx
            ->select('cs.id_shipper, s.shipper_name')
            ->from(self::T_COLOADER_SHIPPER . ' cs')
            ->join('mstshipper s', 's.id_shipper = cs.id_shipper', 'left')
            ->where('cs.company_code', $code)
            ->order_by('cs.id_shipper', 'ASC')
            ->get()->result_array();

        return [
            'ok'             => true,
            'partner'        => $code,
            'user'           => ['email' => 'partner:' . $code],
            'shipper_access' => $access,
        ];
    }

    /* =================================================================
     * READ
     * ================================================================= */

    public function find_booking($bookingCode)
    {
        return $this->dbx
            ->get_where(self::T_BOOKING, ['booking_code' => $bookingCode])
            ->row_array();
    }

    public function find_detail($bookingCode)
    {
        return $this->dbx
            ->get_where(self::T_DETAIL, ['booking_id' => $bookingCode])
            ->row_array();
    }

    public function get_links($bookingCode, $activeOnly = true)
    {
        $this->dbx
            ->select('linked_company, linked_bc_no, linked_hbl, link_type, status, created_by, created_at, cancelled_by, cancelled_at, cancel_reason')
            ->from(self::T_LINK)
            ->where('booking_code', $bookingCode);

        if ($activeOnly) {
            $this->dbx->where('status', 'active');
        }

        return $this->dbx->order_by('id', 'ASC')->get()->result_array();
    }

    /* =================================================================
     * CREATE
     * ================================================================= */

    /**
     * @param array       $header   kolom trnstuffingplan_booking (tanpa booking_code & house_bl)
     * @param array       $detail   kolom trnstuffingplan_booking_detail (tanpa booking_id)
     * @param array       $links    [['bc_no' => ..., 'hbl' => ...], ...]
     * @param string|null $coloader company_code, wajib bila $links tidak kosong
     * @param string      $actor    identitas user untuk audit
     * @return array ['id', 'booking_code', 'house_bl']
     */
    public function create(array $header, array $detail, array $links, $coloader, $actor)
    {
        // Lookup master DI LUAR transaksi dan dengan null-check,
        // supaya data master yang hilang menjadi error yang jelas, bukan fatal error.
        $regionName = (string) $header['region_id'];
        $region = $this->dbx->get_where('refregion', ['region_name' => $regionName])->row_array();
        if (!$region) {
            throw new Booking_exception('MASTER_NOT_FOUND', "Region '{$regionName}' tidak ditemukan di refregion.", 422);
        }

        $port = $this->dbx->get_where(self::T_PORT, ['name' => $header['destination_city']])->row_array();
        if (!$port) {
            throw new Booking_exception('MASTER_NOT_FOUND', "Port tujuan '{$header['destination_city']}' tidak ditemukan.", 422,
                ['booking.pod' => 'Port tidak terdaftar']);
        }

        $attempt = 0;
        while (true) {
            $attempt++;
            $bookingCode = self::new_booking_code();

            $this->dbx->trans_begin();
            try {
                $row = $header;
                $row['booking_code'] = $bookingCode;
                $this->insert_or_throw(self::T_BOOKING, $row);
                $id = (int) $this->dbx->insert_id();

                $houseBl = $this->build_house_bl(
                    $id, $regionName, $region['region_id'], $port['code'],
                    $header['destination_city'], $header['trans_city']
                );
                $this->update_or_throw(self::T_BOOKING, ['house_bl' => $houseBl], ['id' => $id]);

                $det = $detail;
                $det['booking_id'] = $bookingCode;
                $this->insert_or_throw(self::T_DETAIL, $det);

                foreach ($links as $link) {
                    $this->insert_link($bookingCode, $coloader, $link, $actor);
                }

                if ($this->dbx->trans_status() === FALSE) {
                    throw new Booking_exception('DB_ERROR', 'Gagal menyimpan booking.', 500);
                }

                $this->dbx->trans_commit();
                return ['id' => $id, 'booking_code' => $bookingCode, 'house_bl' => $houseBl];

            } catch (Booking_exception $e) {
                $this->dbx->trans_rollback();
                // Tabrakan booking_code (sangat jarang): ulangi dengan kode baru.
                if ($e->error_code === 'DUPLICATE_BOOKING_CODE' && $attempt < 3) {
                    continue;
                }
                throw $e;
            } catch (Throwable $e) {
                $this->dbx->trans_rollback();
                log_message('error', 'M_booking_v2::create ' . $e->getMessage());
                throw new Booking_exception('DB_ERROR', 'Gagal menyimpan booking.', 500);
            }
        }
    }

    /**
     * Format sama dengan M_instant_booking agar nomor HBL tidak berubah:
     *  - tujuan US tertentu : LAX26 / NYK26 + id 5 digit
     *  - lainnya            : <huruf awal region><kode port>-<kode region><periode><id 5 digit>
     */
    public function build_house_bl($id, $regionName, $regionCode, $portCode, $destination, $transCity)
    {
        $periode = $this->config->item('booking_periode', 'booking_api') ?: date('y');
        $dest  = strtoupper(trim((string) $destination));
        $trans = strtoupper(trim((string) $transCity));
        $seq   = str_pad((string) $id, 5, '0', STR_PAD_LEFT);

        $prefix = null;
        if ($trans === '' || $trans === 'LOS ANGELES' || $trans === 'NEW YORK') {
            if (in_array($dest, ['LOS ANGELES', 'HOUSTON', 'SEATTLE', 'OAKLAND'], true)) {
                $prefix = 'LAX' . $periode;
            } elseif (in_array($dest, ['NEW YORK', 'CHICAGO', 'ATLANTA', 'MIAMI', 'KANSAS CITY', 'BALTIMORE'], true)) {
                $prefix = 'NYK' . $periode;
            }
        }

        if ($prefix !== null) {
            return $prefix . $seq;
        }
        return substr($regionName, 0, 1) . $portCode . '-' . $regionCode . $periode . $seq;
    }

    /* =================================================================
     * UPDATE
     * ================================================================= */

    /** Update data booking (tanpa mengubah kode, HBL, pemilik, region, link). */
    public function update_booking($bookingCode, array $header, array $detail)
    {
        foreach (['booking_code', 'house_bl', 'shipper_id', 'region_id', 'created_at', 'created_by', 'status', 'sales'] as $locked) {
            unset($header[$locked]);
        }
        unset($detail['booking_id'], $detail['region_id'], $detail['created_at']);

        $this->dbx->trans_begin();
        try {
            $this->update_or_throw(self::T_BOOKING, $header, ['booking_code' => $bookingCode]);
            $this->update_or_throw(self::T_DETAIL, $detail, ['booking_id' => $bookingCode]);
            $this->dbx->trans_commit();
        } catch (Throwable $e) {
            $this->dbx->trans_rollback();
            throw $e instanceof Booking_exception
                ? $e
                : new Booking_exception('DB_ERROR', 'Gagal mengubah booking.', 500);
        }
    }

    /** PEB / NPE / jalur / karantina. $customs hanya berisi kolom yang dikirim. */
    public function update_customs($bookingCode, array $customs)
    {
        if (!$customs) {
            return;
        }

        $this->dbx->trans_begin();
        try {
            $customs['updated_at'] = date('Y-m-d H:i:s');
            $this->update_or_throw(self::T_DETAIL, $customs, ['booking_id' => $bookingCode]);

            if (array_key_exists('peb_number', $customs)) {
                $this->update_or_throw(
                    self::T_BOOKING,
                    ['peb' => $customs['peb_number'] ? 1 : 0, 'updated_at' => date('Y-m-d H:i:s')],
                    ['booking_code' => $bookingCode]
                );
            }
            $this->dbx->trans_commit();
        } catch (Throwable $e) {
            $this->dbx->trans_rollback();
            throw $e instanceof Booking_exception
                ? $e
                : new Booking_exception('DB_ERROR', 'Gagal menyimpan data kepabeanan.', 500);
        }
    }

    /* =================================================================
     * LINK
     * ================================================================= */

    public function add_links($bookingCode, $coloader, array $links, $actor)
    {
        $this->dbx->trans_begin();
        try {
            $created = [];
            foreach ($links as $link) {
                if ($this->insert_link($bookingCode, $coloader, $link, $actor)) {
                    $created[] = $link['bc_no'];
                }
            }
            $this->dbx->trans_commit();
            return $created;
        } catch (Throwable $e) {
            $this->dbx->trans_rollback();
            throw $e instanceof Booking_exception
                ? $e
                : new Booking_exception('DB_ERROR', 'Gagal menyimpan linked BC.', 500);
        }
    }

    /**
     * @return bool true bila dibuat, false bila link yang sama sudah aktif (idempotent).
     */
    private function insert_link($bookingCode, $coloader, array $link, $actor)
    {
        $existing = $this->dbx->query(
            'SELECT booking_code FROM ' . self::T_LINK . '
              WHERE linked_company = ? AND linked_bc_no = ? AND status = \'active\'
              FOR UPDATE',
            [$coloader, $link['bc_no']]
        )->row_array();

        if ($existing) {
            if ($existing['booking_code'] === $bookingCode) {
                return false;
            }
            throw new Booking_exception(
                'LINK_CONFLICT',
                "BC {$coloader} {$link['bc_no']} sudah ter-link aktif ke booking lain.",
                409,
                ['linked_bc' => "{$link['bc_no']} sudah dipakai booking {$existing['booking_code']}"]
            );
        }

        $this->insert_or_throw(self::T_LINK, [
            'booking_code'   => $bookingCode,
            'linked_company' => $coloader,
            'linked_bc_no'   => $link['bc_no'],
            'linked_hbl'     => $link['hbl'] !== '' ? $link['hbl'] : null,
            'link_type'      => 'COLOAD',
            'status'         => 'active',
            'created_by'     => $actor,
            'created_at'     => date('Y-m-d H:i:s'),
        ]);
        return true;
    }

    /** @return int jumlah link yang dibatalkan */
    public function cancel_link($bookingCode, $coloader, $bcNo, $reason, $actor)
    {
        $this->update_or_throw(self::T_LINK, [
            'status'        => 'cancelled',
            'cancelled_by'  => $actor,
            'cancelled_at'  => date('Y-m-d H:i:s'),
            'cancel_reason' => $reason,
        ], [
            'booking_code'   => $bookingCode,
            'linked_company' => $coloader,
            'linked_bc_no'   => $bcNo,
            'status'         => 'active',
        ]);
        return $this->dbx->affected_rows();
    }

    /**
     * Batalkan booking (tidak dihapus, riwayat tetap ada): status 'cancelled'
     * dan semua linked BC aktif ikut diputus.
     * @return int jumlah link yang diputus
     */
    public function cancel_booking($bookingCode, $reason, $actor)
    {
        $now = date('Y-m-d H:i:s');
        $this->dbx->trans_begin();
        try {
            $this->update_or_throw(self::T_BOOKING, [
                'status'     => 'cancelled',
                'updated_at' => $now,
            ], ['booking_code' => $bookingCode]);

            $this->update_or_throw(self::T_LINK, [
                'status'        => 'cancelled',
                'cancelled_by'  => $actor,
                'cancelled_at'  => $now,
                'cancel_reason' => 'Booking dibatalkan: ' . $reason,
            ], ['booking_code' => $bookingCode, 'status' => 'active']);
            $links = $this->dbx->affected_rows();

            $this->dbx->trans_commit();
            return $links;
        } catch (Throwable $e) {
            $this->dbx->trans_rollback();
            throw $e instanceof Booking_exception
                ? $e
                : new Booking_exception('DB_ERROR', 'Gagal membatalkan booking.', 500);
        }
    }

    /* =================================================================
     * KUNCI & PELEPASAN CARGO YANG SUDAH DI GUDANG
     * ================================================================= */

    /**
     * Nomor job tempat booking ini sudah masuk, '' bila belum.
     * Patokan utama: job_no. Kolom stuffing_plan berisi RENCANA stuffing dari form
     * (Consol / Coload / ...), BUKAN tanda sudah masuk job; hanya dianggap job bila
     * isinya berbentuk nomor job (mis. GCL-1002610055) pada data lama.
     */
    public function job_ref(array $b)
    {
        $job = trim((string) ($b['job_no'] ?? ''));
        if ($job !== '') {
            return $job;
        }
        $sp = trim((string) ($b['stuffing_plan'] ?? ''));
        return preg_match('/^[A-Z]{2,5}-\d{3}/i', $sp) ? $sp : '';
    }

    /**
     * Alasan booking terkunci untuk diubah / dibatalkan.
     *   ''            tidak terkunci
     *   'SHIPPED'     shipped on board
     *   'IN_STUFFING' sudah masuk job (job_no), atau proses gudang selain "received"
     *   'RECEIVED'    cargo sudah diterima gudang, BELUM masuk stuffing plan / job
     */
    public function lock_reason(array $b)
    {
        if ((int) ($b['shipped_on_board'] ?? 0) === 1) {
            return 'SHIPPED';
        }
        if ($this->job_ref($b) !== '') {
            return 'IN_STUFFING';
        }
        $ws = trim((string) ($b['warehouse_status'] ?? ''));
        $rd = trim((string) ($b['received_date'] ?? ''));
        $received = $rd !== '' && strpos($rd, '0000-00-00') !== 0;
        if ($ws !== '' && stripos($ws, 'receiv') === false) {
            return 'IN_STUFFING';   // status gudang lanjutan (stuffing, gate in, ...)
        }
        return ($ws !== '' || $received) ? 'RECEIVED' : '';
    }

    /**
     * Lepas booking coload yang cargonya SUDAH diterima gudang tetapi BELUM masuk
     * stuffing plan / job: status 'cancelled', semua linked BC diputus, catatan di remark.
     * Kunci baris (FOR UPDATE) dan periksa ulang di dalam transaksi, supaya booking yang
     * baru saja dimasukkan ke job oleh ops Gateway tidak ikut terlepas.
     *
     * @return array ['links' => int, 'already' => bool, 'warehouse_status', 'received_date']
     */
    public function release_received($bookingCode, $reason, $actor, $partner)
    {
        $now = date('Y-m-d H:i:s');
        $this->dbx->trans_begin();
        try {
            $q = $this->dbx->query('SELECT * FROM ' . self::T_BOOKING . ' WHERE booking_code = ? FOR UPDATE', [$bookingCode]);
            $b = $q ? $q->row_array() : null;
            if (!$b) {
                throw new Booking_exception('NOT_FOUND', 'Booking tidak ditemukan.', 404);
            }
            if ($b['status'] === 'cancelled') {
                $this->dbx->trans_commit();
                return ['links' => 0, 'already' => true, 'warehouse_status' => $b['warehouse_status'], 'received_date' => $b['received_date']];
            }
            $lock = $this->lock_reason($b);
            if ($lock !== 'RECEIVED') {
                throw new Booking_exception('BOOKING_LOCKED',
                    'Booking ' . $bookingCode . ' tidak bisa dilepas: ' . ($lock === 'SHIPPED' ? 'cargo sudah shipped on board.' : ($lock === 'IN_STUFFING'
                        ? 'cargo sudah masuk stuffing plan / job Gateway.' : 'cargo belum diterima gudang, gunakan pembatalan biasa.')), 409);
            }

            $note   = "[{$now}] Dilepas ke coloader {$partner} (cargo sudah diterima gudang"
                    . ($b['warehouse_status'] !== '' ? ': ' . $b['warehouse_status'] : '') . ', belum masuk stuffing plan) oleh '
                    . $actor . '. Alasan: ' . $reason;
            $remark = trim((string) $b['remark']);
            $this->update_or_throw(self::T_BOOKING, [
                'status'     => 'cancelled',
                'remark'     => $remark === '' ? $note : $remark . "\n" . $note,
                'updated_at' => $now,
            ], ['booking_code' => $bookingCode]);

            $this->update_or_throw(self::T_LINK, [
                'status'        => 'cancelled',
                'cancelled_by'  => $actor,
                'cancelled_at'  => $now,
                'cancel_reason' => mb_substr('Dilepas ke coloader (cargo sudah di gudang): ' . $reason, 0, 255),
            ], ['booking_code' => $bookingCode, 'status' => 'active']);
            $links = $this->dbx->affected_rows();

            $this->dbx->trans_commit();
            log_message('info', "M_booking_v2::release_received {$bookingCode} oleh {$actor}: {$reason}");
            return ['links' => $links, 'already' => false, 'warehouse_status' => $b['warehouse_status'], 'received_date' => $b['received_date']];
        } catch (Throwable $e) {
            $this->dbx->trans_rollback();
            throw $e instanceof Booking_exception
                ? $e
                : new Booking_exception('DB_ERROR', 'Gagal melepas booking.', 500);
        }
    }

    /* =================================================================
     * RESOLVE (scan WMS)
     * ================================================================= */

    /**
     * Terjemahkan kode hasil scan menjadi booking Gateway.
     * Urutan: booking_code -> house_bl -> linked BC aktif -> si_number (QR BC versi lama).
     *
     * @return array ['result' => 'found'|'ambiguous'|'not_found', ...]
     */
    public function resolve($code)
    {
        $code = trim((string) $code);

        // QR BC Gateway berisi booking_code (GTW-xxxx), cukup satu lookup.
        // Bila tidak ada di Gateway, kode itu masih mungkin BC coloader yang
        // memakai skema penomoran sama, jadi lanjut ke pencarian linked BC.
        $isGtw = self::is_gateway_bc($code);
        $b = $this->find_booking($isGtw ? strtoupper($code) : $code);
        if ($b) {
            // Coload antar cabang: BC asal cabang lain -> booking cabang tujuan yang melakukan stuffing
            $child = $this->dbx
                ->select('booking_code, linked_company, linked_bc_no, linked_hbl')
                ->from(self::T_LINK)
                ->where('linked_company', 'GW')
                ->where('linked_bc_no', $b['booking_code'])
                ->where('status', 'active')
                ->order_by('id', 'DESC')->limit(1)
                ->get()->row_array();
            if ($child) {
                $c = $this->find_booking($child['booking_code']);
                if ($c) {
                    return $this->resolved($c, 'linked_bc', $code, $child);
                }
            }
            return $this->resolved($b, 'booking_code', $code, null);
        }

        if (!$isGtw) {
            // Coload antar cabang: BC asal & BC coloader bisa memakai HBL yang sama (house_bl_shared = 1
            // di BC asal). Yang dipakai untuk scan adalah BC coloader (yang di-stuffing).
            $b = null;
            foreach ($this->dbx->get_where(self::T_BOOKING, ['house_bl' => $code])->result_array() as $r) {
                if ($b === null || (int) ($b['house_bl_shared'] ?? 0) === 1) {
                    $b = $r;
                }
            }
            if ($b) {
                return $this->resolved($b, 'house_bl', $code, null);
            }
        }

        $links = $this->dbx
            ->select('booking_code, linked_company, linked_bc_no, linked_hbl')
            ->from(self::T_LINK)
            ->where('status', 'active')
            ->group_start()
                ->where('linked_bc_no', $code)
                ->or_where('linked_hbl', $code)
            ->group_end()
            ->get()->result_array();

        if (count($links) === 1) {
            $b = $this->find_booking($links[0]['booking_code']);
            return $this->resolved($b, 'linked_bc', $code, $links[0]);
        }
        if (count($links) > 1) {
            return [
                'result'     => 'ambiguous',
                'scanned'    => $code,
                'matched_by' => 'linked_bc',
                'candidates' => $links,
            ];
        }

        // BC coloader yang link-nya sudah dibatalkan: cargo itu bukan lagi coload ke Gateway.
        // Jangan sampai fallback si_number di bawah tetap mencocokkannya ke booking Gateway lama.
        $cancelled = $this->dbx
            ->select('booking_code, linked_company, cancelled_at, cancel_reason')
            ->from(self::T_LINK)
            ->where('status', 'cancelled')
            ->where('linked_bc_no', $code)
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get()->row_array();
        if ($cancelled) {
            return ['result' => 'not_found', 'scanned' => $code, 'reason' => 'LINK_CANCELLED', 'cancelled_link' => $cancelled];
        }

        // Fallback masa transisi: QR di BC cetakan lama berisi si_number (tidak unik).
        // Matikan lewat config 'resolve_si_number_fallback' setelah BC lama habis di gudang.
        if (!$this->config->item('resolve_si_number_fallback', 'booking_api')) {
            return ['result' => 'not_found', 'scanned' => $code];
        }

        $rows = $this->dbx
            ->select('booking_code, house_bl, region_id, forwarder_name')
            ->get_where(self::T_BOOKING, ['si_number' => $code])
            ->result_array();

        if (count($rows) === 1) {
            return $this->resolved($this->find_booking($rows[0]['booking_code']), 'si_number', $code, null);
        }
        if (count($rows) > 1) {
            return [
                'result'     => 'ambiguous',
                'scanned'    => $code,
                'matched_by' => 'si_number',
                'candidates' => $rows,
            ];
        }

        return ['result' => 'not_found', 'scanned' => $code];
    }

    private function resolved(array $b, $by, $code, $link)
    {
        $d = $this->find_detail($b['booking_code']) ?: [];

        return [
            'result'      => 'found',
            'scanned'     => $code,
            'resolved_by' => $by,
            'booking'     => [
                'booking_code'     => $b['booking_code'],
                'house_bl'         => $b['house_bl'],
                'status'           => $b['status'],
                'region_id'        => $b['region_id'],
                'forwarder_name'   => $b['forwarder_name'],
                'warehouse'        => $b['warehouse'],
                'warehouse_status' => $b['warehouse_status'],
                'stuffing_plan'    => $b['stuffing_plan'],
                'job_no'           => $b['job_no'],
                'destination_city' => $b['destination_city'],
                'real_dest'        => $b['real_dest'],
                'expect_etd'       => $b['expect_etd'],
                'closing_cargo'    => $b['closing_cargo'],
                'cargo_type'       => $b['cargo_type'],
            ],
            'cargo' => [
                'quantity'     => isset($d['quantity']) ? (int) $d['quantity'] : null,
                'package_type' => $d['package_type'] ?? null,
                'weight'       => $d['weight'] ?? null,
                'volume'       => $d['volume'] ?? null,
                'marking'      => $d['marking'] ?? null,
            ],
            'customs' => [
                'peb_number'       => $d['peb_number'] ?? null,
                'npe_number'       => $d['npe_number'] ?? null,
                'jalur'            => $d['jalur'] ?? null,
                'karantina_status' => $d['karantina_status'] ?? null,
            ],
            'matched_link' => $link,
            'links'        => $this->get_links($b['booking_code']),
        ];
    }

    /* =================================================================
     * DB helpers: ubah kegagalan query menjadi exception.
     * Catatan: set db_debug = FALSE untuk koneksi 'master' di API,
     * agar error DB tidak dicetak sebagai halaman HTML.
     * ================================================================= */

    private function insert_or_throw($table, array $row)
    {
        if (!$this->dbx->insert($table, $row)) {
            $this->throw_db_error($table);
        }
    }

    private function update_or_throw($table, array $set, array $where)
    {
        if (!$this->dbx->where($where)->update($table, $set)) {
            $this->throw_db_error($table);
        }
    }

    private function throw_db_error($table)
    {
        $err = $this->dbx->error();
        log_message('error', "M_booking_v2 {$table}: [{$err['code']}] {$err['message']}");

        if ((int) $err['code'] === self::MYSQL_DUPLICATE) {
            if ($table === self::T_BOOKING && strpos($err['message'], 'booking_code') !== false) {
                throw new Booking_exception('DUPLICATE_BOOKING_CODE', 'Booking code bentrok.', 409);
            }
            if ($table === self::T_LINK) {
                throw new Booking_exception('LINK_CONFLICT', 'Linked BC sudah ter-link aktif ke booking lain.', 409);
            }
            throw new Booking_exception('DUPLICATE', 'Data duplikat.', 409);
        }
        throw new Booking_exception('DB_ERROR', 'Kesalahan database.', 500);
    }
}
