<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Cegah BC manual dengan shipper coloader (Osline / Winfast / HNS / akun cabang Gateway).
 *
 * Sumber aturan = database (jalankan database/gw_103_coloader_guard.sql):
 *   mstcoloader_shipper.manual_mode
 *     auto   -> booking dari akun ini masuk otomatis lewat API; BC manual ditolak
 *     manual -> coloader belum terintegrasi; BC manual boleh, WAJIB BC asal
 *     branch -> akun cabang Gateway; BC manual ditolak (pakai Coload -> GATEWAY <cabang>)
 *   mstcoloader_company.npwp
 *     akun shipper yang belum terdaftar tapi NPWP-nya sama dengan coloader dianggap
 *     'manual' (untuk GW: 'branch'), supaya akun baru/duplikat ikut tertangkap.
 *
 * Bila tabel/kolom belum ada di database tahun ini, tidak ada pembatasan (aman untuk DB lama).
 */
class Coloader_guard
{
    /** @var array cache per database: id_shipper => info|null */
    protected $cache = array();

    /** Informasi coloader untuk satu shipper, atau null bila shipper biasa. */
    public function classify($db, $idShipper)
    {
        $id  = (int) $idShipper;
        $all = $this->classify_many($db, array($id));
        return isset($all[$id]) ? $all[$id] : null;
    }

    /**
     * @param array $ids id_shipper
     * @return array id_shipper => array(mode, company_code, company_name, bc_pattern, bc_prefix, message)
     */
    public function classify_many($db, array $ids)
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (!$ids || !$this->ready($db)) {
            return array();
        }

        $out = array();

        // 1. Akun yang terdaftar eksplisit
        $q = $db->select('cs.id_shipper, cs.manual_mode, c.company_code, c.company_name, c.bc_pattern', false)
                ->from('mstcoloader_shipper cs')
                ->join('mstcoloader_company c', 'c.company_code = cs.company_code', 'inner')
                ->where('c.is_active', 1)
                ->where_in('cs.id_shipper', $ids)
                ->get();
        foreach (($q ? $q->result_array() : array()) as $r) {
            $out[(int) $r['id_shipper']] = $this->info($r['manual_mode'], $r);
        }

        // 2. Belum terdaftar: kenali dari NPWP
        $rest = array_values(array_diff($ids, array_keys($out)));
        if ($rest && $this->has_column($db, 'mstcoloader_company', 'npwp') && $this->has_column($db, 'mstshipper', 'shipper_npwp')) {
            $byNpwp = $this->companies_by_npwp($db);
            if ($byNpwp) {
                $q = $db->select('id_shipper, shipper_npwp')->where_in('id_shipper', $rest)->get('mstshipper');
                foreach (($q ? $q->result_array() : array()) as $r) {
                    $npwp = preg_replace('/\D/', '', (string) $r['shipper_npwp']);
                    if ($npwp !== '' && isset($byNpwp[$npwp])) {
                        $c = $byNpwp[$npwp];
                        $out[(int) $r['id_shipper']] = $this->info($c['company_code'] === 'GW' ? 'branch' : 'manual', $c);
                    }
                }
            }
        }
        return $out;
    }

    /** Nomor BC asal sesuai pola coloader? (pola kosong = tidak divalidasi) */
    public function bc_matches(array $info, $bc)
    {
        if (empty($info['bc_pattern'])) {
            return true;
        }
        return (bool) preg_match("\x01^(?:" . $info['bc_pattern'] . ")$\x01u", $bc);
    }

    /* ------------------------------------------------------------------ */

    protected function info($mode, array $c)
    {
        $name   = (string) $c['company_name'];
        $prefix = '';
        if (!empty($c['bc_pattern']) && preg_match('/^([A-Z]+-)/', $c['bc_pattern'], $m)) {
            $prefix = $m[1];
        }
        switch ($mode) {
            case 'auto':
                $msg = 'Booking untuk akun ini dibuat OTOMATIS oleh sistem ' . $name .
                       ($prefix !== '' ? ' (BC ' . $prefix . '...)' : '') .
                       '. Jangan buat BC manual: cari BC-nya di daftar Belum Masuk Job.';
                break;
            case 'branch':
                $msg = 'Akun ini milik cabang Gateway. Titipan antar cabang dibuat oleh cabang asal ' .
                       'dengan Stuffing Plan Coload -> GATEWAY <cabang tujuan>, bukan BC manual dengan shipper cabang.';
                break;
            default:
                $mode = 'manual';
                $msg  = 'Shipper ini coloader ' . $name . '. Isi BC asal' . ($prefix !== '' ? ' (' . $prefix . '...)' : '') .
                        ' yang tertempel di cargo.';
        }
        return array(
            'mode'         => $mode,
            'company_code' => (string) $c['company_code'],
            'company_name' => $name,
            'bc_pattern'   => (string) $c['bc_pattern'],
            'bc_prefix'    => $prefix,
            'message'      => $msg,
        );
    }

    protected function companies_by_npwp($db)
    {
        $out = array();
        $q = $db->select('company_code, company_name, bc_pattern, npwp')
                ->where('is_active', 1)->where('npwp IS NOT NULL', null, false)
                ->get('mstcoloader_company');
        foreach (($q ? $q->result_array() : array()) as $c) {
            foreach (explode(',', (string) $c['npwp']) as $n) {
                $n = preg_replace('/\D/', '', $n);
                if ($n !== '') $out[$n] = $c;
            }
        }
        return $out;
    }

    protected function ready($db)
    {
        $key = $db->database;
        if (!array_key_exists($key, $this->cache)) {
            $this->cache[$key] = $db->table_exists('mstcoloader_shipper')
                && $db->table_exists('mstcoloader_company')
                && $this->has_column($db, 'mstcoloader_shipper', 'manual_mode');
        }
        return $this->cache[$key];
    }

    protected function has_column($db, $table, $col)
    {
        return $db->table_exists($table) && in_array($col, $db->list_fields($table), true);
    }
}
