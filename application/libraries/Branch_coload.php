<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Aturan coload antar cabang Gateway (sama seperti sister company).
 *
 *   BC cabang asal tetap (shipper asli, HBL cabang asal). Cabang tujuan mendapat BC sendiri
 *   dengan shipper = akun "Gateway <cabang asal>" (config branch_shipper_accounts),
 *   ter-link ke BC asal lewat trnbooking_link (company GW).
 *
 *   stuffing_region hanya dipakai booking cara lama (satu booking untuk dua cabang) yang
 *   masih berjalan; coload baru tidak mengisinya lagi.
 */
class Branch_coload
{
    protected $branches = array();
    protected $aliases  = array();
    protected $others   = array();
    protected $accounts = array();

    public function __construct()
    {
        $CI =& get_instance();
        $CI->config->load('branch_coload', TRUE);
        $this->branches = (array) $CI->config->item('branch_coloaders', 'branch_coload');
        $this->aliases  = (array) $CI->config->item('coloader_aliases', 'branch_coload');
        $this->others   = (array) $CI->config->item('other_coloaders', 'branch_coload');
        $this->accounts = (array) $CI->config->item('branch_shipper_accounts', 'branch_coload');
    }

    /** id_shipper akun "Gateway <cabang asal>" di cabang tujuan, atau 0 bila belum dipetakan. */
    public function shipper_account($targetRegion, $originRegion)
    {
        foreach ($this->accounts as $target => $origins) {
            if (strcasecmp($target, (string) $targetRegion) !== 0) continue;
            foreach ((array) $origins as $origin => $id) {
                if (strcasecmp($origin, (string) $originRegion) === 0) return (int) $id;
            }
        }
        return 0;
    }

    /** Nama coloader baku (alias lama -> nama baku, huruf besar). */
    public function normalize($coloader)
    {
        $c = strtoupper(trim(preg_replace('/\s+/', ' ', (string) $coloader)));
        return isset($this->aliases[$c]) ? $this->aliases[$c] : $c;
    }

    /** Region cabang Gateway untuk nama coloader, atau null bila bukan cabang Gateway. */
    public function region_of($coloader)
    {
        $c = $this->normalize($coloader);
        return isset($this->branches[$c]) ? $this->branches[$c] : null;
    }

    /** Nama coloader untuk sebuah region (kebalikan region_of). */
    public function label_of($region)
    {
        foreach ($this->branches as $label => $r) {
            if (strcasecmp($r, (string) $region) === 0) return $label;
        }
        return (string) $region;
    }

    /** Pilihan dropdown Coloader untuk cabang ini (cabang sendiri tidak ditampilkan). */
    public function options($ownRegion)
    {
        $out = array();
        foreach ($this->branches as $label => $region) {
            if (strcasecmp($region, (string) $ownRegion) !== 0) $out[] = $label;
        }
        return array_merge($out, $this->others);
    }

    /**
     * Terjemahkan isian form menjadi kolom booking.
     * @return array [array $fields, string|null $error]
     *         $fields = stuffing_plan, coloader, stuffing_region (selalu null untuk coload baru),
     *                   target_region (cabang Gateway tujuan coload, null bila bukan cabang) -> bukan kolom DB
     */
    public function resolve($plan, $coloader, $ownRegion)
    {
        $plan = trim((string) $plan);
        if ($plan !== 'Coload') {
            return array(array('stuffing_plan' => $plan, 'coloader' => '', 'stuffing_region' => null, 'target_region' => null), null);
        }

        $c = $this->normalize($coloader);
        if ($c === '') {
            return array(array(), 'Pilih Coloader untuk Stuffing Plan Coload.');
        }

        $region = $this->region_of($c);
        if ($region !== null && strcasecmp($region, (string) $ownRegion) === 0) {
            return array(array(), 'Tidak bisa coload ke cabang sendiri (' . $c . '). Pilih Consol.');
        }

        return array(array('stuffing_plan' => 'Coload', 'coloader' => $c, 'stuffing_region' => null, 'target_region' => $region), null);
    }
}
