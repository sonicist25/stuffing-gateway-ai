<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Synology File Station API Client (untuk CodeIgniter 3)
 *
 * Cara pakai:
 *   1. Simpan file ini di application/models/Synology_filestation.php
 *   2. Buat config di application/config/synology.php, contoh:
 *
 *      $config['synology'] = [
 *          'base_url' => 'https://aurelindo.sg4.quickconnect.to',
 *          'account'  => 'nama_akun_readonly',
 *          'passwd'   => 'password_akun',
 *      ];
 *
 *   3. Load: $this->load->config('synology');
 *            $this->load->model('Synology_filestation');
 *            $this->Synology_filestation->init($this->config->item('synology'));
 */
class Synology_filestation extends CI_Model
{
    private $base_url;
    private $account;
    private $passwd;
    private $sid = null;

    // Kode error Synology yang berarti "sesi ini sudah tidak valid" -> perlu login ulang.
    // 106 = session timeout, 107 = session diinterupsi oleh login lain (session_name sama),
    // 119 = sid tidak ditemukan/kadaluarsa.
    private $session_error_codes = [106, 107, 119];

    public function init($config = [])
    {
        $this->base_url = rtrim($config['base_url'] ?? '', '/');
        $this->account  = $config['account']  ?? '';
        $this->passwd   = $config['passwd']   ?? '';

        // Pakai sid yang sudah tersimpan dari request sebelumnya (kalau ada),
        // supaya TIDAK login ke Synology setiap kali model ini dipakai.
        // Ini penting karena satu halaman "Stuffing Photos" bisa memicu puluhan
        // request <img> paralel; kalau tiap request login sendiri-sendiri,
        // Synology bisa menganggapnya "duplicate login" dan saling meng-invalidasi sid.
        $cached = $this->session->userdata('synology_sid');
        if (!empty($cached)) {
            $this->sid = $cached;
        }
    }

    private function request($path, $params)
    {
        $url = $this->base_url . $path . '?' . http_build_query($params);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $response = curl_exec($ch);

        if ($response === false) {
            log_message('error', 'Synology cURL error: ' . curl_error($ch));
            curl_close($ch);
            return null;
        }
        curl_close($ch);

        return json_decode($response, true);
    }

    /** Pastikan $this->sid terisi (dari cache atau login baru). */
    private function _ensure_sid()
    {
        if ($this->sid) return true;
        return $this->login();
    }

    /**
     * Panggil satu API JSON (SYNO.FileStation.List, dst) dengan sid otomatis,
     * dan otomatis login ulang + retry SATU KALI kalau ternyata sid basi/diinterupsi.
     */
    private function _call($path, $params)
    {
        if (!$this->_ensure_sid()) return null;

        $params['_sid'] = $this->sid;
        $result = $this->request($path, $params);

        $error_code = $result['error']['code'] ?? null;
        if (!empty($result) && empty($result['success']) && in_array($error_code, $this->session_error_codes, true)) {
            log_message('debug', "Synology sid basi (error {$error_code}), login ulang...");
            $this->session->unset_userdata('synology_sid');
            $this->sid = null;

            if ($this->login()) {
                $params['_sid'] = $this->sid;
                $result = $this->request($path, $params);
            }
        }

        return $result;
    }

    /** Login dan simpan session id (sid). $otp_code diisi kalau akun pakai 2FA. */
    public function login($otp_code = null)
    {
        $params = [
            'api'     => 'SYNO.API.Auth',
            'version' => 6,
            'method'  => 'login',
            'account' => $this->account,
            'passwd'  => $this->passwd,
            'session' => 'FileStation',
            'format'  => 'sid',
        ];
        if ($otp_code) {
            $params['otp_code'] = $otp_code;
        }

        $result = $this->request('/webapi/entry.cgi', $params);

        if (!empty($result['success'])) {
            $this->sid = $result['data']['sid'];
            // Simpan supaya request-request berikutnya (termasuk thumbnail lain yang
            // dimuat bersamaan) memakai sid yang SAMA, bukan login sendiri-sendiri.
            $this->session->set_userdata('synology_sid', $this->sid);
            return true;
        }

        log_message('error', 'Synology login gagal: ' . json_encode($result));
        return false;
    }

    public function logout()
    {
        if (!$this->sid) return;

        $this->request('/webapi/entry.cgi', [
            'api'     => 'SYNO.API.Auth',
            'version' => 6,
            'method'  => 'logout',
            'session' => 'FileStation',
            '_sid'    => $this->sid,
        ]);
        $this->sid = null;
        $this->session->unset_userdata('synology_sid');
    }

    /** Ambil daftar shared folder di root (untuk cek nama folder yang benar). */
    public function list_shared_folders()
    {
        $result = $this->_call('/webapi/entry.cgi', [
            'api'     => 'SYNO.FileStation.List',
            'version' => 2,
            'method'  => 'list_share',
        ]);

        return !empty($result['success']) ? $result['data']['shares'] : false;
    }

    /**
     * List isi file/folder pada path tertentu.
     * $folder_path contoh: '/StuffingGudang' atau '/DriveShared/Laporan2026'
     */
    public function list_files($folder_path, $extra = [])
    {
        $params = array_merge([
            'api'         => 'SYNO.FileStation.List',
            'version'     => 2,
            'method'      => 'list',
            'folder_path' => $folder_path,
            'additional'  => 'size,time,type',
        ], $extra);

        $result = $this->_call('/webapi/entry.cgi', $params);

        if (!empty($result['success'])) {
            return $result['data']['files'];
        }

        log_message('error', 'Synology list_files gagal: ' . json_encode($result));
        return false;
    }

    /**
     * Ambil isi file dari Synology dan kembalikan ke PHP (server-side).
     * Dipakai supaya sid/session Synology TIDAK pernah dikirim ke browser user,
     * beda dengan get_download_url() di bawah yang menaruh sid di URL.
     * Return: ['content' => binary, 'content_type' => string] atau false.
     */
    public function download_file($file_path)
    {
        if (!$this->_ensure_sid()) return false;

        $result = $this->_download_raw($file_path);

        // Kalau balasannya JSON (bukan file), berarti error -> cek apakah sid basi, retry sekali.
        if ($result['is_json_error']) {
            $error_code = $result['json']['error']['code'] ?? null;
            if (in_array($error_code, $this->session_error_codes, true)) {
                log_message('debug', "Synology sid basi saat download (error {$error_code}), login ulang...");
                $this->session->unset_userdata('synology_sid');
                $this->sid = null;

                if ($this->login()) {
                    $result = $this->_download_raw($file_path);
                }
            }
        }

        if ($result['is_json_error'] || $result['content'] === false) {
            log_message('error', 'Synology download_file gagal untuk ' . $file_path . ': ' . json_encode($result['json'] ?? $result['content']));
            return false;
        }

        return ['content' => $result['content'], 'content_type' => $result['content_type']];
    }

    /** Curl mentah ke endpoint download, pakai $this->sid saat ini. */
    private function _download_raw($file_path)
    {
        $url = $this->base_url . '/webapi/entry.cgi?' . http_build_query([
            'api'     => 'SYNO.FileStation.Download',
            'version' => 2,
            'method'  => 'download',
            'path'    => $file_path,
            'mode'    => 'open',
            '_sid'    => $this->sid,
        ]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        $content = curl_exec($ch);

        if ($content === false) {
            log_message('error', 'Synology download_file cURL error: ' . curl_error($ch));
            curl_close($ch);
            return ['content' => false, 'content_type' => null, 'is_json_error' => false, 'json' => null];
        }

        $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        $is_json_error = stripos((string) $content_type, 'application/json') !== false;
        $json = $is_json_error ? json_decode($content, true) : null;

        return ['content' => $content, 'content_type' => $content_type, 'is_json_error' => $is_json_error, 'json' => $json];
    }

    /** Bangun URL download langsung untuk satu file (dipakai sebagai href/src di view). Kurang aman, lihat catatan. */
    public function get_download_url($file_path)
    {
        if (!$this->_ensure_sid()) return false;

        return $this->base_url . '/webapi/entry.cgi?' . http_build_query([
            'api'     => 'SYNO.FileStation.Download',
            'version' => 2,
            'method'  => 'download',
            'path'    => $file_path,
            'mode'    => 'open',
            '_sid'    => $this->sid,
        ]);
    }

    // =====================================================================
    // HELPER PENCOCOKAN NAMA FOLDER
    // Dipakai karena nama folder di NAS tidak akan pernah 100% sama dengan
    // data di database (beda spasi, ada tambahan ukuran/tujuan, dll).
    // =====================================================================

    /** Uppercase, buang karakter selain huruf/angka jadi spasi tunggal. "HALU2502842" tetap "HALU2502842". */
    private function _normalize($str)
    {
        $str = strtoupper((string) $str);
        $str = preg_replace('/[^A-Z0-9]+/', ' ', $str);
        return trim(preg_replace('/\s+/', ' ', $str));
    }

    /** Sama seperti _normalize() tapi spasi/simbol DIHAPUS TOTAL (bukan diganti spasi tunggal). "HALU 2502842" dan "HALU2502842" jadi sama-sama "HALU2502842". */
    private function _normalize_compact($str)
    {
        return preg_replace('/[^A-Z0-9]/', '', strtoupper((string) $str));
    }

    /**
     * Cari folder customer berdasarkan HURUF DEPAN nama folder saja, mis. kolom
     * grid `num` berisi "C" -> cocokkan ke folder "C. NOATUM LOGI..." ATAU "C, NOATUM LOGI...".
     * Pemisahnya (titik/koma/spasi) diabaikan sepenuhnya, cuma huruf di depannya yang dibandingkan.
     */
    public function find_folder_by_letter($parent_path, $letter)
    {
        $items = $this->list_files($parent_path);
        if ($items === false) return false;

        $letter_norm = strtoupper(trim((string) $letter));
        $letter_norm = rtrim($letter_norm, ".,;: \t"); // jaga-jaga kalau "C." ikut terkirim, bukan cuma "C"
        if ($letter_norm === '') return false;

        foreach ($items as $item) {
            if (empty($item['isdir'])) continue;

            $name = trim($item['name']);
            // huruf/abjad di paling depan, SEBELUM titik/koma/spasi pertama yang jadi pemisah
            if (preg_match('/^([A-Za-z]+)\s*[.,]/', $name, $m)) {
                if (strtoupper($m[1]) === $letter_norm) {
                    return $item['name'];
                }
            }
        }

        return false;
    }

    /**
     * Cari SATU nama sub-folder di $parent_path yang cocok dengan $needle.
     * $mode:
     *   'exact'          -> nama folder (setelah dinormalisasi) harus persis sama
     *   'prefix'         -> nama folder harus DIAWALI $needle, spasi disamakan jadi satu spasi
     *   'prefix_compact' -> sama seperti 'prefix' TAPI semua spasi/simbol dihapus total dulu di
     *                       kedua sisi sebelum dibandingkan. WAJIB dipakai untuk No. Container,
     *                       karena penamaan folder di NAS ternyata tidak konsisten: kadang ada
     *                       spasi ("HALU 2502842"), kadang tidak ("YMMU7200543").
     *   'contains'       -> nama folder mengandung $needle atau sebaliknya (buat nama Customer,
     *                       yang bisa beda prefix seperti "E. " atau kepanjangan/singkatan)
     * Return: nama folder ASLI (bukan yang sudah dinormalisasi) atau false kalau tidak ketemu.
     */
    public function find_folder($parent_path, $needle, $mode = 'prefix')
    {
        $items = $this->list_files($parent_path);
        if ($items === false) return false;

        $needle_norm    = $this->_normalize($needle);
        $needle_compact = $this->_normalize_compact($needle);
        $best = null;
        $best_diff = null;

        foreach ($items as $item) {
            if (empty($item['isdir'])) continue;
            $name_norm = $this->_normalize($item['name']);

            if ($mode === 'exact' && $name_norm === $needle_norm) {
                return $item['name'];
            }
            if ($mode === 'prefix' && strpos($name_norm, $needle_norm) === 0) {
                return $item['name'];
            }
            if ($mode === 'prefix_compact') {
                $name_compact = $this->_normalize_compact($item['name']);
                if ($needle_compact !== '' && strpos($name_compact, $needle_compact) === 0) {
                    return $item['name'];
                }
            }
            if ($mode === 'contains' && $needle_norm !== '' &&
                (strpos($name_norm, $needle_norm) !== false || strpos($needle_norm, $name_norm) !== false)) {
                $diff = abs(strlen($name_norm) - strlen($needle_norm));
                if ($best === null || $diff < $best_diff) {
                    $best = $item['name'];
                    $best_diff = $diff;
                }
            }
        }

        return $best !== null ? $best : false;
    }

    /**
     * Cari folder tanggal seperti "12 SEPTEMBER" berdasarkan angka tanggal saja,
     * supaya tidak masalah kalau NAS pakai "12 SEPTEMBER" vs "12 September 2026" vs "12" saja.
     */
    public function find_day_folder($parent_path, $day_num)
    {
        $items = $this->list_files($parent_path);
        if ($items === false) return false;

        foreach ($items as $item) {
            if (empty($item['isdir'])) continue;
            if (preg_match('/^0*(\d{1,2})\b/', trim($item['name']), $m)) {
                if ((int) $m[1] === (int) $day_num) {
                    return $item['name'];
                }
            }
        }
        return false;
    }

    /** Bersihkan No. Container jadi "LLLLDDDDDDD" tanpa spasi/simbol (mis. "HALU2502842"). Kecocokan ke folder dilakukan via prefix_compact, jadi tidak perlu tebak-tebak posisi spasi di sini. */
    public static function format_container_prefix($container_no)
    {
        return preg_replace('/[^A-Z0-9]/', '', strtoupper(trim((string) $container_no)));
    }
}