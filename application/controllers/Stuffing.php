<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Controller untuk browse & auto-locate foto/laporan stuffing gudang dari
 * Synology Drive milik surveyor. Struktur folder di NAS:
 *
 *   /<root gudang>/<Tahun>/<Bulan Indonesia>/<Tgl Bulan>/<No Container (...)>/<Customer>/file
 *
 * Contoh nyata:
 *   /GATEWAY/GCL GODOWN/2026/SEPTEMBER/12 SEPTEMBER/HALU 2502842 (20) SHANGHAI/E. GCL CIKARANG 4 PLTS
 *
 * Sedangkan di database kita cuma punya:
 *   - warehouse code (utk tentukan root gudang)
 *   - tanggal stuffing (Y-m-d)
 *   - cont_seal_no  = "HALU2502842 / 234236"   (No Container / No Seal)
 *   - customer      = shipper_print pada row detail yang dicentang (OPSIONAL)
 *
 * Jadi endpoint locate() yang menyusun path itu otomatis, dengan pencocokan
 * folder yang fleksibel (lihat Synology_filestation::find_folder / find_day_folder).
 *
 * Tidak perlu custom routing apa pun di application/config/routes.php — semua path
 * dikirim lewat query string (?warehouse=&p=), jadi cukup default CI3 routing biasa:
 *   POST /stuffing/locate
 *   GET  /stuffing/browse?warehouse=...&p=...
 *   GET  /stuffing/download?warehouse=...&p=...
 */
class Stuffing extends CI_Controller
{
    /**
     * Pemetaan nilai #dropdownWarehouse (persis seperti tersimpan di kolom `warehouse`,
     * contoh nyata: "GCL WAREHOUSE") -> path absolut root gudang itu di NAS.
     *
     * WAJIB dicek ulang: kode kamu (baris ~3829 trn_stuffing_plan.js) menyebut ADA
     * beberapa nilai warehouse berbeda untuk "GCL" -> 'GCL WAREHOUSE', 'BANDUNG GCL WAREHOUSE',
     * 'SARIADI SUB'. Saya asumsikan ketiganya masuk ke folder "GCL GODOWN" yang sama di
     * screenshot kamu, TAPI kalau "BANDUNG GCL WAREHOUSE" itu gudang fisik lain yang punya
     * folder sendiri di NAS (atau malah tidak ada sama sekali di NAS ini), pisahkan / hapus
     * baris itu. Ambil daftar lengkap nilai dropdown dari tabel REF_WRH lalu cocokkan satu-satu
     * dengan nama folder asli via list_shared_folders() / list_files('/').
     */
    private $warehouse_root_map = [
        'GCL WAREHOUSE'         => '/GATEWAY/GCL GODOWN',
        'BANDUNG GCL WAREHOUSE' => '/GATEWAY/GCL GODOWN', // TODO: cek, mungkin harus folder lain
        'SARIADI SUB'           => '/GATEWAY/GCL GODOWN', // TODO: cek, mungkin harus folder lain
        'AIRIN WAREHOUSE'       => '/GATEWAY/AIRIN GODOWN', // TODO: sesuaikan nama persis di dropdown
        'BRJ WAREHOUSE'         => '/GATEWAY/BRJ GODOWN',   // TODO: sesuaikan nama persis di dropdown
        'GLOBAL AUTO MANDIRI'   => '/Global Auto Mandiri',  // TODO: sesuaikan nama persis di dropdown
        'WINFAST'               => '/WINFAST',              // TODO: sesuaikan nama persis di dropdown
    ];

    private $bulan_indo = [
        1 => 'JANUARI', 2 => 'FEBRUARI', 3 => 'MARET', 4 => 'APRIL',
        5 => 'MEI', 6 => 'JUNI', 7 => 'JULI', 8 => 'AGUSTUS',
        9 => 'SEPTEMBER', 10 => 'OKTOBER', 11 => 'NOVEMBER', 12 => 'DESEMBER',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->load->config('synology');
        $this->load->model('Synology_filestation');
        $this->Synology_filestation->init($this->config->item('synology'));
    }

    /**
     * INI ENDPOINT UTAMA UNTUK TOMBOL "STUFFING PHOTOS".
     * POST /stuffing/locate
     *   warehouse    : kode warehouse (key di $warehouse_root_map)
     *   tanggal      : tanggal stuffing, format d/m/Y sesuai #txtStuffingDate (mis. "11/09/2026")
     *   cont_seal_no : "HALU2502842 / 234236" (boleh cuma "HALU2502842" juga)
     *   customer_letter : OPSIONAL, dari kolom `num` di row detail yg dicentang (mis. "A", "C").
     *                      Dicocokkan ke HURUF DEPAN nama folder customer (sebelum . atau ,).
     *                      Ini cara yang DIPRIORITASKAN.
     *   customer        : OPSIONAL, fallback lama berbasis nama customer (fuzzy, kalau letter kosong).
     */
    public function locate()
    {
        $warehouse      = $this->input->post('warehouse');
        $tanggal        = $this->input->post('tanggal');
        $cont_seal_no   = $this->input->post('cont_seal_no');
        $customer_letter = trim((string) $this->input->post('customer_letter')); // kolom `num`, mis. "A", "C"
        $customer_name   = trim((string) $this->input->post('customer'));        // fallback lama, cocokkan nama

        $root = $this->_warehouse_root($warehouse);
        if (!$root) {
            return $this->_json(['error' => "Warehouse '{$warehouse}' belum dipetakan. Cek Stuffing::\$warehouse_root_map."], 400);
        }

        // #txtStuffingDate memakai datepicker format dd/mm/yy, mis. "11/09/2026".
        // JANGAN pakai strtotime() untuk ini, bisa salah dibaca sebagai mm/dd/yyyy.
        $date = DateTime::createFromFormat('d/m/Y', trim((string) $tanggal));
        if (!$date) {
            // fallback kalau ternyata dikirim sebagai Y-m-d
            $date = DateTime::createFromFormat('Y-m-d', trim((string) $tanggal));
        }
        if (!$date) {
            return $this->_json(['error' => "Tanggal '{$tanggal}' tidak valid (harus dd/mm/yyyy)."], 400);
        }

        $year  = $date->format('Y');
        $day   = (int) $date->format('j');
        $bulan = $this->bulan_indo[(int) $date->format('n')];

        // --- Level Tahun/Bulan/Tanggal/Container, dengan toleransi ±3 hari kalau kombinasi
        //     tanggal+container PERSIS tidak ketemu (surveyor kadang upload/simpan foto container
        //     beberapa hari sebelum/sesudah tanggal stuffing di sistem). Container dicek DI SETIAP
        //     kandidat tanggal, bukan cuma berhenti begitu folder tanggalnya ada. Urutan coba:
        //     tanggal persis -> H+1..H+3 -> H-1..H-3 (drift maju lebih sering terjadi makanya dicoba duluan). ---
        $date_search_days = 3; // ganti angka ini kalau perlu rentang lebih lebar/sempit
        $candidates = [$date];
        for ($i = 1; $i <= $date_search_days; $i++) {
            $candidates[] = (clone $date)->modify("+{$i} day");
        }
        for ($i = 1; $i <= $date_search_days; $i++) {
            $candidates[] = (clone $date)->modify("-{$i} day");
        }

        $container_no     = trim(explode('/', (string) $cont_seal_no)[0]);
        $container_prefix = Synology_filestation::format_container_prefix($container_no);

        $path_container = null;
        $day_folder       = null;
        $container_folder = null;
        $used_date        = null;
        $tried_days       = []; // buat pesan error yang informatif kalau semua kandidat gagal

        foreach ($candidates as $cand) {
            $cand_path_year    = $root . '/' . $cand->format('Y');
            $cand_bulan        = $this->bulan_indo[(int) $cand->format('n')];
            $cand_bulan_folder = $this->Synology_filestation->find_folder($cand_path_year, $cand_bulan, 'exact');
            if (!$cand_bulan_folder) continue;

            $cand_path_bulan = $cand_path_year . '/' . $cand_bulan_folder;
            $cand_day_folder = $this->Synology_filestation->find_day_folder($cand_path_bulan, (int) $cand->format('j'));
            if (!$cand_day_folder) continue;

            $tried_days[] = $cand->format('d/m/Y') . " (\"{$cand_day_folder}\")";
            $cand_path_day = $cand_path_bulan . '/' . $cand_day_folder;

            $cand_container_folder = $this->Synology_filestation->find_folder($cand_path_day, $container_prefix, 'prefix_compact');
            if ($cand_container_folder) {
                $day_folder       = $cand_day_folder;
                $container_folder = $cand_container_folder;
                $path_container   = $cand_path_day . '/' . $cand_container_folder;
                $used_date        = $cand;
                break;
            }
        }

        if (!$path_container) {
            $detail = $tried_days ? ('Folder tanggal yang dicek: ' . implode(', ', $tried_days) . '.')
                                   : "Folder tanggal {$date->format('d/m/Y')} (±1 hari) tidak ada di {$root}/{$year}.";
            return $this->_json(['error' => "Folder container '{$container_prefix}' tidak ditemukan. {$detail}"], 404);
        }

        // Kasih tahu frontend kalau yang dipakai bukan tanggal persis, TEGAS minta user perbaiki datanya
        $date_note = null;
        if ($used_date->format('Y-m-d') !== $date->format('Y-m-d')) {
            $date_note = "Tanggal stuffing di sistem (" . $date->format('d/m/Y') . ") TIDAK SESUAI dengan folder aktual di NAS "
                . "(" . $used_date->format('d/m/Y') . ", folder \"{$day_folder}\"). Mohon segera perbaiki tanggal stuffing job ini di sistem.";
        }

        // --- Level Customer, HANYA kalau row detail dicentang & mengirim huruf/nama customer ---
        $path_final = $path_container;

        if ($customer_letter !== '') {
            $customer_folder = $this->Synology_filestation->find_folder_by_letter($path_container, $customer_letter);
            if (!$customer_folder) {
                return $this->_json(['error' => "Folder customer dengan kode '{$customer_letter}' tidak ditemukan di {$path_container}."], 404);
            }
            $path_final = $path_container . '/' . $customer_folder;
        } elseif ($customer_name !== '') {
            $customer_folder = $this->Synology_filestation->find_folder($path_container, $customer_name, 'contains');
            if (!$customer_folder) {
                return $this->_json(['error' => "Folder customer '{$customer_name}' tidak ditemukan di {$path_container}."], 404);
            }
            $path_final = $path_container . '/' . $customer_folder;
        }

        $listing = $this->_list_path($path_final, $root);
        $listing['warehouse']      = $warehouse;
        $listing['resolved_path']  = $path_final;
        if ($date_note) {
            $listing['date_note'] = $date_note;
        }

        $this->_json($listing);
    }

    /**
     * GET /stuffing/browse?warehouse=GCL%20WAREHOUSE&p=2026/SEPTEMBER
     * $p dikirim sebagai query string (bukan URI segment) supaya tidak perlu custom
     * routing dan tidak kena masalah karakter seperti "(" ")" di nama folder NAS.
     */
    public function browse()
    {
        $warehouse = $this->input->get('warehouse');
        $root = $this->_warehouse_root($warehouse);
        if (!$root) {
            return $this->_json(['error' => "Warehouse '{$warehouse}' belum dipetakan."], 400);
        }

        $subpath   = $this->_sanitize_path($this->input->get('p'));
        $full_path = $root . ($subpath !== '' ? '/' . $subpath : '');

        $listing = $this->_list_path($full_path, $root);
        $listing['warehouse'] = $warehouse;
        $listing['path']      = $subpath;

        $this->_json($listing);
    }

    /**
     * GET /stuffing/download?warehouse=GCL%20WAREHOUSE&p=2026/SEPTEMBER/.../file.jpg
     * Stream file dari Synology ke browser TANPA expose sid Synology ke client.
     */
    public function download()
    {
        $warehouse = $this->input->get('warehouse');
        $root = $this->_warehouse_root($warehouse);
        if (!$root) { show_404(); return; }

        $subpath   = $this->_sanitize_path($this->input->get('p'));
        $full_path = $root . '/' . $subpath;

        $result = $this->Synology_filestation->download_file($full_path);
        if (!$result) { show_404(); return; }

        $filename = basename($full_path);

        header('Content-Type: ' . ($result['content_type'] ?: 'application/octet-stream'));
        header('Content-Disposition: inline; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($result['content']));
        echo $result['content'];
    }

    private function _warehouse_root($code)
    {
        $code = strtoupper(trim((string) $code));
        return $this->warehouse_root_map[$code] ?? false;
    }

    /** List isi $full_path, pisah folder/file, dan sertakan rel_path (relatif ke $root) buat dipakai link browse/download berikutnya. */
    private function _list_path($full_path, $root)
    {
        $items = $this->Synology_filestation->list_files($full_path);
        if ($items === false) {
            return ['error' => "Gagal membaca folder di NAS: {$full_path}"];
        }

        $folders = [];
        $files   = [];

        foreach ($items as $item) {
            $rel_path = ltrim(str_replace($root, '', $item['path']), '/');
            $entry    = ['name' => $item['name'], 'rel_path' => $rel_path];

            if (!empty($item['isdir'])) {
                $folders[] = $entry;
            } else {
                $entry['size'] = $item['additional']['size'] ?? null;
                $entry['time'] = $item['additional']['time']['mtime'] ?? null;
                $files[] = $entry;
            }
        }

        return ['folders' => $folders, 'files' => $files];
    }

    /** Cegah path traversal (../) keluar dari folder root. */
    private function _sanitize_path($subpath)
    {
        $subpath = str_replace('\\', '/', (string) $subpath);
        $parts   = array_filter(explode('/', $subpath), function ($p) {
            return $p !== '' && $p !== '.' && $p !== '..';
        });
        return implode('/', $parts);
    }

    private function _json($data, $code = 200)
    {
        $this->output
            ->set_status_header($code)
            ->set_content_type('application/json')
            ->set_output(json_encode($data));
    }
}
