<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;

/**
 * Pembaca & validator payload JSON bersarang.
 * Path memakai titik, misal "parties.shipper.name".
 */
class Booking_payload
{
    public $errors = [];
    private $in;

    public function __construct($in)
    {
        $this->in = is_array($in) ? $in : [];
    }

    public function exists($path)
    {
        $a = $this->in;
        foreach (explode('.', $path) as $k) {
            if (!is_array($a) || !array_key_exists($k, $a)) {
                return false;
            }
            $a = $a[$k];
        }
        return true;
    }

    public function raw($path)
    {
        $a = $this->in;
        foreach (explode('.', $path) as $k) {
            if (!is_array($a) || !array_key_exists($k, $a)) {
                return null;
            }
            $a = $a[$k];
        }
        return $a;
    }

    public function add($path, $msg)
    {
        if (!isset($this->errors[$path])) {
            $this->errors[$path] = $msg;
        }
    }

    private function blank($v)
    {
        return $v === null || (is_string($v) && trim($v) === '');
    }

    /** @return string ('' bila kosong) */
    public function str($path, $max, $required = false, $upper = false)
    {
        $v = $this->raw($path);
        if ($this->blank($v)) {
            if ($required) $this->add($path, 'wajib diisi');
            return '';
        }
        if (!is_scalar($v)) {
            $this->add($path, 'harus berupa teks');
            return '';
        }
        $v = trim((string) $v);
        if (mb_strlen($v) > $max) {
            $this->add($path, "maksimal {$max} karakter");
        }
        return $upper ? mb_strtoupper($v) : $v;
    }

    /** @return int|null */
    public function int($path, $required = false, $min = null, $max = null)
    {
        $v = $this->raw($path);
        if ($this->blank($v)) {
            if ($required) $this->add($path, 'wajib diisi');
            return null;
        }
        if (filter_var($v, FILTER_VALIDATE_INT) === false) {
            $this->add($path, 'harus bilangan bulat');
            return null;
        }
        $v = (int) $v;
        if (($min !== null && $v < $min) || ($max !== null && $v > $max)) {
            $this->add($path, "harus antara {$min} dan {$max}");
        }
        return $v;
    }

    /** @return float|null */
    public function dec($path, $required = false, $min = null, $max = null)
    {
        $v = $this->raw($path);
        if ($this->blank($v)) {
            if ($required) $this->add($path, 'wajib diisi');
            return null;
        }
        if (!is_numeric($v)) {
            $this->add($path, 'harus angka (gunakan titik sebagai desimal)');
            return null;
        }
        $v = (float) $v;
        if (($min !== null && $v < $min) || ($max !== null && $v > $max)) {
            $this->add($path, "harus antara {$min} dan {$max}");
        }
        return $v;
    }

    /** @return string|null format Y-m-d */
    public function date($path, $required = false)
    {
        $v = $this->raw($path);
        if ($this->blank($v)) {
            if ($required) $this->add($path, 'wajib diisi');
            return null;
        }
        $d = DateTime::createFromFormat('!Y-m-d', (string) $v);
        if (!$d || $d->format('Y-m-d') !== (string) $v) {
            $this->add($path, 'format tanggal harus YYYY-MM-DD');
            return null;
        }
        return $d->format('Y-m-d');
    }

    /** @return string|null format Y-m-d H:i:s */
    public function datetime($path, $required = false)
    {
        $v = $this->raw($path);
        if ($this->blank($v)) {
            if ($required) $this->add($path, 'wajib diisi');
            return null;
        }
        $v = str_replace('T', ' ', trim((string) $v));
        foreach (['!Y-m-d H:i:s', '!Y-m-d H:i', '!Y-m-d'] as $fmt) {
            $d = DateTime::createFromFormat($fmt, $v);
            if ($d && $d->format(ltrim($fmt, '!')) === $v) {
                return $d->format('Y-m-d H:i:s');
            }
        }
        $this->add($path, 'format harus YYYY-MM-DD atau YYYY-MM-DD HH:MM[:SS]');
        return null;
    }

    /** @return string ('' bila kosong) */
    public function enum($path, array $allowed, $required = false)
    {
        $v = $this->raw($path);
        if ($this->blank($v)) {
            if ($required) $this->add($path, 'wajib diisi');
            return '';
        }
        $v = is_scalar($v) ? mb_strtoupper(trim((string) $v)) : '';
        foreach ($allowed as $a) {
            if (mb_strtoupper($a) === $v) {
                return $a;
            }
        }
        $this->add($path, 'harus salah satu dari: ' . implode(', ', $allowed));
        return '';
    }

    public function flag($path)
    {
        $v = $this->raw($path);
        return filter_var($v, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
    }
}

/**
 * Booking API v2 (export).
 *
 *  POST /api/booking_v2              buat booking (+ linked BC + data kepabeanan)
 *  GET  /api/booking_v2?booking_code ambil booking milik sendiri
 *  PUT  /api/booking_v2              ubah booking (sebelum diterima gudang)
 *  PUT  /api/booking_v2/customs      isi / ubah PEB, NPE, jalur, karantina
 *  POST /api/booking_v2/link         tambah linked BC ke booking
 *  POST /api/booking_v2/link_cancel  batalkan linked BC
 *  GET  /api/booking_v2/resolve?code [internal WMS] terjemahkan hasil scan
 */
#[\AllowDynamicProperties]
class Booking_v2 extends RestController
{
    const ZERO_DATETIME = '0000-00-00 00:00:00';

    private $editable_customs = [
        'aju_number', 'peb_number', 'peb_date', 'npe_number', 'npe_date',
        'jalur', 'jalur_date', 'karantina_status', 'karantina_number', 'karantina_date',
    ];

    public function __construct()
    {
        parent::__construct();

        // Booking v2 memeriksa aksesnya sendiri (JWT customer, X-Partner-Key, X-Internal-Key).
        // Bebaskan dari API key RestServer (rest_enable_keys) agar partner & WMS tidak
        // ditolak 403 "Invalid API key" sebelum sampai ke pemeriksaan di atas.
        foreach (['index_get', 'index_post', 'index_put', 'customs_put', 'link_post', 'link_cancel_post', 'cancel_post', 'resolve_get'] as $m) {
            $this->methods[$m]['key'] = false;
        }

        $this->config->load('booking_api', TRUE);
        $this->load->library(['Customer_auth', 'Booking_notifier']);
        $this->load->model('api/m_booking_v2');
    }

    /* =================================================================
     * POST /api/booking_v2
     * ================================================================= */
    public function index_post($sub = null)
    {
        if ($this->unknown_endpoint($sub)) return;
        $auth = $this->auth('create');
        if (!$auth) return;

        $in = $this->body();
        $idShipper = $this->resolve_shipper($auth, (int) ($in['id_shipper'] ?? 0));
        if ($idShipper <= 0) {
            return $this->fail('FORBIDDEN', 'Shipper tidak termasuk dalam akses user.', 403);
        }

        $shipper = $this->m_booking_v2->get_shipper($idShipper);
        if (!$shipper) {
            return $this->fail('FORBIDDEN', 'Data shipper pada akun tidak ditemukan.', 403);
        }

        $p = new Booking_payload($in);
        list($header, $detail) = $this->parse_booking($p, empty($auth['partner']));
        $customs = $this->parse_customs($p, 'customs', []);
        $detail  = array_merge($detail, $customs);

        list($coloader, $links) = $this->parse_coloader_links($p, $idShipper, true);

        if ($p->errors) {
            return $this->fail('VALIDATION_ERROR', 'Data booking tidak valid.', 422, $p->errors);
        }

        $now = date('Y-m-d H:i:s');
        $header += [
            'type_of_booking'     => 'EXPORT',
            'origin_country'      => 'INDONESIA',
            'destination_country' => '',
            'clearance'           => 0,
            'peb'                 => !empty($customs['peb_number']) ? 1 : 0,
            'shipper_id'          => $idShipper,
            'sales'               => $shipper['sales'],
            'region_id'           => $shipper['region_id'],
            'consignee_id'        => 0,
            'notify_id'           => 0,
            'drop_location'       => '',
            'pickup_location'     => '',
            'status'              => 'finalize',
            'total'               => 0,
            'created_by'          => $idShipper,
            'file_msds'           => '',
            'file_invoice'        => '',
            'file_packing_list'   => '',
            'file_bl'             => '',
            'file_co'             => '',
            'pickup_datetime'     => null,
            'created_at'          => $now,
            'currency'            => 'USD',
            'value_for_custom'    => '0',
            'value_for_carrier'   => '0',
            'grouping'            => '',   // diisi trigger
            'real_dest'           => '',   // diisi trigger
            'job_no'              => '',
            'warehouse_status'    => '',
            'stuffing_plan'       => '',
            'coloader'            => '',
            'for_delivery'        => '',
            'payable'             => 'PREPAID',
            'shipped_on_board'    => 0,
            'received_date'       => '0000-00-00 00:00:00',
            'stuffing_date'       => '0000-00-00 00:00:00',
            'gate_date'           => '0000-00-00 00:00:00',
            'received_by'         => '',
        ];

        $detail += [
            'region_id'    => $shipper['region_id'],
            'as_agent'     => '',
            'do_number'    => '',
            'container_no' => '',
            'description_print' => '',
            'shipper_note' => '',
            'created_at'   => $now,
            'updated_at'   => null,
        ];

        try {
            $res = $this->m_booking_v2->create($header, $detail, $links, $coloader, $this->actor($auth));
        } catch (Booking_exception $e) {
            return $this->fail($e->error_code, $e->getMessage(), $e->http_status, $e->errors);
        }

        $this->booking_notifier->notify_new_booking([
            'booking_id'   => $res['id'],
            'booking_code' => $res['booking_code'],
            'house_bl'     => $res['house_bl'],
            'id_shipper'   => $idShipper,
            'header'       => $header,
            'detail'       => $detail,
            'links'        => $links,
            'file_other'   => $header['file_other'],
        ]);

        return $this->ok('BOOKING_CREATED', 'Booking berhasil dibuat.', [
            'booking_code'  => $res['booking_code'],
            'house_bl'      => $res['house_bl'],
            'coloader_code' => $coloader,
            'linked_bc'     => $links,
        ], 201);
    }

    /* =================================================================
     * GET /api/booking_v2?booking_code=
     * ================================================================= */
    public function index_get($sub = null)
    {
        if ($this->unknown_endpoint($sub)) return;
        $auth = $this->auth('view');
        if (!$auth) return;

        $code = trim((string) $this->qparam('booking_code'));
        if ($code === '') {
            return $this->fail('VALIDATION_ERROR', 'booking_code wajib diisi.', 422);
        }

        $booking = $this->load_owned_booking($auth, $code);
        if (!$booking) return;

        return $this->ok('OK', '', [
            'booking' => $booking,
            'detail'  => $this->m_booking_v2->find_detail($code),
            'links'   => $this->m_booking_v2->get_links($code, false),
        ]);
    }

    /* =================================================================
     * PUT /api/booking_v2   (full replace field yang boleh diubah)
     * ================================================================= */
    public function index_put($sub = null)
    {
        if ($this->unknown_endpoint($sub)) return;
        $auth = $this->auth('update');
        if (!$auth) return;

        $in = $this->body();
        $code = trim((string) ($in['booking_code'] ?? ''));
        if ($code === '') {
            return $this->fail('VALIDATION_ERROR', 'booking_code wajib diisi.', 422);
        }

        $booking = $this->load_owned_booking($auth, $code);
        if (!$booking) return;

        if ($this->is_in_warehouse_flow($booking)) {
            return $this->fail('BOOKING_LOCKED',
                'Booking sudah diterima gudang / masuk stuffing plan. Perubahan data harus melalui operasional.', 409);
        }

        $p = new Booking_payload($in);
        list($header, $detail) = $this->parse_booking($p, empty($auth['partner']));
        unset($detail['description_print']);

        if ($p->errors) {
            return $this->fail('VALIDATION_ERROR', 'Data booking tidak valid.', 422, $p->errors);
        }

        $header['updated_at'] = date('Y-m-d H:i:s');
        $detail['updated_at'] = $header['updated_at'];

        try {
            $this->m_booking_v2->update_booking($code, $header, $detail);
        } catch (Booking_exception $e) {
            return $this->fail($e->error_code, $e->getMessage(), $e->http_status, $e->errors);
        }

        return $this->ok('BOOKING_UPDATED', 'Booking berhasil diubah.', ['booking_code' => $code]);
    }

    /* =================================================================
     * PUT /api/booking_v2/customs   (partial: hanya field yang dikirim)
     * ================================================================= */
    public function customs_put()
    {
        $auth = $this->auth('update');
        if (!$auth) return;

        $in = $this->body();
        $code = trim((string) ($in['booking_code'] ?? ''));
        if ($code === '') {
            return $this->fail('VALIDATION_ERROR', 'booking_code wajib diisi.', 422);
        }

        $booking = $this->load_owned_booking($auth, $code);
        if (!$booking) return;

        if ((int) $booking['shipped_on_board'] === 1) {
            return $this->fail('BOOKING_LOCKED', 'Cargo sudah shipped on board.', 409);
        }

        $current = $this->m_booking_v2->find_detail($code) ?: [];
        $p = new Booking_payload($in);
        $customs = $this->parse_customs($p, 'customs', $current);

        if ($p->errors) {
            return $this->fail('VALIDATION_ERROR', 'Data kepabeanan tidak valid.', 422, $p->errors);
        }
        if (!$customs) {
            return $this->fail('VALIDATION_ERROR', 'Tidak ada field customs yang dikirim.', 422);
        }

        try {
            $this->m_booking_v2->update_customs($code, $customs);
        } catch (Booking_exception $e) {
            return $this->fail($e->error_code, $e->getMessage(), $e->http_status, $e->errors);
        }

        $after = $this->m_booking_v2->find_detail($code);
        $out = [];
        foreach ($this->editable_customs as $f) {
            $out[$f] = $after[$f] ?? null;
        }
        return $this->ok('CUSTOMS_UPDATED', 'Data kepabeanan tersimpan.', ['booking_code' => $code, 'customs' => $out]);
    }

    /* =================================================================
     * POST /api/booking_v2/link
     * ================================================================= */
    public function link_post()
    {
        $auth = $this->auth('update');
        if (!$auth) return;

        $in = $this->body();
        $code = trim((string) ($in['booking_code'] ?? ''));
        if ($code === '') {
            return $this->fail('VALIDATION_ERROR', 'booking_code wajib diisi.', 422);
        }

        $booking = $this->load_owned_booking($auth, $code);
        if (!$booking) return;

        if ($this->m_booking_v2->job_ref($booking) !== '' || (int) $booking['shipped_on_board'] === 1) {
            return $this->fail('BOOKING_LOCKED', 'Booking sudah masuk job Gateway, link tidak bisa diubah.', 409);
        }

        $p = new Booking_payload($in);
        list($coloader, $links) = $this->parse_coloader_links($p, (int) $booking['shipper_id'], false);

        if (!$p->errors && !$links) {
            $p->add('linked_bc', 'minimal 1 BC');
        }
        if ($p->errors) {
            return $this->fail('VALIDATION_ERROR', 'Linked BC tidak valid.', 422, $p->errors);
        }

        try {
            $created = $this->m_booking_v2->add_links($code, $coloader, $links, $this->actor($auth));
        } catch (Booking_exception $e) {
            return $this->fail($e->error_code, $e->getMessage(), $e->http_status, $e->errors);
        }

        return $this->ok('LINK_SAVED', count($created) . ' link baru dibuat.', [
            'booking_code' => $code,
            'created'      => $created,
            'links'        => $this->m_booking_v2->get_links($code),
        ]);
    }

    /* =================================================================
     * POST /api/booking_v2/link_cancel
     * ================================================================= */
    public function link_cancel_post()
    {
        $auth = $this->auth('update');
        if (!$auth) return;

        $p = new Booking_payload($this->body());
        $code   = $p->str('booking_code', 50, true);
        $bcNo   = $p->str('bc_no', 100, true);
        $reason = $p->str('reason', 255, true);
        if ($p->errors) {
            return $this->fail('VALIDATION_ERROR', 'Data pembatalan tidak valid.', 422, $p->errors);
        }

        $booking = $this->load_owned_booking($auth, $code);
        if (!$booking) return;

        if ($this->m_booking_v2->job_ref($booking) !== '' || (int) $booking['shipped_on_board'] === 1) {
            return $this->fail('BOOKING_LOCKED', 'Booking sudah masuk job Gateway, link tidak bisa diubah.', 409);
        }

        list($coloader) = $this->parse_coloader_links($p, (int) $booking['shipper_id'], false);
        if ($p->errors || !$coloader) {
            return $this->fail('VALIDATION_ERROR', 'Coloader tidak dapat ditentukan.', 422, $p->errors);
        }

        try {
            $n = $this->m_booking_v2->cancel_link($code, $coloader, $bcNo, $reason, $this->actor($auth));
        } catch (Booking_exception $e) {
            return $this->fail($e->error_code, $e->getMessage(), $e->http_status, $e->errors);
        }

        if ($n === 0) {
            return $this->fail('NOT_FOUND', 'Link aktif tidak ditemukan.', 404);
        }
        return $this->ok('LINK_CANCELLED', 'Link dibatalkan.', [
            'booking_code' => $code,
            'links'        => $this->m_booking_v2->get_links($code),
        ]);
    }

    /* =================================================================
     * POST /api/booking_v2/cancel   {booking_code, reason[, release_received]}
     * Hanya pemilik booking. Idempotent.
     *  - Cargo belum diterima gudang: dibatalkan.
     *  - Cargo SUDAH diterima gudang tetapi BELUM masuk stuffing plan / job:
     *      ditolak 409 BOOKING_LOCKED dengan data.lock_reason = RECEIVED dan
     *      data.can_release = true (partner). Kirim ulang dengan
     *      release_received = true + reason (min 5 karakter) untuk melepas cargo
     *      ke coloader -> 200 BOOKING_RELEASED.
     *  - Sudah masuk stuffing plan / job atau shipped: selalu ditolak.
     * ================================================================= */
    public function cancel_post()
    {
        $auth = $this->auth('update');
        if (!$auth) return;

        $p = new Booking_payload($this->body());
        $code   = $p->str('booking_code', 50, true);
        $reason = $p->str('reason', 200, true);
        if ($p->errors) {
            return $this->fail('VALIDATION_ERROR', 'Data pembatalan tidak valid.', 422, $p->errors);
        }

        $booking = $this->load_owned_booking($auth, $code);
        if (!$booking) return;

        if ($booking['status'] === 'cancelled') {
            return $this->ok('BOOKING_CANCELLED', 'Booking sudah dibatalkan sebelumnya.', ['booking_code' => $code, 'links_cancelled' => 0]);
        }
        if ($this->is_in_warehouse_flow($booking)) {
            $lock       = $this->m_booking_v2->lock_reason($booking);
            // Hanya partner (sistem coloader) yang boleh menarik cargo coload-nya sendiri.
            $canRelease = $lock === 'RECEIVED' && !empty($auth['partner']);
            $lockData   = [
                'booking_code'     => $code,
                'lock_reason'      => $lock,
                'can_release'      => $canRelease,
                'warehouse_status' => (string) $booking['warehouse_status'],
                'received_date'    => (string) $booking['received_date'],
                'job_no'           => $this->m_booking_v2->job_ref($booking),
            ];

            if ($p->flag('release_received') === 1 && $canRelease) {
                if (mb_strlen($reason) < 5) {
                    return $this->fail('VALIDATION_ERROR', 'Alasan pelepasan minimal 5 karakter.', 422, ['reason' => 'minimal 5 karakter']);
                }
                try {
                    $r = $this->m_booking_v2->release_received($code, $reason, $this->actor($auth), $auth['partner']);
                } catch (Booking_exception $e) {
                    return $this->fail($e->error_code, $e->getMessage(), $e->http_status, $e->errors);
                }
                return $this->ok('BOOKING_RELEASED',
                    'Booking dibatalkan; cargo yang sudah diterima gudang dilepas ke coloader ' . $auth['partner'] . '.',
                    $lockData + ['links_cancelled' => $r['links'], 'released' => true]);
            }

            return $this->fail_data('BOOKING_LOCKED', $this->lock_message($code, $lock, $canRelease, $lockData['job_no']), 409, $lockData);
        }

        try {
            $n = $this->m_booking_v2->cancel_booking($code, $reason, $this->actor($auth));
        } catch (Booking_exception $e) {
            return $this->fail($e->error_code, $e->getMessage(), $e->http_status, $e->errors);
        }

        return $this->ok('BOOKING_CANCELLED', 'Booking dibatalkan.', ['booking_code' => $code, 'links_cancelled' => $n]);
    }

    /* =================================================================
     * GET /api/booking_v2/resolve?code=    (internal, untuk WMS)
     * Header: X-Internal-Key
     * ================================================================= */
    public function resolve_get()
    {
        $expected = (string) $this->config->item('wms_internal_key', 'booking_api');
        $given    = (string) $this->input->get_request_header('X-Internal-Key', TRUE);

        if ($expected === '' || !hash_equals($expected, $given)) {
            return $this->fail('UNAUTHORIZED', 'Unauthorized', 401);
        }

        $code = trim((string) $this->qparam('code'));
        if ($code === '' || mb_strlen($code) > 100) {
            return $this->fail('VALIDATION_ERROR', 'code wajib diisi (maks 100 karakter).', 422);
        }

        $r = $this->m_booking_v2->resolve($code);

        if ($r['result'] === 'found') {
            return $this->ok('RESOLVED', '', $r);
        }
        if ($r['result'] === 'ambiguous') {
            return $this->response([
                'status'  => false,
                'code'    => 'AMBIGUOUS',
                'message' => 'Kode cocok dengan lebih dari satu booking, perlu dipilih manual.',
                'data'    => $r,
            ], 409);
        }
        if (isset($r['reason']) && $r['reason'] === 'LINK_CANCELLED') {
            return $this->response([
                'status'  => false,
                'code'    => 'LINK_CANCELLED',
                'message' => 'BC ini pernah di-coload ke Gateway tetapi link-nya sudah dibatalkan. Cargo bukan milik stuffing Gateway.',
                'data'    => $r,
            ], 404);
        }
        return $this->fail('NOT_FOUND', 'Kode tidak dikenali sebagai BC Gateway maupun linked BC.', 404);
    }

    /* =================================================================
     * PARSING
     * ================================================================= */

    /**
     * @param bool $requireClosing customer portal wajib isi closing; partner (coload
     *                             server-to-server) boleh kosong, diisi ops Gateway dari jadwal.
     * @return array [header, detail] berisi field yang berasal dari payload
     */
    private function parse_booking(Booking_payload $p, $requireClosing = true)
    {
        $modes   = $this->config->item('allowed_modes', 'booking_api');
        $freight = $this->config->item('allowed_freight', 'booking_api');

        $routeType = $p->str('booking.route_type', 60) ?: 'DIRECT';
        $vessel    = $p->str('booking.vessel', 100);
        $voyage    = $p->str('booking.voyage', 30);
        // Sama dengan perilaku lama: rute DIRECT menyimpan voyage di kolom vessel.
        if ($routeType === 'DIRECT' && $vessel !== '') {
            $vessel = trim($vessel . ' ' . $voyage);
            $voyage = '';
            if (mb_strlen($vessel) > 100) $p->add('booking.vessel', 'vessel + voyage maksimal 100 karakter');
        }

        $etd          = $p->date('booking.etd', true);
        $eta          = $p->date('booking.eta');
        $closingDoc   = $p->datetime('booking.closing_doc', $requireClosing);
        $closingCargo = $p->datetime('booking.closing_cargo', $requireClosing);

        if ($etd && $eta && $eta < $etd) {
            $p->add('booking.eta', 'tidak boleh sebelum ETD');
        }
        if ($etd && $closingCargo && substr($closingCargo, 0, 10) > $etd) {
            $p->add('booking.closing_cargo', 'tidak boleh setelah ETD');
        }

        $cargoType = $p->enum('booking.cargo_type', ['GeneralCargo', 'DG']) ?: 'GeneralCargo';
        $pot = $p->str('booking.pot', 255, false, true);

        $header = [
            'mode'          => $p->enum('booking.mode', $modes, true),
            'movement_type' => $p->str('booking.movement_type', 100, true, true),
            'incoterm'      => $p->str('booking.incoterm', 10, true, true),
            'freight'       => $p->enum('booking.freight', $freight, true),
            'number_of_bl'  => $p->str('booking.number_of_bl', 255, true, true),
            'origin_city'   => $p->str('booking.pol', 255, true, true),
            'destination_city' => $p->str('booking.pod', 255, true, true),
            'trans_city'    => $pot,
            'pol_code'      => $p->str('booking.pol_code', 5, false, true),
            'pot_code'      => $p->str('booking.pot_code', 5, false, true),
            'pod_code'      => $p->str('booking.pod_code', 5, false, true),
            'route_type'    => $routeType,
            'schedule_id'   => (int) $p->int('booking.schedule_id', false, 0),
            'vessel'        => $vessel,
            'voyage'        => $voyage,
            'expect_etd'    => $etd,
            'eta'           => $eta,
            'closing_date'  => $closingDoc ?: self::ZERO_DATETIME,
            'closing_cargo' => $closingCargo ?: self::ZERO_DATETIME,
            'si_number'     => $p->str('booking.si_number', 100),
            'warehouse'     => $p->str('booking.warehouse', 255) ?: $this->config->item('default_warehouse', 'booking_api'),
            'cargo_type'    => $cargoType,
            'trucking'      => $p->str('booking.trucking', 15),
            'insurance'     => $p->flag('booking.insurance'),
            'remark'        => $p->str('booking.remark', 5000),
            'forwarder_name'=> $p->str('parties.shipper.name', 100, true, true),
            'file_other'    => $p->str('booking.file_other', 100),
        ];

        if ($header['file_other'] !== '' && !preg_match('/^[a-f0-9]{32}\.pdf$/', $header['file_other'])) {
            $p->add('booking.file_other', 'gunakan file_name dari endpoint upload_si');
        }

        $weight = $p->dec('cargo.weight', true, 0.001, 99999.999);
        $netto  = $p->dec('cargo.netto', false, 0, 99999.999);
        if ($weight !== null && $netto !== null && $netto > $weight) {
            $p->add('cargo.netto', 'tidak boleh lebih besar dari gross weight');
        }

        $dgClass = $p->str('cargo.dg_class', 50);
        if ($cargoType === 'DG' && $dgClass === '') {
            $p->add('cargo.dg_class', 'wajib diisi untuk cargo DG');
        }

        $shipperName    = $p->str('parties.shipper.name', 100, true, true);
        $shipperAddress = $p->str('parties.shipper.address', 200, true, true);

        $detail = [
            'note'                 => mb_substr($shipperName . "\n" . $shipperAddress, 0, 200),
            'shipper_name'         => $shipperName,
            'shipper_address'      => $shipperAddress,
            'consignee_name'       => $p->str('parties.consignee.name', 200, true, true),
            'consignee'            => $p->str('parties.consignee.address', 200, true, true),
            'notify_name'          => $p->str('parties.notify.name', 100, false, true),
            'notify'               => $p->str('parties.notify.address', 200, false, true) ?: '-',
            'cp_name'              => $p->str('parties.contact.name', 30),
            'cp_email'             => $p->str('parties.contact.email', 155),
            'quantity'             => $p->int('cargo.quantity', true, 1, 999999),
            'package_type'         => $p->str('cargo.package_type', 255, true, true),
            'weight'               => $weight,
            'netto'                => $netto,
            'volume'               => $p->dec('cargo.volume', true, 0.0001, 9999.9999),
            'dimension'            => $this->parse_dimension($p),
            'marking'              => $p->str('cargo.marking', 65535),
            'description_of_goods' => $p->str('cargo.description', 65535, true),
            'hs_code'              => $p->str('cargo.hs_code', 100),
            'dg_class'             => $dgClass,
            'container_type'       => $p->str('cargo.container_type', 255),
            'shipping_mark'        => $p->str('cargo.shipping_mark', 65535),
            // Rencana tiba di gudang. Default = closing cargo.
            'wh_arrival'           => $p->datetime('cargo.wh_arrival') ?: ($closingCargo ?: self::ZERO_DATETIME),
        ];

        $email = $detail['cp_email'];
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $p->add('parties.contact.email', 'format email tidak valid');
        }

        return [$header, $detail];
    }

    /** cargo.dimension: [{length, width, height, qty, unit}] -> JSON */
    private function parse_dimension(Booking_payload $p)
    {
        $raw = $p->raw('cargo.dimension');
        if ($raw === null || $raw === []) {
            return '[]';
        }
        if (!is_array($raw) || array_values($raw) !== $raw) {
            $p->add('cargo.dimension', 'harus berupa array');
            return '[]';
        }

        $out = [];
        foreach ($raw as $i => $row) {
            $path = "cargo.dimension.{$i}";
            $out[] = [
                'length' => $p->dec("{$path}.length", true, 0.01, 100000),
                'width'  => $p->dec("{$path}.width", true, 0.01, 100000),
                'height' => $p->dec("{$path}.height", true, 0.01, 100000),
                'qty'    => $p->int("{$path}.qty", true, 1, 999999),
                'unit'   => $p->enum("{$path}.unit", ['CM', 'M', 'INCH']) ?: 'CM',
            ];
        }
        return json_encode($out);
    }

    /**
     * Field kepabeanan. Hanya field yang ADA di payload yang dikembalikan,
     * sehingga dipakai untuk create maupun update parsial.
     * Field dikirim kosong/null = dihapus (NULL).
     *
     * @param array $current nilai saat ini di DB, untuk validasi silang
     */
    private function parse_customs(Booking_payload $p, $prefix, array $current)
    {
        $out = [];
        $map = [
            'aju_number'       => function ($k) use ($p) {
                $v = $p->str($k, 30, false, true);
                if ($v !== '' && !preg_match('/^[0-9A-Z]{6,30}$/', $v)) $p->add($k, 'hanya huruf/angka tanpa spasi');
                return $v !== '' ? $v : null;
            },
            'peb_number'       => function ($k) use ($p) { $v = $p->str($k, 30, false, true); return $v !== '' ? $v : null; },
            'peb_date'         => function ($k) use ($p) { return $p->date($k); },
            'npe_number'       => function ($k) use ($p) { $v = $p->str($k, 30, false, true); return $v !== '' ? $v : null; },
            'npe_date'         => function ($k) use ($p) { return $p->date($k); },
            'jalur'            => function ($k) use ($p) { return $p->enum($k, ['HIJAU', 'MERAH']) ?: null; },
            'jalur_date'       => function ($k) use ($p) { return $p->date($k); },
            'karantina_status' => function ($k) use ($p) { return $p->enum($k, ['TIDAK WAJIB', 'WAJIB']) ?: null; },
            'karantina_number' => function ($k) use ($p) { $v = $p->str($k, 50, false, true); return $v !== '' ? $v : null; },
            'karantina_date'   => function ($k) use ($p) { return $p->date($k); },
        ];

        foreach ($map as $field => $fn) {
            $path = "{$prefix}.{$field}";
            if ($p->exists($path)) {
                $out[$field] = $fn($path);
            }
        }

        // Validasi silang terhadap keadaan setelah perubahan
        $s = array_merge(array_intersect_key($current, $map), $out);
        $v = function ($f) use ($s) { return $s[$f] ?? null; };

        if ($v('peb_number') && !$v('peb_date'))   $p->add("{$prefix}.peb_date", 'wajib bila peb_number diisi');
        if ($v('peb_date') && !$v('peb_number'))   $p->add("{$prefix}.peb_number", 'wajib bila peb_date diisi');
        if ($v('npe_number') && !$v('peb_number')) $p->add("{$prefix}.npe_number", 'NPE memerlukan PEB');
        if ($v('npe_number') && !$v('npe_date'))   $p->add("{$prefix}.npe_date", 'wajib bila npe_number diisi');
        if ($v('npe_date') && $v('peb_date') && $v('npe_date') < $v('peb_date')) {
            $p->add("{$prefix}.npe_date", 'tidak boleh sebelum tanggal PEB');
        }
        if ($v('jalur') && !$v('peb_number'))      $p->add("{$prefix}.jalur", 'jalur memerlukan PEB');
        if ($v('jalur') && !$v('jalur_date'))      $p->add("{$prefix}.jalur_date", 'wajib bila jalur diisi');
        if ($v('karantina_number') && $v('karantina_status') !== 'WAJIB') {
            $p->add("{$prefix}.karantina_status", 'harus WAJIB bila nomor karantina diisi');
        }

        return $out;
    }

    /**
     * Tentukan coloader dari id_shipper pemilik booking + validasi linked_bc.
     *
     * @param bool $enforceRequired true saat create: wajibkan linked_bc bila require_link
     * @return array [coloader_code|null, links]
     */
    private function parse_coloader_links(Booking_payload $p, $idShipper, $enforceRequired)
    {
        $coloaders = $this->m_booking_v2->get_coloaders_for_shipper($idShipper);
        $requested = $p->str('coloader_code', 10, false, true);
        $raw       = $p->raw('linked_bc');
        $hasLinks  = is_array($raw) && count($raw) > 0;

        $col = null;
        if ($requested !== '') {
            foreach ($coloaders as $c) {
                if ($c['company_code'] === $requested) $col = $c;
            }
            if (!$col) {
                $p->add('coloader_code', 'tidak terdaftar untuk shipper ini');
                return [null, []];
            }
        } elseif (count($coloaders) === 1) {
            $col = $coloaders[0];
        } elseif (count($coloaders) > 1) {
            if ($hasLinks || $p->exists('bc_no')) {
                $p->add('coloader_code', 'wajib diisi, shipper ini terdaftar di lebih dari satu coloader');
            }
            return [null, []];
        }

        if (!$col) {
            if ($hasLinks) {
                $p->add('linked_bc', 'shipper ini tidak terdaftar sebagai coloader');
            }
            return [null, []];
        }

        if ($enforceRequired && (int) $col['require_link'] === 1 && !$hasLinks) {
            $p->add('linked_bc', "wajib diisi: booking dari {$col['company_name']} harus menyertakan BC asal");
        }

        if ($raw === null) {
            return [$col['company_code'], []];
        }
        if (!is_array($raw) || array_values($raw) !== $raw) {
            $p->add('linked_bc', 'harus berupa array');
            return [$col['company_code'], []];
        }
        if (count($raw) > 50) {
            $p->add('linked_bc', 'maksimal 50 BC per request');
            return [$col['company_code'], []];
        }

        $links = [];
        $seen  = [];
        foreach ($raw as $i => $item) {
            $path = "linked_bc.{$i}";
            if (is_string($item)) {
                $item = ['bc_no' => $item];
            }
            $lp  = new Booking_payload(is_array($item) ? $item : []);
            $bc  = $lp->str('bc_no', 100, true);
            $hbl = $lp->str('hbl', 100);
            foreach ($lp->errors as $k => $msg) {
                $p->add("{$path}.{$k}", $msg);
            }
            if ($bc === '') continue;

            if (!empty($col['bc_pattern']) && !preg_match("\x01^(?:{$col['bc_pattern']})$\x01u", $bc)) {
                $p->add("{$path}.bc_no", "format BC {$col['company_code']} tidak sesuai");
            }
            $key = mb_strtoupper($bc);
            if (isset($seen[$key])) {
                $p->add("{$path}.bc_no", 'duplikat di dalam request');
                continue;
            }
            $seen[$key] = true;
            $links[] = ['bc_no' => $bc, 'hbl' => $hbl];
        }

        return [$col['company_code'], $links];
    }

    /* =================================================================
     * HELPERS
     * ================================================================= */

    /**
     * Payload JSON utuh. Sengaja tidak memakai $this->post() tanpa key, karena
     * RestController meratakan key array bersarang ke level atas saat XSS clean.
     */
    private function body()
    {
        if (is_array($this->request->body)) {
            return $this->request->body;
        }
        $args = $this->request->method === 'put' ? $this->put() : $this->post();
        return is_array($args) ? $args : (array) json_decode(json_encode($args), true);
    }

    /** Query string. Beberapa versi RestController tidak menggabungkan query args ke get(). */
    private function qparam($key)
    {
        $v = $this->get($key);
        return $v !== null ? $v : $this->input->get($key, TRUE);
    }

    /**
     * Dua jalur autentikasi:
     *  - Partner (server-to-server sister company): header X-Partner-Code + X-Partner-Key.
     *    Hanya boleh bertindak atas id_shipper yang dipetakan ke coloader itu.
     *  - Customer portal: JWT via Customer_auth.
     */
    private function auth($action)
    {
        $partnerCode = strtoupper(trim((string) $this->input->get_request_header('X-Partner-Code', TRUE)));
        if ($partnerCode !== '') {
            $key  = (string) $this->input->get_request_header('X-Partner-Key', TRUE);
            $auth = $this->m_booking_v2->authenticate_partner($partnerCode, $key);
            if (!$auth) {
                $this->fail('UNAUTHORIZED', 'Partner ditolak: ' . $this->m_booking_v2->partner_error, 401);
                return null;
            }
            return $auth;
        }

        $perms = $this->config->item('permissions', 'booking_api');
        $auth  = $this->customer_auth->authenticate($perms[$action] ?? 'booking.create');

        if (empty($auth['ok'])) {
            $this->fail('UNAUTHORIZED', $auth['message'] ?? 'Unauthorized', (int) ($auth['status'] ?? 401));
            return null;
        }
        return $auth;
    }

    /** Booking hanya bisa diakses user yang punya akses ke shipper pemiliknya. 404 bila bukan miliknya. */
    /**
     * RestServer meneruskan /api/booking_v2/<x> yang method-nya tidak ada ke index_<verb>('<x>').
     * Tanpa penjagaan ini, endpoint yang salah ketik / belum ter-deploy diproses sebagai
     * "buat booking" (POST) atau "ubah booking" (PUT). Tolak dengan 404 yang jelas.
     */
    private function unknown_endpoint($sub)
    {
        if ($sub === null || $sub === '') {
            return false;
        }
        $this->fail('ENDPOINT_NOT_FOUND',
            'Endpoint ' . $this->input->method(TRUE) . ' /api/booking_v2/' . $sub . ' tidak dikenal di server Gateway ini.', 404);
        return true;
    }

    private function load_owned_booking(array $auth, $code)
    {
        $booking = $this->m_booking_v2->find_booking($code);
        $owner   = $booking ? (int) $booking['shipper_id'] : 0;

        if (!$booking || $this->resolve_shipper($auth, $owner) !== $owner) {
            $this->fail('NOT_FOUND', 'Booking tidak ditemukan.', 404);
            return null;
        }
        return $booking;
    }

    /** id_shipper yang boleh dipakai: diminta (bila diizinkan) atau default pertama. 0 = ditolak. */
    private function resolve_shipper(array $auth, $requested)
    {
        if (empty($auth['partner'])) {
            return $this->customer_auth->resolveShipperId($auth, $requested);
        }
        $ids = array_map(function ($a) { return (int) $a['id_shipper']; }, $auth['shipper_access']);
        if ($requested > 0) {
            return in_array((int) $requested, $ids, true) ? (int) $requested : 0;
        }
        return $ids ? $ids[0] : 0;
    }

    private function is_in_warehouse_flow(array $b)
    {
        return trim((string) $b['warehouse_status']) !== ''
            || $this->m_booking_v2->job_ref($b) !== ''   // stuffing_plan = rencana (Consol/Coload), bukan job
            || (int) $b['shipped_on_board'] === 1
            || (!empty($b['received_date']) && strpos($b['received_date'], '0000-00-00') !== 0);
    }

    private function actor(array $auth)
    {
        $u = $auth['user'] ?? [];
        return (string) ($u['email'] ?? $u['username'] ?? ('user#' . ($u['id'] ?? $u['user_id'] ?? '?')));
    }

    private function ok($code, $message, $data, $http = 200)
    {
        return $this->response([
            'status'  => true,
            'code'    => $code,
            'message' => $message,
            'data'    => $data,
        ], $http);
    }

    /** Pesan penolakan pembatalan sesuai alasan kunci. */
    private function lock_message($code, $lock, $canRelease, $jobNo = '')
    {
        switch ($lock) {
            case 'SHIPPED':
                return 'Booking ' . $code . ' tidak bisa dibatalkan: cargo sudah shipped on board.';
            case 'IN_STUFFING':
                return 'Booking ' . $code . ' tidak bisa dibatalkan: cargo sudah masuk stuffing plan / job Gateway'
                    . ($jobNo !== '' ? ' (' . $jobNo . ')' : '') . '. Minta ops Gateway mengeluarkannya dari job dulu.';
            case 'RECEIVED':
                return $canRelease
                    ? 'Booking ' . $code . ' tidak bisa dibatalkan langsung: cargo sudah diterima gudang Gateway (belum masuk stuffing plan). Perlu konfirmasi penarikan cargo dari sistem coloader, atau hubungi ops Gateway.'
                    : 'Booking ' . $code . ' tidak bisa dibatalkan: cargo sudah diterima gudang Gateway. Hubungi ops Gateway.';
        }
        return 'Booking ' . $code . ' tidak bisa dibatalkan: cargo sudah diterima gudang / masuk stuffing plan Gateway. Hubungi ops Gateway.';
    }

    /** Seperti fail(), tetapi membawa 'data' (dipakai klien untuk menentukan langkah berikutnya). */
    private function fail_data($code, $message, $http, array $data)
    {
        return $this->response(['status' => false, 'code' => $code, 'message' => $message, 'data' => $data], $http);
    }

    private function fail($code, $message, $http = 400, array $errors = [])
    {
        $body = ['status' => false, 'code' => $code, 'message' => $message];
        if ($errors) {
            $body['errors'] = $errors;
        }
        return $this->response($body, $http);
    }
}
